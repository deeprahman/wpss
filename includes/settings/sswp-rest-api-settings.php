<?php

$sswp_rest_endpoints_to_rate_limit['rate_limit_endpoints'] = array(
	'/wp/v2/users',
);

$sswp_rest_endpoints_to_rate_limit['max_calls']          = 5;
$sswp_rest_endpoints_to_rate_limit['time_window_in_sec'] = 60;

return $sswp_rest_endpoints_to_rate_limit;
