<?php
/**
 * Plugin Name:     Easy Digital Downloads - GetResponse
 * Plugin URI:      https://easydigitaldownloads.com/extension/getresponse/
 * Description:     Include a GetResponse signup option with your Easy Digital Downloads checkout
 * Version:         1.1.1
 * Author:          Daniel J Griffiths
 * Author URI:      http://ghost1227.com
 * Text Domain:     edd-getresponse
 *
 * @package         EDD\GetResponse
 * @author          Daniel J Griffiths <dgriffiths@ghost1227.com>
 * @copyright       Copyright (c) 2013-2014, Daniel J Griffiths
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
         * @var         EDD_GetResponse $instance The one true EDD_GetResponse
         * @since       1.0.3
         */
        private static $instance;


        /**
         * Get active instance
         *
         * @access      public
         * @since       1.0.3
         * @return      object self::$instance The one true EDD_GetResponse
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
         * @access      private
         * @since       1.0.7
         * @return      void
         */
        public function setup_constants() {
            // Plugin version
            define( 'EDD_GETRESPONSE_VERSION', '1.1.0' );

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
         * @since       1.1.1
         * @return      void
         */
        private function includes() {
            if( edd_get_option( 'edd_getresponse_api' ) && strlen( trim( edd_get_option( 'edd_getresponse_api' ) ) > 0 ) ) {
                require_once EDD_GETRESPONSE_DIR . '/includes/metabox.php';
            }
        }


        /**
         * Run action and filter hooks
         *
         * @access      private
         * @since       1.0.3
         * @return      void
         */
        private function hooks() {
            // Edit plugin metadata
            add_filter( 'plugin_row_meta', array( $this, 'plugin_metalinks' ), null, 2 );

            // Register settings
            add_filter( 'edd_settings_extensions', array( $this, 'settings' ), 1 );

            // Handle licensing
            if( class_exists( 'EDD_License' ) ) {
                $license = new EDD_License( __FILE__, 'GetResponse', EDD_GETRESPONSE_VERSION, 'Daniel J Griffiths' );
            }

            // Add GetResponse checkbox to checkout page
            add_action( 'edd_purchase_form_after_cc_form', array( $this, 'add_fields' ), 999 );

            // Check if a user should be subscribed
            add_action( 'edd_checkout_before_gateway', array( $this, 'signup_check' ), 10, 3 );
        }


        /**
         * Internationalization
         *
         * @access      public
         * @since       1.0.3
         * @return      void
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
                    '<a href="http://section214.com/docs/category/edd-getresponse/" target="_blank">' . __( 'Docs', 'edd-getresponse' ) . '</a>'
                );

                $links = array_merge( $links, $help_link, $docs_link );
            }

            return $links;
        }


        /**
         * Add settings
         *
         * @access      public
         * @since       1.0.0
         * @param       array $settings The existing plugin settings
         * @return      array The modified plugin settings
         */
        public function settings( $settings ) {
            // Just in case the API key isn't set yet
            $edd_getresponse_settings_2 = array();

            $edd_getresponse_settings = array(
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
                    'id'    => 'edd_getresponse_auto_subscribe',
                    'name'  => '<strong>' . __( 'Enable Autosubscription', 'edd-getresponse' ),
                    'desc'  => __( 'Removes the opt-in checkbox and automatically subscribes purchasers', 'edd-getresponse' ),
                    'type'  => 'checkbox'
                ),
                array(
                    'id'    => 'edd_getresponse_label',
                    'name'  => __( 'Checkbox Label', 'edd-getresponse' ),
                    'desc'  => __( 'Define a custom label for the GetResponse subscription checkbox', 'edd-getresponse' ),
                    'type'  => 'text',
                    'size'  => 'regular',
                    'std'   => __( 'Sign up for our mailing list', 'edd-getresponse' ) 
                ),
            );

            if( edd_get_option( 'edd_getresponse_api' ) && strlen( trim( edd_get_option( 'edd_getresponse_api' ) ) > 0 ) ) {
                $edd_getresponse_settings_2 = array(
                    array(
                        'id'    => 'edd_getresponse_list',
                        'name'  => __( 'Choose a Campaign', 'edd-getresponse' ),
                        'desc'  => __( 'Select the campaign you wish to subscribe buyers to', 'edd-getresponse' ),
                        'type'  => 'select',
                        'options'   => $this->get_campaigns()
                    ),
                );
            }
            
            return array_merge( $settings, array_merge( $edd_getresponse_settings, $edd_getresponse_settings_2 ) );
        }


        /**
         * Get GetResponse subscription lists
         *
         * @access      public
         * @since       1.0.0
         * @return      array The list of available campaigns
         */
        public function get_campaigns() {

            // Make sure we have an API key in the database
            if( edd_get_option( 'edd_getresponse_api' ) && strlen( trim( edd_get_option( 'edd_getresponse_api' ) ) > 0 ) ) {

                // Get campaign list from GetResponse
                $campaigns = array(
                    '' => __( 'None', 'edd-getresponse' )    
                );

                if( !class_exists( 'jsonRPCClient' ) ) {
                    require_once EDD_GETRESPONSE_DIR . 'includes/libraries/jsonRPCClient.php';
                }

                try{
                    $api = new jsonRPCClient( EDD_GETRESPONSE_API_URL );

                    $return = $api->get_campaigns( edd_get_option( 'edd_getresponse_api' ) );
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
         * @since       1.0.0
         * @param       array $user_info
         * @return      boolean True if the email can be subscribed, false otherwise
         */
        public function subscribe_email( $user_info ) {
            if( edd_get_option( 'edd_getresponse_api' ) && strlen( trim( edd_get_option( 'edd_getresponse_api' ) ) > 0 ) ) {

                if( ! class_exists( 'jsonRPCClient' ) ) {
                    require_once EDD_GETRESPONSE_DIR . 'includes/libraries/jsonRPCClient.php';
                }

                $email      = $user_info['email'];
                $name       = $user_info['first_name'] . ' ' . $user_info['last_name'];
                $cart_items = edd_get_cart_contents();

                try{
                    $api = new jsonRPCClient( EDD_GETRESPONSE_API_URL );

                    // Global newsletter
                    if( edd_get_option( 'edd_getresponse_list', false ) ) {
                        $params = array(
                            'campaign' => edd_get_option( 'edd_getresponse_list', '' ),
                            'name' => $name,
                            'email' => $email,
                            'ip' => $_SERVER['REMOTE_ADDR'],
                            'cycle_day' => 0
                        );

                        $return = $api->add_contact( edd_get_option( 'edd_getresponse_api' ), $params );
                    }

                    // Per-product newsletters
                    if( !empty( $cart_items ) ) {
                        foreach( $cart_items as $cart_item ) {

                            $campaign = get_post_meta( $cart_item['id'], '_edd_getresponse_campaign', true ) ? get_post_meta( $cart_item['id'], '_edd_getresponse_campaign', true ) : null;

                            if( !$campaign )
                                continue;

                            $params = array(
                                'campaign' => $campaign,
                                'name' => $name,
                                'email' => $email,
                                'ip' => $_SERVER['REMOTE_ADDR'],
                                'cycle_day' => 0
                            );

                            $return = $api->add_contact( edd_get_option( 'edd_getresponse_api' ), $params );
                        }
                    }

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
         * @since       1.0.0
         * @return      void
         */
        public function add_fields() {
            ob_start();

            if( edd_get_option( 'edd_getresponse_api' ) && strlen( trim( edd_get_option( 'edd_getresponse_api' ) ) > 0 ) ) {
                if( edd_get_option( 'edd_getresponse_auto_subscribe' ) ) {
                    echo '<input name="edd_getresponse_signup" id="edd_getresponse_signup" type="hidden" value="true">';
                } else {
                    echo '<fieldset id="edd_getresponse">';
                    echo '<label for="edd_getresponse_signup">';
                    echo '<input name="edd_getresponse_signup" id="edd_getresponse_signup" type="checkbox" checked="checked" />';
                    echo edd_get_option( 'edd_getresponse_label' ) ? edd_get_option( 'edd_getresponse_label' ) : __( 'Sign up for our mailing list', 'edd-getresponse' );
                    echo '</label>';
                    echo '</fieldset>';
                }
            }

            echo ob_get_clean();
        }


        /**
         * Checks whether a user should be added to list
         *
         * @access      public
         * @since       1.0.0
         * @param       array $posted
         * @param       array $user_info The info for this user
         * @return      void
         */
        function signup_check( $posted, $user_info, $valid_data ) {
            if( isset( $posted['edd_getresponse_signup'] ) ) {
                $this->subscribe_email( $user_info );
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
function EDD_GetResponse_load() {
    if( !class_exists( 'Easy_Digital_Downloads' ) ) {
        if( !class_exists( 'S214_EDD_Activation' ) ) {
            require_once( 'includes/class.s214-edd-activation.php' );
        }

        $activation = new S214_EDD_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
        $activation = $activation->run();
    } else {
        return EDD_GetResponse::instance();
    }
}
add_action( 'plugins_loaded', 'EDD_GetResponse_load' );
