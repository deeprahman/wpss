<?php

require_once "/home/deep/wsl.deeprahman.lo/wp-load.php";
wp(); // For query
require_once ABSPATH . "wp-content/plugins/wp-securing-setup/wpss-logger.php";
//========================================================================================

require_once ABSPATH. "wp-admin/includes/misc.php";

require_once WP_PLUGIN_DIR  . "/wp-securing-setup/includes/class-wpss-apache-directives-validator.php";

$validator = new WPSS_Apache_Directives_Validator();

/*

$singleDirectives = <<<EOD
RewriteEngine On
SetEnvIfNoCase Request_URI "^/wp-json/wp/v2/users" api_rate_limit_time_window=60
RewriteCond %{ENV:api_rate_limit_last_request} ^$ [OR]
RewriteCond %{TIME_EPOCH} > expr=%{ENV:api_rate_limit_last_request} + %{ENV:api_rate_limit_time_window}
RewriteRule ^ - [E=api_rate_limit_count:0,E=api_rate_limit_last_request:%{TIME_EPOCH},NS]
RewriteRule ^ - [E=api_rate_limit_count:%{ENV:api_rate_limit_count}+1,NS]
RewriteCond %{ENV:api_rate_limit_count} > 10
RewriteRule ^ - [R=429,L]
EOD;


 echo "Validating Single Directives:\n";
if ($validator->is_valid($singleDirectives)) {
    echo "All single directives are valid.\n";
} else {
    echo "Validation failed for single directives:\n";
    echo $validator->get_last_validation_message();
}
 echo "\n\n";

*/

$file_pattern_regex = "\.log";
$upload_dir = wp_upload_dir();


        $rules = '<Directory "' . $upload_dir['path'] . '">' . PHP_EOL;
        $rules  .= '<FilesMatch "' . $file_pattern_regex . '">' . PHP_EOL;
        $rules .= '    Require all granted' . PHP_EOL;
        $rules .= '</FilesMatch>' . PHP_EOL;
        $rules  .= '<FilesMatch ".*">' . PHP_EOL; // Start FilesMatch directive
        $rules .= '    Require all denied' . PHP_EOL; // Add rule to deny all access
        $rules .= '</FilesMatch>' . PHP_EOL; // Close FilesMatch directive
        $rules .=  '</Directory>' . PHP_EOL;
        $rules .= '</Directory>' . PHP_EOL; // Close Directory directive






 echo "Validating Block Directives:\n";
if ($validator->is_valid($rules)) {
    echo "All block directives are valid.\n";
} else {
    echo "Validation block for single directives:\n";
    echo $rules.PHP_EOL;
    echo $validator->get_last_validation_message();
}
 echo "\n\n";
