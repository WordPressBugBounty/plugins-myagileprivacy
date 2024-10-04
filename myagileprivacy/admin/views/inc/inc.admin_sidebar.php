<?php

	if( !defined( 'MAP_PLUGIN_NAME' ) )
	{
		exit('Not allowed.');
	}

	$locale = get_user_locale();

	if( $the_options['pa'] == 1 )
	{
		if( $locale == 'it_IT' )
		{
			echo '<a href="https://www.myagileprivacy.com/?utm_source=referral&utm_medium=plugin-pro&utm_campaign=backend" target="blank"><img class="img-fluid" src="'.plugin_dir_url( __DIR__  ).'../img/banner_pro_ita.png" ></a>';
		}
		else
		{
			echo '<a href="https://www.myagileprivacy.com/?utm_source=referral&utm_medium=plugin-basic&utm_campaign=backend" target="blank"><img class="img-fluid" src="'.plugin_dir_url( __DIR__  ).'../img/banner_pro_eng.png" ></a>';
		}
	}
	else
	{
		if( $locale == 'it_IT' )
		{
			echo '<a href="https://www.myagileprivacy.com/?utm_source=referral&utm_medium=plugin-pro&utm_campaign=backend" target="blank"><img class="img-fluid" src="'.plugin_dir_url( __DIR__ ).'../img/banner_ita.png" ></a>';
		}
		else
		{
			echo '<a href="https://www.myagileprivacy.com/?utm_source=referral&utm_medium=plugin-basic&utm_campaign=backend" target="blank"><img class="img-fluid" src="'.plugin_dir_url( __DIR__  ).'../img/banner_eng.png" ></a>';
		}
	}

?>

<div>

	<div class="my-5 d-flex flex-row justify-content-center">
		<a href="https://support.google.com/admanager/answer/13554116?hl=en&ref_topic=9760861&sjid=17685026765202216942-AP#zippy=,google-certified-cmps" target="_blank" rel="nofollow" style="margin-left:15px;margin-right:15px;cursor:pointer;"><img src="<?php echo plugin_dir_url( __DIR__  ); ?>../img/google-cmp-badge.png" style="height:80px; cursor:pointer;"></a>

		<a href="https://iabeurope.eu/cmp-list/" target="_blank" rel="nofollow"  style="margin-left:15px;margin-right:15px;cursor:pointer;"><img src="<?php echo plugin_dir_url( __DIR__  ); ?>../img/iab-banner.png" style="height:80px; cursor:pointer;"></a>

	</div>
</div>

<?php

	$installed_html = '';

	if( isset( $map_stats ) && $map_stats && $map_stats->active_installs )
	{
		$installed_html = '<br><b>'.$map_stats->active_installs.'+</b>';

		if( $locale == 'it_IT' )
		{
			$installed_html .= ' installazioni attive';
		}
		else
		{
			$installed_html .= ' active installations';
		}
	}

?>
<div>
	<div class="">
		<a href="<?php echo esc_attr( admin_url('edit.php?post_type=my-agile-privacy-c&page=my-agile-privacy-c_helpdesk') ); ?>" class="btn btn-success d-flex justify-content-center align-items-center gap-4">
			<i class="fa-regular fa-circle-question fa-xl"></i>
			<span style="text-align: left;">
				<?php _e('<strong>Do you need support?</strong><br><u>Click here</u> and check the Help Desk section', 'MAP_txt'); ?>

			</span>
		</a>
	</div>
	<p class="text-center"><?php echo $installed_html; ?></p>
</div>
