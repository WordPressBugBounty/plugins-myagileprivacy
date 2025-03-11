<?php

if( !defined( 'MAP_PLUGIN_NAME' ) )
{
	exit('Not allowed.');
}
?>


<?php if( $caller == 'genericOptionsWrapper') : ?>

	<!-- website name -->
	<div class="row mb-4">
		<label for="website_name_field" class="col-sm-5 col-form-label">
			<?php echo wp_kses_post( __( 'Domain list', 'MAP_txt' ) ); ?>
		</label>

		<div class="col-sm-7">
			<textarea class="form-control" rows="6" id="website_name_field"
				name="website_name_field"><?php echo esc_attr( stripslashes( $the_settings['website_name'] ) ) ?></textarea>

			<div class="form-text">
				<?php echo wp_kses_post( __( 'You can enter multiple domain linked to this website.', 'MAP_txt' ) ); ?>
			</div>
		</div> <!-- /.col-sm-6 -->
	</div> <!-- row -->

<?php endif; ?>


<!-- company name -->
<div class="row mb-4">
	<label for="identity_name_field" class="col-sm-5 col-form-label">
		<?php echo wp_kses_post( __( 'Company Name / Site Holder', 'MAP_txt' ) ); ?>
	</label>

	<div class="col-sm-7">
		<input type="text" class="form-control" id="identity_name_field" name="identity_name_field"
			value="<?php echo esc_attr( stripslashes( $the_settings['identity_name'] ) ) ?>" />

		<div class="form-text">
			<?php echo wp_kses_post( __( 'Insert here the name of the company or the person who is the owner of the website.', 'MAP_txt' ) ); ?>
		</div>
	</div> <!-- /.col-sm-6 -->
</div> <!-- row -->

<!-- company address -->
<div class="row mb-4">
	<label for="identity_address_field" class="col-sm-5 col-form-label">
		<?php echo wp_kses_post( __( 'Company Address', 'MAP_txt' ) ); ?>
	</label>

	<div class="col-sm-7">
		<input type="text" class="form-control" id="identity_address_field" name="identity_address_field"
			value="<?php echo esc_attr( stripslashes( $the_settings['identity_address'] ) ) ?>" />

		<div class="form-text">
			<?php echo wp_kses_post( __( 'Insert here the address of the company or person who owns the website.', 'MAP_txt' ) ); ?>
		</div>
	</div> <!-- /.col-sm-6 -->
</div> <!-- row -->

<!-- vat id -->
<div class="row mb-4">
	<label for="identity_vat_id_field" class="col-sm-5 col-form-label">
		<?php echo wp_kses_post( __( 'Vat Id', 'MAP_txt' ) ); ?>
	</label>

	<div class="col-sm-7">
		<input type="text" class="form-control" id="identity_vat_id_field" name="identity_vat_id_field"
			value="<?php echo esc_attr( stripslashes( $the_settings['identity_vat_id'] ) ) ?>" />

		<div class="form-text">
			<?php echo wp_kses_post( __( 'Leave it blank, if not applicabile', 'MAP_txt' ) ); ?>.
		</div>
	</div> <!-- /.col-sm-6 -->
</div> <!-- row -->

<!-- email -->
<div class="row mb-4">
	<label for="identity_email_field" class="col-sm-5 col-form-label">
		<?php echo wp_kses_post( __( 'Company E-mail', 'MAP_txt' ) ); ?>
	</label>

	<div class="col-sm-7">
		<input type="text" class="form-control" id="identity_email_field" name="identity_email_field"
			value="<?php echo esc_attr( stripslashes( $the_settings['identity_email'] ) ) ?>" />

		<div class="form-text">
			<?php echo wp_kses_post( __( 'Insert here the email where the user can contact you for questions about privacy and personal data use', 'MAP_txt' ) ); ?>.
		</div>
	</div> <!-- /.col-sm-6 -->
</div> <!-- row -->