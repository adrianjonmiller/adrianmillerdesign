<?php

// DO NOT CALL THIS CLASS DIRECTLY. CALL VIA: pb_backupbuddy_destination in bootstrap.php.

class pb_backupbuddy_destination_s3 { // Change class name end to match destination name.
	
	const MINIMUM_CHUNK_SIZE = 5; // Minimum size, in MB to allow chunks to be. Anything less will not be chunked even if requested.
	const BACKUP_FILENAME_PATTERN = '/^backup-.*\.zip/i'; //  Used for matching during backup limits, etc to prevent processing non-BackupBuddy files.
	
	public static $destination_info = array(
		'name'			=>		'Amazon S3',
		'description'	=>		'Amazon S3 is a well known cloud storage provider. This destination is known to be reliable and works well with BackupBuddy. <b>New in BackupBuddy v4.1</b>: S3 now supports multipart chunked file transfers! <a href="http://aws.amazon.com/s3/" target="_new">Learn more here.</a>',
	);
	
	// Default settings. Should be public static for auto-merging.
	public static $default_settings = array(
		'type'						=>		's3',		// MUST MATCH your destination slug. Required destination field.
		'title'						=>		'',			// Required destination field.
		
		'accesskey'					=>		'',			// Amazon access key.
		'secretkey'					=>		'',			// Amazon secret key.
		'bucket'					=>		'',			// Amazon bucket to put into.
		
		'directory'					=>		'',			// Subdirectory to put into in addition to the site url directory.
		'ssl'						=>		'1',		// Whether or not to use SSL encryption for connecting.
		'server_encryption'			=>		'AES256',	// Encryption (if any) to have the destination enact. Empty string for none.
		'max_chunk_size'			=>		'100',		// Maximum chunk size in MB. Anything larger will be chunked up into pieces this size (or less for last piece). This allows larger files to be sent than would otherwise be possible. Minimum of 5mb allowed by S3.
		'archive_limit'				=>		'0',		// Maximum number of backups for this site in this directory for this account. No limit if zero 0.
		'manage_all_files'			=>		'1',		// Allow user to manage all files in S3? If enabled then user can view all files after entering their password. If disabled the link to view all is hidden.
		'region'					=>		's3.amazonaws.com',	// Endpoint to create buckets in. Although named region this is technically the ENDPOINT.
		'storage'					=>		'standard',	// Whether to use standard or reduced redundancy storage. Allowed values: standard, reduced
		
		// Do not store these for destination settings. Only used to pass to functions in this file.
		'_multipart_id'				=>		'',			// Instance var. Internal use only for continuing a chunked upload.
		'_multipart_partnumber'		=>		0,			// Instance var. Part number to upload next.
		'_multipart_file'			=>		'',			// Instance var. Internal use only to store the file that is currently set to be multipart chunked.
		'_multipart_remotefile'		=>		'',			// Instance var. Internal use only to store the remote filepath & file.
		'_multipart_counts'			=>		array(),	// Instance var. Multipart chunks to send. Generated by S3's get_multipart_counts().
		'_multipart_transferspeeds'	=>		array(),
	);
	
	
	
	
	
