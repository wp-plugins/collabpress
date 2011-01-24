<?php

// Add Project
if ( isset( $_POST['cp-add-project'] ) && isset($_POST['cp-project']) ) :

	check_admin_referer('cp-add-project');

	$add_project = array(
					'post_title' => esc_html($_POST['cp-project']),
					'post_status' => 'publish',
					'post_type' => 'cp-projects'
					);
	$project_id = wp_insert_post( $add_project );
	
	update_post_meta( $project_id, '_cp-project-description', esc_html($_POST['cp-project-description']) );
	
	// Add Activity
	cp_add_activity(__('added', 'collabpress'), __('project', 'collabpress'), $current_user->ID, $project_id);

endif;

// Edit Project
if ( isset( $_POST['cp-edit-project'] ) && $_POST['cp-edit-project-id'] ) :

	check_admin_referer('cp-edit-project');

	//verify user has permission to edit projects
	if ( cp_check_permissions( 'settings_user_role' ) ) {

	    // The ID
	    $projectID =  absint( $_POST['cp-edit-project-id'] );

	    $project = array();
	    $project['ID'] = $projectID;
	    $project['post_title'] = esc_html( $_POST['cp-project'] );
	    wp_update_post( $project );
	    update_post_meta( $projectID, '_cp-project-description', esc_html( $_POST['cp-project-description'] ) );

	    // Add Activity
	    cp_add_activity(__('updated', 'collabpress'), __('project', 'collabpress'), $current_user->ID, $projectID);

	}

endif;

// Delete Project
if ( isset( $_GET['cp-delete-project-id'] ) ) :

    check_admin_referer( 'cp-action-delete_project' );

    //verify user has permission to delete projects
    if ( cp_check_permissions( 'settings_user_role' ) ) {

	$cp_project_id = absint( $_GET['cp-delete-project-id'] );

	//delete the project
	wp_delete_post( $cp_project_id, true );

	//delete all task lists assigned to this project
	$tasks_args = array(
			    'post_type' => 'cp-task-lists',
			    'meta_key' => '_cp-project-id',
			    'meta_value' => $cp_project_id,
			    'showposts' => '-1'
			    );
	$tasks_query = new WP_Query( $tasks_args );

	// WP_Query();
	if ( $tasks_query->have_posts() ) :
	    while( $tasks_query->have_posts() ) : $tasks_query->the_post();

		//delete the task
		wp_delete_post( get_the_ID(), true );

	    endwhile;
	endif;

	//delete all tasks assigned to this project
	$tasks_args = array(
			    'post_type' => 'cp-tasks',
			    'meta_key' => '_cp-project-id',
			    'meta_value' => $cp_project_id,
			    'showposts' => '-1'
			    );
	$tasks_query = new WP_Query( $tasks_args );

	// WP_Query();
	if ( $tasks_query->have_posts() ) :
	    while( $tasks_query->have_posts() ) : $tasks_query->the_post();

		//delete the task
		wp_delete_post( get_the_ID(), true );

	    endwhile;
	endif;

	//add activity log
	cp_add_activity(__('deleted', 'collabpress'), __('project', 'collabpress'), $current_user->ID, $cp_project_id );

    }

endif;
