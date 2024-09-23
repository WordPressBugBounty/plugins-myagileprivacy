<?php

/**
 * Core definitions
 */

define( 'MAP_SOFTWARE_KEY', 'map_wp' );
define( 'MAP_PLUGIN_DB_KEY_PREFIX', 'MyAgilePrivacy-' );
define( 'MAP_PLUGIN_SETTINGS_FIELD', MAP_PLUGIN_DB_KEY_PREFIX . '1.0.0' );
define( 'MAP_PLUGIN_JS_DETECTED_FIELDS', MAP_PLUGIN_DB_KEY_PREFIX . '_detected_fields' );
define( 'MAP_PLUGIN_RCONFIG', MAP_PLUGIN_DB_KEY_PREFIX . '_rconfig' );
define( 'MAP_PLUGIN_COMPLIANCE_REPORT', MAP_PLUGIN_DB_KEY_PREFIX . '_compliance_report' );
define( 'MAP_PLUGIN_STATS', MAP_PLUGIN_DB_KEY_PREFIX . 'stats' );
define( 'MAP_PLUGIN_DO_PARITY_CHECK_NOW', MAP_PLUGIN_DB_KEY_PREFIX . 'do_parity_check_now' );
define( 'MAP_PLUGIN_DO_SYNC_NOW', MAP_PLUGIN_DB_KEY_PREFIX . 'do_sync_now' );
define( 'MAP_PLUGIN_DO_SYNC_LAST_EXECUTION', MAP_PLUGIN_DB_KEY_PREFIX . 'do_sync_last_execution' );
define( 'MAP_PLUGIN_VALIDATION_TIMESTAMP', MAP_PLUGIN_DB_KEY_PREFIX . 'validation_timestamp' );
define( 'MAP_PLUGIN_DB_VERSION', MAP_PLUGIN_DB_KEY_PREFIX . 'db_version_number' );
define( 'MAP_PLUGIN_DB_VERSION_NUMBER', 1 );
define( 'MAP_PLUGIN_SYNC_IN_PROGRESS', MAP_PLUGIN_DB_KEY_PREFIX . 'sync_in_progress' );
define( 'MAP_MANIFEST_ASSOC', MAP_PLUGIN_DB_KEY_PREFIX . 'manifest' );
define( 'MAP_POST_TYPE_COOKIES', 'my-agile-privacy-c' );
define( 'MAP_POST_TYPE_POLICY', 'my-agile-privacy-p' );
define( 'MAP_PAGE_SLUG', 'my-agile-privacy' );
define( 'MAP_API_ENDPOINT', 'https://auth.myagileprivacy.com/wp_api' );
define( 'MAP_SCANNER', true );
define( 'MAP_IAB_TCF', true );
define( 'MAP_MULTILANG_SUPPORT', true );
define( 'MAP_LEGIT_SYNC_TRESHOLD', 10800 );
define( 'MAP_AUTORESET_SYNC_TRESHOLD', 259200 ); // 3 days
define( 'MAP_PLUGIN_ACTIVATION_DATE', MAP_PLUGIN_DB_KEY_PREFIX.'-activation_date' );
define( 'MAP_REVIEW_STATUS', MAP_PLUGIN_DB_KEY_PREFIX.'-review_status' );
define( 'MAP_NOTICE_LAST_SHOW_TIME', MAP_PLUGIN_DB_KEY_PREFIX.'-notice_last_show_time' );
define( 'MAP_NOTICE_FIRST_TRESHOLD', 604800 ); // 7 days: 7 * 24 * 60 * 60
define( 'MAP_NOTICE_SECOND_TRESHOLD', 12960000 ); // 5 months: 5 * 30 * 24 * 60 * 60

/**
 * Core definitions
 * *
 * @link       https://www.myagileprivacy.com/
 * @since      1.0.12
 *
 * @package    MyAgilePrivacy
 * @subpackage MyAgilePrivacy/includes
 */

/**
 * Core plugin class.
 *
 *
 * @since      1.0.12
 * @package    MyAgilePrivacy
 * @subpackage MyAgilePrivacy/includes
 * @author     https://www.myagileprivacy.com/
 */
class MyAgilePrivacy {

	/**
	 * Unique identifier of this plugin.
	 *
	 * @since    1.0.12
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * Current version of the plugin.
	 *
	 * @since    1.0.12
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	//stored user options
	private static $stored_options = array();

	/**
	 * Core functionality of the plugin.
	 *
	 * It sets plugin name, plugin version.
	 * It loads dependencies, set the locale, and hoocks for admin and frontend area
	 *
	 * @since    1.0.12
	 */
	public function __construct()
	{
		$this->version = MAP_PLUGIN_VERSION;
		$this->plugin_name = MAP_PLUGIN_NAME;

		register_activation_hook( MAP_PLUGIN_FILENAME, [ self::class, 'map_plugin_activate'] );
        register_deactivation_hook( MAP_PLUGIN_FILENAME, [ self::class, 'map_plugin_deactivate'] );

		$this->load_classes_and_dependencies();
		$this->admin_hooks();
		$this->frontend_hooks();
	}

	/**
     * f for plugin activation
     */
    public static function map_plugin_activate() {

    	if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'calling map_plugin_activate' );

