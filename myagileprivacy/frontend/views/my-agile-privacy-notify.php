<?php

$defaults = MyAgilePrivacy::get_default_settings();
$settings = wp_parse_args( MyAgilePrivacy::get_settings(), $defaults );
$rconfig = MyAgilePrivacy::get_rconfig();
$current_lang = get_locale() ? get_locale() : $settings['default_locale'];

// disable indexing
echo "<!--googleoff: all-->";

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
	defined( 'MAP_IAB_TCF' ) && MAP_IAB_TCF &&
	$rconfig &&
	isset( $rconfig['allow_iab'] ) &&
	$rconfig['allow_iab'] == 1 &&
	$the_options['enable_iab_tcf']
)
{
	$iab_tcf_context = true;
	$iab_extra_class = 'map-iab-context';
}

$enable_cmode_v2 = false;

if( isset( $the_options ) && isset( $the_options['enable_cmode_v2'] ) && $the_options['enable_cmode_v2'] )
{
	$enable_cmode_v2 = true;
}

$always_enable_text = __( 'Always Enabled', 'myagileprivacy' );
$is_enabled_text = __( 'Enabled', 'myagileprivacy' );
$is_disabled_text = __( 'Disabled', 'myagileprivacy' );
$blocked_content_text = __( 'Warning: some page functionalities could not work due to your privacy choices', 'myagileprivacy' );

$color_style = ( $settings['text'] != "" ) ? 'color:'.$settings['text'] : '';
$background_style = $settings['background'] != "" ? 'background-color:'.$settings['background'] : '';
$border_radius_style = 'border-radius:'.$settings['elements_border_radius'].'px';

$text_size = 'font-size:'.$settings['text_size'].'px!important';
$text_lineheight = 'line-height:'.$settings['text_lineheight'].'px!important';

$composed_style = implode( ';', array( $color_style, $background_style, $border_radius_style, $text_size, $text_lineheight  ) );

$notification_bar_composed_style = implode( ';', array( $color_style, $background_style, $border_radius_style ) );

$position_class = ( $settings['is_bottom'] == false ) ? 'isTop' : 'isBottom';
$with_css_effects_class = ( $settings['with_css_effects'] == true ) ? 'withEffects' : '';

$map_shadow_class = ( $settings['cookie_banner_shadow'] == false ) ? '' : $settings['cookie_banner_shadow'];

$map_heading_class = ( $settings['title_is_on'] == true ) ? '' : 'map_displayNone';
$map_heading_style = 'background-color:'.$settings['heading_background_color'].'; color: '.$settings['heading_text_color'].';';

$map_close_button_style = ( $settings['title_is_on'] == true ) ? 'color: '.$settings['heading_text_color'].';' : 'color: '.$settings['text'].';';

//graphic settings
$cookie_banner_vertical_position = $settings['cookie_banner_vertical_position'];
$cookie_banner_horizontal_position = $settings['cookie_banner_horizontal_position'];

$new_position = null;

if( $cookie_banner_vertical_position )
{
	switch( $settings['cookie_banner_size'] )
	{
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
	       $new_size = "mapSizeWide";
	}

	$new_position = 'mapPosition'.$cookie_banner_vertical_position.$cookie_banner_horizontal_position;
}

//retro compatibility
if( !$new_position )
{
	if( $settings['is_bottom'] )
	{
		$new_position = 'mapPositionBottomCenter';
	}
	else
	{
		$new_position = 'mapPositionTopCenter';
	}

	$new_size = 'mapSizeWide';
}

$floating_banner = ( $settings['floating_banner'] == false ) ? '' : 'map_floating_banner';
$animation_class = 'map_animation_'.$settings['cookie_banner_animation'];

//composed_class with new css and options
$composed_class = implode( ' ', array( $new_position, $new_size, $floating_banner, $with_css_effects_class, $animation_class, $map_shadow_class  ) );

//esc_attr
$notify_message_v2 = nl2br( $the_options['notify_message_v2'] );

if( $reload_translation )
{
	$notify_message_v2 = addslashes ( __( 'This website uses technical and profiling cookies. Clicking on "Accept" authorises all profiling cookies. Clicking on "Refuse" or the X will refuse all profiling cookies. By clicking on "Customise" you can select which profiling cookies to activate.[myagileprivacy_extra_info]', 'myagileprivacy' ) );
}

$banner_logo_color = $settings['heading_text_color'];

//apply shortcodes
$notify_message_v2 = do_shortcode( '<div class="map-area-container"><div data-nosnippet class="map_notification-message '.esc_attr( $iab_extra_class ).'">'.stripslashes( $notify_message_v2 ).'</div><div class="map_notification_container '.$iab_extra_class.'">[myagileprivacy_cookie_accept][myagileprivacy_cookie_reject][myagileprivacy_cookie_customize]</div></div>' );

