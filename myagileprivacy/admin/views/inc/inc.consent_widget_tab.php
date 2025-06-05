<?php

if( !defined( 'MAP_PLUGIN_NAME' ) )
{
	exit('Not allowed.');
}

$caller = 'genericOptionsWrapper';

if( version_compare( $GLOBALS['wp_version'], '4.7', '<' ) )
{
	$locale = get_locale();
}
else
{
	$locale = get_user_locale();
}

?>

<div class="row">
	<div class="col-sm-8">

		<?php include 'fields.consent_mode_tab.php'; ?>

		<?php include 'fields.microsoft_consent_mode_tab.php'; ?>

		<?php

			$display_iab = false;

			if( defined( 'MAP_IAB_TCF' ) && MAP_IAB_TCF && isset( $rconfig ) && isset( $rconfig['allow_iab'] ) && $rconfig['allow_iab'] == 1 )
			{
				$display_iab = true;
			}

		?>

		<span class="translate-middle-y badge rounded-pill forbiddenWarningIAB bg-danger  <?php if( $display_iab ){echo 'd-none';} ?>">
			<small>

				<?php

					if( isset( $the_settings ) &&
					isset( $the_settings['pa'] ) &&
					$the_settings['pa'] == 1 )
					{
						echo wp_kses_post( __( 'Feature not available for your license', 'MAP_txt' ) );
					}
					else
					{
						echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) );
					}
				?>

			</small>
		</span>

		<div class="consistent-box <?php if( !$display_iab ){echo 'forbiddenAreaIAB';} ?>">

			<h4 class="mb-4">
				<i class="fa-regular fa-tablet-screen"></i>
				<?php echo wp_kses_post( __( 'Activate IAB Transparency and Consent Framework', 'MAP_txt' ) ); ?>
			</h4>

			<div class="row mb-4">
				<div class="col-sm-12">

					<p><?php echo wp_kses_post( __( 'The IAB TCF, which stands for Interactive Advertising Bureau Transparency and Consent Framework, is a standardized framework specifically developed to help businesses comply with data protection regulations.', 'MAP_txt' ) ); ?>

					<?php echo wp_kses_post( __( 'It serves as a mechanism that allows websites and digital advertising platforms to effectively obtain and manage user consent for the processing of personal data.', 'MAP_txt' ) ); ?><br>
					<b><?php echo wp_kses_post( __( 'It is highly recommended to enable the IAB TCF if you use ad channel banners such as Google Adsense, Ad Manager, AdMob, or similar platforms on your website.', 'MAP_txt' ) ); ?></b></p>
				</div>
			</div>

			<div class="row mb-4">
				<label for="display_ccpa_field" class="col-sm-5 col-form-label">
					<?php echo wp_kses_post( __( 'Activate IAB TCF', 'MAP_txt' ) ); ?>
				</label>

				<div class="col-sm-7">
					<div class="styled_radio d-inline-flex">
						<div class="round d-flex me-4">

							<input type="hidden" name="enable_iab_tcf_field" value="false" id="enable_iab_tcf_field_no" data-preview="iab">

							<input name="enable_iab_tcf_field" type="checkbox" value="true" id="enable_iab_tcf_field" <?php checked( $the_settings['enable_iab_tcf'], true); ?> data-preview="iab">

							<label for="enable_iab_tcf_field" class="me-2 label-checkbox"></label>

							<label for="enable_iab_tcf_field">
								<?php echo wp_kses_post( __( 'Yes, enable. I run Google Adsense, Ad Manager, AdMob or similar platforms on my website and I would to activate the IAB Transparency and Consent Framework', 'MAP_txt' ) ); ?>
							</label>
						</div>
					</div> <!-- ./ styled_radio -->

				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->

		</div>

		<div class="consistent-box">
			<h4 class="mb-4">
				<i class="fa-regular fa-tablet-screen"></i>
				<?php echo wp_kses_post( __( 'Consent Widget', 'MAP_txt' ) ); ?>
			</h4>

			<!-- widget review consent show -->
			<div class="row mb-4">
				<label for="showagain_tab_field" class="col-sm-5 col-form-label">
					<?php echo wp_kses_post( __( 'Enable revisit consent widget', 'MAP_txt' ) ); ?>
				</label>

				<div class="col-sm-7">
					<div class="styled_radio d-inline-flex">
						<div class="round d-flex me-4">

							<input type="hidden" name="showagain_tab_field" value="false" id="showagain_tab_field_no">

							<input name="showagain_tab_field" type="checkbox" value="true" id="showagain_tab_field" class="hideShowInput" data-hide-show-ref="showagain_tab" <?php checked( $the_settings['showagain_tab'], true); ?>>

							<label for="showagain_tab_field" class="me-2 label-checkbox"></label>

							<label for="showagain_tab_field">
								<?php echo wp_kses_post( __( 'Enable revisit consent widget', 'MAP_txt' ) ); ?>
							</label>
						</div>
					</div> <!-- ./ styled_radio -->

					<div class="form-text">
						<?php echo wp_kses_post( __( 'Warning: if you disable this, add the proper link for consent revisit in the foote area, in order to stay GDPR compliant. You can use the [myagileprivacy_showconsent] shortcode.', 'MAP_txt' ) ); ?>
					</div>

				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->

			<div class="showagain_tab displayNone">

				<!-- widget position -->
				<div class="row mb-4">
					<label for="notify_position_horizontal_field" class="col-sm-5 col-form-label">
						<?php echo wp_kses_post( __( 'Tab Position', 'MAP_txt' ) ); ?>
					</label>

					<div class="col-sm-7">
						<select id="notify_position_horizontal_field" name="notify_position_horizontal_field" class="form-control">
						<?php

						$valid_options = array(
							'right'	=>	array(  'label' => esc_attr( __( 'Right', 'MAP_txt' ) ),
																	'selected' => false ),
							'left'	=>	array(  'label' => esc_attr( __( 'Left', 'MAP_txt' ) ),
																	'selected' => false ),
						);

						$selected_value = $the_settings['notify_position_horizontal'];

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
								<option value="<?php echo esc_attr($key)?>"><?php echo esc_attr($data['label'])?></option>
								<?php
							}
						}
						?>
						</select>

						<div class="form-text">
							<?php
								echo wp_kses_post( __( "Select the horizontal position where to show the consent again widget ", 'MAP_txt' ) );
							?>.
						</div>
					</div> <!-- /.col-sm-6 -->
				</div> <!-- row -->

				<!-- widget review consent text -->
				<div class="row mb-4">
					<label for="showagain_text" class="col-sm-5 col-form-label">
						<?php echo wp_kses_post( __( 'Title for show again policy', 'MAP_txt' ) ); ?>
						<a href="<?php echo esc_url( $translation_menu_link ); ?>"><i class="fa-regular fa-comment-pen" data-bs-toggle="tooltip" data-bs-html="true" title="<?php esc_attr_e('You can edit this text from the Texts and Translations section.', 'MAP_txt' ); ?>"></i></a>
					</label>

					<div class="col-sm-7">
						<input type="text" class="form-control" id="showagain_text" name="" value="<?php echo esc_attr( $the_translations[$selected_lang]['manage_consent']) ?>" readonly />

					</div> <!-- /.col-sm-6 -->
				</div> <!-- row -->

				<!-- widget show cookie policy link -->
				<div class="row mb-4">
					<label for="cookie_policy_link_field" class="col-sm-5 col-form-label">
						<?php echo wp_kses_post( __( 'Show Cookie Policy link', 'MAP_txt' ) ); ?>
					</label>

					<div class="col-sm-7">
						<div class="styled_radio d-inline-flex">
							<div class="round d-flex me-4">
								<input type="hidden" name="cookie_policy_link_field" value="false" id="cookie_policy_link_field_no">

								<input name="cookie_policy_link_field" type="checkbox" value="true" id="cookie_policy_link_field" <?php checked( $the_settings['cookie_policy_link'], true); ?>>
								<label for="cookie_policy_link_field" class="me-2 label-checkbox"></label>

								<label for="cookie_policy_link_field">
									<?php echo wp_kses_post( __( 'Show Cookie Policy link', 'MAP_txt' ) ); ?>
								</label>

							</div>
						</div> <!-- ./ styled_radio -->
					</div> <!-- /.col-sm-6 -->
				</div> <!-- row -->

				<!-- widget logo show / hide -->
				<div class="row mb-4">
					<label for="disable_logo_field" class="col-sm-5 col-form-label">
						<?php echo wp_kses_post( __( 'Disable My Agile Privacy logo', 'MAP_txt' ) ); ?>
					</label>

					<div class="col-sm-7">
						<div class="styled_radio d-inline-flex">
							<div class="round d-flex me-4">

								<input type="hidden" name="disable_logo_field" value="false" id="disable_logo_field_no">

								<input name="disable_logo_field" type="checkbox" value="true" id="disable_logo_field" <?php checked($the_settings['disable_logo'], true); ?>>

								<label for="disable_logo_field" class="me-2 label-checkbox"></label>

								<label for="disable_logo_field">
									<?php echo wp_kses_post( __( 'Disable logo on Cookie Bar', 'MAP_txt' ) ); ?>
								</label>
							</div>
						</div> <!-- ./ styled_radio -->

						<div class="form-text">
							<?php echo wp_kses_post( __( 'Check this option to remove My Agile Privacy logo on the consent review widget', 'MAP_txt' ) ); ?>.
						</div>

					</div> <!-- /.col-sm-6 -->
				</div> <!-- row -->

			</div>

		</div> <!-- consistent-box -->


		<div class="consistent-box">
			<h4 class="mb-4">
				<i class="fa-regular fa-tablet-screen"></i>
				<?php echo wp_kses_post( __( 'Reset given consent', 'MAP_txt' ) ); ?>
			</h4>


			<!-- reset consent checkbox -->
			<div class="row mb-4">
				<label for="reset_consent" class="col-sm-5 col-form-label">
					<?php echo wp_kses_post( __( 'Reset given consent', 'MAP_txt' ) ); ?>
				</label>

				<div class="col-sm-7">
					<div class="styled_radio d-inline-flex">
						<div class="round d-flex me-4">

							<input class="uncheck_on_send" name="reset_consent" type="checkbox" value="1" id="reset_consent">

							<label for="reset_consent" class="me-2 label-checkbox"></label>

							<label for="reset_consent">
								<?php echo wp_kses_post( __( 'Do reset given consent', 'MAP_txt' ) ); ?>
							</label>
						</div>
					</div> <!-- ./ styled_radio -->

					<div class="form-text">
					<?php echo wp_kses_post( __( 'Warning: this will reset all the user consent given. Use this only if you change your cookie list and you would like to ask consent again.', 'MAP_txt' ) ); ?>
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