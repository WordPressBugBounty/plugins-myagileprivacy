<?php

if( !defined( 'MAP_PLUGIN_NAME' ) )
{
	exit('Not allowed.');
}

/**
 * Core definitions
 */

define( 'MAP_SOFTWARE_KEY', 'map_wp' );
define( 'MAP_PLUGIN_TEXTDOMAIN', 'MAP_txt' );
define( 'MAP_PLUGIN_DB_KEY_PREFIX', 'MyAgilePrivacy-' );
define( 'MAP_PLUGIN_SETTINGS_FIELD', MAP_PLUGIN_DB_KEY_PREFIX . '1.0.0' );
define( 'MAP_PLUGIN_JS_DETECTED_FIELDS', MAP_PLUGIN_DB_KEY_PREFIX . '_detected_fields' );
define( 'MAP_PLUGIN_RCONFIG', MAP_PLUGIN_DB_KEY_PREFIX . '_rconfig' );
define( 'MAP_PLUGIN_L_ALLOWED', MAP_PLUGIN_DB_KEY_PREFIX . '_l_allowed' );
define( 'MAP_PLUGIN_COMPLIANCE_REPORT', MAP_PLUGIN_DB_KEY_PREFIX . '_compliance_report' );
define( 'MAP_PLUGIN_STATS', MAP_PLUGIN_DB_KEY_PREFIX . 'stats' );
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
define( 'MAP_LEGIT_SYNC_TRESHOLD', 10800 );
define( 'MAP_AUTORESET_SYNC_TRESHOLD', 259200 ); // 3 days
define( 'MAP_PLUGIN_ACTIVATION_DATE', MAP_PLUGIN_DB_KEY_PREFIX.'-activation_date' );
define( 'MAP_REVIEW_STATUS', MAP_PLUGIN_DB_KEY_PREFIX.'-review_status' );
define( 'MAP_NOTICE_LAST_SHOW_TIME', MAP_PLUGIN_DB_KEY_PREFIX.'-notice_last_show_time' );
define( 'MAP_NOTICE_FIRST_TRESHOLD', 604800 ); // 7 days: 7 * 24 * 60 * 60
define( 'MAP_NOTICE_SECOND_TRESHOLD', 12960000 ); // 5 months: 5 * 30 * 24 * 60 * 60
define( 'MAP_SUPPORTED_LANGUAGES', array(
		'en_US'	=>	array(
							'label' => 	'English',
							'2char' => 	'en',
						),
		'it_IT'	=>	array(
							'label' => 	'Italiano',
							'2char' => 	'it',
						),
		'fr_FR'	=>	array(
							'label' => 	'Français',
							'2char' => 	'fr',
						),
		'de_DE'	=>	array(
							'label' => 	'Deutsch',
							'2char' => 	'de',
						),
		'es_ES'	=>	array(
							'label' => 	'Español',
							'2char' => 	'es',
						),
		'pt_PT'	=>	array(
							'label' => 	'Português',
							'2char' => 	'pt',
						),
		'nl_NL'	=>	array(
							'label' => 	'Nederlands',
							'2char' => 	'nl',
						),
		'pl_PL'	=>	array(
							'label' => 	'Polski',
							'2char' => 	'pl',
						),
		'el'	=>	array(
							'label' => 	'Elliniká',
							'2char' => 	'el',
						),
) );
define( 'MAP_DB_PATCH_1_DONE', false );
define( 'MAP_EXPORT_FORMAT_VERSION', '2.0.0' );

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

		if ( !self::get_option( MAP_PLUGIN_ACTIVATION_DATE, null ) )
		{
			self::update_option( MAP_PLUGIN_ACTIVATION_DATE, time() );
		}
	}

	/**
	 * f for plugin deactivation
	 */
	public static function map_plugin_deactivate() {

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'calling map_plugin_deactivate' );

		if( defined( 'MAP_PLUGIN_ACTIVATION_DATE' ) && self::get_option( MAP_PLUGIN_ACTIVATION_DATE, null ) ) delete_option( MAP_PLUGIN_ACTIVATION_DATE );
		if( defined( 'MAP_REVIEW_STATUS' ) && self::get_option( MAP_REVIEW_STATUS, null ) ) delete_option( MAP_REVIEW_STATUS );
		if( defined( 'MAP_NOTICE_LAST_SHOW_TIME' ) && self::get_option( MAP_NOTICE_LAST_SHOW_TIME, null ) ) delete_option( MAP_NOTICE_LAST_SHOW_TIME );
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

		$review_status = self::get_option( MAP_REVIEW_STATUS, null );
		$last_show_time = self::get_option( MAP_NOTICE_LAST_SHOW_TIME, null );
		$activation_date = self::get_option( MAP_PLUGIN_ACTIVATION_DATE, null );

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

		//upgrader_process_complete
		add_action( 'upgrader_process_complete', array( $plugin_admin, 'plugin_upgrade_callback' ), 10, 2);

		//wp_footer hook
		add_action( 'wp_footer', array( $plugin_admin, 'triggered_do_cron_sync' ) );

		/* admin callback actions */
		add_action( 'wp_ajax_nopriv_check_license_status', array( $plugin_admin, 'check_license_status' ) );
		add_action( 'wp_ajax_check_license_status', array( $plugin_admin, 'check_license_status' ) );


		if( !is_admin() )
		{
			return;
		}

		//repeated on admin_footer
		add_action( 'admin_footer', array( $plugin_admin, 'triggered_do_cron_sync' ) );

		//admin menu
		add_action( 'admin_menu', array( $plugin_admin, 'add_admin_pages' ), 11 );

		//cookie list / policy dashboard
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

		add_action( 'wp_ajax_nopriv_update_translations_form', array( $plugin_admin, 'update_translations_form_callback' ) );
		add_action( 'wp_ajax_update_translations_form', array( $plugin_admin, 'update_translations_form_callback' ) );

		//admin post actions
		add_action( 'admin_post_backup_admin_settings_form', array( $plugin_admin, 'backup_admin_settings_form_callback' ) );

		add_action( 'admin_post_import_admin_settings_form', array( $plugin_admin, 'import_admin_settings_form_callback' ) );

		//generic admin styles
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_styles' ) );

		//generic admin scripts
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts' ) );

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
			if ( wp_next_scheduled( 'my_agile_privacy_do_cron_sync_hook' ) )
			{
				wp_clear_scheduled_hook( 'my_agile_privacy_do_cron_sync_hook' );
			}

			//clean old daily event schedule if exists
			if ( wp_next_scheduled( 'my_agile_privacy_do_cron_sync_once_day_hook' ) )
			{
				wp_clear_scheduled_hook( 'my_agile_privacy_do_cron_sync_once_day_hook' );
			}

			//schedule an action if it's not already scheduled
			if ( ! wp_next_scheduled( 'my_agile_privacy_do_cron_sync_twice_day_hook' ) )
			{
				wp_schedule_event( time(), 'twicedaily', 'my_agile_privacy_do_cron_sync_twice_day_hook' );
			}
		}
		else
		{
			//clean old daily event schedule if exists
			if ( wp_next_scheduled( 'my_agile_privacy_do_cron_sync_hook' ) )
			{
				wp_clear_scheduled_hook( 'my_agile_privacy_do_cron_sync_hook' );
			}

			//clean twice a day event schedule if exists
			if ( wp_next_scheduled( 'my_agile_privacy_do_cron_sync_twice_day_hook' ) )
			{
				wp_clear_scheduled_hook( 'my_agile_privacy_do_cron_sync_twice_day_hook' );
			}

			//clean once a day event schedule if exists
			if ( wp_next_scheduled( 'my_agile_privacy_do_cron_sync_once_day_hook' ) )
			{
				wp_clear_scheduled_hook( 'my_agile_privacy_do_cron_sync_once_day_hook' );
			}
		}

		if( !( isset( $rconfig ) &&
				isset( $rconfig['disable_install_counter'] ) &&
				$rconfig['disable_install_counter'] == 1 ) )
		{
			//schedule an action if it's not already scheduled
			if ( !wp_next_scheduled( 'my_agile_privacy_do_cron_sync_install_counter' ) )
			{
				wp_schedule_event( time(), 'daily', 'my_agile_privacy_do_cron_sync_install_counter' );
			}

			if( !self::get_option( MAP_PLUGIN_STATS, null ) )
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
		$currentAndSupportedLanguages = self::getCurrentAndSupportedLanguages();

		return $currentAndSupportedLanguages['with_multilang'];
	}

	/**
	 * Get rconfig settings.
	 */
	public static function get_rconfig()
	{
		return self::get_option( MAP_PLUGIN_RCONFIG, array() );
	}

	/**
	 * Get current settings.
	 * @since    1.0.12
	 * @access   public
	 */
	public static function get_settings()
	{
		$settings = self::get_default_settings();

		self::$stored_options = self::get_option( MAP_PLUGIN_SETTINGS_FIELD, array() );

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
			'background' 								=> 	'#ffffff',
			'text' 										=> 	'#333333',
			'text_size'									=> 	18,
			'text_lineheight'							=> 	30,
			'show_buttons_icons'						=> 	false,
			'button_accept_link_color' 					=> 	'#ffffff',
			'accept_button_animation_delay'				=> 	5,
			'accept_button_animation_repeat'			=> 	1,
			'accept_button_animation_effect'			=> 'shakeX',
			'button_accept_button_color' 				=> 	'#3d3d3d',
			'button_reject_link_color' 					=> 	'#fff',
			'button_reject_button_color' 				=> 	'#3d3d3d',
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
			'notify_position_horizontal'				=> 	'right',
			'showagain_div_id' 							=> 	'my-agile-privacy-consent-again',
			'cookie_policy_link'						=>	false,
			'is_cookie_policy_url'						=>	false,
			'cookie_policy_url'							=>	null,
			'cookie_policy_page'						=> 	self::get_option( 'wp_page_for_privacy_policy', 0 ),
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

			'fixed_translations_encoded'				=>	null,
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

		//get translations
		$the_translations = MyAgilePrivacy::getFixedTranslations();
		$current_lang = MyAgilePrivacy::getCurrentLang4Char();

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
			'blocked_content_text'						=>	esc_html( $the_translations[ $current_lang ]['blocked_content'] ).'.',
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

		$manifest_assoc = self::get_option( MAP_MANIFEST_ASSOC, null );

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

		if ( !function_exists( 'download_url' ) )
		{
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
			if( is_array( $keysToRemove ) &&
				in_array( $k, $keysToRemove ) )
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

	//f for avoiding polylang filters
	public static function get_option( $option_name, $default = false )
	{
		if( !( defined( 'POLYLANG_FILE' ) &&
			function_exists( 'pll_default_language' ) &&
			function_exists( 'pll_languages_list' ) ) )
		{
			return get_option( $option_name, $default );
		}
		else
		{
			global $wpdb;

			// Check the cache first
			$cached_value = wp_cache_get($option_name, 'options');

			if( $cached_value !== false )
			{
				return $cached_value;
			}

			// Fetch the option value directly from the database
			$option_value = $wpdb->get_var($wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
				$option_name
			));

			if( $option_value !== null )
			{
				// Unserialize if necessary
				$option_value = maybe_unserialize( $option_value );

				// Store in cache for future requests
				wp_cache_set( $option_name, $option_value, 'options' );

				return $option_value;
			}
			else
			{
				// Store the default value in cache if not found
				wp_cache_set( $option_name, $default, 'options' );

				return $default;
			}
		}
	}

	//f for avoiding polylang filters
	public static function update_option( $option_name, $new_value )
	{
		if( !( defined( 'POLYLANG_FILE' ) &&
			function_exists( 'pll_default_language' ) &&
			function_exists( 'pll_languages_list' ) ) )
		{
			return update_option( $option_name, $new_value );
		}
		else
		{
			global $wpdb;

			// Ensure the option name is not empty
			if( empty($option_name ) )
			{
				return false;
			}

			// Serialize the value if necessary
			$serialized_value = maybe_serialize( $new_value );

			// Check if the option already exists in the database using a count query
			$option_exists = $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name = %s",
				$option_name
			));

			if( $option_exists > 0 )
			{
				// Update the existing option
				$result = $wpdb->update(
					$wpdb->options,
					array('option_value' => $serialized_value),
					array('option_name' => $option_name),
					array('%s'),
					array('%s')
				);

				if( $result === false )
				{
					return false;
				}
			}
			else
			{
				// Insert a new option
				$autoload = 'yes'; // Default autoload setting
				$result = $wpdb->insert(
					$wpdb->options,
					array(
						'option_name'  => $option_name,
						'option_value' => $serialized_value,
						'autoload'     => $autoload
					),
					array('%s', '%s', '%s')
				);

				if( $result === false )
				{
					return false;
				}
			}

			// Clear the cache for this option
			wp_cache_delete( $option_name, 'options' );

			return true;
		}
	}


	/**
	get final translation table
	*/
	public static function getFixedTranslations()
	{
		$settings = self::get_settings();

		$default_txt = array();

		$default_txt['it_IT'] = array();
		$default_txt['it_IT']['always_enable'] = 'Sempre Abilitato';
		$default_txt['it_IT']['is_enabled'] = 'Abilitato';
		$default_txt['it_IT']['is_disabled'] = 'Disabilitato';
		$default_txt['it_IT']['blocked_content'] = 'Attenzione: alcune funzionalità di questa pagina potrebbero essere bloccate a seguito delle tue scelte privacy';
		$default_txt['it_IT']['notify_message_v2'] = 'Questo sito utilizza cookie tecnici e di profilazione. Cliccando su accetta si autorizzano tutti i cookie di profilazione. Cliccando su rifiuta o la X si rifiutano tutti i cookie di profilazione. Cliccando su personalizza è possibile selezionare quali cookie di profilazione attivare.';
		$default_txt['it_IT']['view_the_cookie_policy'] = 'Visualizza la Cookie Policy';
		$default_txt['it_IT']['view_the_personal_data_policy'] = "Visualizza l'Informativa Privacy";
		$default_txt['it_IT']['manage_consent'] = 'Gestisci il consenso';
		$default_txt['it_IT']['close'] = 'Chiudi';
		$default_txt['it_IT']['privacy_settings'] = 'Impostazioni privacy';
		$default_txt['it_IT']['this_website_uses_cookies'] = 'Questo sito utilizza i cookie per migliorare la tua esperienza di navigazione su questo sito.';
		$default_txt['it_IT']['cookies_and_thirdy_part_software'] = 'Cookie e software di terze parti';
		$default_txt['it_IT']['advertising_preferences'] = 'Preferenze pubblicitarie';
		$default_txt['it_IT']['additional_consents'] = 'Consensi aggiuntivi';
		$default_txt['it_IT']['ad_storage'] = 'Ad Storage';
		$default_txt['it_IT']['ad_user_data'] = 'Ad User Data';
		$default_txt['it_IT']['ad_personalization'] = 'Ad Personalization';
		$default_txt['it_IT']['analytics_storage'] = 'Analytics Storage';
		$default_txt['it_IT']['ad_storage_desc'] = 'Definisce se i cookie relativi alla pubblicità possono essere letti o scritti da Google.';
		$default_txt['it_IT']['ad_user_data_desc'] = "Determina se i dati dell'utente possono essere inviati a Google per scopi pubblicitari.";
		$default_txt['it_IT']['ad_personalization_desc'] = 'Controlla se la pubblicità personalizzata (ad esempio, il remarketing) può essere abilitata.';
		$default_txt['it_IT']['analytics_storage_desc'] = 'Definisce se i cookie associati a Google Analytics possono essere letti o scritti.';
		$default_txt['it_IT']['banner_title'] = '';
		$default_txt['it_IT']['accept'] = 'Accetta';
		$default_txt['it_IT']['refuse'] = 'Rifiuta';
		$default_txt['it_IT']['customize'] = 'Personalizza';
		$default_txt['it_IT']['ga_4_version'] = 'Google Analytics nella versione 4 (GA4)';
		$default_txt['it_IT']['facebook_remarketing'] = 'Facebook Remarketing';
		$default_txt['it_IT']['tiktok_pixel'] = 'TikTok Pixel';
		$default_txt['it_IT']['in_addition_this_site_installs'] = 'Inoltre, questo sito installa';
		$default_txt['it_IT']['with_anonymous_data_transmission_via_proxy'] = 'con trasmissione di dati anonimi tramite proxy.';
		$default_txt['it_IT']['by_giving_your_consent_the_data_will_be_sent_anonymously'] = "Prestando il consenso, l'invio dei dati sarà effettuato in maniera anonima, tutelando così la tua privacy.";
		$default_txt['it_IT']['lpd_compliance_text'] = 'Questo sito è conforme alla Legge sulla Protezione dei Dati (LPD), Legge Federale Svizzera del 25 settembre 2020, e al GDPR, Regolamento UE 2016/679, relativi alla protezione dei dati personali nonché alla libera circolazione di tali dati.';
		$default_txt['it_IT']['iab_bannertext_1'] = 'Noi e i nostri partner pubblicitari selezionati possiamo archiviare e/o accedere alle informazioni sul tuo dispositivo, come i cookie, identificatori unici, dati di navigazione.';
		$default_txt['it_IT']['iab_bannertext_2_a'] = 'Puoi sempre scegliere gli scopi specifici legati al profilo accedendo al';
		$default_txt['it_IT']['iab_bannertext_2_link'] = 'pannello delle preferenze pubblicitarie';
		$default_txt['it_IT']['iab_bannertext_2_b'] = ', e puoi sempre revocare il tuo consenso in qualsiasi momento facendo clic su "Gestisci consenso" in fondo alla pagina.';
		$default_txt['it_IT']['iab_bannertext_3'] = 'Elenco di alcune possibili autorizzazioni pubblicitarie';
		$default_txt['it_IT']['iab_bannertext_4_a'] = 'Puoi consultare: la nostra lista di';
		$default_txt['it_IT']['iab_bannertext_4_b'] = 'partner pubblicitari';
		$default_txt['it_IT']['iab_bannertext_5'] = 'la Cookie Policy';
		$default_txt['it_IT']['iab_bannertext_6'] = 'e la Privacy Policy';
		$default_txt['it_IT']['vat_id'] = 'Partita IVA';
		$default_txt['it_IT']['google_recaptcha_content_notification_a'] = 'Le tue scelte cookie potrebbero non consentire l\'invio del modulo. Puoi rivedere le tue scelte';
		$default_txt['it_IT']['google_recaptcha_content_notification_b'] = 'facendo clic qui';


		$default_txt['en_US'] = array();
		$default_txt['en_US']['always_enable'] = 'Always Enabled';
		$default_txt['en_US']['is_enabled'] = 'Enabled';
		$default_txt['en_US']['is_disabled'] = 'Disabled';
		$default_txt['en_US']['blocked_content'] = 'Warning: some page functionalities could not work due to your privacy choices';
		$default_txt['en_US']['notify_message_v2'] = 'This website uses technical and profiling cookies. Clicking on "Accept" authorizes all profiling cookies. Clicking on "Refuse" or the "X" will refuse all profiling cookies. By clicking on "Customize" you can select which profiling cookies to activate.';
		$default_txt['en_US']['view_the_cookie_policy'] = 'View the Cookie Policy';
		$default_txt['en_US']['view_the_personal_data_policy'] = 'View the Personal Data Policy';
		$default_txt['en_US']['manage_consent'] = 'Manage consent';
		$default_txt['en_US']['close'] = 'Close';
		$default_txt['en_US']['privacy_settings'] = 'Privacy Settings';
		$default_txt['en_US']['this_website_uses_cookies'] = 'This website uses cookies to improve your experience while you navigate through the website.';
		$default_txt['en_US']['cookies_and_thirdy_part_software'] = 'Cookies and third-party software';
		$default_txt['en_US']['advertising_preferences'] = 'Advertising preferences';
		$default_txt['en_US']['additional_consents'] = 'Additional consents';
		$default_txt['en_US']['ad_storage'] = 'Ad Storage';
		$default_txt['en_US']['ad_user_data'] = 'Ad User Data';
		$default_txt['en_US']['ad_personalization'] = 'Ad Personalization';
		$default_txt['en_US']['analytics_storage'] = 'Analytics Storage';
		$default_txt['en_US']['ad_storage_desc'] = 'Defines whether cookies related to advertising can be read or written by Google.';
		$default_txt['en_US']['ad_user_data_desc'] = 'Determines whether user data can be sent to Google for advertising purposes.';
		$default_txt['en_US']['ad_personalization_desc'] = 'Controls whether personalized advertising (for example, remarketing) can be enabled.';
		$default_txt['en_US']['analytics_storage_desc'] = 'Defines whether cookies associated with Google Analytics can be read or written.';
		$default_txt['en_US']['banner_title'] = '';
		$default_txt['en_US']['accept'] = 'Accept';
		$default_txt['en_US']['refuse'] = 'Refuse';
		$default_txt['en_US']['customize'] = 'Customize';
		$default_txt['en_US']['ga_4_version'] = 'Google Analytics version 4 (GA4)';
		$default_txt['en_US']['facebook_remarketing'] = 'Facebook Remarketing';
		$default_txt['en_US']['tiktok_pixel'] = 'TikTok Pixel';
		$default_txt['en_US']['in_addition_this_site_installs'] = 'In addition, this site installs';
		$default_txt['en_US']['with_anonymous_data_transmission_via_proxy'] = 'with anonymous data transmission via proxy.';
		$default_txt['en_US']['by_giving_your_consent_the_data_will_be_sent_anonymously'] = 'By giving your consent, the data will be sent anonymously, thus protecting your privacy.';
		$default_txt['en_US']['lpd_compliance_text'] = 'This site complies with the Data Protection Act (LPD), Swiss Federal Law of September 25, 2020, and the GDPR, EU Regulation 2016/679, regarding the protection of personal data and the free movement of such data.';
		$default_txt['en_US']['iab_bannertext_1'] = 'We and our selected ad partners can store and/or access information on your device, such as cookies, unique identifiers and browsing data.';
		$default_txt['en_US']['iab_bannertext_2_a'] = 'You can always choose the specific purposes related to profiling by accessing the';
		$default_txt['en_US']['iab_bannertext_2_link'] = 'advertising preferences panel';
		$default_txt['en_US']['iab_bannertext_2_b'] = ', and you can withdraw your consent at any time by clicking on "Manage consent" at the bottom of the page.';
		$default_txt['en_US']['iab_bannertext_3'] = 'List of some possible advertising permissions';
		$default_txt['en_US']['iab_bannertext_4_a'] = 'You can consult: our list of';
		$default_txt['en_US']['iab_bannertext_4_b'] = 'advertising partners';
		$default_txt['en_US']['iab_bannertext_5'] = 'the Cookie Policy';
		$default_txt['en_US']['iab_bannertext_6'] = 'and the Privacy Policy';
		$default_txt['en_US']['vat_id'] = 'VAT ID';
		$default_txt['en_US']['google_recaptcha_content_notification_a'] = 'Your cookie choices may not allow the form to be submitted. You can review your choices by';
		$default_txt['en_US']['google_recaptcha_content_notification_b'] = 'clicking here';

		$default_txt['fr_FR'] = array();
		$default_txt['fr_FR']['always_enable'] = 'Toujours activé';
		$default_txt['fr_FR']['is_enabled'] = 'Activé';
		$default_txt['fr_FR']['is_disabled'] = 'Désactivé';
		$default_txt['fr_FR']['blocked_content'] = 'Avertissement: certaines fonctionnalités de la page pourraient ne pas fonctionner en raison de vos choix de confidentialité';
		$default_txt['fr_FR']['notify_message_v2'] = 'Ce site utilise des cookies techniques et de profilage. En cliquant sur "Accepter", vous autorisez tous les cookies de profilage. En cliquant sur "Refuser" ou sur le "X", vous refusez tous les cookies de profilage. En cliquant sur Personnaliser, vous pouvez sélectionner les cookies de profilage à activer.';
		$default_txt['fr_FR']['view_the_cookie_policy'] = 'Politique relative aux cookies';
		$default_txt['fr_FR']['view_the_personal_data_policy'] = 'Consultez la politique de données personnelles';
		$default_txt['fr_FR']['manage_consent'] = 'Consentement à la politique de confidentialité';
		$default_txt['fr_FR']['close'] = 'Close';
		$default_txt['fr_FR']['privacy_settings'] = 'Paramètres de confidentialité';
		$default_txt['fr_FR']['this_website_uses_cookies'] = 'Ce site utilise des cookies pour améliorer votre expérience de navigation.';
		$default_txt['fr_FR']['cookies_and_thirdy_part_software'] = 'Cookies et logiciels tiers';
		$default_txt['fr_FR']['advertising_preferences'] = 'Préférences publicitaires';
		$default_txt['fr_FR']['additional_consents'] = 'Consents supplémentaires';
		$default_txt['fr_FR']['ad_storage'] = 'Ad Storage';
		$default_txt['fr_FR']['ad_user_data'] = 'Ad User Data';
		$default_txt['fr_FR']['ad_personalization'] = 'Ad Personalization';
		$default_txt['fr_FR']['analytics_storage'] = 'Analytics Storage';
		$default_txt['fr_FR']['ad_storage_desc'] = 'Définit si les cookies liés à la publicité peuvent être lus ou écrits par Google.';
		$default_txt['fr_FR']['ad_user_data_desc'] = 'Détermine si les données utilisateur peuvent être envoyées à Google à des fins publicitaires.';
		$default_txt['fr_FR']['ad_personalization_desc'] = 'Contrôle si la publicité personnalisée (par exemple, le remarketing) peut être activée.';
		$default_txt['fr_FR']['analytics_storage_desc'] = 'Définit si les cookies associés à Google Analytics peuvent être lus ou écrits.';
		$default_txt['fr_FR']['banner_title'] = '';
		$default_txt['fr_FR']['accept'] = 'Accepter';
		$default_txt['fr_FR']['refuse'] = 'Refuse';
		$default_txt['fr_FR']['customize'] = 'Personnaliser';
		$default_txt['fr_FR']['ga_4_version'] = 'Google Analytics version 4 (GA4)';
		$default_txt['fr_FR']['facebook_remarketing'] = 'Facebook Remarketing';
		$default_txt['fr_FR']['tiktok_pixel'] = 'TikTok Pixel';
		$default_txt['fr_FR']['in_addition_this_site_installs'] = 'En outre, ce site installe';
		$default_txt['fr_FR']['with_anonymous_data_transmission_via_proxy'] = 'avec transmission de données anonymes via un proxy.';
		$default_txt['fr_FR']['by_giving_your_consent_the_data_will_be_sent_anonymously'] = 'En donnant votre consentement, l’envoi des données sera effectué de manière anonyme, protégeant ainsi votre vie privée.';
		$default_txt['fr_FR']['lpd_compliance_text'] = 'Ce site est conforme à la Loi sur la Protection des Données (LPD), Loi Fédérale Suisse du 25 septembre 2020, et au RGPD, Règlement UE 2016/679, concernant la protection des données personnelles ainsi que la libre circulation de ces données.';
		$default_txt['fr_FR']['iab_bannertext_1'] = 'Nous et nos partenaires publicitaires sélectionnés pouvons stocker et/ou accéder aux informations sur votre appareil, telles que les cookies, les identifiants uniques, les données de navigation.';
		$default_txt['fr_FR']['iab_bannertext_2_a'] = 'Vous pouvez toujours choisir les objectifs spécifiques liés au profilage en accédant au';
		$default_txt['fr_FR']['iab_bannertext_2_link'] = 'panneau des préférences publicitaires';
		$default_txt['fr_FR']['iab_bannertext_2_b'] = ', et vous pouvez toujours retirer votre consentement à tout moment en cliquant sur "Gérer le consentement" en bas de la page.';
		$default_txt['fr_FR']['iab_bannertext_3'] = 'Liste de quelques autorisations publicitaires possibles';
		$default_txt['fr_FR']['iab_bannertext_4_a'] = 'Vous pouvez consulter: notre liste de';
		$default_txt['fr_FR']['iab_bannertext_4_b'] = 'partenaires publicitaires';
		$default_txt['fr_FR']['iab_bannertext_5'] = 'la Politique relative aux cookies';
		$default_txt['fr_FR']['iab_bannertext_6'] = 'et la Politique de confidentialité';
		$default_txt['fr_FR']['vat_id'] = 'VAT ID';
		$default_txt['fr_FR']['google_recaptcha_content_notification_a'] = 'Veuillez noter: vos choix de cookies peuvent ne pas permettre de soumettre le formulaire. Vous pouvez revoir vos choix en';
		$default_txt['fr_FR']['google_recaptcha_content_notification_b'] = 'cliquant ici';

		$default_txt['es_ES'] = array();
		$default_txt['es_ES']['always_enable'] = 'Siempre activado';
		$default_txt['es_ES']['is_enabled'] = 'Activado';
		$default_txt['es_ES']['is_disabled'] = 'Deshabilitado';
		$default_txt['es_ES']['blocked_content'] = 'Advertencia: algunas funciones de esta página pueden estar bloqueadas como resultado de sus opciones de privacidad';
		$default_txt['es_ES']['notify_message_v2'] = 'Este sitio utiliza cookies técnicas y de elaboración de perfiles . Al hacer clic en "Aceptar" , se autorizan todas las cookies de elaboración de perfiles. Al hacer clic en "Rechazar" o en "X" , se rechazan todas las cookies de elaboración de perfiles. Al hacer clic en "Personalizar" , se pueden seleccionar cookies de elaboración de perfiles para su activación.';
		$default_txt['es_ES']['view_the_cookie_policy'] = 'Política de cookies';
		$default_txt['es_ES']['view_the_personal_data_policy'] = 'Consulte la política de datos personales';
		$default_txt['es_ES']['manage_consent'] = 'Consentimiento de privacidad';
		$default_txt['es_ES']['close'] = 'Close';
		$default_txt['es_ES']['privacy_settings'] = 'Ajustes de privacidad';
		$default_txt['es_ES']['this_website_uses_cookies'] = 'Este sitio utiliza cookies para mejorar su experiencia de navegación.';
		$default_txt['es_ES']['cookies_and_thirdy_part_software'] = 'Cookies y software de terceros';
		$default_txt['es_ES']['advertising_preferences'] = 'Preferencias publicitarias';
		$default_txt['es_ES']['additional_consents'] = 'Consentimientos adicionales';
		$default_txt['es_ES']['ad_storage'] = 'Ad Storage';
		$default_txt['es_ES']['ad_user_data'] = 'Ad User Data';
		$default_txt['es_ES']['ad_personalization'] = 'Ad Personalization';
		$default_txt['es_ES']['analytics_storage'] = 'Analytics Storage';
		$default_txt['es_ES']['ad_storage_desc'] = 'Defines whether cookies related to advertising can be read or written by Google.';
		$default_txt['es_ES']['ad_user_data_desc'] = 'Determina si los datos del usuario pueden ser enviados a Google con fines publicitarios.';
		$default_txt['es_ES']['ad_personalization_desc'] = 'Controla si se puede habilitar la publicidad personalizada (por ejemplo, remarketing).';
		$default_txt['es_ES']['analytics_storage_desc'] = 'Define si se pueden leer o escribir cookies asociadas a Google Analytics.';
		$default_txt['es_ES']['banner_title'] = '';
		$default_txt['es_ES']['accept'] = 'Aceptar';
		$default_txt['es_ES']['refuse'] = 'Rechazar';
		$default_txt['es_ES']['customize'] = 'Personalizar';
		$default_txt['es_ES']['ga_4_version'] = 'la versión 4 de Google Analytics (GA4)';
		$default_txt['es_ES']['facebook_remarketing'] = 'Facebook Remarketing';
		$default_txt['es_ES']['tiktok_pixel'] = 'TikTok Pixel';
		$default_txt['es_ES']['in_addition_this_site_installs'] = 'Además, este sitio instala';
		$default_txt['es_ES']['with_anonymous_data_transmission_via_proxy'] = 'con transmisión de datos anónimos mediante «proxy».';
		$default_txt['es_ES']['by_giving_your_consent_the_data_will_be_sent_anonymously'] = 'Al dar tu consentimiento, el envío de los datos se realizará de forma anónima y así tu privacidad quedará protegida.';
		$default_txt['es_ES']['lpd_compliance_text'] = 'Este sitio cumple con la Ley de Protección de Datos (LPD), Ley Federal Suiza del 25 de septiembre de 2020, y el GDPR, Reglamento de la UE 2016/679, respecto a la protección de datos personales así como la libre circulación de dichos datos.';
		$default_txt['es_ES']['iab_bannertext_1'] = 'Nosotros y nuestros socios publicitarios seleccionados podemos almacenar y/o acceder a información en su dispositivo, como cookies, identificadores únicos, datos de navegación.';
		$default_txt['es_ES']['iab_bannertext_2_a'] = 'Siempre puedes elegir los propósitos específicos relacionados con el perfilado accediendo al';
		$default_txt['es_ES']['iab_bannertext_2_link'] = 'panel de preferencias de publicidad';
		$default_txt['es_ES']['iab_bannertext_2_b'] = ', y siempre puedes retirar su consentimiento en cualquier momento haciendo clic en "Gestionar consentimiento" en la parte inferior de la página.';
		$default_txt['es_ES']['iab_bannertext_3'] = 'Lista de algunos permisos publicitarios posibles';
		$default_txt['es_ES']['iab_bannertext_4_a'] = 'Puedes consultar: nuestra lista de';
		$default_txt['es_ES']['iab_bannertext_4_b'] = 'socios publicitarios';
		$default_txt['es_ES']['iab_bannertext_5'] = 'la Política de cookies';
		$default_txt['es_ES']['iab_bannertext_6'] = 'y la Política de privacidad';
		$default_txt['es_ES']['vat_id'] = 'VAT ID';
		$default_txt['es_ES']['google_recaptcha_content_notification_a'] = 'Tenga en cuenta: sus elecciones de cookies pueden no permitir el envío del formulario. Puede revisar sus opciones';
		$default_txt['es_ES']['google_recaptcha_content_notification_b'] = 'haciendo clic aquí';

		$default_txt['de_DE'] = array();
		$default_txt['de_DE']['always_enable'] = 'Immer aktiviert';
		$default_txt['de_DE']['is_enabled'] = 'Aktiviert';
		$default_txt['de_DE']['is_disabled'] = 'Deaktiviert';
		$default_txt['de_DE']['blocked_content'] = 'Warnung: Einige Funktionen dieser Seite können aufgrund Ihrer Datenschutzeinstellungen blockiert werden';
		$default_txt['de_DE']['notify_message_v2'] = 'Diese Website verwendet technische und Profiling-Cookies. Durch Klicken auf "Akzeptieren" autorisieren Sie alle Profiling-Cookies . Durch Klicken auf "Ablehnen" oder das "X" werden alle Profiling-Cookies abgelehnt. Durch Klicken auf "Anpassen" können Sie auswählen, welche Profiling-Cookies aktiviert werden sollen';
		$default_txt['de_DE']['view_the_cookie_policy'] = 'Cookie-Richtlinie';
		$default_txt['de_DE']['view_the_personal_data_policy'] = 'Sehen Sie sich die Datenschutzrichtlinie an';
		$default_txt['de_DE']['manage_consent'] = 'Zustimmung zum Datenschutz';
		$default_txt['de_DE']['close'] = 'Close';
		$default_txt['de_DE']['privacy_settings'] = 'Datenschutzeinstellungen';
		$default_txt['de_DE']['this_website_uses_cookies'] = 'Diese Website verwendet Cookies, um Ihr Surferlebnis zu verbessern.';
		$default_txt['de_DE']['cookies_and_thirdy_part_software'] = 'Cookies und Software von Drittanbietern';
		$default_txt['de_DE']['advertising_preferences'] = 'Werbepreferenzen';
		$default_txt['de_DE']['additional_consents'] = 'Additional consents';
		$default_txt['de_DE']['ad_storage'] = 'Ad Storage';
		$default_txt['de_DE']['ad_user_data'] = 'Ad User Data';
		$default_txt['de_DE']['ad_personalization'] = 'Ad Personalization';
		$default_txt['de_DE']['analytics_storage'] = 'Analytics Storage';
		$default_txt['de_DE']['ad_storage_desc'] = 'Legt fest, ob Cookies im Zusammenhang mit Werbung von Google gelesen oder geschrieben werden können.';
		$default_txt['de_DE']['ad_user_data_desc'] = 'Legt fest, ob Benutzerdaten zu Werbezwecken an Google gesendet werden können.';
		$default_txt['de_DE']['ad_personalization_desc'] = 'Steuert, ob personalisierte Werbung (zum Beispiel Remarketing) aktiviert werden kann.';
		$default_txt['de_DE']['analytics_storage_desc'] = 'Legt fest, ob Cookies, die mit Google Analytics verbunden sind, gelesen oder geschrieben werden können.';
		$default_txt['de_DE']['banner_title'] = '';
		$default_txt['de_DE']['accept'] = 'Akzeptieren';
		$default_txt['de_DE']['refuse'] = 'Ablehnen';
		$default_txt['de_DE']['customize'] = 'Benutzerdefiniert';
		$default_txt['de_DE']['ga_4_version'] = 'Google Analytics Version 4 (GA4)';
		$default_txt['de_DE']['facebook_remarketing'] = 'Facebook Remarketing';
		$default_txt['de_DE']['tiktok_pixel'] = 'TikTok Pixel';
		$default_txt['de_DE']['in_addition_this_site_installs'] = 'Darüber hinaus installiert diese Website';
		$default_txt['de_DE']['with_anonymous_data_transmission_via_proxy'] = 'mit anonymer Datenübertragung über Proxy.';
		$default_txt['de_DE']['by_giving_your_consent_the_data_will_be_sent_anonymously'] = 'Wenn Sie Ihre Zustimmung geben, werden die Daten anonym übermittelt, so dass Ihre Privatsphäre geschützt ist.';
		$default_txt['de_DE']['lpd_compliance_text'] = 'Diese Website entspricht dem Datenschutzgesetz (LPD), Schweizer Bundesgesetz vom 25. September 2020, und der DSGVO, EU-Verordnung 2016/679, bezüglich des Schutzes personenbezogener Daten sowie des freien Verkehrs solcher Daten.';
		$default_txt['de_DE']['iab_bannertext_1'] = 'Wir und unsere ausgewählten Werbepartner können Informationen auf Ihrem Gerät speichern und/oder darauf zugreifen, wie z.B. Cookies, eindeutige Kennungen und Browserdaten.';
		$default_txt['de_DE']['iab_bannertext_2_a'] = 'Sie können jederzeit die spezifischen Zwecke in Bezug auf das Profiling auswählen, indem Sie auf das';
		$default_txt['de_DE']['iab_bannertext_2_link'] = 'Werbepräferenz-Panel';
		$default_txt['de_DE']['iab_bannertext_2_b'] = 'zugreifen, und Sie können Ihre Einwilligung jederzeit widerrufen, indem Sie unten auf der Seite auf "Einwilligung verwalten" klicken.';
		$default_txt['de_DE']['iab_bannertext_3'] = 'Liste einiger möglicher Werbeeinwilligungen';
		$default_txt['de_DE']['iab_bannertext_4_a'] = 'Sie können unsere Liste: mit';
		$default_txt['de_DE']['iab_bannertext_4_b'] = 'Werbepartnern';
		$default_txt['de_DE']['iab_bannertext_5'] = 'die Cookie-Richtlinie';
		$default_txt['de_DE']['iab_bannertext_6'] = 'und die Datenschutzrichtlinie einsehen';
		$default_txt['de_DE']['vat_id'] = 'VAT ID';
		$default_txt['de_DE']['google_recaptcha_content_notification_a'] = 'Bitte beachten: kann es sein, dass Ihre Cookie-Auswahl das Absenden des Formulars nicht zulässt. Sie können Ihre Auswahl überprüfen, indem Sie';
		$default_txt['de_DE']['google_recaptcha_content_notification_b'] = 'hier klicken';

		$default_txt['pt_PT'] = array();
		$default_txt['pt_PT']['always_enable'] = 'Sempre Ativado';
		$default_txt['pt_PT']['is_enabled'] = 'Ativado';
		$default_txt['pt_PT']['is_disabled'] = 'Desativado';
		$default_txt['pt_PT']['blocked_content'] = 'Aviso: algumas funcionalidades da página podem não funcionar devido às suas escolhas de privacidade';
		$default_txt['pt_PT']['notify_message_v2'] = 'Este site utiliza cookies técnicos e de perfilagem . Clicar em "Aceitar" autoriza todos os cookies de perfilagem. Clicar em "Recusar" ou no "X" recusa todos os cookies de perfilagem. Clicando em "Personalizar" , você pode selecionar quais cookies de perfilagem ativar.';
		$default_txt['pt_PT']['view_the_cookie_policy'] = 'Ver a Política de Cookies';
		$default_txt['pt_PT']['view_the_personal_data_policy'] = 'Ver a Política de Dados Pessoais';
		$default_txt['pt_PT']['manage_consent'] = 'Gerenciar consentimento';
		$default_txt['pt_PT']['close'] = 'Fechar';
		$default_txt['pt_PT']['privacy_settings'] = 'Configurações de Privacidade';
		$default_txt['pt_PT']['this_website_uses_cookies'] = 'Este site utiliza cookies para melhorar sua experiência enquanto você navega pelo site.';
		$default_txt['pt_PT']['cookies_and_thirdy_part_software'] = 'Cookies e software de terceiros';
		$default_txt['pt_PT']['advertising_preferences'] = 'Preferências de publicidade';
		$default_txt['pt_PT']['additional_consents'] = 'Consentimentos adicionais';
		$default_txt['pt_PT']['ad_storage'] = 'Armazenamento de anúncios';
		$default_txt['pt_PT']['ad_user_data'] = 'Dados de usuário de anúncios';
		$default_txt['pt_PT']['ad_personalization'] = 'Personalização de anúncios';
		$default_txt['pt_PT']['analytics_storage'] = 'Armazenamento de análises';
		$default_txt['pt_PT']['ad_storage_desc'] = 'Define se cookies relacionados à publicidade podem ser lidos ou escritos pelo Google.';
		$default_txt['pt_PT']['ad_user_data_desc'] = 'Determina se dados de usuário podem ser enviados ao Google para fins publicitários.';
		$default_txt['pt_PT']['ad_personalization_desc'] = 'Controla se a publicidade personalizada (por exemplo, remarketing) pode ser ativada.';
		$default_txt['pt_PT']['analytics_storage_desc'] = 'Define se cookies associados ao Google Analytics podem ser lidos ou escritos.';
		$default_txt['pt_PT']['banner_title'] = '';
		$default_txt['pt_PT']['accept'] = 'Aceitar';
		$default_txt['pt_PT']['refuse'] = 'Recusar';
		$default_txt['pt_PT']['customize'] = 'Personalizar';
		$default_txt['pt_PT']['ga_4_version'] = 'Google Analytics versão 4 (GA4)';
		$default_txt['pt_PT']['facebook_remarketing'] = 'Remarketing do Facebook';
		$default_txt['pt_PT']['tiktok_pixel'] = 'Pixel do TikTok';
		$default_txt['pt_PT']['in_addition_this_site_installs'] = 'Além disso, este site instala';
		$default_txt['pt_PT']['with_anonymous_data_transmission_via_proxy'] = 'com transmissão de dados anônima via proxy.';
		$default_txt['pt_PT']['by_giving_your_consent_the_data_will_be_sent_anonymously'] = 'Ao dar seu consentimento, os dados serão enviados anonimamente, protegendo assim sua privacidade.';
		$default_txt['pt_PT']['lpd_compliance_text'] = 'Este site está em conformidade com a Lei Federal de Proteção de Dados (LPD) da Suíça, de 25 de setembro de 2020 e com o RGPD, Regulamento da UE 2016/679, em relação à proteção de dados pessoais, bem como a livre circulação de tais dados.';
		$default_txt['pt_PT']['iab_bannertext_1'] = 'Nós e nossos parceiros publicitários selecionados podemos armazenar e/ou acessar informações em seu dispositivo, como cookies, identificadores únicos e dados de navegação.';
		$default_txt['pt_PT']['iab_bannertext_2_a'] = 'Você sempre pode escolher os propósitos específicos relacionados ao perfil acessando o';
		$default_txt['pt_PT']['iab_bannertext_2_link'] = 'painel de preferências de publicidade';
		$default_txt['pt_PT']['iab_bannertext_2_b'] = ', e pode sempre retirar seu consentimento a qualquer momento clicando em "Gerenciar consentimento" no final da página.';
		$default_txt['pt_PT']['iab_bannertext_3'] = 'Lista de algumas permissões publicitárias possíveis';
		$default_txt['pt_PT']['iab_bannertext_4_a'] = 'Você pode consultar: nossa lista de';
		$default_txt['pt_PT']['iab_bannertext_4_b'] = 'parceiros de publicidade';
		$default_txt['pt_PT']['iab_bannertext_5'] = 'a Política de Cookies';
		$default_txt['pt_PT']['iab_bannertext_6'] = 'e a Política de Privacidade';
		$default_txt['pt_PT']['vat_id'] = 'ID de IVA';
		$default_txt['pt_PT']['google_recaptcha_content_notification_a'] = 'Suas escolhas de cookies podem não permitir o envio do formulário. Você pode revisar suas escolhas';
		$default_txt['pt_PT']['google_recaptcha_content_notification_b'] = 'clicando aqui';

		$default_txt['nl_NL'] = array();
		$default_txt['nl_NL']['always_enable'] = 'Altijd Ingeschakeld';
		$default_txt['nl_NL']['is_enabled'] = 'Ingeschakeld';
		$default_txt['nl_NL']['is_disabled'] = 'Uitgeschakeld';
		$default_txt['nl_NL']['blocked_content'] = 'Waarschuwing: sommige functionaliteiten van de pagina kunnen niet werken vanwege uw privacykeuzes';
		$default_txt['nl_NL']['notify_message_v2'] = 'Deze website maakt gebruik van technische en profileringscookies . Door op "Accepteren" te klikken, machtigt u alle profileringscookies. Door op "Weigeren" of de "X" te klikken, weigert u alle profileringscookies. Door op "Aanpassen" te klikken, kunt u selecteren welke profileringscookies u wilt activeren.';
		$default_txt['nl_NL']['view_the_cookie_policy'] = 'Bekijk het Cookiebeleid';
		$default_txt['nl_NL']['view_the_personal_data_policy'] = 'Bekijk het Beleid voor Persoonsgegevens';
		$default_txt['nl_NL']['manage_consent'] = 'Beheer toestemmingen';
		$default_txt['nl_NL']['close'] = 'Sluiten';
		$default_txt['nl_NL']['privacy_settings'] = 'Privacy-instellingen';
		$default_txt['nl_NL']['this_website_uses_cookies'] = 'Deze website maakt gebruik van cookies om uw ervaring te verbeteren terwijl u door de website navigeert.';
		$default_txt['nl_NL']['cookies_and_thirdy_part_software'] = 'Cookies en software van derden';
		$default_txt['nl_NL']['advertising_preferences'] = 'Advertentievoorkeuren';
		$default_txt['nl_NL']['additional_consents'] = 'Aanvullende toestemmingen';
		$default_txt['nl_NL']['ad_storage'] = 'Advertentieopslag';
		$default_txt['nl_NL']['ad_user_data'] = 'Advertentiegebruikersgegevens';
		$default_txt['nl_NL']['ad_personalization'] = 'Advertentiepersonalisatie';
		$default_txt['nl_NL']['analytics_storage'] = 'AnalysegOpslag';
		$default_txt['nl_NL']['ad_storage_desc'] = 'Bepaalt of cookies gerelateerd aan advertenties kunnen worden gelezen of geschreven door Google.';
		$default_txt['nl_NL']['ad_user_data_desc'] = 'Bepaalt of gebruikersgegevens naar Google kunnen worden verzonden voor advertentiedoeleinden.';
		$default_txt['nl_NL']['ad_personalization_desc'] = 'Bepaalt of gepersonaliseerde advertenties (bijvoorbeeld remarketing) kunnen worden ingeschakeld.';
		$default_txt['nl_NL']['analytics_storage_desc'] = 'Bepaalt of cookies die gekoppeld zijn aan Google Analytics kunnen worden gelezen of geschreven.';
		$default_txt['nl_NL']['banner_title'] = '';
		$default_txt['nl_NL']['accept'] = 'Accepteren';
		$default_txt['nl_NL']['refuse'] = 'Weigeren';
		$default_txt['nl_NL']['customize'] = 'Aanpassen';
		$default_txt['nl_NL']['ga_4_version'] = 'Google Analytics versie 4 (GA4)';
		$default_txt['nl_NL']['facebook_remarketing'] = 'Facebook Remarketing';
		$default_txt['nl_NL']['tiktok_pixel'] = 'TikTok Pixel';
		$default_txt['nl_NL']['in_addition_this_site_installs'] = 'Daarnaast installeert deze site';
		$default_txt['nl_NL']['with_anonymous_data_transmission_via_proxy'] = 'met anonieme gegevensoverdracht via proxy.';
		$default_txt['nl_NL']['by_giving_your_consent_the_data_will_be_sent_anonymously'] = 'Door uw toestemming te geven, worden de gegevens anoniem verzonden, waardoor uw privacy wordt beschermd.';
		$default_txt['nl_NL']['lpd_compliance_text'] = 'Deze site voldoet aan de Data Protection Act (LPD), de Zwitserse federale wet van 25 september 2020, en de GDPR, EU-verordening 2016/679, met betrekking tot de bescherming van persoonsgegevens en de vrije gegevensoverdracht.';
		$default_txt['nl_NL']['iab_bannertext_1'] = 'Wij en onze geselecteerde advertentiepartners kunnen informatie opslaan en/of openen op uw apparaat, zoals cookies, unieke identificatoren en browsegegevens.';
		$default_txt['nl_NL']['iab_bannertext_2_a'] = 'U kunt altijd de specifieke doeleinden met betrekking tot profilering kiezen door toegang te krijgen tot het';
		$default_txt['nl_NL']['iab_bannertext_2_link'] = 'advertentievoorkeurenpaneel';
		$default_txt['nl_NL']['iab_bannertext_2_b'] = ', en u kunt uw toestemming altijd intrekken door op "Beheer toestemmingen" onderaan de pagina te klikken.';
		$default_txt['nl_NL']['iab_bannertext_3'] = 'Lijst van enkele mogelijke reclame-machtigingen';
		$default_txt['nl_NL']['iab_bannertext_4_a'] = 'U kunt raadplegen: onze lijst van';
		$default_txt['nl_NL']['iab_bannertext_4_b'] = 'advertentiepartners';
		$default_txt['nl_NL']['iab_bannertext_5'] = 'het Cookiebeleid';
		$default_txt['nl_NL']['iab_bannertext_6'] = 'en het Privacybeleid';
		$default_txt['nl_NL']['vat_id'] = 'Btw-nummer';
		$default_txt['nl_NL']['google_recaptcha_content_notification_a'] = 'Uw cookie-keuzes kunnen misschien niet toestaan dat het formulier wordt ingediend. U kunt uw keuzes herzien door';
		$default_txt['nl_NL']['google_recaptcha_content_notification_b'] = 'hier te klikken';


		$default_txt['pl_PL'] = array();
		$default_txt['pl_PL']['always_enable'] = 'Zawsze Włączone';
		$default_txt['pl_PL']['is_enabled'] = 'Włączone';
		$default_txt['pl_PL']['is_disabled'] = 'Wyłączone';
		$default_txt['pl_PL']['blocked_content'] = 'Uwaga: niektóre funkcje strony mogą nie działać z powodu wybranych przez Ciebie opcji prywatności';
		$default_txt['pl_PL']['notify_message_v2'] = 'Ta strona używa technicznych i profilujących plików cookie . Klikając "Akceptuj" , zezwalasz na wszystkie profilujące pliki cookie. Klikając "Odrzuć" lub "X" , odrzucasz wszystkie profilujące pliki cookie. Klikając "Dostosuj" , możesz wybrać, które profilujące pliki cookie włączyć.';
		$default_txt['pl_PL']['view_the_cookie_policy'] = 'Zobacz Politykę Cookie';
		$default_txt['pl_PL']['view_the_personal_data_policy'] = 'Zobacz Politykę Danych Osobowych';
		$default_txt['pl_PL']['manage_consent'] = 'Zarządzaj zgodami';
		$default_txt['pl_PL']['close'] = 'Zamknij';
		$default_txt['pl_PL']['privacy_settings'] = 'Ustawienia Prywatności';
		$default_txt['pl_PL']['this_website_uses_cookies'] = 'Ta strona używa plików cookie, aby poprawić Twoje doświadczenie podczas przeglądania strony.';
		$default_txt['pl_PL']['cookies_and_thirdy_part_software'] = 'Pliki cookie i oprogramowanie stron trzecich';
		$default_txt['pl_PL']['advertising_preferences'] = 'Preferencje reklamowe';
		$default_txt['pl_PL']['additional_consents'] = 'Dodatkowe zgody';
		$default_txt['pl_PL']['ad_storage'] = 'Przechowywanie reklam';
		$default_txt['pl_PL']['ad_user_data'] = 'Dane użytkowników reklam';
		$default_txt['pl_PL']['ad_personalization'] = 'Personalizacja reklam';
		$default_txt['pl_PL']['analytics_storage'] = 'Przechowywanie analityki';
		$default_txt['pl_PL']['ad_storage_desc'] = 'Określa, czy pliki cookie związane z reklamami mogą być odczytywane lub zapisywane przez Google.';
		$default_txt['pl_PL']['ad_user_data_desc'] = 'Określa, czy dane użytkowników mogą być wysyłane do Google w celach reklamowych.';
		$default_txt['pl_PL']['ad_personalization_desc'] = 'Kontroluje, czy personalizowane reklamy (np. remarketing) mogą być włączone.';
		$default_txt['pl_PL']['analytics_storage_desc'] = 'Określa, czy pliki cookie związane z Google Analytics mogą być odczytywane lub zapisywane.';
		$default_txt['pl_PL']['banner_title'] = '';
		$default_txt['pl_PL']['accept'] = 'Akceptuj';
		$default_txt['pl_PL']['refuse'] = 'Odrzuć';
		$default_txt['pl_PL']['customize'] = 'Dostosuj';
		$default_txt['pl_PL']['ga_4_version'] = 'Google Analytics wersji 4 (GA4)';
		$default_txt['pl_PL']['facebook_remarketing'] = 'Remarketing na Facebooku';
		$default_txt['pl_PL']['tiktok_pixel'] = 'Piksel TikTok';
		$default_txt['pl_PL']['in_addition_this_site_installs'] = 'Dodatkowo, ta strona instaluje';
		$default_txt['pl_PL']['with_anonymous_data_transmission_via_proxy'] = 'z anonimową transmisją danych za pośrednictwem proxy.';
		$default_txt['pl_PL']['by_giving_your_consent_the_data_will_be_sent_anonymously'] = 'Dając swoją zgodę, dane będą przesyłane anonimowo, chroniąc w ten sposób Twoją prywatność.';
		$default_txt['pl_PL']['lpd_compliance_text'] = 'Ta strona jest zgodna z Ustawą o Ochronie Danych (LPD), Szwajcarską Federalną Ustawą z dnia 25 września 2020 r., oraz RODO, Rozporządzeniem EU 2016/679, dotyczącym ochrony danych osobowych oraz swobodnego przepływu tych danych.';
		$default_txt['pl_PL']['iab_bannertext_1'] = 'My i wybrani partnerzy reklamowi możemy przechowywać i/lub uzyskiwać dostęp do informacji na Twoim urządzeniu, takich jak pliki cookie, unikalne identyfikatory, dane przeglądania.';
		$default_txt['pl_PL']['iab_bannertext_2_a'] = 'Zawsze możesz wybrać konkretne cele związane z profilowaniem, uzyskując dostęp do';
		$default_txt['pl_PL']['iab_bannertext_2_link'] = 'panelu preferencji reklamowych';
		$default_txt['pl_PL']['iab_bannertext_2_b'] = ', a także zawsze możesz wycofać swoją zgodę w dowolnym momencie, klikając na "Zarządzaj zgodami" na dole strony.';
		$default_txt['pl_PL']['iab_bannertext_3'] = 'Lista niektórych możliwych pozwoleń reklamowych';
		$default_txt['pl_PL']['iab_bannertext_4_a'] = 'Możesz zapoznać się z: naszą listą';
		$default_txt['pl_PL']['iab_bannertext_4_b'] = 'partnerów reklamowych';
		$default_txt['pl_PL']['iab_bannertext_5'] = 'Polityką plików cookie';
		$default_txt['pl_PL']['iab_bannertext_6'] = 'oraz Polityką Prywatności';
		$default_txt['pl_PL']['vat_id'] = 'NIP';
		$default_txt['pl_PL']['google_recaptcha_content_notification_a'] = 'Twoje wybory dotyczące cookie mogą uniemożliwić wypełnienie formularza. Możesz przeglądać swoje wybory';
		$default_txt['pl_PL']['google_recaptcha_content_notification_b'] = 'klikając tutaj';


		$default_txt['el'] = array();
		$default_txt['el']['always_enable'] = 'Πάντα Ενεργοποιημένο';
		$default_txt['el']['is_enabled'] = 'Ενεργοποιήθηκε';
		$default_txt['el']['is_disabled'] = 'Απενεργοποιήθηκε';
		$default_txt['el']['blocked_content'] = 'Προειδοποίηση: ορισμένες λειτουργίες της σελίδας ενδέχεται να μην λειτουργούν λόγω των επιλογών απορρήτου σας';
		$default_txt['el']['notify_message_v2'] = 'Αυτός ο ιστότοπος χρησιμοποιεί τεχνικά και προφίλ cookies. Κάνοντας κλικ στο "Αποδοχή" εξουσιοδοτείτε όλα τα προφίλ cookies. Κάνοντας κλικ στο "Άρνηση" ή στο "X" θα αρνηθείτε όλα τα προφίλ cookies. Κάνοντας κλικ στο "Προσαρμογή" μπορείτε να επιλέξετε ποια προφίλ cookies θα ενεργοποιήσετε.';
		$default_txt['el']['view_the_cookie_policy'] = 'Δείτε την Πολιτική Cookies';
		$default_txt['el']['view_the_personal_data_policy'] = 'Δείτε την Πολιτική Προσωπικών Δεδομένων';
		$default_txt['el']['manage_consent'] = 'Διαχείριση συναίνεσης';
		$default_txt['el']['close'] = 'Κλείσιμο';
		$default_txt['el']['privacy_settings'] = 'Ρυθμίσεις απορρήτου';
		$default_txt['el']['this_website_uses_cookies'] = 'Αυτός ο ιστότοπος χρησιμοποιεί cookies για να βελτιώσει την εμπειρία σας καθώς πλοηγείστε στον ιστότοπο.';
		$default_txt['el']['cookies_and_thirdy_part_software'] = 'Cookies και λογισμικό τρίτων';
		$default_txt['el']['advertising_preferences'] = 'Προτιμήσεις διαφημίσεων';
		$default_txt['el']['additional_consents'] = 'Πρόσθετες συναινέσεις';
		$default_txt['el']['ad_storage'] = 'Αποθήκευση διαφημίσεων';
		$default_txt['el']['ad_user_data'] = 'Δεδομένα χρήστη διαφημίσεων';
		$default_txt['el']['ad_personalization'] = 'Εξατομίκευση διαφημίσεων';
		$default_txt['el']['analytics_storage'] = 'Αποθήκευση αναλύσεων';
		$default_txt['el']['ad_storage_desc'] = 'Καθορίζει εάν τα cookies που σχετίζονται με τη διαφήμιση μπορούν να διαβαστούν ή να γραφτούν από την Google.';
		$default_txt['el']['ad_user_data_desc'] = 'Καθορίζει εάν τα δεδομένα χρήστη μπορούν να σταλούν στην Google για διαφημιστικούς σκοπούς.';
		$default_txt['el']['ad_personalization_desc'] = 'Ελέγχει εάν μπορεί να ενεργοποιηθεί η εξατομικευμένη διαφήμιση (π.χ. επαναληπτικό μάρκετινγκ).';
		$default_txt['el']['analytics_storage_desc'] = 'Καθορίζει εάν τα cookies που σχετίζονται με το Google Analytics μπορούν να διαβαστούν ή να γραφτούν.';
		$default_txt['el']['banner_title'] = '';
		$default_txt['el']['accept'] = 'Αποδοχή';
		$default_txt['el']['refuse'] = 'Άρνηση';
		$default_txt['el']['customize'] = 'Προσαρμογή';
		$default_txt['el']['ga_4_version'] = 'Google Analytics έκδοση 4 (GA4)';
		$default_txt['el']['facebook_remarketing'] = 'Επαναληπτικό Μάρκετινγκ στο Facebook';
		$default_txt['el']['tiktok_pixel'] = 'TikTok Pixel';
		$default_txt['el']['in_addition_this_site_installs'] = 'Επιπλέον, αυτός ο ιστότοπος εγκαθιστά';
		$default_txt['el']['with_anonymous_data_transmission_via_proxy'] = 'με ανώνυμη μετάδοση δεδομένων μέσω διακομιστή μεσολάβησης.';
		$default_txt['el']['by_giving_your_consent_the_data_will_be_sent_anonymously'] = 'Δίνοντας τη συγκατάθεσή σας, τα δεδομένα θα αποστέλλονται ανώνυμα, προστατεύοντας έτσι το απόρρητό σας.';
		$default_txt['el']['lpd_compliance_text'] = 'Αυτός ο ιστότοπος συμμορφώνεται με τον νόμο περί προστασίας δεδομένων (LPD), τον ελβετικό ομοσπονδιακό νόμο της 25ης Σεπτεμβρίου 2020, και τον ΓΚΠΔ, κανονισμό ΕΕ 2016/679, σχετικά με την προστασία των προσωπικών δεδομένων καθώς και την ελεύθερη κυκλοφορία αυτών των δεδομένων.';
		$default_txt['el']['iab_bannertext_1'] = 'Εμείς και οι επιλεγμένοι διαφημιστικοί συνεργάτες μας μπορούμε να αποθηκεύσουμε και/ή να έχουμε πρόσβαση σε πληροφορίες στη συσκευή σας, όπως cookies, μοναδικά αναγνωριστικά και δεδομένα περιήγησης.';
		$default_txt['el']['iab_bannertext_2_a'] = 'Μπορείτε πάντα να επιλέγετε συγκεκριμένους σκοπούς σχετικά με το προφίλ αποκτώντας πρόσβαση στον';
		$default_txt['el']['iab_bannertext_2_link'] = 'πίνακα προτιμήσεων διαφημίσεων';
		$default_txt['el']['iab_bannertext_2_b'] = ', και μπορείτε πάντα να αποσύρετε τη συγκατάθεσή σας ανά πάσα στιγμή κάνοντας κλικ στο "Διαχείριση συναίνεσης" στο κάτω μέρος της σελίδας.';
		$default_txt['el']['iab_bannertext_3'] = 'Λίστα ορισμένων πιθανών διαφημιστικών αδειών';
		$default_txt['el']['iab_bannertext_4_a'] = 'Μπορείτε να συμβουλευτείτε: τη λίστα μας με τους';
		$default_txt['el']['iab_bannertext_4_b'] = 'διαφημιστικούς συνεργάτες';
		$default_txt['el']['iab_bannertext_5'] = 'την Πολιτική Cookies';
		$default_txt['el']['iab_bannertext_6'] = 'και την Πολιτική Απορρήτου';
		$default_txt['el']['vat_id'] = 'Α.Φ.Μ';
		$default_txt['el']['google_recaptcha_content_notification_a'] = 'Οι επιλογές των cookies σας μπορεί να μην επιτρέψουν την υποβολή της φόρμας. Μπορείτε να επανεξετάσετε τις επιλογές σας';
		$default_txt['el']['google_recaptcha_content_notification_b'] = 'κάνοντας κλικ εδώ';


		$final_txt = $default_txt;

		$fixed_translations = ( isset( $settings['fixed_translations_encoded'] ) && $settings['fixed_translations_encoded'] ) ? json_decode( $settings['fixed_translations_encoded'], true ) : array();

		if( !is_null( $fixed_translations ) && !empty( $fixed_translations ) )
		{
			foreach( $fixed_translations as $lang => $translations )
			{
				if( isset( $final_txt[$lang] ) && is_array( $translations ) )
				{
					foreach( $translations as $key => $value )
					{
						if( !empty( $value ) )
						{
							$final_txt[$lang][ $key ] = $value;
						}
					}
				}
			}
		}

		return $final_txt;
	}

	//f for getting current lang (4 char)
	public static function getCurrentLang4Char()
	{
		$the_options = self::get_settings();

		$currentAndSupportedLanguages = self::getCurrentAndSupportedLanguages();

		if( $currentAndSupportedLanguages['with_multilang'] )
		{
			//2 char version
			$current_language_2char = $currentAndSupportedLanguages['current_language'];

			//4 char version
			$current_lang = self::translate2charTo4CharLangCode( $current_language_2char );

			if( !$current_lang )
			{
				$current_lang = self::translate2charTo4CharLangCode( $currentAndSupportedLanguages['multilang_default_lang'] );
			}

			if( !$current_lang )
			{
				$current_lang = 'en_US';
			}
		}
		else
		{
			$current_lang = $the_options['default_locale'];
		}

		return $current_lang;
	}

	//f for translating 2 to 4 char lang code
	public static function translate2charTo4CharLangCode( $lang = 'en' )
	{
		foreach( MAP_SUPPORTED_LANGUAGES as $lang_key => $lang_value )
		{
			if( $lang_value['2char'] == $lang ) return $lang_key;
		}

		return null;;
	}

	//f for returning current and supported languages
	public static function getCurrentAndSupportedLanguages()
	{
		global $locale;
		global $sitepress;

		$the_settings = self::get_settings();

		$return_data = array(
			'with_multilang'				=>	false,
			'is_wpml_enabled'				=>	false,
			'is_polylang_enabled'			=>	false,
			'is_translatepress_enabled'		=> 	false,
			'is_weglot_enabled'				=>	false,
			'language_list_codes'			=>	null,
			'current_language'				=>	get_locale(),
			'multilang_default_lang' 		=> 	null,
			'wpml_language_list'			=>	null,
			'prevent_actions'				=> 	false,
			'supported_languages'			=>	array(),
		);

		//WPML
		if( function_exists( 'icl_object_id' ) && $sitepress )
		{
			$return_data['is_wpml_enabled'] = true;
			$return_data['with_multilang'] = true;

			$multilang_default_lang = $sitepress->get_default_language();
			$wpml_current_lang = ICL_LANGUAGE_CODE;

			//portuguese fix
			if( $wpml_current_lang == 'pt-pt' )
			{
				$wpml_current_lang = 'pt';
			}

			$language_list = icl_get_languages();
			$language_list_codes = array();

			foreach( $language_list as $k => $v )
			{
				$the_language_code = null;

				if( isset( $v['code'] ) )
				{
					$the_language_code = $v['code'];
				}
				elseif( isset( $v['language_code'] ) )
				{
					$the_language_code = $v['language_code'];
				}

				//portuguese fix
				if( $the_language_code == 'pt-pt' )
				{
					$the_language_code = 'pt';
				}

				$language_list_codes[] = $the_language_code;

			}

			$return_data['language_list_codes'] = $language_list_codes;
			$return_data['current_language'] = $wpml_current_lang;
			$return_data['multilang_default_lang'] = $multilang_default_lang;
			$return_data['wpml_language_list'] = $language_list;
		}

		//Polylang
		if( defined( 'POLYLANG_FILE' ) &&
			function_exists( 'pll_default_language' ) &&
			function_exists( 'pll_languages_list' ) )
		{
			$return_data['is_polylang_enabled'] = true;
			$return_data['with_multilang'] = true;

			$multilang_default_lang = pll_default_language();
			$language_list_codes = pll_languages_list();

			$return_data['language_list_codes'] = $language_list_codes;
			$return_data['current_language'] = pll_current_language();
			$return_data['multilang_default_lang'] = $multilang_default_lang;
		}

		//TranslatePress
		if( defined( 'TRP_PLUGIN_VERSION' ) )
		{
			$return_data['is_translatepress_enabled'] = true;
			$return_data['with_multilang'] = true;

			$trp_settings = get_option( 'trp_settings', array() );
			$multilang_default_lang = isset( $trp_settings['default-language']) ? $trp_settings['default-language'] : null;
			$current_language = function_exists( 'trp_get_current_language' ) ? trp_get_current_language() : get_locale();

			$language_list = isset( $trp_settings['translation-languages'] ) ? $trp_settings['translation-languages'] : array();
			$language_list_codes = array();

			foreach( $language_list as $k => $v )
			{
				$the_language_code = substr( $v, 0, 2 );
				$language_list_codes[] = $the_language_code;
			}

			$return_data['language_list_codes'] = $language_list_codes;
			$return_data['current_language'] = substr( $current_language, 0, 2 );
			$return_data['multilang_default_lang'] = substr( $multilang_default_lang, 0 , 2 );
		}

		//Weglot
		if( class_exists('Weglot\Parser\Parser') )
		{
			$return_data['is_weglot_enabled'] = true;
			$return_data['with_multilang'] = true;

			$weglot_options = weglot_get_options();

			$language_list_codes = array();

			$language_list_codes[] = $weglot_options['original_language'];

			foreach( $weglot_options['destination_language'] as $k => $v )
			{
				$the_language_code = $v['language_to'];
				$language_list_codes[] = $the_language_code;
			}

			$multilang_default_lang = $weglot_options['original_language'];
			$current_language = function_exists( 'weglot_get_current_language' ) ? weglot_get_current_language() : get_locale();

			$return_data['language_list_codes'] = $language_list_codes;
			$return_data['current_language'] = $current_language;
			$return_data['multilang_default_lang'] = $multilang_default_lang;
		}


		if( $return_data['with_multilang'] && !$return_data['multilang_default_lang'] )
		{
			$return_data['is_wpml_enabled'] = false;
			$return_data['is_polylang_enabled'] = false;
			$return_data['with_multilang'] = false;
			$return_data['prevent_actions'] = true;

			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) self::write_log( 'error on multilang' );
		}

		if( $return_data['with_multilang'] )
		{
			$website_l_allowed = $return_data['language_list_codes'];
		}
		else
		{
			$website_l_allowed = array( substr( $the_settings['default_locale'], 0, 2 ) );
		}

		$l_allowed = MyAgilePrivacy::get_option( MAP_PLUGIN_L_ALLOWED, array() );

		foreach( MAP_SUPPORTED_LANGUAGES as $lang_code => $lang_data )
		{
			$lang_code_2char = $lang_data['2char'];

			if( $return_data['with_multilang'] )
			{
				if( is_array( $website_l_allowed ) &&
					!in_array( $lang_code_2char, $website_l_allowed ) )
				{
					continue;
				}
			}

			if( is_array( $l_allowed ) &&
				!in_array( $lang_code_2char, $l_allowed ) )
			{
				continue;
			}

			$return_data['supported_languages'][ $lang_code ] = $lang_data;
		}

		return $return_data;
	}

	//clean up custom post type posts
	public static function dropCustomPostTypesPosts()
	{
		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) self::write_log( 'start dropCustomPostTypesPosts' );

		$post_type_array = array(
			MAP_POST_TYPE_COOKIES,
			MAP_POST_TYPE_POLICY
		);

		global $wpdb;

		foreach( $post_type_array as $to_clean_post_type )
		{
			// 1. Count the number of posts of the specified post type
			$post_count = $wpdb->get_var($wpdb->prepare("
				SELECT COUNT(*) FROM {$wpdb->posts}
				WHERE post_type = %s
			", $to_clean_post_type));

			// 2. If there are posts of this post type, proceed to delete them
			if( $post_count > 0 )
			{
				// Get all post IDs of the specified post type
				$post_ids = $wpdb->get_col($wpdb->prepare("
					SELECT ID FROM {$wpdb->posts}
					WHERE post_type = %s
				", $to_clean_post_type));

				// Delete post meta
				$wpdb->query("
					DELETE FROM {$wpdb->postmeta}
					WHERE post_id IN (" . implode(',', array_map('intval', $post_ids)) . ")
				");

				// Delete term relationships
				$wpdb->query("
					DELETE FROM {$wpdb->term_relationships}
					WHERE object_id IN (" . implode(',', array_map('intval', $post_ids)) . ")
				");

				// Delete posts
				$wpdb->query("
					DELETE FROM {$wpdb->posts}
					WHERE ID IN (" . implode(',', array_map('intval', $post_ids)) . ")
				");

				if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) self::write_log( "dropped $post_count post for post type $to_clean_post_type" );

				$post_count = $wpdb->get_var($wpdb->prepare("
					SELECT COUNT(*) FROM {$wpdb->posts}
					WHERE post_type = %s
				", $to_clean_post_type));

				if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) self::write_log( "there are $post_count post left for post type $to_clean_post_type" );
			}
			else
			{
				if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) self::write_log( "no post found to clean for post type $to_clean_post_type" );
			}
		}

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) self::write_log( 'end dropCustomPostTypesPosts' );
	}


	//clean up PolyLang translations
	public static function dropPolyLangTranslations()
	{
		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) self::write_log( 'start dropPolyLangTranslations' );

		$post_type_array = array(
			MAP_POST_TYPE_COOKIES,
			MAP_POST_TYPE_POLICY
		);

		global $wpdb;

		foreach( $post_type_array as $to_clean_post_type )
		{
			$post_type_escaped = esc_sql( $to_clean_post_type );
			$element_type = 'post_' . $post_type_escaped;

			// Delete translations from the Polylang translations table
			$wpdb->query($wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}icl_translations
				WHERE element_id IN (
					SELECT ID
					FROM {$wpdb->posts}
					WHERE post_type = %s
				)
				AND element_type = %s",
				$post_type_escaped,
				$element_type
			));

			// Delete translation posts
			$wpdb->query($wpdb->prepare(
				"DELETE FROM {$wpdb->posts}
				WHERE ID IN (
					SELECT translation_id
					FROM {$wpdb->prefix}icl_translations
					WHERE element_id IN (
						SELECT ID
						FROM {$wpdb->posts}
						WHERE post_type = %s
					)
				)",
				$post_type_escaped
			));
		}

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) self::write_log( 'end dropPolyLangTranslations' );
	}


	//clean up WPML translations
	public static function dropWPMLTranslations()
	{
		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) self::write_log( 'start dropWPMLTranslations' );

		$post_type_array = array(
			MAP_POST_TYPE_COOKIES,
			MAP_POST_TYPE_POLICY
		);

		global $wpdb;

		foreach( $post_type_array as $to_clean_post_type )
		{
			// 1. Create a temporary table to store the IDs of the translations
			$wpdb->query(
				$wpdb->prepare(
					"CREATE TEMPORARY TABLE temp_translation_ids
					 SELECT p.ID
					 FROM {$wpdb->posts} p
					 JOIN {$wpdb->prefix}icl_translations t ON p.ID = t.element_id
					 WHERE p.post_type = %s
					 AND t.source_language_code IS NOT NULL",
					$to_clean_post_type
				)
			);

			// 2. Delete the translated posts and their meta-data
			$wpdb->query(
				"DELETE p, pm
				 FROM {$wpdb->posts} p
				 LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				 WHERE p.ID IN (SELECT ID FROM temp_translation_ids)"
			);


			// 3. Delete the translation relationships in the wp_icl_translations table
			$wpdb->query(
				"DELETE FROM {$wpdb->prefix}icl_translations
				 WHERE element_id IN (SELECT ID FROM temp_translation_ids)"
			);

			// 4. Drop the temporary table
			$wpdb->query("DROP TEMPORARY TABLE temp_translation_ids");


			// final cleanup
			$wpdb->query(
				$wpdb->prepare("
					DELETE FROM {$wpdb->prefix}icl_translations
					WHERE element_id NOT IN (
						SELECT ID FROM {$wpdb->posts}
						WHERE post_type = %s
					)
					AND element_type LIKE CONCAT('post_', %s);
				", $to_clean_post_type, $to_clean_post_type)
			);
		}

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) self::write_log( 'stop dropWPMLTranslations' );
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