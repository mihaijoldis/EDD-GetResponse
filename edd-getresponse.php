<?php
/**
 * Plugin Name:     Easy Digital Downloads - GetResponse
 * Plugin URI:      https://easydigitaldownloads.com/extension/getresponse/
 * Description:     Include a GetResponse signup option with your Easy Digital Downloads checkout
 * Version:         1.0.7
 * Author:          Daniel J Griffiths
 * Author URI:      http://ghost1227.com
 *
 * @package         EDD\GetResponse
 * @author          Daniel J Griffiths <dgriffiths@ghost1227.com>
 * @copyright       Copyright (c) 2013, Daniel J Griffiths
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


if( !class_exists( 'EDD_GetResponse' ) ) {


    /**
     * Main EDD_GetResponse class
     *
     * @since       1.0.3
     */
	class EDD_GetResponse {

        /**
         * @var         \EDD_GetResponse $instance The one true EDD_GetResponse
         * @since       1.0.3
         */
		private static $instance;


		/**
		 * Get active instance
		 *
		 * @access		public
		 * @since		1.0.3
		 * @return		self::$instance The one true EDD_GetResponse
		 */
		public static function instance() {
			if( !self::$instance ) {
                self::$instance = new EDD_GetResponse();
                self::$instance->setup_constants();
                self::$instance->includes();
                self::$instance->load_textdomain();
                self::$instance->hooks();
            }

			return self::$instance;
		}


		/**
		 * Setup plugin constants
		 *
		 * @access		private
		 * @since		1.0.7
		 * @return		void
		 */
        public function setup_constants() {
            // Plugin version
            define( 'EDD_GETRESPONSE_VERSION', '1.0.7' );

            // Plugin path
            define( 'EDD_GETRESPONSE_DIR', plugin_dir_path( __FILE__ ) );

            // Plugin URL
            define( 'EDD_GETRESPONSE_URL', plugin_dir_url( __FILE__ ) );

            // GetResponse API URL
			define( 'EDD_GETRESPONSE_API_URL', 'http://api2.getresponse.com' );
		}


        /**
         * Include necessary files
         *
         * @access      private
         * @since       1.0.7
         * @return      void
         */
        private function includes() {
            // Load our custom updater
            if( !class_exists( 'EDD_License' ) ) {
                include_once EDD_GETRESPONSE_DIR . '/includes/libraries/EDD_SL/EDD_License_Handler.php';
            }
        }


		/**
		 * Run action and filter hooks
		 *
		 * @access		private
		 * @since		1.0.3
		 * @return		void
		 */
        private function hooks() {
            // Edit plugin metadata
            add_filter( 'plugin_row_meta', array( $this, 'plugin_metalinks' ), null, 2 );

            // Register settings
            add_filter( 'edd_settings_extensions', array( $this, 'settings' ), 1 );

			// Handle licensing
            $license = new EDD_License( __FILE__, 'GetResponse', EDD_GETRESPONSE_VERSION, 'Daniel J Griffiths' );

			// Add GetResponse checkbox to checkout page
			add_action( 'edd_purchase_form_after_cc_form', array( $this, 'add_fields' ), 999 );

			// Check if a user should be subscribed
			add_action( 'edd_checkout_before_gateway', array( $this, 'signup_check' ), 10, 2 );
		}


		/**
		 * Internationalization
		 *
		 * @access		public
		 * @since		1.0.3
		 * @return		void
		 */
		public static function load_textdomain() {
			// Set filter for languages directory
			$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
			$lang_dir = apply_filters( 'edd_getresponse_languages_directory', $lang_dir );

            // Traditional WordPress plugin locale filter
            $locale     = apply_filters( 'plugin_locale', get_locale(), 'edd-getresponse' );
            $mofile     = sprintf( '%1$s-%2$s.mo', 'edd-getresponse', $locale );

            // Setup paths to current locale file
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/edd-getresponse/' . $mofile;

            if( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/edd-getresponse/ folder
                load_textdomain( 'edd-getresponse', $mofile_global );
            } elseif( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/edd-getresponse/languages/ folder
                load_textdomain( 'edd-getresponse', $mofile_local );
            } else {
                // Load the default language files
                load_plugin_textdomain( 'edd-getresponse', false, $lang_dir );
            }
		}


        /**
         * Modify plugin metalinks
         *
         * @access      public
         * @since       1.0.7
         * @param       array $links The current links array
         * @param       string $file A specific plugin table entry
         * @return      array $links The modified links array
         */
        public function plugin_metalinks( $links, $file ) {
            if( $file == plugin_basename( __FILE__ ) ) {
                $help_link = array( 
                    '<a href="https://easydigitaldownloads.com/support/forum/add-on-plugins/getresponse/" target="_blank">' . __( 'Support Forum', 'edd-getresponse' ) . '</a>'
                );

                $docs_link = array(
                    '<a href="http://support.ghost1227.com/section/edd-getresponse/" target="_blank">' . __( 'Docs', 'edd-getresponse' ) . '</a>'
                );

                $links = array_merge( $links, $help_link, $docs_link );
            }

            return $links;
        }


		/**
		 * Add settings
		 *
		 * @access		public
		 * @since		1.0.0
         * @param		array $settings The existing plugin settings
         * @global      array $edd_options The EDD settings array
		 * @return		array The modified plugin settings
		 */
        public function settings( $settings ) {
            global $edd_options;

            // Just in case the API key isn't set yet
            $edd_getresponse_settings_2 = array();

			$edd_getresponse_settings = array(
				array(
					'id'	=> 'edd_getresponse_settings',
					'name'	=> '<strong>' . __( 'GetResponse Settings', 'edd-getresponse' ) . '</strong>',
					'desc'	=> __( 'Configure GetResponse Integrations Settings', 'edd-getresponse' ),
					'type'	=> 'header'
				),
				array(
					'id'	=> 'edd_getresponse_api',
					'name'	=> __( 'GetResponse API Key', 'edd-getresponse' ),
					'desc'	=> __( 'Enter your GetResponse API key', 'edd-getresponse' ),
					'type'	=> 'text',
					'size'	=> 'regular'
				),
				array(
					'id'	=> 'edd_getresponse_label',
					'name'	=> __( 'Checkbox Label', 'edd-getresponse' ),
					'desc'	=> __( 'Define a custom label for the GetResponse subscription checkbox', 'edd-getresponse' ),
					'type'	=> 'text',
					'size'	=> 'regular',
					'std'	=> __( 'Sign up for our mailing list', 'edd-getresponse' ) 
                ),
            );

            if( isset( $edd_options['edd_getresponse_api'] ) && strlen( trim( $edd_options['edd_getresponse_api'] ) ) > 0 ) {
                $edd_getresponse_settings_2 = array(
    				array(
	    				'id'	=> 'edd_getresponse_list',
		    			'name'	=> __( 'Choose a Campaign', 'edd-getresponse' ),
			    		'desc'	=> __( 'Select the campaign you wish to subscribe buyers to', 'edd-getresponse' ),
				    	'type'	=> 'select',
					    'options'	=> $this->get_campaigns()
				    ),
                );
            }
			

			return array_merge( $settings, array_merge( $edd_getresponse_settings, $edd_getresponse_settings_2 ) );
		}


		/**
		 * Get GetResponse subscription lists
         *
         * @access      public
         * @since	    1.0.0
         * @global      array $edd_options The EDD settings array
		 * @return	    array The list of available campaigns
		 */
		public function get_campaigns() {
			global $edd_options;

			// Make sure we have an API key in the database
			if( isset( $edd_options['edd_getresponse_api'] ) && strlen( trim( $edd_options['edd_getresponse_api'] ) ) > 0 ) {

				// Get campaign list from GetResponse
				$campaigns = array();

				if( !class_exists( 'jsonRPCClient' ) ) {
                    require_once EDD_GETRESPONSE_DIR . 'includes/libraries/jsonRPCClient.php';
                }

				try{
				    $api = new jsonRPCClient( EDD_GETRESPONSE_API_URL );

					$return = $api->get_campaigns( $edd_options['edd_getresponse_api'] );
                } catch( Exception $e ) {
					$return[] =  array( 'name' => __( 'Invalid API key or API timeout!', 'edd-getresponse' ) );
				}

				foreach( $return as $campaign_id => $campaign_info ) {
					$campaigns[$campaign_id] = $campaign_info['name'];
				}

				return $campaigns;
			}

			return array();
		}


		/**
		 * Add email to GetResponse list
         *
         * @access      public
		 * @since		1.0.0
		 * @param		string $email The email address to register
         * @param		string $name The name of the user
         * @global      array $edd_options The EDD settings array
		 * @return		boolean True if the email can be subscribed, false otherwise
		 */
		public function subscribe_email( $email, $name ) {
			global $edd_options;

			if( isset( $edd_options['edd_getresponse_api'] ) && strlen( trim( $edd_options['edd_getresponse_api'] ) ) > 0 ) {

				if( ! class_exists( 'jsonRPCClient' ) ) {
                    require_once EDD_GETRESPONSE_DIR . 'includes/libraries/jsonRPCClient.php';
                }

                try{
				    $api = new jsonRPCClient( EDD_GETRESPONSE_API_URL );

    				$params = array( 'campaign' => $edd_options['edd_getresponse_list'], 'name' => $name, 'email' => $email, 'ip' => $_SERVER['REMOTE_ADDR'], 'cycle_day' => 0 );

                    $return = $api->add_contact( $edd_options['edd_getresponse_api'], $params );
                } catch( Exception $e ) {
                    $return = false;
                }

                if( $return === true ) return true;
			}

			return false;
		}


		/**
		 * Add GetResponse checkbox to checkout page
         *
         * @access      public
         * @since		1.0.0
         * @global      array $edd_options The EDD settings array
		 * @return		void
		 */
		public function add_fields() {
			global $edd_options;

			ob_start();

			if( isset( $edd_options['edd_getresponse_api'] ) && strlen( trim( $edd_options['edd_getresponse_api'] ) ) > 0 ) {
				echo '<fieldset id="edd_getresponse">';
				echo '<label for="edd_getresponse_signup">';
				echo '<input name="edd_getresponse_signup" id="edd_getresponse_signup" type="checkbox" checked="checked" />';
				echo isset( $edd_options['edd_getresponse_label'] ) ? $edd_options['edd_getresponse_label'] : __( 'Sign up for our mailing list', 'edd-getresponse' );
			   	echo '</label>';
				echo '</fieldset>';
			}

			echo ob_get_clean();
		}


		/**
		 * Checks whether a user should be added to list
         *
         * @access      public
		 * @since		1.0.0
		 * @param		array $posted
		 * @param		array $user_info The info for this user
		 * @return		void
		 */
		function signup_check( $posted, $user_info ) {
			if( isset( $posted['edd_getresponse_signup'] ) ) {
				$email = $user_info['email'];
				$name = $user_info['first_name'] . ' ' . $user_info['last_name'];
				$this->subscribe_email( $email, $name );
			}
		}
	}
}


/**
 * The main function responsible for returning the one true EDD_GetResponse
 * instance to functions everywhere
 *
 * @since       1.0.3
 * @return      \EDD_GetResponse The one true EDD_GetResponse
 */
function edd_getresponse_load() {
    // We need access to deactivate_plugins()
    include_once ABSPATH . 'wp-admin/includes/plugin.php';

    if( !class_exists( 'Easy_Digital_Downloads' ) ) {
        deactivate_plugins( __FILE__ );
        unset( $_GET['activate'] );

        // Display notice
        add_action( 'admin_notices', 'edd_getresponse_missing_edd_notice' );
    } else {
        return EDD_GetResponse::instance();
    }
}
add_action( 'plugins_loaded', 'edd_getresponse_load' );

/**
 * We need Easy Digital Downloads... if it isn't present, notify the user!
 *
 * @since       1.0.7
 * @return      void
 */
function edd_getresponse_missing_edd_notice() {
    echo '<div class="error"><p>' . __( 'GetResponse requires Easy Digital Downloads! Please install it to continue!', 'edd-getresponse' ) . '</p></div>';
}


// Off we go!
edd_getresponse_load();
