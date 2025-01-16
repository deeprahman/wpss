<?php

class WPSS_Server_Directives_Apache_Test extends PHPUnit\Framework\TestCase
{
    private $testDir;
    private $directives;
    private $htaccessPath;

    protected function setUp(): void
    {
        if(!isset($_SERVER['SERVER_SOFTWARE'] ) || ($_SERVER['SERVER_SOFTWARE'] != 'Apache')) {
            $_SERVER['SERVER_SOFTWARE'] = 'Apache';
        }

        WP();

        parent::setUp();


        // Create a temporary test directory
        $this->testDir = sys_get_temp_dir() . '/wpss_test_' . uniqid();
        mkdir($this->testDir);

        // Create test .htaccess file
        $this->htaccessPath = $this->testDir . '/.htaccess';
        touch($this->htaccessPath);


        // Initialize the class
        $this->directives = new SSWP_Server_Directives_Apache([
            'apache' => true
        ]);

        // Set necessary WordPress constants if not already set
        if (!defined('ABSPATH')) {
            define('ABSPATH', $this->testDir . '/');
        }
        if (!defined('WP_CONTENT_DIR')) {
            define('WP_CONTENT_DIR', $this->testDir . '/wp-content');
        }

        if(!is_dir(WP_CONTENT_DIR)){
            // Create wp-content directory
            mkdir(WP_CONTENT_DIR, 0777, true);
            mkdir(WP_CONTENT_DIR . '/uploads', 0777, true); 
        }

    }

    protected function tearDown(): void
    {
        // Clean up test files and directories
            $this->recursiveRemoveDirectory($this->testDir);
        parent::tearDown();
    }

    private function recursiveRemoveDirectory($directory)
    {
        if (is_dir($directory)) {
            $files = array_diff(scandir($directory), ['.', '..']);
            foreach ($files as $file) {
                $path = $directory . '/' . $file;
                is_dir($path) ? $this->recursiveRemoveDirectory($path) : unlink($path);
            }
            rmdir($directory);
        }
    }

    public function testAddRule()
    {
        $rules = "Order deny,allow\nDeny from all";
        $result = $this->directives->add_rule($rules, $this->htaccessPath);

        $this->assertTrue($result);
        $this->assertFileExists($this->htaccessPath);

        $content = file_get_contents($this->htaccessPath);
        $this->assertStringContainsString('# BEGIN wpss', $content);
        $this->assertStringContainsString($rules, $content);
        $this->assertStringContainsString('# END wpss', $content);
    }

    public function testRemoveRule()
    {
        // First add a rule
        $rules = "Order deny,allow\nDeny from all";
        $this->directives->add_rule($rules, $this->htaccessPath);

        // Then remove it
        $result = $this->directives->remove_rule($this->htaccessPath);

        $this->assertTrue($result);
        $content = file_get_contents($this->htaccessPath);
        $this->assertStringNotContainsString('# BEGIN wpss', $content);
        $this->assertStringNotContainsString($rules, $content);
        $this->assertStringNotContainsString('# END wpss', $content);
    }

        public function testProtectDebugLog()
        {
            $result = $this->directives->protect_debug_log();
            
            $this->assertTrue($result);
            $htaccessPath = WP_CONTENT_DIR . '/.htaccess';
            $this->assertFileExists($htaccessPath);
            
            $content = file_get_contents($htaccessPath);
            $this->assertStringContainsString('# BEGIN protect-log', $content);
            $this->assertStringContainsString('<Files debug.log>', $content);
            $this->assertStringContainsString('Order allow,deny', $content);
            $this->assertStringContainsString('Deny from all', $content);
            $this->assertStringContainsString('# END protect-log', $content);
        }
    
        public function testUnprotectDebugLog()
        {
            // First protect the debug log
            $this->directives->protect_debug_log();
            
            // Then unprotect it
            $result = $this->directives->unprotect_debug_log();
            
            $this->assertTrue($result);
            $htaccessPath = WP_CONTENT_DIR . '/.htaccess';
            $content = file_get_contents($htaccessPath);
            $this->assertStringNotContainsString('# BEGIN protect-log', $content);
            $this->assertStringNotContainsString('<Files debug.log>', $content);
        }
    
        public function testProtectUserRestApt()
        {
            $result = $this->directives->protect_user_rest_apt('homepage');
            
            $this->assertTrue($result);
            $htaccessPath = ABSPATH . '.htaccess';
            $this->assertFileExists($htaccessPath);
            
            $content = file_get_contents($htaccessPath);
            $this->assertStringContainsString('# BEGIN protect-rest-api', $content);
            $this->assertStringContainsString('RewriteEngine On', $content);
            $this->assertStringContainsString('RewriteRule ^wp-json/wp/v[0-9]+/users.*$ - [R=404,L]', $content);
            $this->assertStringContainsString('RewriteCond %{QUERY_STRING} author=\d', $content);
        }
    
        public function testUnprotectUserRestApt()
        {
            // First protect the REST API
            $this->directives->protect_user_rest_apt('homepage');
            
            // Then unprotect it
            $result = $this->directives->unprotect_user_rest_apt();
            
            $this->assertTrue($result);
            $htaccessPath = ABSPATH . '.htaccess';
            $content = file_get_contents($htaccessPath);
            $this->assertStringNotContainsString('# BEGIN protect-rest-api', $content);
            $this->assertStringNotContainsString('RewriteRule ^wp-json/wp/v[0-9]+/users.*$ - [R=404,L]', $content);
        }
    
        public function testAllowFileAccess()
        {
            $filePattern = ['.jpg', '.png', '.gif'];
            $result = $this->directives->allow_file_access($filePattern);
            
            $this->assertTrue($result);
            $htaccessPath = WP_CONTENT_DIR . '/uploads/.htaccess';
            $this->assertFileExists($htaccessPath);
            
            $content = file_get_contents($htaccessPath);
            $this->assertStringContainsString('# BEGIN protect-uploads', $content);
            $this->assertStringContainsString('<FilesMatch', $content);
            $this->assertStringContainsString('Require all granted', $content);
            $this->assertStringContainsString('Require all denied', $content);
        }
    
        public function testDisallowFileAccess()
        {
            // First allow file access
            $filePattern = ['.jpg', '.png', '.gif'];
            $this->directives->allow_file_access($filePattern);
            
            // Then disallow it
            $result = $this->directives->disallow_file_access();
            
            $this->assertTrue($result);
            $htaccessPath = WP_CONTENT_DIR . '/uploads/.htaccess';
            $content = file_get_contents($htaccessPath);
            $this->assertStringNotContainsString('# BEGIN protect-uploads', $content);
            $this->assertStringNotContainsString('Require all granted', $content);
        }
}
