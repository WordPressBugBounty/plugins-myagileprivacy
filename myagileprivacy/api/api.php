<?php

/**
 * My Agile Privacy api definitions
 *
 * @author     https://www.myagileprivacy.com/
 */

if( !defined( 'ABSPATH' ) )
{
	define( 'SHORTINIT', true );

	$wp_load_path = dirname( __FILE__ );

	while ( ! file_exists( $wp_load_path . '/wp-load.php' ) )
	{
		$wp_load_path = dirname( $wp_load_path );
		if ( $wp_load_path === '/' ) {
			die( 'wp-load.php not found' );
		}
	}

	require_once $wp_load_path . '/wp-load.php';
}

global $wpdb;

// Load plugin defines
require_once dirname( __FILE__ ) . '/../includes/my-agile-privacy-defines.php';

header( 'Content-Type: application/json' );

// ============================================================
// DEBUG MODE
// Set to true to enable ping/pong response with received data
// ============================================================

$map_api_debug = false;

// ============================================================
// MULTISITE: detect correct options table from current domain
// ============================================================

$map_options_table = $wpdb->base_prefix . 'options';

if( is_multisite() )
{
	$current_domain = $_SERVER['HTTP_HOST'];

	$blog = $wpdb->get_row( $wpdb->prepare(
		"SELECT blog_id FROM {$wpdb->blogs} WHERE domain = %s LIMIT 1",
		$current_domain
	));

	if( $blog && intval( $blog->blog_id ) > 1 )
	{
		$map_options_table = $wpdb->base_prefix . intval( $blog->blog_id ) . '_options';
	}
}

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

	if ( $cached_value !== false )
	{
		return $cached_value;
	}

	$option_value = $wpdb->get_var( $wpdb->prepare(
		"SELECT option_value FROM {$map_options_table} WHERE option_name = %s LIMIT 1",
		$option_name
	));

	if ( $option_value !== null )
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
	global $wpdb, $map_options_table;

	if ( empty( $option_name ) )
	{
		return false;
	}

	$serialized_value = maybe_serialize( $new_value );

	$option_exists = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$map_options_table} WHERE option_name = %s",
		$option_name
	));

	if ( $option_exists > 0 )
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

	return $result !== false;
}

// ============================================================
// SECURITY
// ============================================================

/**
 * Verifies that the request originates from the same site by comparing
 * the host of the HTTP_REFERER header against the current HTTP_HOST.
 * Returns false if either value is missing or malformed.
 *
 * @return bool  True if the referrer host matches the current host, false otherwise.
 */
function map_api_check_referrer()
{
	if ( empty( $_SERVER['HTTP_REFERER'] ) )
	{
		return false;
	}

	$referer_host = parse_url( $_SERVER['HTTP_REFERER'], PHP_URL_HOST );
	$current_host = $_SERVER['HTTP_HOST'];

	if ( empty( $referer_host ) || empty( $current_host ) )
	{
		return false;
	}

	return $referer_host === $current_host;
}

// ============================================================
// DISPATCHER
// ============================================================

$action = isset( $_POST['action'] ) ? sanitize_text_field( $_POST['action'] ) : '';

if ( empty( $action ) )
{
	http_response_code( 400 );
	echo json_encode( array( 'success' => false, 'message' => 'Missing action' ) );
	exit;
}

