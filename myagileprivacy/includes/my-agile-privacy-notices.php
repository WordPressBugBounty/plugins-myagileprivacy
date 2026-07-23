<?php
if( !defined( 'MAP_PLUGIN_NAME' ) )
{
    exit('Not allowed.');
}

add_action( 'admin_init', 'map_sync_gtm_gateway_notice' );

/**
 * Keeps the loading-priority admin notice in sync on admin_init.
 */
function map_sync_gtm_gateway_notice() {
    if ( ! current_user_can( 'manage_options' ) )            { return; }
    if ( ! class_exists( 'MAP_Admin_Notice_Manager' ) )      { return; }

    $manager = MAP_Admin_Notice_Manager::instance();
    $data    = MyAgilePrivacy::get_option( MAP_PLUGIN_GTM_GATEWAY_DETECTED, array() );

    // No state recorded.
    if ( ! is_array( $data ) || empty( $data['last_detected'] ) ) {
        $manager->remove( 'map_gtm_gateway_detected' );
        return;
    }

    // Disabled by settings.
    $the_settings = MyAgilePrivacy::get_settings();
    if ( isset( $the_settings['disable_gtm_gateway_detection'] ) && $the_settings['disable_gtm_gateway_detection'] ) {
        $manager->remove( 'map_gtm_gateway_detected' );
        return;
    }

    $last_detected = intval( $data['last_detected'] );

    // Stale state.
    if ( ( time() - $last_detected ) > ( 7 * DAY_IN_SECONDS ) ) {
        $manager->remove( 'map_gtm_gateway_detected' );
        return;
    }

    // Notice already visible: nothing to do.
    if ( $manager->has( 'map_gtm_gateway_detected' ) ) {
        return;
    }

    // Notice absent: re-add only on newer state.
    $last_notified = isset( $data['last_notice_detect'] ) ? intval( $data['last_notice_detect'] ) : 0;
    if ( $last_detected <= $last_notified ) {
        return;
    }

    $instructions_url = MAP_Helpdesk_Links::get( 'gtg' );

    $message  = '<b>' . esc_html__( 'My Agile Privacy has detected a loading priority issue.', 'MAP_txt' ) . '</b> ';
    $message .= esc_html__( 'Google Tag Manager runs before the consent management system (a configuration typical of Google Tag Gateway or CDN-level injections). In this situation, Google tags start without receiving the default consent state and preventive blocking may not be applied.', 'MAP_txt' );
    $message .= ' <a href="' . esc_url( $instructions_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'See the instructions to fix this', 'MAP_txt' ) . ' &rarr;</a>';

    $manager->add( 'map_gtm_gateway_detected', array(
        'type'        => 'warning',
        'message'     => $message,
        'dismissible' => true,
    ) );

    // Track the state shown by this notice.
    $data['last_notice_detect'] = $last_detected;
    MyAgilePrivacy::update_option( MAP_PLUGIN_GTM_GATEWAY_DETECTED, $data );
}

add_action( 'admin_init', 'map_sync_cmv2_notice' );

/**
 * Keeps the Consent Mode v2 admin notice in sync on admin_init.
 * Condition-driven: shown while Consent Mode v2 is neither enabled nor bypassed,
 * removed as soon as the condition is resolved.
 */
function map_sync_cmv2_notice() {
    if ( ! current_user_can( 'manage_options' ) )       { return; }
    if ( ! class_exists( 'MAP_Admin_Notice_Manager' ) ) { return; }

    $manager  = MAP_Admin_Notice_Manager::instance();
    $settings = MyAgilePrivacy::get_settings();

    $paid     = isset( $settings['pa'] ) && $settings['pa'] == 1;
    $enabled  = isset( $settings['enable_cmode_v2'] ) && $settings['enable_cmode_v2'];
    $bypassed = isset( $settings['bypass_cmode_enable'] ) && $settings['bypass_cmode_enable'];

    if ( ! ( $paid && ! $enabled && ! $bypassed ) ) {
        $manager->remove( 'map_cmv2_not_enabled' );
        return;
    }

    $message  = __( '<strong>Attention: You have not enabled Google Consent Mode v2.</strong>', 'MAP_txt' );
    $message .= '<br>';
    $message .= esc_html__( 'This may cause issues when using tools within the Google ecosystem, such as Google Analytics, Google Tag Manager, Google Ads, or other tools. Enable Consent Mode v2 if you intend to use these tools, in compliance with regulations.', 'MAP_txt' );

    $manager->add( 'map_cmv2_not_enabled', array(
        'type'        => 'warning',
        'message'     => $message,
        'dismissible' => false,
    ) );
}

add_action( 'admin_init', 'map_sync_advanced_required_notice', 20 );

/**
 * Keeps the admin notice in sync on admin_init.
 */
function map_sync_advanced_required_notice() {
    if ( ! current_user_can( 'manage_options' ) )       { return; }
    if ( ! class_exists( 'MAP_Admin_Notice_Manager' ) ) { return; }

    $manager  = MAP_Admin_Notice_Manager::instance();
    $settings = MyAgilePrivacy::get_settings();
    $rconfig  = MyAgilePrivacy::get_rconfig();

    $integration_on = defined( 'MAP_ENABLE_ADVANCED_INTEGRATION' ) && MAP_ENABLE_ADVANCED_INTEGRATION;
    $entitled = ( isset( $settings['pa'] ) && $settings['pa'] == 1 ) &&
                ( isset( $rconfig['advanced_authorized'] ) && true === $rconfig['advanced_authorized'] );
    $advanced_active = function_exists( 'map_is_advanced_active' ) && map_is_advanced_active();

    if ( $integration_on && $entitled && ! $advanced_active && ! $manager->has( 'map_advanced_state' ) ) {
        $manager->add( 'map_advanced_required', array(
            'type'        => 'warning',
            'message'     => esc_html__( 'My Agile Privacy® requires the My Agile Privacy® Advanced Features plugin to be installed and activated.', 'MAP_txt' ),
            'dismissible' => false,
        ) );
    } else {
        $manager->remove( 'map_advanced_required' );
    }
}
