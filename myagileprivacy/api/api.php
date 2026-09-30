<?php

/**
 * My Agile Privacy api definitions
 *
 * @author     https://www.myagileprivacy.com/
 */

if( function_exists( 'ini_set' ) )
{
	ini_set( 'display_errors', 0 );
}

if( !defined( 'ABSPATH' ) )
{
	define( 'SHORTINIT', true );

	$wp_load_path = dirname( __FILE__ );

	while ( ! file_exists( $wp_load_path . '/wp-load.php' ) )
	{
		$wp_load_parent = dirname( $wp_load_path );
		if( $wp_load_parent === $wp_load_path || $wp_load_parent === '/' || $wp_load_parent === '.' ) {
			if( !headers_sent() ) { http_response_code( 500 ); }
			echo json_encode( array( 'success' => false, 'message' => 'wp-load.php not found' ) );
			exit;
		}
		$wp_load_path = $wp_load_parent;
	}

	require_once $wp_load_path . '/wp-load.php';
}

if( function_exists( 'ini_set' ) )
{
	ini_set( 'display_errors', 0 );
}

global $wpdb;

// the helpers below cache only for the length of the request
if( function_exists( 'wp_cache_add_non_persistent_groups' ) )
{
	wp_cache_add_non_persistent_groups( array( 'map_api_options' ) );
}

// Load plugin defines
require_once dirname( __FILE__ ) . '/../includes/my-agile-privacy-defines.php';

if( !headers_sent() )
{
	header( 'Content-Type: application/json' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Access-Control-Allow-Origin: *' );
}

// ============================================================
// Diagnostic echo mode.
// ============================================================

$map_api_debug = false;

// ============================================================
// MULTISITE: the site was already resolved by WordPress from host and path
// ============================================================

$map_options_table = $wpdb->options;
$map_api_blog_id   = ( is_multisite() && get_current_blog_id() > 1 ) ? get_current_blog_id() : 0;

// ============================================================
// DB HELPERS
// ============================================================

/**
 * Reads an option value directly from the DB, bypassing WordPress filters.
 * Uses an in-memory cache to avoid repeated queries within the same request.
 * Compatible with multisite via the global $map_options_table.
 *
 * @param string $option_name  The option key to retrieve.
 * @param mixed  $default      Value to return if the option does not exist.
 * @return mixed               The option value, unserialized if needed, or $default.
 */
function map_api_get_option( $option_name, $default = false )
{
	global $wpdb, $map_options_table;

	$cached_value = wp_cache_get( $option_name, 'map_api_options' );

	if( $cached_value !== false )
	{
		return $cached_value;
	}

	$option_value = $wpdb->get_var( $wpdb->prepare(
		"SELECT option_value FROM {$map_options_table} WHERE option_name = %s LIMIT 1",
		$option_name
	));

	if( $option_value !== null )
	{
		$option_value = maybe_unserialize( $option_value );
		wp_cache_set( $option_name, $option_value, 'map_api_options' );
		return $option_value;
	}

	wp_cache_set( $option_name, $default, 'map_api_options' );

	return $default;
}

/**
 * Writes an option value directly to the DB, bypassing WordPress filters.
 * Performs an UPDATE if the option already exists, INSERT otherwise.
 * Clears the in-memory cache after writing.
 * Compatible with multisite via the global $map_options_table.
 *
 * @param string $option_name  The option key to write.
 * @param mixed  $new_value    The value to store. Arrays and objects are serialized automatically.
 * @return bool                True on success, false on failure.
 */
function map_api_update_option( $option_name, $new_value )
{
	global $wpdb, $map_options_table, $map_api_blog_id;

	if( empty( $option_name ) )
	{
		return false;
	}

	$serialized_value = maybe_serialize( $new_value );

	$option_exists = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$map_options_table} WHERE option_name = %s",
		$option_name
	));

	if( $option_exists > 0 )
	{
		$result = $wpdb->update(
			$map_options_table,
			array( 'option_value' => $serialized_value ),
			array( 'option_name'  => $option_name ),
			array( '%s' ),
			array( '%s' )
		);
	}
	else
	{
		$result = $wpdb->insert(
			$map_options_table,
			array(
				'option_name'  => $option_name,
				'option_value' => $serialized_value,
				'autoload'     => 'yes',
			),
			array( '%s', '%s', '%s' )
		);
	}

	wp_cache_delete( $option_name, 'map_api_options' );

	// Invalidate the native option cache so later reads are not stale; switch
	// object-cache context per blog on multisite.
	if( function_exists( 'wp_cache_delete' ) )
	{
		$map_api_switched = false;

		if( !empty( $map_api_blog_id ) && function_exists( 'wp_cache_switch_to_blog' ) )
		{
			wp_cache_switch_to_blog( $map_api_blog_id );
			$map_api_switched = true;
		}

		wp_cache_delete( $option_name, 'options' );
		wp_cache_delete( 'alloptions', 'options' );

		// a name remembered as missing would hide a newly inserted option
		$map_api_notoptions = wp_cache_get( 'notoptions', 'options' );

		if( is_array( $map_api_notoptions ) && isset( $map_api_notoptions[ $option_name ] ) )
		{
			unset( $map_api_notoptions[ $option_name ] );
			wp_cache_set( 'notoptions', $map_api_notoptions, 'options' );
		}

		if( $map_api_switched )
		{
			wp_cache_switch_to_blog( get_current_blog_id() );
		}
	}

	return $result !== false;
}

