<?php
if( !defined( 'MAP_PLUGIN_NAME' ) )
{
    exit('Not allowed.');
}

/**
 * Setup handler for the MAP Advanced plugin.
 */
final class MAP_Advanced_Trigger {

    /**
     * Runs the setup flow and returns a result array.
     *
     * @param bool   $refreshed
     * @param string $url_before  Package URL before the refresh, when $refreshed.
     * @return array
     */
    public static function run( $refreshed = false, $url_before = '' ) {
        if ( ! map_advanced_is_authorized() ) {
            return array(
                'success' => false,
                'action'  => 'error',
                'code'    => 'not_authorized',
                'message' => __( 'Advanced features are not authorized for this license.', 'MAP_txt' ),
            );
        }

        $url            = map_advanced_get_download_url();
        $remote_version = map_advanced_get_remote_version();

        if ( '' === $url ) {
            return array(
                'success' => false,
                'action'  => 'error',
                'code'    => 'missing_url',
                'message' => __( 'Download URL is not available.', 'MAP_txt' ),
            );
        }

        if ( '' === $remote_version ) {
            return array(
                'success' => false,
                'action'  => 'error',
                'code'    => 'missing_version',
                'message' => __( 'Remote version is not available.', 'MAP_txt' ),
            );
        }

        $installer     = new MAP_Package_Installer();
        $local_version = $installer->get_installed_advanced_version();

        if ( $installer->is_advanced_installed() && $local_version === $remote_version ) {
            if ( ! $installer->is_advanced_active() ) {
                $installer->activate_if_installed();
                return array(
                    'success' => true,
                    'action'  => 'activated',
                    'message' => __( 'Advanced plugin activated.', 'MAP_txt' ),
                );
            }
            return array(
                'success' => true,
                'action'  => 'already_current',
                'message' => __( 'Advanced plugin is already up to date.', 'MAP_txt' ),
            );
        }

        $blocker = $installer->auto_install_blocker();

        if ( '' === $blocker ) {
            // fresh data may change the verdict: nothing left to install, or no package at all
            if ( ! $refreshed && self::refresh_package_data() ) {
                return self::run( true, $url );
            }

            // a link the refresh has just changed is trusted, whatever the local clock says
            $ttl = ( $refreshed && $url !== $url_before ) ? null : map_advanced_download_url_ttl( $url );

            if ( null !== $ttl && $ttl < 60 ) {
                return array(
                    'success' => false,
                    'action'  => 'error',
                    'code'      => 'stale_url',
                    'attempted' => $refreshed,
                    'message'   => __( 'Download URL is not available.', 'MAP_txt' ),
                );
            }

            return $installer->install( $url, $remote_version );
        }

        return array(
            'success'        => true,
            'action'         => 'manual_download',
            'code'           => $blocker,
            'download_url'   => $url,
            'remote_version' => $remote_version,
            'message'        => __( 'Automatic installation is not available. Please download and install manually.', 'MAP_txt' ),
        );
    }

