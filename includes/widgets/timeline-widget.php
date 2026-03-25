<?php
/**
 * Product Haven — Elementor Timeline Widget
 * Shows the personal order timeline of the logged-in customer.
 *
 * @package ProductHaven
 */

namespace ProductHaven;

defined( 'ABSPATH' ) || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class Timeline_Widget extends Widget_Base {

    public function get_name(): string    { return 'ph_order_timeline'; }
    public function get_title(): string   { return __( 'My orders', 'product-haven' ); }
    public function get_icon(): string    { return 'eicon-time-line'; }
    public function get_categories(): array { return [ 'ph-category' ]; }
    public function get_keywords(): array   { return [ 'order', 'timeline', 'account', 'customer', 'op' ]; }

    protected function register_controls(): void {

        /* ---- Content ---- */
        $this->start_controls_section( 'section_content', [
            'label' => __( 'Content', 'product-haven' ),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'show_total', [
            'label'        => __( 'Show total amount', 'product-haven' ),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'return_value' => 'yes',
        ] );

        $this->add_control( 'show_items', [
            'label'        => __( 'Show items per order', 'product-haven' ),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'return_value' => 'yes',
        ] );

        $this->add_control( 'guest_text', [
            'label'   => __( 'Guest text', 'product-haven' ),
            'type'    => Controls_Manager::TEXT,
            'default' => __( 'Log in to view your order history.', 'product-haven' ),
        ] );

        $this->add_control( 'empty_text', [
            'label'   => __( 'Text when no orders', 'product-haven' ),
            'type'    => Controls_Manager::TEXT,
            'default' => __( 'You have not placed any orders yet.', 'product-haven' ),
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
                '{{WRAPPER}} .ph-tl-dot'          => 'background: {{VALUE}};',
                '{{WRAPPER}} .ph-tl-order-number' => 'color: {{VALUE}};',
                '{{WRAPPER}} .ph-tl-page-btn.is-active' => 'background: {{VALUE}}; border-color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'line_color', [
            'label'     => __( 'Timeline line color', 'product-haven' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#E5E7EB',
            'selectors' => [ '{{WRAPPER}} .ph-tl-line' => 'background: {{VALUE}};' ],
        ] );

        $this->add_control( 'card_bg', [
            'label'     => __( 'Card background', 'product-haven' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => [ '{{WRAPPER}} .ph-tl-card' => 'background: {{VALUE}};' ],
        ] );

        $this->add_control( 'card_radius', [
            'label'      => __( 'Border radius', 'product-haven' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [ 'px' => [ 'min' => 0, 'max' => 32 ] ],
            'default'    => [ 'size' => 14, 'unit' => 'px' ],
            'selectors'  => [ '{{WRAPPER}} .ph-tl-card' => 'border-radius: {{SIZE}}{{UNIT}};' ],
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'body_typography',
            'label'    => __( 'Body typography', 'product-haven' ),
            'selector' => '{{WRAPPER}} .ph-tl-card',
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
        $s          = $settings;
        $is_logged  = is_user_logged_in();
        $show_items = ( $s['show_items'] ?? 'yes' ) === 'yes' ? '1' : '0';
        $show_total = ( $s['show_total'] ?? 'yes' ) === 'yes' ? '1' : '0';
        $is_edit    = $is_editor;

        /* ---- Editor preview: static dummy orders ---- */
        if ( $is_edit ) {
            $currency = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '€';
            $preview_orders = [
                [ 'number' => '1042', 'date' => '3 days ago', 'status' => 'completed', 'label' => 'Completed',   'total' => $currency . '89.95',  'items' => [ 'Product A × 2', 'Product B × 1' ] ],
                [ 'number' => '1031', 'date' => '2 weeks ago', 'status' => 'processing', 'label' => 'Processing', 'total' => $currency . '124.00', 'items' => [ 'Product C × 1' ] ],
                [ 'number' => '1019', 'date' => '1 month ago', 'status' => 'completed', 'label' => 'Completed',   'total' => $currency . '54.50',  'items' => [ 'Product D × 3' ] ],
            ];
            ?>
            <div class="ph-timeline-widget" style="pointer-events:none">
                <div class="ph-tl-list">
                    <?php foreach ( $preview_orders as $o ) : ?>
                    <div class="ph-tl-item">
                        <div class="ph-tl-dot-wrap">
                            <div class="ph-tl-dot ph-status-dot-<?php echo esc_attr( $o['status'] ); ?>"></div>
                            <div class="ph-tl-line"></div>
                        </div>
                        <div class="ph-tl-card">
                            <div class="ph-tl-card-header">
                                <span class="ph-tl-number">#<?php echo esc_html( $o['number'] ); ?></span>
                                <span class="ph-status-badge ph-status-<?php echo esc_attr( $o['status'] ); ?>"><?php echo esc_html( $o['label'] ); ?></span>
                            </div>
                            <div class="ph-tl-meta">
                                <span class="ph-tl-date"><?php echo esc_html( $o['date'] ); ?></span>
                                <?php if ( $s['show_total'] === 'yes' ) : ?>
                                    <span class="ph-tl-total"><?php echo esc_html( $o['total'] ); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ( $s['show_items'] === 'yes' ) : ?>
                            <ul class="ph-tl-items">
                                <?php foreach ( $o['items'] as $item ) : ?>
                                    <li><?php echo esc_html( $item ); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
            return;
        }

        /* ---- Live frontend ---- */
        ?>
        <div class="ph-timeline-widget"
             data-show-items="<?php echo esc_attr( $show_items ); ?>"
             data-show-total="<?php echo esc_attr( $show_total ); ?>">

            <?php if ( ! $is_logged ) : ?>
                <div class="ph-guest-notice">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.5" stroke-linecap="round">
                        <circle cx="12" cy="7" r="4"/>
                        <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                    </svg>
                    <p><?php echo esc_html( $s['guest_text'] ); ?></p>
                    <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'dashboard' ) ); ?>"
                       class="ph-login-btn">
                        <?php esc_html_e( 'Log in', 'product-haven' ); ?>
                    </a>
                </div>
            <?php else : ?>
                <div class="ph-tl-list" id="ph-tl-list">
                    <!-- Skeleton loaders -->
                    <?php for ( $i = 0; $i < 4; $i++ ) : ?>
                        <div class="ph-tl-item ph-tl-skeleton">
                            <div class="ph-tl-dot-wrap">
                                <span class="ph-tl-dot"></span>
                                <span class="ph-tl-line"></span>
                            </div>
                            <div class="ph-tl-card">
                                <div class="ph-skeleton" style="width:80px;height:12px;margin-bottom:8px"></div>
                                <div class="ph-skeleton" style="width:140px;height:16px;margin-bottom:6px"></div>
                                <div class="ph-skeleton" style="width:60px;height:10px"></div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>

                <div class="ph-tl-empty" id="ph-tl-empty" hidden>
                    <p><?php echo esc_html( $s['empty_text'] ); ?></p>
                </div>

                <div class="ph-tl-pagination" id="ph-tl-pagination"></div>
            <?php endif; ?>

        </div>
        <?php
    }
}
