<?php

if( !defined( 'MAP_PLUGIN_NAME' ) )
{
	exit('Not allowed.');
}


/**
 * My Agile Privacy core renderer.
 *
 *
 * @package    MyAgilePrivacy
 * @subpackage MyAgilePrivacy/includes
 * @author     https://www.myagileprivacy.com/
 */

/**
 * Renderer class for the My Agile Privacy notification banner and cookie settings modal.
 */
final class MyAgilePrivacyBannerRenderer
{
	/** @var array */
	private $the_settings;

	/** @var array */
	private $rconfig;

	/** @var array */
	private $the_translations;

	/** @var string */
	private $current_lang;

	/** @var bool */
	private $is_polylang_enabled;

	/** @var bool */
	private $is_wpml_enabled;

	/** @var array|null */
	private $clarity_consent_mode_consents;

	/** @var array|null */
	private $microsoft_consent_mode_consents;

	/** @var array|null */
	private $consent_mode_consents;

	/** @var MyAgilePrivacyFrontend */
	private $frontend;

	/**
	 * @param array                  $the_settings
	 * @param array                  $rconfig
	 * @param array                  $the_translations
	 * @param string                 $current_lang
	 * @param bool                   $is_polylang_enabled
	 * @param bool                   $is_wpml_enabled
	 * @param array|null             $clarity_consent_mode_consents
	 * @param array|null             $microsoft_consent_mode_consents
	 * @param array|null             $consent_mode_consents
	 * @param MyAgilePrivacyFrontend $frontend
	 */
	public function __construct(
		$the_settings,
		$rconfig,
		$the_translations,
		$current_lang,
		$is_polylang_enabled,
		$is_wpml_enabled,
		$clarity_consent_mode_consents,
		$microsoft_consent_mode_consents,
		$consent_mode_consents,
		$frontend
	)
	{
		$this->the_settings                    = $the_settings;
		$this->rconfig                         = $rconfig;
		$this->the_translations                = $the_translations;
		$this->current_lang                    = $current_lang;
		$this->is_polylang_enabled             = $is_polylang_enabled;
		$this->is_wpml_enabled                 = $is_wpml_enabled;
		$this->clarity_consent_mode_consents   = $clarity_consent_mode_consents;
		$this->microsoft_consent_mode_consents = $microsoft_consent_mode_consents;
		$this->consent_mode_consents           = $consent_mode_consents;
		$this->frontend                        = $frontend;
	}

	/**
	 * Returns the full HTML output for the banner and modal.
	 *
	 * @return string
	 */
	public function render()
	{
		$html  = '<!-- Consent Management Platform and Privacy Management by My Agile Privacy® | www.myagileprivacy.com -->'.PHP_EOL;
		$html .= '<!--googleoff: all-->'.PHP_EOL;
		$html .= $this->render_banner();
		$html .= $this->render_blocked_content_notification();
		$html .= $this->render_modal();
		$html .= $this->render_custom_css();
		$html .= '<!--googleon: all-->'.PHP_EOL;

		return $html;
	}

	/**
	 * Returns the computed style variables used across multiple render methods.
	 *
	 * @return array
	 */
	private function get_style_vars()
	{
		$s = $this->the_settings;

		$color_style         = ( $s['text'] != '' ) ? 'color:'.$s['text'].' !important' : '';
		$background_style    = $s['background'] != '' ? 'background-color:'.$s['background'] : '';
		$border_radius_style = 'border-radius:'.$s['elements_border_radius'].'px';
		$text_size           = 'font-size:'.$s['text_size'].'px!important';
		$text_lineheight     = 'line-height:'.$s['text_lineheight'].'px!important';

		return array(
			'color_style'                           => $color_style,
			'background_style'                      => $background_style,
			'border_radius_style'                   => $border_radius_style,
			'text_size'                             => $text_size,
			'text_lineheight'                       => $text_lineheight,
			'composed_style'                        => implode( ';', array( $color_style, $background_style, $border_radius_style, $text_size, $text_lineheight ) ),
			'composed_style_paragraph_first_layer'  => implode( ';', array( $text_size, $text_lineheight, $color_style ) ),
			'composed_style_paragraph_second_layer' => implode( ';', array( $text_size, $text_lineheight ) ),
			'notification_bar_composed_style'       => implode( ';', array( $color_style, $background_style, $border_radius_style ) ),
		);
	}

	/**
	 * Returns the computed position and size CSS classes for the banner.
	 *
	 * @return array
	 */
	private function get_position_vars()
	{
		$s = $this->the_settings;

		$cookie_banner_vertical_position   = $s['cookie_banner_vertical_position'];
		$cookie_banner_horizontal_position = $s['cookie_banner_horizontal_position'];

		$with_css_effects_class = ( $s['with_css_effects'] == true ) ? 'withEffects' : '';
		$map_shadow_class       = ( $s['cookie_banner_shadow'] == false ) ? '' : $s['cookie_banner_shadow'];
		$floating_banner        = ( $s['floating_banner'] == false ) ? '' : 'map_floating_banner';
		$animation_class        = 'map_animation_'.$s['cookie_banner_animation'];

		$new_position = null;
		$new_size     = 'mapSizeBoxed';

		if( $cookie_banner_vertical_position )
		{
			switch( $s['cookie_banner_size'] )
			{
				case 'sizeWideBranded':
					$new_size = 'mapSizeWideBranded';
					$cookie_banner_horizontal_position = 'Center';
					break;
				case 'sizeWide':
					$new_size = 'mapSizeWide';
					$cookie_banner_horizontal_position = 'Center';
					break;
				case 'sizeBig':
					$new_size = 'mapSizeBig mapSizeBoxed';
					break;
				case 'sizeBoxed':
					$new_size = 'mapSizeBoxed';
					break;
				default:
					$new_size = 'mapSizeBoxed';
			}

			$new_position = 'mapPosition'.$cookie_banner_vertical_position.$cookie_banner_horizontal_position;
		}

		if( !$new_position )
		{
			$new_position = $s['is_bottom'] ? 'mapPositionBottomCenter' : 'mapPositionTopCenter';
			$new_size     = 'mapSizeBoxed';
		}

		if( !( isset( $s['pa'] ) && $s['pa'] ) )
		{
			$new_size = 'mapSizeWideBranded';
		}

		$composed_class = implode( ' ', array( $new_position, $new_size, $floating_banner, $with_css_effects_class, $animation_class, $map_shadow_class ) );

		if( !$new_position )
		{
			$position_class_alt = ( $s['is_bottom'] == false ) ? 'isBottom' : 'isTop';
		}
		else
		{
			$position_class_alt = ( $cookie_banner_vertical_position == 'Top' ) ? 'isBottom' : 'isTop';
		}

		return array(
			'new_position'                    => $new_position,
			'new_size'                        => $new_size,
			'composed_class'                  => $composed_class,
			'position_class_alt'              => $position_class_alt,
			'with_css_effects_class'          => $with_css_effects_class,
			'map_shadow_class'                => $map_shadow_class,
			'cookie_banner_vertical_position' => $cookie_banner_vertical_position,
		);
	}

