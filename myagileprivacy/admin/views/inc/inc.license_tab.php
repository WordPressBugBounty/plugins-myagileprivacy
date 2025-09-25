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
				<i class="fa-regular fa-key"></i>
				<?php echo wp_kses_post( __( 'Your license details', 'MAP_txt' ) ); ?>
			</h4>

			<?php include 'fields.license_tab.php'; ?>

		</div> <!-- consistent-box -->
	</div> <!-- /.col-sm-8 -->

	<div class="col-sm-4">
		<?php
			$tab = null;
			include 'inc.admin_sidebar.php';
		?>
	</div>
</div> <!-- /.row -->