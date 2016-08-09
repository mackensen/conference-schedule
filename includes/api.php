<?php

class Conference_Schedule_API {

	/**
	 * Warming things up.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function __construct() {

		// Register any REST fields
		add_action( 'rest_api_init', array( $this, 'register_rest_fields' ), 20 );

	}

	/**
	 * Register additional fields for the REST API.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function register_rest_fields() {

		// The args are the same for each field
		$rest_field_args = array(
			'get_callback'		=> array( $this, 'get_field_value' ),
			'update_callback'	=> null,
			'schema'			=> null,
		);

		// Add event info
		$event_fields = array(
			'event_dt',
			'event_dt_gmt',
			'event_date',
			'event_start_time',
			'event_end_time',
			'event_date_display',
			'event_time_display',
			'event_duration',
			'event_parent',
			'event_types',
			'event_location',
			'event_address',
			'event_google_maps_url',
			'link_to_post',
			'event_speakers',
			'event_hashtag',
			'session_categories',
			'session_livestream_url',
			'session_slides_url',
			'session_feedback_url',
			'session_follow_up_url'
		);
		foreach( $event_fields as $field_name ) {
			register_rest_field( 'schedule', $field_name, $rest_field_args );
		}

		// Add speaker info
		$speaker_fields = array( 'speaker_thumbnail', 'speaker_position', 'speaker_url', 'speaker_company', 'speaker_company_url', 'speaker_facebook', 'speaker_instagram', 'speaker_twitter', 'speaker_linkedin' );
		foreach( $speaker_fields as $field_name ) {
			register_rest_field( 'speakers', $field_name, $rest_field_args );
		}

		// Add location info
		$location_fields = array( 'address', 'google_maps_url' );
		foreach( $location_fields as $field_name ) {
			register_rest_field( 'locations', $field_name, $rest_field_args );
		}

	}

	/**
	 * Get field values for the REST API.
	 *
	 * @TODO go through and see what DB calls
	 * we can save by checking the API for values
	 * already retrieved.
	 *
	 * @param	array - $object - details of current post
	 * @param	string - $field_name - name of field
	 * @param	WP_REST_Request - $request - current request
	 * @return	mixed
	 */
	public function get_field_value( $object, $field_name, $request ) {
		global $wpdb;

		// Define field post meta key
		$field_meta_key = "conf_sch_{$field_name}";

		switch( $field_name ) {

			case 'event_start_time':
			case 'event_end_time':
			case 'event_hashtag':
			case 'speaker_position':
			case 'speaker_url':
			case 'speaker_company':
			case 'speaker_company_url':
			case 'speaker_facebook':
			case 'speaker_instagram':
			case 'speaker_twitter':
			case 'speaker_linkedin':
				if ( ! empty( $object[ $field_name ] ) ) {
					$field_value = $object[ $field_name ];
				} else {
					$field_value = get_post_meta( $object['id'], $field_meta_key, true );
				}
				return ! empty( $field_value ) ? $field_value : null;

			// Get the event date/time
			case 'event_dt':

				// Get the event date
				if ( empty( $object['event_date'] ) ) {
					$object['event_date'] = get_post_meta( $object['id'], 'conf_sch_event_date', true );
				}

				// If we have an event date, get the start date
				if ( ! empty( $object['event_date'] ) ) {

					// Store in variable
					$event_date = $object['event_date'];

					// Get the start time, if we have one
					if ( empty( $object['event_start_time'] ) ) {
						$object['event_start_time'] = get_post_meta( $object['id'], 'conf_sch_event_start_time', true );
					}

					// If we have a start time, add to date
					if ( ! empty( $object['event_start_time'] ) ) {
						$event_date .= 'T' . $object['event_start_time'];
					}

				}

				return ! empty( $event_date ) ? $event_date : null;

			// Get the event date/time in GMT
			case 'event_dt_gmt':

				// Get the event date
				if ( empty( $object['event_dt'] ) ) {
					$object['event_dt'] = get_post_meta( $object['id'], 'conf_sch_event_date', true );
				}

				// If we have a date...
				if ( ! empty( $object['event_dt'] ) ) {

					// Get this site's timezone
					$timezone = get_option( 'timezone_string' );
					if ( empty( $timezone ) ) {
						$timezone = 'UTC';
					}

					// Store in date object
					$date = new DateTime( $object['event_dt'], new DateTimeZone( $timezone ) );

					// Convert to UTC/GMT
					$utc_timezone = new DateTimeZone( 'UTC' );
					$date->setTimezone( $utc_timezone );

					// Store GMT
					return $date->format( 'Y-m-d\TH:i' );

				}

				return null;

			// Get the event date
			case 'event_date':

				// Get the event date
				if ( empty( $object['event_date'] ) ) {
					$object['event_date'] = get_post_meta( $object['id'], 'conf_sch_event_date', true );
				}
				return ! empty( $object['event_date'] ) ? $object['event_date'] : null;

			// Get the event parent
			case 'event_parent':
				return get_post_field( 'post_parent', $object['id'] );

			case 'event_date_display':
				if ( empty( $object['event_date'] ) ) {
					$object['event_date'] = get_post_meta( $object['id'], 'conf_sch_event_date', true );
				}
				return ! empty( $object['event_date'] ) ? date( 'l, F j, Y', strtotime( $object['event_date'] ) ) : null;

			case 'event_duration':
			case 'event_time_display':

				// Get the time data
				$start_time = $end_time = '';

				// Get the start time
				if ( empty( $object['event_start_time'] ) ) {
					$object['event_start_time'] = get_post_meta( $object['id'], 'conf_sch_event_start_time', true );
				}
				$start_time = $object['event_start_time'];

				// Get the end time
				if ( empty( $object['event_end_time'] ) ) {
					$object['event_end_time'] = get_post_meta( $object['id'], 'conf_sch_event_end_time', true );
				}
				$end_time = $object['event_end_time'];

				// Only proceed if we have a start time
				if ( ! $start_time ) {
					return null;
				}

				// Convert start time
				$start_time = strtotime( $start_time );

				// Build the display string, starting with start time
				$time_display = date( 'g:i', $start_time );

				// If we don't have an end time...
				if ( ! $end_time ) {
					$time_display .= date( ' a', $start_time );
				}

				// If we have an end time...
				else {

					// Convert end time
					$end_time = strtotime( $end_time );

					// Return duration
					if ( 'event_duration' == $field_name ) {
						return ( $end_time - $start_time );
					}

					// Figure out if the meridian is different
					if ( date( 'a', $start_time ) != date( 'a', $end_time ) ) {
						$time_display .= date( ' a', $start_time );
					}

					$time_display .= ' - ' . date( 'g:i a', $end_time );

				}
				return ( 'event_duration' == $field_name ) ? null : preg_replace( '/(a|p)m/', '$1.m.', $time_display );

			case 'event_types':
				$types = wp_get_object_terms( $object[ 'id' ], 'event_types', array( 'fields' => 'slugs' ) );
				return ! empty( $types ) ? $types : null;

			case 'session_categories':
				$categories = wp_get_object_terms( $object[ 'id' ], 'session_categories', array( 'fields' => 'names' ) );
				return ! empty( $categories ) ? $categories : null;

			case 'event_location':
				$event_location_id = get_post_meta( $object[ 'id' ], 'conf_sch_event_location', true );
				if ( $event_location_id > 0 ) {
					$event_post = get_post( $event_location_id );
					return ! empty( $event_post ) ? $event_post : null;
				}
				return null;

			case 'event_address':
				if ( ! empty( $object[ 'event_location' ]->ID ) ) {
					$conf_sch_location_address = get_post_meta( $object['event_location']->ID, 'conf_sch_location_address', true );
					return ! empty( $conf_sch_location_address ) ? $conf_sch_location_address : null;
				}
				return null;

			case 'address':
				$conf_sch_location_address = get_post_meta( $object[ 'id' ], 'conf_sch_location_address', true );
				return ! empty( $conf_sch_location_address ) ? $conf_sch_location_address : null;

			case 'event_google_maps_url':
				if ( ! empty( $object[ 'event_location' ]->ID ) ) {
					$google_maps_url = get_post_meta( $object['event_location']->ID, 'conf_sch_location_google_maps_url', true );
					return ! empty( $google_maps_url ) ? $google_maps_url : null;
				}
				return null;

			case 'google_maps_url':
				$google_maps_url = get_post_meta( $object[ 'id' ], 'conf_sch_location_google_maps_url', true );
				return ! empty( $google_maps_url ) ? $google_maps_url : null;

			/**
			 * See if we need to link to the event post in the schedule.
			 *
			 * The default is true.
			 *
			 * If database row doesn't exist, then set as default.
			 * Otherwise, check value.
			 */
			case 'link_to_post':

				// Check the database
				$sch_link_to_post_db = $wpdb->get_var( "SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = {$object[ 'id' ]} AND meta_key = 'conf_sch_link_to_post'" );

				// If row exists, then check the value
				if ( $sch_link_to_post_db ) {
					$sch_link_to_post = get_post_meta( $object[ 'id' ], 'conf_sch_link_to_post', true );
					if ( ! $sch_link_to_post ) {
						return false;
					}
				}
				return true;

			case 'event_speakers':
				if ( $event_speaker_ids = get_post_meta( $object[ 'id' ], 'conf_sch_event_speakers', true ) ) {

					// Make sure its an array
					if ( ! is_array( $event_speaker_ids ) ) {
						$event_speaker_ids = implode( ',', $event_speaker_ids );
					}

					// Get speakers info
					$speakers = array();
					foreach( $event_speaker_ids as $speaker_id ) {
						if ( $speaker_post = get_post( $speaker_id ) ) {

							// Add twitter
							$speaker_post->twitter = get_post_meta( $speaker_id, 'conf_sch_speaker_twitter', true );

							$speakers[] = $speaker_post;
						}
					}

					return $speakers;
				}
				return null;

			/**
			 * Livestream URL will only show up 10 minutes before the session starts and stay until it ends.
			 *
			 * @TODO make setting
			 */
			case 'session_livestream_url':

				$livestream_url = get_post_meta( $object[ 'id' ], 'conf_sch_event_livestream_url', true );
				if ( ! empty( $livestream_url ) ) {

					// What time is it in UTC?
					$current_time = new DateTime( 'now', new DateTimeZone( 'UTC' ) );

					// Get date in UTC
					// @TODO create function to get event date and event date GMT
					$session_date = ! empty( $object[ 'event_dt_gmt' ] ) ? $object[ 'event_dt_gmt' ] : '';

					// Make sure we have a date
					if ( empty( $session_date ) ) {
						return null;
					}

					// Will show up 10 minutes before start
					$session_livestream_reveal_delay_seconds = 600;

					// Send URL if time is valid
					if ( strtotime( $session_date ) !== false ) {

						// Build session start date time
						$event_start_dt = new DateTime( $session_date, new DateTimeZone( 'UTC' ) );

						// When will the livestream URL show up?
						if ( ( $event_start_dt->getTimestamp() - $current_time->getTimestamp() ) <= $session_livestream_reveal_delay_seconds ) {

							// Get the duration
							// @TODO create function to get duration
							$event_duration = ! empty( $object['event_duration'] ) ? $object['event_duration'] : 0;

							// Remove when the event ends
							if ( $current_time->getTimestamp() > ( $event_start_dt->getTimestamp() + $event_duration ) ) {
								return null;
							}

							return $livestream_url;
						}

						return null;

					}

				}
				return null;

			case 'session_slides_url':

				// The URL takes priority when a URL and file is provided
				$slides_url = get_post_meta( $object[ 'id' ], 'conf_sch_event_slides_url', true );
				if ( ! empty( $slides_url ) ) {
					return $slides_url;
				}

				// Get the file
				$slides_file_id = get_post_meta( $object[ 'id' ], 'conf_sch_event_slides_file', true );
				if ( $slides_file_id > 0 ) {
					$slides_file_url = wp_get_attachment_url( $slides_file_id );
					return ! empty( $slides_file_url ) ? $slides_file_url : null;
				}
				return null;

			case 'session_follow_up_url':
				$follow_up_url = get_post_meta( $object[ 'id' ], 'conf_sch_event_follow_up_url', true );
				return ! empty( $follow_up_url ) ? $follow_up_url : null;

			case 'session_feedback_url':

				// Feedback URL will only show up 30 minutes after the session has started
				// If no valid session time, well show URL
				$feedback_url = get_post_meta( $object[ 'id' ], 'conf_sch_event_feedback_url', true );

				// Filter the feedback URL
				$feedback_url = apply_filters( 'conf_sch_feedback_url', $feedback_url, $object );

				if ( $feedback_url ) {

					// What time is it?
					$current_time = new DateTime( 'now', new DateTimeZone( 'UTC' ) );

					// Get date in UTC
					// @TODO create function to get event date and event date GMT
					$event_date = ! empty( $object[ 'event_dt_gmt' ] ) ? $object[ 'event_dt_gmt' ] : '';

					// Send URL if time is not valid
					if ( ! empty( $event_date ) && strtotime( $event_date ) !== false ) {

						// Build event start date
						$event_start = new DateTime( $event_date, new DateTimeZone( 'UTC' ) );

						// Get reveal delay
						$session_feedback_reveal_delay_seconds = get_post_meta( $object[ 'id' ], 'conf_sch_event_feedback_reveal_delay_seconds', true );

						// Set the default delay to 30 minutes
						if ( ! empty( $session_feedback_reveal_delay_seconds ) && $session_feedback_reveal_delay_seconds > 0 ) {
							$session_feedback_reveal_delay_seconds = intval( $session_feedback_reveal_delay_seconds );
						} else {
							$session_feedback_reveal_delay_seconds = 1800;
						}

						// Feedback URL will only show up 30 minutes after the event has started
						if ( ( $current_time->getTimestamp() - $event_start->getTimestamp() ) >= $session_feedback_reveal_delay_seconds ) {
							return $feedback_url;
						}

						return null;

					}

					// If no valid event time, well show URL
					return $feedback_url;

				}
				return null;

			case 'speaker_thumbnail':
				$image = wp_get_attachment_image_src( get_post_thumbnail_id( $object[ 'id' ] ), 'thumbnail' );
				return ! empty( $image[0] ) ? $image[0] : null;

		}

		return null;
	}

}

// Let's get this show on the road
new Conference_Schedule_API;