        if ( !get_option( MAP_PLUGIN_ACTIVATION_DATE, null ) )
        {
            update_option( MAP_PLUGIN_ACTIVATION_DATE, time() );
        }
    }

    /**
     * f for plugin deactivation
     */
    public static function map_plugin_deactivate() {

    	if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'calling map_plugin_deactivate' );

        if( defined( 'MAP_PLUGIN_ACTIVATION_DATE' ) && get_option( MAP_PLUGIN_ACTIVATION_DATE, null ) ) delete_option( MAP_PLUGIN_ACTIVATION_DATE );
        if( defined( 'MAP_REVIEW_STATUS' ) && get_option( MAP_REVIEW_STATUS, null ) ) delete_option( MAP_REVIEW_STATUS );
        if( defined( 'MAP_NOTICE_LAST_SHOW_TIME' ) && get_option( MAP_NOTICE_LAST_SHOW_TIME, null ) ) delete_option( MAP_NOTICE_LAST_SHOW_TIME );
    }

	/**
     * Determine if notice should be shown
     */
    public static function should_show_notice()
    {
    	$rconfig = MyAgilePrivacy::get_rconfig();

    	if( isset( $rconfig ) &&
    		isset( $rconfig['block_review_message'] ) &&
    		$rconfig['block_review_message'] )
    	{
    		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'should_show_notice blocked via block_review_message' );
    		return false;
    	}

		if( !current_user_can( 'manage_options' ) )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( ' should_show_notice -> missing user permission' );
			return false;
		}

    	if( !defined( 'MAP_REVIEW_STATUS') )
    	{
    		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'missing should_show_notice review_status' );
    		return false;
    	}

		$review_status = get_option( MAP_REVIEW_STATUS, null );
        $last_show_time = get_option( MAP_NOTICE_LAST_SHOW_TIME, null );
		$activation_date = get_option( MAP_PLUGIN_ACTIVATION_DATE, null );

		if( !$activation_date )
		{
			self::map_plugin_activate();

			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'missing should_show_notice activation_date' );

			return false;
		}
		
        $current_time = time();
		$first_treshold = MAP_NOTICE_FIRST_TRESHOLD;
        $second_treshold = MAP_NOTICE_SECOND_TRESHOLD;

        if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER )
        {
			$debug_data = array(
				'activation_date'				=> 	$activation_date,
				'review_status'					=>	$review_status,
				'last_show_time'				=>	$last_show_time,
				'current_time'					=>	$current_time,
				'first_treshold'				=>	MAP_NOTICE_FIRST_TRESHOLD,
				'second_treshold'				=>	MAP_NOTICE_SECOND_TRESHOLD,
			);

			MyAgilePrivacy::write_log( $debug_data );
        }

		// first show after first treshold
		if( $current_time - $activation_date < MAP_NOTICE_FIRST_TRESHOLD )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'should_show_notice --> false (check A)' );

            return false;
        }

		// if feedback marked as later, show again after first treshold
        if( $review_status === 'later' && ( $current_time - $last_show_time ) < MAP_NOTICE_FIRST_TRESHOLD )
        {
        	if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'should_show_notice --> false (check B)' );

            return false;
        }

		// if feedback marked as done, show again after second treshold
        if( $review_status === 'done' && ( $current_time - $last_show_time ) < MAP_NOTICE_SECOND_TRESHOLD )
        {
        	if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'should_show_notice --> false (check C)' );

            return false;
        }

        if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'should_show_notice --> true' );

        return true;
    }
	

	/**
	 * Load the required dependencies.
	 *
	 * @since    1.0.12
	 * @access   private
	 */
	private function load_classes_and_dependencies()
	{
		/**
		 * The class for defining all actions that occur in the backend area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/my-agile-privacy-admin.php';

		/**
		 * The class for defining all the functionalities for the frontend part
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'frontend/my-agile-privacy-frontend.php';
	}

	/**
	 * Register all of the hooks related to the frontend part
	 *
	 * @since    1.0.12
	 * @access   private
	 */
	private function frontend_hooks()
	{
		$plugin_frontend = new MyAgilePrivacyFrontend( $this->plugin_name, $this->version, $this );

		/* Frontend styles*/
		add_action( 'wp_enqueue_scripts', array( $plugin_frontend, 'enqueue_styles' ), PHP_INT_MIN );

		/* Frontend scripts*/
		add_action( 'wp_enqueue_scripts', array( $plugin_frontend, 'enqueue_scripts' ), PHP_INT_MIN );

		/* set locale, register custom post type */
		add_action( 'init', array( $plugin_frontend, 'plugin_init' ) );

		/* wp_footer hook*/
		add_action( 'wp_footer', array( $plugin_frontend, 'inject_html_code' ) );

		$the_settings = self::get_settings();

		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $the_settings );

		/* admin callback actions */
		add_action( 'wp_ajax_nopriv_map_save_detected_keys', array( $plugin_frontend, 'map_save_detected_keys_callback' ) );
		add_action( 'wp_ajax_map_save_detected_keys', array( $plugin_frontend, 'map_save_detected_keys_callback' ) );
		add_action( 'wp_ajax_nopriv_map_missing_cookie_shield', array( $plugin_frontend, 'map_missing_cookie_shield_callback' ) );
		add_action( 'wp_ajax_map_missing_cookie_shield', array( $plugin_frontend, 'map_missing_cookie_shield_callback' ) );
		add_action( 'wp_ajax_nopriv_map_remote_save_detected_keys', array( $plugin_frontend, 'map_remote_save_detected_keys_callback' ) );
		add_action( 'wp_ajax_map_remote_save_detected_keys', array( $plugin_frontend, 'map_remote_save_detected_keys_callback' ) );

		$skip = $this::check_buffer_skip_conditions( false );

		if( $skip == 'false' && $the_settings['pa'] == 1 && MAP_SCANNER )
		{
			$rconfig = self::get_rconfig();

			$logic_legacy_mode = false;

			if(
				(
					$rconfig &&
					isset( $rconfig['js_legacy_mode'] ) &&
					$rconfig['js_legacy_mode'] == 1
				) ||
				( $the_settings['scanner_compatibility_mode'] && $the_settings['forced_legacy_mode'] ) ||
				$the_settings['missing_cookie_shield']

			)
			{
				$logic_legacy_mode = true;
			}

			if( $logic_legacy_mode )
			{
				add_action( 'wp_head', array( $plugin_frontend, 'wp_head_inject' ), isset( $rconfig['js_legacy_mode_head_prio'] ) ? intval( $rconfig['js_legacy_mode_head_prio'] ) : PHP_INT_MIN );
			}

			/**
			 * The class for html parsing
			 */

			if( !class_exists( 'agile_simple_html_dom_node' ) )
			{
				require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/simple_html_dom.php';
			}

			if( $the_settings['scanner_compatibility_mode'] &&
				$the_settings['scanner_start_hook_prio'] &&
				$the_settings['scanner_end_hook_prio'] )
			{
				if( $the_settings['scanner_hook_type'] == 'template_redirect-shutdown' )
				{
					//customized settings
					add_action( 'template_redirect', array( $plugin_frontend, 'map_buffer_start' ), $the_settings['scanner_start_hook_prio'] );
					add_action( 'shutdown', array( $plugin_frontend, 'map_buffer_end' ), $the_settings['scanner_end_hook_prio'] );
				}
				elseif( $the_settings['scanner_hook_type'] == 'init-shutdown' )
				{
					//customized settings
					add_action( 'init', array( $plugin_frontend, 'map_buffer_start' ), $the_settings['scanner_start_hook_prio'] );
					add_action( 'shutdown', array( $plugin_frontend, 'map_buffer_end' ), $the_settings['scanner_end_hook_prio'] );
				}
				else
				{
					//customized settings
					add_action( 'init', array( $plugin_frontend, 'map_buffer_start' ), $the_settings['scanner_start_hook_prio'] );
					add_action( 'shutdown', array( $plugin_frontend, 'map_buffer_end' ), $the_settings['scanner_end_hook_prio'] );
				}
			}
			else
			{
				//standard settings
				add_action( 'init', array( $plugin_frontend, 'map_buffer_start' ) );
				add_action( 'shutdown', array( $plugin_frontend, 'map_buffer_end' ), -1000 );
			}
		}

		/*auto update*/
		add_filter( 'auto_update_plugin', array( $plugin_frontend, 'auto_update_plugins' ), 10, 2 );
	}

	/**
	 * Register all of the hooks related to the backend area
	 *
	 * @since    1.0.12
	 * @access   private
	 */
	private function admin_hooks()
	{
		$rconfig = self::get_rconfig();

		$plugin_admin = new MyAgilePrivacyAdmin( $this->plugin_name, $this->version, $this );

		if( !( isset( $rconfig ) &&
				isset( $rconfig['disable_cronjob'] ) &&
				$rconfig['disable_cronjob'] == 1 ) )
		{
			add_action( 'my_agile_privacy_do_cron_sync_twice_day_hook', array( $plugin_admin, 'do_cron_sync' ) );
		}

		//wp_footer hook
		add_action( 'wp_footer', array( $plugin_admin, 'triggered_do_cron_sync' ) );
		add_action( 'wp_footer', array( $plugin_admin, 'triggered_parity_check' ) );


		/* admin callback actions */
		add_action( 'wp_ajax_nopriv_check_license_status', array( $plugin_admin, 'check_license_status' ) );
		add_action( 'wp_ajax_check_license_status', array( $plugin_admin, 'check_license_status' ) );


		if( !is_admin() )
		{
			return;
		}

		//repeated on admin_footer
		add_action( 'admin_footer', array( $plugin_admin, 'triggered_do_cron_sync' ) );
		add_action( 'admin_footer', array( $plugin_admin, 'triggered_parity_check' ) );

		//admin menu
		add_action( 'admin_menu', array( $plugin_admin, 'add_admin_pages' ), 11 );

		//cookie list dashboard
		add_action( 'views_edit-'.MAP_POST_TYPE_COOKIES, array( $plugin_admin, 'map_fix_view_links' ), 11 );
		add_action( 'views_edit-'.MAP_POST_TYPE_POLICY, array( $plugin_admin, 'map_fix_view_links' ), 11 );
		add_action( 'admin_footer-edit.php', array( $plugin_admin, 'map_fix_post_status_quick_edit' ) );
		add_action( 'admin_footer-post.php', array( $plugin_admin, 'map_fix_post_status_edit' ) );

		//default sorting
		add_filter( 'pre_get_posts', array( $plugin_admin, 'map_order_post_type' ) );

		//admin init && metabox
		add_action( 'admin_init', array( $plugin_admin, 'admin_init_and_add_meta_box' ) );
		add_action( 'save_post_'.MAP_POST_TYPE_COOKIES, array( $plugin_admin, 'save_custom_metabox_cookies' ) );
		add_action( 'save_post_'.MAP_POST_TYPE_POLICY, array( $plugin_admin, 'save_custom_metabox_policies' ) );

		//cookies
		add_action( 'manage_edit-'.MAP_POST_TYPE_COOKIES.'_columns', array( $plugin_admin, 'manage_cookies_edit_columns' ) );
		add_action( 'manage_'.MAP_POST_TYPE_COOKIES.'_posts_custom_column', array( $plugin_admin, 'manage_cookies_posts_custom_columns' ) );

		//policies
		add_action( 'manage_edit-'.MAP_POST_TYPE_POLICY.'_columns', array( $plugin_admin, 'manage_policies_edit_columns' ) );
		add_action( 'manage_'.MAP_POST_TYPE_POLICY.'_posts_custom_column', array( $plugin_admin, 'manage_policies_posts_custom_columns' ) );

		//inline help
		add_action( 'admin_notices', array( $plugin_admin, 'inline_help_text_after_editor' ) );

		//admin callback actions
		add_action( 'wp_ajax_nopriv_update_admin_settings_form', array( $plugin_admin, 'update_admin_settings_form_callback' ) );
		add_action( 'wp_ajax_update_admin_settings_form', array( $plugin_admin, 'update_admin_settings_form_callback' ) );

		//admin post actions
		add_action( 'admin_post_backup_admin_settings_form', array( $plugin_admin, 'backup_admin_settings_form_callback' ) );

		add_action( 'admin_post_import_admin_settings_form', array( $plugin_admin, 'import_admin_settings_form_callback' ) );

		//generic admin styles
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_styles' ) );

		//generic admin scripts
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts' ) );

		//admin styles for css styles on custom post edit
		add_action( 'load-post.php', array( $plugin_admin, 'custom_post_type_editor' ) );
		add_action( 'load-post-new.php', array( $plugin_admin, 'custom_post_type_editor' ) );

		//add settings links for the menu
		add_filter( 'plugin_action_links_'.plugin_basename( MAP_PLUGIN_FILENAME ), array( $plugin_admin, 'plugin_action_links' ) );

		//hooks for review notice
		add_action( 'admin_notices', array( $plugin_admin, 'show_review_notice' ) );
        add_action( 'wp_ajax_map_review_later', array( $plugin_admin, 'review_later' ) );
        add_action( 'wp_ajax_map_review_done', array( $plugin_admin, 'review_done' ) );

		//add cron scheduled functions
		if( !( isset( $rconfig ) &&
				isset( $rconfig['disable_cronjob'] ) &&
				$rconfig['disable_cronjob'] == 1 ) )
		{
			//clean old daily event schedule if exists
			if ( wp_next_scheduled( 'my_agile_privacy_do_cron_sync_hook' ) ) {
				wp_clear_scheduled_hook( 'my_agile_privacy_do_cron_sync_hook' );
			}

			//clean old daily event schedule if exists
			if ( wp_next_scheduled( 'my_agile_privacy_do_cron_sync_once_day_hook' ) ) {
				wp_clear_scheduled_hook( 'my_agile_privacy_do_cron_sync_once_day_hook' );
			}


			//schedule an action if it's not already scheduled
			if ( ! wp_next_scheduled( 'my_agile_privacy_do_cron_sync_twice_day_hook' ) ) {
				wp_schedule_event( time(), 'twicedaily', 'my_agile_privacy_do_cron_sync_twice_day_hook' );
			}
		}
		else
		{
			//clean old daily event schedule if exists
			if ( wp_next_scheduled( 'my_agile_privacy_do_cron_sync_hook' ) ) {
				wp_clear_scheduled_hook( 'my_agile_privacy_do_cron_sync_hook' );
			}

			//clean twice a day event schedule if exists
			if ( wp_next_scheduled( 'my_agile_privacy_do_cron_sync_twice_day_hook' ) ) {
				wp_clear_scheduled_hook( 'my_agile_privacy_do_cron_sync_twice_day_hook' );
			}

			//clean once a day event schedule if exists
			if ( wp_next_scheduled( 'my_agile_privacy_do_cron_sync_once_day_hook' ) ) {
				wp_clear_scheduled_hook( 'my_agile_privacy_do_cron_sync_once_day_hook' );
			}
		}

		if( !( isset( $rconfig ) &&
				isset( $rconfig['disable_install_counter'] ) &&
				$rconfig['disable_install_counter'] == 1 ) )
		{

			//schedule an action if it's not already scheduled
			if ( ! wp_next_scheduled( 'my_agile_privacy_do_cron_sync_install_counter' ) ) {
				wp_schedule_event( time(), 'daily', 'my_agile_privacy_do_cron_sync_install_counter' );
			}

			if( !get_option( MAP_PLUGIN_STATS, null ) )
			{
				wp_schedule_single_event( time() + 5, 'my_agile_privacy_do_cron_sync_install_counter' );
			}

			add_action( 'my_agile_privacy_do_cron_sync_install_counter', array( $plugin_admin, 'do_cron_sync_install_counter' ) );
		}

		add_action( 'admin_footer', array( $plugin_admin, 'admin_auto_enable_cookie' ) );
		add_action( 'admin_footer', array( $plugin_admin, 'admin_clear_logfile' ) );

		if( defined( 'POLYLANG_FILE' ) &&
			function_exists( 'pll_default_language' ) &&
			function_exists( 'pll_languages_list' ) )
		{
			//polylang
			add_filter( 'pll_get_post_types', array( $plugin_admin, 'add_cpt_to_pll' ), 10, 2 );
		}

		// remove wpautop from tinymce setup
		add_filter( 'tiny_mce_before_init', array( $plugin_admin, 'map_tinymce_config' ) );

		//add cookieshield link in admin topbar
		add_action( 'wp_before_admin_bar_render', array( $plugin_admin, 'map_adminbar_cookieshield_link' ) );
	}

	/**
	 * Function for doing better query reset
	 */
	public static function internal_query_reset()
	{
		$rconfig = self::get_rconfig();

		if( $rconfig && isset( $rconfig['use_alt_query_reset'] ) && $rconfig['use_alt_query_reset'] )
		{
			wp_reset_postdata();
		}
		else
		{
			wp_reset_query();
		}

		return true;
	}


	/**
	 * Function for checking if multilang is enabled
	 */
	public static function check_if_multilang_enabled()
	{
		global $locale;
		global $sitepress;

		$is_wpml_enabled = false;
		$is_polylang_enabled = false;
		$with_multilang = false;

		if( MAP_MULTILANG_SUPPORT )
		{
			if( function_exists( 'icl_object_id' ) && $sitepress )
			{
				$is_wpml_enabled = true;
				$with_multilang = true;
			}

			if( defined( 'POLYLANG_FILE' ) &&
				function_exists( 'pll_default_language' ) &&
				function_exists( 'pll_languages_list' ) )
			{
				$is_polylang_enabled = true;
				$with_multilang = true;
			}
		}

		return $with_multilang;
	}

	/**
	 * Get rconfig settings.
	 */
	public static function get_rconfig()
	{
		return self::nullCoalesce( get_option( MAP_PLUGIN_RCONFIG ), array() );
	}

	/**
	 * Get current settings.
	 * @since    1.0.12
	 * @access   public
	 */
	public static function get_settings()
	{
		$settings = self::get_default_settings();
		self::$stored_options = get_option( MAP_PLUGIN_SETTINGS_FIELD, array() );
		if( !empty( self::$stored_options ) )
		{
			foreach( self::$stored_options as $key => $option )
			{
				$settings[$key] = self::sanitise_settings( $key, $option );
			}
		}
		return $settings;
	}

	/**
	* Returns sanitised content
	 * @since    1.0.12
	 * @access   public
	*/
	public static function sanitise_settings( $key, $value )
	{
		$ret = null;
		switch( $key ){
			// text to bool conversion
			case 'is_on':
			case 'is_bottom':
			case 'showagain_tab':
			case 'wrap_shortcodes':
			case 'cookie_policy_link':
			case 'disable_logo':
			case 'is_cookie_policy_url':
			case 'is_personal_data_policy_url':
			case 'blocked_content_notify':
			case 'blocked_content_notify_auto_shutdown':
			case 'video_advanced_privacy':
			case 'maps_block':
			case 'captcha_block':
			case 'scanner_compatibility_mode':
			case 'enforce_youtube_privacy':
			case 'client_force_reinject_jquery':
			case 'display_dpo':
			case 'display_ccpa':
			case 'display_lpd':
			case 'show_ntf_bar_on_not_yet_consent_choice':
			case 'with_css_effects':
			case 'show_buttons_icons':
			case 'title_is_on':
			case 'forced_legacy_mode':
			case 'missing_cookie_shield':
			case 'forced_auto_update':
			case 'enable_iab_tcf':
			case 'enable_metadata_sync':
			case 'enable_cmode_v2':
			case 'enable_cmode_url_passthrough':
			case 'cmode_v2_forced_off_ga4_advanced':
				if ( $value === 'true' || $value === true )
				{
					$ret = true;
				}
				elseif ( $value === 'false' || $value === false )
				{
					$ret = false;
				}
				else
				{
					$ret = false;
				}
				break;
			//integer
			case 'scanner_start_hook_prio':
			case 'scanner_end_hook_prio':
			case 'blocked_content_notify_auto_shutdown_time':
			case 'floating_banner':
				$ret = intval( $value );
				break;
			// hex colors
			case 'background':
			case 'text':
			case 'button_accept_link_color':
			case 'button_accept_button_color':
			case 'button_reject_link_color':
			case 'button_reject_button_color':
			case 'button_customize_link_color':
			case 'button_customize_button_color':
			case 'map_inline_notify_color':
			case 'map_inline_notify_background':
				if ( preg_match( '/^#[a-f0-9]{6}|#[a-f0-9]{3}$/i', $value ) )
				{
					$ret =  $value;
				}
				else {
					// Failover = assign '#000' (black)
					$ret =  '#000';
				}
				break;
			// html (no js code )
			case 'bar_heading_text':
			case 'website_name':
			case 'identity_name':
			case 'identity_address':
			case 'identity_vat_id':
			case 'notify_message':
			case 'notify_message_v2':
			case 'showagain_text':
				$ret = wp_kses( $value, self::allowed_html_tags(), self::allowed_protocols() );
				break;
			case 'cookie_policy_url':
			case 'personal_data_policy_url':
			case 'license_code':
			case 'identity_email':
			case 'dpo_email':
			case 'dpo_name':
			case 'dpo_address':
				$ret = wp_kses( trim( $value ), self::allowed_html_tags(), self::allowed_protocols() );
				break;
			case 'custom_css':
				$ret = esc_html( $value );
				break;
			// Basic sanitisation for other fields
			default:
				$ret = sanitize_text_field( $value );
				break;
		}
		return $ret;
	}

	/**
	 * check for wp login page
	 * @since    1.3.5
	 * @access   public
	*/
	public static function is_wplogin()
	{
		if( function_exists( 'login_header' ) )
		{
			return true;
		}

		if( isset( $_GET['page'] ) && $_GET['page'] == 'sign-in' )
		{
		   return true;
		}

		$ABSPATH_MY = str_replace(array( '\\','/' ), DIRECTORY_SEPARATOR, ABSPATH);
		return ((in_array($ABSPATH_MY.'wp-login.php', get_included_files()) || in_array($ABSPATH_MY.'wp-register.php', get_included_files()) ) || (isset($_GLOBALS['pagenow']) && $GLOBALS['pagenow'] === 'wp-login.php' ) || $_SERVER['PHP_SELF']== '/wp-login.php' );
	}


	/**
	 * check for buffer / script inclusion skip
	 * @since    1.3.5
	 * @access   public
	*/
	public static function check_buffer_skip_conditions( $added_regexp_limited_check = false )
	{
		$skip = 'false';

		global $wp;
		global $pagenow;
		global $wp_query;
		global $wp_rewrite;
		$feeds = null;

		if( is_object( $wp_rewrite ) )
		{
			$feeds = $wp_rewrite->feeds;
		}

		//url check
		$current_href = null;

		if( is_object( $wp ) )
		{
			if( isset( $_SERVER['QUERY_STRING'] ) )
			{
				$current_href = add_query_arg( $_SERVER['QUERY_STRING'], '', home_url( $wp->request ) );
			}
			else
			{
				$current_href = home_url( $wp->request );
			}
		}

		$alt_current_href = null;

		if( isset( $_SERVER['SCRIPT_URI'] ) )
		{
			$alt_current_href = $_SERVER['SCRIPT_URI'];
		}
		elseif( isset( $_SERVER['REQUEST_URI'] ) )
		{
			$alt_current_href = $_SERVER['REQUEST_URI'];
		}

		$rconfig = self::get_rconfig();

		//regexp check
		if( isset( $rconfig['url_skip_regexp'] ) )
		{
			$url_skip_regexp = $rconfig['url_skip_regexp'];

			if( is_object( $wp ) )
			{
				$found = false;

				foreach( $url_skip_regexp as $regexp )
				{
					if( ( $current_href && preg_match( $regexp, $current_href ) ) ||
						( $alt_current_href && preg_match( $regexp, $alt_current_href ) )
					)
					{
						$found = true;
					}
				}

				if( $found ) $skip = 'true';
			}
		}

		//feed check
		$feed_url_list = array();

		if( $feeds )
		{
			$found = false;

			foreach ( $feeds as $feed )
			{
				$feed_url_list[] = get_feed_link( $feed );
			}

			foreach( $feed_url_list as $feed_url )
			{
				if( ( $current_href && $current_href == $feed_url ) ||
					( $alt_current_href && $alt_current_href == $feed_url )
				)
				{
					$found = true;
				}
			}

			if( $found ) $skip = 'true';
		}


		if( !$added_regexp_limited_check )
		{
			//widgets
			if( $pagenow && $pagenow === 'widgets.php' ) $skip = 'true';

			//amp
			if( ( function_exists( 'amp_is_request' ) && amp_is_request() ) ||
				isset( $_GET['amp'] ) ||
				strpos( $_SERVER['REQUEST_URI'], '/amp/' ) !== false ) $skip = 'true';

			//commercekit ajax search
			if( strpos( $_SERVER['REQUEST_URI'], 'commercekit_ajax_search' ) !== false ) $skip = 'true';

			//elementor
			if( isset( $_GET['elementor-preview'] ) ) $skip = 'true';

			//divi
			if ( isset( $_GET['et_fb'] ) && $_GET['et_fb'] == 1 ) $skip = 'true';

			//thrive theme builder
			if ( isset( $_GET['action'] ) && $_GET['action'] == 'architect' ) $skip = 'true';
			if ( isset( $_GET['tve'] ) && $_GET['tve'] == 'true' ) $skip = 'true';

			//no admin
			if( is_admin() ) $skip = 'true';

			//no rss
			if( isset( $wp_query ) && is_feed() ) $skip = 'true';

			//divi
			if( function_exists( 'et_fb_is_enabled' ) && et_fb_is_enabled() ) $skip = 'true';

			// page builder
			if( is_customize_preview() ) $skip = 'true';

			//xml rpc, ajax, admin
			if( ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || isset($_POST['_wpnonce']) || (function_exists( "wp_doing_ajax" ) && wp_doing_ajax()) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || isset( $_SERVER["HTTP_X_REQUESTED_WITH"] ) )  $skip = 'true';

			//matomo
			if( strpos( $_SERVER['REQUEST_URI'], 'plugins/matomo/app' ) !== false ) $skip = 'true';

			//is_json
			if( ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) || strpos( $_SERVER['REQUEST_URI'], '/wp-json/' ) !== false ) $skip = 'true';

			if( defined( 'REST_REQUEST' ) ) $skip = 'true';

			//wp-login and similar pages
			if( MyAgilePrivacy::is_wplogin() ) $skip = 'true';

			//rest request
			if (defined( 'REST_REQUEST' ) && REST_REQUEST // (#1)
					|| isset($_GET['rest_route']) // (#2)
							&& strpos( $_GET['rest_route'], '/', 0 ) === 0)
					 $skip = 'true';

			//post
			if( !empty( $_POST ) ) $skip = 'true_due_to_post';
		}

		return $skip;
	}


	/**
	 * Returns $do_not_send_in_clear_settings_key
	 */
	public static function get_do_not_send_in_clear_settings_key()
	{
		$do_not_send_in_clear_settings_key = array(
			'website_name',
			'identity_name',
			'identity_address',
			'identity_vat_id',
			'identity_email',
			'dpo_email',
			'dpo_name',
			'dpo_address',
			'license_code',
			'customer_email',
			'parse_config',
			'dpo_email',
		);

		return $do_not_send_in_clear_settings_key;
	}


	/**
	 * Returns default settings
	 * @since    1.0.12
	 * @access   public
	 */
	public static function get_default_settings( $key='' )
	{
		$default_locale = get_locale();

		$settings = array(
			'is_on' 									=> 	true,
			'is_bottom'									=>	'',
			'cookie_banner_vertical_position'			=> 	null,
			'cookie_banner_horizontal_position'			=> 	null,
			'cookie_banner_size'						=> 	null,
			'cookie_banner_shadow'						=> 	false,
			'cookie_banner_animation'					=> 	'none',
			'floating_banner'							=> 	0,
			'elements_border_radius'					=> 	15,
			'heading_background_color'					=> 	'#F14307',
			'heading_text_color'						=> 	'#ffffff',
			'close_icon_color'							=> 	'#ffffff',
			'title_is_on'								=> 	false,
			'bar_heading_text'							=>	'',
			'notify_message'							=> 	addslashes ( __( '<div class="map-area-container"><div data-nosnippet class="map_notification-message">This website uses technical and profiling cookies. Clicking on "Accept" authorises all profiling cookies. Clicking on "Refuse" or the X will refuse all profiling cookies. By clicking on "Customise" you can select which profiling cookies to activate.[myagileprivacy_extra_info]</div><div class="map_notification_container">[myagileprivacy_cookie_accept][myagileprivacy_cookie_reject][myagileprivacy_cookie_customize]</div></div>', 'myagileprivacy' ) ),
			'notify_message_v2'							=> 	addslashes ( __( 'This website uses technical and profiling cookies. Clicking on "Accept" authorises all profiling cookies. Clicking on "Refuse" or the X will refuse all profiling cookies. By clicking on "Customise" you can select which profiling cookies to activate.[myagileprivacy_extra_info]', 'myagileprivacy' ) ),
			'background' 								=> 	'#ffffff',
			'text' 										=> 	'#333333',
			'text_size'									=> 	18,
			'text_lineheight'							=> 	30,
			'show_buttons_icons'						=> 	false,
			'button_accept_text'						=> 	__( 'Accept', 'myagileprivacy' ),
			'button_accept_link_color' 					=> 	'#ffffff',
			'accept_button_animation_delay'				=> 	5,
			'accept_button_animation_repeat'			=> 	1,
			'accept_button_animation_effect'			=> 'shakeX',
			'button_accept_button_color' 				=> 	'#3d3d3d',
			'button_reject_text'						=> 	__( 'Refuse', 'myagileprivacy' ),
			'button_reject_link_color' 					=> 	'#fff',
			'button_reject_button_color' 				=> 	'#3d3d3d',
			'button_customize_text'						=> 	__( 'Customize', 'myagileprivacy' ),
			'button_customize_link_color' 				=> 	'#ffffff',
			'button_customize_button_color' 			=> 	'#3d3d3d',
			'map_inline_notify_color'					=>	'#444444',
			'map_inline_notify_background'				=>	'#FFF3CD',
			'website_name'								=>	'',
			'identity_name'								=>	'',
			'identity_address'							=>	'',
			'identity_vat_id'							=>	'',
			'identity_email'							=>	'',
			'dpo_email'									=> 	'',
			'dpo_name'									=> 	'',
			'dpo_address'								=> 	'',
			'license_code'								=>	'',
			'license_user_status'						=>	'Demo License',
			'is_dm'										=>	true,
			'license_valid'								=>	true,
			'grace_period'								=>	false,
			'customer_email'							=>	null,
			'summary_text'								=>	null,
			'notify_div_id' 							=> '#my-agile-privacy-notification-area',
			'showagain_tab' 							=> 	true,
			'wrap_shortcodes'							=>	false,
			'showagain_text'	 						=> 	__( 'Manage consent', 'myagileprivacy' ),
			'notify_position_horizontal'				=> 	'right',
			'showagain_div_id' 							=> 	'my-agile-privacy-consent-again',
			'cookie_policy_link'						=>	false,
			'is_cookie_policy_url'						=>	false,
			'cookie_policy_url'							=>	null,
			'cookie_policy_page'						=> 	self::nullCoalesce( get_option( 'wp_page_for_privacy_policy' ), 0 ),
			'is_personal_data_policy_url'				=>	false,
			'personal_data_policy_url'					=>	null,
			'personal_data_policy_page'					=>	0,
			'last_sync'									=>	null,
			'default_locale'							=>	$default_locale,
			'disable_logo'								=>	false,
			'wl'										=>	0,
			'pa'										=>	0,
			'last_legit_sync'							=>	null,
			'custom_css'								=>	null,
			'scan_mode'									=>	'turned_off',
			'blocked_content_notify'					=>	false,
			'blocked_content_notify_auto_shutdown'		=>	true,
			'blocked_content_notify_auto_shutdown_time'	=>	3000,
			'video_advanced_privacy'					=>	true,
			'maps_block'								=>	true,
			'captcha_block'								=>	true,
			'parse_config'								=>	null,
			'scanner_compatibility_mode'				=>	false,
			'scanner_hook_type'							=>	'init-shutdown',
			'scanner_start_hook_prio'					=>	-10000,
			'scanner_end_hook_prio'						=>	-10000,
			'scanner_log'								=>	null,
			'alt_accepted_all_cookie_name'				=>	null,
			'alt_accepted_something_cookie_name'		=>	null,
			'learning_mode_last_active_timestamp'		=>	null,
			'enforce_youtube_privacy'					=>	false,
			'client_force_reinject_jquery'				=>	false,
			'display_dpo'								=>	false,
			'dpo_email'									=>	null,
			'display_ccpa'								=>	false,
			'display_lpd'								=>	false,
			'show_ntf_bar_on_not_yet_consent_choice'	=>	false,
			'with_css_effects'							=>	true,
			'forced_legacy_mode'						=>	false,
			'missing_cookie_shield'						=> 	false,
			'missing_cookie_shield_timestamp'			=> 	null,
			'cookie_shield_running'						=>	false,
			'cookie_shield_running_timestamp'			=>	null,
			'forced_auto_update'						=>	true,
			'enable_iab_tcf'							=>	false,
			'enable_metadata_sync'						=>	true,

			'enable_cmode_v2'							=>	false,
			'enable_cmode_url_passthrough'				=> 	false,
			'cmode_v2_implementation_type'				=> 	'native',
			'cmode_v2_gtag_ad_storage'					=> 	'denied',
			'cmode_v2_gtag_ad_user_data'				=> 	'denied',
			'cmode_v2_gtag_ad_personalization'			=> 	'denied',
			'cmode_v2_gtag_analytics_storage'			=> 	'denied',
			'cmode_v2_forced_off_ga4_advanced'			=>	false,
		);

		$settings = apply_filters( 'map_plugin_settings', $settings);

		return $key != "" ? $settings[ $key ] : $settings;
	}

	/**
	 * Returns list of HTML tags allowed in HTML fields for use in declaration of wp_kset field validation.
	 * @since    1.0.12
	 * @access   public
	 */
	public static function allowed_html_tags()
	{
		$allowed_html = array(
			'a' => array(
				'href' => array(),
				'id' => array(),
				'class' => array(),
				'title' => array(),
				'target' => array(),
				'rel' => array(),
				'style' => array(),
				'role' => array(),
				'data-map_action' => array(),
				'data-nosnippet' => array(),
				'data-animate' => array(),
				'data-animation-effect' => array(),
				'data-animation-delay'=>array(),
				'data-animation-repeat'=>array(),
				'tabindex'=>array(),
				'aria-pressed'=>array(),
			),
			'input' => array(
				'id' => array(),
				'name'=> array(),
				'type'=> array(),
				'value'=> array(),
				'class'=> array(),
				'data-cookie-baseindex'=>array(),
				'data-default-color'=>array(),
				'data-preview'=>array(),
			),
			'b' => array(),
			'br' => array(
				'id' => array(),
				'class' => array(),
				'style' => array()
			),
			'div' => array(
				'id' => array(),
				'class' => array(),
				'style' => array(),
				'data-nosnippet' => array(),
				'data-map_action'=> array(),
				'data-cookie-baseindex'=>array(),
				'data-cookie-name'=>array(),
				'data-animation'=>array(),
			),
			'em' => array (
				'id' => array(),
				'class' => array(),
				'style' => array()
			),
			'i' => array(),
			'img' => array(
				'src' => array(),
				'id' => array(),
				'class' => array(),
				'alt' => array(),
				'style' => array()
			),
			'p' => array (
				'id' => array(),
				'class' => array(),
				'style' => array()
			),
			'span' => array(
				'id' => array(),
				'class' => array(),
				'style' => array()
			),
			'strong' => array(
				'id' => array(),
				'class' => array(),
				'style' => array()
			),
			'h1' => array(
				'id' => array(),
				'class' => array(),
				'style' => array()
			),
			'h2' => array(
				'id' => array(),
				'class' => array(),
				'style' => array()
			),
			'h3' => array(
				'id' => array(),
				'class' => array(),
				'style' => array()
			),
			'h4' => array(
				'id' => array(),
				'class' => array(),
				'style' => array()
			),
			'h5' => array(
				'id' => array(),
				'class' => array(),
				'style' => array()
			),
			'h6' => array(
				'id' => array(),
				'class' => array(),
				'style' => array()
			),
			'label' => array(
				'id' => array(),
				'class' => array(),
				'style' => array(),
				'for' => array(),
				'data-map-enable' => array(),
				'data-map-disable' => array(),
			),
			'option' => array(
				'name' => array(),
				'value' => array(),
				'selected' => array(),
			),
			'iframe' => array(
				'id' => array(),
				'src' => array(),
				'class' => array(),
				'style' => array(),
			),
		);
		$html5_tags=array( 'article','section','aside','details','figcaption','figure','footer','header','main','mark','nav','summary','time' );
		foreach($html5_tags as $html5_tag)
		{
			$allowed_html[$html5_tag]=array(
				'id' => array(),
				'class' => array(),
				'style' => array()
			);
		}
		return $allowed_html;
	}


	/**
	 * Returns list of allowed protocols, used in wp_kset field validation.
	 * @since    1.0.12
	 * @access   public
	 */
	public static function allowed_protocols()
	{
		return array ( 'http', 'https' );
	}

	/**
	 * Returns JSON object containing user settings
	 * @since    1.0.12
	 * @access   public
	 */
	public static function get_json_settings()
	{
		$settings = self::get_settings();
		$rconfig = self::get_rconfig();

		$logged_in_and_admin = false;
		$internal_debug = false;

		if( current_user_can( 'manage_options' ) && $settings['pa'] == 1 )
		{
			$logged_in_and_admin = true;
			$internal_debug = true;
		}

		$verbose_remote_log = false;

		if( isset( $rconfig ) &&
    		isset( $rconfig['verbose_remote_log'] ) &&
    		$rconfig['verbose_remote_log'] )
    	{
    		$verbose_remote_log = true;
    	}

		$return_settings = array(
			'logged_in_and_admin'						=>	$logged_in_and_admin,
			'verbose_remote_log'						=>	$verbose_remote_log,
			'internal_debug'							=>	$internal_debug,
			'notify_div_id'								=> 	$settings['notify_div_id'],
			'showagain_tab'								=> 	$settings['showagain_tab'],
			'notify_position_horizontal'				=> 	$settings['notify_position_horizontal'],
			'showagain_div_id'							=> 	$settings['showagain_div_id'],
			'blocked_content_text'						=>	__( 'Warning: some page functionalities could not work due to your privacy choices. Click here to review consent.', 'myagileprivacy' ),
			'inline_notify_color'						=>	$settings['map_inline_notify_color'],
			'inline_notify_background'					=>	$settings['map_inline_notify_background'],
			'blocked_content_notify_auto_shutdown_time'	=>	$settings['blocked_content_notify_auto_shutdown_time'],

			'scan_mode'									=>	$settings['scan_mode'],
			'cookie_reset_timestamp'					=>	( isset( $settings['cookie_reset_timestamp'] ) ) ? '_'.$settings['cookie_reset_timestamp'] : null,
			'show_ntf_bar_on_not_yet_consent_choice'	=>	$settings['show_ntf_bar_on_not_yet_consent_choice'],

			'enable_cmode_v2'							=> 	$settings['enable_cmode_v2'],
			'enable_cmode_url_passthrough'				=>	$settings['enable_cmode_url_passthrough'],
			'cmode_v2_forced_off_ga4_advanced'			=>	$settings['cmode_v2_forced_off_ga4_advanced'],
		);

		return $return_settings;
	}

	/**
	 * f for cleaning hex color
	 */
	public static function clean_hex_color( $hex )
	{
		$hex = strtolower($hex);

		//remove the leading "#"
		if (strlen($hex) == 7 || strlen($hex) == 4)
			$hex = substr($hex, -(strlen($hex) - 1));

		// $hex like "1a7"
		if (preg_match('/^[a-f0-9]{6}$/i', $hex))
			return '#'.$hex;
		// $hex like "162a7b"
		elseif (preg_match('/^[a-f0-9]{3}$/i', $hex))
			return '#'.$hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		//any other format
		else
			return "#000000";
	}

	/**
	 * Makes a call to the WP License Manager API.
	 *
	 * @param $params   array   The parameters for the API call
	 * @return          array   The API response
	 * @since    1.0.12
	 * @access   public
	 */
	public static function call_api( $params )
	{
		$url = MAP_API_ENDPOINT;

		$site_url = null;

		if( function_exists( 'get_site_url' ) )
		{
			$site_url = get_site_url();
		}

		// Set up arguments for POST request
		$args = array(
			'sslverify' =>	false,
			'headers' 	=>	array(
				'Referer' 	=> $site_url,
			),
			'body' 		=>	$params
		);

		// Send the request
		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) )
		{
			//let's try http
			$http_response = wp_remote_post( str_replace( 'https://','http://', $url ), $args );

			if( !is_wp_error( $http_response ) )
			{
				$response_body = wp_remote_retrieve_body( $http_response );
				$result = json_decode( $response_body, true );

				return $result;
			}
			else
			{
				if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $http_response );

				$error_code = array_key_first( $response->errors );
				$error_message = $response->errors[ $error_code ][0];

				$error_code_http = array_key_first( $http_response->errors );
				$error_message_http = $http_response->errors[ $error_code ][0];

				$result = array(
					'internal_error_message'	=>	"$error_code -> $error_message , $error_code_http -> $error_message_http",
				);

				return $result;
			}

			return false;
		}

		$response_body = wp_remote_retrieve_body( $response );
		$result = json_decode( $response_body, true );

		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $result );

		return $result;
	}


	/*
	f for getting in 2 char page language
	 */
	public static function getCurrentLang2char()
	{
		$the_settings = MyAgilePrivacy::get_settings();

		$current_lang = get_locale();

		if( isset( $the_settings['default_locale'] ) )
		{
			$current_lang = $the_settings['default_locale'];
		}

		global $locale;
		global $sitepress;
		$is_wpml_enabled = false;
		$is_polylang_enabled = false;
		$with_multilang = false;

		if( function_exists( 'icl_object_id' ) && $sitepress )
		{
			$is_wpml_enabled = true;
			$with_multilang = true;
			$current_lang = ICL_LANGUAGE_CODE;
		}

		if( defined( 'POLYLANG_FILE' ) &&
			function_exists( 'pll_default_language' ) &&
			function_exists( 'pll_languages_list' ) )
		{
			$is_polylang_enabled = true;
			$with_multilang = true;
			$current_lang = pll_current_language();
		}

		$current_lang_two_char = substr( $current_lang, 0, 2 );

		switch( $current_lang_two_char )
		{
			case 'it':
				return 'it';
				break;

			case 'fr':
				return 'fr';
				break;

			case 'es':
				return 'es';
				break;

			case 'de':
				return 'de';
				break;

			default:
				return 'en';
				break;
		}

		return 'it';
	}


	/**
	 * get cache base directory url
	 */
	public static function get_base_url_for_cache()
	{
		if( !defined( 'MAP_PLUGIN_NAME' ) ) return null;

		$current_plugin_url = plugin_dir_url( MAP_PLUGIN_FILENAME );

		$final_url = $current_plugin_url. '/local-cache/'.MAP_PLUGIN_NAME.'/';

		//remove unnecessary slashes
		$final_url = preg_replace( '/([^:])(\/{2,})/', '$1/', $final_url );

		return  $final_url;
	}

	/**
	 * get cache base directory url
	 */
	public static function get_base_directory_for_cache()
	{
		if( !defined( 'MAP_PLUGIN_NAME' ) ) return null;

		$current_plugin_dir = plugin_dir_path( MAP_PLUGIN_FILENAME );

		return $current_plugin_dir . '/local-cache/'.MAP_PLUGIN_NAME.'/';
	}

	/**
	 * check for file exists
	 */
	public static function cached_file_exists( $local_filename )
	{
		$directory = MyAgilePrivacy::get_base_directory_for_cache();

		if( $directory )
		{
			$local_filename_fullpath = $directory.$local_filename;

			if ( is_file( $local_filename_fullpath ) )
			{
				return true;
			}
		}

		return false;
	}


	/**
	 * download remote file
	 */
	public static function download_remote_file( $remote_filename, $local_filename, $version_number=null, $alt_local_filename=null )
	{
		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( "download_remote_file call with param remote_filename=$remote_filename, local_filename=$local_filename, version_number=$version_number, alt_local_filename=$alt_local_filename" );

		$directory = MyAgilePrivacy::get_base_directory_for_cache();

		if( !$directory )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'missing get_base_directory_for_cache' );
			return false;
		}

		$local_filename_fullpath = $directory.$local_filename;
		$local_alt_filename_fullpath = null;

		if( $alt_local_filename )
		{
			$local_alt_filename_fullpath = $directory.$alt_local_filename;
		}

		$expiration_time_in_seconds = 60*60*24;
		$max_age = time() - $expiration_time_in_seconds;

		$manifest_assoc = get_option( MAP_MANIFEST_ASSOC, null );

		if( $manifest_assoc &&
			isset( $manifest_assoc['files'][ $local_filename ] ) &&
			$manifest_assoc['files'][ $local_filename ] &&
			$version_number &&
			$alt_local_filename )
		{
			if( version_compare( $manifest_assoc['files'][ $local_filename ]['version'], $version_number , '>=' ) &&
				is_file( $local_alt_filename_fullpath ) )
			{
				//no download needed
				if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'check A : no download needed' );

				return true;
			}
			else
			{
				if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER )
				{
					$debug_info = array(
						'remote_version_number'			=>	$manifest_assoc['files'][ $local_filename ]['version'],
						'this_version_number'			=>	$version_number,
						'version_check'					=>	version_compare( $manifest_assoc['files'][ $local_filename ]['version'], $version_number , '>=' ),
						'local_alt_filename_fullpath'	=> 	$local_alt_filename_fullpath,
						'local_alt_filename_check'		=>	is_file( $local_alt_filename_fullpath ),

					);

					MyAgilePrivacy::write_log( $debug_info );
				}
			}
		}
		else
		{
			if( $alt_local_filename )
			{
				if ( is_file( $local_filename_fullpath ) && filemtime( $local_filename_fullpath ) > $max_age &&
					is_file( $local_alt_filename_fullpath ) && filemtime( $local_alt_filename_fullpath ) > $max_age
				)
				{
					//no download needed
					if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'check B : no download needed' );
					return true;
				}
			}
			else
			{
				if ( is_file( $local_filename_fullpath ) && filemtime( $local_filename_fullpath ) > $max_age )
				{
					//no download needed
					if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'check C : no download needed' );
					return true;
				}
			}
		}

		if( file_exists( $local_filename_fullpath ) )
		{
			wp_delete_file( $local_filename_fullpath );
		}

		if( $alt_local_filename )
		{
			if( file_exists( $local_alt_filename_fullpath ) )
			{
				wp_delete_file( $local_alt_filename_fullpath );
			}
		}

		if( ! wp_mkdir_p( $directory ) )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'Error creating needed directory: ' . $directory );
			return false;
		}

		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$tmp_file = download_url( $remote_filename );

		if( !$tmp_file || !is_string( $tmp_file ) )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'Error downloading remote_filename: ' . $remote_filename );
			return false;
		}

		copy( $tmp_file, $local_filename_fullpath );

		if( $alt_local_filename )
		{
			copy( $tmp_file, $local_alt_filename_fullpath );
		}

		if( file_exists( $tmp_file ) ) @unlink( $tmp_file );

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'download_remote_file -> remote file downloaded to '.$local_filename_fullpath . ' from '.$remote_filename );

		//old folder cleanup
		$old_cache_dir = WP_CONTENT_DIR . '/local-cache/'.MAP_PLUGIN_NAME.'/';
		MyAgilePrivacy::clear_cache( $old_cache_dir, true ) ;

		return true;
	}

	/**
	 * clear file cache
	 */
	public static function clear_cache( $directory = null, $remove_dir = false )
	{
		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( "clear_cache with params directory=$directory, remove_dir=$remove_dir" );

		if( !$directory )
		{
			$directory = MyAgilePrivacy::get_base_directory_for_cache();
		}

		if( !$directory )
		{
			return false;
		}

		if( !is_dir( $directory ) )
		{
			return false;
		}

		$objects = scandir( $directory );
		foreach ( $objects as $object ) {
			if ( $object != "." && $object != ".." ) {
				if ( is_dir( $directory . DIRECTORY_SEPARATOR . $object ) && ! is_link( $directory . "/" . $object ) ) {
					MyAgilePrivacy::clear_cache( $directory . DIRECTORY_SEPARATOR . $object );
				} else {
					$this_filepath = $directory . DIRECTORY_SEPARATOR . $object;

					if( file_exists( $this_filepath ) ) @unlink( $this_filepath );
				}
			}
		}

		if( $remove_dir )
		{
			rmdir( $directory );
		}

		return true;
	}


	/**
	 * equivalent for php7 null coalesce
	 */
	public static function nullCoalesce( $var, $default = null )
	{
		return isset( $var ) ? $var : $default;
	}


	/**
	 * equivalent for php7 null coalesce (array)
	 */
	public static function nullCoalesceArrayItem( $var, $key, $default = null )
	{
		return isset( $var[ $key ] ) ? $var[ $key ] : $default;
	}


	/**
	 * summarize post meta attributes
	 */
	public static function summarizeMeta( $all_meta )
	{
		$summary = array();

		foreach( $all_meta as $k => $v )
		{
			if( is_array( $v ) )
			{
				$summary[ $k ] = $v[0];
			}
		}

		return $summary;
	}


	/**
	 * get server footprint
	 */
	public static function getServerFootPrint()
	{
		$return_data = array();

		$keysToRemove = array(
			'HTTP_COOKIE',
			'HTTP_USER_AGENT',
			'HTTP_X_REAL_IP',
			'HTTP_X_REMOTE_IP',
			'HTTP_CF_CONNECTING_IP',
			'HTTP_CF_IPCOUNTRY',
			'SERVER_ADDR',
			'REMOTE_ADDR',
			'PROXY_REMOTE_ADDR',
			'SSL_CLIENT_CERT',
			'SSL_SERVER_CERT'
		);

		foreach( $_SERVER as $k => $v )
		{
			if( in_array( $k, $keysToRemove ) )
			{
				$v = '(set)';
			}

			$return_data[ $k ] = $v;
		}

		return $return_data;
	}

	//f for cache purge
	public static function tryCacheClear()
	{
		//w3 total cache
		if( function_exists( 'w3tc_pgcache_flush' ) )
		{
			w3tc_pgcache_flush();
		}

 		//wordpress
		if( function_exists( 'wp_cache_clear_cache' ) )
		{
			wp_cache_clear_cache();
		}

		//sg optimizer
		if( function_exists( 'sg_cachepress_purge_cache' ) )
		{
			sg_cachepress_purge_cache();
		}

		//wp rocket
		if( function_exists( 'rocket_clean_domain' ) )
		{
			rocket_clean_domain();
		}

		//WP Fastest Cache
		if( function_exists( 'wpfc_clear_all_cache' ) )
		{
			wpfc_clear_all_cache();
		}
	}

	/**
	* sort frontend cookies in order to give prio to google_tag_manager execution
	*/
	public static function frontendCookieSort( $a, $b )
	{
	    if( $a['api_key'] == 'google_tag_manager' )
	    {
	        return -1;
	    }
	    elseif( $b['api_key'] == 'google_tag_manager' )
	    {
	        return 1;
	    }
	    return 0;
	}


	/**
	 * write to log file
	 * @since    1.0.12
	 * @access   public
	 */
	public static function write_log($log)
	{
		if( defined( 'MAP_PLUGIN_NAME' ) )
		{
			$plugin_name = MAP_PLUGIN_NAME;
		}
		else
		{
			$plugin_name = 'my-agile-privacy';
		}

		$dirPath = WP_CONTENT_DIR . '/debug/';
		$filePath = $dirPath.$plugin_name.'.txt';

		if( ! wp_mkdir_p( $dirPath ) )
		{
			return;
		}

		$bt = debug_backtrace();

		$depth = 0;

		$file = isset($bt[$depth])     ? $bt[$depth]['file'] : null;
		$line = isset($bt[$depth])     ? $bt[$depth]['line'] : 0;
		$func = isset($bt[$depth + 1]) ? $bt[$depth + 1]['function'] : null;

		if (is_array($log) || is_object($log)) {
			$data = print_r($log, true);
		} else {
			$data = $log;
		}

		$string = "file=$file, line=$line, func=$func: ".$data."\n";

		file_put_contents( $filePath, $string, FILE_APPEND );
	}
}