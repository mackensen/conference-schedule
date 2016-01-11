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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ), 20 );

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

			// Register mustache
			wp_register_script( 'mustache', trailingslashit( plugin_dir_url( dirname( __FILE__ ) ) . 'js' ) . 'mustache.min.js', array( 'jquery' ) );

			// Register unveil and viewport
			//wp_register_script( 'viewport', $sa_framework_dir . 'js/viewport.min.js', array( 'jquery' ) );
			//wp_register_script( 'unveil', $sa_framework_dir . 'js/unveil.min.js', array( 'jquery', 'viewport' ) );

			// Enqueue the schedule script
			wp_enqueue_script( 'conf-schedule-display', trailingslashit( plugin_dir_url( dirname( __FILE__ ) ) . 'js' ) . 'conference-schedule-display.min.js', array( 'jquery', 'mustache' ), CONFERENCE_SCHEDULE_VERSION, true );

			// Pass some data
			wp_localize_script( 'conf-schedule-display', 'conf_schedule', array(
				'plugin_path' => trailingslashit( plugin_dir_path( dirname( __FILE__ ) ) ),
			));

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
		$content = null;

		// Add the template
		$content .= '<script id="conference-schedule-display" type="x-tmpl-mustache">
			{{#.}}
				<div class="schedule-event{{#event_categories}} {{.}}{{/event_categories}}">
					{{#title}}<h3 class="event-title">{{{rendered}}}</h3>{{/title}}
					{{#excerpt}}<div class="event-desc">{{{rendered}}}</div>{{/excerpt}}
				</div>
			{{/.}}
		</script>';

		// Add the schedule holder
		$content .= '<div id="conference-schedule" data-template="conference-schedule-display"></div>';

		return $content;

	}

}

// Let's get this show on the road
new Conference_Schedule_Shortcodes;