<?php
/**
 * Product Haven — Data layer
 * All database queries, calculations, and helpers.
 *
 * Direct database queries are required throughout this file because:
 * - WooCommerce HPOS table names must be interpolated (they are derived from $wpdb->prefix,
 *   which is trusted internal data, not user input).
 * - $wpdb->prepare() handles user-supplied values; table names cannot use placeholders.
 * - Transient caching is used where appropriate; some queries bypass cache intentionally
 *   (e.g., real-time stock checks, mutations).
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
 * phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
 * phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
 *
 * @package ProductHaven
 */

defined( 'ABSPATH' ) || exit;

/* ============================================================
   STATISTICS
   ============================================================ */

/**
 * Take core metrics for a period in days.
 * Result is cached as transient (15 minutes).
 *
 * @param int $days
 * @return array{revenue: float, orders: int, avg_order: float, new_customers: int, returning: int, refunds: float}
 */
function ph_get_stats( int $days = 30 ): array {
    $cache_key = 'ph_stats_' . $days . '_' . ph_get_lang();
    $cached    = get_transient( $cache_key );
    // Security check: if the cache has no _cached_at or is older than 16 minutes → refresh anyway.
    // This prevents a transient that was once saved without a timeout from hanging forever.
    if ( $cached !== false && isset( $cached['_cached_at'] ) && ( time() - $cached['_cached_at'] ) < 960 ) {
        return $cached;
    }

    global $wpdb;

    $since        = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
    $hpos_table   = $wpdb->prefix . 'wc_orders';
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $use_hpos     = $wpdb->get_var( "SHOW TABLES LIKE '{$hpos_table}'" ) === $hpos_table;

    if ( $use_hpos ) {
        // HPOS: wc_orders table
        $row = $wpdb->get_row( $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT COUNT(id) AS order_count,
                    COALESCE(SUM(total_amount), 0)                          AS revenue,
                    COALESCE(SUM(CASE WHEN total_amount < 0 THEN ABS(total_amount) ELSE 0 END), 0) AS refunds,
                    COUNT(DISTINCT customer_id)                              AS unique_customers
             FROM   {$hpos_table}
             WHERE  type   = 'shop_order'
               AND  status IN ('wc-completed','wc-processing','wc-on-hold')
               AND  date_created_gmt >= %s",
            $since
        ) );

        // New customers: unique customer_id's that have NO previous order
        $new_customers = (int) $wpdb->get_var( $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT COUNT(DISTINCT o.customer_id)
             FROM   {$hpos_table} o
             WHERE  o.type   = 'shop_order'
               AND  o.status IN ('wc-completed','wc-processing','wc-on-hold')
               AND  o.date_created_gmt >= %s
               AND  o.customer_id > 0
               AND  NOT EXISTS (
                   SELECT 1 FROM {$hpos_table} prev
                   WHERE  prev.customer_id = o.customer_id
                     AND  prev.type        = 'shop_order'
                     AND  prev.date_created_gmt < %s
               )",
            $since,
            $since
        ) );
    } else {
        // Legacy: wp_posts + wp_postmeta
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT COUNT(p.ID)                    AS order_count,
                    COALESCE(SUM(pm.meta_value+0), 0) AS revenue,
                    0                              AS refunds,
                    COUNT(DISTINCT cust.meta_value) AS unique_customers
             FROM   {$wpdb->posts} p
             INNER  JOIN {$wpdb->postmeta} pm   ON pm.post_id   = p.ID AND pm.meta_key   = '_order_total'
             LEFT   JOIN {$wpdb->postmeta} cust ON cust.post_id = p.ID AND cust.meta_key = '_customer_user'
             WHERE  p.post_type   = 'shop_order'
               AND  p.post_status IN ('wc-completed','wc-processing','wc-on-hold')
               AND  p.post_date  >= %s",
            $since
        ) );

        $new_customers = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT pm_c.meta_value)
             FROM   {$wpdb->posts} p
             INNER  JOIN {$wpdb->postmeta} pm_c ON pm_c.post_id = p.ID AND pm_c.meta_key = '_customer_user'
             WHERE  p.post_type   = 'shop_order'
               AND  p.post_status IN ('wc-completed','wc-processing','wc-on-hold')
               AND  p.post_date  >= %s
               AND  pm_c.meta_value > 0
               AND  NOT EXISTS (
                   SELECT 1 FROM {$wpdb->posts} p2
                   INNER JOIN {$wpdb->postmeta} pm2 ON pm2.post_id = p2.ID AND pm2.meta_key = '_customer_user'
                   WHERE  pm2.meta_value = pm_c.meta_value
                     AND  p2.post_type   = 'shop_order'
                     AND  p2.post_date   < %s
               )",
            $since,
            $since
        ) );
    }

    $count   = (int)   ( $row->order_count      ?? 0 );
    $revenue = (float) ( $row->revenue           ?? 0 );
    $refunds = (float) ( $row->refunds           ?? 0 );

    $result = [
        'revenue'       => $revenue,
        'orders'        => $count,
        'avg_order'     => $count > 0 ? round( $revenue / $count, 2 ) : 0,
        'new_customers' => (int) $new_customers,
        'returning'     => max( 0, (int) ( $row->unique_customers ?? 0 ) - (int) $new_customers ),
        'refunds'       => $refunds,
        'period_days'   => $days,
        'top_products'  => ph_get_top_products( $days, 5 ),
        '_cached_at'    => time(),
    ];

    set_transient( $cache_key, $result, 15 * MINUTE_IN_SECONDS );
    return $result;
}

/* ============================================================
   CHARTS
   ============================================================ */

/**
 * Daily revenue and order counts for the chart.
 * Uses a single SQL query instead of one query per day.
 *
 * @param int    $days
 * @param string $type  'revenue' | 'orders' | 'both'
 */
function ph_get_chart_data( int $days = 30, string $type = 'both' ): array {
    $cache_key = "ph_chart_{$days}_{$type}_" . ph_get_lang();
    $cached    = get_transient( $cache_key );
    if ( $cached !== false ) return $cached;

    global $wpdb;

    $since        = gmdate( 'Y-m-d 00:00:00', strtotime( "-{$days} days" ) );
    $statuses     = [ 'wc-completed', 'wc-processing', 'wc-on-hold' ];

    // Detect HPOS (wc_orders table) vs legacy (wp_posts)
    $hpos_table = $wpdb->prefix . 'wc_orders';
    $use_hpos   = $wpdb->get_var( "SHOW TABLES LIKE '{$hpos_table}'" ) === $hpos_table; // phpcs:ignore

    if ( $use_hpos ) {
        $placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
        $params       = array_merge( $statuses, [ $since ] );
        $rows         = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->prepare(
                "SELECT DATE(date_created_gmt) AS day,
                        COUNT(id)              AS order_count,
                        SUM(total_amount)      AS day_revenue
                 FROM   {$hpos_table}
                 WHERE  type   = 'shop_order'
                   AND  status IN ($placeholders)
                   AND  date_created_gmt >= %s
                 GROUP  BY DATE(date_created_gmt)
                 ORDER  BY day ASC",
                ...$params
            )
        );
    } else {
        $placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
        $params       = array_merge( $statuses, [ $since ] );
        $rows         = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->prepare(
                "SELECT DATE(p.post_date) AS day,
                        COUNT(p.ID)       AS order_count,
                        SUM(pm.meta_value + 0) AS day_revenue
                 FROM   {$wpdb->posts} p
                 INNER  JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_order_total'
                 WHERE  p.post_type   = 'shop_order'
                   AND  p.post_status IN ($placeholders)
                   AND  p.post_date  >= %s
                 GROUP  BY DATE(p.post_date)
                 ORDER  BY day ASC",
                ...$params
            )
        );
    }

    // Build lookup per day string
    $by_day = [];
    foreach ( (array) $rows as $row ) {
        $by_day[ $row->day ] = [
            'revenue' => (float) $row->day_revenue,
            'count'   => (int)   $row->order_count,
        ];
    }

    $labels  = [];
    $revenue = [];
    $orders  = [];

    for ( $i = $days - 1; $i >= 0; $i-- ) {
        $ts      = strtotime( "midnight -{$i} days" );
        $day_key = gmdate( 'Y-m-d', $ts );
        $labels[]  = ph_date( 'd M', $ts );
        $revenue[] = $by_day[ $day_key ]['revenue'] ?? 0;
        $orders[]  = $by_day[ $day_key ]['count']   ?? 0;
    }

    $result = compact( 'labels', 'revenue', 'orders' );
    set_transient( $cache_key, $result, 15 * MINUTE_IN_SECONDS );
    return $result;
}

