<?php
/**
 * Meta Box
 *
 * @package     EDD\GetResponse\MetaBox
 * @since       1.1.1
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


/**
 * Register meta box
 *
 * @since      1.1.1
 * @return void
 */
function edd_getresponse_add_meta_box() {
    add_meta_box(
        'getresponse',
        __( 'GetResponse', 'edd-getresponse' ),
        'edd_getresponse_render_meta_box',
        'download',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'edd_getresponse_add_meta_box' );


/**
 * Render meta box
 *
 * @since       1.1.1
 * @global      object $post The post we are editing
 * @return      void
 */
function edd_getresponse_render_meta_box() {
    global $post;

    $post_id    = $post->ID;
    $campaign   = get_post_meta( $post_id, '_edd_getresponse_campaign', true ) ? get_post_meta( $post_id, '_edd_getresponse_campaign', true ) : '';


    // Get the available campaigns
    if( !class_exists( 'jsonRPCClient' ) ) {
        require_once EDD_GETRESPONSE_DIR . 'includes/libraries/jsonRPCClient.php';
    }

    try{
        $api = new jsonRPCClient( EDD_GETRESPONSE_API_URL );

        $return = $api->get_campaigns( edd_get_option( 'edd_getresponse_api' ) );
    } catch( Exception $e ) {
        $return[] = array( 'name' => __( 'Invalid API key or API timeout!', 'edd-getresponse' ) );
    }

    $output = '';

    foreach( $return as $campaign_id => $campaign_info ) {
        $output .= '<option value="' . $campaign_id . '"' . ( $campaign_id == $campaign ? ' selected="selected"' : '' ) . '>' . $campaign_info['name'] . '</option>';
    }

    echo '<p><label for="_edd_getresponse_campaign">' .
        __( 'Product-specific campaign', 'edd-getresponse' ) . '</label><br />
        <select name="_edd_getresponse_campaign" id="_edd_getresponse_campaign">
        <option value="">' . __( 'None', 'edd-getresponse' ) . '</option>' .
        $output . '
        </select>
        </p>';

    // Allow extension of the meta box
    do_action( 'edd_getresponse_meta_box_fields', $post->ID );

    wp_nonce_field( basename( __FILE__ ), 'edd_getresponse_meta_box_nonce' );
}


/**
 * Save post meta when the save_post action is called
 *
 * @since       1.1.1
 * @param       int $post_id The ID of the post we are saving
 * @global      object $post The post we are saving
 * @return      void
 */
function edd_getresponse_meta_box_save( $post_id ) {
    global $post;

    // Don't process if nonce can't be validated
    if( !isset( $_POST['edd_getresponse_meta_box_nonce'] ) || !wp_verify_nonce( $_POST['edd_getresponse_meta_box_nonce'], basename( __FILE__ ) ) ) return $post_id;

    // Don't process if this is an autosave
    if( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) return $post_id;

    // Don't process if this is a revision
    if( isset( $post->post_type ) && $post->post_type == 'revision' ) return $post_id;

    // Don't process if the current user shouldn't be editing this product
    if( !current_user_can( 'edit_product', $post_id ) ) return $post_id;

    $fields = apply_filters( 'edd_getresponse_meta_box_fields_save', array(
            '_edd_getresponse_campaign'
        )
    );

    foreach( $fields as $field ) {
        if( isset( $_POST[ $field ] ) ) {
            if( is_string( $_POST[ $field ] ) ) {
                $new = esc_attr( $_POST[ $field ] );
            } else {
                $new = $_POST[ $field ];
            }

            $new = apply_filters( 'edd_getresponse_meta_box_save_' . $field, $new );

            update_post_meta( $post_id, $field, $new );
        } else {
            delete_post_meta( $post_id, $field );
        }
    }
}
add_action( 'save_post', 'edd_getresponse_meta_box_save' );
