<?php

if( !defined( 'MAP_PLUGIN_NAME' ) )
{
	exit('Not allowed.');
}

?>

<div class="row">
	<div class="col-sm-8">

		<?php
				$map_opt_import = isset( $_GET['map_options_import'] ) ? sanitize_key( $_GET['map_options_import'] ) : '';
				if( 'success' === $map_opt_import )
				{
					echo '<div class="alert alert-success">' . esc_html__( 'Options imported successfully.', 'MAP_txt' ) . '</div>';
				}
				elseif( 'invalid' === $map_opt_import )
				{
					echo '<div class="alert alert-warning">' . esc_html__( 'The uploaded file is not a valid options backup.', 'MAP_txt' ) . '</div>';
				}
				elseif( 'corrupted' === $map_opt_import )
				{
					echo '<div class="alert alert-danger">' . esc_html__( 'The options backup file is corrupted and cannot be imported.', 'MAP_txt' ) . '</div>';
				}
				elseif( 'error' === $map_opt_import )
				{
					echo '<div class="alert alert-danger">' . esc_html__( 'Options import failed: invalid or unreadable file.', 'MAP_txt' ) . '</div>';
				}
			?>


			<div class="row mb-5 loadingMessage">
				<div class="col-sm-12">
					<?php echo wp_kses_post( __( 'Please wait: page loading.', 'MAP_txt' ) ); ?>
				</div>
			</div>

			<div class="loadingWrapper displayNone">

				<div class="nav nav-pills" role="tablist">
					<button class="nav-link active" data-bs-toggle="pill" data-bs-target="#map_backup_cookies" type="button" role="tab"><i class="fa-solid fa-cookie-bite"></i> <?php echo wp_kses_post( __( 'Cookies', 'MAP_txt' ) ); ?></button>
					<?php if( defined( 'MAP_ENABLE_ADVANCED_INTEGRATION' ) && MAP_ENABLE_ADVANCED_INTEGRATION ) : ?>
					<button class="nav-link position-relative premium" data-bs-toggle="pill" data-bs-target="#map_backup_options" type="button" role="tab"><i class="fa-solid fa-sliders"></i> <?php echo wp_kses_post( __( 'Plugin Options', 'MAP_txt' ) ); ?>

						<span class="position-absolute top-0 end-0 translate-middle-y badge rounded-pill forbiddenWarning bg-danger <?php if( function_exists( 'map_is_advanced_active' ) && map_is_advanced_active() ){echo 'd-none';} ?>">
							<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
						</span>
					</button>
					<?php endif; ?>
				</div>

				<div class="tab-content">

				<div class="tab-pane fade show active" id="map_backup_cookies" role="tabpanel">

				<div class="row mb-5">
					<div class="col-sm-12">
					<?php echo wp_kses_post( __( 'Backup and restore your Cookie list configuration.', 'MAP_txt' ) ); ?>
					</div>
				</div>

				<div class="card fullwidth map-step-card">
					<div class="row mb-3">
						<div class="col-sm-12">
							<h4><?php echo wp_kses_post( __( 'STEP 1', 'MAP_txt' ) ); ?></h4>
							<?php echo wp_kses_post( __( 'Pressing the following button will download your Cookie configuration.', 'MAP_txt' ) ); ?>
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

								<input type="submit" name="backup_admin_settings_form" value="<?php esc_attr_e('Export Cookie Settings', 'MAP_txt' ); ?>" class="btn-lg button-agile" />
							</form>
						</div>
					</div>
				</div> <!-- /.card -->

				<div class="card fullwidth map-step-card">
					<div class="row mb-3">
						<div class="col-sm-12">
							<h4><?php echo wp_kses_post( __( 'STEP 2', 'MAP_txt' ) ); ?></h4>
							<?php echo wp_kses_post( __( 'Pressing the following button will clean up your Cookie configuration. Warning: do export first, and full page reload before importing again.', 'MAP_txt' ) ); ?>
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


								<input type="submit" name="update_admin_settings_form" value="<?php esc_attr_e('Clean All Cookie', 'MAP_txt' ); ?>" class="btn-lg button-agile" />
							</form>
						</div>
					</div>
				</div><!-- /.card -->

				<div class="card fullwidth map-step-card">
					<div class="row mb-3">
						<div class="col-sm-12">
							<h4><?php echo wp_kses_post( __( 'STEP 3', 'MAP_txt' ) ); ?></h4>
							<?php echo wp_kses_post( __( 'Restore your previously saved configuration. Please wait page fully loaded before importing.', 'MAP_txt' ) ); ?>
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
								<input type="submit" name="import_admin_settings_form" value="<?php esc_attr_e('Import Cookie Settings', 'MAP_txt' ); ?>" class="btn-lg button-agile" />
							</div>
						</div>
					</form>
				</div> <!-- /.card -->

				</div> <!-- /.tab-pane cookies -->

				<?php if( defined( 'MAP_ENABLE_ADVANCED_INTEGRATION' ) && MAP_ENABLE_ADVANCED_INTEGRATION ) : ?>

				<div class="tab-pane fade show" id="map_backup_options" role="tabpanel">

				<div class="row mb-5">
					<div class="col-sm-12">
						<?php echo wp_kses_post( __( 'Export and import the plugin configuration.', 'MAP_txt' ) ); ?>
					</div>
				</div>

				<?php if( function_exists( 'map_is_advanced_active' ) && map_is_advanced_active() ) : ?>

				<div class="card fullwidth map-step-card">
					<div class="row mb-3">
						<div class="col-sm-12">
							<h4><?php echo wp_kses_post( __( 'Export Options', 'MAP_txt' ) ); ?></h4>
							<?php echo wp_kses_post( __( 'Download a JSON file with your plugin options.', 'MAP_txt' ) ); ?>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-12">
							<form action="admin-post.php" method="post">
								<input type="hidden" name="action" value="map_export_options_form" />
								<?php if( function_exists( 'wp_nonce_field' ) ) { wp_nonce_field( 'map_export_options' ); } ?>
								<input type="submit" value="<?php esc_attr_e( 'Export Options', 'MAP_txt' ); ?>" class="btn-lg button-agile" />
							</form>
						</div>
					</div>
				</div><!-- /.card -->

				<div class="card fullwidth map-step-card">
					<div class="row mb-3">
						<div class="col-sm-12">
							<h4><?php echo wp_kses_post( __( 'Import Options', 'MAP_txt' ) ); ?></h4>
							<?php echo wp_kses_post( __( 'Restore options from a previously exported file.', 'MAP_txt' ) ); ?>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-12">
							<form action="admin-post.php" method="post" enctype="multipart/form-data">
								<input type="hidden" name="action" value="map_import_options_form" />
								<?php if( function_exists( 'wp_nonce_field' ) ) { wp_nonce_field( 'map_import_options' ); } ?>
								<input type="file" name="map_options_file" accept="application/json,.json" />
								<input type="submit" value="<?php esc_attr_e( 'Import Options', 'MAP_txt' ); ?>" class="btn-lg button-agile" />
							</form>
						</div>
					</div>
				</div><!-- /.card -->

				<?php else : ?>

				<div class="card fullwidth map-step-card">
					<div class="row">
						<div class="col-sm-12">
							<div class="alert alert-info mb-0"><?php echo wp_kses_post( __( 'Options backup & restore is a feature not available for your license.', 'MAP_txt' ) ); ?></div>
						</div>
					</div>
				</div><!-- /.card -->

				<?php endif; ?>

				</div> <!-- /.tab-pane options -->

				<?php endif; ?>

					<?php map_render_help_fox( 'backup_restore' ); ?>
				</div> <!-- /.tab-content -->

			</div> <!-- /.loadingWrapper -->

	</div> <!-- /.col-sm-8 -->

	<div class="col-sm-4">

		<?php
			$tab = 'backup';
			include 'inc.admin_sidebar.php';
		?>
	</div>
</div> <!-- /.row -->