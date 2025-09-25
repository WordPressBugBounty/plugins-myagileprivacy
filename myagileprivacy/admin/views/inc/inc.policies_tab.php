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
				<i class="fa-regular fa-cookie-bite"></i>
				<?php echo wp_kses_post( __( 'Cookie Policy', 'MAP_txt' ) ); ?>
			</h4>

			<div class="row mb-4">
				<div class="col-sm-12">
					<?php echo wp_kses_post( __( 'The cookie policy page is the place where you tell user about how cookies works and what are the cookies used by the website.', 'MAP_txt' ) ); ?><br>
					<br>
					<?php echo wp_kses_post( __( 'The shortcode for the Cookie Policy text is:', 'MAP_txt' ) ); ?><br>
							<code>[myagileprivacy_fixed_text text="cookie_policy"]</code>
				</div>
			</div>

			<!-- cookie policy url or page -->
			<div class="row mb-4">
				<label for="is_cookie_policy_url_field" class="col-sm-5 col-form-label">
					<?php echo wp_kses_post( __( 'Url or Page ?', 'MAP_txt' ) ); ?>
				</label>

				<div class="col-sm-7">

					<div class="styled_radio d-inline-flex">
						<div class="round d-flex me-4">
							<?php if( $the_settings['is_cookie_policy_url'] == true ): ?>
								<input
									type="radio"
									id="is_cookie_policy_url_yes"
									name="is_cookie_policy_url_field"
									value="true"
									checked="checked" />
							<?php else: ?>
								<input
									type="radio"
									id="is_cookie_policy_url_yes"
									name="is_cookie_policy_url_field" value="true" />
							<?php endif; ?>

							<label for="is_cookie_policy_url_yes" class="me-2 label-radio"></label>

							<label for="is_cookie_policy_url_yes">
								<?php echo wp_kses_post( __( 'Url', 'MAP_txt' ) ); ?>
							</label>


						</div>

						<div class="round d-flex">
							<?php if( $the_settings['is_cookie_policy_url'] == false ): ?>
								<input
									type="radio"
									id="is_cookie_policy_url_no"
									name="is_cookie_policy_url_field"
									class=""
									value="false"
									checked="checked" />
							<?php else: ?>
								<input
									type="radio"
									id="is_cookie_policy_url_no"
									name="is_cookie_policy_url_field"
									class=""
									value="false" />
							<?php endif; ?>

							<label for="is_cookie_policy_url_no" class="me-2 label-radio"></label>

							<label for="is_cookie_policy_url_no">
								<?php echo wp_kses_post( __( 'Page', 'MAP_txt' ) ); ?>
							</label>
						</div>
					</div> <!-- ./ styled_radio -->

				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->

			<!-- cookie policy url row -->
			<div class="row mb-4 is_cookie_policy_url_yes_detail displayNone">
				<label for="cookie_policy_url_field" class="col-sm-5 col-form-label">
					<?php echo wp_kses_post( __( 'Enter URL', 'MAP_txt' ) ); ?>
				</label>

				<div class="col-sm-7">
					<input
						type="text"
						class="form-control"
						id="cookie_policy_url_field"
						name="cookie_policy_url_field"
						value="<?php echo esc_attr ( stripslashes( $the_settings['cookie_policy_url'] ) ); ?>" />

					<div class="form-text">
						<?php echo wp_kses_post( __( "Insert here the URL to the cookie policy page.", 'MAP_txt' ) ); ?><br><br>
					</div>
				</div> <!-- /.col-sm-6 -->


			</div> <!-- row -->

			<!-- cookie policy select page row -->
			<div class="row mb-4 is_cookie_policy_url_no_detail displayNone">
				<label for="cookie_policy_page_field" class="col-sm-5 col-form-label">
					<?php echo wp_kses_post( __( 'Choose Page', 'MAP_txt' ) ); ?>
				</label>

				<div class="col-sm-7">

					<select
						name="cookie_policy_page_field"
						class="form-control"
						id="cookie_policy_page_field">
						<option value="0">--<?php esc_attr_e('Select One', 'MAP_txt' ); ?>--</option>
						<?php
							foreach( $all_pages_for_policies_select as $page )
							{
								?>

								<?php
								if( $the_settings['cookie_policy_page']==$page->ID ):
								?>
									<option value="<?php echo esc_attr( $page->ID ); ?>" selected> <?php echo esc_attr( $page->post_title); ?> </option>
								<?php
								else:
								?>
									<option value="<?php echo esc_attr( $page->ID ); ?>"> <?php echo esc_attr( $page->post_title); ?> </option>
								<?php
								endif;
								?>
								<?php
							}
						?>
					</select>

					<div class="form-text">
						<?php echo wp_kses_post( __( "Don't forget to associate the right page: we suggest you to create a new page, put the right shortcode in the page editor, and associate the new created page here.", 'MAP_txt' ) ); ?>
					</div>

				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->


			<div class="row mb-4">
				<label for="add_cookie_policy_to_first_layer_field" class="col-sm-5 col-form-label">
					<?php echo wp_kses_post( __( 'Show the policy link in the banner', 'MAP_txt' ) ); ?>
				</label>
				<div class="col-sm-7">
					<div class="styled_radio d-inline-flex">
						<div class="round d-flex me-4">
							<input type="hidden" name="add_cookie_policy_to_first_layer_field" value="false"
								id="add_cookie_policy_to_first_layer_no">

							<input
								name="add_cookie_policy_to_first_layer_field"
								type="checkbox"
								value="true"
								id="add_cookie_policy_to_first_layer_field"
								<?php checked( $the_settings['add_cookie_policy_to_first_layer'], true ); ?>>

							<label for="add_cookie_policy_to_first_layer_field" class="me-2 label-checkbox"></label>

							<label for="add_cookie_policy_to_first_layer_field">
								<?php echo wp_kses_post( __( 'Yes, show the policy link in the banner', 'MAP_txt' ) ); ?>
							</label>
						</div>
					</div>

				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->


		</div> <!-- consistent-box -->

		<div class="consistent-box">
			<h4 class="mb-4">
				<i class="fa-regular fa-user-secret"></i>
				<?php echo wp_kses_post( __( 'Personal Data Policy', 'MAP_txt' ) ); ?>
			</h4>

			<div class="row mb-4">
				<div class="col-sm-12">
					<?php echo wp_kses_post( __( "The personal data page is the place where you tell user about how do you use personal data, for example for answering back a user form submission.", 'MAP_txt' ) ); ?><br>
					<br>
					<?php echo wp_kses_post( __( "Please remember also to ask user consent, adjusting your forms, adding the link to the selected page. You have got a shortcode for helping you to insert the right link.", 'MAP_txt' ) ); ?><br>
					<br>
					<?php echo wp_kses_post( __( "The shortcode for the Personal Data Policy text is:", 'MAP_txt' ) ); ?><br>
					<code>[myagileprivacy_fixed_text text="personal_data_policy"]</code><br>
					<br>
					<?php echo wp_kses_post( __( "The shortcode for the Personal Data Policy page URL is:", 'MAP_txt' ) ); ?><br>
								<code>[myagileprivacy_link value="personal_data_policy" text="Personal Data Policy"]</code>
				</div>
			</div>

			<!-- personal data policy url or page -->
			<div class="row mb-4">
				<label for="is_personal_data_policy_url_field" class="col-sm-5 col-form-label">
					<?php echo wp_kses_post( __( 'Url or Page ?', 'MAP_txt' ) ); ?>
				</label>

				<div class="col-sm-7">

					<div class="styled_radio d-inline-flex">
						<div class="round d-flex me-4">
							<?php if( $the_settings['is_personal_data_policy_url'] == true ): ?>
								<input
									type="radio"
									id="is_personal_data_policy_url_yes"
									name="is_personal_data_policy_url_field"
									value="true"
									checked="checked" />
							<?php else: ?>
								<input
									type="radio"
									id="is_personal_data_policy_url_yes"
									name="is_personal_data_policy_url_field"
									value="true" />
							<?php endif; ?>

							<label for="is_personal_data_policy_url_yes" class="me-2 label-radio"></label>

							<label for="is_personal_data_policy_url_yes">
								<?php echo wp_kses_post( __( 'Url', 'MAP_txt' ) ); ?>
							</label>

						</div>

						<div class="round d-flex">
							<?php if( $the_settings['is_personal_data_policy_url'] == false ): ?>
								<input
									type="radio"
									id="is_personal_data_policy_url_no"
									name="is_personal_data_policy_url_field"
									class=""
									value="false"
									checked="checked" />
							<?php else: ?>
								<input
									type="radio"
									id="is_personal_data_policy_url_no"
									name="is_personal_data_policy_url_field"
									class=""
									value="false" />
							<?php endif; ?>

							<label for="is_personal_data_policy_url_no" class="me-2 label-radio"></label>

							<label for="is_personal_data_policy_url_no">
								<?php echo wp_kses_post( __( 'Page', 'MAP_txt' ) ); ?>
							</label>
						</div>
					</div> <!-- ./ styled_radio -->

				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->

			<!-- personal data policy url row -->
			<div class="row mb-4 is_personal_data_policy_url_yes_detail displayNone">
				<label for="personal_data_policy_url_field" class="col-sm-5 col-form-label">
					<?php echo wp_kses_post( __( 'Enter URL', 'MAP_txt' ) ); ?>
				</label>

				<div class="col-sm-7">
					<input
						type="text"
						class="form-control"
						id="personal_data_policy_url_field"
						name="personal_data_policy_url_field"
						value="<?php echo esc_attr( stripslashes( $the_settings['personal_data_policy_url'] ) ); ?>" />

					<div class="form-text">
						<?php echo wp_kses_post( __( "Insert here the URL to the personal data policy page.", 'MAP_txt' ) ); ?><br><br>
					</div>
				</div> <!-- /.col-sm-6 -->


			</div> <!-- row -->

			<!-- personal data policy select page row -->
			<div class="row mb-4 is_personal_data_policy_url_no_detail displayNone">
				<label for="personal_data_policy_page_field" class="col-sm-5 col-form-label">
					<?php echo wp_kses_post( __( 'Choose Page', 'MAP_txt' ) ); ?>
				</label>

				<div class="col-sm-7">

					<select
						name="personal_data_policy_page_field"
						class="form-control"
						id="personal_data_policy_page_field">
						<option value="0">--<?php esc_attr_e('Select One', 'MAP_txt' ); ?>--</option>

						<?php
							foreach( $all_pages_for_policies_select as $page )
							{
								?>

								<?php
								if( $the_settings['personal_data_policy_page']==$page->ID ):
								?>
									<option value="<?php echo esc_attr( $page->ID ); ?>" selected> <?php echo esc_attr( $page->post_title ); ?> </option>
								<?php
								else:
								?>
									<option value="<?php echo esc_attr( $page->ID ); ?>"> <?php echo esc_attr( $page->post_title); ?> </option>
								<?php
								endif;
								?>
								<?php
							}
						?>

					</select>

					<div class="form-text">
					<?php echo wp_kses_post( __( "Don't forget to associate the right page: we suggest you to create a new page, put the right shortcode in the page editor, and associate the new created page here.", 'MAP_txt' ) ); ?>
					</div>

				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->


			<div class="row mb-4">
				<label for="add_personal_policy_to_first_layer_field" class="col-sm-5 col-form-label">
					<?php echo wp_kses_post( __( 'Show the policy link in the banner', 'MAP_txt' ) ); ?>
				</label>
				<div class="col-sm-7">
					<div class="styled_radio d-inline-flex">
						<div class="round d-flex me-4">
							<input type="hidden" name="add_personal_policy_to_first_layer_field" value="false"
								id="add_personal_policy_to_first_layer_no">

							<input
								name="add_personal_policy_to_first_layer_field"
								type="checkbox"
								value="true"
								id="add_personal_policy_to_first_layer_field"
								<?php checked( $the_settings['add_personal_policy_to_first_layer'], true ); ?>>

							<label for="add_personal_policy_to_first_layer_field" class="me-2 label-checkbox"></label>

							<label for="add_personal_policy_to_first_layer_field">
								<?php echo wp_kses_post( __( 'Yes, show the policy link in the banner', 'MAP_txt' ) ); ?>
							</label>
						</div>
					</div>

				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->


		</div> <!-- consistent-box -->


		<?php

			if( !( isset( $rconfig ) && isset( $rconfig['keep_v1_policies'] ) && $rconfig['keep_v1_policies'] ) ):
		?>


			<div class="consistent-box">

				<h4 class="mb-4">
					<i class="fa-solid fa-scale-balanced"></i>
					<?php echo wp_kses_post( __( 'Active regulations', 'MAP_txt' ) ); ?>
				</h4>

				<div class="row mb-4">
					<div class="col-sm-12">

						<?php echo wp_kses_post( __( 'Below is the list of policies currently active on your site. You can modify them at any time using the "Policy Assistant" feature.', 'MAP_txt' ) ); ?><br>


						<div class="mt-4 mb-4">
							<?php

								$MyAgilePrivacyRegulationHelper = new MyAgilePrivacyRegulationHelper();

								$regulation_selected = $MyAgilePrivacyRegulationHelper->getRegulationsSelected( true );

								if( count( $regulation_selected ) > 0 )
								{
									foreach( $regulation_selected as $reg )
									{
										?>
											<span class="badge rounded-pill bg-primary">
												<?php echo esc_html( $reg ); ?>
											</span>
										<?php
									}
								}
								else
								{
									echo "-";
								}

							?>
						</div>

						<?php echo wp_kses_post( __( 'You can review your configuration at any time using the', 'MAP_txt' ) ); ?> <a href="<?php echo esc_url( $policy_assistant_menu_link ); ?>"><?php echo wp_kses_post( __( 'Policy Assistant.', 'MAP_txt' ) ); ?></a>

					</div>
				</div>

			</div>

		<?php

			endif;
		?>


	</div> <!-- /.col-sm-8 -->

	<div class="col-sm-4">
	<?php
		$tab = 'policies';
		include 'inc.admin_sidebar.php';
	?>
	</div>
</div> <!-- /.row -->
