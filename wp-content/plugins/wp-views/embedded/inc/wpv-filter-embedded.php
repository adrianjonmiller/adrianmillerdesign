<?php


/* Handle the short codes for creating a user query form
  
  [wpv-filter-start]
  [wpv-filter-end]
  [wpv-filter-submit]
  
*/

/**
 * Views-Shortcode: wpv-filter-start
 *
 * Description: The [wpv-filter-start] shortcode specifies the start point
 * for any controls that the views filter generates. Example controls are
 * pagination controls and search boxes. This shortcode is usually added
 * automatically to the Views Meta HTML.
 *
 * Parameters:
 * This takes no parameters.
 *
 * Example usage:
 *
 * Link:
 *
 * Note:
 *
 */
add_shortcode('wpv-filter-start', 'wpv_filter_shortcode_start');
function wpv_filter_shortcode_start($atts){
	
	global $WP_Views;
	$view_id = $WP_Views->get_current_view();
	$view_settings = $WP_Views->get_view_settings();
	$view_layout_settings = $WP_Views->get_view_layout_settings();
	$is_required = false;
	$out = '';
	
    if ( _wpv_filter_is_form_required() ) {
		
		$is_required = true;
		
        extract(
            shortcode_atts( array(), $atts )
        );
        
        $hide = '';
        if (isset($atts['hide']) && $atts['hide'] == 'true') {
            $hide = ' style="display:none;"';
        }
        
        $url = get_permalink();
        $out = '<form' . $hide . ' name="wpv-filter-' . $WP_Views->get_view_count() . '" action="' . $url . '" method="get" class="wpv-filter-form">';
        
        // add hidden inputs for any url parameters.
        // We need these for when the form is submitted.
        $url_query = parse_url($url, PHP_URL_QUERY);
        if ($url_query != '') {
            $query_parts = explode('&', $url_query);
            foreach($query_parts as $param) {
                $item = explode('=', $param);
                if (strpos($item[0], 'wpv_') !== 0) {
                    $out .= '<input id="wpv_param_' . $item[0] . '" type="hidden" name="' . $item[0] . '" value="' . $item[1] . '" />';
                }
            }
        }
        
        // Add hidden inputs for column sorting id and direction:
        // these start populated with the View settings values and will be changed when a column title is clicked.
        if (isset($view_layout_settings['style']) && ($view_layout_settings['style'] == 'table_of_fields' or $view_layout_settings['style'] == 'table')) {
            if ($view_settings['query_type'][0] == 'posts') {
                $sort_id = $view_settings['orderby'];
                $sort_dir = strtolower($view_settings['order']);
            }
            if ($view_settings['query_type'][0] == 'taxonomy') {
                $sort_id = $view_settings['taxonomy_orderby'];
                $sort_dir = strtolower($view_settings['taxonomy_order']);
            }
            if ($view_settings['query_type'][0] == 'users') {
                $sort_id = $view_settings['users_orderby'];
                $sort_dir = strtolower($view_settings['users_order']);
            }

            if (isset($_GET['wpv_column_sort_id']) && esc_attr($_GET['wpv_column_sort_id']) != '' && isset($_GET['wpv_view_count']) && esc_attr($_GET['wpv_view_count']) == $WP_Views->get_view_count()) {
                $sort_id = esc_attr($_GET['wpv_column_sort_id']);
            }
            if (isset($_GET['wpv_column_sort_dir']) && esc_attr($_GET['wpv_column_sort_dir']) != '' && isset($_GET['wpv_view_count']) && esc_attr($_GET['wpv_view_count']) == $WP_Views->get_view_count()) {
                $sort_dir = esc_attr($_GET['wpv_column_sort_dir']);
            }
            
            $out .= '<input id="wpv_column_sort_id" type="hidden" name="wpv_column_sort_id" value="' . $sort_id . '" />';
            $out .= '<input id="wpv_column_sort_dir" type="hidden" name="wpv_column_sort_dir" value="' . $sort_dir . '" />';
        }
        
        /**
        * Add other hidden fields for:
        *
        * max number of pages for this View
        * preload reach
        * widget ID when aplicable
        * View count for multiple Views per pages
        * View hash
        * current post ID when needed
        */
        
        $out .= '<input id="wpv_paged_max-' . $WP_Views->get_view_count() . '" type="hidden" name="wpv_paged_max" value="' . intval($WP_Views->get_max_pages()) . '" />';
        
        if ( isset( $view_settings['pagination']['pre_reach'] ) ) { $pre_reach = intval($view_settings['pagination']['pre_reach']); } else { $pre_reach = 1; }
        $out .= '<input id="wpv_paged_preload_reach-' . $WP_Views->get_view_count() . '" type="hidden" name="wpv_paged_preload_reach" value="' . $pre_reach . '" />';
        
        $out .= '<input id="wpv_widget_view-' . $WP_Views->get_view_count() . '" type="hidden" name="wpv_widget_view_id" value="' . intval($WP_Views->get_widget_view_id()) . '" />';
        $out .= '<input id="wpv_view_count-' . $WP_Views->get_view_count() . '" type="hidden" name="wpv_view_count" value="' . $WP_Views->get_view_count() . '" />';

        $view_data = $WP_Views->get_view_shortcodes_attributes();
        //$view_data['view_id'] = $WP_Views->get_current_view();
        $out .= '<input id="wpv_view_hash-' . $WP_Views->get_view_count() . '" type="hidden" name="wpv_view_hash" value="' . base64_encode(serialize($view_data)) . '" />';
    
        $requires_current_page = false;
        $requires_current_page = apply_filters('wpv_filter_requires_current_page', $requires_current_page, $view_settings);
        
        if ($requires_current_page) {
            // Output the current page ID. This is used for ajax call back in pagination.
            $current_post = $WP_Views->get_top_current_page();
            if ($current_post && isset($current_post->ID)) {
                $out .= '<input id="wpv_post_id-' . $WP_Views->get_view_count() . '" type="hidden" name="wpv_post_id" value="' . $current_post->ID . '" />';
            }
        }
        add_action('wp_footer', 'wpv_pagination_js');
        
        // Rollover
        if (isset($view_settings['pagination']['mode']) && $view_settings['pagination']['mode'] == 'rollover') {
            wpv_pagination_rollover_shortcode();
        }
        
    }
    
	/**
	* Filter wpv_filter_start_filter_form
	*
	* @param $out the default form opening tag followed by the required hidden input tags needed for pagination and table sorting
	* @param $view_settings the current View settings
	* @param $view_id the ID of the View being displayed
	* @param $is_required [true|false] whether this View requires a form to be displayed (has a parametric search OR uses table sorting OR uses pagination)
	*
	* This can be useful to create additional inputs for the current form without needing to add them to the Filter HTML textarea
	* Also, can help users having formatting issues
	*
	* @return $out
	*
	* Since 1.5.1
	*
	*/
	
	$out = apply_filters( 'wpv_filter_start_filter_form', $out, $view_settings, $view_id, $is_required );
    
    return $out;
}

/**
 * Views-Shortcode: wpv-filter-end
 *
 * Description: The [wpv-filter-end] shortcode is the end point
 * for any controls that the views filter generates.
 *
 * Parameters:
 * This takes no parameters.
 *
 * Example usage:
 *
 * Link:
 *
 * Note:
 *
 */
  
add_shortcode('wpv-filter-end', 'wpv_filter_shortcode_end');
function wpv_filter_shortcode_end($atts){
	
	global $WP_Views;
	$view_id = $WP_Views->get_current_view();
	$view_settings = $WP_Views->get_view_settings();
	$is_required = false;
	$out = '';
	
    if (_wpv_filter_is_form_required()) {
		$is_required = true;
        extract(
            shortcode_atts( array(), $atts )
        );
        $out = '</form>';
	}
	
	/**
	* Filter wpv_filter_end_filter_form
	*
	* @param $out the default form closing tag
	* @param $view_settings the current View settings
	* @param $view_id the ID of the View being displayed
	* @param $is_required [true|false] whether this View requires a form to be displayed (has a parametric search OR uses table sorting OR uses pagination)
	*
	* This can be useful to create additional inputs for the current form without needing to add them to the Filter HTML textarea
	*
	* @return $out
	*
	* Since 1.5.1
	*
	*/
	
	$out = apply_filters( 'wpv_filter_end_filter_form', $out, $view_settings, $view_id, $is_required );
    
    return $out;
}
    
