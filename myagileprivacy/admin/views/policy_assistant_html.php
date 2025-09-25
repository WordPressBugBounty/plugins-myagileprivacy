<?php

	if( !defined( 'MAP_PLUGIN_NAME' ) )
	{
		exit('Not allowed.');
	}

	$locale = MyAgilePrivacy::get_locale();

	$contact_us_url = 'https://www.myagileprivacy.com/en/contact-us/';

	if( $locale && $locale == 'it_IT' )
	{
		$contact_us_url = 'https://www.myagileprivacy.com/contattaci/';
	}
?>

<script type="text/javascript">
	var map_settings_success_text = '<?php echo wp_kses_post( __( 'Settings updated.', 'MAP_txt' ) ); ?>';
	var map_settings_step_saved_text = '<?php echo wp_kses_post( __( 'Step saved!', 'MAP_txt' ) ); ?>';
	var map_settings_warning_text ='<?php echo wp_kses_post( __( 'Settings saved successfully, but some mandatory data is missing. Please check the required fields', 'MAP_txt' ) ); ?>';
	var map_settings_need_validation_text ='<?php echo wp_kses_post( __( 'Please fill in all required fields before proceeding.', 'MAP_txt' ) ); ?>';
	var map_settings_error_message_text = '<?php echo wp_kses_post( __( 'Unable to update Settings.', 'MAP_txt' ) ); ?>';
	var map_settings_connection_error = '<?php echo wp_kses_post( __( 'Connection error. Please try again.', 'MAP_txt' ) ); ?>';
</script>


<script type="text/javascript">
	<?php

		echo 'const regulation_config = ' . $regulation_config_encoded . ';';
	?>
</script>

