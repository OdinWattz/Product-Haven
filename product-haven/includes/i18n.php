<?php
/**
 * Product Haven — Taal / i18n helper
 *
 * Biedt een eenvoudige NL↔EN language switcher die de voorkeur per gebruiker
 * opslaat in user_meta ('ph_lang'). Standaardtaal is Nederlands ('nl').
 *
 * Gebruik: ph_t( 'sleutel' )  → geeft de vertaling terug als string.
 *
 * @package ProductHaven
 */

defined( 'ABSPATH' ) || exit;

/**
 * Give the active language code back ('nl', 'en', 'fr', 'de' or 'es').
 */
function ph_get_lang(): string { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
    $user_id = get_current_user_id();
    if ( ! $user_id ) return 'nl';
    $lang = get_user_meta( $user_id, 'ph_lang', true );
    return in_array( $lang, [ 'nl', 'en', 'fr', 'de', 'es' ], true ) ? $lang : 'nl';
}

/**
 * All UI-strings for the plugin grouped by language.
 *
 * @return array<string, array<string, string>>
 */
function ph_translations(): array { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
    return [

        /* ---- General / Header ---- */
        'subtitle'                  => [ 'nl' => 'Statistieken, Order beheer & Producten beheer',       'en' => 'Statistics, Order management & Product management',          'de' => 'Statistiken, Auftragsverwaltung & Produktverwaltung',        'fr' => 'Statistiques, gestion des commandes & des produits',       'es' => 'Estadísticas, gestión de pedidos y productos' ],
        'refresh'                   => [ 'nl' => 'Vernieuwen',             'en' => 'Refresh',                   'de' => 'Aktualisieren',              'fr' => 'Actualiser',                'es' => 'Actualizar' ],
        'csv_export'                => [ 'nl' => 'CSV export',             'en' => 'CSV export',                'de' => 'CSV-Export',                 'fr' => 'Export CSV',                'es' => 'Exportar CSV' ],
        'refresh_title'             => [ 'nl' => 'Statistieken vernieuwen','en' => 'Refresh statistics',        'de' => 'Statistiken aktualisieren',  'fr' => 'Actualiser les statistiques','es' => 'Actualizar estadísticas' ],
        'lang_nl'                   => [ 'nl' => 'NL',                     'en' => 'NL',                        'de' => 'NL',                         'fr' => 'NL',                        'es' => 'NL' ],
        'lang_en'                   => [ 'nl' => 'EN',                     'en' => 'EN',                        'de' => 'EN',                         'fr' => 'EN',                        'es' => 'EN' ],
        'lang_de'                   => [ 'nl' => 'DE',                     'en' => 'DE',                        'de' => 'DE',                         'fr' => 'DE',                        'es' => 'DE' ],
        'lang_fr'                   => [ 'nl' => 'FR',                     'en' => 'FR',                        'de' => 'FR',                         'fr' => 'FR',                        'es' => 'FR' ],
        'lang_es'                   => [ 'nl' => 'ES',                     'en' => 'ES',                        'de' => 'ES',                         'fr' => 'ES',                        'es' => 'ES' ],
        'switch_to_nl'              => [ 'nl' => 'Wissel naar Nederlands',  'en' => 'Switch to Dutch',          'de' => 'Zu Niederländisch wechseln', 'fr' => 'Passer au neerlandais',     'es' => 'Cambiar a neerlandes' ],
        'switch_to_en'              => [ 'nl' => 'Wissel naar Engels',      'en' => 'Switch to English',        'de' => 'Zu Englisch wechseln',       'fr' => 'Passer a l\'anglais',       'es' => 'Cambiar a ingles' ],
        'switch_to_de'              => [ 'nl' => 'Wissel naar Duits',       'en' => 'Switch to German',         'de' => 'Zu Deutsch wechseln',        'fr' => 'Passer a l\'allemand',      'es' => 'Cambiar a aleman' ],
        'switch_to_fr'              => [ 'nl' => 'Wissel naar Frans',       'en' => 'Switch to French',         'de' => 'Zu Französisch wechseln',    'fr' => 'Passer au francais',        'es' => 'Cambiar a frances' ],
        'switch_to_es'              => [ 'nl' => 'Wissel naar Spaans',      'en' => 'Switch to Spanish',        'de' => 'Zu Spanisch wechseln',       'fr' => 'Passer a l\'espagnol',      'es' => 'Cambiar a espanol' ],

        /* ---- Tabs ---- */
        'tab_dashboard'             => [ 'nl' => 'Dashboard',              'en' => 'Dashboard',                 'de' => 'Dashboard',                  'fr' => 'Tableau de bord',           'es' => 'Panel' ],
        'tab_revenue'               => [ 'nl' => 'Omzet',                  'en' => 'Revenue',                   'de' => 'Umsatz',                     'fr' => 'Chiffre d\'affaires',       'es' => 'Ingresos' ],
        'tab_categories'            => [ 'nl' => 'Categorieën',            'en' => 'Categories',                'de' => 'Kategorien',                 'fr' => 'Catégories',                'es' => 'Categorías' ],
        'tab_coupons'               => [ 'nl' => 'Coupons',                'en' => 'Coupons',                   'de' => 'Gutscheine',                 'fr' => 'Coupons',                   'es' => 'Cupones' ],
        'tab_daily'                 => [ 'nl' => 'Dag rapport',            'en' => 'Daily report',              'de' => 'Tagesbericht',               'fr' => 'Rapport journalier',        'es' => 'Informe diario' ],
        'tab_customers'             => [ 'nl' => 'Klanten',                'en' => 'Customers',                 'de' => 'Kunden',                     'fr' => 'Clients',                   'es' => 'Clientes' ],
        'tab_stock'                 => [ 'nl' => 'Voorraad',               'en' => 'Stock',                     'de' => 'Lagerbestand',               'fr' => 'Stock',                     'es' => 'Inventario' ],
        'tab_products'              => [ 'nl' => 'Quick Products',         'en' => 'Quick Products',            'de' => 'Quick Products',             'fr' => 'Quick Products',            'es' => 'Quick Products' ],
        'tab_sequential'            => [ 'nl' => 'Bestelnummers',          'en' => 'Order numbers',             'de' => 'Bestellnummern',             'fr' => 'Numéros de commande',       'es' => 'Números de pedido' ],
        'tab_settings'              => [ 'nl' => 'Instellingen',           'en' => 'Settings',                  'de' => 'Einstellungen',              'fr' => 'Paramètres',                'es' => 'Configuración' ],

        /* ---- Stat cards ---- */
        'stat_revenue'              => [ 'nl' => 'Omzet',                  'en' => 'Revenue',                   'de' => 'Umsatz',                     'fr' => 'Chiffre d\'affaires',       'es' => 'Ingresos' ],
        'stat_orders'               => [ 'nl' => 'Orders',                 'en' => 'Orders',                    'de' => 'Bestellungen',               'fr' => 'Commandes',                 'es' => 'Pedidos' ],
        'stat_avg_order'            => [ 'nl' => 'Gem. orderwaarde',       'en' => 'Avg. order value',          'de' => 'Ø Bestellwert',              'fr' => 'Valeur moy. commande',      'es' => 'Valor medio pedido' ],
        'stat_new_customers'        => [ 'nl' => 'Nieuwe klanten',         'en' => 'New customers',             'de' => 'Neukunden',                  'fr' => 'Nouveaux clients',          'es' => 'Nuevos clientes' ],

        /* ---- Dashboard chart ---- */
        'chart_revenue_orders'      => [ 'nl' => 'Omzet & Orders',         'en' => 'Revenue & Orders',          'de' => 'Umsatz & Bestellungen',      'fr' => 'CA & Commandes',            'es' => 'Ingresos & Pedidos' ],
        'chart_revenue'             => [ 'nl' => 'Omzet',                  'en' => 'Revenue',                   'de' => 'Umsatz',                     'fr' => 'Chiffre d\'affaires',       'es' => 'Ingresos' ],
        'chart_orders'              => [ 'nl' => 'Orders',                 'en' => 'Orders',                    'de' => 'Bestellungen',               'fr' => 'Commandes',                 'es' => 'Pedidos' ],

        /* ---- Order timeline ---- */
        'order_timeline'            => [ 'nl' => 'Ordertijdlijn',          'en' => 'Order timeline',            'de' => 'Bestellungszeitleiste',      'fr' => 'Chronologie des commandes', 'es' => 'Línea de tiempo de pedidos' ],
        'search_order_placeholder'  => [ 'nl' => 'Zoek order, naam, e-mail…', 'en' => 'Search order, name, e-mail…', 'de' => 'Bestellung, Name, E-Mail suchen…', 'fr' => 'Chercher commande, nom, e-mail…', 'es' => 'Buscar pedido, nombre, e-mail…' ],
        'all_statuses'              => [ 'nl' => 'Alle statussen',         'en' => 'All statuses',              'de' => 'Alle Status',                'fr' => 'Tous les statuts',          'es' => 'Todos los estados' ],

        /* ---- Top products ---- */
        'top_products'              => [ 'nl' => 'Top producten',          'en' => 'Top products',              'de' => 'Top-Produkte',               'fr' => 'Meilleurs produits',        'es' => 'Mejores productos' ],
        'load_more'                 => [ 'nl' => '+ Meer laden',           'en' => '+ Load more',               'de' => '+ Mehr laden',               'fr' => '+ Charger plus',            'es' => '+ Cargar más' ],

        /* ---- Low stock ---- */
        'low_stock'                 => [ 'nl' => 'Lage voorraad',          'en' => 'Low stock',                 'de' => 'Niedriger Bestand',          'fr' => 'Stock faible',              'es' => 'Stock bajo' ],
        'low_stock_threshold_label' => [ 'nl' => 'Grens:',                 'en' => 'Threshold:',                'de' => 'Grenzwert:',                 'fr' => 'Seuil :',                   'es' => 'Umbral:' ],

        /* ---- Modals: Delete confirmation ---- */
        'delete_order'              => [ 'nl' => 'Bestelling verwijderen', 'en' => 'Delete order',              'de' => 'Bestellung löschen',         'fr' => 'Supprimer la commande',     'es' => 'Eliminar pedido' ],
        'irreversible'              => [ 'nl' => 'Dit kan niet ongedaan worden gemaakt.', 'en' => 'This cannot be undone.', 'de' => 'Dies kann nicht rückgängig gemacht werden.', 'fr' => 'Cette action est irréversible.', 'es' => 'Esta acción no se puede deshacer.' ],
        'cancel'                    => [ 'nl' => 'Annuleren',              'en' => 'Cancel',                    'de' => 'Abbrechen',                  'fr' => 'Annuler',                   'es' => 'Cancelar' ],
        'delete_permanent'          => [ 'nl' => 'Permanent verwijderen',  'en' => 'Delete permanently',        'de' => 'Dauerhaft löschen',          'fr' => 'Supprimer définitivement',  'es' => 'Eliminar permanentemente' ],

        /* ---- Modal: retour ---- */
        'refund_title'              => [ 'nl' => 'Retour verwerken',       'en' => 'Process refund',            'de' => 'Rückerstattung verarbeiten', 'fr' => 'Traiter le remboursement',  'es' => 'Procesar reembolso' ],
        'refund_option_label'       => [ 'nl' => 'Terugbetalingsoptie',    'en' => 'Refund option',             'de' => 'Rückerstattungsoption',      'fr' => 'Option de remboursement',   'es' => 'Opción de reembolso' ],
        'refund_status_only_title'  => [ 'nl' => 'Alleen status op "Retour"', 'en' => 'Status to "Refunded" only', 'de' => 'Status nur auf „Erstattet"', 'fr' => 'Statut sur « Remboursé » uniquement', 'es' => 'Solo estado a "Reembolsado"' ],
        'refund_status_only_desc'   => [ 'nl' => 'Geen financiële terugboeking — alleen de orderstatus wijzigen.', 'en' => 'No financial refund — only change the order status.', 'de' => 'Keine finanzielle Erstattung — nur den Bestellstatus ändern.', 'fr' => 'Aucun remboursement financier — changer uniquement le statut.', 'es' => 'Sin reembolso financiero — solo cambiar el estado del pedido.' ],
        'refund_full_title'         => [ 'nl' => 'Volledige terugbetaling aanmaken', 'en' => 'Create full refund', 'de' => 'Vollständige Rückerstattung erstellen', 'fr' => 'Créer un remboursement complet', 'es' => 'Crear reembolso completo' ],
        'refund_full_desc'          => [ 'nl' => 'Maakt een WooCommerce terugbetaling aan voor het volledige bedrag.', 'en' => 'Creates a WooCommerce refund for the full amount.', 'de' => 'Erstellt eine WooCommerce-Rückerstattung für den vollen Betrag.', 'fr' => 'Crée un remboursement WooCommerce pour le montant total.', 'es' => 'Crea un reembolso de WooCommerce por el importe total.' ],
        'revert_option_label'       => [ 'nl' => 'Terugdraai-optie',      'en' => 'Revert option',             'de' => 'Rückgängig-Option',          'fr' => 'Option d\'annulation',      'es' => 'Opción de reversión' ],
        'revert_processing'         => [ 'nl' => 'Terugzetten naar "In behandeling"', 'en' => 'Revert to "Processing"', 'de' => 'Zurück zu „In Bearbeitung"', 'fr' => 'Revenir à « En cours »',   'es' => 'Volver a "En proceso"' ],
        'revert_on_hold'            => [ 'nl' => 'Terugzetten naar "In de wacht"',   'en' => 'Revert to "On hold"',    'de' => 'Zurück zu „Wartend"',        'fr' => 'Revenir à « En attente »', 'es' => 'Volver a "En espera"' ],
        'revert_completed'          => [ 'nl' => 'Terugzetten naar "Voltooid"',       'en' => 'Revert to "Completed"',  'de' => 'Zurück zu „Abgeschlossen"',  'fr' => 'Revenir à « Terminé »',    'es' => 'Volver a "Completado"' ],
        'delete_refund_records'     => [ 'nl' => 'Bestaande terugbetalingen verwijderen', 'en' => 'Delete existing refund records', 'de' => 'Vorhandene Rückerstattungen löschen', 'fr' => 'Supprimer les remboursements existants', 'es' => 'Eliminar registros de reembolso existentes' ],
        'delete_refund_records_desc'=> [ 'nl' => 'Verwijdert WooCommerce terugbetalingsrecords voor deze order. Kan niet ongedaan worden gemaakt.', 'en' => 'Deletes WooCommerce refund records for this order. Cannot be undone.', 'de' => 'Löscht WooCommerce-Rückerstattungsdatensätze für diese Bestellung. Kann nicht rückgängig gemacht werden.', 'fr' => 'Supprime les enregistrements de remboursement WooCommerce. Irréversible.', 'es' => 'Elimina los registros de reembolso de WooCommerce. No se puede deshacer.' ],
        'confirm'                   => [ 'nl' => 'Bevestigen',             'en' => 'Confirm',                   'de' => 'Bestätigen',                 'fr' => 'Confirmer',                 'es' => 'Confirmar' ],

        /* ---- Modal: Edit stock (old) ---- */
        'edit_stock'                => [ 'nl' => 'Voorraad aanpassen',     'en' => 'Edit stock',                'de' => 'Bestand bearbeiten',         'fr' => 'Modifier le stock',         'es' => 'Editar stock' ],
        'new_stock'                 => [ 'nl' => 'Nieuwe voorraad',        'en' => 'New stock',                 'de' => 'Neuer Bestand',              'fr' => 'Nouveau stock',             'es' => 'Nuevo stock' ],
        'reason_optional'           => [ 'nl' => 'Reden (optioneel)',      'en' => 'Reason (optional)',         'de' => 'Grund (optional)',           'fr' => 'Raison (facultatif)',       'es' => 'Motivo (opcional)' ],
        'reason_placeholder'        => [ 'nl' => 'Bijv. Nieuwe levering ontvangen', 'en' => 'E.g. New shipment received', 'de' => 'Z.B. Neue Lieferung erhalten', 'fr' => 'Ex. Nouvelle livraison reçue', 'es' => 'Ej. Nueva entrega recibida' ],
        'save'                      => [ 'nl' => 'Opslaan',                'en' => 'Save',                      'de' => 'Speichern',                  'fr' => 'Enregistrer',               'es' => 'Guardar' ],

        /* ---- Tab: Revenue ---- */
        'revenue_overview'          => [ 'nl' => 'Omzetoverzicht',         'en' => 'Revenue overview',          'de' => 'Umsatzübersicht',            'fr' => 'Aperçu du chiffre d\'affaires', 'es' => 'Resumen de ingresos' ],

        /* ---- Tab: Categories ---- */
        'top_categories'            => [ 'nl' => 'Top categorieën',        'en' => 'Top categories',            'de' => 'Top-Kategorien',             'fr' => 'Meilleures catégories',     'es' => 'Principales categorías' ],

        /* ---- Tab: Coupons ---- */
        'coupon_usage'              => [ 'nl' => 'Coupongebruik',          'en' => 'Coupon usage',              'de' => 'Gutscheinnutzung',           'fr' => 'Utilisation des coupons',   'es' => 'Uso de cupones' ],

        /* ---- Tab: Daily report ---- */
        'daily_report_title'        => [ 'nl' => 'Dag rapport — verkochte producten', 'en' => 'Daily report — sold products', 'de' => 'Tagesbericht — verkaufte Produkte', 'fr' => 'Rapport journalier — produits vendus', 'es' => 'Informe diario — productos vendidos' ],
        'select_date'               => [ 'nl' => 'Selecteer datum:',       'en' => 'Select date:',              'de' => 'Datum auswählen:',           'fr' => 'Sélectionner une date :',   'es' => 'Seleccionar fecha:' ],
        'load'                      => [ 'nl' => 'Laden',                  'en' => 'Load',                      'de' => 'Laden',                      'fr' => 'Charger',                   'es' => 'Cargar' ],

        /* ---- Tab: Customers ---- */
        'top_customers'             => [ 'nl' => 'Top klanten',            'en' => 'Top customers',             'de' => 'Top-Kunden',                 'fr' => 'Meilleurs clients',         'es' => 'Principales clientes' ],

        /* ---- Tab: Stock ---- */
        'stock_out'                 => [ 'nl' => 'Uitverkocht',            'en' => 'Out of stock',              'de' => 'Ausverkauft',                'fr' => 'Rupture de stock',          'es' => 'Sin stock' ],
        'stock_low'                 => [ 'nl' => 'Lage voorraad',          'en' => 'Low stock',                 'de' => 'Niedriger Bestand',          'fr' => 'Stock faible',              'es' => 'Stock bajo' ],
        'stock_ok'                  => [ 'nl' => 'Op voorraad',            'en' => 'In stock',                  'de' => 'Auf Lager',                  'fr' => 'En stock',                  'es' => 'En stock' ],
        'stock_total'               => [ 'nl' => 'Totaal producten',       'en' => 'Total products',            'de' => 'Produkte gesamt',            'fr' => 'Total produits',            'es' => 'Total de productos' ],
        'filter_all'                => [ 'nl' => 'Alles',                  'en' => 'All',                       'de' => 'Alle',                       'fr' => 'Tout',                      'es' => 'Todo' ],
        'filter_out'                => [ 'nl' => 'Uitverkocht',            'en' => 'Out of stock',              'de' => 'Ausverkauft',                'fr' => 'Rupture',                   'es' => 'Sin stock' ],
        'filter_low'                => [ 'nl' => 'Laag',                   'en' => 'Low',                       'de' => 'Niedrig',                    'fr' => 'Faible',                    'es' => 'Bajo' ],
        'filter_ok'                 => [ 'nl' => 'Op voorraad',            'en' => 'In stock',                  'de' => 'Auf Lager',                  'fr' => 'En stock',                  'es' => 'En stock' ],
        'search_stock_placeholder'  => [ 'nl' => 'Zoek op naam of SKU…',  'en' => 'Search by name or SKU…',    'de' => 'Nach Name oder SKU suchen…', 'fr' => 'Rechercher par nom ou SKU…','es' => 'Buscar por nombre o SKU…' ],
        'settings_btn'              => [ 'nl' => 'Instellingen',           'en' => 'Settings',                  'de' => 'Einstellungen',              'fr' => 'Paramètres',                'es' => 'Ajustes' ],
        'selected_count'            => [ 'nl' => '0 geselecteerd',         'en' => '0 selected',                'de' => '0 ausgewählt',               'fr' => '0 selectionne',             'es' => '0 seleccionado' ],
        'bulk_update_stock'         => [ 'nl' => 'Voorraad bijwerken',     'en' => 'Update stock',              'de' => 'Bestand aktualisieren',      'fr' => 'Mettre a jour le stock',    'es' => 'Actualizar stock' ],
        'col_product'               => [ 'nl' => 'Product',                'en' => 'Product',                   'de' => 'Produkt',                    'fr' => 'Produit',                   'es' => 'Producto' ],
        'col_sku'                   => [ 'nl' => 'SKU',                    'en' => 'SKU',                       'de' => 'SKU',                        'fr' => 'SKU',                       'es' => 'SKU' ],
        'col_stock'                 => [ 'nl' => 'Voorraad',               'en' => 'Stock',                     'de' => 'Bestand',                    'fr' => 'Stock',                     'es' => 'Stock' ],
        'col_status'                => [ 'nl' => 'Status',                 'en' => 'Status',                    'de' => 'Status',                     'fr' => 'Statut',                    'es' => 'Estado' ],
        'col_price'                 => [ 'nl' => 'Prijs',                  'en' => 'Price',                     'de' => 'Preis',                      'fr' => 'Prix',                      'es' => 'Precio' ],
        'col_stock_value'           => [ 'nl' => 'Voorraadwaarde',         'en' => 'Stock value',               'de' => 'Lagerwert',                  'fr' => 'Valeur du stock',           'es' => 'Valor del stock' ],
        'col_actions'               => [ 'nl' => 'Acties',                 'en' => 'Actions',                   'de' => 'Aktionen',                   'fr' => 'Actions',                   'es' => 'Acciones' ],
        'no_products_found'         => [ 'nl' => 'Geen producten gevonden.','en' => 'No products found.',       'de' => 'Keine Produkte gefunden.',   'fr' => 'Aucun produit trouve.',     'es' => 'No se encontraron productos.' ],

        /* ---- Stock settings panel ---- */
        'stock_settings'            => [ 'nl' => 'Voorraad instellingen',  'en' => 'Stock settings',            'de' => 'Lagereinstellungen',         'fr' => 'Parametres de stock',       'es' => 'Ajustes de stock' ],
        'low_stock_threshold'       => [ 'nl' => 'Lage voorraad drempel',  'en' => 'Low stock threshold',       'de' => 'Mindestbestandsgrenze',      'fr' => 'Seuil de stock faible',     'es' => 'Umbral de stock bajo' ],
        'low_stock_threshold_desc'  => [ 'nl' => 'Producten met dit aantal of minder worden als "laag" gemarkeerd.', 'en' => 'Products with this quantity or less are marked as "low".', 'de' => 'Produkte mit dieser Menge oder weniger werden als „niedrig" markiert.', 'fr' => 'Les produits avec cette quantite ou moins sont marques comme "faible".', 'es' => 'Los productos con esta cantidad o menos se marcan como "bajo".' ],
        'alert_email'               => [ 'nl' => 'Alert e-mailadres',      'en' => 'Alert e-mail address',      'de' => 'Warn-E-Mail-Adresse',        'fr' => 'Adresse e-mail d\'alerte',  'es' => 'Direccion de e-mail de alerta' ],
        'alert_when'                => [ 'nl' => 'Alert bij',              'en' => 'Alert when',                'de' => 'Warnen bei',                 'fr' => 'Alerte lors de',            'es' => 'Alerta cuando' ],
        'alert_out_stock'           => [ 'nl' => 'Uitverkochte producten', 'en' => 'Out of stock products',     'de' => 'Ausverkaufte Produkte',      'fr' => 'Produits en rupture',       'es' => 'Productos sin stock' ],
        'alert_low_stock'           => [ 'nl' => 'Lage voorraad producten','en' => 'Low stock products',        'de' => 'Produkte mit niedrigem Bestand', 'fr' => 'Produits a stock faible','es' => 'Productos con stock bajo' ],
        'alerts_label'              => [ 'nl' => 'Alerts',                 'en' => 'Alerts',                    'de' => 'Warnungen',                  'fr' => 'Alertes',                   'es' => 'Alertas' ],
        'realtime_alert'            => [ 'nl' => 'Real-time alert bij voorraadwijziging', 'en' => 'Real-time alert on stock change', 'de' => 'Echtzeit-Warnung bei Bestandsänderung', 'fr' => 'Alerte en temps reel lors d\'un changement de stock', 'es' => 'Alerta en tiempo real al cambiar el stock' ],
        'daily_digest'              => [ 'nl' => 'Dagelijkse digest e-mail','en' => 'Daily digest e-mail',      'de' => 'Tägliche Zusammenfassungs-E-Mail', 'fr' => 'E-mail de digest quotidien', 'es' => 'E-mail de resumen diario' ],
        'send_test_alert'           => [ 'nl' => 'Test-alert versturen',   'en' => 'Send test alert',           'de' => 'Testwarnung senden',          'fr' => 'Envoyer alerte de test',    'es' => 'Enviar alerta de prueba' ],

        /* ---- Stock modal (new) ---- */
        'stock_update_title'        => [ 'nl' => 'Voorraad bijwerken',     'en' => 'Update stock',              'de' => 'Bestand aktualisieren',      'fr' => 'Mettre a jour le stock',    'es' => 'Actualizar stock' ],
        'new_stock_qty'             => [ 'nl' => 'Nieuwe voorraad',        'en' => 'New stock quantity',        'de' => 'Neue Bestandsmenge',         'fr' => 'Nouvelle quantite en stock', 'es' => 'Nueva cantidad de stock' ],
        'reason_placeholder2'       => [ 'nl' => 'Bijv. Herbestelling ontvangen', 'en' => 'E.g. Restock received', 'de' => 'Z.B. Nachbestellung erhalten', 'fr' => 'Ex. Reapprovisionnement recu', 'es' => 'Ej. Reposicion recibida' ],

        /* ---- Tab: Settings ---- */
        'settings_saved'            => [ 'nl' => 'Instellingen opgeslagen.', 'en' => 'Settings saved.',         'de' => 'Einstellungen gespeichert.', 'fr' => 'Parametres enregistres.',   'es' => 'Ajustes guardados.' ],
        'default_period'            => [ 'nl' => 'Standaard periode (dagen)', 'en' => 'Default period (days)',  'de' => 'Standardzeitraum (Tage)',    'fr' => 'Periode par defaut (jours)', 'es' => 'Periodo predeterminado (dias)' ],
        'chart_type'                => [ 'nl' => 'Grafiektype',             'en' => 'Chart type',               'de' => 'Diagrammtyp',               'fr' => 'Type de graphique',         'es' => 'Tipo de grafico' ],
        'chart_line'                => [ 'nl' => 'Lijngrafiek',             'en' => 'Line chart',               'de' => 'Liniendiagramm',             'fr' => 'Graphique en lignes',       'es' => 'Grafico de lineas' ],
        'chart_bar'                 => [ 'nl' => 'Staafgrafiek',            'en' => 'Bar chart',                'de' => 'Balkendiagramm',             'fr' => 'Graphique en barres',       'es' => 'Grafico de barras' ],
        'orders_per_page'           => [ 'nl' => 'Orders per pagina',       'en' => 'Orders per page',          'de' => 'Bestellungen pro Seite',     'fr' => 'Commandes par page',        'es' => 'Pedidos por pagina' ],
        'accent_color'              => [ 'nl' => 'Accentkleur',             'en' => 'Accent color',             'de' => 'Akzentfarbe',                'fr' => 'Couleur d\'accent',         'es' => 'Color de acento' ],
        'show_sections'             => [ 'nl' => 'Toon secties',            'en' => 'Show sections',            'de' => 'Abschnitte anzeigen',        'fr' => 'Afficher les sections',     'es' => 'Mostrar secciones' ],
        'toggle_avg_order'          => [ 'nl' => 'Gem. orderwaarde',        'en' => 'Avg. order value',         'de' => 'Ø Bestellwert',              'fr' => 'Valeur moy. commande',      'es' => 'Valor medio pedido' ],
        'toggle_top_products'       => [ 'nl' => 'Top producten',           'en' => 'Top products',             'de' => 'Top-Produkte',               'fr' => 'Meilleurs produits',        'es' => 'Mejores productos' ],
        'toggle_returning'          => [ 'nl' => 'Terugkerende klanten',    'en' => 'Returning customers',      'de' => 'Wiederkehrende Kunden',      'fr' => 'Clients recurrents',        'es' => 'Clientes recurrentes' ],
        'csv_columns'               => [ 'nl' => 'CSV export kolommen',     'en' => 'CSV export columns',       'de' => 'CSV-Exportspalten',          'fr' => 'Colonnes export CSV',       'es' => 'Columnas exportacion CSV' ],
        'enable_rest_api'           => [ 'nl' => 'REST API inschakelen (/wp-json/product-haven/v1/)', 'en' => 'Enable REST API (/wp-json/product-haven/v1/)', 'de' => 'REST API aktivieren (/wp-json/product-haven/v1/)', 'fr' => 'Activer l\'API REST (/wp-json/product-haven/v1/)', 'es' => 'Habilitar API REST (/wp-json/product-haven/v1/)' ],
        'save_btn'                  => [ 'nl' => 'Opslaan',                 'en' => 'Save',                     'de' => 'Speichern',                  'fr' => 'Enregistrer',               'es' => 'Guardar' ],

        /* ---- Customer card modal ---- */
        'customer_card'             => [ 'nl' => 'Klantenkaart',            'en' => 'Customer card',            'de' => 'Kundenkarte',               'fr' => 'Fiche client',              'es' => 'Ficha de cliente' ],

        /* ---- Order detail modal ---- */
        'edit_btn'                  => [ 'nl' => 'Bewerken',                'en' => 'Edit',                     'de' => 'Bearbeiten',                'fr' => 'Modifier',                  'es' => 'Editar' ],

        /* ---- Order edit modal ---- */
        'edit_order'                => [ 'nl' => 'Order bewerken',          'en' => 'Edit order',               'de' => 'Bestellung bearbeiten',     'fr' => 'Modifier la commande',      'es' => 'Editar pedido' ],
        'billing_address'           => [ 'nl' => 'Factuuradres',            'en' => 'Billing address',          'de' => 'Rechnungsadresse',          'fr' => 'Adresse de facturation',    'es' => 'Direccion de facturacion' ],
        'first_name'                => [ 'nl' => 'Voornaam',                'en' => 'First name',               'de' => 'Vorname',                   'fr' => 'Prenom',                    'es' => 'Nombre' ],
        'last_name'                 => [ 'nl' => 'Achternaam',              'en' => 'Last name',                'de' => 'Nachname',                  'fr' => 'Nom de famille',            'es' => 'Apellido' ],
        'company'                   => [ 'nl' => 'Bedrijf',                 'en' => 'Company',                  'de' => 'Unternehmen',               'fr' => 'Entreprise',                'es' => 'Empresa' ],
        'email'                     => [ 'nl' => 'E-mail',                  'en' => 'E-mail',                   'de' => 'E-Mail',                    'fr' => 'E-mail',                    'es' => 'E-mail' ],
        'phone'                     => [ 'nl' => 'Telefoon',                'en' => 'Phone',                    'de' => 'Telefon',                   'fr' => 'Telephone',                 'es' => 'Telefono' ],
        'address1'                  => [ 'nl' => 'Adresregel 1',            'en' => 'Address line 1',           'de' => 'Adresszeile 1',             'fr' => 'Ligne d\'adresse 1',        'es' => 'Linea de direccion 1' ],
        'address2'                  => [ 'nl' => 'Adresregel 2',            'en' => 'Address line 2',           'de' => 'Adresszeile 2',             'fr' => 'Ligne d\'adresse 2',        'es' => 'Linea de direccion 2' ],
        'city'                      => [ 'nl' => 'Stad',                    'en' => 'City',                     'de' => 'Stadt',                     'fr' => 'Ville',                     'es' => 'Ciudad' ],
        'postcode'                  => [ 'nl' => 'Postcode',                'en' => 'Postcode',                 'de' => 'Postleitzahl',              'fr' => 'Code postal',               'es' => 'Codigo postal' ],
        'country'                   => [ 'nl' => 'Land (ISO code)',          'en' => 'Country (ISO code)',       'de' => 'Land (ISO-Code)',           'fr' => 'Pays (code ISO)',           'es' => 'Pais (codigo ISO)' ],
        'internal_note'             => [ 'nl' => 'Interne notitie toevoegen', 'en' => 'Add internal note',      'de' => 'Interne Notiz hinzufügen', 'fr' => 'Ajouter une note interne',  'es' => 'Agregar nota interna' ],
        'note_placeholder'          => [ 'nl' => 'Optioneel: voeg een interne notitie toe aan deze order…', 'en' => 'Optional: add an internal note to this order…', 'de' => 'Optional: interne Notiz zu dieser Bestellung hinzufügen…', 'fr' => 'Optionnel : ajouter une note interne a cette commande…', 'es' => 'Opcional: agregar una nota interna a este pedido…' ],

        /* ---- JS: General ---- */
        'loading_error'             => [ 'nl' => 'Fout bij laden.',         'en' => 'Error loading.',            'de' => 'Fehler beim Laden.',         'fr' => 'Erreur de chargement.',     'es' => 'Error al cargar.' ],
        'no_orders_found'           => [ 'nl' => 'Geen orders gevonden.',   'en' => 'No orders found.',          'de' => 'Keine Bestellungen gefunden.', 'fr' => 'Aucune commande trouvee.', 'es' => 'No se encontraron pedidos.' ],
        'order_load_error'          => [ 'nl' => 'Order kon niet worden geladen.', 'en' => 'Order could not be loaded.', 'de' => 'Bestellung konnte nicht geladen werden.', 'fr' => 'La commande n\'a pas pu etre chargee.', 'es' => 'El pedido no pudo cargarse.' ],
        'connection_error'          => [ 'nl' => 'Verbindingsfout. Probeer opnieuw.', 'en' => 'Connection error. Please try again.', 'de' => 'Verbindungsfehler. Bitte erneut versuchen.', 'fr' => 'Erreur de connexion. Veuillez reessayer.', 'es' => 'Error de conexion. Por favor intente de nuevo.' ],
        'unknown_error'             => [ 'nl' => 'Onbekende fout.',         'en' => 'Unknown error.',            'de' => 'Unbekannter Fehler.',        'fr' => 'Erreur inconnue.',          'es' => 'Error desconocido.' ],
        'unknown_error_short'       => [ 'nl' => 'onbekend',                'en' => 'unknown',                   'de' => 'unbekannt',                  'fr' => 'inconnu',                   'es' => 'desconocido' ],
        'error_prefix'              => [ 'nl' => 'Fout: ',                  'en' => 'Error: ',                   'de' => 'Fehler: ',                   'fr' => 'Erreur : ',                 'es' => 'Error: ' ],
        'failed_prefix'             => [ 'nl' => 'Mislukt: ',               'en' => 'Failed: ',                  'de' => 'Fehlgeschlagen: ',           'fr' => 'Echec : ',                  'es' => 'Fallido: ' ],
        'saving'                    => [ 'nl' => 'Opslaan…',                'en' => 'Saving…',                   'de' => 'Speichern…',                 'fr' => 'Enregistrement…',           'es' => 'Guardando…' ],
        'deleting'                  => [ 'nl' => 'Verwijderen…',            'en' => 'Deleting…',                 'de' => 'Löschen…',                   'fr' => 'Suppression…',              'es' => 'Eliminando…' ],
        'processing'                => [ 'nl' => 'Bezig…',                  'en' => 'Processing…',               'de' => 'Verarbeiten…',               'fr' => 'Traitement…',               'es' => 'Procesando…' ],
        'sending'                   => [ 'nl' => 'Versturen…',              'en' => 'Sending…',                  'de' => 'Senden…',                    'fr' => 'Envoi…',                    'es' => 'Enviando…' ],
        'loading'                   => [ 'nl' => 'Laden…',                  'en' => 'Loading…',                  'de' => 'Laden…',                     'fr' => 'Chargement…',               'es' => 'Cargando…' ],

        /* ---- JS: low stock card ---- */
        'all_stock_ok'              => [ 'nl' => '✓ Alle producten hebben voldoende voorraad.', 'en' => '✓ All products have sufficient stock.', 'de' => '✓ Alle Produkte haben ausreichend Bestand.', 'fr' => '✓ Tous les produits ont un stock suffisant.', 'es' => '✓ Todos los productos tienen stock suficiente.' ],
        'edit_stock_title'          => [ 'nl' => 'Voorraad aanpassen',      'en' => 'Edit stock',               'de' => 'Bestand bearbeiten',         'fr' => 'Modifier le stock',         'es' => 'Editar stock' ],
        'out_of_stock_short'        => [ 'nl' => 'Op!',                     'en' => 'Out!',                      'de' => 'Leer!',                      'fr' => 'Epuise!',                   'es' => 'Agotado!' ],
        'remaining'                 => [ 'nl' => 'resterend',               'en' => 'remaining',                 'de' => 'verbleibend',                'fr' => 'restant',                   'es' => 'restante' ],
        'no_data'                   => [ 'nl' => 'Geen data beschikbaar.',   'en' => 'No data available.',        'de' => 'Keine Daten verfügbar.',     'fr' => 'Aucune donnee disponible.', 'es' => 'No hay datos disponibles.' ],
        'refunded_prefix'           => [ 'nl' => 'Terugbetaald: ',          'en' => 'Refunded: ',                'de' => 'Erstattet: ',                'fr' => 'Rembourse : ',              'es' => 'Reembolsado: ' ],

        /* ---- JS: Graph ---- */
        'chart_orders_suffix'       => [ 'nl' => ' orders',                 'en' => ' orders',                   'de' => ' Bestellungen',              'fr' => ' commandes',                'es' => ' pedidos' ],

        /* ---- JS: Timeline ---- */
        'time_just_now'             => [ 'nl' => 'Zojuist',                 'en' => 'Just now',                  'de' => 'Gerade eben',                'fr' => 'A l\'instant',              'es' => 'Ahora mismo' ],
        'time_min_ago'              => [ 'nl' => 'min geleden',             'en' => 'min ago',                   'de' => 'Min. her',                   'fr' => 'min',                       'es' => 'min' ],
        'time_hour_ago'             => [ 'nl' => 'uur geleden',             'en' => 'hour ago',                  'de' => 'Stunde her',                 'fr' => 'heure',                     'es' => 'hora' ],
        'time_hours_ago'            => [ 'nl' => 'uur geleden',             'en' => 'hours ago',                 'de' => 'Stunden her',                'fr' => 'heures',                    'es' => 'horas' ],
        'time_yesterday'            => [ 'nl' => 'Gisteren',                'en' => 'Yesterday',                 'de' => 'Gestern',                    'fr' => 'Hier',                      'es' => 'Ayer' ],
        'time_days_ago'             => [ 'nl' => 'dagen geleden',           'en' => 'days ago',                  'de' => 'Tage her',                   'fr' => 'jours',                     'es' => 'dias' ],
        'time_weeks_ago'            => [ 'nl' => 'weken geleden',           'en' => 'weeks ago',                 'de' => 'Wochen her',                 'fr' => 'semaines',                  'es' => 'semanas' ],
        'time_ago'                  => [ 'nl' => 'geleden',                 'en' => 'ago',                       'de' => 'her',                        'fr' => '',                          'es' => '' ],
        'note_just_now_you'         => [ 'nl' => 'Zojuist · jij',          'en' => 'Just now · you',            'de' => 'Gerade eben · du',           'fr' => 'A l\'instant · vous',       'es' => 'Ahora mismo · tu' ],
        'label_guest'               => [ 'nl' => 'Gast',                    'en' => 'Guest',                     'de' => 'Gast',                       'fr' => 'Invite',                    'es' => 'Invitado' ],
        'label_unknown'             => [ 'nl' => 'Onbekend',                'en' => 'Unknown',                   'de' => 'Unbekannt',                  'fr' => 'Inconnu',                   'es' => 'Desconocido' ],

        /* ---- JS: order modal ---- */
        'modal_customer'            => [ 'nl' => 'Klant',                   'en' => 'Customer',                 'de' => 'Kunde',                     'fr' => 'Client',                    'es' => 'Cliente' ],
        'modal_name'                => [ 'nl' => 'Naam',                    'en' => 'Name',                     'de' => 'Name',                      'fr' => 'Nom',                       'es' => 'Nombre' ],
        'modal_email_label'         => [ 'nl' => 'E-mail',                  'en' => 'E-mail',                   'de' => 'E-Mail',                    'fr' => 'E-mail',                    'es' => 'E-mail' ],
        'modal_phone_label'         => [ 'nl' => 'Telefoon',                'en' => 'Phone',                    'de' => 'Telefon',                   'fr' => 'Telephone',                 'es' => 'Telefono' ],
        'modal_address'             => [ 'nl' => 'Adres',                   'en' => 'Address',                  'de' => 'Adresse',                   'fr' => 'Adresse',                   'es' => 'Direccion' ],
        'modal_payment'             => [ 'nl' => 'Betaling',                'en' => 'Payment',                  'de' => 'Zahlung',                   'fr' => 'Paiement',                  'es' => 'Pago' ],
        'modal_date'                => [ 'nl' => 'Datum',                   'en' => 'Date',                     'de' => 'Datum',                     'fr' => 'Date',                      'es' => 'Fecha' ],
        'modal_total'               => [ 'nl' => 'Totaal',                  'en' => 'Total',                    'de' => 'Gesamt',                    'fr' => 'Total',                     'es' => 'Total' ],
        'modal_products'            => [ 'nl' => 'Producten',               'en' => 'Products',                 'de' => 'Produkte',                  'fr' => 'Produits',                  'es' => 'Productos' ],
        'modal_order_notes'         => [ 'nl' => 'Ordernotities',           'en' => 'Order notes',              'de' => 'Bestellnotizen',            'fr' => 'Notes de commande',         'es' => 'Notas del pedido' ],
        'modal_no_notes'            => [ 'nl' => 'Nog geen notities.',      'en' => 'No notes yet.',            'de' => 'Noch keine Notizen.',       'fr' => 'Aucune note pour l\'instant.', 'es' => 'Aun no hay notas.' ],
        'modal_note_placeholder'    => [ 'nl' => 'Notitie toevoegen…',      'en' => 'Add note…',               'de' => 'Notiz hinzufügen…',         'fr' => 'Ajouter une note…',         'es' => 'Agregar nota…' ],
        'modal_note_add'            => [ 'nl' => 'Toevoegen',               'en' => 'Add',                      'de' => 'Hinzufügen',                'fr' => 'Ajouter',                   'es' => 'Agregar' ],
        'modal_change_status'       => [ 'nl' => 'Status wijzigen',         'en' => 'Change status',            'de' => 'Status ändern',             'fr' => 'Changer le statut',         'es' => 'Cambiar estado' ],
        'modal_complete'            => [ 'nl' => '✓ Voltooien',             'en' => '✓ Complete',               'de' => '✓ Abschließen',             'fr' => '✓ Terminer',                'es' => '✓ Completar' ],
        'modal_processing'          => [ 'nl' => '↻ Verwerken',             'en' => '↻ Processing',             'de' => '↻ In Bearbeitung',          'fr' => '↻ En cours',               'es' => '↻ En proceso' ],
        'modal_on_hold'             => [ 'nl' => '⏸ In de wacht',           'en' => '⏸ On hold',               'de' => '⏸ Wartend',                 'fr' => '⏸ En attente',             'es' => '⏸ En espera' ],
        'modal_cancel'              => [ 'nl' => '✕ Annuleren',             'en' => '✕ Cancel',                 'de' => '✕ Stornieren',              'fr' => '✕ Annuler',                 'es' => '✕ Cancelar' ],
        'modal_failed'              => [ 'nl' => '⚠ Mislukt',               'en' => '⚠ Failed',                 'de' => '⚠ Fehlgeschlagen',          'fr' => '⚠ Echoue',                 'es' => '⚠ Fallido' ],
        'modal_refund'              => [ 'nl' => '↩ Retour',                'en' => '↩ Refund',                 'de' => '↩ Erstatten',               'fr' => '↩ Rembourser',              'es' => '↩ Reembolsar' ],
        'modal_revert_refund'       => [ 'nl' => '↩ Retour terugdraaien',   'en' => '↩ Revert refund',          'de' => '↩ Erstattung rückgängig',   'fr' => '↩ Annuler le remboursement','es' => '↩ Revertir reembolso' ],
        'modal_danger_zone'         => [ 'nl' => 'Gevaarlijke acties',      'en' => 'Danger zone',              'de' => 'Gefahrenbereich',           'fr' => 'Zone dangereuse',           'es' => 'Zona peligrosa' ],
        'modal_delete_order'        => [ 'nl' => 'Bestelling permanent verwijderen', 'en' => 'Permanently delete order', 'de' => 'Bestellung dauerhaft löschen', 'fr' => 'Supprimer definitivement la commande', 'es' => 'Eliminar pedido permanentemente' ],
        'modal_open_customer'       => [ 'nl' => '↗ Klantenkaart openen',   'en' => '↗ Open customer card',    'de' => '↗ Kundenkarte öffnen',      'fr' => '↗ Ouvrir la fiche client',  'es' => '↗ Abrir ficha de cliente' ],
        'modal_irreversible'        => [ 'nl' => '⚠ Dit kan niet ongedaan worden gemaakt.', 'en' => '⚠ This cannot be undone.', 'de' => '⚠ Dies kann nicht rückgängig gemacht werden.', 'fr' => '⚠ Cette action est irreversible.', 'es' => '⚠ Esta accion no se puede deshacer.' ],
        'modal_delete_confirm'      => [ 'nl' => 'Weet je zeker dat je order #%s permanent wilt verwijderen?', 'en' => 'Are you sure you want to permanently delete order #%s?', 'de' => 'Möchten Sie Bestellung #%s wirklich dauerhaft löschen?', 'fr' => 'Voulez-vous vraiment supprimer definitivement la commande #%s ?', 'es' => 'Esta seguro de que desea eliminar permanentemente el pedido #%s?' ],

        /* ---- JS: order bewerken modal ---- */
        'order_edit_save'           => [ 'nl' => 'Opslaan',                 'en' => 'Save',                     'de' => 'Speichern',                 'fr' => 'Enregistrer',               'es' => 'Guardar' ],
        'order_edit_saving'         => [ 'nl' => 'Opslaan…',                'en' => 'Saving…',                  'de' => 'Speichern…',                'fr' => 'Enregistrement…',           'es' => 'Guardando…' ],

        /* ---- JS: retour modal ---- */
        'refund_modal_title'        => [ 'nl' => 'Retour verwerken',        'en' => 'Process refund',           'de' => 'Rückerstattung verarbeiten', 'fr' => 'Traiter le remboursement',  'es' => 'Procesar reembolso' ],
        'revert_modal_title'        => [ 'nl' => 'Retour terugdraaien',     'en' => 'Revert refund',            'de' => 'Erstattung rückgängig machen', 'fr' => 'Annuler le remboursement', 'es' => 'Revertir reembolso' ],
        'refund_modal_desc'         => [ 'nl' => 'Wil je order #%s als retour markeren? Kies hoe je de terugbetaling wilt verwerken.', 'en' => 'Do you want to mark order #%s as refunded? Choose how to process the refund.', 'de' => 'Möchten Sie Bestellung #%s als erstattet markieren? Wählen Sie, wie die Erstattung verarbeitet werden soll.', 'fr' => 'Voulez-vous marquer la commande #%s comme remboursee ? Choisissez comment traiter le remboursement.', 'es' => 'Desea marcar el pedido #%s como reembolsado? Elija como procesar el reembolso.' ],
        'revert_modal_desc'         => [ 'nl' => 'Order #%s staat op "Retour". Kies de nieuwe status en of bestaande terugbetalingsrecords verwijderd moeten worden.', 'en' => 'Order #%s is set to "Refunded". Choose the new status and whether to delete existing refund records.', 'de' => 'Bestellung #%s ist auf „Erstattet". Wählen Sie den neuen Status und ob bestehende Erstattungsdatensätze gelöscht werden sollen.', 'fr' => 'La commande #%s est sur "Rembourse". Choisissez le nouveau statut et si les remboursements existants doivent etre supprimes.', 'es' => 'El pedido #%s esta en "Reembolsado". Elija el nuevo estado y si se deben eliminar los registros de reembolso existentes.' ],
        'refund_confirm_btn'        => [ 'nl' => 'Bevestigen',              'en' => 'Confirm',                  'de' => 'Bestätigen',                'fr' => 'Confirmer',                 'es' => 'Confirmar' ],

        /* ---- JS: order verwijderen bevestiging ---- */
        'delete_confirm_ok'         => [ 'nl' => 'Permanent verwijderen',   'en' => 'Delete permanently',       'de' => 'Dauerhaft löschen',         'fr' => 'Supprimer definitivement',  'es' => 'Eliminar permanentemente' ],
        'delete_confirm_ok_busy'    => [ 'nl' => 'Verwijderen…',            'en' => 'Deleting…',                'de' => 'Löschen…',                  'fr' => 'Suppression…',              'es' => 'Eliminando…' ],
        'delete_error_prefix'       => [ 'nl' => 'Mislukt: ',               'en' => 'Failed: ',                 'de' => 'Fehlgeschlagen: ',          'fr' => 'Echec : ',                  'es' => 'Fallido: ' ],
        'delete_unknown_error'      => [ 'nl' => 'onbekende fout',          'en' => 'unknown error',            'de' => 'unbekannter Fehler',        'fr' => 'erreur inconnue',           'es' => 'error desconocido' ],

        /* ---- JS: klantenkaart ---- */
        'customer_not_found'        => [ 'nl' => 'Klant niet gevonden.',    'en' => 'Customer not found.',      'de' => 'Kunde nicht gefunden.',     'fr' => 'Client introuvable.',       'es' => 'Cliente no encontrado.' ],
        'customer_since'            => [ 'nl' => 'Lid sinds',               'en' => 'Member since',             'de' => 'Mitglied seit',             'fr' => 'Membre depuis',             'es' => 'Miembro desde' ],
        'customer_total_spent'      => [ 'nl' => 'Totaal besteed',          'en' => 'Total spent',              'de' => 'Gesamtausgaben',            'fr' => 'Total depense',             'es' => 'Total gastado' ],
        'customer_avg_order'        => [ 'nl' => 'Gem. order',              'en' => 'Avg. order',               'de' => 'Ø Bestellung',              'fr' => 'Commande moy.',             'es' => 'Pedido prom.' ],
        'customer_orders_history'   => [ 'nl' => 'Ordergeschiedenis',       'en' => 'Order history',            'de' => 'Bestellhistorie',           'fr' => 'Historique des commandes',  'es' => 'Historial de pedidos' ],
        'customer_orders_none'      => [ 'nl' => 'Geen orders gevonden.',   'en' => 'No orders found.',         'de' => 'Keine Bestellungen gefunden.', 'fr' => 'Aucune commande trouvee.', 'es' => 'No se encontraron pedidos.' ],
        'customer_col_order'        => [ 'nl' => 'Order',                   'en' => 'Order',                    'de' => 'Bestellung',                'fr' => 'Commande',                  'es' => 'Pedido' ],
        'customer_col_date'         => [ 'nl' => 'Datum',                   'en' => 'Date',                     'de' => 'Datum',                     'fr' => 'Date',                      'es' => 'Fecha' ],
        'customer_col_status'       => [ 'nl' => 'Status',                  'en' => 'Status',                   'de' => 'Status',                    'fr' => 'Statut',                    'es' => 'Estado' ],
        'customer_col_amount'       => [ 'nl' => 'Bedrag',                  'en' => 'Amount',                   'de' => 'Betrag',                    'fr' => 'Montant',                   'es' => 'Importe' ],

        /* ---- JS: omzet rapport ---- */
        'rev_gross'                 => [ 'nl' => 'Bruto verkoop',           'en' => 'Gross sales',              'de' => 'Bruttoumsatz',              'fr' => 'Ventes brutes',             'es' => 'Ventas brutas' ],
        'rev_refunds'               => [ 'nl' => 'Retourneringen',          'en' => 'Refunds',                  'de' => 'Erstattungen',              'fr' => 'Remboursements',            'es' => 'Reembolsos' ],
        'rev_coupons'               => [ 'nl' => 'Waardebonnen',            'en' => 'Coupons',                  'de' => 'Gutscheine',                'fr' => 'Coupons',                   'es' => 'Cupones' ],
        'rev_net'                   => [ 'nl' => 'Netto omzet',             'en' => 'Net revenue',              'de' => 'Nettoumsatz',               'fr' => 'Chiffre d\'affaires net',   'es' => 'Ingresos netos' ],
        'rev_taxes'                 => [ 'nl' => 'Belastingen',             'en' => 'Taxes',                    'de' => 'Steuern',                   'fr' => 'Taxes',                     'es' => 'Impuestos' ],
        'rev_shipping'              => [ 'nl' => 'Verzendkosten',           'en' => 'Shipping',                 'de' => 'Versandkosten',             'fr' => 'Frais de port',             'es' => 'Envio' ],
        'rev_total'                 => [ 'nl' => 'Totale verkopen',         'en' => 'Total sales',              'de' => 'Gesamtumsatz',              'fr' => 'Ventes totales',            'es' => 'Ventas totales' ],

        /* ---- JS: categorieën rapport ---- */
        'cat_col_category'          => [ 'nl' => 'Categorie',               'en' => 'Category',                 'de' => 'Kategorie',                 'fr' => 'Categorie',                 'es' => 'Categoria' ],
        'cat_col_items'             => [ 'nl' => 'Items verkocht',          'en' => 'Items sold',               'de' => 'Verkaufte Artikel',         'fr' => 'Articles vendus',           'es' => 'Articulos vendidos' ],
        'cat_col_revenue'           => [ 'nl' => 'Omzet',                   'en' => 'Revenue',                  'de' => 'Umsatz',                    'fr' => 'Chiffre d\'affaires',       'es' => 'Ingresos' ],
        'cat_no_data'               => [ 'nl' => 'Geen categoriedata beschikbaar.', 'en' => 'No category data available.', 'de' => 'Keine Kategoriedaten verfügbar.', 'fr' => 'Aucune donnee de categorie disponible.', 'es' => 'No hay datos de categoria disponibles.' ],

        /* ---- JS: coupons rapport ---- */
        'coupon_col_code'           => [ 'nl' => 'Coupon code',             'en' => 'Coupon code',              'de' => 'Gutscheincode',             'fr' => 'Code coupon',               'es' => 'Codigo de cupon' ],
        'coupon_col_used'           => [ 'nl' => 'Gebruikt (orders)',       'en' => 'Used (orders)',            'de' => 'Verwendet (Bestellungen)',  'fr' => 'Utilise (commandes)',       'es' => 'Usado (pedidos)' ],
        'coupon_col_discount'       => [ 'nl' => 'Totale korting',          'en' => 'Total discount',           'de' => 'Gesamtrabatt',              'fr' => 'Remise totale',             'es' => 'Descuento total' ],
        'coupon_no_data'            => [ 'nl' => 'Geen coupons gebruikt in deze periode.', 'en' => 'No coupons used in this period.', 'de' => 'Keine Gutscheine in diesem Zeitraum verwendet.', 'fr' => 'Aucun coupon utilise dans cette periode.', 'es' => 'No se usaron cupones en este periodo.' ],

        /* ---- JS: dag rapport ---- */
        'daily_date_label'          => [ 'nl' => 'Datum:',                  'en' => 'Date:',                    'de' => 'Datum:',                    'fr' => 'Date :',                    'es' => 'Fecha:' ],
        'daily_total_label'         => [ 'nl' => 'Totale omzet:',           'en' => 'Total revenue:',           'de' => 'Gesamtumsatz:',             'fr' => 'CA total :',                'es' => 'Ingresos totales:' ],
        'daily_products_label'      => [ 'nl' => 'Producten:',              'en' => 'Products:',                'de' => 'Produkte:',                 'fr' => 'Produits :',                'es' => 'Productos:' ],
        'daily_col_product'         => [ 'nl' => 'Product',                 'en' => 'Product',                  'de' => 'Produkt',                   'fr' => 'Produit',                   'es' => 'Producto' ],
        'daily_col_qty'             => [ 'nl' => 'Stuks verkocht',          'en' => 'Units sold',               'de' => 'Verkaufte Einheiten',       'fr' => 'Unites vendues',            'es' => 'Unidades vendidas' ],
        'daily_col_revenue'         => [ 'nl' => 'Opbrengst',               'en' => 'Revenue',                  'de' => 'Umsatz',                    'fr' => 'Chiffre d\'affaires',       'es' => 'Ingresos' ],
        'daily_no_sales'            => [ 'nl' => 'Geen verkopen gevonden op %s.', 'en' => 'No sales found on %s.', 'de' => 'Keine Verkäufe am %s gefunden.', 'fr' => 'Aucune vente trouvee le %s.', 'es' => 'No se encontraron ventas el %s.' ],

        /* ---- JS: klanten rapport ---- */
        'cust_col_customer'         => [ 'nl' => 'Klant',                   'en' => 'Customer',                 'de' => 'Kunde',                     'fr' => 'Client',                    'es' => 'Cliente' ],
        'cust_col_orders'           => [ 'nl' => 'Orders',                  'en' => 'Orders',                   'de' => 'Bestellungen',              'fr' => 'Commandes',                 'es' => 'Pedidos' ],
        'cust_col_avg'              => [ 'nl' => 'Gem. order',              'en' => 'Avg. order',               'de' => 'Ø Bestellung',              'fr' => 'Commande moy.',             'es' => 'Pedido prom.' ],
        'cust_col_last'             => [ 'nl' => 'Laatste order',           'en' => 'Last order',               'de' => 'Letzte Bestellung',         'fr' => 'Derniere commande',         'es' => 'Ultimo pedido' ],
        'cust_col_total'            => [ 'nl' => 'Totaal besteed',          'en' => 'Total spent',              'de' => 'Gesamtausgaben',            'fr' => 'Total depense',             'es' => 'Total gastado' ],
        'cust_no_data'              => [ 'nl' => 'Geen klantdata beschikbaar in deze periode.', 'en' => 'No customer data available for this period.', 'de' => 'Keine Kundendaten für diesen Zeitraum verfügbar.', 'fr' => 'Aucune donnee client disponible pour cette periode.', 'es' => 'No hay datos de clientes disponibles para este periodo.' ],

        /* ---- JS: voorraad tabel ---- */
        'stock_edit_btn'            => [ 'nl' => 'Bewerken',                'en' => 'Edit',                     'de' => 'Bearbeiten',                'fr' => 'Modifier',                  'es' => 'Editar' ],
        'stock_out_short'           => [ 'nl' => 'Uitverkocht',             'en' => 'Out of stock',             'de' => 'Ausverkauft',               'fr' => 'Rupture',                   'es' => 'Sin stock' ],
        'stock_low_short'           => [ 'nl' => 'Laag',                    'en' => 'Low',                      'de' => 'Niedrig',                   'fr' => 'Faible',                    'es' => 'Bajo' ],
        'stock_ok_short'            => [ 'nl' => 'Op voorraad',             'en' => 'In stock',                 'de' => 'Auf Lager',                 'fr' => 'En stock',                  'es' => 'En stock' ],
        'stock_modal_title_prefix'  => [ 'nl' => 'Voorraad: ',              'en' => 'Stock: ',                  'de' => 'Bestand: ',                 'fr' => 'Stock : ',                  'es' => 'Stock: ' ],
        'stock_modal_title_default' => [ 'nl' => 'Voorraad bijwerken',      'en' => 'Update stock',             'de' => 'Bestand aktualisieren',     'fr' => 'Mettre a jour le stock',    'es' => 'Actualizar stock' ],
        'stock_updated'             => [ 'nl' => 'Voorraad bijgewerkt.',    'en' => 'Stock updated.',           'de' => 'Bestand aktualisiert.',     'fr' => 'Stock mis a jour.',         'es' => 'Stock actualizado.' ],
        'stock_saved'               => [ 'nl' => 'Instellingen opgeslagen.','en' => 'Settings saved.',          'de' => 'Einstellungen gespeichert.', 'fr' => 'Parametres enregistres.',  'es' => 'Ajustes guardados.' ],
        'stock_test_sent'           => [ 'nl' => 'Test-alert verstuurd.',   'en' => 'Test alert sent.',         'de' => 'Testwarnung gesendet.',     'fr' => 'Alerte de test envoyee.',   'es' => 'Alerta de prueba enviada.' ],
        'stock_n_selected'          => [ 'nl' => ' geselecteerd',           'en' => ' selected',                'de' => ' ausgewählt',               'fr' => ' selectionne',              'es' => ' seleccionado' ],
        'stock_bulk_prompt'         => [ 'nl' => 'Nieuwe voorraad voor %s producten:', 'en' => 'New stock for %s products:', 'de' => 'Neuer Bestand für %s Produkte:', 'fr' => 'Nouveau stock pour %s produits :', 'es' => 'Nuevo stock para %s productos:' ],
        'stock_bulk_reason'         => [ 'nl' => 'Bulkupdate',              'en' => 'Bulk update',              'de' => 'Massenaktualisierung',      'fr' => 'Mise a jour en lot',        'es' => 'Actualizacion masiva' ],
        'stock_total_label'         => [ 'nl' => ' producten',              'en' => ' products',                'de' => ' Produkte',                 'fr' => ' produits',                 'es' => ' productos' ],
        'stock_save_settings_busy'  => [ 'nl' => 'Test-alert versturen',    'en' => 'Send test alert',          'de' => 'Testwarnung senden',        'fr' => 'Envoyer alerte de test',    'es' => 'Enviar alerta de prueba' ],

        /* ---- JS: quick products ---- */
        'qp_no_products'            => [ 'nl' => 'Geen producten gevonden.','en' => 'No products found.',       'de' => 'Keine Produkte gefunden.',  'fr' => 'Aucun produit trouve.',     'es' => 'No se encontraron productos.' ],
        'qp_load_error'             => [ 'nl' => 'Fout bij laden.',         'en' => 'Error loading.',           'de' => 'Fehler beim Laden.',        'fr' => 'Erreur de chargement.',     'es' => 'Error al cargar.' ],
        'qp_published'              => [ 'nl' => 'Gepubliceerd',            'en' => 'Published',                'de' => 'Veröffentlicht',            'fr' => 'Publie',                    'es' => 'Publicado' ],
        'qp_draft'                  => [ 'nl' => 'Concept',                 'en' => 'Draft',                    'de' => 'Entwurf',                   'fr' => 'Brouillon',                 'es' => 'Borrador' ],
        'qp_private'                => [ 'nl' => 'Privé',                   'en' => 'Private',                  'de' => 'Privat',                    'fr' => 'Prive',                     'es' => 'Privado' ],
        'qp_pending'                => [ 'nl' => 'Wacht',                   'en' => 'Pending',                  'de' => 'Ausstehend',                'fr' => 'En attente',                'es' => 'Pendiente' ],
        'qp_col_product'            => [ 'nl' => 'Product',                 'en' => 'Product',                  'de' => 'Produkt',                   'fr' => 'Produit',                   'es' => 'Producto' ],
        'qp_col_status'             => [ 'nl' => 'Status',                  'en' => 'Status',                   'de' => 'Status',                    'fr' => 'Statut',                    'es' => 'Estado' ],
        'qp_col_price'              => [ 'nl' => 'Prijs',                   'en' => 'Price',                    'de' => 'Preis',                     'fr' => 'Prix',                      'es' => 'Precio' ],
        'qp_col_stock'              => [ 'nl' => 'Voorraad',                'en' => 'Stock',                    'de' => 'Bestand',                   'fr' => 'Stock',                     'es' => 'Stock' ],
        'qp_instock'                => [ 'nl' => 'Op voorraad',             'en' => 'In stock',                 'de' => 'Auf Lager',                 'fr' => 'En stock',                  'es' => 'En stock' ],
        'qp_outofstock'             => [ 'nl' => 'Niet op voorraad',        'en' => 'Out of stock',             'de' => 'Nicht auf Lager',           'fr' => 'Rupture de stock',          'es' => 'Sin stock' ],
        'qp_onbackorder'            => [ 'nl' => 'Nabestelling',            'en' => 'On backorder',             'de' => 'Nachbestellung',            'fr' => 'Sur commande',              'es' => 'Por encargo' ],
        'qp_edit_title'             => [ 'nl' => 'Bewerken',                'en' => 'Edit',                     'de' => 'Bearbeiten',                'fr' => 'Modifier',                  'es' => 'Editar' ],
        'qp_duplicate_title'        => [ 'nl' => 'Dupliceren',              'en' => 'Duplicate',                'de' => 'Duplizieren',               'fr' => 'Dupliquer',                 'es' => 'Duplicar' ],
        'qp_wc_edit_title'          => [ 'nl' => 'In WC bewerken',          'en' => 'Edit in WC',               'de' => 'In WC bearbeiten',          'fr' => 'Modifier dans WC',          'es' => 'Editar en WC' ],
        'qp_delete_title'           => [ 'nl' => 'Verwijderen',             'en' => 'Delete',                   'de' => 'Löschen',                   'fr' => 'Supprimer',                 'es' => 'Eliminar' ],
        'qp_sale_prefix'            => [ 'nl' => 'Aanbieding: ',            'en' => 'Sale: ',                   'de' => 'Angebot: ',                 'fr' => 'Promo : ',                  'es' => 'Oferta: ' ],
        'qp_delete_confirm'         => [ 'nl' => 'Product verwijderen?',    'en' => 'Delete product?',          'de' => 'Produkt löschen?',          'fr' => 'Supprimer le produit ?',    'es' => 'Eliminar el producto?' ],
        'qp_deleted'                => [ 'nl' => 'Product verwijderd.',     'en' => 'Product deleted.',         'de' => 'Produkt gelöscht.',         'fr' => 'Produit supprime.',         'es' => 'Producto eliminado.' ],
        'qp_duplicated_prefix'      => [ 'nl' => 'Gedupliceerd: ',          'en' => 'Duplicated: ',             'de' => 'Dupliziert: ',              'fr' => 'Duplique : ',               'es' => 'Duplicado: ' ],
        'qp_updated'                => [ 'nl' => 'Bijgewerkt.',             'en' => 'Updated.',                 'de' => 'Aktualisiert.',             'fr' => 'Mis a jour.',               'es' => 'Actualizado.' ],
        'qp_update_error'           => [ 'nl' => 'Fout bij opslaan.',       'en' => 'Error saving.',            'de' => 'Fehler beim Speichern.',    'fr' => 'Erreur lors de l\'enregistrement.', 'es' => 'Error al guardar.' ],
        'qp_name_required'          => [ 'nl' => 'Productnaam is verplicht.','en' => 'Product name is required.','de' => 'Produktname ist erforderlich.', 'fr' => 'Le nom du produit est obligatoire.', 'es' => 'El nombre del producto es obligatorio.' ],
        'qp_saving'                 => [ 'nl' => 'Opslaan…',                'en' => 'Saving…',                  'de' => 'Speichern…',                'fr' => 'Enregistrement…',           'es' => 'Guardando…' ],
        'qp_saved'                  => [ 'nl' => 'Opgeslagen!',             'en' => 'Saved!',                   'de' => 'Gespeichert!',              'fr' => 'Enregistre !',              'es' => 'Guardado!' ],
        'qp_save_error'             => [ 'nl' => 'Fout bij opslaan.',       'en' => 'Error saving.',            'de' => 'Fehler beim Speichern.',    'fr' => 'Erreur lors de l\'enregistrement.', 'es' => 'Error al guardar.' ],
        'qp_new_product_label'      => [ 'nl' => 'Nieuw product',           'en' => 'New product',              'de' => 'Neues Produkt',             'fr' => 'Nouveau produit',           'es' => 'Nuevo producto' ],
        'qp_loading_label'          => [ 'nl' => 'Laden…',                  'en' => 'Loading…',                 'de' => 'Laden…',                    'fr' => 'Chargement…',               'es' => 'Cargando…' ],
        'qp_not_found'              => [ 'nl' => 'Product niet gevonden.',  'en' => 'Product not found.',       'de' => 'Produkt nicht gefunden.',   'fr' => 'Produit introuvable.',      'es' => 'Producto no encontrado.' ],
        'qp_media_unavailable'      => [ 'nl' => 'WordPress Media is niet beschikbaar.', 'en' => 'WordPress Media is not available.', 'de' => 'WordPress Media ist nicht verfügbar.', 'fr' => 'WordPress Media n\'est pas disponible.', 'es' => 'WordPress Media no esta disponible.' ],
        'qp_media_title'            => [ 'nl' => 'Stel productafbeelding in', 'en' => 'Set product image',      'de' => 'Produktbild festlegen',     'fr' => 'Definir l\'image du produit', 'es' => 'Establecer imagen del producto' ],
        'qp_media_btn'              => [ 'nl' => 'Stel in als afbeelding',  'en' => 'Set as image',             'de' => 'Als Bild festlegen',        'fr' => 'Definir comme image',       'es' => 'Establecer como imagen' ],
        'qp_gallery_title'          => [ 'nl' => 'Galerij afbeeldingen selecteren', 'en' => 'Select gallery images', 'de' => 'Galeriebilder auswählen', 'fr' => 'Selectionner les images de la galerie', 'es' => 'Seleccionar imagenes de la galeria' ],
        'qp_gallery_btn'            => [ 'nl' => 'Toevoegen aan galerij',   'en' => 'Add to gallery',           'de' => 'Zur Galerie hinzufügen',    'fr' => 'Ajouter a la galerie',      'es' => 'Agregar a la galeria' ],

        /* ---- JS: stockEdit (dashboard low stock modal) ---- */
        'stock_edit_default_reason' => [ 'nl' => 'Bijgewerkt via Product Haven', 'en' => 'Updated via Product Haven',    'de' => 'Aktualisiert über Product Haven', 'fr' => 'Mis a jour via Product Haven', 'es' => 'Actualizado via Product Haven' ],
        'stock_edit_invalid_qty'    => [ 'nl' => 'Vul een geldig getal in (0 of hoger).', 'en' => 'Please enter a valid number (0 or higher).', 'de' => 'Bitte eine gültige Zahl eingeben (0 oder höher).', 'fr' => 'Veuillez entrer un nombre valide (0 ou plus).', 'es' => 'Por favor ingrese un numero valido (0 o mayor).' ],

        /* ---- Sequential Orders tab (PHP) ---- */
        'so_settings_saved'         => [ 'nl' => 'Instellingen opgeslagen.', 'en' => 'Settings saved.',                 'de' => 'Einstellungen gespeichert.',       'fr' => 'Parametres enregistres.',          'es' => 'Ajustes guardados.' ],
        'so_counter_reset'          => [ 'nl' => 'Teller gereset naar het startnummer.', 'en' => 'Counter reset to the start number.', 'de' => 'Zähler auf die Startnummer zurückgesetzt.', 'fr' => 'Compteur reinitialise au numero de depart.', 'es' => 'Contador restablecido al numero inicial.' ],
        'so_format_title'           => [ 'nl' => 'Opmaak bestelnummer',     'en' => 'Order number format',              'de' => 'Bestellnummer-Format',             'fr' => 'Format du numero de commande',     'es' => 'Formato de numero de pedido' ],
        'so_prefix'                 => [ 'nl' => 'Prefix',                  'en' => 'Prefix',                           'de' => 'Präfix',                           'fr' => 'Prefixe',                          'es' => 'Prefijo' ],
        'so_prefix_placeholder'     => [ 'nl' => 'bijv. ORD- of #',        'en' => 'e.g. ORD- or #',                  'de' => 'z.B. ORD- oder #',                 'fr' => 'ex. ORD- ou #',                    'es' => 'ej. ORD- o #' ],
        'so_prefix_desc'            => [ 'nl' => 'Tekst vóór het nummer. Leeg laten voor geen prefix.', 'en' => 'Text before the number. Leave empty for no prefix.', 'de' => 'Text vor der Nummer. Leer lassen für kein Präfix.', 'fr' => 'Texte avant le numero. Laisser vide pour aucun prefixe.', 'es' => 'Texto antes del numero. Dejar vacio para sin prefijo.' ],
        'so_suffix'                 => [ 'nl' => 'Suffix',                  'en' => 'Suffix',                           'de' => 'Suffix',                           'fr' => 'Suffixe',                          'es' => 'Sufijo' ],
        'so_suffix_placeholder'     => [ 'nl' => 'bijv. -2026',             'en' => 'e.g. -2026',                      'de' => 'z.B. -2026',                       'fr' => 'ex. -2026',                        'es' => 'ej. -2026' ],
        'so_suffix_desc'            => [ 'nl' => 'Tekst ná het nummer. Leeg laten voor geen suffix.', 'en' => 'Text after the number. Leave empty for no suffix.', 'de' => 'Text nach der Nummer. Leer lassen für kein Suffix.', 'fr' => 'Texte apres le numero. Laisser vide pour aucun suffixe.', 'es' => 'Texto despues del numero. Dejar vacio para sin sufijo.' ],
        'so_start_number'           => [ 'nl' => 'Startnummer',             'en' => 'Start number',                     'de' => 'Startnummer',                      'fr' => 'Numero de depart',                 'es' => 'Numero inicial' ],
        'so_start_desc'             => [ 'nl' => 'Eerste bestelnummer. Verhogen werkt meteen, verlagen pas na een reset.', 'en' => 'First order number. Increasing takes effect immediately, decreasing only after a reset.', 'de' => 'Erste Bestellnummer. Erhöhen wirkt sofort, verringern erst nach einem Reset.', 'fr' => 'Premier numero de commande. L\'augmentation prend effet immediatement, la diminution seulement apres un reset.', 'es' => 'Primer numero de pedido. Aumentar tiene efecto inmediato, reducir solo despues de un reinicio.' ],
        'so_padding'                => [ 'nl' => 'Minimum cijfers',         'en' => 'Minimum digits',                   'de' => 'Mindeststellen',                   'fr' => 'Chiffres minimum',                 'es' => 'Digitos minimos' ],
        'so_padding_desc'           => [ 'nl' => 'Aanvullen met nullen. "4" maakt van 1 → 0001.', 'en' => 'Pad with zeros. "4" turns 1 → 0001.', 'de' => 'Mit Nullen auffüllen. „4" macht aus 1 → 0001.', 'fr' => 'Completer avec des zeros. "4" transforme 1 en 0001.', 'es' => 'Rellenar con ceros. "4" convierte 1 en 0001.' ],
        'so_how_title'              => [ 'nl' => 'Hoe werkt het?',          'en' => 'How does it work?',                'de' => 'Wie funktioniert es?',             'fr' => 'Comment ca fonctionne ?',          'es' => 'Como funciona?' ],
        'so_how_1'                  => [ 'nl' => 'Elke nieuwe bestelling krijgt automatisch een eigen oplopend nummer.', 'en' => 'Every new order automatically gets its own sequential number.', 'de' => 'Jede neue Bestellung erhält automatisch eine eigene fortlaufende Nummer.', 'fr' => 'Chaque nouvelle commande recoit automatiquement son propre numero sequentiel.', 'es' => 'Cada nuevo pedido recibe automaticamente su propio numero secuencial.' ],
        'so_how_2'                  => [ 'nl' => 'Het nummer wordt opgeslagen als order meta — bestaande bestellingen behouden hun oude nummer.', 'en' => 'The number is stored as order meta — existing orders keep their old number.', 'de' => 'Die Nummer wird als Bestellungs-Meta gespeichert — bestehende Bestellungen behalten ihre alte Nummer.', 'fr' => 'Le numero est enregistre comme meta de commande — les commandes existantes conservent leur ancien numero.', 'es' => 'El numero se guarda como meta de pedido — los pedidos existentes mantienen su numero antiguo.' ],
        'so_how_3'                  => [ 'nl' => 'In WooCommerce admin kun je zoeken op het nieuwe bestelnummer.', 'en' => 'In WooCommerce admin you can search by the new order number.', 'de' => 'Im WooCommerce-Admin können Sie nach der neuen Bestellnummer suchen.', 'fr' => 'Dans l\'admin WooCommerce, vous pouvez rechercher par le nouveau numero de commande.', 'es' => 'En el admin de WooCommerce puedes buscar por el nuevo numero de pedido.' ],
        'so_how_4'                  => [ 'nl' => 'Op bestellingspagina\'s, e-mails en facturen verschijnt het nieuwe nummer automatisch.', 'en' => 'On order pages, emails and invoices the new number appears automatically.', 'de' => 'Auf Bestellseiten, E-Mails und Rechnungen erscheint die neue Nummer automatisch.', 'fr' => 'Sur les pages de commande, les e-mails et les factures, le nouveau numero apparait automatiquement.', 'es' => 'En las paginas de pedido, correos y facturas el nuevo numero aparece automaticamente.' ],
        'so_how_5'                  => [ 'nl' => 'Twee gelijktijdige bestellingen krijgen nooit hetzelfde nummer (DB-lock).', 'en' => 'Two simultaneous orders will never get the same number (DB lock).', 'de' => 'Zwei gleichzeitige Bestellungen erhalten nie dieselbe Nummer (DB-Lock).', 'fr' => 'Deux commandes simultanees n\'auront jamais le meme numero (verrou DB).', 'es' => 'Dos pedidos simultaneos nunca tendran el mismo numero (bloqueo DB).' ],
        'so_next_order'             => [ 'nl' => 'Volgende bestelling krijgt', 'en' => 'Next order gets',              'de' => 'Nächste Bestellung erhält',        'fr' => 'La prochaine commande recevra',    'es' => 'El proximo pedido obtendra' ],
        'so_counter_status'         => [ 'nl' => 'Teller status',           'en' => 'Counter status',                   'de' => 'Zählerstand',                      'fr' => 'Etat du compteur',                 'es' => 'Estado del contador' ],
        'so_current_number'         => [ 'nl' => 'Huidig nummer',           'en' => 'Current number',                   'de' => 'Aktuelle Nummer',                  'fr' => 'Numero actuel',                    'es' => 'Numero actual' ],
        'so_no_orders_yet'          => [ 'nl' => 'Nog geen bestellingen',   'en' => 'No orders yet',                    'de' => 'Noch keine Bestellungen',          'fr' => 'Aucune commande pour l\'instant',  'es' => 'Aun no hay pedidos' ],
        'so_start_number_label'     => [ 'nl' => 'Startnummer',             'en' => 'Start number',                     'de' => 'Startnummer',                      'fr' => 'Numero de depart',                 'es' => 'Numero inicial' ],
        'so_padding_label'          => [ 'nl' => 'Padding',                 'en' => 'Padding',                          'de' => 'Auffüllung',                       'fr' => 'Remplissage',                      'es' => 'Relleno' ],
        'so_digits'                 => [ 'nl' => 'cijfers',                 'en' => 'digits',                           'de' => 'Stellen',                          'fr' => 'chiffres',                         'es' => 'digitos' ],
        'so_reset_title'            => [ 'nl' => 'Teller resetten',         'en' => 'Reset counter',                    'de' => 'Zähler zurücksetzen',              'fr' => 'Reinitialiser le compteur',        'es' => 'Restablecer contador' ],
        'so_reset_desc'             => [ 'nl' => 'Zet de teller terug naar het startnummer. Doet niks met bestaande bestellingen.', 'en' => 'Resets the counter to the start number. Does nothing to existing orders.', 'de' => 'Setzt den Zähler auf die Startnummer zurück. Bestehende Bestellungen bleiben unverändert.', 'fr' => 'Reinitialise le compteur au numero de depart. N\'affecte pas les commandes existantes.', 'es' => 'Restablece el contador al numero inicial. No afecta los pedidos existentes.' ],
        'so_reset_btn'              => [ 'nl' => 'Teller resetten',         'en' => 'Reset counter',                    'de' => 'Zähler zurücksetzen',              'fr' => 'Reinitialiser le compteur',        'es' => 'Restablecer contador' ],

        /* ---- Quick Products tab (PHP) ---- */
        'qp_tab_list'               => [ 'nl' => 'Producten',               'en' => 'Products',                  'de' => 'Produkte',                  'fr' => 'Produits',                  'es' => 'Productos' ],
        'qp_tab_editor'             => [ 'nl' => 'Editor',                  'en' => 'Editor',                    'de' => 'Editor',                    'fr' => 'Editeur',                   'es' => 'Editor' ],
        'qp_new_product_btn'        => [ 'nl' => 'Nieuw product',           'en' => 'New product',               'de' => 'Neues Produkt',             'fr' => 'Nouveau produit',           'es' => 'Nuevo producto' ],
        'qp_search_placeholder'     => [ 'nl' => 'Zoek op naam, SKU…',      'en' => 'Search by name, SKU…',      'de' => 'Nach Name, SKU suchen…',    'fr' => 'Rechercher par nom, SKU…',  'es' => 'Buscar por nombre, SKU…' ],
        'qp_filter_all_statuses'    => [ 'nl' => 'Alle statussen',          'en' => 'All statuses',              'de' => 'Alle Status',               'fr' => 'Tous les statuts',          'es' => 'Todos los estados' ],
        'qp_filter_published'       => [ 'nl' => 'Gepubliceerd',            'en' => 'Published',                 'de' => 'Veröffentlicht',            'fr' => 'Publie',                    'es' => 'Publicado' ],
        'qp_filter_draft'           => [ 'nl' => 'Concept',                 'en' => 'Draft',                     'de' => 'Entwurf',                   'fr' => 'Brouillon',                 'es' => 'Borrador' ],
        'qp_filter_private'         => [ 'nl' => 'Privé',                   'en' => 'Private',                   'de' => 'Privat',                    'fr' => 'Prive',                     'es' => 'Privado' ],
        'qp_filter_all_cats'        => [ 'nl' => 'Alle categorieën',        'en' => 'All categories',            'de' => 'Alle Kategorien',           'fr' => 'Toutes les categories',     'es' => 'Todas las categorias' ],
        'qp_filter_all_types'       => [ 'nl' => 'Alle typen',              'en' => 'All types',                 'de' => 'Alle Typen',                'fr' => 'Tous les types',            'es' => 'Todos los tipos' ],
        'qp_type_simple'            => [ 'nl' => 'Enkelvoudig',             'en' => 'Simple',                    'de' => 'Einfach',                   'fr' => 'Simple',                    'es' => 'Simple' ],
        'qp_type_variable'          => [ 'nl' => 'Variabel',                'en' => 'Variable',                  'de' => 'Variabel',                  'fr' => 'Variable',                  'es' => 'Variable' ],
        'qp_type_grouped'           => [ 'nl' => 'Gegroepeerd',             'en' => 'Grouped',                   'de' => 'Gruppiert',                 'fr' => 'Groupe',                    'es' => 'Agrupado' ],
        'qp_type_external'          => [ 'nl' => 'Extern',                  'en' => 'External',                  'de' => 'Extern',                    'fr' => 'Externe',                   'es' => 'Externo' ],
        'qp_type_external_aff'      => [ 'nl' => 'Extern/Affiliate',        'en' => 'External/Affiliate',        'de' => 'Extern/Affiliate',          'fr' => 'Externe/Affilie',           'es' => 'Externo/Afiliado' ],
        'qp_sort_newest'            => [ 'nl' => 'Nieuwste eerst',          'en' => 'Newest first',              'de' => 'Neueste zuerst',            'fr' => 'Plus recents d\'abord',     'es' => 'Mas recientes primero' ],
        'qp_sort_name'              => [ 'nl' => 'Naam A–Z',                'en' => 'Name A–Z',                  'de' => 'Name A–Z',                  'fr' => 'Nom A–Z',                   'es' => 'Nombre A–Z' ],
        'qp_sort_price'             => [ 'nl' => 'Prijs laag–hoog',         'en' => 'Price low–high',            'de' => 'Preis niedrig–hoch',        'fr' => 'Prix bas–haut',             'es' => 'Precio bajo–alto' ],
        'qp_section_product_info'   => [ 'nl' => 'Productinfo',             'en' => 'Product info',              'de' => 'Produktinfo',               'fr' => 'Info produit',              'es' => 'Info del producto' ],
        'qp_name_label'             => [ 'nl' => 'Productnaam *',           'en' => 'Product name *',            'de' => 'Produktname *',             'fr' => 'Nom du produit *',          'es' => 'Nombre del producto *' ],
        'qp_name_placeholder'       => [ 'nl' => 'Bijv. Fantasie Boek',     'en' => 'E.g. Fantasy Book',         'de' => 'Z.B. Fantasiebuch',         'fr' => 'Ex. Livre fantastique',     'es' => 'Ej. Libro de fantasia' ],
        'qp_sku_label'              => [ 'nl' => 'SKU',                     'en' => 'SKU',                       'de' => 'SKU',                       'fr' => 'SKU',                       'es' => 'SKU' ],
        'qp_product_type_label'     => [ 'nl' => 'Producttype',             'en' => 'Product type',              'de' => 'Produkttyp',                'fr' => 'Type de produit',           'es' => 'Tipo de producto' ],
        'qp_slug_label'             => [ 'nl' => 'URL-slug',                'en' => 'URL slug',                  'de' => 'URL-Slug',                  'fr' => 'Slug URL',                  'es' => 'URL slug' ],
        'qp_slug_placeholder'       => [ 'nl' => 'auto-gegenereerd',        'en' => 'auto-generated',            'de' => 'automatisch generiert',     'fr' => 'genere automatiquement',    'es' => 'generado automaticamente' ],
        'qp_short_desc_label'       => [ 'nl' => 'Korte beschrijving',      'en' => 'Short description',         'de' => 'Kurzbeschreibung',          'fr' => 'Description courte',        'es' => 'Descripcion corta' ],
        'qp_short_desc_placeholder' => [ 'nl' => 'Wordt getoond bij het product in de winkel.', 'en' => 'Shown with the product in the shop.', 'de' => 'Wird beim Produkt im Shop angezeigt.', 'fr' => 'Affichee avec le produit dans la boutique.', 'es' => 'Se muestra con el producto en la tienda.' ],
        'qp_full_desc_label'        => [ 'nl' => 'Volledige beschrijving',  'en' => 'Full description',          'de' => 'Vollständige Beschreibung', 'fr' => 'Description complete',      'es' => 'Descripcion completa' ],
        'qp_full_desc_placeholder'  => [ 'nl' => 'Uitgebreide productbeschrijving…', 'en' => 'Detailed product description…', 'de' => 'Detaillierte Produktbeschreibung…', 'fr' => 'Description detaillee du produit…', 'es' => 'Descripcion detallada del producto…' ],
        'qp_section_price'          => [ 'nl' => 'Prijs',                   'en' => 'Price',                     'de' => 'Preis',                     'fr' => 'Prix',                      'es' => 'Precio' ],
        'qp_regular_price_label'    => [ 'nl' => 'Reguliere prijs',         'en' => 'Regular price',             'de' => 'Regulärer Preis',           'fr' => 'Prix regulier',             'es' => 'Precio normal' ],
        'qp_sale_price_label'       => [ 'nl' => 'Aanbiedingsprijs',        'en' => 'Sale price',                'de' => 'Angebotspreis',             'fr' => 'Prix promo',                'es' => 'Precio de oferta' ],
        'qp_sale_price_placeholder' => [ 'nl' => 'leeg = geen aanbieding',  'en' => 'empty = no sale',           'de' => 'leer = kein Angebot',       'fr' => 'vide = pas de promo',       'es' => 'vacio = sin oferta' ],
        'qp_sale_from_label'        => [ 'nl' => 'Aanbieding van',          'en' => 'Sale from',                 'de' => 'Angebot ab',                'fr' => 'Promo du',                  'es' => 'Oferta desde' ],
        'qp_sale_to_label'          => [ 'nl' => 'Aanbieding tot',          'en' => 'Sale until',                'de' => 'Angebot bis',               'fr' => 'Promo jusqu\'au',           'es' => 'Oferta hasta' ],
        'qp_section_stock'          => [ 'nl' => 'Voorraad',                'en' => 'Stock',                     'de' => 'Bestand',                   'fr' => 'Stock',                     'es' => 'Stock' ],
        'qp_stock_status_label'     => [ 'nl' => 'Voorraadstatus',          'en' => 'Stock status',              'de' => 'Bestandsstatus',            'fr' => 'Statut du stock',           'es' => 'Estado del stock' ],
        'qp_instock_label'          => [ 'nl' => 'Op voorraad',             'en' => 'In stock',                  'de' => 'Auf Lager',                 'fr' => 'En stock',                  'es' => 'En stock' ],
        'qp_outofstock_label'       => [ 'nl' => 'Niet op voorraad',        'en' => 'Out of stock',              'de' => 'Nicht auf Lager',           'fr' => 'Rupture de stock',          'es' => 'Sin stock' ],
        'qp_onbackorder_label'      => [ 'nl' => 'Nabestelling',            'en' => 'On backorder',              'de' => 'Nachbestellung',            'fr' => 'Sur commande',              'es' => 'Por encargo' ],
        'qp_manage_stock_label'     => [ 'nl' => 'Voorraad bijhouden',      'en' => 'Track stock',               'de' => 'Bestand verfolgen',         'fr' => 'Suivre le stock',           'es' => 'Rastrear stock' ],
        'qp_stock_qty_label'        => [ 'nl' => 'Aantal op voorraad',      'en' => 'Stock quantity',            'de' => 'Bestandsmenge',             'fr' => 'Quantite en stock',         'es' => 'Cantidad en stock' ],
        'qp_backorders_label'       => [ 'nl' => 'Nabestellingen',          'en' => 'Backorders',                'de' => 'Nachbestellungen',          'fr' => 'Commandes en attente',      'es' => 'Pedidos pendientes' ],
        'qp_backorders_no'          => [ 'nl' => 'Niet toestaan',           'en' => 'Do not allow',              'de' => 'Nicht erlauben',            'fr' => 'Ne pas autoriser',          'es' => 'No permitir' ],
        'qp_backorders_notify'      => [ 'nl' => 'Toestaan (meld klant)',   'en' => 'Allow (notify customer)',   'de' => 'Erlauben (Kunde benachrichtigen)', 'fr' => 'Autoriser (notifier le client)', 'es' => 'Permitir (notificar al cliente)' ],
        'qp_backorders_yes'         => [ 'nl' => 'Toestaan',                'en' => 'Allow',                     'de' => 'Erlauben',                  'fr' => 'Autoriser',                 'es' => 'Permitir' ],
        'qp_low_stock_label'        => [ 'nl' => 'Lage voorraad drempel',   'en' => 'Low stock threshold',       'de' => 'Mindestbestandsgrenze',     'fr' => 'Seuil de stock faible',     'es' => 'Umbral de stock bajo' ],
        'qp_store_default'          => [ 'nl' => 'Winkelstandaard',         'en' => 'Store default',             'de' => 'Shop-Standard',             'fr' => 'Valeur par defaut boutique','es' => 'Predeterminado de tienda' ],
        'qp_sold_individually'      => [ 'nl' => 'Slechts 1 per bestelling','en' => 'Only 1 per order',          'de' => 'Nur 1 pro Bestellung',      'fr' => 'Un seul par commande',      'es' => 'Solo 1 por pedido' ],
        'qp_section_shipping'       => [ 'nl' => 'Verzending',              'en' => 'Shipping',                  'de' => 'Versand',                   'fr' => 'Livraison',                 'es' => 'Envio' ],
        'qp_virtual_label'          => [ 'nl' => 'Virtueel',                'en' => 'Virtual',                   'de' => 'Virtuell',                  'fr' => 'Virtuel',                   'es' => 'Virtual' ],
        'qp_downloadable_label'     => [ 'nl' => 'Downloadbaar',            'en' => 'Downloadable',              'de' => 'Herunterladbar',            'fr' => 'Telechargeable',            'es' => 'Descargable' ],
        'qp_weight_label'           => [ 'nl' => 'Gewicht',                 'en' => 'Weight',                    'de' => 'Gewicht',                   'fr' => 'Poids',                     'es' => 'Peso' ],
        'qp_dimensions_label'       => [ 'nl' => 'Afmetingen (l × b × h)', 'en' => 'Dimensions (l × w × h)',    'de' => 'Abmessungen (l × b × h)',   'fr' => 'Dimensions (l × l × h)',    'es' => 'Dimensiones (l × a × h)' ],
        'qp_dim_length'             => [ 'nl' => 'L',                       'en' => 'L',                         'de' => 'L',                         'fr' => 'L',                         'es' => 'L' ],
        'qp_dim_width'              => [ 'nl' => 'B',                       'en' => 'W',                         'de' => 'B',                         'fr' => 'L',                         'es' => 'A' ],
        'qp_dim_height'             => [ 'nl' => 'H',                       'en' => 'H',                         'de' => 'H',                         'fr' => 'H',                         'es' => 'H' ],
        'qp_shipping_class_label'   => [ 'nl' => 'Verzendklasse',           'en' => 'Shipping class',            'de' => 'Versandklasse',             'fr' => 'Classe de livraison',       'es' => 'Clase de envio' ],
        'qp_no_shipping_class'      => [ 'nl' => 'Geen verzendklasse',      'en' => 'No shipping class',         'de' => 'Keine Versandklasse',       'fr' => 'Aucune classe de livraison','es' => 'Sin clase de envio' ],
        'qp_section_tax'            => [ 'nl' => 'Belasting',               'en' => 'Tax',                       'de' => 'Steuer',                    'fr' => 'Taxe',                      'es' => 'Impuesto' ],
        'qp_tax_status_label'       => [ 'nl' => 'Belastingstatus',         'en' => 'Tax status',                'de' => 'Steuerstatus',              'fr' => 'Statut fiscal',             'es' => 'Estado fiscal' ],
        'qp_taxable'                => [ 'nl' => 'Belastbaar',              'en' => 'Taxable',                   'de' => 'Steuerpflichtig',           'fr' => 'Imposable',                 'es' => 'Imponible' ],
        'qp_shipping_only'          => [ 'nl' => 'Alleen verzending',       'en' => 'Shipping only',             'de' => 'Nur Versand',               'fr' => 'Livraison uniquement',      'es' => 'Solo envio' ],
        'qp_none_tax'               => [ 'nl' => 'Geen',                    'en' => 'None',                      'de' => 'Keine',                     'fr' => 'Aucune',                    'es' => 'Ninguno' ],
        'qp_tax_class_label'        => [ 'nl' => 'Belastingklasse',         'en' => 'Tax class',                 'de' => 'Steuerklasse',              'fr' => 'Classe de taxe',            'es' => 'Clase de impuesto' ],
        'qp_section_publish'        => [ 'nl' => 'Publiceren',              'en' => 'Publish',                   'de' => 'Veröffentlichen',           'fr' => 'Publier',                   'es' => 'Publicar' ],
        'qp_status_label'           => [ 'nl' => 'Status',                  'en' => 'Status',                    'de' => 'Status',                    'fr' => 'Statut',                    'es' => 'Estado' ],
        'qp_status_publish'         => [ 'nl' => 'Gepubliceerd',            'en' => 'Published',                 'de' => 'Veröffentlicht',            'fr' => 'Publie',                    'es' => 'Publicado' ],
        'qp_status_draft'           => [ 'nl' => 'Concept',                 'en' => 'Draft',                     'de' => 'Entwurf',                   'fr' => 'Brouillon',                 'es' => 'Borrador' ],
        'qp_status_pending'         => [ 'nl' => 'Wacht op beoordeling',    'en' => 'Pending review',            'de' => 'Ausstehende Überprüfung',   'fr' => 'En attente de revision',    'es' => 'Pendiente de revision' ],
        'qp_status_private'         => [ 'nl' => 'Privé',                   'en' => 'Private',                   'de' => 'Privat',                    'fr' => 'Prive',                     'es' => 'Privado' ],
        'qp_visibility_label'       => [ 'nl' => 'Cataloguszichtbaarheid',  'en' => 'Catalog visibility',        'de' => 'Katalogsichtbarkeit',       'fr' => 'Visibilite du catalogue',   'es' => 'Visibilidad del catalogo' ],
        'qp_visibility_visible'     => [ 'nl' => 'Winkel en zoekresultaten','en' => 'Shop and search results',   'de' => 'Shop und Suchergebnisse',   'fr' => 'Boutique et resultats de recherche', 'es' => 'Tienda y resultados de busqueda' ],
        'qp_visibility_catalog'     => [ 'nl' => 'Alleen winkel',           'en' => 'Shop only',                 'de' => 'Nur Shop',                  'fr' => 'Boutique uniquement',       'es' => 'Solo tienda' ],
        'qp_visibility_search'      => [ 'nl' => 'Alleen zoeken',           'en' => 'Search only',               'de' => 'Nur Suche',                 'fr' => 'Recherche uniquement',      'es' => 'Solo busqueda' ],
        'qp_visibility_hidden'      => [ 'nl' => 'Verborgen',               'en' => 'Hidden',                    'de' => 'Verborgen',                 'fr' => 'Cache',                     'es' => 'Oculto' ],
        'qp_featured_label'         => [ 'nl' => 'Uitgelicht product',      'en' => 'Featured product',          'de' => 'Empfohlenes Produkt',       'fr' => 'Produit mis en avant',      'es' => 'Producto destacado' ],
        'qp_save_btn'               => [ 'nl' => 'Opslaan',                 'en' => 'Save',                      'de' => 'Speichern',                 'fr' => 'Enregistrer',               'es' => 'Guardar' ],
        'qp_edit_in_wc'             => [ 'nl' => '↗ Bewerken in WooCommerce', 'en' => '↗ Edit in WooCommerce',  'de' => '↗ In WooCommerce bearbeiten', 'fr' => '↗ Modifier dans WooCommerce', 'es' => '↗ Editar en WooCommerce' ],
        'qp_section_image'          => [ 'nl' => 'Productafbeelding',       'en' => 'Product image',             'de' => 'Produktbild',               'fr' => 'Image du produit',          'es' => 'Imagen del producto' ],
        'qp_image_click'            => [ 'nl' => 'Klik om afbeelding te selecteren', 'en' => 'Click to select image', 'de' => 'Klicken um Bild auszuwählen', 'fr' => 'Cliquer pour selectionner une image', 'es' => 'Clic para seleccionar imagen' ],
        'qp_select_image_btn'       => [ 'nl' => 'Selecteer afbeelding',    'en' => 'Select image',              'de' => 'Bild auswählen',            'fr' => 'Selectionner une image',    'es' => 'Seleccionar imagen' ],
        'qp_remove_image_btn'       => [ 'nl' => 'Verwijderen',             'en' => 'Remove',                    'de' => 'Entfernen',                 'fr' => 'Supprimer',                 'es' => 'Eliminar' ],
        'qp_section_gallery'        => [ 'nl' => 'Productgalerij',          'en' => 'Product gallery',           'de' => 'Produktgalerie',            'fr' => 'Galerie du produit',        'es' => 'Galeria del producto' ],
        'qp_add_gallery_btn'        => [ 'nl' => 'Afbeeldingen toevoegen',  'en' => 'Add images',               'de' => 'Bilder hinzufügen',         'fr' => 'Ajouter des images',        'es' => 'Agregar imagenes' ],
        'qp_remove_gallery_title'   => [ 'nl' => 'Verwijderen',             'en' => 'Remove',                    'de' => 'Entfernen',                 'fr' => 'Supprimer',                 'es' => 'Eliminar' ],
        'qp_section_categories'     => [ 'nl' => 'Categorieën',             'en' => 'Categories',                'de' => 'Kategorien',                'fr' => 'Categories',                'es' => 'Categorias' ],
        'qp_no_categories'          => [ 'nl' => 'Nog geen categorieën.',   'en' => 'No categories yet.',        'de' => 'Noch keine Kategorien.',    'fr' => 'Aucune categorie pour l\'instant.', 'es' => 'Aun no hay categorias.' ],
        'qp_section_tags'           => [ 'nl' => 'Producttags',             'en' => 'Product tags',              'de' => 'Produkt-Tags',              'fr' => 'Tags du produit',           'es' => 'Etiquetas del producto' ],
        'qp_tag_placeholder'        => [ 'nl' => 'Tag toevoegen en Enter drukken…', 'en' => 'Add tag and press Enter…', 'de' => 'Tag hinzufügen und Enter drücken…', 'fr' => 'Ajouter un tag et appuyer sur Entree…', 'es' => 'Agregar etiqueta y pulsar Enter…' ],
        'qp_section_brands'         => [ 'nl' => 'Merken',                  'en' => 'Brands',                    'de' => 'Marken',                    'fr' => 'Marques',                   'es' => 'Marcas' ],
        'qp_section_attributes'     => [ 'nl' => 'Attributen',              'en' => 'Attributes',                'de' => 'Attribute',                 'fr' => 'Attributs',                 'es' => 'Atributos' ],

        /* ---- WooCommerce order statussen ---- */
        'wc_status_pending'         => [ 'nl' => 'In afwachting',           'en' => 'Pending payment',           'de' => 'Ausstehende Zahlung',       'fr' => 'Paiement en attente',       'es' => 'Pago pendiente' ],
        'wc_status_processing'      => [ 'nl' => 'In behandeling',          'en' => 'Processing',                'de' => 'In Bearbeitung',            'fr' => 'En cours',                  'es' => 'En proceso' ],
        'wc_status_on_hold'         => [ 'nl' => 'In de wacht',             'en' => 'On hold',                   'de' => 'Wartend',                   'fr' => 'En attente',                'es' => 'En espera' ],
        'wc_status_completed'       => [ 'nl' => 'Voltooid',                'en' => 'Completed',                 'de' => 'Abgeschlossen',             'fr' => 'Termine',                   'es' => 'Completado' ],
        'wc_status_cancelled'       => [ 'nl' => 'Geannuleerd',             'en' => 'Cancelled',                 'de' => 'Storniert',                 'fr' => 'Annule',                    'es' => 'Cancelado' ],
        'wc_status_refunded'        => [ 'nl' => 'Terugbetaald',            'en' => 'Refunded',                  'de' => 'Erstattet',                 'fr' => 'Rembourse',                 'es' => 'Reembolsado' ],
        'wc_status_failed'          => [ 'nl' => 'Mislukt',                 'en' => 'Failed',                    'de' => 'Fehlgeschlagen',            'fr' => 'Echoue',                    'es' => 'Fallido' ],

        /* ---- CSV export kolommen ---- */
        'col_id'                    => [ 'nl' => 'Order ID',                'en' => 'Order ID',                  'de' => 'Bestell-ID',                'fr' => 'ID commande',               'es' => 'ID pedido' ],
        'col_date'                  => [ 'nl' => 'Datum',                   'en' => 'Date',                      'de' => 'Datum',                     'fr' => 'Date',                      'es' => 'Fecha' ],
        'col_status_csv'            => [ 'nl' => 'Status',                  'en' => 'Status',                    'de' => 'Status',                    'fr' => 'Statut',                    'es' => 'Estado' ],
        'col_customer'              => [ 'nl' => 'Klant',                   'en' => 'Customer',                  'de' => 'Kunde',                     'fr' => 'Client',                    'es' => 'Cliente' ],
        'col_city'                  => [ 'nl' => 'Stad',                    'en' => 'City',                      'de' => 'Stadt',                     'fr' => 'Ville',                     'es' => 'Ciudad' ],
        'col_total'                 => [ 'nl' => 'Totaal',                  'en' => 'Total',                     'de' => 'Gesamt',                    'fr' => 'Total',                     'es' => 'Total' ],
        'col_items'                 => [ 'nl' => 'Producten',               'en' => 'Products',                  'de' => 'Produkte',                  'fr' => 'Produits',                  'es' => 'Productos' ],
        'col_payment'               => [ 'nl' => 'Betaling',                'en' => 'Payment',                   'de' => 'Zahlung',                   'fr' => 'Paiement',                  'es' => 'Pago' ],
    ];
}

