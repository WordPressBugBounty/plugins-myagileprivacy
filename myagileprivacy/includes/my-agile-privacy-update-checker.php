<?php
if( !defined( 'MAP_PLUGIN_NAME' ) )
{
    exit('Not allowed.');
}

/**
 * Plugs the MAP Advanced plugin into the native WordPress update system.
 */
final class MAP_Update_Checker {

    public function __construct() {
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ), 20 );
        add_filter( 'plugins_api',                           array( $this, 'plugin_info' ),      20, 3 );

        // the link is refreshed before the core puts the site in maintenance for the update
        add_action( 'pre_auto_update',                 array( $this, 'refresh_before_auto_update' ), 10, 2 );
        add_action( 'wp_ajax_update-plugin',           array( $this, 'refresh_before_update' ), 0 );
        add_action( 'admin_action_upgrade-plugin',     array( $this, 'refresh_before_update' ) );
        add_action( 'admin_action_update-selected',    array( $this, 'refresh_before_update' ) );
    }

    /**
     * Refreshes the package link before an automatic update of the advanced plugin.
     *
     * @param string $type
     * @param object $item
     */
    public function refresh_before_auto_update( $type, $item ) {
        if ( 'plugin' !== $type || ! is_object( $item ) || ! isset( $item->plugin ) ) { return; }
        if ( MAP_Package_Installer::MAP_ADVANCED_BASENAME !== $item->plugin ) { return; }
        if ( ! class_exists( 'MAP_Advanced_Trigger' ) ) { return; }

        MAP_Advanced_Trigger::refresh_package_data();
    }

    /**
     * Refreshes the package link before an update of the advanced plugin started from the dashboard.
     */
    public function refresh_before_update() {
        if ( ! current_user_can( 'update_plugins' ) || ! class_exists( 'MAP_Advanced_Trigger' ) ) { return; }

        $basename = MAP_Package_Installer::MAP_ADVANCED_BASENAME;
        $plugins  = array();

        foreach ( array( 'plugin', 'plugins', 'checked' ) as $key ) {
            if ( ! isset( $_REQUEST[ $key ] ) ) { continue; }

            $value = wp_unslash( $_REQUEST[ $key ] );
            $value = is_array( $value ) ? $value : explode( ',', (string) $value );

            foreach ( $value as $item ) {
                if ( is_string( $item ) ) {
                    $plugins[] = trim( $item );
                }
            }
        }

        if ( ! in_array( $basename, $plugins, true ) ) { return; }

        // the core checks its own nonce right after this: only a valid one lets the sync start
        $nonce = '';

        if ( isset( $_REQUEST['_wpnonce'] ) && is_string( $_REQUEST['_wpnonce'] ) ) {
            $nonce = $_REQUEST['_wpnonce'];
        } elseif ( isset( $_REQUEST['_ajax_nonce'] ) && is_string( $_REQUEST['_ajax_nonce'] ) ) {
            $nonce = $_REQUEST['_ajax_nonce'];
        }

        foreach ( array( 'updates', 'upgrade-plugin_' . $basename, 'bulk-update-plugins' ) as $nonce_action ) {
            if ( wp_verify_nonce( $nonce, $nonce_action ) ) {
                MAP_Advanced_Trigger::refresh_package_data();
                return;
            }
        }
    }

    /**
     * Swaps the package of an update of the advanced plugin for a link that can still be used:
     * the one stored in the update transient may be hours old.
     *
     * @param  array $options  Upgrader package options.
     * @return array
     */
    public static function fresh_package( $options ) {
        if ( ! is_array( $options ) || ! isset( $options['hook_extra']['plugin'] ) ) { return $options; }
        if ( MAP_Package_Installer::MAP_ADVANCED_BASENAME !== $options['hook_extra']['plugin'] ) { return $options; }
        if ( ! class_exists( 'MAP_Advanced_Trigger' ) ) { return $options; }

        $url = MAP_Advanced_Trigger::fresh_download_url();

        if ( '' !== $url ) {
            $options['package'] = $url;
        }

        return $options;
    }

    /**
     * Injects an update entry into the WP update transient when applicable.
     *
     * @param  object $transient  The update_plugins transient.
     * @return object
     */
    public function check_for_update( $transient ) {
        if ( empty( $transient->checked ) )   { return $transient; }
        if ( ! map_is_advanced_active() )     { return $transient; }
        if ( ! map_advanced_is_authorized() ) { return $transient; }

        $remote_version = map_advanced_get_remote_version();
        $url            = map_advanced_get_download_url();

        if ( '' === $remote_version || '' === $url ) { return $transient; }

        $installed_version = $this->get_installed_version();
        if ( '' === $installed_version ) { return $transient; }

        if ( version_compare( $installed_version, $remote_version, '<' ) ) {
            $basename = MAP_Package_Installer::MAP_ADVANCED_BASENAME;
            $slug     = dirname( $basename );

            $obj              = new stdClass();
            $obj->new_version = $remote_version;
            $obj->package     = $url;
            $obj->slug        = $slug;
            $obj->plugin      = $basename;
            $obj->url         = '';

            $transient->response[ $basename ] = $obj;
        }

        return $transient;
    }

    /**
     * Provides plugin metadata for the "View details" modal in Plugins → Updates.
     *
     * @param  mixed  $result
     * @param  string $action
     * @param  object $args
     * @return mixed
     */
    public function plugin_info( $result, $action, $args ) {
        if ( 'plugin_information' !== $action ) { return $result; }
        if ( ! map_is_advanced_active() )       { return $result; }

        $slug = dirname( MAP_Package_Installer::MAP_ADVANCED_BASENAME );
        if ( ! isset( $args->slug ) || $args->slug !== $slug ) { return $result; }

        $remote_version = map_advanced_get_remote_version();
        $url            = map_advanced_get_download_url();

        $info                = new stdClass();
        $info->name          = 'My Agile Privacy® - Advanced Features';
        $info->slug          = $slug;
        $info->version       = $remote_version;
        $info->author        = 'MyAgilePrivacy';
        $info->homepage      = '';
        $info->download_link = $url;
        $info->sections      = array(
            'description' => __( 'Advanced features for My Agile Privacy® including geolocation-based banner behavior and premium tools.', 'MAP_txt' ),
        );

        return $info;
    }

    /**
     * Returns the Version header of the currently installed advanced plugin file.
     */
    public function get_installed_version() {
        $installer = new MAP_Package_Installer();
        return $installer->get_installed_advanced_version();
    }
}

// every context: agency tools and WP-CLI update plugins outside wp-admin too
add_filter( 'upgrader_package_options', array( 'MAP_Update_Checker', 'fresh_package' ) );
