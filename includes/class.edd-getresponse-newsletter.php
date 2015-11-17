<?php
/**
 * GetResponse class, extends EDD base newsletter class
 *
 * @package     EDD\GetResponse\Newsletter
 * @since       2.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
    exit;
}


class EDD_GetResponse_Newsletter extends EDD_Newsletter {


    /**
     * Display errors?
     */
    public $api_error = false;


    /**
     * Set up the checkout label
     *
     * @access      public
     * @since       2.0.0
     * @return      void
     */
    public function init() {
        $label = edd_get_option( 'edd_getresponse_label', __( 'Signup for the newsletter', 'edd-getresponse' ) );
        $this->checkout_label = trim( $label );

        add_filter( 'edd_settings_extensions_sanitize', array( $this, 'save_settings' ) );
        add_action( 'admin_notices', array( $this, 'display_api_errors' ) );
    }


    /**
     * Retrieve the lists from GetResponse
     *
     * @access      public
     * @since       2.0.0
     * @return      array $this->lists the array of lists
     */
    public function get_lists() {
        if( edd_get_option( 'edd_getresponse_api', false ) ) {
            $list_data = get_transient( 'edd_getresponse_list_data' );

            if( $list_data === false ) {
                // Get lists
                $list_data = $this->call_api( 'get', 'campaigns' );

                set_transient( 'edd_getresponse_list_data', $list_data, 24*24*24 );
            }

            if( is_array( $list_data ) ) {
                foreach( $list_data as $key => $list ) {
                    $this->lists[$list->campaignId] = $list->name;
                }

                $this->api_error = false;
            } else {
                $this->api_error = $list_data->message;
            }
        }

        return (array) $this->lists;
    }


    /**
     * Register plugin settings
     *
     * @access      public
     * @since       1.0.0
     * @param       array $settings The existing settings
     * @return      array The updated settings
     */
    public function settings( $settings ) {
        $new_settings = array(
            array(
                'id'    => 'edd_getresponse_settings',
                'name'  => '<strong>' . __( 'GetResponse Settings', 'edd-getresponse' ) . '</strong>',
                'desc'  => __( 'Configure GetResponse Integrations Settings', 'edd-getresponse' ),
                'type'  => 'header'
            ),
            array(
                'id'    => 'edd_getresponse_api',
                'name'  => __( 'GetResponse API Key', 'edd-getresponse' ),
                'desc'  => __( 'Enter your GetResponse API key', 'edd-getresponse' ),
                'type'  => 'text',
                'size'  => 'regular'
            ),
            array(
                'id'    => 'edd_getresponse_list',
                'name'  => __( 'Choose A Campaign', 'edd-getresponse' ),
                'desc'  => __( 'Select the campaign you wish to subscribe buyers to', 'edd-getresponse' ),
                'type'  => 'select',
                'options'   => $this->get_lists()
            ),
            array(
                'id'    => 'edd_getresponse_checkout_signup',
                'name'  => __( 'Show Signup Checkbox', 'edd-getresponse' ),
                'desc'  => __( 'Allow customers to signup for the list selected above during checkout', 'edd-getresponse' ),
                'type'  => 'checkbox'
            ),
            array(
                'id'    => 'edd_getresponse_double_optin',
                'name'  => __( 'Double Opt-In', 'edd-getresponse' ),
                'desc'  => __( 'When checked, users will be sent a confirmation email after signing up', 'edd-getresponse' ),
                'type'  => 'checkbox'
            ),
            array(
                'id'    => 'edd_getresponse_label',
                'name'  => __( 'Checkbox Label', 'edd-getresponse' ),
                'desc'  => __( 'Define a custom label for the GetResponse subscription checkbox', 'edd-getresponse' ),
                'type'  => 'text',
                'size'  => 'regular',
                'std'   => __( 'Sign up for our mailing list', 'edd-getresponse' )
            )
        );

        return array_merge( $settings, $new_settings );
    }


    /**
     * Flush the list transient on save
     *
     * @access      public
     * @since       2.0.0
     * @param       array $input The saved settings
     * @return      array $input The saved settings
     */
    public function save_settings( $input ) {
        if( isset( $input['edd_getresponse_api'] ) ) {
            delete_transient( 'edd_getresponse_list_data' );
        }

        return $input;
    }


    /**
     * Determines if the checkout signup option should be displayed
     *
     * @access      public
     * @since       2.0.0
     * @return      bool Whether or not to show the checkbox
     */
    public function show_checkout_signup() {
        return edd_get_option( 'edd_getresponse_checkout_signup', false );
    }


    /**
     * Subscribe an email to a list
     *
     * @access      public
     * @since       2.0.0
     * @param       array $user_info The user info
     * @list_id     string $list_id The list to subscribe users to
     */
    public function subscribe_email( $user_info = array(), $list_id = false ) {
        if( $api_key = edd_get_option( 'edd_getresponse_api', false ) ) {

            // Retrieve the global list ID
            if( ! $list_id ) {
                $list_id = edd_get_option( 'edd_getresponse_list', false );
                $list_id = $list_id ? $list_id : false;

                if( ! $list_id ) {
                    return false;
                }
            }

            // Find out if user is already subscribed
            $query = '[email]=' . $user_info['email'];
            $result = $this->call_api( 'get', 'contacts', $query );

            $name = $user_info['first_name'] . ' ' . $user_info['last_name'];
            $name = ( $name == ' ' ) ? $user_info['email'] : $name;

            if( empty( $result ) ) {
                $contact = array(
                    'name'       => $name,
                    'email'      => $user_info['email'],
                    'dayOfCycle' => 0,
                    'optin'      => edd_get_option( 'edd_getresponse_double_optin', false ) ? 'double' : 'single',
                    'campaign'   => array(
                        'campaignId'    => $list_id
                    ),
                    'ipAddress'  => edd_get_ip()
                );

                $result = $this->call_api( 'post', 'contacts', $contact );

                if( $result ) {
                    return true;
                }
            }
        }

        return false;
    }


    /**
     * Make a call to the GetResponse API
     *
     * @access      public
     * @since       2.0.0
     * @param       string $type The type of query to make
     * @param       string $endpoint The endpoint to call
     * @param       string $query An optional query string
     * @return      object $response The API response
     */
    public function call_api( $type, $endpoint, $query = false ) {
        $response = false;

        if( $api_key = edd_get_option( 'edd_getresponse_api', false ) ) {
            $url = EDD_GETRESPONSE_API_URL . '/' . $endpoint;

            $args = array(
                'timeout' => 0,
                'headers' => array(
                    'X-Auth-Token' => 'api-key ' . $api_key
                )
            );

            if( $type == 'get' ) {
                if( $query ) {
                    $url .= '?query' . $query;
                }

                $response = wp_remote_get( $url, $args );
            } else {
                if( $query ) {
                    if( $endpoint == 'contacts' ) {
                        $args['body'] = json_encode( $query );
                    }
                }

                $response = wp_remote_post( $url, $args );
            }

            if( ! is_wp_error( $response ) ) {
                $response = json_decode( wp_remote_retrieve_body( $response ) );
            } else {
                $response = false;
            }
        }

        return $response;
    }


    /**
     * Display API errors
     *
     * @access      public
     * @since       2.0.0
     * @return      void
     */
    public function display_api_errors() {
        if( $this->api_error ) {
            $error  = '<div class="error settings-error notice">';
            $error .= '<p>' . sprintf( __( '<strong>GetResponse API returned the following error:</strong> %s', 'edd-getresponse' ), $this->api_error ) . '</p>';
            $error .= '</div>';

            echo $error;
        }
    }
}