    /**
     * Syncs when the stored package URL is about to expire or says nothing. Returns true when a sync
     * was attempted: the server may not have answered, and the caller checks the URL again.
     *
     * @return bool
     */
    public static function refresh_package_data() {
        $ttl = map_advanced_download_url_ttl( map_advanced_get_download_url() );

        if ( ( null !== $ttl && $ttl >= 120 ) || ! class_exists( 'MyAgilePrivacyAdmin' ) ) {
            return false;
        }

        // the scheduled sync leaves multilingual sites alone, and so does this one
        if ( defined( 'DOING_CRON' ) && DOING_CRON && MyAgilePrivacy::check_if_multilang_enabled() ) {
            return false;
        }

        // one attempt per window: a sync that dies half way must not start again on every page
        if ( get_transient( 'map_adv_refresh_attempt' ) ) {
            return false;
        }

        set_transient( 'map_adv_refresh_attempt', 1, 540 );

        global $locale;
        $saved_locale = $locale;

        MyAgilePrivacy::update_option( MAP_PLUGIN_DO_SYNC_NOW, 1 );
        delete_transient( 'map_sync_pause' );

        try {
            $admin = new MyAgilePrivacyAdmin( MAP_PLUGIN_NAME, MAP_PLUGIN_VERSION, null );

            // another request is syncing: not an attempt, and the next page may try again
            if ( 'busy' === $admin->triggered_do_cron_sync() ) {
                delete_transient( 'map_adv_refresh_attempt' );

                if ( current_user_can( 'update_plugins' ) ) {
                    MAP_Admin_Notice_Manager::instance()->add( 'map_sync_busy', array(
                        'type'    => 'warning',
                        'message' => esc_html__( 'A synchronization is already running: try again in a few minutes.', 'MAP_txt' ),
                    ) );
                }

                $locale = $saved_locale;

                return false;
            }
        } catch ( Exception $e ) {
            // the caller reads the URL again and finds it unchanged
            MyAgilePrivacy::$doing_internal_write = false;
            if ( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) { MyAgilePrivacy::write_log( 'refresh error: ' . get_class( $e ) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() ); }
        } catch ( Error $e ) {
            // same, PHP 7+ only
            MyAgilePrivacy::$doing_internal_write = false;
            if ( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) { MyAgilePrivacy::write_log( 'refresh error: ' . get_class( $e ) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() ); }
        }

        // the sync switches the global locale to the banner's one and leaves it there
        $locale = $saved_locale;

        return true;
    }

    /**
     * Returns a package link that can still be used, or an empty string.
     *
     * @return string
     */
    public static function fresh_download_url() {
        $before = map_advanced_get_download_url();

        self::refresh_package_data();

        $url = map_advanced_get_download_url();

        // a link the refresh has just changed is trusted, whatever the local clock says
        if ( $url !== $before ) {
            return $url;
        }

        $ttl = map_advanced_download_url_ttl( $url );

        return ( null !== $ttl && $ttl < 60 ) ? '' : $url;
    }
}

// ── AJAX handler ──────────────────────────────────────────────────────────────

/**
 * AJAX: install or update the advanced plugin.
 */
function map_ajax_install_advanced() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'code' => 'forbidden' ), 403 );
    }
    check_ajax_referer( 'map_install_advanced', 'security' );

    $result = MAP_Advanced_Trigger::run();

    if ( isset( $result['success'] ) && $result['success'] ) {
        wp_send_json_success( $result );
    } else {
        wp_send_json_error( $result );
    }
}

add_action( 'wp_ajax_map_install_advanced', 'map_ajax_install_advanced' );
add_action( 'admin_init', 'map_sync_advanced_state_notice' );
add_action( 'admin_init', 'map_reconcile_advanced_state', 20 );
add_action( 'admin_post_map_advanced_download', 'map_advanced_download_redirect' );
add_action( 'admin_init', 'map_note_advanced_version', 5 );
add_action( 'upgrader_process_complete', 'map_note_advanced_version' );
add_action( 'my_agile_privacy_do_cron_sync_twice_day_hook', 'map_advanced_cron_maintenance', 5 );
add_action( 'current_screen', 'map_advanced_screen_maintenance' );
add_action( 'map_delayed_cache_flush', 'map_run_delayed_cache_flush', 10, 1 );

// ── Manual download ──────────────────────────────────────────────────────────

/**
 * Link of the notices to the package, without its nonce: the notice manager adds one when printing.
 */
function map_advanced_download_link() {
    return admin_url( 'admin-post.php?action=map_advanced_download' );
}

/**
 * Sends the browser to a package link that can still be used.
 */
function map_advanced_download_redirect() {
    if ( ! current_user_can( 'manage_options' ) || ! map_advanced_is_authorized() ) {
        wp_die( esc_html__( 'Advanced features are not authorized for this license.', 'MAP_txt' ), '', array( 'response' => 403 ) );
    }

    check_admin_referer( 'map_advanced_download' );

    $url = MAP_Advanced_Trigger::fresh_download_url();

    if ( '' === $url ) {
        wp_die( esc_html__( 'Download URL is not available.', 'MAP_txt' ), '', array( 'response' => 404 ) );
    }

    wp_redirect( $url );
    exit;
}

// ── Advanced version seen and stats layout ───────────────────────────────────

/**
 * Records the advanced version last seen on this site.
 */
