<?php

global $cp_project;

// Add Task List
if ( isset( $_POST['cp-add-task-list'] ) && isset($_POST['cp-task-list']) ) :

	check_admin_referer('cp-add-task-list');

	$add_task_list = array(
						'post_title' => esc_html($_POST['cp-task-list']),
						'post_status' => 'publish',
						'post_type' => 'cp-task-lists'
						);
	$task_list_id = wp_insert_post( $add_task_list );

	if ( isset($_GET['project']) )
		update_post_meta( $task_list_id, '_cp-project-id', $cp_project->id );
	if ( isset($_POST['cp-task-list-description']) )
		update_post_meta( $task_list_id, '_cp-task-list-description', esc_html($_POST['cp-task-list-description']) );
	
	// Add Activity
	cp_add_activity(__('added', 'collabpress'), __('task list', 'collabpress'), $current_user->ID, $task_list_id);

endif;

// Edit Task List
if ( isset( $_POST['cp-edit-task-list'] ) && $_POST['cp-edit-task-list-id'] ) :

    check_admin_referer('cp-edit-task-list');

    //verify user has permission to edit task lists
    if ( cp_check_permissions( 'settings_user_role' ) ) {

	// The ID
	$tasklistID =  esc_html($_POST['cp-edit-task-list-id']);

	$tasklist = array();
	$tasklist['ID'] = $tasklistID;
	$tasklist['post_title'] = esc_html($_POST['cp-task-list']);
	wp_update_post( $tasklist );
	update_post_meta( $tasklistID, '_cp-task-list-description', esc_html($_POST['cp-task-list-description']) );
	
	// Add Activity
	cp_add_activity(__('updated', 'collabpress'), __('task list', 'collabpress'), $current_user->ID, $tasklistID);

    }

endif;

// Delete Task List
if ( isset( $_GET['cp-delete-task-list-id'] ) ) :

    check_admin_referer( 'cp-action-delete_task_list' );

    //verify user has permission to delete task lists
    if ( cp_check_permissions( 'settings_user_role' ) ) {

	$cp_task_list_id = absint( $_GET['cp-delete-task-list-id'] );

	//delete the task list
	wp_delete_post( $cp_task_list_id, true );

	//delete all tasks in the task list
	$tasks_args = array(
			    'post_type' => 'cp-tasks',
			    'meta_key' => '_cp-task-list-id',
			    'meta_value' => $cp_task_list_id,
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

    }

endif;