<?php

/**
 * Plugin Name:       Conference Schedule
 * Plugin URI:        @TODO
 * Description:       @TODO
 * Version:           1.0.0
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
define( 'CONFERENCE_SCHEDULE_VERSION', '1.0.0' );
define( 'CONFERENCE_SCHEDULE_PLUGIN_FILE', 'conference-schedule/conference-schedule.php' );

// We only need admin functionality in the admin
if ( is_admin() ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/admin.php';
}

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

		// Register custom post types
		add_action( 'init', array( $this, 'register_custom_post_types' ), 0 );

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
			'not_found'             => __( 'No events found', 'conf-schedule' ),
			'not_found_in_trash'    => __( 'No events found in Trash', 'conf-schedule' ),
		));

		// Define the args for the schedule CPT
		$schedule_args = apply_filters( 'conf_schedule_CPT_args', array(
			'label'                 => __( 'Schedule', 'conf-schedule' ),
			'description'           => __( 'The schedule content for your conference.', 'conf-schedule' ),
			'labels'                => $schedule_labels,
			'public'                => true,
			'hierarchical'          => false,
			'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
			'has_archive'           => true,
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
			'not_found'             => __( 'No speakers found', 'conf-schedule' ),
			'not_found_in_trash'    => __( 'No speakers found in Trash', 'conf-schedule' ),
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