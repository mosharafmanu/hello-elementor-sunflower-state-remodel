<?php
/**
 * Before / After comparison slider.
 *
 * WHY THIS IS A CUSTOM WIDGET
 * ---------------------------
 * The design's "See the difference" section is a pointer-driven image
 * comparison slider: the user drags a handle and the before image is revealed
 * by an animated clip. Elementor Pro 4.2 ships no image-comparison widget —
 * `wp eval` over `widgets_manager->get_widget_types()` on this install returns
 * no compare/before/after widget of any kind, and the nearest natives
 * (image-carousel, media-carousel, slides) cross-fade or page between whole
 * images rather than clipping one over the other under pointer control.
 * The behaviour therefore cannot be produced with a native widget, which is
 * exactly the exception CLAUDE.md allows.
 *
 * Everything else on this build uses native widgets.
 *
 * @package HelloElementorSunflowerStateRemodel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SSR_Before_After_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'ssr-before-after';
	}

	public function get_title() {
		return esc_html__( 'Before / After Slider', 'hello-elementor-sunflower-state-remodel' );
	}

	public function get_icon() {
		return 'eicon-image-before-after';
	}

	public function get_categories() {
		return array( 'general' );
	}

	public function get_keywords() {
		return array( 'before', 'after', 'compare', 'slider' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'content',
			array( 'label' => esc_html__( 'Images', 'hello-elementor-sunflower-state-remodel' ) )
		);

		$this->add_control(
			'before_image',
			array(
				'label'   => esc_html__( 'Before image', 'hello-elementor-sunflower-state-remodel' ),
				'type'    => \Elementor\Controls_Manager::MEDIA,
				'default' => array( 'url' => \Elementor\Utils::get_placeholder_image_src() ),
			)
		);

		$this->add_control(
			'before_alt',
			array(
				'label'   => esc_html__( 'Before alt text', 'hello-elementor-sunflower-state-remodel' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '',
			)
		);

		$this->add_control(
			'after_image',
			array(
				'label'   => esc_html__( 'After image', 'hello-elementor-sunflower-state-remodel' ),
				'type'    => \Elementor\Controls_Manager::MEDIA,
				'default' => array( 'url' => \Elementor\Utils::get_placeholder_image_src() ),
			)
		);

		$this->add_control(
			'after_alt',
			array(
				'label'   => esc_html__( 'After alt text', 'hello-elementor-sunflower-state-remodel' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '',
			)
		);

		$this->add_control(
			'before_label',
			array(
				'label'   => esc_html__( 'Before label', 'hello-elementor-sunflower-state-remodel' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'BEFORE',
			)
		);

		$this->add_control(
			'after_label',
			array(
				'label'   => esc_html__( 'After label', 'hello-elementor-sunflower-state-remodel' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'AFTER',
			)
		);

		$this->add_control(
			'hint',
			array(
				'label'   => esc_html__( 'Drag hint', 'hello-elementor-sunflower-state-remodel' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '← DRAG TO COMPARE →',
			)
		);

		$this->add_responsive_control(
			'height',
			array(
				'label'      => esc_html__( 'Height', 'hello-elementor-sunflower-state-remodel' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				// `custom` lets the design's literal clamp() through — the
				// source sizes this slider at clamp(300px, 38vw, 520px), which
				// a px/vh slider cannot express.
				'size_units' => array( 'px', 'vh', 'custom' ),
				'range'      => array( 'px' => array( 'min' => 200, 'max' => 900 ) ),
				'default'    => array( 'unit' => 'px', 'size' => 520 ),
				'selectors'  => array(
					'{{WRAPPER}} .ssr-ba' => 'height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();

		$before = $s['before_image']['url'] ?? '';
		$after  = $s['after_image']['url'] ?? '';

		if ( ! $before || ! $after ) {
			return;
		}
		?>
		<div class="ssr-ba" role="group" aria-label="<?php esc_attr_e( 'Before and after comparison', 'hello-elementor-sunflower-state-remodel' ); ?>">
			<img class="ssr-ba-after" src="<?php echo esc_url( $after ); ?>" alt="<?php echo esc_attr( $s['after_alt'] ); ?>" />
			<div class="ssr-ba-clip">
				<img class="ssr-ba-before" src="<?php echo esc_url( $before ); ?>" alt="<?php echo esc_attr( $s['before_alt'] ); ?>" />
				<span class="ssr-ba-tint"></span>
			</div>
			<span class="ssr-ba-label ssr-ba-label-before"><?php echo esc_html( $s['before_label'] ); ?></span>
			<span class="ssr-ba-label ssr-ba-label-after"><?php echo esc_html( $s['after_label'] ); ?></span>
			<span class="ssr-ba-line"></span>
			<button type="button" class="ssr-ba-handle"
				role="slider"
				aria-label="<?php esc_attr_e( 'Reveal before image', 'hello-elementor-sunflower-state-remodel' ); ?>"
				aria-valuemin="0" aria-valuemax="100" aria-valuenow="50">
				<svg width="20" height="13" viewBox="0 0 22 14" fill="none" aria-hidden="true">
					<path d="M6 2L2 7l4 5" stroke="#14294D" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
					<path d="M16 2l4 5-4 5" stroke="#14294D" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</button>
			<span class="ssr-ba-hint"><?php echo esc_html( $s['hint'] ); ?></span>
		</div>
		<?php
	}
}
