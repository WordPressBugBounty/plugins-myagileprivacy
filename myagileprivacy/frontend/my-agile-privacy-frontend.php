<?php

if( !defined( 'MAP_PLUGIN_NAME' ) )
{
	exit('Not allowed.');
}

// Polyfill for hash_equals() - available natively from PHP 5.6.0.
if( !function_exists( 'hash_equals' ) )
{
	function hash_equals( $known_string, $user_string )
	{
		if( !is_string( $known_string ) || !is_string( $user_string ) )
		{
			return false;
		}

		$known_length = strlen( $known_string );

		if( $known_length !== strlen( $user_string ) )
		{
			return false;
		}

		$result = 0;

		for ( $i = 0; $i < $known_length; $i++ )
		{
			$result |= ord( $known_string[ $i ] ) ^ ord( $user_string[ $i ] );
		}

		return $result === 0;
	}
}

/**
 * The frontend-specific functionality of the plugin.
 *
 * @link       https://www.myagileprivacy.com/
 *
 * @package    MyAgilePrivacy
 * @subpackage MyAgilePrivacy/frontend
 */

/**
 * The frontend-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the frontend-specific stylesheet and JavaScript.
 *
 * @package    MyAgilePrivacy
 * @subpackage MyAgilePrivacy/frontend
 * @author     https://www.myagileprivacy.com/
 */

final class MyAgilePrivacyFrontend {

	/**
	 * The ID of this plugin.
	 *
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;
	public $plugin_obj;

	/**
	 * Scan vars
	 *
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $scan_mode;
	private $scan_config;
	private $scan_output;
	private $scan_log;
	private $scan_done;
	private $saved_settings;
	private $saved_post;
	private $head_script;
	private $pending_detected_fields = null;
	private $pending_html_scan_state = null;
	private $shutdown_passed = false;
	private $scan_dom = null;
	private $scan_saved_output = null;
	private $html_head_done = false;
	private $scripts_enqueued = false;
	private $early_gcmode_shield_handle = '';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version, $plugin_obj )
	{
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->plugin_obj = $plugin_obj;

		//blocked on elementor, divi, thrive theme builder
		if( !is_admin() &&
			//elementor
			!isset( $_GET['elementor-preview'] ) &&
			//divi
			( !isset( $_GET['et_fb'] ) || !MyAgilePrivacy::looseEqualsPhp8( $_GET['et_fb'], 1 ) ) &&
			//thrive theme builder
			( !isset( $_GET['action'] ) || $_GET['action'] != 'architect' ) &&
			( !isset( $_GET['tve'] ) || $_GET['tve'] != 'true' )
		)
		{
			//[myagileprivacy_cookie_accept] shortcode
			add_shortcode( 'myagileprivacy_cookie_accept', array( $this, 'myagileprivacy_cookie_accept_button' ));

			//[myagileprivacy_cookie_reject] shortcode
			add_shortcode( 'myagileprivacy_cookie_reject', array( $this, 'myagileprivacy_cookie_reject_button' ));

			//[myagileprivacy_cookie_customize] shortcode
			add_shortcode( 'myagileprivacy_cookie_customize', array( $this, 'myagileprivacy_cookie_customize_button' ));

			//[myagileprivacy_extra_info] shortcode
			add_shortcode( 'myagileprivacy_extra_info', array( $this, 'myagileprivacy_extra_info' ));

			//[myagileprivacy_fixed_text] shortcode
			add_shortcode( 'myagileprivacy_fixed_text', array( $this, 'myagileprivacy_fixed_text' ));

			//[myagileprivacy_link] shortcode
			add_shortcode( 'myagileprivacy_link', array( $this, 'myagileprivacy_link' ));

			//[myagileprivacy_showconsent] shortcde
			add_shortcode( 'myagileprivacy_showconsent', array( $this, 'myagileprivacy_showconsent' ));

			//[myagileprivacy_blocked_content_notification] shortcode
			add_shortcode( 'myagileprivacy_blocked_content_notification', array( $this, 'myagileprivacy_blocked_content_notification' ));
		}
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 * Rewritten func to ignore messy mofile
	 * @access   private
	 */
	private function my_load_plugin_textdomain( $domain, $deprecated = false, $plugin_rel_path = false )
	{
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

		//avoid handing a missing .mo to WordPress: the core lazy loader would then warn
		//on file_get_contents (e.g. for a locale we don't ship, or a removed .mo)
		if( !is_readable( $fullpath ) )
		{
			return false;
		}

		return load_textdomain( $domain, $fullpath );
	}


	/**
	 * Function for checking if polylang is enabled
	 *
	 * @access   private
	 */
	public function check_if_polylang_enabled()
	{
		$currentAndSupportedLanguages = MyAgilePrivacy::getCurrentAndSupportedLanguages();

		return $currentAndSupportedLanguages['is_polylang_enabled'];
	}


	/**
	 * Function for checking if wpml is enabled
	 *
	 * @access   private
	 */
	public function check_if_wpml_enabled()
	{
		$currentAndSupportedLanguages = MyAgilePrivacy::getCurrentAndSupportedLanguages();

		return $currentAndSupportedLanguages['is_wpml_enabled'];
	}

	//f for setting WPML config
	public function map_wpml_config_array( $config )
	{
		$post_type_array = array(
			MAP_POST_TYPE_COOKIES,
			MAP_POST_TYPE_POLICY
		);

		foreach( $post_type_array as $to_clean_post_type )
		{
			$config['post_types'][ $to_clean_post_type ] = array(
				'translate' => 0,
			);
		}

		return $config;
	}


	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * @access   private
	 */
	private function set_locale()
	{
		$multilang_enabled = MyAgilePrivacy::check_if_multilang_enabled();

		global $locale;

		$the_settings = MyAgilePrivacy::get_settings();

		if( $multilang_enabled == false && isset( $the_settings['default_locale'] ) )
		{
			$old_locale = $locale;
			$locale = $the_settings['default_locale'];
		}

		//if( $multilang_enabled )
		if( is_textdomain_loaded( MAP_PLUGIN_SLUG ) )
		{
			unload_textdomain( MAP_PLUGIN_SLUG );
		}

		$loaded = $this->my_load_plugin_textdomain(
			MAP_PLUGIN_TEXTDOMAIN,
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/lang/'
		);

		if( $multilang_enabled == false && isset( $the_settings['default_locale'] ) )
		{
			$locale = $old_locale;
		}
	}

	/**
	 * cookie accept button shortcode
	 * @access   public
	 */
	public function myagileprivacy_cookie_accept_button( $atts )
	{
		$the_settings = MyAgilePrivacy::get_settings();

		//get translations
		$the_translations = MyAgilePrivacy::getFixedTranslations();
		$current_lang = MyAgilePrivacy::getCurrentLang4Char();

		extract( shortcode_atts(array(
			'margin' => '',
		), $atts ) );
		$margin_style = ( $margin != "" ) ? ' margin:'.esc_attr( $margin ).'; ' : '';

		$the_text = esc_html( $the_translations[ $current_lang ]['accept'] );

		$color_style = ( $the_settings['button_accept_link_color'] != "" ) ? ' color:'.esc_attr( $the_settings['button_accept_link_color'] ).' !important ' : '';
		$background_style = ( $the_settings['button_accept_button_color'] != "" ) ? ' background-color:'.esc_attr( $the_settings['button_accept_button_color'] ).' !important ' : '';

		$border_radius_style = 'border-radius:'.esc_attr( $the_settings['elements_border_radius'] ).'px !important';

		$text_size_val = (int) esc_attr( $the_settings['text_size'] );
		$text_lineheight_val = (int) esc_attr( $the_settings['text_lineheight'] );

		$text_size_style = '--map-text-size:'.$text_size_val.'px;font-size:var(--map-text-size)';
		$text_lineheight_style = '--map-text-lh:'.$text_lineheight_val.'px; line-height:var(--map-text-lh)';

		$class = ' class="map-button map-button-style map-accept-button"';

		// colore icona = colore testo
		$icon_background_style = ( $the_settings['button_accept_link_color'] != "" ) ? ' background-color:'.esc_attr( $the_settings['button_accept_link_color'] ).' !important' : '';

		$link_tag = '<a role="button" tabindex="0" data-map_action="accept" id="map-accept-button"'. $class.' style="'.esc_attr( $margin_style ).'; '. esc_attr( $color_style ).'; '.esc_attr( $background_style) .'; '.esc_attr( $border_radius_style ).'; '.esc_attr( $text_size_style ).'; '.esc_attr( $text_lineheight_style ).';">';

		if( $the_settings['show_buttons_icons'] ) $link_tag .= '<span style=" '.esc_attr( $icon_background_style ).';"></span>';
		$link_tag .= esc_html( $the_text ) . '</a>';

		return $link_tag;
	}

	/**
	 * cookie reject button shortcode
	 * @access   public
	 */
	public function myagileprivacy_cookie_reject_button( $atts )
	{
		$the_settings = MyAgilePrivacy::get_settings();

		//get translations
		$the_translations = MyAgilePrivacy::getFixedTranslations();
		$current_lang = MyAgilePrivacy::getCurrentLang4Char();

		extract( shortcode_atts(array(
			'margin' => '',
		), $atts ) );
		$margin_style = ( $margin != "" ) ? ' margin:'.esc_attr( $margin ).'; ' : '';

		$the_text = esc_html( $the_translations[ $current_lang ]['refuse'] );

		$color_style = ( $the_settings['button_reject_link_color'] != "" ) ? ' color:'.esc_attr( $the_settings['button_reject_link_color'] ).' !important' : '';
		$background_style = ( $the_settings['button_reject_button_color'] != "" ) ? ' background-color:'.esc_attr( $the_settings['button_reject_button_color'] ).' !important ' : '';

		$border_radius_style = 'border-radius:'.esc_attr( $the_settings['elements_border_radius'] ).'px !important';

		$text_size_val = (int) esc_attr( $the_settings['text_size'] );
		$text_lineheight_val = (int) esc_attr( $the_settings['text_lineheight'] );

		$text_size_style = '--map-text-size:'.$text_size_val.'px;font-size:var(--map-text-size)';
		$text_lineheight_style = '--map-text-lh:'.$text_lineheight_val.'px; line-height:var(--map-text-lh)';

		$class = ' class="map-button map-button-style map-reject-button"';

		// colore icona = colore testo
		$icon_background_style = ( $the_settings['button_reject_link_color'] != "" ) ? ' background-color:'.esc_attr( $the_settings['button_reject_link_color'] ).' !important' : '';

		$link_tag = '<a role="button" tabindex="0" data-map_action="reject" id="map-reject-button"'. $class.' style="'.esc_attr( $margin_style ).'; '. esc_attr( $color_style ).'; '.esc_attr( $background_style) .'; '.esc_attr( $border_radius_style ).'; '.esc_attr( $text_size_style ).'; '.esc_attr( $text_lineheight_style ).';">';

		if( $the_settings['show_buttons_icons'] )  $link_tag .= '<span style=" '.esc_attr( $icon_background_style ).';"></span>';
		$link_tag .= esc_html( $the_text ) . '</a>';

		return $link_tag;
	}


	/**
	 * cookie customize button shortcode
	 * @access   public
	 */
	public function myagileprivacy_cookie_customize_button( $atts )
	{
		$the_settings = MyAgilePrivacy::get_settings();

		//get translations
		$the_translations = MyAgilePrivacy::getFixedTranslations();
		$current_lang = MyAgilePrivacy::getCurrentLang4Char();

		extract( shortcode_atts(array(
			'margin' => '',
		), $atts ) );
		$margin_style = ( $margin != "" ) ? ' margin:'.esc_attr( $margin ).'; ' : '';

		$the_text = esc_html( $the_translations[ $current_lang ]['customize'] );

		$color_style = ( $the_settings['button_customize_link_color'] != "" ) ? ' color:'.esc_attr( $the_settings['button_customize_link_color'] ).' !important' : '';
		$background_style = ( $the_settings['button_customize_button_color'] != "" ) ? ' background-color:'.esc_attr( $the_settings['button_customize_button_color'] ).' !important' : '';

		$border_radius_style = 'border-radius:'.esc_attr( $the_settings['elements_border_radius'] ).'px !important';

		$text_size_val = (int) esc_attr( $the_settings['text_size'] );
		$text_lineheight_val = (int) esc_attr( $the_settings['text_lineheight'] );

		$text_size_style = '--map-text-size:'.$text_size_val.'px;font-size:var(--map-text-size)';
		$text_lineheight_style = '--map-text-lh:'.$text_lineheight_val.'px; line-height:var(--map-text-lh)';

		$class = ' class="map-button map-button-style map-customize-button"';

		// colore icona = colore testo
		$icon_background_style = ( $the_settings['button_customize_link_color'] != "" ) ? ' background-color:'.esc_attr( $the_settings['button_customize_link_color'] ).' !important ' : '';

		$link_tag = '<a role="button" tabindex="0" aria-haspopup="dialog" data-map_action="customize" id="map-customize-button"'. $class.' style="'.esc_attr( $margin_style ).'; '. esc_attr( $color_style ).'; '.esc_attr( $background_style) .'; '.esc_attr( $border_radius_style ).'; '.esc_attr( $text_size_style ).'; '.esc_attr( $text_lineheight_style ).';">';

		if( $the_settings['show_buttons_icons'] ) $link_tag .= '<span style=" '.esc_attr( $icon_background_style ).';"></span>';
		$link_tag .= esc_html( $the_text ) . '</a>';

		return $link_tag;
	}


	/**
	 * myagileprivacy_extra_info shortcode
	 * @access   public
	 */
	public function myagileprivacy_extra_info( $atts )
	{
		$the_settings = MyAgilePrivacy::get_settings();
		$MyAgilePrivacyRegulationHelper = new MyAgilePrivacyRegulationHelper();
		$site_and_policy_settings = $MyAgilePrivacyRegulationHelper->getSiteAndPolicySettings();
		$templateConfig = $MyAgilePrivacyRegulationHelper->getTemplateConfig();
		$rconfig = MyAgilePrivacy::get_rconfig();

		//get translations
		$the_translations = MyAgilePrivacy::getFixedTranslations();
		$current_lang = MyAgilePrivacy::getCurrentLang4Char();

		$is_polylang_enabled = $this->check_if_polylang_enabled();
		$is_wpml_enabled = $this->check_if_wpml_enabled();

		//bof policies link

		$cookie_policy_link = ( isset( $the_settings ) && isset( $the_settings['cookie_policy_link'] ) ) ? $the_settings['cookie_policy_link'] : null;
		$is_cookie_policy_url = ( isset( $the_settings ) && isset( $the_settings['is_cookie_policy_url'] ) ) ? $the_settings['is_cookie_policy_url'] : null;
		$cookie_policy_url = ( isset( $the_settings ) && isset( $the_settings['cookie_policy_url'] ) ) ? $the_settings['cookie_policy_url'] : null;
		$cookie_policy_page = ( isset( $the_settings ) && isset( $the_settings['cookie_policy_page'] ) ) ? $the_settings['cookie_policy_page'] : null;

		$the_cookie_policy_url = null;

		if( $is_cookie_policy_url && $cookie_policy_url )
		{
			$the_cookie_policy_url = $cookie_policy_url;
		}

		if( !$is_cookie_policy_url && $cookie_policy_page )
		{
			if( $is_wpml_enabled )
			{
				$the_cookie_policy_url = MyAgilePrivacy::get_published_page_url( icl_object_id( $cookie_policy_page, 'page', true) );
			}
			elseif( $is_polylang_enabled )
			{
				$the_cookie_policy_url = MyAgilePrivacy::get_published_page_url( pll_get_post( $cookie_policy_page ) );
			}
			else
			{
				$the_cookie_policy_url = MyAgilePrivacy::get_published_page_url( $cookie_policy_page );
			}
		}

		$personal_data_policy_link = ( isset( $the_settings ) && isset( $the_settings['personal_data_policy_link'] ) ) ? $the_settings['personal_data_policy_link'] : null;
		$is_personal_data_policy_url = ( isset( $the_settings ) && isset( $the_settings['is_personal_data_policy_url'] ) ) ? $the_settings['is_personal_data_policy_url'] : null;
		$personal_data_policy_url = ( isset( $the_settings ) && isset( $the_settings['personal_data_policy_url'] ) ) ? $the_settings['personal_data_policy_url'] : null;
		$personal_data_policy_page = ( isset( $the_settings ) && isset( $the_settings['personal_data_policy_page'] ) ) ? $the_settings['personal_data_policy_page'] : null;

		$the_personal_data_policy_url = null;

		if( $is_personal_data_policy_url && $personal_data_policy_url )
		{
			$the_personal_data_policy_url = $personal_data_policy_url;
		}

		if( !$is_personal_data_policy_url && $personal_data_policy_page )
		{
			if( $is_wpml_enabled )
			{
				$the_personal_data_policy_url = MyAgilePrivacy::get_published_page_url( icl_object_id( $personal_data_policy_page, 'page', true) );
			}
			elseif( $is_polylang_enabled )
			{
				$the_personal_data_policy_url = MyAgilePrivacy::get_published_page_url( pll_get_post( $personal_data_policy_page ) );
			}
			else
			{
				$the_personal_data_policy_url = MyAgilePrivacy::get_published_page_url( $personal_data_policy_page );
			}
		}

		//eof policies link

		extract( shortcode_atts(array(
			'margin' => '',
		), $atts ) );
		$margin_style = $margin != "" ? ' margin:'.$margin.'; ' : '';

		$iab_tcf_context = false;

		if(
			$rconfig &&
			isset( $rconfig['allow_iab'] ) &&
			$rconfig['allow_iab'] == 1 &&
			$the_settings['enable_iab_tcf'] )
		{
			$iab_tcf_context = true;
		}

		$show_lpd = false;

		if( isset( $templateConfig['map_lpd_text'] ) && $templateConfig['map_lpd_text'] == 1 )
		{
			$show_lpd = true;
		}

		$output_text = "";

		if( !function_exists( 'is_plugin_active' ) )
		{
			include_once(ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		if( is_plugin_active( 'myagilepixel/myagilepixel.php' ) )
		{
			$found_items = array();

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
						$found_items[] = esc_html( $the_translations[ $current_lang ]['ga_4_version'] );
					}

					if( defined( 'MAPX_my_agile_pixel_fbq_on' ) )
					{
						$found_items[] = esc_html( $the_translations[ $current_lang ]['facebook_remarketing'] );
					}

					if( defined( 'MAPX_my_agile_pixel_tiktok_on' ) )
					{
						$found_items[] = esc_html( $the_translations[ $current_lang ]['tiktok_pixel'] );
					}
				}
				elseif( $logic_check == 'detailed' )
				{
					if( defined( 'MAPX_my_agile_pixel_ga_on' ) &&
						defined( 'MAPX_my_agile_pixel_ga_on_anonymous' ) &&
						MAPX_my_agile_pixel_ga_on_anonymous )
					{
						$found_items[] = esc_html( $the_translations[ $current_lang ]['ga_4_version'] );
					}

					if( defined( 'MAPX_my_agile_pixel_fbq_on' ) &&
						defined( 'MAPX_my_agile_pixel_fbq_on_anonymous' ) &&
						MAPX_my_agile_pixel_fbq_on_anonymous )
					{
						$found_items[] = esc_html( $the_translations[ $current_lang ]['facebook_remarketing'] );
					}

					if( defined( 'MAPX_my_agile_pixel_tiktok_on' ) &&
						defined( 'MAPX_my_agile_pixel_tiktok_on_anonymous' ) &&
						MAPX_my_agile_pixel_tiktok_on_anonymous )
					{
						$found_items[] = esc_html( $the_translations[ $current_lang ]['tiktok_pixel'] );
					}
				}

			}

			if( count( $found_items ) > 0 )
			{
				$found_items_string = implode( ', ', $found_items );

				$output_text .= ' '.esc_html( $the_translations[ $current_lang ]['in_addition_this_site_installs'] ).' '.$found_items_string.' '.esc_html( $the_translations[ $current_lang ]['with_anonymous_data_transmission_via_proxy'] )." ".esc_html( $the_translations[ $current_lang ]['by_giving_your_consent_the_data_will_be_sent_anonymously'] );
				;
			}
		}

		if( $iab_tcf_context )
		{
			$output_text .= ' '.esc_html( $the_translations[ $current_lang ]['iab_bannertext_1'] ).' '.esc_html( $the_translations[ $current_lang ]['iab_bannertext_2_a'] ).' <a href="#" class="map-triggerGotoIABTCF">'.esc_html( $the_translations[ $current_lang ]['iab_bannertext_2_link'] ).'</a> '.esc_html( $the_translations[ $current_lang ]['iab_bannertext_2_b'] ).' '.esc_html( $the_translations[ $current_lang ]['iab_bannertext_2_c'] ).'<br><br>'.esc_html( $the_translations[ $current_lang ]['iab_bannertext_3'] ).':<br><span id="map-vendor-stack-description"></span><br>';

			$output_text .= esc_html( $the_translations[ $current_lang ]['iab_bannertext_4_a'] ).' <span id="map-vendor-number-count"></span> <a href="#" class="map-triggerGotoIABTCFVendors">'.esc_html( $the_translations[ $current_lang ]['iab_bannertext_4_b'] ).'</a>';

			if( $the_cookie_policy_url )
			{
				$output_text .= ', <a class="map-genericFirstLayerLink" target="blank" href="'.esc_url( $the_cookie_policy_url ).'">'.esc_html( $the_translations[ $current_lang ]['iab_bannertext_5'] ).'</a>';

				if( $the_personal_data_policy_url )
				{
					$output_text .= ' <a class="map-genericFirstLayerLink" target="blank" href="'.esc_url( $the_personal_data_policy_url ).'">'.esc_html( $the_translations[ $current_lang ]['iab_bannertext_6'] ).'</a>.';
				}
				else
				{
					$output_text .= '.';
				}
			}
			else
			{
				$output_text .= '.';
			}
		}

