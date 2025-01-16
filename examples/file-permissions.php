<?php
require_once("/home/deep/wsl.deeprahman.lo/wp-load.php");
wp(); // For query
require_once ABSPATH . "wp-content/plugins/wp-securing-setup/wpss-logger.php";
//========================================================================================

require_once ABSPATH. "wp-admin/includes/misc.php";
//
require_once( ABSPATH .  "wp-content/plugins/wp-securing-setup/includes/interface-wpss-file-permission-manager.php");
require_once(ABSPATH . "wp-content/plugins/wp-securing-setup/includes/class-wpss-file-permission-manager.php");
require_once(ABSPATH . "wp-content/plugins/wp-securing-setup/includes/traits/class-wpss-ownership-permission-trait.php");

require_once(ABSPATH . "wp-content/plugins/wp-securing-setup/wpss-misc.php");


$data = [ "./" => 775,"wp-config-sample.php" => 666,"wp-config.php" => '777', "wp-login.php"=> '777', "wp-content"=> '777', "wp-content/uploads"=> '744', "wp-content/plugins"=> '777', "wp-content/themes"=> '777'];

$paths = array_keys($data);
var_dump($paths);

try{

    $pm = new SSWP_File_Permission_Manager($paths);
    $pm->display_results();
    $errors = array_filter($data, function($perms,$path) use($pm){
        $abspath = realpath(ABSPATH . $path);
        $octperms = sswp_convert_to_octal_pers_from_string($perms);
        return $pm->change_file_permission($abspath,$octperms);
    },ARRAY_FILTER_USE_BOTH);

    echo '\n';

    $pm->display_results();


}catch(Exception $e){
    die($e->getMessage());
}

