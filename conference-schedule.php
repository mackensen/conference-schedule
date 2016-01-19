<?php

/**
 * Plugin Name:       Conference Schedule
 * Plugin URI:        @TODO
 * Description:       Helps you build a simple schedule for your conference website.
 * Version:           1.0 // @TODO Change to 0.5 after name is changed
 * Author:            Rachel Carden
 * Author URI:        https://bamadesigner.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       conf-schedule
 * Domain Path:       /languages
 */

// @TODO Add language files

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// If you define them, will they be used?
define( 'CONFERENCE_SCHEDULE_VERSION', '0.5' );
define( 'CONFERENCE_SCHEDULE_PLUGIN_FILE', 'conference-schedule/conference-schedule.php' );

// Require the files we need
require_once plugin_dir_path( __FILE__ ) . 'includes/api.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/shortcodes.php';

// We only need admin functionality in the admin
if ( is_admin() ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/admin.php';
}

// Add support for featured images
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

		// Is this plugin network active?
		$this->is_network_active = is_multisite() && ( $plugins = get_site_option( 'active_sitewide_plugins' ) ) && isset( $plugins[ CONFERENCE_SCHEDULE_PLUGIN_FILE ] );

		// Load our textdomain
		add_action( 'init', array( $this, 'textdomain' ) );

		// Runs on install
		register_activation_hook( __FILE__, array( $this, 'install' ) );

		// Runs when the plugin is upgraded
		add_action( 'upgrader_process_complete', array( $this, 'upgrader_process_complete' ), 1, 2 );

		// Adjust the schedule query
		add_action( 'pre_get_posts', array( $this, 'filter_pre_get_posts' ), 20 );
		add_filter( 'posts_clauses', array( $this, 'filter_posts_clauses' ), 20, 2 );

		// Add needed styles and scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ), 20 );

		// Tweak the event pages
		add_filter( 'the_content', array( $this, 'the_content' ), 1000 );

		// Register custom post types
		add_action( 'init', array( $this, 'register_custom_post_types' ), 0 );

		// Register taxonomies
		add_action( 'init', array( $this, 'register_taxonomies' ), 0 );

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
	 * Runs when the plugin is upgraded.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function upgrader_process_complete() {}

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

			// Setup the orderby
			$pieces[ 'orderby' ] = " CAST( conf_sch_event_date.meta_value AS DATE ) ASC, conf_sch_event_start_time.meta_value ASC, conf_sch_event_end_time ASC";

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
		wp_register_style( 'conf-schedule-icons', trailingslashit( plugin_dir_url( __FILE__ ) . 'css' ) . 'conf-schedule-icons.min.css', array(), CONFERENCE_SCHEDULE_VERSION );

		// Register our schedule styles
		wp_register_style( 'conf-schedule', trailingslashit( plugin_dir_url( __FILE__ ) . 'css' ) . 'conf-schedule.min.css', array( 'conf-schedule-icons' ), CONFERENCE_SCHEDULE_VERSION );

		// Enqueue the schedule script when needed
		if ( is_singular( 'schedule' ) ) {

			// Enqueue our schedule styles
			wp_enqueue_style( 'conf-schedule' );

			// Register handlebars
			wp_register_script( 'handlebars', '//cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.0.5/handlebars.min.js' );

			// Enqueue the schedule script
			wp_enqueue_script( 'conf-schedule-single', trailingslashit( plugin_dir_url( __FILE__ ) . 'js' ) . 'conf-schedule-single.min.js', array( 'jquery', 'handlebars' ), CONFERENCE_SCHEDULE_VERSION, true );

			// Pass some data
			wp_localize_script( 'conf-schedule-single', 'conf_schedule', array(
				'post_id' => $post->ID,
			));

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

		// For tweaking the single schedule pages
		if ( 'schedule' == $post->post_type ) {

			// Add the info holders
			$the_content = '<div id="conf-sch-single-meta"></div>' . $the_content;
			$the_content .= '<div id="conf-sch-single-speakers">
				<h2>Speakers</h2>
			</div>';

			// Add the before template
			$the_content .= '<script id="conf-sch-single-meta-template" type="text/x-handlebars-template">
				{{#event_date_display}}<span class="event-meta event-date"><span class="event-meta-label">Date:</span> {{.}}</span>{{/event_date_display}}
				{{#event_time_display}}<span class="event-meta event-time"><span class="event-meta-label">Time:</span> {{.}}</span>{{/event_time_display}}
				{{#event_location}}<span class="event-meta event-location"><span class="event-meta-label">Location:</span> {{post_title}}</span>{{/event_location}}
				{{#event_links}}{{body}}{{/event_links}}
			</script>';

			// Add the speakers template
			$the_content .= '<script id="conf-sch-single-speakers-template" type="text/x-handlebars-template">
				<div class="event-speaker">
					{{#speaker_thumbnail}}<img class="speaker-thumb" src="{{.}}" />{{/speaker_thumbnail}}
					{{#title}}<h3>{{{rendered}}}</h3>{{/title}}
					{{#speaker_meta}}{{body}}{{/speaker_meta}}
					{{#speaker_social_media}}{{{body}}}{{/speaker_social_media}}
					{{#content}}{{{rendered}}}{{/content}}
				</div>
			</script>';

			//speaker_facebook
			//speaker_instagram
			//speaker_twitter

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

		// Define the labels for the schedule CPT
		$schedule_labels = apply_filters( 'conf_schedule_CPT_labels', array(
			'name'                  => _x( 'Schedule', 'Post Type General Name', 'conf-schedule' ),
			'singular_name'         => _x( 'Event', 'Post Type Singular Name', 'conf-schedule' ),
			'menu_name'             => __( 'Schedule', 'conf-schedule' ),
			'name_admin_bar'        => __( 'Schedule', 'conf-schedule' ),
			'archives'              => __( 'Schedule', 'conf-schedule' ),
			'all_items'             => __( 'All Events', 'conf-schedule' ),
			'add_new_item'          => __( 'Add New Event', 'conf-schedule' ),
			'new_item'              => __( 'New Event', 'conf-schedule' ),
			'edit_item'             => __( 'Edit Event', 'conf-schedule' ),
			'update_item'           => __( 'Update Event', 'conf-schedule' ),
			'view_item'             => __( 'View Event', 'conf-schedule' ),
			'search_items'          => __( 'Search Events', 'conf-schedule' ),
			'not_found'             => __( 'No events found.', 'conf-schedule' ),
			'not_found_in_trash'    => __( 'No events found in the trash.', 'conf-schedule' ),
		));

		// Define the args for the schedule CPT
		$schedule_args = apply_filters( 'conf_schedule_CPT_args', array(
			'label'                 => __( 'Schedule', 'conf-schedule' ),
			'description'           => __( 'The schedule content for your conference.', 'conf-schedule' ),
			'labels'                => $schedule_labels,
			'public'                => true,
			'hierarchical'          => false,
			'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
			'has_archive'           => false,
			'menu_icon'             => 'dashicons-calendar',
			'can_export'            => true,
			'capability_type'       => 'post',
			'show_in_rest'			=> true,
		));

		// Register the schedule custom post type
		register_post_type( 'schedule', $schedule_args );

		// Define the labels for the speakers CPT
		$speakers_labels = apply_filters( 'conf_schedule_speakers_CPT_labels', array(
			'name'                  => _x( 'Speakers', 'Post Type General Name', 'conf-schedule' ),
			'singular_name'         => _x( 'Speaker', 'Post Type Singular Name', 'conf-schedule' ),
			'menu_name'             => __( 'Speakers', 'conf-schedule' ),
			'name_admin_bar'        => __( 'Speakers', 'conf-schedule' ),
			'archives'              => __( 'Speakers', 'conf-schedule' ),
			'all_items'             => __( 'All Speakers', 'conf-schedule' ),
			'add_new_item'          => __( 'Add New Speaker', 'conf-schedule' ),
			'new_item'              => __( 'New Speaker', 'conf-schedule' ),
			'edit_item'             => __( 'Edit Speaker', 'conf-schedule' ),
			'update_item'           => __( 'Update Speaker', 'conf-schedule' ),
			'view_item'             => __( 'View Speaker', 'conf-schedule' ),
			'search_items'          => __( 'Search Speakers', 'conf-schedule' ),
			'not_found'             => __( 'No speakers found.', 'conf-schedule' ),
			'not_found_in_trash'    => __( 'No speakers found in the trash.', 'conf-schedule' ),
		));

		// Define the args for the speakers CPT
		$speakers_args = apply_filters( 'conf_schedule_speakers_CPT_args', array(
			'label'                 => __( 'Speakers', 'conf-schedule' ),
			'description'           => __( 'The speakers content for your conference.', 'conf-schedule' ),
			'labels'                => $speakers_labels,
			'public'                => true,
			'hierarchical'          => false,
			'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
			'has_archive'           => true,
			'menu_icon'             => 'dashicons-admin-users',
			'can_export'            => true,
			'capability_type'       => 'post',
			'show_in_menu'			=> 'edit.php?post_type=schedule',
			'show_in_rest'			=> true,
		));

		// Register the speakers custom post type
		register_post_type( 'speakers', $speakers_args );

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