function _wpv_filter_is_form_required() {
    global $WP_Views;

    if ($WP_Views->rendering_views_form()) {
        return true;
    }
    
    $view_layout_settings = $WP_Views->get_view_layout_settings();
	// debug only do not commit this file
	// RICCARDO $view_layout_settings['style'] = 'unformatted';
	
//    if (!isset($view_layout_settings['style'])) {
    //    return false;
//    }

    if (isset($view_layout_settings['style']) && $view_layout_settings['style'] == 'table_of_fields') {
        // required for table sorting
        return true;
    }

    $view_settings = $WP_Views->get_view_settings();
    if ($view_settings['pagination'][0] == 'enable' || $view_settings['pagination']['mode'] == 'rollover') {
        return true;
    }

    $meta_html = $view_settings['filter_meta_html'];
	if(preg_match('#\\[wpv-control.*?\\]#is', $meta_html, $matches)) {
	    if ($matches[0] != '') {
	        return true;
	    }
	}


    if (isset($view_settings['post_search_value']) || isset($view_settings['taxonomy_search_value'])) {
        return true;
    }
    
    return false;
}

/**
 * Views-Shortcode: wpv-filter-submit
 *
 * Description: The [wpv-filter-submit] shortcode adds a submit button to
 * the form that the views filter generates. An example is the "Submit" button
 * for a search box
 *
 * Parameters:
 * 'hide' => 'true'|'false'
 * 'name' => The text to be used on the button.
 * 'class' => The classname to be applied to the button - space-separated list
 *
 * Example usage:
 *
 * Link:
 *
 * Note:
 *
 */
  
add_shortcode('wpv-filter-submit', 'wpv_filter_shortcode_submit');
function wpv_filter_shortcode_submit($atts){
    if ( _wpv_filter_is_form_required() ) {
        extract(
            shortcode_atts( array(), $atts )
        );
        
        $hide = '';
        if (isset($atts['hide']) && $atts['hide'] == 'true') {
            $hide = ' style="display:none"';
        }
        $class = '';
        if ( isset( $atts['class'] ) ) {
            $class = ' class="' . $atts['class'] . '"';
        }
        
        //      $name = wpv_translate('wpv-filter-submit-' . $atts['name'], $atts['name'], true);
	global $WP_Views;
	$aux_array = $WP_Views->view_used_ids;
	$view_name = get_post_field( 'post_name', end($aux_array));
        $name = wpv_translate( 'submit_name', $atts['name'], false, 'View ' . $view_name );
        $out = '';
        $out .= '<input type="submit" value="' . $name . '" name="wpv_filter_submit"' . $hide . $class . ' />';
        return $out;
    } else {
        return '';
    }
}

/**
 * Views-Shortcode: wpv-post-count
 *
 * Description: The [wpv-post-count] shortcode displays the number of posts
 * that will be displayed on the page. When using pagination, this value will
 * be limited by the page size and the number of remaining results.
 *
 * Parameters:
 * This takes no parameters.
 *
 * Example usage:
 * Showing [wpv-post-count] posts of [wpv-found-count] posts found
 *
 * Link:
 *
 * Note:
 * This shortcode is deprecated in favor of [wpv-items-count]
 *
 */
  
add_shortcode('wpv-post-count', 'wpv_post_count');
function wpv_post_count($atts){
    extract(
        shortcode_atts( array(), $atts )
    );

    global $WP_Views;
    
    $query = $WP_Views->get_query();
    
    if ($query) {
        return $query->post_count;
    } else {
        return '';
    }
}


/**
 * Views-Shortcode: wpv-items-count
 *
 * Description: The [wpv-items-count] shortcode displays the number of items (posts/taxonomy terms/users)
 * that will be displayed on the page. When using pagination, this value will
 * be limited by the page size and the number of remaining results.
 *
 * Parameters:
 * This takes no parameters.
 *
 * Example usage:
 * Showing [wpv-items-count] posts of [wpv-found-count] posts found
 *
 * Link:
 *
 * Note:
 *
 */
  
add_shortcode('wpv-items-count', 'wpv_items_count');
function wpv_items_count($atts){
     extract(
        shortcode_atts( array(), $atts )
    );

    global $WP_Views;

	if ( isset($WP_Views->current_view) ){
    	$view_settings = get_post_meta($WP_Views->current_view, '_wpv_settings', true);
    }
	$out = '';
	
	if ( !isset($view_settings['query_type'][0]) || ( isset($view_settings['query_type'][0]) && $view_settings['query_type'][0]=='posts' )){
    	$query = $WP_Views->get_query();
		if ( isset($query->post_count) ){
			$out = $query->post_count;
		}
	}elseif( isset($view_settings['query_type'][0]) && $view_settings['query_type'][0]=='users' ){
		if ( isset($WP_Views->users_data['item_count_this_page']) ){
			$out = $WP_Views->users_data['item_count_this_page']; 
		}
	}
	elseif( isset($view_settings['query_type'][0]) && $view_settings['query_type'][0]=='taxonomy' ){
		if ( isset($WP_Views->taxonomy_data['item_count_this_page']) ){
			$out = $WP_Views->taxonomy_data['item_count_this_page']; 
		}
	}
    
	return $out;
}


    
/**
 * Views-Shortcode: wpv-found-count
 *
 * Description: The [wpv-found-count] shortcode displays the total number of
 * items (posts/taxonomy terms/users) that have been found by the Views query. This value is calculated
 * before pagination, so even if you are using pagination, it will return
 * the total number of posts matching the query.
 *
 * Parameters:
 * This takes no parameters.
 *
 * Example usage:
 * Showing [wpv-post-count] posts of [wpv-found-count] posts found
 *
 * Link:
 *
 * Note:
 *
 */
  
 
add_shortcode('wpv-found-count', 'wpv_found_count');
function wpv_found_count($atts){
    extract(
        shortcode_atts( array(), $atts )
    );

    global $WP_Views;

	if ( isset($WP_Views->current_view) ){
    	$view_settings = get_post_meta($WP_Views->current_view, '_wpv_settings', true);
    }
	$out = '';
	
	if ( !isset($view_settings['query_type'][0]) || ( isset($view_settings['query_type'][0]) && $view_settings['query_type'][0]=='posts' )){
    	$query = $WP_Views->get_query();
		if ( isset($query->found_posts) ){
			$out = $query->found_posts;
		}
	}elseif( isset($view_settings['query_type'][0]) && $view_settings['query_type'][0]=='users' ){
		if ( isset($WP_Views->users_data['item_count']) ){
			$out = $WP_Views->users_data['item_count']; 
		}
	}
	elseif( isset($view_settings['query_type'][0]) && $view_settings['query_type'][0]=='taxonomy' ){
		if ( isset($WP_Views->taxonomy_data['item_count']) ){
			$out = $WP_Views->taxonomy_data['item_count']; 
		}
	}
    
	return $out;
}

/**
 * Views-Shortcode: wpv-posts-found
 *
 * Description: The wpv-posts-found shortcode will display the text inside
 * the shortcode if there are posts found by the Views query.
 *
 * Parameters:
 * This takes no parameters.
 *
 * Example usage:
 * [wpv-posts-found]Some posts were found[/wpv-posts-found]
 *
 * Link:
 *
 * Note:
 * This shortcode is deprecated in favour of the new [wpv-items-found]
 *
 */
  
add_shortcode('wpv-posts-found', 'wpv_posts_found');
function wpv_posts_found($atts, $value){
    extract(
        shortcode_atts( array(), $atts )
    );

    global $WP_Views;
    
    $query = $WP_Views->get_query();

    if ($query && ($query->found_posts != 0 || $query->post_count != 0)) {
        // display the message when posts are found.
        return wpv_do_shortcode($value);
    } else {
        return '';
    }
    
}
    
