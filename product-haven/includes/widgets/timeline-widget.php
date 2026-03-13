<?php
/**
 * Product Haven — Elementor Timeline Widget
 * Toont de persoonlijke ordertijdlijn van de ingelogde klant.
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
    public function get_title(): string   { return __( 'Mijn Orders', 'product-haven' ); }
    public function get_icon(): string    { return 'eicon-time-line'; }
    public function get_categories(): array { return [ 'ph-category' ]; }
    public function get_keywords(): array   { return [ 'order', 'timeline', 'account', 'klant', 'op' ]; }

    protected function register_controls(): void {

        /* ---- Inhoud ---- */
        $this->start_controls_section( 'section_content', [
            'label' => __( 'Inhoud', 'product-haven' ),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'show_total', [
            'label'        => __( 'Toon totaalbedrag', 'product-haven' ),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'return_value' => 'yes',
        ] );

        $this->add_control( 'show_items', [
            'label'        => __( 'Toon producten per order', 'product-haven' ),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'return_value' => 'yes',
        ] );

        $this->add_control( 'guest_text', [
            'label'   => __( 'Tekst voor gasten', 'product-haven' ),
            'type'    => Controls_Manager::TEXT,
            'default' => __( 'Log in om je ordergeschiedenis te bekijken.', 'product-haven' ),
        ] );

        $this->add_control( 'empty_text', [
            'label'   => __( 'Tekst als er geen orders zijn', 'product-haven' ),
            'type'    => Controls_Manager::TEXT,
            'default' => __( 'Je hebt nog geen orders geplaatst.', 'product-haven' ),
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
                '{{WRAPPER}} .mopf-tl-dot'          => 'background: {{VALUE}};',
                '{{WRAPPER}} .mopf-tl-order-number' => 'color: {{VALUE}};',
                '{{WRAPPER}} .mopf-tl-page-btn.is-active' => 'background: {{VALUE}}; border-color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'line_color', [
            'label'     => __( 'Tijdlijn lijnkleur', 'product-haven' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#E5E7EB',
            'selectors' => [ '{{WRAPPER}} .mopf-tl-line' => 'background: {{VALUE}};' ],
        ] );

        $this->add_control( 'card_bg', [
            'label'     => __( 'Kaart achtergrond', 'product-haven' ),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => [ '{{WRAPPER}} .mopf-tl-card' => 'background: {{VALUE}};' ],
        ] );

        $this->add_control( 'card_radius', [
            'label'      => __( 'Afronding', 'product-haven' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [ 'px' => [ 'min' => 0, 'max' => 32 ] ],
            'default'    => [ 'size' => 14, 'unit' => 'px' ],
            'selectors'  => [ '{{WRAPPER}} .mopf-tl-card' => 'border-radius: {{SIZE}}{{UNIT}};' ],
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'body_typography',
            'label'    => __( 'Teksttypografie', 'product-haven' ),
            'selector' => '{{WRAPPER}} .mopf-tl-card',
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
        $s          = $settings;
        $is_logged  = is_user_logged_in();
        $show_items = ( $s['show_items'] ?? 'yes' ) === 'yes' ? '1' : '0';
        $show_total = ( $s['show_total'] ?? 'yes' ) === 'yes' ? '1' : '0';
        $is_edit    = $is_editor;

        /* ---- Editor preview: statische nep-orders ---- */
        if ( $is_edit ) {
            $currency = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '€';
            $preview_orders = [
                [ 'number' => '1042', 'date' => '3 dagen geleden', 'status' => 'completed', 'label' => 'Voltooid',   'total' => $currency . '89,95',  'items' => [ 'Product A × 2', 'Product B × 1' ] ],
                [ 'number' => '1031', 'date' => '2 weken geleden', 'status' => 'processing', 'label' => 'In behandeling', 'total' => $currency . '124,00', 'items' => [ 'Product C × 1' ] ],
                [ 'number' => '1019', 'date' => '1 maand geleden', 'status' => 'completed', 'label' => 'Voltooid',   'total' => $currency . '54,50',  'items' => [ 'Product D × 3' ] ],
            ];
            ?>
            <div class="mopf-timeline-widget" style="pointer-events:none">
                <div class="mopf-tl-list">
                    <?php foreach ( $preview_orders as $o ) : ?>
                    <div class="mopf-tl-item">
                        <div class="mopf-tl-dot-wrap">
                            <div class="mopf-tl-dot mopf-status-dot-<?php echo esc_attr( $o['status'] ); ?>"></div>
                            <div class="mopf-tl-line"></div>
                        </div>
                        <div class="mopf-tl-card">
                            <div class="mopf-tl-card-header">
                                <span class="mopf-tl-number">#<?php echo esc_html( $o['number'] ); ?></span>
                                <span class="mopf-status-badge mopf-status-<?php echo esc_attr( $o['status'] ); ?>"><?php echo esc_html( $o['label'] ); ?></span>
                            </div>
                            <div class="mopf-tl-meta">
                                <span class="mopf-tl-date"><?php echo esc_html( $o['date'] ); ?></span>
                                <?php if ( $s['show_total'] === 'yes' ) : ?>
                                    <span class="mopf-tl-total"><?php echo esc_html( $o['total'] ); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ( $s['show_items'] === 'yes' ) : ?>
                            <ul class="mopf-tl-items">
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
        <div class="mopf-timeline-widget"
             data-show-items="<?php echo esc_attr( $show_items ); ?>"
             data-show-total="<?php echo esc_attr( $show_total ); ?>">

            <?php if ( ! $is_logged ) : ?>
                <div class="mopf-guest-notice">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.5" stroke-linecap="round">
                        <circle cx="12" cy="7" r="4"/>
                        <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                    </svg>
                    <p><?php echo esc_html( $s['guest_text'] ); ?></p>
                    <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'dashboard' ) ); ?>"
                       class="mopf-login-btn">
                        <?php esc_html_e( 'Inloggen', 'product-haven' ); ?>
                    </a>
                </div>
            <?php else : ?>
                <div class="mopf-tl-list" id="mopf-tl-list">
                    <!-- Skeleton loaders -->
                    <?php for ( $i = 0; $i < 4; $i++ ) : ?>
                        <div class="mopf-tl-item mopf-tl-skeleton">
                            <div class="mopf-tl-dot-wrap">
                                <span class="mopf-tl-dot"></span>
                                <span class="mopf-tl-line"></span>
                            </div>
                            <div class="mopf-tl-card">
                                <div class="mos-skeleton" style="width:80px;height:12px;margin-bottom:8px"></div>
                                <div class="mos-skeleton" style="width:140px;height:16px;margin-bottom:6px"></div>
                                <div class="mos-skeleton" style="width:60px;height:10px"></div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>

                <div class="mopf-tl-empty" id="mopf-tl-empty" hidden>
                    <p><?php echo esc_html( $s['empty_text'] ); ?></p>
                </div>

                <div class="mopf-tl-pagination" id="mopf-tl-pagination"></div>
            <?php endif; ?>

        </div>
        <?php
    }
}
