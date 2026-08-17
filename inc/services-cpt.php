<?php
/**
 * Services custom post type.
 *
 * The "What are you looking to fix?" grid on the home page is driven by these
 * posts rather than by hand-built Elementor containers, so the client can add,
 * reorder, or retire a service without editing the page. Elementor Pro's Loop
 * Grid widget renders them through the "Service Card" loop item template.
 *
 * Card title  → post title
 * Card image  → featured image
 * Card blurb  → excerpt
 * Card link   → ssr_service_link custom field (the matching service page)
 * Card order  → menu_order (Page Attributes)
 *
 * @package HelloElementorSunflowerStateRemodel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SSR_SERVICE_POST_TYPE = 'ssr_service';
const SSR_SERVICE_LINK_KEY  = 'ssr_service_link';

/**
 * Register the Services post type.
 *
 * Not publicly queryable: each card points at the existing service page, so a
 * separate single-service permalink would only duplicate that content.
 */
function ssr_register_service_post_type() {
	register_post_type(
		SSR_SERVICE_POST_TYPE,
		array(
			'labels'              => array(
				'name'               => __( 'Services', 'hello-elementor-sunflower-state-remodel' ),
				'singular_name'      => __( 'Service', 'hello-elementor-sunflower-state-remodel' ),
				'add_new_item'       => __( 'Add New Service', 'hello-elementor-sunflower-state-remodel' ),
				'edit_item'          => __( 'Edit Service', 'hello-elementor-sunflower-state-remodel' ),
				'new_item'           => __( 'New Service', 'hello-elementor-sunflower-state-remodel' ),
				'view_item'          => __( 'View Service', 'hello-elementor-sunflower-state-remodel' ),
				'search_items'       => __( 'Search Services', 'hello-elementor-sunflower-state-remodel' ),
				'not_found'          => __( 'No services found', 'hello-elementor-sunflower-state-remodel' ),
				'all_items'          => __( 'All Services', 'hello-elementor-sunflower-state-remodel' ),
				'menu_name'          => __( 'Services', 'hello-elementor-sunflower-state-remodel' ),
			),
			'public'              => true,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'menu_position'       => 21,
			'menu_icon'           => 'dashicons-hammer',
			'hierarchical'        => false,
			'supports'            => array( 'title', 'excerpt', 'thumbnail', 'page-attributes' ),
			'rewrite'             => false,
		)
	);

	register_post_meta(
		SSR_SERVICE_POST_TYPE,
		SSR_SERVICE_LINK_KEY,
		array(
			'type'              => 'string',
			'description'       => 'URL the service card links to.',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'esc_url_raw',
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}
add_action( 'init', 'ssr_register_service_post_type' );

/**
 * Order the Services list screen by menu_order, matching the front-end grid.
 */
function ssr_service_admin_order( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( SSR_SERVICE_POST_TYPE === $query->get( 'post_type' ) && ! $query->get( 'orderby' ) ) {
		$query->set( 'orderby', 'menu_order' );
		$query->set( 'order', 'ASC' );
	}
}
add_action( 'pre_get_posts', 'ssr_service_admin_order' );

/**
 * "Card link" meta box — a page picker, so the client never types a URL.
 */
function ssr_service_add_meta_box() {
	add_meta_box(
		'ssr-service-link',
		__( 'Card Link', 'hello-elementor-sunflower-state-remodel' ),
		'ssr_service_render_meta_box',
		SSR_SERVICE_POST_TYPE,
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'ssr_service_add_meta_box' );

/**
 * Render the card-link page picker.
 *
 * @param WP_Post $post Current service post.
 */
function ssr_service_render_meta_box( $post ) {
	wp_nonce_field( 'ssr_service_link_save', 'ssr_service_link_nonce' );

	$current = (string) get_post_meta( $post->ID, SSR_SERVICE_LINK_KEY, true );
	$pages   = get_pages( array( 'sort_column' => 'menu_order,post_title' ) );

	echo '<p>' . esc_html__( 'Where this card should go when clicked.', 'hello-elementor-sunflower-state-remodel' ) . '</p>';
	echo '<select name="ssr_service_link" style="width:100%">';
	echo '<option value="">' . esc_html__( '— none —', 'hello-elementor-sunflower-state-remodel' ) . '</option>';

	$matched = false;

	foreach ( $pages as $page ) {
		$url = get_permalink( $page->ID );
		printf(
			'<option value="%s"%s>%s</option>',
			esc_attr( $url ),
			selected( $url, $current, false ),
			esc_html( $page->post_title )
		);
		if ( $url === $current ) {
			$matched = true;
		}
	}

	// Keep any hand-entered or off-site URL selectable rather than silently dropping it.
	if ( '' !== $current && ! $matched ) {
		printf( '<option value="%s" selected>%s</option>', esc_attr( $current ), esc_html( $current ) );
	}

	echo '</select>';
}

/**
 * Persist the card link.
 *
 * @param int $post_id Service post ID.
 */
function ssr_service_save_meta_box( $post_id ) {
	if ( ! isset( $_POST['ssr_service_link_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ssr_service_link_nonce'] ) ), 'ssr_service_link_save' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$url = isset( $_POST['ssr_service_link'] ) ? esc_url_raw( wp_unslash( $_POST['ssr_service_link'] ) ) : '';

	if ( '' === $url ) {
		delete_post_meta( $post_id, SSR_SERVICE_LINK_KEY );
	} else {
		update_post_meta( $post_id, SSR_SERVICE_LINK_KEY, $url );
	}
}
add_action( 'save_post_' . SSR_SERVICE_POST_TYPE, 'ssr_service_save_meta_box' );

/**
 * Show the card image and link in the Services list screen.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function ssr_service_columns( $columns ) {
	$new = array();

	foreach ( $columns as $key => $label ) {
		if ( 'title' === $key ) {
			$new['ssr_thumb'] = __( 'Image', 'hello-elementor-sunflower-state-remodel' );
		}
		$new[ $key ] = $label;
	}

	$new['ssr_link'] = __( 'Links to', 'hello-elementor-sunflower-state-remodel' );

	return $new;
}
add_filter( 'manage_' . SSR_SERVICE_POST_TYPE . '_posts_columns', 'ssr_service_columns' );

/**
 * Fill the custom Services columns.
 *
 * @param string $column  Column key.
 * @param int    $post_id Service post ID.
 */
function ssr_service_column_content( $column, $post_id ) {
	if ( 'ssr_thumb' === $column ) {
		echo has_post_thumbnail( $post_id )
			? get_the_post_thumbnail( $post_id, array( 60, 40 ), array( 'style' => 'border-radius:3px;object-fit:cover;' ) )
			: '—';
	}

	if ( 'ssr_link' === $column ) {
		$url = (string) get_post_meta( $post_id, SSR_SERVICE_LINK_KEY, true );
		echo $url ? '<a href="' . esc_url( $url ) . '">' . esc_html( wp_parse_url( $url, PHP_URL_PATH ) ) . '</a>' : '—';
	}
}
add_action( 'manage_' . SSR_SERVICE_POST_TYPE . '_posts_custom_column', 'ssr_service_column_content', 10, 2 );
