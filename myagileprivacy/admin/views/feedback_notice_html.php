<?php

	if( !defined( 'MAP_PLUGIN_NAME' ) )
	{
		exit('Not allowed.');
	}

?>

<div class="agile-notice" id="map_feedback_banner">
	<div class="content-wrapper">
		<div class="image-container">
			<img src="<?php echo esc_attr( plugin_dir_url( __DIR__ ) ); ?>img/logo-flat.png" alt="">
		</div>
		<div class="content-container">
			<h4><?php echo wp_kses_post( __( 'Do you want to share your experience with My Agile Privacy®?', 'MAP_txt' ) ); ?></h4>
			<p>
				<?php echo wp_kses_post( __( "You've been using My Agile Privacy® for a while: how about sharing your experience?", 'MAP_txt' ) ); ?><br>
				<?php echo wp_kses_post( __( "You would be doing us a <strong>huge favor</strong> in our mission to <strong>help</strong> WordPress website owners with the compliance process in a <strong>simple and intuitive</strong> way.", 'MAP_txt' ) ); ?>
			</p>
			<p>
				<?php echo wp_kses_post( __( '<strong>Your 5-star review will help us make My Agile Privacy® known to more and more people</strong>. It will be an opportunity to share your opinion and tell us what you like most about our service.', 'MAP_txt' ) ); ?>
			</p>
			<p>
				<a href="<?php echo( esc_attr( $review_url ) ); ?>" class="button-primary" target="_blank"><?php echo wp_kses_post( __( 'Yes, I want to share my experience', 'MAP_txt' ) ); ?></a>
				<button class="button-secondary" id="map_review_later"><?php echo wp_kses_post( __( 'I will do it later', 'MAP_txt' ) ); ?></button>
				<button class="button-secondary" id="map_review_done"><?php echo wp_kses_post( __( 'Already done!', 'MAP_txt' ) ); ?></button>
			</p>
		</div>
	</div>
</div>

<script type="text/javascript">

	var map_feedback_vars = {
		ajax_url: '<?php echo esc_attr( admin_url('admin-ajax.php') ); ?>',
		map_review_nonce: '<?php echo esc_attr( wp_create_nonce('map_review_nonce') ); ?>'
	};

	jQuery( document ).ready(function()
	{
		jQuery( '#map_review_later' ).on( 'click', function() {
			jQuery.post( ajaxurl, {
				action: 'map_review_later',
				security: map_feedback_vars.map_review_nonce
			});
			jQuery( this ).closest( '.agile-notice' ).hide();
			console.log( 'MAP review-later event' );
		});

		jQuery( '#map_review_done' ).on( 'click', function() {

			jQuery.post( ajaxurl, {
				action: 'map_review_done',
				security: map_feedback_vars.map_review_nonce
			});

			jQuery( this ).closest( '.agile-notice' ).hide();
			console.log( 'MAP review-done event' );
		});
	});
</script>