/**
 * Views-Shortcode: wpv-no-posts-found
 *
 * Description: The wpv-no-posts-found shortcode will display the text inside
 * the shortcode if there are no posts found by the Views query.
 *
 * Parameters:
 * This takes no parameters.
 *
 * Example usage:
 * [wpv-no-posts-found]No posts found[/wpv-no-posts-found]
 *
 * Link:
 *
 * Note:
 * This shortcode is deprecated in favour of the new [wpv-no-items-found]
 *
 */
  
add_shortcode('wpv-no-posts-found', 'wpv_no_posts_found');
function wpv_no_posts_found($atts, $value){
    extract(
        shortcode_atts( array(), $atts )
    );

    global $WP_Views;
    
    $query = $WP_Views->get_query();

    if ($query && $query->found_posts == 0 && $query->post_count == 0) {
        // display the message when no posts are found.
        return wpv_do_shortcode($value);
    } else {
        return '';
    }
    
}

/**
 * Views-Shortcode: wpv-items-found
 *
 * Description: The wpv-items-found shortcode will display the text inside
 * the shortcode if there are items found by the Views query.
 *
 * Parameters:
 * This takes no parameters.
 *
 * Example usage:
 * [wpv-items-found]Some posts/taxonomy terms/users were found[/wpv-items-found]
 *
 * Link:
 *
 * Note:
 *
 */
  
add_shortcode('wpv-items-found', 'wpv_items_found');
function wpv_items_found($atts, $value){
    extract(
        shortcode_atts( array(), $atts )
    );

    global $WP_Views;
    
    $view_settings = $WP_Views->get_view_settings();

    if ( isset( $view_settings['query_type'] ) && isset( $view_settings['query_type'][0] ) && 
        ( $view_settings['query_type'][0] == 'taxonomy' || $view_settings['query_type'][0] == 'users') ) {

    if ( $view_settings['query_type'][0] == 'users' ){
	   $number = $WP_Views->get_users_found_count();
    }else{
       $number = $WP_Views->get_taxonomy_found_count();
    }

	if ($number && $number != 0) {
		// display the message when posts are found.
		return wpv_do_shortcode($value);
	} else {
		return '';
	}
	
    } else {
    
	$query = $WP_Views->get_query();

	if ($query && ($query->found_posts != 0 || $query->post_count != 0)) {
		// display the message when posts are found.
		return wpv_do_shortcode($value);
	} else {
		return '';
	}
    }
    
}

/**
 * Views-Shortcode: wpv-no-items-found
 *
 * Description: The wpv-no-items-found shortcode will display the text inside
 * the shortcode if there are no items found by the Views query.
 *
 * Parameters:
 * This takes no parameters.
 *
 * Example usage:
 * [wpv-no-items-found]No items found[/wpv-no-items-found]
 *
 * Link:
 *
 * Note:
 *
 */
  
add_shortcode('wpv-no-items-found', 'wpv_no_items_found');
function wpv_no_items_found($atts, $value){
    extract(
        shortcode_atts( array(), $atts )
    );

    global $WP_Views;
    
    $view_settings = $WP_Views->get_view_settings();
    
    if ( isset( $view_settings['query_type'] ) && isset( $view_settings['query_type'][0] ) && $view_settings['query_type'][0] == 'taxonomy' ) {
    
	$number = $WP_Views->get_taxonomy_found_count();
	
	if ( isset($number) && $number === 0) {
		// display the message when posts are found.
		return wpv_do_shortcode($value);
	} else {
		return '';
	}
	
    } else if ( $view_settings['query_type'][0] == 'users' ){
    
        $number = $WP_Views->get_users_found_count();
        
        if ( isset($number) && $number === 0) {
		// display the message when posts are found.
		return wpv_do_shortcode($value);
	} else {
		return '';
	}
	
    } else {
    
	$query = $WP_Views->get_query();

	if ($query && $query->found_posts == 0 && $query->post_count == 0) {
		// display the message when no posts are found.
		return wpv_do_shortcode($value);
	} else {
		return '';
	}
    }
}
    
/*
         
    This shows the user interface to the end user on page
    that contains the view.
    
*/

function wpv_filter_show_user_interface($name, $values, $selected, $style) {
    $out = '';
    $out .= "<div>\n";
    
    if ($style == 'drop_down') {
        $out .= '<select name="'. $name . '[]">' . "\n";
    }
    
    foreach($values as $v) {
        switch ($style) {
            case "checkboxes":
                if (is_array($selected)) {
                    $checked = @in_array($v, $selected) ? ' checked="checked"' : '';
                } else {
                    $checked = $v == $selected ? ' checked="checked"' : '';
                }
                $out .= '<label><input type="checkbox" name="' . $name. '[]" value="' . $v . '" ' . $checked . ' />&nbsp;' . $v . "</label>\n";
                break;

            case "radios":
                if (is_array($selected)) {
                    $checked = @in_array($v, $selected) ? ' checked="checked"' : '';
                } else {
                    $checked = $v == $selected ? ' checked="checked"' : '';
                }
                $out .= '<label><input type="radio" name="' . $name. '[]" value="' . $v . '" ' . $checked . ' />&nbsp;' . $v . "</label>\n";
                break;

            case "drop_down":
                if (is_array($selected)) {
                    $is_selected = @in_array($v, $selected) ? ' selected="selected"' : '';
                } else {
                    $is_selected = $v == $selected ? ' selected="selected"' : '';
                }
                $out .= '<option value="' . $v . '" ' . $is_selected . '>' . $v . "</option>\n";
                break;
        }
    }

    if ($style == 'drop_down') {
        $out .= "</select>\n";
    }
    
    $out .= "</div>\n";
    
    return $out;
}


/**
 * 
 * Views-Shortcode: wpv-control
 *
 * Description: Add filters for View
 *
 * Parameters:
 * type: type of retrieved field layout (radio, checkbox, select, textfield, checkboxes, datepicker)
 * url_param: the URL parameter passed as an argument
 * values: Optional. a list of supplied values
 * display_values: Optional. A list of values to display for the corresponding values
 * auto_fill: Optional. When set to a "field-slug" the control will be populated with custom field values from the database.
 * auto_fill_default: Optional. Used to set the default, unselected, value of the control. eg Ignore or Don't care
 * auto_fill_sort: Optional. 'asc', 'desc', 'ascnum', 'descnum', 'none'. Defaults to ascending.
 * field: Optional. a Types field to retrieve values from
 * title: Optional. Use for the checkbox title
 * taxonomy: Optional. Use when a taxonomy control should be displayed.
 * default_label: Optional. Use when a taxonomy control should be displayed using select input type.
 * date_format: Optional. Used for a datepicker control
 *
 * Example usage:
 *
 * Link:
 * More details about this shortcode here: <a href="http://wp-types.com/documentation/wpv-control-fields-in-front-end-filters/" title="wpv-control – Displaying fields in front-end filters">http://wp-types.com/documentation/wpv-control-fields-in-front-end-filters/</a>
 *
 * Note:
 *
 */
