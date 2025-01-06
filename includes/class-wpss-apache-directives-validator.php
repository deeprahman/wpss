<?php

class WPSS_Apache_Directives_Validator
{

    // Existing directives array remains unchanged
    private $directives = array(
        'ServerName',
        'DocumentRoot',
        'DirectoryIndex',
        'AllowOverride',
        'Require',
        'Options',
        'ErrorLog',
        'CustomLog',
        'Listen',
        'VirtualHost',
        'RewriteEngine',
        'RewriteRule',
        'SSLEngine',
        'SSLProtocol',
        'SSLCertificateFile',
        'SSLCertificateKeyFile',
        'Order',
        'Allow',
        'Deny',
        'RewriteCond',
        'SetEnvIfNoCase'
    );

    private $blockDirectives = array(
        'Files',
        'FilesMatch',
        'Directory',
        'DirectoryMatch',
        'Location',
        'LocationMatch',
        'VirtualHost'
    );

    // Property to store the last validation message
     private $lastValidationMessage = '';

    /**
     * Main validation method.
     * Determines if the input is a block or single directive and validates accordingly.
     *
     * @param  string $input The directive string to validate.
     * @return string Validation result message.
     */
    public function validate($input)
    {
        $input = trim(preg_replace('/\r\n|\r|\n/', "\n", $input));
        
        // Enhanced regex to handle nested blocks
        if (preg_match('/^\s*<(\w+)\s+(.*?)>\s*(.*?)\s*(<\/\1>)+\s*$/s', $input, $matches)) {
            $blockName = $matches[1];
            $blockArg = $matches[2];
            $innerContent = $matches[3];
            
            // Count closing tags to match with opening tags
            $closingTags = substr_count($matches[4], '</');
            if ($closingTags > 1) {
                return "Invalid block structure: Extra closing tags found.";
            }
            
            $result = $this->validateBlockDirective($blockName, $blockArg, $innerContent);
        } else {
            $result = $this->validateMultipleDirectives($input);
        }

        $this->lastValidationMessage = $result;
        return $result;
    }

    /**
     * Public method to check if the given directive is valid.
     *
     * @param  string $input The directive string to validate.
     * @return bool True if valid, False otherwise.
     */
    public function is_valid($input)
    {
        $validationResult = $this->validate($input);
        if (preg_match('/\bInvalid\b/i', $validationResult)) {
            return false;
        }
        return true;
    }

    /**
     * Get the last validation message.
     *
     * @return string The last validation message.
     */
    public function get_last_validation_message()
    {
        return $this->lastValidationMessage;
    }

    /**
     * Validate multiple single-line directives.
     *
     * @param  string $input The multi-line directive string.
     * @return string Concatenated validation messages.
     */
    private function validateMultipleDirectives( $input )
    {
        $lines    = explode("\n", $input);
        $messages = array();

        foreach ( $lines as $lineNumber => $line ) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0 ) {
                continue; // Skip empty lines and comments
            }

