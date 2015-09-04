<?php
/**
 * Upgrades
 *
 * @package     EDD\GetResponse\Upgrades
 * @since       2.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Add the upgrades page
 *
 * @since       2.0.0
 * @return      void
 */
function edd_getresponse_add_upgrades_page() {
    if( ! class_exists( 'Easy_Digital_Downloads' ) ) {
        return;
    }

    add_submenu_page( null, __( 'EDD GetResponse Upgrades', 'edd-getresponse' ), __( 'EDD Upgrades', 'edd-getresponse' ), 'install_plugins', 'edd-getresponse-upgrades', 'edd_getresponse_upgrades_screen' );
}
add_action( 'admin_menu', 'edd_getresponse_add_upgrades_page' );


/**
 * Render the upgrades screen
 *
 * @since       2.0.0
 * @return      void
 */
function edd_getresponse_render_upgrades_screen() {
    $step   = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : 1;
    $counts = wp_count_posts( 'download' );
    $total  = 0;
    foreach( $counts as $count ) {
        $total += $count;
    }
    $total_steps = round( ( $total / 100 ), 0 );
    ?>
    <div class="wrap">
        <h2><?php _e( 'GetResponse - Upgrades', 'edd-getresponse' ); ?></h2>
        <div id="edd-upgrade-status">
            <p><?php _e( 'The upgrade process is running, please be patient. This could take several minutes to complete while settings are migrated to the new system.', 'edd-getresponse' ); ?></p>
            <p><strong><?php printf( __( 'Step %d of approximately %d running', 'edd-getresponse' ), $step, $total_steps ); ?></strong></p>
        </div>
        <script type="text/javascript">
            document.location.href = "index.php?edd_action=<?php echo $_GET['edd_upgrade']; ?>&step=<?php echo absint( $_GET['step'] ); ?>";
        </script>
    </div>
    <?php
}


/**
 * Trigger all upgrade functions
 *
 * @since       2.0.0
 * @return      void
 */
function edd_getresponse_show_upgrade_notice() {
    if( ! class_exists( 'Easy_Digital_Downloads' ) ) {
        return;
    }

    $edd_getresponse_version = get_option( 'edd_getresponse_version' );

    if( ! $edd_getresponse_version ) {
        printf(
            '<div class="updated"><p>' . __( 'We have changed the GetResponse settings structure to provide you with a more reliable experience. Please click <a href="%s">here</a> to start the upgrade.', 'edd-getresponse' ) . '</p></div>',
            esc_url( add_query_arg( array( 'edd_action' => 'getresponse_v200_upgrade' ), admin_url() ) )
        );
    }
}
add_action( 'admin_notices', 'edd_getresponse_show_upgrade_notice' );


/**
 * Convert pre-2.0.0 per-download settings to the new format
 *
 * @since       2.0.0
 * @global      object $wpdb The WordPress database object
 * @return      void
 */
function edd_getresponse_v200_upgrades() {
    if( get_option( 'edd_getresponse_v200_upgraded' ) ) {
        return;
    }

    global $wpdb;

    ignore_user_abort( true );

    if( ! edd_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
        set_time_limit( 0 );
    }

    // Get old-school download overrides
    $downloads = $wpdb->get_col( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key LIKE '%_edd_getresponse_campaign%';" );

    if( $downloads ) {
        foreach( $downloads as $download ) {
            $campaign = get_post_meta( $download, '_edd_getresponse_campaign', true );
            add_post_meta( $download, '_edd_getresponse', (array) $campaign );
        }
    }

    add_option( 'edd_getresponse_v200_upgraded', '1' );

    // Pre-2.0.0, we didn't store versions
    add_option( 'edd_getresponse_version', '2.0.0' );

    // Redirect to settings
    wp_safe_redirect( admin_url( add_query_arg( array( 'post_type' => 'download', 'page' => 'edd-settings', 'tab' => 'extensions', 'edd-action' => null ), 'edit.php' ) ) );
    die();
}
add_action( 'edd_getresponse_v200_upgrade', 'edd_getresponse_v200_upgrades' );
