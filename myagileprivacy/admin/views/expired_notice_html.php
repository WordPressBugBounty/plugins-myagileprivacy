<?php
	if( !defined( 'MAP_PLUGIN_NAME' ) )
	{
		exit('Not allowed.');
	}

	$locale = MyAgilePrivacy::get_locale();

	$the_url = '#';

	if( defined( 'MAP_EXPIRED_CALLBACK_URL') && MAP_EXPIRED_CALLBACK_URL )
	{
		$the_key = 'default';

		if( $locale == 'it_IT' )
		{
			$the_key = $locale;
		}

		$the_url = MAP_EXPIRED_CALLBACK_URL[ $the_key ];
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

<style>

.agile-notice {
	border-radius: 8px;
	background: #fff;
	border: none;
	padding: 3em 4em 1.5em 4em;
	margin: 50px 20px 30px 2px;
	box-shadow: rgba(50, 50, 93, 0.25) 0px 2px 5px -1px, rgba(0, 0, 0, 0.3) 0px 1px 3px -1px;
}
.agile-notice .content-wrapper {
	display: flex;
	align-items: center;
	gap:30px;
}

.agile-notice .image-container {
	flex-shrink: 0;
	margin-right: 20px;
}

.agile-notice .image-container img {
	max-width: 90px;
	height: auto;
}

.agile-notice .content-container {
	flex-grow: 1;
}

.agile-notice h4 {
	font-size: 20px;
	margin: 0;
}

.agile-notice p {
	font-size: 16px;
}

.agile-notice .button-primary,
.agile-notice .button-secondary {
	font-size: 14px;
}

@media (max-width: 768px) {
	.agile-notice {
		flex-direction: column;
		align-items: flex-start;
	}

	.agile-notice .image-container {
		margin-right: 0;
		margin-bottom: 20px;
		width: 100%;
		display: flex;
		justify-content: center;
	}

	.agile-notice .image-container img {
		max-width: 50%;
	}

	.agile-notice .content-container {
		width: 100%;
	}
}
</style>