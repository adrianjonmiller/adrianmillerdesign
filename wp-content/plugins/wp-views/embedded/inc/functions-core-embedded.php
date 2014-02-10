<?php

/**
* Check the existence of a kind of View (normal or archive)
*
* @param $query_mode kind of View object: normal or archive
* @return array() of relevant Views if they exists or false if not
*/

function wpv_check_views_exists( $query_mode ) {
	$all_views_ids = _wpv_get_all_view_ids($query_mode);
	if ( count( $all_views_ids ) != 0 ) {
		return $all_views_ids;
	} else {
		return false;
	}
}

/**
* Get the IDs for all Views of a kind of View (normal or archive)
*
* @param $view_query_mode kind of View object: normal or archive
* @return array() of relevant Views if they exists or empty array if not
*/

function _wpv_get_all_view_ids( $view_query_mode ) {
	global $wpdb, $WP_Views;
	$q = ( 'SELECT ID FROM ' . $wpdb->prefix . 'posts WHERE post_type="view"' );
	$all_views = $wpdb->get_results( $q );
	$view_ids = array();
	foreach ( $all_views as $key => $view ) {
		$settings = $WP_Views->get_view_settings( $view->ID );
		if( $settings['view-query-mode'] != $view_query_mode ) {
			unset( $all_views[$key] );
		} else {
			$view_ids[] = $view->ID;
		}
	}
	return $view_ids;
}

/**
* wpv_count_dissident_posts_from_template
*
* Counts the amount of posts of a given type that do not use a given Template and creates the HTML structure to notify about it
* Used on the Views popups for the Content Templates listing page on single usage and for the Template edit screen
*
* @param $template_id the ID of the Content Template we want to check against
* @param $content_type the post type to check
* @param $message_header (optional) to override the default message on the HTML structure header "Do you want to apply to all?"
*
* @return nothing
*
* @since 1.5.1
*/

function wpv_count_dissident_posts_from_template( $template_id, $content_type, $message_header = null ) {
	global $wpdb;
	
	if ( is_null( $message_header ) ) {
		$message_header = __('Do you want to apply to all?','wpv-views');
	}
	
	$posts = $wpdb->get_col( $wpdb->prepare( "SELECT {$wpdb->posts}.ID FROM {$wpdb->posts} WHERE post_type='%s' AND post_status!='auto-draft'", $content_type ) );
	$count = sizeof( $posts );
	if ( $count > 0 ) {
		$posts = "'" . implode( "','", $posts ) . "'";
		$set_count = $wpdb->get_var( "SELECT COUNT(post_id) FROM {$wpdb->postmeta} WHERE
						meta_key='_views_template' AND meta_value='{$template_id}'
						AND post_id IN ({$posts})" );
		if ( ( $count - $set_count ) > 0 ) {
			$ptype = get_post_type_object( $content_type );
			$type_label = $ptype->labels->singular_name;
			$message = sprintf( __( '%d %s uses a different Content Template.', 'wpv-views' ), ( $count - $set_count ) , $type_label );
			if ( ( $count - $set_count ) > 1 ){
				$type_label = $ptype->labels->name;
				$message = sprintf( __( '%d %s use a different Content Template.', 'wpv-views' ), ( $count - $set_count ) , $type_label );
			}
		?>

			<div class="wpv-dialog">
				<div class="wpv-dialog-header">
					<h2><?php echo $message_header; ?></h2>
					<i class="icon-remove js-dialog-close"></i>
				</div>
				<div class="wpv-dialog-content">
				<?php echo $message; ?>
				</div>
				<div class="wpv-dialog-footer">
					<button class="button js-dialog-close"><?php _e('Cancel','wpv-views') ?></button>
					<button class="button button-primary js-wpv-content-template-update-posts-process"
					data-type="<?php echo $content_type;?>"
					data-id="<?php echo $template_id;?>">
					<?php echo sprintf( __( 'Update %s now', 'wpv-views' ), $type_label ) ?></button>
				</div>
			</div>
		<?php
		}
	}
}

/**
* wpv_update_dissident_posts_from_template
*
* Updates all the of posts of a given type to use a given Template and creates the HTML structure to notify about it
* Used on the Views popups for the Content Templates listing page on single usage and for the Template edit screen
*
* @param $template_id the ID of the Content Template we want to check against
* @param $content_type the post type to check
*
* @return nothing
*
* @since 1.5.1
*/

function wpv_update_dissident_posts_from_template(  $template_id, $content_type ) {
	global $wpdb;
	
	$posts = $wpdb->get_col( "SELECT {$wpdb->posts}.ID FROM {$wpdb->posts}  WHERE post_type='{$content_type}'" );

	$count = sizeof( $posts );
	$updated_count = 0;
	if ( $count > 0 ) {
		foreach ( $posts as $post ) {
			$template_selected = get_post_meta( $post, '_views_template', true );
			if ( $template_selected != $template_id ) {
				update_post_meta( $post, '_views_template',$template_id );
				$updated_count += 1;
			}
		}
	}
	echo '<div class="wpv-dialog wpv-dialog-change js-wpv-dialog-change">
				<div class="wpv-dialog-header">
					<h2>' . __('Success!', 'wpv-views') . '</h2>
				</div>
				<div class="wpv-dialog-content">
					<p>'. sprintf(__('All %ss were updated', 'wpv-views'), $content_type) .'</p>
				</div>
				<div class="wpv-dialog-footer">
					<button class="button-secondary js-dialog-close">'. esc_js( __('Close','wpv-views') ) .'</button>
				</div>
			</div>';
}