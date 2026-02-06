<?php

if( !defined( 'MAP_PLUGIN_NAME' ) )
{
	exit('Not allowed.');
}

/**
 * Helper class for regulation handling
 * *
 * @link       https://www.myagileprivacy.com/
 * @package    MyAgilePrivacy
 * @subpackage MyAgilePrivacy/includes
 * @author     https://www.myagileprivacy.com/
 */

class MyAgilePrivacyRegulationHelper {

	//stored settings
	private static $the_settings = array();

	//stored site and policy settings
	private static $site_and_policy_settings = array();

	//stored countries
	private static $available_countries = array();

	//stored regulations
	private static $available_regulations = array(
		'gdpr' 			=>	array(
								'area' 			=> 	'eu',
								'regulation'	=>	'gdpr_like',
								'human_name'	=>	'GDPR (EU)',
							),
		'gdpr_gb'		=>	array(
								'area' 			=> 	'gb',
								'regulation'	=>	'gdpr_gb',
								'human_name'	=>	'GDPR (UK) ',
							),
		'lpd'			=>	array(
								'area' 			=> 	'ch',
								'regulation'	=>	'lpd',
								'human_name'	=>	'nLPD / nFADP (Switzerland)',
							),
		'pipeda'		=>	array(
								'area' 			=> 	'ca',
								'regulation'	=>	'pipeda',
								'human_name'	=>	'PIPEDA (Canada)',
							),
		'lgpd'			=>	array(
								'area' 			=>	'br',
								'regulation'	=>	'lgpd',
								'human_name'	=>	'LGPD (Brazil)',
							),
		'ccpa'			=>	array(
								'area' 			=> 	'california',
								'regulation'	=>	'ccpa',
								'human_name'	=>	'CCPA / CPRA (California)',
							),
		'cpa'			=>	array(
								'area' 			=> 	'colorado',
								'regulation'	=>	'cpa',
								'human_name'	=>	'CPA (Colorado)',
							),
		'ctdpa'			=>	array(
								'area' 			=> 	'connecticut',
								'regulation'	=>	'ctdpa',
								'human_name'	=>	'CTDPA (Connecticut)',
							),

		'dpdpa'			=>	array(
								'area' 			=> 	'delaware',
								'regulation'	=>	'dpdpa',
								'human_name'	=>	'DPDPA (Delaware)',
							),
		'mcdpa'			=>	array(
								'area' 			=> 	'minnesota',
								'regulation'	=>	'mcdpa',
								'human_name'	=>	'MCDPA (Minnesota)',
							),
		'mtcdpa'		=>	array(
								'area' 			=> 	'montana',
								'regulation'	=>	'mtcdpa',
								'human_name'	=>	'MTCDPA (Montana)',
							),
		'ndpa'			=>	array(
								'area' 			=> 	'nebraska',
								'regulation'	=>	'ndpa',
								'human_name'	=>	'NDPA (Nebraska)',
							),
		'nevada'		=>	array(
								'area' 			=> 	'nevada',
								'regulation'	=>	'nevada',
								'human_name'	=>	'NRS 603A (Nevada)',
							),
		'nhpa'			=>	array(
								'area' 			=> 	'new_hampshire',
								'regulation'	=>	'nhpa',
								'human_name'	=>	'NHPA (New Hampshire)',
							),
		'njdpa'			=>	array(
								'area' 			=> 	'new_jersey',
								'regulation'	=>	'njdpa',
								'human_name'	=>	'NJDPA (New Jersey)',
							),
		'ocpa'			=>	array(
								'area' 			=> 	'oregon',
								'regulation'	=>	'ocpa',
								'human_name'	=>	'OCPA (Oregon)',
							),
		'tipa'			=>	array(
								'area' 			=> 	'tennessee',
								'regulation'	=>	'tipa',
								'human_name'	=>	'TIPA (Tennessee)',
							),
		'tdpsa'			=>	array(
								'area' 			=> 	'texas',
								'regulation'	=>	'tdpsa',
								'human_name'	=>	'TDPSA (Texas)',
							),
		'ucpa'			=>	array(
								'area' 			=> 	'utah',
								'regulation'	=>	'ucpa',
								'human_name'	=>	'UCPA (Utah)',
							),
		'vcdpa'			=>	array(
								'area' 			=> 	'virginia',
								'regulation'	=>	'vcdpa',
								'human_name'	=>	'VCDPA (Virginia)',
							),
	);

