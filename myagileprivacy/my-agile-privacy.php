<?php
if( !defined( 'ABSPATH' ) ) { exit; }

/**
 * @wordpress-plugin
 * Plugin Name:       My Agile Privacy®
 * Plugin URI:        https://www.myagileprivacy.com/
 * Description:       My Agile Privacy® - CMP, Cookie Consent & Privacy Tools
 * Version:           3.4.0
 * Requires at least: 4.4.0
 * Requires PHP:      5.6
 * Author:            MyAgilePrivacy
 * Author URI:        https://www.myagileprivacy.com/
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       MAP_txt
 * Domain Path:       /lang
 */

// another copy is already loaded
if( defined( 'MAP_PLUGIN_FILENAME' ) ) { return; }

define( 'MAP_PLUGIN_FILENAME', ( function_exists( 'realpath' ) && realpath( __FILE__ ) ) ? realpath( __FILE__ ) : __FILE__ );

require plugin_dir_path( __FILE__ ) . 'includes/my-agile-privacy-defines.php';
require plugin_dir_path( __FILE__ ) . 'includes/my-agile-privacy-class.php';
require plugin_dir_path( __FILE__ ) . 'includes/my-agile-privacy-admin-notice.php';
require plugin_dir_path( __FILE__ ) . 'includes/my-agile-privacy-notices.php';

if( is_admin() )
{
    require plugin_dir_path( __FILE__ ) . 'includes/my-agile-privacy-helpdesk-links.php';
    MAP_Admin_Notice_Manager::instance();
}

if( !defined( 'MAP_ENABLE_ADVANCED_INTEGRATION' ) )
{
    define( 'MAP_ENABLE_ADVANCED_INTEGRATION', true );
}

if( defined( 'MAP_ENABLE_ADVANCED_INTEGRATION' ) && MAP_ENABLE_ADVANCED_INTEGRATION )
{
    require plugin_dir_path( __FILE__ ) . 'includes/my-agile-privacy-advanced-bridge.php';
    require plugin_dir_path( __FILE__ ) . 'includes/my-agile-privacy-package-installer.php';
    require plugin_dir_path( __FILE__ ) . 'includes/my-agile-privacy-advanced-trigger.php';
    require plugin_dir_path( __FILE__ ) . 'includes/my-agile-privacy-update-checker.php';

    define( 'MAP_BASE_LOADED', true );
}

/**
 * Starts the plugin execution
 *
 */
if( !function_exists( 'run_my_agile_privacy' ) ) {
    function run_my_agile_privacy() {
    	if( function_exists( 'ini_set' ) )
    	{
    		ini_set( 'display_errors', 0 );
    	}
    	$plugin  = new MyAgilePrivacy();
    	$rconfig = MyAgilePrivacy::get_rconfig();

        if( !defined( 'MAP_DEBUGGER' ) )
        {
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
    }
}
run_my_agile_privacy();