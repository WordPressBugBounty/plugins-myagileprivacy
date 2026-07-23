<?php

if( !defined( 'MAP_PLUGIN_NAME' ) )
{
	exit('Not allowed.');
}

// All URLs are locale-resolved by the central helper (see MAP_Helpdesk_Links).
$installa_map_url = MAP_Helpdesk_Links::get( 'install' );
$cookie_shied_url = MAP_Helpdesk_Links::get( 'cookie_shield' );
$moduli_norma_url = MAP_Helpdesk_Links::get( 'contact_forms' );

$cache_wprocket_url = MAP_Helpdesk_Links::get( 'cache_wprocket' );
$cache_siteground_url = MAP_Helpdesk_Links::get( 'cache_siteground' );
$cache_optimizepress_url = MAP_Helpdesk_Links::get( 'cache_optimizepress' );
$cache_w3totalcache_url = MAP_Helpdesk_Links::get( 'cache_w3tc' );
$cache_speedycache_url = MAP_Helpdesk_Links::get( 'cache_speedycache' );
$cache_litespeed_url = MAP_Helpdesk_Links::get( 'cache_litespeed' );
$cache_generic_url = MAP_Helpdesk_Links::get( 'cache_generic' );

$scheda_tecnica_url = MAP_Helpdesk_Links::get( 'technical_sheet' );
$sito_multilingue_url = MAP_Helpdesk_Links::get( 'multilanguage' );

$sito_area_faq_url = MAP_Helpdesk_Links::get( 'post_install_faq' );
$sito_area_privata_url = MAP_Helpdesk_Links::get( 'private_area_guide' );

$personalizzazione_cookie_banner = MAP_Helpdesk_Links::get( 'customize_banner' );

$consent_mode_v2 = MAP_Helpdesk_Links::get( 'consent_mode_v2' );

$cmode_uet = MAP_Helpdesk_Links::get( 'microsoft_cmode' );
$cmode_clarity = MAP_Helpdesk_Links::get( 'clarity_cmode' );
$gtg_url = MAP_Helpdesk_Links::get( 'gtg' );

$policy_customization = MAP_Helpdesk_Links::get( 'policy_assistant' );
?>

<div class="row">
	<div class="col-12">
		<p><?php echo wp_kses_post( __( 'Follow the guides found in this section to install and configure My Agile Privacy®, and bring your website into compliance in a few simple steps.', 'MAP_txt' ) ); ?></p>
	</div>
</div>

