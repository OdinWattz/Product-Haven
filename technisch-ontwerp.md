# Technisch Ontwerp — Product Haven

**Versie:** 1.3.0  
**Datum:** 2026-03-12  
**Auteur:** Odin Wattez  

---

## 1. Technische stack

| Laag | Technologie |
|------|-------------|
| Platform | WordPress 6.0+ |
| E-commerce | WooCommerce 7.0+ (HPOS-compatibel) |
| Server-side | PHP 7.4+ |
| Frontend (admin) | Vanilla JavaScript (ES2020), geen framework |
| Grafieken | Chart.js 4.4.0 (lokaal gebundeld, geen CDN) |
| Frontend widgets | Elementor 3.0+ (optioneel) |
| Database | MySQL via `$wpdb` (HPOS + legacy fallback) |
| Stijlen | Eigen CSS (geen Bootstrap of Tailwind) |
| Codekwaliteit | PHP_CodeSniffer met WordPress Coding Standards |

---

## 2. Plugin-structuur

```
product-haven/
├── product-haven.php               # Bootstrap: constanten, hooks, asset-enqueue, AJAX-routing
├── uninstall.php                   # Cleanup bij verwijderen
├── includes/
│   ├── i18n.php                    # ph_translations() + ph_t() helper
│   ├── data.php                    # Alle DB-queries, berekeningen, formattering
│   ├── ajax.php                    # AJAX handlers (lazy geladen)
│   ├── sequential-orders.php       # Sequential order number logica
│   ├── quick-products.php          # Quick Products AJAX handlers
│   ├── admin/
│   │   ├── admin-page.php          # Menu registratie + settings save
│   │   └── settings-page.php       # Admin HTML
│   ├── api/
│   │   └── rest-api.php            # Optionele REST API
│   ├── partials/
│   │   ├── sequential-orders-tab.php
│   │   └── quick-products-tab.php
│   └── widgets/
│       ├── stats-widget.php        # Elementor Stats Widget
│       └── timeline-widget.php     # Elementor Timeline Widget
├── assets/
│   ├── images/
│   │   └── product-haven-dashboard.jpg
│   ├── css/
│   │   ├── ph-admin.css
│   │   ├── ph-front.css
│   │   └── sequential-orders.css
│   └── js/
│       ├── ph-admin.js
│       ├── ph-front.js
│       ├── sequential-orders.js
│       └── vendor/
│           └── chart.umd.min.js
└── languages/
```

---

## 3. Constanten

Gedefinieerd in `product-haven.php`:

| Constante | Waarde |
|-----------|--------|
| `PH_PATH` | `plugin_dir_path( __FILE__ )` — absoluut pad naar de pluginmap |
| `PH_URL` | `plugin_dir_url( __FILE__ )` — publieke URL van de pluginmap |
| `PH_VERSION` | `'1.3.2'` — versienummer voor cache-busting van assets |
| `PH_SLUG` | `'product-haven'` — unieke slug voor menu, tekstdomein en opties |

---

## 4. Opstart en laaivolgorde

```
WordPress init
  └── product-haven.php geladen door WordPress
        ├── require_once includes/i18n.php
        ├── require_once includes/admin/admin-page.php
        ├── require_once includes/sequential-orders.php
        ├── add_action('rest_api_init')      → laadt rest-api.php als REST API is ingeschakeld
        ├── add_action('admin_enqueue_scripts') → laadt CSS + JS + wp_localize_script
        ├── add_action('wp_enqueue_scripts')    → laadt frontend assets
        ├── add_action('elementor/widgets/register') → registreert Elementor widgets
        └── add_action('wp_ajax_*')          → registreert AJAX handlers (lazy via ph_ajax_lazy_load)
```

`ajax.php` en `quick-products.php` worden **pas geladen bij een binnenkomende AJAX-aanvraag** via `ph_ajax_lazy_load()`. Dit voorkomt dat zware data-functies bij elke paginaopname worden ingeladen.

---

## 5. Database

### 5.1 WooCommerce HPOS-compatibiliteit

De plugin detecteert automatisch of WooCommerce HPOS gebruikt (`wc_orders`-tabel aanwezig):

