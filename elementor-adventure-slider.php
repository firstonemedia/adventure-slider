<?php
/**
 * Plugin Name: Elementor Adventure Slider
 * Description: Elementor-native branching sliders with named slides, journey history, Back, Restart, arrows, dots, swipe, keyboard navigation, and ordinary Button widget actions.
 * Version:     1.2.0
 * Author:      Tim Doyle
 * License:     GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: elementor-adventure-slider
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Requires Plugins: elementor
 * Elementor tested up to: 4.3.0
 * Elementor Pro tested up to: 4.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EAS_VERSION', '1.2.0' );
define( 'EAS_MINIMUM_ELEMENTOR_VERSION', '3.26.0' );
define( 'EAS_FILE', __FILE__ );
define( 'EAS_PATH', plugin_dir_path( EAS_FILE ) );
define( 'EAS_URL', plugin_dir_url( EAS_FILE ) );

require_once EAS_PATH . 'includes/class-plugin.php';

add_action( 'plugins_loaded', static function () {
	\Elementor_Adventure_Slider\Plugin::instance();
} );