function wpv_shortcode_wpv_control($atts) {
	global $WP_Views;
	$aux_array = $WP_Views->view_used_ids;
	$view_name = get_post_field( 'post_name', end($aux_array));
	
	if(!isset($atts['url_param'])) {
		return __('The url_param is missing from the wpv-control shortcode argument.', 'wpv-views');	
	}
    
    if((!isset($atts['type']) || $atts == '') && !isset($atts['field'])) {
		return __('The "type" or "field" needs to be set in the wpv-control shortcode argument.', 'wpv-views');	
    }
	
	extract(
		shortcode_atts(array(
				'type' => '',
				'values' => array(),
                'display_values' => array(),
				'field' => '',
				'url_param' => '',
                'title' => '',
                'taxonomy' => '',
                'taxonomy_orderby' => 'name',
                'taxonomy_order' => 'ASC',
                'format' => false,
                'default_label' => '', // new shortcode attribute for default label for taxonomies filter controls when using select input type
                'hide_empty' => 'false',
                'auto_fill' => '',
                'auto_fill_default' => '',
                'auto_fill_sort' => '',
                'date_format' => '',
				'default_date' => ''  // Default date for date control
			), $atts)
	);
	
	
	
    if ($taxonomy != '') { // pass the new shortcode attribute $default_label
	$default_label = wpv_translate( $url_param . '_default_label', $default_label, false, 'View ' . $view_name );
        return _wpv_render_taxonomy_control($taxonomy, $type, $url_param, $default_label, $taxonomy_orderby, $taxonomy_order, $format, $hide_empty);
    }
	
	$multi = '';
	$display_values_trans = false;
	
	if( $type == 'multi-select')
	{
		$type = 'select';
		$multi = 'multiple';
		
	}
    
    if ($auto_fill != '') {
        
        // See if we should handle types checkboxes
        $types_checkboxes_field = false;
        $auto_fill_default_trans = false;
        $display_values_traans = false;
        if(_wpv_is_field_of_type($auto_fill, 'checkboxes')) {
            if (!function_exists('wpcf_admin_fields_get_fields')) {
                if(defined('WPCF_EMBEDDED_ABSPATH')) {
                    include WPCF_EMBEDDED_ABSPATH . '/includes/fields.php';
                }
            }
            if (function_exists('wpcf_admin_fields_get_fields')) {
                $fields = wpcf_admin_fields_get_fields();
                
                $field_name = substr($auto_fill, 5);
                if (isset($fields[$field_name])) {
                    $types_checkboxes_field = true;
                    
                    $db_values = array();
                    
                    $options = $fields[$field_name]['data']['options'];
                    
                    foreach($options as $field_key=>$option) {
			$db_values[] = $option['title'];
                        $display_text[$option['title']] = wpv_translate( 'field '. $fields[$field_name]['id'] .' option '. $field_key .' title', $option['title'], false, 'plugin Types' );
                    }

                    switch (strtolower($auto_fill_sort)) {
                        case 'desc':
                            sort($db_values);
                            $db_values = array_reverse($db_values);
                            break;
                        
                        case 'descnum':
                            sort($db_values, SORT_NUMERIC);
                            $db_values = array_reverse($db_values);
                            break;
                        
                        case 'none':
                            break;
                        
                        case 'ascnum':            
                            sort($db_values, SORT_NUMERIC);
                            break;
            
                        default:            
                            sort($db_values);
                            break;
                    }
                    
                }
            }
        }
        
        if (!$types_checkboxes_field) {
		if ( !function_exists( 'wpcf_admin_fields_get_fields' ) ) {
			if( defined( 'WPCF_EMBEDDED_ABSPATH' ) ) {
			include WPCF_EMBEDDED_ABSPATH . '/includes/fields.php';
			}
		}
		if ( function_exists( 'wpcf_admin_fields_get_fields' ) ) {
			$fields = wpcf_admin_fields_get_fields();
		}
		$field_name = substr($auto_fill, 5);
		if ( isset( $fields ) && isset( $fields[$field_name] ) && isset( $fields[$field_name]['data']['options'] ) ) {
			$display_text = array();
			$options = $fields[$field_name]['data']['options'];
			if ( isset( $options['default'] ) ) unset($options['default']); // remove the default option from the array
			if ( isset( $fields[$field_name]['data']['display'] ) ) $display_option =  $fields[$field_name]['data']['display'];
			foreach ( $options as $field_key=>$option ) {
				if ( isset( $option['value'] ) ) $db_values[] = $option['value'];
				if ( isset( $display_option ) && 'value' == $display_option && isset( $option['display_value'] ) ) {
					// $display_text[$option['value']] = $option['display_value']; // fill an array with the actual display values
					$display_text[$option['value']] = wpv_translate( 'field '. $fields[$field_name]['id'] .' option '. $field_key .' title',
                    $option['display_value'], false, 'plugin Types' );
				} else {
					//$display_text[$option['value']] = $option['title'];
					$display_text[$option['value']] = wpv_translate( 'field '. $fields[$field_name]['id'] .' option '. $field_key .' title',
                    $option['title'], false, 'plugin Types' );
				}
				if ($auto_fill_default != '') {
					// translate the auto_fill_default option if needed, just when it's one of the existing options
					$auto_fill_default = str_replace('\,', ',', $auto_fill_default);
					if ($auto_fill_default == $option['title']) {
						$auto_fill_default = wpv_translate( 'field '. $fields[$field_name]['id'] .' option '. $field_key .' title',
						$option['title'], false, 'plugin Types' );
						$auto_fill_default_trans = true;
					}
					$auto_fill_default = str_replace(',', '\,', $auto_fill_default);
				}
			}
			switch (strtolower($auto_fill_sort)) {
                        case 'desc':
                            sort($db_values);
                            $db_values = array_reverse($db_values);
                            break;
                        
                        case 'descnum':
                            sort($db_values, SORT_NUMERIC);
                            $db_values = array_reverse($db_values);
                            break;
                        
                        case 'none':
                            break;
                        
                        case 'ascnum':            
                            sort($db_values, SORT_NUMERIC);
                            break;
            
                        default:            
                            sort($db_values);
                            break;
			}
		} else {
			global $wpdb;
			
			switch ( strtolower( $auto_fill_sort ) ) {
				case 'desc':
				$db_values = $wpdb->get_col("SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = '{$auto_fill}' ORDER BY meta_value DESC" );
				break;
					
				case 'descnum':
				$db_values = $wpdb->get_col("SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = '{$auto_fill}' ORDER BY meta_value + 0 DESC" );
				break;
				
				case 'none':
				$db_values = $wpdb->get_col("SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = '{$auto_fill}'" );
				break;
				
				case 'ascnum':            
				$db_values = $wpdb->get_col("SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = '{$auto_fill}' ORDER BY meta_value + 0 ASC" );
				break;
	
				default:            
				$db_values = $wpdb->get_col("SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = '{$auto_fill}' ORDER BY meta_value ASC" );
				break;
			}
		}
        }
        
        if ($auto_fill_default != '') {
		if ( !$auto_fill_default_trans ) { // translate the auto_fill_default option when it's not one of the existing options
			$auto_fill_default = str_replace('\,', ',', $auto_fill_default);
			$auto_fill_default = wpv_translate( $url_param . '_auto_fill_default', stripslashes($auto_fill_default), false, 'View ' . $view_name );
			$auto_fill_default = str_replace(',', '\,', $auto_fill_default);
		}
            $values = '';
            $display_values = str_replace('\,', '%comma%', $auto_fill_default);
            $first = false;
        } else {
            $values = '';
            $display_values = '';
            $first = true;
        }
        foreach($db_values as $value) {
            if ($value) {
                if (!$first) {
                    $values .= ',';
                    $display_values .= ',';
                }
                $values .= str_replace(',', '%comma%', $value);
                if ( isset( $display_text[$value] ) ) {
			$display_values .= str_replace(',', '%comma%', $display_text[$value]);
                } else {
			$display_values .= str_replace(',', '%comma%', $value);
		}
                $first = false;
                
            }            
        }
    } else if (!empty($display_values)) {
	$display_values_trans = true;
    }
    
	$out = '';
	
	// Use when values attributes are defined (predefined values to list)
	if(!empty($values)) {
		$values_fix = str_replace('\,', '%comma%', $values);
		//print_r($values_fix);
		$values_arr = explode(',', $values_fix);
		$values_arr = str_replace('%comma%', ',', $values_arr);
        if (!empty($display_values)) {
		$display_values = str_replace('\,', '%comma%', $display_values);
		$display_values = explode(',', $display_values);
		$display_values = str_replace('%comma%', ',', $display_values);
		if ($display_values_trans) {
			$translated_values = array();
			foreach ( $display_values as $index => $valuetrans ) {
				$translated_values[$index] = wpv_translate( $url_param . '_display_values_' . ( $index + 1), stripslashes($valuetrans), false, 'View ' . $view_name );
			}
			$display_values = $translated_values;
		}
        }
        
		$options = array();
		
        if(!in_array($type, array('radio', 'radios', 'select', 'checkboxes'))) {
            $type = 'select';
        }
        
        if ($type == 'radio') {
            $type = 'radios';
        }
		
        switch ($type) {
            case 'checkboxes':
                $defaults = array();
                $original_get = null;
                if ( isset( $auto_fill_default ) ) { // check if the defaul value already exists and set the appropriate arrays and values
			$num_auto_fill_default_display = array_count_values($display_values);
			$auto_fill_default_trans = str_replace('\,', ',', $auto_fill_default);
			if ( ( isset( $num_auto_fill_default_display[$auto_fill_default_trans] ) && $num_auto_fill_default_display[$auto_fill_default_trans] > 1) 
				|| 
				in_array( $auto_fill_default_trans, $values_arr ) ) { // if the default value is an existing display value or stored value
			$values_arr_def = array_shift( $values_arr );
			$display_values_def = array_shift( $display_values );
			}
			$defaults = str_replace('\,', '%comma%', $auto_fill_default);
			$defaults = explode( ',', $defaults );
			$defaults = str_replace('%comma%', ',', $defaults);
			$defaults = array_map( 'trim',$defaults );
                }
                if (isset($_GET[$url_param])) {
                    
                    $original_get = $_GET[$url_param];
                    
                    $defaults = $_GET[$url_param];
                    if (is_string($defaults)) $defaults = explode(',',$defaults);
                    unset($_GET[$url_param]);
                }
                for($i = 0; $i < count($values_arr); $i++) {
                    $value = $values_arr[$i];
                    $value = trim($value);
                    
                    // Check for a display value.
                    if (isset($display_values[$i])) {
                        $display_value = $display_values[$i];
                    } else {
                        $display_value = $value;
                    }
                    $options[$value]['#name'] = $url_param . '[]';
                    $options[$value]['#title'] = $display_value;
                    $options[$value]['#value'] = $value;
                    $options[$value]['#default_value'] = in_array($value, $defaults) || in_array($options[$value]['#title'], $defaults); // set default using option titles too
					
//                    $options[$value]['#inline'] = true;
//                    $options[$value]['#after'] = '&nbsp;&nbsp;';
                }

               	$element = wpv_form_control(array('field' => array(
				                '#type' => $type,
				                '#id' => 'wpv_control_' . $type . '_' . $url_param,
				                '#name' => $url_param . '[]',
				                '#attributes' => array('style' => ''),
				                '#inline' => true,
				                '#options' => $options,
								'#before' => '<div class="wpcf-checboxes-group">', //we need to wrap them for js purposes
								'#after' => '</div>'
				                )));
                
                if ($original_get) {
                    $_GET[$url_param] = $original_get;
                }

                break;
            
            default:
                for($i = 0; $i < count($values_arr); $i++) {
                    $value = $values_arr[$i];
                    $value = trim($value);
                    
                    // Check for a display value.
                    if (isset($display_values[$i])) {
                        $display_value = $display_values[$i];
                    } else {
                        $display_value = $value;
                    }
                    $options[$display_value] = $value;
                }

                if ( count( $values_arr ) != count( $options ) ) { // if the $values_arr has one more item than $options, there is a repeating value reset on creation: the existing default
									$default_value = reset($options);
								} else { // so the default value in this case is the first element in $values_arr
									$default_value = $values_arr[0];
								}

								if( $type == 'radios' )
								{
									if( isset($_GET[$url_param]) && in_array( $_GET[$url_param], $options ) ) {
										$default_value = $_GET[$url_param];
									}

									$element = wpv_form_control(array('field' => array(
									'#type' => $type,
									'#id' => 'wpv_control_' . $type . '_' . $url_param,
									'#name' => $url_param,
									'#attributes' => array('style' => ''),
									'#inline' => true,
									'#options' => $options,
									'#default_value' => $default_value,
									'#multiple' => $multi
									)));

								}
								else
								{
									if( isset( $_GET[$url_param] ) )
									{
										if( is_array( $_GET[$url_param] ) )
										{
											if( count( array_intersect($_GET[$url_param], $options) ) > 0 ) {
												$default_value = $_GET[$url_param];
											}
										}
										else
										{
											if( in_array( $_GET[$url_param], $options ) ) {
												$default_value = $_GET[$url_param];
											}
										}
									}
									

									$element = wpv_form_control(array('field' => array(
									'#type' => $type,
									'#id' => 'wpv_control_' . $type . '_' . $url_param,
									'#name' => $url_param . '[]',
									'#attributes' => array('style' => ''),
									'#inline' => true,
									'#options' => $options,
									'#default_value' => $default_value,
									'#multiple' => $multi
									)));

								}
								break;
        }
        
		
         return $element;
	} 
	
	// Use when field attribute is defined
	else if(!empty($field)) {
		// check if Types is active
		if(!function_exists('wpcf_admin_fields_get_field')) {
			if(defined('WPCF_EMBEDDED_ABSPATH')) {
				include WPCF_EMBEDDED_ABSPATH . '/includes/fields.php';
			} else {
				return __('Types plugin is required.', 'wpv-views');			
			}
		}
		if(!function_exists('wpv_form_control')) {
			include  '../common/functions.php';
		}
		
		//This is important cause wpcf_admin_fields_get_field works with id: $field - 'wpcf-' and search with 'wpcf-'.$field
		/*if( strpos($field, 'wpcf-') !== false )
		{
			$tmp = explode('wpcf-', $field);
			$field = $tmp[1];
		}*/
		// get field options
		$field_options = wpcf_admin_fields_get_field($field);
		if(empty($field_options)) {
			return __('Empty field values or incorrect field defined. ', 'wpv-views');
		}
        $field_options['name'] = wpv_translate('field ' . $field_options['id'] . ' name', $field_options['name'], false, 'plugin Types');
			
		// get the type of custom field (radio, checkbox, other)
		$field_type = $field_options['type'];
		
		// override with type
		if(!empty($type)) {
			$field_type = $type;
		}
        
        if (!in_array($field_type, array('radio', 'checkbox', 'checkboxes', 'select', 'textfield', 'date', 'datepicker' ))) {
            $field_type = 'textfield';
        }
        
		// Radio field
		if($field_type == 'radio') {
		//	print_r( $field_options );
			$field_radio_options = $field_options['data']['options'];
			$options = array();
				
			foreach($field_radio_options as $key=>$opts) {
				if(is_array($opts)) {
					if ( isset( $field_options['data']['display'] ) && 'value' == $field_options['data']['display'] && isset( $opts['display_value'] ) ) {
						$options[$opts['display_value']] = $opts['value']; // if we have an actual display value and is set to be used, use it
					} else {  // else, use the field value title and watch out because checkboxes fields need their titles as values
						if (_wpv_is_field_of_type('wpcf-' . $field, 'checkboxes')) {
							$options[wpv_translate( 'field '. $field_options['id'] .' option '. $key .' title', $opts['title'], false, 'plugin Types' )] = $opts['title'];
						} else {
							$options[wpv_translate( 'field '. $field_options['id'] .' option '. $key .' title', $opts['title'], false, 'plugin Types' )] = $opts['value'];
						}
					}
				} 
			}

            
				
			// get the form content
			$element = wpv_form_control(array('field' => array(
                        '#type' => 'radios',
                        '#id' => 'wpv_control_radio_' . $field,
                        '#name' => $url_param,
                        '#attributes' => array('style' => ''),
                        '#inline' => true,
				 		'#options' => $options,
                        '#default_value' => isset($_GET[$url_param]) ? $_GET[$url_param] : null,
                  )));
				
            return $element;
		} else if($field_type == 'checkbox') {
			
            if (isset($atts['title'])) {
                $checkbox_name =  wpv_translate( $url_param . '_title', $title, false, 'View ' . $view_name );
            } else {
                $checkbox_name = wpv_translate( 'field ' . $field_options['name'] . ' name', $field_options['name'], false, 'plugin Types' );
            }

			$element = wpv_form_control(array('field' => array(
                        '#type' => 'checkbox',
                        '#id' => 'wpv_control_checkbox_' . $field,
                        '#name' => $url_param,
                        '#attributes' => array('style' => ''),
                        '#inline' => true,
				 		'#title' => $checkbox_name,
						'#value' => $field_options['data']['set_value'],
						'#default_value' => 0
                 )));
                        
            return $element;
		} else if($field_type == 'checkboxes') {
			
            $defaults = array();
            $original_get = null;
            if (isset($_GET[$url_param])) {

                $original_get = $_GET[$url_param];

                $defaults = $_GET[$url_param];
                if (is_string($defaults)) $defaults = explode(',',$defaults);
                unset($_GET[$url_param]);
            }
            if (isset($field_options['data']['options']['default'])) unset($field_options['data']['options']['default']); // remove the default option from the array
            foreach($field_options['data']['options'] as $key=>$value) {
                $display_value = wpv_translate( 'field '. $field_options['id'] .' option '. $key .' title', trim($value['title']), false, 'plugin Types' );
                if (_wpv_is_field_of_type('wpcf-' . $field, 'checkboxes')) {
			$value = trim($value['title']);
                } else {
			$value = trim($value['value']);
                }
		
                $options[$value]['#name'] = $url_param . '[]';
                $options[$value]['#title'] = $display_value;
                $options[$value]['#value'] = $value;
                $options[$value]['#default_value'] = in_array($value, $defaults);
//                $options[$value]['#inline'] = true;
//                $options[$value]['#after'] = '&nbsp;&nbsp;';
            }

            $element = wpv_form_control(array('field' => array(
                            '#type' => 'checkboxes',
                            '#id' => 'wpv_control_checkbox_' . $field,
                            '#name' => $url_param . '[]',
                            '#attributes' => array('style' => ''),
                            '#inline' => true,
                            '#options' => $options,
                            )));
            
            if ($original_get) {
                $_GET[$url_param] = $original_get;
            }

            
            return $element;
                
		} else if($field_type == 'select') {
			$field_select_options = $field_options['data']['options'];
			$options = array();
				
			foreach($field_select_options as $key=>$opts) {
				if(is_array($opts)) {
					if (_wpv_is_field_of_type('wpcf-' . $field, 'checkboxes')) {
						$options[wpv_translate( 'field '. $field_options['id'] .' option '. $key .' title', $opts['title'], false, 'plugin Types' )] = $opts['title'];
					} else {
						$options[wpv_translate( 'field '. $field_options['id'] .' option '. $key .' title', $opts['title'], false, 'plugin Types' )] = $opts['value'];
					}
				} 
			}
			
			$default_value = false;
			if(isset($_GET[$url_param]) && in_array($_GET[$url_param], $options)) {
				$default_value = $_GET[$url_param];
			}
			
			$element = wpv_form_control(array('field' => array(
	                        '#type' => 'select',
	                        '#id' => 'wpv_control_select_' . $url_param,
	                        '#name' => $url_param,
	                        '#attributes' => array('style' => ''),
	                        '#inline' => true,
							'#options' => $options,
							'#default_value' => $default_value,
	                 )));
	                 
	        return $element;
		}
		else if($field_type == 'textfield') {
			$default_value = '';
			if(isset($_GET[$url_param])) {
				$default_value = stripslashes( urldecode( sanitize_text_field( $_GET[$url_param] ) ) );
			}
			
			$element = wpv_form_control(array('field' => array(
	                        '#type' => 'textfield',
	                        '#id' => 'wpv_control_textfield_' . $url_param,
	                        '#name' => $url_param,
	                        '#attributes' => array('style' => ''),
	                        '#inline' => true,
							'#value' => $default_value,
	                 )));
	                 
	        return $element;
		}
		else if($field_type == 'date' || $field_type == 'datepicker') {
     
             $out = wpv_render_datepicker($url_param, $date_format, $default_date);
            return $out;
        }
			
		return ''; 
	} else {
        // type parameter without values
        
        $default_value = '';
        if(isset($_GET[$url_param])) {
            $default_value = $_GET[$url_param];
        }
        
        switch ($type) {
            case 'checkbox':
                $element = array('field' => array(
                                '#type' => $type,
                                '#id' => 'wpv_control_' . $type . '_' . $url_param,
                                '#name' => $url_param,
                                '#attributes' => array('style' => ''),
                                '#inline' => true,
                                '#value' => $default_value,
                                ));
                
                $element['field']['#title'] = wpv_translate( $url_param . '_title', $title, false, 'View ' . $view_name );
                    
                $element = wpv_form_control($element);
                
                break;
            
            case 'datepicker':
                $element = wpv_render_datepicker($url_param, $date_format, $default_date);
                break;

            default:
                
                $element = array('field' => array(
                                '#type' => $type,
                                '#id' => 'wpv_control_' . $type . '_' . $url_param,
                                '#name' => $url_param,
                                '#attributes' => array('style' => ''),
                                '#inline' => true,
                                '#value' => $default_value,
                                ));
                $element = wpv_form_control($element);
                break;
        }

        return $element;
    }
}

