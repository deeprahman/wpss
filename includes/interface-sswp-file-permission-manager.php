<?php

/**
 * Class WPSS_File_Permission_Manager
 *
 * A comprehensive WordPress file permission management class that handles checking,
 * displaying, and modifying file permissions for critical WordPress files and directories.
 *
 * Features:
 * - Checks permissions for specified WordPress files and directories
 * - Validates paths are within WordPress installation
 * - Displays permission information in a formatted table
 * - Modifies permissions for files and directories
 * - Supports recursive permission changes
 *
 * @property array $files_to_check Array of file/directory paths to check relative to ABSPATH
 * @property array $recommended_permissions Array of recommended permissions for files and directories
 */
interface ISSWP_File_Permission_manager {

	/**
	 * Checks permissions for all configured files and directories.
	 * For each path, collects:
	 * - Existence status
	 * - Current permissions
	 * - Writability status
	 * - Recommended permissions
	 * - Any errors encountered
	 *
	 * @return array Associative array of permission check results for each path
	 */
	public function check_permissions();

	/**
	 * Determines recommended permissions based on whether path is file or directory.
	 * Uses WordPress Filesystem API to check path type.
	 *
	 * @param string $path Path to get recommended permissions for
	 * @return string Recommended permission string ('755' for directories, '644' for files)
	 */
	public function get_recommended_permission( $path );


	/**
	 * Changes permissions for a specific file or directory.
	 * Validates path is within WordPress installation and exists.
	 * Uses WordPress Filesystem API to modify permissions.
	 *
	 * @param string $path Target file/directory absolute path
	 * @param string $permission Permission string in octal format (e.g., '644', '755')
	 * @return bool True if permissions were changed successfully
	 */
	public function change_file_permission( $path, $permission );

	/**
	 * Recursively changes permissions for a directory and its contents.
	 * Sets recommended permissions:
	 * - Directories: typically '755'
	 * - Files: typically '644'
	 *
	 * @param string $path absolute Directory path to process
	 * @return bool True if all permissions were changed successfully
	 */
	public function recursively_change_to_recommended_permissions( $path );

	/**
	 * Gets current permission string for a path.
	 * Returns null if file doesn't exist.
	 *
	 * @param string $path absolute Path to check
	 * @return string|null Current permission string or null if path doesn't exist
	 */
	public function get_current_permission( $path );

	/**
	 * Updates recommended permissions for either files or directories.
	 * Validates:
	 * - Type is either 'directory' or 'file'
	 * - Permission is valid octal string (e.g., '644', '755')
	 *
	 * @param string $type Path type ('directory' or 'file')
	 * @param string $permission New recommended permission
	 * @return bool True if recommended permission was updated successfully
	 */
	public function set_recommended_permission( $type, $permission );


	/**
	 * Sets permissions for a specific path.
	 * Validates path exists and is within WordPress installation.
	 * Uses WordPress Filesystem API to modify permissions.
	 *
	 * @param string $path Target absolute path
	 * @param string $permission Permission string in octal format (e.g., '644', '755')
	 * @return bool True if permission was set successfully
	 */
	public function set_permission( $path, $permission );

	/**
	 * Apply recommended file permission the elements of the path array
	 *
	 * @param array $paths Array of absolute paths to update to recommended permissions
	 * @return array Response array with status and data/error message
	 */
	function apply_recommended_permissions( $paths );
}