// ============================================================
// SECURITY
// ============================================================

// Polyfill for hash_equals() - available natively from PHP 5.6.0.
if( !function_exists( 'hash_equals' ) )
{
	function hash_equals( $known_string, $user_string )
	{
		if( !is_string( $known_string ) || !is_string( $user_string ) )
		{
			return false;
		}

		$known_length = strlen( $known_string );

		if( $known_length !== strlen( $user_string ) )
		{
			return false;
		}

		$result = 0;

		for ( $i = 0; $i < $known_length; $i++ )
		{
			$result |= ord( $known_string[ $i ] ) ^ ord( $user_string[ $i ] );
		}

		return $result === 0;
	}
}

/**
 * Core files the handlers below rely on.
 */
function map_api_load_core()
{
	if( !function_exists( 'sanitize_text_field' ) )
	{
		require_once ABSPATH . WPINC . '/formatting.php';
	}

	if( !function_exists( 'wp_kses' ) )
	{
		if( defined( 'CUSTOM_TAGS' ) && CUSTOM_TAGS )
		{
			foreach( array( 'allowedposttags', 'allowedtags', 'allowedentitynames', 'allowedxmlentitynames' ) as $map_kses_global )
			{
				if( !isset( $GLOBALS[ $map_kses_global ] ) || !is_array( $GLOBALS[ $map_kses_global ] ) )
				{
					$GLOBALS[ $map_kses_global ] = array();
				}
			}
		}

		require_once ABSPATH . WPINC . '/kses.php';
	}
}

/**
 * intval() with the PHP 8 reading of numeric strings, saturated instead of wrapped.
 */
function map_api_intval( $value )
{
	if( is_string( $value ) )
	{
		if( preg_match( '/^[ \t\n\r\v\f]*[+-]?[0-9]+[ \t\n\r\v\f]*$/', $value ) )
		{
			return (int) $value;
		}

		if( !preg_match( '/^[ \t\n\r\v\f]*[+-]?([0-9]+(\.[0-9]*)?|\.[0-9]+)([eE][+-]?[0-9]+)?/', $value ) )
		{
			return intval( $value );
		}

		$value = (float) $value;
	}

	if( is_float( $value ) )
	{
		if( is_nan( $value ) || is_infinite( $value ) )
		{
			return 0;
		}

		if( $value >= PHP_INT_MAX )
		{
			return PHP_INT_MAX;
		}

		if( $value <= -PHP_INT_MAX )
		{
			return -PHP_INT_MAX;
		}

		return (int) $value;
	}

	return intval( $value );
}

