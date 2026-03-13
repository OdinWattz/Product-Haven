<?php
/**
 * Product Haven — AJAX handlers
 *
 * Note: Nonce verification is performed in ph_verify_admin_nonce() and
 * ph_verify_front_nonce() via check_ajax_referer(). PHPCS cannot detect
 * this indirect verification, so suppression comments are used where needed.
 *
 * @package ProductHaven
 */

// phpcs:disable WordPress.Security.NonceVerification.Missing
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound

defined( 'ABSPATH' ) || exit;

require_once PH_PATH . 'includes/data.php';

/* ============================================================
   Helper functions
   ============================================================ */

function ph_verify_admin_nonce(): void {
    if ( ! check_ajax_referer( 'ph_admin_nonce', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => 'Ongeldige nonce.' ], 403 );
    }
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_send_json_error( [ 'message' => 'Geen toegang.' ], 403 );
    }
}

function ph_verify_front_nonce(): void {
    if ( ! check_ajax_referer( 'ph_front_nonce', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => 'Ongeldige nonce.' ], 403 );
    }
}

/* ============================================================
   Admin AJAX
   ============================================================ */

/** Core stats */
function ph_ajax_get_stats(): void {
    ph_verify_admin_nonce();
    $days  = absint( $_POST['days'] ?? 30 );
    $force = ! empty( $_POST['force_refresh'] );
    if ( $force ) {
        delete_transient( 'ph_stats_' . $days );
        // Top products have their own transient — also delete on force refresh
        delete_transient( 'ph_top_prod_' . $days . '_5' );
    }
    wp_send_json_success( ph_get_stats( $days ) );
}

/** Chart data */
function ph_ajax_get_chart_data(): void {
    ph_verify_admin_nonce();
    $days  = absint( $_POST['days'] ?? 30 );
    $type  = sanitize_key( $_POST['type'] ?? 'both' );
    $force = ! empty( $_POST['force_refresh'] );
    if ( $force ) {
        delete_transient( 'ph_chart_' . $days . '_' . $type );
    }
    wp_send_json_success( ph_get_chart_data( $days, $type ) );
}

/** Timeline */
function ph_ajax_get_timeline(): void {
    ph_verify_admin_nonce();
    $args = [
        'page'     => absint( $_POST['page']     ?? 1 ),
        'per_page' => absint( $_POST['per_page'] ?? 20 ),
        'status'   => sanitize_key( $_POST['status']  ?? 'any' ),
        'search'   => sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) ),
        'days'     => absint( $_POST['days']     ?? 0 ),
    ];
    wp_send_json_success( ph_get_timeline( $args ) );
}

/** CSV export */
function ph_ajax_export_csv(): void {
    ph_verify_admin_nonce();

    // Remove WP's own shutdown handler so it doesn't send JSON after our exit.
    remove_action( 'shutdown', 'wp_ob_end_flush_all', 1 );

    $args = [
        'status' => sanitize_key( $_POST['status'] ?? 'any' ),
        'days'   => absint( $_POST['days']   ?? 0 ),
        'search' => sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) ),
    ];
    ph_export_csv( $args ); // exits itself
}

/** Customer card */
function ph_ajax_get_customer(): void {
    ph_verify_admin_nonce();
    $email = sanitize_email( wp_unslash( $_POST['customer_email'] ?? '' ) );
    $id    = absint( $_POST['customer_id'] ?? 0 );
    if ( ! $email && ! $id ) wp_send_json_error( [ 'message' => 'Geen klant opgegeven.' ] );
    wp_send_json_success( ph_get_customer_card( $id, $email ) );
}

/** Fetch single order (for orders not in the timeline cache) */
function ph_ajax_get_single_order(): void {
    ph_verify_admin_nonce();
    $order_id = absint( $_POST['order_id'] ?? 0 );
    if ( ! $order_id ) wp_send_json_error( [ 'message' => 'Geen order ID.' ] );

    // Clear WP/WC caches so we always read fresh order data.
    clean_post_cache( $order_id );
    wc_delete_shop_order_transients( $order_id );

    $order = wc_get_order( $order_id );
    if ( ! $order ) wp_send_json_error( [ 'message' => 'Order niet gevonden.' ] );
    wp_send_json_success( ph_format_order( $order ) );
}

