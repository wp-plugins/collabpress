<?php

// Check if there are any projects
if (!check_cp_project()) {
	?>
	<div class="updated">
		<p><strong><?php _e( 'Welcome to CollabPress! To create your first project click <a href="admin.php?page=cp-projects-page">here</a>.', 'collabpress' ); ?></strong></p>
	</div>
	<?php
}

// Add Project
if ( isset($_POST['cp_add_project_submit']) ) {
	check_admin_referer('cp-add-project');
	global $wpdb, $current_user;
	
	$cp_project_auth = $current_user->ID;
	$cp_project_date =  date("Y-m-d H:m:s");
	$cp_project_title = esc_html($_POST['cp_project_title']);
	$cp_project_details = esc_html($_POST['cp_project_details']);
	
	$table_name = $wpdb->prefix . "cp_projects";

	$results = $wpdb->insert($table_name, array('auth' => $cp_project_auth, 'date' => $cp_project_date, 
		'title' => $cp_project_title, 'details' => $cp_project_details ) );

	// Retrieve newly created record id
	$lastid = $wpdb->insert_id;
	
	// Add activity log record
	insert_cp_activity($cp_project_auth, $cp_project_date, 'created', $cp_project_title, 'project', $lastid);

?>

	<div class="updated">
		<p><strong><?php _e('Project '.$cp_project_title.' has been added. To add a new task click <a href="admin.php?page=cp-projects-page&view=project&project='.$lastid.'">here</a>.', 'collabpress'); ?></strong></p>
	</div>
	
<?php
}

// Add Task
if ( isset($_POST['cp_add_task_button']) ) {
	check_admin_referer('cp-add-task');
	global $wpdb, $current_user;
	
	$cp_auth = $current_user->ID;
	$cp_users = esc_html($_POST['user']);
	$cp_date =  date("Y-m-d H:m:s");
	$cp_title = esc_html($_POST['cp_title']);
	$cp_details = esc_html($_POST['cp_details']);
	$cp_due_date = $_POST['cp_tasks_due_month'] ."-". $_POST['cp_tasks_due_day'] ."-". $_POST['cp_tasks_due_year'];
	$cp_add_title = get_cp_project_title($_POST['cp_add_tasks_project']);
	$cp_add_tasks_project = esc_html($_POST['cp_add_tasks_project']);
	
	$table_name = $wpdb->prefix . "cp_tasks";
	
	$results = $wpdb->insert($table_name, array('proj_id' => $cp_add_tasks_project, 'auth' => $cp_auth, 
		'users' => $cp_users, 'date' => $cp_date, 'title' => $cp_title, 'details' => $cp_details, 'due_date' => $cp_due_date ) );
	
	// Retrieve newly created record ID
	$lastid = $wpdb->insert_id;

	// Add activity log record
	insert_cp_activity($cp_auth, $cp_date, 'added', $cp_title, 'task', $lastid);

	// Check if email notifications is enabled
	//if (get_option('cp_email_config')) {
	if (isset($_POST['notify'])) {
	
		// Send email to user assigned to task
		$user_info = get_userdata($cp_users);
		$cp_email = $user_info->user_email;
		$cp_subject = 'CollabPress: New task assigned to you';
		$cp_message = "Project: " .$cp_add_title."\n\n";
		$cp_message .= "You have just been assigned the following task by ".$current_user->display_name. "\n\n";
		$cp_message .= "Title: " .$cp_title ."\n";
		$cp_message .= "Details: " .$cp_details ."\n\n";
		$cp_message .= "To view this task visit:\n";
		$cp_message .= get_bloginfo('siteurl') . '/wp-admin/admin.php?page=cp-projects-page&view=project&project='.$cp_add_tasks_project;
	
		// WP_Mail()
		wp_mail($cp_email, $cp_subject, $cp_message);
	
	}
	
?>
	<div class="updated">
		<p><strong><?php _e('Task Added', 'collabpress'); ?></strong></p>
	</div>
	
<?php
}

// Delete Task
if(isset($_GET['delete-task']))
{
	check_admin_referer('cp-action-delete_task');
	delete_cp_task($_GET['delete-task']);
?>
	<div class="error">
		<p><strong><?php _e( 'Task Deleted', 'collabpress' ); ?></strong></p>
	</div>
	
<?php
}

// Delete Task
if(isset($_GET['delete-project']))
{
	check_admin_referer('cp-action-delete_project');
	delete_cp_project($_GET['delete-project']);
?>
	<div class="error">
		<p><strong><?php _e( 'Project Deleted', 'collabpress' ); ?></strong></p>
	</div>
	
<?php
}

// Complete Task
if(isset($_GET['completed-task']))
{
	check_admin_referer('cp-action-complete_task');
	update_cp_task($_GET['completed-task'], '1');	
?>
	<div class="updated">
		<p><strong><?php _e( 'Task Completed', 'collabpress' ); ?></strong></p>
	</div>
	
<?php
}

// Uncomplete Task
if(isset($_GET['uncompleted-task']))
{
	check_admin_referer('cp-action-uncomplete_task');
	update_cp_task($_GET['uncompleted-task'], '0');	
?>
	<div class="updated">
		<p><strong><?php _e( 'Task Status Updated', 'collabpress' ); ?></strong></p>
	</div>
	
<?php
}

?>