<?php

	if( !defined( 'MAP_PLUGIN_NAME' ) )
	{
		exit('Not allowed.');
	}

?>

<script type="text/javascript">
	var map_settings_success_text = '<?php echo esc_html__( 'Settings updated.', 'MAP_txt' ); ?>';
	var map_settings_warning_text ='<?php echo esc_html__( 'Settings saved successfully, but some mandatory data is missing. Please check the required fields', 'MAP_txt' ); ?>';
	var map_settings_error_message_text = '<?php echo esc_html__( 'Unable to update Settings.', 'MAP_txt' ); ?>';
	var map_confirm_clean_cookies_text = '<?php echo esc_html__( 'Warning: this will delete your entire Cookie configuration. Make sure you have exported it first (STEP 1). Do you want to proceed?', 'MAP_txt' ); ?>';
</script>

<?php

if( $css_compatibility_fix ):

?>

<style type="text/css">

.tab-content>.active {
	display: block;
	opacity: 1;
}

</style>


<?php

endif;

?>


<div class="wrap backupRestoreWrapper" id="my_agile_privacy_backend">
	<h2>My Agile Privacy®: <?php echo wp_kses_post( __( 'Backup & Restore', 'MAP_txt' ) ); ?></h2>

	<div class="container-fluid mt-5">
		<?php include 'inc/inc.backup_restore_tab.php'; ?>
	</div> <!-- ./container-fluid -->
</div> <!-- ./wrap -->