<?php
/**
 * This file provides the debugging functions
 *
 * - wp_debug( variable1, variable2, ... )
 * - wp_describe()
 * - wp_debug_trace()
 *
 * Recommendation: Test with function_exists()!
 *
 *   Test if the debug funciton exists before using it.
 *   This way there will be no error when you forget to remove the
 *   debug-statement and test your code on another installation.
 *
 *   Example:
 *     function_exists( 'wp_debug' ) && wp_debug( $the_var );
 */


if ( !function_exists('wp_debug') ) :
/**
 * Writes some debug data to the debug-logfile.
 * Can receive multiple arguments, e.g. wp_debug('User', $user_id, $user_obj);
 *
 * @since  1.0.0
 * @param  <mixed/multiple> Can take one or multiple parameters.
 *           Each parameter will be written to the logfile.
 */
function wp_debug() {
	DebugLogFunctions::debug( func_get_args() );
}
endif;


if ( !function_exists('wp_describe') ) :
/**
 * Dumps all parameters to the screen.
 * Can receive multiple arguments, e.g. wp_describe('User', $user_id, $user_obj);
 *
 * @since  1.0.0
 * @param  <mixed/multiple> The content of the variable is dumped as debug output.
 */
function wp_describe() {
	DebugLogFunctions::describe( func_get_args() );
}
endif;


if ( !function_exists('wp_debug_trace') ) :
/**
 * Writes a stack-trace to the logfile.
 *
 * @since  1.0.0
 */
function wp_debug_trace() {
	DebugLogFunctions::debug_trace();
}
endif;


class DebugLogFunctions {
	/**
	 * Flag to track, if the request-init header was written to logfile
	 *
	 * @since  1.0.0
	 * @var    bool
	 */
	static $init_done = false;

	/**
	 * Stores the plugin configuration
	 *
	 * @since  1.0.0
	 * @var    array
	 */
	static $options = null;

	/**
	 * The full path to current logfile
	 *
	 * @since  1.0.0
	 * @var    string
	 */
	static $logfile = null;


	/**
	 * Adds a new paragraph to the logfile to indicate a new request.
	 *
	 * @since  1.0.0
	 */
	static public function init_request() {
		if ( self::$init_done === true ) {
			return;
		}

		switch ( DebugLogFunctions::get_opt( 'dumptype', 0 ) ) {
			case 2:  $type = '--- print_r() --'; break;
			case 1:  $type = ' var_export() --'; break;
			default: $type = '-- var_dump() --'; break;
		}

		self::$init_done = true;
		self::append( "\n\n---------------------------------------------------------------$type" );
		self::append( "-------------------- NEW REQUEST ----------------------------------------------" );
	}


	/**
	 * Turn the debugger on/off.
	 * When turned off, no data is written to logfile and wp_describe is ignored.
	 *
	 * @since  1.0.0
	 * @param  bool $status True .. turn debugger on, False .. turn debugger off.
	 */
	static public function set_status( $status ) {
		self::set_opt( 'status', $status );
	}


	/**
	 * Returns the current debugger status (enabled/disabled).
	 *
	 * @since  1.0.0
	 * @return bool True .. debugger on, False .. debugger off.
	 */
	static public function get_status() {
		return self::get_opt( 'status', false );
	}


	/**
	 * Writes some debug data to the debug-logfile.
	 *
	 * @since  1.0.0
	 * @param mixed $data An array with multiple variables/statements that will
	 *           be appended to the logfile.
	 */
	static public function debug( $data ) {
		if( ! self::get_status() ) {
			return; // Debugger turned off...
		}

		ob_start();
		echo date("> Y-m-d\tH:i:s\t");

		$title = array_shift( $data );
		if ( is_string( $title ) ) {
			echo htmlspecialchars( $title ) . "\n";
		} else {
			array_unshift( $data, $title );
		}

		$data_count = count( $data );
		for ( $i = 0; $i < $data_count; $i += 1 ) {
			$line_num = $i+1;
			echo "== VAR $line_num ==\n";
			switch ( DebugLogFunctions::get_opt( 'dumptype', 0 ) ) {
				case 3: // 3 .. json_encode()
					// Can be used for export
					echo json_encode( $data[$i] ) . "\n";
					break;

				case 2: // 2 .. print_r()
					// Readable, but not possible to distinguish NULL/bool/Empty string
					echo print_r ( $data[$i], true ) . "\n";
					break;

				case 1: // 1 .. var_export()
					// PHP Code, but no functions
					echo var_export ( $data[$i], true ) . "\n";
					break;

				default: // 0 .. var_dump()
					// not so readable but complete
					var_dump( $data[$i] );
					break;
			}
		}
		$output = ob_get_clean();
		$output = trim( str_replace(
			array("\n", "\n\t\t==", "\n\t\t\n\t=="),
			array("\n\t\t", "\n\t==", "\n\t=="),
			$output
		) );

		self::append( $output );
	}


