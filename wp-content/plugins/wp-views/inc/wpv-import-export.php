<?php

function wpv_admin_menu_import_export() {

    
    ?>    
    <div class="wrap">

        <div id="icon-views" class="icon32"><br /></div>
        <h2><?php _e('Views Import / Export', 'wpv-views') ?></h2>

        <br />
        <form name="View_export" action="<?php echo admin_url('edit.php'); ?>" method="post">
            <h2><?php _e('Export Views and View Templates', 'wpv-views'); ?></h2>
            <p><?php _e('Download all Views and View Templates', 'wpv-views'); ?></p>
            
            <p><strong><?php _e('When importing to theme:', 'wpv-views'); ?></strong></p>
            <ul style="margin-left:10px">
                <li>
                    <input id="radio-1" type="radio" value="ask" name="import-mode" checked="checked" />
                    <label for="radio-1"><?php _e('ask user for approval', 'wpv-views'); ?></label>
                </li>
                <li>
                    <input id="radio-2" type="radio" value="auto" name="import-mode" />
                    <label for="radio-2"><?php _e('import automatically', 'wpv-views'); ?></label>
                </li>
            </ul>
            <p><strong><?php _e('Affiliate details for theme designers:', 'wpv-views'); ?></strong></p>
            <table style="margin-left:10px">
                <tr>
                    <td><?php _e('Affiliate ID:', 'wpv-views'); ?></td><td><input type="text" name="aid" id="aid" style="width:200px;" /></td>
                </tr>
                <tr>
                    <td><?php _e('Affiliate Key:', 'wpv-views'); ?></td><td><input type="text" name="akey" id="akey" style="width:200px;" /></td>
                </tr>
            </table>
            <p style="margin-left:10px">
            <?php _e('You only need to enter affiliate settings if you are a theme designer and want to receive affiliate commission.', 'wpv-views'); ?>
            <br />
            <?php echo sprintf(__('Log into your account at <a href="%s">%s</a> and go to <a href="%s">%s</a> for details.', 'wpv-views'), 
                                    'http://wp-types.com',
                                    'http://wp-types.com',
                                    'http://wp-types.com/shop/account/?acct=affiliate',
                                    'http://wp-types.com/shop/account/?acct=affiliate'); ?>
            </p>
            
            <br /> 
            <input id="wpv-export" class="button-primary" type="submit" value="<?php _e('Export', 'wpv-views'); ?>" name="export" />
            
            <?php wp_nonce_field('wpv-export-nonce', 'wpv-export-nonce'); ?>

        </form>
        
        <hr />
        
        <?php wpv_admin_import_form(''); ?>
        
    </div>
    
    <?php
    
}

/**
 * Exports data to XML.
 */
