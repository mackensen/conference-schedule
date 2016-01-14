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

		// Get the event types for the select2
		$.ajax( {
			url: '/wp-json/wp/v2/schedule_categories',
			success: function ( $categories ) {

				// Make sure we have info
				if ( $categories === undefined || $categories == '' ) {
					return false;
				}

				// Add the options
				$.each( $categories, function( $index, $value ) {
					$( '#conf-sch-event-categories').append( '<option value="' + $value.id + '">' + $value.name + '</option>' );
				});

				// See what is selected
				$.ajax( {
					url: '/wp-json/wp/v2/schedule/133/schedule_categories',
					success: function ( $selected_categories ) {

						// Make sure we have info
						if ( $selected_categories === undefined || $selected_categories == '' ) {
							return false;
						}

						// Mark the options selected
						$.each( $selected_categories, function( $index, $value ) {
							$( '#conf-sch-event-categories option[value="' + $value.id + '"]').attr( 'selected', true ).trigger('change');
						});

					},
					cache: false // @TODO set to true
				} );

			},
			cache: false // @TODO set to true
		} );

	});

})( jQuery );