	/**
	 * Returns whether the IAB TCF context is active.
	 *
	 * @return bool
	 */
	private function is_iab_tcf_context()
	{
		return (
			$this->rconfig &&
			isset( $this->rconfig['allow_iab'] ) &&
			$this->rconfig['allow_iab'] == 1 &&
			$this->the_settings['enable_iab_tcf']
		);
	}

	/**
	 * Returns the resolved cookie policy URL, taking WPML and Polylang into account.
	 *
	 * @return string|null
	 */
	private function get_cookie_policy_url()
	{
		$s = $this->the_settings;

		$is_url = isset( $s['is_cookie_policy_url'] )   ? $s['is_cookie_policy_url']   : null;
		$url    = isset( $s['cookie_policy_url'] )       ? $s['cookie_policy_url']       : null;
		$page   = isset( $s['cookie_policy_page'] )      ? $s['cookie_policy_page']      : null;

		if( $is_url && $url ) return $url;

		if( !$is_url && $page )
		{
			if( $this->is_wpml_enabled )    return get_permalink( icl_object_id( $page, 'page', true ) );
			if( $this->is_polylang_enabled ) return get_permalink( pll_get_post( $page ) );
			return get_permalink( $page );
		}

		return null;
	}

	/**
	 * Returns the resolved personal data policy URL, taking WPML and Polylang into account.
	 *
	 * @return string|null
	 */
	private function get_personal_data_policy_url()
	{
		$s = $this->the_settings;

		$is_url = isset( $s['is_personal_data_policy_url'] ) ? $s['is_personal_data_policy_url'] : null;
		$url    = isset( $s['personal_data_policy_url'] )    ? $s['personal_data_policy_url']    : null;
		$page   = isset( $s['personal_data_policy_page'] )   ? $s['personal_data_policy_page']   : null;

		if( $is_url && $url ) return $url;

		if( !$is_url && $page )
		{
			if( $this->is_wpml_enabled )    return get_permalink( icl_object_id( $page, 'page', true ) );
			if( $this->is_polylang_enabled ) return get_permalink( pll_get_post( $page ) );
			return get_permalink( $page );
		}

		return null;
	}

	/**
	 * Returns the branded content HTML block, or empty string if not applicable.
	 *
	 * @param string $new_size
	 * @return string
	 */
	private function render_branded_content( $new_size )
	{
		if( $new_size == 'mapSizeWideBranded' ||
			!( isset( $this->the_settings['pa'] ) && $this->the_settings['pa'] ) )
		{

			return
				'<div class="map_branded-box">'.
					'<img src="'.esc_attr( plugin_dir_url( __DIR__ ) ).'frontend/img/map_logo_branded.svg" alt="Privacy and Consent by My Agile Privacy®">'.
				'</div>';
		}

		return '';
	}

	/**
	 * Returns the button shortcode string based on the configured button order.
	 *
	 * @return string
	 */
	private function get_button_shortcode_string()
	{
		$default = '[myagileprivacy_cookie_accept][myagileprivacy_cookie_reject][myagileprivacy_cookie_customize]';
		$order   = $this->the_settings['layer_1_button_order'];
		$parts   = explode( '_', $order );

		if( count( $parts ) == 3 )
		{
			$result = '';
			foreach( $parts as $part )
			{
				$result .= '[myagileprivacy_cookie_'.$part.']';
			}
			return $result;
		}

		return $default;
	}

