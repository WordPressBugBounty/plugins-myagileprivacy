<?php

	if( !defined( 'MAP_PLUGIN_NAME' ) )
	{
		exit('Not allowed.');
	}

?>

<input type="hidden" name="is_bottom_field" value="">

<div class="row" id="cookie_banner_options_container">
	<div class="col-sm-8">

		<div class="consistent-box">
			<h4 class="mb-4">
				<i class="fa-regular fa-browser"></i>
				<?php _e('Cookie Banner', 'MAP_txt'); ?>
			</h4>

			<!-- show banner -->
			<div class="row mb-4">
				<label for="is_on_field_yes" class="col-sm-5 col-form-label">
					<?php _e('Enable Cookie Banner', 'MAP_txt'); ?>
				</label>

				<div class="col-sm-7">
					<div class="styled_radio d-inline-flex">
						<div class="round d-flex me-4">
							<?php if( $the_options['is_on'] == true ): ?>
								<input type="radio" id="is_on_field_yes" name="is_on_field" value="true" checked="checked" />
								<label for="is_on_field_yes" class="me-2 label-radio"></label>

								<label for="is_on_field_yes"><?php _e('On', 'MAP_txt'); ?></label>

							<?php else: ?>
								<input type="radio" id="is_on_field_yes" name="is_on_field" value="true" />
								<label for="is_on_field_yes" class="me-2 label-radio"></label>

								<label for="is_on_field_yes"><?php _e('On', 'MAP_txt'); ?></label>
							<?php endif; ?>

						</div>

						<div class="round d-flex">
							<?php if( $the_options['is_on'] == false ): ?>
								<input type="radio" id="is_on_field_no" name="is_on_field" value="false" checked="checked" />
								<label for="is_on_field_no" class="me-2 label-radio"></label>

								<label for="is_on_field_no"><?php _e('Off', 'MAP_txt'); ?></label>
							<?php else: ?>
								<input type="radio" id="is_on_field_no" name="is_on_field" value="false" />
								<label for="is_on_field_no" class="me-2 label-radio"></label>

								<label for="is_on_field_no"><?php _e('Off', 'MAP_txt'); ?></label>

							<?php endif; ?>
						</div>
					</div> <!-- ./ styled_radio -->
					<div class="form-text">
						<?php
							_e("Choose if the cookie banner should be visible on the website", 'MAP_txt');
						?>.
					</div>
				</div> <!-- /.col-sm-6 -->

			</div> <!-- row -->

			<!-- NEW banner width -->
			<div class="row mb-4">
				<label for="cookie_banner_size_field" class="col-sm-5 col-form-label">
					<?php _e('Banner Size', 'MAP_txt'); ?>
				</label>
				<div class="col-sm-7">
					<select name="cookie_banner_size_field" class="form-control hideShowInput" id="cookie_banner_size_field" data-preview="mapSize" data-hide-show-ref="cookie_banner_size">
						<option value="sizeWide" <?php selected( $the_options['cookie_banner_size'], 'sizeWide' ); ?>><?php _e('Wide', 'MAP_txt'); ?></option>
						<option value="sizeBig" <?php selected( $the_options['cookie_banner_size'], 'sizeBig' ); ?>><?php _e('Big', 'MAP_txt'); ?></option>
						<option value="sizeBoxed" <?php selected( $the_options['cookie_banner_size'], 'sizeBoxed' ); ?>><?php _e('Boxed', 'MAP_txt'); ?></option>
					</select>
				</div> <!-- col-sm-7 -->
			</div> <!-- row -->

			<div class="row mb-4">
				<label for="cookie_banner_vertical_position_field" class="col-sm-5 col-form-label">
					<?php _e('Vertical Banner Position', 'MAP_txt'); ?>
				</label>
				<div class="col-sm-7">
					<select name="cookie_banner_vertical_position_field" class="form-control" id="cookie_banner_vertical_position_field" data-preview="mapPosition">

						<option value="Top" <?php selected( $the_options['cookie_banner_vertical_position'], 'Top' ); ?>><?php _e('Top', 'MAP_txt'); ?></option>
						<option value="Center" <?php selected( $the_options['cookie_banner_vertical_position'], 'Center' ); ?>><?php _e('Center', 'MAP_txt'); ?></option>
						<option value="Bottom" <?php selected( $the_options['cookie_banner_vertical_position'], 'Bottom' ); ?>><?php _e('Bottom', 'MAP_txt'); ?></option>
					</select>
				</div> <!-- col-sm-7 -->
			</div> <!-- row -->

			<div class="cookie_banner_size displayNone" data-value="sizeBig sizeBoxed">

				<div class="row mb-4">
					<label for="cookie_banner_horizontal_position_field" class="col-sm-5 col-form-label">
						<?php _e('Horizontal Banner Position', 'MAP_txt'); ?>
					</label>
					<div class="col-sm-7">
						<select name="cookie_banner_horizontal_position_field" class="form-control" id="cookie_banner_horizontal_position_field" data-preview="mapPosition">

							<option value="Center" <?php selected( $the_options['cookie_banner_horizontal_position'], 'Center' ); ?>><?php _e('Center', 'MAP_txt'); ?></option>
							<option value="Left" <?php selected( $the_options['cookie_banner_horizontal_position'], 'Left' ); ?>><?php _e('Left', 'MAP_txt'); ?></option>
							<option value="Right" <?php selected( $the_options['cookie_banner_horizontal_position'], 'Right' ); ?>><?php _e('Right', 'MAP_txt'); ?></option>
						</select>
					</div> <!-- col-sm-7 -->
				</div> <!-- row -->

			</div>

			<!-- NEW banner position -->

			<div class="row mb-4">
				<label for="floating_banner_field" class="col-sm-5 col-form-label">
					<?php _e('Floating Banner', 'MAP_txt'); ?>
				</label>

				<div class="col-sm-7">
					<select name="floating_banner_field" class="form-control" id="floating_banner_field" data-preview="floating_banner">
						<option value="0" <?php selected( $the_options['floating_banner'], 0 ); ?>>No</option>
						<option value="1" <?php selected( $the_options['floating_banner'], 1 ); ?>><?php _e('Yes', 'MAP_txt'); ?></option>
					</select>
				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->


			<!-- NEW banner shadow -->
			<div class="row mb-4">
				<label for="cookie_banner_shadow_field" class="col-sm-5 col-form-label">
					<?php _e('Cookie Banner Shadow', 'MAP_txt'); ?>
				</label>
				<div class="col-sm-7">
					<select name="cookie_banner_shadow_field" class="form-control" id="cookie_banner_shadow_field" data-preview="shadow">
						<option value="false" <?php selected( $the_options['cookie_banner_shadow'], 'false' ); ?>><?php _e('None', 'MAP_txt'); ?></option>

						<option value="map-shadow-soft" <?php selected( $the_options['cookie_banner_shadow'], 'map-shadow-soft' ); ?>><?php _e('Soft', 'MAP_txt'); ?></option>

						<option value="map-shadow-hard" <?php selected( $the_options['cookie_banner_shadow'], 'map-shadow-hard' ); ?>><?php _e('Strong', 'MAP_txt'); ?></option>
					</select>

					<div class="form-text">
						<?php _e('Select the shadow effect for the cookie banner', 'MAP_txt'); ?>.
					</div>
				</div> <!-- col-sm-7 -->
			</div> <!-- row -->

			<!-- NEW banner radius -->
			<div class="row mb-4">
				<label for="elements_border_radius_field" class="col-sm-5 col-form-label">
					<?php _e('Elements Border Radius', 'MAP_txt'); ?>
				</label>
				<div class="col-sm-7">
				<div class="input-group">
				<input type="number" min="0" max="50" data-preview="border_radius" class="form-control" id="elements_border_radius_field" name="elements_border_radius_field" value="<?php echo esc_attr( stripslashes( $the_options['elements_border_radius'] ) )  ?>" />
					<span class="input-group-text">pixel</span>
				</div>

					<div class="form-text">
						<?php _e('Insert the border radius value for cookie banner and buttons, in pixel', 'MAP_txt'); ?>
					</div>
				</div> <!-- col-sm-7 -->
			</div> <!-- row -->

			<!-- NEW banner width -->
			<div class="row mb-4">
				<label for="cookie_banner_animation_field" class="col-sm-5 col-form-label">
					<?php _e('Banner Animation Effect', 'MAP_txt'); ?>
				</label>
				<div class="col-sm-7">
				<select name="cookie_banner_animation_field" class="form-control" id="cookie_banner_animation_field">
					<option value="none" <?php selected( $the_options['cookie_banner_animation'], 'none' ); ?>><?php _e('None', 'MAP_txt'); ?></option>
					<option value="slide" <?php selected( $the_options['cookie_banner_animation'], 'slide' ); ?>><?php _e('Slide', 'MAP_txt'); ?></option>
					<option value="fade" <?php selected( $the_options['cookie_banner_animation'], 'fade' ); ?>><?php _e('Fade', 'MAP_txt'); ?></option>
				</select>
				</div> <!-- col-sm-7 -->
			</div> <!-- row -->

			<!-- NEW banner title -->
			<div class="row mb-4">
				<label for="title_is_on_field_yes" class="col-sm-5 col-form-label">
				<?php _e('Enable banner heading', 'MAP_txt'); ?>
				</label>

				<div class="col-sm-7">
					<div class="styled_radio d-inline-flex">
						<div class="round d-flex me-4">
							<?php if( $the_options['title_is_on'] == true ): ?>
								<input type="radio" id="title_is_on_field_yes" class="hideShowInput" name="title_is_on_field" value="true" checked="checked" data-hide-show-ref="show_banner_title" data-meaning="1" data-preview="bannerTitle" />
								<label for="title_is_on_field_yes" class="me-2 label-radio"></label>

								<label for="title_is_on_field_yes">
									<?php _e('On', 'MAP_txt'); ?>
								</label>


							<?php else: ?>
								<input type="radio" id="title_is_on_field_yes" class="hideShowInput" name="title_is_on_field" value="true" data-hide-show-ref="show_banner_title" data-meaning="1" data-preview="bannerTitle" />
								<label for="title_is_on_field_yes" class="me-2 label-radio"></label>

								<label for="title_is_on_field_yes">
									<?php _e('On', 'MAP_txt'); ?>
								</label>

							<?php endif; ?>

						</div>

						<div class="round d-flex">
							<?php if( $the_options['title_is_on'] == false ): ?>
								<input type="radio" id="title_is_on_field_no" class="hideShowInput" name="title_is_on_field" value="false" checked="checked" data-hide-show-ref="show_banner_title" data-meaning="0" data-preview="bannerTitle" />
								<label for="title_is_on_field_no" class="me-2 label-radio"></label>

								<label for="title_is_on_field_no">
									<?php _e('Off', 'MAP_txt'); ?>
								</label>
							<?php else: ?>
								<input type="radio" id="title_is_on_field_no" class="hideShowInput" name="title_is_on_field" value="false" data-hide-show-ref="show_banner_title" data-meaning="0"  data-preview="bannerTitle" />
								<label for="title_is_on_field_no" class="me-2 label-radio"></label>

								<label for="title_is_on_field_no">
									<?php _e('Off', 'MAP_txt'); ?>
								</label>
							<?php endif; ?>
						</div>
					</div> <!-- ./ styled_radio -->
					<div class="form-text">
						<?php
							_e("Choose if the banner title should be visible", 'MAP_txt');
						?>.
					</div>
				</div> <!-- /.col-sm-6 -->

			</div> <!-- row -->
			<!-- banner title -->
			<div class="row mb-4 show_banner_title">
				<label for="bar_heading_text_field" class="col-sm-5 col-form-label">
					<?php _e('Banner title', 'MAP_txt'); ?>
					<a href="<?php echo esc_url( $translation_menu_link ); ?>"><i class="fa-regular fa-comment-pen" data-bs-toggle="tooltip" data-bs-html="true" title="<?php _e('You can edit this text from the Texts and Translations section.', 'MAP_txt'); ?>"></i></a>

				</label>

				<div class="col-sm-7">
					<input type="text" data-preview="title_text" class="form-control" id="bar_heading_text_field" name="" value="<?php echo esc_attr( $the_translations[$selected_lang]['banner_title'] ); ?>" readonly />
				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->


			<div class="row mb-4">
				<label for="notify_message_v2_field" class="col-sm-5 col-form-label">
					<?php _e('Message', 'MAP_txt'); ?>
					<a href="<?php echo esc_url( $translation_menu_link ); ?>"><i class="fa-regular fa-comment-pen" data-bs-toggle="tooltip" data-bs-html="true" title="<?php _e('You can edit this text from the Texts and Translations section.', 'MAP_txt'); ?>"></i></a>
				</label>

				<div class="col-sm-7">

					<?php
						echo '<textarea id="notify_message_v2_field" name="" class="form-control text_style" rows="4" style="font-size:12px;" readonly>';
						echo apply_filters( 'format_to_edit', esc_attr( stripslashes( $the_translations[$selected_lang]['notify_message_v2'] ) ) ) . '</textarea>';
					?>


					<div class="form-text d-none">
						<?php _e('Example code:', 'MAP_txt'); ?>
						<br>
						<textarea class="text_style form-control" rows="9" style="font-size:12px" disabled><?php _e('This website uses technical and profiling cookies. Clicking on "Accept" authorises all profiling cookies. Clicking on "Refuse" or the X will refuse all profiling cookies. By clicking on "Customise" you can select which profiling cookies to activate.[myagileprivacy_extra_info]', 'MAP_txt'); ?></textarea>
					</div>

				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->


			<!-- banner flat css-->
			<div class="row mb-4 d-none">
				<label for="with_css_effects_field" class="col-sm-5 col-form-label">
					<?php _e('Enable shadows and rounding effects', 'MAP_txt'); ?>
				</label>

				<div class="col-sm-7">
					<div class="styled_radio d-inline-flex">
						<div class="round d-flex me-4">
							<input type="hidden" name="with_css_effects_field" value="false" id="with_css_effects_field_no">

							<input name="with_css_effects_field" type="checkbox" value="true" id="with_css_effects_field" <?php checked($the_options['with_css_effects'], true); ?>>
							<label for="with_css_effects_field" class="me-2 label-checkbox"></label><?php _e('Enable shadows and rounding effects', 'MAP_txt'); ?>

						</div>
					</div> <!-- ./ styled_radio -->
				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->

			<!-- NEW font size banner -->
			<div class="row mb-4">
				<label for="text_size_field" class="col-sm-5 col-form-label">
					<?php _e('Cookie Banner Font Size', 'MAP_txt'); ?>
				</label>
				<div class="col-sm-7">
				<div class="input-group">
				<input type="number" min="4" max="50" class="form-control" id="text_size_field" name="text_size_field" value="<?php echo esc_attr( stripslashes( $the_options['text_size'] ) ); ?>" />
					<span class="input-group-text">pixel</span>
				</div>


					<div class="form-text">
						<?php _e('Insert the font size for the cookie banner heading, text buttons and for the blocked content notification bar text, in pixel <small>(suggested value: 18)</small>', 'MAP_txt'); ?>.
					</div>
				</div> <!-- col-sm-7 -->
			</div> <!-- row -->

			<!-- NEW line height banner -->
			<div class="row mb-4">
				<label for="text_lineheight_field" class="col-sm-5 col-form-label">
					<?php _e('Cookie Banner Line Height', 'MAP_txt'); ?>
				</label>
				<div class="col-sm-7">
				<div class="input-group">
				<input type="number" min="0" max="100" class="form-control" id="text_lineheight_field" name="text_lineheight_field" value="<?php echo esc_attr( stripslashes( $the_options['text_lineheight'] ) ); ?>" />
					<span class="input-group-text">pixel</span>
				</div>


					<div class="form-text">
						<?php _e('Insert the line height for the cookie banner heading, text buttons and for the blocked content notification bar text, in pixel <small>(suggested value: 30)</small>', 'MAP_txt'); ?>.
					</div>
				</div> <!-- col-sm-7 -->
			</div> <!-- row -->

			<!-- banner color presets -->
			<div class="row mb-4">
				<label for="color_preset" class="col-sm-5 col-form-label">
					<?php _e('Color Preset', 'MAP_txt'); ?>
				</label>

				<div class="col-sm-7">
					<select name="color_preset" class="form-control" id="color_preset">
						<option value="none"><?php _e('Select a preset', 'MAP_txt'); ?></option>
						<option value="light"><?php _e('Light', 'MAP_txt'); ?></option>
						<option value="dark"><?php _e('Dark', 'MAP_txt'); ?></option>
						<option value="parchment"><?php _e('Ancient Parchment', 'MAP_txt'); ?></option>
						<option value="wintersky"><?php _e('Winter Sky', 'MAP_txt'); ?></option>
						<option value="mistyforest"><?php _e('Misty Forest', 'MAP_txt'); ?></option>
						<option value="greentea"><?php _e('Green Tea', 'MAP_txt'); ?></option>
						<option value="lavender"><?php _e('Lavender Blooms', 'MAP_txt'); ?></option>
					</select>

					<div class="form-text">
						<?php
							_e("Select a color preset for the cookie banner, or customize by your own", 'MAP_txt');
						?>.
					</div>
				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->


			<!-- banner background color -->
			<div class="row mb-4">
				<label for="background_field" class="col-sm-5 col-form-label">
					<?php _e('Cookie Banner Color', 'MAP_txt'); ?>
				</label>

				<div class="col-sm-7">

					<?php
						echo wp_kses( '<input type="color" id="background_field" name="background_field" value="' . esc_attr( $the_options['background'] ) . '" data-default-color="#ffffff" data-preview="bg_color">', MyAgilePrivacy::allowed_html_tags() );
					?>

					<div class="form-text">
						<?php
							_e("Select the background color of the cookie banner", 'MAP_txt');
						?>.
					</div>
				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->

			<div class="show_banner_title">
				<!-- heading background color -->
				<div class="row mb-4">
					<label for="heading_background_color_field" class="col-sm-5 col-form-label">
						<?php _e('Heading background Color', 'MAP_txt'); ?>
					</label>
					<div class="col-sm-7">
						<?php
							echo wp_kses( '<input type="color" id="heading_background_color_field" name="heading_background_color_field" value="' . esc_attr( $the_options['heading_background_color'] ) . '" data-default-color="#F14307" data-preview="title_background_color">', MyAgilePrivacy::allowed_html_tags() );
						?>
						<div class="form-text">
							<?php
								_e("Select the text color of the cookie banner", 'MAP_txt');
							?>.
						</div>
					</div> <!-- /.col-sm-6 -->
				</div> <!-- row -->
				<!-- heading text color -->
				<div class="row mb-4">
					<label for="heading_text_color_field" class="col-sm-5 col-form-label">
						<?php _e('Heading text Color', 'MAP_txt'); ?>
					</label>
					<div class="col-sm-7">
						<?php
							echo wp_kses( '<input type="color" id="heading_text_color_field" name="heading_text_color_field" value="' . esc_attr( $the_options['heading_text_color'] ) . '" data-default-color="#ffffff" data-preview="title_color">', MyAgilePrivacy::allowed_html_tags() );
						?>
						<div class="form-text">
							<?php
								_e("Select the text color of the cookie banner", 'MAP_txt');
							?>.
						</div>
					</div> <!-- /.col-sm-6 -->
				</div> <!-- row -->
			</div>

			<!-- banner text color -->
			<div class="row mb-4">
				<label for="text_field" class="col-sm-5 col-form-label">
					<?php _e('Text Color', 'MAP_txt'); ?>
				</label>

				<div class="col-sm-7">
					<?php
						echo wp_kses( '<input type="color" id="text_field" name="text_field" value="' . esc_attr( $the_options['text'] ) . '" data-default-color="#000" data-preview="text_color">', MyAgilePrivacy::allowed_html_tags() );
					?>

					<div class="form-text">
						<?php
							_e("Select the text color of the cookie banner", 'MAP_txt');
						?>.
					</div>
				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->



		</div> <!-- consistent-box -->

		<div class="consistent-box">
			<h4 class="mb-4">
				<i class="fa-regular fa-browser"></i>
				<?php _e('Buttons Customization', 'MAP_txt'); ?>
			</h4>

			<div id="map_buttons_background_alert" class="alert alert-warning d-none" role="alert">
				<p>

					<strong><?php

						_e('Attention: We strongly recommend keeping the background colors of the buttons aligned and identical.', 'MAP_txt');
					?></strong>

					<br>

					<?php

						_e('Using different colors might be perceived as a "dark pattern," potentially misleading users and conflicting with privacy regulations. To ensure the highest level of compliance for your site, please consider using the same background color.', 'MAP_txt');
					?>

				</p>

				<p class="mb-0">
					<?php

						_e('You can use the default color settings or <a href="#" role="button" class="standardize_colors_button">click here to standardize the colors</a>.', 'MAP_txt');
					?>
				</p>
			</div>

			<div class="row mb-4">
				<label for="is_on_field_yes" class="col-sm-5 col-form-label">
				<?php _e('Show Buttons icon', 'MAP_txt'); ?>
				</label>

				<div class="col-sm-7">
					<div class="styled_radio d-inline-flex">
						<div class="round d-flex me-4">
							<?php if( $the_options['show_buttons_icons'] == true ): ?>
								<input type="radio" id="show_buttons_icons_field_yes" name="show_buttons_icons_field" value="true" checked="checked" data-meaning="1" data-preview="button_icon" />
								<label for="show_buttons_icons_field_yes" class="me-2 label-radio"></label>

								<label for="show_buttons_icons_field_yes">
									<?php _e('Yes', 'MAP_txt'); ?>
								</label>
							<?php else: ?>
								<input type="radio" id="show_buttons_icons_field_yes" name="show_buttons_icons_field" value="true" data-meaning="1" data-preview="button_icon" />
								<label for="show_buttons_icons_field_yes" class="me-2 label-radio"></label>

								<label for="show_buttons_icons_field_yes">
									<?php _e('Yes', 'MAP_txt'); ?>
								</label>
							<?php endif; ?>

						</div>

						<div class="round d-flex">
							<?php if( $the_options['show_buttons_icons'] == false ): ?>
								<input type="radio" id="show_buttons_icons_field_no" name="show_buttons_icons_field" value="false" checked="checked" data-meaning="0" data-preview="button_icon" />
								<label for="show_buttons_icons_field_no" class="me-2 label-radio"></label>

								<label for="show_buttons_icons_field_no">
									<?php _e('No', 'MAP_txt'); ?>
								</label>

							<?php else: ?>
								<input type="radio" id="show_buttons_icons_field_no" name="show_buttons_icons_field" value="false" data-meaning="0" data-preview="button_icon" />
								<label for="show_buttons_icons_field_no" class="me-2 label-radio"></label>

								<label for="show_buttons_icons_field_no">
									<?php _e('No', 'MAP_txt'); ?>
								</label>

							<?php endif; ?>
						</div>
					</div> <!-- ./ styled_radio -->
				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->

			<div class="card fullwidth">
				<div class="row mb-3">
					<div class="col-sm-12">
						<h6 class="h5"><?php _e('Accept button', 'MAP_txt'); ?></h6>
					</div>
				</div>

				<div class="row">
					<div class="col-sm-4 text-center">
						<div>
							<label for="button_accept_text_field">
								<strong><?php _e('Text', 'MAP_txt'); ?></strong>
								<a href="<?php echo esc_url( $translation_menu_link ); ?>"><i class="fa-regular fa-comment-pen" data-bs-toggle="tooltip" data-bs-html="true" title="<?php _e('You can edit this text from the Texts and Translations section.', 'MAP_txt'); ?>"></i></a>
						</label>
						</div>
						<input type="text" class="form-control" id="button_accept_text_field" name="" data-preview="accept-text" value="<?php echo esc_attr( $the_translations[$selected_lang]['accept'] ); ?>" readonly />
					</div>

					<div class="col-sm-4 text-center">
						<div>
							<label for="button_accept_link_color_field"><strong><?php _e('Text colour', 'MAP_txt'); ?></strong></label></div>
						<?php
							echo wp_kses( '<input type="color" id="button_accept_link_color_field" name="button_accept_link_color_field" data-preview="accept-text-color" value="' . esc_attr( $the_options['button_accept_link_color'] ) . '" data-default-color="#ffffff">', MyAgilePrivacy::allowed_html_tags() );
						?>
					</div>

					<div class="col-sm-4 text-center">
						<div><label for="button_accept_button_color_field"><strong><?php _e('Background colour', 'MAP_txt'); ?></strong></label></div>
						<?php
							echo wp_kses( '<input type="color" id="button_accept_button_color_field" name="button_accept_button_color_field" data-preview="accept" value="' . esc_attr( $the_options['button_accept_button_color'] ) . '" data-default-color="#34C759">', MyAgilePrivacy::allowed_html_tags() );
						?>
					</div>
				</div>


			</div><!-- /.card -->

			<div class="card fullwidth">
				<div class="row mb-3">
					<div class="col-sm-12">
						<h6 class="h5"><?php _e('Refuse button', 'MAP_txt'); ?></h6>
					</div>
				</div>

				<div class="row">
					<div class="col-sm-4 text-center">
						<div>
							<label for="button_reject_text_field">
								<strong><?php _e('Text', 'MAP_txt'); ?></strong>
								<a href="<?php echo esc_url( $translation_menu_link ); ?>"><i class="fa-regular fa-comment-pen" data-bs-toggle="tooltip" data-bs-html="true" title="<?php _e('You can edit this text from the Texts and Translations section.', 'MAP_txt'); ?>"></i></a>
							</label>
						</div>
						<input type="text" class="form-control" id="button_reject_text_field" name="" value="<?php echo esc_attr( $the_translations[$selected_lang]['refuse'] ); ?>" readonly />
					</div>

					<div class="col-sm-4 text-center">
						<div><label for="button_reject_link_color_field"><strong><?php _e('Text colour', 'MAP_txt'); ?></strong></label></div>
						<?php
							echo wp_kses( '<input type="color" id="button_reject_link_color_field" name="button_reject_link_color_field" data-default-color="#ffffff" value="' . esc_attr( $the_options['button_reject_link_color'] ) . '">', MyAgilePrivacy::allowed_html_tags() );
						?>
					</div>

					<div class="col-sm-4 text-center">
						<div><label for="button_reject_button_color_field"><strong><?php _e('Background colour', 'MAP_txt'); ?></strong></label></div>
						<?php
							echo wp_kses( '<input type="color" id="button_reject_button_color_field" name="button_reject_button_color_field" data-preview="refuse" data-default-color="#636366" value="' . esc_attr( $the_options['button_reject_button_color'] ) . '">', MyAgilePrivacy::allowed_html_tags() );
						?>
					</div>
				</div>
			</div><!-- /.card -->

			<div class="card fullwidth">
				<div class="row mb-3">
					<div class="col-sm-12">
						<h6 class="h5"><?php _e('Customize button', 'MAP_txt'); ?></h6>
					</div>
				</div>

				<div class="row">
					<div class="col-sm-4 text-center">
						<div>
							<label for="button_customize_text_field">
								<strong><?php _e('Text', 'MAP_txt'); ?></strong>
								<a href="<?php echo esc_url( $translation_menu_link ); ?>"><i class="fa-regular fa-comment-pen" data-bs-toggle="tooltip" data-bs-html="true" title="<?php _e('You can edit this text from the Texts and Translations section.', 'MAP_txt'); ?>"></i></a>
							</label>
						</div>
						<input type="text" class="form-control" id="button_customize_text_field" name="" value="<?php echo esc_attr( $the_translations[$selected_lang]['customize'] ); ?>" readonly />
					</div>

					<div class="col-sm-4 text-center">
						<div><label for="button_customize_link_color_field"><strong><?php _e('Text colour', 'MAP_txt'); ?></strong></label></div>
						<?php
							echo wp_kses( '<input type="color" id="button_customize_link_color_field" name="button_customize_link_color_field" data-default-color="#ffffff" value="' . esc_attr( $the_options['button_customize_link_color'] ) . '">', MyAgilePrivacy::allowed_html_tags() );
						?>
					</div>

					<div class="col-sm-4 text-center">
						<div><label for="button_customize_button_color_field"><strong><?php _e('Background colour', 'MAP_txt'); ?></strong></label></div>
						<?php
							echo wp_kses( '<input type="color" id="button_customize_button_color_field" name="button_customize_button_color_field" data-default-color="#636366" data-preview="customize" value="' . esc_attr( $the_options['button_customize_button_color'] ) . '">', MyAgilePrivacy::allowed_html_tags() );
						?>
					</div>
				</div>
			</div><!-- /.card -->

		</div> <!-- consistent-box -->
	</div> <!-- /.col-sm-8 -->

