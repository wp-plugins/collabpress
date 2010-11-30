<?php

// Dashboard
require_once( 'menus/dashboard.php' );

// Settings
function cp_settings_page() {
	require_once( 'menus/settings.php' );
}

// Debug
function cp_debug_page() {
	require_once( 'menus/debug.php' );
}

// Footer
function cp_footer() {
    require_once( 'footer.php' );
}