/* ============================================================
   TIMELINE
   ============================================================ */

/**
 * Retrieve orders for the timeline, with pagination and filters.
 *
 * @param array $args {
 *   @type int    $page
 *   @type int    $per_page
 *   @type string $status      WC-statusslug of 'any'
 *   @type string $search      Zoekterm (naam, e-mail, order-ID)
 *   @type int    $days        0 = alle
 * }
 */
function ph_get_timeline( array $args = [] ): array {
    $defaults = [
        'page'     => 1,
        'per_page' => (int) ph_get_option( 'items_per_page', 20 ),
        'status'   => 'any',
        'search'   => '',
        'days'     => 0,
    ];
    $args = wp_parse_args( $args, $defaults );

    $query_args = [
        'limit'    => $args['per_page'],
        'offset'   => ( $args['page'] - 1 ) * $args['per_page'],
        'orderby'  => 'date',
        'order'    => 'DESC',
        'return'   => 'objects',
        'type'     => 'shop_order',   // Exclude refunds and other types
        'paginate' => true,           // WC calculates total itself — no separate COUNT query
    ];

    if ( $args['status'] !== 'any' ) {
        // wc_get_orders() accepts status both with and without the 'wc-' prefix,
        // but HPOS silently returns 0 results when the prefix is included as a plain
        // string. Strip it so both legacy and HPOS return the correct rows.
        $query_args['status'] = ltrim( $args['status'], 'wc-' );
    } else {
        $query_args['status'] = array_keys( wc_get_order_statuses() );
    }

    if ( $args['days'] > 0 ) {
        $query_args['date_created'] = '>' . strtotime( "-{$args['days']} days" );
    }

    if ( ! empty( $args['search'] ) ) {
        // Numeric = order ID search
        if ( is_numeric( $args['search'] ) ) {
            $query_args['post__in'] = [ (int) $args['search'] ];
        } else {
            $query_args['customer'] = sanitize_text_field( $args['search'] );
        }
    }

    $paginated  = wc_get_orders( $query_args );
    $wc_orders  = $paginated->orders;
    $total      = (int) $paginated->total;
    $total_pages = (int) $paginated->max_num_pages;

    $result = [];
    foreach ( $wc_orders as $order ) {
        if ( ! $order instanceof WC_Order ) continue;
        $result[] = ph_format_order( $order );
    }

    return [
        'orders'      => $result,
        'total'       => $total,
        'total_pages' => $total_pages,
        'page'        => $args['page'],
    ];
}

/**
 * Format a single WC_Order into a compact array for the UI.
 */
function ph_format_order( WC_Order $order ): array {
    $items = [];
    foreach ( $order->get_items() as $item ) {
        $items[] = [
            'name'     => $item->get_name(),
            'qty'      => $item->get_quantity(),
            'subtotal' => wc_price( $item->get_subtotal() ),
        ];
    }

    return [
        'id'         => $order->get_id(),
        'number'     => $order->get_order_number(),
        'status'     => $order->get_status(),
        'status_label' => ph_t( 'wc_status_' . str_replace( '-', '_', $order->get_status() ) ),
        'date'       => $order->get_date_created() ? ph_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $order->get_date_created()->getTimestamp() ) : '',
        'date_human' => $order->get_date_created() ? $order->get_date_created()->getTimestamp() : 0,
        'customer'   => [
            'name'       => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
            'first_name' => $order->get_billing_first_name(),
            'last_name'  => $order->get_billing_last_name(),
            'company'    => $order->get_billing_company(),
            'email'      => $order->get_billing_email(),
            'phone'      => $order->get_billing_phone(),
            'address_1'  => $order->get_billing_address_1(),
            'address_2'  => $order->get_billing_address_2(),
            'city'       => $order->get_billing_city(),
            'postcode'   => $order->get_billing_postcode(),
            'country'    => $order->get_billing_country(),
            'id'         => $order->get_customer_id(),
        ],
        'total'      => $order->get_formatted_order_total(),
        'total_raw'  => (float) $order->get_total(),
        'items'      => $items,
        'items_count'=> count( $items ),
        'payment'    => $order->get_payment_method_title(),
        'notes'      => ph_get_order_notes( $order->get_id() ),
        'edit_url'   => get_edit_post_link( $order->get_id(), 'raw' ),
        'view_url'   => $order->get_view_order_url(),
        'refunded'   => (float) $order->get_total_refunded() > 0,
        'refund_amt' => wc_price( $order->get_total_refunded() ),
    ];
}

/**
 * Retrieve order notes (customer + internal).
 */
function ph_get_order_notes( int $order_id ): array {
    $notes  = wc_get_order_notes( [ 'order_id' => $order_id ] );
    $result = [];
    foreach ( $notes as $note ) {
        $result[] = [
            'content'       => wp_kses_post( $note->content ),
            'date'          => strtotime( $note->date_created ),
            'added_by'      => $note->added_by,
            'customer_note' => (bool) $note->customer_note,
        ];
    }
    return $result;
}

/* ============================================================
   CUSTOMER CARD
   ============================================================ */

/**
 * Retrieve all orders + statistics for a single customer.
 */
function ph_get_customer_card( int $customer_id, string $email = '' ): array {
    if ( ! $customer_id && ! $email ) return [];

    // Orders always retrieve on billing email (unique identifier in the customer list)
    // so that two WP users with different emails do not see each other's orders.
    $order_args = [
        'limit'   => -1,
        'orderby' => 'date',
        'order'   => 'DESC',
        'type'    => 'shop_order',
    ];

    if ( $email ) {
        $order_args['billing_email'] = $email;
    } else {
        $order_args['customer'] = $customer_id;
    }

    $orders = wc_get_orders( $order_args );

    // Profile information: first from WP user, otherwise from the most recent order
    $name       = '';
    $user_email = $email;
    $city       = '';
    $country    = '';
    $registered = ph_t( 'label_guest' );
    $avatar_src = '';

    if ( $customer_id ) {
        $wp_user = get_userdata( $customer_id );
        if ( $wp_user ) {
            $wc_customer = new WC_Customer( $customer_id );
            $name        = $wc_customer->get_display_name();
            $user_email  = $email ?: $wc_customer->get_email();
            $city        = $wc_customer->get_billing_city();
            $country     = $wc_customer->get_billing_country();
            $registered  = $wc_customer->get_date_created()
                ? ph_date( get_option( 'date_format' ), $wc_customer->get_date_created()->getTimestamp() )
                : ph_t( 'label_unknown' );
        }
    }

    // Fill empty fields from the most recent order
    if ( ! empty( $orders ) ) {
        $first = $orders[0];
        if ( ! $name ) {
            $name = trim( $first->get_billing_first_name() . ' ' . $first->get_billing_last_name() ) ?: $user_email;
        }
        if ( ! $city )    $city    = $first->get_billing_city();
        if ( ! $country ) $country = $first->get_billing_country();
        if ( ! $user_email ) $user_email = $first->get_billing_email();
    }

    $avatar_src = get_avatar_url( $user_email, [ 'size' => 64 ] );

    $total_spent = 0.0;
    $order_list  = [];
    foreach ( $orders as $o ) {
        $total_spent += (float) $o->get_total();

        $items = [];
        foreach ( $o->get_items() as $item ) {
            /** @var WC_Order_Item_Product $item */
            $items[] = [
                'name' => $item->get_name(),
                'qty'  => (int) $item->get_quantity(),
            ];
        }

        $order_list[] = [
            'id'       => $o->get_id(),
            'number'   => $o->get_order_number(),
            'status'   => ph_t( 'wc_status_' . str_replace( '-', '_', $o->get_status() ) ),
            'total'    => $o->get_formatted_order_total(),
            'date'     => $o->get_date_created() ? ph_date( get_option( 'date_format' ), $o->get_date_created()->getTimestamp() ) : '',
            'items'    => $items,
            'edit_url' => admin_url( 'post.php?post=' . $o->get_id() . '&action=edit' ),
        ];
    }

    return [
        'id'          => $customer_id,
        'name'        => $name ?: $user_email,
        'email'       => $user_email,
        'city'        => $city,
        'country'     => $country,
        'registered'  => $registered,
        'order_count' => count( $orders ),
        'total_spent' => wc_price( $total_spent ),
        'avg_order'   => count( $orders ) > 0 ? wc_price( $total_spent / count( $orders ) ) : wc_price( 0 ),
        'orders'      => $order_list,
        'avatar'      => $avatar_src,
    ];
}