	//stored areas
	private static $availablea_areas = array(
		'eu',
		'gb',
		'us',
		'ch',
		'ca',
		'br',

		'california',
		'colorado',
		'connecticut',
		'delaware',
		'minnesota',
		'montana',
		'nebraska',
		'nevada',
		'new_hampshire',
		'new_jersey',
		'oregon',
		'tennessee',
		'texas',
		'utah',
		'virgina',
	);

	/**
	 *	Constructor
	 */
	public function __construct()
	{
		$the_settings = MyAgilePrivacy::get_settings();

		self::$the_settings = $the_settings;

		if( isset( $the_settings['site_and_policy_settings'] ) )
		{
			self::$site_and_policy_settings = $the_settings['site_and_policy_settings'];
		}

		self::$available_countries = MyAgilePrivacy::get_option( MAP_PLUGIN_COUNTRIES, null );
	}

	//getter class
	public function getSiteAndPolicySettings()
	{
		return self::$site_and_policy_settings;
	}

	//getter class
	public function getAvailableCountries()
	{
		return self::$available_countries;
	}

	//getter class for admin backend
	public function getRegulationForBackendEdit()
	{
		$regulation_config = array();

		$to_add_keys = array();

		foreach( self::$available_regulations as $item )
		{
			$to_add_keys[] = $item['regulation'].'_list';
		}

		if( self::$available_countries )
		{
			foreach( self::$available_countries as $k => $v )
			{
				if( in_array( $k, $to_add_keys ) )
				{
					$regulation_config[ $k ] = $v;
				}
			}
		}

		if( isset( self::$available_countries['usa_sub_list'] ) )
		{
			$regulation_config['usa_sub_list'] = self::$available_countries['usa_sub_list'];
		}


		return $regulation_config;
	}

	//f. for getting regulation list keys
	public function getRegulationList()
	{
		return array_keys( self::$available_regulations );
	}


	//get selected locations
	public function collectSelectedAreas()
	{
		$areas = array();

		if( isset( self::$site_and_policy_settings['base_location'] ) )
		{
			if( isset( self::$site_and_policy_settings['base_location'] ) &&
				self::$site_and_policy_settings['base_location'] == 'us' )
			{
				foreach( self::$available_countries['usa_sub_list'] as $usa_item )
				{
					$areas[] = $usa_item;
				}
			}
			else
			{
				$areas[] = self::$site_and_policy_settings['base_location'];
			}
		}

		if( isset( self::$site_and_policy_settings['customer_location'] ) &&
			self::$site_and_policy_settings['customer_location'] == 'select_countries' )
		{
			foreach( self::$availablea_areas as $available_area )
			{
				if( isset( self::$site_and_policy_settings[ 'customer_area_'.$available_area ] ) && self::$site_and_policy_settings[ 'customer_area_'.$available_area ] )
				{
					$areas[] = $available_area;
				}
			}
		}

		return $areas;
	}

	//get true / false about passed regulation
	public function isRegulationSelected( $regulation_name )
	{
		$available_regulations_keys = array_keys( self::$available_regulations );

		if( !
			( 	$regulation_name &&
				in_array( $regulation_name, $available_regulations_keys )
			)
		)
		{
			return false;
		}

		$collectSelectedAreas = self::collectSelectedAreas();
		$regulation_config = self::getRegulationForBackendEdit();

		$matching_item = null;
		$matching_area = null;
		$matching_regulation = null;

		if( isset( self::$available_regulations[ $regulation_name ] ) )
		{
			$matching_item = self::$available_regulations[ $regulation_name ];
			$matching_area = $matching_item[ 'area' ];
			$matching_regulation = $matching_item[ 'regulation' ];
		}

		if(
			$regulation_config &&
			isset( $regulation_config['usa_sub_list'] ) &&
			$matching_item &&
			$matching_area &&
			$matching_regulation )
		{
			$usa_check = (
						in_array( $matching_area, $regulation_config['usa_sub_list'] ) &&
						isset( self::$site_and_policy_settings[ 'base_location' ] ) &&
						self::$site_and_policy_settings[ 'base_location' ] == 'us'
					);

			$my_country_check = (
					isset( self::$site_and_policy_settings[ 'base_location' ] ) &&
					isset( $regulation_config[$matching_regulation.'_list'] ) &&
					in_array( self::$site_and_policy_settings[ 'base_location' ], $regulation_config[$matching_regulation.'_list'] )
				);

			$select_countries_check = (
					isset( self::$site_and_policy_settings['customer_location'] ) &&
					self::$site_and_policy_settings['customer_location'] == 'select_countries' &&
					isset( self::$site_and_policy_settings[ 'customer_area_'.$matching_area ] ) &&
					self::$site_and_policy_settings[ 'customer_area_'.$matching_area ]
				);

			$regulation_check = isset( self::$site_and_policy_settings[ 'regulation_'.$matching_regulation ] ) &&
				self::$site_and_policy_settings[ 'regulation_'.$matching_regulation ];

			if(
				(
					$usa_check ||
					$my_country_check ||
					$select_countries_check
				) &&
				$regulation_check
			)
			{
				return true;
			}
		}

		return false;
	}

