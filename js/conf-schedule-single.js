(function( $ ) {
	'use strict';

	// Will hold the before and template
	var $conf_sch_single_before = null;
	var $conf_sch_single_before_templ = false;

	// Will hold the after and template
	var $conf_sch_single_after = null;
	var $conf_sch_single_after_templ = false;

	// When the document is ready...
	$(document).ready(function() {

		// Set the schedule container
		$conf_sch_single_before = $( '#conf-sch-single-before' );

		// Take care of the before
		var $conf_sch_single_before_templ_content = $('#conf-sch-single-before-template').html();
		if ( $conf_sch_single_before_templ_content !== undefined && $conf_sch_single_before_templ_content != '' ) {

			// Parse the template
			$conf_sch_single_before_templ = Handlebars.compile( $conf_sch_single_before_templ_content );

			// Render the before
			render_conf_schedule_single_before();

		}

		// Take care of the after
		var $conf_sch_single_after_templ_content = $('#conf-sch-single-after-template').html();
		if ( $conf_sch_single_after_templ_content !== undefined && $conf_sch_single_after_templ_content != '' ) {

			// Parse the template
			$conf_sch_single_after_templ = Handlebars.compile( $conf_sch_single_after_templ_content );

			// Render the after
			//render_conf_schedule_single_after();

		}

	});

	///// FUNCTIONS /////

	// Get/update the before
	function render_conf_schedule_single_before() {

		// Make sure we have an ID
		if ( ! ( conf_schedule.post_id !== undefined && conf_schedule.post_id > 0 ) ) {
			return false;
		}

		// Get the schedule information
		$.ajax( {
			url: '/wp-json/wp/v2/schedule/' + conf_schedule.post_id,
			success: function ( $schedule_item ) {

				console.log($schedule_item);

				// Build the HTML
				var $schedule_html = '';

				// Index by date
				/*var $schedule_by_dates = {};

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

				});*/

				// Print out the schedule by date
				/*$.each( $schedule_by_dates, function( $date, $day_by_time ) {

					// Will hold the day HTML
					var $schedule_day_html = '';

					// Will hold the event day for display
					var $day_display = '';

					// Sort through events by the time
					$.each( $day_by_time, function( $time, $day_items ) {

						// Will hold the time for display
						var $time_display = '';

						// Build events HTML
						var $row_events = '';

						// Add the events
						$.each( $day_items, function ($index, $item) {

							// Get the date
							if ($day_display == '' && $item.event_date_display !== undefined) {
								$day_display = $item.event_date_display;
							}

							// Get the time
							if ($time_display == '' && $item.event_time_display !== undefined) {
								$time_display = $item.event_time_display;
							}

						});

						// Will hold the row HTML - start with the time
						var $schedule_row_html = '<div class="schedule-row-item time">' + $time_display + '</div>';

						// Add the events
						$schedule_row_html += '<div class="schedule-row-item events">' + $row_events + '</div>';

						// Wrap the row
						$schedule_row_html = '<div class="schedule-row">' + $schedule_row_html + '</div>';

						// Add to the day
						$schedule_day_html += $schedule_row_html;

					});

					// Wrap the day in the table
					$schedule_day_html = '<div class="schedule-table">' + $schedule_day_html + '</div>';

					// Prefix the date header
					$schedule_day_html = '<h2 class="schedule-header">' + $day_display + '</h2>' + $schedule_day_html;

					// Add to schedule
					$schedule_html += $schedule_day_html;

				});*/

				// Render the templates
				$schedule_html = $conf_sch_single_before_templ($schedule_item);

				// Add the html
				$conf_sch_single_before.html( $schedule_html );

			},
			cache: false // @TODO set to true
		} );

	}

	// Format the date and time
	Handlebars.registerHelper( 'event_dt', function( $options ) {
		var $date = this.event_date_display;
		if ( $date !== undefined && $date != '' ) {
			return new Handlebars.SafeString('<div class="event-dt">' + $date + '</div>');
		}
		return null;
	});

	// Format the speaker(s)
	Handlebars.registerHelper( 'speakers', function( $options ) {
		// Build speakers
		var $speakers = '';
		if ( this.event_speakers !== undefined && this.event_speakers.length > 0 ) {
			$.each( this.event_speakers, function($index, $value) {
				$speakers += '<div class="event-speaker">' + $value.post_title + '</div>';
			});
		}
		return new Handlebars.SafeString( '<div class="event-speakers">' + $speakers + '</div>' );
	});

})( jQuery );