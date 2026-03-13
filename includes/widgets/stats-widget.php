<?php
/**
 * Product Haven — Elementor Stats Widget
 * Toont omzet, orderaantallen en gem. orderwaarde op de frontend.
 *
 * @package ProductHaven
 */

namespace ProductHaven;

defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class Stats_Widget extends Widget_Base {

    public function get_name(): string   { return 'ph_order_stats'; }
    public function get_title(): string  { return __( 'Order Stats', 'product-haven' ); }
    public function get_icon(): string   { return 'eicon-counter'; }
    public function get_categories(): array { return [ 'ph-category' ]; }
    public function get_keywords(): array   { return [ 'order', 'stats', 'woocommerce', 'omzet', 'op' ]; }

    protected function register_controls(): void {

        /* ---- Inhoud ---- */
        $this->start_controls_section( 'section_content', [
            'label' => __( 'Inhoud', 'product-haven' ),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'period_days', [
            'label'   => __( 'Periode (dagen)', 'product-haven' ),
            'type'    => Controls_Manager::NUMBER,
            'default' => 30,
            'min'     => 1,
            'max'     => 365,
        ] );

        $this->add_control( 'show_revenue', [
            'label'        => __( 'Toon omzet', 'product-haven' ),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'return_value' => 'yes',
        ] );

        $this->add_control( 'show_orders', [
            'label'        => __( 'Toon orderaantal', 'product-haven' ),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'return_value' => 'yes',
        ] );

        $this->add_control( 'show_avg', [
            'label'        => __( 'Toon gem. orderwaarde', 'product-haven' ),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'return_value' => 'yes',
        ] );

        $this->add_control( 'show_chart', [
            'label'        => __( 'Toon minikraafiek', 'product-haven' ),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'return_value' => 'yes',
        ] );

        $this->end_controls_section();

        /* ---- Stijl ---- */
        $this->start_controls_section( 'section_style', [
            'label' => __( 'Stijl', 'product-haven' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'accent_color', [
            'label'     => __( 'Accentkleur', 'product-haven' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#10B981',
            'selectors' => [
                '{{WRAPPER}} .mopf-accent' => 'color: {{VALUE}};',
                '{{WRAPPER}} .mopf-stat-icon' => 'background: color-mix(in srgb, {{VALUE}} 15%, transparent); color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'card_bg', [
            'label'     => __( 'Kaart achtergrond', 'product-haven' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => [ '{{WRAPPER}} .mopf-stat-card' => 'background: {{VALUE}};' ],
        ] );

        $this->add_control( 'card_radius', [
            'label'      => __( 'Afronding', 'product-haven' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [ 'px' => [ 'min' => 0, 'max' => 32 ] ],
            'default'    => [ 'size' => 16, 'unit' => 'px' ],
            'selectors'  => [ '{{WRAPPER}} .mopf-stat-card' => 'border-radius: {{SIZE}}{{UNIT}};' ],
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'value_typography',
            'label'    => __( 'Waarde typografie', 'product-haven' ),
            'selector' => '{{WRAPPER}} .mopf-stat-value',
        ] );

        $this->end_controls_section();
    }

    protected function render(): void {
        $s       = $this->get_settings_for_display();
        $is_edit = \Elementor\Plugin::$instance->editor->is_edit_mode();
        self::render_output( $s, $is_edit );
    }

    /**
     * Gedeelde render-logica — aangeroepen door Elementor én door de shortcode.
     *
     * @param array $settings  Widget-instellingen (of shortcode-atts als array).
     * @param bool  $is_editor True als we in de Elementor editor zitten.
     */
    public static function render_output( array $settings, bool $is_editor = false ): void {
        $s        = $settings;
        $days     = absint( $s['period_days'] ?? 30 );
        $is_edit  = $is_editor;

        /* ---- Editor preview: statische nep-data, geen AJAX ---- */
        if ( $is_edit ) {
            $currency = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '€';
            ?>
            <div class="mopf-stats-widget" style="pointer-events:none">
                <div class="mopf-stats-grid">
                    <?php if ( $s['show_revenue'] === 'yes' ) : ?>
                    <div class="mopf-stat-card">
                        <div class="mopf-stat-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                <line x1="12" y1="1" x2="12" y2="23"/>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                            </svg>
                        </div>
                        <div class="mopf-stat-body">
                            <span class="mopf-stat-label"><?php esc_html_e( 'Omzet', 'product-haven' ); ?></span>
                            <span class="mopf-stat-value mopf-accent"><?php echo esc_html( $currency . '1.234,56' ); ?></span>
                            <span class="mopf-stat-period"><?php /* translators: %d: number of days */ printf( esc_html__( 'Afgelopen %d dagen', 'product-haven' ), absint( $days ) ); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ( $s['show_orders'] === 'yes' ) : ?>
                    <div class="mopf-stat-card">
                        <div class="mopf-stat-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                                <line x1="3" y1="6" x2="21" y2="6"/>
                                <path d="M16 10a4 4 0 0 1-8 0"/>
                            </svg>
                        </div>
                        <div class="mopf-stat-body">
                            <span class="mopf-stat-label"><?php esc_html_e( 'Orders', 'product-haven' ); ?></span>
                            <span class="mopf-stat-value mopf-accent">12</span>
                            <span class="mopf-stat-period"><?php /* translators: %d: number of days */ printf( esc_html__( 'Afgelopen %d dagen', 'product-haven' ), absint( $days ) ); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ( $s['show_avg'] === 'yes' ) : ?>
                    <div class="mopf-stat-card">
                        <div class="mopf-stat-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                <line x1="18" y1="20" x2="18" y2="10"/>
                                <line x1="12" y1="20" x2="12" y2="4"/>
                                <line x1="6"  y1="20" x2="6"  y2="14"/>
                            </svg>
                        </div>
                        <div class="mopf-stat-body">
                            <span class="mopf-stat-label"><?php esc_html_e( 'Gem. orderwaarde', 'product-haven' ); ?></span>
                            <span class="mopf-stat-value mopf-accent"><?php echo esc_html( $currency . '102,88' ); ?></span>
                            <span class="mopf-stat-period"><?php /* translators: %d: number of days */ printf( esc_html__( 'Afgelopen %d dagen', 'product-haven' ), absint( $days ) ); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div><!-- .mopf-stats-grid -->
            </div>
            <?php
            return;
        }

        /* ---- Live frontend: JS vult de waarden via AJAX ---- */
        ?>
        <div class="mopf-stats-widget"
             data-days="<?php echo absint( $days ); ?>"
             data-show-chart="<?php echo $s['show_chart'] === 'yes' ? '1' : '0'; ?>">

            <div class="mopf-stats-grid">

                <?php if ( $s['show_revenue'] === 'yes' ) : ?>
                <div class="mopf-stat-card">
                    <div class="mopf-stat-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <div class="mopf-stat-body">
                        <span class="mopf-stat-label"><?php esc_html_e( 'Omzet', 'product-haven' ); ?></span>
                        <span class="mopf-stat-value mopf-accent mopf-val-revenue">–</span>
                        <span class="mopf-stat-period"><?php /* translators: %d: number of days */ printf( esc_html__( 'Afgelopen %d dagen', 'product-haven' ), absint( $days ) ); ?></span>
                    </div>
                    <?php if ( $s['show_chart'] === 'yes' ) : ?>
                        <canvas class="mopf-sparkline" data-type="revenue" width="80" height="32"></canvas>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ( $s['show_orders'] === 'yes' ) : ?>
                <div class="mopf-stat-card">
                    <div class="mopf-stat-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                            <line x1="3" y1="6" x2="21" y2="6"/>
                            <path d="M16 10a4 4 0 0 1-8 0"/>
                        </svg>
                    </div>
                    <div class="mopf-stat-body">
                        <span class="mopf-stat-label"><?php esc_html_e( 'Orders', 'product-haven' ); ?></span>
                        <span class="mopf-stat-value mopf-accent mopf-val-orders">–</span>
                        <span class="mopf-stat-period"><?php /* translators: %d: number of days */ printf( esc_html__( 'Afgelopen %d dagen', 'product-haven' ), absint( $days ) ); ?></span>
                    </div>
                    <?php if ( $s['show_chart'] === 'yes' ) : ?>
                        <canvas class="mopf-sparkline" data-type="orders" width="80" height="32"></canvas>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ( $s['show_avg'] === 'yes' ) : ?>
                <div class="mopf-stat-card">
                    <div class="mopf-stat-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round">
                            <line x1="18" y1="20" x2="18" y2="10"/>
                            <line x1="12" y1="20" x2="12" y2="4"/>
                            <line x1="6"  y1="20" x2="6"  y2="14"/>
                        </svg>
                    </div>
                    <div class="mopf-stat-body">
                        <span class="mopf-stat-label"><?php esc_html_e( 'Gem. orderwaarde', 'product-haven' ); ?></span>
                        <span class="mopf-stat-value mopf-accent mopf-val-avg">–</span>
                        <span class="mopf-stat-period"><?php /* translators: %d: number of days */ printf( esc_html__( 'Afgelopen %d dagen', 'product-haven' ), absint( $days ) ); ?></span>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
        <?php
    }
}
