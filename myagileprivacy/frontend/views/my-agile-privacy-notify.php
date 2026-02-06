<?php

if( !defined( 'MAP_PLUGIN_NAME' ) )
{
	exit('Not allowed.');
}

// disable indexing
echo "<!--googleoff: all-->";

//bof first layer var init

$layer2_overflow_wrapper_class = "map-cookielist-overflow-container";

if( $rconfig &&
	isset( $rconfig['disable_layer2_overflow'] ) &&
	$rconfig['disable_layer2_overflow'] )
{
	$layer2_overflow_wrapper_class = "";
}

$iab_tcf_context = false;
$iab_extra_class = '';

if(
	$rconfig &&
	isset( $rconfig['allow_iab'] ) &&
	$rconfig['allow_iab'] == 1 &&
	$the_settings['enable_iab_tcf']
)
{
	$iab_tcf_context = true;
	$iab_extra_class = 'map-iab-context';
}

$enable_cmode_v2 = false;

if( isset( $the_settings ) &&
	isset( $the_settings['enable_cmode_v2'] ) && $the_settings['enable_cmode_v2'] &&
	!( isset( $the_settings['bypass_cmode_enable'] ) && $the_settings['bypass_cmode_enable'] == true )
)
{
	$enable_cmode_v2 = true;
}


$enable_microsoft_cmode = false;

if( isset( $the_settings ) &&
	isset( $the_settings['enable_microsoft_cmode'] ) && $the_settings['enable_microsoft_cmode'] )
{
	$enable_microsoft_cmode = true;
}

$enable_clarity_cmode = false;

if( isset( $the_settings ) &&
	isset( $the_settings['enable_clarity_cmode'] ) && $the_settings['enable_clarity_cmode'] )
{
	$enable_clarity_cmode = true;
}


$always_enable_text = esc_html( $the_translations[ $current_lang ]['always_enable'] );
$is_enabled_text = esc_html( $the_translations[ $current_lang ]['is_enabled'] );
$is_disabled_text = esc_html( $the_translations[ $current_lang ]['is_disabled'] );
$blocked_content_text = esc_html( $the_translations[ $current_lang ]['blocked_content'] ).':';

$color_style = ( $the_settings['text'] != "" ) ? 'color:'.$the_settings['text'].' !important' : '';
$background_style = $the_settings['background'] != "" ? 'background-color:'.$the_settings['background'] : '';
$border_radius_style = 'border-radius:'.$the_settings['elements_border_radius'].'px';

$text_size = 'font-size:'.$the_settings['text_size'].'px!important';
$text_lineheight = 'line-height:'.$the_settings['text_lineheight'].'px!important';

$composed_style = implode( ';', array( $color_style, $background_style, $border_radius_style, $text_size, $text_lineheight  ) );

$composed_style_paragraph_first_layer = implode( ';', array( $text_size, $text_lineheight, $color_style ) );
$composed_style_paragraph_second_layer = implode( ';', array( $text_size, $text_lineheight ) );

$notification_bar_composed_style = implode( ';', array( $color_style, $background_style, $border_radius_style ) );

$position_class = ( $the_settings['is_bottom'] == false ) ? 'isTop' : 'isBottom';
$with_css_effects_class = ( $the_settings['with_css_effects'] == true ) ? 'withEffects' : '';

$map_shadow_class = ( $the_settings['cookie_banner_shadow'] == false ) ? '' : $the_settings['cookie_banner_shadow'];

$map_heading_class = ( $the_settings['title_is_on'] == true ) ? '' : 'map_displayNone';
$map_heading_style = 'background-color:'.$the_settings['heading_background_color'].'; color: '.$the_settings['heading_text_color'].';';

$map_close_button_style = ( $the_settings['title_is_on'] == true ) ? 'color: '.$the_settings['heading_text_color'].'!important;' : 'color: '.$the_settings['text'].'!important;';

//graphic settings
$cookie_banner_vertical_position = $the_settings['cookie_banner_vertical_position'];
$cookie_banner_horizontal_position = $the_settings['cookie_banner_horizontal_position'];

$new_position = null;

if( $cookie_banner_vertical_position )
{
	switch( $the_settings['cookie_banner_size'] )
	{
		case 'sizeWideBranded':
			$new_size = "mapSizeWideBranded";
			$cookie_banner_horizontal_position = 'Center';
			break;
		case 'sizeWide':
			$new_size = "mapSizeWide";
			$cookie_banner_horizontal_position = 'Center';
			break;
		case 'sizeBig':
			$new_size = "mapSizeBig mapSizeBoxed";
			break;
		case 'sizeBoxed':
			$new_size = "mapSizeBoxed";
			break;
		default:
		   $new_size = "mapSizeBoxed";
	}

	$new_position = 'mapPosition'.$cookie_banner_vertical_position.$cookie_banner_horizontal_position;
}

