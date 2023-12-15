<?php
/**
 * Astra-child Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package astra-child
 */

/**
 * Enqueue scripts and styles.
 */
function astra_parent_theme_enqueue() {
	wp_enqueue_style( 'astra-child-style', get_stylesheet_directory_uri() . '/style.css' );
}

add_action( 'wp_enqueue_scripts', 'astra_parent_theme_enqueue' );

/**
 * Custom event scope for Event Manager plugin.
 *
 * Source: https://wp-events-plugin.com/tutorials/create-your-own-event-scope/
 */
add_filter( 'em_events_build_sql_conditions', 'my_em_scope_conditions', 1, 2 );
function my_em_scope_conditions( $conditions, $args ) {
	if ( ! empty( $args['scope'] ) && $args['scope'] == 'next7days' ) {
		$start_date          = date( 'Y-m-d', current_time( 'timestamp' ) );
		$end_date            = date( 'Y-m-d', strtotime( '+6 day', current_time( 'timestamp' ) ) );
		$conditions['scope'] = " (event_start_date BETWEEN CAST('$start_date' AS DATE) AND CAST('$end_date' AS DATE)) OR (event_end_date BETWEEN CAST('$end_date' AS DATE) AND CAST('$start_date' AS DATE))";
	}

	return $conditions;
}

add_filter( 'em_get_scopes', 'my_em_scopes', 1, 1 );
function my_em_scopes( $scopes ) {
	$my_scopes = [
		'next7days' => 'next7days',
	];

	return $scopes + $my_scopes;
}

/**
 * Nesting shortcodes in menu.
 * Need it for the Events Manager plugin.
 */
add_filter( 'wp_nav_menu', 'do_shortcode' );
add_filter( 'the_content', 'do_shortcode' );

/**
 * Shortcodes for advanced notice in menu "Gesungene Gebete".
 */
@require 'events-manager/shortcode-gesungene-gebete.php';

/**
 * Shortcodes for advanced notice in menu "Wiederkehrende Veranstaltungen".
 */
@require 'events-manager/shortcode-wiederkehrende-veranstaltungen.php';

/**
 * Shortcodes for advanced notice in menu "Zweigstellen".
 */
@require 'events-manager/shortcode-zweigstellen.php';

/**
 * Shortcodes for advanced notice in menu "Meditationskurse".
 */
@require 'events-manager/shortcode-meditationskurse.php';

/**
 * Options page for Event Manager
 */
@require 'events-manager/options-page.php';
