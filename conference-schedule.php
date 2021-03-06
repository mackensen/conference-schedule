<?php

/**
 * Plugin Name:       Conference Schedule
 * Plugin URI:        @TODO
 * Description:       Helps you build a simple schedule for your conference website.
 * Version:           0.5
 * Author:            Rachel Carden
 * Author URI:        https://bamadesigner.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       conf-schedule
 * Domain Path:       /languages
 */

// @TODO Add language files
// @TODO check all filter names to make sure they make sense
// @TODO make sure, when multiple sessions in a row, they're always in same room order
// @TODO add settings:
	// Need a way to know if they want track labels or not
// @TODO allow for shortcode to only show specific days or time ranges
// @TODO set it up so that past days collapse
// @TODO add button to go to current event?
// @TODO stylize current event(s)
// @TODO setup media library integration with slides file
// @TODO disable saving a post until all API fields load

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// If you define them, will they be used?
define( 'CONFERENCE_SCHEDULE_VERSION', '0.5' );
define( 'CONFERENCE_SCHEDULE_PLUGIN_URL', 'https://github.com/bamadesigner/conference-schedule' );
define( 'CONFERENCE_SCHEDULE_PLUGIN_FILE', 'conference-schedule/conference-schedule.php' );

// Require the files we need
require_once plugin_dir_path( __FILE__ ) . 'includes/speakers.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/events.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/api.php';

// We only need admin functionality in the admin
if ( is_admin() ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/admin.php';
}

// Add theme support for featured images
add_theme_support( 'post-thumbnails' );

class Conference_Schedule {

	/**
	 * Whether or not this plugin is network active.
	 *
	 * @since	1.0.0
	 * @access	public
	 * @var		boolean
	 */
	public $is_network_active;

	/**
	 * Will hold the plugin's settings.
	 *
	 * @since	1.0.0
	 * @access	private
	 * @var		array
	 */
	private $settings;

	/**
	 * Will hold the enabled session fields.
	 *
	 * @since	1.0.0
	 * @access	private
	 * @var		array
	 */
	private $session_fields;

	/**
	 * Will hold the enabled schedule display fields.
	 *
	 * @since	1.0.0
	 * @access	private
	 * @var		array
	 */
	private $schedule_display_fields;

	/**
	 * Holds the class instance.
	 *
	 * @since	1.0.0
	 * @access	private
	 * @var		Conference_Schedule
	 */
	private static $instance;

	/**
	 * Returns the instance of this class.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return	Conference_Schedule
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

		// Is this plugin network active?
		$this->is_network_active = is_multisite() && ( $plugins = get_site_option( 'active_sitewide_plugins' ) ) && isset( $plugins[ CONFERENCE_SCHEDULE_PLUGIN_FILE ] );

		// Load our textdomain
		add_action( 'init', array( $this, 'textdomain' ) );

		// Runs on install
		register_activation_hook( __FILE__, array( $this, 'install' ) );

		// Runs when the plugin is upgraded
		add_action( 'upgrader_process_complete', array( $this, 'upgrader_process_complete' ), 1, 2 );

		// Adjust the schedule query
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		add_action( 'pre_get_posts', array( $this, 'filter_pre_get_posts' ), 20 );
		add_filter( 'posts_clauses', array( $this, 'filter_posts_clauses' ), 20, 2 );

		// Add needed styles and scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ), 20 );

		// Tweak the event pages and add schedule to a page, if necessary
		add_filter( 'the_content', array( $this, 'the_content' ), 1000 );

		// Register custom post types
		add_action( 'init', array( $this, 'register_custom_post_types' ), 0 );

		// Register taxonomies
		add_action( 'init', array( $this, 'register_taxonomies' ), 0 );

		// Add our [print_conference_schedule] shortcode
		add_shortcode( 'print_conference_schedule', array( $this, 'conference_schedule_shortcode' ) );

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
	public function install() {

		// Flush the rewrite rules to start fresh
		flush_rewrite_rules();

	}

	/**
	 * Runs when the plugin is upgraded.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function upgrader_process_complete() {

		// Flush the rewrite rules to start fresh
		flush_rewrite_rules();

	}

	/**
	 * Internationalization FTW.
	 * Load our textdomain.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function textdomain() {
		load_plugin_textdomain( 'conf-schedule', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Returns settings for the front-end.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  array - the settings
	 */
	public function get_settings() {

		// If already set, return the settings
		if ( isset( $this->settings ) ) {
			return $this->settings;
		}

		// Define the default settings
		$default_settings = array(
			'schedule_add_page' => '',
			'session_fields'    => array(
				'slides_url',
			),
			'schedule_display_fields' => array(
				'view_slides',
				'watch_video',
			)
		);

		// Get/store the settings
		return $this->settings = get_option( 'conf_schedule', $default_settings );
	}

