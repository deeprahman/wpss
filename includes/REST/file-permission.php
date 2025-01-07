<?php

require_once WP_Securing_Setup::ROOT . 'includes/wpss-file-permission.php';

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'wpss/v1',
			'/file-permissions',
			array(
				'methods'             => array( 'GET', 'PATCH', 'PUT', 'POST', 'DELETE' ),
				'callback'            => 'wpss_file_permissions_callback',
				'permission_callback' => 'wpss_file_permissions_permission_check',
				'args'                => array(
					'nonce' => array(
						'required' => true,
					),
				),
			)
		);
	}
);

function wpss_file_permissions_permission_check( $request ) {
	global $wpss;

	if ( ! current_user_can( 'manage_options' ) ) {
		return false;
	}

	return true;
}

function wpss_file_permissions_callback( $request ) {
	global $wpss;
	$message = '';
	switch ( $request->get_method() ) {
		case 'GET':
			$fs_permission = get_file_permissions();
			break;
		case 'POST':
			$message      .= do_recommended_permission();
			$fs_permission = get_file_permissions();
			break;
		case 'PUT':
			if ( 'revert' == ( $request->get_params() )['action'] ) {
				$message .= is_wp_error( $res = revert_to_original() ) ? $res->get_error_message() : $res;
			} else {
				$message = __( 'Action not found', 'secure-setup' );
				error_log( 'Function: ' . __FUNCTION__ . ' Message: ' . $message );
			}
			$fs_permission = get_file_permissions();
			break;
		case 'PATCH':
			break;
	}

	// Add your file permissions logic here
	$response = array(
		'success' => true,
		'data'    => array(
			'message' => $message,
			'fs_data' => isset( $fs_permission ) ? json_encode( $fs_permission, JSON_NUMERIC_CHECK ) : null,
		),
	);
	return rest_ensure_response( $response );
}