/* ============================================================
   LOW STOCK
   ============================================================ */

/**
 * All products with stock ≤ $threshold, sorted lowest first.
 */
function ph_get_low_stock( int $threshold = 20 ): array {
    $query_args = [
        'post_type'      => [ 'product', 'product_variation' ],
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => [
            [
                'key'     => '_manage_stock',
                'value'   => 'yes',
                'compare' => '=',
            ],
        ],
        'fields' => 'ids',
    ];

    $ids      = get_posts( $query_args );
    $products = [];

    foreach ( $ids as $id ) {
        $product = wc_get_product( $id );
        if ( ! $product || ! $product->managing_stock() ) continue;

        $stock = (int) $product->get_stock_quantity();
        if ( $stock > $threshold ) continue;

        $parent = $product->get_parent_id() ? wc_get_product( $product->get_parent_id() ) : null;
        $name   = $parent
            ? $parent->get_name() . ' — ' . implode( ', ', array_map(
                fn( $k, $v ) => wc_attribute_label( str_replace( 'attribute_', '', $k ) ) . ': ' . $v,
                array_keys( $product->get_variation_attributes() ),
                $product->get_variation_attributes()
            ) )
            : $product->get_name();

        $products[] = [
            'id'       => $id,
            'name'     => $name,
            'sku'      => $product->get_sku() ?: '–',
            'stock'    => $stock,
            'status'   => $stock <= 0 ? 'out' : 'low',
            'edit_url' => get_edit_post_link( $product->get_parent_id() ?: $id, 'raw' ),
            'image'    => wp_get_attachment_image_url( $product->get_image_id(), [ 36, 36 ] )
                          ?: wc_placeholder_img_src( [ 36, 36 ] ),
        ];
    }

    usort( $products, fn( $a, $b ) => $a['stock'] <=> $b['stock'] );
    return $products;
}

/**
 * Update stock of a product (inline editing in Product Haven).
 */
function ph_update_stock( int $product_id, int $new_stock, string $reason = 'Bijgewerkt via Product Haven' ): array {
    $product = wc_get_product( $product_id );
    if ( ! $product ) {
        return [ 'success' => false, 'message' => 'Product niet gevonden.' ];
    }
    $old_stock = (int) $product->get_stock_quantity();
    $product->set_stock_quantity( $new_stock );
    $product->save();

    // Also log in Stock Sentinel table if that plugin is active
    if ( function_exists( 'mss_log_stock_change' ) ) {
        mss_log_stock_change( $product_id, $old_stock, $new_stock, $reason );
    }

    return [
        'success'    => true,
        'product_id' => $product_id,
        'old_stock'  => $old_stock,
        'new_stock'  => $new_stock,
    ];
}

/* ============================================================
   TOP PRODUCTS
   ============================================================ */

/**
 * Best-selling products in a period.
 */
function ph_get_top_products( int $days = 30, int $limit = 10 ): array {
    $cache_key = "ph_top_prod_{$days}_{$limit}_" . ph_get_lang();
    $cached    = get_transient( $cache_key );
    if ( $cached !== false ) return $cached;

    global $wpdb;

    $since      = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
    $hpos_table = $wpdb->prefix . 'wc_orders';
    $use_hpos   = $wpdb->get_var( "SHOW TABLES LIKE '{$hpos_table}'" ) === $hpos_table; // phpcs:ignore

    if ( $use_hpos ) {
        // HPOS: join via wc_orders tabel
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT oi.order_item_name AS name,
                        MAX( oim_pid.meta_value + 0 )   AS product_id,
                        SUM( oim_qty.meta_value + 0 )   AS qty,
                        SUM( oim_total.meta_value + 0 ) AS revenue
                 FROM   {$wpdb->prefix}woocommerce_order_items oi
                 INNER  JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_qty
                        ON   oi.order_item_id = oim_qty.order_item_id
                        AND  oim_qty.meta_key = '_qty'
                 INNER  JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_total
                        ON   oi.order_item_id = oim_total.order_item_id
                        AND  oim_total.meta_key = '_line_total'
                 LEFT   JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_pid
                        ON   oi.order_item_id = oim_pid.order_item_id
                        AND  oim_pid.meta_key = '_product_id'
                 INNER  JOIN {$hpos_table} o
                        ON   o.id = oi.order_id
                        AND  o.type   = 'shop_order'
                        AND  o.status IN ('wc-completed','wc-processing')
                        AND  o.date_created_gmt >= %s
                 WHERE  oi.order_item_type = 'line_item'
                 GROUP  BY oi.order_item_name
                 ORDER  BY qty DESC
                 LIMIT  %d",
                $since,
                $limit
            )
        );
    } else {
        // Legacy: join via wp_posts
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT oi.order_item_name AS name,
                        MAX( oim_pid.meta_value + 0 )   AS product_id,
                        SUM( oim_qty.meta_value + 0 )   AS qty,
                        SUM( oim_total.meta_value + 0 ) AS revenue
                 FROM   {$wpdb->prefix}woocommerce_order_items oi
                 INNER  JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_qty
                        ON   oi.order_item_id = oim_qty.order_item_id
                        AND  oim_qty.meta_key = '_qty'
                 INNER  JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_total
                        ON   oi.order_item_id = oim_total.order_item_id
                        AND  oim_total.meta_key = '_line_total'
                 LEFT   JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_pid
                        ON   oi.order_item_id = oim_pid.order_item_id
                        AND  oim_pid.meta_key = '_product_id'
                 INNER  JOIN {$wpdb->posts} p
                        ON   p.ID = oi.order_id
                        AND  p.post_type   = 'shop_order'
                        AND  p.post_status IN ('wc-completed','wc-processing')
                        AND  p.post_date  >= %s
                 WHERE  oi.order_item_type = 'line_item'
                 GROUP  BY oi.order_item_name
                 ORDER  BY qty DESC
                 LIMIT  %d",
                $since,
                $limit
            )
        );
    }

    // Threshold for low stock (same setting as Stock Sentinel, or default 20)
    $low_stock_threshold = 20;

    $result = array_map( function( $r ) use ( $low_stock_threshold ) {
        $product_id = (int) ( $r->product_id ?? 0 );
        $stock      = null;
        if ( $product_id ) {
            $product = wc_get_product( $product_id );
            if ( $product && $product->managing_stock() ) {
                $stock = (int) $product->get_stock_quantity();
            }
        }
        return [
            'name'       => $r->name,
            'product_id' => $product_id,
            'qty'        => (int) $r->qty,
            'revenue'    => round( (float) $r->revenue, 2 ),
            'stock'      => $stock,                          // null = not managed
            'stock_low'  => $stock !== null && $stock <= $low_stock_threshold,
            'stock_out'  => $stock !== null && $stock <= 0,
            'edit_url'   => $product_id ? get_edit_post_link( $product_id, 'raw' ) : '',
        ];
    }, $rows ?: [] );

    set_transient( $cache_key, $result, 30 * MINUTE_IN_SECONDS );
    return $result;
}