//retro compatibility
if( !$new_position )
{
	if( $the_settings['is_bottom'] )
	{
		$new_position = 'mapPositionBottomCenter';
	}
	else
	{
		$new_position = 'mapPositionTopCenter';
	}

	$new_size = 'mapSizeBoxed';
}

if( !( isset( $the_settings['pa'] ) &&  $the_settings['pa'] ) )
{
	$new_size = 'mapSizeWideBranded';
}

$floating_banner = ( $the_settings['floating_banner'] == false ) ? '' : 'map_floating_banner';
$animation_class = 'map_animation_'.$the_settings['cookie_banner_animation'];

//composed_class with new css and options
$composed_class = implode( ' ', array( $new_position, $new_size, $floating_banner, $with_css_effects_class, $animation_class, $map_shadow_class  ) );


//bof paragraph split

$allowed_paragraph_split = false;
$map_notification_container_flex = '';

if( !$iab_tcf_context &&
	$the_translations[ $current_lang ]['notify_message_v2'] == $the_translations[ $current_lang ]['notify_message_v2_short'] )
{
	$allowed_paragraph_split = true;
	$map_notification_container_flex = 'map_flex';
}

$branded_content = '';

if( $the_settings['cookie_banner_size'] == 'sizeWideBranded' || !( isset( $the_settings['pa'] ) &&  $the_settings['pa'] )  )
{
	$branded_content = '<div class="map_branded-box">';
	$branded_content .= '<img src="' . esc_attr( plugin_dir_url( __DIR__ ) ) . '/img/map_logo_branded.svg" alt="Privacy and Consent by My Agile Privacy®">';
	$branded_content .= '</div>';
}

//eof first layer var init

//bof second layer var init


$cookies_categories_data = $this->get_cookie_categories_description( 'publish' );

$disable_logo = $the_settings['disable_logo'];

$cookie_policy_link = ( isset( $the_settings ) && isset( $the_settings['cookie_policy_link'] ) ) ? $the_settings['cookie_policy_link'] : null;
$is_cookie_policy_url = ( isset( $the_settings ) && isset( $the_settings['is_cookie_policy_url'] ) ) ? $the_settings['is_cookie_policy_url'] : null;
$cookie_policy_url = ( isset( $the_settings ) && isset( $the_settings['cookie_policy_url'] ) ) ? $the_settings['cookie_policy_url'] : null;
$cookie_policy_page = ( isset( $the_settings ) && isset( $the_settings['cookie_policy_page'] ) ) ? $the_settings['cookie_policy_page'] : null;

$personal_data_policy_link = ( isset( $the_settings ) && isset( $the_settings['personal_data_policy_link'] ) ) ? $the_settings['personal_data_policy_link'] : null;
$is_personal_data_policy_url = ( isset( $the_settings ) && isset( $the_settings['is_personal_data_policy_url'] ) ) ? $the_settings['is_personal_data_policy_url'] : null;
$personal_data_policy_url = ( isset( $the_settings ) && isset( $the_settings['personal_data_policy_url'] ) ) ? $the_settings['personal_data_policy_url'] : null;
$personal_data_policy_page = ( isset( $the_settings ) && isset( $the_settings['personal_data_policy_page'] ) ) ? $the_settings['personal_data_policy_page'] : null;


$showagain_tab = $the_settings['showagain_tab'];
$custom_css = $the_settings['custom_css'];
$notify_position_horizontal = $the_settings['notify_position_horizontal'];
$blocked_content_notify = ( $the_settings['pa'] == 1 && $the_settings['blocked_content_notify'] );
$wl_b = ( isset( $the_settings['wl_b'] ) ) ? intval( $the_settings['wl_b'] ) : 0;

$cookie_policy_html_inside_banner = "";
$cookie_policy_html_inside_popup = "";
$personal_data_policy_html_inside_popup = "";

//load cookie policy url
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

//load personal data policy url
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

if( $the_cookie_policy_url )
{
	$cookie_policy_html_inside_popup = '<a target="blank" href="'.esc_url( $the_cookie_policy_url ).'" tabindex="0" aria-label="Cookie Policy">'.esc_html( $the_translations[ $current_lang ]['view_the_cookie_policy'] ).'</a>';
}

