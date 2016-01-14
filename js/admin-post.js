(function( $ ) {
	'use strict';

	// When the document is ready...
	$(document).ready(function() {

		// Set our date picker
		$( '.conf-sch-date-field' ).datepicker();

		// Setup alt field and format for event date picker
		$( '#conf-sch-date' ).datepicker( 'option', 'altField', '#conf-sch-date-alt' );
		$( '#conf-sch-date' ).datepicker( 'option', 'altFormat', 'yy-mm-dd' );

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

		// Change settings for end time
		$( '#conf-sch-end-time' ).timepicker( 'option', 'showDuration', true );
		$( '#conf-sch-end-time' ).timepicker( 'option', 'durationTime', function() { return $( '#conf-sch-start-time').val() } );

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

	});

	// Set the event types for the select2
	function conf_sch_set_event_types() {

		// Get the event types for the select2
		$.ajax( {
			url: '/wp-json/wp/v2/event_types',
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
						url: '/wp-json/wp/v2/schedule/' + $( '#post_ID' ).val() + '/event_types',
						success: function ( $selected_event_types ) {

							// Make sure we have info
							if ( $selected_event_types === undefined || $selected_event_types == '' ) {
								return false;
							}

							// Mark the options selected
							$.each( $selected_event_types, function ($index, $value) {
								$event_types_select.find( 'option[value="' + $value.id + '"]' ).attr( 'selected', true).trigger('change');
							});

						},
						cache: false // @TODO set to true?
					});
				}

			},
			cache: false // @TODO set to true?
		} );

	}

	// Set the session categories for the select2
	function conf_sch_set_session_categories() {

		// Get the session categories for the select2
		$.ajax( {
			url: '/wp-json/wp/v2/session_categories',
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
						url: '/wp-json/wp/v2/schedule/' + $( '#post_ID' ).val() + '/session_categories',
						success: function ( $selected_categories ) {

							// Make sure we have info
							if ( $selected_categories === undefined || $selected_categories == '' ) {
								return false;
							}

							// Mark the options selected
							$.each( $selected_categories, function ($index, $value) {
								$categories_select.find( 'option[value="' + $value.id + '"]' ).attr( 'selected', true).trigger('change');
							});

						},
						cache: false // @TODO set to true?
					});
				}

			},
			cache: false // @TODO set to true?
		} );

	}

	// Set the location for the select2
	function conf_sch_set_location() {

		// Get the location for the select2
		$.ajax( {
			url: '/wp-json/wp/v2/locations',
			success: function ( $locations ) {

				// Make sure we have info
				if ( $locations === undefined || $locations == '' ) {
					return false;
				}

				// Set the <select>
				var $location_select = $( '#conf-sch-location');

				// Reset the <select>
				$location_select.empty();

				// Add the options
				$.each( $locations, function( $index, $value ) {
					$location_select.append( '<option value="' + $value.id + '">' + $value.title.rendered + '</option>' );
				});

				// See what is selected for this particular post
				if ( $( '#post_ID' ).val() != '' ) {
					$.ajax({
						url: '/wp-json/wp/v2/schedule/' + $( '#post_ID' ).val(),
						success: function ( $event ) {

							// Make sure we have info
							if ( $event.event_location === undefined || $event.event_location == '') {
								return false;
							}

							// Mark the location as selected
							$location_select.find( 'option[value="' + $event.event_location + '"]').attr('selected', true).trigger('change');

						},
						cache: false // @TODO set to true?
					});
				}

			},
			cache: false // @TODO set to true?
		} );

	}

})( jQuery );