switch ( $action )
{
	case 'map_diagnostic_data':
		map_diagnostic_data();
		break;

	default:
		http_response_code( 400 );
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

	if ( ! map_api_check_referrer() ) {
		http_response_code( 403 );
		echo json_encode( array( 'success' => false, 'message' => 'Invalid referrer' ) );
		exit;
	}

	// Sanitize incoming data
	$send_detected_keys     = isset( $_POST['send_detected_keys'] )     ? intval( $_POST['send_detected_keys'] )             : 0;
	$cookie_shield_detected = isset( $_POST['cookie_shield_detected'] ) ? intval( $_POST['cookie_shield_detected'] )         : 0;
	$is_consent_valid       = isset( $_POST['is_consent_valid'] )       ? intval( $_POST['is_consent_valid'] )               : 0;
	$error_motivation       = isset( $_POST['error_motivation'] )       ? sanitize_text_field( $_POST['error_motivation'] )  : '';
	$error_code             = isset( $_POST['error_code'] )             ? intval( $_POST['error_code'] )                     : 0;
	$detectable_keys_raw    = isset( $_POST['detectableKeys'] )         ? sanitize_text_field( $_POST['detectableKeys'] )    : '';
	$detected_keys_raw      = isset( $_POST['detectedKeys'] )           ? sanitize_text_field( $_POST['detectedKeys'] )      : '';

	// Debug mode: return received data without processing
	if ( $map_api_debug )
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
	if ( ! isset( $the_settings['pa'] ) || $the_settings['pa'] != 1 )
	{
		echo json_encode( array( 'success' => false ) );
		exit;
	}

	$success = true;

	// 1. Detected Keys (learning mode only)
	if ( $send_detected_keys )
	{
		$success = map_api_internal_save_detected_keys( $the_settings, $detectable_keys_raw, $detected_keys_raw );
	}

	// 2. JS Shield status
	map_api_save_cookie_shield_status( $the_settings, $cookie_shield_detected );

	// 3. Consent Mode status
	map_api_save_consent_mode_status( $the_settings, $is_consent_valid, $error_motivation, $error_code );

	echo json_encode( array( 'success' => $success ) );
	exit;
}

// ============================================================
// LOGIC HELPERS
// ============================================================

/**
 * Merges the JS-detected cookie keys with the ones already stored in the DB,
 * deduplicates the result, saves the updated list, and auto-publishes any
 * cookie posts whose API key matches one of the detected keys.
 * Replaces the original internal_save_detected_keys() method, using direct
 * DB queries instead of WP_Query and wp_update_post (not available with SHORTINIT).
 *
 * @param array  $the_settings       Current plugin settings.
 * @param string $detectable_keys_raw Comma-separated list of detectable keys from JS.
 * @param string $detected_keys_raw   Comma-separated list of detected keys from JS.
 * @return bool                       Always returns true.
 */
function map_api_internal_save_detected_keys( $the_settings, $detectable_keys_raw, $detected_keys_raw )
{
	// Load existing saved keys
	$js_cookie_shield_detected_keys = explode( ',', map_api_get_option( MAP_PLUGIN_JS_DETECTED_FIELDS, '' ) );

	if ( ! $js_cookie_shield_detected_keys )
	{
		$js_cookie_shield_detected_keys = array();
	}

	// Merge detectableKeys
	if ( $detectable_keys_raw )
	{
		foreach( explode( ',', $detectable_keys_raw ) as $v )
		{
			$v = sanitize_text_field( $v );

			if ( $v && $v !== 'null' )
			{
				$js_cookie_shield_detected_keys[] = $v;
			}
		}
	}

	// Merge detectedKeys
	if ( $detected_keys_raw )
	{
		foreach( explode( ',', $detected_keys_raw ) as $v )
		{
			$v = sanitize_text_field( $v );
			if ( $v && $v !== 'null' ) {
				$js_cookie_shield_detected_keys[] = $v;
			}
		}
	}

	$js_cookie_shield_detected_keys = array_unique( array_filter( $js_cookie_shield_detected_keys ) );

	map_api_update_option( MAP_PLUGIN_JS_DETECTED_FIELDS, implode( ',', $js_cookie_shield_detected_keys ) );

	// Auto-activate matching cookie posts
	if ( ! empty( $js_cookie_shield_detected_keys ) )
	{
		map_api_auto_activate_cookie_posts( $js_cookie_shield_detected_keys );
	}

	return true;
}

/**
 * Finds all cookie posts (MAP_POST_TYPE_COOKIES) whose _map_api_key meta value
 * matches one of the provided keys, publishes them, and sets the _map_auto_detected
 * meta to 1. Uses direct DB queries for compatibility with SHORTINIT.
 * Also clears post-related transient cache after updating.
 *
 * @param array $keys  List of API keys to match against cookie posts.
 */
