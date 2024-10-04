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
				<i class="fa-regular fa-key"></i>
				<?php _e('Your license details', 'MAP_txt'); ?>
			</h4>

			<!-- stato licenza -->
			<div class="row mb-4">
				<label for="license_user_status_field" class="col-sm-5 col-form-label">
					<?php _e('Your license status', 'MAP_txt'); ?>
				</label>

				<div class="col-sm-7">
				<input disabled type="text" id="license_user_status_field" name="license_user_status_field" value="<?php echo esc_attr(stripslashes($the_options['license_user_status']));  ?>" class="form-control <?php if( $the_options['license_valid'] && !$the_options['grace_period'] ) echo esc_attr( 'success_style' ); else echo esc_attr( 'warning_style' );  ?>"/>

					<div class="form-text">
						<?php
							_e("The status of your license", 'MAP_txt');
						?>.
					</div>

				</div>
			</div> <!-- row -->


			<div class="license_code_wrapper <?php if( isset( $rconfig ) && isset( $rconfig['lc_hide_local'] ) && $rconfig['lc_hide_local'] == 1 ) echo 'd-none'; ?>">

				<!-- chiave di licenza -->
				<div class="row mb-4">
					<label for="license_code_field" class="col-sm-5 col-form-label">
						<?php _e('License code', 'MAP_txt'); ?>
					</label>

					<div class="col-sm-7">
						<input type="text" class="form-control" id="license_code_field" name="license_code_field" value="<?php echo esc_attr(stripslashes($the_options['license_code']))  ?>" />

						<div class="form-text">
							<?php
								_e("Enter your license key here", 'MAP_txt');
							?>.

							<?php

								if( isset( $the_options ) && isset( $the_options['license_code'] ) && $the_options['license_code'] ):
							?>

							<br>
							<?php
								_e("Would you like to verify the status of your subscription, download invoices, or carry out other administrative tasks?", 'MAP_txt');
							?><br>

							<a href="https://areaprivata.myagileprivacy.com/" target="blank"><?php _e("Click here to access your user area.", 'MAP_txt');?></a>

							<?php
								endif;
							?>

						</div>

					</div>
				</div> <!-- row -->

				<div class="<?php if( !$the_options['summary_text'] ) echo 'd-none';  ?>">

					<!-- email associata -->
					<div class="row mb-4 d-none">
						<label for="customer_email_field" class="col-sm-5 col-form-label">
							<?php _e('Your e-mail', 'MAP_txt'); ?>
						</label>

						<div class="col-sm-7">
							<input disabled class="form-control" type="text" id="customer_email_field" name="customer_email_field" value="<?php echo esc_attr(stripslashes($the_options['customer_email']));  ?>" />

							<div class="form-text">
								<?php
									_e("The email address linked to the license key", 'MAP_txt');
								?>.
							</div>

						</div>
					</div> <!-- row -->

					<!-- sommario licenza -->
					<div class="row mb-4 d-none">
						<label for="summary_text_field" class="col-sm-5 col-form-label">
							<?php _e('License summary', 'MAP_txt'); ?>
						</label>

						<div class="col-sm-7">
							<textarea disabled class="form-control" id="summary_text_field" name="summary_text_field"><?php echo esc_attr(stripslashes($the_options['summary_text']));?></textarea>
						</div>
					</div> <!-- row -->

				</div>
			</div>


			<div class="hide_code_wrapper <?php if( !( isset( $rconfig ) && isset( $rconfig['lc_hide_local'] ) && $rconfig['lc_hide_local'] == 1) ) echo 'd-none'; ?>">

				<div class="row mb-4">
					<label for="license_code_field" class="col-sm-5 col-form-label">
						<?php _e('Reseller info', 'MAP_txt'); ?>:
					</label>

					<div class="col-sm-7">

						<h6>
							<?php _e('Your license key is provided by', 'MAP_txt'); ?> <span class="lc_owner_description"><?php if( isset( $rconfig ) && isset( $rconfig['lc_owner_description'] ) ) echo esc_html( $rconfig['lc_owner_description'] ); ?></span> .
						</h6>

						<div class="my-3">
							<strong>
								<?php _e('For further information you can check:', 'MAP_txt'); ?>
							</strong>
							<br>

							<span class="d-block lc_owner_website_wrapper <?php if( !( isset( $rconfig ) && isset( $rconfig['lc_owner_website'] ) ) ) echo 'd-none'; ?>">
								<?php _e('Reseller Website:', 'MAP_txt'); ?> <span class="lc_owner_website"><?php if( isset( $rconfig ) && isset( $rconfig['lc_owner_website'] ) ) echo '<a target="blank" href="'.esc_attr( $rconfig['lc_owner_website'] ).'">'.$rconfig['lc_owner_website'].'</a>'; ?></span>
							</span>

							<span class="d-block lc_owner_email_wrapper  <?php if( !( isset( $rconfig ) && isset( $rconfig['lc_owner_email'] ) ) ) echo 'd-none'; ?>">
								<?php _e('Reseller Mail:', 'MAP_txt'); ?> <span class="lc_owner_email"><?php if( isset( $rconfig ) && isset( $rconfig['lc_owner_email'] ) ) echo  '<a href="mailto:'.esc_attr( $rconfig['lc_owner_email'] ).'">'.$rconfig['lc_owner_email'].'</a>'; ?></span>
							</span>
						</div>

						<button class="button-agile-outline btn-md changeLicenseCode"><?php _e('Change license code', 'MAP_txt'); ?></button>

					</div>
				</div> <!-- row -->

			</div>


			<!-- ultima data di sync -->
			<div class="row mb-4">
				<label for="last_sync_field" class="col-sm-5 col-form-label">
					<?php _e('Last cookies sync', 'MAP_txt'); ?>
				</label>

				<div class="col-sm-7">
					<input disabled class="form-control" type="text" id="last_sync_field" name="last_sync_field" value="<?php if( $the_options['last_sync'] ) echo esc_attr( stripslashes( $the_options['last_sync'] ) ); else echo ''; ?>"/>

					<div class="form-text">
						<?php
							_e("The last time cookies and policy were syncronized with remote db", 'MAP_txt');
						?>.
					</div>

				</div>
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