if( $the_personal_data_policy_url )
{
	$personal_data_policy_html_inside_popup = '<a target="blank" href="'.esc_url( $the_personal_data_policy_url ).'" tabindex="0" aria-label="Personal Data Policy">'.esc_html( $the_translations[ $current_lang ]['view_the_personal_data_policy'] ).'</a>';
}

if( $cookie_policy_link && $the_cookie_policy_url )
{
	$cookie_policy_html_inside_banner = $cookie_policy_html_inside_popup;
}

$spacing_text = "";
if( $cookie_policy_link && $cookie_policy_html_inside_banner )
{
	$spacing_text = " / ";
}

$show_again = esc_html( $the_translations[ $current_lang ]['manage_consent'] );

$showagain_div_classes = "map_displayNone";

if( $disable_logo )
{
	$showagain_div_classes .= " nologo";
}

if( $showagain_tab == 0 )
{
	$showagain_div_classes .= " disactive";
}

if( $the_settings['with_css_effects'] == true )
{
	$showagain_div_classes .= " withEffects";
}

switch( $notify_position_horizontal )
{
	case 'left':
		$showagain_div_classes .= " left_position";
		break;
	case 'right':
		$showagain_div_classes .= " right_position";
		break;
}

// new positioning with retrocompatibility
// new position is related to the new position option for the cookie banner
if( !$new_position )
{
	$position_class_alt = ( $the_settings['is_bottom'] == false ) ? 'isBottom' : 'isTop';
}
else
{
	// if banner is top, notification in bottom
	// if banner is center or bottom, notification is top
	$position_class_alt = ( $cookie_banner_vertical_position == 'Top' ) ? 'isBottom': 'isTop';
}

$notify_logo_color = ( $the_settings['heading_background_color'] == '#ffffff' ) ? '#F93F00' : $the_settings['heading_background_color'];

$the_empty_href = "#";

//eof second layer var init


$notify_message_v2 = do_shortcode( stripslashes( esc_html( $the_translations[ $current_lang ]['notify_message_v2'].'[myagileprivacy_extra_info]' ) ) );

if( $allowed_paragraph_split )
{
	// Use preg_split to split the text at every period
	$paragraphs = preg_split( '/\.(?!\d)/', $notify_message_v2 );

	// Trim whitespace and remove empty paragraphs
	$paragraphs = array_map( 'trim', $paragraphs );
	$paragraphs = array_filter( $paragraphs );

	if( count( $paragraphs ) > 1 )
	{
		$notify_message_v2 = "";
		foreach( $paragraphs as $paragraph )
		{
			if( $paragraph && $paragraph != '' )
			{
				$notify_message_v2 .= '<p class="map_p_splitted" style="'.esc_attr( $composed_style_paragraph_first_layer ).'">'.$paragraph.'.&nbsp;</p>';
			}
		}
	}
}

//eof paragraph split

$banner_logo_color = $the_settings['heading_text_color'];

$map_notification_container_extra_class = implode( ' ', array( $iab_extra_class, $map_notification_container_flex ) );

//bof button order
$button_shortcode_string = "";
$default_button_shortcode_string = '[myagileprivacy_cookie_accept][myagileprivacy_cookie_reject][myagileprivacy_cookie_customize]';

$layer_1_button_order = $the_settings['layer_1_button_order'];

$layer_1_button_order_array = explode( '_', $layer_1_button_order );

if( count( $layer_1_button_order_array ) == 3 )
{
	foreach( $layer_1_button_order_array as $shortcode_base_elem )
	{
		$button_shortcode_string .= "[myagileprivacy_cookie_".$shortcode_base_elem."]";
	}
}
else
{
	$button_shortcode_string = $default_button_shortcode_string;
}
//eof button order


$first_layer_extra_text = '';



$cookie_policy_first_layer_check = ( $cookie_policy_html_inside_popup &&
	isset( $the_settings['add_cookie_policy_to_first_layer'] ) &&
	$the_settings['add_cookie_policy_to_first_layer'] );

$personal_data_policy_first_layer_check = ( $personal_data_policy_html_inside_popup &&
	isset( $the_settings['add_personal_policy_to_first_layer'] ) &&
	$the_settings['add_personal_policy_to_first_layer'] );

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



//apply shortcodes
$notify_message_v2 = do_shortcode( '<div class="map-area-container"><div data-nosnippet class="map_notification-message '.esc_attr( $map_notification_container_extra_class ).'">'. $notify_message_v2.$first_layer_extra_text.'</div>'.'<div class="map_notification_container '. esc_attr( $iab_extra_class ).'">'.esc_attr( $button_shortcode_string ).'</div></div>' );