$bar_heading_text = trim( stripslashes( $the_options['bar_heading_text'] ) );

$notify_title = '<div class="map_notify_title '.esc_attr( $map_heading_class ).'" style="'.esc_attr( $map_heading_style ).';">'.
						( $bar_heading_text != '' ? esc_html( $bar_heading_text ) : '<div class="banner-title-logo" style="background:'.$banner_logo_color.';"></div> My Agile Privacy' )
					. '</div>';

$notify_close_button = '<div class="map-closebutton-right"><a role="button" class="map-button map-reject-button" data-map_action="reject" style="'.esc_attr( $map_close_button_style ).'">&#x2715;</a></div>';

$notify_html = '<div id="my-agile-privacy-notification-area" class="'.esc_attr( $composed_class ).' mapButtonsAside" data-nosnippet="true" style="'.esc_attr( $composed_style ).'" data-animation="'.$settings['cookie_banner_animation'].'">'.
				$notify_title.
				$notify_close_button.
				'<div id="my-agile-privacy-notification-content">'.
				$notify_message_v2.
				'</div>'.
			 '</div>';

$cookies_categories_data = $this->get_cookie_categories_description( 'publish' );

$disable_logo = $settings['disable_logo'];

$cookie_policy_link = ( isset( $settings ) && isset( $settings['cookie_policy_link'] ) ) ? $settings['cookie_policy_link'] : null;
$is_cookie_policy_url = ( isset( $settings ) && isset( $settings['is_cookie_policy_url'] ) ) ? $settings['is_cookie_policy_url'] : null;
$cookie_policy_url = ( isset( $settings ) && isset( $settings['cookie_policy_url'] ) ) ? $settings['cookie_policy_url'] : null;
$cookie_policy_page = ( isset( $settings ) && isset( $settings['cookie_policy_page'] ) ) ? $settings['cookie_policy_page'] : null;

$personal_data_policy_link = ( isset( $settings ) && isset( $settings['personal_data_policy_link'] ) ) ? $settings['personal_data_policy_link'] : null;
$is_personal_data_policy_url = ( isset( $settings ) && isset( $settings['is_personal_data_policy_url'] ) ) ? $settings['is_personal_data_policy_url'] : null;
$personal_data_policy_url = ( isset( $settings ) && isset( $settings['personal_data_policy_url'] ) ) ? $settings['personal_data_policy_url'] : null;
$personal_data_policy_page = ( isset( $settings ) && isset( $settings['personal_data_policy_page'] ) ) ? $settings['personal_data_policy_page'] : null;


$showagain_tab = $settings['showagain_tab'];
$custom_css = $settings['custom_css'];
$notify_position_horizontal = $settings['notify_position_horizontal'];
$blocked_content_notify = $settings['blocked_content_notify'];
$wl = intval( $settings['wl'] );

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
	$cookie_policy_html_inside_popup = '<a target="blank" href="'.esc_url( $the_cookie_policy_url ).'" tabindex="-1">'.__( 'View the Cookie Policy', 'myagileprivacy' ).'</a>';
}

if( $the_personal_data_policy_url )
{
	$personal_data_policy_html_inside_popup = '<a target="blank" href="'.esc_url( $the_personal_data_policy_url ).'" tabindex="-1">'.__( 'View the Personal Data Policy', 'myagileprivacy' ).'</a>';
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

$show_again = $the_options["showagain_text"] ;

if( $reload_translation )
{
	$show_again = __( 'Manage consent', 'myagileprivacy' );
}

$showagain_div_classes = "map_displayNone";

if( $disable_logo )
{
	$showagain_div_classes .= " nologo";
}

if( $showagain_tab == 0 )
{
	$showagain_div_classes .= " disactive";
}

if( $settings['with_css_effects'] == true )
{
	$showagain_div_classes .= " withEffects";
}

switch ($notify_position_horizontal)
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
	$position_class_alt = ( $settings['is_bottom'] == false ) ? 'isBottom' : 'isTop';
}
else
{
	// if banner is top, notification in bottom
	// if banner is center or bottom, notification is top
	$position_class_alt = ( $cookie_banner_vertical_position == 'Top' ) ? 'isBottom': 'isTop';
}

$notify_logo_color = ( $settings['heading_background_color'] == '#ffffff' ) ? '#F93F00' : $settings['heading_background_color'];

$the_empty_href = "";

if( isset( $settings['scanner_compatibility_mode'] ) && $settings['scanner_compatibility_mode'] )
{
	$the_empty_href = "#";
}