/** Add order note */
function ph_ajax_update_order_note(): void {
    ph_verify_admin_nonce();
    $order_id = absint( $_POST['order_id'] ?? 0 );
    $note     = sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) );
    $is_cust  = ! empty( $_POST['customer_note'] );

    if ( ! $order_id || ! $note ) {
        wp_send_json_error( [ 'message' => 'Ontbrekende gegevens.' ] );
    }

    $order = wc_get_order( $order_id );
    if ( ! $order ) wp_send_json_error( [ 'message' => 'Order niet gevonden.' ] );

    $note_id = $order->add_order_note( $note, $is_cust ? 1 : 0, true );
    wp_send_json_success( [ 'note_id' => $note_id, 'message' => __( 'Notitie toegevoegd.', 'product-haven' ) ] );
}

/** Update order status */
function ph_ajax_update_order_status(): void {
    ph_verify_admin_nonce();

    $order_id   = absint( $_POST['order_id'] ?? 0 );
    $new_status = sanitize_key( $_POST['status'] ?? '' );

    $allowed = [ 'pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed' ];
    if ( ! $order_id || ! in_array( $new_status, $allowed, true ) ) {
        wp_send_json_error( [ 'message' => 'Ongeldige status of order ID.' ] );
    }

    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        wp_send_json_error( [ 'message' => 'Order niet gevonden.' ] );
    }

    $order->update_status( $new_status, __( 'Status gewijzigd via Product Haven.', 'product-haven' ), true );

    wp_send_json_success( [
        'order_id'     => $order_id,
        'status'       => $order->get_status(),
        'status_label' => ph_t( 'wc_status_' . str_replace( '-', '_', $order->get_status() ) ),
    ] );
}

/** Delete order */
function ph_ajax_delete_order(): void {
    ph_verify_admin_nonce();

    $order_id = absint( $_POST['order_id'] ?? 0 );
    if ( ! $order_id ) {
        wp_send_json_error( [ 'message' => 'Geen order ID opgegeven.' ] );
    }

    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        wp_send_json_error( [ 'message' => 'Order niet gevonden.' ] );
    }

    $order->delete( true ); // true = permanently delete
    wp_send_json_success( [ 'order_id' => $order_id, 'message' => 'Order verwijderd.' ] );
}

/** Edit order (customer details + note) */
function ph_ajax_qp_save_order(): void {
    ph_verify_admin_nonce();

    $order_id = absint( $_POST['order_id'] ?? 0 );
    if ( ! $order_id ) {
        wp_send_json_error( [ 'message' => 'Geen order ID opgegeven.' ] );
    }

    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        wp_send_json_error( [ 'message' => 'Order niet gevonden.' ] );
    }

    // Update billing address fields
    $billing_fields = [
        'first_name', 'last_name', 'company', 'email',
        'phone', 'address_1', 'address_2', 'city', 'postcode', 'country',
    ];
    foreach ( $billing_fields as $field ) {
        $key = 'billing_' . $field;
        if ( isset( $_POST[ $key ] ) ) {
            $value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
            if ( $field === 'email' ) {
                $value = sanitize_email( wp_unslash( $_POST[ $key ] ) );
            }
            $setter = 'set_billing_' . $field;
            if ( method_exists( $order, $setter ) ) {
                $order->$setter( $value );
            }
        }
    }

    $order->save();

    // Optional internal note
    $note = sanitize_textarea_field( wp_unslash( $_POST['internal_note'] ?? '' ) );
    if ( $note !== '' ) {
        $order->add_order_note( $note, 0, true );
    }

    // Clear cache and re-fetch order so all fields are fresh
    wc_delete_shop_order_transients( $order_id );
    clean_post_cache( $order_id );
    $order = wc_get_order( $order_id );

    wp_send_json_success( ph_format_order( $order ) );
}

/**
 * Process refund: change status only OR create a full WC refund.
 */
