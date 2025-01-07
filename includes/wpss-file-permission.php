<?php
function get_file_permissions() {
	global $wpss;

	return $wpss->get_fpm()->check_permissions();
}

function do_recommended_permission(): string {

	global $wpss;
	$errors = $wpss->get_fpm()->change_to_recommended_permissions( $wpss->file_paths );

	$message = '';

	if ( ! empty( $errors ) ) {
		$e_files = implode( ',', $errors );
		$message = __( 'Could not change permissoin for the given files: ', 'secure-setup' ) . $e_files;

	}

	return $message;
}

function revert_to_original() {
	global $wpss;

	include_once $wpss->root . 'wpss-misc.php';
	// Get the initial permission
	$initial_perms = $wpss->get_original_permission();

	$errors = array_filter(
		$initial_perms,
		function ( $status, $path ) use ( $wpss ) {

			$abspath = ABSPATH . $path;

			if ( null === ( $sanitized_perms = wpss_convert_to_octal_pers_from_string( $status['permission'] ) ) ) {
				return true;
			}

			return is_wp_error( $wpss->get_fpm()->change_file_permission( $abspath, $sanitized_perms ) );
		},
		ARRAY_FILTER_USE_BOTH
	);

	if ( ! empty( $errors ) ) {
		return new WP_Error(
			'failed_permission_change',
			'Permission could not the changed',
			$errors
		);
	}
	return __( 'Successfully reverted permission', 'secure-setup' );
}
