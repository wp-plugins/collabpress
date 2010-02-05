<?php

// Avoid direct calls to this page
if (!function_exists ('add_action')) {
		header('Status: 403 Forbidden');
		header('HTTP/1.1 403 Forbidden');
		exit();
}

// Define page name
define('CP_PROJECTS_METABOX_PAGE_NAME', 'cp-projects-page');

// Create projects class
class cp_core_projects {

	// Constructor
	function cp_core_projects() {
		
		// Add filter for WP 2.8 box system
		add_filter('screen_layout_columns', array(&$this, 'cp_onscreen_layout_columns'), 10, 2);
		
		// Register callback
		add_action('admin_menu', array(&$this, 'cp_onadmin_menu')); 
		
		// Register the callback been used if options of page been submitted and needs to be processed
		add_action('admin_post_save_cp_projects_metaboxes_general', array(&$this, 'cp_onsave_changes'));
		
	}
	
	// For WordPress 2.8 column support
	function cp_onscreen_layout_columns($columns, $screen) {
		
		if ($screen == $this->pagehook) {
			$columns[$this->pagehook] = 2;
		}
		
		return $columns;
		
	}
	
	// Extend the admin menu
	function cp_onadmin_menu() {
		
		// Add our own option page, you can also add it to different sections or use your own one
		$this->pagehook = add_submenu_page(CP_DASHBOARD_METABOX_PAGE_NAME, 'CollabPress - Projects', "Projects", 'manage_options', CP_PROJECTS_METABOX_PAGE_NAME, array(&$this, 'cp_onshow_page'));
		
		// Register callback gets call prior your own page gets rendered
		add_action('load-'.$this->pagehook, array(&$this, 'cp_onload_page'));
		
	}
	
	// Will be executed if wordpress core detects this page has to be rendered
	function cp_onload_page() {
		
		// Ensure, that the needed javascripts been loaded to allow drag/drop, expand/collapse and hide/show of boxes
		wp_enqueue_script('common');
		wp_enqueue_script('wp-lists');
		wp_enqueue_script('postbox');
		
		add_meta_box('cp-projects-metaboxes-sidebox-1', __( 'Calendar', 'collabpress' ), array(&$this, 'cp_projects_sidebox_1_content'), $this->pagehook, 'side', 'core');
		add_meta_box('cp-projects-metaboxes-sidebox-2', __( 'Projects', 'collabpress' ), array(&$this, 'cp_projects_onsidebox_2_content'), $this->pagehook, 'side', 'core');
		add_meta_box('cp-projects-metaboxes-sidebox-3', __( 'Users', 'collabpress' ), array(&$this, 'cp_projects_onsidebox_3_content'), $this->pagehook, 'side', 'core');
		
		if ($_GET['view'] != 'project') {

			// Add several metaboxes now, all metaboxes registered during load page can be switched off/on at "Screen Options" automatically, nothing special to do therefore
			add_meta_box('cp-projects-metaboxes-contentbox-1', 'Create New Project', array(&$this, 'cp_oncontentbox_1_content'), $this->pagehook, 'normal', 'core');
			
		} else {
			add_meta_box('cp-projects-metaboxes-contentbox-2', 'Tasks', array(&$this, 'cp_oncontentbox_2_content'), $this->pagehook, 'normal', 'core');
			add_meta_box('cp-projects-metaboxes-contentbox-3', 'Add A New Task', array(&$this, 'cp_oncontentbox_3_content'), $this->pagehook, 'normal', 'core');
			
		}
	
	}
	