	/**
	 * Writes a stack-trace to the logfile.
	 *
	 * @since  1.0.0
	 */
	static public function debug_trace() {
		if ( ! self::get_status() ) {
			return; // Debugger turned off...
		}

		ob_start();
		echo "> Trace:\n";
		debug_print_backtrace();
		$output = ob_get_clean();
		$output = trim( str_replace( "\n", "\n\t", $output ) );

		self::append($output);
	}


	/**
	 * Directly appends the given data to the logfile.
	 *
	 * @since  1.0.0
	 * @param  string $output The text that will be appended to the logfile.
	 */
	static public function append( $output ) {
		if ( ! self::get_status() ) {
			return; // Debugger turned off...
		}
		if ( self::$init_done !== true ) {
			self::init_request();
		}

		$debug_logfile = self::get_logfile_name();
		file_put_contents( $debug_logfile, $output . "\n", FILE_APPEND );
	}


	/**
	 * Empties the debug-logfile.
	 *
	 * @since  1.0.0
	 */
	static public function flush() {
		$debug_logfile = self::get_logfile_name();
		// Delete the current logfile
		unlink( $debug_logfile );

		// On next log-action we create a new logfile
		self::$logfile = null;
		self::set_opt( 'secure_filename', false );
	}


	/**
	 * Returns the filename/path to the debug logfile.
	 *
	 * @since  1.0.0
	 * @return string The full path to the debug logfile.
	 */
	static public function get_logfile_name() {
		if ( self::$logfile === null ) {
			$secure_filename = self::get_opt( 'secure_filename', false );
			if ( empty( $secure_filename ) ) {
				$secure_filename = md5( get_bloginfo('title') . time() );
				self::set_opt( 'secure_filename', $secure_filename );
			}
			$file = $secure_filename;
			$file .= '.log';
			$log_dir = dirname( __FILE__ ) . '/logs';

			if ( !file_exists( $log_dir ) ) {
				mkdir( $log_dir );
			}

			self::$logfile = $log_dir . '/' . $file;

			if ( !file_exists(self::$logfile) ) {
				touch(self::$logfile);
			}
		}

		return self::$logfile;
	}


	/**
	 * Returns the contents of the logfile.
	 *
	 * @since  1.0.0
	 * @return string The current contents of the logfile.
	 */
	static public function get_logfile_data() {
		$debug_logfile = self::get_logfile_name();
		return trim( file_get_contents( $debug_logfile ) );
	}


	/**
	 * Dumps contents of all parameters on the screen.
	 *
	 * @since  1.0.0
	 * @param  array $data An array of variables that will be dumped as debug output.
	 */
	static public function describe( $data ) {
		if ( ! self::get_status() ) {
			return; // Debugger turned off...
		}

		echo '<pre style="color:#A00">';

		$title = array_shift( $data );
		if ( is_string( $title ) ){
			echo '<strong>' . htmlspecialchars( $title ) . '</strong><br />';
		} else {
			array_unshift( $data, $title );
		}

		$data_count = count( $data );
		for ( $i = 0; $i < $data_count; $i += 1 ) {
			$line_num = $i+1;
			echo "<strong><em>var $line_num</em></strong>: ";
			switch ( DebugLogFunctions::get_opt( 'dumptype', 0 ) ) {
				case 3: // 3 .. json_encode()
					// Can be used for export
					echo htmlspecialchars( json_encode( $data[$i] ) ) . '<br />';
					break;

				case 2: // 2 .. print_r()
					// Readable, but not possible to distinguish NULL/bool/Empty string
					echo htmlspecialchars( print_r ( $data[$i], true ) ) . '<br />';
					break;

				case 1: // 1 .. var_export()
					// PHP Code, but no functions
					echo htmlspecialchars( var_export ( $data[$i], true ) ) . '<br />';
					break;

				default: // 0 .. var_dump()
					// not so readable but complete
					var_dump( $data[$i] );
					break;
			}
		}
		echo '<hr />';
		echo '</pre>';
	}


	/**
	 * Return the current value of a plugin option.
	 *
	 * @since  1.0.0
	 * @param  string $key The option to get
	 * @param  mixed $def_value (optional) the default value, when option is not defined
	 * @return mixed The option value.
	 */
	static public function get_opt( $key, $def_value='' ) {
		if ( ! is_array( self::$options ) ) {
			self::$options = get_option( 'logfile_debugger', array() );

			if ( ! is_array( self::$options ) ) {
				self::$options = array();
			}
		}

		if ( isset( self::$options[$key] ) ) {
			return self::$options[$key];
		} else {
			return $def_value;
		}
	}


	/**
	 * Change a plugin option to a new value.
	 *
	 * @since  1.0.0
	 * @param  string $key The option to get.
	 * @param  mixed $value The new value of the option.
	 */
	static public function set_opt( $key, $value ) {
		if ( ! is_array( self::$options ) ) {
			self::get_opt( $key );
		}

		self::$options[$key] = $value;
		update_option( 'logfile_debugger', self::$options );
	}
};


