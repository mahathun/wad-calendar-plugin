<?php
/*
Plugin Name: WAD Calendar
Plugin URI:  http://google.com
Description: Calendar for ITWD Assignment 2.
Version:     1.0
Author:      Jamie, Kit & Dan
Author URI:  http://google.com
Last update: 25 April 2016
*/





global $jscal_db_version;
$jscal_db_version  = 1.0;

register_activation_hook( __FILE__, 'jscal_activate' );
register_deactivation_hook( __FILE__, 'jscal_deactivate' );

// Activate the plugin
function jscal_activate() {
	global $wpdb; //Declare WP Database as global
	global $jscal_db_version; // Declared plugin version as global
	
	$charset_collate = $wpdb->get_charset_collate(); 
	
	// Create the names of the tables using the WP db prefix set by the site user
	$table_events = $wpdb->prefix . 'js_events';
	$table_venues = $wpdb->prefix . 'js_venues';
	$table_categories = $wpdb->prefix . 'js_categories';
	$table_messages = $wpdb->prefix . 'js_messages';
	$table_users = $wpdb->prefix . 'js_users'; // Dan
	
	// Create events table
	$sql = "CREATE TABLE $table_events (
	event_id bigint(20) UNSIGNED AUTO_INCREMENT NOT NULL,
	event_name varchar(40) NOT NULL,
	event_start datetime NOT NULL,
	event_finish datetime NOT NULL,
	event_recurring INT(4) NOT NULL,
	event_category_id bigint(20) UNSIGNED NOT NULL,
	event_location_id VARCHAR(255) NOT NULL,
	event_description TEXT,
	event_organizer_id bigint(20) UNSIGNED NOT NULL,
	event_status tinyint(4) UNSIGNED NOT NULL,
	PRIMARY KEY  (event_id)
	) $charset_collate;";

	// Create venues table
	$sql .= "CREATE TABLE $table_venues (
	venue_id bigint(20) UNSIGNED AUTO_INCREMENT NOT NULL,
	venue_name varchar(100) NOT NULL,
	venue_location VARCHAR(255) NOT NULL,
	venue_author bigint(20) UNSIGNED NOT NULL,
	PRIMARY KEY  (venue_id)
	) $charset_collate;";
	
	// Creates categories table
	$sql .= "CREATE TABLE $table_categories (
	category_id bigint(20) UNSIGNED AUTO_INCREMENT NOT NULL,
	category_name varchar(100) NOT NULL,
	category_author bigint(20) UNSIGNED NOT NULL,
	category_status tinyint(4) UNSIGNED NOT NULL,
	PRIMARY KEY  (category_id)
	) $charset_collate;";
			
	// Creates messages table
	$sql .= "CREATE TABLE $table_messages (
	message_id bigint(20) UNSIGNED AUTO_INCREMENT NOT NULL,
	message_date DATETIME NOT NULL,
	message_content text NOT NULL,
	message_author bigint(20) UNSIGNED NOT NULL,
	event_id tinyint(4) UNSIGNED NOT NULL,
	PRIMARY KEY  (message_id)
	) $charset_collate;";

	//Dan - start
	// Creates users table
	$sql .= "CREATE TABLE $table_users (
	user_id bigint(20) UNSIGNED AUTO_INCREMENT NOT NULL,
	default_view tinyint(4) UNSIGNED NOT NULL,
	PRIMARY KEY  (user_id)
	) $charset_collate;";
	//Dan - end
	
	// Using dbDelta, run the SQL code
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
	
	// Adds the plugin version to the wp options table
	add_option('jscal_db_version', $jscal_db_version);
	
	$installed_ver = get_option('jscal_db_version');

	// If the new version of plugin goes not equal old version, run this code
	if ($installed_ver != $jscal_db_version) {
		$table_events = $wpdb->prefix . '_events';
		$table_venues = $wpdb->prefix . '_venues';
		$table_categories = $wpdb->prefix . 'js_categories';
		$table_messages = $wpdb->prefix . 'js_messages';
		$table_users = $wpdb->prefix . 'js_users'; // Dan
	
		// Create events table
		$sql = "CREATE TABLE $table_events (
		event_id bigint(20) UNSIGNED AUTO_INCREMENT NOT NULL,
		event_name varchar(40) NOT NULL,
		event_start datetime NOT NULL,
		event_finish datetime NOT NULL,		
		event_recurring INT(4) NOT NULL,
		event_category_id bigint(20) UNSIGNED NOT NULL,
		event_location_id bigint(20) NOT NULL,
		event_description TEXT,
		event_organizer_id bigint(20) UNSIGNED NOT NULL,
		event_status tinyint(4) UNSIGNED NOT NULL,
		PRIMARY KEY  (event_id)
		) $charset_collate;";

		// Create venues table
		$sql .= "CREATE TABLE $table_venues (
		venue_id bigint(20) UNSIGNED AUTO_INCREMENT NOT NULL,
		venue_name varchar(100) NOT NULL,
		venue_location VARCHAR(255) NOT NULL,
		venue_author bigint(20) UNSIGNED NOT NULL,
		PRIMARY KEY  (venue_id)
		) $charset_collate;";
		
		// Creates categories table
		$sql .= "CREATE TABLE $table_categories (
		category_id bigint(20) UNSIGNED AUTO_INCREMENT NOT NULL,
		category_name varchar(100) NOT NULL,
		category_author bigint(20) UNSIGNED NOT NULL,
		category_status tinyint(4) UNSIGNED NOT NULL,
		PRIMARY KEY  (category_id)
		) $charset_collate;";
		
		// Creates messages table
		$sql .= "CREATE TABLE $table_messages (
		message_id bigint(20) UNSIGNED AUTO_INCREMENT NOT NULL,
		message_date DATETIME NOT NULL,
		message_content text NOT NULL,
		message_author bigint(20) UNSIGNED NOT NULL,
		event_id tinyint(4) UNSIGNED NOT NULL,
		PRIMARY KEY  (message_id)
		) $charset_collate;";

		//Dan - strat
		// Creates users table
			$sql .= "CREATE TABLE $table_users (
			user_id bigint(20) UNSIGNED AUTO_INCREMENT NOT NULL,
			default_view tinyint(4) UNSIGNED NOT NULL,
			PRIMARY KEY  (user_id)
			) $charset_collate;";
		//Dan - end

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		update_option( "jscal_db_version", $jscal_db_version );
	}
}

// Deactivation sequence
function jscal_deactivate() {
	delete_option('jscal_db_version'); // Deletes the plugin version from the options table
}

// Create the administration menus
add_action('admin_menu', 'jscal_admin_menus');

function jscal_admin_menus() {
	add_menu_page('Manage Events', 'Events', 'read', 'manage_events', 'jscal_manage_events', '');
	add_submenu_page('manage_events', 'Manage Venues', 'Venues', 'read', 'manage_venues', 'jscal_manage_venues');
	add_submenu_page('manage_events', 'Manage Categories', 'Categories', 'read', 'manage_categories', 'jscal_manage_categories');
	add_submenu_page('manage_events', 'Settings', 'Settings', 'read', 'manage_settings', 'jscal_manage_settings');// Dan
}


include('js-backend.php');
include('js-calender-frontend.php');

?>