function ph_ajax_process_refund(): void {
    ph_verify_admin_nonce();

    $order_id    = absint( $_POST['order_id']    ?? 0 );
    $refund_type = sanitize_key( $_POST['refund_type'] ?? 'status_only' );

    if ( ! $order_id ) {
        wp_send_json_error( [ 'message' => 'Geen order ID opgegeven.' ] );
    }

    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        wp_send_json_error( [ 'message' => 'Order niet gevonden.' ] );
    }

    if ( 'full_refund' === $refund_type ) {
        $amount = $order->get_total() - $order->get_total_refunded();
        if ( $amount > 0 ) {
            $refund = wc_create_refund( [
                'amount'     => $amount,
                'reason'     => __( 'Terugbetaling via Product Haven', 'product-haven' ),
                'order_id'   => $order_id,
                'line_items' => [],
                'refund_payment' => false, // no automatic gateway transaction
            ] );
            if ( is_wp_error( $refund ) ) {
                wp_send_json_error( [ 'message' => $refund->get_error_message() ] );
            }
        }
    }

    $order->update_status( 'refunded', __( 'Retour verwerkt via Product Haven.', 'product-haven' ), true );

    wp_send_json_success( [
        'order_id'     => $order_id,
        'status'       => $order->get_status(),
        'status_label' => ph_t( 'wc_status_' . str_replace( '-', '_', $order->get_status() ) ),
        'refund_type'  => $refund_type,
    ] );
}

/**
 * Revert refund: restore status and optionally delete WC refund records.
 */
function ph_ajax_revert_refund(): void {
    ph_verify_admin_nonce();

    $order_id       = absint( $_POST['order_id']       ?? 0 );
    $new_status     = sanitize_key( $_POST['new_status']     ?? 'processing' );
    $delete_refunds = ! empty( $_POST['delete_refunds'] ) && '1' === $_POST['delete_refunds'];

    $allowed_statuses = [ 'pending', 'processing', 'on-hold', 'completed', 'cancelled', 'failed' ];
    if ( ! $order_id || ! in_array( $new_status, $allowed_statuses, true ) ) {
        wp_send_json_error( [ 'message' => 'Ongeldige parameters.' ] );
    }

    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        wp_send_json_error( [ 'message' => 'Order niet gevonden.' ] );
    }

    if ( $delete_refunds ) {
        foreach ( $order->get_refunds() as $refund ) {
            $refund_obj = wc_get_order( $refund->get_id() );
            if ( $refund_obj ) {
                $refund_obj->delete( true );
            }
        }
        // Clear cache so WC recalculates the new totals
        wc_delete_shop_order_transients( $order_id );
    }

    $order->update_status( $new_status, __( 'Retour teruggedraaid via Product Haven.', 'product-haven' ), true );

    wp_send_json_success( [
        'order_id'     => $order_id,
        'status'       => $order->get_status(),
        'status_label' => ph_t( 'wc_status_' . str_replace( '-', '_', $order->get_status() ) ),
    ] );
}

/** Top products with variable limit */
function ph_ajax_get_top_products(): void {
    ph_verify_admin_nonce();
    $days  = absint( $_POST['days']  ?? 30 );
    $limit = min( 100, absint( $_POST['limit'] ?? 10 ) );
    $force = ! empty( $_POST['force_refresh'] );
    if ( $force ) {
        delete_transient( "ph_top_prod_{$days}_{$limit}" );
    }
    wp_send_json_success( ph_get_top_products( $days, $limit ) );
}

/** Revenue report */
function ph_ajax_get_revenue_report(): void {
    ph_verify_admin_nonce();
    $days  = absint( $_POST['days'] ?? 30 );
    $force = ! empty( $_POST['force_refresh'] );
    if ( $force ) {
        delete_transient( "ph_revenue_report_{$days}" );
    }
    wp_send_json_success( ph_get_revenue_report( $days ) );
}

/** Categories report */
function ph_ajax_get_top_categories(): void {
    ph_verify_admin_nonce();
    $days  = absint( $_POST['days']  ?? 30 );
    $limit = min( 50, absint( $_POST['limit'] ?? 10 ) );
    $force = ! empty( $_POST['force_refresh'] );
    if ( $force ) {
        delete_transient( "ph_top_cats_{$days}_{$limit}" );
    }
    wp_send_json_success( ph_get_top_categories( $days, $limit ) );
}

