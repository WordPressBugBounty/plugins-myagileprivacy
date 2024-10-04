<?php

	if( !defined( 'MAP_PLUGIN_NAME' ) )
	{
		exit('Not allowed.');
	}

	$locale = get_user_locale();
?>
<div class="row">
	<div class="col-sm-8">

		<div class="consistent-box">
			<h4 class="mb-4">
				<i class="fa-regular fa-tablet-screen"></i>
				<?php _e('Consent Widget', 'MAP_txt'); ?>
			</h4>

			<!-- widget review consent show -->
			<div class="row mb-4">
				<label for="showagain_tab_field" class="col-sm-5 col-form-label">
					<?php _e('Enable revisit consent widget', 'MAP_txt'); ?>
				</label>

				<div class="col-sm-7">
					<div class="styled_radio d-inline-flex">
						<div class="round d-flex me-4">

							<input type="hidden" name="showagain_tab_field" value="false" id="showagain_tab_field_no">

							<input name="showagain_tab_field" type="checkbox" value="true" id="showagain_tab_field" class="hideShowInput" data-hide-show-ref="showagain_tab" <?php checked( $the_options['showagain_tab'], true); ?>>

							<label for="showagain_tab_field" class="me-2 label-checkbox"></label>

							<label for="showagain_tab_field">
								<?php _e('Enable revisit consent widget', 'MAP_txt'); ?>
							</label>
						</div>
					</div> <!-- ./ styled_radio -->

					<div class="form-text">
						<?php echo esc_html__('Warning: if you disable this, add the proper link for consent revisit in the foote area, in order to stay GDPR compliant. You can use the [myagileprivacy_showconsent] shortcode.', 'MAP_txt'); ?>
					</div>

				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->

			<div class="showagain_tab displayNone">

				<!-- widget position -->
				<div class="row mb-4">
					<label for="notify_position_horizontal_field" class="col-sm-5 col-form-label">
						<?php _e('Tab Position', 'MAP_txt'); ?>
					</label>

					<div class="col-sm-7">
						<select id="notify_position_horizontal_field" name="notify_position_horizontal_field" class="form-control">
						<?php

						$valid_options = array(
							'right'	=>	array(  'label' => __('Right', 'MAP_txt'),
																	'selected' => false ),
							'left'	=>	array(  'label' => __('Left', 'MAP_txt'),
																	'selected' => false ),
						);

						$selected_value = $the_options['notify_position_horizontal'];

						if( isset( $valid_options[ $selected_value ] ) )
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

						<div class="form-text">
							<?php
								_e("Select the horizontal position where to show the consent again widget ", 'MAP_txt');
							?>.
						</div>
					</div> <!-- /.col-sm-6 -->
				</div> <!-- row -->

				<!-- widget review consent text -->
				<div class="row mb-4">
					<label for="showagain_text" class="col-sm-5 col-form-label">
						<?php _e('Title for show again policy', 'MAP_txt'); ?>
						<a href="<?php echo esc_url( $translation_menu_link ); ?>"><i class="fa-regular fa-comment-pen" data-bs-toggle="tooltip" data-bs-html="true" title="<?php _e('You can edit this text from the Texts and Translations section.', 'MAP_txt'); ?>"></i></a>
					</label>

					<div class="col-sm-7">
						<input type="text" class="form-control" id="showagain_text" name="" value="<?php echo esc_attr( $the_translations[$selected_lang]['manage_consent']) ?>" readonly />

					</div> <!-- /.col-sm-6 -->
				</div> <!-- row -->

				<!-- widget show cookie policy link -->
				<div class="row mb-4">
					<label for="cookie_policy_link_field" class="col-sm-5 col-form-label">
						<?php _e('Show Cookie Policy link', 'MAP_txt'); ?>
					</label>

					<div class="col-sm-7">
						<div class="styled_radio d-inline-flex">
							<div class="round d-flex me-4">
								<input type="hidden" name="cookie_policy_link_field" value="false" id="cookie_policy_link_field_no">

								<input name="cookie_policy_link_field" type="checkbox" value="true" id="cookie_policy_link_field" <?php checked( $the_options['cookie_policy_link'], true); ?>>
								<label for="cookie_policy_link_field" class="me-2 label-checkbox"></label>

								<label for="cookie_policy_link_field">
									<?php _e('Show Cookie Policy link', 'MAP_txt'); ?>
								</label>

							</div>
						</div> <!-- ./ styled_radio -->
					</div> <!-- /.col-sm-6 -->
				</div> <!-- row -->

				<!-- widget logo show / hide -->
				<div class="row mb-4">
					<label for="disable_logo_field" class="col-sm-5 col-form-label">
						<?php _e('Disable My Agile Privacy logo', 'MAP_txt'); ?>
					</label>

					<div class="col-sm-7">
						<div class="styled_radio d-inline-flex">
							<div class="round d-flex me-4">

								<input type="hidden" name="disable_logo_field" value="false" id="disable_logo_field_no">

								<input name="disable_logo_field" type="checkbox" value="true" id="disable_logo_field" <?php checked($the_options['disable_logo'], true); ?>>

								<label for="disable_logo_field" class="me-2 label-checkbox"></label>

								<label for="disable_logo_field">
									<?php echo esc_html__('Disable logo on Cookie Bar', 'MAP_txt'); ?>
								</label>
							</div>
						</div> <!-- ./ styled_radio -->

						<div class="form-text">
							<?php echo esc_html__('Check this option to remove My Agile Privacy logo on the consent review widget', 'MAP_txt'); ?>.
						</div>

					</div> <!-- /.col-sm-6 -->
				</div> <!-- row -->

			</div>

		</div> <!-- consistent-box -->


		<?php
			$display_cmode_v2 = false;

			if( isset( $the_options ) &&
			isset( $the_options['pa'] ) &&
			$the_options['pa'] == 1
		)
		{
			$display_cmode_v2 = true;
		}
		?>

		<span class="translate-middle-y forbiddenWarning badge rounded-pill bg-danger  <?php if( $display_cmode_v2 ){echo 'd-none';} ?>">
			<small><?php _e('Premium Feature', 'MAP_txt'); ?></small>
		</span>
		<div class="consistent-box <?php if( !$display_cmode_v2 ){echo 'forbiddenArea';} ?>">
			<h4 class="mb-4">
				<i class="fa-brands fa-google"></i>
				<?php _e('Google Consent Mode v2', 'MAP_txt'); ?>
			</h4>

			<!-- widget review consent show -->
			<div class="row mb-4">
				<label for="showagain_tab_field" class="col-sm-5 col-form-label">
					<?php _e('Enable Google Consent Mode v2', 'MAP_txt'); ?>
				</label>

				<div class="col-sm-7">
					<div class="styled_radio d-inline-flex">
						<div class="round d-flex me-4">

							<input type="hidden" name="enable_cmode_v2_field" value="false" id="enable_cmode_v2_field_no">

							<input name="enable_cmode_v2_field" type="checkbox" value="true" id="enable_cmode_v2_field" class="hideShowInput" data-hide-show-ref="enable_cmode_v2_options" <?php checked( $the_options['enable_cmode_v2'], true); ?>>

							<label for="enable_cmode_v2_field" class="me-2 label-checkbox"></label>
								<?php

									$cmode_link = 'https://www.myagileprivacy.com/en/supporting-consent-mode-v2-what-it-is-and-how-to-implement-it-gdpr-compliant-with-my-agile-privacy/';

									if( $locale && $locale == 'it_IT' )
									{
										$cmode_link = 'https://www.myagileprivacy.com/supporto-alla-consent-mode-v2-cose-e-come-implementarla-a-norma-gdpr-con-my-agile-privacy';
									}
								?>

							<label for="enable_cmode_v2_field">
								<?php echo sprintf( __('Enable Google Consent Mode v2 - %1$sOnline Help%2$s', 'MAP_txt'), '<a href="'.esc_attr( $cmode_link ).'" target="_blank">','</a>' ); ?>
							</label>
						</div>
					</div> <!-- ./ styled_radio -->

				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->

			<div class="enable_cmode_v2_options displayNone">

				<div class="row mb-4">
					<label for="cmode_v2_implementation_type_field" class="col-sm-5 col-form-label">
						<?php _e('Select the type of implementation', 'MAP_txt'); ?>
					</label>

					<div class="col-sm-7">

						<select id="cmode_v2_implementation_type_field" name="cmode_v2_implementation_type_field" class="hideShowInput form-control" style="max-width:100%;" data-hide-show-ref="cmode_v2_implementation_type_options">
							<?php

							$valid_options = array(
								'native'	  =>	array(  'label' => __('via My Agile Privacy', 'MAP_txt'),
																		'selected' => false ),
								'gtm'	      =>	array(  'label' => __('via Google Tag Manager', 'MAP_txt'),
																		'selected' => false ),
							);

							$selected_value = $the_options['cmode_v2_implementation_type'];

							if( isset( $valid_options[ $selected_value ] ) )
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

					</div> <!-- col -->
				</div> <!-- /row-->

				<div class="cmode_v2_implementation_type_options displayNone mt-4" data-value="native">
					<div class="row mb-3">
						<div class="col-12">
						<strong><?php _e('Native implementation via My Agile Privacy', 'MAP_txt'); ?></strong>
						<p><?php _e("Select the initial configuration of the parameters necessary for the operation of Consent Mode v2. The standard configuration, as required by regulations, precedes all parameters set to 'denied'.", 'MAP_txt'); ?></p>
						</div>
					</div>
					<div class="row m-0 p-0 alert">
						<label for="enable_cmode_url_passthrough_field" class="col-sm-5 col-form-label">
							<?php _e('Url Passthrough', 'MAP_txt'); ?><br>
							<span class="form-text">
								<?php echo esc_html__("This option is useful if you do not want to lose the data related to the user, in case they do not immediately accept cookies but decide to do so later.", 'MAP_txt'); ?>
							</span>
						</label>

						<div class="col-sm-7">
							<div class="styled_radio d-inline-flex">
								<div class="round d-flex me-4">
									<input type="hidden" name="enable_cmode_url_passthrough_field" value="false" id="enable_cmode_url_passthrough_field_no">
									<input name="enable_cmode_url_passthrough_field" type="checkbox" value="true" id="enable_cmode_url_passthrough_field" class="hideShowInput" data-hide-show-ref="enable_cmode_url_passthrough_options" <?php checked( $the_options['enable_cmode_url_passthrough'], true); ?>>
									<label for="enable_cmode_url_passthrough_field" class="me-2 label-checkbox"></label>
									<label for="enable_cmode_url_passthrough_field">
										<?php _e('Enable Url Passthrough', 'MAP_txt'); ?>
									</label>
								</div>
							</div> <!-- ./ styled_radio -->
						</div>

					</div> <!-- /row-->

					<div class="m-0 p-0 row alert">
						<label for="cmode_v2_gtag_ad_storage_field" class="col-sm-5 col-form-label">
							<?php _e('Ad Storage', 'MAP_txt'); ?><br>
							<span class="form-text">
								<?php echo esc_html__('Defines whether cookies related to advertising can be read or written by Google.', 'MAP_txt'); ?>
							</span>
						</label>

						<div class="col-sm-7">

							<select id="cmode_v2_gtag_ad_storage_field" name="cmode_v2_gtag_ad_storage_field" class="form-control" style="max-width:100%;">
								<?php

								$valid_options = array(
									'denied'    =>  array(  'label' => __('Denied', 'MAP_txt'),
																			'selected' => false ),
									'granted'   =>  array(  'label' => __('Granted', 'MAP_txt'),
																			'selected' => false ),
								);

								$selected_value = $the_options['cmode_v2_gtag_ad_storage'];

								if( isset( $valid_options[ $selected_value ] ) )
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

							<div class="suggested-value-alert d-none">

								<strong>
									<?php
										_e('Suggested value for compliance:', 'MAP_txt');
									?>
								</strong> denied

							</div>
						</div> <!-- col -->

					</div> <!-- /row-->

					<div class="m-0 p-0 row alert">
						<label for="cmode_v2_gtag_ad_user_data_field" class="col-sm-5 col-form-label">
							<?php _e('Ad User Data', 'MAP_txt'); ?><br>
							<span class="form-text">
								<?php echo esc_html__('Determines whether user data can be sent to Google for advertising purposes.', 'MAP_txt'); ?>
							</span>
						</label>

						<div class="col-sm-7">
							<select id="cmode_v2_gtag_ad_user_data_field" name="cmode_v2_gtag_ad_user_data_field" class="form-control" style="max-width:100%;">
								<?php

								$valid_options = array(
									'denied'    =>  array(  'label' => __('Denied', 'MAP_txt'),
																			'selected' => false ),
									'granted'   =>  array(  'label' => __('Granted', 'MAP_txt'),
																			'selected' => false ),
								);

								$selected_value = $the_options['cmode_v2_gtag_ad_user_data'];

								if( isset( $valid_options[ $selected_value ] ) )
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

							<div class="suggested-value-alert d-none">
								<strong>
									<?php
										_e('Suggested value for compliance:', 'MAP_txt');
									?>
								</strong> denied
							</div>

						</div> <!-- col -->
					</div> <!-- /row-->

					<div class="m-0 p-0 row alert">
						<label for="cmode_v2_gtag_ad_personalization_field" class="col-sm-5 col-form-label">
							<?php _e('Ad Personalization', 'MAP_txt'); ?><br>
							<span class="form-text">
								<?php echo esc_html__('Controls whether personalized advertising (for example, remarketing) can be enabled.', 'MAP_txt'); ?>
							</span>
						</label>

						<div class="col-sm-7">

							<select id="cmode_v2_gtag_ad_personalization_field" name="cmode_v2_gtag_ad_personalization_field" class="form-control" style="max-width:100%;">
								<?php

								$valid_options = array(
									'denied'    =>  array(  'label' => __('Denied', 'MAP_txt'),
																			'selected' => false ),
									'granted'   =>  array(  'label' => __('Granted', 'MAP_txt'),
																			'selected' => false ),
								);

								$selected_value = $the_options['cmode_v2_gtag_ad_personalization'];

								if( isset( $valid_options[ $selected_value ] ) )
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

							<div class="suggested-value-alert d-none">
								<strong>
									<?php
										_e('Suggested value for compliance:', 'MAP_txt');
									?>
								</strong> denied
							</div>
						</div> <!-- col -->
					</div> <!-- /row-->

					<div class="m-0 p-0 row alert">
						<label for="cmode_v2_gtag_analytics_storage_field" class="col-sm-5 col-form-label">
							<?php _e('Analytics Storage', 'MAP_txt'); ?><br>
							<span class="form-text">
								<?php echo esc_html__('Defines whether cookies associated with Google Analytics can be read or written.', 'MAP_txt'); ?>
							</span>
						</label>

						<div class="col-sm-7">

							<select id="cmode_v2_gtag_analytics_storage_field" name="cmode_v2_gtag_analytics_storage_field" class="form-control" style="max-width:100%;">
								<?php

								$valid_options = array(
									'denied'    =>  array(  'label' => __('Denied', 'MAP_txt'),
																			'selected' => false ),
									'granted'   =>  array(  'label' => __('Granted', 'MAP_txt'),
																			'selected' => false ),
								);

								$selected_value = $the_options['cmode_v2_gtag_analytics_storage'];

								if( isset( $valid_options[ $selected_value ] ) )
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

							<div class="suggested-value-alert d-none">
								<strong>
									<?php
										_e('Suggested value for compliance:', 'MAP_txt');
									?>
								</strong> denied
							</div>
						</div> <!-- col -->
					</div> <!-- /row-->

					<div class="row">
						<label for="cmode_v2_forced_off_ga4_advanced_field" class="col-sm-5 col-form-label">
							<?php _e( 'Disable Advanced Consent Mode', 'MAP_txt' ); ?><br>
						</label>

						<div class="col-sm-7">
							<div class="styled_radio d-inline-flex">
								<div class="round d-flex me-4">
									<input type="hidden" name="cmode_v2_forced_off_ga4_advanced_field" value="false" id="cmode_v2_forced_off_ga4_advanced_field_no">
									<input name="cmode_v2_forced_off_ga4_advanced_field" type="checkbox" value="true" id="cmode_v2_forced_off_ga4_advanced_field" class="hideShowInput" data-hide-show-ref="cmode_v2_forced_off_ga4_advanced_description" <?php checked( $the_options['cmode_v2_forced_off_ga4_advanced'], true); ?>>
									<label for="cmode_v2_forced_off_ga4_advanced_field" class="me-2 label-checkbox"></label>
									<label for="cmode_v2_forced_off_ga4_advanced_field">
										<?php _e( 'Disable Advanced Consent Mode', 'MAP_txt' ); ?>
									</label>
								</div>
							</div> <!-- ./ styled_radio -->
						</div>

						<p class="form-text cmode_v2_forced_off_ga4_advanced_description">
							<?php echo esc_html__( "By disabling this mode of operation, you will minimize the data sent to Google servers in case of a lack of user consent, achieving greater compliance. However, you may receive warnings from Google regarding the non-detection of Consent Mode V2.", 'MAP_txt' ); ?>
						</p>

					</div> <!-- /row-->
				</div>



				<div class="cmode_v2_implementation_type_options mt-4 displayNone" data-value="gtm">
					<div class="row mb-3">
						<div class="col-12">
							<strong><?php _e('Configuration via Google Tag Manager', 'MAP_txt'); ?></strong>
							<p class="mt-3">
								<?php
									echo sprintf(__('You can save the settings and continue the configuration on Google Tag Manager.<br>You can follow the setup steps in the guide we have created. %1$sClick here%2$s to go to the guide.', 'MAP_txt'),'<a href="https://www.myagileprivacy.com/supporto-alla-consent-mode-v2-cose-e-come-implementarla-a-norma-gdpr-con-my-agile-privacy" target="_blank">','</a>');

								?>
							</p>

						</div>
					</div>
				</div>






			</div>

		</div> <!-- consistent-box -->


		<?php

			$display_iab = false;

			if( defined( 'MAP_IAB_TCF' ) && MAP_IAB_TCF && isset( $rconfig ) && isset( $rconfig['allow_iab'] ) && $rconfig['allow_iab'] == 1 )
			{
				$display_iab = true;
			}

		?>


		<span class="translate-middle-y forbiddenWarning badge rounded-pill bg-danger  <?php if( $display_iab ){echo 'd-none';} ?>">
			<small>

				<?php

					if( isset( $the_options ) &&
					isset( $the_options['pa'] ) &&
					$the_options['pa'] == 1 )
					{
						_e('Feature not available for your license', 'MAP_txt');
					}
					else
					{
						_e('Premium Feature', 'MAP_txt');
					}
				?>

			</small>
		</span>

		<div class="consistent-box <?php if( !$display_iab ){echo 'forbiddenArea';} ?>">

			<h4 class="mb-4">
				<i class="fa-regular fa-tablet-screen"></i>
				<?php _e('Activate IAB Transparency and Consent Framework', 'MAP_txt'); ?>
			</h4>

			<div class="row mb-4">
				<div class="col-sm-12">

					<p><?php _e('The IAB TCF, which stands for Interactive Advertising Bureau Transparency and Consent Framework, is a standardized framework specifically developed to help businesses comply with data protection regulations.', 'MAP_txt'); ?>

					<?php _e('It serves as a mechanism that allows websites and digital advertising platforms to effectively obtain and manage user consent for the processing of personal data.', 'MAP_txt'); ?><br>
					<b><?php _e('Enabling the IAB TCF is highly recommended if you utilize advertising channels such as Google Adsense, Ad Manager, AdMob or similar platforms on your website.', 'MAP_txt'); ?></b></p>
				</div>
			</div>

			<div class="row mb-4">
				<label for="display_ccpa_field" class="col-sm-5 col-form-label">
					<?php _e('Activate IAB TCF', 'MAP_txt'); ?>
				</label>

				<div class="col-sm-7">
					<div class="styled_radio d-inline-flex">
						<div class="round d-flex me-4">

							<input type="hidden" name="enable_iab_tcf_field" value="false" id="enable_iab_tcf_field_no" data-preview="iab">

							<input name="enable_iab_tcf_field" type="checkbox" value="true" id="enable_iab_tcf_field" <?php checked( $the_options['enable_iab_tcf'], true); ?> data-preview="iab">

							<label for="enable_iab_tcf_field" class="me-2 label-checkbox"></label>

							<label for="enable_iab_tcf_field">
								<?php _e('Yes, enable. I run Google Adsense, Ad Manager, AdMob or similar platforms on my website and I would to activate the IAB Transparency and Consent Framework', 'MAP_txt'); ?>
							</label>
						</div>
					</div> <!-- ./ styled_radio -->

				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->

		</div>


		<div class="consistent-box">
			<h4 class="mb-4">
				<i class="fa-regular fa-tablet-screen"></i>
				<?php _e('Reset given consent', 'MAP_txt'); ?>
			</h4>


			<!-- reset consent checkbox -->
			<div class="row mb-4">
				<label for="reset_consent" class="col-sm-5 col-form-label">
					<?php _e('Reset given consent', 'MAP_txt'); ?>
				</label>

				<div class="col-sm-7">
					<div class="styled_radio d-inline-flex">
						<div class="round d-flex me-4">

							<input class="uncheck_on_send" name="reset_consent" type="checkbox" value="1" id="reset_consent">

							<label for="reset_consent" class="me-2 label-checkbox"></label>

							<label for="reset_consent">
								<?php _e('Do reset given consent', 'MAP_txt'); ?>
							</label>
						</div>
					</div> <!-- ./ styled_radio -->

					<div class="form-text">
					<?php echo esc_html__('Warning: this will reset all the user consent given. Use this only if you change your cookie list and you would like to ask consent again.', 'MAP_txt'); ?>
					</div>

				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->

		</div> <!-- consistent-box -->

	</div> <!-- /.col-sm-8 -->

	<div class="col-sm-4">
		<?php
			$tab = null;
			include 'inc.admin_sidebar.php';
		?>
	</div>
</div> <!-- /.row -->