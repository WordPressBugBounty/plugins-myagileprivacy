<?php
if( !defined( 'MAP_PLUGIN_NAME' ) )
{
    exit('Not allowed.');
}

/**
 * Handles installation, activation, and introspection of the MAP Advanced plugin.
 * Used by MAP_Advanced_Trigger (install/update/repair) and MAP_Update_Checker.
 * No hooks registered here - this class is a pure service object.
 */
final class MAP_Package_Installer {

    const MAP_ADVANCED_BASENAME = 'myagileprivacy-advanced-features/my-agile-privacy-advanced.php';

    /**
     * Returns the reason automatic installation cannot run, or an empty string when it can.
     */
    public function auto_install_blocker() {
        if ( ! MyAgilePrivacy::verify_file_mods_allowed() ) {
            return 'file_mods_disabled';
        }
        if ( is_multisite() && ! is_super_admin() ) {
            return 'network_admin_required';
        }
        if ( ! current_user_can( 'install_plugins' ) ) {
            return 'no_capability';
        }
        if ( ! function_exists( 'get_filesystem_method' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if ( get_filesystem_method() !== 'direct' ) {
            return 'filesystem';
        }
        return '';
    }

    /**
     * Returns true when the advanced plugin file is present on disk.
     */
    public function is_advanced_installed() {
        return file_exists( WP_PLUGIN_DIR . '/' . self::MAP_ADVANCED_BASENAME );
    }

    /**
     * Returns true when the advanced plugin is in the active_plugins list.
     */
    public function is_advanced_active() {
        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return is_plugin_active( self::MAP_ADVANCED_BASENAME );
    }

    /**
     * Downloads and installs the advanced plugin package from $download_url.
     * Overwrites an existing (mis-versioned) install: Plugin_Upgrader::install()
     * ignores clear_destination and derives it from overwrite_package, so it is
     * the latter that clears the destination directory.
     * On success, calls activate_if_installed().
     *
     * @param  string $download_url      Validated HTTPS URL.
     * @param  string $expected_version  Version the package must carry, or '' to skip the check.
     * @return array  { success, action, message }
     */
    public function install( $download_url, $expected_version = '' ) {
        $url    = esc_url_raw( $download_url );
        $parsed = parse_url( $url );
        if ( ! is_array( $parsed ) || ! isset( $parsed['scheme'] ) || $parsed['scheme'] !== 'https'
            || ! isset( $parsed['host'] )
            || ! function_exists( 'map_advanced_host_allowed' )
            || ! map_advanced_host_allowed( $parsed['host'] ) ) {
            return array(
                'success' => false,
                'action'  => 'error',
                'message' => __( 'Invalid download URL.', 'MAP_txt' ),
            );
        }

        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

        $skin     = class_exists( 'WP_Ajax_Upgrader_Skin' ) ? new WP_Ajax_Upgrader_Skin() : new Automatic_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader( $skin );

        global $wp_version;

        if ( isset( $wp_version ) && version_compare( $wp_version, '5.5', '<' ) ) {
            $result = $this->install_over_existing( $upgrader, $url );
        } else {
            $result = $upgrader->install( $url, array(
                'clear_destination' => true,
                'overwrite_package' => true,
            ) );
        }

        if ( is_wp_error( $result ) ) {
            return array(
                'success' => false,
                'action'  => 'error',
                'message' => $result->get_error_message(),
            );
        }

        if ( true !== $result ) {
            // a failed download ends here without a WP_Error: the reason is only in the skin
            $reason = method_exists( $skin, 'get_error_messages' ) ? trim( (string) $skin->get_error_messages() ) : '';

            return array(
                'success' => false,
                'action'  => 'error',
                'message' => ( '' !== $reason ) ? $reason : __( 'Installation failed.', 'MAP_txt' ),
            );
        }

        $this->activate_if_installed();

        // a package whose header does not say the expected version would be installed again on every page
        if ( '' !== $expected_version && $this->get_installed_advanced_version() !== $expected_version ) {
            return array(
                'success' => false,
                'action'  => 'error',
                'code'    => 'version_mismatch',
                'message' => __( 'Installation failed.', 'MAP_txt' ),
            );
        }

        return array(
            'success' => true,
            'action'  => 'installed',
            'message' => __( 'Advanced plugin installed and activated.', 'MAP_txt' ),
        );
    }

    /**
     * Installs the package over an existing copy, for WordPress before 5.5.
     */
    private function install_over_existing( $upgrader, $package ) {
        $upgrader->init();
        $upgrader->upgrade_strings();
        $upgrader->install_strings();

        add_filter( 'upgrader_source_selection', array( $upgrader, 'check_package' ) );
        add_action( 'upgrader_process_complete', 'wp_clean_plugins_cache', 9, 0 );

        $upgrader->run( array(
            'package'           => $package,
            'destination'       => WP_PLUGIN_DIR,
            'clear_destination' => true,
            'clear_working'     => true,
            'hook_extra'        => array(
                'type'   => 'plugin',
                'action' => 'install',
            ),
        ) );

        remove_action( 'upgrader_process_complete', 'wp_clean_plugins_cache', 9 );
        remove_filter( 'upgrader_source_selection', array( $upgrader, 'check_package' ) );

        if ( ! $upgrader->result || is_wp_error( $upgrader->result ) ) {
            return $upgrader->result;
        }

        wp_clean_plugins_cache( true );

        return true;
    }

    /**
     * Activates the advanced plugin if the file exists on disk.
     */
    public function activate_if_installed() {
        if ( ! $this->is_advanced_installed() ) { return; }
        if ( ! function_exists( 'activate_plugin' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        activate_plugin( self::MAP_ADVANCED_BASENAME );
    }

    /**
     * Returns the Version header of the installed advanced plugin file,
     * or empty string when the file is absent.
     */
    public function get_installed_advanced_version() {
        if ( ! $this->is_advanced_installed() ) { return ''; }
        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $data = get_plugin_data( WP_PLUGIN_DIR . '/' . self::MAP_ADVANCED_BASENAME, false, false );
        return isset( $data['Version'] ) ? $data['Version'] : '';
    }
}
