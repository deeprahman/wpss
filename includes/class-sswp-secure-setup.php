<?php

class SSWP_Secure_Setup {


	public const ROOT = SSWP_ROOT;

	public const DOMAIN = 'secure-setup';

	public const URL = SSWP_URL;

	public const VERSION = '0.1.0';

	public $domain;

	public $root;

	public $version;

	public $root_url;

	public $name;

	public $js_handle;

	public $css_handle;

	public $nonce_action;

	public $settings;
	/**
	 * File-paths: for file permissions to be checked
	 *
	 * @var array
	 */
	public $file_paths = array();

	/**
	 * Recommended  permsiions for files
	 *
	 * @var array
	 */
	public $rcmnd_perms = array();

	/**
	 * @var SSWP_File_Permission_Manager
	 */
	public $fpm;

	public function __construct() {
		$this->name         = __( 'Secure Setup', 'secure-setup' );
		$this->root         = self::ROOT;
		$this->domain       = self::DOMAIN;
		$this->root_url     = SSWP_URL;
		$this->js_handle    = 'sswp-primary-js';
		$this->css_handle   = 'sswp-primary-css';
		$this->nonce_action = 'sswp-rest';
		$this->settings     = SSWP_SETTINGS;
				$this->init();
	}

	public function init() {
			// $this->file_paths = ["wp-config.php", "wp-login.php", "wp-content", "wp-content/uploads", "wp-content/plugins", "wp-content/themes"];
				add_action(
					'plugin_loaded',
					function () {

						$this->file_paths  = $this->get_file_paths();
						$this->rcmnd_perms = $this->get_rcmnd_perms();
						$this->set_fpm();
					}
				);
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_js' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_css' ) );
				$this->admin_pages();
				$this->xml_rpc_config();
				$this->admin_rest();
				$this->rest_endpoints_config();
	}

	public function enqueue_admin_js( $admin_page ) {
			global $sswp;
			wp_enqueue_script( 'wp-api-request' );
			include_once $this->root . '/includes/enqueue-scripts/wpss-enqueue-admin-scripts.php';
	}

	public function enqueue_admin_css() {
	}

	public function admin_pages() {
			global $sswp;
			include_once $this->root . '/admin/wpss-files-permissions-tools-page.php';
	}

	public function admin_rest() {
			include_once $this->root . '/includes/REST/file-permission.php';
			include_once $this->root . '/includes/REST/htaccess-protect.php';
	}

	public function xml_rpc_config() {
			include_once $this->root . DIRECTORY_SEPARATOR . 'includes/wpss-xml-rpc.php';
	}

	public function rest_endpoints_config() {
			include_once $this->root . DIRECTORY_SEPARATOR . 'includes/wpss-rate-limit-rest.php';
	}

	/**
	 * Set file permission manager
	 *
	 * @return $this
	 */
	public function set_fpm() {
			include_once $this->root . 'includes/class-wpss-file-permission-manager.php';
			$this->fpm = new SSWP_File_Permission_Manager( $this->file_paths, $this->rcmnd_perms );
			return $this;
	}
	public function get_fpm(): SSWP_File_Permission_Manager {
			return empty( $this->fpm ) ? $this->set_fpm()->fpm : $this->fpm;
	}

	public function get_extension_map() {
			$opt = get_option( $this->settings );
			return isset( ( $opt )['htaccess']['extension_map'] ) ? ( $opt )['htaccess']['extension_map'] : null;
	}

	public function get_ht_form() {

			$opt = get_option( $this->settings );
			return isset( ( $opt )['htaccess']['ht_form'] ) ? ( $opt )['htaccess']['ht_form'] : null;
	}

	public function get_file_types() {
			$opt = get_option( $this->settings );
			return isset( ( $opt ) ['htaccess']['file_types'] ) ? ( $opt ) ['htaccess']['file_types'] : null;
	}

	public function get_original_permission() {
			$opt = get_option( $this->settings );
			return isset( ( $opt ) ['file_permission']['chk_results'] ) ? ( ( $opt ) ['file_permission']['chk_results'] ) : null;
	}

	public function get_file_paths() {

			$opt = ! empty( get_option( $this->settings ) ) ? get_option( $this->settings ) : null;
		return isset( ( $opt )['file_permission']['paths'] ) ? ( $opt )['file_permission']['paths'] : null;
	}

	public function get_rcmnd_perms() {
			$opt = ! empty( get_option( $this->settings ) ) ? get_option( $this->settings ) : null;
		return ( ( $opt )['file_permission']['rcmnd_perms'] ) ? ( $opt )['file_permission']['rcmnd_perms'] : null;
	}

	public function get_rest_endpoints_for_limiting() {
			$opt = ! empty( get_option( $this->settings ) ) ? get_option( $this->settings ) : null;
		return ( ( $opt )['rest_api']['rate_limit_endpoints'] ) ? ( $opt )['rest_api']['rate_limit_endpoints'] : null;
	}


	public function get_max_call_for_limiting() {
			$opt = ! empty( get_option( $this->settings ) ) ? get_option( $this->settings ) : null;
		return ( ( $opt )['rest_api']['max_calls'] ) ? ( $opt )['rest_api']['max_calls'] : null;
	}

	public function get_time_window_for_limiting() {
			$opt = ! empty( get_option( $this->settings ) ) ? get_option( $this->settings ) : null;
		return ( ( $opt )['rest_api']['time_window_in_sec'] ) ? ( $opt )['rest_api']['time_window_in_sec'] : null;
	}
}