/**
 * Validates the request token.
 *
 * @return bool
 */
function map_api_check_token()
{
	if( !defined( 'AUTH_KEY' ) || ! defined( 'AUTH_SALT' ) )
	{
		return false;
	}

	if( !function_exists( 'hash_hmac' ) )
	{
		return false;
	}

	$token    = isset( $_POST['map_api_token'] ) ? $_POST['map_api_token'] : '';
	$expected = hash_hmac( 'sha256', 'map_api_v1', AUTH_KEY . AUTH_SALT );

	return !empty( $token ) && is_string( $token ) && is_string( $expected ) && hash_equals( $expected, $token );
}

/**
 * Whether a plugin is active on this site or network-wide.
 *
 * @param string $plugin plugin basename
 * @return bool
 */
function map_api_plugin_is_active( $plugin )
{
	global $wpdb;

	$active = map_api_get_option( 'active_plugins', array() );

	if( is_array( $active ) && in_array( $plugin, $active, true ) )
	{
		return true;
	}

	if( is_multisite() && !empty( $wpdb->sitemeta ) )
	{
		$network = maybe_unserialize( $wpdb->get_var( $wpdb->prepare(
			"SELECT meta_value FROM {$wpdb->sitemeta} WHERE meta_key = %s AND site_id = %d LIMIT 1",
			'active_sitewide_plugins',
			(int) $wpdb->siteid
		) ) );

		return is_array( $network ) && isset( $network[ $plugin ] );
	}

	return false;
}

// ============================================================
// DISPATCHER
// ============================================================

if( !map_api_plugin_is_active( basename( dirname( dirname( __FILE__ ) ) ) . '/my-agile-privacy.php' ) )
{
	if( !headers_sent() ) { http_response_code( 404 ); }
	echo json_encode( array( 'success' => false ) );
	exit;
}

$action = ( isset( $_POST['action'] ) && is_string( $_POST['action'] ) ) ? $_POST['action'] : '';

if( empty( $action ) )
{
	if( !headers_sent() ) { http_response_code( 400 ); }
	echo json_encode( array( 'success' => false, 'message' => 'Missing action' ) );
	exit;
}

switch ( $action )
{
	case 'map_diagnostic_data':
		map_diagnostic_data();
		break;

	case 'map_report_gtm_gateway':
		map_api_report_gtm_gateway();
		break;

	default:
		if( !headers_sent() ) { http_response_code( 400 ); }
		echo json_encode( array( 'success' => false, 'message' => 'Unknown action' ) );
		exit;
}

// ============================================================
// ACTIONS
// ============================================================

/**
 * Unified diagnostic endpoint.
 *
 * Receives all diagnostic data in a single POST request and dispatches
 * each piece of data to the appropriate handler.
 *
 * In debug mode ($map_api_debug = true) returns the received payload as-is
 * without writing anything to the DB.
 */
