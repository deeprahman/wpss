<?php

require_once WP_Securing_Setup::ROOT . DIRECTORY_SEPARATOR . 'includes/class-wpss-server-directives.php';
require_once WP_Securing_Setup::ROOT . DIRECTORY_SEPARATOR . 'includes/interface-wpss-server-directives.php';
// require_once ABSPATH . 'wp-admin/includes/misc.php';  // TODO: To be removed

class WPSS_Server_Directives_Apache extends WPSS_Server_Directives implements IWPSS_Server_Directives {



	public function __construct( $cli_args = array() ) {
		parent::__construct();

		if ( ! empty( $cli_args ) ) {
			$this->is_apache = isset( $cli_args['apache'] ) ? $cli_args['apache'] : true;
		}
	}

	public function add_rule( $rules, $htaccess_path = '', $marker = 'wpss' ): bool {

		if ( $this->is_apache || $this->is_litespeed ) {
			return $this->add_apache_rule( $rules, $htaccess_path, $marker );
		}
		return false;
	}

	public function remove_rule( $htaccess_path = '', string $marker = 'wpss' ) {

		if ( $this->is_apache || $this->is_litespeed ) {
			return $this->remove_apache_rule( $htaccess_path, $marker );
		}
		return false;
	}

	private function add_apache_rule( $rules, $htaccess_path = '', string $marker = 'wpss' ) {

		if ( ! $this->validate_htaccess_syntax( $rules ) ) {
			return false;
		}

		$htaccess_file = $htaccess_path ?: $this->home_path . '.htaccess';

		if ( ! $this->wp_filesystem->exists( $htaccess_file ) ) {
			if ( ! $this->wp_filesystem->is_writable( dirname( $htaccess_file ) ) ) {
				return false;
			}
			$this->wp_filesystem->touch( $htaccess_file );
		} elseif ( ! $this->wp_filesystem->is_writable( $htaccess_file ) ) {
			return false;
		}

		$current_rules = sswp_extract_from_markers( $htaccess_file, $marker );

		// Read the contents of the htaccess file
		$htaccess_content = $this->wp_filesystem->get_contents( $htaccess_file );
		if ( $htaccess_content === false ) {
			return false;
		}

		// Split the file into lines
		$lines = explode( "\n", $htaccess_content );

		// Remove trailing blank lines
		while ( ! empty( $lines ) && trim( end( $lines ) ) === '' ) {
			array_pop( $lines ); // Remove empty lines from the end
		}

		// Rebuild the htaccess content without the trailing blank lines
		$cleaned_htaccess_content = implode( "\n", $lines );

		// Write the cleaned content back to the file
		if ( ! $this->wp_filesystem->put_contents( $htaccess_file, $cleaned_htaccess_content ) ) {
			return false;
		}

		// Now, merge the current rules with the new rules
		$new_rules = array_merge( $current_rules, explode( "\n", $rules ) );

		// Insert the new rules with the marker
		return sswp_insert_with_markers( $htaccess_file, $marker, $new_rules );
	}

	/**
	 * Summary of remove_apache_rule
	 *
	 * @param  mixed  $htaccess_path
	 * @param  string $marker
	 * @return bool
	 */
	private function remove_apache_rule( $htaccess_path = '', string $marker = 'wpss' ) {
		$htaccess_file = $htaccess_path ?: $this->home_path . '.htaccess';

		if ( ! $this->wp_filesystem->exists( $htaccess_file ) ) {
			return false;
		}

		if ( ! $this->wp_filesystem->is_writable( $htaccess_file ) ) {
			return false;
		}

		// Extract the current rules for the given marker
		$extracted_rules = sswp_extract_from_markers( $htaccess_file, $marker );

		if ( empty( $extracted_rules ) ) {
			return true; // Nothing to remove, so we consider it a success
		}

		// Get the current content of the .htaccess file
		$current_content = $this->wp_filesystem->get_contents( $htaccess_file );
		if ( $current_content === false ) {
			return false;
		}

		// Remove the extracted rules from the file content
		$start_marker = "# BEGIN {$marker}";
		$end_marker   = "# END {$marker}";
		$pattern      = "/{$start_marker}.*?{$end_marker}\s*/s";
		$new_content  = preg_replace( $pattern, '', $current_content );

		if ( $new_content === null ) {
			return false;
		}

		// Write the updated content back to the file
		$result = $this->wp_filesystem->put_contents( $htaccess_file, $new_content );

		if ( $result === false ) {
			return false;
		}

		return true;
	}

