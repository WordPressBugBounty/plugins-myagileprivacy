<?php

if( !defined( 'MAP_PLUGIN_NAME' ) )
{
	exit('Not allowed.');
}
?>


<div class="row mb-4">

	<div class="form-text">

		<?php

			if( !( isset( $rconfig ) && isset( $rconfig['keep_v1_policies'] ) && $rconfig['keep_v1_policies'] ) ):
		?>

		 	<?php echo wp_kses_post( __( 'You can review your configuration at any time using the', 'MAP_txt' ) ); ?> <a href="<?php echo esc_url( $policy_assistant_menu_link ); ?>"><?php echo wp_kses_post( __( 'Policy Assistant.', 'MAP_txt' ) ); ?></a>


		<?php
			else:
		?>

		 	<?php echo wp_kses_post( __( 'The Policy Assistant is not available on this installation. Contact us for assistance.', 'MAP_txt' ) ); ?>

		<?php
			endif;
		?>

	</div>

</div>
