<?php

if( !defined( 'MAP_PLUGIN_NAME' ) )
{
	exit('Not allowed.');
}
?>

<!-- company name -->
<div class="row mb-4">
	<label for="identity_name_field" class="col-sm-5 col-form-label">
		<?php echo wp_kses_post( __( 'Company Name / Site Holder', 'MAP_txt' ) ); ?>
	</label>

	<div class="col-sm-7">

		<input
			type="text"
			class="form-control"
			id="identity_name_field"
			name="site_and_policy_settings[identity_name]"
			value="<?php echo esc_attr( stripslashes( $site_and_policy_settings['identity_name'] ) ); ?>" />

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

		<input
			type="text"
			class="form-control"
			id="identity_address_field"
			name="site_and_policy_settings[identity_address]"
			value="<?php echo esc_attr( stripslashes( $site_and_policy_settings['identity_address'] ) ); ?>" />

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

		<input
			type="text"
			class="form-control"
			id="identity_vat_id_field"
			name="site_and_policy_settings[identity_vat_id]"
			value="<?php echo esc_attr( stripslashes( $site_and_policy_settings['identity_vat_id'] ) ); ?>" />

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

		<input
			type="text"
			class="form-control"
			id="identity_email_field"
			name="site_and_policy_settings[identity_email]"
			value="<?php echo esc_attr( stripslashes( $site_and_policy_settings['identity_email'] ) ); ?>" />

		<div class="form-text">
			<?php echo wp_kses_post( __( 'Insert here the email where the user can contact you for questions about privacy and personal data use', 'MAP_txt' ) ); ?>.
		</div>
	</div> <!-- /.col-sm-6 -->
</div> <!-- row -->