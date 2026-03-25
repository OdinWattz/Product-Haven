<?php
/**
 * Product Haven — Elementor Stats Widget
 * Displays revenue, order volumes, and average order value on the frontend.
 * 
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

        /* ---- Content ---- */
        $this->start_controls_section( 'section_content', [
            'label' => __( 'Contents', 'product-haven' ),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'period_days', [
            'label'   => __( 'Period (days)', 'product-haven' ),
            'type'    => Controls_Manager::NUMBER,
            'default' => 30,
            'min'     => 1,
            'max'     => 365,
        ] );

        $this->add_control( 'show_revenue', [
            'label'        => __( 'Show revenue', 'product-haven' ),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'return_value' => 'yes',
        ] );

        $this->add_control( 'show_orders', [
            'label'        => __( 'Show order count', 'product-haven' ),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'return_value' => 'yes',
        ] );

        $this->add_control( 'show_avg', [
            'label'        => __( 'Show avg. order value', 'product-haven' ),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'return_value' => 'yes',
        ] );

        $this->add_control( 'show_chart', [
            'label'        => __( 'Show minigraph', 'product-haven' ),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'return_value' => 'yes',
        ] );

        $this->end_controls_section();

        /* ---- Style ---- */
        $this->start_controls_section( 'section_style', [
            'label' => __( 'Style', 'product-haven' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'accent_color', [
            'label'     => __( 'Accent color', 'product-haven' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#10B981',
            'selectors' => [
                '{{WRAPPER}} .ph-accent' => 'color: {{VALUE}};',
                '{{WRAPPER}} .ph-stat-icon' => 'background: color-mix(in srgb, {{VALUE}} 15%, transparent); color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'card_bg', [
            'label'     => __( 'Card background', 'product-haven' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => [ '{{WRAPPER}} .ph-stat-card' => 'background: {{VALUE}};' ],
        ] );

        $this->add_control( 'card_radius', [
            'label'      => __( 'Border radius', 'product-haven' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [ 'px' => [ 'min' => 0, 'max' => 32 ] ],
            'default'    => [ 'size' => 16, 'unit' => 'px' ],
            'selectors'  => [ '{{WRAPPER}} .ph-stat-card' => 'border-radius: {{SIZE}}{{UNIT}};' ],
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'value_typography',
            'label'    => __( 'Value typography', 'product-haven' ),
            'selector' => '{{WRAPPER}} .ph-stat-value',
        ] );

        $this->end_controls_section();
    }

    protected function render(): void {
        $s       = $this->get_settings_for_display();
        $is_edit = \Elementor\Plugin::$instance->editor->is_edit_mode();
        self::render_output( $s, $is_edit );
    }

    /**
     * Shared rendering logic — called by both Elementor and the shortcode.
     *
     * @param array $settings  Widget settings (or shortcode attributes as array).
     * @param bool  $is_editor True if we are in the Elementor editor.
     */
    public static function render_output( array $settings, bool $is_editor = false ): void {
        $s        = $settings;
        $days     = absint( $s['period_days'] ?? 30 );
        $is_edit  = $is_editor;

        /* ---- Editor preview: static dummy data, no AJAX ---- */
        if ( $is_edit ) {
            $currency = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '€';
            ?>
            <div class="ph-stats-widget" style="pointer-events:none">
                <div class="ph-stats-grid">
                    <?php if ( $s['show_revenue'] === 'yes' ) : ?>
                    <div class="ph-stat-card">
                        <div class="ph-stat-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                <line x1="12" y1="1" x2="12" y2="23"/>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                            </svg>
                        </div>
                        <div class="ph-stat-body">
                            <span class="ph-stat-label"><?php esc_html_e( 'Revenue', 'product-haven' ); ?></span>
                            <span class="ph-stat-value ph-accent"><?php echo esc_html( $currency . '1.234,56' ); ?></span>
                            <span class="ph-stat-period"><?php printf( esc_html__( 'Last %d days', 'product-haven' ), absint( $days ) ); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ( $s['show_orders'] === 'yes' ) : ?>
                    <div class="ph-stat-card">
                        <div class="ph-stat-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                                <line x1="3" y1="6" x2="21" y2="6"/>
                                <path d="M16 10a4 4 0 0 1-8 0"/>
                            </svg>
                        </div>
                        <div class="ph-stat-body">
                            <span class="ph-stat-label"><?php esc_html_e( 'Orders', 'product-haven' ); ?></span>
                            <span class="ph-stat-value ph-accent">12</span>
                            <span class="ph-stat-period"><?php printf( esc_html__( 'Last %d days', 'product-haven' ), absint( $days ) ); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ( $s['show_avg'] === 'yes' ) : ?>
                    <div class="ph-stat-card">
                        <div class="ph-stat-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                <line x1="18" y1="20" x2="18" y2="10"/>
                                <line x1="12" y1="20" x2="12" y2="4"/>
                                <line x1="6"  y1="20" x2="6"  y2="14"/>
                            </svg>
                        </div>
                        <div class="ph-stat-body">
                            <span class="ph-stat-label"><?php esc_html_e( 'Avg. order value', 'product-haven' ); ?></span>
                            <span class="ph-stat-value ph-accent"><?php echo esc_html( $currency . '102,88' ); ?></span>
                            <span class="ph-stat-period"><?php printf( esc_html__( 'Last %d days', 'product-haven' ), absint( $days ) ); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php
            return;
        }

        /* ---- Live frontend: JS fills in the values via AJAX ---- */
        ?>
        <div class="ph-stats-widget"
             data-days="<?php echo absint( $days ); ?>"
             data-show-chart="<?php echo $s['show_chart'] === 'yes' ? '1' : '0'; ?>">

            <div class="ph-stats-grid">

                <?php if ( $s['show_revenue'] === 'yes' ) : ?>
                <div class="ph-stat-card">
                    <div class="ph-stat-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <div class="ph-stat-body">
                        <span class="ph-stat-label"><?php esc_html_e( 'Revenue', 'product-haven' ); ?></span>
                        <span class="ph-stat-value ph-accent ph-val-revenue">–</span>
                        <span class="ph-stat-period"><?php printf( esc_html__( 'Last %d days', 'product-haven' ), absint( $days ) ); ?></span>
                    </div>
                    <?php if ( $s['show_chart'] === 'yes' ) : ?>
                        <canvas class="ph-sparkline" data-type="revenue" width="80" height="32"></canvas>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ( $s['show_orders'] === 'yes' ) : ?>
                <div class="ph-stat-card">
                    <div class="ph-stat-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                            <line x1="3" y1="6" x2="21" y2="6"/>
                            <path d="M16 10a4 4 0 0 1-8 0"/>
                        </svg>
                    </div>
                    <div class="ph-stat-body">
                        <span class="ph-stat-label"><?php esc_html_e( 'Orders', 'product-haven' ); ?></span>
                        <span class="ph-stat-value ph-accent ph-val-orders">–</span>
                        <span class="ph-stat-period"><?php printf( esc_html__( 'Last %d days', 'product-haven' ), absint( $days ) ); ?></span>
                    </div>
                    <?php if ( $s['show_chart'] === 'yes' ) : ?>
                        <canvas class="ph-sparkline" data-type="orders" width="80" height="32"></canvas>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ( $s['show_avg'] === 'yes' ) : ?>
                <div class="ph-stat-card">
                    <div class="ph-stat-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round">
                            <line x1="18" y1="20" x2="18" y2="10"/>
                            <line x1="12" y1="20" x2="12" y2="4"/>
                            <line x1="6"  y1="20" x2="6"  y2="14"/>
                        </svg>
                    </div>
                    <div class="ph-stat-body">
                        <span class="ph-stat-label"><?php esc_html_e( 'Avg. order value', 'product-haven' ); ?></span>
                        <span class="ph-stat-value ph-accent ph-val-avg">–</span>
                        <span class="ph-stat-period"><?php printf( esc_html__( 'Last %d days', 'product-haven' ), absint( $days ) ); ?></span>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
        <?php
    }
}
