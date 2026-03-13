# Product Haven Roadmap

This roadmap is intended as a practical planning document for future iterations of `Product Haven`.
It focuses on two things:

- **features worth considering later**
- **areas that could be polished or made more release-ready**

The goal is not to add everything, but to help choose improvements that increase usefulness without turning the plugin into an overcomplicated maintenance burden.

---

## Current Positioning

Product Haven already has a strong foundation:

- live WooCommerce dashboard
- stock management
- quick product editing
- sequential order numbers
- Elementor frontend widgets
- optional REST API

That is already enough for a first public release.

The next iterations should focus on **clarity, polish, and a few high-value workflow improvements**, rather than adding too many unrelated features.

---

## Guiding Product Direction

A good long-term identity for Product Haven is:

> A WooCommerce operations dashboard for store owners who want faster access to orders, stock, products, and store insights.

This means future improvements should preferably strengthen one of these areas:

- order workflow
- stock workflow
- store overview and reporting
- admin efficiency
- cleaner merchant experience

Features that pull the plugin too far into CRM, marketing automation, or enterprise ERP territory should probably be avoided unless there is a clear plan for that direction.

---

## Recommended Release Order

### Phase 1 — Public release polish

These are the improvements that are most worth doing before or shortly after publishing.

#### 1. English user-facing copy
**Priority:** High  
**Why it matters:** The plugin is already documented in English, but a lot of the admin and frontend interface text is still Dutch. For a public GitHub or WordPress.org release, English UI text will make the plugin feel much more complete and accessible.

**Examples:**
- AJAX error messages
- admin labels and buttons
- widget labels
- dashboard text
- sequential order labels
- stock action reasons/default texts

#### 2. Screenshot and presentation assets
**Priority:** High  
**Why it matters:** Good screenshots often do more for adoption than one extra feature.

**Suggested assets:**
- admin dashboard overview
- order timeline modal
- stock management panel
- quick products editor
- sequential order number settings
- Elementor widgets on the frontend

#### 3. Final UX polish pass
**Priority:** High  
**Why it matters:** Small UX improvements often create more value than feature growth.

**Examples:**
- better empty states
- clearer success/error feedback
- more consistent button labels
- more readable table/filter states
- improved spacing and grouping in settings

#### 4. Broader smoke testing
**Priority:** High  
**Why it matters:** A public release should feel stable on a clean WooCommerce install.

**Suggested checks:**
- plugin activation with WooCommerce active
- plugin behaviour with WooCommerce inactive
- settings save flow
- AJAX endpoints
- stock updates
- quick product create/edit flow
- sequential order numbering on new orders
- uninstall behaviour

---

## Phase 2 — High-value feature improvements

These are the best future features because they match the plugin well and should add clear value without introducing huge support burden.

### 1. Saved filters and custom views
**Priority:** Very High  
**Estimated value:** High  
**Support burden:** Low to medium

Allow users to save common dashboard views, such as:

- pending + processing orders
- low stock under a custom threshold
- orders from the last 7 days
- top products in the last 30 days
- high-value customers

**Why this is a strong addition:**
- fits your current dashboard model perfectly
- improves speed for merchants
- feels more advanced without being hard to explain

### 2. Scheduled summary emails or admin digests
**Priority:** High  
**Estimated value:** High  
**Support burden:** Low

Examples:
- daily store summary
- weekly revenue summary
- low-stock digest
- top-products digest

**Why this is a strong addition:**
- useful for merchants who do not log in constantly
- easy to position in the plugin description
- good perceived value for relatively controlled scope

### 3. Activity log / audit history
**Priority:** High  
**Estimated value:** High  
**Support burden:** Medium

Possible log items:
- stock changes
- quick product edits
- manual order status changes
- order note additions
- sequential counter resets

**Why this is a strong addition:**
- useful for teams and store managers
- increases trust and traceability
- fits naturally with your operations focus

### 4. Better stock actions
**Priority:** High  
**Estimated value:** High  
**Support burden:** Medium

Possible additions:
- one-click restock amounts
- “mark for reorder” status
- restock notes or supplier notes
- low-stock sorting by recent sales velocity

**Why this is a strong addition:**
- direct store value
- strengthens a major existing part of the plugin
- more useful than adding something unrelated

### 5. Enhanced customer insights card
**Priority:** Medium  
**Estimated value:** Medium to high  
**Support burden:** Low to medium

Potential additions:
- lifetime value
- average order size
- last order date
- total refund amount
- top purchased categories
- simple new vs returning customer badge

**Why this is worth considering:**
- builds on something already present
- creates stronger “store intelligence” value
- useful without changing plugin identity

---

## Phase 3 — Nice-to-have additions

These are useful ideas, but they should come after polish and the higher-value items above.

### 1. Export presets
Allow users to quickly export predefined datasets such as:

- orders for the selected date range
- refunded orders
- low-stock products
- top customers
- product stock history

**Why useful:** practical, clear, low-friction feature.

### 2. Admin dashboard widgets
Add smaller summary widgets to the default WordPress dashboard, for example:

