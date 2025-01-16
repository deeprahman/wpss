<?php
/**
 * Limit REST API rate for specified endpoints.
 *
 * @param  mixed           $result  Current result passed through the filter.
 * @param  WP_REST_Server  $server  REST server instance.
 * @param  WP_REST_Request $request Current REST request.
 * @return bool true if limit exceeded.
 */
function sswp_limit_rest_api_rate( $result, $server, $request ) {
	// Get the endpoints to apply rate limiting
	$endpoints = isset( $GLOBALS['sswp'] ) && method_exists( $GLOBALS['sswp'], 'get_rest_endpoints_for_limiting' )
		? $GLOBALS['sswp']->get_rest_endpoints_for_limiting()
		: array( '/wp/v2/users' );
	$route     = $request->get_route();
	// Target only the desired endpoints
	if ( ! in_array( $route, $endpoints, true ) ) {
		return false;
	}

	// Get the client IP address
	$client_ip = sswp_get_client_ip(); // Assume sswp_get_client_ip() is defined elsewhere
	$cache_key = 'rest_api_rate_limit_' . md5( $client_ip . $request->get_route() );

	// Get the current call count
	$call_data = get_transient( $cache_key );

	// Set rate limit parameters
	$max_calls   = $GLOBALS['sswp']->get_max_call_for_limiting(); // Maximum API calls allowed
	$time_window = $GLOBALS['sswp']->get_time_window_for_limiting(); // Time window in seconds

	if ( $call_data ) {
		// Check if the client exceeded the limit
		if ( $call_data['count'] >= $max_calls ) {
			sswp_logger( 'INFO', 'API call limit exceeded for IP : ' . $client_ip, __FUNCTION__ );
			return true;
		} else {
			// Increment the call count
			++$call_data['count'];
			set_transient( $cache_key, $call_data, $time_window );
		}
	} else {
		// Initialize the call count
		$call_data = array( 'count' => 1 );
		set_transient( $cache_key, $call_data, $time_window );
	}

	return false;
}


function sswp_handle_rate_limiting( $result, $server, $request ) {
	global $sswp;
	$ht_form_settings = ( get_options( array( $sswp->settings ) ) )['_sswp_settings']['htaccess']['ht_form'];
	$output           = false;
		array_walk(
			$ht_form_settings,
			function ( $v ) use ( $result, $server, $request, &$output ) {
				if ( $v['name'] !== 'protect-rest-endpoint' ) {
					return;
				}
				if ( $v['value'] == 'on' ) {
					$output = sswp_limit_rest_api_rate( $result, $server, $request );
				}
			}
		);
	if ( $output === true ) {
		return new WP_Error(
			'rate_limit_exceeded',
			'You have exceeded the rate limit. Please try again later.',
			array( 'status' => 429 )
		);

	}

	return $result;
}

// Hook into the REST API pre-dispatch filter
add_filter( 'rest_pre_dispatch', 'sswp_handle_rate_limiting', 10, 3 );
