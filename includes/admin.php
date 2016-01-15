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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ), 20 );

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

		// Only for the post pages
		if ( 'schedule' == $post_type && in_array( $hook_suffix, array( 'post.php', 'post-new.php' ) ) ) {

			// Enqueue the UI style
			wp_enqueue_style( 'jquery-ui', '//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css', array(), CONFERENCE_SCHEDULE_VERSION );

			// Enqueue the time picker
			wp_enqueue_style( 'timepicker', trailingslashit( plugin_dir_url( dirname( __FILE__ ) ) . 'css' ) . 'timepicker.min.css', array(), CONFERENCE_SCHEDULE_VERSION );
			wp_register_script( 'timepicker', trailingslashit( plugin_dir_url( dirname( __FILE__ ) ) . 'js' ) . 'timepicker.min.js', array( 'jquery' ), CONFERENCE_SCHEDULE_VERSION, true );

			// Enqueue select2
			wp_enqueue_style( 'select2', trailingslashit( plugin_dir_url( dirname( __FILE__ ) ) . 'css' ) . 'select2.min.css', array(), CONFERENCE_SCHEDULE_VERSION );
			wp_register_script( 'select2', trailingslashit( plugin_dir_url( dirname( __FILE__ ) ) . 'js' ) . 'select2.min.js', array( 'jquery' ), CONFERENCE_SCHEDULE_VERSION, true );

			// Enqueue the post script
			wp_enqueue_script( 'conf-schedule-admin-post', trailingslashit( plugin_dir_url( dirname( __FILE__ ) ) . 'js' ) . 'admin-post.min.js', array( 'jquery', 'jquery-ui-datepicker', 'timepicker', 'select2' ), CONFERENCE_SCHEDULE_VERSION, true );

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

			case 'conf-schedule-speaker-details':
				$this->print_speaker_details_form( $post->ID );
				break;

			case 'conf-schedule-speaker-social-media':
				$this->print_speaker_social_media_form( $post->ID );
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

				// Check if our nonce is set because the 'save_post' action can be triggered at other times
				if ( ! isset( $_POST[ 'conf_schedule_save_event_details_nonce' ] ) ) {
					return;
				}

				// Verify the nonce
				if ( ! wp_verify_nonce( $_POST[ 'conf_schedule_save_event_details_nonce' ], 'conf_schedule_save_event_details' ) ) {
					return;
				}

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

						// If we have a time, format it
						if ( ! empty( $time_value ) ) {
							$time_value = date( 'H:i', strtotime( $time_value ) );
						}

						// Update/save value
						update_post_meta( $post_id, "conf_sch_event_{$time_key}", $time_value );

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

				}

				// Clear out event types meta
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

				}

				// Clear out session categories meta
				else {
					wp_delete_object_term_relationships( $post_id, 'session_categories' );
				}

				// Make sure location is set
				if ( isset( $_POST[ 'conf_schedule' ][ 'event' ][ 'location' ] ) ) {

					// Sanitize the value
					$event_location = sanitize_text_field( $_POST[ 'conf_schedule' ][ 'event' ][ 'location'] );

					// Update/save value
					update_post_meta( $post_id, 'conf_sch_event_location', $event_location );

				}

				// Make sure speakers are set
				if ( isset( $_POST[ 'conf_schedule' ][ 'event' ][ 'speakers' ] ) ) {
					$event_speakers = $_POST[ 'conf_schedule' ][ 'event' ][ 'speakers'];

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

				// Check if our session details nonce is set because the 'save_post' action can be triggered at other times
				if ( isset( $_POST[ 'conf_schedule_save_session_details_nonce' ] ) ) {

					// Verify the nonce
					if ( wp_verify_nonce( $_POST[ 'conf_schedule_save_session_details_nonce' ], 'conf_schedule_save_session_details' ) ) {

						// Process each field
						foreach ( array( 'slides_url', 'feedback_url' ) as $field_name ) {
							if ( isset( $_POST[ 'conf_schedule' ][ 'event' ][ $field_name ] ) ) {

								// Sanitize the value
								$field_value = sanitize_text_field( $_POST[ 'conf_schedule' ][ 'event' ][ $field_name ] );

								// Update/save value
								update_post_meta( $post_id, "conf_sch_event_{$field_name}", $field_value );

							}
						}

						// Process the session file
						if( ! empty( $_FILES )
							&& isset( $_FILES[ 'conf_schedule_event_slides_file' ] )
							&& ! empty( $_FILES[ 'conf_schedule_event_slides_file' ][ 'name' ] ) ) {

							// Upload the file to the server
							$upload_file = wp_handle_upload( $_FILES[ 'conf_schedule_event_slides_file' ], array( 'test_form' => false ) );

							// If the upload was successful...
							if ( $upload_file && ! isset( $upload_file[ 'error'] ) ) {

								// Should be the path to a file in the upload directory
								$file_name = $upload_file[ 'file' ];

								// Get the file type
								$file_type = wp_check_filetype( $file_name );

								// Prepare an array of post data for the attachment.
								$attachment = array(
									'guid'           => $upload_file[ 'url' ],
									'post_mime_type' => $file_type['type'],
									'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $file_name ) ),
									'post_content'   => '',
									'post_status'    => 'inherit'
								);

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

						}

						// Check to see if our 'conf_schedule_event_delete_slides_file' hidden input is included
						else if ( isset( $_POST[ 'conf_schedule_event_delete_slides_file' ] )
							&& $_POST[ 'conf_schedule_event_delete_slides_file' ] > 0 ) {

							// Clear out the meta
							update_post_meta( $post_id, 'conf_sch_event_slides_file', null );

						}

					}

				}

				break;

			case 'events':

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
							foreach ( array( 'facebook', 'instagram', 'twitter' ) as $field_name ) {
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
		$event_date = get_post_meta( $post_id, 'conf_sch_event_date', true ); // Y-m-d
		$event_start_time = get_post_meta( $post_id, 'conf_sch_event_start_time', true );
		$event_end_time = get_post_meta( $post_id, 'conf_sch_event_end_time', true );

		// Convert event date to m/d/Y
		$event_date_mdy = $event_date ? date( 'm/d/Y', strtotime( $event_date ) ) : null;

		?><table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><label for="conf-sch-date">Date</label></th>
					<td>
						<input type="text" id="conf-sch-date" value="<?php echo esc_attr( $event_date_mdy ); ?>" class="conf-sch-date-field" />
						<input name="conf_schedule[event][date]" type="hidden" id="conf-sch-date-alt" value="<?php echo esc_attr( $event_date ); ?>" />
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
				<tr>
					<th scope="row"><label for="conf-sch-event-types">Event Type(s)</label></th>
					<td>
						<select id="conf-sch-event-types" style="width:75%;" name="conf_schedule[event][event_types][]" multiple="multiple">
							<option value="">No event types</option>
						</select>
						<p class="description"><a class="conf-sch-reload-event-types" href="<?php echo admin_url( 'edit-tags.php?taxonomy=event_types&post_type=schedule' ); ?>" target="_blank">Manage the event types</a></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="conf-sch-session-categories">Session Categories</label></th>
					<td>
						<select id="conf-sch-session-categories" style="width:75%;" name="conf_schedule[event][session_categories][]" multiple="multiple">
							<option value="">No session categories</option>
						</select>
						<p class="description"><a class="conf-sch-reload-session-categories" href="<?php echo admin_url( 'edit-tags.php?taxonomy=session_categories&post_type=schedule' ); ?>" target="_blank">Manage the session categories</a></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="conf-sch-location">Location</label></th>
					<td>
						<select id="conf-sch-location" style="width:75%;" name="conf_schedule[event][location]" data-default="No location">
							<option value="">No location</option>
						</select>
						<p class="description"><a class="conf-sch-reload-locations" href="<?php echo admin_url( 'edit.php?post_type=locations' ); ?>" target="_blank">Manage the locations</a></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="conf-sch-speakers">Speaker(s)</label></th>
					<td>
						<select id="conf-sch-speakers" style="width:75%;" name="conf_schedule[event][speakers][]" multiple="multiple">
							<option value="">No speakers</option>
						</select>
						<p class="description"><a class="conf-sch-reload-speakers" href="<?php echo admin_url( 'edit.php?post_type=speakers' ); ?>" target="_blank">Manage the speakers</a></p>
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
		$slides_url = get_post_meta( $post_id, 'conf_sch_event_slides_url', true );
		$slides_file = get_post_meta( $post_id, 'conf_sch_event_slides_file', true );
		$feedback_url = get_post_meta( $post_id, 'conf_sch_event_feedback_url', true );

		?><table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><label for="conf-sch-slides-url">Slides URL</label></th>
					<td>
						<input type="text" id="conf-sch-slides-url" style="width:75%;" name="conf_schedule[event][slides_url]" value="<?php echo esc_attr( $slides_url ); ?>" />
						<p class="description">Please provide the URL (or file below) for users to download or view this session's slides.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="conf-sch-slides-file-input">Slides File</label></th>
					<td><?php

						// Should we hide the input?
						$slides_file_hide_input = false;

						// If selected (and confirmed) file...
						if ( $slides_file > 0 && ( $slides_file_post = get_post( $slides_file ) ) ) {

							// Get URL
							$attached_slides_url = wp_get_attachment_url( $slides_file );

							// Hide the file input
							$slides_file_hide_input = true;

							?><div id="conf-sch-slides-file-info" style="margin:0 0 10px 0;">
								<a style="display:block;margin:0 0 10px 0;" href="<?php echo $attached_slides_url; ?>" target="_blank"><?php echo $attached_slides_url; ?></a>
								<span class="button conf-sch-slides-file-remove" style="clear:both;padding-left:5px;"><span class="dashicons dashicons-no" style="line-height:inherit"></span> Remove the file</span>
							</div><?php

						}

						?><input type="file" accept="application/pdf" id="conf-sch-slides-file-input" style="width:75%;<?php echo $slides_file_hide_input ? 'display:none;' : null; ?>" size="25" name="conf_schedule_event_slides_file" value="" />
						<p class="description">You may also upload a file if you wish to host the session's slides for users to download or view. <strong>Only PDF files are allowed.</strong></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="conf-sch-feedback-url">Feedback URL</label></th>
					<td>
						<input type="text" id="conf-sch-feedback-url" style="width:75%;" name="conf_schedule[event][feedback_url]" value="<?php echo esc_attr( $feedback_url ); ?>" />
						<p class="description">Please provide the URL you wish to provide to gather session feedback. <strong>It will display 30 minutes after the session has started.</strong></p>
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

		?><table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><label for="conf-sch-position">Position</label></th>
					<td>
						<input type="text" id="conf-sch-position" name="conf_schedule[speaker][position]" value="<?php echo esc_attr( $speaker_position ); ?>" class="regular-text" />
						<p class="description">Please provide the speaker's job title.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="conf-sch-url">Website</label></th>
					<td>
						<input type="text" id="conf-sch-url" name="conf_schedule[speaker][url]" value="<?php echo esc_attr( $speaker_url ); ?>" class="regular-text" />
						<p class="description">Please provide the URL for the speaker's website.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="conf-sch-company">Company</label></th>
					<td>
						<input type="text" id="conf-sch-company" name="conf_schedule[speaker][company]" value="<?php echo esc_attr( $speaker_company ); ?>" class="regular-text" />
						<p class="description">Where does the speaker work?</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="conf-sch-company-url">Company Website</label></th>
					<td>
						<input type="text" id="conf-sch-company-url" name="conf_schedule[speaker][company_url]" value="<?php echo esc_attr( $speaker_company_url ); ?>" class="regular-text" />
						<p class="description">Please provide the URL for the speaker's company website.</p>
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

		?><table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><label for="conf-sch-facebook">Facebook</label></th>
					<td>
						<input type="text" id="conf-sch-facebook" name="conf_schedule[speaker][facebook]" value="<?php echo esc_attr( $speaker_facebook ); ?>" class="regular-text" />
						<p class="description">Please provide the full Facebook URL.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="conf-sch-instagram">Instagram</label></th>
					<td>
						<input type="text" id="conf-sch-instagram" name="conf_schedule[speaker][instagram]" value="<?php echo esc_attr( $speaker_instagram ); ?>" class="regular-text" />
						<p class="description">Please provide the Instagram handle or username.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="conf-sch-twitter">Twitter</label></th>
					<td>
						<input type="text" id="conf-sch-twitter" name="conf_schedule[speaker][twitter]" value="<?php echo esc_attr( $speaker_twitter ); ?>" class="regular-text" />
						<p class="description">Please provide the Twitter handle, without the "@".</p>
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