	public function protect_debug_log() {
		$htaccess_path = ABSPATH . DIRECTORY_SEPARATOR . '.htaccess';

		$rules  = '    <FilesMatch "\.log$">' . PHP_EOL;
		$rules .= '        Require all denied' . PHP_EOL;
		$rules .= '    </FilesMatch>' . PHP_EOL;

		return $this->add_rule( $rules, $htaccess_path, 'protect-log' );
	}

	public function unprotect_debug_log() {

		$htaccess_path = ABSPATH . DIRECTORY_SEPARATOR . '.htaccess';

		return $this->remove_rule( $htaccess_path, 'protect-log' );
	}

	public function protect_user_rest_apt( $page = 'home' ) {
		// NOTE: Configuration not suitable for all setup
		$htaccess_path = ABSPATH . '.htaccess';

		$rules  = '# Custom Rate Limiting for /wp-json/wp/v2/users' . PHP_EOL;
		$rules .= '<IfModule mod_ratelimit.c>' . PHP_EOL;
		$rules .= '    <Location /wp-json/wp/v2/users>' . PHP_EOL;
		$rules .= '        SetOutputFilter RATE_LIMIT' . PHP_EOL;
		$rules .= '        SetEnv rate-limit 10' . PHP_EOL;
		$rules .= '    </Location>' . PHP_EOL;
		$rules .= '</IfModule>' . PHP_EOL;
		return $this->add_rule( $rules, $htaccess_path, 'protect-rest-api' );
	}

	public function unprotect_user_rest_apt() {
		$htaccess_path = ABSPATH . '/.htaccess';

		return $this->remove_rule( $htaccess_path, 'protect-rest-api' );
	}

	public function allow_file_access( $file_pattern ) {
		$uploads            = wp_upload_dir();
		$htaccess_path      = $uploads['basedir'] . DIRECTORY_SEPARATOR . '.htaccess';
		$file_pattern_regex = $this->file_ext_regex_creator( $file_pattern );

		$rules  = '<FilesMatch "' . $file_pattern_regex . '">' . PHP_EOL;
		$rules .= '    Require all granted' . PHP_EOL;
		$rules .= '</FilesMatch>' . PHP_EOL;
		$rules .= '<FilesMatch ".*">' . PHP_EOL; // Start FilesMatch directive
		$rules .= '    Require all denied' . PHP_EOL; // Add rule to deny all access
		$rules .= '</FilesMatch>' . PHP_EOL; // Close FilesMatch directive

		return $this->add_rule( $rules, $htaccess_path, 'protect-uploads' );
	}

	public function disallow_file_access() {

		$uploads       = wp_upload_dir();
		$htaccess_path = $uploads['basedir'] . DIRECTORY_SEPARATOR . '.htaccess';
		return $this->remove_rule( $htaccess_path, 'protect-uploads' );
	}

	/**
	 * create file-extension regex pattern for extension array
	 *
	 * @param  array $file_ext Contains file extension
	 * @return mixed regex string of file extension
	 */
	protected function file_ext_regex_creator( array $file_ext ): mixed {
		global $wpss;
		$file_path = $wpss->root . 'includes/class-wpss-file-regex-pattern-creator.php';
		if ( ! file_exists( $file_path ) ) {
			return new WP_Error( 'File Not Exists: ' . $file_path );
		}
		include_once $file_path;
		// BUG: Null
		$extension_map = $wpss->get_extension_map();

		$regex_pat = new WPSS_File_Regex_Pattern_Creator( $file_ext, $extension_map );
		// call generateApacheExtensionRegex method
		$regex = $regex_pat->generateApacheExtensionRegex();
		return $regex;
	}
	protected function validate_htaccess_syntax( string $rules ): mixed {
		global $wpss;
		$file_path = $wpss->root . 'includes/class-wpss-apache-directives-validator.php';
		if ( ! file_exists( $file_path ) ) {
			return new WP_Error( 'File Not Exists: ' . $file_path );
		}
		include_once $file_path;
		$validator = new WPSS_Apache_Directives_Validator();
		return $validator->is_valid( $rules );
	}
}