            // Validate single directive
            $result     = $this->validateDirectiveSyntax($line);
            $messages[] = 'Line ' . ( $lineNumber + 1 ) . ': ' . $result;
        }

        return implode("\n", $messages);
    }

    /**
     * Validate a single directive syntax.
     *
     * @param  string $directive The directive line to validate.
     * @return string Validation result message.
     */
    public function validateDirectiveSyntax( $directive )
    {
        // Regex to capture directive name and value
        if (preg_match('/^\s*([A-Za-z]+)\s+(.*)$/', $directive, $matches) ) {
            $directiveName  = $matches[1];
            $directiveValue = trim($matches[2]);

            if (in_array($directiveName, $this->directives) ) {
                // Validate the directive value based on its type
                return $this->validateDirectiveValue($directiveName, $directiveValue);
            } else {
                return "Unknown directive: '$directiveName'.";
            }
        } else {
            return "Invalid syntax. Expected format: 'DirectiveName DirectiveValue'.";
        }
    }

    /**
     * Validate the value of a specific directive.
     *
     * @param  string $name  The name of the directive.
     * @param  string $value The value of the directive.
     * @return string Validation result message.
     */
    private function validateDirectiveValue( $name, $value )
    {
        switch ( strtolower($name) ) {
        case 'servername':
            return $this->validateDomainName($value);
        case 'documentroot':
            return $this->validatePath($value);
        case 'require':
            return $this->validateRequireDirective($value);
        case 'listen':
            return $this->validatePort($value);
        case 'rewriterule':
            return $this->validateRewriteRule($value);
        case 'order':
            return $this->validateOrderDirective($value);
        case 'allow':
            return $this->validateAllowDirective($value);
        case 'deny':
            return $this->validateDenyDirective($value);
            // Add more directive cases as needed
        default:
            return "Valid syntax for directive '$name'.";
        }
    }

    /**
     * Validate block directives like <Files> or <FilesMatch>.
     *
     * @param  string $blockName    The name of the block directive.
     * @param  string $blockArg     The argument of the block directive.
     * @param  string $innerContent The inner content of the block.
     * @return string Validation result message.
     */
    private function validateBlockDirective($blockName, $blockArg, $innerContent)
    {
        if (!in_array($blockName, $this->blockDirectives)) {
            return "Unknown block directive: <$blockName>.";
        }

        // Validate block argument based on block type
        $argValidation = $this->validateBlockArgument($blockName, $blockArg);
        if ($argValidation !== true) {
            return $argValidation;
        }

        // Check for nested blocks in the inner content
        if (preg_match_all('/<(\w+)\s+(.*?)>/', $innerContent, $nestedMatches)) {
            foreach ($nestedMatches[1] as $index => $nestedBlockName) {
                $nestedArg = $nestedMatches[2][$index];
                
                // Extract the content between nested tags
                $pattern = '/<' . preg_quote($nestedBlockName) . '\s+' . preg_quote($nestedArg, '/') . '>(.*?)<\/' . preg_quote($nestedBlockName) . '>/s';
                if (preg_match($pattern, $innerContent, $contentMatch)) {
                    $nestedContent = $contentMatch[1];
                    $nestedValidation = $this->validateBlockDirective($nestedBlockName, $nestedArg, $nestedContent);
                    if (strpos($nestedValidation, 'Invalid') === 0) {
                        return $nestedValidation;
                    }
                }
            }
        }

        // Validate remaining directives in the inner content
        $lines = explode("\n", $innerContent);
        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }

            // Skip nested block lines
            if (strpos($line, '<') === 0) {
                continue;
            }

            // Validate inner directive
            $result = $this->validateDirectiveSyntax($line);
            if (strpos($result, 'Valid') !== 0) {
                return "Invalid directive inside <$blockName> at inner line " . ($lineNumber + 1) . ": $result";
            }
        }

        return "Valid block directive: <$blockName>.";
    }


    private function validateBlockArgument($blockName, $blockArg)
    {
        switch ($blockName) {
        case 'Directory':
            return $this->validateDirectoryPath($blockArg) ? true : 
                    "Invalid Directory path: '$blockArg'.";
            
        case 'FilesMatch':
            return $this->validateFilesMatchPattern($blockArg) ? true :
                    "Invalid FilesMatch pattern: '$blockArg'.";
            
        case 'Files':
            return $this->validateFilename($blockArg) ? true :
                    "Invalid Files pattern: '$blockArg'.";
            
        default:
            return true;
        }
    }


    private function validateDirectoryPath($path)
    {
        // Remove quotes if present
        $path = trim($path, '"\'');
        
        // Check if the path starts with a forward slash or drive letter (Windows)
        return preg_match('~^(/|[a-zA-Z]:[\\/])~', $path) === 1;
    }


    private function validateFilesMatchPattern($pattern)
    {
        // Remove quotes if present
        $pattern = trim($pattern, '"\'');
        
        // Validate common FilesMatch patterns
        $validPatterns = [
            '/^\\\\?\\.\\w+$/', // File extension (e.g., \.php)
            '/^\\.\\*$/',       // All files (.*)
            '/^[\\w\\-.*?]+$/'  // Simple wildcards and literal names
        ];

        foreach ($validPatterns as $validPattern) {
            if (preg_match($validPattern, $pattern)) {
                return true;
            }
        }

        // For complex patterns, try to compile them
        return @preg_match('/' . str_replace('/', '\\/', $pattern) . '/', '') !== false;
    }

    /**
     * Validate a filename pattern for the <Files> directive.
     *
     * @param  string $filename The filename pattern to validate.
     * @return bool True if valid, false otherwise.
     */
    private function validateFilename( $filename )
    {
        // Allow wildcard characters *, ?, etc.
        return preg_match('/^[\w.\-*?]+$/', $filename) === 1;
    }

    /**
     * Validate a regular expression pattern.
     *
     * @param  string $pattern The regex pattern to validate.
     * @return bool True if valid, false otherwise.
     */
    private function validateRegex( $pattern )
    {
        // Attempt to compile the regex
        return @preg_match('/' . $pattern . '/', '') !== false;
    }

    /**
     * Validate a domain name for the ServerName directive.
     *
     * @param  string $domain The domain name to validate.
     * @return string Validation result message.
     */
    private function validateDomainName( $domain )
    {
        if (filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) ) {
            return "Valid ServerName: '$domain'.";
        } else {
            return "Invalid ServerName: '$domain'.";
        }
    }

    /**
     * Validate a filesystem path for the DocumentRoot directive.
     *
     * @param  string $path The path to validate.
     * @return string Validation result message.
     */
    private function validatePath( $path )
    {
        // Check if the path exists or follows a valid pattern
        if (is_dir($path) || preg_match('/^\/[a-zA-Z0-9_\/.\-]+$/', $path) ) {
            return "Valid DocumentRoot: '$path'.";
        } else {
            return "Invalid DocumentRoot: '$path'.";
        }
    }

    /**
     * Validate the Require directive.
     *
     * @param  string $value The value of the Require directive.
     * @return string Validation result message.
     */
    private function validateRequireDirective( $value )
    {
        $allowedValues = array( 'all granted', 'all denied', 'valid-user', 'user', 'group', 'expr' );
        foreach ( $allowedValues as $allowed ) {
            if (stripos($value, $allowed) !== false ) {
                return 'Valid Require directive.';
            }
        }
        return "Invalid Require directive value: '$value'.";
    }

    /**
     * Validate port numbers for the Listen directive.
     *
     * @param  string $value The port number to validate.
     * @return string Validation result message.
     */
    private function validatePort( $value )
    {
        if (is_numeric($value) && $value > 0 && $value <= 65535 ) {
            return "Valid Listen port: '$value'.";
        } else {
            return "Invalid Listen port: '$value'.";
        }
    }

    /**
     * Validate the RewriteRule directive with enhanced pattern support.
     *
     * @param  string $value The value of the RewriteRule directive.
     * @return string Validation result message.
     */
    private function validateRewriteRule($value)
    {
        // Trim any extra whitespace
        $value = trim($value);
    
        // Pattern for environment variable settings and flags
        $flagsPattern = '\\[(?:[A-Za-z,=0-9]+|E=[a-zA-Z_]+:[^,\\]]+|E=[a-zA-Z_]+:%\\{[A-Z_]+\\}(?:\\+[0-9]+)?|R=[0-9]+|NS|L)+\\]';
    
        // Pattern for basic path components
        $pathPattern = '(?:[\\^_0-9a-zA-Z-]+/?)?';
    
        // Combined patterns for different types of RewriteRules
        $patterns = [
        // Simple dash with flags: ^ - [flags]
        '#^\\^\\s+-\\s+' . $flagsPattern . '$#',
        
        // Basic path with optional subdirectory: ^([_0-9a-zA-Z-]+/)?(pattern) target [flags]
        '#^\\^?' . $pathPattern . '\\((.*?)\\)\\s+\\$[0-9]+\\s+(?:' . $flagsPattern . ')?$#',
        
        // Direct path to file: ^pattern$ target [flags]
        '#^\\^?' . $pathPattern . '.*?\\s+(?:[-\\w.\\/]+|\\$[0-9]+)\\s+(?:' . $flagsPattern . ')?$#',
        
        // Simple redirect: . target [flags]
        '#^\\.\\s+[-\\w.\\/]+\\s+(?:' . $flagsPattern . ')?$#',
        
        // Original basic pattern
        '#^([^\\s]+)\\s+([^\\s]+)(?:\\s+\\[([A-Za-z,=0-9]+)\\])?$#'
        ];
    
        // Test against all patterns
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                // Additional validation for environment variables
                if (strpos($value, 'E=') !== false) {
                    // Validate environment variable syntax
                    if (!preg_match('#E=[a-zA-Z_]+:(?:%\\{[A-Z_]+\\}(?:\\+[0-9]+)?|[^,\\]]+)#', $value)) {
                        return 'Invalid environment variable syntax in RewriteRule.';
                    }
                }
            
                // Additional validation for status codes
                if (strpos($value, 'R=') !== false) {
                    if (!preg_match('#R=(?:301|302|303|404|410|429|500|503)#', $value)) {
                        return 'Invalid redirect status code in RewriteRule.';
                    }
                }
            
                return 'Valid RewriteRule.';
            }
        }
    
        return 'Invalid RewriteRule syntax.';
    }


    /**
     * Validate the Order directive.
     *
     * @param  string $value The value of the Order directive.
     * @return string Validation result message.
     */
    private function validateOrderDirective( $value )
    {
        $allowedOrders = array( 'allow,deny', 'deny,allow' );
        if (in_array(strtolower($value), $allowedOrders) ) {
            return 'Valid Order directive.';
        } else {
            return "Invalid Order directive value: '$value'.";
        }
    }

    /**
     * Validate the Allow directive.
     *
     * @param  string $value The value of the Allow directive.
     * @return string Validation result message.
     */
    private function validateAllowDirective( $value )
    {
        // Example formats: 'from all', 'from 192.168.1.0/24'
        if (preg_match('/^from\s+(.+)$/i', $value, $matches) ) {
            $fromValue = trim($matches[1]);
            if (strcasecmp($fromValue, 'all') === 0 
                || filter_var($fromValue, FILTER_VALIDATE_IP) 
                || preg_match('/^\d{1,3}(\.\d{1,3}){3}\/\d+$/', $fromValue)
            ) {
                return 'Valid Allow directive.';
            }
        }
        return "Invalid Allow directive value: '$value'.";
    }

    /**
     * Validate the Deny directive.
     *
     * @param  string $value The value of the Deny directive.
     * @return string Validation result message.
     */
    private function validateDenyDirective( $value )
    {
        // Example formats: 'from all', 'from 192.168.1.0/24'
        if (preg_match('/^from\s+(.+)$/i', $value, $matches) ) {
            $fromValue = trim($matches[1]);
            if (strcasecmp($fromValue, 'all') === 0 
                || filter_var($fromValue, FILTER_VALIDATE_IP) 
                || preg_match('/^\d{1,3}(\.\d{1,3}){3}\/\d+$/', $fromValue)
            ) {
                return 'Valid Deny directive.';
            }
        }
        return "Invalid Deny directive value: '$value'.";
    }
}



