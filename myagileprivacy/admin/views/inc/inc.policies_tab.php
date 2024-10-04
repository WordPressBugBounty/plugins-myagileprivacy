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
				<?php _e('Cookie Policy', 'MAP_txt'); ?>
			</h4>

			<div class="row mb-4">
				<div class="col-sm-12">
					<?php _e('The cookie policy page is the place where you tell user about how cookies works and what are the cookies used by the website.', 'MAP_txt'); ?><br>
					<br>
					<?php _e('The shortcode for the Cookie Policy text is:', 'MAP_txt'); ?><br>
							<code>[myagileprivacy_fixed_text text="cookie_policy"]</code>
				</div>
			</div>

			<!-- cookie policy url or page -->
			<div class="row mb-4">
				<label for="is_cookie_policy_url_field" class="col-sm-5 col-form-label">
					<?php _e('Url or Page ?', 'MAP_txt'); ?>
				</label>

				<div class="col-sm-7">

					<div class="styled_radio d-inline-flex">
						<div class="round d-flex me-4">
							<?php if( $the_options['is_cookie_policy_url'] == true ): ?>
								<input type="radio" id="is_cookie_policy_url_yes" name="is_cookie_policy_url_field" value="true" checked="checked" />
							<?php else: ?>
								<input type="radio" id="is_cookie_policy_url_yes" name="is_cookie_policy_url_field" value="true" />
							<?php endif; ?>

							<label for="is_cookie_policy_url_yes" class="me-2 label-radio"></label>

							<label for="is_cookie_policy_url_yes">
								<?php _e('Url', 'MAP_txt'); ?>
							</label>


						</div>

						<div class="round d-flex">
							<?php if( $the_options['is_cookie_policy_url'] == false ): ?>
								<input type="radio" id="is_cookie_policy_url_no" name="is_cookie_policy_url_field" class="" value="false" checked="checked" />
							<?php else: ?>
								<input type="radio" id="is_cookie_policy_url_no" name="is_cookie_policy_url_field" class="" value="false" />
							<?php endif; ?>

							<label for="is_cookie_policy_url_no" class="me-2 label-radio"></label>

							<label for="is_cookie_policy_url_no">
								<?php _e('Page', 'MAP_txt'); ?>
							</label>
						</div>
					</div> <!-- ./ styled_radio -->

				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->

			<!-- cookie policy url row -->
			<div class="row mb-4 is_cookie_policy_url_yes_detail displayNone">
				<label for="cookie_policy_url_field" class="col-sm-5 col-form-label">
					<?php _e('Enter URL', 'MAP_txt'); ?>
				</label>

				<div class="col-sm-7">
					<input type="text" class="form-control" id="cookie_policy_url_field" name="cookie_policy_url_field" value="<?php echo esc_attr(stripslashes($the_options['cookie_policy_url'])) ?>" />

					<div class="form-text">
						<?php _e("Insert here the URL to the cookie policy page.", 'MAP_txt'); ?><br><br>
					</div>
				</div> <!-- /.col-sm-6 -->


			</div> <!-- row -->

			<!-- cookie policy select page row -->
			<div class="row mb-4 is_cookie_policy_url_no_detail displayNone">
				<label for="cookie_policy_page_field" class="col-sm-5 col-form-label">
					<?php _e('Choose Page', 'MAP_txt'); ?>
				</label>

				<div class="col-sm-7">

					<select name="cookie_policy_page_field" class="form-control" id="cookie_policy_page_field">
						<option value="0">--<?php _e('Select One', 'MAP_txt'); ?>--</option>
						<?php
						foreach( $all_pages_for_policies_select as $page )
						{
							?>

							<?php
							if( $the_options['cookie_policy_page']==$page->ID ):
							?>
								<option value="<?php echo esc_attr( $page->ID ); ?>" selected> <?php echo esc_html( $page->post_title);?> </option>
							<?php
							else:
							?>
								<option value="<?php echo esc_attr( $page->ID ); ?>"> <?php echo esc_html( $page->post_title);?> </option>
							<?php
							endif;
							?>
							<?php
						}
						?>
					</select>

					<div class="form-text">
						<?php _e("Don't forget to associate the right page: we suggest you to create a new page, put the right shortcode in the page editor, and associate the new created page here.", 'MAP_txt'); ?>
					</div>

				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->

		</div> <!-- consistent-box -->

		<div class="consistent-box">
			<h4 class="mb-5">
				<i class="fa-regular fa-user-secret"></i>
				<?php _e('Personal Data Policy', 'MAP_txt'); ?>
			</h4>

			<div class="row mb-5">
				<div class="col-sm-12">
					<?php _e("The personal data page is the place where you tell user about how do you use personal data, for example for answering back a user form submission.", 'MAP_txt'); ?><br>
					<br>
					<?php _e("Please remember also to ask user consent, adjusting your forms, adding the link to the selected page. You have got a shortcode for helping you to insert the right link.", 'MAP_txt'); ?><br>
					<br>
					<?php _e("The shortcode for the Personal Data Policy text is:", 'MAP_txt'); ?><br>
					<code>[myagileprivacy_fixed_text text="personal_data_policy"]</code><br>
					<br>
					<?php _e("The shortcode for the Personal Data Policy page URL is:", 'MAP_txt'); ?><br>
								<code>[myagileprivacy_link value="personal_data_policy" text="Personal Data Policy"]</code>
				</div>
			</div>

			<!-- personal data policy url or page -->
			<div class="row mb-4">
				<label for="is_personal_data_policy_url_field" class="col-sm-5 col-form-label">
					<?php _e('Url or Page ?', 'MAP_txt'); ?>
				</label>

				<div class="col-sm-7">

					<div class="styled_radio d-inline-flex">
						<div class="round d-flex me-4">
							<?php if( $the_options['is_personal_data_policy_url'] == true ): ?>
								<input type="radio" id="is_personal_data_policy_url_yes" name="is_personal_data_policy_url_field" value="true" checked="checked" />
							<?php else: ?>
								<input type="radio" id="is_personal_data_policy_url_yes" name="is_personal_data_policy_url_field" value="true" />
							<?php endif; ?>

							<label for="is_personal_data_policy_url_yes" class="me-2 label-radio"></label>

							<label for="is_personal_data_policy_url_yes">
								<?php _e('Url', 'MAP_txt'); ?>
							</label>

						</div>

						<div class="round d-flex">
							<?php if( $the_options['is_personal_data_policy_url'] == false ): ?>
								<input type="radio" id="is_personal_data_policy_url_no" name="is_personal_data_policy_url_field" class="" value="false" checked="checked" />
							<?php else: ?>
								<input type="radio" id="is_personal_data_policy_url_no" name="is_personal_data_policy_url_field" class="" value="false" />
							<?php endif; ?>

							<label for="is_personal_data_policy_url_no" class="me-2 label-radio"></label>

							<label for="is_personal_data_policy_url_no">
								<?php _e('Page', 'MAP_txt'); ?>
							</label>
						</div>
					</div> <!-- ./ styled_radio -->

				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->

			<!-- personal data policy url row -->
			<div class="row mb-4 is_personal_data_policy_url_yes_detail displayNone">
				<label for="personal_data_policy_url_field" class="col-sm-5 col-form-label">
					<?php _e('Enter URL', 'MAP_txt'); ?>
				</label>

				<div class="col-sm-7">
					<input type="text" class="form-control" id="personal_data_policy_url_field" name="personal_data_policy_url_field" value="<?php echo esc_attr(stripslashes($the_options['personal_data_policy_url'])) ?>" />

					<div class="form-text">
						<?php _e("Insert here the URL to the personal data policy page.", 'MAP_txt'); ?><br><br>
					</div>
				</div> <!-- /.col-sm-6 -->


			</div> <!-- row -->

			<!-- personal data policy select page row -->
			<div class="row mb-4 is_personal_data_policy_url_no_detail displayNone">
				<label for="personal_data_policy_page_field" class="col-sm-5 col-form-label">
					<?php _e('Choose Page', 'MAP_txt'); ?>
				</label>

				<div class="col-sm-7">

					<select name="personal_data_policy_page_field" class="form-control" id="personal_data_policy_page_field">
						<option value="0">--<?php _e('Select One', 'MAP_txt'); ?>--</option>

						<?php
						foreach( $all_pages_for_policies_select as $page )
						{
							?>

							<?php
							if( $the_options['personal_data_policy_page']==$page->ID ):
							?>
								<option value="<?php echo esc_attr( $page->ID ); ?>" selected> <?php echo esc_html( $page->post_title);?> </option>
							<?php
							else:
							?>
								<option value="<?php echo esc_attr( $page->ID ); ?>"> <?php echo esc_html( $page->post_title);?> </option>
							<?php
							endif;
							?>
							<?php
						}
						?>

					</select>

					<div class="form-text">
					<?php _e("Don't forget to associate the right page: we suggest you to create a new page, put the right shortcode in the page editor, and associate the new created page here.", 'MAP_txt'); ?>
					</div>

				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->


		</div> <!-- consistent-box -->


		<span class="translate-middle-y forbiddenWarning badge rounded-pill bg-danger  <?php if( $the_options['pa'] == 1){echo 'd-none';} ?>">
			<small><?php _e('Premium Feature', 'MAP_txt'); ?></small>
		</span>

		<div class="consistent-box <?php if( $the_options['pa'] != 1){echo 'forbiddenArea';} ?>">

			<h4 class="mb-4">
				<img src="<?php echo plugin_dir_url( __DIR__ ); ?>../img/flag-switzerland.png" alt="" width="24">
				<?php _e('LPD Privacy Regulation', 'MAP_txt'); ?>
			</h4>

			<div class="row mb-4">
				<div class="col-sm-12">

					<b><?php _e("The LPD is Switzerland's Privacy regulation.", 'MAP_txt'); ?></b><br>
					<?php _e('By activating the management of Swiss LPD, you declare to be familiar with Swiss privacy legislation and to fall within its scope of application.', 'MAP_txt'); ?><br>
					<?php _e('You acknowledge that, depending on the personal data processing activities you will undertake, you will need to comply with both GDPR and LPD as well as other local regulations where applicable.', 'MAP_txt'); ?><br>
					<?php _e('You have also considered the potential appointment of a Data Protection Advisor as provided by Swiss LPD.', 'MAP_txt'); ?><br>
					<?php _e('Lastly, you declare that you have reviewed the <a href="https://www.bj.admin.ch/bj/en/home/staat/datenschutz/internationales/anerkennung-staaten.html" target="_blank">list of States</a> where, according to the LPD, you may possibly transfer the data subject to processing.', 'MAP_txt'); ?>
				</div>
			</div>

			<div class="row mb-4">
				<label for="display_lpd_field" class="col-sm-5 col-form-label">
					<?php _e('Enable LPD notice', 'MAP_txt'); ?>
				</label>

				<div class="col-sm-7">
					<div class="styled_radio d-inline-flex">
						<div class="round d-flex me-4">

							<input type="hidden" name="display_lpd_field" value="false" id="display_lpd_field_no">

							<input name="display_lpd_field" type="checkbox" value="true" id="display_lpd_field" <?php checked( $the_options['display_lpd'], true); ?>>

							<label for="display_lpd_field" class="me-2 label-checkbox"></label>

							<label for="display_lpd_field">
								<?php _e('Yes, I fall under the scope of LPD and I want to display the corresponding notice.', 'MAP_txt'); ?>
							</label>
						</div>
					</div> <!-- ./ styled_radio -->

				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->

		</div>


		<span class="translate-middle-y forbiddenWarning badge rounded-pill bg-danger  <?php if( $the_options['pa'] == 1){echo 'd-none';} ?>">
			<small><?php _e('Premium Feature', 'MAP_txt'); ?></small>
		</span>

		<div class="consistent-box <?php if( $the_options['pa'] != 1){echo 'forbiddenArea';} ?>">

			<h4 class="mb-4">
				<i class="fa-regular fa-flag-usa"></i>
				<?php _e('CCPA Privacy Regulation', 'MAP_txt'); ?>
			</h4>

			<div class="row mb-4">
				<div class="col-sm-12">

					<b><?php _e('The CCPA is the California Privacy regulation.', 'MAP_txt'); ?></b><br>
					<?php _e('You fall under the scope of CCPA when both of these conditions apply:', 'MAP_txt'); ?><br>
					<?php _e('-you have a business with an annual gross revenue exceeding $25 million, or 50% of the revenue comes from selling personal data, or you buy, receive, sell, or share personal information of 50000 or more consumers annually for commercial purposes', 'MAP_txt'); ?><br>
					<?php _e('-you target California residents.', 'MAP_txt'); ?><br>
				</div>
			</div>

			<div class="row mb-4">
				<label for="display_ccpa_field" class="col-sm-5 col-form-label">
					<?php _e('Enable CCPA notice', 'MAP_txt'); ?>
				</label>

				<div class="col-sm-7">
					<div class="styled_radio d-inline-flex">
						<div class="round d-flex me-4">

							<input type="hidden" name="display_ccpa_field" value="false" id="display_ccpa_field_no">

							<input name="display_ccpa_field" type="checkbox" value="true" id="display_ccpa_field" <?php checked( $the_options['display_ccpa'], true); ?>>

							<label for="display_ccpa_field" class="me-2 label-checkbox"></label>

							<label for="display_ccpa_field">
								<?php _e('Yes, I fall under the scope of CCPA and I want to display the corresponding notice.', 'MAP_txt'); ?>
							</label>
						</div>
					</div> <!-- ./ styled_radio -->

				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->

		</div>




	</div> <!-- /.col-sm-8 -->

	<div class="col-sm-4">
	<?php
		$tab = 'policies';
		include 'inc.admin_sidebar.php';
	?>
	</div>
</div> <!-- /.row -->
