<div class="wrap">
	<h2><?php _e( 'CollabPress Settings', 'collabpress' ); ?></h2>
        <form method="post" action="options.php">
	    <?php settings_fields('cp_options_group'); ?>
            <?php $options = get_option('cp_options'); ?>
	    <?php
	    //load option values
	    $cp_rss_feed_num = ( isset( $options['num_recent_activity'] ) ) ? absint( $options['num_recent_activity'] ) : 4;
	    
	    //load minimum user role
	    $cp_user_role = ( isset( $options['user_role'] ) ) ? esc_attr( $options['user_role'] ) : 'manage_options';

	    //load settings user role
	    $cp_settings_user_role = ( isset( $options['settings_user_role'] ) ) ? esc_attr( $options['settings_user_role'] ) : 'manage_options';
	    ?>
            <table class="form-table">
		<tr>
		    <td colspan="2"><h3><?php _e( 'General', 'collabpress' ); ?></h3><hr /></td>
		</tr>
	        <tr>
		    <th scope="row"><label for="dashboard"><?php _e( 'Dashboard Meta Box', 'collabpress' ); ?></label></th>
                    <td>
                    	<select name="cp_options[dashboard_meta_box]">
			    			<option value="disabled" <?php selected( $options['dashboard_meta_box'], 'disabled' ); ?>><?php _e('Disabled', 'collabpress') ?></option>
                            <option value="enabled" <?php selected( $options['dashboard_meta_box'], 'enabled' ); ?>><?php _e('Enabled', 'collabpress') ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="email"><?php _e( 'Email Notifications', 'collabpress' ); ?></label></th>
                    <td>
                    	<select name="cp_options[email_notifications]">
			    			<option value="enabled" <?php selected( $options['email_notifications'], 'enabled' ); ?>><?php _e('Enabled', 'collabpress') ?></option>
                            <option value="disabled" <?php selected( $options['email_notifications'], 'disabled' ); ?>><?php _e('Disabled', 'collabpress') ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="recent_activity"><?php _e( 'Recent Activites Per Page', 'collabpress' ); ?></label></th>
                    <td>
                    	<select name="cp_options[num_recent_activity]">
			    <option value="1" <?php selected( $cp_rss_feed_num, '1' ); ?>>1</option>
			    <option value="2" <?php selected( $cp_rss_feed_num, '2' ); ?>>2</option>
			    <option value="3" <?php selected( $cp_rss_feed_num, '3' ); ?>>3</option>
			    <option value="4" <?php selected( $cp_rss_feed_num, '4' ); ?>>4</option>
			    <option value="5" <?php selected( $cp_rss_feed_num, '5' ); ?>>5</option>
			    <option value="6" <?php selected( $cp_rss_feed_num, '6' ); ?>>6</option>
			    <option value="7" <?php selected( $cp_rss_feed_num, '7' ); ?>>7</option>
			    <option value="8" <?php selected( $cp_rss_feed_num, '8' ); ?>>8</option>
			    <option value="9" <?php selected( $cp_rss_feed_num, '9' ); ?>>9</option>
			    <option value="10" <?php selected( $cp_rss_feed_num, '10' ); ?>>10</option>
                        </select>
                    </td>
                </tr>
		<tr>
		    <td colspan="2"><h3><?php _e( 'Permissions', 'collabpress' ); ?></h3><hr /></td>
		</tr>
                <tr>
                    <th scope="row"><label for="user_role"><?php _e( 'Minimum User Role for Access', 'collabpress' ); ?></label></th>
                    <td>
                    	<select name="cp_options[user_role]">
			    <option value="manage_options" <?php selected( $cp_user_role, 'manage_options' ); ?>>Administrator</option>
			    <option value="delete_others_posts" <?php selected( $cp_user_role, 'delete_others_posts' ); ?>>Editor</option>
			    <option value="publish_posts" <?php selected( $cp_user_role, 'publish_posts' ); ?>>Author</option>
			    <option value="edit_posts" <?php selected( $cp_user_role, 'edit_posts' ); ?>>Contributor</option>
			    <option value="read" <?php selected( $cp_user_role, 'read' ); ?>>Subscriber</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="settings_user_role"><?php _e( 'Minimum User Role to change Settings, Edit/Delete data, Enable/View Debug, etc', 'collabpress' ); ?></label></th>
                    <td>
                    	<select name="cp_options[settings_user_role]">
			    <option value="manage_options" <?php selected( $cp_settings_user_role, 'manage_options' ); ?>>Administrator</option>
			    <option value="delete_others_posts" <?php selected( $cp_settings_user_role, 'delete_others_posts' ); ?>>Editor</option>
			    <option value="publish_posts" <?php selected( $cp_settings_user_role, 'publish_posts' ); ?>>Author</option>
			    <option value="edit_posts" <?php selected( $cp_settings_user_role, 'edit_posts' ); ?>>Contributor</option>
			    <option value="read" <?php selected( $cp_settings_user_role, 'read' ); ?>>Subscriber</option>
                        </select>
                    </td>
                </tr>
		<tr>
		    <td colspan="2"><h3><?php _e( 'Advanced', 'collabpress' ); ?></h3><hr /></td>
		</tr>
		<tr>
                	<th scope="row"><label for="debug"><?php _e( 'Debug Mode', 'collabpress' ); ?></label></th>
                    <td>
                    	<select name="cp_options[debug_mode]">
			    			<option value="disabled" <?php selected( $options['debug_mode'], 'disabled' ); ?>><?php _e('Disabled', 'collabpress') ?></option>
                            <option value="enabled" <?php selected( $options['debug_mode'], 'enabled' ); ?>><?php _e('Enabled', 'collabpress') ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td colspan="2"><input type="submit" name="Save" value="<?php _e( 'Save Settings', 'collabpress' ) ?>" class="button-primary" /></td>
                </tr>
	    </table>
	</form>
</div>

<?php cp_footer(); ?>