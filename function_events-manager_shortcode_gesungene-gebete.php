<?php
/**
 * Shortcodes for advanced notice in menu "Gesungene Gebete".
 * This is an enhancement of the Events Manager plugin.
 */
if ( class_exists( 'EM_Events' ) ) {
	function em_gesungene_gebete_func() {

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

		$scope_today = date( 'Y-m-d' );
		$scope_later = date( 'Y-m-d', strtotime( "+21 day" ) );

		// Get events within the scope (today + 21 days)
		$em_events = EM_Events::get( [
			'hide_empty'  => 1,
			'recurrences' => 1,
			'orderby'     => "event_start_date,event_start_time",
			'scope'       => $scope_today . "," . $scope_later,
			'category'    => '48' // Event category 'Gesungene Gebete'.
		] );

		if ( ! empty( $em_events ) ) {

			// Set all event data in array.
			foreach ( $em_events as $em_event ) {
				$events[] = [
					'day_number'    => date( 'N', strtotime( $em_event->start_date ) ),
					'day'           => $localDateformat->format( strtotime( $em_event->start_date ) ),
					'timestamp'     => strtotime( $em_event->start_date ),
					'start_time'    => date( 'H:i', strtotime( $em_event->start_time ) ),
					'recurrence_id' => $em_event->recurrence_id,
					'guid'          => $em_event->guid,
					'event_name'    => $em_event->event_name,
				];
			}

			// First sort by day then by time and then by timestamp.
			$day_number = array_column( $events, 'day_number' );
			$start_time = array_column( $events, 'start_time' );
			$timestamp  = array_column( $events, 'timestamp' );
			array_multisort( $day_number, SORT_ASC, $start_time, SORT_ASC, $timestamp, SORT_ASC, $events );

			// Marker value for the start in the loop
			$event_day = 'start';

			// We only want the soonest recurring event on every day.
			// Therefore, we need make sure only 1 recurrence_id occur per day.
			$alreadyExistingEventsBasket = [];

			foreach ( $events as $event ) {

				$eventControlNumber = $event['recurrence_id'];

				if ( ! in_array( $eventControlNumber, $alreadyExistingEventsBasket ) ) {

					$alreadyExistingEventsBasket[] = $eventControlNumber;

					// Need special markup for the first loop.
					if ( $event_day === 'start' ) {
						$string .= '<div class="menu-link-flex em-recurring-events-in-menu">';
						$string .= '<div class="menu-link menu-link-day">' . $event['day'] . '</div>';
						$string .= '<div class="menu-link menu-link-event-list">';
						// Need special markup for new weekday in the loop.
					} elseif ( $event_day != $event['day'] ) {
						$string .= '</div>'; // .menu-link-event-list
						$string .= '</div>'; // .menu-link-flex

						$string .= '<div class="menu-link-flex em-recurring-events-in-menu">';
						$string .= '<div class="menu-link menu-link-day">' . $event['day'] . '</div>';
						$string .= '<div class="menu-link menu-link-event-list">';
					}

					$string .= '<a class="menu-link-flex" href="' . $event['guid'] . '">';
					$string .= '<span class="menu-link-flex-item2">' . date( 'G:i', strtotime( $event['start_time'] ) ) . '</span> ';
					$string .= '<span class="menu-link-flex-item3">' . $event['event_name'] . '</span>';
					$string .= '</a>';

					$event_day = $event['day'];
				}
			}

			$string .= '</div>'; // .menu-link-event-list
			$string .= '</div>'; // .menu-link-flex

			return $string;
		}
	}

	add_shortcode( 'em_gesungene_gebete', 'em_gesungene_gebete_func' );
}