	// Executed to show the plugins complete admin page
	function cp_onshow_page() {
		
		// We need the global screen column value to beable to have a sidebar in WordPress 2.8
		global $screen_layout_columns;
		
		// Define some data can be given to each metabox during rendering
		$data = array();
		
		?>
		<div id="cp-projects-metaboxes-general" class="wrap">
		
		<?php
		
		get_cp_project_id('CollabPress');
		
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
			if (get_option('cp_email_config')) {
			
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
			delete_cp_task($_GET['delete-task'], $_GET['task-title']);
		?>
			<div class="error">
				<p><strong><?php _e( 'Task Deleted', 'collabpress' ); ?></strong></p>
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
		
		<?php // screen_icon('options-general'); ?>
		
		<?php
		
		$cp_project_title = get_cp_project_title(esc_html($_GET['project']));
		
		?>
		
		<?php if($cp_project_title) { ?>
			<h2>Project: <?php echo $cp_project_title; ?></h2>
		<?php } else { ?>
			<h2>Add New Project</h2>
		<?php } ?>
		
		
			<div id="poststuff" class="metabox-holder<?php echo 2 == $screen_layout_columns ? ' has-right-sidebar' : ''; ?>">
				
				<div id="side-info-column" class="inner-sidebar">
					<?php do_meta_boxes($this->pagehook, 'side', $data); ?>
				</div>
				
				<div id="post-body" class="has-sidebar">
					
					<div id="post-body-content" class="has-sidebar-content">
						<?php do_meta_boxes($this->pagehook, 'normal', $data); ?>
					</div>
				
					<form action="admin-post.php" method="post">
						<?php wp_nonce_field('cp-dashboard-metaboxes-general'); ?>
						<?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); ?>
						<?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false ); ?>
						<input type="hidden" name="action" value="save_cp_dashboard_metaboxes_general" />

						<p style="display:none">
							<input type="submit" value="<?php _e( 'Save Changes', 'collabpress' ) ?>" class="button-primary" name="Submit"/>	
						</p>
					</form>
					
				</div>
				
				<br class="clear"/>	
						
			</div>
			
		</div>
		
		<script type="text/javascript">
			//<![CDATA[
			jQuery(document).ready( function($) {
				// Close postboxes that should be closed
				$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
				// Postboxes setup
				postboxes.add_postbox_toggles('<?php echo $this->pagehook; ?>');
			});
			//]]>
		</script>
		
