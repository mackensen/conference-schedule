(function( $ ) {
	'use strict';

	// Will hold the template
	var $conf_sch_templ = false;

	// Will hold the schedule
	var $conf_schedule = null;

	// When the document is ready...
	$(document).ready(function() {

		// Set the schedule container
		$conf_schedule = $( '#conference-schedule' );

		// Make sure we have a template
		if ( $conf_schedule.data( 'template' ) ) {

			// Get the templates
			$conf_sch_templ = $('#' + $conf_schedule.data('template')).html();
			if ( $conf_sch_templ !== undefined && $conf_sch_templ != '' ) {

				// Parse the template
				Mustache.parse( $conf_sch_templ );// optional, speeds up future uses

				// Render the schedule
				render_conference_schedule();

			}

		}

	});

	///// FUNCTIONS /////

	// Get/update the schedule
	function render_conference_schedule() {

		// Get the schedule information
		$.ajax( {
			url: '/wp-json/wp/v2/schedule',
			success: function ( $schedule_items, $text_status, $xhr ) {

				// Add some functionality
				$schedule_items.display_content = function() {
					return 'rachel';
				}

				console.log($schedule_items);

				// Render the templates
				var $rendered_list = Mustache.render( $conf_sch_templ, $schedule_items );

				// Add to the list
				$conf_schedule.html( $rendered_list );

				/*// Unveil the images
				$conf_schedule.find( 'img' ).unveil( 0, function() {
					$(this).load(function() {
						$(this).closest('.list-item').css({'opacity':'1'});
					});
				});
				$conf_schedule.find( 'img:in-viewport' ).trigger( 'unveil' );

				// Load those without an image
				$conf_schedule.find( "img[data-src='']" ).closest('.list-item').css({'opacity':'1'});*/

			},
			cache: false // @TODO set to true
		} );

	}

})( jQuery );