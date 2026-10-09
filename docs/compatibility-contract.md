# Adventure Slider compatibility contract

This document characterizes the current implementation. It is not a new specification. Existing Elementor documents must continue to load with the identifiers and positional structure below unless a migration or alias strategy is introduced.

## Saved Elementor structure

The redacted real fixture is [fixture-defaults.json](../tests/fixtures/fixture-defaults.json), from page 7, and is checked by [compatibility.test.js](../tests/compatibility.test.js). The widget type is `ea-adventure-slider`. Its repeater is `slides`; current slide fields are `slide_title`, `slide_id`, `breadcrumb_label`, and `show_in_heading`. The button fields are `eas_adventure_action` and `eas_adventure_destination` ([includes/class-plugin.php:136-154](../includes/class-plugin.php#L136)).

The saved fixture contains three slides and three nested Elementor child containers. Mapping is positional and exact:

`slides[0]` → child `[0]` (Start), `slides[1]` → child `[1]` (Choice), `slides[2]` → child `[2]` (Result). The widget renders children by the same index ([includes/widgets/class-adventure-slider-widget.php:863-867](../includes/widgets/class-adventure-slider-widget.php#L863)).

## Public rendering and JavaScript

The root class is `ea-adventure-slider`; slide wrappers use `ea-adventure-slide`, `data-adventure-slide`, `data-slide-index`, and ARIA attributes ([includes/widgets/class-adventure-slider-widget.php:871-897](../includes/widgets/class-adventure-slider-widget.php#L871)). Root data attributes include `data-adventure-instance`, `data-initial-slide`, transition, navigation, loop, persistence, and autoplay values ([includes/widgets/class-adventure-slider-widget.php:915-930](../includes/widgets/class-adventure-slider-widget.php#L915)).

The front-end exposes `root.adventureSlider`, dispatches `adventureslider:change`, and includes `detail.slideId` and `detail.previousSlideId` ([assets/js/adventure-slider.js:223-226](../assets/js/adventure-slider.js#L223)). In-document navigation uses `#adventure:<target>`; button actions are rewritten to that syntax by the Elementor filter ([includes/class-plugin.php:170-199](../includes/class-plugin.php#L170)).

Multiple-slider persistence is isolated by the key `eas:<pathname>:<data-adventure-instance>` ([assets/js/adventure-slider.js:46](../assets/js/adventure-slider.js#L46)). The instance ID and the nested child order therefore form compatibility surfaces.

## Current known inconsistencies

PHP uses `sanitize_key()` and its fallback is `slide-(index + 1)` ([includes/widgets/class-adventure-slider-widget.php:823-849](../includes/widgets/class-adventure-slider-widget.php#L823)). The editor replaces invalid characters with hyphens and has an item-template fallback `slide-<zero-based index>` ([assets/js/editor.js:28-31, 1044-1052](../assets/js/editor.js#L28)). The front end keeps canonical IDs unchanged, but may resolve legacy aliases to those canonical IDs. These PHP/editor differences for malformed IDs remain a known limitation for a later, separately tested improvement.

The real defaults fixture explicitly stores its three `slide_title`/`slide_id` pairs and selected accessibility/label settings. It omits `breadcrumb_label`, `show_in_heading`, transition, autoplay, and other controls, so those currently rely on control or runtime defaults.

## Transitions

The `transition` setting continues to accept the stored values `slide`, `fade`, and `none`, and additionally accepts `slide-left`, `slide-right`, `slide-up`, and `slide-down`. Missing or unsupported values render and run as `slide`; no saved Elementor data is migrated. `slide` is horizontal and intentionally now uses the configurable default movement distance of `35%` (previously `12%`) so existing sliders with an omitted distance have a visibly stronger movement. Existing sliders that explicitly saved a distance keep that value.

For all slide modes, forward navigation uses the named direction: `slide`/`slide-left` moves left (the incoming slide enters from the right), `slide-right` moves right, `slide-up` moves up, and `slide-down` moves down. Back reverses that direction. Direct `#adventure:<id>` links, Button-generated targets, heading/dot targets, and autoplay are forward; Back, breadcrumbs, restart, Previous, and negative relative navigation are back. Initial selection and restored session history do not animate. Fade remains opacity-only, and None, zero speed, reduced motion, editor mode, or missing `Element.animate()` finish immediately.

Slide transitions retain their existing opacity cross-fade while adding the stronger directional transform. The slide stack remains grid-overlapped and clipped by CSS. A monotonically increasing transition token prevents a completion from a cancelled older animation from finalizing a newer navigation.

## Compatibility-safe ID resolution

The canonical ID remains the PHP `sanitize_key()` result, or the existing deterministic `slide-(index + 1)` fallback. The renderer now adds `data-adventure-aliases` only when a legacy spelling needs compatibility. Aliases include the lowercased raw ID, the editor-style punctuation replacement, and the old zero-based missing-ID fallback where applicable. Canonical IDs always take precedence; the first claimed alias wins; duplicate canonical IDs retain the existing fallback and never overwrite an earlier target. The alias metadata is additive and stored Elementor data is not rewritten.

The front end resolves direct `#adventure:<target>` links, Button-rewritten targets, and initial IDs through the alias map while retaining canonical IDs in `data-adventure-slide`, history, events, and session storage. Values restored from sessionStorage are resolved to canonical IDs in memory; invalid values are discarded and duplicate canonical values are retained once. Canonical IDs take precedence over aliases, and the first claimed alias wins. Editor child wrappers receive the same additive alias metadata. No Elementor data is rewritten. Relevant implementation points are `includes/widgets/class-adventure-slider-widget.php:823-889`, `assets/js/editor.js:28-46, 57-88`, and `assets/js/adventure-slider.js:21-98, 134-160, 380-405`.

## Baseline command

`node tests/compatibility.test.js`

The script uses only Node built-ins. It performs static characterization, live HTTP/asset checks against page 7 when the isolated site is running, and a Chromium dump-DOM check. It does not modify files or data. Interactive navigation is not exercised because no CDP automation dependency is installed.

The live check observed HTTP 200, the widget root and expected data/ARIA markers, and four Elementor/plugin asset URLs returning successfully. The current live page response contains more slide headings than the copied defaults fixture; this is recorded as an environment/document-state distinction, not normalized by the tests.
