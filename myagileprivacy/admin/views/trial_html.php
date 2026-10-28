<?php

	if( !defined( 'MAP_PLUGIN_NAME' ) )
	{
		exit('Not allowed.');
	}

	$map_trial_domain  = MyAgilePrivacyAdmin::map_trial_domain();
	$map_trial_state   = MyAgilePrivacyAdmin::map_trial_state();
	$map_trial_end     = MyAgilePrivacyAdmin::map_trial_end();
	$map_trial_b_state = MyAgilePrivacyAdmin::map_trial_b_state();
	$map_trial_b_end   = MyAgilePrivacyAdmin::map_trial_b_end();
	$map_trial_reseller = MyAgilePrivacyAdmin::map_trial_is_reseller();
	$map_trial_invitable = MyAgilePrivacyAdmin::map_trial_invitable();
	$map_trial_policy = ( MAP_Helpdesk_Links::get_language_key() === 'it' )
						? 'https://www.myagileprivacy.com/personal-data-policy/'
						: 'https://www.myagileprivacy.com/en/personal-data-policy/';
	$map_trial_plans = MyAgilePrivacyAdmin::map_trial_plans_url();

?>

<div class="wrap dashboardOptionsWrapper" id="my_agile_privacy_backend">
	<h1>My Agile Privacy®: <?php echo esc_html__( 'Free trial', 'MAP_txt' ); ?></h1>

	<div class="container-fluid">
		<div class="row">

			<div class="col-md-9">
				<div class="consistent-box">

<?php if( $map_trial_state === 'active' ) : ?>

					<h2 class="alert-heading"><?php echo esc_html__( 'Your free trial is running', 'MAP_txt' ); ?></h2>

					<p>
<?php
	if( $map_trial_end )
	{
		printf(
			/* translators: %s: end date of the running trial */
			esc_html__( 'The trial for this site is active until %s.', 'MAP_txt' ),
			'<strong>' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $map_trial_end ) ) ) . '</strong>'
		);
	}
	else
	{
		echo esc_html__( 'The trial for this site is active.', 'MAP_txt' );
	}
?>
					</p>

					<p><?php echo esc_html__( 'When it ends My Agile Privacy® goes back to the demo version, without any of the features included in the plans on the site.', 'MAP_txt' ); ?></p>

					<p class="mt-3">
						<a class="button-agile btn-md" href="<?php echo esc_url( $map_trial_plans ); ?>" target="_blank" rel="noopener"><?php echo esc_html__( 'See the plans', 'MAP_txt' ); ?><span class="screen-reader-text"> <?php echo esc_html__( 'opens in a new tab', 'MAP_txt' ); ?></span></a>
					</p>

<?php elseif( $map_trial_b_state === 'active' ) : ?>

					<h2 class="alert-heading"><?php echo esc_html__( 'Your free trial of the paid features is running', 'MAP_txt' ); ?></h2>

					<p>
<?php
	if( $map_trial_b_end )
	{
		printf(
			/* translators: %s: end date of the running trial */
			esc_html__( 'Statistics, geolocation and the A/B test are active on this site until %s.', 'MAP_txt' ),
			'<strong>' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $map_trial_b_end ) ) ) . '</strong>'
		);
	}
	else
	{
		echo esc_html__( 'Statistics, geolocation and the A/B test are active on this site.', 'MAP_txt' );
	}
?>
					</p>

					<p><?php echo esc_html__( 'When it ends these three features switch off and your license goes on as before, with nothing for you to do.', 'MAP_txt' ); ?></p>

					<p><?php echo esc_html__( 'An A/B test still running when the trial ends is closed and cannot be reopened. Its results are kept, but the page that shows them is no longer reachable: read them before the last day.', 'MAP_txt' ); ?></p>

					<p><?php echo esc_html__( 'To keep these three features after the trial, upgrade your plan from your private area.', 'MAP_txt' ); ?></p>

					<p class="mt-3">
						<a class="button-agile btn-md" href="<?php echo esc_url( MAP_Helpdesk_Links::get( 'private_area_app' ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html__( 'Go to your private area', 'MAP_txt' ); ?></a>
					</p>