		return $output_text;
	}

	/**
	 * fixed_text shortcode
	 * @access   public
	 */
	public function myagileprivacy_fixed_text( $atts )
	{
		$the_settings = MyAgilePrivacy::get_settings();
		$MyAgilePrivacyRegulationHelper = new MyAgilePrivacyRegulationHelper();
		$site_and_policy_settings = $MyAgilePrivacyRegulationHelper->getSiteAndPolicySettings();
		$templateConfig = $MyAgilePrivacyRegulationHelper->getTemplateConfig();
		$rconfig = MyAgilePrivacy::get_rconfig();

		//get translations
		$the_translations = MyAgilePrivacy::getFixedTranslations();
		$current_lang = MyAgilePrivacy::getCurrentLang4Char();

		extract( shortcode_atts(array(
			'text' => '',
			'lang' => '',
		), $atts ) );

		$iab_tcf_context = false;

	    if(
	        $rconfig &&
	        isset( $rconfig['allow_iab'] ) &&
	        $rconfig['allow_iab'] == 1 &&
	        $the_settings['enable_iab_tcf'] )
	    {
	        $iab_tcf_context = true;
	    }

		$remove_dpo_text = true;
		$remove_dpo_other_text = true;
		$remove_ccpa_text = true;
		$remove_lpd_text = true;

		if( isset( $the_settings['pa'] ) &&
			$the_settings['pa'] == 1 &&
			is_array( $rconfig ) && isset( $rconfig['allow_dpo_edit'] ) && $rconfig['allow_dpo_edit'] &&
			isset( $templateConfig['map_dpo_text'] ) && $templateConfig['map_dpo_text'] == 1 &&
			isset( $templateConfig['map_dpo_email'] ) && $templateConfig['map_dpo_email'] )
		{
			$remove_dpo_text = false;

			if(
				( isset( $templateConfig['map_dpo_name'] ) && $templateConfig['map_dpo_name'] ) ||
				( isset( $templateConfig['map_dpo_address'] ) && $templateConfig['map_dpo_address'] )
			)
			{
				$remove_dpo_other_text = false;
			}
		}

		if( isset( $the_settings['pa'] ) &&
			$the_settings['pa'] == 1 &&
			is_array( $rconfig ) && isset( $rconfig['allow_ccpa_text'] ) && $rconfig['allow_ccpa_text'] &&
			isset( $templateConfig['map_ccpa_text'] ) && $templateConfig['map_ccpa_text'] == 1 )
		{
			$remove_ccpa_text = false;
		}

		if( isset( $the_settings['pa'] ) &&
			$the_settings['pa'] == 1 &&
			is_array( $rconfig ) && isset( $rconfig['allow_lpd_text'] ) && $rconfig['allow_lpd_text'] &&
			isset( $templateConfig['map_lpd_text'] ) && $templateConfig['map_lpd_text'] == 1 )
		{
			$remove_lpd_text = false;
		}

		$cookies_categories_data = $this->get_cookie_categories_description( 'publish' );
		$cookie_list_html = "";

		$counter = 0;

		//preventing double cookie print
		$all_remote_ids = array();

		foreach( $cookies_categories_data as $k => $v )
		{
			foreach( $v as $key => $value )
			{
				$the_remote_id = $value['remote_id'];

				if( in_array( $the_remote_id, $all_remote_ids ) )
				{
					continue;
				}
				else
				{
					$all_remote_ids[] = $the_remote_id;
					$counter++;
				}

				$cookie_list_html .= "<p><b>".esc_html( $value['post_title'] )."</b><br>";
				$cookie_list_html .= wp_kses_post( $value['post_content'] )."</p>";
			}
		}

		if( $counter == 0 && !$iab_tcf_context )
		{
			$cookie_list_html .= "<p>".$the_translations[$current_lang]['layer2_nocookie']."</p>";
		}

		if( $iab_tcf_context )
		{
			$cookie_list_html .= "<p><b>".$the_translations[$current_lang]['cookie_iab_title']."</b><br>";
			$cookie_list_html .= $the_translations[$current_lang]['cookie_iab_desc']."</p>";
		}

		if( !$text )
		{
			$text = 'cookie_policy';
		}

		$currentAndSupportedLanguages = MyAgilePrivacy::getCurrentAndSupportedLanguages();

		//check if exists
		$cc_args = array(
			'posts_per_page'   => 	-1,
			'post_type'        =>	MAP_POST_TYPE_POLICY,
			'meta_key'         => 	'_map_remote_id',
			'meta_value'       => 	$text
		);

		$cc_query = new WP_Query( $cc_args );

		if( $cc_query->have_posts() )
		{
			foreach ( $cc_query->get_posts() as $p )
			{
				$the_id = $p->ID;

				//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $the_id );

				$content = '';

				if( $currentAndSupportedLanguages['with_multilang'] )
				{
					$lang = $currentAndSupportedLanguages['current_language'];

					$post_custom = get_post_custom( $the_id );
					$_map_translations_decoded = ( isset( $post_custom["_map_translations"][0] ) ) ? wp_unslash( json_decode( $post_custom["_map_translations"][0], true ) )  : null;

					if( $_map_translations_decoded )
					{
						if( null !== $lang && isset( $_map_translations_decoded[$lang] ) )
						{
							$content = self::translation_field( $_map_translations_decoded, $lang, 'text' );
						}
						else
						{
							if(
								isset( $the_settings ) &&
								isset( $the_settings['enable_language_fallback'] ) &&
								$the_settings['enable_language_fallback'] &&
								isset( $the_settings['language_fallback_locale'] ) &&
								$the_settings['language_fallback_locale']
							)
							{
								$language_key = MyAgilePrivacy::normalizeLocaleTo2Char( $the_settings['language_fallback_locale'] );
							}
							else
							{
								$language_key = $currentAndSupportedLanguages['multilang_default_lang'];
							}


							$content = self::translation_field( $_map_translations_decoded, $language_key, 'text' );
						}
					}
				}
				else
				{
					$content = get_post_field( 'post_content', $the_id );
				}

				$content = MyAgilePrivacy::internalMAPCleanupAndCompilePolicy( $content, $the_translations, $current_lang, 'frontend', $cookie_list_html );

				return $content;
			}

			MyAgilePrivacy::internal_query_reset();
		}
	}


	/**
	 * link shortcode
	 * @access   public
	 */
	public function myagileprivacy_link( $atts )
	{
		extract( shortcode_atts(array(
			'value' => '',
			'text' => '',
		), $atts ) );

		$the_settings = MyAgilePrivacy::get_settings();

		$is_polylang_enabled = $this->check_if_polylang_enabled();

		if( $value == 'cookie_policy' )
		{
			$is_cookie_policy_url = $the_settings['is_cookie_policy_url'];
			$cookie_policy_url = $the_settings['cookie_policy_url'];
			$cookie_policy_page = $the_settings['cookie_policy_page'];

			if( !$text )
			{
				$text = "Cookie Policy";
			}

			if( $is_cookie_policy_url && $cookie_policy_url )
			{
				$the_url = $cookie_policy_url;
			}

			if( !$is_cookie_policy_url && $cookie_policy_page )
			{
				if( $is_polylang_enabled )
				{
					$the_url = MyAgilePrivacy::get_published_page_url( pll_get_post( $cookie_policy_page ) );
				}
				else
				{
					$the_url = MyAgilePrivacy::get_published_page_url( $cookie_policy_page );
				}
			}

			if( isset( $the_url ) )
			{
				$html = '<a href="'.esc_url( $the_url ).'" target="blank">'.esc_html( $text ).'</a>';

				return $html;
			}
		}

		if( $value == 'personal_data_policy' )
		{
			$is_personal_data_policy_url = $the_settings['is_personal_data_policy_url'];
			$personal_data_policy_url = $the_settings['personal_data_policy_url'];
			$personal_data_policy_page = $the_settings['personal_data_policy_page'];

			if( !$text )
			{
				$text = "Personal Data Policy";
			}

			if( $is_personal_data_policy_url && $personal_data_policy_url )
			{
				$the_url = $personal_data_policy_url;
			}

			if( !$is_personal_data_policy_url && $personal_data_policy_page )
			{
				if( $is_polylang_enabled )
				{
					$the_url = MyAgilePrivacy::get_published_page_url( pll_get_post( $personal_data_policy_page ) );
				}
				else
				{
					$the_url = MyAgilePrivacy::get_published_page_url( $personal_data_policy_page );
				}
			}

			if( isset( $the_url ) )
			{
				$html = '<a href="'.esc_url( $the_url ).'" target="blank">'.esc_html( $text ).'</a>';

				return $html;
			}
		}
		return;
	}

	/**
	 * showconsent shortcode
	 * @access   public
	 */
	public function myagileprivacy_showconsent( $atts )
	{
		$the_settings = MyAgilePrivacy::get_settings();

		//get translations
		$the_translations = MyAgilePrivacy::getFixedTranslations();
		$current_lang = MyAgilePrivacy::getCurrentLang4Char();

		$html = '<a tabindex="0" role="button" href="#" class="showConsentAgain">'.esc_html( $the_translations[ $current_lang ]['manage_consent'] ).'</a>';

		return $html;
	}


	/**
	 * link shortcode
	 * @access   public
	 */
	public function myagileprivacy_blocked_content_notification( $atts )
	{
		$the_settings = MyAgilePrivacy::get_settings();

		//get translations
		$the_translations = MyAgilePrivacy::getFixedTranslations();
		$current_lang = MyAgilePrivacy::getCurrentLang4Char();

		extract( shortcode_atts(array(
			'api_key' => '',
			'text' => '',
		), $atts ) );

		if( $api_key )
		{
			if( !$text )
			{
				switch( $api_key )
				{
					case 'google_recaptcha':
						$text = esc_html( $the_translations[ $current_lang ]['google_recaptcha_content_notification_a'] ).' <a tabindex="0" role="button" href="#" class="showConsentAgain">'.esc_html( $the_translations[ $current_lang ]['google_recaptcha_content_notification_b'] ).'</a>.';
						break;
				}
			}

			$html = '<p class="map_custom_notify map_api_key_'.esc_attr( $api_key ).'" role="button">'. $text .'</p>';

			return wp_kses( $html, MyAgilePrivacy::allowed_html_tags() );;
		}

		return;
	}


	/**
	 * inject html code in the site footer
	 * @access   public
	 */
	public function inject_html_code()
	{
	    $the_settings    = MyAgilePrivacy::get_settings();
	    $rconfig         = MyAgilePrivacy::get_rconfig();
	    $the_translations = MyAgilePrivacy::getFixedTranslations();
	    $current_lang    = MyAgilePrivacy::getCurrentLang4Char();

	    if( !is_admin() &&
	        !isset( $_GET['elementor-preview'] ) &&
	        ( !isset( $_GET['et_fb'] ) || !MyAgilePrivacy::looseEqualsPhp8( $_GET['et_fb'], 1 ) ) &&
	        !( isset( $_GET['ct_builder'] ) && $_GET['ct_builder'] == 'true' ) &&
	        !( isset( $_GET['ct_inner'] ) && $_GET['ct_inner'] == 'true' ) &&
	        ( !isset( $_GET['action'] ) || $_GET['action'] != 'architect' ) &&
	        ( !isset( $_GET['tve'] ) || $_GET['tve'] != 'true' ) &&
	        $the_settings['is_on'] == true
	    )
	    {
	        $skip = MyAgilePrivacy::check_buffer_skip_conditions( true );

	        if( $skip == 'true' || !$this->scripts_enqueued )
	        {
	            return;
	        }

			$skip_json_cache = false;

		    if( !( isset( $the_settings['pa'] ) &&
		    	$the_settings['pa'] == 1 ) )
		    {
		    	$skip_json_cache = true;
		    }

			if( $rconfig &&
				isset( $rconfig['skip_json_cache'] ) &&
				$rconfig['skip_json_cache'] == 1
			)
			{
				$skip_json_cache = true;
			}

			if( $rconfig &&
				isset( $rconfig['skip_banner_cache_renderer'] ) &&
				$rconfig['skip_banner_cache_renderer'] == 1
			)
			{
				$skip_json_cache = true;
			}

		    if( MAP_DEV_MODE )
		    {
		    	$skip_json_cache = true;
		    }

		    //rendering contexts that must not feed the shared cache
		    if( MyAgilePrivacy::check_buffer_skip_conditions() == 'true' )
		    {
		    	$skip_json_cache = true;
		    }

		    if( MyAgilePrivacy::is_split_preview() )
		    {
		    	$skip_json_cache = true;
		    }

		    $skip_json_cache_read = $skip_json_cache || current_user_can( 'manage_options' );

		    $item_type = 'def';
		    $blog_id   = is_multisite() ? get_current_blog_id() : 1;
		    $scheme    = is_ssl() ? 'https' : 'http';

	        $cache_key = 'notify_html_' . $item_type. '_' . MyAgilePrivacy::get_json_cache_variant_segment() . $current_lang. '_' . $blog_id. '_'.$scheme;
	        $cached    = MyAgilePrivacy::get_json_cache( $cache_key );

	        if( !$skip_json_cache_read )
	        {
		        if( $cached !== false )
		        {
		            echo '<div id="map-inject-root">' . $cached . '</div>';
		            return;
		        }
	        }

	        $is_polylang_enabled = $this->check_if_polylang_enabled();
	        $is_wpml_enabled     = $this->check_if_wpml_enabled();

	        $clarity_consent_mode_consents = null;

	        if( isset( $the_settings['pa'] ) &&
	            $the_settings['pa'] == 1 &&
	            isset( $the_settings['enable_clarity_cmode'] ) && $the_settings['enable_clarity_cmode'] )
	        {
	            $clarity_consent_mode_consents   = array();
	            $clarity_consent_mode_consents[] = array(
	                'key'        => 'clarity_ad_storage',
	                'human_name' => esc_html( $the_translations[ $current_lang ]['ad_storage'] ),
	                'human_desc' => esc_html( $the_translations[ $current_lang ]['ad_storage_clarity_desc'] ),
	            );
	            $clarity_consent_mode_consents[] = array(
	                'key'        => 'clarity_analytics_storage',
	                'human_name' => esc_html( $the_translations[ $current_lang ]['analytics_storage'] ),
	                'human_desc' => esc_html( $the_translations[ $current_lang ]['analytics_storage_clarity_desc'] ),
	            );
	        }

	        $microsoft_consent_mode_consents = null;
	        if( isset( $the_settings['pa'] ) &&
	            $the_settings['pa'] == 1 &&
	            isset( $the_settings['enable_microsoft_cmode'] ) && $the_settings['enable_microsoft_cmode'] )
	        {
	            $microsoft_consent_mode_consents   = array();
	            $microsoft_consent_mode_consents[] = array(
	                'key'        => 'microsoft_ad_storage',
	                'human_name' => esc_html( $the_translations[ $current_lang ]['ad_storage'] ),
	                'human_desc' => esc_html( $the_translations[ $current_lang ]['ad_storage_microsoft_desc'] ),
	            );
	        }

	        $consent_mode_consents = null;
	        if( isset( $the_settings['pa'] ) &&
	            $the_settings['pa'] == 1 &&
	            isset( $the_settings['enable_cmode_v2'] ) && $the_settings['enable_cmode_v2'] &&
	            !( isset( $the_settings['bypass_cmode_enable'] ) && $the_settings['bypass_cmode_enable'] == true )
	        )
	        {
	            $consent_mode_consents   = array();
	            $consent_mode_consents[] = array(
	                'key'        => 'ad_storage',
	                'human_name' => esc_html( $the_translations[ $current_lang ]['ad_storage'] ),
	                'human_desc' => esc_html( $the_translations[ $current_lang ]['ad_storage_desc'] ),
	            );
	            $consent_mode_consents[] = array(
	                'key'        => 'ad_user_data',
	                'human_name' => esc_html( $the_translations[ $current_lang ]['ad_user_data'] ),
	                'human_desc' => esc_html( $the_translations[ $current_lang ]['ad_user_data_desc'] ),
	            );
	            $consent_mode_consents[] = array(
	                'key'        => 'ad_personalization',
	                'human_name' => esc_html( $the_translations[ $current_lang ]['ad_personalization'] ),
	                'human_desc' => esc_html( $the_translations[ $current_lang ]['ad_personalization_desc'] ),
	            );
	            $consent_mode_consents[] = array(
	                'key'        => 'analytics_storage',
	                'human_name' => esc_html( $the_translations[ $current_lang ]['analytics_storage'] ),
	                'human_desc' => esc_html( $the_translations[ $current_lang ]['analytics_storage_desc'] ),
	            );
	        }

	        $html = $this->generate_notify_html(
	            $the_settings,
	            $rconfig,
	            $the_translations,
	            $current_lang,
	            $is_polylang_enabled,
	            $is_wpml_enabled,
	            $clarity_consent_mode_consents,
	            $microsoft_consent_mode_consents,
	            $consent_mode_consents
	        );

	        if( !$skip_json_cache )
	        {
	        	MyAgilePrivacy::set_json_cache( $cache_key, $html );
	        }

	        echo '<div id="map-inject-root">' . $html . '</div>';
	    }
	}

	/**
	 * Generates the full HTML output for the privacy banner and cookie settings modal.
	 * Called only on cache miss by inject_html_code().
	 *
	 * @param array      $the_settings
	 * @param array      $rconfig
	 * @param array      $the_translations
	 * @param string     $current_lang
	 * @param bool       $is_polylang_enabled
	 * @param bool       $is_wpml_enabled
	 * @param array|null $clarity_consent_mode_consents
	 * @param array|null $microsoft_consent_mode_consents
	 * @param array|null $consent_mode_consents
	 * @return string
	 */
	private function generate_notify_html(
	    $the_settings,
	    $rconfig,
	    $the_translations,
	    $current_lang,
	    $is_polylang_enabled,
	    $is_wpml_enabled,
	    $clarity_consent_mode_consents,
	    $microsoft_consent_mode_consents,
	    $consent_mode_consents
	)
	{
		return require plugin_dir_path( MAP_PLUGIN_FILENAME ) . 'includes/my-agile-privacy-banner-renderer.php';
	}

	/**
	 * head inject
	 * @access   public
	*/
	public function wp_head_inject()
	{
		$skip = MyAgilePrivacy::check_buffer_skip_conditions( false );
		// the page filter already carries the head
		if( $skip == 'false' && !$this->head_script )
		{
			echo $this->get_head_script_string( false );
		}
	}

	/**
	* function for plugin init
	 * @access   public
	*/
	public function plugin_init()
	{
		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'plugin_init' );

		global $sitepress;
		if( function_exists( 'icl_object_id' ) && $sitepress )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'add filter wpml_config_array' );

			add_filter( 'wpml_config_array', array( $this, 'map_wpml_config_array' ) );
		}

		$labels = array(
			'name'					=> wp_kses_post( __( 'My Agile Privacy®', 'MAP_txt' ) ),
			'all_items'             => wp_kses_post( __( 'Cookie List', 'MAP_txt' ) ),
			'singular_name'			=> wp_kses_post( __( 'Cookie', 'MAP_txt' ) ),
			'add_new'				=> wp_kses_post( __( 'Add New Cookie', 'MAP_txt' ) ),
			'add_new_item'			=> wp_kses_post( __( 'Add New Cookie', 'MAP_txt' ) ),
			'edit_item'				=> wp_kses_post( __( 'Edit Cookie', 'MAP_txt' ) ),
			'new_item'				=> wp_kses_post( __( 'New Cookie', 'MAP_txt' ) ),
			'view_item'				=> wp_kses_post( __( 'View Cookie', 'MAP_txt' ) ),
			'search_items'			=> wp_kses_post( __( 'Search Cookies', 'MAP_txt' ) ),
			'not_found'				=> wp_kses_post( __( 'Nothing found', 'MAP_txt' ) ),
			'not_found_in_trash'	=> wp_kses_post( __( 'Nothing found in Trash', 'MAP_txt' ) ),
			'parent_item_colon'		=> ''
		);

		$args = array(
			'labels'				=> $labels,
			'public'				=> false,
			'publicly_queryable'	=> false,
			'exclude_from_search'	=> true,
			'show_ui'				=> true,
			'query_var'				=> true,
			'rewrite'				=> true,
			'menu_icon' 			=> plugin_dir_url(__DIR__).'/admin/img/logo.png',
			'capabilities' => array(
				'publish_posts' => MyAgilePrivacy::get_capability( 'options' ),
				'edit_posts' => MyAgilePrivacy::get_capability( 'options' ),
				'edit_others_posts' => MyAgilePrivacy::get_capability( 'options' ),
				'delete_posts' => MyAgilePrivacy::get_capability( 'options' ),
				'delete_others_posts' => MyAgilePrivacy::get_capability( 'options' ),
				'read_private_posts' => MyAgilePrivacy::get_capability( 'options' ),
				'edit_post' => MyAgilePrivacy::get_capability( 'options' ),
				'delete_post' => MyAgilePrivacy::get_capability( 'options' ),
				'read_post' => MyAgilePrivacy::get_capability( 'options' ),
			),
			'hierarchical'			=> false,
			'menu_position' 		=> 5,
			'supports'				=> array( 'title', 'editor' ),
			'can_export'			=> false,
		);

		register_post_type( MAP_POST_TYPE_COOKIES, $args );

		register_post_status( '__expired', array(
			'label'                     => _x( 'Expired', MAP_POST_TYPE_COOKIES, 'MAP_txt' ),
			'public'                    => false,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Expired <span class="count">(%s)</span>', 'Expired <span class="count">(%s)</span>' ),
		) );

		register_post_status( '__blocked', array(
			'label'                     => _x( 'Blocked without notification', MAP_POST_TYPE_COOKIES, 'MAP_txt' ),
			'public'                    => true,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( wp_kses_post( __( 'Blocked without notification', 'MAP_txt' ) ).' <span class="count">(%s)</span>', wp_kses_post( __( 'Blocked without notification', 'MAP_txt' ) ).' <span class="count">(%s)</span>' ),
		) );

		register_post_status( '__always_allowed', array(
			'label'                     => _x( 'Allowed without notification', MAP_POST_TYPE_COOKIES, 'MAP_txt' ),
			'public'                    => true,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( wp_kses_post( __( 'Allowed without notification', 'MAP_txt' ) ).' <span class="count">(%s)</span>', wp_kses_post( __( 'Allowed without notification', 'MAP_txt' ) ).' <span class="count">(%s)</span>' ),
		) );

		$labels = array(
			'name'					=> wp_kses_post( __( 'My Agile Privacy®', 'MAP_txt' ) ),
			'all_items'             => wp_kses_post( __( 'Policies List', 'MAP_txt' ) ),
			'singular_name'			=> wp_kses_post( __( 'Policy', 'MAP_txt' ) ),
			'add_new'				=> wp_kses_post( __( 'Add New Policy', 'MAP_txt' ) ),
			'add_new_item'			=> wp_kses_post( __( 'Add New Policy', 'MAP_txt' ) ),
			'edit_item'				=> wp_kses_post( __( 'Edit Policy', 'MAP_txt' ) ),
			'new_item'				=> wp_kses_post( __( 'New Policy', 'MAP_txt' ) ),
			'view_item'				=> wp_kses_post( __( 'View Policy', 'MAP_txt' ) ),
			'search_items'			=> wp_kses_post( __( 'Search Policy', 'MAP_txt' ) ),
			'not_found'				=> wp_kses_post( __( 'Nothing found', 'MAP_txt' ) ),
			'not_found_in_trash'	=> wp_kses_post( __( 'Nothing found in Trash', 'MAP_txt' ) ),
			'parent_item_colon'		=> ''
		);

		$map_policy_cap = MyAgilePrivacy::get_capability( 'options' );

		$args = array(
			'labels'				=> $labels,
			'public'				=> false,
			'publicly_queryable'	=> false,
			'exclude_from_search'	=> true,
			'show_ui'				=> true,
			'query_var'				=> true,
			'rewrite'				=> true,
			'menu_icon' 			=> plugin_dir_url(__DIR__).'/admin/img/logo.png',
			'capabilities' => array(
				'publish_posts' => $map_policy_cap,
				'edit_posts' => $map_policy_cap,
				'edit_others_posts' => $map_policy_cap,
				'delete_posts' => 'do_not_allow',
				'delete_others_posts' => $map_policy_cap,
				'read_private_posts' => $map_policy_cap,
				'edit_post' => $map_policy_cap,
				'delete_post' => $map_policy_cap,
				'read_post' => $map_policy_cap,
				'create_posts' => 'do_not_allow',
			),
			'hierarchical'			=> false,
			'menu_position'			=> null,
			'supports'				=> array( 'title', 'editor' ),
			'can_export'			=> false,
		);

		register_post_type( MAP_POST_TYPE_POLICY, $args );

		register_post_status( '__expired', array(
			'label'                     => _x( 'Expired', MAP_POST_TYPE_POLICY, 'MAP_txt' ),
			'public'                    => false,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Expired <span class="count">(%s)</span>', 'Expired <span class="count">(%s)</span>' ),
		) );

		remove_post_type_support( MAP_POST_TYPE_POLICY, 'title' );

		//blocked on elementor, divi, thrive theme builder
		if( !is_admin() &&
			//elementor
			!isset( $_GET['elementor-preview'] ) &&
			//divi
			( !isset( $_GET['et_fb'] ) || !MyAgilePrivacy::looseEqualsPhp8( $_GET['et_fb'], 1 ) ) &&
			//thrive theme builder
			( !isset( $_GET['action'] ) || $_GET['action'] != 'architect' ) &&
			( !isset( $_GET['tve'] ) || $_GET['tve'] != 'true' )
		)
		{
			$this->set_locale();
		}
	}

	/**
	 * Register the stylesheets for the frontend area.
	 * @access   public
	 */
	public function enqueue_styles()
	{
		$the_settings = MyAgilePrivacy::get_settings();

		if( $the_settings['is_on'] == true )
		{
			$rconfig = MyAgilePrivacy::get_rconfig();

			$minified_filename_add = "";

			if( defined( 'MAP_USE_MINIFIED_FILES' ) && MAP_USE_MINIFIED_FILES )
			{
				$minified_filename_add = ".min";
			}

			if( isset( $rconfig ) &&
				isset( $rconfig['use_css_reset'] ) &&
				$rconfig['use_css_reset'] == 1 )
			{
				wp_enqueue_style( $this->plugin_name.'-reset', plugin_dir_url(__FILE__) . "css/my-agile-privacy-reset$minified_filename_add.css", array(), $this->version, 'all' );
			}

			wp_enqueue_style( $this->plugin_name.'-animate', plugin_dir_url(__FILE__) . "css/animate.min.css", array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name, plugin_dir_url(__FILE__) . "css/my-agile-privacy-frontend$minified_filename_add.css", array(), $this->version, 'all' );

			if( current_user_can( 'manage_options' ) && isset( $the_settings['pa'] ) && $the_settings['pa'] == 1 )
			{
				wp_enqueue_style( $this->plugin_name.'-notification-bar', plugin_dir_url(__FILE__) . "css/my-agile-privacy-notification-bar$minified_filename_add.css", array(), $this->version, 'all' );
			}
		}
	}

	//keeps the plugin stylesheets out of async loading
	public function map_jetpack_boost_async_style( $method, $handle )
	{
		if( is_string( $handle ) &&
			in_array( $handle, array( $this->plugin_name, $this->plugin_name.'-reset', $this->plugin_name.'-animate', $this->plugin_name.'-notification-bar' ), true ) )
		{
			return false;
		}

		return $method;
	}

	//add script attributes
	public function add_script_attributes( $tag, $handle, $src )
	{
	    if( is_string( $tag ) && is_string( $handle ) && strpos( $handle, $this->plugin_name ) !== false )
	    {
	        $new_tag = preg_replace( '#^(?:\s*<script\b(?![^>]*\ssrc=)[^>]*>.*?</script>)*\s*\K<script\b(?=[^>]*\ssrc=)#is', '<script ' . MAP_INLINE_SCRIPT_EXTRA_ATTRS, $tag, 1 );

	        $tag = is_string( $new_tag ) ? $new_tag : $tag;
	    }

	    return $tag;
	}

	/**
	 * Adds jquery to the queued frontend handles, when it is still registered.
	 * @access   public
	 */
	public function add_legacy_jquery_dependency()
	{
		if( !function_exists( 'wp_scripts' ) || !wp_script_is( 'jquery', 'registered' ) )
		{
			return;
		}

		$map_scripts = wp_scripts();

		foreach( (array) $map_scripts->queue as $handle )
		{
			if( !is_string( $handle ) ||
				strpos( $handle, $this->plugin_name ) !== 0 ||
				!isset( $map_scripts->registered[ $handle ] ) )
			{
				continue;
			}

			$map_script = $map_scripts->registered[ $handle ];

			if( !is_array( $map_script->deps ) ||
				in_array( 'jquery', $map_script->deps, true ) ||
				( is_string( $map_script->src ) && substr( $map_script->src, -11 ) === 'js/empty.js' ) )
			{
				continue;
			}

			array_unshift( $map_script->deps, 'jquery' );
		}
	}

	/**
	 * Register the js for the frontend area.
	 * @access   public
	 */
	public function enqueue_scripts()
	{
		$the_settings = MyAgilePrivacy::get_settings();

		if( $the_settings['is_on'] == true )
		{
			$load_type = 'std';

			$rconfig = MyAgilePrivacy::get_rconfig();

			$skip = MyAgilePrivacy::check_buffer_skip_conditions( false );

			//skip banner display on skip rules
			if( $skip == 'true' )
			{
				return;
			}

			$this->scripts_enqueued = true;

			$post_mode = false;
			$post_mode_deps = array( $this->plugin_name, $this->plugin_name.'-anime' );

			if( $skip == 'true_due_to_post' )
			{
				$post_mode = true;

				wp_enqueue_script( $this->plugin_name, plugin_dir_url(__FILE__) . 'js/empty.js', array(), $this->version, false );

				$blocks_to_inject = $this->head_script ? array( 'inline' => array(), 'enqueue' => array() ) : $this->get_head_script_string( true );

				foreach( $blocks_to_inject['inline'] as $k => $elem )
				{
					if( function_exists( 'wp_add_inline_script' ) )
					{
						wp_add_inline_script( $this->plugin_name, $elem );
					}
					else
					{
						$map_scripts = wp_scripts();
						$map_script_data = $map_scripts->get_data( $this->plugin_name, 'data' );
						$map_scripts->add_data( $this->plugin_name, 'data', ( $map_script_data ? $map_script_data . "\n" : '' ) . $elem );
					}
				}

				$map_enqueued_keys = array();

				foreach( $blocks_to_inject['enqueue'] as $k => $elem )
				{
					$map_enqueue_ver = $this->version;

					if( is_array( $elem ) )
					{
						if( !isset( $elem['src'], $elem['ver'] ) || !is_string( $elem['src'] ) || !is_string( $elem['ver'] ) )
						{
							continue;
						}

						$map_enqueue_ver = $elem['ver'];
						$elem            = $elem['src'];
					}

					wp_enqueue_script( $this->plugin_name.'_enqueue_'.$k, $elem, array(), $map_enqueue_ver, false );
					$post_mode_deps[] = $this->plugin_name.'_enqueue_'.$k;
					$map_enqueued_keys[] = $k;
				}

				// attached once the handles exist
				$map_early = $this->early_gcmode_block_target( $blocks_to_inject, $map_enqueued_keys );

				if( null !== $map_early )
				{
					if( function_exists( 'wp_add_inline_script' ) )
					{
						wp_add_inline_script( $map_early['handle'], $map_early['code'], 'after' );
					}
					else
					{
						$map_scripts = wp_scripts();
						$map_script_data = $map_scripts->get_data( $map_early['handle'], 'data' );
						$map_scripts->add_data( $map_early['handle'], 'data', ( $map_script_data ? $map_script_data . "\n" : '' ) . $map_early['code'] );
					}

					$this->early_gcmode_shield_handle = $map_early['handle'];
				}
			}

			$minified_filename_add = "";

			if( defined( 'MAP_USE_MINIFIED_FILES' ) && MAP_USE_MINIFIED_FILES )
			{
				$minified_filename_add = ".min";
			}

			$js_frontend_filepath = "js/plain/my-agile-privacy-frontend$minified_filename_add.js";

			wp_enqueue_script( $this->plugin_name.'-anime', plugin_dir_url(__FILE__) . 'js/anime.min.js', array(), $this->version, false );
			wp_enqueue_script( $this->plugin_name, plugin_dir_url(__FILE__) . $js_frontend_filepath, array(), $this->version, false );

			if( $post_mode )
			{
				wp_enqueue_script( $this->plugin_name.'-postmode', plugin_dir_url(__FILE__) . $js_frontend_filepath, $post_mode_deps, $this->version, false );
			}

			if( MyAgilePrivacy::is_legacy_mode_configured( $the_settings, $rconfig ) )
			{
				add_action( 'wp_print_scripts', array( $this, 'add_legacy_jquery_dependency' ), 999 );
			}

			add_filter( 'script_loader_tag', array( $this, 'add_script_attributes' ), 10, 3 );

			$json_settings = $this->getJsonSettings();

			if( $post_mode )
			{
				$json_settings['post_mode'] = 1;
			}

			wp_localize_script( $this->plugin_name, 'map_cookiebar_settings', $json_settings );

			$cookie_process_delayed_mode = false;

			if( $rconfig &&
				isset( $rconfig['cookie_process_delayed_mode'] ) &&
				$rconfig['cookie_process_delayed_mode'] == 1
			)
			{
				$cookie_process_delayed_mode = true;
			}

			$force_js_learning_mode = 0;

			if( $rconfig &&
				isset( $rconfig['force_js_learning_mode'] ) &&
				$rconfig['force_js_learning_mode'] == 1
			)
			{
				$force_js_learning_mode = 1;
			}

			$map_ajax_settings = $this->getMapAjaxSettings( $force_js_learning_mode, intval( $cookie_process_delayed_mode ) );
			wp_localize_script( $this->plugin_name, 'map_ajax', $map_ajax_settings );

			if( current_user_can( 'manage_options' ) && isset( $the_settings['pa'] ) && $the_settings['pa'] == 1 )
			{
				wp_localize_script( $this->plugin_name, 'map_nb_config', array(
					'style'   => 'card',
					'mascot'  => plugin_dir_url( __FILE__ ) . 'img/fox-mascot.png',
					'shape'   => 'round',
					'product' => 'My Agile Privacy',
				) );
			}

			if(
				isset( $the_settings['pa'] ) &&
				$the_settings['pa'] == 1 &&
				$the_settings['scan_mode'] == 'learning_mode' &&
				$the_settings['scanner_compatibility_mode'] )
			{
				$this->internal_save_detected_keys();
			}
		}
	}

	/**
	 * Adds extra attributes to inline <script> tags tied to this plugin's handle.
	 * Works with inline generated by wp_localize_script (…-js-extra)
	 * and wp_add_inline_script (…-js-before / …-js-after).
	 * This works on WP 4.4+
	 *
	 */
	public function add_attrs_to_inline_script( $attributes, $id )
	{
		// Determine the inline script ID reliably.
		$inline_id = '';
		if( isset( $attributes['id'] ) && is_string( $attributes['id'] ) )
		{
			$inline_id = $attributes['id'];
		}
		elseif( is_string( $id ) )
		{
			$inline_id = $id;
		}

		$early = ( $inline_id !== '' &&
			is_string( $this->early_gcmode_shield_handle ) &&
			$this->early_gcmode_shield_handle !== '' &&
			$inline_id === $this->early_gcmode_shield_handle . '-js-after' );

		// Only target inline scripts tied to this handle.
		// WP uses "{$handle}-js-extra|before|after".
		$prefix = $this->plugin_name . '-js-';
		if( $inline_id === '' || ( !$early && strpos( $inline_id, $prefix ) !== 0 ) )
		{
			return $attributes;
		}

		if( $early )
		{
			$attributes['class'] = ( isset( $attributes['class'] ) && is_string( $attributes['class'] ) && $attributes['class'] !== '' ) ? $attributes['class'] . ' map_early_gcmode' : 'map_early_gcmode';
		}

		// Parse the attributes string constant into an associative array.
		$extra = $this->parse_attrs_string( MAP_INLINE_SCRIPT_EXTRA_ATTRS );

		// Merge without overwriting existing attributes.
		foreach ( $extra as $name => $value )
		{
			if( array_key_exists( $name, $attributes ) )
			{
				continue;
			}

			// For inline scripts, use string values even for boolean attributes.
			$attributes[ $name ] = ( $value === true ) ? '1' : (string) $value;
		}

		return $attributes;
	}


	/**
	 * Converts an HTML attributes string into a map [name => value].
	 * - Attributes without an explicit value are treated as boolean true (e.g., nowprocket).
	 * - Example input: data-no-minify="1" nowprocket data-cfasync="false"
	 *
	 */
	private function parse_attrs_string( $attr_string )
	{
		$result = array();

		// Match name[=value] pairs, supporting quoted and unquoted values.
		if( preg_match_all( '/([^\s=]+)(?:=("[^"]*"|\'[^\']*\'|[^"\']+))?/', $attr_string, $matches, PREG_SET_ORDER ) ) {
			foreach( $matches as $m )
			{
				$name = strtolower( trim( $m[1] ) );
				if( $name === '' )
				{
					continue;
				}

				if( isset( $m[2] ) && $m[2] !== '' )
				{
					$val = trim( $m[2] );

					// Strip surrounding quotes if present.
					$first = substr( $val, 0, 1 );
					$last  = substr( $val, -1 );
					if( ( $first === '"' && $last === '"' ) || ( $first === "'" && $last === "'" ) )
					{
						$val = substr( $val, 1, -1 );
					}

					$result[ $name ] = $val;
				}
				else
				{
					// Boolean attribute (no value specified).
					$result[ $name ] = true;
				}
			}
		}

		return $result;
	}


	/**
	 * Returns one field of a stored translation as a string.
	 * @access   private
	*/
	private static function translation_field( $translations, $lang, $field )
	{
		if( null === $lang || !is_array( $translations ) || !isset( $translations[ $lang ][ $field ] ) )
		{
			return '';
		}

		$value = $translations[ $lang ][ $field ];

		return ( is_string( $value ) || is_int( $value ) || is_float( $value ) ) ? (string) $value : '';
	}


	/**
	* function for getting cookie list displayed
	 * @access   public
	*/
	public function get_cookie_categories_description( $the_post_status='publish' )
	{
		$the_settings = MyAgilePrivacy::get_settings();

		$rconfig = MyAgilePrivacy::get_rconfig();

		$currentAndSupportedLanguages = MyAgilePrivacy::getCurrentAndSupportedLanguages();
		$lang = $currentAndSupportedLanguages['current_language'];

		$post_status = array( $the_post_status );

		$cookie_categories = array(
			'necessary'			=>	array(),
			'not-necessary'		=>	array(),
		);

		$cc_args = array(
			'posts_per_page'   			=> 	-1,
			'post_type'        			=>	MAP_POST_TYPE_COOKIES,
			'post_status' 	   			=> 	$post_status,
			'no_found_rows'    			=> 	true,
			'update_post_meta_cache'	=> 	false,
			'update_post_term_cache'	=> 	false,
		);

		$cc_query = new WP_Query( $cc_args );

		$i = 0;

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

					if( $this_post_status == $the_post_status )
					{
						$post_data = get_post( $main_post_id );
						$post_title = get_the_title( $main_post_id );

						$the_post_content = stripslashes( $post_data->post_content );

						$elem = array(
							'post_meta' 	=> 	get_post_meta( $main_post_id ),
							'post_data' 	=> 	$post_data,
							'post_title'	=>	$post_title,
							'post_content' 	=> 	$the_post_content,
							'remote_id'		=>	null,
							'api_key' 		=> 	null,
						);

						if( $currentAndSupportedLanguages['with_multilang'] )
						{
							$post_custom = get_post_custom( $main_post_id );
							$_map_translations_decoded = ( isset( $post_custom["_map_translations"][0] ) ) ? wp_unslash( json_decode( $post_custom["_map_translations"][0], true ) )  : null;

							if( $_map_translations_decoded )
							{
								if( null !== $lang && isset( $_map_translations_decoded[ $lang ] ) )
								{
									$elem['post_title'] = self::translation_field( $_map_translations_decoded, $lang, 'name' );
									$elem['post_content'] = self::translation_field( $_map_translations_decoded, $lang, 'text' );
								}
								else
								{
									if(
										isset( $the_settings ) &&
										isset( $the_settings['enable_language_fallback'] ) &&
										$the_settings['enable_language_fallback'] &&
										isset( $the_settings['language_fallback_locale'] ) &&
										$the_settings['language_fallback_locale']
									)
									{
										$language_key = MyAgilePrivacy::normalizeLocaleTo2Char( $the_settings['language_fallback_locale'] );
									}
									else
									{
										$language_key = $currentAndSupportedLanguages['multilang_default_lang'];
									}

									if( null !== $language_key && isset( $_map_translations_decoded[ $language_key ] ) )
									{
										$elem['post_title'] = self::translation_field( $_map_translations_decoded, $language_key, 'name' );
										$elem['post_content'] = self::translation_field( $_map_translations_decoded, $language_key, 'text' );
									}
									else
									{
										$elem['post_title'] = '';
										$elem['post_content'] = '';
									}
								}
							}

							if( isset( $elem['post_meta']['_map_translations'] ) )
							{
								unset( $elem['post_meta']['_map_translations'] );
							}
						}

						if( isset( $elem['post_meta']['_map_remote_id'][0] ) )
						{
							$elem['remote_id'] = $elem['post_meta']['_map_remote_id'][0];
						}
						else
						{
							//custom cookies
							$elem['remote_id'] = '_c'.$i;
							$i++;
						}

						if( isset( $elem['post_meta']['_map_api_key'][0] ) )
						{
							$elem['api_key'] = $elem['post_meta']['_map_api_key'][0];
						}

						if( isset( $elem['post_meta']['_map_is_necessary'] ) )
						{
							$the_key = ( $elem['post_meta']['_map_is_necessary'][0]  == 'necessary' ) ? 'necessary' : 'not-necessary';

							//consent mode v2 ga4: basic = optional, advanced = always-on
							if( ( $elem['api_key'] == 'google_analytics' || $elem['api_key'] == 'my_agile_pixel_ga' ) &&
								isset( $the_settings ) &&
								isset( $the_settings['cmode_v2_implementation_type'] ) &&
								$the_settings['cmode_v2_implementation_type'] == 'native' )
							{
								if( isset( $the_settings['cmode_v2_forced_off_ga4_advanced'] ) &&
									$the_settings['cmode_v2_forced_off_ga4_advanced'] )
								{
									$the_key = 'not-necessary';
								}
								else
								{
									$the_key = 'necessary';
								}
							}

							//video privacy mode
							if( in_array( $elem['api_key'], array( 'youtube', 'vimeo' ), true ) )
							{
								if( isset( $the_settings['video_privacy_mode'] ) &&
									$the_settings['video_privacy_mode'] == 'block' )
								{
									$the_key = 'not-necessary';
								}
								else
								{
									$the_key = 'necessary';
								}
							}


							$cookie_categories[ $the_key ][] = $elem;
						}

					}
				}

				wp_cache_delete( $main_post_id, 'post_meta' );
			}

			MyAgilePrivacy::internal_query_reset();
		}

		$currentPHPVersion = phpversion();

		// Specify the minimum PHP required version
		$requiredPHPVersion = '7.0.0';

		// Compare the versions
		if( version_compare( $currentPHPVersion, $requiredPHPVersion, '>=' ) )
		{
			usort( $cookie_categories['necessary'], array( 'MyAgilePrivacy', 'frontendCookieSort' ) );
			usort( $cookie_categories['not-necessary'], array( 'MyAgilePrivacy', 'frontendCookieSort' ) );
		}

		return $cookie_categories;
	}

	/**
	 * Plugin auto update
	 * @access   public
	 */
	public function auto_update_plugins( $update, $item )
	{
		$the_settings = MyAgilePrivacy::get_settings();

		$rconfig = MyAgilePrivacy::get_rconfig();

		$plugins = array ( MAP_PLUGIN_SLUG );

		if( is_object( $item ) &&
			property_exists( $item, 'slug' ) &&
			in_array( $item->slug, $plugins ) )
		{
			if( isset( $the_settings ) &&
				isset( $the_settings['forced_auto_update'] ) )
			{
				return ($the_settings['forced_auto_update']) ? true : null;
			}
			elseif( isset( $rconfig ) &&
				isset( $rconfig['disable_plugin_autoupdate'] ) &&
				$rconfig['disable_plugin_autoupdate'] == 1 )
			{
				// use default settings
				return $update;
			}
			else
			{
				// update plugin
				return true;
			}

		} else {
			// use default settings
			return $update;
		}
	}


	/**
	 * buffer start
	 * @access   public
	*/
	public function map_buffer_start()
	{
		MyAgilePrivacy::arm_banner_variant();

		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'map_buffer_start' );

		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $_REQUEST );

		$skip = MyAgilePrivacy::check_buffer_skip_conditions( false );

		$the_settings = MyAgilePrivacy::get_settings();

		$rconfig = MyAgilePrivacy::get_rconfig();

		if( $skip != 'true' )
		{
			global $post;

			$this->saved_post = $post;

			$this->saved_settings = $the_settings;

			$this->scan_mode = ( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1 ) ? $the_settings['scan_mode'] : 'turned_off';

			if( !isset( $this->scan_mode ) || $this->scan_mode == 'turned_off' )
			{
				$skip = true;
			}
		}

		if( $skip != 'true' )
		{
			$post_status_to_search = array( 'publish' , '__always_allowed' );

			if( $this->scan_mode == 'learning_mode' )
			{
				$post_status_to_search = array( 'publish', 'draft' );

				$scan_runtime = MyAgilePrivacy::get_scan_runtime();

				// hourly resolution is enough for the scan date shown in admin
				if( !is_numeric( $scan_runtime['last_scan_date_internal'] ) ||
					abs( time() - (int) $scan_runtime['last_scan_date_internal'] ) >= HOUR_IN_SECONDS )
				{
					MyAgilePrivacy::update_scan_runtime( array( 'last_scan_date_internal' => time() ) );
				}
			}

			$parse_config = array();

			if( isset( $this->saved_settings['parse_config'] ) && is_string( $this->saved_settings['parse_config'] ) )
			{
				$parse_config = json_decode( $this->saved_settings['parse_config'], true );
			}

			//fix
			if( !is_array( $parse_config ) )
			{
				$parse_config = array();
			}

			foreach( $parse_config as $map_pc_group => $map_pc_entries )
			{
				if( !is_array( $map_pc_entries ) )
				{
					unset( $parse_config[ $map_pc_group ] );
					continue;
				}

				foreach( $map_pc_entries as $map_pc_key => $map_pc_entry )
				{
					if( !is_array( $map_pc_entry ) )
					{
						unset( $parse_config[ $map_pc_group ][ $map_pc_key ] );
						continue;
					}

					foreach( array( 'plain_js', 'src', 'href', 'added_src' ) as $map_pc_field )
					{
						if( isset( $map_pc_entry[ $map_pc_field ] ) && !is_string( $map_pc_entry[ $map_pc_field ] ) )
						{
							$parse_config[ $map_pc_group ][ $map_pc_key ][ $map_pc_field ] = is_scalar( $map_pc_entry[ $map_pc_field ] ) ? (string) $map_pc_entry[ $map_pc_field ] : null;
						}
					}
				}
			}

			//bof _map_js_dependencies inject
			$cookies_categories_data = $this->get_cookie_categories_description( 'publish' );

			foreach( $cookies_categories_data as $k => $v )
			{
				foreach( $v as $key => $value )
				{
					$the_remote_id = $value['remote_id'];
					$the_post_api_key = isset( $value['post_meta']['_map_api_key'][0] ) ? $value['post_meta']['_map_api_key'][0] : null;

					$_map_js_dependencies = ( isset( $value['post_meta']["_map_js_dependencies"][0] ) ) ? $value['post_meta']["_map_js_dependencies"][0] : '';

					$_map_js_dependencies_array = json_decode( $_map_js_dependencies , true );

					if( $the_post_api_key &&
						is_array( $_map_js_dependencies_array ) &&
						!empty( $_map_js_dependencies_array ) )
					{
						foreach( $_map_js_dependencies_array as $single_map_js_dependencies )
						{
							if(
								isset( $single_map_js_dependencies['type'] ) &&
								$single_map_js_dependencies['type'] &&
								$single_map_js_dependencies['type'] != '' &&

								isset( $single_map_js_dependencies['value'] ) &&
								$single_map_js_dependencies['value'] &&
								$single_map_js_dependencies['value'] != '' )
							{
								switch( $single_map_js_dependencies['type'] )
								{
									case 'js_patterns_src':
										$item = array(
											"active"				=> 	false,
											'plain_js' 				=> 	null,
											"src" 					=> 	$single_map_js_dependencies['value'],
											"key" 					=> 	$the_post_api_key,
											"to_detect" 			=> 	true,
											"to_block"				=> 	true,
											"detected" 				=> 	null,
											"blocked" 				=> 	null,
											"on_block_add_classes" 	=> 	'mapWait',
										);

										$parse_config['scripts_src_block'][] = $item;

										break;
									case 'js_patterns_code':
										$this->ao_code_patterns[] = array( $the_post_api_key, $single_map_js_dependencies['value'] );
										break;
								}
							}
						}
					}
				}
			}
			//bof _map_js_dependencies inject
			//video mode script entries
			$parse_config = $this->map_add_video_api_entries( $parse_config );
			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $parse_config );
			$currentAndSupportedLanguages = MyAgilePrivacy::getCurrentAndSupportedLanguages();

			//check if exists
			$cc_args = array(
				'posts_per_page'   			=> 	-1,
				'post_type'        			=>	MAP_POST_TYPE_COOKIES,
				'post_status' 				=> 	$post_status_to_search,
				'no_found_rows'    			=> 	true,
				'update_post_meta_cache'	=> 	false,
				'update_post_term_cache'	=> 	false,
			);

			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $cc_args );
			$cc_query = new WP_Query( $cc_args );

			$key_to_enable = array();
			$key_to_not_block = array();
			$key_to_always_allow = array();
			$friendly_name_map = array();

			if( $cc_query->have_posts() )
			{
				foreach( $cc_query->get_posts() as $p )
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
							$main_post_title = get_the_title( $p->ID );

							$the_key = get_post_meta( $main_post_id, '_map_api_key', true );
							$is_necessary = get_post_meta( $main_post_id, '_map_is_necessary', true );

							//advanced consent mode ga4
							if( ( $the_key == 'google_analytics' || $the_key == 'my_agile_pixel_ga' ) &&
								isset( $the_settings ) &&
								isset( $the_settings['cmode_v2_implementation_type'] ) &&
								$the_settings['cmode_v2_implementation_type'] == 'native' &&
								isset( $the_settings['cmode_v2_forced_off_ga4_advanced'] ) &&
								$the_settings['cmode_v2_forced_off_ga4_advanced'] )
							{
								$is_necessary = 'not-necessary';
							}

							//video privacy mode
							if( in_array( $the_key, array( 'youtube', 'vimeo' ), true ) &&
								isset( $the_settings['video_privacy_mode'] ) &&
								$the_settings['video_privacy_mode'] == 'block' )
							{
								$is_necessary = 'not-necessary';
							}

							if( $the_key )
							{
								if( $this_post_status == 'publish' )
								{
									//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $the_key );
									$key_to_enable[] = $the_key;

									$friendly_name_map[ $the_key ] = $main_post_title;

									if( $is_necessary == 'necessary' )
									{
										$key_to_not_block[] = $the_key;
									}
								}

								if( $this_post_status == '__always_allowed' )
								{
									$key_to_always_allow[] = $the_key;
								}
							}
						}
					}

					wp_cache_delete( $main_post_id, 'post_meta' );
				}

				MyAgilePrivacy::internal_query_reset();
			}

			$this->map_read_blocked_silent_keys();
			$this->ao_select_code_patterns( $key_to_enable, $key_to_not_block, $key_to_always_allow );
			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $key_to_enable );
			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $key_to_always_allow );
			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $friendly_name_map );
			foreach( $parse_config as $k => &$v )
			{
				foreach( $v as $kk => &$vv )
				{
					if( is_array( $key_to_enable ) &&
						is_string( $vv['key'] ) &&
						in_array( $vv['key'], $key_to_enable, true ) )
					{
						$vv['active'] = true;

						if( isset( $friendly_name_map[ $vv['key'] ] ) )
						{
							$vv['friendly_name'] = $friendly_name_map[ $vv['key'] ];
						}

						if( is_array( $key_to_not_block ) &&
							in_array( $vv['key'], $key_to_not_block, true ) )
						{
							$vv['to_block'] = false;
							$vv['map_key_not_blockable'] = true;
						}

						$vv['always_allowed'] = false;
					}

					if( is_array( $key_to_always_allow ) &&
						is_string( $vv['key'] ) &&
						in_array( $vv['key'], $key_to_always_allow, true ) )
					{
						$vv['always_allowed'] = true;
					}
					$vv['blocked_without_notification'] = $this->map_is_blocked_silent_entry( $vv );
				}
			}

			$this->scan_config = $parse_config;

			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $parse_config );

			$logic_legacy_mode = false;

			if( MyAgilePrivacy::is_legacy_mode_configured( $the_settings, $rconfig ) ||
				MyAgilePrivacy::get_scan_runtime()['missing_cookie_shield']
			)
			{
				$logic_legacy_mode = true;
			}

			if( !$logic_legacy_mode )
			{
				$this->head_script = $this->get_head_script_string( false );
			}

			static $already_started = false;

			if( $already_started )
			{
				return;
			}

			$already_started = true;

			add_action( 'shutdown', array( $this, 'map_run_pending_writes' ), 2 );

			$this->map_register_rocket_catalog();
			$this->map_register_ao_catalog();
			ob_start( array( $this, 'map_callback' ) );
		}
	}

	/**
	 * buffer end
	 * @access   public
	*/
	public function map_buffer_end()
	{
		static $already_run = false;

		if( $already_run )
		{
			return;
		}

		$already_run = true;

		if( ob_get_level() < 1 )
		{
			return;
		}

		if( ob_get_length() === false || ob_get_length() < 1 )
		{
			return;
		}

		ob_end_flush();
	}

	/**
	 * run the writes queued by the buffer callback
	 * @access   public
	*/
	public function map_run_pending_writes()
	{
		$this->shutdown_passed = true;

		if( null !== $this->pending_detected_fields )
		{
			$detected_fields = $this->pending_detected_fields;
			$this->pending_detected_fields = null;

			MyAgilePrivacy::update_option( MAP_PLUGIN_JS_DETECTED_FIELDS, $detected_fields );
		}

		if( null !== $this->pending_html_scan_state )
		{
			$html_scan_state = $this->pending_html_scan_state;
			$this->pending_html_scan_state = null;

			$this->map_write_html_scan_state( $html_scan_state );
		}
	}

	/**
	 * get map_cookiebar_settings and map_ajax string
	 * @access   public
	 */
	public function getJsonSettings()
	{
		$the_settings = MyAgilePrivacy::get_settings();
		$rconfig = MyAgilePrivacy::get_rconfig();

		$skip_json_cache = false;

	    if( !( isset( $the_settings['pa'] ) &&
	    	$the_settings['pa'] == 1 ) )
	    {
	    	$skip_json_cache = true;
	    }

		if( $rconfig &&
			isset( $rconfig['skip_json_cache'] ) &&
			$rconfig['skip_json_cache'] == 1
		)
		{
			$skip_json_cache = true;
		}

	    if( MAP_DEV_MODE )
	    {
	    	$skip_json_cache = true;
	    }

	    if( MyAgilePrivacy::is_split_preview() )
	    {
	    	$skip_json_cache = true;
	    }

	    // One copy per language: the two values a variant changes travel in map_full_config, so a
	    // request serving a variant neither reads nor writes the shared copy.
	    if( MyAgilePrivacy::get_json_cache_variant_segment() !== '' )
	    {
	    	$skip_json_cache = true;
	    }

	    $skip_json_cache_read = $skip_json_cache || current_user_can( 'manage_options' );

	    $lang      = MyAgilePrivacy::getCurrentLang4Char();
	    $blog_id   = is_multisite() ? get_current_blog_id() : 1;
	    $scheme    = is_ssl() ? 'https' : 'http';

	    $cache_key = 'json_settings_script_' . $lang. '_' .$blog_id.'_'.$scheme;

	    if( !$skip_json_cache_read )
	    {
		    $cached = MyAgilePrivacy::get_json_cache( $cache_key );

		    if( $cached !== false )
		    {
		        return $cached;
		    }
	    }

	    $fresh_data = MyAgilePrivacy::get_json_settings();

	    if( !$skip_json_cache )
	    {
	    	MyAgilePrivacy::set_json_cache( $cache_key, MyAgilePrivacy::anonymize_json_settings( $fresh_data ) );
	    }

	    return $fresh_data;
	}

	/**
	 * Get map_ajax settings array, with JSON cache support
	 * @access public
	 */
	public function getMapAjaxSettings( $force_js_learning_mode=0, $cookie_process_delayed_mode=0 )
	{
	    $the_settings = MyAgilePrivacy::get_settings();
	    $rconfig      = MyAgilePrivacy::get_rconfig();
	    $skip_json_cache = false;

	    if( !( isset( $the_settings['pa'] ) &&
	        $the_settings['pa'] == 1 ) )
	    {
	        $skip_json_cache = true;
	    }

	    if( $rconfig &&
	        isset( $rconfig['skip_json_cache'] ) &&
	        $rconfig['skip_json_cache'] == 1
	    )
	    {
	        $skip_json_cache = true;
	    }

	    if( MAP_DEV_MODE )
	    {
	        $skip_json_cache = true;
	    }

	    $skip_json_cache_read = $skip_json_cache || current_user_can( 'manage_options' );

	    $lang      = MyAgilePrivacy::getCurrentLang4Char();
	    $blog_id   = is_multisite() ? get_current_blog_id() : 1;
	    $scheme    = is_ssl() ? 'https' : 'http';
	    $cache_key = 'json_ajax_script_' . $lang . '_' . $blog_id . '_' . $scheme;

	    $adv = $this->json_cache_adv_state();

	    if( !$skip_json_cache_read )
	    {
	        $cached = MyAgilePrivacy::get_json_cache( $cache_key, $adv );
	        if( $cached !== false )
	        {
	            return $cached;
	        }
	    }

	    $fresh_data = array(
	        'ajax_url'                    	=> admin_url( 'admin-ajax.php' ),
	        'api_url'                     	=> MyAgilePrivacy::get_api_endpoint_url( dirname( __FILE__ ), 'api/api.php' ),
	        'api_token'                   	=> ( defined( 'AUTH_KEY' ) && defined( 'AUTH_SALT' ) ) ? hash_hmac( 'sha256', 'map_api_v1', AUTH_KEY . AUTH_SALT ) : '',
		    'force_js_learning_mode'      	=> $force_js_learning_mode,
	        'scanner_compatibility_mode'  	=> $the_settings['scanner_compatibility_mode'],
	        'cookie_process_delayed_mode'	=> intval( $cookie_process_delayed_mode ),
	        'gtm_gateway_detection_disabled'=> ( isset( $the_settings['disable_gtm_gateway_detection'] ) && $the_settings['disable_gtm_gateway_detection'] ) ? 1 : 0,
	        'advanced_api_url'            	=> function_exists( 'map_call_advanced_get_api_url' ) ? map_call_advanced_get_api_url() : '',
	    );

	    if( !$skip_json_cache )
	    {
	        MyAgilePrivacy::set_json_cache( $cache_key, $fresh_data, 21600, $adv );
	    }

	    return $fresh_data;
	}

	/**
	 * State of the optional modules that the cached head and map_ajax entries depend on.
	 * @access private
	 */
	private function json_cache_adv_state( $scan_off = false )
	{
	    $adv = '0';

	    if( function_exists( 'map_is_advanced_active' ) && map_is_advanced_active() )
	    {
	        $adv_version = function_exists( 'map_call_advanced_get_bundle_version' ) ? map_call_advanced_get_bundle_version() : '';

	        $adv = '1:' . ( is_string( $adv_version ) ? $adv_version : '' );
	    }

	    $adv .= ( function_exists( 'map_bot_detection_on' ) && map_bot_detection_on() ) ? ':b1' : ':b0';
	    $adv .= $scan_off ? ':o' : '';
	    $cookie_domain = MyAgilePrivacy::get_cookie_domain_state( MyAgilePrivacy::get_rconfig() );

	    $adv .= ':d' . $cookie_domain;

	    return $adv;
	}

	/**
	 * get head script string
	 * @access   public
	*/
	public function get_head_script_string( $block_mode = false )
	{
		$the_settings = MyAgilePrivacy::get_settings();
		$rconfig = MyAgilePrivacy::get_rconfig();

		$skip_json_cache = false;

	    if( !( isset( $the_settings['pa'] ) &&
	    	$the_settings['pa'] == 1 ) )
	    {
	    	$skip_json_cache = true;
	    }

		if( $rconfig &&
			isset( $rconfig['skip_json_cache'] ) &&
			$rconfig['skip_json_cache'] == 1
		)
		{
			$skip_json_cache = true;
		}

	    if( MAP_DEV_MODE )
	    {
	    	$skip_json_cache = true;
	    }

	    if( MyAgilePrivacy::is_split_preview() )
	    {
	    	$skip_json_cache = true;
	    }

	    $skip_json_cache_read = $skip_json_cache || current_user_can( 'manage_options' );

	    $lang      = MyAgilePrivacy::getCurrentLang4Char();
	    $blog_id   = is_multisite() ? get_current_blog_id() : 1;
	    $scheme    = is_ssl() ? 'https' : 'http';

	    $cache_key = 'head_script_' . ( $block_mode ? 'block' : 'string' ) . '_' . MyAgilePrivacy::get_json_cache_variant_segment() . $lang. '_' .$blog_id.'_'.$scheme;

	    $adv = $this->json_cache_adv_state( isset( $the_settings['scan_mode'] ) && $the_settings['scan_mode'] == 'turned_off' );

	    if( !$skip_json_cache_read )
	    {
		    $cached = MyAgilePrivacy::get_json_cache( $cache_key, $adv );

		    if( $cached !== false )
		    {
		        return $cached;
		    }
	    }

	    $fresh_data = $this->generate_head_script_string( $block_mode );

	    if( !$skip_json_cache )
	    {
	    	MyAgilePrivacy::set_json_cache( $cache_key, $fresh_data, 21600, $adv );
	    }

	    return $fresh_data;
	}

	/**
	 * Contains the actual generation logic for the head script string.
	 * Called only on cache miss by get_head_script_string().
	 *
	 * @param bool $block_mode If true, returns the blocks array instead of the HTML string.
	 * @return string|array The head script HTML string, or the blocks array if block_mode is true.
	 */
	private function generate_head_script_string( $block_mode = false )
	{
	    $the_settings = MyAgilePrivacy::get_settings();
	    $rconfig = MyAgilePrivacy::get_rconfig();

	    $MyAgilePrivacyRegulationHelper = new MyAgilePrivacyRegulationHelper();
	    $frontend_regulation = $MyAgilePrivacyRegulationHelper->getFrontendConfig();

	    //get base directory, safe mode for multisite
		$_plugin_url = MyAgilePrivacy::get_plugin_url( __FILE__ );

	    $manifest_assoc = MyAgilePrivacy::get_option( MAP_MANIFEST_ASSOC, null );

	    $minified_filename_add = "";
	    $version_param = "";

	    if( defined('MAP_PLUGIN_VERSION') && MAP_PLUGIN_VERSION )
	    {
	        $version_param = '?ver='. MAP_PLUGIN_VERSION;
	    }

	    if( defined( 'MAP_USE_MINIFIED_FILES' ) && MAP_USE_MINIFIED_FILES )
	    {
	        $minified_filename_add = ".min";
	    }

	    $main_frontend_js = null;
	    $frontend_css = array();

	    if( $the_settings['is_on'] == true )
	    {
	        $main_frontend_js = $_plugin_url . "js/plain/my-agile-privacy-frontend$minified_filename_add.js$version_param";

	        if( isset( $rconfig ) &&
	            isset( $rconfig['use_css_reset'] ) &&
	            $rconfig['use_css_reset'] == 1 )
	        {
	            $frontend_css[] = $_plugin_url . "css/my-agile-privacy-reset$minified_filename_add.css$version_param";
	        }

	        $frontend_css[] = $_plugin_url . "css/animate.min.css$version_param";
	        $frontend_css[] = $_plugin_url . "css/my-agile-privacy-frontend$minified_filename_add.css$version_param";
	    }

	    $js_shield_url = null;
	    $iab_tcf_context = false;
	    $iab_tcf_script_url = null;

	    if(
	        $rconfig &&
	        isset( $rconfig['allow_iab'] ) &&
	        $rconfig['allow_iab'] == 1 &&
	        $the_settings['enable_iab_tcf'] )
	    {
	        $iab_tcf_context = true;
	    }

	    $map_wl_b = 0;

	    if( isset( $the_settings ) &&
	        isset( $the_settings['wl_b'] ) &&
	        $the_settings['wl_b'] )
	    {
	        $map_wl_b = 1;
	    }

	    $cookie_reset_timestamp = MyAgilePrivacy::nullCoalesceArrayItem( $the_settings, 'cookie_reset_timestamp', '' );

	    $cookie_reset_timestamp_rich = $cookie_reset_timestamp;

	    if( $cookie_reset_timestamp_rich )
	    {
	        $cookie_reset_timestamp_rich = '_'.$cookie_reset_timestamp;
	    }

	    $cookies_categories_data = $this->get_cookie_categories_description( 'publish' );
	    $cookie_api_key_remote_id_map_active = array();
	    $cookie_api_key_friendly_name_map = array();
	    $cookie_api_key_not_to_block = array();
	    $shield_added_pattern = array(
	        'js_patterns_src' => array(),
	    );

	    foreach( $cookies_categories_data as $k => $v )
	    {
	        foreach( $v as $key => $value )
	        {
	            $the_post_title = $value['post_title'];
	            $the_remote_id = $value['remote_id'];
	            $the_post_api_key = isset( $value['post_meta']['_map_api_key'][0] ) ? $value['post_meta']['_map_api_key'][0] : null;
	            $is_necessary = isset( $value['post_meta']['_map_is_necessary'][0] ) ? $value['post_meta']['_map_is_necessary'][0] : 'not-necessary';

	            if( ( $the_post_api_key == 'google_analytics' || $the_post_api_key == 'my_agile_pixel_ga' ) &&
	                isset( $the_settings ) &&
	                isset( $the_settings['cmode_v2_implementation_type'] ) &&
	                $the_settings['cmode_v2_implementation_type'] == 'native' &&
	                isset( $the_settings['cmode_v2_forced_off_ga4_advanced'] ) &&
	                $the_settings['cmode_v2_forced_off_ga4_advanced'] )
	            {
	                $is_necessary = 'not-necessary';
	            }

	            if( in_array( $the_post_api_key, array( 'youtube', 'vimeo' ), true ) &&
	                isset( $the_settings['video_privacy_mode'] ) &&
	                $the_settings['video_privacy_mode'] == 'block' )
	            {
	                $is_necessary = 'not-necessary';
	            }

	            $_map_js_dependencies = ( isset( $value['post_meta']["_map_js_dependencies"][0] ) ) ? $value['post_meta']["_map_js_dependencies"][0] : '';
	            $_map_js_dependencies_array = json_decode( $_map_js_dependencies, true );

	            if( $the_remote_id && $the_post_api_key )
	            {
	                $cookie_api_key_remote_id_map_active[ $the_post_api_key ] = 'map_cookie_'.$the_remote_id.$cookie_reset_timestamp_rich;

	                if( $is_necessary == 'necessary' )
	                {
	                    $cookie_api_key_not_to_block[] = $the_post_api_key;
	                }

	                if( $the_post_title )
	                {
	                    $this_friendly_elem = array(
	                        'desc'         => $the_post_title,
	                        'is_necessary' => ( $is_necessary ) ? true : false,
	                    );

	                    $cookie_api_key_friendly_name_map[ $the_post_api_key ] = $this_friendly_elem;
	                }
	            }

	            if( $the_post_api_key &&
	                is_array( $_map_js_dependencies_array ) &&
	                !empty( $_map_js_dependencies_array ) )
	            {
	                foreach( $_map_js_dependencies_array as $single_map_js_dependencies )
	                {
	                    if(
	                        isset( $single_map_js_dependencies['type'] ) &&
	                        $single_map_js_dependencies['type'] &&
	                        $single_map_js_dependencies['type'] != '' &&

	                        isset( $single_map_js_dependencies['value'] ) &&
	                        $single_map_js_dependencies['value'] &&
	                        $single_map_js_dependencies['value'] != '' )
	                    {
	                        switch( $single_map_js_dependencies['type'] )
	                        {
	                            case 'js_patterns_src':
	                                $item = array(
	                                    "src"                  => $single_map_js_dependencies['value'],
	                                    "added_src"            => null,
	                                    "negative_src"         => null,
	                                    "key"                  => $the_post_api_key,
	                                    "on_block_add_classes" => 'mapWait',
	                                    "do_forced_delay"      => true,
	                                );

	                                $shield_added_pattern[ 'js_patterns_src' ][] = $item;
	                                break;

	                            case 'js_patterns_code':
	                                $item = array(
	                                    "key"                  => $the_post_api_key,
	                                    "plain_pattern"        => $single_map_js_dependencies['value'],
	                                    "on_block_add_classes" => 'mapWait',
	                                    "do_forced_delay"      => true,
	                                );

	                                $shield_added_pattern[ 'js_patterns_code' ][] = $item;
	                                break;
	                        }
	                    }
	                }
	            }
	        }
	    }

	    if( isset( $the_settings['pa'] ) &&
	        $the_settings['pa'] == 1 &&
	        $the_settings['scan_mode'] == 'learning_mode' )
	    {
	        $cookies_categories_data_detectable = $this->get_cookie_categories_description( 'draft' );
	        $cookie_api_key_remote_id_map_detectable = array();

	        foreach( $cookies_categories_data_detectable as $k => $v )
	        {
	            foreach( $v as $key => $value )
	            {
	                $the_post_title = $value['post_title'];
	                $the_remote_id = $value['remote_id'];
	                $the_post_api_key = isset( $value['post_meta']['_map_api_key'][0] ) ? $value['post_meta']['_map_api_key'][0] : null;
	                $is_necessary = isset( $value['post_meta']['_map_is_necessary'][0] ) ? $value['post_meta']['_map_is_necessary'][0] : 'not-necessary';

	                if( $the_remote_id && $the_post_api_key )
	                {
	                    $cookie_api_key_remote_id_map_detectable[ $the_post_api_key ] = 'map_cookie_'.$the_remote_id.$cookie_reset_timestamp_rich;

	                    if( $the_post_title )
	                    {
	                        $this_friendly_elem = array(
	                            'desc'         => $the_post_title,
	                            'is_necessary' => ( $is_necessary ) ? true : false,
	                        );

	                        $cookie_api_key_friendly_name_map[ $the_post_api_key ] = $this_friendly_elem;
	                    }
	                }
	            }
	        }
	    }
	    else
	    {
	        $cookie_api_key_remote_id_map_detectable = array();
	    }

	    $cookies_categories_data_blocked_without_notification = $this->get_cookie_categories_description( '__blocked' );
	    $cookie_api_key_remote_id_map_blocked_without_notification = array();

	    foreach( $cookies_categories_data_blocked_without_notification as $k => $v )
	    {
	        foreach( $v as $key => $value )
	        {
	            $the_remote_id = $value['remote_id'];
	            $the_post_api_key = isset( $value['post_meta']['_map_api_key'][0] ) ? $value['post_meta']['_map_api_key'][0] : null;

	            if( $the_remote_id && $the_post_api_key )
	            {
	                $cookie_api_key_remote_id_map_blocked_without_notification[ $the_post_api_key ] = 'map_cookie_'.$the_remote_id.$cookie_reset_timestamp_rich;
	            }
	        }
	    }

	    $cookies_categories_data_always_allowed = $this->get_cookie_categories_description( '__always_allowed' );
	    $map_cookies_always_allowed = array();

	    foreach( $cookies_categories_data_always_allowed as $k => $v )
	    {
	        foreach( $v as $key => $value )
	        {
	            $the_remote_id = $value['remote_id'];
	            $the_post_api_key = isset( $value['post_meta']['_map_api_key'][0] ) ? $value['post_meta']['_map_api_key'][0] : null;

	            if( $the_remote_id && $the_post_api_key )
	            {
	                $map_cookies_always_allowed[] = $the_post_api_key;
	            }
	        }
	    }

	    //get base directory, safe mode for multisite
	    $_plugin_url = MyAgilePrivacy::get_plugin_url( __FILE__ );

	    if( MAP_DEV_MODE )
	    {
	        $dev_plugin_url = MyAgilePrivacy::get_plugin_url( MAP_PLUGIN_FILENAME );
	        $map_js_basedirectory = $dev_plugin_url.'/dev/MyAgilePrivacyIabTCF/';
	    }
	    else
	    {
	        if( !( isset( $rconfig['forbid_local_js_caching'] ) && $rconfig['forbid_local_js_caching'] == 1 ) )
	        {
	            $map_js_basedirectory = MyAgilePrivacy::get_base_url_for_cache();
	        }
	        else
	        {
	            $map_js_basedirectory = "https://localcdn.myagileprivacy.com/MyAgilePrivacyIabTCF/";
	        }
	    }

	    $map_lang_code_4char = MyAgilePrivacy::getCurrentLang4Char();
	    $map_lang_code = MyAgilePrivacy::normalizeLocaleTo2Char( $map_lang_code_4char );

	    $cookie_reset_timestamp = ( isset( $the_settings['cookie_reset_timestamp'] ) ) ? $the_settings['cookie_reset_timestamp'] : null;
	    $video_privacy_mode = ( isset( $the_settings['video_privacy_mode'] ) ) ? $the_settings['video_privacy_mode'] : 'anonymous';
	    $enforce_youtube_privacy = ( isset( $the_settings['enforce_youtube_privacy'] ) ) ? intval( $the_settings['enforce_youtube_privacy'] ) : 0;
	    $send_ga4_event_on_consent_change = ( isset( $the_settings['send_ga4_event_on_consent_change'] ) ) ? intval( $the_settings['send_ga4_event_on_consent_change'] ) : 0;

	    // the GA4 consent event is not emitted here
	    if( ! ( function_exists( 'map_stats_enabled' ) && map_stats_enabled() ) )
	    {
	        $send_ga4_event_on_consent_change = 0;
	    }

	    $map_stats_config = function_exists( 'map_call_advanced_get_stats_config' ) ? map_call_advanced_get_stats_config() : array( 'enabled' => false, 'variant' => 'default' );

	    $map_bot_detect = ( function_exists( 'map_bot_detection_on' ) && map_bot_detection_on() );

	    $map_split_config = MyAgilePrivacy::get_split_public_config();

	    // a preview page neither counts nor notifies
	    if( $map_split_config['preview'] )
	    {
	        $map_stats_config['enabled']      = false;
	        $send_ga4_event_on_consent_change = 0;
	    }

	    $enforce_youtube_privacy_v2 = 0;

	    if( $enforce_youtube_privacy &&
	        isset( $rconfig ) &&
	        isset( $rconfig['enforce_youtube_privacy_v2'] ) &&
	        $rconfig['enforce_youtube_privacy_v2'] )
	    {
	        $enforce_youtube_privacy_v2 = 1;
	    }

	    $allow_js_fast_callback = 0;

	    if( isset( $rconfig ) &&
	        isset( $rconfig['allow_js_fast_callback'] ) &&
	        $rconfig['allow_js_fast_callback'] )
	    {
	        $allow_js_fast_callback = 1;
	    }

	    $cookie_domain = MyAgilePrivacy::get_applied_cookie_domain( $rconfig );


	    $mapx_ga4 = 0;

	    if( !( $rconfig &&
	        isset( $rconfig['disable_mapx_header_check'] ) &&
	        $rconfig['disable_mapx_header_check'] == 1 )
	    )
	    {
	        if( !function_exists( 'is_plugin_active' ) )
	        {
	            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	        }

	        if( is_plugin_active( 'myagilepixel/myagilepixel.php' ) )
	        {
	            if( defined( 'MAPX_PLUGIN_SETTINGS_FIELD' ) && MAPX_PLUGIN_SETTINGS_FIELD )
	            {
	                $mapx_options = MyAgilePrivacy::get_option( MAPX_PLUGIN_SETTINGS_FIELD, null );
	                if(
	                    isset( $mapx_options ) &&
	                    isset( $mapx_options['general_plugin_active'] ) &&
	                    $mapx_options['general_plugin_active'] &&
	                    isset( $mapx_options['ganalytics_enable'] ) &&
	                    $mapx_options['ganalytics_enable'] == 1
	                )
	                {
	                    $mapx_ga4 = 1;
	                }
	            }
	        }
	    }

	    $blocks = array(
	        'inline'  => array(),
	        'enqueue' => array(),
	    );

	    $head_script = '';
	    $map_shield_tag = '';
	    $map_shield_end = 0;
	    $map_shield_index = null;

	    if( isset( $the_settings['pa'] ) &&
	        $the_settings['pa'] == 1 )
	    {
	        if( $enforce_youtube_privacy && !$enforce_youtube_privacy_v2 )
	        {
	            $the_script = plugin_dir_url(__FILE__).'js/youtube_enforced_privacy.js';

	            $head_script .= '<script class="map_advanced_shield_youtube_enforced_privacy" type="text/javascript" src="'.esc_url( $the_script ).'" '.MAP_INLINE_SCRIPT_EXTRA_ATTRS.'></script>'.PHP_EOL;

	            $blocks['enqueue'][] = $the_script;
	        }

	        if( !( isset( $the_settings['scan_mode'] ) && $the_settings['scan_mode'] == 'turned_off' ) )
	        {
	            if( MAP_DEV_MODE )
	            {
	                $the_script = plugin_dir_url(__FILE__).'../dev/dev.cookie-shield.js';

	                $map_shield_tag = '<script class="map_advanced_shield" type="text/javascript" src="'.esc_url( $the_script ).'" '.MAP_INLINE_SCRIPT_EXTRA_ATTRS.'></script>'.PHP_EOL;
	                $head_script .= $map_shield_tag;
	                $map_shield_end = strlen( $head_script );

	                $map_shield_index = count( $blocks['enqueue'] );
	                $blocks['enqueue'][] = $the_script;

	                $js_shield_url = $the_script;
	            }
	            else
	            {
	                $local_file_exists = false;
	                $base_ref = "";
	                $local_caching_allowed = !( isset( $rconfig['forbid_local_js_caching'] ) && $rconfig['forbid_local_js_caching'] == 1 );

	                if( $local_caching_allowed )
	                {
	                    $local_file_exists = MyAgilePrivacy::cached_file_exists( 'cookie-shield.js' );

	                    if( $local_file_exists )
	                    {
	                        $base_ref = MyAgilePrivacy::get_base_url_for_cache();
	                    }
	                }

	                if( !$local_file_exists )
	                {
	                    $base_ref = "https://localcdn.myagileprivacy.com/";

	                    // a sync cannot bring back a file that is not allowed locally, and a raised flag needs no write
	                    if( $local_caching_allowed &&
	                        !MyAgilePrivacy::get_option( MAP_PLUGIN_DO_SYNC_NOW, 0 ) &&
	                        !get_transient( 'map_sync_pause' ) &&
	                        !get_transient( 'map_download_brake' ) )
	                    {
	                        MyAgilePrivacy::update_option( MAP_PLUGIN_DO_SYNC_NOW, 1 );
	                    }
	                }

	                $shield_test_mode = ( isset( $rconfig ) &&
	                    isset( $rconfig['js_shield_test_mode'] ) &&
	                    $rconfig['js_shield_test_mode'] == 1 );

	                $cdn_pack = MyAgilePrivacy::get_cdn_pack();

	                $cdn_pack_used = !$shield_test_mode &&
	                    MyAgilePrivacy::cdn_pack_shield_wins( $cdn_pack, MyAgilePrivacy::get_option( MAP_MANIFEST_ASSOC, null ), $local_caching_allowed );

	                if( !$local_file_exists && !$cdn_pack_used && isset( $base_ref ) && !(
	                    isset( $rconfig ) &&
	                    isset( $rconfig['prevent_preconnect_prefetch'] ) &&
	                    $rconfig['prevent_preconnect_prefetch'] == 1 ) )
	                {
	                    $head_script .= '<link rel="preconnect" href="'.esc_url( $base_ref ).'" crossorigin />'.PHP_EOL.'<link rel="dns-prefetch" href="'.esc_url( $base_ref ).'" />'.PHP_EOL;
	                }

	                if( $shield_test_mode )
	                {
	                    $the_script = $base_ref.'test.cookie-shield.js';

	                    $map_shield_tag = '<script class="map_advanced_shield" type="text/javascript" src="'.esc_url( $the_script ).'" '.MAP_INLINE_SCRIPT_EXTRA_ATTRS.'></script>'.PHP_EOL;
	                    $head_script .= $map_shield_tag;
	                    $map_shield_end = strlen( $head_script );

	                    $map_shield_index = count( $blocks['enqueue'] );
	                    $blocks['enqueue'][] = $the_script;

	                    $js_shield_url = $the_script;
	                }
	                else
	                {
	                    $script_filename = 'cookie-shield.js';

	                    if( $cdn_pack_used )
	                    {
	                        $base_ref = MyAgilePrivacy::get_cdn_pack_base_url();
	                        $script_filename = 'cookie-shield.js?v='.$cdn_pack['version'].'-'.$cdn_pack['build'];

	                        $the_script = $base_ref.$script_filename;
	                        $enqueue_item = $the_script;
	                    }
	                    else
	                    {
	                        $asset = $this->map_asset_version( $manifest_assoc, $script_filename, $local_caching_allowed );

	                        $the_script = $base_ref.$asset['filename'].( '' !== $asset['version'] ? '?v='.$asset['version'] : '' );
	                        $enqueue_item = ( '' !== $asset['version'] ) ? array( 'src' => $base_ref.$asset['filename'], 'ver' => $asset['version'] ) : $the_script;
	                    }

	                    $map_shield_tag = '<script class="map_advanced_shield" type="text/javascript" src="'.esc_url( $the_script ).'" '.MAP_INLINE_SCRIPT_EXTRA_ATTRS.'></script>'.PHP_EOL;
	                    $head_script .= $map_shield_tag;
	                    $map_shield_end = strlen( $head_script );

	                    $map_shield_index = count( $blocks['enqueue'] );
	                    $blocks['enqueue'][] = $enqueue_item;

	                    $js_shield_url = $the_script;
	                }
	            }
	        }

	        if( $iab_tcf_context )
	        {
	            if( MAP_DEV_MODE )
	            {
	                $the_script = plugin_dir_url(__FILE__).'../dev/MyAgilePrivacyIabTCF/dev.MyAgilePrivacyIabTCF.js';

	                $head_script .= '<script class="map_iab_tcf" type="text/javascript" src="'.esc_url( $the_script ).'" '.MAP_INLINE_SCRIPT_EXTRA_ATTRS.'></script>'.PHP_EOL;

	                $blocks['enqueue'][] = $the_script;

	                $iab_tcf_script_url = $the_script;
	            }
	            else
	            {
	                $local_file_exists = false;
	                $base_ref = "";
	                $iab_local_caching_allowed = !( isset( $rconfig['forbid_local_js_caching'] ) && $rconfig['forbid_local_js_caching'] == 1 );

	                if( $iab_local_caching_allowed )
	                {
	                    $local_file_exists = MyAgilePrivacy::cached_file_exists( 'MyAgilePrivacyIabTCF.js' );

	                    if( $local_file_exists )
	                    {
	                        $base_ref = MyAgilePrivacy::get_base_url_for_cache();
	                    }
	                }

	                if( !$local_file_exists )
	                {
	                    $base_ref = "https://localcdn.myagileprivacy.com/MyAgilePrivacyIabTCF/";
	                }

	                $asset = $this->map_asset_version( $manifest_assoc, 'MyAgilePrivacyIabTCF.js', $iab_local_caching_allowed );

	                $the_script = $base_ref.$asset['filename'].( '' !== $asset['version'] ? '?v='.$asset['version'] : '' );

	                $head_script .= '<script class="map_iab_tcf" type="text/javascript" src="'.esc_url( $the_script ).'" '.MAP_INLINE_SCRIPT_EXTRA_ATTRS.'></script>'.PHP_EOL;

	                $blocks['enqueue'][] = ( '' !== $asset['version'] ) ? array( 'src' => $base_ref.$asset['filename'], 'ver' => $asset['version'] ) : $the_script;

	                $iab_tcf_script_url = $the_script;
	            }
	        }
	    }

	    $base_config_script = '';

	    $early_gcmode = true;

	    if( isset( $rconfig ) &&
	        isset( $rconfig['disable_early_gcmode'] ) &&
	        $rconfig['disable_early_gcmode'] == 1 )
	    {
	        $early_gcmode = false;
	    }

	    $iab_vendor_count = 0;

	    if( defined( 'MAP_IAB_VENDOR_COUNT' ) && MAP_IAB_VENDOR_COUNT )
	    {
	    	$iab_vendor_count = MAP_IAB_VENDOR_COUNT;
	    }

	    //compute geo values injected into the cached HTML config
	    $geo_enabled      = false;
	    $geo_sede_strong  = false;
	    $geo_force_optout = array();

	    if( function_exists( 'map_geo_enabled' ) && map_geo_enabled() )
	    {
	    	$geo_enabled = true;

	    	$geo_available_countries = MyAgilePrivacy::get_option( MAP_PLUGIN_COUNTRIES, array() );
	    	$geo_gdpr_like_list = ( is_array( $geo_available_countries ) && isset( $geo_available_countries['gdpr_like_list'] ) && is_array( $geo_available_countries['gdpr_like_list'] ) )
	    		? $geo_available_countries['gdpr_like_list']
	    		: array();

	    	$geo_base_location = isset( $the_settings['site_and_policy_settings']['base_location'] )
	    		? strtolower( (string) $the_settings['site_and_policy_settings']['base_location'] )
	    		: '';

	    	if( '' !== $geo_base_location && in_array( $geo_base_location, $geo_gdpr_like_list, true ) )
	    	{
	    		$geo_sede_strong = true;
	    	}

	    	$geo_force_optout_raw = isset( $the_settings['site_and_policy_settings']['geo_force_optout'] )
	    		? $the_settings['site_and_policy_settings']['geo_force_optout']
	    		: array();

	    	if( is_array( $geo_force_optout_raw ) )
	    	{
	    		$geo_force_optout = $geo_force_optout_raw;
	    	}

	    	// Skip when the outcome cannot change.
	    	if( $geo_sede_strong )
	    	{
	    		$geo_has_forced_optout = false;
	    		foreach( $geo_force_optout as $geo_optout_flag )
	    		{
	    			if( $geo_optout_flag )
	    			{
	    				$geo_has_forced_optout = true;
	    				break;
	    			}
	    		}

	    		if( ! $geo_has_forced_optout )
	    		{
	    			$geo_enabled = false;
	    		}
	    	}
	    }

	    $map_full_config = array(
	        'config_origin'                                             => 'myagileprivacy_native',
	        'mapx_ga4'                                                  => $mapx_ga4,
	        'map_wl'                                                    => $map_wl_b,
	        'map_js_basedirectory'                                      => $map_js_basedirectory,
	        'map_lang_code'                                             => $map_lang_code,
	        'cookie_reset_timestamp'                                    => $cookie_reset_timestamp,
	        'cookie_api_key_remote_id_map_active'                       => $cookie_api_key_remote_id_map_active,
	        'cookie_api_key_remote_id_map_detectable'                   => $cookie_api_key_remote_id_map_detectable,
	        'cookie_api_key_remote_id_map_blocked_without_notification' => $cookie_api_key_remote_id_map_blocked_without_notification,
	        'map_cookies_always_allowed'                                => $map_cookies_always_allowed,
	        'cookie_api_key_friendly_name_map'                          => $cookie_api_key_friendly_name_map,
	        'cookie_api_key_not_to_block'                               => null,
	        'enforce_youtube_privacy'                                   => $enforce_youtube_privacy,
	        'enforce_youtube_privacy_v2'                                => $enforce_youtube_privacy_v2,
	        'video_advanced_privacy'                                    => 1,
	        'video_privacy_mode'                                        => $video_privacy_mode,
	        'img_srcset_restore'                                        => 1,
	        'js_shield_url'                                             => $js_shield_url,
	        'main_frontend_js'                                          => $main_frontend_js,
	        'frontend_css'                                              => $frontend_css,
	        'load_iab_tcf'                                              => false,
	        'iab_tcf_script_url'                                        => null,
	        'iab_enable_advertiser_consent_mode'                        => null,
	        'enable_microsoft_cmode'                                    => null,
	        'cmode_microsoft_default_consent_obj'                       => null,
	        'enable_clarity_cmode'                                      => null,
	        'cmode_clarity_default_consent_obj'                         => null,
	        'enable_cmode_v2'                                           => null,
	        'cmode_v2_implementation_type'                              => null,
	        'enable_cmode_url_passthrough'                              => null,
	        'cmode_v2_forced_off_ga4_advanced'                          => null,
	        'cmode_v2_default_consent_obj'                              => null,
	        'cmode_v2_js_on_error'                                      => null,
	        'shield_added_pattern'                                      => $shield_added_pattern,
	        'early_gcmode'                                              => $early_gcmode,
	        'frontend_regulation'                                       => $frontend_regulation,
	        'geo_enabled'                                               => $geo_enabled,
	        'geo_sede_strong'                                           => $geo_sede_strong,
	        'geo_force_optout'                                          => $geo_force_optout,
	        'send_ga4_event_on_consent_change'                          => $send_ga4_event_on_consent_change,
	        'stats_enabled'                                             => $map_stats_config['enabled'],
	        'bot_detect'                                                => $map_bot_detect ? 1 : 0,
	        'banner_variant_id'                                         => $map_stats_config['variant'],
	        'allow_js_fast_callback'                                    => $allow_js_fast_callback,
	        'cookie_domain'                                    			=> $cookie_domain,
	        'iab_vendor_count'											=> $iab_vendor_count,
	        'split_test'												=> $map_split_config,
	    );

	    if( null !== $cookie_domain && MyAgilePrivacy::cookie_cleanup_allowed() )
	    {
	        $map_full_config['cookie_domain_tracked'] = true;
	    }

	    if( $iab_tcf_context )
	    {
	        $map_full_config['load_iab_tcf'] = true;
	        $map_full_config['iab_tcf_script_url'] = $iab_tcf_script_url;
	        $map_full_config['iab_enable_advertiser_consent_mode'] = isset( $the_settings['iab_enable_advertiser_consent_mode'] ) ? (bool) $the_settings['iab_enable_advertiser_consent_mode'] : true;

	        if( !empty( $cookie_domain ) )
	        {
	            $plugin_url  = plugin_dir_url( dirname( __FILE__ ) );
	            $parsed      = parse_url( $plugin_url );
	            if( is_array( $parsed ) )
	            {
	                $path        = isset( $parsed['path'] )   ? $parsed['path']   : '/';
	                $bridge_base = '//' . $cookie_domain . $path;
	                $map_full_config['iab_bridge_url'] = $bridge_base . 'api/iab-bridge.php';
	            }
	        }
	    }

	    if( isset( $the_settings ) &&
	        isset( $the_settings['enable_microsoft_cmode'] ) && $the_settings['enable_microsoft_cmode']
	    )
	    {
	        $map_full_config['enable_microsoft_cmode'] = true;
	        $map_full_config['cmode_microsoft_default_consent_obj'] = array(
	            'microsoft_ad_storage' => $the_settings['microsoft_consent_ad_storage'],
	        );
	    }

	    if( isset( $the_settings ) &&
	        isset( $the_settings['enable_clarity_cmode'] ) && $the_settings['enable_clarity_cmode']
	    )
	    {
	        $map_full_config['enable_clarity_cmode'] = true;
	        $map_full_config['cmode_clarity_default_consent_obj'] = array(
	            'clarity_ad_storage'        => $the_settings['clarity_consent_ad_storage'],
	            'clarity_analytics_storage' => $the_settings['clarity_consent_analytics_storage'],
	        );
	    }

	    if( isset( $the_settings ) &&
	        isset( $the_settings['enable_cmode_v2'] ) && $the_settings['enable_cmode_v2'] &&
	        !( isset( $the_settings['bypass_cmode_enable'] ) && $the_settings['bypass_cmode_enable'] == true )
	    )
	    {
	        $map_full_config['enable_cmode_v2'] = true;
	        $map_full_config['cmode_v2_implementation_type'] = $the_settings['cmode_v2_implementation_type'];
	        $map_full_config['enable_cmode_url_passthrough'] = $the_settings['enable_cmode_url_passthrough'];

	        if( $map_full_config['cmode_v2_implementation_type'] == 'native' )
	        {
	            $map_full_config['cmode_v2_default_consent_obj'] = array(
	                'ad_storage'          => $the_settings['cmode_v2_gtag_ad_storage'],
	                'ad_user_data'        => $the_settings['cmode_v2_gtag_ad_user_data'],
	                'ad_personalization'  => $the_settings['cmode_v2_gtag_ad_personalization'],
	                'analytics_storage'   => $the_settings['cmode_v2_gtag_analytics_storage'],
	            );
	        }

	        $cmode_v2_forced_off_ga4_advanced = false;

	        if( $map_full_config['cmode_v2_implementation_type'] == 'native' &&
	            isset( $the_settings['cmode_v2_forced_off_ga4_advanced'] ) )
	        {
	            $cmode_v2_forced_off_ga4_advanced = $the_settings['cmode_v2_forced_off_ga4_advanced'];
	        }

	        $map_full_config['cmode_v2_forced_off_ga4_advanced'] = $cmode_v2_forced_off_ga4_advanced;

	        if( $map_full_config['cmode_v2_forced_off_ga4_advanced'] == false )
	        {
	            if( $mapx_ga4 == 0 )
	            {
	                $cookie_api_key_not_to_block[] = 'google_analytics';
	            }
	        }
	    }

	    $map_scan_runtime = MyAgilePrivacy::get_scan_runtime();

	    if( isset( $map_scan_runtime['cmode_v2_js_on_error'] ) )
	    {
	        $map_full_config['cmode_v2_js_on_error'] = $map_scan_runtime['cmode_v2_js_on_error'];
	    }

	    $map_full_config['cookie_api_key_not_to_block'] = $cookie_api_key_not_to_block;

	    $map_full_config_json = MyAgilePrivacy::jsonEncodeSafe( $map_full_config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );

	    $base_config_script .= 'var map_full_config='.( ( false !== $map_full_config_json ) ? $map_full_config_json : '{}' ).';'.PHP_EOL;

	    if( $map_full_config['enable_clarity_cmode'] )
	    {
	        $base_config_script .= 'window.clarity = window.clarity || function() {(window.clarity.q = window.clarity.q || []).push(arguments);};'.PHP_EOL;
	    }

	    if( $map_full_config['enable_microsoft_cmode'] )
	    {
	        $base_config_script .= 'window.uetq = window.uetq || [];'.PHP_EOL;
	    }

	    if( $map_full_config['enable_cmode_v2'] )
	    {
	        if( $iab_tcf_context )
	        {
	            $base_config_script .= "window['gtag_enable_tcf_support'] = true;".PHP_EOL;
	        }

	        if( $map_full_config['cmode_v2_implementation_type'] == 'native' )
	        {
	            if( $map_full_config['cmode_v2_forced_off_ga4_advanced'] == false )
	            {
	                if( $mapx_ga4 == 0 )
	                {
	                    $base_config_script .= 'var alt_mpx_settings='.json_encode( array(
	                        'caller'                 => 'MAP',
	                        'map_ga_consent_checker' => true,
	                    ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ).';'.PHP_EOL;
	                    $base_config_script .= "setTimeout( function() {
	                        if( typeof window.MyAgilePixelProxyBeacon !== 'undefined' ) window.MyAgilePixelProxyBeacon( alt_mpx_settings );
	                    }, 500 );".PHP_EOL;
	                }
	            }

	            if( !$map_full_config['early_gcmode'] )
	            {
	                $base_config_script .= 'window.dataLayer = window.dataLayer || [];'.PHP_EOL;
	                $base_config_script .= 'function gtag(){dataLayer.push(arguments);}'.PHP_EOL;
	                $base_config_script .= "gtag('set', 'developer_id.dY2ZhMm', true);".PHP_EOL;

	                if( $map_full_config['enable_cmode_url_passthrough'] )
	                {
	                    $base_config_script .= "gtag('set', 'url_passthrough', true);".PHP_EOL;
	                }
	            }
	        }

	        if( $map_full_config['cmode_v2_implementation_type'] == 'gtm' )
	        {
	            $base_config_script .= 'window.dataLayer = window.dataLayer || [];'.PHP_EOL;
	            $base_config_script .= 'function gtag(){dataLayer.push(arguments);}'.PHP_EOL;
	            $base_config_script .= "gtag('set', 'developer_id.dY2ZhMm', true);".PHP_EOL;
	            $base_config_script .= 'window.gTagManagerConsentListeners = window.gTagManagerConsentListeners || [];'.PHP_EOL;

	            //stub for gTagManagerConsentListeners
				$base_config_script .= 'window.MAP = window.MAP || {};' . PHP_EOL;
				$base_config_script .= 'if( typeof window.MAP.googleTagManagerConsentListener !== "function" ) {' . PHP_EOL;
				$base_config_script .= '    window.MAP.googleTagManagerConsentListener = function(cb) {' . PHP_EOL;
				$base_config_script .= '        window.gTagManagerConsentListeners = window.gTagManagerConsentListeners || [];' . PHP_EOL;
				$base_config_script .= '        window.gTagManagerConsentListeners.push(cb);' . PHP_EOL;
				$base_config_script .= '    };' . PHP_EOL;
				$base_config_script .= '}' . PHP_EOL;
	        }
	    }

	    $blocks['inline'][] = $base_config_script;

	    $map_early = $this->early_gcmode_inline( $map_full_config, $map_shield_tag );

	    if( $map_early['tag'] !== '' )
	    {
	        if( $map_early['after_shield'] )
	        {
	            $head_script = substr_replace( $head_script, $map_early['tag'], $map_shield_end, 0 );
	            $blocks['shield_index'] = $map_shield_index;
	            $blocks['after_shield'] = $map_early['code'];
	        }
	        else
	        {
	            $head_script = $map_early['tag'].$head_script;
	            $blocks['inline'][] = $map_early['code'];
	        }
	    }

	    $start_config_script = '<script class="map_advanced_shield" type="text/javascript" '.MAP_INLINE_SCRIPT_EXTRA_ATTRS.'>'.PHP_EOL.$base_config_script.PHP_EOL.'</script>'.PHP_EOL;

	    // Load the advanced JS bundle when available.
	    $advanced_bundle_tag = '';
	    if( function_exists( 'map_is_advanced_active' ) && map_is_advanced_active() &&
	        function_exists( 'map_call_advanced_get_bundle_url' ) )
	    {
	    	$bundle_url = map_call_advanced_get_bundle_url();
	    	if( '' !== $bundle_url )
	    	{
	    		$bundle_version       = function_exists( 'map_call_advanced_get_bundle_version' ) ? map_call_advanced_get_bundle_version() : '';
	    		$bundle_version       = ( is_string( $bundle_version ) && '' !== $bundle_version ) ? $bundle_version . '-a' : '';
	    		$bundle_version_param = ( '' !== $bundle_version ) ? '?ver=' . $bundle_version : $version_param;

	    		$advanced_bundle_tag = '<script class="map_advanced_shield map_advanced_bundle" type="text/javascript" src="'.esc_url( $bundle_url.$bundle_version_param ).'" '.MAP_INLINE_SCRIPT_EXTRA_ATTRS.'></script>'.PHP_EOL;

	    		$blocks['enqueue'][] = ( '' !== $bundle_version ) ? array( 'src' => $bundle_url, 'ver' => $bundle_version ) : $bundle_url;
	    	}
	    }

	    if( $block_mode )
	    {
	        return $blocks;
	    }
	    else
	    {
	        return $start_config_script.$advanced_bundle_tag.$head_script;
	    }
	}

	//f. early consent default tag, placed after the shield tag or in its place
	private function early_gcmode_inline( $map_full_config, $shield_tag )
	{
		$out = array( 'tag' => '', 'code' => '', 'after_shield' => false );

		if( !is_array( $map_full_config ) ||
			empty( $map_full_config['early_gcmode'] ) ||
			empty( $map_full_config['enable_cmode_v2'] ) ||
			!isset( $map_full_config['cmode_v2_implementation_type'] ) ||
			$map_full_config['cmode_v2_implementation_type'] !== 'native' )
		{
			return $out;
		}

		$code = implode( "\n", array(
			'var early_starting_gconsent=early_starting_gconsent;',
			'( function() {',
			'	var c = ( typeof map_full_config !== "undefined" && map_full_config ) ? map_full_config : null;',
			'	if( !c || !c.early_gcmode || !c.enable_cmode_v2 || c.cmode_v2_implementation_type != "native" ) return;',
			'	if( typeof MAP_JSCOOKIE_SHIELD !== "undefined" && MAP_JSCOOKIE_SHIELD ) return;',
			'	if( window.early_starting_gconsent ) return;',
			'	window.dataLayer = window.dataLayer || [];',
			'	if( typeof window.gtag !== "function" ) { window.gtag = function(){ window.dataLayer.push( arguments ); }; }',
			'	window.gtag( "set", "developer_id.dY2ZhMm", true );',
			'	if( c.enable_cmode_url_passthrough ) { window.gtag( "set", "url_passthrough", true ); }',
			'	window.early_starting_gconsent = c.cmode_v2_default_consent_obj;',
			'	if( window.early_starting_gconsent ) { try { window.gtag( "consent", "default", Object.assign( {}, window.early_starting_gconsent ) ); } catch( e ) {} }',
			'} )();'
		) );

		$out['code'] = $code;
		$out['tag'] = '<script class="map_early_gcmode" type="text/javascript" '.MAP_INLINE_SCRIPT_EXTRA_ATTRS.'>'."\n".$code."\n".'</script>'.PHP_EOL;
		$out['after_shield'] = ( is_string( $shield_tag ) && $shield_tag !== '' );

		return $out;
	}

	//f. code to attach after the queued shield, null when the blocks do not carry it
	private function early_gcmode_block_target( $blocks, $enqueued )
	{
		if( !is_array( $blocks ) || !is_array( $enqueued ) ||
			!isset( $blocks['shield_index'], $blocks['after_shield'], $blocks['enqueue'] ) ||
			!is_array( $blocks['enqueue'] ) )
		{
			return null;
		}

		$index = $blocks['shield_index'];
		$code  = $blocks['after_shield'];

		if( !is_int( $index ) || $index < 0 || $index >= count( $blocks['enqueue'] ) || !in_array( $index, $enqueued, true ) )
		{
			return null;
		}

		if( !is_string( $code ) || trim( $code ) === '' )
		{
			return null;
		}

		return array( 'handle' => $this->plugin_name.'_enqueue_'.$index, 'code' => $code );
	}

	/**
	 * Internal functin for detected keys save
	 *
	 */
	//f. file name and version for a cached asset url
	private function map_asset_version( $manifest_assoc, $script_filename, $local_caching_allowed )
	{
		$version  = ( defined( 'MAP_PLUGIN_VERSION' ) && is_string( MAP_PLUGIN_VERSION ) ) ? MAP_PLUGIN_VERSION : '';
		$filename = $script_filename;

		if( $local_caching_allowed &&
			is_array( $manifest_assoc ) &&
			isset( $manifest_assoc['files'] ) &&
			is_array( $manifest_assoc['files'] ) &&
			isset( $manifest_assoc['files'][ $script_filename ] ) &&
			is_array( $manifest_assoc['files'][ $script_filename ] ) &&
			isset( $manifest_assoc['files'][ $script_filename ]['filename'], $manifest_assoc['files'][ $script_filename ]['version'] ) )
		{
			$m_filename = $manifest_assoc['files'][ $script_filename ]['filename'];
			$m_version  = $manifest_assoc['files'][ $script_filename ]['version'];

			if( is_string( $m_filename ) &&
				( is_string( $m_version ) || is_int( $m_version ) ) &&
				preg_match( '/^[A-Za-z0-9._-]+\z/', $m_filename ) &&
				preg_match( '/^[A-Za-z0-9._-]+\z/', (string) $m_version ) )
			{
				$filename = $m_filename;
				$version  = (string) $m_version;
			}
		}

		if( '' !== $version && !preg_match( '/^[A-Za-z0-9._-]+\z/', $version ) )
		{
			$version = '';
		}

		return array( 'filename' => $filename, 'version' => $version );
	}

	/**
	 * Whether the site is currently scanning.
	 *
	 * Mirrors the condition the frontend applies before reporting: the same two
	 * sources, combined the same way. Reports that arrive outside this state did
	 * not come from our own code.
	 *
	 * @return bool
	 */
	private function is_scanning_mode()
	{
		$the_settings = MyAgilePrivacy::get_settings();

		// Same two sources the frontend reads, and nothing more: the license
		// state is checked by the callers that depend on it, so adding it here
		// would reject reports the frontend still considers valid.
		if( isset( $the_settings['scan_mode'] ) && $the_settings['scan_mode'] == 'learning_mode' )
		{
			return true;
		}

		$rconfig = MyAgilePrivacy::get_rconfig();

		return ( is_array( $rconfig ) &&
			isset( $rconfig['force_js_learning_mode'] ) &&
			$rconfig['force_js_learning_mode'] == 1 );
	}

	private function internal_save_detected_keys()
	{
		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'start internal_save_detected_keys' );

		// Get settings
		$js_cookie_shield_detected_keys = explode( ',', MyAgilePrivacy::get_option( MAP_PLUGIN_JS_DETECTED_FIELDS, '' ) );

		if( !$js_cookie_shield_detected_keys )
		{
			$js_cookie_shield_detected_keys = array();
		}

		if( isset( $_POST['detectableKeys'] ) && is_string( $_POST['detectableKeys'] ) )
		{
			$detectableKeys = array_slice( explode( ',', $_POST['detectableKeys'], MAP_DETECTED_KEYS_MAX + 1 ), 0, MAP_DETECTED_KEYS_MAX );

			foreach( $detectableKeys as $k => $v )
			{
				$v = sanitize_text_field( $v );
				if( $v != null && $v != 'null' && preg_match( MAP_DETECTED_KEY_PATTERN, $v ) )
				{
					$js_cookie_shield_detected_keys[] = $v;
				}
			}
		}

		if( isset( $_POST['detectedKeys'] ) && is_string( $_POST['detectedKeys'] ) )
		{
			$detectedKeys = array_slice( explode( ',', $_POST['detectedKeys'], MAP_DETECTED_KEYS_MAX + 1 ), 0, MAP_DETECTED_KEYS_MAX );

			foreach( $detectedKeys as $k => $v )
			{
				$v = sanitize_text_field( $v );
				if( $v != null && $v != 'null' && preg_match( MAP_DETECTED_KEY_PATTERN, $v ) )
				{
					$js_cookie_shield_detected_keys[] = $v;
				}
			}
		}

		$js_cookie_shield_detected_keys = array_slice( array_unique( array_filter( $js_cookie_shield_detected_keys ) ), 0, MAP_DETECTED_KEYS_MAX );

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $js_cookie_shield_detected_keys );

		MyAgilePrivacy::update_option( MAP_PLUGIN_JS_DETECTED_FIELDS, implode( ',', $js_cookie_shield_detected_keys ) );

		$currentAndSupportedLanguages = MyAgilePrivacy::getCurrentAndSupportedLanguages();

		$auto_activate_keys = array();

		foreach( $js_cookie_shield_detected_keys as $k => $v )
		{
			if( $v )
			{
				$auto_activate_keys[] = $v;
			}
		}

		$consumed_keys = array();

		foreach( $auto_activate_keys as $k => $v )
		{
			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $v, true );

			if( $v )
			{
				$post_status_to_search = array( 'draft', 'publish' );

				$cc_args = array(
					'posts_per_page'   	=> 	-1,
					'post_type'        	=>	MAP_POST_TYPE_COOKIES,
					'meta_key'         	=> 	'_map_api_key',
					'meta_value'       	=> 	$v,
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

								update_post_meta( $main_post_id, "_map_auto_detected", 1 );

								$consumed_keys[] = $v;

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

		if( !empty( $consumed_keys ) )
		{
			$residual_keys = array_diff( $js_cookie_shield_detected_keys, $consumed_keys );
			MyAgilePrivacy::update_option( MAP_PLUGIN_JS_DETECTED_FIELDS, implode( ',', $residual_keys ) );
		}

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'end internal_save_detected_keys' );

		return true;
	}

	/**
	 * Loading-priority detection report callback.
	 * admin-ajax fallback for the api/api.php detection endpoint.
	 */
	public function map_report_gtm_gateway_callback()
	{
		$expected  = ( defined( 'AUTH_KEY' ) && defined( 'AUTH_SALT' ) ) ? hash_hmac( 'sha256', 'map_api_v1', AUTH_KEY . AUTH_SALT ) : '';
		$map_token = isset( $_POST['map_api_token'] ) ? $_POST['map_api_token'] : '';

		if( empty( $expected ) || ! is_string( $map_token ) || ! hash_equals( $expected, $map_token ) )
		{
			wp_send_json( array( 'success' => false ) );
		}

		// The beacon is emitted only while scanning; see is_scanning_mode().
		if( ! $this->is_scanning_mode() )
		{
			wp_send_json( array( 'success' => false ) );
		}

		$the_settings = MyAgilePrivacy::get_settings();

		if( isset( $the_settings['disable_gtm_gateway_detection'] ) &&
			$the_settings['disable_gtm_gateway_detection'] )
		{
			wp_send_json( array( 'success' => false ) );
		}

		$now  = time();
		$data = MyAgilePrivacy::get_option( MAP_PLUGIN_GTM_GATEWAY_DETECTED, array() );

		if( ! is_array( $data ) )
		{
			$data = array();
		}

		// Rate-limit repeated writes.
		if( isset( $data['last_detected'] ) &&
			( $now - intval( $data['last_detected'] ) ) < HOUR_IN_SECONDS )
		{
			wp_send_json( array( 'success' => true, 'throttled' => true ) );
		}

		$data['first_detected'] = isset( $data['first_detected'] ) ? intval( $data['first_detected'] ) : $now;
		$data['last_detected']  = $now;
		$data['count']          = isset( $data['count'] ) ? intval( $data['count'] ) + 1 : 1;

		MyAgilePrivacy::update_option( MAP_PLUGIN_GTM_GATEWAY_DETECTED, $data );

		wp_send_json( array( 'success' => true ) );
	}

	/**
	 * Missing cookie shield callback function
	 *
	 */
	public function map_missing_cookie_shield_callback()
	{
		$expected  = ( defined( 'AUTH_KEY' ) && defined( 'AUTH_SALT' ) ) ? hash_hmac( 'sha256', 'map_api_v1', AUTH_KEY . AUTH_SALT ) : '';
		$map_token = isset( $_POST['map_api_token'] ) ? $_POST['map_api_token'] : '';

		if( ! empty( $expected ) && is_string( $map_token ) && hash_equals( $expected, $map_token ) )
		{
			// The report is only expected while scanning; see is_scanning_mode().
			if( ! $this->is_scanning_mode() )
			{
				wp_send_json( array( 'success' => false ) );
			}

			$success = false;

			// check form submit
			if( isset( $_POST['action'] ) && $_POST['action'] == 'map_missing_cookie_shield' )
			{
				$the_settings = MyAgilePrivacy::get_settings();

				if( isset( $the_settings['pa'] ) &&
					$the_settings['pa'] == 1 )
				{
					$detected = isset( $_POST['detected'] ) ? intval( $_POST['detected'] ) : 0;

					$scan_runtime = MyAgilePrivacy::get_scan_runtime();

					if( $detected == 0 )
					{
						if( $scan_runtime['missing_cookie_shield'] == false ||
							$scan_runtime['cookie_shield_running'] == true
						)
						{
							MyAgilePrivacy::update_scan_runtime( array(
								'cookie_shield_running'				=>	false,
								'missing_cookie_shield'				=>	true,
								'missing_cookie_shield_timestamp'	=>	strtotime( "now" ),
							) );
						}
					}
					elseif( $detected == 1 )
					{
						if( $scan_runtime['cookie_shield_running'] == false ||
							$scan_runtime['missing_cookie_shield'] == true
						)
						{
							MyAgilePrivacy::update_scan_runtime( array(
								'missing_cookie_shield'				=>	false,
								'cookie_shield_running'				=>	true,
								'cookie_shield_running_timestamp'	=>	strtotime( "now" ),
							) );
						}
					}


				}

				$success = true;
			}

			$answer = array(
				'success'				=>	$success,
			);

			wp_send_json( $answer );
		}

		die();
	}


	/**
	 * Consent Mode Status Check callback
	 *
	 */
	public function map_check_consent_mode_status_callback()
	{
		$expected  = ( defined( 'AUTH_KEY' ) && defined( 'AUTH_SALT' ) ) ? hash_hmac( 'sha256', 'map_api_v1', AUTH_KEY . AUTH_SALT ) : '';
		$map_token = isset( $_POST['map_api_token'] ) ? $_POST['map_api_token'] : '';

		if( ! empty( $expected ) && is_string( $map_token ) && hash_equals( $expected, $map_token ) )
		{
			// The report is only expected while scanning; see is_scanning_mode().
			if( ! $this->is_scanning_mode() )
			{
				wp_send_json( array( 'success' => false ) );
			}

			$the_settings = MyAgilePrivacy::get_settings();

			if( !isset( $the_settings['pa'] ) || $the_settings['pa'] != 1 )
			{
				wp_send_json( array( 'success' => false ) );
			}

			$success = false;

			// check form submit
			if( isset( $_POST['action'] ) && $_POST['action'] == 'map_check_consent_mode_status' )
			{
				$cmode_v2_js_on_error = ( isset( $_POST['is_consent_valid'] ) && intval( $_POST['is_consent_valid'] ) ) ? false : true;
				$error_code           = isset( $_POST['error_code'] ) ? intval( $_POST['error_code'] ) : 0;
				$error_motivation     = ( isset( $_POST['error_motivation'] ) && is_string( $_POST['error_motivation'] ) ) ? sanitize_text_field( $_POST['error_motivation'] ) : '';

				// Free text reaches the license server verbatim; keep it bounded.
				// Counted in characters: cutting on a byte boundary would split a
				// multibyte sequence and leave text that no longer encodes.
				$max_len = defined( 'MAP_PLUGIN_JS_ERROR_TEXT_MAX' ) ? (int) MAP_PLUGIN_JS_ERROR_TEXT_MAX : 255;
				$error_motivation = mb_substr( $error_motivation, 0, $max_len );

				// The value is stored and travels on: drop the characters that could
				// turn it into markup and keep the message otherwise faithful.
				$error_motivation = str_replace( array( '<', '>', '"', "'", '&' ), '', $error_motivation );

				$scan_runtime = MyAgilePrivacy::get_scan_runtime();

				// Write only on an actual change, as the api.php twin already does.
				$has_changed =
					$scan_runtime['cmode_v2_js_on_error']         !== $cmode_v2_js_on_error ||
					$scan_runtime['cmode_v2_js_error_code']       !== $error_code ||
					$scan_runtime['cmode_v2_js_error_motivation'] !== $error_motivation;

				if( !$has_changed )
				{
					wp_send_json( array( 'success' => true ) );
				}

				$runtime_changes = array(
					'cmode_v2_js_on_error'			=>	$cmode_v2_js_on_error,
					'cmode_v2_js_error_code'		=>	$error_code,
					'cmode_v2_js_error_motivation'	=>	$error_motivation,
				);

				if( $scan_runtime['cmode_v2_js_on_error'] && !$cmode_v2_js_on_error )
				{
					$runtime_changes['cmode_v2_js_on_error_first_relevation'] = 0;
				}

				if( !$scan_runtime['cmode_v2_js_on_error'] && $cmode_v2_js_on_error )
				{
					$runtime_changes['cmode_v2_js_on_error_first_relevation'] = strtotime( "now" );
				}

				MyAgilePrivacy::update_scan_runtime( $runtime_changes );

				$success = true;
			}

			$answer = array(
				'success'				=>	$success,
			);

			wp_send_json( $answer );
		}

		die();
	}


	/**
	 * Save detected keys callback function
	 *
	 */
	public function map_save_detected_keys_callback()
	{
		$expected  = ( defined( 'AUTH_KEY' ) && defined( 'AUTH_SALT' ) ) ? hash_hmac( 'sha256', 'map_api_v1', AUTH_KEY . AUTH_SALT ) : '';
		$map_token = isset( $_POST['map_api_token'] ) ? $_POST['map_api_token'] : '';

		if( ! empty( $expected ) && is_string( $map_token ) && hash_equals( $expected, $map_token ) )
		{
			$success = false;

			// check form submit
			if( isset( $_POST['action'] ) && $_POST['action'] == 'map_save_detected_keys' )
			{
				$the_settings = MyAgilePrivacy::get_settings();

				// Reports arrive only while scanning; outside that state they are
				// not ours, and this path publishes cookie declarations.
				if( isset( $the_settings['pa'] ) &&
					$the_settings['pa'] == 1 &&
					$this->is_scanning_mode() )
				{
					$success = $this->internal_save_detected_keys();
				}
			}

			$answer = array(
				'success'				=>	$success,
			);

			wp_send_json( $answer );
		}

		die();
	}




	/**
	 * map_callback
	 * @access   public
	*/
	public function map_callback( $output, $phase = PHP_OUTPUT_HANDLER_FINAL )
	{
		// discarded output
		if( ( $phase & PHP_OUTPUT_HANDLER_CLEAN ) || !is_string( $output ) || !$this->map_is_html_response() )
		{
			$this->map_restore_postdata();
			return $output;
		}

		$scan_done_before = $this->scan_done;
		$reason = null;
		$info = '';
		$result = $output;
		$this->scan_saved_output = null;

		if( strlen( $output ) > MAP_SHD_MAX_FILE_SIZE )
		{
			$reason = 'dimensione';
			$info = (string) strlen( $output );
		}
		else
		{
			$this->map_html_scan_limits( strlen( $output ) );

			try
			{
				$result = $this->map_callback_run( $output );
			}
			catch( MAP_Simple_Html_Dom_Abort $e )
			{
				$reason = $e->reason;
				$info = $e->info;
			}
			catch( Exception $e )
			{
				$reason = 'errore';
				$info = get_class( $e );
			}
			catch( Error $e )
			{
				$reason = 'errore';
				$info = get_class( $e );
			}

			// the exception keeps its frames alive
			unset( $e );

			MAP_Simple_Html_Dom::$deadline = 0;
			MAP_Simple_Html_Dom::$mem_limit = 0;
		}

		if( null === $reason )
		{
			$this->scan_saved_output = null;
			return $result;
		}

		if( is_object( $this->scan_dom ) )
		{
			$this->scan_dom->clear();
		}

		$this->scan_dom = null;
		$result = null;
		$this->scan_done = $scan_done_before;
		$this->scan_output = $this->scan_config;

		if( function_exists( 'gc_mem_caches' ) )
		{
			gc_mem_caches();
		}

		$this->map_restore_postdata();

		// the page was already saved: the error came after it
		if( is_string( $this->scan_saved_output ) )
		{
			$saved = $this->scan_saved_output;
			$this->scan_saved_output = null;

			if( !$this->shutdown_passed )
			{
				$this->pending_html_scan_state = array(
					'reason' => $reason,
					'info'   => (string) substr( (string) $info, 0, 100 ),
				);
			}

			return $saved;
		}

		return $this->map_html_fallback( $output, $phase, $reason, $info );
	}

	/**
	 * Restores the post data in compatibility mode, as the full callback does.
	 */
	private function map_restore_postdata()
	{
		if( is_array( $this->saved_settings ) && !empty( $this->saved_settings['scanner_compatibility_mode'] ) )
		{
			global $post;
			setup_postdata( $this->saved_post );
		}
	}

	/**
	 * Whether the response being buffered is an HTML page.
	 */
	private function map_is_html_response()
	{
		if( !function_exists( 'headers_list' ) )
		{
			return true;
		}

		foreach( headers_list() as $header )
		{
			if( is_string( $header ) && strncasecmp( $header, 'content-type:', 13 ) === 0 )
			{
				$type = strtolower( trim( substr( $header, 13 ) ) );

				return ( strpos( $type, 'text/html' ) === 0 || strpos( $type, 'application/xhtml+xml' ) === 0 );
			}
		}

		return true;
	}

	/**
	 * Sets the time and memory limits of the HTML parser for this page.
	 */
	private function map_html_scan_limits( $page_len )
	{
		$budget = MAP_SHD_TIME_BUDGET;
		$max_execution_time = function_exists( 'ini_get' ) ? (int) ini_get( 'max_execution_time' ) : 0;

		if( $max_execution_time > 0 && $max_execution_time < 30 )
		{
			$budget = max( 2, $max_execution_time / 3 );
		}

		MAP_Simple_Html_Dom::$deadline = microtime( true ) + $budget;
		MAP_Simple_Html_Dom::$mem_limit = $this->map_memory_limit_bytes();
		MAP_Simple_Html_Dom::$page_len = $page_len;
		MAP_Simple_Html_Dom::$depth = 0;
	}

	/**
	 * The PHP memory limit in bytes, 0 when there is none or it cannot be read.
	 */
	private function map_memory_limit_bytes()
	{
		if( !function_exists( 'ini_get' ) )
		{
			return 0;
		}

		$value = ini_get( 'memory_limit' );

		if( !is_string( $value ) || trim( $value ) === '' )
		{
			return 0;
		}

		if( function_exists( 'wp_convert_hr_to_bytes' ) )
		{
			$bytes = (float) wp_convert_hr_to_bytes( $value );
		}
		else
		{
			$value = strtolower( trim( $value ) );
			$bytes = (float) $value;
			$unit = substr( $value, -1 );

			if( $unit === 'g' ) { $bytes *= 1073741824; }
			elseif( $unit === 'm' ) { $bytes *= 1048576; }
			elseif( $unit === 'k' ) { $bytes *= 1024; }
		}

		return ( $bytes > 0 ) ? $bytes : 0;
	}

	/**
	 * Page left unparsed: adds the head script as plain text and records why.
	 */
	private function map_html_fallback( $page, $phase, $reason, $info )
	{
		$final = (bool) ( $phase & PHP_OUTPUT_HANDLER_FINAL );
		$record = $reason;
		$out = $page;

		if( is_string( $this->head_script ) && $this->head_script !== '' && !$this->html_head_done )
		{
			$position = $this->map_head_insert_position( $page );

			if( $position >= 0 )
			{
				$limit = $this->map_memory_limit_bytes();
				$needed = strlen( $page ) + strlen( $this->head_script ) + 2097152;

				if( $limit > 0 && memory_get_usage( true ) + $needed > $limit )
				{
					$record = 'memoria';
				}
				else
				{
					$out = substr_replace( $page, $this->head_script, $position, 0 );
					$this->html_head_done = true;
				}
			}
			elseif( $position === -2 )
			{
				$this->html_head_done = true;
			}
			elseif( $final )
			{
				$record = 'senza-head';
			}
		}

		if( $record !== $reason )
		{
			$info = trim( $reason . ' ' . (string) $info );
		}

		if( !$this->shutdown_passed )
		{
			$this->pending_html_scan_state = array(
				'reason' => $record,
				'info'   => (string) substr( (string) $info, 0, 100 ),
			);
		}

		return $out;
	}

	/**
	 * Where the head script goes: after the head start tag, -1 when there is no usable head, -2 when the shield is already there.
	 */
	private function map_head_insert_position( $page )
	{
		$length = strlen( $page );
		$pos = 0;

		while( $pos < $length )
		{
			$lt = strpos( $page, '<', $pos );

			if( false === $lt )
			{
				return -1;
			}

			if( substr_compare( $page, '<!--', $lt, 4 ) === 0 )
			{
				// <!--> and <!---> are complete empty comments
				if( substr( $page, $lt + 4, 1 ) === '>' || substr( $page, $lt + 4, 2 ) === '->' )
				{
					$pos = $lt + ( ( substr( $page, $lt + 4, 1 ) === '>' ) ? 5 : 6 );
					continue;
				}

				$comment_end = strpos( $page, '-->', $lt + 4 );

				if( false === $comment_end )
				{
					return -1;
				}

				$pos = $comment_end + 3;
				continue;
			}

			$name_length = strspn( $page, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-', $lt + 1 );

			if( 0 === $name_length )
			{
				$pos = $lt + 1;
				continue;
			}

			$name = strtolower( substr( $page, $lt + 1, $name_length ) );
			$after = $lt + 1 + $name_length;

			if( $after < $length && strpos( " \t\r\n\f>/", $page[ $after ] ) === false )
			{
				$pos = $after;
				continue;
			}

			if( $name === 'body' )
			{
				return -1;
			}

			$tag_end = $this->map_tag_end( $page, $after, ( $name === 'head' ) ? 4096 : 0 );

			if( $tag_end < 0 )
			{
				return -1;
			}

			if( $name === 'head' )
			{
				$stop = $this->map_find_tag( $page, array( '</head', '<body' ), $tag_end + 1 );

				if( $stop < 0 )
				{
					return -1;
				}

				$present = strpos( $page, 'class="map_advanced_shield"', $tag_end + 1 );

				if( false !== $present && $present < $stop )
				{
					return -2;
				}

				return $tag_end + 1;
			}

			if( $name === 'script' || $name === 'style' )
			{
				$close = $this->map_find_tag( $page, array( '</' . $name ), $tag_end + 1 );

				if( $close < 0 )
				{
					return -1;
				}

				$pos = $close + 2;
				continue;
			}

			$pos = $tag_end + 1;
		}

		return -1;
	}

	/**
	 * Position of the > closing a tag, skipping quoted attribute values; -1 when missing.
	 */
	private function map_tag_end( $page, $from, $max_chars )
	{
		$length = strlen( $page );
		$end = ( $max_chars > 0 ) ? min( $length, $from + $max_chars ) : $length;
		$quote = '';
		$after_equals = false;

		for( $i = $from; $i < $end; $i++ )
		{
			$char = $page[ $i ];

			if( $quote !== '' )
			{
				if( $char === $quote )
				{
					$quote = '';
				}

				continue;
			}

			if( $char === '>' )
			{
				return $i;
			}

			if( ( $char === '"' || $char === "'" ) && $after_equals )
			{
				$quote = $char;
				$after_equals = false;
				continue;
			}

			if( $char === '=' )
			{
				$after_equals = true;
			}
			elseif( strpos( " \t\r\n\f", $char ) === false )
			{
				$after_equals = false;
			}
		}

		return -1;
	}

	/**
	 * First position of one of the lowercase tag openings, case insensitive and followed by a delimiter; -1 when missing.
	 */
	private function map_find_tag( $page, $needles, $from )
	{
		$length = strlen( $page );
		$pos = $from;

		while( $pos < $length )
		{
			$lt = strpos( $page, '<', $pos );

			if( false === $lt )
			{
				return -1;
			}

			foreach( $needles as $needle )
			{
				$needle_length = strlen( $needle );

				if( strtolower( substr( $page, $lt, $needle_length ) ) === $needle )
				{
					$next = ( $lt + $needle_length < $length ) ? $page[ $lt + $needle_length ] : '>';

					if( strpos( " \t\r\n\f>/", $next ) !== false )
					{
						return $lt;
					}
				}
			}

			$pos = $lt + 1;
		}

		return -1;
	}

	/**
	 * Records pages left unparsed, at most once per six-hour window.
	 */
	private function map_write_html_scan_state( $state )
	{
		$now = time();
		$window = 6 * HOUR_IN_SECONDS;
		$current = MyAgilePrivacy::get_html_scan_state();

		if( null !== $current &&
			( floor( $now / $window ) == floor( $current['at'] / $window ) ||
				( $current['at'] > $now && $current['at'] - $now < $window ) ) )
		{
			return;
		}

		$record = array(
			'first'		=>	$now,
			'at'		=>	$now,
			'reason'	=>	( isset( $state['reason'] ) ) ? $state['reason'] : '',
			'info'		=>	( isset( $state['info'] ) ) ? $state['info'] : '',
			'n'			=>	1,
		);

		if( null !== $current &&
			$now - $current['at'] <= MAP_HTML_SCAN_STATE_TTL )
		{
			$record['first'] = min( $current['first'], $now );
			$record['n'] = min( $current['n'] + 1, 1000000 );
		}

		if( false === get_option( MAP_PLUGIN_HTML_SCAN_STATE, false ) )
		{
			add_option( MAP_PLUGIN_HTML_SCAN_STATE, $record, '', 'no' );
		}
		else
		{
			update_option( MAP_PLUGIN_HTML_SCAN_STATE, $record, false );
		}
	}

	/**
	 * map_callback_run
	 * @access   private
	*/
	private function map_callback_run( $output )
	{
		$this->scan_log = "Scanner started.\n";

		$something_done = false;
		$head_injected = false;

		$starting_output_save = $output;

		//init config
		$parse_config = $this->scan_config;

		$map_video_block_mode = isset( $this->saved_settings['video_privacy_mode'] ) && $this->saved_settings['video_privacy_mode'] == 'block';

		if( $map_video_block_mode && isset( $parse_config['iframe_src_block'] ) && is_array( $parse_config['iframe_src_block'] ) )
		{
			foreach( $parse_config['iframe_src_block'] as $map_video_entry )
			{
				if( isset( $map_video_entry['key'] ) && $map_video_entry['key'] === 'youtube' && !empty( $map_video_entry['src'] ) )
				{
					foreach( array( 'www.youtube-nocookie.com/embed/', '//youtube.com/embed/', '//youtube-nocookie.com/embed/' ) as $map_video_src )
					{
						$map_video_entry['src'] = $map_video_src;
						$parse_config['iframe_src_block'][] = $map_video_entry;
					}
					break;
				}
			}
		}

		$scan_output = $parse_config;

		if( $this->saved_settings['scanner_compatibility_mode'] )
		{
			//init html parser (false to lowercase, forceTagsClosed, stripRN )
			$dom = map_shd_str_get_html( $output, false, false, false, false );
		}
		else
		{
			//init html parser
			$dom = map_shd_str_get_html( $output, true, true, false, false );
		}
		$this->scan_dom = $dom;
		$parse_config = $this->map_drop_video_api_entries( $parse_config, $dom, $output );

		if( is_object( $dom ) )
		{
			//search for scripts
			$scripts = $dom->find( 'script' );

			if( isset( $parse_config ) && isset( $parse_config['scripts_src_block'] ) )
			{
				$this->map_find_pixel_channels( $scripts );

				foreach( $scripts as $k => $v )
				{
					//parent node check for skipping code inside map textarea
					$parentNode = $v->parentNode();

					if( $parentNode &&
						$parentNode->tag == 'textarea' &&
						$parentNode->hasClass( 'my_agile_privacy_activate' ) )
					{
						continue;
					}

					//class
					$class = $v->class;

					//inline
					$innertext = $v->innertext;

					//src
					$src = $this->map_script_src( $v );

					if( strpos( $class, 'my_agile_privacy_activate' ) === false && strpos( $class, 'map_do_not_touch' ) === false )
					{
						if( !empty( $innertext ) )
						{
							$found = array_filter( $parse_config['scripts_src_block'],  function( $e, $the_key ) use ( $innertext ){

								if( !$e['plain_js'] ) return false;

								return ( strpos( $innertext, $e['plain_js'] ) !== false );

							}, ARRAY_FILTER_USE_BOTH );

							if( !empty( $found ) )
							{
								$action = reset( $found );
								$return_key = key( $found );

								if( isset( $action ) )
								{
									$action_state = $this->map_script_action_state( $action );

									if( $action_state['state'] === 'always_allowed' )
									{
										$this->scan_log .= $action['key']." is always allowed\n";
										$v->class = $v->class.' map_always_allowed';
										continue;
									}

									$detected_pattern = $action['plain_js'];
									$detected_key = $action['key'];

									$action_active = $action_state['active'];
									$no_action = $action_state['no_action'];

									if( MyAgilePrivacy::looseEqualsPhp8( $action['to_detect'], 1 ) )
									{
										$this->scan_log .= "detected plain_js script $detected_key $detected_pattern, action_active=$action_active, no_action=$no_action\n";

										$scan_output['scripts_src_block'][ $return_key ]['detected'] = 1;

										$this->scan_done = true;
									}

									if( $action_state['state'] === 'block' )
									{
										if( !( isset( $action['silent_blocking'] ) && $action['silent_blocking'] == true ) )
										{
											if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= "blocked plain_js script $detected_key $detected_pattern\n\n";
											$v->class = $v->class .' '.'my_agile_privacy_activate autoscan_mode map_inline_script_blocked map_blocked_content';
										}

										if( isset( $action['on_block_add_classes'] ) )
										{
											$v->class = $v->class.' '.$action['on_block_add_classes'];
										}

										$v->type = 'text/plain';

										$v->setAttribute( 'data-cookie-api-key', $detected_key );
										$v->setAttribute( 'data-friendly_name', MyAgilePrivacy::nullCoalesceArrayItem( $action, 'friendly_name', '' ) );

										$scan_output['scripts_src_block'][ $return_key ]['blocked'] = 1;

										$this->scan_done = true;

										$something_done = true;
									}
									elseif( $action_state['state'] === 'no_block' )
									{
										$v->class = $v->class.' map_not_to_block';
									}
									elseif( !$src && !$v->hasAttribute( 'src' ) && $this->map_inline_held_twin( $action, $v ) )
									{
										$v->class = $v->class.' my_agile_privacy_activate autoscan_mode map_inline_script_blocked';

										if( isset( $action['on_block_add_classes'] ) )
										{
											$v->class = $v->class.' '.$action['on_block_add_classes'];
										}

										$v->type = 'text/plain';

										$v->setAttribute( 'data-cookie-api-key', $detected_key );

										$something_done = true;
									}
									elseif( !$this->map_inline_held_silent( $action ) )
									{
										//add the map_do_not_touch class
										$v->class = $v->class.' map_do_not_touch';
										$something_done = true;
									}
								}
							}
							else
							{
								//$this->scan_log .= "plain_js script unmatched, innertext=$innertext \n";
							}
						}
						elseif( $src )
						{
							$found = $this->map_find_src_entry( $parse_config['scripts_src_block'], array( $src ) );

							if( $found )
							{
								$action = $found['entry'];
								$return_key = $found['index'];

								$action_state = $this->map_script_action_state( $action );

								if( $action_state['state'] === 'always_allowed' )
								{
									$this->scan_log .= $action['key']." is always allowed\n";
									$v->class = $v->class.' map_always_allowed';
									continue;
								}

								$detected_pattern = $action['src'];
								$detected_key = $action['key'];

								$action_active = $action_state['active'];
								$no_action = $action_state['no_action'];

								if( MyAgilePrivacy::looseEqualsPhp8( $action['to_detect'], 1 ) )
								{
									$this->scan_log .= "detected src script $detected_key $detected_pattern $detected_pattern, action_active=$action_active, no_action=$no_action\n";

									$scan_output['scripts_src_block'][ $return_key ]['detected'] = 1;

									$this->scan_done = true;
								}
								$something_done = $this->map_silence_optimized_tag( $v, $action ) || $something_done;

								if( $action_state['state'] === 'block' )
								{
									if( !( isset( $action['silent_blocking'] ) && $action['silent_blocking'] == true ) )
									{
										if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= "blocked src script $detected_key $detected_pattern\n\n";
										$v->class = $v->class .' '.'my_agile_privacy_activate autoscan_mode map_src_script_blocked map_blocked_content';
									}

									if( isset( $action['on_block_add_classes'] ) )
									{
										$v->class = $v->class.' '.$action['on_block_add_classes'];
									}

									$this->map_unwrap_rocket_tag( $v );
									$v->unblocked_src = $src;
									$v->removeAttribute( 'src' );

									if( $this->map_src_needs_jquery( $v, $src ) ) { $v->class = $v->class.' map_needs_jquery'; }

									$v->setAttribute( 'data-cookie-api-key', $detected_key );
									$v->setAttribute( 'data-friendly_name', MyAgilePrivacy::nullCoalesceArrayItem( $action, 'friendly_name', '' ) );

									$scan_output['scripts_src_block'][ $return_key ]['blocked'] = 1;

									$this->scan_done = true;

									$something_done = true;
								}
								elseif( $action_state['state'] === 'no_block' )
								{
									$v->class = $v->class.' map_not_to_block';
									$something_done = true;
								}
							}
							else
							{
								//$this->scan_log .= "plain_js script unmatched, src=$src \n";
							}
						}
						else
						{
							//no action
						}
					}
				}
			}


			//search for iframes
			$iframes = $dom->find( 'iframe' );

			if( isset( $parse_config['iframe_src_block'] ) && is_array( $parse_config['iframe_src_block'] ) )
			{
				foreach( $iframes as $k => $v )
				{
					if( $this->map_process_iframe_node( $v, $parse_config['iframe_src_block'], $scan_output, $map_video_block_mode ) )
					{
						$something_done = true;
					}
				}

				foreach( $dom->find( 'div.rll-youtube-player' ) as $k => $v )
				{
					if( $this->map_process_rocket_youtube_facade( $v, $parse_config['iframe_src_block'] ) )
					{
						$something_done = true;
					}
				}

				if( $map_video_block_mode )
				{
					foreach( $dom->find( 'div.container-lazyload' ) as $k => $v )
					{
						if( $this->map_process_lazy_video_link( $v, $parse_config['iframe_src_block'], $scan_output, $map_video_block_mode ) )
						{
							$something_done = true;
						}
					}
				}
			}

			//search for links
			$links = $dom->find( 'link[rel="stylesheet"]' );

			if( isset( $parse_config ) && isset( $parse_config['css_href_block'] ) )
			{
				foreach( $links as $k => $v )
				{
					//href
					$href = $v->href;
					$class = $v->class;

					if( strpos( $class, 'my_agile_privacy_activate' ) === false && strpos( $class, 'map_do_not_touch' ) === false )
					{
						$found = array_filter( $parse_config['css_href_block'],  function( $e, $the_key ) use ( $href ){

							if( !$e['href'] ) return false;

							return ( strpos(  $href, $e['href'] ) !== false );

						}, ARRAY_FILTER_USE_BOTH );

						if( !empty( $found ) )
						{
							$action = reset( $found );
							$return_key = key( $found );

							if( isset( $action ) )
							{
								if( isset( $action['always_allowed'] ) && $action['always_allowed'] )
								{
									$this->scan_log .= $action['key']." is always allowed\n";
									$v->class = $v->class.' map_always_allowed';
									continue;
								}

								$detected_pattern = $action['href'];
								$detected_key = $action['key'];

								$action_active = $action['active'];

								$no_action = false;

								if( isset( $action['only_on_compatibility_mode'] ) &&
									$action['only_on_compatibility_mode'] == true &&
									$this->saved_settings['scanner_compatibility_mode'] == false )
								{
									$no_action = true;
								}

								if( MyAgilePrivacy::looseEqualsPhp8( $action['to_detect'], 1 ) )
								{
									$this->scan_log .= "detected link $detected_key $detected_pattern, action_active=$action_active, no_action=$no_action\n";

									$scan_output['css_href_block'][ $return_key ]['detected'] = 1;

									$this->scan_done = true;

									$something_done = true;
								}

								if( $action_active && !$no_action )
								{
									if( MyAgilePrivacy::looseEqualsPhp8( $action['to_block'], 1 ) )
									{
										if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= "blocked link $detected_key $detected_pattern\n\n";

										$v->class = $v->class.' '.'my_agile_privacy_activate autoscan_mode css_href_blocked map_blocked_content';

										if( isset( $action['on_block_add_classes'] ) )
										{
											$v->class = $v->class.' '.$action['on_block_add_classes'];
										}

										$v->unblocked_href = $v->href;
										$v->href = '';
										$v->setAttribute( 'data-cookie-api-key', $detected_key );
										$v->setAttribute( 'data-friendly_name', MyAgilePrivacy::nullCoalesceArrayItem( $action, 'friendly_name', '' ) );

										$scan_output['css_href_block'][ $return_key ]['blocked'] = 1;

										$this->scan_done = true;

										$something_done = true;
									}
									else
									{
										$v->class = $v->class.' map_not_to_block';
									}
								}
								else
								{
									//add the map_do_not_touch class

									$v->class = $v->class.' map_do_not_touch';
									$something_done = true;
								}
							}
						}
					}
				}
			}

			//elementor wigets
			$elementor_widgets = $dom->find( '.elementor-widget' );

			if( isset( $parse_config ) && isset( $parse_config['elementor_widget_block'] ) )
			{
				foreach( $elementor_widgets as $k => $v )
				{
					//data-settings
					$data_settings = $v->getAttribute( 'data-settings' );

					$class = $v->class;

					if( strpos( $class, 'map_do_not_touch' ) === false )
					{
						$found = array_filter( $parse_config['elementor_widget_block'],  function( $e, $the_key ) use ( $data_settings ){

							if( !$e['src'] ) return false;

							return ( strpos(  $data_settings, $e['src'] ) !== false );

						}, ARRAY_FILTER_USE_BOTH );

						if( !empty( $found ) )
						{
							$action = reset( $found );
							$return_key = key( $found );

							if( isset( $action ) )
							{
								if( isset( $action['always_allowed'] ) && $action['always_allowed'] )
								{
									$this->scan_log .= $action['key']." is always allowed\n";
									$v->class = $v->class.' map_always_allowed';
									continue;
								}

								$detected_pattern = $action['src'];
								$detected_key = $action['key'];

								$action_active = $action['active'];

								$no_action = false;

								if( isset( $action['only_on_compatibility_mode'] ) &&
									$action['only_on_compatibility_mode'] == true &&
									$this->saved_settings['scanner_compatibility_mode'] == false )
								{
									$no_action = true;
								}

								if( MyAgilePrivacy::looseEqualsPhp8( $action['to_detect'], 1 ) )
								{
									$this->scan_log .= "detected elementor-widget $detected_key $detected_pattern, action_active=$action_active, no_action=$no_action\n";

									$scan_output['elementor_widget_block'][ $return_key ]['detected'] = 1;

									$this->scan_done = true;
								}

								if( $action_active && !$no_action )
								{
									if( isset( $action['to_fix'] ) && $action['to_fix'] == 1 )
									{
										if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= "fixed elementor-widget $detected_pattern\n\n";

										if( $action['fix_mode'] == 'json_append' )
										{
											$v->class = $v->class.' '.'map_script_fixed';
											$v->original_data_settings = $data_settings;

											$decoded_data_settings = json_decode( html_entity_decode( $data_settings, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8' ), true );

											$decoded_data_settings = array_merge( (array)$decoded_data_settings,(array)$action['json_add'] );

											$v->setAttribute( 'data-settings', htmlentities( json_encode( $decoded_data_settings ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8' ) );
										}

										$scan_output['elementor_widget_block'][ $return_key ]['fixed'] = 1;

										$this->scan_done = true;

										$something_done = true;
									}
								}
								else
								{
									//add the map_do_not_touch class

									$v->class = $v->class.' map_do_not_touch';
									$something_done = true;
								}
							}
						}
					}
				}
			}

			//elementor specific fixings (lightbox youtube)
			$elementor_widgets = $dom->find( '.elementor-widget .elementor-image a' );

			foreach( $elementor_widgets as $k => $v )
			{
				//href
				$href = $v->href;
				$class = $v->class;

				if( strpos( $class, 'map_do_not_touch' ) === false )
				{
					if( strpos( $href, '#elementor-action%3Aaction%3Dlightbox' ) !== false )
					{
						$url_decoded = urldecode( $href );
						$exploded = explode( '&', $url_decoded );

						if( !isset( $exploded[1] ) )
						{
							continue;
						}

						$to_fix = str_replace( 'settings=','', $exploded[1] );

						$to_fix_decoded = base64_decode( $to_fix );

						$to_fix_decoded_decoded = json_decode( $to_fix_decoded, true );

						if( !is_array( $to_fix_decoded_decoded ) ||
							!isset( $to_fix_decoded_decoded['url'] ) ||
							!is_string( $to_fix_decoded_decoded['url'] ) )
						{
							continue;
						}

						$to_fix_decoded_decoded['url'] = str_replace( 'www.youtube.com', 'www.youtube-nocookie.com', $to_fix_decoded_decoded['url'] );

						$to_fix_decoded_encoded = json_encode($to_fix_decoded_decoded );

						$to_fix_encoded = base64_encode( $to_fix_decoded_encoded );

						$url_fixed = urlencode( str_replace( $to_fix, $to_fix_encoded, $url_decoded ) );

						$v->href = $url_fixed;
						$v->setAttribute( 'original-href', $href );

						$this->scan_done = true;

						$something_done = true;
					}
				}
			}

			//elementor-background-video-container
			$elementor_widgets = $dom->find( '.elementor-background-video-container' );

			foreach( $elementor_widgets as $k => $v )
			{
				$class = $v->class;

				if( strpos( $class, 'map_do_not_touch' ) === false )
				{
					//parent
					$parent = $v->parent();

					if( $parent )
					{
						$data_settings = $parent->getAttribute( 'data-settings' );

						$action_to_add = array( 'background_privacy_mode' => 'yes' );

						$decoded_data_settings = json_decode( html_entity_decode( $data_settings, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8' ), true );

						$decoded_data_settings = array_merge( (array)$decoded_data_settings,(array)$action_to_add );

						$parent->setAttribute( 'data-settings', htmlentities( json_encode( $decoded_data_settings ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8' ) );

						$parent->class = $parent->class.' '.'map_script_fixed';

						$this->scan_done = true;

						$something_done = true;
					}
				}
			}

			//avia specific fixings (youtube)
			$avia_widgets = $dom->find( '.av-video-tmpl' );

			foreach( $avia_widgets as $k => $v )
			{
				//inline
				$innertext = $v->innertext;
				$class = $v->class;

				if( strpos( $class, 'map_do_not_touch' ) === false )
				{
					if( strpos( $innertext, 'www.youtube.com' ) !== false )
					{
						$innertext = str_replace( 'www.youtube.com', 'www.youtube-nocookie.com', $innertext );

						$v->__set( 'innertext', $innertext );

						$this->scan_done = true;

						$something_done = true;
					}
				}
			}

			//search for images
			$images = $dom->find( 'img' );

			if( isset( $parse_config['img_search_replace'] ) && is_array( $parse_config['img_search_replace'] ) )
			{
				foreach( $images as $k => $v )
				{
					if( $this->map_process_img_node( $v, $parse_config['img_search_replace'], $scan_output ) )
					{
						$something_done = true;
					}
				}
			}
		}

		$this->scan_output = $scan_output;

		$output_ori = $dom;

		if( $this->head_script && is_object( $dom ) )
		{
			$inject = $this->head_script;

			$head_object = $dom->find( 'head', 0 );

			if( $head_object && is_object( $head_object ) )
			{
				$head_object->innertext = $inject.$head_object->innertext;

				$something_done = true;
				$head_injected = true;
			}
		}

		$output = $dom;

		if( !$output )
		{
			$output = $starting_output_save;
			$this->scan_log .= "Missing output" . "\n";
		}

		if( !$something_done )
		{
			$output = $starting_output_save;
			$this->scan_log .= "Something done is false" . "\n";
		}

		//dom to html conversion
		$final_output = '';

		if( is_object( $output ) )
		{
		    $this->scan_log .= "Converting DOM object to HTML string\n";

		    // errors reach map_callback(), which keeps the head script
		    $final_output = $output->save();
		    $this->scan_log .= "DOM conversion successful\n";
		}
		else
		{
		    $final_output = $output;
		    $this->scan_log .= "Output is already string, length=" . strlen( $final_output ) . "\n";
		}

		if( empty( $final_output ) )
		{
		    if( !empty( $starting_output_save ) )
		    {
		        MAP_Simple_Html_Dom::abort( 'errore', 'empty output' );
		    }

		    $this->scan_log .= "WARNING: Empty output, using original input\n";
		    $final_output = $starting_output_save;
		}

		$this->scan_dom = null;
		$this->scan_saved_output = $final_output;

		// only a saved page carries the head script
		if( $head_injected )
		{
			$this->html_head_done = true;
		}

		$starting_input_length = strlen( $starting_output_save );
		$final_output_length = strlen( $final_output );

		$different_length = 'NO';
		if( $starting_input_length != $final_output_length )
		{
			$different_length = 'YES';
		}

		$this->scan_log .= "Final output ready, length=$final_output_length, starting_lenght=$starting_input_length, different_length=$different_length\n";

		$js_cookie_shield_detected_keys = explode( ',', MyAgilePrivacy::get_option( MAP_PLUGIN_JS_DETECTED_FIELDS, '' ) );
		$js_cookie_shield_detected_keys = array_unique( array_filter( $js_cookie_shield_detected_keys ) );

		if( $this->scan_done || count( $js_cookie_shield_detected_keys ) > 0 || count( $this->optimizer_detected_keys ) > 0 )
		{
			if( $this->scan_log && defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $this->scan_log );

			//auto activate
			if( $this->scan_mode == 'learning_mode' )
			{
				$auto_activate_keys = $this->optimizer_detected_keys;

				foreach( $this->scan_output as $k => $v )
				{
					foreach( $v as $kk => $vv )
					{
						if( $vv['key'] && $vv['detected'] )
						{
							$auto_activate_keys[] = $vv['key'];
						}
					}
				}

				foreach( $js_cookie_shield_detected_keys as $k => $v )
				{
					$this->scan_log .= "JS Cookie Shield has detected $v\n";

					if( $v )
					{
						$auto_activate_keys[] = $v;
					}
				}

				// published by the detection report
				$this->pending_detected_fields = $this->map_detected_keys_string( $auto_activate_keys );

				if( $this->shutdown_passed )
				{
					$this->map_run_pending_writes();
				}

				$this->scan_log .= "Scanner finished.\n";
			}
		}

		if( $this->saved_settings['scanner_compatibility_mode'] )
		{
			global $post;
			setup_postdata( $this->saved_post );
		}

		return $final_output;
	}

	/**
	 * Deferred source attributes of an iframe.
	 */
	private function map_iframe_lazy_attrs()
	{
		return array( 'data-lazy-src', 'data-src', 'data-litespeed-src', 'data-lazy-load' );
	}

	/**
	 * Tells whether a source value carries no real url.
	 */
	private function map_is_placeholder_url( $value )
	{
		if( !is_string( $value ) )
		{
			return true;
		}

		$value = strtolower( trim( $value ) );

		return ( $value === '' || $value === 'about:blank' || strpos( $value, 'data:' ) === 0 );
	}

	/**
	 * Class tokens of a class attribute value.
	 */
	private function map_class_tokens( $class )
	{
		if( !is_string( $class ) )
		{
			return array();
		}

		return preg_split( '/\s+/', trim( $class ), -1, PREG_SPLIT_NO_EMPTY );
	}

	/**
	 * Tells whether a class attribute value has a token.
	 */
	private function map_class_has( $class, $token )
	{
		return in_array( $token, $this->map_class_tokens( $class ), true );
	}

	/**
	 * Class attribute value with some tokens removed.
	 */
	private function map_class_remove( $class, $remove )
	{
		return implode( ' ', array_diff( $this->map_class_tokens( $class ), $remove ) );
	}

	/**
	 * Class attribute value with some tokens added.
	 */
	private function map_class_add( $class, $add )
	{
		$tokens = $this->map_class_tokens( $class );

		foreach( $this->map_class_tokens( $add ) as $token )
		{
			if( !in_array( $token, $tokens, true ) )
			{
				$tokens[] = $token;
			}
		}

		return implode( ' ', $tokens );
	}

	/**
	 * Tells whether a node has at least one of the given attributes.
	 */
	private function map_has_any_attribute( $node, $attrs )
	{
		foreach( $attrs as $attr )
		{
			if( $node->hasAttribute( $attr ) )
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Value for a new double quoted attribute.
	 */
	private function map_attr_value( $value )
	{
		return str_replace( '"', '&quot;', $value );
	}

	/**
	 * Url forms compared against the block list.
	 */
	private function map_url_match_values( $value )
	{
		$values = array( $value );

		if( strpos( $value, '//' ) === 0 )
		{
			$values[] = 'https:' . $value;
		}

		return $values;
	}

	/**
	 * Srcset forms compared against the block list.
	 */
	private function map_srcset_match_values( $value )
	{
		$values = array( $value );

		$normalized = preg_replace( '#(^|[\s,])//#', '$1https://', $value );

		if( is_string( $normalized ) && $normalized !== $value )
		{
			$values[] = $normalized;
		}

		return $values;
	}

	/**
	 * First block list entry matching one of the values.
	 */
	private function map_find_src_entry( $entries, $values )
	{
		foreach( $entries as $index => $e )
		{
			if( !is_array( $e ) || empty( $e['src'] ) || !is_string( $e['src'] ) )
			{
				continue;
			}

			$added_src = '';

			if( !empty( $e['added_src'] ) )
			{
				if( !is_string( $e['added_src'] ) )
				{
					continue;
				}

				$added_src = $e['added_src'];
			}

			foreach( $values as $value )
			{
				if( strpos( $value, $e['src'] ) !== false &&
					( $added_src === '' || strpos( $value, $added_src ) !== false ) )
				{
					return array( 'index' => $index, 'entry' => $e );
				}
			}
		}

		return null;
	}

	/**
	 * Source candidates of a node, deferred attributes first.
	 */
	private function map_get_url_candidates( $node, $attrs, $kind )
	{
		$candidates = array();

		foreach( $attrs as $attr )
		{
			if( !$node->hasAttribute( $attr ) )
			{
				continue;
			}

			$value = $node->getAttribute( $attr );

			if( $this->map_is_placeholder_url( $value ) )
			{
				continue;
			}

			$candidates[] = array( 'attr' => $attr, 'value' => $value, 'kind' => $kind );
		}

		return $candidates;
	}

	/**
	 * Evaluates the candidates of a node against a block list.
	 */
	private function map_evaluate_candidates( $candidates, $entries, $section, $label, &$scan_output, $map_video_block_mode )
	{
		$result = array( 'block' => null, 'fix' => array(), 'matched' => 0, 'allowed' => 0, 'inactive' => 0 );

		foreach( $candidates as $candidate )
		{
			$match_values = ( $candidate['kind'] === 'single' ) ? $this->map_url_match_values( $candidate['value'] ) : $this->map_srcset_match_values( $candidate['value'] );

			$found = $this->map_find_src_entry( $entries, $match_values );

			if( $found === null )
			{
				continue;
			}

			$result['matched']++;

			$action = $found['entry'];
			$return_key = $found['index'];

			$detected_key = MyAgilePrivacy::nullCoalesceArrayItem( $action, 'key', '' );

			if( isset( $action['always_allowed'] ) && $action['always_allowed'] )
			{
				$this->scan_log .= $detected_key." is always allowed\n";
				$result['allowed']++;
				continue;
			}

			$detected_pattern = $action['src'];

			$action_active = MyAgilePrivacy::nullCoalesceArrayItem( $action, 'active', false );

			if( $map_video_block_mode &&
				in_array( $detected_key, array( 'youtube', 'vimeo' ), true ) &&
				empty( $action['map_key_not_blockable'] ) )
			{
				$action['to_fix'] = 0;
				$action['to_block'] = 1;
				$action['show_inline_notify'] = 1;
				$action['on_block_add_classes'] = trim( MyAgilePrivacy::nullCoalesceArrayItem( $action, 'on_block_add_classes', '' ).' map_video_block' );
			}

			$no_action = false;

			if( isset( $action['only_on_compatibility_mode'] ) &&
				$action['only_on_compatibility_mode'] == true &&
				$this->saved_settings['scanner_compatibility_mode'] == false )
			{
				$no_action = true;
			}

			if( MyAgilePrivacy::looseEqualsPhp8( MyAgilePrivacy::nullCoalesceArrayItem( $action, 'to_detect', 0 ), 1 ) )
			{
				$this->scan_log .= "detected $label $detected_key $detected_pattern, action_active=$action_active, no_action=$no_action\n";

				$scan_output[ $section ][ $return_key ]['detected'] = 1;

				$this->scan_done = true;
			}

			if( !$action_active || $no_action )
			{
				$result['inactive']++;
				continue;
			}

			$candidate['action'] = $action;
			$candidate['return_key'] = $return_key;

			if( MyAgilePrivacy::looseEqualsPhp8( MyAgilePrivacy::nullCoalesceArrayItem( $action, 'to_block', 0 ), 1 ) )
			{
				if( $result['block'] === null )
				{
					$result['block'] = $candidate;
				}
			}
			elseif( MyAgilePrivacy::looseEqualsPhp8( MyAgilePrivacy::nullCoalesceArrayItem( $action, 'to_fix', 0 ), 1 ) )
			{
				$result['fix'][] = $candidate;
			}
		}

		return $result;
	}

	/**
	 * Marks a node whose matches are all allowed or all inactive.
	 */
	private function map_mark_untouched_node( $node, $result )
	{
		if( $result['matched'] > 0 && $result['allowed'] === $result['matched'] )
		{
			$node->class = $this->map_class_add( $node->class, 'map_always_allowed' );
			return false;
		}

		if( $result['matched'] > 0 && $result['inactive'] === $result['matched'] )
		{
			$node->class = $this->map_class_add( $node->class, 'map_do_not_touch' );
			return true;
		}

		return false;
	}

	/**
	 * Fixed url for an iframe entry, or null when the entry has no fix.
	 */
	private function map_apply_iframe_fix( $action, $value )
	{
		$has_replace = isset( $action['to_fix_search'] ) && isset( $action['to_fix_replace'] );
		$has_param = isset( $action['to_append_param'] ) && is_string( $action['to_append_param'] );

		if( !$has_replace && !$has_param )
		{
			return null;
		}

		$fixed = $value;

		if( $has_replace )
		{
			$fixed = str_replace( $action['to_fix_search'], $action['to_fix_replace'], $value );
		}

		if( $has_param )
		{
			$fixed = $value . ( ( strpos( $value, '?' ) !== false ) ? '&' : '?' ) . $action['to_append_param'];

			if( !empty( $action['to_fix_search'] ) && !empty( $action['to_fix_replace'] ) )
			{
				$fixed = str_replace( $action['to_fix_search'], $action['to_fix_replace'], $fixed );
			}
		}

		return is_string( $fixed ) ? $fixed : null;
	}

	/**
	 * Applies the iframe rules to one node.
	 */
	private function map_process_iframe_node( $v, $entries, &$scan_output, $map_video_block_mode )
	{
		$lazy_attrs = $this->map_iframe_lazy_attrs();

		$candidates = $this->map_get_url_candidates( $v, array_merge( $lazy_attrs, array( 'src' ) ), 'single' );

		if( empty( $candidates ) )
		{
			return false;
		}

		$result = $this->map_evaluate_candidates( $candidates, $entries, 'iframe_src_block', 'iframe', $scan_output, $map_video_block_mode );

		if( $result['block'] !== null )
		{
			$this->map_block_iframe_node( $v, $result['block'], $this->map_has_any_attribute( $v, $lazy_attrs ), $scan_output );
			return true;
		}

		if( !empty( $result['fix'] ) )
		{
			foreach( $result['fix'] as $candidate )
			{
				$action = $candidate['action'];

				if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= "fixed ".MyAgilePrivacy::nullCoalesceArrayItem( $action, 'key', '' )." ".$action['src']."\n\n";

				$fixed = $this->map_apply_iframe_fix( $action, $candidate['value'] );

				if( $fixed !== null )
				{
					if( !$v->hasAttribute( 'original_src' ) )
					{
						$v->setAttribute( 'original_src', $this->map_attr_value( $candidate['value'] ) );
					}

					$v->setAttribute( $candidate['attr'], $fixed );
					$v->class = $this->map_class_add( $v->class, 'map_script_fixed' );
				}

				$scan_output['iframe_src_block'][ $candidate['return_key'] ]['fixed'] = 1;
			}

			$this->scan_done = true;

			return true;
		}

		return $this->map_mark_untouched_node( $v, $result );
	}

	/**
	 * Blocks an iframe and clears its deferred loading state.
	 */
	private function map_block_iframe_node( $v, $candidate, $had_lazy, &$scan_output )
	{
		$action = $candidate['action'];

		$detected_key = MyAgilePrivacy::nullCoalesceArrayItem( $action, 'key', '' );

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= "blocked iframe $detected_key ".$action['src']."\n\n";

		$classes = 'my_agile_privacy_activate autoscan_mode iframe_src_blocked map_blocked_content';

		if( isset( $action['show_inline_notify'] ) )
		{
			$classes .= ' map_show_inline_notify';
		}

		if( isset( $action['on_block_add_classes'] ) && is_string( $action['on_block_add_classes'] ) )
		{
			$classes .= ' '.$action['on_block_add_classes'];
		}

		if( $detected_key === 'google_maps' )
		{
			$classes .= ' map_maps_block';
		}

		$class = $v->class;

		if( $had_lazy )
		{
			$class = $this->map_class_remove( $class, array( 'lazyload', 'lazyloading', 'lazy-hidden' ) );
		}

		$v->class = $this->map_class_add( $class, $classes );

		$v->unblocked_src = $this->map_attr_value( $candidate['value'] );

		foreach( array_merge( array( 'src' ), $this->map_iframe_lazy_attrs() ) as $attr )
		{
			if( $v->hasAttribute( $attr ) )
			{
				$v->removeAttribute( $attr );
			}
		}

		$v->setAttribute( 'data-cookie-api-key', $detected_key );
		$v->setAttribute( 'data-friendly_name', MyAgilePrivacy::nullCoalesceArrayItem( $action, 'friendly_name', '' ) );

		if( $had_lazy && !$v->hasAttribute( 'loading' ) )
		{
			$v->setAttribute( 'loading', 'lazy' );
		}

		$parent = $v->parent();

		if( $parent && $this->map_class_has( $parent->class, 'smush-lazyload-video' ) )
		{
			$parent->class = $this->map_class_add( $this->map_class_remove( $parent->class, array( 'lazyload', 'lazyloading' ) ), 'smush-lazyloaded-video' );

			foreach( array( 'data-bg-image', 'data-bg', 'data-smush-webp-fallback', 'data-smush-avif-fallback' ) as $attr )
			{
				if( $parent->hasAttribute( $attr ) )
				{
					$parent->removeAttribute( $attr );
				}
			}
		}

		$scan_output['iframe_src_block'][ $candidate['return_key'] ]['blocked'] = 1;

		$this->scan_done = true;
	}

	/**
	 * Replaces a video facade with its blocked or fixed embed.
	 */
	private function map_process_rocket_youtube_facade( $div, $entries )
	{
		$noscript = $div->next_sibling();

		if( $noscript && strtolower( $noscript->tag ) === 'p' )
		{
			$noscript = $noscript->first_child();
		}

		if( !$noscript || strtolower( $noscript->tag ) !== 'noscript' )
		{
			return false;
		}

		$iframe = $noscript->find( 'iframe', 0 );

		if( !$iframe )
		{
			return false;
		}

		if( $this->map_class_has( $iframe->class, 'iframe_src_blocked' ) )
		{
			if( !$iframe->hasAttribute( 'loading' ) )
			{
				$iframe->setAttribute( 'loading', 'lazy' );
			}

			$div->outertext = $iframe->outertext;

			return true;
		}

		if( !$this->map_class_has( $iframe->class, 'map_script_fixed' ) )
		{
			return false;
		}

		$data_src = $div->getAttribute( 'data-src' );

		if( $this->map_is_placeholder_url( $data_src ) )
		{
			return false;
		}

		$found = $this->map_find_src_entry( $entries, $this->map_url_match_values( $data_src ) );

		if( $found === null || !MyAgilePrivacy::looseEqualsPhp8( MyAgilePrivacy::nullCoalesceArrayItem( $found['entry'], 'to_fix', 0 ), 1 ) )
		{
			return false;
		}

		$fixed = $this->map_apply_iframe_fix( $found['entry'], $data_src );

		if( $fixed === null || $fixed === $data_src )
		{
			return false;
		}

		$div->setAttribute( 'data-src', $fixed );

		return true;
	}

	/**
	 * Embed url of a video link, or null when it is not recognized.
	 */
	private function map_video_link_embed_url( $link, $provider )
	{
		$href = $link->getAttribute( 'href' );

		if( !is_string( $href ) )
		{
			return null;
		}

		$href = html_entity_decode( $href, ENT_QUOTES, 'UTF-8' );

		if( $provider === 'youtube' )
		{
			if( preg_match( '#(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:[^\#]*&)?v=|embed/|shorts/|live/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})#i', $href, $matches ) )
			{
				return 'https://www.youtube-nocookie.com/embed/'.$matches[1];
			}

			return null;
		}

		$video_id = $link->getAttribute( 'id' );

		if( !is_string( $video_id ) || !preg_match( '/^\d+$/', $video_id ) )
		{
			if( !preg_match( '#vimeo\.com/(?:[^?\#]*/)?(\d+)#i', $href, $matches ) )
			{
				return null;
			}

			$video_id = $matches[1];
		}

		$query = 'dnt=1';

		if( preg_match( '#vimeo\.com/(?:[^?\#]*/)?\d+/([0-9a-f]+)#i', $href, $matches ) ||
			preg_match( '#[?&]h=([0-9a-f]+)#i', $href, $matches ) )
		{
			$query = 'h='.$matches[1].'&dnt=1';
		}

		return 'https://player.vimeo.com/video/'.$video_id.'?'.$query;
	}

	/**
	 * Replaces a video link placeholder with a blocked embed.
	 */
	private function map_process_lazy_video_link( $div, $entries, &$scan_output, $map_video_block_mode )
	{
		$provider = 'youtube';
		$link = $div->find( 'a.lazy-load-youtube', 0 );

		if( !$link )
		{
			$provider = 'vimeo';
			$link = $div->find( 'a.lazy-load-vimeo', 0 );
		}

		if( !$link )
		{
			return false;
		}

		$embed_url = $this->map_video_link_embed_url( $link, $provider );

		if( $embed_url === null )
		{
			return false;
		}

		$title = $link->getAttribute( 'data-video-title' );

		if( !is_string( $title ) )
		{
			$title = '';
		}

		$html = '<iframe src="'.esc_url( $embed_url ).'" title="'.esc_attr( $title ).'" width="560" height="315" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';

		$fragment = map_shd_str_get_html( $html, true, true, false, false );

		if( !is_object( $fragment ) )
		{
			return false;
		}

		$iframe = $fragment->find( 'iframe', 0 );

		if( !$iframe )
		{
			return false;
		}

		$this->map_process_iframe_node( $iframe, $entries, $scan_output, $map_video_block_mode );

		if( !$this->map_class_has( $iframe->class, 'iframe_src_blocked' ) )
		{
			return false;
		}

		$div->outertext = $iframe->outertext;

		return true;
	}

	/**
	 * Applies the image rules to one node.
	 */
	private function map_process_img_node( $v, $entries, &$scan_output )
	{
		$single_attrs = array( 'data-lazy-src', 'data-src', 'src' );
		$set_attrs = array( 'data-lazy-srcset', 'data-srcset', 'srcset' );
		$sizes_attrs = array( 'data-lazy-sizes', 'data-sizes', 'sizes' );

		$sources = array();

		$parent = $v->parent();

		if( $parent && strtolower( $parent->tag ) === 'picture' )
		{
			foreach( $parent->children() as $child )
			{
				if( strtolower( $child->tag ) === 'source' )
				{
					$sources[] = $child;
				}
			}
		}

		$candidates = array_merge(
			$this->map_get_url_candidates( $v, $single_attrs, 'single' ),
			$this->map_get_url_candidates( $v, $set_attrs, 'set' )
		);

		foreach( $sources as $source )
		{
			$candidates = array_merge( $candidates, $this->map_get_url_candidates( $source, $set_attrs, 'source' ) );
		}

		if( empty( $candidates ) )
		{
			return false;
		}

		$result = $this->map_evaluate_candidates( $candidates, $entries, 'img_search_replace', 'img', $scan_output, false );

		if( $result['block'] === null )
		{
			return $this->map_mark_untouched_node( $v, $result );
		}

		$candidate = $result['block'];
		$action = $candidate['action'];

		$detected_key = MyAgilePrivacy::nullCoalesceArrayItem( $action, 'key', '' );

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= "blocked img $detected_key ".$action['src']."\n";

		$lazy_attrs = array( 'data-lazy-src', 'data-src', 'data-lazy-srcset', 'data-srcset' );

		$had_lazy = $this->map_has_any_attribute( $v, $lazy_attrs );

		foreach( $sources as $source )
		{
			if( $this->map_has_any_attribute( $source, $lazy_attrs ) )
			{
				$had_lazy = true;
			}
		}

		$unblocked_src = null;

		if( $candidate['kind'] === 'single' )
		{
			$unblocked_src = $candidate['value'];
		}
		else
		{
			$singles = $this->map_get_url_candidates( $v, $single_attrs, 'single' );

			if( !empty( $singles ) )
			{
				$unblocked_src = $singles[0]['value'];
			}
		}

		$sets = $this->map_get_url_candidates( $v, $set_attrs, 'set' );
		$sizes = $this->map_get_img_sizes( $v, $sizes_attrs );

		$classes = 'my_agile_privacy_activate autoscan_mode img_src_blocked map_blocked_content';

		if( isset( $action['show_inline_notify'] ) )
		{
			$classes .= ' map_show_inline_notify';
		}

		if( isset( $action['on_block_add_classes'] ) && is_string( $action['on_block_add_classes'] ) )
		{
			$classes .= ' '.$action['on_block_add_classes'];
		}

		$class = $v->class;

		if( $had_lazy )
		{
			$class = $this->map_class_remove( $class, array( 'lazyload', 'lazyloading', 'lazy-hidden' ) );
		}

		$v->class = $this->map_class_add( $class, $classes );

		if( $unblocked_src !== null )
		{
			$v->unblocked_src = $this->map_attr_value( $unblocked_src );
		}

		if( !empty( $sets ) )
		{
			$v->setAttribute( 'unblocked_srcset', $this->map_attr_value( $sets[0]['value'] ) );
		}

		if( $sizes !== null )
		{
			$v->setAttribute( 'unblocked_sizes', $this->map_attr_value( $sizes ) );
		}

		foreach( array_merge( array( 'data-lazy-src', 'data-src' ), $set_attrs, array( 'data-lazy-sizes', 'data-sizes' ) ) as $attr )
		{
			if( $v->hasAttribute( $attr ) )
			{
				$v->removeAttribute( $attr );
			}
		}

		//blank image
		$v->src = 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';
		$v->setAttribute( 'data-cookie-api-key', $detected_key );
		$v->setAttribute( 'data-friendly_name', MyAgilePrivacy::nullCoalesceArrayItem( $action, 'friendly_name', '' ) );

		if( $had_lazy && !$v->hasAttribute( 'loading' ) )
		{
			$v->setAttribute( 'loading', 'lazy' );
		}

		foreach( $sources as $source )
		{
			$source_sets = $this->map_get_url_candidates( $source, $set_attrs, 'source' );

			if( !empty( $source_sets ) )
			{
				$source->setAttribute( 'unblocked_srcset', $this->map_attr_value( $source_sets[0]['value'] ) );
			}

			$source_sizes = $this->map_get_img_sizes( $source, $sizes_attrs );

			if( $source_sizes !== null )
			{
				$source->setAttribute( 'unblocked_sizes', $this->map_attr_value( $source_sizes ) );
			}

			foreach( array_merge( $set_attrs, array( 'data-lazy-sizes', 'data-sizes' ) ) as $attr )
			{
				if( $source->hasAttribute( $attr ) )
				{
					$source->removeAttribute( $attr );
				}
			}
		}

		$scan_output['img_search_replace'][ $candidate['return_key'] ]['blocked'] = 1;

		$this->scan_done = true;

		return true;
	}

	/**
	 * First explicit sizes value of a node.
	 */
	private function map_get_img_sizes( $node, $attrs )
	{
		foreach( $attrs as $attr )
		{
			if( !$node->hasAttribute( $attr ) )
			{
				continue;
			}

			$value = $node->getAttribute( $attr );

			if( !is_string( $value ) )
			{
				continue;
			}

			$trimmed = strtolower( trim( $value ) );

			if( $trimmed !== '' && $trimmed !== 'auto' )
			{
				return $value;
			}
		}

		return null;
	}

	private $optimizer_exclusions = null;

	/**
	 * Registers the exclusion lists and the asset version filters for the page optimizers.
	 * @access   public
	 */
	public function register_optimizer_exclusions()
	{
		foreach( array( 'litespeed_optm_js_defer_exc', 'litespeed_optm_gm_js_exc', 'litespeed_optimize_js_excludes' ) as $hook )
		{
			add_filter( $hook, array( $this, 'map_opt_exclusions_litespeed' ) );
		}

		add_filter( 'rocket_delay_js_exclusions', array( $this, 'map_opt_exclusions_rocket_delay' ) );
		add_filter( 'rocket_exclude_defer_js', array( $this, 'map_opt_exclusions_rocket_paths' ) );
		add_filter( 'rocket_exclude_js', array( $this, 'map_opt_exclusions_rocket_local' ) );
		add_filter( 'rocket_minify_excluded_external_js', array( $this, 'map_opt_exclusions_host' ) );
		add_filter( 'rocket_defer_inline_exclusions', array( $this, 'map_opt_exclusions_inline' ) );
		add_filter( 'rocket_excluded_inline_js_content', array( $this, 'map_opt_exclusions_inline' ) );

		// above 13: the callbacks Autoptimize adds on 12 and 13 flatten an array into a string
		add_filter( 'autoptimize_filter_js_exclude', array( $this, 'map_opt_exclusions_autoptimize' ), 20 );

		foreach( array( 'sgo_js_async_exclude', 'sgo_js_minify_exclude', 'sgo_javascript_combine_exclude' ) as $hook )
		{
			add_filter( $hook, array( $this, 'map_opt_exclusions_handles' ) );
		}
		add_filter( 'sgo_rqs_exclude', array( $this, 'map_opt_exclusions_sgo_rqs' ) );
		add_filter( 'sgo_javascript_combine_excluded_internal_paths', array( $this, 'map_opt_exclusions_local' ) );
		add_filter( 'sgo_javascript_combine_excluded_external_paths', array( $this, 'map_opt_exclusions_host' ) );
		add_filter( 'sgo_javascript_combine_excluded_inline_content', array( $this, 'map_opt_exclusions_inline' ) );
		add_filter( 'sgo_css_combine_exclude', array( $this, 'map_opt_exclusions_style_handles' ) );
		add_filter( 'script_loader_src', array( $this, 'keep_asset_version' ), 10, 2 );
		add_filter( 'style_loader_src', array( $this, 'keep_asset_version' ), 10, 2 );
		add_action( 'wp', array( $this, 'warm_optimizer_exclusions' ) );
	}

	/**
	 * Builds the lists once, outside any output buffer callback.
	 * @access   public
	 */
	public function warm_optimizer_exclusions()
	{
		$this->get_optimizer_exclusions( 'paths' );
	}

	/**
	 * Exclusion entries of one kind: base, local, paths, host, inline, tail.
	 * @access   public
	 */
	public function get_optimizer_exclusions( $kind )
	{
		$lists = $this->optimizer_exclusions;

		if( !is_array( $lists ) )
		{
			$lists = $this->build_optimizer_exclusions();

			if( did_action( 'init' ) )
			{
				$this->optimizer_exclusions = $lists;
			}
		}

		return ( is_string( $kind ) && isset( $lists[ $kind ] ) ) ? $lists[ $kind ] : array();
	}

	private function build_optimizer_exclusions()
	{
		$base = $this->optimizer_url_path( plugin_dir_url( MAP_PLUGIN_FILENAME ) );

		if( basename( $base ) === '' )
		{
			$base = '';
		}

		$advanced = '';

		if( function_exists( 'map_call_advanced_get_bundle_url' ) )
		{
			try
			{
				$advanced = $this->optimizer_url_path( map_call_advanced_get_bundle_url() );
			}
			catch( Exception $e )
			{
				$advanced = '';
			}
			catch( Error $e )
			{
				$advanced = '';
			}
		}

		$host   = 'localcdn.myagileprivacy.com';
		$inline = array( 'var map_full_config=', 'var map_cookiebar_settings = ', 'var map_ajax = ', 'var early_starting_gconsent=' );
		$tail   = ( $base !== '' ) ? '/' . basename( $base ) . '/' : '';

		return array(
			'base'   => $this->clean_optimizer_entries( array( $base ) ),
			'local'  => $this->clean_optimizer_entries( array( $base, $advanced ) ),
			'paths'  => $this->clean_optimizer_entries( array( $base, $host, $advanced ) ),
			'host'   => $this->clean_optimizer_entries( array( $host ) ),
			'inline' => $this->clean_optimizer_entries( $inline ),
			'tail'   => $this->clean_optimizer_entries( array( $tail ) ),
		);
	}

	private function optimizer_url_path( $url )
	{
		if( !is_string( $url ) || $url === '' )
		{
			return '';
		}

		$parts = parse_url( $url );

		return ( is_array( $parts ) && isset( $parts['path'] ) && is_string( $parts['path'] ) ) ? $parts['path'] : '';
	}

	private function clean_optimizer_entries( $entries )
	{
		$out = array();

		foreach( $entries as $entry )
		{
			if( is_string( $entry ) && trim( $entry ) !== '' && !in_array( $entry, $out, true ) )
			{
				$out[] = $entry;
			}
		}

		return $out;
	}

	// always an array, as the receiving plugins type their own callbacks; a string is split on | only where that form exists
	private function merge_optimizer_list( $list, $entries, $split_string )
	{
		if( is_string( $list ) && $split_string )
		{
			$list = ( $list === '' ) ? array() : explode( '|', $list );
		}

		$list = ( $list === null || $list === '' ) ? array() : (array) $list;

		return array_merge( $list, $entries );
	}

	private function regex_optimizer_entries( $entries )
	{
		$out = array();

		foreach( $entries as $entry )
		{
			if( @preg_match( '#' . str_replace( '#', '\#', $entry ) . '#', '' ) !== false )
			{
				$out[] = $entry;
			}
		}

		return $out;
	}

	private function plain_optimizer_entries( $entries )
	{
		$out = array();

		foreach( $entries as $entry )
		{
			if( preg_match( '#^[A-Za-z0-9./_~%-]+$#', $entry ) )
			{
				$out[] = $entry;
			}
		}

		return $out;
	}

	public function map_opt_exclusions_litespeed( $list )
	{
		return $this->merge_optimizer_list( $list, array_merge( $this->plain_optimizer_entries( $this->get_optimizer_exclusions( 'paths' ) ), $this->get_optimizer_exclusions( 'inline' ) ), false );
	}

	public function map_opt_exclusions_rocket_delay( $list )
	{
		$markers = array();

		// matched as #…#i on the whole tag, after Rocket escapes + ?ver and # itself
		foreach( $this->get_optimizer_exclusions( 'inline' ) as $marker )
		{
			$markers[] = str_replace( array( '\+', '\?ver', '\#' ), array( '+', '?ver', '#' ), preg_quote( $marker ) );
		}

		return $this->merge_optimizer_list( $list, array_merge( $this->regex_optimizer_entries( $this->get_optimizer_exclusions( 'paths' ) ), $markers ), false );
	}

	public function map_opt_exclusions_rocket_paths( $list )
	{
		return $this->merge_optimizer_list( $list, $this->regex_optimizer_entries( $this->get_optimizer_exclusions( 'paths' ) ), false );
	}

	public function map_opt_exclusions_rocket_local( $list )
	{
		return $this->merge_optimizer_list( $list, $this->regex_optimizer_entries( $this->get_optimizer_exclusions( 'local' ) ), false );
	}

	public function map_opt_exclusions_local( $list )
	{
		return $this->merge_optimizer_list( $list, $this->get_optimizer_exclusions( 'local' ), false );
	}

	public function map_opt_exclusions_host( $list )
	{
		return $this->merge_optimizer_list( $list, $this->get_optimizer_exclusions( 'host' ), false );
	}

	public function map_opt_exclusions_inline( $list )
	{
		return $this->merge_optimizer_list( $list, $this->get_optimizer_exclusions( 'inline' ), true );
	}

	public function map_opt_exclusions_autoptimize( $exclude )
	{
		$entries = array_merge( $this->get_optimizer_exclusions( 'paths' ), $this->get_optimizer_exclusions( 'tail' ), $this->get_optimizer_exclusions( 'inline' ) );

		if( is_array( $exclude ) )
		{
			foreach( $entries as $entry )
			{
				if( !isset( $exclude[ $entry ] ) )
				{
					$exclude[ $entry ] = '';
				}
			}

			return $exclude;
		}

		if( $exclude === null )
		{
			$exclude = '';
		}

		if( !is_string( $exclude ) || !$entries )
		{
			return $exclude;
		}

		return ( trim( $exclude ) === '' ? '' : $exclude . ', ' ) . implode( ', ', $entries );
	}

	public function map_opt_exclusions_handles( $handles )
	{
		$handles = ( $handles === null || $handles === '' ) ? array() : (array) $handles;

		if( !isset( $GLOBALS['wp_scripts'] ) ||
			!( $GLOBALS['wp_scripts'] instanceof WP_Scripts ) ||
			!is_array( $GLOBALS['wp_scripts']->queue ) )
		{
			return $handles;
		}

		foreach( $GLOBALS['wp_scripts']->queue as $handle )
		{
			if( is_string( $handle ) && strpos( $handle, $this->plugin_name ) === 0 && !in_array( $handle, $handles, true ) )
			{
				$handles[] = $handle;
			}
		}

		return $handles;
	}

	/**
	 * Keeps the version query on the plugin assets served by this site.
	 * @access   public
	 */
	public function keep_asset_version( $src, $handle = null )
	{
		if( !defined( 'LSCWP_V' ) ||
			!is_string( $src ) ||
			!is_string( $handle ) ||
			strpos( $handle, $this->plugin_name ) !== 0 ||
			strpos( $src, '?' ) === false ||
			strpos( $src, '_litespeed_rm_qs=' ) !== false )
		{
			return $src;
		}

		$root_relative = substr( $src, 0, 1 ) === '/' && substr( $src, 0, 2 ) !== '//';

		$parts = $root_relative ? null : parse_url( $src );

		if( !$root_relative &&
			( !is_array( $parts ) ||
			!isset( $parts['host'] ) ||
			!is_string( $parts['host'] ) ||
			!in_array( strtolower( $parts['host'] ), $this->get_asset_hosts(), true ) ) )
		{
			return $src;
		}

		return add_query_arg( '_litespeed_rm_qs', '0', $src );
	}

	public function map_opt_exclusions_style_handles( $handles )
	{
		$handles = ( $handles === null || $handles === '' ) ? array() : (array) $handles;

		if( !isset( $GLOBALS['wp_styles'] ) ||
			!( $GLOBALS['wp_styles'] instanceof WP_Styles ) ||
			!is_array( $GLOBALS['wp_styles']->registered ) )
		{
			return $handles;
		}

		$own = array();

		foreach( array_keys( $GLOBALS['wp_styles']->registered ) as $handle )
		{
			if( is_string( $handle ) && strpos( $handle, $this->plugin_name ) === 0 && !in_array( $handle, $handles, true ) )
			{
				$own[] = $handle;
			}
		}

		return $this->merge_optimizer_list( $handles, $this->clean_optimizer_entries( $own ), false );
	}

	//the plugin URL builders can differ in host on multisite
	private function get_asset_hosts()
	{
		$hosts = array();

		foreach( array( plugin_dir_url( MAP_PLUGIN_FILENAME ), MyAgilePrivacy::get_plugin_url( MAP_PLUGIN_FILENAME ), home_url( '/' ) ) as $url )
		{
			$parts = is_string( $url ) ? parse_url( $url ) : false;

			if( is_array( $parts ) && isset( $parts['host'] ) && is_string( $parts['host'] ) && $parts['host'] !== '' )
			{
				$hosts[] = strtolower( $parts['host'] );
			}
		}

		return $hosts;
	}

	// the receiving plugin joins the entries with | inside ~ delimiters, without escaping them
	public function map_opt_exclusions_sgo_rqs( $list )
	{
		if( !doing_filter( 'script_loader_src' ) && !doing_filter( 'style_loader_src' ) )
		{
			return $list;
		}

		if( !is_array( $list ) )
		{
			$list = ( is_string( $list ) && $list !== '' ) ? array( $list ) : array();
		}

		$entries = array();

		foreach( $this->get_optimizer_exclusions( 'local' ) as $path )
		{
			$entry = '^[^?#]*' . preg_quote( $path, '~' ) . ( substr( $path, -3 ) === '.js' ? '' : '[^?#]*\.js' ) . '(?:[?#]|$)';

			if( !in_array( $entry, $list, true ) )
			{
				$entries[] = $entry;
			}
		}

		return $this->merge_optimizer_list( $list, $entries, false );
	}

	private $blocked_silent_keys = null;
	private $site_silent_keys = array();
	private $rocket_catalog = null;
	private $rocket_combined = null;
	private $rocket_optimized = false;
	private $optimizer_detected_keys = array();
	private $ao_urls = array();
	private $ao_excluded = array();
	private $ao_blocked_silent = array();

	/**
	 * Keys of the entries held without notice, read once per request.
	 * @access   private
	 */
	private function map_read_blocked_silent_keys()
	{
		if( is_array( $this->blocked_silent_keys ) )
		{
			return;
		}

		$this->blocked_silent_keys = array();

		foreach( $this->get_cookie_categories_description( '__blocked' ) as $group )
		{
			foreach( $group as $item )
			{
				if( isset( $item['api_key'] ) && is_string( $item['api_key'] ) && $item['api_key'] !== '' )
				{
					$this->site_silent_keys[] = $item['api_key'];
				}
			}
		}

		if( defined( 'WP_ROCKET_VERSION' ) || defined( 'AUTOPTIMIZE_PLUGIN_VERSION' ) )
		{
			$this->blocked_silent_keys = $this->site_silent_keys;
		}
	}

	private function map_is_blocked_silent_entry( $entry )
	{
		return is_array( $this->blocked_silent_keys ) &&
			isset( $entry['key'] ) &&
			is_string( $entry['key'] ) &&
			in_array( $entry['key'], $this->blocked_silent_keys, true );
	}

	private function map_register_rocket_catalog()
	{
		add_filter( 'rocket_exclude_js', array( $this, 'map_rocket_catalog_paths' ) );
		add_filter( 'rocket_minify_excluded_external_js', array( $this, 'map_rocket_catalog_external' ) );
		add_filter( 'rocket_excluded_inline_js_content', array( $this, 'map_rocket_catalog_inline' ) );
		add_filter( 'rocket_move_after_combine_js', array( $this, 'map_rocket_catalog_after' ) );

		// below 22, where the plugin minifies and combines scripts
		if( $this->scan_mode == 'learning_mode' )
		{
			add_filter( 'rocket_buffer', array( $this, 'map_detect_before_optimizer' ), 21 );
		}
	}

	/**
	 * Script node delayed by WP Rocket, with its address in data-rocket-src.
	 */
	private function map_is_rocket_delayed( $node )
	{
		if( $node->src )
		{
			return false;
		}

		$type = $node->type;
		$rocket_src = $node->getAttribute( 'data-rocket-src' );

		return $this->map_is_rocket_type( $type ) &&
			is_string( $rocket_src ) &&
			$rocket_src !== '';
	}

	private function map_script_src( $node )
	{
		return $this->map_is_rocket_delayed( $node ) ? $node->getAttribute( 'data-rocket-src' ) : $node->src;
	}

	private function map_remove_rocket_attributes( $node )
	{
		foreach( array( 'data-rocket-src', 'data-rocket-type' ) as $name )
		{
			if( $node->hasAttribute( $name ) )
			{
				$node->removeAttribute( $name );
			}
		}
	}

	/**
	 * Gives a delayed node back its own type before it is blocked.
	 */
	private function map_unwrap_rocket_tag( $node )
	{
		if( !$this->map_is_rocket_delayed( $node ) )
		{
			return;
		}

		$type = $node->getAttribute( 'data-rocket-type' );

		if( is_string( $type ) && $type !== '' )
		{
			$node->type = $type;
		}
		else
		{
			$node->removeAttribute( 'type' );
		}

		$this->map_remove_rocket_attributes( $node );
	}

	/**
	 * Makes a script node of an entry held without notice inert, on pages the optimizer has touched.
	 */
	private function map_silence_optimized_tag( $node, $entry )
	{
		if( !$this->map_script_action_silent( $entry ) ||
			!( $this->map_is_rocket_delayed( $node ) || $this->rocket_optimized || $this->ao_held_silent( $node ) ) )
		{
			return false;
		}

		$node->type = 'text/plain';
		$node->removeAttribute( 'src' );
		$this->map_remove_rocket_attributes( $node );

		return true;
	}

	/**
	 * State of a script block list entry: always_allowed, inactive, no_block or block.
	 */
	private function map_script_action_state( $entry )
	{
		$active = isset( $entry['active'] ) ? $entry['active'] : null;

		$no_action = ( !empty( $entry['only_on_compatibility_mode'] ) &&
			empty( $this->saved_settings['scanner_compatibility_mode'] ) );

		if( !empty( $entry['always_allowed'] ) )
		{
			$state = 'always_allowed';
		}
		elseif( !$active || $no_action )
		{
			$state = 'inactive';
		}
		elseif( isset( $entry['to_block'] ) && MyAgilePrivacy::looseEqualsPhp8( $entry['to_block'], 1 ) )
		{
			$state = 'block';
		}
		else
		{
			$state = 'no_block';
		}

		return array( 'state' => $state, 'active' => $active, 'no_action' => $no_action );
	}

	private function map_script_action_blocks( $entry )
	{
		$state = $this->map_script_action_state( $entry );

		return $state['state'] === 'block';
	}

	/**
	 * Entry held without notice and not published.
	 */
	private function map_script_action_silent( $entry )
	{
		return !empty( $entry['blocked_without_notification'] ) &&
			empty( $entry['active'] ) &&
			empty( $entry['always_allowed'] );
	}

	/**
	 * Block list entries kept out of the page optimizer, one list per filter.
	 * @access   public
	 */
	public function map_rocket_catalog_paths( $list )
	{
		return $this->rocket_catalog_merge( $list, 'paths' );
	}

	public function map_rocket_catalog_external( $list )
	{
		return $this->rocket_catalog_merge( $list, 'external' );
	}

	public function map_rocket_catalog_inline( $list )
	{
		return $this->rocket_catalog_merge( $list, 'inline' );
	}

	public function map_rocket_catalog_after( $list )
	{
		return $this->rocket_catalog_merge( $list, 'after' );
	}

	private function rocket_catalog_merge( $list, $kind )
	{
		$entries = array();

		try
		{
			if( is_array( $this->scan_config ) &&
				isset( $this->scan_config['scripts_src_block'] ) &&
				is_array( $this->scan_config['scripts_src_block'] ) &&
				!$this->rocket_page_inert() )
			{
				if( !is_array( $this->rocket_catalog ) )
				{
					$this->rocket_catalog = $this->build_rocket_catalog( $this->scan_config['scripts_src_block'] );
				}

				$this->rocket_optimized = true;

				$entries = $this->rocket_catalog[ $kind ];
			}
		}
		catch( Exception $e )
		{
			$entries = array();
		}
		catch( Error $e )
		{
			$entries = array();
		}

		return $this->merge_optimizer_list( $list, $entries, false );
	}

	// read on the first call, when the plugin has already chosen for this page
	private function rocket_page_combined()
	{
		if( $this->rocket_combined === null )
		{
			$this->rocket_combined = ( function_exists( 'get_rocket_option' ) &&
				get_rocket_option( 'minify_js' ) &&
				get_rocket_option( 'minify_concatenate_js' ) );
		}

		return $this->rocket_combined;
	}

	// catalog version check
	private function rocket_catalog_current()
	{
		return isset( $this->saved_settings['parse_config_version_number'] ) &&
			is_string( $this->saved_settings['parse_config_version_number'] ) &&
			version_compare( $this->saved_settings['parse_config_version_number'], '1.3.61', '>=' );
	}

	private function rocket_page_inert()
	{
		return $this->rocket_page_combined() && !$this->rocket_catalog_current();
	}

	private function build_rocket_catalog( $entries )
	{
		$external = array();
		$paths = array();
		$inline = array();
		$after = array();
		$held_keys = array();
		$call_forms = array();

		foreach( $entries as $entry )
		{
			if( !is_array( $entry ) )
			{
				continue;
			}

			$blocks = $this->map_script_action_blocks( $entry );
			$silent = $this->map_script_action_silent( $entry );
			$held = ( $blocks || $silent );
			$key = ( isset( $entry['key'] ) && is_string( $entry['key'] ) ) ? $entry['key'] : null;

			if( $held && $key !== null )
			{
				$held_keys[ $key ] = true;
			}

			// the forms of a key are the union over all its entries
			if( $key !== null && isset( $entry['call_forms'] ) && is_array( $entry['call_forms'] ) )
			{
				foreach( $entry['call_forms'] as $form )
				{
					$call_forms[ $key ][] = $form;
				}
			}

			if( !empty( $entry['plain_js'] ) && is_string( $entry['plain_js'] ) )
			{
				if( $blocks )
				{
					$inline[] = $entry['plain_js'];
				}
				elseif( $silent )
				{
					$after[] = $entry['plain_js'];
				}
			}

			if( empty( $entry['src'] ) || !is_string( $entry['src'] ) )
			{
				continue;
			}

			$pattern = $entry['src'];

			if( $held ||
				!empty( $entry['blocked_without_notification'] ) ||
				( isset( $entry['to_detect'] ) && MyAgilePrivacy::looseEqualsPhp8( $entry['to_detect'], 1 ) ) )
			{
				$host = $this->rocket_catalog_host( $pattern );

				if( $host !== '' )
				{
					$external[] = $host;
				}
				elseif( preg_match( '#^[A-Za-z0-9./_-]+$#', $pattern ) )
				{
					$external[] = $pattern;
				}
			}

			// a # would be escaped twice and void the whole list
			if( $held && strpos( $pattern, '#' ) === false )
			{
				$paths[] = preg_quote( $pattern );
			}
		}

		foreach( array_keys( $held_keys ) as $key )
		{
			if( isset( $call_forms[ $key ] ) )
			{
				$after = array_merge( $after, $call_forms[ $key ] );
			}
		}

		return array(
			'external' => $this->clean_optimizer_entries( $external ),
			'paths'    => $this->regex_optimizer_entries( $this->clean_optimizer_entries( $paths ) ),
			'inline'   => $this->clean_optimizer_entries( $inline ),
			'after'    => $this->clean_optimizer_entries( $after ),
		);
	}

	private function rocket_catalog_host( $pattern )
	{
		if( preg_match( '#^[a-z][a-z0-9+.-]*://#i', $pattern ) )
		{
			$host = parse_url( $pattern, PHP_URL_HOST );
		}
		else
		{
			$rest = ltrim( $pattern, '/' );
			$host = parse_url( '//' . $rest, PHP_URL_HOST );

			if( !is_string( $host ) || $host === '' || strpos( $rest, $host ) !== 0 )
			{
				$host = null;
			}
		}

		if( !is_string( $host ) ||
			!preg_match( '#^[a-z0-9.-]+\.[a-z]{2,}$#i', $host ) ||
			preg_match( '#\.(js|php)$#i', $host ) )
		{
			return '';
		}

		return $host;
	}

	/**
	 * Reads the page before the optimizer rewrites it; the page is returned unchanged.
	 * @access   public
	 */
	public function map_detect_before_optimizer( $buffer )
	{
		if( !is_string( $buffer ) ||
			$buffer === '' ||
			$this->scan_mode != 'learning_mode' ||
			!is_array( $this->scan_config ) ||
			!isset( $this->scan_config['scripts_src_block'] ) ||
			!is_array( $this->scan_config['scripts_src_block'] ) ||
			strlen( $buffer ) > MAP_SHD_MAX_FILE_SIZE ||
			!$this->map_is_html_response() )
		{
			return $buffer;
		}

		$dom = null;

		try
		{
			if( !$this->rocket_page_combined() )
			{
				return $buffer;
			}

			$this->map_html_scan_limits( strlen( $buffer ) );

			// half of the time budget, the page scan keeps its own
			MAP_Simple_Html_Dom::$deadline = ( microtime( true ) + MAP_Simple_Html_Dom::$deadline ) / 2;

			if( empty( $this->saved_settings['scanner_compatibility_mode'] ) )
			{
				$dom = map_shd_str_get_html( $buffer, true, true, false, false );
			}
			else
			{
				$dom = map_shd_str_get_html( $buffer, false, false, false, false );
			}

			if( is_object( $dom ) )
			{
				foreach( $dom->find( 'script' ) as $node )
				{
					$key = $this->map_detected_script_key( $node );

					if( $key !== null && !in_array( $key, $this->optimizer_detected_keys, true ) )
					{
						$this->optimizer_detected_keys[] = $key;
					}
				}
			}
		}
		catch( MAP_Simple_Html_Dom_Abort $e )
		{
			// the page goes on unchanged
		}
		catch( Exception $e )
		{
		}
		catch( Error $e )
		{
		}

		unset( $e );

		if( is_object( $dom ) )
		{
			$dom->clear();
		}

		MAP_Simple_Html_Dom::$deadline = 0;
		MAP_Simple_Html_Dom::$mem_limit = 0;

		return $buffer;
	}

	/**
	 * Block list key of a script node, or null.
	 */
	private function map_detected_script_key( $node )
	{
		$parent = $node->parentNode();

		if( $parent && $parent->tag == 'textarea' && $parent->hasClass( 'my_agile_privacy_activate' ) )
		{
			return null;
		}

		$class = is_string( $node->class ) ? $node->class : '';

		if( strpos( $class, 'my_agile_privacy_activate' ) !== false || strpos( $class, 'map_do_not_touch' ) !== false )
		{
			return null;
		}

		$entries = $this->scan_config['scripts_src_block'];
		$innertext = $node->innertext;
		$entry = null;

		if( !empty( $innertext ) )
		{
			foreach( $entries as $candidate )
			{
				if( is_array( $candidate ) &&
					!empty( $candidate['plain_js'] ) &&
					is_string( $candidate['plain_js'] ) &&
					strpos( $innertext, $candidate['plain_js'] ) !== false )
				{
					$entry = $candidate;
					break;
				}
			}
		}
		else
		{
			$src = $this->map_script_src( $node );

			if( is_string( $src ) && $src !== '' )
			{
				$found = $this->map_find_src_entry( $entries, array( $src ) );
				$entry = $found ? $found['entry'] : null;
			}
		}

		if( !is_array( $entry ) ||
			!empty( $entry['always_allowed'] ) ||
			!isset( $entry['to_detect'] ) ||
			!MyAgilePrivacy::looseEqualsPhp8( $entry['to_detect'], 1 ) ||
			!isset( $entry['key'] ) ||
			!is_string( $entry['key'] ) ||
			$entry['key'] === '' )
		{
			return null;
		}

		return $entry['key'];
	}

	// sorted, so the same keys always give the same stored value
	private function map_detected_keys_string( $keys )
	{
		$keys = array_values( array_unique( $keys ) );
		sort( $keys, SORT_STRING );

		return implode( ',', $keys );
	}

	private function map_register_ao_catalog()
	{
		add_filter( 'autoptimize_filter_cssjs_alter_url', array( $this, 'map_ao_catalog_alter_url' ), PHP_INT_MAX );
		add_filter( 'autoptimize_filter_base_apply_cdn', array( $this, 'map_ao_catalog_apply_cdn' ), PHP_INT_MAX, 2 );
		add_filter( 'autoptimize_filter_js_dontmove', array( $this, 'map_ao_catalog_js_dontmove' ), PHP_INT_MAX );
	}

	private function ao_catalog_ready()
	{
		return is_array( $this->scan_config ) &&
			isset( $this->scan_config['scripts_src_block'] ) &&
			is_array( $this->scan_config['scripts_src_block'] );
	}

	/**
	 * Script addresses of block list entries kept away from the page optimizer.
	 * @access   public
	 */
	public function map_ao_catalog_alter_url( $url )
	{
		try
		{
			if( !is_string( $url ) || $url === '' || !$this->ao_catalog_ready() )
			{
				return $url;
			}

			if( !isset( $this->ao_urls[ $url ] ) )
			{
				$this->ao_urls[ $url ] = $this->ao_catalog_url( $url );
			}

			return $this->ao_urls[ $url ];
		}
		catch( Exception $e )
		{
			return $url;
		}
		catch( Error $e )
		{
			return $url;
		}
	}

	public function map_ao_catalog_apply_cdn( $apply, $url = '' )
	{
		$result = $apply;

		try
		{
			if( is_string( $url ) &&
				$this->ao_catalog_ready() &&
				( isset( $this->ao_excluded[ $url ] ) || isset( $this->ao_blocked_silent[ $url ] ) ) )
			{
				$result = false;
			}
		}
		catch( Exception $e )
		{
			$result = $apply;
		}
		catch( Error $e )
		{
			$result = $apply;
		}

		return $result;
	}

	public function map_ao_catalog_js_dontmove( $dontmove )
	{
		try
		{
			$entries = array( 'unblocked_src' );

			if( $this->ao_catalog_ready() )
			{
				foreach( $this->scan_config['scripts_src_block'] as $entry )
				{
					if( is_array( $entry ) &&
						!empty( $entry['plain_js'] ) &&
						is_string( $entry['plain_js'] ) &&
						( $this->map_script_action_silent( $entry ) || $this->map_script_action_blocks( $entry ) ) )
					{
						$entries[] = $entry['plain_js'];
					}
				}
				$entries = array_merge( $entries, $this->ao_code_selected );
			}

			$list = $this->merge_optimizer_list( $dontmove, array(), false );
			$new = array();

			foreach( $this->clean_optimizer_entries( $entries ) as $value )
			{
				if( !preg_match( '/^\d+$/', $value ) && !in_array( $value, $list, true ) )
				{
					$new[] = $value;
				}
			}

			return array_merge( $list, $new );
		}
		catch( Exception $e )
		{
			return $this->merge_optimizer_list( $dontmove, array( 'unblocked_src' ), false );
		}
		catch( Error $e )
		{
			return $this->merge_optimizer_list( $dontmove, array( 'unblocked_src' ), false );
		}
	}

	private function ao_catalog_url( $url )
	{
		$parts = preg_split( '/[?#]/', $url, 2 );

		if( !is_array( $parts ) || substr( $parts[0], -3 ) !== '.js' )
		{
			return $url;
		}

		$found = $this->map_find_src_entry( $this->scan_config['scripts_src_block'], array( $url ) );

		if( !$found )
		{
			return $url;
		}

		if( $this->map_script_action_blocks( $found['entry'] ) )
		{
			$this->ao_excluded[ $url ] = true;
		}
		elseif( $this->map_script_action_silent( $found['entry'] ) )
		{
			$this->ao_blocked_silent[ $url ] = true;
		}
		else
		{
			return $url;
		}

		return '//map-excluded.invalid/' . md5( $url ) . '.js';
	}

	private function ao_held_silent( $node )
	{
		if( !$this->ao_blocked_silent )
		{
			return false;
		}

		$src = $this->map_script_src( $node );

		if( !is_string( $src ) || $src === '' )
		{
			return false;
		}

		$parts = explode( '?', $src, 2 );

		return isset( $this->ao_blocked_silent[ $parts[0] ] );
	}

	/**
	 * Video player API script entries, in video block mode.
	 */
	private function map_add_video_api_entries( $parse_config )
	{
		if( !isset( $this->saved_settings['video_privacy_mode'] ) ||
			$this->saved_settings['video_privacy_mode'] !== 'block' ||
			!isset( $parse_config['scripts_src_block'] ) ||
			!is_array( $parse_config['scripts_src_block'] ) )
		{
			return $parse_config;
		}

		foreach( array( array( '//www.youtube.com/iframe_api', null ), array( '//www.youtube.com/player_api', null ), array( '//www.youtube.com/s/', 'www-widgetapi.js' ) ) as $pattern )
		{
			$parse_config['scripts_src_block'][] = array(
				'active'        => false,
				'plain_js'      => null,
				'src'           => $pattern[0],
				'added_src'     => $pattern[1],
				'key'           => 'youtube',
				'to_detect'     => false,
				'to_block'      => true,
				'detected'      => null,
				'blocked'       => null,
				'map_video_api' => true,
			);
		}

		return $parse_config;
	}

	/**
	 * Leaves the video player API entries out on pages with a page builder video.
	 */
	private function map_drop_video_api_entries( $parse_config, $dom, $output )
	{
		if( !is_object( $dom ) ||
			!isset( $parse_config['scripts_src_block'] ) ||
			!is_array( $parse_config['scripts_src_block'] ) ||
			!is_string( $output ) )
		{
			return $parse_config;
		}

		$indexes = array();

		foreach( $parse_config['scripts_src_block'] as $index => $entry )
		{
			if( is_array( $entry ) && !empty( $entry['map_video_api'] ) )
			{
				$indexes[] = $index;
			}
		}

		if( !$indexes ||
			( strpos( $output, '//www.youtube.com/iframe_api' ) === false &&
				strpos( $output, '//www.youtube.com/player_api' ) === false &&
				strpos( $output, 'www-widgetapi.js' ) === false ) )
		{
			return $parse_config;
		}

		// same class list as the shield
		$builder_classes = array( 'elementor-widget-video', 'elementor-background-video-container', 'elementor-lightbox', 'avia-video', 'av-video-tmpl', 'elementor-video-iframe', 'elementor-background-video-embed' );
		$selectors = array();

		foreach( $builder_classes as $builder_class )
		{
			if( strpos( $output, $builder_class ) !== false )
			{
				$selectors[] = '.' . $builder_class;
			}
		}

		if( !$selectors || !$dom->find( implode( ', ', $selectors ), 0 ) )
		{
			return $parse_config;
		}

		// the player API tags of this page are left alone by the shield too
		$tick = 0;

		foreach( $dom->find( 'script[src]' ) as $node )
		{
			if( ( ++$tick & 255 ) === 0 ) { MAP_Simple_Html_Dom::guard(); }

			$src = $node->getAttribute( 'src' );

			if( !is_string( $src ) || $src === '' || $node->hasClass( 'map_not_to_block' ) ) continue;

			foreach( $indexes as $index )
			{
				$entry = $parse_config['scripts_src_block'][ $index ];

				if( strpos( $src, $entry['src'] ) !== false && ( empty( $entry['added_src'] ) || strpos( $src, $entry['added_src'] ) !== false ) )
				{
					$node->addClass( 'map_not_to_block' );
					break;
				}
			}
		}

		foreach( $indexes as $index )
		{
			unset( $parse_config['scripts_src_block'][ $index ] );
		}

		return $parse_config;
	}

	//true when the registered handle that printed the tag depends on jQuery
	private function map_src_needs_jquery( $node, $src )
	{
		global $wp_scripts;

		static $by_path = null;

		if( !is_object( $wp_scripts ) || !isset( $wp_scripts->registered ) || !is_array( $wp_scripts->registered ) ) return false;

		$registered = $wp_scripts->registered;
		$handle = null;

		$id = $node->getAttribute( 'id' );

		if( is_string( $id ) && strlen( $id ) > 3 && substr( $id, -3 ) === '-js' && isset( $registered[ substr( $id, 0, -3 ) ] ) )
		{
			$handle = substr( $id, 0, -3 );
		}
		elseif( is_string( $src ) && $src !== '' )
		{
			if( $by_path === null )
			{
				$by_path = array();

				foreach( $registered as $h => $dep )
				{
					if( !is_object( $dep ) || !isset( $dep->src ) || !is_string( $dep->src ) || $dep->src === '' ) continue;

					$p = parse_url( $dep->src );

					if( is_array( $p ) && isset( $p['path'] ) && is_string( $p['path'] ) && !isset( $by_path[ $p['path'] ] ) )
					{
						$by_path[ $p['path'] ] = (string) $h;
					}
				}
			}

			$p = parse_url( $src );

			if( is_array( $p ) && isset( $p['path'] ) && is_string( $p['path'] ) && isset( $by_path[ $p['path'] ] ) )
			{
				$handle = $by_path[ $p['path'] ];
			}
		}

		if( $handle === null ) return false;

		$jquery = array( 'jquery' => true, 'jquery-core' => true, 'jquery-migrate' => true );
		$queue = array( $handle );
		$seen = array();

		while( !empty( $queue ) )
		{
			$h = array_shift( $queue );

			if( isset( $seen[ $h ] ) ) continue;

			$seen[ $h ] = true;

			if( $h !== $handle && isset( $jquery[ $h ] ) ) return true;

			if( !isset( $registered[ $h ] ) || !is_object( $registered[ $h ] ) || !isset( $registered[ $h ]->deps ) || !is_array( $registered[ $h ]->deps ) ) continue;

			foreach( $registered[ $h ]->deps as $d )
			{
				if( is_string( $d ) && $d !== '' && !isset( $seen[ $d ] ) ) $queue[] = $d;
			}
		}

		return false;
	}

	/**
	 * Inline script of an entry held without notice and not published.
	 */
	private function map_inline_held_silent( $entry )
	{
		return isset( $entry['key'] ) &&
			is_string( $entry['key'] ) &&
			in_array( $entry['key'], $this->site_silent_keys, true ) &&
			empty( $entry['active'] ) &&
			empty( $entry['always_allowed'] );
	}

	//services a My Agile Pixel channel stands in for
	private static $map_pixel_twins = array( 'facebook_remarketing' => 'my_agile_pixel_fbq', 'google_analytics' => 'my_agile_pixel_ga', 'tik_tok' => 'my_agile_pixel_tiktok' );
	private $pixel_channels_in_page = array();

	/**
	 * My Agile Pixel channels whose held script is on the page, read before the page is changed.
	 */
	private function map_find_pixel_channels( $scripts )
	{
		$this->pixel_channels_in_page = array();

		if( !array_intersect( array_keys( self::$map_pixel_twins ), $this->site_silent_keys ) )
		{
			return;
		}

		$wanted = array_flip( self::$map_pixel_twins );
		$tick = 0;

		foreach( $scripts as $node )
		{
			if( ( ++$tick & 255 ) === 0 ) { MAP_Simple_Html_Dom::guard(); }

			$key = $node->getAttribute( 'data-cookie-api-key' );

			if( is_string( $key ) && isset( $wanted[ $key ] ) && !isset( $this->pixel_channels_in_page[ $key ] ) && $node->hasClass( 'map_inline_script_blocked' ) && !$this->map_inside_inert_tag( $node ) )
			{
				$this->pixel_channels_in_page[ $key ] = true;
			}
		}
	}

	/**
	 * Node inside an element whose content is not run as markup.
	 */
	private function map_inside_inert_tag( $node )
	{
		$inert = array( 'noscript', 'template', 'textarea', 'iframe', 'noembed', 'noframes', 'xmp', 'title', 'script', 'style', 'plaintext' );

		for( $p = $node->parentNode(); $p; $p = $p->parentNode() )
		{
			if( in_array( strtolower( (string) $p->tag ), $inert, true ) )
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Script run as a classic script by the browser, also when an optimizer has delayed it.
	 */
	private function map_is_classic_script( $node )
	{
		$type = $node->type;

		if( $this->map_is_rocket_type( $type ) )
		{
			$type = $node->getAttribute( 'data-rocket-type' );
		}

		if( $type === true )
		{
			$type = '';
		}

		if( !is_string( $type ) )
		{
			$language = $node->getAttribute( 'language' );

			if( !is_string( $language ) || $language === '' )
			{
				return true;
			}

			$type = strtolower( 'text/' . $language );
		}
		else
		{
			$type = strtolower( trim( $type, " \t\n\r\f" ) );
		}

		return in_array( $type, array( '', 'application/ecmascript', 'application/javascript', 'application/x-ecmascript', 'application/x-javascript',
			'text/ecmascript', 'text/javascript', 'text/javascript1.0', 'text/javascript1.1', 'text/javascript1.2', 'text/javascript1.3', 'text/javascript1.4',
			'text/javascript1.5', 'text/jscript', 'text/livescript', 'text/x-ecmascript', 'text/x-javascript' ), true );
	}

	/**
	 * Script node with an attribute name the parser kept in upper case.
	 */
	private function map_has_upper_attributes( $node )
	{
		$attributes = $node->getAllAttributes();

		foreach( is_array( $attributes ) ? array_keys( $attributes ) : array() as $name )
		{
			if( is_string( $name ) && strtolower( $name ) !== $name &&
				in_array( strtolower( $name ), array( 'type', 'language', 'src', 'class', 'data-cookie-api-key' ), true ) )
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Script node the shield and the page filter leave alone.
	 */
	private function map_is_untouchable( $node )
	{
		$class = (string) $node->class;

		foreach( array( 'my_agile_privacy_do_not_touch', 'map_do_not_touch', 'map_not_to_block' ) as $name )
		{
			if( strpos( $class, $name ) !== false )
			{
				return true;
			}
		}

		return false;
	}

	private function map_is_rocket_type( $type )
	{
		return is_string( $type ) && in_array( strtolower( trim( $type ) ), array( 'rocketlazyloadscript', 'text/rocketlazyloadscript' ), true );
	}

	/**
	 * Inline script of a held service whose My Agile Pixel channel is on the page.
	 */
	private function map_inline_held_twin( $entry, $node )
	{
		return $this->map_inline_held_silent( $entry ) &&
			isset( self::$map_pixel_twins[ $entry['key'] ] ) &&
			isset( $this->pixel_channels_in_page[ self::$map_pixel_twins[ $entry['key'] ] ] ) &&
			( empty( $entry['only_on_compatibility_mode'] ) || !empty( $this->saved_settings['scanner_compatibility_mode'] ) ) &&
			!$this->map_is_untouchable( $node ) &&
			!$this->map_has_upper_attributes( $node ) &&
			$this->map_is_classic_script( $node );
	}

	private $ao_code_patterns = array();
	private $ao_code_selected = array();

	/** Code patterns of the dependencies of published entries to block. */
	private function ao_select_code_patterns( $enabled, $not_block, $always_allow )
	{
		$selected = array();

		foreach( $this->ao_code_patterns as $item )
		{
			if( is_string( $item[0] ) &&
				is_string( $item[1] ) &&
				in_array( $item[0], $enabled, true ) &&
				!in_array( $item[0], $not_block, true ) &&
				!in_array( $item[0], $always_allow, true ) )
			{
				$selected[] = $item[1];
			}
		}

		$this->ao_code_selected = $selected;
		$this->ao_code_patterns = array();
	}
}