=== Webcasata WooCommerce Suite ===
Contributors: webcasata
Tags: woocommerce, product page, product card, cart, quantity
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Requires Plugins: woocommerce
WC requires at least: 8.0
Stable tag: 1.1.0
License: GPLv2 or later

A lightweight, modular WooCommerce enhancement toolkit. Turn on only the features you need — everything else stays completely inactive.

== Description ==

Webcasata WooCommerce Suite replaces several single-purpose WooCommerce plugins with one lightweight toolkit. Every feature is a self-contained module with its own toggle in WooCommerce > Webcasata Suite. If a toggle is off, its PHP, CSS, and JS are never loaded — so enabling 2 of 20 modules costs you the weight of 2 modules, not 20.

= Included in v1.1.0 =

**General**
* Sticky Add to Cart

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

Two Product Card modules (Auto "New" Badge, Installment/EMI Price Hint) have a small config field next to their toggle — e.g. how many days count as "new". Fields are greyed out and disabled via JS while their feature is off, and the backend only ever overwrites a field's saved value when it's actually submitted, so toggling a feature off and back on never resets your configured number back to the default.

Modules marked "Coming soon" in the admin screen are wired into the settings UI and toggle-ready, so they can be built out and dropped in without touching the settings or admin UI code.

== Activation & Uninstall ==

* **Activation:** the plugin declares `Requires Plugins: woocommerce` in its header. On WordPress 6.5+, core itself disables the Activate link and shows "This plugin cannot be activated because required plugins are missing or inactive" if WooCommerce isn't active — the same native UI WooCommerce extensions on wordpress.org use. It also blocks activation at the code level (WP-CLI included), not just in the UI. As a fallback for sites on WordPress older than 6.5 (where this header isn't enforced), the plugin also hard-checks on its own activation hook and refuses to activate without WooCommerce.
* **Dependency lock:** because of the same header, WordPress prevents WooCommerce from being deactivated or deleted while this plugin is active — its row shows "Required by: Webcasata WooCommerce Suite" and the Deactivate/Delete links are disabled, exactly like the screenshot behavior of other WooCommerce extensions. To deactivate WooCommerce, the admin must deactivate this plugin first.
* **Uninstall:** deleting the plugin from the Plugins screen keeps all saved settings by default, so reinstalling later restores the previous configuration. To remove settings on uninstall instead, check "Delete all Webcasata WooCommerce Suite settings when this plugin is deleted" on the settings screen *before* deleting the plugin. This never touches WooCommerce products, orders, or customer data — only this plugin's own two options (`wwcs_settings`, `wwcs_delete_data_on_uninstall`).

== Developer notes: adding a new module ==

1. Add the feature's label/description to the relevant tab in `includes/class-wwcs-settings.php` (`WWCS_Settings::build_registry()`). If it needs a small config value alongside its toggle (a number or short text, e.g. a day threshold), add a `fields` sub-array — see `auto_new_badge` or `emi_price_hint` for the pattern. The admin UI, saving, and defaulting are all handled generically from this one array; no other admin code needs to change.
2. Add a `feature_key => [ 'file', 'class' ]` entry to `WWCS_Loader::$module_map` in `includes/class-wwcs-loader.php`.
3. Create `includes/modules/class-module-your-feature.php` with a class that has a static `init()` method — that's the only method the loader calls, so hook everything from there. Read a config field's saved value with `WWCS_Settings::get_field_value( 'field_key', $default )`.
4. Enqueue any CSS/JS from inside your module's own `init()`, conditioned on the relevant page (`is_product()`, `is_shop()`, etc.) — never enqueue globally.

The module only loads at all if its toggle is on, so there's no need to check the setting again inside the module itself.

== Changelog ==

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
