=== Elementor Adventure Slider ===
Contributors: timdoyle
Tags: elementor, slider, carousel, branching, choose your own adventure
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Create completely Elementor-native branching sliders. Every slide is a real Elementor container and ordinary Button widgets can jump to named slides.

== Description ==

Adventure Slider adds a nested Elementor widget for decision trees, ticket choosers, guided journeys, quizzes and choose-your-own-adventure interfaces.

Every slide is a normal Elementor container. Add headings, images, video, buttons, forms, nested containers, motion effects and responsive styling exactly as you would elsewhere in Elementor.

Features:

* Named slide IDs that remain meaningful when slides are reordered.
* Go to slide, Back through actual journey history, Restart, Next and Previous actions.
* Adventure controls added to Elementor's classic Button widget.
* Clickable, fully styleable heading buttons for switching and editing slides.
* Optional footer breadcrumbs that follow the visitor's actual route.
* A visible destination chooser for Buttons placed inside the slider.
* Manual `#adventure:slide-id` links for other link-capable widgets.
* Optional arrows, pagination dots, progress, swipe, keyboard navigation and autoplay.
* Slide and fade transitions with reduced-motion support.
* Optional session-only path restoration after reload.
* Multiple independent sliders on one page.
* No AJAX, REST routes, custom tables, cookies, remote requests or unauthenticated writes.

== Installation ==

1. Install and activate Elementor 3.26 or newer.
2. Upload and activate this plugin.
3. In Elementor, drag the Adventure Slider widget onto a page.
4. Add slides and give each one a unique Slide ID.
5. Place an Elementor Button inside a slide and choose its Adventure action.

== Frequently Asked Questions ==

= Can I link widgets other than the classic Button? =

Yes. Set the widget's URL to `#adventure:destination-id`. The special destinations are `back`, `restart`, `next` and `previous`.

= Does this require Elementor Pro? =

No. It relies on Elementor's stable Nested Elements infrastructure available in current free Elementor releases.

= Is visitor data sent anywhere? =

No. The plugin has no frontend network endpoints. Optional path restoration stores only the slider's named slide IDs in sessionStorage for the current browser tab.

== Changelog ==

= 1.3.0 =
* Added compatibility-safe legacy slide ID aliases without rewriting existing Elementor data.
* Improved editor performance when editing slide titles, IDs and breadcrumb labels.
* Stabilized transition height changes between slides.
* Polished responsive and keyboard-focus navigation.
* Corrected autoplay pause and resume behaviour for hover, focus and completed transitions.
* Preserved compatibility with existing Elementor sliders and their public contracts.

= 1.2.0 =
* Fixed slide metadata in Elementor 4.3 so canvas buttons select the correct named slide.
* Fixed Navigator selection for slide containers and widgets nested inside hidden slides.
* Kept the active heading, progress, navigation state and editor breadcrumbs synchronized.
* Added an always-visible editor label showing the selected slide title and ID.
* Made the Button destination chooser refresh when Adventure action changes and mark the selected destination.

= 1.1.0 =
* Added front-end heading navigation with normal, hover and active styling controls.
* Added mouse-based slide selection in the Elementor canvas and Navigator-aware slide revealing.
* Added optional clickable route breadcrumbs in the footer with full styling controls.
* Added a slide-choice list to Elementor Button destination controls.

= 1.0.0 =
* Initial release.