function wpv_admin_export_data($download = true) {
    global $WP_Views;
    
    require_once WPV_PATH_EMBEDDED . '/common/array2xml.php';
    $xml = new ICL_Array2XML();
    $data = array();
    
    // SRDJAN - add siteurl, upload url, record taxonomies old IDs
    // https://icanlocalize.basecamphq.com/projects/7393061-wp-views/todo_items/142382866/comments
    // https://icanlocalize.basecamphq.com/projects/7393061-wp-views/todo_items/142389966/comments
    $data['site_url'] = get_site_url();
    if (is_multisite()) {
        $data['fileupload_url'] = get_option('fileupload_url');
    } else {
        $wp_upload_dir = wp_upload_dir();
        $data['fileupload_url'] = $wp_upload_dir['baseurl'];
    }

    // Get the views
    $views = get_posts('post_type=view&post_status=any&posts_per_page=-1');
    if (!empty($views)) {
	global $_wp_additional_image_sizes;
	if (!isset($_wp_additional_image_sizes) || !is_array($_wp_additional_image_sizes)) {
		$_wp_additional_image_sizes = array();
	}
	$attached_images_sizes=array_merge(
		// additional thumbnail sizes
		array_keys($_wp_additional_image_sizes), 
		// wp default thumbnail sizes
		array('thumbnail', 'medium', 'large')
	);
        $data['views'] = array('__key' => 'view');
        foreach ($views as $key => $post) {
            $post = (array) $post;
            if ($post['post_name']) {
                $post_data = array();
                $copy_data = array('ID', 'post_content', 'post_title', 'post_name',
                    'post_excerpt', 'post_type', 'post_status');
                foreach ($copy_data as $copy) {
                    if (isset($post[$copy])) {
                        $post_data[$copy] = $post[$copy];
                    }
                }
                $data['views']['view-' . $post['ID']] = $post_data;
                $meta = get_post_custom($post['ID']);
                if (!empty($meta)) {
                    $data['view']['view-' . $post['ID']]['meta'] = array();
                    foreach ($meta as $meta_key => $meta_value) {
                        if ($meta_key == '_wpv_settings') {
                            $value = maybe_unserialize($meta_value[0]);

                            // Add any taxonomy terms so we can re-map when we import.                            
                            if (!empty($value['taxonomy_terms'])) {
                    			$taxonomy = $value['taxonomy_type'][0];
                                
                                foreach ($value['taxonomy_terms'] as $term_id) {
                                    $term = get_term($term_id, $taxonomy);
                                    $data['terms_map']['term_' . $term->term_id]['old_id'] = $term->term_id;
                                    $data['terms_map']['term_' . $term->term_id]['slug'] = $term->slug;
                                    $data['terms_map']['term_' . $term->term_id]['taxonomy'] = $taxonomy;
                                }
                            }
                            
                            if (isset($value['author_mode'])) {
				$value['author_mode']['type'] = $value['author_mode'][0];
				unset($value['author_mode'][0]);
			    }
                            
                            $value = $WP_Views->convert_ids_to_names_in_settings($value);
                            
                            $data['views']['view-' . $post['ID']]['meta'][$meta_key] = $value;

                            
                        }
                        if ($meta_key == '_wpv_layout_settings') {
                            $value = maybe_unserialize($meta_value[0]);
                            $value = $WP_Views->convert_ids_to_names_in_layout_settings($value);
                            $data['views']['view-' . $post['ID']]['meta'][$meta_key] = $value;
                        }
                    }
                    if (empty($data['views']['view-' . $post['ID']]['meta'])) {
                        unset($data['views']['view-' . $post['ID']]['meta']);
                    }
                }
                
                // Juan - add images for exporting
		// https://icanlocalize.basecamphq.com/projects/7393061-wp-views/todo_items/150919286/comments
                
                $att_args = array( 'post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'post_parent' => $post['ID'] ); 
		$attachments = get_posts( $att_args );
		if ( $attachments ) {
			$data['views']['view-' . $post['ID']]['attachments'] = array();
			foreach ( $attachments as $attachment ) {
				$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID] = array();
				$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['title'] = $attachment->post_title;
				$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['content'] = $attachment->post_content;
				$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['excerpt'] = $attachment->post_excerpt;
				$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['status'] = $attachment->post_status;
				$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['alt'] = get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true );
				$imdata = base64_encode(file_get_contents($attachment->guid));
				$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['data'] = $imdata;
				preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $attachment->guid, $matches );
				$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['filename'] = basename( $matches[0] );
				$this_settings = get_post_meta($post['ID'], '_wpv_settings', true);
				$this_layout_settings = get_post_meta($post['ID'], '_wpv_layout_settings', true);
				if ( isset( $this_settings['pagination']['spinner_image_uploaded'] ) && $attachment->guid == $this_settings['pagination']['spinner_image_uploaded'] ) {
					$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['custom_spinner'] = 'this';
				}
				$imthumbs = array();
				foreach ($attached_images_sizes as $ts) {
					$imthumbs[$ts] = wp_get_attachment_image_src( $attachment->ID, $ts );
				}
				if ( isset( $this_settings['filter_meta_html'] ) ) {
					$pos = strpos( $this_settings['filter_meta_html'], $attachment->guid );
					if ($pos !== false) {
						$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['on_filter_meta_html'] = $attachment->guid;
					}
					foreach ($imthumbs as $thumbsize => $thumbdata) {
						if (!empty($thumbdata) && isset($thumbdata[0])) {
							$pos = strpos( $this_settings['filter_meta_html'], $thumbdata[0] );
							if ($pos !== false) {
								$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['on_filter_meta_html_sizes'][$thumbsize] = $thumbdata[0];
							}
						}
					}
				}
				if ( isset( $this_settings['filter_meta_html_css'] ) ) {
					$pos = strpos( $this_settings['filter_meta_html_css'], $attachment->guid );
					if ($pos !== false) {
						$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['on_filter_meta_html_css'] = $attachment->guid;
					}
					foreach ($imthumbs as $thumbsize => $thumbdata) {
						if (!empty($thumbdata) && isset($thumbdata[0])) {
							$pos = strpos( $this_settings['filter_meta_html_css'], $thumbdata[0] );
							if ($pos !== false) {
								$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['on_filter_meta_html_css_sizes'][$thumbsize] = $thumbdata[0];
							}
						}
					}
				}
				if ( isset( $this_settings['filter_meta_html_js'] ) ) {
					$pos = strpos( $this_settings['filter_meta_html_js'], $attachment->guid );
					if ($pos !== false) {
						$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['on_filter_meta_html_js'] = $attachment->guid;
					}
					foreach ($imthumbs as $thumbsize => $thumbdata) {
						if (!empty($thumbdata) && isset($thumbdata[0])) {
							$pos = strpos( $this_settings['filter_meta_html_js'], $thumbdata[0] );
							if ($pos !== false) {
								$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['on_filter_meta_html_js_sizes'][$thumbsize] = $thumbdata[0];
							}
						}
					}
				}
				if ( isset( $this_layout_settings['layout_meta_html'] ) ) {
					$pos = strpos( $this_layout_settings['layout_meta_html'], $attachment->guid );
					if ($pos !== false) {
						$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['on_layout_meta_html'] = $attachment->guid;
					}
					foreach ($imthumbs as $thumbsize => $thumbdata) {
						if (!empty($thumbdata) && isset($thumbdata[0])) {
							$pos = strpos( $this_layout_settings['layout_meta_html'], $thumbdata[0] );
							if ($pos !== false) {
								$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['on_layout_meta_html_sizes'][$thumbsize] = $thumbdata[0];
							}
						}
					}
				}
				if ( isset( $this_settings['layout_meta_html_css'] ) ) {
					$pos = strpos( $this_settings['layout_meta_html_css'], $attachment->guid );
					if ($pos !== false) {
						$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['on_layout_meta_html_css'] = $attachment->guid;
					}
					foreach ($imthumbs as $thumbsize => $thumbdata) {
						if (!empty($thumbdata) && isset($thumbdata[0])) {
							$pos = strpos( $this_settings['layout_meta_html_css'], $thumbdata[0] );
							if ($pos !== false) {
								$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['on_layout_meta_html_css_sizes'][$thumbsize] = $thumbdata[0];
							}
						}
					}
				}
				if ( isset( $this_settings['layout_meta_html_js'] ) ) {
					$pos = strpos( $this_settings['layout_meta_html_js'], $attachment->guid );
					if ($pos !== false) {
						$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['on_layout_meta_html_js'] = $attachment->guid;
					}
					foreach ($imthumbs as $thumbsize => $thumbdata) {
						if (!empty($thumbdata) && isset($thumbdata[0])) {
							$pos = strpos( $this_settings['layout_meta_html_js'], $thumbdata[0] );
							if ($pos !== false) {
								$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['on_layout_meta_html_js_sizes'][$thumbsize] = $thumbdata[0];
							}
						}
					}
				}
				$poscont = strpos( $data['views']['view-' . $post['ID']]['post_content'], $attachment->guid );
				if ($poscont !== false) {
					$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['on_post_content'] = $attachment->guid;
				}
				foreach ($imthumbs as $thumbsize => $thumbdata) {
					if (!empty($thumbdata) && isset($thumbdata[0])) {
						$pos = strpos( $data['views']['view-' . $post['ID']]['post_content'], $thumbdata[0] );
						if ($pos !== false) {
							$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['on_post_content_sizes'][$thumbsize] = $thumbdata[0];
						}
					}
				}
			}
		}
		
            }
        }
    }

    // Get the view templates
    $view_templates = get_posts('post_type=view-template&post_status=any&posts_per_page=-1');
    if (!empty($view_templates)) {
	global $_wp_additional_image_sizes;
	if (!isset($_wp_additional_image_sizes) || !is_array($_wp_additional_image_sizes)) {
		$_wp_additional_image_sizes = array();
	}
	$attached_images_sizes=array_merge(
		// additional thumbnail sizes
		array_keys($_wp_additional_image_sizes), 
		// wp default thumbnail sizes
		array('thumbnail', 'medium', 'large')
	);
        $data['view-templates'] = array('__key' => 'view-template');
        foreach ($view_templates as $key => $post) {
            $post = (array) $post;
            if ($post['post_name']) {
                $post_data = array();
                $copy_data = array('ID', 'post_content', 'post_title', 'post_name',
                    'post_excerpt', 'post_type', 'post_status');
                foreach ($copy_data as $copy) {
                    if (isset($post[$copy])) {
                        $post_data[$copy] = $post[$copy];
                    }
                }
                $output_mode = get_post_meta($post['ID'], '_wpv_view_template_mode', true);
                $template_extra_css = get_post_meta($post['ID'], '_wpv_view_template_extra_css', true);
                $template_extra_js = get_post_meta($post['ID'], '_wpv_view_template_extra_js', true);
                
                $post_data['template_mode'] = $output_mode;
                $post_data['template_extra_css'] = $template_extra_css;
                $post_data['template_extra_js'] = $template_extra_js;
                
                // Juan - add images for exporting
		// https://icanlocalize.basecamphq.com/projects/7393061-wp-views/todo_items/150919286/comments
		
                $att_args = array( 'post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'post_parent' => $post['ID'] ); 
		$attachments = get_posts( $att_args );
		if ( $attachments ) {
			$post_data['attachments'] = array();
			foreach ( $attachments as $attachment ) {
				$post_data['attachments']['attach_'.$attachment->ID] = array();
				$post_data['attachments']['attach_'.$attachment->ID]['title'] = $attachment->post_title;
				$post_data['attachments']['attach_'.$attachment->ID]['content'] = $attachment->post_content;
				$post_data['attachments']['attach_'.$attachment->ID]['excerpt'] = $attachment->post_excerpt;
				$post_data['attachments']['attach_'.$attachment->ID]['status'] = $attachment->post_status;
				$post_data['attachments']['attach_'.$attachment->ID]['alt'] = get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true );
				$imdata = base64_encode(file_get_contents($attachment->guid));
				$post_data['attachments']['attach_'.$attachment->ID]['data'] = $imdata;
				preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $attachment->guid, $matches );
				$post_data['attachments']['attach_'.$attachment->ID]['filename'] = basename( $matches[0] );
				$imthumbs = array();
				foreach ($attached_images_sizes as $ts) {
					$imthumbs[$ts] = wp_get_attachment_image_src( $attachment->ID, $ts );
				}
				if ( isset( $template_extra_css ) ) {
					$pos = strpos( $template_extra_css, $attachment->guid );
					if ($pos !== false) {
						$post_data['attachments']['attach_'.$attachment->ID]['on_meta_html_css'] = $attachment->guid;
					}
					foreach ($imthumbs as $thumbsize => $thumbdata) {
						if (!empty($thumbdata) && isset($thumbdata[0])) {
							$pos = strpos( $template_extra_css, $thumbdata[0] );
							if ($pos !== false) {
								$post_data['attachments']['attach_'.$attachment->ID]['on_meta_html_css_sizes'][$thumbsize] = $thumbdata[0];
							}
						}
					}
				}
				if ( isset( $template_extra_js ) ) {
					$posjs = strpos( $template_extra_js, $attachment->guid );
					if ($posjs !== false) {
						$post_data['attachments']['attach_'.$attachment->ID]['on_meta_html_js'] = $attachment->guid;
					}
					foreach ($imthumbs as $thumbsize => $thumbdata) {
						if (!empty($thumbdata) && isset($thumbdata[0])) {
							$pos = strpos( $template_extra_js, $thumbdata[0] );
							if ($pos !== false) {
								$post_data['attachments']['attach_'.$attachment->ID]['on_meta_html_js_sizes'][$thumbsize] = $thumbdata[0];
							}
						}
					}
				}
				$poscont = strpos( $post_data['post_content'], $attachment->guid );
				if ($poscont !== false) {
					$post_data['attachments']['attach_'.$attachment->ID]['on_post_content'] = $attachment->guid;
				}
				foreach ($imthumbs as $thumbsize => $thumbdata) {
					if (!empty($thumbdata) && isset($thumbdata[0])) {
						$pos = strpos( $post_data['post_content'], $thumbdata[0] );
						if ($pos !== false) {
							$post_data['attachments']['attach_'.$attachment->ID]['on_post_content_sizes'][$thumbsize] = $thumbdata[0];
						}
					}
				}
			}
		}

                $data['view-templates']['view-template-' . $post['ID']] = $post_data;
            }
        }
    }
    
    // Get settings
    $options = get_option('wpv_options');
    if (!empty($options)) {
        foreach ($options as $option_name => $option_value) {
            if (strpos($option_name, 'view_') === 0
                    || strpos($option_name, 'views_template_') === 0) {
                $post = get_post($option_value);
                if (!empty($post)) {
                    $options[$option_name] = $post->post_name;
                }
            }
        }
        $data['settings'] = $options;
    }


    // Offer for download
    $data = $xml->array2xml($data, 'views');

    $sitename = sanitize_key(get_bloginfo('name'));
    if (!empty($sitename)) {
        $sitename .= '.';
    }
    $filename = $sitename . 'views.' . date('Y-m-d') . '.xml';
    $code = "<?php\r\n";
    $code .= '$timestamp = ' . time() . ';' . "\r\n";
    $code .= '$auto_import = ';
    $code .=  (isset($_POST['import-mode']) && $_POST['import-mode'] == 'ask') ? 0 : 1;
    $code .= ';' . "\r\n";
    if (isset($_POST['aid']) && $_POST['aid'] != '' && isset($_POST['akey']) && $_POST['aid'] != '') {
        $code .= '$affiliate_id="' . $_POST['aid'] . '";' . "\r\n";
        $code .= '$affiliate_key="' . $_POST['akey'] . '";' . "\r\n";
    }
    $code .= "\r\n?>";
    
    if (!$download) {
        return $data;
    }

    if (class_exists('ZipArchive')) { 
        $zipname = $sitename . 'views.' . date('Y-m-d') . '.zip';
        $zip = new ZipArchive();
        $file = tempnam(sys_get_temp_dir(), "zip");
        $zip->open($file, ZipArchive::OVERWRITE);
    
        $res = $zip->addFromString('settings.xml', $data);
        $zip->addFromString('settings.php', $code);
        $zip->close();
        $data = file_get_contents($file);
        header("Content-Description: File Transfer");
        header("Content-Disposition: attachment; filename=" . $zipname);
        header("Content-Type: application/zip");
        header("Content-length: " . strlen($data) . "\n\n");
        header("Content-Transfer-Encoding: binary");
        echo $data;
        unlink($file);
        die();
    } else {
        // download the xml.
        
        header("Content-Description: File Transfer");
        header("Content-Disposition: attachment; filename=" . $filename);
        header("Content-Type: application/xml");
        header("Content-length: " . strlen($data) . "\n\n");
        echo $data;
        die();
    }
}