function map_diagnostic_data()
{
	global $map_api_debug;

	if( !map_api_check_token() ) {
		if( !headers_sent() ) { http_response_code( 403 ); }
		echo json_encode( array( 'success' => false, 'message' => 'Invalid token' ) );
		exit;
	}

	map_api_load_core();

	// Sanitize incoming data
	$send_detected_keys     = isset( $_POST['send_detected_keys'] )     ? map_api_intval( $_POST['send_detected_keys'] )     : 0;
	$cookie_shield_detected = isset( $_POST['cookie_shield_detected'] ) ? map_api_intval( $_POST['cookie_shield_detected'] ) : 0;
	$is_consent_valid       = isset( $_POST['is_consent_valid'] )       ? map_api_intval( $_POST['is_consent_valid'] )       : 0;
	$error_motivation       = ( isset( $_POST['error_motivation'] ) && is_string( $_POST['error_motivation'] ) ) ? sanitize_text_field( $_POST['error_motivation'] ) : '';

	// Free text reaches the licence server verbatim; keep it bounded. Counted in
	// characters: cutting on a byte boundary would split a multibyte sequence
	// and leave text that no longer encodes.
	$map_api_text_max = defined( 'MAP_PLUGIN_JS_ERROR_TEXT_MAX' ) ? map_api_intval( MAP_PLUGIN_JS_ERROR_TEXT_MAX ) : 255;
	$error_motivation = mb_substr( $error_motivation, 0, $map_api_text_max );

	// The value is stored and travels on: drop the characters that could turn it
	// into markup and keep the message otherwise faithful.
	$error_motivation = str_replace( array( '<', '>', '"', "'", '&' ), '', $error_motivation );

	$error_code             = isset( $_POST['error_code'] )             ? map_api_intval( $_POST['error_code'] )             : 0;
	$detectable_keys_raw    = ( isset( $_POST['detectableKeys'] ) && is_string( $_POST['detectableKeys'] ) ) ? sanitize_text_field( $_POST['detectableKeys'] ) : '';
	$detected_keys_raw      = ( isset( $_POST['detectedKeys'] ) && is_string( $_POST['detectedKeys'] ) )     ? sanitize_text_field( $_POST['detectedKeys'] )   : '';

	// Debug mode: return received data without processing
	if( $map_api_debug )
	{
		echo json_encode( array(
			'success'               => true,
			'debug'                 => true,
			'ping'                  => array(
				'action'                => 'map_diagnostic_data',
				'send_detected_keys'    => $send_detected_keys,
				'cookie_shield_detected'=> $cookie_shield_detected,
				'is_consent_valid'      => $is_consent_valid,
				'error_motivation'      => $error_motivation,
				'error_code'            => $error_code,
				'detectableKeys'        => $detectable_keys_raw,
				'detectedKeys'          => $detected_keys_raw,
			),
		));
		exit;
	}

	$the_settings = map_api_get_option( MAP_PLUGIN_SETTINGS_FIELD, array() );

	// Plugin not active, do nothing
	if( !isset( $the_settings['pa'] ) || $the_settings['pa'] != 1 )
	{
		echo json_encode( array( 'success' => false ) );
		exit;
	}

	$success = true;

	// Every branch below writes settings, and the reports are only expected while
	// scanning: outside that state they did not come from our own code.
	$map_api_scanning = map_api_is_scanning_mode( $the_settings );

	// 1. Detected Keys
	if( $send_detected_keys && $map_api_scanning )
	{
		$success = map_api_internal_save_detected_keys( $the_settings, $detectable_keys_raw, $detected_keys_raw );
	}

	// 2. JS Shield status
	if( $map_api_scanning )
	{
		map_api_save_cookie_shield_status( $the_settings, $cookie_shield_detected );
	}

	// 3. Consent Mode status
	if( $map_api_scanning )
	{
		map_api_save_consent_mode_status( $the_settings, $is_consent_valid, $error_motivation, $error_code );
	}

	echo json_encode( array( 'success' => $success ) );
	exit;
}

/**
 * Records a Tag Gateway loading-priority detection reported by the frontend.
 * Requests are validated by the shared token.
 */
function map_api_report_gtm_gateway()
{
	if( !map_api_check_token() ) {
		if( !headers_sent() ) { http_response_code( 403 ); }
		echo json_encode( array( 'success' => false, 'message' => 'Invalid token' ) );
		exit;
	}

	$the_settings = map_api_get_option( MAP_PLUGIN_SETTINGS_FIELD, array() );

	// The beacon is emitted only while scanning; see map_api_is_scanning_mode().
	if( !map_api_is_scanning_mode( $the_settings ) ) {
		echo json_encode( array( 'success' => false ) );
		exit;
	}

	if( is_array( $the_settings ) && !empty( $the_settings['disable_gtm_gateway_detection'] ) ) {
		echo json_encode( array( 'success' => false ) );
		exit;
	}

	$now  = time();
	$data = map_api_get_option( MAP_PLUGIN_GTM_GATEWAY_DETECTED, array() );

	if( !is_array( $data ) ) {
		$data = array();
	}

	// Rate-limit repeated writes.
	if( isset( $data['last_detected'] ) && ( $now - intval( $data['last_detected'] ) ) < 3600 ) {
		echo json_encode( array( 'success' => true, 'throttled' => true ) );
		exit;
	}

	$data['first_detected'] = isset( $data['first_detected'] ) ? intval( $data['first_detected'] ) : $now;
	$data['last_detected']  = $now;
	$data['count']          = isset( $data['count'] ) ? intval( $data['count'] ) + 1 : 1;

	map_api_update_option( MAP_PLUGIN_GTM_GATEWAY_DETECTED, $data );

	// Keep the standard WP options cache coherent for the full-WP notice reader
	// (this lightweight endpoint only clears its own private cache group).
	if( function_exists( 'wp_cache_delete' ) ) {
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( MAP_PLUGIN_GTM_GATEWAY_DETECTED, 'options' );
	}

	echo json_encode( array( 'success' => true ) );
	exit;
}

