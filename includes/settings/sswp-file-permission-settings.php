<?php

// $wpss_file_permission = array
// (
// 'wp-config.php' =>
// array(
// 'exists' => true,
// 'permission' => '777',
// 'writable' => true,
// 'recommended' => '0444',
// ),
// 'wp-login.php' =>
// array(
// 'exists' => true,
// 'permission' => '777',
// 'writable' => true,
// 'recommended' => '0644',
// ),
// 'wp-content' =>
// array(
// 'exists' => true,
// 'permission' => '777',
// 'writable' => true,
// 'recommended' => '0755',
// ),
// 'wp-content/uploads' =>
// array(
// 'exists' => true,
// 'permission' => '777',
// 'writable' => true,
// 'recommended' => '0755',
// ),
// 'wp-content/plugins' =>
// array(
// 'exists' => true,
// 'permission' => '777',
// 'writable' => true,
// 'recommended' => '0755',
// ),
// 'wp-content/themes' =>
// array(
// 'exists' => true,
// 'permission' => '777',
// 'writable' => true,
// 'recommended' => '0755',
// ),
// );

require_once sswp_ROOT . 'includes/class-sswp-file-permission-manager.php';
// TODO: Get file permissions
try {
	$ret['rcmnd_perms'] = array(
		'directory'     => '0755',
		'file'          => '0644',
		'wp-config.php' => '0400',
	);

	$ret['paths']       = array( './', 'wp-config.php', 'wp-content', 'wp-content/uploads', 'wp-content/plugins', 'wp-content/themes' );
	$ret['chk_results'] = ( new SSWP_File_Permission_Manager( $ret['paths'] ) )->check_permissions();
	return $ret;
} catch ( \Exception $ex ) {
	error_log( $ex->getMessage() );
}
