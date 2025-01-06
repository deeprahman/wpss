<?php

$file_permission = include WPSS_ROOT . '/includes/settings/wpss-file-permission-settings.php';
$htaccess_form   = include WPSS_ROOT . '/includes/settings/wpss-htaccess-settings.php';
$rest_api = include WPSS_ROOT . "/includes/settings/wpss-rest-api-settings.php";
$defaults        = array();

$defaults['file_permission'] = $file_permission;
$defaults['htaccess']        = $htaccess_form;
$defaults['rest_api']        = $rest_api;

// Register the setting with the default value

if ( false === get_option( WPSS_SETTINGS ) ) {
    add_option( WPSS_SETTINGS, $defaults );
}
// Register the setting
register_setting('wpss_options_group',
 WPSS_SETTINGS,
 array(
    'sanitize_callback' => 'sswp_validate_security_settings',
    'default' => $defaults
)
);


function sswp_validate_security_settings($input) {
    // Initialize errors array
    $errors = [];
    $valid_input = [];
    
    // 1. Validate file_permission exists and structure
    if (!isset($input['file_permission'])) {
        $errors[] = 'file_permission key is missing';
    } else {
        $valid_input['file_permission'] = $input['file_permission'];
        
        // Validate required sub-arrays
        if (!isset($input['file_permission']['rcmnd_perms']) || !is_array($input['file_permission']['rcmnd_perms'])) {
            $errors[] = 'file_permission.rcmnd_perms must be an array';
        }
        if (!isset($input['file_permission']['paths']) || !is_array($input['file_permission']['paths'])) {
            $errors[] = 'file_permission.paths must be an array';
        }
        if (!isset($input['file_permission']['chk_results']) || !is_array($input['file_permission']['chk_results'])) {
            $errors[] = 'file_permission.chk_results must be an array';
        }
    }
    
    // 2. Validate htaccess exists and structure
    if (!isset($input['htaccess'])) {
        $errors[] = 'htaccess key is missing';
    } else {
        $valid_input['htaccess'] = $input['htaccess'];
        
        // Validate ht_form structure
        if (!isset($input['htaccess']['ht_form']) || !is_array($input['htaccess']['ht_form'])) {
            $errors[] = 'htaccess.ht_form must be an array';
        } else {
            foreach ($input['htaccess']['ht_form'] as $form_item) {
                if (!isset($form_item['name']) || !isset($form_item['value'])) {
                    $errors[] = 'Each htaccess.ht_form item must have name and value keys';
                }
            }
        }
        
        // Validate file_types exists
        if (!isset($input['htaccess']['file_types']) || !is_array($input['htaccess']['file_types'])) {
            $errors[] = 'htaccess.file_types must be an array';
        }
        
        // Validate extension_map exists
        if (!isset($input['htaccess']['extension_map']) || !is_array($input['htaccess']['extension_map'])) {
            $errors[] = 'htaccess.extension_map must be an array';
        }
    }
    
    // 3. Validate rest_api exists and structure
    if (!isset($input['rest_api'])) {
        $errors[] = 'rest_api key is missing';
    } else {
        $valid_input['rest_api'] = $input['rest_api'];
        
        // 4. Validate max_calls exists and is numeric
        if (!isset($input['rest_api']['max_calls'])) {
            $errors[] = 'rest_api.max_calls is missing';
        } elseif (!is_numeric($input['rest_api']['max_calls'])) {
            $errors[] = 'rest_api.max_calls must be numeric';
        }
        
        // 5. Validate time_window_in_sec exists and is numeric
        if (!isset($input['rest_api']['time_window_in_sec'])) {
            $errors[] = 'rest_api.time_window_in_sec is missing';
        } elseif (!is_numeric($input['rest_api']['time_window_in_sec'])) {
            $errors[] = 'rest_api.time_window_in_sec must be numeric';
        }
        
        // Validate rate_limit_endpoints exists
        if (!isset($input['rest_api']['rate_limit_endpoints']) || !is_array($input['rest_api']['rate_limit_endpoints'])) {
            $errors[] = 'rest_api.rate_limit_endpoints must be an array';
        }
    }
    
    // If there are validation errors, add them to WordPress settings errors
    if (!empty($errors)) {
        foreach ($errors as $error) {
            add_settings_error(
                'security_settings',
                'security_settings_error',
                $error,
                'error'
            );
        }
        // Return the previous valid settings if validation fails
        return get_option('security_settings');
    }
    
    return $valid_input;
}