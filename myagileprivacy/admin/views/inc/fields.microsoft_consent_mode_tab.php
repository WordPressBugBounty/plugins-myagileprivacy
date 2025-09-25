<?php

if( !defined( 'MAP_PLUGIN_NAME' ) )
{
	exit('Not allowed.');
}

$first_class = '';
$second_class = 'd-none';

if( isset( $the_settings['pa'] ) &&
	$the_settings['pa'] == 1
)
{
	$first_class = 'd-none';
	$second_class = '';
}

?>

<span class="translate-middle-y badge rounded-pill forbiddenWarning bg-danger  <?php if( isset( $the_settings['pa'] ) && $the_settings['pa'] ){echo 'd-none';} ?>">
	<small><?php echo wp_kses_post( __( 'Premium Feature', 'MAP_txt' ) ); ?></small>
</span>


<div class="consistent-box">
	<h4 class="mb-4">
		<i class="fa-brands fa-microsoft"></i>
		<?php echo wp_kses_post( __( 'Microsoft UET Consent Mode', 'MAP_txt' ) ); ?>
	</h4>

	<div class="row mb-1">

		<div class="col-sm-12">

			<p>
				<?php echo wp_kses_post( __( "This feature allows you to implement Microsoft's UET Consent Mode.", 'MAP_txt' ) ); ?>

				<?php echo wp_kses_post( __( "Without this feature, you might not be able to properly use Microsoft's ecosystem products such as Microsoft Ads.", 'MAP_txt' ) ); ?>
			</p>
		</div>
	</div>


	<div class="<?php echo esc_attr( $second_class );?>">

		<!-- widget review consent show -->
		<div class="row mb-4">
			<label for="enable_microsoft_cmode_field" class="col-sm-5 col-form-label">
				<?php echo wp_kses_post( __( 'Enable Microsoft UET Consent Mode', 'MAP_txt' ) ); ?>
			</label>

			<div class="col-sm-7">
				<div class="styled_radio d-inline-flex">
					<div class="round d-flex me-4">

						<input type="hidden" name="enable_microsoft_cmode_field" value="false" id="enable_microsoft_cmode_field_no">

						<input
							name="enable_microsoft_cmode_field"
							type="checkbox"
							value="true"
							id="enable_microsoft_cmode_field"
							class="hideShowInput"
							data-hide-show-ref="enable_microsoft_cmode_options"
							<?php checked( $the_settings['enable_microsoft_cmode'], true ); ?>>

						<label for="enable_microsoft_cmode_field" class="me-2 label-checkbox"></label>
						<?php

							$microsoft_cmode_link = 'https://www.myagileprivacy.com/en/how-to-implement-microsoft-consent-mode-with-my-agile-privacy';

							if( $locale && $locale == 'it_IT' )
							{
								$microsoft_cmode_link = 'https://www.myagileprivacy.com/come-implementare-microsoft-consent-mode-con-my-agile-privacy';
							}
						?>

						<label for="enable_microsoft_cmode_field">
							<?php echo sprintf(__('Enable Microsoft UET Consent Mode - %1$sOnline Help%2$s', 'MAP_txt'), '<a href="' . esc_attr( $microsoft_cmode_link ) . '" target="_blank">', '</a>'); ?>
						</label>
					</div>
				</div> <!-- ./ styled_radio -->

			</div> <!-- /.col-sm-6 -->
		</div> <!-- row -->


		<div class="enable_microsoft_cmode_options displayNone">

			<div class="m-0 p-0 row alert">

				<label for="microsoft_consent_ad_storage_field" class="col-sm-5 col-form-label">
					<?php echo wp_kses_post( __( 'Ad Storage', 'MAP_txt' ) ); ?><br>
					<span class="form-text">
						<?php echo wp_kses_post( __( 'Defines whether cookies related to advertising can be read or written by Microsoft.', 'MAP_txt' ) ); ?>
					</span>
				</label>

				<div class="col-sm-7">

					<select
						id="microsoft_consent_ad_storage_field"
						name="microsoft_consent_ad_storage_field"
						class="form-control" style="max-width:100%;">
						<?php

						$valid_options = array(
							'denied'    =>  array(  'label' => esc_attr( __( 'Denied', 'MAP_txt' ) ),
																	'selected' => false ),
							'granted'   =>  array(  'label' => esc_attr( __( 'Granted', 'MAP_txt' ) ),
																	'selected' => false ),
						);

						$selected_value = $the_settings['microsoft_consent_ad_storage'];

						if( isset( $valid_options[ $selected_value ] ) )
						{
							$valid_options[ $selected_value ]['selected'] = true;
						}

						foreach( $valid_options as $key => $data )
						{
							if( $data['selected'] )
							{
								?>
								<option value="<?php echo esc_attr( $key ); ?>" selected><?php echo esc_attr( $data['label'] ); ?></option>
								<?php
							}
							else
							{
								?>
								<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $data['label'] );?></option>
								<?php
							}
						}

						?>
					</select>

					<div class="suggested-value-alert d-none">

						<strong>
							<?php
								echo wp_kses_post( __( 'Suggested value for compliance:', 'MAP_txt' ) );
							?>
						</strong> denied

					</div>
				</div> <!-- col -->
			</div>

		</div>

	</div>
</div>