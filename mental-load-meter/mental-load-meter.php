<?php
/**
 * Plugin Name:       Mental Load Meter
 * Plugin URI:        https://konrad.edgeone.dev/
 * Description:       Add reading time and a 1 to 5 mental load score to your posts, so readers know what they are walking into.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Konrad Sroka
 * Author URI:        https://konrad.edgeone.dev/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mental-load-meter
 *
 * @package MentalLoadMeter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MLM_VERSION', '1.0.0' );
define( 'MLM_FILE', __FILE__ );
define( 'MLM_URL', plugin_dir_url( __FILE__ ) );

/**
 * Post types that get the meter.
 *
 * @since 1.0.0
 *
 * @return array List of post type slugs.
 */
function mlm_post_types() {
	/**
	 * Filters the post types the meter applies to.
	 *
	 * @since 1.0.0
	 *
	 * @param array $post_types List of post type slugs.
	 */
	return (array) apply_filters( 'mlm_post_types', array( 'post', 'page' ) );
}

/**
 * The five mental load levels.
 *
 * @since 1.0.0
 *
 * @return array Level number => label.
 */
function mlm_levels() {
	return array(
		1 => __( 'Light, skim it', 'mental-load-meter' ),
		2 => __( 'Easy read', 'mental-load-meter' ),
		3 => __( 'Needs focus', 'mental-load-meter' ),
		4 => __( 'Heavy, grab a coffee', 'mental-load-meter' ),
		5 => __( "Dense, you'll reread parts", 'mental-load-meter' ),
	);
}

/**
 * Registers the two post meta fields.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mlm_register_meta() {
	$auth = static function ( $allowed, $meta_key, $post_id ) {
		return current_user_can( 'edit_post', $post_id );
	};

	foreach ( mlm_post_types() as $post_type ) {
		register_post_meta(
			$post_type,
			'_mlm_level',
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'show_in_rest'      => true,
				'sanitize_callback' => 'mlm_sanitize_level',
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			$post_type,
			'_mlm_minutes',
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'show_in_rest'      => true,
				'sanitize_callback' => 'mlm_sanitize_minutes',
				'auth_callback'     => $auth,
			)
		);
	}
}
add_action( 'init', 'mlm_register_meta' );

/**
 * Keeps the level between 0 and 5.
 *
 * @since 1.0.0
 *
 * @param mixed $value Raw value.
 * @return int Sanitised level.
 */
function mlm_sanitize_level( $value ) {
	$value = (int) $value;

	if ( 1 > $value || 5 < $value ) {
		return 0;
	}

	return $value;
}

/**
 * Keeps the minute override sane.
 *
 * @since 1.0.0
 *
 * @param mixed $value Raw value.
 * @return int Sanitised minutes.
 */
function mlm_sanitize_minutes( $value ) {
	$value = (int) $value;

	if ( 0 > $value || 999 < $value ) {
		return 0;
	}

	return $value;
}

/**
 * Estimates reading time in minutes.
 *
 * @since 1.0.0
 *
 * @param string $content Post content.
 * @return int Minutes, at least 1 when there is text.
 */
function mlm_estimate_minutes( $content ) {
	$text = wp_strip_all_tags( strip_shortcodes( $content ) );

	if ( '' === trim( $text ) ) {
		return 0;
	}

	/**
	 * Filters the reading speed used for the estimate.
	 *
	 * @since 1.0.0
	 *
	 * @param int $wpm Words per minute.
	 */
	$wpm = (int) apply_filters( 'mlm_words_per_minute', 200 );

	if ( 1 > $wpm ) {
		$wpm = 200;
	}

	$words = count( preg_split( '/\s+/u', trim( $text ), -1, PREG_SPLIT_NO_EMPTY ) );

	return max( 1, (int) ceil( $words / $wpm ) );
}

/**
 * Reads the stored values for a post.
 *
 * @since 1.0.0
 *
 * @param int $post_id Post ID.
 * @return array Array with 'level' and 'minutes'.
 */
function mlm_get_data( $post_id ) {
	$post = get_post( $post_id );

	if ( ! $post ) {
		return array(
			'level'   => 0,
			'minutes' => 0,
		);
	}

	$level   = mlm_sanitize_level( get_post_meta( $post_id, '_mlm_level', true ) );
	$minutes = mlm_sanitize_minutes( get_post_meta( $post_id, '_mlm_minutes', true ) );

	if ( 0 === $minutes ) {
		$minutes = mlm_estimate_minutes( $post->post_content );
	}

	return array(
		'level'   => $level,
		'minutes' => $minutes,
	);
}

/**
 * Builds the front-end markup.
 *
 * @since 1.0.0
 *
 * @param int $post_id Post ID.
 * @return string HTML, or an empty string when there is nothing to show.
 */
