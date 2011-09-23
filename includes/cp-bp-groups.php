<?php

/**
 * BuddyPress Groups integration functions
 *
 * @package CollabPress
 * @subpackage CP BP
 * @since 1.2
 */


/**
 * Implementation of BP_Group_Extension
 *
 * @package CollabPress
 * @subpackage CP BP
 * @since 1.2
 */
if ( class_exists( 'BP_Group_Extension' ) ) :

class CP_BP_Group_Extension extends BP_Group_Extension {
	// Group settings
	var $maybe_group_id;
	var $group_settings;
	var $calendar_enable;

	var $cp_link;
	var $current_view;
	var $current_item = array();
	var $current_item_id;
	var $current_item_obj;
	var $current_item_ancestry;

	// These values default to false, and are turned on in the constructor when allowed
	var $enable_create_step = false;
	var $enable_edit_item	= false;

	// Sitewide settings
	var $cp_settings;
	var $admins_can_disable;
	var $admins_can_customize;

	/**
	 * Constructor
	 *
	 * @package CollabPress
	 * @subpackage CP BP
	 * @since 1.2
	 */
	function __construct() {
		global $bp, $cp_bp_integration;

		$this->cp_settings = cp_get_options();

		// Set up a group id. This will differ depending on when the class is being instantiated
		if ( bp_get_current_group_id() )
			$this->maybe_group_id	= bp_get_current_group_id();
		else if ( !empty( $bp->groups->new_group_id ) )
			$this->maybe_group_id	= $bp->groups->new_group_id;
		else
			$this->maybe_group_id	= false;

		// Grab the group settings for use throughout
		$this->group_settings = groups_get_groupmeta( $this->maybe_group_id, 'collabpress' );

		// Should we enable the group tab?
		if ( !empty( $this->group_settings ) ) {
			$this->enable_nav_item = isset( $this->group_settings['group_enable'] ) && 'enabled' == $this->group_settings['group_enable'];
		} else {
			// Should the group tab be enabled by default? Mostly for backpat and groups
			// created before CP was installed.
			$this->enable_nav_item = apply_filters( 'bp_cp_group_enable_default', true );
		}

	 	// Abstract some of the sitewide settings
	 	$this->admins_can_disable 	= 'allow' == $this->cp_settings['bp']['groups_admins_can_disable'];
	 	$this->admins_can_customize 	= 'allow' == $this->cp_settings['bp']['groups_admins_can_customize'];

		// Set up name and slug
		if ( $this->admins_can_customize ) {
			// Admins are allowed to customize this data
			// Fall back on sitewide settings if none have been provided
			$this->name = !empty( $this->group_settings['tab_name'] ) ? $this->group_settings['tab_name'] : $this->cp_settings['bp']['groups_default_tab_name'];
			$this->slug = !empty( $this->group_settings['tab_slug'] ) ? $this->group_settings['tab_slug'] : $this->cp_settings['bp']['groups_default_tab_slug'];
		} else {
			// Use the sitewide settings
			$this->name = $this->cp_settings['bp']['groups_default_tab_name'];
			$this->slug = $this->cp_settings['bp']['groups_default_tab_slug'];
		}

		// Put the CP slug in the main global for later access when building URLs
		$cp_bp_integration->item_cp_slug = $this->slug;

	 	// We only need to show in the admin if admins can customize
	 	if ( $this->admins_can_customize || $this->admins_can_disable ) {
	 		$this->enable_create_step 	= true;
	 		$this->enable_edit_item		= true;

	 		$this->create_step_position 	= 35;
	 	}

		$this->nav_item_position = 31;

		if ( bp_is_group() ) {
			// Set up the group's CP link
			$this->cp_link = bp_get_group_permalink( groups_get_current_group() ) . $this->slug;

			// Don't do this work unless we're on a CP page
			if ( bp_is_current_action( $this->slug ) ) {
				// Set up the current item
				$this->set_current_item();

				// Based on the current item, set the current view
				$this->set_current_view();

				// A less-than-ideal way to let the main CPBP class know we're done
				do_action( 'cp_bp_setup_item' );
			}

			// Enable the calendar tab if necessary
			$this->calendar_enable = !isset( $this->group_settings['calendar_enable'] ) || 'enabled' == $this->group_settings['calendar_enable'];

			if ( $this->calendar_enable ) {
				$this->calendar_nav_setup();
			}

			// Filter permalinks so that they point to the proper place
			add_filter( 'cp_bp_get_project_permalink_parent_item', array( &$this, 'rewrite_item_parent' ), 10, 2 );

			// Ensure that the proper users show up on the user list dropdown
			add_filter( 'cp_task_user_list_html', array( &$this, 'user_list_html' ), 10, 2 );
		}

		// Automatically provision group members to each group project
		add_filter( 'cp_check_project_permissions', array( &$this, 'project_perms' ), 10, 4 );

		// Filter the tax query for project loops
		add_filter( 'cp_bp_projects_tax_query', array( &$this, 'projects_tax_query' ) );

		// Load the styles
		add_action( 'wp_print_styles', array( &$this, 'enqueue_styles' ) );
		$this->enqueue_scripts();
	}