function map_api_auto_activate_cookie_posts( $keys )
{
	global $wpdb;

	if ( empty( $keys ) ) return;

	$placeholders = implode( ',', array_fill( 0, count( $keys ), '%s' ) );

	// Find posts matching the given api keys
	// Use call_user_func_array for compatibility with PHP 5.6 and WordPress < 4.8.3
	$sql = "SELECT p.ID
		FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
		WHERE p.post_type = %s
		AND p.post_status IN ('draft', 'publish')
		AND pm.meta_key = '_map_api_key'
		AND pm.meta_value IN ($placeholders)";

	$params = array_merge( array( $sql, MAP_POST_TYPE_COOKIES ), $keys );
	$query  = call_user_func_array( array( $wpdb, 'prepare' ), $params );

	$post_ids = $wpdb->get_col( $query );

	if( empty( $post_ids ) ) return;

	foreach( $post_ids as $post_id )
	{
		$post_id = intval( $post_id );

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

		if ( $meta_exists )
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
	}

	// Clear post transient cache
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_posts_%'" );
}

/**
 * Updates the cookie shield status in the plugin settings.
 * Writes to DB only if the current status differs from what is already stored,
 * avoiding unnecessary DB writes on every page load.
 * Tracks timestamps for both the missing and running states.
 *
 * @param array $the_settings        Current plugin settings, passed by reference.
 * @param int   $cookie_shield_detected  1 if CookieShield is detected and running, 0 if missing.
 */
function map_api_save_cookie_shield_status( &$the_settings, $cookie_shield_detected )
{
	if ( $cookie_shield_detected == 0 )
	{
		if ( $the_settings['missing_cookie_shield'] == false ||
			$the_settings['cookie_shield_running'] == true
		)
		{
			$the_settings['cookie_shield_running']           = false;
			$the_settings['missing_cookie_shield']           = true;
			$the_settings['missing_cookie_shield_timestamp'] = time();

			$the_settings['missing_api_support'] = false;
			$the_settings['missing_api_support_timestamp'] = null;

			map_api_update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_settings );
		}
	}
	elseif ( $cookie_shield_detected == 1 )
	{
		if ( $the_settings['cookie_shield_running'] == false ||
			$the_settings['missing_cookie_shield'] == true
		) {
			$the_settings['missing_cookie_shield']           = false;
			$the_settings['cookie_shield_running']           = true;
			$the_settings['cookie_shield_running_timestamp'] = time();

			$the_settings['missing_api_support'] = false;
			$the_settings['missing_api_support_timestamp'] = null;

			map_api_update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_settings );
		}
	}
}

/**
 * Updates the Google Consent Mode V2 status in the plugin settings.
 * Writes to DB only if the status, error code, or error motivation have changed
 * since the last save, avoiding unnecessary DB writes on every page load.
 * Tracks the timestamp of the first error detection and resets it when resolved.
 *
 * @param array  $the_settings    Current plugin settings, passed by reference.
 * @param int    $is_consent_valid  1 if Consent Mode is correctly configured, 0 if not.
 * @param string $error_motivation  Human-readable description of the error, empty if valid.
 * @param int    $error_code        Numeric error code, 0 if valid.
 */
function map_api_save_consent_mode_status( &$the_settings, $is_consent_valid, $error_motivation, $error_code )
{
	$cmode_v2_js_on_error = $is_consent_valid ? false : true;

	// Write to DB only if something has changed
	$has_changed =
		$the_settings['cmode_v2_js_on_error']        !== $cmode_v2_js_on_error ||
		$the_settings['cmode_v2_js_error_code']       !== $error_code ||
		$the_settings['cmode_v2_js_error_motivation'] !== $error_motivation;

	if ( !$has_changed ) return;

	if ( $the_settings['cmode_v2_js_on_error'] && ! $cmode_v2_js_on_error )
	{
		$the_settings['cmode_v2_js_on_error_first_relevation'] = 0;
	}

	if ( ! $the_settings['cmode_v2_js_on_error'] && $cmode_v2_js_on_error )
	{
		$the_settings['cmode_v2_js_on_error_first_relevation'] = time();
	}

	$the_settings['cmode_v2_js_on_error']        = $cmode_v2_js_on_error;
	$the_settings['cmode_v2_js_error_code']       = $error_code;
	$the_settings['cmode_v2_js_error_motivation'] = $error_motivation;

	map_api_update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_settings );
}