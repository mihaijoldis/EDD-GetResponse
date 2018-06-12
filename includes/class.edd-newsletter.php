<?php
/**
 * Base newsletter class
 *
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
*/

class EDD_Newsletter {

	/*************************************************************************************
	 *
	 * The functions in this section must be overwritten by the extension using this class
	 *
	 ************************************************************************************/


	/**
	 * Defines the default label shown on checkout
	 *
	 * Other things can be done here if necessary, such as additional filters or actions
	 */
	public function init() {
		$this->checkout_label = 'Signup for the newsletter';
	}

	/**
	 * Retrieve the newsletter lists
	 *
	 * Must return an array like this:
	 *   array(
	 *     'some_id'  => 'value1',
	 *     'other_id' => 'value2'
	 *   )
	 */
	public function get_lists() {
		return (array)$this->lists;
	}

	/**
     * Retrieve groups for a list
     *
     * @param  string $list_id     List id for which groupings should be returned
     * @return array  $groups_data Data about the groups
     */
	public function get_groupings( $list_id = '' ) {
		return array();
	}

	/**
	 * Determines if the signup checkbox should be shown on checkout
	 *
	 */
	public function show_checkout_signup() {
		return true;
	}

	/**
	 * Subscribe an customer to a list
	 *
	 * $user_info is an array containing the user ID, email, first name, and last name
	 *
	 * $list_id is the list ID the user should be subscribed to. If it is false, sign the user
	 * up for the default list defined in settings
	 *
	 */
	public function subscribe_email( $user_info = array(), $list_id = false ) {
		return true;
	}

	/**
	 * Register the plugin settings
	 *
	 */
	public function settings( $settings ) {
		return $settings;
	}


	/*************************************************************************************
	 *
	 * The properties and functions in this section may be overwritten by the extension using this class
	 * but are not mandatory
	 *
	 ************************************************************************************/

	/**
	 * The ID for this newsletter extension, such as 'mailchimp'
	 */
	public $id;

	/**
	 * The label for the extension, probably just shown as the title of the metabox
	 */
	public $label;

	/**
	 * Newsletter lists retrieved from the API
	 */
	public $lists;

	/**
	 * Text shown on the checkout, if none is set in the settings
	 */
	public $checkout_label;

