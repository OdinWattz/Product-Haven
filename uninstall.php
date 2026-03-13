<?php
/**
 * Uninstall Product Haven
 *
 * Runs when the plugin is deleted via the WordPress admin.
 * Removes all options and transients stored by Product Haven.
 *
 * @package ProductHaven
 */

// Only run when WordPress triggers the uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// ── Options ──────────────────────────────────────────────────────────────────
$ph_options_to_delete = [ // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
    'ph_options',
    'ph_stock_options',
    'ph_so_options',
    'ph_so_counter',
    'ph_cache_flushed_v4',
];

foreach ( $ph_options_to_delete as $ph_option ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
    delete_option( $ph_option );
}

// ── Transients ───────────────────────────────────────────────────────────────
global $wpdb;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_ph_%'
        OR option_name LIKE '_transient_timeout_ph_%'"
);

// ── Stock log table (optional — only if plugin created one) ──────────────────
// Uncomment the line below if you want to remove the stock log table on uninstall.
// $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ph_stock_log" );
