<?php
/**
 * TODO: Make it abstract class
 * TODO: To be other classes for nginx, apache, lightspeed and iis server
 */

abstract class SSWP_Server_Directives {
	protected $is_apache;
	protected $is_nginx;
	protected $is_litespeed;
	protected $is_iis;
	protected $home_path;
	protected $wp_rewrite;
	protected $wp_filesystem;

	public function __construct() {
		global $is_apache, $is_nginx, $is_litespeed, $is_IIS, $is_iis7, $wp_rewrite;
		// Initialize WP_Filesystem
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		$this->is_apache    = $is_apache;
		$this->is_nginx     = $is_nginx;
		$this->is_litespeed = $is_litespeed;
		$this->is_iis       = $is_IIS || $is_iis7;
		$this->home_path    = get_home_path();
		$this->wp_rewrite   = $wp_rewrite;

		WP_Filesystem();
		global $wp_filesystem;
		$this->wp_filesystem = $wp_filesystem;
	}

	/**
	 *
	 * @param string $rules
	 * @param string $htaccess_path
	 * @param string $marker
	 * @return void
	 */
	abstract protected function add_rule( string $rules, string $htaccess_path = '', string $marker = 'sswp' ): bool;
}
