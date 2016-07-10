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
		$event_fields = array( 'event_date', 'event_date_display', 'event_start_time', 'event_end_time', 'event_duration', 'event_time_display', 'event_types', 'event_location', 'event_speakers', 'event_hashtag', 'session_categories', 'session_slides_url', 'session_feedback_url' );
		foreach( $event_fields as $field_name ) {
			register_rest_field( 'schedule', $field_name, $rest_field_args );
		}

		// Add speaker info
		$speaker_fields = array( 'speaker_thumbnail', 'speaker_position', 'speaker_url', 'speaker_company', 'speaker_company_url', 'speaker_facebook', 'speaker_instagram', 'speaker_twitter', 'speaker_linkedin' );
		foreach( $speaker_fields as $field_name ) {
			register_rest_field( 'speakers', $field_name, $rest_field_args );
		}

	}

	/**
	 * Get field values for the REST API.
	 *
	 * @param	array - $object - details of current post
	 * @param	string - $field_name - name of field
	 * @param	WP_REST_Request - $request - current request
	 * @return	mixed
	 */
	public function get_field_value( $object, $field_name, $request ) {

		switch( $field_name ) {

			case 'event_date':
			case 'event_start_time':
			case 'event_end_time':
			case 'event_hashtag':
				return get_post_meta( $object[ 'id' ], "conf_sch_{$field_name}", true );

			case 'event_date_display':
				if ( $event_date = get_post_meta( $object[ 'id' ], 'conf_sch_event_date', true ) ) {
					return date( 'l, F j, Y', strtotime( $event_date ) );
				}
				break;

			case 'event_duration':
			case 'event_time_display':

				// Get start and end time
				$event_start_time = get_post_meta( $object[ 'id' ], 'conf_sch_event_start_time', true );
				$event_end_time = get_post_meta( $object[ 'id' ], 'conf_sch_event_end_time', true );

				// Build display string
				$event_time_display = '';

				// Start with start time
				if ( $event_start_time ) {

					// Convert start time
					$event_start_time = strtotime( $event_start_time );

					// Build the start time
					$event_time_display = date( 'g:i', $event_start_time );

					// If we don't have an end time...
					if ( ! $event_end_time ) {
						$event_time_display = date( ' a', $event_start_time );
					}

					// If we have an end time...
					else {

						// Convert end time
						$event_end_time = strtotime( $event_end_time );

						// Return duration
						if ( 'event_duration' == $field_name ) {
							return ( $event_end_time - $event_start_time );
						}

						// Figure out if the meridian is different
						if ( date( 'a', $event_start_time ) != date( 'a', $event_end_time ) ) {
							$event_time_display .= date( ' a', $event_start_time );
						}

						$event_time_display .= ' - ' . date( 'g:i a', $event_end_time );

					}

				}
				return ( 'event_time_display' == $field_name ) ? preg_replace( '/(a|p)m/', '$1.m.', $event_time_display ) : null;

			case 'event_types':
				return ( $types = wp_get_object_terms( $object[ 'id' ], 'event_types', array( 'fields' => 'slugs' ) ) ) ? $types : null;

			case 'session_categories':
				return ( $categories = wp_get_object_terms( $object[ 'id' ], 'session_categories', array( 'fields' => 'names' ) ) ) ? $categories : null;

			case 'event_location':
				if ( $event_location_id = get_post_meta( $object[ 'id' ], 'conf_sch_event_location', true ) ) {
					if ( $event_post = get_post( $event_location_id ) ) {
						return $event_post;
					}
				}
				return null;

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
							$speakers[] = $speaker_post;
						}
					}

					return $speakers;
				}
				return null;

			case 'session_slides_url':

				// The URL takes priority when a URL and file is provided
				if ( $slides_url = get_post_meta( $object[ 'id' ], 'conf_sch_event_slides_url', true ) ) {
					return $slides_url;
				}

				// Get the file
				if ( $slides_file_id = get_post_meta( $object[ 'id' ], 'conf_sch_event_slides_file', true ) ) {
					if ( $slides_file_url = wp_get_attachment_url( $slides_file_id ) ) {
						return $slides_file_url;
					}

				}
				break;

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
				break;

			case 'speaker_thumbnail':
				if ( ( $image = wp_get_attachment_image_src( get_post_thumbnail_id( $object[ 'id' ] ), 'thumbnail' ) )
					&& isset( $image[0] ) ) {
					return $image[0];
				}
				return null;

			case 'speaker_position':
			case 'speaker_url':
			case 'speaker_company':
			case 'speaker_company_url':
			case 'speaker_facebook':
			case 'speaker_instagram':
			case 'speaker_twitter':
			case 'speaker_linkedin':
				return get_post_meta( $object[ 'id' ], "conf_sch_{$field_name}", true );

		}

		return null;
	}

}

// Let's get this show on the road
new Conference_Schedule_API;