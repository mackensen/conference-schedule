(function( $ ) {
	'use strict';

	// When the document is ready...
	$(document).ready(function() {

		// Set our datepicker
		$( '.conf-sch-date-field' ).datepicker();

		// Setup alt field and format for event datepicker
		$( '#conf-sch-date' ).datepicker( 'option', 'altField', '#conf-sch-date-alt' );
		$( '#conf-sch-date' ).datepicker( 'option', 'altFormat', 'yy-mm-dd' );

		// Set our timepicker
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

	});

})( jQuery );