```php
$hpos_table = $wpdb->prefix . 'wc_orders';
$use_hpos   = $wpdb->get_var( "SHOW TABLES LIKE '{$hpos_table}'" ) === $hpos_table;
```

Bij HPOS worden queries uitgevoerd op `{prefix}wc_orders`. Bij legacy wordt `wp_posts` + `wp_postmeta` gebruikt. Beide paden zijn geïmplementeerd in:

- `ph_get_stats()`
- `ph_get_chart_data()`
- `ph_get_top_products()`

### 5.2 Eigen logtabel

Bij activatie (en lazy via `init`) wordt aangemaakt:

**`{prefix}ph_stock_log`**

| Kolom | Type | Beschrijving |
|-------|------|--------------|
| `id` | `BIGINT UNSIGNED AUTO_INCREMENT` | Primaire sleutel |
| `product_id` | `BIGINT UNSIGNED` | WooCommerce product-ID |
| `old_stock` | `INT` | Voorraad vóór wijziging |
| `new_stock` | `INT` | Voorraad na wijziging |
| `reason` | `VARCHAR(255)` | Opgegeven reden |
| `changed_at` | `DATETIME` | Tijdstip van wijziging (UTC) |

### 5.3 WordPress options

| Optie | Inhoud |
|-------|--------|
| `ph_options` | Centrale plugin-instellingen (periode, grafiektype, exportkolommen, etc.) |
| `ph_so_options` | Sequential Orders instellingen (prefix, suffix, padding, startnummer) |
| `ph_so_counter` | Huidige waarde van de orderteller |
| `ph_stock_options` | Voorraad-instellingen (drempel, e-mail, alerts aan/uit) |
| `ph_cache_flushed_v4` | Eenmalige migratieflag voor cache-flush |

### 5.4 User meta

| Sleutel | Inhoud |
|---------|--------|
| `ph_lang` | Geselecteerde taal per beheerder (`nl` / `en` / `de` / `fr` / `es`) |

### 5.5 Order meta

| Sleutel | Inhoud |
|---------|--------|
| `_ph_so_order_number` | Numerieke teller (integer) |
| `_ph_so_order_number_full` | Opgemaakte string (prefix + padded number + suffix) |

---

## 6. Caching

Alle zware berekeningen worden gecached als WordPress transients.

| Transient | TTL | Inhoud |
|-----------|-----|--------|
| `ph_stats_{days}_{lang}` | 15 minuten | Kerncijfers (omzet, orders, klanten, refunds) |
| `ph_chart_{days}_{type}_{lang}` | 15 minuten | Dagelijkse grafiekdata (labels, revenue, orders) |
| `ph_top_prod_{days}_{limit}_{lang}` | 30 minuten | Top-N producten op verkochte stuks |
| `ph_stock_alert_{product_id}` | 30 minuten | Cooldown per product voor realtime e-mailalerts |

**Cache-invalidatie:**
- Bij opslaan van instellingen: alle `_transient_ph_*` transients worden verwijderd via directe SQL.
- Bij force-refresh (knop in de UI): transient voor de actieve periode wordt verwijderd.
- Eenmalige migratie: alle `_transient_op_*` (oud prefix) worden verwijderd bij eerste run na update.
- Dagelijkse cron `ph_daily_cache_flush` ruimt verouderde transients op.

Extra veiligheidscheck in `ph_get_stats()`: als een transient ouder is dan 16 minuten (maar de TTL heeft gemist), wordt hij alsnog ververst.

---

## 7. AJAX-architectuur

### 7.1 Admin AJAX

Alle admin-AJAX-aanvragen gaan via `wp-admin/admin-ajax.php` met POST-parameters:

| Parameter | Beschrijving |
|-----------|--------------|
| `action` | Naam van de AJAX-actie (bijv. `ph_get_stats`) |
| `nonce` | WordPress nonce (`ph_admin_nonce`) |
| Overige | Actie-specifieke parameters |

**Authenticatie per aanvraag:**
1. `check_ajax_referer('ph_admin_nonce', 'nonce')` — nonce validatie
2. `current_user_can('manage_woocommerce')` — capability check

### 7.2 Lazy loading via ph_ajax_lazy_load()

Alle AJAX-acties zijn gebonden aan één dispatcher-functie:

