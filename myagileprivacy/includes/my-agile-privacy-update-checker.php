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
