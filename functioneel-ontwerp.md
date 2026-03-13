# Functioneel Ontwerp — Product Haven

**Versie:** 1.3.0  
**Datum:** 2026-03-12  
**Auteur:** Odin Wattez  

---

## 1. Inleiding

Product Haven is een WordPress-plugin die bovenop WooCommerce een volledig eigen beheerdashboard biedt. Het doel is om de dagelijkse operationele taken van een webshop-eigenaar — orders bekijken, voorraad beheren, producten aanpassen — vanuit één scherm te kunnen uitvoeren, zonder steeds door de standaard WooCommerce-menu's te navigeren.

De plugin is bedoeld voor:

- **Winkelbeheerders** die dagelijks orders verwerken en statistieken willen volgen.
- **Producteigenaren** die snel producten willen aanpassen of aanmaken.
- **Voorraadbeheerders** die lage voorraad willen signaleren en opvolgen.
- **Ontwikkelaars** die de frontend van een webshop willen uitbreiden met Elementor-widgets.

---

## 2. Gebruikersrollen en toegang

| Rol | Toegang |
|-----|---------|
| `manage_woocommerce` | Volledige toegang tot het admin-dashboard, alle tabs, instellingen en AJAX-acties |
| `edit_products` | Toegang tot Quick Products tab (aanmaken en bewerken van producten) |
| Ingelogde bezoeker | Frontend Elementor-widgets: eigen statistieken en tijdlijn |
| Niet-ingelogde bezoeker | Geen toegang (HTTP 401 op alle endpoints) |

---

## 3. Modules en functionaliteit

### 3.1 Order Dashboard

Het dashboard is de centrale plek voor orderbeheer. Het toont:

**Statistieken (bovenaan)**
- Totale omzet in de geselecteerde periode
- Aantal orders
- Gemiddeld orderbedrag
- Nieuwe klanten (eerste aankoop in periode)
- Terugkerende klanten
- Totale terugbetalingen

De periode is instelbaar: 7, 14, 30, 90 dagen of 1 jaar. De standaardperiode is configureerbaar via Instellingen.

**Grafiek**
- Omzet en orderaantallen per dag weergegeven als lijn- of staafgrafiek (Chart.js)
- Datasets zijn individueel aan- en uitschakelbaar

**Tijdlijn**
- Gesorteerde lijst van alle orders, nieuwste bovenaan
- Doorzoekbaar op naam, e-mailadres of order-ID
- Filterbaar op orderstatus (alle, afgerond, verwerking, in de wacht, geannuleerd, enzovoort)
- Paginering (configureerbaar aantal per pagina)

**Order modal**
Via de tijdlijn is elke order te openen in een detailvenster:
- Klantgegevens: naam, e-mail, telefoon, volledig factuuradres
- Orderregels: productnaam, aantal, subtotaal
- Betaalmethode, datum, totaal
- Ordernotities (klant + intern), met de mogelijkheid een nieuwe notitie toe te voegen
- Statuswijziging: afgerond, verwerking, in de wacht, geannuleerd, mislukt
- Terugbetaling aanvragen of terugdraaien
- Order verwijderen (permanent, met bevestigingsstap)
- Order bewerken: factuurvelden aanpassen (10 velden) + interne notitie

**Klantenkaart**
Vanuit de order modal is de klantenkaart te openen:
- Profielfoto (Gravatar), naam, e-mail, stad, land, registratiedatum
- Totaal besteed, gemiddeld orderbedrag, totaal aantal orders
- Volledige orderhistorie van de klant

**Top producten**
- Beste 5 producten op aantal stuks verkocht in de gekozen periode
- Zichtbaar als blok naast de statistieken (aan/uit via instellingen)

**CSV export**
- Configureerbare export: kies welke kolommen worden meegenomen
- Gefilterd op huidige periode en statusfilter
- Direct als download, geen tijdelijk bestand op de server

---

### 3.2 Stock Management (Voorraad-tab)

De voorraadtab biedt een volledig overzicht van de productvoorraad en waarschuwingen.

**Overzichtstabel**
- Alle producten met voorraadbeheer ingeschakeld
- Kolommen: naam, SKU, voorraad, status (OK / laag / uitverkocht)
- Zoekbalk, filter (alle / laag / uitverkocht), sorteerbaar, gepagineerd

**Statkaarten**
- Totaal producten
- Totale voorraadwaarde
- Aantal producten met lage voorraad
- Aantal uitverkochte producten

**Inline voorraad bewerken**
- Via een modal: nieuwe hoeveelheid invoeren + reden opgeven
- Wijziging wordt gelogd in de database-logtabel

**Bulk-update**
- Meerdere producten selecteren en tegelijk een voorraadwijziging doorvoeren

**CSV-export**
- Volledige voorraadlijst exporteerbaar

**Instellingen (eigen paneel)**
- Drempelwaarde voor "lage voorraad" (standaard: 20)
- E-mailadres voor alerts
- Realtime alerts aan/uit
- Dagelijkse digest aan/uit
- Testknop om direct een alertmail te sturen

**E-mailalerts**
- Realtime: direct bij een voorraadwijziging die de drempel raakt of het product uitverkocht maakt
- 30-minuten cooldown per product om spam te voorkomen
- Dagelijkse digest: overzicht van alle lage/uitverkochte producten
- WooCommerce eigen stock-mails zijn uitgeschakeld; Product Haven stuurt ze zelf

---

### 3.3 Quick Products

De Quick Products-tab maakt het mogelijk producten te beheren zonder WooCommerce in te gaan.