function _wpv_is_field_of_type($field_name, $type) {
    $opt = get_option('wpcf-fields');
    if($opt && strpos($field_name, 'wpcf-') === 0) {
        $field_name = substr($field_name,5);
        if (isset($opt[$field_name]['type'])) {
            $field_type = strtolower($opt[$field_name]['type']);
            if ( $field_type == $type) {
                return true;
            }
        }
        
    }
    
    return false;
}

function wpv_add_front_end_js() {
    ?>
        <script type="text/javascript">
            var front_ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            var wpv_calendar_image = '<?php echo WPV_URL_EMBEDDED; ?>/res/img/calendar.gif';
            var wpv_calendar_text = '<?php echo esc_js(__('Select date', 'wpv-views')); ?>';
        </script>
    
    <?php
    
}

function wpv_render_datepicker($url_param, $date_format, $default_date = 'NOW()') {
    
    static $support_loaded = false;
	$display_date = $datepicker_date = '';
    if (!$support_loaded) {
        ?>
            <script type="text/javascript">
                jQuery(document).ready(function () {
                    jQuery('head').append('<link rel="stylesheet" href="<?php echo WPV_URL_EMBEDDED . '/res/css/datepicker.css';?>" type="text/css" />');
                });
            </script>
        <?php
        $support_loaded = true;
    }
    
    if ($date_format == '') {
        $date_format = get_option('date_format');
    }
    
    if(isset($_GET[$url_param]) && $_GET[$url_param] != '' && $_GET[$url_param] != '0') {
        $date = (int) $_GET[$url_param];
    } else {
        //$date = time();
		if ( $default_date == '' ){ //If default date not set, date = now()
			$date = wpv_filter_parse_date('NOW()');
		}
		elseif ( $default_date == 'NONE' ){ // Empty Date
			$date = '';
		}
		else{
			$date = wpv_filter_parse_date($default_date);
		}
    }
	//if ( $default_date != 'NONE' ){
	if ( $date != '' ) {
    	$display_date = date_i18n($date_format, intval($date));
	}

    
    $out = '';
    $out .= '<span class="wpv_date_input js-wpv-date-param-' . $url_param . ' js-wpv-date-display" data-param="' . $url_param . '">' . $display_date . '</span> ';
    $out .= '<input type="hidden" class="js-wpv-date-param-' . $url_param . '-value" name="' . $url_param . '" value="' . $date . '" />';
    $out .= '<input type="hidden" class="js-wpv-date-param-' . $url_param . '-format" name="' . $url_param . '-format" value="' . $date_format . '" />';
	//if ( $default_date != 'NONE' ){
	if ( $date != '' ) {
    	$datepicker_date = date('dmy', intval($date));
	}
    $out .= '<input type="hidden" data-param="' . $url_param . '" class="wpv-date-front-end js-wpv-date-front-end-' . $url_param . '" value="' . $datepicker_date . '"/>';

    return $out;       
}