<?php
	$preview_cookiebanner_classes = 'displayNone';
	$preview_cookiebanner_styles = '';

	$preview_title_styles = '';

	$preview_text_styles = '';
	$preview_accept_styles = '';
	$preview_refuse_styles = '';
	$preview_customize_styles = '';
	$accept_button_styles = '';

	$border_radius_style = '';
?>

	<div class="col-sm-4">
		<div id="live-preview">
			<div class="row align-items-center mb-4">
				<div class="col-7">
					<h6 class="text-center"><?php _e('Your website preview', 'MAP_txt'); ?></h6>
				</div>
				<div class="col-5">
					<div id="device-view-container" class="mb-2 text-center">
						<div class="btn-group">
							<button class="btn btn-outline-primary active" data-view="desktop"><i class="fa-regular fa-desktop"></i></button>
							<button class="btn btn-outline-primary" data-view="mobile"><i class="fa-regular fa-mobile"></i></button>
						</div>
					</div> <!-- device-view-container -->
				</div>
			</div>
			<div class="browser">
				<div id="preview-cookiebanner" class="<?php echo esc_attr( $preview_cookiebanner_classes ); ?>" style="<?php echo esc_attr( $preview_cookiebanner_styles.$border_radius_style ); ?>">

					<div id="preview-title" style="<?php echo esc_attr( $preview_title_styles ); ?>">
						<?php

							echo ( $the_translations[ $selected_lang ]['banner_title'] == '' ? '<div class="banner-title-logo" style="background:'.esc_html( $the_translations[ $selected_lang ]['banner_title'] ).';"></div> My Agile Privacy' : esc_attr( $the_translations[ $selected_lang ]['banner_title'] ) );

						?>
					</div>

					<div id="preview-content">
						<div id="preview-text-container">
							<div class="text" style="<?php echo esc_attr( $preview_text_styles ); ?>"></div>
							<div class="text" style="<?php echo esc_attr( $preview_text_styles ); ?>"></div>
							<div class="text" style="<?php echo esc_attr( $preview_text_styles ); ?>"></div>

							<div class="added_iab_text displayNone">
								<div class="text" style="<?php echo esc_attr( $preview_text_styles ); ?>"></div>
								<div class="text" style="<?php echo esc_attr( $preview_text_styles ); ?>"></div>
								<div class="text" style="<?php echo esc_attr( $preview_text_styles ); ?>"></div>
								<div class="text" style="<?php echo esc_attr( $preview_text_styles ); ?>"></div>
							</div>

							<div class="text show_boxed_preview" style="<?php echo $preview_text_styles; ?>"></div>
						</div>
						<div id="preview-button-container">
							<div class="preview-button" id="preview-accept" style="<?php echo esc_attr( $preview_accept_styles.$border_radius_style ); ?>"></div>
							<div class="preview-button" id="preview-refuse" style="<?php echo esc_attr( $preview_refuse_styles.$border_radius_style ); ?>"></div>
							<div class="preview-button" id="preview-customize" style="<?php echo esc_attr( $preview_customize_styles.$border_radius_style ); ?>"></div>
						</div>
					</div>

				</div>
			</div> <!-- browser -->

			<div class="mt-5">
				<h6 class="text-center"><?php _e('Accept button detailed preview', 'MAP_txt'); ?></h6>
				<div id="accept-detail-preview">
					<div class="preview-button" id="detail-preview-accept" style="<?php echo esc_attr( $preview_accept_styles.$border_radius_style.$accept_button_styles ); ?>"><div class="preview-button-icon" style="background:<?php echo $the_options['button_accept_link_color']; ?>;"></div> <span class="preview-botton-text"></span></div>
				</div>
			</div>

		</div> <!-- live preview -->
	</div>
</div> <!-- /.row -->