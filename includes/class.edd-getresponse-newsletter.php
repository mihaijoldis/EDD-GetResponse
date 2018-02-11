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
	public $api_error = true;


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
		add_filter( 'edd_settings_extensions-getresponse_sanitize', array( $this, 'save_settings' ) );
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
	 * Flush the list transient on save
	 *
	 * @access      public
	 * @since       2.0.0
	 * @param       array $input The saved settings
	 * @return      array $input The saved settings
	 */
	public function save_settings( $input ) {
		if( isset( $input['edd_getresponse_api'] ) !== edd_get_option( 'edd_getresponse_api', false ) ) {
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

			// Maybe add debugging
			if( edd_getresponse()->debugging ) {
				s214_debug_log_error( 'GetResponse Debug - List Check', 'No list ID predefined, attempting to load site default.', 'EDD GetResponse' );
			}

			// Retrieve the global list ID
			if( ! $list_id ) {
				$list_id = edd_get_option( 'edd_getresponse_list', false );
				$list_id = $list_id ? $list_id : false;

				if( ! $list_id ) {
					// Maybe add debugging
					if( edd_getresponse()->debugging ) {
						s214_debug_log_error( 'GetResponse Debug - List Check', 'No site list ID defined, exiting.', 'EDD GetResponse' );
					}

					return false;
				}
			}

			// Find out if user is already subscribed
			$query  = '[email]=' . $user_info['email'] . '&query[campaignId]=' . $list_id;
			$result = $this->call_api( 'get', 'contacts', $query );

			// Maybe add debugging
			if( edd_getresponse()->debugging ) {
				s214_debug_log_error( 'GetResponse Debug - Presubscribe Check', print_r( $result, true ), 'EDD GetResponse' );
			}

			$name = $user_info['first_name'] . ' ' . $user_info['last_name'];
			$name = ( $name == ' ' ) ? $user_info['email'] : $name;

			if( empty( $result ) ) {
				$contact = array(
					'name'       => $name,
					'email'      => $user_info['email'],
					'dayOfCycle' => 0,
					//'optin'      => edd_get_option( 'edd_getresponse_double_optin', false ) ? 'double' : 'single',
					'campaign'   => array(
						'campaignId' => $list_id
					),
					'ipAddress'  => edd_get_ip()
				);

				$result = $this->call_api( 'post', 'contacts', $contact );

				// Maybe add debugging
				if( edd_getresponse()->debugging ) {
					s214_debug_log_error( 'GetResponse Debug - API Response', print_r( $result, true ), 'EDD GetResponse' );
				}

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
						$args['body']                    = json_encode( $query );
						$args['headers']['content-type'] = 'application/json';
					}
				}

				$response = wp_remote_post( $url, $args );
			}

			// Maybe add debugging
			if( edd_getresponse()->debugging ) {
				s214_debug_log_error( 'GetResponse Debug - Initial API Response', print_r( $response, true ), 'EDD GetResponse' );
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
		if( $this->api_error && edd_get_option( 'edd_getresponse_api', false ) ) {
			$error  = '<div class="error settings-error notice">';
			$error .= '<p>' . sprintf( __( '<strong>GetResponse API returned the following error:</strong> %s', 'edd-getresponse' ), $this->api_error ) . '</p>';
			$error .= '</div>';

			echo $error;
		}
	}
}