	/**
	 * Returns the notification message HTML, applying paragraph split if applicable.
	 *
	 * @param bool   $iab_tcf_context
	 * @param string $composed_style_paragraph_first_layer
	 * @param string $iab_extra_class
	 * @param string $cookie_policy_html_inside_popup
	 * @param string $personal_data_policy_html_inside_popup
	 * @param string $button_shortcode_string
	 * @return string
	 */
	private function render_notify_message(
		$iab_tcf_context,
		$composed_style_paragraph_first_layer,
		$iab_extra_class,
		$cookie_policy_html_inside_popup,
		$personal_data_policy_html_inside_popup,
		$button_shortcode_string
	)
	{
		$lang = $this->current_lang;
		$t    = $this->the_translations[ $lang ];
		$s    = $this->the_settings;

		$allowed_paragraph_split         = false;
		$map_notification_container_flex = '';

		if( !$iab_tcf_context &&
			$t['notify_message_v2'] == $t['notify_message_v2_short'] )
		{
			$allowed_paragraph_split         = true;
			$map_notification_container_flex = 'map_flex';
		}

		$notify_message = do_shortcode( stripslashes( esc_html( $t['notify_message_v2'].'[myagileprivacy_extra_info]' ) ) );

		if( $allowed_paragraph_split )
		{
			$paragraphs = preg_split( '/\.(?!\d)/', $notify_message );
			$paragraphs = array_map( 'trim', $paragraphs );
			$paragraphs = array_filter( $paragraphs );

			if( count( $paragraphs ) > 1 )
			{
				$notify_message = '';
				foreach( $paragraphs as $paragraph )
				{
					if( $paragraph && $paragraph != '' )
					{
						$notify_message .= '<p class="map_p_splitted" style="'.esc_attr( $composed_style_paragraph_first_layer ).'">'.$paragraph.'.&nbsp;</p>';
					}
				}
			}
		}

		$first_layer_extra_text = '';

		$cookie_policy_first_layer_check = ( $cookie_policy_html_inside_popup &&
			isset( $s['add_cookie_policy_to_first_layer'] ) &&
			$s['add_cookie_policy_to_first_layer'] );

		$personal_data_policy_first_layer_check = ( $personal_data_policy_html_inside_popup &&
			isset( $s['add_personal_policy_to_first_layer'] ) &&
			$s['add_personal_policy_to_first_layer'] );

		if( $cookie_policy_first_layer_check || $personal_data_policy_first_layer_check )
		{
			$first_layer_extra_text = '<div class="map-modal-cookie-policy-link">';

			if( $cookie_policy_first_layer_check )
			{
				$first_layer_extra_text .= wp_kses( $cookie_policy_html_inside_popup, MyAgilePrivacy::allowed_html_tags() );
			}

			if( $personal_data_policy_first_layer_check )
			{
				$first_layer_extra_text .= wp_kses( $personal_data_policy_html_inside_popup, MyAgilePrivacy::allowed_html_tags() );
			}

			$first_layer_extra_text .= '</div>';
		}

		$map_notification_container_extra_class = implode( ' ', array( $iab_extra_class, $map_notification_container_flex ) );

		return do_shortcode(
			'<div class="map-area-container">'.
				'<div data-nosnippet class="map_notification-message '.esc_attr( $map_notification_container_extra_class ).'">'.
					$notify_message.$first_layer_extra_text.
				'</div>'.
				'<div class="map_notification_container '.esc_attr( $iab_extra_class ).'">'.
					esc_attr( $button_shortcode_string ).
				'</div>'.
			'</div>'
		);
	}

	/**
	 * Returns the full first-layer banner HTML.
	 *
	 * @return string
	 */
	private function render_banner()
	{
		$s    = $this->the_settings;
		$lang = $this->current_lang;
		$t    = $this->the_translations[ $lang ];

		$style_vars    = $this->get_style_vars();
		$position_vars = $this->get_position_vars();

		$iab_tcf_context = $this->is_iab_tcf_context();
		$iab_extra_class = $iab_tcf_context ? 'map-iab-context' : '';

		$cookie_policy_url        = $this->get_cookie_policy_url();
		$personal_data_policy_url = $this->get_personal_data_policy_url();

		$cookie_policy_html_inside_popup        = '';
		$personal_data_policy_html_inside_popup = '';
		$cookie_policy_html_inside_banner       = '';

		if( $cookie_policy_url )
		{
			$cookie_policy_html_inside_popup = '<a target="blank" href="'.esc_url( $cookie_policy_url ).'" tabindex="0" aria-label="Cookie Policy">'.esc_html( $t['view_the_cookie_policy'] ).'</a>';
		}

		if( $personal_data_policy_url )
		{
			$personal_data_policy_html_inside_popup = '<a target="blank" href="'.esc_url( $personal_data_policy_url ).'" tabindex="0" aria-label="Personal Data Policy">'.esc_html( $t['view_the_personal_data_policy'] ).'</a>';
		}

		if( isset( $s['cookie_policy_link'] ) && $s['cookie_policy_link'] && $cookie_policy_url )
		{
			$cookie_policy_html_inside_banner = $cookie_policy_html_inside_popup;
		}

		$spacing_text = ( isset( $s['cookie_policy_link'] ) && $s['cookie_policy_link'] && $cookie_policy_html_inside_banner ) ? ' / ' : '';

		$map_heading_style      = 'background-color:'.$s['heading_background_color'].'; color: '.$s['heading_text_color'].';';
		$map_heading_class      = ( $s['title_is_on'] == true ) ? '' : 'map_displayNone';
		$map_close_button_style = ( $s['title_is_on'] == true ) ? 'color: '.$s['heading_text_color'].'!important;' : 'color: '.$s['text'].'!important;';
		$notify_logo_color      = ( $s['heading_background_color'] == '#ffffff' ) ? '#F93F00' : $s['heading_background_color'];
		$banner_logo_color      = $s['heading_text_color'];
		$banner_title           = esc_html( $t['banner_title'] );

		$notify_message = $this->render_notify_message(
			$iab_tcf_context,
			$style_vars['composed_style_paragraph_first_layer'],
			$iab_extra_class,
			$cookie_policy_html_inside_popup,
			$personal_data_policy_html_inside_popup,
			$this->get_button_shortcode_string()
		);

		$notify_title =
			'<div class="map_notify_title '.esc_attr( $map_heading_class ).'" style="'.esc_attr( $map_heading_style ).';">'.
				( $banner_title != '' ? esc_html( $banner_title ) : '<div class="banner-title-logo" style="background:'.esc_attr( $banner_logo_color ).';"></div> My Agile Privacy®' ).
			'</div>';

		$notify_close_button =
			'<div class="map-closebutton-right">'.
				'<a tabindex="0" role="button" class="map-button map-reject-button" data-map_action="reject" style="'.esc_attr( $map_close_button_style ).'">&#x2715;</a>'.
			'</div>';

		$html =
			'<div'.
				' role="dialog"'.
				' aria-label="My Agile Privacy®"'.
				' tabindex="0"'.
				' id="my-agile-privacy-notification-area"'.
				' class="'.esc_attr( $position_vars['composed_class'] ).' mapButtonsAside"'.
				' data-nosnippet="true"'.
				' style="'.esc_attr( $style_vars['composed_style'] ).'"'.
				' data-animation="'.$s['cookie_banner_animation'].'">'.
				$notify_title.
				$notify_close_button.
				'<div id="my-agile-privacy-notification-content">'.
					$this->render_branded_content( $position_vars['new_size'] ).
					$notify_message.
				'</div>'.
			'</div>';

		$html .= $this->render_show_again_widget(
			$notify_logo_color,
			$cookie_policy_html_inside_banner,
			$spacing_text,
			$style_vars['border_radius_style']
		);

		return $html;
	}