```php
function ph_ajax_lazy_load(): void {
    require_once PH_PATH . 'includes/ajax.php';
    require_once PH_PATH . 'includes/quick-products.php';
    $action = sanitize_key( $_POST['action'] ?? '' );
    $fn_map = [ 'ph_get_stats' => 'ph_ajax_get_stats', ... ];
    call_user_func( $fn_map[ $action ] );
}
```

`ajax.php` en `quick-products.php` worden dus maar geladen als er daadwerkelijk een AJAX-aanvraag binnenkomt.

### 7.3 Volledige AJAX-actietabel

| AJAX-actie | PHP-handler | Functie |
|-----------|-------------|---------|
| `ph_set_lang` | inline in `product-haven.php` | Taal opslaan in user meta |
| `ph_get_stats` | `ph_ajax_get_stats` | Statistieken ophalen |
| `ph_get_timeline` | `ph_ajax_get_timeline` | Tijdlijn ophalen (gepagineerd) |
| `ph_get_chart_data` | `ph_ajax_get_chart_data` | Grafiekdata ophalen |
| `ph_export_csv` | `ph_ajax_export_csv` | CSV streamen naar browser |
| `ph_get_customer` | `ph_ajax_get_customer` | Klantenkaart ophalen |
| `ph_get_single_order` | `ph_ajax_get_single_order` | Één order ophalen (cache-bypass) |
| `ph_update_order_note` | `ph_ajax_update_order_note` | Notitie toevoegen aan order |
| `ph_update_order_status` | `ph_ajax_update_order_status` | Orderstatus wijzigen |
| `ph_delete_order` | `ph_ajax_delete_order` | Order permanent verwijderen |
| `ph_process_refund` | `ph_ajax_process_refund` | Terugbetaling aanvragen |
| `ph_revert_refund` | `ph_ajax_revert_refund` | Terugbetaling terugdraaien |
| `ph_get_top_products` | `ph_ajax_get_top_products` | Top-producten ophalen |
| `ph_get_revenue_report` | `ph_ajax_get_revenue_report` | Omzetrapport |
| `ph_get_top_categories` | `ph_ajax_get_top_categories` | Categorieën rapport |
| `ph_get_coupons_report` | `ph_ajax_get_coupons_report` | Couponsrapport |
| `ph_get_daily_products` | `ph_ajax_get_daily_products` | Dagelijks productenrapport |
| `ph_get_top_customers` | `ph_ajax_get_top_customers` | Top-klantenrapport |
| `ph_front_stats` | `ph_ajax_front_stats` | Frontend stats (ingelogde klant) |
| `ph_front_timeline` | `ph_ajax_front_timeline` | Frontend tijdlijn (ingelogde klant) |
| `ph_get_low_stock` | `ph_ajax_get_low_stock` | Lage voorraad ophalen |
| `ph_update_stock` | `ph_ajax_update_stock` | Voorraad bijwerken (dashboard modal) |
| `ph_stock_get` | `ph_ajax_stock_get` | Voorraadtabel ophalen |
| `ph_stock_update` | `ph_ajax_stock_update` | Voorraad bijwerken (stock tab) |
| `ph_stock_export_csv` | `ph_ajax_stock_export_csv` | Voorraad CSV exporteren |
| `ph_stock_save_settings` | `ph_ajax_stock_save_settings` | Voorraadinstellingen opslaan |
| `ph_stock_send_test_alert` | `ph_ajax_stock_send_test_alert` | Test-alertmail sturen |
| `ph_qp_get_products` | `ph_ajax_qp_get_products` | Productlijst ophalen |
| `ph_qp_save_product` | `ph_ajax_qp_save_product` | Product opslaan (nieuw + update) |
| `ph_qp_load_product` | `ph_ajax_qp_load_product` | Één product laden in editor |
| `ph_qp_delete_product` | `ph_ajax_qp_delete_product` | Product verwijderen |
| `ph_qp_duplicate_product` | `ph_ajax_qp_duplicate_product` | Product dupliceren |
| `ph_qp_quick_edit` | `ph_ajax_qp_quick_edit` | Inline bewerken (prijs/voorraad) |
| `ph_qp_save_order` | `ph_ajax_qp_save_order` | Billing-velden order opslaan |

### 7.4 Frontend AJAX

