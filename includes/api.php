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
			'event_date',
			'event_date_display',
			'event_start_time',
			'event_end_time',
			'event_combine_start_time',
			'event_combine_end_time',
			'event_duration',
			'event_time_display',
			'event_combine_time_display',
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
			'session_feedback_url'
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

			case 'event_date':
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
				$field_value = get_post_meta( $object[ 'id' ], $field_meta_key, true );
				return ! empty( $field_value ) ? $field_value : null;

			// Only get the combine start time if enabled
			case 'event_combine_start_time':
				if ( get_post_meta( $object[ 'id' ], 'conf_sch_combine_event', true ) ) {
					$event_combine_start_time = get_post_meta( $object['id'], $field_meta_key, true );
					return ! empty( $event_combine_start_time ) ? $event_combine_start_time : null;
				}
				return null;

			// Only get the combine end time if we have a start time, which means it's enabled
			case 'event_combine_end_time':
				if ( ! empty( $object[ 'event_combine_start_time' ] ) ) {
					$event_combine_end_time = get_post_meta( $object['id'], $field_meta_key, true );
					return ! empty( $event_combine_end_time ) ? $event_combine_end_time : null;
				}
				return null;

			case 'event_date_display':
				$event_date = get_post_meta( $object[ 'id' ], 'conf_sch_event_date', true );
				return ! empty( $event_date ) ? date( 'l, F j, Y', strtotime( $event_date ) ) : null;

			case 'event_duration':
			case 'event_time_display':
			case 'event_combine_time_display':

				// Get the time data
				$start_time = $end_time = '';

				// Get start and end time for the combine time
				if ( 'event_combine_time_display' == $field_name ) {

					// Only get the combine time display if we have a start time, which means it's enabled
					if ( ! empty( $object[ 'event_combine_start_time' ] ) ) {
						$start_time = $object[ 'event_combine_start_time' ];
						$end_time = $object[ 'event_combine_end_time' ];
					}

				}

				// Rest of the fields use the same start and end time
				else {
					$start_time = $object['event_start_time'];
					$end_time = $object['event_end_time'];
				}

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
			 * Livestream URL will only show up 10 minutes before the session starts.
			 *
			 * @TODO make setting
			 */
			case 'session_livestream_url':

				$livestream_url = get_post_meta( $object[ 'id' ], 'conf_sch_event_livestream_url', true );
				if ( ! empty( $livestream_url ) ) {

					// Get this site's timezone
					$timezone = get_option( 'timezone_string' );
					if ( empty( $timezone ) ) {
						$timezone = 'UTC';
					}

					// What time is it?
					$current_time = new DateTime( 'now', new DateTimeZone( $timezone ) );

					// Get date
					$session_date = ! empty( $object[ 'event_date' ] ) ? $object[ 'event_date' ] : get_post_meta( $object[ 'id' ], 'conf_sch_event_date', true );

					// Make sure we have a date
					if ( empty( $session_date ) ) {
						return null;
					}

					// Get start time
					$session_start_time = ! empty( $object[ 'event_start_time' ] ) ? $object[ 'event_start_time' ] : get_post_meta( $object[ 'id' ], 'conf_sch_event_start_time', true );

					// Make sure we have a start time
					if ( empty( $session_start_time ) ) {
						return null;
					}

					// Will show up 10 minutes before start
					$session_livestream_reveal_delay_seconds = 600;

					// Build date string
					$session_date_string = $session_date ? $session_date : null;

					// Send URL if time is valid
					if ( strtotime( $session_date_string ) !== false ) {

						// Add the start time
						if ( $session_start_time && strtotime( "{$session_date_string} {$session_start_time}" ) !== false ) {
							$session_date_string .= " {$session_start_time}";
						}

						// Build session date time
						$session_date_time = new DateTime( $session_date_string, new DateTimeZone( $timezone ) );

						// Feedback URL will only show up 30 minutes after the session has started
						if ( ( $session_date_time->getTimestamp() - $current_time->getTimestamp() ) <= $session_livestream_reveal_delay_seconds ) {
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

			case 'session_feedback_url':

				// Feedback URL will only show up 30 minutes after the session has started
				// If no valid session time, well show URL
				if ( $feedback_url = get_post_meta( $object[ 'id' ], 'conf_sch_event_feedback_url', true ) ) {

					// Get this site's timezone
					$timezone = get_option('timezone_string');

					// What time is it?
					$current_time = new DateTime( 'now', new DateTimeZone( $timezone ) );

					// Get date and start time and reveal delay
					$session_date = get_post_meta( $object[ 'id' ], 'conf_sch_event_date', true );
					$session_start_time = get_post_meta( $object[ 'id' ], 'conf_sch_event_start_time', true );
					$session_feedback_reveal_delay_seconds = get_post_meta( $object[ 'id' ], 'conf_sch_event_feedback_reveal_delay_seconds', true );

					// Set the default delay to 30 minutes
					if ( ! empty( $session_feedback_reveal_delay_seconds ) && $session_feedback_reveal_delay_seconds > 0 ) {
						$session_feedback_reveal_delay_seconds = intval( $session_feedback_reveal_delay_seconds );
					} else {
						$session_feedback_reveal_delay_seconds = 1800;
					}

					// Build date string
					$session_date_string = $session_date ? $session_date : null;

					// Send URL if time is not valid
					if ( $session_date_string && strtotime( $session_date_string ) !== false ) {

						// Add the start time
						if ( $session_start_time && strtotime( "{$session_date_string} {$session_start_time}" ) !== false ) {
							$session_date_string .= " {$session_start_time}";
						}

						// Build session date time
						$session_date_time = new DateTime( $session_date_string, new DateTimeZone( $timezone ) );

						// Feedback URL will only show up 30 minutes after the session has started
						if ( ( $current_time->getTimestamp() - $session_date_time->getTimestamp() ) >= $session_feedback_reveal_delay_seconds ) {
							return $feedback_url;
						}

						return null;

					}

					// If no valid session time, well show URL
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