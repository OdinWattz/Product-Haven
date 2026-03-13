<?php
/**
 * Product Haven — Quick Products AJAX handlers
 *
 * All functions that access $_POST data first call ph_qp_verify_nonce(),
 * which internally uses check_ajax_referer(). PHPCS cannot detect this
 * indirect nonce verification, so the sniff is suppressed file-wide.
 *
 * @package ProductHaven
 */

// phpcs:disable WordPress.Security.NonceVerification.Missing
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_tax_query

defined( 'ABSPATH' ) || exit;

/* ── Nonce helper ─────────────────────────────────────────────────── */
function ph_qp_verify_nonce(): void {
    if ( ! check_ajax_referer( 'ph_admin_nonce', 'nonce', false ) || ! current_user_can( 'edit_products' ) ) {
        wp_send_json_error( [ 'message' => 'Geen toegang.' ], 403 );
    }
}

/* ── Product opslaan (nieuw + update) ─────────────────────────────── */
function ph_ajax_qp_save_product(): void {
    ph_qp_verify_nonce();

    $id   = absint( $_POST['product_id'] ?? 0 );
    $type = sanitize_key( $_POST['product_type'] ?? 'simple' );

    $args = [
        'post_title'   => sanitize_text_field( wp_unslash( $_POST['name']        ?? '' ) ),
        'post_content' => wp_kses_post( wp_unslash( $_POST['description']        ?? '' ) ),
        'post_excerpt' => wp_kses_post( wp_unslash( $_POST['short_description']  ?? '' ) ),
        'post_status'  => sanitize_key( $_POST['status']             ?? 'publish' ),
        'post_type'    => 'product',
    ];

    if ( $id ) {
        $args['ID'] = $id;
        $post_id    = wp_update_post( $args, true );
    } else {
        $post_id = wp_insert_post( $args, true );
    }

    if ( is_wp_error( $post_id ) ) {
        wp_send_json_error( [ 'message' => $post_id->get_error_message() ] );
    }

    wp_set_object_terms( $post_id, $type, 'product_type' );

    $cat_ids = array_map( 'absint', (array) ( $_POST['categories'] ?? [] ) );
    $tag_ids = array_map( 'absint', (array) ( $_POST['tags']       ?? [] ) );
    wp_set_object_terms( $post_id, $cat_ids, 'product_cat' );
    wp_set_object_terms( $post_id, $tag_ids, 'product_tag' );

    if ( taxonomy_exists( 'product_brand' ) ) {
        $brand_ids = array_filter( array_map( 'absint', (array) ( $_POST['brands'] ?? [] ) ) );
        wp_set_object_terms( $post_id, $brand_ids, 'product_brand' );
    }

    $attr_taxonomies = wc_get_attribute_taxonomies();
    $product_attrs   = [];
    foreach ( $attr_taxonomies as $attr ) {
        $tax_name = wc_attribute_taxonomy_name( $attr->attribute_name );
        $key      = 'attr_' . $attr->attribute_name;
        $term_ids = array_filter( array_map( 'absint', (array) ( $_POST[ $key ] ?? [] ) ) );
        if ( ! empty( $term_ids ) ) {
            wp_set_object_terms( $post_id, $term_ids, $tax_name );
            $product_attrs[ $tax_name ] = [
                'name'         => $tax_name,
                'value'        => '',
                'position'     => 0,
                'is_visible'   => 1,
                'is_variation' => 0,
                'is_taxonomy'  => 1,
            ];
        } else {
            wp_set_object_terms( $post_id, [], $tax_name );
        }
    }

    if ( ! empty( $product_attrs ) ) {
        $existing_attrs = get_post_meta( $post_id, '_product_attributes', true ) ?: [];
        foreach ( $attr_taxonomies as $attr ) {
            $tax_name = wc_attribute_taxonomy_name( $attr->attribute_name );
            if ( isset( $product_attrs[ $tax_name ] ) ) {
                $existing_attrs[ $tax_name ] = $product_attrs[ $tax_name ];
            } elseif ( isset( $existing_attrs[ $tax_name ] ) ) {
                unset( $existing_attrs[ $tax_name ] );
            }
        }
        update_post_meta( $post_id, '_product_attributes', $existing_attrs );
    }

    $image_id = absint( $_POST['image_id'] ?? 0 );
    if ( $image_id ) {
        set_post_thumbnail( $post_id, $image_id );
    } elseif ( isset( $_POST['remove_image'] ) ) {
        delete_post_thumbnail( $post_id );
    }

    $gallery_ids = array_filter( array_map( 'absint', (array) ( $_POST['gallery_ids'] ?? [] ) ) );
    update_post_meta( $post_id, '_product_image_gallery', implode( ',', $gallery_ids ) );

    $regular = wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['regular_price'] ?? '' ) ) );
    $sale    = wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['sale_price']    ?? '' ) ) );

    update_post_meta( $post_id, '_regular_price', $regular );
    update_post_meta( $post_id, '_price', $sale !== '' ? $sale : $regular );
    update_post_meta( $post_id, '_sale_price', $sale );

    $sale_from = sanitize_text_field( wp_unslash( $_POST['sale_price_dates_from'] ?? '' ) );
    $sale_to   = sanitize_text_field( wp_unslash( $_POST['sale_price_dates_to']   ?? '' ) );
    update_post_meta( $post_id, '_sale_price_dates_from', $sale_from ? strtotime( $sale_from ) : '' );
    update_post_meta( $post_id, '_sale_price_dates_to',   $sale_to   ? strtotime( $sale_to )   : '' );

    $manage_stock = ! empty( $_POST['manage_stock'] ) ? 'yes' : 'no';
    update_post_meta( $post_id, '_manage_stock',     $manage_stock );
    update_post_meta( $post_id, '_stock_status',     sanitize_key( $_POST['stock_status']  ?? 'instock' ) );
    update_post_meta( $post_id, '_backorders',       sanitize_key( $_POST['backorders']    ?? 'no' ) );
    update_post_meta( $post_id, '_low_stock_amount', absint( $_POST['low_stock_amount']    ?? '' ) ?: '' );

    if ( $manage_stock === 'yes' ) {
        $qty = (int) wp_unslash( $_POST['stock_quantity'] ?? 0 ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        update_post_meta( $post_id, '_stock', $qty );
        wc_update_product_stock_status( $post_id, $qty > 0 ? 'instock' : 'outofstock' );
    }

    update_post_meta( $post_id, '_weight',       wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['weight'] ?? '' ) ) ) );
    update_post_meta( $post_id, '_length',       wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['length'] ?? '' ) ) ) );
    update_post_meta( $post_id, '_width',        wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['width']  ?? '' ) ) ) );
    update_post_meta( $post_id, '_height',       wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['height'] ?? '' ) ) ) );
    update_post_meta( $post_id, '_virtual',      ! empty( $_POST['virtual'] )       ? 'yes' : 'no' );
    update_post_meta( $post_id, '_downloadable', ! empty( $_POST['downloadable'] )  ? 'yes' : 'no' );

    $ship_class = absint( $_POST['shipping_class'] ?? 0 );
    wp_set_object_terms( $post_id, $ship_class ?: [], 'product_shipping_class' );

    update_post_meta( $post_id, '_tax_status', sanitize_key( $_POST['tax_status'] ?? 'taxable' ) );
    update_post_meta( $post_id, '_tax_class',  sanitize_text_field( wp_unslash( $_POST['tax_class'] ?? '' ) ) );

    $slug = sanitize_title( wp_unslash( $_POST['slug'] ?? '' ) );
    if ( $slug ) {
        wp_update_post( [ 'ID' => $post_id, 'post_name' => $slug ] );
    }

    $sku = sanitize_text_field( wp_unslash( $_POST['sku'] ?? '' ) );
    update_post_meta( $post_id, '_sku', $sku );

    $upsells    = array_filter( array_map( 'absint', (array) ( $_POST['upsell_ids']    ?? [] ) ) );
    $crosssells = array_filter( array_map( 'absint', (array) ( $_POST['crosssell_ids'] ?? [] ) ) );
    update_post_meta( $post_id, '_upsell_ids',    $upsells );
    update_post_meta( $post_id, '_crosssell_ids', $crosssells );

    $visibility = sanitize_key( $_POST['catalog_visibility'] ?? 'visible' );
    update_post_meta( $post_id, '_visibility',        $visibility );
    update_post_meta( $post_id, '_featured',          ! empty( $_POST['featured'] )          ? 'yes' : 'no' );
    update_post_meta( $post_id, '_sold_individually', ! empty( $_POST['sold_individually'] )  ? 'yes' : 'no' );

    wc_delete_product_transients( $post_id );
    clean_post_cache( $post_id );

    $product = wc_get_product( $post_id );

    wp_send_json_success( [
        'product_id'   => $post_id,
        'edit_url'     => get_edit_post_link( $post_id, 'raw' ),
        'name'         => $product->get_name(),
        'price'        => $product->get_price(),
        'status'       => $product->get_status(),
        'stock'        => $product->get_stock_quantity(),
        'stock_status' => $product->get_stock_status(),
        'sku'          => $product->get_sku(),
        'image'        => wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ) ?: '',
    ] );
}
add_action( 'wp_ajax_ph_qp_save_product', 'ph_ajax_qp_save_product' );

