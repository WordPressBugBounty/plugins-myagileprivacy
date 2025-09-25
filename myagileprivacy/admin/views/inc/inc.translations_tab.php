<?php

if( !defined( 'MAP_PLUGIN_NAME' ) )
{
	exit('Not allowed.');
}

?>

<div class="row mb-3">
	<div class="col-12">

		<div class="mb-3">

			<?php
				echo wp_kses_post( __( "From this interface, you can edit the texts of the cookie banner. To find out which texts can be modified, use the mouse and hover over the text: if it is editable, it will be highlighted in yellow. Click on the text to edit it.", 'MAP_txt' ) );
			?>

			<br>

			<?php
				echo wp_kses_post( __( "Remember to save to apply the changes made.", 'MAP_txt' ) );
			?>

		</div>

		<div class="alert alert-warning" role="alert">

			<?php
				echo wp_kses_post( __( "Warning: Modify the banner texts only under the supervision of your privacy consultant.", 'MAP_txt' ) );
			?>

			<br>

			<?php
				echo wp_kses_post( __( "Altering the content without supervision could invalidate your website's compliance.", 'MAP_txt' ) );
			?>

		</div>

	</div>
</div>

<span class="translate-middle-y forbiddenWarning badge rounded-pill bg-danger  <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1){echo 'd-none';} ?>">
	<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