	/**
	 * Returns the "show again" consent widget HTML.
	 *
	 * @param string $notify_logo_color
	 * @param string $cookie_policy_html_inside_banner
	 * @param string $spacing_text
	 * @param string $border_radius_style
	 * @return string
	 */
	private function render_show_again_widget( $notify_logo_color, $cookie_policy_html_inside_banner, $spacing_text, $border_radius_style )
	{
		$s    = $this->the_settings;
		$lang = $this->current_lang;

		$consent_widget_style       = isset( $s['consent_widget_style'] ) ? $s['consent_widget_style'] : 'text_and_logo';
		$disable_logo               = $s['disable_logo'];
		$showagain_tab              = $s['showagain_tab'];
		$notify_position_horizontal = $s['notify_position_horizontal'];

		$show_again            = esc_html( $this->the_translations[ $lang ]['manage_consent'] );
		$showagain_div_classes = 'map_displayNone';
		$the_empty_href        = '#';

		if( $disable_logo )                         $showagain_div_classes .= ' nologo';
		if( $consent_widget_style === 'logo_only' ) $showagain_div_classes .= ' notext';
		if( $consent_widget_style === 'text_only' ) $showagain_div_classes .= ' nologo';
		if( $showagain_tab == 0 )                   $showagain_div_classes .= ' disactive';
		if( $s['with_css_effects'] == true )        $showagain_div_classes .= ' withEffects';

		switch( $notify_position_horizontal )
		{
			case 'left':   $showagain_div_classes .= ' left_position';   break;
			case 'center': $showagain_div_classes .= ' center_position'; break;
			case 'right':  $showagain_div_classes .= ' right_position';  break;
		}

		$widget_inner_html = '';

		if( $consent_widget_style === 'logo_only' )
		{
			$widget_inner_html = '<button class="map_logo_container map_logo_only showConsent" tabindex="0" role="button" style="background-color:'.esc_attr( $notify_logo_color ).';" aria-label="'.wp_kses( $show_again, MyAgilePrivacy::allowed_html_tags() ).'"></button>';
		}
		elseif( $consent_widget_style === 'text_only' )
		{
			$widget_inner_html = '<a tabindex="0" role="button" class="showConsent" href="'.$the_empty_href.'" data-nosnippet>'.wp_kses( $show_again, MyAgilePrivacy::allowed_html_tags() ).'</a>'.$spacing_text.$cookie_policy_html_inside_banner;
		}
		else
		{
			$widget_inner_html = '<div class="map_logo_container" style="background-color:'.esc_attr( $notify_logo_color ).';"></div><a tabindex="0" role="button" class="showConsent" href="'.$the_empty_href.'" data-nosnippet>'.wp_kses( $show_again, MyAgilePrivacy::allowed_html_tags() ).'</a>'.$spacing_text.$cookie_policy_html_inside_banner;
		}

		return
			'<div data-nosnippet class="'.esc_attr( $showagain_div_classes ).'" id="'.esc_attr( $s['showagain_div_id'] ).'" style="'.esc_attr( $border_radius_style ).'">'.
				$widget_inner_html.
			'</div>';
	}

	/**
	 * Returns the blocked content notification bar HTML, or empty string if disabled.
	 *
	 * @return string
	 */
	private function render_blocked_content_notification()
	{
		$s = $this->the_settings;

		if( !( $s['pa'] == 1 && $s['blocked_content_notify'] ) )
		{
			return '';
		}

		$style_vars    = $this->get_style_vars();
		$position_vars = $this->get_position_vars();

		$blocked_content_text = esc_html( $this->the_translations[ $this->current_lang ]['blocked_content'] ).':';
		$autoclose            = ( $s['blocked_content_notify_auto_shutdown'] == false ) ? '' : 'autoShutDown';
		$composed_class_alt   = implode( ' ', array( $position_vars['position_class_alt'], $autoclose, $position_vars['with_css_effects_class'], $position_vars['map_shadow_class'] ) );

		return
			'<div'.
				' class="map-blocked-content-notification-area '.esc_attr( $composed_class_alt ).'"'.
				' id="map-blocked-content-notification-area"'.
				' style="'.esc_attr( $style_vars['notification_bar_composed_style'] ).'"'.
			'>'.
				'<div class="map-area-container">'.
					'<div class="map_notification-message data-nosnippet">'.
						$blocked_content_text.'<br>'.
						'<span class="map_blocked_elems_desc"></span>'.
					'</div>'.
				'</div>'.
			'</div>';
	}

