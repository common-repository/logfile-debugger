=== Logfile Debugger ===
Contributors: strackerphil
Donate link:  https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=5A98CHL224QG6
Tags:         admin, administration, debug, debugging, log
Requires at least: 3.0.1
Tested up to: 3.8.1
Stable tag:   trunk
License:      GPLv2 or later
License URI:  http://www.gnu.org/licenses/gpl-2.0.html

Small tool for developers to make debugging more easy (and fun)!

== Description ==

*This plugin is intended to be used by WordPress developers, during development.*

Logfile Debugger basically provides you with 3 new methods:

* `wp_debug(var1, var2, ...)`
* `wp_debug_trace()`
* `wp_describe(var1, var2, ...)`

The first two methods will add a new entry to a log file, the last method will dump all specified variables on the screen.

Now the cool part: The plugin also provides a new admin section to view the log file (check the screenshot)!
This viewer can even be opened in a stand-alone popup/separate tab.

**Attention**:

*For security reasons this plugin should only be used on a development environment! Every user that is logged in to WordPress can potentially read the log-data. It is your responsibility as a developer to take make sure that no sensitive data is made available to other WordPress users via the logfile.*

**Recommended usage**:

You should always test if the debug function exists before calling it.
This prevents unexpected errors in cases where you forget to remove the wp_debug() or wp_describe() calls form the code while testing your plugin/theme on installations that do not have the logfile-debugger plugin installed.

Example:

`function_exists( 'wp_debug' ) && wp_debug( 'Current WP object: ', $wp );`

== Installation ==

1. Upload `logfile-debugger.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. You will get a new 'View log file' menu-item in the 'Tools' menu

== Frequently Asked Questions ==

= Who is this plugin for? =

This plugin is intended as a tool for developers. It should be installed and used only on development environments.

= Why only on development environments? =

The plugin itself has no considerable impact on performance.

However, for *security reasons* this plugin should only be used on a development environment! Every user that is logged in to WordPress can potentially read the log-data. It is your responsibility as a developer to take make sure that no sensitive data is made available to other WordPress users via the logfile.

== Screenshots ==

1. That's just a simple code-example for using the debug methods

2. This is the log file viewer in the admin section

3. The log file viewer opened as popup! Only the logfile is displayed, no WordPress header or menu

4. **New since v1.1**: Choose the format of variable dump!

== Changelog ==

= 1.3 =
* Small improvement in the logfile-output (some linebreaks were missing)

= 1.2 =
* Add new dump-option: JSON-Encode
* Fix display of log-data that contains HTML code (code is now always escaped)

= 1.1 =
* Add new option to choose the format of the variable dump

= 1.0 =
* Initial version released

== Upgrade Notice ==

**Attention**:

*When the plugin is updated to a new version the current logfile will be lost!*