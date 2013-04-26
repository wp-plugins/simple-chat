<?php
/*
Plugin Name: Simple Chat
Plugin URI: http://tutzstyle.com/
Author URI: http://tutzstyle.com/
Description:  a chat based on facebook chat, works only for registred and logged users.
Author: Arthur Araújo
Version: 1.0

*/

define( 'SIMPLE_CHAT', '1' );
//define( 'SIMPLE_CHAT_THEME', get_option( 'schat_theme', 'default' ) );
define( 'SIMPLE_CHAT_THEME', get_option( 'schat_theme', 'goggle-of-lulz' ) );
define( 'SIMPLE_CHAT_PLUGIN_DIR', dirname(__FILE__).'/' );
define( 'SIMPLE_CHAT_URL', WP_PLUGIN_URL . '/' . str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) );

define( 'SIMPLE_CHAT_AFK_TIME', '3' ); // minutes to set afk status
define( 'SIMPLE_CHAT_OFF_TIME', '10' ); // minutes to set afk status

define( 'SIMPLE_CHAT_DB_CHAT_USERS', 'schat_users' );
define( 'SIMPLE_CHAT_DB_CHAT_CHANNELS', 'schat_channels' );
define( 'SIMPLE_CHAT_DB_CHANNEL_USERS', 'schat_channel_users' );
define( 'SIMPLE_CHAT_DB_USER_MESSAGES', 'usermessages' );

if( is_admin() )
	include SIMPLE_CHAT_PLUGIN_DIR. 'admin.php';

add_action('wp_head','schat_ajaxurl'); // ajax url js var

add_action( 'init', 'schat_load_files_and_setup' ); //load functions and setup

// include basic functions
require( SIMPLE_CHAT_PLUGIN_DIR. 'functions.php' );

//logout user from chat when user logs out
add_action("wp_logout","schat_user_status"); // logout current user

add_action('wp_login', 'schat_user_status_wp_login', 10, 2);

//add_action("wp_footer","schat_soundmanager_settings");

//enqueue the required script
add_action( 'wp_print_scripts', 'schat_load_js');
//add_action( 'admin_print_scripts', 'schat_load_js');

//load the chat bar when the user is logged in
add_action( 'wp_footer', 'schat_show_template');
//add_action( 'admin_footer', 'schat_show_template');

add_action("wp_print_styles","schat_load_css");
//add_action("admin_print_styles","schat_load_css");

register_activation_hook( __FILE__, 'schat_activation' );