	/**
	 * Returns the full cookie settings modal HTML.
	 *
	 * @return string
	 */
	private function render_modal()
	{
		$s    = $this->the_settings;
		$lang = $this->current_lang;
		$t    = $this->the_translations[ $lang ];

		$style_vars    = $this->get_style_vars();
		$position_vars = $this->get_position_vars();

		$iab_tcf_context = $this->is_iab_tcf_context();

		$cookie_policy_url        = $this->get_cookie_policy_url();
		$personal_data_policy_url = $this->get_personal_data_policy_url();

		$cookie_policy_html_inside_popup        = '';
		$personal_data_policy_html_inside_popup = '';

		if( $cookie_policy_url )
		{
			$cookie_policy_html_inside_popup = '<a target="blank" href="'.esc_url( $cookie_policy_url ).'" tabindex="0" aria-label="Cookie Policy">'.esc_html( $t['view_the_cookie_policy'] ).'</a>';
		}

		if( $personal_data_policy_url )
		{
			$personal_data_policy_html_inside_popup = '<a target="blank" href="'.esc_url( $personal_data_policy_url ).'" tabindex="0" aria-label="Personal Data Policy">'.esc_html( $t['view_the_personal_data_policy'] ).'</a>';
		}

		$layer2_overflow_wrapper_class = 'map-cookielist-overflow-container';

		if( $this->rconfig &&
			isset( $this->rconfig['disable_layer2_overflow'] ) &&
			$this->rconfig['disable_layer2_overflow'] )
		{
			$layer2_overflow_wrapper_class = '';
		}

		$html  = '<div class="map-modal" id="mapSettingsPopup" data-nosnippet="true" role="dialog" tabindex="0" aria-labelledby="mapSettingsPopup">';
		$html .= '<div class="map-modal-dialog">';
		$html .= '<div class="map-modal-content map-bar-popup '.esc_attr( $position_vars['with_css_effects_class'] ).'">';
		$html .= '<div class="map-modal-body">';
		$html .= '<div class="map-container-fluid map-tab-container">';

		$html .= '<div class="map-privacy-overview">';
		$html .= '<p class="map-h4-heading" data-nosnippet>'.esc_html( $t['privacy_settings'] ).'</p>';
		$html .= '</div>';

		$html .= '<p data-nosnippet style="'.esc_attr( $style_vars['composed_style_paragraph_second_layer'] ).'">';
		$html .= esc_html( $t['this_website_uses_cookies'] ).'<br>';
		$html .= '<span class="map-modal-cookie-policy-link">'.wp_kses( $cookie_policy_html_inside_popup, MyAgilePrivacy::allowed_html_tags() ).' '.wp_kses( $personal_data_policy_html_inside_popup, MyAgilePrivacy::allowed_html_tags() ).'</span>';
		$html .= '</p>';

		$html .= '<div class="'.esc_attr( $layer2_overflow_wrapper_class ).'">';

		if( $iab_tcf_context )
		{
			$html .= $this->render_iab_tabs_header();
			$html .= '<div id="map-privacy-cookie-thirdypart-wrapper" class="map-wrappertab map-wrappertab-active map-privacy-cookie-thirdypart-wrapper">';
		}

		$html .= $this->render_cookie_list( $position_vars['with_css_effects_class'] );

		if( $iab_tcf_context )
		{
			$html .= '</div>'; // map-privacy-cookie-thirdypart-wrapper
			$html .= '<div id="map-privacy-iab-tcf-wrapper" class="map-wrappertab map-privacy-iab-tcf-wrapper"></div>';
			$html .= '</div>'; // map-consent-extrawrapper
		}

		$html .= '</div>'; // overflow-cookielist-container
		$html .= '</div>'; // map-container-fluid

		$html .= $this->render_credits();

		$html .= '</div>'; // map-modal-body
		$html .= '<button type="button" tabindex="0" class="map-modal-close" id="mapModalClose">&#x2715;<span class="sr-only">'.esc_html( $t['close'] ).'</span></button>';
		$html .= '</div>'; // map-modal-content
		$html .= '</div>'; // map-modal-dialog
		$html .= '</div>'; // map-modal

		$html .= '<div class="map-modal-backdrop map-fade map-settings-overlay"></div>';
		$html .= '<div class="map-modal-backdrop map-fade map-popupbar-overlay"></div>';

		return $html;
	}

	/**
	 * Returns the IAB TCF tab navigation header HTML.
	 *
	 * @return string
	 */
	private function render_iab_tabs_header()
	{
		$t = $this->the_translations[ $this->current_lang ];

		$html  = '<div class="map-consent-extrawrapper">';
		$html .= '<ul class="map-wrappertab-navigation">';
			$html .= '<li><a href="#map-privacy-cookie-thirdypart-wrapper" class="map-tab-navigation active-wrappertab-nav" role="button" tabindex="0">'.esc_html( $t['cookies_and_thirdy_part_software'] ).'</a></li>';
			$html .= '<li><a href="#map-privacy-iab-tcf-wrapper" class="map-tab-navigation" role="button" tabindex="0">'.esc_html( $t['advertising_preferences'] ).'</a></li>';
		$html .= '</ul>';

		return $html;
	}