function map_note_advanced_version() {
    global $wpdb;

    $on_upgrade = doing_action( 'upgrader_process_complete' );

    if ( doing_action( 'admin_init' ) ) {
        if ( ! is_user_logged_in() )                    { return; }
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX )    { return; }
    }

    if ( ! map_bot_detection_on() ) {
        map_drop_stats_bot_since();
    }

    $version = '';
    $source  = 'const';

    if ( $on_upgrade ) {
        if ( ! class_exists( 'MAP_Package_Installer' ) ) { return; }
        $installer = new MAP_Package_Installer();
        $version   = $installer->get_installed_advanced_version();
        $source    = 'hdr';
    } elseif ( defined( 'MAP_ADVANCED_VERSION' ) && is_string( MAP_ADVANCED_VERSION ) ) {
        $version = MAP_ADVANCED_VERSION;
    }

    if ( ! is_string( $version ) || ! preg_match( '/^[0-9A-Za-z.\-]{1,32}$/D', $version ) ) { return; }

    $now = time();
    $raw = $wpdb->get_var( $wpdb->prepare(
        "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
        MAP_PLUGIN_ADV_SEEN_VERSION
    ) );

    $seen = null;

    if ( is_string( $raw ) && preg_match( '/^([0-9A-Za-z.\-]{1,32})\|(hdr|const)\|(\d{1,10})$/D', $raw, $m ) ) {
        $seen = array( 'version' => $m[1], 'source' => $m[2], 't' => MyAgilePrivacy::intvalPhp8( $m[3] ) );
    }

    $value = $version . '|' . $source . '|' . $now;

    if ( $seen !== null && $seen['version'] === $version ) {
        // the header rewrite closes the race with a request that started before the files were copied
        if ( $on_upgrade ) {
            $wpdb->query( $wpdb->prepare(
                "UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s",
                $value,
                MAP_PLUGIN_ADV_SEEN_VERSION,
                $raw
            ) );
            MyAgilePrivacy::drop_option_cache( MAP_PLUGIN_ADV_SEEN_VERSION );
        }
        return;
    }

    $lower = ( $seen !== null && version_compare( $version, $seen['version'], '<' ) );

    // a request still running the old code reads the old constant after an update
    if ( $lower && ! $on_upgrade ) {
        if ( class_exists( 'MAP_Package_Installer' ) ) {
            $installer = new MAP_Package_Installer();
            if ( $installer->get_installed_advanced_version() !== $version ) { return; }
        } elseif ( $seen['source'] === 'hdr' && ( $now - $seen['t'] ) < 600 ) {
            return;
        }
    }

    if ( is_string( $raw ) ) {
        $won = $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s",
            $value,
            MAP_PLUGIN_ADV_SEEN_VERSION,
            $raw
        ) );
    } else {
        $won = $wpdb->query( $wpdb->prepare(
            "INSERT IGNORE INTO {$wpdb->options} ( option_name, option_value, autoload ) VALUES ( %s, %s, 'no' )",
            MAP_PLUGIN_ADV_SEEN_VERSION,
            $value
        ) );
    }

    MyAgilePrivacy::drop_option_cache( MAP_PLUGIN_ADV_SEEN_VERSION );

    if ( 1 !== $won ) { return; }

    $rc = ( false !== strpos( $version, '-rc' ) ) || ( $seen !== null && false !== strpos( $seen['version'], '-rc' ) );

    if ( $lower || $rc ) {
        map_drop_stats_bot_since();
    }

    map_advanced_version_cache_flush( $version );
}

/**
 * Clears the cached head once per network and books the delayed page purge.
 *
 * @param string $version Advanced version just recorded.
 */