/* ============================================================
   SALES REPORT (revenue breakdown)
   ============================================================ */

/**
 * Gross sales, returns, vouchers, net revenue, taxes, shipping costs.
 */
function ph_get_revenue_report( int $days = 30 ): array {
    $cache_key = "ph_revenue_report_{$days}";
    $cached    = get_transient( $cache_key );
    if ( $cached !== false ) return $cached;

    global $wpdb;

    $since      = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
    $hpos_table = $wpdb->prefix . 'wc_orders';
    $meta_table = $wpdb->prefix . 'wc_orders_meta';
    $use_hpos   = $wpdb->get_var( "SHOW TABLES LIKE '{$hpos_table}'" ) === $hpos_table; // phpcs:ignore

    if ( $use_hpos ) {
        // HPOS: total amount from wp_wc_orders, detail fields from wp_wc_orders_meta
        $row = $wpdb->get_row( $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT
                COALESCE(SUM(o.total_amount), 0)                                                  AS net_revenue,
                COALESCE(SUM(CAST(m_ship.meta_value  AS DECIMAL(12,2))), 0)                       AS shipping,
                COALESCE(SUM(CAST(m_tax.meta_value   AS DECIMAL(12,2))
                           + CAST(COALESCE(m_stax.meta_value,'0') AS DECIMAL(12,2))), 0)          AS tax,
                COALESCE(SUM(CAST(m_disc.meta_value  AS DECIMAL(12,2))), 0)                       AS discount
             FROM {$hpos_table} o
             LEFT JOIN {$meta_table} m_ship ON m_ship.order_id = o.id AND m_ship.meta_key = '_order_shipping'
             LEFT JOIN {$meta_table} m_tax  ON m_tax.order_id  = o.id AND m_tax.meta_key  = '_order_tax'
             LEFT JOIN {$meta_table} m_stax ON m_stax.order_id = o.id AND m_stax.meta_key = '_order_shipping_tax'
             LEFT JOIN {$meta_table} m_disc ON m_disc.order_id = o.id AND m_disc.meta_key = '_cart_discount'
             WHERE o.type = 'shop_order'
               AND o.status IN ('wc-completed','wc-processing','wc-on-hold')
               AND o.date_created_gmt >= %s",
            $since
        ) );
    } else {
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT
                COALESCE(SUM(pm_total.meta_value    + 0), 0) AS net_revenue,
                COALESCE(SUM(pm_ship.meta_value     + 0), 0) AS shipping,
                COALESCE(SUM(pm_tax.meta_value      + 0
                           + pm_ship_tax.meta_value + 0), 0) AS tax,
                COALESCE(SUM(pm_disc.meta_value     + 0), 0) AS discount
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm_total    ON pm_total.post_id    = p.ID AND pm_total.meta_key    = '_order_total'
             LEFT JOIN {$wpdb->postmeta} pm_ship     ON pm_ship.post_id     = p.ID AND pm_ship.meta_key     = '_order_shipping'
             LEFT JOIN {$wpdb->postmeta} pm_tax      ON pm_tax.post_id      = p.ID AND pm_tax.meta_key      = '_order_tax'
             LEFT JOIN {$wpdb->postmeta} pm_ship_tax ON pm_ship_tax.post_id = p.ID AND pm_ship_tax.meta_key = '_order_shipping_tax'
             LEFT JOIN {$wpdb->postmeta} pm_disc     ON pm_disc.post_id     = p.ID AND pm_disc.meta_key     = '_cart_discount'
             WHERE p.post_type   = 'shop_order'
               AND p.post_status IN ('wc-completed','wc-processing','wc-on-hold')
               AND p.post_date  >= %s",
            $since
        ) );
    }

    // Gross = net revenue + discounts (discounts are already deducted in total_amount/order_total)
    $net_revenue     = (float) ( $row->net_revenue ?? 0 );
    $shipping        = (float) ( $row->shipping    ?? 0 );
    $tax             = (float) ( $row->tax         ?? 0 );
    $coupon_discount = (float) ( $row->discount    ?? 0 );
    $gross           = $net_revenue + $coupon_discount; // gross before discounts

    // Refunds
    if ( $use_hpos ) {
        $refunds = (float) $wpdb->get_var( $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT COALESCE(SUM(ABS(total_amount)), 0)
             FROM {$hpos_table}
             WHERE type = 'shop_order_refund'
               AND date_created_gmt >= %s",
            $since
        ) );
    } else {
        $refunds = (float) $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE(SUM(ABS(pm.meta_value + 0)), 0)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_order_total'
             WHERE p.post_type   = 'shop_order_refund'
               AND p.post_date  >= %s",
            $since
        ) );
    }

    // Net sales = gross - discounts - refunds
    $net_sales = $gross - $coupon_discount - $refunds; // = net_revenue - refunds

    $result = [
        'gross_sales'    => round( $gross,        2 ),
        'refunds'        => round( $refunds,       2 ),
        'coupons'        => round( $coupon_discount, 2 ),
        'net_sales'      => round( max( 0, $net_sales ), 2 ),
        'taxes'          => round( $tax,           2 ),
        'shipping'       => round( $shipping,      2 ),
        'total_sales'    => round( max( 0, $net_revenue - $refunds ), 2 ),
    ];

    set_transient( $cache_key, $result, 15 * MINUTE_IN_SECONDS );
    return $result;
}

/* ============================================================
   TOP CUSTOMERS
   ============================================================ */

