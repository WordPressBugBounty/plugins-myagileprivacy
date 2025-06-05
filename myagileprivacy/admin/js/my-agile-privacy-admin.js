(function( $ ) {
	'use strict';


	var map_backend_prefix = '[MAP_BACKEND] ';

	$(function() {

		try {

			console.debug( map_backend_prefix + 'backend init start');

			$( document ).ready(function(){

				console.debug( map_backend_prefix + 'dom ready');

				//for policy and cookies and other inline elements
				var $my_agile_privacy_backend_inline = $( '.my_agile_privacy_backend_inline' );

				//for generic panels
				var $my_agile_privacy_backend = $( '#my_agile_privacy_backend' );

				var reload_at_afterfinish = false;

				if( $my_agile_privacy_backend_inline.length )
				{
					console.debug( map_backend_prefix + '#my_agile_privacy_backend_inline context');

					if( $my_agile_privacy_backend_inline.hasClass( 'map_policy_edit') )
					{
						console.debug( map_backend_prefix + '.map_policy_edit inline subcontext');

						//policy unlock edit
						$('.map-do-edit-this-policy', $my_agile_privacy_backend_inline ).bind( 'click', function(){

							$( '.map-policy-quickview', $my_agile_privacy_backend_inline ).addClass( 'displayNone' );
							$( '.map-wrap-editor', $my_agile_privacy_backend_inline ).removeClass( 'displayNone' );
						});
					}

					if( $my_agile_privacy_backend_inline.hasClass( 'map_cookie_edit') )
					{
						console.debug( map_backend_prefix + '.map_cookie_edit inline subcontext');

						$( '#title' ).hide();
						$( '#title-prompt-text' ).hide();
					}
				}

				if( $my_agile_privacy_backend.length )
				{
					console.debug( map_backend_prefix + '#my_agile_privacy_backend context');

					$my_agile_privacy_backend.bind( 'mapDomLoaded', function(){

						var $loadingMessage = $( '.loadingMessage' );
						var $loadingWrapper = $( '.loadingWrapper' );

						$loadingMessage.addClass( 'displayNone' );
						$loadingWrapper.removeClass( 'displayNone' );
					});

					$( window ).on( 'load', function() {

						$my_agile_privacy_backend.trigger( 'mapDomLoaded' );
					});

					setTimeout(function(){

						$my_agile_privacy_backend.trigger( 'mapDomLoaded' );

					}, 30000 );

					$( ':input[name="license_code_field"]' ).bind( 'change keyup', function(){
						reload_at_afterfinish = true;
					});

					var $input = $( ':input.hideShowInput', $my_agile_privacy_backend );

					if( $input.length )
					{
						console.debug( map_backend_prefix + '.initInputHideShowWrapper context' );

						$input.each(function(){

							var $this = $( this );

							$this.bind( 'change', function(){

								var $this = $( this );
								var hide_show_ref = $this.attr( 'data-hide-show-ref' );

								//meaning is for radio
								var meaning = $this.attr( 'data-meaning' );

								var $ref = $( '.' + hide_show_ref);

								if( $this.is( 'input[type="checkbox"]' ) )
								{
									if( $this.is( '.reverseHideShow' ) )
									{
										if( $this.is( ':checked' ) )
										{
											$ref.addClass( 'displayNone' );
										}
										else
										{
											$ref.removeClass( 'displayNone' );
										}
									}
									else
									{
										if( $this.is( ':checked' ) )
										{
											$ref.removeClass( 'displayNone' );
										}
										else
										{
											$ref.addClass( 'displayNone' );
										}
									}
								}
								else if( $this.is( 'input[type="radio"]' ) )
								{
									if( $this.is( ':checked' ) )
									{
										if( meaning == "1" )
										{
											$ref.removeClass( 'displayNone' );
										}
										else
										{
											$ref.addClass( 'displayNone' );
										}
									}
									else
									{
										if( meaning == "1" )
										{
											$ref.addClass( 'displayNone' );
										}
										else
										{
											$ref.removeClass( 'displayNone' );
										}
									}
								}
								else if( $this.is( 'select' ) )
								{
									var value = $this.val();

									$ref.addClass( 'displayNone' );

									var $target = $( '.' + hide_show_ref + '[data-value~="' + value + '"]' );
									$target.removeClass( 'displayNone' );
								}


							}).trigger( 'change' );
						});
					}


					var init_generic_options = false;

					if( $my_agile_privacy_backend.hasClass( 'MAP_policyWrapperEdit' ) || $my_agile_privacy_backend.hasClass( 'MAP_cookieWrapperEdit' ) )
					{
						init_generic_options = true;

						console.debug( map_backend_prefix + '.MAP_policyWrapperEdit / .MAP_cookieWrapperEdit context');

						//check for active license
						var data = {
							action: 'check_license_status',
							security : map_ajax?.security
						};

						jQuery.post( map_ajax.ajax_url, data, function( response )
						{
							if( !response.success )
							{
								jQuery('#my_agile_privacy_backend.MAP_policyWrapperEdit .forbiddenWarning' ).removeClass( 'd-none' );
								jQuery('#my_agile_privacy_backend.MAP_policyWrapperEdit .checkForbiddenArea' ).addClass( 'forbiddenArea' );


								jQuery('#my_agile_privacy_backend.MAP_cookieWrapperEdit .forbiddenWarning' ).removeClass( 'd-none' );
								jQuery('#my_agile_privacy_backend.MAP_cookieWrapperEdit .checkForbiddenArea' ).addClass( 'forbiddenArea' );

							}
						}, 'json' );

					}

					if( $my_agile_privacy_backend.hasClass( 'cookieWrapperView' ) )
					{
						console.debug( map_backend_prefix + '.cookieWrapperView context');
					}

					if( $my_agile_privacy_backend.hasClass( 'policyWrapperView' ) )
					{
						console.debug( map_backend_prefix + '.policyWrapperView context');
					}

					if( $my_agile_privacy_backend.hasClass( 'genericOptionsWrapper' ) )
					{
						init_generic_options = true;

						console.debug( map_backend_prefix + '.genericOptionsWrapper context');

						$( '.wpColorPicker' ).wpColorPicker();

						var $is_cookie_policy_url_field = $( ':input[name="is_cookie_policy_url_field"]' );

						$is_cookie_policy_url_field.bind( 'change', function( e ){

							e.stopPropagation();

							var $is_cookie_policy_url_yes_detail = $( '.is_cookie_policy_url_yes_detail' );
							var $is_cookie_policy_url_no_detail = $( '.is_cookie_policy_url_no_detail' );

							var $this = $( this );

							if( $this.is( ':checked' ) )
							{
								var val = $this.val();

								if( val == 'true' )
								{
									$is_cookie_policy_url_yes_detail.removeClass( 'displayNone' );
									$is_cookie_policy_url_no_detail.addClass( 'displayNone' );
								}
								else
								{
									$is_cookie_policy_url_yes_detail.addClass( 'displayNone' );
									$is_cookie_policy_url_no_detail.removeClass( 'displayNone' );
								}
							}

						}).trigger( 'change' );


						var $is_personal_data_policy_url_field = $( ':input[name="is_personal_data_policy_url_field"]' );

						$is_personal_data_policy_url_field.bind( 'change', function( e ){

							console.debug( map_backend_prefix + 'change is_personal_data_policy_url_field event');

							e.stopPropagation();

							var $is_personal_data_policy_url_yes_detail = $( '.is_personal_data_policy_url_yes_detail' );
							var $is_personal_data_policy_url_no_detail = $( '.is_personal_data_policy_url_no_detail' );

							var $this = $( this );

							if( $this.is( ':checked' ) )
							{
								var val = $this.val();

								if( val == 'true' )
								{
									$is_personal_data_policy_url_yes_detail.removeClass( 'displayNone' );
									$is_personal_data_policy_url_no_detail.addClass( 'displayNone' );
								}
								else
								{
									$is_personal_data_policy_url_yes_detail.addClass( 'displayNone' );
									$is_personal_data_policy_url_no_detail.removeClass( 'displayNone' );
								}
							}

						}).trigger( 'change' );

						$( '.changeLicenseCode' ).bind( 'click', function( e ){

							e.preventDefault();

							var $license_code_field = $( ':input[name="license_code_field"]' );
							var $license_code_wrapper = $( '.license_code_wrapper' );
							var $hide_code_wrapper = $( '.hide_code_wrapper' );

							$license_code_field.val( '' );
							$license_code_wrapper.removeClass( 'd-none' );
							$hide_code_wrapper.addClass( 'd-none' );

						});

						$( '#map_user_settings_form' ).submit( function(e){

							e.preventDefault();
							var $this = $( this );
							var data = $this.serialize();
							var url = $this.attr( 'action' );

							var $reset_settings = $( '#reset_settings', $this );

							if( ( $reset_settings.length && $reset_settings.is( ':checked' ) ) || $this.hasClass( 'reload_at_afterfinish' ) )
							{
								reload_at_afterfinish = true;
							}

							var $submit_button = $this.find( 'input[type="submit"]' );
							var $fake_submit_buttons = $( this ).find( '.fake-save-button' );

							var $brief_wrapper = $( '.brief_wrapper' );
							var $last_sync_field = $( 'input[name="last_sync_field"]' );
							var $license_user_status_field = $( 'input[name="license_user_status_field"]' );
							var $customer_email = $( 'input[name="customer_email_field"]' );
							var $summary_text = $( ':input[name="summary_text_field"]' );

							var $premium_pills = $( '#my_agile_privacy_backend .nav-pills .nav-link.premium' );
							var $premium_pills_content = $( 'div', $premium_pills );
							var $premium_badge = $( '.badge', $premium_pills );
							var $forbiddenWarning = $( '#my_agile_privacy_backend .forbiddenWarning');
							var $forbiddenArea = $( '#my_agile_privacy_backend .forbiddenArea');

							var $license_code_wrapper = $( '.license_code_wrapper' );
							var $hide_code_wrapper = $( '.hide_code_wrapper' );
							var $lc_owner_description = $( '.lc_owner_description' );
							var $lc_owner_email_wrapper = $( '.lc_owner_email_wrapper' );
							var $lc_owner_website_wrapper = $( '.lc_owner_website_wrapper' );
							var $lc_owner_email = $( '.lc_owner_email' );
							var $lc_owner_website = $( '.lc_owner_website' );

							//console.log( data );

							$submit_button.css( {'opacity':'.6','cursor':'default'} ).prop( 'disabled', true );
							$fake_submit_buttons.css( {'opacity':'.6','cursor':'default'} ).prop( 'disabled', true );

							$( '.map_wait' ).fadeIn();

							$.ajax({
								url : url,
								type : 'POST',
								data : data,
								success : function( data )
								{
									//console.log( data );

									$submit_button.css({ 'opacity':'1','cursor':'pointer'} ).prop( 'disabled', false );
									$fake_submit_buttons.css( {'opacity':'1','cursor':'pointer'} ).prop( 'disabled', false );

									$( '.map_wait' ).fadeOut();

									if( data.license_valid )
									{
										if( data.grace_period )
										{
											$license_user_status_field.removeClass( 'warning_style success_style grace_period_style' ).addClass( 'grace_period_style' );
										}
										else
										{
											$license_user_status_field.removeClass( 'warning_style success_style grace_period_style' ).addClass( 'success_style' );
										}

										$premium_pills.removeClass( 'disabled' );
										$premium_pills_content.removeClass( 'opacity-50' );
										$premium_badge.addClass( 'd-none' );
										$forbiddenWarning.addClass( 'd-none' );
										$forbiddenArea.removeClass( 'forbiddenArea' );

										if( data.lc_hide_local == 1 )
										{
											$license_code_wrapper.addClass( 'd-none' );
											$hide_code_wrapper.removeClass( 'd-none' );
											$lc_owner_description.html( data.lc_owner_description );

											if( data.lc_owner_email )
											{
												$lc_owner_email.html( '<a href="mailto:'+ data.lc_owner_email + '" target="blank">' + data.lc_owner_email +'</a>' );
												$lc_owner_email_wrapper.removeClass( 'd-none' );
											}
											else
											{
												$lc_owner_email_wrapper.addClass( 'd-none' );
											}

											if( data.lc_owner_email )
											{
												$lc_owner_website.html( '<a href="'+ data.lc_owner_email + '" target="blank">' + data.lc_owner_website +'</a>' );

												$lc_owner_website_wrapper.removeClass( 'd-none' );
											}
											else
											{
												$lc_owner_website_wrapper.addClass( 'd-none' );
											}
										}
										else
										{
											$license_code_wrapper.removeClass( 'd-none' );
											$hide_code_wrapper.addClass( 'd-none' );

											$lc_owner_email_wrapper.addClass( 'd-none' );
											$lc_owner_website_wrapper.addClass( 'd-none' );

											$lc_owner_description.html( '' );
											$lc_owner_email.html( '' );
											$lc_owner_website.html( '' );
										}
									}
									else
									{
										$license_user_status_field.removeClass( 'warning_style success_style grace_period_style' ).addClass( 'warning_style' );

										$premium_pills.addClass( 'disabled' );
										$premium_pills_content.addClass( 'opacity-50' );
										$premium_badge.removeClass( 'd-none' );

										$license_code_wrapper.removeClass( 'd-none' );
										$hide_code_wrapper.addClass( 'd-none' );

										$lc_owner_email_wrapper.addClass( 'd-none' );
										$lc_owner_website_wrapper.addClass( 'd-none' );

										$lc_owner_description.html( '' );
										$lc_owner_email.html( '' );
										$lc_owner_website.html( '' );
									}

									$license_user_status_field.val( data.license_user_status );

									if( data.summary_text )
									{
										$brief_wrapper.removeClass( 'displayNone displayBlock' ).addClass( 'displayBlock' );

										$customer_email.val( data.customer_email );
										$summary_text.val( data.summary_text )
									}
									else
									{
										$brief_wrapper.removeClass( 'displayNone displayBlock' ).addClass( 'displayNone' );

										$customer_email.val( '' );
										$summary_text.val( '' );
									}

									$last_sync_field.val( 'right now' );

									$submit_button.css( {'opacity':'1','cursor':'pointer'} ).prop( 'disabled', false );

									if( !!data?.with_missing_fields )
									{
										map_pupup_notify.warning( map_settings_warning_text );
									}
									else
									{
										map_pupup_notify.success( map_settings_success_text );
									}

									//topbar update
									if( !!data?.cookie_shield_raw_status )
									{
										$( '#wp-admin-bar-map_cookieshield' ).removeClass().addClass( data.cookie_shield_raw_status );

										$( '#wp-admin-bar-map_cookieshield .map_cookieshield_text_status' ).html( 'Cookie Shield: ' +data.cookie_shied_value );
									}

									//unset "critical" checkbox
									jQuery( ':input.uncheck_on_send', $my_agile_privacy_backend).each(function(){

										var $this = jQuery( this );

										$this.prop('checked', false );
									});


									if( reload_at_afterfinish )
									{
										setTimeout( function(){
											location.reload();
										}, 200 );
									}

								},
								error:function ()
								{
									$submit_button.css( {'opacity':'1','cursor':'pointer'} ).prop( 'disabled', false );

									map_pupup_notify.error( map_settings_error_message_text );
								}
							});
						});
					}

					if( $my_agile_privacy_backend.hasClass( 'backupRestoreWrapper' ) )
					{
						init_generic_options = true;

						console.debug( map_backend_prefix + '.backupRestoreWrapper context');

						$( '#map_user_settings_form' ).submit( function(e){

							e.preventDefault();
							var $this = $( this );
							var data = $this.serialize();
							var url = $this.attr( 'action' );

							var $submit_button = $this.find( 'input[type="submit"]' );
							var $fake_submit_buttons = $( this ).find( '.fake-save-button' );

							//console.log( data );

							$submit_button.css( {'opacity':'.6','cursor':'default'} ).prop( 'disabled', true );
							$fake_submit_buttons.css( {'opacity':'.6','cursor':'default'} ).prop( 'disabled', true );

							$( '.map_wait' ).fadeIn();

							$.ajax({
								url : url,
								type : 'POST',
								data : data,
								success : function( data )
								{
									//console.log( data );

									$submit_button.css({ 'opacity':'1','cursor':'pointer'} ).prop( 'disabled', false );
									$fake_submit_buttons.css( {'opacity':'1','cursor':'pointer'} ).prop( 'disabled', false );

									$( '.map_wait' ).fadeOut();

									$submit_button.css( {'opacity':'1','cursor':'pointer'} ).prop( 'disabled', false );

									if( !!data?.with_missing_fields )
									{
										map_pupup_notify.warning( map_settings_warning_text );
									}
									else
									{
										map_pupup_notify.success( map_settings_success_text );
									}

									if( reload_at_afterfinish )
									{
										setTimeout( function(){
											location.reload();
										}, 200 );
									}

								},
								error:function ()
								{
									$submit_button.css( {'opacity':'1','cursor':'pointer'} ).prop( 'disabled', false );

									map_pupup_notify.error( map_settings_error_message_text );
								}
							});
						});

					}

					if( $my_agile_privacy_backend.hasClass( 'dashboardOptionsWrapper' ) )
					{
						init_generic_options = true;

						console.debug( map_backend_prefix + '.dashboardOptionsWrapper context');

						reload_at_afterfinish = true;

						$( '#map_user_settings_form' ).submit( function(e){

							e.preventDefault();
							var $this = $( this );
							var data = $this.serialize();
							var url = $this.attr( 'action' );

							var $submit_button = $this.find( 'input[type="submit"]' );
							var $fake_submit_buttons = $( this ).find( '.fake-save-button' );

							//console.log( data );

							$submit_button.css( {'opacity':'.6','cursor':'default'} ).prop( 'disabled', true );
							$fake_submit_buttons.css( {'opacity':'.6','cursor':'default'} ).prop( 'disabled', true );

							$( '.map_wait' ).fadeIn();

							$.ajax({
								url : url,
								type : 'POST',
								data : data,
								success : function( data )
								{
									//console.log( data );

									$submit_button.css({ 'opacity':'1','cursor':'pointer'} ).prop( 'disabled', false );
									$fake_submit_buttons.css( {'opacity':'1','cursor':'pointer'} ).prop( 'disabled', false );

									$( '.map_wait' ).fadeOut();

									$submit_button.css( {'opacity':'1','cursor':'pointer'} ).prop( 'disabled', false );

									if( !!data?.with_missing_fields )
									{
										map_pupup_notify.warning( map_settings_warning_text );
									}
									else
									{
										map_pupup_notify.success( map_settings_success_text );
									}

									if( reload_at_afterfinish )
									{
										setTimeout( function(){
											location.reload();
										}, 200 );
									}

								},
								error:function ()
								{
									$submit_button.css( {'opacity':'1','cursor':'pointer'} ).prop( 'disabled', false );

									map_pupup_notify.error( map_settings_error_message_text );
								}
							});
						});

					}

					if( $my_agile_privacy_backend.hasClass( 'translationsWrapper' ) )
					{
						init_generic_options = true;

						console.debug( map_backend_prefix + '.translationsWrapper context');

						$('.reset_lang_values').each( function(){

							var $button = $(this);
							$button.click( function(e)
							{
								e.preventDefault();

								if( confirm( map_confirm_lang_reset_text ) )
								{
									var $container = $button.closest( '.lang-panel' );
									var all_input = $( ':input[name^="translations"]', $container);
									all_input.val('');

									$( '#map_user_settings_form' ).data( 'map_reloadPage', true );

									reload_at_afterfinish = true;
									$( '#map_user_settings_form' ).submit();
								}
							});
						});


						//bof translation edit in place
						var currentEditField;
						var editModal_element = document.getElementById( 'MAP_editModal' );

						if( editModal_element )
						{
							const modal = new bootstrap.Modal( editModal_element );
							$( '#my_agile_privacy_backend.translationsWrapper .text-preview [data-edit]' ).on( 'click', function() {

								var $this = $( this );

								console.log( 'Element clicked:', $this.attr('data-edit'));

								currentEditField = $this.attr( 'data-edit' );
								var inputType = $this.attr( 'data-input-type' ) || 'input';
								var currentValue = $( `input[name="${currentEditField}"]` ).val();

								let inputElement;
								if( inputType === 'textarea' )
								{
									inputElement = $( '<textarea>' ).addClass( 'form-control' ).val( currentValue );
								}
								else
								{
									inputElement = $( '<input>' ).attr( 'type', 'text' ).addClass( 'form-control' ).val( currentValue );
								}

								$( '.modal-body' ).empty().append( inputElement );
								modal.show();
							});

							$( '#my_agile_privacy_backend.translationsWrapper #saveChanges' ).on( 'click', function()
							{
								const newValue = $( '#my_agile_privacy_backend.translationsWrapper .modal-body .form-control').val();
								$( `input[name="${currentEditField}"]` ).val( newValue );
								$( `[data-edit="${currentEditField}"]` ).text( newValue );

								modal.hide();
							});
						}

						//eof translation edit in place

						$( '#map_user_settings_form' ).submit( function(e){

							e.preventDefault();
							var $this = $( this );
							var data = $this.serialize();
							var url = $this.attr( 'action' );

							var $submit_button = $this.find( 'input[type="submit"]' );
							var $fake_submit_buttons = $( this ).find( '.fake-save-button' );

							//console.log( data );

							$submit_button.css( {'opacity':'.6','cursor':'default'} ).prop( 'disabled', true );
							$fake_submit_buttons.css( {'opacity':'.6','cursor':'default'} ).prop( 'disabled', true );

							$( '.map_wait' ).fadeIn();

							$.ajax({
								url : url,
								type : 'POST',
								data : data,
								success : function( data )
								{
									//console.log( data );

									$submit_button.css({ 'opacity':'1','cursor':'pointer'} ).prop( 'disabled', false );
									$fake_submit_buttons.css( {'opacity':'1','cursor':'pointer'} ).prop( 'disabled', false );

									$( '.map_wait' ).fadeOut();

									$submit_button.css( {'opacity':'1','cursor':'pointer'} ).prop( 'disabled', false );

									if( !!data?.with_missing_fields )
									{
										map_pupup_notify.warning( map_settings_warning_text );
									}
									else
									{
										map_pupup_notify.success( map_settings_success_text );
									}

									if( reload_at_afterfinish )
									{
										setTimeout( function(){
											location.reload();
										}, 200 );
									}

								},
								error:function ()
								{
									$submit_button.css( {'opacity':'1','cursor':'pointer'} ).prop( 'disabled', false );

									map_pupup_notify.error( map_settings_error_message_text );
								}
							});
						});
					}

					if( init_generic_options )
					{
						$( '.wpColorPicker' ).wpColorPicker();

						//preview
						var $preview_cookiebanner = $( '#preview-cookiebanner' );

						if( $preview_cookiebanner.length )
						{
							console.debug( map_backend_prefix + '#preview-cookiebanner context');

							var $all_preview_fields = $( '*[data-preview]' );

							var $all_view_buttons = $( 'button[data-view]', '#device-view-container' );

							$all_preview_fields.each(function(){
								var $this = $( this );

								$this.on( 'change', function( e, autoInit ){
									var preview_attr = $this.attr( 'data-preview' );

									$preview_cookiebanner.removeClass( 'displayNone' );

									var this_value = $this.val();

									switch( preview_attr )
									{
										case 'iab':

											if( $this.is( ':checked') )
											{
												//console.log( 'iab checked');

												$('.added_iab_text').removeClass( 'displayNone' );
												$preview_cookiebanner.addClass( 'map-iab-context' );
											}
											else
											{
												//console.log( 'iab NOT checked');

												$('.added_iab_text').addClass( 'displayNone' );
												$preview_cookiebanner.removeClass( 'map-iab-context' );
											}

											break;


										case 'bg_color':
											$preview_cookiebanner.css( 'background-color', this_value );
											break;

										case 'text_color':
											$( '.text', $preview_cookiebanner ).css( 'background-color', this_value );
											break;

										case 'accept':
											$( '#preview-' + preview_attr, $preview_cookiebanner ).css( 'background-color', this_value );
											$( '#detail-preview-' + preview_attr).css( 'background-color', this_value );
											break;

										case 'refuse':
										case 'customize':
											$( '#preview-' + preview_attr, $preview_cookiebanner ).css( 'background-color', this_value );
											break;

										case 'border_radius':
											$preview_cookiebanner.css( 'border-radius', this_value +'px' );
											$( '.preview-button', $preview_cookiebanner ).css( 'border-radius', this_value + 'px' );
											$( '#detail-preview-accept' ).css( 'border-radius', this_value + 'px' );
											break;

										case 'accept-text':
											$( '#detail-preview-accept .preview-botton-text' ).text( this_value );
											break;

										case 'accept-text-color':
											$( '#detail-preview-accept' ).css( 'color', this_value );
											$( '.preview-button-icon','#detail-preview-accept' ).css( 'background-color', this_value );
											break;

										case 'accept-animation':
											if( !autoInit || autoInit == undefined )
											{
												$( '#detail-preview-accept' ).addClass( 'animate__animated' ).addClass( 'animate__' + this_value );
												setTimeout(function(){
													$( '#detail-preview-accept' ).removeClass( 'animate__' + this_value);
												}, 800 );
											}
											break;

										case 'floating_banner':

											if( this_value == false )
											{
												$preview_cookiebanner.removeClass( 'map_floating_banner' );
											}
											else if( this_value == true )
											{
												$preview_cookiebanner.addClass( 'map_floating_banner' );
											}
											break;

										case 'shadow':

											$preview_cookiebanner.removeClassStartingWith( 'map-shadow-' );

											if( this_value != false )
											{
												$preview_cookiebanner.addClass( this_value );
											}

											break;

										case 'title_background_color':
											jQuery( '#preview-title', $preview_cookiebanner ).css( 'background-color', this_value );
											break;

										case 'title_color':
											jQuery( '#preview-title', $preview_cookiebanner ).css( 'color', this_value );
											jQuery( '.banner-title-logo', '#preview-title' ).css( 'background', this_value );
											break;

										case 'title_text':
											if( this_value == '' )
											{
												var heading_color = jQuery( '[data-preview="title_color"]' ).val();

												jQuery( '#preview-title', $preview_cookiebanner ).html( '<div class="banner-title-logo" style="background:' + heading_color + ';"></div>  My Agile Privacy' );
											}
											else
											{
												jQuery( '#preview-title', $preview_cookiebanner ).text( this_value );
											}
											break;

										case 'mapSize':

											$preview_cookiebanner.removeClassStartingWith( preview_attr );

											var newClass = "";

											switch( this_value )
											{
												case 'sizeWideBranded':
													newClass = "mapSizeWideBranded";
													break;
												case 'sizeWide':
													newClass = "mapSizeWide";
													break;
												case 'sizeBig':
													newClass = "mapSizeBig mapSizeBoxed";
													break;
												case 'sizeBoxed':
													newClass = "mapSizeBoxed";
													break;
											}

											$preview_cookiebanner.addClass( newClass );

											break;

										case 'mapPosition':

											$preview_cookiebanner.removeClassStartingWith( preview_attr );

											var vertical_value = jQuery( '#cookie_banner_vertical_position_field' ).val();
											var horizontal_value = jQuery( '#cookie_banner_horizontal_position_field' ).val();

											var newClass = "mapPosition" + vertical_value + horizontal_value;

											$preview_cookiebanner.addClass( newClass );

											break;

										case 'bannerTitle':

											var this_meaning = $this.attr( 'data-meaning' );

											if( $this.is( ':checked' ) )
											{
												if( this_meaning == "1" )
												{

													jQuery( '#preview-title', $preview_cookiebanner ).show();
												}
												else
												{

													jQuery( '#preview-title', $preview_cookiebanner ).hide();
												}
											}
											else
											{
												if( this_meaning == "1" )
												{

													jQuery( '#preview-title', $preview_cookiebanner ).hide();
												}
												else
												{

													jQuery( '#preview-title', $preview_cookiebanner ).show();
												}
											}

											break;

											case 'button_icon':

												var this_meaning = $this.attr( 'data-meaning' );

												if( $this.is( ':checked' ) )
												{
													if( this_meaning == "1" )
													{

														jQuery( '#accept-detail-preview .preview-button-icon' ).show();
													}
													else
													{

														jQuery( '#accept-detail-preview .preview-button-icon' ).hide();
													}
												}
												else
												{
													if( this_meaning == "1" )
													{

														jQuery( '#accept-detail-preview .preview-button-icon' ).hide();
													}
													else
													{

														jQuery( '#accept-detail-preview .preview-button-icon' ).show();
													}
												}


											break;

										default: //noaction


									}
								}).trigger( 'change', true );
							});

							$all_view_buttons.each(function(){
								var $button = $( this );
								var device = $button.attr( 'data-view' );

								$button.bind( 'click', function( e ){
									e.preventDefault();

									$all_view_buttons.removeClass( 'active' );
									$button.addClass( 'active' );

									switch( device )
									{
										case 'mobile':
											$( '.browser', '#live-preview' ).addClass( 'mobile-view' );

										break;
										case 'desktop':
											$( '.browser', '#live-preview' ).removeClass( 'mobile-view' );
										default:
									}
								});
							});
						}


						var $save_trigger_buttons = $( '.fake-save-button' );
						if( $save_trigger_buttons.length )
						{
							console.debug( map_backend_prefix + '.fake-save-button context');

							$save_trigger_buttons.on( 'click', function()
							{
								$( '#map-save-button' ).trigger( 'click' );
							});
						}

						var $color_preset_select = $( '#color_preset' );

						if( $color_preset_select.length )
						{
							console.debug( map_backend_prefix + '#color_preset context');

							$color_preset_select.on( 'change', function(){
								var preset = $color_preset_select.val();

								var $text_color_input = $( '#text_field' );
								var $banner_background_input = $( '#background_field' );

								var $heading_background_input = $( '#heading_background_color_field' );
								var $heading_color_input = $( '#heading_text_color_field' );

								var $accept_button_text_color_input = $( '#button_accept_link_color_field' );
								var $accept_button_background_input = $( '#button_accept_button_color_field' );

								var $refuse_button_text_color_input = $( '#button_reject_link_color_field' );
								var $refuse_button_background_input = $( '#button_reject_button_color_field' );

								var $customize_button_text_color_input = $( '#button_customize_link_color_field' );
								var $customize_button_background_input = $( '#button_customize_button_color_field' );

								switch( preset )
								{
									case 'light':
										$text_color_input = $( '#text_field' ).val( '#333333' ).trigger( 'change' );
										$banner_background_input = $( '#background_field' ).val( '#ffffff' ).trigger( 'change' );

										$heading_color_input = $( '#heading_text_color_field' ).val( '#ffffff' ).trigger( 'change' );
										$heading_background_input = $( '#heading_background_color_field' ).val( '#0279ff' ).trigger( 'change' );

										$accept_button_text_color_input = $( '#button_accept_link_color_field' ).val( '#ffffff' ).trigger( 'change' );
										$accept_button_background_input = $( '#button_accept_button_color_field' ).val( '#32ade6' ).trigger( 'change' );

										$refuse_button_text_color_input = $( '#button_reject_link_color_field' ).val( '#ffffff' ).trigger( 'change' );
										$refuse_button_background_input = $( '#button_reject_button_color_field' ).val( '#32ade6' ).trigger( 'change' );

										$customize_button_text_color_input = $( '#button_customize_link_color_field' ).val( '#ffffff' ).trigger( 'change' );
										$customize_button_background_input = $( '#button_customize_button_color_field' ).val( '#32ade6' ).trigger( 'change' );
									break;

									case 'dark':
										$text_color_input = $( '#text_field' ).val( '#989899' ).trigger( 'change' );
										$banner_background_input = $( '#background_field' ).val( '#2c2c2e' ).trigger( 'change' );

										$heading_color_input = $( '#heading_text_color_field' ).val( '#ffffff' ).trigger( 'change' );
										$heading_background_input = $( '#heading_background_color_field' ).val( '#1c1c1e' ).trigger( 'change' );

										$accept_button_text_color_input = $( '#button_accept_link_color_field' ).val( '#ffffff' ).trigger( 'change' );
										$accept_button_background_input = $( '#button_accept_button_color_field' ).val( '#5e5ce6' ).trigger( 'change' );

										$refuse_button_text_color_input = $( '#button_reject_link_color_field' ).val( '#ffffff' ).trigger( 'change' );
										$refuse_button_background_input = $( '#button_reject_button_color_field' ).val( '#5e5ce6' ).trigger( 'change' );

										$customize_button_text_color_input = $( '#button_customize_link_color_field' ).val( '#ffffff' ).trigger( 'change' );
										$customize_button_background_input = $( '#button_customize_button_color_field' ).val( '#5e5ce6' ).trigger( 'change' );
									break;

									case 'parchment':
										$text_color_input = $( '#text_field' ).val( '#784B2A' ).trigger( 'change' );
										$banner_background_input = $( '#background_field' ).val( '#FDF5E7' ).trigger( 'change' );

										$heading_color_input = $( '#heading_text_color_field' ).val( '#FDF5E7' ).trigger( 'change' );
										$heading_background_input = $( '#heading_background_color_field' ).val( '#784B2A' ).trigger( 'change' );

										$accept_button_text_color_input = $( '#button_accept_link_color_field' ).val( '#FDF5E7' ).trigger( 'change' );
										$accept_button_background_input = $( '#button_accept_button_color_field' ).val( '#784B2A' ).trigger( 'change' );

										$refuse_button_text_color_input = $( '#button_reject_link_color_field' ).val( '#FDF5E7' ).trigger( 'change' );
										$refuse_button_background_input = $( '#button_reject_button_color_field' ).val( '#784B2A' ).trigger( 'change' );

										$customize_button_text_color_input = $( '#button_customize_link_color_field' ).val( '#FDF5E7' ).trigger( 'change' );
										$customize_button_background_input = $( '#button_customize_button_color_field' ).val( '#784B2A' ).trigger( 'change' );

									break;

									case 'wintersky':
										$text_color_input = $( '#text_field' ).val( '#2A4178' ).trigger( 'change' );
										$banner_background_input = $( '#background_field' ).val( '#E7F2FD' ).trigger( 'change' );

										$heading_color_input = $( '#heading_text_color_field' ).val( '#E7F2FD' ).trigger( 'change' );
										$heading_background_input = $( '#heading_background_color_field' ).val( '#2A4178' ).trigger( 'change' );

										$accept_button_text_color_input = $( '#button_accept_link_color_field' ).val( '#E7F2FD' ).trigger( 'change' );
										$accept_button_background_input = $( '#button_accept_button_color_field' ).val( '#2A4178' ).trigger( 'change' );

										$refuse_button_text_color_input = $( '#button_reject_link_color_field' ).val( '#E7F2FD' ).trigger( 'change' );
										$refuse_button_background_input = $( '#button_reject_button_color_field' ).val( '#2A4178' ).trigger( 'change' );

										$customize_button_text_color_input = $( '#button_customize_link_color_field' ).val( '#E7F2FD' ).trigger( 'change' );
										$customize_button_background_input = $( '#button_customize_button_color_field' ).val( '#2A4178' ).trigger( 'change' );

									break;

									case 'mistyforest':
										$text_color_input = $( '#text_field' ).val( '#2A7858' ).trigger( 'change' );
										$banner_background_input = $( '#background_field' ).val( '#E7FDE9' ).trigger( 'change' );

										$heading_color_input = $( '#heading_text_color_field' ).val( '#E7FDE9' ).trigger( 'change' );
										$heading_background_input = $( '#heading_background_color_field' ).val( '#2A7858' ).trigger( 'change' );

										$accept_button_text_color_input = $( '#button_accept_link_color_field' ).val( '#E7FDE9' ).trigger( 'change' );
										$accept_button_background_input = $( '#button_accept_button_color_field' ).val( '#2A7858' ).trigger( 'change' );

										$refuse_button_text_color_input = $( '#button_reject_link_color_field' ).val( '#E7FDE9' ).trigger( 'change' );
										$refuse_button_background_input = $( '#button_reject_button_color_field' ).val( '#2A7858' ).trigger( 'change' );

										$customize_button_text_color_input = $( '#button_customize_link_color_field' ).val( '#E7FDE9' ).trigger( 'change' );
										$customize_button_background_input = $( '#button_customize_button_color_field' ).val( '#2A7858' ).trigger( 'change' );

									break;

									case 'greentea':
										$text_color_input = $( '#text_field' ).val( '#69782A' ).trigger( 'change' );
										$banner_background_input = $( '#background_field' ).val( '#FAFDE7' ).trigger( 'change' );

										$heading_color_input = $( '#heading_text_color_field' ).val( '#FAFDE7' ).trigger( 'change' );
										$heading_background_input = $( '#heading_background_color_field' ).val( '#69782A' ).trigger( 'change' );

										$accept_button_text_color_input = $( '#button_accept_link_color_field' ).val( '#FAFDE7' ).trigger( 'change' );
										$accept_button_background_input = $( '#button_accept_button_color_field' ).val( '#69782A' ).trigger( 'change' );

										$refuse_button_text_color_input = $( '#button_reject_link_color_field' ).val( '#FAFDE7' ).trigger( 'change' );
										$refuse_button_background_input = $( '#button_reject_button_color_field' ).val( '#69782A' ).trigger( 'change' );

										$customize_button_text_color_input = $( '#button_customize_link_color_field' ).val( '#FAFDE7' ).trigger( 'change' );
										$customize_button_background_input = $( '#button_customize_button_color_field' ).val( '#69782A' ).trigger( 'change' );

									break;

									case 'lavender':
										$text_color_input = $( '#text_field' ).val( '#5D2A78' ).trigger( 'change' );
										$banner_background_input = $( '#background_field' ).val( '#FDE7FB' ).trigger( 'change' );

										$heading_color_input = $( '#heading_text_color_field' ).val( '#FDE7FB' ).trigger( 'change' );
										$heading_background_input = $( '#heading_background_color_field' ).val( '#5D2A78' ).trigger( 'change' );

										$accept_button_text_color_input = $( '#button_accept_link_color_field' ).val( '#FDE7FB' ).trigger( 'change' );
										$accept_button_background_input = $( '#button_accept_button_color_field' ).val( '#5D2A78' ).trigger( 'change' );

										$refuse_button_text_color_input = $( '#button_reject_link_color_field' ).val( '#FDE7FB' ).trigger( 'change' );
										$refuse_button_background_input = $( '#button_reject_button_color_field' ).val( '#5D2A78' ).trigger( 'change' );

										$customize_button_text_color_input = $( '#button_customize_link_color_field' ).val( '#FDE7FB' ).trigger( 'change' );
										$customize_button_background_input = $( '#button_customize_button_color_field' ).val( '#5D2A78' ).trigger( 'change' );

									break;

									default:
										//
									break;
								}
							});
						}

						$( "select[name^='microsoft_consent_']" ).each( function(){

							var $this = $( this );
							var $row = $this.closest( '.row' );
							var $alertDiv = $this.siblings( '.suggested-value-alert' );

							if( $this.val() === 'granted' )
							{
								$this.addClass( 'is-invalid' );
								$row.addClass( 'alert-warning' );
								$alertDiv.removeClass( 'd-none' );
							}
						});

						$( "select[name^='microsoft_consent_']" ).on( 'change', function() {

							var $this = $( this );
							var $row = $this.closest( '.row' );
							var $alertDiv = $this.siblings( '.suggested-value-alert' );

							if( $this.val() === 'granted')
							{
								$this.addClass( 'is-invalid' );
								$row.addClass( 'alert-warning' );
								$alertDiv.removeClass( 'd-none' );
							}
							else if( $this.val() === 'denied' )
							{
								$this.removeClass( 'is-invalid' );
								$row.removeClass( 'alert-warning' );
								$alertDiv.addClass( 'd-none' );
							}
						});

						$( ".cmode_v2_implementation_type_options[data-value='native'] select[name^='cmode_v2_gtag_']" ).each( function(){

							var $this = $( this );
							var $row = $this.closest( '.row' );
							var $alertDiv = $this.siblings( '.suggested-value-alert' );

							if( $this.val() === 'granted' )
							{
								$this.addClass( 'is-invalid' );
								$row.addClass( 'alert-warning' );
								$alertDiv.removeClass( 'd-none' );
							}
						});

						$( ".cmode_v2_implementation_type_options[data-value='native']" ).on( 'change', 'select[name^="cmode_v2_gtag_"]', function() {

							var $this = $( this );
							var $row = $this.closest( '.row' );
							var $alertDiv = $this.siblings( '.suggested-value-alert' );

							if( $this.val() === 'granted')
							{
								$this.addClass( 'is-invalid' );
								$row.addClass( 'alert-warning' );
								$alertDiv.removeClass( 'd-none' );
							}
							else if( $this.val() === 'denied' )
							{
								$this.removeClass( 'is-invalid' );
								$row.removeClass( 'alert-warning' );
								$alertDiv.addClass( 'd-none' );
							}
						});

						//check if buttons background are equals
						$( '#cookie_banner_options_container input[type="color"][id$="_button_color_field"]' ).on( 'change', checkButtonsEqualColors ).trigger( 'change' );

						//normalize buttons background color
						$( '#cookie_banner_options_container .standardize_colors_button' ).on( 'click', function( e ) {
							e.preventDefault();
							standardizeColors();
						});

						// bof Code Higlight via Prisma.js in admin fields

						var $textarea_code_editor = $( 'textarea.code-editor' );

						if( $textarea_code_editor.length )
						{
							console.debug( map_backend_prefix + '#color_preset context');

							$textarea_code_editor.each(function (){
								var $this = $(this);

								let codeContainer = $this.next();
								let codeRender = codeContainer.find('code');

								// on input we update di pre -> code values
								$this.on('input', function () {
									let text = $this.val();

									if (text[text.length - 1] == "\n") { // If the last character is a newline character
										text += " "; // Add a placeholder space character to the final line
									}

									let text_replaced = text.replace(new RegExp("&", "g"), "&amp;").replace(new RegExp("<", "g"), "&lt;");

									codeRender.html( text_replaced );

									Prism.highlightElement( codeRender[0] );

								});


								$this.on( 'input scroll', function () {
									// Get and set x and y
									codeContainer.scrollTop($(this).scrollTop());
									codeContainer.scrollLeft($(this).scrollLeft());
								});

								$this.on( 'keydown', function (event) {

									if (event.key == "Tab") {
										/* Tab key pressed */
										event.preventDefault(); // stop normal
										let code = $this.val();
										let before_tab = code.slice(0, this.selectionStart); // text before tab
										let after_tab = code.slice(this.selectionEnd, this.value.length); // text after tab
										let cursor_pos = this.selectionEnd + 1; // where cursor moves after tab - moving forward by 1 char to after tab
										$this.val(before_tab + "\t" + after_tab); // add tab char
										// move cursor
										this.selectionStart = cursor_pos;
										this.selectionEnd = cursor_pos;

										$this.trigger( 'input' );
									}
								});
							});
						}

						// eof Code Higlight via Prisma.js in admin fields

						//dynamic map-btn-add / map-btn-remove
						createDynamicFields( '#my_agile_privacy_backend' );

						//bof hash url navigation // tabbed content
						var url = document.URL;
						var hash = url.substring(url.indexOf('#'));

						$( ".nav-pills" ).find( "li button" ).each( function( key, val ){
							var $val = $( val );
							var bs_target = $val.attr( 'data-bs-target');

							if( hash == bs_target )
							{
								$val.click();
							}

							$val.click( function( ky, vl ){
								location.hash = $(this).attr('data-bs-target');
							});
						});

						//eof hash url navigation // tabbed content
					}

					var tooltipTriggerList = [].slice.call( document.querySelectorAll( '[data-bs-toggle="tooltip"]' ) );
					var tooltipList = tooltipTriggerList.map( function( tooltipTriggerEl ) {
						return new bootstrap.Tooltip(tooltipTriggerEl)
					});
				}
			});

			console.debug( map_backend_prefix + 'backend init end.');

		}
		catch( error )
		{
			console.error( error );
		}

	});


	$.fn.removeClassStartingWith = function ( filter ) {

		try{

			$( this ).removeClass( function( index, className ) {
				return ( className.match( new RegExp("\\S*" + filter + "\\S*", 'g' ) ) || []).join( ' ' );
			});

			return this;

		}
		catch( error )
		{
			console.error( error );
		}
	};

	//f for creating dynamic fields
	//used for js_dependencies_field
	function createDynamicFields( $extrawrapper )
	{
		try {

			//add button
			$( $extrawrapper ).on( 'click', '.map-btn-add', function( e ){

				//console.log( 'click on mapx-btn-add' );

				e.preventDefault();

				var this_button = $( this );
				var box_container = this_button.closest( '.dynamic_fields_container' );
				var current_dynamic_entry = this_button.parents( '.map-dynamic-entry:first' );
				var cloned_item = current_dynamic_entry.clone();
				cloned_item.find( 'input' ).val( '' );
				cloned_item.find( 'select' ).css( 'width', '300px' );
				box_container.append( cloned_item );

				box_container.find( '.map-dynamic-entry:not(:last) .map-btn-add' )
									.removeClass( 'map-btn-add' ).addClass( 'map-btn-remove' )
									.removeClass( 'btn-success' ).addClass( 'btn-danger' )
									.html( '-' );

			});

			//remove button
			$( $extrawrapper ).on( 'click', '.map-btn-remove', function( e ){
				e.preventDefault();
				$( this ).parents( '.map-dynamic-entry:first' ).remove();
			});

		}
		catch( error )
		{
			console.error( error );
		}
	};


	//check if buttons background are equals
	function checkButtonsEqualColors()
	{
		try{

			const colors = $( '#cookie_banner_options_container input[type="color"][id$="_button_color_field"]' )
			.map(function() {
				return $( this ).val();
			}).get();

			const allEqual = colors.every( color => color === colors[0] );

			if( allEqual )
			{
				$( '#map_buttons_background_alert' ).addClass( 'd-none' );
			}
			else
			{
				$( '#map_buttons_background_alert' ).removeClass( 'd-none' );
			}

		}
		catch( error )
		{
			console.error( error );
		}
	}

	//normalize buttons background color
	function standardizeColors()
	{
		try{

			var sourceColor = $( '#button_accept_button_color_field' ).val();

			$( '#button_reject_button_color_field, #button_customize_button_color_field' )
			.val( sourceColor )
			.trigger( 'change' );

			console.debug( map_backend_prefix + 'Colors standardized to: ' + sourceColor);

		}
		catch( error )
		{
			console.error( error );
		}
	}

	var map_pupup_notify =
	{
		error : function( message )
		{
			var error_element = $( '<div class="map_notify_popup" style="background:#ec2b77; border:solid 1px #ec2b77;">'+message+'</div>' );
			this.showNotify( error_element );
		},
		success : function( message )
		{
			var success_element = $( '<div class="map_notify_popup" style="background:#049ecc; border:solid 1px #049ecc;">'+message+'</div>' );
			this.showNotify( success_element );
		},
		warning : function( message )
		{
			var success_element = $( '<div class="map_notify_popup" style="background:#fff3cd; border:solid 1px #ffecb5; color: #111111;">'+message+'</div>' );
			this.showNotify( success_element );
		},
		showNotify : function( elm )
		{
			try{

				$( 'body' ).append( elm );
				elm.stop( true, true ).animate( {'opacity':1,'top':'40px'}, 1000 );

				setTimeout(function(){
					elm.animate( {'opacity':0,'top':'60px'}, 1000, function(){
						elm.remove();
					});
				}, 2500 );

			}
			catch( error )
			{
				console.error( error );
			}
		}
	}

})( jQuery );