/* ── Product laden (bewerken) ─────────────────────────────────────── */
function ph_ajax_qp_load_product(): void {
    ph_qp_verify_nonce();
    $id = absint( $_POST['product_id'] ?? 0 );
    if ( ! $id ) wp_send_json_error( [ 'message' => 'Geen product ID.' ] );

    $product = wc_get_product( $id );
    if ( ! $product ) wp_send_json_error( [ 'message' => 'Product niet gevonden.' ] );

    $post   = get_post( $id );
    $cats   = wp_get_object_terms( $id, 'product_cat', [ 'fields' => 'ids' ] );
    $tags   = wp_get_object_terms( $id, 'product_tag', [ 'fields' => 'ids' ] );
    $brands = taxonomy_exists( 'product_brand' )
        ? wp_get_object_terms( $id, 'product_brand', [ 'fields' => 'ids' ] )
        : [];

    $attr_taxonomies = wc_get_attribute_taxonomies();
    $selected_attrs  = [];
    foreach ( $attr_taxonomies as $attr ) {
        $tax_name = wc_attribute_taxonomy_name( $attr->attribute_name );
        $term_ids = wp_get_object_terms( $id, $tax_name, [ 'fields' => 'ids' ] );
        $selected_attrs[ $attr->attribute_name ] = is_wp_error( $term_ids ) ? [] : array_map( 'intval', $term_ids );
    }

    $image_id  = $product->get_image_id();
    $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';

    $gallery_ids  = $product->get_gallery_image_ids();
    $gallery_urls = array_map( fn( $gid ) => [
        'id'  => $gid,
        'url' => wp_get_attachment_image_url( $gid, 'thumbnail' ),
    ], $gallery_ids );

    $ship_terms = wp_get_object_terms( $id, 'product_shipping_class', [ 'fields' => 'ids' ] );
    $ship_class = ! empty( $ship_terms ) ? (int) $ship_terms[0] : 0;

    $sale_from = get_post_meta( $id, '_sale_price_dates_from', true );
    $sale_to   = get_post_meta( $id, '_sale_price_dates_to',   true );

    wp_send_json_success( [
        'product_id'            => $id,
        'name'                  => $product->get_name(),
        'slug'                  => $post->post_name,
        'status'                => $post->post_status,
        'description'           => $post->post_content,
        'short_description'     => $post->post_excerpt,
        'product_type'          => $product->get_type(),
        'sku'                   => $product->get_sku(),
        'regular_price'         => $product->get_regular_price(),
        'sale_price'            => $product->get_sale_price(),
        'sale_price_dates_from' => $sale_from ? gmdate( 'Y-m-d', $sale_from ) : '',
        'sale_price_dates_to'   => $sale_to   ? gmdate( 'Y-m-d', $sale_to )   : '',
        'manage_stock'          => $product->get_manage_stock() ? '1' : '0',
        'stock_quantity'        => $product->get_stock_quantity(),
        'stock_status'          => $product->get_stock_status(),
        'backorders'            => $product->get_backorders(),
        'low_stock_amount'      => $product->get_low_stock_amount(),
        'weight'                => $product->get_weight(),
        'length'                => $product->get_length(),
        'width'                 => $product->get_width(),
        'height'                => $product->get_height(),
        'virtual'               => $product->get_virtual() ? '1' : '0',
        'downloadable'          => $product->get_downloadable() ? '1' : '0',
        'shipping_class'        => $ship_class,
        'tax_status'            => $product->get_tax_status(),
        'tax_class'             => $product->get_tax_class(),
        'catalog_visibility'    => $product->get_catalog_visibility(),
        'featured'              => $product->get_featured() ? '1' : '0',
        'sold_individually'     => $product->get_sold_individually() ? '1' : '0',
        'categories'            => $cats,
        'tags'                  => $tags,
        'brands'                => $brands,
        'attributes'            => $selected_attrs,
        'image_id'              => $image_id,
        'image_url'             => $image_url,
        'gallery'               => $gallery_urls,
        'upsell_ids'            => $product->get_upsell_ids(),
        'crosssell_ids'         => $product->get_cross_sell_ids(),
        'edit_url'              => get_edit_post_link( $id, 'raw' ),
    ] );
}
add_action( 'wp_ajax_ph_qp_load_product', 'ph_ajax_qp_load_product' );

