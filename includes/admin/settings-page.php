<?php
/**
 * Product Haven — Admin pagina HTML
 *
 * This file is included inside ph_render_admin_page(), so all variables
 * declared here are effectively scoped to that function's include context.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 *
 * @package ProductHaven
 */

defined( 'ABSPATH' ) || exit;

$opts    = get_option( 'ph_options', [] );
$period  = absint( $opts['default_period'] ?? 30 );
$saved   = isset( $_GET['ph_saved'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$accent  = sanitize_hex_color( $opts['accent_color'] ?? '' ) ?: '#10B981';
$lang    = ph_get_lang();

$statuses = [
    'wc-pending'    => ph_t( 'wc_status_pending',    $lang ),
    'wc-processing' => ph_t( 'wc_status_processing', $lang ),
    'wc-on-hold'    => ph_t( 'wc_status_on_hold',    $lang ),
    'wc-completed'  => ph_t( 'wc_status_completed',  $lang ),
    'wc-cancelled'  => ph_t( 'wc_status_cancelled',  $lang ),
    'wc-refunded'   => ph_t( 'wc_status_refunded',   $lang ),
    'wc-failed'     => ph_t( 'wc_status_failed',     $lang ),
];
?>
<style>
    #ph-app { --ph-accent: <?php echo esc_attr( $accent ); ?>; --ph-accent-dark: <?php echo esc_attr( $accent ); ?>cc; }
</style>
<div class="ph-wrap" id="ph-app">

    <!-- ===== HEADER ===== -->
    <header class="ph-header">
        <div class="ph-header-left">
            <div class="ph-logo">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                </svg>
            </div>
            <div>
                <h1 class="ph-title">Product Haven</h1>
                <p class="ph-subtitle"><?php echo esc_html( ph_t( 'subtitle', $lang ) ); ?></p>
            </div>
        </div>
        <div class="ph-header-right">
            <!-- Language Switcher -->
            <div class="ph-lang-switcher">
                <button class="ph-lang-btn <?php echo $lang === 'nl' ? 'is-active' : ''; ?>" data-lang="nl" title="<?php echo esc_attr( ph_t( 'switch_to_nl', $lang ) ); ?>">🇳🇱 NL</button>
                <button class="ph-lang-btn <?php echo $lang === 'en' ? 'is-active' : ''; ?>" data-lang="en" title="<?php echo esc_attr( ph_t( 'switch_to_en', $lang ) ); ?>">🇬🇧 EN</button>
                <button class="ph-lang-btn <?php echo $lang === 'de' ? 'is-active' : ''; ?>" data-lang="de" title="<?php echo esc_attr( ph_t( 'switch_to_de', $lang ) ); ?>">🇩🇪 DE</button>
                <button class="ph-lang-btn <?php echo $lang === 'fr' ? 'is-active' : ''; ?>" data-lang="fr" title="<?php echo esc_attr( ph_t( 'switch_to_fr', $lang ) ); ?>">🇫🇷 FR</button>
                <button class="ph-lang-btn <?php echo $lang === 'es' ? 'is-active' : ''; ?>" data-lang="es" title="<?php echo esc_attr( ph_t( 'switch_to_es', $lang ) ); ?>">🇪🇸 ES</button>
                <button class="ph-lang-btn <?php echo $lang === 'pt' ? 'is-active' : ''; ?>" data-lang="pt" title="<?php echo esc_attr( ph_t( 'switch_to_pt', $lang ) ); ?>">🇵🇹 PT</button>
                <button class="ph-lang-btn <?php echo $lang === 'it' ? 'is-active' : ''; ?>" data-lang="it" title="<?php echo esc_attr( ph_t( 'switch_to_it', $lang ) ); ?>">🇮🇹 IT</button>
            </div>
            <div class="ph-period-selector" id="ph-period-selector">
                <?php foreach ( [ 7 => '7d', 14 => '14d', 30 => '30d', 90 => '90d', 365 => '1y' ] as $d => $label ) : ?>
                    <button class="ph-period-btn <?php echo $d === $period ? 'is-active' : ''; ?>"
                            data-days="<?php echo absint( $d ); ?>">
                        <?php echo esc_html( $label ); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <button class="ph-refresh-btn" id="ph-refresh-btn" title="<?php echo esc_attr( ph_t( 'refresh_title', $lang ) ); ?>">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 4 23 10 17 10"/>
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                </svg>
                <?php echo esc_html( ph_t( 'refresh', $lang ) ); ?>
            </button>
            <button class="ph-export-btn" id="ph-export-btn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.2" stroke-linecap="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                <?php echo esc_html( ph_t( 'csv_export', $lang ) ); ?>
            </button>
        </div>
    </header>

    <!-- ===== TABS ===== -->
    <nav class="ph-tabs" id="ph-tabs">
        <button class="ph-tab is-active" data-tab="dashboard">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
            <?php echo esc_html( ph_t( 'tab_dashboard', $lang ) ); ?>
        </button>
        <button class="ph-tab" data-tab="revenue">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            <?php echo esc_html( ph_t( 'tab_revenue', $lang ) ); ?>
        </button>
        <button class="ph-tab" data-tab="categories">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
            <?php echo esc_html( ph_t( 'tab_categories', $lang ) ); ?>
        </button>
        <button class="ph-tab" data-tab="coupons">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            <?php echo esc_html( ph_t( 'tab_coupons', $lang ) ); ?>
        </button>
        <button class="ph-tab" data-tab="daily">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <?php echo esc_html( ph_t( 'tab_daily', $lang ) ); ?>
        </button>
        <button class="ph-tab" data-tab="customers">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <?php echo esc_html( ph_t( 'tab_customers', $lang ) ); ?>
        </button>
        <button class="ph-tab" data-tab="stock">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            <?php echo esc_html( ph_t( 'tab_stock', $lang ) ); ?>
        </button>
        <button class="ph-tab" data-tab="products">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
            <?php echo esc_html( ph_t( 'tab_products', $lang ) ); ?>
        </button>
        <button class="ph-tab" data-tab="sequential">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            <?php echo esc_html( ph_t( 'tab_sequential', $lang ) ); ?>
        </button>
        <button class="ph-tab" data-tab="settings">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
            <?php echo esc_html( ph_t( 'tab_settings', $lang ) ); ?>
        </button>
    </nav>

    <!-- ===== TAB: DASHBOARD ===== -->
    <div class="ph-tab-panel is-active" id="ph-panel-dashboard">

        <section class="ph-stats-grid" id="ph-stats-grid">
            <?php
            $cards = [
                [ 'key' => 'revenue',       'label' => ph_t( 'stat_revenue',       $lang ), 'icon' => 'M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6',        'prefix' => get_woocommerce_currency_symbol() ],
                [ 'key' => 'orders',        'label' => ph_t( 'stat_orders',        $lang ), 'icon' => 'M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4zM3 6h18M16 10a4 4 0 0 1-8 0', 'prefix' => '' ],
                [ 'key' => 'avg_order',     'label' => ph_t( 'stat_avg_order',     $lang ), 'icon' => 'M9 19v-6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2zm0 0V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v10m-6 0a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2m0 0V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v14a2 2 0 0 0-2 2h-2a2 2 0 0 0-2-2z', 'prefix' => get_woocommerce_currency_symbol() ],
                [ 'key' => 'new_customers', 'label' => ph_t( 'stat_new_customers', $lang ), 'icon' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm9 4h-6m3-3v6', 'prefix' => '' ],
            ];
            foreach ( $cards as $card ) : ?>
                <div class="ph-stat-card" data-stat="<?php echo esc_attr( $card['key'] ); ?>">
                    <div class="ph-stat-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="<?php echo esc_attr( $card['icon'] ); ?>"/>
                        </svg>
                    </div>
                    <div class="ph-stat-body">
                        <span class="ph-stat-label"><?php echo esc_html( $card['label'] ); ?></span>
                        <span class="ph-stat-value" data-prefix="<?php echo esc_attr( $card['prefix'] ); ?>">
                            <span class="ph-skeleton ph-skeleton-sm"></span>
                        </span>
                        <span class="ph-stat-sub"></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>

        <section class="ph-card ph-chart-card">
            <div class="ph-card-header">
                <h2><?php echo esc_html( ph_t( 'chart_revenue_orders', $lang ) ); ?></h2>
                <div class="ph-chart-toggles">
                    <button class="ph-toggle-btn is-active" data-dataset="revenue"><?php echo esc_html( ph_t( 'chart_revenue', $lang ) ); ?></button>
                    <button class="ph-toggle-btn" data-dataset="orders"><?php echo esc_html( ph_t( 'chart_orders', $lang ) ); ?></button>
                </div>
            </div>
            <div class="ph-chart-wrap">
                <canvas id="ph-main-chart"></canvas>
            </div>
        </section>

        <div class="ph-two-col">
            <section class="ph-card ph-timeline-card">
                <div class="ph-card-header">
                    <h2><?php echo esc_html( ph_t( 'order_timeline', $lang ) ); ?></h2>
                    <div class="ph-timeline-filters">
                        <input type="search" class="ph-search-input" id="ph-timeline-search"
                               placeholder="<?php echo esc_attr( ph_t( 'search_order_placeholder', $lang ) ); ?>">
                        <select class="ph-status-filter" id="ph-status-filter">
                            <option value="any"><?php echo esc_html( ph_t( 'all_statuses', $lang ) ); ?></option>
                            <?php foreach ( $statuses as $slug => $label ) : ?>
                                <option value="<?php echo esc_attr( str_replace( 'wc-', '', $slug ) ); ?>">
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="ph-timeline" id="ph-timeline">
                    <?php for ( $i = 0; $i < 5; $i++ ) : ?>
                        <div class="ph-skeleton-row">
                            <span class="ph-skeleton ph-skeleton-badge"></span>
                            <span class="ph-skeleton" style="width:120px"></span>
                            <span class="ph-skeleton" style="width:80px"></span>
                            <span class="ph-skeleton" style="width:60px"></span>
                        </div>
                    <?php endfor; ?>
                </div>
                <div class="ph-pagination" id="ph-pagination"></div>
            </section>

            <div class="ph-right-col">
                <?php if ( ! empty( $opts['show_top_products'] ?? '1' ) ) : ?>
                <section class="ph-card ph-top-products-card">
                    <div class="ph-card-header">
                        <h2><?php echo esc_html( ph_t( 'top_products', $lang ) ); ?></h2>
                        <div class="ph-top-products-controls">
                            <button class="ph-load-more-btn" id="ph-top-load-more" data-limit="5" data-step="5">
                                <?php echo esc_html( ph_t( 'load_more', $lang ) ); ?>
                            </button>
                        </div>
                    </div>
                    <ul class="ph-top-list" id="ph-top-products">
                        <?php for ( $i = 0; $i < 5; $i++ ) : ?>
                            <li class="ph-skeleton-row">
                                <span class="ph-skeleton ph-skeleton-rank"></span>
                                <span class="ph-skeleton" style="width:70%"></span>
                                <span class="ph-skeleton" style="width:40px"></span>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </section>
                <?php endif; ?>

                <!-- Low stock card -->
                <section class="ph-card ph-low-stock-card" id="ph-low-stock-card">
                    <div class="ph-card-header">
                        <h2>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:5px"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            <?php echo esc_html( ph_t( 'low_stock', $lang ) ); ?>
                        </h2>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <label class="ph-low-stock-threshold-label" style="font-size:12px;color:var(--ph-muted);">
                                <?php echo esc_html( ph_t( 'low_stock_threshold_label', $lang ) ); ?>
                                <input type="number" id="ph-low-stock-threshold" value="20" min="1" max="999"
                                       style="width:52px;padding:3px 6px;border:1.5px solid var(--ph-border);border-radius:7px;font-size:12px;font-family:inherit;">
                            </label>
                        </div>
                    </div>
                    <div id="ph-low-stock-body">
                        <?php for ( $i = 0; $i < 3; $i++ ) : ?>
                            <div class="ph-skeleton-row" style="padding:10px 20px;gap:10px;">
                                <span class="ph-skeleton" style="width:36px;height:36px;border-radius:8px;flex-shrink:0"></span>
                                <span class="ph-skeleton" style="width:60%"></span>
                                <span class="ph-skeleton" style="width:40px"></span>
                            </div>
                        <?php endfor; ?>
                    </div>
                </section>

            </div>
        </div>

    </div>

    <!-- ===== DELETE CONFIRM MODAL ===== -->
    <div class="ph-modal-backdrop" id="ph-delete-confirm-backdrop" hidden>
        <div class="ph-modal ph-delete-confirm-modal" role="dialog" aria-modal="true" style="max-width:420px;">
            <div class="ph-modal-header">
                <h3 style="display:flex;align-items:center;gap:10px;color:#EF4444;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                    <?php echo esc_html( ph_t( 'delete_order', $lang ) ); ?>
                </h3>
                <button class="ph-modal-close" id="ph-delete-confirm-close" aria-label="Close">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div style="padding:24px;">
                <p id="ph-delete-confirm-text" style="font-size:14px;line-height:1.6;color:var(--ph-text);margin:0 0 8px;"></p>
                <p style="font-size:12px;color:#EF4444;font-weight:600;margin:0 0 24px;">⚠ <?php echo esc_html( ph_t( 'irreversible', $lang ) ); ?></p>
                <div style="display:flex;gap:10px;justify-content:flex-end;">
                    <button class="ph-btn ph-btn-ghost" id="ph-delete-confirm-cancel"><?php echo esc_html( ph_t( 'cancel', $lang ) ); ?></button>
                    <button class="ph-btn ph-btn-danger" id="ph-delete-confirm-ok"><?php echo esc_html( ph_t( 'delete_permanent', $lang ) ); ?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== REFUND MODAL ===== -->
    <div class="ph-modal-backdrop" id="ph-refund-modal-backdrop" hidden>
        <div class="ph-modal" role="dialog" aria-modal="true" style="max-width:460px;">
            <div class="ph-modal-header">
                <h3 style="display:flex;align-items:center;gap:10px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/></svg>
                    <span id="ph-refund-modal-title"><?php echo esc_html( ph_t( 'refund_title', $lang ) ); ?></span>
                </h3>
                <button class="ph-modal-close" id="ph-refund-modal-close" aria-label="Close">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div style="padding:24px;">
                <input type="hidden" id="ph-refund-order-id">
                <p id="ph-refund-modal-desc" style="font-size:14px;line-height:1.6;color:var(--ph-text);margin:0 0 20px;"></p>

                <div id="ph-refund-options-wrap" style="margin-bottom:20px;">
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--ph-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;">
                        <?php echo esc_html( ph_t( 'refund_option_label', $lang ) ); ?>
                    </label>
                    <label style="display:flex;align-items:flex-start;gap:10px;margin-bottom:8px;cursor:pointer;">
                        <input type="radio" name="ph_refund_type" value="status_only" checked style="margin-top:3px;">
                        <span>
                            <strong><?php echo esc_html( ph_t( 'refund_status_only_title', $lang ) ); ?></strong><br>
                            <span style="font-size:12px;color:var(--ph-muted);"><?php echo esc_html( ph_t( 'refund_status_only_desc', $lang ) ); ?></span>
                        </span>
                    </label>
                    <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;">
                        <input type="radio" name="ph_refund_type" value="full_refund" style="margin-top:3px;">
                        <span>
                            <strong><?php echo esc_html( ph_t( 'refund_full_title', $lang ) ); ?></strong><br>
                            <span style="font-size:12px;color:var(--ph-muted);"><?php echo esc_html( ph_t( 'refund_full_desc', $lang ) ); ?></span>
                        </span>
                    </label>
                </div>

                <div id="ph-revert-options-wrap" style="margin-bottom:20px;display:none;">
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--ph-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;">
                        <?php echo esc_html( ph_t( 'revert_option_label', $lang ) ); ?>
                    </label>
                    <label style="display:flex;align-items:flex-start;gap:10px;margin-bottom:8px;cursor:pointer;">
                        <input type="radio" name="ph_revert_type" value="processing" checked style="margin-top:3px;">
                        <span>
                            <strong><?php echo esc_html( ph_t( 'revert_processing', $lang ) ); ?></strong>
                        </span>
                    </label>
                    <label style="display:flex;align-items:flex-start;gap:10px;margin-bottom:8px;cursor:pointer;">
                        <input type="radio" name="ph_revert_type" value="on-hold" style="margin-top:3px;">
                        <span>
                            <strong><?php echo esc_html( ph_t( 'revert_on_hold', $lang ) ); ?></strong>
                        </span>
                    </label>
                    <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;">
                        <input type="radio" name="ph_revert_type" value="completed" style="margin-top:3px;">
                        <span>
                            <strong><?php echo esc_html( ph_t( 'revert_completed', $lang ) ); ?></strong>
                        </span>
                    </label>
                    <label style="display:flex;align-items:flex-start;gap:10px;margin-top:14px;cursor:pointer;border-top:1px solid var(--ph-border);padding-top:14px;">
                        <input type="checkbox" id="ph-revert-delete-refunds" style="margin-top:3px;">
                        <span>
                            <strong><?php echo esc_html( ph_t( 'delete_refund_records', $lang ) ); ?></strong><br>
                            <span style="font-size:12px;color:#EF4444;"><?php echo esc_html( ph_t( 'delete_refund_records_desc', $lang ) ); ?></span>
                        </span>
                    </label>
                </div>

                <div style="display:flex;gap:10px;justify-content:flex-end;">
                    <button class="ph-btn ph-btn-ghost" id="ph-refund-modal-cancel"><?php echo esc_html( ph_t( 'cancel', $lang ) ); ?></button>
                    <button class="ph-btn ph-btn-primary" id="ph-refund-modal-confirm"><?php echo esc_html( ph_t( 'confirm', $lang ) ); ?></button>
                </div>
                <p id="ph-refund-modal-error" style="color:#EF4444;font-size:13px;margin:10px 0 0;text-align:right;display:none;"></p>
            </div>
        </div>
    </div>

    <!-- ===== STOCK EDIT MODAL ===== -->
    <div class="ph-modal-backdrop" id="ph-stock-edit-backdrop" hidden>
        <div class="ph-modal ph-stock-edit-modal" role="dialog" aria-modal="true">
            <div class="ph-modal-header">
                <h3 id="ph-stock-edit-title"><?php echo esc_html( ph_t( 'edit_stock', $lang ) ); ?></h3>
                <button class="ph-modal-close" id="ph-stock-edit-close" aria-label="Close">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="ph-modal-body" style="padding:24px;">
                <input type="hidden" id="ph-stock-edit-product-id">
                <div style="margin-bottom:16px;">
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--ph-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.05em">
                        <?php echo esc_html( ph_t( 'new_stock', $lang ) ); ?>
                    </label>
                    <input type="number" id="ph-stock-edit-qty" min="0" step="1"
                           style="width:100%;padding:10px 14px;border:1.5px solid var(--ph-border);border-radius:10px;font-size:16px;font-weight:700;font-family:inherit;">
                </div>
                <div style="margin-bottom:20px;">
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--ph-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.05em">
                        <?php echo esc_html( ph_t( 'reason_optional', $lang ) ); ?>
                    </label>
                    <input type="text" id="ph-stock-edit-reason" placeholder="<?php echo esc_attr( ph_t( 'reason_placeholder', $lang ) ); ?>"
                           style="width:100%;padding:9px 14px;border:1.5px solid var(--ph-border);border-radius:10px;font-size:13px;font-family:inherit;">
                </div>
                <div style="display:flex;gap:10px;justify-content:flex-end;">
                    <button class="ph-btn ph-btn-ghost" id="ph-stock-edit-cancel"><?php echo esc_html( ph_t( 'cancel', $lang ) ); ?></button>
                    <button class="ph-btn ph-btn-primary" id="ph-stock-edit-save"><?php echo esc_html( ph_t( 'save', $lang ) ); ?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== TAB: REVENUE ===== -->
    <div class="ph-tab-panel" id="ph-panel-revenue">
        <section class="ph-card">
            <div class="ph-card-header">
                <h2><?php echo esc_html( ph_t( 'revenue_overview', $lang ) ); ?></h2>
            </div>
            <div id="ph-revenue-report"><div class="ph-loading-spinner"></div></div>
        </section>
    </div>

    <!-- ===== TAB: CATEGORIES ===== -->
    <div class="ph-tab-panel" id="ph-panel-categories">
        <section class="ph-card">
            <div class="ph-card-header ph-card-header--dark">
                <div class="ph-categories-col-header"></div>
            </div>
            <div id="ph-categories-report"><div class="ph-loading-spinner"></div></div>
        </section>
    </div>

    <!-- ===== TAB: COUPONS ===== -->
    <div class="ph-tab-panel" id="ph-panel-coupons">
        <section class="ph-card">
            <div class="ph-card-header">
                <div class="ph-coupons-col-header"></div>
            </div>
            <div id="ph-coupons-report"><div class="ph-loading-spinner"></div></div>
        </section>
    </div>

    <!-- ===== TAB: DAILY REPORT ===== -->
    <div class="ph-tab-panel" id="ph-panel-daily">
        <section class="ph-card">
            <div class="ph-card-header">
                <h2><?php echo esc_html( ph_t( 'daily_report_title', $lang ) ); ?></h2>
            </div>
            <div style="padding: 0 24px 20px;">
                <div class="ph-daily-date-wrap">
                    <label for="ph-daily-date"><?php echo esc_html( ph_t( 'select_date', $lang ) ); ?></label>
                    <input type="date" id="ph-daily-date"
                           value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>"
                           max="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>">
                    <button class="ph-btn-secondary" id="ph-daily-load-btn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <?php echo esc_html( ph_t( 'load', $lang ) ); ?>
                    </button>
                </div>
            </div>
            <div id="ph-daily-report" style="padding: 0 24px 24px;"><div class="ph-loading-spinner"></div></div>
        </section>
    </div>

    <!-- ===== TAB: CUSTOMERS ===== -->
    <div class="ph-tab-panel" id="ph-panel-customers">
        <section class="ph-card">
            <div class="ph-card-header ph-card-header--light">
                <div class="ph-customers-col-header"></div>
            </div>
            <div id="ph-customers-report"><div class="ph-loading-spinner"></div></div>
        </section>
    </div>

    <!-- ===== TAB: STOCK ===== -->
    <?php
    $stock_opts      = get_option( 'ph_stock_options', [] );
    $stock_threshold = (int) ( $stock_opts['low_stock_threshold'] ?? 5 );
    $stock_statuses  = (array) ( $stock_opts['alert_statuses'] ?? [ 'low', 'out' ] );
    ?>
    <div class="ph-tab-panel" id="ph-panel-stock">

        <section class="ph-stock-summary" id="ph-stock-summary">
            <?php foreach ( [
                [ 'key' => 'out_of_stock',   'label' => ph_t( 'stock_out',   $lang ), 'color' => 'danger'  ],
                [ 'key' => 'low_stock',      'label' => ph_t( 'stock_low',   $lang ), 'color' => 'warning' ],
                [ 'key' => 'ok_stock',       'label' => ph_t( 'stock_ok',    $lang ), 'color' => 'ok'      ],
                [ 'key' => 'total_products', 'label' => ph_t( 'stock_total', $lang ), 'color' => 'neutral' ],
            ] as $c ) : ?>
            <div class="ph-stock-stat ph-stock-stat--<?php echo esc_attr( $c['color'] ); ?>">
                <span class="ph-stock-stat__value" data-stock-stat="<?php echo esc_attr( $c['key'] ); ?>">–</span>
                <span class="ph-stock-stat__label"><?php echo esc_html( $c['label'] ); ?></span>
            </div>
            <?php endforeach; ?>
        </section>

        <div class="ph-stock-toolbar">
            <div class="ph-stock-filter-btns">
                <?php foreach ( [
                    'all' => ph_t( 'filter_all', $lang ),
                    'out' => ph_t( 'filter_out', $lang ),
                    'low' => ph_t( 'filter_low', $lang ),
                    'ok'  => ph_t( 'filter_ok',  $lang ),
                ] as $val => $label ) : ?>
                <button class="ph-stock-filter <?php echo $val === 'all' ? 'is-active' : ''; ?>" data-status="<?php echo esc_attr( $val ); ?>">
                    <?php echo esc_html( $label ); ?>
                </button>
                <?php endforeach; ?>
            </div>
            <div class="ph-stock-search-wrap">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="search" id="ph-stock-search" class="ph-stock-search" placeholder="<?php echo esc_attr( ph_t( 'search_stock_placeholder', $lang ) ); ?>">
            </div>
            <div class="ph-stock-header-btns">
                <button class="ph-btn ph-btn-sm" id="ph-stock-export-btn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    CSV
                </button>
                <button class="ph-btn ph-btn-sm" id="ph-stock-settings-btn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <?php echo esc_html( ph_t( 'settings_btn', $lang ) ); ?>
                </button>
            </div>
            <div class="ph-stock-bulk" id="ph-stock-bulk" hidden>
                <span id="ph-stock-selected-count"><?php echo esc_html( ph_t( 'selected_count', $lang ) ); ?></span>
                <button class="ph-btn ph-btn-sm" id="ph-stock-bulk-update"><?php echo esc_html( ph_t( 'bulk_update_stock', $lang ) ); ?></button>
            </div>
        </div>

        <section class="ph-card" style="padding:0;overflow:hidden;">
            <table class="ph-stock-table" id="ph-stock-table">
                <thead>
                    <tr>
                        <th style="width:44px"><input type="checkbox" id="ph-stock-select-all"></th>
                        <th><?php echo esc_html( ph_t( 'col_product',     $lang ) ); ?></th>
                        <th><?php echo esc_html( ph_t( 'col_sku',         $lang ) ); ?></th>
                        <th class="ph-stock-sortable is-sorted" data-col="stock"><?php echo esc_html( ph_t( 'col_stock', $lang ) ); ?> <svg class="ph-sort-icon" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></th>
                        <th><?php echo esc_html( ph_t( 'col_status',      $lang ) ); ?></th>
                        <th class="ph-stock-sortable" data-col="price"><?php echo esc_html( ph_t( 'col_price', $lang ) ); ?> <svg class="ph-sort-icon" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></th>
                        <th class="ph-stock-sortable" data-col="stock_value"><?php echo esc_html( ph_t( 'col_stock_value', $lang ) ); ?> <svg class="ph-sort-icon" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></th>
                        <th><?php echo esc_html( ph_t( 'col_actions',     $lang ) ); ?></th>
                    </tr>
                </thead>
                <tbody id="ph-stock-tbody">
                    <tr><td colspan="8" style="padding:40px;text-align:center"><div class="ph-loading-spinner"></div></td></tr>
                </tbody>
            </table>
            <div id="ph-stock-empty" hidden style="padding:56px 32px;text-align:center;color:#94A3B8">
                <p style="margin:0;font-size:15px"><?php echo esc_html( ph_t( 'no_products_found', $lang ) ); ?></p>
            </div>
        </section>

        <div class="ph-stock-pagination" id="ph-stock-pagination"></div>

        <div class="ph-stock-settings-overlay" id="ph-stock-settings-overlay" hidden></div>
        <div class="ph-stock-settings-panel" id="ph-stock-settings-panel" hidden>
            <div class="ph-stock-settings-header">
                <h2><?php echo esc_html( ph_t( 'stock_settings', $lang ) ); ?></h2>
                <button class="ph-modal-close" id="ph-stock-settings-close">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="ph-stock-settings-body">
                <div class="ph-setting-row">
                    <label for="ph-set-threshold"><strong><?php echo esc_html( ph_t( 'low_stock_threshold', $lang ) ); ?></strong><br><small><?php echo esc_html( ph_t( 'low_stock_threshold_desc', $lang ) ); ?></small></label>
                    <input type="number" id="ph-set-threshold" class="ph-input-sm" min="1" max="9999" value="<?php echo esc_attr( $stock_threshold ); ?>">
                </div>
                <div class="ph-setting-row">
                    <label for="ph-set-email"><strong><?php echo esc_html( ph_t( 'alert_email', $lang ) ); ?></strong></label>
                    <input type="email" id="ph-set-email" class="ph-input-sm" value="<?php echo esc_attr( $stock_opts['alert_email'] ?? get_option( 'admin_email' ) ); ?>">
                </div>
                <div class="ph-setting-row ph-setting-full">
                    <span class="ph-setting-group-label"><?php echo esc_html( ph_t( 'alert_when', $lang ) ); ?></span>
                    <div class="ph-toggles-row">
                        <label class="ph-toggle-label"><input type="checkbox" id="ph-set-alert-out" <?php checked( in_array( 'out', $stock_statuses, true ) ); ?>> <?php echo esc_html( ph_t( 'alert_out_stock', $lang ) ); ?></label>
                        <label class="ph-toggle-label"><input type="checkbox" id="ph-set-alert-low" <?php checked( in_array( 'low', $stock_statuses, true ) ); ?>> <?php echo esc_html( ph_t( 'alert_low_stock', $lang ) ); ?></label>
                    </div>
                </div>
                <div class="ph-setting-row ph-setting-full">
                    <span class="ph-setting-group-label"><?php echo esc_html( ph_t( 'alerts_label', $lang ) ); ?></span>
                    <div class="ph-toggles-row">
                        <label class="ph-toggle-label"><input type="checkbox" id="ph-set-realtime" <?php checked( ( $stock_opts['realtime_alerts'] ?? '1' ) === '1' ); ?>> <?php echo esc_html( ph_t( 'realtime_alert', $lang ) ); ?></label>
                        <label class="ph-toggle-label"><input type="checkbox" id="ph-set-daily" <?php checked( ( $stock_opts['daily_digest'] ?? '1' ) === '1' ); ?>> <?php echo esc_html( ph_t( 'daily_digest', $lang ) ); ?></label>
                    </div>
                </div>
            </div>
            <div class="ph-stock-settings-footer">
                <button class="ph-btn ph-btn-sm" id="ph-stock-test-alert"><?php echo esc_html( ph_t( 'send_test_alert', $lang ) ); ?></button>
                <button class="ph-btn ph-btn-sm ph-btn-primary" id="ph-stock-save-settings"><?php echo esc_html( ph_t( 'save_btn', $lang ) ); ?></button>
            </div>
        </div>

        <div id="ph-stock-edit-backdrop" class="ph-modal-backdrop" hidden>
            <div class="ph-modal" id="ph-stock-edit-modal" role="dialog" style="width:420px;max-width:calc(100vw - 40px)">
                <div class="ph-modal-header">
                    <div><h2 class="ph-modal-title" id="ph-stock-modal-title"><?php echo esc_html( ph_t( 'stock_update_title', $lang ) ); ?></h2></div>
                    <div class="ph-modal-actions">
                        <button class="ph-modal-close" id="ph-stock-modal-close">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                </div>
                <div class="ph-modal-body" style="display:flex;flex-direction:column;gap:16px;">
                    <input type="hidden" id="ph-stock-edit-id">
                    <div>
                        <label style="font-size:13px;font-weight:700;display:block;margin-bottom:6px"><?php echo esc_html( ph_t( 'new_stock_qty', $lang ) ); ?></label>
                        <input type="number" id="ph-stock-edit-qty" class="ph-input-sm" style="width:100%;font-size:22px;font-weight:700;padding:12px 16px" min="0" step="1" placeholder="0">
                    </div>
                    <div>
                        <label style="font-size:13px;font-weight:700;display:block;margin-bottom:6px"><?php echo esc_html( ph_t( 'reason_optional', $lang ) ); ?></label>
                        <input type="text" id="ph-stock-edit-reason" class="ph-input-sm" style="width:100%" placeholder="<?php echo esc_attr( ph_t( 'reason_placeholder2', $lang ) ); ?>">
                    </div>
                </div>
                <div style="display:flex;gap:10px;justify-content:flex-end;padding:16px 24px;border-top:1.5px solid #E2E8F0">
                    <button class="ph-btn ph-btn-sm" id="ph-stock-modal-cancel"><?php echo esc_html( ph_t( 'cancel', $lang ) ); ?></button>
                    <button class="ph-btn ph-btn-sm ph-btn-primary" id="ph-stock-modal-save"><?php echo esc_html( ph_t( 'save_btn', $lang ) ); ?></button>
                </div>
            </div>
        </div>

    </div>

    <!-- ===== TAB: QUICK PRODUCTS ===== -->
    <?php
    require_once PH_PATH . 'includes/quick-products.php';
    require PH_PATH . 'includes/partials/quick-products-tab.php';
    ?>

    <!-- ===== TAB: SEQUENTIAL ORDERS ===== -->
    <?php
    require_once PH_PATH . 'includes/sequential-orders.php';
    require PH_PATH . 'includes/partials/sequential-orders-tab.php';
    ?>

    <!-- ===== TAB: SETTINGS ===== -->
    <div class="ph-tab-panel" id="ph-panel-settings">
        <section class="ph-card ph-settings-card">
            <?php if ( $saved ) : ?>
                <div class="ph-notice ph-notice-success">
                    ✓ <?php echo esc_html( ph_t( 'settings_saved', $lang ) ); ?>
                </div>
            <?php endif; ?>
            <form method="post">
                <?php wp_nonce_field( 'ph_save_settings', 'ph_nonce' ); ?>
                <div class="ph-settings-grid">

                    <div class="ph-setting-row">
                        <label for="ph_default_period"><?php echo esc_html( ph_t( 'default_period', $lang ) ); ?></label>
                        <input type="number" id="ph_default_period" name="default_period"
                               value="<?php echo esc_attr( $opts['default_period'] ?? 30 ); ?>" min="1" max="365" class="ph-input-sm">
                    </div>

                    <div class="ph-setting-row">
                        <label for="ph_chart_type"><?php echo esc_html( ph_t( 'chart_type', $lang ) ); ?></label>
                        <select id="ph_chart_type" name="chart_type" class="ph-input-sm">
                            <option value="line" <?php selected( $opts['chart_type'] ?? 'line', 'line' ); ?>><?php echo esc_html( ph_t( 'chart_line', $lang ) ); ?></option>
                            <option value="bar"  <?php selected( $opts['chart_type'] ?? 'line', 'bar' ); ?>><?php echo esc_html( ph_t( 'chart_bar', $lang ) ); ?></option>
                        </select>
                    </div>

                    <div class="ph-setting-row">
                        <label for="ph_items_per_page"><?php echo esc_html( ph_t( 'orders_per_page', $lang ) ); ?></label>
                        <input type="number" id="ph_items_per_page" name="items_per_page"
                               value="<?php echo esc_attr( $opts['items_per_page'] ?? 20 ); ?>" min="5" max="100" class="ph-input-sm">
                    </div>

                    <div class="ph-setting-row">
                        <label for="ph_accent_color"><?php echo esc_html( ph_t( 'accent_color', $lang ) ); ?></label>
                        <input type="color" id="ph_accent_color" name="accent_color"
                               value="<?php echo esc_attr( $opts['accent_color'] ?? '#10B981' ); ?>">
                    </div>

                    <div class="ph-setting-row ph-setting-full">
                        <span class="ph-setting-group-label"><?php echo esc_html( ph_t( 'show_sections', $lang ) ); ?></span>
                        <div class="ph-toggles-row">
                            <?php
                            $toggles = [
                                'show_avg_order'    => ph_t( 'toggle_avg_order',    $lang ),
                                'show_top_products' => ph_t( 'toggle_top_products', $lang ),
                                'show_returning'    => ph_t( 'toggle_returning',    $lang ),
                            ];
                            foreach ( $toggles as $key => $label ) : ?>
                                <label class="ph-toggle-label">
                                    <input type="checkbox" name="<?php echo esc_attr( $key ); ?>" value="1"
                                        <?php checked( '1', $opts[ $key ] ?? '1' ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="ph-setting-row ph-setting-full">
                        <span class="ph-setting-group-label"><?php echo esc_html( ph_t( 'csv_columns', $lang ) ); ?></span>
                        <div class="ph-toggles-row">
                            <?php
                            $cols = [
                                'id'       => ph_t( 'col_id', $lang ),
                                'date'     => ph_t( 'col_date', $lang ),
                                'status'   => ph_t( 'col_status_csv', $lang ),
                                'customer' => ph_t( 'col_customer', $lang ),
                                'email'    => 'E-mail',
                                'city'     => ph_t( 'col_city', $lang ),
                                'total'    => ph_t( 'col_total', $lang ),
                                'items'    => ph_t( 'col_items', $lang ),
                                'payment'  => ph_t( 'col_payment', $lang ),
                            ];
                            $saved_cols = (array) ( $opts['export_columns'] ?? [ 'id','date','status','customer','total','items' ] );
                            foreach ( $cols as $val => $lbl ) : ?>
                                <label class="ph-toggle-label">
                                    <input type="checkbox" name="export_columns[]" value="<?php echo esc_attr( $val ); ?>"
                                        <?php checked( in_array( $val, $saved_cols, true ) ); ?>>
                                    <?php echo esc_html( $lbl ); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="ph-setting-row ph-setting-full">
                        <label class="ph-toggle-label" style="gap:10px;">
                            <input type="checkbox" name="enable_rest_api" value="1"
                                <?php checked( '1', $opts['enable_rest_api'] ?? '' ); ?>>
                            <?php echo esc_html( ph_t( 'enable_rest_api', $lang ) ); ?>
                        </label>
                    </div>

                </div>
                <div class="ph-settings-footer">
                    <button type="submit" name="ph_save" class="ph-save-btn">
                        <?php echo esc_html( ph_t( 'save_btn', $lang ) ); ?>
                    </button>
                </div>
            </form>
        </section>
    </div>

    <!-- ===== CUSTOMER CARD MODAL ===== -->
    <div class="ph-modal-backdrop" id="ph-customer-backdrop" hidden>
        <div class="ph-modal ph-customer-modal" id="ph-customer-panel" role="dialog" aria-modal="true">
            <div class="ph-modal-header">
                <div>
                    <h2 class="ph-modal-title"><?php echo esc_html( ph_t( 'customer_card', $lang ) ); ?></h2>
                </div>
                <div class="ph-modal-actions">
                    <button class="ph-modal-close" id="ph-panel-close">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="ph-modal-body ph-customer-panel-body" id="ph-customer-body">
                <div class="ph-loading-spinner"></div>
            </div>
        </div>
    </div>

    <!-- ===== ORDER DETAIL MODAL ===== -->
    <div class="ph-modal-backdrop" id="ph-modal-backdrop" hidden>
        <div class="ph-modal" id="ph-order-modal" role="dialog" aria-modal="true">
            <div class="ph-modal-header">
                <div>
                    <h2 class="ph-modal-title" id="ph-modal-title">Order #<span id="ph-modal-number"></span></h2>
                    <span class="ph-status-badge" id="ph-modal-status"></span>
                </div>
                <div class="ph-modal-actions">
                    <button class="ph-modal-edit-link" id="ph-modal-edit-btn" type="button">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        <?php echo esc_html( ph_t( 'edit_btn', $lang ) ); ?>
                    </button>
                    <button class="ph-modal-close" id="ph-modal-close">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="ph-modal-body" id="ph-modal-body">
                <div class="ph-loading-spinner"></div>
            </div>
        </div>
    </div>

    <!-- ===== ORDER EDIT MODAL ===== -->
    <div class="ph-modal-backdrop" id="ph-order-edit-backdrop" hidden>
        <div class="ph-modal ph-order-edit-modal" role="dialog" aria-modal="true">
            <div class="ph-modal-header">
                <div>
                    <h2 class="ph-modal-title"><?php echo esc_html( ph_t( 'edit_order', $lang ) ); ?> <span id="ph-order-edit-number"></span></h2>
                </div>
                <div class="ph-modal-actions">
                    <button class="ph-modal-close" id="ph-order-edit-close">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="ph-modal-body">
                <input type="hidden" id="ph-order-edit-id">

                <div class="ph-modal-section">
                    <div class="ph-modal-section-title"><?php echo esc_html( ph_t( 'billing_address', $lang ) ); ?></div>
                    <div class="ph-order-edit-grid">
                        <label class="ph-order-edit-field">
                            <span><?php echo esc_html( ph_t( 'first_name', $lang ) ); ?></span>
                            <input type="text" id="ph-oe-first-name" name="billing_first_name" autocomplete="off">
                        </label>
                        <label class="ph-order-edit-field">
                            <span><?php echo esc_html( ph_t( 'last_name', $lang ) ); ?></span>
                            <input type="text" id="ph-oe-last-name" name="billing_last_name" autocomplete="off">
                        </label>
                        <label class="ph-order-edit-field">
                            <span><?php echo esc_html( ph_t( 'company', $lang ) ); ?></span>
                            <input type="text" id="ph-oe-company" name="billing_company" autocomplete="off">
                        </label>
                        <label class="ph-order-edit-field">
                            <span><?php echo esc_html( ph_t( 'email', $lang ) ); ?></span>
                            <input type="email" id="ph-oe-email" name="billing_email" autocomplete="off">
                        </label>
                        <label class="ph-order-edit-field">
                            <span><?php echo esc_html( ph_t( 'phone', $lang ) ); ?></span>
                            <input type="text" id="ph-oe-phone" name="billing_phone" autocomplete="off">
                        </label>
                        <label class="ph-order-edit-field">
                            <span><?php echo esc_html( ph_t( 'address1', $lang ) ); ?></span>
                            <input type="text" id="ph-oe-address1" name="billing_address_1" autocomplete="off">
                        </label>
                        <label class="ph-order-edit-field">
                            <span><?php echo esc_html( ph_t( 'address2', $lang ) ); ?></span>
                            <input type="text" id="ph-oe-address2" name="billing_address_2" autocomplete="off">
                        </label>
                        <label class="ph-order-edit-field">
                            <span><?php echo esc_html( ph_t( 'city', $lang ) ); ?></span>
                            <input type="text" id="ph-oe-city" name="billing_city" autocomplete="off">
                        </label>
                        <label class="ph-order-edit-field">
                            <span><?php echo esc_html( ph_t( 'postcode', $lang ) ); ?></span>
                            <input type="text" id="ph-oe-postcode" name="billing_postcode" autocomplete="off">
                        </label>
                        <label class="ph-order-edit-field">
                            <span><?php echo esc_html( ph_t( 'country', $lang ) ); ?></span>
                            <input type="text" id="ph-oe-country" name="billing_country" autocomplete="off" maxlength="2" placeholder="NL">
                        </label>
                    </div>
                </div>

                <div class="ph-modal-section">
                    <div class="ph-modal-section-title"><?php echo esc_html( ph_t( 'internal_note', $lang ) ); ?></div>
                    <textarea id="ph-oe-note" class="ph-note-input" placeholder="<?php echo esc_attr( ph_t( 'note_placeholder', $lang ) ); ?>" rows="3"></textarea>
                </div>

                <div class="ph-order-edit-footer">
                    <p id="ph-order-edit-error" style="display:none;color:#EF4444;font-size:13px;margin:0;"></p>
                    <button type="button" class="ph-btn-secondary" id="ph-order-edit-cancel"><?php echo esc_html( ph_t( 'cancel', $lang ) ); ?></button>
                    <button type="button" class="ph-note-submit" id="ph-order-edit-save"><?php echo esc_html( ph_t( 'save_btn', $lang ) ); ?></button>
                </div>
            </div>
        </div>
    </div>

</div>