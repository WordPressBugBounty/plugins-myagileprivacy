<?php

/**
 * The admin-specific functionalities
 *
 * @link       https://www.myagileprivacy.com/
 * @since      1.0.12
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
class MyAgilePrivacyAdmin {

	/**
	 * Plugin Name
	 *
	 * @since    1.0.12
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * Plugin Version
	 *
	 * @since    1.0.12
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Constructor
	 *
	 * @since    1.0.12
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
	 * Rewritten func to ignore messy mofile
	 * @since    1.3.9
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
		if ( load_textdomain( $domain, WP_LANG_DIR . '/plugins/' . $mofile ) ) {
			return true;
		}
		*/

		if ( false !== $plugin_rel_path ) {
			$path = WP_PLUGIN_DIR . '/' . trim( $plugin_rel_path, '/' );
		} elseif ( false !== $deprecated ) {
			_deprecated_argument( __FUNCTION__, '2.7.0' );
			$path = ABSPATH . trim( $deprecated, '/' );
		} else {
			$path = WP_PLUGIN_DIR;
		}

		return load_textdomain( $domain, $path . '/' . $mofile );
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * @since    1.0.12
	 * @access   private
	 */
	private function set_locale()
	{
		global $locale;

		$the_settings = MyAgilePrivacy::get_settings();

		if( isset( $the_settings['default_locale'] ) )
		{
			$old_locale = $locale;
			$locale = $the_settings['default_locale'];
		}

		$loaded = $this->my_load_plugin_textdomain(
			MAP_PLUGIN_SLUG,
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/lang/'
		);

		if( isset( $the_settings['default_locale'] ) )
		{
			$locale = $old_locale;
		}
	}

	/**
	 * f. for activating myagilepixel
	 */
	public function admin_auto_enable_cookie()
	{
		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'START admin_auto_enable_cookie' );

		if (!function_exists( 'is_plugin_active' ) ) {
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
				// Get options
				$the_options = MyAgilePrivacy::get_settings();

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

						$multilang_default_lang = $sitepress->get_default_language();
						$wpml_current_lang = ICL_LANGUAGE_CODE;
						$language_list = icl_get_languages();
						$language_list_codes = array();

						//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $multilang_default_lang );
						//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $wpml_current_lang );

						foreach( $language_list as $k => $v )
						{
							if( isset( $v['code'] ) )
							{
								$language_list_codes[] = $v['code'];
							}
							elseif( isset( $v['language_code'] ) )
							{
								$language_list_codes[] = $v['language_code'];
							}
						}
					}

					if( defined( 'POLYLANG_FILE' ) &&
						function_exists( 'pll_default_language' ) &&
						function_exists( 'pll_languages_list' ) )
					{
						$is_polylang_enabled = true;
						$with_multilang = true;

						$multilang_default_lang = pll_default_language();
						$language_list_codes = pll_languages_list();

						//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $default );
						//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $language_list_codes );
					}
				}

				foreach( $auto_activate_keys as $k => $v )
				{
					//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $v, true );

					if( $v )
					{
						if( $is_wpml_enabled )
						{
							$sitepress->switch_lang( $multilang_default_lang );
						}

						$post_status_to_search = array( 'draft', 'publish', '__blocked', '__always_allowed' );

						$cc_args = array(
							'posts_per_page'   	=> 	-1,
							'post_type'        	=>	MAP_POST_TYPE_COOKIES,
							'meta_key'         	=> 	'_map_api_key',
							'meta_value'       	=> 	$v['key'],
							'post_status' 		=> 	$post_status_to_search,
						);

						if( $is_polylang_enabled )
						{
							$cc_args['lang'] = $multilang_default_lang;
						}

						//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $cc_args, true );
						$cc_query = new WP_Query( $cc_args );

						//wpml language reset
						if( $is_wpml_enabled )
						{
							$sitepress->switch_lang( $wpml_current_lang );
						}

						if ( $cc_query->have_posts() )
						{
							foreach ( $cc_query->get_posts() as $p )
							{
								$main_post_id = $p->ID;

								$post_type = get_post_type( $main_post_id );

								//double check for strange theme / plugins
								if( $post_type == MAP_POST_TYPE_COOKIES )
								{
									$this_post_status = get_post_status( $main_post_id );

									if( in_array( $this_post_status, $post_status_to_search ) )
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
										}

										if( $with_multilang )
										{
											if( $is_wpml_enabled )
											{
												$t_post_id = apply_filters( 'wpml_element_trid', null, $main_post_id );

												$translations = apply_filters( 'wpml_get_element_translations', null, $t_post_id, 'post_'.MAP_POST_TYPE_COOKIES );

												//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $t_post_id );
												//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $translations );
											}

											if( $is_polylang_enabled )
											{
												$translations = pll_get_post_translations( $main_post_id );
											}

											if( $translations )
											{
												$to_update = array();

												foreach( $translations as $t_key => $t_value )
												{
													if( $t_key != $multilang_default_lang )
													{
														if( $is_wpml_enabled )
														{
															$to_update[ $t_key ] = $t_value->element_id;
														}

														if( $is_polylang_enabled )
														{
															$to_update[ $t_key ] = $t_value;
														}
													}
												}

												if( $to_update )
												{
													foreach( $to_update as $key_to_update => $to_update_post_id )
													{
														if( $to_update_post_id )
														{
															$original_post_status = get_post_status( $to_update_post_id );

															if( $original_post_status != $v['next_status'] )
															{
																//update
																$my_post = array(
																	'ID'           	=> 	$to_update_post_id,
																	'post_status'	=>	$v['next_status'],
																);

																if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $my_post );

																wp_update_post( $my_post );

																update_post_meta( $to_update_post_id, "_map_auto_detected", 1 );
															}
														}
													}
												}
											}
										}
									}
								}
							}

							MyAgilePrivacy::internal_query_reset();
						}

					}
				}
			}
		}
		else
		{
			//display the banner

			echo '<script type="text/javascript">'.PHP_EOL;
			echo 'jQuery( "#mapx_banner" ).removeClass( "d-none" );';
			echo '</script>'.PHP_EOL;
		}


		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'END admin_auto_enable_cookie' );
	}


	/*
	 * f. for clearing log file
	*/
	public function admin_clear_logfile()
	{
		//clean logfile if it's stale and debugger is off
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

			$expiration_time_in_seconds = 60*60*24;
			$max_age = time() - $expiration_time_in_seconds;

			if( is_file( $filePath ) && filemtime( $filePath ) > $max_age )
			{
				wp_delete_file( $filePath );
			}
		}
	}

	/**
	 * Unset some data for polylang
	 *
	 * @since    1.2.0
	 */
	public function add_cpt_to_pll( $post_types, $is_settings )
	{
		if ( $is_settings ) {
			// hides cpt from the list of custom post types in Polylang settings
			unset( $post_types[ MAP_POST_TYPE_COOKIES ] );
			unset( $post_types[ MAP_POST_TYPE_POLICY ] );
		} else {
			// enables language and translation management
			$post_types[ MAP_POST_TYPE_COOKIES ] =  MAP_POST_TYPE_COOKIES;
			$post_types[ MAP_POST_TYPE_POLICY ] =  MAP_POST_TYPE_POLICY;
		}
		return $post_types;
	}


	/**
	 * Stylesheets for the admin area.
	 *
	 * @since    1.0.12
	 * @access   public
	 */
	public function enqueue_styles()
	{
		$do_load = false;

		global $pagenow;
		$current_page_settings = get_current_screen();
		$current_page_post_type = $current_page_settings->post_type;
		$current_page_base = $current_page_settings->base;

		if ( $current_page_base == 'my-agile-privacy-c_page_my-agile-privacy-c_settings' ||
			 $current_page_base == 'my-agile-privacy-c_page_my-agile-privacy-c_backup_restore' ||
			 $current_page_base == 'my-agile-privacy-c_page_my-agile-privacy-c_compliance_report' ||
			 $current_page_base == 'my-agile-privacy-c_page_my-agile-privacy-c_helpdesk'
		)
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
	 * @since    1.0.12
	 */
	public function enqueue_scripts()
	{
		$do_load = false;

		global $pagenow;

		$current_page_settings = get_current_screen();
		$current_page_post_type = $current_page_settings->post_type;
		$current_page_base = $current_page_settings->base;

		if ( $current_page_base == 'my-agile-privacy-c_page_my-agile-privacy-c_settings' ||
			 $current_page_base == 'my-agile-privacy-c_page_my-agile-privacy-c_backup_restore' ||
			 $current_page_base == 'my-agile-privacy-c_page_my-agile-privacy-c_compliance_report' ||
			 $current_page_base == 'my-agile-privacy-c_page_my-agile-privacy-c_helpdesk'
			) 
		{
			$do_load = true;
		}

		if( $do_load )
		{
			wp_enqueue_script( $this->plugin_name.'-prism', plugin_dir_url( __FILE__ ) . 'js/prism.js', array( 'jquery'), $this->version, false );
			wp_enqueue_script( $this->plugin_name.'-popper', plugin_dir_url( __FILE__ ) . 'js/popper.min.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( $this->plugin_name.'-bootstrap', plugin_dir_url( __FILE__ ) . 'js/bootstrap.bundle.min.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/my-agile-privacy-admin.js', array( 'jquery' ,'wp-color-picker' ), $this->version, false );
		}
		
	}


	/**
	 * Function called on custom post editing
	 *
	 * @since    1.0.12
	 * @access   public
	 */
	public function custom_post_type_editor()
	{
	    $do_load = false;

		global $typenow;
	    if( $typenow &&
	    	( $typenow == MAP_POST_TYPE_COOKIES || $typenow == MAP_POST_TYPE_POLICY )
	    )
		{
			$do_load = true;
		}

		if( $do_load )
	    {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style( $this->plugin_name.'-prism', plugin_dir_url( __FILE__ ) ."css/prism.css", array(),$this->version, 'all' );
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) ."css/my-agile-privacy-admin.css", array(),$this->version, 'all' );
			wp_enqueue_style( $this->plugin_name.'-bootstrap', plugin_dir_url( __FILE__ ) ."css/bootstrap.min.css", array(),$this->version, 'all' );


			wp_enqueue_script( $this->plugin_name.'-prism', plugin_dir_url( __FILE__ ) . 'js/prism.js', array( 'jquery'), $this->version, false );
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

		if ( is_admin() && $do_load )
		{
			$init['wpautop'] = false;
		}

		return $init;
	}


	/**
	* Function for calling remote sync via cronjob
	 * @since    1.3.7
	 * @access   public
	*/
	public function do_cron_sync_install_counter()
	{
		$data = $this->js_get_plugin_stats();

		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $data );

		if( $data && isset( $data ) )
		{
			update_option( MAP_PLUGIN_STATS, $data );
		}
	}


	/**
	* Function for calling remote sync via cronjob
	 * @since    1.0.12
	 * @access   public
	*/
	public function do_cron_sync()
	{
		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'do_cron_sync start' );

		update_option( MAP_PLUGIN_DO_SYNC_NOW, 1 );
		update_option( MAP_PLUGIN_SYNC_IN_PROGRESS, 0 );
		update_option( MAP_PLUGIN_DO_PARITY_CHECK_NOW, 1 );

		$multilang_enabled = MyAgilePrivacy::check_if_multilang_enabled();

		if( !$multilang_enabled )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $multilang_enabled );

			$this->triggered_do_cron_sync();

			$this->triggered_parity_check();
		}

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'do_cron_sync end' );

		return true;
	}


	/**
	* Function for calling remote sync via cronjob (triggered via wp_footer hook and do_cron_sync)
	*/
	public function triggered_do_cron_sync()
	{
		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'start triggered_do_cron_sync' );

		$the_options = MyAgilePrivacy::get_settings();
		$rconfig = MyAgilePrivacy::get_rconfig();

		$now = time();

		$sync_last_execution = MyAgilePrivacy::nullCoalesce( get_option( MAP_PLUGIN_DO_SYNC_LAST_EXECUTION ), null );

		//bypass blocked cron websites
		if( $sync_last_execution )
		{
			//23 hours
			if( $now - $sync_last_execution > 82800 )
			{
				if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'detected stale sync_last_execution' );

				update_option( MAP_PLUGIN_DO_SYNC_NOW, 1 );
			}
		}
		else
		{
			update_option( MAP_PLUGIN_DO_SYNC_NOW, 1 );
		}

		$do_sync_now = MyAgilePrivacy::nullCoalesce( get_option( MAP_PLUGIN_DO_SYNC_NOW ), 0 );

		if( $do_sync_now )
		{
			if( isset( $the_options['pa'] ) &&
				$the_options['pa'] == 1
				&& !( isset( $rconfig['forbid_local_js_caching'] ) && $rconfig['forbid_local_js_caching'] == 1 )
			)
			{
				$cdn_basepath = 'https://cdn.myagileprivacy.com/';
				$manifest_file = 'version_manifest.json';

				if( MAP_DEV_MODE )
				{
					$manifest_filename = plugin_dir_path( MAP_PLUGIN_FILENAME ) .'dev/'.$manifest_file;
					$manifest_content = file_get_contents( $manifest_filename );
					$manifest = json_decode( $manifest_content, true );
				}
				else
				{
					MyAgilePrivacy::download_remote_file( $cdn_basepath.$manifest_file, $manifest_file );

					$manifest_filename = MyAgilePrivacy::get_base_directory_for_cache().$manifest_file;
					$manifest_content = file_get_contents( $manifest_filename );
					$manifest = json_decode( $manifest_content, true );
				}

				//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( get_option( MAP_MANIFEST_ASSOC) );
				//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $manifest );

				if( $manifest && isset( $manifest['manifest_version_file'] ) )
				{
					$manifest_assoc = array();

					$manifest_assoc['manifest_version_file'] = $manifest['manifest_version_file'];
					$manifest_assoc['files'] = array();

					foreach( $manifest['files'] as $remote_file => $remote_details )
					{
						$version = $remote_details['version'];
						$remote_url = $cdn_basepath . $remote_file;
						$path_info = pathinfo( $remote_file );
						$local_file = basename( $remote_file );
						$local_file_with_version = $path_info['filename'] . '-' . $version . '.' . $path_info['extension'];

						$this_item = array(
							'filename'			=>	$local_file_with_version,
							'version'			=> 	$version,
							'remote_details'	=>	$remote_details,
						);

						$manifest_assoc['files'][ $local_file ] = $this_item;

						$do_get_file = true;

						if( !(
								defined( 'MAP_IAB_TCF' ) && MAP_IAB_TCF &&
								$rconfig['allow_iab'] == 1 &&
								$the_options['enable_iab_tcf']
							) &&
							strpos( $remote_url, "MyAgilePrivacyIabTCF" ) !== false )
						{
							$do_get_file = false;
						}

						if( $do_get_file )
						{
							MyAgilePrivacy::download_remote_file( $remote_url, $local_file, $version, $local_file_with_version );
						}
					}

					update_option( MAP_MANIFEST_ASSOC, $manifest_assoc );
				}
				else
				{
					update_option( MAP_MANIFEST_ASSOC, null );
				}
			}

			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'START triggered_do_cron_sync' );

			// Get options
			$the_options = MyAgilePrivacy::get_settings();

			$sync_result = $this->sync_cookies_and_fixed_texts( false );
			$the_options = MyAgilePrivacy::get_settings();

			//bof adjust last_sync data

			$this_sync_datetime_human = strtotime( "now" );

			if( function_exists( 'wp_date') )
			{
				$wp_date_format = get_option( 'date_format', null );

				if( $wp_date_format )
				{
					$wp_date_format .= ' H:i:s';

					$this_sync_datetime_human = wp_date( $wp_date_format, $now );
				}
			}

			$the_options['last_sync'] = $this_sync_datetime_human;

			update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_options );
			$the_options = MyAgilePrivacy::get_settings();

			//eof adjust last_sync data

			//log reset
			if( $the_options['scanner_log'] )
			{
				$log = substr( $the_options['scanner_log'], -200 );
				$the_options['scanner_log'] = $log;

				update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_options );
				$the_options = MyAgilePrivacy::get_settings();
			}

			//learning mode auto turn to live
			if( $the_options['scan_mode'] == 'learning_mode' )
			{
				$now = time();

				if( $the_options['learning_mode_last_active_timestamp'] == null )
				{
					$the_options['learning_mode_last_active_timestamp'] = $now;

					update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_options );
					$the_options = MyAgilePrivacy::get_settings();
				}
				else
				{
					$turn_to_live = false;

					if( MAP_DEV_MODE )
					{
						if( $now - $the_options['learning_mode_last_active_timestamp'] > 3600 )
						{
							$turn_to_live = true;
						}
					}
					else
					{
						if( $now - $the_options['learning_mode_last_active_timestamp'] > 604800 )
						{
							$turn_to_live = true;
						}
					}

					if( $turn_to_live )
					{
						//auto turn to live after 1 week
						$the_options['learning_mode_last_active_timestamp'] = null;
						$the_options['scan_mode'] = 'config_finished';
						$the_options['scanner_log'] = null;

						update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_options );
						$the_options = MyAgilePrivacy::get_settings();
					}
				}
			}

			update_option( MAP_PLUGIN_DO_SYNC_NOW, 0 );

			$now = time();
			update_option( MAP_PLUGIN_DO_SYNC_LAST_EXECUTION, $now );

			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'END triggered_do_cron_sync' );

			$this->do_cron_sync_install_counter();
		}
		else
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'exiting triggered_do_cron_sync' );
		}

		//update_option( MAP_PLUGIN_DO_SYNC_NOW, 1 );
		//update_option( MAP_PLUGIN_SYNC_IN_PROGRESS, 0 );

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'end triggered_do_cron_sync' );
	}


	/**
	* Function for calling parity check (triggered via wp_footer hook and do_cron_sync)
	*/
	public function triggered_parity_check()
	{
		$multilang_enabled = MyAgilePrivacy::check_if_multilang_enabled();

		if( $multilang_enabled )
		{
			$do_sync_now = get_option( MAP_PLUGIN_DO_PARITY_CHECK_NOW, false );

			if( $do_sync_now &&
			( ( function_exists( 'wp_doing_ajax' ) && !wp_doing_ajax() ) ||
			  !function_exists( 'wp_doing_ajax' ) ) )
			{
				$rconfig = MyAgilePrivacy::get_rconfig();

				if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'START triggered_parity_check' );

				//parity check part
				if( isset( $GLOBALS['wp_version'] ) )
				{
					if( version_compare( $GLOBALS['wp_version'], '5.9', '<=' ) )
					{
						//no action
					}
					else
					{
						if( is_admin() && empty( $_POST ) )
						{
							if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'admin_footer parity check part' );

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

									$multilang_default_lang = $sitepress->get_default_language();
									$wpml_current_lang = ICL_LANGUAGE_CODE;
									$language_list = icl_get_languages();
									$language_list_codes = array();

									//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $multilang_default_lang );
									//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $wpml_current_lang );
									//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $language_list );

									foreach( $language_list as $k => $v )
									{
										if( isset( $v['code'] ) )
										{
											$language_list_codes[] = $v['code'];
										}
										elseif( isset( $v['language_code'] ) )
										{
											$language_list_codes[] = $v['language_code'];
										}
									}
								}

								if( defined( 'POLYLANG_FILE' ) &&
									function_exists( 'pll_default_language' ) &&
									function_exists( 'pll_languages_list' ) )
								{
									$is_polylang_enabled = true;
									$with_multilang = true;

									$multilang_default_lang = pll_default_language();
									$language_list_codes = pll_languages_list();

									//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $default );
									//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $language_list_codes );
								}
							}

							$do_sync = false;

							//check if exists post type
							$cc_args = array(
								'posts_per_page'   => 	-1,
								'post_type'        =>	MAP_POST_TYPE_COOKIES,
							);

							if( $is_wpml_enabled )
							{
								$cc_args['suppress_filters'] = true;
							}

							if( $is_polylang_enabled )
							{
								$cc_args['lang'] = '';
							}

							$cc_query = new WP_Query( $cc_args );

							if( $cc_query->have_posts() )
							{
								if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'do_not_sync' );

								MyAgilePrivacy::internal_query_reset();
							}
							else
							{
								$do_sync = true;
							}

							//check if exists post type
							$cc_args = array(
								'posts_per_page'   => 	-1,
								'post_type'        =>	MAP_POST_TYPE_POLICY,
							);

							if( $is_wpml_enabled )
							{
								$cc_args['suppress_filters'] = true;
							}

							if( $is_polylang_enabled )
							{
								$cc_args['lang'] = '';
							}

							$cc_query = new WP_Query( $cc_args );

							if( $cc_query->have_posts() )
							{
								if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'do_not_sync' );

								MyAgilePrivacy::internal_query_reset();
							}
							else
							{
								$do_sync = true;
							}

							// Get options
							$the_options = MyAgilePrivacy::get_settings();

							$now = strtotime( "now" );

							$treshold = MAP_LEGIT_SYNC_TRESHOLD;

							if( $the_options &&
								( $the_options['last_legit_sync'] == null || ( ( $the_options['last_legit_sync'] + $treshold ) < $now ) ) )
							{
								$do_sync = true;
							}

							if( $do_sync )
							{
								if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'footer triggering sync_cookies_and_fixed_texts' );

								//bypass_cache
								$this->sync_cookies_and_fixed_texts( true );
								$the_options = MyAgilePrivacy::get_settings();
							}

							if( MAP_MULTILANG_SUPPORT )
							{
								$parity_checker = array();

								if( $is_wpml_enabled )
								{
									$language_list = icl_get_languages();

									foreach( $language_list as $k => $v )
									{
										if( isset( $v['code'] ) )
										{
											$language_list_codes[] = $v['code'];
											$sitepress->switch_lang( $v['code'] );
										}
										elseif( isset( $v['language_code'] ) )
										{
											$language_list_codes[] = $v['language_code'];
											$sitepress->switch_lang( $v['language_code'] );
										}

										$post_status_to_search = array( 'publish' );

										$cc_args = array(
											'posts_per_page'   	=> 	-1,
											'post_type'        	=>	MAP_POST_TYPE_COOKIES,
											'post_status' 		=> 	$post_status_to_search,
											'meta_query'    	=> 	array (
												array(
													'key' 		=> '_map_api_key',
													'value' 	=> '' ,
													'compare' 	=> 'EXISTS',
													'type'    	=> 'NUMERIC',
												),
											),
										);

										$cc_query = new WP_Query( $cc_args );

										if( $cc_query->found_posts > 0 )
										{
											$parity_checker[] = $cc_query->found_posts;

											MyAgilePrivacy::internal_query_reset();
										}
									}

									$sitepress->switch_lang( $wpml_current_lang );
								}

								if( $is_polylang_enabled )
								{
									foreach( $language_list_codes as $k => $v )
									{
										$post_status_to_search = array( 'publish' );

										$cc_args = array(
											'posts_per_page'   	=> 	-1,
											'post_type'        	=>	MAP_POST_TYPE_COOKIES,
											'post_status' 		=> 	$post_status_to_search,
											'meta_query'    	=> 	array (
												array(
													'key' 		=> '_map_api_key',
													'value' 	=> '' ,
													'compare' 	=> 'EXISTS',
													'type'    	=> 'NUMERIC',
												),
											),
										);

										$cc_args['lang'] = $v;

										$cc_query = new WP_Query( $cc_args );

										if( $cc_query->found_posts > 0 )
										{
											$parity_checker[] = $cc_query->found_posts;

											MyAgilePrivacy::internal_query_reset();
										}
									}
								}

								if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $parity_checker );

								if( $with_multilang && count( array_unique( $parity_checker ) ) != 1 )
								{
									if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $parity_checker );

									$this->triggerInternalCookiesRefresh();
								}
							}

							update_option( MAP_PLUGIN_DO_PARITY_CHECK_NOW, 0 );
						}
					}
				}


				if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'END triggered_parity_check' );
			}
		}
		//update_option( MAP_PLUGIN_DO_PARITY_CHECK_NOW, 1 );
	}

	/**
	 * Function to trigger post save on parity check failed
	 * @since    1.1.9
	 * @access   public
	*/
	public function triggerInternalCookiesRefresh()
	{
		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'start triggerInternalCookiesRefresh' );

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

				$multilang_default_lang = $sitepress->get_default_language();
				$wpml_current_lang = ICL_LANGUAGE_CODE;
				$language_list = icl_get_languages();
				$language_list_codes = array();

				foreach( $language_list as $k => $v )
				{
					if( isset( $v['code'] ) )
					{
						$language_list_codes[] = $v['code'];
					}
					elseif( isset( $v['language_code'] ) )
					{
						$language_list_codes[] = $v['language_code'];
					}
				}
			}

			if( defined( 'POLYLANG_FILE' ) &&
				function_exists( 'pll_default_language' ) &&
				function_exists( 'pll_languages_list' ) )
			{
				$is_polylang_enabled = true;
				$with_multilang = true;

				$multilang_default_lang = pll_default_language();
				$language_list_codes = pll_languages_list();
			}
		}

		//check if exists
		if( $is_wpml_enabled )
		{
			$sitepress->switch_lang( $multilang_default_lang );
		}

		$cc_args = array(
			'posts_per_page'   	=> 	-1,
			'post_type'        	=>	MAP_POST_TYPE_COOKIES,
		);

		if( $is_polylang_enabled )
		{
			$cc_args['lang'] = $multilang_default_lang;
		}

		$cc_query = new WP_Query( $cc_args );

		//wpml language reset
		if( $is_wpml_enabled )
		{
			$sitepress->switch_lang( $wpml_current_lang );
		}

		if ( $cc_query->have_posts() )
		{
			foreach ( $cc_query->get_posts() as $p )
			{
				$main_post_id = $p->ID;

				//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $main_post_id );

				$the_post_status = get_post_status( $main_post_id );

				//update
				$my_post = array(
					'ID'           	=> 	$main_post_id,
					'post_status'	=>	$the_post_status,
				);

				if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $my_post );

				wp_update_post( $my_post );
		   }

		   MyAgilePrivacy::internal_query_reset();
		}

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'end parity fix' );
	}


	/**
	* Function for executing remote sync
	 * @since    1.0.12
	 * @access   public
	 */
	public function sync_cookies_and_fixed_texts( $bypass_cache=false )
	{
		// Get options
		$the_options = MyAgilePrivacy::get_settings();

		$sync_in_progress = MyAgilePrivacy::nullCoalesce( get_option( MAP_PLUGIN_SYNC_IN_PROGRESS ), 0 );

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $sync_in_progress );

		if( !( defined( 'MAP_DEV_MODE') && MAP_DEV_MODE ) )
		{
			if( $sync_in_progress )
			{
				$do_block = true;

				$now = strtotime( "now" );

				if( $the_options &&
					( $the_options['last_legit_sync'] ) &&
					( $now - $the_options['last_legit_sync'] > MAP_AUTORESET_SYNC_TRESHOLD ) )
				{
					$do_block = false;

					if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( "unsetting block" );
				}

				if( $do_block )
				{
					if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( "HALT sync_cookies_and_fixed_texts already in progress" );
					return;
				}
			}

			update_option( MAP_PLUGIN_SYNC_IN_PROGRESS, 1 );
		}


		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( "START sync_cookies_and_fixed_texts" );

		global $locale;
		global $sitepress;
		$is_wpml_enabled = false;
		$is_polylang_enabled = false;
		$with_multilang = false;
		$prevent_actions = false;

		if( MAP_MULTILANG_SUPPORT )
		{
			if( function_exists( 'icl_object_id' ) && $sitepress )
			{
				$is_wpml_enabled = true;
				$with_multilang = true;

				$multilang_default_lang = $sitepress->get_default_language();
				$wpml_current_lang = ICL_LANGUAGE_CODE;
				$language_list = icl_get_languages();
				$language_list_codes = array();

				//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $multilang_default_lang );
				//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $wpml_current_lang );
				//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $language_list );

				foreach( $language_list as $k => $v )
				{
					if( isset( $v['code'] ) )
					{
						$language_list_codes[] = $v['code'];
					}
					elseif( isset( $v['language_code'] ) )
					{
						$language_list_codes[] = $v['language_code'];
					}
				}
			}

			if( defined( 'POLYLANG_FILE' ) &&
				function_exists( 'pll_default_language' ) &&
				function_exists( 'pll_languages_list' ) )
			{
				$is_polylang_enabled = true;
				$with_multilang = true;

				$multilang_default_lang = pll_default_language();
				$language_list_codes = pll_languages_list();

				//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $default );
				//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $language_list_codes );
			}

			if( $with_multilang && !$multilang_default_lang )
			{
				$is_wpml_enabled = false;
				$is_polylang_enabled = false;
				$with_multilang = false;
				$prevent_actions = true;

				if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'error on multilang' );
			}

			remove_action( 'save_post_'.MAP_POST_TYPE_COOKIES, array( $this, 'save_custom_metabox_cookies' ) );
			remove_action( 'save_post_'.MAP_POST_TYPE_POLICY, array( $this, 'save_custom_metabox_policies' ) );
		}

		if( isset( $the_options['default_locale'] ) )
		{
			$old_locale = $locale;
			$locale = $the_options['default_locale'];
		}

		$urlparts = parse_url(home_url());
		$domain = $urlparts['host'];

		$data_to_send = array(
			'action'			=>	'get_cookies_and_fixed_text',
			'software_key'		=>	MAP_SOFTWARE_KEY,
			'hash'				=>	$the_options['license_code'],
			'domain'			=>	$domain,
			'locale'			=>	$locale,
			'version'			=>	MAP_PLUGIN_VERSION,
			'options_summary'	=>	$this->get_options_summary(),
			'server_data'		=>	MyAgilePrivacy::getServerFootPrint(),
			'bypass_cache'		=>	$bypass_cache,
		);

		if( $with_multilang )
		{
			$data_to_send['languages'] = implode( ',', $language_list_codes );
		}

		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $data_to_send );

		$action_result = MyAgilePrivacy::call_api( $data_to_send );

		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $action_result );

		$rr = false;
		$pa = 0;
		$is_dm = false;

		if( !$action_result ||
			( $action_result && isset( $action_result['internal_error_message'] ) )
		)
		{
			$rr = false;
		}
		else
		{
			if( $action_result['success'] )
			{
				$rr = true;

				$license_valid = true;
				$grace_period = false;

				if( $action_result['paid_license'] == 0 )
				{
					$license_user_status = 'Demo license';
					$is_dm = true;

					if( isset( $action_result['error_msg'] ) )
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
					elseif( isset( $action_result['error_msg'] ) )
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
				$license_user_status = $action_result['error_msg'];
			}
		}

		if( $rr )
		{
			$customer_email = $action_result['customer_email'];
			$summary_text = $action_result['summary_text'];

			$the_options['license_user_status'] = $license_user_status;
			$the_options['is_dm'] = $is_dm;
			$the_options['license_valid'] = $license_valid;
			$the_options['grace_period'] = $grace_period;
			$the_options['customer_email'] = $customer_email;
			$the_options['summary_text'] = $summary_text;
			$the_options['wl'] = ( isset( $action_result['wl'] ) ) ? $action_result['wl'] : 0;
			$the_options['parse_config'] = ( isset( $action_result['parse_config'] ) ) ? $action_result['parse_config'] : null;
			$the_options['last_legit_sync'] = strtotime( "now" );
			$the_options['pa'] = $pa;
			$rconfig = ( isset( $action_result['rconfig'] ) ) ? $action_result['rconfig'] : null;
			$compliance_report = ( isset( $action_result['compliance_report'] ) ) ? $action_result['compliance_report'] : null;
			update_option( MAP_PLUGIN_RCONFIG, $rconfig );
			update_option( MAP_PLUGIN_COMPLIANCE_REPORT, $compliance_report );

			//if( $can_update_settings )
			{
				update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_options );
				$the_options = MyAgilePrivacy::get_settings();
			}
		}
		else
		{
			$license_user_status = $the_options['license_user_status'];
			$is_dm = $the_options['is_dm'];
			$license_valid = $the_options['license_valid'];
			$grace_period = $the_options['grace_period'];
			$customer_email = $the_options['customer_email'];
			$summary_text = $the_options['summary_text'];
			$rconfig = MyAgilePrivacy::get_rconfig();
		}

		//eof licensing update part

		if( $rr )
		{
			$cookies = $action_result['cookies'];
			$fixed_text = $action_result['fixed_text'];

			$sync_datetime_human = strtotime( "now" );
			$sync_datetime = time();

			if( function_exists( 'wp_date') )
			{
				$wp_date_format = get_option( 'date_format', null );

				if( $wp_date_format )
				{
					$wp_date_format .= ' H:i:s';

					$sync_datetime_human = wp_date( $wp_date_format, $sync_datetime );
				}
			}

			if( $action_result &&
				$action_result['success'] &&
				$action_result['valid_license']
			)
			{
				//no action on error on multilang
				if( $prevent_actions == false )
				{
					//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $with_multilang );
					//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $is_wpml_enabled );
					//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $is_polylang_enabled );

					foreach( $cookies as $k => $v )
					{
						if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $v );

						//check if exists
						if( $is_wpml_enabled )
						{
							$sitepress->switch_lang( $multilang_default_lang );
						}

						$post_status_to_search = array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', '__expired', '__blocked', '__always_allowed' );

						$cc_args = array(
							'posts_per_page'   	=> 	-1,
							'post_type'        	=>	MAP_POST_TYPE_COOKIES,
							'meta_key'         	=> 	'_map_remote_id',
							'meta_value'       	=> 	$v['remote_id'],
							'post_status' 		=> 	$post_status_to_search,
						);

						if( $is_polylang_enabled )
						{
							$cc_args['lang'] = $multilang_default_lang;
						}

						$cc_query = new WP_Query( $cc_args );

						//wpml language reset
						if( $is_wpml_enabled )
						{
							$sitepress->switch_lang( $wpml_current_lang );
						}

						if ( $cc_query->have_posts() )
						{
							foreach ( $cc_query->get_posts() as $p )
							{
								$main_post_id = $p->ID;

								$post_type = get_post_type( $main_post_id );

								//double check for strange theme / plugins
								if( $post_type == MAP_POST_TYPE_COOKIES )
								{
									$this_post_status = get_post_status( $main_post_id );

									if( in_array( $this_post_status, $post_status_to_search ) )
									{
										$do_content_sync = false;
										$do_crit_metadata_sync = false;

										$allow_sync = get_post_meta( $main_post_id, '_map_allow_sync', true );

										if( $allow_sync )
										{
											$do_content_sync = true;
										}

										if( $the_options['enable_metadata_sync'] &&
											isset( $v['forced_metadata_sync'] ) &&
											$v['forced_metadata_sync'] == 1 )
										{
											$do_crit_metadata_sync = true;
										}

										if( $do_content_sync || $do_crit_metadata_sync )
										{
											if( $is_polylang_enabled )
											{
												$key_to_check = $multilang_default_lang;

												$name_key_to_read = ( isset( $v[ 'name_'.$key_to_check ] ) ) ? $v[ 'name_'.$key_to_check ] : $v[ 'name' ];
												$text_key_to_read = ( isset( $v[ 'text_'.$key_to_check ] ) ) ? $v[ 'text_'.$key_to_check ] : $v[ 'text' ];
											}
											else
											{
												$name_key_to_read = $v[ 'name' ];
												$text_key_to_read = $v[ 'text' ];
											}

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

											update_post_meta( $main_post_id, "_map_name", $v['name'] );
											update_post_meta( $main_post_id, "_map_is_free", $v['is_free'] );
											update_post_meta( $main_post_id, "_map_is_readonly", $v['is_readonly'] );
											update_post_meta( $main_post_id, "_map_help_url", $v['help_url'] );
											update_post_meta( $main_post_id, "_map_api_key", $v['api_key'] );
											update_post_meta( $main_post_id, "_map_source_hash", $v['source_hash'] );
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

											if( $is_polylang_enabled )
											{
												pll_set_post_language( $main_post_id, $multilang_default_lang );
											}

											if( $with_multilang )
											{
												if( $is_wpml_enabled )
												{
													$t_post_id = apply_filters( 'wpml_element_trid', null, $main_post_id );
													$translations = apply_filters( 'wpml_get_element_translations', null, $t_post_id, 'post_'.MAP_POST_TYPE_COOKIES );
												}

												if( $is_polylang_enabled )
												{
													$translations = pll_get_post_translations( $main_post_id );
												}

												if( $translations )
												{
													$to_update = array();
													$to_insert = array();

													foreach( $language_list_codes as $the_key )
													{
														$to_insert[ $the_key ] = null;
													}

													unset( $to_insert[ $multilang_default_lang ] );

													foreach( $translations as $t_key => $t_value )
													{
														if( $t_key != $multilang_default_lang )
														{
															if( $is_wpml_enabled )
															{
																$to_update[ $t_key ] = $t_value->element_id;
															}

															if( $is_polylang_enabled )
															{
																$to_update[ $t_key ] = $t_value;
															}

															unset( $to_insert[ $t_key ] );
														}
													}

													//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $to_insert );
													//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $to_update );

													if( $to_insert )
													{
														if( $is_polylang_enabled )
														{
															$poly_posts = array();
														}

														foreach( $to_insert as $key_to_insert => $value_to_insert )
														{
															$name_key_to_read = ( isset( $v[ 'name_'.$key_to_insert ] ) ) ? $v[ 'name_'.$key_to_insert ] : $v[ 'name_en' ];
															$text_key_to_read = ( isset( $v[ 'text_'.$key_to_insert ] ) ) ? $v[ 'text_'.$key_to_insert ] : $v[ 'text_en' ];

															$this_post_title = $name_key_to_read;
															$this_post_content = $text_key_to_read;

															//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $this_post_title );

															if( $this_post_title && $this_post_content )
															{
																$do_insert = true;

																if( $is_wpml_enabled )
																{
																	//check if exists
																	if( $is_wpml_enabled )
																	{
																		$sitepress->switch_lang( $key_to_insert );
																	}

																	$post_status_to_search  = array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', '__expired', '__blocked', '__always_allowed' );

																	$cc_search_args = array(
																		'posts_per_page'   	=> 	-1,
																		'post_type'        	=>	MAP_POST_TYPE_COOKIES,
																		'meta_key'         	=> 	'_map_remote_id',
																		'meta_value'       	=> 	$v['remote_id'],
																		'post_status' 		=> 	$post_status_to_search,
																	);

																	MyAgilePrivacy::internal_query_reset();
																	$cc_search_query = new WP_Query( $cc_search_args );

																	//wpml language reset
																	if( $is_wpml_enabled )
																	{
																		$sitepress->switch_lang( $wpml_current_lang );
																	}

																	if ( $cc_search_query->have_posts() )
																	{
																		$do_insert = false;
																	}
																}

																if( $do_insert )
																{
																	$meta_input = array(
																		'_map_remote_id'					=>	$v['remote_id'],
																		'_map_name'							=>	$v['name'],
																		'_map_is_free'						=>	$v['is_free'],
																		'_map_is_necessary'					=>	( $v['is_necessary'] ) ? 'necessary' : 'not-necessary',
																		'_map_suggested_is_necessary'		=>	( $v['is_necessary'] ) ? 'necessary' : 'not-necessary',
																		'_map_is_readonly'					=>	$v['is_readonly'],
																		'_map_help_url'						=>	$v['help_url'],
																		'_map_api_key'						=>	$v['api_key'],
																		'_map_allow_sync'					=>	1,
																		'_map_page_reload_on_user_consent'	=>	$v['page_reload_on_user_consent'],
																		'_map_sync_datetime_human'			=>	$sync_datetime_human,
																		'_map_sync_datetime'				=>	$sync_datetime,
																	);

																	//insert
																	$new_post = array (
																		'post_type' 		=> 	MAP_POST_TYPE_COOKIES,
																		'post_title' 		=>  $this_post_title,
																		'post_content' 		=> 	$this_post_content,
																		'post_status' 		=> 	$the_post_status,
																		'comment_status' 	=> 	'closed',
																		'ping_status' 		=> 	'closed',
																		'meta_input'		=>	$meta_input,
																	);


																	//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $new_post );

																	$translated_post_id = wp_insert_post( $new_post );

																	if( $is_wpml_enabled )
																	{
																		$linking_details = $sitepress->set_element_language_details( $translated_post_id, 'post_'.MAP_POST_TYPE_COOKIES, $t_post_id, $key_to_insert );

																		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $linking_details );
																	}

																	if( $is_polylang_enabled )
																	{
																		$poly_posts[ $key_to_insert ] = $translated_post_id;
																	}
																}
															}
														}

														if( $is_polylang_enabled )
														{
															foreach( $poly_posts as $poly_language => $poly_value )
															{
																pll_set_post_language( $poly_value, $poly_language );
															}

															$merged = array_merge( array( $multilang_default_lang => $main_post_id ) , $poly_posts );

															pll_save_post_translations( $merged );
														}

													}

													//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $to_update );

													if( $to_update )
													{
														foreach( $to_update as $key_to_update => $to_update_post_id )
														{
															if( $to_update_post_id )
															{
																$name_key_to_read = ( isset( $v[ 'name_'.$key_to_update ] ) ) ? $v[ 'name_'.$key_to_update ] : $v[ 'name_en' ];
																$text_key_to_read = ( isset( $v[ 'text_'.$key_to_update ] ) ) ? $v[ 'text_'.$key_to_update ] : $v[ 'text_en' ];

																$this_post_title = $name_key_to_read;
																$this_post_content = $text_key_to_read;

																if( $this_post_title && $this_post_content )
																{
																	//update
																	$my_post = array(
																		'ID'           	=> 	$to_update_post_id,
																		'post_status'	=>	$the_post_status,
																	);

																	if( $do_content_sync )
																	{
																		$my_post['post_title'] = $this_post_title;
																		$my_post['post_content'] = $this_post_content;
																	}

																	//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $my_post );

																	wp_update_post( $my_post );

																	if( !metadata_exists( 'post', $to_update_post_id, '_map_remote_id'))
																	{
																		update_post_meta( $to_update_post_id, "_map_remote_id", $v['remote_id'] );
																	}

																	update_post_meta( $to_update_post_id, "_map_sync_datetime_human", $sync_datetime_human );
																	update_post_meta( $to_update_post_id, "_map_sync_datetime", $sync_datetime );
																}
															}
														}
													}
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

												if( $with_multilang )
												{
													if( $is_wpml_enabled )
													{
														$t_post_id = apply_filters( 'wpml_element_trid', null, $main_post_id );
														$translations = apply_filters( 'wpml_get_element_translations', null, $t_post_id, 'post_'.MAP_POST_TYPE_COOKIES );
													}

													if( $is_polylang_enabled )
													{
														$translations = pll_get_post_translations( $main_post_id );
													}

													if( $translations )
													{
														$to_update = array();

														foreach( $translations as $t_key => $t_value )
														{
															if( $t_key != $multilang_default_lang )
															{
																if( $is_wpml_enabled )
																{
																	$to_update[ $t_key ] = $t_value->element_id;
																}

																if( $is_polylang_enabled )
																{
																	$to_update[ $t_key ] = $t_value;
																}

															}
														}

														if( $to_update )
														{
															foreach( $to_update as $key_to_update => $to_update_post_id )
															{
																if( $to_update_post_id )
																{
																	//update
																	$my_post = array(
																		'ID'           	=> 	$to_update_post_id,
																		'post_status'	=>	'draft',
																	);

																	//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $my_post );

																	wp_update_post( $my_post );

																	update_post_meta( $to_update_post_id, "_map_sync_datetime_human", $sync_datetime_human );
																	update_post_meta( $to_update_post_id, "_map_sync_datetime", $sync_datetime );
																}
															}
														}
													}
												}
											}

											update_post_meta( $main_post_id, "_map_api_key", $v['api_key'] );
											update_post_meta( $main_post_id, "_map_help_url", $v['help_url'] );
											update_post_meta( $main_post_id, "_map_source_hash", $v['source_hash'] );
										}
									}
								}
							}

							MyAgilePrivacy::internal_query_reset();
						}
						else
						{
							//create
							$this_go_on = true;

							//double check before creating
							if( $with_multilang == false )
							{
								$alt_cc_args = array(
									'posts_per_page'   	=> 	-1,
									'post_type'        	=>	MAP_POST_TYPE_COOKIES,
									'meta_key'         	=> 	'_map_remote_id',
									'meta_value'       	=> 	$v['remote_id'],
									'post_status' 		=> 	$post_status_to_search,
								);

								$alt_cc_query = new WP_Query( $alt_cc_args );

								if ( $alt_cc_query->have_posts() )
								{
									$this_go_on = false;

									MyAgilePrivacy::internal_query_reset();
								}
							}

							if( $this_go_on )
							{
								if( $is_polylang_enabled )
								{
									$key_to_check = $multilang_default_lang;

									$name_key_to_read = ( isset( $v[ 'name_'.$key_to_check ] ) ) ? $v[ 'name_'.$key_to_check ] : $v[ 'name' ];
									$text_key_to_read = ( isset( $v[ 'text_'.$key_to_check ] ) ) ? $v[ 'text_'.$key_to_check ] : $v[ 'text' ];
								}
								else
								{
									$name_key_to_read = $v[ 'name' ];
									$text_key_to_read = $v[ 'text' ];
								}

								$meta_input = array(
									'_map_remote_id'					=>	$v['remote_id'],
									'_map_name'							=>	$v['name'],
									'_map_is_free'						=>	$v['is_free'],
									'_map_is_necessary'					=>	( $v['is_necessary'] ) ? 'necessary' : 'not-necessary',
									'_map_suggested_is_necessary'		=>	( $v['is_necessary'] ) ? 'necessary' : 'not-necessary',
									'_map_is_readonly'					=>	$v['is_readonly'],
									'_map_help_url'						=>	$v['help_url'],
									'_map_api_key'						=>	$v['api_key'],
									'_map_source_hash'					=>	$v['source_hash'],
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

								if( $is_polylang_enabled )
								{
									pll_set_post_language( $main_post_id, $multilang_default_lang );
								}

								//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $main_post_id );

								if( $with_multilang )
								{
									if( $is_wpml_enabled )
									{
										$t_post_id = apply_filters( 'wpml_element_trid', null, $main_post_id );
										$translations = apply_filters( 'wpml_get_element_translations', null, $t_post_id, 'post_'.MAP_POST_TYPE_COOKIES );
									}

									if( $is_polylang_enabled )
									{
										$translations = pll_get_post_translations( $main_post_id );
									}

									if( $translations )
									{
										if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $translations );

										$to_insert = array();

										foreach( $language_list_codes as $the_key )
										{
											$to_insert[ $the_key ] = null;
										}

										unset( $to_insert[ $multilang_default_lang ] );

										foreach( $translations as $t_key => $t_value )
										{
											if( $t_key != $multilang_default_lang )
											{
												unset( $to_insert[ $t_key ] );
											}
										}

										if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $to_insert );

										if( $to_insert )
										{
											if( $is_polylang_enabled )
											{
												$poly_posts = array();
											}

											foreach( $to_insert as $key_to_insert => $value_to_insert )
											{
												if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $key_to_insert );

												$name_key_to_read = ( isset( $v[ 'name_'.$key_to_insert ] ) ) ? $v[ 'name_'.$key_to_insert ] : $v[ 'name_en' ];
												$text_key_to_read = ( isset( $v[ 'text_'.$key_to_insert ] ) ) ? $v[ 'text_'.$key_to_insert ] : $v[ 'text_en' ];

												$this_post_title = $name_key_to_read;
												$this_post_content = $text_key_to_read;

												if( $this_post_title && $this_post_content )
												{
													$do_insert = true;

													if( $is_wpml_enabled )
													{
														//check if exists
														$sitepress->switch_lang( $key_to_insert );

														$post_status_to_search  = array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', '__expired', '__blocked', '__always_allowed' );

														$cc_search_args = array(
															'posts_per_page'   	=> 	-1,
															'post_type'        	=>	MAP_POST_TYPE_COOKIES,
															'meta_key'         	=> 	'_map_remote_id',
															'meta_value'       	=> 	$v['remote_id'],
															'post_status' 		=> 	$post_status_to_search,
														);

														$cc_search_query = new WP_Query( $cc_search_args );

														//wpml language reset
														if( $is_wpml_enabled )
														{
															$sitepress->switch_lang( $wpml_current_lang );
														}

														if ( $cc_search_query->have_posts() )
														{
															$do_insert = false;

															MyAgilePrivacy::internal_query_reset();
														}
													}

													if( $do_insert )
													{
														$meta_input = array(
															'_map_remote_id'					=>	$v['remote_id'],
															'_map_name'							=>	$v['name'],
															'_map_is_free'						=>	$v['is_free'],
															'_map_is_necessary'					=>	( $v['is_necessary'] ) ? 'necessary' : 'not-necessary',
															'_map_suggested_is_necessary'		=>	( $v['is_necessary'] ) ? 'necessary' : 'not-necessary',
															'_map_is_readonly'					=>	$v['is_readonly'],
															'_map_help_url'						=>	$v['help_url'],
															'_map_api_key'						=>	$v['api_key'],
															'_map_source_hash'					=>	$v['source_hash'],
															'_map_allow_sync'					=>	1,
															'_map_page_reload_on_user_consent'	=>	$v['page_reload_on_user_consent'],
															'_map_sync_datetime_human'			=>	$sync_datetime_human,
															'_map_sync_datetime'				=>	$sync_datetime,
														);


														//insert
														$new_post = array (
															'post_type' 		=> 	MAP_POST_TYPE_COOKIES,
															'post_title' 		=>  $this_post_title,
															'post_content' 		=> 	$this_post_content,
															'post_status' 		=> 	'draft',
															'comment_status' 	=> 	'closed',
															'ping_status' 		=> 	'closed',
															'meta_input'		=>	$meta_input,
														);

														if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $new_post );

														$translated_post_id = wp_insert_post( $new_post );

														if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $translated_post_id );

														if( $is_wpml_enabled )
														{
															$linking_details = $sitepress->set_element_language_details( $translated_post_id, 'post_'.MAP_POST_TYPE_COOKIES, $t_post_id, $key_to_insert );

															if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $linking_details );
														}

														if( $is_polylang_enabled )
														{
															$poly_posts[ $key_to_insert ] = $translated_post_id;
														}
													}
												}
											}

											//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $poly_posts );

											if( $is_polylang_enabled )
											{
												foreach( $poly_posts as $poly_language => $poly_value )
												{
													pll_set_post_language( $poly_value, $poly_language );
												}

												$merged = array_merge( array( $multilang_default_lang => $main_post_id ), $poly_posts );

												if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $merged );

												pll_save_post_translations( $merged );
											}
										}
									}
								}
							}
						}
					}

					foreach( $fixed_text as $k => $v )
					{
						//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $v );

						//check if exists
						if( $is_wpml_enabled )
						{
							$sitepress->switch_lang( $multilang_default_lang );
						}

						$cc_args = array(
							'posts_per_page'   => 	-1,
							'post_type'        =>	MAP_POST_TYPE_POLICY,
							'meta_key'         => 	'_map_remote_id',
							'meta_value'       => 	$v['remote_id']
						);

						if( $is_polylang_enabled )
						{
							$cc_args['lang'] = $multilang_default_lang;
						}

						$cc_query = new WP_Query( $cc_args );

						//wpml language reset
						if( $is_wpml_enabled )
						{
							$sitepress->switch_lang( $wpml_current_lang );
						}

						if ( $cc_query->have_posts() )
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
										if( $is_polylang_enabled )
										{
											$key_to_check = $multilang_default_lang;

											$name_key_to_read = ( isset( $v[ 'name_'.$key_to_check ] ) ) ? $v[ 'name_'.$key_to_check ] : $v[ 'name' ];
											$text_key_to_read = ( isset( $v[ 'text_'.$key_to_check ] ) ) ? $v[ 'text_'.$key_to_check ] : $v[ 'text' ];
										}
										else
										{
											$name_key_to_read = $v[ 'name' ];
											$text_key_to_read = $v[ 'text' ];
										}


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

										update_post_meta( $main_post_id, "_map_name", $v['name'] );
										update_post_meta( $main_post_id, "_map_is_free", $v['is_free'] );
										update_post_meta( $main_post_id, "_map_sync_datetime_human", $sync_datetime_human );
										update_post_meta( $main_post_id, "_map_sync_datetime", $sync_datetime );

										if( $is_polylang_enabled )
										{
											pll_set_post_language( $main_post_id, $multilang_default_lang );
										}

										if( $with_multilang )
										{
											if( $is_wpml_enabled )
											{
												$t_post_id = apply_filters( 'wpml_element_trid', null, $main_post_id );
												$translations = apply_filters( 'wpml_get_element_translations', null, $t_post_id, 'post_'.MAP_POST_TYPE_POLICY );
												//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $t_post_id );
												//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $translations );
											}

											if( $is_polylang_enabled )
											{
												$translations = pll_get_post_translations( $main_post_id );
											}

											if( $translations )
											{
												$to_update = array();
												$to_insert = array();

												foreach( $language_list_codes as $the_key )
												{
													$to_insert[ $the_key ] = null;
												}

												unset( $to_insert[ $multilang_default_lang ] );

												foreach( $translations as $t_key => $t_value )
												{
													if( $t_key != $multilang_default_lang )
													{
														if( $is_wpml_enabled )
														{
															$to_update[ $t_key ] = $t_value->element_id;
														}

														if( $is_polylang_enabled )
														{
															$to_update[ $t_key ] = $t_value;
														}

														unset( $to_insert[ $t_key ] );
													}
												}

												if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $to_insert );

												if( $to_insert )
												{
													if( $is_polylang_enabled )
													{
														$poly_posts = array();
													}

													foreach( $to_insert as $key_to_insert => $value_to_insert )
													{
														$name_key_to_read = $v['name'];
														$text_key_to_read = ( isset( $v[ 'text_'.$key_to_insert ] ) ) ? $v[ 'text_'.$key_to_insert ] : $v[ 'text_en' ];

														$this_post_title = $name_key_to_read;
														$this_post_content = $text_key_to_read;

														//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $this_post_title );

														//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $this_post_content );

														if( $this_post_title && $this_post_content )
														{
															$do_insert = true;

															if( $is_wpml_enabled )
															{
																//check if exists
																if( $is_wpml_enabled )
																{
																	$sitepress->switch_lang( $key_to_insert );
																}

																$post_status_to_search = array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', '__expired', '__blocked', '__always_allowed' );

																$cc_search_args = array(
																	'posts_per_page'   	=> 	-1,
																	'post_type'        	=>	MAP_POST_TYPE_POLICY,
																	'meta_key'         	=> 	'_map_remote_id',
																	'meta_value'       	=> 	$v['remote_id'],
																	'post_status' 		=> 	$post_status_to_search,
																);

																$cc_search_query = new WP_Query( $cc_search_args );

																//wpml language reset
																if( $is_wpml_enabled )
																{
																	$sitepress->switch_lang( $wpml_current_lang );
																}

																if ( $cc_search_query->have_posts() )
																{
																	$do_insert = false;

																	MyAgilePrivacy::internal_query_reset();
																}
															}

															if( $do_insert )
															{
																//insert
																$new_post = array (
																	'post_type' 		=> 	MAP_POST_TYPE_POLICY,
																	'post_title' 		=>  $this_post_title,
																	'post_content' 		=> 	$this_post_content,
																	'post_status' 		=> 	$the_post_status,
																	'comment_status' 	=> 	'closed',
																	'ping_status' 		=> 	'closed',
																	'meta_input'		=>	array(
																									'_map_remote_id'			=>	$v['remote_id'],
																									'_map_name'					=>	$v['name'],
																									'_map_is_free'				=>	$v['is_free'],
																									'_map_option1_ack'			=>	0,
																									'_map_option1_on'			=>	0,
																									'_map_allow_sync'			=>	1,
																									'_map_sync_datetime_human'	=>	$sync_datetime_human,
																									'_map_sync_datetime'				=>	$sync_datetime,
																								)
																);

																if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $new_post );

																$translated_post_id = wp_insert_post( $new_post );

																if( $is_wpml_enabled )
																{
																	$linking_details = $sitepress->set_element_language_details( $translated_post_id, 'post_'.MAP_POST_TYPE_POLICY, $t_post_id, $key_to_insert );
																	//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $linking_details );
																}

																if( $is_polylang_enabled )
																{
																	$poly_posts[ $key_to_insert ] = $translated_post_id;
																}
															}
														}
													}

													if( $is_polylang_enabled )
													{
														foreach( $poly_posts as $poly_language => $poly_value )
														{
															pll_set_post_language( $poly_value, $poly_language );
														}

														$merged = array_merge( array( $multilang_default_lang => $main_post_id ) , $poly_posts );

														pll_save_post_translations( $merged );
													}
												}

												//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $to_update );

												if( $to_update )
												{
													foreach( $to_update as $key_to_update => $to_update_post_id )
													{
														if( $to_update_post_id )
														{
															$name_key_to_read = $v['name'];
															$text_key_to_read = ( isset( $v[ 'text_'.$key_to_update ] ) ) ? $v[ 'text_'.$key_to_update ] : $v[ 'text_en' ];

															$this_post_title = $name_key_to_read;
															$this_post_content = $text_key_to_read;

															if( $this_post_title && $this_post_content )
															{
																//update
																$my_post = array(
																	'ID'           	=> 	$to_update_post_id,
																	'post_title' 	=>  $this_post_title,
																	'post_content' 	=> 	$this_post_content,
																	'post_status'	=>	$the_post_status,
																);

																//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $my_post );

																wp_update_post( $my_post );

																if( !metadata_exists( 'post', $to_update_post_id, '_map_remote_id'))
																{
																	update_post_meta( $to_update_post_id, "_map_remote_id", $v['remote_id'] );
																}

																update_post_meta( $to_update_post_id, "_map_sync_datetime_human", $sync_datetime_human );
																update_post_meta( $to_update_post_id, "_map_sync_datetime", $sync_datetime );
															}
														}
													}
												}
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

											if( $with_multilang )
											{
												if( $is_wpml_enabled )
												{
													$t_post_id = apply_filters( 'wpml_element_trid', null, $main_post_id );
													$translations = apply_filters( 'wpml_get_element_translations', null, $t_post_id, 'post_'.MAP_POST_TYPE_POLICY );
												}

												if( $translations )
												{
													$to_update = array();

													foreach( $translations as $t_key => $t_value )
													{
														if( $t_key != $multilang_default_lang )
														{
															$to_update[ $t_key ] = $t_value->element_id;
														}
													}

													if( $to_update )
													{
														foreach( $to_update as $key_to_update => $to_update_post_id )
														{
															if( $to_update_post_id )
															{
																//update
																$my_post = array(
																	'ID'           	=> 	$to_update_post_id,
																	'post_status'	=>	'draft',
																);

																//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $my_post );

																wp_update_post( $my_post );
															}
														}
													}
												}
											}
										}
									}

									update_post_meta( $main_post_id, "_map_source_hash", $v['source_hash'] );
								}
							}
						}
						else
						{
							//create
							$this_go_on = true;

							//double check before creating
							if( $with_multilang == false )
							{
								$alt_cc_args = array(
									'posts_per_page'   	=> 	-1,
									'post_type'        	=>	MAP_POST_TYPE_POLICY,
									'meta_key'         	=> 	'_map_remote_id',
									'meta_value'       	=> 	$v['remote_id'],
								);

								$alt_cc_query = new WP_Query( $alt_cc_args );

								if ( $alt_cc_query->have_posts() )
								{
									$this_go_on = false;

									MyAgilePrivacy::internal_query_reset();
								}
							}

							if( $this_go_on )
							{
								if( $is_polylang_enabled )
								{
									$key_to_check = $multilang_default_lang;

									$name_key_to_read = ( isset( $v[ 'name_'.$key_to_check ] ) ) ? $v[ 'name_'.$key_to_check ] : $v[ 'name' ];
									$text_key_to_read = ( isset( $v[ 'text_'.$key_to_check ] ) ) ? $v[ 'text_'.$key_to_check ] : $v[ 'text' ];
								}
								else
								{
									$name_key_to_read = $v[ 'name' ];
									$text_key_to_read = $v[ 'text' ];
								}

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
																'_map_source_hash'			=>	$v['source_hash'],
																'_map_allow_sync'			=>	1,
																'_map_sync_datetime_human'	=>	$sync_datetime_human,
																'_map_sync_datetime'		=>	$sync_datetime,
															)
								);

								$main_post_id = wp_insert_post( $new_post );

								if( $is_polylang_enabled )
								{
									pll_set_post_language( $main_post_id, $multilang_default_lang );
								}

								if( $with_multilang )
								{
									if( $is_wpml_enabled )
									{
										$t_post_id = apply_filters( 'wpml_element_trid', null, $main_post_id );
										$translations = apply_filters( 'wpml_get_element_translations', null, $t_post_id, 'post_'.MAP_POST_TYPE_POLICY );
									}

									if( $is_polylang_enabled )
									{
										$translations = pll_get_post_translations( $main_post_id );
									}

									if( $translations )
									{
										//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $translations );

										$to_insert = array();

										foreach( $language_list_codes as $the_key )
										{
											$to_insert[ $the_key ] = null;
										}

										unset( $to_insert[ $multilang_default_lang ] );

										foreach( $translations as $t_key => $t_value )
										{
											if( $t_key != $multilang_default_lang )
											{
												unset( $to_insert[ $t_key ] );
											}
										}

										//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $to_insert );

										if( $to_insert )
										{
											if( $is_polylang_enabled )
											{
												$poly_posts = array();
											}

											foreach( $to_insert as $key_to_insert => $value_to_insert )
											{
												//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $key_to_insert );

												$name_key_to_read = $v['name'];
												$text_key_to_read = ( isset( $v[ 'text_'.$key_to_insert ] ) ) ? $v[ 'text_'.$key_to_insert ] : $v[ 'text_en' ];

												$this_post_title = $name_key_to_read;
												$this_post_content = $text_key_to_read;

												if( $this_post_title && $this_post_content )
												{
													//insert
													$new_post = array (
														'post_type' 		=>	MAP_POST_TYPE_POLICY,
														'post_title' 		=>	$this_post_title,
														'post_content' 		=>	$this_post_content,
														'post_status' 		=>	'publish',
														'comment_status' 	=>	'closed',
														'ping_status' 		=>	'closed',
														'meta_input'		=>	array(
																					'_map_remote_id'			=>	$v['remote_id'],
																					'_map_name'					=>	$v['name'],
																					'_map_is_free'				=>	$v['is_free'],
																					'_map_source_hash'			=>	$v['source_hash'],
																					'_map_allow_sync'			=>	1,
																					'_map_sync_datetime_human'	=>	$sync_datetime_human,
																					'_map_sync_datetime'		=>	$sync_datetime,
																				)
													);

													//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $new_post );

													$translated_post_id = wp_insert_post( $new_post );

													if( $is_wpml_enabled )
													{
														$linking_details = $sitepress->set_element_language_details( $translated_post_id, 'post_'.MAP_POST_TYPE_POLICY, $t_post_id, $key_to_insert );

														//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $linking_details );
													}

													if( $is_polylang_enabled )
													{
														$poly_posts[ $key_to_insert ] = $translated_post_id;
													}

												}
											}

											if( $is_polylang_enabled )
											{
												foreach( $poly_posts as $poly_language => $poly_value )
												{
													pll_set_post_language( $poly_value, $poly_language );
												}

												$merged = array_merge( array( $multilang_default_lang => $main_post_id ) , $poly_posts );

												//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $merged );

												pll_save_post_translations( $merged );
											}
										}
									}
								}
							}
						}
					}
				}
			}

			if(
				!( $action_result &&
					$action_result['success'] &&
					$action_result['valid_license']
				) || !$license_valid
			)
			{
				if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'make expire' );

				//check if exists
				if( $is_wpml_enabled )
				{
					$sitepress->switch_lang( $multilang_default_lang );
				}

				$post_status_to_search = array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', '__expired', '__blocked', '__always_allowed' );

				//cookies
				$cc_args = array(
					'posts_per_page'  	=> 	-1,
					'post_type'        	=>	MAP_POST_TYPE_COOKIES,
					'meta_key'         	=> 	'_map_is_free',
					'meta_value'       	=> 	0,
					'post_status' 		=> 	$post_status_to_search,
				);

				if( $is_polylang_enabled )
				{
					$cc_args['lang'] = $multilang_default_lang;
				}

				$cc_query = new WP_Query( $cc_args );

				//wpml language reset
				if( $is_wpml_enabled )
				{
					$sitepress->switch_lang( $wpml_current_lang );
				}

				if ( $cc_query->have_posts() )
				{
					foreach ( $cc_query->get_posts() as $p )
					{
						$main_post_id = $p->ID;

						$post_type = get_post_type( $main_post_id );

						//double check for strange theme / plugins
						if( $post_type == MAP_POST_TYPE_COOKIES )
						{
							$this_post_status = get_post_status( $main_post_id );

							if( in_array( $this_post_status, $post_status_to_search ) )
							{
								if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $main_post_id );

								//update
								$my_post = array(
									'ID'           	=> 	$main_post_id ,
									'post_status'	=>	'__expired',
								);

								wp_update_post( $my_post );

								if( $is_polylang_enabled )
								{
									pll_set_post_language( $main_post_id, $multilang_default_lang );
								}

								if( $with_multilang )
								{
									if( $is_wpml_enabled )
									{
										$t_post_id = apply_filters( 'wpml_element_trid', null, $main_post_id );
										$translations = apply_filters( 'wpml_get_element_translations', null, $t_post_id, 'post_'.MAP_POST_TYPE_COOKIES );
									}

									if( $is_polylang_enabled )
									{
										$translations = pll_get_post_translations( $main_post_id );
									}

									if( $translations )
									{
										$to_update = array();

										foreach( $translations as $t_key => $t_value )
										{
											if( $t_key != $multilang_default_lang )
											{
												if( $is_wpml_enabled )
												{
													$to_update[ $t_key ] = $t_value->element_id;
												}

												if( $is_polylang_enabled )
												{
													$to_update[ $t_key ] = $t_value;
												}
											}
										}

										if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $to_update );

										if( $to_update )
										{
											foreach( $to_update as $key_to_update => $to_update_post_id )
											{
												if( $to_update_post_id )
												{
													//update
													$my_post = array(
														'ID'           	=> 	$to_update_post_id,
														'post_status'	=>	'__expired',
													);

													if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $my_post );

													wp_update_post( $my_post );
												}
											}
										}
									}
								}
							}
						}
					}

					MyAgilePrivacy::internal_query_reset();
				}

				//check if exists
				if( $is_wpml_enabled )
				{
					$sitepress->switch_lang( $multilang_default_lang );
				}

				$post_status_to_search = array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', '__expired' );

				//fixed texts
				$cc_args = array(
					'posts_per_page'  	=> 	-1,
					'post_type'        	=>	MAP_POST_TYPE_POLICY,
					'meta_key'         	=> 	'_map_is_free',
					'meta_value'       	=> 	0,
					'post_status' 		=> 	$post_status_to_search,
				);

				if( $is_polylang_enabled )
				{
					$cc_args['lang'] = $multilang_default_lang;
				}

				$cc_query = new WP_Query( $cc_args );

				//wpml language reset
				if( $is_wpml_enabled )
				{
					$sitepress->switch_lang( $wpml_current_lang );
				}

				if ( $cc_query->have_posts() )
				{
					foreach ( $cc_query->get_posts() as $p )
					{
						$main_post_id = $p->ID;

						$post_type = get_post_type( $main_post_id );

						//double check for strange theme / plugins
						if( $post_type == MAP_POST_TYPE_POLICY )
						{
							$this_post_status = get_post_status( $main_post_id );

							if( in_array( $this_post_status, $post_status_to_search ) )
							{
								//update
								$my_post = array(
									'ID'           	=> 	$main_post_id ,
									'post_status'	=>	'__expired',
								);

								wp_update_post( $my_post );

								if( $is_polylang_enabled )
								{
									pll_set_post_language( $main_post_id, $multilang_default_lang );
								}

								if( $with_multilang )
								{
									if( $is_wpml_enabled )
									{
										$t_post_id = apply_filters( 'wpml_element_trid', null, $main_post_id );
										$translations = apply_filters( 'wpml_get_element_translations', null, $t_post_id, 'post_'.MAP_POST_TYPE_COOKIES );
									}

									if( $is_polylang_enabled )
									{
										$translations = pll_get_post_translations( $main_post_id );
									}

									if( $translations )
									{
										$to_update = array();

										foreach( $translations as $t_key => $t_value )
										{
											if( $t_key != $multilang_default_lang )
											{
												if( $is_wpml_enabled )
												{
													$to_update[ $t_key ] = $t_value->element_id;
												}

												if( $is_polylang_enabled )
												{
													$to_update[ $t_key ] = $t_value;
												}
											}
										}

										if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $to_update );

										if( $to_update )
										{
											foreach( $to_update as $key_to_update => $to_update_post_id )
											{
												if( $to_update_post_id )
												{
													//update
													$my_post = array(
														'ID'           	=> 	$to_update_post_id,
														'post_status'	=>	'__expired',
													);

													if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $my_post );

													wp_update_post( $my_post );
												}
											}
										}
									}
								}
							}
						}
					}

					MyAgilePrivacy::internal_query_reset();
				}
			}
		}

		//parsing autoconsume_options

		if( isset( $rconfig ) && isset( $rconfig['autoconsume_options'] ) )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $rconfig['autoconsume_options'] );

			$autoconsume_options = json_decode( $rconfig['autoconsume_options'], true );

			$rconfig['autoconsume_options'] = null;
			update_option( MAP_PLUGIN_RCONFIG, $rconfig );

			if( isset( $autoconsume_options['forced'] ) )
			{
				if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $the_options );

				if( isset( $autoconsume_options['forced']['forced_auto_update'] ) )
				{
					$the_options['forced_auto_update'] = ( $autoconsume_options['forced']['forced_auto_update'] == 1 ) ? true : false;
					update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_options );
				}

				if( isset( $autoconsume_options['forced']['enable_metadata_sync'] ) )
				{
					$the_options['enable_metadata_sync'] = ( $autoconsume_options['forced']['enable_metadata_sync'] == 1 ) ? true : false;
					update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_options );
				}

				if( isset( $autoconsume_options['forced']['learning_mode'] ) && $autoconsume_options['forced']['learning_mode'] == 1 )
				{
					$the_options['scan_mode'] = 'learning_mode';
					$the_options['learning_mode_last_active_timestamp'] = null;
					update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_options );
				}

				if( isset( $autoconsume_options['forced']['reset_consent'] ) && $autoconsume_options['forced']['reset_consent'] == 1 )
				{
					$timestamp = time();
					$the_options['cookie_reset_timestamp'] = $timestamp;
					update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_options );
				}

				if( isset( $autoconsume_options['added_cookies'] ) && $autoconsume_options['added_cookies'] )
				{
					$added_cookies = $autoconsume_options['added_cookies'];
					$added_cookies = array_filter( $added_cookies );

					if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $added_cookies );

					//no action on error on multilang
					if( $prevent_actions == false )
					{
						//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $with_multilang );
						//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $is_wpml_enabled );
						//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $is_polylang_enabled );

						foreach( $added_cookies as $k => $v )
						{
							if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $v );

							//check if exists
							if( $is_wpml_enabled )
							{
								$sitepress->switch_lang( $multilang_default_lang );
							}

							$post_status_to_search = array( 'draft', 'publish' );

							$cc_args = array(
								'posts_per_page'   	=> 	-1,
								'post_type'        	=>	MAP_POST_TYPE_COOKIES,
								'meta_key'         	=> 	'_map_remote_id',
								'meta_value'       	=> 	$v,
								'post_status' 		=> 	$post_status_to_search,
							);

							if( $is_polylang_enabled )
							{
								$cc_args['lang'] = $multilang_default_lang;
							}

							$cc_query = new WP_Query( $cc_args );

							//wpml language reset
							if( $is_wpml_enabled )
							{
								$sitepress->switch_lang( $wpml_current_lang );
							}

							if ( $cc_query->have_posts() )
							{
								foreach ( $cc_query->get_posts() as $p )
								{
									$main_post_id = $p->ID;

									$post_type = get_post_type( $main_post_id );

									//double check for strange theme / plugins
									if( $post_type == MAP_POST_TYPE_COOKIES )
									{
										$this_post_status = get_post_status( $main_post_id );

										if( in_array( $this_post_status, $post_status_to_search ) )
										{
											//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $main_post_id, true );

											//update
											$my_post = array(
												'ID'           	=> 	$main_post_id,
												'post_status'	=>	'publish',
											);

											if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $my_post );

											wp_update_post( $my_post );

											if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( "published ".$v , true );

											if( $with_multilang )
											{
												if( $is_wpml_enabled )
												{
													$t_post_id = apply_filters( 'wpml_element_trid', null, $main_post_id );

													$translations = apply_filters( 'wpml_get_element_translations', null, $t_post_id, 'post_'.MAP_POST_TYPE_COOKIES );

													//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $t_post_id );
													//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $translations );
												}

												if( $is_polylang_enabled )
												{
													$translations = pll_get_post_translations( $main_post_id );
												}

												if( $translations )
												{
													$to_update = array();

													foreach( $translations as $t_key => $t_value )
													{
														if( $t_key != $multilang_default_lang )
														{
															if( $is_wpml_enabled )
															{
																$to_update[ $t_key ] = $t_value->element_id;
															}

															if( $is_polylang_enabled )
															{
																$to_update[ $t_key ] = $t_value;
															}
														}
													}

													if( $to_update )
													{
														foreach( $to_update as $key_to_update => $to_update_post_id )
														{
															if( $to_update_post_id )
															{
																//update
																$my_post = array(
																	'ID'           	=> 	$to_update_post_id,
																	'post_status'	=>	'publish',
																);

																//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $my_post );

																wp_update_post( $my_post );
															}
														}
													}
												}
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
					MyAgilePrivacy::tryCacheClear();
				}
			}
		}

		update_option( MAP_PLUGIN_SYNC_IN_PROGRESS, 0 );

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( "END sync_cookies_and_fixed_texts" );

		return true;
	}


	/**
	 * Import Cookie Callback
	 *
	 * @since    1.1.9
	 */
	public function import_admin_settings_form_callback()
	{
		if ( !current_user_can( 'manage_options' ) ) 
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( ' import_admin_settings_form_callback -> missing user permission' );
			return false; 
		}
		// Check nonce:
		check_admin_referer( 'myagileprivacy-update-' . MAP_PLUGIN_SETTINGS_FIELD );

		if( $_FILES['the_imported_file']['error'] == UPLOAD_ERR_OK
			&& is_uploaded_file($_FILES['the_imported_file']['tmp_name']))
		{
			$file_content = file_get_contents($_FILES['the_imported_file']['tmp_name']);

			$cookies = json_decode( $file_content, true );

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

					$multilang_default_lang = $sitepress->get_default_language();
					$wpml_current_lang = ICL_LANGUAGE_CODE;
					$language_list = icl_get_languages();
					$language_list_codes = array();

					foreach( $language_list as $k => $v )
					{
						if( isset( $v['code'] ) )
						{
							$language_list_codes[] = $v['code'];
						}
						elseif( isset( $v['language_code'] ) )
						{
							$language_list_codes[] = $v['language_code'];
						}
					}
				}

				if( defined( 'POLYLANG_FILE' ) &&
					function_exists( 'pll_default_language' ) &&
					function_exists( 'pll_languages_list' ) )
				{
					$is_polylang_enabled = true;
					$with_multilang = true;

					$multilang_default_lang = pll_default_language();
					$language_list_codes = pll_languages_list();

					//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $default );
					//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $language_list_codes );
				}

				remove_action( 'save_post_'.MAP_POST_TYPE_COOKIES, array( $this, 'save_custom_metabox_cookies' ) );
			}

			foreach( $cookies['standard'] as $k => $v )
			{
				if( $is_wpml_enabled )
				{
					$sitepress->switch_lang( $multilang_default_lang );
				}

				$post_status_to_search = array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit' );

				$cc_args = array(
					'posts_per_page'   	=> 	-1,
					'post_type'        	=>	MAP_POST_TYPE_COOKIES,
					'meta_key'         	=> 	'_map_api_key',
					'meta_value'       	=> 	$k,
					'post_status' 		=> 	$post_status_to_search,
				);

				if( $is_polylang_enabled )
				{
					$cc_args['lang'] = $multilang_default_lang;
				}

				if( $is_wpml_enabled )
				{
					//$cc_args['suppress_filters'] = true;
				}
				$cc_query = new WP_Query( $cc_args );

				//wpml language reset
				if( $is_wpml_enabled )
				{
					$sitepress->switch_lang( $wpml_current_lang );
				}

				if ( $cc_query->have_posts() )
				{
					foreach ( $cc_query->get_posts() as $p )
					{
						$main_post_id = $p->ID;

						$post_type = get_post_type( $main_post_id );

						//double check for strange theme / plugins
						if( $post_type == MAP_POST_TYPE_COOKIES )
						{
							$this_post_status = get_post_status( $main_post_id );

							if( in_array( $this_post_status, $post_status_to_search ) )
							{
								$my_post = array(
									'ID'           	=> 	$main_post_id,
									'post_status'	=>	'publish',
								);

								wp_update_post( $my_post );

								update_post_meta( $main_post_id, "_map_installation_type", $v['_map_installation_type'] );
								update_post_meta( $main_post_id, "_map_code", $v['_map_code'] );
								update_post_meta( $main_post_id, "_map_raw_code", $v['_map_raw_code'] );
								update_post_meta( $main_post_id, "_map_is_necessary", $v['_map_is_necessary'] );
								update_post_meta( $main_post_id, "_map_is_readonly", $v['_map_is_readonly'] );
								update_post_meta( $main_post_id, "_map_allow_sync", intval( $v['_map_allow_sync'] ) );
								update_post_meta( $main_post_id, "_map_auto_detected", intval( $v['_map_auto_detected'] ) );
								update_post_meta( $main_post_id, "_map_auto_detected_override", intval( $v['_map_auto_detected_override'] ) );
							}
						}
					}

					MyAgilePrivacy::internal_query_reset();
				}
			}

			foreach( $cookies['custom'] as $k => $v )
			{
				if( $is_wpml_enabled )
				{
					$sitepress->switch_lang( $multilang_default_lang );
				}

				$new_post = array (
					'post_type' 		=>	MAP_POST_TYPE_COOKIES,
					'post_title' 		=>	$v['title'],
					'post_content' 		=>	$v['content'],
					'post_status' 		=>	'publish',
					'comment_status' 	=>	'closed',
					'ping_status' 		=>	'closed',
					'meta_input'		=>	array(
												'_map_installation_type'		=>	$v['_map_installation_type'],
												'_map_code'						=>	$v['_map_code'],
												'_map_raw_code'					=>	$v['_map_raw_code'],
												'_map_is_necessary'				=>	$v['_map_is_necessary'],
												'_map_is_readonly'				=>	$v['_map_is_readonly'],
												'_map_allow_sync'				=>	intval( $v['_map_allow_sync'] ),
												'_map_auto_detected'			=>	intval( $v['_map_auto_detected'] ),
												'_map_auto_detected_override'	=>	intval( $v['_map_auto_detected_override'] ),
											)
				);

				//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $new_post );

				$main_post_id = wp_insert_post( $new_post );

				if( $is_polylang_enabled )
				{
					pll_set_post_language( $main_post_id, $multilang_default_lang );
				}

				//wpml language reset
				if( $is_wpml_enabled )
				{
					$sitepress->switch_lang( $wpml_current_lang );
				}
			}
		}

		header( 'Location: ' . $_SERVER['HTTP_REFERER'] );
	}


	/**
	 * Export Cookie Callback
	 *
	 * @since    1.1.9
	 */
	public function backup_admin_settings_form_callback()
	{

		if ( !current_user_can( 'manage_options' ) ) 
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'backup_admin_settings_form_callback -> missing user permission' );
			return false; 
		}

		// Check nonce:
		check_admin_referer( 'myagileprivacy-update-' . MAP_PLUGIN_SETTINGS_FIELD );

		$export_data = array(
			'standard'	=>	array(),
			'custom'	=>	array(),
		);

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

				$multilang_default_lang = $sitepress->get_default_language();
				$wpml_current_lang = ICL_LANGUAGE_CODE;
				$language_list = icl_get_languages();
				$language_list_codes = array();

				foreach( $language_list as $k => $v )
				{
					if( isset( $v['code'] ) )
					{
						$language_list_codes[] = $v['code'];
					}
					elseif( isset( $v['language_code'] ) )
					{
						$language_list_codes[] = $v['language_code'];
					}
				}
			}

			if( defined( 'POLYLANG_FILE' ) &&
				function_exists( 'pll_default_language' ) &&
				function_exists( 'pll_languages_list' ) )
			{
				$is_polylang_enabled = true;
				$with_multilang = true;

				$multilang_default_lang = pll_default_language();
				$language_list_codes = pll_languages_list();

				//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $default );
				//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $language_list_codes );
			}
		}

		if( $is_wpml_enabled )
		{
			$sitepress->switch_lang( $multilang_default_lang );
		}

		$post_status_to_search = array( 'publish' );

		$cc_args = array(
			'posts_per_page'   	=> 	-1,
			'post_type'        	=>	MAP_POST_TYPE_COOKIES,
			'post_status' 		=> 	$post_status_to_search,
		);

		if( $is_polylang_enabled )
		{
			$cc_args['lang'] = $multilang_default_lang;
		}

		$cc_query = new WP_Query( $cc_args );

		//wpml language reset
		if( $is_wpml_enabled )
		{
			$sitepress->switch_lang( $wpml_current_lang );
		}

		if ( $cc_query->have_posts() )
		{
			foreach ( $cc_query->get_posts() as $p )
			{
				$main_post_id = $p->ID;

				$post_type = get_post_type( $main_post_id );

				//double check for strange theme / plugins
				if( $post_type == MAP_POST_TYPE_COOKIES )
				{
					$this_post_status = get_post_status( $main_post_id );

					if( in_array( $this_post_status, $post_status_to_search ) )
					{
						$all_meta = get_post_meta( $main_post_id );

						$key = $all_meta['_map_api_key'][0];

						if( $key )
						{
							$elem_to_save = array(
								'_map_installation_type'		=>	MyAgilePrivacy::nullCoalesce( $all_meta['_map_installation_type'][0], '' ),
								'_map_code'						=>	MyAgilePrivacy::nullCoalesce( $all_meta['_map_code'][0], null ),
								'_map_raw_code'					=>	MyAgilePrivacy::nullCoalesce( $all_meta['_map_raw_code'][0], null ),
								'_map_is_necessary'				=>	MyAgilePrivacy::nullCoalesce( $all_meta['_map_is_necessary'][0], 'not-necessary' ),
								'_map_is_readonly'				=>	MyAgilePrivacy::nullCoalesce( $all_meta['_map_is_readonly'][0], 0 ),
								'_map_allow_sync'				=>	MyAgilePrivacy::nullCoalesce( $all_meta['_map_allow_sync'][0], 1 ),
								'_map_auto_detected'			=>	MyAgilePrivacy::nullCoalesce( $all_meta['_map_auto_detected'][0], 0 ),
								'_map_auto_detected_override'	=>	MyAgilePrivacy::nullCoalesce( $all_meta['_map_auto_detected_override'][0], 0 ),
							);

							$export_data['standard'][ $key ] = $elem_to_save;
						}
						else
						{
							$elem_to_save = array(
								'title'							=>	get_the_title( $main_post_id ),
								'content'						=>	get_post_field( 'post_content', $main_post_id ),
								'_map_installation_type'		=>	MyAgilePrivacy::nullCoalesce( $all_meta['_map_installation_type'][0], '' ),
								'_map_code'						=>	MyAgilePrivacy::nullCoalesce( $all_meta['_map_code'][0], null ),
								'_map_raw_code'					=>	MyAgilePrivacy::nullCoalesce( $all_meta['_map_raw_code'][0], null ),
								'_map_is_necessary'				=>	MyAgilePrivacy::nullCoalesce( $all_meta['_map_is_necessary'][0], 'not-necessary' ),
								'_map_is_readonly'				=>	0,
								'_map_allow_sync'				=>	0,
								'_map_auto_detected'			=>	0,
								'_map_auto_detected_override'	=>	0,
							);

							$export_data['custom'][] = $elem_to_save;
						}
					}
				}
			}

			MyAgilePrivacy::internal_query_reset();
		}

		$json_data = json_encode( $export_data );

		header( 'Content-disposition: attachment; filename=export.json' );
		header( 'Content-type: application/json' );

		echo( $json_data );

		//die();
	}

	//display scan mode in admin topbar
	public function map_adminbar_cookieshield_link()
	{
		global $wp_admin_bar;
		$the_settings = MyAgilePrivacy::get_settings();

		$cookie_shield_raw_status = 'turned_off';
		$cookie_shied_value = 'Off';

		if( isset( $the_settings ) && isset( $the_settings['scan_mode'] ) )
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

		echo '
		<style>
			#wp-admin-bar-map_cookieshield.learning_mode { background: #FFC205 !important; }
			#wp-admin-bar-map_cookieshield.config_finished{ background: #28A745 !important; }
			#wp-admin-bar-map_cookieshield.turned_off{ background: #DC3546 !important; }

			#wp-admin-bar-map_cookieshield a {
				display: flex !important;
				flex-direction: row !important;
				align-items: center !important;
				font-weight: bold !important;
				padding-left: 20px !important;
				padding-right: 20px !important;
				column-gap: 5px !important;
			}
			#wp-admin-bar-map_cookieshield.learning_mode a { color: #333 !important; }
			#wp-admin-bar-map_cookieshield a:hover { color: #fff !important; }
		</style>
		';

		$this_href = null;
		if ( current_user_can( 'manage_options' ) )
		{
			$this_href = admin_url( 'admin.php?page=my-agile-privacy-c_settings#cookieshield' );
		}

		$wp_admin_bar->add_menu( array(
			'id'    => 'map_cookieshield',
			'title' => '<img src="'. plugin_dir_url( __FILE__ ) . 'img/logo.png" style="max-height:18px;width:auto; margin-right:5px;" /> <span class="map_cookieshield_text_status">Cookie Shield: '.$cookie_shied_value.'</span>',
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
			$manifest_assoc = get_option( MAP_MANIFEST_ASSOC, null );
		}


		$current_lang = get_locale();

		$other_data = array(
			'map_version'				=>	MAP_PLUGIN_VERSION,
			'locale'					=>	$current_lang,
			'with_my_agile_pixel'		=> 	false,
			'my_agile_pixel_version'	=> 	null,
			'my_agile_pixel_options'	=> 	null,
			'theme_name'				=> 	null,
			'with_multilang'			=> 	false,
			'is_wpml_enabled'			=> 	false,
			'is_polylang_enabled'		=> 	false,
		);

		//bof theme calc
		$my_theme = wp_get_theme();
		if( $my_theme && is_object( $my_theme ) )
		{
			$other_data['theme_name'] = $my_theme->get( 'Name' );
		}
		//eof theme calc

		//bof my agile pixel
		$with_my_agile_pixel = false;

		if (!function_exists( 'is_plugin_active' ) ) {
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
				$other_data['my_agile_pixel_options'] = get_option( MAPX_PLUGIN_SETTINGS_FIELD, null );
			}
		}
		$other_data['with_my_agile_pixel'] = $with_my_agile_pixel;

		//eof my agile pixel

		// Get options
		$the_options = MyAgilePrivacy::get_settings();
		$do_not_send_in_clear_settings_key = MyAgilePrivacy::get_do_not_send_in_clear_settings_key();

		//purge do_not_send fields

		foreach( $the_options as $k => $v )
		{
			if( in_array( $k, $do_not_send_in_clear_settings_key ) )
			{
				$cleaned_options[$k] = ( isset( $v ) ) ? '(set)' : '(not set)';
			}
			else
			{
				$cleaned_options[$k] = $v;
			}
		}

		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $cleaned_options );

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

				$other_data['with_multilang'] = true;
				$other_data['is_wpml_enabled'] = true;

				$multilang_default_lang = $sitepress->get_default_language();
				$wpml_current_lang = ICL_LANGUAGE_CODE;
				$language_list = icl_get_languages();
				$language_list_codes = array();

				foreach( $language_list as $k => $v )
				{
					if( isset( $v['code'] ) )
					{
						$language_list_codes[] = $v['code'];
					}
					elseif( isset( $v['language_code'] ) )
					{
						$language_list_codes[] = $v['language_code'];
					}
				}
			}

			if( defined( 'POLYLANG_FILE' ) &&
				function_exists( 'pll_default_language' ) &&
				function_exists( 'pll_languages_list' ) )
			{
				$is_polylang_enabled = true;
				$with_multilang = true;

				$other_data['with_multilang'] = true;
				$other_data['is_polylang_enabled'] = true;

				$multilang_default_lang = pll_default_language();
				$language_list_codes = pll_languages_list();

				//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $default );
				//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $language_list_codes );
			}
		}

		if( $is_wpml_enabled )
		{
			$sitepress->switch_lang( $multilang_default_lang );
		}

		$post_status_to_search = array( 'draft', 'publish', '__blocked', '__always_allowed' );

		$cc_args = array(
			'posts_per_page'   	=> 	-1,
			'post_type'        	=>	MAP_POST_TYPE_COOKIES,
			'post_status' 		=> 	$post_status_to_search,
		);

		if( $is_polylang_enabled )
		{
			$cc_args['lang'] = $multilang_default_lang;
		}

		$cc_query = new WP_Query( $cc_args );

		//wpml language reset
		if( $is_wpml_enabled )
		{
			$sitepress->switch_lang( $wpml_current_lang );
		}

		//load cookies for any language (not stricly necessary due to synced options)
		if ( $cc_query->have_posts() )
		{
			foreach ( $cc_query->get_posts() as $p )
			{
				$main_post_id = $p->ID;

				$post_type = get_post_type( $main_post_id );

				//double check for strange theme / plugins
				if( $post_type == MAP_POST_TYPE_COOKIES )
				{
					$this_post_status = get_post_status( $main_post_id );

					if( in_array( $this_post_status, $post_status_to_search ) )
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
								$item_for_hash_calculation = array(
									isset( $all_meta['_map_remote_id'] ) ? $all_meta['_map_remote_id'][0] : null,
									isset( $all_meta['_map_api_key'] ) ? $all_meta['_map_api_key'][0] : null,
									isset( $all_meta['_map_is_necessary'] ) ? $all_meta['_map_is_necessary'][0] : null,
									isset( $all_meta['_map_is_readonly'] ) ? $all_meta['_map_is_readonly'][0] : null,
								);

								//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $item_for_hash_calculation );

								$recalculated_hash = md5( json_encode( $item_for_hash_calculation ) );

								//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $recalculated_hash );

								$final_item = array(
									'post_id'				=>	$main_post_id,
									'post_status'			=>	$this_post_status,
									'all_meta' 				=>	$all_meta_summarized,
									'recalculated_hash'		=>	$recalculated_hash,
								);

								$cookies[ $key ] = $final_item;
							}
						}
						else
						{
							if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $all_meta );
						}
					}
				}
			}

			MyAgilePrivacy::internal_query_reset();
		}


		if( $is_wpml_enabled )
		{
			$sitepress->switch_lang( $multilang_default_lang );
		}

		$post_status_to_search = array( 'publish' );

		$cc_args = array(
			'posts_per_page'   	=> 	-1,
			'post_type'        	=>	MAP_POST_TYPE_POLICY,
			'post_status' 		=> 	$post_status_to_search,
		);

		if( $is_polylang_enabled )
		{
			$cc_args['lang'] = $multilang_default_lang;
		}

		$cc_query = new WP_Query( $cc_args );

		//wpml language reset
		if( $is_wpml_enabled )
		{
			$sitepress->switch_lang( $wpml_current_lang );
		}

		//load policies for any language (not stricly necessary due to synced options)
		if ( $cc_query->have_posts() )
		{
			foreach ( $cc_query->get_posts() as $p )
			{
				$main_post_id = $p->ID;

				$post_type = get_post_type( $main_post_id );

				//double check for strange theme / plugins
				if( $post_type == MAP_POST_TYPE_POLICY )
				{
					$this_post_status = get_post_status( $main_post_id );

					if( in_array( $this_post_status, $post_status_to_search ) )
					{
						//retrieve all meta, also customizations (punto c ecc)
						$all_meta = get_post_meta( $main_post_id );

						$key = $all_meta['_map_remote_id'][0];

						if( $key )
						{
							//calc hash and check

							$item_for_hash_calculation = array(
								$all_meta['_map_remote_id'][0],
								$p->post_title,
							);

							$recalculated_hash = md5( json_encode( $item_for_hash_calculation ) );

							$final_item = array(
								'post_id'				=>	$main_post_id,
								'all_meta' 				=>	MyAgilePrivacy::summarizeMeta( $all_meta ),
								'recalculated_hash'		=>	$recalculated_hash,
							);

							$policies[ $key ] = $final_item;
						}
					}
				}
			}

			MyAgilePrivacy::internal_query_reset();
		}

		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $cookies );
		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $policies );

		$output = array(
			'cleaned_options' 	=>	$cleaned_options,
			'cookies'			=>	$cookies,
			'policies'			=>	$policies,
			'other_data'		=>	$other_data,
			'manifest_assoc'	=>	$manifest_assoc,
		);

		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $output );

		return $output;
	}


	/**
	 * Update Options Callback
	 *
	 * @since    1.0.12
	 */
	public function update_admin_settings_form_callback()
	{
		if ( !current_user_can( 'manage_options' ) ) 
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'update_admin_settings_form_callback -> missing user permission' );
			return false; 
		}

		global $locale;
		global $sitepress;
		$is_wpml_enabled = false;
		$is_polylang_enabled = false;
		$with_multilang = false;

		$with_missing_fields = false;

		if( MAP_MULTILANG_SUPPORT )
		{
			if( function_exists( 'icl_object_id' ) && $sitepress )
			{
				$is_wpml_enabled = true;
				$with_multilang = true;

				$multilang_default_lang = $sitepress->get_default_language();
				$wpml_current_lang = ICL_LANGUAGE_CODE;
				$language_list = icl_get_languages();
				$language_list_codes = array();

				foreach( $language_list as $k => $v )
				{
					if( isset( $v['code'] ) )
					{
						$language_list_codes[] = $v['code'];
					}
					elseif( isset( $v['language_code'] ) )
					{
						$language_list_codes[] = $v['language_code'];
					}
				}
			}

			if( defined( 'POLYLANG_FILE' ) &&
				function_exists( 'pll_default_language' ) &&
				function_exists( 'pll_languages_list' ) )
			{
				$is_polylang_enabled = true;
				$with_multilang = true;

				$multilang_default_lang = pll_default_language();
				$language_list_codes = pll_languages_list();

				//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $default );
				//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $language_list_codes );
			}
		}

		$do_clear_file_cache = false;
		$do_revalidation = false;

		$license_user_status = null;
		$license_valid = null;
		$grace_period = false;
		$customer_email = null;
		$summary_text = null;

		// Get options
		$the_options = MyAgilePrivacy::get_settings();
		$the_options_save = $the_options;

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $_POST );

		// Check nonce:
		check_admin_referer( 'myagileprivacy-update-' . MAP_PLUGIN_SETTINGS_FIELD );

		// check form submit
		if( isset( $_POST['action'] ) && $_POST['action'] == 'update_admin_settings_form' )
		{
			// Check nonce:
			check_admin_referer( 'myagileprivacy-update-' . MAP_PLUGIN_SETTINGS_FIELD );

			foreach( $the_options as $key => $value )
			{
				if( isset($_POST[$key . '_field'] ) )
				{
					//store sanitised values only
					$the_options[ $key ] = MyAgilePrivacy::sanitise_settings( $key, $_POST[$key . '_field'] );
				}
			};

			if( $the_options_save['is_on'] == false &&
				$the_options['is_on'] == true )
			{
				$do_revalidation = true;
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
				$the_options['scanner_log'] = null;
				update_option( MAP_PLUGIN_JS_DETECTED_FIELDS, null );
			}

			if( isset( $_POST['reset_consent'] ) )
			{
				$timestamp = time();

				$the_options['cookie_reset_timestamp'] = $timestamp;
			}

			if( isset( $_POST['reset_cookie_settings'] ) )
			{
				$post_status_to_search = array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', '__expired', '__blocked', '__always_allowed' );

				//check if exists post type
				$cc_args = array(
					'posts_per_page'   	=> 	-1,
					'post_type'        	=>	MAP_POST_TYPE_COOKIES,
					'post_status' 		=> 	$post_status_to_search,
				);

				if( $is_wpml_enabled )
				{
					$cc_args['suppress_filters'] = true;
				}

				if( $is_polylang_enabled )
				{
					$cc_args['lang'] = '';
				}

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

							if( in_array( $this_post_status, $post_status_to_search ) )
							{
								if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $main_post_id );

								wp_delete_post( $main_post_id, true );
							}
						}
					}

					MyAgilePrivacy::internal_query_reset();
				}

				$post_status_to_search = array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', '__expired', '__blocked', '__always_allowed' );

				//check if exists post type
				$cc_args = array(
					'posts_per_page'   	=> 	-1,
					'post_type'        	=>	MAP_POST_TYPE_POLICY,
					'post_status' 		=> 	$post_status_to_search,
				);

				if( $is_wpml_enabled )
				{
					$cc_args['suppress_filters'] = true;
				}

				if( $is_polylang_enabled )
				{
					$cc_args['lang'] = '';
				}

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

							if( in_array( $this_post_status, $post_status_to_search ) )
							{
								if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $main_post_id );

								wp_delete_post( $main_post_id, true );
							}
						}
					}

					MyAgilePrivacy::internal_query_reset();
				}

				//refresh cookies (bypass_cache)
				$this->sync_cookies_and_fixed_texts( true );

				update_option( MAP_PLUGIN_SYNC_IN_PROGRESS, 0 );
				update_option( MAP_PLUGIN_VALIDATION_TIMESTAMP, null );
				update_option( MAP_PLUGIN_DO_SYNC_NOW, 1 );
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
					'default_locale',
					'website_name',
					'identity_name',
					'identity_address',
					'identity_email',
					'pa',
					'wl',
				);

				foreach( $default_settings as $key => $value )
				{
					if( !in_array( $key, $do_not_reset_array ) )
					{
						//lets'keep the license code and other do not reset settings
						$the_options[ $key ] = MyAgilePrivacy::sanitise_settings( $key, $value );
					}
				}

				update_option( MAP_MANIFEST_ASSOC, null );
			}

			$rr = false;
			$pa = 0;
			$is_dm = false;

			$missing_key = false;

			if( !$the_options['license_valid'] ||
				!$the_options_save['pa'] ||
				$the_options_save['license_code'] != $_POST['license_code_field'] )
			{
				$missing_key = true;
			}

			$now = time();
			$the_timestamp = MyAgilePrivacy::nullCoalesce( get_option( MAP_PLUGIN_VALIDATION_TIMESTAMP ), null );

			if( ( $do_revalidation ||
					$the_timestamp == null ||
					$now - $the_timestamp > 86400 ||
					$missing_key ) &&
				isset( $_POST['license_code_field'] )
			)
			{
				$do_clear_file_cache = true;

				//validation part
				$urlparts = parse_url(home_url());
				$domain = $urlparts['host'];

				$data_to_send = array(
					'action'			=>	'validation',
					'software_key'		=>	MAP_SOFTWARE_KEY,
					'hash'				=>	sanitize_text_field( $_POST['license_code_field'] ),
					'domain'			=>	$domain,
					'version'			=>	MAP_PLUGIN_VERSION,
					'options_summary'	=>	$this->get_options_summary(),
					'server_data'		=>	MyAgilePrivacy::getServerFootPrint(),
					'bypass_cache'		=>	( $do_revalidation || $missing_key ) ? 1 : 0,
				);

				//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $data_to_send );

				$action_result = MyAgilePrivacy::call_api( $data_to_send );

				//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $action_result );

				if( !$action_result ||
					( $action_result && isset( $action_result['internal_error_message'] ) )
				)
				{
					$rr = false;
				}
				else
				{
					if( $action_result['success'] )
					{
						$rr = true;

						$license_valid = true;
						$grace_period = false;

						if( $action_result['paid_license'] == 0 )
						{
							$license_user_status = 'Demo license';
							$is_dm = true;

							if( isset( $action_result['error_msg'] ) )
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
							elseif( isset( $action_result['error_msg'] ) )
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
						$license_user_status = $action_result['error_msg'];
					}
				}

				update_option( MAP_PLUGIN_VALIDATION_TIMESTAMP, $now );

				if(
					!$license_valid ||
					( $the_options_save['license_valid'] != $license_valid ) ||
					( $the_options_save['is_dm'] )
				)
				{
					if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'trigger cookie update via db and hooks' );

					//trigger cookie update	via db and hooks
					update_option( MAP_PLUGIN_SYNC_IN_PROGRESS, 0 );
					update_option( MAP_PLUGIN_DO_SYNC_LAST_EXECUTION, null );
					update_option( MAP_PLUGIN_DO_SYNC_NOW, 1 );
				}
			}

			if( $do_clear_file_cache )
			{
				MyAgilePrivacy::clear_cache();
				update_option( MAP_PLUGIN_DO_SYNC_NOW, 1 );
			}

			if( $rr )
			{
				$customer_email = $action_result['customer_email'];
				$summary_text = $action_result['summary_text'];

				$the_options['license_user_status'] = $license_user_status;
				$the_options['is_dm'] = $is_dm;
				$the_options['license_valid'] = $license_valid;
				$the_options['grace_period'] = $grace_period;
				$the_options['customer_email'] = $customer_email;
				$the_options['summary_text'] = $summary_text;
				$the_options['wl'] = ( isset( $action_result['wl'] ) ) ? $action_result['wl'] : 0;
				$the_options['parse_config'] = ( isset( $action_result['parse_config'] ) ) ? $action_result['parse_config'] : null;
				$the_options['last_legit_sync'] = strtotime( "now" );
				$the_options['pa'] = $pa;
				$rconfig = ( isset( $action_result['rconfig'] ) ) ? $action_result['rconfig'] : null;
				$compliance_report = ( isset( $action_result['compliance_report'] ) ) ? $action_result['compliance_report'] : null;

				update_option( MAP_PLUGIN_RCONFIG, $rconfig );
				update_option( MAP_PLUGIN_COMPLIANCE_REPORT, $compliance_report );
			}
			else
			{
				$license_user_status = $the_options['license_user_status'];
				$is_dm = $the_options['is_dm'];
				$license_valid = $the_options['license_valid'];
				$grace_period = $the_options['grace_period'];
				$customer_email = $the_options['customer_email'];
				$summary_text = $the_options['summary_text'];

				$rconfig = MyAgilePrivacy::get_rconfig();
			}

			$lc_hide_local = ( isset( $rconfig ) && isset( $rconfig['lc_hide_local'] ) && $rconfig['lc_hide_local'] == 1 ) ? 1 : 0;
			$lc_owner_description = ( isset( $rconfig ) && isset( $rconfig['lc_owner_description'] ) ) ? $rconfig['lc_owner_description'] : null;
			$lc_owner_email = ( isset( $rconfig ) && isset( $rconfig['lc_owner_email'] ) ) ? $rconfig['lc_owner_email'] : null;
			$lc_owner_website = ( isset( $rconfig ) && isset( $rconfig['lc_owner_website'] ) ) ? $rconfig['lc_owner_website'] : null;

			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $the_options );

			update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_options );

			if( isset( $_POST['force_sync'] ) )
			{
				//reset sync in progress status var for forced sync
				update_option( MAP_PLUGIN_SYNC_IN_PROGRESS, 0 );
				update_option( MAP_PLUGIN_DO_SYNC_LAST_EXECUTION, null );
				update_option( MAP_PLUGIN_DO_SYNC_NOW, 1 );

				//lang propagation
				$this->triggerInternalCookiesRefresh();

				//refresh cookies (bypass_cache)
				$sync_result = $this->sync_cookies_and_fixed_texts( true );

				//bof adjust last_sync data

				$now = time();
				$this_sync_datetime_human = strtotime( "now" );

				if( function_exists( 'wp_date') )
				{
					$wp_date_format = get_option( 'date_format', null );

					if( $wp_date_format )
					{
						$wp_date_format .= ' H:i:s';

						$this_sync_datetime_human = wp_date( $wp_date_format, $now );
					}
				}

				$the_options['last_sync'] = $this_sync_datetime_human;

				update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_options );
				$the_options = MyAgilePrivacy::get_settings();

				//eof adjust last_sync data
			}

			$cookie_shield_raw_status = null;
			$cookie_shied_value = null;

			if( isset( $the_options ) && $the_options['scan_mode'] )
			{
				$cookie_shield_raw_status = $the_options['scan_mode'];

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
				'lc_hide_local'				=>	$lc_hide_local,
				'lc_owner_description'		=>	$lc_owner_description,
				'lc_owner_email'			=>	$lc_owner_email,
				'lc_owner_website'			=>	$lc_owner_website,
				'with_missing_fields'		=>	$with_missing_fields,
				'cookie_shield_raw_status'	=>	$cookie_shield_raw_status,
				'cookie_shied_value' 		=>	$cookie_shied_value,
				'internal_error_message'	=>	( $action_result && isset( $action_result['internal_error_message'] ) ) ? $action_result['internal_error_message'] : null,
			);

			wp_send_json( $answer );

			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $answer );
		}
	}

	/**
	 * Function for admin display order fix
	 *
	 * @since    1.0.12
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
	 * @since    1.0.12
	 */
	public function map_fix_view_links( $views )
	{
		$do_load = false;

		if( isset( $_GET['post_type'] ) )
		{
			if( $_GET['post_type']  == MAP_POST_TYPE_POLICY )
			{
				$do_load = true;

				echo '<div id="my_agile_privacy_backend" class="cookieWrapperView postbox map_infobox">'.__( 'Welcome to the policy list! ', 'myagileprivacy' ).'<br>'.__( 'Here you will find a list of all the available policies. You can modify specific options by clicking on the policy.', 'myagileprivacy' ).'</div>';
			}

			elseif( $_GET['post_type']  == MAP_POST_TYPE_COOKIES )
			{
				$do_load = true;

				unset( $views['all'] );
				unset( $views['mine'] );
				unset( $views['__expired'] );

				echo '<div id="my_agile_privacy_backend" class="cookieWrapperView postbox map_infobox">'.__( 'Welcome to your Cookie list!', 'myagileprivacy' ).'<br>'.__( 'Available cookies to use are under Draft. Just edit them accordingly to your neeedings, and publish in order to make them active. ', 'myagileprivacy' ).'<br><br>'.

					__( 'Here is the mapping of the possible states of the Cookies:', 'myagileprivacy').'<br>'.
					__( '<b>Published</b>: the Cookie configuration is active according to the specified settings.', 'myagileprivacy').'<br>'.
					__( '<b>Draft</b>: represents a Cookie or a software that you are not currently using, and is available as a "library".', 'myagileprivacy').'<br>'.
					__( '<b>Blocked without notification</b>: represents a Cookie that you want to block without it appearing in the list of Cookies for which you are requesting consent.', 'myagileprivacy').'<br>'.
					__( '<b>Allowed without notification</b>: represents a Cookie that you do not wish to block, and you want to prevent it from appearing in the list of cookies. This setting is intended for advanced users and should be used with caution.', 'myagileprivacy').'<br>'.

				'</div>';
			}

			if( $do_load )
			{
				wp_enqueue_style( 'wp-color-picker' );
				
				wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) ."css/my-agile-privacy-admin.css", array(),$this->version, 'all' );
				wp_enqueue_style( $this->plugin_name.'-bootstrap', plugin_dir_url( __FILE__ ) ."css/bootstrap.min.css", array(),$this->version, 'all' );
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
			$label_blocked = __( 'Blocked without notification', 'myagileprivacy' );
			$label_allowed = __( 'Allowed without notification', 'myagileprivacy' );

			$the_options = '<option value="__blocked">'.$label_blocked.'</option>' .
						'<option value="__always_allowed">'.$label_allowed.'</option>';

			$final_output_script = '<script type="text/javascript">' . PHP_EOL;

			$final_output_script .= '
									jQuery( "select[name=\"_status\"]" ).append( \''.$the_options.'\' );
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

			$label_blocked = __( 'Blocked without notification', 'myagileprivacy' );
			$label_allowed = __( 'Allowed without notification', 'myagileprivacy' );

			if( $post->post_status == '__blocked' )
			{
				$complete_blocked = ' selected="selected"';
			}

			if( $post->post_status == '__always_allowed' )
			{
				$complete_allowed = ' selected="selected"';
			}

			$the_options = '<option value="__blocked" '.$complete_blocked.'>'.$label_blocked.'</option>' .
						'<option value="__always_allowed" '.$complete_allowed.'>'.$label_allowed.'</option>';


			$final_output_script = '<script type="text/javascript">' . PHP_EOL;

			$final_output_script .= '
									jQuery("select#post_status").append( \''.$the_options.'\' );
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
	 * @since    1.0.12
	 */
	public function add_admin_pages()
	{
		global $submenu;

		$rconfig = MyAgilePrivacy::get_rconfig();

		remove_menu_page( 'edit.php?post_type='.MAP_POST_TYPE_POLICY );

		add_submenu_page(
			'edit.php?post_type='.MAP_POST_TYPE_COOKIES,
			__( 'Privacy Settings', 'myagileprivacy' ),
			__( 'Privacy Settings', 'myagileprivacy' ),
			'manage_options',
			MAP_POST_TYPE_COOKIES.'_settings',
			array( $this, 'admin_page_html' )
		);

		add_submenu_page(
			'edit.php?post_type='.MAP_POST_TYPE_COOKIES,
			__( 'Policies List', 'myagileprivacy' ),
			__( 'Policies List', 'myagileprivacy' ),
			'manage_options',
			'edit.php?post_type='.MAP_POST_TYPE_POLICY
		);

		add_submenu_page(
			'edit.php?post_type='.MAP_POST_TYPE_COOKIES,
			__( 'Backup & Restore', 'myagileprivacy' ),			 	//page_title
			__( 'Backup & Restore', 'myagileprivacy' ), 				//menu_title
			'manage_options', 										//capability
			MAP_POST_TYPE_COOKIES.'_backup_restore', 				//menu_slug
			array( $this, 'backup_restore_view' ) 					//function		 										//position
		);

		add_submenu_page(
			'edit.php?post_type='.MAP_POST_TYPE_COOKIES,
			__( 'Help Desk', 'myagileprivacy' ),			 			//page_title
			__( 'Help Desk', 'myagileprivacy' ), 					//menu_title
			'manage_options', 										//capability
			MAP_POST_TYPE_COOKIES.'_helpdesk', 						//menu_slug
			array( $this, 'helpdesk_view' ) 						//function		 										//position
		);

		if( isset( $rconfig['display_compliance_report'] ) && $rconfig['display_compliance_report'] == 1 )
		{
			add_submenu_page(
				'edit.php?post_type='.MAP_POST_TYPE_COOKIES,
				__( 'Compliance Report', 'myagileprivacy' ),			 	//page_title
				__( 'Compliance Report', 'myagileprivacy' ), 				//menu_title
				'manage_options', 										//capability
				MAP_POST_TYPE_COOKIES.'_compliance_report', 				//menu_slug
				array( $this, 'compliance_report_view' ) 					//function		 								//position
			);
		}

		//reorder settings menu
		if( isset( $submenu ) && !empty( $submenu ) && is_array( $submenu ) )
		{
			$out = array();
			$settings_menu = array();
			if( isset( $submenu['edit.php?post_type='.MAP_POST_TYPE_COOKIES] ) && is_array( $submenu['edit.php?post_type='.MAP_POST_TYPE_COOKIES ] ))
			{
				foreach( $submenu['edit.php?post_type='.MAP_POST_TYPE_COOKIES] as $k => $v )
				{
					if( $v[2] != 'post-new.php?post_type='.MAP_POST_TYPE_COOKIES )
					{
						//slug check
						if( $v[2] == MAP_POST_TYPE_COOKIES.'_settings' )
						{
							$settings_menu = $v;
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
				array_unshift( $out, $settings_menu );

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
		if ( ! current_user_can( 'manage_options' ) )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'backup_restore_view -> missing user permission' );
			return false;
		}

		// Get options
		$the_options = MyAgilePrivacy::get_settings();
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

			$map_stats = MyAgilePrivacy::nullCoalesce( get_option( MAP_PLUGIN_STATS ), null );
		}


		$css_compatibility_fix = false;

		if( isset( $GLOBALS['wp_version'] ) && version_compare( $GLOBALS['wp_version'], '4.2', '<' ) )
		{
			$css_compatibility_fix = true;
		}

		require_once plugin_dir_path( __FILE__ ).'views/backup_restore_html.php';
	}

	/**
	* Compliance report view
	*/
	public function compliance_report_view()
	{
		// check user capabilities
		if ( ! current_user_can( 'manage_options' ) )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'compliance_report_view -> missing user permission' );
			return false;
		}

		// Get options
		$the_options = MyAgilePrivacy::get_settings();
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

		require_once plugin_dir_path( __FILE__ ).'views/compliance_report_html.php';
	}

	/**
	* Help Desk report view
	*/
	public function helpdesk_view()
	{
		// check user capabilities
		if ( ! current_user_can( 'manage_options' ) )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'helpdesk_view -> missing user permission' );
			return false;
		}

		// Get options
		$the_options = MyAgilePrivacy::get_settings();
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

	/**
	* Admin page html callback
	 * @since    1.0.12
	 * @access   public
	*/
	public function admin_page_html()
	{
		// check user capabilities
		if ( ! current_user_can( 'manage_options' ) )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'admin_page_html -> missing user permission' );
			return false;
		}

		// Get options
		$the_options = MyAgilePrivacy::get_settings();

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

		foreach( $the_options as $k => &$v )
		{
			if( in_array( $k, $param_to_clean ) )
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

			$map_stats = MyAgilePrivacy::nullCoalesce( get_option( MAP_PLUGIN_STATS ), null );
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

		require_once plugin_dir_path( __FILE__ ).'views/admin_page_html.php';
	}

	/**
	* Function for outputting action link ( wp plugins area)
	 * @since    1.0.12
	 * @access   public
	*/
	public function plugin_action_links( $links )
	{
		global $locale;

		$links[] = '<a href="'. get_admin_url( null, 'edit.php?post_type='.MAP_POST_TYPE_COOKIES.'&page=my-agile-privacy-c_settings' ) .'">'.__( 'Settings','myagileprivacy' ).'</a>';
		$links[] = '<a href="https://www.myagileprivacy.com/" target="_blank">'.__( 'Support','myagileprivacy' ).'</a>';

		if( $locale == 'it_IT' )
		{
			$links[] = '<a href="https://www.myagileprivacy.com/changelog/" target="_blank">'.__( 'Changelog', 'myagileprivacy' ).'</a>';
		}
		return $links;
	}


	/**
	* Admin Init
	* Set locale
	* Add custom meta box for handling custom field edit
	 * @since    1.0.12
	 * @access   public
	*/
	public function admin_init_and_add_meta_box()
	{
		$this->set_locale();

		//add metabox (cookies)
		add_meta_box( "_map_remote_id",__( 'Cookie ID','myagileprivacy' ), array( $this, "metabox_map_remote_id" ), MAP_POST_TYPE_COOKIES, "side", "default" );
		add_meta_box( "_map_name",__( 'Cookie Name', 'myagileprivacy' ), array( $this, "metabox_map_name" ), MAP_POST_TYPE_COOKIES, "side", "default" );
		add_meta_box( "_map_is_free",__( 'Free Available', 'myagileprivacy' ), array( $this, "metabox_is_free" ), MAP_POST_TYPE_COOKIES, "side", "default" );
		add_meta_box( "_map_is_necessary",__( 'Is necessary?', 'myagileprivacy' ), array( $this, "metabox_map_is_necessary" ), MAP_POST_TYPE_COOKIES, "side", "default" );
		add_meta_box( "_map_page_reload_on_user_consent",__( 'Requires page reload on user acceptance?', 'myagileprivacy' ), array( $this, "metabox_map_page_reload_on_user_consent" ), MAP_POST_TYPE_COOKIES, "side", "default" );
		add_meta_box( "_map_allow_sync",__( 'Allow updates?', 'myagileprivacy' ), array( $this, "metabox_map_allow_sync" ), MAP_POST_TYPE_COOKIES, "side", "default" );
		add_meta_box( "_map_script_installation",__( 'Script Installation', 'myagileprivacy' ), array( $this, "metabox_script_installation" ), MAP_POST_TYPE_COOKIES, "advanced", "default" );
		add_meta_box( "_map_marketing",__( 'Marketing Policy', 'myagileprivacy' ), array( $this, "metabox_marketing" ), MAP_POST_TYPE_POLICY, "advanced", "default" );


		//add metabox (policies)
		add_meta_box( "_map_allow_sync",__( 'Allow updates?','myagileprivacy' ), array( $this, "metabox_map_allow_sync" ), MAP_POST_TYPE_POLICY, "side", "default" );

		//hide metabox
		remove_meta_box( '_map_remote_id', MAP_POST_TYPE_COOKIES, 'side' );
		remove_meta_box( '_map_name', MAP_POST_TYPE_COOKIES, 'side' );
		remove_meta_box( '_map_is_free', MAP_POST_TYPE_COOKIES, 'side' );

		//db version handling
		$db_version = MyAgilePrivacy::nullCoalesce( get_option( MAP_PLUGIN_DB_VERSION ), null );

		if( !$db_version )
		{
			update_option( MAP_PLUGIN_DB_VERSION, MAP_PLUGIN_DB_VERSION_NUMBER );
		}
	}

	/**
	* metabox render function
	* renders remote_id
	* @since    1.0.12
	* @access   public
	*/
	public function metabox_map_remote_id()
	{
		global $post;
		$custom = get_post_custom( $post->ID );
		$_map_remote_id = ( isset ( $custom["_map_remote_id"][0] ) ) ? $custom["_map_remote_id"][0] : '';
		?>
		<label>Cookie ID:</label>
		<input name="_map_remote_id" value="<?php echo esc_attr( $_map_remote_id ); ?>" style="width:95%;" />
		<?php
	}

	/**
	 * metabox render function
	 * render name
	 * @since    1.0.12
	 * @access   public
	*/
	public function metabox_map_name()
	{
		global $post;
		$custom = get_post_custom( $post->ID );
		$_map_name = ( isset ( $custom["_map_name"][0] ) ) ? $custom["_map_name"][0] : '';
		?>
		<label>Cookie Name:</label>
		<input name="_map_name" value="<?php echo esc_attr( $_map_name ); ?>" style="width:95%;" />
		<?php
	}

	/**
	 * metabox render function
	 * renders is_free
	 * @since    1.0.12
	 * @access   public
	*/
	public function metabox_is_free()
	{
		global $post;
		$custom = get_post_custom( $post->ID );
		$_map_is_free = ( isset ( $custom["_map_is_free"][0] ) ) ? $custom["_map_is_free"][0] : '';
		?>
		<label>Is Free:</label>
		<input name="_map_is_free" value="<?php echo esc_attr( $_map_is_free ); ?>" style="width:95%;" />
		<?php
	}

	public function metabox_map_page_reload_on_user_consent()
	{
		global $post;

		$custom = get_post_custom( $post->ID );

		$selected = ( isset ( $custom["_map_page_reload_on_user_consent"][0] ) ) ? $custom["_map_page_reload_on_user_consent"][0] : '';

		?>
		<label class="mt-2" for="_map_page_reload_on_user_consent"><?php _e( "Requires page reload on user acceptance?", 'myagileprivacy' )?></label>
		<br>

		<select class="mt-2 mb-2" name='_map_page_reload_on_user_consent' id='_map_page_reload_on_user_consent'>
			<?php $post_types = array(
				'1'	=>	__( 'Yes', 'myagileprivacy' ),
				'0'	=> 	__( 'No', 'myagileprivacy' ),
			);
			foreach( $post_types as $key => $post_type ): ?>
			<option value="<?php echo esc_attr($key); ?>" <?php if( $selected == esc_attr($key) ):?>selected<?php endif;?>><?php echo esc_html($post_type); ?></option>
			<?php endforeach; ?>
		</select>

		<p><?php _e( "If this setting is enabled, the first-time activation by the user will trigger a page reload.", 'myagileprivacy' );?><br>
			<?php _e( "This setting does not apply if the cookie is set as 'necessary'.", 'myagileprivacy' );?>
		</p>

		<?php
	}


	/**
	 * metabox render function
	 * renders allow_sync
	 * @since    1.0.12
	 * @access   public
	*/
	public function metabox_map_allow_sync()
	{
		global $post;
		$custom = get_post_custom( $post->ID );

		$map_remote_id = ( isset ( $custom["_map_remote_id"][0] ) ) ? $custom["_map_remote_id"][0] : '';

		if( $map_remote_id ):

		$selected = ( isset ( $custom["_map_allow_sync"][0] ) ) ? $custom["_map_allow_sync"][0] : '';

		?>
		<label class="mt-2" for="_map_allow_sync"><?php _e( "Allow automatic updates ?", 'myagileprivacy' )?></label>
		<br>

		<select class="mt-2 mb-2" name='_map_allow_sync' id='_map_allow_sync'>
			<?php $post_types = array(
				'1'	=>	__( 'Yes', 'myagileprivacy' ),
				'0'	=> 	__( 'No', 'myagileprivacy' ),
			);
			foreach( $post_types as $key => $post_type ): ?>
			<option value="<?php echo esc_attr($key); ?>" <?php if( $selected == esc_attr($key) ):?>selected<?php endif;?>><?php echo esc_html($post_type); ?></option>
			<?php endforeach; ?>
		</select>


		<p><?php _e( "If enabled, this will auto update this element text. Disable it if you would to change the text and keep it unchanged.", 'myagileprivacy' );?></p>
		<?php
		else:
		?>
		<p><?php _e( "This setting is unavailable on custom added cookie", 'myagileprivacy' );?>.</p>
		<?php
		endif;
	}


	/**
	 * metabox render function
	 * renders is_necessary
	 * @since    1.0.12
	 * @access   public
	*/
	public function metabox_map_is_necessary()
	{
		global $post;
		$custom = get_post_custom( $post->ID );

		$selected = ( isset ( $custom["_map_is_necessary"][0] ) ) ? $custom["_map_is_necessary"][0] : '';

		?>
		<label class="mt-2" for="_map_is_necessary"><?php _e( "Is necessary?", 'myagileprivacy' ) ?></label>
		<br>
		<select class="mt-2 mb-2" name='_map_is_necessary' id='_map_is_necessary'>
			<?php $post_types = array( 'necessary', 'not-necessary' );
			foreach( $post_types as $post_type ): ?>
			<option value="<?php echo esc_attr( $post_type ); ?>" <?php if( $selected == esc_attr( $post_type ) ):?>selected<?php endif;?>><?php echo esc_html( $post_type ); ?></option>
			<?php endforeach; ?>
		</select>

		<p>
			<?php _e( 'Setting the cookie as "necessary" will disable the preventive blocking, making the tool always active regardless of user consent.', 'myagileprivacy' );?><br>
			<?php _e( 'Setting the cookie as "not necessary" will activate the cookie only upon user consent.', 'myagileprivacy' );?><br>
			<?php

				if( isset( $custom["_map_suggested_is_necessary"] ) &&
					isset( $custom["_map_suggested_is_necessary"][0] )
				)
				{
					echo __( 'Suggested value for this Cookie', 'myagileprivacy' ).': '.$custom["_map_suggested_is_necessary"][0].'.';
				}
			?>
		</p>

		<?php
	}

	/**
	 * metabox render function
	 * renders marketing options
	 * @since    1.0.12
	 * @access   public
	*/
	public function metabox_marketing()
	{
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/my-agile-privacy-admin.js', array( 'jquery' , 'wp-color-picker' ), $this->version, false );

		wp_localize_script( $this->plugin_name, 'map_ajax',
			array(
				'ajax_url' 						=> admin_url( 'admin-ajax.php' ),
				'security' 						=> wp_create_nonce( 'check_license_status' ),
		) );

		global $post;
		$custom = get_post_custom( $post->ID );

		$_map_remote_id = $custom["_map_remote_id"][0];
		$_map_option1_ack = ( isset ( $custom["_map_option1_ack"][0] ) ) ? $custom["_map_option1_ack"][0] : 0;
		$_map_option1_on = ( isset ( $custom["_map_option1_on"][0] ) ) ? $custom["_map_option1_on"][0] : 0;

		if( $_map_remote_id == 'personal_data_policy' ):

		?>

		<div id="my_agile_privacy_backend" class="MAP_policyWrapperEdit">

			<span class="forbiddenWarning badge rounded-pill bg-danger position-absolute metabox-pill d-none">
				<small><?php _e( 'Premium Feature','myagileprivacy' ); ?></small>
			</span>

			<div class="checkForbiddenArea">

				<label><?php _e( "Compliance with Marketing Consent", 'myagileprivacy' ); ?></label>

	            <div class="row mt-4">

	                <div class="col-sm-12">

	                    <div class="styled_radio d-inline-flex">
	                        <div class="round d-flex me-12">

	                            <input type="hidden" name="_map_option1_ack" value="0" id="_map_option1_ack_no">

	                            <input name="_map_option1_ack" type="checkbox" value="1" id="_map_option1_ack" class="hideShowInput" data-hide-show-ref="map_option1_on_wrapper" <?php checked( $_map_option1_ack, true); ?>>

	                            <label for="_map_option1_ack" class="me-3 label-checkbox"></label>

	                            <label for="_map_option1_ack">
	                                <?php _e( "I declare that I have read, understood and adapted the forms on my site according to the specifications",'myagileprivacy' );?> <a target="blank" href="https://www.myagileprivacy.com/come-essere-a-norma-con-i-moduli-di-contatto-per-le-attivita-di-marketing/"><?php _e( "described here",'myagileprivacy' );?></a>
	                            </label>
	                        </div>
	                    </div> <!-- ./ styled_radio -->

	                </div>
	            </div> <!-- row -->


	            <div class="row mt-2 map_option1_on_wrapper displayNone" data-value="1">

	                <div class="col-sm-12">

	                    <div class="styled_radio d-inline-flex">
	                        <div class="round d-flex me-12">

	                            <input type="hidden" name="_map_option1_on" value="0" id="_map_option1_on_no">

	                            <input name="_map_option1_on" type="checkbox" value="1" id="_map_option1_on" <?php checked( $_map_option1_on, true); ?>>

	                            <label for="_map_option1_on" class="me-3 label-checkbox"></label>

	                            <label for="_map_option1_on">
	                                <?php _e( "I request consent for functional marketing activities including the sending of newsletters, paper mail, sms, mms and messaging apps.", 'myagileprivacy' ); ?>
	                            </label>
	                        </div>
	                    </div> <!-- ./ styled_radio -->

	                </div>
	            </div> <!-- row -->

			</div>
		</div>

		<?php
		else:
			echo "<style>#_map_marketing.postbox{display:none;}</style>";
		endif;
	}


	/**
	 * metabox render function
	 * renders installation method
	 * @since    1.0.12
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
		$custom = get_post_custom( $post->ID );
		$locale = get_user_locale();

		$_map_is_readonly = ( isset ( $custom["_map_is_readonly"][0] ) ) ? $custom["_map_is_readonly"][0] : false;

		$_map_auto_detected = ( isset ( $custom["_map_auto_detected"][0] ) ) ? $custom["_map_auto_detected"][0] : 0;
		$_map_auto_detected_override = ( isset ( $custom["_map_auto_detected_override"][0] ) ) ? intval( $custom["_map_auto_detected_override"][0] ) : 0;

		$_map_installation_type = ( isset ( $custom["_map_installation_type"][0] ) ) ? $custom["_map_installation_type"][0] : '';
		$_map_code = ( isset ( $custom["_map_code"][0] ) ) ? $custom["_map_code"][0] : '';
		$_map_noscript = ( isset ( $custom["_map_noscript"][0] ) ) ? $custom["_map_noscript"][0] : '';
		$_map_raw_code = ( isset ( $custom["_map_raw_code"][0] ) ) ? $custom["_map_raw_code"][0] : '';
		$_map_help_url = ( isset ( $custom["_map_help_url"][0] ) ) ? $custom["_map_help_url"][0] : '';
		$_map_api_key = ( isset ( $custom["_map_api_key"][0] ) ) ? $custom["_map_api_key"][0] : '';
		$_map_js_dependencies = ( isset ( $custom["_map_js_dependencies"][0] ) ) ? $custom["_map_js_dependencies"][0] : null;
		?>

		<?php

			if( $_map_is_readonly ):

		?>
			<div id="my_agile_privacy_backend" class="MAP_cookieWrapperEdit">

				<?php _e( "This Cookie is reserved by the system and it is not possible to associate other scripts with its execution.", 'myagileprivacy' ) ?>
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
					<?php _e( "Warning: this cookie has been detected automatically and usually you shouldn't need to install other code.", 'myagileprivacy' ) ?>
				</div>

	            <div class="row mt-3 mb-2">

	                <div class="col-sm-12">

	                    <div class="styled_radio d-inline-flex">
	                        <div class="round d-flex me-12">

	                            <input type="hidden" name="_map_auto_detected_override" value="0" id="_map_auto_detected_override_no">

	                            <input name="_map_auto_detected_override" type="checkbox" value="1" id="_map_auto_detected_override" class="hideShowInput" data-hide-show-ref="map_auto_detected_override_wrapper" <?php checked( $_map_auto_detected_override, true); ?>>

	                            <label for="_map_auto_detected_override" class="me-3 label-checkbox"></label>

	                            <label for="_map_auto_detected_override">
	                                <?php _e( "Add custom code anyway.", 'myagileprivacy' ) ?>
	                            </label>
	                        </div>
	                    </div> <!-- ./ styled_radio -->

	                </div>
	            </div> <!-- row -->


			<?php
				endif;
			?>

				<div class="row mb-3 MAP_cookieWrapperEdit map_auto_detected_override_wrapper <?php echo $addedClass;?>" data-value="1">

					<div class="col-sm-12">

						<label class="col-form-label"><b><?php _e( "Script Installation type", 'myagileprivacy' ) ?></b></label>

						<div class="col-sm-7">

							<select id="_map_installation_type" name="_map_installation_type" class="form-control hideShowInput" style="" data-hide-show-ref="map_installation_type_wrapper">
								<?php

								$valid_options = array(

									'js_noscript'					=>	array(  'label' => __( 'JavaScript-Noscript code', 'myagileprivacy' ),
																			'selected' => false ),
									'raw_code'						=>	array(  'label' => __( 'Raw code', 'myagileprivacy' ),
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
										<option value="<?php echo esc_attr($key)?>" selected><?php echo esc_attr($data['label'])?></option>
										<?php
									}
									else
									{
										?>
										<option value="<?php echo esc_attr($key)?>"><?php echo esc_attr($data['label'])?></option>
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
								 _e( "The API KEY for this cookie is", 'myagileprivacy' );
								 echo ' <b>'.esc_html( $_map_api_key ).'</b>.<br>';
								 _e( "You will need this only for advanced installation", 'myagileprivacy' );
								 ?>.
							</p>

						<?php
							endif;
						?>

						<?php
							if( $locale && $locale == 'it_IT' && $_map_help_url ):
						?>

							<p>
								<a target="blank" href="<?php echo esc_attr($_map_help_url);?>"><?php _e( "Need help ? See this cookie installation guide", 'myagileprivacy' ) ?></a>
							</p>

						<?php
							endif;
						?>

					</div>


					<div class="map_installation_type_wrapper displayNone" data-value="js_noscript">

						<label><b><?php _e( "Custom JavaScript Code", 'myagileprivacy' ) ?></b></label>

						<div class="position-relative code-block-container code-block-container-small mb-4">
							<textarea name="_map_code" class="code-editor code-editor-small" spellcheck="false"><?php echo esc_attr( $_map_code ); ?></textarea>

							<pre class="line-numbers code-viewer code-viewer-small"><code class="language-js"><?php echo esc_attr( $_map_code ); ?></code></pre>
						</div>

						<label><b><?php _e( "Noscript Code", 'myagileprivacy' ) ?></b></label>
						
						<div class="position-relative code-block-container code-block-container-small">
							<textarea name="_map_noscript" class="code-editor code-editor-small" spellcheck="false"><?php echo esc_attr( $_map_noscript ); ?></textarea>

							<pre class="code-viewer code-viewer-small"><code class="line-numbers language-html"><?php echo esc_attr( $_map_noscript ); ?></code></pre>
						</div>

					</div>

					<div class="map_installation_type_wrapper displayNone" data-value="raw_code">

						<label><b><?php _e( "Raw Html Code", 'myagileprivacy' ) ?></b></label>
						
						<div class="position-relative code-block-container code-block-container-small">
							<textarea name="_map_raw_code" class="code-editor code-editor-small" spellcheck="false"><?php echo esc_attr( $_map_raw_code ); ?></textarea>
							
							<pre class="line-numbers code-viewer code-viewer-small"><code class="language-html"><?php echo esc_attr( $_map_raw_code ); ?></code></pre>
						</div>
					</div>
				</div>
				
				<?php if( $_map_api_key ): ?>

					<div class="map_js_dependencies_wrapper mt-4">
						<label><b><?php _e("Custom JavaScript dependencies", 'myagileprivacy') ?></b></label>

						<span class="forbiddenWarning badge rounded-pill bg-danger metabox-pill d-none">
							<small><?php _e( 'Premium Feature','myagileprivacy' ); ?></small>
						</span>

						<div class="checkForbiddenArea">

							<p>
								<i><?php _e("Warning: this feature is intended for advanced users and developers.", 'myagileprivacy') ?></i><br>
								<?php _e("If you are experiencing JavaScript errors with scripts dependent on this cookie, you can list them here by writing part of the source file path or part of the inline JavaScript code.", 'myagileprivacy') ?><br>
								<?php _e("Thanks to this functionality, these scripts will be unblocked only after the user has accepted this cookie.", 'myagileprivacy') ?>
							</p>

							<div id="map-ips-list-container" class="row dynamic_fields_container">
								<?php
								$_map_js_dependencies_array = json_decode( $_map_js_dependencies, true );

								if( $_map_js_dependencies_array ):
									foreach( $_map_js_dependencies_array as $the_item ):
										if( $the_item['value'] != '' ):
								?>
										<div class="map-dependency-entry map-dynamic-entry mb-2 col-sm-12">
											<div class="input-group">

												<select class="form-select2" name="js_dependencies_type[]">
													<option value="js_patterns_src" <?php selected( $the_item['type'], 'js_patterns_src' ); ?>>JavaScript script src path</option>

													<option value="js_patterns_code" <?php selected( $the_item['type'], 'js_patterns_code' ); ?>>JavaScript inline code</option>
												</select>
												<input class="form-control is-valid" name="js_dependencies_field[]" value="<?php echo esc_attr($the_item['value']); ?>" type="text" autocomplete="off"/>
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
										<select class="form-select" name="js_dependencies_type[]">
											<option value="js_patterns_src">JavaScript script src path</option>
											<option value="js_patterns_code">JavaScript inline code</option>
										</select>
										<input class="form-control" name="js_dependencies_field[]" type="text" autocomplete="off"/>
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
	 * @since    1.0.12
	 * @access   public
	*/
	public function save_custom_metabox_cookies( $main_post_id )
	{
		global $post;

		if ( !current_user_can( 'manage_options' ) ) 
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'save_custom_metabox_cookies -> missing user permission' );
			return false; 
		}

		if ( isset ( $_POST["_map_remote_id"] ) )
		{
			update_post_meta( $main_post_id, "_map_remote_id", sanitize_text_field( $_POST["_map_remote_id"] ) );
		}

		if ( isset ( $_POST["_map_name"] ) )
		{
			update_post_meta( $main_post_id, "_map_name", sanitize_text_field( $_POST["_map_name"] ) );
		}

		if ( isset ( $_POST["_map_installation_type"] ) )
		{
			update_post_meta( $main_post_id, "_map_installation_type", sanitize_text_field( $_POST["_map_installation_type"] ) );
		}

		if ( isset ( $_POST["_map_code"] ) )
		{
			update_post_meta( $main_post_id, "_map_code", sanitize_textarea_field( $_POST["_map_code"] ) );
		}

		if ( isset ( $_POST["_map_raw_code"] ) )
		{
			update_post_meta( $main_post_id, "_map_raw_code", esc_html( $_POST["_map_raw_code"] ) );
		}

		if ( isset ( $_POST["_map_noscript"] ) )
		{
			update_post_meta( $main_post_id, "_map_noscript", esc_html( $_POST["_map_noscript"] ) );
		}

		if ( isset ( $_POST["_map_is_free"] ) )
		{
			update_post_meta( $main_post_id, "_map_is_free", sanitize_text_field( $_POST["_map_is_free"] ) );
		}

		if ( isset ( $_POST["_map_is_necessary"] ) )
		{
			update_post_meta( $main_post_id, "_map_is_necessary", sanitize_text_field( $_POST["_map_is_necessary"] ) );
		}

		if ( isset ( $_POST["_map_allow_sync"] ) )
		{
			update_post_meta( $main_post_id, "_map_allow_sync", sanitize_text_field( $_POST["_map_allow_sync"] ) );
		}

		if ( isset ( $_POST["_map_page_reload_on_user_consent"] ) )
		{
			update_post_meta( $main_post_id, "_map_page_reload_on_user_consent", intval( $_POST["_map_page_reload_on_user_consent"] ) );
		}

		if ( isset ( $_POST["_map_auto_detected_override"] ) )
		{
			update_post_meta( $main_post_id, "_map_auto_detected_override", intval( $_POST["_map_auto_detected_override"] ) );
		}


		if (isset($_POST["js_dependencies_field"]) && isset($_POST["js_dependencies_type"])) {
			$js_dependencies = array();
		
			foreach ($_POST['js_dependencies_field'] as $index => $value) {
				$type = sanitize_text_field($_POST['js_dependencies_type'][$index]);
				$value = str_replace('"', '\"', stripslashes(sanitize_text_field($value)));
				if (!empty($value)) {
					$js_dependencies[] = array(
						'type' => $type,
						'value' => $value
					);
				}
			}
		
			update_post_meta($main_post_id, '_map_js_dependencies', json_encode($js_dependencies));
		}


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

				$multilang_default_lang = $sitepress->get_default_language();
				$wpml_current_lang = ICL_LANGUAGE_CODE;
				$language_list = icl_get_languages();
				$language_list_codes = array();

				foreach( $language_list as $k => $v )
				{
					if( isset( $v['code'] ) )
					{
						$language_list_codes[] = $v['code'];
					}
					elseif( isset( $v['language_code'] ) )
					{
						$language_list_codes[] = $v['language_code'];
					}
				}
			}

			if( defined( 'POLYLANG_FILE' ) &&
				function_exists( 'pll_default_language' ) &&
				function_exists( 'pll_languages_list' ) )
			{
				$is_polylang_enabled = true;
				$with_multilang = true;

				$multilang_default_lang = pll_default_language();
				$language_list_codes = pll_languages_list();

				//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $default );
				//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $language_list_codes );
			}
		}


		if( $with_multilang )
		{
			$all_meta = get_post_meta( $main_post_id );

			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $all_meta );

			remove_action( 'save_post_'.MAP_POST_TYPE_COOKIES, array( $this, 'save_custom_metabox_cookies' ) );

			if( $is_wpml_enabled )
			{
				$t_post_id = apply_filters( 'wpml_element_trid', null, $main_post_id );

				//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $t_post_id );

				$translations = apply_filters( 'wpml_get_element_translations', null, $t_post_id, 'post_'.MAP_POST_TYPE_COOKIES );

				//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $translations );
			}

			if( $is_polylang_enabled )
			{
				$translations = pll_get_post_translations( $main_post_id );
			}

			if( $translations )
			{
				$to_update = array();

				foreach( $translations as $t_key => $t_value )
				{
					if( $t_key != $multilang_default_lang )
					{
						if( $is_wpml_enabled )
						{
							$to_update[ $t_key ] = $t_value->element_id;
						}

						if( $is_polylang_enabled )
						{
							$to_update[ $t_key ] = $t_value;
						}
					}
				}

				//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $to_update );

				if( $to_update )
				{
					$the_post_status = get_post_status( $main_post_id );

					foreach( $to_update as $key_to_update => $to_update_post_id )
					{
						if( $to_update_post_id )
						{
							//update
							$my_post = array(
								'ID'           	=> 	$to_update_post_id,
								'post_status'	=>	$the_post_status,
							);

							wp_update_post( $my_post );

							if( isset( $all_meta["_map_remote_id"][0] ) )
							{
								update_post_meta( $to_update_post_id, "_map_remote_id", sanitize_text_field( $all_meta["_map_remote_id"][0] ) );
							}

							if( isset( $all_meta["_map_name"][0] ) )
							{
								update_post_meta( $to_update_post_id, "_map_name", sanitize_text_field( $all_meta["_map_name"][0] ) );
							}

							if( isset( $all_meta["_map_installation_type"][0] ) )
							{
								update_post_meta( $to_update_post_id, "_map_installation_type", sanitize_text_field( $all_meta["_map_installation_type"][0] ) );
							}

							if( isset( $all_meta["_map_code"][0] ) )
							{
								update_post_meta( $to_update_post_id, "_map_code", sanitize_textarea_field( $all_meta["_map_code"][0] ) );
							}

							if( isset( $all_meta["_map_raw_code"][0] ) )
							{
								update_post_meta( $to_update_post_id, "_map_raw_code", esc_html( $all_meta["_map_raw_code"][0] ) );
							}

							if( isset( $all_meta["_map_noscript"][0] ) )
							{
								update_post_meta( $to_update_post_id, "_map_noscript", esc_html( $all_meta["_map_noscript"][0] ) );
							}

							if( isset( $all_meta["_map_is_free"][0] ) )
							{
								update_post_meta( $to_update_post_id, "_map_is_free", sanitize_text_field( $all_meta["_map_is_free"][0] ) );
							}

							if( isset( $all_meta["_map_is_necessary"][0] ) )
							{
								update_post_meta( $to_update_post_id, "_map_is_necessary", sanitize_text_field( $all_meta["_map_is_necessary"][0] ) );
							}

							if( isset( $all_meta["_map_allow_sync"][0] ) )
							{
								update_post_meta( $to_update_post_id, "_map_allow_sync", sanitize_text_field( $all_meta["_map_allow_sync"][0] ) );
							}

							if( isset( $all_meta["_map_auto_detected"][0] ) )
							{
								update_post_meta( $to_update_post_id, "_map_auto_detected", $all_meta["_map_auto_detected"][0] );
							}

							if( isset( $all_meta["_map_auto_detected_override"][0] ) )
							{
								update_post_meta( $to_update_post_id, "_map_auto_detected_override", intval( $all_meta["_map_auto_detected_override"][0] ) );
							}
						}
					}
				}
			}

			add_action( 'save_post_'.MAP_POST_TYPE_COOKIES, array( $this, 'save_custom_metabox_cookies' ) );
		}
	}

	/**
	 * function for custom metabox save (policies)
	 * @since    1.0.13
	 * @access   public
	*/
	public function save_custom_metabox_policies( $main_post_id )
	{
		$rconfig = MyAgilePrivacy::get_rconfig();

		global $post;

		$custom = get_post_custom( $post->ID );

		if ( isset ( $_POST["_map_remote_id"] ) )
		{
			update_post_meta( $main_post_id, "_map_remote_id", sanitize_text_field( $_POST["_map_remote_id"] ) );
		}

		if ( isset ( $_POST["_map_name"] ) )
		{
			update_post_meta( $main_post_id, "_map_name", sanitize_text_field( $_POST["_map_name"] ) );
		}

		if ( isset ( $_POST["_map_is_free"] ) )
		{
			update_post_meta( $main_post_id, "_map_is_free", sanitize_text_field( $_POST["_map_is_free"] ) );
		}

		if ( isset ( $_POST["_map_allow_sync"] ) )
		{
			update_post_meta( $main_post_id, "_map_allow_sync", sanitize_text_field( $_POST["_map_allow_sync"] ) );
		}

		$_map_option1_ack = null;
		$_map_option1_on = null;

		if( $custom["_map_remote_id"][0] == 'personal_data_policy' )
		{
			if ( isset ( $_POST["_map_option1_ack"] ) )
			{
				$_map_option1_ack = intval( $_POST["_map_option1_ack"] );
			}
			else
			{
				$_map_option1_ack = 0;
			}

			update_post_meta( $main_post_id, "_map_option1_ack", $_map_option1_ack );

			if ( isset ( $_POST["_map_option1_on"] ) )
			{
				$_map_option1_on = intval( $_POST["_map_option1_on"] );
			}
			else
			{
				$_map_option1_on = 0;
			}

			update_post_meta( $main_post_id, "_map_option1_on", $_map_option1_on );

			if( !( isset( $rconfig ) &&
					isset( $rconfig['disable_cronjob'] ) &&
					$rconfig['disable_cronjob'] == 1 ) )
			{
				//refresh cookies
				wp_schedule_single_event( time() + 5, 'my_agile_privacy_do_cron_sync_once_day_hook' );
			}
		}

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

				$multilang_default_lang = $sitepress->get_default_language();
				$wpml_current_lang = ICL_LANGUAGE_CODE;
				$language_list = icl_get_languages();
				$language_list_codes = array();

				foreach( $language_list as $k => $v )
				{
					if( isset( $v['code'] ) )
					{
						$language_list_codes[] = $v['code'];
					}
					elseif( isset( $v['language_code'] ) )
					{
						$language_list_codes[] = $v['language_code'];
					}
				}
			}

			if( defined( 'POLYLANG_FILE' ) &&
				function_exists( 'pll_default_language' ) &&
				function_exists( 'pll_languages_list' ) )
			{
				$is_polylang_enabled = true;
				$with_multilang = true;

				$multilang_default_lang = pll_default_language();
				$language_list_codes = pll_languages_list();

				//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $default );
				//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $language_list_codes );
			}
		}

		if( $with_multilang )
		{
			$all_meta = get_post_meta( $main_post_id );

			remove_action( 'save_post_'.MAP_POST_TYPE_POLICY, array( $this, 'save_custom_metabox_policies' ) );

			if( $is_wpml_enabled )
			{

				$t_post_id = apply_filters( 'wpml_element_trid', null, $main_post_id );
				$translations = apply_filters( 'wpml_get_element_translations', null, $t_post_id, 'post_'.MAP_POST_TYPE_POLICY );
			}

			if( $is_polylang_enabled )
			{
				$translations = pll_get_post_translations( $main_post_id );
			}

			if( $translations )
			{
				$to_update = array();

				foreach( $translations as $t_key => $t_value )
				{
					if( $t_key != $multilang_default_lang )
					{
						if( $is_wpml_enabled )
						{
							$to_update[ $t_key ] = $t_value->element_id;
						}

						if( $is_polylang_enabled )
						{
							$to_update[ $t_key ] = $t_value;
						}
					}
				}

				//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $to_update );

				if( $to_update )
				{
					$the_post_status = get_post_status( $main_post_id );

					foreach( $to_update as $key_to_update => $to_update_post_id )
					{
						if( $to_update_post_id )
						{
							//update
							$my_post = array(
								'ID'           	=> 	$to_update_post_id,
								'post_status'	=>	$the_post_status,
							);

							wp_update_post( $my_post );

							if( isset( $all_meta["_map_remote_id"][0] ) )
							{
								update_post_meta( $to_update_post_id, "_map_remote_id", sanitize_text_field( $all_meta["_map_remote_id"][0] ) );
							}

							if( isset( $all_meta["_map_name"][0] ) )
							{
								update_post_meta( $to_update_post_id, "_map_name", sanitize_text_field( $all_meta["_map_name"][0] ) );
							}

							if( isset( $all_meta["_map_is_free"][0] ) )
							{
								update_post_meta( $to_update_post_id, "_map_is_free", sanitize_text_field( $all_meta["_map_is_free"][0] ) );
							}

							if( isset( $all_meta["_map_allow_sync"][0] ) )
							{
								update_post_meta( $to_update_post_id, "_map_allow_sync", sanitize_text_field( $all_meta["_map_allow_sync"][0] ) );
							}

							if( $custom["_map_remote_id"][0] == 'personal_data_policy' )
							{
								update_post_meta( $to_update_post_id, "_map_option1_ack", $_map_option1_ack );
								update_post_meta( $to_update_post_id, "_map_option1_on", $_map_option1_on );
							}
						}
					}
				}
			}
			add_action( 'save_post_'.MAP_POST_TYPE_POLICY, array( $this, 'save_custom_metabox_policies' ) );
		}
	}


	/**
	 * function for defining column names ( custom post type )
	 * @since    1.0.12
	 * @access   public
	*/
	public function manage_cookies_edit_columns( $columns )
	{
		$columns = array(
			"cb" 							=> "<input type=\"checkbox\" />",
			"title"							=> __( 'Cookie Name', 'myagileprivacy' ),
			"auto_detected"					=> __( 'Auto Detected', 'myagileprivacy' ),
			"help"							=> __( 'Help link', 'myagileprivacy' ),
			"necessary"						=> __( 'Preventive blocking status', 'myagileprivacy' ),
			"suggested"						=> __( 'Suggested setting', 'myagileprivacy' ),
			"allow_sync"					=> __( 'Allow automatic updates ?', 'myagileprivacy' ),
			"installation_type"				=> __( 'Additional code installation type', 'myagileprivacy' ),
			"page_reload_on_user_consent"	=> __( 'Requires page reload on user acceptance', 'myagileprivacy' ),
			"map_sync_datetime_human"		=> __( 'Last update', 'myagileprivacy' ),
		);
		return $columns;
	}

	/**
	 * function for getting column data ( custom post type )
	 * @since    1.0.12
	 * @access   public
	*/
	public function manage_cookies_posts_custom_columns( $column, $post_id=0 )
	{
		global $post;
		$custom = get_post_custom();
		$locale = get_user_locale();

		switch ( $column )
		{
			case "map_sync_datetime_human":

				if ( isset ( $custom["_map_sync_datetime_human"][0] ) ) {
					echo $custom["_map_sync_datetime_human"][0];
				}
				else
				{
					echo '-';
				}

				break;

			case "installation_type":

				if ( isset ( $custom["_map_installation_type"][0] ) ) {

					if(
						(
							isset ( $custom["_map_auto_detected"] ) &&
							$custom["_map_auto_detected"][0] == '1' &&
							isset ( $custom["_map_auto_detected_override"] ) &&
							$custom["_map_auto_detected_override"][0] == '1'
						) ||

						( isset ( $custom["_map_auto_detected"] ) &&
							$custom["_map_auto_detected"][0] == '0' )
					)
					{
						switch ( $custom["_map_installation_type"][0] )
						{
							case 'js_noscript':
								echo __( 'Javascript-Noscript code', 'myagileprivacy' );
								break;
							case 'raw_code':
								echo __( 'Raw code', 'myagileprivacy' );
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

				if ( isset ( $custom["_map_is_necessary"][0] ) ) {

					switch( $custom["_map_is_necessary"][0] )
					{
						case 'necessary':
							echo '-';

							break;
						case 'not-necessary':
							if ( isset ( $custom["_map_page_reload_on_user_consent"] ) &&
								$custom["_map_page_reload_on_user_consent"][0] == 1 )
							{
								echo __( 'Yes', 'myagileprivacy' );
							}
							else
							{
								echo __( 'No', 'myagileprivacy' );
							}

							break;
					}
				}

				break;


			case "auto_detected":

				if ( isset ( $custom["_map_auto_detected"] ) &&
					$custom["_map_auto_detected"][0] == 1 )
				{
					echo "<span style='color:#3fe63f;' title='".__( 'Yes', 'myagileprivacy' )."'>&#11044;</span>";
				}
				else
				{
					echo "<span style='color:#d0d0d0;' title='".__( 'No', 'myagileprivacy' )."'>&#11044;</span>";
				}
				break;

			case "help":

				if( $locale && $locale == 'it_IT' )
				{
					if ( isset ( $custom["_map_help_url"][0] ) ) {
						echo "<a href='".esc_attr( $custom["_map_help_url"][0] )."' target='blank'>&#x25B6;</a>";
					}
				}
				break;

			case "code":

				if ( isset ( $custom["_map_code"][0] ) ) {
					echo esc_html( $custom["_map_code"][0] );
				}
				elseif( isset ( $custom["_map_raw_code"][0] ) ) {
					echo esc_html( $custom["_map_raw_code"][0] );
				}
				break;

			case "necessary":

				if ( isset ( $custom["_map_is_necessary"][0] ) ) {

					switch( $custom["_map_is_necessary"][0] )
					{
						case 'necessary':
							echo 'necessary:<br>'.__( 'Preventive blocking is not active', 'myagileprivacy' );
							break;

						case 'not-necessary':
							echo 'not-necessary:<br>'.__( 'Preventive blocking <strong>is active</strong>.', 'myagileprivacy' );
							break;
					}
				}
				break;

			case "suggested":

				if ( isset ( $custom["_map_suggested_is_necessary"][0] ) ) {

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
				break;

			case "allow_sync":

				if ( isset ( $custom["_map_allow_sync"][0] ) ) {
					if( $custom["_map_allow_sync"][0] )
					{
						echo __( 'Yes', 'myagileprivacy' );
					}
					else
					{
						echo __( 'No', 'myagileprivacy' );
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
	 * @since    1.0.12
	 * @access   public
	*/
	public function manage_policies_edit_columns( $columns )
	{
		$columns = array(
			"cb" 						=> "<input type=\"checkbox\" />",
			"title"						=> "Policy",
			"allow_sync"				=> __( "Allow automatic updates ?", 'myagileprivacy' ),
			"map_sync_datetime_human"	=> __( 'Last update', 'myagileprivacy' ),
		);
		return $columns;
	}


	/**
	 * function for getting column data ( custom post type )
	 * @since    1.0.12
	 * @access   public
	*/
	public function manage_policies_posts_custom_columns( $column, $post_id=0 )
	{
		global $post;
		$custom = get_post_custom();

		switch ( $column )
		{
			case "map_sync_datetime_human":

				if ( isset ( $custom["_map_sync_datetime_human"][0] ) ) {
					echo $custom["_map_sync_datetime_human"][0];
				}
				else
				{
					echo '-';
				}

				break;

			case "allow_sync":

				if ( isset ( $custom["_map_allow_sync"][0] ) ) {
					if( $custom["_map_allow_sync"][0] )
					{
						echo __( 'Yes', 'myagileprivacy' );
					}
					else
					{
						echo __( 'No', 'myagileprivacy' );
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
	public static function genPolicyQuickViewContent( $the_id )
	{
		$content = get_post_field( 'post_content', $the_id );
		$map_remote_id = get_post_meta( $the_id, '_map_remote_id', true );

		if( !$map_remote_id )
		{
			$map_remote_id = 'cookie_policy';
		}

		$defaults = MyAgilePrivacy::get_default_settings();
		$settings = wp_parse_args( MyAgilePrivacy::get_settings(), $defaults );
		$rconfig = MyAgilePrivacy::get_rconfig();

		$remove_dpo_text = true;
		$remove_dpo_other_text = true;
		$remove_ccpa_text = true;
		$remove_lpd_text = true;

		if( $settings['pa'] == 1 &&
			isset( $rconfig ) && $rconfig['allow_dpo_edit'] &&
			isset( $settings['display_dpo'] ) && $settings['display_dpo'] == 1 &&
			isset( $settings['dpo_email'] ) && $settings['dpo_email'] )
		{
			$remove_dpo_text = false;

			if(
				( isset( $settings['dpo_name'] ) && $settings['dpo_name'] ) ||
				( isset( $settings['dpo_address'] ) && $settings['dpo_address'] )
			)
			{
				$remove_dpo_other_text = false;
			}
		}

		if( $settings['pa'] == 1 &&
			isset( $rconfig ) && $rconfig['allow_ccpa_text'] &&
			isset( $settings['display_ccpa'] ) && $settings['display_ccpa'] == 1 )
		{
			$remove_ccpa_text = false;
		}

		if( $settings['pa'] == 1 &&
			isset( $rconfig ) && $rconfig['allow_lpd_text'] &&
			isset( $settings['display_lpd'] ) && $settings['display_lpd'] == 1 )
		{
			$remove_lpd_text = false;
		}

		//$content = apply_filters('the_content', $content);

		if( $map_remote_id == 'personal_data_policy' )
		{
			$map_option1_ack = get_post_meta( $the_id, '_map_option1_ack', true );
			$map_option1_on = get_post_meta( $the_id, '_map_option1_on', true );

			if( $settings['pa'] == 1 )
			{
				if( $map_option1_ack == 1 && $map_option1_on == 1 )
				{
					//no action
				}
				else
				{
					$content = preg_replace( '#(<li class="map_marketing_consent">).*?(</li>)#', '' , $content );
					$content = preg_replace( '#(<p class="map_marketing_consent">).*?(</p>)#', '' , $content );
				}
			}
			else
			{
				$content = preg_replace( '#(<li class="map_marketing_consent">).*?(</li>)#', '' , $content );
				$content = preg_replace( '#(<p class="map_marketing_consent">).*?(</p>)#', '' , $content );
			}

		}

		if( $remove_dpo_text )
		{
			$content = preg_replace( '#(<p class="map_dpo_text">).*?(</p>)#', '' , $content );
		}
		else
		{
			$content = str_replace( 'MAP_DPO_MAIL', stripslashes( $settings['dpo_email'] ), $content );
		}

		if( $remove_dpo_other_text )
		{
			$content = preg_replace( '#(<p class="map_dpo_other_text">).*?(</p>)#', '' , $content );
		}
		else
		{
			if( $settings['dpo_name'] )
			{
				$content = str_replace( 'MAP_DPO_NAME', stripslashes( $settings['dpo_name'] ), $content );
			}
			else
			{
				$content = str_replace( 'MAP_DPO_NAME<br>', '', $content );
			}

			if( $settings['dpo_address'] )
			{
				$content = str_replace( 'MAP_DPO_ADDRESS', stripslashes( $settings['dpo_address'] ), $content );
			}
			else
			{
				$content = str_replace( 'MAP_DPO_ADDRESS<br>', '', $content );
			}
		}

		if( $remove_ccpa_text )
		{
			$content = preg_replace( '#(<p class="map_ccpa_text">).*?(</p>)#', '' , $content );
		}
		else
		{
			//no action
		}


		if( $remove_lpd_text )
		{
			$content = preg_replace( '#(<p class="map_lpd_text">).*?(</p>)#', '' , $content );
			$content = preg_replace( '#(<span class="map_lpd_text">).*?(</span>)#', '' , $content );
		}
		else
		{
			//no action
		}

		$website_name = get_site_url();

		if( isset( $settings['website_name'] ) && $settings['website_name'] != '' )
		{
			$website_name = stripslashes( $settings['website_name'] );

			//block_the_content_filter mode
			if(
				( isset( $rconfig ) &&
				isset( $rconfig['block_the_content_filter'] ) &&
				$rconfig['block_the_content_filter'] == 1 ) ||
				(
					$settings['scanner_compatibility_mode']
				)
			)
			{
				//no action
			}
			else
			{
				//add paragraphs to text
				$website_name = apply_filters( 'the_content', $website_name );
			}
		}

		$content = str_replace( '[website_name]', $website_name, $content );
		$content = str_replace( '[identity_name]', stripslashes( $settings['identity_name'] ), $content );
		$content = str_replace( '[identity_vat_id]', ( $settings['identity_vat_id'] ) ? __( 'Vat ID', 'myagileprivacy' ).': '.stripslashes( $settings['identity_vat_id'] ) : '', $content );
		$content = str_replace( '[identity_address]', stripslashes( $settings['identity_address'] ), $content );
		$content = str_replace( '[identity_email]', stripslashes( $settings['identity_email'] ), $content );

		return $content;
	}

	/**
	 * inline help text after editor (policies)
	 * @since    1.0.12
	 * @access   public
	*/
	public function inline_help_text_after_editor()
	{
		global $my_admin_page;
		$screen = get_current_screen();

		if ( is_admin() && ( $screen->id == MAP_POST_TYPE_POLICY ) )
		{
			function add_content_after_editor() {
				global $post;
				$the_id = $post->ID;

				$_map_name = get_post_meta( $the_id, '_map_name', true );

				//apply_filters('the_content', $post->post_content);

				//$the_quickview_content = 'ciao palle';
				$the_quickview_content = MyAgilePrivacyAdmin::genPolicyQuickViewContent( $the_id );

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

					<div class="postbox map_infobox">
						<?php
							echo esc_html__( "Please add the following shortcode to the right page:", 'myagileprivacy' ).'<br>';
							echo '<code>'.wp_kses($shortcode, MyAgilePrivacy::allowed_html_tags() ).'</code><br>';
							echo esc_html__( "Remember also associate in the Privacy Settings -> Policies tab.", 'myagileprivacy' );
						?>
					</div>


					<div class="my_agile_privacy_component_backend">
						<div class="map-policy-quickview">
							<div class="map-custom-card">
								<div class="map-custom-card-header">
									<h1><?php echo esc_html__( "Quickview for", 'myagileprivacy' ).' '.esc_html( $post->post_title ) ?> <button type="button" class="button-agile btn-md map-do-edit-this-policy"><?php _e( 'Edit Policy', 'myagileprivacy' ); ?></button></h1>
								</div>
								<div class="map-custom-card-body">
									<div class="map-custom-card-body">
										<?php echo ( $the_quickview_content ); ?>
									</div>
								</div>
							</div>
						</div>
						<div class="map-wrap-editor displayNone">
							<p class="p-3 mb-2 bg-danger text-white">
								<?php _e( 'Attention: Modifying the policy without specific expertise may result in loss of compliance.', 'myagileprivacy' ); ?><br>
								<?php _e( 'By modifying the text, you assume all risks.', 'myagileprivacy' ); ?><br>
								<?php _e( 'Remember to disable automatic updates for this policy to avoid losing the changes made.', 'myagileprivacy' ); ?><br>
							</p>
						<?php
				}
			}

			function add_text_after_editor() {
				// Text to display after the editor
				$text_after = '</div></div>';
				echo $text_after;
			}

			add_action( 'edit_form_after_title', 'add_content_after_editor' );
			add_action( 'edit_form_after_editor', 'add_text_after_editor' );
		}
	}


	/**
	 * Check license callback function
	 *
	 * @since    1.3.0
	 */
	public function check_license_status()
	{
		//check security param
		check_ajax_referer( 'check_license_status', 'security' );

		$success = false;

		// Get options
		$the_options = MyAgilePrivacy::get_settings();

		if( isset( $the_options['pa'] ) && $the_options['pa'] == 1 )
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
	 * @since    1.3.7
	 * @access   public
	*/
	public function js_get_plugin_stats()
	{
		$args = (object) array(
			'slug' 		=> MAP_PLUGIN_SLUG,
			'fields'	=> array(
							'active_installs'	=> true,
							'downloaded'		=> false,
							'rating'			=> false,
							'description'		=> false,
							'short_description' => false,
							'donate_link'		=> false,
							'tags'				=> false,
							'sections'			=> false,
							'homepage'			=> false,
							'added'				=> false,
							'last_updated'		=> false,
							'compatibility'		=> false,
							'tested'			=> false,
							'requires'			=> false,
							'downloadlink'		=> false,
					)
		);

		$request = array(
			'action' => 'plugin_information',
			'timeout' => 15,
			'request' => serialize( $args )
		);

		$url = 'http://api.wordpress.org/plugins/info/1.0/';

		$response = wp_remote_post( $url, array( 'body' => $request ) );

		if( !is_wp_error( $response ) )
		{
			$plugin_info = unserialize( $response['body'] );

			return $plugin_info;
		}
		else
		{
			$error_code = array_key_first( $response->errors );
			$error_message = $response->errors[ $error_code ][0];

			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $error_message );
		}

		return null;
	}

	//f for review banner show
	public function show_review_notice()
	{
		$show_notice = MyAgilePrivacy::should_show_notice();
		$rconfig = MyAgilePrivacy::get_rconfig();

		$review_urls = null;
		$review_url_key = null;

		//default url
		$review_url = 'https://wordpress.org/support/plugin/myagileprivacy/reviews/#new-post';

		if( isset( $rconfig ) && isset( $rconfig['review_urls'] ) && $rconfig['review_urls'] )
		{
			$review_urls = $rconfig['review_urls'];
		}

        $locale = get_user_locale();

        if( $locale && $locale == 'it_IT' )
        {
        	$review_url_key = 'it';
        }
        else
        {
        	$review_url_key = 'en';
        }

        if( $locale && $review_urls && isset( $review_urls[ $review_url_key ] ) )
        {
        	$review_url = $review_urls[ $review_url_key ];
        }
		
		if ( $show_notice )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( "showing review notice, review_url_key=$review_url_key, review_urls=".print_r( $review_urls, true ) );

			require_once plugin_dir_path( __FILE__ ).'views/feedback_notice_html.php';
		}
	}

	//f event review later
	public function review_later()
	{
		// nonce check
		check_ajax_referer('map_review_nonce', 'security');

		if ( !current_user_can( 'manage_options' ) )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'review_later -> missing user permission' );
			wp_die( 'Unauthorized', '', 403 );
		}

		update_option( MAP_REVIEW_STATUS, 'later' );
        update_option( MAP_NOTICE_LAST_SHOW_TIME, time() );

        if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'setting review status to later' );

        wp_die();
	}

	//f event review done
	public function review_done()
	{
		// nonce check
		check_ajax_referer( 'map_review_nonce', 'security' );

		if ( !current_user_can( 'manage_options' ) )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'review_done -> missing user permission' );
			wp_die( 'Unauthorized', '', 403 );
		}

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'setting review status to done' );

		update_option( MAP_REVIEW_STATUS, 'done' );
        update_option( MAP_NOTICE_LAST_SHOW_TIME, time() );
        wp_die();
	}
}
