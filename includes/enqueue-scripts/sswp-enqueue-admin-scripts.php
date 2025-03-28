<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $sswp;
// $admin_page passed from included page
if ( 'tools_page_sswp-files-permission' !== $admin_page ) {
	return;
}


$asset_file = SSWP_ROOT . 'build/main.asset.php';

if ( ! file_exists( $asset_file ) ) {
	new WP_Error( 'Asset File does not exists' );
}
$index_js  = SSWP_URL . 'build/main.js';
$index_css = SSWP_URL . 'build/main.css';

if ( ! file_exists( SSWP_ROOT . 'build/main.js' ) ) {
	new WP_Error( 'JS File does not exists' );
	sswp_logger( 'Info', 'JS File does not exists', __FILE__ );
}

if ( ! file_exists( SSWP_ROOT . 'build/main.css' ) ) {
	new WP_Error( 'CSS File does not exists' );

	sswp_logger( 'Info', 'CSS File does not exists', __FILE__ );
}

$asset = include $asset_file;

wp_enqueue_script(
	$sswp->js_handle,
	$index_js,
	$asset['dependencies'],
	$asset['version'],
	array(
		'in_footer' => true,
	)
);

wp_enqueue_style(
	$sswp->css_handle,
	$index_css,
	array(),
	$asset['version']
);
wp_enqueue_style( 'wp-components' );

// == For jQuery and related


function sswp_enqueue_jquery_scripts() {
	// Enqueue jQuery (already included with WordPress)
	wp_enqueue_script( 'jquery' );

	// Enqueue jQuery UI Core
	wp_enqueue_script( 'jquery-ui-core' );

	// Enqueue jQuery UI Tabs
	wp_enqueue_script( 'jquery-ui-tabs' );

	wp_enqueue_script('jquery-ui-dialog');
}



sswp_enqueue_jquery_scripts();


wp_localize_script(
	$sswp->js_handle,
	'sswpRest',
	array(
		'rest_url'     => esc_url_raw( rest_url() ),
		'nonce'        => wp_create_nonce( $sswp->nonce_action ), // Use 'wp_rest' as the action for REST API
		'current_user' => wp_get_current_user()->data->user_login, // Optional: Pass current user info
	)
);