| AJAX-actie | Nonce | Toegang |
|-----------|-------|---------|
| `ph_front_stats` | `ph_front_nonce` | Ingelogde gebruiker |
| `ph_front_timeline` | `ph_front_nonce` | Ingelogde gebruiker |
| `ph_front_stats` (nopriv) | `ph_front_nonce` | Niet-ingelogd → HTTP 401 |

---

## 8. Data-laag (data.php)

`data.php` bevat alle database-functies en is de enige plek waar directe SQL-queries voorkomen. Het bestand wordt geladen door `ajax.php` (`require_once`).

### 8.1 Functies

| Functie | Parameters | Terugkeerwaarde |
|---------|------------|-----------------|
| `ph_get_stats(int $days)` | Periode in dagen | Array met revenue, orders, avg_order, new_customers, returning, refunds, top_products |
| `ph_get_chart_data(int $days, string $type)` | Periode, type (revenue/orders/both) | Array met labels[], revenue[], orders[] |
| `ph_get_timeline(array $args)` | page, per_page, status, search, days | Array met orders[], total, total_pages, page |
| `ph_format_order(WC_Order $order)` | WC_Order object | Compacte array met alle ordergegevens voor de UI |
| `ph_get_order_notes(int $order_id)` | Order-ID | Array van notities (content, date, added_by, customer_note) |
| `ph_get_customer_card(int $id, string $email)` | Klant-ID en/of e-mail | Klantprofiel + orderhistorie |
| `ph_get_low_stock(int $threshold)` | Drempelwaarde | Array van producten met lage/geen voorraad |
| `ph_update_stock(int $id, int $new, string $reason)` | Product-ID, nieuwe hoeveelheid, reden | Success/error array |
| `ph_get_top_products(int $days, int $limit)` | Periode, limiet | Top-N producten (naam, qty, revenue, afbeelding) |
| `ph_stock_create_table()` | — | Maakt `{prefix}ph_stock_log` aan |
| `ph_stock_check_single($product)` | WC_Product | Stuurt alert als onder drempel |
| `ph_stock_send_alert()` | — | Stuurt dagelijkse digest |

### 8.2 ph_format_order — output-structuur

```php
[
  'id'           => int,
  'number'       => string,          // Sequentieel ordernummer (of post-ID)
  'status'       => string,          // WC-statusslug (bijv. 'completed')
  'status_label' => string,          // Vertaalde statuslabel
  'date'         => string,          // Opgemaakte datum
  'date_human'   => int,             // Unix timestamp (voor time-ago rendering)
  'customer'     => [
    'name', 'first_name', 'last_name', 'company',
    'email', 'phone', 'address_1', 'address_2',
    'city', 'postcode', 'country', 'id'
  ],
  'total'        => string,          // Opgemaakte prijsstring (wc_price)
  'total_raw'    => float,
  'items'        => [ ['name', 'qty', 'subtotal'], ... ],
  'items_count'  => int,
  'payment'      => string,
  'notes'        => [ ['content', 'date', 'added_by', 'customer_note'], ... ],
  'edit_url'     => string,
  'view_url'     => string,
  'refunded'     => bool,
  'refund_amt'   => string,
]
```

---

## 9. i18n-systeem

### 9.1 Structuur

`i18n.php` exporteert twee functies:

```php
function ph_translations(): array  // Retourneert de volledige vertaaltabel
function ph_t(string $key, string $lang = ''): string  // Haalt één string op
```

De vertaaltabel is een associatieve array:
```php
[
  'key_naam' => [
    'nl' => '...',
    'en' => '...',
    'de' => '...',
    'fr' => '...',
    'es' => '...',
  ],
  ...
]
```

### 9.2 Taaldetectie

```php
function ph_get_lang(): string {
    return get_user_meta( get_current_user_id(), 'ph_lang', true ) ?: 'nl';
}
```

### 9.3 JS-localisatie

Alle vertaalstrings worden via `wp_localize_script()` doorgegeven aan JavaScript als `ph_admin.i18n`. Dit object bevat meer dan 130 sleutels. JavaScript gebruikt nooit hardcoded tekst.

### 9.4 Taalwisseling