// ============================================================
// LOGIC HELPERS
// ============================================================

/**
 * Merges detected cookie keys with stored keys, dedups, saves, and auto-publishes matching cookie posts.
 *
 * @param array  $the_settings       Current plugin settings.
 * @param string $detectable_keys_raw Comma-separated list of detectable keys from JS.
 * @param string $detected_keys_raw   Comma-separated list of detected keys from JS.
 * @return bool                       Always returns true.
 */
/**
 * Whether the site is currently scanning.
 *
 * Mirrors the condition the frontend applies before reporting: the same two
 * sources, combined the same way. Reports that arrive outside this state did
 * not come from our own code.
 *
 * @param array $the_settings
 * @return bool
 */
function map_api_is_scanning_mode( $the_settings )
{
	// Same two sources the frontend reads, and nothing more: the licence state
	// is checked by the callers that depend on it, so adding it here would
	// reject reports the frontend still considers valid.
	if( is_array( $the_settings ) &&
		isset( $the_settings['scan_mode'] ) && $the_settings['scan_mode'] == 'learning_mode' )
	{
		return true;
	}

	$rconfig = map_api_get_option( MAP_PLUGIN_RCONFIG, array() );

	return ( is_array( $rconfig ) &&
		isset( $rconfig['force_js_learning_mode'] ) &&
		$rconfig['force_js_learning_mode'] == 1 );
}

function map_api_internal_save_detected_keys( $the_settings, $detectable_keys_raw, $detected_keys_raw )
{
	// Load existing saved keys
	$js_cookie_shield_detected_keys = explode( ',', map_api_get_option( MAP_PLUGIN_JS_DETECTED_FIELDS, '' ) );

	if( !$js_cookie_shield_detected_keys )
	{
		$js_cookie_shield_detected_keys = array();
	}

	// Merge detectableKeys
	if( $detectable_keys_raw )
	{
		foreach( array_slice( explode( ',', $detectable_keys_raw, MAP_DETECTED_KEYS_MAX + 1 ), 0, MAP_DETECTED_KEYS_MAX ) as $v )
		{
			$v = sanitize_text_field( $v );

			if( $v && $v !== 'null' && preg_match( MAP_DETECTED_KEY_PATTERN, $v ) )
			{
				$js_cookie_shield_detected_keys[] = $v;
			}
		}
	}

	// Merge detectedKeys
	if( $detected_keys_raw )
	{
		foreach( array_slice( explode( ',', $detected_keys_raw, MAP_DETECTED_KEYS_MAX + 1 ), 0, MAP_DETECTED_KEYS_MAX ) as $v )
		{
			$v = sanitize_text_field( $v );
			if( $v && $v !== 'null' && preg_match( MAP_DETECTED_KEY_PATTERN, $v ) ) {
				$js_cookie_shield_detected_keys[] = $v;
			}
		}
	}

	$js_cookie_shield_detected_keys = array_slice( array_unique( array_filter( $js_cookie_shield_detected_keys ) ), 0, MAP_DETECTED_KEYS_MAX );

	map_api_update_option( MAP_PLUGIN_JS_DETECTED_FIELDS, implode( ',', $js_cookie_shield_detected_keys ) );

	// Auto-activate matching cookie posts
	if( !empty( $js_cookie_shield_detected_keys ) )
	{
		$consumed_keys = map_api_auto_activate_cookie_posts( $js_cookie_shield_detected_keys );

		if( !empty( $consumed_keys ) )
		{
			$residual_keys = array_diff( $js_cookie_shield_detected_keys, $consumed_keys );
			map_api_update_option( MAP_PLUGIN_JS_DETECTED_FIELDS, implode( ',', $residual_keys ) );
		}
	}

	return true;
}

