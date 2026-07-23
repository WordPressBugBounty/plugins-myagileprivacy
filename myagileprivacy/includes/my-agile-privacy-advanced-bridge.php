<?php
if( !defined( 'MAP_PLUGIN_NAME' ) )
{
    exit('Not allowed.');
}

// Comma-separated allow-list of hosts. Override in wp-config to add hosts.
if ( ! defined( 'MAP_ADVANCED_CDN_HOSTS' ) ) {
    define( 'MAP_ADVANCED_CDN_HOSTS', 'auth.formula-agile.com' );
}

// ── Configuration & authorization helpers ────────────────────────────────────

/**
 * Configuration flag check.
 */
function map_capability_enabled() {
    if ( ! class_exists( 'MyAgilePrivacy' ) ) { return false; }
    $s = MyAgilePrivacy::get_settings();
    return is_array( $s ) && isset( $s['pa'] ) && $s['pa'] == 1;
}

/**
 * Availability check.
 */
function map_advanced_is_authorized() {
    if ( ! class_exists( 'MyAgilePrivacy' ) ) { return false; }
    $rconfig = MyAgilePrivacy::get_rconfig();
    if ( ! is_array( $rconfig ) )                           { return false; }
    if ( ! isset( $rconfig['advanced_authorized'] ) )       { return false; }
    return $rconfig['advanced_authorized'] === true;
}

/**
 * Runtime availability check for the advanced module.
 */
function map_is_advanced_active() {
    if ( ! defined( 'MAP_ADVANCED_LOADED' ) || ! MAP_ADVANCED_LOADED ) { return false; }
    if ( ! map_capability_enabled() )                                   { return false; }
    if ( ! map_advanced_is_authorized() )                               { return false; }
    return true;
}

/**
 * Whether $host is in the trusted package-host allowlist ( MAP_ADVANCED_CDN_HOSTS ).
 * Case-insensitive exact match of $host against the host allow-list.
 */
function map_advanced_host_allowed( $host ) {
    if ( ! is_string( $host ) || $host === '' ) { return false; }
    $allowed = array_filter( array_map( 'trim', explode( ',', strtolower( MAP_ADVANCED_CDN_HOSTS ) ) ) );
    return in_array( strtolower( $host ), $allowed, true );
}

/**
 * Returns the package URL, or an empty string when unavailable.
 */
function map_advanced_get_download_url() {
    if ( ! class_exists( 'MyAgilePrivacy' ) ) { return ''; }
    $rconfig = MyAgilePrivacy::get_rconfig();
    if ( ! is_array( $rconfig ) || ! isset( $rconfig['advanced_download_url'] ) ) { return ''; }
    if ( ! is_string( $rconfig['advanced_download_url'] ) )                       { return ''; }
    $url    = esc_url_raw( $rconfig['advanced_download_url'] );
    $parsed = parse_url( $url );
    if ( ! is_array( $parsed ) || ! isset( $parsed['scheme'] ) || $parsed['scheme'] !== 'https' ) { return ''; }
    if ( ! isset( $parsed['host'] ) || ! map_advanced_host_allowed( $parsed['host'] ) ) { return ''; }
    return $url;
}

/**
 * Returns the expected version string, or an empty string when unavailable.
 */
function map_advanced_get_remote_version() {
    if ( ! class_exists( 'MyAgilePrivacy' ) ) { return ''; }
    $rconfig = MyAgilePrivacy::get_rconfig();
    if ( ! is_array( $rconfig ) || ! isset( $rconfig['advanced_version'] ) ) { return ''; }
    $version = $rconfig['advanced_version'];
    if ( ! is_string( $version ) )                              { return ''; }
    if ( ! preg_match( '/^[0-9][0-9a-zA-Z\.\-]+$/', $version ) ) { return ''; }
    return $version;
}

// ── Geo helpers ──────────────────────────────────────────────────────────────

/**
 * Availability check for the geo feature.
 */
function map_geo_authorized() {
    if ( ! class_exists( 'MyAgilePrivacy' ) ) { return false; }
    $rconfig = MyAgilePrivacy::get_rconfig();
    if ( ! is_array( $rconfig ) )           { return false; }
    if ( ! isset( $rconfig['allow_geo'] ) ) { return false; }
    return $rconfig['allow_geo'] === true;
}

/**
 * Runtime availability check for the geo feature.
 */
function map_geo_enabled() {
    return map_is_advanced_active() && map_geo_authorized();
}

// ── Bridge functions (base → advanced) ───────────────────────────────────────

/**
 * Resolves the visitor region.
 *
 * @return array
 */
function map_call_advanced_resolve_geo() {
    $fail_closed = array( 'country' => 'unknown', 'native' => 'block' );
    if ( ! map_is_advanced_active() )                                          { return $fail_closed; }
    if ( ! function_exists( 'map_advanced_instance' ) )                        { return $fail_closed; }
    $advanced = map_advanced_instance();
    if ( ! is_object( $advanced ) || ! method_exists( $advanced, 'resolve_geo' ) ) { return $fail_closed; }
    $result = $advanced->resolve_geo();
    if ( ! is_array( $result ) || ! isset( $result['country'] ) || ! isset( $result['native'] ) ) { return $fail_closed; }
    return $result;
}

/**
 * Returns the public URL of the advanced JS bundle, or '' when unavailable.
 *
 * @return string
 */
function map_call_advanced_get_bundle_url() {
    if ( ! map_is_advanced_active() )                                      { return ''; }
    if ( ! function_exists( 'map_advanced_instance' ) )                    { return ''; }
    $advanced = map_advanced_instance();
    if ( ! is_object( $advanced ) || ! method_exists( $advanced, 'get_bundle_url' ) ) { return ''; }
    return $advanced->get_bundle_url();
}

/**
 * Returns the URL of the advanced API endpoint, or '' when unavailable.
 *
 * @return string
 */
function map_call_advanced_get_api_url() {
    if ( ! map_is_advanced_active() )                                      { return ''; }
    if ( ! function_exists( 'map_advanced_instance' ) )                    { return ''; }
    $advanced = map_advanced_instance();
    if ( ! is_object( $advanced ) || ! method_exists( $advanced, 'get_api_url' ) ) { return ''; }
    return $advanced->get_api_url();
}

/**
 * Returns the consent-stats config array, or a disabled default when unavailable.
 *
 * @return array
 */
function map_call_advanced_get_stats_config() {
    $fail_closed = array( 'enabled' => false, 'variant' => 'default' );
    if ( ! map_is_advanced_active() )                                          { return $fail_closed; }
    if ( ! function_exists( 'map_advanced_instance' ) )                        { return $fail_closed; }
    $advanced = map_advanced_instance();
    if ( ! is_object( $advanced ) || ! method_exists( $advanced, 'get_stats_config' ) ) { return $fail_closed; }
    $result = $advanced->get_stats_config();
    if ( ! is_array( $result ) || ! isset( $result['enabled'] ) || ! isset( $result['variant'] ) ) { return $fail_closed; }
    return $result;
}

/**
 * Renders the consent-stats admin page. Returns true when the advanced page was
 * echoed, false when the caller should show its own fallback.
 *
 * @return bool
 */
function map_call_advanced_render_stats() {
    if ( ! map_is_advanced_active() )                                          { return false; }
    if ( ! function_exists( 'map_advanced_instance' ) )                        { return false; }
    $advanced = map_advanced_instance();
    if ( ! is_object( $advanced ) || ! method_exists( $advanced, 'render_stats' ) ) { return false; }
    $advanced->render_stats();
    return true;
}

