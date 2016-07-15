<?php

class Conference_Schedule_Admin {

	/**
	 * ID of the settings page
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string
	 */
	public $settings_page_id;

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
		if ( ! isset( self::$instance ) ) {
			$className = __CLASS__;
			self::$instance = new $className;
		}
		return self::$instance;
	}

	/**
	 * Warming things up.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	protected function __construct() {

		// Add styles and scripts for the tools page
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ), 20 );

		// Add regular settings page
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );

		// Register our settings
		add_action( 'admin_init', array( $this, 'register_settings' ), 1 );

		// Add our settings meta boxes
		add_action( 'admin_head-schedule_page_conf-schedule-settings', array( $this, 'add_settings_meta_boxes' ) );

		// Add instructions to thumbnail admin meta box
		add_filter( 'admin_post_thumbnail_html', array( $this, 'filter_admin_post_thumbnail_html' ), 1, 2 );

		// Add admin notices
		add_action( 'admin_notices', array( $this, 'print_admin_notice' ) );

		// Add meta boxes
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 1, 2 );

		// Remove meta boxes
		add_action( 'admin_menu', array( $this, 'remove_meta_boxes' ), 100 );

		// Save meta box data
		add_action( 'save_post', array( $this, 'save_meta_box_data' ), 20, 3 );

		// Set it up so we can do file uploads
		add_action( 'post_edit_form_tag' , array( $this, 'post_edit_form_tag' ) );

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
	 * Add styles and scripts in the admin.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param	string - $hook_suffix - the ID of the current page
	 */
	public function enqueue_styles_scripts( $hook_suffix ) {
		global $post_type;

		// Only for the settings page
		if ( $this->settings_page_id == $hook_suffix ) {

			// Enqueue our settings styles
			wp_enqueue_style( 'conf-schedule-settings', trailingslashit( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css' ) . 'conf-schedule-settings.min.css', array(), CONFERENCE_SCHEDULE_VERSION );

			// Need these scripts for the meta boxes to work correctly on our settings page
			wp_enqueue_script( 'post' );
			wp_enqueue_script( 'postbox' );

		}

		// Only for the post pages
		if ( 'schedule' == $post_type && in_array( $hook_suffix, array( 'post.php', 'post-new.php' ) ) ) {

			// Register the UI style
			wp_register_style( 'jquery-ui', '//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css', array(), CONFERENCE_SCHEDULE_VERSION );

			// Register the time picker
			wp_register_style( 'timepicker', trailingslashit( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css' ) . 'timepicker.min.css', array(), CONFERENCE_SCHEDULE_VERSION );
			wp_register_script( 'timepicker', trailingslashit( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js' ) . 'timepicker.min.js', array( 'jquery' ), CONFERENCE_SCHEDULE_VERSION, true );

			// Register select2
			wp_register_style( 'select2', trailingslashit( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css' ) . 'select2.min.css', array(), CONFERENCE_SCHEDULE_VERSION );
			wp_register_script( 'select2', trailingslashit( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js' ) . 'select2.min.js', array( 'jquery' ), CONFERENCE_SCHEDULE_VERSION, true );

			// Enqueue the post styles
			wp_enqueue_style( 'conf-schedule-admin-post', trailingslashit( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css' ) . 'admin-post-schedule.min.css', array( 'jquery-ui', 'timepicker', 'select2' ), CONFERENCE_SCHEDULE_VERSION );

			// Enqueue the post script
			wp_enqueue_script( 'conf-schedule-admin-post', trailingslashit( plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js' ) . 'admin-post.min.js', array( 'jquery', 'jquery-ui-datepicker', 'timepicker', 'select2' ), CONFERENCE_SCHEDULE_VERSION, true );

			// Get the API route
			$wp_rest_api_route = function_exists( 'rest_get_url_prefix' ) ? rest_get_url_prefix() : '';
			if ( ! empty( $wp_rest_api_route ) ) {
				$wp_rest_api_route = "/{$wp_rest_api_route}/wp/v2/";
			}
			
			// Pass info to the script
			wp_localize_script( 'conf-schedule-admin-post', 'conf_sch', array(
				'wp_api_route' => $wp_rest_api_route,
			));

		}

	}

	/**
	 * Add our settings page.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function add_settings_page() {
		$this->settings_page_id = add_submenu_page(
			'edit.php?post_type=schedule',
			__( 'Conference Schedule Settings', 'conf-schedule' ),
			__( 'Settings', 'conf-schedule' ),
			'edit_posts',
			'conf-schedule-settings',
			array( $this, 'print_settings_page' )
    	);
	}

	/**
	 * Print our settings page.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function print_settings_page() {

		?><div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1><?php

			// Print the settings form
			?><form method="post" action="options.php" novalidate="novalidate"><?php

				// Setup fields
				settings_fields( 'conf_schedule' );

				?><div id="poststuff">
					<div id="post-body" class="metabox-holder columns-2">

						<div id="postbox-container-1" class="postbox-container">

							<div id="side-sortables" class="meta-box-sortables"><?php
								do_meta_boxes( $this->settings_page_id, 'side', array() );
							?></div> <!-- #side-sortables -->

						</div> <!-- #postbox-container-1 -->

						<div id="postbox-container-2" class="postbox-container">

							<div id="normal-sortables" class="meta-box-sortables"><?php
								do_meta_boxes( $this->settings_page_id, 'normal', array() );
								?></div> <!-- #normal-sortables -->

							<div id="advanced-sortables" class="meta-box-sortables"><?php
								do_meta_boxes( $this->settings_page_id, 'advanced', array() );
								?></div> <!-- #advanced-sortables --><?php

							// Print save button
							submit_button( 'Save Changes', 'primary', 'conf_schedule_save_changes', false );

						?></div> <!-- #postbox-container-2 -->

					</div> <!-- #post-body -->
					<br class="clear" />
				</div> <!-- #poststuff -->

			</form>
		</div> <!-- .wrap --><?php

	}

	/**
	 * Register our settings.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function register_settings() {
		register_setting( 'conf_schedule', 'conf_schedule', array( $this, 'update_settings' ) );
	}

	/**
	 * Updates the 'conf_schedule' setting.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param	array - the settings we're sanitizing
	 * @return	array - the updated settings
	 */
	public function update_settings( $settings ) {
		return $settings;
	}

	/**
	 * Add our settings meta boxes.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function add_settings_meta_boxes() {

		// About this Plugin
		add_meta_box( 'conf-schedule-about-mb', __( 'About this Plugin', 'conf-schedule' ), array( $this, 'print_settings_meta_boxes' ), $this->settings_page_id, 'side', 'core', array( 'id' => 'about' ) );

		// Spread the Love
		add_meta_box( 'conf-schedule-promote-mb', __( 'Spread the Love', 'conf-schedule' ), array( $this, 'print_settings_meta_boxes' ), $this->settings_page_id, 'side', 'core', array( 'id' => 'promote' ) );

		// Displaying the Schedule
		add_meta_box( 'conf-schedule-display-schedule-mb', __( 'Displaying The Schedule', 'conf-schedule' ), array( $this, 'print_settings_meta_boxes' ), $this->settings_page_id, 'normal', 'core', array( 'id' => 'display-schedule' ) );

	}

	/**
	 * Print our settings meta boxes.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param 	array - $post - information about the current post, which is empty because there is no current post on a settings page
	 * @param 	array - $metabox - information about the metabox
	 */
	public function print_settings_meta_boxes( $post, $metabox ) {

		switch( $metabox[ 'args' ][ 'id' ] ) {

			// About meta box
			case 'about':
				?><p><?php _e( 'Helps you build a simple schedule for your conference website.', 'conf-schedule' ); ?></p>
				<p><strong><a href="<?php echo CONFERENCE_SCHEDULE_PLUGIN_URL; ?>" target="_blank"><?php _e( 'Conference Schedule', 'conf-schedule' ); ?></a></strong><br />
				<strong><?php _e( 'Version', 'conf-schedule' ); ?>:</strong> <?php echo CONFERENCE_SCHEDULE_VERSION; ?><br /><strong><?php _e( 'Author', 'conf-schedule' ); ?>:</strong> <a href="https://bamadesigner.com/" target="_blank">Rachel Carden</a></p><?php
				break;

			// Promote meta box
			case 'promote':
				?><p class="twitter"><a href="https://twitter.com/bamadesigner" title="<?php _e( 'Follow bamadesigner on Twitter', 'conf-schedule' ); ?>" target="_blank"><span class="dashicons dashicons-twitter"></span> <span class="promote-text"><?php _e( 'Follow me on Twitter', 'conf-schedule' ); ?></span></a></p>
				<p class="donate"><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=ZCAN2UX7QHZPL&lc=US&item_name=Rachel%20Carden%20%28Conference%20Schedule%29&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted" title="<?php esc_attr_e( 'Donate a few bucks to the plugin', 'conf-schedule' ); ?>" target="_blank"><img src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" alt="<?php esc_attr_e( 'Donate', 'conf-schedule' ); ?>" /> <span class="promote-text"><?php _e( 'and buy me a coffee', 'conf-schedule' ); ?></span></a></p><?php
				break;

			// Displaying The Schedule meta box
			case 'display-schedule':

				// Get the settings
				$settings = conference_schedule()->get_settings();

				// Get the existing pages
				$pages = get_pages();

				// Print the settings table
				?><table id="conf-schedule-settings" class="form-table conf-schedule-settings">
					<tbody>
						<tr>
							<td>
								<strong><?php _e( 'Use the shortcode', 'conf-schedule' ); ?></strong>
								<p class="description"><?php _e( 'Place the shortcode [print_conference_schedule] inside any content to add the schedule to a page.', 'conf-schedule' ); ?></p>
							</td>
						</tr>
						<tr>
							<td>
								<label for="conf-schedule-schedule-add-page"><strong><?php _e( 'Add the schedule to a page', 'conf-schedule' ); ?></strong></label>
								<select name="conf_schedule[schedule_add_page]" id="conf-schedule-schedule-add-page">
									<option value=""><?php _e( 'Do not add to a page', 'conf-schedule' ); ?></option>
									<?php foreach( $pages as $page ) { ?>
										<option value="<?php echo $page->ID; ?>"<?php selected( ! empty( $settings['schedule_add_page'] ) && $page->ID == $settings['schedule_add_page'] ); ?>><?php echo $page->post_title; ?></option>
									<?php } ?>
								</select>
								<p class="description"><?php _e( 'If defined, will automatically add the schedule to the end of the selected page. Otherwise, you can add the schedule with the [print_conference_schedule] shortcode.', 'conf-schedule' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table><?php
				break;

		}

	}

	/**
	 * Adds instructions to the admin thumbnail meta box.
	 *
	 * @access  public
	 * @since   1.1.0
	 */
	public function filter_admin_post_thumbnail_html( $content, $post_id ) {

		// Show instructions for speaker photo
		if ( 'speakers' == get_post_type( $post_id ) ) {
			$content .= '<div class="wp-ui-highlight" style="padding:10px;margin:15px 0 5px 0;">' . __( "Please load the speaker's photo as a featured image. The image needs to be at least 200px wide.", 'conf-schedule' ) . '</div>';
		}

		return $content;
	}

	/**
	 * Prints any needed admin notices.
	 *
	 * @access  public
	 * @since   1.1.0
	 */
	public function print_admin_notice() {

		// Let us know if the REST API plugin, which we depend on, is not active
		if ( ! is_plugin_active( 'WP-API/plugin.php' ) && ! is_plugin_active( 'rest-api/plugin.php' ) ) {
			?><div class="updated notice">
				<p><?php _e( 'The Conference Schedule plugin depends on the REST API plugin, version 2.0. <a href="' . admin_url('plugins.php') . '">Please activate this plugin</a>. ', 'conf-schedule' ); ?></p>
			</div><?php
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

				// Event Details
				add_meta_box(
					'conf-schedule-event-details',
					__( 'Event Details', 'conf-schedule' ),
					array( $this, 'print_meta_boxes' ),
					$post_type,
					'normal',
					'high'
				);

				// Session Details
				add_meta_box(
					'conf-schedule-session-details',
					__( 'Session Details', 'conf-schedule' ),
					array( $this, 'print_meta_boxes' ),
					$post_type,
					'normal',
					'high'
				);

				// Social Media
				add_meta_box(
					'conf-schedule-social-media',
					__( 'Social Media', 'conf-schedule' ),
					array( $this, 'print_meta_boxes' ),
					$post_type,
					'normal',
					'high'
				);

				break;

			case 'speakers':

				// Speaker Details
				add_meta_box(
					'conf-schedule-speaker-details',
					__( 'Speaker Details', 'conf-schedule' ),
					array( $this, 'print_meta_boxes' ),
					$post_type,
					'normal',
					'high'
				);

				// Social Media
				add_meta_box(
					'conf-schedule-speaker-social-media',
					__( 'Social Media', 'conf-schedule' ),
					array( $this, 'print_meta_boxes' ),
					$post_type,
					'normal',
					'high'
				);

				break;

			case 'locations':

				// Location Details
				add_meta_box(
					'conf-schedule-location-details',
					__( 'Location Details', 'conf-schedule' ),
					array( $this, 'print_meta_boxes' ),
					$post_type,
					'normal',
					'high'
				);

				break;

		}

	}

	/**
	 * Removes meta boxes we don't need
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function remove_meta_boxes() {

		// Remove the event types taxonomy meta box
		remove_meta_box( 'tagsdiv-event_types', 'schedule', 'side' );

		// Remove the session categories taxonomy meta box
		remove_meta_box( 'tagsdiv-session_categories', 'schedule', 'side' );

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

			case 'conf-schedule-session-details':
				$this->print_session_details_form( $post->ID );
				break;

			case 'conf-schedule-social-media':
				$this->print_event_social_media_form( $post->ID );
				break;

			case 'conf-schedule-speaker-details':
				$this->print_speaker_details_form( $post->ID );
				break;

			case 'conf-schedule-speaker-social-media':
				$this->print_speaker_social_media_form( $post->ID );
				break;

			case 'conf-schedule-location-details':
				$this->print_location_details_form( $post->ID );
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
				if ( isset( $_POST[ 'conf_schedule' ] ) && isset( $_POST[ 'conf_schedule' ][ 'event' ] ) ) {

					// Check if our nonce is set because the 'save_post' action can be triggered at other times
					if ( isset( $_POST[ 'conf_schedule_save_event_details_nonce' ] ) ) {

						// Verify the nonce
						if ( wp_verify_nonce( $_POST[ 'conf_schedule_save_event_details_nonce' ], 'conf_schedule_save_event_details' ) ) {

							// Make sure date is set
							if ( isset( $_POST[ 'conf_schedule' ][ 'event' ][ 'date' ] ) ) {

								// Sanitize the value
								$event_date = sanitize_text_field( $_POST[ 'conf_schedule' ][ 'event' ][ 'date' ] );

								// Update/save value
								update_post_meta( $post_id, 'conf_sch_event_date', $event_date );

							}

							// Make sure times are set
							foreach ( array( 'start_time', 'end_time', 'combine_start_time', 'combine_end_time' ) as $time_key ) {

								// If we have a value, store it
								if ( isset( $_POST[ 'conf_schedule' ][ 'event' ][ $time_key ] ) ) {

									// Sanitize the value
									$time_value = sanitize_text_field( $_POST[ 'conf_schedule' ][ 'event' ][ $time_key ] );

									// If we have a time, format it
									if ( ! empty( $time_value ) ) {
										$time_value = date( 'H:i', strtotime( $time_value ) );
									}

									// Update/save value
									update_post_meta( $post_id, "conf_sch_event_{$time_key}", $time_value );

								}

								// Otherwise, clear it out
								else {
									update_post_meta( $post_id, "conf_sch_event_{$time_key}", null );
								}

							}

							// Make sure type is set
							if ( isset( $_POST[ 'conf_schedule' ][ 'event' ][ 'event_types' ] ) ) {
								$event_types = $_POST[ 'conf_schedule' ][ 'event' ][ 'event_types' ];

								// Make sure its an array
								if ( ! is_array( $event_types ) ) {
									$event_types = explode( ',', $event_types );
								}

								// Make sure it has only IDs
								$event_types = array_filter( $event_types, 'is_numeric' );

								// Convert to integer
								$event_types = array_map( 'intval', $event_types );

								// Set the terms
								wp_set_object_terms( $post_id, $event_types, 'event_types', false );

							} // Clear out event types meta
							else {
								wp_delete_object_term_relationships( $post_id, 'event_types' );
							}

							// Make sure session categories are set
							if ( isset( $_POST[ 'conf_schedule' ][ 'event' ][ 'session_categories' ] ) ) {
								$session_categories = $_POST[ 'conf_schedule' ][ 'event' ][ 'session_categories' ];

								// Make sure its an array
								if ( ! is_array( $session_categories ) ) {
									$session_categories = explode( ',', $session_categories );
								}

								// Make sure it has only IDs
								$session_categories = array_filter( $session_categories, 'is_numeric' );

								// Convert to integer
								$session_categories = array_map( 'intval', $session_categories );

								// Set the terms
								wp_set_object_terms( $post_id, $session_categories, 'session_categories', false );

							} // Clear out session categories meta
							else {
								wp_delete_object_term_relationships( $post_id, 'session_categories' );
							}

							// Make sure location is set
							if ( isset( $_POST[ 'conf_schedule' ][ 'event' ][ 'location' ] ) ) {

								// Sanitize the value
								$event_location = sanitize_text_field( $_POST[ 'conf_schedule' ][ 'event' ][ 'location' ] );

								// Update/save value
								update_post_meta( $post_id, 'conf_sch_event_location', $event_location );

							}

							// Make sure speakers are set
							if ( isset( $_POST[ 'conf_schedule' ][ 'event' ][ 'speakers' ] ) ) {
								$event_speakers = $_POST[ 'conf_schedule' ][ 'event' ][ 'speakers' ];

								// Make sure its an array
								if ( ! is_array( $event_speakers ) ) {
									$event_speakers = explode( ',', $event_speakers );
								}

								// Make sure it has only IDs
								$event_speakers = array_filter( $event_speakers, 'is_numeric' );

								// Convert to integer
								$event_speakers = array_map( 'intval', $event_speakers );

								// Update/save value
								update_post_meta( $post_id, 'conf_sch_event_speakers', $event_speakers );

							}

							// Clear out speakers meta
							else {
								update_post_meta( $post_id, 'conf_sch_event_speakers', null );
							}

							// Make sure 'conf_sch_combine_event' is set
							if ( isset( $_POST[ 'conf_schedule' ][ 'event' ][ 'combine_event' ] ) ) {
								update_post_meta( $post_id, 'conf_sch_combine_event', true );
							}

							// Clear out 'sch_link_to_post' meta
							else {
								update_post_meta( $post_id, 'conf_sch_combine_event', null );
							}

							// Make sure 'sch_link_to_post' is set
							if ( isset( $_POST[ 'conf_schedule' ][ 'event' ][ 'sch_link_to_post' ] ) ) {
								update_post_meta( $post_id, 'conf_sch_link_to_post', '1' );
							}

							// Clear out 'sch_link_to_post' meta
							else {
								update_post_meta( $post_id, 'conf_sch_link_to_post', '0' );
							}

						}

					}

					// Check if our session details nonce is set because the 'save_post' action can be triggered at other times
					if ( isset( $_POST[ 'conf_schedule_save_session_details_nonce' ] ) ) {

						// Verify the nonce
						if ( wp_verify_nonce( $_POST[ 'conf_schedule_save_session_details_nonce' ], 'conf_schedule_save_session_details' ) ) {

							// Process each field
							foreach ( array( 'livestream_url', 'slides_url', 'feedback_url', 'feedback_reveal_delay_seconds' ) as $field_name ) {
								if ( isset( $_POST[ 'conf_schedule' ][ 'event' ][ $field_name ] ) ) {

									// Sanitize the value
									$field_value = sanitize_text_field( $_POST[ 'conf_schedule' ][ 'event' ][ $field_name ] );

									// Update/save value
									update_post_meta( $post_id, "conf_sch_event_{$field_name}", $field_value );

								}
							}

							// Process the session file
							if ( ! empty( $_FILES ) && isset( $_FILES[ 'conf_schedule_event_slides_file' ] ) && ! empty( $_FILES[ 'conf_schedule_event_slides_file' ][ 'name' ] ) ) {

								// Upload the file to the server
								$upload_file = wp_handle_upload( $_FILES[ 'conf_schedule_event_slides_file' ], array( 'test_form' => false ) );

								// If the upload was successful...
								if ( $upload_file && ! isset( $upload_file[ 'error' ] ) ) {

									// Should be the path to a file in the upload directory
									$file_name = $upload_file[ 'file' ];

									// Get the file type
									$file_type = wp_check_filetype( $file_name );

									// Prepare an array of post data for the attachment.
									$attachment = array( 'guid' => $upload_file[ 'url' ], 'post_mime_type' => $file_type[ 'type' ], 'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $file_name ) ), 'post_content' => '', 'post_status' => 'inherit' );

									// Insert the attachment
									if ( $attachment_id = wp_insert_attachment( $attachment, $file_name, $post_id ) ) {

										// Generate the metadata for the attachment and update the database record
										if ( $attach_data = wp_generate_attachment_metadata( $attachment_id, $file_name ) ) {
											wp_update_attachment_metadata( $attachment_id, $attach_data );
										}

										// Update/save value
										update_post_meta( $post_id, 'conf_sch_event_slides_file', $attachment_id );

									}

								}

							} // Check to see if our 'conf_schedule_event_delete_slides_file' hidden input is included
							else if ( isset( $_POST[ 'conf_schedule_event_delete_slides_file' ] ) && $_POST[ 'conf_schedule_event_delete_slides_file' ] > 0 ) {

								// Clear out the meta
								update_post_meta( $post_id, 'conf_sch_event_slides_file', null );

							}

						}

					}

					// Check if our social media nonce is set because the 'save_post' action can be triggered at other times
					if ( isset( $_POST[ 'conf_schedule_save_event_social_media_nonce' ] ) ) {

						// Verify the nonce
						if ( wp_verify_nonce( $_POST[ 'conf_schedule_save_event_social_media_nonce' ], 'conf_schedule_save_event_social_media' ) ) {

							// Process each field
							foreach ( array( 'hashtag' ) as $field_name ) {
								if ( isset( $_POST[ 'conf_schedule' ][ 'event' ][ $field_name ] ) ) {

									// Sanitize the value
									$field_value = sanitize_text_field( $_POST[ 'conf_schedule' ][ 'event' ][ $field_name ] );

									// Remove any possible hashtags
									$field_value = preg_replace( '/\#/i', '', $field_value );

									// Update/save value
									update_post_meta( $post_id, "conf_sch_event_{$field_name}", $field_value );

								}

							}

						}

					}

				}

				break;

			case 'speakers':

				// Make sure event fields are set
				if ( isset( $_POST[ 'conf_schedule' ] ) && isset( $_POST[ 'conf_schedule' ][ 'speaker' ] ) ) {

					// Check if our speaker details nonce is set because the 'save_post' action can be triggered at other times
					if ( isset( $_POST[ 'conf_schedule_save_speaker_details_nonce' ] ) ) {

						// Verify the nonce
						if ( wp_verify_nonce( $_POST[ 'conf_schedule_save_speaker_details_nonce' ], 'conf_schedule_save_speaker_details' ) ) {

							// Process each field
							foreach ( array( 'position', 'url', 'company', 'company_url' ) as $field_name ) {
								if ( isset( $_POST[ 'conf_schedule' ][ 'speaker' ][ $field_name ] ) ) {

									// Sanitize the value
									$field_value = sanitize_text_field( $_POST[ 'conf_schedule' ][ 'speaker' ][ $field_name ] );

									// Update/save value
									update_post_meta( $post_id, "conf_sch_speaker_{$field_name}", $field_value );

								}
							}

						}

					}

					// Check if our social media nonce is set because the 'save_post' action can be triggered at other times
					if ( isset( $_POST[ 'conf_schedule_save_speaker_social_media_nonce' ] ) ) {

						// Verify the nonce
						if ( wp_verify_nonce( $_POST[ 'conf_schedule_save_speaker_social_media_nonce' ], 'conf_schedule_save_speaker_social_media' ) ) {

							// Process each field
							foreach ( array( 'facebook', 'instagram', 'twitter', 'linkedin' ) as $field_name ) {
								if ( isset( $_POST[ 'conf_schedule' ][ 'speaker' ][ $field_name ] ) ) {

									// Sanitize the value
									$field_value = sanitize_text_field( $_POST[ 'conf_schedule' ][ 'speaker' ][ $field_name ] );

									// Update/save value
									update_post_meta( $post_id, "conf_sch_speaker_{$field_name}", $field_value );

								}
							}

						}

					}

				}

				break;

			case 'locations':

				// Make sure location fields are set
				if ( isset( $_POST[ 'conf_schedule' ] ) && isset( $_POST[ 'conf_schedule' ][ 'location' ] ) ) {

					// Check if our location details nonce is set because the 'save_post' action can be triggered at other times
					if ( isset( $_POST[ 'conf_schedule_save_location_details_nonce' ] ) ) {

						// Verify the nonce
						if ( wp_verify_nonce( $_POST[ 'conf_schedule_save_location_details_nonce' ], 'conf_schedule_save_location_details' ) ) {

							// Process each field
							foreach ( array( 'address', 'google_maps_url' ) as $field_name ) {

								// If we have a value, update the value
								if ( isset( $_POST[ 'conf_schedule' ][ 'location' ][ $field_name ] ) ) {

									// Sanitize the value
									$field_value = sanitize_text_field( $_POST[ 'conf_schedule' ][ 'location' ][ $field_name ] );

									// Update/save value
									update_post_meta( $post_id, "conf_sch_location_{$field_name}", $field_value );

								}

								// Otherwise, clear out the value
								else {
									update_post_meta( $post_id, "conf_sch_location_{$field_name}", null );
								}

							}

						}

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
		global $wpdb;

		// Add a nonce field so we can check for it when saving the data
		wp_nonce_field( 'conf_schedule_save_event_details', 'conf_schedule_save_event_details_nonce' );

		// Get saved event details
		$event_date = get_post_meta( $post_id, 'conf_sch_event_date', true ); // Y-m-d
		$event_start_time = get_post_meta( $post_id, 'conf_sch_event_start_time', true );
		$event_end_time = get_post_meta( $post_id, 'conf_sch_event_end_time', true );

		/**
		 * See if we need to link to the event post in the schedule.
		 *
		 * The default is true.
		 *
		 * If database row doesn't exist, then set as default.
		 * Otherwise, check value.
		 */
		$sch_link_to_post = true;

		// Check the database
		$sch_link_to_post_db = $wpdb->get_var( "SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = {$post_id} AND meta_key = 'conf_sch_link_to_post'" );

		// If row exists, then check the value
		if ( $sch_link_to_post_db ) {
			$sch_link_to_post = get_post_meta( $post_id, 'conf_sch_link_to_post', true );
		}

		// Should we combine this event with other events?
		$sch_combine_event = get_post_meta( $post_id, 'conf_sch_combine_event', true );
		$sch_combine_event_enabled = isset( $sch_combine_event ) && $sch_combine_event;

		// Get combined time block
		$event_combine_start_time = get_post_meta( $post_id, 'conf_sch_event_combine_start_time', true );
		$event_combine_end_time = get_post_meta( $post_id, 'conf_sch_event_combine_end_time', true );

		// Convert event date to m/d/Y
		$event_date_mdy = $event_date ? date( 'm/d/Y', strtotime( $event_date ) ) : null;

		?><table class="form-table conf-schedule-post">
			<tbody>
				<tr>
					<th scope="row"><label for="conf-sch-date"><?php _e( 'Date', 'conf-schedule' ); ?></label></th>
					<td>
						<input type="text" id="conf-sch-date" value="<?php echo esc_attr( $event_date_mdy ); ?>" class="conf-sch-date-field" />
						<input name="conf_schedule[event][date]" type="hidden" id="conf-sch-date-alt" value="<?php echo esc_attr( $event_date ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="conf-sch-start-time"><?php _e( 'Start Time', 'conf-schedule' ); ?></label></th>
					<td>
						<input name="conf_schedule[event][start_time]" type="text" id="conf-sch-start-time" value="<?php echo esc_attr( $event_start_time ); ?>" class="regular-text conf-sch-time-field" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="conf-sch-end-time"><?php _e( 'End Time', 'conf-schedule' ); ?></label></th>
					<td>
						<input name="conf_schedule[event][end_time]" type="text" id="conf-sch-end-time" value="<?php echo esc_attr( $event_end_time ); ?>" class="regular-text conf-sch-time-field" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Combine with other events', 'conf-schedule' ); ?></th>
					<td>
						<label for="conf-sch-combine-event"><input name="conf_schedule[event][combine_event]" type="checkbox" id="conf-sch-combine-event" value="1"<?php checked( $sch_combine_event_enabled ); ?> /> <?php _e( "If checked, this event will be combined with other events on the schedule", 'conf-schedule' ); ?></label>
						<p class="description"><?php _e( 'For example, lightning talks are usually events where multiple sessions equal one block on the schedule. This setting helps combines these sessions on the schedule.', 'conf-schedule' ); ?></p>

						<span id="conf-sch-combine-event-times"<?php echo ! $sch_combine_event_enabled ? ' class="disabled"' : ''; ?>>
							<p class="description"><strong><?php _e( 'Please provide the time block for the combined events.', 'conf-schedule' ); ?></strong></p>

							<label class="label-block" for="conf-sch-combine-start-time"><?php _e( 'Start Time', 'conf-schedule' ); ?></label>
							<input name="conf_schedule[event][combine_start_time]" type="text" id="conf-sch-combine-start-time" value="<?php echo esc_attr( $event_combine_start_time ); ?>" class="regular-text conf-sch-time-field" />

							<br /><br />
							<label class="label-block" for="conf-sch-combine-end-time"><?php _e( 'End Time', 'conf-schedule' ); ?></label>
							<input name="conf_schedule[event][combine_end_time]" type="text" id="conf-sch-combine-end-time" value="<?php echo esc_attr( $event_combine_end_time ); ?>" class="regular-text conf-sch-time-field" />
						</span>

					</td>
				</tr>
				<tr>
					<th scope="row"><label for="conf-sch-event-types"><?php _e( 'Event Types', 'conf-schedule' ); ?></label></th>
					<td>
						<select id="conf-sch-event-types" name="conf_schedule[event][event_types][]" multiple="multiple">
							<option value=""><?php _e( 'No event types', 'conf-schedule' ); ?></option>
						</select>
						<p class="description"><a class="conf-sch-reload-event-types" href="<?php echo admin_url( 'edit-tags.php?taxonomy=event_types&post_type=schedule' ); ?>" target="_blank"><?php _e( 'Manage the event types', 'conf-schedule' ); ?></a></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="conf-sch-session-categories"><?php _e( 'Session Categories', 'conf-schedule' ); ?></label></th>
					<td>
						<select id="conf-sch-session-categories" name="conf_schedule[event][session_categories][]" multiple="multiple">
							<option value=""><?php _e( 'No session categories', 'conf-schedule' ); ?></option>
						</select>
						<p class="description"><a class="conf-sch-reload-session-categories" href="<?php echo admin_url( 'edit-tags.php?taxonomy=session_categories&post_type=schedule' ); ?>" target="_blank"><?php _e( 'Manage the session categories', 'conf-schedule' ); ?></a></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="conf-sch-location"><?php _e( 'Location', 'conf-schedule' ); ?></label></th>
					<td>
						<select id="conf-sch-location" name="conf_schedule[event][location]" data-default="<?php _e( 'No location', 'conf-schedule' ); ?>">
							<option value=""><?php _e( 'No location', 'conf-schedule' ); ?></option>
						</select>
						<p class="description"><a class="conf-sch-reload-locations" href="<?php echo admin_url( 'edit.php?post_type=locations' ); ?>" target="_blank"><?php _e( 'Manage the locations', 'conf-schedule' ); ?></a></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="conf-sch-speakers"><?php _e( 'Speakers', 'conf-schedule' ); ?></label></th>
					<td>
						<select id="conf-sch-speakers" name="conf_schedule[event][speakers][]" multiple="multiple">
							<option value=""><?php _e( 'No speakers', 'conf-schedule' ); ?></option>
						</select>
						<p class="description"><a class="conf-sch-reload-speakers" href="<?php echo admin_url( 'edit.php?post_type=speakers' ); ?>" target="_blank"><?php _e( 'Manage the speakers', 'conf-schedule' ); ?></a></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e( 'Include Link to Event Post in Schedule', 'conf-schedule' ); ?></th>
					<td>
						<label for="conf-sch-link-post"><input name="conf_schedule[event][sch_link_to_post]" type="checkbox" id="conf-sch-link-post" value="1"<?php checked( isset( $sch_link_to_post ) && $sch_link_to_post ); ?> /> <?php _e( "If checked, will include a link to the event's post in the schedule.", 'conf-schedule' ); ?></label>
					</td>
				</tr>
			</tbody>
		</table><?php

	}

	/**
	 * Print the session details form for a particular event.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param	int - $post_id - the ID of the event
	 */
	public function print_session_details_form( $post_id ) {

		// Add a session details nonce field so we can check for it when saving the data
		wp_nonce_field( 'conf_schedule_save_session_details', 'conf_schedule_save_session_details_nonce' );

		// Get saved event details
		$livestream_url = get_post_meta( $post_id, 'conf_sch_event_livestream_url', true );
		$slides_url = get_post_meta( $post_id, 'conf_sch_event_slides_url', true );
		$slides_file = get_post_meta( $post_id, 'conf_sch_event_slides_file', true );
		$feedback_url = get_post_meta( $post_id, 'conf_sch_event_feedback_url', true );
		$feedback_reveal_delay_seconds = get_post_meta( $post_id, 'conf_sch_event_feedback_reveal_delay_seconds', true );

		?><table class="form-table conf-schedule-post">
			<tbody>
				<tr>
					<th scope="row"><label for="conf-sch-livestream-url"><?php _e( 'Livestream URL', 'conf-schedule' ); ?></label></th>
					<td>
						<input type="text" id="conf-sch-livestream-url" name="conf_schedule[event][livestream_url]" value="<?php echo esc_attr( $livestream_url ); ?>" />
						<p class="description"><?php _e( "Please provide the URL for users to view the livestream.", 'conf-schedule' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="conf-sch-slides-url"><?php _e( 'Slides URL', 'conf-schedule' ); ?></label></th>
					<td>
						<input type="text" id="conf-sch-slides-url" name="conf_schedule[event][slides_url]" value="<?php echo esc_attr( $slides_url ); ?>" />
						<p class="description"><?php _e( "Please provide the URL (or file below) for users to download or view this session's slides. <strong>If a URL and file are provided, the URL will priority.</strong>", 'conf-schedule' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="conf-sch-slides-file-input"><?php _e( 'Slides File', 'conf-schedule' ); ?></label></th>
					<td><?php

						// Should we hide the input?
						$slides_file_hide_input = false;

						// If selected file...
						if ( $slides_file > 0 ) {

							// Confirm the file still exists
							if ( $slides_file_post = get_post( $slides_file ) ) {

								// Get URL
								$attached_slides_url = wp_get_attachment_url( $slides_file );

								// Hide the file input
								$slides_file_hide_input = true;

								?><div id="conf-sch-slides-file-info" style="margin:0 0 10px 0;">
									<a style="display:block;margin:0 0 10px 0;" href="<?php echo $attached_slides_url; ?>" target="_blank"><?php echo $attached_slides_url; ?></a>
									<span class="button conf-sch-slides-file-remove" style="clear:both;padding-left:5px;"><span class="dashicons dashicons-no" style="line-height:inherit"></span> <?php _e( 'Remove the file', 'conf-schedule' ); ?></span>
								</div><?php

							}

							// Otherwise clear the meta
							else {
								update_post_meta( $post_id, 'conf_sch_event_slides_file', null );
							}

						}

						?><input type="file" accept="application/pdf" id="conf-sch-slides-file-input" style="width:75%;<?php echo $slides_file_hide_input ? 'display:none;' : null; ?>" size="25" name="conf_schedule_event_slides_file" value="" />
						<p class="description"><?php _e( "You may also upload a file if you wish to host the session's slides for users to download or view. <strong>Only PDF files are allowed.</strong>", 'conf-schedule' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="conf-sch-feedback-url"><?php _e( 'Feedback URL', 'conf-schedule' ); ?></label></th>
					<td>
						<input type="text" id="conf-sch-feedback-url" name="conf_schedule[event][feedback_url]" value="<?php echo esc_attr( $feedback_url ); ?>" />
						<p class="description"><?php _e( 'Please provide the URL you wish to provide to gather session feedback. <strong>It will display 30 minutes after the session has started, unless you provide a value below.</strong>', 'conf-schedule' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="conf-sch-feedback-reveal-delay-seconds"><?php _e( 'Feedback Reveal Delay Seconds', 'conf-schedule' ); ?></label></th>
					<td>
						<input type="text" id="conf-sch-feedback-reveal-delay-seconds" name="conf_schedule[event][feedback_reveal_delay_seconds]" value="<?php echo esc_attr( $feedback_reveal_delay_seconds ); ?>" />
						<p class="description"><?php _e( 'Please provide the number of seconds after the start of the session after which the feedback button will be revealed.  1800 is the default (30 minutes).', 'conf-schedule' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table><?php

	}

	/**
	 * Print the social media form for a particular event.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param	int - $post_id - the ID of the event
	 */
	public function print_event_social_media_form( $post_id ) {

		// Add a nonce field so we can check for it when saving the data
		wp_nonce_field( 'conf_schedule_save_event_social_media', 'conf_schedule_save_event_social_media_nonce' );

		// Get saved social media
		$event_hashtag = get_post_meta( $post_id, 'conf_sch_event_hashtag', true );

		?><table class="form-table conf-schedule-post">
			<tbody>
				<tr>
					<th scope="row"><label for="conf-sch-event-hashtag"><?php _e( 'Hashtag', 'conf-schedule' ); ?></label></th>
					<td>
						<input type="text" id="conf-sch-event-hashtag" name="conf_schedule[event][hashtag]" value="<?php echo esc_attr( $event_hashtag ); ?>" class="regular-text" />
						<p class="description"><?php _e( 'Please provide the hashtag you wish attendees to use for this event. If no hashtag is provided, the schedule will display the speaker(s) Twitter account.', 'conf-schedule' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table><?php

	}

	/**
	 * Print the speaker details form for a particular speaker.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param	int - $post_id - the ID of the speaker
	 */
	public function print_speaker_details_form( $post_id ) {

		// Add a nonce field so we can check for it when saving the data
		wp_nonce_field( 'conf_schedule_save_speaker_details', 'conf_schedule_save_speaker_details_nonce' );

		// Get saved speaker details
		$speaker_position = get_post_meta( $post_id, 'conf_sch_speaker_position', true );
		$speaker_url = get_post_meta( $post_id, 'conf_sch_speaker_url', true );
		$speaker_company = get_post_meta( $post_id, 'conf_sch_speaker_company', true );
		$speaker_company_url = get_post_meta( $post_id, 'conf_sch_speaker_company_url', true );

		?><table class="form-table conf-schedule-post">
			<tbody>
				<tr>
					<th scope="row"><label for="conf-sch-position"><?php _e( 'Position', 'conf-schedule' ); ?></label></th>
					<td>
						<input type="text" id="conf-sch-position" name="conf_schedule[speaker][position]" value="<?php echo esc_attr( $speaker_position ); ?>" class="regular-text" />
						<p class="description"><?php _e( "Please provide the speaker's job title.", 'conf-schedule' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="conf-sch-url"><?php _e( 'Website', 'conf-schedule' ); ?></label></th>
					<td>
						<input type="text" id="conf-sch-url" name="conf_schedule[speaker][url]" value="<?php echo esc_attr( $speaker_url ); ?>" class="regular-text" />
						<p class="description"><?php _e( "Please provide the URL for the speaker's website.", 'conf-schedule' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="conf-sch-company"><?php _e( 'Company', 'conf-schedule' ); ?></label></th>
					<td>
						<input type="text" id="conf-sch-company" name="conf_schedule[speaker][company]" value="<?php echo esc_attr( $speaker_company ); ?>" class="regular-text" />
						<p class="description"><?php _e( 'Where does the speaker work?', 'conf-schedule' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="conf-sch-company-url"><?php _e( 'Company Website', 'conf-schedule' ); ?></label></th>
					<td>
						<input type="text" id="conf-sch-company-url" name="conf_schedule[speaker][company_url]" value="<?php echo esc_attr( $speaker_company_url ); ?>" class="regular-text" />
						<p class="description"><?php _e( "Please provide the URL for the speaker's company website.", 'conf-schedule' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table><?php

	}

	/**
	 * Print the location details form for a particular location.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param	int - $post_id - the ID of the location
	 */
	public function print_location_details_form( $post_id ) {

		// Add a nonce field so we can check for it when saving the data
		wp_nonce_field( 'conf_schedule_save_location_details', 'conf_schedule_save_location_details_nonce' );

		// Get saved location details
		$location_address = get_post_meta( $post_id, 'conf_sch_location_address', true );
		$location_google_maps_url = get_post_meta( $post_id, 'conf_sch_location_google_maps_url', true );

		?><table class="form-table conf-schedule-post">
			<tbody>
				<tr>
					<th scope="row"><label for="conf-sch-address"><?php _e( 'Address', 'conf-schedule' ); ?></label></th>
					<td>
						<input type="text" id="conf-sch-address" name="conf_schedule[location][address]" value="<?php echo esc_attr( $location_address ); ?>" class="regular-text" />
						<p class="description"><?php _e( "Please provide the location's address.", 'conf-schedule' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="conf-sch-google-maps-url"><?php _e( 'Google Maps URL', 'conf-schedule' ); ?></label></th>
					<td>
						<input type="url" id="conf-sch-google-maps-url" name="conf_schedule[location][google_maps_url]" value="<?php echo esc_attr( $location_google_maps_url ); ?>" class="regular-text" />
						<p class="description"><?php _e( "Please provide the Google Maps URL for this location.", 'conf-schedule' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table><?php

	}

	/**
	 * Print the social media form for a particular speaker.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param	int - $post_id - the ID of the speaker
	 */
	public function print_speaker_social_media_form( $post_id ) {

		// Add a nonce field so we can check for it when saving the data
		wp_nonce_field( 'conf_schedule_save_speaker_social_media', 'conf_schedule_save_speaker_social_media_nonce' );

		// Get saved speaker social media
		$speaker_facebook = get_post_meta( $post_id, 'conf_sch_speaker_facebook', true );
		$speaker_instagram = get_post_meta( $post_id, 'conf_sch_speaker_instagram', true );
		$speaker_twitter = get_post_meta( $post_id, 'conf_sch_speaker_twitter', true );
		$speaker_linkedin = get_post_meta( $post_id, 'conf_sch_speaker_linkedin', true );

		?><table class="form-table conf-schedule-post">
			<tbody>
				<tr>
					<th scope="row"><label for="conf-sch-facebook"><?php _e( 'Facebook', 'conf-schedule' ); ?></label></th>
					<td>
						<input type="text" id="conf-sch-facebook" name="conf_schedule[speaker][facebook]" value="<?php echo esc_attr( $speaker_facebook ); ?>" class="regular-text" />
						<p class="description"><?php _e( 'Please provide the full Facebook URL.', 'conf-schedule' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="conf-sch-instagram"><?php _e( 'Instagram', 'conf-schedule' ); ?></label></th>
					<td>
						<input type="text" id="conf-sch-instagram" name="conf_schedule[speaker][instagram]" value="<?php echo esc_attr( $speaker_instagram ); ?>" class="regular-text" />
						<p class="description"><?php _e( 'Please provide the Instagram handle or username.', 'conf-schedule' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="conf-sch-twitter"><?php _e( 'Twitter', 'conf-schedule' ); ?></label></th>
					<td>
						<input type="text" id="conf-sch-twitter" name="conf_schedule[speaker][twitter]" value="<?php echo esc_attr( $speaker_twitter ); ?>" class="regular-text" />
						<p class="description"><?php _e( 'Please provide the Twitter handle, without the "@".', 'conf-schedule' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="conf-sch-linkedin">LinkedIn</label></th>
					<td>
						<input type="text" id="conf-sch-linkedin" name="conf_schedule[speaker][linkedin]" value="<?php echo esc_attr( $speaker_linkedin ); ?>" class="regular-text" />
						<p class="description"><?php _e( 'Please provide the full LinkedIn URL.', 'conf-schedule' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table><?php

	}

	/**
	 * Setup the edit form for file upload.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param	WP_Post - $post - the post object
	 */
	public function post_edit_form_tag( $post ) {

		// Only include when editing the schedule
		if ( 'schedule' == $post->post_type ) {
			echo ' enctype="multipart/form-data"';
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