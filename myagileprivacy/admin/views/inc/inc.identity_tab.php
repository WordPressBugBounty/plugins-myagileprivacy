<?php

if( !defined( 'MAP_PLUGIN_NAME' ) )
{
	exit('Not allowed.');
}

$caller = 'genericOptionsWrapper';

?>

<div class="row">
	<div class="col-sm-8">

		<div class="consistent-box">
			<h4 class="mb-4">
				<i class="fa-regular fa-address-card"></i>
				<?php echo wp_kses_post( __( 'Identity Settings', 'MAP_txt' ) ); ?>
			</h4>

			<div class="row mb-5">
				<div class="col-sm-12">
				<?php echo wp_kses_post( __( 'Here you can enter the company details or the site older informations. This is used for populating the privacy data controller section.', 'MAP_txt' ) ); ?>
				</div>
			</div>

			<?php include 'fields.identity_tab.php'; ?>

		</div> <!-- consistent-box -->

		<span class="translate-middle-y forbiddenWarning badge rounded-pill bg-danger  <?php if( $the_settings['pa'] == 1){echo 'd-none';} ?>">
			<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
		</span>

		<div class="consistent-box <?php if( $the_settings['pa'] != 1){echo 'forbiddenArea';} ?>">

			<h4 class="mb-4">
				<i class="fa-regular fa-address-card"></i>
				<?php echo wp_kses_post( __( 'DPO settings', 'MAP_txt' ) ); ?>
			</h4>

			<div class="row mb-5">
				<div class="col-sm-12">
				<?php echo wp_kses_post( __( 'Here you can enter the DPO data for your organization, if applicable.', 'MAP_txt' ) ); ?>
				</div>
			</div>

			<!-- dpo checkbox -->
			<div class="row mb-4">
				<label for="display_dpo_field" class="col-sm-5 col-form-label">
					<?php echo wp_kses_post( __( 'I have a DPO (Data Protection Officer)', 'MAP_txt' ) ); ?>
				</label>

				<div class="col-sm-7">
					<div class="styled_radio d-inline-flex">
						<div class="round d-flex me-4">
							<input type="hidden" name="display_dpo_field" value="false" id="display_dpo_field_no">

							<input class="hideShowInput" data-hide-show-ref="display_dpo_field_wrapper" name="display_dpo_field" type="checkbox" value="true" id="display_dpo_field" <?php checked($the_settings['display_dpo'], true); ?>>

							<label for="display_dpo_field" class="me-2 label-checkbox"></label>

							<label for="display_dpo_field">
								<?php echo wp_kses_post( __( 'Yes, I have a DPO', 'MAP_txt' ) ); ?>.
							</label>

						</div>
					</div> <!-- ./ styled_radio -->
				</div> <!-- /.col-sm-6 -->

			</div> <!-- row -->

			<!-- dpo email -->
			<div class="row mb-4 display_dpo_field_wrapper displayNone">
				<label for="dpo_email_field" class="col-sm-5 col-form-label">
					<?php echo wp_kses_post( __( 'DPO Email', 'MAP_txt' ) ); ?> (*)
				</label>

				<div class="col-sm-7">
					<input type="text" class="form-control" id="dpo_email_field" name="dpo_email_field" value="<?php echo esc_attr(stripslashes($the_settings['dpo_email']))  ?>" />

					<div class="form-text">
						<?php echo wp_kses_post( __( 'Insert here the email of your DPO', 'MAP_txt' ) ); ?>.
					</div>
				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->


			<!-- dpo name -->
			<div class="row mb-4 display_dpo_field_wrapper displayNone">
				<label for="dpo_email_field" class="col-sm-5 col-form-label">
					<?php echo wp_kses_post( __( 'DPO Name / Company name', 'MAP_txt' ) ); ?>
				</label>

				<div class="col-sm-7">
					<input type="text" class="form-control" id="dpo_name_field" name="dpo_name_field" value="<?php echo esc_attr(stripslashes($the_settings['dpo_name']))  ?>" />
				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->


			<!-- dpo name -->
			<div class="row mb-4 display_dpo_field_wrapper displayNone">
				<label for="dpo_email_field" class="col-sm-5 col-form-label">
					<?php echo wp_kses_post( __( 'DPO Address', 'MAP_txt' ) ); ?>
				</label>

				<div class="col-sm-7">
					<input type="text" class="form-control" id="dpo_address_field" name="dpo_address_field" value="<?php echo esc_attr( stripslashes( $the_settings['dpo_address'] ) )  ?>" />
				</div> <!-- /.col-sm-6 -->
			</div> <!-- row -->



		</div> <!-- consistent-box -->
	</div> <!-- /.col-sm-8 -->

	<div class="col-sm-4">
		<?php
			$tab = 'identity';
			include 'inc.admin_sidebar.php';
		?>
	</div>
</div> <!-- /.row -->