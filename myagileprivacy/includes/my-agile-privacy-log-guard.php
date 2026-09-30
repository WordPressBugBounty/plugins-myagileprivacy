<?php
if( !defined( 'ABSPATH' ) ) { exit; }

/**
 * My Agile Privacy - Log guard
 *
 * Shared helpers for the debug log: value redaction and directory protection.
 * Kept free of side effects so it can be required from both full WP and
 * SHORTINIT without pulling anything else in.
 *
 * Include it with require_once: a top-of-file function_exists() guard would not
 * help, because top level declarations are bound while the file is compiled,
 * before any statement in it runs.
 *
 * @package    MyAgilePrivacy
 * @subpackage MyAgilePrivacy/includes
 */

/**
 * Keys whose value is replaced before a log entry is written.
 *
 * The list the plugin already curates for what must not leave the site in clear
 * is the same set that must not land in a file either, so it is reused when the
 * class is available; the literals below are the SHORTINIT fallback.
 *
 * @return array
 */
function map_log_sensitive_keys()
{
	$always = array(
		'license_code',
		'customer_email',
		'website_name',
		'identity_name',
		'identity_address',
		'identity_vat_id',
		'identity_email',
		'dpo_name',
		'dpo_address',
		'dpo_email',
		'parse_config',
		'map_api_token',
	);

	if( method_exists( 'MyAgilePrivacy', 'get_do_not_send_in_clear_settings_key' ) )
	{
		$curated = MyAgilePrivacy::get_do_not_send_in_clear_settings_key();

		if( is_array( $curated ) )
		{
			$always = array_values( array_unique( array_merge( $curated, array( 'map_api_token' ) ) ) );
		}
	}

	return array(
		'always' => $always,
	);
}

/**
 * True while the secrets are being read: a log line written then is dropped.
 *
 * @param  bool|null $set
 * @return bool
 */
function map_log_preparing( $set = null )
{
	static $preparing = false;

	if( null !== $set )
	{
		$preparing = (bool) $set;
	}

	return $preparing;
}

/**
 * Secret values that must not appear in a log line, whatever key carries them.
 *
 * @return array
 */
function map_log_sensitive_values()
{
	static $by_site = array();

	$site = function_exists( 'get_current_blog_id' ) ? (int) get_current_blog_id() : 0;

	if( isset( $by_site[ $site ] ) )
	{
		return $by_site[ $site ];
	}

	$values = array();

	if( defined( 'AUTH_KEY' ) && defined( 'AUTH_SALT' ) && function_exists( 'hash_hmac' ) )
	{
		$values[] = hash_hmac( 'sha256', 'map_api_v1', AUTH_KEY . AUTH_SALT );
	}

	// set before reading the option, so a log written while it is read does not recurse
	$by_site[ $site ] = $values;

	global $wpdb;

	if( defined( 'MAP_PLUGIN_SETTINGS_FIELD' ) && isset( $wpdb ) && is_object( $wpdb ) && function_exists( 'maybe_unserialize' ) )
	{
		map_log_preparing( true );

		try
		{
			$raw = $wpdb->get_var( $wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
				MAP_PLUGIN_SETTINGS_FIELD
			) );

			$settings = ( null !== $raw ) ? maybe_unserialize( $raw ) : null;
		}
		finally
		{
			map_log_preparing( false );
		}

		foreach( array( 'license_code', 'customer_email', 'identity_email', 'dpo_email' ) as $key )
		{
			if( is_array( $settings ) && isset( $settings[ $key ] ) && is_string( $settings[ $key ] ) && strlen( $settings[ $key ] ) >= 6 )
			{
				$values[] = $settings[ $key ];
			}
		}
	}

	$by_site[ $site ] = $values;

	return $values;
}

/**
 * Returns a copy of the value with sensitive entries replaced.
 *
 * Operates on a copy on purpose: several callers log a structure they are about
 * to send, so mutating it in place would alter what goes out. Strings are masked
 * by value, other scalars returned untouched, and the depth limit keeps
 * self-referencing structures from recursing forever.
 *
 * @param  mixed $data
 * @param  int   $depth
 * @return mixed
 */
function map_log_redact( $data, $depth = 0 )
{
	if( is_string( $data ) )
	{
		$secrets = map_log_sensitive_values();

		return $secrets ? str_replace( $secrets, '[redacted]', $data ) : $data;
	}

	if( !is_array( $data ) && !is_object( $data ) )
	{
		return $data;
	}

	if( $depth > 8 )
	{
		return '[depth]';
	}

	$rules  = map_log_sensitive_keys();
	$source = is_object( $data ) ? get_object_vars( $data ) : $data;
	$out    = array();

	foreach( $source as $key => $value )
	{
		// The admin form posts every setting under its own name plus a suffix, so
		// the comparison is made on the name without it.
		$compare = $key;

		if( is_string( $compare ) && '_field' === substr( $compare, -6 ) )
		{
			$compare = substr( $compare, 0, -6 );
		}

		if( in_array( $compare, $rules['always'], true ) )
		{
			$out[ $key ] = '[redacted]';
			continue;
		}

		$out[ $key ] = map_log_redact( $value, $depth + 1 );
	}

	return $out;
}

/**
 * Drops the deny rules and the empty index into a directory, once per request.
 *
 * The condition is the presence of the two files rather than of the directory:
 * the directory already exists on every installation that has ever logged.
 *
 * @param  string $dir  directory path, trailing slash included
 * @return void
 */
function map_log_protect_dir( $dir )
{
	static $done = array();

	if( empty( $dir ) || isset( $done[ $dir ] ) || !function_exists( 'file_put_contents' ) )
	{
		return;
	}

	$done[ $dir ] = true;

	$htaccess = $dir . '.htaccess';

	if( !file_exists( $htaccess ) )
	{
		$rules  = '<IfModule mod_authz_core.c>' . PHP_EOL . 'Require all denied' . PHP_EOL . '</IfModule>' . PHP_EOL;
		$rules .= '<IfModule !mod_authz_core.c>' . PHP_EOL . 'Order allow,deny' . PHP_EOL . 'Deny from all' . PHP_EOL . '</IfModule>' . PHP_EOL;

		@file_put_contents( $htaccess, $rules );
	}

	$index = $dir . 'index.php';

	if( !file_exists( $index ) )
	{
		@file_put_contents( $index, '<?php' . PHP_EOL . '// Silence is golden.' . PHP_EOL );
	}
}