/* ── Product verwijderen ──────────────────────────────────────────── */
function ph_ajax_qp_delete_product(): void {
    ph_qp_verify_nonce();
    $id = absint( $_POST['product_id'] ?? 0 );
    if ( ! $id || ! current_user_can( 'delete_products' ) ) {
        wp_send_json_error( [ 'message' => 'Geen rechten.' ] );
    }
    $result = wp_trash_post( $id );
    $result ? wp_send_json_success() : wp_send_json_error( [ 'message' => 'Verwijderen mislukt.' ] );
}
add_action( 'wp_ajax_ph_qp_delete_product', 'ph_ajax_qp_delete_product' );

/* ── Productenlijst (paginering + zoeken + filter) ────────────────── */
function ph_ajax_qp_get_products(): void {
    ph_qp_verify_nonce();

    $page     = max( 1, absint( $_POST['page']    ?? 1 ) );
    $per_page = min( 50, absint( $_POST['per_page'] ?? 20 ) );
    $search   = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
    $status   = sanitize_key( $_POST['status'] ?? 'any' );
    $cat_id   = absint( $_POST['cat_id'] ?? 0 );
    $type     = sanitize_key( $_POST['product_type'] ?? '' );
    $orderby  = sanitize_key( $_POST['orderby'] ?? 'date' );
    $order    = strtoupper( sanitize_key( $_POST['order'] ?? 'DESC' ) ) === 'ASC' ? 'ASC' : 'DESC';

    $args = [
        'post_type'      => 'product',
        'post_status'    => $status === 'any' ? [ 'publish', 'draft', 'pending', 'private' ] : [ $status ],
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'orderby'        => $orderby,
        'order'          => $order,
        's'              => $search,
    ];

    if ( $cat_id ) {
        $args['tax_query'] = [ [ 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $cat_id ] ];
    }
    if ( $type ) {
        $type_query = [ [ 'taxonomy' => 'product_type', 'field' => 'slug', 'terms' => $type ] ];
        $args['tax_query'] = isset( $args['tax_query'] )
            ? array_merge( $args['tax_query'], $type_query )
            : $type_query;
    }

    $query    = new WP_Query( $args );
    $products = [];

    foreach ( $query->posts as $post ) {
        $p = wc_get_product( $post->ID );
        if ( ! $p ) continue;
        $products[] = [
            'id'            => $p->get_id(),
            'name'          => $p->get_name(),
            'sku'           => $p->get_sku(),
            'type'          => $p->get_type(),
            'status'        => $p->get_status(),
            'price'         => (float) $p->get_price(),
            'regular_price' => (float) $p->get_regular_price(),
            'sale_price'    => (float) $p->get_sale_price(),
            'stock_status'  => $p->get_stock_status(),
            'stock_qty'     => $p->get_stock_quantity(),
            'manage_stock'  => $p->get_manage_stock(),
            'image'         => wp_get_attachment_image_url( $p->get_image_id(), 'thumbnail' ) ?: '',
            'edit_url'      => get_edit_post_link( $p->get_id(), 'raw' ),
            'categories'    => implode( ', ', wp_get_object_terms( $p->get_id(), 'product_cat', [ 'fields' => 'names' ] ) ),
        ];
    }

    wp_send_json_success( [
        'products' => $products,
        'total'    => (int) $query->found_posts,
        'pages'    => (int) $query->max_num_pages,
        'page'     => $page,
    ] );
}
add_action( 'wp_ajax_ph_qp_get_products', 'ph_ajax_qp_get_products' );