	/**
	 * Class constructor
	 */
	public function __construct( $_id = 'newsletter', $_label = 'Newsletter' ) {

		global $edd_options;

		$this->id    = $_id;
		$this->label = $_label;

		add_action( 'init', array( $this, 'textdomain' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
		add_filter( 'edd_metabox_fields_save', array( $this, 'save_metabox' ) );
		add_filter( 'edd_settings_extensions', array( $this, 'settings' ) );
		add_action( 'edd_purchase_form_before_submit', array( $this, 'checkout_fields' ), 100 );
		add_action( 'edd_insert_payment', array( $this, 'check_for_email_signup' ), 10, 2 );
		add_action( 'edd_complete_download_purchase', array( $this, 'completed_download_purchase_signup' ), 10, 3 );

		$this->init();

	}

	/**
	 * Load the plugin's textdomain
	 */
	public function textdomain() {
		// Load the translations
		load_plugin_textdomain( 'edd_' . $this->id, false, EDD_GETRESPONSE_URL . '/languages/' );
	}

	/**
	 * Output the signup checkbox on the checkout screen, if enabled
	 */
	public function checkout_fields() {
		global $edd_options;

		if( ! $this->show_checkout_signup() )
			return;

		$checked = edd_get_option( 'edd_getresponse_checkout_signup_default_value', false );

		ob_start(); ?>
		<fieldset id="edd_<?php echo $this->id; ?>">
			<p>
                <label for="edd_<?php echo $this->id; ?>_signup">
				    <input name="edd_<?php echo $this->id; ?>_signup" id="edd_<?php echo $this->id; ?>_signup" type="checkbox" <?php checked( '1', $checked, true ); ?>/>
                    <?php echo $this->checkout_label; ?>
                </label>
            </p>
		</fieldset>
		<?php
		echo ob_get_clean();
	}

	/**
	 * Check if a customer needs to be subscribed at checkout
	 */
	public function check_for_email_signup( $payment_id = 0, $payment_data = array() ) {
		// Check for global newsletter
		if( isset( $_POST['edd_' . $this->id . '_signup'] ) ) {
			add_post_meta( $payment_id, '_edd_' . $this->id . '_signup', '1' );
		}
	}

	/**
	 * Check if a customer needs to be subscribed on completed purchase of specific products
	 */
	public function completed_download_purchase_signup( $download_id = 0, $payment_id = 0, $download_type = 'default' ) {
		// Check for signup during checkout
		if( get_post_meta( $payment_id, '_edd_' . $this->id . '_signup', true ) ) {
			$user_info = edd_get_payment_meta_user_info( $payment_id );
			$lists     = get_post_meta( $download_id, '_edd_' . $this->id, true );			

			if( 'bundle' == $download_type ) {
				// Get the lists of all items included in the bundle
				$downloads = edd_get_bundled_products( $download_id );
				if( $downloads ) {
					foreach( $downloads as $d_id ) {
						$d_lists = get_post_meta( $d_id, '_edd_' . $this->id, true );
						if ( is_array( $d_lists ) ) {
							$lists = array_merge( $d_lists, (array) $lists );
						}
					}
				}
			}

			if( empty( $lists ) ) {
				if( function_exists( 'edd_debug_log' ) ) {
					edd_debug_log( 'GetResponse Debug - List Check. No list ID predefined, attempting to load site default.' );
				}

				// No Download list set so return global list ID
				$list_id = edd_get_option( 'edd_getresponse_list', false );

				if( ! $list_id ) {
					if( function_exists( 'edd_debug_log' ) ) {
						edd_debug_log( 'GetResponse Debug - List Check. No site list ID defined, exiting.' );
					}

					return false;
				}

				$this->subscribe_email( $user_info, $list_id );
				return;
			}

			$lists = array_unique( $lists );

			foreach( $lists as $list ) {
				$this->subscribe_email( $user_info, $list );
			}

			// Cleanup after ourselves
			delete_post_meta( $payment_id, '_edd_' . $this->id . '_signup' );
		}
	}

	/**
	 * Register the metabox on the 'download' post type
	 */
	public function add_metabox() {
		if ( current_user_can( 'edit_product', get_the_ID() ) ) {
			add_meta_box( 'edd_' . $this->id, $this->label, array( $this, 'render_metabox' ), 'download', 'side' );
		}
	}

	/**
	 * Display the metabox, which is a list of newsletter lists
	 */
	public function render_metabox() {

		global $post;

		echo '<p>' . __( 'Select the lists you wish buyers to be subscribed to when purchasing. Overrides global setting.', 'edd-getresponse' ) . '</p>';

		$checked = (array) get_post_meta( $post->ID, '_edd_' . esc_attr( $this->id ), true );
		foreach( $this->get_lists() as $list_id => $list_name ) {
			echo '<label>';
				echo '<input type="checkbox" name="_edd_' . esc_attr( $this->id ) . '[]" value="' . esc_attr( $list_id ) . '"' . checked( true, in_array( $list_id, $checked ), false ) . '>';
				echo '&nbsp;' . $list_name;
			echo '</label><br/>';

			$groupings = $this->get_groupings( $list_id );
			if( ! empty( $groupings ) ) {
				foreach ( $groupings as $group_id => $group_name ){
					echo '<label>';
						echo '&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="_edd_' . esc_attr( $this->id ) . '[]" value="' . esc_attr( $group_id ) . '"' . checked( true, in_array( $group_id, $checked ), false ) . '>';
						echo '&nbsp;' . $group_name;
					echo '</label><br/>';
				}
			}
		}
	}

	/**
	 * Save the metabox
	 */
	public function save_metabox( $fields ) {

		$fields[] = '_edd_' . esc_attr( $this->id );
		return $fields;
	}

}
