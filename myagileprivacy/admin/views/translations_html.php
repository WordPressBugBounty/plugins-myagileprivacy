<?php

	if( !defined( 'MAP_PLUGIN_NAME' ) )
	{
		exit('Not allowed.');
	}

?>

<script type="text/javascript">
	var map_settings_success_text = '<?php echo wp_kses_post( __( 'Settings updated.', 'MAP_txt' ) ); ?>';
	var map_settings_warning_text ='<?php echo wp_kses_post( __( 'Settings saved successfully, but some mandatory data is missing. Please check the required fields', 'MAP_txt' ) ); ?>';
	var map_settings_error_message_text = '<?php echo wp_kses_post( __( 'Unable to update Settings.', 'MAP_txt' ) ); ?>';
	var unsaved_settings_text = '<?php echo wp_kses_post( __( 'Warning! Unsaved changes. Are you sure you want to leave?', 'MAP_txt' ) ); ?>';
	var map_confirm_lang_reset_text = '<?php echo wp_kses_post( __( 'Continuing will result in the loss of all customizations for this language. Are you sure you want to proceed?', 'MAP_txt' ) ); ?>';
</script>

<div class="wrap translationsWrapper" id="my_agile_privacy_backend">

	<h2>My Agile Privacy: <?php echo wp_kses_post( __( 'Texts and Translations', 'MAP_txt' ) ); ?></h2>

	<form action="admin-ajax.php" method="post" id="map_user_settings_form">

		<input type="hidden" name="action" value="update_translations_form" id="action" />

		<?php
			if( function_exists( 'wp_nonce_field' ) )
			{
				wp_nonce_field( 'myagileprivacy-update-' . MAP_PLUGIN_SETTINGS_FIELD );
			}
		?>

		<div class="mb-3">
			<button class="fake-save-button button-agile btn-md"><?php echo wp_kses_post( __( 'Update Settings', 'MAP_txt' ) ); ?></button>
			<span class="map_wait text-muted">
				<i class="fas fa-spinner-third fa-fw fa-spin" style="--fa-animation-duration: 1s;"></i> <?php echo wp_kses_post( __( 'Saving in progress', 'MAP_txt' ) ); ?>...
			</span>
		</div>

		<div class="container-fluid mt-3">
			<?php include 'inc/inc.translations_tab.php'; ?>
		</div> <!-- ./container-fluid -->


		<div class="row mt-5">
			<div class="col-12">
				<input type="submit" name="update_translations_form" value="<?php echo wp_kses_post( __( 'Update Settings', 'MAP_txt' ) ); ?>" class="button-agile btn-md" id="map-save-button" />
				<span class="map_wait text-muted">
					<i class="fas fa-spinner-third fa-fw fa-spin" style="--fa-animation-duration: 1s;"></i> <?php echo wp_kses_post( __( 'Saving in progress', 'MAP_txt' ) ); ?>...
				</span>
			</div>
		</div>

	</form>

</div>