/** Coupons report */
function ph_ajax_get_coupons_report(): void {
    ph_verify_admin_nonce();
    $days  = absint( $_POST['days']  ?? 30 );
    $limit = min( 100, absint( $_POST['limit'] ?? 20 ) );
    $force = ! empty( $_POST['force_refresh'] );
    if ( $force ) {
        delete_transient( "ph_coupons_{$days}_{$limit}" );
    }
    wp_send_json_success( ph_get_coupons_report( $days, $limit ) );
}

/** Daily report — products per day */
function ph_ajax_get_daily_products(): void {
    ph_verify_admin_nonce();
    $date  = sanitize_text_field( wp_unslash( $_POST['date'] ?? gmdate( 'Y-m-d' ) ) );
    $force = ! empty( $_POST['force_refresh'] );
    if ( $force ) {
        delete_transient( "ph_daily_prod_{$date}" );
    }
    wp_send_json_success( ph_get_daily_products_report( $date ) );
}

/** Top customers report */
function ph_ajax_get_top_customers(): void {
    ph_verify_admin_nonce();
    $days  = absint( $_POST['days']  ?? 30 );
    $limit = min( 100, absint( $_POST['limit'] ?? 20 ) );
    $force = ! empty( $_POST['force_refresh'] );
    if ( $force ) {
        delete_transient( "ph_top_customers_{$days}_{$limit}" );
    }
    wp_send_json_success( ph_get_top_customers( $days, $limit ) );
}

/* ============================================================
   Front-end AJAX (for logged-in customers)
   ============================================================ */

/** Stats for the logged-in customer */
function ph_ajax_front_stats(): void {
    ph_verify_front_nonce();

    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        wp_send_json_error( [ 'message' => 'Niet ingelogd.' ], 401 );
    }

    $days = absint( $_POST['days'] ?? 30 );

    $orders = wc_get_orders( [
        'customer' => $user_id,
        'status'   => [ 'wc-completed', 'wc-processing', 'wc-on-hold' ],
        'limit'    => -1,
        'return'   => 'objects',
    ] );

    $count = count( $orders );
    $total = array_sum( array_map( fn( $o ) => (float) $o->get_total(), $orders ) );

    // Return raw numbers — JS handles formatting
    wp_send_json_success( [
        'revenue'     => round( $total, 2 ),
        'orders'      => $count,
        'avg_order'   => $count > 0 ? round( $total / $count, 2 ) : 0,
    ] );
}

/** Timeline for unauthenticated visitors (public — returns empty data) */
function ph_ajax_front_stats_public(): void {
    wp_send_json_error( [ 'message' => __( 'Log in om je statistieken te zien.', 'product-haven' ) ], 401 );
}

/** Order timeline for the logged-in customer */
function ph_ajax_front_timeline(): void {
    ph_verify_front_nonce();

    $user_id = get_current_user_id();
    if ( ! $user_id ) wp_send_json_error( [ 'message' => 'Niet ingelogd.' ], 401 );

    $page  = absint( $_POST['page'] ?? 1 );
    $limit = 10;

    $orders = wc_get_orders( [
        'customer' => $user_id,
        'limit'    => $limit,
        'offset'   => ( $page - 1 ) * $limit,
        'orderby'  => 'date',
        'order'    => 'DESC',
    ] );

    $total = count( wc_get_orders( [
        'customer' => $user_id,
        'limit'    => -1,
        'return'   => 'ids',
    ] ) );

    wp_send_json_success( [
        'orders'      => array_map( 'ph_format_order', $orders ),
        'total'       => $total,
        'total_pages' => (int) ceil( $total / $limit ),
        'page'        => $page,
    ] );
}

/* ============================================================
   LOW STOCK
   ============================================================ */

function ph_ajax_get_low_stock(): void {
    ph_verify_admin_nonce();
    $threshold = absint( $_POST['threshold'] ?? 20 );
    wp_send_json_success( ph_get_low_stock( $threshold ) );
}