$banner_title = esc_html( $the_translations[ $current_lang ]['banner_title'] );

$notify_title = '<div class="map_notify_title '.esc_attr( $map_heading_class ).'" style="'.esc_attr( $map_heading_style ).';">'.
						( $banner_title != '' ? esc_html( $banner_title ) : '<div class="banner-title-logo" style="background:'.esc_attr( $banner_logo_color ).';"></div> My Agile Privacy®' )
					. '</div>';

$notify_close_button = '<div class="map-closebutton-right"><a tabindex="0" role="button" class="map-button map-reject-button" data-map_action="reject" style="'.esc_attr( $map_close_button_style ).'">&#x2715;</a></div>';

$notify_html = '<div
					role="dialog"
					aria-label="My Agile Privacy®"
					tabindex="0"
					id="my-agile-privacy-notification-area"
					class="'.esc_attr( $composed_class ).' mapButtonsAside"
					data-nosnippet="true"
					style="'.esc_attr( $composed_style ).'" data-animation="'.$the_settings['cookie_banner_animation'].'">'.
					$notify_title.
					$notify_close_button.
					'<div id="my-agile-privacy-notification-content">'.
					$branded_content.
					$notify_message_v2.
					'</div>'.
			 	'</div>';


$notify_html .= '<div data-nosnippet class="'.esc_attr( $showagain_div_classes ).'" id="' . esc_attr( $the_settings["showagain_div_id"] ) . '" style="'.esc_attr( $border_radius_style ).'"><div class="map_logo_container" style="background-color:'.$notify_logo_color.';"></div><a tabindex="0" role="button" class="showConsent" href="'.$the_empty_href.'" data-nosnippet>' . wp_kses( $show_again, MyAgilePrivacy::allowed_html_tags() ) . '</a>'.$spacing_text.$cookie_policy_html_inside_banner.'</div>';

echo $notify_html;

$autoclose = ( $the_settings['blocked_content_notify_auto_shutdown'] == false ) ? '' : 'autoShutDown';
$composed_class_alt = implode( ' ', array( $position_class_alt, $autoclose, $with_css_effects_class, $map_shadow_class ) );

$blocked_content_notification_html = "";

if( $blocked_content_notify )
{
	$blocked_content_notification_html =  '<div
												class="map-blocked-content-notification-area '.esc_attr( $composed_class_alt ).'"
												id="map-blocked-content-notification-area"
												style="'.esc_attr( $notification_bar_composed_style ).'"
											>
											<div class="map-area-container">
												<div class="map_notification-message data-nosnippet">'.
													$blocked_content_text.'<br>
													<span class="map_blocked_elems_desc"></span>
												</div>
											</div>
										</div>';
}

echo wp_kses( $blocked_content_notification_html, MyAgilePrivacy::allowed_html_tags() );
?>

