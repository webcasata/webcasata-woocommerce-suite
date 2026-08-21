=== Webcasata WooCommerce Suite ===
Contributors: webcasata
Tags: woocommerce, product page, product card, cart, quantity
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Requires Plugins: woocommerce
WC requires at least: 8.0
Stable tag: 1.4.0
License: GPLv2 or later

A lightweight, modular WooCommerce enhancement toolkit. Turn on only the features you need — everything else stays completely inactive.

== Description ==

Webcasata WooCommerce Suite replaces several single-purpose WooCommerce plugins with one lightweight toolkit. Every feature is a self-contained module with its own toggle in WooCommerce > Webcasata Suite. If a toggle is off, its PHP, CSS, and JS are never loaded — so enabling 2 of 20 modules costs you the weight of 2 modules, not 20.

= Included in v1.1.0 =

**General**
* Sticky Add to Cart
* Quick View — image, price, stock status, rating, short description, SKU. Simple products get AJAX Add to Cart; variable products get WooCommerce's own native attribute-selector form (live price/stock, AJAX add); external products get a direct "Buy" link; grouped products link to the full product page.

**Product Card**
* Percentage Discount Badge
* Color Attribute Swatches (archive/shop cards)
* Hover Image Swap (second gallery image on hover)
* Auto "New" Badge (configurable day threshold)
* "You Save" Label
* Star Rating + Review Count
* Out of Stock Ribbon
* Installment / EMI Price Hint (configurable installment count)

**Plus / Minus**
* Plus / Minus Quantity Buttons (product, cart, checkout)

Four Product Card badges — Percentage Discount Badge, Auto "New" Badge, "You Save" Label, and Out of Stock Ribbon — have appearance controls (background color, text color, border-radius, font size) in an accordion panel that opens automatically under the toggle when it's switched on, and closes when it's switched off. Two modules (Auto "New" Badge, Installment/EMI Price Hint) also have a small functional field (day threshold, installment count) in the same panel. The panel's open/closed state is rendered server-side to match the saved toggle (no flash of the wrong state on page load); JS only animates the transition when you flip a toggle.

Modules marked "Coming soon" in the admin screen are wired into the settings UI and toggle-ready, so they can be built out and dropped in without touching the settings or admin UI code.

== Activation & Uninstall ==

* **Activation:** the plugin declares `Requires Plugins: woocommerce` in its header. On WordPress 6.5+, core itself disables the Activate link and shows "This plugin cannot be activated because required plugins are missing or inactive" if WooCommerce isn't active — the same native UI WooCommerce extensions on wordpress.org use. It also blocks activation at the code level (WP-CLI included), not just in the UI. As a fallback for sites on WordPress older than 6.5 (where this header isn't enforced), the plugin also hard-checks on its own activation hook and refuses to activate without WooCommerce.
* **Dependency lock:** because of the same header, WordPress prevents WooCommerce from being deactivated or deleted while this plugin is active — its row shows "Required by: Webcasata WooCommerce Suite" and the Deactivate/Delete links are disabled, exactly like the screenshot behavior of other WooCommerce extensions. To deactivate WooCommerce, the admin must deactivate this plugin first.
* **Uninstall:** deleting the plugin from the Plugins screen keeps all saved settings by default, so reinstalling later restores the previous configuration. To remove settings on uninstall instead, check "Delete all Webcasata WooCommerce Suite settings when this plugin is deleted" on the settings screen *before* deleting the plugin. This never touches WooCommerce products, orders, or customer data — only this plugin's own two options (`wwcs_settings`, `wwcs_delete_data_on_uninstall`).

== Developer notes: adding a new module ==

