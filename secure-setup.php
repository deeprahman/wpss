<?php

/**
 * Plugin Name: Secure Setup
 * Plugin URI: https://deeprahman.com/wp-secure-setup
 * Description: This plugin helps secure your WordPress website by implementing various security measures.
 * Version: 1.0.0
 * Author: Deep
 * Author URI: https://deeprahman.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: secure-setup
 * Domain Path: /languages
 */

if (! defined('ABSPATH')) {
    exit;
}


// Set Plugin Root
define('SSWP_ROOT', plugin_dir_path(__FILE__));


// Set Plugin URL
define('SSWP_URL', plugin_dir_url(__FILE__));

// Set Domain
define('SSWP_DOMAIN', 'secure-setup');

define('SSWP_VERSION', '0.1.0');

define('SSWP_SETTINGS', '_sswp_settings');

require_once SSWP_ROOT . '/sswp-logger.php';
require_once SSWP_ROOT . '/sswp-misc.php';

$is_litespeed = strpos($_SERVER['SERVER_SOFTWARE'], 'LiteSpeed') !== false;

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'wpss_activate');
register_deactivation_hook(__FILE__, 'wpss_deactivate');

// Function to handle plugin activation
function sswp_activate()
{
    global $is_apache, $is_litespeed, $is_nginx, $is_IIS;
    // Add your activation logic here
    // For example, create options, update database tables, etc.
    include_once SSWP_ROOT . '/includes/settings/sswp-default-settings.php';

    $server_requirement = $is_litespeed || $is_apache;

    if (! $server_requirement) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('This plugin requires Apache 2.4 or Lightspeed server, . Please contact your hosting provider.', 'Plugin Activation Error', array( 'back_link' => true ));
    }
}

// Function to handle plugin deactivation
function sswp_deactivate()
{
    // Add your deactivation logic here
    // For example, delete options, remove database tables, etc.
    delete_option(SSWP_SETTINGS);
}

// Include the plugin class
require_once plugin_dir_path(__FILE__) . 'includes/class-sswp-secure-setup.php';


try {
    $GLOBAL['sswp'] = $sswp = new SSWP_Secure_Setup();
} catch (\Exception $ex) {
    error_log('SSWP-ERROR: ' . $ex->getMessage());
    return new WP_Error(
        'wpss_error',
        __('An avoidable incident han ocurred..', 'secure-setup'),
    );
}
