<?php
/**
 * Testimonials custom post type.
 *
 * The reviews grid on the home page is driven by these posts rather than by
 * hand-built Elementor cards, so the client can add or retire a review without
 * editing the page. Elementor Pro's Loop Grid renders them through the
 * "Review Card" loop item template.
 *
 * Reviewer name → post title
 * Quote         → excerpt
 * Location      → ssr_testimonial_location
 * Service       → ssr_testimonial_service
 * Star rating   → ssr_testimonial_rating (rendered by [ssr_stars])
 * Order         → menu_order (Page Attributes)
 *
 * @package HelloElementorSunflowerStateRemodel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SSR_TESTIMONIAL_POST_TYPE = 'ssr_testimonial';

/**
 * Register the Testimonials post type.
 */
function ssr_register_testimonial_post_type() {
	register_post_type(
		SSR_TESTIMONIAL_POST_TYPE,
		array(
			'labels'              => array(
				'name'          => __( 'Testimonials', 'hello-elementor-sunflower-state-remodel' ),
				'singular_name' => __( 'Testimonial', 'hello-elementor-sunflower-state-remodel' ),
				'add_new_item'  => __( 'Add New Testimonial', 'hello-elementor-sunflower-state-remodel' ),
				'edit_item'     => __( 'Edit Testimonial', 'hello-elementor-sunflower-state-remodel' ),
				'all_items'     => __( 'All Testimonials', 'hello-elementor-sunflower-state-remodel' ),
				'menu_name'     => __( 'Testimonials', 'hello-elementor-sunflower-state-remodel' ),
			),
			'public'              => true,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'menu_position'       => 22,
			'menu_icon'           => 'dashicons-format-quote',
			'supports'            => array( 'title', 'excerpt', 'page-attributes' ),
			'rewrite'             => false,
		)
	);

	foreach ( array( 'ssr_testimonial_location', 'ssr_testimonial_service' ) as $key ) {
		register_post_meta(
			SSR_TESTIMONIAL_POST_TYPE,
			$key,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	register_post_meta(
		SSR_TESTIMONIAL_POST_TYPE,
		'ssr_testimonial_rating',
		array(
			'type'              => 'integer',
			'single'            => true,
			'show_in_rest'      => true,
			'default'           => 5,
			'sanitize_callback' => 'absint',
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}
add_action( 'init', 'ssr_register_testimonial_post_type' );

/**
 * Order the Testimonials list screen by menu_order, matching the front end.
 *
 * @param WP_Query $query Current query.
 */
function ssr_testimonial_admin_order( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( SSR_TESTIMONIAL_POST_TYPE === $query->get( 'post_type' ) && ! $query->get( 'orderby' ) ) {
		$query->set( 'orderby', 'menu_order' );
		$query->set( 'order', 'ASC' );
	}
}
add_action( 'pre_get_posts', 'ssr_testimonial_admin_order' );

/**
 * Review details meta box.
 */
function ssr_testimonial_add_meta_box() {
	add_meta_box(
		'ssr-testimonial-details',
		__( 'Review Details', 'hello-elementor-sunflower-state-remodel' ),
		'ssr_testimonial_render_meta_box',
		SSR_TESTIMONIAL_POST_TYPE,
		'side'
	);
}
add_action( 'add_meta_boxes', 'ssr_testimonial_add_meta_box' );

/**
 * Render the review details fields.
 *
 * @param WP_Post $post Current testimonial.
 */
function ssr_testimonial_render_meta_box( $post ) {
	wp_nonce_field( 'ssr_testimonial_save', 'ssr_testimonial_nonce' );

	$location = (string) get_post_meta( $post->ID, 'ssr_testimonial_location', true );
	$service  = (string) get_post_meta( $post->ID, 'ssr_testimonial_service', true );
	$rating   = (int) get_post_meta( $post->ID, 'ssr_testimonial_rating', true );
	$rating   = $rating ? $rating : 5;

	echo '<p><label for="ssr_testimonial_location"><strong>' . esc_html__( 'Neighbourhood', 'hello-elementor-sunflower-state-remodel' ) . '</strong></label>';
	printf(
		'<input type="text" id="ssr_testimonial_location" name="ssr_testimonial_location" value="%s" style="width:100%%" placeholder="Riverside" /></p>',
		esc_attr( $location )
	);

	echo '<p><label for="ssr_testimonial_service"><strong>' . esc_html__( 'Service', 'hello-elementor-sunflower-state-remodel' ) . '</strong></label>';
	printf(
		'<input type="text" id="ssr_testimonial_service" name="ssr_testimonial_service" value="%s" style="width:100%%" placeholder="Kitchen" /></p>',
		esc_attr( $service )
	);

	echo '<p><label for="ssr_testimonial_rating"><strong>' . esc_html__( 'Stars', 'hello-elementor-sunflower-state-remodel' ) . '</strong></label>';
	echo '<select id="ssr_testimonial_rating" name="ssr_testimonial_rating" style="width:100%">';
	for ( $i = 5; $i >= 1; $i-- ) {
		printf( '<option value="%d"%s>%d</option>', $i, selected( $i, $rating, false ), $i );
	}
	echo '</select></p>';

	echo '<p class="description">' . esc_html__( 'The quote itself goes in the Excerpt box.', 'hello-elementor-sunflower-state-remodel' ) . '</p>';
}

/**
 * Persist the review details.
 *
 * @param int $post_id Testimonial ID.
 */
function ssr_testimonial_save_meta_box( $post_id ) {
	if ( ! isset( $_POST['ssr_testimonial_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ssr_testimonial_nonce'] ) ), 'ssr_testimonial_save' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( array( 'ssr_testimonial_location', 'ssr_testimonial_service' ) as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
		}
	}

	if ( isset( $_POST['ssr_testimonial_rating'] ) ) {
		$rating = min( 5, max( 1, absint( wp_unslash( $_POST['ssr_testimonial_rating'] ) ) ) );
		update_post_meta( $post_id, 'ssr_testimonial_rating', $rating );
	}
}
add_action( 'save_post_' . SSR_TESTIMONIAL_POST_TYPE, 'ssr_testimonial_save_meta_box' );

/**
 * [ssr_stars] — the design's five-pointed star row.
 *
 * The design source draws each star as a 14px SVG rather than the ★ glyph,
 * whose shape and weight vary by platform font. Reads the current post's
 * rating so a four-star review renders four stars.
 *
 * @param array $atts Optional { rating: int }.
 * @return string
 */
function ssr_stars_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'rating' => '' ), $atts, 'ssr_stars' );

	$rating = '' !== $atts['rating']
		? absint( $atts['rating'] )
		: (int) get_post_meta( get_the_ID(), 'ssr_testimonial_rating', true );

	$rating = min( 5, max( 0, $rating ? $rating : 5 ) );

	$star = '<svg width="14" height="14" viewBox="0 0 16 16" fill="%s" aria-hidden="true"><path d="M8 1l1.8 3.6L14 5.4l-3 2.9.7 4.1L8 10.4l-3.7 2 .7-4.1L2 5.4l4.2-.8z"/></svg>';

	$out = '<span class="ssr-stars-row" role="img" aria-label="'
		. esc_attr( sprintf( /* translators: %d: star rating out of five */ __( '%d out of 5 stars', 'hello-elementor-sunflower-state-remodel' ), $rating ) )
		. '">';

	for ( $i = 1; $i <= 5; $i++ ) {
		$out .= sprintf( $star, $i <= $rating ? '#E9A825' : 'rgba(233,168,37,0.25)' );
	}

	return $out . '</span>';
}
add_shortcode( 'ssr_stars', 'ssr_stars_shortcode' );