class Walker_Category_select extends Walker {
	var $tree_type = 'category';
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id'); //TODO: decouple this

    function __construct($selected_id, $slug_mode = false, $format = false){
		$this->selected = $selected_id;
        $this->slug_mode = $slug_mode;
        $this->format = $format;
	}
	
	function start_lvl(&$output, $depth = 0, $args = array()) {
	}

	function end_lvl(&$output, $depth = 0, $args = array()) {
	}

	function start_el(&$output, $category, $depth = 0, $args = array(), $current_object_id = 0) {
		extract($args);
		$selected = '';
		
		$indent = str_repeat('-', $depth);
		if ($indent != '') {
			$indent = '&nbsp;' . str_repeat('&nbsp;', $depth) . $indent;
		}
		
		$tax_option = $category->name;
		if ($this->format) $tax_option = str_replace(array('%%NAME%%', '%%COUNT%%'), array($category->name, $category->count), $this->format);
		
		
		if ($this->slug_mode) {
			if( is_array( $this->selected  ) )
			{
				foreach( $this->selected as $sel )
				{
					$selected .= $sel == $category->slug ? ' selected="selected"' : '';
				}
			}
			else
			{
				$selected .= $this->selected == $category->slug ? ' selected="selected"' : '';
			}
		
			$output .= '<option value="' . $category->slug. '"' . $selected . '>' . $indent . $tax_option . "</option>\n";
		} else {
			if ( is_array( $this->selected ) )
			{
				foreach( $this->selected as $sel )
				{
					$selected .= $sel == $category->name ? ' selected="selected"' : '';
				}
			}
			else
			{
				$selected .= $this->selected == $category->name ? ' selected="selected"' : '';
			}
			
			$output .= '<option value="' . $category->name. '"' . $selected . '>' . $indent . $tax_option . "</option>\n";
		}
	}

