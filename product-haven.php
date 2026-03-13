<?php
/**
 * Plugin Name:       Product Haven
 * Plugin URI:        https://odinwattez.nl/product-haven/
 * Description:       All-in-one WooCommerce management: orders, stock, products & sequential order numbers — with Elementor widgets.
 * Version:           1.3.2
 * Author:            Odin Wattez
 * Author URI:        https://odinwattez.nl/
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       product-haven
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 */

defined( 'ABSPATH' ) || exit;

// All functions in this file use the ph_ plugin prefix.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound

// ---------- CONSTANTS ----------
define( 'PH_PATH',    plugin_dir_path( __FILE__ ) );
define( 'PH_URL',     plugin_dir_url( __FILE__ ) );
define( 'PH_VERSION', '1.3.2' );
define( 'PH_SLUG',    'product-haven' );

// ---------- LOAD FILES ----------
require_once PH_PATH . 'includes/i18n.php';              // Language / i18n helper (NL ↔ EN)
require_once PH_PATH . 'includes/admin/admin-page.php';
require_once PH_PATH . 'includes/sequential-orders.php'; // Sequential Orders logic (hooks + helpers)

// Only load REST API when enabled, and only on rest_api_init
add_action( 'rest_api_init', function () {
    if ( ph_get_option( 'enable_rest_api' ) === '1' ) {
        require_once PH_PATH . 'includes/api/rest-api.php';
    }
} );

// ---------- DASHBOARD INTEGRATION ----------
add_action( 'ph_dashboard_register_addons', function () {
    if ( ! function_exists( 'ph_dashboard_register_addon' ) ) return;
    ph_dashboard_register_addon( [
        'id'         => PH_SLUG,
        'page_title' => __( 'Product Haven', 'product-haven' ),
        'menu_title' => __( 'Product Haven', 'product-haven' ),
        'menu_slug'  => 'product-haven',
        'capability' => 'manage_woocommerce',
        'callback'   => 'ph_render_admin_page',
        'position'   => 30,
    ] );
} );

add_action( 'ph_dashboard_register_items', function () {
    if ( ! function_exists( 'ph_dashboard_register_dashboard_item' ) ) return;
    ph_dashboard_register_dashboard_item( [
        'id'          => PH_SLUG,
        'name'        => __( 'Product Haven', 'product-haven' ),
        'description' => __( 'Live order-statistieken, tijdlijn en klantenkaarten voor jouw webshop.', 'product-haven' ),
        'menu_slug'   => 'product-haven',
        'svg'         => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>',
    ] );
} );

// ---------- ELEMENTOR WIDGETS ----------
add_action( 'elementor/widgets/register', function ( $wm ) {
    if ( ! did_action( 'elementor/loaded' ) ) return;
    require_once PH_PATH . 'includes/widgets/stats-widget.php';
    require_once PH_PATH . 'includes/widgets/timeline-widget.php';
    $wm->register( new \ProductHaven\Stats_Widget() );
    $wm->register( new \ProductHaven\Timeline_Widget() );
} );

add_action( 'elementor/elements/categories_registered', function ( $em ) {
    if ( isset( $em->get_categories()['ph-category'] ) ) return;
    $em->add_category( 'ph-category', [
        'title' => __( 'Product Haven Widgets', 'product-haven' ),
        'icon'  => 'fa fa-plug',
    ] );
} );