Via AJAX-actie `ph_set_lang`:
1. Nonce + capability check
2. Validatie: taal moet in `['nl', 'en', 'de', 'fr', 'es']` zitten
3. Opslaan in `ph_lang` user meta
4. Pagina herladen aan clientzijde

---

## 10. Asset-loading

### 10.1 Admin assets

Geladen via `admin_enqueue_scripts`, alleen op pagina's met `product-haven` in de hook:

| Handle | Bestand | Afhankelijkheden |
|--------|---------|-----------------|
| `ph-admin-css` | `assets/css/ph-admin.css` | — |
| `op-sequential-css` | `assets/css/sequential-orders.css` | — |
| `ph-chart-js` | `assets/js/vendor/chart.umd.min.js` | — |
| `ph-admin-js` | `assets/js/ph-admin.js` | `ph-chart-js` |
| `op-sequential-js` | `assets/js/sequential-orders.js` | — |

`wp_enqueue_media()` wordt geladen voor de Quick Products afbeelding/galerij-uploader.

### 10.2 Frontend assets

Geladen via `wp_enqueue_scripts` + `elementor/editor/after_enqueue_scripts`:

| Handle | Bestand |
|--------|---------|
| `ph-front-css` | `assets/css/ph-front.css` |
| `ph-chart-front` | `assets/js/vendor/chart.umd.min.js` |
| `ph-front-js` | `assets/js/ph-front.js` |

### 10.3 Gelocaliseerde data (ph_admin)

Via `wp_localize_script('ph-admin-js', 'ph_admin', [...])`:

```js
ph_admin = {
  ajax_url: '...',
  nonce:    '...',
  currency: '€',
  lang:     'nl',
  locale:   'nl-NL',   // Per taal: nl-NL / en-GB / de-DE / fr-FR / es-ES
  i18n:     { ... }    // >130 vertaalsleutels
}
```

---

## 11. Sequential Orders

### 11.1 Hooks

| Hook | Functie | Beschrijving |
|------|---------|--------------|
| `woocommerce_checkout_order_created` | `ph_so_assign_order_number` | Nummer toewijzen bij standaard checkout |
| `woocommerce_store_api_checkout_order_processed` | `ph_so_assign_order_number` | Nummer toewijzen bij Blocks checkout |
| `woocommerce_order_number` | `ph_so_filter_order_number` | Opgemaakte string tonen i.p.v. post-ID |
| `woocommerce_shop_order_search_fields` | `ph_so_add_search_fields` | Zoeken op sequentieel nummer in WC-admin |
| `admin_post_ph_so_save_settings` | `ph_so_save_settings` | Instellingen opslaan via formulier |

### 11.2 Thread-safe teller

```php
$wpdb->query( "SELECT GET_LOCK('ph_so_order_counter', 5)" );
$next = max( $start, $current + 1 );
update_option( 'ph_so_counter', $next, false );
$wpdb->query( "SELECT RELEASE_LOCK('ph_so_order_counter')" );
```

De MySQL GET_LOCK/RELEASE_LOCK mechanisme garandeert dat gelijktijdige checkouts geen dubbele nummers krijgen.

---

## 12. Voorraad — hooks en alerts

### 12.1 Stock hooks

| Hook | Functie | Beschrijving |
|------|---------|--------------|
| `woocommerce_product_set_stock` | `ph_handle_stock_change` | Realtime alert bij kwantiteitswijziging |
| `woocommerce_variation_set_stock` | `ph_handle_stock_change` | Idem voor variaties |
| `woocommerce_product_set_stock_status` | `ph_handle_stock_status_change` | Backup hook voor statuswijziging |
| `ph_stock_daily_alert` (cron) | inline closure | Dagelijkse digest sturen |
| `woocommerce_email_enabled_low_stock` | `__return_false` | WC eigen alerts uitschakelen |
| `woocommerce_email_enabled_no_stock` | `__return_false` | Idem |

### 12.2 Alert-flow

```
Stock gewijzigd via WC
  → ph_handle_stock_change() aangeroepen
    → realtime_alerts instelling check
    → cooldown transient check (30 min per product)
    → ph_stock_check_single(): drempel check
      → e-mail sturen als onder drempel
```

---

## 13. Beveiliging

### 13.1 Nonce-verificatie

Elke AJAX-handler begint met `ph_verify_admin_nonce()` of `ph_verify_front_nonce()`:

