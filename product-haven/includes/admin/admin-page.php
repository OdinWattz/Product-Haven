<?php
/**
 * Product Haven — Admin pagina handler
 *
 * All functions are prefixed with ph_ per the plugin prefix convention.
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
 *
 * @package ProductHaven
 */

defined( 'ABSPATH' ) || exit;

/* ---- Menu ---- */
add_action( 'admin_menu', 'ph_register_menu' );
function ph_register_menu(): void {
    if ( function_exists( 'mcd_add_submenu_page' ) ) {
        mcd_add_submenu_page( 'Product Haven', 'Product Haven', 'manage_woocommerce', 'product-haven', 'ph_render_admin_page' );
    } else {
        add_menu_page( 'Product Haven', 'Product Haven', 'manage_woocommerce', 'product-haven', 'ph_render_admin_page', 'dashicons-chart-line', 57 );
    }
}

/* ---- Instellingen opslaan ---- */
add_action( 'admin_init', 'ph_handle_settings_save' );
function ph_handle_settings_save(): void {
    if ( ! isset( $_POST['ph_save'], $_POST['ph_nonce'] ) ) return;
    if ( ! current_user_can( 'manage_woocommerce' ) ) wp_die( 'Geen toegang.' );
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ph_nonce'] ) ), 'ph_save_settings' ) ) wp_die( 'Beveiligingsfout.' );

    $current = get_option( 'ph_options', [] );
    $new = [
        'default_period'    => absint( $_POST['default_period']    ?? 30 ),
        'chart_type'        => in_array( wp_unslash( $_POST['chart_type'] ?? '' ), [ 'line', 'bar' ], true ) ? sanitize_key( wp_unslash( $_POST['chart_type'] ) ) : 'line', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        'show_avg_order'    => isset( $_POST['show_avg_order'] )    ? '1' : '',
        'show_top_products' => isset( $_POST['show_top_products'] ) ? '1' : '',
        'show_returning'    => isset( $_POST['show_returning'] )    ? '1' : '',
        'items_per_page'    => min( 100, absint( $_POST['items_per_page'] ?? 20 ) ),
        'export_columns'    => array_map( 'sanitize_key', (array) ( $_POST['export_columns'] ?? [] ) ),
        'accent_color'      => sanitize_hex_color( wp_unslash( $_POST['accent_color'] ?? '#10B981' ) ) ?: '#10B981',
        'enable_rest_api'   => isset( $_POST['enable_rest_api'] ) ? '1' : '',
    ];

    update_option( 'ph_options', $new );

    // Cache legen na instellingswijziging
    global $wpdb;
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ph_%' OR option_name LIKE '_transient_timeout_ph_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

    wp_safe_redirect( add_query_arg( 'ph_saved', '1', wp_get_referer() ?: admin_url( 'admin.php?page=product-haven' ) ) );
    exit;
}

/* ---- Pagina weergeven ---- */
function ph_render_admin_page(): void {
    if ( ! current_user_can( 'manage_woocommerce' ) ) return;

    // AJAX handlers laden (require enkel als ze nodig zijn)
    require_once PH_PATH . 'includes/ajax.php';

    require_once PH_PATH . 'includes/admin/settings-page.php';
}
