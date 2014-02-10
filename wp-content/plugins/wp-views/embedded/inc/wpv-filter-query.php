<?php

/**
 * Create the query to return the posts based on the settings
 * in the views query meta box.
 *
 */

function wpv_filter_get_posts($id) {
    global $WP_Views, $post, $wplogger,$WPVDebug, $wpdb;

    $view_settings_defaults = array(
        'post_type'         => 'any',
        'orderby'           => 'post-date',
        'order'             => 'DESC',
        'paged'             => '1',
        'posts_per_page'    =>  -1
    );
    extract($view_settings_defaults);
    $view_settings = $WP_Views->get_view_settings($id);
	$view_settings['view_id'] = $id;
    extract($view_settings, EXTR_OVERWRITE);

    if (isset($_GET['wpv_paged']) && isset($_GET['wpv_view_count']) && esc_attr($_GET['wpv_view_count']) == $WP_Views->get_view_count()) {
        $paged = intval(esc_attr($_GET['wpv_paged']));
    }

    $query = array(
            'posts_per_page'    => $posts_per_page,
            'paged'             => $paged,
            'post_type'         => $post_type,
            'order'             => $order,
            'suppress_filters'  => false,
			'ignore_sticky_posts' => true
    );

    if (isset($view_settings['pagination'][0]) && $view_settings['pagination'][0] == 'disable'
    // && isset($view_settings['pagination']['mode']) && $view_settings['pagination']['mode'] == 'paged'
    ) {
        // Show all the posts if pagination is disabled.
        $query['posts_per_page'] = -1;
    }
    if (isset($view_settings['pagination']['mode']) && $view_settings['pagination']['mode'] == 'rollover') {
        $query['posts_per_page'] = $view_settings['rollover']['posts_per_page'];
    }

	// Add special check for media (attachments) as their default status in not usually published
	if (sizeof($post_type) == 1 && $post_type[0] == 'attachment') {
		$query['post_status'] = 'any'; // Note this can be overriden by adding a status filter.
	}

	$WPVDebug->add_log( 'info' , apply_filters('wpv-view-get-content-summary', '', $WP_Views->current_view, $view_settings) , 'short_query' );

	$WPVDebug->add_log( 'info' , "Basic query arguments\n". print_r($query, true) , 'query_args' );

	/**
	* Filter wpv_filter_query
	*
	* This is where all the filters coming from the View settings to modify the query are hooked
	*
	* @param $query the Query arguments as in WP_Query
	* @param $view_settings the View settings
	* @param $id the ID of the View being displayed
	*
	* @return $query
	*
	* @since unknown
	*/

    $query = apply_filters( 'wpv_filter_query', $query, $view_settings, $id );

    $WPVDebug->add_log( 'filters' , "wpv_filter_query\n" . print_r($query, true) , 'filters', 'Filter arguments before the query using <strong>wpv_filter_query</strong>' );

    $post_query = new WP_Query($query);

	$WPVDebug->add_log( 'mysql_query' , $post_query->request , 'posts' , '' , true );

	$WPVDebug->add_log( 'info' , print_r($post_query, true) , 'query_results' , '' , true );

	$wplogger->log($post_query->query, WPLOG_DEBUG);
	$wplogger->log($post_query->request, WPLOG_DEBUG);

	/**
	* Filter wpv_filter_query_post_process
	*
	* This is applied to the results of the main query.
	*
	* @param $post_query the queried object returned by the WordPress WP_Query()
	* @param $view_settings the View settings
	* @param $id the ID of the View being displayed
	*
	* @return $post_query
	*
	* @since unknown
	*/

    $post_query = apply_filters( 'wpv_filter_query_post_process', $post_query, $view_settings, $id );

    $WPVDebug->add_log( 'filters' , "wpv_filter_query_post_process\n" . print_r($post_query, true) , 'filters', 'Filter the returned query using <strong>wpv_filter_query_post_process</strong>' );

    return $post_query;
}

add_filter('wpv_filter_query', 'wpv_filter_query_compatibility', 99,2);

function wpv_filter_query_compatibility($query, $view_settings) {

	// Relevanssi compatibility
	if ( isset($view_settings['search_mode'] ) && function_exists( 'relevanssi_prevent_default_request' ) ) {
		remove_filter('posts_request', 'relevanssi_prevent_default_request', 10, 2 );
	}

	return $query;
}

add_filter('wpv_filter_query_post_process', 'wpv_filter_query_post_proccess_compatibility', 99, 2);

function wpv_filter_query_post_proccess_compatibility($post_query, $view_settings ) {

	// Relevanssi compatibility
	if ( isset($view_settings['search_mode'] ) && function_exists( 'relevanssi_prevent_default_request' ) ) {
		add_filter('posts_request', 'relevanssi_prevent_default_request', 10, 2 );
	}

	return $post_query;
}
