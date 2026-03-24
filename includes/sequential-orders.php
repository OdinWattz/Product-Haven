<?php
/**
 * Product Haven — Sequential Orders module
 *
 * Give WooCommerce orders their own sequential order number,
 * independent of the WordPress post ID. Fully integrated into Product Haven.
 *
 * Original logic: Odin's Sequential Orders (odinwattez.nl)
 * Integrated under Product Haven prefix: ph_so_*
 *
 * All functions are prefixed with ph_ or ph_so_ per the plugin prefix convention.
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
 *
 * @package ProductHaven
 */

defined( 'ABSPATH' ) || exit;

/*
   ORDER NUMBER ASSIGNMENT
*/

add_action( 'woocommerce_checkout_order_created', 'ph_so_assign_order_number' );
add_action( 'woocommerce_store_api_checkout_order_processed', 'ph_so_assign_order_number' );

function ph_so_assign_order_number( $order ): void {
    // Prevent duplicate assignment
    if ( $order->get_meta( '_ph_so_order_number' ) ) {
        return;
    }

    $options = get_option( 'ph_so_options', [] );
    $prefix  = $options['prefix']  ?? '';
    $suffix  = $options['suffix']  ?? '';
    $padding = max( 1, (int) ( $options['padding'] ?? 1 ) );

    $number    = ph_so_next_number();
    $formatted = $prefix . str_pad( $number, $padding, '0', STR_PAD_LEFT ) . $suffix;

    $order->update_meta_data( '_ph_so_order_number',      $number );
    $order->update_meta_data( '_ph_so_order_number_full', $formatted );
    $order->save();
}

/**
 * Thread-safe atomic counter via DB lock.
 */
function ph_so_next_number(): int {
    global $wpdb;

    $wpdb->query( "SELECT GET_LOCK('ph_so_order_counter', 5)" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

    $current = (int) get_option( 'ph_so_counter', 0 );
    $options = get_option( 'ph_so_options', [] );
    $start   = max( 1, (int) ( $options['start_number'] ?? 1 ) );

    $next = max( $start, $current + 1 );
    update_option( 'ph_so_counter', $next, false );

    $wpdb->query( "SELECT RELEASE_LOCK('ph_so_order_counter')" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

    return $next;
}

/*
   ORDER NUMBER DISPLAY IN WOOCOMMERCE
*/

add_filter( 'woocommerce_order_number', 'ph_so_filter_order_number', 10, 2 );
function ph_so_filter_order_number( $order_number, $order ) {
    $full = $order->get_meta( '_ph_so_order_number_full' );
    return $full ?: $order_number;
}

/*
   SEARCHING IN ADMIN BY ORDER NUMBER
*/

add_filter( 'woocommerce_shop_order_search_fields', 'ph_so_add_search_fields' );
function ph_so_add_search_fields( $search_fields ) {
    $search_fields[] = '_ph_so_order_number_full';
    $search_fields[] = '_ph_so_order_number';
    return $search_fields;
}

/*
   SETTINGS SAVE (admin_post handler)
*/

add_action( 'admin_post_ph_so_save_settings', 'ph_so_save_settings' );
function ph_so_save_settings(): void {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'No access.' );
    check_admin_referer( 'ph_so_settings_nonce' );

    $options = [
        'prefix'       => sanitize_text_field( wp_unslash( $_POST['prefix']       ?? '' ) ),
        'suffix'       => sanitize_text_field( wp_unslash( $_POST['suffix']       ?? '' ) ),
        'start_number' => max( 1, absint( $_POST['start_number'] ?? 1 ) ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash
        'padding'      => max( 1, min( 10, absint( $_POST['padding'] ?? 1 ) ) ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash
    ];

    // If start number is higher than current counter, update counter
    $current_counter = (int) get_option( 'ph_so_counter', 0 );
    if ( $options['start_number'] > $current_counter ) {
        update_option( 'ph_so_counter', $options['start_number'] - 1, false );
    }

    update_option( 'ph_so_options', $options );

    wp_safe_redirect( add_query_arg( [ 'page' => 'product-haven', 'ph_so_saved' => '1', 'tab' => 'sequential' ], admin_url( 'admin.php' ) ) );
    exit;
}

/*
   COUNTER RESET (admin_post handler)
*/

add_action( 'admin_post_ph_so_reset_counter', 'ph_so_reset_counter' );
function ph_so_reset_counter(): void {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'No access.' );
    check_admin_referer( 'ph_so_reset_nonce' );

    $options = get_option( 'ph_so_options', [] );
    $start   = max( 1, (int) ( $options['start_number'] ?? 1 ) );
    update_option( 'ph_so_counter', $start - 1, false );

    wp_safe_redirect( add_query_arg( [ 'page' => 'product-haven', 'ph_so_reset' => '1', 'tab' => 'sequential' ], admin_url( 'admin.php' ) ) );
    exit;
}

/*
   PAGE DATA HELPER — used by the tab partial
*/

function ph_so_get_page_data(): array {
    $options      = get_option( 'ph_so_options', [] );
    $counter      = (int) get_option( 'ph_so_counter', 0 );
    $prefix       = $options['prefix']       ?? '';
    $suffix       = $options['suffix']       ?? '';
    $start_number = $options['start_number'] ?? 1;
    $padding      = $options['padding']      ?? 1;

    $preview_num = max( (int) $start_number, $counter + 1 );
    $preview     = $prefix . str_pad( $preview_num, max( 1, (int) $padding ), '0', STR_PAD_LEFT ) . $suffix;

    return compact( 'options', 'counter', 'prefix', 'suffix', 'start_number', 'padding', 'preview' );
}