<div class="wrap siteAndPoliciesSettingsWrapper" id="my_agile_privacy_backend">

	<div class="consistent-box">
		<h4 class="mb-4"><i class="fa-solid fa-scale-balanced"></i> <?php echo wp_kses_post( __( 'Policy Assistant', 'MAP_txt' ) ); ?></h2>
		<form action="admin-ajax.php" method="post" id="map_policy_assistant_form">
			<input type="hidden" name="action" value="update_admin_settings_form" id="action" />
			<input type="hidden" name="site_and_policy_settings[completion_percentage]" value="<?php echo esc_attr( $site_and_policy_settings['completion_percentage'] ); ?>" id="policy_completion_percentage" />
			<input type="hidden" name="site_and_policy_settings[last_update_timestamp]" value="<?php echo esc_attr( $site_and_policy_settings['last_update_timestamp'] ); ?>" id="last_update_timestamp" />
			<?php
				if( function_exists( 'wp_nonce_field' ) )
				{
					wp_nonce_field( 'myagileprivacy-update-' . MAP_PLUGIN_SETTINGS_FIELD );
				}
			?>
			<div class="container-fluid mt-5">
				<!-- Progress Bar -->
				<div class="wizard-progress mb-4">
					<div class="progress">
						<div class="progress-bar" role="progressbar" style="width: 20%" id="map_policy_assistant_progress-bar"></div>
					</div>
					<div class="d-flex justify-content-between mt-2">
						<small class="text-muted step-indicator active" data-step="1">
							<i class="fa-regular fa-globe"></i> <?php echo wp_kses_post( __( 'Policy Assistant', 'MAP_txt' ) ); ?>
						</small>
						<small class="text-muted step-indicator" data-step="2">
							<i class="fa-regular fa-globe"></i> <?php echo wp_kses_post( __( 'Localization', 'MAP_txt' ) ); ?>
						</small>
						<small class="text-muted step-indicator" data-step="3">
							<i class="fa-regular fa-address-card"></i> <?php echo wp_kses_post( __( 'Identity', 'MAP_txt' ) ); ?>
						</small>
						<small class="text-muted step-indicator" data-step="4">
							<i class="fa-regular fa-browser"></i> <?php echo wp_kses_post( __( 'Site Features', 'MAP_txt' ) ); ?>
						</small>
						<small class="text-muted step-indicator" data-step="5">
							<i class="fa-regular fa-share-nodes"></i> <?php echo wp_kses_post( __( 'Data Sharing', 'MAP_txt' ) ); ?>
						</small>
						<small class="text-muted step-indicator" data-step="6">
							<i class="fa-regular fa-shield"></i> <?php echo wp_kses_post( __( 'Protection Systems', 'MAP_txt' ) ); ?>
						</small>
					</div>
				</div>
				<div class="wizard-steps">
					<!-- Step 1: Introduction -->
					<div class="wizard-step active" id="step-1">

						<div class="row">
							<div class="col-12">
								<h3><?php echo wp_kses_post( __( 'Welcome!', 'MAP_txt' ) ); ?></h3>

							</div>
						</div>

						<div class="row mb-3">

							<div class="col-md-8">
								<label class="form-label">
									<?php echo wp_kses_post( __( 'With this tool, you can improve your site’s compliance by configuring options related to the site, your information, and your specific methods for processing your users’ data. Changes made here are reflected in the Cookie Policy and Privacy Policy.', 'MAP_txt' ) ); ?>
									<br>

									<?php echo wp_kses_post( __( 'You can review and update your choices at any time.', 'MAP_txt' ) ); ?>
								</label>
								<label class="form-label">
									<?php echo wp_kses_post( __( 'Use the "Next" button to proceed!', 'MAP_txt' ) ); ?>
								</label>

							</div>

							<div class="col-md-4">

								<div class="card-group pb-4">
									<div class="card rounded-3 p-0 pb-5 m-0 mx-1">
										<div class="card-header bg-transparent"><h5 class="pt-2"><?php echo wp_kses_post( __( 'What are "Policies" ?', 'MAP_txt' ) ); ?></h5></div>

										<div class="card-body">
											<p>
												<?php echo wp_kses_post( __( 'Policies are documents that clearly explain how your website handles data and what regulations apply.', 'MAP_txt' ) ); ?>
											</p>

											<p>
												<?php echo wp_kses_post( __( '<strong>Privacy Policy:</strong> tells users what personal data you collect, why and on what legal basis, who you share it with, and how users can exercise their rights.', 'MAP_txt' ) ); ?>

												<br>

												<?php echo wp_kses_post( __( '<strong>Cookie Policy:</strong> describes which cookies and similar technologies you use, for what purposes, which require consent, and how users can manage their preferences.', 'MAP_txt' ) ); ?>

											</p>

										</div>

										<img src="<?php echo plugin_dir_url( __FILE__ ) ?>../img/fox-profile.png" class="wizard-card-avatar">

									</div>

								</div>

							</div>

						</div>
					</div>

					<!-- Step 2: Localization -->
					<div class="wizard-step" id="step-2">
						<div class="row">
							<div class="col-12">
								<h3><?php echo wp_kses_post( __( 'Localization', 'MAP_txt' ) ); ?></h3>
								<p class="text-muted"><?php echo wp_kses_post( __( 'Tell us about your location and target audience.', 'MAP_txt' ) ); ?></p>
							</div>
						</div>
						<div class="row mb-3">

							<div class="col-md-8">

								<div class="row mb-3">
									<div class="col-md-3">
										<label for="base_location" class="form-label">
											<?php echo wp_kses_post( __( 'Where are you based?', 'MAP_txt' ) ); ?> <span class="">(*)</span>
										</label>
									</div>
									<div class="col-md-9">

										<?php

											$valid_options = array();

											if( isset( $available_countries['all_supported_countries'] ) )
											{
												foreach( $available_countries['all_supported_countries'] as $country_key => $country_item )
												{
													$valid_options[ $country_key ] = array(
														'label' 	=> esc_attr( $country_item ),
														'selected' 	=> false
													);
												}
											}

											$valid_options[ 'unsupported' ] = array(
												'label' 	=> esc_attr( __( 'Other - None of the above', 'MAP_txt' ) ),
												'selected' 	=> false
											);

											$selected_value = $site_and_policy_settings['base_location'];

											if( isset( $valid_options[ $selected_value ] ) )
											{
												$valid_options[ $selected_value ]['selected'] = true;
											}

										?>

										<select
											id="base_location"
											name="site_and_policy_settings[base_location]"
											class="form-control hideShowInput"
											data-hide-show-ref="unsupported_country_wrapper"
											required>

											<option value=""><?php echo wp_kses_post( __( 'Select your location', 'MAP_txt' ) ); ?></option>

											<?php

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
														<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $data['label'] );?></option>
														<?php
													}
												}

											?>
										</select>

										<div class="unsupported_country_wrapper displayNone"  data-value="unsupported">
											<p class="small">
												<?php echo wp_kses_post( __( 'Is your country not on the list?', 'MAP_txt' ) ); ?> <a href="<?php echo esc_attr( $contact_us_url ); ?>" target="blank"><?php echo wp_kses_post( __( 'Contact us for assistance.', 'MAP_txt' ) ); ?></a>.
											</p>
										</div>

									</div>
								</div>


								<div class="row mb-3">

									<div class="col-md-3">
										<label for="customer_location" class="form-label">
											<?php echo wp_kses_post( __( 'Where are your customers located?', 'MAP_txt' ) ); ?> <span class="">(*)</span>
										</label>
									</div>
									<div class="col-md-9">

										<?php

											$valid_options = array(
												'my_country'				=>	array(  'label' => esc_attr( __( 'Only in my country', 'MAP_txt' ) ),
																						'selected' => false ),
												'select_countries'			=>	array(  'label' => esc_attr( __( 'Select countries', 'MAP_txt' ) ),
																						'selected' => false ),

											);

											$selected_value = $site_and_policy_settings['customer_location'];

											if( isset( $valid_options[ $selected_value ] ) )
											{
												$valid_options[ $selected_value ]['selected'] = true;
											}

										?>


										<select
											id="customer_location"
											name="site_and_policy_settings[customer_location]"
											class="form-select hideShowInput"
											data-hide-show-ref="map_customer_countries_wrapper"
											required>

											<option value=""><?php echo wp_kses_post( __( 'Select your customer location', 'MAP_txt' ) ); ?></option>

											<?php

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

									</div>
								</div>

								<div class="row mb-3 map_customer_countries_wrapper displayNone" data-value="select_countries">

									<div class="col-md-3">
										<label class="form-label">
											<?php echo wp_kses_post( __( 'Select countries', 'MAP_txt' ) ); ?> <span class="">(*)</span>
										</label>
									</div>
									<div class="col-md-9">
										<div class="styled_radio d-flex">
											<div class="round d-flex me-4">

												<input type="hidden" name="site_and_policy_settings[customer_area_eu]" value="false" id="customer_area_eu_no">

												<input
													type="checkbox"
													id="customer_area_eu"
													name="site_and_policy_settings[customer_area_eu]"
													value="true"
													data-checkbox-group="map_customer_countries_wrapper"
													<?php checked( $site_and_policy_settings['customer_area_eu'], true ); ?>
													/>
												<label for="customer_area_eu" class="me-2 label-checkbox"></label>
												<label for="customer_area_eu"><?php echo wp_kses_post( __( 'Any European Union country', 'MAP_txt' ) ); ?></label>
											</div>
										</div>


										<span class="translate-lower-middle-y badge rounded-pill forbiddenWarning bg-danger <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1){echo 'd-none';} ?>">
											<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
										</span>

										<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
											<div class="round d-flex me-4">

												<input type="hidden" name="site_and_policy_settings[customer_area_gb]" value="false" id="customer_area_gb_no">

												<input
													type="checkbox"
													id="customer_area_gb"
													name="site_and_policy_settings[customer_area_gb]"
													value="true"
													data-checkbox-group="map_customer_countries_wrapper"
													<?php checked( $site_and_policy_settings['customer_area_gb'], true ); ?>
													/>

												<label for="customer_area_gb" class="me-2 label-checkbox"></label>
												<label for="customer_area_gb"><?php echo wp_kses_post( __( 'United Kingdom', 'MAP_txt' ) ); ?></label>
											</div>
										</div>


										<div class="ms-0 mb-2">
											<label ><?php echo wp_kses_post( __( 'United States', 'MAP_txt' ) ); ?></label>
										</div>

										<div class="ms-5">

											<div class="row">

												<div class="col-md-3">

													<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">

														<div class="round d-flex me-4">

															<input type="hidden" name="site_and_policy_settings[customer_area_california]" value="false" id="customer_area_california_no">

															<input
																type="checkbox"
																id="customer_area_california"
																name="site_and_policy_settings[customer_area_california]"
																value="true"
																data-checkbox-group="map_customer_countries_wrapper"
																<?php checked( $site_and_policy_settings['customer_area_california'], true ); ?>
																/>

															<label for="customer_area_california" class="me-2 label-checkbox"></label>
															<label for="customer_area_california"><?php echo wp_kses_post( __( 'California', 'MAP_txt' ) ); ?></label>
														</div>
													</div>


													<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
														<div class="round d-flex me-4">

															<input type="hidden" name="site_and_policy_settings[customer_area_colorado]" value="false" id="customer_area_colorado_no">

															<input
																type="checkbox"
																id="customer_area_colorado"
																name="site_and_policy_settings[customer_area_colorado]"
																value="true"
																data-checkbox-group="map_customer_countries_wrapper"
																<?php checked( $site_and_policy_settings['customer_area_colorado'], true ); ?>
																/>

															<label for="customer_area_colorado" class="me-2 label-checkbox"></label>
															<label for="customer_area_colorado"><?php echo wp_kses_post( __( 'Colorado', 'MAP_txt' ) ); ?></label>
														</div>
													</div>

													<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
														<div class="round d-flex me-4">

															<input type="hidden" name="site_and_policy_settings[customer_area_connecticut]" value="false" id="customer_area_connecticut_no">

															<input
																type="checkbox"
																id="customer_area_connecticut"
																name="site_and_policy_settings[customer_area_connecticut]"
																value="true"
																data-checkbox-group="map_customer_countries_wrapper"
																<?php checked( $site_and_policy_settings['customer_area_connecticut'], true ); ?>
																/>

															<label for="customer_area_connecticut" class="me-2 label-checkbox"></label>
															<label for="customer_area_connecticut"><?php echo wp_kses_post( __( 'Connecticut', 'MAP_txt' ) ); ?></label>
														</div>
													</div>

													<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
														<div class="round d-flex me-4">

															<input type="hidden" name="site_and_policy_settings[customer_area_delaware]" value="false" id="customer_area_delaware_no">

															<input
																type="checkbox"
																id="customer_area_delaware"
																name="site_and_policy_settings[customer_area_delaware]"
																value="true"
																data-checkbox-group="map_customer_countries_wrapper"
																<?php checked( $site_and_policy_settings['customer_area_delaware'], true ); ?>
																/>

															<label for="customer_area_delaware" class="me-2 label-checkbox"></label>
															<label for="customer_area_delaware"><?php echo wp_kses_post( __( 'Delaware', 'MAP_txt' ) ); ?></label>
														</div>
													</div>

													<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
														<div class="round d-flex me-4">

															<input type="hidden" name="site_and_policy_settings[customer_area_minnesota]" value="false" id="customer_area_minnesota_no">

															<input
																type="checkbox"
																id="customer_area_minnesota"
																name="site_and_policy_settings[customer_area_minnesota]"
																value="true"
																data-checkbox-group="map_customer_countries_wrapper"
																<?php checked( $site_and_policy_settings['customer_area_minnesota'], true ); ?>
																/>

															<label for="customer_area_minnesota" class="me-2 label-checkbox"></label>
															<label for="customer_area_minnesota"><?php echo wp_kses_post( __( 'Minnesota', 'MAP_txt' ) ); ?></label>
														</div>
													</div>

													<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
														<div class="round d-flex me-4">

															<input type="hidden" name="site_and_policy_settings[customer_area_montana]" value="false" id="customer_area_montana_no">

															<input
																type="checkbox"
																id="customer_area_montana"
																name="site_and_policy_settings[customer_area_montana]"
																value="true"
																data-checkbox-group="map_customer_countries_wrapper"
																<?php checked( $site_and_policy_settings['customer_area_montana'], true ); ?>
																/>

															<label for="customer_area_montana" class="me-2 label-checkbox"></label>
															<label for="customer_area_montana"><?php echo wp_kses_post( __( 'Montana', 'MAP_txt' ) ); ?></label>
														</div>
													</div>

													<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
														<div class="round d-flex me-4">

															<input type="hidden" name="site_and_policy_settings[customer_area_nebraska]" value="false" id="customer_area_nebraska_no">

															<input
																type="checkbox"
																id="customer_area_nebraska"
																name="site_and_policy_settings[customer_area_nebraska]"
																value="true"
																data-checkbox-group="map_customer_countries_wrapper"
																<?php checked( $site_and_policy_settings['customer_area_nebraska'], true ); ?>
																/>

															<label for="customer_area_nebraska" class="me-2 label-checkbox"></label>
															<label for="customer_area_nebraska"><?php echo wp_kses_post( __( 'Nebraska', 'MAP_txt' ) ); ?></label>
														</div>
													</div>

													<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
														<div class="round d-flex me-4">

															<input type="hidden" name="site_and_policy_settings[customer_area_nevada]" value="false" id="customer_area_nevada_no">

															<input
																type="checkbox"
																id="customer_area_nevada"
																name="site_and_policy_settings[customer_area_nevada]"
																value="true"
																data-checkbox-group="map_customer_countries_wrapper"
																<?php checked( $site_and_policy_settings['customer_area_nevada'], true ); ?>
																/>

															<label for="customer_area_nevada" class="me-2 label-checkbox"></label>
															<label for="customer_area_nevada"><?php echo wp_kses_post( __( 'Nevada', 'MAP_txt' ) ); ?></label>
														</div>
													</div>

												</div>


												<div class="col-md-3">

													<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
														<div class="round d-flex me-4">

															<input type="hidden" name="site_and_policy_settings[customer_area_new_hampshire]" value="false" id="customer_area_new_hampshire_no">

															<input
																type="checkbox"
																id="customer_area_new_hampshire"
																name="site_and_policy_settings[customer_area_new_hampshire]"
																value="true"
																data-checkbox-group="map_customer_countries_wrapper"
																<?php checked( $site_and_policy_settings['customer_area_new_hampshire'], true ); ?>
																/>

															<label for="customer_area_new_hampshire" class="me-2 label-checkbox"></label>
															<label for="customer_area_new_hampshire"><?php echo wp_kses_post( __( 'New Hampshire', 'MAP_txt' ) ); ?></label>
														</div>
													</div>

													<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
														<div class="round d-flex me-4">

															<input type="hidden" name="site_and_policy_settings[customer_area_new_jersey]" value="false" id="customer_area_new_jersey_no">

															<input
																type="checkbox"
																id="customer_area_new_jersey"
																name="site_and_policy_settings[customer_area_new_jersey]"
																value="true"
																data-checkbox-group="map_customer_countries_wrapper"
																<?php checked( $site_and_policy_settings['customer_area_new_jersey'], true ); ?>
																/>

															<label for="customer_area_new_jersey" class="me-2 label-checkbox"></label>
															<label for="customer_area_new_jersey"><?php echo wp_kses_post( __( 'New Jersey', 'MAP_txt' ) ); ?></label>
														</div>
													</div>

													<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
														<div class="round d-flex me-4">

															<input type="hidden" name="site_and_policy_settings[customer_area_oregon]" value="false" id="customer_area_oregon_no">

															<input
																type="checkbox"
																id="customer_area_oregon"
																name="site_and_policy_settings[customer_area_oregon]"
																value="true"
																data-checkbox-group="map_customer_countries_wrapper"
																<?php checked( $site_and_policy_settings['customer_area_oregon'], true ); ?>
																/>

															<label for="customer_area_oregon" class="me-2 label-checkbox"></label>
															<label for="customer_area_oregon"><?php echo wp_kses_post( __( 'Oregon', 'MAP_txt' ) ); ?></label>
														</div>
													</div>

													<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
														<div class="round d-flex me-4">

															<input type="hidden" name="site_and_policy_settings[customer_area_tennessee]" value="false" id="customer_area_tennessee_no">

															<input
																type="checkbox"
																id="customer_area_tennessee"
																name="site_and_policy_settings[customer_area_tennessee]"
																value="true"
																data-checkbox-group="map_customer_countries_wrapper"
																<?php checked( $site_and_policy_settings['customer_area_tennessee'], true ); ?>
																/>

															<label for="customer_area_tennessee" class="me-2 label-checkbox"></label>
															<label for="customer_area_tennessee"><?php echo wp_kses_post( __( 'Tennessee', 'MAP_txt' ) ); ?></label>
														</div>
													</div>

													<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
														<div class="round d-flex me-4">

															<input type="hidden" name="site_and_policy_settings[customer_area_texas]" value="false" id="customer_area_texas_no">

															<input
																type="checkbox"
																id="customer_area_texas"
																name="site_and_policy_settings[customer_area_texas]"
																value="true"
																data-checkbox-group="map_customer_countries_wrapper"
																<?php checked( $site_and_policy_settings['customer_area_texas'], true ); ?>
																/>

															<label for="customer_area_texas" class="me-2 label-checkbox"></label>
															<label for="customer_area_texas"><?php echo wp_kses_post( __( 'Texas', 'MAP_txt' ) ); ?></label>
														</div>
													</div>

													<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
														<div class="round d-flex me-4">

															<input type="hidden" name="site_and_policy_settings[customer_area_utah]" value="false" id="customer_area_utah_no">

															<input
																type="checkbox"
																id="customer_area_utah"
																name="site_and_policy_settings[customer_area_utah]"
																value="true"
																data-checkbox-group="map_customer_countries_wrapper"
																<?php checked( $site_and_policy_settings['customer_area_utah'], true ); ?>
																/>

															<label for="customer_area_utah" class="me-2 label-checkbox"></label>
															<label for="customer_area_utah"><?php echo wp_kses_post( __( 'Utah', 'MAP_txt' ) ); ?></label>
														</div>
													</div>

													<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
														<div class="round d-flex me-4">

															<input type="hidden" name="site_and_policy_settings[customer_area_virginia]" value="false" id="customer_area_virginia_no">

															<input
																type="checkbox"
																id="customer_area_virginia"
																name="site_and_policy_settings[customer_area_virginia]"
																value="true"
																data-checkbox-group="map_customer_countries_wrapper"
																<?php checked( $site_and_policy_settings['customer_area_virginia'], true ); ?>
																/>

															<label for="customer_area_virginia" class="me-2 label-checkbox"></label>
															<label for="customer_area_virginia"><?php echo wp_kses_post( __( 'Virginia', 'MAP_txt' ) ); ?></label>
														</div>
													</div>

												</div>

											</div>
										</div>

										<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
											<div class="round d-flex me-4">

												<input type="hidden" name="site_and_policy_settings[customer_area_ch]" value="false" id="customer_area_ch_no">

												<input
													type="checkbox"
													id="customer_area_ch"
													name="site_and_policy_settings[customer_area_ch]"
													value="true"
													data-checkbox-group="map_customer_countries_wrapper"
													<?php checked( $site_and_policy_settings['customer_area_ch'], true ); ?>
													/>

												<label for="customer_area_ch" class="me-2 label-checkbox"></label>
												<label for="customer_area_ch"><?php echo wp_kses_post( __( 'Switzerland', 'MAP_txt' ) ); ?></label>
											</div>
										</div>

										<div class="styled_radio _d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
											<div class="round d-flex me-4">

												<input type="hidden" name="site_and_policy_settings[customer_area_ca]" value="false" id="customer_area_ca_no">

												<input
													type="checkbox"
													id="customer_area_ca"
													name="site_and_policy_settings[customer_area_ca]"
													value="true"
													data-checkbox-group="map_customer_countries_wrapper"
													<?php checked( $site_and_policy_settings['customer_area_ca'], true ); ?>
													/>

												<label for="customer_area_ca" class="me-2 label-checkbox"></label>
												<label for="customer_area_ca"><?php echo wp_kses_post( __( 'Canada', 'MAP_txt' ) ); ?></label>
											</div>
										</div>

										<div class="styled_radio _d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
											<div class="round d-flex me-4">

												<input type="hidden" name="site_and_policy_settings[customer_area_br]" value="false" id="customer_area_br_no">

												<input
													type="checkbox"
													id="customer_area_br"
													name="site_and_policy_settings[customer_area_br]"
													value="true"
													data-checkbox-group="map_customer_countries_wrapper"
													<?php checked( $site_and_policy_settings['customer_area_br'], true ); ?>
													/>

												<label for="customer_area_br" class="me-2 label-checkbox"></label>
												<label for="customer_area_br"><?php echo wp_kses_post( __( 'Brazil', 'MAP_txt' ) ); ?></label>
											</div>
										</div>

										<p
											class="map_group_message_warning alert-warning displayNone text-center py-3"
											data-checkbox-group-message="map_customer_countries_wrapper">
											<?php echo wp_kses_post( __( 'Warning: at least one country selection is required.', 'MAP_txt' ) ); ?>
										</p>
									</div>

								</div>

							</div>

							<div class="col-md-4">

								<div class="card-group">
									<div class="card rounded-3 p-0 pb-5 m-0 mx-1">
										<div class="card-header bg-transparent">

											<h5 class="pt-2"><?php echo wp_kses_post( __( 'What is "Localization"?', 'MAP_txt' ) ); ?></h5>
										</div>

										<div class="card-body pb-0">

											<p>
												<?php echo wp_kses_post( __( 'The privacy and cookie regulations that apply to your site depend on where your company is established and where the users you target are located.', 'MAP_txt' ) ); ?>
											</p>

										</div>

										<div class="card-header bg-transparent">

											<h5 class="pt-0">

												<?php echo wp_kses_post( __( 'What to do in this step?', 'MAP_txt' ) ); ?>

											</h5>
										</div>

										<div class="card-body">
											<p>
												<?php echo wp_kses_post( __( '-Select all relevant jurisdictions', 'MAP_txt' ) ); ?><br>
												<?php echo wp_kses_post( __( '-If you operate globally, include your users’ countries', 'MAP_txt' ) ); ?><br>
												<?php echo wp_kses_post( __( '-Finally, select the regulations to adopt based on the system’s suggestions', 'MAP_txt' ) ); ?><br>
											</p>

											<p class="mb-0">
												Attenzione: alcuni regolamenti prevedono regole specifiche riguardo alle categorie speciali di dati personali.


												<?php echo wp_kses_post( __( 'Note: some regulations set specific rules regarding special categories of personal data.', 'MAP_txt' ) ); ?>
											</p>
											<div class="mt-0">
												<p>
													<a href="#" class="map-sensitive-data-toggle"><?php echo wp_kses_post( __( 'What are the special categories of personal data?', 'MAP_txt' ) ); ?></a>
												</p>
													<ul class="map-sensitive-data-examples mt-2" style="display: none;">
														<li><strong><?php echo wp_kses_post( __( 'Health data', 'MAP_txt' ) ); ?></strong> <?php echo wp_kses_post( __( '(e.g., diagnoses, medical conditions, treatments and medications, test results/reports, disability)', 'MAP_txt' ) ); ?></li>
														<li><strong><?php echo wp_kses_post( __( 'Political opinions', 'MAP_txt' ) ); ?></strong> <?php echo wp_kses_post( __( '(e.g., membership, support or donations to parties/movements)', 'MAP_txt' ) ); ?></li>
														<li><strong><?php echo wp_kses_post( __( 'Religious or philosophical beliefs', 'MAP_txt' ) ); ?></strong> <?php echo wp_kses_post( __( '(e.g., membership in a religious denomination or ethical belief organization)', 'MAP_txt' ) ); ?></li>
														<li><strong><?php echo wp_kses_post( __( 'Racial or ethnic origin', 'MAP_txt' ) ); ?></strong> <?php echo wp_kses_post( __( '(e.g., stated ethnicity or racial background)', 'MAP_txt' ) ); ?></li>
														<li><strong><?php echo wp_kses_post( __( 'Biometric data processed for the purpose of unique identification', 'MAP_txt' ) ); ?></strong> <?php echo wp_kses_post( __( '(e.g., fingerprints, facial recognition templates, iris/retina scans, voice templates)', 'MAP_txt' ) ); ?></li>
														<li><strong><?php echo wp_kses_post( __( 'Genetic data', 'MAP_txt' ) ); ?></strong> <?php echo wp_kses_post( __( '(e.g., DNA profiles, results of genetic testing)', 'MAP_txt' ) ); ?></li>
														<li><strong><?php echo wp_kses_post( __( 'Sex life or sexual orientation', 'MAP_txt' ) ); ?></strong> <?php echo wp_kses_post( __( '(e.g., information on relationships or practices, self-declared orientation, memberships revealing orientation)', 'MAP_txt' ) ); ?></li>
														<li><strong><?php echo wp_kses_post( __( 'Trade union membership', 'MAP_txt' ) ); ?></strong> <?php echo wp_kses_post( __( '(e.g., union enrollment, payroll dues/withholdings)', 'MAP_txt' ) ); ?></li>
													</ul>
											</div>

										</div>

										<img src="<?php echo plugin_dir_url( __FILE__ ) ?>../img/fox-profile.png" class="wizard-card-avatar">

									</div>

								</div>

							</div>

						</div>

						<div class="row">
							<div class="col-md-12">
								<label class="form-label mb-0">
									<?php echo wp_kses_post( __( 'Applicable policies', 'MAP_txt' ) ); ?> <span class="">(*)</span>
								</label>
							</div>
						</div>
						<div class="row mb-3">
							<div class="col-md-12">

								<!-- CARDS -->
								<div class="row g-4 pb-4">

									<!-- GDPR -->
									<div class="col-12 col-md-4 regulation_wrapper displayNone" data-regulation="regulation_gdpr_like">
										<div class="card h-100 policy-card" data-clickable="true">
											<div class="card-body">
												<div class="d-flex align-items-start justify-content-between">
													<h3 class="h5 card-title"><?php echo wp_kses_post( __( 'GDPR (European Union)', 'MAP_txt' ) ); ?></h3>
													<img src="<?php echo plugin_dir_url( __FILE__ ) ?>../img/flag-ue.png" class="policy-card-flag">
												</div>
												<p class="mb-2">
													<?php echo wp_kses_post( __( 'Applies to your processing of personal data when:', 'MAP_txt' ) ); ?>
												</p>
												<ul class="mb-0">
													<li>
														<?php echo wp_kses_post( __( 'you are established in the EU/EEA and the processing is carried out in the context of the activities of your establishment, regardless of where the processing takes place or where the data subjects are located; or', 'MAP_txt' ) ); ?>
													</li>
													<li>
														<?php echo wp_kses_post( __( 'you are not established in the EU/EEA but you offer goods or services (including free of charge) to individuals located in the EU/EEA; or', 'MAP_txt' ) ); ?>
													</li>
													<li>
														<?php echo wp_kses_post( __( 'you are not established in the EU/EEA but you monitor the behavior of individuals to the extent that such behavior takes place within the EU/EEA (e.g., tracking, profiling, behavioral advertising, geolocation).', 'MAP_txt' ) ); ?>
													</li>
												</ul>
											</div>
											<div class="card-footer">

												<div class="styled_radio d-flex">
													<div class="round d-flex me-4">

														<input type="hidden" name="site_and_policy_settings[regulation_gdpr_like]" value="false" id="regulation_gdpr_like_no">

														<input
															class="form-check-input"
															type="checkbox"
															id="regulation_gdpr_like"
															name="site_and_policy_settings[regulation_gdpr_like]"
															value="true"
															data-checkbox-group="map_regulation"
															<?php checked( $site_and_policy_settings['regulation_gdpr_like'], true ); ?>
															/>
														<label for="regulation_gdpr_like" class="me-2 label-checkbox"></label>
														<label for="regulation_gdpr_like"><?php echo wp_kses_post( __( 'Yes - this applies to my processing. Add it to my policies.', 'MAP_txt' ) ); ?></label>
													</div>
												</div>

											</div>
										</div>
									</div>

									<!-- UK GDPR -->
									<div class="col-12 col-md-4 regulation_wrapper displayNone" data-regulation="regulation_gdpr_gb">
										<div class="card h-100 policy-card" <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1 ) echo 'data-clickable="true"'; ?>>
											<div class="card-body">
												<div class="d-flex align-items-start justify-content-between">
													<h3 class="h5 card-title"><?php echo wp_kses_post( __( 'UK GDPR (United Kingdom)', 'MAP_txt' ) ); ?></h3>
													<img src="<?php echo plugin_dir_url( __FILE__ ) ?>../img/flag-uk.png" class="policy-card-flag">
												</div>
												<p class="mb-2"><?php echo wp_kses_post( __( 'Applies to your processing of personal data when:', 'MAP_txt' ) ); ?></p>
												<ul class="mb-0">
													<li>
														<?php echo wp_kses_post( __( 'you are established in the United Kingdom and the processing is carried out in the context of the activities of your establishment, regardless of where the processing takes place or where the data subjects are located; or', 'MAP_txt' ) ); ?>
													</li>
													<li>
														<?php echo wp_kses_post( __( 'you are not established in the United Kingdom but you offer goods or services (including free of charge) to individuals located in the United Kingdom; or', 'MAP_txt' ) ); ?>
													</li>
													<li>
														<?php echo wp_kses_post( __( 'you are not established in the United Kingdom but you monitor the behavior of individuals to the extent that such behavior takes place in the United Kingdom (e.g., tracking, profiling, geolocation).', 'MAP_txt' ) ); ?>
													</li>
												</ul>
											</div>
											<div class="card-footer">

												<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 0){echo 'd-none';} ?>">
													<div class="round d-flex me-4">

														<input type="hidden" name="site_and_policy_settings[regulation_gdpr_gb]" value="false" id="regulation_gdpr_gb_no">

														<input
															class="form-check-input"
															type="checkbox"
															id="regulation_gdpr_gb"
															name="site_and_policy_settings[regulation_gdpr_gb]"
															value="true"
															data-checkbox-group="map_regulation"
															<?php checked( $site_and_policy_settings['regulation_gdpr_gb'], true ); ?>
															/>
														<label for="regulation_gdpr_gb" class="me-2 label-checkbox"></label>
														<label for="regulation_gdpr_gb"><?php echo wp_kses_post( __( 'Yes - this applies to my processing. Add it to my policies.', 'MAP_txt' ) ); ?></label>
													</div>
												</div>

												<span class="translate-lower-middle-y forbiddenWarning badge rounded-pill bg-danger <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1){echo 'd-none';} ?>">
													<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
												</span>

											</div>
										</div>
									</div>

									<!-- CCPA/CPRA -->
									<div class="col-12 col-md-4 regulation_wrapper displayNone" data-regulation="regulation_ccpa">
										<div class="card h-100 policy-card" <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1 ) echo 'data-clickable="true"'; ?>>
											<div class="card-body">
												<div class="d-flex align-items-start justify-content-between">
													<h3 class="h5 card-title"><?php echo wp_kses_post( __( 'CCPA / CPRA (California)', 'MAP_txt' ) ); ?></h3>
													<img src="<?php echo plugin_dir_url( __FILE__ ) ?>../img/flag-us.png" class="policy-card-flag">
												</div>
												<p class="mb-2">
													<?php echo wp_kses_post( __( 'Applies to for-profit businesses that do business in California, collect or have collected personal information of California residents, and determine the purposes and means of processing, when they meet at least one of the following conditions:', 'MAP_txt' ) ); ?>
												</p>
												<ul class="mb-0">
													<li>
														<?php echo wp_kses_post( __( 'annual gross revenues exceeding USD 25 million (in the preceding calendar year); or', 'MAP_txt' ) ); ?>
													</li>
													<li>
														<?php echo wp_kses_post( __( 'buy, sell, or share, in a year, the personal information of 100,000 or more California consumers or households; or', 'MAP_txt' ) ); ?>
													</li>
													<li>
														<?php echo wp_kses_post( __( 'derive 50% or more of annual revenues from selling or sharing personal information.', 'MAP_txt' ) ); ?>
													</li>
												</ul>
											</div>
											<div class="card-footer">

												<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 0){echo 'd-none';} ?>">
													<div class="round d-flex me-4">

														<input type="hidden" name="site_and_policy_settings[regulation_ccpa]" value="false" id="regulation_ccpa_no">

														<input
															class="form-check-input"
															type="checkbox"
															id="regulation_ccpa"
															name="site_and_policy_settings[regulation_ccpa]"
															value="true"
															data-checkbox-group="map_regulation"
															<?php checked( $site_and_policy_settings['regulation_ccpa'], true ); ?>
															/>
														<label for="regulation_ccpa" class="me-2 label-checkbox"></label>
														<label for="regulation_ccpa"><?php echo wp_kses_post( __( 'Yes - this applies to my processing. Add it to my policies.', 'MAP_txt' ) ); ?></label>
													</div>
												</div>

												<span class="translate-lower-middle-y forbiddenWarning badge rounded-pill bg-danger <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1){echo 'd-none';} ?>">
													<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
												</span>

											</div>

										</div>
									</div>

									<!-- CPA -->
									<div class="col-12 col-md-4 regulation_wrapper displayNone" data-regulation="regulation_cpa">
										<div class="card h-100 policy-card" <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1 ) echo 'data-clickable="true"'; ?>>
											<div class="card-body">
												<div class="d-flex align-items-start justify-content-between">
													<h3 class="h5 card-title"><?php echo wp_kses_post( __( 'CPA (Colorado Privacy Act)', 'MAP_txt' ) ); ?></h3>
													<img src="<?php echo plugin_dir_url( __FILE__ ) ?>../img/flag-us.png" class="policy-card-flag">
												</div>
												<p class="mb-2">
													<?php echo wp_kses_post( __( 'Applies to those who do business in Colorado or intentionally target products/services to Colorado residents and, during a calendar year, meet at least one of these thresholds:', 'MAP_txt' ) ); ?>
												</p>
												<ul class="mb-0">
													<li>
														<?php echo wp_kses_post( __( 'control or process the personal data of at least 100,000 consumers; or', 'MAP_txt' ) ); ?>
													</li>
													<li>
														<?php echo wp_kses_post( __( 'control or process the personal data of at least 25,000 consumers and derive revenue, or receive a discount on the price of goods or services, from the sale of personal data.', 'MAP_txt' ) ); ?>
													</li>
												</ul>
											</div>
											<div class="card-footer">

												<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 0){echo 'd-none';} ?>">
													<div class="round d-flex me-4">

														<input type="hidden" name="site_and_policy_settings[regulation_cpa]" value="false" id="regulation_cpa_no">

														<input
															class="form-check-input"
															type="checkbox"
															id="regulation_cpa"
															name="site_and_policy_settings[regulation_cpa]"
															value="true"
															data-checkbox-group="map_regulation"
															<?php checked( $site_and_policy_settings['regulation_cpa'], true ); ?>
															/>
														<label for="regulation_cpa" class="me-2 label-checkbox"></label>
														<label for="regulation_cpa"><?php echo wp_kses_post( __( 'Yes - this applies to my processing. Add it to my policies.', 'MAP_txt' ) ); ?></label>
													</div>
												</div>


												<span class="translate-lower-middle-y forbiddenWarning badge rounded-pill bg-danger <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1){echo 'd-none';} ?>">
													<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
												</span>

											</div>
										</div>
									</div>

									<!-- CTDPA -->
									<div class="col-12 col-md-4 regulation_wrapper displayNone" data-regulation="regulation_ctdpa">
										<div class="card h-100 policy-card" <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1 ) echo 'data-clickable="true"'; ?>>
											<div class="card-body">
												<div class="d-flex align-items-start justify-content-between">
													<h3 class="h5 card-title"><?php echo wp_kses_post( __( 'CTDPA (Connecticut Data Privacy Act)', 'MAP_txt' ) ); ?></h3>
													<img src="<?php echo plugin_dir_url( __FILE__ ) ?>../img/flag-us.png" class="policy-card-flag">
												</div>
												<p class="mb-2">
													<?php echo wp_kses_post( __( 'Applies to those who do business in Connecticut or target products/services to Connecticut residents and, in the preceding calendar year, meet at least one of these thresholds:', 'MAP_txt' ) ); ?>
												</p>
												<ul class="mb-0">
													<li>
														<?php echo wp_kses_post( __( 'control or process the personal data of at least 100,000 consumers (excluding personal data processed solely to complete a payment transaction); or', 'MAP_txt' ) ); ?>
													</li>
													<li>
														<?php echo wp_kses_post( __( 'control or process the personal data of at least 25,000 consumers and derive over 25% of gross revenue from the sale of personal data.', 'MAP_txt' ) ); ?>
													</li>
												</ul>
											</div>
											<div class="card-footer">

												<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 0){echo 'd-none';} ?>">
													<div class="round d-flex me-4">

														<input type="hidden" name="site_and_policy_settings[regulation_ctdpa]" value="false" id="regulation_ctdpa_no">

														<input
															class="form-check-input"
															type="checkbox"
															id="regulation_ctdpa"
															name="site_and_policy_settings[regulation_ctdpa]"
															value="true"
															data-checkbox-group="map_regulation"
															<?php checked( $site_and_policy_settings['regulation_ctdpa'], true ); ?>
															/>
														<label for="regulation_ctdpa" class="me-2 label-checkbox"></label>
														<label for="regulation_ctdpa"><?php echo wp_kses_post( __( 'Yes - this applies to my processing. Add it to my policies.', 'MAP_txt' ) ); ?></label>
													</div>
												</div>


												<span class="translate-lower-middle-y forbiddenWarning badge rounded-pill bg-danger <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1){echo 'd-none';} ?>">
													<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
												</span>

											</div>

										</div>
									</div>

									<!-- DPDPA -->
									<div class="col-12 col-md-4 regulation_wrapper displayNone" data-regulation="regulation_dpdpa">
										<div class="card h-100 policy-card" <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1 ) echo 'data-clickable="true"'; ?>>
											<div class="card-body">
												<div class="d-flex align-items-start justify-content-between">
													<h3 class="h5 card-title"><?php echo wp_kses_post( __( 'DPDPA (Delaware Personal Data Privacy Act)', 'MAP_txt' ) ); ?></h3>
													<img src="<?php echo plugin_dir_url( __FILE__ ) ?>../img/flag-us.png" class="policy-card-flag">
												</div>
												<p class="mb-2">
													<?php echo wp_kses_post( __( 'Applies to those who do business in Delaware or target products/services to Delaware residents and, in the previous calendar year:', 'MAP_txt' ) ); ?>
												</p>
												<ul class="mb-0">
													<li>
														<?php echo wp_kses_post( __( 'control or process the personal data of at least 35,000 consumers (excluding data processed solely to complete a payment); or', 'MAP_txt' ) ); ?>
													</li>
													<li>
														<?php echo wp_kses_post( __( 'control or process the personal data of at least 10,000 consumers and derive more than 20% of gross revenue from the sale of personal data.', 'MAP_txt' ) ); ?>
													</li>
												</ul>
											</div>
											<div class="card-footer">

												<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 0){echo 'd-none';} ?>">
													<div class="round d-flex me-4">

														<input type="hidden" name="site_and_policy_settings[regulation_dpdpa]" value="false" id="regulation_dpdpa_no">

														<input
															class="form-check-input"
															type="checkbox"
															id="regulation_dpdpa"
															name="site_and_policy_settings[regulation_dpdpa]"
															value="true"
															data-checkbox-group="map_regulation"
															<?php checked( $site_and_policy_settings['regulation_dpdpa'], true ); ?>
															/>
														<label for="regulation_dpdpa" class="me-2 label-checkbox"></label>
														<label for="regulation_dpdpa"><?php echo wp_kses_post( __( 'Yes - this applies to my processing. Add it to my policies.', 'MAP_txt' ) ); ?></label>
													</div>
												</div>


												<span class="translate-lower-middle-y forbiddenWarning badge rounded-pill bg-danger <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1){echo 'd-none';} ?>">
													<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
												</span>

											</div>

										</div>
									</div>

									<!-- MCDPA -->
									<div class="col-12 col-md-4 regulation_wrapper displayNone" data-regulation="regulation_mcdpa">
										<div class="card h-100 policy-card" <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1 ) echo 'data-clickable="true"'; ?>>
											<div class="card-body">
												<div class="d-flex align-items-start justify-content-between">
													<h3 class="h5 card-title"><?php echo wp_kses_post( __( 'MCDPA (Minnesota Consumer Data Privacy Act)', 'MAP_txt' ) ); ?></h3>
													<img src="<?php echo plugin_dir_url( __FILE__ ) ?>../img/flag-us.png" class="policy-card-flag">
												</div>
												<p class="mb-2">
													<?php echo wp_kses_post( __( 'Applies to those who do business in Minnesota or target products/services to Minnesota residents and, during a calendar year:', 'MAP_txt' ) ); ?>
												</p>
												<ul class="mb-0">
													<li>
														<?php echo wp_kses_post( __( 'control or process the personal data of at least 100,000 consumers (excluding data processed solely to complete a payment); or', 'MAP_txt' ) ); ?>
													</li>
													<li>
														<?php echo wp_kses_post( __( 'control or process the personal data of at least 25,000 consumers and derive more than 25% of gross revenue from the sale of personal data.', 'MAP_txt' ) ); ?>
													</li>
												</ul>
											</div>
											<div class="card-footer">

												<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 0){echo 'd-none';} ?>">
													<div class="round d-flex me-4">

														<input type="hidden" name="site_and_policy_settings[regulation_mcdpa]" value="false" id="regulation_mcdpa_no">

														<input
															class="form-check-input"
															type="checkbox"
															id="regulation_mcdpa"
															name="site_and_policy_settings[regulation_mcdpa]"
															value="true"
															data-checkbox-group="map_regulation"
															<?php checked( $site_and_policy_settings['regulation_mcdpa'], true ); ?>
															/>
														<label for="regulation_mcdpa" class="me-2 label-checkbox"></label>
														<label for="regulation_mcdpa"><?php echo wp_kses_post( __( 'Yes - this applies to my processing. Add it to my policies.', 'MAP_txt' ) ); ?></label>
													</div>
												</div>


												<span class="translate-lower-middle-y forbiddenWarning badge rounded-pill bg-danger <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1){echo 'd-none';} ?>">
													<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
												</span>

											</div>

										</div>
									</div>

									<!-- MTCDPA -->
									<div class="col-12 col-md-4 regulation_wrapper displayNone" data-regulation="regulation_mtcdpa">
										<div class="card h-100 policy-card" <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1 ) echo 'data-clickable="true"'; ?>>
											<div class="card-body">
												<div class="d-flex align-items-start justify-content-between">
													<h3 class="h5 card-title"><?php echo wp_kses_post( __( 'MTCDPA (Montana Consumer Data Privacy Act)', 'MAP_txt' ) ); ?></h3>
													<img src="<?php echo plugin_dir_url( __FILE__ ) ?>../img/flag-us.png" class="policy-card-flag">
												</div>
												<p class="mb-2">
													<?php echo wp_kses_post( __( 'Applies to those who do business in Montana or target products/services to Montana residents and, during a calendar year:', 'MAP_txt' ) ); ?>
												</p>
												<ul class="mb-0">
													<li>
														<?php echo wp_kses_post( __( 'control or process the personal data of at least 50,000 consumers (excluding data processed solely to complete a payment); or', 'MAP_txt' ) ); ?>
													</li>
													<li>
														<?php echo wp_kses_post( __( 'control or process the personal data of at least 25,000 consumers and derive at least 25% of gross revenue from the sale of personal data.', 'MAP_txt' ) ); ?>
													</li>
												</ul>
											</div>
											<div class="card-footer">

												<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 0){echo 'd-none';} ?>">
													<div class="round d-flex me-4">

														<input type="hidden" name="site_and_policy_settings[regulation_mtcdpa]" value="false" id="regulation_mtcdpa_no">

														<input
															class="form-check-input"
															type="checkbox"
															id="regulation_mtcdpa"
															name="site_and_policy_settings[regulation_mtcdpa]"
															value="true"
															data-checkbox-group="map_regulation"
															<?php checked( $site_and_policy_settings['regulation_mtcdpa'], true ); ?>
															/>
														<label for="regulation_mtcdpa" class="me-2 label-checkbox"></label>
														<label for="regulation_mtcdpa"><?php echo wp_kses_post( __( 'Yes - this applies to my processing. Add it to my policies.', 'MAP_txt' ) ); ?></label>
													</div>
												</div>


												<span class="translate-lower-middle-y forbiddenWarning badge rounded-pill bg-danger <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1){echo 'd-none';} ?>">
													<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
												</span>

											</div>

										</div>
									</div>


									<!-- NDPA -->
									<div class="col-12 col-md-4 regulation_wrapper displayNone" data-regulation="regulation_ndpa">
										<div class="card h-100 policy-card" <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1 ) echo 'data-clickable="true"'; ?>>
											<div class="card-body">
												<div class="d-flex align-items-start justify-content-between">
													<h3 class="h5 card-title"><?php echo wp_kses_post( __( 'NDPA (Nebraska Data Privacy Act)', 'MAP_txt' ) ); ?></h3>
													<img src="<?php echo plugin_dir_url( __FILE__ ) ?>../img/flag-us.png" class="policy-card-flag">
												</div>
												<p class="mb-2">
													<?php echo wp_kses_post( __( 'Applies to those who do business in Nebraska or target products/services to Nebraska residents and process or sell personal data.', 'MAP_txt' ) ); ?>
												</p>
												<ul class="mb-0">
													<li>
														<?php echo wp_kses_post( __( 'No numerical thresholds.', 'MAP_txt' ) ); ?>
													</li>
													<li>
														<?php echo wp_kses_post( __( 'Entities that fall within the SBA (Small Business Administration) definition of small business are largely exempt from the law, but may not sell special categories of personal data without consent.', 'MAP_txt' ) ); ?>
													</li>
												</ul>
											</div>
											<div class="card-footer">

												<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 0){echo 'd-none';} ?>">
													<div class="round d-flex me-4">

														<input type="hidden" name="site_and_policy_settings[regulation_ndpa]" value="false" id="regulation_ndpa_no">

														<input
															class="form-check-input"
															type="checkbox"
															id="regulation_ndpa"
															name="site_and_policy_settings[regulation_ndpa]"
															value="true"
															data-checkbox-group="map_regulation"
															<?php checked( $site_and_policy_settings['regulation_ndpa'], true ); ?>
															/>
														<label for="regulation_ndpa" class="me-2 label-checkbox"></label>
														<label for="regulation_ndpa"><?php echo wp_kses_post( __( 'Yes - this applies to my processing. Add it to my policies.', 'MAP_txt' ) ); ?></label>
													</div>
												</div>


												<span class="translate-lower-middle-y forbiddenWarning badge rounded-pill bg-danger <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1){echo 'd-none';} ?>">
													<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
												</span>

											</div>

										</div>
									</div>

									<!-- NEVADA -->
									<div class="col-12 col-md-4 regulation_wrapper displayNone" data-regulation="regulation_nevada">
										<div class="card h-100 policy-card" <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1 ) echo 'data-clickable="true"'; ?>>
											<div class="card-body">
												<div class="d-flex align-items-start justify-content-between">
													<h3 class="h5 card-title"><?php echo wp_kses_post( __( 'NRS 603A (Nevada Privacy Law)', 'MAP_txt' ) ); ?></h3>
													<img src="<?php echo plugin_dir_url( __FILE__ ) ?>../img/flag-us.png" class="policy-card-flag">
												</div>
												<p class="mb-2">
													<?php echo wp_kses_post( __( 'Applies to operators of websites/online services that do business in Nevada, collect personal information from Nevada consumers, and sell that information.', 'MAP_txt' ) ); ?>
												</p>
												<ul class="mb-0">
													<li>
														<?php echo wp_kses_post( __( 'No numerical thresholds.', 'MAP_txt' ) ); ?>
													</li>
												</ul>
											</div>
											<div class="card-footer">

												<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 0){echo 'd-none';} ?>">
													<div class="round d-flex me-4">

														<input type="hidden" name="site_and_policy_settings[regulation_nevada]" value="false" id="regulation_nevada_no">

														<input
															class="form-check-input"
															type="checkbox"
															id="regulation_nevada"
															name="site_and_policy_settings[regulation_nevada]"
															value="true"
															data-checkbox-group="map_regulation"
															<?php checked( $site_and_policy_settings['regulation_nevada'], true ); ?>
															/>
														<label for="regulation_nevada" class="me-2 label-checkbox"></label>
														<label for="regulation_nevada"><?php echo wp_kses_post( __( 'Yes - this applies to my processing. Add it to my policies.', 'MAP_txt' ) ); ?></label>
													</div>
												</div>


												<span class="translate-lower-middle-y forbiddenWarning badge rounded-pill bg-danger <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1){echo 'd-none';} ?>">
													<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
												</span>

											</div>

										</div>
									</div>

									<!-- NHPA -->
									<div class="col-12 col-md-4 regulation_wrapper displayNone" data-regulation="regulation_nhpa">
										<div class="card h-100 policy-card" <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1 ) echo 'data-clickable="true"'; ?>>
											<div class="card-body">
												<div class="d-flex align-items-start justify-content-between">
													<h3 class="h5 card-title"><?php echo wp_kses_post( __( 'NHPA (New Hampshire Privacy Act)', 'MAP_txt' ) ); ?></h3>
													<img src="<?php echo plugin_dir_url( __FILE__ ) ?>../img/flag-us.png" class="policy-card-flag">
												</div>
												<p class="mb-2">
													<?php echo wp_kses_post( __( 'Applies to those who do business in New Hampshire or target products/services to New Hampshire residents and, in the previous calendar year:', 'MAP_txt' ) ); ?>
												</p>
												<ul class="mb-0">
													<li>
														<?php echo wp_kses_post( __( 'control or process the personal data of at least 35,000 consumers (excluding data processed solely to complete a payment); or', 'MAP_txt' ) ); ?>
													</li>
													<li>
														<?php echo wp_kses_post( __( 'control or process the personal data of at least 10,000 consumers and derive at least 25% of gross revenue from the sale of personal data.', 'MAP_txt' ) ); ?>
													</li>
												</ul>
											</div>
											<div class="card-footer">

												<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 0){echo 'd-none';} ?>">
													<div class="round d-flex me-4">

														<input type="hidden" name="site_and_policy_settings[regulation_nhpa]" value="false" id="regulation_nhpa_no">

														<input
															class="form-check-input"
															type="checkbox"
															id="regulation_nhpa"
															name="site_and_policy_settings[regulation_nhpa]"
															value="true"
															data-checkbox-group="map_regulation"
															<?php checked( $site_and_policy_settings['regulation_nhpa'], true ); ?>
															/>
														<label for="regulation_nhpa" class="me-2 label-checkbox"></label>
														<label for="regulation_nhpa"><?php echo wp_kses_post( __( 'Yes - this applies to my processing. Add it to my policies.', 'MAP_txt' ) ); ?></label>
													</div>
												</div>


												<span class="translate-lower-middle-y forbiddenWarning badge rounded-pill bg-danger <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1){echo 'd-none';} ?>">
													<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
												</span>

											</div>

										</div>
									</div>

									<!-- NJDPA -->
									<div class="col-12 col-md-4 regulation_wrapper displayNone" data-regulation="regulation_njdpa">
										<div class="card h-100 policy-card" <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1 ) echo 'data-clickable="true"'; ?>>
											<div class="card-body">
												<div class="d-flex align-items-start justify-content-between">
													<h3 class="h5 card-title"><?php echo wp_kses_post( __( 'NJDPA (New Jersey Data Privacy Act)', 'MAP_txt' ) ); ?></h3>
													<img src="<?php echo plugin_dir_url( __FILE__ ) ?>../img/flag-us.png" class="policy-card-flag">
												</div>
												<p class="mb-2">
													<?php echo wp_kses_post( __( 'Applies to those who do business in New Jersey or target products/services to New Jersey residents and, in the previous calendar year:', 'MAP_txt' ) ); ?>
												</p>
												<ul class="mb-0">
													<li>
														<?php echo wp_kses_post( __( 'control or process the personal data of at least 100,000 consumers (excluding data processed solely to complete a payment); or', 'MAP_txt' ) ); ?>
													</li>
													<li>
														<?php echo wp_kses_post( __( 'control or process the personal data of at least 25,000 consumers and derive at least 25% of gross revenue from the sale of personal data.', 'MAP_txt' ) ); ?>
													</li>
												</ul>
											</div>
											<div class="card-footer">

												<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 0){echo 'd-none';} ?>">
													<div class="round d-flex me-4">

														<input type="hidden" name="site_and_policy_settings[regulation_njdpa]" value="false" id="regulation_njdpa_no">

														<input
															class="form-check-input"
															type="checkbox"
															id="regulation_njdpa"
															name="site_and_policy_settings[regulation_njdpa]"
															value="true"
															data-checkbox-group="map_regulation"
															<?php checked( $site_and_policy_settings['regulation_njdpa'], true ); ?>
															/>
														<label for="regulation_njdpa" class="me-2 label-checkbox"></label>
														<label for="regulation_njdpa"><?php echo wp_kses_post( __( 'Yes - this applies to my processing. Add it to my policies.', 'MAP_txt' ) ); ?></label>
													</div>
												</div>


												<span class="translate-lower-middle-y forbiddenWarning badge rounded-pill bg-danger <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1){echo 'd-none';} ?>">
													<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
												</span>

											</div>

										</div>
									</div>

									<!-- OCPA -->
									<div class="col-12 col-md-4 regulation_wrapper displayNone" data-regulation="regulation_ocpa">
										<div class="card h-100 policy-card" <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1 ) echo 'data-clickable="true"'; ?>>
											<div class="card-body">
												<div class="d-flex align-items-start justify-content-between">
													<h3 class="h5 card-title"><?php echo wp_kses_post( __( 'OCPA (Oregon Consumer Privacy Act)', 'MAP_txt' ) ); ?></h3>
													<img src="<?php echo plugin_dir_url( __FILE__ ) ?>../img/flag-us.png" class="policy-card-flag">
												</div>
												<p class="mb-2">
													<?php echo wp_kses_post( __( 'Applies to those who do business in Oregon or target products/services to Oregon residents and, during a calendar year:', 'MAP_txt' ) ); ?>
												</p>
												<ul class="mb-0">
													<li>
														<?php echo wp_kses_post( __( 'control or process the personal data of at least 100,000 consumers (excluding data processed solely to complete a payment); or', 'MAP_txt' ) ); ?>
													</li>
													<li>
														<?php echo wp_kses_post( __( 'control or process the personal data of at least 25,000 consumers and derive more than 25% of gross revenue from the sale of personal data.', 'MAP_txt' ) ); ?>
													</li>
												</ul>
											</div>
											<div class="card-footer">

												<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 0){echo 'd-none';} ?>">
													<div class="round d-flex me-4">

														<input type="hidden" name="site_and_policy_settings[regulation_ocpa]" value="false" id="regulation_ocpa_no">

														<input
															class="form-check-input"
															type="checkbox"
															id="regulation_ocpa"
															name="site_and_policy_settings[regulation_ocpa]"
															value="true"
															data-checkbox-group="map_regulation"
															<?php checked( $site_and_policy_settings['regulation_ocpa'], true ); ?>
															/>
														<label for="regulation_ocpa" class="me-2 label-checkbox"></label>
														<label for="regulation_ocpa"><?php echo wp_kses_post( __( 'Yes - this applies to my processing. Add it to my policies.', 'MAP_txt' ) ); ?></label>
													</div>
												</div>


												<span class="translate-lower-middle-y forbiddenWarning badge rounded-pill bg-danger <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1){echo 'd-none';} ?>">
													<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
												</span>

											</div>

										</div>
									</div>

									<!-- TIPA -->
									<div class="col-12 col-md-4 regulation_wrapper displayNone" data-regulation="regulation_tipa">
										<div class="card h-100 policy-card" <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1 ) echo 'data-clickable="true"'; ?>>
											<div class="card-body">
												<div class="d-flex align-items-start justify-content-between">
													<h3 class="h5 card-title"><?php echo wp_kses_post( __( 'TIPA (Tennessee Information Protection Act)', 'MAP_txt' ) ); ?></h3>
													<img src="<?php echo plugin_dir_url( __FILE__ ) ?>../img/flag-us.png" class="policy-card-flag">
												</div>
												<p class="mb-2">
													<?php echo wp_kses_post( __( 'Applies to those who do business in Tennessee or target products/services to Tennessee residents and meet all of the following:', 'MAP_txt' ) ); ?>
												</p>
												<ul class="mb-0">
													<li>
														<?php echo wp_kses_post( __( 'annual gross revenues of at least USD 25 million; and', 'MAP_txt' ) ); ?>
													</li>
													<li>
														<?php echo wp_kses_post( __( 'during a calendar year, control or process the personal data of at least 175,000 consumers (excluding data processed solely to complete a payment); or control or process the personal data of at least 25,000 consumers and derive at least 50% of gross revenue from the sale of personal data.', 'MAP_txt' ) ); ?>
													</li>
												</ul>
											</div>
											<div class="card-footer">

												<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 0){echo 'd-none';} ?>">
													<div class="round d-flex me-4">

														<input type="hidden" name="site_and_policy_settings[regulation_tipa]" value="false" id="regulation_tipa_no">

														<input
															class="form-check-input"
															type="checkbox"
															id="regulation_tipa"
															name="site_and_policy_settings[regulation_tipa]"
															value="true"
															data-checkbox-group="map_regulation"
															<?php checked( $site_and_policy_settings['regulation_tipa'], true ); ?>
															/>
														<label for="regulation_tipa" class="me-2 label-checkbox"></label>
														<label for="regulation_tipa"><?php echo wp_kses_post( __( 'Yes - this applies to my processing. Add it to my policies.', 'MAP_txt' ) ); ?></label>
													</div>
												</div>


												<span class="translate-lower-middle-y forbiddenWarning badge rounded-pill bg-danger <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1){echo 'd-none';} ?>">
													<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
												</span>

											</div>

										</div>
									</div>

									<!-- TDPSA -->
									<div class="col-12 col-md-4 regulation_wrapper displayNone" data-regulation="regulation_tdpsa">
										<div class="card h-100 policy-card" <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1 ) echo 'data-clickable="true"'; ?>>
											<div class="card-body">
												<div class="d-flex align-items-start justify-content-between">
													<h3 class="h5 card-title"><?php echo wp_kses_post( __( 'TDPSA (Texas Data Privacy and Security Act)', 'MAP_txt' ) ); ?></h3>
													<img src="<?php echo plugin_dir_url( __FILE__ ) ?>../img/flag-us.png" class="policy-card-flag">
												</div>
												<p class="mb-2">
													<?php echo wp_kses_post( __( 'Applies to those who do business in Texas or target products/services to Texas residents and process or sell personal data.', 'MAP_txt' ) ); ?>
												</p>

												<p class="mb-0">
													<?php echo wp_kses_post( __( 'No numerical thresholds. Entities that fall within the SBA (Small Business Administration) definition of small business are largely exempt from the law, but may not sell special categories of personal data without consent.', 'MAP_txt' ) ); ?>
												</p>
											</div>
											<div class="card-footer">

												<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 0){echo 'd-none';} ?>">
													<div class="round d-flex me-4">

														<input type="hidden" name="site_and_policy_settings[regulation_tdpsa]" value="false" id="regulation_tdpsa_no">

														<input
															class="form-check-input"
															type="checkbox"
															id="regulation_tdpsa"
															name="site_and_policy_settings[regulation_tdpsa]"
															value="true"
															data-checkbox-group="map_regulation"
															<?php checked( $site_and_policy_settings['regulation_tdpsa'], true ); ?>
															/>
														<label for="regulation_tdpsa" class="me-2 label-checkbox"></label>
														<label for="regulation_tdpsa"><?php echo wp_kses_post( __( 'Yes - this applies to my processing. Add it to my policies.', 'MAP_txt' ) ); ?></label>
													</div>
												</div>


												<span class="translate-lower-middle-y forbiddenWarning badge rounded-pill bg-danger <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1){echo 'd-none';} ?>">
													<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
												</span>

											</div>

										</div>
									</div>

									<!-- UCPA -->
									<div class="col-12 col-md-4 regulation_wrapper displayNone" data-regulation="regulation_ucpa">
										<div class="card h-100 policy-card" <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1 ) echo 'data-clickable="true"'; ?>>
											<div class="card-body">
												<div class="d-flex align-items-start justify-content-between">
													<h3 class="h5 card-title"><?php echo wp_kses_post( __( 'UCPA (Utah Consumer Privacy Act)', 'MAP_txt' ) ); ?></h3>
													<img src="<?php echo plugin_dir_url( __FILE__ ) ?>../img/flag-us.png" class="policy-card-flag">
												</div>
												<p class="mb-2">
													<?php echo wp_kses_post( __( 'Applies to those who do business in Utah or target products/services to Utah residents and satisfy all of the following conditions:', 'MAP_txt' ) ); ?>
												</p>
												<ul class="mb-0">
													<li>
														<?php echo wp_kses_post( __( 'have annual gross revenues of at least USD 25 million; and', 'MAP_txt' ) ); ?>
													</li>
													<li>
														<?php echo wp_kses_post( __( 'during a calendar year, control or process the personal data of at least 100,000 consumers; or control or process the personal data of at least 25,000 consumers and derive over 50% of gross revenue from the sale of personal data.', 'MAP_txt' ) ); ?>
													</li>
												</ul>
											</div>
											<div class="card-footer">

												<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 0){echo 'd-none';} ?>">
													<div class="round d-flex me-4">

														<input type="hidden" name="site_and_policy_settings[regulation_ucpa]" value="false" id="regulation_ucpa_no">

														<input
															class="form-check-input"
															type="checkbox"
															id="regulation_ucpa"
															name="site_and_policy_settings[regulation_ucpa]"
															value="true"
															data-checkbox-group="map_regulation"
															<?php checked( $site_and_policy_settings['regulation_ucpa'], true ); ?>
															/>
														<label for="regulation_ucpa" class="me-2 label-checkbox"></label>
														<label for="regulation_ucpa"><?php echo wp_kses_post( __( 'Yes - this applies to my processing. Add it to my policies.', 'MAP_txt' ) ); ?></label>
													</div>
												</div>

												<span class="translate-lower-middle-y forbiddenWarning badge rounded-pill bg-danger <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1){echo 'd-none';} ?>">
													<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
												</span>

											</div>
										</div>
									</div>


									<!-- VCDPA -->
									<div class="col-12 col-md-4 regulation_wrapper displayNone" data-regulation="regulation_vcdpa">
										<div class="card h-100 policy-card" <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1 ) echo 'data-clickable="true"'; ?>>
											<div class="card-body">
												<div class="d-flex align-items-start justify-content-between">
													<h3 class="h5 card-title"><?php echo wp_kses_post( __( 'VCDPA (Virginia Consumer Data Protection Act)', 'MAP_txt' ) ); ?></h3>
													<img src="<?php echo plugin_dir_url( __FILE__ ) ?>../img/flag-us.png" class="policy-card-flag">
												</div>
												<p class="mb-2">
													<?php echo wp_kses_post( __( 'Applies to those who do business in Virginia or target products/services to Virginia residents and, in a calendar year:', 'MAP_txt' ) ); ?>
												</p>
												<ul class="mb-0">
													<li>
														<?php echo wp_kses_post( __( 'control or process the personal data of at least 100,000 consumers; or', 'MAP_txt' ) ); ?>
													</li>
													<li>
														<?php echo wp_kses_post( __( 'control or process the personal data of at least 25,000 consumers and derive over 50% of gross revenue from the sale of personal data.', 'MAP_txt' ) ); ?>
													</li>
												</ul>
											</div>
											<div class="card-footer">

												<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 0){echo 'd-none';} ?>">
													<div class="round d-flex me-4">

														<input type="hidden" name="site_and_policy_settings[regulation_vcdpa]" value="false" id="regulation_vcdpa_no">

														<input
															class="form-check-input"
															type="checkbox"
															id="regulation_vcdpa"
															name="site_and_policy_settings[regulation_vcdpa]"
															value="true"
															data-checkbox-group="map_regulation"
															<?php checked( $site_and_policy_settings['regulation_vcdpa'], true ); ?>
															/>
														<label for="regulation_vcdpa" class="me-2 label-checkbox"></label>
														<label for="regulation_vcdpa"><?php echo wp_kses_post( __( 'Yes - this applies to my processing. Add it to my policies.', 'MAP_txt' ) ); ?></label>
													</div>
												</div>


												<span class="translate-lower-middle-y forbiddenWarning badge rounded-pill bg-danger <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1){echo 'd-none';} ?>">
													<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
												</span>


											</div>
										</div>
									</div>






									<!-- LPD -->
									<div class="col-12 col-md-4 regulation_wrapper displayNone" data-regulation="regulation_lpd">
										<div class="card h-100 policy-card" <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1 ) echo 'data-clickable="true"'; ?>>
											<div class="card-body">
												<div class="d-flex align-items-start justify-content-between">
													<h3 class="h5 card-title"><?php echo wp_kses_post( __( 'nLPD / nFADP (Switzerland - new Federal Act on Data Protection)', 'MAP_txt' ) ); ?></h3>
													<img src="<?php echo plugin_dir_url( __FILE__ ) ?>../img/flag-ch.png" class="policy-card-flag">
												</div>
												<p class="mb-2"><?php echo wp_kses_post( __( 'Applies to private individuals and federal bodies that process personal data of natural persons (cantonal authorities are governed by cantonal law).', 'MAP_txt' ) ); ?></p>
												<p class="mb-0"><?php echo wp_kses_post( __( 'It also applies if the processing takes place abroad when it has effects in Switzerland, for example because you offer goods or services to people in Switzerland or monitor their behavior.', 'MAP_txt' ) ); ?></p>
											</div>
											<div class="card-footer">

												<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 0){echo 'd-none';} ?>">
													<div class="round d-flex me-4">

														<input type="hidden" name="site_and_policy_settings[regulation_lpd]" value="false" id="regulation_lpd_no">

														<input
															class="form-check-input"
															type="checkbox"
															id="regulation_lpd"
															name="site_and_policy_settings[regulation_lpd]"
															value="true"
															data-checkbox-group="map_regulation"
															<?php checked( $site_and_policy_settings['regulation_lpd'], true ); ?>
															/>
														<label for="regulation_lpd" class="me-2 label-checkbox"></label>
														<label for="regulation_lpd"><?php echo wp_kses_post( __( 'Yes - this applies to my processing. Add it to my policies.', 'MAP_txt' ) ); ?></label>
													</div>
												</div>


												<span class="translate-lower-middle-y forbiddenWarning badge rounded-pill bg-danger <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1){echo 'd-none';} ?>">
													<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
												</span>

											</div>
										</div>
									</div>


									<!-- PIPEDA -->
									<div class="col-12 col-md-4 regulation_wrapper displayNone" data-regulation="regulation_pipeda">
										<div class="card h-100 policy-card" <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1 ) echo 'data-clickable="true"'; ?>>
											<div class="card-body">
												<div class="d-flex align-items-start justify-content-between">
													<h3 class="h5 card-title"><?php echo wp_kses_post( __( 'PIPEDA (Canada - federal private‑sector law)', 'MAP_txt' ) ); ?></h3>
													<img src="<?php echo plugin_dir_url( __FILE__ ) ?>../img/flag-ca.png" class="policy-card-flag">
												</div>
												<p class="mb-2">
													<?php echo wp_kses_post( __( 'Applies to private‑sector organizations that, in the course of commercial activities, collect, use, or disclose personal information in Canada (including interprovincial or international transfers). ', 'MAP_txt' ) ); ?>
												</p>
												<p class="mb-0">
													<?php echo wp_kses_post( __( 'It also applies to employers that are federal works, undertakings or businesses with respect to the personal information of their employees.', 'MAP_txt' ) ); ?>
												</p>
											</div>
											<div class="card-footer">

												<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 0){echo 'd-none';} ?>">
													<div class="round d-flex me-4">

														<input type="hidden" name="site_and_policy_settings[regulation_pipeda]" value="false" id="regulation_pipeda_no">

														<input
															class="form-check-input"
															type="checkbox"
															id="regulation_pipeda"
															name="site_and_policy_settings[regulation_pipeda]"
															value="true"
															data-checkbox-group="map_regulation"
															<?php checked( $site_and_policy_settings['regulation_pipeda'], true ); ?>
															/>
														<label for="regulation_pipeda" class="me-2 label-checkbox"></label>
														<label for="regulation_pipeda"><?php echo wp_kses_post( __( 'Yes - this applies to my processing. Add it to my policies.', 'MAP_txt' ) ); ?></label>
													</div>
												</div>


												<span class="translate-lower-middle-y forbiddenWarning badge rounded-pill bg-danger <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1){echo 'd-none';} ?>">
													<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
												</span>


											</div>

										</div>
									</div>

									<!-- LGPD -->
									<div class="col-12 col-md-4 regulation_wrapper displayNone" data-regulation="regulation_lgpd">
										<div class="card h-100 policy-card" <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1 ) echo 'data-clickable="true"'; ?>>
											<div class="card-body">
												<div class="d-flex align-items-start justify-content-between">
													<h3 class="h5 card-title"><?php echo wp_kses_post( __( 'LGPD (Brazil - General Data Protection Law)', 'MAP_txt' ) ); ?></h3>
													<img src="<?php echo plugin_dir_url( __FILE__ ) ?>../img/flag-br.png" class="policy-card-flag">
												</div>
												<p class="mb-2">
													<?php echo wp_kses_post( __( 'Applies to any processing operation carried out by natural persons or legal entities, under public or private law, regardless of the controller/processor’s location or the location of the data, when at least one of these conditions is met:', 'MAP_txt' ) ); ?>
												</p>
												<ul class="mb-0">
													<li>
														<?php echo wp_kses_post( __( 'the processing is carried out within Brazilian territory; or', 'MAP_txt' ) ); ?>
													</li>
													<li>
														<?php echo wp_kses_post( __( 'the offering or provision of goods or services is directed to individuals located in Brazil; or', 'MAP_txt' ) ); ?>
													</li>
													<li>
														<?php echo wp_kses_post( __( 'the processing concerns data of individuals located in Brazil; or', 'MAP_txt' ) ); ?>
													</li>
													<li>
														<?php echo wp_kses_post( __( 'the personal data were collected in Brazil.', 'MAP_txt' ) ); ?>
													</li>

												</ul>
											</div>
											<div class="card-footer">

												<div class="styled_radio d-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 0){echo 'd-none';} ?>">
													<div class="round d-flex me-4">

														<input type="hidden" name="site_and_policy_settings[regulation_lgpd]" value="false" id="regulation_lgpd_no">

														<input
															class="form-check-input"
															type="checkbox"
															id="regulation_lgpd"
															name="site_and_policy_settings[regulation_lgpd]"
															value="true"
															data-checkbox-group="map_regulation"
															<?php checked( $site_and_policy_settings['regulation_lgpd'], true ); ?>
															/>
														<label for="regulation_lgpd" class="me-2 label-checkbox"></label>
														<label for="regulation_lgpd"><?php echo wp_kses_post( __( 'Yes - this applies to my processing. Add it to my policies.', 'MAP_txt' ) ); ?></label>
													</div>
												</div>

												<span class="translate-lower-middle-y forbiddenWarning badge rounded-pill bg-danger <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1){echo 'd-none';} ?>">
													<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
												</span>

											</div>
										</div>
									</div>

									<p
										class="map_group_message_warning alert-warning displayNone text-center py-3 mt-5"
										data-checkbox-group-message="map_regulation">
										<?php echo wp_kses_post( __( 'Warning: at least one regulation selection is required.', 'MAP_txt' ) ); ?>
									</p>

								</div>

							</div>
						</div>

					</div>


					<!-- Step 3: Identity -->
					<div class="wizard-step" id="step-3">
						<div class="row">
							<div class="col-12">
								<h3><?php echo wp_kses_post( __( 'Identity', 'MAP_txt' ) ); ?></h3>
								<p class="text-muted"><?php echo wp_kses_post( __( 'Tell us about your information.', 'MAP_txt' ) ); ?></p>
							</div>
						</div>
						<div class="row mb-3">


							<div class="col-md-8">

								<div class="row mb-3">

									<div class="col-md-3">
										<label for="identity_name" class="form-label">
											<?php echo wp_kses_post( __( 'Company Name / Site Holder', 'MAP_txt' ) ); ?> <span class="">(*)</span>
										</label>
									</div>
									<div class="col-md-9">
										<input
											type="text"
											id="identity_name"
											name="site_and_policy_settings[identity_name]"
											class="form-control"
											value="<?php echo esc_attr( stripslashes( $site_and_policy_settings['identity_name'] ) ); ?>"
											required />
									</div>

								</div>

								<div class="row mb-3">
									<div class="col-md-3">
										<label for="identity_address" class="form-label">
											<?php echo wp_kses_post( __( 'Company Address', 'MAP_txt' ) ); ?>
										</label>
									</div>
									<div class="col-md-9">

										<input
											type="text"
											id="identity_address"
											name="site_and_policy_settings[identity_address]"
											class="form-control"
											value="<?php echo esc_attr( stripslashes( $site_and_policy_settings['identity_address'] ) ); ?>"
											/>

									</div>
								</div>
								<div class="row mb-3">
									<div class="col-md-3">
										<label for="identity_vat_id" class="form-label">
											<?php echo wp_kses_post( __( 'Vat Id', 'MAP_txt' ) ); ?>
										</label>
									</div>
									<div class="col-md-9">

										<input
											type="text"
											id="identity_vat_id"
											name="site_and_policy_settings[identity_vat_id]"
											class="form-control"
											value="<?php echo esc_attr( stripslashes( $site_and_policy_settings['identity_vat_id'] ) ); ?>"
											/>
									</div>
								</div>
								<div class="row mb-3">
									<div class="col-md-3">
										<label for="identity_email" class="form-label">
											<?php echo wp_kses_post( __( 'Company E-mail', 'MAP_txt' ) ); ?> <span class="">(*)</span>
										</label>
									</div>
									<div class="col-md-9">

										<input
											type="text"
											id="identity_email"
											name="site_and_policy_settings[identity_email]"
											class="form-control"
											value="<?php echo esc_attr( stripslashes( $site_and_policy_settings['identity_email'] ) ); ?>"
											required/>
									</div>
								</div>

								<div class="row mb-3">
									<div class="col-md-3">
										<label class="form-label">
											<?php echo wp_kses_post( __( 'I have a DPO (Data Protection Officer)', 'MAP_txt' ) ); ?>
										</label>
									</div>
									<div class="col-md-9">

										<span class="translate-lower-middle-y forbiddenWarning badge rounded-pill bg-danger <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1){echo 'd-none';} ?>">
											<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
										</span>

										<div class="styled_radio d-inline-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea';} ?>">
											<div class="round d-flex me-4">

												<input type="hidden" name="site_and_policy_settings[display_dpo]" value="false" id="display_dpo_no">

												<input
													type="checkbox"
													id="display_dpo"
													name="site_and_policy_settings[display_dpo]"
													class="hideShowInput"
													data-hide-show-ref="map_dpo_fields_wrapper"
													value="true"
													<?php checked( $site_and_policy_settings['display_dpo'], true ); ?>
													/>
												<label for="display_dpo" class="me-2 label-checkbox"></label>
												<label for="display_dpo">
													<?php echo wp_kses_post( __( 'Yes, I have a DPO', 'MAP_txt' ) ); ?>
												</label>
											</div>
										</div>

									</div>
									<div class="col-md-9 mt-3 map_dpo_fields_wrapper displayNone">

										<div class="row mb-2">
											<div class="col-md-4">
												<label for="dpo_email" class="form-label">
													<?php echo wp_kses_post( __( 'DPO Email', 'MAP_txt' ) ); ?> <span class="">(*)</span>
												</label>
											</div>
											<div class="col-md-8">

												<input
													type="text"
													id="dpo_email"
													name="site_and_policy_settings[dpo_email]"
													class="form-control"
													value="<?php echo esc_attr( stripslashes( $site_and_policy_settings['dpo_email'] ) );?>"
													/>

											</div>
										</div>

										<div class="row mb-2">
											<div class="col-md-4">
												<label for="dpo_name" class="form-label">
													<?php echo wp_kses_post( __( 'DPO Name / Company name', 'MAP_txt' ) ); ?>
												</label>
											</div>
											<div class="col-md-8">

												<input
													type="text"
													id="dpo_name"
													name="site_and_policy_settings[dpo_name]"
													class="form-control"
													value="<?php echo esc_attr( stripslashes( $site_and_policy_settings['dpo_name'] ) );?>"
													/>

											</div>
										</div>
										<div class="row mb-2">
											<div class="col-md-4">
												<label for="dpo_address" class="form-label">
													<?php echo wp_kses_post( __( 'DPO Address', 'MAP_txt' ) ); ?>
												</label>
											</div>
											<div class="col-md-8">

												<input
													type="text"
													id="dpo_address"
													name="site_and_policy_settings[dpo_address]"
													class="form-control"
													value="<?php echo esc_attr( stripslashes( $site_and_policy_settings['dpo_address'] ) );?>"
													/>

											</div>
										</div>

									</div>
								</div>

							</div>


							<div class="col-md-4">

								<div class="card-group">
									<div class="card rounded-3 p-0 pb-5 m-0 mx-1">
										<div class="card-header bg-transparent">

											<h5 class="pt-2">

												<?php echo wp_kses_post( __( 'Who is the Data Controller and what information is needed?', 'MAP_txt' ) ); ?>

											</h5>
										</div>

										<div class="card-body pb-0">

											<p>
												<?php echo wp_kses_post( __( 'Enter the details of the entity that determines the purposes and means of processing: legal or personal name, full address, contact email for users/privacy matters, tax details and VAT number. This information will appear in the policies and will be the point of reference for user requests.', 'MAP_txt' ) ); ?>
											</p>

										</div>

										<div class="card-header bg-transparent">

											<h5 class="pt-0">

												<?php echo wp_kses_post( __( 'What is a DPO?', 'MAP_txt' ) ); ?>

											</h5>
										</div>

										<div class="card-body">

											<p>
												<?php echo wp_kses_post( __( 'The Data Protection Officer (DPO) monitors privacy compliance, serves as a point of contact for users and Supervisory Authorities, and advises the Controller. A DPO is often mandatory for public bodies, for those who carry out regular and systematic large-scale monitoring, or who process special categories of personal data on a large scale. If you do not have a DPO, requests will be handled directly by the Controller.', 'MAP_txt' ) ); ?>
											</p>

										</div>

										<img src="<?php echo plugin_dir_url( __FILE__ ) ?>../img/fox-profile.png" class="wizard-card-avatar">

									</div>



								</div>

							</div>


						</div>



					</div>
					<!-- Step 4: Site Features -->
					<div class="wizard-step" id="step-4">
						<div class="row">
							<div class="col-12">
								<h3><?php echo wp_kses_post( __( 'Site Features', 'MAP_txt' ) ); ?></h3>
								<p class="text-muted"><?php echo wp_kses_post( __( 'Check the features present on your site', 'MAP_txt' ) ); ?></p>
							</div>
						</div>
						<div class="row mb-3">

							<div class="col-md-8">

								<div class="row mb-3">
									<div class="col-md-3">
										<label class="form-label"><?php echo wp_kses_post( __( 'Check the features present on your site', 'MAP_txt' ) ); ?></label>
									</div>
									<div class="col-md-9">
										<div class="styled_radio">
											<div class="round d-flex me-4 align-items-center">

												<input type="hidden" name="site_and_policy_settings[site_features_contact_forms]" value="false" id="site_features_contact_forms_no">

												<input
													type="checkbox"
													id="site_features_contact_forms"
													name="site_and_policy_settings[site_features_contact_forms]"
													value="true"
													<?php checked( $site_and_policy_settings['site_features_contact_forms'], true ); ?>
													/>


												<label for="site_features_contact_forms" class="me-2 label-checkbox"></label>
												<label for="site_features_contact_forms"><?php echo wp_kses_post( __( 'Contact forms', 'MAP_txt' ) ); ?></label>
												<i class="fa-regular fa-circle-info text-muted ms-2" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_attr( __( 'Forms that collect user information—such as names, email addresses, and phone numbers—as well as user requests.', 'MAP_txt' ) ); ?>"></i>
											</div>
										</div>

										<span class="translate-middle-y badge rounded-pill forbiddenWarning bg-danger mt-3 <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1){echo 'd-none';} ?>">
											<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
										</span>

										<div class="styled_radio <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
											<div class="round d-flex me-4 align-items-center">

												<input type="hidden" name="site_and_policy_settings[site_features_payments]" value="false" id="site_features_payments_no">

												<input
													type="checkbox"
													id="site_features_payments"
													name="site_and_policy_settings[site_features_payments]"
													value="true"
													<?php checked( $site_and_policy_settings['site_features_payments'], true ); ?>
													/>

												<label for="site_features_payments" class="me-2 label-checkbox"></label>
												<label for="site_features_payments"><?php echo wp_kses_post( __( 'Payments', 'MAP_txt' ) ); ?></label>
												<i class="fa-regular fa-circle-info text-muted ms-2" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_attr( __( 'Online payment processing, e‑commerce transactions, credit card transactions, and financial data handling.', 'MAP_txt' ) ); ?>"></i>
											</div>
										</div>
										<div class="styled_radio <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
											<div class="round d-flex me-4 align-items-center">

												<input type="hidden" name="site_and_policy_settings[site_features_account_reg]" value="false" id="site_features_account_reg_no">

												<input
													type="checkbox"
													id="site_features_account_reg"
													name="site_and_policy_settings[site_features_account_reg]"
													value="true"
													<?php checked( $site_and_policy_settings['site_features_account_reg'], true ); ?>
													/>

												<label for="site_features_account_reg" class="me-2 label-checkbox"></label>
												<label for="site_features_account_reg"><?php echo wp_kses_post( __( 'Account registration', 'MAP_txt' ) ); ?></label>
												<i class="fa-regular fa-circle-info text-muted ms-2" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_attr( __( 'User registration, user accounts and login, user profiles, and membership areas.', 'MAP_txt' ) ); ?>"></i>
											</div>
										</div>
										<div class="styled_radio <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
											<div class="round d-flex me-4 align-items-center">

												<input type="hidden" name="site_and_policy_settings[site_features_newsletter]" value="false" id="site_features_newsletter_no">

												<input
													type="checkbox"
													id="site_features_newsletter"
													name="site_and_policy_settings[site_features_newsletter]"
													value="true"
													class="hideShowInput"
													data-hide-show-ref="site_features_newsletter_wrapper"
													<?php checked( $site_and_policy_settings['site_features_newsletter'], true ); ?>
													/>

												<label for="site_features_newsletter" class="me-2 label-checkbox"></label>
												<label for="site_features_newsletter"><?php echo wp_kses_post( __( 'Newsletter', 'MAP_txt' ) ); ?></label>
												<i class="fa-regular fa-circle-info text-muted ms-2" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_attr( __( 'Email marketing and newsletters, postal mailings (direct mail), SMS/MMS, other messaging services.', 'MAP_txt' ) ); ?>"></i>
											</div>
										</div>


										<div class="styled_radio ms-5 site_features_newsletter_wrapper displayNone <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
											<div class="round d-flex me-4 align-items-center">

												<input type="hidden" name="site_and_policy_settings[site_features_show_marketing_data_retention]" value="false" id="site_features_show_marketing_data_retention_no">

												<input
													type="checkbox"
													id="site_features_show_marketing_data_retention"
													name="site_and_policy_settings[site_features_show_marketing_data_retention]"
													value="true"
													<?php checked( $site_and_policy_settings['site_features_show_marketing_data_retention'], true ); ?>
													/>

												<label for="site_features_show_marketing_data_retention" class="me-2 label-checkbox"></label>
												<label for="site_features_show_marketing_data_retention"><?php echo wp_kses_post( __( 'I retain data for a maximum of 24 months from the last meaningful interaction', 'MAP_txt' ) ); ?></label>
												<i class="fa-regular fa-circle-info text-muted ms-2" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_attr( __( 'By "last meaningful interaction" we mean, for example, opening a communication, clicking within a communication, or submitting a request through the website.', 'MAP_txt' ) ); ?>"></i>
											</div>
										</div>


										<div class="styled_radio <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
											<div class="round d-flex me-4 align-items-center">

												<input type="hidden" name="site_and_policy_settings[site_features_reviews_collect]" value="false" id="site_features_reviews_collect_no">

												<input
													type="checkbox"
													id="site_features_reviews_collect"
													name="site_and_policy_settings[site_features_reviews_collect]"
													value="true"
													<?php checked( $site_and_policy_settings['site_features_reviews_collect'], true ); ?>
													/>

												<label for="site_features_reviews_collect" class="me-2 label-checkbox"></label>
												<label for="site_features_reviews_collect"><?php echo wp_kses_post( __( 'Reviews / Feedback collection', 'MAP_txt' ) ); ?></label>
												<i class="fa-regular fa-circle-info text-muted ms-2" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_attr( __( 'Customer reviews, ratings, testimonials, surveys, and feedback forms.', 'MAP_txt' ) ); ?>"></i>
											</div>
										</div>
										<div class="styled_radio <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
											<div class="round d-flex me-4 align-items-center">

												<input type="hidden" name="site_and_policy_settings[site_features_minors_data]" value="false" id="site_features_minors_data_no">

												<input
													type="checkbox"
													id="site_features_minors_data"
													name="site_and_policy_settings[site_features_minors_data]"
													value="true"
													<?php checked( $site_and_policy_settings['site_features_minors_data'], true ); ?>
													/>

												<label for="site_features_minors_data" class="me-2 label-checkbox"></label>
												<label for="site_features_minors_data"><?php echo wp_kses_post( __( 'Processing of minors\' personal data', 'MAP_txt' ) ); ?></label>
												<i class="fa-regular fa-circle-info text-muted ms-2" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_attr( __( 'Collection of personal data of users under 18.', 'MAP_txt' ) ); ?>"></i>
											</div>
										</div>
										<div class="styled_radio <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
											<div class="round d-flex me-4 align-items-center">

												<input type="hidden" name="site_and_policy_settings[site_features_sensitive_data]" value="false" id="site_features_sensitive_data_no">

												<input
													type="checkbox"
													id="site_features_sensitive_data"
													name="site_and_policy_settings[site_features_sensitive_data]"
													value="true"
													<?php checked( $site_and_policy_settings['site_features_sensitive_data'], true ); ?>
													/>

												<label for="site_features_sensitive_data" class="me-2 label-checkbox"></label>
												<label for="site_features_sensitive_data"><?php echo wp_kses_post( __( 'Processing of special categories of personal data', 'MAP_txt' ) ); ?></label>
												<i class="fa-regular fa-circle-info text-muted ms-2" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_attr( __( 'Collection and processing of special categories of personal data.', 'MAP_txt' ) ); ?>"></i>
											</div>
										</div>
										<div class="mt-2">
											<p>
												<a href="#" class="map-sensitive-data-toggle"><?php echo wp_kses_post( __( 'What are the special categories of personal data?', 'MAP_txt' ) ); ?></a>
											</p>
												<ul class="map-sensitive-data-examples mt-2" style="display: none;">
													<li><strong><?php echo wp_kses_post( __( 'Health data', 'MAP_txt' ) ); ?></strong> <?php echo wp_kses_post( __( '(e.g., diagnoses, medical conditions, treatments and medications, test results/reports, disability)', 'MAP_txt' ) ); ?></li>
													<li><strong><?php echo wp_kses_post( __( 'Political opinions', 'MAP_txt' ) ); ?></strong> <?php echo wp_kses_post( __( '(e.g., membership, support or donations to parties/movements)', 'MAP_txt' ) ); ?></li>
													<li><strong><?php echo wp_kses_post( __( 'Religious or philosophical beliefs', 'MAP_txt' ) ); ?></strong> <?php echo wp_kses_post( __( '(e.g., membership in a religious denomination or ethical belief organization)', 'MAP_txt' ) ); ?></li>
													<li><strong><?php echo wp_kses_post( __( 'Racial or ethnic origin', 'MAP_txt' ) ); ?></strong> <?php echo wp_kses_post( __( '(e.g., stated ethnicity or racial background)', 'MAP_txt' ) ); ?></li>
													<li><strong><?php echo wp_kses_post( __( 'Biometric data processed for the purpose of unique identification', 'MAP_txt' ) ); ?></strong> <?php echo wp_kses_post( __( '(e.g., fingerprints, facial recognition templates, iris/retina scans, voice templates)', 'MAP_txt' ) ); ?></li>
													<li><strong><?php echo wp_kses_post( __( 'Genetic data', 'MAP_txt' ) ); ?></strong> <?php echo wp_kses_post( __( '(e.g., DNA profiles, results of genetic testing)', 'MAP_txt' ) ); ?></li>
													<li><strong><?php echo wp_kses_post( __( 'Sex life or sexual orientation', 'MAP_txt' ) ); ?></strong> <?php echo wp_kses_post( __( '(e.g., information on relationships or practices, self-declared orientation, memberships revealing orientation)', 'MAP_txt' ) ); ?></li>
													<li><strong><?php echo wp_kses_post( __( 'Trade union membership', 'MAP_txt' ) ); ?></strong> <?php echo wp_kses_post( __( '(e.g., union enrollment, payroll dues/withholdings)', 'MAP_txt' ) ); ?></li>
												</ul>
										</div>
									</div>
								</div>

							</div>
							<div class="col-md-4">

								<div class="card-group">
									<div class="card rounded-3 p-0 pb-5 m-0 mx-1">
										<div class="card-header bg-transparent">

											<h5 class="pt-2">

												<?php echo wp_kses_post( __( 'What should be included under "Site Features"?', 'MAP_txt' ) ); ?>

											</h5>
										</div>

										<div class="card-body">

											<p>
												<?php echo wp_kses_post( __( 'For transparency to users, you should disclose what is actually active (contact forms, newsletters, payments, members-only area, reviews, social media integrations, analytics/marketing). ', 'MAP_txt' ) ); ?><br>
												<?php echo wp_kses_post( __( 'This allows the policies to be tailored, explaining to your users what data you collect and why. ', 'MAP_txt' ) ); ?><br>
												<?php echo wp_kses_post( __( 'Select only the features that are actually active.', 'MAP_txt' ) ); ?>
											</p>

										</div>

										<img src="<?php echo plugin_dir_url( __FILE__ ) ?>../img/fox-profile.png" class="wizard-card-avatar">

									</div>

								</div>

							</div>

						</div>
					</div>
					<!-- Step 5: Data Sharing -->
					<div class="wizard-step" id="step-5">
						<div class="row">
							<div class="col-12">
								<h3><?php echo wp_kses_post( __( 'Data Sharing', 'MAP_txt' ) ); ?></h3>
								<p class="text-muted"><?php echo wp_kses_post( __( 'Information about data sharing with third parties.', 'MAP_txt' ) ); ?></p>
							</div>
						</div>
						<div class="row mb-3">


							<div class="col-md-8">
								<div class="row mb-3">
									<div class="col-md-3">
										<label class="form-label">
											<?php echo wp_kses_post( __( 'In your business, do you use suppliers located outside the EU, Switzerland or other countries recognized as adequate?', 'MAP_txt' ) ); ?>
										</label>
									</div>

									<div class="col-md-9">

										<span class="translate-lower-middle-y badge rounded-pill forbiddenWarning bg-danger <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1){echo 'd-none';} ?>">
											<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
										</span>

										<div class="styled_radio d-inline-flex <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
											<div class="round d-flex me-4">

												<input type="hidden" name="site_and_policy_settings[outside_adequate_suppliers]" value="false" id="outside_adequate_suppliers_no">

												<input
													type="checkbox"
													id="outside_adequate_suppliers"
													name="site_and_policy_settings[outside_adequate_suppliers]"
													class="hideShowInput"
													data-hide-show-ref="map_outside_adequate_countries_wrapper"
													value="true"
													<?php checked( $site_and_policy_settings['outside_adequate_suppliers'], true ); ?>
													/>

												<label for="outside_adequate_suppliers" class="me-2 label-checkbox"></label>
												<label for="outside_adequate_suppliers">
													<?php echo wp_kses_post( __( 'Yes, I use suppliers outside EU / Switzerland / adequate countries', 'MAP_txt' ) ); ?>
												</label>
											</div>
										</div>
										<div class="map_outside_adequate_countries_wrapper mt-3 displayNone">
											<label class="form-label">
												<?php echo wp_kses_post( __( 'Select countries', 'MAP_txt' ) ); ?> <span class="">(*)</span>
											</label>

											<div class="row">

												<?php

													$columns = 4;
													$colClass = 'col-md-3';
													$total = ( isset( $available_countries['not_adequate'] ) ) ? count( $available_countries['not_adequate'] ) : 0;
													$currentCol = -1;
													$idx = 0;

													if( isset( $available_countries['not_adequate'] ) )
													{
														foreach( $available_countries['not_adequate'] as $country_key => $country_item )
														{
															// Compute which column the i-th item belongs to (balanced chunks)
															$colIdx = ( $total > 0 ) ? (int) floor( $idx * $columns / $total ) : 0;

															// When the target column changes, close the previous div and open a new column
															if( $colIdx !== $currentCol )
															{
																if( $currentCol !== -1 )
																{
																	echo "  </div>" . PHP_EOL;
																}

																echo "  <div class=\"$colClass\">" . PHP_EOL;

																$currentCol = $colIdx;
															}

															?>

															<div class="styled_radio d-flex">
																<div class="round d-flex me-4">

																	<input type="hidden" name="site_and_policy_settings[outside_country_<?php echo esc_attr( $country_key );?>]" value="false" id="<?php echo esc_attr( $country_key );?>_no">

																	<input
																		type="checkbox"
																		id="outside_country_<?php echo esc_attr( $country_key );?>"
																		name="site_and_policy_settings[outside_country_<?php echo esc_attr( $country_key );?>]"
																		value="true"
																		data-checkbox-group="map_outside_adequate_countries_wrapper"
																		<?php

																			$this_key = 'outside_country_' . $country_key;
																			checked( isset( $site_and_policy_settings[$this_key] ) ? $site_and_policy_settings[$this_key] : false, true );
																		?>
																		/>


																	<label for="outside_country_<?php echo esc_attr( $country_key );?>" class="me-2 label-checkbox"></label>
																	<label for="outside_country_<?php echo esc_attr( $country_key );?>"><?php echo wp_kses_post( $country_item ); ?></label>
																</div>
															</div>


															<?php

															$idx++;

														}
													}


													if ($currentCol !== -1)
													{
														 echo "  </div>" . PHP_EOL;
													}

												?>

											</div>

										</div>
									</div>
								</div>
							</div>

							<div class="col-md-4">

								<div class="card-group">
									<div class="card rounded-3 p-0 pb-5 m-0 mx-1">
										<div class="card-header bg-transparent">

											<h5 class="pt-2">

												<?php echo wp_kses_post( __( 'What does "Data sharing/transfers abroad" mean?', 'MAP_txt' ) ); ?>

											</h5>
										</div>

										<div class="card-body">

											<p>

												<?php echo wp_kses_post( __( 'A transfer occurs when personal data is processed or stored outside the European Economic Area (EEA), Switzerland, or a country deemed “adequate.” This can happen with hosting, CDNs, help desks, analytics, email, CRM systems, advertising, or other providers that use data centers located abroad.', 'MAP_txt' ) ); ?><br>
												<?php echo wp_kses_post( __( 'Indicate whether transfers occur outside the EEA/Switzerland/adequate countries and, if so, specify the third countries involved.', 'MAP_txt' ) ); ?>


											</p>
											<p>
												<a href="https://ec.europa.eu/info/law/law-topic/data-protection/international-dimension-data-protection/adequacy-decisions_en" target="blank"><?php echo wp_kses_post( __( 'List of countries deemed adequate.', 'MAP_txt' ) ); ?></a>
											</p>

											<p>
												<?php echo wp_kses_post( __( 'In the event of transfers to countries outside the EEA or Switzerland, or to countries not deemed “adequate,” you must in all cases implement appropriate security measures and safeguards required by law, including:', 'MAP_txt' ) ); ?><br>

												-<?php echo wp_kses_post( __( 'Standard Contractual Clauses approved by the European Commission;', 'MAP_txt' ) ); ?><br>
												-<?php echo wp_kses_post( __( 'Adequacy decisions by the European Commission or the Swiss authority;', 'MAP_txt' ) ); ?><br>
												-<?php echo wp_kses_post( __( 'Adherence to a certified framework (e.g., the U.S. Data Privacy Framework);', 'MAP_txt' ) ); ?><br>
												-<?php echo wp_kses_post( __( 'Other instruments recognized under supranational or national law.', 'MAP_txt' ) ); ?>

											</p>

										</div>

										<img src="<?php echo plugin_dir_url( __FILE__ ) ?>../img/fox-profile.png" class="wizard-card-avatar">

									</div>

								</div>

							</div>

						</div>
					</div>
					<!-- Step 6: Protection Systems -->
					<div class="wizard-step" id="step-6">
						<div class="row">
							<div class="col-12">
								<h3><?php echo wp_kses_post( __( 'Protection Systems', 'MAP_txt' ) ); ?></h3>
								<p class="text-muted"><?php echo wp_kses_post( __( 'Check the protection systems in place', 'MAP_txt' ) ); ?></p>
							</div>
						</div>
						<div class="row mb-3">


							<div class="col-md-8">
								<div class="row mb-3">
									<div class="col-md-3">
										<label class="form-label"><?php echo wp_kses_post( __( 'Check the protection systems in place', 'MAP_txt' ) ); ?></label>
									</div>
									<div class="col-md-9">
										<div class="styled_radio">
											<div class="round d-flex me-4 align-items-center">

												<input type="hidden" name="site_and_policy_settings[protection_system_https]" value="false" id="protection_system_https_no">

												<input
													type="checkbox"
													id="protection_system_https"
													name="site_and_policy_settings[protection_system_https]"
													value="true"
													<?php checked( $site_and_policy_settings['protection_system_https'], true ); ?>
													/>

												<label for="protection_system_https" class="me-2 label-checkbox"></label>
												<label for="protection_system_https"><?php echo wp_kses_post( __( 'Communication encryption (https)', 'MAP_txt' ) ); ?></label>

												<i class="fa-regular fa-circle-info text-muted ms-2" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_attr( __( 'Communications between your browser and the site are protected by HTTPS encryption, which prevents the interception or tampering of data exchanged while you browse.', 'MAP_txt' ) ); ?>"></i>
											</div>
										</div>

										<span class="translate-middle-y badge rounded-pill forbiddenWarning bg-danger mt-3 <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1){echo 'd-none';} ?>">
											<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
										</span>

										<div class="styled_radio <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
											<div class="round d-flex me-4 align-items-center">

												<input type="hidden" name="site_and_policy_settings[protection_system_log_control]" value="false" id="protection_system_log_control_no">

												<input
													type="checkbox"
													id="protection_system_log_control"
													name="site_and_policy_settings[protection_system_log_control]"
													value="true"
													<?php checked( $site_and_policy_settings['protection_system_log_control'], true ); ?>
													/>

												<label for="protection_system_log_control" class="me-2 label-checkbox"></label>
												<label for="protection_system_log_control"><?php echo wp_kses_post( __( 'Log monitoring', 'MAP_txt' ) ); ?></label>
												<i class="fa-regular fa-circle-info text-muted ms-2" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_attr( __( 'The system logs and monitors access and activity to detect suspicious or unauthorized attempts and ensure data security.', 'MAP_txt' ) ); ?>"></i>
											</div>
										</div>
										<div class="styled_radio <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
											<div class="round d-flex me-4 align-items-center">

												<input type="hidden" name="site_and_policy_settings[protection_system_backup]" value="false" id="protection_system_backup_no">

												<input
													type="checkbox"
													id="protection_system_backup"
													name="site_and_policy_settings[protection_system_backup]"
													value="true"
													<?php checked( $site_and_policy_settings['protection_system_backup'], true ); ?>
													/>

												<label for="protection_system_backup" class="me-2 label-checkbox"></label>
												<label for="protection_system_backup"><?php echo wp_kses_post( __( 'Periodic backups', 'MAP_txt' ) ); ?></label>
												<i class="fa-regular fa-circle-info text-muted ms-2" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_attr( __( 'Data is regularly backed up and stored to protect it against accidental loss or technical incidents.', 'MAP_txt' ) ); ?>"></i>

											</div>
										</div>
										<div class="styled_radio <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
											<div class="round d-flex me-4 align-items-center">

												<input type="hidden" name="site_and_policy_settings[protection_system_audit]" value="false" id="protection_system_audit_no">

												<input
													type="checkbox"
													id="protection_system_audit"
													name="site_and_policy_settings[protection_system_audit]"
													value="true"
													<?php checked( $site_and_policy_settings['protection_system_audit'], true ); ?>
													/>

												<label for="protection_system_audit" class="me-2 label-checkbox"></label>
												<label for="protection_system_audit"><?php echo wp_kses_post( __( 'Security audits and checks', 'MAP_txt' ) ); ?></label>
												<i class="fa-regular fa-circle-info text-muted ms-2" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_attr( __( 'Regular security assessments and tests are conducted to identify and address potential system vulnerabilities.', 'MAP_txt' ) ); ?>"></i>
											</div>
										</div>
										<div class="styled_radio <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea"';} ?>">
											<div class="round d-flex me-4 align-items-center">

												<input type="hidden" name="site_and_policy_settings[protection_system_access_limited]" value="false" id="protection_system_access_limited_no">

												<input
													type="checkbox"
													id="protection_system_access_limited"
													name="site_and_policy_settings[protection_system_access_limited]"
													value="true"
													<?php checked( $site_and_policy_settings['protection_system_access_limited'], true ); ?>
													/>

												<label for="protection_system_access_limited" class="me-2 label-checkbox"></label>
												<label for="protection_system_access_limited"><?php echo wp_kses_post( __( 'Restriction of data access to duly authorized subjects only', 'MAP_txt' ) ); ?></label>
												<i class="fa-regular fa-circle-info text-muted ms-2" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_attr( __( 'Access to personal data is granted only to authorized and trained personnel, in accordance with internal security policies.', 'MAP_txt' ) ); ?>"></i>
											</div>
										</div>
									</div>
								</div>
							</div>

							<div class="col-md-4">

								<div class="card-group">
									<div class="card rounded-3 p-0 pb-5 m-0 mx-1">
										<div class="card-header bg-transparent">

											<h5 class="pt-2">

												<?php echo wp_kses_post( __( 'What should be included under "Security Measures"?', 'MAP_txt' ) ); ?>

											</h5>
										</div>

										<div class="card-body">
											<p>
												<?php echo wp_kses_post( __( 'Clearly show your users the measures you have implemented to protect data.', 'MAP_txt' ) ); ?><br>
												<?php echo wp_kses_post( __( 'List only measures that are actually in place and maintained over time.', 'MAP_txt' ) ); ?>
											</p>
										</div>

										<img src="<?php echo plugin_dir_url( __FILE__ ) ?>../img/fox-profile.png" class="wizard-card-avatar">

									</div>

								</div>

							</div>

						</div>
					</div>
					<!-- Step 7: Completion -->
					<div class="wizard-step completion-step" id="step-7">
						
						<div class="row">
							<div class="col-12 text-center">
								<div class="mb-4 success-confirmation alert-success p-4">
									<i class="fa-regular fa-check-circle" style="font-size: 4rem;"></i>
									<h3><?php echo wp_kses_post( __( 'Policy Configuration Complete!', 'MAP_txt' ) ); ?></h3>
								</div>
								<p class="text-muted mb-4"><strong><?php echo wp_kses_post( __( 'Your Policies has been successfully configured and update based on your preferences.', 'MAP_txt' ) ); ?></strong></p>
							</div>
						</div>

						<div class="row my-4">
							<div class="col-md-6">
								<div class="whats-next">
									<h5><?php echo wp_kses_post( __( 'What happens next?', 'MAP_txt' ) ); ?></h5>
									<ul class="mb-0">
										<li class="mb-2"><i class="fa-regular fa-arrow-right text-primary me-2"></i> <?php echo wp_kses_post( __( 'Your Privacy Policies are already updated.', 'MAP_txt' ) ); ?></li>
										<li class="mb-2"><i class="fa-regular fa-arrow-right text-primary me-2"></i> <?php echo wp_kses_post( __( 'You can further customize settings in the main plugin panel.', 'MAP_txt' ) ); ?></li>
									</ul>
								</div>
							</div>

							<div class="col-md-6">
								<div class="whats-next">
									<h5 class="alt_bg"><?php echo wp_kses_post( __( 'What Steps Should I Take Now?', 'MAP_txt' ) ); ?></h5>
									<ul class="mb-4">
										<li class="mb-2"><i class="fa-regular fa-arrow-right text-primary me-2"></i> <?php echo wp_kses_post( __( 'First, create the Cookie Policy and Privacy Policy pages. Then insert the corresponding shortcode into each page.', 'MAP_txt' ) ); ?></li>
										<li class="mb-2"><i class="fa-regular fa-arrow-right text-primary me-2"></i> <?php echo wp_kses_post( __( 'If these pages don’t exist yet, create and publish them, then add the relevant shortcodes to the page content.', 'MAP_txt' ) ); ?></li>
										<li class="mb-2"><i class="fa-regular fa-arrow-right text-primary me-2"></i> <?php echo wp_kses_post( __( 'You can find the shortcodes on the page shown below.', 'MAP_txt' ) ); ?></li>
										<li class="mb-2"><i class="fa-regular fa-arrow-right text-primary me-2"></i> <?php echo wp_kses_post( __( 'Final step: Go to the link below to complete the association.', 'MAP_txt' ) ); ?></li>
									</ul>
									<div class="text-center">
										<a href="<?php echo esc_url( add_query_arg( array( 'post_type' => 'my-agile-privacy-c', 'page' => 'my-agile-privacy-c_settings' ), admin_url( 'edit.php' ) ) ); ?>#policies" class="btn btn-primary btn-lg me-3">
											<i class="fa-regular fa-cog"></i> <?php echo wp_kses_post( __( 'Go to Settings > Policy Tab', 'MAP_txt' ) ); ?>
										</a>
									</div>
								</div>
							</div>
						</div>
						
					</div>
				</div> <!-- /.wizard-steps -->
				<!-- Navigation Buttons -->
				<div class="wizard-navigation mt-4">
					<div class="row">
						<div class="col-6">
							<button type="button" class="btn btn-secondary map-wizard-prev-btn displayNone" id="wizard-prev-btn">
								<i class="fa-regular fa-arrow-left"></i> <?php echo wp_kses_post( __( 'Previous', 'MAP_txt' ) ); ?>
							</button>
						</div>
						<div class="col-6 text-end">
							<span class="map_wait text-muted ms-2">
								<i class="fas fa-spinner-third fa-fw fa-spin" style="--fa-animation-duration: 1s;"></i> <?php echo wp_kses_post( __( 'Saving in progress', 'MAP_txt' ) ); ?>...
							</span>

							<button type="button" class="btn btn-primary map-wizard-next-btn-first-step displayNone" id="wizard-next-btn-first-step">
								<?php echo wp_kses_post( __( 'Next', 'MAP_txt' ) ); ?> <i class="fa-regular fa-arrow-right"></i>
							</button>

							<button type="button" class="btn btn-primary map-wizard-next-btn displayNone" id="wizard-next-btn">
								<?php echo wp_kses_post( __( 'Save & Next', 'MAP_txt' ) ); ?> <i class="fa-regular fa-arrow-right"></i>
							</button>

							<button type="submit" name="update_guided_wizard_form" class="btn btn-success map-wizard-finish-btn displayNone" id="wizard-finish-btn">
								<i class="fa-regular fa-check"></i> <?php echo wp_kses_post( __( 'Complete Configuration', 'MAP_txt' ) ); ?>
							</button>
						</div>
					</div>
				</div>
			</div> <!-- /.container-fluid -->
		</form>
	</div> <!-- consistent-box -->

</div> <!-- /#my_agile_privacy_backend -->


