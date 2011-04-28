<?php

// Install CollabPress
register_activation_hook( __FILE__, 'cp_install' );

// CollabPress Admin Init Functions
require_once( 'admin_init.php' );

// CollabPress Functions
require_once( 'functions.php' );

// Administration Menus
require_once( 'menus.php' );

// CollabPress shortcode support
require_once( 'shortcode.php' );

// CollabPress widgets
require_once( 'cp-widgets.php' );

// Add "View CollabPress Dashboard" link on plugins page
add_filter( 'plugin_action_links_' . CP_BASENAME, 'filter_plugin_actions' );

function filter_plugin_actions ( $links ) { 
	$settings_link = '<a href="'.CP_DASHBOARD.'">'.__('View Dashboard', 'collabpress').'</a>'; 
	array_unshift ( $links, $settings_link ); 
	return $links;
}

// Show Dashboard Meta Box
add_action( 'wp_dashboard_setup', 'cp_wp_add_dashboard_widgets' );
function cp_wp_add_dashboard_widgets() {

    //check if dashboard widget is enabled
    $options = get_option('cp_options');
    if ( $options['dashboard_meta_box'] == 'enabled' ) {
	wp_add_dashboard_widget('cp_wp_dashboard_widget', __('CollabPress - Recent Activity', 'collabpress'), 'cp_wp_dashboard_widget_function');
    }
    
}
function cp_wp_dashboard_widget_function() {
	cp_recent_activity();
}