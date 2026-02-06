<?php

if( !defined( 'MAP_PLUGIN_NAME' ) )
{
	exit('Not allowed.');
}

$first_class = '';
$second_class = 'd-none';
$wrap_early_content = false;

if( isset( $the_settings['pa'] ) &&
	$the_settings['pa'] == 1
)
{
	$first_class = 'd-none';
	$second_class = '';

	if( $caller != 'genericOptionsWrapper' )
	{
		$wrap_early_content = true;
	}
}

?>


<?php if( $wrap_early_content ) : ?>

	<div class="cmode_fields displayNone">

<?php endif; ?>

		<span class="translate-middle-y badge rounded-pill forbiddenWarning bg-danger  <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] ){echo 'd-none';} ?>">
			<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
		</span>
		<div class="consistent-box">
			<h4 class="mb-4">
				<i class="fa-brands fa-google"></i>
				<?php echo wp_kses_post( __( 'Google Consent Mode v2', 'MAP_txt' ) ); ?>
			</h4>

			<?php if( $caller == 'genericOptionsWrapper') : ?>

				<div class="row mb-4">
					<label for="bypass_cmode_enable_field" class="col-sm-5 col-form-label">
						<?php echo esc_html__('Consent Mode v2 Ignore', 'MAP_txt'); ?>
					</label>

					<div class="col-sm-7">
						<div class="styled_radio d-inline-flex">
							<div class="round d-flex me-4">

								<input type="hidden" name="bypass_cmode_enable_field" value="false" id="enable_cmode_v2_field_no">

								<input
									name="bypass_cmode_enable_field"
									class="hideShowInput reverseHideShow"
									data-hide-show-ref="cmode_fields"
									type="checkbox"
									value="true"
									id="bypass_cmode_enable_field"
									<?php checked( $the_settings['bypass_cmode_enable'], true ); ?>>

								<label for="bypass_cmode_enable_field" class="me-2 label-checkbox"></label>
								<label for="bypass_cmode_enable_field">
									<?php echo esc_html__('I do not use Google products and I do not need Consent Mode v2', 'MAP_txt'); ?>
								</label>
							</div>
						</div> <!-- ./ styled_radio -->

					</div> <!-- /.col-sm-6 -->
				</div> <!-- row -->


			<?php endif; ?>

			<div class="row mb-1 <?php echo esc_attr( $first_class );?>">

				<div class="col-sm-12">

					<p>
						<?php echo wp_kses_post( __( "This feature allows you to implement Google's Consent Mode v2.", 'MAP_txt' ) ); ?>

						<?php echo wp_kses_post( __( "Without this feature, you might not be able to properly use Google's ecosystem products such as Google Adsense, Google Ads, Google Analytics, Google Tag Manager, and other tools.", 'MAP_txt' ) ); ?>
					</p>
				</div>
			</div>

			<?php if( !$wrap_early_content ) : ?>

				<div class="cmode_fields displayNone">

			<?php endif; ?>

				<div class="<?php echo esc_attr( $second_class );?>">

					<!-- widget review consent show -->
					<div class="row mb-4">
						<label for="enable_cmode_v2_field" class="col-sm-5 col-form-label">
							<?php echo wp_kses_post( __( 'Enable Google Consent Mode v2', 'MAP_txt' ) ); ?>
						</label>

						<div class="col-sm-7">
							<div class="styled_radio d-inline-flex">
								<div class="round d-flex me-4">

									<input type="hidden" name="enable_cmode_v2_field" value="false" id="enable_cmode_v2_field_no">

									<input
										name="enable_cmode_v2_field"
										type="checkbox"
										value="true"
										id="enable_cmode_v2_field"
										class="hideShowInput"
										data-hide-show-ref="enable_cmode_v2_options"
										<?php checked( $the_settings['enable_cmode_v2'], true ); ?>>

									<label for="enable_cmode_v2_field" class="me-2 label-checkbox"></label>
									<?php

										$cmode_link = 'https://www.myagileprivacy.com/en/supporting-consent-mode-v2-what-it-is-and-how-to-implement-it-gdpr-compliant-with-my-agile-privacy/';

										if( $locale && $locale == 'it_IT' )
										{
											$cmode_link = 'https://www.myagileprivacy.com/supporto-alla-consent-mode-v2-cose-e-come-implementarla-a-norma-gdpr-con-my-agile-privacy';
										}
									?>

									<label for="enable_cmode_v2_field">
										<?php echo sprintf(__('Enable Google Consent Mode v2 - %1$sOnline Help%2$s', 'MAP_txt'), '<a href="' . esc_attr( $cmode_link ) . '" target="_blank">', '</a>'); ?>
									</label>
								</div>
							</div> <!-- ./ styled_radio -->

						</div> <!-- /.col-sm-6 -->
					</div> <!-- row -->

					<div class="enable_cmode_v2_options displayNone">

						<div class="row mb-4">
							<label for="cmode_v2_implementation_type_field" class="col-sm-5 col-form-label">
								<?php echo wp_kses_post( __( 'Select the type of implementation', 'MAP_txt' ) ); ?>
							</label>

							<div class="col-sm-7">

								<select
									id="cmode_v2_implementation_type_field"
									name="cmode_v2_implementation_type_field"
									class="hideShowInput form-control"
									data-hide-show-ref="cmode_v2_implementation_type_options"
									style="max-width:100%;">
									<?php

										$valid_options = array(
											'native'	  =>	array(  'label' => esc_attr( __( 'via My Agile Privacy®', 'MAP_txt' ) ),
																					'selected' => false ),
											'gtm'	      =>	array(  'label' => esc_attr( __( 'via Google Tag Manager', 'MAP_txt' ) ),
																					'selected' => false ),
										);

										$selected_value = $the_settings['cmode_v2_implementation_type'];

										if( isset( $valid_options[ $selected_value ] ) )
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

							</div> <!-- col -->
						</div> <!-- /row-->

						<div class="cmode_v2_implementation_type_options displayNone mt-4" data-value="native">
							<div class="row mb-3">
								<div class="col-12">
								<strong><?php echo wp_kses_post( __( 'Native implementation via My Agile Privacy®', 'MAP_txt' ) ); ?></strong>
								<p><?php echo wp_kses_post( __( "Select the initial configuration of the parameters necessary for the operation of Consent Mode v2. The standard configuration, as required by regulations, precedes all parameters set to 'denied'.", 'MAP_txt' ) ); ?></p>
								</div>
							</div>
							<div class="row m-0 p-0 alert">
								<label for="enable_cmode_url_passthrough_field" class="col-sm-5 col-form-label">
									<?php echo wp_kses_post( __( 'Url Passthrough', 'MAP_txt' ) ); ?><br>
									<span class="form-text">
										<?php echo wp_kses_post( __( "This option is useful if you do not want to lose the data related to the user, in case they do not immediately accept cookies but decide to do so later.", 'MAP_txt' ) ); ?>
									</span>
								</label>

								<div class="col-sm-7">
									<div class="styled_radio d-inline-flex">
										<div class="round d-flex me-4">

											<input type="hidden" name="enable_cmode_url_passthrough_field" value="false"
												id="enable_cmode_url_passthrough_field_no">

											<input
												name="enable_cmode_url_passthrough_field"
												type="checkbox"
												value="true"
												id="enable_cmode_url_passthrough_field"
												class="hideShowInput"
												data-hide-show-ref="enable_cmode_url_passthrough_options" <?php checked( $the_settings['enable_cmode_url_passthrough'], true ); ?>>
											<label for="enable_cmode_url_passthrough_field" class="me-2 label-checkbox"></label>
											<label for="enable_cmode_url_passthrough_field">
												<?php echo wp_kses_post( __( 'Enable Url Passthrough', 'MAP_txt' ) ); ?>
											</label>
										</div>
									</div> <!-- ./ styled_radio -->
								</div>

							</div> <!-- /row-->


							<div class="m-0 p-0 row alert">
								<label for="cmode_v2_gtag_ad_storage_field" class="col-sm-5 col-form-label">
									<?php echo wp_kses_post( __( 'Ad Storage', 'MAP_txt' ) ); ?><br>
									<span class="form-text">
										<?php echo wp_kses_post( __( 'Defines whether cookies related to advertising can be read or written by Google.', 'MAP_txt' ) ); ?>
									</span>
								</label>

								<div class="col-sm-7">

									<select
										id="cmode_v2_gtag_ad_storage_field"
										name="cmode_v2_gtag_ad_storage_field"
										class="form-control"
										style="max-width:100%;">
										<?php

										$valid_options = array(
											'denied'    =>  array(  'label' => esc_attr( __( 'Denied', 'MAP_txt' ) ),
																					'selected' => false ),
											'granted'   =>  array(  'label' => esc_attr( __( 'Granted', 'MAP_txt' ) ),
																					'selected' => false ),
										);

										$selected_value = $the_settings['cmode_v2_gtag_ad_storage'];

										if( isset( $valid_options[ $selected_value ] ) )
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

									<div class="suggested-value-alert d-none">

										<strong>
											<?php
												echo wp_kses_post( __( 'Suggested value for compliance:', 'MAP_txt' ) );
											?>
										</strong> denied

									</div>
								</div> <!-- col -->

							</div> <!-- /row-->

							<div class="m-0 p-0 row alert">
								<label for="cmode_v2_gtag_ad_user_data_field" class="col-sm-5 col-form-label">
									<?php echo wp_kses_post( __( 'Ad User Data', 'MAP_txt' ) ); ?><br>
									<span class="form-text">
										<?php echo wp_kses_post( __( 'Determines whether user data can be sent to Google for advertising purposes.', 'MAP_txt' ) ); ?>
									</span>
								</label>

								<div class="col-sm-7">

									<select
										id="cmode_v2_gtag_ad_user_data_field"
										name="cmode_v2_gtag_ad_user_data_field"
										class="form-control"
										style="max-width:100%;">
										<?php

										$valid_options = array(
											'denied'    =>  array(  'label' => esc_attr( __( 'Denied', 'MAP_txt' ) ),
																					'selected' => false ),
											'granted'   =>  array(  'label' => esc_attr( __( 'Granted', 'MAP_txt' ) ),
																					'selected' => false ),
										);

										$selected_value = $the_settings['cmode_v2_gtag_ad_user_data'];

										if( isset( $valid_options[ $selected_value ] ) )
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

									<div class="suggested-value-alert d-none">
										<strong>
											<?php
												echo wp_kses_post( __( 'Suggested value for compliance:', 'MAP_txt' ) );
											?>
										</strong> denied
									</div>

								</div> <!-- col -->
							</div> <!-- /row-->

							<div class="m-0 p-0 row alert">
								<label for="cmode_v2_gtag_ad_personalization_field" class="col-sm-5 col-form-label">
									<?php echo wp_kses_post( __( 'Ad Personalization', 'MAP_txt' ) ); ?><br>
									<span class="form-text">
										<?php echo wp_kses_post( __( 'Controls whether personalized advertising (for example, remarketing) can be enabled.', 'MAP_txt' ) ); ?>
									</span>
								</label>

								<div class="col-sm-7">

									<select
										id="cmode_v2_gtag_ad_personalization_field"
										name="cmode_v2_gtag_ad_personalization_field"
										class="form-control"
										style="max-width:100%;">
										<?php

										$valid_options = array(
											'denied'    =>  array(  'label' => esc_attr( __( 'Denied', 'MAP_txt' ) ),
																					'selected' => false ),
											'granted'   =>  array(  'label' => esc_attr( __( 'Granted', 'MAP_txt' ) ),
																					'selected' => false ),
										);

										$selected_value = $the_settings['cmode_v2_gtag_ad_personalization'];

										if( isset( $valid_options[ $selected_value ] ) )
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

									<div class="suggested-value-alert d-none">
										<strong>
											<?php
												echo wp_kses_post( __( 'Suggested value for compliance:', 'MAP_txt' ) );
											?>
										</strong> denied
									</div>
								</div> <!-- col -->
							</div> <!-- /row-->

							<div class="m-0 p-0 row alert">
								<label for="cmode_v2_gtag_analytics_storage_field" class="col-sm-5 col-form-label">
									<?php echo wp_kses_post( __( 'Analytics Storage', 'MAP_txt' ) ); ?><br>
									<span class="form-text">
										<?php echo wp_kses_post( __( 'Defines whether cookies associated with Google Analytics can be read or written.', 'MAP_txt' ) ); ?>
									</span>
								</label>

								<div class="col-sm-7">

									<select
										id="cmode_v2_gtag_analytics_storage_field"
										name="cmode_v2_gtag_analytics_storage_field"
										class="form-control"
										style="max-width:100%;">
										<?php

										$valid_options = array(
											'denied'    =>  array(  'label' => esc_attr( __( 'Denied', 'MAP_txt' ) ),
																					'selected' => false ),
											'granted'   =>  array(  'label' => esc_attr( __( 'Granted', 'MAP_txt' ) ),
																					'selected' => false ),
										);

										$selected_value = $the_settings['cmode_v2_gtag_analytics_storage'];

										if( isset( $valid_options[ $selected_value ] ) )
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

									<div class="suggested-value-alert d-none">
										<strong>
											<?php
												echo wp_kses_post( __( 'Suggested value for compliance:', 'MAP_txt' ) );
											?>
										</strong> denied
									</div>
								</div> <!-- col -->
							</div> <!-- /row-->

							<div class="row">
								<label for="cmode_v2_forced_off_ga4_advanced_field" class="col-sm-5 col-form-label">
									<?php echo wp_kses_post( __( 'Disable Advanced Consent Mode', 'MAP_txt' ) ); ?><br>
								</label>

								<div class="col-sm-7">
									<div class="styled_radio d-inline-flex">
										<div class="round d-flex me-4">

											<input type="hidden" name="cmode_v2_forced_off_ga4_advanced_field" value="false"
												id="cmode_v2_forced_off_ga4_advanced_field_no">

											<input
												name="cmode_v2_forced_off_ga4_advanced_field"
												type="checkbox"
												value="true"
												id="cmode_v2_forced_off_ga4_advanced_field"
												class="hideShowInput"
												data-hide-show-ref="cmode_v2_forced_off_ga4_advanced_description"
												<?php checked( $the_settings['cmode_v2_forced_off_ga4_advanced'], true ); ?>>

											<label for="cmode_v2_forced_off_ga4_advanced_field" class="me-2 label-checkbox"></label>
											<label for="cmode_v2_forced_off_ga4_advanced_field">
												<?php echo wp_kses_post( __( 'Disable Advanced Consent Mode', 'MAP_txt' ) ); ?>
											</label>
										</div>
									</div> <!-- ./ styled_radio -->
								</div>

								<p class="form-text cmode_v2_forced_off_ga4_advanced_description">
									<?php echo wp_kses_post( __( "By disabling this mode of operation, you will minimize the data sent to Google servers in case of a lack of user consent, achieving greater compliance. However, you may receive warnings from Google regarding the non-detection of Consent Mode V2.", 'MAP_txt' ) ); ?>
								</p>

							</div> <!-- /row-->
						</div>


						<div class="cmode_v2_implementation_type_options mt-4 displayNone" data-value="gtm">
							<div class="row mb-3">
								<div class="col-12">
									<strong><?php echo wp_kses_post( __( 'Configuration via Google Tag Manager', 'MAP_txt' ) ); ?></strong>
									<p class="mt-3">
										<?php
											echo sprintf(__( 'You can save the settings and continue the configuration on Google Tag Manager.<br>You can follow the setup steps in the guide we have created. %1$sClick here%2$s to go to the guide.', 'MAP_txt' ),'<a href="https://www.myagileprivacy.com/supporto-alla-consent-mode-v2-cose-e-come-implementarla-a-norma-gdpr-con-my-agile-privacy" target="_blank">','</a>');

										?>
									</p>

								</div>
							</div>
						</div>

					</div>

				</div>

			<?php if( $caller == 'genericOptionsWrapper') : ?>

				</div>

			<?php endif; ?>

		</div> <!-- consistent-box -->


<?php if( $caller != 'genericOptionsWrapper') : ?>

	</div>

<?php endif; ?>