	/**
	 * Set up the current item, based on the request URL
	 *
	 * @package CollabPress
	 * @since 1.2
	 */
	function set_current_item() {
		global $cp_bp_integration;

		$this->current_item = array(
			'project' 	=> '',
			'task_list' 	=> '',
			'task' 		=> ''
		);

		// If we're not on the CP tab, there's nothing to fill in
		if ( bp_is_current_action( $this->slug ) ) {
			$this->current_item['project']   = bp_action_variable( 0 );
			$this->current_item['task_list'] = bp_action_variable( 1 );
			$this->current_item['task'] 	 = bp_action_variable( 2 );
		}

		foreach( $this->current_item as $key => $value ) {
			$this->current_item[$key] = $this->sanitize_current_item_part( $value );
		}

		// Put in the global object for abstraction
		$cp_bp_integration->current_item = $this->current_item;
	}

	/**
	 * Strip all query args off of URL parts
	 *
	 * @package CollabPress
	 * @since 1.2
	 */
	function sanitize_current_item_part( $raw ) {
		$array = explode( '&', $raw );
		return $array[0];
	}

	/**
	 * Set the current view
	 *
	 * @package CollabPress
	 * @since 1.2
	 */
	function set_current_view() {
		global $cp_bp_integration;

		if ( !empty( $this->current_item['task'] ) ) {
			$view = 'task';
		} else if ( !empty( $this->current_item['task_list'] ) ) {
			$view = 'task_list';
		} else if ( !empty( $this->current_item['project'] ) ) {
			$view = 'project';
		} else {
			$view = 'list';
		}

		$this->current_view = $view;

		// Set the global current view as well
		$cp_bp_integration->current_view = apply_filters( 'bp_cp_current_group_view', $view );

		// Now let's get the post ID for the currently viewed item
		if ( in_array( $this->current_view, array( 'task', 'task_list', 'project' ) ) ) {
			// Hackish, but needed for the query
			$post_type_name = 'cp-' . str_replace( '_', '-', $this->current_view ) . 's';
			$current_item_query = new WP_Query( array(
				'name' 		=> $this->current_item[$view],
				'post_type' 	=> $post_type_name
			) );

			if ( $current_item_query->have_posts() ) {
				$this->current_item_id       = $current_item_query->posts[0]->ID;
				$this->current_item_obj      = $current_item_query->posts[0];
				$this->current_item_ancestry = cp_bp_get_item_ancestry( $this->current_item_obj );

				$cp_bp_integration->current_item_id       = $this->current_item_id;
				$cp_bp_integration->current_item_obj      = $this->current_item_obj;
				$cp_bp_integration->current_item_ancestry = $this->current_item_ancestry;
			}
		}
	}

