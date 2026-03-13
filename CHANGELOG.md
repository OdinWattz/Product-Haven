# Changelog — Product Haven

All notable changes to this project are documented here.  
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).  
Versions follow [Semantic Versioning](https://semver.org/).

---

## [Unreleased]

---

## [1.3.2] — 2026-03-12

### Changed
- **Quick Products — action button tooltips** — the 4 row-action buttons (Edit, Duplicate, Open in WC, Delete) now show a styled CSS tooltip on hover via `data-tooltip` + `::after`. Uses a dark pill (`#1e293b`) that fades in above the button; more reliable than native `title` tooltips which were suppressed by the table overflow context.
- **Quick Products — "New product" button spacing** — added `margin-bottom: 8px` so the button no longer sits flush against the filter bar below it.
- **Quick Products — Publish card field spacing** — `.op-qp-editor-sidebar .mos-card-body` now uses `display:flex; flex-direction:column; gap:16px`, giving proper breathing room between Status, Visibility, and Featured toggle fields.

---

## [1.3.1] — 2026-03-12

### Changed
- **Revenue tab** — container now centred with flexbox (`display:flex; justify-content:center`), padding increased to `28px 32px`; `.mos-revenue-table` gets `max-width:560px`, subtle `box-shadow`, and row padding increased from `14px 20px` to `16px 24px`.
- **Categories tab header** — column headers ("Top categories", "Items sold", "Revenue") moved into the card-header div (`.mos-categories-col-header`), matching the coupons pattern. Card-header uses `#F8FAFC` background; text colour `#64748B`.
- **Customers tab header** — column headers ("Top customers", "Orders", "Gem. order", "Laatste order", "Totaal besteed") moved into the card-header div (`.mos-customers-col-header`), removing the inline `mos-report-header` row. Card-header uses `#F8FAFC` background; text colour `#64748B`.
- **Daily report** — `mos-daily-date-wrap` gets `margin-top:12px` (more breathing room above the date picker) and `margin-bottom:6px` (less gap between date picker and summary card below).
- **Stock reminder email** — header gradient and CTA button colour now reflect the most severe stock status in the email:
  - 🔴 Any out-of-stock product → red gradient (`#EF4444 → #DC2626`)
  - 🟡 Low stock only → amber gradient (`#F59E0B → #D97706`)
  - 🟢 No alerts (test email) → original green
  - Stock count number in each row is also coloured red (out) or amber (low) to match.

---

## [1.3.0] — 2026-03-12

### Added
- **French (FR) and Spanish (ES) translations** — all UI strings in `i18n.php` now have complete `fr` and `es` entries across every section:
  - General / Header
  - Tabs
  - Stat cards
  - Dashboard chart
  - Order timeline
  - Top products
  - Low stock card
  - Delete / refund / revert modals
  - Stock edit modal (old + new)
  - Settings tab
  - Customer card modal
  - Order detail + edit modal
  - JS general strings (errors, loading states)
  - JS: low stock card
  - JS: chart
  - JS: timeline / time-ago strings
  - JS: order modal (all actions, status buttons, danger zone)
  - JS: order edit modal
  - JS: refund + revert modals
  - JS: delete confirmation
  - JS: customer card
  - JS: revenue report
  - JS: categories report
  - JS: coupons report
  - JS: daily report
  - JS: customers report
  - JS: stock table + settings + bulk actions
  - JS: Quick Products (all list, editor, filters, fields, image/gallery)
  - JS: stockEdit (dashboard low stock modal)
  - Sequential Orders tab
  - WooCommerce order statuses
  - CSV export columns

- **German (DE) translations** — all entries that were missing `de` are now complete (same sections as above).

- **Language switcher extended** — the header now shows **5 language buttons**: NL · EN · DE · FR · ES.
  - Each button has a localized `title` tooltip (e.g. "Switch to French").
  - New i18n keys: `lang_de`, `lang_fr`, `lang_es`, `switch_to_de`, `switch_to_fr`, `switch_to_es`.

- **AJAX language handler** (`ph_set_lang`) now accepts `de`, `fr` and `es` in addition to `nl` and `en`. Previously selecting DE/FR/ES would silently fall back to `nl`.

- **Locale mapping** — `locale` passed to JS is now correctly set per language:
  - `nl` → `nl-NL`
  - `en` → `en-GB`
  - `de` → `de-DE`
  - `fr` → `fr-FR`
  - `es` → `es-ES`

### Fixed
- Broken flag emoji characters (🇫🇷 / 🇪🇸) that rendered as replacement boxes on some server encodings — replaced with plain text labels (`FR` / `ES`) for all language buttons.

### Changed
- Language switcher `title` attribute moved from the wrapping `<div>` to individual `<button>` elements, so each button describes its own action.

---

## [1.2.0] — 2026-03-11

### Added
- **In-plugin order edit modal** — replaced the "Bewerken in WC" external link with an own modal inside the plugin.
  - 10 billing fields: voornaam, achternaam, bedrijf, e-mail, telefoon, adres 1, adres 2, stad, postcode, land.
  - Optioneel intern notitieveld dat als ordernotitie wordt opgeslagen na het bewaren.
  - Foutmelding-paragraaf en save-knop met laad-indicator.
  - Knop `#mos-modal-edit-btn` in de order modal header vervangt de oude `<a>`-link.
- **`ph_ajax_qp_save_order()` (ajax.php)** — nieuwe AJAX handler die billing-velden opslaat via WC setters, optioneel een interne ordernotitie toevoegt, WP/WC caches leegt en een verse `ph_format_order()` teruggeeft.
- **`ph_format_order()` uitgebreid (data.php)** — het `customer`-object bevat nu alle billing-adresvelden:
  `first_name`, `last_name`, `company`, `phone`, `address_1`, `address_2`, `postcode` (naast de al bestaande `name`, `email`, `city`, `country`, `id`).
- **Order modal toont nu** telefoon en volledig adres (adres 1, adres 2, postcode, stad, land).
- **`openOrderEditModal(o)` (ph-admin.js)** — nieuwe JS-functie die de edit modal opent, velden vult vanuit `o.customer.*`, save/cancel/close listeners beheert via klonen en na opslaan de cache en modal live bijwerkt.
- **`openOrderModal()` heeft nu een `forceReload`-parameter** — vanuit de klantenkaart wordt altijd `forceReload=true` meegegeven zodat order-data nooit uit een mogelijk verouderde cache komt.

### Fixed
- **Order edit modal toonde oude data bij heropenen** — na opslaan wordt de `orderCache` bijgewerkt, de modal body herrenderd en de editBtn opnieuw gebonden aan de verse data.
- **Verkeerde stad/adres bij orders geopend vanuit klantenkaart** — `openOrderModal` sloeg een order op in `orderCache` vanuit de tijdlijn; bij een volgende open vanuit de klantenkaart werd de gecachede (mogelijk verouderde) versie getoond. Opgelost via `forceReload=true` en het legen van WP/WC caches in `ph_ajax_get_single_order`.
- **`ph_ajax_get_single_order` legt nu WP object-cache en WC transients** — `clean_post_cache()` + `wc_delete_shop_order_transients()` voor het ophalen, zodat altijd actuele order-data wordt gelezen.

### Changed
- `mos-modal-edit-link` CSS uitgebreid met `background:none; border:none; cursor:pointer; padding:0` zodat het element als knop werkt.
- Nieuwe CSS-klassen: `.mos-order-edit-modal`, `.mos-order-edit-grid` (2-koloms grid), `.mos-order-edit-field`, `.mos-order-edit-footer`; backdrop `#mos-order-edit-backdrop` toegevoegd aan de `[hidden]`-suppressieblok.

---

## [1.1.0] — 2026-03-10

### Added
- **Voorraad-tab** — volledige Stock Sentinel-functionaliteit geïntegreerd in Product Haven als eigen "Voorraad" tab in de zijbalk.
  - Overzichtstabel met zoeken, filteren (alle / laag / uitverkocht), sorteren en paginering.
  - Statkaarten: totale producten, totale voorraadwaarde, laag op voorraad, uitverkocht.
  - Inline voorraad bewerken via een eigen edit-modal (hoeveelheid + reden).
  - Bulk-update: meerdere producten tegelijk bijwerken.
  - CSV-export van de volledige voorraadlijst.
  - Instellingenpaneel: drempelwaarde, alert-e-mailadres, realtime alerts aan/uit, dagelijkse digest aan/uit.
  - Testknop om direct een alert-e-mail te versturen.
- **Realtime e-mailalerts** — `woocommerce_product_set_stock` en `woocommerce_variation_set_stock` hooks sturen een alert wanneer een product onder de drempel of uitverkocht raakt.
  - Backup-hook `woocommerce_product_set_stock_status` vangt statuswijzigingen op die de kwantiteits-hook missen.
  - 30-minuten cooldown per product (via transient) voorkomt spam bij meerdere orders.
- **Dagelijkse digest-cron** — `ph_stock_daily_alert` cron stuurt elke dag een overzichts-e-mail van alle lage/uitverkochte producten (instelbaar).
- **DB-logtabel** `{prefix}ph_stock_log` — elke voorraadwijziging wordt gelogd (product, oud/nieuw aantal, reden, tijdstip).
- **WooCommerce eigen stock-mails uitgeschakeld** — `woocommerce_email_enabled_low_stock` en `woocommerce_email_enabled_no_stock` gefilterd naar `false`; Product Haven stuurt zelf de meldingen.
- **`wc_update_product_stock()`** gebruikt voor voorraadwijzigingen zodat alle WC-hooks correct vuren.
- Added Quick Products tab — create and edit products directly from the dashboard.
- Added Sequential Order Numbers tab — clean sequential numbering with prefix/suffix support.
- Full rebrand to Product Haven.

### Fixed
- **Operator-precedence bug** in de alertcontrole (`$opts['key'] ?? '1' !== '1'` evalueerde verkeerd) — opgelost met extra haakjes.
- **`require_once` stond ná een vroegtijdige `return`** — verplaatst naar vóór alle controles zodat helperfuncties altijd beschikbaar zijn.
- **Refresh-knop vernieuwe de Voorraad-tab niet** — `loadStock()` wordt nu ook aangeroepen vanuit de refresh-handler als de Voorraad-tab actief is.
- **Settings- en edit-popup waren traag** — `backdrop-filter: blur()` verwijderd uit de CSS (GPU-zwaar); vervangen door `rgba`-achtergrond zonder blur.

### Changed
- Plugin-naam in de WordPress-zijbalk gewijzigd van `OrderSync` naar `Order Pulse` (nu: **Product Haven**).
- Voorraadupdate gebruikt `wc_update_product_stock()` in plaats van `set_stock_quantity()/save()` voor correcte hook-triggering.

---

## Notes

- The `[Unreleased]` section is used to stage changes before a version bump.
- When releasing, move the `[Unreleased]` content to a new `[x.y.z] — YYYY-MM-DD` heading and bump the version in `product-haven.php` and `README.md`.
