<?php
/**
 * Do .htaccess form related stuff
 */
global $sswp;
require_once $sswp->root . DIRECTORY_SEPARATOR . 'includes/class-sswp-server-directives-apache.php';
require_once $sswp->root . DIRECTORY_SEPARATOR . 'includes/class-sswp-server-directives-factory.php';

try {
	$GLOBALS['sswp_sd'] = $sd = SSWP_Server_Directives_Factory::create_server_directives();
} catch ( Exception $ex ) {
}

$GLOBALS['allowed_functions'] = $allowed_functions = array(
	'protect-debug-log' => 'sswp_protect_debug_log',
	'sswp_allowed_files'     => 'sswp_protect_update_directory', // NOTE: make the file name consistent
);

/**
 * Handles the post
 *
 * @param  array $data the htaccess form settings values; example: $data = array(
 *                     array( "name" => "protect-debug-log", "value" => "off" ),
 *                     array( "name" => "protect-update-directory", "value" =>
 *                     "on" ), array( "name" => "protect-xml-rpc", "value" =>
 *                     "on" ), array( "name" => "protect-rest-endpoint", "value"
 *                     => "off" ), array( "name" => "allowed_files", "value" =>
 *                     array( "jpeg", "gif" ) ) );
 * @return array|mixed
 */
function sswp_handle_htaccess_post_req( $data ) {
	$sd                           = $GLOBALS['sswp_sd'];
	$GLOBALS['htaccess_settings'] = $htaccess_from_settings = sswp_save_htaccess_option( $data );
	// Walk through the $data array
	foreach ( $htaccess_from_settings['ht_form'] as $item ) {
		$name  = $item['name'];
		$value = $item['value'];

		// Check if the name exists in the allowed_functions array
		if ( array_key_exists( $name, $GLOBALS['allowed_functions'] ) ) {
			$function_name = $GLOBALS['allowed_functions'][ $name ];

			// Call the appropriate function if it exists
			if ( ! empty( $function_name ) && function_exists( $function_name ) ) {
				$function_name( $value, $sd, $htaccess_from_settings['ht_form'] );
			} else {
				error_log( 'Function: ' . __FUNCTION__ . " Message: Function {$function_name} does not exists" );
				return new WP_Error( __( 'client_error', 'secure-setup' ), __( 'Your custom error message here', 'secure-setup' ), array( 'status' => 400 ) );
			}
		}
	}
	return sswp_from_data_with_message( 'Form Saved' );
}

/**
 * Return HTAccess Form data with message
 *
 * @param  string $message the message
 * @return array        data-structure: [
		'message' => $message,
		'data' => json_encode($ht_form)
	]
 */
function sswp_from_data_with_message( $message ): array {
	global $sswp;
	$ht_form = $sswp->get_ht_form();
	$message = array(
		'message' => $message,
		'data'    => json_encode( $ht_form ),
	);
	return $message;
}

function sswp_handle_htaccess_get_req() {
	return sswp_from_data_with_message( __('Form Data return', 'secure-setup') );
}
function sswp_save_htaccess_option( $new = array() ) {
	global $sswp;
	$cur = get_options( array( $sswp->settings ) );

	$cur['_sswp_settings']['htaccess']['ht_form'] = $new;
	update_option( $sswp->settings, $cur['_sswp_settings'] );
	$new = get_options( array( $sswp->settings ) );
	return $new[ $sswp->settings ]['htaccess'];
}

function sswp_protect_debug_log( $d, ISSWP_Server_Directives $sd ) {
	if ( $d === 'on' ) {
		$sd->unprotect_debug_log();
		$sd->protect_debug_log();
	} else {
		$sd->unprotect_debug_log();
	}
}

function sswp_protect_update_directory( $d, ISSWP_Server_Directives $sd, &$ht_form = array() ) {
	$is_uploads_checked = array_filter(
		$ht_form,
		function ( $v ) {
			$is_checked = ( ( $v['name'] === 'protect-update-directory' ) && ( $v['value'] === 'on' ) );
			return $is_checked;
		}
	);
	$files              = sswp_allowed_files( $d );
	if ( empty( $is_uploads_checked ) || empty( $files ) ) {
		$sd->disallow_file_access();
	} else {
		$sd->disallow_file_access();
		$sd->allow_file_access( $files );
	}
}


function sswp_protect_rest_endpoint( $d, ISSWP_Server_Directives $sd ) {
	// NOTE: function Not in use
	if ( $d !== 'on' ) {
		$sd->unprotect_user_rest_apt();
	} else {
		$sd->unprotect_user_rest_apt();
		$sd->protect_user_rest_apt();
	}
}

/**
 *  filter out the unallowed files types
 *
 * @param  array $d files extensions
 * @return array allowed files
 */
function sswp_allowed_files( $d ): array {
	global $htaccess_from_settings;
	if ( empty( $d ) ) {
		return array();
	}

	// $allowed = $htaccess_from_settings["file_types"];   //todo: to be removed
	$allowed = $GLOBALS['htaccess_settings']['file_types'];
	// The filtered files
	$files = array_filter(
		$d,
		function ( $v ) use ( $allowed ) {
			return ( array_search( $v, $allowed ) !== false );
		}
	);
	return $files;
}
