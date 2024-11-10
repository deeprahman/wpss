<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package Wp_Securing_Setup
 */

//function load_files_in_a_directory($directory)
//{
//    // Load all PHP files from the directory recursively
//    $iterator = new RecursiveIteratorIterator(
//        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
//    );
//
//    foreach ($iterator as $file) {
//        if ($file->isFile() && $file->getExtension() === 'php') {
//            require_once $file->getPathname();
//        }
//    }
//}
//
//load_files_in_a_directory("/home/deep/Websites/woocommerce.lo/wp/");
//load_files_in_a_directory("/home/deep/Websites/woocommerce.lo/wp/wp-includes/");
//load_files_in_a_directory("/home/deep/Websites/woocommerce.lo/wp/wp-admin/");

const ROOT = __DIR__;

const WP_ROOT =   ROOT . "/../../.." ;

require_once(WP_ROOT . "/wp-load.php");
require_once(WP_ROOT . "/wp-admin/includes/misc.php");
wp(); // For query


require_once ROOT . "/vendor/autoload.php";




/**
 * WordPress-style autoloader for PHPUnit.
 *
 * @param string $class_name The name of the class to be loaded.
 * @return void
 */
function wpss_autoloader($class_name)
{
    
    $class_file = 'class-' . strtolower(str_replace('_', '-', $class_name)) . '.php';
    $directories = [
        ABSPATH . 'wp-content/plugins/wp-securing-setup/includes/',
        ABSPATH . 'wp-content/plugins/wp-securing-setup/tests/'
    ];
    // TOD debug

    foreach ($directories as $directory) {
        $file_path = $directory . $class_file;
        if (file_exists($file_path)) {
            require_once $file_path;
            return;
        }
    }
}

spl_autoload_register('wpss_autoloader');








