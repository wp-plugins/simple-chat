<?php

defined( 'ABSPATH' ) or exit; // prevent direct access

// get id users list
if( !function_exists('friends_get_friend_user_ids') ):
function friends_get_friend_user_ids( $user_id, $friend_requests_only = false, $assoc_arr = false ) {
	global $wpdb;

	return $wpdb->get_col( "SELECT ID as user_id FROM $wpdb->users -- ORDER BY user_registered DESC" );
}
endif;

// on plugin activation install tables in database
function schat_activation() {
	require_once dirname(__FILE__).'/install.php';
	return schat_install();
}

// load template
function schat_show_template() {
	if(!defined('SIMPLE_CHAT_THEME'))
		return;
	include SIMPLE_CHAT_PLUGIN_DIR.'themes/'.SIMPLE_CHAT_THEME.'/template.php';
}

// load all necessery functions
function schat_load_files_and_setup() {
	
    if( !is_user_logged_in () )
        return;

	global $wpdb;
	
	// setup table names
	$wpdb->chat_users = $wpdb->prefix.SIMPLE_CHAT_DB_CHAT_USERS;
	$wpdb->chat_channels = $wpdb->prefix.SIMPLE_CHAT_DB_CHAT_CHANNELS;
    $wpdb->channel_users = $wpdb->prefix.SIMPLE_CHAT_DB_CHANNEL_USERS;
    $wpdb->chat_messages = $wpdb->prefix.SIMPLE_CHAT_DB_USER_MESSAGES;

	require( SIMPLE_CHAT_PLUGIN_DIR. 'ajax.php' );
	
	if( !defined('DOING_AJAX') )
		schat_update_last_active();//update last active time for user
	
	//echo DOING_AJAX;
	
	define( 'CURRENT_MYSQL_TIME', $wpdb->get_var('select NOW()'));
}

// Set ajax url request
function schat_ajaxurl() {
	echo '<script type="text/javascript">var ajaxurl = "'. admin_url('admin-ajax.php') .'";</script>';
}

function get_gravatar_url( $email ) {
    $hash = md5( strtolower( trim ( $email ) ) );
    return 'http://gravatar.com/avatar/' . $hash;
}

function schat_get_user_displayname( $user_id ) {
	if( $user = get_userdata( $user_id ) )
		return $user->display_name;
}


/* update user activity on login */
function schat_user_status_wp_login( $user_login, $user ) {
	
	global $wpdb;
	
	// setup table names
	$wpdb->chat_users = $wpdb->prefix.SIMPLE_CHAT_DB_CHAT_USERS;
	$wpdb->chat_channels = $wpdb->prefix.SIMPLE_CHAT_DB_CHAT_CHANNELS;
    $wpdb->channel_users = $wpdb->prefix.SIMPLE_CHAT_DB_CHANNEL_USERS;
    $wpdb->chat_messages = $wpdb->prefix.SIMPLE_CHAT_DB_USER_MESSAGES;

	$row_exists = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $wpdb->chat_users WHERE user_id=%d", $user->ID ) );
	
	if(!$row_exists)
		$wpdb->query( $wpdb->prepare( "INSERT INTO $wpdb->chat_users (user_id, is_online) VALUES (%d, 1)", $user->ID ) );
	
	schat_update_last_active( $user->ID );
}

/* logout a user from chat session */
function schat_user_status( $user_id=NULL, $status=0 ) {

	global $wpdb;
	
	if( !$user_id )
		$user_id = get_current_user_ID();
	
	$query=$wpdb->prepare( "UPDATE $wpdb->chat_users set is_online=%d where user_id=%d", $status, $user_id );
	
	return $wpdb->query($query); // return false on db error
}

/* update user activity */
function schat_update_last_active( $user_id=NULL ){
	  
	global $wpdb;

	if( !$user_id )
		$user_id = get_current_user_id();
	
	// update last activity time
	$query = $wpdb->prepare("UPDATE $wpdb->chat_users SET last_active_time= NOW() WHERE user_id=%d", $user_id);
	
	// update status
	$query2 = $wpdb->prepare( "UPDATE $wpdb->chat_users set is_online=%d where user_id=%d", 1, $user_id );
	
	return $wpdb->query( $query ) && $wpdb->query( $query2 ); // return false on db error
}

// return count of online users
function schat_get_online_users_count() {
	global $wpdb;
	
	//$time-strtotime($last_active_time)< (60 * SIMPLE_CHAT_OFF_TIME)
	
	$query = $wpdb->prepare("
		SELECT DISTINCT COUNT(user_id)
		FROM $wpdb->chat_users
		WHERE
			is_online=1
			AND user_id!=%d
			AND ( UNIX_TIMESTAMP(now())-UNIX_TIMESTAMP(last_active_time) < ".(60 * SIMPLE_CHAT_OFF_TIME)." )
	", get_current_user_id());
	
	return $wpdb->get_var($query);
	
}

function schat_save_message( $channel_id, $message='', $sender_id=NULL ) {
	
	global $wpdb;
	
	if( is_null($sender_id) )
		$sender_id = get_current_user_ID();
	
	$query = $wpdb->prepare("
		INSERT INTO {$wpdb->chat_messages} ( sender_id, channel_id, message )
		VALUES ( %d, %d, %s)
	", $sender_id, $channel_id, $message );

	//echo $query;
	$wpdb->query($query);
	
	return $wpdb->insert_id;
}

function schat_soundmanager_settings(){
	?>
	<script type="text/javascript">
		schat={};
		schat.plugin_url="<?php echo SIMPLE_CHAT_URL ?>";

		soundManager.url = schat.plugin_url+"assets/soundmanager/swf/"; // directory where SM2 .SWFs live
	  //  soundManager.useFlashBlock = false;

	</script>
	<?php
}

function schat_load_css(){
	
	if(is_user_logged_in())
		wp_enqueue_style( 'chatcss', SIMPLE_CHAT_URL.'/themes/'.SIMPLE_CHAT_THEME.'/style.css' );
	
	if( $color=get_option( 'schat_color', '#333' ) )
		echo '<style type="text/css">#schatbar .win_titlebar {background-color:'.$color.'} </style>';
	
	if( SIMPLE_CHAT_THEME=='goggle-of-lulz' )
		echo '<style type="text/css">#schatbar .chat_button {border-color:'.$color.'; background-color:'.$color.'} #schatbar .win_titlebar {border-color:'.$color.'} </style>';
}

function schat_load_js(){
//if user is online, load the javascript
    if(is_user_logged_in()&&!is_admin()){//has issues while loading on admin pages a 0 is appeneded still not sure why ?
        
        wp_enqueue_script( 'json2' );
        wp_enqueue_script( 'jquery' );
        
        
        // wp_enqueue_script( 'poshytip', SIMPLE_CHAT_URL.'js/tip/jquery.poshytip.js', array('jquery') );
		
		// wp_enqueue_script("soundmanager",SIMPLE_CHAT_URL."assets/soundmanager/script/soundmanager2.js");
		
		// Its make the chat works fine
		wp_enqueue_script( 'byddypress_global', SIMPLE_CHAT_URL."js/global.js",array("jquery"));
		
		// no cache scripts (get last time modified file, is better)
        wp_enqueue_script("schat", SIMPLE_CHAT_URL."js/schat.js?nocache=".time(), array("jquery","json2"));
	}
}