	/*	send()
	 *	
	 *	Send one or more files.
	 *	
	 *	@param		array			$files			Array of one or more files to send.
	 *	@return		boolean|array					True on success, false on failure, array if a multipart chunked send so there is no status yet.
	 */
	public static function send( $settings = array(), $files = array(), $send_id = '' ) {
		
		global $pb_backupbuddy_destination_errors;
		$backup_type_dir = '';
		$region = '';
		
		$settings['bucket'] = strtolower( $settings['bucket'] ); // Buckets must be lowercase.
		
		if ( !is_array( $files ) ) {
			$files = array( $files );
		}
		
		$limit = $settings['archive_limit'];
		$max_chunk_size = $settings['max_chunk_size'];
		$remote_path = self::get_remote_path( $settings['directory'] ); // Has leading and trailng slashes.
		if ( $settings['ssl'] == '0' ) {
			$disable_ssl = true;
		} else {
			$disable_ssl = false;
		}
		
		$multipart_id = $settings['_multipart_id'];
		$multipart_counts = $settings['_multipart_counts'];
		
		pb_backupbuddy::status( 'details', 'S3 remote path set to `' . $remote_path . '`.' );
		
		pb_backupbuddy::status( 'details', 'Loading S3 SDK library file...' );
		require_once( dirname( dirname( __FILE__ ) ) . '/_s3lib/aws-sdk/sdk.class.php' );
		pb_backupbuddy::status( 'details', 'S3 SDK file loaded.' );
		
		// S3 API talk.
		$manage_data = pb_backupbuddy_destination_s3::get_credentials( $settings );
		
		// Process multipart transfer that we already initiated in a previous PHP load.
		if ( $multipart_id != '' ) { // Multipart upload initiated and needs parts sent.
			
			// Create S3 instance.
			pb_backupbuddy::status( 'details', 'Creating S3 instance.' );
			$s3 = new AmazonS3( $manage_data );    // the key, secret, token
			if ( $disable_ssl === true ) {
				@$s3->disable_ssl(true);
			}
			pb_backupbuddy::status( 'details', 'S3 instance created.' );
			
			// Verify bucket exists; create if not. Also set region to the region bucket exists in.
			if ( false === self::_prepareBucketAndRegion( $s3, $settings ) ) {
				return false;
			}
			
			$this_part_number = $settings['_multipart_partnumber'] + 1;
			pb_backupbuddy::status( 'details', 'S3 beginning upload of part `' . $this_part_number . '` of `' . count( $settings['_multipart_counts'] ) . '` parts of file `' . $settings['_multipart_file'] . '` to remote location `' . $settings['_multipart_remotefile'] . '` with multipart ID `' . $settings['_multipart_id'] . '`.' );
			$response = $s3->upload_part( $manage_data['bucket'], $settings['_multipart_remotefile'], $settings['_multipart_id'], array(
				'expect'     => '100-continue',
				'fileUpload' => $settings['_multipart_file'],
				'partNumber' => $this_part_number,
				'seekTo'     => (integer) $settings['_multipart_counts'][ $settings['_multipart_partnumber'] ]['seekTo'],
				'length'     => (integer) $settings['_multipart_counts'][ $settings['_multipart_partnumber'] ]['length'],
			));
			
			if(!$response->isOK()) {
				$this_error = 'S3 unable to upload file part for multipart upload `' . $settings['_multipart_id'] . '`. Details: `' . print_r( $response, true ) . '`.';
				$pb_backupbuddy_destination_errors[] = $this_error;
				pb_backupbuddy::status( 'error', $this_error );
				return false;
			} else { // Send success.
				
				pb_backupbuddy::status( 'details', 'Success sending chunk. Upload details: `' . print_r( $response, true ) . '`.' );
				
				$uploaded_size = $response->header['_info']['size_upload'];
				$uploaded_speed = $response->header['_info']['speed_upload'];
				pb_backupbuddy::status( 'details', 'Uploaded size: ' .  pb_backupbuddy::$format->file_size( $uploaded_size ) . ', Speed: ' . pb_backupbuddy::$format->file_size( $uploaded_speed ) . '/sec.' );
				
			}
			
			
			// Load fileoptions to the send.
			pb_backupbuddy::status( 'details', 'About to load fileoptions data.' );
			require_once( pb_backupbuddy::plugin_path() . '/classes/fileoptions.php' );
			$fileoptions_obj = new pb_backupbuddy_fileoptions( backupbuddy_core::getLogDirectory() . 'fileoptions/send-' . $send_id . '.txt', $read_only = false, $ignore_lock = false, $create_file = false );
			if ( true !== ( $result = $fileoptions_obj->is_ok() ) ) {
				pb_backupbuddy::status( 'error', __('Fatal Error #9034.2344848. Unable to access fileoptions data.', 'it-l10n-backupbuddy' ) . ' Error: ' . $result );
				return false;
			}
			pb_backupbuddy::status( 'details', 'Fileoptions data loaded.' );
			$fileoptions = &$fileoptions_obj->options;
			
			
			$update_status = 'Sent part ' . $this_part_number . ' of ' . count( $settings['_multipart_counts'] ) . '.';
			
			
			// Made it here so success sending part. Increment for next part to send.
			$settings['_multipart_partnumber']++;
			
			if ( !isset( $settings['_multipart_counts'][ $settings['_multipart_partnumber'] ] ) ) { // No more parts exist for this file. Tell S3 the multipart upload is complete and move on.
				pb_backupbuddy::status( 'details', 'S3 getting parts with etags to notify S3 of completed multipart send.' );
				$etag_parts = $s3->list_parts( $manage_data['bucket'], $settings['_multipart_remotefile'], $settings['_multipart_id'] );
				pb_backupbuddy::status( 'details', 'S3 got parts list. Notifying S3 of multipart upload completion.' );
				$response = $s3->complete_multipart_upload( $manage_data['bucket'], $settings['_multipart_remotefile'], $settings['_multipart_id'], $etag_parts );
				if(!$response->isOK()) {
					$this_error = 'S3 unable to notify S3 of completion of all parts for multipart upload `' . $settings['_multipart_id'] . '`.';
					$pb_backupbuddy_destination_errors[] = $this_error;
					pb_backupbuddy::status( 'error', $this_error );
					return false;
				} else {
					pb_backupbuddy::status( 'details', 'S3 notified S3 of multipart completion.' );
				}
				
				pb_backupbuddy::status( 'details', 'S3 has no more parts left for this multipart upload. Clearing multipart instance variables.' );
				$settings['_multipart_partnumber'] = 0;
				$settings['_multipart_id'] = '';
				$settings['_multipart_file'] = '';
				$settings['_multipart_remotefile'] = ''; // Multipart completed so safe to prevent housekeeping of incomplete multipart uploads.
				$settings['_multipart_transferspeeds'][] = $uploaded_speed;
				
				// Overall upload speed average.
				$uploaded_speed = array_sum( $settings['_multipart_transferspeeds'] ) / count( $settings['_multipart_counts'] );
				pb_backupbuddy::status( 'details', 'Upload speed average of all chunks: `' . pb_backupbuddy::$format->file_size( $uploaded_speed ) . '`.' );
				
				$settings['_multipart_counts'] = array();
				
				// Update stats.
				$fileoptions['_multipart_status'] = $update_status;
				$fileoptions['finish_time'] = time();
				$fileoptions['status'] = 'success';
				if ( isset( $uploaded_speed ) ) {
					$fileoptions['write_speed'] = $uploaded_speed;
				}
				$fileoptions_obj->save();
				unset( $fileoptions );
			}
			
			
			
			// Schedule to continue if anything is left to upload for this multipart of any individual files.
			if ( ( $settings['_multipart_id'] != '' ) || ( count( $files ) > 0 ) ) {
				pb_backupbuddy::status( 'details', 'S3 multipart upload has more parts left. Scheduling next part send.' );
				$schedule_result = backupbuddy_core::schedule_single_event( time(), pb_backupbuddy::cron_tag( 'destination_send' ), array( $settings, $files, $send_id ) );
				if ( true === $schedule_result ) {
					pb_backupbuddy::status( 'details', 'Next S3 chunk step cron event scheduled.' );
				} else {
					pb_backupbuddy::status( 'error', 'Next S3 chunk step cron even FAILED to be scheduled.' );
				}
				spawn_cron( time() + 150 ); // Adds > 60 seconds to get around once per minute cron running limit.
				update_option( '_transient_doing_cron', 0 ); // Prevent cron-blocking for next item.
				
				return array( $settings['_multipart_id'], 'Sent part ' . $this_part_number . ' of ' . count( $settings['_multipart_counts'] ) . ' parts.' );
			}
		} // end if multipart continuation.
		
		
		require_once( pb_backupbuddy::plugin_path() . '/classes/fileoptions.php' );
		
		// Upload each file.
		foreach( $files as $file_id => $file ) {
			
			// Determine backup type directory (if zip).
			$backup_type_dir = '';
			$backup_type = '';
			if ( stristr( $file, '.zip' ) !== false ) { // If a zip try to determine backup type.
				pb_backupbuddy::status( 'details', 'S3: Zip file. Detecting backup type if possible.' );
				$serial = backupbuddy_core::get_serial_from_file( $file );
				
				// See if we can get backup type from fileoptions data.
				$backup_options = new pb_backupbuddy_fileoptions( backupbuddy_core::getLogDirectory() . 'fileoptions/' . $serial . '.txt', $read_only = true, $ignore_lock = true );
				if ( true !== ( $result = $backup_options->is_ok() ) ) {
					pb_backupbuddy::status( 'error', 'Unable to open fileoptions file `' . backupbuddy_core::getLogDirectory() . 'fileoptions/' . $serial . '.txt' . '`.' );
				} else {
					if ( isset( $backup_options->options['integrity']['detected_type'] ) ) {
						pb_backupbuddy::status( 'details', 'S3: Detected backup type as `' . $backup_options->options['integrity']['detected_type'] . '` via integrity check data.' );
						//$backup_type_dir = $backup_options->options['integrity']['detected_type'] . '/';
						$backup_type = $backup_options->options['integrity']['detected_type'];
					}
				}
				
				// If still do not know backup type then attempt to deduce it from filename.
				if ( $backup_type == '' ) {
					if ( stristr( $file, '-db-' ) !== false ) {
						pb_backupbuddy::status( 'details', 'S3: Detected backup type as `db` via filename.' );
						//$backup_type_dir = 'db/';
						$backup_type = 'db';
					} elseif ( stristr( $file, '-full-' ) !== false ) {
						pb_backupbuddy::status( 'details', 'S3: Detected backup type as `full` via filename.' );
						//$backup_type_dir = 'full/';
						$backup_type = 'full';
					} else {
						pb_backupbuddy::status( 'details', 'S3: Could not detect backup type via integrity details nor filename.' );
					}
				}
			}
			
			
			$credentials = pb_backupbuddy_destination_s3::get_credentials( $settings );
			
			// Create S3 instance.
			pb_backupbuddy::status( 'details', 'Creating S3 instance.' );
			$s3 = new AmazonS3( $credentials );    // the key, secret, token
			if ( $disable_ssl === true ) {
				@$s3->disable_ssl(true);
			}
			pb_backupbuddy::status( 'details', 'S3 instance created.' );
			
			// Verify bucket exists; create if not. Also set region to the region bucket exists in.
			if ( false === self::_prepareBucketAndRegion( $s3, $settings ) ) {
				return false;
			}
			
			// Handle chunking of file into a multipart upload (if applicable).
			$file_size = filesize( $file );
			if ( ( $max_chunk_size >= self::MINIMUM_CHUNK_SIZE ) && ( ( $file_size / 1024 / 1024 ) > $max_chunk_size ) ) { // minimum chunk size is 5mb. Anything under 5mb we will not chunk.
				
				// About to chunk so cleanup any previous hanging multipart transfers.
				self::multipart_cleanup( $settings, $lessLogs = false );
				
				pb_backupbuddy::status( 'details', 'S3 file size of ' . pb_backupbuddy::$format->file_size( $file_size ) . ' exceeds max chunk size of ' . $max_chunk_size . 'MB set in settings for sending file as multipart upload.' );
				// Initiate multipart upload with S3.
				pb_backupbuddy::status( 'details', 'Initiating S3 multipart upload.' );
				$response = $s3->initiate_multipart_upload(
					$settings['bucket'],
					$remote_path . $backup_type_dir . basename( $file ),
					array(
						'encryption' => 'AES256',
						//'meta'       => $meta_array,
					)
				);
				
				if(!$response->isOK()) {
					$this_error = 'S3 was unable to initiate multipart upload.';
					$pb_backupbuddy_destination_errors[] = $this_error;
					pb_backupbuddy::status( 'error', $this_error );
					return false;
				} else {
					$upload_id = (string) $response->body->UploadId;
					pb_backupbuddy::status( 'details', 'S3 initiated multipart upload with ID `' . $upload_id . '`.' );
				}
				
				// Get chunk parts for multipart transfer.
				pb_backupbuddy::status( 'details', 'S3 getting multipart counts.' );
				$parts = $s3->get_multipart_counts( $file_size, $max_chunk_size * 1024 * 1024 ); // Size of chunks expected to be in bytes.
				
				$multipart_destination_settings = $settings;
				$multipart_destination_settings['_multipart_id'] = $upload_id;
				$multipart_destination_settings['_multipart_partnumber'] = 0;
				$multipart_destination_settings['_multipart_file'] = $file;
				$multipart_destination_settings['_multipart_remotefile'] = $remote_path . basename( $file );
				$multipart_destination_settings['_multipart_counts'] = $parts;
				
				pb_backupbuddy::status( 'details', 'S3 multipart settings to pass:' . print_r( $multipart_destination_settings, true ) );
				
				unset( $files[$file_id] ); // Remove this file from queue of files to send as it is now passed off to be handled in multipart upload.
				
				
				// Schedule to process the parts.
				pb_backupbuddy::status( 'details', 'S3 scheduling send of next part(s).' );
				backupbuddy_core::schedule_single_event( time(), pb_backupbuddy::cron_tag( 'destination_send' ), array( $multipart_destination_settings, $files, $send_id ) );
				spawn_cron( time() + 150 ); // Adds > 60 seconds to get around once per minute cron running limit.
				update_option( '_transient_doing_cron', 0 ); // Prevent cron-blocking for next item.
				pb_backupbuddy::status( 'details', 'S3 scheduled send of next part(s). Done for this cycle.' );
				
				return array( $upload_id, 'Starting send of ' . count( $multipart_destination_settings['_multipart_counts'] ) . ' parts.' );
			} else { // did not meet chunking criteria.
				if ( $max_chunk_size != '0' ) {
					if ( ( $file_size / 1024 / 1024 ) > self::MINIMUM_CHUNK_SIZE ) {
						pb_backupbuddy::status( 'details', 'File size of ' . pb_backupbuddy::$format->file_size( $file_size ) . ' is less than the max chunk size of ' . $max_chunk_size . 'MB; not chunking into multipart upload.' );
					} else {
						pb_backupbuddy::status( 'details', 'File size of ' . pb_backupbuddy::$format->file_size( $file_size ) . ' is less than the minimum allowed chunk size of ' . self::MINIMUM_CHUNK_SIZE . 'MB; not chunking into multipart upload.' );
					}
				} else {
					pb_backupbuddy::status( 'details', 'Max chunk size set to zero so not chunking into multipart upload.' );
				}
				
			}
			
			
			// SEND file.
			if ( 'standard' == $settings['storage'] ) {
				$storageVal = AmazonS3::STORAGE_STANDARD;
			} elseif( 'reduced' == $settings['storage'] ) {
				$storageVal = AmazonS3::STORAGE_REDUCED;
			} else {
				pb_backupbuddy::status( 'error', 'Error #854784: Unknown S3 storage type: `' . $settings['storage'] . '`.' );
			}
			pb_backupbuddy::status( 'details', 'About to put (upload) object to S3: `' . $remote_path . $backup_type_dir . basename( $file ) . '`. Storage type: `' . $settings['storage'] . ' (' . $storageVal . ')`.' );
			$response = $s3->create_object(
				$settings['bucket'],
				$remote_path . $backup_type_dir . basename( $file ),
				array(
					'fileUpload' => $file,
					'encryption' => 'AES256',
					'storage'    => $storageVal,
					//'meta'       => $meta_array,
				)
			);
			unset( $storageVal );
			
			
			// Validate response. On failure notify S3 API that things went wrong.
			if(!$response->isOK()) { // Send FAILED.
				
				$this_error = 'Failure uploading file to S3 storage. Failure details: `' .print_r( $response, true ) . '`';
				$pb_backupbuddy_destination_errors[] = $this_error;
				pb_backupbuddy::status( 'error', $this_error );
				return false;
				
			} else { // Send SUCCESS.
				
				pb_backupbuddy::status( 'details', 'Success uploading file to S3 storage. Upload details: `' . print_r( $response, true ) . '`.' );
				
				$uploaded_size = $response->header['_info']['size_upload'];
				$uploaded_speed = $response->header['_info']['speed_upload'];
				pb_backupbuddy::status( 'details', 'Uploaded size: ' .  pb_backupbuddy::$format->file_size( $uploaded_size ) . ', Speed: ' . pb_backupbuddy::$format->file_size( $uploaded_speed ) . '/sec.' );
				
			}
		
			
			unset( $files[$file_id] ); // Remove from list of files we have not sent yet.
			
			pb_backupbuddy::status( 'details', 'S3 success sending file `' . basename( $file ) . '`. File uploaded and reported to S3 as completed.' );
			
			// Load destination fileoptions.
			pb_backupbuddy::status( 'details', 'About to load fileoptions data.' );
			require_once( pb_backupbuddy::plugin_path() . '/classes/fileoptions.php' );
			$fileoptions_obj = new pb_backupbuddy_fileoptions( backupbuddy_core::getLogDirectory() . 'fileoptions/send-' . $send_id . '.txt', $read_only = false, $ignore_lock = false, $create_file = false );
			if ( true !== ( $result = $fileoptions_obj->is_ok() ) ) {
				pb_backupbuddy::status( 'error', __('Fatal Error #9034.84838. Unable to access fileoptions data.', 'it-l10n-backupbuddy' ) . ' Error: ' . $result );
				return false;
			}
			pb_backupbuddy::status( 'details', 'Fileoptions data loaded.' );
			$fileoptions = &$fileoptions_obj->options;
			
			// Save stats.
			if ( isset( $uploaded_speed ) ) {
				$fileoptions['write_speed'] = $uploaded_speed;
				$fileoptions_obj->save();
			}
			unset( $fileoptions_obj );
			
		} // end foreach.
		
		
		// BEGIN backup limits.
		if ( $limit > 0 ) {
			
			pb_backupbuddy::status( 'details', 'S3 archive limit enforcement to `' . $limit . '` archives beginning.' );
			// S3 object for managing files.
			$s3_manage = new AmazonS3( $manage_data );
			if ( $disable_ssl === true ) {
				@$s3_manage->disable_ssl(true);
			}
			
			if ( false === self::_prepareBucketAndRegion( $s3_manage, $settings ) ) {
				return false;
			}
			
			// Get file listing.
			$response_manage = $s3_manage->list_objects( $manage_data['bucket'], array('prefix'=> $remote_path . $backup_type_dir ));     // list all the files in the subscriber account
			
			// Create array of backups and organize by date
			$prefix = backupbuddy_core::backup_prefix();
			
			// List backups associated with this site by date.
			$backups = array();
			foreach( $response_manage->body->Contents as $object ) {
				
				$file = str_replace( $remote_path . $backup_type_dir, '', $object->Key );
				if ( FALSE !== stristr( $file, '/' ) ) { // CRITICAL CODE! Subdir found due to slash. Do NOT display any files within a deeper subdirectory. Without this files could be deleted not belonging to this destination!
					continue;
				}
				if ( ! preg_match( self::BACKUP_FILENAME_PATTERN, $file ) ) { // CRITICAL CODE! Safety against accidental deletion of non-BB files. Do NOT delete files that do not look like a BackupBuddy backup filename.
					continue;
				}
				if ( FALSE === ( strpos( $file, 'backup-' . $prefix . '-' ) ) ) { // Not a backup for THIS site. Skip interacting with for limits.
					continue;
				}
				
				// S3 stores files in a directory per site so no need to check prefix here! if ( false !== strpos( $file, 'backup-' . $prefix . '-' ) ) { // if backup has this site prefix...
				$backups[$file] = strtotime( $object->LastModified );
			
			}
			arsort( $backups );
			
			
			pb_backupbuddy::status( 'details', 'S3 found `' . count( $backups ) . '` backups when checking archive limits.' );
			if ( ( count( $backups ) ) > $limit ) {
				pb_backupbuddy::status( 'details', 'More archives (' . count( $backups ) . ') than limit (' . $limit . ') allows. Trimming...' );
				$i = 0;
				$delete_fail_count = 0;
				foreach( $backups as $buname => $butime ) {
					$i++;
					if ( $i > $limit ) {
						pb_backupbuddy::status ( 'details', 'Trimming excess file `' . $buname . '`...' );
						$response = $s3_manage->delete_object( $manage_data['bucket'], $remote_path . $backup_type_dir . $buname );
						if ( !$response->isOK() ) {
							pb_backupbuddy::status( 'details',  'Unable to delete excess S3 file `' . $buname . '`. Details: `' . print_r( $response, true ) . '`.' );
							$delete_fail_count++;
						}
					}
				}
				pb_backupbuddy::status( 'details', 'Finished trimming excess backups.' );
				if ( $delete_fail_count !== 0 ) {
					$error_message = 'S3 remote limit could not delete ' . $delete_fail_count . ' backups.';
					pb_backupbuddy::status( 'error', $error_message );
					backupbuddy_core::mail_error( $error_message );
				}
			}
			
			pb_backupbuddy::status( 'details', 'S3 completed archive limiting.' );
			
		} else {
			pb_backupbuddy::status( 'details',  'No S3 archive file limit to enforce.' );
		} // End remote backup limit
		// END backup limits.
		
		
		if ( isset( $fileoptions_obj ) ) {
			unset( $fileoptions_obj );
		}
		
		// Success if we made it this far.
		return true;
		
	} // End send().
	
	
	
