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

		// Setup the select2
		$( '#conf-sch-event-categories').select2();
		$( '#conf-sch-location').select2();

		// Get the event categories for the select2
		$.ajax( {
			url: '/wp-json/wp/v2/schedule_categories',
			success: function ( $categories ) {

				// Make sure we have info
				if ( $categories === undefined || $categories == '' ) {
					return false;
				}

				// Add the options
				$.each( $categories, function( $index, $value ) {
					$( '#conf-sch-event-categories' ).append( '<option value="' + $value.id + '">' + $value.name + '</option>' );
				});

				// See what schedule categories are selected for this particular post
				if ( $( '#post_ID' ).val() != '' ) {
					$.ajax({
						url: '/wp-json/wp/v2/schedule/' + $( '#post_ID' ).val() + '/schedule_categories',
						success: function ($selected_categories) {

							// Make sure we have info
							if ( $selected_categories === undefined || $selected_categories == '' ) {
								return false;
							}

							// Mark the options selected
							$.each( $selected_categories, function ($index, $value) {
								$( '#conf-sch-event-categories option[value="' + $value.id + '"]' ).attr( 'selected', true).trigger('change');
							});

						},
						cache: false // @TODO set to true?
					});
				}

			},
			cache: false // @TODO set to true?
		} );

		// Get the location for the select2
		$.ajax( {
			url: '/wp-json/wp/v2/locations',
			success: function ( $locations ) {

				// Make sure we have info
				if ( $locations === undefined || $locations == '' ) {
					return false;
				}

				// Add the options
				$.each( $locations, function( $index, $value ) {
					$( '#conf-sch-location' ).append( '<option value="' + $value.id + '">' + $value.title.rendered + '</option>' );
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
							$( '#conf-sch-location option[value="' + $event.event_location + '"]').attr('selected', true).trigger('change');

						},
						cache: false // @TODO set to true?
					});
				}

			},
			cache: false // @TODO set to true?
		} );

	});

})( jQuery );