/* ── Dupliceren ───────────────────────────────────────────────────── */
function ph_ajax_qp_duplicate_product(): void {
    ph_qp_verify_nonce();
    $id = absint( $_POST['product_id'] ?? 0 );
    if ( ! $id || ! current_user_can( 'edit_products' ) ) wp_send_json_error();

    if ( ! function_exists( 'wc_duplicate_product' ) ) {
        include_once WC()->plugin_path() . '/includes/admin/wc-admin-functions.php';
    }

    $product   = wc_get_product( $id );
    $duplicate = wc_duplicate_product( $product );

    if ( ! $duplicate ) wp_send_json_error( [ 'message' => 'Dupliceren mislukt.' ] );

    wp_send_json_success( [
        'product_id' => $duplicate->get_id(),
        'name'       => $duplicate->get_name(),
        'edit_url'   => get_edit_post_link( $duplicate->get_id(), 'raw' ),
    ] );
}
add_action( 'wp_ajax_ph_qp_duplicate_product', 'ph_ajax_qp_duplicate_product' );

/* ── Snel-prijs en snel-voorraad inline bewerken ─────────────────── */
function ph_ajax_qp_quick_edit(): void {
    ph_qp_verify_nonce();
    $id    = absint( $_POST['product_id'] ?? 0 );
    $field = sanitize_key( $_POST['field'] ?? '' );
    $value = sanitize_text_field( wp_unslash( $_POST['value'] ?? '' ) );

    if ( ! $id || ! $field ) wp_send_json_error();

    switch ( $field ) {
        case 'regular_price':
            $v = wc_format_decimal( $value );
            update_post_meta( $id, '_regular_price', $v );
            update_post_meta( $id, '_price', $v );
            break;
        case 'sale_price':
            $v = wc_format_decimal( $value );
            update_post_meta( $id, '_sale_price', $v );
            if ( $v !== '' ) update_post_meta( $id, '_price', $v );
            break;
        case 'stock_quantity':
            update_post_meta( $id, '_stock', (int) $value );
            update_post_meta( $id, '_manage_stock', 'yes' );
            $new_status = (int) $value > 0 ? 'instock' : 'outofstock';
            update_post_meta( $id, '_stock_status', $new_status );
            wc_delete_product_transients( $id );
            break;
        case 'status':
            wp_update_post( [ 'ID' => $id, 'post_status' => sanitize_key( $value ) ] );
            break;
        default:
            wp_send_json_error( [ 'message' => 'Onbekend veld.' ] );
    }

    wc_delete_product_transients( $id );
    wp_send_json_success();
}
add_action( 'wp_ajax_ph_qp_quick_edit', 'ph_ajax_qp_quick_edit' );