/**
 * Example Usage
 *
if ($validator->is_valid($input)) {
    // Directive is valid
} else {
    // Directive is invalid
    echo $validator->get_last_validation_message();
}
 */

// Include the ApacheDirectiveValidator class (ensure the file path is correct)
// require_once 'ApacheDirectiveValidator.php';

// Example 1: Single Directives
// $singleDirectives = <<<EOD
// RewriteEngine On
//
// # Block access to the users endpoint for any version of the API
// RewriteRule ^wp-json/wp/v[0-9]+/users.*$ - [R=404,L]
//
// # Redirect query strings with author to the provided page
// RewriteCond %{QUERY_STRING} author=\d
// RewriteRule (.*) {$page} [L,R=301,QSD]
// EOD;
//
// echo "Validating Single Directives:\n";
// if ($validator->is_valid($singleDirectives)) {
// echo "All single directives are valid.\n";
// } else {
// echo "Validation failed for single directives:\n";
// echo $validator->get_last_validation_message();
// }
// echo "\n\n";

// Example 2: <Files> Block Directive
// $filesBlock = <<<EOD
// <FilesMatch "\.(jpg|png)$">
// Require all granted
// </FilesMatch>
// EOD;
// xdebug_break();
// echo "Validating <Files> Block Directive:\n";
// if ($validator->is_valid($filesBlock)) {
// echo "The <Files> block directive is valid.\n";
// } else {
// echo "Validation failed for <Files> block directive:\n";
// echo $validator->get_last_validation_message();
// }
// echo "\n\n";