	/**
	 * Returns array of enabled session fields.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  array - the enabled session fields
	 */
	public function get_session_fields() {

		// If already set, return the settings
		if ( isset( $this->session_fields ) ) {
			return $this->session_fields;
		}

		// Get settings
		$settings = $this->get_settings();

		// Get enabled session fields
		$session_fields = isset( $settings['session_fields'] ) ? $settings['session_fields'] : array();

		// Make sure its an array
		if ( ! is_array( $session_fields ) ) {
			$session_fields = explode( ', ', $session_fields );
		}

		return $this->session_fields = apply_filters( 'conf_schedule_session_fields', $session_fields );
	}

	/**
	 * Returns array of enabled schedule display fields.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  array - the enabled session fields
	 */
	public function get_schedule_display_fields() {

		// If already set, return the settings
		if ( isset( $this->schedule_display_fields ) ) {
			return $this->schedule_display_fields;
		}

		// Get settings
		$settings = $this->get_settings();

		// Get enabled schedule display fields
		$display_fields = isset( $settings['schedule_display_fields'] ) ? $settings['schedule_display_fields'] : array();

		// Make sure its an array
		if ( ! is_array( $display_fields ) ) {
			$display_fields = explode( ', ', $display_fields );
		}

		return $this->schedule_display_fields = apply_filters( 'conf_schedule_display_fields', $display_fields );
	}

