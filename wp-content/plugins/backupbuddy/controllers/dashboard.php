<?php

class pb_backupbuddy_dashboard extends pb_backupbuddy_dashboardcore {


	/*	stats()
	 *	
	 *	Displays (echos out) an overview of stats into the WordPress Dashboard.
	 *	
	 *	@return		null
	 */
	function stats() {

		$getOverview = backupbuddy_api0::getOverview();
		$backup_url = 'admin.php?page=pb_backupbuddy_backup';
		
		
		// Red-Green status for editsSinceLastBackup
		if ( $getOverview['editsSinceLastBackup'] == 0 )
			$status = 'green';
		else
			$status = 'red';
		

		// Format file archiveSize to readable format
		$file_size = $getOverview['lastBackupStats']['archiveSize'];

		if ( $file_size >= 1073741824 )
			$archiveSize = round( $file_size / 1024 / 1024 / 1024 , 2 ) . ' GB';

		elseif ( $file_size >= 1048576 )
			$archiveSize = round( $file_size / 1024 / 1024 , 1 ) . ' MB';

		elseif( $file_size >= 1024 )
			$archiveSize = round( $file_size / 1024 , 0 ) . ' KB';

		else
			$archiveSize = $file_size . ' bytes';


		// Format timestamp
		$time = $getOverview['lastBackupStats']['finish'];
		$time_nice = date("M j - g:i A", $time);

		// Format Type
		if ( $getOverview['lastBackupStats']['type'] == 'full' )
			$backup_type = 'Full';
		elseif ( $getOverview['lastBackupStats']['type'] == 'db' )
			$backup_type = 'Database';

		// Build widget markup
		ob_start();
		?>

		<div class="edits-since-wrapper">
			<p class="edits-since <?php echo $status; ?>">
				<?php echo $getOverview['editsSinceLastBackup']; ?>
			</p>
			<h4 class="number-heading">Edits since<br>last Backup</h4>
		</div>

		<div class="info-group">
			<h3>Latest Backup</h3>
			<ul class="backup-list">
				<li>
					<div class="list-wrapper">
						<div class="list-title">
							<a href="<?php echo $getOverview['lastBackupStats']['archiveURL']; ?>">Download</a>
						</div>
						<div class="list-description">
							<div class="backup-type description-item">
								<span>Type</span><br>
								<?php echo $backup_type; ?>
							</div>
							<div class="backup-size description-item">
								<span>Size</span><br>
								<?php echo $archiveSize; ?>
							</div>
							<div class="backup-time description-item">
								<span>Time</span><br>
								<?php echo $time_nice; ?>
							</div>
						</div>
					</div>
				</li>
			</ul>
		</div>

		<div class="backup-now">
			<a href="<?php echo $backup_url; ?>">Backup Now</a>
		</div>

		<?php
		ob_end_flush();
	}


}
?>