/**
 * Publishes cookie posts whose api key matches and flags them auto-detected.
 *
 * @param array $keys  List of API keys to match against cookie posts.
 */
function map_api_auto_activate_cookie_posts( $keys )
{
	global $wpdb;

	if( empty( $keys ) ) return array();

	$placeholders = implode( ',', array_fill( 0, count( $keys ), '%s' ) );

	// Find posts matching the given api keys
	// Use call_user_func_array for compatibility with PHP 5.6 and WordPress < 4.8.3
	$sql = "SELECT p.ID, pm.meta_value AS api_key
		FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
		WHERE p.post_type = %s
		AND p.post_status IN ('draft', 'publish')
		AND pm.meta_key = '_map_api_key'
		AND pm.meta_value IN ($placeholders)";

	$params = array_merge( array( $sql, MAP_POST_TYPE_COOKIES ), $keys );
	$query  = call_user_func_array( array( $wpdb, 'prepare' ), $params );

	$post_rows = $wpdb->get_results( $query, ARRAY_A );

	if( empty( $post_rows ) ) return array();

	$consumed_keys = array();

	foreach( $post_rows as $row )
	{
		$post_id = intval( $row['ID'] );

		// Publish the post
		$wpdb->update(
			$wpdb->posts,
			array( 'post_status' => 'publish' ),
			array( 'ID' => $post_id ),
			array( '%s' ),
			array( '%d' )
		);

		// Update or insert _map_auto_detected meta
		$meta_exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_map_auto_detected'",
			$post_id
		));

		if( $meta_exists )
		{
			$wpdb->update(
				$wpdb->postmeta,
				array( 'meta_value' => 1 ),
				array( 'post_id' => $post_id, 'meta_key' => '_map_auto_detected' ),
				array( '%d' ),
				array( '%d', '%s' )
			);
		}
		else
		{
			$wpdb->insert(
				$wpdb->postmeta,
				array( 'post_id' => $post_id, 'meta_key' => '_map_auto_detected', 'meta_value' => 1 ),
				array( '%d', '%s', '%d' )
			);
		}

		$consumed_keys[] = $row['api_key'];
	}

	// Clear post transient cache
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_posts_%'" );

	return $consumed_keys;
}

/**
 * Runtime state of the scan, kept apart from the settings (twin of MyAgilePrivacy::get_scan_runtime()).
 *
 * @param array $the_settings  Current plugin settings, read for values saved by earlier versions.
 * @return array
 */
function map_api_get_scan_runtime( $the_settings )
{
	$defaults = array(
		'missing_cookie_shield'					=>	false,
		'missing_cookie_shield_timestamp'		=>	null,
		'cookie_shield_running'					=>	false,
		'cookie_shield_running_timestamp'		=>	null,
		'cmode_v2_js_on_error'					=>	false,
		'cmode_v2_js_on_error_first_relevation'	=>	false,
		'cmode_v2_js_error_code'				=>	0,
		'cmode_v2_js_error_motivation'			=>	null,
		'last_scan_date_internal'				=>	null,
		'last_sync'								=>	null,
		'learning_mode_last_active_timestamp'	=>	null,
	);

	$runtime = map_api_get_option( MAP_PLUGIN_SCAN_RUNTIME, false );

	if( !is_array( $runtime ) )
	{
		$runtime = array();
	}

	$runtime = array_intersect_key( $runtime, $defaults );

	// values saved inside the settings by earlier versions, with the typing of MyAgilePrivacy::sanitise_settings()
	$legacy = is_array( $the_settings ) ? array_diff_key( array_intersect_key( $the_settings, $defaults ), $runtime ) : array();

	foreach( $legacy as $key => $value )
	{
		if( in_array( $key, array( 'missing_cookie_shield', 'cmode_v2_js_on_error' ), true ) )
		{
			$value = ( $value === true || $value === 'true' );
		}
		elseif( in_array( $key, array( 'cmode_v2_js_error_code', 'cmode_v2_js_on_error_first_relevation' ), true ) )
		{
			$value = map_api_intval( $value );
		}

		$runtime[ $key ] = $value;
	}

	return array_merge( $defaults, $runtime );
}