		<?php
	}

	// Executed if the post arrives initiated by pressing the submit button of form
	function cp_onsave_changes() {
		
		// User permission check
		if ( !current_user_can('manage_options') )
			wp_die( __('Cheatin&#8217; uh?', 'collabpress') );		
				
		// Cross check the given referer.
		check_admin_referer('cp-projects-metaboxes-general');
		
		// Process here your on $_POST validation and / or option saving
		
		// Lets redirect the post request into get request (you may add additional params at the url, if you need to show save results
		wp_redirect($_POST['_wp_http_referer']);	
			
	}

	// Below you will find for each registered metabox the callback method, that produces the content inside the boxes
	function cp_projects_sidebox_1_content($data) {
		?><center><?php
		$time = time();
    	echo cp_generate_small_calendar(date('Y', $time), date('n', $time));
		echo '<p><a style="text-decoration:none; color:#D54E21" href="#">' . __('Coming Soon', 'collabpress') . '</a></p>';	
    	?></center><?php	
	}
	
	function cp_projects_onsidebox_2_content($data) {
		list_cp_projects();
		echo '<p><a style="text-decoration:none; color:#D54E21" href="admin.php?page=cp-projects-page">' . __('Add New', 'collabpress') . '</a></p>';	
	}
	
	function cp_projects_onsidebox_3_content($data) {
		list_cp_users();
	}
	
	function cp_oncontentbox_1_content($data) {
	?>
		<form method="post" action="">
		<?php wp_nonce_field('cp-add-project'); ?>
		
		<table class="form-table">
		<tr class="form-field form-required">
		<th scope="row"><label for="cp_project_title"><?php _e('Title', 'collabpress'); ?> <span class="description"><?php _e('(required)', 'collabpress'); ?></span></label></th>
		<td><input name="cp_project_title" type="text" id="cp_project_title" value="" aria-required="true" /></td>
		</tr>
		<tr class="form-field">
		<th scope="row"><label for="cp_project_details"><?php _e('Details', 'collabpress'); ?></label></th>
		<td><input name="cp_project_details" type="text" id="cp_project_details" value="" /></td>
		</tr>			
		</table>
		
		<input type="hidden" name="page_options" value="cp_project_title, cp_project_details" />
		
		<p>
		<input type="submit" class="button-primary" name="cp_add_project_submit" value="<?php _e('Add Project', 'collabpress') ?>" />
		</p>
		
		</form>
	<?php
	}
	
	function cp_oncontentbox_2_content($data) {
		
		$project_id = $_GET['project'];
		
		list_cp_tasks($project_id, CP_PROJECTS_METABOX_PAGE_NAME);
	
	}
	
	function cp_oncontentbox_3_content($data) {
	?>
		<form method="post" action="" enctype="multipart/form-data">
		<?php wp_nonce_field('cp-add-task'); ?>
		
		<table class="form-table">
		<tr class="form-field form-required">
		<th scope="row"><label for="cp_title"><?php _e('Title: ', 'collabpress'); ?> <span class="description"><?php _e('(required)'); ?></span></label></th>
		<td><input name="cp_title" type="text" id="cp_title" value="" aria-required="true" /></td>
		</tr>
		
		<tr class="form-field">
		<th scope="row"><label for="cp_details"><?php _e('Details: ', 'collabpress'); ?></label></th>
		<td><textarea name="cp_details" id="cp_details" rows="10" cols="20" /></textarea></td>
		</tr>
		
		<tr class="form-field">
		<th scope="row"><label for="cp_users"><?php _e('Assign to: ', 'collabpress'); ?></label></th>
		<td><?php wp_dropdown_users(); ?><td>
		</tr>
		
		<tr class="form-field">
		<th scope="row"><label for="cp_tasks_due"><?php _e('Due: ', 'collabpress'); ?></label></th>
		<td>
		<?php
			$months = array (1 => 'January', 'February', 'March', 'April', 'May', 'June','July', 'August', 'September', 'October', 'November', 'December');
			$days = range (1, 31);
			$years = range (date('Y'), 2025);
			
			// Month
			echo __('Month', 'collabpress') . ": <select name='cp_tasks_due_month'>";
			$cp_month_count = 1;
			foreach ($months as $value) {
				
				if ($value == date('F')) {
					$month_selected = "SELECTED";
				} else {
					$month_selected = '';
				}
				
				echo '<option ' . $month_selected . ' value="'.$cp_month_count.'">'.$value.'</option>\n';
				$cp_month_count++;
			} echo '</select>';
			
			// Day
			echo __('Day', 'collabpress') . ": <select name='cp_tasks_due_day'>";
			foreach ($days as $value) {
				
				if ($value == date('j')) {
					$day_selected = "SELECTED";
				} else {
					$day_selected = '';
				}
				
				echo '<option ' . $day_selected . ' value="'.$value.'">'.$value.'</option>\n';
			} echo '</select>';
			
			
			// Year
			echo __('Year', 'collabpress') . ": <select name='cp_tasks_due_year'>";
			foreach ($years as $value) {
				
				if ($value == date('Y')) {
					$year_selected = "SELECTED";
				} else {
					$year_selected = '';
				}
				
				echo '<option ' . $year_selected . ' value="'.$value.'">'.$value.'</option>\n';
			} 
			echo '</select>';
		?>
		</td>
		</tr>

		</table>
		
		<input type="hidden" name="cp_add_tasks_project" value="<?php echo $_GET['project']; ?>">
		<input type="hidden" name="page_options" value="user, cp_title, cp_details, cp_users, cp_tasks_due_month, cp_tasks_due_day, cp_tasks_due_year, cp_add_tasks_project" />
		
		<p>
		<input type="submit" class="button-primary" name="cp_add_task_button" value="<?php _e('Add Task', 'collabpress') ?>" />
		</p>
		
		</form>
	<?php
	}
	
}

?>