function mlm_render( $post_id ) {
	$data = mlm_get_data( $post_id );

	if ( 0 === $data['level'] && 0 === $data['minutes'] ) {
		return '';
	}

	$levels = mlm_levels();
	$leds   = '';

	if ( 0 < $data['level'] ) {
		foreach ( array_keys( $levels ) as $number ) {
			$classes = 'mlm-led mlm-led--' . $number;

			if ( $number <= $data['level'] ) {
				$classes .= ' mlm-led--on';
			}

			$leds .= sprintf( '<span class="%s"></span>', esc_attr( $classes ) );
		}
	}

	$parts = array();

	if ( 0 < $data['level'] ) {
		$parts[] = sprintf(
			'<span class="mlm-label">%s</span>',
			esc_html( $levels[ $data['level'] ] )
		);
	}

	if ( 0 < $data['minutes'] ) {
		$parts[] = sprintf(
			'<span class="mlm-time">%s</span>',
			esc_html(
				sprintf(
					/* translators: %s: number of minutes. */
					_n( '%s min read', '%s min read', $data['minutes'], 'mental-load-meter' ),
					number_format_i18n( $data['minutes'] )
				)
			)
		);
	}

	$html = sprintf(
		'<p class="mlm-meter">%1$s%2$s</p>',
		'' === $leds ? '' : '<span class="mlm-leds" aria-hidden="true">' . $leds . '</span>',
		implode( '<span class="mlm-sep" aria-hidden="true">&middot;</span>', $parts )
	);

	wp_enqueue_style( 'mental-load-meter' );

	/**
	 * Filters the meter markup.
	 *
	 * @since 1.0.0
	 *
	 * @param string $html    Markup.
	 * @param array  $data    Level and minutes.
	 * @param int    $post_id Post ID.
	 */
	return apply_filters( 'mlm_output_html', $html, $data, $post_id );
}

/**
 * Registers the front-end stylesheet, and enqueues it where the meter shows.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mlm_register_style() {
	wp_register_style(
		'mental-load-meter',
		MLM_URL . 'assets/front.css',
		array(),
		MLM_VERSION
	);

	if ( is_singular( mlm_post_types() ) ) {
		$data = mlm_get_data( get_queried_object_id() );

		if ( 0 < $data['level'] || 0 < $data['minutes'] ) {
			wp_enqueue_style( 'mental-load-meter' );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'mlm_register_style' );

/**
 * Puts the meter above the content on singular views.
 *
 * @since 1.0.0
 *
 * @param string $content Post content.
 * @return string Content, with the meter prepended when it applies.
 */
function mlm_prepend_to_content( $content ) {
	/**
	 * Filters whether the meter is added automatically.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $auto Whether to prepend the meter.
	 */
	if ( ! apply_filters( 'mlm_auto_output', true ) ) {
		return $content;
	}

	if ( ! is_singular( mlm_post_types() ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	return mlm_render( get_the_ID() ) . $content;
}
add_filter( 'the_content', 'mlm_prepend_to_content' );

/**
 * Shortcode for manual placement.
 *
 * @since 1.0.0
 *
 * @return string Meter markup.
 */
function mlm_shortcode() {
	$post_id = get_the_ID();

	if ( ! $post_id ) {
		return '';
	}

	return mlm_render( $post_id );
}
add_shortcode( 'mental_load', 'mlm_shortcode' );

/**
 * Loads the editor panel.
 *
 * @since 1.0.0
 *
 * @return void
 */
function mlm_enqueue_editor_assets() {
	$screen = get_current_screen();

	if ( ! $screen || ! in_array( $screen->post_type, mlm_post_types(), true ) ) {
		return;
	}

	wp_enqueue_script(
		'mental-load-meter-editor',
		MLM_URL . 'assets/editor.js',
		array( 'wp-plugins', 'wp-element', 'wp-components', 'wp-data', 'wp-i18n', 'wp-edit-post', 'wp-editor' ),
		MLM_VERSION,
		true
	);

	$levels = array();

	foreach ( mlm_levels() as $number => $label ) {
		$levels[] = array(
			'value' => $number,
			'label' => $label,
		);
	}

	wp_add_inline_script(
		'mental-load-meter-editor',
		'var mlmData = ' . wp_json_encode(
			array(
				'levels'    => $levels,
				'postTypes' => array_values( mlm_post_types() ),
				'wpm'       => (int) apply_filters( 'mlm_words_per_minute', 200 ),
			)
		) . ';',
		'before'
	);

	wp_set_script_translations( 'mental-load-meter-editor', 'mental-load-meter' );
}
add_action( 'enqueue_block_editor_assets', 'mlm_enqueue_editor_assets' );
