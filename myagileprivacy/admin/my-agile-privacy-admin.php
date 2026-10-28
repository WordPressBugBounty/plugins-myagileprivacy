<?php

if( !defined( 'MAP_PLUGIN_NAME' ) )
{
	exit('Not allowed.');
}

/**
 * The admin-specific functionalities
 *
 * @link       https://www.myagileprivacy.com/
 *
 * @package    MyAgilePrivacy
 * @subpackage MyAgilePrivacy/admin
 */

/**
 * The admin-specific functionalities
 *
 * Defines the plugin name, version, and hooks
 *
 * @package    MyAgilePrivacy
 * @subpackage MyAgilePrivacy/admin
 * @author     https://www.myagileprivacy.com/
 */

final class MyAgilePrivacyAdmin {

	/**
	 * Plugin Name
	 *
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * Plugin Version
	 *
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	private $sync_lock_taken = false;

	private $sync_lock_value = null;

	/**
	 * Constructor
	 *
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version, $plugin_obj )
	{
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->set_locale();
	}


	/**
	 * Define the locale for this plugin for internationalization.
	 * Custom text-domain loader.
	 * @access   private
	 */
	private function my_load_plugin_textdomain( $domain, $deprecated = false, $plugin_rel_path = false ) {

		if( isset( $GLOBALS['wp_version'] ) && version_compare( $GLOBALS['wp_version'], '5.0', '<' ) )
		{
			if( version_compare( $GLOBALS['wp_version'], '4.7', '<' ) )
			{
				$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
			}
			else
			{
				$locale = apply_filters( 'plugin_locale', is_admin() ? get_user_locale() : get_locale(), $domain );
			}
		}
		else
		{
			$locale = apply_filters( 'plugin_locale', determine_locale(), $domain );
		}

		$mofile = $domain . '-' . $locale . '.mo';

		/*
		// Try to load from the languages directory first.
		if( load_textdomain( $domain, WP_LANG_DIR . '/plugins/' . $mofile ) ) {
			return true;
		}
		*/

		if( false !== $plugin_rel_path ) {
			$path = WP_PLUGIN_DIR . '/' . trim( $plugin_rel_path, '/' );
		} elseif( false !== $deprecated ) {
			_deprecated_argument( __FUNCTION__, '2.7.0' );
			$path = ABSPATH . trim( $deprecated, '/' );
		} else {
			$path = WP_PLUGIN_DIR;
		}

		$fullpath = $path . '/' . $mofile;

		//Skip loading when the .mo file is absent.
		if( !is_readable( $fullpath ) )
		{
			return false;
		}

		return load_textdomain( $domain, $fullpath );
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * @access   private
	 */
	private function set_locale()
	{
		global $locale;

		$loaded = $this->my_load_plugin_textdomain(
			MAP_PLUGIN_TEXTDOMAIN,
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/lang/'
		);
	}

	/**
	 * f. for activating myagilepixel
	 */
	public function admin_auto_enable_cookie()
	{
		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'START admin_auto_enable_cookie' );

		if( !function_exists( 'is_plugin_active' ) )
		{
			include_once(ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		if( is_plugin_active( 'myagilepixel/myagilepixel.php' ) )
		{
			$auto_activate_keys = array();

			if( defined( 'MAPX_my_agile_pixel_ga_on' ) )
			{
				$elem = array(
					'next_status'	=> 	'publish',
					'key'			=>	'my_agile_pixel_ga',
				);

				$auto_activate_keys[] = $elem;

				$elem = array(
					'next_status'	=> 	'__blocked',
					'key'			=>	'google_analytics',
				);

				$auto_activate_keys[] = $elem;
			}

			if( defined( 'MAPX_my_agile_pixel_fbq_on' ) )
			{
				$elem = array(
					'next_status'	=> 	'publish',
					'key'			=>	'my_agile_pixel_fbq',
				);

				$auto_activate_keys[] = $elem;

				$elem = array(
					'next_status'	=> 	'__blocked',
					'key'			=>	'facebook_remarketing',
				);

				$auto_activate_keys[] = $elem;
			}

			if( defined( 'MAPX_my_agile_pixel_tiktok_on' ) )
			{
				$elem = array(
					'next_status'	=> 	'publish',
					'key'			=>	'my_agile_pixel_tiktok',
				);

				$auto_activate_keys[] = $elem;

				$elem = array(
					'next_status'	=> 	'__blocked',
					'key'			=>	'tik_tok',
				);

				$auto_activate_keys[] = $elem;
			}

			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $auto_activate_keys );

			if( count( $auto_activate_keys ) > 0 )
			{
				// Get settings
				$the_settings = MyAgilePrivacy::get_settings();

				$currentAndSupportedLanguages = MyAgilePrivacy::getCurrentAndSupportedLanguages();

				$something_changed = false;

				foreach( $auto_activate_keys as $k => $v )
				{
					//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $v, true );

					if( $v )
					{
						$post_status_to_search = array( 'draft', 'publish', '__blocked', '__always_allowed' );

						$cc_args = array(
							'posts_per_page'   	=> 	-1,
							'post_type'        	=>	MAP_POST_TYPE_COOKIES,
							'meta_key'         	=> 	'_map_api_key',
							'meta_value'       	=> 	$v['key'],
							'post_status' 		=> 	$post_status_to_search,
						);

						//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $cc_args, true );
						$cc_query = new WP_Query( $cc_args );

						if( $cc_query->have_posts() )
						{
							foreach ( $cc_query->get_posts() as $p )
							{
								$main_post_id = $p->ID;

								$post_type = get_post_type( $main_post_id );

								//double check for strange theme / plugins
								if( $post_type == MAP_POST_TYPE_COOKIES )
								{
									$this_post_status = get_post_status( $main_post_id );

									if( is_array( $post_status_to_search ) &&
										in_array( $this_post_status, $post_status_to_search ) )
									{
										$original_post_status = $p->post_status;

										if( $original_post_status != $v['next_status'] )
										{
											//update
											$my_post = array(
												'ID'           	=> 	$main_post_id,
												'post_status'	=>	$v['next_status'],
											);

											if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $my_post );

											wp_update_post( $my_post );

											if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( "published ". print_r($v, true ) );

											update_post_meta( $main_post_id, "_map_auto_detected", 1 );

											$something_changed = true;
										}

										if( $currentAndSupportedLanguages['with_multilang'] )
										{
											//no further actions
										}
									}
								}
							}

							MyAgilePrivacy::internal_query_reset();
						}
					}
				}

				if( $something_changed )
				{
					MyAgilePrivacy::flush_json_cache();
				}
			}
		}


		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'END admin_auto_enable_cookie' );
	}


	/** f. cookie state realignment */
	public function map_reverse_pixel_cookies( $force_all_channels = false )
	{
		if( !function_exists( 'is_plugin_active' ) )
		{
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		$pixel_active = is_plugin_active( 'myagilepixel/myagilepixel.php' );

		if( !$force_all_channels )
		{
			$seen = MyAgilePrivacy::get_option( MAP_PLUGIN_PIXEL_SEEN, null );

			if( $pixel_active )
			{
				if( !$seen )
				{
					MyAgilePrivacy::update_option( MAP_PLUGIN_PIXEL_SEEN, 1 );
				}
			}
			else if( !$seen )
			{
				return;
			}
		}

		$channels = array(
			array( 'const' => 'MAPX_my_agile_pixel_ga_on', 'pixel_key' => 'my_agile_pixel_ga', 'std_key' => 'google_analytics' ),
			array( 'const' => 'MAPX_my_agile_pixel_fbq_on', 'pixel_key' => 'my_agile_pixel_fbq', 'std_key' => 'facebook_remarketing' ),
			array( 'const' => 'MAPX_my_agile_pixel_tiktok_on', 'pixel_key' => 'my_agile_pixel_tiktok', 'std_key' => 'tik_tok' ),
		);

		$changed = false;

		foreach( $channels as $ch )
		{
			$channel_on = ( !$force_all_channels ) && $pixel_active && defined( $ch['const'] );

			if( $channel_on )
			{
				continue;
			}

			if( $this->map_set_pixel_cookie_status( $ch['pixel_key'], 'publish', 'draft' ) )
			{
				$changed = true;

				if( $this->map_set_pixel_cookie_status( $ch['std_key'], '__blocked', 'publish' ) )
				{
					$changed = true;
				}
			}
		}

		MyAgilePrivacy::internal_query_reset();

		if( $changed )
		{
			MyAgilePrivacy::flush_json_cache();
		}
		else if( !$pixel_active && !$force_all_channels )
		{
			MyAgilePrivacy::update_option( MAP_PLUGIN_PIXEL_SEEN, 0 );
		}
	}


	/** f. set cookie post-status by api key */
	public function map_set_pixel_cookie_status( $api_key, $from_status, $to_status )
	{
		$changed = false;

		$cc_args = array(
			'posts_per_page'         => -1,
			'post_type'              => MAP_POST_TYPE_COOKIES,
			'post_status'            => $from_status,
			'meta_key'               => '_map_api_key',
			'meta_value'             => $api_key,
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		);

		$cc_query = new WP_Query( $cc_args );

		if( $cc_query->have_posts() )
		{
			foreach( $cc_query->get_posts() as $p )
			{
				if( get_post_type( $p->ID ) != MAP_POST_TYPE_COOKIES )
				{
					continue;
				}

				if( get_post_status( $p->ID ) != $from_status )
				{
					continue;
				}

				$auto_detected = get_post_meta( $p->ID, '_map_auto_detected', true );

				if( intval( $auto_detected ) != 1 )
				{
					continue;
				}

				wp_update_post( array(
					'ID'          => $p->ID,
					'post_status' => $to_status,
				) );

				$changed = true;
			}
		}

		return $changed;
	}


	/** f. realignment on admin footer */
	public function map_reverse_pixel_cookies_footer()
	{
		$this->map_reverse_pixel_cookies( false );
	}


	/** f. realignment on plugin deactivation */
	public function map_on_pixel_deactivated( $plugin )
	{
		if( $plugin === 'myagilepixel/myagilepixel.php' )
		{
			$this->map_reverse_pixel_cookies( true );
		}
	}


	/*
	 * f. for clearing log file
	*/
	public function admin_clear_logfile()
	{
		//clean logfile when debugger is off
		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER == false )
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

			if( is_file( $filePath ) )
			{
				wp_delete_file( $filePath );
			}
		}
	}

	/**
	 * Unset some data for polylang
	 *
	 */
	public function add_cpt_to_pll( $post_types, $is_settings )
	{
		if( $is_settings )
		{
			// hides cpt from the list of custom post types in Polylang settings
			unset( $post_types[ MAP_POST_TYPE_COOKIES ] );
			unset( $post_types[ MAP_POST_TYPE_POLICY ] );
		}
		else
		{
			// enables language and translation management
			$post_types[ MAP_POST_TYPE_COOKIES ] =  MAP_POST_TYPE_COOKIES;
			$post_types[ MAP_POST_TYPE_POLICY ] =  MAP_POST_TYPE_POLICY;
		}
		return $post_types;
	}


	/**
	 * Stylesheets for the admin area.
	 *
	 * @access   public
	 */
	/**
	 * Tells whether the current screen belongs to the plugin.
	 *
	 * @return bool
	 */
	public static function is_map_screen()
	{
		$screen = get_current_screen();

		if( !is_object( $screen ) )
		{
			return false;
		}

		$bases = array(
			'my-agile-privacy-c_page_my-agile-privacy-c_settings',
			'my-agile-privacy-c_page_my-agile-privacy-c_backup_restore',
			'my-agile-privacy-c_page_my-agile-privacy-c_helpdesk',
			'my-agile-privacy-c_page_my-agile-privacy-c_dashboard',
			'my-agile-privacy-c_page_my-agile-privacy-c_stats',
			'my-agile-privacy-c_page_my-agile-privacy-c_ab_test',
			'my-agile-privacy-c_page_my-agile-privacy-c_translations',
			'my-agile-privacy-c_page_my-agile-privacy-c_guided_wizard',
			'my-agile-privacy-c_page_my-agile-privacy-c_trial',
		);

		if( isset( $screen->base ) && in_array( $screen->base, $bases, true ) )
		{
			return true;
		}

		if( isset( $screen->post_type ) &&
			( $screen->post_type == MAP_POST_TYPE_COOKIES || $screen->post_type == MAP_POST_TYPE_POLICY )
		)
		{
			return true;
		}

		return false;
	}

	/**
	 * Resolves the file a callback is defined in and matches it against the
	 * plugin directory and the WordPress core directories.
	 *
	 * @param  mixed $callback
	 * @return bool
	 */
	private static function is_own_or_core_callback( $callback )
	{
		try
		{
			if( is_array( $callback ) && 2 === count( $callback ) )
			{
				$target = is_object( $callback[0] ) ? get_class( $callback[0] ) : $callback[0];
				$ref    = new ReflectionMethod( $target, $callback[1] );
			}
			elseif( is_object( $callback ) && !( $callback instanceof Closure ) && method_exists( $callback, '__invoke' ) )
			{
				$ref = new ReflectionMethod( $callback, '__invoke' );
			}
			elseif( is_string( $callback ) && false !== strpos( $callback, '::' ) )
			{
				$callback_parts = explode( '::', $callback, 2 );
				$ref = new ReflectionMethod( $callback_parts[0], $callback_parts[1] );
			}
			elseif( is_string( $callback ) || $callback instanceof Closure )
			{
				$ref = new ReflectionFunction( $callback );
			}
			else
			{
				return true;
			}

			$file = (string) $ref->getFileName();
		}
		catch( Exception $e )
		{
			return true;
		}

		if( '' === $file )
		{
			return true;
		}

		$file = wp_normalize_path( $file );

		if( 0 === strpos( $file, wp_normalize_path( ABSPATH.'wp-admin' ) ) ||
			0 === strpos( $file, wp_normalize_path( ABSPATH.'wp-includes' ) )
		)
		{
			return true;
		}

		$own = array( MAP_PLUGIN_SLUG, 'myagilepixel' );

		foreach( $own as $slug )
		{
			if( false !== strpos( $file, '/'.$slug ) )
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Keeps unrelated notices out of the plugin screens.
	 *
	 */
	public function hide_foreign_notices()
	{
		if( !self::is_map_screen() )
		{
			return;
		}

		global $wp_filter;

		$hooks = array( 'admin_notices', 'all_admin_notices' );

		foreach( $hooks as $hook )
		{
			if( !isset( $wp_filter[ $hook ] ) )
			{
				continue;
			}

			if( is_object( $wp_filter[ $hook ] ) && isset( $wp_filter[ $hook ]->callbacks ) && is_array( $wp_filter[ $hook ]->callbacks ) )
			{
				$hook_callbacks = $wp_filter[ $hook ]->callbacks;
			}
			elseif( is_array( $wp_filter[ $hook ] ) )
			{
				$hook_callbacks = $wp_filter[ $hook ];
			}
			else
			{
				continue;
			}

			$to_remove = array();

			foreach( $hook_callbacks as $priority => $callbacks )
			{
				foreach( $callbacks as $callback )
				{
					if( !isset( $callback['function'] ) || self::is_own_or_core_callback( $callback['function'] ) )
					{
						continue;
					}

					$to_remove[] = array( $callback['function'], $priority );
				}
			}

			foreach( $to_remove as $item )
			{
				remove_action( $hook, $item[0], $item[1] );
			}
		}
	}

	public function enqueue_styles()
	{
		wp_enqueue_style( 'map-notices', plugin_dir_url( __FILE__ ) ."css/map-notices.css", array(),$this->version, 'all' );

		if( MyAgilePrivacy::map_can_edit() )
		{
			wp_enqueue_style( 'map-adminbar', plugin_dir_url( __FILE__ ) ."css/map-adminbar.css", array(),$this->version, 'all' );
		}

		$do_load = false;

		if( self::is_map_screen() )
		{
			$do_load = true;
		}

		if( $do_load )
		{
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style( 'admin-myagileprivacy', plugin_dir_url( __FILE__ ) ."css/my-agile-privacy-admin.css", array(),$this->version, 'all' );
			wp_enqueue_style( $this->plugin_name.'-bootstrap', plugin_dir_url( __FILE__ ) ."css/bootstrap.min.css", array(),$this->version, 'all' );
			wp_enqueue_style( $this->plugin_name.'-fawesome', plugin_dir_url( __FILE__ ) ."css/f-awesome-all.css", array(),$this->version, 'all' );
			wp_enqueue_style( $this->plugin_name.'-animate', plugin_dir_url( __FILE__ ) ."css/animate.min.css", array(),$this->version, 'all' );
			wp_enqueue_style( $this->plugin_name.'-prism', plugin_dir_url( __FILE__ ) ."css/prism.css", array(),$this->version, 'all' );
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) ."css/preview.css", array(),$this->version, 'all' );
		}

	}

	/**
	 * Js for the admin area.
	 *
	 */
	public function enqueue_scripts()
	{
		$do_load = false;

		if( self::is_map_screen() )
		{
			$do_load = true;
		}

		if( $do_load )
		{
			wp_enqueue_script( $this->plugin_name.'-prism', plugin_dir_url( __FILE__ ) . 'js/prism.js', array( 'jquery'), $this->version, false );
			wp_enqueue_script( $this->plugin_name.'-popper', plugin_dir_url( __FILE__ ) . 'js/popper.min.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( $this->plugin_name.'-bootstrap', plugin_dir_url( __FILE__ ) . 'js/bootstrap.bundle.min.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/my-agile-privacy-admin.js', array( 'jquery' ,'wp-color-picker' ), $this->version, false );


			wp_localize_script( $this->plugin_name, 'map_ajax',
				array(
					'ajax_url' 						=> admin_url( 'admin-ajax.php' ),
					'security' 						=> wp_create_nonce( 'check_license_status' ),
					// own nonce: the trial family writes and reaches the network, the license check
					// only reads. ⚠️ wp_localize_script turns top level scalars into STRINGS: any
					// value added here is compared in JS with == and never with ===
					'trial_security' 				=> wp_create_nonce( 'map_request_trial' ),
					'trial_b_security' 				=> wp_create_nonce( 'map_request_trial_b' ),
			) );

			// the outcome texts belong to the catalog, not to the JavaScript
			wp_localize_script( $this->plugin_name, 'map_trial_i18n',
				array(
					'invalid'     => __( 'Please fill in your name and a valid email address.', 'MAP_txt' ),
					'sent'        => __( 'Done. We have sent a confirmation link to %s: open it to start the trial.', 'MAP_txt' ),
					'refused'     => __( 'We cannot start a trial for this site.', 'MAP_txt' ),
					'pending'     => __( 'A request is already waiting for confirmation. Check your inbox.', 'MAP_txt' ),
					'unreachable' => __( 'We could not reach the server. Please try again in a few minutes.', 'MAP_txt' ),
					'granted'     => __( 'Done. The paid features are on for this site: the page is reloading.', 'MAP_txt' ),
					'no_site'     => __( 'This site is not connected to your license yet. Save your settings first, then ask again.', 'MAP_txt' ),
					'no_licence'  => __( 'Enter your license code first.', 'MAP_txt' ),
					'site_used'   => __( 'This site has already had its trial, and it cannot be reopened.', 'MAP_txt' ),
					'family_used' => __( 'This site has already had its trial, and it cannot be reopened.', 'MAP_txt' ),
					'cap_reached' => __( 'Your license has used all of its trials. Contact us to try the paid features on another site.', 'MAP_txt' ),
					'reseller'    => __( 'Your license was sold to you by a reseller: ask them for the trial.', 'MAP_txt' ),
			) );
		}
	}

	//conditionally reconfig tinymce
	public function map_tinymce_config( $init )
	{
		$do_load = false;

		global $typenow;
		if( $typenow &&
			( $typenow == MAP_POST_TYPE_COOKIES || $typenow == MAP_POST_TYPE_POLICY )
		)
		{
			$do_load = true;
		}

		if( is_admin() && $do_load )
		{
			$init['wpautop'] = false;
		}

		return $init;
	}


	/**
	* Function for calling remote sync via cronjob
	 * @access   public
	*/
	public function do_cron_sync_install_counter()
	{
		$data = $this->js_get_plugin_stats();

		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $data );

		if( $data && isset( $data ) )
		{
			MyAgilePrivacy::update_option( MAP_PLUGIN_STATS, $data );
		}
	}


	/**
	* Function for calling remote sync via cronjob
	 * @access   public
	*/
	public function do_cron_sync()
	{
		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'do_cron_sync start' );

		MyAgilePrivacy::update_option( MAP_PLUGIN_DO_SYNC_NOW, 1 );

		$multilang_enabled = MyAgilePrivacy::check_if_multilang_enabled();

		if( !$multilang_enabled )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $multilang_enabled );

			$this->triggered_do_cron_sync();
		}

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'do_cron_sync end' );

		return true;
	}


	/**
	* Function for calling remote sync via cronjob (triggered via wp_footer hook and do_cron_sync)
	*/
	public function triggered_do_cron_sync()
	{
		global $locale;

		$sync_locale = $locale;

		try
		{
			return $this->run_cron_sync();
		}
		catch( Exception $e )
		{
			$this->recover_failed_sync( $sync_locale, $e );
		}
		catch( Error $e )
		{
			$this->recover_failed_sync( $sync_locale, $e );
		}

		return null;
	}


	/**
	* sanitize_textarea_field(), with the WordPress 4.7 body where the function is missing.
	*/
	private function sanitize_textarea_value( $str )
	{
		if( function_exists( 'sanitize_textarea_field' ) )
		{
			return sanitize_textarea_field( $str );
		}

		$filtered = wp_check_invalid_utf8( $str );

		if( strpos( $filtered, '<' ) !== false )
		{
			$filtered = wp_pre_kses_less_than( $filtered );
			$filtered = wp_strip_all_tags( $filtered, false );
			$filtered = str_replace( "<\n", "&lt;\n", $filtered );
		}

		$filtered = trim( $filtered );

		$found = false;

		while( preg_match( '/%[a-f0-9]{2}/i', $filtered, $match ) )
		{
			$filtered = str_replace( $match[0], '', $filtered );
			$found = true;
		}

		if( $found )
		{
			$filtered = trim( preg_replace( '/ +/', ' ', $filtered ) );
		}

		return apply_filters( 'sanitize_textarea_field', $filtered, $str );
	}


	/**
	* Leaves the sync state consistent after an unexpected error.
	*/
	private function recover_failed_sync( $sync_locale, $e )
	{
		global $locale;

		$locale = $sync_locale;

		if( MyAgilePrivacy::get_option( MAP_PLUGIN_DO_SYNC_NOW, 0 ) )
		{
			MyAgilePrivacy::update_option( MAP_PLUGIN_DO_SYNC_NOW, 0 );
			MyAgilePrivacy::update_option( MAP_PLUGIN_DO_SYNC_LAST_EXECUTION, time() );

			set_transient( 'map_sync_pause', 1, HOUR_IN_SECONDS );

			MyAgilePrivacy::$doing_internal_write = false;
		}

		$this->release_sync_lock();

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'sync error: ' . get_class( $e ) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() );
	}

	/**
	* Whether the last sync is older than the expected interval.
	*/
	private function is_sync_stale( $last_execution, $now )
	{
		return !$last_execution || $now - (int) $last_execution > 82800;
	}

	/**
	* Takes the sync lock; false when another request holds it.
	*/
	private function take_sync_lock()
	{
		global $wpdb;

		if( $this->sync_lock_taken )
		{
			return true;
		}

		if( defined( 'MAP_DEV_MODE' ) && MAP_DEV_MODE )
		{
			$this->sync_lock_taken = true;
			return true;
		}

		$now = time();

		// the time of the take, made unique so that the release only removes this request's lock
		$lock_value = sprintf( '%d.%06d', $now, mt_rand( 0, 999999 ) );

		$affected = $wpdb->query( $wpdb->prepare(
			"INSERT IGNORE INTO {$wpdb->options} ( option_name, option_value, autoload ) VALUES ( %s, %s, 'no' )",
			MAP_PLUGIN_SYNC_LOCK,
			$lock_value
		) );

		if( !$affected )
		{
			$held_since = $wpdb->get_var( $wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
				MAP_PLUGIN_SYNC_LOCK
			) );

			if( $held_since === null ||
				( is_numeric( $held_since ) && $now - (int) $held_since <= MAP_SYNC_LOCK_TRESHOLD ) )
			{
				return false;
			}

			// only one of the requests that found it expired wins the swap
			$affected = $wpdb->query( $wpdb->prepare(
				"UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s",
				$lock_value,
				MAP_PLUGIN_SYNC_LOCK,
				$held_since
			) );
		}

		MyAgilePrivacy::drop_option_cache( MAP_PLUGIN_SYNC_LOCK );

		$this->sync_lock_taken = ( $affected > 0 );
		$this->sync_lock_value = $this->sync_lock_taken ? $lock_value : null;

		return $this->sync_lock_taken;
	}

	/**
	* Releases the sync lock taken by this request.
	*/
	private function release_sync_lock()
	{
		global $wpdb;

		if( !$this->sync_lock_taken )
		{
			return;
		}

		// a lock taken over by another request after expiry is not ours to remove
		if( $this->sync_lock_value !== null )
		{
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s",
				MAP_PLUGIN_SYNC_LOCK,
				$this->sync_lock_value
			) );

			MyAgilePrivacy::drop_option_cache( MAP_PLUGIN_SYNC_LOCK );
		}

		$this->sync_lock_taken = false;
		$this->sync_lock_value = null;
	}


	/**
	* Body of the remote sync.
	*/
	private function run_cron_sync()
	{
		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'start triggered_do_cron_sync' );

		//Apply account preferences.
		MyAgilePrivacy::checkDoNotAskLicenseCode();

		$the_settings = MyAgilePrivacy::get_settings();
		$rconfig = MyAgilePrivacy::get_rconfig();

		$now = time();

		$MAP_DB_PATCH_2_DONE = MyAgilePrivacy::get_option( MAP_DB_PATCH_2_DONE, false );

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $MAP_DB_PATCH_2_DONE );

		if( !$MAP_DB_PATCH_2_DONE )
		{
			$patch2_status = MyAgilePrivacy::dbMigrateDbasePatch2();

			MyAgilePrivacy::update_option( MAP_DB_PATCH_2_DONE, true );

			if( $patch2_status )
			{
				MyAgilePrivacy::update_option( MAP_PLUGIN_DO_SYNC_NOW, 1 );
			}
		}

		$MAP_DB_PATCH_3_DONE = MyAgilePrivacy::get_option( MAP_DB_PATCH_3_DONE, false );

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $MAP_DB_PATCH_3_DONE );

		if( !$MAP_DB_PATCH_3_DONE )
		{
			$patch3_status = MyAgilePrivacy::dbMigrateDbasePatch3();

			MyAgilePrivacy::update_option( MAP_DB_PATCH_3_DONE, true );

			if( $patch3_status )
			{
				MyAgilePrivacy::update_option( MAP_PLUGIN_DO_SYNC_NOW, 1 );
			}
		}

		//Force a sync if the last one is older than the expected interval.
		$do_sync_now = MyAgilePrivacy::get_option( MAP_PLUGIN_DO_SYNC_NOW , 1 ) ||
			$this->is_sync_stale( MyAgilePrivacy::get_option( MAP_PLUGIN_DO_SYNC_LAST_EXECUTION, null ), $now );

		if( $do_sync_now && get_transient( 'map_sync_pause' ) )
		{
			$do_sync_now = 0;
		}

		$map_lock_busy = false;

		if( $do_sync_now && !$this->take_sync_lock() )
		{
			$do_sync_now = 0;
			$map_lock_busy = true;
		}

		// the request that held the lock may have completed the sync meanwhile
		if( $do_sync_now )
		{
			wp_cache_delete( MAP_PLUGIN_DO_SYNC_NOW, 'options' );
			wp_cache_delete( MAP_PLUGIN_DO_SYNC_LAST_EXECUTION, 'options' );
			wp_cache_delete( 'alloptions', 'options' );

			if( !MyAgilePrivacy::get_option( MAP_PLUGIN_DO_SYNC_NOW, 0 ) )
			{
				if( $this->is_sync_stale( MyAgilePrivacy::get_option( MAP_PLUGIN_DO_SYNC_LAST_EXECUTION, null ), time() ) )
				{
					if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'detected stale sync_last_execution' );

					// raised so that a failure is handled by recover_failed_sync()
					MyAgilePrivacy::update_option( MAP_PLUGIN_DO_SYNC_NOW, 1 );
				}
				else
				{
					$do_sync_now = 0;
					$this->release_sync_lock();
				}
			}
		}

		if( $do_sync_now )
		{
			if( isset( $the_settings['pa'] ) &&
				$the_settings['pa'] == 1
				&& !( isset( $rconfig['forbid_local_js_caching'] ) && $rconfig['forbid_local_js_caching'] == 1 )
			)
			{
				$cdn_basepath = 'https://localcdn.myagileprivacy.com/';
				$manifest_file = 'version_manifest.json';

				if( MAP_DEV_MODE )
				{
					$manifest_filename = plugin_dir_path( MAP_PLUGIN_FILENAME ) .'dev/'.$manifest_file;
					$manifest_content  = @file_get_contents( $manifest_filename );
					$manifest          = ( $manifest_content !== false ) ? json_decode( $manifest_content, true ) : null;
				}
				else
				{
					MyAgilePrivacy::download_remote_file( $cdn_basepath.$manifest_file, $manifest_file );

					$manifest_filename = MyAgilePrivacy::get_base_directory_for_cache().$manifest_file;
					$manifest_content  = @file_get_contents( $manifest_filename );
					$manifest          = ( $manifest_content !== false ) ? json_decode( $manifest_content, true ) : null;
				}

				if( $manifest && isset( $manifest['manifest_version_file'] ) && isset( $manifest['files'] ) && is_array( $manifest['files'] ) )
				{
					if( isset( $rconfig['manifest_added_files'] ) &&
						is_array( $rconfig['manifest_added_files'] ) &&
						count( $rconfig['manifest_added_files'] ) > 0 )
					{
						foreach( $rconfig['manifest_added_files'] as $manifest_added_file_key => $manifest_added_file_value )
						{
							$manifest['files'][ $manifest_added_file_key ] = $manifest_added_file_value;
						}
					}

					$manifest_assoc = array();

					$manifest_assoc['manifest_version_file'] = $manifest['manifest_version_file'];
					$manifest_assoc['files'] = array();

					foreach( $manifest['files'] as $remote_file => $remote_details )
					{
						if( !is_array( $remote_details ) || !isset( $remote_details['version'] ) || !is_string( $remote_details['version'] ) )
						{
							continue;
						}

						$version = $remote_details['version'];
						$iab = isset( $remote_details['iab'] ) ? $remote_details['iab'] : null;
						$remote_url = $cdn_basepath . $remote_file;
						$local_file = basename( $remote_file );

						$this_item = array(
							'filename'			=>	$local_file,
							'version'			=> 	$version,
							'iab'				=> 	$iab,
							'remote_details'	=>	$remote_details,
						);

						$do_get_file = true;

						if( !(
								isset( $rconfig['allow_iab'] ) &&
								$rconfig['allow_iab'] == 1 &&
								$the_settings['enable_iab_tcf']
							) &&
							strpos( $remote_url, "MyAgilePrivacyIabTCF" ) !== false )
						{
							$do_get_file = false;
						}

						if( $do_get_file )
						{
							$manifest_assoc['files'][ $local_file ] = $this_item;
							MyAgilePrivacy::download_remote_file( $remote_url, $local_file, $version, null );
						}
					}

					MyAgilePrivacy::update_option( MAP_MANIFEST_ASSOC, $manifest_assoc );
				}
				else
				{
					MyAgilePrivacy::update_option( MAP_MANIFEST_ASSOC, null );
				}

				// whatever kept it out, a missing main script must not bring the sync back on every page
				if( !MAP_DEV_MODE &&
					!MyAgilePrivacy::cached_file_exists( 'cookie-shield.js' ) &&
					!get_transient( 'map_download_brake' ) )
				{
					set_transient( 'map_download_brake', 1, 12 * HOUR_IN_SECONDS );
				}
			}

			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'START triggered_do_cron_sync' );

			// Get settings
			$the_settings = MyAgilePrivacy::get_settings();

			$sync_result = $this->sync_cookies_and_fixed_texts( false );

			// the new license code gets its own sync: the raised flag stays
			if( $sync_result === 'license_changed' )
			{
				$this->release_sync_lock();
				return;
			}

			// reread from the database: the sync above ran long, and a save may have landed meanwhile
			wp_cache_delete( MAP_PLUGIN_SETTINGS_FIELD, 'options' );
			wp_cache_delete( 'alloptions', 'options' );

			$the_settings = MyAgilePrivacy::get_settings();

			//bof adjust last_sync data

			$this_sync_datetime_human = strtotime( "now" );

			$wp_date_format = MyAgilePrivacy::get_option( 'date_format', null );

			if( $wp_date_format )
			{
				$wp_date_format .= ' H:i:s';

				if( function_exists( 'wp_date' ) )
				{
					$this_sync_datetime_human = wp_date( $wp_date_format, $now );
				}
				else
				{
					$this_sync_datetime_human = date_i18n( $wp_date_format, $now );
				}
			}

			MyAgilePrivacy::update_scan_runtime( array( 'last_sync' => $this_sync_datetime_human ) );

			//eof adjust last_sync data

			//Transition scan mode to live.
			if( $the_settings['scan_mode'] == 'learning_mode' )
			{
				$now = time();

				$scan_runtime = MyAgilePrivacy::get_scan_runtime();

				if( $scan_runtime['learning_mode_last_active_timestamp'] === null )
				{
					MyAgilePrivacy::update_scan_runtime( array( 'learning_mode_last_active_timestamp' => $now ) );
				}
				else
				{
					$turn_to_live = false;

					if( MAP_DEV_MODE )
					{
						if( $now - (int) $scan_runtime['learning_mode_last_active_timestamp'] > 3600 )
						{
							$turn_to_live = true;
						}
					}
					else
					{
						if( $now - (int) $scan_runtime['learning_mode_last_active_timestamp'] > 604800 )
						{
							$turn_to_live = true;
						}
					}

					if( $turn_to_live )
					{
						MyAgilePrivacy::update_scan_runtime( array( 'learning_mode_last_active_timestamp' => null ) );

						// reread right before writing the settings
						wp_cache_delete( MAP_PLUGIN_SETTINGS_FIELD, 'options' );
						wp_cache_delete( 'alloptions', 'options' );

						//Transition scan mode to live.
						$the_settings = MyAgilePrivacy::get_settings();
						$the_settings['scan_mode'] = 'config_finished';

						MyAgilePrivacy::update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_settings );
						$the_settings = MyAgilePrivacy::get_settings();
					}
				}
			}

			MyAgilePrivacy::update_option( MAP_PLUGIN_DO_SYNC_NOW, 0 );

			$now = time();
			MyAgilePrivacy::update_option( MAP_PLUGIN_DO_SYNC_LAST_EXECUTION, $now );

			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'END triggered_do_cron_sync' );

			$this->do_cron_sync_install_counter();

			$this->release_sync_lock();
			MAP_Admin_Notice_Manager::instance()->remove( 'map_sync_busy' );
		}
		else
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'exiting triggered_do_cron_sync' );
		}

		//MyAgilePrivacy::update_option( MAP_PLUGIN_DO_SYNC_NOW, 1 );

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'end triggered_do_cron_sync' );

		return $map_lock_busy ? 'busy' : null;
	}

	// Stores a content baseline for the post.
	private function map_store_textual_baseline( $post_id, $is_cookie )
	{
		if( ! $post_id || is_wp_error( $post_id ) ) return;

		$p = get_post( $post_id );
		if( ! $p ) return;

		$basis = $is_cookie ? ( $p->post_title . $p->post_content ) : $p->post_content;

		update_post_meta( $post_id, "_map_local_textual_baseline", hash( 'sha256', $basis ) );
	}

	// Stores a translations baseline for the post.
	private function map_store_translations_baseline( $post_id, $translations, $is_cookie )
	{
		if( ! $post_id || is_wp_error( $post_id ) ) return;

		$baseline = array();

		if( is_array( $translations ) )
		{
			foreach( $translations as $lang_key => $trans_item )
			{
				if( ! is_array( $trans_item ) ) continue;

				$name = ( isset( $trans_item['name'] ) && is_string( $trans_item['name'] ) ) ? wp_unslash( $trans_item['name'] ) : '';
				$text = ( isset( $trans_item['text'] ) && is_string( $trans_item['text'] ) ) ? wp_unslash( $trans_item['text'] ) : '';

				$basis = $is_cookie ? ( $name . $text ) : $text;

				$baseline[ $lang_key ] = hash( 'sha256', $basis );
			}
		}

		$json = json_encode( $baseline );
		if( ! is_string( $json ) ) return;

		update_post_meta( $post_id, "_map_translations_baseline", wp_slash( $json ) );
	}

	/**
	* Snapshot of the grants carried by rconfig, for a before/after comparison
	 * @access   private
	 */
	private static function map_rconfig_grants( $rconfig )
	{
		$keys = array( 'advanced_authorized', 'stats_auth', 'geo_auth', 'abtest_auth' );
		$out  = array();

		foreach( $keys as $key )
		{
			$out[ $key ] = ( is_array( $rconfig ) && isset( $rconfig[ $key ] ) && $rconfig[ $key ] === true );
		}

		return $out;
	}

	/**
	* Function for executing remote sync
	 * @access   public
	 */
	public function sync_cookies_and_fixed_texts( $bypass_cache=false, $run_dedupe=false )
	{
		// Get settings
		$the_settings = MyAgilePrivacy::get_settings();

		// the footer sync arrives here already holding the lock
		$map_own_sync_lock = !$this->sync_lock_taken;

		if( $map_own_sync_lock && !$this->take_sync_lock() )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( "HALT sync_cookies_and_fixed_texts already in progress" );
			return 'busy';
		}

		MyAgilePrivacy::$doing_internal_write = true;

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( "START sync_cookies_and_fixed_texts" );

		if( function_exists( 'wp_raise_memory_limit' ) )
		{
			wp_raise_memory_limit( 'admin' );
		}

		$currentAndSupportedLanguages = MyAgilePrivacy::getCurrentAndSupportedLanguages();

		if( isset( $the_settings['default_locale'] ) )
		{
			global $locale;

			$old_locale = $locale;
			$locale = $the_settings['default_locale'];
		}

		$urlparts = wp_parse_url( home_url() );
		$domain = ( is_array( $urlparts ) && isset( $urlparts['host'] ) ) ? $urlparts['host'] : '';

		$bypass_cache_check = $bypass_cache;

		if( !$bypass_cache_check )
		{
			//check for records

			$post_status_to_search = array( 'draft', 'publish', '__blocked', '__always_allowed' );

			$cc_args = array(
				'posts_per_page'   	=> 	1,
				'post_type'        	=>	MAP_POST_TYPE_COOKIES,
				'post_status' 		=> 	$post_status_to_search,
				'fields'           	=> 	'ids',
				'no_found_rows'    	=> 	true,
			);

			$cc_query = new WP_Query( $cc_args );

			if( !$cc_query->have_posts() )
			{
				$bypass_cache_check = true;
			}
		}

		$data_to_send = array(
			'action'			=>	'get_cookies_and_fixed_text',
			'software_key'		=>	MAP_SOFTWARE_KEY,
			'hash'				=>	$the_settings['license_code'],
			'domain'			=>	$domain,
			'locale'			=>	$locale,
			'version'			=>	MAP_PLUGIN_VERSION,
			'bypass_cache'		=>	$bypass_cache_check,
			'options_summary'	=>	$this->get_options_summary(),
			'server_data'		=>	MyAgilePrivacy::getServerFootPrint(),
		);

		if( $currentAndSupportedLanguages['with_multilang'] &&
			is_array( $currentAndSupportedLanguages['language_list_codes'] ) )
		{
			$data_to_send['languages'] = implode( ',', $currentAndSupportedLanguages['language_list_codes'] );
		}

		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $data_to_send );

		$action_result = MyAgilePrivacy::call_api( $data_to_send );

		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $action_result );

		// an answer about a license code replaced during the call is discarded
		wp_cache_delete( MAP_PLUGIN_SETTINGS_FIELD, 'options' );
		wp_cache_delete( 'alloptions', 'options' );

		$map_code_check_settings = MyAgilePrivacy::get_settings();
		$map_stored_license_code = ( isset( $map_code_check_settings['license_code'] ) && is_scalar( $map_code_check_settings['license_code'] ) ) ? (string) $map_code_check_settings['license_code'] : '';
		$map_sent_license_code = ( isset( $data_to_send['hash'] ) && is_scalar( $data_to_send['hash'] ) ) ? (string) $data_to_send['hash'] : '';
		$map_license_changed = ( $map_stored_license_code !== $map_sent_license_code );

		$rr = false;
		$pa = 0;
		$is_dm = false;

		if( $map_license_changed ||
			!$action_result ||
			!is_array( $action_result ) ||
			isset( $action_result['internal_error_message'] )
		)
		{
			$rr = false;
		}
		else
		{
			if( MyAgilePrivacy::nullCoalesceArrayItem( $action_result, 'success', false ) )
			{
				$rr = true;

				$license_valid = true;
				$grace_period = false;

				if( !( is_scalar( MyAgilePrivacy::nullCoalesceArrayItem( $action_result, 'paid_license', 0 ) ) && MyAgilePrivacy::intvalPhp8( MyAgilePrivacy::nullCoalesceArrayItem( $action_result, 'paid_license', 0 ) ) > 0 ) )
				{
					$license_user_status = 'Demo license';
					$is_dm = true;

					if( isset( $action_result['error_msg'] ) && is_string( $action_result['error_msg'] ) )
					{
						$license_valid = false;
						$license_user_status = $action_result['error_msg'];
					}
				}
				else
				{
					if( isset( $action_result['grace_period'] ) && $action_result['grace_period'] == 1 )
					{
						$license_user_status = 'Grace period - expiring soon';
						$grace_period = true;
					}
					elseif( isset( $action_result['error_msg'] ) && is_string( $action_result['error_msg'] ) )
					{
						$license_user_status = $action_result['error_msg'];
					}
					else
					{
						$license_user_status = 'License valid';
					}

					$pa = 1;
				}
			}
			else
			{
				$rr = true;
				$license_valid = false;
				$grace_period = false;
				$license_user_status = ( isset( $action_result['error_msg'] ) && is_string( $action_result['error_msg'] ) ) ? $action_result['error_msg'] : null;
			}
		}

		if( $rr )
		{
			// values before the sync, compared further down
			$map_pre_sync_pa = ( isset( $the_settings['pa'] ) ) ? $the_settings['pa'] : 0;
			$map_pre_sync_rconfig = MyAgilePrivacy::get_rconfig();
			$map_pre_sync_grants = self::map_rconfig_grants( $map_pre_sync_rconfig );

			$customer_email = ( isset( $action_result['customer_email'] ) && is_string( $action_result['customer_email'] ) ) ? $action_result['customer_email'] : null;
			$summary_text = ( isset( $action_result['summary_text'] ) && is_string( $action_result['summary_text'] ) ) ? $action_result['summary_text'] : null;
			$license_name = ( isset( $action_result['license_name'] ) && is_string( $action_result['license_name'] ) ) ? $action_result['license_name'] : '';

			$the_settings['license_user_status'] = $license_user_status;
			$the_settings['is_dm'] = $is_dm;
			$the_settings['license_valid'] = $license_valid;
			$the_settings['grace_period'] = $grace_period;
			$the_settings['customer_email'] = $customer_email;
			$the_settings['summary_text'] = $summary_text;
			$the_settings['license_name'] = $license_name;
			$the_settings['wl_b'] = ( isset( $action_result['wl_b'] ) ) ? $action_result['wl_b'] : 0;
			$the_settings['parse_config'] = ( isset( $action_result['parse_config'] ) ) ? $action_result['parse_config'] : null;
			$the_settings['parse_config_version_number'] = ( isset( $action_result['parse_config_version_number'] ) ) ? $action_result['parse_config_version_number'] : null;
			$the_settings['last_legit_sync'] = strtotime( "now" );
			$the_settings['pa'] = $pa;
			$rconfig = ( isset( $action_result['rconfig'] ) ) ? $action_result['rconfig'] : array();
			$l_allowed = ( isset( $action_result['l_allowed'] ) ) ? $action_result['l_allowed'] : array();

			// reread from the database, so a save made during the remote call is not overwritten
			wp_cache_delete( MAP_PLUGIN_SETTINGS_FIELD, 'options' );
			wp_cache_delete( 'alloptions', 'options' );

			$map_fresh_settings = MyAgilePrivacy::get_settings();

			$map_fresh_code = ( is_array( $map_fresh_settings ) && isset( $map_fresh_settings['license_code'] ) && is_scalar( $map_fresh_settings['license_code'] ) ) ? (string) $map_fresh_settings['license_code'] : '';
			$map_license_changed = ( $map_fresh_code !== $map_sent_license_code );

			if( !$map_license_changed )
			{
				MyAgilePrivacy::update_option( MAP_PLUGIN_RCONFIG, $rconfig );
				MyAgilePrivacy::update_option( MAP_PLUGIN_L_ALLOWED, $l_allowed );
			}

			if( !$map_license_changed && is_array( $map_fresh_settings ) )
			{
				$map_sync_owned_fields = array(
					'customer_email', 'grace_period', 'is_dm', 'last_legit_sync', 'license_name',
					'license_user_status', 'license_valid', 'pa', 'parse_config',
					'parse_config_version_number', 'summary_text', 'wl_b',
				);

				foreach( $map_sync_owned_fields as $map_sync_field )
				{
					if( array_key_exists( $map_sync_field, $the_settings ) )
					{
						$map_fresh_settings[ $map_sync_field ] = $the_settings[ $map_sync_field ];
					}
				}

				$the_settings = $map_fresh_settings;
			}

			if( $map_license_changed )
			{
				// the answer belongs to the previous code: nothing of it is written
				$rr = false;
				$rconfig = MyAgilePrivacy::get_rconfig();
				$the_settings = MyAgilePrivacy::get_settings();
			}
			else
			{
				MyAgilePrivacy::update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_settings );
				$the_settings = MyAgilePrivacy::get_settings();

				// refresh the cached frontend config when these values changed
				$map_post_sync_grants = self::map_rconfig_grants( $rconfig );
				if( $map_pre_sync_pa != $pa || $map_pre_sync_grants !== $map_post_sync_grants )
				{
					MyAgilePrivacy::flush_json_cache();
				}

				if( $pa != 1 )
				{
					MyAgilePrivacy::flush_manifest_file_cache();
				}
			}
		}
		else
		{
			$license_user_status = $the_settings['license_user_status'];
			$is_dm = $the_settings['is_dm'];
			$license_valid = $the_settings['license_valid'];
			$grace_period = $the_settings['grace_period'];
			$customer_email = $the_settings['customer_email'];
			$summary_text = $the_settings['summary_text'];
			$rconfig = MyAgilePrivacy::get_rconfig();
		}

		//eof licensing update part

		if( $rr )
		{
			$cookies = ( isset( $action_result['cookies'] ) && is_array( $action_result['cookies'] ) ) ? $action_result['cookies'] : array();
			$fixed_text = ( isset( $action_result['fixed_text'] ) && is_array( $action_result['fixed_text'] ) ) ? $action_result['fixed_text'] : array();
			$countries = ( isset( $action_result['countries'] ) && is_array( $action_result['countries'] ) ) ? array_filter( $action_result['countries'], 'is_array' ) : null;

			if( empty( $countries ) )
			{
				$countries = null;
			}

			$sync_datetime_human = strtotime( "now" );
			$sync_datetime = time();

			$wp_date_format = MyAgilePrivacy::get_option( 'date_format', null );

			if( $wp_date_format )
			{
				$wp_date_format .= ' H:i:s';

				if( function_exists( 'wp_date' ) )
				{
					$sync_datetime_human = wp_date( $wp_date_format, $sync_datetime );
				}
				else
				{
					$sync_datetime_human = date_i18n( $wp_date_format, $sync_datetime );
				}
			}

			if( $action_result &&
				MyAgilePrivacy::nullCoalesceArrayItem( $action_result, 'success', false ) &&
				MyAgilePrivacy::nullCoalesceArrayItem( $action_result, 'valid_license', false )
			)
			{
				//no action on error on multilang
				if( $currentAndSupportedLanguages['prevent_actions'] == false )
				{
					if( $run_dedupe )
					{
						$this->map_collapse_duplicate_posts( MAP_POST_TYPE_POLICY );
						$this->map_collapse_duplicate_posts( MAP_POST_TYPE_COOKIES );
					}

					foreach( $cookies as $k => $v )
					{
						// skip malformed entries
						if( !is_array( $v ) ||
							( isset( $v['name'] ) && !is_scalar( $v['name'] ) ) ||
							( isset( $v['text'] ) && !is_scalar( $v['text'] ) ) )
						{
							continue;
						}

						if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $v );

						$post_status_to_search = array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', '__expired', '__blocked', '__always_allowed', 'trash' );

						$cc_args = array(
							'posts_per_page'   	=> 	-1,
							'post_type'        	=>	MAP_POST_TYPE_COOKIES,
							'meta_key'         	=> 	'_map_remote_id',
							'meta_value'       	=> 	$v['remote_id'],
							'post_status' 		=> 	$post_status_to_search,
						);

						$cc_query = new WP_Query( $cc_args );

						if( $cc_query->have_posts() )
						{
							foreach ( $cc_query->get_posts() as $p )
							{
								$main_post_id = $p->ID;

								$post_type = get_post_type( $main_post_id );

								//double check for strange theme / plugins
								if( $post_type == MAP_POST_TYPE_COOKIES )
								{
									$this_post_status = get_post_status( $main_post_id );

									if( is_array( $post_status_to_search ) &&
										in_array( $this_post_status, $post_status_to_search ) )
									{
										update_post_meta( $main_post_id, "_map_country_code", ( isset( $v['country_code'] ) && is_scalar( $v['country_code'] ) ) ? sanitize_key( (string) $v['country_code'] ) : '' );

										$do_content_sync = false;
										$do_crit_metadata_sync = false;

										$allow_sync = get_post_meta( $main_post_id, '_map_allow_sync', true );

										if( $allow_sync )
										{
											$do_content_sync = true;
										}

										if( $the_settings['enable_metadata_sync'] &&
											isset( $v['forced_metadata_sync'] ) &&
											$v['forced_metadata_sync'] == 1 )
										{
											$do_crit_metadata_sync = true;
										}

										if( $do_content_sync || $do_crit_metadata_sync )
										{
											$name_key_to_read = $v[ 'name' ];
											$text_key_to_read = $v[ 'text' ];

											//update
											$my_post = array(
												'ID'           	=> 	$main_post_id,
											);

											if( $do_content_sync )
											{
												$my_post['post_title'] = $name_key_to_read;
												$my_post['post_content'] = $text_key_to_read;
											}

											$the_post_status = get_post_status( $main_post_id );

											if( $the_post_status == '__expired' )
											{
												$my_post['post_status'] = 'draft';
											}

											//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $my_post );

											wp_update_post( $my_post );

											if( $do_content_sync )
											{
												$this->map_store_textual_baseline( $main_post_id, true );
											}

											update_post_meta( $main_post_id, "_map_name", $v['name'] );
											update_post_meta( $main_post_id, "_map_is_free", $v['is_free'] );
											update_post_meta( $main_post_id, "_map_is_readonly", $v['is_readonly'] );
											update_post_meta( $main_post_id, "_map_help_url", $v['help_url'] );
											update_post_meta( $main_post_id, "_map_api_key", $v['api_key'] );
											if( isset( $v['source_hash'] ) ) update_post_meta( $main_post_id, "_map_source_hash", $v['source_hash'] );
											update_post_meta( $main_post_id, "_map_textual_hash", $v['textual_hash'] );
											update_post_meta( $main_post_id, "_map_suggested_is_necessary", ( $v['is_necessary'] ) ? 'necessary' : 'not-necessary' );


											$this_post_meta = get_post_meta( $main_post_id );

											$_set_page_reload_on_user_consent = isset( $this_post_meta['_map_page_reload_on_user_consent'][0] ) ? true : false;

											if( !$_set_page_reload_on_user_consent )
											{
												update_post_meta( $main_post_id, "_map_page_reload_on_user_consent", $v['page_reload_on_user_consent'] );
											}

											update_post_meta( $main_post_id, "_map_sync_datetime_human", $sync_datetime_human );
											update_post_meta( $main_post_id, "_map_sync_datetime", $sync_datetime );

											if( $do_crit_metadata_sync )
											{
												update_post_meta( $main_post_id, "_map_is_necessary", ( $v['is_necessary'] ) ? 'necessary' : 'not-necessary' );

												update_post_meta( $main_post_id, "_map_page_reload_on_user_consent", $v['page_reload_on_user_consent'] );
											}

											if( $currentAndSupportedLanguages['with_multilang'] )
											{
												if( isset( $v['translations'] ) && $v['translations'] )
												{
													update_post_meta( $main_post_id, "_map_translations", wp_slash( json_encode( $v['translations'] ) ) );

													$this->map_store_translations_baseline( $main_post_id, $v['translations'], true );
												}
											}
										}
										else
										{
											if( get_post_status( $main_post_id ) == '__expired' )
											{
												//update
												$my_post = array(
													'ID'           	=> 	$main_post_id ,
													'post_status'	=> 'draft',
												);

												wp_update_post( $my_post );

												if( $currentAndSupportedLanguages['with_multilang'] )
												{
													if( isset( $v['translations'] ) && $v['translations'] )
													{
														update_post_meta( $main_post_id, "_map_translations", wp_slash( json_encode( $v['translations'] ) ) );

														$this->map_store_translations_baseline( $main_post_id, $v['translations'], true );
													}
												}
											}

											update_post_meta( $main_post_id, "_map_api_key", $v['api_key'] );
											update_post_meta( $main_post_id, "_map_help_url", $v['help_url'] );
											if( isset( $v['source_hash'] ) ) update_post_meta( $main_post_id, "_map_source_hash", $v['source_hash'] );
											update_post_meta( $main_post_id, "_map_textual_hash", $v['textual_hash'] );
										}
									}
								}
							}

							MyAgilePrivacy::internal_query_reset();
						}
						else
						{
							//create
							$name_key_to_read = $v[ 'name' ];
							$text_key_to_read = $v[ 'text' ];

							$meta_input = array(
								'_map_remote_id'					=>	$v['remote_id'],
								'_map_country_code'					=>	( isset( $v['country_code'] ) && is_scalar( $v['country_code'] ) ) ? sanitize_key( (string) $v['country_code'] ) : '',
								'_map_name'							=>	$v['name'],
								'_map_is_free'						=>	$v['is_free'],
								'_map_is_necessary'					=>	( $v['is_necessary'] ) ? 'necessary' : 'not-necessary',
								'_map_suggested_is_necessary'		=>	( $v['is_necessary'] ) ? 'necessary' : 'not-necessary',
								'_map_is_readonly'					=>	$v['is_readonly'],
								'_map_help_url'						=>	$v['help_url'],
								'_map_api_key'						=>	$v['api_key'],
								'_map_source_hash'					=>	isset( $v['source_hash'] ) ? $v['source_hash'] : '',
								'_map_textual_hash'					=>	$v['textual_hash'],
								'_map_allow_sync'					=>	1,
								'_map_page_reload_on_user_consent'	=>	$v['page_reload_on_user_consent'],
								'_map_sync_datetime_human'			=>	$sync_datetime_human,
								'_map_sync_datetime'				=>	$sync_datetime,
							);

							$new_post = array (
								'post_type' 		=>	MAP_POST_TYPE_COOKIES,
								'post_title' 		=>	$name_key_to_read,
								'post_content' 		=>	$text_key_to_read,
								'post_status' 		=>	'draft',
								'comment_status' 	=>	'closed',
								'ping_status' 		=>	'closed',
								'meta_input'		=>	$meta_input,
							);

							//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $new_post );

							$main_post_id = wp_insert_post( $new_post );

							$this->map_store_textual_baseline( $main_post_id, true );

							//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $main_post_id );

							if( $currentAndSupportedLanguages['with_multilang'] )
							{
								if( isset( $v['translations'] ) && $v['translations'] )
								{
									update_post_meta( $main_post_id, "_map_translations", wp_slash( json_encode( $v['translations'] ) ) );

									$this->map_store_translations_baseline( $main_post_id, $v['translations'], true );
								}
							}

						}
					}

					foreach( $fixed_text as $k => $v )
					{
						// skip malformed entries
						if( !is_array( $v ) ||
							( isset( $v['name'] ) && !is_scalar( $v['name'] ) ) ||
							( isset( $v['text'] ) && !is_scalar( $v['text'] ) ) )
						{
							continue;
						}

						//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $v );

						$cc_args = array(
							'posts_per_page'   => 	-1,
							'post_type'        =>	MAP_POST_TYPE_POLICY,
							'meta_key'         => 	'_map_remote_id',
							'meta_value'       => 	$v['remote_id'],
							'post_status'      => 	array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', '__expired', 'trash' )
						);

						$cc_query = new WP_Query( $cc_args );

						if( $cc_query->have_posts() )
						{
							foreach ( $cc_query->get_posts() as $p )
							{
								$main_post_id = $p->ID;

								$post_type = get_post_type( $main_post_id );

								//double check for strange theme / plugins
								if( $post_type == MAP_POST_TYPE_POLICY )
								{
									$allow_sync = get_post_meta( $main_post_id, '_map_allow_sync', true );

									if( $allow_sync == 1 )
									{
										$name_key_to_read = $v[ 'name' ];
										$text_key_to_read = $v[ 'text' ];

										//update
										$my_post = array(
											'ID'           	=> 	$main_post_id ,
											'post_title'   	=> 	$name_key_to_read,
											'post_content' 	=> 	$text_key_to_read,
										);

										$the_post_status = get_post_status( $main_post_id );

										if( get_post_status( $main_post_id ) == '__expired' )
										{
											$my_post['post_status'] = 'publish';

											$the_post_status = 'publish';
										}

										//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $my_post );

										wp_update_post( $my_post );

										$this->map_store_textual_baseline( $main_post_id, false );

										update_post_meta( $main_post_id, "_map_name", $v['name'] );
										update_post_meta( $main_post_id, "_map_is_free", $v['is_free'] );
										update_post_meta( $main_post_id, "_map_sync_datetime_human", $sync_datetime_human );
										update_post_meta( $main_post_id, "_map_sync_datetime", $sync_datetime );

										if( $currentAndSupportedLanguages['with_multilang'] )
										{
											if( isset( $v['translations'] ) && $v['translations'] )
											{
												update_post_meta( $main_post_id, "_map_translations", wp_slash( json_encode( $v['translations'] ) ) );

												$this->map_store_translations_baseline( $main_post_id, $v['translations'], false );
											}
										}
									}
									else
									{
										if( get_post_status( $main_post_id ) == '__expired' )
										{
											//update
											$my_post = array(
												'ID'           	=> 	$main_post_id ,
												'post_status'	=> 'publish',
											);

											wp_update_post( $my_post );

											if( $currentAndSupportedLanguages['with_multilang'] )
											{
												if( isset( $v['translations'] ) && $v['translations'] )
												{
													update_post_meta( $main_post_id, "_map_translations", wp_slash( json_encode( $v['translations'] ) ) );

													$this->map_store_translations_baseline( $main_post_id, $v['translations'], false );
												}
											}
										}
									}

									if( isset( $v['source_hash'] ) ) update_post_meta( $main_post_id, "_map_source_hash", $v['source_hash'] );
									update_post_meta( $main_post_id, "_map_textual_hash", $v['textual_hash'] );
								}
							}
						}
						else
						{
							//create

							$name_key_to_read = $v[ 'name' ];
							$text_key_to_read = $v[ 'text' ];

							//create
							$new_post = array (
								'post_type' 		=>	MAP_POST_TYPE_POLICY,
								'post_title' 		=>	$name_key_to_read,
								'post_content' 		=>	$text_key_to_read,
								'post_status' 		=>	'publish',
								'comment_status' 	=>	'closed',
								'ping_status' 		=>	'closed',
								'meta_input'		=>	array(
															'_map_remote_id'			=>	$v['remote_id'],
															'_map_name'					=>	$v['name'],
															'_map_is_free'				=>	$v['is_free'],
															'_map_source_hash'			=>	isset( $v['source_hash'] ) ? $v['source_hash'] : '',
															'_map_textual_hash'			=>	$v['textual_hash'],
															'_map_allow_sync'			=>	1,
															'_map_sync_datetime_human'	=>	$sync_datetime_human,
															'_map_sync_datetime'		=>	$sync_datetime,
														)
							);

							$main_post_id = wp_insert_post( $new_post );

							$this->map_store_textual_baseline( $main_post_id, false );

							if( $currentAndSupportedLanguages['with_multilang'] )
							{
								if( isset( $v['translations'] ) && $v['translations'] )
								{
									update_post_meta( $main_post_id, "_map_translations", wp_slash( json_encode( $v['translations'] ) ) );

									$this->map_store_translations_baseline( $main_post_id, $v['translations'], false );
								}
							}
						}
					}
				}
			}

			if(
				!( $action_result &&
					MyAgilePrivacy::nullCoalesceArrayItem( $action_result, 'success', false ) &&
					MyAgilePrivacy::nullCoalesceArrayItem( $action_result, 'valid_license', false )
				) || !$license_valid
			)
			{
				if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'make expire' );

				//check if exists

				$post_status_to_search = array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', '__expired', '__blocked', '__always_allowed' );

				//cookies
				$cc_args = array(
					'posts_per_page'  			=> 	-1,
					'post_type'        			=>	MAP_POST_TYPE_COOKIES,
					'meta_key'         			=> 	'_map_is_free',
					'meta_value'       			=> 	0,
					'post_status' 				=> 	$post_status_to_search,
					'no_found_rows'    			=> 	true,
					'update_post_meta_cache'	=> 	false,
					'update_post_term_cache'	=> 	false,
				);

				$cc_query = new WP_Query( $cc_args );

				if( $cc_query->have_posts() )
				{
					foreach ( $cc_query->get_posts() as $p )
					{
						$main_post_id = $p->ID;

						$post_type = get_post_type( $main_post_id );

						//double check for strange theme / plugins
						if( $post_type == MAP_POST_TYPE_COOKIES )
						{
							$this_post_status = get_post_status( $main_post_id );

							if( is_array( $post_status_to_search ) &&
								in_array( $this_post_status, $post_status_to_search ) )
							{
								if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $main_post_id );

								//update
								$my_post = array(
									'ID'           	=> 	$main_post_id ,
									'post_status'	=>	'__expired',
								);

								wp_update_post( $my_post );

								if( $currentAndSupportedLanguages['with_multilang'] )
								{
									if( isset( $v['translations'] ) && $v['translations'] )
									{
										update_post_meta( $main_post_id, "_map_translations", wp_slash( json_encode( $v['translations'] ) ) );
									}
								}
							}
						}
					}

					MyAgilePrivacy::internal_query_reset();
				}

				//check if exists
				$post_status_to_search = array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', '__expired' );

				//fixed texts
				$cc_args = array(
					'posts_per_page'  	=> 	-1,
					'post_type'        	=>	MAP_POST_TYPE_POLICY,
					'meta_key'         	=> 	'_map_is_free',
					'meta_value'       	=> 	0,
					'post_status' 		=> 	$post_status_to_search,
				);

				$cc_query = new WP_Query( $cc_args );

				if( $cc_query->have_posts() )
				{
					foreach ( $cc_query->get_posts() as $p )
					{
						$main_post_id = $p->ID;

						$post_type = get_post_type( $main_post_id );

						//double check for strange theme / plugins
						if( $post_type == MAP_POST_TYPE_POLICY )
						{
							$this_post_status = get_post_status( $main_post_id );

							if( is_array( $post_status_to_search ) &&
								in_array( $this_post_status, $post_status_to_search ) )
							{
								//update
								$my_post = array(
									'ID'           	=> 	$main_post_id ,
									'post_status'	=>	'__expired',
								);

								wp_update_post( $my_post );

								if( $currentAndSupportedLanguages['with_multilang'] )
								{
									if( isset( $v['translations'] ) && $v['translations'] )
									{
										update_post_meta( $main_post_id, "_map_translations", wp_slash( json_encode( $v['translations'] ) ) );
									}
								}
							}
						}
					}

					MyAgilePrivacy::internal_query_reset();
				}
			}

			// an answer without the lists keeps the stored ones: empty lists would put every visitor in block
			if( is_array( $countries ) )
			{
				MyAgilePrivacy::update_option( MAP_PLUGIN_COUNTRIES, $countries );
			}

			if( isset( $action_result['localized_countries'] ) )
			{
				MyAgilePrivacy::update_option( MAP_PLUGIN_LOCALIZED_COUNTRIES, $action_result['localized_countries'] );
			}
		}

		//parsing autoconsume_options

		if( isset( $rconfig ) && isset( $rconfig['autoconsume_options'] ) )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $rconfig['autoconsume_options'] );

			$autoconsume_options = is_string( $rconfig['autoconsume_options'] ) ? json_decode( $rconfig['autoconsume_options'], true ) : null;

			$rconfig['autoconsume_options'] = null;
			MyAgilePrivacy::update_option( MAP_PLUGIN_RCONFIG, $rconfig );

			if( isset( $autoconsume_options['forced'] ) )
			{
				// reached on the failed call too, and after the import loop: the array in hand is old
				wp_cache_delete( MAP_PLUGIN_SETTINGS_FIELD, 'options' );
				wp_cache_delete( 'alloptions', 'options' );

				$map_forced_fresh = MyAgilePrivacy::get_settings();

				if( is_array( $map_forced_fresh ) )
				{
					$the_settings = $map_forced_fresh;
				}

				if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $the_settings );

				if( isset( $autoconsume_options['forced']['forced_auto_update'] ) )
				{
					$the_settings['forced_auto_update'] = ( $autoconsume_options['forced']['forced_auto_update'] == 1 ) ? true : false;
					MyAgilePrivacy::update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_settings );
				}

				if( isset( $autoconsume_options['forced']['enable_metadata_sync'] ) )
				{
					$the_settings['enable_metadata_sync'] = ( $autoconsume_options['forced']['enable_metadata_sync'] == 1 ) ? true : false;
					MyAgilePrivacy::update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_settings );
				}

				if( isset( $autoconsume_options['forced']['learning_mode'] ) && $autoconsume_options['forced']['learning_mode'] == 1 )
				{
					$the_settings['scan_mode'] = 'learning_mode';
					MyAgilePrivacy::update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_settings );
					MyAgilePrivacy::update_scan_runtime( array( 'learning_mode_last_active_timestamp' => null ) );
				}

				if( isset( $autoconsume_options['forced']['reset_consent'] ) && $autoconsume_options['forced']['reset_consent'] == 1 )
				{
					$timestamp = time();
					$the_settings['cookie_reset_timestamp'] = $timestamp;
					MyAgilePrivacy::update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_settings );

					// pages cached before the reset carry the earlier cookie names
					MyAgilePrivacy::flush_json_cache();
					MyAgilePrivacy::schedule_page_cache_flush();
				}

				if( isset( $autoconsume_options['added_cookies'] ) && is_array( $autoconsume_options['added_cookies'] ) && $autoconsume_options['added_cookies'] )
				{
					$added_cookies = $autoconsume_options['added_cookies'];
					$added_cookies = array_filter( $added_cookies );

					if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $added_cookies );

					//no action on error on multilang
					if( $currentAndSupportedLanguages['prevent_actions'] == false )
					{
						foreach( $added_cookies as $k => $v )
						{
							if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $v );

							//check if exists
							$post_status_to_search = array( 'draft', 'publish' );

							$cc_args = array(
								'posts_per_page'   	=> 	-1,
								'post_type'        	=>	MAP_POST_TYPE_COOKIES,
								'meta_key'         	=> 	'_map_remote_id',
								'meta_value'       	=> 	$v,
								'post_status' 		=> 	$post_status_to_search,
							);

							$cc_query = new WP_Query( $cc_args );

							if( $cc_query->have_posts() )
							{
								foreach ( $cc_query->get_posts() as $p )
								{
									$main_post_id = $p->ID;

									$post_type = get_post_type( $main_post_id );

									//double check for strange theme / plugins
									if( $post_type == MAP_POST_TYPE_COOKIES )
									{
										$this_post_status = get_post_status( $main_post_id );

										if( is_array( $post_status_to_search ) &&
											in_array( $this_post_status, $post_status_to_search ) )
										{
											//update
											$my_post = array(
												'ID'           	=> 	$main_post_id,
												'post_status'	=>	'publish',
											);

											if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $my_post );

											wp_update_post( $my_post );

											if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( "published ".$v , true );

											if( $currentAndSupportedLanguages['with_multilang'] )
											{
												//no further actions
											}
										}
									}
								}

								MyAgilePrivacy::internal_query_reset();
							}
						}
					}
				}

				if( isset( $autoconsume_options['forced']['do_empty_cache'] ) && $autoconsume_options['forced']['do_empty_cache'] == 1 )
				{
					MyAgilePrivacy::flush_manifest_file_cache();
					// Invalidate the JSON cache since cookie data has changed
					MyAgilePrivacy::flush_json_cache();
					// third-party page caches are cleared in a request of their own
					MyAgilePrivacy::schedule_page_cache_flush();
				}
			}
		}

		if( $map_own_sync_lock )
		{
			$this->release_sync_lock();
		}

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( "END sync_cookies_and_fixed_texts" );

		//note: any new return added to this function must reset this flag
		MyAgilePrivacy::$doing_internal_write = false;

		if( isset( $old_locale ) )
		{
			$locale = $old_locale;
		}

		MyAgilePrivacy::track_applied_cookie_domain();

		return $map_license_changed ? 'license_changed' : true;
	}


	/**
	 * Removes duplicate synced posts, keeping the best original per remote id. Returns the number removed.
	 */
	private function map_collapse_duplicate_posts( $cpt )
	{
		global $wpdb;

		if( function_exists( 'set_time_limit' ) )
		{
			@set_time_limit( 0 );
		}

		$el  = 'post_' . $cpt;
		$icl = $wpdb->prefix . 'icl_translations';
		$icl_found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $icl ) ) );
		$has_wpml  = ( is_string( $icl_found ) && strtolower( $icl_found ) === strtolower( $icl ) );

		$needs_config = ( $cpt === MAP_POST_TYPE_COOKIES );
		$config_col   = '';
		if( $needs_config )
		{
			$config_col = ", ( SELECT 1 FROM {$wpdb->postmeta} mc WHERE mc.post_id = p.ID AND mc.meta_key IN ( '_map_code', '_map_raw_code', '_map_installation_type' ) AND mc.meta_value <> '' LIMIT 1 ) AS has_config";
		}

		if( $has_wpml )
		{
			$rows = $wpdb->get_results( $wpdb->prepare(
				"SELECT p.ID, p.post_status, p.post_date, pm.meta_value AS rid, t.source_language_code AS src{$config_col}
				 FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_map_remote_id' AND pm.meta_value <> ''
				 LEFT JOIN {$icl} t ON t.element_id = p.ID AND t.element_type = %s
				 WHERE p.post_type = %s", $el, $cpt ) );
		}
		else
		{
			$rows = $wpdb->get_results( $wpdb->prepare(
				"SELECT p.ID, p.post_status, p.post_date, pm.meta_value AS rid{$config_col}
				 FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_map_remote_id' AND pm.meta_value <> ''
				 WHERE p.post_type = %s", $cpt ) );
		}

		if( !$rows || count( $rows ) < 2 )
		{
			return 0;
		}

		$delete_ids = array();
		$groups     = array();

		foreach( $rows as $r )
		{
			if( $has_wpml && $r->src !== null )
			{
				$delete_ids[] = (int) $r->ID;
				continue;
			}
			if( !isset( $groups[ $r->rid ] ) )
			{
				$groups[ $r->rid ] = array();
			}
			$groups[ $r->rid ][] = $r;
		}

		foreach( $groups as $rid => $list )
		{
			if( count( $list ) < 2 )
			{
				continue;
			}

			$keep = null;
			foreach( $list as $r )
			{
				if( $keep === null || ( $needs_config ? $this->map_cookie_is_better( $r, $keep ) : $this->map_policy_is_better( $r, $keep ) ) )
				{
					$keep = $r;
				}
			}
			foreach( $list as $r )
			{
				if( (int) $r->ID !== (int) $keep->ID )
				{
					$delete_ids[] = (int) $r->ID;
				}
			}
		}

		$delete_ids = array_values( array_unique( $delete_ids ) );
		if( empty( $delete_ids ) )
		{
			return 0;
		}

		$total = count( $delete_ids );

		foreach( array_chunk( $delete_ids, 500 ) as $ids )
		{
			$ph = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE post_id IN ({$ph})", $ids ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->term_relationships} WHERE object_id IN ({$ph})", $ids ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->posts} WHERE ID IN ({$ph}) AND post_type = %s", array_merge( $ids, array( $cpt ) ) ) );
			if( $has_wpml )
			{
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$icl} WHERE element_type = %s AND element_id IN ({$ph})", array_merge( array( $el ), $ids ) ) );
			}
			foreach( $ids as $id )
			{
				wp_cache_delete( $id, 'posts' );
				wp_cache_delete( $id, 'post_meta' );
			}
		}

		// Bump the posts cache marker.
		wp_cache_set( 'last_changed', microtime(), 'posts' );

		// Final sweep of orphan WPML rows for this CPT.
		if( $has_wpml )
		{
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM {$icl} WHERE element_type = %s AND element_id NOT IN ( SELECT ID FROM {$wpdb->posts} WHERE post_type = %s )",
				$el, $cpt ) );
		}

		MyAgilePrivacy::flush_json_cache();

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER )
		{
			MyAgilePrivacy::write_log( 'map_collapse_duplicate_posts (' . $cpt . '): removed ' . $total . ' duplicate/translation posts' );
		}

		return $total;
	}

	/**
	 * Chooses which duplicate policy post to keep.
	 */
	private function map_policy_is_better( $a, $b )
	{
		$ap = ( $a->post_status === 'publish' ) ? 1 : 0;
		$bp = ( $b->post_status === 'publish' ) ? 1 : 0;
		if( $ap !== $bp )
		{
			return $ap > $bp;
		}
		if( $a->post_date !== $b->post_date )
		{
			return $a->post_date > $b->post_date;
		}
		return (int) $a->ID > (int) $b->ID;
	}

	/**
	 * Chooses which duplicate cookie post to keep; prefers the user-configured copy.
	 */
	private function map_cookie_is_better( $a, $b )
	{
		$ac = ( !empty( $a->has_config ) ) ? 1 : 0;
		$bc = ( !empty( $b->has_config ) ) ? 1 : 0;
		if( $ac !== $bc )
		{
			return $ac > $bc;
		}
		$at = ( $a->post_status === 'trash' ) ? 0 : 1;
		$bt = ( $b->post_status === 'trash' ) ? 0 : 1;
		if( $at !== $bt )
		{
			return $at > $bt;
		}
		if( $a->post_date !== $b->post_date )
		{
			return $a->post_date > $b->post_date;
		}
		return (int) $a->ID > (int) $b->ID;
	}


	/**
	 * Import Cookie Callback
	 *
	 */
	public function import_admin_settings_form_callback()
	{
		if( !MyAgilePrivacy::map_can_edit() )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( ' import_admin_settings_form_callback -> missing user permission' );
			wp_die( 'Unauthorized', '', 403 );
		}
		// Check nonce:
		check_admin_referer( 'myagileprivacy-update-' . MAP_PLUGIN_SETTINGS_FIELD );

		if( isset( $_FILES['the_imported_file'] ) &&
			$_FILES['the_imported_file']['error'] == UPLOAD_ERR_OK &&
			is_uploaded_file( $_FILES['the_imported_file']['tmp_name'] ) )
		{
			$file_content = file_get_contents( $_FILES['the_imported_file']['tmp_name'] );

			$cookies = json_decode( $file_content, true );

			if( !is_array( $cookies ) ||
				!isset( $cookies['standard'] ) || !is_array( $cookies['standard'] ) ||
				!isset( $cookies['custom'] ) || !is_array( $cookies['custom'] ) )
			{
				return false;
			}

			$currentAndSupportedLanguages = MyAgilePrivacy::getCurrentAndSupportedLanguages();

			$map_scalar_keys = array( 'title', 'content', '_map_installation_type', '_map_code', '_map_raw_code', '_map_is_necessary', '_map_is_readonly', '_map_js_dependencies' );

			foreach( $cookies['standard'] as $k => $v )
			{
				if( !is_array( $v ) )
				{
					continue;
				}

				// skip malformed entries
				foreach( $map_scalar_keys as $map_scalar_key )
				{
					if( isset( $v[ $map_scalar_key ] ) && !is_scalar( $v[ $map_scalar_key ] ) )
					{
						continue 2;
					}
				}

				$post_status_to_search = array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit' );

				$cc_args = array(
					'posts_per_page'   	=> 	-1,
					'post_type'        	=>	MAP_POST_TYPE_COOKIES,
					'meta_key'         	=> 	'_map_api_key',
					'meta_value'       	=> 	$k,
					'post_status' 		=> 	$post_status_to_search,
				);

				$cc_query = new WP_Query( $cc_args );

				if( $cc_query->have_posts() )
				{
					foreach ( $cc_query->get_posts() as $p )
					{
						$main_post_id = $p->ID;

						$post_type = get_post_type( $main_post_id );

						//double check for strange theme / plugins
						if( $post_type == MAP_POST_TYPE_COOKIES )
						{
							$this_post_status = get_post_status( $main_post_id );

							if( is_array( $post_status_to_search ) &&
								in_array( $this_post_status, $post_status_to_search ) )
							{
								$my_post = array(
									'ID'           	=> 	$main_post_id,
									'post_status'	=>	'publish',
								);

								wp_update_post( $my_post );

								//wp slash because of wp_unslash on display
								$this_map_translations = ( isset( $v['_map_translations'] ) && is_string( $v['_map_translations'] ) && $v['_map_translations'] !== '' ) ? wp_slash( $v['_map_translations'] ) : null;

								update_post_meta( $main_post_id, "_map_installation_type", MyAgilePrivacy::nullCoalesceArrayItem( $v, '_map_installation_type' ) );
								update_post_meta( $main_post_id, "_map_code", MyAgilePrivacy::nullCoalesceArrayItem( $v, '_map_code' ) );
								update_post_meta( $main_post_id, "_map_raw_code", MyAgilePrivacy::nullCoalesceArrayItem( $v, '_map_raw_code' ) );
								update_post_meta( $main_post_id, "_map_is_necessary", MyAgilePrivacy::nullCoalesceArrayItem( $v, '_map_is_necessary' ) );
								update_post_meta( $main_post_id, "_map_is_readonly", MyAgilePrivacy::nullCoalesceArrayItem( $v, '_map_is_readonly' ) );
								update_post_meta( $main_post_id, "_map_allow_sync", MyAgilePrivacy::intvalPhp8( MyAgilePrivacy::nullCoalesceArrayItem( $v,'_map_allow_sync' ) ) );
								update_post_meta( $main_post_id, "_map_auto_detected", MyAgilePrivacy::intvalPhp8( MyAgilePrivacy::nullCoalesceArrayItem( $v,'_map_auto_detected' ) ) );
								update_post_meta( $main_post_id, "_map_auto_detected_override", MyAgilePrivacy::intvalPhp8( MyAgilePrivacy::nullCoalesceArrayItem( $v,'_map_auto_detected_override' ) ) );
								update_post_meta( $main_post_id, "_map_js_dependencies", MyAgilePrivacy::nullCoalesceArrayItem( $v, '_map_js_dependencies' ) );
								update_post_meta( $main_post_id, "_map_translations", $this_map_translations );
							}
						}
					}

					MyAgilePrivacy::internal_query_reset();
				}
			}

			foreach( $cookies['custom'] as $k => $v )
			{
				if( !is_array( $v ) )
				{
					continue;
				}

				// skip malformed entries
				foreach( $map_scalar_keys as $map_scalar_key )
				{
					if( isset( $v[ $map_scalar_key ] ) && !is_scalar( $v[ $map_scalar_key ] ) )
					{
						continue 2;
					}
				}

				//wp slash because of wp_unslash on display
				$this_map_translations = ( isset( $v['_map_translations'] ) && is_string( $v['_map_translations'] ) && $v['_map_translations'] !== '' ) ? wp_slash( $v['_map_translations'] ) : null;

				$new_post = array (
					'post_type' 		=>	MAP_POST_TYPE_COOKIES,
					'post_title' 		=>	MyAgilePrivacy::nullCoalesceArrayItem( $v, 'title', '' ),
					'post_content' 		=>	MyAgilePrivacy::nullCoalesceArrayItem( $v, 'content', '' ),
					'post_status' 		=>	'publish',
					'comment_status' 	=>	'closed',
					'ping_status' 		=>	'closed',
					'meta_input'		=>	array(
												'_map_installation_type'		=>	MyAgilePrivacy::nullCoalesceArrayItem( $v, '_map_installation_type' ),
												'_map_code'						=>	MyAgilePrivacy::nullCoalesceArrayItem( $v, '_map_code' ),
												'_map_raw_code'					=>	MyAgilePrivacy::nullCoalesceArrayItem( $v, '_map_raw_code' ),
												'_map_is_necessary'				=>	MyAgilePrivacy::nullCoalesceArrayItem( $v, '_map_is_necessary' ),
												'_map_is_readonly'				=>	MyAgilePrivacy::nullCoalesceArrayItem( $v, '_map_is_readonly' ),
												'_map_allow_sync'				=>	MyAgilePrivacy::intvalPhp8( MyAgilePrivacy::nullCoalesceArrayItem( $v,'_map_allow_sync' ) ),
												'_map_auto_detected'			=>	MyAgilePrivacy::intvalPhp8( MyAgilePrivacy::nullCoalesceArrayItem( $v,'_map_auto_detected' ) ),
												'_map_auto_detected_override'	=>	MyAgilePrivacy::intvalPhp8( MyAgilePrivacy::nullCoalesceArrayItem( $v,'_map_auto_detected_override' ) ),
												'_map_js_dependencies'			=>	MyAgilePrivacy::nullCoalesceArrayItem( $v, '_map_js_dependencies' ),
												'_map_translations'				=>	$this_map_translations,
											)
				);

				$main_post_id = wp_insert_post( $new_post );
			}
		}

	    // Invalidate the JSON cache since cookie data has changed
	    MyAgilePrivacy::flush_json_cache();

		wp_safe_redirect( wp_get_referer() );
		exit;
	}


	/**
	 * Export Cookie Callback
	 *
	 */
	public function backup_admin_settings_form_callback()
	{
		if( !MyAgilePrivacy::map_can_edit() )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'backup_admin_settings_form_callback -> missing user permission' );
			wp_die( 'Unauthorized', '', 403 );
		}

		// Check nonce:
		check_admin_referer( 'myagileprivacy-update-' . MAP_PLUGIN_SETTINGS_FIELD );

		if( function_exists( 'wp_raise_memory_limit' ) )
		{
			wp_raise_memory_limit( 'admin' );
		}

		$export_data = array(
			'standard'				=>	array(),
			'custom'				=>	array(),
			'file_format_version'	=>	MAP_EXPORT_FORMAT_VERSION,
			'map_version'			=>	MAP_PLUGIN_VERSION,
		);

		$currentAndSupportedLanguages = MyAgilePrivacy::getCurrentAndSupportedLanguages();

		$post_status_to_search = array( 'publish' );

		$cc_args = array(
			'posts_per_page'   			=> 	-1,
			'post_type'        			=>	MAP_POST_TYPE_COOKIES,
			'post_status' 				=> 	$post_status_to_search,
			'no_found_rows'    			=> 	true,
			'update_post_meta_cache'	=> 	false,
			'update_post_term_cache'	=> 	false,
		);

		$cc_query = new WP_Query( $cc_args );

		if( $cc_query->have_posts() )
		{
			foreach ( $cc_query->get_posts() as $p )
			{
				$main_post_id = $p->ID;

				$post_type = get_post_type( $main_post_id );

				//double check for strange theme / plugins
				if( $post_type == MAP_POST_TYPE_COOKIES )
				{
					$this_post_status = get_post_status( $main_post_id );

					if( is_array( $post_status_to_search ) &&
						in_array( $this_post_status, $post_status_to_search ) )
					{
						$all_meta = get_post_meta( $main_post_id );

						$key = ( isset( $all_meta['_map_api_key'] ) && $all_meta['_map_api_key'][0]) ? $all_meta['_map_api_key'][0] : null;

						if( $key )
						{
							$this_map_translations = MyAgilePrivacy::nullCoalesceMetaItem( $all_meta, '_map_translations' );

							$elem_to_save = array(
								'_map_installation_type'		=>	MyAgilePrivacy::nullCoalesceMetaItem( $all_meta, '_map_installation_type', '' ),
								'_map_code'						=>	MyAgilePrivacy::nullCoalesceMetaItem( $all_meta, '_map_code' ),
								'_map_raw_code'					=>	MyAgilePrivacy::nullCoalesceMetaItem( $all_meta, '_map_raw_code' ),
								'_map_is_necessary'				=>	MyAgilePrivacy::nullCoalesceMetaItem( $all_meta, '_map_is_necessary', 'not-necessary' ),
								'_map_is_readonly'				=>	MyAgilePrivacy::nullCoalesceMetaItem( $all_meta, '_map_is_readonly', 0 ),
								'_map_allow_sync'				=>	MyAgilePrivacy::nullCoalesceMetaItem( $all_meta, '_map_allow_sync', 1 ),
								'_map_auto_detected'			=>	MyAgilePrivacy::nullCoalesceMetaItem( $all_meta, '_map_auto_detected', 0 ),
								'_map_auto_detected_override'	=>	MyAgilePrivacy::nullCoalesceMetaItem( $all_meta, '_map_auto_detected_override', 0 ),
								'_map_js_dependencies'			=>	MyAgilePrivacy::nullCoalesceMetaItem( $all_meta, '_map_js_dependencies', 0 ),
								'_map_translations'				=>	$this_map_translations,
							);

							$export_data['standard'][ $key ] = $elem_to_save;
						}
						else
						{
							$this_map_translations = MyAgilePrivacy::nullCoalesceMetaItem( $all_meta, '_map_translations' );

							if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $this_map_translations );

							$elem_to_save = array(
								'title'							=>	get_the_title( $main_post_id ),
								'content'						=>	get_post_field( 'post_content', $main_post_id ),
								'_map_installation_type'		=>	MyAgilePrivacy::nullCoalesceMetaItem( $all_meta, '_map_installation_type', '' ),
								'_map_code'						=>	MyAgilePrivacy::nullCoalesceMetaItem( $all_meta, '_map_code' ),
								'_map_raw_code'					=>	MyAgilePrivacy::nullCoalesceMetaItem( $all_meta, '_map_raw_code' ),
								'_map_is_necessary'				=>	MyAgilePrivacy::nullCoalesceMetaItem( $all_meta, '_map_is_necessary', 'not-necessary' ),
								'_map_is_readonly'				=>	0,
								'_map_allow_sync'				=>	0,
								'_map_auto_detected'			=>	0,
								'_map_auto_detected_override'	=>	0,
								'_map_js_dependencies'			=>	MyAgilePrivacy::nullCoalesceMetaItem( $all_meta, '_map_js_dependencies', 0 ),
								'_map_translations'				=>	$this_map_translations,
							);

							$export_data['custom'][] = $elem_to_save;
						}
					}
				}

				wp_cache_delete( $main_post_id, 'post_meta' );
			}

			MyAgilePrivacy::internal_query_reset();
		}

		$json_data = MyAgilePrivacy::jsonEncodeSafe( $export_data );

		if( !headers_sent() )
		{
			header( 'Content-disposition: attachment; filename=export.json' );
			header( 'Content-type: application/json' );
		}

		echo( $json_data );

		return;
	}

	//display scan mode in admin topbar
	public function map_adminbar_cookieshield_link()
	{
		if( !MyAgilePrivacy::map_can_edit() )
		{
			return;
		}

		global $wp_admin_bar;
		$the_settings = MyAgilePrivacy::get_settings();

		$cookie_shield_raw_status = 'turned_off';
		$cookie_shied_value = 'Off';

		if( isset( $the_settings['pa'] ) &&
			$the_settings['pa'] == 1 &&
			isset( $the_settings['scan_mode'] )
		)
		{
			$cookie_shield_raw_status = $the_settings['scan_mode'];
		}

		switch( $cookie_shield_raw_status )
		{
			case 'learning_mode':
				$cookie_shied_value = 'Learning Mode';
			break;

			case 'config_finished':
				$cookie_shied_value = 'Live';
			break;

			case 'turned_off':
				$cookie_shied_value = 'Off';
			break;

			default:
				$cookie_shied_value = 'Off';
			break;
		}

		$this_href = null;

		if( MyAgilePrivacy::map_can_edit() )
		{
			$this_href = admin_url( 'admin.php?page=my-agile-privacy-c_settings#cookieshield' );
		}

		$wp_admin_bar->add_menu( array(
			'id'    => 'map_cookieshield',
			'title' => '<img src="'. plugin_dir_url( __FILE__ ) . 'img/logo.png" alt="" class="map_cookieshield_logo" /> <span class="map_cookieshield_text_status">Cookie Shield: '.$cookie_shied_value.'</span>',
			'href'  => $this_href,
			'meta'  => array(
				'class' => esc_attr( $cookie_shield_raw_status )
			),
		) );

	}


	/**
	 * get options summary for remote validation
	 *
	 */
	public function get_options_summary()
	{
		if( function_exists( 'wp_raise_memory_limit' ) )
		{
			wp_raise_memory_limit( 'admin' );
		}

		$rconfig = MyAgilePrivacy::get_rconfig();

		$cleaned_options = array();
		$cookies = array();
		$policies = array();

		$manifest_assoc = null;

		if( !MAP_DEV_MODE &&
			$rconfig &&
			isset( $rconfig['allow_manifest'] ) &&
			$rconfig['allow_manifest']
		)
		{
			$manifest_assoc = MyAgilePrivacy::get_option( MAP_MANIFEST_ASSOC, null );
		}

		$current_lang = MyAgilePrivacy::get_locale();

		$php_version = null;

		if( defined( 'PHP_MAJOR_VERSION' ) && defined( 'PHP_MINOR_VERSION' ) )
		{
			$php_version = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.0';
		}

		$MyAgilePrivacyRegulationHelper = new MyAgilePrivacyRegulationHelper();
		$frontend_regulation = $MyAgilePrivacyRegulationHelper->getFrontendConfig();

		$filesystem_write_access = MyAgilePrivacy::verify_filesystem_write_access();
		$zip_support = MyAgilePrivacy::verify_zip_support();
		$remote_download = MyAgilePrivacy::verify_remote_download();

		$can_install_and_activate = $filesystem_write_access && $zip_support && $remote_download &&
			MyAgilePrivacy::verify_file_mods_allowed();

		$other_data = array(
			'map_version'					=>	MAP_PLUGIN_VERSION,
			'summary_version'				=>	MAP_SUMMARY_VERSION,
			'php_version'					=>	$php_version,
			'locale'						=>	$current_lang,
			'with_my_agile_pixel'			=> 	false,
			'with_woocommerce'				=>	class_exists( 'woocommerce' ),
			'my_agile_pixel_version'		=> 	null,
			'my_agile_pixel_options'		=> 	null,
			'theme_name'					=> 	null,
			'currentAndSupportedLanguages' 	=> 	MyAgilePrivacy::getCurrentAndSupportedLanguages(),
			'integrityChecks'				=>	MyAgilePrivacy::getGlobalIntegrityChecks( 'backend' ),
			'frontend_regulation'			=>	$frontend_regulation,
			'is_multisite'					=>	is_multisite(),

			'filesystem_write_access'		=>	$filesystem_write_access,
			'zip_support'					=>	$zip_support,
			'remote_download'				=>	$remote_download,
			'can_install_and_activate'		=>	$can_install_and_activate,
		);

		//bof theme calc
		$my_theme = wp_get_theme();
		if( $my_theme && is_object( $my_theme ) )
		{
			$other_data['theme_name'] = $my_theme->get( 'Name' );
		}
		//eof theme calc

		//bof
		$with_my_agile_pixel = false;

		if( !function_exists( 'is_plugin_active' ) )
		{
			include_once(ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		if( is_plugin_active( 'myagilepixel/myagilepixel.php' ) )
		{
			$with_my_agile_pixel = true;

			if( defined( 'MAPX_PLUGIN_VERSION' ) )
			{
				$other_data['my_agile_pixel_version'] = MAPX_PLUGIN_VERSION;
			}

			if( defined( 'MAPX_PLUGIN_SETTINGS_FIELD' ) )
			{
				$pixel_options = MyAgilePrivacy::get_option( MAPX_PLUGIN_SETTINGS_FIELD, null );

				if( is_array( $pixel_options ) )
				{
					foreach( array( 'license_code', 'facebook_access_token', 'tiktok_access_token', 'blacklisted_ip' ) as $pixel_key )
					{
						if( array_key_exists( $pixel_key, $pixel_options ) )
						{
							$pixel_options[ $pixel_key ] = ( isset( $pixel_options[ $pixel_key ] ) ) ? '(set)' : '(not set)';
						}
					}

					$other_data['my_agile_pixel_options'] = $pixel_options;
				}
			}
		}
		$other_data['with_my_agile_pixel'] = $with_my_agile_pixel;

		//eof

		// Get settings
		$the_settings = array_merge( MyAgilePrivacy::get_settings(), MyAgilePrivacy::get_scan_runtime() );
		$do_not_send_in_clear_settings_key = MyAgilePrivacy::get_do_not_send_in_clear_settings_key();

		if( isset( $the_settings['pa'] ) &&
			MyAgilePrivacy::looseEqualsPhp8( $the_settings['pa'], 1 ) &&
			function_exists( 'map_bot_detection_on' ) &&
			map_bot_detection_on() )
		{
			$other_data['bot_diag'] = $this->get_bot_diag();
		}

		$html_scan = $this->get_html_scan_diag();

		if( $html_scan !== '' )
		{
			$other_data['html_scan'] = $html_scan;
		}

		//purge do_not_send fields

		foreach( $the_settings as $k => $v )
		{
			if( is_array( $do_not_send_in_clear_settings_key ) &&
				in_array( $k, $do_not_send_in_clear_settings_key ) )
			{
				$cleaned_options[$k] = ( isset( $v ) ) ? '(set)' : '(not set)';
			}
			else
			{
				$cleaned_options[$k] = $v;
			}
		}

		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $cleaned_options );

		$post_status_to_search = array( 'publish', '__blocked', '__always_allowed' );

		$cc_args = array(
			'posts_per_page'   			=> 	-1,
			'post_type'        			=>	MAP_POST_TYPE_COOKIES,
			'post_status' 				=> 	$post_status_to_search,
			'no_found_rows'    			=> 	true,
			'update_post_meta_cache'	=> 	false,
			'update_post_term_cache'	=> 	false,
		);

		$cc_query = new WP_Query( $cc_args );

		//load cookies
		if( $cc_query->have_posts() )
		{
			foreach ( $cc_query->get_posts() as $p )
			{
				$main_post_id = $p->ID;

				$post_type = get_post_type( $main_post_id );

				//double check for strange theme / plugins
				if( $post_type == MAP_POST_TYPE_COOKIES )
				{
					$this_post_status = get_post_status( $main_post_id );

					if( is_array( $post_status_to_search ) &&
						in_array( $this_post_status, $post_status_to_search ) )
					{
						//retrieve all meta, also customizations (js code)
						$all_meta = get_post_meta( $main_post_id );
						$all_meta_summarized = MyAgilePrivacy::summarizeMeta( $all_meta );

						$key = MyAgilePrivacy::nullCoalesceArrayItem( $all_meta_summarized, '_map_remote_id', null );
						$map_auto_detected = MyAgilePrivacy::nullCoalesceArrayItem( $all_meta_summarized, '_map_auto_detected', false );

						if( $key )
						{
							//calc hash and check

							$do_include = false;

							if( $this_post_status == 'publish' ||
								$this_post_status == '__blocked' ||
								$this_post_status == '__always_allowed' ||
								( $this_post_status == 'draft' && $map_auto_detected == 1 ) )
							{
								$do_include = true;
							}

							if( $do_include )
							{

								if( isset( $all_meta['_map_suggested_is_necessary'] ) &&
									$all_meta['_map_suggested_is_necessary'][0] == 'necessary' &&
									isset( $all_meta['_map_is_necessary'] ) &&
									$all_meta['_map_is_necessary'][0] == 'not-necessary'
								)
								{
									$necessary_value = 'necessary';
								}
								else
								{
									$necessary_value = isset( $all_meta['_map_is_necessary'] ) ? (string)$all_meta['_map_is_necessary'][0] : null;
								}

								$item_for_hash_calculation = array(
									isset( $all_meta['_map_remote_id'] ) ? (string)$all_meta['_map_remote_id'][0] : '',
									isset( $all_meta['_map_api_key'] ) ? (string)$all_meta['_map_api_key'][0] : '',
									$necessary_value,
									isset( $all_meta['_map_is_readonly'] ) ? (string)$all_meta['_map_is_readonly'][0] : null,
								);

								//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $item_for_hash_calculation );

								$recalculated_hash = hash( 'sha256', json_encode( $item_for_hash_calculation ) );
								$recalculated_textual_hash = hash( 'sha256', $p->post_title.$p->post_content );

								//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $recalculated_hash );

								$final_item = array(
									'post_id'					=>	$main_post_id,
									'post_status'				=>	$this_post_status,
									'all_meta' 					=>	$all_meta_summarized,
									'recalculated_hash'			=>	$recalculated_hash,
									'recalculated_textual_hash'	=>	$recalculated_textual_hash,
								);

								$this_map_translations_decoded = ( isset( $all_meta["_map_translations"][0] ) ) ? wp_unslash( json_decode( $all_meta["_map_translations"][0], true ) )  : null;

								if( $this_map_translations_decoded && is_array( $this_map_translations_decoded ) )
								{
									$recalculated_translations_hash = array();

									foreach( $this_map_translations_decoded as $trans_key => $trans_item )
									{
										if( !is_array( $trans_item ) ) continue;

										$hash_lang_item = array(
											'key'	=>	$trans_key,
											'hash'	=>	hash( 'sha256', MyAgilePrivacy::nullCoalesceArrayItem( $trans_item, 'name', '' ).MyAgilePrivacy::nullCoalesceArrayItem( $trans_item, 'text', '' ) )
										);

										$recalculated_translations_hash[ $trans_key ] = $hash_lang_item;
									}

									$final_item['recalculated_translations_hash'] = $recalculated_translations_hash;
								}

								$cookies[ $key ] = $final_item;
							}
						}
						else
						{
							if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $all_meta );
						}
					}
				}

				wp_cache_delete( $main_post_id, 'post_meta' );
			}

			MyAgilePrivacy::internal_query_reset();
		}

		$post_status_to_search = array( 'publish' );

		$cc_args = array(
			'posts_per_page'   			=> 	-1,
			'post_type'        			=>	MAP_POST_TYPE_POLICY,
			'post_status' 				=> 	$post_status_to_search,
			'no_found_rows'    			=> 	true,
			'update_post_meta_cache'	=> 	false,
			'update_post_term_cache'	=> 	false,
		);

		$cc_query = new WP_Query( $cc_args );

		//load policies for any language (not stricly necessary due to synced options)
		if( $cc_query->have_posts() )
		{
			foreach ( $cc_query->get_posts() as $p )
			{
				$main_post_id = $p->ID;

				$post_type = get_post_type( $main_post_id );

				//double check for strange theme / plugins
				if( $post_type == MAP_POST_TYPE_POLICY )
				{
					$this_post_status = get_post_status( $main_post_id );

					if( is_array( $post_status_to_search ) &&
						in_array( $this_post_status, $post_status_to_search ) )
					{
						//retrieve all meta, also customizations (punto c ecc)
						$all_meta = get_post_meta( $main_post_id );

						$key = isset( $all_meta['_map_remote_id'][0] ) ? $all_meta['_map_remote_id'][0] : null;

						if( $key )
						{
							//calc hash and check

							$item_for_hash_calculation = array(
								isset( $all_meta['_map_remote_id'] ) ? (string)$all_meta['_map_remote_id'][0] : null,
								(string)$p->post_title,
							);

							$recalculated_hash = hash( 'sha256', json_encode( $item_for_hash_calculation ) );
							$recalculated_textual_hash = hash( 'sha256', $p->post_content );

							$final_item = array(
								'post_id'					=>	$main_post_id,
								'all_meta' 					=>	MyAgilePrivacy::summarizeMeta( $all_meta ),
								'recalculated_hash'			=>	$recalculated_hash,
								'recalculated_textual_hash'	=>	$recalculated_textual_hash,
							);

							$this_map_translations_decoded = ( isset( $all_meta["_map_translations"][0] ) ) ? wp_unslash( json_decode( $all_meta["_map_translations"][0], true ) )  : null;

							if( $this_map_translations_decoded && is_array( $this_map_translations_decoded ) )
							{
								$recalculated_translations_hash = array();

								foreach( $this_map_translations_decoded as $trans_key => $trans_item )
								{
									if( !is_array( $trans_item ) ) continue;

									$hash_lang_item = array(
										'key'	=>	$trans_key,
										'hash'	=>	hash( 'sha256', (string) MyAgilePrivacy::nullCoalesceArrayItem( $trans_item, 'text', '' ) )
									);

									$recalculated_translations_hash[ $trans_key ] = $hash_lang_item;
								}

								$final_item['recalculated_translations_hash'] = $recalculated_translations_hash;
							}

							$policies[ $key ] = $final_item;
						}
					}
				}

				wp_cache_delete( $main_post_id, 'post_meta' );
			}

			MyAgilePrivacy::internal_query_reset();
		}

		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $cookies );
		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $policies );

		$advanced_usage = ( function_exists( 'map_call_advanced_get_usage_facts' ) ) ? map_call_advanced_get_usage_facts() : array();

		$output = array(
			'cleaned_options' 	=>	$cleaned_options,
			'cookies'			=>	$cookies,
			'policies'			=>	$policies,
			'other_data'		=>	$other_data,
			'manifest_assoc'	=>	$manifest_assoc,
			'advanced_usage'	=>	$advanced_usage,
		);

		$output = array( 'cf_nets_echo' => $this->get_cf_nets_echo() ) + $output;

		$usage_extract = ( function_exists( 'map_call_advanced_get_usage_extract' ) ) ? map_call_advanced_get_usage_extract() : '';

		if( $usage_extract !== '' )
		{
			$output = array( 'usage_extract' => $usage_extract ) + $output;
		}

		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $output );

		return $output;
	}

	/**
	 * Diagnostic scalars for the summary; a field that cannot be read is null.
	 *
	 * @return array
	 */
	private function get_bot_diag()
	{
		$diag = array(
			'd'		=>	null,
			'dd'	=>	null,
			'mig'	=>	null,
			'migd'	=>	null,
			'bs'	=>	null,
			'seen'	=>	null,
		);

		if( !defined( 'MAP_PLUGIN_TRAFFIC_OUTCOME' ) ||
			!defined( 'MAP_PLUGIN_STATS_MIG' ) ||
			!defined( 'MAP_PLUGIN_STATS_BOT_SINCE' ) ||
			!defined( 'MAP_PLUGIN_ADV_SEEN_VERSION' ) )
		{
			return $diag;
		}

		try
		{
			$now = time();

			// all four first: each call also drops the autoloaded set
			MyAgilePrivacy::drop_option_cache( MAP_PLUGIN_TRAFFIC_OUTCOME );
			MyAgilePrivacy::drop_option_cache( MAP_PLUGIN_STATS_MIG );
			MyAgilePrivacy::drop_option_cache( MAP_PLUGIN_STATS_BOT_SINCE );
			MyAgilePrivacy::drop_option_cache( MAP_PLUGIN_ADV_SEEN_VERSION );

			$outcome = get_option( MAP_PLUGIN_TRAFFIC_OUTCOME, null );
			$mark = get_option( MAP_PLUGIN_STATS_MIG, null );
			$since = get_option( MAP_PLUGIN_STATS_BOT_SINCE, null );
			$seen = get_option( MAP_PLUGIN_ADV_SEEN_VERSION, null );

			if( is_array( $outcome ) &&
				isset( $outcome['d'], $outcome['td'] ) &&
				is_int( $outcome['d'] ) &&
				is_int( $outcome['td'] ) &&
				in_array( $outcome['d'], array( 0, 1, 2 ), true ) )
			{
				$diag['d'] = $outcome['d'];
				$diag['dd'] = self::diag_days( $now, $outcome['td'] );
			}

			if( is_array( $mark ) &&
				isset( $mark['t'], $mark['state'] ) &&
				is_string( $mark['t'] ) &&
				preg_match( '/^(\d{1,10})(\.\d{6})?$/D', $mark['t'], $m ) &&
				in_array( $mark['state'], array( 'run', 'fail' ), true ) )
			{
				$diag['mig'] = $mark['state'];
				$diag['migd'] = self::diag_days( $now, (float) $m[1] );
			}

			if( is_string( $since ) &&
				preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/D', $since, $m ) &&
				checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) )
			{
				$diag['bs'] = $since;
			}

			if( is_string( $seen ) &&
				preg_match( '/^([0-9A-Za-z.\-]{1,32})\|(hdr|const)\|(\d{1,10})$/D', $seen, $m ) )
			{
				$diag['seen'] = $m[1];
			}
		}
		catch( Exception $e )
		{
			return $diag;
		}
		catch( Error $e )
		{
			return $diag;
		}

		return $diag;
	}

	/**
	 * Last page served without the full analysis, as one JSON string; empty when none.
	 *
	 * @return string
	 */
	private function get_html_scan_diag()
	{
		if( !defined( 'MAP_PLUGIN_HTML_SCAN_STATE' ) )
		{
			return '';
		}

		try
		{
			$diag = MyAgilePrivacy::get_html_scan_state();

			if( null === $diag )
			{
				return '';
			}

			if( time() - $diag['at'] > MAP_HTML_SCAN_STATE_TTL )
			{
				delete_option( MAP_PLUGIN_HTML_SCAN_STATE );

				return '';
			}

			$json = wp_json_encode( $diag );

			return ( is_string( $json ) ) ? $json : '';
		}
		catch( Exception $e )
		{
			return '';
		}
		catch( Error $e )
		{
			return '';
		}
	}

	/**
	 * Whole days from a past time to now, negative for a time ahead of the clock.
	 *
	 * @param int $now
	 * @param int|float $then
	 * @return int
	 */
	private static function diag_days( $now, $then )
	{
		$days = floor( ( (float) $now - (float) $then ) / 86400 );

		return (int) max( -2147483647, min( 2147483647, $days ) );
	}

	/**
	 * Update Translation Table Callback
	 *
	 */
	public function update_translations_form_callback()
	{
		if( !MyAgilePrivacy::map_can_edit() )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'update_translations_form_callback -> missing user permission' );
			wp_send_json_error( array( 'code' => 'forbidden' ), 403 );
		}

		// Get settings
		$the_settings = MyAgilePrivacy::get_settings();
		$the_settings_save = $the_settings;

		// Check nonce:
		check_admin_referer( 'myagileprivacy-update-' . MAP_PLUGIN_SETTINGS_FIELD );

		if( isset( $_POST['action'] ) && $_POST['action'] == 'update_translations_form' )
		{
			$submitted_translations = ( isset( $_POST['translations'] ) && is_array( $_POST['translations'] ) ) ? $_POST['translations'] : array();
			$sanitized_submitted_translations = array();

			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $submitted_translations );

			foreach( $submitted_translations as $lang => $translations )
			{
				if( !is_array( $translations ) )
				{
					continue;
				}

				if( !isset( $sanitized_submitted_translations[ $lang ] ) ||
					!is_array( $sanitized_submitted_translations[ $lang ] ) )
				{
					$sanitized_submitted_translations[ $lang ] = array();
				}

				foreach( $translations as $key => $value )
				{
					$sanitized_submitted_translations[ $lang ][ $key ] = wp_unslash( sanitize_text_field( $value ) );
				}
			}

			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $sanitized_submitted_translations );

			$map_fixed_translations_json = wp_json_encode( $sanitized_submitted_translations );

			if( false !== $map_fixed_translations_json )
			{
				$the_settings['fixed_translations_encoded'] = $map_fixed_translations_json;

				$the_settings = $this->map_reconciled_settings( $the_settings, array( 'fixed_translations_encoded' ) );

				MyAgilePrivacy::update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_settings );
			}
		}

		$answer = array(
			'success'					=>	true,
			'post_translations' 		=> 	isset( $_POST['translations'] ) ? $_POST['translations'] : array(),
		);

	    // Invalidate the JSON cache since data has changed
	    MyAgilePrivacy::flush_json_cache();

		wp_send_json( $answer );
	}


	/**
	 * Returns the settings reread from the database, with the caller's own fields carried over.
	 *
	 * @param array $current  the array the caller holds
	 * @param array $owned    keys the caller is entitled to write
	 * @return array
	 */
	private function map_reconciled_settings( $current, $owned )
	{
		// the delete is what makes the reread reach the database instead of the request cache
		wp_cache_delete( MAP_PLUGIN_SETTINGS_FIELD, 'options' );
		wp_cache_delete( 'alloptions', 'options' );

		$fresh = MyAgilePrivacy::get_settings();

		if( !is_array( $fresh ) || !is_array( $current ) )
		{
			return $current;
		}

		foreach( array_unique( $owned ) as $field )
		{
			if( array_key_exists( $field, $current ) )
			{
				$fresh[ $field ] = $current[ $field ];
			}
		}

		return $fresh;
	}


	/**
	 * Update Options Callback
	 *
	 */
	public function update_admin_settings_form_callback()
	{
		if( !MyAgilePrivacy::map_can_edit() )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'update_admin_settings_form_callback -> missing user permission' );
			wp_send_json_error( array( 'code' => 'forbidden' ), 403 );
		}

		$currentAndSupportedLanguages = MyAgilePrivacy::getCurrentAndSupportedLanguages();

		$with_missing_fields = false;
		$action_result = null;

		$do_clear_file_cache = false;
		$do_revalidation = false;
		$do_page_cache_flush = false;

		$license_user_status = null;
		$license_valid = null;
		$grace_period = false;
		$customer_email = null;
		$summary_text = null;
		$license_name = '';

		// Get settings
		$the_settings = MyAgilePrivacy::get_settings();
		$the_settings_save = $the_settings;

		// keys this callback is entitled to write, collected as they are assigned
		$map_owned_fields = array();

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $_POST );

		// Check nonce:
		check_admin_referer( 'myagileprivacy-update-' . MAP_PLUGIN_SETTINGS_FIELD );

		// check form submit
		if( isset( $_POST['action'] ) && $_POST['action'] == 'update_admin_settings_form' )
		{
			// Check nonce:
			check_admin_referer( 'myagileprivacy-update-' . MAP_PLUGIN_SETTINGS_FIELD );

			$map_locked_keys = array( 'pa', 'license_valid', 'license_user_status', 'grace_period', 'wl_b', 'summary_text', 'customer_email', 'license_name', 'missing_api_support', 'missing_api_support_timestamp', 'missing_api_support_failures_count', 'missing_api_support_failures_window_start' );

			// these keys keep their stored values
			if( ! function_exists( 'map_stats_enabled' ) || ! map_stats_enabled() )
			{
				$map_locked_keys[] = 'stats_enabled';
				$map_locked_keys[] = 'send_ga4_event_on_consent_change';
			}

			$map_ab_state  = function_exists( 'map_call_advanced_get_ab_state' ) ? map_call_advanced_get_ab_state() : array( 'running' => false, 'panel_url' => '' );
			$map_ab_locked = ( isset( $map_ab_state['running'] ) && $map_ab_state['running'] );

			$map_sync_busy = false;

			// appearance keys keep their stored values while a test is running
			if( $map_ab_locked )
			{
				$map_locked_keys = array_merge( $map_locked_keys, MyAgilePrivacy::get_banner_variant_keys() );
				$map_locked_keys[] = 'stats_enabled';
			}

			$map_stats_migrating = false;

			// the collection switch keeps its stored value while the stats rows are being updated
			if( !$map_ab_locked &&
				isset( $_POST['stats_enabled_field'] ) &&
				is_string( $_POST['stats_enabled_field'] ) &&
				( !empty( $the_settings['stats_enabled'] ) ) !== ( !empty( MyAgilePrivacy::sanitise_settings( 'stats_enabled', $_POST['stats_enabled_field'] ) ) ) &&
				function_exists( 'map_call_advanced_stats_migrating' ) &&
				map_call_advanced_stats_migrating() )
			{
				$map_locked_keys[] = 'stats_enabled';
				$map_stats_migrating = true;
			}

			$map_default_settings = MyAgilePrivacy::get_default_settings();

			foreach( $the_settings as $key => $value )
			{
				if( in_array( $key, $map_locked_keys, true ) )
				{
					continue;
				}

				$map_default_value = ( is_array( $map_default_settings ) && array_key_exists( $key, $map_default_settings ) ) ? $map_default_settings[ $key ] : null;

				if( isset( $_POST[$key] ) && is_array( $_POST[$key] ) && is_array( $value ) )
				{
					$map_posted_value = $_POST[$key];

					// a sub-key sent as an array where the default is not one keeps its stored value
					if( is_array( $map_default_value ) )
					{
						foreach( $map_posted_value as $sub_key => $sub_value )
						{
							if( is_array( $sub_value ) &&
								array_key_exists( $sub_key, $map_default_value ) &&
								!is_array( $map_default_value[ $sub_key ] ) )
							{
								unset( $map_posted_value[ $sub_key ] );
							}
						}
					}

					//store sanitized values only
					$the_settings[ $key ] = MyAgilePrivacy::sanitise_array_settings( $key, $map_posted_value );
					$map_owned_fields[] = $key;
				}
				elseif( isset( $_POST[$key . '_field'] ) && !is_array( $_POST[$key . '_field'] ) && !is_array( $map_default_value ) )
				{
					//store sanitized values only
					$the_settings[ $key ] = MyAgilePrivacy::sanitise_settings( $key, $_POST[$key . '_field'] );
					$map_owned_fields[] = $key;
				}
			};

			//sanitize geo_force_optout (array of country_iso => bool)
			if( isset( $the_settings['site_and_policy_settings']['geo_force_optout'] ) &&
				is_array( $the_settings['site_and_policy_settings']['geo_force_optout'] ) )
			{
				$geo_fo_clean = array();
				foreach( $the_settings['site_and_policy_settings']['geo_force_optout'] as $iso => $val )
				{
					$iso_clean = sanitize_key( $iso );
					if( '' !== $iso_clean )
					{
						$geo_fo_clean[ $iso_clean ] = ( $val === 'true' || $val === true );
					}
				}
				// the key is not claimed here: the default is an array, so this branch runs on every
				// save, and the loop above has already claimed it when the form actually sent it
				$the_settings['site_and_policy_settings']['geo_force_optout'] = $geo_fo_clean;
			}

			if( $the_settings_save['is_on'] == false &&
				$the_settings['is_on'] == true )
			{
				$do_revalidation = true;
				$do_clear_file_cache = true;
			}

			if(
				isset( $the_settings_save['license_code'] ) &&
				isset( $the_settings['license_code'] ) &&
				$the_settings_save['license_code'] == '' &&
				$the_settings_save['license_code'] != $the_settings['license_code'] &&
				$the_settings_save['scan_mode'] == 'turned_off'
			)
			{
				$the_settings['scan_mode'] = 'learning_mode';
				$map_owned_fields[] = 'scan_mode';
			}

			if( $the_settings_save['default_locale'] != $the_settings['default_locale'] )
			{
				$do_revalidation = true;
				$do_clear_file_cache = true;
			}

			if( $the_settings_save['enable_iab_tcf'] != $the_settings['enable_iab_tcf'] )
			{
				$do_clear_file_cache = true;
			}

			if( isset( $_POST['force_sync'] ) )
			{
				$do_revalidation = true;
				$do_clear_file_cache = true;
			}

			if( isset( $_POST['scan_mode_field'] ) &&
				( $_POST['scan_mode_field'] == 'turned_off' || $_POST['scan_mode_field'] == 'config_finished' ) )
			{
				MyAgilePrivacy::update_option( MAP_PLUGIN_JS_DETECTED_FIELDS, '' );
			}

			if( isset( $_POST['reset_consent'] ) )
			{
				$timestamp = time();

				$the_settings['cookie_reset_timestamp'] = $timestamp;
				$map_owned_fields[] = 'cookie_reset_timestamp';
				$do_page_cache_flush = true;
			}

			if( isset( $_POST['reset_cookie_settings'] ) )
			{
				// the posts are removed only by a request that can rebuild them
				if( !$this->take_sync_lock() )
				{
					$map_sync_busy = true;
				}
				else
				{
					try
					{
						// raised first, so that a failure below is rebuilt by the next page
						MyAgilePrivacy::update_option( MAP_PLUGIN_VALIDATION_TIMESTAMP, null );
						MyAgilePrivacy::update_option( MAP_PLUGIN_DO_SYNC_NOW, 1 );

						MyAgilePrivacy::dropCustomPostTypesPosts();

						//refresh cookies (bypass_cache)
						$this->sync_cookies_and_fixed_texts( true );
					}
					finally
					{
						$this->release_sync_lock();
					}
				}
			}

			if( isset( $_POST['reset_settings'] ) )
			{
				$default_settings = MyAgilePrivacy::get_default_settings();

				$do_not_reset_array = array(
					'license_code',
					'license_user_status',
					'license_valid',
					'grace_period',
					'customer_email',
					'summary_text',
					'license_name',
					'default_locale',
					'website_name',
					'identity_name',
					'identity_address',
					'identity_email',
					'pa',
					'wl_b',
					'site_and_policy_settings',
					'stats_enabled',
				);

				// appearance keys keep their stored values while a test is running
				if( $map_ab_locked )
				{
					$do_not_reset_array = array_merge( $do_not_reset_array, MyAgilePrivacy::get_banner_variant_keys() );
				}

				foreach( $default_settings as $key => $value )
				{
					if( is_array( $value ) )
					{
						if( !in_array( $key, $do_not_reset_array ) )
						{
							foreach( $value as $sub_key => $sub_value )
							{
								if( !is_array( $sub_value ) && !in_array( $sub_key, $do_not_reset_array ) )
								{
									$the_settings[ $key ][ $sub_key ] = MyAgilePrivacy::sanitise_settings( $sub_key, $sub_value );
									$map_owned_fields[] = $key;
								}
							}
						}
					}
					else
					{
						if( !in_array( $key, $do_not_reset_array ) )
						{
							$the_settings[ $key ] = MyAgilePrivacy::sanitise_settings( $key, $value );
							$map_owned_fields[] = $key;
						}
					}
				}

				MyAgilePrivacy::update_option( MAP_MANIFEST_ASSOC, null );
				MyAgilePrivacy::update_option( MAP_PLUGIN_SCAN_RUNTIME, MyAgilePrivacy::get_scan_runtime_defaults() );
			}

			$rr = false;
			$pa = 0;
			$is_dm = false;

			$missing_key = false;

			if( !$the_settings['license_valid'] ||
				!$the_settings_save['pa'] ||
				( isset( $_POST['license_code_field'] ) &&
					!MyAgilePrivacy::looseEqualsPhp8( $the_settings_save['license_code'], $_POST['license_code_field'] ) )
			)
			{
				$missing_key = true;
			}

			$now = time();
			$the_timestamp = MyAgilePrivacy::get_option( MAP_PLUGIN_VALIDATION_TIMESTAMP, null );

			if( ( $do_revalidation ||
					$the_timestamp === null ||
					$now - (int) $the_timestamp > 86400 ||
					$missing_key ) &&
				isset( $_POST['license_code_field'] )
			)
			{
				$do_clear_file_cache = true;

				//validation part
				$urlparts = wp_parse_url( home_url() );
				$domain = ( is_array( $urlparts ) && isset( $urlparts['host'] ) ) ? $urlparts['host'] : '';

				$data_to_send = array(
					'action'			=>	'validation',
					'software_key'		=>	MAP_SOFTWARE_KEY,
					'hash'				=>	sanitize_text_field( $_POST['license_code_field'] ),
					'domain'			=>	$domain,
					'version'			=>	MAP_PLUGIN_VERSION,
					'bypass_cache'		=>	( $do_revalidation || $missing_key ) ? 1 : 0,
					'options_summary'	=>	$this->get_options_summary(),
					'server_data'		=>	MyAgilePrivacy::getServerFootPrint(),
				);

				//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $data_to_send );

				$action_result = MyAgilePrivacy::call_api( $data_to_send );

				//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $action_result );

				if( !$action_result ||
					!is_array( $action_result ) ||
					isset( $action_result['internal_error_message'] )
				)
				{
					$rr = false;
				}
				else
				{
					if( MyAgilePrivacy::nullCoalesceArrayItem( $action_result, 'success', false ) )
					{
						$rr = true;

						$license_valid = true;
						$grace_period = false;

						if( !( is_scalar( MyAgilePrivacy::nullCoalesceArrayItem( $action_result, 'paid_license', 0 ) ) && MyAgilePrivacy::intvalPhp8( MyAgilePrivacy::nullCoalesceArrayItem( $action_result, 'paid_license', 0 ) ) > 0 ) )
						{
							$license_user_status = 'Demo license';
							$is_dm = true;

							if( isset( $action_result['error_msg'] ) && is_string( $action_result['error_msg'] ) )
							{
								$license_valid = false;
								$license_user_status = $action_result['error_msg'];
							}
						}
						else
						{
							if( isset( $action_result['grace_period'] ) && $action_result['grace_period'] == 1 )
							{
								$license_user_status = 'Grace period - expiring soon';
								$grace_period = true;
							}
							elseif( isset( $action_result['error_msg'] ) && is_string( $action_result['error_msg'] ) )
							{
								$license_user_status = $action_result['error_msg'];
							}
							else
							{
								$license_user_status = 'License valid';
							}

							$pa = 1;
						}
					}
					else
					{
						$rr = true;
						$license_valid = false;
						$grace_period = false;
						$license_user_status = ( isset( $action_result['error_msg'] ) && is_string( $action_result['error_msg'] ) ) ? $action_result['error_msg'] : null;
					}
				}

				MyAgilePrivacy::update_option( MAP_PLUGIN_VALIDATION_TIMESTAMP, $now );

				if(
					!$license_valid ||
					( $the_settings_save['license_valid'] != $license_valid ) ||
					( $the_settings_save['is_dm'] )
				)
				{
					if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'trigger cookie update via db and hooks' );

					//trigger cookie update	via db and hooks
					MyAgilePrivacy::update_option( MAP_PLUGIN_DO_SYNC_LAST_EXECUTION, null );
					MyAgilePrivacy::update_option( MAP_PLUGIN_DO_SYNC_NOW, 1 );
				}
			}

			if( $do_clear_file_cache )
			{
				// a running sync would switch the flag off after the files are gone
				if( !$this->take_sync_lock() )
				{
					$map_sync_busy = true;
				}
				else
				{
					try
					{
						// raised first, so that a partial flush is rebuilt by the next page
						MyAgilePrivacy::update_option( MAP_PLUGIN_DO_SYNC_NOW, 1 );
						MyAgilePrivacy::flush_manifest_file_cache();
					}
					finally
					{
						$this->release_sync_lock();
					}
				}
			}

			if( $rr )
			{
				$customer_email = ( isset( $action_result['customer_email'] ) && is_string( $action_result['customer_email'] ) ) ? $action_result['customer_email'] : null;
				$summary_text = ( isset( $action_result['summary_text'] ) && is_string( $action_result['summary_text'] ) ) ? $action_result['summary_text'] : null;
				$license_name = ( isset( $action_result['license_name'] ) && is_string( $action_result['license_name'] ) ) ? $action_result['license_name'] : '';

				$the_settings['license_user_status'] = $license_user_status;
				$the_settings['is_dm'] = $is_dm;
				$the_settings['license_valid'] = $license_valid;
				$the_settings['grace_period'] = $grace_period;
				$the_settings['customer_email'] = $customer_email;
				$the_settings['summary_text'] = $summary_text;
				$the_settings['license_name'] = $license_name;
				$the_settings['wl_b'] = ( isset( $action_result['wl_b'] ) ) ? $action_result['wl_b'] : 0;
				$the_settings['parse_config'] = ( isset( $action_result['parse_config'] ) ) ? $action_result['parse_config'] : null;
				$the_settings['parse_config_version_number'] = ( isset( $action_result['parse_config_version_number'] ) ) ? $action_result['parse_config_version_number'] : null;
				$the_settings['last_legit_sync'] = strtotime( "now" );
				$the_settings['pa'] = $pa;
				$map_owned_fields = array_merge( $map_owned_fields, array(
					'license_user_status', 'is_dm', 'license_valid', 'grace_period', 'customer_email',
					'summary_text', 'license_name', 'wl_b', 'parse_config',
					'parse_config_version_number', 'last_legit_sync', 'pa',
				) );

				$rconfig = ( isset( $action_result['rconfig'] ) ) ? $action_result['rconfig'] : array();
				$l_allowed = ( isset( $action_result['l_allowed'] ) ) ? $action_result['l_allowed'] : array();

				MyAgilePrivacy::update_option( MAP_PLUGIN_RCONFIG, $rconfig );
				MyAgilePrivacy::update_option( MAP_PLUGIN_L_ALLOWED, $l_allowed );

				// the remote call above took seconds: do not write back what was read before it
				$the_settings = $this->map_reconciled_settings( $the_settings, $map_owned_fields );

				if( $pa != 1 )
				{
					MyAgilePrivacy::update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_settings );
					MyAgilePrivacy::flush_manifest_file_cache();
				}
			}
			else
			{
				$license_user_status = $the_settings['license_user_status'];
				$is_dm = $the_settings['is_dm'];
				$license_valid = $the_settings['license_valid'];
				$grace_period = $the_settings['grace_period'];
				$customer_email = $the_settings['customer_email'];
				$summary_text = $the_settings['summary_text'];
				$license_name = $the_settings['license_name'];

				$rconfig = MyAgilePrivacy::get_rconfig();
			}

			$lc_hide_local = ( isset( $rconfig ) && isset( $rconfig['lc_hide_local'] ) && MyAgilePrivacy::looseEqualsPhp8( $rconfig['lc_hide_local'], 1 ) ) ? 1 : 0;
			$lc_owner_description = ( isset( $rconfig ) && isset( $rconfig['lc_owner_description'] ) ) ? $rconfig['lc_owner_description'] : null;
			$lc_owner_email = ( isset( $rconfig ) && isset( $rconfig['lc_owner_email'] ) ) ? $rconfig['lc_owner_email'] : null;
			$lc_owner_website = ( isset( $rconfig ) && isset( $rconfig['lc_owner_website'] ) ) ? $rconfig['lc_owner_website'] : null;

			if(
				isset( $the_settings['dont_ask_license_code'] ) &&
				$the_settings['dont_ask_license_code'] &&
				isset( $the_settings['dont_ask_license_code_timestamp'] ) &&
				$the_settings['dont_ask_license_code_timestamp'] == 0
			)
			{
				$the_settings['dont_ask_license_code_timestamp'] = time();
				$map_owned_fields[] = 'dont_ask_license_code_timestamp';
			}

			if(
				isset( $the_settings['dont_ask_license_code'] ) &&
				!$the_settings['dont_ask_license_code'] &&
				isset( $the_settings['dont_ask_license_code_timestamp'] ) &&
				$the_settings['dont_ask_license_code_timestamp'] != 0
			)
			{
				$the_settings['dont_ask_license_code_timestamp'] = 0;
				$map_owned_fields[] = 'dont_ask_license_code_timestamp';
			}

			// second pass: covers the branch where the call failed and never reconciled
			$the_settings = $this->map_reconciled_settings( $the_settings, $map_owned_fields );

			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $the_settings );

			MyAgilePrivacy::update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_settings );

			// Let integrations react to a settings change ( old + new snapshots ).
			do_action( 'map_settings_saved', $the_settings_save, $the_settings );

			if( isset( $_POST['force_sync'] ) )
			{
				MyAgilePrivacy::update_option( MAP_PLUGIN_DO_SYNC_LAST_EXECUTION, null );
				MyAgilePrivacy::update_option( MAP_PLUGIN_DO_SYNC_NOW, 1 );

				//refresh cookies (bypass_cache)
				$sync_result = $this->sync_cookies_and_fixed_texts( true, true );

				if( $sync_result === 'busy' )
				{
					$map_sync_busy = true;
				}
			}

			if( isset( $_POST['force_sync'] ) && !$map_sync_busy )
			{
				//bof adjust last_sync data

				$now = time();
				$this_sync_datetime_human = strtotime( "now" );

				$wp_date_format = MyAgilePrivacy::get_option( 'date_format', null );

				if( $wp_date_format )
				{
					$wp_date_format .= ' H:i:s';

					if( function_exists( 'wp_date' ) )
					{
						$this_sync_datetime_human = wp_date( $wp_date_format, $now );
					}
					else
					{
						$this_sync_datetime_human = date_i18n( $wp_date_format, $now );
					}
				}

				// reread: the sync just above wrote fields that the old array would undo
				wp_cache_delete( MAP_PLUGIN_SETTINGS_FIELD, 'options' );
				wp_cache_delete( 'alloptions', 'options' );

				$the_settings = MyAgilePrivacy::get_settings();

				MyAgilePrivacy::update_scan_runtime( array( 'last_sync' => $this_sync_datetime_human ) );

				//eof adjust last_sync data
			}

			$cookie_shield_raw_status = null;
			$cookie_shied_value = null;

			if( isset( $the_settings ) && $the_settings['scan_mode'] )
			{
				$cookie_shield_raw_status = $the_settings['scan_mode'];

				switch( $cookie_shield_raw_status )
				{
					case 'learning_mode':
						$cookie_shied_value = 'Learning Mode';
					break;

					case 'config_finished':
						$cookie_shied_value = 'Live';
					break;

					case 'turned_off':
						$cookie_shied_value = 'Off';
					break;

					default:
						$cookie_shied_value = 'Off';
					break;
				}
			}

			$answer = array(
				'success'					=>	true,
				'license_user_status'		=>	$license_user_status,
				'is_dm'						=> 	$is_dm,
				'license_valid'				=>	$license_valid,
				'grace_period'				=>	$grace_period,
				'customer_email'			=>	$customer_email,
				'summary_text'				=>	$summary_text,
				'license_name'				=>	$license_name,
				'lc_hide_local'				=>	$lc_hide_local,
				'lc_owner_description'		=>	$lc_owner_description,
				'lc_owner_email'			=>	$lc_owner_email,
				'lc_owner_website'			=>	$lc_owner_website,
				'with_missing_fields'		=>	$with_missing_fields,
				'cookie_shield_raw_status'	=>	$cookie_shield_raw_status,
				'cookie_shied_value' 		=>	$cookie_shied_value,
				'internal_error_message'	=>	( $action_result && isset( $action_result['internal_error_message'] ) ) ? $action_result['internal_error_message'] : null,
			);

			if( $map_sync_busy )
			{
				$answer['sync_busy']         = true;
				$answer['sync_busy_message'] = esc_html__( 'Settings saved. A synchronization is already running: try again in a few minutes.', 'MAP_txt' );
			}

			if( $map_ab_locked )
			{
				// the settings page is the only form carrying this field
				if( isset( $_POST['with_css_effects_field'] ) )
				{
					$answer['ab_locked']         = true;
					$answer['ab_locked_message'] = esc_html__( 'Settings saved. The graphic options did not change: a Banner A/B Test is running.', 'MAP_txt' );
				}

				if( isset( $_POST['stats_enabled_field'] ) )
				{
					$answer['ab_locked_stats'] = true;
				}
			}

			if( $map_stats_migrating )
			{
				$answer['stats_migrating'] = true;
			}

			MyAgilePrivacy::track_applied_cookie_domain();

		    // Invalidate the JSON cache since cookie data has changed
		    MyAgilePrivacy::flush_json_cache();

			// pages cached before a consent reset carry the earlier cookie names
			if( $do_page_cache_flush )
			{
				MyAgilePrivacy::tryOtherPluginCacheClearScoped();
			}

			wp_send_json( $answer );

			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $answer );
		}
	}

	/**
	* Guided Wizard view
	*/
	public function guided_wizard_view()
	{
		// check user capabilities
		if( !MyAgilePrivacy::map_can_edit() )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'guided_wizard_view -> missing user permission' );
			return false;
		}

		// Get settings
		$the_settings = MyAgilePrivacy::get_settings();
		$MyAgilePrivacyRegulationHelper = new MyAgilePrivacyRegulationHelper();
		$site_and_policy_settings = $MyAgilePrivacyRegulationHelper->getSiteAndPolicySettings();
		$available_countries = $MyAgilePrivacyRegulationHelper->getAvailableCountries();
		$rconfig = MyAgilePrivacy::get_rconfig();

		$regulation_config = $MyAgilePrivacyRegulationHelper->getRegulationForBackendEdit();
		$regulation_config_encoded = MyAgilePrivacy::jsonEncodeSafe( $regulation_config );

		if( false === $regulation_config_encoded )
		{
			$regulation_config_encoded = '{}';
		}

		if( !empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) &&
			strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] )== 'xmlhttprequest' )
		{
			exit();
		}

		$css_compatibility_fix = false;

		if( isset( $GLOBALS['wp_version'] ) && version_compare( $GLOBALS['wp_version'], '4.2', '<' ) )
		{
			$css_compatibility_fix = true;
		}

		require_once plugin_dir_path( __FILE__ ).'views/policy_assistant_html.php';
	}


	/**
	 * Function for admin display order fix
	 *
	 * @access   public
	 */
	public function map_order_post_type($query)
	{
		$orderby = MyAgilePrivacy::nullCoalesceArrayItem( $_REQUEST, 'orderby', null );
		$order   = MyAgilePrivacy::nullCoalesceArrayItem( $_REQUEST, 'order', null );

		if( $query->is_admin ) {

			if( $query->get( 'post_type' ) == MAP_POST_TYPE_COOKIES ||
				$query->get( 'post_type' ) == MAP_POST_TYPE_POLICY
			)
			{
				if( !$orderby )
				{
					$query->set( 'orderby', 'title' );
				}

				if( !$order )
				{
					$query->set( 'order', 'ASC' );
				}
			}
		}
		return $query;
	}


	/**
	 * Function for fixing link (MAP_POST_TYPE_COOKIES / MAP_POST_TYPE_POLICY)
	 * loads also css and js
	 *
	 */
	public function map_fix_view_links( $views )
	{
		$do_load = false;

		if( isset( $_GET['post_type'] ) )
		{
			if( $_GET['post_type']  == MAP_POST_TYPE_POLICY ) 
			{
				$do_load = true; 

				?>

					<div class="map-hero">
						<div class="map-avatar">
							<img src="<?php echo plugin_dir_url( __FILE__ ) ?>img/fox-profile.png" alt="">
						</div>

						<div id="_my_agile_privacy_backend" class="policyWrapperView postbox map_infobox">
							<?php echo "<strong>" . wp_kses_post( __( 'Welcome to the Policy list! ', 'MAP_txt' ) ) . "</strong>"; ?><br>
							<?php echo wp_kses_post( __( 'Here you will find a list of all the available policies. You can modify specific options by clicking on the policy.', 'MAP_txt' ) ); ?>
							<div class="mt-2"><?php map_render_helpdesk_link( 'policy_management', null, true, 'policy-list' ); ?></div>
						</div>
					</div>

				<?php
			}
			

			elseif( $_GET['post_type']  == MAP_POST_TYPE_COOKIES )
			{
				$do_load = true;

				unset( $views['all'] );
				unset( $views['mine'] );
				unset( $views['__expired'] );

				$img_url = plugin_dir_url( __FILE__ ) . 'img/fox-profile.png';

			    echo '<div class="map-hero">'
			            . '<div class="map-avatar">'
			                . '<img src="' . esc_url( $img_url ) . '" alt="' . esc_attr__( 'Fox profile', 'MAP_txt' ) . '">'
			            . '</div>'

			            . '<div id="my_agile_privacy_backend" class="cookieWrapperView postbox map_infobox">'
			                . "<strong>" . wp_kses_post( __( 'Welcome to your Cookie list!', 'MAP_txt' ) ) . "</strong>" . '<br>'
			                . wp_kses_post( __( 'In this section, you will find a detailed list of the cookies and third-party services used on your website.', 'MAP_txt' ) ) . '<br>'
			                . wp_kses_post( __( 'We encourage you to use the "Cookie Shield" function in Learning mode to automatically detect the cookies and third-party services active on your site. This feature allows you to easily configure and customize cookie management, enabling you to publish or keep in draft those you consider most appropriate.', 'MAP_txt' ) ) . '<br><br>'
			                . wp_kses_post( __( 'Here is the mapping of the possible states of the Cookies:', 'MAP_txt' ) ) . '<br>'
			                . wp_kses_post( __( '<b>Published</b>: the Cookie configuration is active according to the specified settings.', 'MAP_txt' ) ) . '<br>'
			                . wp_kses_post( __( '<b>Draft</b>: represents a Cookie or a software that you are not currently using, and is available as a "library".', 'MAP_txt' ) ) . '<br>'
			                . wp_kses_post( __( '<b>Blocked without notification</b>: represents a Cookie that you want to block without it appearing in the list of Cookies for which you are requesting consent.', 'MAP_txt' ) ) . '<br>'
			                . wp_kses_post( __( '<b>Allowed without notification</b>: represents a Cookie that you do not wish to block, and you want to prevent it from appearing in the list of cookies. This setting is intended for advanced users and should be used with caution.', 'MAP_txt' ) )
			                . '<div class="mt-2">' . map_render_helpdesk_link( 'cookie_management', null, false, 'cookie-list' ) . '</div>'
			            . '</div>'
			        . '</div>';
			}

			if( $do_load )
			{
				wp_enqueue_style( 'wp-color-picker' );

				wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) ."css/my-agile-privacy-admin.css", array(), $this->version, 'all' );
				wp_enqueue_style( $this->plugin_name.'-bootstrap', plugin_dir_url( __FILE__ ) ."css/bootstrap.min.css", array(), $this->version, 'all' );
				wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/my-agile-privacy-admin.js', array( 'jquery' ,'wp-color-picker' ), $this->version, false );
			}
		}

		return $views;
	}

	/**
	 * Function for adding __blocked / always allowed select to post quick edit
	 */
	function map_fix_post_status_quick_edit()
	{
		global $post;

		$final_output_script = "";

		if( isset( $post ) && $post->post_type == MAP_POST_TYPE_COOKIES )
		{
			$label_blocked = wp_kses_post( __( 'Blocked without notification', 'MAP_txt' ) );
			$label_allowed = wp_kses_post( __( 'Allowed without notification', 'MAP_txt' ) );

			$this_options = '<option value="__blocked">'.$label_blocked.'</option>' .
						'<option value="__always_allowed">'.$label_allowed.'</option>';

			$final_output_script = '<script type="text/javascript">' . PHP_EOL;

			$final_output_script .= '
									jQuery( "select[name=\"_status\"]" ).append( \''.$this_options.'\' );
									'. PHP_EOL;


			$final_output_script .= '</script>' . PHP_EOL;

			echo $final_output_script;
		}
	}


	/**
	 * Function for adding __blocked / always allowed select to post edit
	 */
	function map_fix_post_status_edit()
	{
		global $post;

		$final_output_script = "";

		$complete_blocked = '';
		$complete_allowed = '';

		if( $post->post_type == MAP_POST_TYPE_COOKIES )
		{

			$label_blocked = wp_kses_post( __( 'Blocked without notification', 'MAP_txt' ) );
			$label_allowed = wp_kses_post( __( 'Allowed without notification', 'MAP_txt' ) );

			if( $post->post_status == '__blocked' )
			{
				$complete_blocked = ' selected="selected"';
			}

			if( $post->post_status == '__always_allowed' )
			{
				$complete_allowed = ' selected="selected"';
			}

			$this_options = '<option value="__blocked" '.$complete_blocked.'>'.$label_blocked.'</option>' .
						'<option value="__always_allowed" '.$complete_allowed.'>'.$label_allowed.'</option>';


			$final_output_script = '<script type="text/javascript">' . PHP_EOL;

			$final_output_script .= '
									jQuery("select#post_status").append( \''.$this_options.'\' );
									'. PHP_EOL;

			if( $post->post_status == '__blocked' )
			{
				$final_output_script .= 'jQuery("#post-status-display").text("'.$label_blocked.'");' . PHP_EOL;
			}

			if( $post->post_status == '__always_allowed' )
			{
				$final_output_script .= 'jQuery("#post-status-display").text("'.$label_allowed.'");' . PHP_EOL;
			}

			$final_output_script .= '</script>' . PHP_EOL;

			echo $final_output_script;
		}
	}


	/**
	 * Add Admin Pages
	 *
	 */
	public function add_admin_pages()
	{
		global $submenu;

		$rconfig = MyAgilePrivacy::get_rconfig();

		if( MyAgilePrivacy::map_can_edit() )
		{
			remove_menu_page( 'edit.php?post_type='.MAP_POST_TYPE_POLICY );
		}

		add_submenu_page(
			'edit.php?post_type='.MAP_POST_TYPE_COOKIES,
			__('Dashboard', 'MAP_txt'),
			__('Dashboard', 'MAP_txt'),
			MyAgilePrivacy::get_capability( 'options' ),
			MAP_POST_TYPE_COOKIES.'_dashboard',
			array( $this, 'dashboard_view' )
		);

		add_submenu_page(
			'edit.php?post_type='.MAP_POST_TYPE_COOKIES,
			__('Privacy Settings', 'MAP_txt'),
			__('Privacy Settings', 'MAP_txt'),
			MyAgilePrivacy::get_capability( 'options' ),
			MAP_POST_TYPE_COOKIES.'_settings',
			array( $this, 'admin_page_html' )
		);

		add_submenu_page(
			'edit.php?post_type='.MAP_POST_TYPE_COOKIES,
			__( 'Policy Assistant', 'MAP_txt' ),
			__( 'Policy Assistant', 'MAP_txt' ),
			MyAgilePrivacy::get_capability( 'options' ),
			MAP_POST_TYPE_COOKIES.'_guided_wizard',
			array( $this, 'guided_wizard_view' )
		);

		add_submenu_page(
			'edit.php?post_type='.MAP_POST_TYPE_COOKIES,
			wp_kses_post( __( 'Policies List', 'MAP_txt' ) ),
			wp_kses_post( __( 'Policies List', 'MAP_txt' ) ),
			MyAgilePrivacy::get_capability( 'options' ),
			'edit.php?post_type='.MAP_POST_TYPE_POLICY
		);

		add_submenu_page(
			'edit.php?post_type='.MAP_POST_TYPE_COOKIES,
			wp_kses_post( __( 'Texts and Translations', 'MAP_txt' ) ),
			wp_kses_post( __( 'Texts and Translations', 'MAP_txt' ) ),
			MyAgilePrivacy::get_capability( 'options' ),
			MAP_POST_TYPE_COOKIES.'_translations',
			array( $this, 'translations_view' )
		);

		add_submenu_page(
			'edit.php?post_type='.MAP_POST_TYPE_COOKIES,
			__('Consent Statistics', 'MAP_txt'),
			__('Consent Statistics', 'MAP_txt'),
			MyAgilePrivacy::get_capability( 'options' ),
			MAP_POST_TYPE_COOKIES.'_stats',
			array( $this, 'stats_view' )
		);

		add_submenu_page(
			'edit.php?post_type='.MAP_POST_TYPE_COOKIES,
			__('Banner A/B Test', 'MAP_txt'),
			__('Banner A/B Test', 'MAP_txt'),
			MyAgilePrivacy::get_capability( 'options' ),
			MAP_POST_TYPE_COOKIES.'_ab_test',
			array( $this, 'ab_test_view' )
		);

		add_submenu_page(
			'edit.php?post_type='.MAP_POST_TYPE_COOKIES,
			wp_kses_post( __( 'Backup & Restore', 'MAP_txt' ) ),
			wp_kses_post( __( 'Backup & Restore', 'MAP_txt' ) ),
			MyAgilePrivacy::get_capability( 'options' ),
			MAP_POST_TYPE_COOKIES.'_backup_restore',
			array( $this, 'backup_restore_view' )
		);


		add_submenu_page(
			'edit.php?post_type='.MAP_POST_TYPE_COOKIES,
			wp_kses_post( __( 'Help Desk', 'MAP_txt' ) ),
			wp_kses_post( __( 'Help Desk', 'MAP_txt' ) ),
			MyAgilePrivacy::get_capability( 'options' ),
			MAP_POST_TYPE_COOKIES.'_helpdesk',
			array( $this, 'helpdesk_view' )
		);

		if( self::map_trial_offered() )
		{
			add_submenu_page(
				'edit.php?post_type='.MAP_POST_TYPE_COOKIES,
				wp_kses_post( __( 'Free trial', 'MAP_txt' ) ),
				wp_kses_post( __( 'Free trial', 'MAP_txt' ) ),
				MyAgilePrivacy::get_capability( 'options' ),
				MAP_POST_TYPE_COOKIES.'_trial',
				array( $this, 'trial_view' )
			);
		}

		//reorder settings menu
		if( isset( $submenu ) &&
			is_array( $submenu ) &&
			!empty( $submenu )
		)
		{
			$out = array();
			$settings_menu = array();
			$dashboard_menu = array();
			$policy_assistant_menu = array();

			if( isset( $submenu['edit.php?post_type='.MAP_POST_TYPE_COOKIES] ) && is_array( $submenu['edit.php?post_type='.MAP_POST_TYPE_COOKIES ] ))
			{
				foreach( $submenu['edit.php?post_type='.MAP_POST_TYPE_COOKIES] as $k => $v )
				{
					if( $v[2] != 'post-new.php?post_type='.MAP_POST_TYPE_COOKIES )
					{
						// Slug check
						if( $v[2] == MAP_POST_TYPE_COOKIES.'_settings' )
						{
							$settings_menu = $v;
						}
						elseif( $v[2] == MAP_POST_TYPE_COOKIES.'_dashboard' )
						{
							$dashboard_menu = $v;
						}
						elseif( $v[2] == MAP_POST_TYPE_COOKIES.'_guided_wizard' )
						{
							$policy_assistant_menu = $v;
						}
						else
						{
							if( $v[2] == 'edit.php?post_type='.MAP_POST_TYPE_COOKIES )
							{
								$v[2] = 'edit.php?post_type='.MAP_POST_TYPE_COOKIES.'&post_status=publish';
								$out[$k] = $v;
							}
							else
							{
								$out[$k] = $v;
							}
						}
					}
				}

				//dashboard first

				if( !( isset( $rconfig ) && isset( $rconfig['keep_v1_policies'] ) && $rconfig['keep_v1_policies'] ) )
				{
					array_unshift( $out, $policy_assistant_menu );
				}

				array_unshift( $out, $dashboard_menu );

				//settings next
				array_splice( $out, 1, 0, array( $settings_menu ) );

				$submenu['edit.php?post_type='.MAP_POST_TYPE_COOKIES] = $out;
			}
		}
	}


	/**
	* Backup / restore view
	*/
	public function backup_restore_view()
	{
		// check user capabilities
		if( !MyAgilePrivacy::map_can_edit() )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'backup_restore_view -> missing user permission' );
			return false;
		}

		// Get settings
		$the_settings = MyAgilePrivacy::get_settings();
		$MyAgilePrivacyRegulationHelper = new MyAgilePrivacyRegulationHelper();
		$site_and_policy_settings = $MyAgilePrivacyRegulationHelper->getSiteAndPolicySettings();
		$rconfig = MyAgilePrivacy::get_rconfig();

		if( !empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) &&
			strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] )== 'xmlhttprequest' )
		{
			exit();
		}

		if( !( isset( $rconfig ) &&
				isset( $rconfig['disable_install_counter'] ) &&
				$rconfig['disable_install_counter'] == 1 ) )
		{
			global $map_stats;

			$map_stats = MyAgilePrivacy::get_option( MAP_PLUGIN_STATS, null );
		}


		$css_compatibility_fix = false;

		if( isset( $GLOBALS['wp_version'] ) && version_compare( $GLOBALS['wp_version'], '4.2', '<' ) )
		{
			$css_compatibility_fix = true;
		}

		require_once plugin_dir_path( __FILE__ ).'views/backup_restore_html.php';
	}

	/**
	* Consent Statistics view
	*/
	public function stats_view()
	{
		if( !MyAgilePrivacy::map_can_edit() )
		{
			return;
		}

		if( function_exists( 'map_call_advanced_render_stats' ) && map_call_advanced_render_stats() )
		{
			return;
		}

		$this->map_render_upsell_page(
			'stats',
			__( 'My Agile Privacy®: Consent Statistics', 'MAP_txt' ),
			array(
				__( 'Consent Statistics is a premium feature. Measure how visitors interact with your cookie banner - interaction, consent and opt-in rates, with no personal data collected. Upgrade your license to unlock it.', 'MAP_txt' ),
			)
		);
	}

	/**
	 * Prints the attributes that freeze an appearance control.
	 */
	public static function print_ab_lock_attrs( $locked, $describedby )
	{
		if( !$locked )
		{
			return;
		}

		echo ' disabled aria-describedby="' . esc_attr( $describedby ) . '"';
	}

	public function ab_test_view()
	{
		if( !MyAgilePrivacy::map_can_edit() )
		{
			return;
		}

		if( function_exists( 'map_call_advanced_render_ab_test' ) && map_call_advanced_render_ab_test() )
		{
			return;
		}

		$this->map_render_upsell_page(
			'abtest',
			__( 'My Agile Privacy®: Banner A/B Test', 'MAP_txt' ),
			array(
				__( 'Want to know which Banner configuration earns the most consent? Then activate the license: Banner A/B Test compares up to four configurations on real visitors and applies the winner with one click.', 'MAP_txt' ),
			)
		);
	}

	/**
	 * Whether this site carries a paid license. Read from the setting and not from the bridge, which
	 * does not exist with MAP_ENABLE_ADVANCED_INTEGRATION off.
	 */
	private static function map_has_paid_license()
	{
		$settings = MyAgilePrivacy::get_settings();

		return ( is_array( $settings ) && isset( $settings['pa'] ) && $settings['pa'] == 1 );
	}

	/**
	 * Menu visibility for the "Free trial" item.
	 */
	private static function map_trial_offered()
	{
		// a trial of either kind keeps the item visible: a running trial B switches the three
		// features on, and without this the page disappears exactly while it has something to say
		if( self::map_trial_state() !== 'none' || self::map_trial_b_state() !== 'none' )
		{
			return true;
		}

		// 🔴 a paid license with no trial to show has nothing on that page: what is left there is the
		// form of the other trial, the one for sites without a license, and the server refuses it on a
		// domain that already carries a paying accreditation. Offering it invites a customer to fill in
		// a form and be told no
		if( self::map_has_paid_license() )
		{
			return false;
		}

		if( function_exists( 'map_stats_enabled' ) && map_stats_enabled() &&
			function_exists( 'map_abtest_enabled' ) && map_abtest_enabled() &&
			function_exists( 'map_geo_enabled' ) && map_geo_enabled() )
		{
			return false;
		}

		return true;
	}

	/**
	 * Whether to invite the visitor to ask for a trial. The menu item stays visible while a trial
	 * runs - that page is where its state and end date are - but the invitation does not.
	 */
	public static function map_trial_invitable()
	{
		return ( self::map_trial_offered()
					&& self::map_trial_state() === 'none'
					&& self::map_trial_b_state() === 'none' );
	}

	/**
	 * Trial state of this site: "active" while it runs, "requested" between the request and the
	 * confirmation, "none" otherwise.
	 */
	public static function map_trial_state()
	{
		$rconfig = MyAgilePrivacy::get_rconfig();

		// rconfig is saved verbatim: check the type before use
		if( is_array( $rconfig ) && isset( $rconfig['is_trial'] ) && $rconfig['is_trial'] === true )
		{
			return 'active';
		}

		// a trial that is over, told apart from one never asked: the server sends both keys, and
		// without the second the page would offer the form to someone the server will refuse
		if( is_array( $rconfig ) && isset( $rconfig['trial_used'] ) && $rconfig['trial_used'] === true )
		{
			return 'finished';
		}

		$asked = get_option( MAP_PLUGIN_TRIAL_REQUESTED );

		// the marker expires with the link it stands for: after that the site may ask again
		if( $asked && MyAgilePrivacy::isNumericPhp8( $asked ) && ( time() - MyAgilePrivacy::intvalPhp8( $asked ) ) < ( 8 * HOUR_IN_SECONDS ) )
		{
			return 'requested';
		}

		return 'none';
	}

	/**
	 * Trial B state for this site: active, available, used, or none. Never computed from dates here -
	 * the server decides, against its own clock.
	 */
	public static function map_trial_b_state()
	{
		$rconfig = MyAgilePrivacy::get_rconfig();

		if( !is_array( $rconfig ) )
		{
			return 'none';
		}

		// the server answers for THIS site, so used means used here. A running one arrives with its
		// end date; without it the fifteen days are over
		if( isset( $rconfig['trial_b_used'] ) && $rconfig['trial_b_used'] === true )
		{
			return ( self::map_trial_b_end() !== null ) ? 'active' : 'used';
		}

		if( isset( $rconfig['trial_b_available'] ) && $rconfig['trial_b_available'] === true )
		{
			return 'available';
		}

		return 'none';
	}

	/**
	 * End date of a running trial B, as the server sent it, or null.
	 */
	public static function map_trial_b_end()
	{
		return self::map_trial_date_key( 'trial_b_until' );
	}

	/**
	 * True when the license was sold through a reseller: the page then sends the customer there.
	 */
	public static function map_trial_is_reseller()
	{
		$rconfig = MyAgilePrivacy::get_rconfig();

		return ( is_array( $rconfig ) && isset( $rconfig['is_reseller'] ) && $rconfig['is_reseller'] === true );
	}

	/**
	 * A date key of rconfig, only when it really looks like one. A string that is not a date gives
	 * strtotime false, and date_i18n with false prints TODAY: the site would read "active until today".
	 */
	private static function map_trial_date_key( $key )
	{
		$rconfig = MyAgilePrivacy::get_rconfig();

		if( is_array( $rconfig ) && isset( $rconfig[ $key ] ) && is_string( $rconfig[ $key ] )
			&& preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $rconfig[ $key ], $date_parts )
			&& checkdate( (int) $date_parts[2], (int) $date_parts[3], (int) $date_parts[1] ) )
		{
			return $rconfig[ $key ];
		}

		return null;
	}

	/**
	 * End date of a running trial, or null. Comes from the license server, never computed locally:
	 * two clocks are the defect that bit the other trial.
	 */
	public static function map_trial_end()
	{
		$rconfig = MyAgilePrivacy::get_rconfig();

		return self::map_trial_date_key( 'trial_end' );
	}

	/**
	 * Link to the plans page; $page ( 'stats' or 'abtest' ) adds the campaign parameters.
	 */
	public static function map_trial_plans_url( $page = '' )
	{
		$url = ( MAP_Helpdesk_Links::get_language_key() === 'it' )
				? 'https://www.myagileprivacy.com/'
				: 'https://www.myagileprivacy.com/en/';

		$campaigns = array(
			'stats'  => 'upsell_stats',
			'abtest' => 'upsell_abtest',
		);

		if( !is_string( $page ) || !isset( $campaigns[ $page ] ) )
		{
			return $url;
		}

		$args = array(
			'utm_source'   => 'referral',
			'utm_medium'   => self::map_has_paid_license() ? 'plugin-pro' : 'plugin-basic',
			'utm_campaign' => $campaigns[ $page ],
		);

		if( self::map_trial_state() === 'active' )
		{
			$args['utm_content'] = 'trial';
		}

		return add_query_arg( $args, $url );
	}

	/**
	 * Whether the license covers the feature of $page.
	 */
	private static function map_upsell_license_includes( $page )
	{
		if( !self::map_has_paid_license() )
		{
			return false;
		}

		$rconfig = MyAgilePrivacy::get_rconfig();

		if( !is_array( $rconfig ) )
		{
			return false;
		}

		$keys = ( $page === 'abtest' ) ? array( 'advanced_authorized', 'stats_auth', 'abtest_auth' ) : array( 'advanced_authorized', 'stats_auth' );

		foreach( $keys as $key )
		{
			if( !isset( $rconfig[ $key ] ) || $rconfig[ $key ] !== true )
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Whether the automatic setup can switch the feature on by itself.
	 */
	private static function map_upsell_turns_on_by_itself()
	{
		if( !function_exists( 'map_is_advanced_active' ) || !class_exists( 'MAP_Package_Installer' ) )
		{
			return false;
		}

		$remote_version = map_advanced_get_remote_version();

		if( $remote_version === '' || map_advanced_get_download_url() === '' )
		{
			return false;
		}

		$error = get_option( MAP_PLUGIN_ADVANCED_INSTALL_ERROR, array() );

		if( is_array( $error ) && !empty( $error['timestamp'] ) && ( time() - (int) $error['timestamp'] ) < DAY_IN_SECONDS )
		{
			return false;
		}

		if( MAP_Admin_Notice_Manager::instance()->has( 'map_advanced_state' ) )
		{
			return false;
		}

		$installer = new MAP_Package_Installer();

		if( $installer->auto_install_blocker() === '' )
		{
			return true;
		}

		// the setup on admin_init may have activated it earlier in this same request
		return ( $installer->is_advanced_installed()
					&& $installer->get_installed_advanced_version() === $remote_version
					&& ( !$installer->is_advanced_active() || did_action( 'activate_' . MAP_Package_Installer::MAP_ADVANCED_BASENAME ) ) );
	}

	/**
	 * State of the page of a feature that is not available.
	 */
	private static function map_upsell_state( $page )
	{
		if( self::map_upsell_license_includes( $page ) )
		{
			if( function_exists( 'map_is_advanced_active' ) && map_is_advanced_active() )
			{
				return '0d';
			}

			if( !current_user_can( 'manage_options' ) )
			{
				return '0c';
			}

			if( self::map_upsell_turns_on_by_itself() )
			{
				return '0a';
			}

			if( MAP_Admin_Notice_Manager::instance()->has( 'map_advanced_state' ) )
			{
				return '0b';
			}

			return '0d';
		}

		if( self::map_trial_b_state() === 'available' )
		{
			return 'trial_b';
		}

		if( self::map_trial_state() === 'active' )
		{
			return 'trial';
		}

		if( !self::map_has_paid_license() )
		{
			return 'plans';
		}

		return 'unavailable';
	}

	/**
	 * Prints the "See the plans" button of the page of a feature that is not available.
	 */
	private static function map_print_plans_button( $page )
	{
		echo '<p class="mt-3"><a class="button-agile" href="' . esc_url( self::map_trial_plans_url( $page ) ) . '" target="_blank" rel="noopener">'
			. esc_html__( 'See the plans', 'MAP_txt' )
			. '<span class="screen-reader-text"> ' . esc_html__( 'opens in a new tab', 'MAP_txt' ) . '</span>'
			. '</a></p>';
	}

	/**
	 * Renders the page of a feature that is not available.
	 */
	private function map_render_upsell_page( $page, $title, $paragraphs )
	{
		$state = self::map_upsell_state( $page );

		$included = array(
			'0a' => __( 'Your license includes this feature. It switches on by itself within a few minutes: then reload this page and turn it on.', 'MAP_txt' ),
			'0b' => __( 'Your license includes this feature: follow the My Agile Privacy® notice at the top of this page to activate it.', 'MAP_txt' ),
			'0c' => __( 'Your license includes this feature: ask a site administrator to activate it.', 'MAP_txt' ),
			'0d' => __( 'Your license includes this feature. If it is still off tomorrow, contact support.', 'MAP_txt' ),
		);

		echo '<div class="wrap dashboardOptionsWrapper" id="my_agile_privacy_backend">';
		echo '<h1>' . esc_html( $title ) . '</h1>';
		echo '<div class="container-fluid"><div class="row"><div class="col-md-9"><div class="consistent-box">';
		echo '<p><span class="badge rounded-pill forbiddenWarning bg-danger"><small>' . esc_html__( 'Premium Feature', 'MAP_txt' ) . '</small></span></p>';

		if( !isset( $included[ $state ] ) )
		{
			$map_first = true;

			foreach( $paragraphs as $map_paragraph )
			{
				echo '<p' . ( $map_first ? ' class="lead"' : '' ) . '>' . esc_html( $map_paragraph ) . '</p>';

				$map_first = false;
			}
		}

		if( isset( $included[ $state ] ) )
		{
			echo '<div class="alert alert-info mt-3 mb-0">' . esc_html( $included[ $state ] );

			if( $state === '0d' )
			{
				echo ' <a href="' . esc_url( MAP_Helpdesk_Links::get( 'contact' ) ) . '" class="alert-link">' . esc_html__( 'Contact us', 'MAP_txt' ) . '</a>';
			}

			echo '</div>';
		}
		elseif( $state === 'trial_b' )
		{
			echo '<p class="mt-3"><a class="button-agile" href="' . esc_url( admin_url( 'edit.php?post_type=' . MAP_POST_TYPE_COOKIES . '&page=' . MAP_POST_TYPE_COOKIES . '_trial' ) ) . '">'
			   . esc_html__( 'Request a free trial', 'MAP_txt' )
			   . '</a></p>';
		}
		elseif( $state === 'trial' )
		{
			echo '<div class="alert alert-info mt-3 mb-0">' . esc_html__( 'This feature is not included in the free trial.', 'MAP_txt' ) . '</div>';
			self::map_print_plans_button( $page );
		}
		elseif( $state === 'plans' )
		{
			self::map_print_plans_button( $page );
		}
		else
		{
			echo '<div class="alert alert-info mt-3 mb-0">' . esc_html__( 'This feature is not available with your license.', 'MAP_txt' ) . '</div>';
		}

		echo '</div></div></div></div>';
		echo '</div>';
	}


	/**
	 * Returns the domain the trial is requested for.
	 */
	public static function map_trial_domain()
	{
		$host = function_exists( 'get_site_url' ) ? get_site_url() : '';
		$parts = parse_url( $host );

		if( is_array( $parts ) && isset( $parts['host'] ) && $parts['host'] )
		{
			return $parts['host'];
		}

		return $host;
	}

	/**
	 * Free trial request. Talks to the license server; the trial is only created after the
	 * requester confirms by email.
	 */
	public function map_request_trial()
	{
		if( !MyAgilePrivacy::map_can_edit() )
		{
			wp_send_json_error( array( 'code' => 'forbidden' ), 403 );
		}

		// own nonce: this family writes and reaches the network, the license check only reads
		check_ajax_referer( 'map_request_trial', 'security' );

		$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$email = ( isset( $_POST['email'] ) && is_string( $_POST['email'] ) ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$type  = ( isset( $_POST['user_type'] ) && is_string( $_POST['user_type'] ) ) ? sanitize_key( wp_unslash( $_POST['user_type'] ) ) : '';

		// the consent is OPTIONAL: the trial starts either way
		$consent = ( isset( $_POST['marketing_consent'] ) && MyAgilePrivacy::looseEqualsPhp8( $_POST['marketing_consent'], 1 ) ) ? 1 : 0;

		// strict rule on the name: it travels inside three messages signed by our domain, towards an
		// address that may belong to a third party. No newlines, no markup, no length to hide in
		$name = trim( preg_replace( '/[\r\n\t]+/', ' ', $name ) );
		$name = preg_replace( '/\s{2,}/', ' ', $name );

		if( function_exists( 'mb_substr' ) )
		{
			$name = mb_substr( $name, 0, 120 );
		}
		else
		{
			$name = substr( $name, 0, 120 );
		}

		if( $name === '' || !is_email( $email ) )
		{
			wp_send_json_error( array( 'code' => 'invalid' ), 400 );
		}

		if( $type !== 'azienda' && $type !== 'privato' )
		{
			$type = 'privato';
		}

		// the language is the one of the ADMINISTRATOR filling the form, not the site's:
		// MyAgilePrivacy::get_locale() goes through get_user_locale() on WP 4.7 and later
		$language = MAP_Helpdesk_Links::get_language_key();

		$answer = MyAgilePrivacy::call_api( array(
			'action'            => 'trial_request',
			'software_key'      => 'map_wp',
			'domain'            => self::map_trial_domain(),
			'name'              => $name,
			'email'             => $email,
			'user_type'         => $type,
			'language'          => $language,
			'marketing_consent' => $consent,
		) );

		// the answer is saved verbatim nowhere and read defensively: an unusable response must not
		// become a success, and must not throw on an array where a string is expected
		if( !is_array( $answer ) || isset( $answer['internal_error_message'] ) )
		{
			wp_send_json_error( array( 'code' => 'unreachable' ), 502 );
		}

		if( empty( $answer['success'] ) )
		{
			$code = ( isset( $answer['code'] ) && is_string( $answer['code'] ) ) ? sanitize_key( $answer['code'] ) : 'refused';

			wp_send_json_error( array( 'code' => $code ), 200 );
		}

		// remembered locally so the page does not offer a second request while the first link is
		// still valid: the license server knows nothing until the requester confirms
		update_option( MAP_PLUGIN_TRIAL_REQUESTED, time() );

		wp_send_json_success( array( 'email' => $email ) );
	}

	/**
	 * Trial B request. The customer already holds a valid license, so there is nothing to confirm by
	 * mail: the answer is the grant itself.
	 */
	public function map_request_trial_b()
	{
		if( !MyAgilePrivacy::map_can_edit() )
		{
			wp_send_json_error( array( 'code' => 'forbidden' ), 403 );
		}

		check_ajax_referer( 'map_request_trial_b', 'security' );

		$settings = MyAgilePrivacy::get_settings();

		$hash = ( is_array( $settings ) && isset( $settings['license_code'] ) ) ? trim( (string)$settings['license_code'] ) : '';

		if( $hash === '' )
		{
			wp_send_json_error( array( 'code' => 'no_licence' ), 400 );
		}

		$answer = MyAgilePrivacy::call_api( array(
			'action'       => 'trial_b_request',
			'software_key' => 'map_wp',
			'hash'         => $hash,
			'domain'       => self::map_trial_domain(),
		) );

		if( !is_array( $answer ) || isset( $answer['internal_error_message'] ) )
		{
			wp_send_json_error( array( 'code' => 'unreachable' ), 502 );
		}

		if( empty( $answer['success'] ) )
		{
			$code = ( isset( $answer['code'] ) && is_string( $answer['code'] ) ) ? sanitize_key( $answer['code'] ) : 'refused';

			wp_send_json_error( array( 'code' => $code ), 200 );
		}

		// the grant is already written on the server: without a resync the site would see nothing
		// change for half a day while the fifteen days run
		MyAgilePrivacy::update_option( MAP_PLUGIN_DO_SYNC_NOW, 1 );
		delete_transient( 'map_sync_pause' );

		// synced here and not left to admin_footer: the reload that follows draws the page before its footer
		$this->triggered_do_cron_sync();

		wp_send_json_success( array() );
	}

	/**
	 * Renders the "Free trial" page.
	 */
	public function trial_view()
	{
		if( !MyAgilePrivacy::map_can_edit() )
		{
			return;
		}

		require_once plugin_dir_path( __FILE__ ).'views/trial_html.php';
	}

	public function dashboard_view()
	{
		// check user capabilities
		if( !MyAgilePrivacy::map_can_edit() )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'dashboard_view -> missing user permission' );
			return false;
		}

		// Get settings
		$the_settings = MyAgilePrivacy::get_settings();
		$MyAgilePrivacyRegulationHelper = new MyAgilePrivacyRegulationHelper();
		$site_and_policy_settings = $MyAgilePrivacyRegulationHelper->getSiteAndPolicySettings();
		$rconfig = MyAgilePrivacy::get_rconfig();

		$settings_menu_link = admin_url( 'edit.php?post_type=my-agile-privacy-c&page=my-agile-privacy-c_settings' );
		$cookie_list_menu_link = admin_url( 'edit.php?post_type=my-agile-privacy-c&&post_status=publish' );
		$policy_assistant_menu_link = admin_url( 'edit.php?post_type=my-agile-privacy-c&page=my-agile-privacy-c_guided_wizard' );

		if( !empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) &&
			strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] )== 'xmlhttprequest' )
		{
			exit();
		}

		$css_compatibility_fix = false;

		if( isset( $GLOBALS['wp_version'] ) && version_compare( $GLOBALS['wp_version'], '4.2', '<' ) )
		{
			$css_compatibility_fix = true;
		}

		$locale = MyAgilePrivacy::get_locale();

		global $wpdb;

		//get global integrity checks
		$global_integrity_checks = MyAgilePrivacy::getGlobalIntegrityChecks( 'frontend' );

		$is_on = isset( $the_settings['is_on'] ) ? $the_settings['is_on'] : false;

		// Secure queries using prepared statements
		$cookie_published_count = $wpdb->get_var(
		    $wpdb->prepare(
		        "SELECT COUNT(DISTINCT pm.meta_value)
		         FROM {$wpdb->posts} p
		         INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
		         WHERE p.post_type = %s
		           AND p.post_status = %s
		           AND pm.meta_key = %s",
		        MAP_POST_TYPE_COOKIES,
		        'publish',
		        '_map_api_key'
		    )
		);
		$last_scan_date_human = '-';

		$scan_runtime = MyAgilePrivacy::get_scan_runtime();

		if( $scan_runtime['last_scan_date_internal'] )
		{
			$last_scan_date_human = $scan_runtime['last_scan_date_internal'];

			$wp_date_format = MyAgilePrivacy::get_option( 'date_format', null );

			if( $wp_date_format )
			{
				if( function_exists( 'wp_date' ) )
				{
					$last_scan_date_human = wp_date( $wp_date_format, $scan_runtime['last_scan_date_internal'] );
				}
				else
				{
					$last_scan_date_human = date_i18n( $wp_date_format, $scan_runtime['last_scan_date_internal'] );
				}
			}
		}

		$regulation_selected = $MyAgilePrivacyRegulationHelper->getRegulationsSelected( true );

		require_once plugin_dir_path( __FILE__ ).'views/dashboard_html.php';
	}

	/**
	* Help Desk report view
	*/
	public function helpdesk_view()
	{
		// check user capabilities
		if( !MyAgilePrivacy::map_can_edit() )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'helpdesk_view -> missing user permission' );
			return false;
		}

		// Get settings
		$the_settings = MyAgilePrivacy::get_settings();
		$MyAgilePrivacyRegulationHelper = new MyAgilePrivacyRegulationHelper();
		$site_and_policy_settings = $MyAgilePrivacyRegulationHelper->getSiteAndPolicySettings();
		$rconfig = MyAgilePrivacy::get_rconfig();

		if( !empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) &&
			strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] )== 'xmlhttprequest' )
		{
			exit();
		}

		$css_compatibility_fix = false;

		if( isset( $GLOBALS['wp_version'] ) && version_compare( $GLOBALS['wp_version'], '4.2', '<' ) )
		{
			$css_compatibility_fix = true;
		}

		require_once plugin_dir_path( __FILE__ ).'views/helpdesk_html.php';
	}


	public function translations_view()
	{
		// check user capabilities
		if( !MyAgilePrivacy::map_can_edit() )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'translations_view -> missing user permission' );
			return false;
		}

		// Get settings
		$the_settings = MyAgilePrivacy::get_settings();
		$MyAgilePrivacyRegulationHelper = new MyAgilePrivacyRegulationHelper();
		$site_and_policy_settings = $MyAgilePrivacyRegulationHelper->getSiteAndPolicySettings();
		$templateConfig = $MyAgilePrivacyRegulationHelper->getTemplateConfig();
		$rconfig = MyAgilePrivacy::get_rconfig();

		if( !empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) &&
			strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] )== 'xmlhttprequest' )
		{
			exit();
		}

		$css_compatibility_fix = false;

		if( isset( $GLOBALS['wp_version'] ) && version_compare( $GLOBALS['wp_version'], '4.2', '<' ) )
		{
			$css_compatibility_fix = true;
		}

		$the_translations = MyAgilePrivacy::getFixedTranslations();

		$iab_tcf_context = false;

		if(
			$rconfig &&
			isset( $rconfig['allow_iab'] ) &&
			$rconfig['allow_iab'] == 1 &&
			$the_settings['enable_iab_tcf']
		)
		{
			$iab_tcf_context = true;
		}

		$no_cookies = false;

		$post_status_to_search = array( 'publish' );

		$cc_args = array(
			'posts_per_page'   	=> 	1,
			'post_type'        	=>	MAP_POST_TYPE_COOKIES,
			'post_status' 		=> 	$post_status_to_search,
			'fields'           	=> 	'ids',
			'no_found_rows'    	=> 	true,
		);

		$cc_query = new WP_Query( $cc_args );

		if( !$cc_query->have_posts() )
		{
			$no_cookies = true;
		}

		$show_lpd = false;
		if( isset( $templateConfig['map_lpd_text'] ) && $templateConfig['map_lpd_text'] == 1 )
		{
			$show_lpd = true;
		}

		$mapx_items = array();

		if( is_plugin_active( 'myagilepixel/myagilepixel.php' ) )
		{
			$added_first_layer_text_skip = false;

			$logic_check = 'basic';

			if( defined( 'MAPX_PLUGIN_VERSION' ) && version_compare( MAPX_PLUGIN_VERSION, '3.0.9', '>=' ) )
			{
				$logic_check = 'detailed';

				if( defined( 'MAPX_added_first_layer_text_skip' ) && MAPX_added_first_layer_text_skip )
				{
					$added_first_layer_text_skip = true;
				}
			}

			if( !$added_first_layer_text_skip )
			{
				if( $logic_check == 'basic' )
				{
					if( defined( 'MAPX_my_agile_pixel_ga_on' ) )
					{
						$mapx_items[] = 'ga_4_version';
					}

					if( defined( 'MAPX_my_agile_pixel_fbq_on' ) )
					{
						$mapx_items[] = 'facebook_remarketing';
					}

					if( defined( 'MAPX_my_agile_pixel_tiktok_on' ) )
					{
						$mapx_items[] = 'tiktok_pixel';
					}
				}
				elseif( $logic_check == 'detailed' )
				{
					if( defined( 'MAPX_my_agile_pixel_ga_on' ) &&
						defined( 'MAPX_my_agile_pixel_ga_on_anonymous' ) &&
						MAPX_my_agile_pixel_ga_on_anonymous )
					{
						$mapx_items[] = 'ga_4_version';
					}

					if( defined( 'MAPX_my_agile_pixel_fbq_on' ) &&
						defined( 'MAPX_my_agile_pixel_fbq_on_anonymous' ) &&
						MAPX_my_agile_pixel_fbq_on_anonymous )
					{
						$mapx_items[] = 'facebook_remarketing';
					}

					if( defined( 'MAPX_my_agile_pixel_tiktok_on' ) &&
						defined( 'MAPX_my_agile_pixel_tiktok_on_anonymous' ) &&
						MAPX_my_agile_pixel_tiktok_on_anonymous )
					{
						$mapx_items[] = 'tiktok_pixel';
					}
				}
			}
		}

		$currentAndSupportedLanguages = MyAgilePrivacy::getCurrentAndSupportedLanguages();

		require_once plugin_dir_path( __FILE__ ).'views/translations_html.php';
	}


	/**
	* Admin page html callback
	 * @access   public
	*/
	public function admin_page_html()
	{
		// check user capabilities
		if( !MyAgilePrivacy::map_can_edit() )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'admin_page_html -> missing user permission' );
			return false;
		}

		// Get settings
		$the_settings = MyAgilePrivacy::get_settings();

		$map_ab_state  = function_exists( 'map_call_advanced_get_ab_state' ) ? map_call_advanced_get_ab_state() : array( 'running' => false, 'panel_url' => '' );
		$map_ab_locked = ( isset( $map_ab_state['running'] ) && $map_ab_state['running'] );

		$MyAgilePrivacyRegulationHelper = new MyAgilePrivacyRegulationHelper();
		$site_and_policy_settings = $MyAgilePrivacyRegulationHelper->getSiteAndPolicySettings();

		//clean colors
		$param_to_clean = array(
			'background',
			'background_field',
			'heading_background_color',
			'heading_text_color',
			'text',
			'button_accept_link_color',
			'button_accept_button_color',
			'button_reject_link_color',
			'button_reject_button_color',
			'button_customize_link_color',
			'button_customize_button_color',
			'map_inline_notify_color',
			'map_inline_notify_background',
		);

		foreach( $the_settings as $k => &$v )
		{
			if( is_array( $param_to_clean ) &&
				in_array( $k, $param_to_clean ) )
			{
				$v = MyAgilePrivacy::clean_hex_color( $v );
			}
		}

		$rconfig = MyAgilePrivacy::get_rconfig();

		if( !( isset( $rconfig ) &&
				isset( $rconfig['disable_install_counter'] ) &&
				$rconfig['disable_install_counter'] == 1 ) )
		{
			global $map_stats;

			$map_stats = MyAgilePrivacy::get_option( MAP_PLUGIN_STATS, null );
		}

		if( !empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) &&
			strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] )== 'xmlhttprequest' )
		{
			exit();
		}

		$wasm_environment = false;

		if( isset( $_SERVER ) &&
			isset( $_SERVER['SERVER_SOFTWARE'] ) &&
			$_SERVER['SERVER_SOFTWARE'] == 'PHP.wasm'
		)
		{
			$wasm_environment = true;
		}

		$css_compatibility_fix = false;

		if( isset( $GLOBALS['wp_version'] ) && version_compare( $GLOBALS['wp_version'], '4.2', '<' ) )
		{
			$css_compatibility_fix = true;
		}

		$translation_menu_link = admin_url( 'edit.php?post_type=my-agile-privacy-c&page=my-agile-privacy-c_translations' );
		$policy_assistant_menu_link = admin_url( 'edit.php?post_type=my-agile-privacy-c&page=my-agile-privacy-c_guided_wizard' );

		$the_translations = MyAgilePrivacy::getFixedTranslations();
		$currentAndSupportedLanguages = MyAgilePrivacy::getCurrentAndSupportedLanguages();

		$selected_lang = MyAgilePrivacy::getCurrentLang4Char();

		require_once plugin_dir_path( __FILE__ ).'views/admin_page_html.php';
	}

	/**
	* Function for outputting action link ( wp plugins area)
	 * @access   public
	*/
	public function plugin_action_links( $links )
	{
		global $locale;

		$links[] = '<a href="'. get_admin_url( null, 'edit.php?post_type='.MAP_POST_TYPE_COOKIES.'&page=my-agile-privacy-c_settings' ) .'&page=my-agile-privacy-c_dashboard">'.wp_kses_post( __( 'Dashboard', 'MAP_txt' ) ).'</a>';
		$links[] = '<a href="'. get_admin_url( null, 'edit.php?post_type='.MAP_POST_TYPE_COOKIES.'&page=my-agile-privacy-c_settings' ) .'">'.wp_kses_post( __( 'Settings', 'MAP_txt' ) ).'</a>';
		$links[] = '<a href="https://www.myagileprivacy.com/" target="_blank">'.wp_kses_post( __( 'Support', 'MAP_txt' ) ).'</a>';

		if( $locale == 'it_IT' )
		{
			$links[] = '<a href="https://www.myagileprivacy.com/changelog/" target="_blank">'.wp_kses_post( __( 'Changelog', 'MAP_txt' ) ).'</a>';
		}
		else
		{
			$links[] = '<a href="https://www.myagileprivacy.com/en/changelog/" target="_blank">'.wp_kses_post( __( 'Changelog', 'MAP_txt' ) ).'</a>';

		}
		return $links;
	}

	//Remove WPML's language box
	public function remove_wpml_metaboxes()
	{
		global $post;

		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'remove_wpml_metaboxes' );

		if( isset( $post->post_type ) && $post->post_type === MAP_POST_TYPE_COOKIES )
		{
			 remove_meta_box('icl_div_config', $post->post_type, 'normal');  // Remove WPML's language box
		}

		if( isset( $post->post_type ) && $post->post_type === MAP_POST_TYPE_POLICY )
		{
			 remove_meta_box('icl_div_config', $post->post_type, 'normal');  // Remove WPML's language box
		}
	}

	/**
	* Admin Init
	* Set locale
	* Add custom meta box for handling custom field edit
	 * @access   public
	*/
	public function admin_init_and_add_meta_box()
	{
		$this->set_locale();

		$this->remove_wpml_metaboxes();

		//add metabox (cookies)
		add_meta_box( "_map_remote_id",wp_kses_post( __( 'Cookie ID', 'MAP_txt' ) ), array( $this, "metabox_map_remote_id" ), MAP_POST_TYPE_COOKIES, "side", "default" );
		add_meta_box( "_map_name",wp_kses_post( __( 'Cookie Name', 'MAP_txt' ) ), array( $this, "metabox_map_name" ), MAP_POST_TYPE_COOKIES, "side", "default" );
		add_meta_box( "_map_is_free",wp_kses_post( __( 'Free Available', 'MAP_txt' ) ), array( $this, "metabox_is_free" ), MAP_POST_TYPE_COOKIES, "side", "default" );
		add_meta_box( "_map_is_necessary",wp_kses_post( __( 'Is necessary?', 'MAP_txt' ) ), array( $this, "metabox_map_is_necessary" ), MAP_POST_TYPE_COOKIES, "side", "default" );
		add_meta_box( "_map_page_reload_on_user_consent",wp_kses_post( __( 'Requires page reload on user acceptance?', 'MAP_txt' ) ), array( $this, "metabox_map_page_reload_on_user_consent" ), MAP_POST_TYPE_COOKIES, "side", "default" );
		add_meta_box( "_map_allow_sync",wp_kses_post( __( 'Allow updates?', 'MAP_txt' ) ), array( $this, "metabox_map_allow_sync" ), MAP_POST_TYPE_COOKIES, "side", "default" );
		add_meta_box( "_map_script_installation",wp_kses_post( __( 'Script Installation', 'MAP_txt' ) ), array( $this, "metabox_script_installation" ), MAP_POST_TYPE_COOKIES, "advanced", "default" );

		//add metabox (policies)
		add_meta_box( "_map_allow_sync",wp_kses_post( __( 'Allow updates?', 'MAP_txt' ) ), array( $this, "metabox_map_allow_sync" ), MAP_POST_TYPE_POLICY, "side", "default" );

		//hide metabox
		remove_meta_box( '_map_remote_id', MAP_POST_TYPE_COOKIES, 'side' );
		remove_meta_box( '_map_name', MAP_POST_TYPE_COOKIES, 'side' );
		remove_meta_box( '_map_is_free', MAP_POST_TYPE_COOKIES, 'side' );

		//db version handling
		$db_version = MyAgilePrivacy::get_option( MAP_PLUGIN_DB_VERSION, null );

		if( !$db_version )
		{
			MyAgilePrivacy::update_option( MAP_PLUGIN_DB_VERSION, MAP_PLUGIN_DB_VERSION_NUMBER );
		}
	}



	/**
	* metabox render function
	* renders remote_id
	* @access   public
	*/
	public function metabox_map_remote_id()
	{
		global $post;

		if( !( isset( $post ) && is_object( $post ) && isset( $post->ID ) ) )
		{
			return;
		}

		$custom = get_post_custom( $post->ID );

		$_map_remote_id = ( isset( $custom["_map_remote_id"][0] ) ) ? $custom["_map_remote_id"][0] : '';

		?>
		<label>Cookie ID:</label>
		<input
			name="_map_remote_id"
			value="<?php echo esc_attr( $_map_remote_id ); ?>"
			class="map-w-95" />
		<?php
	}

	/**
	 * metabox render function
	 * render name
	 * @access   public
	*/
	public function metabox_map_name()
	{
		global $post;

		if( !( isset( $post ) && is_object( $post ) && isset( $post->ID ) ) )
		{
			return;
		}

		$custom = get_post_custom( $post->ID );
		$_map_name = ( isset( $custom["_map_name"][0] ) ) ? $custom["_map_name"][0] : '';
		?>
		<label>Cookie Name:</label>
		<input
			name="_map_name"
			value="<?php echo esc_attr( $_map_name ); ?>"
			class="map-w-95" />
		<?php
	}

	/**
	 * metabox render function
	 * renders is_free
	 * @access   public
	*/
	public function metabox_is_free()
	{
		global $post;

		if( !( isset( $post ) && is_object( $post ) && isset( $post->ID ) ) )
		{
			return;
		}

		$custom = get_post_custom( $post->ID );
		$_map_is_free = ( isset( $custom["_map_is_free"][0] ) ) ? $custom["_map_is_free"][0] : '';
		?>
		<label>Is Free:</label>
		<input
			name="_map_is_free"
			value="<?php echo esc_attr( $_map_is_free ); ?>"
			class="map-w-95" />
		<?php
	}

	public function metabox_map_page_reload_on_user_consent()
	{
		global $post;

		if( !( isset( $post ) && is_object( $post ) && isset( $post->ID ) ) )
		{
			return;
		}

		$custom = get_post_custom( $post->ID );
		$selected = ( isset( $custom["_map_page_reload_on_user_consent"][0] ) ) ? $custom["_map_page_reload_on_user_consent"][0] : '';

		?>
		<label class="mt-2" for="_map_page_reload_on_user_consent"><?php echo wp_kses_post( __( "Requires page reload on user acceptance?", 'MAP_txt' ) ); ?></label>
		<br>

		<select
			class="mt-2 mb-2"
			name='_map_page_reload_on_user_consent'
			id='_map_page_reload_on_user_consent'>
			<?php $post_types = array(
				'1'	=>	esc_attr( __( 'Yes', 'MAP_txt' ) ),
				'0'	=> 	esc_attr( __( 'No', 'MAP_txt' ) ),
			);
			foreach( $post_types as $key => $post_type ): ?>
			<option value="<?php echo esc_attr( $key ); ?>" <?php if( $selected == esc_attr( $key ) ):?>selected<?php endif; ?>><?php echo esc_html( $post_type ); ?></option>
			<?php endforeach; ?>
		</select>

		<p><?php echo wp_kses_post( __( "If this setting is enabled, the first-time activation by the user will trigger a page reload.", 'MAP_txt' ) ); ?><br>
			<?php echo wp_kses_post( __( "This setting does not apply if the cookie is set as 'necessary'.", 'MAP_txt' ) ); ?>
		</p>

		<?php
	}


	/**
	 * metabox render function
	 * renders allow_sync
	 * @access   public
	*/
	public function metabox_map_allow_sync()
	{
		global $post;

		if( !( isset( $post ) && is_object( $post ) && isset( $post->ID ) ) )
		{
			return;
		}

		$custom = get_post_custom( $post->ID );

		$map_remote_id = ( isset( $custom["_map_remote_id"][0] ) ) ? $custom["_map_remote_id"][0] : '';

		if( $map_remote_id ):

		$selected = ( isset( $custom["_map_allow_sync"][0] ) ) ? $custom["_map_allow_sync"][0] : '';

		?>
		<label class="mt-2" for="_map_allow_sync"><?php echo wp_kses_post( __( "Allow automatic updates ?", 'MAP_txt' ) ); ?></label>
		<br>

		<select
			class="mt-2 mb-2"
			name='_map_allow_sync'
			id='_map_allow_sync'>
			<?php $post_types = array(
				'1'	=>	esc_attr( __( 'Yes', 'MAP_txt' ) ),
				'0'	=> 	esc_attr( __( 'No', 'MAP_txt' ) ),
			);
			foreach( $post_types as $key => $post_type ): ?>
			<option value="<?php echo esc_attr( $key ); ?>" <?php if( $selected == esc_attr( $key ) ):?>selected<?php endif; ?>><?php echo esc_html( $post_type ); ?></option>
			<?php endforeach; ?>
		</select>


		<p><?php echo wp_kses_post( __( "If enabled, this will auto update this element text. Disable it if you would to change the text and keep it unchanged.", 'MAP_txt' ) ); ?></p>
		<?php
		else:
		?>
		<p><?php echo wp_kses_post( __( "This setting is unavailable on custom added cookie", 'MAP_txt' ) ); ?>.</p>
		<?php
		endif;
	}


	/**
	 * metabox render function
	 * renders is_necessary
	 * @access   public
	*/
	public function metabox_map_is_necessary()
	{
		global $post;

		if( !( isset( $post ) && is_object( $post ) && isset( $post->ID ) ) )
		{
			return;
		}

		$custom = get_post_custom( $post->ID );

		$selected = ( isset( $custom["_map_is_necessary"][0] ) ) ? $custom["_map_is_necessary"][0] : '';

		$is_google_analytics_like = ( isset( $custom["_map_api_key"] ) &&
			isset( $custom["_map_api_key"][0] ) &&
			( 	$custom["_map_api_key"][0] == 'google_analytics' ||
				$custom["_map_api_key"][0] == 'my_agile_pixel_ga' )
			) ? true : false;

		$is_video_key = ( isset( $custom["_map_api_key"] ) &&
			isset( $custom["_map_api_key"][0] ) &&
			in_array( $custom["_map_api_key"][0], array( 'youtube', 'vimeo' ), true )
			) ? true : false;

		?>
		<div <?php if( $is_google_analytics_like || $is_video_key ) echo 'class="map-hidden"';?>>

			<label class="mt-2" for="_map_is_necessary"><?php echo wp_kses_post( __( "Is necessary?", 'MAP_txt' ) ); ?></label>

			<br>

			<select
				class="mt-2 mb-2"
				name='_map_is_necessary'
				id='_map_is_necessary'>
				<?php $post_types = array( 'necessary', 'not-necessary' );
				foreach( $post_types as $post_type ): ?>
				<option value="<?php echo esc_attr( $post_type ); ?>" <?php if( $selected == esc_attr( $post_type ) ):?>selected<?php endif; ?>><?php echo esc_html( $post_type ); ?></option>
				<?php endforeach; ?>
			</select>

			<p>
				<?php echo wp_kses_post( __( 'Setting the cookie as "necessary" will disable the preventive blocking, making the tool always active regardless of user consent.', 'MAP_txt' ) ); ?><br>
				<?php echo wp_kses_post( __( 'Setting the cookie as "not necessary" will activate the cookie only upon user consent.', 'MAP_txt' ) ); ?><br>
				<?php

					if( isset( $custom["_map_suggested_is_necessary"] ) &&
						isset( $custom["_map_suggested_is_necessary"][0] )
					)
					{
						echo wp_kses_post( __( 'Suggested value for this Cookie', 'MAP_txt' ) ).':<b> '.esc_html( $custom["_map_suggested_is_necessary"][0] ).'</b>.';
					}
				?>
			</p>

		</div>

		<?php

		if( $is_google_analytics_like )
		{
			echo '<p>'.wp_kses_post( __( 'Managed setting via Privacy Settings ▸ Consent ▸ Google Consent Mode v2', 'MAP_txt' ) ).'</p>';
		}

		if( $is_video_key )
		{
			echo '<p>'.wp_kses_post( __( 'Managed setting via Privacy Settings ▸ Cookie Shield ▸ Video privacy mode', 'MAP_txt' ) ).'</p>';
		}
	}

	/**
	 * metabox render function
	 * renders installation method
	 * @access   public
	*/
	public function metabox_script_installation()
	{
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/my-agile-privacy-admin.js', array( 'jquery' ,'wp-color-picker' ), $this->version, false );

		wp_localize_script( $this->plugin_name, 'map_ajax',
			array(
				'ajax_url' 						=> admin_url( 'admin-ajax.php' ),
				'security' 						=> wp_create_nonce( 'check_license_status' ),
		) );

		global $post;

		if( !( isset( $post ) && is_object( $post ) && isset( $post->ID ) ) )
		{
			return;
		}

		$custom = get_post_custom( $post->ID );

		$locale = MyAgilePrivacy::get_locale();

		$_map_is_readonly = ( isset( $custom["_map_is_readonly"][0] ) ) ? $custom["_map_is_readonly"][0] : false;

		$_map_auto_detected = ( isset( $custom["_map_auto_detected"][0] ) ) ? $custom["_map_auto_detected"][0] : 0;
		$_map_auto_detected_override = ( isset( $custom["_map_auto_detected_override"][0] ) ) ? intval( $custom["_map_auto_detected_override"][0] ) : 0;

		$_map_installation_type = ( isset( $custom["_map_installation_type"][0] ) ) ? $custom["_map_installation_type"][0] : '';
		$_map_code = ( isset( $custom["_map_code"][0] ) ) ? $custom["_map_code"][0] : '';
		$_map_noscript = ( isset( $custom["_map_noscript"][0] ) ) ? $custom["_map_noscript"][0] : '';
		$_map_raw_code = ( isset( $custom["_map_raw_code"][0] ) ) ? $custom["_map_raw_code"][0] : '';
		$_map_help_url = ( isset( $custom["_map_help_url"][0] ) ) ? $custom["_map_help_url"][0] : '';
		$_map_api_key = ( isset( $custom["_map_api_key"][0] ) ) ? $custom["_map_api_key"][0] : '';
		$_map_js_dependencies = ( isset( $custom["_map_js_dependencies"][0] ) ) ? $custom["_map_js_dependencies"][0] : '';
		?>

		<?php

			if( $_map_is_readonly ):

		?>
			<div id="my_agile_privacy_backend" class="MAP_cookieWrapperEdit">

				<?php echo wp_kses_post( __( "This Cookie is reserved by the system and it is not possible to associate other scripts with its execution.", 'MAP_txt' ) ); ?>
			</div>

		<?php

			else:
		?>

			<div id="my_agile_privacy_backend" class="MAP_cookieWrapperEdit">

			<?php

				$addedClass = '';
				if( $_map_auto_detected ):

				$addedClass = 'displayNone';
			?>

				<div>
					<?php echo wp_kses_post( __( "Warning: this cookie has been detected automatically and usually you shouldn't need to install other code.", 'MAP_txt' ) ); ?>
				</div>

				<div class="row mt-3 mb-2">

					<div class="col-sm-12">

						<div class="styled_radio d-inline-flex">
							<div class="round d-flex me-12">

								<input type="hidden" name="_map_auto_detected_override" value="0" id="_map_auto_detected_override_no">

								<input
									name="_map_auto_detected_override"
									type="checkbox"
									value="1"
									id="_map_auto_detected_override"
									class="hideShowInput"
									data-hide-show-ref="map_auto_detected_override_wrapper"
									<?php checked( $_map_auto_detected_override, true ); ?>>

								<label for="_map_auto_detected_override" class="round-label"><span class="me-3 label-checkbox" aria-hidden="true"></span><?php echo wp_kses_post( __( "Add custom code anyway.", 'MAP_txt' ) ); ?></label>
							</div>
						</div> <!-- ./ styled_radio -->

					</div>
				</div> <!-- row -->


			<?php
				endif;
			?>

				<div class="row mb-3 MAP_cookieWrapperEdit map_auto_detected_override_wrapper <?php echo esc_attr( $addedClass ) ; ?>" data-value="1">

					<div class="col-sm-12">

						<label for="_map_installation_type" class="col-form-label"><b><?php echo wp_kses_post( __( "Script Installation type", 'MAP_txt' ) ); ?></b></label>

						<div class="col-sm-7">

							<select
								id="_map_installation_type"
								name="_map_installation_type"
								class="form-control hideShowInput"
								data-hide-show-ref="map_installation_type_wrapper">
								<?php

								$valid_options = array(

									'js_noscript'		=>	array( 'label' => esc_attr( __( 'JavaScript-Noscript code', 'MAP_txt' ) ),
																			'selected' => false ),
									'raw_code'			=>	array( 'label' => esc_attr( __( 'Raw code', 'MAP_txt' ) ),
																			'selected' => false ),
								);

								$selected_value = $_map_installation_type;

								if( isset( $valid_options[ $_map_installation_type ] ) )
								{
									$valid_options[ $selected_value ]['selected'] = true;
								}

								foreach( $valid_options as $key => $data )
								{
									if( $data['selected'] )
									{
										?>
										<option value="<?php echo esc_attr( $key ); ?>" selected><?php echo esc_attr( $data['label'] ); ?></option>
										<?php
									}
									else
									{
										?>
										<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $data['label'] ); ?></option>
										<?php
									}
								}

								?>
							</select>

						</div>

						<?php
							if( $_map_api_key ):
						?>

							<p class="mt-3">
								 <?php
								 echo wp_kses_post( __( "The API KEY for this cookie is", 'MAP_txt' ) );
								 echo ' <b>'.esc_html( $_map_api_key ).'</b>.<br>';
								 echo wp_kses_post( __( "You will need this only for advanced installation", 'MAP_txt' ) );
								 ?>.
							</p>

						<?php
							endif;
						?>

						<?php
							if( $locale && $locale == 'it_IT' && $_map_help_url ):
						?>

							<p>
								<a target="blank" href="<?php echo esc_url( $_map_help_url); ?>"><?php echo wp_kses_post( __( "Need help ? See this cookie installation guide", 'MAP_txt' ) ); ?></a>
							</p>

						<?php
							endif;
						?>

					</div>


					<div class="map_installation_type_wrapper displayNone" data-value="js_noscript">

						<label for="_map_code"><b><?php echo wp_kses_post( __( "Custom JavaScript Code", 'MAP_txt' ) ); ?></b></label>

						<div class="position-relative code-block-container code-block-container-small mb-4">
							<textarea id="_map_code" name="_map_code" class="code-editor code-editor-small" spellcheck="false"><?php echo esc_attr( $_map_code ); ?></textarea>

							<pre class="line-numbers code-viewer code-viewer-small"><code class="language-js"><?php echo esc_attr( $_map_code ); ?></code></pre>
						</div>

						<label for="_map_noscript"><b><?php echo wp_kses_post( __( "Noscript Code", 'MAP_txt' ) ); ?></b></label>

						<div class="position-relative code-block-container code-block-container-small">
							<textarea id="_map_noscript" name="_map_noscript" class="code-editor code-editor-small" spellcheck="false"><?php echo esc_attr( $_map_noscript ); ?></textarea>

							<pre class="code-viewer code-viewer-small"><code class="line-numbers language-html"><?php echo esc_attr( $_map_noscript ); ?></code></pre>
						</div>

					</div>

					<div class="map_installation_type_wrapper displayNone" data-value="raw_code">

						<label for="_map_raw_code"><b><?php echo wp_kses_post( __( "Raw Html Code", 'MAP_txt' ) ); ?></b></label>

						<div class="position-relative code-block-container code-block-container-small">
							<textarea id="_map_raw_code" name="_map_raw_code" class="code-editor code-editor-small" spellcheck="false"><?php echo esc_attr( $_map_raw_code ); ?></textarea>

							<pre class="line-numbers code-viewer code-viewer-small"><code class="language-html"><?php echo esc_attr( $_map_raw_code ); ?></code></pre>
						</div>
					</div>
				</div>

				<?php

					$map_widget_langs = array();

					if( $_map_api_key )
					{
						$map_widget_context = MyAgilePrivacy::getCurrentAndSupportedLanguages();
						$map_widget_texts   = MyAgilePrivacy::getFixedTranslations();

						foreach( $map_widget_context['supported_languages'] as $map_widget_code => $map_widget_data )
						{
							if( isset( $map_widget_texts[ $map_widget_code ]['blocked_element'] ) )
							{
								$map_widget_langs[ $map_widget_code ] = array(
									'label'	=>	ucfirst( $map_widget_data['en_label'] ),
									'text'	=>	$map_widget_texts[ $map_widget_code ]['blocked_element'],
								);
							}
						}

						//the visible list is empty until the language set is received
						if( empty( $map_widget_langs ) )
						{
							$map_widget_fallback = MyAgilePrivacy::normalizeLocaleTo4Char( get_locale() );

							if( !$map_widget_fallback ||
								!isset( $map_widget_texts[ $map_widget_fallback ]['blocked_element'] ) )
							{
								$map_widget_fallback = 'en_US';
							}

							$map_widget_all = MyAgilePrivacy::get_supported_languages();

							$map_widget_langs[ $map_widget_fallback ] = array(
								'label'	=>	isset( $map_widget_all[ $map_widget_fallback ]['en_label'] ) ? ucfirst( $map_widget_all[ $map_widget_fallback ]['en_label'] ) : $map_widget_fallback,
								'text'	=>	$map_widget_texts[ $map_widget_fallback ]['blocked_element'],
							);
						}
					}

				?>

				<?php if( $_map_api_key && !empty( $map_widget_langs ) ): ?>

					<div class="map-widget-generator mt-4">

						<button type="button" class="button-agile btn-md map-widget-generate-btn"><?php echo wp_kses_post( __( "Generate reconsent widget code", 'MAP_txt' ) ); ?></button>

						<div class="map-widget-panel displayNone mt-3">

							<p class="map-widget-note"><?php echo wp_kses_post( __( "Paste this code into your page where the blocked element appears. The notice is shown to visitors who have not accepted this cookie, and disappears on its own once they do.", 'MAP_txt' ) ); ?></p>

							<?php if( count( $map_widget_langs ) > 1 ): ?>

								<p class="map-widget-lang-row">
									<label for="map_widget_lang_select" class="me-2"><b><?php echo wp_kses_post( __( "Language", 'MAP_txt' ) ); ?></b></label>
									<select id="map_widget_lang_select" class="map-widget-lang-select mt-2 lh-sm">
										<?php foreach( $map_widget_langs as $map_widget_code => $map_widget_lang ): ?>
											<option value="<?php echo esc_attr( $map_widget_code ); ?>"><?php echo esc_html( $map_widget_lang['label'] ); ?></option>
										<?php endforeach; ?>
									</select>
								</p>

							<?php endif; ?>

							<?php

								$map_widget_first = true;

								foreach( $map_widget_langs as $map_widget_code => $map_widget_lang )
								{
									$map_widget_snippet = '<div class="map_custom_notify map_api_key_'.$_map_api_key.' showConsentAgain mapShowItem">'."\n".$map_widget_lang['text']."\n".'</div>';

							?>

								<div class="map-widget-snippet<?php echo $map_widget_first ? '' : ' displayNone'; ?>" data-map-widget-lang="<?php echo esc_attr( $map_widget_code ); ?>">
									<pre class="map-widget-code language-html"><code class="language-html"><?php echo esc_html( $map_widget_snippet ); ?></code></pre>
								</div>

							<?php

									$map_widget_first = false;
								}

							?>

							<button type="button" class="button-agile btn-md map-widget-copy-btn" data-map-widget-copied="<?php echo esc_attr( __( "Copied", 'MAP_txt' ) ); ?>"><?php echo wp_kses_post( __( "Copy", 'MAP_txt' ) ); ?></button>

						</div>

					</div>

				<?php endif; ?>

				<?php if( $_map_api_key ): ?>

					<div class="map_js_dependencies_wrapper mt-4">
						<label><b><?php echo wp_kses_post( __( "Custom JavaScript dependencies", 'MAP_txt' ) ); ?></b></label>

						<span class="forbiddenWarning badge rounded-pill bg-danger metabox-pill d-none">
							<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
						</span>

						<div class="checkForbiddenArea">

							<p>
								<i><?php echo wp_kses_post( __( "Warning: this feature is intended for advanced users and developers.", 'MAP_txt' ) ); ?></i><br>
								<?php echo wp_kses_post( __( "If you are experiencing JavaScript errors with scripts dependent on this cookie, you can list them here by writing part of the source file path or part of the inline JavaScript code.", 'MAP_txt' ) ); ?><br>
								<?php echo wp_kses_post( __( "Thanks to this functionality, these scripts will be unblocked only after the user has accepted this cookie.", 'MAP_txt' ) ); ?>
							</p>

							<div id="map-ips-list-container" class="row dynamic_fields_container">
								<?php
								$_map_js_dependencies_array = json_decode( $_map_js_dependencies, true );

								if( is_array( $_map_js_dependencies_array ) &&
									!empty( $_map_js_dependencies_array ) ):
									foreach( $_map_js_dependencies_array as $the_item ):
										if( is_array( $the_item ) && isset( $the_item['value'] ) && !MyAgilePrivacy::looseEqualsPhp8( $the_item['value'], '' ) ):
								?>
										<div class="map-dependency-entry map-dynamic-entry mb-2 col-sm-12">
											<div class="input-group">

												<select
													class="form-select2"
													name="js_dependencies_type[]"
													aria-label="<?php echo esc_attr__( 'Dependency type', 'MAP_txt' ); ?>">
													<option value="js_patterns_src" <?php selected( isset( $the_item['type'] ) ? $the_item['type'] : '', 'js_patterns_src' ); ?>>JavaScript script src path</option>

													<option value="js_patterns_code" <?php selected( isset( $the_item['type'] ) ? $the_item['type'] : '', 'js_patterns_code' ); ?>>JavaScript inline code</option>
												</select>

												<input
													class="form-control is-valid"
													name="js_dependencies_field[]"
													value="<?php echo esc_attr( $the_item['value'] ); ?>" type="text"
													autocomplete="off"
													aria-label="<?php echo esc_attr__( 'Dependency value', 'MAP_txt' ); ?>"/>
												<button class="btn btn-danger map-btn-remove" type="button">-</button>
											</div>
										</div>
								<?php
										endif;
									endforeach;
								endif;
								?>

								<div class="map-dependency-entry map-dynamic-entry mb-2 col-sm-12">
									<div class="input-group">
										<select
											class="form-select"
											name="js_dependencies_type[]"
											aria-label="<?php echo esc_attr__( 'Dependency type', 'MAP_txt' ); ?>">
											<option value="js_patterns_src">JavaScript script src path</option>
											<option value="js_patterns_code">JavaScript inline code</option>
										</select>

										<input
											class="form-control"
											name="js_dependencies_field[]"
											type="text"
											autocomplete="off"
											aria-label="<?php echo esc_attr__( 'Dependency value', 'MAP_txt' ); ?>"/>
										<button class="btn btn-success map-btn-add" type="button">+</button>
									</div>
								</div>
							</div>
						</div>
					</div> <!-- map_js_dependencies_wrapper -->
				<?php endif; ?>
		<?php

			endif;
		?>

		<?php
	}


	/**
	 * function for custom metabox save (cookies)
	 * @access   public
	*/
	public function save_custom_metabox_cookies( $main_post_id )
	{
		global $post;

		if( !MyAgilePrivacy::map_can_edit() )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'save_custom_metabox_cookies -> missing user permission' );
			return false;
		}

		if( !isset( $_POST['_wpnonce'] ) || !wp_verify_nonce( $_POST['_wpnonce'], 'update-post_' . $main_post_id ) )
		{
			return false;
		}

		if( isset( $_POST["_map_remote_id"] ) )
		{
			update_post_meta( $main_post_id, "_map_remote_id", sanitize_text_field( $_POST["_map_remote_id"] ) );
		}

		if( isset( $_POST["_map_name"] ) )
		{
			update_post_meta( $main_post_id, "_map_name", sanitize_text_field( $_POST["_map_name"] ) );
		}

		if( isset( $_POST["_map_installation_type"] ) )
		{
			update_post_meta( $main_post_id, "_map_installation_type", sanitize_text_field( $_POST["_map_installation_type"] ) );
		}

		if( isset( $_POST["_map_code"] ) && is_string( $_POST["_map_code"] ) )
		{
			update_post_meta( $main_post_id, "_map_code", $this->sanitize_textarea_value( $_POST["_map_code"] ) );
		}

		if( isset( $_POST["_map_raw_code"] ) && is_string( $_POST["_map_raw_code"] ) )
		{
			update_post_meta( $main_post_id, "_map_raw_code", esc_html( $_POST["_map_raw_code"] ) );
		}

		if( isset( $_POST["_map_noscript"] ) && is_string( $_POST["_map_noscript"] ) )
		{
			update_post_meta( $main_post_id, "_map_noscript", esc_html( $_POST["_map_noscript"] ) );
		}

		if( isset( $_POST["_map_is_free"] ) )
		{
			update_post_meta( $main_post_id, "_map_is_free", sanitize_text_field( $_POST["_map_is_free"] ) );
		}

		if( isset( $_POST["_map_is_necessary"] ) )
		{
			update_post_meta( $main_post_id, "_map_is_necessary", sanitize_text_field( $_POST["_map_is_necessary"] ) );
		}

		if( isset( $_POST["_map_allow_sync"] ) )
		{
			update_post_meta( $main_post_id, "_map_allow_sync", sanitize_text_field( $_POST["_map_allow_sync"] ) );
		}

		if( isset( $_POST["_map_page_reload_on_user_consent"] ) )
		{
			update_post_meta( $main_post_id, "_map_page_reload_on_user_consent", intval( $_POST["_map_page_reload_on_user_consent"] ) );
		}

		if( isset( $_POST["_map_auto_detected_override"] ) )
		{
			update_post_meta( $main_post_id, "_map_auto_detected_override", intval( $_POST["_map_auto_detected_override"] ) );
		}

		if( isset( $_POST["js_dependencies_field"] ) &&
			isset( $_POST["js_dependencies_type"] ) &&
			is_array( $_POST["js_dependencies_field"] ) &&
			is_array( $_POST["js_dependencies_type"] ) )
		{
			$js_dependencies = array();

			foreach( $_POST['js_dependencies_field'] as $index => $value )
			{
				if( !is_string( $value ) )
				{
					continue;
				}

				$type = ( isset( $_POST['js_dependencies_type'][$index] ) && is_string( $_POST['js_dependencies_type'][$index] ) ) ? sanitize_text_field( $_POST['js_dependencies_type'][$index] ) : '';
				$value = str_replace( '"', '\"', stripslashes( sanitize_text_field( $value ) ) );
				if( !empty( $value ) )
				{
					$js_dependencies[] = array(
						'type' => $type,
						'value' => $value
					);
				}
			}

			$map_js_dependencies_json = wp_json_encode( $js_dependencies );

			if( false !== $map_js_dependencies_json )
			{
				update_post_meta( $main_post_id, '_map_js_dependencies', $map_js_dependencies_json );
			}
		}

		if( isset( $_POST["map_translations"] ) && is_array( $_POST["map_translations"] ) )
		{
			$translations = $_POST["map_translations"];
			$sanitized_translations = array();

			$stored_translations_raw = get_post_meta( $main_post_id, '_map_translations', true );
			$stored_translations = is_string( $stored_translations_raw ) ? json_decode( $stored_translations_raw, true ) : null;

			if( !is_array( $stored_translations ) )
			{
				$stored_translations = array();
			}

			$the_first_name = null;

			foreach( $translations as $lang_code => $translation)
			{
				if( !is_array( $translation ) )
				{
					if( isset( $stored_translations[ $lang_code ] ) && is_array( $stored_translations[ $lang_code ] ) )
					{
						$sanitized_translations[ $lang_code ] = $stored_translations[ $lang_code ];
					}

					continue;
				}

				$the_name = '';

				if( isset( $translation['name'] ) && is_string( $translation['name'] ) )
				{
					$the_name = sanitize_text_field( $translation['name'] );

					if( !$the_first_name && $the_name)
					{
						$the_first_name = $the_name;
					}
				}
				elseif( isset( $stored_translations[ $lang_code ]['name'] ) )
				{
					$the_name = $stored_translations[ $lang_code ]['name'];
				}

				$sanitized_translations[ $lang_code ] = array(
					'name' 	=> 	$the_name,
					'text' 	=> 	( isset( $translation['text'] ) && is_string( $translation['text'] ) ) ? wp_kses_post( $translation['text'] ) : ''
				);
			}

			$json_translations = wp_json_encode( $sanitized_translations );

			if( false !== $json_translations )
			{
				update_post_meta( $main_post_id, '_map_translations', wp_slash( $json_translations ) );
			}

			//title align for manually added cookies
			$main_post = get_post( $main_post_id );
			$_map_remote_id_value = get_post_meta( $main_post_id, '_map_remote_id', true);

			if( $the_first_name &&
				empty( $_map_remote_id_value ) )
			{
				$updated_post = array(
					'ID'         => $main_post_id,
					'post_title' => $the_first_name,
				);

				//for preserving infinite loop due to wp_update_post hooks
				static $is_updating = false;

				if( $is_updating )
				{
					return;
				}

				$is_updating = true;
				$result = wp_update_post( $updated_post );
				$is_updating = false;
			}
		}

	    // Invalidate the JSON cache since cookie data has changed
	    MyAgilePrivacy::flush_json_cache();
	}

	/**
	 * Locks the post status of policy entries for non-admin users.
	 */
	public function map_lock_policy_status( $data, $postarr )
	{
		if( !isset( $data['post_type'] ) || $data['post_type'] !== MAP_POST_TYPE_POLICY )
		{
			return $data;
		}

		if( empty( $postarr['ID'] ) )
		{
			return $data;
		}

		if( !is_user_logged_in() )
		{
			return $data;
		}

		if( MyAgilePrivacy::$doing_internal_write )
		{
			return $data;
		}

		if( current_user_can( MyAgilePrivacy::get_capability( 'options' ) ) )
		{
			return $data;
		}

		$existing_status = get_post_status( $postarr['ID'] );

		if( $existing_status && isset( $data['post_status'] ) && $data['post_status'] !== $existing_status )
		{
			$data['post_status'] = $existing_status;
		}

		return $data;
	}

	/**
	 * function for custom metabox save (policies)
	 * @access   public
	*/
	public function save_custom_metabox_policies( $main_post_id )
	{
		if( !MyAgilePrivacy::map_can_edit() )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'save_custom_metabox_policies -> missing user permission' );
			return false;
		}

		if( !isset( $_POST['_wpnonce'] ) || !wp_verify_nonce( $_POST['_wpnonce'], 'update-post_' . $main_post_id ) )
		{
			return false;
		}

		$rconfig = MyAgilePrivacy::get_rconfig();

		global $post;

		if( !( isset( $post ) && is_object( $post ) && isset( $post->ID ) ) )
		{
			return;
		}

		$custom = get_post_custom( $post->ID );

		if( isset( $_POST["_map_allow_sync"] ) )
		{
			update_post_meta( $main_post_id, "_map_allow_sync", sanitize_text_field( $_POST["_map_allow_sync"] ) );
		}

		if( $custom &&
			isset( $custom["_map_remote_id"] ) &&
			$custom["_map_remote_id"] &&
			isset( $custom["_map_remote_id"][0] ) &&
			$custom["_map_remote_id"][0] &&
			$custom["_map_remote_id"][0] == 'personal_data_policy' )
		{
			if( !( isset( $rconfig ) &&
					isset( $rconfig['disable_cronjob'] ) &&
					$rconfig['disable_cronjob'] == 1 ) )
			{
				//refresh texts
				wp_schedule_single_event( time() + 5, 'my_agile_privacy_do_cron_sync_once_day_hook' );
			}
		}

		if( isset( $_POST['map_translations'] ) && is_array( $_POST['map_translations'] ) )
		{
			$translations = $_POST['map_translations'];
			$sanitized_translations = array();

			$stored_translations_raw = get_post_meta( $main_post_id, '_map_translations', true );
			$stored_translations = is_string( $stored_translations_raw ) ? json_decode( $stored_translations_raw, true ) : null;

			if( !is_array( $stored_translations ) )
			{
				$stored_translations = array();
			}

			foreach( $translations as $lang_code => $translation)
			{
				if( !is_array( $translation ) )
				{
					if( isset( $stored_translations[ $lang_code ] ) && is_array( $stored_translations[ $lang_code ] ) )
					{
						$sanitized_translations[ $lang_code ] = $stored_translations[ $lang_code ];
					}

					continue;
				}

				$sanitized_translations[ $lang_code ] = array(
					'text' 	=> 	( isset( $translation['text'] ) && is_string( $translation['text'] ) ) ? wp_kses_post( $translation['text'] ) : ''
				);
			}

			$json_translations = wp_json_encode( $sanitized_translations );

			if( false !== $json_translations )
			{
				update_post_meta( $main_post_id, '_map_translations', wp_slash( $json_translations ) );
			}
		}

	    // Invalidate the JSON cache since cookie data has changed
	    MyAgilePrivacy::flush_json_cache();
	}


	/**
	 * function for defining column names ( custom post type )
	 * @access   public
	*/
	public function manage_cookies_edit_columns( $columns )
	{
		$columns = array(
			"cb" 							=> "<input type=\"checkbox\" />",
			"title"							=> wp_kses_post( __( 'Cookie Name', 'MAP_txt' ) ),
			"auto_detected"					=> wp_kses_post( __( 'Auto Detected', 'MAP_txt' ) ),
			"necessary"						=> wp_kses_post( __( 'Preventive blocking status', 'MAP_txt' ) ),
			"suggested"						=> wp_kses_post( __( 'Suggested setting', 'MAP_txt' ) ),
			"allow_sync"					=> wp_kses_post( __( 'Allow automatic updates ?', 'MAP_txt' ) ),
			"installation_type"				=> wp_kses_post( __( 'Additional code installation type', 'MAP_txt' ) ),
			"page_reload_on_user_consent"	=> wp_kses_post( __( 'Requires page reload on user acceptance', 'MAP_txt' ) ),
			"map_sync_datetime_human"		=> wp_kses_post( __( 'Last update', 'MAP_txt' ) ),
		);
		return $columns;
	}

	/**
	 * function for getting column data ( custom post type )
	 * @access   public
	*/
	public function manage_cookies_posts_custom_columns( $column, $post_id=0 )
	{
		global $post;
		$custom = get_post_custom();

		$locale = MyAgilePrivacy::get_locale();

		switch ( $column )
		{
			case "map_sync_datetime_human":

				if( isset( $custom["_map_sync_datetime_human"][0] ) )
				{
					echo esc_html( $custom["_map_sync_datetime_human"][0] );
				}
				else
				{
					echo '-';
				}

				break;

			case "installation_type":

				if( isset( $custom["_map_installation_type"][0] ) )
				{
					if(
						(
							isset( $custom["_map_auto_detected"] ) &&
							$custom["_map_auto_detected"][0] == '1' &&
							isset( $custom["_map_auto_detected_override"] ) &&
							$custom["_map_auto_detected_override"][0] == '1'
						) ||

						( isset( $custom["_map_auto_detected"] ) &&
							$custom["_map_auto_detected"][0] == '0' )
					)
					{
						switch ( $custom["_map_installation_type"][0] )
						{
							case 'js_noscript':
								echo wp_kses_post( __( 'Javascript-Noscript code', 'MAP_txt' ) );
								break;
							case 'raw_code':
								echo wp_kses_post( __( 'Raw code', 'MAP_txt' ) );
								break;
						}
					}
					else
					{
						echo '-';
					}
				}
				else
				{
					echo '-';
				}
				break;

			case "page_reload_on_user_consent":

				if( isset( $custom["_map_is_necessary"][0] ) )
				{
					switch( $custom["_map_is_necessary"][0] )
					{
						case 'necessary':
							echo '-';

							break;
						case 'not-necessary':
							if( isset( $custom["_map_page_reload_on_user_consent"] ) &&
								$custom["_map_page_reload_on_user_consent"][0] == 1 )
							{
								echo wp_kses_post( __( 'Yes', 'MAP_txt' ) );
							}
							else
							{
								echo wp_kses_post( __( 'No', 'MAP_txt' ) );
							}

							break;
					}
				}

				break;


			case "auto_detected":

				if( isset( $custom["_map_auto_detected"] ) &&
					$custom["_map_auto_detected"][0] == 1 )
				{
					echo "<span class='map-dot map-dot-on' role='img' aria-label='".esc_attr( __( 'Yes', 'MAP_txt' ) )."' title='".esc_attr( __( 'Yes', 'MAP_txt' ) )."'>&#11044;</span>";
				}
				else
				{
					echo "<span class='map-dot map-dot-off' role='img' aria-label='".esc_attr( __( 'No', 'MAP_txt' ) )."' title='".esc_attr( __( 'No', 'MAP_txt' ) )."'>&#11044;</span>";
				}
				break;

			case "code":

				if( isset( $custom["_map_code"][0] ) )
				{
					echo esc_html( $custom["_map_code"][0] );
				}
				elseif( isset( $custom["_map_raw_code"][0] ) )
				{
					echo esc_html( $custom["_map_raw_code"][0] );
				}
				break;

			case "necessary":

				if( isset( $custom["_map_api_key"] ) &&
					isset( $custom["_map_api_key"][0] ) &&
					(
						$custom["_map_api_key"][0] == 'google_analytics' ||
						$custom["_map_api_key"][0] == 'my_agile_pixel_ga' )
				)
				{
					echo wp_kses_post( __( 'Managed setting via Privacy Settings ▸ Consent ▸ Google Consent Mode v2', 'MAP_txt' ) );
				}
				elseif( isset( $custom["_map_api_key"][0] ) &&
					in_array( $custom["_map_api_key"][0], array( 'youtube', 'vimeo' ), true ) )
				{
					echo wp_kses_post( __( 'Managed setting via Privacy Settings ▸ Cookie Shield ▸ Video privacy mode', 'MAP_txt' ) );
				}
				else
				{
					if( isset( $custom["_map_is_necessary"][0] ) )
					{
						switch( $custom["_map_is_necessary"][0] )
						{
							case 'necessary':
								echo wp_kses_post( __( 'Preventive blocking is not active<br>(cookie setting: “necessary”)', 'MAP_txt' ) );
								break;

							case 'not-necessary':
								echo wp_kses_post( __( 'Preventive blocking <strong>is active</strong><br>(cookie setting: “not-necessary”)', 'MAP_txt' ) );
								break;
						}
					}
				}
				break;

			case "suggested":

				if( isset( $custom["_map_api_key"] ) &&
					isset( $custom["_map_api_key"][0] ) &&
					(
						$custom["_map_api_key"][0] == 'google_analytics' ||
						$custom["_map_api_key"][0] == 'my_agile_pixel_ga' )
				)
				{
					echo '-';
				}
				elseif( isset( $custom["_map_api_key"][0] ) &&
					in_array( $custom["_map_api_key"][0], array( 'youtube', 'vimeo' ), true ) )
				{
					echo '-';
				}
				else
				{
					if( isset( $custom["_map_suggested_is_necessary"][0] ) )
					{
						switch( $custom["_map_suggested_is_necessary"][0] )
						{
							case 'necessary':
								echo '<b>necessary</b>';
								break;

							case 'not-necessary':
								echo '<b>not-necessary</b>';
								break;
						}
					}
				}

				break;

			case "allow_sync":

				if( isset( $custom["_map_allow_sync"][0] ) )
				{
					if( $custom["_map_allow_sync"][0] )
					{
						echo wp_kses_post( __( 'Yes', 'MAP_txt' ) );
					}
					else
					{
						echo wp_kses_post( __( 'No', 'MAP_txt' ) );
					}
				}
				else
				{
					echo '-';
				}
				break;
		}
	}


	/**
	 * function for defining column names ( custom post type )
	 * @access   public
	*/
	public function manage_policies_edit_columns( $columns )
	{
		$columns = array(
			"cb" 						=> "<input type=\"checkbox\" />",
			"title"						=> "Policy",
			"allow_sync"				=> wp_kses_post( __( "Allow automatic updates ?", 'MAP_txt' ) ),
			"map_sync_datetime_human"	=> wp_kses_post( __( 'Last update', 'MAP_txt' ) ),
		);
		return $columns;
	}


	/**
	 * function for getting column data ( custom post type )
	 * @access   public
	*/
	public function manage_policies_posts_custom_columns( $column, $post_id=0 )
	{
		global $post;
		$custom = get_post_custom();

		switch ( $column )
		{
			case "map_sync_datetime_human":

				if( isset( $custom["_map_sync_datetime_human"][0] ) )
				{
					echo esc_html( $custom["_map_sync_datetime_human"][0] );
				}
				else
				{
					echo '-';
				}

				break;

			case "allow_sync":

				if( isset( $custom["_map_allow_sync"][0] ) )
				{
					if( $custom["_map_allow_sync"][0] )
					{
						echo wp_kses_post( __( 'Yes', 'MAP_txt' ) );
					}
					else
					{
						echo wp_kses_post( __( 'No', 'MAP_txt' ) );
					}
				}
				else
				{
					echo '-';
				}
				break;
		}
	}

	/**
	 * policy content quickview generation
	 * @access   public
	*/
	public static function genPolicyQuickViewContent( $the_id, $lang_code_2char=null )
	{
		//get translations
		$the_translations = MyAgilePrivacy::getFixedTranslations();

		$map_remote_id = get_post_meta( $the_id, '_map_remote_id', true );

		if( $lang_code_2char )
		{
			$_map_translations = get_post_meta( $the_id, '_map_translations', true );
			$translations = is_string( $_map_translations ) ? wp_unslash( json_decode( $_map_translations, true ) ) : null;

			$content = ( isset( $translations[ $lang_code_2char ]['text'] ) && is_string( $translations[ $lang_code_2char ]['text'] ) ) ? $translations[ $lang_code_2char ]['text'] : '';

			//for accessing $the_translations
			$current_lang = MyAgilePrivacy::translate2charTo4CharLangCode( $lang_code_2char );
		}
		else
		{
			$content = get_post_field( 'post_content', $the_id );

			//for accessing $the_translations
			$current_lang = MyAgilePrivacy::getCurrentLang4Char();
		}

		if( !$map_remote_id )
		{
			$map_remote_id = 'cookie_policy';
		}

		$the_settings = MyAgilePrivacy::get_settings();
		$rconfig = MyAgilePrivacy::get_rconfig();

		$content = MyAgilePrivacy::internalMAPCleanupAndCompilePolicy( $content, $the_translations, $current_lang, 'backend', '' );

		return $content;
	}

	/**
	 * inline help text after editor (policies)
	 * @access   public
	*/
	public function inline_help_text_after_editor()
	{
		global $my_admin_page;
		$screen = get_current_screen();

		if( is_admin() && is_object( $screen ) && ( $screen->id == MAP_POST_TYPE_POLICY ) )
		{
			if( !function_exists( 'map_policy_add_content_after_editor' ) ) {
			function map_policy_add_content_after_editor() {
				global $post;

				if( !( isset( $post ) && is_object( $post ) && isset( $post->ID ) ) )
				{
					return;
				}

				$the_id = $post->ID;

				$_map_translations = get_post_meta( $the_id, '_map_translations', true );
				$translations = is_string( $_map_translations ) ? wp_unslash( json_decode( $_map_translations, true ) ) : null;

				$currentAndSupportedLanguages = MyAgilePrivacy::getCurrentAndSupportedLanguages();

				$_map_name = get_post_meta( $the_id, '_map_name', true );

				$shortcode = '';

				if( $_map_name )
				{
					switch( $_map_name )
					{
						case 'cookie_policy':
							$shortcode = '[myagileprivacy_fixed_text text="cookie_policy"]';
							break;
						case 'personal_data_policy':
							$shortcode = '[myagileprivacy_fixed_text text="personal_data_policy"]';
							break;
					}

					?>


					<div class="map-hero">
						<div class="map-avatar">
							<img src="<?php echo plugin_dir_url( __FILE__ ) ?>img/fox-profile.png" alt="">
						</div>

						<div id="_my_agile_privacy_backend" class="policyWrapperView postbox map_infobox">
							<?php
								echo "<strong>" . wp_kses_post( __( "Welcome to the preview of your Policy!", 'MAP_txt' ) ) . '</strong><br>';
								echo wp_kses_post( __( "Remember to add the following shortcode to the correct page:", 'MAP_txt' ) ).'<br>';
								echo '<code>'.wp_kses( $shortcode, MyAgilePrivacy::allowed_html_tags() ).'</code><br>';
								echo wp_kses_post( __( "Remember also associate in the Privacy Settings -> Policies tab.", 'MAP_txt' ) );
								?>
							<div class="mt-3">
								<?php
									$MyAgilePrivacyRegulationHelper = new MyAgilePrivacyRegulationHelper();

									echo "<strong>" . wp_kses_post( __( "Active regulations", 'MAP_txt' ) ) . ':</strong><br>';
									echo "<div>";

									$regulation_selected = $MyAgilePrivacyRegulationHelper->getRegulationsSelected( true );

									if( count( $regulation_selected ) > 0 )
									{
										foreach( $regulation_selected as $reg )
										{
											?>
												<span class="badge rounded-pill bg-primary">
													<?php echo esc_html( $reg ); ?>
												</span>
											<?php
										}
									}
									else
									{
										echo "-";
									}

									echo "</div>";
								?>
							</div>

							<div class="mt-2"><?php map_render_helpdesk_link( 'policy_management', null, true, 'policy-manual-edit' ); ?></div>
						</div>
					</div>

					<div class="my_agile_privacy_backend_inline map_text_edit">
						<div class="map-text-quickview">
							<div class="map-custom-card">
								<div class="map-custom-card-header">
									<h1><?php echo wp_kses_post( __( "Quickview for", 'MAP_txt' ) ).' '.esc_html( $post->post_title ) ?> <button type="button" class="button-agile btn-md map-do-edit-this-text"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> <?php echo wp_kses_post( __( 'Edit Policy', 'MAP_txt' ) ); ?></button></h1>
								</div>
								<div class="map-custom-card-body">

									<?php

										if( $currentAndSupportedLanguages['with_multilang'] )
										{
									?>

											<div class="map-translations-list">

												<ul class="nav nav-tabs" id="translationTabs" role="tablist">
													<?php
													$first = true;

													foreach( $currentAndSupportedLanguages['supported_languages'] as $lang_code => $lang_data ):

														$lang_code_2char = $lang_data['2char'];
														$active = $first ? 'active' : '';
														$aria_selected = $first ? 'true' : 'false';
														$this_label = ucfirst( $lang_data['en_label'] );

													?>
														<li class="nav-item" role="presentation">
															<button class="nav-link <?php echo esc_attr( $active ); ?>" id="preview_<?php echo esc_attr( $lang_code_2char ); ?>-tab" data-bs-toggle="tab" data-bs-target="#preview_<?php echo esc_attr( $lang_code_2char ); ?>-content" type="button" role="tab" aria-controls="preview_<?php echo esc_attr( $lang_code_2char ); ?>-content" aria-selected="<?php echo esc_attr( $aria_selected ); ?>">
																<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'img/flags/' . $lang_code_2char . '.png' ); ?>" alt="<?php echo esc_attr( $this_label ); ?>" class="map-lang-flag">
																<?php echo esc_html( $this_label ); ?>
															</button>
														</li>
													<?php
														$first = false;
													endforeach;
													?>
												</ul>


												<div class="tab-content" id="translationTabsContent">
													<?php
													$first = true;

													foreach( $currentAndSupportedLanguages['supported_languages'] as $lang_code => $lang_data ):

														$lang_code_2char = $lang_data['2char'];
														$active = $first ? 'show active' : '';
														$this_name = null;
														$this_text = null;
														$this_label = ucfirst( $lang_data['en_label'] );

														if( isset( $translations ) &&
															$translations &&
															isset( $translations[ $lang_data['2char'] ]) )
														{
															$translation = $translations[ $lang_data['2char'] ];

															$this_text = isset( $translation['text'] ) ? $translation['text'] : null;
														}

													?>
														<div class="tab-pane fade <?php echo esc_attr( $active ); ?>" id="preview_<?php echo esc_attr( $lang_code_2char ); ?>-content" role="tabpanel" aria-labelledby="preview_<?php echo esc_attr( $lang_code_2char ); ?>-tab">
															<span class="map-preview-caption"><?php echo wp_kses_post( __( 'Policy Preview', 'MAP_txt' ) ); ?></span>

															<?php

																$the_quickview_content = MyAgilePrivacyAdmin::genPolicyQuickViewContent( $the_id, $lang_code_2char );

																echo wp_kses_post( $the_quickview_content );
															?>
														</div>
													<?php
														$first = false;
													endforeach;
													?>
												</div>
											</div>

									<?php

										}
										else
										{
											$the_quickview_content = MyAgilePrivacyAdmin::genPolicyQuickViewContent( $the_id, null );
											echo wp_kses_post( $the_quickview_content );
										}

									?>


								</div>
							</div>
						</div>
						<div class="map-wrap-editor displayNone">
							<label for="content" class="screen-reader-text"><?php echo esc_html__( 'Policy text', 'MAP_txt' ); ?></label>
							<div class="alert alert-warning" role="alert">
								<?php echo wp_kses_post( __( 'Attention: Modifying the policy without specific expertise may result in loss of compliance.', 'MAP_txt' ) ); ?><br>
								<?php echo wp_kses_post( __( 'By modifying the text, you assume all risks.', 'MAP_txt' ) ); ?><br>
								<?php echo wp_kses_post( __( 'Remember to disable automatic updates for this policy to avoid losing the changes made.', 'MAP_txt' ) ); ?><br>
							</div>

							<?php

								if( $currentAndSupportedLanguages['with_multilang'] ):
							?>

									<div class="map-translations-list">

										<ul class="nav nav-tabs" id="translationTabs" role="tablist">
											<?php
											$first = true;

											foreach( $currentAndSupportedLanguages['supported_languages'] as $lang_code => $lang_data ):

												$lang_code_2char = $lang_data['2char'];
												$active = $first ? 'active' : '';
												$aria_selected = $first ? 'true' : 'false';
												$this_label = ucfirst( $lang_data['en_label'] );

											?>
												<li class="nav-item" role="presentation">
													<button class="nav-link <?php echo esc_attr( $active ); ?>" id="<?php echo esc_attr( $lang_code_2char ); ?>-tab" data-bs-toggle="tab" data-bs-target="#<?php echo esc_attr( $lang_code_2char ); ?>-content" type="button" role="tab" aria-controls="<?php echo esc_attr( $lang_code_2char ); ?>-content" aria-selected="<?php echo esc_attr( $aria_selected ); ?>">
														<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'img/flags/' . $lang_code_2char . '.png' ); ?>" alt="<?php echo esc_attr( $this_label ); ?>" class="map-lang-flag">
														<?php echo esc_html( $this_label ); ?>
													</button>
												</li>
											<?php
												$first = false;
											endforeach;
											?>
										</ul>

										<div class="tab-content" id="translationTabsContent">
											<?php
											$first = true;

											foreach( $currentAndSupportedLanguages['supported_languages'] as $lang_code => $lang_data ):

												$lang_code_2char = $lang_data['2char'];
												$active = $first ? 'show active' : '';
												$this_name = null;
												$this_text = '';
												$this_label = ucfirst( $lang_data['en_label'] );

												if( isset( $translations ) &&
													$translations &&
													isset( $translations[ $lang_data['2char'] ]) )
												{
													$translation = $translations[ $lang_data['2char'] ];

													$this_text = isset( $translation['text'] ) ? $translation['text'] : '';
												}

											?>
												<div class="tab-pane fade <?php echo esc_attr( $active ); ?>" id="<?php echo esc_attr( $lang_code_2char ); ?>-content" role="tabpanel" aria-labelledby="<?php echo esc_attr( $lang_code_2char ); ?>-tab">

													<?php
														$editor_id = 'map_translation_' . $lang_code_2char;
														$editor_settings = array(
															'textarea_name' => 'map_translations[' . $lang_code_2char . '][text]',
															'textarea_rows' => 10,
															'media_buttons' => false,
														);
														wp_editor( $this_text, $editor_id, $editor_settings );
													?>
												</div>
											<?php
												$first = false;
											endforeach;
											?>
										</div>
									</div>

							<?php
								endif;
							?>

						<?php

							if( $currentAndSupportedLanguages['with_multilang'] ):
						?>

							</div>
							<div class="displayNone">

						<?php
							endif;
				}
			}
			}

			if( !function_exists( 'map_policy_add_text_after_editor' ) )
			{
				function map_policy_add_text_after_editor()
				{
					// Text to display after the editor
					echo '</div></div>';
				}
			}

			if( ! function_exists( 'map_policy_strip_native_editor_content' ) )
			{
				function map_policy_strip_native_editor_content( $content, $default_editor )
				{
					global $post;

					if( ! ( isset( $post ) && is_object( $post ) && isset( $post->ID ) && isset( $post->post_content ) ) )
					{
						return $content;
					}

					if( $content !== $post->post_content )
					{
						return $content;
					}

					$baseline = get_post_meta( $post->ID, '_map_local_textual_baseline', true );

					if( $baseline && hash( 'sha256', $post->post_content ) === $baseline )
					{
						return MyAgilePrivacy::internalMAPStripUnselectedRegulations( $content );
					}

					return $content;
				}
			}

			$map_current_langs = MyAgilePrivacy::getCurrentAndSupportedLanguages();

			if( ! ( isset( $map_current_langs['with_multilang'] ) && $map_current_langs['with_multilang'] ) )
			{
				add_filter( 'the_editor_content', 'map_policy_strip_native_editor_content', 9, 2 );
			}

			add_action( 'edit_form_after_title', 'map_policy_add_content_after_editor' );
			add_action( 'edit_form_after_editor', 'map_policy_add_text_after_editor' );
		}

		if( is_admin() && is_object( $screen ) && ( $screen->id == MAP_POST_TYPE_COOKIES ) )
		{
			global $post;

			if( !( isset( $post ) && is_object( $post ) && isset( $post->ID ) ) )
			{
				return;
			}

			$the_id = $post->ID;

			$_map_translations = get_post_meta( $the_id, '_map_translations', true );
			$translations = is_string( $_map_translations ) ? wp_unslash( json_decode( $_map_translations, true ) ) : null;

			$currentAndSupportedLanguages = MyAgilePrivacy::getCurrentAndSupportedLanguages();

			if( !function_exists( 'map_cookie_add_content_after_editor' ) ) {
			function map_cookie_add_content_after_editor()
			{
				global $post;

				if( !( isset( $post ) && is_object( $post ) && isset( $post->ID ) ) )
				{
					return;
				}

				$the_id = $post->ID;

				$_map_translations = get_post_meta( $the_id, '_map_translations', true );
				$translations = is_string( $_map_translations ) ? wp_unslash( json_decode( $_map_translations, true ) ) : null;

				$currentAndSupportedLanguages = MyAgilePrivacy::getCurrentAndSupportedLanguages();

				?>
					<div class="map-hero">
							<div class="map-avatar">
								<img src="<?php echo plugin_dir_url( __FILE__ ) ?>img/fox-profile.png" alt="">
							</div>

						<div id="_my_agile_privacy_backend" class="policyWrapperView postbox map_infobox">

							<?php echo wp_kses_post( __( 'On this page, you can choose which cookies to keep always active (necessary) and which to enable only with your consent. You can decide whether to refresh the page after acceptance and, if you’re an advanced user, manage any JavaScript dependencies. Warning: change these settings only if you know what you’re doing; incorrect configurations can compromise site functionality and regulatory compliance. If in doubt, keep the recommended settings. ', 'MAP_txt' ) ); ?>

							<div class="mt-2"><?php map_render_helpdesk_link( 'cookie_management', null, true, 'preventive-blocking' ); ?></div>
						</div>
				</div>

				<div class="my_agile_privacy_backend_inline map_text_edit">
					<div class="map-text-quickview">
						<div class="map-custom-card">
							<div class="map-custom-card-header">
								<h1><?php echo wp_kses_post( __( "Quickview for", 'MAP_txt' ) ).' '.esc_html( $post->post_title ) ?> <button type="button" class="button-agile btn-md map-do-edit-this-text"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> <?php echo wp_kses_post( __( 'Edit Cookie Text', 'MAP_txt' ) ); ?></button></h1>
							</div>
							<div class="map-custom-card-body">

								<?php

									if( $currentAndSupportedLanguages['with_multilang'] )
									{
								?>

										<div class="map-translations-list">

											<ul class="nav nav-tabs" id="translationTabs" role="tablist">
												<?php
												$first = true;

												foreach( $currentAndSupportedLanguages['supported_languages'] as $lang_code => $lang_data ):

													$lang_code_2char = $lang_data['2char'];
													$active = $first ? 'active' : '';
													$aria_selected = $first ? 'true' : 'false';
													$this_label = ucfirst( $lang_data['en_label'] );

												?>
													<li class="nav-item" role="presentation">
														<button class="nav-link <?php echo esc_attr( $active ); ?>" id="preview_<?php echo esc_attr( $lang_code_2char ); ?>-tab" data-bs-toggle="tab" data-bs-target="#preview_<?php echo esc_attr( $lang_code_2char ); ?>-content" type="button" role="tab" aria-controls="preview_<?php echo esc_attr( $lang_code_2char ); ?>-content" aria-selected="<?php echo esc_attr( $aria_selected ); ?>">
															<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'img/flags/' . $lang_code_2char . '.png' ); ?>" alt="<?php echo esc_attr( $this_label ); ?>" class="map-lang-flag">
															<?php echo esc_html( $this_label ); ?>
														</button>
													</li>
												<?php
													$first = false;
												endforeach;
												?>
											</ul>


											<div class="tab-content" id="translationTabsContent">
												<?php
												$first = true;

												foreach( $currentAndSupportedLanguages['supported_languages'] as $lang_code => $lang_data ):

													$lang_code_2char = $lang_data['2char'];
													$active = $first ? 'show active' : '';
													$this_name = null;
													$this_text = null;
													$this_label = ucfirst( $lang_data['en_label'] );

													if( isset( $translations ) &&
														$translations &&
														isset( $translations[ $lang_data['2char'] ]) )
													{
														$translation = $translations[ $lang_data['2char'] ];

														$this_text = isset( $translation['text'] ) ? $translation['text'] : null;

														$_map_tr_baseline_raw = get_post_meta( $the_id, '_map_translations_baseline', true );
														$_map_tr_baseline = is_string( $_map_tr_baseline_raw ) ? json_decode( $_map_tr_baseline_raw, true ) : null;

														if( is_string( $this_text ) &&
															is_array( $_map_tr_baseline ) &&
															isset( $_map_tr_baseline[ $lang_data['2char'] ] ) &&
															hash( 'sha256', $this_text ) === $_map_tr_baseline[ $lang_data['2char'] ] )
														{
															$this_text = MyAgilePrivacy::internalMAPStripUnselectedRegulations( $this_text );
														}
													}

												?>
													<div class="tab-pane fade <?php echo esc_attr( $active ); ?>" id="preview_<?php echo esc_attr( $lang_code_2char ); ?>-content" role="tabpanel" aria-labelledby="preview_<?php echo esc_attr( $lang_code_2char ); ?>-tab">
														<label for="map_translations[<?php echo esc_attr( $lang_code_2char ); ?>][name]"><?php echo wp_kses_post( __( 'Cookie Text Preview', 'MAP_txt' ) ); ?></label>

														<?php

															$the_quickview_content = MyAgilePrivacyAdmin::genPolicyQuickViewContent( $the_id, $lang_code_2char );

															echo wp_kses_post( $the_quickview_content );
														?>
													</div>
												<?php
													$first = false;
												endforeach;
												?>
											</div>
										</div>

								<?php

									}
									else
									{
										$the_quickview_content = MyAgilePrivacyAdmin::genPolicyQuickViewContent( $the_id, null );
										echo wp_kses_post( $the_quickview_content );
									}

								?>


							</div>
						</div>
					</div>
					<div class="map-wrap-editor displayNone">
						<label for="content" class="screen-reader-text"><?php echo esc_html__( 'Cookie text', 'MAP_txt' ); ?></label>
						<div class="alert alert-warning" role="alert">
							<?php echo wp_kses_post( __( 'Attention: Modifying the cookie without specific expertise may result in loss of compliance.', 'MAP_txt' ) ); ?><br>
							<?php echo wp_kses_post( __( 'By modifying the text, you assume all risks.', 'MAP_txt' ) ); ?><br>
							<?php echo wp_kses_post( __( 'Remember to disable automatic updates for this cookie to avoid losing the changes made.', 'MAP_txt' ) ); ?><br>
						</div>

						<?php

							if( $currentAndSupportedLanguages['with_multilang'] ):
						?>

								<div class="map-translations-list">

									<ul class="nav nav-tabs" id="translationTabs" role="tablist">
										<?php
										$first = true;

										foreach( $currentAndSupportedLanguages['supported_languages'] as $lang_code => $lang_data ):

											$lang_code_2char = $lang_data['2char'];
											$active = $first ? 'active' : '';
											$aria_selected = $first ? 'true' : 'false';
											$this_label = ucfirst( $lang_data['en_label'] );

										?>
											<li class="nav-item" role="presentation">
												<button class="nav-link <?php echo esc_attr( $active ); ?>" id="<?php echo esc_attr( $lang_code_2char ); ?>-tab" data-bs-toggle="tab" data-bs-target="#<?php echo esc_attr( $lang_code_2char ); ?>-content" type="button" role="tab" aria-controls="<?php echo esc_attr( $lang_code_2char ); ?>-content" aria-selected="<?php echo esc_attr( $aria_selected ); ?>">
													<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'img/flags/' . $lang_code_2char . '.png' ); ?>" alt="<?php echo esc_attr( $this_label ); ?>" class="map-lang-flag">
													<?php echo esc_html( $this_label ); ?>
												</button>
											</li>
										<?php
											$first = false;
										endforeach;
										?>
									</ul>

									<div class="tab-content" id="translationTabsContent">
										<?php
										$first = true;

										foreach( $currentAndSupportedLanguages['supported_languages'] as $lang_code => $lang_data ):

											$lang_code_2char = $lang_data['2char'];
											$active = $first ? 'show active' : '';
											$this_name = null;
											$this_text = '';
											$this_label = ucfirst( $lang_data['en_label'] );

											if( isset( $translations ) &&
												$translations &&
												isset( $translations[ $lang_data['2char'] ]) )
											{
												$translation = $translations[ $lang_data['2char'] ];

												$this_text = isset( $translation['text'] ) ? $translation['text'] : '';
											}

										?>
											<div class="tab-pane fade <?php echo esc_attr( $active ); ?>" id="<?php echo esc_attr( $lang_code_2char ); ?>-content" role="tabpanel" aria-labelledby="<?php echo esc_attr( $lang_code_2char ); ?>-tab">

												<?php
													$editor_id = 'map_translation_' . $lang_code_2char;
													$editor_settings = array(
														'textarea_name' => 'map_translations[' . $lang_code_2char . '][text]',
														'textarea_rows' => 10,
														'media_buttons' => false,
													);
													wp_editor( $this_text, $editor_id, $editor_settings );
												?>
											</div>
										<?php
											$first = false;
										endforeach;
										?>
									</div>
								</div>

						<?php
							endif;
						?>

					<?php

						if( $currentAndSupportedLanguages['with_multilang'] ):
					?>

						</div>
						<div class="displayNone">

					<?php
						endif;

			}
			}

			if( !function_exists( 'map_cookie_add_text_after_editor' ) )
			{
				function map_cookie_add_text_after_editor()
				{
					// Text to display after the editor
					echo '</div></div>';
				}
			}

			add_action( 'edit_form_after_title', 'map_cookie_add_content_after_editor' );
			add_action( 'edit_form_after_editor', 'map_cookie_add_text_after_editor' );
		}
	}


	/**
	 * Check license callback function
	 *
	 */
	public function check_license_status()
	{
		if( !MyAgilePrivacy::map_can_edit() )
		{
			wp_send_json_error( array( 'code' => 'forbidden' ), 403 );
		}

		//check security param
		check_ajax_referer( 'check_license_status', 'security' );

		$success = false;

		// Get settings
		$the_settings = MyAgilePrivacy::get_settings();

		if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1 )
		{
			$success = true;
		}

		$answer = array(
			'success'				=>	$success,
		);

		wp_send_json( $answer );

		die();
	}


	/**
	 * download plugin stats
	 * @access   public
	*/
	public function js_get_plugin_stats()
	{
		$url = add_query_arg( array(
			'action'                  => 'plugin_information',
			'slug'                    => MAP_PLUGIN_SLUG,
			'fields[active_installs]' => '1',
		), 'https://api.wordpress.org/plugins/info/1.2/' );

		$response = wp_remote_get( $url, array( 'timeout' => 15 ) );

		if( !is_wp_error( $response ) )
		{
			$plugin_info = json_decode( wp_remote_retrieve_body( $response ) );

			return $plugin_info ? $plugin_info : null;
		}
		else
		{
			reset( $response->errors );
			$error_code = key( $response->errors );
			$error_message = $response->errors[ $error_code ][0];

			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $error_message );
		}

		return null;
	}

	//f. for review expired notice
	public function show_expired_notice()
	{
		$show_expired_notice = MyAgilePrivacy::should_show_expired_notice();

		if( $show_expired_notice )
		{
			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( "showing review notice, review_url_key=$review_url_key, review_urls=".print_r( $review_urls, true ) );

			require_once plugin_dir_path( __FILE__ ).'views/expired_notice_html.php';
		}

	}

	//f. for review banner show
	public function show_review_notice()
	{
		//Apply account preferences.
		MyAgilePrivacy::checkDoNotAskLicenseCode();

		$show_feedback_notice = MyAgilePrivacy::should_show_feedback_notice();
		$rconfig = MyAgilePrivacy::get_rconfig();

		$review_urls = null;
		$review_url_key = null;

		//default url
		$review_url = 'https://wordpress.org/support/plugin/myagileprivacy/reviews/#new-post';

		if( isset( $rconfig ) && isset( $rconfig['review_urls'] ) && $rconfig['review_urls'] )
		{
			$review_urls = $rconfig['review_urls'];
		}

		$locale = MyAgilePrivacy::get_locale();

		if( $locale && $locale == 'it_IT' )
		{
			$review_url_key = 'it';
		}
		else
		{
			$review_url_key = 'en';
		}

		if( $locale && $review_urls && isset( $review_urls[ $review_url_key ] ) && is_string( $review_urls[ $review_url_key ] ) )
		{
			$review_url = $review_urls[ $review_url_key ];
		}

		if( $show_feedback_notice )
		{
			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( "showing review notice, review_url_key=$review_url_key, review_urls=".print_r( $review_urls, true ) );

			require_once plugin_dir_path( __FILE__ ).'views/feedback_notice_html.php';
		}
	}

	//f event review later
	public function review_later()
	{
		// nonce check
		check_ajax_referer('map_review_nonce', 'security');

		if( !MyAgilePrivacy::map_can_edit() )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'review_later -> missing user permission' );
			wp_die( 'Unauthorized', '', 403 );
		}

		MyAgilePrivacy::update_option( MAP_REVIEW_STATUS, 'later' );
		MyAgilePrivacy::update_option( MAP_NOTICE_LAST_SHOW_TIME, time() );

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'setting review status to later' );

		wp_die();
	}

	//f event review done
	public function review_done()
	{
		// nonce check
		check_ajax_referer( 'map_review_nonce', 'security' );

		if( !MyAgilePrivacy::map_can_edit() )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'review_done -> missing user permission' );
			wp_die( 'Unauthorized', '', 403 );
		}

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'setting review status to done' );

		MyAgilePrivacy::update_option( MAP_REVIEW_STATUS, 'done' );
		MyAgilePrivacy::update_option( MAP_NOTICE_LAST_SHOW_TIME, time() );

		wp_die();
	}

	//plugins_loaded callback: check whether a post-update routine needs to run
	public function map_check_post_update_transient()
	{
		$installed_version = MyAgilePrivacy::get_option( MAP_PLUGIN_INSTALLED_VERSION, false );
		$effective_version = MyAgilePrivacy::get_effective_installed_version();

		if( $installed_version === $effective_version )
		{
			return;
		}

		// written before the routine, so the next requests do not repeat it
		MyAgilePrivacy::update_option( MAP_PLUGIN_INSTALLED_VERSION, $effective_version );

		// a new installation has nothing to refresh
		if( $installed_version === false && !is_array( MyAgilePrivacy::get_option( MAP_PLUGIN_SETTINGS_FIELD, false ) ) )
		{
			return;
		}

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'map_check_post_update_transient: version changed, calling post-update routine' );

		//Runs the post-update routine once; it is idempotent.
		$this->map_post_update_routine();
	}

	//post-update routine: runs once on the first request after a MAP plugin update
	public function map_post_update_routine()
	{
		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'map_post_update_routine called after plugin update' );

		MyAgilePrivacy::flush_manifest_file_cache();
		// Invalidate the JSON cache since cookie data has changed
		MyAgilePrivacy::flush_json_cache();
		// third-party page caches are cleared in a request of their own
		MyAgilePrivacy::schedule_page_cache_flush();

        MyAgilePrivacy::update_option( MAP_PLUGIN_VALIDATION_TIMESTAMP, null );
        MyAgilePrivacy::update_option( MAP_PLUGIN_DO_SYNC_NOW, 1 );

		// rows left by earlier versions
		delete_option( MAP_PLUGIN_DB_KEY_PREFIX . 'sync_in_progress' );
		delete_transient( 'map_just_updated' );
	}

	//refreshes the frontend cache when My Agile Pixel is switched on or off
	public function map_on_pixel_toggled( $plugin )
	{
		if( $plugin === 'myagilepixel/myagilepixel.php' )
		{
			MyAgilePrivacy::flush_json_cache();
		}
	}

	//watches My Agile Pixel activation and settings
	public function map_watch_pixel_state()
	{
		add_action( 'activated_plugin', array( $this, 'map_on_pixel_toggled' ), 10, 1 );
		add_action( 'deactivated_plugin', array( $this, 'map_on_pixel_toggled' ), 10, 1 );

		if( defined( 'MAPX_PLUGIN_SETTINGS_FIELD' ) && is_string( MAPX_PLUGIN_SETTINGS_FIELD ) && MAPX_PLUGIN_SETTINGS_FIELD !== '' )
		{
			add_action( 'add_option_' . MAPX_PLUGIN_SETTINGS_FIELD, array( $this, 'map_on_pixel_settings_saved' ) );
			add_action( 'update_option_' . MAPX_PLUGIN_SETTINGS_FIELD, array( $this, 'map_on_pixel_settings_saved' ) );
		}
	}

	//refreshes the frontend cache after a My Agile Pixel settings write
	public function map_on_pixel_settings_saved()
	{
		MyAgilePrivacy::flush_json_cache();
	}

	/**
	 * Count and fingerprint of the stored Cloudflare list, as a JSON string for the summary.
	 *
	 * @return string
	 */
	private function get_cf_nets_echo()
	{
		$echo = array( 'n' => 0, 'h' => '' );

		try
		{
			$rconfig = MyAgilePrivacy::get_rconfig();
			$list = ( is_array( $rconfig ) && isset( $rconfig['cf_nets'] ) ) ? $rconfig['cf_nets'] : array();

			if( !is_array( $list ) )
			{
				$echo['n'] = -1;
			}
			elseif( count( $list ) > 0 )
			{
				$echo['n'] = -1;
				$plain = ( array_keys( $list ) === range( 0, count( $list ) - 1 ) );

				foreach( $list as $item )
				{
					if( !$plain || !is_string( $item ) || false !== strpos( $item, "\n" ) )
					{
						$plain = false;
						break;
					}
				}

				if( $plain )
				{
					$echo = array( 'n' => count( $list ), 'h' => hash( 'sha256', implode( "\n", $list ) ) );
				}
			}
		}
		catch( Exception $e )
		{
			$echo = array( 'n' => -1, 'h' => '' );
		}
		catch( Error $e )
		{
			$echo = array( 'n' => -1, 'h' => '' );
		}

		$json = wp_json_encode( $echo );

		return ( is_string( $json ) ) ? $json : '{"n":-1,"h":""}';
	}
}
