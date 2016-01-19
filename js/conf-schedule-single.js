(function( $ ) {
	'use strict';

	// Will hold the before and template
	var $conf_sch_single_meta = null;
	var $conf_sch_single_meta_templ = false;

	// Will hold the speakers template
	var $conf_sch_single_speakers = null;
	var $conf_sch_single_speakers_templ = false;

	// When the document is ready...
	$(document).ready(function() {

		// Set the containers
		$conf_sch_single_meta = $( '#conf-sch-single-meta' );

		// Hide speakers so we can fade in
		$conf_sch_single_speakers = $( '#conf-sch-single-speakers').hide();

		// Take care of the before
		var $conf_sch_single_meta_templ_content = $('#conf-sch-single-meta-template').html();
		if ( $conf_sch_single_meta_templ_content !== undefined && $conf_sch_single_meta_templ_content != null ) {

			// Parse the template
			$conf_sch_single_meta_templ = Handlebars.compile( $conf_sch_single_meta_templ_content );

		}

		// Take care of the speakers
		var $conf_sch_single_speakers_templ_content = $('#conf-sch-single-speakers-template').html();
		if ( $conf_sch_single_speakers_templ_content !== undefined && $conf_sch_single_speakers_templ_content != null ) {

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
				$conf_sch_single_meta.hide().html( $conf_sch_single_meta_templ($schedule_item)).fadeIn( 1000 );

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

	// Format the speaker position
	Handlebars.registerHelper( 'speaker_meta', function( $options ) {

		// Make sure we at least have a position
		if ( this.speaker_position !== undefined && this.speaker_position != null ) {

			// Build string
			var $speaker_pos_string = '<span class="speaker-position">' + this.speaker_position + '</span>';

			// Get company
			if ( this.speaker_company !== undefined && this.speaker_company != null ) {

				// Add company name
				var $speaker_company = this.speaker_company;

				// Get company URL
				if ( this.speaker_company_url !== undefined && this.speaker_company_url != null ) {
					$speaker_company = '<a href="' + this.speaker_company_url + '">' + $speaker_company + '</a>';
				}

				// Add to main string
				$speaker_pos_string += ', <span class="speaker-company">' + $speaker_company + '</span>';

				// Add speaker URL
				/*if ( this.speaker_url !== undefined && this.speaker_url != null ) {
					$speaker_pos_string += ' <span class="speaker-url"><a href="' + this.speaker_url + '">' + this.speaker_url + '</a></span>';
				}*/

			}

			return new Handlebars.SafeString('<div class="speaker-meta">' + $speaker_pos_string + '</div>');
		}
		return null;
	});

	// Format the speaker social media
	Handlebars.registerHelper( 'speaker_social_media', function( $options ) {

		// Build string
		var $social_media_string = '';

		//speaker_facebook
		//speaker_instagram
		//speaker_twitter
		//speaker_linkedin

		if ( $social_media_string ) {
			return new Handlebars.SafeString('<ul class="speaker-social-media">' + $social_media_string + '</ul>');
		}
		return null;
	});

})( jQuery );