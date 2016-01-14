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

		// Get the event date
		register_rest_field( 'schedule', 'event_date',
			array(
				'get_callback'		=> array( $this, 'get_field_value' ),
				'update_callback'	=> null,
				'schema'			=> null,
			)
		);

		// Get the event start time
		register_rest_field( 'schedule', 'event_start_time',
			array(
				'get_callback'		=> array( $this, 'get_field_value' ),
				'update_callback'	=> null,
				'schema'			=> null,
			)
		);

		// Get the event end time
		register_rest_field( 'schedule', 'event_end_time',
			array(
				'get_callback'		=> array( $this, 'get_field_value' ),
				'update_callback'	=> null,
				'schema'			=> null,
			)
		);

		// Get the event types
		register_rest_field( 'schedule', 'event_types',
			array(
				'get_callback'		=> array( $this, 'get_field_value' ),
				'update_callback'	=> null,
				'schema'			=> null,
			)
		);

		// Get the event location
		register_rest_field( 'schedule', 'event_location',
			array(
				'get_callback'		=> array( $this, 'get_field_value' ),
				'update_callback'	=> null,
				'schema'			=> null,
			)
		);

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
				return get_post_meta( $object[ 'id' ], 'conf_sch_event_date', true );

			case 'event_start_time':
				return get_post_meta( $object[ 'id' ], 'conf_sch_event_start_time', true );

			case 'event_end_time':
				return get_post_meta( $object[ 'id' ], 'conf_sch_event_end_time', true );

			case 'event_types':
				return ( $categories = wp_get_object_terms( $object[ 'id' ], 'event_types', array( 'fields' => 'slugs' ) ) ) ? $categories : false;

			case 'event_location':
				return get_post_meta( $object[ 'id' ], 'conf_sch_event_location', true );

		}

		return false;
	}

}

// Let's get this show on the road
new Conference_Schedule_API;