function ph_ajax_update_stock(): void {
    ph_verify_admin_nonce();

    $product_id = absint( $_POST['product_id'] ?? 0 );
    $new_stock  = absint( $_POST['new_stock']  ?? 0 );
    $reason     = sanitize_text_field( wp_unslash( $_POST['reason'] ?? 'Bijgewerkt via Product Haven' ) );

    if ( ! $product_id ) {
        wp_send_json_error( [ 'message' => 'Ongeldig product.' ] );
    }

    wp_send_json_success( ph_update_stock( $product_id, $new_stock, $reason ) );
}

/* ============================================================
   STOCK AJAX HANDLERS
   ============================================================ */

function ph_ajax_stock_get(): void {
    ph_verify_admin_nonce();
    $args = [
        'status'   => sanitize_key( $_POST['status']   ?? 'all' ),
        'search'   => sanitize_text_field( wp_unslash( $_POST['search']   ?? '' ) ),
        'orderby'  => sanitize_key( $_POST['sort_col'] ?? 'stock' ),
        'order'    => strtoupper( sanitize_key( $_POST['sort_dir'] ?? 'ASC' ) ) === 'DESC' ? 'DESC' : 'ASC',
        'page'     => absint( $_POST['page']     ?? 1 ),
        'per_page' => absint( $_POST['per_page'] ?? 25 ),
    ];
    wp_send_json_success( ph_stock_get( $args ) );
}

function ph_ajax_stock_update(): void {
    ph_verify_admin_nonce();
    $updates = json_decode( wp_unslash( $_POST['updates'] ?? '[]' ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    if ( ! is_array( $updates ) || empty( $updates ) ) {
        wp_send_json_error( [ 'message' => 'Geen geldige updates.' ] );
    }
    $results = [];
    foreach ( $updates as $u ) {
        $product_id = absint( $u['id']     ?? 0 );
        $new_stock  = absint( $u['qty']    ?? $u['stock'] ?? 0 );
        $reason     = sanitize_text_field( $u['reason'] ?? 'Handmatig bijgewerkt via Product Haven' );
        if ( $product_id > 0 ) {
            $results[] = ph_stock_update( $product_id, $new_stock, $reason );
        }
    }
    wp_send_json_success( [ 'updated' => count( $results ), 'message' => count( $results ) . ' product(en) bijgewerkt.', 'results' => $results ] );
}

function ph_ajax_stock_export_csv(): void {
    ph_verify_admin_nonce();
    remove_action( 'shutdown', 'wp_ob_end_flush_all', 1 );
    ph_stock_export_csv();
}

function ph_ajax_stock_save_settings(): void {
    ph_verify_admin_nonce();
    $input = json_decode( wp_unslash( $_POST['settings'] ?? '{}' ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    if ( ! is_array( $input ) ) $input = [];
    $opts = get_option( 'ph_stock_options', [] );
    if ( isset( $input['low_stock_threshold'] ) ) $opts['low_stock_threshold'] = absint( $input['low_stock_threshold'] );
    if ( isset( $input['alert_email'] ) )         $opts['alert_email']         = sanitize_email( $input['alert_email'] );
    if ( isset( $input['realtime_alerts'] ) )     $opts['realtime_alerts']     = $input['realtime_alerts'] === '1' ? '1' : '0';
    if ( isset( $input['daily_digest'] ) )        $opts['daily_digest']        = $input['daily_digest']    === '1' ? '1' : '0';
    if ( isset( $input['alert_statuses'] ) )      $opts['alert_statuses']      = array_values( array_intersect(
        array_map( 'sanitize_key', (array) $input['alert_statuses'] ),
        [ 'low', 'out' ]
    ) );
    update_option( 'ph_stock_options', $opts );
    wp_send_json_success( [ 'message' => 'Instellingen opgeslagen.' ] );
}

function ph_ajax_stock_send_test_alert(): void {
    ph_verify_admin_nonce();
    $result = ph_stock_send_alert( true );
    if ( $result ) {
        wp_send_json_success( [ 'message' => 'Test-e-mail verstuurd.' ] );
    } else {
        wp_send_json_error( [ 'message' => 'Verzenden mislukt. Controleer het e-mailadres.' ] );
    }
}
