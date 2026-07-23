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

					if( $my_agile_privacy_backend_inline.hasClass( 'map_text_edit') )
					{
						console.debug( map_backend_prefix + '.map_text_edit inline subcontext');

						//policy unlock edit
						$('.map-do-edit-this-text', $my_agile_privacy_backend_inline ).bind( 'click', function(){

							$( '.map-text-quickview', $my_agile_privacy_backend_inline ).addClass( 'displayNone' );
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
										var $alt_ref = $( '.' + hide_show_ref +'_reverse' );

										if( $this.is( ':checked' ) )
										{
											$ref.addClass( 'displayNone' );

											if( $alt_ref.length ) $alt_ref.removeClass( 'displayNone' );
										}
										else
										{
											$ref.removeClass( 'displayNone' );

											if( $alt_ref.length ) $alt_ref.addClass( 'displayNone' );
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

							$license_code_field.prop( 'disabled', false );
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
											$lc_owner_description.text( data.lc_owner_description );

											if( data.lc_owner_email )
											{
												var $owner_email_link = $( '<a>' ).attr( 'target', 'blank' ).text( data.lc_owner_email );
												$owner_email_link.attr( 'href', 'mailto:' + data.lc_owner_email );

												$lc_owner_email.empty().append( $owner_email_link );
												$lc_owner_email_wrapper.removeClass( 'd-none' );
											}
											else
											{
												$lc_owner_email_wrapper.addClass( 'd-none' );
											}

											if( data.lc_owner_website && /^https?:\/\//i.test( data.lc_owner_website ) )
											{
												var $owner_website_link = $( '<a>' ).attr( 'target', 'blank' ).text( data.lc_owner_website );
												$owner_website_link.attr( 'href', data.lc_owner_website );

												$lc_owner_website.empty().append( $owner_website_link );
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

							//destructive action guard: "Clean All Cookie" drops every configured cookie
							if( $this.find( 'input[name="reset_cookie_settings"]' ).val() == '1' )
							{
								if( typeof map_confirm_clean_cookies_text !== 'undefined' && !confirm( map_confirm_clean_cookies_text ) )
								{
									return false;
								}
							}

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

						var isDirty = false;

 						// Mark the page as "dirty" when the user interacts with fields
						$my_agile_privacy_backend
							.on( 'input.unsaved change.unsaved keyup.unsaved keydown.unsaved', 'input, textarea, select', function() {
								isDirty = true;
							});

						$( window ).on( 'beforeunload.unsaved', function( e ) {
							if (!isDirty) return;
							e.preventDefault();
							e.returnValue = map_leaveout_text;
							return map_leaveout_text;
						});

						$( '#map_user_settings_form' ).submit( function( e ){

							isDirty = false;

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

						// restore the wizard state persisted before the post-save reload
						var map_step_match = /^#map-step-([A-Za-z]+)$/.exec( window.location.hash );

						if( map_step_match )
						{
							var $map_step_button = $( '[data-bs-target="#collapse' + map_step_match[1] + '"]', $my_agile_privacy_backend ).first();

							if( $map_step_button.length && !$( '#collapse' + map_step_match[1] ).hasClass( 'show' ) )
							{
								$map_step_button.trigger( 'click' );
							}
						}

						// persist the open step through the reload ( non-element-id token )
						$( '#map_user_settings_form' ).on( 'submit', function(){

							try{

								var map_open_step = $( '.accordion-collapse.show', $my_agile_privacy_backend ).attr( 'id' );

								if( map_open_step )
								{
									window.location.hash = '#map-step-' + map_open_step.replace( 'collapse', '' );
								}
							}
							catch( error )
							{
								console.error( error );
							}
						});

						/* A pending license code must be submitted before moving on: the wizard
						   state is computed server-side, so leaving the step would show it stale.
						   A dedicated button shows up next to the field, and the step headers are
						   disabled meanwhile: a disabled button emits no click at all, so the
						   collapse data-api never fires. */
						var $map_license_save_wrapper = $( '.map-license-save-wrapper', $my_agile_privacy_backend );
						var $map_step_buttons = $( '#settingsAccordion .accordion-button', $my_agile_privacy_backend );

						$( ':input[name="license_code_field"]', $my_agile_privacy_backend ).on( 'change keyup', function(){

							$map_license_save_wrapper.removeClass( 'displayNone' );
							$map_step_buttons.prop( 'disabled', true );
						});

						$( '#map-license-save', $my_agile_privacy_backend ).on( 'click', function(){

							$map_step_buttons.prop( 'disabled', false );

							$( '#map_user_settings_form' ).submit();
						});
					}

					if( $my_agile_privacy_backend.hasClass( 'translationsWrapper' ) )
					{
						init_generic_options = true;

						console.debug( map_backend_prefix + '.translationsWrapper context');

						$( 'span[data-edit]', $my_agile_privacy_backend )
							.on( 'keydown', function( e ) {
								const key = e.key || e.originalEvent.key;
								const code = e.which || e.keyCode;

								if( key === 'Enter' || code === 13 )
								{
									e.preventDefault();
									$( this ).trigger( 'click' );
								} else if( key === ' ' || code === 32 )
								{
									e.preventDefault(); // prevents scroll
									$( this ).data( 'spacePressed', true );
								}
							})
							.on( 'keyup', function( e ) {
								const key = e.key || e.originalEvent.key;
								const code = e.which || e.keyCode;

								if( ( key === ' ' || code === 32 ) && $( this ).data('spacePressed') )
								{
									$( this ).removeData( 'spacePressed' );
									$( this ).trigger( 'click' );
								}
							})
							.on('blur', function() {
								$( this ).removeData( 'spacePressed' );
							});

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

								console.debug( map_backend_prefix + 'Element clicked:', $this.attr('data-edit'));

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

					if( $my_agile_privacy_backend.hasClass( 'siteAndPoliciesSettingsWrapper' ) )
					{
						console.debug( map_backend_prefix + '.siteAndPoliciesSettingsWrapper context');

						let currentStep = 1;
						const totalSteps = 6;

						// Read regulation_config (support both local scope and window)
						let cfg = {};

						try
						{
							if( typeof regulation_config !== 'undefined' ) cfg = regulation_config;
						}
						catch ( error )
						{
							console.error( error );
						}

						if( !cfg || typeof cfg !== 'object' ) cfg = window.regulation_config || {};

						// Collects baseLocation + selected area keys (checked checkboxes), all lowercase and unique
						function collectSelectedAreas()
						{
							const $w = $my_agile_privacy_backend;

							const baseLocation = (($w.find( '[name="site_and_policy_settings[base_location]"]' ).val() || '') + '')
							.trim().toLowerCase() || null;
							const customerLocation = (($w.find( '[name="site_and_policy_settings[customer_location]"]' ).val() || '') + '')
							.trim().toLowerCase() || null;

							const set = new Set();

							if( baseLocation == 'us' )
							{
								cfg?.usa_sub_list.forEach( ( value, index ) => {
									set.add( value );
								});
							}
							else
							{
								// Always include baseLocation if present
								if( baseLocation ) set.add( baseLocation );
							}

							// Only collect customer areas when the selector is "select_countries"
							if( customerLocation === 'select_countries' )
							{
								const re = /^site_and_policy_settings\[customer_area_([^\]]+)\]$/;
								$w.find( ':checkbox[name^="site_and_policy_settings[customer_area_"]' ).each( function()
									{
										const $chk = $( this );

										if( $chk.is( ':checked' ) )
										{
											const name = $chk.attr( 'name' ) || '';
											const m = name.match( re );
											if( m && m[1] ) set.add( String( m[1] ).toLowerCase() );
										}
									});
							}

							return { baseLocation, customerLocation, values: Array.from( set ) };
						}

						// Hides all .regulation_wrapper and the error message, then shows matching wrappers
						// If nothing is shown, reveals .regulation_wrapper_error_message
						function applyRegulationConfig()
						{
							const $w = $my_agile_privacy_backend;

							// Hide all wrappers and the error message first
							$w.find( '.regulation_wrapper, .regulation_wrapper_error_message' ).addClass( 'displayNone' );

							// Collect values and normalize to lowercase
							const { values } = collectSelectedAreas();
							const valueSet = new Set( values.map( v => String( v ).toLowerCase() ) );

							//console.log( valueSet );

							const toShowSet = new Set();

							// Match values against each list in regulation_config
							Object.keys( cfg ).forEach( listKey => {



								const list = Array.isArray( cfg[listKey] ) ? cfg[listKey] : [];
								const listLC = list.map( x => String( x ).toLowerCase() );

								//for site_and_policy_settings[customer_area_eu] selection
								if( listKey == 'gdpr_like_list' )
								{
									const hasUEMatch = valueSet.has( 'eu' );

									if( hasUEMatch )
									{
										const regulationKey = 'regulation_gdpr_like';
										toShowSet.add( regulationKey );

										//console.log( listKey );
										//console.log( regulationKey );
									}
								}

								const hasMatch = listLC.some( code => valueSet.has( code ) );

								if( hasMatch )
								{
									const regulationKey = 'regulation_' + listKey.replace(/_list$/, '' );
									toShowSet.add( regulationKey );

									//console.log( listKey );
									//console.log( regulationKey );
								}

							});

							// Show all matching wrappers
							let shownCount = 0;

							//console.log( toShowSet );

							toShowSet.forEach( regKey => {

								const $targets = $w.find( `.regulation_wrapper[data-regulation="${regKey}"]` );
								if( $targets.length )
								{
									$targets.removeClass( 'displayNone' );
									shownCount += $targets.length;
								}
							});

							// If nothing is shown, display the error message
							if( shownCount === 0 )
							{
								$w.find( '.regulation_wrapper_error_message' ).removeClass( 'displayNone' );
							}
						}

						// Wizard navigation functions
						function updateWizardUI()
						{
							//console.debug( map_backend_prefix + 'updateWizardUI, currentStep=' + currentStep );

							// Hide all steps
							$( '.wizard-step', $my_agile_privacy_backend ).removeClass( 'active' );

							// Show current step
							$( '#step-' + currentStep, $my_agile_privacy_backend ).addClass( 'active' );

							// Update progress bar
							const progressPercent = ( currentStep / totalSteps ) * 100;
							$( '#map_policy_assistant_progress-bar' ).css( 'width', progressPercent + '%' );

							// Update hidden input with completion percentage only if it's higher than saved value
							const currentSavedPercent = parseInt( $( '#policy_completion_percentage' ).val() ) || 0;

							if( currentSavedPercent > 100 )
							{
								currentSavedPercent = 100;
							}

							if( progressPercent > currentSavedPercent )
							{
								$( '#policy_completion_percentage' ).val( progressPercent );
							}

							// Update step indicators
							$( '.step-indicator', $my_agile_privacy_backend ).removeClass( 'active' );
							$( '.step-indicator[data-step="' + currentStep + '"]', $my_agile_privacy_backend ).addClass( 'active' );

							// DB-IP attribution: visible only on step 2 (geo tab)
							var $dbip = $( '#dbip-attribution' );
							if( $dbip.length ) {
								if( currentStep === 2 ) { $dbip.removeClass( 'displayNone' ); }
								else                    { $dbip.addClass( 'displayNone' ); }
							}

							// Update navigation buttons
							if( currentStep === 1 )
							{
								$( '.map-wizard-prev-btn', $my_agile_privacy_backend ).addClass(' displayNone' );

								$( '.map-wizard-next-btn', $my_agile_privacy_backend ).addClass(' displayNone' );

								$( '.map-wizard-next-btn-first-step', $my_agile_privacy_backend ).removeClass(' displayNone' );
							}
							else
							{
								$( '.map-wizard-prev-btn', $my_agile_privacy_backend ).removeClass(' displayNone' );

								$( '.map-wizard-next-btn', $my_agile_privacy_backend ).removeClass(' displayNone' );

								$( '.map-wizard-next-btn-first-step', $my_agile_privacy_backend ).addClass(' displayNone' );
							}

							if( currentStep === totalSteps )
							{
								$( '.map-wizard-next-btn', $my_agile_privacy_backend ).addClass(' displayNone' );
								$( '.map-wizard-finish-btn', $my_agile_privacy_backend ).removeClass(' displayNone' );
							}
							else
							{
								$( '.map-wizard-finish-btn', $my_agile_privacy_backend ).addClass(' displayNone' );
							}

							// Show "Save and Complete" button only if wizard was completed (100%) and not on completion step
							// Read the actual saved percentage from PHP data, not from the input field
							const actualSavedPercent = 0;

							// Show button only if actual saved percentage is 100% and not on completion step
							if( actualSavedPercent >= 100 && currentStep < 6 )
							{
								$( '.map-wizard-save-complete-btn', $my_agile_privacy_backend ).removeClass(' displayNone' );
							}
							else
							{
								$( '.map-wizard-save-complete-btn', $my_agile_privacy_backend ).addClass(' displayNone' );
							}
						}

						//validation func
						function validateCurrentStep()
						{
							const currentStepElement = $( '#step-' + currentStep );
							let isValid = true;

							// Check required fields in current step
							currentStepElement
								.find( 'input[required], select[required]' )
								.each( function() {

								var $elem = $( this );

								if( !$elem.val() )
								{
									isValid = false;
									$elem.addClass( 'is-invalid' );
								}
								else
								{
									$elem.removeClass( 'is-invalid' );
								}
							});

							// Collect all checkboxes with data-checkbox-group
							var boxes = currentStepElement
										.find( 'input[type="checkbox"][data-checkbox-group]' )
										.get();

							// Build the set of present groups
							var groupsMap = Object.create( null );
							for( var i = 0; i < boxes.length; i++ )
							{
								var g = boxes[i].getAttribute( 'data-checkbox-group' );
								if (g !== null && g !== '')
								{
									//check for wrapper visibility
									var $this_wrapper = $( '.' + g );

									if( $this_wrapper.length )
									{
										if( !$this_wrapper.hasClass( 'displayNone' ) )
										{
											groupsMap[g] = true;
										}
									}
									else
									{
										groupsMap[g] = true;
									}
								}
							}

							//console.log( groupsMap );

							var keys = Object.keys( groupsMap );
							for( var k = 0; k < keys.length; k++ )
							{
								var val = keys[k];

								var map_group_message_warning = $( '.map_group_message_warning[data-checkbox-group-message="'+ val + '"]');

								if( map_group_message_warning.length )
								{
									map_group_message_warning.addClass( 'displayNone' );
								}

								if( currentStepElement.find( 'input[type="checkbox"][data-checkbox-group="' + val + '"]:checked:not(:disabled)' ).length === 0 )
								{
									isValid = false;

									if( map_group_message_warning.length )
									{
										map_group_message_warning.removeClass( 'displayNone' );
									}

									break;
								}
							}

							//console.debug( map_backend_prefix + 'isValid for currentStep=' + currentStep + ', status=' + isValid );

							return isValid;
						}

						//save function
						function saveStepData( callback )
						{
							var $form = $( '#map_policy_assistant_form' );

							$( '#last_update_timestamp' ).val( Math.floor( Date.now() / 1000 ) );

							var data = $form.serialize();
							var url = $form.attr( 'action' );

							$( '.map_wait' ).fadeIn();

							return $.ajax({
								url: url,
								type: 'POST',
								data: data,
								success: function( response )
								{
									$( '.map_wait' ).fadeOut();

									if( response.success )
									{
										map_pupup_notify.success( map_settings_step_saved_text );

										if( callback )
										{
											callback();
										}
									}
									else
									{
										map_pupup_notify.error( map_settings_error_message_text );
									}
								},
								error: function()
								{
									map_pupup_notify.error( map_settings_connection_error );
								}
							});
						}

						$( '#step-2 :input', $my_agile_privacy_backend ).on( 'change keyup', function(){

							//console.debug( map_backend_prefix + 'change' );

							applyRegulationConfig();

						}).first().trigger('change');

						// Next button click
						$( '.map-wizard-next-btn, .map-wizard-next-btn-first-step', $my_agile_privacy_backend ).on( 'click', function() {

							//console.debug( map_backend_prefix + 'click on next' );

							if( validateCurrentStep() )
							{
								if( currentStep == 1 )
								{
									currentStep++;
									updateWizardUI();
								}
								else
								{
									// Save current step data before proceeding
									saveStepData().done( function( response )
									{
										if( response.success )
										{
											if( currentStep < totalSteps )
											{
												currentStep++;
												updateWizardUI();

												$('html, body').animate({
												    scrollTop: $my_agile_privacy_backend.offset().top
												  }, 600);


											}
										}
										else
										{
											map_pupup_notify.error( map_settings_connection_error );
										}
									}).fail( function() {
										map_pupup_notify.error( map_settings_error_message_text );
									});
								}
							}
							else
							{
								// Show validation error
								map_pupup_notify.warning( map_settings_need_validation_text );
							}
						});


						// Previous button click
						$( '.map-wizard-prev-btn', $my_agile_privacy_backend ).on( 'click', function() {

							//console.debug( map_backend_prefix + 'click on prev' );

							if( currentStep > 1 )
							{
								currentStep--;
								updateWizardUI();

								$('html, body').animate({
								    scrollTop: $my_agile_privacy_backend.offset().top
								  }, 600);

							}
						});

						// Form submission - Complete Configuration button
						$( '.map-wizard-finish-btn', $my_agile_privacy_backend ).on( 'click', function( e ) {

							//console.debug( map_backend_prefix + 'click on finish' );

							e.preventDefault();

							if( validateCurrentStep() )
							{
								// Save final step data
								saveStepData().done( function( response )
								{
									if( response.success )
									{
										// Move to completion step
										currentStep = 7; // Go to completion step
										updateWizardUI();

										// Hide navigation buttons on completion step
										$( '.wizard-navigation', $my_agile_privacy_backend ).hide();

										// Hide progress bar on completion step
										$( '.wizard-progress', $my_agile_privacy_backend ).hide();
									}
									else
									{
										map_pupup_notify.error( map_settings_error_message_text );
									}
								}).fail( function() {
									map_pupup_notify.error( map_settings_connection_error );
								});
							} else {
								// Show validation error
								ap_pupup_notify.error( map_settings_error_message_text );
							}
						});

						$( '.map-sensitive-data-toggle', $my_agile_privacy_backend ).on( 'click', function( e ) {
							e.preventDefault();
							$( '.map-sensitive-data-examples', $my_agile_privacy_backend ).slideToggle();
						});

						// Toggle checkbox on policy-card click when data-clickable is true
						$('.policy-card[data-clickable="true"]', $my_agile_privacy_backend).on('click', function(e) {
							// Prevent triggering if clicking on checkbox or label
							if (!$(e.target).is('input[type="checkbox"], label')) {
								var $checkbox = $(this).find('.card-footer input[type="checkbox"]');
								if ($checkbox.length && !$checkbox.prop('disabled')) {
									$checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');
								}
							}
						});

						updateWizardUI();
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

												jQuery( '#preview-title', $preview_cookiebanner ).html( '<div class="banner-title-logo" style="background:' + heading_color + ';"></div>  My Agile Privacy®' );
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
							//console.debug( map_backend_prefix + '.fake-save-button context');

							$save_trigger_buttons.on( 'click', function( e )
							{
								e.preventDefault();

								$( '#map-save-button' ).trigger( 'click' );
							});
						}

						var $color_preset_select = $( '#color_preset' );

						if( $color_preset_select.length )
						{
							//console.debug( map_backend_prefix + '#color_preset context');

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
							//console.debug( map_backend_prefix + '#color_preset context');

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
						var hash = window.location.hash;

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

				//console.debug( map_backend_prefix + 'click on mapx-btn-add' );

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
			// Check background colors
			const bgColors = $( '#cookie_banner_options_container input[type="color"][id$="_button_color_field"]' )
				.map(function() {
					return $( this ).val();
				}).get();

			const bgAllEqual = bgColors.every( color => color === bgColors[0] );

			// Check link colors
			const linkColors = $( '#cookie_banner_options_container input[type="color"][id$="_link_color_field"]' )
				.map(function() {
					return $( this ).val();
				}).get();

			const linkAllEqual = linkColors.every( color => color === linkColors[0] );

			// Show/hide alert based on results
			if( bgAllEqual && linkAllEqual )
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
			// Standardize background colors
			var sourceBgColor = $( '#button_accept_button_color_field' ).val();
			$( '#button_reject_button_color_field, #button_customize_button_color_field' )
				.val( sourceBgColor )
				.trigger( 'change' );

			console.debug( map_backend_prefix + 'Background colors standardized to: ' + sourceBgColor);

			// Standardize link colors
			var sourceLinkColor = $( '#button_accept_link_color_field' ).val();
			$( '#button_reject_link_color_field, #button_customize_link_color_field' )
				.val( sourceLinkColor )
				.trigger( 'change' );

			console.debug( map_backend_prefix + 'Link colors standardized to: ' + sourceLinkColor);
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

	// ── Geo cards module (policy assistant, step 2) ───────────────────────────
	$(function() {
		if (typeof MAPGEO_DATA === 'undefined') { return; }

		var D         = MAPGEO_DATA;
		var FLAG_BASE = D.flag_base;
		var REGIONS   = D.regions;
		var ORDER     = D.order;
		var US_STATES = D.us_states;
		var NOTE      = D.note;
		var TXT       = D.txt;
		var FORCED    = D.forced;

		function sedeForte() {
			var baseEl   = document.getElementById('base_location');
			var baseLoc  = baseEl ? baseEl.value.toLowerCase() : '';
			var gdprLike = (typeof regulation_config !== 'undefined' && regulation_config.gdpr_like_list) ? regulation_config.gdpr_like_list : [];
			return gdprLike.indexOf(baseLoc) !== -1;
		}

		function isMyCountryMode() {
			var el = document.getElementById('customer_location');
			return el && el.value === 'my_country';
		}

		function baseRegion() {
			var baseEl   = document.getElementById('base_location');
			var v        = baseEl ? baseEl.value.toLowerCase() : '';
			if (!v || v === 'unsupported') { return null; }
			var explicit = { gb: 'uk', us: 'us', ch: 'ch', ca: 'ca', br: 'br' };
			if (explicit[v]) { return explicit[v]; }
			var gdprLike = (typeof regulation_config !== 'undefined' && regulation_config.gdpr_like_list) ? regulation_config.gdpr_like_list : [];
			return (gdprLike.indexOf(v) !== -1) ? 'ue' : null;
		}

		function isSelected(k) {
			if (isMyCountryMode()) { return baseRegion() === k; }
			if (k === 'us') {
				var baseEl = document.getElementById('base_location');
				if (baseEl && baseEl.value.toLowerCase() === 'us') { return true; }
				for (var i = 0; i < US_STATES.length; i++) {
					var cb = document.getElementById('customer_area_' + US_STATES[i]);
					if (cb && cb.checked) { return true; }
				}
				return false;
			}
			var el = document.getElementById(REGIONS[k].checkbox);
			return !!(el && el.checked);
		}

		function canForce(k) { return sedeForte() && REGIONS[k].nat === 'noblock'; }

		function effType(k) {
			if (REGIONS[k].nat === 'block') { return 'block'; }
			if (sedeForte()) { return FORCED[k] ? 'noblock' : 'block'; }
			return 'noblock';
		}

		function nativeNote(k) {
			if (k === 'ue') { return sedeForte() ? NOTE.ue_strong : NOTE.ue_light; }
			return NOTE[k] || '';
		}

		function esc(s) {
			var d = document.createElement('div');
			d.textContent = (s == null ? '' : s);
			return d.innerHTML;
		}

		function badge(type) {
			return type === 'block'
				? '<span class="mapgeo-badge mapgeo-badge-block">🔒 ' + esc(TXT.badge_block) + '</span>'
				: '<span class="mapgeo-badge mapgeo-badge-noblock">🔓 ' + esc(TXT.badge_noblock) + '</span>';
		}

		function render() {
			var forte = sedeForte();
			var html  = '';

			ORDER.forEach(function (k) {
				if (!isSelected(k)) { return; }
				var eff        = effType(k);
				var toggleHtml = '';
				if (canForce(k)) {
					var ck = FORCED[k] ? ' checked' : '';
					toggleHtml =
						'<label class="mapgeo-toggle"><span class="mapgeo-sw">' +
						'<input type="hidden" name="site_and_policy_settings[geo_force_optout][' + k + ']" value="false">' +
						'<input type="checkbox" name="site_and_policy_settings[geo_force_optout][' + k + ']" value="true"' + ck + ' data-region="' + k + '" class="mapgeo-force-toggle">' +
						'<span class="mapgeo-kn"></span></span>' + esc(TXT.toggle) + '</label>';
				}
				var noteHtml;
				if (canForce(k) && eff === 'noblock') {
					noteHtml = '<div class="mapgeo-note mapgeo-note-warn">' + esc(NOTE.forced_risk) + '</div>';
				} else if (canForce(k) && eff === 'block') {
					noteHtml = '<div class="mapgeo-note">' + esc(NOTE.force_off) + '</div>';
				} else {
					noteHtml = '<div class="mapgeo-note">' + esc(nativeNote(k)) + '</div>';
				}
				var flagHtml = REGIONS[k].flag ? '<img class="mapgeo-flag" src="' + FLAG_BASE + REGIONS[k].flag + '" alt="">' : '';
				html += '<div class="mapgeo-card">' + flagHtml + '<div class="mapgeo-card-title">' + esc(REGIONS[k].label) + '</div>' + badge(eff) + noteHtml + toggleHtml + '</div>';
			});

			// "Altri paesi" card — always present.
			var rowType, rowNote;
			if (forte) {
				rowType = 'block';
				rowNote = '<div class="mapgeo-note">' + esc(NOTE.others_block) + '</div>';
			} else {
				rowType = 'noblock';
				rowNote = '<div class="mapgeo-note">' + esc(NOTE.others_noblock) + '<br><span class="mapgeo-note-soft">' + esc(NOTE.others_noblock_soft) + '</span></div>';
			}
			html += '<div class="mapgeo-card-default"><div class="mapgeo-card-title">🌍 ' + esc(TXT.others) + '</div>' + badge(rowType) + rowNote + '</div>';

			// Global notice.
			var hasBlock = (rowType === 'block'), hasNoBlock = (rowType === 'noblock');
			ORDER.forEach(function (k) {
				if (!isSelected(k)) { return; }
				if (effType(k) === 'block') { hasBlock = true; } else { hasNoBlock = true; }
			});
			var noticeHtml;
			if (hasBlock && hasNoBlock) {
				noticeHtml = '<div class="mapgeo-notice mapgeo-notice-info"><strong>📍 ' + esc(TXT.notice_mixed_strong) + '</strong> ' + esc(TXT.notice_mixed) + '</div>';
			} else {
				noticeHtml = '<div class="mapgeo-notice mapgeo-notice-neutral">✓ ' + esc(hasNoBlock ? TXT.notice_noblock : TXT.notice_block) + '</div>';
			}

			document.getElementById('mapgeo_notice').innerHTML = noticeHtml;
			document.getElementById('mapgeo_cards').innerHTML  = html;

			// Re-bind force toggles (re-created on every render).
			document.querySelectorAll('.mapgeo-force-toggle').forEach(function (cb) {
				cb.addEventListener('change', function () {
					FORCED[this.getAttribute('data-region')] = this.checked;
					render();
				});
			});
		}

		var baseEl    = document.getElementById('base_location');
		var custLocEl = document.getElementById('customer_location');
		if (baseEl)    { baseEl.addEventListener('change', render); }
		if (custLocEl) { custLocEl.addEventListener('change', render); }
		US_STATES.forEach(function (s) {
			var cb = document.getElementById('customer_area_' + s);
			if (cb) { cb.addEventListener('change', render); }
		});
		['eu', 'gb', 'ch', 'ca', 'br'].forEach(function (r) {
			var cb = document.getElementById('customer_area_' + r);
			if (cb) { cb.addEventListener('change', render); }
		});

		render();
	});

	// ── Advertiser Consent Mode toggle visibility ──
	$(function() {
		var $iab   = $( '#enable_iab_tcf_field' );
		var $cmode = $( '#enable_cmode_v2_field' );
		var $wrap  = $( '.map_acm_wrapper' );

		if ( ! $wrap.length ) { return; }

		function syncAcmVisibility() {
			var show = $iab.is( ':checked' ) && $cmode.is( ':checked' );
			$wrap.toggleClass( 'displayNone', ! show );
		}

		$iab.on( 'change', syncAcmVisibility );
		$cmode.on( 'change', syncAcmVisibility );
		syncAcmVisibility();
	});

})( jQuery );
