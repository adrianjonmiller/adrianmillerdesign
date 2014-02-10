<?php

/*
* We can enable this to hide the Filter HTML/CSS/JS section
* TODO if we enable this and a user enables pagination with this section hidden there can be problems
*/

add_filter('wpv_sections_filter_show_hide', 'wpv_show_hide_filter_extra', 1,1);

function wpv_show_hide_filter_extra($sections) {
	$sections['filter-extra'] = array(
		'name'		=> __('Filter HTML/CSS/JS', 'wpv-views'),
		);
	return $sections;
}

add_action('view-editor-section-filter', 'add_view_filter_extra', 30, 2);

function add_view_filter_extra($view_settings, $view_id) {
    global $views_edit_help;
	$hide = '';
	if (isset($view_settings['sections-show-hide']) && isset($view_settings['sections-show-hide']['filter-extra']) && 'off' == $view_settings['sections-show-hide']['filter-extra']) {
		$hide = ' hidden';
	}?>
	<div class="wpv-setting-container wpv-setting-container-horizontal wpv-settings-filter-markup js-wpv-settings-filter-extra<?php echo $hide; ?>">

		<div class="wpv-settings-header">
			<h3>
				<?php _e( 'Filter HTML/CSS/JS', 'wpv-views' ) ?>
				<i class="icon-question-sign js-display-tooltip" data-header="<?php echo $views_edit_help['filters_html_css_js']['title']; ?>" data-content="<?php echo $views_edit_help['filters_html_css_js']['content']; ?>"></i>
			</h3>
		</div>

		<div class="wpv-setting">

			<!-- <div class="js-error-container"></div> -->
			<div class="code-editor js-code-editor filter-html-editor" data-name="filter-html-editor" >
				<div class="code-editor-toolbar js-code-editor-toolbar">
					<ul class="js-wpv-filter-edit-toolbar">
						<li class="wpv-vicon-codemirror-button">
							<?php wpv_add_v_icon_to_codemirror( 'wpv_filter_meta_html_content' ); ?>
						</li>
						<li>
							<?php // TODO Review CRED button, produces orphan li if CRED not activated
							//CREED BUTTON
							wpv_add_cred_to_codemirror('wpv_filter_meta_html_content');
							?>
						</li>
						<li class="js-editor-pagination-button-wrapper">
							<button class="button-secondary js-code-editor-toolbar-button js-wpv-pagination-popup" data-content="wpv_filter_meta_html_content">
								<i class="icon-pagination"></i>
								<span class="button-label"><?php _e('Pagination','wpv-views'); ?></span>
							</button>
						</li>
						<?php echo apply_filters('wpv_meta_html_add_form_button_new', '', '#wpv_filter_meta_html_content'); ?>
						<li>
							<button class="button-secondary js-code-editor-toolbar-button js-wpv-media-manager" data-id="<?php echo $view_id;?>" data-content="wpv_filter_meta_html_content">
								<i class="icon-picture"></i>
								<span class="button-label"><?php _e('Media','wpv-views'); ?></span>
							</button>
						</li>
					</ul>

				</div>

				<textarea cols="30" rows="10" id="wpv_filter_meta_html_content" name="_wpv_settings[filter_meta_html]"><?php echo ( isset( $view_settings['filter_meta_html'] ) ) ? $view_settings['filter_meta_html'] : ''; ?></textarea>
			</div>
			
			<?php 
			$filter_extra_css = isset( $view_settings['filter_meta_html_css'] ) ? $view_settings['filter_meta_html_css'] : '';
			if ( empty( $filter_extra_css ) ) {
				$aux_class = ' code-editor-textarea-empty';
			} else {
				$aux_class = ' code-editor-textarea-full';
			}
			?>

			<p class="js-wpv-filter-css-editor-old-place">
				<input type="hidden" name="_wpv_settings[filter_meta_html_state][css]" id="wpv_filter_meta_html_extra_css_state" value="<?php echo isset($view_settings['filter_meta_html_state']['css']) ? $view_settings['filter_meta_html_state']['css'] : 'off'; ?>" />
				<button class="button-secondary js-code-editor-button filter-css-editor-button<?php echo $aux_class; ?>" data-target="filter-css-editor" data-state="closed" data-closed="<?php echo htmlentities( __( 'Open CSS editor', 'wpv-views' ), ENT_QUOTES ); ?>" data-opened="<?php echo htmlentities( __( 'Close CSS editor', 'wpv-views' ), ENT_QUOTES ); ?>">
					<?php _e( 'Open CSS editor', 'wpv-views' ) ?>
				</button>
			</p>

			<div class="js-code-editor code-editor filter-css-editor closed" data-name="filter-css-editor">
				<div class="code-editor-toolbar js-code-editor-toolbar">
					<ul>
						<li>
							<button class="button-secondary js-code-editor-toolbar-button js-wpv-media-manager" data-id="<?php echo $view_id;?>" data-content="wpv_filter_meta_html_css">
								<i class="icon-picture"></i>
								<span class="button-label"><?php _e('Media','wpv-views'); ?></span>
							</button>
						</li>
					</ul>
				</div>
				<textarea cols="30" rows="10" id="wpv_filter_meta_html_css" name="_wpv_settings[filter_meta_html_css]"><?php echo $filter_extra_css; ?></textarea>
			</div>
			
			<?php 
			$filter_extra_js = isset( $view_settings['filter_meta_html_js'] ) ? $view_settings['filter_meta_html_js'] : '';
			if ( empty( $filter_extra_js ) ) {
				$aux_class = ' code-editor-textarea-empty';
			} else {
				$aux_class = ' code-editor-textarea-full';
			}
			?>

			<p class="js-wpv-filter-js-editor-old-place">
				<input type="hidden" name="_wpv_settings[filter_meta_html_state][js]" id="wpv_filter_meta_html_extra_js_state" value="<?php echo isset($view_settings['filter_meta_html_state']['js']) ? $view_settings['filter_meta_html_state']['js'] : 'off'; ?>" />
				<button class="button-secondary js-code-editor-button filter-js-editor-button<?php echo $aux_class; ?>" data-target="filter-js-editor"  data-state="closed" data-closed="<?php echo htmlentities( __( 'Open JS editor', 'wpv-views' ), ENT_QUOTES ); ?>" data-opened="<?php echo htmlentities( __( 'Close JS editor', 'wpv-views' ), ENT_QUOTES ); ?>">
					<?php _e( 'Open JS editor', 'wpv-views' ) ?>
				</button>
			</p>

			<div class="js-code-editor code-editor filter-js-editor closed" data-name="filter-js-editor" >
				<div class="code-editor-toolbar js-code-editor-toolbar">
					<ul>
						<li>
							<button class="button-secondary js-code-editor-toolbar-button js-wpv-media-manager" data-id="<?php echo $view_id;?>" data-content="wpv_filter_meta_html_js">
								<i class="icon-picture"></i>
								<span class="button-label"><?php _e('Media','wpv-views'); ?></span>
							</button>
						</li>
					</ul>
				</div>
				<textarea cols="30" rows="10" id="wpv_filter_meta_html_js" name="_wpv_settings[filter_meta_html_js]"><?php echo $filter_extra_js; ?></textarea>
			</div>

			<p class="update-button-wrap">
				<button data-success="<?php echo htmlentities( __('Data updated', 'wpv-views'), ENT_QUOTES ); ?>" data-unsaved="<?php echo htmlentities( __('Data not saved', 'wpv-views'), ENT_QUOTES ); ?>" data-nonce="<?php echo wp_create_nonce( 'wpv_view_filter_extra_nonce' ); ?>" class="js-wpv-filter-extra-update button-secondary" disabled="disabled"><?php _e('Update', 'wpv-views'); ?></button>
			</p>
		</div>

	</div>
<?php }