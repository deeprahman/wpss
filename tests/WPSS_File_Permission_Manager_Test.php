<?php

use PHPUnit\Framework\TestCase;

class WPSS_File_Permission_Manager_Test extends TestCase {
    private $test_dir;
    private $manager;
    private $test_files;

    public function setUp(): void {
        WP();
        parent::setUp();
        
        // Create test directory and files
        $this->test_dir = WP_CONTENT_DIR . '/test-permissions';
        wp_mkdir_p($this->test_dir);
        
        // Create test files with different permissions
        $this->test_files = [
            'test-file.txt' => '0644',
            'test-wp-config.php' => '0444',
            'test-directory' => '0755'
        ];
        
        foreach ($this->test_files as $file => $perm) {
            if (strpos($file, '.') !== false) {
                // Create file
                file_put_contents($this->test_dir . '/' . $file, 'test content');
            } else {
                // Create directory
                wp_mkdir_p($this->test_dir . '/' . $file);
            }
            chmod($this->test_dir . '/' . $file, octdec($perm));
        }
        
        $this->manager = new SSWP_File_Permission_Manager([
            'wp-content/test-permissions/test-file.txt',
            'wp-content/test-permissions/test-wp-config.php',
            'wp-content/test-permissions/test-directory'
        ]);
    }

    public function tearDown(): void {
        // Clean up test files
        $this->removeDirectory($this->test_dir);
        parent::tearDown();
    }

    private function removeDirectory($dir) {
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file != "." && $file != "..") {
                    $path = $dir . DIRECTORY_SEPARATOR . $file;
                    if (is_dir($path)) {
                        $this->removeDirectory($path);
                    } else {
                        unlink($path);
                    }
                }
            }
            rmdir($dir);
        }
    }

    public function test_check_permissions() {
        $results = $this->manager->check_permissions();

        
        $this->assertIsArray($results);
        $this->assertCount(3, $results);
        
        foreach ($results as $file => $info) {
            $this->assertArrayHasKey('exists', $info);
            $this->assertArrayHasKey('permission', $info);
            $this->assertArrayHasKey('writable', $info);
            $this->assertArrayHasKey('recommended', $info);
            
            $this->assertTrue($info['exists']);
        }
    }

    public function test_get_recommended_permission() {
        // Test directory permission
        $dir_perm = $this->manager->get_recommended_permission($this->test_dir . '/test-directory');
        $this->assertEquals('0755', $dir_perm);
        
        // Test regular file permission
        $file_perm = $this->manager->get_recommended_permission($this->test_dir . '/test-file.txt');
        $this->assertEquals('0644', $file_perm);
        
        // Test wp-config permission
        $config_perm = $this->manager->get_recommended_permission($this->test_dir . '/test-wp-config.php');
        $this->assertEquals('0444', $config_perm);
    }

    public function test_change_file_permission() {
        $test_file = $this->test_dir . '/test-file.txt';
        $new_permission = '0444';
        
        $result = $this->manager->change_file_permission($test_file, $new_permission);
        $this->assertTrue($result);
        
        $current_perm = substr(sprintf('%o', fileperms($test_file)), -3);
        $this->assertEquals('444', $current_perm);
    }

    public function test_change_to_recommended_permissions() {
        $paths = [
            'wp-content/test-permissions/test-file.txt',
            'wp-content/test-permissions/test-directory'
        ];
        
        $errors = $this->manager->change_to_recommended_permissions($paths);
        $this->assertEmpty($errors);
        
        // Verify permissions were changed correctly
        $file_perm = substr(sprintf('%o', fileperms($this->test_dir . '/test-file.txt')), -3);
        $dir_perm = substr(sprintf('%o', fileperms($this->test_dir . '/test-directory')), -3);
        
        $this->assertEquals('644', $file_perm);
        $this->assertEquals('755', $dir_perm);
    }

    public function test_get_current_permission() {
        $test_file = $this->test_dir . '/test-file.txt';
        $permission = $this->manager->get_current_permission($test_file);
        
        $this->assertNotNull($permission);
        $this->assertEquals('644', $permission);
        
        // Test non-existent file
        $nonexistent = $this->test_dir . '/nonexistent.txt';
        $permission = $this->manager->get_current_permission($nonexistent);
        $this->assertNull($permission);
    }

    public function test_set_recommended_permission() {
        // Test valid directory permission change
        $result = $this->manager->set_recommended_permission('directory', '775');
        $this->assertTrue($result);
        
        // Test valid file permission change
        $result = $this->manager->set_recommended_permission('file', '664');
        $this->assertTrue($result);
        
        // Test invalid type
        $result = $this->manager->set_recommended_permission('invalid', '644');
        $this->assertFalse($result);
        
        // Test invalid permission format
        $result = $this->manager->set_recommended_permission('file', '8888');
        $this->assertFalse($result);
    }

    public function test_set_permission() {
        $test_file = $this->test_dir . '/test-file.txt';
        $new_permission = '0444';
        
        $result = $this->manager->set_permission($test_file, $new_permission);
        $this->assertTrue($result);
        
        $current_perm = substr(sprintf('%o', fileperms($test_file)), -3);
        $this->assertEquals('444', $current_perm);
        
        // Test non-existent file
        $result = $this->manager->set_permission($this->test_dir . '/nonexistent.txt', '0644');
        $this->assertFalse($result);
    }

    public function test_is_wp_owner() {
        $test_file = $this->test_dir . '/test-file.txt';
        $result = $this->manager->is_wp_owner($test_file);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('ownership', $result);
        $this->assertArrayHasKey('security', $result);
    }
}