/* ── Render pagina data (categorieën, tags, merken, attributen) ───── */
function ph_qp_get_page_data(): array {
    $cats   = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => false, 'orderby' => 'name' ] );
    $tags   = get_terms( [ 'taxonomy' => 'product_tag', 'hide_empty' => false, 'orderby' => 'name' ] );
    $brands = taxonomy_exists( 'product_brand' )
        ? get_terms( [ 'taxonomy' => 'product_brand', 'hide_empty' => false, 'orderby' => 'name' ] )
        : [];

    $attr_taxonomies = wc_get_attribute_taxonomies();
    $attributes_data = [];
    foreach ( $attr_taxonomies as $attr ) {
        $tax_name = wc_attribute_taxonomy_name( $attr->attribute_name );
        $terms    = get_terms( [ 'taxonomy' => $tax_name, 'hide_empty' => false, 'orderby' => 'name' ] );
        $attributes_data[] = [
            'id'       => $attr->attribute_id,
            'name'     => $attr->attribute_name,
            'label'    => $attr->attribute_label,
            'taxonomy' => $tax_name,
            'type'     => $attr->attribute_type,
            'terms'    => is_wp_error( $terms ) ? [] : $terms,
        ];
    }

    $tax_classes   = WC_Tax::get_tax_classes();
    $tax_class_opt = [ '' => __( 'Standaard', 'product-haven' ) ];
    foreach ( $tax_classes as $tc ) {
        $tax_class_opt[ sanitize_title( $tc ) ] = $tc;
    }
    $tax_class_opt['zero-rate'] = __( '0% (Vrijgesteld)', 'product-haven' );

    $shipping_classes = WC()->shipping() ? WC()->shipping()->get_shipping_classes() : [];

    return compact( 'cats', 'tags', 'brands', 'attributes_data', 'tax_class_opt', 'shipping_classes' );
}
