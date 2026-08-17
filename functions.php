<?php
/**
 * Hello Elementor Sunflower State Remodel — child theme functions.
 *
 * All custom CSS/JS for this project lives here or in style.css.
 * The Hello Elementor parent theme is never modified.
 *
 * @package HelloElementorSunflowerStateRemodel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SSR_THEME_VERSION', '1.12.5' );

/**
 * Services custom post type — drives the "What are you looking to fix?" grid.
 */
require_once get_stylesheet_directory() . '/inc/services-cpt.php';

/**
 * Testimonials custom post type — drives the reviews grid.
 */
require_once get_stylesheet_directory() . '/inc/testimonials-cpt.php';

/**
 * Enqueue parent + child styles and the Google Fonts used in the Figma design.
 */
function ssr_enqueue_styles() {
	wp_enqueue_style(
		'hello-elementor-parent',
		get_template_directory_uri() . '/style.css',
		array(),
		SSR_THEME_VERSION
	);

	// Anton (display), Barlow Semi Condensed (UI), Barlow (body) — from the design source.
	wp_enqueue_style(
		'ssr-google-fonts',
		'https://fonts.googleapis.com/css2?family=Anton&family=Barlow:wght@300;400;500;600;700&family=Barlow+Semi+Condensed:wght@400;600;700&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'hello-elementor-child',
		get_stylesheet_directory_uri() . '/style.css',
		array( 'hello-elementor-parent', 'ssr-google-fonts' ),
		SSR_THEME_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'ssr_enqueue_styles', 20 );

/**
 * Preconnect to the Google Fonts hosts so the display font paints sooner.
 */
function ssr_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = array( 'href' => 'https://fonts.googleapis.com' );
		$urls[] = array(
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => 'anonymous',
		);
	}

	return $urls;
}
add_filter( 'wp_resource_hints', 'ssr_resource_hints', 10, 2 );

/**
 * Toggle the .ssr-scrolled class on the fixed header past 72px of scroll.
 *
 * The design's header is transparent over the hero and switches to charcoal
 * once scrolled — Elementor has no native scroll-state trigger for this, so a
 * small inline listener handles it. No custom widget is involved.
 */
function ssr_header_scroll_script() {
	?>
	<script>
	( function () {
		function init() {
			var header = document.querySelector( '.ssr-header' );
			if ( ! header ) {
				return;
			}
			function onScroll() {
				header.classList.toggle( 'ssr-scrolled', window.scrollY > 72 );
			}
			window.addEventListener( 'scroll', onScroll, { passive: true } );
			onScroll();
		}
		if ( 'loading' === document.readyState ) {
			document.addEventListener( 'DOMContentLoaded', init );
		} else {
			init();
		}
	} )();
	</script>
	<?php
}
add_action( 'wp_footer', 'ssr_header_scroll_script' );

/**
 * Register the Anton / Barlow families with Elementor so they are selectable
 * in Site Settings and render without an extra Google Fonts request.
 */
function ssr_register_elementor_fonts( $fonts ) {
	$fonts['Anton']                 = 'googlefonts';
	$fonts['Barlow']                = 'googlefonts';
	$fonts['Barlow Semi Condensed'] = 'googlefonts';

	return $fonts;
}
add_filter( 'elementor/fonts/additional_fonts', 'ssr_register_elementor_fonts' );

/**
 * Register the two custom widgets this build needs.
 *
 * Both cover behaviour Elementor Pro has no widget for — a pointer-driven
 * image comparison slider, and a self-assessment whose verdict is computed
 * from the visitor's running selection. Each class file documents why. Every
 * other section on the site uses stock Elementor Pro widgets.
 */
function ssr_register_widgets( $widgets_manager ) {
	require_once get_stylesheet_directory() . '/widgets/class-ssr-before-after.php';
	require_once get_stylesheet_directory() . '/widgets/class-ssr-symptom-checker.php';
	require_once get_stylesheet_directory() . '/widgets/class-ssr-gutter-calc.php';
	require_once get_stylesheet_directory() . '/widgets/class-ssr-budget-allocator.php';
	$widgets_manager->register( new \SSR_Before_After_Widget() );
	$widgets_manager->register( new \SSR_Symptom_Checker_Widget() );
	$widgets_manager->register( new \SSR_Gutter_Calc_Widget() );
	$widgets_manager->register( new \SSR_Budget_Allocator_Widget() );
}
add_action( 'elementor/widgets/register', 'ssr_register_widgets' );

/**
 * Front-end script for the before/after slider.
 */
function ssr_enqueue_widget_scripts() {
	wp_enqueue_script(
		'ssr-before-after',
		get_stylesheet_directory_uri() . '/assets/before-after.js',
		array(),
		SSR_THEME_VERSION,
		true
	);

	wp_enqueue_script(
		'ssr-symptom-checker',
		get_stylesheet_directory_uri() . '/assets/symptom-checker.js',
		array(),
		SSR_THEME_VERSION,
		true
	);

	wp_enqueue_script(
		'ssr-gutter-calc',
		get_stylesheet_directory_uri() . '/assets/gutter-calc.js',
		array(),
		SSR_THEME_VERSION,
		true
	);

	wp_enqueue_script(
		'ssr-budget-allocator',
		get_stylesheet_directory_uri() . '/assets/budget-allocator.js',
		array(),
		SSR_THEME_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'ssr_enqueue_widget_scripts' );
add_action( 'elementor/preview/enqueue_scripts', 'ssr_enqueue_widget_scripts' );

/**
 * Site-wide sticky mobile CTA bar (call / quote), shown under 900px.
 *
 * Part of the design's global chrome rather than any single page, so it is
 * output by the theme instead of being rebuilt in every Elementor template.
 */
function ssr_mobile_cta_bar() {
	?>
	<div class="ssr-mobile-cta">
		<a class="ssr-call" href="tel:3167084630">Call (316) 708-4630</a>
		<a class="ssr-quote" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Get a Quote</a>
	</div>
	<?php
}
add_action( 'wp_body_open', 'ssr_mobile_cta_bar' );