$notify_html .= '<div data-nosnippet class="'.esc_attr( $showagain_div_classes ).'" id="' . esc_attr( $the_options["showagain_div_id"] ) . '" style="'.esc_attr( $border_radius_style ).'"><div class="map_logo_container" style="background-color:'.$notify_logo_color.';"></div><a role="button" class="showConsent" href="'.$the_empty_href.'" data-nosnippet>' . wp_kses( $show_again, MyAgilePrivacy::allowed_html_tags() ) . '</a>'.$spacing_text.$cookie_policy_html_inside_banner.'</div>';


if( isset( $settings['scanner_compatibility_mode'] ) && $settings['scanner_compatibility_mode'] )
{
	echo $notify_html;
}
else
{
	echo wp_kses( $notify_html, MyAgilePrivacy::allowed_html_tags() );
}


$autoclose = ( $settings['blocked_content_notify_auto_shutdown'] == false ) ? '' : 'autoShutDown';
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
	aria-labelledby="mapSettingsPopup"
	aria-hidden="true"
	>
  <div class="map-modal-dialog" role="document">
	<div class="map-modal-content map-bar-popup <?php echo esc_attr( $with_css_effects_class );?>">
	  <button type="button" class="map-modal-close" id="mapModalClose" tabindex='-1'>
	  		&#x2715;
		  <span class="map-sr-only"><?php echo esc_html__( 'Close', 'myagileprivacy' );  ?></span>
	  </button>
	  <div class="map-modal-body">

	  	<div class="map-container-fluid map-tab-container">

			<div class="map-privacy-overview">
	            <h4 data-nosnippet><?php echo esc_html__( 'Privacy Setting', 'myagileprivacy' ); ?></h4>
	        </div>

			<p data-nosnippet>
				<?php echo esc_html__( 'This website uses cookies to improve your experience while you navigate through the website.', 'myagileprivacy' ); ?><br>
				<span class="map-modal-cookie-policy-link"><?php echo wp_kses( $cookie_policy_html_inside_popup, MyAgilePrivacy::allowed_html_tags() ) ; ?> <?php echo wp_kses( $personal_data_policy_html_inside_popup, MyAgilePrivacy::allowed_html_tags() ) ; ?></span>
			</p>
			<div class="<?php echo esc_attr( $layer2_overflow_wrapper_class );?>">
				<?php
				if( $iab_tcf_context ):
				?>

					<div class="map-consent-extrawrapper">

						<ul class="map-wrappertab-navigation">

							<li><a href="#map-privacy-cookie-thirdypart-wrapper" class="active-wrappertab-nav" tabindex='-1'><?php echo esc_html__( 'Cookies and third-party software', 'myagileprivacy' );  ?></a></li>
							<li><a href="#map-privacy-iab-tcf-wrapper" tabindex='-1'><?php echo esc_html__( 'Advertising preferences', 'myagileprivacy' );  ?></a></li>

						</ul>

						<div id="map-privacy-cookie-thirdypart-wrapper" class="map-wrappertab map-wrappertab-active map-privacy-cookie-thirdypart-wrapper">

				<?php
				endif;
				?>

				<?php

				$consent_mode_options_shown = false;
				$consent_mode_valid_post_api_key = array(
					'google_tag_manager',
					'google_analytics',
					'my_agile_pixel_ga'
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

						$cleaned_cookie_name = str_replace( '"', '', $value['post_data']->post_title );
						$the_post_is_readonly = isset( $value['post_meta']['_map_is_readonly'][0] ) ? $value['post_meta']['_map_is_readonly'][0] : false;

						$the_post_installation_type = isset( $value['post_meta']['_map_installation_type'][0] ) ? $value['post_meta']['_map_installation_type'][0] : 'js_noscript';
						$the_post_code = isset( $value['post_meta']['_map_code'][0] ) ? $value['post_meta']['_map_code'][0] : null;
						$the_noscript = isset( $value['post_meta']['_map_noscript'][0] ) ? $value['post_meta']['_map_noscript'][0] : null;
						$the_post_raw_code = isset( $value['post_meta']['_map_raw_code'][0] ) ? $value['post_meta']['_map_raw_code'][0] : null;
						$the_post_api_key = isset( $value['post_meta']['_map_api_key'][0] ) ? $value['post_meta']['_map_api_key'][0] : null;
						$page_reload_on_user_consent = isset( $value['post_meta']['_map_page_reload_on_user_consent'][0] ) ? $value['post_meta']['_map_page_reload_on_user_consent'][0] : null;

						$this_to_show_consent_mode = false;
						$this_extra_header_class = "";
						$this_extra_content_class = "";
						$this_content_display = 'display:none;';

						if( !$consent_mode_options_shown &&
							$enable_cmode_v2 &&
							$the_post_api_key && in_array( $the_post_api_key, $consent_mode_valid_post_api_key ) )
						{
							$this_to_show_consent_mode = true;
							$consent_mode_options_shown = true;

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
						class="map-tab-section map_cookie_description_wrapper <?php echo esc_attr( $map_cookie_description_wrapper_added_class );?>"
						data-cookie-baseindex="<?php echo esc_attr( $the_remote_id ); ?>"
						data-cookie-name="<?php echo esc_attr( $cleaned_cookie_name ); ?>"
						data-cookie-api-key="<?php echo esc_attr( $the_post_api_key ); ?>">
						<div class="map-tab-header map-standard-header <?php echo esc_attr( $with_css_effects_class.$this_extra_header_class );?>">
							<a role="button" class="map_expandItem map-nav-link map-settings-mobile" data-toggle="map-toggle-tab" tabindex='-1'>
							<?php echo esc_html( $value['post_data']->post_title ); ?>
							</a>
							<?php if( $k == 'necessary' ):
							?>
								<span class="map-necessary-caption"><?php echo esc_html( $always_enable_text ) ?></span>
							<?php
							else:

					$map_switch = '<div class="map-switch">
										<input
											data-cookie-baseindex="'.esc_attr( $the_remote_id ).'"
											type="checkbox"
											id="map-checkbox-' . esc_attr( $the_remote_id ) . '"
											class="map-user-preference-checkbox MapDoNotTouch"
										/>
										<label
											for="map-checkbox-' . esc_attr( $the_remote_id ) . '"
											class="map-slider"
											data-map-enable="'.esc_attr( $is_enabled_text ).'"
											data-map-disable="'.esc_attr( $is_disabled_text ).'">
											<span class="map-sr-only">' .
												esc_html( $value['post_data']->post_title ) .
											'</span>
										</label>
									</div>';

					echo wp_kses( $map_switch , MyAgilePrivacy::allowed_html_tags() ) ;
							?>

							<?php
							endif;
							?>
						</div>
						<div class="map-tab-content <?php echo esc_attr( $this_extra_content_class );?>"
								style="<?php echo esc_attr( $this_content_display );?>"
						>
							<div data-nosnippet class="map-tab-pane map-fade">
								<?php
								echo wp_kses_post( $value['post_content'] );
								?>
							</div>

								<?php
									if( $the_options['pa'] == 1 && $this_to_show_consent_mode ):
								?>

								<p><?php echo __( 'Additional consents', 'myagileprivacy' ); ?>:</p>

								<?php
										foreach( $consent_mode_consents as $kk => $vv ):
								?>

									<div class="map-tab-section map_consent_description_wrapper" data-consent-key="<?php echo esc_attr( $vv['key'] ); ?>">
										<div class="map-tab-header map-standard-header map-nocursor withEffects">
											<a role="button" class="map_expandItem map-contextual-expansion map-nav-link map-settings-mobile" data-toggle="map-toggle-tab" tabindex='-1'><?php echo esc_html($vv['human_name']) ?></a>
											<div class="map-switch">
												<input type="checkbox" id="map-consent-<?php echo esc_attr( $vv['key'] ); ?>" class="map-consent-mode-preference-checkbox MapDoNotTouch" data-consent-key="<?php echo esc_attr( $vv['key'] ); ?>">
												<label for="map-consent-<?php echo esc_attr( $vv['key'] ); ?>" class="map-slider map-nested" data-map-enable="<?php echo esc_attr( $is_enabled_text );?>" data-map-disable="<?php echo esc_attr( $is_disabled_text );?>">
												<span class="map-sr-only"><?php echo esc_html( $vv['human_name'] ); ?></span></label>
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
								><?php echo strip_tags( stripslashes( $the_post_code ) );?></script>


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
		if( $wl == 0 ):

			if( ( isset( $rconfig ) &&
				$rconfig['credits_image_only'] == 1 ) ):
		?>

				<div data-nosnippet class="modal_credits">
					<?php if( $the_options['pa'] == 1 ): ?>
						<img src="<?php echo plugin_dir_url( __DIR__ );?>img/privacy-by-pro.png" alt="Privacy by My Agile Privacy" width="111" height="50">
					<?php else: ?>
						<img src="<?php echo plugin_dir_url( __DIR__ );?>img/privacy-by-basic.png" alt="Privacy by My Agile Privacy" width="111" height="50">
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
					<?php if( $the_options['pa'] == 1 ): ?>
						<a href="<?php echo esc_attr( $about_url ); ?>" target="_blank" rel="nofollow" tabindex='-1'><img src="<?php echo plugin_dir_url( __DIR__ );?>img/privacy-by-pro.png" alt="Privacy by My Agile Privacy"  width="111" height="50"></a>
					<?php else: ?>
						<img src="<?php echo plugin_dir_url( __DIR__ );?>img/privacy-by-basic.png" alt="Privacy by My Agile Privacy" width="111" height="50">
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
