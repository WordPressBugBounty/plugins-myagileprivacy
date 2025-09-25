<?php

if( !defined( 'MAP_PLUGIN_NAME' ) )
{
	exit('Not allowed.');
}


$locale = MyAgilePrivacy::get_locale();

$buyLink = 'https://www.myagileprivacy.com/en/';

if( $locale && $locale == 'it_IT' )
{
	$buyLink = 'https://www.myagileprivacy.com/';
}

?>


<?php if( ( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1 ) || $caller == 'genericOptionsWrapper' ) : ?>

	<div class="row mb-4">
		<label for="license_user_status_field" class="col-sm-5 col-form-label">
			<?php echo wp_kses_post( __( 'Your license status', 'MAP_txt' ) ); ?>
		</label>

		<div class="col-sm-7">

			<input
				disabled
				type="text"
				id="license_user_status_field"
				name="license_user_status_field"
				value="<?php echo esc_attr( stripslashes( $the_settings['license_user_status'] ) ); ?>" class="form-control <?php if( $the_settings['license_valid'] && !$the_settings['grace_period'] ) echo esc_attr( 'success_style' ); else echo esc_attr( 'warning_style' );  ?>"/>

			<div class="form-text">
				<?php
					echo wp_kses_post( __( "The status of your license", 'MAP_txt' ) );
				?>.
			</div>

		</div>
	</div> <!-- row -->


<?php endif; ?>

<div class="license_code_extra_wrapper displayNone">

	<div class="license_code_wrapper <?php if( isset( $rconfig ) && isset( $rconfig['lc_hide_local'] ) && $rconfig['lc_hide_local'] == 1 ) echo 'd-none'; ?>">

		<!-- chiave di licenza -->
		<div class="row mb-4">
			<label for="license_code_field" class="col-sm-5 col-form-label">
				<?php echo wp_kses_post( __( 'License code', 'MAP_txt' ) ); ?>
			</label>

			<div class="col-sm-7">

				<input
					type="text"
					class="form-control"
					id="license_code_field"
					name="license_code_field"
					value="<?php echo esc_attr( stripslashes( $the_settings['license_code'] ) ); ?>" />

				<div class="form-text">
					<?php
						echo wp_kses_post( __( "Enter your license key here", 'MAP_txt' ) );
					?>.

					<?php

						if( isset( $the_settings ) && isset( $the_settings['license_code'] ) && $the_settings['license_code'] ):
					?>

					<br>
					<?php
						echo wp_kses_post( __( "Would you like to verify the status of your subscription, download invoices, or carry out other administrative tasks?", 'MAP_txt' ) );
					?><br>

					<a href="https://areaprivata.myagileprivacy.com/" target="blank"><?php echo wp_kses_post( __( "Click here to access your user area.", 'MAP_txt' ) ); ?></a>

					<?php
						endif;
					?>

				</div>

			</div>
		</div> <!-- row -->

		<div class="<?php if( !$the_settings['summary_text'] ) echo 'd-none';  ?>">

			<!-- email associata -->
			<div class="row mb-4 d-none">
				<label for="customer_email_field" class="col-sm-5 col-form-label">
					<?php echo wp_kses_post( __( 'Your e-mail', 'MAP_txt' ) ); ?>
				</label>

				<div class="col-sm-7">

					<input
						disabled
						class="form-control"
						type="text"
						id="customer_email_field"
						name="customer_email_field"
						value="<?php echo esc_attr( stripslashes( $the_settings['customer_email'] ) );  ?>" />

					<div class="form-text">
						<?php
							echo wp_kses_post( __( "The email address linked to the license key", 'MAP_txt' ) );
						?>.
					</div>

				</div>
			</div> <!-- row -->

			<!-- sommario licenza -->
			<div class="row mb-4 d-none">
				<label for="summary_text_field" class="col-sm-5 col-form-label">
					<?php echo wp_kses_post( __( 'License summary', 'MAP_txt' ) ); ?>
				</label>

				<div class="col-sm-7">
					<textarea disabled class="form-control" id="summary_text_field" name="summary_text_field"><?php echo esc_attr( stripslashes( $the_settings['summary_text'] ) ); ?></textarea>
				</div>
			</div> <!-- row -->

		</div>
	</div>

	<div class="hide_code_wrapper <?php if( !( isset( $rconfig ) && isset( $rconfig['lc_hide_local'] ) && $rconfig['lc_hide_local'] == 1) ) echo 'd-none'; ?>">

		<div class="row mb-4">
			<label for="license_code_field" class="col-sm-5 col-form-label">
				<?php echo wp_kses_post( __( 'Reseller info', 'MAP_txt' ) ); ?>:
			</label>

			<div class="col-sm-7">

				<h6>
					<?php echo wp_kses_post( __( 'Your license key is provided by', 'MAP_txt' ) ); ?> <span class="lc_owner_description"><?php if( isset( $rconfig ) && isset( $rconfig['lc_owner_description'] ) ) echo esc_html( $rconfig['lc_owner_description'] ); ?></span> .
				</h6>

				<div class="my-3">
					<strong>
						<?php echo wp_kses_post( __( 'For further information you can check:', 'MAP_txt' ) ); ?>
					</strong>
					<br>

					<span class="d-block lc_owner_website_wrapper <?php if( !( isset( $rconfig ) && isset( $rconfig['lc_owner_website'] ) ) ) echo 'd-none'; ?>">
						<?php echo wp_kses_post( __( 'Reseller Website:', 'MAP_txt' ) ); ?> <span class="lc_owner_website"><?php if( isset( $rconfig ) && isset( $rconfig['lc_owner_website'] ) ) echo '<a target="blank" href="'.esc_attr( $rconfig['lc_owner_website'] ).'">'.$rconfig['lc_owner_website'].'</a>'; ?></span>
					</span>

					<span class="d-block lc_owner_email_wrapper  <?php if( !( isset( $rconfig ) && isset( $rconfig['lc_owner_email'] ) ) ) echo 'd-none'; ?>">
						<?php echo wp_kses_post( __( 'Reseller Mail:', 'MAP_txt' ) ); ?> <span class="lc_owner_email"><?php if( isset( $rconfig ) && isset( $rconfig['lc_owner_email'] ) ) echo  '<a href="mailto:'.esc_attr( $rconfig['lc_owner_email'] ).'">'.$rconfig['lc_owner_email'].'</a>'; ?></span>
					</span>
				</div>

				<button class="button-agile-outline btn-md changeLicenseCode <?php if( $caller != 'genericOptionsWrapper' ) echo 'displayNone'; ?>"><?php echo wp_kses_post( __( 'Change license code', 'MAP_txt' ) ); ?></button>

			</div>
		</div> <!-- row -->

	</div>

