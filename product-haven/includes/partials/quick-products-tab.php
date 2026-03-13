<?php
/**
 * Product Haven — Quick Products tab HTML
 *
 * Included inside ph_render_admin_page(); variables are scoped to that context.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 *
 * @package ProductHaven
 */

defined( 'ABSPATH' ) || exit;

$lang = ph_get_lang();
$qp = ph_qp_get_page_data();
extract( $qp ); // $cats, $tags, $brands, $attributes_data, $tax_class_opt, $shipping_classes
?>
<!-- ===== TAB: QUICK PRODUCTS ===== -->
<div class="mos-tab-panel" id="mos-panel-products">

    <!-- Sub-tabs binnen het producten-paneel -->
    <nav class="op-qp-tabs" id="op-qp-tabs">
        <button class="op-qp-tab is-active" data-qptab="list">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            <?php echo esc_html( ph_t( 'qp_tab_list', $lang ) ); ?>
            <span class="op-qp-tab-count" id="op-qp-total-count"></span>
        </button>
        <button class="op-qp-tab" data-qptab="editor">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            <span id="op-qp-editor-tab-label"><?php echo esc_html( ph_t( 'qp_tab_editor', $lang ) ); ?></span>
        </button>
        <button class="op-qp-new-btn mos-btn mos-btn-primary" id="op-qp-new-btn" style="margin-left:auto">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <?php echo esc_html( ph_t( 'qp_new_product_btn', $lang ) ); ?>
        </button>
    </nav>

    <!-- ── LIJST PANEEL ── -->
    <div class="op-qp-panel is-active" id="op-qp-panel-list">

        <div class="op-qp-filters">
            <div class="op-qp-search-wrap">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="search" id="op-qp-search" class="op-qp-search-input" placeholder="<?php echo esc_attr( ph_t( 'qp_search_placeholder', $lang ) ); ?>">
            </div>
            <select id="op-qp-filter-status" class="mos-input-sm">
                <option value="any"><?php echo esc_html( ph_t( 'qp_filter_all_statuses', $lang ) ); ?></option>
                <option value="publish"><?php echo esc_html( ph_t( 'qp_filter_published', $lang ) ); ?></option>
                <option value="draft"><?php echo esc_html( ph_t( 'qp_filter_draft', $lang ) ); ?></option>
                <option value="private"><?php echo esc_html( ph_t( 'qp_filter_private', $lang ) ); ?></option>
            </select>
            <select id="op-qp-filter-cat" class="mos-input-sm">
                <option value="0"><?php echo esc_html( ph_t( 'qp_filter_all_cats', $lang ) ); ?></option>
                <?php foreach ( $cats as $cat ) : ?>
                    <option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
                <?php endforeach; ?>
            </select>
            <select id="op-qp-filter-type" class="mos-input-sm">
                <option value=""><?php echo esc_html( ph_t( 'qp_filter_all_types', $lang ) ); ?></option>
                <option value="simple"><?php echo esc_html( ph_t( 'qp_type_simple', $lang ) ); ?></option>
                <option value="variable"><?php echo esc_html( ph_t( 'qp_type_variable', $lang ) ); ?></option>
                <option value="grouped"><?php echo esc_html( ph_t( 'qp_type_grouped', $lang ) ); ?></option>
                <option value="external"><?php echo esc_html( ph_t( 'qp_type_external', $lang ) ); ?></option>
            </select>
            <select id="op-qp-filter-orderby" class="mos-input-sm">
                <option value="date"><?php echo esc_html( ph_t( 'qp_sort_newest', $lang ) ); ?></option>
                <option value="title"><?php echo esc_html( ph_t( 'qp_sort_name', $lang ) ); ?></option>
                <option value="meta_value_num"><?php echo esc_html( ph_t( 'qp_sort_price', $lang ) ); ?></option>
            </select>
        </div>

        <section class="mos-card" style="padding:0;overflow:hidden;">
            <div class="op-qp-table-wrap" id="op-qp-table-wrap">
                <div class="mos-loading-spinner" style="margin:40px auto;"></div>
            </div>
            <div class="op-qp-pagination" id="op-qp-pagination"></div>
        </section>

    </div><!-- #op-qp-panel-list -->

    <!-- ── EDITOR PANEEL ── -->
    <div class="op-qp-panel" id="op-qp-panel-editor">
        <form id="op-qp-product-form" novalidate>
            <input type="hidden" id="op-qp-product-id" name="product_id" value="0">

            <div class="op-qp-editor-layout">

                <!-- LINKER KOLOM -->
                <div class="op-qp-editor-main">

                    <!-- Basisinfo -->
                    <section class="mos-card">
                        <div class="mos-card-header"><h2><?php echo esc_html( ph_t( 'qp_section_product_info', $lang ) ); ?></h2></div>
                        <div class="mos-card-body">
                            <div class="op-qp-field op-qp-field-full">
                                <label for="op-qp-name"><?php echo esc_html( ph_t( 'qp_name_label', $lang ) ); ?></label>
                                <input type="text" id="op-qp-name" name="name" class="op-qp-input" required placeholder="<?php echo esc_attr( ph_t( 'qp_name_placeholder', $lang ) ); ?>">
                            </div>
                            <div class="op-qp-field-row">
                                <div class="op-qp-field">
                                    <label for="op-qp-sku"><?php echo esc_html( ph_t( 'qp_sku_label', $lang ) ); ?></label>
                                    <input type="text" id="op-qp-sku" name="sku" class="op-qp-input" placeholder="bijv. SNK-042-R">
                                </div>
                                <div class="op-qp-field">
                                    <label for="op-qp-product-type"><?php echo esc_html( ph_t( 'qp_product_type_label', $lang ) ); ?></label>
                                    <select id="op-qp-product-type" name="product_type" class="op-qp-select">
                                        <option value="simple"><?php echo esc_html( ph_t( 'qp_type_simple', $lang ) ); ?></option>
                                        <option value="variable"><?php echo esc_html( ph_t( 'qp_type_variable', $lang ) ); ?></option>
                                        <option value="grouped"><?php echo esc_html( ph_t( 'qp_type_grouped', $lang ) ); ?></option>
                                        <option value="external"><?php echo esc_html( ph_t( 'qp_type_external_aff', $lang ) ); ?></option>
                                    </select>
                                </div>
                                <div class="op-qp-field">
                                    <label for="op-qp-slug"><?php echo esc_html( ph_t( 'qp_slug_label', $lang ) ); ?></label>
                                    <input type="text" id="op-qp-slug" name="slug" class="op-qp-input" placeholder="<?php echo esc_attr( ph_t( 'qp_slug_placeholder', $lang ) ); ?>">
                                </div>
                            </div>
                            <div class="op-qp-field op-qp-field-full">
                                <label for="op-qp-short-desc"><?php echo esc_html( ph_t( 'qp_short_desc_label', $lang ) ); ?></label>
                                <textarea id="op-qp-short-desc" name="short_description" class="op-qp-textarea op-qp-textarea-sm" rows="3" placeholder="<?php echo esc_attr( ph_t( 'qp_short_desc_placeholder', $lang ) ); ?>"></textarea>
                            </div>
                            <div class="op-qp-field op-qp-field-full">
                                <label for="op-qp-desc"><?php echo esc_html( ph_t( 'qp_desc_label', $lang ) ); ?></label>
                                <textarea id="op-qp-desc" name="description" class="op-qp-textarea" rows="6" placeholder="<?php echo esc_attr( ph_t( 'qp_desc_placeholder', $lang ) ); ?>"></textarea>
                            </div>
                        </div>
                    </section>

                    <!-- Prijs -->
                    <section class="mos-card">
                        <div class="mos-card-header"><h2><?php echo esc_html( ph_t( 'qp_section_price', $lang ) ); ?></h2></div>
                        <div class="mos-card-body">
                            <div class="op-qp-field-row">
                                <div class="op-qp-field">
                                    <label for="op-qp-regular-price"><?php echo esc_html( ph_t( 'qp_regular_price_label', $lang ) ); ?> (<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>)</label>
                                    <input type="number" id="op-qp-regular-price" name="regular_price" class="op-qp-input" step="0.01" min="0" placeholder="0.00">
                                </div>
                                <div class="op-qp-field">
                                    <label for="op-qp-sale-price"><?php echo esc_html( ph_t( 'qp_sale_price_label', $lang ) ); ?> (<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>)</label>
                                    <input type="number" id="op-qp-sale-price" name="sale_price" class="op-qp-input" step="0.01" min="0" placeholder="<?php echo esc_attr( ph_t( 'qp_sale_price_placeholder', $lang ) ); ?>">
                                </div>
                            </div>
                            <div class="op-qp-field-row" id="op-qp-sale-dates">
                                <div class="op-qp-field">
                                    <label for="op-qp-sale-from"><?php echo esc_html( ph_t( 'qp_sale_from_label', $lang ) ); ?></label>
                                    <input type="date" id="op-qp-sale-from" name="sale_price_dates_from" class="op-qp-input">
                                </div>
                                <div class="op-qp-field">
                                    <label for="op-qp-sale-to"><?php echo esc_html( ph_t( 'qp_sale_to_label', $lang ) ); ?></label>
                                    <input type="date" id="op-qp-sale-to" name="sale_price_dates_to" class="op-qp-input">
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Voorraad -->
                    <section class="mos-card">
                        <div class="mos-card-header"><h2><?php echo esc_html( ph_t( 'qp_section_stock', $lang ) ); ?></h2></div>
                        <div class="mos-card-body">
                            <div class="op-qp-field-row">
                                <div class="op-qp-field">
                                    <label for="op-qp-stock-status"><?php echo esc_html( ph_t( 'qp_stock_status_label', $lang ) ); ?></label>
                                    <select id="op-qp-stock-status" name="stock_status" class="op-qp-select">
                                        <option value="instock"><?php echo esc_html( ph_t( 'qp_instock_label', $lang ) ); ?></option>
                                        <option value="outofstock"><?php echo esc_html( ph_t( 'qp_outofstock_label', $lang ) ); ?></option>
                                        <option value="onbackorder"><?php echo esc_html( ph_t( 'qp_onbackorder_label', $lang ) ); ?></option>
                                    </select>
                                </div>
                                <div class="op-qp-field op-qp-field-toggle-wrap">
                                    <label class="op-qp-toggle-label">
                                        <input type="checkbox" id="op-qp-manage-stock" name="manage_stock" value="1" class="op-qp-toggle-check">
                                        <span class="op-qp-toggle"></span>
                                        <?php echo esc_html( ph_t( 'qp_manage_stock_label', $lang ) ); ?>
                                    </label>
                                </div>
                            </div>
                            <div id="op-qp-stock-fields" class="op-qp-stock-fields op-qp-hidden">
                                <div class="op-qp-field-row">
                                    <div class="op-qp-field">
                                        <label for="op-qp-stock-qty"><?php echo esc_html( ph_t( 'qp_stock_qty_label', $lang ) ); ?></label>
                                        <input type="number" id="op-qp-stock-qty" name="stock_quantity" class="op-qp-input" min="0" step="1" value="0">
                                    </div>
                                    <div class="op-qp-field">
                                        <label for="op-qp-backorders"><?php echo esc_html( ph_t( 'qp_backorders_label', $lang ) ); ?></label>
                                        <select id="op-qp-backorders" name="backorders" class="op-qp-select">
                                            <option value="no"><?php echo esc_html( ph_t( 'qp_backorders_no', $lang ) ); ?></option>
                                            <option value="notify"><?php echo esc_html( ph_t( 'qp_backorders_notify', $lang ) ); ?></option>
                                            <option value="yes"><?php echo esc_html( ph_t( 'qp_backorders_yes', $lang ) ); ?></option>
                                        </select>
                                    </div>
                                    <div class="op-qp-field">
                                        <label for="op-qp-low-stock"><?php echo esc_html( ph_t( 'qp_low_stock_label', $lang ) ); ?></label>
                                        <input type="number" id="op-qp-low-stock" name="low_stock_amount" class="op-qp-input" min="0" step="1" placeholder="<?php echo esc_attr( ph_t( 'qp_store_default', $lang ) ); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="op-qp-field-row op-qp-mt-sm">
                                <div class="op-qp-field op-qp-field-toggle-wrap">
                                    <label class="op-qp-toggle-label">
                                        <input type="checkbox" id="op-qp-sold-individually" name="sold_individually" value="1" class="op-qp-toggle-check">
                                        <span class="op-qp-toggle"></span>
                                        <?php echo esc_html( ph_t( 'qp_sold_individually', $lang ) ); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Verzending -->
                    <section class="mos-card">
                        <div class="mos-card-header">
                            <h2><?php echo esc_html( ph_t( 'qp_section_shipping', $lang ) ); ?></h2>
                            <div style="display:flex;align-items:center;gap:16px;">
                                <label class="op-qp-toggle-label">
                                    <input type="checkbox" id="op-qp-virtual" name="virtual" value="1" class="op-qp-toggle-check">
                                    <span class="op-qp-toggle"></span>
                                    <?php echo esc_html( ph_t( 'qp_virtual_label', $lang ) ); ?>
                                </label>
                                <label class="op-qp-toggle-label">
                                    <input type="checkbox" id="op-qp-downloadable" name="downloadable" value="1" class="op-qp-toggle-check">
                                    <span class="op-qp-toggle"></span>
                                    <?php echo esc_html( ph_t( 'qp_downloadable_label', $lang ) ); ?>
                                </label>
                            </div>
                        </div>
                        <div class="mos-card-body" id="op-qp-shipping-fields">
                            <div class="op-qp-field-row">
                                <div class="op-qp-field">
                                    <label for="op-qp-weight"><?php echo esc_html( ph_t( 'qp_weight_label', $lang ) ); ?> (<?php echo esc_html( get_option( 'woocommerce_weight_unit', 'kg' ) ); ?>)</label>
                                    <input type="number" id="op-qp-weight" name="weight" class="op-qp-input" step="0.001" min="0" placeholder="0">
                                </div>
                                <div class="op-qp-field">
                                    <label><?php echo esc_html( ph_t( 'qp_dimensions_label', $lang ) ); ?> (<?php echo esc_html( get_option( 'woocommerce_dimension_unit', 'cm' ) ); ?>)</label>
                                    <div class="op-qp-dimensions">
                                        <input type="number" name="length" class="op-qp-input" step="0.01" min="0" placeholder="<?php echo esc_attr( ph_t( 'qp_dim_length', $lang ) ); ?>">
                                        <input type="number" name="width"  class="op-qp-input" step="0.01" min="0" placeholder="<?php echo esc_attr( ph_t( 'qp_dim_width', $lang ) ); ?>">
                                        <input type="number" name="height" class="op-qp-input" step="0.01" min="0" placeholder="<?php echo esc_attr( ph_t( 'qp_dim_height', $lang ) ); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="op-qp-field">
                                <label for="op-qp-shipping-class"><?php echo esc_html( ph_t( 'qp_shipping_class_label', $lang ) ); ?></label>
                                <select id="op-qp-shipping-class" name="shipping_class" class="op-qp-select">
                                    <option value="0"><?php echo esc_html( ph_t( 'qp_no_shipping_class', $lang ) ); ?></option>
                                    <?php foreach ( $shipping_classes as $sc ) : ?>
                                        <option value="<?php echo esc_attr( $sc->term_id ); ?>"><?php echo esc_html( $sc->name ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </section>

                    <!-- Belasting -->
                    <section class="mos-card">
                        <div class="mos-card-header"><h2><?php echo esc_html( ph_t( 'qp_section_tax', $lang ) ); ?></h2></div>
                        <div class="mos-card-body">
                            <div class="op-qp-field-row">
                                <div class="op-qp-field">
                                    <label for="op-qp-tax-status"><?php echo esc_html( ph_t( 'qp_tax_status_label', $lang ) ); ?></label>
                                    <select id="op-qp-tax-status" name="tax_status" class="op-qp-select">
                                        <option value="taxable"><?php echo esc_html( ph_t( 'qp_taxable', $lang ) ); ?></option>
                                        <option value="shipping"><?php echo esc_html( ph_t( 'qp_shipping_only', $lang ) ); ?></option>
                                        <option value="none"><?php echo esc_html( ph_t( 'qp_none_tax', $lang ) ); ?></option>
                                    </select>
                                </div>
                                <div class="op-qp-field">
                                    <label for="op-qp-tax-class"><?php echo esc_html( ph_t( 'qp_tax_class_label', $lang ) ); ?></label>
                                    <select id="op-qp-tax-class" name="tax_class" class="op-qp-select">
                                        <?php foreach ( $tax_class_opt as $val => $lbl ) : ?>
                                            <option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $lbl ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </section>

                </div><!-- .op-qp-editor-main -->

                <!-- RECHTER KOLOM -->
                <div class="op-qp-editor-sidebar">

                    <!-- Publiceren -->
                    <section class="mos-card">
                        <div class="mos-card-header"><h2><?php echo esc_html( ph_t( 'qp_section_publish', $lang ) ); ?></h2></div>
                        <div class="mos-card-body">
                            <div class="op-qp-field">
                                <label for="op-qp-status"><?php echo esc_html( ph_t( 'qp_status_label', $lang ) ); ?></label>
                                <select id="op-qp-status" name="status" class="op-qp-select">
                                    <option value="publish"><?php echo esc_html( ph_t( 'qp_status_publish', $lang ) ); ?></option>
                                    <option value="draft"><?php echo esc_html( ph_t( 'qp_status_draft', $lang ) ); ?></option>
                                    <option value="pending"><?php echo esc_html( ph_t( 'qp_status_pending', $lang ) ); ?></option>
                                    <option value="private"><?php echo esc_html( ph_t( 'qp_status_private', $lang ) ); ?></option>
                                </select>
                            </div>
                            <div class="op-qp-field">
                                <label for="op-qp-visibility"><?php echo esc_html( ph_t( 'qp_visibility_label', $lang ) ); ?></label>
                                <select id="op-qp-visibility" name="catalog_visibility" class="op-qp-select">
                                    <option value="visible"><?php echo esc_html( ph_t( 'qp_visibility_visible', $lang ) ); ?></option>
                                    <option value="catalog"><?php echo esc_html( ph_t( 'qp_visibility_catalog', $lang ) ); ?></option>
                                    <option value="search"><?php echo esc_html( ph_t( 'qp_visibility_search', $lang ) ); ?></option>
                                    <option value="hidden"><?php echo esc_html( ph_t( 'qp_visibility_hidden', $lang ) ); ?></option>
                                </select>
                            </div>
                            <div class="op-qp-field op-qp-field-toggle-wrap">
                                <label class="op-qp-toggle-label">
                                    <input type="checkbox" id="op-qp-featured" name="featured" value="1" class="op-qp-toggle-check">
                                    <span class="op-qp-toggle"></span>
                                    <?php echo esc_html( ph_t( 'qp_featured_label', $lang ) ); ?>
                                </label>
                            </div>
                            <div class="op-qp-publish-actions">
                                <button type="submit" class="mos-btn mos-btn-primary op-qp-btn-full" id="op-qp-save-btn">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                    <?php echo esc_html( ph_t( 'qp_save_btn', $lang ) ); ?>
                                </button>
                                <a href="#" class="mos-btn op-qp-btn-full op-qp-hidden" id="op-qp-wc-edit-link" target="_blank" style="justify-content:center;text-align:center;">
                                    <?php echo esc_html( ph_t( 'qp_edit_in_wc', $lang ) ); ?>
                                </a>
                            </div>
                        </div>
                    </section>

                    <!-- Productafbeelding -->
                    <section class="mos-card">
                        <div class="mos-card-header"><h2><?php echo esc_html( ph_t( 'qp_section_image', $lang ) ); ?></h2></div>
                        <div class="mos-card-body">
                            <input type="hidden" id="op-qp-image-id" name="image_id" value="0">
                            <div class="op-qp-image-box" id="op-qp-image-box">
                                <div class="op-qp-image-placeholder" id="op-qp-image-placeholder">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                    <span><?php echo esc_html( ph_t( 'qp_image_click', $lang ) ); ?></span>
                                </div>
                                <img id="op-qp-image-preview" src="" alt="" class="op-qp-hidden">
                            </div>
                            <div class="op-qp-image-actions">
                                <button type="button" class="mos-btn mos-btn-sm" id="op-qp-select-image">
                                    <?php echo esc_html( ph_t( 'qp_select_image_btn', $lang ) ); ?>
                                </button>
                                <button type="button" class="mos-btn mos-btn-sm op-qp-hidden" id="op-qp-remove-image">
                                    <?php echo esc_html( ph_t( 'qp_remove_image_btn', $lang ) ); ?>
                                </button>
                            </div>
                        </div>
                    </section>

                    <!-- Productgalerij -->
                    <section class="mos-card">
                        <div class="mos-card-header"><h2><?php echo esc_html( ph_t( 'qp_section_gallery', $lang ) ); ?></h2></div>
                        <div class="mos-card-body">
                            <div class="op-qp-gallery-grid" id="op-qp-gallery-grid"></div>
                            <input type="hidden" id="op-qp-gallery-ids" name="gallery_ids" value="">
                            <button type="button" class="mos-btn mos-btn-sm" id="op-qp-add-gallery" style="margin-top:10px;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                <?php echo esc_html( ph_t( 'qp_add_gallery_btn', $lang ) ); ?>
                            </button>
                        </div>
                    </section>

                    <!-- Categorieën -->
                    <section class="mos-card">
                        <div class="mos-card-header"><h2><?php echo esc_html( ph_t( 'qp_section_categories', $lang ) ); ?></h2></div>
                        <div class="mos-card-body op-qp-checklist-wrap">
                            <?php foreach ( $cats as $cat ) : ?>
                                <label class="op-qp-check-item">
                                    <input type="checkbox" name="categories[]" value="<?php echo esc_attr( $cat->term_id ); ?>" class="op-qp-cat-check">
                                    <?php echo esc_html( $cat->name ); ?>
                                    <span class="op-qp-check-count"><?php echo esc_html( $cat->count ); ?></span>
                                </label>
                            <?php endforeach; ?>
                            <?php if ( empty( $cats ) ) : ?>
                                <p style="color:var(--mos-muted);font-size:13px;margin:0;"><?php echo esc_html( ph_t( 'qp_no_categories', $lang ) ); ?></p>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- Tags -->
                    <section class="mos-card">
                        <div class="mos-card-header"><h2><?php echo esc_html( ph_t( 'qp_section_tags', $lang ) ); ?></h2></div>
                        <div class="mos-card-body">
                            <input type="text" id="op-qp-tag-input" class="op-qp-input" placeholder="<?php echo esc_attr( ph_t( 'qp_tag_placeholder', $lang ) ); ?>">
                            <div class="op-qp-tags-selected" id="op-qp-tags-selected"></div>
                            <input type="hidden" id="op-qp-tag-ids" name="tags" value="">
                            <datalist id="op-qp-tags-list">
                                <?php foreach ( $tags as $tag ) : ?>
                                    <option data-id="<?php echo esc_attr( $tag->term_id ); ?>" value="<?php echo esc_attr( $tag->name ); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                    </section>

                    <!-- Merken -->
                    <?php if ( ! empty( $brands ) ) : ?>
                    <section class="mos-card">
                        <div class="mos-card-header"><h2><?php echo esc_html( ph_t( 'qp_section_brands', $lang ) ); ?></h2></div>
                        <div class="mos-card-body op-qp-checklist-wrap">
                            <?php foreach ( $brands as $brand ) : ?>
                                <label class="op-qp-check-item">
                                    <input type="checkbox" name="brands[]" value="<?php echo esc_attr( $brand->term_id ); ?>" class="op-qp-brand-check">
                                    <?php echo esc_html( $brand->name ); ?>
                                    <span class="op-qp-check-count"><?php echo esc_html( $brand->count ); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    <!-- Attributen -->
                    <?php if ( ! empty( $attributes_data ) ) : ?>
                    <section class="mos-card">
                        <div class="mos-card-header"><h2><?php echo esc_html( ph_t( 'qp_section_attributes', $lang ) ); ?></h2></div>
                        <div class="mos-card-body op-qp-attributes-wrap">
                            <?php foreach ( $attributes_data as $attr ) : ?>
                                <?php if ( empty( $attr['terms'] ) ) continue; ?>
                                <div class="op-qp-attr-group" data-attr="<?php echo esc_attr( $attr['name'] ); ?>">
                                    <div class="op-qp-attr-label"><?php echo esc_html( $attr['label'] ); ?></div>
                                    <div class="op-qp-attr-terms">
                                        <?php foreach ( $attr['terms'] as $term ) : ?>
                                            <label class="op-qp-attr-chip">
                                                <input type="checkbox"
                                                       name="attr_<?php echo esc_attr( $attr['name'] ); ?>[]"
                                                       value="<?php echo esc_attr( $term->term_id ); ?>"
                                                       class="op-qp-attr-check"
                                                       data-attr="<?php echo esc_attr( $attr['name'] ); ?>">
                                                <?php echo esc_html( $term->name ); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>

                </div><!-- .op-qp-editor-sidebar -->

            </div><!-- .op-qp-editor-layout -->
        </form>
    </div><!-- #op-qp-panel-editor -->

</div><!-- #mos-panel-products -->