	/*	test()
	 *	
	 *	Tests ability to write to this remote destination.
	 *	
	 *	@param		array			$settings	Destination settings.
	 *	@return		bool|string					True on success, string error message on failure.
	 */
	public static function test( $settings ) {
		
		require_once( dirname( dirname( __FILE__ ) ) . '/_s3lib/aws-sdk/sdk.class.php' );
		
		$remote_path = self::get_remote_path( $settings['directory'] ); // Has leading and trailng slashes.
		$settings['bucket'] = strtolower( $settings['bucket'] ); // Buckets must be lowercase.
		
		// Try sending a file.
		$send_response = pb_backupbuddy_destinations::send( $settings, dirname( dirname( __FILE__ ) ) . '/remote-send-test.php', $send_id = 'TEST-' . pb_backupbuddy::random_string( 12 ) ); // 3rd param true forces clearing of any current uploads.
		if ( false === $send_response ) {
			$send_response = 'Error sending test file to S3.';
		} else {
			$send_response = 'Success.';
		}
		
		// S3 object for managing files.
		$credentials = pb_backupbuddy_destination_s3::get_credentials( $settings );
		$s3_manage = new AmazonS3( $credentials );
		if ( $settings['ssl'] == 0 ) {
			@$s3_manage->disable_ssl(true);
		}
		
		// Verify bucket exists; create if not. Also set region to the region bucket exists in.
		if ( false === self::_prepareBucketAndRegion( $s3_manage, $settings ) ) {
			return false;
		}
			
		// Delete sent file.
		$delete_response = 'Success.';
		$delete_response = $s3_manage->delete_object( $credentials['bucket'], $remote_path . 'remote-send-test.php' );
		if ( !$delete_response->isOK() ) {
			$delete_response = 'Unable to delete test S3 file `remote-send-test.php`.';
			pb_backupbuddy::status( 'details', $delete_response . ' Details: `' . print_r( $delete_response, true ) . '`.' );
		} else {
			$delete_response = 'Success.';
		}
		
		// Load destination fileoptions.
		pb_backupbuddy::status( 'details', 'About to load fileoptions data.' );
		require_once( pb_backupbuddy::plugin_path() . '/classes/fileoptions.php' );
		$fileoptions_obj = new pb_backupbuddy_fileoptions( backupbuddy_core::getLogDirectory() . 'fileoptions/send-' . $send_id . '.txt', $read_only = false, $ignore_lock = false, $create_file = false );
		if ( true !== ( $result = $fileoptions_obj->is_ok() ) ) {
			pb_backupbuddy::status( 'error', __('Fatal Error #9034.84838. Unable to access fileoptions data.', 'it-l10n-backupbuddy' ) . ' Error: ' . $result );
			return false;
		}
		pb_backupbuddy::status( 'details', 'Fileoptions data loaded.' );
		$fileoptions = &$fileoptions_obj->options;
		
		if ( ( 'Success.' != $send_response ) || ( 'Success.' != $delete_response ) ) {
			$fileoptions['status'] = 'failure';
			
			$fileoptions_obj->save();
			unset( $fileoptions_obj );
			
			return 'Send details: `' . $send_response . '`. Delete details: `' . $delete_response . '`.';
		} else {
			$fileoptions['status'] = 'success';
			$fileoptions['finish_time'] = time();
		}
		
		$fileoptions_obj->save();
		unset( $fileoptions_obj );
		
		return true;
		
	} // End test().
	
	
	
