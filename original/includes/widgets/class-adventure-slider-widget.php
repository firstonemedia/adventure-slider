<?php
namespace Elementor_Adventure_Slider\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Modules\NestedElements\Base\Widget_Nested_Base;
use Elementor\Modules\NestedElements\Controls\Control_Nested_Repeater;
use Elementor\Plugin;
use Elementor\Repeater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Adventure_Slider_Widget extends Widget_Nested_Base {
	private $slides_for_render = [];

	public function get_name() {
		return 'ea-adventure-slider';
	}

	public function get_title() {
		return esc_html__( 'Adventure Slider', 'elementor-adventure-slider' );
	}

	public function get_icon() {
		return 'eicon-slider-push';
	}

	public function get_categories() {
		return [ 'adventure-elements' ];
	}

	public function get_keywords() {
		return [ 'slider', 'carousel', 'adventure', 'branching', 'journey', 'chooser', 'quiz' ];
	}

	public function get_style_depends(): array {
		return [ 'elementor-adventure-slider' ];
	}

	public function get_script_depends(): array {
		return [ 'elementor-adventure-slider' ];
	}

	public function show_in_panel(): bool {
		return Plugin::$instance->experiments->is_feature_active( 'nested-elements', true );
	}

	protected function get_default_children_elements() {
		return [
			$this->default_slide_container( __( 'Start', 'elementor-adventure-slider' ) ),
			$this->default_slide_container( __( 'Choice', 'elementor-adventure-slider' ) ),
			$this->default_slide_container( __( 'Result', 'elementor-adventure-slider' ) ),
		];
	}

	private function default_slide_container( string $title ): array {
		return [
			'elType'   => 'container',
			'settings' => [
				'_title'        => $title,
				'content_width' => 'full',
				'flex_direction' => 'column',
			],
		];
	}

	protected function get_default_repeater_title_setting_key() {
		return 'slide_title';
	}

	protected function get_default_children_title() {
		/* translators: %d: slide number. */
		return esc_html__( 'Slide #%d', 'elementor-adventure-slider' );
	}

	protected function get_default_children_placeholder_selector() {
		return '.ea-adventure-slider__slides';
	}

	protected function get_html_wrapper_class() {
		return 'elementor-widget-ea-adventure-slider';
	}

	protected function get_initial_config(): array {
		return array_merge(
			parent::get_initial_config(),
			[
				'support_improved_repeaters' => true,
				'target_container'           => [ '.ea-adventure-slider__editor-tabs' ],
				'node'                       => 'button',
			]
		);
	}

	protected function register_controls() {
		$this->register_slides_controls();
		$this->register_behaviour_controls();
		$this->register_navigation_controls();
		$this->register_layout_style_controls();
		$this->register_heading_style_controls();
		$this->register_navigation_style_controls();
		$this->register_breadcrumb_style_controls();
		$this->register_dots_style_controls();
		$this->register_progress_style_controls();
	}

	private function register_slides_controls(): void {
		$this->start_controls_section(
			'section_slides',
			[ 'label' => esc_html__( 'Slides', 'elementor-adventure-slider' ) ]
		);

		$repeater = new Repeater();
		$repeater->add_control(
			'slide_title',
			[
				'label'       => esc_html__( 'Editor label', 'elementor-adventure-slider' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Slide', 'elementor-adventure-slider' ),
				'label_block' => true,
			]
		);
		$repeater->add_control(
			'slide_id',
			[
				'label'       => esc_html__( 'Slide ID', 'elementor-adventure-slider' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => 'international-first-timer',
				'label_block' => true,
				'ai'          => [ 'active' => false ],
				'description' => esc_html__( 'Lowercase letters, numbers, hyphens and underscores. Keep this stable after linking buttons to it.', 'elementor-adventure-slider' ),
			]
		);
		$repeater->add_control(
			'breadcrumb_label',
			[
				'label'       => esc_html__( 'Breadcrumb label', 'elementor-adventure-slider' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__( 'Uses the editor label when empty', 'elementor-adventure-slider' ),
				'label_block' => true,
			]
		);
		$repeater->add_control(
			'show_in_heading',
			[
				'label'        => esc_html__( 'Show heading button', 'elementor-adventure-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'slides',
			[
				'label'       => esc_html__( 'Adventure slides', 'elementor-adventure-slider' ),
				'type'        => Control_Nested_Repeater::CONTROL_TYPE,
				'fields'      => $repeater->get_controls(),
				'default'     => [
					[ 'slide_title' => esc_html__( 'Start', 'elementor-adventure-slider' ), 'slide_id' => 'start', 'show_in_heading' => 'yes' ],
					[ 'slide_title' => esc_html__( 'Choice', 'elementor-adventure-slider' ), 'slide_id' => 'choice', 'show_in_heading' => 'yes' ],
					[ 'slide_title' => esc_html__( 'Result', 'elementor-adventure-slider' ), 'slide_id' => 'result', 'show_in_heading' => 'yes' ],
				],
				'title_field' => '{{{ slide_title }}} — {{{ slide_id }}}',
				'button_text' => esc_html__( 'Add slide', 'elementor-adventure-slider' ),
			]
		);

		$this->add_control(
			'editing_help',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => wp_kses_post( __( '<strong>Editing slides:</strong> click a heading button directly on the canvas, or expand Adventure Slider in Structure and select any slide or widget inside it. The canvas label always shows which slide you are editing.', 'elementor-adventure-slider' ) ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			]
		);

		$this->add_control(
			'initial_slide',
			[
				'label'       => esc_html__( 'Initial slide ID', 'elementor-adventure-slider' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'start',
				'label_block' => true,
				'ai'          => [ 'active' => false ],
				'description' => esc_html__( 'Falls back to the first slide if the ID does not exist.', 'elementor-adventure-slider' ),
			]
		);

		$this->add_control(
			'link_help',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => wp_kses_post( __( '<strong>Link any Elementor Button:</strong> choose an Adventure action in the Button panel, or use <code>#adventure:slide-id</code>. Special targets are <code>back</code>, <code>restart</code>, <code>next</code>, and <code>previous</code>.', 'elementor-adventure-slider' ) ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			]
		);

		$this->end_controls_section();
	}

	private function register_behaviour_controls(): void {
		$this->start_controls_section(
			'section_behaviour',
			[ 'label' => esc_html__( 'Behaviour', 'elementor-adventure-slider' ) ]
		);

		$this->add_control(
			'transition',
			[
				'label'   => esc_html__( 'Transition', 'elementor-adventure-slider' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'slide',
				'options' => [
					'slide' => esc_html__( 'Slide', 'elementor-adventure-slider' ),
					'fade'  => esc_html__( 'Fade', 'elementor-adventure-slider' ),
					'none'  => esc_html__( 'None', 'elementor-adventure-slider' ),
				],
			]
		);

		$this->add_control(
			'transition_speed',
			[
				'label'   => esc_html__( 'Speed (ms)', 'elementor-adventure-slider' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 350,
				'min'     => 0,
				'max'     => 3000,
				'step'    => 25,
			]
		);

		$this->add_control(
			'allow_swipe',
			[
				'label'        => esc_html__( 'Touch swipe', 'elementor-adventure-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'keyboard_navigation',
			[
				'label'        => esc_html__( 'Keyboard arrows', 'elementor-adventure-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'loop',
			[
				'label'        => esc_html__( 'Loop Next/Previous', 'elementor-adventure-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'persist_path',
			[
				'label'        => esc_html__( 'Restore path after reload', 'elementor-adventure-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'description'  => esc_html__( 'Stores only slide IDs in this browser tab. No cookies or server writes.', 'elementor-adventure-slider' ),
			]
		);

		$this->add_control(
			'autoplay',
			[
				'label'        => esc_html__( 'Autoplay', 'elementor-adventure-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'description'  => esc_html__( 'Usually leave this off for branching journeys.', 'elementor-adventure-slider' ),
			]
		);

		$this->add_control(
			'autoplay_delay',
			[
				'label'     => esc_html__( 'Autoplay delay (ms)', 'elementor-adventure-slider' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 5000,
				'min'       => 1000,
				'max'       => 60000,
				'step'      => 250,
				'condition' => [ 'autoplay' => 'yes' ],
			]
		);

		$this->add_control(
			'pause_on_hover',
			[
				'label'        => esc_html__( 'Pause on hover/focus', 'elementor-adventure-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'condition'    => [ 'autoplay' => 'yes' ],
			]
		);

		$this->end_controls_section();
	}

	private function register_navigation_controls(): void {
		$this->start_controls_section(
			'section_navigation',
			[ 'label' => esc_html__( 'Navigation', 'elementor-adventure-slider' ) ]
		);

		foreach ( [
			'show_heading_navigation' => [ __( 'Show heading buttons', 'elementor-adventure-slider' ), 'yes' ],
			'show_back'     => [ __( 'Show Back', 'elementor-adventure-slider' ), 'yes' ],
			'show_restart'  => [ __( 'Show Restart', 'elementor-adventure-slider' ), 'yes' ],
			'show_arrows'   => [ __( 'Show Previous/Next', 'elementor-adventure-slider' ), '' ],
			'show_dots'     => [ __( 'Show pagination dots', 'elementor-adventure-slider' ), '' ],
			'show_progress' => [ __( 'Show slide progress', 'elementor-adventure-slider' ), '' ],
		] as $name => $config ) {
			$this->add_control(
				$name,
				[
					'label'        => esc_html( $config[0] ),
					'type'         => Controls_Manager::SWITCHER,
					'default'      => $config[1],
					'return_value' => 'yes',
				]
			);
		}

		$this->add_control( 'heading_accessibility_label', [
			'label' => esc_html__( 'Heading buttons label', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::TEXT,
			'default' => esc_html__( 'Choose a slide', 'elementor-adventure-slider' ),
			'label_block' => true,
			'condition' => [ 'show_heading_navigation' => 'yes' ],
		] );

		$this->add_control( 'show_breadcrumbs', [
			'label' => esc_html__( 'Show footer breadcrumbs', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
			'return_value' => 'yes',
		] );
		$this->add_control( 'breadcrumbs_clickable', [
			'label' => esc_html__( 'Clickable previous steps', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
			'return_value' => 'yes',
			'condition' => [ 'show_breadcrumbs' => 'yes' ],
		] );
		$this->add_control( 'breadcrumb_separator', [
			'label' => esc_html__( 'Breadcrumb separator', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::TEXT,
			'default' => '›',
			'condition' => [ 'show_breadcrumbs' => 'yes' ],
		] );
		$this->add_control( 'breadcrumb_accessibility_label', [
			'label' => esc_html__( 'Breadcrumbs label', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::TEXT,
			'default' => esc_html__( 'Your journey', 'elementor-adventure-slider' ),
			'label_block' => true,
			'condition' => [ 'show_breadcrumbs' => 'yes' ],
		] );

		$this->add_control( 'back_label', [
			'label' => esc_html__( 'Back label', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::TEXT,
			'default' => esc_html__( 'Back', 'elementor-adventure-slider' ),
			'condition' => [ 'show_back' => 'yes' ],
		] );
		$this->add_control( 'restart_label', [
			'label' => esc_html__( 'Restart label', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::TEXT,
			'default' => esc_html__( 'Restart', 'elementor-adventure-slider' ),
			'condition' => [ 'show_restart' => 'yes' ],
		] );
		$this->add_control( 'previous_label', [
			'label' => esc_html__( 'Previous label', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::TEXT,
			'default' => esc_html__( 'Previous', 'elementor-adventure-slider' ),
			'condition' => [ 'show_arrows' => 'yes' ],
		] );
		$this->add_control( 'next_label', [
			'label' => esc_html__( 'Next label', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::TEXT,
			'default' => esc_html__( 'Next', 'elementor-adventure-slider' ),
			'condition' => [ 'show_arrows' => 'yes' ],
		] );

		$this->add_control(
			'accessibility_label',
			[
				'label'       => esc_html__( 'Accessible label', 'elementor-adventure-slider' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Interactive journey', 'elementor-adventure-slider' ),
				'label_block' => true,
			]
		);

		$this->end_controls_section();
	}

	private function register_layout_style_controls(): void {
		$this->start_controls_section(
			'section_layout_style',
			[
				'label' => esc_html__( 'Slider', 'elementor-adventure-slider' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'min_height',
			[
				'label'      => esc_html__( 'Minimum height', 'elementor-adventure-slider' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'vh', 'rem', 'em', 'custom' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 1200 ],
					'vh' => [ 'min' => 0, 'max' => 100 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .ea-adventure-slider__slides' => 'min-height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'transition_distance',
			[
				'label'      => esc_html__( 'Slide movement', 'elementor-adventure-slider' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ '%', 'px' ],
				'default'    => [ 'size' => 12, 'unit' => '%' ],
				'selectors'  => [
					'{{WRAPPER}} .ea-adventure-slider' => '--ea-transition-distance: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	private function register_heading_style_controls(): void {
		$this->start_controls_section(
			'section_heading_style',
			[
				'label' => esc_html__( 'Heading buttons', 'elementor-adventure-slider' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control( 'heading_direction', [
			'label' => esc_html__( 'Direction', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::CHOOSE,
			'options' => [
				'row' => [ 'title' => esc_html__( 'Row', 'elementor-adventure-slider' ), 'icon' => 'eicon-arrow-right' ],
				'column' => [ 'title' => esc_html__( 'Column', 'elementor-adventure-slider' ), 'icon' => 'eicon-arrow-down' ],
			],
			'default' => 'row',
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__heading' => 'flex-direction: {{VALUE}};' ],
		] );
		$this->add_responsive_control( 'heading_alignment', [
			'label' => esc_html__( 'Alignment', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::CHOOSE,
			'options' => [
				'flex-start' => [ 'title' => esc_html__( 'Start', 'elementor-adventure-slider' ), 'icon' => 'eicon-text-align-left' ],
				'center' => [ 'title' => esc_html__( 'Centre', 'elementor-adventure-slider' ), 'icon' => 'eicon-text-align-center' ],
				'flex-end' => [ 'title' => esc_html__( 'End', 'elementor-adventure-slider' ), 'icon' => 'eicon-text-align-right' ],
				'space-between' => [ 'title' => esc_html__( 'Space between', 'elementor-adventure-slider' ), 'icon' => 'eicon-justify-space-between-h' ],
			],
			'default' => 'flex-start',
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__heading' => 'justify-content: {{VALUE}};' ],
		] );
		$this->add_responsive_control( 'heading_gap', [
			'label' => esc_html__( 'Gap', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'rem', 'em' ],
			'range' => [ 'px' => [ 'min' => 0, 'max' => 100 ] ],
			'default' => [ 'size' => 8, 'unit' => 'px' ],
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__heading' => 'gap: {{SIZE}}{{UNIT}};' ],
		] );
		$this->add_control( 'heading_wrap', [
			'label' => esc_html__( 'Wrap buttons', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
			'return_value' => 'wrap',
			'empty_value' => 'nowrap',
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__heading' => 'flex-wrap: {{VALUE}};' ],
		] );
		$this->add_control( 'heading_equal_width', [
			'label' => esc_html__( 'Equal-width buttons', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::SWITCHER,
			'default' => '',
			'return_value' => 'yes',
			'prefix_class' => 'ea-heading-equal-',
		] );
		$this->add_responsive_control( 'heading_margin', [
			'label' => esc_html__( 'Navigation margin', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'rem', 'em', '%' ],
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__heading' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );
		$this->add_responsive_control( 'heading_container_padding', [
			'label' => esc_html__( 'Navigation padding', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'rem', 'em', '%' ],
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__heading' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name' => 'heading_typography',
			'selector' => '{{WRAPPER}} .ea-adventure-slider__heading-button',
		] );
		$this->start_controls_tabs( 'heading_states' );
		$this->start_controls_tab( 'heading_normal', [ 'label' => esc_html__( 'Normal', 'elementor-adventure-slider' ) ] );
		$this->add_control( 'heading_text_color', [
			'label' => esc_html__( 'Text colour', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__heading-button' => 'color: {{VALUE}};' ],
		] );
		$this->add_group_control( Group_Control_Background::get_type(), [
			'name' => 'heading_background',
			'types' => [ 'classic', 'gradient' ],
			'exclude' => [ 'image' ],
			'selector' => '{{WRAPPER}} .ea-adventure-slider__heading-button',
		] );
		$this->end_controls_tab();
		$this->start_controls_tab( 'heading_hover', [ 'label' => esc_html__( 'Hover / focus', 'elementor-adventure-slider' ) ] );
		$this->add_control( 'heading_hover_text_color', [
			'label' => esc_html__( 'Text colour', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__heading-button:hover, {{WRAPPER}} .ea-adventure-slider__heading-button:focus-visible' => 'color: {{VALUE}};' ],
		] );
		$this->add_control( 'heading_hover_background_color', [
			'label' => esc_html__( 'Background colour', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__heading-button:hover, {{WRAPPER}} .ea-adventure-slider__heading-button:focus-visible' => 'background-color: {{VALUE}};' ],
		] );
		$this->add_control( 'heading_hover_border_color', [
			'label' => esc_html__( 'Border colour', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__heading-button:hover, {{WRAPPER}} .ea-adventure-slider__heading-button:focus-visible' => 'border-color: {{VALUE}};' ],
		] );
		$this->end_controls_tab();
		$this->start_controls_tab( 'heading_active', [ 'label' => esc_html__( 'Active', 'elementor-adventure-slider' ) ] );
		$this->add_control( 'heading_active_text_color', [
			'label' => esc_html__( 'Text colour', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__heading-button[aria-current="true"]' => 'color: {{VALUE}};' ],
		] );
		$this->add_control( 'heading_active_background_color', [
			'label' => esc_html__( 'Background colour', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__heading-button[aria-current="true"]' => 'background: {{VALUE}};' ],
		] );
		$this->add_control( 'heading_active_border_color', [
			'label' => esc_html__( 'Border colour', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__heading-button[aria-current="true"]' => 'border-color: {{VALUE}};' ],
		] );
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control( Group_Control_Border::get_type(), [
			'name' => 'heading_border',
			'selector' => '{{WRAPPER}} .ea-adventure-slider__heading-button',
		] );
		$this->add_responsive_control( 'heading_radius', [
			'label' => esc_html__( 'Border radius', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'rem' ],
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__heading-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );
		$this->add_responsive_control( 'heading_button_padding', [
			'label' => esc_html__( 'Button padding', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'rem', 'em', '%' ],
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__heading-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );
		$this->end_controls_section();
	}

	private function register_navigation_style_controls(): void {
		$this->start_controls_section(
			'section_navigation_style',
			[
				'label' => esc_html__( 'Navigation', 'elementor-adventure-slider' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control( 'nav_alignment', [
			'label' => esc_html__( 'Alignment', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::CHOOSE,
			'options' => [
				'flex-start' => [ 'title' => esc_html__( 'Start', 'elementor-adventure-slider' ), 'icon' => 'eicon-text-align-left' ],
				'center' => [ 'title' => esc_html__( 'Centre', 'elementor-adventure-slider' ), 'icon' => 'eicon-text-align-center' ],
				'flex-end' => [ 'title' => esc_html__( 'End', 'elementor-adventure-slider' ), 'icon' => 'eicon-text-align-right' ],
				'space-between' => [ 'title' => esc_html__( 'Space between', 'elementor-adventure-slider' ), 'icon' => 'eicon-justify-space-between-h' ],
			],
			'default' => 'space-between',
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__navigation' => 'justify-content: {{VALUE}};' ],
		] );

		$this->add_responsive_control( 'nav_gap', [
			'label' => esc_html__( 'Gap', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => 0, 'max' => 100 ] ],
			'default' => [ 'size' => 12, 'unit' => 'px' ],
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__navigation' => 'gap: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_responsive_control( 'nav_margin', [
			'label' => esc_html__( 'Margin', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'rem', 'em', '%' ],
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__navigation' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name' => 'nav_typography',
			'selector' => '{{WRAPPER}} .ea-adventure-slider__nav-button',
		] );

		$this->start_controls_tabs( 'nav_states' );
		$this->start_controls_tab( 'nav_normal', [ 'label' => esc_html__( 'Normal', 'elementor-adventure-slider' ) ] );
		$this->add_control( 'nav_text_color', [
			'label' => esc_html__( 'Text colour', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__nav-button' => 'color: {{VALUE}};' ],
		] );
		$this->add_group_control( Group_Control_Background::get_type(), [
			'name' => 'nav_background',
			'types' => [ 'classic', 'gradient' ],
			'exclude' => [ 'image' ],
			'selector' => '{{WRAPPER}} .ea-adventure-slider__nav-button',
		] );
		$this->end_controls_tab();
		$this->start_controls_tab( 'nav_hover', [ 'label' => esc_html__( 'Hover / focus', 'elementor-adventure-slider' ) ] );
		$this->add_control( 'nav_text_color_hover', [
			'label' => esc_html__( 'Text colour', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .ea-adventure-slider__nav-button:hover, {{WRAPPER}} .ea-adventure-slider__nav-button:focus-visible' => 'color: {{VALUE}};',
			],
		] );
		$this->add_control( 'nav_background_hover', [
			'label' => esc_html__( 'Background colour', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [
				'{{WRAPPER}} .ea-adventure-slider__nav-button:hover, {{WRAPPER}} .ea-adventure-slider__nav-button:focus-visible' => 'background-color: {{VALUE}};',
			],
		] );
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control( Group_Control_Border::get_type(), [
			'name' => 'nav_border',
			'selector' => '{{WRAPPER}} .ea-adventure-slider__nav-button',
		] );
		$this->add_responsive_control( 'nav_radius', [
			'label' => esc_html__( 'Border radius', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'rem' ],
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__nav-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );
		$this->add_responsive_control( 'nav_padding', [
			'label' => esc_html__( 'Padding', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'rem', 'em', '%' ],
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__nav-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->end_controls_section();
	}

	private function register_breadcrumb_style_controls(): void {
		$this->start_controls_section( 'section_breadcrumb_style', [
			'label' => esc_html__( 'Footer breadcrumbs', 'elementor-adventure-slider' ),
			'tab' => Controls_Manager::TAB_STYLE,
			'condition' => [ 'show_breadcrumbs' => 'yes' ],
		] );
		$this->add_responsive_control( 'breadcrumb_alignment', [
			'label' => esc_html__( 'Alignment', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::CHOOSE,
			'options' => [
				'flex-start' => [ 'title' => esc_html__( 'Start', 'elementor-adventure-slider' ), 'icon' => 'eicon-text-align-left' ],
				'center' => [ 'title' => esc_html__( 'Centre', 'elementor-adventure-slider' ), 'icon' => 'eicon-text-align-center' ],
				'flex-end' => [ 'title' => esc_html__( 'End', 'elementor-adventure-slider' ), 'icon' => 'eicon-text-align-right' ],
			],
			'default' => 'flex-start',
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__breadcrumb-list' => 'justify-content: {{VALUE}};' ],
		] );
		$this->add_responsive_control( 'breadcrumb_gap', [
			'label' => esc_html__( 'Gap', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'rem', 'em' ],
			'range' => [ 'px' => [ 'min' => 0, 'max' => 80 ] ],
			'default' => [ 'size' => 8, 'unit' => 'px' ],
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__breadcrumb-list' => 'gap: {{SIZE}}{{UNIT}};' ],
		] );
		$this->add_responsive_control( 'breadcrumb_margin', [
			'label' => esc_html__( 'Margin', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'rem', 'em', '%' ],
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__breadcrumbs' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );
		$this->add_responsive_control( 'breadcrumb_padding', [
			'label' => esc_html__( 'Padding', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'rem', 'em', '%' ],
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__breadcrumbs' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );
		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name' => 'breadcrumb_typography',
			'selector' => '{{WRAPPER}} .ea-adventure-slider__breadcrumb-label',
		] );
		$this->add_control( 'breadcrumb_text_color', [
			'label' => esc_html__( 'Previous step colour', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__breadcrumb-label' => 'color: {{VALUE}};' ],
		] );
		$this->add_control( 'breadcrumb_hover_color', [
			'label' => esc_html__( 'Hover / focus colour', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} button.ea-adventure-slider__breadcrumb-label:hover, {{WRAPPER}} button.ea-adventure-slider__breadcrumb-label:focus-visible' => 'color: {{VALUE}};' ],
		] );
		$this->add_control( 'breadcrumb_current_color', [
			'label' => esc_html__( 'Current step colour', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__breadcrumb-label[aria-current="step"]' => 'color: {{VALUE}};' ],
		] );
		$this->add_control( 'breadcrumb_separator_color', [
			'label' => esc_html__( 'Separator colour', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__breadcrumb-separator' => 'color: {{VALUE}};' ],
		] );
		$this->add_control( 'breadcrumb_background_color', [
			'label' => esc_html__( 'Background colour', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__breadcrumbs' => 'background-color: {{VALUE}};' ],
		] );
		$this->add_group_control( Group_Control_Border::get_type(), [
			'name' => 'breadcrumb_border',
			'selector' => '{{WRAPPER}} .ea-adventure-slider__breadcrumbs',
		] );
		$this->add_responsive_control( 'breadcrumb_radius', [
			'label' => esc_html__( 'Border radius', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'rem' ],
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__breadcrumbs' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );
		$this->end_controls_section();
	}

	private function register_dots_style_controls(): void {
		$this->start_controls_section( 'section_dots_style', [
			'label' => esc_html__( 'Pagination dots', 'elementor-adventure-slider' ),
			'tab' => Controls_Manager::TAB_STYLE,
			'condition' => [ 'show_dots' => 'yes' ],
		] );
		$this->add_responsive_control( 'dot_size', [
			'label' => esc_html__( 'Size', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => 4, 'max' => 40 ] ],
			'default' => [ 'size' => 10, 'unit' => 'px' ],
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__dot' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ],
		] );
		$this->add_control( 'dot_color', [
			'label' => esc_html__( 'Colour', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__dot' => 'background-color: {{VALUE}};' ],
		] );
		$this->add_control( 'dot_active_color', [
			'label' => esc_html__( 'Active colour', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__dot[aria-current="true"]' => 'background-color: {{VALUE}};' ],
		] );
		$this->end_controls_section();
	}

	private function register_progress_style_controls(): void {
		$this->start_controls_section( 'section_progress_style', [
			'label' => esc_html__( 'Progress', 'elementor-adventure-slider' ),
			'tab' => Controls_Manager::TAB_STYLE,
			'condition' => [ 'show_progress' => 'yes' ],
		] );
		$this->add_control( 'progress_track_color', [
			'label' => esc_html__( 'Track colour', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__progress-track' => 'background-color: {{VALUE}};' ],
		] );
		$this->add_control( 'progress_bar_color', [
			'label' => esc_html__( 'Bar colour', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__progress-bar' => 'background-color: {{VALUE}};' ],
		] );
		$this->add_responsive_control( 'progress_height', [
			'label' => esc_html__( 'Height', 'elementor-adventure-slider' ),
			'type' => Controls_Manager::SLIDER,
			'range' => [ 'px' => [ 'min' => 1, 'max' => 30 ] ],
			'default' => [ 'size' => 4, 'unit' => 'px' ],
			'selectors' => [ '{{WRAPPER}} .ea-adventure-slider__progress-track' => 'height: {{SIZE}}{{UNIT}};' ],
		] );
		$this->end_controls_section();
	}

	private function normalise_slides( array $items ): array {
		$slides = [];
		$used = [];

		foreach ( $items as $index => $item ) {
			$title = isset( $item['slide_title'] ) ? sanitize_text_field( (string) $item['slide_title'] ) : '';
			$id = isset( $item['slide_id'] ) ? sanitize_key( (string) $item['slide_id'] ) : '';
			$breadcrumb_label = isset( $item['breadcrumb_label'] ) ? sanitize_text_field( (string) $item['breadcrumb_label'] ) : '';

			if ( '' === $id || isset( $used[ $id ] ) ) {
				$id = 'slide-' . ( $index + 1 );
				while ( isset( $used[ $id ] ) ) {
					$id .= '-x';
				}
			}

			$used[ $id ] = true;
			$slides[] = [
				'id'              => $id,
				'title'           => '' !== $title ? $title : sprintf( __( 'Slide %d', 'elementor-adventure-slider' ), $index + 1 ),
				'breadcrumb'      => '' !== $breadcrumb_label ? $breadcrumb_label : ( '' !== $title ? $title : sprintf( __( 'Slide %d', 'elementor-adventure-slider' ), $index + 1 ) ),
				'show_in_heading' => ! isset( $item['show_in_heading'] ) || 'yes' === $item['show_in_heading'],
				'index'           => $index,
			];
		}

		return $slides;
	}

	private function get_initial_slide_id( array $settings, array $slides ): string {
		$requested = sanitize_key( (string) ( $settings['initial_slide'] ?? '' ) );
		foreach ( $slides as $slide ) {
			if ( $requested === $slide['id'] ) {
				return $requested;
			}
		}

		return $slides[0]['id'] ?? '';
	}

	public function print_child( $index, $item_settings = [] ) {
		$children = $this->get_children();
		if ( ! isset( $children[ $index ] ) ) {
			return;
		}

		$child_id = $children[ $index ]->get_id();
		$add_attributes = static function ( $should_render, $container ) use ( $child_id, $item_settings ) {
			if ( $child_id !== $container->get_id() ) {
				return $should_render;
			}

			$is_active = ! empty( $item_settings['active'] );
			$attributes = [
				'class'                => [ 'ea-adventure-slide', $is_active ? 'is-active' : '' ],
				'data-adventure-slide' => $item_settings['id'],
				'data-slide-index'     => (string) $item_settings['index'],
				'data-slide-title'     => $item_settings['title'],
				'data-breadcrumb-title' => $item_settings['breadcrumb'],
				'role'                 => 'group',
				'aria-roledescription' => esc_attr__( 'slide', 'elementor-adventure-slider' ),
				'aria-label'           => sprintf(
					/* translators: 1: current slide number, 2: total slides, 3: slide title. */
					esc_attr__( '%1$d of %2$d: %3$s', 'elementor-adventure-slider' ),
					$item_settings['index'] + 1,
					$item_settings['total'],
					$item_settings['title']
				),
				'aria-hidden'          => $is_active ? 'false' : 'true',
			];

			if ( ! $is_active ) {
				$attributes['hidden'] = 'hidden';
			}

			$container->add_render_attribute( '_wrapper', $attributes );
			return $should_render;
		};

		add_filter( 'elementor/frontend/container/should_render', $add_attributes, 10, 2 );
		$children[ $index ]->print_element();
		remove_filter( 'elementor/frontend/container/should_render', $add_attributes, 10 );
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$slides = $this->normalise_slides( is_array( $settings['slides'] ?? null ) ? $settings['slides'] : [] );
		if ( [] === $slides ) {
			return;
		}

		$this->slides_for_render = $slides;
		$initial_id = $this->get_initial_slide_id( $settings, $slides );
		$root_key = 'adventure-root';
		$this->add_render_attribute( $root_key, [
			'class'                    => 'ea-adventure-slider',
			'data-adventure-instance'  => $this->get_id(),
			'data-initial-slide'       => $initial_id,
			'data-transition'          => in_array( $settings['transition'] ?? '', [ 'slide', 'fade', 'none' ], true ) ? $settings['transition'] : 'slide',
			'data-transition-speed'    => (string) min( 3000, max( 0, (int) ( $settings['transition_speed'] ?? 350 ) ) ),
			'data-allow-swipe'         => 'yes' === ( $settings['allow_swipe'] ?? '' ) ? 'true' : 'false',
			'data-keyboard-navigation' => 'yes' === ( $settings['keyboard_navigation'] ?? '' ) ? 'true' : 'false',
			'data-loop'                => 'yes' === ( $settings['loop'] ?? '' ) ? 'true' : 'false',
			'data-persist-path'        => 'yes' === ( $settings['persist_path'] ?? '' ) ? 'true' : 'false',
			'data-autoplay'            => 'yes' === ( $settings['autoplay'] ?? '' ) ? 'true' : 'false',
			'data-autoplay-delay'      => (string) min( 60000, max( 1000, (int) ( $settings['autoplay_delay'] ?? 5000 ) ) ),
			'data-pause-on-hover'      => 'yes' === ( $settings['pause_on_hover'] ?? '' ) ? 'true' : 'false',
			'role'                     => 'region',
			'aria-roledescription'     => esc_attr__( 'carousel', 'elementor-adventure-slider' ),
			'aria-label'               => sanitize_text_field( (string) ( $settings['accessibility_label'] ?? __( 'Interactive journey', 'elementor-adventure-slider' ) ) ),
		] );

		if ( 'yes' === ( $settings['keyboard_navigation'] ?? '' ) ) {
			$this->add_render_attribute( $root_key, 'tabindex', '0' );
		}
		?>
		<div <?php $this->print_render_attribute_string( $root_key ); ?>>
			<div class="ea-adventure-slider__editor-status" data-adventure-editor-status><?php
				echo esc_html(
					sprintf(
						/* translators: 1: slide title, 2: slide ID. */
						__( 'Editing slide: %1$s (ID: %2$s)', 'elementor-adventure-slider' ),
						$slides[0]['title'],
						$slides[0]['id']
					)
				);
			?></div>
			<nav class="ea-adventure-slider__heading ea-adventure-slider__editor-tabs<?php echo 'yes' === ( $settings['show_heading_navigation'] ?? 'yes' ) ? '' : ' is-editor-only'; ?>" aria-label="<?php echo esc_attr( sanitize_text_field( (string) ( $settings['heading_accessibility_label'] ?? __( 'Choose a slide', 'elementor-adventure-slider' ) ) ) ); ?>">
				<?php foreach ( $slides as $slide ) : ?>
					<button class="ea-adventure-slider__heading-button<?php echo $slide['show_in_heading'] ? '' : ' is-editor-only'; ?>" type="button" data-adventure-editor-slide="<?php echo esc_attr( $slide['id'] ); ?>" data-adventure-target="<?php echo esc_attr( $slide['id'] ); ?>" data-slide-index="<?php echo esc_attr( (string) $slide['index'] ); ?>" aria-current="<?php echo $slide['id'] === $initial_id ? 'true' : 'false'; ?>"><?php echo esc_html( $slide['title'] ); ?></button>
				<?php endforeach; ?>
			</nav>
			<div class="ea-adventure-slider__slides">
				<?php foreach ( $slides as $slide ) : ?>
					<?php $this->print_child( $slide['index'], array_merge( $slide, [ 'total' => count( $slides ), 'active' => $slide['id'] === $initial_id ] ) ); ?>
				<?php endforeach; ?>
			</div>
			<?php $this->render_progress( $settings ); ?>
			<?php $this->render_dots( $settings, $slides, $initial_id ); ?>
			<?php $this->render_navigation( $settings ); ?>
			<?php $this->render_breadcrumbs( $settings, $slides, $initial_id ); ?>
			<div class="ea-adventure-slider__live elementor-screen-only" aria-live="polite" aria-atomic="true"></div>
		</div>
		<?php
	}

	private function render_progress( array $settings ): void {
		if ( 'yes' !== ( $settings['show_progress'] ?? '' ) ) {
			return;
		}
		?>
		<div class="ea-adventure-slider__progress">
			<div class="ea-adventure-slider__progress-track" role="progressbar" aria-valuemin="1" aria-valuemax="<?php echo esc_attr( (string) count( $this->slides_for_render ) ); ?>" aria-valuenow="1">
				<span class="ea-adventure-slider__progress-bar"></span>
			</div>
			<span class="ea-adventure-slider__progress-text"></span>
		</div>
		<?php
	}

	private function render_dots( array $settings, array $slides, string $initial_id ): void {
		if ( 'yes' !== ( $settings['show_dots'] ?? '' ) ) {
			return;
		}
		?>
		<div class="ea-adventure-slider__dots" aria-label="<?php echo esc_attr__( 'Choose a slide', 'elementor-adventure-slider' ); ?>">
			<?php foreach ( $slides as $slide ) : ?>
				<button class="ea-adventure-slider__dot" type="button" data-adventure-target="<?php echo esc_attr( $slide['id'] ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Go to %s', 'elementor-adventure-slider' ), $slide['title'] ) ); ?>" aria-current="<?php echo $slide['id'] === $initial_id ? 'true' : 'false'; ?>"></button>
			<?php endforeach; ?>
		</div>
		<?php
	}

	private function render_navigation( array $settings ): void {
		$buttons = [];
		if ( 'yes' === ( $settings['show_back'] ?? '' ) ) {
			$buttons[] = [ 'back', $settings['back_label'] ?? __( 'Back', 'elementor-adventure-slider' ) ];
		}
		if ( 'yes' === ( $settings['show_restart'] ?? '' ) ) {
			$buttons[] = [ 'restart', $settings['restart_label'] ?? __( 'Restart', 'elementor-adventure-slider' ) ];
		}
		if ( 'yes' === ( $settings['show_arrows'] ?? '' ) ) {
			$buttons[] = [ 'previous', $settings['previous_label'] ?? __( 'Previous', 'elementor-adventure-slider' ) ];
			$buttons[] = [ 'next', $settings['next_label'] ?? __( 'Next', 'elementor-adventure-slider' ) ];
		}

		if ( [] === $buttons ) {
			return;
		}
		?>
		<div class="ea-adventure-slider__navigation">
			<?php foreach ( $buttons as $button ) : ?>
				<button class="ea-adventure-slider__nav-button ea-adventure-slider__nav-button--<?php echo esc_attr( $button[0] ); ?>" type="button" data-adventure-action="<?php echo esc_attr( $button[0] ); ?>"><?php echo esc_html( $button[1] ); ?></button>
			<?php endforeach; ?>
		</div>
		<?php
	}

	private function render_breadcrumbs( array $settings, array $slides, string $initial_id ): void {
		if ( 'yes' !== ( $settings['show_breadcrumbs'] ?? 'yes' ) ) {
			return;
		}
		$current = $slides[0] ?? null;
		foreach ( $slides as $slide ) {
			if ( $slide['id'] === $initial_id ) {
				$current = $slide;
				break;
			}
		}
		if ( ! $current ) {
			return;
		}
		?>
		<nav class="ea-adventure-slider__breadcrumbs" data-adventure-breadcrumbs data-clickable="<?php echo 'yes' === ( $settings['breadcrumbs_clickable'] ?? 'yes' ) ? 'true' : 'false'; ?>" data-separator="<?php echo esc_attr( sanitize_text_field( (string) ( $settings['breadcrumb_separator'] ?? '›' ) ) ); ?>" aria-label="<?php echo esc_attr( sanitize_text_field( (string) ( $settings['breadcrumb_accessibility_label'] ?? __( 'Your journey', 'elementor-adventure-slider' ) ) ) ); ?>">
			<ol class="ea-adventure-slider__breadcrumb-list">
				<li class="ea-adventure-slider__breadcrumb-item"><span class="ea-adventure-slider__breadcrumb-label" aria-current="step"><?php echo esc_html( $current['breadcrumb'] ); ?></span></li>
			</ol>
		</nav>
		<?php
	}

	protected function content_template_single_repeater_item() {
		?>
		<#
		const itemIndex = view.collection.length,
			item = data,
			fallbackId = 'slide-' + itemIndex,
			itemId = ( item.slide_id || fallbackId ).toString().toLowerCase().replace( /[^a-z0-9_-]/g, '-' );
		#>
		<button class="ea-adventure-slider__heading-button{{ 'yes' === item.show_in_heading || undefined === item.show_in_heading ? '' : ' is-editor-only' }}" type="button" data-adventure-editor-slide="{{ itemId }}" data-adventure-target="{{ itemId }}" data-slide-index="{{ itemIndex - 1 }}" data-binding-type="repeater-item" data-binding-repeater-name="slides" data-binding-index="{{ itemIndex }}">{{{ item.slide_title || fallbackId }}}</button>
		<?php
	}

	protected function content_template() {
		?>
		<#
		const sliderId = view.getID(),
			initialId = ( settings.initial_slide || 'start' ).toString().toLowerCase().replace( /[^a-z0-9_-]/g, '-' );
		const initialItem = _.find( settings.slides || [], function( item ) {
			return ( item.slide_id || '' ).toString().toLowerCase().replace( /[^a-z0-9_-]/g, '-' ) === initialId;
		} ) || ( settings.slides || [] )[ 0 ] || {};
		#>
		<div class="ea-adventure-slider"
			data-adventure-instance="{{ sliderId }}"
			data-initial-slide="{{ initialId }}"
			data-transition="{{ settings.transition || 'slide' }}"
			data-transition-speed="{{ settings.transition_speed || 350 }}"
			data-allow-swipe="{{ 'yes' === settings.allow_swipe ? 'true' : 'false' }}"
			data-keyboard-navigation="{{ 'yes' === settings.keyboard_navigation ? 'true' : 'false' }}"
			data-loop="{{ 'yes' === settings.loop ? 'true' : 'false' }}"
			data-persist-path="false"
			data-autoplay="false"
			role="region"
			aria-roledescription="carousel"
			aria-label="{{ settings.accessibility_label || '<?php echo esc_js( __( 'Interactive journey', 'elementor-adventure-slider' ) ); ?>' }}">
			<div class="ea-adventure-slider__editor-status" data-adventure-editor-status>Editing slide: {{{ initialItem.slide_title || initialId }}} (ID: {{ initialItem.slide_id || initialId }})</div>
			<nav class="ea-adventure-slider__heading ea-adventure-slider__editor-tabs{{ 'yes' === settings.show_heading_navigation || undefined === settings.show_heading_navigation ? '' : ' is-editor-only' }}" aria-label="{{ settings.heading_accessibility_label || '<?php echo esc_js( __( 'Choose a slide', 'elementor-adventure-slider' ) ); ?>' }}">
				<# _.each( settings.slides || [], function( item, index ) {
					const fallbackId = 'slide-' + ( index + 1 ),
						itemId = ( item.slide_id || fallbackId ).toString().toLowerCase().replace( /[^a-z0-9_-]/g, '-' );
				#>
				<button class="ea-adventure-slider__heading-button{{ 'yes' === item.show_in_heading || undefined === item.show_in_heading ? '' : ' is-editor-only' }}" type="button" data-adventure-editor-slide="{{ itemId }}" data-adventure-target="{{ itemId }}" data-slide-index="{{ index }}" aria-current="{{ itemId === initialId ? 'true' : 'false' }}">{{{ item.slide_title || fallbackId }}}</button>
				<# } ); #>
			</nav>
			<div class="ea-adventure-slider__slides"></div>
			<# if ( 'yes' === settings.show_progress ) { #>
			<div class="ea-adventure-slider__progress"><div class="ea-adventure-slider__progress-track" role="progressbar"><span class="ea-adventure-slider__progress-bar"></span></div><span class="ea-adventure-slider__progress-text"></span></div>
			<# } #>
			<# if ( 'yes' === settings.show_dots ) { #>
			<div class="ea-adventure-slider__dots">
				<# _.each( settings.slides || [], function( item, index ) { const fallbackId = 'slide-' + ( index + 1 ), itemId = ( item.slide_id || fallbackId ).toString().toLowerCase().replace( /[^a-z0-9_-]/g, '-' ); #>
				<button class="ea-adventure-slider__dot" type="button" data-adventure-target="{{ itemId }}" aria-label="<?php echo esc_attr__( 'Go to slide', 'elementor-adventure-slider' ); ?> {{ index + 1 }}"></button>
				<# } ); #>
			</div>
			<# } #>
			<div class="ea-adventure-slider__navigation">
				<# if ( 'yes' === settings.show_back ) { #><button class="ea-adventure-slider__nav-button" type="button" data-adventure-action="back">{{ settings.back_label }}</button><# } #>
				<# if ( 'yes' === settings.show_restart ) { #><button class="ea-adventure-slider__nav-button" type="button" data-adventure-action="restart">{{ settings.restart_label }}</button><# } #>
				<# if ( 'yes' === settings.show_arrows ) { #><button class="ea-adventure-slider__nav-button" type="button" data-adventure-action="previous">{{ settings.previous_label }}</button><button class="ea-adventure-slider__nav-button" type="button" data-adventure-action="next">{{ settings.next_label }}</button><# } #>
			</div>
			<# if ( 'yes' === settings.show_breadcrumbs || undefined === settings.show_breadcrumbs ) { #>
			<nav class="ea-adventure-slider__breadcrumbs" data-adventure-breadcrumbs data-clickable="{{ 'yes' === settings.breadcrumbs_clickable || undefined === settings.breadcrumbs_clickable ? 'true' : 'false' }}" data-separator="{{ settings.breadcrumb_separator || '›' }}" aria-label="{{ settings.breadcrumb_accessibility_label || '<?php echo esc_js( __( 'Your journey', 'elementor-adventure-slider' ) ); ?>' }}">
				<ol class="ea-adventure-slider__breadcrumb-list"></ol>
			</nav>
			<# } #>
			<div class="ea-adventure-slider__live elementor-screen-only" aria-live="polite" aria-atomic="true"></div>
		</div>
		<?php
	}
}
