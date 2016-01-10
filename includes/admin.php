<?php

class Conference_Schedule_Admin {

	/**
	 * Holds the class instance.
	 *
	 * @since	1.0.0
	 * @access	private
	 * @var		Conference_Schedule_Admin
	 */
	private static $instance;

	/**
	 * Returns the instance of this class.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return	Conference_Schedule_Admin
	 */
	public static function instance() {
		if ( ! isset( static::$instance ) ) {
			$className = __CLASS__;
			static::$instance = new $className;
		}
		return static::$instance;
	}

	/**
	 * Warming things up.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	protected function __construct() {

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 1, 2 );

	}

	/**
	 * Method to keep our instance from being cloned.
	 *
	 * @since	1.0.0
	 * @access	private
	 * @return	void
	 */
	private function __clone() {}

	/**
	 * Method to keep our instance from being unserialized.
	 *
	 * @since	1.0.0
	 * @access	private
	 * @return	void
	 */
	private function __wakeup() {}

	/**
	 * Runs when the plugin is installed.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function install() {}

	/**
	 * Adds our admin meta boxes.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function add_meta_boxes( $post_type, $post ) {

		switch( $post_type ) {

			case 'schedule':

				add_meta_box(
					'conf-schedule-event-details',
					__( 'Event Details', 'conf-schedule' ),
					array( $this, 'print_meta_boxes' ),
					$post_type,
					'normal',
					'high'
				);

				break;

		}

	}

	/**
	 * Prints the content in our admin meta boxes.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function print_meta_boxes( $post, $metabox ) {

		switch( $metabox[ 'id' ] ) {

			case 'conf-schedule-event-details':
				break;

		}

	}

}

/**
 * Returns the instance of our Conference_Schedule_Admin class.
 *
 * Will come in handy when we need to access the
 * class to retrieve data throughout the plugin.
 *
 * @since	1.0.0
 * @access	public
 * @return	Conference_Schedule_Admin
 */
function conference_schedule_admin() {
	return Conference_Schedule_Admin::instance();
}

// Let's get this show on the road
conference_schedule_admin();