	//get list of selected regulations
	public function getRegulationsSelected( $human = true)
	{
		$selected_regulations = array();

		foreach( self::$available_regulations as $k => $v )
		{
			if( self::isRegulationSelected( $k ) )
			{
				if( $human )
				{
					$value = $v['human_name'];
				}
				else
				{
					$value = $k;
				}

				$selected_regulations[] = $value;
			}
		}

		return $selected_regulations;
	}

	//f. for getting template config
	public function getTemplateConfig()
	{
		$selected_regulations = self::getRegulationsSelected( false );

		$pa = ( isset( self::$the_settings['pa'] ) && self::$the_settings['pa'] == 1 );

		$map_dpo_text = ( $pa &&
							isset( self::$site_and_policy_settings['display_dpo'] ) &&
							self::$site_and_policy_settings['display_dpo'] == 1
						) ? true : false;

		$map_dpo_text_unset = ( !$map_dpo_text ) ? true : false;

		$identity_name = (
							isset( self::$site_and_policy_settings['identity_name'] ) &&
							self::$site_and_policy_settings['identity_name']
						) ? self::$site_and_policy_settings['identity_name'] : null;

		$identity_address = (
							isset( self::$site_and_policy_settings['identity_address'] ) &&
							self::$site_and_policy_settings['identity_address']
						) ? self::$site_and_policy_settings['identity_address'] : null;

		$identity_vat_id = (
							isset( self::$site_and_policy_settings['identity_vat_id'] ) &&
							self::$site_and_policy_settings['identity_vat_id']
						) ? self::$site_and_policy_settings['identity_vat_id'] : null;

		$identity_email = (
							isset( self::$site_and_policy_settings['identity_email'] ) &&
							self::$site_and_policy_settings['identity_email']
						) ? self::$site_and_policy_settings['identity_email'] : null;

		$identity_name = (
							isset( self::$site_and_policy_settings['identity_name'] ) &&
							self::$site_and_policy_settings['identity_name']
						) ? self::$site_and_policy_settings['identity_name'] : null;


		$dpo_email = (
							$map_dpo_text &&
							isset( self::$site_and_policy_settings['dpo_email'] ) &&
							self::$site_and_policy_settings['dpo_email']
						) ? self::$site_and_policy_settings['dpo_email'] : null;

		$dpo_name = (
							$map_dpo_text &&
							isset( self::$site_and_policy_settings['dpo_name'] ) &&
							self::$site_and_policy_settings['dpo_name']
						) ? self::$site_and_policy_settings['dpo_name'] : null;

		$dpo_address = (
							$map_dpo_text &&
							isset( self::$site_and_policy_settings['dpo_address'] ) &&
							self::$site_and_policy_settings['dpo_address']
						) ? self::$site_and_policy_settings['dpo_address'] : null;


		$map_gdpr_text = ( in_array( 'gdpr', $selected_regulations ) ) ? true : false;


		//fallback
		if(
			!$map_gdpr_text &&
			isset( self::$site_and_policy_settings['completion_percentage'] ) &&
			intval( self::$site_and_policy_settings['completion_percentage'] ) < 80
		)
		{
			$map_gdpr_text = true;
		}

		$map_gdpr_gb_text = ( $pa && in_array( 'gdpr_gb', $selected_regulations ) ) ? true : false;
		$map_lpd_text = ( $pa && in_array( 'lpd', $selected_regulations ) ) ? true : false;
		$map_pipeda_text = ( $pa && in_array( 'pipeda', $selected_regulations ) ) ? true : false;
		$map_lgpd_text = ( $pa && in_array( 'lgpd', $selected_regulations ) ) ? true : false;
		$map_ccpa_text = ( $pa && in_array( 'ccpa', $selected_regulations ) ) ? true : false;
		$map_cpa_text = ( $pa && in_array( 'cpa', $selected_regulations ) ) ? true : false;
		$map_ctdpa_text = ( $pa && in_array( 'ctdpa', $selected_regulations ) ) ? true : false;
		$map_dpdpa_text = ( $pa && in_array( 'dpdpa', $selected_regulations ) ) ? true : false;
		$map_mcdpa_text = ( $pa && in_array( 'mcdpa', $selected_regulations ) ) ? true : false;
		$map_mtcdpa_text = ( $pa && in_array( 'mtcdpa', $selected_regulations ) ) ? true : false;
		$map_ndpa_text = ( $pa && in_array( 'ndpa', $selected_regulations ) ) ? true : false;
		$map_nevada_text = ( $pa && in_array( 'nevada', $selected_regulations ) ) ? true : false;
		$map_nhpa_text = ( $pa && in_array( 'nhpa', $selected_regulations ) ) ? true : false;
		$map_njdpa_text = ( $pa && in_array( 'njdpa', $selected_regulations ) ) ? true : false;
		$map_ocpa_text = ( $pa && in_array( 'ocpa', $selected_regulations ) ) ? true : false;
		$map_tipa_text = ( $pa && in_array( 'tipa', $selected_regulations ) ) ? true : false;
		$map_tdpsa_text = ( $pa && in_array( 'tdpsa', $selected_regulations ) ) ? true : false;

		$map_ucpa_text = ( $pa && in_array( 'ucpa', $selected_regulations ) ) ? true : false;
		$map_vcdpa_text = ( $pa && in_array( 'vcdpa', $selected_regulations ) ) ? true : false;

		$map_any_gdpr_like_text = false;

		if(
			$map_gdpr_text ||
			$map_gdpr_gb_text ||
			$map_lpd_text ||
			$map_lgpd_text
		)
		{
			$map_any_gdpr_like_text = true;
		}

		$map_any_usa_text = false;
		if(
			$map_ccpa_text ||
			$map_cpa_text ||
			$map_ctdpa_text ||
			$map_dpdpa_text ||
			$map_mcdpa_text ||
			$map_mtcdpa_text ||
			$map_ndpa_text ||
			$map_nevada_text ||
			$map_nhpa_text ||
			$map_njdpa_text ||
			$map_ocpa_text ||
			$map_tipa_text ||
			$map_tdpsa_text ||
			$map_ucpa_text ||
			$map_vcdpa_text
		)
		{
			$map_any_usa_text = true;
		}

		$map_any_usa_like_text = false;

		if(
			$map_any_usa_text ||
			$map_pipeda_text
		)
		{
			$map_any_usa_like_text = true;
		}

        $map_using_contact_forms = (
        								isset( self::$site_and_policy_settings['site_features_contact_forms'] ) &&
        								self::$site_and_policy_settings['site_features_contact_forms']
        							);
        $map_accepting_payments = (
        							$pa &&
        							isset( self::$site_and_policy_settings['site_features_payments'] ) &&
        							self::$site_and_policy_settings['site_features_payments']
        						);
        $map_account_reg = (
        						$pa &&
        						isset( self::$site_and_policy_settings['site_features_account_reg'] ) &&
        						self::$site_and_policy_settings['site_features_account_reg']
        					);
        $map_using_newsletter = (
        							$pa &&
        							isset( self::$site_and_policy_settings['site_features_newsletter'] ) &&
        							self::$site_and_policy_settings['site_features_newsletter']
        						);
        $map_show_marketing_data_retention = (
        										$map_using_newsletter &&
        										isset( self::$site_and_policy_settings['site_features_show_marketing_data_retention'] ) &&
        										self::$site_and_policy_settings['site_features_show_marketing_data_retention']
        									);
        $map_accepting_reviews = (
        							$pa &&
        							isset( self::$site_and_policy_settings['site_features_reviews_collect'] ) &&
        							self::$site_and_policy_settings['site_features_reviews_collect']
        						);
        $map_using_minors_data = (
        							$pa &&
        							isset( self::$site_and_policy_settings['site_features_minors_data'] ) &&
        							self::$site_and_policy_settings['site_features_minors_data']
        						);
        $map_using_minors_data_off = ( !$map_using_minors_data ) ? true : false;
        $map_sensitive_data = (
        						$pa &&
        						isset( self::$site_and_policy_settings['site_features_sensitive_data'] ) &&
        						self::$site_and_policy_settings['site_features_sensitive_data']
        					);

		$map_https = (
						isset( self::$site_and_policy_settings['protection_system_https'] ) &&
						self::$site_and_policy_settings['protection_system_https']
					);
		$map_log_control = (
								$pa &&
								isset( self::$site_and_policy_settings['protection_system_log_control'] ) &&
								self::$site_and_policy_settings['protection_system_log_control']
							);
		$map_backup = (
						$pa &&
						isset( self::$site_and_policy_settings['protection_system_backup'] ) &&
						self::$site_and_policy_settings['protection_system_backup']
					);
		$map_audit = (
						$pa &&
						isset( self::$site_and_policy_settings['protection_system_audit'] ) &&
						self::$site_and_policy_settings['protection_system_audit']
					);
		$map_system_access_limited = (
										$pa &&
										isset( self::$site_and_policy_settings['protection_system_access_limited'] ) &&
										self::$site_and_policy_settings['protection_system_access_limited']
									);

		$map_any_security_measure = false;

		if(
			$map_https ||
			$map_log_control ||
			$map_backup ||
			$map_audit ||
			$map_system_access_limited
		)
		{
			$map_any_security_measure = true;
		}

		$map_transferring_data_to_other_unapproved_countries = false;
		$map_transferring_data_to_other_unapproved_countries_list = "";
		$map_transferring_data_to_other_unapproved_countries_array = array();

		if( $pa &&
			isset( self::$site_and_policy_settings['outside_adequate_suppliers'] ) &&
			self::$site_and_policy_settings['outside_adequate_suppliers']
		)
		{
			foreach( self::$available_countries['not_adequate'] as $k => $v )
			{
				if( isset( self::$site_and_policy_settings['outside_country_'.$k] ) &&
					self::$site_and_policy_settings['outside_country_'.$k]
				)
				{
					$map_transferring_data_to_other_unapproved_countries_array[] = $v;
				}
			}

			if( count( $map_transferring_data_to_other_unapproved_countries_array ) > 0 )
			{
				$map_transferring_data_to_other_unapproved_countries = true;
				$map_transferring_data_to_other_unapproved_countries_list = implode( ',', $map_transferring_data_to_other_unapproved_countries_array );
			}
		}


		$last_policy_revision_date_human = '-';
		$last_policy_revision_date = false;

		$last_update_timestamp_check = ( isset( self::$site_and_policy_settings['last_update_timestamp'] ) &&
			self::$site_and_policy_settings['last_update_timestamp'] );
		$cookie_reset_timestamp_check = ( isset( self::$the_settings['cookie_reset_timestamp'] ) && self::$the_settings['cookie_reset_timestamp'] );
		$last_scan_date_internal_check = ( isset( self::$the_settings['last_scan_date_internal'] ) && self::$the_settings['last_scan_date_internal'] );


		if( $last_update_timestamp_check ||
			$cookie_reset_timestamp_check ||
			$last_scan_date_internal_check
		)
		{
			//get values
			$last_update_timestamp = isset( self::$site_and_policy_settings['last_update_timestamp'] ) ? (int) self::$site_and_policy_settings['last_update_timestamp'] : 0;

			$cookie_reset_timestamp = isset( self::$the_settings['cookie_reset_timestamp'] ) ? (int) self::$the_settings['cookie_reset_timestamp'] : 0;

			$last_scan_date_internal = isset( self::$the_settings['last_scan_date_internal'] ) ? (int) self::$the_settings['last_scan_date_internal'] : 0;

			//Keep only values > 0 (ignore null/zero/empty)
			$date_values = array_filter(
			    array( $last_update_timestamp, $cookie_reset_timestamp, $last_scan_date_internal ),
			    function ( $v ) {
			    	return $v > 0;
			    }
			);

			// Compute the maximum; null if all values were 0/null
			$max_date_value = !empty( $date_values ) ? max( $date_values ) : null;

			if( $max_date_value )
			{
				$wp_date_format = MyAgilePrivacy::get_option( 'date_format', null );

				if( $wp_date_format )
				{
					if( function_exists( 'wp_date' ) )
					{
						$last_policy_revision_date_human = wp_date( $wp_date_format, $max_date_value );
					}
					else
					{
						$last_policy_revision_date_human = date_i18n( $wp_date_format, $max_date_value );
					}

					$last_policy_revision_date = true;
				}
			}
		}

		$config = array(

			'shortcode_identity_name'				=>	$identity_name,
			'shortcode_identity_address'			=>	$identity_address,
			'shortcode_identity_vat_id'				=>	$identity_vat_id,
			'shortcode_identity_email'				=>	$identity_email,

			'map_identity_name'						=>	$identity_name,
			'map_identity_address'					=>	$identity_address,
			'map_identity_vat_id'					=>	$identity_vat_id,
			'map_identity_email'					=>	$identity_email,


			'map_dpo_text'							=>	$map_dpo_text,
			'map_dpo_text_unset'					=>	$map_dpo_text_unset,

			'shortcode_dpo_email'					=>	$dpo_email,
			'shortcode_dpo_name'					=>	$dpo_name,
			'shortcode_dpo_address'					=>	$dpo_address,
			'map_dpo_email'							=>	(
															$dpo_email
														) ? true : false,
			'map_dpo_name'							=>	(
															$dpo_name
														) ? true : false,
			'map_dpo_address'						=>	(
															$dpo_address
														) ? true : false,


			'map_dpo_other_text'					=>	( $map_dpo_text &&
															(
																$dpo_email ||
																$dpo_name ||
																$dpo_address
															)
														) ? true : false,

			'map_gdpr_text'							=>	$map_gdpr_text,
			'map_gdpr_gb_text'						=>	$map_gdpr_gb_text,
			'map_lpd_text'							=>	$map_lpd_text,
			'map_lgpd_text'							=>	$map_lgpd_text,
			'map_pipeda_text'						=>	$map_pipeda_text,

			'map_ccpa_text'							=>	$map_ccpa_text,
			'map_cpa_text'							=>	$map_cpa_text,
			'map_ctdpa_text'						=>	$map_ctdpa_text,
			'map_dpdpa_text'						=>	$map_dpdpa_text,
			'map_mcdpa_text' 						=>	$map_mcdpa_text,
			'map_mtcdpa_text'						=>	$map_mtcdpa_text,
			'map_ndpa_text' 						=>	$map_ndpa_text,
			'map_nevada_text'						=>	$map_nevada_text,
			'map_nhpa_text' 						=>	$map_nhpa_text,
			'map_njdpa_text' 						=>	$map_njdpa_text,
			'map_ocpa_text' 						=>	$map_ocpa_text,
			'map_tipa_text' 						=>	$map_tipa_text,
			'map_tdpsa_text' 						=>	$map_tdpsa_text,
			'map_vcdpa_text'						=>	$map_vcdpa_text,
			'map_ucpa_text'							=>	$map_ucpa_text,

			'map_any_gdpr_like_text'				=>	$map_any_gdpr_like_text,
			'map_any_usa_like_text'					=>	$map_any_usa_like_text,
			'map_any_usa_text'						=>	$map_any_usa_text,

            'map_using_contact_forms'				=>	$map_using_contact_forms,
            'map_accepting_payments' 				=>	$map_accepting_payments,
            'map_account_reg' 						=> 	$map_account_reg,
            'map_using_newsletter'					=>	$map_using_newsletter,
            'map_show_marketing_data_retention'		=>	$map_show_marketing_data_retention,
            'map_accepting_reviews'					=>	$map_accepting_reviews,
            'map_using_minors_data'					=>	$map_using_minors_data,
            'map_using_minors_data_off'				=>	$map_using_minors_data_off,
            'map_sensitive_data' 					=>	$map_sensitive_data,

			'map_https'								=>	$map_https,
			'map_log_control'						=>	$map_log_control,
			'map_backup'							=>	$map_backup,
			'map_audit'								=>	$map_audit,
			'map_system_access_limited'				=>	$map_system_access_limited,

            'map_any_security_measure'				=>	$map_any_security_measure,

            'map_transferring_data_to'				=> 	$map_transferring_data_to_other_unapproved_countries,
            'shortcode_transferring_data_to'		=>	$map_transferring_data_to_other_unapproved_countries_list,


            'map_last_policy_revision_date'			=>	$last_policy_revision_date,
            'shortcode_last_policy_revision_date'	=>	$last_policy_revision_date_human,
		);

		return $config;
	}

	//f. for getting frontend config
	public function getFrontendConfig()
	{
		$return_setting = 'opt-in';

		if( isset( self::$site_and_policy_settings['base_location'] ) &&
			isset( self::$available_countries ) )
		{
			if( in_array( self::$site_and_policy_settings['base_location'], self::$available_countries['gdpr_like_list'] ) )
			{
				$return_setting = 'opt-in';
			}
			elseif( in_array( self::$site_and_policy_settings['base_location'], self::$available_countries['usa_like'] ) )
			{
				$return_setting = 'opt-out';
			}
			else
			{
				$return_setting = 'opt-in';
			}
		}

		return $return_setting;
	}
}