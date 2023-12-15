<?php
/**
 * Shortcodes for advanced notice in menu "Zweigstellen".
 * This is an enhancement of the Events Manager plugin.
 */

function em_menu_zweigstellen_func() {
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

	// Get all subcategories of "Zweigstellen"
	$branches = get_categories( [
		'taxonomy' => 'event-categories',
		'parent'   => 53, // 53 = Zweigstellen
	] );

	if ( empty( $branches ) ) {
		return 'Keine Kategorie "Zweigstellen" gefunden.';
	}

	$branches_id = [];
	foreach ( $branches as $branch ) {
		$branches_id[] = $branch->term_id;
	}

	if ( empty( $branches_id ) ) {
		return 'Keine Zweigstellen gefunden.';
	}

	// Get events within the scope (today + 31 days)
	$em_events = EM_Events::get( [
		'hide_empty'  => 1,
		'recurrences' => 1,
		'orderby'     => 'event_start_date,event_start_time',
		'scope'       => $scope_today . ',' . $scope_later,
		'category'    => $branches_id,
	] );

	if ( empty( $em_events ) ) {
		return 'Keine Veranstaltungen gefunden.';
	}

	// Set all event data in array.
	foreach ( $em_events as $em_event ) {
		$terms = get_the_terms( $em_event->post_id, 'event-categories' );
		$term  = array_shift( $terms ); // Should only be one category per event.

		$events[] = [
			'day_number'    => date( 'N', strtotime( $em_event->start_date ) ),
			'day'           => $localDateformat->format( strtotime( $em_event->start_date ) ),
			'timestamp'     => strtotime( $em_event->start_date ),
			'start_time'    => date( 'H:i', strtotime( $em_event->start_time ) ),
			'recurrence_id' => $em_event->recurrence_id,
			'guid'          => $em_event->guid,
			'event_name'    => $em_event->event_name,
			'category_name' => $term->name,
		];
	}

	// First sort by day then by time and then by timestamp.
	$event      = array_column( $events, 'event_name' );
	$day_number = array_column( $events, 'day_number' );
	$start_time = array_column( $events, 'start_time' );
	$timestamp  = array_column( $events, 'timestamp' );
	array_multisort( $event, $day_number, SORT_ASC, $start_time, SORT_ASC, $timestamp, SORT_ASC, $events );

	// Marker value for the start in the loop
	$event_day = 'start';

	// We only want the soonest recurring event on every day.
	// Therefore, we need make sure only 1 recurrence_id occur per day.
	$alreadyExistingEventsBasket = [];

	foreach ( $events as $event ) {
		$eventControlNumber = $event['day_number'] . '-' . $event['recurrence_id'];

		if ( ! in_array( $eventControlNumber, $alreadyExistingEventsBasket, true ) ) {
			$alreadyExistingEventsBasket[] = $eventControlNumber;
			// Need special markup for the first loop.
			if ( $event_day === 'start' ) {
				$alreadyExistingEventsNameBasket = $event['event_name'];

				$string .= '<div class="menu-link-flex em-recurring-events-in-menu branch-event-name">';
				$string .= esc_html( $event['event_name'] );
				$string .= '</div>'; // .menu-link-flex

				$string .= '<div class="menu-link-flex em-recurring-events-in-menu">';
				$string .= '<div class="menu-link menu-link-day">' . esc_html( $event['day'] ) . '</div>';
				$string .= '<div class="menu-link menu-link-event-list">';
				// Need special markup for new weekday in the loop.
			} elseif ( $event_day != $event['day'] ) {
				$string .= '</div>'; // .menu-link-event-list
				$string .= '</div>'; // .menu-link-flex

				if ( $alreadyExistingEventsNameBasket !== $event['event_name'] ) {
					$string .= '<div class="menu-link-flex em-recurring-events-in-menu branch-event-name">';
					$string .= esc_html( $event['event_name'] );
					$string .= '</div>'; // .menu-link-flex

					$alreadyExistingEventsNameBasket = $event['event_name'];
				}
				$string .= '<div class="menu-link-flex em-recurring-events-in-menu">';
				$string .= '<div class="menu-link menu-link-day">' . esc_html( $event['day'] ) . '</div>';
				$string .= '<div class="menu-link menu-link-event-list">';
			}

			$string .= '<a class="menu-link-flex" href="' . esc_url( $event['guid'] ) . '">';
			$string .= '<span class="menu-link-flex-item2">' . esc_html( date( 'G:i', strtotime( $event['start_time'] ) ) ) . '</span> ';
			$string .= '<span class="menu-link-flex-item3">' . esc_html( $event['category_name'] ) . '</span>';
			$string .= '</a>';

			$event_day = $event['day'];
		}
	}

	$string .= '</div>'; // .menu-link-event-list
	$string .= '</div>'; // .menu-link-flex

	return $string;
}

add_shortcode( 'em_menu_zweigstellen', 'em_menu_zweigstellen_func' );