function map_advanced_version_cache_flush( $version ) {
    global $wpdb;

    $table = $wpdb->base_prefix . 'options';

    $raw = $wpdb->get_var( $wpdb->prepare(
        "SELECT option_value FROM {$table} WHERE option_name = %s LIMIT 1",
        'map_adv_cache_flushed_version'
    ) );

    $flushed = is_string( $raw ) ? preg_replace( '/\|d$/D', '', $raw ) : null;

    if ( $flushed !== $version ) {
        if ( is_string( $raw ) ) {
            $won = $wpdb->query( $wpdb->prepare(
                "UPDATE {$table} SET option_value = %s WHERE option_name = %s AND option_value = %s",
                $version,
                'map_adv_cache_flushed_version',
                $raw
            ) );
        } else {
            $won = $wpdb->query( $wpdb->prepare(
                "INSERT IGNORE INTO {$table} ( option_name, option_value, autoload ) VALUES ( %s, %s, 'no' )",
                'map_adv_cache_flushed_version',
                $version
            ) );
        }

        if ( 1 === $won && class_exists( 'MyAgilePrivacy' ) ) {
            MyAgilePrivacy::flush_json_cache();
        }
    }

    wp_schedule_single_event( time() + 120, 'map_delayed_cache_flush', array( $version ) );
}

/**
 * Delayed purge after an advanced version change: the pages of this site, and once per network the cached head.
 *
 * @param string $version Advanced version the event was booked for.
 */
function map_run_delayed_cache_flush( $version ) {
    global $wpdb;

    if ( ! is_string( $version ) || ! preg_match( '/^[0-9A-Za-z.\-]{1,32}$/D', $version ) ) { return; }
    if ( ! class_exists( 'MyAgilePrivacy' ) )                                              { return; }

    $table = $wpdb->base_prefix . 'options';

    $won = $wpdb->query( $wpdb->prepare(
        "UPDATE {$table} SET option_value = %s WHERE option_name = %s AND option_value = %s",
        $version . '|d',
        'map_adv_cache_flushed_version',
        $version
    ) );

    if ( 1 === $won ) {
        MyAgilePrivacy::flush_json_cache();
    }

    // on a network every blog runs its own event and each plugin clears the blog of the request
    if ( 1 === $won || is_multisite() ) {
        MyAgilePrivacy::tryOtherPluginCacheClear( 'network' );
    }

    MyAgilePrivacy::tryOtherPluginCacheClear( 'blog' );
}

/**
 * Removes a stored stats date, when present.
 */
function map_drop_stats_bot_since() {
    global $wpdb;

    $raw = $wpdb->get_var( $wpdb->prepare(
        "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
        MAP_PLUGIN_STATS_BOT_SINCE
    ) );

    if ( ! is_string( $raw ) ) { return; }

    $wpdb->query( $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s",
        MAP_PLUGIN_STATS_BOT_SINCE,
        $raw
    ) );
    MyAgilePrivacy::drop_option_cache( MAP_PLUGIN_STATS_BOT_SINCE );
}

/**
 * Early step of the twice-daily sync event.
 */
function map_advanced_cron_maintenance() {
    map_note_advanced_version();

    if ( ! defined( 'DOING_CRON' ) || ! DOING_CRON ) { return; }

    map_call_advanced_stats_migrate_schedule( 'cron' );
}

/**
 * Keeps the stats layout in sync from the plugin screens.
 */
function map_advanced_screen_maintenance() {
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX )                                       { return; }
    if ( ! class_exists( 'MyAgilePrivacyAdmin' ) || ! MyAgilePrivacyAdmin::is_map_screen() ) { return; }
    if ( ! class_exists( 'MyAgilePrivacy' ) || ! MyAgilePrivacy::map_can_edit() )     { return; }
    if ( ! map_stats_enabled() )                                                       { return; }

    map_call_advanced_stats_migrate_schedule( 'screen' );
}

// ── State reconciliation ─────────────────────────────────────────────────────

/**
 * Keeps the stored state consistent with the current entitlement: closes a
 * split test left running, and applies the retention policy to the collected
 * rows, which outlive the feature that produced them.
 * Also records that a split test was applied.
 * Signed-in requests only: admin_init also fires for logged-out admin-ajax
 * callers, and the closing path clears third-party page caches.
 * No capability check on purpose, or a site whose editors never hold
 * manage_options would never reconcile.
 */
function map_reconcile_advanced_state() {
    if ( ! is_user_logged_in() ) { return; }

    if ( function_exists( 'map_call_advanced_close_ab_test' ) ) {
        map_call_advanced_close_ab_test();
    }

    if ( function_exists( 'map_call_advanced_prune_stats' ) ) {
        map_call_advanced_prune_stats();
    }

    if ( function_exists( 'map_call_advanced_mark_ab_applied' ) ) {
        map_call_advanced_mark_ab_applied();
    }
}