	/* download_file()
	 *
	 * Download remote file to local system.
	 *
	 * @param	array 		$settings				Destination settings.
	 * @param	string		$remoteFile				Remote filename.
	 * @param	string		$localDestinationFile	Full path & filename of destination file.
	 *
	 */
	public static function download_file( $settings, $remoteFile, $localDestinationFile ) {
		
		require_once( dirname( dirname( __FILE__ ) ) . '/_s3lib/aws-sdk/sdk.class.php' );
		
		pb_backupbuddy::status( 'details', 'Downloading remote file `' . $remoteFile . '` from S3 to local file `' . $localDestinationFile . '`.' );
		$manage_data = pb_backupbuddy_destination_s3::get_credentials( $settings );
		
		// Connect to S3.
		$s3 = new AmazonS3( $manage_data );    // the key, secret, token
		if ( $settings['ssl'] == '0' ) {
			@$s3->disable_ssl(true);
		}
		
		// Verify bucket exists; create if not. Also set region to the region bucket exists in.
		if ( false === self::_prepareBucketAndRegion( $s3, $settings ) ) {
			return false;
		}
		
		$manage_data = pb_backupbuddy_destination_s3::get_credentials( $settings );
		$remotePath = self::get_remote_path( $settings['directory'] ); // includes trailing slash.
		
		$get_response = $s3->get_object( $manage_data['bucket'], $remotePath . $remoteFile, array( 'fileDownload' => $localDestinationFile ) );
		
		if ( ! $get_response->isOK() ) {
			pb_backupbuddy::status( 'error', 'Error #958483. Unable to retrieve S3 object `' . $remoteFile . '`.' );
			return false;
		} else {
			pb_backupbuddy::status( 'details', 'Success copying remote S3 object `' . $remoteFile . '` to local.' );
			return true;
		}
		
	} // end download_file().
	
	
	
