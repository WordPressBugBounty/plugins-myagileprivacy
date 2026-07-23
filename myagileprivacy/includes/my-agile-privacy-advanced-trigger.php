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
     * @return array
     */
    public static function run() {
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

        if ( $installer->can_auto_install() ) {
            return $installer->install( $url );
        }

        return array(
            'success'        => true,
            'action'         => 'manual_download',
            'download_url'   => $url,
            'remote_version' => $remote_version,
            'message'        => __( 'Automatic installation is not available. Please download and install manually.', 'MAP_txt' ),
        );
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

// ── Notice synchronization ───────────────────────────────────────────────────

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
        return;
    }

    // Recent error state.
    $error_opt = get_option( MAP_PLUGIN_ADVANCED_INSTALL_ERROR, array() );
    $had_error = is_array( $error_opt ) && ! empty( $error_opt['timestamp'] );

    if ( $had_error ) {
        $age = time() - (int) $error_opt['timestamp'];
        if ( $age < DAY_IN_SECONDS ) {
            if ( ! $manager->has( 'map_advanced_state' ) ) {
                $err_msg = isset( $error_opt['message'] ) ? $error_opt['message'] : __( 'Unknown error.', 'MAP_txt' );
                $manager->add( 'map_advanced_state', array(
                    'type'        => 'error',
                    'message'     => sprintf(
                        esc_html__( 'My Agile Privacy® Advanced could not be installed automatically: %s. A new attempt will be made in 24 hours.', 'MAP_txt' ),
                        esc_html( $err_msg )
                    ),
                    'dismissible' => true,
                ) );
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
    }

    // Hard error: record state, show notice.
    if ( ! isset( $result['success'] ) || ! $result['success'] ) {
        $err_msg = isset( $result['message'] ) ? $result['message'] : __( 'Unknown error.', 'MAP_txt' );
        update_option( MAP_PLUGIN_ADVANCED_INSTALL_ERROR, array(
            'code'      => isset( $result['code'] ) ? $result['code'] : 'unknown',
            'message'   => $err_msg,
            'timestamp' => time(),
        ) );
        $manager->add( 'map_advanced_state', array(
            'type'        => 'error',
            'message'     => sprintf(
                esc_html__( 'My Agile Privacy® Advanced could not be installed automatically: %s. A new attempt will be made in 24 hours.', 'MAP_txt' ),
                esc_html( $err_msg )
            ),
            'dismissible' => true,
        ) );
        return;
    }

    // Manual setup path.
    if ( isset( $result['action'] ) && 'manual_download' === $result['action'] ) {
        $dl_url  = isset( $result['download_url'] ) ? esc_url( $result['download_url'] ) : '';
        $message = esc_html__( 'My Agile Privacy® Advanced is available but cannot be installed automatically due to filesystem restrictions.', 'MAP_txt' );
        if ( $dl_url ) {
            $message .= ' <a href="' . $dl_url . '" target="_blank" rel="noopener noreferrer">'
                      . esc_html__( 'Download manually', 'MAP_txt' )
                      . '</a>.';
        }
        $manager->add( 'map_advanced_state', array(
            'type'        => 'info',
            'message'     => $message,
            'dismissible' => true,
        ) );
        return;
    }

    // Success: clean state.
    if ( $had_error ) {
        delete_option( MAP_PLUGIN_ADVANCED_INSTALL_ERROR );
    }
    $manager->remove( 'map_advanced_state' );
}