function ph_get_top_customers( int $days = 30, int $limit = 20 ): array {
    $cache_key = "ph_top_customers_{$days}_{$limit}";
    $cached    = get_transient( $cache_key );
    if ( $cached !== false ) return $cached;

    global $wpdb;

    $since      = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
    $hpos_table = $wpdb->prefix . 'wc_orders';
    $use_hpos   = $wpdb->get_var( "SHOW TABLES LIKE '{$hpos_table}'" ) === $hpos_table; // phpcs:ignore

    $addr_table = $wpdb->prefix . 'wc_order_addresses';

    if ( $use_hpos ) {
        // HPOS: address data is in wp_wc_order_addresses, not in wp_wc_orders
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT
                oa.email                                          AS email,
                CONCAT(
                    SUBSTRING_INDEX(GROUP_CONCAT(oa.first_name ORDER BY o.date_created_gmt DESC SEPARATOR '|||'), '|||', 1),
                    ' ',
                    SUBSTRING_INDEX(GROUP_CONCAT(oa.last_name  ORDER BY o.date_created_gmt DESC SEPARATOR '|||'), '|||', 1)
                )                                                 AS name,
                SUBSTRING_INDEX(GROUP_CONCAT(oa.city ORDER BY o.date_created_gmt DESC SEPARATOR '|||'), '|||', 1)
                                                                  AS city,
                MAX(o.customer_id)                               AS user_id,
                COUNT(*)                                          AS order_count,
                COALESCE(SUM(o.total_amount), 0)                 AS total_spent,
                MAX(o.date_created_gmt)                          AS last_order_date
             FROM {$hpos_table} o
             LEFT JOIN {$addr_table} oa
                    ON oa.order_id = o.id AND oa.address_type = 'billing'
             WHERE o.type   = 'shop_order'
               AND o.status IN ('wc-completed','wc-processing','wc-on-hold')
               AND o.date_created_gmt >= %s
               AND oa.email != ''
             GROUP BY oa.email
             ORDER BY total_spent DESC
             LIMIT %d",
            $since,
            $limit
        ) );
    } else {
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT
                pm_email.meta_value                                           AS email,
                CONCAT(
                    SUBSTRING_INDEX(GROUP_CONCAT(pm_fn.meta_value ORDER BY p.post_date DESC SEPARATOR '|||'), '|||', 1),
                    ' ',
                    SUBSTRING_INDEX(GROUP_CONCAT(pm_ln.meta_value ORDER BY p.post_date DESC SEPARATOR '|||'), '|||', 1)
                )                                                             AS name,
                SUBSTRING_INDEX(GROUP_CONCAT(pm_city.meta_value ORDER BY p.post_date DESC SEPARATOR '|||'), '|||', 1)
                                                                              AS city,
                MAX(pm_uid.meta_value + 0)                                   AS user_id,
                COUNT(DISTINCT p.ID)                                          AS order_count,
                COALESCE(SUM(pm_total.meta_value + 0), 0)                    AS total_spent,
                MAX(p.post_date)                                              AS last_order_date
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm_email  ON pm_email.post_id  = p.ID AND pm_email.meta_key  = '_billing_email'
             LEFT  JOIN {$wpdb->postmeta} pm_fn     ON pm_fn.post_id     = p.ID AND pm_fn.meta_key     = '_billing_first_name'
             LEFT  JOIN {$wpdb->postmeta} pm_ln     ON pm_ln.post_id     = p.ID AND pm_ln.meta_key     = '_billing_last_name'
             LEFT  JOIN {$wpdb->postmeta} pm_city   ON pm_city.post_id   = p.ID AND pm_city.meta_key   = '_billing_city'
             LEFT  JOIN {$wpdb->postmeta} pm_total  ON pm_total.post_id  = p.ID AND pm_total.meta_key  = '_order_total'
             LEFT  JOIN {$wpdb->postmeta} pm_uid    ON pm_uid.post_id    = p.ID AND pm_uid.meta_key    = '_customer_user'
             WHERE p.post_type   = 'shop_order'
               AND p.post_status IN ('wc-completed','wc-processing','wc-on-hold')
               AND p.post_date  >= %s
               AND pm_email.meta_value != ''
             GROUP BY pm_email.meta_value
             ORDER BY total_spent DESC
             LIMIT %d",
            $since,
            $limit
        ) );
    }

    $result = array_map( function( $r ) {
        return [
            'email'          => $r->email,
            'name'           => trim( $r->name ) ?: $r->email,
            'city'           => $r->city ?? '',
            'user_id'        => (int) ( $r->user_id ?? 0 ),
            'order_count'    => (int) $r->order_count,
            'total_spent'    => round( (float) $r->total_spent, 2 ),
            'avg_order'      => $r->order_count > 0 ? round( (float) $r->total_spent / (int) $r->order_count, 2 ) : 0,
            'last_order'     => $r->last_order_date ? ph_date( 'd M Y', strtotime( $r->last_order_date ) ) : '',
        ];
    }, $rows );

    set_transient( $cache_key, $result, 30 * MINUTE_IN_SECONDS );
    return $result;
}

/* ============================================================
   TOP CATEGORIES
   ============================================================ */

function ph_get_top_categories( int $days = 30, int $limit = 10 ): array {
    $cache_key = "ph_top_cats_{$days}_{$limit}";
    $cached    = get_transient( $cache_key );
    if ( $cached !== false ) return $cached;

    global $wpdb;

    $since      = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
    $hpos_table = $wpdb->prefix . 'wc_orders';
    $use_hpos   = $wpdb->get_var( "SHOW TABLES LIKE '{$hpos_table}'" ) === $hpos_table; // phpcs:ignore

    $date_join = $use_hpos
        ? "INNER JOIN {$hpos_table} o ON o.id = oi.order_id AND o.type = 'shop_order' AND o.status IN ('wc-completed','wc-processing') AND o.date_created_gmt >= '{$since}'"
        : "INNER JOIN {$wpdb->posts} p ON p.ID = oi.order_id AND p.post_type = 'shop_order' AND p.post_status IN ('wc-completed','wc-processing') AND p.post_date >= '{$since}'";

    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT t.name AS category_name, t.term_id,
                SUM(oim_qty.meta_value + 0) AS qty,
                SUM(oim_total.meta_value + 0) AS revenue
         FROM {$wpdb->prefix}woocommerce_order_items oi
         {$date_join}
         INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_prod
                ON oim_prod.order_item_id = oi.order_item_id AND oim_prod.meta_key = '_product_id'
         INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_qty
                ON oim_qty.order_item_id = oi.order_item_id AND oim_qty.meta_key = '_qty'
         INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_total
                ON oim_total.order_item_id = oi.order_item_id AND oim_total.meta_key = '_line_total'
         INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = oim_prod.meta_value
         INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'product_cat'
         INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
         WHERE oi.order_item_type = 'line_item'
         GROUP BY t.term_id
         ORDER BY qty DESC
         LIMIT %d",
        $limit
    ) );

    $result = array_map( fn( $r ) => [
        'name'    => $r->category_name,
        'qty'     => (int) $r->qty,
        'revenue' => round( (float) $r->revenue, 2 ),
    ], $rows ?: [] );

    set_transient( $cache_key, $result, 30 * MINUTE_IN_SECONDS );
    return $result;
}

/* ============================================================
   COUPONS REPORT
   ============================================================ */

function ph_get_coupons_report( int $days = 30, int $limit = 20 ): array {
    $cache_key = "ph_coupons_{$days}_{$limit}";
    $cached    = get_transient( $cache_key );
    if ( $cached !== false ) return $cached;

    global $wpdb;

    $since      = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
    $hpos_table = $wpdb->prefix . 'wc_orders';
    $use_hpos   = $wpdb->get_var( "SHOW TABLES LIKE '{$hpos_table}'" ) === $hpos_table; // phpcs:ignore

    $date_join = $use_hpos
        ? "INNER JOIN {$hpos_table} o ON o.id = oi.order_id AND o.type = 'shop_order' AND o.status IN ('wc-completed','wc-processing','wc-on-hold') AND o.date_created_gmt >= '{$since}'"
        : "INNER JOIN {$wpdb->posts} p ON p.ID = oi.order_id AND p.post_type = 'shop_order' AND p.post_status IN ('wc-completed','wc-processing','wc-on-hold') AND p.post_date >= '{$since}'";

    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT oi.order_item_name AS coupon_code,
                COUNT(DISTINCT oi.order_id) AS order_count,
                COALESCE(SUM(oim.meta_value + 0), 0) AS discount_amount
         FROM {$wpdb->prefix}woocommerce_order_items oi
         {$date_join}
         LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim
                ON oim.order_item_id = oi.order_item_id AND oim.meta_key = 'discount_amount'
         WHERE oi.order_item_type = 'coupon'
         GROUP BY oi.order_item_name
         ORDER BY discount_amount DESC
         LIMIT %d",
        $limit
    ) );

    $result = array_map( fn( $r ) => [
        'code'            => $r->coupon_code,
        'order_count'     => (int) $r->order_count,
        'discount_amount' => round( (float) $r->discount_amount, 2 ),
    ], $rows ?: [] );

    set_transient( $cache_key, $result, 30 * MINUTE_IN_SECONDS );
    return $result;
}

/* ============================================================
   DAILY PRODUCTS REPORT
   ============================================================ */