	function end_el(&$output, $category, $depth = 0, $args = array()) {
	}
}

class Walker_Category_radios extends Walker {
	var $tree_type = 'category';
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id'); //TODO: decouple this

    function __construct($selected_id, $slug_mode = false, $format = false){
		$this->selected = $selected_id;
        $this->slug_mode = $slug_mode;
        $this->format = $format;
	}
	
	function start_lvl(&$output, $depth = 0, $args = array()) {
	}

	function end_lvl(&$output, $depth = 0, $args = array()) {
	}

	function start_el(&$output, $category, $depth = 0, $args = array(), $current_object_id = 0) {
		extract($args);
		$selected = '';
		
		if ( empty($taxonomy) )
            $taxonomy = 'category';
 
        if ( $taxonomy == 'category' )
            $name = 'post_category';
        else
            $name = $taxonomy;
		
		$indent = str_repeat('-', $depth);
		if ($indent != '') {
			$indent = '&nbsp;' . str_repeat('&nbsp;', $depth) . $indent;
		}
		
		$tax_option = $category->name;
		if ($this->format) $tax_option = str_replace(array('%%NAME%%', '%%COUNT%%'), array($category->name, $category->count), $this->format);
		
		
        if ($this->slug_mode) {
	
			$tmp = is_array( $this->selected ) ? $this->selected[0] : $this->selected;
			
			$selected .= $tmp == $category->slug ? ' checked' : '';
             
    		$output .= '<input id="' . $name . '-'. $category->slug . '" name="'.$name.'" type="radio" value="' . $category->slug. '"' . $selected . '/><label for="' . $name . '-'. $category->slug . '" class="radios-taxonomies-title">' . $indent . $tax_option . '</label>' . "\n";

        } else {
	//		foreach( $this->selected as $sel )
	//		{
        //	$selected .= $sel == $category->name ? ' checked"' : '';
	//		}
			
			$tmp = is_array( $this->selected ) ? $this->selected[0] : $this->selected;
			
			$selected .= $tmp == $category->name ? ' checked' : '';
			
    		$output .= '<input id="' . $name . '-'. $category->slug . '" name="'.$name.'" type="radio" value="' . $category->name. '"' . $selected . '/><label for="' . $name . '-'. $category->slug . '" class="radios-taxonomies-title">' . $indent . $tax_option . '</label>' . "\n";
        }
	}

	function end_el(&$output, $category, $depth = 0, $args = array()) {
	}
}

class Walker_Category_id_select extends Walker {
	var $tree_type = 'category';
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id'); //TODO: decouple this

    function __construct($selected_id){
		$this->selected = $selected_id;
	}
	
	function start_lvl(&$output, $depth = 0, $args = array()) {
	}

	function end_lvl(&$output, $depth = 0, $args = array()) {
	}

	function start_el(&$output, $category, $depth = 0, $args = array(), $current_object_id = 0) {
		extract($args);
		
		$indent = str_repeat('-', $depth);
		if ($indent != '') {
			$indent = '&nbsp;' . str_repeat('&nbsp;', $depth) . $indent;
		}
		
        $selected = $this->selected == $category->term_id ? ' selected="selected"' : '';
    		$output .= '<option value="' . $category->term_id. '"' . $selected . '>' . $indent . $category->name . "</option>\n";
	}

	function end_el(&$output, $category, $depth = 0, $args = array()) {
	}
}

if(!class_exists('WPV_Walker_Category_Checklist')){
    
	// We need to include the taxonomy checkboxes as there not
	// available in the front end.

	class WPV_Walker_Category_Checklist extends Walker {
		var $tree_type = 'category';
		var $db_fields = array ('parent' => 'parent', 'id' => 'term_id'); //TODO: decouple this
							
		function __construct($slug_mode = false, $format = false) {
			$this->slug_mode = $slug_mode;
			$this->format = $format;
		}
		
		function start_lvl(&$output, $depth = 0, $args = array() ) {
			$indent = str_repeat("\t", $depth);
			$output .= "$indent<ul class='children'>\n";
					//$output .= "$indent\n";
		}
		
		function end_lvl(&$output, $depth = 0, $args = array() ) {
			$indent = str_repeat("\t", $depth);
			$output .= "$indent</ul>\n";
					//$output .= "$indent\n";
		}
		
		function start_el(&$output, $category, $depth = 0, $args = array(), $current_object_id = 0 ) {
			extract($args);
			if ( empty($taxonomy) )
				$taxonomy = 'category';
		
			if ( $taxonomy == 'category' )
				$name = 'post_category';
			else
				$name = $taxonomy;
		
			$class = in_array( $category->term_id, $popular_cats ) ? ' class="popular-category"' : '';
			$tax_option = esc_html( apply_filters('the_category', $category->name ));
			if ($this->format) $tax_option = str_replace(array('%%NAME%%', '%%COUNT%%'), array($category->name, $category->count), $this->format);
					// NOTE: were outputing the "slug" and not the "term-id".
					// WP outputs the "term-id"
					if ($this->slug_mode) {
				$output .= "\n<li id='{$taxonomy}-{$category->term_id}'$class>" . '<label class="selectit"><input value="' . $category->slug . '" type="checkbox" name="'.$name.'[]" id="in-'.$taxonomy.'-' . $category->term_id . '"' . checked( in_array( $category->slug, $selected_cats ), true, false ) . disabled( empty( $args['disabled'] ), false, false ) . ' /> ' . $tax_option . '</label>';
			} else {
				$output .= "\n<li id='{$taxonomy}-{$category->term_id}'$class>" . '<label class="selectit"><input value="' . $category->name . '" type="checkbox" name="'.$name.'[]" id="in-'.$taxonomy.'-' . $category->term_id . '"' . checked( in_array( $category->name, $selected_cats ), true, false ) . disabled( empty( $args['disabled'] ), false, false ) . ' /> ' . $tax_option . '</label>';
			}
		}
		
		function end_el(&$output, $category, $depth = 0, $args = array()) {
			$output .= "</li>\n";
					//$output .= "\n";
		}
	}
}


