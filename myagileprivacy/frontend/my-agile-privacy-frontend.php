<?php

if( !defined( 'MAP_PLUGIN_NAME' ) )
{
	exit('Not allowed.');
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
class MyAgilePrivacyFrontend {

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
	private $cookie_post_id;
	private $scan_config;
	private $scan_output;
	private $scan_log;
	private $scan_done;
	private $saved_settings;
	private $saved_post;
	private $head_script;

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
			( !isset( $_GET['et_fb'] ) || $_GET['et_fb'] != 1 ) &&
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

		return load_textdomain( $domain, $path . '/' . $mofile );
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
		$text_size_style = 'font-size:'.esc_attr( $the_settings['text_size'] ).'px!important';

		$class = ' class="map-button map-button-style map-accept-button"';

		$animation_attrs = '';

		// colore icona = colore testo
		$icon_background_style = ( $the_settings['button_accept_link_color'] != "" ) ? ' background-color:'.esc_attr( $the_settings['button_accept_link_color'] ).' !important' : '';

		$link_tag = '<a role="button" tabindex="0" aria-pressed="false" data-map_action="accept" id="map-accept-button"'. $class.' style="'.esc_attr( $margin_style ).'; '. esc_attr( $color_style ).'; '.esc_attr( $background_style) .'; '.esc_attr( $border_radius_style ).'; '.esc_attr( $text_size_style ).';" '.esc_attr( $animation_attrs ).'>';

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
		$text_size_style = 'font-size:'.esc_attr( $the_settings['text_size'] ).'px !important';

		$class = ' class="map-button map-button-style map-reject-button"';

		// colore icona = colore testo
		$icon_background_style = ( $the_settings['button_reject_link_color'] != "" ) ? ' background-color:'.esc_attr( $the_settings['button_reject_link_color'] ).' !important' : '';

		$link_tag = '<a role="button" tabindex="0" aria-pressed="false" data-map_action="reject" id="map-reject-button"'. $class.' style="'.esc_attr( $margin_style ).'; '.esc_attr( $color_style) .'; '.esc_attr( $background_style ).'; '.esc_attr( $border_radius_style ).'; '.esc_attr( $text_size_style ).';">';

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
		$text_size_style = 'font-size:'.esc_attr( $the_settings['text_size'] ).'px !important';

		$class = ' class="map-button map-button-style map-customize-button"';

		// colore icona = colore testo
		$icon_background_style = ( $the_settings['button_customize_link_color'] != "" ) ? ' background-color:'.esc_attr( $the_settings['button_customize_link_color'] ).' !important ' : '';

		$link_tag = '<a role="button" tabindex="0" aria-pressed="false" data-map_action="customize" id="map-customize-button"'. $class.' style="'.esc_attr( $margin_style ).'; '.esc_attr( $color_style ) .'; '.esc_attr( $background_style ) .'; '.esc_attr( $border_radius_style ).'; '.esc_attr( $text_size_style ).';">';

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
				$the_cookie_policy_url = get_permalink( icl_object_id( $cookie_policy_page, 'page', true) );
			}
			elseif( $is_polylang_enabled )
			{
				$the_cookie_policy_url = get_permalink( pll_get_post( $cookie_policy_page ) );
			}
			else
			{
				$the_cookie_policy_url = get_permalink( $cookie_policy_page );
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
				$the_personal_data_policy_url = get_permalink( icl_object_id( $personal_data_policy_page, 'page', true) );
			}
			elseif( $is_polylang_enabled )
			{
				$the_personal_data_policy_url = get_permalink( pll_get_post( $personal_data_policy_page ) );
			}
			else
			{
				$the_personal_data_policy_url = get_permalink( $personal_data_policy_page );
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

			if( defined( 'MAP_MY_AGILE_PIXEL_TEXT_FIX' ) && MAP_MY_AGILE_PIXEL_TEXT_FIX )
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
			else
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

			if( count( $found_items ) > 0 )
			{
				$found_items_string = implode( ', ', $found_items );

				$output_text .= ' '.esc_html( $the_translations[ $current_lang ]['in_addition_this_site_installs'] ).' '.$found_items_string.' '.esc_html( $the_translations[ $current_lang ]['with_anonymous_data_transmission_via_proxy'] )." ".esc_html( $the_translations[ $current_lang ]['by_giving_your_consent_the_data_will_be_sent_anonymously'] );
				;
			}
		}

		if( $iab_tcf_context )
		{
			$output_text .= ' '.esc_html( $the_translations[ $current_lang ]['iab_bannertext_1'] ).' '.esc_html( $the_translations[ $current_lang ]['iab_bannertext_2_a'] ).' <a href="#" class="map-triggerGotoIABTCF">'.esc_html( $the_translations[ $current_lang ]['iab_bannertext_2_link'] ).'</a> '.esc_html( $the_translations[ $current_lang ]['iab_bannertext_2_b'] ).'<br><br>'.esc_html( $the_translations[ $current_lang ]['iab_bannertext_3'] ).':<br><span id="map-vendor-stack-description"></span><br>';

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

		$remove_dpo_text = true;
		$remove_dpo_other_text = true;
		$remove_ccpa_text = true;
		$remove_lpd_text = true;

		if( isset( $the_settings['pa'] ) &&
			$the_settings['pa'] == 1 &&
			isset( $rconfig ) && $rconfig['allow_dpo_edit'] &&
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
			isset( $rconfig ) && $rconfig['allow_ccpa_text'] &&
			isset( $templateConfig['map_ccpa_text'] ) && $templateConfig['map_ccpa_text'] == 1 )
		{
			$remove_ccpa_text = false;
		}

		if( isset( $the_settings['pa'] ) &&
			$the_settings['pa'] == 1 &&
			isset( $rconfig ) && $rconfig['allow_lpd_text'] &&
			isset( $templateConfig['map_lpd_text'] ) && $templateConfig['map_lpd_text'] == 1 )
		{
			$remove_lpd_text = false;
		}

		$cookies_categories_data = $this->get_cookie_categories_description( 'publish' );
		$cookie_list_html = "";

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
				}

				$cookie_list_html .= "<p><b>".$value['post_data']->post_title."</b><br>";
				$cookie_list_html .= $value['post_data']->post_content."</p>";
			}
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

				if( $currentAndSupportedLanguages['with_multilang'] )
				{
					$lang = $currentAndSupportedLanguages['current_language'];

					$post_custom = get_post_custom( $the_id );
					$_map_translations_decoded = ( isset( $post_custom["_map_translations"][0] ) ) ? json_decode( $post_custom["_map_translations"][0], true )  : null;

					if( $_map_translations_decoded )
					{
						if( isset( $_map_translations_decoded[$lang] ) )
						{
							$content = $_map_translations_decoded[ $lang ]['text'];
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
								$language_key = substr( $the_settings['language_fallback_locale'], 0, 2 );
							}
							else
							{
								$language_key = $currentAndSupportedLanguages['multilang_default_lang'];
							}


							$content = $_map_translations_decoded[ $language_key ]['text'];
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
					$the_url = get_permalink( pll_get_post( $cookie_policy_page ) );
				}
				else
				{
					$the_url = get_permalink( $cookie_policy_page );
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
					$the_url = get_permalink( pll_get_post( $personal_data_policy_page ) );
				}
				else
				{
					$the_url = get_permalink( $personal_data_policy_page );
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
		$the_settings = MyAgilePrivacy::get_settings();
		$rconfig = MyAgilePrivacy::get_rconfig();

		//get translations
		$the_translations = MyAgilePrivacy::getFixedTranslations();
		$current_lang = MyAgilePrivacy::getCurrentLang4Char();

		if( !is_admin() &&
			//elementor
			!isset( $_GET['elementor-preview'] ) &&
			//divi
			( !isset( $_GET['et_fb'] ) || $_GET['et_fb'] != 1 ) &&
			//thrive theme builder
			( !isset( $_GET['action'] ) || $_GET['action'] != 'architect' ) &&
			( !isset( $_GET['tve'] ) || $_GET['tve'] != 'true' ) &&
			$the_settings['is_on'] == true
		)
		{
			$skip = MyAgilePrivacy::check_buffer_skip_conditions( true );

			//skip banner display on skip rules
			if( $skip == 'true' )
			{
				return;
			}

			$is_polylang_enabled = $this->check_if_polylang_enabled();

			$is_wpml_enabled = $this->check_if_wpml_enabled();

			$microsoft_consent_mode_consents = null;

			if( isset( $the_settings['pa'] ) &&
				$the_settings['pa'] == 1 &&
				isset( $the_settings['enable_microsoft_cmode'] ) && $the_settings['enable_microsoft_cmode'] )
			{
				$microsoft_consent_mode_consents = array();

				$item = array(
					'key'			=>	'microsoft_ad_storage',
					'human_name'  	=>	esc_html( $the_translations[ $current_lang ]['ad_storage'] ),
					'human_desc'	=>	esc_html( $the_translations[ $current_lang ]['ad_storage_microsoft_desc'] ),
				);

				$microsoft_consent_mode_consents[] = $item;
			}

			$consent_mode_consents = null;

			if( isset( $the_settings['pa'] ) &&
				$the_settings['pa'] == 1 &&
				isset( $the_settings['enable_cmode_v2'] ) && $the_settings['enable_cmode_v2'] &&
				!( isset( $the_settings['bypass_cmode_enable'] ) && $the_settings['bypass_cmode_enable'] == true )
			)
			{
				$consent_mode_consents = array();

				$item = array(
					'key'			=>	'ad_storage',
					'human_name'  	=>	esc_html( $the_translations[ $current_lang ]['ad_storage'] ),
					'human_desc'	=>	esc_html( $the_translations[ $current_lang ]['ad_storage_desc'] ),
				);

				$consent_mode_consents[] = $item;

				$item = array(
					'key'			=>	'ad_user_data',
					'human_name'  	=>	esc_html( $the_translations[ $current_lang ]['ad_user_data'] ),
					'human_desc'	=>	esc_html( $the_translations[ $current_lang ]['ad_user_data_desc'] ),
				);

				$consent_mode_consents[] = $item;

				$item = array(
					'key'			=>	'ad_personalization',
					'human_name'  	=>	esc_html( $the_translations[ $current_lang ]['ad_personalization'] ),
					'human_desc'	=>	esc_html( $the_translations[ $current_lang ]['ad_personalization_desc'] ),
				);

				$consent_mode_consents[] = $item;

				$item = array(
					'key'			=>	'analytics_storage',
					'human_name'  	=>	esc_html( $the_translations[ $current_lang ]['analytics_storage'] ),
					'human_desc'	=>	esc_html( $the_translations[ $current_lang ]['analytics_storage_desc'] ),
				);

				$consent_mode_consents[] = $item;
			}

			require_once plugin_dir_path(__FILE__) . 'views/my-agile-privacy-notify.php';
		}
	}

	/**
	 * head inject
	 * @access   public
	*/
	public function wp_head_inject()
	{
		$skip = MyAgilePrivacy::check_buffer_skip_conditions( false );

		if( $skip == 'false' )
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
		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'plugin_init' );

		global $sitepress;
		if( function_exists( 'icl_object_id' ) && $sitepress )
		{
			if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'add filter wpml_config_array' );

			add_filter( 'wpml_config_array', array( $this, 'map_wpml_config_array' ) );
		}

		$labels = array(
			'name'					=> wp_kses_post( __( 'My Agile Privacy', 'MAP_txt' ) ),
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
				'publish_posts' => 'manage_options',
				'edit_posts' => 'manage_options',
				'edit_others_posts' => 'manage_options',
				'delete_posts' => 'manage_options',
				'delete_others_posts' => 'manage_options',
				'read_private_posts' => 'manage_options',
				'edit_post' => 'manage_options',
				'delete_post' => 'manage_options',
				'read_post' => 'manage_options',
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
			'name'					=> wp_kses_post( __( 'Policies', 'MAP_txt' ) ),
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

		$args = array(
			'labels'				=> $labels,
			'public'				=> false,
			'publicly_queryable'	=> false,
			'exclude_from_search'	=> true,
			'show_ui'				=> true,
			'query_var'				=> true,
			'rewrite'				=> true,
			'capabilities' => array(
				'publish_posts' => 'manage_options',
				'edit_posts' => 'manage_options',
				'edit_others_posts' => 'manage_options',
				'delete_posts' => 'do_not_allow',
				'delete_others_posts' => 'manage_options',
				'read_private_posts' => 'manage_options',
				'edit_post' => 'manage_options',
				'delete_post' => 'manage_options',
				'read_post' => 'manage_options',
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
			( !isset( $_GET['et_fb'] ) || $_GET['et_fb'] != 1 ) &&
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

			if( isset( $rconfig ) &&
				isset( $rconfig['use_css_reset'] ) &&
				$rconfig['use_css_reset'] == 1 )
			{
				wp_enqueue_style( $this->plugin_name.'-reset', plugin_dir_url(__FILE__) . 'css/my-agile-privacy-reset.css', array(), $this->version, 'all' );
			}

			wp_enqueue_style( $this->plugin_name.'-animate', plugin_dir_url(__FILE__) . 'css/animate.min.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name, plugin_dir_url(__FILE__) . 'css/my-agile-privacy-frontend.css', array(), $this->version, 'all' );

			if( !function_exists( 'is_plugin_active' ) )
			{
				include_once(ABSPATH . 'wp-admin/includes/plugin.php' );
			}

			if( !is_plugin_active( 'myagilepixel/myagilepixel.php' ) )
			{
				wp_enqueue_style( $this->plugin_name.'-notification-bar', plugin_dir_url(__FILE__) . 'css/my-agile-privacy-notification-bar.css', array(), $this->version, 'all' );
			}
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

			if( $skip == 'true_due_to_post' )
			{
				wp_enqueue_script( $this->plugin_name, plugin_dir_url(__FILE__) . 'js/empty.js', array(), $this->version, false );

				$blocks_to_inject = $this->get_head_script_string( true );

				foreach( $blocks_to_inject['inline'] as $k => $elem )
				{
					wp_add_inline_script( $this->plugin_name, $elem );
				}

				foreach( $blocks_to_inject['enqueue'] as $k => $elem )
				{
					wp_enqueue_script( $this->plugin_name.'_enqueue_'.$k, $elem, array( 'jquery' ), $this->version, false );
				}
			}

			$js_frontend_filepath = 'js/plain/my-agile-privacy-frontend.js';

			wp_enqueue_script( $this->plugin_name.'-anime', plugin_dir_url(__FILE__) . 'js/anime.min.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( $this->plugin_name, plugin_dir_url(__FILE__) . $js_frontend_filepath, array( 'jquery' ), $this->version, false );

			wp_localize_script( $this->plugin_name, 'map_cookiebar_settings', MyAgilePrivacy::get_json_settings() );

			$cookie_process_delayed_mode = false;

			if( $rconfig &&
				isset( $rconfig['cookie_process_delayed_mode'] ) &&
				$rconfig['cookie_process_delayed_mode'] == 1
			)
			{
				$cookie_process_delayed_mode = true;
			}

			wp_localize_script( $this->plugin_name, 'map_ajax',
				array(
					'ajax_url' 						=> 	admin_url( 'admin-ajax.php' ),
					'security' 						=> 	wp_create_nonce( 'map_js_shield_callback' ),
					'force_js_learning_mode' 		=> 	$rconfig['force_js_learning_mode'],
					'scanner_compatibility_mode'	=> 	$the_settings['scanner_compatibility_mode'],
					'cookie_process_delayed_mode'	=> 	intval( $cookie_process_delayed_mode ),
			) );

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
		if ( isset( $attributes['id'] ) && is_string( $attributes['id'] ) )
		{
			$inline_id = $attributes['id'];
		}
		elseif( is_string( $id ) )
		{
			$inline_id = $id;
		}

		// Only target inline scripts tied to this handle.
		// WP uses "{$handle}-js-extra|before|after".
		$prefix = $this->plugin_name . '-js-';
		if ( $inline_id === '' || strpos( $inline_id, $prefix ) !== 0 )
		{
			return $attributes;
		}

		// Parse the attributes string constant into an associative array.
		$extra = $this->parse_attrs_string( MAP_INLINE_SCRIPT_EXTRA_ATTRS );

		// Merge without overwriting existing attributes.
		foreach ( $extra as $name => $value )
		{
			if ( array_key_exists( $name, $attributes ) )
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
				if ( $name === '' )
				{
					continue;
				}

				if ( isset( $m[2] ) && $m[2] !== '' )
				{
					$val = trim( $m[2] );

					// Strip surrounding quotes if present.
					$first = substr( $val, 0, 1 );
					$last  = substr( $val, -1 );
					if ( ( $first === '"' && $last === '"' ) || ( $first === "'" && $last === "'" ) )
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
			'posts_per_page'   => 	-1,
			'post_type'        =>	MAP_POST_TYPE_COOKIES,
			'post_status' 	   => 	$post_status,
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
								if( isset( $_map_translations_decoded[ $lang ] ) )
								{
									$elem['post_title'] = $_map_translations_decoded[ $lang ]['name'];
									$elem['post_content'] = $_map_translations_decoded[ $lang ]['text'];
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
										$language_key = substr( $the_settings['language_fallback_locale'], 0, 2 );
									}
									else
									{
										$language_key = $currentAndSupportedLanguages['multilang_default_lang'];
									}

									if( isset( $_map_translations_decoded[ $language_key ] ) )
									{
										$elem['post_title'] = $_map_translations_decoded[ $language_key ]['name'];
										$elem['post_content'] = $_map_translations_decoded[ $language_key ]['text'];
									}
									else
									{
										$elem['post_title'] = '';
										$elem['post_content'] = '';
									}
								}
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

							//advanced consent mode ga4
							if( ( $elem['api_key'] == 'google_analytics' || $elem['api_key'] == 'my_agile_pixel_ga' ) &&
								isset( $the_settings ) &&
								isset( $the_settings['cmode_v2_implementation_type'] ) &&
								$the_settings['cmode_v2_implementation_type'] == 'native' &&
								isset( $the_settings['cmode_v2_forced_off_ga4_advanced'] ) &&
								$the_settings['cmode_v2_forced_off_ga4_advanced'] )
							{
								$the_key = 'not-necessary';
							}

							$cookie_categories[ $the_key ][] = $elem;
						}
					}
				}
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

				$the_settings['last_scan_date_internal'] = strtotime( "now" );
				MyAgilePrivacy::update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_settings );
			}

			$parse_config = array();

			if( isset( $this->saved_settings['parse_config'] ) )
			{
				$parse_config = json_decode( $this->saved_settings['parse_config'], true );
			}

			//fix
			if( !$parse_config )
			{
				$parse_config = array();
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
								}
							}
						}
					}
				}
			}

			//bof _map_js_dependencies inject

			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $parse_config );

			$currentAndSupportedLanguages = MyAgilePrivacy::getCurrentAndSupportedLanguages();

			//check if exists

			$cc_args = array(
				'posts_per_page'   	=> 	-1,
				'post_type'        	=>	MAP_POST_TYPE_COOKIES,
				'post_status' 		=> 	$post_status_to_search,
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

									if( $this->scan_mode == 'learning_mode' )
									{
										$this->cookie_post_id[] = $main_post_id;
									}
								}

								if( $this_post_status == '__always_allowed' )
								{
									$key_to_always_allow[] = $the_key;
								}
							}
						}
					}
				}

				MyAgilePrivacy::internal_query_reset();
			}

			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $key_to_enable );
			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $key_to_always_allow );
			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $friendly_name_map );

			foreach( $parse_config as $k => &$v )
			{
				foreach( $v as $kk => &$vv )
				{
					if( is_array( $key_to_enable ) &&
						in_array( $vv['key'], $key_to_enable ) )
					{
						$vv['active'] = true;

						if( isset( $friendly_name_map[ $vv['key'] ] ) )
						{
							$vv['friendly_name'] = $friendly_name_map[ $vv['key'] ];
						}

						if( is_array( $key_to_not_block ) &&
							in_array( $vv['key'], $key_to_not_block ) )
						{
							$vv['to_block'] = false;
						}

						$vv['always_allowed'] = false;
					}

					if( is_array( $key_to_always_allow ) &&
						in_array( $vv['key'], $key_to_always_allow ) )
					{
						$vv['always_allowed'] = true;
					}

				}
			}

			$this->scan_config = $parse_config;

			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $parse_config );

			$logic_legacy_mode = false;

			if( $rconfig &&
				isset( $rconfig['js_legacy_mode'] ) &&
				$rconfig['js_legacy_mode'] == 1 ||
				( $the_settings['scanner_compatibility_mode'] && $the_settings['forced_legacy_mode'] ) ||
				$the_settings['missing_cookie_shield']
			)
			{
				$logic_legacy_mode = true;
			}

			if( !$logic_legacy_mode )
			{
				$this->head_script = $this->get_head_script_string( false );
			}

			ob_start( array( $this, 'map_callback' ) );
		}
	}

	/**
	 * buffer end
	 * @access   public
	*/
	public function map_buffer_end()
	{
		if( ob_get_level() )
		{
			ob_end_flush();
		}
	}

	/**
	 * get head script string
	 * @access   public
	*/
	public function get_head_script_string( $block_mode = false )
	{

		$the_settings = MyAgilePrivacy::get_settings();

		$rconfig = MyAgilePrivacy::get_rconfig();

		$manifest_assoc = null;

		if( !MAP_DEV_MODE &&
			$rconfig &&
			isset( $rconfig['allow_manifest'] ) &&
			$rconfig['allow_manifest']
		)
		{
			$manifest_assoc = MyAgilePrivacy::get_option( MAP_MANIFEST_ASSOC, null );

			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $manifest_assoc );
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

		//populating cookie_api_key_remote_id_map_active
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
			'js_patterns_src'	=> array(),
		);

		foreach( $cookies_categories_data as $k => $v )
		{
			foreach( $v as $key => $value )
			{
				$the_post_title = $value['post_title'];
				$the_remote_id = $value['remote_id'];
				$the_post_api_key = isset( $value['post_meta']['_map_api_key'][0] ) ? $value['post_meta']['_map_api_key'][0] : null;
				$is_necessary = isset( $value['post_meta']['_map_is_necessary'][0] ) ? $value['post_meta']['_map_is_necessary'][0] : 'not-necessary';

				//advanced consent mode ga4
				if( ( $the_post_api_key == 'google_analytics' || $the_post_api_key == 'my_agile_pixel_ga' ) &&
					isset( $the_settings ) &&
					isset( $the_settings['cmode_v2_implementation_type'] ) &&
					$the_settings['cmode_v2_implementation_type'] == 'native' &&
					isset( $the_settings['cmode_v2_forced_off_ga4_advanced'] ) &&
					$the_settings['cmode_v2_forced_off_ga4_advanced'] )
				{
					$is_necessary = 'not-necessary';
				}

				$_map_js_dependencies = ( isset( $value['post_meta']["_map_js_dependencies"][0] ) ) ? $value['post_meta']["_map_js_dependencies"][0] : '';

				$_map_js_dependencies_array = json_decode( $_map_js_dependencies , true );

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
							'desc'			=>	$the_post_title,
							'is_necessary'	=> ( $is_necessary ) ? true : false,
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
										"src" 					=> 	$single_map_js_dependencies['value'],
										"added_src" 			=> 	null,
										"negative_src" 			=> 	null,
										"key" 					=> 	$the_post_api_key,
										"on_block_add_classes" 	=> 	'mapWait',
										"do_forced_delay"		=>	true,
									);

									$shield_added_pattern[ 'js_patterns_src' ][] = $item;

									break;
								case 'js_patterns_code':

									$item = array(
										"key" 					=> 	$the_post_api_key,
										"plain_pattern" 		=> 	$single_map_js_dependencies['value'],
										"on_block_add_classes" 	=> 	'mapWait',
										"do_forced_delay"		=>	true,
									);

									$shield_added_pattern[ 'js_patterns_code' ][] = $item;

									break;
							}
						}
					}
				}
			}
		}

		//populating cookie_api_key_remote_id_map_detectable
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
							'desc'			=>	$the_post_title,
							'is_necessary'	=> ( $is_necessary ) ? true : false,
						);

						$cookie_api_key_friendly_name_map[ $the_post_api_key ] = $this_friendly_elem;
					}
				}
			}
		}

		//populating cookie_api_key_remote_id_map_blocked_without_notification
		$cookies_categories_data_blocked_without_notification = $this->get_cookie_categories_description( '__blocked' );
		$cookie_api_key_remote_id_map_blocked_without_notification = array();

		foreach( $cookies_categories_data_blocked_without_notification as $k => $v )
		{
			foreach( $v as $key => $value )
			{
				$the_post_title = $value['post_title'];
				$the_remote_id = $value['remote_id'];
				$the_post_api_key = isset( $value['post_meta']['_map_api_key'][0] ) ? $value['post_meta']['_map_api_key'][0] : null;

				if( $the_remote_id && $the_post_api_key )
				{
					$cookie_api_key_remote_id_map_blocked_without_notification[ $the_post_api_key ] = 'map_cookie_'.$the_remote_id.$cookie_reset_timestamp_rich;
				}
			}
		}

		//populating map_cookies_always_allowed
		$cookies_categories_data_always_allowed = $this->get_cookie_categories_description( '__always_allowed' );
		$map_cookies_always_allowed = array();

		foreach( $cookies_categories_data_always_allowed as $k => $v )
		{
			foreach( $v as $key => $value )
			{
				$the_post_title = $value['post_title'];
				$the_remote_id = $value['remote_id'];
				$the_post_api_key = isset( $value['post_meta']['_map_api_key'][0] ) ? $value['post_meta']['_map_api_key'][0] : null;

				if( $the_remote_id && $the_post_api_key )
				{
					$map_cookies_always_allowed[] = $the_post_api_key;
				}
			}
		}

		//bof iab tcf part
		$map_js_basedirectory = plugin_dir_url(__FILE__);

		if( MAP_DEV_MODE )
		{
			$map_js_basedirectory .= '../dev/MyAgilePrivacyIabTCF/';
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

		$map_lang_code = substr( $map_lang_code_4char, 0, 2 );
		//eof iab tcf part

		$cookie_reset_timestamp = ( isset( $the_settings['cookie_reset_timestamp'] ) ) ? $the_settings['cookie_reset_timestamp'] : null;
		$video_advanced_privacy = ( isset( $the_settings['video_advanced_privacy'] ) ) ? intval( $the_settings['video_advanced_privacy'] ) : 0;
		$enforce_youtube_privacy = ( isset( $the_settings['enforce_youtube_privacy'] ) ) ? intval( $the_settings['enforce_youtube_privacy'] ) : 0;
		$enforce_youtube_privacy_v2 = 0;

		if( $enforce_youtube_privacy &&
			isset( $rconfig ) &&
			isset( $rconfig['enforce_youtube_privacy_v2'] ) &&
			$rconfig['enforce_youtube_privacy_v2'] )
		{
			$enforce_youtube_privacy_v2 = 1;
		}

		$mapx_ga4 = 0;

		if( !( $rconfig &&
			isset( $rconfig['disable_mapx_header_check'] ) &&
			$rconfig['disable_mapx_header_check'] == 1 )
		)
		{
			if( !function_exists( 'is_plugin_active' ) )
			{
				include_once(ABSPATH . 'wp-admin/includes/plugin.php' );
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
			'inline' 	=> array(),
			'enqueue'	=> array(),
		);

		$head_script = '';

		if( isset( $the_settings['pa'] ) &&
			$the_settings['pa'] == 1 )
		{
			if( $enforce_youtube_privacy && !$enforce_youtube_privacy_v2 )
			{
				$the_script = plugin_dir_url(__FILE__).'js/youtube_enforced_privacy.js';

				$head_script .= '<script class="map_advanced_shield_youtube_enforced_privacy" type="text/javascript" src="'.$the_script.'" '.MAP_INLINE_SCRIPT_EXTRA_ATTRS.'></script>'.PHP_EOL;

				$blocks['enqueue'][] = $the_script;
			}

			if(
				!( isset( $the_settings['scan_mode'] ) &&  $the_settings['scan_mode'] == 'turned_off' )
			)
			{
				if( MAP_DEV_MODE )
				{
					$the_script = plugin_dir_url(__FILE__).'../dev/dev.cookie-shield.js';

					$head_script .= '<script class="map_advanced_shield" type="text/javascript" src="'.$the_script.'" '.MAP_INLINE_SCRIPT_EXTRA_ATTRS.'></script>'.PHP_EOL;

					$blocks['enqueue'][] = $the_script;

					$js_shield_url = $the_script;
				}
				else
				{
					$local_file_exists = false;

					$base_ref = "";

					if( !( isset( $rconfig['forbid_local_js_caching'] ) && $rconfig['forbid_local_js_caching'] == 1 ) )
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

						MyAgilePrivacy::update_option( MAP_PLUGIN_DO_SYNC_NOW, 1 );
					}

					if( !$local_file_exists && isset( $base_ref ) && !(
						isset( $rconfig ) &&
						isset( $rconfig['prevent_preconnect_prefetch'] ) &&
						$rconfig['prevent_preconnect_prefetch'] == 1 ) )
					{
						$head_script .= '<link rel="preconnect" href="'.$base_ref.'" crossorigin />'.PHP_EOL.'<link rel="dns-prefetch" href="'.$base_ref.'" />'.PHP_EOL;
					}

					//js_shield_test_mode mode
					if( isset( $rconfig ) &&
						isset( $rconfig['js_shield_test_mode'] ) &&
						$rconfig['js_shield_test_mode'] == 1 )
					{
						$the_script = $base_ref.'test.cookie-shield.js';

						$head_script .= '<script class="map_advanced_shield" type="text/javascript" src="'.$the_script.'" '.MAP_INLINE_SCRIPT_EXTRA_ATTRS.'></script>'.PHP_EOL;

						$blocks['enqueue'][] = $the_script;

						$js_shield_url = $the_script;
					}
					else
					{
						$script_filename = 'cookie-shield.js';

						if( $local_file_exists )
						{
							if( $manifest_assoc &&
								isset( $manifest_assoc['files'][ $script_filename ] ) &&
								$manifest_assoc['files'][ $script_filename ]
							)
							{
								$script_filename = $manifest_assoc['files'][ $script_filename ]['filename'];
							}
						}

						$the_script = $base_ref.$script_filename;

						$head_script .= '<script class="map_advanced_shield" type="text/javascript" src="'.$the_script.'" '.MAP_INLINE_SCRIPT_EXTRA_ATTRS.'></script>'.PHP_EOL;

						$blocks['enqueue'][] = $the_script;

						$js_shield_url = $the_script;
					}
				}
			}

			if( $iab_tcf_context )
			{
				if( MAP_DEV_MODE )
				{
					$the_script = plugin_dir_url(__FILE__).'../dev/MyAgilePrivacyIabTCF/dev.MyAgilePrivacyIabTCF.js';

					$head_script .= '<script class="map_iab_tcf" type="text/javascript" src="'.$the_script.'" '.MAP_INLINE_SCRIPT_EXTRA_ATTRS.'></script>'.PHP_EOL;

					$blocks['enqueue'][] = $the_script;

					$iab_tcf_script_url = $the_script;
				}
				else
				{
					$local_file_exists = false;

					$base_ref = "";

					if( !( isset( $rconfig['forbid_local_js_caching'] ) && $rconfig['forbid_local_js_caching'] == 1 ) )
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

					$script_filename = 'MyAgilePrivacyIabTCF.js';

					if( $local_file_exists )
					{
						if( $manifest_assoc &&
							isset( $manifest_assoc['files'][ $script_filename ] ) &&
							$manifest_assoc['files'][ $script_filename ]
						)
						{
							$script_filename = $manifest_assoc['files'][ $script_filename ]['filename'];
						}
					}

					$the_script = $base_ref.$script_filename;

					$head_script .= '<script class="map_iab_tcf" type="text/javascript" src="'.$the_script.'" '.MAP_INLINE_SCRIPT_EXTRA_ATTRS.'></script>'.PHP_EOL;

					$blocks['enqueue'][] = $the_script;

					$iab_tcf_script_url = $the_script;
				}
			}
		}

		$base_config_script = '';

		$manifest_assoc_public = array();

		if( $manifest_assoc && isset( $manifest_assoc['manifest_version_file'] ) )
		{
			foreach( $manifest_assoc['files'] as $the_file => $the_item )
			{
				$new_item = array(
					'filename'		=>	$the_item['filename'],
					'version'		=>	$the_item['version'],
				);

				$manifest_assoc_public[ $the_file ] = $new_item;
			}
		}

		$map_full_config = array(
			'config_origin'												=>	'myagileprivacy_native',
			'mapx_ga4' 													=> 	$mapx_ga4,
			'map_wl' 													=> 	$map_wl_b,

			'map_js_basedirectory' 										=> 	$map_js_basedirectory,


			'map_lang_code' 											=> 	$map_lang_code,

			'cookie_reset_timestamp' 									=> 	$cookie_reset_timestamp,
			'cookie_api_key_remote_id_map_active' 						=> 	$cookie_api_key_remote_id_map_active,
			'cookie_api_key_remote_id_map_detectable' 					=> 	$cookie_api_key_remote_id_map_detectable,
			'cookie_api_key_remote_id_map_blocked_without_notification' => 	$cookie_api_key_remote_id_map_blocked_without_notification,
			'map_cookies_always_allowed' 								=> 	$map_cookies_always_allowed,
			'cookie_api_key_friendly_name_map' 							=> 	$cookie_api_key_friendly_name_map,
			'cookie_api_key_not_to_block' 								=> 	null,
			'enforce_youtube_privacy' 									=> 	$enforce_youtube_privacy,
			'enforce_youtube_privacy_v2'								=>	$enforce_youtube_privacy_v2,
			'video_advanced_privacy' 									=> 	$video_advanced_privacy,
			'manifest_assoc'											=> 	$manifest_assoc_public,
			'js_shield_url' 											=> 	$js_shield_url,
			'load_iab_tcf'												=> 	false,
			'iab_tcf_script_url'										=>	null,
			'enable_microsoft_cmode'									=>	null,
			'cmode_microsoft_default_consent_obj'						=>	null,
			'enable_cmode_v2'											=>	null,
			'cmode_v2_implementation_type'								=>	null,
			'enable_cmode_url_passthrough'								=>	null,
			'cmode_v2_forced_off_ga4_advanced'							=>	null,
			'cmode_v2_default_consent_obj'								=> 	null,
			'cmode_v2_js_on_error'										=>	null,
			'shield_added_pattern'										=>	$shield_added_pattern,
		);


		if( $iab_tcf_context )
		{
			$map_full_config['load_iab_tcf'] = true;
			$map_full_config['iab_tcf_script_url'] = $iab_tcf_script_url;
		}

		if( isset( $the_settings ) &&
			isset( $the_settings['enable_microsoft_cmode'] ) && $the_settings['enable_microsoft_cmode']
		)
		{
			$map_full_config['enable_microsoft_cmode'] = true;
			$map_full_config['cmode_microsoft_default_consent_obj'] = array(
				'microsoft_ad_storage'		=>	$the_settings['microsoft_consent_ad_storage'],
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
					'ad_storage'			=>	$the_settings['cmode_v2_gtag_ad_storage'],
					'ad_user_data'			=>	$the_settings['cmode_v2_gtag_ad_user_data'],
					'ad_personalization'	=>	$the_settings['cmode_v2_gtag_ad_personalization'],
					'analytics_storage'		=>	$the_settings['cmode_v2_gtag_analytics_storage'],
				);
			}

			//advanced consent mode ga4
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

		if( isset( $the_settings['cmode_v2_js_on_error'] ) )
		{
			$map_full_config['cmode_v2_js_on_error'] = $the_settings['cmode_v2_js_on_error'];
		}

		$map_full_config['cookie_api_key_not_to_block'] = $cookie_api_key_not_to_block;

		$base_config_script .= 'var map_full_config='.json_encode( $map_full_config ).';'.PHP_EOL;

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
				//advanced consent mode ga4
				if( $map_full_config['cmode_v2_forced_off_ga4_advanced'] == false )
				{
					if( $mapx_ga4 == 0 )
					{
						$base_config_script .= 'var alt_mpx_settings='.json_encode( array(
							'caller'						=>	'MAP',
							'map_ga_consent_checker'		=>	true,
						)).';'.PHP_EOL;
						$base_config_script .= "setTimeout( function() {
												if( typeof window.MyAgilePixelProxyBeacon !== 'undefined' ) window.MyAgilePixelProxyBeacon( alt_mpx_settings );
											}, 500 );".PHP_EOL;
					}
				}

				$base_config_script .= 'window.dataLayer = window.dataLayer || [];'.PHP_EOL;
				$base_config_script .= 'function gtag(){dataLayer.push(arguments);}'.PHP_EOL;

				$base_config_script .= "gtag('set', 'developer_id.dY2ZhMm', true);".PHP_EOL;

				if( $map_full_config['enable_cmode_url_passthrough'] )
				{
					$base_config_script .= "gtag('set', 'url_passthrough', true);".PHP_EOL;
				}
			}

			if( $map_full_config['cmode_v2_implementation_type'] == 'gtm' )
			{
				$base_config_script .= 'window.dataLayer = window.dataLayer || [];'.PHP_EOL;
				$base_config_script .= 'function gtag(){dataLayer.push(arguments);}'.PHP_EOL;

				$base_config_script .= "gtag('set', 'developer_id.dY2ZhMm', true);".PHP_EOL;

				$base_config_script .= 'var gTagManagerConsentListeners = [];'.PHP_EOL;
			}
		}

		$blocks['inline'][] = $base_config_script;

		$start_config_script = '<script class="map_advanced_shield" type="text/javascript" '.MAP_INLINE_SCRIPT_EXTRA_ATTRS.'>'.PHP_EOL.$base_config_script.PHP_EOL.'</script>'.PHP_EOL;

		if( $block_mode )
		{
			return $blocks;
		}
		else
		{
			return $start_config_script.$head_script;
		}
	}


	/**
	 * Internal functin for detected keys save
	 *
	 */
	private function internal_save_detected_keys( $key=null )
	{
		// Get settings
		$the_settings = MyAgilePrivacy::get_settings();

		if( isset( $key ) && $key )
		{
			if( $key != $the_settings['license_code'] )
			{
				if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'Wrong key' );

				return false;
			}
		}

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'start internal_save_detected_keys' );

		// Get settings
		$js_cookie_shield_detected_keys = explode( ',', MyAgilePrivacy::get_option( MAP_PLUGIN_JS_DETECTED_FIELDS, '' ) );

		if( !$js_cookie_shield_detected_keys )
		{
			$js_cookie_shield_detected_keys = array();
		}

		if( isset( $_POST['detectableKeys'] ) )
		{
			$detectableKeys = explode( ',', $_POST['detectableKeys'] );

			foreach( $detectableKeys as $k => $v )
			{
				if( $v != null && $v != 'null' )
				{
					$js_cookie_shield_detected_keys[] = $v;
				}
			}
		}

		if( isset( $_POST['detectedKeys'] ) )
		{
			$detectedKeys = explode( ',', $_POST['detectedKeys'] );

			foreach( $detectedKeys as $k => $v )
			{
				if( $v != null && $v != 'null' )
				{
					$js_cookie_shield_detected_keys[] = $v;
				}
			}
		}

		$js_cookie_shield_detected_keys = array_unique( array_filter( $js_cookie_shield_detected_keys ) );

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

		if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'end internal_save_detected_keys' );

		return true;
	}

	/**
	 * Save remotely detected keys callback function
	 *
	 */
	public function map_remote_save_detected_keys_callback()
	{
		$referral_url = wp_get_referer();

		$valid_referral_url = wp_validate_redirect( $referral_url );

		if( $valid_referral_url )
		{
			$success = false;

			// check form submit
			if( isset( $_POST['key'] ) )
			{
				$success = $this->internal_save_detected_keys( $_POST['key'] );
			}

			$answer = array(
				'success'				=>	$success,
			);

			wp_send_json( $answer );

		}

		die();
	}

	/**
	 * Missing cookie shield callback function
	 *
	 */
	public function map_missing_cookie_shield_callback()
	{
		$referral_url = wp_get_referer();

		$valid_referral_url = wp_validate_redirect( $referral_url );

		if( $valid_referral_url )
		{
			$success = false;

			// check form submit
			if( isset( $_POST['action'] ) && $_POST['action'] == 'map_missing_cookie_shield' )
			{
				$the_settings = MyAgilePrivacy::get_settings();

				if( isset( $the_settings['pa'] ) &&
					$the_settings['pa'] == 1 )
				{
					if( $_POST['detected'] == 0 )
					{
						if( $the_settings['missing_cookie_shield'] == false ||
							$the_settings['cookie_shield_running'] == true
						)
						{
							$the_settings['cookie_shield_running'] = false;
							$the_settings['missing_cookie_shield'] = true;
							$the_settings['missing_cookie_shield_timestamp'] = strtotime( "now" );

							MyAgilePrivacy::update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_settings );
						}
					}
					elseif( $_POST['detected'] == 1 )
					{
						if( $the_settings['cookie_shield_running'] == false ||
							$the_settings['missing_cookie_shield'] == true
						)
						{
							$the_settings['missing_cookie_shield'] = false;
							$the_settings['cookie_shield_running'] = true;
							$the_settings['cookie_shield_running_timestamp'] = strtotime( "now" );

							MyAgilePrivacy::update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_settings );
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
		$referral_url = wp_get_referer();

		$valid_referral_url = wp_validate_redirect( $referral_url );

		if( $valid_referral_url )
		{
			$success = false;

			// check form submit
			if( isset( $_POST['action'] ) && $_POST['action'] == 'map_check_consent_mode_status' )
			{
				$the_settings = MyAgilePrivacy::get_settings();

				$cmode_v2_js_on_error = ( isset( $_POST['is_consent_valid'] ) && $_POST['is_consent_valid'] ) ? false : true;

				if( $the_settings['cmode_v2_js_on_error'] && !$cmode_v2_js_on_error )
				{
					$the_settings['cmode_v2_js_on_error_first_relevation'] = 0;
				}

				if( !$the_settings['cmode_v2_js_on_error'] && $cmode_v2_js_on_error )
				{
					$the_settings['cmode_v2_js_on_error_first_relevation'] = strtotime( "now" );
				}

				$the_settings['cmode_v2_js_on_error'] = $cmode_v2_js_on_error;
				$the_settings['cmode_v2_js_error_code'] = intval( $_POST['error_code'] );
				$the_settings['cmode_v2_js_error_motivation'] = esc_attr( $_POST['error_motivation'] );

				MyAgilePrivacy::update_option( MAP_PLUGIN_SETTINGS_FIELD, $the_settings );

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
		$referral_url = wp_get_referer();

		$valid_referral_url = wp_validate_redirect( $referral_url );

		if( $valid_referral_url )
		{
			$success = false;

			// check form submit
			if( isset( $_POST['action'] ) && $_POST['action'] == 'map_save_detected_keys' )
			{
				$the_settings = MyAgilePrivacy::get_settings();

				if( isset( $the_settings['pa'] ) &&
					$the_settings['pa'] == 1 )
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
	public function map_callback( $output )
	{
		$this->scan_log = "Scanner started.\n";

		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= "map_callback\n\n";

		$something_done = false;

		//init config
		$parse_config = $this->scan_config;
		$scan_output = $parse_config;

		if( $this->saved_settings['scanner_compatibility_mode'] )
		{
			//init html parser (false to lowercase, forceTagsClosed, stripRN )
			$dom = shd_str_get_html( $output, false, false, false, false );
		}
		else
		{
			//init html parser
			$dom = shd_str_get_html( $output, true, true, false, false );
		}

		if( is_object( $dom ) )
		{
			//search for scripts
			$scripts = $dom->find( 'script' );

			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= print_r( $scripts, true );

			if( isset( $parse_config ) && isset( $parse_config['scripts_src_block'] ) )
			{
				foreach( $scripts as $k => $v )
				{
					//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= print_r( $scripts, true );

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
					$src = $v->src;

					if( strpos( $class, 'my_agile_privacy_activate' ) === false && strpos( $class, 'map_do_not_touch' ) === false )
					{
						if( !empty( $innertext ) )
						{
							$found = array_filter( $parse_config['scripts_src_block'],  function( $e, $the_key ) use ( $innertext ){

								if( !$e['plain_js'] ) return false;

								return ( strpos( $innertext, $e['plain_js'] ) !== false );

							}, ARRAY_FILTER_USE_BOTH );

							if( $found )
							{
								$action = array_values( $found )[0];
								$return_key = array_keys( $found )[0];

								if( isset( $action ) )
								{
									if( isset( $action['always_allowed'] ) && $action['always_allowed'] )
									{
										$this->scan_log .= $action['key']." is always allowed\n";
										$v->class = $v->class.' map_always_allowed';
										continue;
									}

									$detected_pattern = $action['plain_js'];
									$detected_key = $action['key'];

									$action_active = $action['active'];

									$no_action = false;

									if( isset( $action['only_on_compatibility_mode'] ) &&
										$action['only_on_compatibility_mode'] == true &&
										$this->saved_settings['scanner_compatibility_mode'] == false )
									{
										$no_action = true;
									}

									if( $action['to_detect'] == 1 )
									{
										$this->scan_log .= "detected plain_js script $detected_key $detected_pattern, action_active=$action_active, no_action=$no_action\n";

										$scan_output['scripts_src_block'][ $return_key ]['detected'] = 1;

										$this->scan_done = true;
									}

									if( $action_active && !$no_action )
									{
										if( $action['to_block'] == 1 )
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
								//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= .= print_r( array_values($found)[0], true )."\n";
							}
						}
						elseif( $src )
						{
							$found = array_filter( $parse_config['scripts_src_block'],  function( $e, $the_key ) use ( $src ){

								if( !$e['src'] ) return false;

								return ( strpos(  $src, $e['src'] ) !== false );

							}, ARRAY_FILTER_USE_BOTH );

							if( $found )
							{
								//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= print_r($found,true)."\n";

								$action = array_values( $found )[0];
								$return_key = array_keys( $found )[0];

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

									if( $action['to_detect'] == 1 )
									{
										$this->scan_log .= "detected src script $detected_key $detected_pattern $detected_pattern, action_active=$action_active, no_action=$no_action\n";

										$scan_output['scripts_src_block'][ $return_key ]['detected'] = 1;

										$this->scan_done = true;
									}

									if( $action_active && !$no_action )
									{
										if( $action['to_block'] == 1 )
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

											$v->unblocked_src = $v->src;
											$v->src = '';

											$v->setAttribute( 'data-cookie-api-key', $detected_key );
											$v->setAttribute( 'data-friendly_name', MyAgilePrivacy::nullCoalesceArrayItem( $action, 'friendly_name', '' ) );

											$scan_output['scripts_src_block'][ $return_key ]['blocked'] = 1;

											$this->scan_done = true;

											$something_done = true;
										}
										else
										{
											$v->class = $v->class.' map_not_to_block';
										}

									}
								}
								//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= print_r( array_values($found)[0], true )."\n";
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

			if( isset( $parse_config ) && isset( $parse_config['iframe_src_block'] ) )
			{
				foreach( $iframes as $k => $v )
				{
					//src
					$src = $v->src;
					$data_src = $v->getAttribute( 'data-src' );

					$the_src = null;

					//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= print_r( $src, true )."\n";

					$found = array_filter( $parse_config['iframe_src_block'],  function( $e, $the_key ) use ( $src ){

						if( !$e['src'] ) return false;

						return ( strpos(  $src, $e['src'] ) !== false );

					}, ARRAY_FILTER_USE_BOTH );


					if( !$found && $data_src && $data_src != '' )
					{
						$found = array_filter( $parse_config['iframe_src_block'],  function( $e, $the_key ) use ( $data_src ){

							if( !$e['src'] ) return false;

							return ( strpos(  $data_src, $e['src'] ) !== false );

						}, ARRAY_FILTER_USE_BOTH );
					}


					if( $found )
					{
						//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= print_r($found,true)."\n";

						$action = array_values( $found )[0];
						$return_key = array_keys( $found )[0];

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

							if( $action['to_detect'] == 1 )
							{
								$this->scan_log .= "detected iframe $detected_key $detected_pattern, action_active=$action_active, no_action=$no_action\n";

								$scan_output['iframe_src_block'][ $return_key ]['detected'] = 1;

								$this->scan_done = true;
							}

							if( $action_active && !$no_action )
							{
								if( $data_src && $data_src != '' )
								{
									$the_src = $data_src;
								}
								else
								{
									$the_src = $src;
								}

								if( $action['to_block'] == 1 )
								{
									if( strpos( $v->class, 'lazyload' ) !== false )
									{
										$v->class = str_replace( 'lazyload', '', $v->class );
									}

									if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= "blocked iframe $detected_key $detected_pattern\n\n";

									$v->class = $v->class.' '.'my_agile_privacy_activate autoscan_mode iframe_src_blocked map_blocked_content';

									if( isset( $action['show_inline_notify'] ) )
									{
										$v->class = $v->class.' map_show_inline_notify';
									}

									if( isset( $action['on_block_add_classes'] ) )
									{
										$v->class = $v->class.' '.$action['on_block_add_classes'];
									}

									$v->unblocked_src = $the_src;
									$v->src = '';
									$v->setAttribute( 'data-cookie-api-key', $detected_key );
									$v->setAttribute( 'data-friendly_name', MyAgilePrivacy::nullCoalesceArrayItem( $action, 'friendly_name', '' ) );

									$scan_output['iframe_src_block'][ $return_key ]['blocked'] = 1;

									$this->scan_done = true;

									$something_done = true;
								}

								if( isset( $action['to_fix'] ) && $action['to_fix'] == 1 )
								{
									if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= "fixed $detected_key $detected_pattern\n\n";

									if( isset( $action['to_fix_search'] ) &&
										isset( $action['to_fix_replace'] ) )
									{
										$v->class = $v->class.' '.'map_script_fixed';
										$v->original_src = $the_src;
										$v->src = str_replace( $action['to_fix_search'], $action['to_fix_replace'], $the_src );
									}

									if( isset( $action['to_append_param'] ) )
									{
										$v->class = $v->class.' '.'map_script_fixed';
										$v->original_src = $the_src;

										if( strpos( $v->original_src, '?' ) !== false )
										{
											$v->src = $src.'&'.$action['to_append_param'];
										}
										else
										{
											$v->src = $src.'?'.$action['to_append_param'];
										}

										$v->src = str_replace( $action['to_fix_search'], $action['to_fix_replace'], $the_src );
									}

									$scan_output['iframe_src_block'][ $return_key ]['fixed'] = 1;

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
						//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= print_r( $href, true )."\n";

						$found = array_filter( $parse_config['css_href_block'],  function( $e, $the_key ) use ( $href ){

							if( !$e['href'] ) return false;

							return ( strpos(  $href, $e['href'] ) !== false );

						}, ARRAY_FILTER_USE_BOTH );

						if( $found )
						{
							//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= print_r($found,true)."\n";

							$action = array_values( $found )[0];
							$return_key = array_keys( $found )[0];

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

								if( $action['to_detect'] == 1 )
								{
									$this->scan_log .= "detected link $detected_key $detected_pattern, action_active=$action_active, no_action=$no_action\n";

									$scan_output['css_href_block'][ $return_key ]['detected'] = 1;

									$this->scan_done = true;

									$something_done = true;
								}

								if( $action_active && !$no_action )
								{
									if( $action['to_block'] == 1 )
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
						//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= print_r( $src, true )."\n";

						$found = array_filter( $parse_config['elementor_widget_block'],  function( $e, $the_key ) use ( $data_settings ){

							if( !$e['src'] ) return false;

							return ( strpos(  $data_settings, $e['src'] ) !== false );

						}, ARRAY_FILTER_USE_BOTH );

						if( $found )
						{
							//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= print_r($found,true)."\n";

							$action = array_values( $found )[0];
							$return_key = array_keys( $found )[0];

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

								if( $action['to_detect'] == 1 )
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

											//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= html_entity_decode( $data_settings );

											$decoded_data_settings = json_decode( html_entity_decode( $data_settings ), true );

											$decoded_data_settings = array_merge( (array)$decoded_data_settings,(array)$action['json_add'] );

											//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= print_r( $decoded_data_settings, true );

											$v->setAttribute( 'data-settings', htmlentities( json_encode( $decoded_data_settings ) ) );
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

						$to_fix = str_replace( 'settings=','', $exploded[1] );

						$to_fix_decoded = base64_decode( $to_fix );

						$to_fix_decoded_decoded = json_decode( $to_fix_decoded, true );

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

						$decoded_data_settings = json_decode( html_entity_decode( $data_settings ), true );

						$decoded_data_settings = array_merge( (array)$decoded_data_settings,(array)$action_to_add );

						$parent->setAttribute( 'data-settings', htmlentities( json_encode( $decoded_data_settings ) ) );

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

			//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= print_r( $parse_config['img_search_replace'], true )."\n";

			//search for images
			$images = $dom->find( 'img' );

			if( isset( $parse_config ) && isset( $parse_config['img_search_replace'] ) )
			{
				foreach( $images as $k => $v )
				{
					//src
					$src = $v->src;
					$srcset = $v->getAttribute( 'srcset' );

					if( strpos( $v->class, 'lazyload' ) !== false )
					{
						$src = $v->getAttribute( 'data-src' );
					}

					//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= print_r( $src, true )."\n";

					$found = array_filter( $parse_config['img_search_replace'],  function( $e, $the_key ) use ( $src ){

						if( !$e['src'] ) return false;

						return ( strpos(  $src, $e['src'] ) !== false );

					}, ARRAY_FILTER_USE_BOTH );


					$found_alt = array_filter( $parse_config['img_search_replace'],  function( $e, $the_key ) use ( $srcset ){

						if( !$e['src'] ) return false;

						return ( strpos(  $srcset, $e['src'] ) !== false );

					}, ARRAY_FILTER_USE_BOTH );


					if( $found || $found_alt )
					{
						//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= print_r($found,true)."\n";

						$action = array_values( $found )[0];
						$return_key = array_keys( $found )[0];

						if( isset( $action ) )
						{
							if( isset( $action['always_allowed'] ) && $action['always_allowed'] )
							{
								$this->scan_log .= $action['key']." is always allowed\n";
								$v->class = $v->class.' map_always_allowed';
								continue;
							}

							if( strpos( $v->class, 'lazyload' ) !== false )
							{
								$v->class = str_replace( 'lazyload', '', $v->class );
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

							if( $action['to_detect'] == 1 )
							{
								$this->scan_log .= "detected img $detected_key $detected_pattern, action_active=$action_active, no_action=$no_action\n";

								$scan_output['img_search_replace'][ $return_key ]['detected'] = 1;

								$this->scan_done = true;
							}

							if( $action_active && !$no_action )
							{
								if( $action['to_block'] == 1 )
								{
									if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= "blocked img $detected_key $detected_pattern\n";

									$v->class = $v->class.' '.'my_agile_privacy_activate autoscan_mode img_src_blocked map_blocked_content';

									if( isset( $action['show_inline_notify'] ) )
									{
										$v->class = $v->class.' map_show_inline_notify';
									}

									if( isset( $action['on_block_add_classes'] ) )
									{
										$v->class = $v->class.' '.$action['on_block_add_classes'];
									}

									$v->unblocked_src = $src;
									//blank image
									$v->src = 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';
									$v->setAttribute( 'srcset', '' );
									$v->setAttribute( 'data-cookie-api-key', $detected_key );
									$v->setAttribute( 'data-friendly_name', MyAgilePrivacy::nullCoalesceArrayItem( $action, 'friendly_name', '' ) );

									$scan_output['img_search_replace'][ $return_key ]['blocked'] = 1;

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

		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= print_r( $this->saved_settings, true );
		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= print_r( $parse_config, true );
		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= print_r( $scan_output, true );
		$this->scan_output = $scan_output;
		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) $this->scan_log .= "map_callback_end\n\n";
		//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) return $this->scan_log;

		//$output = $this->scan_log;

		$output_ori = $dom;

		if( $this->head_script && is_object( $dom ) )
		{
			$inject = $this->head_script;

			$head_object = $dom->find( 'head', 0 );

			if( $head_object && is_object( $head_object ) )
			{
				$head_object->innertext = $inject.$head_object->innertext;

				$something_done = true;
			}
		}

		$output = $dom;

		if( !$output )
		{
			$output = $output_ori;
		}

		if( !$something_done )
		{
			$output = $output_ori;
		}

		$js_cookie_shield_detected_keys = explode( ',', MyAgilePrivacy::get_option( MAP_PLUGIN_JS_DETECTED_FIELDS, '' ) );
		$js_cookie_shield_detected_keys = array_unique( array_filter( $js_cookie_shield_detected_keys ) );

		if( $this->scan_log && defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $js_cookie_shield_detected_keys );

		if( $this->scan_done || count( $js_cookie_shield_detected_keys ) > 0 )
		{
			//if( $this->scan_output && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $this->scan_output, true );
			if( $this->scan_log && defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $this->scan_log );

			//auto activate
			if( $this->scan_mode == 'learning_mode' )
			{
				//if( $this->scan_log && defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $this->cookie_post_id );

				// Get settings
				$the_settings = MyAgilePrivacy::get_settings();

				$currentAndSupportedLanguages = MyAgilePrivacy::getCurrentAndSupportedLanguages();

				$auto_activate_keys = array();

				foreach( $this->scan_output as $k => $v )
				{
					foreach( $v as $kk => $vv )
					{
						//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $vv, true );

						$key_to_look_for = $vv['key'];

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

				if( $this->saved_settings['scanner_compatibility_mode'] == false )
				{
					$posts_per_page = -1;

					if( !$currentAndSupportedLanguages['with_multilang'] )
					{
						$posts_per_page = 1;
					}

					foreach( $auto_activate_keys as $k => $v )
					{
						//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $v, true );

						if( $v )
						{
							$post_status_to_search = array( 'draft' );

							$cc_args = array(
								'posts_per_page'   	=>  $posts_per_page,
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
											if( in_array( $main_post_id, $this->cookie_post_id ) )
											{
												//update
												$my_post = array(
													'ID'           	=> 	$main_post_id,
													'post_status'	=>	'publish',
												);

												//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $my_post );

												wp_update_post( $my_post );

												if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( "published cookie ".$v , true );

												update_post_meta( $main_post_id, "_map_auto_detected", 1 );

												if( $currentAndSupportedLanguages['with_multilang'] )
												{
													//no further actions
												}
											}
										}
									}
								}

								MyAgilePrivacy::internal_query_reset();
							}
							else
							{
								if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( $v." not found", true );
							}
						}
					}

					MyAgilePrivacy::update_option( MAP_PLUGIN_JS_DETECTED_FIELDS, '' );
				}
				else
				{
					MyAgilePrivacy::update_option( MAP_PLUGIN_JS_DETECTED_FIELDS, implode( ',', array_unique( $auto_activate_keys ) ) );
				}

				$this->scan_log .= "Scanner finished.\n";

				//if( defined( 'MAP_DEBUGGER' ) && MAP_DEBUGGER ) MyAgilePrivacy::write_log( 'END' );
			}
		}

		if( $this->saved_settings['scanner_compatibility_mode'] )
		{
			global $post;
			setup_postdata( $this->saved_post );
		}

		return $output;
	}
}