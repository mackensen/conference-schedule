(function( $ ) {
	'use strict';

	// When the document is ready...
	$(document).ready(function() {

		// Set our date picker
		$( '.conf-sch-date-field' ).datepicker();

		// Setup alt field and format for event date picker
		$( '#conf-sch-date' ).datepicker( 'option', 'altField', '#conf-sch-date-alt' );
		$( '#conf-sch-date' ).datepicker( 'option', 'altFormat', 'yy-mm-dd' );

		// When date is cleared, be sure to clear the altField
		$( '#conf-sch-date' ).on( 'change', function() {
			if ( '' == $(this).val() ) {
				$('#conf-sch-date-alt').val('');
			}
		});

		// Set our time picker
		$( '.conf-sch-time-field' ).timepicker({
			step: 15,
			timeFormat: 'g:i a',
			minTime: '5:00 am',
		});

		// Run some code when the start time changes
		$( '#conf-sch-start-time').on( 'changeTime', function() {

			// Change settings for end time
			$( '#conf-sch-end-time' ).timepicker( 'option', 'minTime', $(this).val() );

		});

		// Run some code when the combine start time changes
		$( '#conf-sch-combine-start-time').on( 'changeTime', function() {

			// Change settings for end time
			$( '#conf-sch-combine-end-time' ).timepicker( 'option', 'minTime', $(this).val() );

		});

		// Change settings for end time
		$( '#conf-sch-end-time, #conf-sch-combine-end-time' ).timepicker( 'option', 'showDuration', true );
		$( '#conf-sch-end-time' ).timepicker( 'option', 'durationTime', function() { return $( '#conf-sch-start-time').val() } );
		$( '#conf-sch-combine-end-time' ).timepicker( 'option', 'durationTime', function() { return $( '#conf-sch-combine-start-time').val() } );

		// Enable/disable combine time blocks
		$( '#conf-sch-combine-event' ).change(function() {
			if ( $( this ).is( ':checked' ) ) {
				$( '#conf-sch-combine-event-times' ).removeClass( 'disabled' );
			} else {
				$( '#conf-sch-combine-event-times' ).addClass( 'disabled' );
			}
		});

		// Setup the event types select2
		$( '#conf-sch-event-types').select2();
		conf_sch_set_event_types();

		// Reload event types when you click
		$('.conf-sch-reload-event-types').on('click', function() {
			conf_sch_set_event_types();
		});

		// Setup session categories select2
		$( '#conf-sch-session-categories').select2();
		conf_sch_set_session_categories();

		// Reload session categories when you click
		$('.conf-sch-reload-session-categories').on('click', function() {
			conf_sch_set_session_categories();
		});

		// Setup location select2
		$( '#conf-sch-location').select2();
		conf_sch_set_location();

		// Reload locations when you click
		$('.conf-sch-reload-locations').on('click', function() {
			conf_sch_set_location();
		});

		// Setup sepeakers select2
		$( '#conf-sch-speakers' ).select2();
		conf_sch_set_speakers();

		// Reload speakers when you click
		$('.conf-sch-reload-speakers').on('click', function() {
			conf_sch_set_speakers();
		});

		// Remove the slides file
		$('.conf-sch-slides-file-remove').on('click', function($event) {
			$event.preventDefault();

			// Hide the info
			$('#conf-sch-slides-file-info').hide();

			// Show the file input, clear it out, and add a hidden input to let the admin know to clear the DB
			$('#conf-sch-slides-file-input').show().val('').after( '<input type="hidden" name="conf_schedule_event_delete_slides_file" value="1" />');

		});

	});

	// Set the event types for the select2
	function conf_sch_set_event_types() {

		// Get the event types for the select2
		$.ajax( {
			url: conf_sch.wp_api_route + 'event_types?number=-1',
			success: function ( $types ) {

				// Make sure we have info
				if ( $types === undefined || $types == '' ) {
					return false;
				}

				// Set the <select>
				var $event_types_select = $( '#conf-sch-event-types');

				// Reset the <select>
				$event_types_select.empty();

				// Add the options
				$.each( $types, function( $index, $value ) {
					$event_types_select.append( '<option value="' + $value.id + '">' + $value.name + '</option>' );
				});

				// See what event types are selected for this particular post
				if ( $( '#post_ID' ).val() != '' ) {
					$.ajax({
						url: conf_sch.wp_api_route + 'event_types?post=' + $( '#post_ID' ).val() + '&number=-1',
						success: function ( $selected_event_types ) {

							// Make sure we have info
							if ( $selected_event_types === undefined || $selected_event_types == '' ) {
								return false;
							}

							// Mark the options selected
							$.each( $selected_event_types, function ($index, $value) {
								$event_types_select.find( 'option[value="' + $value.id + '"]' ).attr( 'selected', true).trigger('change');
							});

						}
					});
				}

			}
		} );

	}

	// Set the session categories for the select2
	function conf_sch_set_session_categories() {

		// Get the session categories for the select2
		$.ajax( {
			url: conf_sch.wp_api_route + 'session_categories?number=-1',
			success: function ( $categories ) {

				// Make sure we have info
				if ( $categories === undefined || $categories == '' ) {
					return false;
				}

				// Set the <select>
				var $categories_select = $( '#conf-sch-session-categories');

				// Reset the <select>
				$categories_select.empty();

				// Add the options
				$.each( $categories, function( $index, $value ) {
					$categories_select.append( '<option value="' + $value.id + '">' + $value.name + '</option>' );
				});

				// See what session categories are selected for this particular post
				if ( $( '#post_ID' ).val() != '' ) {
					$.ajax({
						url: conf_sch.wp_api_route + 'session_categories?post=' + $( '#post_ID' ).val() + '&number=-1',
						success: function ( $selected_categories ) {

							// Make sure we have info
							if ( $selected_categories === undefined || $selected_categories == '' ) {
								return false;
							}

							// Mark the options selected
							$.each( $selected_categories, function ($index, $value) {
								$categories_select.find( 'option[value="' + $value.id + '"]' ).attr( 'selected', true).trigger('change');
							});

						}
					});
				}

			}
		} );

	}

	// Set the location for the select2
	function conf_sch_set_location() {

		// Get the location for the select2
		$.ajax( {
			url: conf_sch.wp_api_route + 'locations?filter[posts_per_page]=-1',
			success: function ( $locations ) {

				// Make sure we have info
				if ( $locations === undefined || $locations == '' ) {
					return false;
				}

				// Set the <select>
				var $location_select = $( '#conf-sch-location');

				// Reset the <select>
				$location_select.empty();

				// Add default <option>
				if ( $location_select.data( 'default' ) != '' ) {
					$location_select.append( '<option value="">' + $location_select.data( 'default' ) + '</option>' );
				}

				// Add the options
				$.each( $locations, function( $index, $value ) {
					$location_select.append( '<option value="' + $value.id + '">' + $value.title.rendered + '</option>' );
				});

				// See what is selected for this particular post
				if ( $( '#post_ID' ).val() != '' ) {
					$.ajax({
						url: conf_sch.wp_api_route + 'schedule/' + $( '#post_ID' ).val(),
						success: function ( $event ) {

							// Make sure we have info
							if ( $event.event_location === undefined || $event.event_location == '' ) {
								return false;
							}

							// Mark the location as selected
							$location_select.find( 'option[value="' + $event.event_location.ID + '"]').attr('selected', true).trigger('change');

						}
					});
				}

			}
		} );

	}

	// Set the speakers for the select2
	function conf_sch_set_speakers() {

		// Get the speakers for the select2
		$.ajax( {
			url: conf_sch.wp_api_route + 'speakers?filter[posts_per_page]=-1',
			success: function ( $speakers ) {

				// Make sure we have info
				if ( $speakers === undefined || $speakers == '' ) {
					return false;
				}

				// Set the <select>
				var $speakers_select = $( '#conf-sch-speakers');

				// Reset the <select>
				$speakers_select.empty();

				// Add the options
				$.each( $speakers, function( $index, $value ) {
					$speakers_select.append( '<option value="' + $value.id + '">' + $value.title.rendered + '</option>' );
				});

				// See what is selected for this particular post
				if ( $( '#post_ID' ).val() != '' ) {
					$.ajax({
						url: conf_sch.wp_api_route + 'schedule/' + $( '#post_ID' ).val(),
						success: function ( $event ) {

							// Make sure we have info
							if ( $event.event_speakers === undefined || $event.event_speakers == '') {
								return false;
							}

							// Mark the speaker(s) as selected
							$.each( $event.event_speakers, function ($index, $value) {
								$speakers_select.find( 'option[value="' + $value.ID + '"]' ).attr( 'selected', true).trigger('change');
							});

						}
					});
				}

			}
		} );

	}

})( jQuery );