```php
function ph_verify_admin_nonce(): void {
    if ( ! check_ajax_referer( 'ph_admin_nonce', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => 'Ongeldige nonce.' ], 403 );
    }
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_send_json_error( [ 'message' => 'Geen toegang.' ], 403 );
    }
}
```

### 13.2 Invoersanitisatie

| Invoertype | Sanitisatiefunctie |
|------------|-------------------|
| Tekstveld | `sanitize_text_field( wp_unslash() )` |
| Geheel getal | `absint()` |
| Sleutelstring | `sanitize_key()` |
| E-mailadres | `sanitize_email()` |
| HTML-inhoud | `wp_kses_post()` |
| Kleurcode | `sanitize_hex_color()` |
| Array van integers | `array_map('absint', ...)` |

### 13.3 SQL-injectiepreventie

- Gebruikersinvoer gaat altijd via `$wpdb->prepare()`
- Tabelnamen worden geïnterpoleerd vanuit `$wpdb->prefix` (intern vertrouwde data, geen gebruikersinvoer)
- PHPCS-suppressiecommentaar aanwezig met toelichting waar directe queries noodzakelijk zijn

### 13.4 CSV-export

De CSV-export streamt direct via PHP output buffers. Er worden geen tijdelijke bestanden naar disk geschreven.

### 13.5 Frontend endpoints

- Vereisen `ph_front_nonce`
- Retourneren alleen data van de ingelogde gebruiker
- Unauthenticated: HTTP 401

### 13.6 REST API

- Uitgeschakeld by default
- Vereist `manage_woocommerce` capability
- Geen publieke endpoints

---

## 14. Activatie en deactivatie

### Activatie (`register_activation_hook`)

- Schrijft standaardwaarden naar `ph_options` (als de optie nog niet bestaat)

### Init (bij elke request)

- Controleert of `{prefix}ph_stock_log` bestaat; maakt hem aan indien niet
- Plant `ph_stock_daily_alert` cron in als die nog niet gepland staat

### Deactivatie (`register_deactivation_hook`)

- Verwijdert geplande crons: `ph_daily_cache_flush` en `ph_stock_daily_alert`

### Verwijderen (`uninstall.php`)

- Ruimt plugin-data op uit de database (opties, transients, logtabel)

---

## 15. Elementor-widgets

Beide widgets worden geregistreerd via `elementor/widgets/register` (alleen als Elementor geladen is).

### Stats Widget (`\ProductHaven\Stats_Widget`)

- Toont: totale omzet, orderaantal, gemiddeld orderbedrag
- Data via AJAX: `ph_front_stats`
- Rendering volledig client-side

### Timeline Widget (`\ProductHaven\Timeline_Widget`)

- Gepagineerde orderhistorie
- Data via AJAX: `ph_front_timeline`
- Statusbadges en productlijst per order

Beide widgets vallen onder de categorie `ph-category` in de Elementor-editor.

---

## 16. Cron-taken

| Cron-hook | Frequentie | Taak |
|-----------|-----------|------|
| `ph_stock_daily_alert` | Dagelijks | Dagelijkse voorraad-digest e-mail sturen |
| `ph_daily_cache_flush` | Dagelijks | Verouderde `ph_*` transients opschonen |

---

## 17. Bekende beperkingen en aandachtspunten

- **Directe SQL noodzakelijk**: WooCommerce HPOS-tabelnamen zijn dynamisch en kunnen niet via `$wpdb->prepare()` als placeholder worden meegegeven. Tabelnamen worden altijd afgeleid van `$wpdb->prefix` (niet van gebruikersinvoer).
- **PHPCS-suppressie**: Diverse regels zijn gesuppressed met toelichting waar WordPress Coding Standards botsen met de HPOS-aanpak.
- **Quick Products ondersteunt alleen eenvoudige producten**: Variabele producten met variaties worden niet ondersteund in de plugin-editor.
- **Multisite**: Niet getest of ondersteund.
- **Cache-TTL safety check**: `ph_get_stats()` heeft een extra controle op `_cached_at` om te voorkomen dat een transient zonder timeout eeuwig blijft hangen.
- **Elementor afhankelijkheid**: De widgets zijn optioneel; de plugin werkt volledig zonder Elementor.