	/*	get_credentials()
	 *	
	 *	Get the required credentials and management data for managing user files.
	 *	
	 *	@return		false|array			Boolean false on failure. Array of data on success.
	 */
	public static function get_credentials( $settings ) {
		
		$settings['bucket'] = strtolower( $settings['bucket'] ); // Buckets must be lowercase.
		
		return array(
			'bucket'	=> $settings['bucket'],
			'key' 		=> $settings['accesskey'],
			'secret'	=> $settings['secretkey'],
		);
		
	} // End get_manage_data().
	
	
	
	/*	get_remote_path()
	 *	
	 *	Returns the site-specific remote path to store into.
	 *	Slashes (caused by subdirectories in url) are replaced with underscores.
	 *	Always has a leading and trailing slash.
	 *	
	 *	@return		string			Ex: /dustinbolton.com_blog/
	 */
	public static function get_remote_path( $directory = '' ) {
		
		$directory = trim( $directory, '/\\' );
		if ( $directory != '' ) {
			$directory .= '/';
		}
		
		return $directory;
		
	} // End get_remote_path().
	
	
	
	/*	get_bucket_region()
	 *	
	 *	Gets the region in which the specified Amazon S3 bucket is located.
	 *	This is a fixed up version of the Amazon SDK 1.6.2 method in s3.class.php
	 *	which is broken under PHP 5.4 because of a broken to_string() function
	 *	that returns a null value. This replacement avoids a direct string cast of the
	 *	response body and does an array cast instead and that gives us the correct
	 *	string value to put back into the response body.
	 *
	 *	The AmazonS3 object passed in must have already had credentials supplied
	 *	
	 *	@param	object	$s3		(Required) The instantiated AmazonS3 object to use
	 *	@param	string	$bucket	(Required) The name of the bucket to use.
	 *	@param	array	$opt	(Optional) An associative array of parameters
	 *
	 *	@return	CFResponse		A <CFResponse> object containing a parsed HTTP response.
	 */
	public static function get_bucket_region($s3, $bucket, $opt = null)
	{
		// Add this to our request
		if (!$opt) $opt = array();
		$opt['verb'] = 'GET';
		$opt['sub_resource'] = 'location';
		
		// Authenticate to S3
		$response = $s3->authenticate($bucket, $opt);
		
		if ($response->isOK())
		{
			// Handle body - this _should_ create an array with elements [@attributes] which is iself
			// an array of attributes and [0] which should in this case be the "value" of the element or
			// may not be present if the element is empty (has no value)
			$response_body = (array) $response->body;
			
			// For US Standard region body would have empty value so no element [0] - but [@attributes]
			// element always present so array is not empty so that is not a valid test for no value
			( isset( $response_body[ 0 ] ) ) ? $response->body = $response_body[ 0 ] : $response->body = '' ;
			
			// Need to translate a returned region of EU into eu-west-1 because EU is not a region but
			// a location constraint but it seems that in some cases this is returned as a region value.
			( 'EU' === $response->body )? $response->body = 'eu-west-1' : false ;
		}
		
		return $response;
	}
	
	
	
