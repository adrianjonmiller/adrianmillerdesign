<?php

/*
* We can enable this to hide the Loop selection section
* TODO hide it, refresh the page and show it: the list of loops is still hidden
*/

// add_filter('wpv_sections_archive_loop_show_hide', 'wpv_show_hide_archive_loop', 1,1);

function wpv_show_hide_archive_loop($sections) {
	$sections['archive-loop'] = array(
		'name'		=> __('Loops selection', 'wpv-views'),
		);
	return $sections;
}

add_action('view-editor-section-archive-loop', 'add_view_loop_selection', 10, 2);

function add_view_loop_selection($view_settings, $view_id) {
	global $views_edit_help;

	$hide = '';
	if (isset($view_settings['sections-show-hide']) && isset($view_settings['sections-show-hide']['archive-loop']) && 'off' == $view_settings['sections-show-hide']['archive-loop']) {
		$hide = ' hidden';
	}?>
	<div class="wpv-setting-container wpv-settings-archive-loops js-wpv-settings-archive-loop<?php echo $hide; ?>">
		<div class="wpv-settings-header">
			<h3>
				<?php _e('Loops selection', 'wpv-views' ) ?>
				<i class="icon-question-sign js-display-tooltip" data-header="<?php echo $views_edit_help['loops_selection']['title'] ?>" data-content="<?php echo $views_edit_help['loops_selection']['content'] ?>"></i>
			</h3>
		</div>
		<div class="wpv-setting">
			<?php global $WPV_view_archive_loop, $WP_Views;
			$options = $WP_Views->get_options();
			$loops = $WPV_view_archive_loop->_get_post_type_loops();
			$options = $WPV_view_archive_loop->_view_edit_options($view_id, $options);
			?>
			<form class="js-loop-selection-form">

				<h3><?php _e('Post type loops', 'wpv-views'); ?></h3>
				<ul class="enable-scrollbar">
				<?php foreach($loops as $loop => $loop_name): ?>
					<?php $checked = (isset ($options['view_' . $loop]) && $options['view_' . $loop] == $view_id) ? ' checked="checked"' : ''; ?>
					<li>
						<label>
							<input type="checkbox" <?php echo $checked; ?> name="wpv-view-loop-<?php echo $loop; ?>" />
							<?php echo $loop_name; ?>
						</label>
					</li>

				<?php endforeach; ?>
				</ul>

				<h3><?php _e('Taxonomy loops', 'wpv-views'); ?></h3>
				<ul class="enable-scrollbar">
				<?php
					$taxonomies = get_taxonomies('', 'objects');
					$exclude_tax_slugs = array();
					$exclude_tax_slugs = apply_filters( 'wpv_admin_exclude_tax_slugs', $exclude_tax_slugs );
					foreach ($taxonomies as $category_slug => $category):
						if ( in_array($category_slug, $exclude_tax_slugs) ) {
							continue;
						}
						if ( !$category->show_ui ) {
							continue; // Only show taxonomies with show_ui set to TRUE
						}
						$name = $category->name;
						$checked = (isset ($options['view_taxonomy_loop_' . $name ]) && $options['view_taxonomy_loop_' . $name ] == $view_id) ? ' checked="checked"' : '';
					?>
						<li>
							<label>
								<input type="checkbox" <?php echo $checked; ?> name="wpv-view-taxonomy-loop-<?php echo $name; ?>" />
								<?php echo $category->labels->name; ?>
							</label>
						</li>
					<?php endforeach; ?>
				</ul>
			</form>
			<p class="update-button-wrap">
				<button data-success="<?php echo htmlentities( __('Loop selection updated', 'wpv-views'), ENT_QUOTES ); ?>" data-unsaved="<?php echo htmlentities( __('Loop selection not saved', 'wpv-views'), ENT_QUOTES ); ?>" data-nonce="<?php echo wp_create_nonce( 'wpv_view_loop_selection_nonce' ); ?>" class="js-wpv-loop-selection-update button-secondary" disabled="disabled"><?php _e('Update', 'wpv-views'); ?></button>
			</p>
		</div>
	</div>
<?php }