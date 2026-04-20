<?php
namespace MD_Nested_Carousel;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Modules\NestedElements\Base\Widget_Nested_Base;
use Elementor\Modules\NestedElements\Controls\Control_Nested_Repeater;
use Elementor\Repeater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Widget extends Widget_Nested_Base {

	public int $num_of_carousel_items = 0;

	// =========================================================================
	// Identity
	// =========================================================================

	public function get_name(): string {
		return 'md-nested-carousel';
	}

	public function get_title(): string {
		return esc_html__( 'Nested Carousel', 'md-nested-carousel' );
	}

	public function get_icon(): string {
		return 'eicon-nested-carousel';
	}

	public function get_keywords(): array {
		return [ 'Carousel', 'Slides', 'Nested', 'Media', 'Gallery', 'Image' ];
	}

	public function get_categories(): array {
		return [ 'general' ];
	}

	public function get_style_depends(): array {
		return [ 'e-swiper', 'md-nested-carousel' ];
	}

	public function get_script_depends(): array {
		return [ 'swiper', 'md-nested-carousel' ];
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	// =========================================================================
	// Nested Elements scaffolding
	// These methods are the bridge between the repeater items and the actual
	// Elementor child containers injected into each slide shell.
	// =========================================================================

	/**
	 * Default children created when the widget is first placed on the canvas.
	 * Each item becomes a full Elementor Container (e-con).
	 */
	protected function get_default_children_elements(): array {
		return [
			[ 'elType' => 'container', 'settings' => [ '_title' => __( 'Slide #1', 'md-nested-carousel' ) ] ],
			[ 'elType' => 'container', 'settings' => [ '_title' => __( 'Slide #2', 'md-nested-carousel' ) ] ],
			[ 'elType' => 'container', 'settings' => [ '_title' => __( 'Slide #3', 'md-nested-carousel' ) ] ],
		];
	}

	/**
	 * The repeater control key whose value becomes the container title
	 * shown in the Navigator (e.g. "Slide #1").
	 */
	protected function get_default_repeater_title_setting_key(): string {
		return 'slide_title';
	}

	/**
	 * sprintf pattern for auto-naming new slides ("Slide #%d").
	 */
	protected function get_default_children_title(): string {
		return esc_html__( 'Slide #%d', 'md-nested-carousel' );
	}

	/**
	 * CSS selector of the element INSIDE the widget template where Elementor
	 * should place the child containers (.e-con) during initial render.
	 */
	protected function get_default_children_placeholder_selector(): string {
		return '.swiper-wrapper';
	}

	/**
	 * CSS selector of the per-slide shell element that wraps each child container.
	 * Elementor uses this to know which shell corresponds to each child.
	 */
	protected function get_default_children_container_placeholder_selector(): string {
		return '.swiper-slide';
	}

	/**
	 * Initial JS config passed to the editor.
	 * 'support_improved_repeaters' + 'is_interlaced' enable the mechanism where
	 * each repeater item is paired 1:1 with a child container.
	 * 'target_container' is the CSS path where Elementor injects children.
	 */
	protected function get_initial_config(): array {
		return array_merge( parent::get_initial_config(), [
			'support_improved_repeaters' => true,
			'target_container'           => [ '.md-n-carousel > .swiper-wrapper' ],
			'node'                       => 'div',
			'is_interlaced'              => true,
		] );
	}

	// =========================================================================
	// Controls
	// =========================================================================

	protected function register_controls(): void {
		$slide_con = ':where( {{WRAPPER}} .swiper-slide ) > .e-con';

		// ---- Slides ----
		$this->start_controls_section( 'section_slides', [
			'label' => esc_html__( 'Slides', 'md-nested-carousel' ),
		] );

		$repeater = new Repeater();
		$repeater->add_control( 'slide_title', [
			'label'       => esc_html__( 'Title', 'md-nested-carousel' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => esc_html__( 'Slide Title', 'md-nested-carousel' ),
			'placeholder' => esc_html__( 'Slide Title', 'md-nested-carousel' ),
			'dynamic'     => [ 'active' => true ],
			'label_block' => true,
		] );

		$this->add_control( 'carousel_name', [
			'label'   => esc_html__( 'Carousel Name', 'md-nested-carousel' ),
			'type'    => Controls_Manager::TEXT,
			'default' => esc_html__( 'Carousel', 'md-nested-carousel' ),
		] );

		$this->add_control( 'carousel_items', [
			'label'              => esc_html__( 'Carousel Items', 'md-nested-carousel' ),
			'type'               => Control_Nested_Repeater::CONTROL_TYPE,
			'fields'             => $repeater->get_controls(),
			'default'            => [
				[ 'slide_title' => esc_html__( 'Slide #1', 'md-nested-carousel' ) ],
				[ 'slide_title' => esc_html__( 'Slide #2', 'md-nested-carousel' ) ],
				[ 'slide_title' => esc_html__( 'Slide #3', 'md-nested-carousel' ) ],
			],
			'frontend_available' => true,
			'title_field'        => '{{{ slide_title }}}',
		] );

		// Layout controls (slides per view, scroll, equal height)
		$slides_range = range( 1, 8 );
		$slides_opts  = [ '' => esc_html__( 'Default', 'md-nested-carousel' ) ]
		                + array_combine( $slides_range, $slides_range );

		$this->add_responsive_control( 'slides_to_show', [
			'label'                => esc_html__( 'Slides on display', 'md-nested-carousel' ),
			'type'                 => Controls_Manager::SELECT,
			'options'              => $slides_opts,
			'default'              => '3',
			'tablet_default'       => '2',
			'mobile_default'       => '1',
			'inherit_placeholders' => false,
			'frontend_available'   => true,
			'render_type'          => 'template',
			'separator'            => 'before',
			'selectors'            => [
				'{{WRAPPER}}' => '--md-n-carousel-slides-to-display: {{VALUE}}',
			],
		] );

		$this->add_responsive_control( 'slides_to_scroll', [
			'label'                => esc_html__( 'Slides on scroll', 'md-nested-carousel' ),
			'type'                 => Controls_Manager::SELECT,
			'options'              => $slides_opts,
			'inherit_placeholders' => false,
			'frontend_available'   => true,
		] );

		$this->add_control( 'equal_height', [
			'label'     => esc_html__( 'Equal Height', 'md-nested-carousel' ),
			'type'      => Controls_Manager::SWITCHER,
			'label_off' => esc_html__( 'Off', 'md-nested-carousel' ),
			'label_on'  => esc_html__( 'On', 'md-nested-carousel' ),
			'default'   => 'yes',
			'selectors' => [
				'{{WRAPPER}}' => '--md-n-carousel-slide-height: auto; --md-n-carousel-slide-container-height: 100%;',
			],
		] );

		$this->end_controls_section();

		// ---- Settings ----
		$this->start_controls_section( 'section_carousel_settings', [
			'label' => esc_html__( 'Settings', 'md-nested-carousel' ),
		] );

		$this->add_control( 'autoplay', [
			'label'              => esc_html__( 'Autoplay', 'md-nested-carousel' ),
			'type'               => Controls_Manager::SWITCHER,
			'default'            => 'yes',
			'frontend_available' => true,
			'description'        => esc_html__( 'Inactive in editor. Preview to see it.', 'md-nested-carousel' ),
		] );

		$this->add_control( 'autoplay_speed', [
			'label'              => esc_html__( 'Scroll Speed', 'md-nested-carousel' ) . ' (ms)',
			'type'               => Controls_Manager::NUMBER,
			'default'            => 5000,
			'condition'          => [ 'autoplay' => 'yes' ],
			'frontend_available' => true,
		] );

		$this->add_control( 'pause_on_hover', [
			'label'              => esc_html__( 'Pause on hover', 'md-nested-carousel' ),
			'type'               => Controls_Manager::SWITCHER,
			'default'            => 'yes',
			'condition'          => [ 'autoplay' => 'yes' ],
			'frontend_available' => true,
		] );

		$this->add_control( 'pause_on_interaction', [
			'label'              => esc_html__( 'Pause on interaction', 'md-nested-carousel' ),
			'type'               => Controls_Manager::SWITCHER,
			'default'            => 'yes',
			'condition'          => [ 'autoplay' => 'yes' ],
			'frontend_available' => true,
		] );

		$this->add_control( 'infinite', [
			'label'              => esc_html__( 'Infinite scroll', 'md-nested-carousel' ),
			'type'               => Controls_Manager::SWITCHER,
			'default'            => 'yes',
			'frontend_available' => true,
			'description'        => esc_html__( 'Inactive in editor. Preview to see it.', 'md-nested-carousel' ),
		] );

		$this->add_control( 'speed', [
			'label'              => esc_html__( 'Transition Duration', 'md-nested-carousel' ) . ' (ms)',
			'type'               => Controls_Manager::NUMBER,
			'default'            => 500,
			'frontend_available' => true,
		] );

		$this->add_control( 'direction', [
			'label'   => esc_html__( 'Direction', 'md-nested-carousel' ),
			'type'    => Controls_Manager::SELECT,
			'default' => is_rtl() ? 'rtl' : 'ltr',
			'options' => [
				'ltr' => esc_html__( 'Left', 'md-nested-carousel' ),
				'rtl' => esc_html__( 'Right', 'md-nested-carousel' ),
			],
		] );

		$this->end_controls_section();

		// ---- Navigation ----
		$this->start_controls_section( 'section_navigation_settings', [
			'label' => esc_html__( 'Navigation', 'md-nested-carousel' ),
		] );

		$this->add_control( 'arrows', [
			'label'              => esc_html__( 'Arrows', 'md-nested-carousel' ),
			'type'               => Controls_Manager::SWITCHER,
			'label_off'          => esc_html__( 'Hide', 'md-nested-carousel' ),
			'label_on'           => esc_html__( 'Show', 'md-nested-carousel' ),
			'default'            => 'yes',
			'frontend_available' => true,
		] );

		$this->add_control( 'navigation_previous_icon', [
			'label'     => esc_html__( 'Previous Icon', 'md-nested-carousel' ),
			'type'      => Controls_Manager::ICONS,
			'default'   => [ 'value' => 'eicon-chevron-left', 'library' => 'eicons' ],
			'condition' => [ 'arrows' => 'yes' ],
			'skin'      => 'inline',
			'label_block' => false,
		] );

		$this->add_control( 'navigation_next_icon', [
			'label'     => esc_html__( 'Next Icon', 'md-nested-carousel' ),
			'type'      => Controls_Manager::ICONS,
			'default'   => [ 'value' => 'eicon-chevron-right', 'library' => 'eicons' ],
			'condition' => [ 'arrows' => 'yes' ],
			'skin'      => 'inline',
			'label_block' => false,
		] );

		$this->add_control( 'arrows_position', [
			'label'        => esc_html__( 'Position', 'md-nested-carousel' ),
			'type'         => Controls_Manager::SELECT,
			'default'      => 'inside',
			'options'      => [
				'inside'  => esc_html__( 'Inside', 'md-nested-carousel' ),
				'outside' => esc_html__( 'Outside', 'md-nested-carousel' ),
			],
			'prefix_class' => 'md-arrows-position-',
			'condition'    => [ 'arrows' => 'yes' ],
			'separator'    => 'before',
		] );

		$this->end_controls_section();

		// ---- Pagination ----
		$this->start_controls_section( 'section_carousel_pagination', [
			'label' => esc_html__( 'Pagination', 'md-nested-carousel' ),
		] );

		$this->add_control( 'pagination', [
			'label'              => esc_html__( 'Pagination', 'md-nested-carousel' ),
			'type'               => Controls_Manager::SELECT,
			'default'            => 'bullets',
			'options'            => [
				''            => esc_html__( 'None', 'md-nested-carousel' ),
				'bullets'     => esc_html__( 'Dots', 'md-nested-carousel' ),
				'fraction'    => esc_html__( 'Fraction', 'md-nested-carousel' ),
				'progressbar' => esc_html__( 'Progress', 'md-nested-carousel' ),
			],
			'prefix_class'       => 'md-pagination-type-',
			'frontend_available' => true,
		] );

		$this->end_controls_section();

		// =========================================================================
		// STYLE TAB
		// =========================================================================

		// ---- Slides Style ----
		$this->start_controls_section( 'section_slides_style', [
			'label' => esc_html__( 'Slides', 'md-nested-carousel' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_responsive_control( 'image_spacing_custom', [
			'label'              => esc_html__( 'Gap between slides', 'md-nested-carousel' ),
			'type'               => Controls_Manager::SLIDER,
			'size_units'         => [ 'px' ],
			'range'              => [ 'px' => [ 'max' => 400 ] ],
			'default'            => [ 'size' => 10 ],
			'frontend_available' => true,
			'selectors'          => [
				'{{WRAPPER}}' => '--md-n-carousel-slides-gap: {{SIZE}}{{UNIT}}',
			],
		] );

		$this->add_group_control( Group_Control_Background::get_type(), [
			'name'     => 'content_background',
			'types'    => [ 'classic', 'gradient' ],
			'exclude'  => [ 'image' ],
			'selector' => $slide_con,
		] );

		$this->add_group_control( Group_Control_Border::get_type(), [
			'name'     => 'content_border',
			'selector' => $slide_con,
		] );

		$this->add_responsive_control( 'border_radius', [
			'label'      => esc_html__( 'Border Radius', 'md-nested-carousel' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
			'selectors'  => [
				$slide_con => '--border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
		] );

		$this->add_responsive_control( 'content_padding', [
			'label'      => esc_html__( 'Padding', 'md-nested-carousel' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
			'selectors'  => [
				$slide_con => '--padding-top: {{TOP}}{{UNIT}}; --padding-right: {{RIGHT}}{{UNIT}}; --padding-bottom: {{BOTTOM}}{{UNIT}}; --padding-left: {{LEFT}}{{UNIT}};',
			],
			'separator'  => 'before',
		] );

		$this->end_controls_section();

		// ---- Navigation Style ----
		$this->start_controls_section( 'section_design_navigation', [
			'label'     => esc_html__( 'Navigation', 'md-nested-carousel' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => [ 'arrows' => 'yes' ],
		] );

		$this->add_responsive_control( 'arrows_size', [
			'label'      => esc_html__( 'Icon Size', 'md-nested-carousel' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em', 'rem', 'custom' ],
			'range'      => [ 'px' => [ 'min' => 5, 'max' => 100 ] ],
			'selectors'  => [
				'{{WRAPPER}}' => '--md-n-carousel-arrow-size: {{SIZE}}{{UNIT}}',
			],
		] );

		$this->start_controls_tabs( 'arrows_colors' );
		foreach ( [ 'normal', 'hover' ] as $state ) {
			$label = 'normal' === $state
				? esc_html__( 'Normal', 'md-nested-carousel' )
				: esc_html__( 'Hover', 'md-nested-carousel' );
			$sel   = 'normal' === $state
				? '{{WRAPPER}} :is(.md-carousel-button-prev, .md-carousel-button-next)'
				: '{{WRAPPER}} :is(.md-carousel-button-prev:hover, .md-carousel-button-next:hover)';

			$this->start_controls_tab( 'arrows_' . $state . '_tab', [ 'label' => $label ] );

			$this->add_control( 'arrow_' . $state . '_color', [
				'label'     => esc_html__( 'Color', 'md-nested-carousel' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [ '{{WRAPPER}}' => '--md-n-carousel-arrow-' . $state . '-color: {{VALUE}}' ],
			] );

			$this->add_group_control( Group_Control_Background::get_type(), [
				'name'    => 'arrows_' . $state . '_bg',
				'types'   => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => $sel,
			] );

			$this->add_group_control( Group_Control_Border::get_type(), [
				'name'     => 'arrows_' . $state . '_border',
				'selector' => $sel,
			] );

			$this->add_group_control( Group_Control_Box_Shadow::get_type(), [
				'name'     => 'arrows_' . $state . '_shadow',
				'selector' => $sel,
			] );

			$this->end_controls_tab();
		}
		$this->end_controls_tabs();

		$this->add_responsive_control( 'arrows_border_radius', [
			'label'      => esc_html__( 'Border Radius', 'md-nested-carousel' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
			'selectors'  => [
				'{{WRAPPER}} :is(.md-carousel-button-prev, .md-carousel-button-next)' =>
					'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
			'separator'  => 'before',
		] );

		$this->add_responsive_control( 'arrows_padding', [
			'label'      => esc_html__( 'Padding', 'md-nested-carousel' ),
			'type'       => Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
			'selectors'  => [
				'{{WRAPPER}} :is(.md-carousel-button-prev, .md-carousel-button-next)' =>
					'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
			],
		] );

		$this->end_controls_section();

		// ---- Pagination Style ----
		$this->start_controls_section( 'section_pagination_design', [
			'label'     => esc_html__( 'Pagination', 'md-nested-carousel' ),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => [ 'pagination!' => '' ],
		] );

		$this->add_responsive_control( 'dots_size', [
			'label'      => esc_html__( 'Size', 'md-nested-carousel' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em', 'rem', 'custom' ],
			'range'      => [ 'px' => [ 'min' => 5, 'max' => 40 ] ],
			'selectors'  => [ '{{WRAPPER}}' => '--md-n-carousel-pagination-size: {{SIZE}}{{UNIT}}' ],
			'condition'  => [ 'pagination' => 'bullets' ],
		] );

		$this->start_controls_tabs( 'dots_colors' );
		$this->start_controls_tab( 'dots_normal_tab', [ 'label' => esc_html__( 'Normal', 'md-nested-carousel' ), 'condition' => [ 'pagination' => 'bullets' ] ] );
		$this->add_control( 'dots_normal_color', [
			'label'     => esc_html__( 'Color', 'md-nested-carousel' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}}' => '--md-n-carousel-dots-normal-color: {{VALUE}}' ],
			'condition' => [ 'pagination' => 'bullets' ],
		] );
		$this->end_controls_tab();
		$this->start_controls_tab( 'dots_active_tab', [ 'label' => esc_html__( 'Active', 'md-nested-carousel' ), 'condition' => [ 'pagination' => 'bullets' ] ] );
		$this->add_control( 'dots_active_color', [
			'label'     => esc_html__( 'Active Color', 'md-nested-carousel' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}}' => '--md-n-carousel-dots-active-color: {{VALUE}}' ],
			'condition' => [ 'pagination' => 'bullets' ],
		] );
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control( Group_Control_Typography::get_type(), [
			'name'      => 'typography_fraction',
			'selector'  => '{{WRAPPER}} .swiper-pagination',
			'condition' => [ 'pagination' => 'fraction' ],
		] );

		$this->add_control( 'fraction_color', [
			'label'     => esc_html__( 'Color', 'md-nested-carousel' ),
			'type'      => Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .swiper-pagination' => 'color: {{VALUE}}' ],
			'condition' => [ 'pagination' => 'fraction' ],
		] );

		$this->add_responsive_control( 'pagination_spacing', [
			'label'      => esc_html__( 'Spacing from slides', 'md-nested-carousel' ),
			'type'       => Controls_Manager::SLIDER,
			'size_units' => [ 'px', 'em', 'rem', 'custom' ],
			'selectors'  => [ '{{WRAPPER}}' => '--md-n-carousel-pagination-spacing: {{SIZE}}{{UNIT}}' ],
			'separator'  => 'before',
		] );

		$this->end_controls_section();
	}

	// =========================================================================
	// PHP Render (frontend + editor server-side)
	// =========================================================================

	protected function render(): void {
		$settings  = $this->get_settings_for_display();
		$slides    = $settings['carousel_items'] ?? [];
		$total     = count( $slides );

		$this->num_of_carousel_items = $total;

		$has_autoplay = 'yes' === ( $settings['autoplay'] ?? '' );
		$direction    = $settings['direction'] ?? '';
		$show_nav     = $total > 1;

		$this->add_render_attribute( 'wrapper', [
			'class'                => [ 'md-n-carousel', 'swiper' ],
			'role'                 => 'region',
			'aria-roledescription' => 'carousel',
			'aria-label'           => esc_attr( $settings['carousel_name'] ?? '' ),
		] );

		if ( $direction ) {
			$this->add_render_attribute( 'wrapper', 'dir', $direction );
		}

		$this->add_render_attribute( 'swiper-wrapper', [
			'class'     => 'swiper-wrapper',
			'aria-live' => $has_autoplay ? 'off' : 'polite',
		] );
		?>
		<div <?php $this->print_render_attribute_string( 'wrapper' ); ?>>
			<div <?php $this->print_render_attribute_string( 'swiper-wrapper' ); ?>>
				<?php foreach ( $slides as $index => $slide ) :
					$n   = $index + 1;
					$key = $this->get_repeater_setting_key( 'slide', 'carousel_items', $index );
					$this->add_render_attribute( $key, [
						'class'                => 'swiper-slide',
						'data-slide'           => $n,
						'role'                 => 'group',
						'aria-roledescription' => 'slide',
						'aria-label'           => sprintf(
							esc_attr__( '%1$s of %2$s', 'md-nested-carousel' ),
							$n, $total
						),
					] );
					?>
					<div <?php $this->print_render_attribute_string( $key ); ?>>
						<?php $this->print_child( $index ); ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<?php if ( 'yes' === ( $settings['arrows'] ?? '' ) && $show_nav ) : ?>
			<div class="md-carousel-button md-carousel-button-prev" role="button" tabindex="0"
				aria-label="<?php esc_attr_e( 'Previous', 'md-nested-carousel' ); ?>">
				<?php $this->_render_icon( 'navigation_previous_icon', 'eicon-chevron-left' ); ?>
			</div>
			<div class="md-carousel-button md-carousel-button-next" role="button" tabindex="0"
				aria-label="<?php esc_attr_e( 'Next', 'md-nested-carousel' ); ?>">
				<?php $this->_render_icon( 'navigation_next_icon', 'eicon-chevron-right' ); ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $settings['pagination'] ) && $show_nav ) : ?>
			<div class="swiper-pagination"></div>
		<?php endif;
	}

	/**
	 * Render an icon control with a guaranteed fallback.
	 *
	 * `Icons_Manager::render_icon()` can silently output nothing when the
	 * control value is empty (e.g. user cleared it) — which left us with
	 * buttons that had only a background and no glyph. Force-output the
	 * fallback eicon class in that case.
	 */
	private function _render_icon( string $key, string $fallback ): void {
		$icon  = $this->get_settings_for_display( $key );
		$value = is_array( $icon ) ? ( $icon['value'] ?? '' ) : '';

		if ( is_string( $value ) && '' !== $value ) {
			// eicons / Font Awesome / any font-icon — render `<i>` directly.
			printf( '<i class="%s" aria-hidden="true"></i>', esc_attr( $value ) );
			return;
		}

		if ( is_array( $value ) ) {
			// Custom SVG upload — `value` is `[ 'url' => ..., 'id' => ... ]`.
			Icons_Manager::render_icon( $icon, [ 'aria-hidden' => 'true' ] );
			return;
		}

		printf( '<i class="%s" aria-hidden="true"></i>', esc_attr( $fallback ) );
	}

	// =========================================================================
	// JS template — editor live preview (Backbone/Underscore)
	// =========================================================================

	/**
	 * Shell rendered when a NEW slide is added via "+" in the panel.
	 * Elementor then injects the new child container into this shell.
	 */
	protected function content_template_single_repeater_item(): void {
		?>
		<#
		const uid_sri  = view.getIDInt().toString().substr( 0, 3 ),
			total_sri  = view.collection.length + 1,
			count_sri  = total_sri;

		view.addRenderAttribute( 'single-slide', {
			'class'                : 'swiper-slide',
			'data-slide'           : count_sri,
			'role'                 : 'group',
			'aria-roledescription' : 'slide',
			'aria-label'           : count_sri + ' <?php echo esc_js( __( 'of', 'md-nested-carousel' ) ); ?> ' + total_sri,
		}, null, true );
		#>
		<div {{{ view.getRenderAttributeString( 'single-slide' ) }}}></div>
		<?php
	}

	/**
	 * Full template rendered in the editor canvas.
	 * IMPORTANT: The .swiper-slide elements must be empty here —
	 * Elementor's interlace mechanism fills them with .e-con children.
	 */
	protected function content_template(): void {
		?>
		<# if ( settings.carousel_items && settings.carousel_items.length ) {
			const uid        = view.getIDInt().toString().substr( 0, 3 ),
				wrapperKey   = 'wrapper-'        + uid,
				innerKey     = 'swiper-wrapper-' + uid,
				hasAutoplay  = 'yes' === settings.autoplay,
				total        = settings.carousel_items.length,
				showNav      = total > 1;

			view.addRenderAttribute( wrapperKey, {
				'class'                : [ 'md-n-carousel', elementorFrontend.config.swiperClass ],
				'role'                 : 'region',
				'aria-roledescription' : 'carousel',
				'aria-label'           : settings.carousel_name,
			} );

			if ( settings.direction ) {
				view.addRenderAttribute( wrapperKey, 'dir', settings.direction );
			}

			view.addRenderAttribute( innerKey, {
				'class'     : 'swiper-wrapper',
				'aria-live' : hasAutoplay ? 'off' : 'polite',
			} );
		#>
		<div {{{ view.getRenderAttributeString( wrapperKey ) }}}>
			<div {{{ view.getRenderAttributeString( innerKey ) }}}>
				<# _.each( settings.carousel_items, function( slide, index ) {
					const n       = index + 1,
						slideKey  = 'slide-' + uid + '-' + n;

					view.addRenderAttribute( slideKey, {
						'class'                : 'swiper-slide',
						'data-slide'           : n,
						'role'                 : 'group',
						'aria-roledescription' : 'slide',
						'aria-label'           : n + ' <?php echo esc_js( __( 'of', 'md-nested-carousel' ) ); ?> ' + total,
					} );
				#>
					<div {{{ view.getRenderAttributeString( slideKey ) }}}></div>
				<# } ); #>
			</div>
		</div>

		<# if ( 'yes' === settings.arrows && showNav ) {
			var _iconHTML = function( iconObj, fallbackClass ) {
				var v = iconObj && iconObj.value;
				if ( typeof v === 'string' && v ) {
					return '<i class="' + _.escape( v ) + '" aria-hidden="true"></i>';
				}
				if ( v && typeof v === 'object' ) {
					var rendered = elementor.helpers.renderIcon( view, iconObj, { 'aria-hidden': true }, 'i', 'object' );
					if ( rendered && rendered.rendered ) return rendered.value;
				}
				return '<i class="' + fallbackClass + '" aria-hidden="true"></i>';
			};
		#>
			<div class="md-carousel-button md-carousel-button-prev" role="button" tabindex="0"
				aria-label="<?php echo esc_js( __( 'Previous', 'md-nested-carousel' ) ); ?>">
				{{{ _iconHTML( settings.navigation_previous_icon, 'eicon-chevron-left' ) }}}
			</div>
			<div class="md-carousel-button md-carousel-button-next" role="button" tabindex="0"
				aria-label="<?php echo esc_js( __( 'Next', 'md-nested-carousel' ) ); ?>">
				{{{ _iconHTML( settings.navigation_next_icon, 'eicon-chevron-right' ) }}}
			</div>
		<# } #>

		<# if ( settings.pagination && showNav ) { #>
			<div class="swiper-pagination"></div>
		<# } #>

		<# } #>
		<?php
	}
}
