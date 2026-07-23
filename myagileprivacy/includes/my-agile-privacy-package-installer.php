<?php
if( !defined( 'MAP_PLUGIN_NAME' ) )
{
    exit('Not allowed.');
}

/**
 * Handles installation, activation, and introspection of the MAP Advanced plugin.
 * Used by MAP_Advanced_Trigger (install/update/repair) and MAP_Update_Checker.
 * No hooks registered here — this class is a pure service object.
 */
final class MAP_Package_Installer {

    const MAP_ADVANCED_BASENAME = 'myagileprivacy-advanced-features/my-agile-privacy-advanced.php';

    /**
     * Returns true when auto-install is possible without user interaction:
     * current user has install_plugins cap AND the filesystem is 'direct'.
     */
    public function can_auto_install() {
        return current_user_can( 'install_plugins' ) && get_filesystem_method() === 'direct';
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
     * Uses clear_destination=true so it overwrites an existing (mis-versioned) install.
     * On success, calls activate_if_installed().
     *
     * @param  string $download_url  Validated HTTPS URL.
     * @return array  { success, action, message }
     */
    public function install( $download_url ) {
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

        $skin     = new WP_Ajax_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader( $skin );

        $result = $upgrader->install( $url, array(
            'clear_destination' => true,
            'overwrite_package' => true,
        ) );

        if ( is_wp_error( $result ) ) {
            return array(
                'success' => false,
                'action'  => 'error',
                'message' => $result->get_error_message(),
            );
        }

        if ( true !== $result ) {
            return array(
                'success' => false,
                'action'  => 'error',
                'message' => __( 'Installation failed.', 'MAP_txt' ),
            );
        }

        $this->activate_if_installed();

        return array(
            'success' => true,
            'action'  => 'installed',
            'message' => __( 'Advanced plugin installed and activated.', 'MAP_txt' ),
        );
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
