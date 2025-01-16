<?php 

function sswp_convert_to_octal_pers_from_string(string $perms):string|null
{
        

    // Use regex to check if it conforms to '0xxx' format
    $reg_ex_oct = '/^0([1-7]{3})$/';
    $reg_ex_string = '/^([1-7]{3})$/';
    if(preg_match($reg_ex_string, $perms)) {
        
         $ret = "0" . $perms; 
    } else if (preg_match($reg_ex_oct, $perms)) {
        
        $ret = $perms;        
    }else{
        
        $ret = null;
    }

    return $ret;

}

/**
 * Get the client IP address reliably.
 *
 * @return string Client IP address or '0.0.0.0' if no valid IP found.
 */
function sswp_get_client_ip()
{
    $ip_keys = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];

    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            // Handle cases with multiple IPs (e.g., proxies).
            $ip_list = explode(',', $_SERVER[$key]);
            foreach ($ip_list as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
    }

    return '0.0.0.0'; // Fallback if no valid IP found.
}




    /**
     * Inserts an array of strings into a file (.htaccess), placing it between
     * BEGIN and END markers.
     *
     * Replaces existing marked info. Retains surrounding
     * data. Creates file if none exists.
     *
     * @since 1.5.0
     *
     * @param  string       $filename  Filename to alter.
     * @param  string       $marker    The marker to alter.
     * @param  array|string $insertion The new content to insert.
     * @return bool True on write success, false on failure.
     */
function sswp_insert_with_markers( $filename, $marker, $insertion )
{
    if (! file_exists($filename) ) {
        if (! is_writable(dirname($filename)) ) {
              return false;
        }

        if (! touch($filename) ) {
            return false;
        }

        // Make sure the file is created with a minimum set of permissions.
        $perms = fileperms($filename);

        if ($perms ) {
            chmod($filename, $perms | 0644);
        }
    } elseif (! is_writable($filename) ) {
        return false;
    }

    if (! is_array($insertion) ) {
        $insertion = explode("\n", $insertion);
    }

    $switched_locale = switch_to_locale(get_locale());

    $instructions = sprintf(
    /* translators: 1: Marker. */
        __(
            'The directives (lines) between "BEGIN %1$s" and "END %1$s" are
dynamically generated, and should only be modified via WordPress filters.
Any changes to the directives between these markers will be overwritten.'
        ),
        $marker
    );

    $instructions = explode("\n", $instructions);

    foreach ( $instructions as $line => $text ) {
        $instructions[ $line ] = '# ' . $text;
    }

    /**
     * Filters the inline instructions inserted before the dynamically generated content.
     *
     * @since 5.3.0
     *
     * @param string[] $instructions Array of lines with inline instructions.
     * @param string   $marker       The marker being inserted.
     */
    $instructions = apply_filters('insert_with_markers_inline_instructions', $instructions, $marker);

    if ($switched_locale ) {
        restore_previous_locale();
    }

    $insertion = array_merge($instructions, $insertion);

    $start_marker = "# BEGIN {$marker}";
    $end_marker   = "# END {$marker}";

    $fp = fopen($filename, 'r+');

    if (! $fp ) {
        return false;
    }

    // Attempt to get a lock. If the filesystem supports locking, this will block until the lock is acquired.
    flock($fp, LOCK_EX);

    $lines = array();

    while ( ! feof($fp) ) {
        $lines[] = rtrim(fgets($fp), "\r\n");
    }

    // Split out the existing file into the preceding lines, and those that appear after the marker.
    $pre_lines        = array();
    $post_lines       = array();
    $existing_lines   = array();
    $found_marker     = false;
    $found_end_marker = false;

    foreach ( $lines as $line ) {
        if (! $found_marker && str_contains($line, $start_marker) ) {
            $found_marker = true;
            continue;
        } elseif (! $found_end_marker && str_contains($line, $end_marker) ) {
            $found_end_marker = true;
            continue;
        }

        if (! $found_marker ) {
            $pre_lines[] = $line;
        } elseif ($found_marker && $found_end_marker ) {
            $post_lines[] = $line;
        } else {
            $existing_lines[] = $line;
        }
    }

    // Check to see if there was a change.
    if ($existing_lines === $insertion ) {
        flock($fp, LOCK_UN);
        fclose($fp);

        return true;
    }

    // Generate the new file data.
    $new_file_data = implode(
        "\n",
        array_merge(
            $pre_lines,
            array( $start_marker ),
            $insertion,
            array( $end_marker ),
            $post_lines
        )
    );

    // Write to the start of the file, and truncate it to that length.
    fseek($fp, 0);
    $bytes = fwrite($fp, $new_file_data);

    if ($bytes ) {
        ftruncate($fp, ftell($fp));
    }

    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return (bool) $bytes;
}



    /**
     * Extracts strings from between the BEGIN and END markers in the .htaccess file.
     *
     * @since 1.5.0
     *
     * @param  string $filename Filename to extract the strings from.
     * @param  string $marker   The marker to extract the strings from.
     * @return string[] An array of strings from a file (.htaccess) from between BEGIN and END markers.
     */
function sswp_extract_from_markers( $filename, $marker )
{
    $result = array();

    if (! file_exists($filename) ) {
        return $result;
    }

    $markerdata = explode("\n", implode('', file($filename)));

    $state = false;

    foreach ( $markerdata as $markerline ) {
        if (str_contains($markerline, '# END ' . $marker) ) {
            $state = false;
        }

        if ($state ) {
            if (str_starts_with($markerline, '#') ) {
                continue;
            }

            $result[] = $markerline;
        }

        if (str_contains($markerline, '# BEGIN ' . $marker) ) {
            $state = true;
        }
    }

    return $result;
}




