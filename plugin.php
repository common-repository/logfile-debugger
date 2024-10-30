<?php
/*
 * Plugin Name: Log file debugger
 * Plugin URI:
 * Description: This plugin is intended for developers. It provides some debugging/logging functions, that should only be used during development: You can call these methods in your php code to produce log-output. (Attention: Every logged-in user can potentially access the logfile, so for security reasons this plugin should only be installed and activated on a development environment!)
 * Version:     1.3
 * Author:      Philipp Stracker
 * Author URI:  http://www.stracker.net
 * License:     GPLv2 or Later
 */

// The functions.php file contains the actual debugging functions
require_once 'functions.php';

DebugLog::init();


/**
 * The DebugLog class provides the Admin interface for the debug plugin.
 *
 * @since  1.0.0
 */
class DebugLog {
	/**
	 * The slug to the settings-page.
	 *
 	 * @since  1.0.0
 	 * @var    string
	 */
	const SETTING_SLUG = 'debug-log';

	/**
	 * Identifier of this plugin in WordPress.
	 * Basically the path of this file, relative to the "plugins" directory.
	 *
 	 * @since  1.0.0
 	 * @var    string
	 */
	static $plugin_name = '';

	/**
	 * Sets up the action-hooks for this plugin.
	 *
 	 * @since  1.0.0
	 */
	static public function init() {
		self::$plugin_name = plugin_basename( __FILE__ );

		// Adds the entry in the admin menu
		add_action( 'admin_menu', 'DebugLog::add_menu' );

		// Add a quick-link to the options-page in the plugin screen
		add_filter( 'plugin_action_links_' . self::$plugin_name, 'DebugLog::settings_link' );

		// Add the ajax listener for logged in users
		add_filter( 'wp_ajax_logfile', 'DebugLog::handle_ajax' );
	}

	/**
	 * This will hook up a new submenu item in the tools-menu.
	 *
 	 * @since  1.0.0
	 */
	static public function add_menu() {
		add_management_page(
			'View log file',
			'View log file',
			'delete_users', // Only show this menu item for the super-admin
			self::SETTING_SLUG,
			'DebugLog::render_options'
		);
	}

	/**
	 * Displays a direct link to the plugins option-page in the plugin-overview.
	 *
 	 * @since  1.0.0
	 */
	static public function settings_link( $links ) {
		$settings_link = '<a href="tools.php?page=' . self::SETTING_SLUG . '">Debug Log</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Renders the plugin options page.
	 * User can turn debugging on/off on this page + see the log file contents.
	 *
 	 * @since  1.0.0
	 */
	static public function render_options() {

		// this should make it little bit more difficult to mess with the parameter
		$ajax_url = base64_encode( admin_url( 'admin-ajax.php' ) );
		?>
		<h3>Usage</h3>
		<a href="<?php echo plugin_dir_url( __FILE__ ) ?>logfile.php?dq=<?php echo $ajax_url; ?>" target="_blank" style="float: right;margin-right: 20px">Open in popup</a>
		<p>
			You can use the following methods to debug your code:
			<ul>
				<li><code>wp_debug( variable1, variable2, ... );</code> Adds a new entry to the log file [<a href="tools.php?page=<?php echo self::SETTING_SLUG; ?>&demo=debug">Demo</a>]</li>
				<li><code>wp_describe( variable1, variable2, ... );</code> Displays the variables on screen [<a href="tools.php?page=<?php echo self::SETTING_SLUG; ?>&demo=describe">Demo</a>]</li>
				<li><code>wp_debug_trace();</code> Adds a stack-trace to the log file [<a href="tools.php?page=<?php echo self::SETTING_SLUG; ?>&demo=trace">Demo</a>]</li>
			</ul>
		</p>
		<hr />
		<?php
		$demo = isset( $_GET['demo'] ) ? $_GET['demo'] : '';
		switch ( $demo ) {
			case 'debug':
				if ( DebugLogFunctions::get_status() ) {
					wp_debug( 'Debugging a single string variable' );
					wp_debug( 'This is a debug-demo with 3 variables', array( 'time' => time(), 'user_id' => get_current_user_id() ), 'Title of this installation: <title>' . strip_tags( get_bloginfo( 'title' ) ) . '</title>' );
				} else {
					echo "<p><em>Nothing happened: Debugging is turned off.</em></p><hr />";
				}
				break;

			case 'describe':
				if ( DebugLogFunctions::get_status() ) {
					wp_describe( 'Describing a single string variable' );
					wp_describe( 'This is a describe-demo with 3 variables', array( 'time' => time(), 'user_id' => get_current_user_id() ), 'Title of this installation: <title>' . strip_tags( get_bloginfo( 'title' ) ) . '</title>' );
				} else {
					echo "<p><em>Nothing happened: Debugging is turned off.</em></p><hr />";
				}
				break;

			case 'trace':
				if ( DebugLogFunctions::get_status() ) {
					wp_debug_trace();
				} else {
					echo "<p><em>Nothing happened: Debugging is turned off.</em></p><hr />";
				}
				break;
		}
		include 'logfile.php';
	}

	/**
	 * Handles the ajax-request.
	 * No input parameters: Directly fetch the parameters from $_POST.
	 * No return value: Directly output the ajax response and then die().
	 *
 	 * @since  1.0.0
	 */
	static public function handle_ajax() {
		$action = isset( $_POST['query'] ) ? $_POST['query'] : '';
		$status = DebugLogFunctions::get_status() ? '1' : '0';
		$type = DebugLogFunctions::get_opt( 'dumptype', '0' );
		$data = '';

		switch ( $action ) {
			case 'dumptype':
				// change the format of variable dumps
				$type = isset( $_POST['type'] ) ? $_POST['type'] : 0;
				DebugLogFunctions::set_opt( 'dumptype', $type );
				break;

			case 'flush':
				// Empty the log file
				DebugLogFunctions::flush();
				break;

			case 'enable':
				// Enable logging
				DebugLogFunctions::set_status( true );
				$status = '1';
				break;

			case 'disable':
				// Disable logging
				DebugLogFunctions::set_status( false );
				$status = '0';
				break;

			default:
				// Refresh: Output the logfile contents
				$data = DebugLogFunctions::get_logfile_data();
				break;
		}
		echo $status . $type . $data;
		die();
	}
};