// ---------- ADMIN ASSETS ----------
add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( strpos( $hook, 'product-haven' ) === false ) return;
    wp_enqueue_media(); // For Quick Products image/gallery uploader
    wp_enqueue_style(  'ph-admin-css',        PH_URL . 'assets/css/ph-admin.css',        [], PH_VERSION );
    wp_enqueue_style(  'op-sequential-css',   PH_URL . 'assets/css/sequential-orders.css', [], PH_VERSION );
    wp_enqueue_script( 'ph-chart-js',  PH_URL . 'assets/js/vendor/chart.umd.min.js', [], '4.4.0', true );
    wp_enqueue_script( 'ph-admin-js',        PH_URL . 'assets/js/ph-admin.js',        [ 'ph-chart-js' ], PH_VERSION, true );
    wp_enqueue_script( 'op-sequential-js',   PH_URL . 'assets/js/sequential-orders.js', [], PH_VERSION, true );
    $ph_lang = ph_get_lang();
    wp_localize_script( 'ph-admin-js', 'ph_admin', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'ph_admin_nonce' ),
        'currency' => get_woocommerce_currency_symbol(),
        'lang'     => $ph_lang,
        'locale'   => [ 'nl' => 'nl-NL', 'en' => 'en-GB', 'de' => 'de-DE', 'fr' => 'fr-FR', 'es' => 'es-ES' ][ $ph_lang ] ?? 'nl-NL',
        'i18n'     => [
            // General
            'loading_error'          => ph_t( 'loading_error', $ph_lang ),
            'no_orders_found'        => ph_t( 'no_orders_found', $ph_lang ),
            'order_load_error'       => ph_t( 'order_load_error', $ph_lang ),
            'connection_error'       => ph_t( 'connection_error', $ph_lang ),
            'unknown_error'          => ph_t( 'unknown_error', $ph_lang ),
            'unknown_error_short'    => ph_t( 'unknown_error_short', $ph_lang ),
            'error_prefix'           => ph_t( 'error_prefix', $ph_lang ),
            'failed_prefix'          => ph_t( 'failed_prefix', $ph_lang ),
            'saving'                 => ph_t( 'saving', $ph_lang ),
            'deleting'               => ph_t( 'deleting', $ph_lang ),
            'processing'             => ph_t( 'processing', $ph_lang ),
            'sending'                => ph_t( 'sending', $ph_lang ),
            'loading'                => ph_t( 'loading', $ph_lang ),
            // Low stock card
            'all_stock_ok'           => ph_t( 'all_stock_ok', $ph_lang ),
            'edit_stock_title'       => ph_t( 'edit_stock_title', $ph_lang ),
            'out_of_stock_short'     => ph_t( 'out_of_stock_short', $ph_lang ),
            'remaining'              => ph_t( 'remaining', $ph_lang ),
            'no_data'                => ph_t( 'no_data', $ph_lang ),
            'refunded_prefix'        => ph_t( 'refunded_prefix', $ph_lang ),
            // Chart
            'chart_revenue'          => ph_t( 'chart_revenue', $ph_lang ),
            'chart_orders'           => ph_t( 'chart_orders', $ph_lang ),
            'chart_orders_suffix'    => ph_t( 'chart_orders_suffix', $ph_lang ),
            // Timeline
            'time_just_now'          => ph_t( 'time_just_now', $ph_lang ),
            'time_min_ago'           => ph_t( 'time_min_ago', $ph_lang ),
            'time_hour_ago'          => ph_t( 'time_hour_ago', $ph_lang ),
            'time_hours_ago'         => ph_t( 'time_hours_ago', $ph_lang ),
            'time_yesterday'         => ph_t( 'time_yesterday', $ph_lang ),
            'time_days_ago'          => ph_t( 'time_days_ago', $ph_lang ),
            'time_weeks_ago'         => ph_t( 'time_weeks_ago', $ph_lang ),
            'note_just_now_you'      => ph_t( 'note_just_now_you', $ph_lang ),
            // Order modal
            'modal_customer'         => ph_t( 'modal_customer', $ph_lang ),
            'modal_name'             => ph_t( 'modal_name', $ph_lang ),
            'modal_email_label'      => ph_t( 'modal_email_label', $ph_lang ),
            'modal_phone_label'      => ph_t( 'modal_phone_label', $ph_lang ),
            'modal_address'          => ph_t( 'modal_address', $ph_lang ),
            'modal_payment'          => ph_t( 'modal_payment', $ph_lang ),
            'modal_date'             => ph_t( 'modal_date', $ph_lang ),
            'modal_total'            => ph_t( 'modal_total', $ph_lang ),
            'modal_products'         => ph_t( 'modal_products', $ph_lang ),
            'modal_order_notes'      => ph_t( 'modal_order_notes', $ph_lang ),
            'modal_no_notes'         => ph_t( 'modal_no_notes', $ph_lang ),
            'modal_note_placeholder' => ph_t( 'modal_note_placeholder', $ph_lang ),
            'modal_note_add'         => ph_t( 'modal_note_add', $ph_lang ),
            'modal_change_status'    => ph_t( 'modal_change_status', $ph_lang ),
            'modal_complete'         => ph_t( 'modal_complete', $ph_lang ),
            'modal_processing'       => ph_t( 'modal_processing', $ph_lang ),
            'modal_on_hold'          => ph_t( 'modal_on_hold', $ph_lang ),
            'modal_cancel'           => ph_t( 'modal_cancel', $ph_lang ),
            'modal_failed'           => ph_t( 'modal_failed', $ph_lang ),
            'modal_refund'           => ph_t( 'modal_refund', $ph_lang ),
            'modal_revert_refund'    => ph_t( 'modal_revert_refund', $ph_lang ),
            'modal_danger_zone'      => ph_t( 'modal_danger_zone', $ph_lang ),
            'modal_delete_order'     => ph_t( 'modal_delete_order', $ph_lang ),
            'modal_open_customer'    => ph_t( 'modal_open_customer', $ph_lang ),
            'modal_irreversible'     => ph_t( 'modal_irreversible', $ph_lang ),
            'modal_delete_confirm'   => ph_t( 'modal_delete_confirm', $ph_lang ),
            // Order edit modal
            'order_edit_save'        => ph_t( 'order_edit_save', $ph_lang ),
            'order_edit_saving'      => ph_t( 'order_edit_saving', $ph_lang ),
            // Refund modal
            'refund_modal_title'     => ph_t( 'refund_modal_title', $ph_lang ),
            'revert_modal_title'     => ph_t( 'revert_modal_title', $ph_lang ),
            'refund_modal_desc'      => ph_t( 'refund_modal_desc', $ph_lang ),
            'revert_modal_desc'      => ph_t( 'revert_modal_desc', $ph_lang ),
            'refund_confirm_btn'     => ph_t( 'refund_confirm_btn', $ph_lang ),
            // Delete confirm
            'delete_confirm_ok'      => ph_t( 'delete_confirm_ok', $ph_lang ),
            'delete_confirm_ok_busy' => ph_t( 'delete_confirm_ok_busy', $ph_lang ),
            'delete_error_prefix'    => ph_t( 'delete_error_prefix', $ph_lang ),
            'delete_unknown_error'   => ph_t( 'delete_unknown_error', $ph_lang ),
            // Customer card
            'customer_not_found'     => ph_t( 'customer_not_found', $ph_lang ),
            'customer_since'         => ph_t( 'customer_since', $ph_lang ),
            'customer_total_spent'   => ph_t( 'customer_total_spent', $ph_lang ),
            'customer_avg_order'     => ph_t( 'customer_avg_order', $ph_lang ),
            'customer_orders_history'=> ph_t( 'customer_orders_history', $ph_lang ),
            'customer_orders_none'   => ph_t( 'customer_orders_none', $ph_lang ),
            'customer_col_order'     => ph_t( 'customer_col_order', $ph_lang ),
            'customer_col_date'      => ph_t( 'customer_col_date', $ph_lang ),
            'customer_col_status'    => ph_t( 'customer_col_status', $ph_lang ),
            'customer_col_amount'    => ph_t( 'customer_col_amount', $ph_lang ),
            // Revenue report
            'rev_gross'              => ph_t( 'rev_gross', $ph_lang ),
            'rev_refunds'            => ph_t( 'rev_refunds', $ph_lang ),
            'rev_coupons'            => ph_t( 'rev_coupons', $ph_lang ),
            'rev_net'                => ph_t( 'rev_net', $ph_lang ),
            'rev_taxes'              => ph_t( 'rev_taxes', $ph_lang ),
            'rev_shipping'           => ph_t( 'rev_shipping', $ph_lang ),
            'rev_total'              => ph_t( 'rev_total', $ph_lang ),
            // Categories report
            'cat_col_category'       => ph_t( 'cat_col_category', $ph_lang ),
            'cat_col_items'          => ph_t( 'cat_col_items', $ph_lang ),
            'cat_col_revenue'        => ph_t( 'cat_col_revenue', $ph_lang ),
            'cat_no_data'            => ph_t( 'cat_no_data', $ph_lang ),
            // Coupons report
            'coupon_col_code'        => ph_t( 'coupon_col_code', $ph_lang ),
            'coupon_col_used'        => ph_t( 'coupon_col_used', $ph_lang ),
            'coupon_col_discount'    => ph_t( 'coupon_col_discount', $ph_lang ),
            'coupon_no_data'         => ph_t( 'coupon_no_data', $ph_lang ),
            // Daily report
            'daily_date_label'       => ph_t( 'daily_date_label', $ph_lang ),
            'daily_total_label'      => ph_t( 'daily_total_label', $ph_lang ),
            'daily_products_label'   => ph_t( 'daily_products_label', $ph_lang ),
            'daily_col_product'      => ph_t( 'daily_col_product', $ph_lang ),
            'daily_col_qty'          => ph_t( 'daily_col_qty', $ph_lang ),
            'daily_col_revenue'      => ph_t( 'daily_col_revenue', $ph_lang ),
            'daily_no_sales'         => ph_t( 'daily_no_sales', $ph_lang ),
            // Customers report
            'cust_col_customer'      => ph_t( 'cust_col_customer', $ph_lang ),
            'cust_col_orders'        => ph_t( 'cust_col_orders', $ph_lang ),
            'cust_col_avg'           => ph_t( 'cust_col_avg', $ph_lang ),
            'cust_col_last'          => ph_t( 'cust_col_last', $ph_lang ),
            'cust_col_total'         => ph_t( 'cust_col_total', $ph_lang ),
            'cust_no_data'           => ph_t( 'cust_no_data', $ph_lang ),
            // Stock table
            'stock_edit_btn'         => ph_t( 'stock_edit_btn', $ph_lang ),
            'stock_out_short'        => ph_t( 'stock_out_short', $ph_lang ),
            'stock_low_short'        => ph_t( 'stock_low_short', $ph_lang ),
            'stock_ok_short'         => ph_t( 'stock_ok_short', $ph_lang ),
            'stock_modal_title_prefix'  => ph_t( 'stock_modal_title_prefix', $ph_lang ),
            'stock_modal_title_default' => ph_t( 'stock_modal_title_default', $ph_lang ),
            'stock_updated'          => ph_t( 'stock_updated', $ph_lang ),
            'stock_saved'            => ph_t( 'stock_saved', $ph_lang ),
            'stock_test_sent'        => ph_t( 'stock_test_sent', $ph_lang ),
            'stock_n_selected'       => ph_t( 'stock_n_selected', $ph_lang ),
            'stock_bulk_prompt'      => ph_t( 'stock_bulk_prompt', $ph_lang ),
            'stock_bulk_reason'      => ph_t( 'stock_bulk_reason', $ph_lang ),
            'stock_total_label'      => ph_t( 'stock_total_label', $ph_lang ),
            // Stock edit (dashboard low stock modal)
            'stock_edit_default_reason' => ph_t( 'stock_edit_default_reason', $ph_lang ),
            'stock_edit_invalid_qty'    => ph_t( 'stock_edit_invalid_qty', $ph_lang ),
            // Quick Products
            'qp_no_products'         => ph_t( 'qp_no_products', $ph_lang ),
            'qp_load_error'          => ph_t( 'qp_load_error', $ph_lang ),
            'qp_published'           => ph_t( 'qp_published', $ph_lang ),
            'qp_draft'               => ph_t( 'qp_draft', $ph_lang ),
            'qp_private'             => ph_t( 'qp_private', $ph_lang ),
            'qp_pending'             => ph_t( 'qp_pending', $ph_lang ),
            'qp_col_product'         => ph_t( 'qp_col_product', $ph_lang ),
            'qp_col_status'          => ph_t( 'qp_col_status', $ph_lang ),
            'qp_col_price'           => ph_t( 'qp_col_price', $ph_lang ),
            'qp_col_stock'           => ph_t( 'qp_col_stock', $ph_lang ),
            'qp_instock'             => ph_t( 'qp_instock', $ph_lang ),
            'qp_outofstock'          => ph_t( 'qp_outofstock', $ph_lang ),
            'qp_onbackorder'         => ph_t( 'qp_onbackorder', $ph_lang ),
            'qp_edit_title'          => ph_t( 'qp_edit_title', $ph_lang ),
            'qp_duplicate_title'     => ph_t( 'qp_duplicate_title', $ph_lang ),
            'qp_wc_edit_title'       => ph_t( 'qp_wc_edit_title', $ph_lang ),
            'qp_delete_title'        => ph_t( 'qp_delete_title', $ph_lang ),
            'qp_sale_prefix'         => ph_t( 'qp_sale_prefix', $ph_lang ),
            'qp_delete_confirm'      => ph_t( 'qp_delete_confirm', $ph_lang ),
            'qp_deleted'             => ph_t( 'qp_deleted', $ph_lang ),
            'qp_duplicated_prefix'   => ph_t( 'qp_duplicated_prefix', $ph_lang ),
            'qp_updated'             => ph_t( 'qp_updated', $ph_lang ),
            'qp_update_error'        => ph_t( 'qp_update_error', $ph_lang ),
            'qp_name_required'       => ph_t( 'qp_name_required', $ph_lang ),
            'qp_saving'              => ph_t( 'qp_saving', $ph_lang ),
            'qp_saved'               => ph_t( 'qp_saved', $ph_lang ),
            'qp_save_error'          => ph_t( 'qp_save_error', $ph_lang ),
            'qp_new_product_label'   => ph_t( 'qp_new_product_label', $ph_lang ),
            'qp_loading_label'       => ph_t( 'qp_loading_label', $ph_lang ),
            'qp_not_found'           => ph_t( 'qp_not_found', $ph_lang ),
            'qp_media_unavailable'   => ph_t( 'qp_media_unavailable', $ph_lang ),
            'qp_media_title'         => ph_t( 'qp_media_title', $ph_lang ),
            'qp_media_btn'           => ph_t( 'qp_media_btn', $ph_lang ),
            'qp_gallery_title'       => ph_t( 'qp_gallery_title', $ph_lang ),
            'qp_gallery_btn'         => ph_t( 'qp_gallery_btn', $ph_lang ),
            'qp_remove_gallery_title'=> ph_t( 'qp_remove_gallery_title', $ph_lang ),
        ],
    ] );
} );

