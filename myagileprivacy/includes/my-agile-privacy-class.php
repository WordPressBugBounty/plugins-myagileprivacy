<?php

if( !defined( 'MAP_PLUGIN_NAME' ) )
{
	exit('Not allowed.');
}


/**
 * Core plugin class.
 *
 *
 * @package    MyAgilePrivacy
 * @subpackage MyAgilePrivacy/includes
 * @author     https://www.myagileprivacy.com/
 */

final class MyAgilePrivacy {

	/**
	 * Unique identifier of this plugin.
	 *
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * Current version of the plugin.
	 *
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	//stored user options
	private static $stored_options = array();

	// guard flag used during programmatic post updates
	public static $doing_internal_write = false;

	// set when the CDN did not answer during this request
	private static $cdn_unreachable = false;

	// per request banner configuration state; null means not resolved yet
	private static $banner_overrides    = null;
	private static $banner_variant_id   = 'default';
	private static $banner_variant_slot = 0;
	private static $banner_public       = array( 'active' => false, 'ids' => array(), 'cookie_days' => 30, 'preview' => false, 'variant_looks' => array() );

	/**
	 * Core functionality of the plugin.
	 *
	 * It sets plugin name, plugin version.
	 * It loads dependencies, set the locale, and hoocks for admin and frontend area
	 *
	 */
	public function __construct()
	{
		$this->version = MAP_PLUGIN_VERSION;
		$this->plugin_name = MAP_PLUGIN_NAME;

		register_activation_hook( MAP_PLUGIN_FILENAME, array( self::class, 'map_plugin_activate' ) );
		register_deactivation_hook( MAP_PLUGIN_FILENAME, array( self::class, 'map_plugin_deactivate' ) );

		$this->load_classes_and_dependencies();
		$this->admin_hooks();
		$this->frontend_hooks();
		$this->boot_advanced();
	}

	/**
	 * Boots the optional integration layer, gating each concern to the correct request context. No-op when the integration constant is absent or false.
	 */
	private function boot_advanced()
	{
		if ( !defined( 'MAP_ENABLE_ADVANCED_INTEGRATION' ) || ! MAP_ENABLE_ADVANCED_INTEGRATION ) { return; }
		if ( is_admin() || ( defined( 'DOING_CRON' ) && DOING_CRON ) )
		{
			new MAP_Update_Checker();
		}

		if ( is_admin() )
		{
			MAP_Admin_Notice_Manager::instance();
		}

		do_action( 'map_base_loaded' );
	}

	/**
	 * f for plugin activation
	 */
	public static function map_plugin_activate()
	{
		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'calling map_plugin_activate' );

		if( !self::get_option( MAP_PLUGIN_ACTIVATION_DATE, null ) )
		{
			self::update_option( MAP_PLUGIN_ACTIVATION_DATE, time() );
		}

		if( false === self::get_option( MAP_PLUGIN_SETTINGS_FIELD, false ) )
		{
			add_option( MAP_PLUGIN_SETTINGS_FIELD, array( 'video_privacy_mode' => 'block' ) );
			wp_cache_delete( MAP_PLUGIN_SETTINGS_FIELD, 'options' );

			// a new installation, not an update
			self::update_option( MAP_PLUGIN_INSTALLED_VERSION, self::get_effective_installed_version() );
		}