<?php elseif( $map_trial_b_state === 'used' ) : ?>

					<h2 class="alert-heading"><?php echo esc_html__( 'You have already used your free trial', 'MAP_txt' ); ?></h2>

					<p><?php echo esc_html__( 'The trial of the paid features is one per site, and this site has already had it.', 'MAP_txt' ); ?></p>

					<p><?php echo esc_html__( 'To keep these three features after the trial, upgrade your plan from your private area.', 'MAP_txt' ); ?></p>

					<p class="mt-3">
						<a class="button-agile btn-md" href="<?php echo esc_url( MAP_Helpdesk_Links::get( 'private_area_app' ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html__( 'Go to your private area', 'MAP_txt' ); ?></a>
					</p>

<?php elseif( $map_trial_b_state === 'available' ) : ?>

					<h2 class="alert-heading"><?php echo esc_html__( 'Try the paid features for 15 days', 'MAP_txt' ); ?></h2>

					<p class="lead"><?php echo esc_html__( 'Statistics, geolocation and the A/B test, switched on for 15 days. Your license and your invoicing do not change: the trial is free.', 'MAP_txt' ); ?></p>

					<div class="bg-light p-2 mb-3">
						<?php echo esc_html__( 'The trial applies to this site only:', 'MAP_txt' ); ?>
						<strong><?php echo esc_html( $map_trial_domain ); ?></strong>
					</div>

					<p><?php echo esc_html__( 'Please note: you have a single trial for this site.', 'MAP_txt' ); ?></p>

<?php if( $map_trial_reseller ) : ?>

					<p><?php echo esc_html__( 'Your license was sold to you by a reseller: ask them for the trial.', 'MAP_txt' ); ?></p>

<?php else : ?>

					<p class="mt-3">
						<button type="button" class="button-agile btn-md" id="map_trial_b_submit"><?php echo esc_html__( 'Start the free trial', 'MAP_txt' ); ?></button>
					</p>

					<div id="map_trial_b_outcome" class="map-trial-outcome" role="status" aria-live="polite" hidden></div>

<?php endif; ?>

<?php elseif( $map_trial_state === 'finished' ) : ?>

					<h2 class="alert-heading"><?php echo esc_html__( 'Your free trial has ended', 'MAP_txt' ); ?></h2>

					<p>
<?php
	if( $map_trial_end )
	{
		printf(
			/* translators: %s: the day the trial ended */
			esc_html__( 'The trial for this site ended on %s, and this site is back to the demo version.', 'MAP_txt' ),
			'<strong>' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $map_trial_end ) ) ) . '</strong>'
		);
	}
	else
	{
		echo esc_html__( 'The trial for this site has ended, and this site is back to the demo version.', 'MAP_txt' );
	}
?>
					</p>

					<p><?php echo esc_html__( 'The trial is one per site and cannot be asked for again.', 'MAP_txt' ); ?></p>

					<p class="mt-3">
						<a class="button-agile btn-md" href="<?php echo esc_url( $map_trial_plans ); ?>" target="_blank" rel="noopener"><?php echo esc_html__( 'See the plans', 'MAP_txt' ); ?><span class="screen-reader-text"> <?php echo esc_html__( 'opens in a new tab', 'MAP_txt' ); ?></span></a>
					</p>

<?php elseif( $map_trial_state === 'requested' ) : ?>

					<h2 class="alert-heading"><?php echo esc_html__( 'You have already asked for the trial', 'MAP_txt' ); ?></h2>

					<p><?php echo esc_html__( 'We have sent you a confirmation link: open it to start the trial. The link stays valid for 8 hours.', 'MAP_txt' ); ?></p>

					<p><?php echo esc_html__( 'If it does not arrive, check the spam folder before asking again.', 'MAP_txt' ); ?></p>

<?php elseif( !$map_trial_invitable ) : ?>

					<?php // guard: the menu item is not registered in this state ?>
					<h2 class="alert-heading"><?php echo esc_html__( 'Free trial', 'MAP_txt' ); ?></h2>

					<p><?php echo esc_html__( 'This feature is not available with your license.', 'MAP_txt' ); ?></p>

