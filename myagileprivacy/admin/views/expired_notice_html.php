<?php
	if( !defined( 'MAP_PLUGIN_NAME' ) )
	{
		exit('Not allowed.');
	}

	$locale = MyAgilePrivacy::get_locale();

	$the_url = '#';

	if( defined( 'MAP_EXPIRED_CALLBACK_URL_DEFAULT' ) )
	{
		$the_url = ( $locale === 'it_IT' ) ? MAP_EXPIRED_CALLBACK_URL_IT : MAP_EXPIRED_CALLBACK_URL_DEFAULT;
	}
?>
<div class="agile-notice" id="map_expired_banner">
	<div class="content-wrapper">
		<div class="image-container">
			<img width="80" src="<?php echo esc_attr( plugin_dir_url( __DIR__ ) ); ?>img/warning_triangle.png" alt="">
		</div>
		<div class="content-container">
			<h4><?php echo wp_kses_post( __( 'License issue detected for My Agile Privacy®', 'MAP_txt' ) ); ?></h4>
			<p>
				<?php echo wp_kses_post( __( 'There is a problem with your My Agile Privacy® license. Some features may be limited or unavailable until the issue is resolved.', 'MAP_txt' ) ); ?>
			</p>
			<p>
				<?php echo wp_kses_post( __( '<strong>Please check your license status and renew or reactivate it</strong> to restore full functionality and keep your website compliant.', 'MAP_txt' ) ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( $the_url ); ?>" class="button button-primary">
					<?php echo esc_html( __( 'Manage your license', 'MAP_txt' ) ); ?>
				</a>
			</p>
		</div>
	</div>
</div>
