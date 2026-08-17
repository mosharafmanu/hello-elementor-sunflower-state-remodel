<?php
/**
 * Kitchen budget allocator.
 *
 * WHY THIS IS A CUSTOM WIDGET
 * ---------------------------
 * The design's "Where Kitchen Remodel Budgets Actually Go" section is a live
 * allocator: a total-budget slider drives eight percentage splits, each of
 * which recomputes its own dollar figure, and a summary paragraph rewrites
 * itself with four of those figures inline. Elementor Pro has no widget that
 * derives values from visitor input — the same justification as
 * `ssr-symptom-checker` and `ssr-gutter-calc`.
 *
 * The categories and percentages are the source's, exposed as a repeater so
 * the client can retune them without touching code.
 *
 * @package HelloElementorSunflowerStateRemodel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SSR_Budget_Allocator_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'ssr-budget-allocator';
	}

	public function get_title() {
		return esc_html__( 'Budget Allocator', 'hello-elementor-sunflower-state-remodel' );
	}

	public function get_icon() {
		return 'eicon-skill-bar';
	}

	public function get_categories() {
		return array( 'general' );
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => esc_html__( 'Allocator', 'hello-elementor-sunflower-state-remodel' ) ) );

		foreach ( array(
			'min'     => array( 'Minimum budget', 25000 ),
			'max'     => array( 'Maximum budget', 120000 ),
			'step'    => array( 'Step', 5000 ),
			'default' => array( 'Starting budget', 45000 ),
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

		$repeater = new \Elementor\Repeater();
		$repeater->add_control( 'label', array( 'label' => 'Category', 'type' => \Elementor\Controls_Manager::TEXT ) );
		$repeater->add_control( 'pct', array( 'label' => 'Percent', 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 10 ) );
		$repeater->add_control( 'color', array( 'label' => 'Colour', 'type' => \Elementor\Controls_Manager::COLOR ) );

		$this->add_control(
			'categories',
			array(
				'label'       => esc_html__( 'Categories', 'hello-elementor-sunflower-state-remodel' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ label }}} — {{{ pct }}}%',
			)
		);

		$this->add_control(
			'summary',
			array(
				'label'       => esc_html__( 'Summary sentence', 'hello-elementor-sunflower-state-remodel' ),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'description' => 'Tokens: {total} {cat1} {cat2} {cat3} {cat4}',
				'default'     => "Based on a {total} budget, you'd allocate roughly {cat1} on cabinets, {cat2} on labor, {cat3} on appliances, and {cat4} on countertops. These are real averages from Wichita kitchen remodels. Individual projects vary — a custom range hood or appliance tier shift can move the needle significantly.",
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$s    = $this->get_settings_for_display();
		$cats = $s['categories'] ?? array();

		if ( empty( $cats ) ) {
			return;
		}

		$min = (int) ( $s['min'] ?? 25000 );
		$max = (int) ( $s['max'] ?? 120000 );
		$stp = (int) ( $s['step'] ?? 5000 );
		$def = (int) ( $s['default'] ?? 45000 );

		$config = array(
			'pcts'    => array_map( static function ( $c ) {
				return (float) $c['pct'];
			}, $cats ),
			'summary' => $s['summary'],
		);

		$id = 'ssr-ba-range-' . $this->get_id();
		?>
		<div class="ssr-alloc" data-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
			<div class="ssr-alloc-field">
				<label class="ssr-alloc-total" for="<?php echo esc_attr( $id ); ?>">
					<span class="ssr-alloc-total-value"></span>
					<?php esc_html_e( 'total budget', 'hello-elementor-sunflower-state-remodel' ); ?>
				</label>
				<input type="range" class="ssr-alloc-range" id="<?php echo esc_attr( $id ); ?>"
					min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>"
					step="<?php echo esc_attr( $stp ); ?>" value="<?php echo esc_attr( $def ); ?>" />
				<div class="ssr-alloc-scale">
					<span>$<?php echo esc_html( number_format( $min ) ); ?></span>
					<span>$<?php echo esc_html( number_format( $max ) ); ?></span>
				</div>
			</div>

			<div class="ssr-alloc-bar" role="img"
				aria-label="<?php esc_attr_e( 'Proportional split of the remodel budget', 'hello-elementor-sunflower-state-remodel' ); ?>">
				<?php foreach ( $cats as $c ) : ?>
					<span class="ssr-alloc-seg"
						style="width:<?php echo esc_attr( (float) $c['pct'] ); ?>%;background:<?php echo esc_attr( $c['color'] ); ?>"
						title="<?php echo esc_attr( $c['label'] . ': ' . $c['pct'] . '%' ); ?>"></span>
				<?php endforeach; ?>
			</div>

			<div class="ssr-alloc-legend">
				<?php foreach ( $cats as $i => $c ) : ?>
					<div class="ssr-alloc-row">
						<span class="ssr-alloc-key">
							<span class="ssr-alloc-dot" style="background:<?php echo esc_attr( $c['color'] ); ?>"></span>
							<span class="ssr-alloc-label"><?php echo esc_html( $c['label'] ); ?></span>
						</span>
						<span class="ssr-alloc-val">
							<span class="ssr-alloc-amount" data-pct="<?php echo esc_attr( (float) $c['pct'] ); ?>"></span>
							<span class="ssr-alloc-pct">(<?php echo esc_html( (float) $c['pct'] ); ?>%)</span>
						</span>
					</div>
				<?php endforeach; ?>
			</div>

			<p class="ssr-alloc-summary" aria-live="polite"></p>
		</div>
		<?php
	}
}