		self::map_maybe_register_capabilities();
	}

	/**
	 * f for plugin deactivation
	 */
	public static function map_plugin_deactivate()
	{
		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'calling map_plugin_deactivate' );

		if( defined( 'MAP_PLUGIN_ACTIVATION_DATE' ) && self::get_option( MAP_PLUGIN_ACTIVATION_DATE, null ) ) delete_option( MAP_PLUGIN_ACTIVATION_DATE );
		if( defined( 'MAP_REVIEW_STATUS' ) && self::get_option( MAP_REVIEW_STATUS, null ) ) delete_option( MAP_REVIEW_STATUS );
		if( defined( 'MAP_NOTICE_LAST_SHOW_TIME' ) && self::get_option( MAP_NOTICE_LAST_SHOW_TIME, null ) ) delete_option( MAP_NOTICE_LAST_SHOW_TIME );
	}

	/**
	 * Capability registry for plugin areas.
	 */
	public static function map_capability_registry()
	{
		return array(
			'options'	=>	'myagileprivacy_edit_options',
		);
	}

	/**
	 * Resolves the capability for a given plugin area.
	 */
	public static function get_capability( $area )
	{
		static $cache = array();
		static $resolving = array();

		if( isset( $cache[ $area ] ) )
		{
			return $cache[ $area ];
		}

		$map = self::map_capability_registry();

		$default = isset( $map[ $area ] ) ? $map[ $area ] : 'manage_options';

		if( !empty( $resolving[ $area ] ) )
		{
			return $default;
		}

		$resolving[ $area ] = true;

		$cap = apply_filters( 'map_' . $area . '_capability', $default, $area );

		if( !is_string( $cap ) || !preg_match( '/^[A-Za-z0-9_]+$/', $cap ) )
		{
			$cap = $default;
		}

		$resolving[ $area ] = false;

		if( did_action( 'init' ) )
		{
			$cache[ $area ] = $cap;
		}

		return $cap;
	}

	/**
	 * Whether the current user can manage the plugin areas.
	 */
	public static function map_can_edit()
	{
		return current_user_can( self::get_capability( 'options' ) );
	}

	/**
	 * Grants area capabilities to users holding manage_options.
	 */
	public static function map_bridge_admin_capability( $allcaps, $caps, $args )
	{
		if( empty( $allcaps['manage_options'] ) )
		{
			return $allcaps;
		}

		foreach( array_keys( self::map_capability_registry() ) as $area )
		{
			$cap = self::get_capability( $area );

			if( in_array( $cap, $caps, true ) )
			{
				$allcaps[ $cap ] = true;
			}
		}

		return $allcaps;
	}

	/**
	 * Adds area capabilities to the administrator role.
	 */
	public static function map_register_capabilities()
	{
		$role = get_role( 'administrator' );

		if( !$role )
		{
			return false;
		}

		foreach( array_keys( self::map_capability_registry() ) as $area )
		{
			$cap = self::get_capability( $area );

			if( !$role->has_cap( $cap ) )
			{
				$role->add_cap( $cap );
			}
		}

		return true;
	}

	/**
	 * Registers area capabilities when the resolved set changes.
	 */
	public static function map_maybe_register_capabilities()
	{
		$caps = array();

		foreach( array_keys( self::map_capability_registry() ) as $area )
		{
			$caps[] = self::get_capability( $area );
		}

		sort( $caps );

		$signature = implode( ',', $caps );

		if( self::get_option( MAP_PLUGIN_CAPS_REGISTERED, '' ) === $signature )
		{
			return;
		}

		if( self::map_register_capabilities() )
		{
			self::update_option( MAP_PLUGIN_CAPS_REGISTERED, $signature );
		}
	}

	// resets the flag once its timeout has elapsed
	public static function checkDoNotAskLicenseCode()
	{
		$the_settings = MyAgilePrivacy::get_settings();

		$now = time();

		if(
			isset( $the_settings['dont_ask_license_code'] ) &&
			$the_settings['dont_ask_license_code'] &&
			isset( $the_settings['dont_ask_license_code_timestamp'] ) &&
			(int) $the_settings['dont_ask_license_code_timestamp'] > 0 &&
			(int) $the_settings['dont_ask_license_code_timestamp'] <= $now - MAP_BYPASS_LICENSE_TRESHOLD
		)
		{
			wp_cache_delete( MAP_PLUGIN_SETTINGS_FIELD, 'options' );
			wp_cache_delete( 'alloptions', 'options' );

			$the_settings = MyAgilePrivacy::get_settings();

			// the flag may have been set again in the meantime
			if(
				!empty( $the_settings['dont_ask_license_code'] ) &&
				isset( $the_settings['dont_ask_license_code_timestamp'] ) &&
				(int) $the_settings['dont_ask_license_code_timestamp'] > 0 &&
				(int) $the_settings['dont_ask_license_code_timestamp'] <= $now - MAP_BYPASS_LICENSE_TRESHOLD
			)
			{
				$the_settings['dont_ask_license_code'] = false;
				$the_settings['dont_ask_license_code_timestamp'] = 0;

				MyAgilePrivacy::update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_settings );
			}
		}

		return true;
	}

	/**
	 * Determine if expired notice should be shown
	 */
	public static function should_show_expired_notice()
	{
		$rconfig = MyAgilePrivacy::get_rconfig();

		if( isset( $rconfig ) &&
			isset( $rconfig['expired_30_days'] ) &&
			$rconfig['expired_30_days'] == 1
		)
		{
			return true;
		}

		if( isset( $rconfig ) &&
			isset( $rconfig['expired_60_days'] ) &&
			$rconfig['expired_60_days'] == 1
		)
		{
			return true;
		}

		return false;
	}

	/**
	 * Determine if feedback notice should be shown
	 */
	public static function should_show_feedback_notice()
	{
		$rconfig = MyAgilePrivacy::get_rconfig();

		if( isset( $rconfig ) &&
			isset( $rconfig['expired_30_days'] ) &&
			$rconfig['expired_30_days'] == 1
		)
		{
			return false;
		}

		if( isset( $rconfig ) &&
			isset( $rconfig['expired_60_days'] ) &&
			$rconfig['expired_60_days'] == 1
		)
		{
			return false;
		}

		if( isset( $rconfig ) &&
			isset( $rconfig['block_review_message'] ) &&
			$rconfig['block_review_message'] )
		{
			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'should_show_feedback_notice blocked via block_review_message' );
			return false;
		}

		if( !current_user_can( 'manage_options' ) )
		{
			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( ' should_show_feedback_notice -> missing user permission' );
			return false;
		}

		if( !defined( 'MAP_REVIEW_STATUS') )
		{
			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'should_show_feedback_notice -> missing review_status' );
			return false;
		}

		$review_status = self::get_option( MAP_REVIEW_STATUS, null );
		$last_show_time = self::get_option( MAP_NOTICE_LAST_SHOW_TIME, null );
		$activation_date = self::get_option( MAP_PLUGIN_ACTIVATION_DATE, null );

		if( !$activation_date )
		{
			self::map_plugin_activate();

			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'missing should_show_notice activation_date' );

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
		if( $current_time - (int) $activation_date < MAP_NOTICE_FIRST_TRESHOLD )
		{
			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'should_show_feedback_notice --> false (check A)' );

			return false;
		}

		// if feedback marked as later, show again after first treshold
		if( $review_status === 'later' && ( $current_time - (int) $last_show_time ) < MAP_NOTICE_FIRST_TRESHOLD )
		{
			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'should_show_feedback_notice --> false (check B)' );

			return false;
		}

		// if feedback marked as done, show again after second treshold
		if( $review_status === 'done' && ( $current_time - (int) $last_show_time ) < MAP_NOTICE_SECOND_TRESHOLD )
		{
			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'should_show_feedback_notice --> false (check C)' );

			return false;
		}

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'should_show_feedback_notice --> true' );

		return true;
	}


	/**
	 * Load the required dependencies.
	 *
	 * @access   private
	 */
	private function load_classes_and_dependencies()
	{
		/**
		 * The class for handling localization
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/my-agile-privacy-lang-helper.php';

		/**
		 * The class for handling regulation
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/my-agile-privacy-regulation-helper.php';

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
	 * @access   private
	 */
	private function frontend_hooks()
	{
		$plugin_frontend = new MyAgilePrivacyFrontend( $this->plugin_name, $this->version, $this );

		$map_first_priority = defined( 'PHP_INT_MIN' ) ? PHP_INT_MIN : ~PHP_INT_MAX;

		/* Frontend styles*/
		add_action( 'wp_enqueue_scripts', array( $plugin_frontend, 'enqueue_styles' ), $map_first_priority );

		/* Frontend scripts*/
		add_action( 'wp_enqueue_scripts', array( $plugin_frontend, 'enqueue_scripts' ), $map_first_priority );

		/* stylesheet loading compatibility */
		$rconfig = self::get_rconfig();

		if( is_array( $rconfig ) &&
			isset( $rconfig['enable_plugins_loaded_filter'] ) &&
			$rconfig['enable_plugins_loaded_filter'] )
		{
			add_filter( 'jetpack_boost_async_style', array( $plugin_frontend, 'map_jetpack_boost_async_style' ), 10, 2 );
		}

		/* inline script attribute rewrite */
		add_filter( 'wp_inline_script_attributes', array( $plugin_frontend, 'add_attrs_to_inline_script' ), 10, 2 );

		/* set locale, register custom post type */
		add_action( 'init', array( $plugin_frontend, 'plugin_init' ) );

		/* resolves the banner configuration before any producer runs */
		add_action( 'init', array( 'MyAgilePrivacy', 'arm_banner_variant' ), $map_first_priority );

		/* wp_footer hook*/
		add_action( 'wp_footer', array( $plugin_frontend, 'inject_html_code' ) );
		add_action( 'send_headers', array( 'MyAgilePrivacy', 'map_send_nocache_signal' ) );

		$the_settings = self::get_settings();

		/* admin callback actions */
		add_action( 'wp_ajax_nopriv_map_save_detected_keys', array( $plugin_frontend, 'map_save_detected_keys_callback' ) );
		add_action( 'wp_ajax_map_save_detected_keys', array( $plugin_frontend, 'map_save_detected_keys_callback' ) );
		add_action( 'wp_ajax_nopriv_map_missing_cookie_shield', array( $plugin_frontend, 'map_missing_cookie_shield_callback' ) );
		add_action( 'wp_ajax_map_missing_cookie_shield', array( $plugin_frontend, 'map_missing_cookie_shield_callback' ) );
		add_action( 'wp_ajax_nopriv_map_check_consent_mode_status', array( $plugin_frontend, 'map_check_consent_mode_status_callback' ) );
		add_action( 'wp_ajax_map_check_consent_mode_status', array( $plugin_frontend, 'map_check_consent_mode_status_callback' ) );
		add_action( 'wp_ajax_nopriv_map_report_gtm_gateway', array( $plugin_frontend, 'map_report_gtm_gateway_callback' ) );
		add_action( 'wp_ajax_map_report_gtm_gateway', array( $plugin_frontend, 'map_report_gtm_gateway_callback' ) );

		if( isset( $the_settings['pa'] ) &&
			$the_settings['pa'] == 1 )
		{
			$skip = $this::check_buffer_skip_conditions( false );

			if( $skip == 'false' )
			{
				$rconfig = self::get_rconfig();

				$logic_legacy_mode = false;

				if(
					self::is_legacy_mode_configured( $the_settings, $rconfig ) ||
					self::get_scan_runtime()['missing_cookie_shield']
				)
				{
					$logic_legacy_mode = true;
				}
				// the head is printed here when the page filter does not run
				if( $logic_legacy_mode || ( $the_settings['scan_mode'] == 'turned_off' && !empty( $the_settings['is_on'] ) ) )
				{
					add_action( 'wp_head', array( $plugin_frontend, 'wp_head_inject' ), isset( $rconfig['js_legacy_mode_head_prio'] ) ? self::intvalPhp8( $rconfig['js_legacy_mode_head_prio'] ) : $map_first_priority );
				}

				/**
				 * The class for html parsing
				 */

				if( !class_exists( 'MAP_Simple_Html_Dom_Node', false ) )
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

						global $wp_version;
						if( version_compare( $wp_version, '6.9', '>=' ) )
						{
							add_action( 'shutdown', array( $plugin_frontend, 'map_buffer_end' ), 0 );
						}
						else
						{
							add_action( 'shutdown', array( $plugin_frontend, 'map_buffer_end' ), $the_settings['scanner_end_hook_prio'] );
						}
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


		}

		/* optimizer exclusion lists */
		$plugin_frontend->register_optimizer_exclusions();

		/*auto update*/
		add_filter( 'auto_update_plugin', array( $plugin_frontend, 'auto_update_plugins' ), 10, 2 );
	}

	/**
	 * Register all of the hooks related to the backend area
	 *
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

		//plugins_loaded: post-update check (unconditional - fires on any request type)
		add_action( 'plugins_loaded', array( $plugin_admin, 'map_check_post_update_transient' ) );

		//wp_footer hook
		add_action( 'wp_footer', array( $plugin_admin, 'triggered_do_cron_sync' ) );

		// scheduled event: is_admin() is false in cron
		add_action( 'map_cookie_domain_cache_flush', array( 'MyAgilePrivacy', 'run_cookie_domain_cache_flush' ) );

		/* admin callback actions */
		add_action( 'wp_ajax_check_license_status', array( $plugin_admin, 'check_license_status' ) );
		add_action( 'wp_ajax_map_request_trial', array( $plugin_admin, 'map_request_trial' ) );
		add_action( 'wp_ajax_map_request_trial_b', array( $plugin_admin, 'map_request_trial_b' ) );

		//capability mapping
		add_filter( 'user_has_cap', array( 'MyAgilePrivacy', 'map_bridge_admin_capability' ), 10, 3 );
		add_filter( 'wp_insert_post_data', array( $plugin_admin, 'map_lock_policy_status' ), 10, 2 );
		add_action( 'plugins_loaded', array( $plugin_admin, 'map_watch_pixel_state' ) );

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
		add_action( 'admin_init', array( 'MyAgilePrivacy', 'map_maybe_register_capabilities' ) );
		add_action( 'admin_init', array( 'MyAgilePrivacy', 'map_protect_log_dir' ) );
		add_action( 'admin_init', array( 'MyAgilePrivacy', 'seed_scan_runtime' ) );
		add_action( 'admin_init', array( 'MyAgilePrivacy', 'seed_rconfig' ) );
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
		add_action( 'wp_ajax_update_admin_settings_form', array( $plugin_admin, 'update_admin_settings_form_callback' ) );

		add_action( 'wp_ajax_update_translations_form', array( $plugin_admin, 'update_translations_form_callback' ) );

		//admin post actions
		add_action( 'admin_post_backup_admin_settings_form', array( $plugin_admin, 'backup_admin_settings_form_callback' ) );

		add_action( 'admin_post_import_admin_settings_form', array( $plugin_admin, 'import_admin_settings_form_callback' ) );

		//generic admin styles
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_styles' ) );

		//generic admin scripts
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts' ) );

		//keeps unrelated notices out of the plugin screens
		add_action( 'in_admin_header', array( $plugin_admin, 'hide_foreign_notices' ) );

		//add settings links for the menu
		add_filter( 'plugin_action_links_'.plugin_basename( MAP_PLUGIN_FILENAME ), array( $plugin_admin, 'plugin_action_links' ) );

		//hooks for review notice
		add_action( 'admin_notices', array( $plugin_admin, 'show_review_notice' ) );
		add_action( 'admin_notices', array( $plugin_admin, 'show_expired_notice' ) );
		add_action( 'wp_ajax_map_review_later', array( $plugin_admin, 'review_later' ) );
		add_action( 'wp_ajax_map_review_done', array( $plugin_admin, 'review_done' ) );

		//add cron scheduled functions
		if( !( isset( $rconfig ) &&
				isset( $rconfig['disable_cronjob'] ) &&
				$rconfig['disable_cronjob'] == 1 ) )
		{
			//clean old daily event schedule if exists
			if( wp_next_scheduled( 'my_agile_privacy_do_cron_sync_hook' ) )
			{
				wp_clear_scheduled_hook( 'my_agile_privacy_do_cron_sync_hook' );
			}

			//clean old daily event schedule if exists
			if( wp_next_scheduled( 'my_agile_privacy_do_cron_sync_once_day_hook' ) )
			{
				wp_clear_scheduled_hook( 'my_agile_privacy_do_cron_sync_once_day_hook' );
			}

			//schedule an action if it's not already scheduled
			if( ! wp_next_scheduled( 'my_agile_privacy_do_cron_sync_twice_day_hook' ) )
			{
				wp_schedule_event( time(), 'twicedaily', 'my_agile_privacy_do_cron_sync_twice_day_hook' );
			}
		}
		else
		{
			//clean old daily event schedule if exists
			if( wp_next_scheduled( 'my_agile_privacy_do_cron_sync_hook' ) )
			{
				wp_clear_scheduled_hook( 'my_agile_privacy_do_cron_sync_hook' );
			}

			//clean twice a day event schedule if exists
			if( wp_next_scheduled( 'my_agile_privacy_do_cron_sync_twice_day_hook' ) )
			{
				wp_clear_scheduled_hook( 'my_agile_privacy_do_cron_sync_twice_day_hook' );
			}

			//clean once a day event schedule if exists
			if( wp_next_scheduled( 'my_agile_privacy_do_cron_sync_once_day_hook' ) )
			{
				wp_clear_scheduled_hook( 'my_agile_privacy_do_cron_sync_once_day_hook' );
			}
		}

		if( !( isset( $rconfig ) &&
				isset( $rconfig['disable_install_counter'] ) &&
				$rconfig['disable_install_counter'] == 1 ) )
		{
			//schedule an action if it's not already scheduled
			if( !wp_next_scheduled( 'my_agile_privacy_do_cron_sync_install_counter' ) )
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
		add_action( 'admin_footer', array( $plugin_admin, 'map_reverse_pixel_cookies_footer' ) );
		add_action( 'deactivated_plugin', array( $plugin_admin, 'map_on_pixel_deactivated' ), 10, 1 );

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
	 * Returns the stored configuration array.
	 */
	public static function get_rconfig()
	{
		return self::get_option( MAP_PLUGIN_RCONFIG, array() );
	}

	/**
	 * Get current settings.
	 * @access   public
	 */
	public static function get_settings()
	{
		$settings = self::get_default_settings();

		self::$stored_options = self::get_option( MAP_PLUGIN_SETTINGS_FIELD, array() );

		if( !empty( self::$stored_options ) && is_array( self::$stored_options ) )
		{
			//use default setting in case of missing site_and_policy_settings array
			if(
				isset( self::$stored_options['site_and_policy_settings'] ) &&
				!is_array( self::$stored_options['site_and_policy_settings'] )
			)
			{
				self::$stored_options['site_and_policy_settings'] = $settings['site_and_policy_settings'];
			}

			foreach( self::$stored_options as $key => $option )
			{
				if( is_array( $option ) )
				{
					$settings[ $key ] = self::sanitise_array_settings( $key, $option );
				}
				else
				{
					$settings[ $key ] = self::sanitise_settings( $key, $option );
				}
			}
		}

		if( !empty( self::$banner_overrides ) )
		{
			foreach( self::$banner_overrides as $override_key => $override_value )
			{
				$settings[ $override_key ] = $override_value;
			}
		}

		return $settings;
	}

	//recursive array validation
	public static function sanitise_array_settings( $key, $values = array() )
	{
		if( isset( $key ) &&
			$key &&
			isset( self::$stored_options ) &&
			isset( self::$stored_options[ $key ] ) )
		{
	    	$sanitised = self::$stored_options[ $key ];
		}
		else
		{
	    	$sanitised = array();
		}

	    foreach( $values as $k => $v )
	    {
	        if( is_array( $v ) )
	        {
	            $sanitised[ $k ] = self::sanitise_array_settings( null, $v );
	        }
	        else
	        {
	            $sanitised[ $k ] = self::sanitise_settings( $k, $v );
	        }
	    }

	    return $sanitised;
	}


	/**
	* Returns sanitized content
	 * @access   public
	*/
	public static function sanitise_settings( $key, $value )
	{
	    $ret   = null;
	    $logic = null;

	    if( !is_string( $key ) )
	    {
	        $key = (string) $key;
	    }

	    // get logic validation criteria
	    if(
	    	strpos( $key, 'customer_area_' ) === 0  ||
	    	strpos( $key, 'regulation_' ) === 0  ||
	    	strpos( $key, 'site_features_' ) === 0 ||
	    	strpos( $key, 'outside_country_' ) === 0 ||
	    	strpos( $key, 'protection_system_' ) === 0
		)
	    {
	        $logic = 'bool';
	    }
	    else
	    {
	        switch ( $key )
	        {
	            // boolean
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
	            case 'show_ntf_bar_on_not_yet_consent_choice':
	            case 'with_css_effects':
	            case 'show_buttons_icons':
	            case 'title_is_on':
	            case 'forced_legacy_mode':
	            case 'dev_mode':
	            case 'missing_cookie_shield':
	            case 'forced_auto_update':
	            case 'enable_iab_tcf':
	            case 'enable_metadata_sync':
	            case 'enable_microsoft_cmode':
	            case 'enable_clarity_cmode':
	            case 'enable_cmode_v2':
	            case 'enable_cmode_url_passthrough':
	            case 'iab_enable_advertiser_consent_mode':
	            case 'disable_gtm_gateway_detection':
	            case 'cmode_v2_forced_off_ga4_advanced':
	            case 'bypass_cmode_enable':
	            case 'cmode_v2_js_on_error':
	            case 'enable_language_fallback':
	            case 'outside_adequate_suppliers':
	            case 'add_cookie_policy_to_first_layer':
	            case 'add_personal_policy_to_first_layer':
	            case 'dont_ask_license_code':
	            case 'send_ga4_event_on_consent_change':
	            case 'stats_enabled':
	            case 'missing_api_support':
	                $logic = 'bool';
	                break;

	            // integer
	            case 'scanner_start_hook_prio':
	            case 'scanner_end_hook_prio':
	            case 'blocked_content_notify_auto_shutdown_time':
	            case 'floating_banner':
	            case 'cmode_v2_js_error_code':
	            case 'cmode_v2_js_on_error_first_relevation':
	            case 'completion_percentage':
	            case 'last_update_timestamp':
	            case 'dont_ask_license_code_timestamp':
	            case 'missing_api_support_failures_count':
	            case 'missing_api_support_failures_window_start':
	                $logic = 'int';
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
	                $logic = 'hexcolor';
	                break;

	            // html (no js code)
	            case 'bar_heading_text':
	            case 'website_name':
	                $logic = 'html';
	                break;

	            // identity and DPO: emphasis only
	            case 'identity_name':
	            case 'identity_address':
	            case 'identity_vat_id':
	                $logic = 'identity_html';
	                break;

	            // identity and DPO + trim
	            case 'identity_email':
	            case 'dpo_email':
	            case 'dpo_name':
	            case 'dpo_address':
	                $logic = 'identity_html_trim';
	                break;

	            // html + trim
	            case 'cookie_policy_url':
	            case 'personal_data_policy_url':
	            case 'license_code':
	                $logic = 'html_trim';
	                break;

	            // custom css
	            case 'custom_css':
	                $logic = 'esc_html';
	                break;

	            // attributes
	            case 'base_location':
	                $logic = 'esc_attr';
	                break;

	            // no processing
	            case 'last_scan_date_internal':
	            case 'cmode_v2_js_error_motivation':
	            case 'cookie_banner_vertical_position':
	            case 'cookie_banner_horizontal_position':
	            case 'cookie_banner_size':
	            case 'consent_widget_style':
	            case 'customer_email':
	            case 'summary_text':
	            case 'last_sync':
	            case 'last_legit_sync':
	            case 'parse_config':
	            case 'parse_config_version_number':
	            case 'alt_accepted_all_cookie_name':
	            case 'alt_accepted_something_cookie_name':
	            case 'learning_mode_last_active_timestamp':
	            case 'missing_cookie_shield_timestamp':
	            case 'cookie_shield_running_timestamp':
	            case 'fixed_translations_encoded':
	            case 'layer_1_button_order':
	            case 'site_and_policy_settings':
	            case 'missing_api_support_timestamp':
	                $logic = 'raw';
	                break;

	            case 'video_privacy_mode':
	                $logic = 'video_privacy_mode';
	                break;

	            // default
	            default:
	                $logic = 'text';
	                break;
	        }
	    }

	    // apply validation logic
	    switch ( $logic )
	    {
	        case 'bool':
	            if( $value === 'true' || $value === true )
	            {
	                $ret = true;
	            }
	            elseif( $value === 'false' || $value === false )
	            {
	                $ret = false;
	            }
	            else
	            {
	                $ret = false;
	            }
	            break;

	        case 'int':
	            $ret = self::intvalPhp8( $value );
	            break;

	        case 'hexcolor':
				if( is_string( $value ) && preg_match( '/^(#[a-f0-9]{6}|#[a-f0-9]{3})$/i', $value ) )
	            {
	                $ret = $value;
	            }
	            else
	            {
	                // Failover = assign '#000' (black)
	                $ret = '#000';
	            }
	            break;

	        case 'html':
	            $ret = is_scalar( $value ) ? wp_kses( (string) $value, self::allowed_html_tags(), self::allowed_protocols() ) : '';
	            break;

	        case 'html_trim':
	            $ret = is_scalar( $value ) ? wp_kses( (string) $value, self::allowed_html_tags(), self::allowed_protocols() ) : '';
	            if( ! is_null( $ret ) ) {
	                $ret = trim( $ret );
	            }
	            break;

	        case 'identity_html':
	            $ret = is_scalar( $value ) ? wp_kses( (string) $value, self::allowed_identity_tags(), self::allowed_protocols() ) : '';
	            break;

	        case 'identity_html_trim':
	            $ret = is_scalar( $value ) ? wp_kses( (string) $value, self::allowed_identity_tags(), self::allowed_protocols() ) : '';
	            if( ! is_null( $ret ) ) {
	                $ret = trim( $ret );
	            }
	            break;

	        case 'esc_html':
	            $ret = esc_html( $value );
	            break;

	        case 'esc_attr':
	            $ret = esc_attr( $value );
	            break;

	        case 'raw':
	            $ret = $value;
	            break;

	        case 'video_privacy_mode':
	            $ret = ( $value === 'block' ) ? 'block' : 'anonymous';
	            break;

	        case 'text':
	        default:
	            $ret = sanitize_text_field( $value );
	            break;
	    }

	    return $ret;
	}

	/**
	 * check for wp login page
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

		$ABSPATH_MY = str_replace( array( '\\','/' ), DIRECTORY_SEPARATOR, ABSPATH );
		return (( in_array($ABSPATH_MY.'wp-login.php', get_included_files()) || in_array( $ABSPATH_MY.'wp-register.php', get_included_files() ) ) || ( isset( $GLOBALS['pagenow'] ) && $GLOBALS['pagenow'] === 'wp-login.php' ) || ( isset( $_SERVER['PHP_SELF'] ) && $_SERVER['PHP_SELF'] == '/wp-login.php' ) );
	}


	/**
	 * check for buffer / script inclusion skip
	 * @access   public
	*/
	public static function check_buffer_skip_conditions( $added_regexp_limited_check = false )
	{
	    global $wp, $pagenow, $wp_query, $wp_rewrite;

	    // Early returns for faster and more common conditions
	    if( !$added_regexp_limited_check )
	    {
	        // Check admin first (very common)
	        if( is_admin() )
	        {
	            return 'true';
	        }

	        // Check AJAX and XMLRPC (common)
	        if( ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) ||
	            ( defined('DOING_AJAX') && DOING_AJAX) ||
	            ( function_exists('wp_doing_ajax') && wp_doing_ajax() ) ||
	            self::is_non_page_request() )
	        {
	            return 'true';
	        }

	        // Check REST request
	        if( defined( 'REST_REQUEST' ) && REST_REQUEST )
	        {
	            return 'true';
	        }

	        if( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ||
	            ( isset($_GET['rest_route'] ) && is_string( $_GET['rest_route'] ) && strpos( $_GET['rest_route'], '/', 0 ) === 0) )
	        {
	            return 'true';
	        }

	        // Check JSON request
	        if( ( function_exists('wp_is_json_request') && wp_is_json_request()) ||
	            ( isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], '/wp-json/' ) !== false ) )
	        {
	            return 'true';
	        }

	        // Check wp-login
	        if( MyAgilePrivacy::is_wplogin() )
	        {
	            return 'true';
	        }

	        // Check pagenow
	        if( $pagenow === 'widgets.php' )
	        {
	            return 'true';
	        }

	        // Check customize preview
	        if( is_customize_preview() )
	        {
	            return 'true';
	        }

	        // Check feed
	        if( isset($wp_query) && is_feed() )
	        {
	            return 'true';
	        }

	        // Divi (first check)
	        if( function_exists('et_fb_is_enabled') && et_fb_is_enabled() )
	        {
	            return 'true';
	        }
	    }

	    // URL checks (more expensive)
	    if( !$added_regexp_limited_check && isset( $_SERVER['REQUEST_URI'] ) )
	    {
	        $request_uri = $_SERVER['REQUEST_URI'];

	        // Use strpos instead of multiple checks
	        $uri_checks = array(
	            'commercekit_ajax_search',
	            'plugins/matomo/app'
	        );

	        foreach( $uri_checks as $check )
	        {
	            if( strpos($request_uri, $check) !== false )
	            {
	                return 'true';
	            }
	        }

	        if( self::is_amp_plugin_active() )
	        {
	            $request_path = parse_url( $request_uri, PHP_URL_PATH );

	            if( !is_string( $request_path ) )
	            {
	                $request_path = strtok( $request_uri, '?#' );
	            }

	            $home_path = parse_url( home_url( '/' ), PHP_URL_PATH );

	            if( is_string( $request_path ) && is_string( $home_path ) && $home_path !== '/' &&
	                0 === strpos( $request_path, rtrim( $home_path, '/' ) ) )
	            {
	                $request_path = substr( $request_path, strlen( rtrim( $home_path, '/' ) ) );
	            }

	            if( is_string( $request_path ) && preg_match( '#/amp(/|$)#', $request_path ) )
	            {
	                return 'true';
	            }
	        }
	    }

	    if( !$added_regexp_limited_check )
	    {
	        // AMP check
	        if( ( function_exists('amp_is_request') && amp_is_request()) || ( isset($_GET['amp']) && self::is_amp_plugin_active() ) )
	        {
	            return 'true';
	        }

	        //bricks
	        if( isset( $_GET['bricks'] ) && $_GET['bricks'] == 'run' )
	        {
	            return 'true';
	        }

	        // Page builders breakdance
	        if( isset( $_GET['ct_builder'] ) )
	        {
	            return 'true';
	        }

	        // Page builders breakdance
	        if( isset( $_GET['ct_inner'] ) )
	        {
	            return 'true';
	        }

	        // Page builders (elementor, divi, thrive, avada)
	        if( isset( $_GET['elementor-preview'] ) )
	        {
	            return 'true';
	        }

	        if( isset( $_GET['et_fb'] ) && self::looseEqualsPhp8( $_GET['et_fb'], 1 ) )
	        {
	            return 'true';
	        }

	        if( isset( $_GET['action'] ) && $_GET['action'] == 'architect' )
	        {
	            return 'true';
	        }

	        if( isset( $_GET['tve'] ) && $_GET['tve'] == 'true' )
	        {
	            return 'true';
	        }

	        // Avada
	        if( isset( $_GET['fb-edit'] ) && self::looseEqualsPhp8( $_GET['fb-edit'], 1 ) )
	        {
	            return 'true';
	        }

	        // more Avada
	        if( isset($_GET['builder'] ) && $_GET['builder'] == 'true' )
	        {
	            return 'true';
	        }
	    }

	    // Build URL only if necessary
	    if( !is_object( $wp ) )
	    {
	        return 'false';
	    }

	    // Build URL
	    $current_href = null;
	    $alt_current_href = null;

	    if( isset( $_SERVER['QUERY_STRING'] ) )
	    {
	        $current_href = add_query_arg( $_SERVER['QUERY_STRING'], '', home_url( $wp->request ) );
	    }
	    else
	    {
	        $current_href = home_url( $wp->request );
	    }

	    if( isset( $_SERVER['SCRIPT_URI'] ) )
	    {
	        $alt_current_href = $_SERVER['SCRIPT_URI'];
	    }
	    elseif( isset( $_SERVER['REQUEST_URI'] ) )
	    {
	        $alt_current_href = $_SERVER['REQUEST_URI'];
	    }

	    // Regexp check
	    $rconfig = self::get_rconfig();
	    if( isset( $rconfig['url_skip_regexp'] ) )
	    {
	        $url_skip_regexp = $rconfig['url_skip_regexp'];

	        if( !is_array( $url_skip_regexp ) ) { $url_skip_regexp = array(); }

	        foreach( $url_skip_regexp as $regexp )
	        {
	            if( !is_string( $regexp ) || empty( $regexp ) ) { continue; }
	            if( ( $current_href && 1 === @preg_match( $regexp, $current_href ) ) ||
	                ( $alt_current_href && 1 === @preg_match( $regexp, $alt_current_href ) ) )
	            {
	                return 'true';
	            }
	        }
	    }

	    // Feed check
	    if( is_object( $wp_rewrite ) && $wp_rewrite->feeds )
	    {
	        foreach( $wp_rewrite->feeds as $feed )
	        {
	            $feed_url = get_feed_link( $feed );

	            if( ($current_href && $current_href == $feed_url) ||
	                ($alt_current_href && $alt_current_href == $feed_url ) )
	            {
	                return 'true';
	            }
	        }
	    }

	    // Legacy mode check
	    if( !$added_regexp_limited_check )
	    {
	        $the_settings = self::get_settings();

	        if( !(isset($the_settings) && $the_settings['forced_legacy_mode'] ) )
	        {
	            if( !empty( $_POST ) )
	            {
	                return 'true_due_to_post';
	            }
	        }
	    }

	    return 'false';
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
	 * Whitelist of settings keys that can be exported and imported.
	 *
	 * Explicit allow-list: only genuine, portable user-configuration keys are listed below; anything not listed is intentionally non-portable.
	 */
	public static function get_portable_settings_keys()
	{
		return array(
			// Banner / UI
			'is_on', 'is_bottom', 'cookie_banner_vertical_position', 'cookie_banner_horizontal_position',
			'cookie_banner_size', 'cookie_banner_shadow', 'cookie_banner_animation', 'floating_banner',
			'elements_border_radius', 'heading_background_color', 'heading_text_color', 'close_icon_color',
			'title_is_on', 'bar_heading_text', 'background', 'text', 'text_size', 'text_lineheight',
			'show_buttons_icons', 'button_accept_link_color', 'button_accept_button_color',
			'button_reject_link_color', 'button_reject_button_color', 'button_customize_link_color',
			'button_customize_button_color', 'map_inline_notify_color', 'map_inline_notify_background',
			'with_css_effects', 'consent_widget_style', 'disable_logo', 'custom_css', 'notify_div_id',
			'notify_position_horizontal', 'showagain_div_id', 'showagain_tab', 'wrap_shortcodes',
			'layer_1_button_order',
			// Policy (URLs / flags, not page IDs)
			'cookie_policy_link', 'is_cookie_policy_url', 'cookie_policy_url', 'is_personal_data_policy_url',
			'personal_data_policy_url', 'add_cookie_policy_to_first_layer', 'add_personal_policy_to_first_layer',
			// Privacy features
			'blocked_content_notify', 'blocked_content_notify_auto_shutdown',
			'blocked_content_notify_auto_shutdown_time', 'video_privacy_mode', 'maps_block', 'captcha_block',
			'enforce_youtube_privacy', 'show_ntf_bar_on_not_yet_consent_choice', 'forced_legacy_mode', 'is_dm',
			// Consent Mode / GTM
			'enable_iab_tcf', 'iab_enable_advertiser_consent_mode', 'enable_microsoft_cmode',
			'microsoft_consent_ad_storage', 'enable_clarity_cmode', 'clarity_consent_ad_storage',
			'clarity_consent_analytics_storage', 'bypass_cmode_enable', 'enable_cmode_v2',
			'enable_cmode_url_passthrough', 'disable_gtm_gateway_detection', 'cmode_v2_implementation_type',
			'cmode_v2_gtag_ad_storage', 'cmode_v2_gtag_ad_user_data', 'cmode_v2_gtag_ad_personalization',
			'cmode_v2_gtag_analytics_storage', 'cmode_v2_forced_off_ga4_advanced',
			// Scanner / i18n / update / misc
			'scanner_compatibility_mode', 'scanner_hook_type', 'scanner_start_hook_prio', 'scanner_end_hook_prio',
			'enable_language_fallback', 'language_fallback_locale', 'enable_metadata_sync', 'forced_auto_update',
			'website_name', 'alt_accepted_all_cookie_name', 'alt_accepted_something_cookie_name',
			'fixed_translations_encoded',
			// Policy Assistant (nested; inner prefix-whitelist applied on import)
			'site_and_policy_settings',
		);
	}


	/**
	 * Appearance keys handled by the banner split test.
	 */
	public static function get_banner_variant_keys()
	{
		return array(
			'cookie_banner_size', 'cookie_banner_vertical_position', 'cookie_banner_horizontal_position',
			'floating_banner', 'cookie_banner_shadow', 'elements_border_radius', 'cookie_banner_animation',
			'title_is_on', 'layer_1_button_order', 'text_size', 'text_lineheight',
			'background', 'heading_background_color', 'heading_text_color', 'text', 'show_buttons_icons',
			'button_accept_link_color', 'button_accept_button_color',
			'button_reject_link_color', 'button_reject_button_color',
			'button_customize_link_color', 'button_customize_button_color',
		);
	}


	/**
	 * Accepted values of the enumerated appearance keys.
	 */
	public static function get_banner_variant_enums()
	{
		return array(
			'cookie_banner_size'                => array( 'sizeWideBranded', 'sizeBig', 'sizeWide', 'sizeBoxed' ),
			'cookie_banner_vertical_position'   => array( 'Top', 'Center', 'Bottom' ),
			'cookie_banner_horizontal_position' => array( 'Center', 'Left', 'Right' ),
			'cookie_banner_shadow'              => array( 'false', 'map-shadow-soft', 'map-shadow-hard' ),
			'cookie_banner_animation'           => array( 'none', 'slide', 'fade' ),
			'layer_1_button_order'              => array(
				'accept_reject_customize', 'accept_customize_reject', 'reject_accept_customize',
				'reject_customize_accept', 'customize_accept_reject', 'customize_reject_accept',
			),
		);
	}


	/**
	 * Keeps the appearance values that are inside their domain, in their stored type.
	 *
	 * A value out of domain is dropped, so the saved setting keeps its place.
	 */
	public static function sanitise_banner_variant( $values )
	{
		$clean = array();

		if( !is_array( $values ) ) return $clean;

		$enums = self::get_banner_variant_enums();

		$ranges = array(
			'floating_banner'        => array( 0, 1 ),
			'elements_border_radius' => array( 0, 50 ),
			'text_size'              => array( 4, 50 ),
			'text_lineheight'        => array( 0, 100 ),
		);

		$booleans = array( 'title_is_on', 'show_buttons_icons' );

		foreach( self::get_banner_variant_keys() as $key )
		{
			if( !isset( $values[ $key ] ) ) continue;

			$value = $values[ $key ];

			if( is_array( $value ) || is_object( $value ) ) continue;

			if( isset( $enums[ $key ] ) )
			{
				if( is_string( $value ) && in_array( $value, $enums[ $key ], true ) )
				{
					$clean[ $key ] = $value;
				}

				continue;
			}

			if( isset( $ranges[ $key ] ) )
			{
				if( !self::isNumericPhp8( $value ) ) continue;

				$number = self::intvalPhp8( $value );

				if( $number < $ranges[ $key ][0] ) $number = $ranges[ $key ][0];
				if( $number > $ranges[ $key ][1] ) $number = $ranges[ $key ][1];

				$clean[ $key ] = ( $key === 'floating_banner' ) ? $number : strval( $number );

				continue;
			}

			if( in_array( $key, $booleans, true ) )
			{
				if( $value === true || $value === 'true' || $value === 1 || $value === '1' )
				{
					$clean[ $key ] = true;
				}
				elseif( $value === false || $value === 'false' || $value === 0 || $value === '0' )
				{
					$clean[ $key ] = false;
				}

				continue;
			}

			if( is_string( $value ) && preg_match( '/^(#[a-f0-9]{6}|#[a-f0-9]{3})$/i', $value ) )
			{
				$clean[ $key ] = $value;
			}
		}

		return $clean;
	}


	/**
	 * Resolves the banner configuration for this request, once.
	 */
	public static function arm_banner_variant()
	{
		if( self::$banner_overrides !== null ) return;

		self::$banner_overrides = array();

		if( is_admin() )                                                                                    return;
		if( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) ) return;
		if( defined( 'DOING_CRON' ) && DOING_CRON )                                                         return;
		if( defined( 'WP_CLI' ) && WP_CLI )                                                                 return;
		if( PHP_SAPI === 'cli' )                                                                            return;
		if( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST )                                                 return;

		if(
			( defined( 'REST_REQUEST' ) && REST_REQUEST ) ||
			( isset( $_GET['rest_route'] ) && is_string( $_GET['rest_route'] ) ) ||
			( isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], '/wp-json/' ) !== false )
		) return;

		if( function_exists( 'wp_is_json_request' ) && wp_is_json_request() )                               return;
		if( self::is_wplogin() )                                                                            return;
		if( !function_exists( 'map_call_advanced_get_banner_variant' ) )                                     return;

		$preview = '';
		$warm    = '';

		if(
			isset( $_GET['map_ab_preview'] ) && is_string( $_GET['map_ab_preview'] ) &&
			preg_match( '/^[a-z0-9]{1,32}$/', $_GET['map_ab_preview'] ) && self::map_can_edit()
		)
		{
			$preview = $_GET['map_ab_preview'];
		}
		elseif(
			isset( $_GET['map_ab_warm'] ) && is_string( $_GET['map_ab_warm'] ) &&
			preg_match( '/^[a-z0-9]{1,32}$/', $_GET['map_ab_warm'] ) && self::map_can_edit()
		)
		{
			$warm = $_GET['map_ab_warm'];
		}

		$cookie = '';

		if(
			isset( $_COOKIE['map_banner_variant'] ) && is_string( $_COOKIE['map_banner_variant'] ) &&
			preg_match( '/^[a-z0-9]{1,32}$/', $_COOKIE['map_banner_variant'] )
		)
		{
			$cookie = $_COOKIE['map_banner_variant'];
		}

		$variant = map_call_advanced_get_banner_variant( $cookie, $preview, $warm );

		self::$banner_variant_id   = $variant['id'];
		self::$banner_variant_slot = intval( $variant['slot'] );
		self::$banner_overrides    = self::sanitise_banner_variant( $variant['overrides'] );
		self::$banner_public       = $variant['public'];
	}


	/**
	 * Identifier of the banner configuration served in this request.
	 */
	public static function get_banner_variant_id()
	{
		return self::$banner_variant_id;
	}


	/**
	 * Cache key fragment of the served configuration; empty for the saved settings.
	 */
	public static function get_json_cache_variant_segment()
	{
		if( self::$banner_variant_slot === 0 || self::$banner_variant_id === 'default' ) return '';

		return self::$banner_variant_id . '_';
	}


	/**
	 * True while the page is rendered as a preview.
	 */
	public static function is_split_preview()
	{
		return ( isset( self::$banner_public['preview'] ) && self::$banner_public['preview'] === true );
	}


	/**
	 * Configuration block published to the frontend script.
	 */
	/**
	 * The two appearance flags the JS payload carries, from the settings they derive from.
	 *
	 * @param mixed  $title_is_on
	 * @param string $banner_size
	 * @param bool   $paid
	 * @return array
	 */
	public static function banner_look_flags( $title_is_on, $banner_size, $paid )
	{
		return array(
			'title'   => $title_is_on ? '1' : '0',
			'branded' => ( !$paid || $banner_size === 'sizeWideBranded' ) ? '1' : '0',
		);
	}

	public static function get_split_public_config()
	{
		$public = self::$banner_public;

		$ids = array();

		if( isset( $public['ids'] ) && is_array( $public['ids'] ) )
		{
			$ids = array_values( $public['ids'] );
		}

		$blog_id = is_multisite() ? get_current_blog_id() : 1;
		$scheme  = is_ssl() ? 'https' : 'http';

		$shim_base = '';

		if( defined( 'MAP_PLUGIN_FILENAME' ) )
		{
			$shim_base = self::get_plugin_url( MAP_PLUGIN_FILENAME ) . '/api/cache-json.php';
			$shim_base = preg_replace( '/([^:])(\/{2,})/', '$1/', $shim_base );
		}

		// The two flags each variant needs, so the swap does not fetch a second file for them.
		$settings = self::get_settings();
		$paid     = ( isset( $settings['pa'] ) && $settings['pa'] );
		$looks    = array();

		if( isset( $public['variant_looks'] ) && is_array( $public['variant_looks'] ) )
		{
			foreach( $public['variant_looks'] as $token => $raw )
			{
				if( !in_array( $token, $ids, true ) ) { continue; }

				$looks[ $token ] = self::banner_look_flags(
					isset( $raw['title_is_on'] ) ? $raw['title_is_on'] : false,
					isset( $raw['cookie_banner_size'] ) ? $raw['cookie_banner_size'] : '',
					$paid
				);
			}
		}

		return array(
			'active'      => ( isset( $public['active'] ) && $public['active'] === true ),
			'ids'         => $ids,
			'looks'       => $looks,
			'cookie_days' => isset( $public['cookie_days'] ) ? intval( $public['cookie_days'] ) : 30,
			'preview'     => self::is_split_preview(),
			'served'      => self::$banner_variant_id,
			'suffix'      => self::getCurrentLang4Char() . '_' . $blog_id . '_' . $scheme,
			'json_base'   => self::get_base_url_for_cache() . 'json/',
			'shim_base'   => $shim_base,
			'timeout_ms'  => 800,
		);
	}


	/**
	 * Returns default settings
	 * @access   public
	 */
	public static function get_default_settings( $key='' )
	{
		$default_locale = MyAgilePrivacy::get_locale();

		$site_and_policy_settings = array(

			'completion_percentage'							=>	0,

			'last_update_timestamp'							=>	null,

			'base_location'									=> 	null,
			'customer_location' 							=> 	null,

			'customer_area_eu'								=>	false,
			'customer_area_gb'								=>	false,
			'customer_area_ch'								=>	false,
			'customer_area_ca'								=>	false,
			'customer_area_br'								=>	false,

			'customer_area_california'						=>	false,
			'customer_area_colorado'						=>	false,
			'customer_area_connecticut'						=>	false,
			'customer_area_delaware'						=>	false,
			'customer_area_indiana'							=>	false,
			'customer_area_iowa'							=>	false,
			'customer_area_kentucky'						=>	false,
			'customer_area_maryland'						=>	false,
			'customer_area_minnesota'						=>	false,
			'customer_area_montana'							=>	false,
			'customer_area_nebraska'						=>	false,
			'customer_area_nevada'							=>	false,
			'customer_area_new_hampshire'					=>	false,
			'customer_area_new_jersey'						=>	false,
			'customer_area_oregon'							=>	false,
			'customer_area_rhode_island'					=>	false,
			'customer_area_tennessee'						=>	false,
			'customer_area_texas'							=>	false,
			'customer_area_utah'							=>	false,
			'customer_area_virginia'						=>	false,

			// per-country override map (ISO alpha-2 lowercase => bool)
			'geo_force_optout'								=>	array(),


			'regulation_gdpr_like'							=>	false,
			'regulation_gdpr_gb'							=>	false,


			'regulation_ccpa'								=>	false,
			'regulation_cpa'								=>	false,
			'regulation_ctdpa'								=>	false,
			'regulation_dpdpa'								=>	false,
			'regulation_incdpa'								=>	false,
			'regulation_icdpa'								=>	false,
			'regulation_kcdpa'								=>	false,
			'regulation_modpa'								=>	false,
			'regulation_mcdpa'								=>	false,
			'regulation_mtcdpa'								=>	false,
			'regulation_ndpa'								=>	false,
			'regulation_nevada'								=>	false,
			'regulation_nhpa'								=>	false,
			'regulation_njdpa'								=>	false,
			'regulation_ocpa'								=>	false,
			'regulation_ridtppa'							=>	false,
			'regulation_tipa'								=>	false,
			'regulation_tdpsa'								=>	false,
			'regulation_ucpa'								=>	false,
			'regulation_vcdpa'								=>	false,


			'regulation_lpd'								=>	false,
			'regulation_lgpd'								=>	false,
			'regulation_pipeda'								=>	false,


			'identity_name'									=> 	null,
			'identity_address'								=>	null,
			'identity_vat_id'								=>	null,
			'identity_email'								=>	null,
			'display_dpo'									=> 	false,

			'dpo_email'										=> 	null,
			'dpo_name'										=> 	null,
			'dpo_address'									=> 	null,

			'site_features_contact_forms'					=>	false,
			'site_features_payments'						=>	false,
			'site_features_account_reg'						=>	false,
			'site_features_newsletter'						=>	false,
			'site_features_show_marketing_data_retention'	=>	false,
			'site_features_newsletter_tracking'				=>	false,
			'site_features_newsletter_click_tracking'		=>	false,
			'site_features_service_email_tracking'			=>	false,
			'site_features_reviews_collect'					=>	false,
			'site_features_minors_data'						=>	false,
			'site_features_sensitive_data'					=>	false,
			'site_features_social_login'					=>	false,
			'site_features_advertising'						=>	false,
			'outside_adequate_suppliers'					=>	false,


			'protection_system_https'						=>	false,
			'protection_system_log_control'					=>	false,
			'protection_system_backup'						=>	false,
			'protection_system_audit'						=>	false,
			'protection_system_access_limited'				=>	false,
		);


		$settings = array(
			'is_on' 									=> 	true,
			'is_bottom'									=>	true,
			'cookie_banner_vertical_position'			=> 	null,
			'cookie_banner_horizontal_position'			=> 	null,
			'cookie_banner_size'						=> 	'sizeWideBranded',
			'cookie_banner_shadow'						=> 	false,
			'cookie_banner_animation'					=> 	'none',
			'floating_banner'							=> 	1,
			'elements_border_radius'					=> 	15,
			'heading_background_color'					=> 	'#F14307',
			'heading_text_color'						=> 	'#ffffff',
			'close_icon_color'							=> 	'#ffffff',
			'title_is_on'								=> 	true,
			'bar_heading_text'							=>	'',
			'background' 								=> 	'#ffffff',
			'text' 										=> 	'#333333',
			'text_size'									=> 	18,
			'text_lineheight'							=> 	30,
			'show_buttons_icons'						=> 	false,
			'button_accept_link_color' 					=> 	'#ffffff',
			'button_accept_button_color' 				=> 	'#3d3d3d',
			'button_reject_link_color' 					=> 	'#fff',
			'button_reject_button_color' 				=> 	'#3d3d3d',
			'button_customize_link_color' 				=> 	'#ffffff',
			'button_customize_button_color' 			=> 	'#3d3d3d',
			'map_inline_notify_color'					=>	'#324962',
			'map_inline_notify_background'				=>	'#d1e7ff',
			'website_name'								=>	'',
			'license_code'								=>	'',
			'license_user_status'						=>	'Demo License',
			'dont_ask_license_code'						=>	false,
			'dont_ask_license_code_timestamp'			=>	0,
			'is_dm'										=>	true,
			'license_valid'								=>	true,
			'grace_period'								=>	false,
			'customer_email'							=>	null,
			'summary_text'								=>	null,
			'license_name'								=>	'',
			'notify_div_id'							=> '#my-agile-privacy-notification-area',
			'showagain_tab' 							=> 	true,
			'wrap_shortcodes'							=>	false,
			'notify_position_horizontal'				=> 	'right',
			'showagain_div_id' 							=> 	'my-agile-privacy-consent-again',
			'cookie_policy_link'						=>	false,
			'is_cookie_policy_url'						=>	false,
			'cookie_policy_url'							=>	null,
			'cookie_policy_page'						=> 	0,
			'is_personal_data_policy_url'				=>	false,
			'personal_data_policy_url'					=>	null,
			'personal_data_policy_page'					=>	0,
			'default_locale'							=>	$default_locale,
			'disable_logo'								=>	false,
			'consent_widget_style'						=>	'text_and_logo',
			'wl_b'										=>	0,
			'pa'										=>	0,
			'last_legit_sync'							=>	null,
			'custom_css'								=>	null,
			'scan_mode'									=>	'learning_mode',
			'blocked_content_notify'					=>	false,
			'blocked_content_notify_auto_shutdown'		=>	true,
			'blocked_content_notify_auto_shutdown_time'	=>	3000,
			'video_privacy_mode'						=>	'anonymous',
			'maps_block'								=>	true,
			'captcha_block'								=>	true,
			'parse_config'								=>	null,
			'parse_config_version_number'				=>	null,
			'scanner_compatibility_mode'				=>	true,
			'scanner_hook_type'							=>	'init-shutdown',
			'scanner_start_hook_prio'					=>	-10000,
			'scanner_end_hook_prio'						=>	-10000,
			'alt_accepted_all_cookie_name'				=>	null,
			'alt_accepted_something_cookie_name'		=>	null,
			'enforce_youtube_privacy'					=>	false,
			'show_ntf_bar_on_not_yet_consent_choice'	=>	false,
			'with_css_effects'							=>	true,
			'forced_legacy_mode'						=>	false,
			'dev_mode'									=>	false,
			'forced_auto_update'						=>	true,
			'enable_iab_tcf'							=>	false,
			'enable_metadata_sync'						=>	true,

			'enable_microsoft_cmode'					=>	false,
			'microsoft_consent_ad_storage'				=> 	'denied',

			'enable_clarity_cmode'						=>	false,
			'clarity_consent_ad_storage'				=> 	'denied',
			'clarity_consent_analytics_storage'			=> 	'denied',

			'bypass_cmode_enable'						=> 	false,
			'enable_cmode_v2'							=>	false,
			'enable_cmode_url_passthrough'				=> 	false,
			'iab_enable_advertiser_consent_mode'		=>	true,
			'disable_gtm_gateway_detection'				=>	false,
			'cmode_v2_implementation_type'				=> 	'native',
			'cmode_v2_gtag_ad_storage'					=> 	'denied',
			'cmode_v2_gtag_ad_user_data'				=> 	'denied',
			'cmode_v2_gtag_ad_personalization'			=> 	'denied',
			'cmode_v2_gtag_analytics_storage'			=> 	'denied',

			'fixed_translations_encoded'				=>	null,
			'cmode_v2_forced_off_ga4_advanced'			=>	false,

			'enable_language_fallback' 					=> 	false,
			'language_fallback_locale'					=>	null,
			'layer_1_button_order'						=>	'accept_reject_customize',
			'site_and_policy_settings'					=>	$site_and_policy_settings,

			'add_cookie_policy_to_first_layer'			=>	false,
			'add_personal_policy_to_first_layer'		=>	false,

			'send_ga4_event_on_consent_change'			=>	false,

			'stats_enabled'								=>	false,


		);

		$filtered_settings = apply_filters( 'map_plugin_settings', $settings );

		if( is_array( $filtered_settings ) )
		{
			$settings = $filtered_settings;
		}

		if( $key != "" )
		{
			return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
		}

		return $settings;
	}


	/**
	 * Returns list of HTML tags allowed in HTML fields for use in declaration of wp_kset field validation.
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
				'tabindex' => array(),
				'aria-pressed' => array(),
				'aria-label' => array(),
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
				'role'=>array(),
				'aria-label' => array(),
				'data-map-enable' => array(),
				'data-map-disable' => array(),
				'role' => array(),
				'tabindex' => array(),
				'aria-checked' => array(),
				'data-wg-notranslate' => array(),
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
				'style' => array(),
				'role' => array(),
				'aria-label' => array(),
			),
			'span' => array(
				'id' => array(),
				'class' => array(),
				'style' => array(),
				'role' => array(),
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
				'role' => array(),
				'tabindex' => array(),
				'aria-checked' => array(),
				'aria-label' => array(),
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
	 * Returns the tags allowed in identity and DPO fields.
	 * @access   public
	 */
	public static function allowed_identity_tags()
	{
		return array(
			'b'      => array(),
			'strong' => array(),
			'i'      => array(),
			'em'     => array(),
			'br'     => array(),
		);
	}


	/**
	 * Returns list of allowed protocols, used in wp_kset field validation.
	 * @access   public
	 */
	public static function allowed_protocols()
	{
		return array ( 'http', 'https' );
	}

	/**
	 * Returns JSON object containing user settings
	 * @access   public
	 */
	public static function get_json_settings()
	{
		$the_settings = self::get_settings();
		$rconfig = self::get_rconfig();

		$internal_debug = false;

		//get translations
		$the_translations = MyAgilePrivacy::getFixedTranslations();
		$current_lang = MyAgilePrivacy::getCurrentLang4Char();

		if( current_user_can( 'manage_options' ) && isset( $the_settings['pa'] ) && $the_settings['pa'] == 1 )
		{
			$internal_debug = true;
		}

		$verbose_remote_log = false;

		if( isset( $rconfig ) &&
			isset( $rconfig['verbose_remote_log'] ) &&
			$rconfig['verbose_remote_log'] )
		{
			$verbose_remote_log = true;
		}

		$map_look = self::banner_look_flags(
			$the_settings['title_is_on'],
			isset( $the_settings['cookie_banner_size'] ) ? $the_settings['cookie_banner_size'] : '',
			( isset( $the_settings['pa'] ) && $the_settings['pa'] )
		);

		$map_notify_title        = $map_look['title'];
		$map_first_layer_branded = $map_look['branded'];

		$return_settings = array(
			'verbose_remote_log'						=>	$verbose_remote_log,
			'internal_debug'							=>	$internal_debug,
			'notify_div_id'								=> 	$the_settings['notify_div_id'],
			'showagain_tab'								=> 	$the_settings['showagain_tab'],
			'notify_position_horizontal'				=> 	$the_settings['notify_position_horizontal'],
			'showagain_div_id'							=> 	$the_settings['showagain_div_id'],
			'blocked_content_text'						=>	esc_html( $the_translations[ $current_lang ]['blocked_content'] ).'.',
			'blocked_video_text'						=>	( isset( $the_translations[ $current_lang ]['blocked_video'] ) ) ? esc_html( $the_translations[ $current_lang ]['blocked_video'] ).'.' : esc_html( $the_translations[ $current_lang ]['blocked_content'] ).'.',
			'blocked_maps_text'							=>	( isset( $the_translations[ $current_lang ]['blocked_maps'] ) ) ? esc_html( $the_translations[ $current_lang ]['blocked_maps'] ).'.' : esc_html( $the_translations[ $current_lang ]['blocked_content'] ).'.',
			'inline_notify_color'						=>	$the_settings['map_inline_notify_color'],
			'inline_notify_background'					=>	$the_settings['map_inline_notify_background'],
			'blocked_content_notify_auto_shutdown_time'	=>	$the_settings['blocked_content_notify_auto_shutdown_time'],

			'scan_mode'									=>	$the_settings['scan_mode'],
			'cookie_reset_timestamp'					=>	( isset( $the_settings['cookie_reset_timestamp'] ) ) ? '_'.$the_settings['cookie_reset_timestamp'] : null,
			'show_ntf_bar_on_not_yet_consent_choice'	=>	$the_settings['show_ntf_bar_on_not_yet_consent_choice'],

			'enable_microsoft_cmode'					=> 	$the_settings['enable_microsoft_cmode'],

			'enable_clarity_cmode'						=> 	$the_settings['enable_clarity_cmode'],

			'enable_cmode_v2'							=> 	$the_settings['enable_cmode_v2'],
			'enable_cmode_url_passthrough'				=>	$the_settings['enable_cmode_url_passthrough'],
			'cmode_v2_forced_off_ga4_advanced'			=>	$the_settings['cmode_v2_forced_off_ga4_advanced'],
			'map_notify_title'							=>	$map_notify_title,
			'map_first_layer_branded'					=>	$map_first_layer_branded,
			'plugin_version'							=>	MAP_PLUGIN_VERSION,
			'parse_config_version_number'				=>	$the_settings['parse_config_version_number'],
		);

		return $return_settings;
	}

	/**
	 * Returns the settings payload without values tied to the current visitor
	 * @access   public
	 */
	public static function anonymize_json_settings( $settings )
	{
		$settings['internal_debug'] = false;

		return $settings;
	}

	/**
	 * f for cleaning hex color
	 */
	public static function clean_hex_color( $hex )
	{
		$hex = strtolower( is_scalar( $hex ) ? (string) $hex : '' );

		//remove the leading "#"
		if(strlen($hex) == 7 || strlen($hex) == 4)
			$hex = substr($hex, -(strlen($hex) - 1));

		// $hex like "1a7"
		if(preg_match('/^[a-f0-9]{6}$/i', $hex))
			return '#'.$hex;
		// $hex like "162a7b"
		elseif(preg_match('/^[a-f0-9]{3}$/i', $hex))
			return '#'.$hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		//any other format
		else
			return "#000000";
	}

	/**
	 * Sends a POST request to the remote API endpoint.
	 *
	 * @param $params   array   The parameters for the API call
	 * @return          array   The API response
	 * @access   public
	 */
	public static function call_api( $params )
	{
		if( !is_string( MAP_API_ENDPOINT ) )
		{
			return array(
				'internal_error_message'	=>	'invalid endpoint',
			);
		}

		$url = MAP_API_ENDPOINT;

		$site_url = null;

		if( function_exists( 'get_site_url' ) )
		{
			$site_url = get_site_url();
		}

		// Set up arguments for POST request
		$args = array(
			'sslverify' =>	!( defined( 'MAP_API_SKIP_SSL_VERIFY' ) && MAP_API_SKIP_SSL_VERIFY ),
			'headers' 	=>	array(
				'Referer' 	=> $site_url,
			),
			'body' 		=>	$params
		);

		// Send the request
		$response = wp_remote_post( $url, $args );
		$http_code = wp_remote_retrieve_response_code( $response );

		if( is_wp_error( $response ) || !( $http_code == '200' || $http_code == '429' ) )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $response );

			$error_code = null;
			$error_message = null;

			if( isset( $response->errors ) && is_array( $response->errors ) && !empty( $response->errors ) )
			{
				reset( $response->errors );
				$error_code = key( $response->errors );
				$error_message = $response->errors[ $error_code ][0];
			}

			$result = array(
				'internal_error_message'	=>	"$error_code -> $error_message ( $http_code )",
			);

			return $result;
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

		$current_plugin_url = self::get_plugin_url( MAP_PLUGIN_FILENAME );

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

			if( is_file( $local_filename_fullpath ) )
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

		$allowed_extensions = array( 'js', 'css', 'json' );

		if( !in_array( strtolower( pathinfo( $local_filename, PATHINFO_EXTENSION ) ), $allowed_extensions, true ) )
		{
			return false;
		}

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
				if( is_file( $local_filename_fullpath ) && @filemtime( $local_filename_fullpath ) > $max_age &&
					is_file( $local_alt_filename_fullpath ) && @filemtime( $local_alt_filename_fullpath ) > $max_age
				)
				{
					//no download needed
					if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'check B : no download needed' );
					return true;
				}
			}
			else
			{
				if( is_file( $local_filename_fullpath ) && @filemtime( $local_filename_fullpath ) > $max_age )
				{
					//no download needed
					if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'check C : no download needed' );
					return true;
				}
			}
		}

		// the other files of a request whose CDN did not answer are not tried, and keep their old copy
		if( self::$cdn_unreachable )
		{
			return false;
		}

		if( ! wp_mkdir_p( $directory ) )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'Error creating needed directory: ' . $directory );
			if( !get_transient( 'map_download_brake' ) ) { set_transient( 'map_download_brake', 1, 12 * HOUR_IN_SECONDS ); }
			return false;
		}

		if( !function_exists( 'download_url' ) )
		{
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$download_url = $remote_filename;

		if( $version_number )
		{
		    $download_url = add_query_arg( 'v', $version_number, $remote_filename );
		}

		$tmp_file = download_url( $download_url, 10 );

		if( !$tmp_file || !is_string( $tmp_file ) )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'Error downloading remote_filename: ' . $remote_filename );

			if( is_wp_error( $tmp_file ) && $tmp_file->get_error_code() === 'http_request_failed' )
			{
				self::$cdn_unreachable = true;

				if( !get_transient( 'map_download_brake' ) ) { set_transient( 'map_download_brake', 1, 12 * HOUR_IN_SECONDS ); }
			}

			return false;
		}

		// the old copy goes only once a new one is in hand
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

		$copied = @copy( $tmp_file, $local_filename_fullpath );

		if( $copied && $alt_local_filename )
		{
			$copied = @copy( $tmp_file, $local_alt_filename_fullpath );
		}

		if( file_exists( $tmp_file ) ) { wp_delete_file( $tmp_file ); }

		if( !$copied )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'Error copying remote_filename: ' . $remote_filename );

			// a partial copy must not be served as a good file
			if( file_exists( $local_filename_fullpath ) ) { wp_delete_file( $local_filename_fullpath ); }
			if( $local_alt_filename_fullpath && file_exists( $local_alt_filename_fullpath ) ) { wp_delete_file( $local_alt_filename_fullpath ); }

			if( !get_transient( 'map_download_brake' ) ) { set_transient( 'map_download_brake', 1, 12 * HOUR_IN_SECONDS ); }
			return false;
		}

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'download_remote_file -> remote file downloaded to '.$local_filename_fullpath . ' from '.$remote_filename );

		//old folder cleanup
		$old_cache_dir = WP_CONTENT_DIR . '/local-cache/'.MAP_PLUGIN_NAME.'/';
		if( !is_link( rtrim( $old_cache_dir, '/' ) ) )
		{
			MyAgilePrivacy::flush_manifest_file_cache( $old_cache_dir, true ) ;
		}

		return true;
	}

	/**
	 * clear file cache
	 */
	public static function flush_manifest_file_cache( $directory = null, $remove_dir = false )
	{
		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( "flush_manifest_file_cache with params directory=$directory, remove_dir=$remove_dir" );

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

		$objects = @scandir( $directory );

		if( !is_array( $objects ) )
		{
			return false;
		}

		foreach ( $objects as $object ) {
			if( $object != "." && $object != ".." ) {
				$this_filepath = $directory . DIRECTORY_SEPARATOR . $object;

				if( is_link( $this_filepath ) ) {
					@unlink( $this_filepath );
				} elseif( is_dir( $this_filepath ) ) {
					MyAgilePrivacy::flush_manifest_file_cache( $this_filepath, $remove_dir );
				} else {
					if( file_exists( $this_filepath ) ) { wp_delete_file( $this_filepath ); }
				}
			}
		}

		if( $remove_dir )
		{
			@rmdir( $directory );
		}

		return true;
	}

	/**
	 * Returns the base path of the JSON cache folder.
	 *
	 * @return string|null Full directory path, or null if plugin name is not defined.
	 */
	public static function get_json_cache_directory()
	{
	    $base = MyAgilePrivacy::get_base_directory_for_cache();
	    if( !$base ) return null;
	    return $base . 'json/';
	}

	/**
	 * Ensures the JSON cache directory exists, creating it if necessary.
	 *
	 * @return bool True if the directory exists and is writable, false otherwise.
	 */
	public static function ensure_json_cache_directory()
	{
	    $dir = MyAgilePrivacy::get_json_cache_directory();
	    if( !$dir ) return false;

	    if( !is_dir( $dir ) )
	    {
	        $created = wp_mkdir_p( $dir );
	        if( !$created ) return false;
	    }

	    return is_dir( $dir ) && is_writable( $dir );
	}

	/**
	 * Builds the full file path for a given cache key.
	 * The cache key is sanitized to be safe for use as a filename.
	 *
	 * @param string $cache_key Unique identifier for the cached item (e.g. 'head_script_it').
	 * @return string|null Full file path, or null if the directory cannot be resolved.
	 */
	public static function get_json_cache_filepath( $cache_key )
	{
	    $dir = MyAgilePrivacy::get_json_cache_directory();
	    if( !$dir ) return null;

	    // Sanitize the key: allow only alphanumeric characters, dashes and underscores
	    $safe_key = preg_replace( '/[^a-zA-Z0-9_\-]/', '_', $cache_key );
	    return $dir . $safe_key . '.json';
	}

	/**
	 * Saves a value to the JSON file cache.
	 *
	 * The data is wrapped in a payload envelope containing metadata
	 * (creation time, expiry time, original key) before being written to disk.
	 * Uses atomic write (temp file + rename) to prevent file corruption.
	 *
	 * @param string $cache_key   Unique identifier for the cached item.
	 * @param mixed  $data        The data to cache. Must be JSON-serializable.
	 * @param int    $ttl_seconds Time-to-live in seconds. Defaults to 6 hours (21600).
	 * @param string|null $adv    Module state stored with the entry; null stores none.
	 * @return bool True on success, false on failure.
	 */
	public static function set_json_cache( $cache_key, $data, $ttl_seconds = 21600, $adv = null )
	{
	    if( !function_exists( 'file_put_contents' ) || !function_exists( 'rename' ) ) return false;

	    if( !MyAgilePrivacy::ensure_json_cache_directory() ) return false;

	    $filepath = MyAgilePrivacy::get_json_cache_filepath( $cache_key );
	    if( !$filepath ) return false;

	    // Build the cache envelope with metadata
	    $payload = array(
	        'expires_at'    => time() + $ttl_seconds,
	        'created_at'    => time(),
	        'cache_key'     => $cache_key,
	        'data'          => $data,
	    );

	    if( null !== $adv )
	    {
	        $payload['adv'] = $adv;
	    }

	    $json = json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

	    if( $json === false ) return false;

	    // Atomic write: write to a temp file first, then rename to the final path.
	    // rename() is atomic on the same filesystem, preventing partial reads.
	    $tmp_filepath = $filepath . '.tmp.' . uniqid();
	    $written = file_put_contents( $tmp_filepath, $json, LOCK_EX );

	    if( $written !== strlen( $json ) )
	    {
	        wp_delete_file( $tmp_filepath );
	        return false;
	    }

	    // A concurrent cache flush may remove the temp file before the rename.
	    if( !@rename( $tmp_filepath, $filepath ) )
	    {
	        wp_delete_file( $tmp_filepath );
	        return false;
	    }

	    return true;
	}

	/**
	 * Reads a value from the JSON file cache.
	 *
	 * Returns false if the cache file does not exist, is expired, or is corrupted.
	 * Expired and corrupted files are NOT deleted here - they will be overwritten
	 * by the next set_json_cache() call
	 *
	 * @param string $cache_key Unique identifier for the cached item.
	 * @param string|null $adv  Module state the entry must carry; null skips the check.
	 * @return mixed|false The cached data on success, false on cache miss or expiry.
	 */
	public static function get_json_cache( $cache_key, $adv = null )
	{
	    $filepath = MyAgilePrivacy::get_json_cache_filepath( $cache_key );
	    if( !$filepath ) return false;

	    if( !is_file( $filepath ) ) return false;

	    $raw = @file_get_contents( $filepath );
	    if( $raw === false ) return false;

	    $payload = json_decode( $raw, true );

	    // Validate payload structure
	    if( !is_array( $payload ) ||
	        !isset( $payload['expires_at'] ) ||
	        !isset( $payload['data'] ) )
	    {
	        return false;
	    }

	    // Check expiry
	    if( time() > $payload['expires_at'] )
	    {
	        return false;
	    }

	    if( null !== $adv &&
	        ( !array_key_exists( 'adv', $payload ) || $payload['adv'] !== $adv ) )
	    {
	        return false;
	    }

	    return $payload['data'];
	}


	/**
	 * Invalidates all JSON cache files by setting their expiry to the past.
	 *
	 * Files are NOT deleted - they are marked as expired so that the next
	 * get_json_cache() call returns false and triggers regeneration via set_json_cache().
	 * This approach ensures no downtime: the old file remains on disk and can still
	 * be inspected, while the next request will overwrite it with fresh data.
	 *
	 * @return int Number of files successfully invalidated.
	 */
	public static function flush_json_cache( $prefix = '' )
	{
	    if( !function_exists( 'file_put_contents' ) || !function_exists( 'rename' ) || !function_exists( 'glob' ) ) return 0;

	    $dir = MyAgilePrivacy::get_json_cache_directory();
	    if( !$dir || !is_dir( $dir ) ) return 0;

	    $invalidated = 0;
	    $files = glob( $dir . $prefix . '*.json' );

	    if( !is_array( $files ) ) return 0;

	    foreach( $files as $file )
	    {
	        if( !is_file( $file ) ) continue;

	        $raw = @file_get_contents( $file );
	        if( $raw === false ) continue;

	        $payload = json_decode( $raw, true );

	        if( !is_array( $payload ) ) continue;

	        // Set expiry to the past to mark the file as expired
	        $payload['expires_at'] = time() - 1;

	        $json = json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

	        if( $json === false ) continue;

	        // Atomic write to avoid corruption during the update
	        $tmp_filepath = $file . '.tmp.' . uniqid();
	        $written = file_put_contents( $tmp_filepath, $json, LOCK_EX );

	        if( $written !== strlen( $json ) )
	        {
	            wp_delete_file( $tmp_filepath );
	            continue;
	        }

	        if( !@rename( $tmp_filepath, $file ) )
	        {
	            wp_delete_file( $tmp_filepath );
	            continue;
	        }

	        $invalidated++;
	    }

	    return $invalidated;
	}

	/**
	 * Deletes the cache files whose name carries the given fragment.
	 */
	public static function purge_json_cache( $fragment )
	{
	    if( !is_string( $fragment ) || !preg_match( '/^[a-z0-9]{1,32}$/', $fragment ) ) return 0;

	    $dir = MyAgilePrivacy::get_json_cache_directory();
	    if( !$dir || !is_dir( $dir ) ) return 0;

	    // The directory is shared across the network while the identifiers restart
	    // per site: without the blog id this would delete another site's files.
	    $blog_id = is_multisite() ? get_current_blog_id() : 1;
	    $files   = glob( $dir . '*' . $fragment . '*_' . $blog_id . '_*.json' );

	    if( !is_array( $files ) ) return 0;

	    $deleted = 0;

	    foreach( $files as $file )
	    {
	        if( !is_file( $file ) ) continue;

	        wp_delete_file( $file );

	        $deleted++;
	    }

	    return $deleted;
	}

	/**
	 * equivalent for php7 null coalesce (array)
	 */
	public static function nullCoalesceArrayItem( $var, $key, $default = null )
	{
		return isset( $var[ $key ] ) ? $var[ $key ] : $default;
	}


	/**
	 * First value of a post meta entry, or the default when the key is absent.
	 * The array access happens inside, so the caller never triggers a warning.
	 */
	public static function nullCoalesceMetaItem( $all_meta, $key, $default = null )
	{
		return isset( $all_meta[ $key ][0] ) ? $all_meta[ $key ][0] : $default;
	}


	/**
	 * Whether a value is numeric the way PHP 8 reads it, on every version.
	 */
	public static function isNumericPhp8( $value )
	{
		if( is_int( $value ) || is_float( $value ) )
		{
			return true;
		}

		if( !is_string( $value ) )
		{
			return false;
		}

		$trimmed = rtrim( $value, " \t\n\r\v\f" );

		return is_numeric( $trimmed ) && !preg_match( '/^[ \t\n\r\v\f]*[+-]?0[xX]/', $trimmed );
	}


	/**
	 * Loose comparison with the PHP 8 result, on every version.
	 */
	public static function looseEqualsPhp8( $a, $b )
	{
		$spaces = " \t\n\r\v\f";

		if( is_string( $a ) && is_string( $b ) )
		{
			if( self::isNumericPhp8( $a ) && self::isNumericPhp8( $b ) )
			{
				return rtrim( $a, $spaces ) == rtrim( $b, $spaces );
			}

			return $a === $b;
		}

		if( ( is_int( $a ) || is_float( $a ) ) && is_string( $b ) )
		{
			if( is_float( $a ) && is_nan( $a ) )
			{
				return false;
			}

			return self::isNumericPhp8( $b ) ? ( $a == rtrim( $b, $spaces ) ) : ( (string) $a === $b );
		}

		if( ( is_int( $b ) || is_float( $b ) ) && is_string( $a ) )
		{
			if( is_float( $b ) && is_nan( $b ) )
			{
				return false;
			}

			return self::isNumericPhp8( $a ) ? ( $b == rtrim( $a, $spaces ) ) : ( (string) $b === $a );
		}

		return $a == $b;
	}


	/**
	 * json_encode that keeps what JSON can write when some values cannot be encoded
	 */
	public static function jsonEncodeSafe( $data, $flags = 0 )
	{
		$json = wp_json_encode( $data, $flags );

		if( $json === false )
		{
			$json = json_encode( $data, $flags | JSON_PARTIAL_OUTPUT_ON_ERROR );
		}

		return $json;
	}


	/**
	 * intval() with the PHP 8 reading of numeric strings, saturated instead of wrapped.
	 */
	public static function intvalPhp8( $value )
	{
		if( is_string( $value ) )
		{
			if( preg_match( '/^[ \t\n\r\v\f]*[+-]?[0-9]+[ \t\n\r\v\f]*$/', $value ) )
			{
				return (int) $value;
			}

			if( !preg_match( '/^[ \t\n\r\v\f]*[+-]?([0-9]+(\.[0-9]*)?|\.[0-9]+)([eE][+-]?[0-9]+)?/', $value ) )
			{
				return intval( $value );
			}

			$value = (float) $value;
		}

		if( is_float( $value ) )
		{
			if( is_nan( $value ) || is_infinite( $value ) )
			{
				return 0;
			}

			if( $value >= PHP_INT_MAX )
			{
				return PHP_INT_MAX;
			}

			if( $value <= -PHP_INT_MAX )
			{
				return -PHP_INT_MAX;
			}

			return (int) $value;
		}

		return intval( $value );
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

		$keysToKeep = array(
			'SERVER_SOFTWARE',
			'SERVER_SIGNATURE',
			'SERVER_NAME',
			'SERVER_PORT',
			'SERVER_PROTOCOL',
			'HTTP_HOST',
			'HTTPS',
			'REQUEST_SCHEME',
			'REQUEST_METHOD',
			'REQUEST_TIME',
			'REDIRECT_STATUS',
			'GATEWAY_INTERFACE',
			'FCGI_ROLE',
			'DOCUMENT_ROOT',
			'CONTEXT_DOCUMENT_ROOT',
			'CONTEXT_PREFIX',
			'SCRIPT_NAME',
			'SCRIPT_FILENAME',
			'PHP_SELF',
			'PHP_INI_SCAN_DIR',
			'PHP_MEMORY_LIMIT',
			'PHP_MAX_EXECUTION_TIME',
			'PHP_MAX_INPUT_TIME',
			'PHP_POST_MAX_SIZE',
			'PHP_UPLOAD_MAX_FILESIZE',
			'SSL_PROTOCOL',
			'HTTP_X_FORWARDED_PROTO',
			'HTTP_X_FORWARDED_PORT',
			'HTTP_X_HTTPS',
			'HTTP_CF_VISITOR',
			'HTTP_CDN_LOOP',
			'X-LSCACHE',
		);

		$keysToRemove = array(
			'HTTP_COOKIE',
			'HTTP_USER_AGENT',
			'HTTP_X_REAL_IP',
			'HTTP_X_REMOTE_IP',
			'HTTP_CF_CONNECTING_IP',
			'HTTP_CF_IPCOUNTRY',
			'HTTP_X_FORWARDED_FOR',
			'SERVER_ADDR',
			'REMOTE_ADDR',
			'PROXY_REMOTE_ADDR',
			'SSL_CLIENT_CERT',
			'SSL_SERVER_CERT',
			'PHP_AUTH_USER',
			'PHP_AUTH_PW',
			'PHP_AUTH_DIGEST',
			'HTTP_AUTHORIZATION',
			'HTTP_PROXY_AUTHORIZATION',
			'REDIRECT_HTTP_AUTHORIZATION'
		);

		foreach( $_SERVER as $k => $v )
		{
			if( in_array( $k, $keysToRemove, true ) )
			{
				$return_data[ $k ] = '(set)';
			}
			elseif( in_array( $k, $keysToKeep, true ) && is_scalar( $v ) )
			{
				$return_data[ $k ] = $v;
			}
		}

		return $return_data;
	}

	//f for cache purge (system cache); $scope: 'all', 'blog' or 'network'
	public static function tryOtherPluginCacheClear( $scope = 'all' )
	{
		if( $scope !== 'blog' && $scope !== 'network' )
		{
			$scope = 'all';
		}

		$blog    = ( $scope === 'all' || $scope === 'blog' );
		$network = ( $scope === 'all' || $scope === 'network' );

		//w3 total cache
		if( $network && function_exists( 'w3tc_pgcache_flush' ) )
		{
			w3tc_pgcache_flush();
		}

		//wordpress
		if( $network && function_exists( 'wp_cache_clear_cache' ) )
		{
			if( $scope === 'network' && is_multisite() )
			{
				wp_cache_clear_cache( get_current_blog_id() );
			}
			else
			{
				wp_cache_clear_cache();
			}
		}

		//sg optimizer
		if( $blog && function_exists( 'sg_cachepress_purge_cache' ) )
		{
			sg_cachepress_purge_cache();
		}

		//wp rocket
		if( $blog && function_exists( 'rocket_clean_domain' ) )
		{
			rocket_clean_domain();
		}

		//WP Fastest Cache
		if( $network && function_exists( 'wpfc_clear_all_cache' ) )
		{
			wpfc_clear_all_cache();
		}

		//LiteSpeed Cache
		if( $network )
		{
			do_action( 'litespeed_purge_all', 'My Agile Privacy' );
		}
	}

	/**
	* sort frontend cookies
	*/
	public static function frontendCookieSort( $a, $b )
	{
		$priority_list = array(
			'my_agile_pixel_ga',
			'google_analytics',
			'google_tag_manager',
			'stape',
			'microsoft_ads',
		);

		$a_priority = array_search($a['api_key'], $priority_list);
		$b_priority = array_search($b['api_key'], $priority_list);

		// If both are in the priority list
		if( $a_priority !== false && $b_priority !== false )
		{
			// Order according to their position in the list
			if( $a_priority === $b_priority )
			{
				// If they have the same priority, sort by post_title
				return strcasecmp($a['post_title'], $b['post_title']);
			}
			return $a_priority - $b_priority;
		}

		// If ONLY $a is priority
		if( $a_priority !== false )
		{
			return -1;
		}

		// If ONLY $b is priority
		if( $b_priority !== false )
		{
			return 1;
		}

		// If neither one is priority, sort by post_title
		return strcasecmp($a['post_title'], $b['post_title']);
	}

	//f for getting global integrity checks
	public static function getGlobalIntegrityChecks( $caller = 'frontend' )
	{
		$the_settings = self::get_settings();

		$MyAgilePrivacyRegulationHelper = new MyAgilePrivacyRegulationHelper();
		$site_and_policy_settings = $MyAgilePrivacyRegulationHelper->getSiteAndPolicySettings();

		$global_integrity_checks = array(
			'integrity_check_version' 		=> 	MAP_INTEGRITY_CHECK_VERSION,
			'dashboard_checks'				=>	null,
			'dashboard_checks_count'		=>	0,
			'dashboard_checks_passed_count'	=>	0,
			'other_frontend_checks'			=>	null,
			'backend_checks'				=>	null,
			'summary'						=>	array(
													'status_class_name'				=>	'bg-success',
													'completion_percentage'			=>	0,
													'completion_percentage_width'	=>	0,
												),
			'template_config'				=>	$MyAgilePrivacyRegulationHelper->getTemplateConfig(),
			'regulations_selected'			=>	$MyAgilePrivacyRegulationHelper->getRegulationsSelected( true ),
		);


		$is_pa            = isset( $the_settings['pa'] ) && $the_settings['pa'] == 1;

		$dont_ask_license = isset( $the_settings['dont_ask_license_code'] ) && $the_settings['dont_ask_license_code'] && !$is_pa;
		$has_license_code = isset( $the_settings['license_code'] ) && $the_settings['license_code'] !== '';

		$enable_cmode     = isset( $the_settings['enable_cmode_v2'] ) && $the_settings['enable_cmode_v2'];
		$bypass_cmode     = isset( $the_settings['bypass_cmode_enable'] ) && $the_settings['bypass_cmode_enable'];

		$the_settingscan_mode             = isset( $the_settings['scan_mode'] ) ? $the_settings['scan_mode'] : null;
		$the_settingscan_is_learning      = $is_pa && $the_settingscan_mode === 'learning_mode';
		$the_settingscan_is_config_finish = $is_pa && $the_settingscan_mode === 'config_finished';
		$the_settingscan_is_turned_off    = $is_pa && $the_settingscan_mode === 'turned_off';


		$license_ok          = $is_pa && $has_license_code;
		$consent_mode_ok     = $is_pa && $enable_cmode;
		$consent_status_skip = $the_settingscan_is_learning || $the_settingscan_is_turned_off;
		$consent_status_ok   = $is_pa
			&& ( $the_settingscan_mode === 'config_finished' || $the_settingscan_mode === 'learning_mode' )
			&& ( $enable_cmode || $bypass_cmode );

		$policy_config        = isset( $site_and_policy_settings['completion_percentage'] ) &&
								$site_and_policy_settings['completion_percentage'] >= 100;

		$dashboard_checks = array(
			// license code status
			'license_code' => array(
				'is_skipped' => $dont_ask_license,
				'is_enabled' => $license_ok,
				'check'      => $license_ok,
				'do_count'   => true,
			),

			// consent mode status
			'consent_mode' => array(
				'is_skipped' => $bypass_cmode,
				'is_enabled' => $consent_mode_ok,
				'check'      => $consent_mode_ok,
				'do_count'   => false,
			),

			// cookie shield status
			'cookie_shield' => array(
				'is_skipped' => $the_settingscan_is_learning,
				'is_enabled' => $the_settingscan_is_config_finish,
				'check'      => $the_settingscan_is_config_finish,
				'do_count'   => false,
			),

			// consent status
			'consent_status' => array(
				'is_skipped' => $consent_status_skip,
				'check'      => $consent_status_ok,
				'do_count'   => true,
			),

			//policies
			'policy_config' => array(

				'is_enabled' => $policy_config,
				'check'		 =>	$policy_config,
				'do_count'   => true,

			),
		);

		$global_integrity_checks['dashboard_checks'] = $dashboard_checks;

		$dashboard_checks_count = 0;

		foreach( $global_integrity_checks['dashboard_checks'] as $key => &$item )
		{
			if( isset( $item['do_count'] ) && $item['do_count'] )
			{
				$dashboard_checks_count++;
			}

			switch( $key )
			{
				case 'policy_config':

						if( $item['check'] )
						{
							if( $caller == 'frontend' )
							{

								$item['status_class_name'] = 'success';
								$item['status_class_desc'] = wp_kses_post( __( 'Configuration complete', 'MAP_txt' ) );
							}

							if( isset( $item['do_count'] ) && $item['do_count'] )
							{
								$global_integrity_checks['dashboard_checks_passed_count'] = $global_integrity_checks['dashboard_checks_passed_count'] + 1;
							}
						}
						else
						{
							if( $caller == 'frontend' )
							{
								$item['status_class_name'] = 'danger';
								$item['status_class_desc'] = wp_kses_post( __( 'Needs attention', 'MAP_txt' ) );
							}

						}

					break;

				case 'consent_mode':
				case 'license_code':
				case 'cookie_shield':

					if( $item['is_skipped'] )
					{
						if( $caller == 'frontend' )
						{

							$item['status_class_name'] = 'warning';
							$item['status_class_desc'] = wp_kses_post( __( 'Needs attention', 'MAP_txt' ) );
						}
					}
					else
					{
						if( $item['check'] )
						{
							if( $caller == 'frontend' )
							{
								$item['status_class_name'] = 'success';
								$item['status_class_desc'] = wp_kses_post( __( 'Configuration complete', 'MAP_txt' ) );
							}

							if( isset( $item['do_count'] ) && $item['do_count'] )
							{
								$global_integrity_checks['dashboard_checks_passed_count'] = $global_integrity_checks['dashboard_checks_passed_count'] + 1;
							}
						}
						else
						{
							if( $caller == 'frontend' )
							{
								$item['status_class_name'] = 'danger';
								$item['status_class_desc'] = wp_kses_post( __( 'Needs attention', 'MAP_txt' ) );
							}
						}
					}

					break;

				/**/

				case 'consent_status':

					if( !$item['check'] )
					{
						if( $caller == 'frontend' )
						{
							$item['status_class_name'] = 'danger';
							$item['status_class_desc'] = wp_kses_post( __( 'Needs attention', 'MAP_txt' ) );
						}
					}
					else
					{
						if( $item['is_skipped'] )
						{
							if( $caller == 'frontend' )
							{
								$item['status_class_name'] = 'warning';
								$item['status_class_desc'] = wp_kses_post( __( 'Needs attention', 'MAP_txt' ) );
							}
						}
						else
						{
							if( $caller == 'frontend' )
							{
								$item['status_class_name'] = 'success';
								$item['status_class_desc'] = wp_kses_post( __( 'Configuration complete', 'MAP_txt' ) );
							}

							if( isset( $item['do_count'] ) && $item['do_count'] )
							{
								$global_integrity_checks['dashboard_checks_passed_count'] = $global_integrity_checks['dashboard_checks_passed_count'] + 1;
							}
						}
					}

					break;
				/**/

				default:
					if( $caller == 'frontend' )
					{
						$check['status_class_name'] = '';
						$item['status_class_desc'] = '';
					}
					break;
			}
		}

		$global_integrity_checks['dashboard_checks_count'] = $dashboard_checks_count;

		$global_integrity_checks['summary']['completion_percentage'] = intval( ( $global_integrity_checks['dashboard_checks_passed_count'] / $global_integrity_checks['dashboard_checks_count'] ) * 100 );
		$global_integrity_checks['summary']['completion_percentage_width'] = $global_integrity_checks['summary']['completion_percentage'];

		if( $global_integrity_checks['summary']['completion_percentage'] == 0 )
		{
			$global_integrity_checks['summary']['status_class_name'] = 'bg-danger';
			$global_integrity_checks['summary']['completion_percentage_width'] = 100;
		}

		$other_frontend_checks = array(
			//check identity information
			'identity' 		=> array(
				'check' 		=> (
											isset( $the_settings['identity_name'] ) &&
											$the_settings['identity_name'] &&
											isset( $the_settings['identity_address'] ) &&
											$the_settings['identity_address'] &&
											isset( $the_settings['identity_email'] ) &&
											$the_settings['identity_email']
									),
			),
			//bg colors and link colors dark patterns
			'not_using_dark_patterns'	=> array(
				'check' 		=> (
											// Check background colors
											isset( $the_settings['button_accept_button_color'] ) &&
											isset( $the_settings['button_reject_button_color'] ) &&
											isset( $the_settings['button_customize_button_color'] ) &&
											$the_settings['button_accept_button_color'] == $the_settings['button_reject_button_color'] &&
											$the_settings['button_reject_button_color'] == $the_settings['button_customize_button_color'] &&

											// Check link colors
											isset( $the_settings['button_accept_link_color'] ) &&
											isset( $the_settings['button_reject_link_color'] ) &&
											isset( $the_settings['button_customize_link_color'] ) &&
											$the_settings['button_accept_link_color'] == $the_settings['button_reject_link_color'] &&
											$the_settings['button_reject_link_color'] == $the_settings['button_customize_link_color']
										),
			),
		);

		$global_integrity_checks['other_frontend_checks'] = $other_frontend_checks;

		if( $caller == 'backend' )
		{
			/**
			 * The class for handling policies
			 */
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/my-agile-privacy-policies-helper.php';


			//find [myagileprivacy_fixed_text text="cookie_policy"]
			$pattern_cookie = '/\[\s*myagileprivacy_fixed_text\b(?=[^\]]*\btext=(["\'])cookie_policy\1)[^\]]*\]/i';

			$result_cookie_policy_page = MyAgilePrivacyPoliciesHelper::find_shortcode_in_pages(
			    'myagileprivacy_fixed_text',
			    array(
			        'custom_pattern' => $pattern_cookie
			    )
			);

			//find [myagileprivacy_fixed_text text="personal_data_policy"]
			$pattern_personal = '/\[\s*myagileprivacy_fixed_text\b(?=[^\]]*\btext=(["\'])personal_data_policy\1)[^\]]*\]/i';

			$result_personal_data_policy_page = MyAgilePrivacyPoliciesHelper::find_shortcode_in_pages(
			    'myagileprivacy_fixed_text',
			    array(
			        'custom_pattern' => $pattern_personal
			    )
			);

			$backend_checks = array(
				'cookie_policy_page'		=>	$result_cookie_policy_page,
				'personal_data_policy_page'	=>	$result_personal_data_policy_page,

			);

			$global_integrity_checks['backend_checks'] = $backend_checks;
		}

		return $global_integrity_checks;
	}


	//f for checking if to use alternative loading method, necessary for multilang plugin
	public static function use_alt_option_method()
	{
		if( !function_exists( 'is_plugin_active' ) )
		{
			include_once(ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		if( (
				defined( 'POLYLANG_FILE' ) &&
				function_exists( 'pll_default_language' ) &&
				function_exists( 'pll_languages_list' )
			) ||
			(
				is_plugin_active( 'wpml-string-translation/plugin.php' )
			) ||
			(
				function_exists( 'FALANG') && function_exists( 'falang_default_language' )
			)
		)
		{
			return true;
		}

		return false;
	}

	/**
	 * Whether the legacy mode is configured.
	 */
	public static function is_legacy_mode_configured( $the_settings, $rconfig )
	{
		if( is_array( $rconfig ) &&
			isset( $rconfig['js_legacy_mode'] ) &&
			self::looseEqualsPhp8( $rconfig['js_legacy_mode'], 1 ) )
		{
			return true;
		}

		return is_array( $the_settings ) &&
			!empty( $the_settings['scanner_compatibility_mode'] ) &&
			!empty( $the_settings['forced_legacy_mode'] );
	}

	/**
	 * Runtime state of the scan, kept apart from the settings.
	 */
	public static function get_scan_runtime()
	{
		$defaults = self::get_scan_runtime_defaults();

		$runtime = self::get_option( MAP_PLUGIN_SCAN_RUNTIME, false );

		if( !is_array( $runtime ) )
		{
			$runtime = array();
		}

		$runtime = array_intersect_key( $runtime, $defaults );

		// values saved inside the settings by earlier versions
		if( count( $runtime ) < count( $defaults ) )
		{
			$stored = self::get_option( MAP_PLUGIN_SETTINGS_FIELD, array() );

			if( is_array( $stored ) )
			{
				foreach( $defaults as $key => $value )
				{
					if( !array_key_exists( $key, $runtime ) && array_key_exists( $key, $stored ) )
					{
						$runtime[ $key ] = self::sanitise_settings( $key, $stored[ $key ] );
					}
				}
			}
		}

		return array_merge( $defaults, $runtime );
	}

	/**
	 * Default values of the scan runtime state.
	 */
	public static function get_scan_runtime_defaults()
	{
		return array(
			'missing_cookie_shield'					=>	false,
			'missing_cookie_shield_timestamp'		=>	null,
			'cookie_shield_running'					=>	false,
			'cookie_shield_running_timestamp'		=>	null,
			'cmode_v2_js_on_error'					=>	false,
			'cmode_v2_js_on_error_first_relevation'	=>	false,
			'cmode_v2_js_error_code'				=>	0,
			'cmode_v2_js_error_motivation'			=>	null,
			'last_scan_date_internal'				=>	null,
			'last_sync'								=>	null,
			'learning_mode_last_active_timestamp'	=>	null,
		);
	}

	/**
	 * Creates the scan runtime state once, from an administrator request.
	 */
	public static function seed_scan_runtime()
	{
		if( !self::map_can_edit() )
		{
			return;
		}

		$runtime = self::get_option( MAP_PLUGIN_SCAN_RUNTIME, false );

		if( is_array( $runtime ) && !array_diff_key( self::get_scan_runtime_defaults(), $runtime ) )
		{
			return;
		}

		self::update_option( MAP_PLUGIN_SCAN_RUNTIME, self::get_scan_runtime() );
	}

	/**
	 * Writes some keys of the scan runtime state.
	 */
	public static function update_scan_runtime( $changes )
	{
		// merge onto the stored copy, not the one loaded at the start of the request
		wp_cache_delete( MAP_PLUGIN_SCAN_RUNTIME, 'options' );
		wp_cache_delete( 'alloptions', 'options' );

		$runtime = self::get_scan_runtime();

		foreach( $changes as $key => $value )
		{
			$runtime[ $key ] = $value;
		}

		return self::update_option( MAP_PLUGIN_SCAN_RUNTIME, $runtime );
	}

	//f for getting data
	public static function get_option( $option_name, $default = false )
	{
		$use_alt_option_method = self::use_alt_option_method();

		if( !$use_alt_option_method )
		{
			return get_option( $option_name, $default );
		}
		else
		{
			global $wpdb;

			// Check the cache first
			$cached_value = wp_cache_get( $option_name, 'options' );

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
		// keeps the stored appearance out of any write made while a variant is served
		if( $option_name === MAP_PLUGIN_SETTINGS_FIELD && !empty( self::$banner_overrides ) && is_array( $new_value ) )
		{
			$stored = self::get_option( MAP_PLUGIN_SETTINGS_FIELD, array() );

			if( !is_array( $stored ) ) $stored = array();

			foreach( self::$banner_overrides as $override_key => $override_value )
			{
				if( array_key_exists( $override_key, $stored ) )
				{
					$new_value[ $override_key ] = $stored[ $override_key ];
				}
				else
				{
					unset( $new_value[ $override_key ] );
				}
			}
		}

		$use_alt_option_method = self::use_alt_option_method();

		if( !$use_alt_option_method )
		{
			return update_option( $option_name, $new_value );
		}
		else
		{
			global $wpdb;

			// Ensure the option name is not empty
			if( empty( $option_name ) )
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

				// the row may have been created between the count and the insert
				if( $result === false )
				{
					$result = $wpdb->update(
						$wpdb->options,
						array('option_value' => $serialized_value),
						array('option_name' => $option_name),
						array('%s'),
						array('%s')
					);

					// no row touched and none there: the insert failed for another reason
					if( $result === 0 && !$wpdb->get_var( $wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name = %s",
						$option_name
					) ) )
					{
						$result = false;
					}
				}

				if( $result === false )
				{
					return false;
				}
			}

			self::drop_option_cache( $option_name );

			return true;
		}
	}

	/**
	 * Drops the cached copies of an option written directly in the database.
	 */
	public static function drop_option_cache( $option_name )
	{
		// the core reads autoloaded options from 'alloptions', so both entries go
		wp_cache_delete( $option_name, 'options' );
		wp_cache_delete( 'alloptions', 'options' );

		// a name remembered as missing would hide a newly inserted option
		$notoptions = wp_cache_get( 'notoptions', 'options' );

		if( is_array( $notoptions ) && isset( $notoptions[ $option_name ] ) )
		{
			unset( $notoptions[ $option_name ] );
			wp_cache_set( 'notoptions', $notoptions, 'options' );
		}
	}

	/**
	 * Stored record of pages left unparsed, normalized; null when missing or malformed.
	 *
	 * @return array|null
	 */
	public static function get_html_scan_state()
	{
		if( !defined( 'MAP_PLUGIN_HTML_SCAN_STATE' ) )
		{
			return null;
		}

		$state = get_option( MAP_PLUGIN_HTML_SCAN_STATE, false );

		if( !is_array( $state ) ||
			!isset( $state['at'], $state['reason'] ) ||
			!is_string( $state['reason'] ) ||
			!in_array( $state['reason'], array( 'dimensione', 'tag', 'blocchi', 'tempo', 'memoria', 'regex', 'errore', 'senza-head' ), true ) )
		{
			return null;
		}

		$at = self::html_scan_int( $state['at'] );

		if( null === $at )
		{
			return null;
		}

		$first = ( isset( $state['first'] ) ) ? self::html_scan_int( $state['first'] ) : null;

		if( null === $first || $first > $at )
		{
			$first = $at;
		}

		$n = ( isset( $state['n'] ) ) ? self::html_scan_int( $state['n'] ) : null;

		if( null === $n || $n < 1 )
		{
			$n = 1;
		}

		$info = null;

		if( isset( $state['info'] ) &&
			is_string( $state['info'] ) &&
			preg_match( '/^[A-Za-z0-9_\\\\ .\-]{1,100}$/D', $state['info'] ) )
		{
			$info = $state['info'];
		}

		return array(
			'first'		=>	$first,
			'at'		=>	$at,
			'reason'	=>	$state['reason'],
			'info'		=>	$info,
			'n'			=>	min( $n, 1000000 ),
		);
	}

	/**
	 * Non-negative integer from an int or a digit string; null otherwise.
	 *
	 * @return int|null
	 */
	private static function html_scan_int( $value )
	{
		if( is_int( $value ) && $value >= 0 )
		{
			return $value;
		}

		if( is_string( $value ) && preg_match( '/^\d{1,10}$/D', $value ) )
		{
			return (int) $value;
		}

		return null;
	}

	/**
	 * Creates an empty remote configuration once, from an administrator request.
	 */
	public static function seed_rconfig()
	{
		global $wpdb;

		if( !self::map_can_edit() )
		{
			return;
		}

		// the alternative reader caches the default of an earlier call as if it were stored
		wp_cache_delete( MAP_PLUGIN_RCONFIG, 'options' );

		if( false !== self::get_option( MAP_PLUGIN_RCONFIG, false ) )
		{
			return;
		}

		// never overwrites a configuration that arrived meanwhile
		$wpdb->query( $wpdb->prepare(
			"INSERT IGNORE INTO {$wpdb->options} ( option_name, option_value, autoload ) VALUES ( %s, %s, 'yes' )",
			MAP_PLUGIN_RCONFIG,
			serialize( array() )
		) );

		self::drop_option_cache( MAP_PLUGIN_RCONFIG );
	}


	/**
	get final translation table
	*/
	public static function getFixedTranslations()
	{
		$the_settings = self::get_settings();

		$default_txt = MyAgilePrivacyLangHelper::getDefaultText();

		$final_txt = $default_txt;

		$fixed_translations = ( isset( $the_settings['fixed_translations_encoded'] ) && is_string( $the_settings['fixed_translations_encoded'] ) && $the_settings['fixed_translations_encoded'] ) ? json_decode( $the_settings['fixed_translations_encoded'], true ) : array();

		if( is_array( $fixed_translations ) && !empty( $fixed_translations ) )
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

	//f. for reading the subscription form texts, keyed by supported language (4 char); empty when unusable
	public static function get_email_consent_texts( $file = null )
	{
		$texts = array();

		if( !is_string( $file ) || $file === '' )
		{
			$file = dirname( __FILE__ ) . '/email-consent-texts-v5.json';
		}

		if( !function_exists( 'file_get_contents' ) || !is_file( $file ) || !is_readable( $file ) )
		{
			return $texts;
		}

		$raw = @file_get_contents( $file );

		if( !is_string( $raw ) || $raw === '' )
		{
			return $texts;
		}

		if( strncmp( $raw, "\xEF\xBB\xBF", 3 ) === 0 )
		{
			$raw = (string) substr( $raw, 3 );
		}

		$decoded = json_decode( $raw, true );

		if( !is_array( $decoded ) )
		{
			return $texts;
		}

		$fields = array( 'newsletter', 'tracking_title', 'tracking', 'tracking_clicks' );

		foreach( $decoded as $template_key => $item )
		{
			if( !is_string( $template_key ) || !is_array( $item ) )
			{
				continue;
			}

			$lang_2char = self::strictLangTo2Char( (string) substr( $template_key, 0, strcspn( $template_key, '_' ) ) );
			$lang_4char = $lang_2char ? self::translate2charTo4CharLangCode( $lang_2char ) : null;

			if( !$lang_4char || isset( $texts[ $lang_4char ] ) )
			{
				continue;
			}

			$entry = array();

			foreach( $fields as $field )
			{
				if( !isset( $item[ $field ] ) || !is_string( $item[ $field ] ) || trim( $item[ $field ] ) === '' )
				{
					$entry = null;
					break;
				}

				$entry[ $field ] = trim( $item[ $field ] );
			}

			if( $entry )
			{
				$texts[ $lang_4char ] = $entry;
			}
		}

		return $texts;
	}

	//f. for choosing the languages of the subscription form texts: site languages first (English in place of a missing one), then every other language of the file
	public static function get_email_consent_text_tabs( $texts, $site_languages )
	{
		$tabs = array();

		if( !is_array( $texts ) || empty( $texts ) )
		{
			return $tabs;
		}

		$supported = self::get_supported_languages();

		if( is_array( $site_languages ) )
		{
			foreach( $site_languages as $lang_code => $lang_data )
			{
				if( !is_string( $lang_code ) || !isset( $supported[ $lang_code ] ) )
				{
					continue;
				}

				if( isset( $texts[ $lang_code ] ) )
				{
					$tabs[ $lang_code ] = array( 'label' => $supported[ $lang_code ]['label'], 'texts' => $texts[ $lang_code ], 'fallback' => false, 'lang' => $supported[ $lang_code ]['2char'] );
				}
				elseif( isset( $texts['en_US'] ) )
				{
					$tabs[ $lang_code ] = array( 'label' => $supported[ $lang_code ]['label'], 'texts' => $texts['en_US'], 'fallback' => true, 'lang' => 'en' );
				}
			}
		}

		foreach( $texts as $lang_code => $entry )
		{
			if( is_string( $lang_code ) && isset( $supported[ $lang_code ] ) && !isset( $tabs[ $lang_code ] ) )
			{
				$tabs[ $lang_code ] = array( 'label' => $supported[ $lang_code ]['label'], 'texts' => $entry, 'fallback' => false, 'lang' => $supported[ $lang_code ]['2char'] );
			}
		}

		return $tabs;
	}

	//f for getting current lang (4 char)
	public static function getCurrentLang4Char()
	{
		$the_settings = self::get_settings();

		$currentAndSupportedLanguages = self::getCurrentAndSupportedLanguages();

		if( $currentAndSupportedLanguages['with_multilang'] )
		{
			//2 char version
			$current_language_2char = $currentAndSupportedLanguages['current_language'];

			//4 char version
			$current_lang = self::translate2charTo4CharLangCode( $current_language_2char );

			if( !$current_lang &&
				isset( $the_settings ) &&
				isset( $the_settings['enable_language_fallback'] ) &&
				$the_settings['enable_language_fallback'] &&
				isset( $the_settings['language_fallback_locale'] ) &&
				$the_settings['language_fallback_locale']
			)
			{
				$current_lang = $the_settings['language_fallback_locale'];
			}

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
			$current_lang = $the_settings['default_locale'];
		}

		//always return a canonical 4char key (fallback en_US) so every downstream
		//the_translations lookup and JS config stays safe
		$current_lang_normalized = self::normalizeLocaleTo4Char( $current_lang );
		$current_lang = $current_lang_normalized ? $current_lang_normalized : 'en_US';

		return $current_lang;
	}

	//f for translating 2 to 4 char lang code
	public static function translate2charTo4CharLangCode( $lang = 'en' )
	{
		foreach( MyAgilePrivacy::get_supported_languages() as $lang_key => $lang_value )
		{
			if( $lang_value['2char'] == $lang )
			{
				return $lang_key;
			}
		}

		return null;;
	}

	//f for normalizing any locale string to a supported canonical 4char key (or null)
	public static function normalizeLocaleTo4Char( $locale )
	{
		if( !is_string( $locale ) || $locale === '' )
		{
			return null;
		}

		//local copy: isset() is not allowed on a function-call array offset under PHP 5.6
		$supported = MyAgilePrivacy::get_supported_languages();

		// (a) exact match as a canonical key (de_DE, en_US, el, fi, hr, uk...)
		if( isset( $supported[ $locale ] ) )
		{
			return $locale;
		}

		// (b) strip _formal / _informal suffix and retry exact match (de_DE_formal -> de_DE)
		$stripped = preg_replace( '/_(formal|informal)$/', '', $locale );
		if( $stripped !== $locale && isset( $supported[ $stripped ] ) )
		{
			return $stripped;
		}

		// (c) language part before the first "_" or "-" matched against the 2char codes
		$cut = strcspn( $stripped, '_-' );
		$prefix = substr( $stripped, 0, $cut );
		$by_prefix = self::translate2charTo4CharLangCode( $prefix );
		if( $by_prefix )
		{
			return $by_prefix;
		}

		// (d) first 2 chars matched against the 2char codes (safety net)
		$by_two = self::translate2charTo4CharLangCode( substr( $stripped, 0, 2 ) );
		if( $by_two )
		{
			return $by_two;
		}

		// (e) nothing matched
		return null;
	}

	//f for normalizing any locale string to a supported 2char code (or null)
	public static function normalizeLocaleTo2Char( $locale )
	{
		$four = self::normalizeLocaleTo4Char( $locale );

		if( !$four )
		{
			return null;
		}

		$supported = MyAgilePrivacy::get_supported_languages();
		return $supported[ $four ][ '2char' ];
	}

	//f for matching a language code or locale to a supported 2char code: exact key or language part only (or null)
	public static function strictLangTo2Char( $value )
	{
		if( !is_string( $value ) || $value === '' )
		{
			return null;
		}

		$upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$lower = 'abcdefghijklmnopqrstuvwxyz';

		$value = strtr( $value, $upper, $lower );

		$supported = MyAgilePrivacy::get_supported_languages();

		foreach( $supported as $lang_key => $lang_value )
		{
			if( strtr( $lang_key, $upper, $lower ) === $value )
			{
				return $lang_value['2char'];
			}
		}

		$prefix = (string) substr( $value, 0, strcspn( $value, '-_@' ) );

		if( $prefix === 'nb' || $prefix === 'nn' )
		{
			$prefix = 'no';
		}

		foreach( $supported as $lang_key => $lang_value )
		{
			if( $lang_value['2char'] === $prefix )
			{
				return $prefix;
			}
		}

		return null;
	}

	//f for resolving a multilang plugin language code to a supported 2char code, the locale first (or the code unchanged)
	public static function resolveMultilangCode( $code, $locale = null )
	{
		if( !is_string( $code ) || $code === '' )
		{
			return $code;
		}

		$by_locale = self::strictLangTo2Char( $locale );

		if( $by_locale )
		{
			return $by_locale;
		}

		$by_code = self::strictLangTo2Char( $code );

		if( $by_code )
		{
			return $by_code;
		}

		return $code;
	}

	//f for getting the locale WPML declares for one of its language codes (or null)
	public static function wpml_language_locale( $code )
	{
		global $sitepress;

		if( !is_string( $code ) ||
			$code === '' ||
			!is_object( $sitepress ) ||
			!method_exists( $sitepress, 'get_locale' ) )
		{
			return null;
		}

		$locale = $sitepress->get_locale( $code );

		return is_string( $locale ) ? $locale : null;
	}

	//f for resolving a country iso2 code to its display name for the given locale
	public static function localize_country_name( $iso2, $locale )
	{
		static $localized_countries = null;
		static $available_countries = null;

		if( !is_string( $iso2 ) || $iso2 === '' )
		{
			return '';
		}

		$iso2 = strtolower( $iso2 );

		if( null === $localized_countries )
		{
			$localized_countries = self::get_option( MAP_PLUGIN_LOCALIZED_COUNTRIES, array() );

			if( !is_array( $localized_countries ) )
			{
				$localized_countries = array();
			}
		}

		$lang_2_char = self::normalizeLocaleTo2Char( $locale );

		if( !$lang_2_char )
		{
			$lang_2_char = 'en';
		}

		if( isset( $localized_countries[ $lang_2_char ][ $iso2 ] ) &&
			is_string( $localized_countries[ $lang_2_char ][ $iso2 ] ) &&
			$localized_countries[ $lang_2_char ][ $iso2 ] !== '' )
		{
			return $localized_countries[ $lang_2_char ][ $iso2 ];
		}

		if( isset( $localized_countries[ 'en' ][ $iso2 ] ) &&
			is_string( $localized_countries[ 'en' ][ $iso2 ] ) &&
			$localized_countries[ 'en' ][ $iso2 ] !== '' )
		{
			return $localized_countries[ 'en' ][ $iso2 ];
		}

		if( null === $available_countries )
		{
			$available_countries = self::get_option( MAP_PLUGIN_COUNTRIES, array() );

			if( !is_array( $available_countries ) )
			{
				$available_countries = array();
			}
		}

		if( isset( $available_countries[ 'not_adequate' ][ $iso2 ] ) &&
			is_string( $available_countries[ 'not_adequate' ][ $iso2 ] ) )
		{
			return $available_countries[ 'not_adequate' ][ $iso2 ];
		}

		if( isset( $available_countries[ 'all_supported_countries' ][ $iso2 ] ) &&
			is_string( $available_countries[ 'all_supported_countries' ][ $iso2 ] ) )
		{
			return $available_countries[ 'all_supported_countries' ][ $iso2 ];
		}

		return '';
	}

	//f for returning current and supported languages
	public static function getCurrentAndSupportedLanguages()
	{
		global $locale;
		global $sitepress;

		$the_settings = self::get_settings();

		$MAP_SUPPORTED_LANGUAGES = MyAgilePrivacy::get_supported_languages();

		$return_data = array(
			'with_multilang'				=>	false,
			'is_wpml_enabled'				=>	false,
			'is_polylang_enabled'			=>	false,
			'is_translatepress_enabled'		=> 	false,
			'is_weglot_enabled'				=>	false,
			'is_falang_enabled'				=>	false,
			'language_list_codes'			=>	null,
			'multilang_language_slugs'		=>	null,
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
			$wpml_current_lang = defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE : $multilang_default_lang;

			$language_list = icl_get_languages();

			if( !is_array( $language_list ) )
			{
				$language_list = array();
			}

			$language_list_codes = array();
			$language_list_slugs = array();

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

				$language_list_slugs[] = $the_language_code;
				$language_list_codes[] = self::resolveMultilangCode( $the_language_code, self::wpml_language_locale( $the_language_code ) );
			}

			$return_data['language_list_codes'] = array_values( array_unique( $language_list_codes ) );
			$return_data['multilang_language_slugs'] = $language_list_slugs;
			$return_data['current_language'] = self::resolveMultilangCode( $wpml_current_lang, self::wpml_language_locale( $wpml_current_lang ) );
			$return_data['multilang_default_lang'] = self::resolveMultilangCode( $multilang_default_lang, self::wpml_language_locale( $multilang_default_lang ) );
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
			$current_language = pll_current_language();

			$language_locales = array();
			$language_list_slugs = array();
			$language_list = pll_languages_list( array( 'fields' => '' ) );

			if( !is_array( $language_list ) )
			{
				$language_list = array();
			}

			foreach( $language_list as $k => $v )
			{
				if( !is_object( $v ) || !isset( $v->slug ) || !is_string( $v->slug ) )
				{
					continue;
				}

				$language_list_slugs[] = $v->slug;
				$language_locales[ $v->slug ] = ( isset( $v->locale ) && is_string( $v->locale ) ) ? $v->locale : null;
			}

			if( empty( $language_list_slugs ) )
			{
				$language_list_slugs = pll_languages_list();
			}

			if( !is_array( $language_list_slugs ) )
			{
				$language_list_slugs = array();
			}

			$language_list_codes = array();

			foreach( $language_list_slugs as $k => $v )
			{
				$language_list_codes[] = self::resolveMultilangCode( $v, ( is_string( $v ) && isset( $language_locales[ $v ] ) ) ? $language_locales[ $v ] : null );
			}

			if( is_string( $current_language ) && isset( $language_locales[ $current_language ] ) )
			{
				$current_locale = $language_locales[ $current_language ];
			}
			else
			{
				$current_locale = pll_current_language( 'locale' );
			}

			if( is_string( $multilang_default_lang ) && isset( $language_locales[ $multilang_default_lang ] ) )
			{
				$default_locale = $language_locales[ $multilang_default_lang ];
			}
			else
			{
				$default_locale = pll_default_language( 'locale' );
			}

			$return_data['language_list_codes'] = array_values( array_unique( $language_list_codes ) );
			$return_data['multilang_language_slugs'] = $language_list_slugs;
			$return_data['current_language'] = self::resolveMultilangCode( $current_language, $current_locale );
			$return_data['multilang_default_lang'] = self::resolveMultilangCode( $multilang_default_lang, $default_locale );
		}

		//Falang
		if( function_exists( 'FALANG' ) && function_exists( 'falang_default_language' ) )
		{
			$return_data['is_falang_enabled'] = true;
			$return_data['with_multilang'] = true;

			$multilang_default_lang = falang_default_language();
			$current_language = falang_current_language();

			$language_list_codes = array();
			$language_list_slugs = array();
			$language_locales = array();
			$language_list = FALANG()->model->get_languages_list();

			if( !is_array( $language_list ) )
			{
				$language_list = array();
			}

			foreach( $language_list as $k => $v )
			{
				if( !is_object( $v ) )
				{
					continue;
				}

				$the_language_code = $v->slug;

				if( !is_string( $the_language_code ) )
				{
					continue;
				}

				$the_language_locale = ( isset( $v->locale ) && is_string( $v->locale ) ) ? $v->locale : null;

				$language_list_slugs[] = $the_language_code;
				$language_locales[ $the_language_code ] = $the_language_locale;
				$language_list_codes[] = self::resolveMultilangCode( $the_language_code, $the_language_locale );
			}

			if( is_string( $current_language ) && isset( $language_locales[ $current_language ] ) )
			{
				$current_locale = $language_locales[ $current_language ];
			}
			else
			{
				$current_locale = falang_current_language( 'locale' );
			}

			if( is_string( $multilang_default_lang ) && isset( $language_locales[ $multilang_default_lang ] ) )
			{
				$default_locale = $language_locales[ $multilang_default_lang ];
			}
			else
			{
				$default_locale = falang_default_language( 'locale' );
			}

			$return_data['language_list_codes'] = array_values( array_unique( $language_list_codes ) );
			$return_data['multilang_language_slugs'] = $language_list_slugs;
			$return_data['current_language'] = self::resolveMultilangCode( $current_language, $current_locale );
			$return_data['multilang_default_lang'] = self::resolveMultilangCode( $multilang_default_lang, $default_locale );
		}

		//TranslatePress
		if( defined( 'TRP_PLUGIN_VERSION' ) )
		{
			$return_data['is_translatepress_enabled'] = true;
			$return_data['with_multilang'] = true;

			$trp_settings = get_option( 'trp_settings', array() );
			$multilang_default_lang = isset( $trp_settings['default-language']) ? $trp_settings['default-language'] : null;
			$current_language = function_exists( 'trp_get_current_language' ) ? trp_get_current_language() : get_locale();

			//Norsk fix
			if( $current_language == 'nn_NO' )
			{
				$current_language = 'nb_NO';
			}

			//Latvian fix
			if( $current_language == 'lv' )
			{
				$current_language = 'lv_LV';
			}

			//Albanian fix
			if( $current_language == 'sq' )
			{
				$current_language = 'sq_AL';
			}

			$language_list = isset( $trp_settings['translation-languages'] ) ? $trp_settings['translation-languages'] : array();
			$language_list_codes = array();

			foreach( $language_list as $k => $v )
			{
				$the_language_code = substr( $v, 0, 2 );

				//Norsk fix
				if( $the_language_code == 'nn' )
				{
					$the_language_code = 'no';
				}

				$language_list_codes[] = $the_language_code;
			}

			$return_data['language_list_codes'] = $language_list_codes;
			$return_data['current_language'] = self::normalizeLocaleTo2Char( $current_language );
			$return_data['multilang_default_lang'] = self::normalizeLocaleTo2Char( $multilang_default_lang );
		}

		//Weglot
		if( class_exists('Weglot\Parser\Parser') )
		{
			$return_data['is_weglot_enabled'] = true;
			$return_data['with_multilang'] = true;

			$weglot_options = weglot_get_options();

			$language_list_codes = array();

			if( isset( $weglot_options['original_language'] ) )
			{
				$language_list_codes[] = $weglot_options['original_language'];
			}

			if( isset( $weglot_options['destination_language'] ) && is_array( $weglot_options['destination_language'] ) )
			{
				foreach( $weglot_options['destination_language'] as $k => $v )
				{
					if( !is_array( $v ) || !isset( $v['language_to'] ) )
					{
						continue;
					}

					$the_language_code = $v['language_to'];
					$language_list_codes[] = $the_language_code;
				}
			}

			$multilang_default_lang = isset( $weglot_options['original_language'] ) ? $weglot_options['original_language'] : null;
			$current_language = function_exists( 'weglot_get_current_language' ) ? weglot_get_current_language() : get_locale();

			$return_data['language_list_codes'] = $language_list_codes;
			$return_data['current_language'] = self::normalizeLocaleTo2Char( $current_language );
			$return_data['multilang_default_lang'] = self::normalizeLocaleTo2Char( $multilang_default_lang );
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

			$website_l_allowed = array(
			 self::normalizeLocaleTo2Char( $the_settings['default_locale'] )
			);

			//check $return_data['current_language']
			$website_locale_2_char = substr( (string) $return_data['current_language'], 0, 2 );

			foreach( $MAP_SUPPORTED_LANGUAGES as $lang_code => $lang_data )
			{
				$lang_code_2char = $lang_data['2char'];

				if( $website_locale_2_char == $lang_data['2char'] )
				{
					$return_data['current_language'] = $lang_code;
				}
			}

			$website_l_allowed[] = $website_locale_2_char;

			if( isset( $the_settings['enable_language_fallback'] ) &&
				$the_settings['enable_language_fallback'] &&
				isset( $the_settings['language_fallback_locale'] ) &&
				$the_settings['language_fallback_locale'] )
			{
				$website_l_allowed[] = self::normalizeLocaleTo2Char( $the_settings['language_fallback_locale'] );
			}

			$website_l_allowed = array_unique( $website_l_allowed );
		}

		$l_allowed = MyAgilePrivacy::get_option( MAP_PLUGIN_L_ALLOWED, array() );

		uasort( $MAP_SUPPORTED_LANGUAGES, function( $a, $b )
		{
		    return strcasecmp( $a['en_label'], $b['en_label'] );
		});

		foreach( $MAP_SUPPORTED_LANGUAGES as $lang_code => $lang_data )
		{
			$lang_code_2char = $lang_data['2char'];

			if( is_array( $website_l_allowed ) &&
				!in_array( $lang_code_2char, $website_l_allowed ) )
			{
				continue;
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

	// Strips ONLY the not-selected regulation-family blocks (map_<regulation>_text + aggregates)
	// from a raw policy source, leaving tokens and every other conditional block intact.
	// Uses the same booleans/regex as the compile; the removed blocks match the preview. Idempotent.
	public static function internalMAPStripUnselectedRegulations( $text )
	{
		if( ! is_string( $text ) || $text === '' )
		{
			return $text;
		}

		$MyAgilePrivacyRegulationHelper = new MyAgilePrivacyRegulationHelper();
		$templateConfig = $MyAgilePrivacyRegulationHelper->getTemplateConfig();
		$regulationList = $MyAgilePrivacyRegulationHelper->getRegulationList();

		if( ! is_array( $templateConfig ) )
		{
			return $text;
		}

		$keys_to_check = array();

		if( is_array( $regulationList ) )
		{
			foreach( $regulationList as $regulation )
			{
				$keys_to_check[] = 'map_'.$regulation.'_text';
			}
		}

		$keys_to_check[] = 'map_any_gdpr_like_text';
		$keys_to_check[] = 'map_any_gdpr_like_text_off';
		$keys_to_check[] = 'map_any_usa_like_text';
		$keys_to_check[] = 'map_any_usa_text';

		foreach( $keys_to_check as $key )
		{
			if( ! isset( $templateConfig[ $key ] ) || $templateConfig[ $key ] )
			{
				continue;
			}

			$patterns = array(
				'#(<p class="'.$key.'">).*?(</p>)#s',
				'#(<span class="'.$key.'">).*?(</span>)#s',
				'#(<ul class="'.$key.'">).*?(</ul>)#s',
				'#(<li class="'.$key.'">).*?(</li>)#s',
				'#(<strong class="'.$key.'">).*?(</strong>)#s',
			);

			foreach( $patterns as $pattern )
			{
				$replaced = preg_replace( $pattern, '', $text );

				if( null !== $replaced )
				{
					$text = $replaced;
				}
			}
		}

		return $text;
	}

	//f. for text cleanup & compile
	public static function internalMAPCleanupAndCompilePolicy( $text, $the_translations, $current_lang, $caller, $cookie_list_html )
	{
		if( !is_string( $current_lang ) )
		{
			$current_lang = '';
		}

		$the_settings = MyAgilePrivacy::get_settings();
		$rconfig = MyAgilePrivacy::get_rconfig();
		$MyAgilePrivacyRegulationHelper = new MyAgilePrivacyRegulationHelper();
		$site_and_policy_settings = $MyAgilePrivacyRegulationHelper->getSiteAndPolicySettings();
		$templateConfig = $MyAgilePrivacyRegulationHelper->getTemplateConfig( $current_lang );
		$regulationList = $MyAgilePrivacyRegulationHelper->getRegulationList();
		$availableCountries = $MyAgilePrivacyRegulationHelper->getAvailableCountries();


		if( isset( $rconfig ) &&
			isset( $rconfig['expired_60_days'] ) &&
			$rconfig['expired_60_days'] == 1
		)
		{
			$the_url = '#';

			if( defined( 'MAP_EXPIRED_CALLBACK_URL_DEFAULT' ) )
			{
				$the_url = ( $current_lang === 'it_IT' ) ? MAP_EXPIRED_CALLBACK_URL_IT : MAP_EXPIRED_CALLBACK_URL_DEFAULT;
			}


			$text = '<p>' . __( 'Some features of My Agile Privacy® are currently unavailable. Please check your license to restore full functionality.', 'MAP_txt' ) . '<br><a href="' . esc_url( $the_url ) . '" target="blank">' . __( 'Reactivate now', 'MAP_txt' ) . '</a></p>';

			return $text;
		}

		if( !is_array( $templateConfig ) )
		{
			$templateConfig = array();
		}

		$set_list = array();
		$unset_list = array();
		$processed_keys = array();

		$debug_mode = false;

		$empty_text = '';

		$remove_dpo_text = true;
		$remove_dpo_other_text = true;

		$website_name = get_site_url();

		$base_location = null;

		if( isset( $site_and_policy_settings ) &&
			isset( $site_and_policy_settings['base_location'] )
		)
		{
			$base_location = $site_and_policy_settings['base_location'];
		}

		if( isset( $the_settings['website_name'] ) &&
			$the_settings['website_name'] != '' )
		{
			$website_name = stripslashes( $the_settings['website_name'] );

			//block_the_content_filter mode
			if(
				( isset( $rconfig ) &&
				isset( $rconfig['block_the_content_filter'] ) &&
				$rconfig['block_the_content_filter'] == 1 ) ||
				(
					isset( $the_settings ) &&
					isset( $the_settings['scanner_compatibility_mode'] ) &&
					$the_settings['scanner_compatibility_mode']
				)
			)
			{
				//no action
			}
			else
			{
				//add paragraphs to text
				$website_name = strip_shortcodes( $website_name );
				$website_name = apply_filters( 'the_content', $website_name );
			}
		}

		$text = str_replace( '[website_name]', $website_name, $text );

		$text = str_replace( '[identity_vat_id]', ( isset( $templateConfig['map_identity_vat_id'] ) && $templateConfig['map_identity_vat_id'] ) ? esc_html( isset( $the_translations[ $current_lang ]['vat_id'] ) ? $the_translations[ $current_lang ]['vat_id'] : '' ).': '.wp_kses( stripslashes( $templateConfig['map_identity_vat_id'] ), self::allowed_identity_tags(), self::allowed_protocols() ) : '', $text );

		$processed_keys[] = 'shortcode_identity_vat_id';

		if( is_array( $rconfig ) && isset( $rconfig['allow_dpo_edit'] ) && $rconfig['allow_dpo_edit'] &&
			isset( $templateConfig['map_dpo_text'] ) && $templateConfig['map_dpo_text'] == 1 )
		{
			$remove_dpo_text = false;

			if(
				( isset( $templateConfig['shortcode_dpo_email'] ) && $templateConfig['shortcode_dpo_email'] ) ||
				( isset( $templateConfig['shortcode_dpo_name'] ) && $templateConfig['shortcode_dpo_name'] ) ||
				( isset( $templateConfig['shortcode_dpo_address'] ) && $templateConfig['shortcode_dpo_address'] )

			)
			{
				$remove_dpo_other_text = false;
			}
		}

		if( !$templateConfig['map_using_newsletter']  )
		{
			$text = preg_replace( '#(<p class="map_marketing_consent">).*?(</p>)#s', '' , $text );
			$text = preg_replace( '#(<span class="map_marketing_consent">).*?(</span>)#s', '' , $text );
			$text = preg_replace( '#(<ul class="map_marketing_consent">).*?(</ul>)#s', '' , $text );
			$text = preg_replace( '#(<li class="map_marketing_consent">).*?(</li>)#s', '' , $text );
			$text = preg_replace( '#(<strong class="map_marketing_consent">).*?(</strong>)#s', '' , $text );
		}

		if( $remove_dpo_text )
		{
			$text = preg_replace( '#(<p class="map_dpo_text">).*?(</p>)#s', '' , $text );
			$text = preg_replace( '#(<span class="map_dpo_text">).*?(</span>)#s', '' , $text );
			$text = preg_replace( '#(<ul class="map_dpo_text">).*?(</ul>)#s', '' , $text );
			$text = preg_replace( '#(<li class="map_dpo_text">).*?(</li>)#s', '' , $text );
			$text = preg_replace( '#(<strong class="map_dpo_text">).*?(</strong>)#s', '' , $text );
		}

		if( $remove_dpo_other_text )
		{
			$text = preg_replace( '#(<p class="map_dpo_other_text">).*?(</p>)#s', '' , $text );
			$text = preg_replace( '#(<span class="map_dpo_other_text">).*?(</span>)#s', '' , $text );
			$text = preg_replace( '#(<ul class="map_dpo_other_text">).*?(</ul>)#s', '' , $text );
			$text = preg_replace( '#(<li class="map_dpo_other_text">).*?(</li>)#s', '' , $text );
			$text = preg_replace( '#(<strong class="map_dpo_other_text">).*?(</strong>)#s', '' , $text );
		}
		else
		{
			if( $templateConfig['shortcode_dpo_email'] )
			{
				$text = str_replace( 'MAP_DPO_MAIL', wp_kses( stripslashes( $templateConfig['shortcode_dpo_email'] ), self::allowed_identity_tags(), self::allowed_protocols() ), $text );
			}
			else
			{
				$text = str_replace( 'MAP_DPO_MAIL<br>', '', $text );
			}

			$processed_keys[] = 'shortcode_dpo_email';

			if( $templateConfig['shortcode_dpo_name'] )
			{
				$text = str_replace( 'MAP_DPO_NAME', wp_kses( stripslashes( $templateConfig['shortcode_dpo_name'] ), self::allowed_identity_tags(), self::allowed_protocols() ), $text );
			}
			else
			{
				$text = str_replace( 'MAP_DPO_NAME<br>', '', $text );
			}

			$processed_keys[] = 'shortcode_dpo_name';

			if( $templateConfig['shortcode_dpo_address'] )
			{
				$text = str_replace( 'MAP_DPO_ADDRESS', wp_kses( stripslashes( $templateConfig['shortcode_dpo_address'] ), self::allowed_identity_tags(), self::allowed_protocols() ), $text );
			}
			else
			{
				$text = str_replace( 'MAP_DPO_ADDRESS<br>', '', $text );
			}

			$processed_keys[] = 'shortcode_dpo_address';
		}

		//base location specific texts
		if(
			isset( $availableCountries ) &&
			isset( $availableCountries['all_supported_countries'] )
		)
		{
			foreach( $availableCountries['all_supported_countries'] as $countryKey => $countryDescription )
			{
				$key = 'map_'.$countryKey.'_base_location';

				$processed_keys[] = $key;

				if( $countryKey !== $base_location )
				{
					if( $debug_mode ) $empty_text = strtoupper( '['.$key.']' );

					$text = preg_replace( '#(<p class="'.$key.'">).*?(</p>)#s', $empty_text , $text );
					$text = preg_replace( '#(<span class="'.$key.'">).*?(</span>)#s', $empty_text , $text );
					$text = preg_replace( '#(<ul class="'.$key.'">).*?(</ul>)#s', $empty_text , $text );
					$text = preg_replace( '#(<li class="'.$key.'">).*?(</li>)#s', $empty_text , $text );
					$text = preg_replace( '#(<strong class="'.$key.'">).*?(</strong>)#s', $empty_text , $text );

					$unset_list[] = $key;
				}
				else
				{
					$set_list[] = $key;
				}
			}
		}

		foreach( $regulationList as $regulation )
		{
			$key = 'map_'.$regulation.'_text';

			$processed_keys[] = $key;

			if( !$templateConfig[ $key ] )
			{
				if( $debug_mode ) $empty_text = strtoupper( '['.$key.']' );

				$text = preg_replace( '#(<p class="'.$key.'">).*?(</p>)#s', $empty_text , $text );
				$text = preg_replace( '#(<span class="'.$key.'">).*?(</span>)#s', $empty_text , $text );
				$text = preg_replace( '#(<ul class="'.$key.'">).*?(</ul>)#s', $empty_text , $text );
				$text = preg_replace( '#(<li class="'.$key.'">).*?(</li>)#s', $empty_text , $text );
				$text = preg_replace( '#(<strong class="'.$key.'">).*?(</strong>)#s', $empty_text , $text );

				$unset_list[] = $key;
			}
			else
			{
				$set_list[] = $key;
			}
		}

		foreach( $templateConfig as $templateConfigKey => $templateConfigItem )
		{
			if(  !in_array( $templateConfigKey, $processed_keys ) )
			{
				$processed_keys[] = $templateConfigKey;

				//css class
				if( strncmp( $templateConfigKey, 'map_', strlen( 'map_' ) ) === 0 )
				{
					if( !$templateConfigItem )
					{
						if( $debug_mode ) $empty_text = strtoupper( '['.$templateConfigKey.']' );

						$text = preg_replace( '#(<p class="'.$templateConfigKey.'">).*?(</p>)#s', $empty_text , $text );
						$text = preg_replace( '#(<span class="'.$templateConfigKey.'">).*?(</span>)#s', $empty_text , $text );
						$text = preg_replace( '#(<ul class="'.$templateConfigKey.'">).*?(</ul>)#s', $empty_text , $text );
						$text = preg_replace( '#(<li class="'.$templateConfigKey.'">).*?(</li>)#s', $empty_text , $text );
						$text = preg_replace( '#(<strong class="'.$templateConfigKey.'">).*?(</strong>)#s', $empty_text , $text );

						$unset_list[] = $templateConfigKey;
					}
					else
					{
						$set_list[] = $templateConfigKey;
					}
				}

				//shortcodes
				if( strncmp( $templateConfigKey, 'shortcode_', strlen( 'shortcode_' ) ) === 0 )
				{
					$cleaned_string = preg_replace('/^shortcode_/i', '', $templateConfigKey );
					$shortcode_to_search = '['.$cleaned_string.']';

					$processed_keys[] = $templateConfigKey;

					if( isset( $templateConfig[ $templateConfigKey ] ) &&
						is_string( $templateConfig[ $templateConfigKey ] ) )
					{
						$text = str_replace( $shortcode_to_search, wp_kses( stripslashes( $templateConfig[ $templateConfigKey ] ), self::allowed_identity_tags(), self::allowed_protocols() ), $text );

						$set_list[] = $templateConfigKey;
					}
					else
					{
						if( $debug_mode ) $empty_text = strtoupper( '['.$templateConfigKey.']' );

						$text = str_replace( $shortcode_to_search, $empty_text, $text );

						$unset_list[] = $templateConfigKey;
					}
				}
			}
		}

		$processed_keys[] = 'shortcode_identity_name';

		$text = str_replace( '[identity_address]', esc_html( stripslashes( (string) $templateConfig['shortcode_identity_address'] ) ), $text );
		$processed_keys[] = 'shortcode_identity_address';

		$text = str_replace( '[identity_email]', esc_html( stripslashes( (string) $templateConfig['shortcode_identity_email'] ) ), $text );
		$processed_keys[] = 'shortcode_identity_email';

		if( $caller == 'frontend' )
		{
			$text = str_replace( '[cookie_list]', $cookie_list_html, $text );

			if( $the_settings['wrap_shortcodes'] )
			{
				$text = '<div id="myagileprivacy_text_wrapper" class="myagileprivacy_text_wrapper">'.$text.'</div>';
			}

			$text = '<!--googleoff: all-->'.$text.'<!--googleon: all-->';

		}

		return $text;
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
			$sql1 = $wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}icl_translations
				 WHERE element_id IN (
					 SELECT ID
					 FROM {$wpdb->posts}
					 WHERE post_type = %s
				 )
				 AND element_type = %s",
				$post_type_escaped,
				$element_type
			);
			$wpdb->query( $sql1 );

			// Delete translation posts
			$sql2 = $wpdb->prepare(
				"DELETE FROM {$wpdb->posts}
				 WHERE ID IN (
					 SELECT translation_id
					 FROM {$wpdb->prefix}icl_translations
					 WHERE element_id IN (
						 SELECT id FROM (
							 SELECT ID
							 FROM {$wpdb->posts}
							 WHERE post_type = %s
						 ) AS tmp
					 )
				 )",
				$post_type_escaped
			);
			$wpdb->query( $sql2 );
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
					AND element_type = %s;
				", $to_clean_post_type, 'post_' . $to_clean_post_type)
			);
		}

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) self::write_log( 'stop dropWPMLTranslations' );
	}

	//db migration patch 3
	public static function dbMigrateDbasePatch3()
	{
		$the_settings = self::get_settings();

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $the_settings );

		if(
			isset( $the_settings['disable_logo'] ) && $the_settings['disable_logo']
		)
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'executing patch 3' );

			$the_settings['disable_logo'] = false;
			$the_settings['consent_widget_style'] = 'text_only';

			MyAgilePrivacy::update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_settings );
		}

		return true;
	}


	//db migration patch 2
	public static function dbMigrateDbasePatch2()
	{
		$the_settings = self::get_settings();

		$raw_settings = self::get_option( MAP_PLUGIN_SETTINGS_FIELD, array() );

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $raw_settings );

		if(
			isset( $raw_settings['identity_name'] ) ||
			isset( $raw_settings['identity_vat_id'] )
		)
		{
			$display_ccpa = false;

			if( isset( $raw_settings['display_ccpa'] ) && $raw_settings['display_ccpa']  )
			{
				$display_ccpa = true;
			}

			$display_lpd = false;

			if( isset( $raw_settings['display_lpd'] ) && $raw_settings['display_lpd']  )
			{
				$display_lpd = true;
			}

			$newsletter = false;

			//check if exists
			$cc_args = array(
				'posts_per_page'   => 	-1,
				'post_type'        =>	MAP_POST_TYPE_POLICY,
				'meta_key'         => 	'_map_remote_id',
				'meta_value'       => 	'personal_data_policy',
			);

			$cc_query = new WP_Query( $cc_args );

			if( $cc_query->have_posts() )
			{
				foreach ( $cc_query->get_posts() as $p )
				{
					$the_id = $p->ID;
					$map_option1_ack = get_post_meta( $the_id, '_map_option1_ack', true );
					$map_option1_on = get_post_meta( $the_id, '_map_option1_on', true );

					if( $map_option1_ack == 1 && $map_option1_on == 1 )
					{
						$newsletter = true;
					}
				}
			}

			$to_migrate_settings = array(
				'identity_name'					=> 	( isset( $raw_settings['identity_name'] ) )
													? $raw_settings['identity_name']
													: null,
				'identity_address'				=> 	( isset( $raw_settings['identity_address'] ) )
													? $raw_settings['identity_address']
													: null,
				'identity_vat_id'				=> 	( isset( $raw_settings['identity_vat_id'] ) )
													? $raw_settings['identity_vat_id']
													: null,
				'identity_email'				=> 	( isset( $raw_settings['identity_email'] ) )
													? $raw_settings['identity_email']
													: null,
				'display_dpo'					=> 	( isset( $raw_settings['display_dpo'] ) )
													? $raw_settings['display_dpo']
													: false,
				'dpo_email'						=> 	( isset( $raw_settings['dpo_email'] ) )
													? $raw_settings['dpo_email']
													: null,
				'dpo_name'						=> 	( isset( $raw_settings['dpo_name'] ) )
													? $raw_settings['dpo_name']
													: null,
				'dpo_address'					=> 	( isset( $raw_settings['dpo_address'] ) )
													? $raw_settings['dpo_address']
													: null,

				'customer_location'				=>	'select_countries',
				'customer_area_eu'				=>	true,
				'regulation_gdpr_like'			=>	true,

				'customer_area_california'		=>	$display_ccpa,
				'regulation_ccpa'				=>	$display_ccpa,

				'customer_area_ch'				=>	$display_lpd,
				'regulation_lpd'				=>	$display_lpd,


				'site_features_newsletter'		=>	$newsletter,

			);


			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $to_migrate_settings );

			foreach( $to_migrate_settings as $k => $v )
			{
				$the_settings['site_and_policy_settings'][ $k ] = $v;
			}

			$the_settings['add_cookie_policy_to_first_layer'] = false;
			$the_settings['add_personal_policy_to_first_layer'] = false;

			MyAgilePrivacy::update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_settings );

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Returns the permalink of a page, or null when the page is not published.
	 */
	public static function get_published_page_url( $page_id )
	{
		if( !$page_id || get_post_status( $page_id ) !== 'publish' )
		{
			return null;
		}

		return get_permalink( $page_id );
	}

	//fixed get_locale / get_user_locale
	public static function get_locale()
	{
		if( version_compare( $GLOBALS['wp_version'], '4.7', '<' ) )
		{
			$locale = get_locale();
		}
		else
		{
			$locale = get_user_locale();
		}

		return 	$locale;
	}

	//f. for getting supported languages
	public static function get_supported_languages()
	{
		return array(
			'en_US'	=>	array( 'label' => 'English',           'en_label' => 'English',      '2char' => 'en' ),
			'it_IT'	=>	array( 'label' => 'Italiano',          'en_label' => 'Italian',      '2char' => 'it' ),
			'fr_FR'	=>	array( 'label' => 'Français',          'en_label' => 'French',       '2char' => 'fr' ),
			'de_DE'	=>	array( 'label' => 'Deutsch',           'en_label' => 'German',       '2char' => 'de' ),
			'es_ES'	=>	array( 'label' => 'Español',           'en_label' => 'Spanish',      '2char' => 'es' ),
			'pt_PT'	=>	array( 'label' => 'Português',         'en_label' => 'Portuguese',   '2char' => 'pt' ),
			'nl_NL'	=>	array( 'label' => 'Nederlands',        'en_label' => 'Dutch',        '2char' => 'nl' ),
			'pl_PL'	=>	array( 'label' => 'Polski',            'en_label' => 'Polish',       '2char' => 'pl' ),
			'el'	=>	array( 'label' => 'Elliniká',          'en_label' => 'Greek',        '2char' => 'el' ),
			'hu_HU'	=>	array( 'label' => 'Magyar',            'en_label' => 'Hungarian',    '2char' => 'hu' ),
			'nb_NO'	=>	array( 'label' => 'Norsk',             'en_label' => 'Norwegian',    '2char' => 'no' ),
			'sv_SE'	=>	array( 'label' => 'Svenska',           'en_label' => 'Swedish',      '2char' => 'sv' ),
			'ru_RU'	=>	array( 'label' => 'Русский',           'en_label' => 'Russian',      '2char' => 'ru' ),
			'fi'	=>	array( 'label' => 'Suomi',             'en_label' => 'Finnish',      '2char' => 'fi' ),
			'da_DK'	=>	array( 'label' => 'Dansk',             'en_label' => 'Danish',       '2char' => 'da' ),
			'tr_TR'	=>	array( 'label' => 'Türkçe',            'en_label' => 'Turkish',      '2char' => 'tr' ),
			'cs_CZ'	=>	array( 'label' => 'Čeština',           'en_label' => 'Czech',        '2char' => 'cs' ),
			'ro_RO'	=>	array( 'label' => 'Română',            'en_label' => 'Romanian',     '2char' => 'ro' ),
			'sk_SK'	=>	array( 'label' => 'Slovenčina',        'en_label' => 'Slovak',       '2char' => 'sk' ),
			'sl_SI'	=>	array( 'label' => 'Slovenščina',       'en_label' => 'Slovenian',    '2char' => 'sl' ),
			'hr'	=>	array( 'label' => 'Hrvatski',          'en_label' => 'Croatian',     '2char' => 'hr' ),
			'lt_LT'	=>	array( 'label' => 'Lietuvių',          'en_label' => 'Lithuanian',   '2char' => 'lt' ),
			'lv_LV'	=>	array( 'label' => 'Latviešu',          'en_label' => 'Latvian',      '2char' => 'lv' ),
			'sq_AL'	=>	array( 'label' => 'Shqip',             'en_label' => 'Albanian',     '2char' => 'sq' ),
			'az'	=>	array( 'label' => 'Azərbaycan dili',   'en_label' => 'Azerbaijani',  '2char' => 'az' ),
			'bs_BA'	=>	array( 'label' => 'Bosanski',          'en_label' => 'Bosnian',      '2char' => 'bs' ),
			'bg_BG'	=>	array( 'label' => 'Български',         'en_label' => 'Bulgarian',    '2char' => 'bg' ),
			'ca'	=>	array( 'label' => 'Català',            'en_label' => 'Catalan',      '2char' => 'ca' ),
			'et'	=>	array( 'label' => 'Eesti',             'en_label' => 'Estonian',     '2char' => 'et' ),
			'mk_MK'	=>	array( 'label' => 'Македонски',        'en_label' => 'Macedonian',   '2char' => 'mk' ),
			'sr_RS'	=>	array( 'label' => 'Српски',            'en_label' => 'Serbian',      '2char' => 'sr' ),
			'uk'	=>	array( 'label' => 'Українська',        'en_label' => 'Ukrainian',    '2char' => 'uk' ),
		);
	}

	/**
	 * Returns the plugin URL, normalized to the current site scheme.
	 *
	 * Handles multisite by replacing network_site_url() with the current
	 * site URL, then enforces the correct http/https scheme via is_ssl().
	 *
	 * @param string $file Absolute path to the plugin file (__FILE__).
	 * @return string Fully qualified plugin URL with correct scheme.
	 */
	public static function get_plugin_url( $file )
	{
	    $scheme      = is_ssl() ? 'https' : 'http';
	    $network_url = set_url_scheme( network_site_url(),              $scheme );
	    $site_url    = set_url_scheme( trailingslashit( get_site_url() ), $scheme );
	    $plugin_url  = set_url_scheme( plugin_dir_url( $file ),         $scheme );

	    return str_replace( $network_url, $site_url, $plugin_url );
	}

	/**
	 * Returns a protocol-relative URL to a plugin endpoint.
	 *
	 * The scheme is inherited from the page by the browser, so the request
	 * stays same-scheme regardless of is_ssl()/home/siteurl or reverse proxies.
	 *
	 * @param string $file     Absolute path to the plugin file (__FILE__).
	 * @param string $sub_path Path appended to the plugin URL, e.g. 'api/api.php'.
	 * @return string Protocol-relative URL, e.g. //host/wp-content/.../api.php
	 */
	public static function get_api_endpoint_url( $file, $sub_path )
	{
	    $url = plugin_dir_url( $file ) . $sub_path;
	    return preg_replace( '#^https?:#', '', $url );
	}

	/**
	 * Detects whether the current web server is Nginx.
	 *
	 * Uses WordPress global variables set in wp-includes/vars.php as primary source,
	 * with a fallback to SERVER_SOFTWARE string detection.
	 *
	 * Note: detection may be unreliable behind reverse proxies (e.g. Cloudflare),
	 * in which case the function returns null.
	 *
	 * @return bool|null True if Nginx, false if not Nginx, null if unable to determine.
	 */
	public static function is_nginx()
	{
	    // Primary: use WordPress globals set in wp-includes/vars.php (available since WP 4.0)
	    global $is_nginx, $is_apache, $is_IIS, $is_iis7;

	    if( ! empty( $is_nginx ) ) {
	        return true;
	    }

	    if( ! empty( $is_apache ) || ! empty( $is_IIS ) || ! empty( $is_iis7 ) ) {
	        return false;
	    }

	    // Fallback: parse SERVER_SOFTWARE header directly
	    if( ! empty( $_SERVER['SERVER_SOFTWARE'] ) )
	    {
	        $software = strtolower( (string) $_SERVER['SERVER_SOFTWARE'] );

	        if( strpos( $software, 'nginx' ) !== false ) {
	            return true;
	        }

	        if( strpos( $software, 'apache' ) !== false ||
	             strpos( $software, 'iis' ) !== false ||
	             strpos( $software, 'litespeed' ) !== false ) {
	            return false;
	        }
	    }

	    // Unable to determine (e.g. behind a reverse proxy)
	    return null;
	}

    /**
     * Check if WordPress has direct filesystem write access to the plugins directory.
     *
     * @return bool
     */
    public static function verify_filesystem_write_access()
    {
        if( !function_exists( 'get_filesystem_method' ) )
        {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        if( !class_exists( 'WP_Filesystem_Base' ) )
        {
            require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
        }

        $context = defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : ABSPATH . 'wp-content/plugins';

        return 'direct' === get_filesystem_method( array(), $context );
    }

    /**
     * Check if PHP can extract zip files (ZipArchive class).
     *
     * @return bool
     */
    public static function verify_zip_support()
    {
        if( class_exists( 'ZipArchive' ) )
        {
            return true;
        }

        return false;
    }

    /**
     * Check if WordPress can perform remote HTTP requests.
     * Uses curl or allow_url_fopen - both work in CLI/cron context.
     *
     * @return bool
     */
    public static function verify_remote_download()
    {
        // Load HTTP utilities if not already available.
        if( !function_exists( 'wp_http_supports' ) )
        {
            require_once ABSPATH . 'wp-includes/class-http.php';
        }

        if( function_exists( 'wp_http_supports' ) )
        {
            return wp_http_supports( array( 'ssl' ) );
        }

        // Fallback: check curl or allow_url_fopen directly.
        return function_exists( 'curl_init' ) || (bool) ini_get( 'allow_url_fopen' );
    }

    /**
     * Check if WordPress allows file modifications.
     *
     * @return bool
     */
    public static function verify_file_mods_allowed()
    {
        // wp_is_file_mod_allowed() exists since WP 4.8; the constant is honored before that.
        if( !function_exists( 'wp_is_file_mod_allowed' ) )
        {
            return !( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS );
        }

        return wp_is_file_mod_allowed( 'capability_update_core' );
    }

	private static function map_load_log_guard()
	{
		if( function_exists( 'map_log_redact' ) )
		{
			return;
		}

		$guard_file = dirname( __FILE__ ) . '/my-agile-privacy-log-guard.php';

		if( file_exists( $guard_file ) )
		{
			@include_once $guard_file;
		}
	}

	/**
	 * Puts the access guards in the log directory, whether or not anything is
	 * being logged right now: a directory left over from an earlier run has to be
	 * covered too.
	 *
	 * @access   public
	 */
	public static function map_protect_log_dir()
	{
		self::map_load_log_guard();

		if( function_exists( 'map_log_protect_dir' ) && defined( 'WP_CONTENT_DIR' ) )
		{
			map_log_protect_dir( WP_CONTENT_DIR . '/debug/' );
		}
	}

	/**
	 * write to log file
	 * @access   public
	 */
	public static function write_log($log)
	{
		self::map_load_log_guard();

		if( !function_exists( 'map_log_redact' ) || !function_exists( 'file_put_contents' ) )
		{
			return;
		}

		if( function_exists( 'map_log_preparing' ) && map_log_preparing() )
		{
			return;
		}

		$log = map_log_redact( $log );

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

		if( function_exists( 'map_log_protect_dir' ) )
		{
			map_log_protect_dir( $dirPath );
		}

		$bt = debug_backtrace();

		$depth = 0;

		$file = isset($bt[$depth])     ? $bt[$depth]['file'] : null;
		$line = isset($bt[$depth])     ? $bt[$depth]['line'] : 0;
		$func = isset($bt[$depth + 1]) ? $bt[$depth + 1]['function'] : null;

		if(is_array($log) || is_object($log)) {
			$data = print_r($log, true);
		} else {
			$data = $log;
		}

		$string = "file=$file, line=$line, func=$func: ".$data."\n";

		@file_put_contents( $filePath, $string, FILE_APPEND );
	}

	/**
	 * True for script-initiated requests.
	 */
	public static function is_xhr_request()
	{
		return ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) &&
			is_string( $_SERVER['HTTP_X_REQUESTED_WITH'] ) &&
			false !== stripos( $_SERVER['HTTP_X_REQUESTED_WITH'], 'xmlhttprequest' ) );
	}

	/**
	 * True when an AMP plugin is active.
	 */
	public static function is_amp_plugin_active()
	{
		return ( function_exists( 'amp_is_request' ) ||
			function_exists( 'is_amp_endpoint' ) ||
			function_exists( 'ampforwp_is_amp_endpoint' ) ||
			function_exists( 'is_better_amp' ) );
	}

	/**
	 * True for requests that do not ask for a full page.
	 */
	public static function is_non_page_request()
	{
		if( self::is_xhr_request() || isset( $_POST['_wpnonce'] ) )
		{
			return true;
		}

		return ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() );
	}

	/**
	 * Marks non page responses as not cacheable.
	 */
	public static function map_send_nocache_signal()
	{
		static $already_run = false;

		if( $already_run )
		{
			return;
		}

		$already_run = true;

		$the_settings = self::get_settings();

		if( !( isset( $the_settings['is_on'] ) && $the_settings['is_on'] ) || !self::is_non_page_request() )
		{
			return;
		}

		if( !defined( 'DONOTCACHEPAGE' ) )
		{
			define( 'DONOTCACHEPAGE', true );
		}

		do_action( 'litespeed_control_set_nocache', 'My Agile Privacy: non page request' );

		if( headers_sent() )
		{
			return;
		}

		foreach( headers_list() as $sent_header )
		{
			if( is_string( $sent_header ) &&
				0 === stripos( $sent_header, 'cache-control:' ) &&
				( false !== stripos( $sent_header, 'no-store' ) || false !== stripos( $sent_header, 'private' ) ||
					false !== stripos( $sent_header, 'no-cache' ) ) )
			{
				return;
			}
		}

		header( 'Cache-Control: private' );
	}

	/**
	 * True for a version made of digits and dots only.
	 */
	public static function is_plain_version( $version )
	{
		return ( is_string( $version ) && 1 === preg_match( '/^\d+(\.\d+)*\z/', $version ) );
	}

	/**
	 * Parses the cdn-pack manifest: array( 'version' => ..., 'build' => ... ), or an empty array.
	 */
	public static function parse_cdn_pack_manifest( $json, $dir )
	{
		if( !is_string( $json ) || !is_string( $dir ) )
		{
			return array();
		}

		$data = json_decode( $json, true );

		if( !is_array( $data ) ||
			!isset( $data['files'] ) || !is_array( $data['files'] ) ||
			!isset( $data['files']['cookie-shield.js'] ) || !is_array( $data['files']['cookie-shield.js'] ) ||
			!isset( $data['files']['cookie-shield.js']['version'] ) ||
			!self::is_plain_version( $data['files']['cookie-shield.js']['version'] ) ||
			!isset( $data['build'] ) || !is_string( $data['build'] ) ||
			1 !== preg_match( '/^[0-9a-f]{7,40}-[0-9a-f]{8}\z/', $data['build'] ) ||
			!is_file( $dir . 'cookie-shield.js' ) )
		{
			return array();
		}

		return array( 'version' => $data['files']['cookie-shield.js']['version'], 'build' => $data['build'] );
	}

	/**
	 * Reads the cdn-pack folder, once per request.
	 */
	public static function get_cdn_pack()
	{
		static $pack = null;

		if( $pack !== null )
		{
			return $pack;
		}

		$pack = array();

		if( !defined( 'MAP_PLUGIN_FILENAME' ) )
		{
			return $pack;
		}

		$dir = plugin_dir_path( MAP_PLUGIN_FILENAME ) . 'cdn-pack/';
		$manifest_path = $dir . 'version_manifest.json';

		if( !is_file( $manifest_path ) )
		{
			return $pack;
		}

		$size = filesize( $manifest_path );

		if( $size === false || $size > 8192 )
		{
			return $pack;
		}

		$pack = self::parse_cdn_pack_manifest( file_get_contents( $manifest_path ), $dir );

		return $pack;
	}

	/**
	 * True when the cdn-pack shield replaces the CDN one.
	 */
	public static function cdn_pack_shield_wins( $pack, $cdn_manifest, $local_caching_allowed )
	{
		if( !$local_caching_allowed ||
			!is_array( $pack ) ||
			!isset( $pack['version'] ) || !self::is_plain_version( $pack['version'] ) )
		{
			return false;
		}

		$cdn_version = null;

		if( is_array( $cdn_manifest ) &&
			isset( $cdn_manifest['files'] ) && is_array( $cdn_manifest['files'] ) &&
			isset( $cdn_manifest['files']['cookie-shield.js'] ) && is_array( $cdn_manifest['files']['cookie-shield.js'] ) &&
			isset( $cdn_manifest['files']['cookie-shield.js']['version'] ) )
		{
			$cdn_version = $cdn_manifest['files']['cookie-shield.js']['version'];
		}

		if( !self::is_plain_version( $cdn_version ) )
		{
			return true;
		}

		return version_compare( $pack['version'], $cdn_version, '>' );
	}

	/**
	 * Base URL of the cdn-pack folder.
	 */
	public static function get_cdn_pack_base_url()
	{
		$final_url = self::get_plugin_url( MAP_PLUGIN_FILENAME ) . '/cdn-pack/';

		return preg_replace( '/([^:])(\/{2,})/', '$1/', $final_url );
	}

	/**
	 * Installed version as the post-update check compares it.
	 */
	public static function get_effective_installed_version()
	{
		$pack = self::get_cdn_pack();

		if( isset( $pack['build'] ) && is_string( $pack['build'] ) )
		{
			return MAP_PLUGIN_VERSION . '+cdn-pack-' . $pack['build'];
		}

		return MAP_PLUGIN_VERSION;
	}

	/**
	 * Cookie domain the frontend applies, lower case; null when there is none.
	 */
	public static function get_applied_cookie_domain( $rconfig )
	{
		if( !is_array( $rconfig ) ||
			!isset( $rconfig['cookie_domain'] ) ||
			!is_string( $rconfig['cookie_domain'] ) ||
			$rconfig['cookie_domain'] === '' )
		{
			return null;
		}

		$candidate = sanitize_text_field( $rconfig['cookie_domain'] );

		if( !preg_match( '/^\.?([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i', $candidate ) )
		{
			return null;
		}

		return strtolower( $candidate );
	}

	/**
	 * Keeps the stored cookie domain in step with the applied one, and books a page cache clear when it changes.
	 */
	public static function track_applied_cookie_domain()
	{
		$applied = self::get_cookie_domain_state( self::get_rconfig() );

		$stored = get_option( MAP_PLUGIN_COOKIE_DOMAIN_SEEN, false );
		$seen = is_string( $stored ) ? $stored : '';

		if( $seen === $applied )
		{
			return;
		}

		if( false === $stored )
		{
			add_option( MAP_PLUGIN_COOKIE_DOMAIN_SEEN, $applied, '', 'no' );
		}
		else
		{
			update_option( MAP_PLUGIN_COOKIE_DOMAIN_SEEN, $applied, false );
		}

		self::schedule_page_cache_flush();
	}

	/**
	 * Books a clear of the third-party page caches in a request of its own, once.
	 */
	public static function schedule_page_cache_flush()
	{
		if( false === wp_next_scheduled( 'map_cookie_domain_cache_flush' ) )
		{
			wp_schedule_single_event( time() + 120, 'map_cookie_domain_cache_flush' );
		}
	}

	/**
	 * Clears the third-party page caches after a cookie domain change.
	 */
	public static function run_cookie_domain_cache_flush()
	{
		self::tryOtherPluginCacheClearScoped();
	}

	/**
	 * Clears the third-party page caches, limited to the current blog where the cache plugin allows it.
	 */
	public static function tryOtherPluginCacheClearScoped()
	{
		self::tryOtherPluginCacheClear( 'network' );
		self::tryOtherPluginCacheClear( 'blog' );
	}

	/**
	 * Whether the frontend may drop the host-only copies of its cookies on this installation.
	 */
	public static function cookie_cleanup_allowed()
	{
		return !( is_multisite() && function_exists( 'is_subdomain_install' ) && !is_subdomain_install() );
	}

	/**
	 * Applied cookie domain with the cleanup marker, as stamped and stored; '' when there is none.
	 */
	public static function get_cookie_domain_state( $rconfig )
	{
		$domain = self::get_applied_cookie_domain( $rconfig );

		if( !is_string( $domain ) )
		{
			return '';
		}

		return self::cookie_cleanup_allowed() ? $domain : $domain . ':n';
	}
}
