<?php

	if( !defined( 'MAP_PLUGIN_NAME' ) )
	{
		exit('Not allowed.');
	}


	//Get the active tab from the $_GET param
	$default_tab = null;
	$tab = $default_tab;

	//taking pages for policies url
	$get_pages_args = array(
		'sort_order'    => 	'ASC',
		'sort_column'   => 	'post_title',
		'hierarchical'  => 	0,
		'child_of'      => 	0,
		'parent'        => 	-1,
		'offset'        => 	0,
		'post_type'     => 	'page',
		'post_status'   => 	'publish'
	);
	$all_pages_for_policies_select = get_pages( $get_pages_args );

	//retrocompatibility
	$cookie_banner_vertical_position = $the_settings['cookie_banner_vertical_position'];

	if( !$cookie_banner_vertical_position )
	{
		if( $the_settings['is_bottom'] )
		{
			$cookie_banner_vertical_position = 'Bottom';
		}
		else
		{
			$cookie_banner_vertical_position = 'Top';
		}

		$the_settings['cookie_banner_vertical_position'] = $cookie_banner_vertical_position;
		$the_settings['cookie_banner_horizontal_position'] = 'Center';
		$the_settings['cookie_banner_size'] = 'sizeBoxed';
	}

	//iab and cmode check
	$cmode_enabled = isset( $the_settings['enable_cmode_v2'] ) ? $the_settings['enable_cmode_v2'] : false;
	$bypass_cmode_enable = isset( $the_settings['bypass_cmode_enable'] ) ? $the_settings['bypass_cmode_enable'] : false;
	$hide_cmode_alert = true;

	if( isset( $the_settings['pa'] ) &&
		$the_settings['pa'] == 1 &&
		!$cmode_enabled &&
		!$bypass_cmode_enable )
	{
		$hide_cmode_alert = false;
	}

?>

<script type="text/javascript">
	var map_settings_success_text = '<?php echo wp_kses_post( __( 'Settings updated.', 'MAP_txt' ) ); ?>';
	var map_settings_warning_text ='<?php echo wp_kses_post( __( 'Settings saved successfully, but some mandatory data is missing. Please check the required fields', 'MAP_txt' ) ); ?>';
	var map_settings_error_message_text = '<?php echo wp_kses_post( __( 'Unable to update Settings.', 'MAP_txt' ) ); ?>';
	var unsaved_settings_text = '<?php echo wp_kses_post( __( 'Warning! Unsaved changes. Are you sure you want to leave?', 'MAP_txt' ) ); ?>';