	/* multipart_cleanup()
	 *
	 * S3 does NOT automatically clean up failred or expired multipart chunk files so clean up for them.
	 *
	 */
	public static function multipart_cleanup( $settings, $lessLogs = true ) {
		
		$settings['bucket'] = strtolower( $settings['bucket'] ); // Buckets must be lowercase.
		
		$max_age = 60*60*72; // Seconds of max age to allow a stalled multipart upload.
		
		require_once( dirname( dirname( __FILE__ ) ) . '/_s3lib/aws-sdk/sdk.class.php' );
		
		pb_backupbuddy::status( 'details', 'Amazon S3 Multipart Remote Housekeeping Starting ...' );
		$manage_data = pb_backupbuddy_destination_s3::get_credentials( $settings );
		
		// Create S3 instance.
		pb_backupbuddy::status( 'details', 'Creating S3 instance.' );
		$s3 = new AmazonS3( $manage_data );    // the key, secret, token
		if ( $settings['ssl'] == 0 ) {
			@$s3->disable_ssl(true);
		}
		pb_backupbuddy::status( 'details', 'S3 instance created. Listing in progress multipart uploads ...' );
		
		// Verify bucket exists; create if not. Also set region to the region bucket exists in.
		if ( false === self::_prepareBucketAndRegion( $s3, $settings, $createBucket = false ) ) {
			return false;
		}
		
		// Get the in progress multipart uploads
		$response = $s3->list_multipart_uploads(
			$settings['bucket'],
			array(
				//'prefix' => $settings['_multipart_remotefile'],
				'prefix' => 'backup',
			)
		);
		if(!$response->isOK()) {
			pb_backupbuddy::status( 'error', 'Error listing multipart uploads. Details: `' . print_r( $response, true ) . '`' );
			return;
		} else {
			if ( true !== $lessLogs ) {
				pb_backupbuddy::status( 'details', 'Multipart upload check retrieved. Found `' . count( $response->body->Upload ) . '` multipart uploads in progress / stalled. Details: `' . print_r( $response, true ) . '`' );
			} else {
				pb_backupbuddy::status( 'details', 'Multipart upload check retrieved. Found `' . count( $response->body->Upload ) . '` multipart uploads in progress / stalled. Old BackupBuddy parts will be cleaned up (if any found) ...' );
			}
			foreach( $response->body->Upload as $upload ) {
				if ( true !== $lessLogs ) {
					pb_backupbuddy::status( 'details', 'Checking upload: ' . print_r( $upload, true ) );
				}
				if ( FALSE !== stristr( $upload->Key, 'backup-' ) ) { // BackupBuddy backup file.
					$initiated = strtotime( $upload->Initiated );
					if ( true !== $lessLogs ) {
						pb_backupbuddy::status( 'details', 'BackupBuddy Multipart Chunked Upload(s) detected in progress. Age: `' . pb_backupbuddy::$format->time_ago( $initiated ) . '`.' );
					}
					if ( ( $initiated + $max_age ) < time() ) {
						$abort_response = $s3->abort_multipart_upload( $settings['bucket'], $upload->Key, $upload->UploadId );
						if(!$abort_response->isOK()) { // abort fail.
							pb_backupbuddy::status( 'error', 'Stalled Amazon S3 Multipart Chunked abort of file `' . $upload->Key . '` with ID `' . $upload->UploadId . '` FAILED. Manually abort it.' );
						} else { // aborted.
							pb_backupbuddy::status( 'details', 'Stalled Amazon S3 Multipart Chunked Uploads ABORTED ID `' . $upload->UploadId . '` of age `' . pb_backupbuddy::$format->time_ago( $initiated ) . '`.' );
						}
					} else {
						if ( true !== $lessLogs ) {
							pb_backupbuddy::status( 'details', 'Amazon S3 Multipart Chunked Uploads not aborted as not too old.' );
						}
					}
				}
			} // end foreach uploads.
		}
		
		pb_backupbuddy::status( 'details', 'Amazon S3 Multipart Remote Housekeeping Finished.' );
		return true;
		
	} // end multipart_cleanup().
	
	
	