/*
*
*   Custom Export function for Module Manager
*   Exports selected items (by ID) and of specified type (eg views, view-templates)
*   Returns xml string
*/
function wpv_admin_export_selected_data($items, $type = 'view', $mode = 'xml' ) {
    global $WP_Views;
    
    require_once WPV_PATH_EMBEDDED . '/common/array2xml.php';
    $xml = new ICL_Array2XML();
    $data = array();
    $items_hash = array();
    
    // SRDJAN - add siteurl, upload url, record taxonomies old IDs
    // https://icanlocalize.basecamphq.com/projects/7393061-wp-views/todo_items/142382866/comments
    // https://icanlocalize.basecamphq.com/projects/7393061-wp-views/todo_items/142389966/comments
//    $data['site_url'] = get_site_url();
    if (is_multisite()) {
        $upload_directory = get_option('fileupload_url');
    } else {
        $wp_upload_dir = wp_upload_dir();
        $upload_directory = $wp_upload_dir['baseurl'];
    }

    $args=array(
        'posts_per_page' => -1,
        'post_status' => 'any'
    );
    
    $export=false;
    $view_types=array(
        'view'=>array('key'=>'views'),
        'view-template'=>array('key'=>'view-templates')
    );
    
    if (is_string($items) && 'all'===$items)
    {
        $export=true;
    }
    elseif (is_array($items) && !empty($items))
    {
        $args['post__in']=$items;
        $export=true;
    }
    
    if (!in_array($type, array_keys($view_types)))
        $export=false;
    else
    {
        $args['post_type']=$type;
        $vkey=$view_types[$type]['key'];
    }
    if (!$export) return '';
    
    switch($type)
    {
         case 'view':
            // Get the views
            $views = get_posts($args);
            if (!empty($views)) {
		global $_wp_additional_image_sizes;
		if (!isset($_wp_additional_image_sizes) || !is_array($_wp_additional_image_sizes)) {
			$_wp_additional_image_sizes = array();
		}
		$attached_images_sizes=array_merge(
			// additional thumbnail sizes
			array_keys($_wp_additional_image_sizes), 
			// wp default thumbnail sizes
			array('thumbnail', 'medium', 'large')
		);
                $data['views'] = array('__key' => 'view');
                foreach ($views as $key => $post) {
                    $post = (array) $post;
                    if ($post['post_name']) {
                        $hash_data = array();
                        $post_data = array();
                        $copy_data = array('ID', 'post_content', 'post_title', 'post_name',
                            'post_excerpt', 'post_type', 'post_status');
                        foreach ($copy_data as $copy) {
                            if (isset($post[$copy])) {
                                $post_data[$copy] = $post[$copy];
                            }
                        }
                        $data['views']['view-' . $post['ID']] = $post_data;
                        $hash_basics = array('post_title', 'post_name', 'post_type', 'post_status');
			foreach ($hash_basics as $basics) {
				if ( isset( $data['views']['view-' . $post['ID']][$basics] ) ) $hash_data[$basics] = $data['views']['view-' . $post['ID']][$basics];
			}
			if (isset($data['views']['view-' . $post['ID']]['post_content'])) $hash_data['post_content'] = preg_replace('/\s+/', '', str_replace("\n","",$data['views']['view-' . $post['ID']]['post_content']));
			if (isset($data['views']['view-' . $post['ID']]['post_excerpt'])) $hash_data['post_excerpt'] = preg_replace('/\s+/', '', str_replace("\n","",$data['views']['view-' . $post['ID']]['post_excerpt']));
                        $meta = get_post_custom($post['ID']);
                        if (!empty($meta)) {
                            $data['view']['view-' . $post['ID']]['meta'] = array();
                            foreach ($meta as $meta_key => $meta_value) {
                                if ($meta_key == '_wpv_settings') {
                                    $value = maybe_unserialize($meta_value[0]);

                                    // Add any taxonomy terms so we can re-map when we import.                            
                                    if (!empty($value['taxonomy_terms'])) {
                                        $taxonomy = $value['taxonomy_type'][0];
                                        
                                        foreach ($value['taxonomy_terms'] as $term_id) {
                                            $term = get_term($term_id, $taxonomy);
                                            $data['terms_map']['term_' . $term->term_id]['old_id'] = $term->term_id;
                                            $data['terms_map']['term_' . $term->term_id]['slug'] = $term->slug;
                                            $data['terms_map']['term_' . $term->term_id]['taxonomy'] = $taxonomy;
                                        }
                                    }
                                    
                                    if (isset($value['author_mode'])) {
					$value['author_mode']['type'] = $value['author_mode'][0];
					unset($value['author_mode'][0]);
				    }
                                    
                                    $value = $WP_Views->convert_ids_to_names_in_settings($value);
                                    
                                    $data['views']['view-' . $post['ID']]['meta'][$meta_key] = $value;
                                    if ('module_manager' == $mode ) {
					$hash_data['meta'][$meta_key] = $value;
					// Correct possible elements with changing format
					if (isset($value['taxonomy_hide_empty'])) $hash_data['meta'][$meta_key]['taxonomy_hide_empty'] = strval($value['taxonomy_hide_empty']);
					if (isset($value['taxonomy_include_non_empty_decendants'])) $hash_data['meta'][$meta_key]['taxonomy_include_non_empty_decendants'] = strval($value['taxonomy_include_non_empty_decendants']);
					if (isset($value['taxonomy_pad_counts'])) $hash_data['meta'][$meta_key]['taxonomy_pad_counts'] = strval($value['taxonomy_pad_counts']);
					if (isset($value['post_type_dont_include_current_page'])) $hash_data['meta'][$meta_key]['post_type_dont_include_current_page'] = strval($value['post_type_dont_include_current_page']);
					if (isset($value['pagination']['preload_images']) ) $hash_data['meta'][$meta_key]['pagination']['preload_images'] = strval($value['pagination']['preload_images']);
					if (isset($value['pagination']['cache_pages']) ) $hash_data['meta'][$meta_key]['pagination']['cache_pages'] = strval($value['pagination']['cache_pages']);
					if (isset($value['pagination']['preload_pages']) ) $hash_data['meta'][$meta_key]['pagination']['preload_pages'] = strval($value['pagination']['preload_pages']);
					if (isset($value['pagination']['spinner_image'])) $hash_data['meta'][$meta_key]['pagination']['spinner_image'] = basename($value['pagination']['spinner_image']);
					if (isset($value['rollover']['preload_images']) ) $hash_data['meta'][$meta_key]['rollover']['preload_images'] = strval($value['rollover']['preload_images']);
					if (isset($value['offset'])) $hash_data['meta'][$meta_key]['offset'] = strval($value['offset']);
					if (isset($value['taxonomy_offset'])) $hash_data['meta'][$meta_key]['taxonomy_offset'] = strval($value['taxonomy_offset']);
					if (isset($value['filter_meta_html'])) $hash_data['meta'][$meta_key]['filter_meta_html'] = preg_replace('/\s+/', '', str_replace("\n","",$value['filter_meta_html']));
					if (isset($value['generated_filter_meta_html'])) $hash_data['meta'][$meta_key]['generated_filter_meta_html'] = preg_replace('/\s+/', '', str_replace("\n","",$value['generated_filter_meta_html']));
					if (isset($value['filter_meta_html_css'])) $hash_data['meta'][$meta_key]['filter_meta_html_css'] = preg_replace('/\s+/', '', str_replace("\n","",$value['filter_meta_html_css']));
					if (isset($value['filter_meta_html_js'])) $hash_data['meta'][$meta_key]['filter_meta_html_js'] = preg_replace('/\s+/', '', str_replace("\n","",$value['filter_meta_html_js']));
					if (isset($value['layout_meta_html_css'])) $hash_data['meta'][$meta_key]['layout_meta_html_css'] = preg_replace('/\s+/', '', str_replace("\n","",$value['layout_meta_html_css']));
					if (isset($value['layout_meta_html_js'])) $hash_data['meta'][$meta_key]['layout_meta_html_js'] = preg_replace('/\s+/', '', str_replace("\n","",$value['layout_meta_html_js']));
					if (isset($value['author_mode'])) $hash_data['meta'][$meta_key]['author_mode'] = reset($value['author_mode']);
					
					$cursed_array = array(
						'filter_controls_enable',
						'filter_controls_param',
						'filter_controls_mode',
						'filter_controls_field_name',
						'filter_controls_label',
						'filter_controls_type', 
						'filter_controls_values'
					);
					foreach ($cursed_array as $cursed) {
						if (isset($hash_data['meta'][$meta_key][$cursed])) unset($hash_data['meta'][$meta_key][$cursed]);
					}
                                    
                                    
                                    }

                                    
                                }
                                if ($meta_key == '_wpv_layout_settings') {
                                    $value = maybe_unserialize($meta_value[0]);
                                    $value = $WP_Views->convert_ids_to_names_in_layout_settings($value);
                                    $data['views']['view-' . $post['ID']]['meta'][$meta_key] = $value;
                                    if ('module_manager' == $mode ) {
					$hash_data['meta'][$meta_key] = $value;
					if (isset($value['layout_meta_html'])) $hash_data['meta'][$meta_key]['layout_meta_html'] = preg_replace('/\s+/', '', str_replace("\n","",$value['layout_meta_html']));
					if (isset($value['generated_layout_meta_html'])) $hash_data['meta'][$meta_key]['generated_layout_meta_html'] = preg_replace('/\s+/', '', str_replace("\n","",$value['generated_layout_meta_html']));
                                    }
                                }
                            }
                            if (empty($data['views']['view-' . $post['ID']]['meta'])) {
                                unset($data['views']['view-' . $post['ID']]['meta']);
                            }
                        }
                        
                        // Juan - add images for exporting
			// https://icanlocalize.basecamphq.com/projects/7393061-wp-views/todo_items/150919286/comments
			
			$att_args = array( 'post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'post_parent' => $post['ID'] ); 
			$attachments = get_posts( $att_args );
			if ( $attachments ) {
				$data['views']['view-' . $post['ID']]['attachments'] = array();
				if ('module_manager' == $mode ) $hash_data['attachments'] = array();
				foreach ( $attachments as $attachment ) {
					$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID] = array();
					$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['title'] = $attachment->post_title;
					$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['content'] = $attachment->post_content;
					$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['excerpt'] = $attachment->post_excerpt;
					$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['status'] = $attachment->post_status;
					$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['alt'] = get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true );
					$imdata = base64_encode(file_get_contents($attachment->guid));
					if ('module_manager' == $mode ) $hash_data['attachments'][] = md5($imdata);
					$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['data'] = $imdata;
					preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $attachment->guid, $matches );
					$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['filename'] = basename( $matches[0] );
					$this_settings = get_post_meta($post['ID'], '_wpv_settings', true);
					$this_layout_settings = get_post_meta($post['ID'], '_wpv_layout_settings', true);
					if ( isset( $this_settings['pagination']['spinner_image_uploaded'] ) && $attachment->guid == $this_settings['pagination']['spinner_image_uploaded'] ) {
						$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['custom_spinner'] = 'this';
						if ( 'module_manager' == $mode ) {
							$hash_data['meta']['_wpv_settings']['pagination']['spinner_image_uploaded'] = md5($imdata);
						}
					}
					$imthumbs = array();
					foreach ($attached_images_sizes as $ts) {
						$imthumbs[$ts] = wp_get_attachment_image_src( $attachment->ID, $ts );
					}
					if ( isset( $this_settings['filter_meta_html'] ) ) {
						$pos = strpos( $this_settings['filter_meta_html'], $attachment->guid );
						if ($pos !== false) {
							$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['on_filter_meta_html'] = $attachment->guid;
							if ( 'module_manager' == $mode ) {
								$hash_data['meta']['_wpv_settings']['filter_meta_html'] = str_replace($attachment->guid, md5($imdata), $hash_data['meta']['_wpv_settings']['filter_meta_html']);
							}
						}
						foreach ($imthumbs as $thumbsize => $thumbdata) {
							if (!empty($thumbdata) && isset($thumbdata[0])) {
								$pos = strpos( $this_settings['filter_meta_html'], $thumbdata[0] );
								if ($pos !== false) {
									$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['on_filter_meta_html_sizes'][$thumbsize] = $thumbdata[0];
									if ( 'module_manager' == $mode ) {
										$hash_data['meta']['_wpv_settings']['filter_meta_html'] = str_replace($thumbdata[0], md5($imdata) . '_' . $thumbsize, $hash_data['meta']['_wpv_settings']['filter_meta_html']);
									}
								}
							}
						}
					}
					if ( isset( $this_settings['filter_meta_html_css'] ) ) {
						$pos = strpos( $this_settings['filter_meta_html_css'], $attachment->guid );
						if ($pos !== false) {
							$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['on_filter_meta_html_css'] = $attachment->guid;
							if ( 'module_manager' == $mode ) {
								$hash_data['meta']['_wpv_settings']['filter_meta_html_css'] = str_replace($attachment->guid, md5($imdata), $hash_data['meta']['_wpv_settings']['filter_meta_html_css']);
							}
						}
						foreach ($imthumbs as $thumbsize => $thumbdata) {
							if (!empty($thumbdata) && isset($thumbdata[0])) {
								$pos = strpos( $this_settings['filter_meta_html_css'], $thumbdata[0] );
								if ($pos !== false) {
									$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['on_filter_meta_html_css_sizes'][$thumbsize] = $thumbdata[0];
									if ( 'module_manager' == $mode ) {
										$hash_data['meta']['_wpv_settings']['filter_meta_html_css'] = str_replace($thumbdata[0], md5($imdata) . '_' . $thumbsize, $hash_data['meta']['_wpv_settings']['filter_meta_html_css']);
									}
								}
							}
						}
					}
					if ( isset( $this_settings['filter_meta_html_js'] ) ) {
						$pos = strpos( $this_settings['filter_meta_html_js'], $attachment->guid );
						if ($pos !== false) {
							$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['on_filter_meta_html_js'] = $attachment->guid;
							if ( 'module_manager' == $mode ) {
								$hash_data['meta']['_wpv_settings']['filter_meta_html_js'] = str_replace($attachment->guid, md5($imdata), $hash_data['meta']['_wpv_settings']['filter_meta_html_js']);
							}
						}
						foreach ($imthumbs as $thumbsize => $thumbdata) {
							if (!empty($thumbdata) && isset($thumbdata[0])) {
								$pos = strpos( $this_settings['filter_meta_html_js'], $thumbdata[0] );
								if ($pos !== false) {
									$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['on_filter_meta_html_js_sizes'][$thumbsize] = $thumbdata[0];
									if ( 'module_manager' == $mode ) {
										$hash_data['meta']['_wpv_settings']['filter_meta_html_js'] = str_replace($thumbdata[0], md5($imdata) . '_' . $thumbsize, $hash_data['meta']['_wpv_settings']['filter_meta_html_js']);
									}
								}
							}
						}
					}
					if ( isset( $this_layout_settings['layout_meta_html'] ) ) {
						$pos = strpos( $this_layout_settings['layout_meta_html'], $attachment->guid );
						if ($pos !== false) {
							$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['on_layout_meta_html'] = $attachment->guid;
							if ( 'module_manager' == $mode ) {
								$hash_data['meta']['_wpv_layout_settings']['layout_meta_html'] = str_replace($attachment->guid, md5($imdata), $hash_data['meta']['_wpv_layout_settings']['layout_meta_html']);
							}
						}
						foreach ($imthumbs as $thumbsize => $thumbdata) {
							if (!empty($thumbdata) && isset($thumbdata[0])) {
								$pos = strpos( $this_layout_settings['layout_meta_html'], $thumbdata[0] );
								if ($pos !== false) {
									$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['on_layout_meta_html_sizes'][$thumbsize] = $thumbdata[0];
									if ( 'module_manager' == $mode ) {
										$hash_data['meta']['_wpv_layout_settings']['layout_meta_html'] = str_replace($thumbdata[0], md5($imdata) . '_' . $thumbsize, $hash_data['meta']['_wpv_layout_settings']['layout_meta_html']);
									}
								}
							}
						}
					}
					if ( isset( $this_settings['layout_meta_html_css'] ) ) {
						$pos = strpos( $this_settings['layout_meta_html_css'], $attachment->guid );
						if ($pos !== false) {
							$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['on_layout_meta_html_css'] = $attachment->guid;
							if ( 'module_manager' == $mode ) {
								$hash_data['meta']['_wpv_settings']['layout_meta_html_css'] = str_replace($attachment->guid, md5($imdata), $hash_data['meta']['_wpv_settings']['layout_meta_html_css']);
							}
						}
						foreach ($imthumbs as $thumbsize => $thumbdata) {
							if (!empty($thumbdata) && isset($thumbdata[0])) {
								$pos = strpos( $this_settings['layout_meta_html_css'], $thumbdata[0] );
								if ($pos !== false) {
									$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['on_layout_meta_html_css_sizes'][$thumbsize] = $thumbdata[0];
									if ( 'module_manager' == $mode ) {
										$hash_data['meta']['_wpv_settings']['layout_meta_html_css'] = str_replace($thumbdata[0], md5($imdata) . '_' . $thumbsize, $hash_data['meta']['_wpv_settings']['layout_meta_html_css']);
									}
								}
							}
						}
					}
					if ( isset( $this_settings['layout_meta_html_js'] ) ) {
						$pos = strpos( $this_settings['layout_meta_html_js'], $attachment->guid );
						if ($pos !== false) {
							$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['on_layout_meta_html_js'] = $attachment->guid;
							if ( 'module_manager' == $mode ) {
								$hash_data['meta']['_wpv_settings']['layout_meta_html_js'] = str_replace($attachment->guid, md5($imdata), $hash_data['meta']['_wpv_settings']['layout_meta_html_js']);
							}
						}
						foreach ($imthumbs as $thumbsize => $thumbdata) {
							if (!empty($thumbdata) && isset($thumbdata[0])) {
								$pos = strpos( $this_settings['layout_meta_html_js'], $thumbdata[0] );
								if ($pos !== false) {
									$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['on_layout_meta_html_js_sizes'][$thumbsize] = $thumbdata[0];
									if ( 'module_manager' == $mode ) {
										$hash_data['meta']['_wpv_settings']['layout_meta_html_js'] = str_replace($thumbdata[0], md5($imdata) . '_' . $thumbsize, $hash_data['meta']['_wpv_settings']['layout_meta_html_js']);
									}
								}
							}
						}
					}
					$poscont = strpos( $data['views']['view-' . $post['ID']]['post_content'], $attachment->guid );
					if ($poscont !== false) {
						$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['on_post_content'] = $attachment->guid;
						if ( 'module_manager' == $mode ) {
							$hash_data['post_content'] = str_replace($attachment->guid, md5($imdata), $hash_data['post_content']);
						}
					}
					foreach ($imthumbs as $thumbsize => $thumbdata) {
						if (!empty($thumbdata) && isset($thumbdata[0])) {
							$pos = strpos( $data['views']['view-' . $post['ID']]['post_content'], $thumbdata[0] );
							if ($pos !== false) {
								$data['views']['view-' . $post['ID']]['attachments']['attach_'.$attachment->ID]['on_post_content_sizes'][$thumbsize] = $thumbdata[0];
								if ( 'module_manager' == $mode ) {
									$hash_data['post_content'] = str_replace($thumbdata[0], md5($imdata) . '_' . $thumbsize, $hash_data['post_content']);
								}
							}
						}
					}
				}
			}
			if ('module_manager' == $mode ) {
				$items_hash[$post['ID']] = md5(serialize($hash_data));
			}
                    }
                }
            }
        break;
        
        case 'view-template':
            // Get the view templates
            $view_templates = get_posts($args);
            if (!empty($view_templates)) {
		global $_wp_additional_image_sizes;
		if (!isset($_wp_additional_image_sizes) || !is_array($_wp_additional_image_sizes)) {
			$_wp_additional_image_sizes = array();
		}
		$attached_images_sizes=array_merge(
			// additional thumbnail sizes
			array_keys($_wp_additional_image_sizes), 
			// wp default thumbnail sizes
			array('thumbnail', 'medium', 'large')
		);
                $data['view-templates'] = array('__key' => 'view-template');
                foreach ($view_templates as $key => $post) {
                    $post = (array) $post;
                    if ($post['post_name']) {
                        $post_data = array();
                        $copy_data = array('ID', 'post_content', 'post_title', 'post_name',
                            'post_excerpt', 'post_type', 'post_status');
                        foreach ($copy_data as $copy) {
                            if (isset($post[$copy])) {
                                $post_data[$copy] = $post[$copy];
                            }
                        }
                        $output_mode = get_post_meta($post['ID'], '_wpv_view_template_mode', true);
                        $template_extra_css = get_post_meta($post['ID'], '_wpv_view_template_extra_css', true);
                        $template_extra_js = get_post_meta($post['ID'], '_wpv_view_template_extra_js', true);
                        
                        $post_data['template_mode'] = $output_mode;
                        $post_data['template_extra_css'] = $template_extra_css;
                        $post_data['template_extra_js'] = $template_extra_js;
                        
                        // Juan - add images for exporting
			// https://icanlocalize.basecamphq.com/projects/7393061-wp-views/todo_items/150919286/comments
			
			$att_args = array( 'post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'post_parent' => $post['ID'] ); 
			$attachments = get_posts( $att_args );
			if ( $attachments ) {
				$post_data['attachments'] = array();
				foreach ( $attachments as $attachment ) {
					$post_data['attachments']['attach_'.$attachment->ID] = array();
					$post_data['attachments']['attach_'.$attachment->ID]['title'] = $attachment->post_title;
					$post_data['attachments']['attach_'.$attachment->ID]['content'] = $attachment->post_content;
					$post_data['attachments']['attach_'.$attachment->ID]['excerpt'] = $attachment->post_excerpt;
					$post_data['attachments']['attach_'.$attachment->ID]['status'] = $attachment->post_status;
					$post_data['attachments']['attach_'.$attachment->ID]['alt'] = get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true );
					$imdata = base64_encode(file_get_contents($attachment->guid));
					$post_data['attachments']['attach_'.$attachment->ID]['data'] = $imdata;
					preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $attachment->guid, $matches );
					$post_data['attachments']['attach_'.$attachment->ID]['filename'] = basename( $matches[0] );
					$imthumbs = array();
					foreach ($attached_images_sizes as $ts) {
						$imthumbs[$ts] = wp_get_attachment_image_src( $attachment->ID, $ts );
					}
					if ( isset( $template_extra_css ) ) {
						$pos = strpos( $template_extra_css, $attachment->guid );
						if ($pos !== false) {
							$post_data['attachments']['attach_'.$attachment->ID]['on_meta_html_css'] = $attachment->guid;
						}
						foreach ($imthumbs as $thumbsize => $thumbdata) {
							if (!empty($thumbdata) && isset($thumbdata[0])) {
								$pos = strpos( $template_extra_css, $thumbdata[0] );
								if ($pos !== false) {
									$post_data['attachments']['attach_'.$attachment->ID]['on_meta_html_css_sizes'][$thumbsize] = $thumbdata[0];
								}
							}
						}
					}
					if ( isset( $template_extra_js ) ) {
						$posjs = strpos( $template_extra_js, $attachment->guid );
						if ($posjs !== false) {
							$post_data['attachments']['attach_'.$attachment->ID]['on_meta_html_js'] = $attachment->guid;
						}
						foreach ($imthumbs as $thumbsize => $thumbdata) {
							if (!empty($thumbdata) && isset($thumbdata[0])) {
								$pos = strpos( $template_extra_js, $thumbdata[0] );
								if ($pos !== false) {
									$post_data['attachments']['attach_'.$attachment->ID]['on_meta_html_js_sizes'][$thumbsize] = $thumbdata[0];
								}
							}
						}
					}
					$poscont = strpos( $post_data['post_content'], $attachment->guid );
					if ($poscont !== false) {
						$post_data['attachments']['attach_'.$attachment->ID]['on_post_content'] = $attachment->guid;
					}
					foreach ($imthumbs as $thumbsize => $thumbdata) {
						if (!empty($thumbdata) && isset($thumbdata[0])) {
							$pos = strpos( $post_data['post_content'], $thumbdata[0] );
							if ($pos !== false) {
								$post_data['attachments']['attach_'.$attachment->ID]['on_post_content_sizes'][$thumbsize] = $thumbdata[0];
							}
						}
					}
				}
			}

                        $data['view-templates']['view-template-' . $post['ID']] = $post_data;
                        
                        if ('module_manager' == $mode ) {
				$hash_data = $post_data;
				$hash_data['post_content'] = preg_replace('/\s+/', '', str_replace("\n","", $post_data['post_content'] ));
				$hash_data['template_extra_css'] = preg_replace('/\s+/', '', str_replace("\n","", $post_data['template_extra_css'] ));
				$hash_data['template_extra_js'] = preg_replace('/\s+/', '', str_replace("\n","", $post_data['template_extra_js'] ));
				if ( isset( $post_data['attachments'] ) ) {
					unset( $hash_data['attachments'] );
					$hash_data['attachments'] = array();
					foreach ( $post_data['attachments'] as $key => $attvalues ) {
						$hash_data['attachments'][] = md5($attvalues['data']);
						if ( isset( $attvalues['on_meta_html_css'] ) ) $hash_data['template_extra_css'] = str_replace( $attvalues['on_meta_html_css'], md5($attvalues['data']), $hash_data['template_extra_css'] );
						if ( isset( $attvalues['on_meta_html_css_sizes'] ) && is_array( $attvalues['on_meta_html_css_sizes'] ) ) {
							foreach ( $attvalues['on_meta_html_css_sizes'] as $tsize => $turl ) {
								$hash_data['template_extra_css'] = str_replace( $turl, md5($attvalues['data']) . '_' . $tsize, $hash_data['template_extra_css'] );
							}
						}
						if ( isset( $attvalues['on_meta_html_js'] ) ) $hash_data['template_extra_js'] = str_replace( $attvalues['on_meta_html_js'], $attvalues['data'], $hash_data['template_extra_js'] );
						if ( isset( $attvalues['on_meta_html_js_sizes'] ) && is_array( $attvalues['on_meta_html_js_sizes'] ) ) {
							foreach ( $attvalues['on_meta_html_js_sizes'] as $tsize => $turl ) {
								$hash_data['template_extra_js'] = str_replace( $turl, md5($attvalues['data']) . '_' . $tsize, $hash_data['template_extra_js'] );
							}
						}
						if ( isset( $attvalues['on_post_content'] ) ) {
							$hash_data['post_content'] = str_replace( $attvalues['on_post_content'], $attvalues['data'], $hash_data['post_content'] );
						}
						if ( isset( $attvalues['on_post_content_sizes'] ) && is_array( $attvalues['on_post_content_sizes'] ) ) {
							foreach ( $attvalues['on_post_content_sizes'] as $tsize => $turl ) {
								$hash_data['post_content'] = str_replace( $turl, md5($attvalues['data']) . '_' . $tsize, $hash_data['post_content'] );
							}
						}
					}
				}
				unset( $hash_data['ID'] );
				$items_hash[$post['ID']] = md5(serialize($hash_data));
			}
                    }
                }
            }
        break;
    }
    
    // Offer for download
    $xmldata = $xml->array2xml($data, 'views');
    if ( 'xml' == $mode ) {
	return $xmldata;
    } elseif ( 'module_manager' == $mode ) {
	$export_data = array(
		'xml' => $xmldata,
		'items_hash' => $items_hash // this is an array with format [itemID] => item_hash
	);
	return $export_data;
    }
}
