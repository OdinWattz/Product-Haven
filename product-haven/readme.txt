=== Product Haven ===
Contributors: odinwattez
Tags: woocommerce, orders, inventory, stock management, sequential order numbers
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.3.2
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

All-in-one WooCommerce management: orders, stock, products & sequential order numbers — with Elementor widgets.

== Description ==

Product Haven is an advanced WooCommerce management plugin that combines a live order dashboard, stock management, quick product editor, sequential order numbering and Elementor frontend widgets — all in one clean admin interface.

It is designed for WooCommerce store owners who want a central place to review orders, manage stock, handle products faster, and work with cleaner order numbering.

Product Haven is open-source software released under the GPL-3.0-or-later license. You are free to use, study, modify, and redistribute it under the terms of that license.

Support is provided on a best-effort basis only. Bug reports may be reviewed when time allows, but no guaranteed response times, custom development, or project-specific compatibility work are included.

Use in production is at your own discretion, and testing on a staging site before updating is strongly recommended.

= Features =

**Order Dashboard**

* Live statistics — revenue, order count, average order value and new customers for the chosen period (7, 14, 30, 90 days or 1 year)
* Interactive Chart.js graph — revenue and orders on the same timeline, toggle per dataset
* Order timeline — searchable, filterable by status, paginated
* Order modal — full order detail overlay with customer info, product list and notes; add notes directly from the modal
* Order edit modal — edit billing fields and add internal notes without leaving the dashboard
* Customer card — slide-in panel with order statistics and full order history per customer
* Top products — best-selling products for the chosen period
* CSV export — configurable columns, filtered by current period and status

**Stock Management**

* Live low-stock overview with configurable threshold
* Bulk stock updates with reason logging
* Stock history log per product
* Email alerts for low stock and out-of-stock products (real-time and daily digest)
* Dynamic alert email colour — red for out-of-stock, amber for low stock, green for test emails
* CSV export of full stock overview

**Quick Products**

* Create and edit simple WooCommerce products directly from the Product Haven dashboard
* Duplicate, quick-edit and delete products without leaving the page
* Styled action button tooltips on hover
* Media library integration for product images

**Sequential Order Numbers**

* Assign clean sequential order numbers independent of WordPress post IDs
* Configurable prefix, suffix, padding and start number
* Orders remain searchable by sequential number in WooCommerce admin
* Thread-safe counter using database locks

**Frontend (Elementor)**

* Stats Widget — displays revenue, order count and average order value for the logged-in customer
* Timeline Widget — paginated order timeline for the logged-in customer with status badges and product list

**Multilingual**

* Fully translated into Dutch (NL), English (EN), German (DE), French (FR) and Spanish (ES)
* Language switcher in the admin header

**REST API (optional)**

Activate via Settings → Enable REST API.

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /wp-json/product-haven/v1/stats?days=30 | Statistics |
| GET | /wp-json/product-haven/v1/timeline?page=1&per_page=20 | Order timeline |
| GET | /wp-json/product-haven/v1/top-products?days=30&limit=10 | Top products |

All endpoints require the manage_woocommerce capability.

= Requirements =

* WordPress 6.0 or higher
* WooCommerce 7.0 or higher
* PHP 7.4 or higher
* Elementor 3.0+ (optional, for frontend widgets only)

== Installation ==

1. Upload the `product-haven` folder to the `/wp-content/plugins/` directory, or install directly through the WordPress plugin screen.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. WooCommerce must be installed and active.
4. Navigate to **Product Haven** in the WordPress admin menu.

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =

Yes. Product Haven is built exclusively for WooCommerce stores.

= Does this plugin work without Elementor? =

Yes. The admin dashboard, stock management, quick products and sequential order numbers all work without Elementor. Elementor is only required for the two optional frontend widgets.

= Is support included? =

Support is limited and provided on a best-effort basis only. Confirmed bugs may be reviewed when possible, but customisations and guaranteed support are not included.

= Will sequential order numbers conflict with my existing orders? =

No. Sequential numbers are stored as order meta and displayed via WooCommerce's built-in `woocommerce_order_number` filter. Existing orders keep their original numbers.

= Where are the settings? =

All settings are in the **Settings** tab inside the Product Haven dashboard.

= Can I reset the sequential order counter? =

Yes. Go to the **Order Numbers** tab and use the reset button. You can set any starting number.

== Screenshots ==

1. Order dashboard with live statistics and chart
2. Order timeline with modal detail view
3. Stock management overview
4. Quick Products editor
5. Sequential Order Numbers settings
6. Elementor frontend widgets for logged-in customers

== Changelog ==

= 1.3.2 =
* Quick Products — action buttons now show styled CSS tooltips on hover (Edit, Duplicate, Open in WC, Delete)
* Quick Products — "New product" button gets bottom margin so it no longer sits flush against the filter bar
* Quick Products — Publish card fields (Status, Visibility, Featured) now have proper spacing between them

= 1.3.1 =
* Revenue tab layout centred with flexbox; table max-width, box-shadow and larger row padding
* Categories tab column headers moved into card-header matching the coupons pattern
* Customers tab column headers moved into card-header; inline header row removed
* Daily report date-wrap spacing tightened
* Stock reminder email header gradient and CTA colour now reflect most severe stock status (red/amber/green)

= 1.3.0 =
* Full FR, ES and DE translations added across all UI strings
* Language switcher extended to 5 languages: NL, EN, DE, FR, ES
* AJAX language handler now accepts de, fr and es
* Locale mapping corrected per language

= 1.2.0 =
* In-plugin order edit modal — edit billing fields and add internal notes without leaving the dashboard
* ph_format_order() extended with full billing address fields
* Order modal displays phone number and full billing address
* openOrderModal() now supports forceReload to bypass stale cache

= 1.1.0 =
* Stock Management tab with low-stock overview, bulk updates, reason logging and CSV export
* Real-time email alerts and daily digest for low/out-of-stock products
* Daily digest cron job
* DB stock log table
* Quick Products tab — create and edit products from the dashboard
* Sequential Order Numbers tab
* Full rebrand to Product Haven

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.3.2 =
Minor UI improvements to Quick Products.

= 1.3.1 =
UI polish across revenue, categories, customers and daily report tabs. Stock alert email now uses dynamic colours.

= 1.3.0 =
Added FR, ES and DE translations and extended the language switcher to 5 languages.

= 1.2.0 =
Adds in-plugin order editing. Upgrade recommended for stores that frequently update customer billing details.

= 1.1.0 =
Major update — adds stock management, quick products and sequential order numbers.
