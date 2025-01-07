<?php

global $wpss;
// $admin_page passed from included page
if ( 'tools_page_wpss-files-permission' !== $admin_page ) {
	return;
}


$asset_file = WPSS_ROOT . 'build/index.asset.php';

if ( ! file_exists( $asset_file ) ) {
	new WP_Error( 'Asset File does not exists' );
}
$index_js  = WPSS_URL . 'build/index.js';
$index_css = WPSS_URL . 'build/index.css';

if ( ! file_exists( $index_js ) ) {
	new WP_Error( 'JS File does not exists' );
	wpss_logger( 'Info', 'JS File does not exists', __FILE__ );
}

if ( ! file_exists( $index_css ) ) {
	new WP_Error( 'CSS File does not exists' );

	wpss_logger( 'Info', 'CSS File does not exists', __FILE__ );
}

$asset = include $asset_file;

wp_enqueue_script(
	$wpss->js_handle,
	$index_js,
	$asset['dependencies'],
	$asset['version'],
	array(
		'in_footer' => true,
	)
);

wp_enqueue_style(
	$wpss->css_handle,
	$index_css,
	array(),
	$asset['version']
);
wp_enqueue_style( 'wp-components' );

// == For jQuery and related


function enqueue_jquery_scripts() {
	// Enqueue jQuery (already included with WordPress)
	wp_enqueue_script( 'jquery' );

	// Enqueue jQuery UI Core
	wp_enqueue_script( 'jquery-ui-core' );

	// Enqueue jQuery UI Tabs
	wp_enqueue_script( 'jquery-ui-tabs' );
}



enqueue_jquery_scripts();


wp_localize_script(
	$wpss->js_handle,
	'WpssRest',
	array(
		'rest_url'     => esc_url_raw( rest_url() ),
		'nonce'        => wp_create_nonce( $wpss->nonce_action ), // Use 'wp_rest' as the action for REST API
		'current_user' => wp_get_current_user()->data->user_login, // Optional: Pass current user info
	)
);
