<?php

// Avoid direct calls to this page
if (!function_exists ('add_action')) {
		header('Status: 403 Forbidden');
		header('HTTP/1.1 403 Forbidden');
		exit();
}

// Insert a project
function insert_cp_project() {	
}

// Delete a project
function delete_cp_project() {	
}

// Get project id by title
function get_cp_project_id($title) {
	
	global $wpdb;
	
	$table_name = $wpdb->prefix . "cp_projects";
	
	$cp_get_project_id = $wpdb->get_var("SELECT DISTINCT id FROM " . $table_name . " WHERE title = '".$title."'");
	
	if ($cp_get_project_id) {
	
		return $cp_get_project_id;
		
	} else {
		
		return false;
	
	}
	
}

// Get project title by id
function get_cp_project_title($id) {
	
	global $wpdb;
	
	$table_name = $wpdb->prefix . "cp_projects";
	
	$cp_get_project_title = $wpdb->get_var("SELECT DISTINCT title FROM " . $table_name . " WHERE id = '".$id."'");
	
	if ($cp_get_project_title) {
	
		return $cp_get_project_title;
		
	} else {
		
		return false;
	
	}
	
}

// List projects
function list_cp_projects() {
	
	global $wpdb;
	
	$table_name = $wpdb->prefix . "cp_projects";
	
	$cp_list_projects = $wpdb->get_results("SELECT * FROM $table_name WHERE 1");
	
	if ($cp_list_projects) {
	
		$project_count = 1;
		
		foreach ($cp_list_projects as $cp_list_project) {
			
		echo "<p>" . $project_count . ": <a href='admin.php?page=cp-projects-page&view=project&project=".$cp_list_project->id."'>" . $cp_list_project->title . "</a></p>";
		
		$project_count++;
		
		}
		
	} else {
		
		echo "<p>No projects...</p>";
		
	}
	
}

// Check if projects exist
function check_cp_project() {
	
	global $wpdb;
	
	$table_name = $wpdb->prefix . "cp_projects";
	
	$cp_check_projects = $wpdb->get_results("SELECT * FROM $table_name WHERE 1");
	
	if ($cp_check_projects) {
		
		return true;
		
	} else {
		
		return false;
		
	}
	
}

?>