<?php

class Conference_Schedule_Shortcodes {

	/**
	 * Warming things up.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function __construct() {

		// Add needed styles and scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ), 30 );

		// Add our [print_conference_schedule] shortcode
		add_shortcode( 'print_conference_schedule', array( $this, 'print_conference_schedule' ) );

	}

	/**
	 * Add styles and scripts for our shortcodes.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param	string - $hook_suffix - the ID of the current page
	 */
	public function enqueue_styles_scripts() {
		global $post;

		// Enqueue the schedule script when needed
		if ( isset( $post ) && has_shortcode( $post->post_content, 'print_conference_schedule' ) ) {

			// Enqueue our schedule styles
			wp_enqueue_style( 'conf-schedule' );

			// Register handlebars
			wp_register_script( 'handlebars', '//cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.0.5/handlebars.min.js' );

			// Enqueue the schedule script
			wp_enqueue_script( 'conf-schedule', trailingslashit( plugin_dir_url( dirname( __FILE__ ) ) . 'js' ) . 'conf-schedule.min.js', array( 'jquery', 'handlebars' ), CONFERENCE_SCHEDULE_VERSION, true );

			// Pass some translations
			wp_localize_script( 'conf-schedule', 'conf_schedule', array(
				'view_slides' => __( 'View Slides', 'conf-schedule' ),
				'give_feedback' => __( 'Give Feedback', 'conf-schedule' ),
			) );

		}

	}

	/**
	 * Returns the [print_conference_schedule] shortcode content.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param	array - $args - arguments passed to the shortcode
	 * @return	string - the content for the shortcode
	 */
	public function print_conference_schedule( $args = array() ) {

		// Prepare the shortcode args
		//$args = shortcode_atts( array(), $args );

		// Build the content
		// Add the template
		$content = '<script id="conference-schedule-template" type="text/x-handlebars-template">
			<div class="schedule-event{{#event_types}} {{.}}{{/event_types}}">
				{{#event_time_display}}<div class="event-time">{{.}}</div>{{/event_time_display}}
				{{#title}}{{body}}{{/title}}
				{{#event_location}}<div class="event-location">{{post_title}}</div>{{/event_location}}
				{{#speakers}}{{body}}{{/speakers}}
				{{#event_links}}{{body}}{{/event_links}}
			</div>
		</script>';

		// Add the schedule holder
		$content .= '<div id="conference-schedule"></div>';

		/*// What time is it?
		$current_time = new DateTime( 'now', new DateTimeZone( 'America/Chicago' ) );

		?><div class="schedule-main-buttons-wrapper">
			<a href="#" class="btn btn-primary go-to-current-event">Go To Current Event</a>
			</div><?php

			foreach ( $schedule_data as $day_key => $day ) {

				// Create the date for this day
				$day_date = new DateTime( $day_key, new DateTimeZone( 'America/Chicago' ) );

				// Has this date passed?
				//$day_has_passed = $day_date->format( 'j' ) < $current_time->format( 'j' );

				// Wrap in collapsible block
				*//*if ( $day_has_passed ) {
					echo '<div class="collapsible-schedule-block">';
				}*//*

				// Wrap in collapsible block
				*//*if ( $day_has_passed ) {
					echo '</div>';
				}*//*

			}

		?></div><?php */

		return $content;

	}

}

// Let's get this show on the road
new Conference_Schedule_Shortcodes;