<?php


if (! function_exists('wpss_logger') ) {

    function sswp_logger( $type, $log, $function )
    {

        $formatted_log = $type . ': [' . date('Y-m-d H:i:s') . '] ' . ' Function: ' . $function . ' ';
        if (is_array($log) || is_object($log) ) {
            $formatted_log .= print_r($log, true);
        } else {
            $formatted_log .= $log;
        }

        $formatted_log .= PHP_EOL;

        error_log($formatted_log);
    }
}