function ph_get_daily_products_report( string $date = '' ): array {
    if ( ! $date ) {
        $date = gmdate( 'Y-m-d' );
    }
    $date = sanitize_text_field( $date );

    $cache_key = "ph_daily_prod_{$date}";
    $cached    = get_transient( $cache_key );
    if ( $cached !== false ) return $cached;

    global $wpdb;

    $hpos_table = $wpdb->prefix . 'wc_orders';
    $use_hpos   = $wpdb->get_var( "SHOW TABLES LIKE '{$hpos_table}'" ) === $hpos_table; // phpcs:ignore

    $date_from = $date . ' 00:00:00';
    $date_to   = $date . ' 23:59:59';

    if ( $use_hpos ) {
        $rows = $wpdb->get_results( $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT oi.order_item_name AS name,
                    SUM(oim_qty.meta_value + 0) AS qty,
                    SUM(oim_total.meta_value + 0) AS revenue
             FROM {$wpdb->prefix}woocommerce_order_items oi
             INNER JOIN {$hpos_table} o ON o.id = oi.order_id
                AND o.type = 'shop_order'
                AND o.status IN ('wc-completed','wc-processing','wc-on-hold')
                AND o.date_created_gmt >= %s AND o.date_created_gmt <= %s
             INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_qty
                ON oim_qty.order_item_id = oi.order_item_id AND oim_qty.meta_key = '_qty'
             INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_total
                ON oim_total.order_item_id = oi.order_item_id AND oim_total.meta_key = '_line_total'
             WHERE oi.order_item_type = 'line_item'
             GROUP BY oi.order_item_name
             ORDER BY qty DESC",
            $date_from,
            $date_to
        ) );
    } else {
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT oi.order_item_name AS name,
                    SUM(oim_qty.meta_value + 0) AS qty,
                    SUM(oim_total.meta_value + 0) AS revenue
             FROM {$wpdb->prefix}woocommerce_order_items oi
             INNER JOIN {$wpdb->posts} p ON p.ID = oi.order_id
                AND p.post_type = 'shop_order'
                AND p.post_status IN ('wc-completed','wc-processing','wc-on-hold')
                AND p.post_date >= %s AND p.post_date <= %s
             INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_qty
                ON oim_qty.order_item_id = oi.order_item_id AND oim_qty.meta_key = '_qty'
             INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_total
                ON oim_total.order_item_id = oi.order_item_id AND oim_total.meta_key = '_line_total'
             WHERE oi.order_item_type = 'line_item'
             GROUP BY oi.order_item_name
             ORDER BY qty DESC",
            $date_from,
            $date_to
        ) );
    }

    // Total turnover of that day
    $day_total = (float) array_sum( array_map( fn( $r ) => (float) $r->revenue, $rows ?: [] ) );

    $result = [
        'date'      => $date,
        'products'  => array_map( fn( $r ) => [
            'name'    => $r->name,
            'qty'     => (int) $r->qty,
            'revenue' => round( (float) $r->revenue, 2 ),
        ], $rows ?: [] ),
        'day_total' => round( $day_total, 2 ),
    ];

    // Cache 5 minutes for today, 30 minutes for earlier days
    $ttl = ( $date === gmdate( 'Y-m-d' ) ) ? 5 * MINUTE_IN_SECONDS : 30 * MINUTE_IN_SECONDS;
    set_transient( $cache_key, $result, $ttl );
    return $result;
}

/* ============================================================
   CSV EXPORT
   ============================================================ */

/**
 * Export filtered orders to CSV and send as a download.
 */
function ph_export_csv( array $args = [] ): void {
    $query_args = [
        'limit'   => 2000,
        'orderby' => 'date',
        'order'   => 'DESC',
        'return'  => 'objects',
        'type'    => 'shop_order', // No refund objects included
    ];

    $status = sanitize_key( $args['status'] ?? 'any' );
    if ( $status !== 'any' && $status !== '' ) {
        $query_args['status'] = 'wc-' . ltrim( $status, 'wc-' );
    } else {
        // Only real orders, no refunds
        $query_args['status'] = array_keys( wc_get_order_statuses() );
    }

    $days = absint( $args['days'] ?? 0 );
    if ( $days > 0 ) {
        $query_args['date_created'] = '>' . strtotime( "-{$days} days" );
    }

    $search = sanitize_text_field( $args['search'] ?? '' );
    if ( $search !== '' ) {
        if ( is_numeric( $search ) ) {
            $query_args['id'] = (int) $search;
        } else {
            $query_args['customer'] = $search;
        }
    }

    $wc_orders = wc_get_orders( $query_args );

    // Only real shop_orders (extra safety)
    $wc_orders = array_filter( $wc_orders, fn( $o ) => $o instanceof WC_Order && ! ( $o instanceof WC_Order_Refund ) );

    $columns = (array) ph_get_option( 'export_columns', [ 'id', 'date', 'status', 'customer', 'total', 'items' ] );
    // Always add products column if not already present
    if ( ! in_array( 'products', $columns, true ) ) {
        $columns[] = 'products';
    }

    $header_map = [
        'id'          => 'Order ID',
        'date'        => 'Datum',
        'status'      => 'Status',
        'customer'    => 'Klant',
        'email'       => 'E-mail',
        'city'        => 'Stad',
        'total'       => 'Totaal (incl. BTW)',
        'subtotal'    => 'Subtotaal',
        'tax'         => 'BTW',
        'shipping'    => 'Verzendkosten',
        'discount'    => 'Korting',
        'items'       => 'Aantal producten',
        'products'    => 'Producten',
        'payment'     => 'Betaalmethode',
    ];

    $headers = array_values( array_filter(
        array_map( fn( $c ) => $header_map[ $c ] ?? null, $columns ),
        fn( $h ) => $h !== null
    ) );

    if ( ob_get_level() ) {
        ob_end_clean();
    }

    $filename = 'orders-' . gmdate( 'Y-m-d' ) . ( $days ? "-{$days}d" : '' ) . '.csv';

    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
    header( 'Cache-Control: no-cache, no-store, must-revalidate' );
    header( 'Pragma: no-cache' );
    header( 'Expires: 0' );

    $out = fopen( 'php://output', 'w' );
    fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- BOM for Excel, streaming output

    // Report header
    fputcsv( $out, [ 'Product Haven Export', gmdate( 'd-m-Y H:i' ), 'Period: ' . ( $days ? "{$days} days" : 'all' ) ], ';' );
    fputcsv( $out, [], ';' ); // empty separator line
    fputcsv( $out, $headers, ';' );

    foreach ( $wc_orders as $order ) {
        if ( ! $order instanceof WC_Order ) continue;

        // Product List: "Product Name × Qty; Product Name × Qty"
        $product_parts = [];
        foreach ( $order->get_items() as $item ) {
            /** @var WC_Order_Item_Product $item */
            $product_parts[] = $item->get_name() . ' ×' . $item->get_quantity();
        }
        $products_str = implode( ' | ', $product_parts );

        $row = [];
        foreach ( $columns as $col ) {
            $row[] = match ( $col ) {
                'id'       => $order->get_id(),
                'date'     => $order->get_date_created() ? ph_date( 'd-m-Y H:i', $order->get_date_created()->getTimestamp() ) : '',
                'status'   => ph_t( 'wc_status_' . str_replace( '-', '_', $order->get_status() ) ),
                'customer' => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
                'email'    => $order->get_billing_email(),
                'city'     => trim( $order->get_billing_city() . ( $order->get_billing_country() ? ' (' . $order->get_billing_country() . ')' : '' ) ),
                'total'    => number_format( (float) $order->get_total(),            2, ',', '.' ),
                'subtotal' => number_format( (float) $order->get_subtotal(),         2, ',', '.' ),
                'tax'      => number_format( (float) $order->get_total_tax(),        2, ',', '.' ),
                'shipping' => number_format( (float) $order->get_shipping_total(),   2, ',', '.' ),
                'discount' => number_format( (float) $order->get_total_discount(),   2, ',', '.' ),
                'items'    => $order->get_item_count(),
                'products' => $products_str,
                'payment'  => $order->get_payment_method_title(),
                default    => '',
            };
        }
        fputcsv( $out, $row, ';' );
    }

    // Summary at the bottom
    $total_revenue = array_sum( array_map( fn( $o ) => $o instanceof WC_Order ? (float) $o->get_total() : 0, $wc_orders ) );
    fputcsv( $out, [], ';' );
    fputcsv( $out, [ 'Totaal orders:', count( $wc_orders ), '', '', '', '', number_format( $total_revenue, 2, ',', '.' ) ], ';' );

    fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- streaming output
    exit;
}

