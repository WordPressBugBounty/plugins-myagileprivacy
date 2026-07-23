<?php

if( !defined( 'MAP_PLUGIN_NAME' ) )
{
	exit('Not allowed.');
}

?>

<script type="text/javascript">
	var map_settings_success_text = '<?php echo esc_html__( 'Settings updated.', 'MAP_txt' );?>';
	var map_settings_warning_text ='<?php echo esc_html__( 'Settings saved successfully, but some mandatory data is missing. Please check the required fields', 'MAP_txt' );?>';
	var map_settings_error_message_text = '<?php echo esc_html__( 'Unable to update Settings.', 'MAP_txt' );?>';
</script>

<div class="wrap dashboardOptionsWrapper" id="my_agile_privacy_backend">
	<div class="container-fluid mt-5">
			<?php include 'inc/inc.dashboard_tab.php'; ?>
		</div> <!-- ./container-fluid -->
	</div>