	/**
	 * Returns the full cookie list HTML for the modal second layer.
	 *
	 * @param string $with_css_effects_class
	 * @return string
	 */
	private function render_cookie_list( $with_css_effects_class )
	{
		$s    = $this->the_settings;
		$lang = $this->current_lang;
		$t    = $this->the_translations[ $lang ];

		$is_enabled_text    = esc_html( $t['is_enabled'] );
		$is_disabled_text   = esc_html( $t['is_disabled'] );
		$always_enable_text = esc_html( $t['always_enable'] );

		$enable_cmode_v2        = ( isset( $s['enable_cmode_v2'] ) && $s['enable_cmode_v2'] && !( isset( $s['bypass_cmode_enable'] ) && $s['bypass_cmode_enable'] ) );
		$enable_microsoft_cmode = ( isset( $s['enable_microsoft_cmode'] ) && $s['enable_microsoft_cmode'] );
		$enable_clarity_cmode   = ( isset( $s['enable_clarity_cmode'] ) && $s['enable_clarity_cmode'] );

		$consent_mode_valid_post_api_key           = array( 'my_agile_pixel_ga', 'google_analytics', 'google_tag_manager', 'stape' );
		$microsoft_consent_mode_valid_post_api_key = array( 'microsoft_ads' );
		$clarity_consent_mode_valid_post_api_key   = array( 'microsoft_clarity' );

		$consent_mode_options_shown           = false;
		$microsoft_consent_mode_options_shown = false;
		$clarity_consent_mode_options_shown   = false;

		$cookies_categories_data = $this->frontend->get_cookie_categories_description( 'publish' );

		$all_remote_ids = array();
		$html           = '';

		foreach( $cookies_categories_data as $k => $v )
		{
			foreach( $v as $key => $value )
			{
				$the_remote_id = $value['remote_id'];

				if( in_array( $the_remote_id, $all_remote_ids ) ) continue;
				$all_remote_ids[] = $the_remote_id;

				$cleaned_cookie_name         = str_replace( '"', '', $value['post_title'] );
				$the_post_is_readonly        = isset( $value['post_meta']['_map_is_readonly'][0] )                 ? $value['post_meta']['_map_is_readonly'][0]                 : false;
				$the_post_installation_type  = isset( $value['post_meta']['_map_installation_type'][0] )           ? $value['post_meta']['_map_installation_type'][0]           : 'js_noscript';
				$the_post_code               = isset( $value['post_meta']['_map_code'][0] )                        ? $value['post_meta']['_map_code'][0]                        : null;
				$the_noscript                = isset( $value['post_meta']['_map_noscript'][0] )                    ? $value['post_meta']['_map_noscript'][0]                    : null;
				$the_post_raw_code           = isset( $value['post_meta']['_map_raw_code'][0] )                    ? $value['post_meta']['_map_raw_code'][0]                    : null;
				$the_post_api_key            = isset( $value['post_meta']['_map_api_key'][0] )                     ? $value['post_meta']['_map_api_key'][0]                     : null;
				$page_reload_on_user_consent = isset( $value['post_meta']['_map_page_reload_on_user_consent'][0] ) ? $value['post_meta']['_map_page_reload_on_user_consent'][0] : null;

				$this_to_show_consent_mode           = false;
				$this_to_show_microsoft_consent_mode = false;
				$this_to_show_clarity_consent_mode   = false;
				$this_extra_header_class             = '';
				$this_extra_content_class            = '';
				$this_content_display                = 'display:none;';

				if( !$consent_mode_options_shown && $enable_cmode_v2 && $the_post_api_key && in_array( $the_post_api_key, $consent_mode_valid_post_api_key ) )
				{
					$this_to_show_consent_mode  = true;
					$consent_mode_options_shown = true;
					$this_extra_header_class    = ' map-tab-active map-do-not-collapse';
					$this_extra_content_class   = ' map-do-not-collapse';
					$this_content_display       = 'display:block;';
				}

				if( !$microsoft_consent_mode_options_shown && $enable_microsoft_cmode && $the_post_api_key && in_array( $the_post_api_key, $microsoft_consent_mode_valid_post_api_key ) )
				{
					$this_to_show_microsoft_consent_mode  = true;
					$microsoft_consent_mode_options_shown = true;
					$this_extra_header_class              = ' map-tab-active map-do-not-collapse';
					$this_extra_content_class             = ' map-do-not-collapse';
					$this_content_display                 = 'display:block;';
				}

				if( !$clarity_consent_mode_options_shown && $enable_clarity_cmode && $the_post_api_key && in_array( $the_post_api_key, $clarity_consent_mode_valid_post_api_key ) )
				{
					$this_to_show_clarity_consent_mode  = true;
					$clarity_consent_mode_options_shown = true;
					$this_extra_header_class            = ' map-tab-active map-do-not-collapse';
					$this_extra_content_class           = ' map-do-not-collapse';
					$this_content_display               = 'display:block;';
				}

				$wrapper_added_class = '';

				if( $k == 'necessary' )
				{
					$wrapper_added_class .= ' _always_on';
				}
				else
				{
					if( $page_reload_on_user_consent ) $wrapper_added_class .= ' map_page_reload_on_user_consent';
				}

				if( $the_post_code || $the_post_raw_code ) $wrapper_added_class .= ' _with_code';

				$html .= $this->render_cookie_item(
					$k,
					$value,
					$the_remote_id,
					$cleaned_cookie_name,
					$the_post_api_key,
					$the_post_code,
					$the_post_raw_code,
					$the_noscript,
					$the_post_installation_type,
					$the_post_is_readonly,
					$wrapper_added_class,
					$with_css_effects_class,
					$this_extra_header_class,
					$this_extra_content_class,
					$this_content_display,
					$always_enable_text,
					$is_enabled_text,
					$is_disabled_text,
					$this_to_show_consent_mode,
					$this_to_show_microsoft_consent_mode,
					$this_to_show_clarity_consent_mode
				);
			}
		}

		return $html;
	}