- today’s revenue
- pending orders
- low-stock count
- recent activity

**Why useful:** good visibility, fairly simple concept.

### 3. More granular REST API controls
If the REST API becomes more important later, you could add:

- endpoint toggles
- API access settings
- improved documentation
- more export/reporting endpoints

**Why useful:** helpful for developers, but lower priority than merchant UX.

### 4. Better onboarding / first-run guidance
Potential additions:

- welcome panel
- setup checklist
- feature highlights after activation

**Why useful:** improves first impression, especially for public releases.

---

## Things That Would Make the Plugin Feel More Professional

These are not necessarily “headline features,” but they could improve the quality of the plugin significantly.

### 1. Consistent language and translation readiness
- convert the remaining Dutch UI strings to English
- keep all user-facing strings wrapped in translation functions
- make sure wording is consistent across admin, frontend, and widgets

### 2. Cleaner capability strategy
A review of capabilities could make access management more consistent.

Examples to evaluate:
- where `manage_woocommerce` is used
- where `manage_options` is used
- whether some actions should be more granular later

### 3. Better fallback handling for dependencies
Make sure the plugin behaves cleanly when:

- WooCommerce is inactive
- Elementor is inactive
- REST API is disabled
- optional integrations are unavailable

### 4. More consistent notices and feedback messages
Examples:
- consistent success notice language
- clearer error phrasing
- more helpful empty-state text
- confirmation prompts for destructive actions

### 5. Better visual consistency
Examples:
- spacing consistency in admin panels
- card and tab styling consistency
- more predictable button hierarchy
- more readable mobile admin layouts if relevant

### 6. Changelog and release discipline
Keep releases tidy with:

- versioned changelog updates
- release notes on GitHub
- screenshot updates when big UI changes happen

---

## Things Probably Not Worth Adding Soon

These ideas are not inherently bad, but they are likely lower-value or higher-maintenance for the current direction of Product Haven.

### 1. Full CRM features
Examples:
- customer segmentation systems
- campaigns
- lifecycle marketing
- email automations

**Why avoid for now:** moves the plugin too far away from its current purpose.

### 2. AI-heavy forecasting
Examples:
- demand forecasting
- smart order recommendations
- restock prediction engines

**Why avoid for now:** higher complexity, harder to explain, more support risk.

### 3. Too many third-party integrations
Examples:
- HubSpot
- Mailchimp
- Zapier
- multiple page builders
- ERP connectors

**Why avoid for now:** support burden increases quickly and maintenance becomes harder.

### 4. Rebuilding full WooCommerce admin features
**Why avoid for now:** can create overlap without adding enough unique value.

---

## Suggested Practical Roadmap

### Version 1.4 TODO

This should stay intentionally small and realistic.

The goal for `v1.4` is **not** to add lots of features, but to make Product Haven feel more polished and public-release ready.

#### Core TODO

- [x] Convert the most visible user-facing UI text from Dutch to English
- [ ] Improve the main admin empty states and feedback messages
- [ ] Add a small set of presentation screenshots for GitHub / WordPress.org
- [ ] Do one full smoke-test pass on a clean WordPress + WooCommerce install
- [ ] Review dependency fallbacks for WooCommerce and Elementor one more time

#### If there is still time

- [ ] Clean up wording consistency across admin tabs and widgets
- [ ] Improve a few high-impact labels, buttons, and notices for better UX clarity

#### Explicitly not in v1.4

To avoid over-scoping, these should stay out of this release:

- saved filters / custom views
- scheduled summary digests
- activity log
- advanced stock workflow additions
- richer customer insights

### Version 1.4
Focus on polish and public-readiness.

**Suggested scope:**
- convert key user-facing text to English
- improve empty states and admin feedback
- create screenshots/assets
- do a release testing pass
- tighten public-facing documentation where needed

### Version 1.5
Add one workflow feature with strong merchant value.

**Suggested scope:**
- saved filters / custom views
- or scheduled summary digests

### Version 1.6
Strengthen traceability and store operations.

**Suggested scope:**
- activity log
- improved stock actions

### Version 2.0
Only if the plugin gains traction and you want to deepen the product.

**Suggested scope:**
- richer customer insights
- better REST API controls
- stronger onboarding
- more refined reporting/exports

---

## Best Next Step

If only one thing should happen next, the best choice is:

**Make the plugin interface feel internationally release-ready before adding more features.**

That means:
- English UI text
- stronger polish
- screenshots
- small UX cleanup

After that, the strongest feature candidate is:

**saved filters / custom views**

because it adds real merchant value while fitting naturally into the current product.

---

## Simple Decision Filter for Future Ideas

Before adding any new feature, ask:

1. Does this help a WooCommerce store owner work faster?
2. Does it fit the operations/dashboard identity of Product Haven?
3. Can it be explained simply on a plugin page?
4. Will it create a lot of support burden?
5. Is it more valuable than improving polish or stability?

If the answer is mostly “yes,” it is probably worth considering.
