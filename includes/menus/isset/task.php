<?php

global $cp_project;
global $cp_task_list;

// Add Task
if ( isset( $_POST['cp-add-task'] ) && isset($_POST['cp-task']) ) :

	check_admin_referer('cp-add-task');

	$add_task = array(
					'post_title' => esc_html($_POST['cp-task']),
					'post_status' => 'publish',
					'post_type' => 'cp-tasks'
					);
	$task_id = wp_insert_post( $add_task );

	//add task status
	update_post_meta( $task_id, '_cp-task-status', 'open' );

	if ( isset($_GET['project']) )
		update_post_meta( $task_id, '_cp-project-id', $cp_project->id );
	if ( isset($_GET['task-list']) )
		update_post_meta( $task_id, '_cp-task-list-id', $cp_task_list->id );
	if ( isset($_POST['cp-task-due']) ) :
		// Validate Date
		if ( cp_validate_date($_POST['cp-task-due']) ) :
			$taskDate = esc_html($_POST['cp-task-due']);
		else :
			$taskDate = date('n/j/Y');
		endif;
		update_post_meta( $task_id, '_cp-task-due', $taskDate );
	endif;
	if ( isset($_POST['cp-task-assign']) )
		update_post_meta( $task_id, '_cp-task-assign', absint($_POST['cp-task-assign']) );
	
	// Add Activity
	cp_add_activity(__('added', 'collabpress'), __('task', 'collabpress'), $current_user->ID, $task_id);


	//check if email notification is checked
	if( isset( $_POST['notify'] ) ) {

	    //send email
	    $task_author_data = get_userdata( absint( $_POST['cp-task-assign'] ) );
	    $author_email = $task_author_data->user_email;

	    $subject = 'New task assigned to you: ' .get_the_title( $task_id );

	    $message = "There is a new task assigned to you: \n\n";
	    $message .= esc_html( $_POST['cp-task'] );

	    cp_send_email( $author_email, $subject, $message );

	}

endif;

// Edit Task
if ( isset( $_POST['cp-edit-task'] ) && isset($_POST['cp-edit-task-id']) ) :

	check_admin_referer('cp-edit-task');

    //verify user has permission to edit tasks
    if ( cp_check_permissions( 'settings_user_role' ) ) {

	// The ID
	$taskID =  absint($_POST['cp-edit-task-id']);

	$task = array();
	$task['ID'] = $taskID;
	$task['post_title'] = esc_html($_POST['cp-task']);
	wp_update_post( $task );
	update_post_meta( $taskID, '_cp-task-due', esc_html($_POST['cp-task-due']) );
	update_post_meta( $taskID, '_cp-task-assign', absint($_POST['cp-task-assign']) );
	
	// Add Activity
	cp_add_activity(__('updated', 'collabpress'), __('task', 'collabpress'), $current_user->ID, $taskID);

    }

endif;

// Complete Task
if ( isset( $_GET['cp-complete-task-id'] ) ) :

    check_admin_referer('cp-complete-task');

    //task ID to complete
    $taskID = ( isset( $_GET['cp-complete-task-id'] ) ) ? absint( $_GET['cp-complete-task-id'] ) : null;

    //get current task status
    $task_status = get_post_meta( $taskID, '_cp-task-status', true );

    if ( $taskID && $task_status != 'complete' ) {

	//set the task to complete
	update_post_meta( $taskID, '_cp-task-status', 'complete' );

	// Add Activity
	cp_add_activity(__('completed', 'collabpress'), __('task', 'collabpress'), $current_user->ID, $taskID );

    }elseif ($taskID ) {

	//open the task
	update_post_meta( $taskID, '_cp-task-status', 'open' );

	// Add Activity
	cp_add_activity(__('opened', 'collabpress'), __('task', 'collabpress'), $current_user->ID, $taskID );

    }

endif;

// Delete Task
if ( isset( $_GET['cp-delete-task-id'] ) ) :

    check_admin_referer( 'cp-action-delete_task' );

    //verify user has permission to delete tasks
    if ( cp_check_permissions( 'settings_user_role' ) ) {

	$cp_task_id = absint( $_GET['cp-delete-task-id'] );

	//delete the task list
	wp_delete_post( $cp_task_id, true );

	//add activity log
	cp_add_activity(__('deleted', 'collabpress'), __('task', 'collabpress'), $current_user->ID, $cp_task_id );

    }

endif;