/**
 * Show the review's service and location in the Testimonials list screen.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function ssr_testimonial_columns( $columns ) {
	$new = array();

	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['ssr_service']  = __( 'Service', 'hello-elementor-sunflower-state-remodel' );
			$new['ssr_location'] = __( 'Neighbourhood', 'hello-elementor-sunflower-state-remodel' );
			$new['ssr_rating']   = __( 'Stars', 'hello-elementor-sunflower-state-remodel' );
		}
	}

	return $new;
}
add_filter( 'manage_' . SSR_TESTIMONIAL_POST_TYPE . '_posts_columns', 'ssr_testimonial_columns' );

/**
 * Fill the custom Testimonials columns.
 *
 * @param string $column  Column key.
 * @param int    $post_id Testimonial ID.
 */
function ssr_testimonial_column_content( $column, $post_id ) {
	if ( 'ssr_service' === $column ) {
		echo esc_html( get_post_meta( $post_id, 'ssr_testimonial_service', true ) ?: '—' );
	}

	if ( 'ssr_location' === $column ) {
		echo esc_html( get_post_meta( $post_id, 'ssr_testimonial_location', true ) ?: '—' );
	}

	if ( 'ssr_rating' === $column ) {
		$rating = (int) get_post_meta( $post_id, 'ssr_testimonial_rating', true );
		echo esc_html( str_repeat( '★', $rating ) );
	}
}
add_action( 'manage_' . SSR_TESTIMONIAL_POST_TYPE . '_posts_custom_column', 'ssr_testimonial_column_content', 10, 2 );