<div class="row">
	<div class="col-sm-8">
		<div class="card-group">
			<div class="card rounded-3 p-0 mx-1">
				<div class="card-header bg-transparent"><h5 class="pt-2"><?php echo wp_kses_post( __( 'Start from here', 'MAP_txt' ) ); ?></h5></div>

				<div class="card-body">
					<p>
						<?php echo wp_kses_post( __( 'My Agile Privacy® is currently the easiest-to-configure Cookie Banner on the market, promising genuine compliance and adherence to the regulations as required by the Data Protection Authority.', 'MAP_txt' ) ); ?><br><br>
						<?php echo wp_kses_post( __( 'We have created a truly simple configuration process: to bring your website into compliance, you just need to follow the three steps outlined below.', 'MAP_txt' ) ); ?>

					</p>

					<p><a target="_blank" href="<?php echo esc_attr( $installa_map_url ); ?>" ><i class="fa-regular fa-link orange-icon"></i> <?php echo wp_kses_post( __( 'Install and configure My Agile Privacy®', 'MAP_txt' ) ); ?></a></p>
					<p><a target="_blank" href="<?php echo esc_attr( $cookie_shied_url ); ?>" ><i class="fa-regular fa-link orange-icon"></i> <?php echo wp_kses_post( __( 'Detect and automatically block cookies with Cookie Shield', 'MAP_txt' ) ); ?></a></p>
					<p><a target="_blank" href="<?php echo esc_attr( $moduli_norma_url ); ?>" ><i class="fa-regular fa-link orange-icon"></i> <?php echo wp_kses_post( __( 'Adapt the contact forms to GDPR', 'MAP_txt' ) ); ?></a></p>

					<hr>

					<p>

						<?php echo wp_kses_post( __( 'Customizations', 'MAP_txt' ) ); ?>

						<br>

						<a target="_blank" href="<?php echo esc_attr( $personalizzazione_cookie_banner ); ?>" ><i class="fa-regular fa-link orange-icon"></i> <?php echo wp_kses_post( __( 'Customize cookie banner, text and translations, and user notifications', 'MAP_txt' ) ); ?></a>
					</p>

					<hr>

					<p>

						<?php echo wp_kses_post( __( 'Google Consent Mode V2', 'MAP_txt' ) ); ?>

						<br>

						<a target="_blank" href="<?php echo esc_attr( $consent_mode_v2 ); ?>" ><i class="fa-regular fa-link orange-icon"></i> <?php echo wp_kses_post( __( 'How to implement Google Consent Mode V2 on your website', 'MAP_txt' ) ); ?></a>
					</p>

					<p>

						<?php echo wp_kses_post( __( 'Microsoft Consent Mode', 'MAP_txt' ) ); ?>

						<br>

						<a target="_blank" href="<?php echo esc_attr( $cmode_uet ); ?>" ><i class="fa-regular fa-link orange-icon"></i> <?php echo wp_kses_post( __( 'How to implement Microsoft Consent Mode on your website', 'MAP_txt' ) ); ?></a>
					</p>

					<p>

						<?php echo wp_kses_post( __( 'Clarity Consent Mode', 'MAP_txt' ) ); ?>

						<br>

						<a target="_blank" href="<?php echo esc_attr( $cmode_clarity ); ?>" ><i class="fa-regular fa-link orange-icon"></i> <?php echo wp_kses_post( __( 'How to implement Clarity Consent Mode on your website', 'MAP_txt' ) ); ?></a>
					</p>

					<p>

						<?php echo wp_kses_post( __( 'Google Tag Gateway (GTG)', 'MAP_txt' ) ); ?>

						<br>

						<a target="_blank" href="<?php echo esc_attr( $gtg_url ); ?>" ><i class="fa-regular fa-link orange-icon"></i> <?php echo wp_kses_post( __( 'Google Tag Gateway - integration guide', 'MAP_txt' ) ); ?></a>
					</p>


					<hr>

					<p>

						<?php echo wp_kses_post( __( 'GDPR, CCPA, nLPD, PIPEDA, LGPD: Which privacy regulations should I choose and how do I customize the policies?', 'MAP_txt' ) ); ?>

						<br>

						<a target="_blank" href="<?php echo esc_attr( $policy_customization ); ?>" ><i class="fa-regular fa-link orange-icon"></i> <?php echo wp_kses_post( __( 'Learn how to customize your Privacy Policies', 'MAP_txt' ) ); ?></a>
					</p>



				</div>


			</div>

			<div class="card rounded-3 p-0 mx-1">
				<div class="card-header bg-transparent"><h5 class="pt-2"><?php echo wp_kses_post( __( 'Do you use a cache plugin?', 'MAP_txt' ) ); ?></h5></div>
				<div class="card-body">
					<p>
						<?php echo wp_kses_post( __( 'My Agile Privacy® works correctly with most cache plugins available on the market.', 'MAP_txt' ) ); ?><br><br>
						<?php echo wp_kses_post( __( 'Below are the configuration guides for the most commonly used ones. Make sure to follow the instructions to ensure compliance and performance.', 'MAP_txt' ) ); ?>
					</p>
					<p><a target="_blank" href="<?php echo esc_attr( $cache_wprocket_url ); ?>" ><i class="fa-regular fa-link orange-icon"></i> <?php echo wp_kses_post( __( 'How to configure WP Rocket with My Agile Privacy®', 'MAP_txt' ) ); ?></a></p>
					<p><a target="_blank" href="<?php echo esc_attr( $cache_siteground_url ); ?>" ><i class="fa-regular fa-link orange-icon"></i> <?php echo wp_kses_post( __( 'How to configure Siteground Optimizer with My Agile Privacy®', 'MAP_txt' ) ); ?></a></p>
					<p><a target="_blank" href="<?php echo esc_attr( $cache_optimizepress_url ); ?>" ><i class="fa-regular fa-link orange-icon"></i> <?php echo wp_kses_post( __( 'How to configure Optimize Press with My Agile Privacy®', 'MAP_txt' ) ); ?></a></p>
					<p><a target="_blank" href="<?php echo esc_attr( $cache_w3totalcache_url ); ?>" ><i class="fa-regular fa-link orange-icon"></i> <?php echo wp_kses_post( __( 'How to configure W3 Total Cache with My Agile Privacy®', 'MAP_txt' ) ); ?></a></p>
					<p><a target="_blank" href="<?php echo esc_attr( $cache_speedycache_url ); ?>" ><i class="fa-regular fa-link orange-icon"></i> <?php echo wp_kses_post( __( 'How to configure Speedy Cache with My Agile Privacy®', 'MAP_txt' ) ); ?></a></p>
					<p><a target="_blank" href="<?php echo esc_attr( $cache_litespeed_url ); ?>" ><i class="fa-regular fa-link orange-icon"></i> <?php echo wp_kses_post( __( 'How to configure LiteSpeed Cache with My Agile Privacy®', 'MAP_txt' ) ); ?></a></p>
					<p><a target="_blank" href="<?php echo esc_attr( $cache_generic_url ); ?>" ><i class="fa-regular fa-link orange-icon"></i> <?php echo wp_kses_post( __( 'Do you use another cache plugin?', 'MAP_txt' ) ); ?></a></p>
				</div>
			</div>
		</div>
		
		<div class="card-group">

			<div class="card rounded-3 p-0 mx-1">
				<div class="card-header bg-transparent"><h5 class="pt-2"><?php echo wp_kses_post( __( 'Do you have a multilingual website?', 'MAP_txt' ) ); ?></h5></div>
				<div class="card-body">
					<p>
						<?php echo wp_kses_post( __( 'My Agile Privacy® is compatible with the main multilingual plugins for WordPress and provides ready-made translations for elements such as the cookie banner, policies, and individual cookies in multiple languages.', 'MAP_txt' ) ); ?><br><br>
						<?php echo wp_kses_post( __( 'Depending on the license you have purchased, you will have access to one or more languages.', 'MAP_txt' ) ); ?><br>
						<?php echo wp_kses_post( sprintf(__( 'For the updated list and implementation instructions, please refer to the <a href="%s" target="_blank">technical sheet.</a>', 'MAP_txt' ), esc_attr( $scheda_tecnica_url )) ); ?><br><br>
						<?php echo wp_kses_post( __( 'Follow these implementation guides for a correct display of textual content.', 'MAP_txt' ) ); ?>
					</p>
					<p><a target="_blank" href="<?php echo esc_attr( $sito_multilingue_url ); ?>" ><i class="fa-regular fa-link orange-icon"></i> <?php echo wp_kses_post( __( 'How to configure My Agile Privacy® for multilingual websites', 'MAP_txt' ) ); ?></a></p>
				</div>
	
			</div>
			<div>	
				<div class="card rounded-3 mx-1">
					<div class="row">
						<div class="col-2 align-self-center"><i class="fa-regular fa-circle-question fa-2xl orange-icon"></i></div>
						<div class="col-10">
							<h5><?php echo wp_kses_post( __( 'Questions?', 'MAP_txt' ) ); ?></h5>
							<p><?php echo wp_kses_post( __( 'Visit our FAQ section to view answers to the most common post-installation questions.', 'MAP_txt' ) ); ?></p>
							<a target="_blank" href="<?php echo esc_attr( $sito_area_faq_url ); ?>" class="btn btn-outline-orange"><?php echo wp_kses_post( __( 'Go to the FAQ', 'MAP_txt' ) ); ?></a>
						</div>
					</div>
				</div>
				<div class="card rounded-3 mx-1">
					<div class="row">
						<div class="col-2 align-self-center"><i class="fa-regular fa-user-unlock fa-2xl orange-icon"></i></div>
						<div class="col-10">
							<h5><?php echo wp_kses_post( __( 'Subscription management and bulk operations', 'MAP_txt' ) ); ?></h5>
							<p><?php echo wp_kses_post( __( "Discover how to manage your subscription and multiple installations through the dedicated private area.", 'MAP_txt' ) ); ?></p>
							<a target="_blank" href="<?php echo esc_attr( $sito_area_privata_url ); ?>" class="btn btn-outline-orange"><?php echo wp_kses_post( __( "Go to the guide related to the private area.", 'MAP_txt' ) ); ?></a>
						</div>
					</div>
				</div>	
			</div>

		</div>

	</div> <!-- col-sm-8 -->

	<div class="col-sm-4">
		<img src="<?php echo esc_attr( plugin_dir_url( __DIR__ ) ); ?>../img/fox-helpdesk.png" class="img-fluid" alt="">

		<?php
			$helpdesk_href = MAP_Helpdesk_Links::get( 'helpdesk_index' );
			$contact_href = MAP_Helpdesk_Links::get( 'contact' );
		?>

		<div class="text-center mt-4">
			<strong><?php echo wp_kses_post( __( 'Need more help?', 'MAP_txt' ) ); ?></strong><br>
			<a href="<?php echo esc_attr( $helpdesk_href ); ?>" class="link-secondary"><?php echo wp_kses_post( __( 'Go to the Helpdesk on the website', 'MAP_txt' ) ); ?></a> <?php echo wp_kses_post( __( 'or', 'MAP_txt' ) ); ?> <a href="<?php echo esc_attr( $contact_href ); ?>" class="link-secondary"><?php echo wp_kses_post( __( 'Contact us', 'MAP_txt' ) ); ?></a>
		</div>
	</div> <!-- col-sm-4 -->

</div>