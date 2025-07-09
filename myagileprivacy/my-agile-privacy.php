<?php

/**
 * @wordpress-plugin
 * Plugin Name:       MyAgilePrivacy
 * Plugin URI:        https://www.myagileprivacy.com/
 * Description:       The only GDPR solution for WordPress that you can truly trust.
 * Version:           3.1.10
 * Requires at least: 4.4.0
 * Requires PHP:      5.6
 * Author:            MyAgilePrivacy
 * Author URI:        https://www.myagileprivacy.com/
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       MAP_txt
 * Domain Path:       /lang
 */

define( 'MAP_PLUGIN_VERSION', '3.1.10' );
define( 'MAP_PLUGIN_NAME', 'my-agile-privacy' );
define( 'MAP_PLUGIN_SLUG', 'myagileprivacy' );
define( 'MAP_PLUGIN_FILENAME', __FILE__ );
define( 'MAP_DEV_MODE', false );

require plugin_dir_path( __FILE__ ) . 'includes/my-agile-privacy-class.php';

/**
 * Starts the plugin execution
 *
 * @since    1.0.12
 */
function run_my_agile_privacy() {
	ini_set( 'display_errors', 0 );
	$plugin = new MyAgilePrivacy();

    $rconfig = MyAgilePrivacy::get_rconfig();

    if( isset( $rconfig ) &&
        isset( $rconfig['verbose_remote_log'] ) &&
        $rconfig['verbose_remote_log'] )
    {
        define ( 'MAP_DEBUGGER', true );
    }
    else
    {
        define ( 'MAP_DEBUGGER', false );
    }
}
run_my_agile_privacy();