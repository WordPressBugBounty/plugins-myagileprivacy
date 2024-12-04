<?php

	if( !defined( 'MAP_PLUGIN_NAME' ) )
	{
		exit('Not allowed.');
	}

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

<div class="wrap helpdeskWrapper" id="my_agile_privacy_backend">
	<h2>My Agile Privacy: <?php echo wp_kses_post( __( 'Helpdesk', 'MAP_txt' ) ); ?></h2>

	<div class="container-fluid mt-5">
		<?php include 'inc/inc.helpdesk_tab.php'; ?>
	</div> <!-- ./container-fluid -->
</div> <!-- ./wrap -->

