<?php
namespace Elementor_Adventure_Slider;

use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {
	private static $instance;
	private $bootstrapped = false;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', [ $this, 'load_textdomain' ] );
		add_action( 'admin_notices', [ $this, 'maybe_show_dependency_notice' ] );
		add_action( 'elementor/init', [ $this, 'bootstrap' ], 20 );

		if ( did_action( 'elementor/init' ) ) {
			$this->bootstrap();
		}
	}

	public function bootstrap(): void {
		if ( $this->bootstrapped || ! $this->dependencies_are_ready() ) {
			return;
		}
		$this->bootstrapped = true;

		add_action( 'elementor/elements/categories_registered', [ $this, 'register_category' ] );
		add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
		add_action( 'elementor/frontend/after_register_scripts', [ $this, 'register_frontend_assets' ] );
		add_action( 'elementor/frontend/after_register_styles', [ $this, 'register_frontend_assets' ] );
		add_action( 'elementor/editor/before_enqueue_scripts', [ $this, 'enqueue_editor_assets' ] );

		add_action( 'elementor/element/button/section_button/before_section_end', [ $this, 'add_button_controls' ], 10, 2 );
		add_filter( 'elementor/widget/render_content', [ $this, 'filter_button_action_content' ], 10, 2 );
	}

	public function load_textdomain(): void {
		load_plugin_textdomain(
			'elementor-adventure-slider',
			false,
			dirname( plugin_basename( EAS_FILE ) ) . '/languages'
		);
	}

	private function dependencies_are_ready(): bool {
		return did_action( 'elementor/loaded' )
			&& defined( 'ELEMENTOR_VERSION' )
			&& version_compare( ELEMENTOR_VERSION, EAS_MINIMUM_ELEMENTOR_VERSION, '>=' )
			&& class_exists( '\\Elementor\\Modules\\NestedElements\\Base\\Widget_Nested_Base' )
			&& class_exists( '\\Elementor\\Modules\\NestedElements\\Controls\\Control_Nested_Repeater' );
	}

	public function maybe_show_dependency_notice(): void {
		if ( $this->dependencies_are_ready() || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( ! did_action( 'elementor/loaded' ) ) {
			$message = __( 'Elementor Adventure Slider requires Elementor to be installed and activated.', 'elementor-adventure-slider' );
		} elseif ( defined( 'ELEMENTOR_VERSION' ) && version_compare( ELEMENTOR_VERSION, EAS_MINIMUM_ELEMENTOR_VERSION, '<' ) ) {
			$message = sprintf(
				/* translators: %s: minimum Elementor version. */
				__( 'Elementor Adventure Slider requires Elementor %s or newer.', 'elementor-adventure-slider' ),
				EAS_MINIMUM_ELEMENTOR_VERSION
			);
		} else {
			$message = __( 'Elementor Adventure Slider requires Elementor Nested Elements to be available.', 'elementor-adventure-slider' );
		}

		printf(
			'<div class="notice notice-error"><p><strong>%s</strong> %s</p></div>',
			esc_html__( 'Elementor Adventure Slider:', 'elementor-adventure-slider' ),
			esc_html( $message )
		);
	}

	public function register_category( $elements_manager ): void {
		$elements_manager->add_category(
			'adventure-elements',
			[
				'title' => esc_html__( 'Adventure', 'elementor-adventure-slider' ),
				'icon'  => 'fa fa-map-signs',
			]
		);
	}

	public function register_widgets( $widgets_manager ): void {
		require_once EAS_PATH . 'includes/widgets/class-adventure-slider-widget.php';
		$widgets_manager->register( new Widgets\Adventure_Slider_Widget() );
	}

	public function register_frontend_assets(): void {
		wp_register_style(
			'elementor-adventure-slider',
			EAS_URL . 'assets/css/adventure-slider.css',
			[],
			EAS_VERSION
		);

		wp_register_script(
			'elementor-adventure-slider',
			EAS_URL . 'assets/js/adventure-slider.js',
			[ 'elementor-frontend' ],
			EAS_VERSION,
			true
		);
	}

	public function enqueue_editor_assets(): void {
		$this->register_frontend_assets();
		wp_enqueue_style( 'elementor-adventure-slider' );
		wp_enqueue_script(
			'elementor-adventure-slider-editor',
			EAS_URL . 'assets/js/editor.js',
			[ 'elementor-editor', 'nested-elements' ],
			EAS_VERSION,
			true
		);
	}

	public function add_button_controls( $element, $args ): void {
		unset( $args );

		$element->add_control(
			'eas_adventure_action',
			[
				'label'       => esc_html__( 'Adventure action', 'elementor-adventure-slider' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => [
					''         => esc_html__( 'Use normal link', 'elementor-adventure-slider' ),
					'go'       => esc_html__( 'Go to named slide', 'elementor-adventure-slider' ),
					'back'     => esc_html__( 'Back through journey', 'elementor-adventure-slider' ),
					'restart'  => esc_html__( 'Restart journey', 'elementor-adventure-slider' ),
					'next'     => esc_html__( 'Next slide', 'elementor-adventure-slider' ),
					'previous' => esc_html__( 'Previous slide', 'elementor-adventure-slider' ),
				],
				'default'     => '',
				'separator'   => 'before',
				'description' => esc_html__( 'Works when this Button is placed inside an Adventure Slider slide.', 'elementor-adventure-slider' ),
			]
		);

		$element->add_control(
			'eas_adventure_destination',
			[
				'label'       => esc_html__( 'Destination slide ID', 'elementor-adventure-slider' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'international-first-timer',
				'label_block' => true,
				'ai'          => [ 'active' => false ],
				'description' => esc_html__( 'Choose from the slide buttons shown below. You do not need to copy or guess an ID.', 'elementor-adventure-slider' ),
				'condition'   => [
					'eas_adventure_action' => 'go',
				],
			]
		);
	}

	public function filter_button_action_content( $content, $widget ): string {
		if ( 'button' !== $widget->get_name() ) {
			return $content;
		}

		$action = sanitize_key( (string) $widget->get_settings( 'eas_adventure_action' ) );
		if ( ! in_array( $action, [ 'go', 'back', 'restart', 'next', 'previous' ], true ) ) {
			return $content;
		}

		if ( 'go' === $action ) {
			$destination = sanitize_key( (string) $widget->get_settings( 'eas_adventure_destination' ) );
			if ( '' === $destination ) {
				return $content;
			}
			$target = $destination;
		} else {
			$target = $action;
		}

		$href = '#adventure:' . $target;
		$replacement = ' href="' . esc_attr( $href ) . '" data-adventure-link="true"';
		$updated = preg_replace( '/\shref=("|\').*?\1/', $replacement, $content, 1 );

		if ( is_string( $updated ) && $updated !== $content ) {
			return $updated;
		}

		$updated = preg_replace( '/<a\b/', '<a' . $replacement, $content, 1 );
		return is_string( $updated ) ? $updated : $content;
	}
}
