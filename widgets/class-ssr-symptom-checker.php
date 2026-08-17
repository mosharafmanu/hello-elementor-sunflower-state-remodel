<?php
/**
 * Symptom checker with a live verdict panel.
 *
 * WHY THIS IS A CUSTOM WIDGET
 * ---------------------------
 * The design's "How to tell when your windows need replacing" section is a
 * stateful self-assessment: the visitor ticks symptoms and a verdict card
 * recomputes its heading, body, colour and count on every change, revealing a
 * call button once a threshold is crossed. Elementor Pro has no widget that
 * derives content from a running tally of user selections — its interactive
 * natives (accordion, tabs, toggle) only show and hide fixed panels. This is
 * the "special functionality requiring custom JavaScript" case CLAUDE.md
 * allows.
 *
 * @package HelloElementorSunflowerStateRemodel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SSR_Symptom_Checker_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'ssr-symptom-checker';
	}

	public function get_title() {
		return esc_html__( 'Symptom Checker', 'hello-elementor-sunflower-state-remodel' );
	}

	public function get_icon() {
		return 'eicon-checkbox';
	}

	public function get_categories() {
		return array( 'general' );
	}

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => esc_html__( 'Symptoms', 'hello-elementor-sunflower-state-remodel' ) ) );

		$repeater = new \Elementor\Repeater();
		$repeater->add_control(
			'text',
			array(
				'label'   => esc_html__( 'Symptom', 'hello-elementor-sunflower-state-remodel' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '',
			)
		);

		$this->add_control(
			'symptoms',
			array(
				'label'       => esc_html__( 'Symptoms', 'hello-elementor-sunflower-state-remodel' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ text }}}',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section( 'verdicts', array( 'label' => esc_html__( 'Verdicts', 'hello-elementor-sunflower-state-remodel' ) ) );

		foreach ( array(
			'low'  => array( 'Probably Fine', 'Monitor these symptoms and re-evaluate at your next energy bill cycle.', '#3A7D44' ),
			'mid'  => array( 'Worth a Professional Look', 'These symptoms together suggest you’re losing energy and comfort. A free measurement visit makes sense.', '#E9A825' ),
			'high' => array( 'Time to Replace', 'Multiple symptoms compounding means your windows are costing you money every month. A full replacement will pay back.', '#C4462F' ),
		) as $key => $default ) {
			$this->add_control(
				$key . '_title',
				array( 'label' => ucfirst( $key ) . ' title', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => $default[0] )
			);
			$this->add_control(
				$key . '_desc',
				array( 'label' => ucfirst( $key ) . ' description', 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => $default[1] )
			);
			$this->add_control(
				$key . '_color',
				array( 'label' => ucfirst( $key ) . ' colour', 'type' => \Elementor\Controls_Manager::COLOR, 'default' => $default[2] )
			);
		}

		$this->add_control(
			'cta_text',
			array( 'label' => esc_html__( 'CTA (shown from the mid verdict up)', 'hello-elementor-sunflower-state-remodel' ), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'CALL (316) 708-4630 — FREE VISIT' )
		);
		$this->add_control(
			'cta_link',
			array( 'label' => esc_html__( 'CTA link', 'hello-elementor-sunflower-state-remodel' ), 'type' => \Elementor\Controls_Manager::URL, 'default' => array( 'url' => 'tel:3167084630' ) )
		);

		$this->end_controls_section();
	}

	protected function render() {
		$s        = $this->get_settings_for_display();
		$symptoms = $s['symptoms'] ?? array();

		if ( empty( $symptoms ) ) {
			return;
		}

		$config = array(
			'total'    => count( $symptoms ),
			'verdicts' => array(
				'low'  => array( 'title' => $s['low_title'],  'desc' => $s['low_desc'],  'color' => $s['low_color'] ),
				'mid'  => array( 'title' => $s['mid_title'],  'desc' => $s['mid_desc'],  'color' => $s['mid_color'] ),
				'high' => array( 'title' => $s['high_title'], 'desc' => $s['high_desc'], 'color' => $s['high_color'] ),
			),
		);

		$uid = 'ssr-sc-' . $this->get_id();
		?>
		<div class="ssr-sc" id="<?php echo esc_attr( $uid ); ?>"
			data-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">

			<div class="ssr-sc-list" role="group" aria-label="<?php esc_attr_e( 'Symptoms you are experiencing', 'hello-elementor-sunflower-state-remodel' ); ?>">
				<?php foreach ( $symptoms as $i => $item ) : ?>
					<label class="ssr-sc-item">
						<input type="checkbox" class="ssr-sc-input" value="<?php echo esc_attr( $i ); ?>" />
						<span class="ssr-sc-box" aria-hidden="true"></span>
						<span class="ssr-sc-text"><?php echo esc_html( $item['text'] ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>

			<div class="ssr-sc-verdict" aria-live="polite">
				<div class="ssr-sc-verdict-head">
					<span class="ssr-sc-dot" aria-hidden="true"></span>
					<span class="ssr-sc-label"><?php esc_html_e( 'YOUR VERDICT', 'hello-elementor-sunflower-state-remodel' ); ?></span>
				</div>
				<h3 class="ssr-sc-title"></h3>
				<p class="ssr-sc-desc"></p>
				<div class="ssr-sc-count">
					<span class="ssr-sc-num">0</span>
					<span class="ssr-sc-of">of <?php echo (int) $config['total']; ?> symptoms selected</span>
				</div>
				<a class="ssr-sc-cta" href="<?php echo esc_url( $s['cta_link']['url'] ?? 'tel:3167084630' ); ?>" hidden>
					<?php echo esc_html( $s['cta_text'] ); ?>
				</a>
			</div>
		</div>
		<?php
	}
}
