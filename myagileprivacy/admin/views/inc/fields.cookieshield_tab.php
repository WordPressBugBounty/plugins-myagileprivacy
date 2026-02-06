<?php

if( !defined( 'MAP_PLUGIN_NAME' ) )
{
	exit('Not allowed.');
}


$first_class = '';
$second_class = 'd-none';

if( isset( $the_settings['pa'] ) &&
	$the_settings['pa'] == 1
)
{
	$first_class = 'd-none';
	$second_class = '';
}

?>


<div class="<?php if( $caller == 'genericOptionsWrapper') echo 'col-sm-8'; ?> <?php echo esc_attr( $first_class );?>">

	<span class="translate-middle-y badge rounded-pill forbiddenWarning bg-danger  <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1){echo 'd-none';} ?>">
		<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
	</span>

	<div class="consistent-box">
		<h4 class="mb-4">
			<i class="fa-regular fa-shield"></i>
			<?php echo wp_kses_post( __( 'Cookie Shield', 'MAP_txt' ) ); ?>
		</h4>

		<div class="row mb-1">
			<div class="col-sm-12">
				<p>
					<?php echo wp_kses_post( __( 'This feature allows you to automatically detect cookies and third-party software on your website, enabling you to be compliant with the requirement of prior consent.', 'MAP_txt' ) ); ?><br>
					<?php echo wp_kses_post( __( 'Without this feature, <b>you might not be compliant with the GDPR regulations.</b> ', 'MAP_txt' ) ); ?>
				</p>
			</div>
		</div>

	</div>
</div>


