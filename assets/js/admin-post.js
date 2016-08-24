(function( $ ) {
	'use strict';

	// When the document is ready...
	$(document).ready(function() {

		// Set our date picker
		$( '.conf-sch-date-field' ).datepicker({
			altField: '#conf-sch-date-alt',
			altFormat: 'yy-mm-dd'
		});

		// When date is cleared, be sure to clear the altField
		$( '#conf-sch-date' ).on( 'change', function() {
			if ( '' == $(this).val() ) {
				$( '#conf-sch-date-alt' ).val( '' );
			}
		});

		// Set our time picker
		$( '.conf-sch-time-field' ).timepicker({
			step: 15,
			timeFormat: 'g:i a',
			minTime: '5:00 am',
		});

		// Run some code when the start time changes
		$( '#conf-sch-start-time' ).on( 'changeTime', function() {

			// Change settings for end time
			$( '#conf-sch-end-time' ).timepicker( 'option', 'minTime', $(this).val() );

		});

		// Change settings for end time
		$( '#conf-sch-end-time' ).timepicker( 'option', 'showDuration', true );
		$( '#conf-sch-end-time' ).timepicker( 'option', 'durationTime', function() { return $( '#conf-sch-start-time' ).val() } );

		// Setup the select2 fields
		$( '#conf-sch-event-types' ).select2();
		$( '#conf-sch-session-categories' ).select2();
		$( '#conf-sch-location' ).select2();
		$( '#conf-sch-speakers' ).select2();

		// Populate the events and refresh when you click
		conf_sch_populate_events();
		$( '.conf-sch-refresh-events' ).on( 'click', function( $event ) {
			$event.preventDefault();
			conf_sch_populate_events();
			return false;
		});

		// Populate the event types and refresh when you click
		conf_sch_populate_event_types();
		$( '.conf-sch-refresh-event-types' ).on( 'click', function( $event ) {
			$event.preventDefault();
			conf_sch_populate_event_types();
			return false;
		});

		// Populate the session categories and refresh when you click
		conf_sch_populate_session_categories();
		$( '.conf-sch-refresh-session-categories' ).on( 'click', function( $event ) {
			$event.preventDefault();
			conf_sch_populate_session_categories();
			return false;
		});

		// Populate the locations and refresh when you click
		conf_sch_populate_locations();
		$( '.conf-sch-refresh-locations' ).on( 'click', function( $event ) {
			$event.preventDefault();
			conf_sch_populate_locations();
			return false;
		});

		// Populate the speakers and refresh when you click
		conf_sch_populate_speakers();
		$( '.conf-sch-refresh-speakers' ).on( 'click', function( $event ) {
			$event.preventDefault();
			conf_sch_populate_speakers();
			return false;
		});

		// Remove the slides file
		$( '.conf-sch-slides-file-remove' ).on( 'click', function( $event ) {
			$event.preventDefault();

			// Hide the info
			$( '#conf-sch-slides-file-info' ).hide();

			// Show the file input, clear it out, and add a hidden input to let the admin know to clear the DB
			$( '#conf-sch-slides-file-input' ).show().val( '' ).after( '<input type="hidden" name="conf_schedule_event_delete_slides_file" value="1" />' );

		});

	});

	// Populate the event
	function conf_sch_populate_events() {

		// Set the <select> field and disable
		var $events_select = $( '#conf-sch-event-parent' ).prop( 'disabled', 'disabled' );

		// Get the field information
		$.ajax({
			url: conf_sch.wp_api_route + 'schedule?filter[posts_per_page]=-1&filter[conf_sch_ignore_clause_filter]=1',
			success: function( $select_data ) {

				// Make sure we have info
				if ( undefined === $select_data || '' == $select_data ) {
					return false;
				}

				// Reset the <select>
				$events_select.empty();

				// Add default <option>
				if ( $events_select.data( 'default' ) != '' ) {
					$events_select.append( '<option value="">' + $events_select.data( 'default' ) + '</option>' );
				}

				// Add the options
				$.each( $select_data, function( $index, $value ) {

					// Don't include current event
					if ( undefined !== conf_sch.post_id && conf_sch.post_id == $value.id ) {
						return;
					}

					// Build title string
					var $event_title = $value.title.rendered;

					// Add the option
					$events_select.append( '<option value="' + $value.id + '">' + $event_title + '</option>' );

				});

				// See what is selected for this particular post
				if ( undefined !== conf_sch.post_id && conf_sch.post_id > 0 ) {
					$.ajax({
						url: conf_sch.wp_api_route + 'schedule/' + conf_sch.post_id,
						success: function( $event ) {

							// Make sure we have info
							if ( undefined === $event.event_parent || '' == $event.event_parent ) {
								return false;
							}

							// Mark the location as selected
							$events_select.find( 'option[value="' + $event.event_parent + '"]' ).attr( 'selected', true).trigger( 'change' );

						}
					});
				}

				// Enable the select
				$events_select.prop( 'disabled', false );

			}
		});

	}

	// Populate the event types
	function conf_sch_populate_event_types() {

		// Set the <select> and disable
    	var $event_types_select = $( '#conf-sch-event-types' ).prop( 'disabled', 'disabled' );

		// Get the event types information
		$.ajax({
			url: conf_sch.wp_api_route + 'event_types?number=-1',
			success: function( $types ) {

				// Make sure we have info
				if ( undefined === $types || '' == $types ) {
					return false;
				}

				// Reset the <select>
				$event_types_select.empty();

				// Add the options
				$.each( $types, function( $index, $value ) {
					$event_types_select.append( '<option value="' + $value.id + '">' + $value.name + '</option>' );
				});

				// See what event types are selected for this particular post
				if ( undefined !== conf_sch.post_id && conf_sch.post_id > 0 ) {
					$.ajax({
						url: conf_sch.wp_api_route + 'event_types?post=' + conf_sch.post_id + '&number=-1',
						success: function( $selected_event_types ) {

							// Make sure we have info
							if ( undefined === $selected_event_types || '' == $selected_event_types ) {
								return false;
							}

							// Mark the options selected
							$.each( $selected_event_types, function( $index, $value ) {
								$event_types_select.find( 'option[value="' + $value.id + '"]' ).attr( 'selected', true).trigger( 'change' );
							});

						}
					});
				}

				// Enable the select
				$event_types_select.prop( 'disabled', false );

			}
		});

	}

	// Populate the session categories
	function conf_sch_populate_session_categories() {

		// Set the <select> and disable
		var $categories_select = $( '#conf-sch-session-categories' ).prop( 'disabled', 'disabled' );

		// Get the session categories information
		$.ajax({
			url: conf_sch.wp_api_route + 'session_categories?number=-1',
			success: function( $categories ) {

				// Make sure we have info
				if ( undefined === $categories || '' == $categories ) {
					return false;
				}

				// Reset the <select>
				$categories_select.empty();

				// Add the options
				$.each( $categories, function( $index, $value ) {
					$categories_select.append( '<option value="' + $value.id + '">' + $value.name + '</option>' );
				});

				// See what session categories are selected for this particular post
				if ( undefined !== conf_sch.post_id && conf_sch.post_id > 0 ) {
					$.ajax({
						url: conf_sch.wp_api_route + 'session_categories?post=' + conf_sch.post_id + '&number=-1',
						success: function( $selected_categories ) {

							// Make sure we have info
							if ( undefined === $selected_categories || '' == $selected_categories ) {
								return false;
							}

							// Mark the options selected
							$.each( $selected_categories, function( $index, $value ) {
								$categories_select.find( 'option[value="' + $value.id + '"]' ).attr( 'selected', true).trigger( 'change' );
							});

						}
					});
				}

				// Enable the select
                $categories_select.prop( 'disabled', false );

			}
		});

	}

	// Populate the locations
	function conf_sch_populate_locations() {

		// Set the <select> and disable
		var $locations_select = $( '#conf-sch-location' ).prop( 'disabled', 'disabled' );

		// Get the field information
		$.ajax({
			url: conf_sch.wp_api_route + 'locations?filter[posts_per_page]=-1',
			success: function( $select_data ) {

				// Make sure we have info
				if ( undefined === $select_data || '' == $select_data ) {
					return false;
				}

				// Reset the <select>
				$locations_select.empty();

				// Add default <option>
				if ( $locations_select.data( 'default' ) != '' ) {
					$locations_select.append( '<option value="">' + $locations_select.data( 'default' ) + '</option>' );
				}

				// Add the options
				$.each( $select_data, function( $index, $value ) {
					$locations_select.append( '<option value="' + $value.id + '">' + $value.title.rendered + '</option>' );
				});

				// See what is selected for this particular post
				if ( undefined !== conf_sch.post_id && conf_sch.post_id > 0 ) {
					$.ajax({
						url: conf_sch.wp_api_route + 'schedule/' + conf_sch.post_id,
						success: function( $event ) {

							// Make sure we have info
							if ( undefined === $event.event_location || '' == $event.event_location ) {
								return false;
							}

							// Mark the location as selected
							$locations_select.find( 'option[value="' + $event.event_location.ID + '"]' ).attr( 'selected', true).trigger( 'change' );

						}
					});
				}

				// Enable the select
				$locations_select.prop( 'disabled', false );

			}
		});

	}

	// Populate the speakers field
	function conf_sch_populate_speakers() {

		// Set the <select> and disable
		var $speakers_select = $( '#conf-sch-speakers' ).prop( 'disabled', 'disabled' );

		// Get the speakers information
		$.ajax({
			url: conf_sch.wp_api_route + 'speakers?filter[posts_per_page]=-1',
			success: function( $speakers ) {

				// Make sure we have info
				if ( undefined === $speakers || '' == $speakers ) {
					return false;
				}

				// Reset the <select>
				$speakers_select.empty();

				// Add the options
				$.each( $speakers, function( $index, $value ) {
					$speakers_select.append( '<option value="' + $value.id + '">' + $value.title.rendered + '</option>' );
				});

				// See what is selected for this particular post
				if ( undefined !== conf_sch.post_id && conf_sch.post_id > 0 ) {
					$.ajax({
						url: conf_sch.wp_api_route + 'schedule/' + conf_sch.post_id,
						success: function( $event ) {

							// Make sure we have info
							if ( undefined === $event.event_speakers || '' == $event.event_speakers ) {
								return false;
							}

							// Mark the speaker(s) as selected
							$.each( $event.event_speakers, function( $index, $value ) {
								$speakers_select.find( 'option[value="' + $value.ID + '"]' ).attr( 'selected', true).trigger( 'change' );
							});

						}
					});
				}

				// Enable the select
				$speakers_select.prop( 'disabled', false );

			}
		});

	}

})( jQuery );