	/* _prepareBucketAndRegion()
	 *
	 * Validates bucket existance, creating if needed.  Sets region for non-US usage.
	 *
	 * @param	object		&$s3			S3 object currently in use. Pased by reference so region can be set.
	 * @param	array 		$settings		Destination settings array.
	 * @param	bool		$createBucket	Whether or not to create bucket if it does not currently exist.
	 * @return	bool						true on all okay, false otherwise.
	 *
	 */
	private static function _prepareBucketAndRegion( &$s3, $settings, $createBucket = true ) {
		
		// Get bucket region to determine if a bucket already exists.
		// Assume we will not have to try and create a bucket
		$maybe_create_bucket = false;
		pb_backupbuddy::status( 'details', 'Getting region for bucket: `' . $settings['bucket'] . "`." );
		$response = self::get_bucket_region( $s3, $settings['bucket'] );
		if( !$response->isOK() ) {
			
			$this_error = 'Bucket region could not be determined; bucket may not exist yet. Message details: `' . (string)$response->body->Message . '`.';
			pb_backupbuddy::status( 'details' , $this_error );
			
			// Assume we have to create the bucket
			$region = '';
			$maybe_create_bucket = true;
			
		} else {
			
			pb_backupbuddy::status( 'details', 'Bucket exists in region: ' .  (($response->body ==="") ? 'us-east-1' : $response->body ) );
			$region = $response->body; // Must leave as is for actual operational usage
			
		}

		// Set region context for later operations - note that if we are going to try and create
		// a bucket the region will have been set to empty so we'll get the bucket created in the
		// user-specified region.
		if ( '' == $region ) { // Bucket has no current region (ie it does not exist). Set user-specified region for new buckets.
			$s3->set_region( $settings['region'] );
		} else {
			$s3->set_region( 's3-' . $region . '.amazonaws.com' );
		}
		
		// Create bucket if it does not exist AND parameter pased to this function to create the bucket set to true.
		// Region/endpoint used based on user-defined setting.
		if ( ( true === $maybe_create_bucket ) && ( true === $createBucket ) ) {
		
			pb_backupbuddy::status( 'details', 'Attempting to create bucket `' . $settings['bucket'] . '` at region endpoint `' . $settings['region'] . '`.' );
			try {
				$response = $s3->create_bucket(
					$settings['bucket'],
					$settings['region'],
					AmazonS3::ACL_PRIVATE
				);
			} catch( Exception $e ) {
				$message = 'Exception while trying to create bucket `' . $settings['bucket'] . '` at region endpoint `' . $settings['region'] . '`. Details: `' . $e->getMessage() . '`.';
				pb_backupbuddy::status( 'error', $message );
				echo $message;
				return false;
			}
			
			if ( ! $response->isOK() ) { // Bucket creation FAILED.
			
				$message = 'Failure creating bucket `' . $settings['bucket'] . '` at region endpoint `' . $settings['region'] . '`. Message details: `' . (string)$response->body->Message . '`.';
				pb_backupbuddy::status( 'details', $message );
				echo $message;
				return false;
				
			} else { // Send SUCCESS.
				
				if ( is_object( $response->body ) ) {
					$messageDetails = (string)$response->body->Message;
				} else {
					$messageDetails = '';
				}
				pb_backupbuddy::status( 'details', 'Success creating bucket `' . $settings['bucket'] . '` at region endpoint `' . $settings['region'] . '`. Message details: `' . $messageDetails . '`.' );
				unset( $messageDetails );
				
			}
		} // end if create bucket.
		
		return true;
		
	} // end _prepareBucketAndRegion().
	
	
} // End class.