<div class="map-modal"
	id="mapSettingsPopup"
	data-nosnippet="true"
	role="dialog"
	tabindex="0"
	aria-labelledby="mapSettingsPopup"
	>
  <div class="map-modal-dialog">
	<div class="map-modal-content map-bar-popup <?php echo esc_attr( $with_css_effects_class ); ?>">
	  <div class="map-modal-body">

		<div class="map-container-fluid map-tab-container">

			<div class="map-privacy-overview">
				<p class="map-h4-heading" data-nosnippet><?php echo esc_html( $the_translations[ $current_lang ]['privacy_settings'] ); ?></p>
			</div>

			<p data-nosnippet
				style="<?php echo esc_attr( $composed_style_paragraph_second_layer ); ?>"

				>
				<?php echo esc_html( $the_translations[ $current_lang ]['this_website_uses_cookies'] ); ?><br>
				<span class="map-modal-cookie-policy-link"><?php echo wp_kses( $cookie_policy_html_inside_popup, MyAgilePrivacy::allowed_html_tags() ) ; ?> <?php echo wp_kses( $personal_data_policy_html_inside_popup, MyAgilePrivacy::allowed_html_tags() ) ; ?></span>
			</p>
			<div class="<?php echo esc_attr( $layer2_overflow_wrapper_class ); ?>">
				<?php
				if( $iab_tcf_context ):
				?>

					<div class="map-consent-extrawrapper">

						<ul class="map-wrappertab-navigation">

							<li><a href="#map-privacy-cookie-thirdypart-wrapper" class="map-tab-navigation active-wrappertab-nav" role="button" tabindex="0"><?php echo esc_html( $the_translations[ $current_lang ]['cookies_and_thirdy_part_software'] );  ?></a></li>
							<li><a href="#map-privacy-iab-tcf-wrapper" class="map-tab-navigation" role="button" tabindex="0"><?php echo esc_html( $the_translations[ $current_lang ]['advertising_preferences'] );  ?></a></li>

						</ul>

						<div id="map-privacy-cookie-thirdypart-wrapper" class="map-wrappertab map-wrappertab-active map-privacy-cookie-thirdypart-wrapper">

				<?php
				endif;
				?>

				<?php

				$consent_mode_options_shown = false;
				$consent_mode_valid_post_api_key = array(
					'my_agile_pixel_ga',
					'google_analytics',
					'google_tag_manager',
					'stape'
				);

				$microsoft_consent_mode_options_shown = false;
				$microsoft_consent_mode_valid_post_api_key = array(
					'microsoft_ads',
				);


				$clarity_consent_mode_options_shown = false;
				$clarity_consent_mode_valid_post_api_key = array(
					'microsoft_clarity',
				);

				//preventing double cookie print
				$all_remote_ids = array();
				
				foreach( $cookies_categories_data as $k => $v ):

					foreach( $v as $key => $value ):

						$the_remote_id = $value['remote_id'];

						if( in_array( $the_remote_id, $all_remote_ids ) )
						{
							continue;
						}
						else
						{
							$all_remote_ids[] = $the_remote_id;
						}

						$cleaned_cookie_name = str_replace( '"', '', $value['post_title'] );
						$the_post_is_readonly = isset( $value['post_meta']['_map_is_readonly'][0] ) ? $value['post_meta']['_map_is_readonly'][0] : false;

						$the_post_installation_type = isset( $value['post_meta']['_map_installation_type'][0] ) ? $value['post_meta']['_map_installation_type'][0] : 'js_noscript';
						$the_post_code = isset( $value['post_meta']['_map_code'][0] ) ? $value['post_meta']['_map_code'][0] : null;
						$the_noscript = isset( $value['post_meta']['_map_noscript'][0] ) ? $value['post_meta']['_map_noscript'][0] : null;
						$the_post_raw_code = isset( $value['post_meta']['_map_raw_code'][0] ) ? $value['post_meta']['_map_raw_code'][0] : null;
						$the_post_api_key = isset( $value['post_meta']['_map_api_key'][0] ) ? $value['post_meta']['_map_api_key'][0] : null;
						$page_reload_on_user_consent = isset( $value['post_meta']['_map_page_reload_on_user_consent'][0] ) ? $value['post_meta']['_map_page_reload_on_user_consent'][0] : null;

						$this_to_show_consent_mode = false;
						$this_to_show_microsoft_consent_mode = false;
						$this_to_show_clarity_consent_mode = false;
						$this_extra_header_class = "";
						$this_extra_content_class = "";
						$this_content_display = 'display:none;';

						if( !$consent_mode_options_shown &&
							$enable_cmode_v2 &&
							$the_post_api_key &&
							is_array( $consent_mode_valid_post_api_key ) &&
							in_array( $the_post_api_key, $consent_mode_valid_post_api_key ) )
						{
							$this_to_show_consent_mode = true;
							$consent_mode_options_shown = true;

							$this_extra_header_class = " map-tab-active map-do-not-collapse";
							$this_extra_content_class = " map-do-not-collapse";
							$this_content_display = 'display:block;';
						}

						if( !$microsoft_consent_mode_options_shown &&
							$enable_microsoft_cmode &&
							$the_post_api_key &&
							is_array( $microsoft_consent_mode_valid_post_api_key ) &&
							in_array( $the_post_api_key, $microsoft_consent_mode_valid_post_api_key ) )
						{
							$this_to_show_microsoft_consent_mode = true;
							$microsoft_consent_mode_options_shown = true;

							$this_extra_header_class = " map-tab-active map-do-not-collapse";
							$this_extra_content_class = " map-do-not-collapse";
							$this_content_display = 'display:block;';
						}

						if( !$clarity_consent_mode_options_shown &&
							$enable_clarity_cmode &&
							$the_post_api_key &&
							is_array( $clarity_consent_mode_valid_post_api_key ) &&
							in_array( $the_post_api_key, $clarity_consent_mode_valid_post_api_key ) )
						{
							$this_to_show_clarity_consent_mode = true;
							$clarity_consent_mode_options_shown = true;

							$this_extra_header_class = " map-tab-active map-do-not-collapse";
							$this_extra_content_class = " map-do-not-collapse";
							$this_content_display = 'display:block;';
						}


						$map_cookie_description_wrapper_added_class = "";

						if( $k == 'necessary' )
						{
							$map_cookie_description_wrapper_added_class .= ' _always_on';
						}
						else
						{
							if( $page_reload_on_user_consent )
							{
								$map_cookie_description_wrapper_added_class .= ' map_page_reload_on_user_consent';
							}
						}

						if( $the_post_code || $the_post_raw_code )
						{
							$map_cookie_description_wrapper_added_class .= ' _with_code';
						}
				?>

					<div
						class="map-tab-section map_cookie_description_wrapper <?php echo esc_attr( $map_cookie_description_wrapper_added_class ); ?>"
						data-cookie-baseindex="<?php echo esc_attr( $the_remote_id ); ?>"
						data-cookie-name="<?php echo esc_attr( $cleaned_cookie_name ); ?>"
						data-cookie-api-key="<?php echo esc_attr( $the_post_api_key ); ?>">
						<div class="map-tab-header map-standard-header <?php echo esc_attr( $with_css_effects_class.$this_extra_header_class ); ?>">
							<a class="map_expandItem map-nav-link map-settings-mobile" data-toggle="map-toggle-tab" role="button" tabindex="0">
							<?php echo esc_html( $value['post_title'] ); ?>
							</a>
							<?php
								if( $k == 'necessary' ):
							?>
								<span class="map-necessary-caption" role="button" tabindex="0"><?php echo esc_html( $always_enable_text ) ?></span>
							<?php
								else:

									$map_switch = '<div class="map-switch">
														<input
															data-cookie-baseindex="'.esc_attr( $the_remote_id ).'"
															type="checkbox"
															id="map-checkbox-' . esc_attr( $the_remote_id ) . '"
															class="map-user-preference-checkbox MapDoNotTouch"
														/>
														<div
															class="map-slider map-for-map-checkbox-' . esc_attr( $the_remote_id ) . '"
															role="checkbox"
															aria-label="'.
																esc_attr( $value['post_title'] ) .'"
															tabindex="0"
															aria-checked="mixed"
															data-map-enable="'.esc_attr( $is_enabled_text ).'"
															data-map-disable="'.esc_attr( $is_disabled_text ).'">
															<span class="sr-only">' .
																esc_html( $value['post_title'] ) .
															'</span>
														</div>
													</div>';

									echo wp_kses( $map_switch , MyAgilePrivacy::allowed_html_tags() ) ;
							?>

							<?php
								endif;
							?>
						</div>
						<div class="map-tab-content <?php echo esc_attr( $this_extra_content_class ); ?>"
								style="<?php echo esc_attr( $this_content_display ); ?>"
						>
							<div data-nosnippet class="map-tab-pane map-fade">
								<?php
								echo wp_kses_post( $value['post_content'] );
								?>
							</div>

								<?php
									if( isset( $the_settings['pa'] ) &&
										$the_settings['pa'] == 1 &&
										$this_to_show_clarity_consent_mode ):
								?>

								<p><?php echo esc_html( $the_translations[ $current_lang ]['additional_consents'] ) ?>:</p>

								<?php
									foreach( $clarity_consent_mode_consents as $kk => $vv ):
								?>

									<div class="map-tab-section map_consent_description_wrapper" data-consent-key="<?php echo esc_attr( $vv['key'] ); ?>">
										<div class="map-tab-header map-standard-header map-nocursor withEffects">
											<a class="map_expandItem map-contextual-expansion map-nav-link map-consent-mode-link map-settings-mobile" data-toggle="map-toggle-tab" role="button" tabindex="0"><?php echo esc_html( $vv['human_name'] ) ?></a>
											<div class="map-switch" >
												<input type="checkbox" id="map-consent-<?php echo esc_attr( $vv['key'] ); ?>" class="map-consent-mode-preference-checkbox map-consent-clarity MapDoNotTouch" data-consent-key="<?php echo esc_attr( $vv['key'] ); ?>">
												<div
													class="map-slider map-nested map-for-map-consent-<?php echo esc_attr( $vv['key'] ); ?>"
													data-map-enable="<?php echo esc_attr( $is_enabled_text ); ?>" data-map-disable="<?php echo esc_attr( $is_disabled_text ); ?>"
													role="checkbox"
													aria-label="<?php echo esc_attr( $vv['human_name'] ); ?>"
													tabindex="0"
													aria-checked="mixed">
													<span class="sr-only"><?php echo esc_html( $vv['human_name'] ); ?></span>
												</div>
											</div>
										</div>
										<div class="map-tab-content" style="display: none;">
											<div data-nosnippet="" class="map-tab-pane map-fade">
											<?php echo esc_html( $vv['human_desc'] ); ?>
											</div>
										</div>
									</div>

								<?php
									endforeach;
									endif;
								?>


								<?php
									if( isset( $the_settings['pa'] ) &&
										$the_settings['pa'] == 1 &&
										$this_to_show_microsoft_consent_mode ):
								?>

								<p><?php echo esc_html( $the_translations[ $current_lang ]['additional_consents'] ) ?>:</p>

								<?php
									foreach( $microsoft_consent_mode_consents as $kk => $vv ):
								?>

									<div class="map-tab-section map_consent_description_wrapper" data-consent-key="<?php echo esc_attr( $vv['key'] ); ?>">
										<div class="map-tab-header map-standard-header map-nocursor withEffects">
											<a class="map_expandItem map-contextual-expansion map-nav-link map-consent-mode-link map-settings-mobile" data-toggle="map-toggle-tab" role="button" tabindex="0"><?php echo esc_html( $vv['human_name'] ) ?></a>
											<div class="map-switch" >
												<input type="checkbox" id="map-consent-<?php echo esc_attr( $vv['key'] ); ?>" class="map-consent-mode-preference-checkbox map-consent-microsoft MapDoNotTouch" data-consent-key="<?php echo esc_attr( $vv['key'] ); ?>">
												<div
													class="map-slider map-nested map-for-map-consent-<?php echo esc_attr( $vv['key'] ); ?>"
													data-map-enable="<?php echo esc_attr( $is_enabled_text ); ?>" data-map-disable="<?php echo esc_attr( $is_disabled_text ); ?>"
													role="checkbox"
													aria-label="<?php echo esc_attr( $vv['human_name'] ); ?>"
													tabindex="0"
													aria-checked="mixed">
													<span class="sr-only"><?php echo esc_html( $vv['human_name'] ); ?></span>
												</div>
											</div>
										</div>
										<div class="map-tab-content" style="display: none;">
											<div data-nosnippet="" class="map-tab-pane map-fade">
											<?php echo esc_html( $vv['human_desc'] ); ?>
											</div>
										</div>
									</div>

								<?php
									endforeach;
									endif;
								?>

								<?php
									if( isset( $the_settings['pa'] ) &&
										$the_settings['pa'] == 1 &&
										$this_to_show_consent_mode ):
								?>

								<p><?php echo esc_html( $the_translations[ $current_lang ]['additional_consents'] ) ?>:</p>

								<?php
									foreach( $consent_mode_consents as $kk => $vv ):
								?>

									<div class="map-tab-section map_consent_description_wrapper" data-consent-key="<?php echo esc_attr( $vv['key'] ); ?>">
										<div class="map-tab-header map-standard-header map-nocursor withEffects">
											<a class="map_expandItem map-contextual-expansion map-nav-link map-consent-mode-link map-settings-mobile" data-toggle="map-toggle-tab" role="button" tabindex="0"><?php echo esc_html( $vv['human_name'] ) ?></a>
											<div class="map-switch">
												<input type="checkbox" id="map-consent-<?php echo esc_attr( $vv['key'] ); ?>" class="map-consent-mode-preference-checkbox map-consent-google MapDoNotTouch" data-consent-key="<?php echo esc_attr( $vv['key'] ); ?>">
												<div
													class="map-slider map-nested map-for-map-consent-<?php echo esc_attr( $vv['key'] ); ?>"
													data-map-enable="<?php echo esc_attr( $is_enabled_text ); ?>" data-map-disable="<?php echo esc_attr( $is_disabled_text ); ?>"
													role="checkbox"
													aria-label="<?php echo esc_attr( $vv['human_name'] ); ?>"
													tabindex="0"
													aria-checked="mixed">
													<span class="sr-only"><?php echo esc_html( $vv['human_name'] ); ?></span>
												</div>
											</div>
										</div>
										<div class="map-tab-content" style="display: none;">
											<div data-nosnippet="" class="map-tab-pane map-fade">
											<?php echo esc_html( $vv['human_desc'] ); ?>
											</div>
										</div>
									</div>

								<?php
									endforeach;
									endif;
								?>


						</div>

						<?php

							if( $the_post_is_readonly == false && $the_post_installation_type == 'js_noscript' &&
								$the_post_code  ):
						?>

							<script type="text/plain"
								class="my_agile_privacy_activate _js_noscript_type_mode"
								data-cookie-baseindex="<?php echo esc_attr( $the_remote_id ); ?>"
								data-cookie-name="<?php echo esc_attr( $cleaned_cookie_name ); ?>"
								data-cookie-api-key="<?php echo esc_attr( $the_post_api_key ); ?>"
								><?php echo strip_tags( stripslashes( $the_post_code ) ); ?></script>


							<?php

								if( $the_noscript ):

							?>
								<noscript
									data-cookie-baseindex="<?php echo esc_attr( $the_remote_id ); ?>"
									data-cookie-name="<?php echo esc_attr( $cleaned_cookie_name ); ?>"
									data-cookie-api-key="<?php echo esc_attr( $the_post_api_key ); ?>"
								><?php echo htmlspecialchars_decode( stripslashes( $the_noscript ), ENT_QUOTES ); ?></noscript>

							<?php
								endif;
							?>


						<?php
							elseif( $the_post_is_readonly == false && $the_post_installation_type == 'raw_code' &&
									$the_post_raw_code ) :
						?>

							<textarea
								style="display:none;"
								class="my_agile_privacy_activate _raw_type_mode"
								data-cookie-baseindex="<?php echo esc_attr( $the_remote_id ); ?>"
								data-cookie-name="<?php echo esc_attr( $cleaned_cookie_name ); ?>"
								data-cookie-api-key="<?php echo esc_attr( $the_post_api_key ); ?>"
							><?php echo htmlspecialchars_decode( stripslashes( $the_post_raw_code ), ENT_QUOTES ); ?></textarea>

						<?php
							endif;
						?>

				</div>

				<?php

					endforeach;
				endforeach;

				?>

				<?php
				if( $iab_tcf_context ):
				?>

						</div>
						<div id="map-privacy-iab-tcf-wrapper" class="map-wrappertab map-privacy-iab-tcf-wrapper"></div>
					</div>

				<?php
				endif;
				?>
			</div> <!-- overflow-cookielist-container -->

		</div> <!-- map-container-fluid -->

		<?php
		if( $wl_b == 0 ):

			if( ( isset( $rconfig ) &&
				$rconfig['credits_image_only'] == 1 ) ):
		?>

				<div data-nosnippet class="modal_credits">
					<?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1 ): ?>
						<img src="<?php echo esc_attr( plugin_dir_url( __DIR__ ) ); ?>img/privacy-by-pro.png" alt="Privacy by My Agile Privacy®" width="435" height="40">
					<?php else: ?>
						<img src="<?php echo esc_attr( plugin_dir_url( __DIR__ ) ); ?>img/privacy-by-basic.png" alt="Privacy by My Agile Privacy®" width="435" height="40">
					<?php endif; ?>
				</div>

			<?php
			else:
				$about_url = 'https://www.myagileprivacy.com/en/you-come-from-a-reliable-site/?utm_source=referral&utm_medium=plugin-pro&utm_campaign=customize';

				if( $current_lang && $current_lang == 'it_IT' )
				{
					$about_url = 'https://www.myagileprivacy.com/about/?utm_source=referral&utm_medium=plugin-pro&utm_campaign=customize';
				}
				
			?>
				<div data-nosnippet class="modal_credits">
					<?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1 ): ?>
						<a href="<?php echo esc_attr( $about_url ); ?>" target="_blank" rel="nofollow" tabindex="-1" aria-label="Privacy by My Agile Privacy®"><img src="<?php echo esc_attr( plugin_dir_url( __DIR__ ) ); ?>img/privacy-by-pro.png" alt="Privacy by My Agile Privacy®"  width="111" height="50"></a>
					<?php else: ?>
						<img src="<?php echo esc_attr( plugin_dir_url( __DIR__ ) ); ?>img/privacy-by-basic.png" alt="Privacy by My Agile Privacy®" width="111" height="50">
					<?php endif; ?>
				</div>

			<?php
			endif;
			?>


		<?php
		else:
		?>
			<br>
		<?php
		endif;
		?>


	</div> <!-- map-modal-body -->

	<button type="button" tabindex="0" class="map-modal-close" id="mapModalClose">
			&#x2715;
		  <span class="sr-only"><?php echo esc_html( $the_translations[ $current_lang ]['close'] );  ?></span>
	  </button>
	  
	</div>
  </div>
</div>
<div class="map-modal-backdrop map-fade map-settings-overlay"></div>
<div class="map-modal-backdrop map-fade map-popupbar-overlay"></div>

<?php

if( $custom_css )
{
	echo '<style type="text/css">'.esc_attr( $custom_css ).'</style>';
}

// enable indexing
echo "<!--googleon: all-->";