1. Add the feature's label/description to the relevant tab in `includes/class-wwcs-settings.php` (`WWCS_Settings::build_registry()`). If it needs a small config value alongside its toggle, add a `fields` sub-array — supported types are `number` (with an optional `suffix` label like "days" or "px"), `color` (renders a color picker), and plain `text`. Any feature with a non-empty `fields` array automatically gets the accordion panel — no extra admin code needed.
2. For a "badge-style" element (background + text color + border-radius + font-size), give it exactly those four fields named `{your_prefix}_bg_color`, `{your_prefix}_text_color`, `{your_prefix}_border_radius`, `{your_prefix}_font_size`, then call `WWCS_Settings::build_badge_css( '.your-css-class', 'your_prefix', $defaults )` from your module's `enqueue()` and pass the result to `wp_add_inline_style()` — see `class-module-discount-badge.php` for the pattern used by all four current badge modules.
3. Add a `feature_key => [ 'file', 'class' ]` entry to `WWCS_Loader::$module_map` in `includes/class-wwcs-loader.php`.
4. Create `includes/modules/class-module-your-feature.php` with a class that has a static `init()` method — that's the only method the loader calls, so hook everything from there. Read a config field's saved value with `WWCS_Settings::get_field_value( 'field_key', $default )`.
5. Enqueue any CSS/JS from inside your module's own `init()`, conditioned on the relevant page (`is_product()`, `is_shop()`, etc.) — never enqueue globally.

The module only loads at all if its toggle is on, so there's no need to check the setting again inside the module itself.

== Changelog ==

= 1.4.0 =
* Quick View modal now shows stock status and SKU alongside price/rating/short description.
* Variable products now get WooCommerce's own native variation form inside the modal (attribute dropdowns, live price/stock/image updates, AJAX Add to Cart with the selected variation) instead of just a link — reused as-is from WooCommerce core rather than custom-built, so it inherits WooCommerce's own variation-matching logic and styling.
* External/affiliate products now get a direct "Buy" link inside the modal instead of falling through to "View full details".
* Grouped products remain link-only ("View full details & options") — their multi-child-row UI doesn't fit a compact modal well.

= 1.3.0 =
* Added Quick View: a modal on shop/archive pages showing image, price, rating, short description, and Add to Cart, triggered from a button revealed on card hover.
* Simple products add to cart from inside the modal via WooCommerce's own AJAX add-to-cart endpoint (reuses wc-add-to-cart's localization, so mini-cart fragments and the `added_to_cart` event fire exactly as they do for the loop's own Add to Cart button).
* Variable, grouped, and external products show a "View full details & options" link instead of an in-modal cart form — deliberately, to avoid re-initializing WooCommerce's variation-selector JS against content injected after page load, which is where most basic Quick View implementations break.
* Quick View's AJAX endpoint (`wp_ajax_wwcs_quick_view`) is only registered while the Quick View toggle is on, consistent with the rest of the plugin's "off means truly inactive" behavior.

= 1.2.0 =
* Added appearance controls (background color, text color, border-radius, font size) to Discount Badge, Auto "New" Badge, "You Save" Label, and Out of Stock Ribbon, applied via wp_add_inline_style so no page reload is needed to see the effect after saving.
* Added a `color` field type to the settings framework, alongside the existing `number`/`text` types.
* Per-feature config panels are now a real accordion (slide open/closed) tied directly to the feature's toggle, instead of a dimmed static block.
* "You Save" Label now renders as a styled pill/box by default instead of plain text, to match its new background/border-radius options.

= 1.1.0 =
* Added 6 new Product Card modules: Hover Image Swap, Auto "New" Badge, "You Save" Label, Star Rating + Review Count, Out of Stock Ribbon, Installment/EMI Price Hint.
* Extended the settings framework to support small per-feature config fields (number/text) alongside a toggle, with generic admin rendering, saving, and defaulting.
* Star Rating + Review Count module removes WooCommerce's default loop rating hook before adding its own, to avoid a duplicate rating showing on the card.

= 1.0.2 =
* Fixed a bug where saving one tab's toggles (e.g. Product Card) reset every other tab's toggles (e.g. General) back to off. Each tab's form now only recomputes that tab's own settings and leaves all other tabs untouched.

= 1.0.1 =
* Added `Requires Plugins: woocommerce` header — WordPress 6.5+ now natively blocks activation without WooCommerce and locks WooCommerce against deactivation while this plugin is active, matching the standard WooCommerce-extension dependency UI.
* Activation still hard-blocked via code as a fallback on WordPress versions older than 6.5.
* Added uninstall.php + "Delete data on uninstall" checkbox — settings are kept by default when the plugin is deleted.

= 1.0.0 =
* Initial release: toggle framework + Sticky Add to Cart, Plus/Minus buttons, Discount Badge, Color Swatches on card.
