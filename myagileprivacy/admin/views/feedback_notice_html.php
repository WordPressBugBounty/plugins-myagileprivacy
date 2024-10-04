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
			<h4><?php _e('Do you want to share your experience with My Agile Privacy?', 'MAP_txt'); ?></h4>
			<p>
				<?php _e("You've been using My Agile Privacy for a while: how about sharing your experience?", 'MAP_txt'); ?><br>
				<?php _e("You would be doing us a <strong>huge favor</strong> in our mission to <strong>help</strong> WordPress website owners with the compliance process in a <strong>simple and intuitive</strong> way.", 'MAP_txt'); ?>
			</p>
			<p>
				<?php _e('<strong>Your 5-star review will help us make My Agile Privacy known to more and more people</strong>. It will be an opportunity to share your opinion and tell us what you like most about our service.', 'MAP_txt'); ?>
			</p>
			<p>
				<a href="<?php echo( esc_attr( $review_url ) ); ?>" class="button-primary" target="_blank"><?php _e('Yes, I want to share my experience', 'MAP_txt'); ?></a>
				<button class="button-secondary" id="map_review_later"><?php _e('I will do it later', 'MAP_txt'); ?></button>
				<button class="button-secondary" id="map_review_done"><?php _e('Already done!', 'MAP_txt'); ?></button>
			</p>
		</div>
	</div>
</div>

<script type="text/javascript">

	var map_feedback_vars = {
		ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
		map_review_nonce: '<?php echo wp_create_nonce('map_review_nonce'); ?>'
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

<style>
	#map_feedback_banner {

		border-radius: 8px;
		background: #fff;
		border: none;
		padding: 3em 4em 1.5em 4em;
		margin: 50px 20px 30px 2px;
		box-shadow: rgba(50, 50, 93, 0.25) 0px 2px 5px -1px, rgba(0, 0, 0, 0.3) 0px 1px 3px -1px;
	}
	#map_feedback_banner .content-wrapper {
		display: flex;
		align-items: center;
		gap:30px;
	}

	#map_feedback_banner .image-container {
		flex-shrink: 0;
		margin-right: 20px;
	}

	#map_feedback_banner .image-container img {
		max-width: 90px;
		height: auto;
	}

	#map_feedback_banner .content-container {
		flex-grow: 1;
	}

	#map_feedback_banner h4 {
		font-size: 20px;
		margin: 0;
	}

	#map_feedback_banner p {
		font-size: 16px;
	}

	#map_feedback_banner .button-primary,
	#map_feedback_banner .button-secondary {
		font-size: 14px;
	}

	@media (max-width: 768px) {
		#map_feedback_banner {
			flex-direction: column;
			align-items: flex-start;
		}

		#map_feedback_banner .image-container {
			margin-right: 0;
			margin-bottom: 20px;
			width: 100%;
			display: flex;
			justify-content: center;
		}

		#map_feedback_banner .image-container img {
			max-width: 50%;
		}

		#map_feedback_banner .content-container {
			width: 100%;
		}
	}
</style>