	/**
	 * Add custom query vars.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'conf_sch_ignore_clause_filter';
		return $vars;
	}

	/**
	 * Adjust the schedule query.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function filter_pre_get_posts( $query ) {

		// Not in admin
		if ( is_admin() ) {
			return false;
		}

		// Have to check single array with json queries
		$post_type = $query->get( 'post_type' );
		if ( 'schedule' == $post_type
			|| ( is_array( $post_type ) && in_array( 'schedule', $post_type ) && count( $post_type ) == 1 ) ) {

			// Always get all schedule items
			$query->set( 'posts_per_page' , '-1' );

			// Default order is by title asc
			$query->set( 'orderby', 'title' );
			$query->set( 'order', 'ASC' );

		}

	}

	/**
	 * Filter the queries to "join" and order schedule information.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function filter_posts_clauses( $pieces, $query ) {
		global $wpdb;

		// Not in admin
		if ( is_admin() ) {
			return $pieces;
		}

		// If we pass a filter telling it to ignore our filter
		if ( '1' == $query->get( 'conf_sch_ignore_clause_filter' ) ) {
			return $pieces;
		}

		// Only for schedule query
		$post_type = $query->get( 'post_type' );
		if ( 'schedule' == $post_type
			|| ( is_array( $post_type ) && in_array( 'schedule', $post_type ) && count( $post_type ) == 1 ) ) {

			// Join to get name info
			foreach( array( 'conf_sch_event_date', 'conf_sch_event_start_time', 'conf_sch_event_end_time' ) as $name_part ) {

				// Might as well store the join info as fields
				$pieces[ 'fields' ] .= ", {$name_part}.meta_value AS {$name_part}";

				// "Join" to get the info
				$pieces[ 'join' ] .= " LEFT JOIN {$wpdb->postmeta} {$name_part} ON {$name_part}.post_id = {$wpdb->posts}.ID AND {$name_part}.meta_key = '{$name_part}'";

			}

			// Get the location information
			$pieces[ 'fields' ] .= ", IF ( conf_sch_event_location.meta_value IS NOT NULL, ( SELECT post_title FROM {$wpdb->posts} WHERE ID = conf_sch_event_location.meta_value ), '' ) AS conf_sch_event_location";
			$pieces[ 'join' ] .= " LEFT JOIN {$wpdb->postmeta} conf_sch_event_location ON conf_sch_event_location.post_id = {$wpdb->posts}.ID AND conf_sch_event_location.meta_key = 'conf_sch_event_location'";

			// Setup the orderby
			$pieces[ 'orderby' ] = " CAST( conf_sch_event_date.meta_value AS DATE ) ASC, conf_sch_event_start_time.meta_value ASC, conf_sch_event_location ASC, conf_sch_event_end_time ASC";

		}

		return $pieces;
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

		// Register our icons
		wp_register_style( 'conf-schedule-icons', trailingslashit( plugin_dir_url( __FILE__ ) . 'assets/css' ) . 'conf-schedule-icons.min.css', array(), CONFERENCE_SCHEDULE_VERSION );

		// Register our schedule styles
		wp_register_style( 'conf-schedule', trailingslashit( plugin_dir_url( __FILE__ ) . 'assets/css' ) . 'conf-schedule.min.css', array( 'conf-schedule-icons' ), CONFERENCE_SCHEDULE_VERSION );

		// Register handlebars
		wp_register_script( 'handlebars', '//cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.0.5/handlebars.min.js' );

		// Get the API route
		$wp_rest_api_route = function_exists( 'rest_get_url_prefix' ) ? rest_get_url_prefix() : '';
		if ( ! empty( $wp_rest_api_route ) ) {
			$wp_rest_api_route = "/{$wp_rest_api_route}/wp/v2/";
		}

		// Enqueue the schedule script when needed
		if ( is_singular( 'schedule' ) ) {

			// Enqueue our schedule styles
			wp_enqueue_style( 'conf-schedule' );

			// Enqueue the schedule script
			wp_enqueue_script( 'conf-schedule-single', trailingslashit( plugin_dir_url( __FILE__ ) . 'assets/js' ) . 'conf-schedule-single.min.js', array( 'jquery', 'handlebars' ), CONFERENCE_SCHEDULE_VERSION, true );

			// Build data
			$conf_sch_data = array(
				'post_id'       => $post->ID,
				'wp_api_route'  => $wp_rest_api_route,
			);

			// Get display field settings
			$display_fields = conference_schedule()->get_schedule_display_fields();

			// Figure out which fields to display
			if ( ! empty( $display_fields ) ) {

				// If we're set to view slides...
				if ( in_array( 'view_slides', $display_fields ) ) {
					$conf_sch_data['view_slides'] = __( 'View Slides', 'conf-schedule' );
				}

				// If we're set to give feedback
				if ( in_array( 'give_feedback', $display_fields ) ) {
					$conf_sch_data['give_feedback'] = __( 'Give Feedback', 'conf-schedule' );
				}

				// If we're set to watch the video
				if ( in_array( 'watch_video', $display_fields ) ) {
					$conf_sch_data['watch_video'] = __( 'Watch Session', 'conf-schedule' );
				}

			}

			// Pass some data
			wp_localize_script( 'conf-schedule-single', 'conf_sch', $conf_sch_data );

		} else {

			// Does this post have our shortcode?
			$has_schedule_shortcode = isset( $post ) && has_shortcode( $post->post_content, 'print_conference_schedule' );

			// If not the shortcode, do we want to add the schedule to the page?
			$add_schedule_to_page = ! $has_schedule_shortcode ? $this->add_schedule_to_page() : false;

			// Enqueue the schedule script when needed
			if ( $has_schedule_shortcode || $add_schedule_to_page ) {

				// Enqueue our schedule styles
				wp_enqueue_style( 'conf-schedule' );

				// Enqueue the schedule script
				wp_enqueue_script( 'conf-schedule', trailingslashit( plugin_dir_url( __FILE__ ) . 'assets/js' ) . 'conf-schedule.min.js', array( 'jquery', 'handlebars' ), CONFERENCE_SCHEDULE_VERSION, true );

				// Build data.
				$conf_sch_data = array(
					'wp_api_route'  => $wp_rest_api_route,
				);

				// Get display field settings
				$display_fields = conference_schedule()->get_schedule_display_fields();

				// Figure out which fields to display
				if ( ! empty( $display_fields ) ) {

					// If we're set to view slides...
					if ( in_array( 'view_slides', $display_fields ) ) {
						$conf_sch_data['view_slides'] = __( 'View Slides', 'conf-schedule' );
					}

					// If we're set to give feedback
					if ( in_array( 'give_feedback', $display_fields ) ) {
						$conf_sch_data['give_feedback'] = __( 'Give Feedback', 'conf-schedule' );
					}

					// If we're set to watch the video
					if ( in_array( 'watch_video', $display_fields ) ) {
						$conf_sch_data['watch_video'] = __( 'Watch Session', 'conf-schedule' );
					}

				}

				// Pass some translations
				wp_localize_script( 'conf-schedule', 'conf_sch', $conf_sch_data );

			}

		}

	}

	/**
	 * Filter the content.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param	string - $the_content - the content
	 * @return	string - the filtered content
	 */
	public function the_content( $the_content ) {
		global $post;

		// For tweaking the single schedule pages.
		if ( 'schedule' == get_query_var( 'post_type' ) ) {

			// Get the settings.
			$settings = $this->get_settings();

			// Get post type object.
			$speakers_post_type_obj = get_post_type_object( 'speakers' );

			// Get post type's archive title.
			$speakers_archive_title = apply_filters( 'post_type_archive_title', $speakers_post_type_obj->labels->name, 'speakers' );

			ob_start();

			// If we have pre HTML...
			if ( ! empty( $settings['pre_event_html'] ) ) :

				// Filter the message.
				$pre_html = apply_filters( 'conf_schedule_pre_event_message', $settings['pre_event_html'] );
				if ( ! empty( $pre_html ) ) :

					?>
					<div class="conf-sch-pre-event-message"><?php echo wpautop( $pre_html ); ?></div>
					<?php

				endif;
			endif;

			// Add livestream holder.
			?>
			<div id="conf-sch-single-livestream"></div>
			<?php

			// Add the livestream template.
			?>
			<script id="conf-sch-single-ls-template" type="text/x-handlebars-template">
				{{#if session_livestream_url}}<div class="callout"><a href="{{session_livestream_url}}"><?php _e( 'Watch the livestream', 'conf-schedule' ); ?></a></div>{{/if}}
			</script>
			<?php

			// Add the info holders.
			?>
			<div id="conf-sch-single-meta"></div>
			<?php

			// Print the content.
			echo $the_content;

			// Embed the video.
			$video_url = get_post_meta( $post->ID, 'conf_sch_event_video_url', true );
			if ( ! empty( $video_url ) ) {

				// Get embed.
				$video_html = wp_oembed_get( $video_url, array(
					'height' => 450,
				));

				// Filter video html.
				$video_html = apply_filters( 'conf_schedule_session_video_html', $video_html, $video_url, $post->ID );
				if ( ! empty( $video_html ) ) {

					// Print embed.
					?>
					<div id="conf-sch-single-video">
						<h2><?php _e( 'Watch The Session', 'conf-schedule' ); ?></h2>
						<?php echo ! empty( $video_html ) ? $video_html : ''; ?>
					</div>
					<?php

				}

			}

			?>
			<div id="conf-sch-single-speakers">
				<h2 class="conf-sch-single-speakers-title"><?php echo $speakers_archive_title; ?></h2>
			</div>
			<script id="conf-sch-single-meta-template" type="text/x-handlebars-template">
				{{#event_date_display}}<span class="event-meta event-date"><span class="event-meta-label"><?php _e( 'Date', 'conf-schedule' ); ?>:</span> {{.}}</span>{{/event_date_display}}
				{{#event_time_display}}<span class="event-meta event-time"><span class="event-meta-label"><?php _e( 'Time', 'conf-schedule' ); ?>:</span> {{.}}</span>{{/event_time_display}}
				{{#event_location}}<span class="event-meta event-location"><span class="event-meta-label"><?php _e( 'Location', 'conf-schedule' ); ?>:</span> {{post_title}}</span>{{/event_location}}
				{{#if session_categories}}<span class="event-meta event-categories"><span class="event-meta-label"><?php _e( 'Categories', 'conf-schedule' ); ?>:</span> {{#each session_categories}}{{#unless @first}}, {{/unless}}{{.}}{{/each}}</span>{{/if}}
				{{#event_links}}{{body}}{{/event_links}}
			</script>
			<script id="conf-sch-single-speakers-template" type="text/x-handlebars-template">
				<div class="event-speaker">
					{{#if title.rendered}}<h3 class="speaker-name">{{{title.rendered}}}</h3>{{/if}}
					{{{speaker_meta}}}
					{{{speaker_social_media}}}
					{{#if content}}
						<div class="speaker-bio{{#if speaker_thumbnail}} has-photo{{/if}}">
							{{#if speaker_thumbnail}}<img class="speaker-thumb" src="{{speaker_thumbnail}}" />{{/if}}
							{{{content.rendered}}}
						</div>
					{{/if}}
				</div>
			</script>
			<?php

			// If we have post HTML...
			if ( ! empty( $settings['post_event_html'] ) ) :

				// Filter the message.
				$post_html = apply_filters( 'conf_schedule_post_event_message', $settings['post_event_html'] );
				if ( ! empty( $post_html ) ) :

					?>
					<div class="conf-sch-post-event-message"><?php echo wpautop( $post_html ); ?></div>
					<?php

				endif;
			endif;

			return ob_get_clean();
		}

		// If we want to add the schedule to a page...
		if ( $this->add_schedule_to_page() ) {

			// Add the schedule.
			$the_content .= $this->get_conference_schedule();

		}

		return $the_content;
	}

	/**
	 * Registers our plugins's custom post types.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function register_custom_post_types() {

		// Define the labels for the locations CPT
		$locations_labels = apply_filters( 'conf_schedule_locations_CPT_labels', array(
			'name'                  => _x( 'Locations', 'Post Type General Name', 'conf-schedule' ),
			'singular_name'         => _x( 'Location', 'Post Type Singular Name', 'conf-schedule' ),
			'menu_name'             => __( 'Locations', 'conf-schedule' ),
			'name_admin_bar'        => __( 'Locations', 'conf-schedule' ),
			'archives'              => __( 'Locations', 'conf-schedule' ),
			'all_items'             => __( 'All Locations', 'conf-schedule' ),
			'add_new_item'          => __( 'Add New Location', 'conf-schedule' ),
			'new_item'              => __( 'New Location', 'conf-schedule' ),
			'edit_item'             => __( 'Edit Location', 'conf-schedule' ),
			'update_item'           => __( 'Update Location', 'conf-schedule' ),
			'view_item'             => __( 'View Location', 'conf-schedule' ),
			'search_items'          => __( 'Search Locations', 'conf-schedule' ),
			'not_found'             => __( 'No locations found.', 'conf-schedule' ),
			'not_found_in_trash'    => __( 'No locations found in Trash', 'conf-schedule' ),
		));

		// Define the args for the locations CPT
		$locations_args = apply_filters( 'conf_schedule_locations_CPT_args', array(
			'label'                 => __( 'Locations', 'conf-schedule' ),
			'description'           => __( 'The locations content for your conference.', 'conf-schedule' ),
			'labels'                => $locations_labels,
			'public'                => true,
			'hierarchical'          => false,
			'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
			'has_archive'           => true,
			'menu_icon'             => 'dashicons-location',
			'can_export'            => true,
			'capability_type'       => 'post',
			'show_in_menu'			=> 'edit.php?post_type=schedule',
			'show_in_rest'			=> true,
		));

		// Register the locations custom post type
		register_post_type( 'locations', $locations_args );

	}

	/**
	 * Registers our plugins's taxonomies.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function register_taxonomies() {

		// Define the labels for the event types taxonomy
		$types_labels = apply_filters( 'conf_schedule_event_types_labels', array(
			'name'						=> _x( 'Event Types', 'Taxonomy General Name', 'conf-schedule' ),
			'singular_name'				=> _x( 'Event Type', 'Taxonomy Singular Name', 'conf-schedule' ),
			'menu_name'					=> __( 'Event Types', 'conf-schedule' ),
			'all_items'					=> __( 'All Event Types', 'conf-schedule' ),
			'new_item_name'				=> __( 'New Event Type', 'conf-schedule' ),
			'add_new_item'				=> __( 'Add New Event Type', 'conf-schedule' ),
			'edit_item'					=> __( 'Edit Event Type', 'conf-schedule' ),
			'update_item'				=> __( 'Update Event Type', 'conf-schedule' ),
			'view_item'					=> __( 'View Event Type', 'conf-schedule' ),
			'separate_items_with_commas'=> __( 'Separate event types with commas', 'conf-schedule' ),
			'add_or_remove_items'		=> __( 'Add or remove event types', 'conf-schedule' ),
			'choose_from_most_used'		=> __( 'Choose from the most used event types', 'conf-schedule' ),
			'popular_items'				=> __( 'Popular event types', 'conf-schedule' ),
			'search_items'				=> __( 'Search Event Types', 'conf-schedule' ),
			'not_found'					=> __( 'No event types found.', 'conf-schedule' ),
			'no_terms'					=> __( 'No event types', 'conf-schedule' ),
		));

		// Define the arguments for the event types taxonomy
		$types_args = apply_filters( 'conf_schedule_event_types_args', array(
			'labels'					=> $types_labels,
			'hierarchical'				=> false,
			'public'					=> true,
			'show_ui'					=> true,
			'show_admin_column'			=> true,
			'show_in_nav_menus'			=> true,
			'show_tagcloud'				=> false,
			'meta_box_cb'				=> 'post_categories_meta_box',
			'show_in_rest'				=> true,
		));

		// Register the event types taxonomy
		register_taxonomy( 'event_types', array( 'schedule' ), $types_args );

		// Define the labels for the session categories taxonomy
		$session_categories_labels = apply_filters( 'conf_schedule_session_categories_labels', array(
			'name'						=> _x( 'Session Categories', 'Taxonomy General Name', 'conf-schedule' ),
			'singular_name'				=> _x( 'Session Category', 'Taxonomy Singular Name', 'conf-schedule' ),
			'menu_name'					=> __( 'Session Categories', 'conf-schedule' ),
			'all_items'					=> __( 'All Session Categories', 'conf-schedule' ),
			'new_item_name'				=> __( 'New Session Category', 'conf-schedule' ),
			'add_new_item'				=> __( 'Add New Session Category', 'conf-schedule' ),
			'edit_item'					=> __( 'Edit Session Category', 'conf-schedule' ),
			'update_item'				=> __( 'Update Session Category', 'conf-schedule' ),
			'view_item'					=> __( 'View Session Category', 'conf-schedule' ),
			'separate_items_with_commas'=> __( 'Separate session categories with commas', 'conf-schedule' ),
			'add_or_remove_items'		=> __( 'Add or remove session categories', 'conf-schedule' ),
			'choose_from_most_used'		=> __( 'Choose from the most used session categories', 'conf-schedule' ),
			'popular_items'				=> __( 'Popular session categories', 'conf-schedule' ),
			'search_items'				=> __( 'Search Session Categories', 'conf-schedule' ),
			'not_found'					=> __( 'No session categories found.', 'conf-schedule' ),
			'no_terms'					=> __( 'No session categories', 'conf-schedule' ),
		));

		// Define the arguments for the session categories taxonomy
		$session_categories_args = apply_filters( 'conf_schedule_session_categories_args', array(
			'labels'					=> $session_categories_labels,
			'hierarchical'				=> false,
			'public'					=> true,
			'show_ui'					=> true,
			'show_admin_column'			=> true,
			'show_in_nav_menus'			=> true,
			'show_tagcloud'				=> false,
			'meta_box_cb'				=> 'post_categories_meta_box',
			'show_in_rest'				=> true,
		));

		// Register the session categories taxonomy
		register_taxonomy( 'session_categories', array( 'schedule' ), $session_categories_args );

	}

	/**
	 * Returns true if, setting wise,
	 * we should add the schedule to the current page.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return	string - the schedule
	 */
	public function add_schedule_to_page() {
		global $post;

		// Make sure we have an ID and a post type
		if ( empty( $post->ID ) || empty( $post->post_type ) ) {
			return false;
		}

		// We only add to pages
		if ( 'page' != $post->post_type ) {
			return false;
		}

		// Get settings
		$settings = $this->get_settings();

		// If we want to add the schedule to this page...
		if ( ! empty( $settings['schedule_add_page'] ) && $settings['schedule_add_page'] > 0 ) {
			if ( $post->ID == $settings['schedule_add_page'] ) {

				// Add the schedule
				return true;

			}
		}

		return false;
	}

	/**
	 * Get the conference schedule.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return	string - the schedule
	 */
	public function get_conference_schedule() {
		ob_start();

		// Get settings.
		$settings = $this->get_settings();

		?>
		<div id="conference-schedule-container" class="loading">
			<?php

			// If we have pre HTML...
			if ( ! empty( $settings['pre_html'] ) ) :

				// Filter the message.
				$pre_html = apply_filters( 'conf_schedule_pre_schedule_message', $settings['pre_html'] );
				if ( ! empty( $pre_html ) ) :

					?>
					<div class="schedule-pre-message"><?php echo wpautop( $pre_html ); ?></div>
					<?php

				endif;
			endif;

			?>
			<div id="conference-schedule"></div>
			<?php

			// If we have post HTML...
			if ( ! empty( $settings['post_html'] ) ) :

				// Filter the message.
				$post_html = apply_filters( 'conf_schedule_post_schedule_message', $settings['post_html'] );
				if ( ! empty( $post_html ) ) :

					?>
					<div class="schedule-post-message"><?php echo wpautop( $post_html ); ?></div>
					<?php

				endif;
			endif;

			?>
		</div>
		<script id="conference-schedule-template" type="text/x-handlebars-template">
			<div id="conf-sch-event-{{id}}" class="schedule-event{{#if event_parent}} event-child{{/if}}{{#event_types}} {{.}}{{/event_types}}">
				{{#event_time_display}}<div class="event-time">{{.}}</div>{{/event_time_display}}
				{{#title}}{{body}}{{/title}}
				{{#event_location}}<div class="event-location">{{post_title}}</div>{{/event_location}}
				{{#if event_address}}<div class="event-address">{{#if event_google_maps_url}}<a href="{{event_google_maps_url}}">{{/if}}{{event_address}}{{#if event_google_maps_url}}</a>{{/if}}</div>{{/if}}
				{{#if event_speakers}}<div class="event-speakers">{{#each event_speakers}}<div class="event-speaker">{{post_title}}</div>{{/each}}</div>{{/if}}
				{{#if session_categories}}<div class="event-categories">{{#each session_categories}}{{#unless @first}}, {{/unless}}{{.}}{{/each}}</div>{{/if}}
				{{#event_links}}{{body}}{{/event_links}}
			</div>
		</script>
		<?php

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

		return ob_get_clean();

	}

	/**
	 * Returns the [print_conference_schedule] shortcode content.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @param   array - $args - arguments passed to the shortcode
	 * @return  string - the content for the shortcode
	 */
	public function conference_schedule_shortcode( $args = array() ) {
		return conference_schedule()->get_conference_schedule();
	}

}

/**
 * Returns the instance of our main Conference_Schedule class.
 *
 * Will come in handy when we need to access the
 * class to retrieve data throughout the plugin.
 *
 * @since	1.0.0
 * @access	public
 * @return	Conference_Schedule
 */
function conference_schedule() {
	return Conference_Schedule::instance();
}

// Let's get this show on the road
conference_schedule();