/* ============================================================
   STOCK — STOCK SENTINEL INTEGRATION
   ============================================================ */

/**
 * Retrieve all products with stock, filtered and sorted.
 */
function ph_stock_get( array $args = [] ): array {
    $defaults = [
        'status'   => 'all',
        'search'   => '',
        'orderby'  => 'stock',
        'order'    => 'ASC',
        'page'     => 1,
        'per_page' => 20,
    ];
    $args = wp_parse_args( $args, $defaults );

    $threshold = (int) ph_stock_get_option( 'low_stock_threshold', 5 );

    $query_args = [
        'post_type'      => [ 'product', 'product_variation' ],
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => [ [ 'key' => '_manage_stock', 'value' => 'yes', 'compare' => '=' ] ],
        'fields'         => 'ids',
    ];

    if ( ! empty( $args['search'] ) ) {
        $query_args['s'] = sanitize_text_field( $args['search'] );
    }

    $product_ids = get_posts( $query_args );

    $products = [];
    foreach ( $product_ids as $id ) {
        $product = wc_get_product( $id );
        if ( ! $product || ! $product->managing_stock() ) continue;

        $stock  = (int) $product->get_stock_quantity();
        $status = ph_stock_status( $stock, $threshold );

        if ( $args['status'] !== 'all' && $status !== $args['status'] ) continue;

        $parent = $product->get_parent_id() ? wc_get_product( $product->get_parent_id() ) : null;
        $name   = $parent
            ? $parent->get_name() . ' — ' . implode( ', ', $product->get_variation_attributes() )
            : $product->get_name();

        $products[] = [
            'id'          => $id,
            'name'        => $name,
            'sku'         => $product->get_sku() ?: '–',
            'stock'       => $stock,
            'status'      => $status,
            'price'       => (float) $product->get_price(),
            'price_html'  => $product->get_price_html(),
            'edit_url'    => get_edit_post_link( $product->get_parent_id() ?: $id, 'raw' ),
            'image'       => wp_get_attachment_image_url( $product->get_image_id(), [ 48, 48 ] ) ?: wc_placeholder_img_src( [ 48, 48 ] ),
            'stock_value' => round( $stock * (float) $product->get_price(), 2 ),
        ];
    }

    // Sorting
    $orderby = in_array( $args['orderby'], [ 'stock', 'title', 'sku', 'price', 'stock_value' ], true )
        ? $args['orderby'] : 'stock';
    usort( $products, function ( $a, $b ) use ( $orderby, $args ) {
        $va = $a[ $orderby ] ?? $a['stock'];
        $vb = $b[ $orderby ] ?? $b['stock'];
        $cmp = is_string( $va ) ? strcmp( $va, $vb ) : ( $va <=> $vb );
        return $args['order'] === 'DESC' ? -$cmp : $cmp;
    } );

    $total  = count( $products );
    $offset = ( $args['page'] - 1 ) * $args['per_page'];
    $paged  = array_slice( $products, $offset, $args['per_page'] );

    $summary = [
        'total_products' => $total,
        'out_of_stock'   => count( array_filter( $products, fn( $p ) => $p['status'] === 'out' ) ),
        'low_stock'      => count( array_filter( $products, fn( $p ) => $p['status'] === 'low' ) ),
        'ok_stock'       => count( array_filter( $products, fn( $p ) => $p['status'] === 'ok' ) ),
        'total_value'    => round( array_sum( array_column( $products, 'stock_value' ) ), 2 ),
    ];

    return [
        'products'    => $paged,
        'total'       => $total,
        'total_pages' => (int) ceil( $total / max( 1, $args['per_page'] ) ),
        'page'        => $args['page'],
        'summary'     => $summary,
        'threshold'   => $threshold,
    ];
}

function ph_stock_status( int $stock, int $threshold ): string {
    if ( $stock <= 0 )          return 'out';
    if ( $stock <= $threshold ) return 'low';
    return 'ok';
}

function ph_stock_get_option( string $key, $default = '' ) {
    $opts = get_option( 'ph_stock_options', [] );
    return $opts[ $key ] ?? $default;
}

function ph_stock_update( int $product_id, int $new_stock, string $reason = 'Handmatig bijgewerkt' ): array {
    $product = wc_get_product( $product_id );
    if ( ! $product ) return [ 'success' => false, 'message' => 'Product niet gevonden.' ];

    $old_stock = (int) $product->get_stock_quantity();

    // Use WC own function so all WC hooks are fired correctly
    wc_update_product_stock( $product, $new_stock, 'set' );

    ph_stock_log_change( $product_id, $old_stock, $new_stock, $reason );

    // Direct alert after manual change (bypass cooldown)
    $threshold = (int) ph_stock_get_option( 'low_stock_threshold', 5 );
    $statuses  = (array) ph_stock_get_option( 'alert_statuses', [ 'low', 'out' ] );
    $status    = ph_stock_status( $new_stock, $threshold );
    $opts      = get_option( 'ph_stock_options', [] );

    if ( ( $opts['realtime_alerts'] ?? '1' ) === '1' && in_array( $status, $statuses, true ) ) {
        // Clear cooldown so the hook that just fired is not blocked
        delete_transient( 'ph_stock_alert_' . $product_id );
    }

    return [ 'success' => true, 'product_id' => $product_id, 'old_stock' => $old_stock, 'new_stock' => $new_stock ];
}

function ph_stock_log_change( int $product_id, int $old_stock, int $new_stock, string $reason = '' ): void {
    global $wpdb;
    $table = $wpdb->prefix . 'ph_stock_log';
    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) !== $table ) return; // phpcs:ignore
    $wpdb->insert( $table, [
        'product_id' => $product_id,
        'old_stock'  => $old_stock,
        'new_stock'  => $new_stock,
        'change'     => $new_stock - $old_stock,
        'reason'     => sanitize_text_field( $reason ),
        'user_id'    => get_current_user_id(),
        'created_at' => current_time( 'mysql', true ),
    ], [ '%d', '%d', '%d', '%d', '%s', '%d', '%s' ] );
}

function ph_stock_create_table(): void {
    global $wpdb;
    $table   = $wpdb->prefix . 'ph_stock_log';
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        product_id  BIGINT UNSIGNED NOT NULL,
        old_stock   INT             NOT NULL DEFAULT 0,
        new_stock   INT             NOT NULL DEFAULT 0,
        `change`    INT             NOT NULL DEFAULT 0,
        reason      VARCHAR(255)    NOT NULL DEFAULT '',
        user_id     BIGINT UNSIGNED NOT NULL DEFAULT 0,
        created_at  DATETIME        NOT NULL,
        PRIMARY KEY (id),
        KEY product_id (product_id),
        KEY created_at (created_at)
    ) {$charset};";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

