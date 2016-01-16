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

		// Get the templates
		var $conf_sch_templ_content = $('#conference-schedule-display').html();
		if ( $conf_sch_templ_content !== undefined && $conf_sch_templ_content != '' ) {

			// Parse the template
			$conf_sch_templ = Handlebars.compile( $conf_sch_templ_content );

			// Render the schedule
			render_conference_schedule();

		}

	});

	///// FUNCTIONS /////

	// Get/update the schedule
	function render_conference_schedule() {

		// Get the schedule information
		$.ajax( {
			url: '/wp-json/wp/v2/schedule',
			success: function ( $schedule_items ) {

				// Build the HTML
				var $schedule_html = '';

				// Index by date
				var $schedule_by_dates = {};

				// Go through each item
				$.each( $schedule_items, function( $index, $item ) {

					// Make sure we have a date
					if ( ! ( $item.event_date !== undefined && $item.event_date != '' ) ) {
						return false;
					}

					// Make sure we have a start time
					if ( ! ( $item.event_start_time !== undefined && $item.event_start_time != '' ) ) {
						return false;
					}

					// Make sure array exists
					if ( $schedule_by_dates[$item.event_date] === undefined ) {
						$schedule_by_dates[$item.event_date] = {};
					}

					// Make sure start time exists
					if ( $schedule_by_dates[$item.event_date][$item.event_start_time] === undefined ) {
						$schedule_by_dates[$item.event_date][$item.event_start_time] = [];
					}

					// Add this item by date
					$schedule_by_dates[$item.event_date][$item.event_start_time].push( $item );

				});

				// Print out the schedule by date
				$.each( $schedule_by_dates, function( $date, $day_by_time ) {

					// Sort through events by the time
					$.each( $day_by_time, function( $time, $day_items ) {

						// Will hold the event display
						var $date_display = '';

						// Build row HTML
						var $schedule_row_html = '';

						// Add the time
						$schedule_row_html += '<div class="schedule-row-item time">' + $time + '</div>';

						// Build events HTML
						var $row_events = '';

						// Add the events
						$.each( $day_items, function ($index, $item) {

							// Get the date
							if ($date_display == '' && $item.event_date_display !== undefined) {
								$date_display = $item.event_date_display;
							}

							// Render the templates
							$row_events += $conf_sch_templ($item);

						});

						// Wrap all events in a row item
						$schedule_row_html += '<div class="schedule-row-item events">' + $row_events + '</div>';

						// Wrap the day in a row
						$schedule_row_html = '<div class="schedule-row">' + $schedule_row_html + '</div>';

						// Wrap the day in the table
						$schedule_row_html = '<div class="schedule-table">' + $schedule_row_html + '</div>';

						// Prefix the date header
						$schedule_row_html = '<h2 class="schedule-header">' + $date_display + '</h2>' + $schedule_row_html;

						// Add to schedule
						$schedule_html += $schedule_row_html;

					});

				});

				// Add the html
				$conf_schedule.html( $schedule_html );

			},
			cache: false // @TODO set to true
		} );

	}

	// Add link to the title
	Handlebars.registerHelper( 'title', function( $options ) {
		var $new_title = this.title.rendered;
		if ( this.link != '' ) {
			$new_title = '<a href="' + this.link + '">' + $new_title + '</a>';
		}
		return new Handlebars.SafeString( '<h3 class="event-title">' + $new_title + '</h3>');
	});

})( jQuery );