<?php

if( !defined( 'MAP_PLUGIN_NAME' ) )
{
	exit('Not allowed.');
}


$caller = 'genericOptionsWrapper';

?>

<div class="row">

	<?php include 'fields.cookieshield_tab.php' ?>


	<div class="col-sm-4">
		<?php
			$tab = 'scanner';
			include 'inc.admin_sidebar.php';
		?>
	</div>
</div> <!-- /.row -->