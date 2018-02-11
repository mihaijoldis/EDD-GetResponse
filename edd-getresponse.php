<?php
/**
 * Plugin Name:     Easy Digital Downloads - GetResponse
 * Plugin URI:      https://easydigitaldownloads.com/extension/getresponse/
 * Description:     Include a GetResponse signup option with your Easy Digital Downloads checkout
 * Version:         2.1.2
 * Author:          Daniel J Griffiths
 * Author URI:      http://ghost1227.com
 * Text Domain:     edd-getresponse
 *
 * @package         EDD\GetResponse
 * @author          Daniel J Griffiths <dgriffiths@ghost1227.com>
 * @copyright       Copyright (c) 2013-2014, Daniel J Griffiths
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// Plugin version
define( 'EDD_GETRESPONSE_VERSION', '2.1.2' );


if( ! class_exists( 'EDD_GetResponse' ) ) {


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
		 * @var         EDD_GetResponse_Newsletter $newsletter The newsletter instance
		 * @since       2.0.0
		 */
		public $newsletter;

		/**
		 * Get active instance
		 *
		 * @access      public
		 * @since       1.0.3
		 * @return      object self::$instance The one true EDD_GetResponse
		 */
		public static function instance() {
			if( ! self::$instance ) {
				self::$instance = new EDD_GetResponse();
				self::$instance->setup_constants();
				self::$instance->includes();
				self::$instance->load_textdomain();
				self::$instance->hooks();
				self::$instance->newsletter = new EDD_GetResponse_Newsletter( 'getresponse', 'GetResponse' );

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
			// Plugin path
			define( 'EDD_GETRESPONSE_DIR', plugin_dir_path( __FILE__ ) );

			// Plugin URL
			define( 'EDD_GETRESPONSE_URL', plugin_dir_url( __FILE__ ) );

			// GetResponse API URL
			define( 'EDD_GETRESPONSE_API_URL', 'https://api.getresponse.com/v3' );
		}


		/**
		 * Include necessary files
		 *
		 * @access      private
		 * @since       1.1.1
		 * @return      void
		 */
		private function includes() {
			if( ! class_exists( 'EDD_Newsletter' ) ) {
				require_once EDD_GETRESPONSE_DIR . '/includes/class.edd-newsletter.php';
			}

			require_once EDD_GETRESPONSE_DIR . '/includes/class.edd-getresponse-newsletter.php';

			if( is_admin() ) {
				require_once EDD_GETRESPONSE_DIR . 'includes/admin/settings/register.php';
				require_once EDD_GETRESPONSE_DIR . '/includes/upgrades.php';
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
			// Handle licensing
			if( class_exists( 'EDD_License' ) ) {
				$license = new EDD_License( __FILE__, 'GetResponse', EDD_GETRESPONSE_VERSION, 'Daniel J Griffiths' );
			}
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
			$locale = apply_filters( 'plugin_locale', get_locale(), 'edd-getresponse' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'edd-getresponse', $locale );

			// Setup paths to current locale file
			$mofile_local  = $lang_dir . $mofile;
			$mofile_global = WP_LANG_DIR . '/edd-getresponse/' . $mofile;

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
	}
}


/**
 * The main function responsible for returning the one true EDD_GetResponse
 * instance to functions everywhere
 *
 * @since       1.0.3
 * @return      \EDD_GetResponse The one true EDD_GetResponse
 */
function edd_getresponse() {
	if( ! class_exists( 'Easy_Digital_Downloads' ) ) {
		if( ! class_exists( 'S214_EDD_Activation' ) ) {
			require_once( 'includes/libraries/class.s214-edd-activation.php' );
		}

		$activation = new S214_EDD_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
		$activation = $activation->run();
	} else {
		return EDD_GetResponse::instance();
	}
}
add_action( 'plugins_loaded', 'edd_getresponse' );


/**
 * Install initial settings on activation
 *
 * @since 2.1.3
 * @return void
 */
function edd_getresponse_install() {
	update_option( 'edd_getresponse_version', EDD_GETRESPONSE_VERSION );
}
register_activation_hook( __FILE__, 'edd_getresponse_install' );