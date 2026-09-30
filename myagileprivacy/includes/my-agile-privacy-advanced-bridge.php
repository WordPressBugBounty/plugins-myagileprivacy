<?php
if( !defined( 'MAP_PLUGIN_NAME' ) )
{
    exit('Not allowed.');
}

// Comma-separated allow-list of hosts. Override in wp-config to add hosts.
if ( ! defined( 'MAP_ADVANCED_CDN_HOSTS' ) ) {
    define( 'MAP_ADVANCED_CDN_HOSTS', 'dist.myagileprivacy.com' );
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
    if ( ! is_string( MAP_ADVANCED_CDN_HOSTS ) ) { return false; }
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
 * Seconds the package URL can still be used for, or null when the URL does not say.
 */
function map_advanced_download_url_ttl( $url ) {
    if ( ! is_string( $url ) || ! preg_match( '/[?&]token=([A-Za-z0-9_\-]+)\./', $url, $m ) ) { return null; }
    $payload = json_decode( (string) base64_decode( strtr( $m[1], '-_', '+/' ) ), true );
    if ( ! is_array( $payload ) || ! isset( $payload['exp'] ) || ! MyAgilePrivacy::isNumericPhp8( $payload['exp'] ) ) { return null; }
    $ttl = (float) rtrim( (string) $payload['exp'], " \t\n\r\v\f" ) - time();
    // the server signs for ten minutes: a longer life means a local clock running behind
    return ( $ttl > 660 ) ? null : MyAgilePrivacy::intvalPhp8( $ttl );
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

// ── Per-feature helpers ──────────────────────────────────────────────────────

/**
 * Availability check for the geo feature.
 */
function map_geo_authorized() {
    if ( ! class_exists( 'MyAgilePrivacy' ) ) { return false; }
    $rconfig = MyAgilePrivacy::get_rconfig();
    if ( ! is_array( $rconfig ) )          { return false; }
    if ( ! isset( $rconfig['geo_auth'] ) ) { return false; }
    return $rconfig['geo_auth'] === true;
}

/**
 * Runtime availability check for the geo feature.
 */
function map_geo_enabled() {
    return map_is_advanced_active() && map_geo_authorized();
}

/**
 * Availability check for the stats feature.
 */
function map_stats_authorized() {
    if ( ! class_exists( 'MyAgilePrivacy' ) ) { return false; }
    $rconfig = MyAgilePrivacy::get_rconfig();
    if ( ! is_array( $rconfig ) )            { return false; }
    if ( ! isset( $rconfig['stats_auth'] ) ) { return false; }
    return $rconfig['stats_auth'] === true;
}

/**
 * Runtime availability check for the stats feature.
 */
function map_stats_enabled() {
    return map_is_advanced_active() && map_stats_authorized();
}

/**
 * Availability check for the banner split feature.
 */
function map_abtest_authorized() {
    if ( ! class_exists( 'MyAgilePrivacy' ) ) { return false; }
    $rconfig = MyAgilePrivacy::get_rconfig();
    if ( ! is_array( $rconfig ) )             { return false; }
    if ( ! isset( $rconfig['abtest_auth'] ) ) { return false; }
    return $rconfig['abtest_auth'] === true;
}

/**
 * Runtime availability check for the banner split feature.
 */
function map_abtest_enabled() {
    return map_is_advanced_active() && map_abtest_authorized() && map_stats_authorized();
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
    if ( ! map_stats_enabled() )                                               { return $fail_closed; }
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
    if ( ! map_stats_enabled() )                                               { return false; }
    if ( ! function_exists( 'map_advanced_instance' ) )                        { return false; }
    $advanced = map_advanced_instance();
    if ( ! is_object( $advanced ) || ! method_exists( $advanced, 'render_stats' ) ) { return false; }
    $advanced->render_stats();
    return true;
}

/**
 * Returns the banner configuration to serve, or the saved one when unavailable.
 *
 * @param string $cookie_value Value carried by the visitor.
 * @param string $preview      Requested preview identifier, or ''.
 * @param string $warm         Requested identifier for cache generation, or ''.
 * @return array
 */
function map_call_advanced_get_banner_variant( $cookie_value, $preview, $warm ) {
    $fail_closed = array(
        'id'        => 'default',
        'slot'      => 0,
        'overrides' => array(),
        'public'    => array( 'active' => false, 'ids' => array(), 'cookie_days' => 30, 'preview' => false, 'variant_looks' => array() ),
    );

    if ( ! map_abtest_enabled() )                                              { return $fail_closed; }
    if ( ! function_exists( 'map_advanced_instance' ) )                        { return $fail_closed; }
    $advanced = map_advanced_instance();
    if ( ! is_object( $advanced ) || ! method_exists( $advanced, 'get_banner_variant' ) ) { return $fail_closed; }

    $result = $advanced->get_banner_variant( $cookie_value, $preview, $warm );

    if ( ! is_array( $result ) )                                               { return $fail_closed; }
    if ( ! isset( $result['id'] ) || ! is_string( $result['id'] ) )             { return $fail_closed; }
    if ( ! preg_match( '/^[a-z0-9]{1,32}$/', $result['id'] ) )                  { return $fail_closed; }
    if ( ! isset( $result['slot'] ) || ! is_numeric( $result['slot'] ) )        { return $fail_closed; }

    $slot = intval( $result['slot'] );

    if ( $slot < 0 || $slot > 4 )                                              { return $fail_closed; }
    if ( ( $slot === 0 ) !== ( $result['id'] === 'default' ) )                  { return $fail_closed; }
    if ( ! isset( $result['overrides'] ) || ! is_array( $result['overrides'] ) ) { return $fail_closed; }
    if ( ! isset( $result['public'] ) || ! is_array( $result['public'] ) )      { return $fail_closed; }

    $public = $result['public'];

    if ( ! isset( $public['active'] ) || ! is_bool( $public['active'] ) )       { return $fail_closed; }
    if ( ! isset( $public['preview'] ) || ! is_bool( $public['preview'] ) )     { return $fail_closed; }
    if ( ! isset( $public['ids'] ) || ! is_array( $public['ids'] ) )            { return $fail_closed; }
    if ( ! isset( $public['cookie_days'] ) || ! is_numeric( $public['cookie_days'] ) ) { return $fail_closed; }

    $days = intval( $public['cookie_days'] );

    if ( $days < 1 || $days > 365 )                                            { return $fail_closed; }

    $ids = array();

    foreach ( $public['ids'] as $id ) {
        if ( ! is_string( $id ) || ! preg_match( '/^[a-z0-9]{1,32}$/', $id ) )  { return $fail_closed; }
        $ids[] = $id;
    }

    // A malformed map is dropped, not fatal: the worst case is today's behaviour.
    $looks = array();

    if ( isset( $public['variant_looks'] ) && is_array( $public['variant_looks'] ) ) {
        $sizes = MyAgilePrivacy::get_banner_variant_enums();
        $sizes = isset( $sizes['cookie_banner_size'] ) ? $sizes['cookie_banner_size'] : array();

        foreach ( $public['variant_looks'] as $token => $raw ) {
            if ( ! is_string( $token ) || ! in_array( $token, $ids, true ) )    { continue; }
            if ( ! is_array( $raw ) )                                          { continue; }
            if ( ! isset( $raw['title_is_on'] ) || ! is_bool( $raw['title_is_on'] ) ) { continue; }
            if ( ! isset( $raw['cookie_banner_size'] ) || ! is_string( $raw['cookie_banner_size'] ) ) { continue; }
            if ( ! empty( $sizes ) && ! in_array( $raw['cookie_banner_size'], $sizes, true ) ) { continue; }

            $looks[ $token ] = array(
                'title_is_on'        => $raw['title_is_on'],
                'cookie_banner_size' => $raw['cookie_banner_size'],
            );
        }
    }

    return array(
        'id'        => $result['id'],
        'slot'      => $slot,
        'overrides' => $result['overrides'],
        'public'    => array(
            'active'        => $public['active'],
            'ids'           => $ids,
            'cookie_days'   => $days,
            'preview'       => $public['preview'],
            'variant_looks' => $looks,
        ),
    );
}

/**
 * Renders the A/B test admin page. Returns true when the advanced page was
 * echoed, false when the caller should show its own fallback.
 *
 * @return bool
 */
function map_call_advanced_render_ab_test() {
    if ( ! map_abtest_enabled() )                                              { return false; }
    if ( ! function_exists( 'map_advanced_instance' ) )                        { return false; }
    $advanced = map_advanced_instance();
    if ( ! is_object( $advanced ) || ! method_exists( $advanced, 'render_ab_test' ) ) { return false; }
    $advanced->render_ab_test();
    return true;
}

/**
 * Returns the A/B test state used by the settings page.
 *
 * @return array
 */
function map_call_advanced_get_ab_state() {
    $fail_closed = array( 'running' => false, 'panel_url' => '' );

    if ( ! map_abtest_enabled() )                                              { return $fail_closed; }
    if ( ! function_exists( 'map_advanced_instance' ) )                        { return $fail_closed; }
    $advanced = map_advanced_instance();
    if ( ! is_object( $advanced ) || ! method_exists( $advanced, 'get_ab_state' ) ) { return $fail_closed; }

    $result = $advanced->get_ab_state();

    if ( ! is_array( $result ) )                                               { return $fail_closed; }
    if ( ! isset( $result['running'] ) || ! is_bool( $result['running'] ) )     { return $fail_closed; }
    if ( ! isset( $result['panel_url'] ) || ! is_string( $result['panel_url'] ) || $result['panel_url'] === '' ) { return $fail_closed; }

    return array(
        'running'   => $result['running'],
        'panel_url' => $result['panel_url'],
    );
}

/**
 * Reconciles the stored split state with the current entitlement.
 *
 * @return bool
 */
function map_call_advanced_close_ab_test() {
    if ( ! defined( 'MAP_ADVANCED_LOADED' ) || ! MAP_ADVANCED_LOADED ) { return false; }
    if ( ! function_exists( 'map_advanced_instance' ) )                { return false; }
    $advanced = map_advanced_instance();
    if ( ! is_object( $advanced ) || ! method_exists( $advanced, 'close_ab_test' ) ) { return false; }

    return ( true === $advanced->close_ab_test() );
}

/**
 * Applies the retention policy to the collected stats rows.
 *
 * @return void
 */
function map_call_advanced_prune_stats() {
    if ( ! defined( 'MAP_ADVANCED_LOADED' ) || ! MAP_ADVANCED_LOADED ) { return; }
    if ( ! function_exists( 'map_advanced_instance' ) )                { return; }
    $advanced = map_advanced_instance();
    if ( ! is_object( $advanced ) || ! method_exists( $advanced, 'prune_stats' ) ) { return; }

    $advanced->prune_stats();
}

/**
 * Declares what this installation believes it is allowed to do.
 *
 * @return array
 */
function map_call_advanced_get_usage_facts() {
    $fail_closed = array();

    if ( ! defined( 'MAP_ADVANCED_LOADED' ) || ! MAP_ADVANCED_LOADED ) { return $fail_closed; }
    if ( ! function_exists( 'map_advanced_instance' ) )                { return $fail_closed; }
    $advanced = map_advanced_instance();
    if ( ! is_object( $advanced ) || ! method_exists( $advanced, 'get_usage_facts' ) ) { return $fail_closed; }

    $result = $advanced->get_usage_facts();

    if ( ! is_array( $result ) ) { return $fail_closed; }

    $out  = array();
    $keys = array( 'gate_stats', 'gate_geo', 'gate_abtest', 'stats_recent' );

    foreach ( $keys as $key ) {
        if ( ! isset( $result[ $key ] ) ) { return $fail_closed; }
        $out[ $key ] = ( intval( $result[ $key ] ) === 1 ) ? 1 : 0;
    }

    return $out;
}

/**
 * Compact usage figures for the periodic summary, as a JSON string.
 *
 * @return string
 */
function map_call_advanced_get_usage_extract() {
    if ( ! defined( 'MAP_ADVANCED_LOADED' ) || ! MAP_ADVANCED_LOADED ) { return ''; }
    if ( ! function_exists( 'map_advanced_instance' ) )                { return ''; }
    $advanced = map_advanced_instance();
    if ( ! is_object( $advanced ) || ! method_exists( $advanced, 'get_usage_extract' ) ) { return ''; }

    try {
        $result = $advanced->get_usage_extract();
    } catch ( Exception $e ) {
        return '';
    } catch ( Error $e ) {
        return '';
    }

    if ( ! is_array( $result ) || ! isset( $result['v'] ) || $result['v'] !== 1 ) { return ''; }
    if ( ! isset( $result['day'] ) || ! is_string( $result['day'] ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $result['day'] ) ) { return ''; }

    $out = array( 'v' => 1, 'day' => $result['day'] );

    if ( isset( $result['st'] ) ) {
        $st = $result['st'];

        if ( ! is_array( $st ) || ! isset( $st['on'] ) || ! in_array( $st['on'], array( 0, 1, 2 ), true ) ) { return ''; }

        $out['st'] = array( 'on' => $st['on'] );

        if ( $st['on'] === 1 ) {
            foreach ( array( 'd', 'm' ) as $device ) {
                if ( ! isset( $st[ $device ] ) || ! is_array( $st[ $device ] ) || count( $st[ $device ] ) !== 5 ) { return ''; }

                $values = array();

                for ( $n = 0; $n < 5; $n++ ) {
                    if ( ! isset( $st[ $device ][ $n ] ) || ! is_int( $st[ $device ][ $n ] ) ) { return ''; }
                    if ( $st[ $device ][ $n ] < 0 || $st[ $device ][ $n ] > 2147483647 )       { return ''; }

                    $values[] = ( $n > 0 && $st[ $device ][ $n ] > $values[0] ) ? $values[0] : $st[ $device ][ $n ];
                }

                $out['st'][ $device ] = $values;
            }
        } elseif ( isset( $st['d'] ) || isset( $st['m'] ) ) {
            return '';
        }
    }

    if ( isset( $result['ab'] ) ) {
        if ( $result['ab'] !== 0 && $result['ab'] !== 1 ) { return ''; }

        $out['ab'] = $result['ab'];
    }

    $json = wp_json_encode( $out );

    return ( is_string( $json ) && strlen( $json ) <= 2048 ) ? $json : '';
}

/**
 * Sets the permanent mark of an applied test.
 *
 * @return void
 */
function map_call_advanced_mark_ab_applied() {
    if ( ! map_abtest_enabled() )                                              { return; }
    if ( ! function_exists( 'map_advanced_instance' ) )                        { return; }
    $advanced = map_advanced_instance();
    if ( ! is_object( $advanced ) || ! method_exists( $advanced, 'mark_ab_applied' ) ) { return; }

    try {
        $advanced->mark_ab_applied();
    } catch ( Exception $e ) {
        return;
    } catch ( Error $e ) {
        return;
    }
}