	/**
	 * Determines what shows up on the BP Docs panel of the Create process
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 */
	function create_screen() {
		if ( !bp_is_group_creation_step( $this->slug ) )
			return false;

		$this->admin_markup();

		wp_nonce_field( 'groups_create_save_' . $this->slug );
	}

	/**
	 * Runs when the create screen is saved
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 */

	function create_screen_save() {
		global $bp;

		check_admin_referer( 'groups_create_save_' . $this->slug );

		$success = $this->settings_save( $bp->groups->new_group_id );
	}

	/**
	 * Determines what shows up on the BP Docs panel of the Group Admin
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 */
	function edit_screen() {
		if ( !bp_is_group_admin_screen( $this->slug ) )
			return false;

		$this->admin_markup();

		// On the edit screen, we have to provide a save button
		?>
		<p>
			<input type="submit" value="<?php _e( 'Save Changes', 'bp-docs' ) ?>" id="save" name="save" />
		</p>
		<?php

		wp_nonce_field( 'groups_edit_save_' . $this->slug );
	}

	/**
	 * Runs when the admin panel is saved
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 */
	function edit_screen_save() {
		global $bp;

		if ( !isset( $_POST['save'] ) )
			return false;

		check_admin_referer( 'groups_edit_save_' . $this->slug );

		$success = $this->settings_save();

		/* To post an error/success message to the screen, use the following */
		if ( !$success )
			bp_core_add_message( __( 'There was an error saving, please try again', 'buddypress' ), 'error' );
		else
			bp_core_add_message( __( 'Settings saved successfully', 'buddypress' ) );

		bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . 'admin/' . $this->slug );
	}

	/**
	 * Saves group settings. Called from edit_screen_save() and create_screen_save()
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 */
	function settings_save( $group_id = false ) {
		$success = false;

		if ( !$group_id )
			$group_id = $this->maybe_group_id;

		$settings = !empty( $_POST['collabpress'] ) ? $_POST['collabpress'] : array();

		$old_settings = groups_get_groupmeta( $group_id, 'collabpress' );

		if ( $old_settings == $settings ) {
			// No need to resave settings if they're the same
			$success = true;
		} else if ( groups_update_groupmeta( $group_id, 'collabpress', $settings ) ) {
			$success = true;

			// The slug may have been changed, so let's double check before redirecting
			$this->slug = $settings['tab_slug'];
		}

		return $success;
	}

	/**
	 * Admin markup used on the edit and create admin panels
	 *
	 * @package BuddyPress Docs
	 * @since 1.0-beta
	 */
	function admin_markup() {
		// Enabled for this group?
		if ( $this->admins_can_disable ) {
			// Defaults to turned on, if nothing has been set
			$group_enable = isset( $this->group_settings['group_enable'] ) && 'disabled' == $this->group_settings['group_enable'] ? false : true;
		} else {
			$group_enable = true;
		}

		?>

		<h2><?php _e( 'CollabPress Projects', 'bp-docs' ) ?></h2>

		<?php if ( $this->admins_can_disable ) : ?>
			<table class="group-collabpress-options" id="cp-enable">
				<tr>
					<th scope="row">
						<label for="collabpress[group_enable]"><?php _e( 'Enable a Collabpress tab for this group', 'collabpress' ) ?></label>
					</th>

					<td>
						<select name="collabpress[group_enable]">
							<option value="enabled" <?php selected( $group_enable ) ?>>Enabled</option>
							<option value="disabled" <?php selected( $group_enable, false ) ?>>Disabled</option>
						</select>
					</td>
				</tr>
			</table>
		<?php endif ?>

		<?php if ( $this->admins_can_customize ) : ?>
			<table class="group-collabpress-options" id="cp-details">
				<tr>
					<th scope="row"><label for="collabpress[tab_name]"><?php _e( 'Tab name', 'collabpress' ); ?></label></th>

					<td>
						<input type="text" name="collabpress[tab_name]" id="tab-name" value="<?php echo esc_html( $this->name ) ?>" />
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="collabpress[tab_slug]"><?php _e( 'Tab slug', 'collabpress' ); ?></label></th>

					<td>
						<input type="text" name="collabpress[tab_slug]" id="tab-slug" value="<?php echo esc_html( $this->slug ) ?>" />
					</td>
				</tr>
			</table>
		<?php endif ?>

		<table class="group-collabpress-options" id="cp-calendar">
			<tr>
				<th scope="row"><label for="collabpress[calendar_enable]"><?php _e( 'Project Calendar Tab', 'collabpress' ); ?></label></th>

				<td>
					<select name="collabpress[calendar_enable]">
						<option value="enabled" <?php selected( $this->calendar_enable ) ?>>Enabled</option>
						<option value="disabled" <?php selected( $this->calendar_enable, false ) ?>>Disabled</option>
					</select>
				</td>
			</tr>
		</table>

		<?php
	}

	/**
	 * Loads the content of the tab
	 *
	 * This function does a few things. First, it loads the subnav, which is visible on every
	 * CP BP subtab. Then, it decides which template should be loaded, based on the current
	 * view (determined by the URL). It then checks to see whether the template in question
	 * has been overridden in the active theme or its parent, using locate_template(). Finally,
	 * the proper template is loaded.
	 *
	 * @package CollabPress
	 * @subpackage CP BP
	 * @since 1.2
	 */
	function display() {
		// Render the subnav
		$this->render_subnav();

		// What gets displayed after the subnav depends on the current view
		switch ( $this->current_view ) {
			case 'project' :
				$template = 'collabpress/project.php';
				break;

			case 'task_list' :
				$template = 'collabpress/task-list.php';
				break;

			case 'task' :
				$template = 'collabpress/task.php';
				break;

			case 'list' :
			default :
				$template = 'collabpress/projects-loop.php';
				break;
		}

		// Allow themes to override the template
		if ( !$located_template = locate_template( $template ) ) {
			// If no template is found, load the one from the plugin
			$located_template = CP_PLUGIN_DIR . 'includes/templates/' . $template;
		}

		require( $located_template );
	}

	/**
	 * Renders the group subnav
	 *
	 * @package CollabPress
	 * @subpackage CP BP
	 * @since 1.2
	 */
	function render_subnav() {
		global $cp_bp_integration;

		?>
		<div class="item-list-tabs no-ajax" id="subnav" role="navigation">

		<ul>

			<li<?php if ( 'list' == $this->current_view ) : ?> class="current"<?php endif; ?>><a href="<?php echo esc_html( $this->cp_link ) ?>"><?php _e( 'Dashboard', 'collabpress' ) ?></a></li>

			<?php if ( $project_name = $cp_bp_integration->get_current_item_project_name() ) : ?>
				<li<?php if ( 'project' == $this->current_view ) : ?> class="current"<?php endif; ?>><a href="<?php echo esc_html( $this->cp_link . '/' . $cp_bp_integration->get_current_item_project_slug() ) ?>"> &rarr; <?php echo $project_name ?></a></li>
			<?php endif ?>

			<?php if ( $task_list_name = $cp_bp_integration->get_current_item_task_list_name() ) : ?>
				<li<?php if ( 'task_list' == $this->current_view ) : ?> class="current"<?php endif; ?>><a href="<?php echo esc_html( $this->cp_link . '/' . $cp_bp_integration->get_current_item_project_slug() . '/' . $cp_bp_integration->get_current_item_task_list_slug() ) ?>"> &rarr; <?php echo $task_list_name ?></a></li>
			<?php endif ?>

			<?php if ( $task_name = $cp_bp_integration->get_current_item_task_name() ) : ?>
				<li<?php if ( 'task' == $this->current_view ) : ?> class="current"<?php endif; ?>><a href="<?php echo esc_html( $this->cp_link . '/' . $cp_bp_integration->get_current_item_project_slug() . '/' . $cp_bp_integration->get_current_item_task_list_slug() . '/' . $cp_bp_integration->get_current_item_task_slug() ) ?>"> &rarr; <?php echo $task_name ?></a></li>
			<?php endif ?>
		</ul>

		</div>

		<?php
	}

	function widget_display() {}

	function user_list_html( $html, $selected ) {
		if ( bp_group_has_members( array( 'exclude_admins_mods' => false ) ) ) {
			$html = '<select name="cp-task-assign" id="cp-task-assign">';
			while ( bp_group_members() ) {
				bp_group_the_member();

				$html .= '<option value="'. bp_get_group_member_id() . '" ' . selected( bp_get_group_member_id(), $selected, false ) . '>' . bp_get_group_member_name() . '</option>';

			}

			$html .= '</select>';
		}
		return $html;
	}

	/**
	 * Filters the output of cp_check_project_permissions() to correspond to group membership
	 *
	 * When the project is not associated with a group, it falls back on the CP value of
	 * $has_access
	 *
	 * @package CollabPress
	 * @subpackage CP BP
	 * @since 1.2
	 *
	 * @param bool $has_access The value passed along by cp_check_project_permissions()
	 * @param int $user_id The id of the user being checked against
	 * @param int $project_id The id of the project
	 * @param array $cp_project_users The users of the project, as stored by CP
	 * @return bool $has_access Whether the user has access to the project
	 */
	function project_perms( $has_access, $user_id, $project_id, $cp_project_users ) {
		// Super admins always have access
		if ( is_super_admin() )
			return true;

		if ( bp_is_group() ) {
			// If we're looking at a group page, we can assume we're checking against
			// that group
			$has_access = bp_group_is_member();
		} else {
			// Otherwise (on the admin panel specifically) we have to check to see
			// whether the user is in the associated group
			$terms = wp_get_post_terms( $project_id, 'cp-bp-group' );

			// If there are associated groups, check to see whether the current user
			// is in at least one of them
			if ( !empty( $terms ) ) {
				foreach( (array)$terms as $term ) {
					$has_access = groups_is_user_member( bp_loggedin_user_id(), $term->name );

					// Once we find a single group, no need to keep looping
					if ( $has_access )
						break;
				}
			}
		}

		return $has_access;
	}

	/**
	 * Filters the projects query's tax_query to filter by groups, if necessary
	 *
	 * @package CollabPress
	 * @subpackage CP BP
	 * @since 1.2
	 *
	 * @param array $tax_query
	 * @return array $tax_query
	 */
	function projects_tax_query( $tax_query ) {

		// If we're on a group page, show only the current group's projects
		if ( bp_is_group() ) {
			$tax_query[] = array(
				'taxonomy' => 'cp-bp-group',
				'terms'	   => array( bp_get_current_group_id() ),
				'field'    => 'name'
			);
		}

		return $tax_query;
	}

	/**
	 * If a project is associated with a group, this function will catch its parent_item args
	 * (which are associated with the project author by default) and replace them with args
	 * corresponding to the group.
	 *
	 * @package CollabPress
	 * @since 1.2
	 *
	 * @param array $args The arguments filtered at cp_bp_get_project_permalink_parent_item
	 * @param array $project The WP post object representing the project
	 * @return array $args The arguments which have possibly been modified
	 */
	function rewrite_item_parent( $args, $item ) {
		// Check to see whether this is associated with a group
		// If so, get the group info and replace args
		$terms = wp_get_post_terms( $item->ID, 'cp-bp-group' );

		if ( !empty( $terms ) ) {
			// Take the first term for now. Todo: figure this out
			$term = $terms[0];
			if ( bp_get_current_group_id() == $term->name ) {
				// We've already got much of the info we need
				$args = array(
					'item_id' => bp_get_current_group_id(),
					'item_type' => 'group',
					'item_link' => bp_get_group_permalink( groups_get_current_group() ),
					'item_cp_slug' => $this->slug
				);
			} else {
				// We'll have to pull up this group's data
				$group = new BP_Groups_Group( $term->name );

				if ( !$group )
					return $args;

				$args = array(
					'item_id' => $group->id,
					'item_type' => 'group',
					'item_link' => bp_get_group_permalink( $group )
				);

				if ( $this->admins_can_customize ) {
					// Pull up the group settings to see if there is a custom
					// slug defined. Otherwise fall back on site settings
					$group_settings = groups_get_groupmeta( $group->id, 'collabpress' );
					$args['item_cp_slug'] = !empty( $group_settings['tab_slug'] ) ? $group_settings['tab_slug'] : $this->cp_settings['bp']['groups_default_tab_slug'];
				} else {
					// If customization is not allowed, the slug will be the
					// same through all groups
					$args['item_cp_slug'] = $this->cp_settings['bp']['groups_default_tab_slug'];
				}
			}
		}

		return $args;
	}

	/**
	 * Sets up the Calendar tab
	 *
	 * @package CollabPress
	 * @subpackage CP BP
	 * @since 1.2
	 */
	function calendar_nav_setup() {
		bp_core_new_subnav_item( array(
			'name'		  => __( 'Calendar', 'collabpress' ),
			'slug'  	  => 'calendar', // todo: abstract into a slug for better l18n
			'parent_slug' 	  => bp_get_current_group_slug(),
			'parent_url'	  => bp_get_group_permalink( groups_get_current_group() ),
			'screen_function' => array( $this, '_calendar_display_hook' ),
			'position'	  => 34 // the Projects tab is at 31
		) );
	}

	/**
	 * Screen function for the Calendar tab. Hooks the actual content, and then loads template
	 *
	 * @package CollabPress
	 * @subpackage CP BP
	 * @since 1.2
	 */
	function _calendar_display_hook() {
		add_action( 'bp_template_content', array( &$this, 'calendar_display' ) );

		bp_core_load_template( apply_filters( 'bp_core_template_plugin', $this->template_file ) );
	}

	/**
	 * Loads the calendar markup
	 *
	 * @package CollabPress
	 * @subpackage CP BP
	 * @since 1.2
	 */
	function calendar_display() {
		// Render the project dropdown
		?>
		<form action="<?php bp_group_permalink( groups_get_current_group() ) ?>calendar" method="get">
			<label for="show_cp_project"><?php _e( 'Show tasks from: ', 'collabpress' ) ?></label>
			<?php $this->render_project_selector() ?>
			<input type="submit" value="<?php _e( 'Go', 'collabpress' ) ?>" />
		</form>
		<?php

		add_filter( 'cp_calendar_tasks_args', array( $this, 'calendar_filter_task_query' ) );

		cp_draw_calendar();

		// Just in case it's run more than once on a page.
		remove_filter( 'cp_calendar_tasks_args', array( $this, 'calendar_filter_task_query' ) );
	}

	/**
	 * Renders a dropdown menu for selecting projects
	 *
	 * @package CollabPress
	 * @subpackage CP BP
	 * @since 1.2
	 */
	function render_project_selector( $echo = true ) {
		$projects = $this->get_group_projects();

		$currently_selected = isset( $_GET['show_cp_project'] ) ? $_GET['show_cp_project'] : false;

		$html = '<select class="cp-project-selector" name="show_cp_project">';

		$selected_text = 'all' == $currently_selected ? ' selected="selected"' : '';

		// There will always be an All Projects option
		$html .= '<option value="all"' . $selected_text . '>' . __( 'All Group Projects', 'collabpress' ) . '</option>';

		foreach ( (array)$projects as $project ) {
			$selected_text = $currently_selected == $project->ID ? ' selected="selected"' : '';

			$html .= '<option value="' . $project->ID . '"' . $selected_text . '>' . $project->post_title . '</option>';
		}

		$html .= '</select>';

		if ( $echo )
			echo $html;
		else
			return $html;
	}

	/**
	 * Filters the WP_Query arguments for populating the tasks on the group calendar, to ensure
	 * that only tasks associated with the current group appear
	 *
	 * @package CollabPress
	 * @subpackage CP BP
	 * @since 1.2
	 *
	 * @param array $args The default arguments for the tasks query
	 * @return array $args The modified args
	 */
	function calendar_filter_task_query( $args ) {
		$meta_query = array();

		// Set up the due date meta query (translated from the default args)
		$due_date_meta = array(
			'key'	  => $args['meta_key'],
			'value'	  => $args['meta_value'],
			'compare' => '='
		);

		// Unset the old-style meta_key and meta_value args - we don't need them anymore
		unset( $args['meta_key'] );
		unset( $args['meta_value'] );

		// Now we need to limit by projects. First, query for the group's projects.
		$group_projects = $this->get_group_projects();

		$project_ids = array();
		foreach ( $group_projects as $project ) {
			$project_ids[] = $project->ID;
		}

		// If there is a specific project id being requested, ensure that it belongs to the
		// group. If it doesn't, show all the group's projects; otherwise show just
		// the requested project
		if ( isset( $_GET['show_cp_project'] ) && 'all' != $_GET['show_cp_project'] ) {
			if ( in_array( $_GET['show_cp_project'], $project_ids ) ) {
				$project_ids = array( (int)$_GET['show_cp_project'] );
			}
		}

		// Assemble the meta query that limits tasks to these projects
		$projects_meta = array(
			'key'	  => '_cp-project-id',
			'value'	  => $project_ids,
			'compare' => 'IN'
		);

		// Finally, put the meta queries into a single array, and put them into $args
		$args['meta_query'] = array( $due_date_meta, $projects_meta );

		return $args;
	}

	/**
	 * Get this group's projects
	 *
	 * @package CollabPress
	 * @subpackage CP BP
	 * @since 1.2
	 *
	 * @return array An array of project objects
	 */
	function get_group_projects() {
		$projects_args = array(
			'post_type'	=> 'cp-projects',
			'showposts' 	=> '-1',
			'tax_query'	=> array(
				array(
					'taxonomy' => 'cp-bp-group',
					'terms'	   => array( bp_get_current_group_id() ),
					'field'    => 'name'
				)
			)
		);
		$projects_query = new WP_Query( $projects_args );

		return $projects_query->posts;
	}

	/**
	 * Enqueue styles
	 *
	 * @package CollabPress
	 * @subpackage CP BP
	 * @since 1.2
	 */
	function enqueue_styles() {
		// Styles should be loaded on the groups tab, or on the group admin subtab, or on
		// the calendar page
		if ( bp_is_current_action( $this->slug ) || in_array( $this->slug, (array)bp_action_variables() ) || bp_is_current_action( 'calendar' ) ) {
			wp_enqueue_style( 'cp-bp', CP_PLUGIN_URL . 'includes/css/bp.css' );
		}
	}

	/**
	 * Enqueue scripts
	 *
	 * @package CollabPress
	 * @subpackage CP BP
	 * @since 1.2
	 */
	function enqueue_scripts() {
		// Scripts should be loaded on the groups tab, or on the group admin subtab
		if ( bp_is_current_action( $this->slug ) || in_array( $this->slug, (array)bp_action_variables() ) || bp_is_current_action( 'calendar' ) ) {
			wp_enqueue_script( 'cp-bp', CP_PLUGIN_URL . 'includes/js/bp.js', array( 'jquery' ) );

			collabpress_dashboard_page::cp_admin_scripts();
		}
	}
}

endif;

?>