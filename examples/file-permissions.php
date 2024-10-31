<?php
require_once("/home/deep/wsl.deeprahman.lo/wp-load.php");
wp(); // For query
require_once ABSPATH . "wp-content/plugins/wp-securing-setup/wpss-logger.php";
//========================================================================================

require_once ABSPATH. "wp-admin/includes/misc.php";
//
require_once( ABSPATH .  "wp-content/plugins/wp-securing-setup/includes/interface-wpss-file-permission-manager.php");
require_once(ABSPATH . "wp-content/plugins/wp-securing-setup/includes/class-wpss-file-permission-manager.php");
//

$paths = ["wp-config.php", "wp-login.php", "wp-content", "wp-content/uploads", "wp-content/plugins", "wp-content/themes"];
//
$ab = ABSPATH;
$abspath_array = array_map(function($v) {
    return ABSPATH . $v;
}, $paths);

print_r($abspath_array);

try{
$pm = new WPSS_File_Permission_Manager($paths);

//$ret = $pm->apply_recommended_permissions($paths);
xdebug_break();
$pm->set_permission(ABSPATH.'wp-config.php', '0775');

//print_r($ret);
$pm->display_results();
}catch(Exception $e){
    printf($e->getMessage());
}