	/**
	 * Returns the HTML for a single cookie item row in the modal list.
	 *
	 * @param string $k
	 * @param array  $value
	 * @param string $the_remote_id
	 * @param string $cleaned_cookie_name
	 * @param string $the_post_api_key
	 * @param string $the_post_code
	 * @param string $the_post_raw_code
	 * @param string $the_noscript
	 * @param string $the_post_installation_type
	 * @param bool   $the_post_is_readonly
	 * @param string $wrapper_added_class
	 * @param string $with_css_effects_class
	 * @param string $this_extra_header_class
	 * @param string $this_extra_content_class
	 * @param string $this_content_display
	 * @param string $always_enable_text
	 * @param string $is_enabled_text
	 * @param string $is_disabled_text
	 * @param bool   $this_to_show_consent_mode
	 * @param bool   $this_to_show_microsoft_consent_mode
	 * @param bool   $this_to_show_clarity_consent_mode
	 * @return string
	 */
	private function render_cookie_item(
		$k,
		$value,
		$the_remote_id,
		$cleaned_cookie_name,
		$the_post_api_key,
		$the_post_code,
		$the_post_raw_code,
		$the_noscript,
		$the_post_installation_type,
		$the_post_is_readonly,
		$wrapper_added_class,
		$with_css_effects_class,
		$this_extra_header_class,
		$this_extra_content_class,
		$this_content_display,
		$always_enable_text,
		$is_enabled_text,
		$is_disabled_text,
		$this_to_show_consent_mode,
		$this_to_show_microsoft_consent_mode,
		$this_to_show_clarity_consent_mode
	)
	{
		$s = $this->the_settings;
		$t = $this->the_translations[ $this->current_lang ];

		$html  = '<div class="map-tab-section map_cookie_description_wrapper '.esc_attr( $wrapper_added_class ).'" data-cookie-baseindex="'.esc_attr( $the_remote_id ).'" data-cookie-name="'.esc_attr( $cleaned_cookie_name ).'" data-cookie-api-key="'.esc_attr( $the_post_api_key ).'">';
		$html .= '<div class="map-tab-header map-standard-header '.esc_attr( $with_css_effects_class.$this_extra_header_class ).'">';
		$html .= '<a class="map_expandItem map-nav-link map-settings-mobile" data-toggle="map-toggle-tab" role="button" tabindex="0">'.esc_html( $value['post_title'] ).'</a>';

		if( $k == 'necessary' )
		{
			$html .= '<span class="map-necessary-caption" role="button" tabindex="0">'.esc_html( $always_enable_text ).'</span>';
		}
		else
		{
			$html .=
				'<div class="map-switch">'.
					'<input data-cookie-baseindex="'.esc_attr( $the_remote_id ).'" type="checkbox" id="map-checkbox-'.esc_attr( $the_remote_id ).'" class="map-user-preference-checkbox MapDoNotTouch"/>'.
					'<div class="map-slider map-for-map-checkbox-'.esc_attr( $the_remote_id ).'" role="checkbox" aria-label="'.esc_attr( $value['post_title'] ).'" tabindex="0" aria-checked="mixed" data-map-enable="'.esc_attr( $is_enabled_text ).'" data-map-disable="'.esc_attr( $is_disabled_text ).'">'.
						'<span class="sr-only">'.esc_html( $value['post_title'] ).'</span>'.
					'</div>'.
				'</div>';
		}

		$html .= '</div>'; // map-tab-header

		$html .= '<div class="map-tab-content'.esc_attr( $this_extra_content_class ).'" style="'.esc_attr( $this_content_display ).'">';
		$html .= '<div data-nosnippet class="map-tab-pane map-fade">'.wp_kses_post( $value['post_content'] ).'</div>';

		if( isset( $s['pa'] ) && $s['pa'] == 1 && $this_to_show_clarity_consent_mode && is_array( $this->clarity_consent_mode_consents ) )
		{
			$html .= '<p>'.esc_html( $t['additional_consents'] ).':</p>';
			foreach( $this->clarity_consent_mode_consents as $vv )
			{
				$html .= $this->render_consent_mode_item( $vv, $is_enabled_text, $is_disabled_text, 'map-consent-clarity' );
			}
		}

		if( isset( $s['pa'] ) && $s['pa'] == 1 && $this_to_show_microsoft_consent_mode && is_array( $this->microsoft_consent_mode_consents ) )
		{
			$html .= '<p>'.esc_html( $t['additional_consents'] ).':</p>';
			foreach( $this->microsoft_consent_mode_consents as $vv )
			{
				$html .= $this->render_consent_mode_item( $vv, $is_enabled_text, $is_disabled_text, 'map-consent-microsoft' );
			}
		}

		if( isset( $s['pa'] ) && $s['pa'] == 1 && $this_to_show_consent_mode && is_array( $this->consent_mode_consents ) )
		{
			$html .= '<p>'.esc_html( $t['additional_consents'] ).':</p>';
			foreach( $this->consent_mode_consents as $vv )
			{
				$html .= $this->render_consent_mode_item( $vv, $is_enabled_text, $is_disabled_text, 'map-consent-google' );
			}
		}

		$html .= '</div>'; // map-tab-content

		if( $the_post_is_readonly == false && $the_post_installation_type == 'js_noscript' && $the_post_code )
		{
			$html .= '<script type="text/plain" class="my_agile_privacy_activate _js_noscript_type_mode" data-cookie-baseindex="'.esc_attr( $the_remote_id ).'" data-cookie-name="'.esc_attr( $cleaned_cookie_name ).'" data-cookie-api-key="'.esc_attr( $the_post_api_key ).'">'.strip_tags( stripslashes( $the_post_code ) ).'</script>';

			if( $the_noscript )
			{
				$html .= '<noscript data-cookie-baseindex="'.esc_attr( $the_remote_id ).'" data-cookie-name="'.esc_attr( $cleaned_cookie_name ).'" data-cookie-api-key="'.esc_attr( $the_post_api_key ).'">'.htmlspecialchars_decode( stripslashes( $the_noscript ), ENT_QUOTES ).'</noscript>';
			}
		}
		elseif( $the_post_is_readonly == false && $the_post_installation_type == 'raw_code' && $the_post_raw_code )
		{
			$html .= '<textarea style="display:none;" class="my_agile_privacy_activate _raw_type_mode" data-cookie-baseindex="'.esc_attr( $the_remote_id ).'" data-cookie-name="'.esc_attr( $cleaned_cookie_name ).'" data-cookie-api-key="'.esc_attr( $the_post_api_key ).'">'.htmlspecialchars_decode( stripslashes( $the_post_raw_code ), ENT_QUOTES ).'</textarea>';
		}

		$html .= '</div>'; // map_cookie_description_wrapper

		return $html;
	}