function ph_stock_export_csv(): void {
    $all = ph_stock_get( [ 'per_page' => 9999, 'status' => 'all' ] );
    if ( ob_get_level() ) ob_end_clean();
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="product-haven-stock-' . gmdate( 'Y-m-d' ) . '.csv"' );
    header( 'Cache-Control: no-cache, no-store, must-revalidate' );
    $out = fopen( 'php://output', 'w' );
    fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- streaming output
    fputcsv( $out, [ 'Product', 'SKU', 'Voorraad', 'Status', 'Prijs', 'Voorraadwaarde', 'Bewerken' ] );
    foreach ( $all['products'] as $p ) {
        fputcsv( $out, [ $p['name'], $p['sku'], $p['stock'], ucfirst( $p['status'] ), $p['price'], $p['stock_value'], $p['edit_url'] ] );
    }
    fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- streaming output
    exit;
}

function ph_stock_send_alert( bool $force_test = false ): bool {
    $threshold = (int) ph_stock_get_option( 'low_stock_threshold', 5 );
    $email     = sanitize_email( ph_stock_get_option( 'alert_email', get_option( 'admin_email' ) ) );
    $statuses  = (array) ph_stock_get_option( 'alert_statuses', [ 'low', 'out' ] );

    if ( ! is_email( $email ) ) return false;

    $all      = ph_stock_get( [ 'per_page' => 9999, 'status' => 'all' ] );
    $alert_ps = array_filter( $all['products'], fn( $p ) => in_array( $p['status'], $statuses, true ) );

    if ( empty( $alert_ps ) && ! $force_test ) return true;

    $site_name = get_bloginfo( 'name' );
    $subject   = $force_test
        ? sprintf( '[%s] Product Haven — Test voorraad alert', $site_name )
        : sprintf( '[%s] Product Haven — %d producten vereisen aandacht', $site_name, count( $alert_ps ) );

    $message = ph_stock_build_email( array_values( $alert_ps ), $threshold, $force_test );
    $headers = [ 'Content-Type: text/html; charset=UTF-8', 'From: ' . $site_name . ' <' . get_option( 'admin_email' ) . '>' ];

    return wp_mail( $email, $subject, $message, $headers );
}

function ph_stock_check_single( $product ): void {
    if ( ! is_object( $product ) ) return;

    $threshold = (int) ph_stock_get_option( 'low_stock_threshold', 5 );
    $statuses  = (array) ph_stock_get_option( 'alert_statuses', [ 'low', 'out' ] );

    // Stock retrieval — also products without manage_stock but still outofstock
    if ( $product->get_manage_stock() ) {
        $stock  = (int) $product->get_stock_quantity();
        $status = ph_stock_status( $stock, $threshold );
    } else {
        $wc_status = $product->get_stock_status();
        $stock     = 0;
        $status    = ( $wc_status === 'outofstock' ) ? 'out' : 'ok';
    }

    if ( ! in_array( $status, $statuses, true ) ) return;

    $email = sanitize_email( ph_stock_get_option( 'alert_email', get_option( 'admin_email' ) ) );
    if ( ! is_email( $email ) ) return;

    $product_id = $product->get_id();
    $site_name  = get_bloginfo( 'name' );
    $name       = $product->get_name();
    $sku        = $product->get_sku() ?: '–';
    $edit_url   = get_edit_post_link( $product->is_type( 'variation' ) ? $product->get_parent_id() : $product_id, 'raw' );

    $subject = sprintf( '[%s] Voorraad alert: %s (%d stuks)', $site_name, $name, $stock );
    $p_data  = [ [ 'name' => $name, 'sku' => $sku, 'stock' => $stock, 'status' => $status, 'edit_url' => $edit_url ] ];
    $message = ph_stock_build_email( $p_data, $threshold, false );
    $headers = [ 'Content-Type: text/html; charset=UTF-8', 'From: ' . $site_name . ' <' . get_option( 'admin_email' ) . '>' ];

    wp_mail( $email, $subject, $message, $headers );
}

function ph_stock_build_email( array $products, int $threshold, bool $is_test ): string {
    $site_name = get_bloginfo( 'name' );
    $site_url  = get_site_url();
    $dashboard = admin_url( 'admin.php?page=product-haven' );

    $statuses    = array_column( $products, 'status' );
    $has_out     = in_array( 'out', $statuses, true );
    $has_low     = in_array( 'low', $statuses, true );
    if ( $has_out ) {
        $header_grad = 'linear-gradient(135deg,#EF4444,#DC2626)';
        $cta_color   = '#DC2626';
    } elseif ( $has_low ) {
        $header_grad = 'linear-gradient(135deg,#F59E0B,#D97706)';
        $cta_color   = '#D97706';
    } else {
        $header_grad = 'linear-gradient(135deg,#10B981,#059669)';
        $cta_color   = '#10B981';
    }

    ob_start();
    ?><!DOCTYPE html><html lang="nl"><head><meta charset="UTF-8"><style>
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#F8FAFC;margin:0;padding:0}
.wrap{max-width:600px;margin:32px auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08)}
.header{background:<?php echo esc_attr( $header_grad ); ?>;padding:28px 32px}
.header h1{margin:0;color:#fff;font-size:22px;font-weight:800}
.header p{margin:4px 0 0;color:rgba(255,255,255,.8);font-size:13px}
.body{padding:28px 32px}
.test-badge{background:#D1FAE5;border:1.5px solid #10B981;color:#065F46;padding:8px 16px;border-radius:8px;font-size:13px;margin-bottom:20px}
.product-row{display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid #E2E8F0}
.product-row:last-child{border-bottom:none}
.badge{padding:3px 10px;border-radius:6px;font-size:12px;font-weight:700;text-transform:uppercase}
.badge-out{background:#FEE2E2;color:#991B1B}.badge-low{background:#FEF3C7;color:#92400E}
.product-name{font-weight:600;font-size:14px;color:#0F172A}
.product-meta{font-size:12px;color:#64748B;margin-top:2px}
.stock-num{font-size:22px;font-weight:800}
.stock-num-out{color:#DC2626}.stock-num-low{color:#D97706}
.cta{display:inline-block;margin-top:24px;background:<?php echo esc_attr( $cta_color ); ?>;color:#fff;padding:12px 24px;border-radius:10px;font-weight:700;text-decoration:none;font-size:14px}
.footer{padding:16px 32px;background:#F8FAFC;font-size:12px;color:#94A3B8;border-top:1px solid #E2E8F0}
</style></head><body><div class="wrap">
<div class="header"><h1>📦 Product Haven — Voorraad</h1><p><?php echo esc_html( $site_name ); ?> — Voorraad alert</p></div>
<div class="body">
<?php if ( $is_test ) : ?><div class="test-badge">⚠️ Dit is een test-e-mail.</div><?php endif; ?>
<p style="color:#64748B;font-size:14px;margin:0 0 20px"><?php
if ( empty( $products ) ) { echo 'Geen producten met lage of nul voorraad gevonden.'; }
else { echo esc_html( sprintf( '%d %s vereist%s je aandacht (drempel: %d stuks).', count( $products ), count( $products ) === 1 ? 'product' : 'producten', count( $products ) === 1 ? '' : 'en', $threshold ) ); }
?></p>
<?php foreach ( $products as $p ) : ?><div class="product-row"><div><div class="product-name"><?php echo esc_html( $p['name'] ); ?></div><div class="product-meta">SKU: <?php echo esc_html( $p['sku'] ); ?></div></div><div style="text-align:right"><div class="stock-num stock-num-<?php echo esc_attr( $p['status'] ); ?>"><?php echo (int) $p['stock']; ?></div><span class="badge badge-<?php echo esc_attr( $p['status'] ); ?>"><?php echo $p['status'] === 'out' ? 'Uitverkocht' : 'Lage voorraad'; ?></span></div></div><?php endforeach; ?>
<a href="<?php echo esc_url( $dashboard ); ?>" class="cta">Ga naar Product Haven →</a>
</div><div class="footer">Automatisch verstuurd door Product Haven op <?php echo esc_html( $site_url ); ?></div>
</div></body></html><?php
    return ob_get_clean();
}
