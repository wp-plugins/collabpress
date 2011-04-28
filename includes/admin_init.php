<?php

// Initialize CollabPress settings
add_action( 'admin_init', 'cp_admin_init' );
function cp_admin_init() {
	// Register CollabPress options
	register_setting( 'cp_options_group', 'cp_options' );
}

// Add Translation
add_action( 'init', 'cp_translation' );
function cp_translation() {
	load_plugin_textdomain( 'collabpress', false, basename( dirname( dirname( __FILE__ ) ) ) . '/languages' );
}

// Frontend Init
add_action( 'init', 'cp_frontend_init' );
function cp_frontend_init() {
	if ( !is_admin() ) :
		// Register Styles
		if ( is_ssl() )
			wp_register_style('jquery-ui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.7.3/themes/base/jquery-ui.css');
		else
			wp_register_style('jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.3/themes/base/jquery-ui.css');

		// Register Scripts
		wp_register_script('cp_frontend', CP_PLUGIN_URL . 'includes/js/frontend.js', array('jquery'));
		if ( is_ssl() )
			wp_register_script('jquery-ui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.7.3/jquery-ui.min.js', array('jquery'));
		else
			wp_register_script('jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.3/jquery-ui.min.js', array('jquery'));
	endif;
}

// Print Styles
add_action( 'wp_print_styles', 'collabpress_frontend_styles' );
function collabpress_frontend_styles() {
	wp_enqueue_style('jquery-ui');
}

// Print Scripts
add_action( 'wp_print_scripts', 'collabpress_frontend_scripts' );
function collabpress_frontend_scripts() {
	wp_enqueue_script('jquery-ui');
	wp_enqueue_script('cp_frontend');
}

// CollabPress Init
add_action( 'init', 'collabpress_init' );
function collabpress_init() {

	// Load plugin options
	$cp_options = get_option( 'cp_options' );

	// Check if debug mode is enabled
	$cp_debug_mode = ( $cp_options['debug_mode'] == 'enabled' ) ? true : false;

	// Custom Post Types

	// Projects
	$args_projects = array('label' => 'Projects',
								'description' => 'Custom Post Type for CollabPress Projects',
								'public' => $cp_debug_mode,
								'supports' => array('title','author','thumbnail','comments','custom-fields'),
								'exclude_from_search' => true
								);
	// Register Projects Custom Post Type
	register_post_type( 'cp-projects', $args_projects );

	// Task Lists
	$args_task_lists = array('label' => 'Task Lists',
								'description' => 'Custom Post Type for CollabPress Task Lists',
								'public' => $cp_debug_mode,
								'supports' => array('title','author','thumbnail','comments','custom-fields'),
								'exclude_from_search' => true
								);
	// Register Projects Custom Post Type
	register_post_type( 'cp-task-lists', $args_task_lists );

	// Tasks
	$args_tasks = array('label' => 'Tasks',
							'description' => 'Custom Post Type for CollabPress Tasks',
							'public' => $cp_debug_mode,
							'supports' => array('title','author','thumbnail','comments','custom-fields'),
							'exclude_from_search' => true
							);
	// Register Projects Custom Post Type
	register_post_type( 'cp-tasks', $args_tasks );
	
	// Meta Data
	$args_tasks = array('label' => 'Meta Data',
							'description' => 'Custom Post Type for CollabPress Meta Data',
							'public' => $cp_debug_mode,
							'supports' => array('title','author','thumbnail','comments','custom-fields'),
							'exclude_from_search' => true
							);
	// Register CollabPress Meta Data
	register_post_type( 'cp-meta-data', $args_tasks );
    
}