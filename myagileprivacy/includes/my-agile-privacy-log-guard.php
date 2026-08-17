<?php

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
 * Returns a copy of the value with sensitive entries replaced.
 *
 * Operates on a copy on purpose: several callers log a structure they are about
 * to send, so mutating it in place would alter what goes out. Scalars are
 * returned untouched and the depth limit keeps self-referencing structures from
 * recursing forever.
 *
 * @param  mixed $data
 * @param  int   $depth
 * @return mixed
 */
function map_log_redact( $data, $depth = 0 )
{
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

	if( empty( $dir ) || isset( $done[ $dir ] ) )
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
