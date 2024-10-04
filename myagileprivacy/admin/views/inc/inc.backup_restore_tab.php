<?php

	if( !defined( 'MAP_PLUGIN_NAME' ) )
	{
		exit('Not allowed.');
	}

?>

<div class="row">
	<div class="col-sm-8">

		<div class="consistent-box">
			<h4 class="mb-4">
				<i class="fa-regular fa-cloud-arrow-down"></i>
				<?php _e('Backup & Restore', 'MAP_txt'); ?>
			</h4>


			<div class="row mb-5 loadingMessage">
				<div class="col-sm-12">
					<?php _e('Please wait: page loading.', 'MAP_txt'); ?>
				</div>
			</div>

			<div class="loadingWrapper displayNone">

				<div class="row mb-5">
					<div class="col-sm-12">
					<?php _e('Backup and restore your cookie list configuration.', 'MAP_txt'); ?>
					</div>
				</div>

				<div class="card fullwidth">
					<div class="row mb-3">
						<div class="col-sm-12">
							<h4><?php _e('STEP 1', 'MAP_txt'); ?></h4>
							<?php _e('Pressing the following button will download your cookie configuration.', 'MAP_txt'); ?>
						</div>
					</div>

					<div class="row">
						<div class="col-sm-12">
							<form action="admin-post.php" method="post" id="">
								<input type="hidden" name="action" value="backup_admin_settings_form" id="action" />
								<input type="hidden" name="do" value="1" />

								<?php
									if( function_exists( 'wp_nonce_field' ) )
									{
										wp_nonce_field( 'myagileprivacy-update-' . MAP_PLUGIN_SETTINGS_FIELD );
									}
								?>

								<input type="submit" name="backup_admin_settings_form" value="<?php _e('Export Cookie Settings', 'MAP_txt'); ?>" class="btn-lg button-agile" />
							</form>
						</div>
					</div>
				</div> <!-- /.card -->

				<div class="card fullwidth">
					<div class="row mb-3">
						<div class="col-sm-12">
							<h4><?php _e('STEP 2', 'MAP_txt'); ?></h4>
							<?php _e('Pressing the following button will clean up your cookie configuration. Warning: do export first, and full page reload before importing again.', 'MAP_txt'); ?>
						</div>
					</div>

					<div class="row">
						<div class="col-sm-12">
							<form action="admin-ajax.php" method="post" id="map_user_settings_form" class="reload_at_afterfinish">
								<input type="hidden" name="action" value="update_admin_settings_form" id="action" />
								<input type="hidden" name="reset_cookie_settings"  value="1" id="reset_settings">

								<?php
									if( function_exists( 'wp_nonce_field' ) )
									{
										wp_nonce_field( 'myagileprivacy-update-' . MAP_PLUGIN_SETTINGS_FIELD );
									}
								?>


								<input type="submit" name="update_admin_settings_form" value="<?php _e('Clean All Cookie', 'MAP_txt'); ?>" class="btn-lg button-agile" />
							</form>
						</div>
					</div>
				</div><!-- /.card -->

				<div class="card fullwidth">
					<div class="row mb-3">
						<div class="col-sm-12">
							<h4><?php _e('STEP 3', 'MAP_txt'); ?></h4>
							<?php _e('Restore your previously saved configuration. Please wait page fully loaded before importing.', 'MAP_txt'); ?>
						</div>
					</div>

					<form action="admin-post.php" method="post" id="" enctype="multipart/form-data">
						<input type="hidden" name="action" value="import_admin_settings_form" id="action" />
						<input type="hidden" name="do" value="1" />
						<?php
							if( function_exists( 'wp_nonce_field' ) )
							{
								wp_nonce_field( 'myagileprivacy-update-' . MAP_PLUGIN_SETTINGS_FIELD );
							}
						?>

						<div class="row">
							<div class="col-sm-6">
								<input type="file" name="the_imported_file" >
							</div>

							<div class="col-sm-6">
								<input type="submit" name="import_admin_settings_form" value="<?php _e('Import Cookie Settings', 'MAP_txt'); ?>" class="btn-lg button-agile" />
							</div>
						</div>
					</form>
				</div> <!-- /.card -->

			</div> <!-- /.loadingWrapper -->


		</div> <!-- consistent-box -->
	</div> <!-- /.col-sm-8 -->

	<div class="col-sm-4">

		<?php
			$tab = 'backup';
			include 'inc.admin_sidebar.php';
		?>
	</div>
</div> <!-- /.row -->