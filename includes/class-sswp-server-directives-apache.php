<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
require_once Sswp_Securing_Setup::ROOT . DIRECTORY_SEPARATOR . 'includes/class-sswp-server-directives.php';
require_once Sswp_Securing_Setup::ROOT . DIRECTORY_SEPARATOR . 'includes/interface-sswp-server-directives.php';


class Sswp_Server_Directives_Apache extends Sswp_Server_Directives implements ISswp_Server_Directives {


	public function __construct( $cli_args = array() ) {
		parent::__construct();

		if ( ! empty( $cli_args ) ) {
			$this->is_apache = isset( $cli_args['apache'] ) ? $cli_args['apache'] : true;
		}
	}

	public function add_rule( $rules, $htaccess_path = '', $marker = 'sswp' ): bool {

		if ( $this->is_apache || $this->is_litespeed ) {
			return $this->add_apache_rule( $rules, $htaccess_path, $marker );
		}
		return false;
	}

	public function remove_rule( $htaccess_path = '', string $marker = 'sswp' ) {

		if ( $this->is_apache || $this->is_litespeed ) {
			return $this->remove_apache_rule( $htaccess_path, $marker );
		}
		return false;
	}

	private function add_apache_rule( $rules, $htaccess_path = '', string $marker = 'sswp' ) {

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
		if ( ! function_exists( 'extract_from_markers' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}
		$current_rules = extract_from_markers( $htaccess_file, $marker );

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
		if ( ! function_exists( 'extract_from_markers' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}
		// Insert the new rules with the marker
		return insert_with_markers( $htaccess_file, $marker, $new_rules );
	}

	/**
	 * Summary of remove_apache_rule
	 *
	 * @param  mixed  $htaccess_path
	 * @param  string $marker
	 * @return bool
	 */
	private function remove_apache_rule( $htaccess_path = '', string $marker = 'sswp' ) {
		$htaccess_file = $htaccess_path ?: $this->home_path . '.htaccess';

		if ( ! $this->wp_filesystem->exists( $htaccess_file ) ) {
			return false;
		}

		if ( ! $this->wp_filesystem->is_writable( $htaccess_file ) ) {
			return false;
		}
		if ( ! function_exists( 'extract_from_markers' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}
		// Extract the current rules for the given marker
		$extracted_rules = extract_from_markers( $htaccess_file, $marker );

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

	public function sswp_protect_debug_log() {
		$htaccess_path = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . '.htaccess';

		$rules  = "<Files debug.log>\n";
		$rules .= "    Order allow,deny\n";
		$rules .= "    Deny from all\n";
		$rules .= "</Files>\n";

		return $this->add_rule( $rules, $htaccess_path, 'protect-log' );
	}


	public function unprotect_debug_log() {
		$htaccess_path = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . '.htaccess';
		return $this->remove_rule( $htaccess_path, 'protect-log' );
	}

	public function protect_user_rest_apt( $page = 'home' ) {
		// NOTE: Configuration not suitable for all setups
		$htaccess_path = ABSPATH . '.htaccess';

		$rules  = "# Custom Rate Limiting for /wp-json/wp/v2/users\n";
		$rules .= "<IfModule mod_ratelimit.c>\n";
		$rules .= "    <Location /wp-json/wp/v2/users>\n";
		$rules .= "        SetOutputFilter RATE_LIMIT\n";
		$rules .= "        SetEnv rate-limit 10\n";
		$rules .= "    </Location>\n";
		$rules .= "</IfModule>\n";

		return $this->add_rule( $rules, $htaccess_path, 'protect-rest-api' );
	}


	public function unprotect_user_rest_apt() {
		$htaccess_path = ABSPATH . '/.htaccess';

		return $this->remove_rule( $htaccess_path, 'protect-rest-api' );
	}

	public function allow_file_access( $file_pattern ) {
		$upload_dir         = wp_upload_dir();
		$base_dir           = $upload_dir['basedir'];
		$file_pattern_regex = $this->file_ext_regex_creator( $file_pattern );
		// $htaccess_path = WP_CONTENT_DIR . '/uploads/.htaccess';
		$htaccess_path = $base_dir . '/.htaccess';

		$rules  = "<FilesMatch \"{$file_pattern_regex}\">\n";
		$rules .= "    Require all granted\n";
		$rules .= "</FilesMatch>\n";

		$res = $this->add_rule( $rules, $htaccess_path, 'protect-uploads' );

		if ( $res ) {
			$rules  = "<FilesMatch \".*\">\n";
			$rules .= "    Require all denied\n";
			$rules .= "</FilesMatch>\n";

			$res = $this->add_rule( $rules, $htaccess_path, 'protect-uploads' );

			if ( ! $res ) {
				$this->remove_apache_rule( $htaccess_path, 'protect-uploads' );
			}
		}

		return $res;
	}

	public function disallow_file_access() {
		$upload_dir = wp_upload_dir();
		$base_dir   = $upload_dir['basedir'];

		// $htaccess_path = WP_CONTENT_DIR . '/uploads/.htaccess';
		$htaccess_path = $base_dir . '/.htaccess';

		return $this->remove_rule( $htaccess_path, 'protect-uploads' );
	}

	/**
	 * create file-extension regex pattern for extension array
	 *
	 * @param  array $file_ext Contains file extension
	 * @return mixed regex string of file extension
	 */
	protected function file_ext_regex_creator( array $file_ext ): mixed {
		global $sswp;
		$file_path = $sswp->root . 'includes/class-sswp-file-regex-pattern-creator.php';
		if ( ! file_exists( $file_path ) ) {
			return new WP_Error( 'File Not Exists: ' . $file_path );
		}
		include_once $file_path;
		// BUG: Null
		$extension_map = $sswp->get_extension_map();

		$regex_pat = new Sswp_File_Regex_Pattern_Creator( $file_ext, $extension_map );
		// call generateApacheExtensionRegex method
		$regex = $regex_pat->generateApacheExtensionRegex();
		return $regex;
	}
	protected function validate_htaccess_syntax( string $rules ): mixed {
		global $sswp;
		$file_path = $sswp->root . 'includes/class-sswp-apache-directives-validator.php';
		if ( ! file_exists( $file_path ) ) {
			return new WP_Error( 'File Not Exists: ' . $file_path );
		}
		include_once $file_path;
		$validator = new Sswp_Apache_Directives_Validator();
		return $validator->is_valid( $rules );
	}
}
