<?php


if( !is_admin()){
		wp_deregister_script('jquery');
		wp_register_script('jquery', ("//ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"), false, '1.9.0', true);
		wp_enqueue_script('jquery');
	}
	
	add_action('init', 'modify_jquery');


function starkers_script_enqueuer() {
		
		wp_register_script('jqueryui', '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.0/jquery-ui.min.js', false, false, true);
		wp_enqueue_script('jqueryui');
		
		wp_register_script( 'application', get_stylesheet_directory_uri().'/js/application.js', array( 'jquery' ), false, true );
		wp_enqueue_script( 'application' );
		
		wp_register_script( 'behavior-fitText', get_stylesheet_directory_uri().'/js/behaviors/fitText.js', array( 'jquery' ), false, true );
		wp_enqueue_script( 'behavior-fitText' );
		
		wp_register_script( 'behavior-pageSize', get_stylesheet_directory_uri().'/js/behaviors/pageSize.js', array( 'jquery' ), false, true );
		wp_enqueue_script( 'behavior-pageSize' );
		
		wp_register_script( 'behavior-setWidths', get_stylesheet_directory_uri().'/js/behaviors/setWidths.js', array( 'jquery' ), false, true );
		wp_enqueue_script( 'behavior-setWidths' );
		
		wp_register_script( 'site', get_stylesheet_directory_uri().'/js/behaviors/script.js', array( 'jquery' ), false, true );
		wp_enqueue_script( 'site' );
		
		wp_register_script( 'setWidths', get_stylesheet_directory_uri().'/js/plugins/setWidths.js', array( 'jquery' ), false, true );
		wp_enqueue_script( 'setWidths' );
		
		wp_register_script( 'pageSize', get_stylesheet_directory_uri().'/js/plugins/pageSize.js', array( 'jquery' ), false, true );
		wp_enqueue_script( 'pageSize' );
		
		wp_register_script( 'scrollto', get_stylesheet_directory_uri().'/js/plugins/scrollto.js', array( 'jquery' ), false, true );
		wp_enqueue_script( 'scrollto' );
		
		wp_register_script( 'modernizr', get_stylesheet_directory_uri().'/js/vendors/modernizr-2.0.6.min.js', array( 'jquery' ), '2.0.6' );
		wp_enqueue_script( 'modernizr' );
		
		wp_register_script( 'fittext', get_stylesheet_directory_uri().'/js/vendors/jquery.fittext.js', array( 'jquery' ), false, true );
		wp_enqueue_script( 'fittext' );
		
		wp_register_script( 'inview', get_template_directory_uri().'/js/vendors/jquery.inview.min.js', array( 'jquery' ), false, true );
		wp_enqueue_script( 'inview' );

        wp_register_script( 'waypoints', get_stylesheet_directory_uri().'/js/vendors/waypoints.min.js', array( 'jquery' ), false, true );
    	wp_enqueue_script( 'waypoints' );
		
		wp_register_style( 'fonts', get_stylesheet_directory_uri().'/css/fonts/stylesheet.css', '', '', 'screen' );
		wp_enqueue_style( 'fonts' );
		
		wp_register_style( 'screen', get_stylesheet_directory_uri().'/css/screen.css', '', '', 'screen' );
        wp_enqueue_style( 'screen' );
	}
	
function wp_nav_menu_select_sort( $a, $b ) {
    return $a = $b;
}
	
function wp_nav_menu_select( $args = array() ) {
     
    $defaults = array(
        'theme_location' => '',
        'menu_class' => 'select-menu',
    );
     
    $args = wp_parse_args( $args, $defaults );
      
    if ( ( $menu_locations = get_nav_menu_locations() ) && isset( $menu_locations[ $args['theme_location'] ] ) ) {
        $menu = wp_get_nav_menu_object( $menu_locations[ $args['theme_location'] ] );
          
        $menu_items = wp_get_nav_menu_items( $menu->term_id );
         
        $children = array();
        $parents = array();
         
        foreach ( $menu_items as $id => $data ) {
            if ( empty( $data->menu_item_parent )  ) {
                $top_level[$data->ID] = $data;
            } else {
                $children[$data->menu_item_parent][$data->ID] = $data;
            }
        }
         
        foreach ( $top_level as $id => $data ) {
            foreach ( $children as $parent => $items ) {
                if ( $id == $parent  ) {
                    $menu_item[$id] = array(
                        'parent' => true,
                        'item' => $data,
                        'children' => $items,
                    );
                    $parents[] = $parent;
                }
            }
        }
         
        foreach ( $top_level as $id => $data ) {
            if ( ! in_array( $id, $parents ) ) {
                $menu_item[$id] = array(
                    'parent' => false,
                    'item' => $data,
                );
            }
        }
         
        uksort( $menu_item, 'wp_nav_menu_select_sort' ); 
         
        ?>
            <select id="mobile-menu-<?php echo $args['theme_location'] ?>" class="<?php echo $args['menu_class'] ?>">
                <option value=""><?php _e( 'Navigation' ); ?></option>
                <?php foreach ( $menu_item as $id => $data ) : ?>
                    <?php if ( $data['parent'] == true ) : ?>
                        <optgroup label="<?php echo $data['item']->title ?>">
                            <option value="<?php echo $data['item']->url ?>"><?php echo $data['item']->title ?></option>
                            <?php foreach ( $data['children'] as $id => $child ) : ?>
                                <option value="<?php echo $child->url ?>"><?php echo $child->title ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php else : ?>
                        <option value="<?php echo $data['item']->url ?>"><?php echo $data['item']->title ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        <?php
    } else {
        ?>
            <select class="menu-not-found">
                <option value=""><?php _e( 'Menu Not Found' ); ?></option>
            </select>
        <?php
    }
}

?>