// ── Notice synchronization ───────────────────────────────────────────────────

/**
 * Message of the install error notice.
 */
function map_advanced_install_error_message( $err_msg ) {
    // the reason often ends with its own full stop, and the sentence adds one
    return sprintf(
        esc_html__( 'My Agile Privacy® Advanced could not be installed automatically: %s. A new attempt will be made in 24 hours.', 'MAP_txt' ),
        esc_html( rtrim( trim( (string) $err_msg ), '.' ) )
    );
}

/**
 * Notice action that downloads the package, or none while the license carries no package.
 */
function map_advanced_download_actions() {
    if ( '' === map_advanced_get_download_url() ) {
        return array();
    }

    return array(
        array(
            'label'        => __( 'Download manually', 'MAP_txt' ),
            'url'          => map_advanced_download_link(),
            'nonce_action' => 'map_advanced_download',
        ),
    );
}

/**
 * Keeps the advanced module state in sync on admin_init.
 */
function map_sync_advanced_state_notice() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }

    $manager = MAP_Admin_Notice_Manager::instance();

    // Preliminary checks.
    if ( ! map_capability_enabled() || ! map_advanced_is_authorized() ) {
        $manager->remove( 'map_advanced_state' );
        delete_option( MAP_PLUGIN_ADVANCED_INSTALL_ERROR );
        return;
    }

    // Version alignment check.
    $installer    = new MAP_Package_Installer();
    $base_version = defined( 'MAP_PLUGIN_VERSION' ) ? MAP_PLUGIN_VERSION : '';
    $local_adv    = $installer->get_installed_advanced_version();
    $base_behind  = ( '' !== $base_version && '' !== $local_adv &&
                      version_compare( $base_version, $local_adv, '<' ) );

    if ( $base_behind ) {
        $manager->add( 'map_advanced_state', array(
            'type'        => 'warning',
            'message'     => sprintf(
                esc_html__( 'My Agile Privacy® base plugin (v%1$s) is older than the Advanced plugin (v%2$s). Please update the base plugin to restore full functionality.', 'MAP_txt' ),
                esc_html( $base_version ),
                esc_html( $local_adv )
            ),
            'dismissible' => false,
        ) );
        return;
    }

    // Required data not available yet.
    if ( '' === map_advanced_get_download_url() || '' === map_advanced_get_remote_version() ) {
        $manager->remove( 'map_advanced_state' );
        delete_option( MAP_PLUGIN_ADVANCED_INSTALL_ERROR );
        return;
    }

    // Recent error state.
    $error_opt = get_option( MAP_PLUGIN_ADVANCED_INSTALL_ERROR, array() );
    $had_error = is_array( $error_opt ) && ! empty( $error_opt['timestamp'] );

    // Installed since the error by other means, by hand for instance: the error no longer holds,
    // and run() below activates it if needed.
    if ( $had_error && $installer->is_advanced_installed() && $local_adv === map_advanced_get_remote_version() ) {
        delete_option( MAP_PLUGIN_ADVANCED_INSTALL_ERROR );
        $manager->remove( 'map_advanced_state' );
        $had_error = false;
    }

    if ( $had_error ) {
        $error_ts = (int) $error_opt['timestamp'];
        $age      = time() - $error_ts;
        if ( $age < DAY_IN_SECONDS ) {
            $announced = isset( $error_opt['announced'] ) ? (int) $error_opt['announced'] : 0;
            if ( $announced < $error_ts ) {
                if ( ! $manager->has( 'map_advanced_state' ) ) {
                    $err_msg = isset( $error_opt['message'] ) ? $error_opt['message'] : __( 'Unknown error.', 'MAP_txt' );
                    $manager->add( 'map_advanced_state', array(
                        'type'        => 'error',
                        'message'     => map_advanced_install_error_message( $err_msg ),
                        'dismissible' => true,
                        'actions'     => map_advanced_download_actions(),
                    ) );
                }
                $error_opt['announced'] = $error_ts;
                update_option( MAP_PLUGIN_ADVANCED_INSTALL_ERROR, $error_opt );
            }
            return;
        }
        // Stale error: clear and continue.
        delete_option( MAP_PLUGIN_ADVANCED_INSTALL_ERROR );
        $had_error = false;
    }

    // Run the setup flow.
    try {
        $result = MAP_Advanced_Trigger::run();
    } catch ( Exception $e ) {
        $result = array(
            'success' => false,
            'action'  => 'error',
            'code'    => 'exception',
            'message' => $e->getMessage(),
        );
    } catch ( Error $e ) {
        // PHP 7+ only: a TypeError inside the sync would otherwise skip the 24 hour wait
        $result = array(
            'success' => false,
            'action'  => 'error',
            'code'    => 'exception',
            'message' => $e->getMessage(),
        );
    }

    // A link still unusable after a refresh: the next attempt window, three times, then the 24 hour wait.
    if ( isset( $result['code'] ) && 'stale_url' === $result['code'] ) {
        $failures = (int) get_transient( 'map_adv_stale_count' );

        if ( ! empty( $result['attempted'] ) ) {
            $failures++;
            set_transient( 'map_adv_stale_count', $failures, DAY_IN_SECONDS );
        }

        if ( $failures < 3 ) {
            $manager->remove( 'map_advanced_state' );
            return;
        }

        delete_transient( 'map_adv_stale_count' );
    }

    // Data not ready, possibly after the refresh inside run(): nothing to record.
    if ( isset( $result['code'] ) && in_array( $result['code'], array( 'not_authorized', 'missing_url', 'missing_version' ), true ) ) {
        $manager->remove( 'map_advanced_state' );
        return;
    }

    // Hard error: record state, show notice.
    if ( ! isset( $result['success'] ) || ! $result['success'] ) {
        $err_msg = isset( $result['message'] ) ? $result['message'] : __( 'Unknown error.', 'MAP_txt' );
        $now     = time();
        update_option( MAP_PLUGIN_ADVANCED_INSTALL_ERROR, array(
            'code'      => isset( $result['code'] ) ? $result['code'] : 'unknown',
            'message'   => $err_msg,
            'timestamp' => $now,
            'announced' => $now,
        ) );
        $manager->add( 'map_advanced_state', array(
            'type'        => 'error',
            'message'     => map_advanced_install_error_message( $err_msg ),
            'dismissible' => true,
            'actions'     => map_advanced_download_actions(),
        ) );
        return;
    }

    // Manual setup path.
    if ( isset( $result['action'] ) && 'manual_download' === $result['action'] ) {
        $code   = isset( $result['code'] ) ? $result['code'] : '';

        if ( 'file_mods_disabled' === $code ) {
            $message  = esc_html__( 'File modifications are disabled on this site, so My Agile Privacy® Advanced cannot be installed automatically.', 'MAP_txt' );
            $message .= ' ' . esc_html__( 'Uploading the package from the dashboard is blocked as well: download it and copy it into the plugins folder over FTP.', 'MAP_txt' );
        } elseif ( 'network_admin_required' === $code ) {
            $message = esc_html__( 'My Agile Privacy® Advanced cannot be installed automatically: on a multisite network only a network administrator can install plugins.', 'MAP_txt' );
        } elseif ( 'filesystem' === $code ) {
            $message  = esc_html__( 'My Agile Privacy® Advanced is available but cannot be installed automatically due to filesystem restrictions.', 'MAP_txt' );
            $message .= ' ' . esc_html__( 'You can still install it from the Plugins screen: WordPress will ask for your FTP credentials.', 'MAP_txt' );
        } else {
            $message = esc_html__( 'Automatic installation is not available. Please download and install manually.', 'MAP_txt' );
        }

        $manager->add( 'map_advanced_state', array(
            'type'        => 'info',
            'message'     => $message,
            'dismissible' => false,
            'actions'     => ( 'network_admin_required' !== $code ) ? map_advanced_download_actions() : array(),
        ) );
        return;
    }

    // Success: clean state.
    delete_transient( 'map_adv_stale_count' );

    if ( $had_error ) {
        delete_option( MAP_PLUGIN_ADVANCED_INSTALL_ERROR );
    }
    $manager->remove( 'map_advanced_state' );
}
