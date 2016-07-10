<?php

class Conference_Schedule_Shortcodes {

	/**
	 * Warming things up.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function __construct() {

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
		return conference_schedule()->get_conference_schedule();
	}

}

// Let's get this show on the road
new Conference_Schedule_Shortcodes;