<div class="<?php if( $caller == 'genericOptionsWrapper') echo 'col-sm-8'; ?> <?php echo esc_attr( $second_class );?>">

	<div class="consistent-box">
		<h4 class="mb-4">
			<i class="fa-regular fa-shield"></i>
			<?php echo wp_kses_post( __( 'Cookie Shield', 'MAP_txt' ) ); ?>
		</h4>

		<div class="row mb-5">
			<div class="col-sm-12">
				<?php echo wp_kses_post( __( 'This advanced feature is essential for the automatic detection and blocking of the most common third-party cookies and software.', 'MAP_txt' ) ); ?><br>
				<br>
				<?php echo wp_kses_post( __( 'By enabling Cookie Shield in "Learning" mode and starting to navigate your site, the tool will automatically detect the cookies and third-party software present.', 'MAP_txt' ) ); ?><br>
				<br>
				<?php echo wp_kses_post( __( 'Once you have finished browsing the main pages of your site, remember to set the Cookie Shield to "Live" mode.', 'MAP_txt' ) ); ?><br>
				<br>
				<?php echo wp_kses_post( __( 'Important: Cookie Shield may not function properly when used in conjunction with minification or caching plugins. It is recommended to temporarily disable them in your system configuration.', 'MAP_txt' ) ); ?><br>
				<br>
				<?php echo wp_kses_post( __( 'Please note: The Shield is regularly updated to detect as many cookies and third-party software as possible. However, it may not identify all the content that must be detected and blocked according to the regulations in your country. If you have any questions, please don\'t hesitate to <a href="https://www.myagileprivacy.com/en/contact-us/">contact us</a>.', 'MAP_txt' ) ); ?>
			</div>
		</div>


		<!-- scan mode select -->
		<div class="row mb-4">
			<label for="scan_mode_field" class="col-sm-5 col-form-label">
				<?php echo wp_kses_post( __( 'Scanner / Blocker Mode', 'MAP_txt' ) ); ?>
			</label>

			<div class="col-sm-7">

				<select
					id="scan_mode_field"
					name="scan_mode_field"
					class="hideShowInput form-control"
					data-hide-show-ref="map_scan_mode"
					style="max-width:100%;" >
					<?php

					$valid_options = array(
						'turned_off'				=>	array(  'label' => esc_attr( __( 'Scanner / Blocker OFF', 'MAP_txt' ) ),
																'selected' => false ),
						'learning_mode'				=>	array(  'label' => esc_attr( __( 'Learning Mode', 'MAP_txt' ) ),
																'selected' => false ),

						'config_finished'			=>	array(  'label' => esc_attr( __( 'Live Mode', 'MAP_txt' ) ),
																'selected' => false ),
					);

					$selected_value = $the_settings['scan_mode'];

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

			</div> <!-- /.col-sm-7 -->
		</div> <!-- row -->


		<?php if( $caller == 'genericOptionsWrapper'): ?>


			<div class="map_scan_mode displayNone" data-value="learning_mode config_finished">

				<!-- compatibility mode checkbox -->
				<div class="row mb-4">
					<label for="scanner_compatibility_mode_field" class="col-sm-5 col-form-label">
						<?php echo wp_kses_post( __( 'Enable compatibility mode', 'MAP_txt' ) ); ?>
					</label>
					<div class="col-sm-7">
						<div class="styled_radio d-inline-flex">
							<div class="round d-flex me-4">
								<input type="hidden" name="scanner_compatibility_mode_field" value="false"
									id="scanner_compatibility_mode_field_no">

								<input
									name="scanner_compatibility_mode_field"
									type="checkbox"
									value="true"
									id="scanner_compatibility_mode_field"
									<?php checked( $the_settings['scanner_compatibility_mode'], true ); ?>>

								<label for="scanner_compatibility_mode_field" class="me-2 label-checkbox"></label>

								<label for="scanner_compatibility_mode_field">
									<?php echo wp_kses_post( __( 'Enable compatibility mode', 'MAP_txt' ) ); ?>
								</label>
							</div>
						</div>

						<div class="form-text">
							<?php echo wp_kses_post( __( 'Enable this mode to maximize compatibility with themes and older plugins.', 'MAP_txt' ) ); ?>.
						</div>

					</div> <!-- /.col-sm-6 -->
				</div> <!-- row -->


				<?php if( $caller == 'genericOptionsWrapper') :  ?>


					<!-- forced_legacy_mode checkbox -->
					<div class="row mb-4">
						<label for="forced_legacy_mode_field" class="col-sm-5 col-form-label">
							<?php echo wp_kses_post( __( 'Legacy mode', 'MAP_txt' ) ); ?>
						</label>
						<div class="col-sm-7">
							<div class="styled_radio d-inline-flex">
								<div class="round d-flex me-4">

									<input type="hidden" name="forced_legacy_mode_field" value="false" id="forced_legacy_mode_field_no">

									<input
										name="forced_legacy_mode_field"
										type="checkbox" value="true"
										id="forced_legacy_mode_field"
										<?php checked( $the_settings['forced_legacy_mode'], true ); ?>>

									<label for="forced_legacy_mode_field" class="me-2 label-checkbox"></label>

									<label for="forced_legacy_mode_field">
										<?php echo wp_kses_post( __( 'Cookies are not being detected; enable "legacy mode."', 'MAP_txt' ) ); ?>
									</label>
								</div>
							</div>

							<div class="form-text">
								<?php echo wp_kses_post( __( 'In some cases, specific or older themes may require the activation of this setting. Enable this option if you continue to experience a failure in the preventive blocking of cookies', 'MAP_txt' ) ); ?>.
							</div>
						</div> <!-- /.col-sm-6 -->
					</div> <!-- row -->


					<!-- video advanced privacy checkbox -->
					<div class="row mb-4">
						<label for="video_advanced_privacy_field" class="col-sm-5 col-form-label">
							<?php echo wp_kses_post( __( 'Video advanced privacy', 'MAP_txt' ) ); ?>
						</label>
						<div class="col-sm-7">
							<div class="styled_radio d-inline-flex">
								<div class="round d-flex me-4">

									<input type="hidden" name="video_advanced_privacy_field" value="false"
										id="video_advanced_privacy_field_no">

									<input
										name="video_advanced_privacy_field"
										type="checkbox"
										value="true"
										id="video_advanced_privacy_field"
										<?php checked( $the_settings['video_advanced_privacy'], true ); ?>>

									<label for="video_advanced_privacy_field" class="me-2 label-checkbox"></label>

									<label for="video_advanced_privacy_field">
										<?php echo wp_kses_post( __( 'Video advanced privacy', 'MAP_txt' ) ); ?>
									</label>
								</div>
							</div>
							<div class="form-text">
								<?php echo wp_kses_post( __( 'Enabling this setting, YouTube and Vimeo videos will be made GDPR compliant by modifying the embedding URLs', 'MAP_txt' ) ); ?>.
							</div>
						</div> <!-- /.col-sm-6 -->
					</div> <!-- row -->

					<!-- youtube enforce privacy checkbox -->
					<div class="row mb-4">
						<label for="enforce_youtube_privacy_field" class="col-sm-5 col-form-label">
							<?php echo wp_kses_post( __( 'Enforce Youtube Privacy', 'MAP_txt' ) ); ?>
						</label>
						<div class="col-sm-7">
							<div class="styled_radio d-inline-flex">
								<div class="round d-flex me-4">

									<input type="hidden" name="enforce_youtube_privacy_field" value="false"
										id="enforce_youtube_privacy_field_no">

									<input
										name="enforce_youtube_privacy_field"
										type="checkbox"
										value="true"
										id="enforce_youtube_privacy_field"
										<?php checked( $the_settings['enforce_youtube_privacy'], true ); ?>>

									<label for="enforce_youtube_privacy_field" class="me-2 label-checkbox"></label>

									<label for="enforce_youtube_privacy_field">
										<?php echo wp_kses_post( __( 'Enforce Youtube Privacy', 'MAP_txt' ) ); ?>
									</label>
								</div>
							</div>
							<div class="form-text">
								<?php echo wp_kses_post( __( 'By enabling the Cookie Block technology for enhanced YouTube privacy, you will further reduce the number of cookies used by YouTube. However, please note that in some cases, videos may not be displayed correctly', 'MAP_txt' ) ); ?>.
							</div>
						</div> <!-- /.col-sm-6 -->
					</div> <!-- row -->

					<div class="row mb-4">
						<label for="dev_mode_field" class="col-sm-5 col-form-label">
							<?php echo wp_kses_post( __( 'Developer mode', 'MAP_txt' ) ); ?>
						</label>
						<div class="col-sm-7">
							<div class="styled_radio d-inline-flex">
								<div class="round d-flex me-4">

									<input type="hidden" name="dev_mode_field" value="false" id="dev_mode_field_no">

									<input
										class="hideShowInput"
										data-hide-show-ref="dev_mode_wrapper"
										name="dev_mode_field"
										type="checkbox"
										value="true"
										id="dev_mode_field"
										<?php checked( $the_settings['dev_mode'], true ); ?>>

									<label for="dev_mode_field" class="me-2 label-checkbox"></label>

									<label for="dev_mode_field">
										<?php echo wp_kses_post( __( 'Enable Developer mode', 'MAP_txt' ) ); ?>
									</label>
								</div>
							</div>

							<div class="form-text">
								<?php echo wp_kses_post( __( 'Please note: enabling developer mode may lead to unexpected behavior. Proceed only if you understand the risks, and change this setting only if requested by our support team.', 'MAP_txt' ) ); ?>.
							</div>
						</div> <!-- /.col-sm-6 -->
					</div> <!-- row -->

					<div class="dev_mode_wrapper displayNone">

						<!-- hook type select -->
						<div class="row mb-4">
							<label for="scanner_hook_type_field" class="col-sm-5 col-form-label">
								<?php echo wp_kses_post( __( 'Scanner Hook Type', 'MAP_txt' ) ); ?>
							</label>

							<div class="col-sm-7">

								<select
									id="scanner_hook_type_field"
									name="scanner_hook_type_field"
									class="form-control"
									style="max-width:100%;">
									<?php
										$valid_options = array(
											'init-shutdown'					=>	array(  'label' => esc_attr( __( 'Init / Shutdown', 'MAP_txt' ) ),
																					'selected' => false ),
											'template_redirect-shutdown'	=>	array(  'label' => esc_attr( __( 'Template Redirect / Shutdown', 'MAP_txt' ) ),
																					'selected' => false ),
										);
										$selected_value = $the_settings['scanner_hook_type'];

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

								<div class="form-text">
									<?php echo wp_kses_post( __( 'The type of hook is the technology used to "hook" and insert the functions of My Agile Privacy® for the preventive blocking. Change this setting only if requested by support', 'MAP_txt' ) ); ?>.
								</div>


							</div> <!-- /.col-sm-7 -->
						</div> <!-- row -->

						<!-- start hook priority -->
						<div class="row mb-4">
							<label for="scanner_start_hook_prio_field" class="col-sm-5 col-form-label">
								<?php echo wp_kses_post( __( 'Scanner Start Priority', 'MAP_txt' ) ); ?>
							</label>
							<div class="col-sm-7">

								<input
									type="text"
									class="form-control"
									id="scanner_start_hook_prio_field"
									name="scanner_start_hook_prio_field"
									value="<?php echo esc_attr( stripslashes( $the_settings['scanner_start_hook_prio'] ) ); ?>" />

								<div class="form-text">
									<?php echo wp_kses_post( __( 'Change this only if asked by customer support. (Default value: -10000).', 'MAP_txt' ) ); ?>
								</div>
							</div> <!-- /.col-sm-6 -->
						</div> <!-- row -->

						<!-- end hook priority -->
						<div class="row mb-4">
							<label for="scanner_end_hook_prio_field" class="col-sm-5 col-form-label">
								<?php echo wp_kses_post( __( 'Scanner End Priority', 'MAP_txt' ) ); ?>
							</label>
							<div class="col-sm-7">

								<input
									type="text"
									class="form-control"
									id="scanner_end_hook_prio_field"
									name="scanner_end_hook_prio_field"
									value="<?php echo esc_attr( stripslashes( $the_settings['scanner_end_hook_prio'] ) ); ?>" />

								<div class="form-text">
									<?php echo wp_kses_post( __( 'Change this only if asked by customer support. (Default value: -10000).', 'MAP_txt' ) ); ?>
								</div>
							</div> <!-- /.col-sm-6 -->
						</div> <!-- row -->


					</div>

				<?php endif; ?>


			</div> <!-- /.map_scan_mode -->

		<?php endif; ?>

	</div> <!-- consistent-box -->

</div> <!-- /.col-sm-8 -->