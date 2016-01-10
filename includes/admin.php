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

		// Add styles and scripts for the tools page
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );

		// Add meta boxes
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 1, 2 );

		// Save meta box data
		add_action( 'save_post', array( $this, 'save_meta_box_data' ), 20, 3 );

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
	 * Add styles and scripts in the admin.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param	string - $hook_suffix - the ID of the current page
	 */
	public function enqueue_styles_scripts( $hook_suffix ) {

		// Only for the post pages
		if ( in_array( $hook_suffix, array( 'post.php', 'post-new.php' ) ) ) {

			// Enqueue the UI style
			wp_enqueue_style( 'jquery-ui', '//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css', array(), CONFERENCE_SCHEDULE_VERSION );

			// Enqueue the time picker
			wp_enqueue_style( 'timepicker', trailingslashit( plugin_dir_url( dirname( __FILE__ ) ) . 'css' ) . 'timepicker.css', array(), CONFERENCE_SCHEDULE_VERSION );
			wp_register_script( 'timepicker', trailingslashit( plugin_dir_url( dirname( __FILE__ ) ) . 'js' ) . 'jquery.timepicker.min.js', array( 'jquery' ), CONFERENCE_SCHEDULE_VERSION, true );

			// Enqueue the post script
			wp_enqueue_script( 'conf-schedule-admin-post', trailingslashit( plugin_dir_url( dirname( __FILE__ ) ) . 'js' ) . 'admin-post.js', array( 'jquery', 'jquery-ui-datepicker', 'timepicker' ), CONFERENCE_SCHEDULE_VERSION, true );

		}

	}

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
				$this->print_event_details_form( $post->ID );
				break;

		}

	}

	/**
	 * When the post is saved, saves our custom meta box data.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param	int - $post_id - the ID of the post being saved
	 * @param	WP_Post - $post - the post object
	 * @param	bool - $update - whether this is an existing post being updated or not
	 */
	function save_meta_box_data( $post_id, $post, $update ) {

		// Check if our nonce is set because the 'save_post' action can be triggered at other times
		if ( ! isset( $_POST[ 'conf_schedule_save_event_details_nonce' ] ) ) {
			return;
		}

		// Verify the nonce
		if ( ! wp_verify_nonce( $_POST[ 'conf_schedule_save_event_details_nonce' ], 'conf_schedule_save_event_details' ) ) {
			return;
		}

		// Disregard on autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Make sure user has permissions
		$post_type_object = get_post_type_object( $post->post_type );
		$user_has_cap = $post_type_object && isset( $post_type_object->cap->edit_post ) ? current_user_can( $post_type_object->cap->edit_post ) : false;

		if ( ! $user_has_cap ) {
			return;
		}

		// Proceed depending on post type
		switch( $post->post_type ) {

			case 'schedule':

				// Make sure fields are set
				if ( ! ( isset( $_POST[ 'conf_schedule' ] ) && isset( $_POST[ 'conf_schedule' ][ 'event' ] ) ) ) {
					return;
				}

				// Make sure date is set
				if ( isset( $_POST[ 'conf_schedule' ][ 'event' ][ 'date' ] ) ) {

					// Sanitize the value
					$event_date = sanitize_text_field( $_POST[ 'conf_schedule' ][ 'event' ][ 'date'] );

					// Update/save value
					update_post_meta( $post_id, 'conf_sch_event_date', $event_date );

				}

				// Make sure times are set
				foreach( array( 'start_time', 'end_time' ) as $time_key ) {

					if ( isset( $_POST[ 'conf_schedule' ][ 'event' ][ $time_key ] ) ) {

						// Sanitize the value
						$time_value = sanitize_text_field( $_POST[ 'conf_schedule' ][ 'event' ][ $time_key ] );

						// Update/save value
						update_post_meta( $post_id, "conf_sch_event_{$time_key}", date( 'H:i', strtotime( $time_value ) ) );

					}

				}

				break;

		}

	}

	/**
	 * Print the event details form for a particular event.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param	int - $post_id - the ID of the event
	 */
	public function print_event_details_form( $post_id ) {

		// Add a nonce field so we can check for it when saving the data
		wp_nonce_field( 'conf_schedule_save_event_details', 'conf_schedule_save_event_details_nonce' );

		// Get saved event details
		$event_date = get_post_meta( $post_id, 'conf_sch_event_date', true );
		$event_start_time = get_post_meta( $post_id, 'conf_sch_event_start_time', true );
		$event_end_time = get_post_meta( $post_id, 'conf_sch_event_end_time', true );

		?><table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><label for="conf-sch-date">Date</label></th>
					<td>
						<input name="conf_schedule[event][date]" type="text" id="conf-sch-date" value="<?php echo esc_attr( $event_date ); ?>" class="regular-text conf-sch-date-field" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="conf-sch-start-time">Start Time</label></th>
					<td>
						<input name="conf_schedule[event][start_time]" type="text" id="conf-sch-start-time" value="<?php echo esc_attr( $event_start_time ); ?>" class="regular-text conf-sch-time-field" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="conf-sch-end-time">End Time</label></th>
					<td>
						<input name="conf_schedule[event][end_time]" type="text" id="conf-sch-end-time" value="<?php echo esc_attr( $event_end_time ); ?>" class="regular-text conf-sch-time-field" />
					</td>
				</tr>
			</tbody>
		</table><?php


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