// // Example 3: <FilesMatch> Block Directive with Invalid Directive
// $invalidFilesMatchBlock = <<<EOD
// <FilesMatch "\.php$">
// Allow from all
// InvalidDirective somevalue
// </FilesMatch>
// EOD;

// echo "Validating <FilesMatch> Block Directive with an Invalid Directive:\n";
// if ($validator->is_valid($invalidFilesMatchBlock)) {
// echo "The <FilesMatch> block directive is valid.\n";
// } else {
// echo "Validation failed for <FilesMatch> block directive:\n";
// echo $validator->get_last_validation_message();
// }
// echo "\n\n";

// // Example 4: <FilesMatch> Block Directive with Valid Directives
// $validFilesMatchBlock = <<<EOD
// <FilesMatch "\.php$">
// Allow from all
// </FilesMatch>
// EOD;

// echo "Validating <FilesMatch> Block Directive with Valid Directives:\n";
// if ($validator->is_valid($validFilesMatchBlock)) {
// echo "The <FilesMatch> block directive is valid.\n";
// } else {
// echo "Validation failed for <FilesMatch> block directive:\n";
// echo $validator->get_last_validation_message();
// }
// echo "\n";

// Example 4: Single directives validator

// $singleDirectives= <<<EOD
// RewriteEngine On
// RewriteRule ^index\.php$ - [L]
// EOD;
// echo "Validating Single Directives:\n";
// if ($validator->is_valid($singleDirectives)) {
// echo "All single directives are valid.\n";
// } else {
// echo "Validation failed for single directives:\n";
// echo $validator->get_last_validation_message();
// }
// echo "\n\n";





/***************************
 * Response Above **********************
Validating Single Directives:
All single directives are valid.

Validating <Files> Block Directive:
The <Files> block directive is valid.

Validating <FilesMatch> Block Directive with an Invalid Directive:
Validation failed for <FilesMatch> block directive:
Invalid directive inside <FilesMatch> at inner line 2: Unknown directive: 'InvalidDirective'.

Validating <FilesMatch> Block Directive with Valid Directives:
The <FilesMatch> block directive is valid.

===================================
*/