	/**
	 * Returns the HTML for a single consent mode item (Google, Microsoft, Clarity).
	 *
	 * @param array  $vv               Consent item with keys: key, human_name, human_desc
	 * @param string $is_enabled_text
	 * @param string $is_disabled_text
	 * @param string $extra_class      e.g. 'map-consent-google'
	 * @return string
	 */
	private function render_consent_mode_item( $vv, $is_enabled_text, $is_disabled_text, $extra_class )
	{
		$html  = '<div class="map-tab-section map_consent_description_wrapper" data-consent-key="'.esc_attr( $vv['key'] ).'">';
			$html .= '<div class="map-tab-header map-standard-header map-nocursor withEffects">';
				$html .= '<a class="map_expandItem map-contextual-expansion map-nav-link map-consent-mode-link map-settings-mobile" data-toggle="map-toggle-tab" role="button" tabindex="0">'.esc_html( $vv['human_name'] ).'</a>';
				$html .= '<div class="map-switch">';
					$html .= '<input type="checkbox" id="map-consent-'.esc_attr( $vv['key'] ).'" class="map-consent-mode-preference-checkbox '.esc_attr( $extra_class ).' MapDoNotTouch" data-consent-key="'.esc_attr( $vv['key'] ).'">';
					$html .=
						'<div'.
							' class="map-slider map-nested map-for-map-consent-'.esc_attr( $vv['key'] ).'"'.
							' data-map-enable="'.esc_attr( $is_enabled_text ).'"'.
							' data-map-disable="'.esc_attr( $is_disabled_text ).'"'.
							' role="checkbox"'.
							' aria-label="'.esc_attr( $vv['human_name'] ).'"'.
							' tabindex="0"'.
							' aria-checked="mixed">'.
							'<span class="sr-only">'.esc_html( $vv['human_name'] ).'</span>'.
						'</div>';
					$html .= '</div>'; // map-switch
				$html .= '</div>'; // map-tab-header
				$html .= '<div class="map-tab-content" style="display: none;">';
				$html .= '<div data-nosnippet="" class="map-tab-pane map-fade">'.esc_html( $vv['human_desc'] ).'</div>';
				$html .= '</div>'; // map-tab-content
		$html .= '</div>'; // map_consent_description_wrapper

		return $html;
	}

	/**
	 * Returns the credits footer HTML for the modal.
	 *
	 * @return string
	 */
	private function render_credits()
	{
		$s    = $this->the_settings;
		$wl_b = isset( $s['wl_b'] ) ? intval( $s['wl_b'] ) : 0;

		if( $wl_b != 0 ) return '<br>';

		$anchor_texts = array();

		$anchor_texts[] = $this->the_translations[ $this->current_lang ]['anchor_text_1'];
		$anchor_texts[] = $this->the_translations[ $this->current_lang ]['anchor_text_2'];
		$anchor_texts[] = $this->the_translations[ $this->current_lang ]['anchor_text_3'];
		$anchor_texts[] = $this->the_translations[ $this->current_lang ]['anchor_text_4'];
		$anchor_texts[] = $this->the_translations[ $this->current_lang ]['anchor_text_5'];
		$anchor_texts[] = $this->the_translations[ $this->current_lang ]['anchor_text_6'];

		$site_name = get_bloginfo( 'name' );
		if ( empty( $site_name ) )
		{
		    $site_name = home_url();
		}
		$anchor_text_index = abs( crc32( $site_name ) ) % count( $anchor_texts );
		$anchor_text = $anchor_texts[$anchor_text_index];

		$is_pro         = isset( $s['pa'] ) && $s['pa'] == 1;
		$img_pro        = '<img src="'.esc_attr( plugin_dir_url( __DIR__ ) ).'frontend/img/privacy-by-pro.png" alt="'.esc_attr( $anchor_text ).'" width="111" height="50">';
		$img_basic      = '<img src="'.esc_attr( plugin_dir_url( __DIR__ ) ).'frontend/img/privacy-by-basic.png" alt="'.esc_attr( $anchor_text ).'" width="111" height="50">';
		$img_pro_wide   = '<img src="'.esc_attr( plugin_dir_url( __DIR__ ) ).'frontend/img/privacy-by-pro.png" alt="'.esc_attr( $anchor_text ).'" width="435" height="40">';
		$img_basic_wide = '<img src="'.esc_attr( plugin_dir_url( __DIR__ ) ).'frontend/img/privacy-by-basic.png" alt="'.esc_attr( $anchor_text ).'" width="435" height="40">';

		$html = '<div data-nosnippet class="modal_credits">';

		if( isset( $this->rconfig ) && $this->rconfig['credits_image_only'] == 1 )
		{
			$html .= $is_pro ? $img_pro_wide : $img_basic_wide;
		}
		else
		{
			$about_url = 'https://www.myagileprivacy.com/en/you-are-visiting-from-a-trusted-site/?utm_source=referral&utm_medium=plugin-pro&utm_campaign=customize';
			$about_url_basic = 'https://www.myagileprivacy.com/en/?utm_source=referral&utm_medium=plugin-pro&utm_campaign=customize_free';

			if( $this->current_lang && $this->current_lang == 'it_IT' )
			{
				$about_url = 'https://www.myagileprivacy.com/about/?utm_source=referral&utm_medium=plugin-pro&utm_campaign=customize';
				$about_url_basic = 'https://www.myagileprivacy.com/?utm_source=referral&utm_medium=plugin-pro&utm_campaign=customize_free';
			}

			if( $is_pro )
			{
				$html .= '<a href="'.esc_attr( $about_url ).'" target="_blank" rel="nofollow" tabindex="-1" aria-label="'.esc_attr( $anchor_text ).'">'.$img_pro.'</a>';
			}
			else
			{
				$html .= '<a href="'.esc_attr( $about_url_basic ).'" target="_blank" tabindex="-1" aria-label="'.esc_attr( $anchor_text ).'">'.$img_basic.'</a>';
			}
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Returns the custom CSS block HTML, or empty string if no custom CSS is set.
	 *
	 * @return string
	 */
	private function render_custom_css()
	{
		$custom_css = $this->the_settings['custom_css'];

		if( !$custom_css ) return '';

		return '<style type="text/css">'.esc_attr( $custom_css ).'</style>'.PHP_EOL;
	}
}

// --- instantiate and render ---

$renderer = new MyAgilePrivacyBannerRenderer(
	$the_settings,
	$rconfig,
	$the_translations,
	$current_lang,
	$is_polylang_enabled,
	$is_wpml_enabled,
	$clarity_consent_mode_consents,
	$microsoft_consent_mode_consents,
	$consent_mode_consents,
	$this
);

return $renderer->render();