/**
 * Updates the cookie shield status in the scan runtime state.
 * Writes to DB only if the current status differs from what is already stored.
 *
 * @param array $the_settings            Current plugin settings.
 * @param int   $cookie_shield_detected  1 if CookieShield is detected and running, 0 if missing.
 */
function map_api_save_cookie_shield_status( $the_settings, $cookie_shield_detected )
{
	$runtime = map_api_get_scan_runtime( $the_settings );

	if( $cookie_shield_detected == 0 )
	{
		if( $runtime['missing_cookie_shield'] == false ||
			$runtime['cookie_shield_running'] == true
		)
		{
			$runtime['cookie_shield_running']           = false;
			$runtime['missing_cookie_shield']           = true;
			$runtime['missing_cookie_shield_timestamp'] = time();

			map_api_update_option( MAP_PLUGIN_SCAN_RUNTIME, $runtime );
		}
	}
	elseif( $cookie_shield_detected == 1 )
	{
		if( $runtime['cookie_shield_running'] == false ||
			$runtime['missing_cookie_shield'] == true
		) {
			$runtime['missing_cookie_shield']           = false;
			$runtime['cookie_shield_running']           = true;
			$runtime['cookie_shield_running_timestamp'] = time();

			map_api_update_option( MAP_PLUGIN_SCAN_RUNTIME, $runtime );
		}
	}
}

/**
 * Updates the Google Consent Mode V2 status in the scan runtime state.
 * Writes to DB only if the status, error code, or error motivation have changed.
 * Tracks the timestamp of the first error detection and resets it when resolved.
 *
 * @param array  $the_settings      Current plugin settings.
 * @param int    $is_consent_valid  1 if Consent Mode is correctly configured, 0 if not.
 * @param string $error_motivation  Human-readable description of the error, empty if valid.
 * @param int    $error_code        Numeric error code, 0 if valid.
 */
function map_api_save_consent_mode_status( $the_settings, $is_consent_valid, $error_motivation, $error_code )
{
	$cmode_v2_js_on_error = $is_consent_valid ? false : true;

	$runtime = map_api_get_scan_runtime( $the_settings );

	$stored_on_error = $runtime['cmode_v2_js_on_error'];

	// Write to DB only if something has changed
	$has_changed =
		$stored_on_error                         !== $cmode_v2_js_on_error ||
		$runtime['cmode_v2_js_error_code']       !== $error_code ||
		$runtime['cmode_v2_js_error_motivation'] !== $error_motivation;

	if( !$has_changed ) return;

	if( $stored_on_error && ! $cmode_v2_js_on_error )
	{
		$runtime['cmode_v2_js_on_error_first_relevation'] = 0;
	}

	if( ! $stored_on_error && $cmode_v2_js_on_error )
	{
		$runtime['cmode_v2_js_on_error_first_relevation'] = time();
	}

	$runtime['cmode_v2_js_on_error']         = $cmode_v2_js_on_error;
	$runtime['cmode_v2_js_error_code']       = $error_code;
	$runtime['cmode_v2_js_error_motivation'] = $error_motivation;

	map_api_update_option( MAP_PLUGIN_SCAN_RUNTIME, $runtime );
}

