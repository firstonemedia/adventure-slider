# Elementor Adventure Slider

An Elementor-native branching slider for ticket choosers, decision trees, guided journeys, quizzes and choose-your-own-adventure interfaces.

## Build a journey

1. Add **Adventure Slider** from Elementor's **Adventure** category.
2. Add, duplicate or remove slides in the widget's **Slides** repeater.
3. Give every slide a unique, stable ID such as `start`, `international`, `first-timer`, or `ticket-result`.
4. Design each slide as a normal Elementor container. Nothing visual is imposed by the plugin.
5. Add an ordinary Elementor Button inside a slide.
6. In that Button's content controls, set **Adventure action** to **Go to named slide**, then click the destination in the **Choose a slide** list. No ID copying is required.

## Editing slides

Click a heading button directly in the Elementor canvas to reveal that slide and open its child container for editing. Or expand **Adventure Slider** in **Structure** and select a slide container—or any widget inside it. The canvas shows an **Editing slide** label with the current title and ID, and both routes stay in sync.

The heading buttons can be shown to visitors or kept editor-only. Each slide can also be excluded from the public heading navigation without making it inaccessible in the editor.

You can also type these links into any Elementor widget that accepts a URL:

```text
#adventure:international
#adventure:back
#adventure:restart
#adventure:next
#adventure:previous
```

Links affect only the closest Adventure Slider, so multiple sliders can safely appear on the same page.

## Journey history

Back and the optional footer breadcrumbs follow the route the visitor actually took. If their path was `start → international → first-timer`, Back returns to `international`, then `start`; it does not simply subtract one from the slide number.

Restart clears that route and returns to the configured initial slide.

## Styling

Slide content is styled with normal Elementor controls because every slide is a real nested container. The widget Style tab controls the heading buttons, footer breadcrumbs, built-in navigation, pagination dots, progress and movement distance. Heading buttons include layout, spacing, typography, borders, radius, padding, and separate normal, hover/focus and active colours.

For complete visual freedom, hide the built-in navigation and place your own Elementor Buttons inside the slides.

## Accessibility

The widget:

* exposes carousel and slide semantics;
* keeps inactive slides out of the focus order;
* moves focus into the new slide after visitor navigation;
* provides a polite live-region announcement;
* supports optional keyboard arrows;
* honours `prefers-reduced-motion`;
* disables unavailable Back, Previous and Next controls.

## Security model

The frontend is static HTML, CSS and JavaScript. The plugin registers no AJAX actions, REST routes, uploads, database tables, remote requests or public write operations. Slide identifiers are normalised server-side, rendered values are escaped, and JavaScript accepts only lowercase alphanumeric IDs plus hyphens and underscores. User values are never executed as JavaScript, HTML or CSS selectors.

## Requirements

* WordPress 6.2 or newer
* PHP 7.4 or newer
* Elementor 3.26 or newer with Nested Elements available

Tested in a live WordPress 7.1.2 installation with Elementor 4.3.0.
