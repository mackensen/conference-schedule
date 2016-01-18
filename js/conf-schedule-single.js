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

		}

		// Take care of the speakers
		var $conf_sch_single_speakers_templ_content = $('#conf-sch-single-speakers-template').html();
		if ( $conf_sch_single_speakers_templ_content !== undefined && $conf_sch_single_speakers_templ_content != '' ) {

			// Parse the template
			$conf_sch_single_speakers_templ = Handlebars.compile( $conf_sch_single_speakers_templ_content );

		}

		// Render the content
		render_conf_schedule_single();

	});

	///// FUNCTIONS /////

	// Get/update the content
	function render_conf_schedule_single() {

		// Make sure we have an ID
		if ( ! ( conf_schedule.post_id !== undefined && conf_schedule.post_id > 0 ) ) {
			return false;
		}

		// Get the schedule information
		$.ajax( {
			url: '/wp-json/wp/v2/schedule/' + conf_schedule.post_id,
			success: function ( $schedule_item ) {

				// Build/add the html
				$conf_sch_single_before.html( $conf_sch_single_before_templ($schedule_item) );

				// Get the speakers
				if ( $schedule_item.event_speakers !== undefined ) {
					$.each( $schedule_item.event_speakers, function($index, $value){

						// Get the speaker information
						$.ajax({
							url: '/wp-json/wp/v2/speakers/' + $value.ID,
							success: function ($speaker) {

								// Make sure is valid speaker
								if ( ! ( $speaker.id !== undefined && $speaker.id > 0 ) ) {
									return false;
								}

								// Create speaker
								var $speaker_dom = $( $conf_sch_single_speakers_templ($speaker));

								// Render/add the speaker and fade in
								$conf_sch_single_speakers.append( $speaker_dom ).fadeIn( 1000 );

							}
						});

					});
				}

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

	// Format the speaker position
	Handlebars.registerHelper( 'speaker_meta', function( $options ) {

		// Make sure we at least have a position
		if ( this.speaker_position !== undefined && this.speaker_position != '' ) {

			// Build string
			var $speaker_pos_string = '<span class="speaker-position">' + this.speaker_position + '</span>';

			// Get company
			if ( this.speaker_company !== undefined && this.speaker_company != '' ) {

				// Add company name
				var $speaker_company = this.speaker_company;

				// Get company URL
				if ( this.speaker_company_url !== undefined && this.speaker_company_url != '' ) {
					$speaker_company = '<a href="' + this.speaker_company_url + '">' + $speaker_company + '</a>';
				}

				// Add to main string
				$speaker_pos_string += ', <span class="speaker-company">' + $speaker_company + '</span>';
			}

			return new Handlebars.SafeString('<div class="speaker-meta">' + $speaker_pos_string + '</div>');
		}
		return null;
	});

	/*speaker_url: "",
	 speaker_facebook: "",
	 speaker_instagram: "",
	 speaker_twitter: "",*/

})( jQuery );