/**
 * Geeft de vertaling terug voor een gegeven sleutel.
 *
 * @param string $key  Sleutel uit ph_translations().
 * @param string $lang Taalcode ('nl'|'en'). Standaard automatisch.
 * @return string
 */
function ph_t( string $key, string $lang = '' ): string { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
    if ( $lang === '' ) {
        $lang = ph_get_lang();
    }
    $table = ph_translations();
    return $table[ $key ][ $lang ] ?? $table[ $key ]['en'] ?? $table[ $key ]['nl'] ?? $key;
}

/**
 * Datum formatteren in de juiste taal.
 * Wanneer lang = 'en' wordt PHP's date() gebruikt (altijd Engelstalig).
 * Wanneer lang = 'nl' wordt date_i18n() gebruikt (WordPress-locale).
 *
 * @param string   $format  PHP date format string.
 * @param int|null $ts      Unix timestamp; null = nu.
 * @param string   $lang    Taalcode ('nl'|'en'). Standaard automatisch.
 * @return string
 */
function ph_date( string $format, ?int $ts = null, string $lang = '' ): string { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
    if ( $lang === '' ) {
        $lang = ph_get_lang();
    }
    $ts = $ts ?? time();
    if ( $lang === 'en' ) {
        return date( $format, $ts ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
    }
    return date_i18n( $format, $ts );
}
