<?php
/**
 * Gutter sizing calculator.
 *
 * WHY THIS IS A CUSTOM WIDGET
 * ---------------------------
 * The design's "Sizing Gutters for Kansas Rainfall" section is a live
 * calculator: a roof-area slider and a pitch selector feed an arithmetic model
 * that recomputes the recommended gutter size, downspout count, linear-footage
 * estimate and a written verdict on every input change. Elementor Pro has no
 * widget that computes derived values from visitor input — its interactive
 * natives (tabs, accordion, toggle) only show and hide authored panels. This
 * is the "special functionality requiring custom JavaScript" case CLAUDE.md
 * allows, and it is the same justification as the symptom checker.
 *
 * The model is the one in src/pages/Gutters.tsx, unchanged:
 *   pitchFactor  low 0.9 | medium 1.0 | steep 1.15
 *   drainageLoad = sqft * pitchFactor
 *   needsSix     = drainageLoad > 1400
 *   linearFeet   = round(sqrt(sqft) * 4.2)
 *   downspouts   = ceil(linearFeet / 37)
 *
 * @package HelloElementorSunflowerStateRemodel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SSR_Gutter_Calc_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'ssr-gutter-calc';
	}

	public function get_title() {
		return esc_html__( 'Gutter Sizing Calculator', 'hello-elementor-sunflower-state-remodel' );
	}

	public function get_icon() {
		return 'eicon-slider-push';
	}

	public function get_categories() {
		return array( 'general' );
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => esc_html__( 'Calculator', 'hello-elementor-sunflower-state-remodel' ) ) );

		foreach ( array(
			'min'     => array( 'Minimum roof area (sq ft)', 800 ),
			'max'     => array( 'Maximum roof area (sq ft)', 4000 ),
			'step'    => array( 'Step (sq ft)', 100 ),
			'default' => array( 'Starting roof area (sq ft)', 1800 ),
		) as $key => $conf ) {
			$this->add_control(
				$key,
				array(
					'label'   => esc_html__( $conf[0], 'hello-elementor-sunflower-state-remodel' ),
					'type'    => \Elementor\Controls_Manager::NUMBER,
					'default' => $conf[1],
				)
			);
		}

		$this->add_control(
			'fine_print',
			array(
				'label'   => esc_html__( 'Fine print', 'hello-elementor-sunflower-state-remodel' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => 'These are estimates. We calculate exact sizing at the estimate based on your actual roof geometry.',
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$s   = $this->get_settings_for_display();
		$min = (int) ( $s['min'] ?? 800 );
		$max = (int) ( $s['max'] ?? 4000 );
		$stp = (int) ( $s['step'] ?? 100 );
		$def = (int) ( $s['default'] ?? 1800 );

		$pitches = array(
			'low'    => 'Low (2–4/12)',
			'medium' => 'Medium (5–8/12)',
			'steep'  => 'Steep (9+/12)',
		);
		?>
		<div class="ssr-gcalc" data-min="<?php echo esc_attr( $min ); ?>" data-max="<?php echo esc_attr( $max ); ?>">
			<div class="ssr-gcalc-controls">
				<div class="ssr-gcalc-field">
					<label class="ssr-gcalc-label" for="<?php echo esc_attr( 'ssr-gcalc-range-' . $this->get_id() ); ?>">
						<?php esc_html_e( 'Roof Square Footage:', 'hello-elementor-sunflower-state-remodel' ); ?>
						<span class="ssr-gcalc-sqft"></span>
					</label>
					<input type="range" class="ssr-gcalc-range"
						id="<?php echo esc_attr( 'ssr-gcalc-range-' . $this->get_id() ); ?>"
						min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>"
						step="<?php echo esc_attr( $stp ); ?>" value="<?php echo esc_attr( $def ); ?>" />
					<div class="ssr-gcalc-scale">
						<span><?php echo esc_html( number_format( $min ) ); ?> sq ft</span>
						<span><?php echo esc_html( number_format( $max ) ); ?> sq ft</span>
					</div>
				</div>

				<div class="ssr-gcalc-field">
					<span class="ssr-gcalc-label"><?php esc_html_e( 'Roof Pitch', 'hello-elementor-sunflower-state-remodel' ); ?></span>
					<div class="ssr-gcalc-pitch" role="radiogroup" aria-label="<?php esc_attr_e( 'Roof pitch', 'hello-elementor-sunflower-state-remodel' ); ?>">
						<?php foreach ( $pitches as $key => $label ) : ?>
							<button type="button" class="ssr-gcalc-pitch-btn<?php echo 'medium' === $key ? ' is-active' : ''; ?>"
								data-pitch="<?php echo esc_attr( $key ); ?>"
								role="radio" aria-checked="<?php echo 'medium' === $key ? 'true' : 'false'; ?>">
								<?php echo esc_html( $label ); ?>
							</button>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<div class="ssr-gcalc-out" aria-live="polite">
				<p class="ssr-gcalc-out-h"><?php esc_html_e( 'Recommendation for Your Home', 'hello-elementor-sunflower-state-remodel' ); ?></p>
				<div class="ssr-gcalc-stats">
					<div class="ssr-gcalc-stat">
						<p class="ssr-gcalc-num" data-out="size"></p>
						<p class="ssr-gcalc-cap"><?php esc_html_e( 'K-Style Gutter', 'hello-elementor-sunflower-state-remodel' ); ?></p>
					</div>
					<div class="ssr-gcalc-stat">
						<p class="ssr-gcalc-num" data-out="downspouts"></p>
						<p class="ssr-gcalc-cap"><?php esc_html_e( 'Downspouts', 'hello-elementor-sunflower-state-remodel' ); ?></p>
					</div>
					<div class="ssr-gcalc-stat">
						<p class="ssr-gcalc-num" data-out="linear"></p>
						<p class="ssr-gcalc-cap"><?php esc_html_e( 'Linear Feet Est.', 'hello-elementor-sunflower-state-remodel' ); ?></p>
					</div>
				</div>
				<div class="ssr-gcalc-note">
					<p class="ssr-gcalc-verdict" data-out="verdict"></p>
					<p class="ssr-gcalc-fine"><?php echo esc_html( $s['fine_print'] ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}
}