</div>


<div class="<?php if( ( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1 ) || $caller == 'genericOptionsWrapper' ) echo 'displayNone'; ?>">

	<div class="row mb-4">

		<label for="dont_ask_license_code_field" class="col-sm-5 col-form-label">
			<?php echo wp_kses_post( __( 'Don\'t you have a license key?', 'MAP_txt' ) ); ?>
		</label>

		<div class="col-sm-7">
			<div class="styled_radio d-inline-flex">
				<div class="round d-flex me-4">

					<input type="hidden" name="dont_ask_license_code_field" value="false" id="enable_cmode_v2_field_no">

					<input
						name="dont_ask_license_code_field"
						class="hideShowInput reverseHideShow"
						data-hide-show-ref="license_code_extra_wrapper"
						type="checkbox"
						value="true"
						id="dont_ask_license_code_field"
						<?php checked( $the_settings['dont_ask_license_code'], true ); ?>>

					<label for="dont_ask_license_code_field" class="me-2 label-checkbox"></label>
					<label for="dont_ask_license_code_field">
						<?php echo esc_html__( 'I don\'t have a license key yet', 'MAP_txt' ); ?>
					</label>
				</div>
			</div> <!-- ./ styled_radio -->

		</div> <!-- /.col-sm-6 -->
	</div> <!-- row -->

	<div class="row mb-4 license_code_extra_wrapper_reverse displayNone">

		<div class="col-sm-5"></div>

		<div class="col-sm-7">

			<a href="<?php echo esc_attr( $buyLink ); ?>" class="btn btn-primary btn-lg" role="button"><?php echo esc_html__( 'Buy now a license code!', 'MAP_txt' ); ?></a>

		</div>

	</div>


</div>


<?php if( $caller == 'genericOptionsWrapper') : ?>

	<div class="row mb-4">
		<label for="last_sync_field" class="col-sm-5 col-form-label">
			<?php echo wp_kses_post( __( 'Last cookies sync', 'MAP_txt' ) ); ?>
		</label>

		<div class="col-sm-7">

			<input
				disabled
				class="form-control"
				type="text"
				id="last_sync_field"
				name="last_sync_field"
				value="<?php if( $the_settings['last_sync'] ) echo esc_attr( stripslashes( $the_settings['last_sync'] ) ); else echo ''; ?>"/>

			<div class="form-text">
				<?php
					echo wp_kses_post( __( "The last time cookies and policy were syncronized with remote db", 'MAP_txt' ) );
				?>.
			</div>

		</div>
	</div> <!-- row -->

<?php endif; ?>