function _wpv_render_taxonomy_control($taxonomy, $type, $url_param, $default_label, $taxonomy_orderby, $taxonomy_order, $format, $hide_empty) {

    // We need to know what attribute url format are we using
    // to make the control filter use values of names or slugs for values.
    // If using names, $url_format=false and if using slugs, $url_format=true
	//printf('$taxonomy %s, $type %s, $url_param %s, $default_label %s, $taxonomy_orderby %s, $taxonomy_order %s, $format %s', $taxonomy, $type, $url_param, $default_label, $taxonomy_orderby, $taxonomy_order, $format);
    global $WP_Views;
    $aux_array = $WP_Views->view_used_ids;
    $view_settings = $WP_Views->get_view_settings(end($aux_array));
    $url_format = false;
    
    if ( !taxonomy_exists( $taxonomy ) ) {
		return;
    }
    
    if (isset($view_settings['taxonomy-'. $taxonomy .'-attribute-url-format']) && 'slug' == $view_settings['taxonomy-'.$taxonomy . '-attribute-url-format'][0]) $url_format = true;
    
    $terms = array();
    $get_value = ( $hide_empty == 'true' ) ? '' : 'all';
    
    if (isset($_GET[$url_param])) {
        if (is_array($_GET[$url_param])) {
            $terms = $_GET[$url_param];
        } else {
            // support csv terms
            $terms = explode(',', $_GET[$url_param]);
        }
    }    

    ob_start();
    ?>
        

		<?php
            if ($type == 'select' || $type == 'multi-select' ) {
				
                $name = $taxonomy;
                if ($name == 'category') {
                    $name = 'post_category';
                }
        		
				if( $type == 'select' )
				{
					echo '<select name="' . $name . '">';
	    			echo "<option selected='selected' value='0'>$default_label</option>"; // set the label for the default option
				}
				else if( $type == 'multi-select' )
				{
					
					echo '<select name="' . $name . '[]" multiple >';
				}
				
                $temp_slug = '0';
                if (count($terms)) {
                    $temp_slug = $terms;
                }
        		$my_walker = new Walker_Category_select($temp_slug, $url_format, $format);
        		
                wpv_terms_checklist(0, array('taxonomy' => $taxonomy, 'selected_cats' => $terms, 'walker' => $my_walker, 'taxonomy_orderby' => $taxonomy_orderby, 'taxonomy_order' => $taxonomy_order, 'get_value' => $get_value));
                echo '</select>';
            } 
			elseif ($type == 'radios' || $type == 'radio' ) {

			    $name = $taxonomy;
			    if ($name == 'category') {
			        $name = 'post_category';
			    }
				
			    $temp_slug = '0';
			    if (count($terms)) {
			        $temp_slug = $terms;
			    }
				$my_walker = new Walker_Category_radios($temp_slug, $url_format, $format);

			    wpv_terms_checklist(0, array('taxonomy' => $taxonomy, 'selected_cats' => $terms, 'walker' => $my_walker, 'taxonomy_orderby' => $taxonomy_orderby, 'taxonomy_order' => $taxonomy_order, 'get_value' => $get_value));
			} else {
				echo '<ul class="categorychecklist form-no-clear">';
			    wpv_terms_checklist(0, array('taxonomy' => $taxonomy, 'selected_cats' => $terms, 'url_format' => $url_format, 'format' => $format, 'taxonomy_orderby' => $taxonomy_orderby, 'taxonomy_order' => $taxonomy_order, 'get_value' => $get_value));
				echo '</ul>';
			}
            
        ?>
		
    <?php
    
    $taxonomy_check_list = ob_get_clean();
    
    if ($taxonomy == 'category') {
        $taxonomy_check_list = str_replace('name="post_category', 'name="' . $url_param, $taxonomy_check_list);
    } else {
        $taxonomy_check_list = str_replace('name="' . $taxonomy, 'name="' . $url_param, $taxonomy_check_list);
    }
    
    return $taxonomy_check_list;
    
}

/**
* Taxonomy independent version of wp_category_checklist
*
* @since 3.0.0
*
* @param int $post_id
* @param array $args
*/
if ( !function_exists( 'wpv_terms_checklist' ) ) {
	function wpv_terms_checklist( $post_id = 0, $args = array() ) {
		$defaults = array(
			'descendants_and_self' => 0,
			'selected_cats' => false,
			'popular_cats' => false,
			'walker' => null,
			'url_format' => false,
			'format' => false,
			'taxonomy' => 'category',
			'taxonomy_orderby' => 'name',
			'taxonomy_order' => 'ASC',
			'checked_ontop' => false,
			'get_value' => 'all'
		);
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
		
		if ( empty( $walker ) || !is_a( $walker, 'Walker' ) )
			$walker = new WPV_Walker_Category_Checklist( $url_format, $format );
		
		if ( !in_array( $taxonomy_orderby, array( 'id', 'count', 'name', 'slug', 'term_group', 'none' ) ) ) $taxonomy_orderby = 'name';
		if ( !in_array( $taxonomy_order, array( 'ASC', 'DESC' ) ) ) $taxonomy_order = 'ASC';
		
		$descendants_and_self = (int) $descendants_and_self;
		
		$args = array( 'taxonomy' => $taxonomy );
		
		$tax = get_taxonomy( $taxonomy );
		$args['disabled'] = false;
		
		if ( is_array( $selected_cats ) )
			$args['selected_cats'] = $selected_cats;
		elseif ( $post_id )
			$args['selected_cats'] = wp_get_object_terms( $post_id, $taxonomy, array_merge( $args, array( 'fields' => 'ids' ) ) );
		else
			$args['selected_cats'] = array();
		
		if ( is_array( $popular_cats ) )
			$args['popular_cats'] = $popular_cats;
		else
			$args['popular_cats'] = get_terms( $taxonomy, array( 'fields' => 'ids', 'orderby' => 'count', 'order' => 'DESC', 'number' => 10, 'hierarchical' => false ) );
		
		if ( $descendants_and_self ) {
			$categories = (array) get_terms( $taxonomy, array( 'child_of' => $descendants_and_self, 'hierarchical' => 0, 'hide_empty' => 1 ) );
			$self = get_term( $descendants_and_self, $taxonomy );
			array_unshift( $categories, $self );
		} else {
			$categories = (array) get_terms( $taxonomy, array('get' => $get_value, 'orderby' => $taxonomy_orderby, 'order' => $taxonomy_order ) );
		}
		
		if ( $checked_ontop ) {
			// Post process $categories rather than adding an exclude to the get_terms() query to keep the query the same across all posts (for any query cache)
			$checked_categories = array();
			$keys = array_keys( $categories );
		
			foreach( $keys as $k ) {
				if ( in_array( $categories[$k]->term_id, $args['selected_cats'] ) ) {
					$checked_categories[] = $categories[$k];
					unset( $categories[$k] );
				}
			}
		
			// Put checked cats on top
			echo call_user_func_array( array( &$walker, 'walk' ), array( $checked_categories, 0, $args ) );
		}
		// Then the rest of them
		echo call_user_func_array( array( &$walker, 'walk' ), array( $categories, 0, $args ) );
	}
}

add_shortcode('wpv-control', 'wpv_shortcode_wpv_control');

//not in use anymore - leave it for retro-compatibility
add_shortcode('wpv-filter-controls', 'wpv_shortcode_wpv_filter_controls');
function wpv_shortcode_wpv_filter_controls($atts, $value) {
    
    /**
     *
     * This is a do nothing shortcode. It's just a place holder for putting the
     * wpv-control shortcodes and allows for easier editing inside the meta HTML
     *
     * This shortcode now has a function: when hide="true"
     * it does not display the wpv-control shortcodes
     * This is usefull if you need to show pagination controls but not filter controls
     * For View Forms, this hide parameter is overriden and controls are always shown
     */
    
    $value = str_replace("<!-- ADD USER CONTROLS HERE -->", '', $value);
    
	if (isset($atts['hide']) && $atts['hide'] == 'true') {
		return '';
        } else {
		return wpv_do_shortcode($value);
        }
    
}
