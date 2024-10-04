<?php

	if( !defined( 'MAP_PLUGIN_NAME' ) )
	{
		exit('Not allowed.');
	}

?>

<div class="row compliance-report-row">
	<div class="col-sm-8">

		<div class="consistent-box">
			<h4 class="mb-4">
				<i class="fa-regular fa-file-certificate"></i>
				<?php _e('Compliance Report', 'MAP_txt'); ?>
			</h4>

			<div class="row mb-5">
				<div class="col-sm-12">
				<?php _e('Here you can find the compliance report for your website.', 'MAP_txt'); ?><br>
				<?php _e('The report is automatically generated based on the plugin configuration status, therefore its reliability is extremely high.', 'MAP_txt'); ?><br>
				<?php _e('However, if you have any doubts, we invite you to contact our support team.', 'MAP_txt'); ?>
				</div>
			</div>

			<div class="progress">
				<div class="progress-bar bg-success" role="progressbar" style="width: 33%"><strong><?php _e('Compliant', 'MAP_txt'); ?>: 33%</strong></div>
			</div>
			<div class="row mb-5">
				<div class="table-responsive">
					<table class="table">
						<tr>
							<td></td>
							<td></td>
						</tr>

						<tr>
							<th><?php _e('Policy', 'MAP_txt'); ?></th>
							<td class="text-end">
								<div class="badge rounded-pill bg-warning text-dark" data-bs-toggle="tooltip" title="Tooltip bla bla bla">
									<i class="fa-regular fa-triangle-exclamation"></i>
									<?php _e('Partially Compliant', 'MAP_txt'); ?>
								</div>
							</td>
						</tr>

						<tr>
							<th><?php _e('Cookie Banner', 'MAP_txt'); ?></th>
							<td class="text-end">
								<div class="badge rounded-pill bg-success">
									<i class="fa-regular fa-circle-check"></i>
									<?php _e('Compliant', 'MAP_txt'); ?>
								</div>
							</td>
						</tr>

						<tr>
							<th><?php _e('Google Analytics consistency', 'MAP_txt'); ?></th>
							<td class="text-end">
								<div class="badge rounded-pill bg-danger" data-bs-toggle="tooltip" title="Tooltip bla bla bla">
									<i class="fa-regular fa-circle-xmark"></i>
									<?php _e('Not Compliant', 'MAP_txt'); ?>
								</div>
							</td>
						</tr>

						<tr>
							<th><?php _e('Compliance total', 'MAP_txt'); ?></th>
							<th class="text-end">
								1 <?php _e('out of', 'MAP_txt'); ?> 3
							</th>
						</tr>
					</table>
				</div>
			</div>


		</div> <!-- consistent-box -->
	</div> <!-- /.col-sm-8 -->

	<div class="col-sm-4">
		<?php
			$tab = 'advanced';
			include 'inc.admin_sidebar.php';
		?>
	</div>
</div> <!-- /.row -->