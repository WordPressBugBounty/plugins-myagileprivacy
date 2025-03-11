<?php

if( !defined( 'MAP_PLUGIN_NAME' ) )
{
	exit('Not allowed.');
}

$caller = 'dashboardOptionsWrapper';

?>

<div class="container-fluid mt-5">
	<h2 class="mb-4"><?php echo wp_kses_post( __( 'Privacy Status', 'MAP_txt' ) ); ?></h2>

	<div class="row">

		<div class="col-md-9">

			<div class="row g-4 consistent-alert-container mt-3">
				<div class="col-md-4">

					<div class="alert <?php echo ( $is_on ) ? 'alert-success' : 'alert-danger'; ?> h-50 d-flex flex-column justify-content-between">
						<div>
							<h5 class="alert-heading"><i class="fas fa-toggle-<?php echo ( $is_on ) ? 'on' : 'off'; ?>"></i> <?php echo wp_kses_post( __( 'Banner Status', 'MAP_txt' ) ); ?></h5>
							<p class="mt-3 fw-bold data-text text-<?php echo ( $is_on ) ? 'success' : 'danger'; ?>">
								<?php echo ( $is_on ) ? wp_kses_post( __( 'On', 'MAP_txt' ) ) : wp_kses_post( __( 'Off', 'MAP_txt' ) ); ?>
							</p>
						</div>
					</div>
				</div>
				<div class="col-md-4">
					<div class="alert consistent-alert h-50 d-flex flex-column justify-content-between">
						<div>
							<h5 class="alert-heading"><i class="fas fa-cookie"></i> <?php echo wp_kses_post( __( 'Cookies detected', 'MAP_txt' ) ); ?></h5>
							<p class="mt-3 fw-bold data-text">
								<?php echo esc_html( $cookie_published_count ); ?>
							</p>
						</div>
					</div>
				</div>

				<div class="col-md-4">
					<div class="alert consistent-alert h-50 d-flex flex-column justify-content-between">
						<div>
							<h5 class="alert-heading"><i class="fas fa-calendar-alt"></i> <?php echo wp_kses_post( __( 'Last Scan', 'MAP_txt' ) ); ?></h5>
							<p class="mt-3 fw-bold data-text">
								<?php echo esc_html( $last_scan_date_human ); ?>
							</p>
						</div>
					</div>
				</div>

			</div>


			<div class="row g-4">


				<form action="admin-ajax.php" method="post" id="map_user_settings_form" class="reload_at_afterfinish">
					<input type="hidden" name="action" value="update_admin_settings_form" id="action" />
					<?php
						if( function_exists( 'wp_nonce_field' ) )
						{
							wp_nonce_field( 'myagileprivacy-update-' . MAP_PLUGIN_SETTINGS_FIELD );
						}
					?>
					<div class="mt-4">
						<div class="row g-1">

							<div class="consistent-box">
								<div class="row mt-3 mb-4">
									<div class="col-12">
										<div class="progress" style="height: 3rem;">
											<div class="progress-bar <?php echo esc_attr( $global_integrity_checks['summary']['status_class_name'] ); ?>" role="progressbar" style="width: <?php echo esc_attr( $global_integrity_checks['summary']['completion_percentage_width'] ); ?>%"><strong><?php echo wp_kses_post( __( 'Configuration completion status', 'MAP_txt' ) ); ?>: <?php echo esc_attr( $global_integrity_checks['summary']['completion_percentage'] ); ?>%</strong></div>
										</div>
									</div>
								</div>
								<div class="mb-3">
									<button class="fake-save-button button-agile btn-md"><?php echo wp_kses_post( __( 'Update Settings', 'MAP_txt' ) ); ?></button>
									<span class="map_wait text-muted">
										<i class="fas fa-spinner-third fa-fw fa-spin" style="--fa-animation-duration: 1s;"></i> <?php echo wp_kses_post( __( 'Saving in progress', 'MAP_txt' ) ); ?>...
									</span>
								</div>
								<div class="accordion" id="settingsAccordion">
									<div class="accordion-item mb-1">
										<h2 class="accordion-header" id="headingIdentity">
											<button class="fs-5 accordion-button collapsed alert-<?php echo esc_attr( $global_integrity_checks['dashboard_checks']['identity']['status_class_name'] ); ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseIdentity" aria-expanded="false" aria-controls="collapseIdentity">
												<i class="fas fa-id-card me-3"></i>
												<?php echo wp_kses_post( __( 'Identity Information', 'MAP_txt' ) ); ?>
												<small class="d-inline-block ms-3"><?php echo ( $global_integrity_checks['dashboard_checks']['identity']['check'] ) ? wp_kses_post( __( 'Configuration complete', 'MAP_txt' ) ) : wp_kses_post( __( 'Needs attention', 'MAP_txt' ) ); ?></small>
											</button>
										</h2>
										<div id="collapseIdentity" class="accordion-collapse collapse" aria-labelledby="headingIdentity" data-bs-parent="#settingsAccordion">
											<div class="accordion-body">
												<div class="alert alert-secondary d-flex flex-column justify-content-between">
													<p class="mt-3 fw-bold fs-6">
														<?php
															if( $global_integrity_checks['dashboard_checks']['identity']['check'] ):
																echo wp_kses_post( __( 'Identity configuration is complete. You can check and modify the information below.', 'MAP_txt' ) );
															else:
																echo wp_kses_post( __( 'Some identity information is missing. Please complete the information below.', 'MAP_txt' ) );
															endif;
														?>
													</p>
												</div>
												<?php include plugin_dir_path(__FILE__) . 'fields.identity_tab.php'; ?>
											</div>
										</div>
									</div> <!-- accordion-item-->
									<div class="accordion-item mb-1">
										<h2 class="accordion-header" id="headingVerification">
											<button class="fs-5 accordion-button collapsed alert-<?php echo esc_attr( $global_integrity_checks['dashboard_checks']['consent_mode']['status_class_name'] ); ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseVerification" aria-expanded="false" aria-controls="collapseVerification">
												<i class="fas fa-check-circle me-3"></i>
												<?php echo wp_kses_post( __( 'Consent Mode v2 Status', 'MAP_txt' ) ); ?>
												<small class="d-inline-block ms-3"><?php echo ( $global_integrity_checks['dashboard_checks']['consent_mode']['check'] ) ? wp_kses_post( __( 'Configuration complete', 'MAP_txt' ) ) : wp_kses_post( __( 'Needs attention', 'MAP_txt' ) ); ?></small>
											</button>
										</h2>
										<div id="collapseVerification" class="accordion-collapse collapse" aria-labelledby="headingVerification" data-bs-parent="#settingsAccordion">
											<div class="accordion-body">
												<div class="alert alert-secondary d-flex flex-column justify-content-between">
													<p class="mt-3 fw-bold fs-6">
														<?php
															if( $global_integrity_checks['dashboard_checks']['consent_mode']['is_skipped'] )
															{
																echo wp_kses_post( __( 'Consent Mode v2 is bypassed because you have chosen not to use Google products.', 'MAP_txt' ) );
															} else
															{
																if( $global_integrity_checks['dashboard_checks']['consent_mode']['is_enabled'] ):
																	echo wp_kses_post( __( 'Consent Mode v2 is enabled. You can review the settings below.', 'MAP_txt' ) );
																else:
																	echo wp_kses_post( __( 'Consent Mode v2 is not enabled. Check the settings below.', 'MAP_txt' ) );
																endif;
															}
														?>
													</p>
												</div>
												<div class="bg-light p-2 mb-3">
													<div class="styled_radio d-inline-flex">
														<div class="round d-flex me-4">
															<input type="hidden" name="bypass_cmode_enable_field" value="false" id="bypass_cmode_enable_field_no">
															<input name="bypass_cmode_enable_field" class="hideShowInput reverseHideShow" data-hide-show-ref="cmode_fields" type="checkbox" value="true" id="bypass_cmode_enable_field" <?php checked( $the_settings['bypass_cmode_enable'], true ); ?>>
															<label for="bypass_cmode_enable_field" class="me-2 label-checkbox"></label>
															<label for="bypass_cmode_enable_field">
																<?php echo esc_html__('I do not use Google products and I do not need Consent Mode v2', 'MAP_txt'); ?>
															</label>
														</div>
													</div>
												</div>

												<?php include 'fields.consent_mode_tab.php'; ?>

											</div>
										</div>
									</div> <!-- accordion-item-->
									<div class="accordion-item mb-1">
										<h2 class="accordion-header" id="headingCookieShield">
											<button class="fs-5 accordion-button collapsed alert-<?php echo esc_attr( $global_integrity_checks['dashboard_checks']['cookie_shield']['status_class_name'] ); ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCookieShield" aria-expanded="false" aria-controls="collapseCookieShield">
												<i class="fas fa-shield-alt me-3"></i>
												<?php echo wp_kses_post( __( 'Cookie Shield Status', 'MAP_txt' ) ); ?>
												<small class="d-inline-block ms-3"><?php echo ( $global_integrity_checks['dashboard_checks']['cookie_shield']['check'] ) ? wp_kses_post( __( 'Configuration complete', 'MAP_txt' ) ) : wp_kses_post( __( 'Needs attention', 'MAP_txt' ) ); ?></small>
											</button>
										</h2>
										<div id="collapseCookieShield" class="accordion-collapse collapse" aria-labelledby="headingCookieShield" data-bs-parent="#settingsAccordion">
											<div class="accordion-body">
												<div class="alert alert-secondary d-flex flex-column justify-content-between">
													<p class="mt-3 fw-bold fs-6">
														<?php
															if( $global_integrity_checks['dashboard_checks']['cookie_shield']['check'] ):
																echo wp_kses_post( __( 'Cookie Shield is active and running. You can review the settings below.', 'MAP_txt' ) );
															else:
																echo wp_kses_post( __( 'Cookie Shield is not active or needs your review. Check the settings below.', 'MAP_txt' ) );
															endif;
														?>
													</p>
												</div>
												<?php include plugin_dir_path(__FILE__) . 'fields.cookieshield_tab.php'; ?>
											</div>
										</div>
									</div> <!-- accordion-item-->
								</div> <!-- accordion -->
								<div class="row mt-3">
									<div class="col-12">
										<input type="submit" name="update_admin_settings_form" value="<?php echo wp_kses_post( __( 'Update Settings', 'MAP_txt' ) ); ?>" class="button-agile btn-md" id="map-save-button" />
										<span class="map_wait text-muted">
											<i class="fas fa-spinner-third fa-fw fa-spin" style="--fa-animation-duration: 1s;"></i> <?php echo wp_kses_post( __( 'Saving in progress', 'MAP_txt' ) ); ?>...
										</span>
									</div>
								</div>
							</div> <!-- .consistent-box -->


						</div> <!-- row -->

					</div>
				</form>

			</div>
		</div>

		<div class="col-md-3">

			<img src="<?php echo esc_attr( plugin_dir_url( __DIR__ ) ); ?>../img/fox-laptop-thumbs-up.png" class="img-fluid" alt="">

			<?php
				$admin_lang = get_locale();
				$helpdesk_href = admin_url( 'edit.php?post_type=my-agile-privacy-c&page=my-agile-privacy-c_helpdesk' );
				$contact_href = ( $admin_lang == 'it_IT' ) ? 'https://www.myagileprivacy.com/contattaci/' : 'https://www.myagileprivacy.com/en/contact-us/';
			?>

			<div class="text-center mt-4">
				<strong><?php echo wp_kses_post( __( 'Welcome to the My Agile Privacy Dashboard.<br>Need help?', 'MAP_txt' ) ); ?></strong><br>
				<a href="<?php echo esc_attr( $helpdesk_href ); ?>" class="link-secondary"><?php echo wp_kses_post( __( 'Go to the Helpdesk', 'MAP_txt' ) ); ?></a> <?php echo wp_kses_post( __( 'or', 'MAP_txt') ); ?> <a href="<?php echo esc_attr( $contact_href ); ?>" class="link-secondary"><?php echo wp_kses_post( __( 'Contact us', 'MAP_txt' ) ); ?></a>
			</div>
		</div>
	</div>

</div>

