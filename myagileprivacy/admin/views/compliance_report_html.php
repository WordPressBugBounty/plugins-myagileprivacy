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

<div class="wrap genericOptionsWrapper" id="my_agile_privacy_backend">
	<h2>My Agile Privacy: <?php _e( 'Compliance Report', 'myagileprivacy' ); ?></h2>

    <div class="container-fluid mt-5">
        <?php include 'inc/inc.compliance_report_tab.php'; ?>
    </div> <!-- ./container-fluid -->
</div> <!-- ./wrap -->



