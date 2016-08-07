<?php
/**
 * Register Settings
 *
 * @package     EDD\GetResponse\Admin\Settings\Register
 * @since       2.1.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Add settings section
 *
 * @since       2.1.0
 * @param       array $sections The existing extensions sections
 * @return      array The modified extensions settings
 */
function edd_getresponse_add_settings_section( $sections ) {
	$sections['getresponse'] = __( 'GetResponse', 'edd-free-downloads' );

	return $sections;
}
add_filter( 'edd_settings_sections_extensions', 'edd_getresponse_add_settings_section' );


/**
 * Add settings
 *
 * @since       2.1.0
 * @param       array $settings The existing plugin settings
 * @return      array The modified plugin settings
 */
function edd_getresponse_add_settings( $settings ) {
	if( EDD_VERSION >= '2.5' ) {
		$new_settings = array(
			'getresponse' => apply_filters( 'edd_getresponse_settings', array(
				array(
					'id'   => 'edd_getresponse_api_config',
					'name' => '<strong>' . __( 'API Configuration', 'edd-getresponse' ) . '</strong>',
					'desc' => '',
					'type' => 'header'
				),
				array(
					'id'   => 'edd_getresponse_api',
					'name' => __( 'GetResponse API Key', 'edd-getresponse' ),
					'desc' => __( 'Enter your GetResponse API key', 'edd-getresponse' ),
					'type' => 'text',
					'size' => 'regular'
				),
				array(
					'id'      => 'edd_getresponse_list',
					'name'    => __( 'Choose A Campaign', 'edd-getresponse' ),
					'desc'    => __( 'Select the campaign you wish to subscribe buyers to', 'edd-getresponse' ),
					'type'    => 'select',
					'options' => edd_getresponse()->newsletter->get_lists()
				),
				array(
					'id'   => 'edd_getresponse_signup_config',
					'name' => '<strong>' . __( 'Signup Configuration', 'edd-getresponse' ) . '</strong>',
					'desc' => '',
					'type' => 'header'
				),
				array(
					'id'   => 'edd_getresponse_checkout_signup',
					'name' => __( 'Show Signup Checkbox', 'edd-getresponse' ),
					'desc' => __( 'Allow customers to signup for the list selected above during checkout', 'edd-getresponse' ),
					'type' => 'checkbox'
				),
				array(
					'id'   => 'edd_getresponse_label',
					'name' => __( 'Checkbox Label', 'edd-getresponse' ),
					'desc' => __( 'Define a custom label for the GetResponse subscription checkbox', 'edd-getresponse' ),
					'type' => 'text',
					'size' => 'regular',
					'std'  => __( 'Sign up for our mailing list', 'edd-getresponse' )
				)
			) )
		);

		$settings = array_merge( $settings, $new_settings );
	}

	return $settings;
}
add_filter( 'edd_settings_extensions', 'edd_getresponse_add_settings' );


/**
 * Add settings (pre-2.5)
 *
 * @since       2.1.0
 * @param       array $settings The existing plugin settings
 * @return      array The modified plugin settings
 */
function edd_getresponse_add_settings_pre25( $settings ) {
	if( EDD_VERSION < '2.5' ) {
		$new_settings = apply_filters( 'edd_getresponse_settings', array(
			array(
				'id'   => 'edd_getresponse_settings',
				'name' => '<strong>' . __( 'GetResponse Settings', 'edd-getresponse' ) . '</strong>',
				'desc' => __( 'Configure GetResponse Integrations Settings', 'edd-getresponse' ),
				'type' => 'header'
			),
			array(
				'id'   => 'edd_getresponse_api',
				'name' => __( 'GetResponse API Key', 'edd-getresponse' ),
				'desc' => __( 'Enter your GetResponse API key', 'edd-getresponse' ),
				'type' => 'text',
				'size' => 'regular'
			),
			array(
				'id'      => 'edd_getresponse_list',
				'name'    => __( 'Choose A Campaign', 'edd-getresponse' ),
				'desc'    => __( 'Select the campaign you wish to subscribe buyers to', 'edd-getresponse' ),
				'type'    => 'select',
				'options' => edd_getresponse()->newsletter->get_lists()
			),
			array(
				'id'   => 'edd_getresponse_checkout_signup',
				'name' => __( 'Show Signup Checkbox', 'edd-getresponse' ),
				'desc' => __( 'Allow customers to signup for the list selected above during checkout', 'edd-getresponse' ),
				'type' => 'checkbox'
			),
			array(
				'id'   => 'edd_getresponse_label',
				'name' => __( 'Checkbox Label', 'edd-getresponse' ),
				'desc' => __( 'Define a custom label for the GetResponse subscription checkbox', 'edd-getresponse' ),
				'type' => 'text',
				'size' => 'regular',
				'std'  => __( 'Sign up for our mailing list', 'edd-getresponse' )
			)
		) );

		$settings = array_merge( $settings, $new_settings );
	}

	return $settings;
}
add_filter( 'edd_settings_extensions', 'edd_getresponse_add_settings_pre25' );


/**
 * Add debug option if the S214 Debug plugin is enabled
 *
 * @since       2.1.2
 * @param       array $settings The current settings
 * @return      array $settings The updated settings
 */
function edd_getresponse_add_debug( $settings ) {
	if( class_exists( 'S214_Debug' ) ) {
		$debug_setting[] = array(
			'id'   => 'edd_getresponse_debugging',
			'name' => '<strong>' . __( 'Debugging', 'edd-getresponse' ) . '</strong>',
			'desc' => '',
			'type' => 'header'
		);

		$debug_setting[] = array(
			'id'   => 'edd_getresponse_enable_debug',
			'name' => __( 'Enable Debug', 'edd-getresponse' ),
			'desc' => sprintf( __( 'Log plugin errors. You can view errors %s.', 'edd-getresponse' ), '<a href="' . admin_url( 'tools.php?page=s214-debug-logs' ) . '">' . __( 'here', 'edd-getresponse' ) . '</a>' ),
			'type' => 'checkbox'
		);

		$settings = array_merge( $settings, $debug_setting );
	}

	return $settings;
}
add_filter( 'edd_getresponse_settings', 'edd_getresponse_add_debug' );