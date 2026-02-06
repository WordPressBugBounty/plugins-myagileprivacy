<?php

if( !defined( 'MAP_PLUGIN_NAME' ) )
{
	exit('Not allowed.');
}

?>

<div class="row">
	<div class="col-sm-8">

		<div class="consistent-box">
			<h4 class="mb-4">
				<i class="fa-solid fa-sliders-up"></i>
				<?php echo wp_kses_post( __( 'Advanced Settings', 'MAP_txt' ) ); ?>
			</h4>


			<div class="row mb-4">
				<label for="forced_auto_update_field" class="col-sm-5 col-form-label">
					<?php echo wp_kses_post( __( 'Enable plugin auto update', 'MAP_txt' ) ); ?>
				</label>

				<div class="col-sm-6">

					<div class="styled_radio d-inline-flex">
						<div class="round d-flex me-12">

							<input type="hidden" name="forced_auto_update_field" value="false" id="forced_auto_update_field_no">

							<input
								name="forced_auto_update_field"
								type="checkbox"
								value="true"
								id="forced_auto_update_field"
								<?php checked( $the_settings['forced_auto_update'], true ); ?>>

							<label for="forced_auto_update_field" class="me-3 label-checkbox"></label>

							<label for="forced_auto_update_field">
								<?php echo wp_kses_post( __( 'Yes, I would like to turn on automatic plugin updates.', 'MAP_txt' ) ); ?>
							</label>
						</div>
					</div> <!-- ./ styled_radio -->


				</div>
			</div> <!-- row -->


			<div class="row mb-4">
				<label for="enable_metadata_sync_field" class="col-sm-5 col-form-label">
					<?php  echo wp_kses_post( __( 'Cookie metadata Sync', 'MAP_txt' ) ); ?>
				</label>

				<div class="col-sm-6">

					<div class="styled_radio d-inline-flex">
						<div class="round d-flex me-12">

							<input type="hidden" name="enable_metadata_sync_field" value="false" id="enable_metadata_sync_field_no">

							<input
								name="enable_metadata_sync_field"
								type="checkbox"
								value="true"
								id="enable_metadata_sync_field"
								<?php checked( $the_settings['enable_metadata_sync'], true ); ?>>

							<label for="enable_metadata_sync_field" class="me-3 label-checkbox"></label>

							<label for="enable_metadata_sync_field">
								<?php  echo wp_kses_post( __( 'Yes, enable Cookie metadata synchronization.', 'MAP_txt' ) ); ?>
							</label>
						</div>
					</div> <!-- ./ styled_radio -->
					<div class="form-text">
						<?php  echo wp_kses_post( __( 'By enabling this feature, you will allow for automatic updates of settings related to the preemptive blocking of cookies, and you will achieve greater compliance in case of regulatory adjustments.', 'MAP_txt' ) ); ?>
					</div>

				</div>
			</div> <!-- row -->

			<?php

				$this_added_class_first = '';
				$this_added_class_second = 'd-none';

				if( $currentAndSupportedLanguages['with_multilang'] )
				{
					$this_added_class_first = 'd-none';
					$this_added_class_second = '';
				}

			?>

			<div class="row mb-4 <?php echo esc_attr( $this_added_class_first ) ; ?>">
				<label for="default_locale_field" class="col-sm-5 col-form-label">
					<?php  echo wp_kses_post( __( 'Language', 'MAP_txt' ) ); ?>
				</label>

				<div class="col-sm-7">

					<select
						id="default_locale_field"
						name="default_locale_field"
						class="form-control">
						<?php

							$valid_options = array();

							foreach( $currentAndSupportedLanguages['supported_languages'] as $this_language_key => $this_language_value )
							{
								$this_language_value['selected'] = false;

								$valid_options[ $this_language_key ] = $this_language_value;
							}

							$selected_value = $the_settings['default_locale'];

							if( isset( $valid_options[ $selected_value ] ) )
							{
								$valid_options[ $selected_value ]['selected'] = true;
							}

							foreach( $valid_options as $key => $data )
							{
								if( $data['selected'] )
								{
									?>
									<option value="<?php echo esc_attr( $key ); ?>" selected><?php echo esc_attr( $data['en_label'] ).' - '.esc_attr( $data['label'] ); ?></option>
									<?php
								}
								else
								{
									?>
									<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $data['en_label'] ).' - '.esc_attr( $data['label'] ); ?></option>
									<?php
								}
							}

						?>
					</select>
					
					<p class="mt-2 lh-sm"><em><?php _e('You can change the active languages on your plan from the <strong><a href="https://privatearea.myagileprivacy.com/" target="_blank">Private Area</a></strong>, under the <strong>My Subscription</strong> section, by clicking on <strong>Customize Languages</strong>.','MAP_txt'); ?></em></p>
				

				</div> <!-- /.col-sm-7 -->
			</div> <!-- row -->


			<div class="row mb-4 <?php echo esc_attr( $this_added_class_second ) ; ?>">

				<label for="enable_language_fallback_field" class="col-sm-5 col-form-label">
					<?php  echo wp_kses_post( __( 'Enable Fallback language', 'MAP_txt' ) ); ?>
				</label>

				<div class="col-sm-6">

					<div class="styled_radio d-inline-flex">
						<div class="round d-flex me-12">

							<input type="hidden" name="enable_language_fallback_field" value="false" id="enable_language_fallback_field_no">

							<input
								class="hideShowInput"
								data-hide-show-ref="language_fallback_locale"
								name="enable_language_fallback_field"
								type="checkbox"
								value="true"
								id="enable_language_fallback_field"
								<?php checked( $the_settings['enable_language_fallback'], true ); ?>>

							<label for="enable_language_fallback_field" class="me-3 label-checkbox"></label>

							<label for="enable_language_fallback_field">
								<?php  echo wp_kses_post( __( 'I would like to customize the default language fallback setting.', 'MAP_txt' ) ); ?>
							</label>
						</div>
					</div> <!-- ./ styled_radio -->
					<div class="form-text">
						<?php  echo wp_kses_post( __( 'By modifying this setting, you will change the default display language for texts and notifications in the event of an unsupported language.', 'MAP_txt' ) ); ?>
					</div>

				</div>

			</div>

			<div class="row mb-4 language_fallback_locale displayNone">


				<label for="default_locale_field" class="col-sm-5 col-form-label">
					<?php  echo wp_kses_post( __( 'Fallback language', 'MAP_txt' ) ); ?>
				</label>

				<div class="col-sm-7">

					<select
						id="language_fallback_locale_field"
						name="language_fallback_locale_field"
						class="form-control">
						<?php

							$valid_options = array();

							foreach( $currentAndSupportedLanguages['supported_languages'] as $this_language_key => $this_language_value )
							{
								$this_language_value['selected'] = false;

								$valid_options[ $this_language_key ] = $this_language_value;
							}

							$selected_value = $the_settings['language_fallback_locale'];

							if( isset( $valid_options[ $selected_value ] ) )
							{
								$valid_options[ $selected_value ]['selected'] = true;
							}

							foreach( $valid_options as $key => $data )
							{
								if( $data['selected'] )
								{
									?>
									<option value="<?php echo esc_attr( $key ); ?>" selected><?php echo esc_attr( $data['en_label'] ).' - '.esc_attr( $data['label'] ); ?></option>
									<?php
								}
								else
								{
									?>
									<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $data['en_label'] ).' - '.esc_attr( $data['label'] ); ?></option>
									<?php
								}
							}

						?>
					</select>

					<p class="mt-2 lh-sm"><em><?php _e('You can change the active languages on your plan from the <strong><a href="https://privatearea.myagileprivacy.com/" target="_blank">Private Area</a></strong>, under the <strong>My Subscription</strong> section, by clicking on <strong>Customize Languages</strong>.','MAP_txt'); ?></em></p>

				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->


			<!-- wrapping css textarea -->
			<div class="row mb-4">
				<label for="custom_css_field" class="col-sm-5 col-form-label">
					<?php  echo wp_kses_post( __( 'Custom Css', 'MAP_txt' ) ); ?>
				</label>

				<div class="col-sm-7">

					<div class="position-relative code-block-container">
						<textarea id="custom_css_field" name="custom_css_field" class="code-editor text_style" spellcheck="false"><?php echo apply_filters( 'format_to_edit', esc_attr( $the_settings['custom_css'] ) ); ?></textarea>

						<pre class="line-numbers code-viewer"><code class="language-css"><?php echo apply_filters( 'format_to_edit', esc_attr( $the_settings['custom_css'] ) ); ?></code></pre>
					</div>

					<div class="form-text">
						<?php  echo wp_kses_post( __( 'Enter your custom css', 'MAP_txt' ) ); ?>.
					</div>
				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->

			<!-- wrapping css checkbox -->
			<div class="row mb-4">
				<label for="wrap_shortcodes_field" class="col-sm-5 col-form-label">
					<?php  echo wp_kses_post( __( 'Enable policy wrapping for CSS customization purposes', 'MAP_txt' ) ); ?>
				</label>

				<div class="col-sm-7">
					<div class="styled_radio d-inline-flex">
						<div class="round d-flex me-4">

							<input type="hidden" name="wrap_shortcodes_field" value="false" id="wrap_shortcodes_field_no">

							<input
								name="wrap_shortcodes_field"
								type="checkbox"
								value="true"
								id="wrap_shortcodes_field"
								<?php checked( $the_settings['wrap_shortcodes'], true ); ?>>

							<label for="wrap_shortcodes_field" class="me-2 label-checkbox"></label>

							<label for="wrap_shortcodes_field">
								<?php echo wp_kses_post( __( 'Enable wrapping', 'MAP_txt' ) ); ?>
							</label>
						</div>
					</div> <!-- ./ styled_radio -->
					<div class="form-text">
						<?php  echo wp_kses_post( __( 'By enabling this feature, the textual content of the policies will become targetable by a CSS selector. The selector is .myagileprivacy_text_wrapper', 'MAP_txt' ) ); ?>.
					</div>

				</div> <!-- /.col-sm-6 -->


			</div> <!-- row -->


			<!-- force sync checkbox -->
			<div class="row mb-4">
				<label for="force_sync" class="col-sm-5 col-form-label">
					<?php  echo wp_kses_post( __( 'Force Cookies and Policy syncronization', 'MAP_txt' ) ); ?>
				</label>

				<div class="col-sm-7">
					<div class="styled_radio d-inline-flex">
						<div class="round d-flex me-4">

							<input
								name="force_sync"
								class="uncheck_on_send"
								type="checkbox"
								value="1"
								id="force_sync">

							<label for="force_sync" class="me-2 label-checkbox"></label>

							<label for="force_sync">
								<?php  echo wp_kses_post( __( 'Force Cookies and Policy syncronization', 'MAP_txt' ) ); ?>
							</label>
						</div>
					</div> <!-- ./ styled_radio -->

					<div class="form-text">
						<?php echo wp_kses_post( __( 'This will sync Cookies And Policy in the next five minute', 'MAP_txt' ) ); ?>.
					</div>

				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->

			<!-- reset settings checkbox -->
			<div class="row mb-4">
				<label for="reset_settings" class="col-sm-5 col-form-label">
					<?php  echo wp_kses_post( __( 'Reset all settings', 'MAP_txt' ) ); ?>
				</label>

				<div class="col-sm-7">
					<div class="styled_radio d-inline-flex">
						<div class="round d-flex me-4">

							<input
								class="uncheck_on_send"
								name="reset_settings"
								type="checkbox"
								value="1"
								id="reset_settings">

							<label for="reset_settings" class="me-2 label-checkbox"></label>

							<label for="reset_settings">
								<?php  echo wp_kses_post( __( 'Reset all settings', 'MAP_txt' ) ); ?>
							</label>
						</div>
					</div> <!-- ./ styled_radio -->

					<div class="form-text">
						<?php echo wp_kses_post( __( 'Warning: this will reset all the plugin settings.', 'MAP_txt' ) ); ?>
					</div>

				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->


		</div> <!-- consistent-box -->
	</div> <!-- /.col-sm-8 -->

	<div class="col-sm-4">
		<?php
			$tab = 'advanced';
			include 'inc.admin_sidebar.php';
		?>
	</div>
</div> <!-- /.row -->