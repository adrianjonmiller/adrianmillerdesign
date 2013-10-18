
<header>
	<?php wp_nav_menu(array(
	    'container'=> 'nav',
	    'menu_id' =>'menu',
	    'menu_class' =>'',
	    'theme_location' => 'primary'
	)); ?>
	
	<?php 
		wp_nav_menu_select(
		    array(
		        'theme_location' => 'primary',
		        'menu_class' => 'select-menu'
		    )
		);
	?>
</header>