**Productlijst**
- Zoeken, filteren op status (gepubliceerd, concept, privé, wachtend)
- Kolommen: naam, status, prijs, voorraadstatus
- Acties per rij: bewerken, dupliceren, verwijderen, openen in WooCommerce

**Product aanmaken / bewerken**
Via een modal met velden:
- Naam (verplicht)
- Beschrijving en korte beschrijving (WYSIWYG light)
- Status (gepubliceerd / concept / privé / wachtend)
- Prijs en aanbiedingsprijs
- Voorraadhoeveelheid en voorraadstatus
- SKU
- Categorieën, tags, merken (indien taxonomie actief)
- Productattributen (alle WooCommerce-attribuuttaxonomieën)
- Afbeelding (WordPress Media Library)
- Galerij (WordPress Media Library, meerdere afbeeldingen)

**Dupliceren**
- Maakt een exacte kopie van het product aan met "(kopie)" in de naam

**Verwijderen**
- Directe permanente verwijdering met bevestigingsstap

---

### 3.4 Sequential Order Numbers

Geeft WooCommerce-bestellingen een eigen oplopend nummer, los van de WordPress post-ID.

**Instellingen**
- Voorvoegsel (bijv. `#ORD-`)
- Achtervoegsel (bijv. `-2026`)
- Opvulling (bijv. `5` → `00001`)
- Startnummer

**Werking**
- Nummer wordt toegewezen bij aanmaken van een bestelling (checkout + Store API)
- Nummer is doorzoekbaar in WooCommerce admin
- Thread-safe: gebruikt een MySQL-lock om dubbeltellingen te voorkomen

---

### 3.5 Frontend Elementor-widgets

Twee widgets voor de frontend van de webshop, beschikbaar in de Elementor-editor.

**Stats Widget**
- Toont voor de ingelogde klant: totale omzet, aantal orders, gemiddeld orderbedrag
- Instelbare periode

**Timeline Widget**
- Gepagineerde orderhistorie voor de ingelogde klant
- Statusbadges, productlijst per order

---

### 3.6 Instellingen

Het instellingenpaneel is bereikbaar via de ⚙-knop in de adminheader.

| Instelling | Standaard | Beschrijving |
|-----------|-----------|--------------|
| Standaardperiode | 30 dagen | Actieve periode bij openen |
| Grafiektype | Lijn | Lijn of staaf |
| Toon gemiddeld orderbedrag | Aan | Statkaart zichtbaar |
| Toon top producten | Aan | Blok zichtbaar |
| Orders per pagina | 20 | Tijdlijnpaginering |
| Accentkleur | `#10B981` | Aanpasbare merkkleur |
| Exportkolommen | Alle | Keuze per kolom |
| REST API inschakelen | Uit | REST-endpoints activeren |

---

### 3.7 Taalondersteuning

De plugin heeft een eigen i18n-systeem, los van de WordPress `.po/.mo`-stroom.

Ondersteunde talen:
- **NL** — Nederlands (standaard)
- **EN** — Engels
- **DE** — Duits
- **FR** — Frans
- **ES** — Spaans

De actieve taal wordt per beheerder opgeslagen (`ph_lang` user meta). De taalwisselaar in de adminheader toont vijf knoppen: NL · EN · DE · FR · ES. Bij wisselen wordt de taal opgeslagen via AJAX en de pagina herladen.

---

### 3.8 REST API (optioneel)

De REST API is standaard uitgeschakeld en activeerbaar via Instellingen.

| Methode | Endpoint | Beschrijving |
|---------|----------|--------------|
| GET | `/wp-json/product-haven/v1/stats?days=30` | Statistieken voor een periode |
| GET | `/wp-json/product-haven/v1/timeline?page=1&per_page=20` | Gepagineerde tijdlijn |
| GET | `/wp-json/product-haven/v1/top-products?days=30&limit=10` | Bestverkochte producten |

Vereist: `manage_woocommerce`-capability. Geen publieke toegang.

---

## 4. Gebruikersstromen

### 4.1 Orderstroom
```
Dashboard openen
  → Periode kiezen (7/14/30/90 dagen / 1 jaar)
  → Statistieken en grafiek laden automatisch
  → Order klikken in tijdlijn → Order modal opent
    → Notitie toevoegen
    → Status wijzigen
    → Klantkaart openen
    → Bewerken (billing-velden)
    → Terugbetalen / terugdraaien
    → Verwijderen (bevestiging vereist)
```

### 4.2 Voorraadstroom
```
Voorraad-tab openen
  → Overzichtstabel toont alle producten
  → Filteren op "laag" of "uitverkocht"
  → Product selecteren → Edit-modal → Nieuwe hoeveelheid + reden → Opslaan
  → OF meerdere producten selecteren → Bulk-update
  → Instellingen: drempelwaarde, alerts configureren
```

### 4.3 Quick Products-stroom
```
Quick Products-tab openen
  → Productlijst laden
  → Nieuw product → Editor modal → Alle velden invullen → Opslaan
  → OF bestaand product → Bewerken
  → OF dupliceren → kopie verschijnt direct in lijst
  → OF verwijderen (bevestiging)
```

---

## 5. Meldingen en feedback

- Toast-berichten bij succesvolle acties (opslaan, statuswijziging, etc.)
- Foutmeldingen in de modal bij mislukte AJAX-calls
- Laad-indicatoren op knoppen tijdens verwerking
- Lege staten met meldingstekst als er geen data is

---

## 6. Niet in scope

- Variabele producten aanmaken (alleen eenvoudige producten via Quick Products)
- Bestellingen aanmaken vanuit de plugin
- Koppeling met externe fulfillment-systemen
- Klantbeheer (aanmaken, bewerken, verwijderen van WP-gebruikers)
- Multisite-ondersteuning