<?php else : ?>

					<p class="lead"><?php echo esc_html__( 'Try the paid version of My Agile Privacy® on this site for 15 days. Statistics, geolocation and the Banner A/B Test are not included. No credit card required.', 'MAP_txt' ); ?></p>

					<div class="bg-light p-2 mb-3">
						<?php echo esc_html__( 'The trial is valid for this site:', 'MAP_txt' ); ?>
						<strong><?php echo esc_html( $map_trial_domain ); ?></strong>
					</div>

					<form id="map_trial_form" class="map-trial-form" novalidate>

						<div class="row">
							<div class="col-md-12 mb-3">
								<fieldset>
									<legend class="map-trial-legend"><?php echo esc_html__( 'Are you a company or an individual?', 'MAP_txt' ); ?></legend>

									<label class="me-4" for="map_trial_type_company">
										<input type="radio" name="map_trial_type" id="map_trial_type_company" value="azienda" checked>
										<?php echo esc_html__( 'Company', 'MAP_txt' ); ?>
									</label>

									<label for="map_trial_type_individual">
										<input type="radio" name="map_trial_type" id="map_trial_type_individual" value="privato">
										<?php echo esc_html__( 'Individual', 'MAP_txt' ); ?>
									</label>
								</fieldset>
							</div>
						</div>

						<div class="row">
							<div class="col-md-6 mb-3">
								<label class="map-trial-label" for="map_trial_name"><?php echo esc_html__( 'Full name, or company name', 'MAP_txt' ); ?></label>
								<input type="text" class="map-trial-input" id="map_trial_name" name="map_trial_name" maxlength="120" autocomplete="organization" required>
							</div>

							<div class="col-md-6 mb-3">
								<label class="map-trial-label" for="map_trial_email"><?php echo esc_html__( 'Email address', 'MAP_txt' ); ?></label>
								<input type="email" class="map-trial-input" id="map_trial_email" name="map_trial_email" maxlength="190" autocomplete="email" required>
							</div>
						</div>

						<div class="row">
							<div class="col-12 mb-3">
								<label for="map_trial_consent">
									<input type="checkbox" id="map_trial_consent" name="map_trial_consent" value="1">
									<small>
										<?php
											echo wp_kses(
												sprintf(
													/* translators: %s: privacy policy link */
													__( 'I consent to receive promotional material as indicated in point c of the <a href="%s" target="_blank">privacy policy</a>', 'MAP_txt' ),
													esc_url( $map_trial_policy )
												),
												array( 'a' => array( 'href' => array(), 'target' => array() ) )
											);
										?>
									</small>
								</label>
							</div>
						</div>

						<div class="row">
							<div class="col-12">
								<button type="submit" class="button-agile btn-md" id="map_trial_submit"><?php echo esc_html__( 'Request the free trial', 'MAP_txt' ); ?></button>
							</div>
						</div>

					</form>

					<div id="map_trial_outcome" class="map-trial-outcome" role="status" aria-live="polite" hidden></div>

<?php endif; ?>

				</div>
			</div>

			<div class="col-md-3">

				<img src="<?php echo esc_url( plugin_dir_url( dirname( __FILE__ ) ) . 'img/fox-laptop-thumbs-up.png' ); ?>" class="img-fluid" alt="">

				<div class="text-center mt-4">
					<strong><?php echo esc_html__( 'Questions about the trial?', 'MAP_txt' ); ?></strong><br>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . MAP_POST_TYPE_COOKIES . '&page=' . MAP_POST_TYPE_COOKIES . '_helpdesk' ) ); ?>" class="link-secondary"><?php echo esc_html__( 'Go to the Helpdesk', 'MAP_txt' ); ?></a>
					<?php echo esc_html__( 'or', 'MAP_txt' ); ?>
					<a href="<?php echo esc_url( MAP_Helpdesk_Links::get( 'contact' ) ); ?>" class="link-secondary"><?php echo esc_html__( 'Contact us', 'MAP_txt' ); ?></a>
				</div>
			</div>

		</div>
	</div>
</div>
