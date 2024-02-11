<?php
/**
 * Shortcodes for entries in menu Programm.
 *
 * Wochenprogramm / Wiederkehrende Veranstaltungen
 * e.g. [em_menu cat_id=15]
 *
 * Meditationskurse / Kurse am WE
 * e.g. [em_menu cat_id=23 limit=3 recurrences=0]
 *
 * Zweigstellen
 * e.g. [em_menu cat_id=25 get_branches=1]
 */

function em_menu_func( $atts ) {
	// Set default values for shortcode.
	$atts = shortcode_atts(
		[
			'cat_id'       => '0', // ID of the event category, 0 = no category
			'get_branches' => '0', // 0 = no, 1 = yes
			'limit'        => '0', // 0 = no limits
			'recurrences'  => '1', // 1 = only recurring events, 0 = all events
		],
		$atts,
		'em_menu'
	);

	$string = '';

	// Set to german time zone
	date_default_timezone_set( 'Europe/Berlin' );

	// Set to german language
	setlocale( LC_ALL, 'de_DE.UTF8' );

	// "strftime" is deprecated.
	// We need to use IntlDateFormatter for date translation
	$localDateformat = new IntlDateFormatter(
		'de-DE',
		IntlDateFormatter::FULL,
		IntlDateFormatter::FULL,
		'Europe/Berlin',
		IntlDateFormatter::GREGORIAN,
		'eee'
	);

	$number_days = 60;
	$datetime    = '+' . $number_days . ' day';
	$scope_today = date( 'Y-m-d' );
	$scope_later = date( 'Y-m-d', strtotime( $datetime ) );

	// Get all subcategories of "Zweigstellen".
	if ( $atts['get_branches'] === '1' ) {
		$branches = get_categories(
			[
				'taxonomy' => 'event-categories',
				'parent'   => $atts['cat_id'],
			]
		);

		if ( empty( $branches ) ) {
			return 'Keine Kategorie "Zweigstellen" gefunden.';
		}

		$cat_id = [];
		foreach ( $branches as $branch ) {
			$cat_id[] = $branch->term_id;
		}

		if ( empty( $cat_id ) ) {
			return 'Keine Zweigstellen gefunden.';
		}
	}

	// Get events within the scope (today + 31 days)
	$em_events = EM_Events::get(
		[
			'category'    => $atts['cat_id'],
			'hide_empty'  => 1,
			'limit'       => $atts['limit'],
			'orderby'     => 'event_start_date,event_start_time',
			'recurrences' => $atts['recurrences'],
			'scope'       => $scope_today . ',' . $scope_later,
		]
	);

	if ( empty( $em_events ) ) {
		return 'Keine Veranstaltungen gefunden.';
	}

	// Set all event data in array.
	$events = [];
	foreach ( $em_events as $em_event ) {
		$terms = get_the_terms( $em_event->post_id, 'event-categories' );
		$term  = array_shift( $terms ); // Should only be one category per event.

		$events[] = [
			'day_number'    => date( 'N', strtotime( $em_event->start_date ) ),
			'day'           => $localDateformat->format( strtotime( $em_event->start_date ) ),
			'timestamp'     => strtotime( $em_event->start_date ),
			'start_time'    => date( 'H:i', strtotime( $em_event->start_time ) ),
			'start_date'    => date( 'j.n.', strtotime( $em_event->start_date ) ),
			'recurrence_id' => $em_event->recurrence_id,
			'guid'          => $em_event->guid,
			'event_name'    => $em_event->event_name,
			'category_name' => $term->name,
		];
	}

	// Sort the events.
	$day_number = array_column( $events, 'day_number' );
	$start_time = array_column( $events, 'start_time' );
	$timestamp  = array_column( $events, 'timestamp' );

	if ( $atts['recurrences'] === '0' ) { // Meditationskurse
		array_multisort( $day_number, SORT_DESC, $start_time, SORT_ASC, $timestamp, SORT_ASC, $events );
	} elseif ( $atts['get_branches'] === '1' ) { // Zweigstellen
		$event = array_column( $events, 'event_name' );
		array_multisort( $event, $day_number, SORT_ASC, $start_time, SORT_ASC, $timestamp, SORT_ASC, $events );
	} else { // Wochenprogramm
		// Sort by day then by time and then by timestamp.
		array_multisort( $day_number, SORT_ASC, $start_time, SORT_ASC, $timestamp, SORT_ASC, $events );
	}

	// Wiederkehrende Veranstaltungen or Zweigstellen
	if ( $atts['recurrences'] === '1' ) {
		// Iterate trough $events and remove duplicate arrays with same event['event_name'] on same $event['day'].
		$event_name = '';
		$day        = '';
		foreach ( $events as $key => $event ) {
			$sanitized_event_name = preg_replace( '/[^a-zA-Z0-9]/', '', $event['event_name'] );
			if ( ( $event_name === $sanitized_event_name ) && ( $day === $event['day'] ) ) {
				unset( $events[ $key ] );
			} else {
				$event_name = $sanitized_event_name;
				$day        = $event['day'];
			}
		}
	}

	// Wiederkehrende Veranstaltungen
	if ( ( $atts['recurrences'] === '1' ) && ( $atts['get_branches'] === '0' ) ) {
		// Iterate trough $events and unset duplicate arrays with same event['recurrence_id'] on same $event['day'].
		$recurrence_id = '';
		$day           = '';
		foreach ( $events as $key => $event ) {
			if ( ( $recurrence_id === $event['recurrence_id'] ) && ( $day === $event['day'] ) ) {
				unset( $events[ $key ] );
			} else {
				$recurrence_id = $event['recurrence_id'];
				$day           = $event['day'];
			}
		}
	}

	// Wiederkehrende Veranstaltungen or Meditationskurse
	if ( $atts['get_branches'] === '0' ) {
		// Iterate trough $events and toggle duplicate day to empty string.
		$day = '';
		foreach ( $events as $key => $event ) {
			if ( $day === $event['day'] ) {
				$events[ $key ]['day'] = '';
			} else {
				$day = $event['day'];
			}
		}
	}

	// Zweigstellen
	if ( $atts['get_branches'] === '1' ) {
		// Iterate trough $events and toggle duplicate event_name to empty string.
		$event_name = '';
		foreach ( $events as $key => $event ) {
			$sanitized_event_name = preg_replace( '/[^a-zA-Z0-9]/', '', $event['event_name'] );
			if ( $event_name === $sanitized_event_name ) {
				$events[ $key ]['event_name'] = '';
			} else {
				$event_name = $sanitized_event_name;
			}
		}
	}

	foreach ( $events as $event ) {
		if ( ( $atts['get_branches'] === '1' ) && ( $event['event_name'] !== '' ) ) { // Zweigstellen
			$string .= '<div class="kmc-em-menu-title">' . $event['event_name'] . '</div>';
		}
		$string .= '<a class="kmc-em-menu-link" href="' . $event['guid'] . '">';
		$string .= '<span>' . $event['day'] . '</span>';
		if ( $atts['recurrences'] === '1' ) { // Wiederkehrende Veranstaltungen
			$string .= '<span class="kmc-em-menu-col2">' . $event['start_time'] . '</span>';
		} else {
			$string .= '<span class="kmc-em-menu-col2">' . $event['start_date'] . '</span>';
		}
		if ( $atts['get_branches'] === '1' ) { // Zweigstellen
			$string .= '<span>' . $event['category_name'] . '</span>';
		} else {
			$string .= '<span>' . $event['event_name'] . '</span>';
		}
		$string .= '</a>';
	}

	return $string;
}

add_shortcode( 'em_menu', 'em_menu_func' );
