<?php
/**
 * Product Haven — Sequential Orders tab HTML
 *
 * Included inside ph_render_admin_page(); variables are scoped to that context.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 *
 * @package ProductHaven
 */

defined( 'ABSPATH' ) || exit;

$lang = ph_get_lang();
$d = ph_so_get_page_data();
extract( $d ); // $options, $counter, $prefix, $suffix, $start_number, $padding, $preview

$so_saved = isset( $_GET['ph_so_saved'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$so_reset = isset( $_GET['ph_so_reset'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<!-- ===== TAB: SEQUENTIAL ORDERS ===== -->
<div class="mos-tab-panel" id="mos-panel-sequential">

    <div class="op-so-layout">

        <!-- ── Linker kolom: instellingen form ── -->
        <div class="op-so-main">

            <?php if ( $so_saved ) : ?>
                <div class="mos-notice mos-notice-success" style="margin-bottom:20px">
                    ✓ <?php echo esc_html( ph_t( 'so_settings_saved', $lang ) ); ?>
                </div>
            <?php endif; ?>
            <?php if ( $so_reset ) : ?>
                <div class="mos-notice" style="background:#FEF9C3;border-color:#FDE047;color:#713F12;margin-bottom:20px">
                    ↺ <?php echo esc_html( ph_t( 'so_counter_reset', $lang ) ); ?>
                </div>
            <?php endif; ?>

            <section class="mos-card">
                <div class="mos-card-header">
                    <h2><?php echo esc_html( ph_t( 'so_format_title', $lang ) ); ?></h2>
                </div>
                <div class="mos-card-body">
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <?php wp_nonce_field( 'ph_so_settings_nonce' ); ?>
                        <input type="hidden" name="action' value='ph_so_save_settings">

                        <div class="op-so-field-grid">

                            <div class="op-so-field">
                                <label class="op-so-label" for="op-so-prefix">
                                    <?php echo esc_html( ph_t( 'so_prefix', $lang ) ); ?>
                                </label>
                                <input type="text" id="op-so-prefix" name="prefix"
                                       value="<?php echo esc_attr( $prefix ); ?>"
                                       class="op-so-input" placeholder="<?php echo esc_attr( ph_t( 'so_prefix_placeholder', $lang ) ); ?>">
                                <p class="op-so-desc"><?php echo esc_html( ph_t( 'so_prefix_desc', $lang ) ); ?></p>
                            </div>

                            <div class="op-so-field">
                                <label class="op-so-label" for="op-so-suffix">
                                    <?php echo esc_html( ph_t( 'so_suffix', $lang ) ); ?>
                                </label>
                                <input type="text" id="op-so-suffix" name="suffix"
                                       value="<?php echo esc_attr( $suffix ); ?>"
                                       class="op-so-input" placeholder="<?php echo esc_attr( ph_t( 'so_suffix_placeholder', $lang ) ); ?>">
                                <p class="op-so-desc"><?php echo esc_html( ph_t( 'so_suffix_desc', $lang ) ); ?></p>
                            </div>

                            <div class="op-so-field">
                                <label class="op-so-label" for="op-so-start">
                                    <?php echo esc_html( ph_t( 'so_start_number', $lang ) ); ?>
                                </label>
                                <input type="number" id="op-so-start" name="start_number"
                                       value="<?php echo esc_attr( $start_number ); ?>"
                                       min="1" class="op-so-input op-so-input-sm">
                                <p class="op-so-desc"><?php echo esc_html( ph_t( 'so_start_desc', $lang ) ); ?></p>
                            </div>

                            <div class="op-so-field">
                                <label class="op-so-label" for="op-so-padding">
                                    <?php echo esc_html( ph_t( 'so_padding', $lang ) ); ?>
                                </label>
                                <input type="number" id="op-so-padding" name="padding"
                                       value="<?php echo esc_attr( $padding ); ?>"
                                       min="1" max="10" class="op-so-input op-so-input-sm">
                                <p class="op-so-desc"><?php echo esc_html( ph_t( 'so_padding_desc', $lang ) ); ?></p>
                            </div>

                        </div><!-- .op-so-field-grid -->

                        <div class="op-so-form-footer">
                            <button type="submit" class="mos-btn mos-btn-primary">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                <?php echo esc_html( ph_t( 'save', $lang ) ); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Uitleg -->
            <section class="mos-card" style="margin-top:20px">
                <div class="mos-card-header">
                    <h2><?php echo esc_html( ph_t( 'so_how_title', $lang ) ); ?></h2>
                </div>
                <div class="mos-card-body">
                    <ul class="op-so-info-list">
                        <li><?php echo esc_html( ph_t( 'so_how_1', $lang ) ); ?></li>
                        <li><?php echo esc_html( ph_t( 'so_how_2', $lang ) ); ?></li>
                        <li><?php echo esc_html( ph_t( 'so_how_3', $lang ) ); ?></li>
                        <li><?php echo esc_html( ph_t( 'so_how_4', $lang ) ); ?></li>
                        <li><?php echo esc_html( ph_t( 'so_how_5', $lang ) ); ?></li>
                    </ul>
                </div>
            </section>

        </div><!-- .op-so-main -->

        <!-- ── Rechter kolom: preview + status + reset ── -->
        <div class="op-so-sidebar">

            <!-- Preview -->
            <section class="mos-card op-so-preview-card">
                <p class="op-so-preview-label"><?php echo esc_html( ph_t( 'so_next_order', $lang ) ); ?></p>
                <p class="op-so-preview-number" id="op-so-preview"><?php echo esc_html( $preview ); ?></p>
            </section>

            <!-- Status -->
            <section class="mos-card">
                <div class="mos-card-header">
                    <h2><?php echo esc_html( ph_t( 'so_counter_status', $lang ) ); ?></h2>
                </div>
                <div class="mos-card-body op-so-status-body">
                    <div class="op-so-status-row">
                        <span class="op-so-status-label"><?php echo esc_html( ph_t( 'so_current_number', $lang ) ); ?></span>
                        <strong>
                            <?php echo $counter > 0
                                ? esc_html( $counter )
                                : '<span style="color:#94A3B8">' . esc_html( ph_t( 'so_no_orders_yet', $lang ) ) . '</span>';
                            ?>
                        </strong>
                    </div>
                    <div class="op-so-status-row">
                        <span class="op-so-status-label"><?php echo esc_html( ph_t( 'so_start_number', $lang ) ); ?></span>
                        <strong><?php echo esc_html( $start_number ); ?></strong>
                    </div>
                    <div class="op-so-status-row">
                        <span class="op-so-status-label"><?php echo esc_html( ph_t( 'so_padding', $lang ) ); ?></span>
                        <strong><?php echo esc_html( $padding ); ?> <?php echo esc_html( ph_t( 'so_digits', $lang ) ); ?></strong>
                    </div>
                </div>
            </section>

            <!-- Reset -->
            <section class="mos-card op-so-reset-card">
                <div class="mos-card-header">
                    <h2><?php echo esc_html( ph_t( 'so_reset_title', $lang ) ); ?></h2>
                </div>
                <div class="mos-card-body">
                    <p class="op-so-reset-desc"><?php echo esc_html( ph_t( 'so_reset_desc', $lang ) ); ?></p>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="op-so-reset-form">
                        <?php wp_nonce_field( 'ph_so_reset_nonce' ); ?>
                        <input type="hidden" name="action" value="ph_so_reset_counter">
                        <button type="submit" class="mos-btn op-so-reset-btn">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.5"/></svg>
                            <?php echo esc_html( ph_t( 'so_reset_btn', $lang ) ); ?>
                        </button>
                    </form>
                </div>
            </section>

        </div><!-- .op-so-sidebar -->

    </div><!-- .op-so-layout -->

</div><!-- #mos-panel-sequential -->