// ---------- FRONT-END ASSETS ----------
add_action( 'wp_enqueue_scripts', 'ph_enqueue_frontend' );
add_action( 'elementor/editor/after_enqueue_scripts', 'ph_enqueue_frontend' );

function ph_enqueue_frontend() {
    wp_enqueue_style(  'ph-front-css', PH_URL . 'assets/css/ph-front.css', [], PH_VERSION );
    wp_enqueue_script( 'ph-chart-front', PH_URL . 'assets/js/vendor/chart.umd.min.js', [], '4.4.0', true );
    wp_enqueue_script( 'ph-front-js',  PH_URL . 'assets/js/ph-front.js', [ 'ph-chart-front' ], PH_VERSION, true );
    wp_localize_script( 'ph-front-js', 'ph_data', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'ph_front_nonce' ),
        'currency' => get_woocommerce_currency_symbol(),
        'i18n'     => [
            'revenue'  => __( 'Omzet', 'product-haven' ),
            'orders'   => __( 'Orders', 'product-haven' ),
            'no_data'  => __( 'Geen data beschikbaar', 'product-haven' ),
        ],
    ] );
}

// ---------- AJAX HANDLERS ----------
// Functions are loaded from includes/ajax.php lazily (only when an AJAX request comes in).

// ----- Language switch -----
add_action( 'wp_ajax_ph_set_lang', function () {
    check_ajax_referer( 'ph_admin_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error( [], 403 );
    $lang = in_array( $_POST['lang'] ?? '', [ 'nl', 'en', 'de', 'fr', 'es' ], true )
        ? sanitize_key( $_POST['lang'] )
        : 'nl';
    update_user_meta( get_current_user_id(), 'ph_lang', $lang );
    wp_send_json_success( [ 'lang' => $lang ] );
} );
add_action( 'wp_ajax_ph_get_stats',              'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_get_timeline',           'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_get_chart_data',         'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_export_csv',             'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_get_customer',           'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_get_single_order',       'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_update_order_note',      'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_update_order_status',    'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_delete_order',           'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_process_refund',         'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_revert_refund',          'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_get_top_products',       'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_get_revenue_report',     'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_get_top_categories',     'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_get_coupons_report',     'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_get_daily_products',     'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_get_top_customers',      'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_front_stats',            'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_front_timeline',         'ph_ajax_lazy_load' );
add_action( 'wp_ajax_nopriv_ph_front_stats',     'ph_ajax_lazy_load' );
add_action( 'wp_ajax_nopriv_ph_front_timeline',  'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_get_low_stock',          'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_update_stock',           'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_stock_get',              'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_stock_update',           'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_stock_export_csv',       'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_stock_save_settings',    'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_stock_send_test_alert',  'ph_ajax_lazy_load' );
// ---------- QUICK PRODUCTS AJAX ----------
add_action( 'wp_ajax_ph_qp_get_products',        'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_qp_save_product',        'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_qp_load_product',        'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_qp_delete_product',      'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_qp_duplicate_product',   'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_qp_quick_edit',          'ph_ajax_lazy_load' );
add_action( 'wp_ajax_ph_qp_save_order',          'ph_ajax_lazy_load' );

function ph_ajax_lazy_load(): void {
    require_once PH_PATH . 'includes/ajax.php';
    require_once PH_PATH . 'includes/quick-products.php';

    // Call the real handler based on the action parameter
    $action   = sanitize_key( $_POST['action'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $fn_map   = [
        'ph_get_stats'            => 'ph_ajax_get_stats',
        'ph_get_timeline'         => 'ph_ajax_get_timeline',
        'ph_get_chart_data'       => 'ph_ajax_get_chart_data',
        'ph_export_csv'           => 'ph_ajax_export_csv',
        'ph_get_customer'         => 'ph_ajax_get_customer',
        'ph_get_single_order'     => 'ph_ajax_get_single_order',
        'ph_update_order_note'    => 'ph_ajax_update_order_note',
        'ph_update_order_status'  => 'ph_ajax_update_order_status',
        'ph_delete_order'         => 'ph_ajax_delete_order',
        'ph_process_refund'       => 'ph_ajax_process_refund',
        'ph_revert_refund'        => 'ph_ajax_revert_refund',
        'ph_get_top_products'     => 'ph_ajax_get_top_products',
        'ph_get_revenue_report'   => 'ph_ajax_get_revenue_report',
        'ph_get_top_categories'   => 'ph_ajax_get_top_categories',
        'ph_get_coupons_report'   => 'ph_ajax_get_coupons_report',
        'ph_get_daily_products'   => 'ph_ajax_get_daily_products',
        'ph_get_top_customers'    => 'ph_ajax_get_top_customers',
        'ph_front_stats'          => 'ph_ajax_front_stats',
        'ph_front_timeline'       => 'ph_ajax_front_timeline',
        'ph_get_low_stock'        => 'ph_ajax_get_low_stock',
        'ph_update_stock'         => 'ph_ajax_update_stock',
        'ph_stock_get'            => 'ph_ajax_stock_get',
        'ph_stock_update'         => 'ph_ajax_stock_update',
        'ph_stock_export_csv'     => 'ph_ajax_stock_export_csv',
        'ph_stock_save_settings'  => 'ph_ajax_stock_save_settings',
        'ph_stock_send_test_alert'=> 'ph_ajax_stock_send_test_alert',
        // Quick Products
        'ph_qp_get_products'      => 'ph_ajax_qp_get_products',
        'ph_qp_save_product'      => 'ph_ajax_qp_save_product',
        'ph_qp_load_product'      => 'ph_ajax_qp_load_product',
        'ph_qp_delete_product'    => 'ph_ajax_qp_delete_product',
        'ph_qp_duplicate_product' => 'ph_ajax_qp_duplicate_product',
        'ph_qp_quick_edit'        => 'ph_ajax_qp_quick_edit',
        'ph_qp_save_order'        => 'ph_ajax_qp_save_order',
    ];
    if ( ! is_user_logged_in() && $action === 'ph_front_stats' ) {
        ph_ajax_front_stats_public();
        return;
    }
    if ( isset( $fn_map[ $action ] ) && function_exists( $fn_map[ $action ] ) ) {
        call_user_func( $fn_map[ $action ] );
    } else {
        wp_send_json_error( [ 'message' => 'Onbekende actie.' ], 400 );
    }
}

// ---------- HELPER: get option ----------
function ph_get_option( string $key, $default = '' ) {
    $opts = get_option( 'ph_options', [] );
    return $opts[ $key ] ?? $default;
}

// ---------- ACTIVATION ----------
register_activation_hook( __FILE__, function () {
    add_option( 'ph_options', [
        'default_period'    => '30',
        'chart_type'        => 'line',
        'show_avg_order'    => '1',
        'show_top_products' => '1',
        'show_returning'    => '1',
        'items_per_page'    => '20',
        'export_columns'    => [ 'id', 'date', 'status', 'customer', 'total', 'items' ],
        'accent_color'      => '#10B981',
        'enable_rest_api'   => '',
    ] );
} );

// ---------- DEACTIVATION ----------
register_deactivation_hook( __FILE__, function () {
    wp_clear_scheduled_hook( 'ph_daily_cache_flush' );
    wp_clear_scheduled_hook( 'ph_stock_daily_alert' );
} );

// ---------- STOCK: DB TABLE + HOOKS ----------
add_action( 'init', function () {
    // Create table if it does not exist yet
    global $wpdb;
    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}ph_stock_log'" ) !== $wpdb->prefix . 'ph_stock_log' ) { // phpcs:ignore
        require_once PH_PATH . 'includes/data.php';
        ph_stock_create_table();
    }
    // Daily digest cron
    if ( ! wp_next_scheduled( 'ph_stock_daily_alert' ) ) {
        wp_schedule_event( time(), 'daily', 'ph_stock_daily_alert' );
    }
} );

add_action( 'ph_stock_daily_alert', function () {
    require_once PH_PATH . 'includes/data.php';
    if ( ph_stock_get_option( 'daily_digest', '1' ) === '1' ) {
        ph_stock_send_alert();
    }
} );

// Real-time alert on stock change
// woocommerce_product_set_stock / woocommerce_variation_set_stock still work in WC 7+
// Also hook woocommerce_reduce_order_stock as a backup
add_action( 'woocommerce_product_set_stock',          'ph_handle_stock_change' );
add_action( 'woocommerce_variation_set_stock',        'ph_handle_stock_change' );
add_action( 'woocommerce_product_set_stock_status',   'ph_handle_stock_status_change', 10, 3 );

function ph_handle_stock_change( $product ): void {
    if ( ! is_object( $product ) ) return;
    require_once PH_PATH . 'includes/data.php';

    // Realtime alerts on/off
    $opts = get_option( 'ph_stock_options', [] );
    if ( ( $opts['realtime_alerts'] ?? '1' ) !== '1' ) return;

    // Cooldown: max 1 alert per product per 30 minutes
    $cooldown_key = 'ph_stock_alert_' . $product->get_id();
    if ( get_transient( $cooldown_key ) ) return;
    set_transient( $cooldown_key, 1, 30 * MINUTE_IN_SECONDS );

    ph_stock_check_single( $product );
}

// Extra hook: fires when WC sets the stock_status (e.g. 'outofstock')
function ph_handle_stock_status_change( $product_id, $status, $product ): void {
    if ( ! in_array( $status, [ 'outofstock', 'onbackorder' ], true ) ) return;
    if ( ! is_object( $product ) ) $product = wc_get_product( $product_id );
    if ( ! $product ) return;
    ph_handle_stock_change( $product );
}

// Disable WooCommerce's own stock emails (Product Haven sends them instead)
add_filter( 'woocommerce_email_enabled_low_stock', '__return_false' );
add_filter( 'woocommerce_email_enabled_no_stock',  '__return_false' );

// ---------- ONE-TIME CACHE FLUSH (remove after first run) ----------
add_action( 'init', function () {
    if ( get_option( 'ph_cache_flushed_v4' ) ) return;
    global $wpdb;
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_op_%' OR option_name LIKE '_transient_timeout_op_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    update_option( 'ph_cache_flushed_v4', 1, false );
} );

// ---------- DAILY CACHE CLEANUP ----------
if ( ! wp_next_scheduled( 'ph_daily_cache_flush' ) ) {
    wp_schedule_event( time(), 'daily', 'ph_daily_cache_flush' );
}
add_action( 'ph_daily_cache_flush', function () {
    global $wpdb;
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_op_%' OR option_name LIKE '_transient_timeout_op_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
} );