</span>
<div class="row  <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] != 1){echo 'forbiddenArea';} ?>">
	<div class="col-sm-2">
		<div class="nav flex-column nav-pills me-3" id="map-translations-tab" role="tablist"
			aria-orientation="vertical">

			<?php

				$lang_count = 0;

				foreach( $currentAndSupportedLanguages['supported_languages'] as $lang_code => $lang_data ):

					$active_class = ($lang_count === 0) ? ' active' : '';

					$label = $lang_data['label'];
			?>

					<button class="nav-link<?php echo esc_attr( $active_class ); ?>" id="map-pills-<?php echo esc_attr( $lang_code ); ?>-tab" data-bs-toggle="pill" data-bs-target="#map_translations-<?php echo esc_attr( $lang_code ); ?>" type="button" role="tab"><?php echo esc_html( $label ); ?></button>

			<?php
					$lang_count++;
				endforeach;



			?>

		</div>
	</div>

	<div class="col-sm-10">
		<div class="tab-content" id="v-pills-tabContent">

			<?php

				$lang_count = 0;
				foreach( $currentAndSupportedLanguages['supported_languages'] as $lang_code => $lang_data ):

					$active_class = ($lang_count === 0) ? ' show active' : '';
			?>

					<div class="tab-pane fade<?php echo esc_attr( $active_class ); ?> lang-panel" id="map_translations-<?php echo esc_attr( $lang_code ); ?>" role="tabpanel">

						<!-- bof hidden original input -->

						<input type="hidden" id="<?php echo esc_attr( $lang_code ); ?>_banner_title" name="translations[<?php echo esc_attr( $lang_code) ; ?>][banner_title]" value="<?php echo ( $the_translations[$lang_code]['banner_title'] ) ? esc_attr( $the_translations[$lang_code]['banner_title'] ) : 'My Agile Privacy' ; ?>">

						<input type="hidden" id="<?php echo esc_attr( $lang_code ); ?>_notify_message_v2" name="translations[<?php echo esc_attr( $lang_code ); ?>][notify_message_v2]"
							value="<?php echo esc_attr( $the_translations[$lang_code]['notify_message_v2'] ); ?>">

						<input type="hidden" id="<?php echo esc_attr( $lang_code ); ?>_accept" name="translations[<?php echo esc_attr( $lang_code ); ?>][accept]"
							value="<?php echo esc_attr( $the_translations[$lang_code]['accept'] ); ?>">

						<input type="hidden" id="<?php echo esc_attr( $lang_code ); ?>_refuse" name="translations[<?php echo esc_attr( $lang_code ); ?>][refuse]"
							value="<?php echo esc_attr( $the_translations[$lang_code]['refuse'] ); ?>">

						<input type="hidden" id="<?php echo esc_attr( $lang_code ); ?>_customize" name="translations[<?php echo esc_attr( $lang_code ); ?>][customize]"
							value="<?php echo esc_attr( $the_translations[$lang_code]['customize'] ); ?>">

						<input type="hidden" id="<?php echo esc_attr( $lang_code ); ?>_manage_consent" name="translations[<?php echo esc_attr( $lang_code ); ?>][manage_consent]"
							value="<?php echo esc_attr( $the_translations[$lang_code]['manage_consent'] ); ?>">

						<input type="hidden" id="<?php echo esc_attr( $lang_code ); ?>_this_website_uses_cookies" name="translations[<?php echo esc_attr( $lang_code ); ?>][this_website_uses_cookies]"
							value="<?php echo esc_attr( $the_translations[$lang_code]['this_website_uses_cookies'] ); ?>">

						<?php if( !empty( $mapx_items ) ): ?>
							<input type="hidden" id="<?php echo esc_attr( $lang_code ); ?>_in_addition_this_site_installs"
								name="translations[<?php echo esc_attr( $lang_code ); ?>][in_addition_this_site_installs]"
								value="<?php echo esc_attr( $the_translations[$lang_code]['in_addition_this_site_installs'] ); ?>">

							<input type="hidden" id="<?php echo esc_attr( $lang_code ); ?>_with_anonymous_data_transmission_via_proxy"
								name="translations[<?php echo esc_attr( $lang_code ); ?>][with_anonymous_data_transmission_via_proxy]"
								value="<?php echo esc_attr( $the_translations[$lang_code]['with_anonymous_data_transmission_via_proxy'] ); ?>">
						<?php endif; ?>

						<?php if( $iab_tcf_context ): ?>
							<input type="hidden" id="<?php echo esc_attr( $lang_code ); ?>_iab_bannertext_1" name="translations[<?php echo esc_attr( $lang_code ); ?>][iab_bannertext_1]"
								value="<?php echo esc_attr( $the_translations[$lang_code]['iab_bannertext_1'] ); ?>">

							<input type="hidden" id="<?php echo esc_attr( $lang_code ); ?>_iab_bannertext_2_a" name="translations[<?php echo esc_attr( $lang_code ); ?>][iab_bannertext_2_a]"
								value="<?php echo esc_attr( $the_translations[$lang_code]['iab_bannertext_2_a'] ); ?>">

							<input type="hidden" id="<?php echo esc_attr( $lang_code ); ?>_iab_bannertext_2_link" name="translations[<?php echo esc_attr( $lang_code ); ?>][iab_bannertext_2_link]"
								value="<?php echo esc_attr( $the_translations[$lang_code]['iab_bannertext_2_link'] ); ?>">

							<input type="hidden" id="<?php echo esc_attr( $lang_code ); ?>_iab_bannertext_2_b" name="translations[<?php echo esc_attr( $lang_code ); ?>][iab_bannertext_2_b]"
								value="<?php echo esc_attr( $the_translations[$lang_code]['iab_bannertext_2_b'] ); ?>">

							<input type="hidden" id="<?php echo esc_attr( $lang_code ); ?>_iab_bannertext_3" name="translations[<?php echo esc_attr( $lang_code ); ?>][iab_bannertext_3]"
								value="<?php echo esc_attr( $the_translations[$lang_code]['iab_bannertext_3'] ); ?>">

							<input type="hidden" id="<?php echo esc_attr( $lang_code ); ?>_iab_bannertext_4_a" name="translations[<?php echo esc_attr( $lang_code ); ?>][iab_bannertext_4_a]"
								value="<?php echo esc_attr( $the_translations[$lang_code]['iab_bannertext_4_a'] ); ?>">

							<input type="hidden" id="<?php echo esc_attr( $lang_code ); ?>_iab_bannertext_4_b" name="translations[<?php echo esc_attr( $lang_code ); ?>][iab_bannertext_4_b]"
								value="<?php echo esc_attr( $the_translations[$lang_code]['iab_bannertext_4_b'] ); ?>">

							<input type="hidden" id="<?php echo esc_attr( $lang_code ); ?>_iab_bannertext_5" name="translations[<?php echo esc_attr( $lang_code ); ?>][iab_bannertext_5]"
								value="<?php echo esc_attr( $the_translations[$lang_code]['iab_bannertext_6'] ); ?>">

							<input type="hidden" id="<?php echo esc_attr( $lang_code ); ?>_iab_bannertext_6" name="translations[<?php echo esc_attr( $lang_code ); ?>][iab_bannertext_6]"
								value="<?php echo esc_attr( $the_translations[$lang_code]['iab_bannertext_6'] ); ?>">

							<input type="hidden" id="<?php echo esc_attr( $lang_code ); ?>_cookies_and_thirdy_part_software"
								name="translations[<?php echo esc_attr( $lang_code ); ?>][cookies_and_thirdy_part_software]"
								value="<?php echo esc_attr( $the_translations[$lang_code]['cookies_and_thirdy_part_software'] ); ?>">

							<input type="hidden" class="form-control" id="<?php echo esc_attr( $lang_code ); ?>_advertising_preferences"
								name="translations[<?php echo esc_attr( $lang_code ); ?>][advertising_preferences]"
								value="<?php echo esc_attr( $the_translations[$lang_code]['advertising_preferences'] ); ?>">

						<?php endif; ?>

						<!-- eof hidden original input -->

						<p>
							<?php echo wp_kses_post( __( "Do you want to revert to the default content?", 'MAP_txt' ) ); ?> <a role="button" class="reset_lang_values"><?php echo wp_kses_post( __( "Click here to reset the current language", 'MAP_txt' ) ); ?></a><br>
							<?php _e('You can change the active languages on your plan from the <strong><a href="https://privatearea.myagileprivacy.com/" target="_blank">Private Area</a></strong>, under the <strong>My Subscription</strong> section, by clicking on <strong>Customize Languages</strong>.','MAP_txt'); ?>
						</p>



						<div class="text-preview">
							<div class="browser">

								<div class="preview-cookiebanner" <?php if( !$the_settings['title_is_on'] )
									echo 'style="padding-top:20px;"' ?>>
									<?php if( $the_settings['title_is_on'] ): ?>
										<div class="preview-title">
											<span
												role="button" tabindex="0"
												data-edit="translations[<?php echo esc_attr( $lang_code ); ?>][banner_title]"><?php echo ($the_translations[$lang_code]['banner_title']) ? esc_attr( $the_translations[$lang_code]['banner_title'] ) : 'My Agile Privacy' ?></span>
										</div>
									<?php endif; ?>
									<div class="preview-content">
										<div class="preview-text-container">

											<span
												role="button" tabindex="0"
												data-edit="translations[<?php echo esc_attr( $lang_code ); ?>][notify_message_v2]"
												data-input-type="textarea"><?php echo esc_html( $the_translations[$lang_code]['notify_message_v2'] ); ?></span>

											<?php

												if( !empty( $mapx_items ) ):

													$mapx_texts = array();

													foreach( $mapx_items as $mapx_item )
													{
														$mapx_texts[] = esc_html( $the_translations[$lang_code][$mapx_item] );
													}


													$mapx_items_string = implode( ', ', $mapx_texts );
											?>
												<div class="my-2">

													<span
														role="button" tabindex="0"
														data-edit="translations[<?php echo esc_attr( $lang_code ); ?>][in_addition_this_site_installs]"><?php echo esc_attr( $the_translations[$lang_code]['in_addition_this_site_installs'] ); ?></span>

													<?php echo esc_html( $mapx_items_string ); ?>

													<span
														role="button" tabindex="0"
														data-edit="translations[<?php echo esc_attr( $lang_code ); ?>][with_anonymous_data_transmission_via_proxy]"><?php echo esc_attr( $the_translations[$lang_code]['with_anonymous_data_transmission_via_proxy'] ); ?></span>

												</div>
											<?php endif; ?>

											<?php if( $iab_tcf_context ): ?>
												<span
													role="button" tabindex="0"
													data-edit="translations[<?php echo esc_attr( $lang_code ); ?>][iab_bannertext_1]"><?php echo esc_attr( $the_translations[$lang_code]['iab_bannertext_1'] ); ?></span><br>

												<span
													role="button" tabindex="0"
													data-edit="translations[<?php echo esc_attr( $lang_code ); ?>][iab_bannertext_2_a]"><?php echo esc_attr( $the_translations[$lang_code]['iab_bannertext_2_a'] ); ?></span>

												<span
													class="text-wrap"
													role="button" tabindex="0"
													data-edit="translations[<?php echo esc_attr( $lang_code ); ?>][iab_bannertext_2_link]"><?php echo esc_attr( $the_translations[$lang_code]['iab_bannertext_2_link'] ); ?></span>

												<span
													role="button" tabindex="0"
													data-edit="translations[<?php echo esc_attr( $lang_code ); ?>][iab_bannertext_2_b]"><?php echo esc_attr( $the_translations[$lang_code]['iab_bannertext_2_b'] ); ?></span><br><br>

												<span
													role="button" tabindex="0"
													data-edit="translations[<?php echo esc_attr( $lang_code ); ?>][iab_bannertext_3]"><?php echo esc_attr( $the_translations[$lang_code]['iab_bannertext_3'] ); ?></span>:<br>

												<span
													role="button" tabindex="0"
													data-edit="translations[<?php echo esc_attr( $lang_code ); ?>][iab_bannertext_4_a]"><?php echo esc_attr( $the_translations[$lang_code]['iab_bannertext_4_a'] ); ?></span>

												<span
													role="button" tabindex="0"
													data-edit="translations[<?php echo esc_attr( $lang_code ); ?>][iab_bannertext_4_b]"><?php echo esc_attr( $the_translations[$lang_code]['iab_bannertext_4_b'] ); ?></span>,

												<span
													role="button" tabindex="0"
													data-edit="translations[<?php echo esc_attr( $lang_code ); ?>][iab_bannertext_5]"><?php echo esc_attr( $the_translations[$lang_code]['iab_bannertext_5'] ); ?></span>

												<span
													role="button" tabindex="0"
													data-edit="translations[<?php echo esc_attr( $lang_code ); ?>][iab_bannertext_6]"><?php echo esc_attr( $the_translations[$lang_code]['iab_bannertext_6'] ); ?></span>
											<?php endif; ?>


										</div>
										<div class="preview-button-container">

											<?php

												$layer_1_button_order = $the_settings['layer_1_button_order'];

												$layer_1_button_order_array = explode( '_', $layer_1_button_order );

												$button_key_map = array(
													'accept'    => 'accept',
													'reject'    => 'refuse',
													'customize' => 'customize',
												);

												$button_configs = array(
													'accept' => array(
														'id'    => 'preview-accept',
														'edit'  => 'accept',
														'bg'    => $the_settings['button_accept_button_color'],
														'color' => $the_settings['button_accept_link_color'],
														'label' => $the_translations[$lang_code]['accept'],
													),
													'refuse' => array(
														'id'    => 'preview-refuse',
														'edit'  => 'refuse',
														'bg'    => $the_settings['button_reject_button_color'],
														'color' => $the_settings['button_reject_link_color'],
														'label' => $the_translations[$lang_code]['refuse'],
													),
													'customize' => array(
														'id'    => 'preview-customize',
														'edit'  => 'customize',
														'bg'    => $the_settings['button_customize_button_color'],
														'color' => $the_settings['button_customize_link_color'],
														'label' => $the_translations[$lang_code]['customize'],
													),
												);


												foreach( $layer_1_button_order_array as $button_key )
												{
													if( isset( $button_key_map[$button_key] ) )
													{
														$normalized = $button_key_map[$button_key];
														$config = $button_configs[$normalized];
														?>
														<div class="preview-button" id="<?php echo esc_attr( $config['id'] ); ?>">
															<span
																role="button" tabindex="0"
																data-edit="translations[<?php echo esc_attr( $lang_code ); ?>][<?php echo esc_attr( $config['edit'] ); ?>]"
																style="background:<?php echo esc_attr( $config['bg'] ); ?>; color:<?php echo esc_attr( $config['color'] ); ?>;">
																<?php echo esc_html($config['label']); ?>
															</span>
														</div>
														<?php
													}
												}
											?>

										</div>
									</div>
								</div> <!-- cookie banner -->

								<div class="my-agile-privacy-consent-again" style="border-radius: 15px; opacity: 1; display: block;">
									<div class="map_logo_container" style="background-color:#f14307;"></div>
									<span
										role="button" tabindex="0"
										data-edit="translations[<?php echo esc_attr( $lang_code ); ?>][manage_consent]"><?php echo esc_html( $the_translations[$lang_code]['manage_consent'] ); ?></span>
								</div>

							</div> <!-- browser -->

							<!-- bof second layer -->
							<div class="browser with_overlay">

								<div class="second-layer">
									<div class="map-modal-body">

										<div class="map-container-fluid map-tab-container" style="height: 603px;">

											<div class="map-privacy-overview">
												<h4><?php echo esc_html( $the_translations[$lang_code]['privacy_settings'] ); ?></h4>
											</div>

											<p>
												<span
													role="button" tabindex="0"
													data-edit="translations[<?php echo esc_attr( $lang_code ); ?>][this_website_uses_cookies]"><?php echo esc_attr( $the_translations[$lang_code]['this_website_uses_cookies'] ); ?></span>
											</p>
											<div class="map-cookielist-overflow-container" style="max-height: 307px;">

												<div class="map-consent-extrawrapper">
													<?php if( $iab_tcf_context ): ?>
														<div class="d-flex justify-content-around mb-4">

															<div class="second-layer-tab">
																<span
																	role="button" tabindex="0"
																	data-edit="translations[<?php echo esc_attr( $lang_code ); ?>][cookies_and_thirdy_part_software]"><?php echo esc_attr( $the_translations[$lang_code]['cookies_and_thirdy_part_software'] ); ?></span>
															</div>
															<div class="second-layer-tab">
																<span
																	role="button" tabindex="0"
																	data-edit="translations[<?php echo esc_attr( $lang_code ); ?>][advertising_preferences]"><?php echo esc_attr( $the_translations[$lang_code]['advertising_preferences'] ); ?></span>
															</div>
														</div>
													<?php endif; ?>

													<div class="cookie-placeholder-list">
														<div><?php echo wp_kses_post( __( 'This is an example cookie', 'MAP_txt' ) ); ?></div>
														<div><?php echo wp_kses_post( __( 'This is an example cookie', 'MAP_txt' ) ); ?></div>
														<div><?php echo wp_kses_post( __( 'This is an example cookie', 'MAP_txt' ) ); ?></div>
														<div><?php echo wp_kses_post( __( 'This is an example cookie', 'MAP_txt' ) ); ?></div>
													</div>

												</div>

												<div class="mt-3 d-flex flex-row-reverse"><img src="<?php echo esc_attr( plugin_dir_url(__DIR__) ); ?>../img/privacy-by-pro.png" style="max-width: 300px"></div>

											</div> <!-- overflow-cookielist-container -->

										</div> <!-- map-container-fluid -->

									</div> <!-- map-modal-body -->
								</div>

							</div> <!-- browser -->


						</div> <!-- text-preview -->


					</div> <!-- tab-pane -->

			<?php
					$lang_count++;
				endforeach;
			?>

		</div>

	</div>
</div>

<!-- Modal -->
<div class="modal fade" id="MAP_editModal" tabindex="-1">
  <div class="modal-dialog">
	<div class="modal-content">
	  <div class="modal-header">
		<h5 class="modal-title" id="editModalLabel"> <?php echo wp_kses_post( __( "Modify text", 'MAP_txt' ) ); ?></h5>
		<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
	  </div>
	  <div class="modal-body">
	  </div>
	  <div class="modal-footer">
		<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo wp_kses_post( __( "Cancel", 'MAP_txt' ) ); ?></button>
		<button type="button" class="btn btn-primary" id="saveChanges"><?php echo wp_kses_post( __( "Modify text", 'MAP_txt' ) ); ?></button>
	  </div>
	</div>
  </div>
</div>

