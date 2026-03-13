<?php
/**
 * Product Haven — REST API (optioneel)
 *
 * Endpoint: /wp-json/product-haven/v1/stats
 * Vereist: 'enable_rest_api' => '1' in ph_options
 *
 * All functions are prefixed with ph_ per the plugin prefix convention.
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
 *
 * @package ProductHaven
 */

defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', function () {
    if ( ! ph_get_option( 'enable_rest_api' ) ) return;

    $namespace = 'product-haven/v1';

    register_rest_route( $namespace, '/stats', [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'ph_rest_stats',
        'permission_callback' => 'ph_rest_permission',
        'args'                => [
            'days' => [
                'default'           => 30,
                'sanitize_callback' => 'absint',
                'validate_callback' => fn( $v ) => $v > 0 && $v <= 365,
            ],
        ],
    ] );

    register_rest_route( $namespace, '/timeline', [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'ph_rest_timeline',
        'permission_callback' => 'ph_rest_permission',
        'args'                => [
            'page'     => [ 'default' => 1,    'sanitize_callback' => 'absint' ],
            'per_page' => [ 'default' => 20,   'sanitize_callback' => 'absint' ],
            'status'   => [ 'default' => 'any','sanitize_callback' => 'sanitize_key' ],
            'days'     => [ 'default' => 0,    'sanitize_callback' => 'absint' ],
        ],
    ] );

    register_rest_route( $namespace, '/top-products', [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'ph_rest_top_products',
        'permission_callback' => 'ph_rest_permission',
        'args'                => [
            'days'  => [ 'default' => 30, 'sanitize_callback' => 'absint' ],
            'limit' => [ 'default' => 10, 'sanitize_callback' => 'absint' ],
        ],
    ] );
} );

function ph_rest_permission(): bool {
    return current_user_can( 'manage_woocommerce' );
}

function ph_rest_stats( WP_REST_Request $req ): WP_REST_Response {
    require_once PH_PATH . 'includes/data.php';
    return rest_ensure_response( ph_get_stats( $req->get_param( 'days' ) ) );
}

function ph_rest_timeline( WP_REST_Request $req ): WP_REST_Response {
    require_once PH_PATH . 'includes/data.php';
    return rest_ensure_response( ph_get_timeline( [
        'page'     => $req->get_param( 'page' ),
        'per_page' => $req->get_param( 'per_page' ),
        'status'   => $req->get_param( 'status' ),
        'days'     => $req->get_param( 'days' ),
    ] ) );
}

function ph_rest_top_products( WP_REST_Request $req ): WP_REST_Response {
    require_once PH_PATH . 'includes/data.php';
    return rest_ensure_response( ph_get_top_products(
        $req->get_param( 'days' ),
        min( 50, $req->get_param( 'limit' ) )
    ) );
}