</script>


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

	<div class="iab_cmode_alert alert alert-warning <?php if( $hide_cmode_alert ){ echo 'd-none'; } ?>">
		<?php echo wp_kses_post( __( '<strong>Attention: You have not enabled Google Consent Mode v2.</strong>', 'MAP_txt' ) ); ?>
		<br>
		<?php echo wp_kses_post( __( 'This may cause issues when using tools within the Google ecosystem, such as Google Analytics, Google Tag Manager, Google Ads, or other tools. Enable Consent Mode v2 if you intend to use these tools, in compliance with regulations.', 'MAP_txt' ) ); ?>
	</div>

	<div id="mapx_banner" class="d-none">
		<?php

			$locale = MyAgilePrivacy::get_locale();

			if( isset( $the_settings['pa'] ) &&
				$the_settings['pa'] == 1 )
			{
				if( $locale && $locale == 'it_IT' )
				{
					echo '<a href="https://www.myagilepixel.com/?utm_source=referral&utm_medium=plugin-map-pro&utm_campaign=backend" target="blank"><img class="img-fluid" src="'.esc_attr( plugin_dir_url( __DIR__  ) ).'/img/banner-pixel-it.png" ></a>';
				}
				else
				{
					echo '<a href="https://www.myagilepixel.com/?utm_source=referral&utm_medium=plugin-map-basic&utm_campaign=backend" target="blank"><img class="img-fluid" src="'.esc_attr( plugin_dir_url( __DIR__  ) ).'/img/banner-pixel-en.png" ></a>';
				}
			}
			else
			{
				if( $locale && $locale == 'it_IT' )
				{
					echo '<a href="https://www.myagilepixel.com/?utm_source=referral&utm_medium=plugin-map-pro&utm_campaign=backend" target="blank"><img class="img-fluid" src="'.esc_attr( plugin_dir_url( __DIR__ ) ).'/img/banner-pixel-it.png" ></a>';
				}
				else
				{
					echo '<a href="https://www.myagilepixel.com/?utm_source=referral&utm_mediumplugin-map-basic&utm_campaign=backend" target="blank"><img class="img-fluid" src="'.esc_attr( plugin_dir_url( __DIR__  ) ).'/img/banner-pixel-en.png" ></a>';
				}
			}
		?>


	</div>


	<?php
	if( $wasm_environment ):
	?>

		<div class="alert alert-danger alert-dismissible fade show mt-5">
			<?php echo wp_kses_post( __( '<b>Warning</b>: You are using a PHP.wasm environment. Due to the limitations of this stack, which emulates a real web server, some functionalities may not work as expected.', 'MAP_txt' ) ); ?>
		</div>

	<?php
	endif;
	?>

	<form action="admin-ajax.php" method="post" id="map_user_settings_form">
		<input type="hidden" name="action" value="update_admin_settings_form" id="action" />
		<?php
			if( function_exists( 'wp_nonce_field' ) )
			{
				wp_nonce_field( 'myagileprivacy-update-' . MAP_PLUGIN_SETTINGS_FIELD );
			}
		?>

		<div class="container-fluid mt-5">
			<ul class="nav nav-pills mb-4" role="tablist">

				<li class="nav-item" role="presentation">
					<button class="nav-link active position-relative" data-bs-toggle="pill" data-bs-target="#cookie_banner" type="button" role="tab">
						<i class="fa-regular fa-browser"></i>
						<?php echo wp_kses_post( __( 'Cookie Banner', 'MAP_txt' ) ); ?>
					</button>
				</li>

				<li class="nav-item" role="presentation">
					<button class="nav-link position-relative" data-bs-toggle="pill" data-bs-target="#license" type="button" role="tab">
						<i class="fa-regular fa-key"></i>
						<?php echo wp_kses_post( __( 'License', 'MAP_txt' ) ); ?>
					</button>
				</li>

				<li class="nav-item" role="presentation">
				<button class="nav-link position-relative" data-bs-toggle="pill" data-bs-target="#identity" type="button" role="tab">
						<i class="fa-regular fa-address-card"></i>
						<?php echo wp_kses_post( __( 'Identity', 'MAP_txt' ) ); ?>
					</button>
				</li>

				<li class="nav-item" role="presentation">
					<button class="nav-link position-relative" data-bs-toggle="pill" data-bs-target="#policies" type="button" role="tab">
						<i class="fa-regular fa-files"></i>
						<?php echo wp_kses_post( __( 'Policies and Regulations', 'MAP_txt' ) ); ?>
					</button>
				</li>

				<li class="nav-item" role="presentation">
					<button class="nav-link position-relative" data-bs-toggle="pill" data-bs-target="#consent_widget" type="button" role="tab">
						<i class="fa-regular fa-tablet-screen"></i>
						<?php echo wp_kses_post( __( 'Consent', 'MAP_txt' ) ); ?>
					</button>
				</li>

				<li class="nav-item" role="presentation">
					<button class="nav-link position-relative premium" data-bs-toggle="pill" data-bs-target="#cookieshield" type="button" role="tab">
						<i class="fa-regular fa-shield"></i>
						<?php echo wp_kses_post( __( 'Cookie Shield', 'MAP_txt' ) ); ?>

						<span class="position-absolute top-0 end-0 translate-middle-y badge rounded-pill forbiddenWarning bg-danger  <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] == 1){echo 'd-none';} ?>">
							<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
						</span>
					</button>
				</li>

				<li class="nav-item" role="presentation">
					<button class="nav-link position-relative" data-bs-toggle="pill" data-bs-target="#advanced" type="button" role="tab">
						<i class="fa-solid fa-sliders-up"></i>
						<?php echo wp_kses_post( __( 'Advanced', 'MAP_txt' ) ); ?>
					</button>
				</li>
			</ul>

			<div class="mb-3">
				<button class="fake-save-button button-agile btn-md"><?php echo wp_kses_post( __( 'Update Settings', 'MAP_txt' ) ); ?></button>
				<span class="map_wait text-muted">
					<i class="fas fa-spinner-third fa-fw fa-spin" style="--fa-animation-duration: 1s;"></i> <?php echo wp_kses_post( __( 'Saving in progress', 'MAP_txt' ) ); ?>...
				</span>
			</div>


			<div class="tab-content">
				<div class="tab-pane fade show active" id="cookie_banner" role="tabpanel">
					<?php include 'inc/inc.cookie_banner_tab.php'; ?>
				</div> <!-- tabpane cookie_banner -->

				<div class="tab-pane fade show" id="consent_widget" role="tabpanel">
						<?php include 'inc/inc.consent_widget_tab.php'; ?>
				</div> <!-- tabpane license -->

				<div class="tab-pane fade show" id="policies" role="tabpanel">
						<?php include 'inc/inc.policies_tab.php'; ?>
				</div> <!-- tabpane license -->

				<div class="tab-pane fade show" id="identity" role="tabpanel">
					<?php include 'inc/inc.identity_tab.php'; ?>
				</div> <!-- tabpane license -->

				<div class="tab-pane fade show" id="license" role="tabpanel">
					<?php include 'inc/inc.license_tab.php'; ?>
				</div> <!-- tabpane license -->

				<div class="tab-pane fade show" id="cookieshield" role="tabpanel">
					<?php include 'inc/inc.cookieshield_tab.php'; ?>
				</div> <!-- tabpane license -->

				<div class="tab-pane fade show" id="advanced" role="tabpanel">
					<?php include 'inc/inc.advanced_tab.php'; ?>
				</div> <!-- tabpane license -->

			</div> <!-- /.tab-content -->

			<div class="row">
				<div class="col-12">
					<input type="submit" name="update_admin_settings_form" value="<?php echo wp_kses_post( __( 'Update Settings', 'MAP_txt' ) ); ?>" class="button-agile btn-md" id="map-save-button" />
					<span class="map_wait text-muted">
						<i class="fas fa-spinner-third fa-fw fa-spin" style="--fa-animation-duration: 1s;"></i> <?php echo wp_kses_post( __( 'Saving in progress', 'MAP_txt' ) ); ?>...
					</span>
				</div>
			</div>

		</div> <!-- /.container-fluid -->

	</form>
</div> <!-- /#my_agile_privacy_backend -->

<?php
