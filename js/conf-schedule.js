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
		var $conf_sch_templ_content = $('#conference-schedule-template').html();
		if ( $conf_sch_templ_content !== undefined && $conf_sch_templ_content ) {

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
					if ( ! ( $item.event_date !== undefined && $item.event_date ) ) {
						return false;
					}

					// Make sure we have a start time
					if ( ! ( $item.event_start_time !== undefined && $item.event_start_time ) ) {
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

						// Get event types
						var $event_types = [];

						// Add the events
						$.each( $day_items, function ($index, $item) {

							// Get the date
							if ($day_display == '' && $item.event_date_display) {
								$day_display = $item.event_date_display;
							}

							// Get the time
							if ($time_display == '' && $item.event_time_display) {
								$time_display = $item.event_time_display;
							}

							// Render the templates
							$row_events += $conf_sch_templ($item);

							// Store event types
							if ( $item.event_types && $.isArray( $item.event_types ) ) {
								$.each( $item.event_types, function( $index, $type ) {
									if ( $type != '' && $.inArray( $type, $event_types ) == -1 ) {
										$event_types.push($type.replace( /\s/, '-' ));
									}
								});
							}

						});

						// Will hold the row HTML - start with the time
						var $schedule_row_html = '<div class="schedule-row-item time">' + $time_display + '</div>';

						// Add the events
						$schedule_row_html += '<div class="schedule-row-item events">' + $row_events + '</div>';

						// Wrap the row
						$schedule_row_html = '<div class="schedule-row ' + $event_types.join(' ') + '">' + $schedule_row_html + '</div>';

						// Add to the day
						$schedule_day_html += $schedule_row_html;

					});

					// Wrap the day in the table
					$schedule_day_html = '<div class="schedule-table">' + $schedule_day_html + '</div>';

					// Prefix the date header
					$schedule_day_html = '<h2 class="schedule-header">' + $day_display + '</h2>' + $schedule_day_html;

					// Add to schedule
					$schedule_html += $schedule_day_html;

				});

				// Add the html
				$conf_schedule.html( $schedule_html );

			}
		} );

	}

	// Format the title
	Handlebars.registerHelper( 'title', function( $options ) {
		var $new_title = this.title.rendered;
		if ( $new_title !== undefined && $new_title ) {
			if (this.link !== undefined && this.link) {
				$new_title = '<a href="' + this.link + '">' + $new_title + '</a>';
			}
			return new Handlebars.SafeString('<h3 class="event-title">' + $new_title + '</h3>');
		}
		return null;
	});

	// Format the excerpt
	Handlebars.registerHelper( 'excerpt', function( $options ) {
		var $new_excerpt = this.excerpt.rendered;
		if ( $new_excerpt !== undefined && $new_excerpt ) {
			return new Handlebars.SafeString('<div class="event-desc">' + $new_excerpt + '</div>');
		}
		return null;
	});

	// Format the speaker(s)
	Handlebars.registerHelper( 'speakers', function( $options ) {
		// Build speakers
		var $speakers = '';
		if ( this.event_speakers !== undefined && $.isArray( this.event_speakers ) && this.event_speakers.length > 0 ) {
			$.each( this.event_speakers, function($index, $value) {
				$speakers += '<div class="event-speaker">' + $value.post_title + '</div>';
			});
		}
		return new Handlebars.SafeString( '<div class="event-speakers">' + $speakers + '</div>' );
	});

	// Format the event meta links
	Handlebars.registerHelper( 'event_links', function( $options ) {

		// Build the string
		var $event_links_string = '';

		// Do we have a hashtag?
		if ( this.event_hashtag !== undefined && this.event_hashtag ) {
			$event_links_string += '<li class="event-hashtag"><a href="https://twitter.com/search?q=%23' + this.event_hashtag + '"><i class="conf-sch-icon conf-sch-icon-twitter"></i> <span class="icon-label">#' + this.event_hashtag + '</span></a></li>';
		}

		// Do we have a slides URL?
		if ( this.session_slides_url !== undefined && this.session_slides_url ) {
			$event_links_string += '<li class="event-slides"><a href="' + this.session_slides_url + '">View Slides</span></a></li>';
		}

		// Do we have a feedback URL?
		if ( this.session_feedback_url !== undefined && this.session_feedback_url ) {
			$event_links_string += '<li class="event-feedback"><a href="' + this.session_feedback_url + '">Give Feedback</span></a></li>';
		}

		if ( $event_links_string ) {
			return new Handlebars.SafeString('<ul class="conf-sch-event-links">' + $event_links_string + '</ul>');
		}
		return null;
	});

})( jQuery );