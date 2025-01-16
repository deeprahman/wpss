<?php
trait SSWP_Ownership_Permission_Trait {

	/**
	 * WordPress Filesystem instance
	 */
	private $wp_filesystem;

	/**
	 * Initialize WordPress Filesystem
	 *
	 * @return bool True if initialization successful
	 */
	protected function initializeFilesystem(): bool {
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// Initialize WP_Filesystem
		if ( WP_Filesystem() ) {
			global $wp_filesystem;
			$this->wp_filesystem = $wp_filesystem;
			return true;
		}

		return false;
	}

	/**
	 * Check file ownership and permissions
	 *
	 * @param string $path File or directory path
	 * @return array|WP_Error Permission and ownership information
	 */
	protected function check_ownership_permissions( string $path ) {
		if ( ! $this->wp_filesystem && ! $this->initializeFilesystem() ) {
			return new WP_Error(
				'filesystem_error',
				'Unable to initialize WordPress filesystem'
			);
		}

		if ( ! $this->wp_filesystem->exists( $path ) ) {
			return new WP_Error(
				'file_not_found',
				'The specified path does not exist',
				$path
			);
		}

		try {
			return array(
				'path'        => $path,
				'ownership'   => $this->get_ownership_Info( $path ),
				'permissions' => $this->get_permissions_info( $path ),
				'access'      => $this->get_access_info( $path ),
				'security'    => $this->get_security_assessment( $path ),
			);
		} catch ( Exception $e ) {
			return new WP_Error(
				'check_failed',
				'Failed to check ownership and permissions: ' . $e->getMessage()
			);
		}
	}

	/**
	 * Get ownership information
	 *
	 * @param string $path File or directory path
	 * @return array Ownership information
	 */
	protected function get_ownership_Info( string $path ): array {
		$owner_id = fileowner( $path );
		$group_id = filegroup( $path );

		$owner_info = function_exists( 'posix_getpwuid' ) ?
			posix_getpwuid( $owner_id ) :
			array( 'name' => $owner_id );

		$group_info = function_exists( 'posix_getgrgid' ) ?
			posix_getgrgid( $group_id ) :
			array( 'name' => $group_id );

		$wp_user = $this->get_word_press_process_owner();

		return array(
			'owner'          => array(
				'id'   => $owner_id,
				'name' => $owner_info['name'] ?? 'unknown',
			),
			'group'          => array(
				'id'   => $group_id,
				'name' => $group_info['name'] ?? 'unknown',
			),
			'wordpress_user' => $wp_user,
			'is_wp_owner'    => ( $owner_info['name'] ?? '' ) === $wp_user,
		);
	}

	/**
	 * Get permissions information
	 *
	 * @param string $path File or directory path
	 * @return array Permissions information
	 */
	protected function get_permissions_info( string $path ): array {
		$perms = fileperms( $path );
		$mode  = substr( sprintf( '%o', $perms ), -4 );

		return array(
			'mode_octal'   => $mode,
			'mode_human'   => $this->getHumanReadablePermissions( $perms ),
			'is_directory' => is_dir( $path ),
			'special_bits' => array(
				'setuid' => (bool) ( $perms & 0x800 ),
				'setgid' => (bool) ( $perms & 0x400 ),
				'sticky' => (bool) ( $perms & 0x200 ),
			),
		);
	}

	/**
	 * Get access information
	 *
	 * @param string $path File or directory path
	 * @return array Access information
	 */
	protected function get_access_info( string $path ): array {
		return array(
			'readable'   => array(
				'php'       => is_readable( $path ),
				'wordpress' => $this->wp_filesystem->is_readable( $path ),
			),
			'writable'   => array(
				'php'       => is_writable( $path ),
				'wordpress' => $this->wp_filesystem->is_writable( $path ),
			),
			'executable' => is_executable( $path ),
		);
	}

	/**
	 * Get security assessment
	 *
	 * @param string $path File or directory path
	 * @return array Security assessment
	 */
	protected function get_security_assessment( string $path ): array {
		$perms          = fileperms( $path );
		$is_public      = $perms & 0x0004;
		$world_writable = $perms & 0x0002;

		return array(
			'is_secure'       => ! $world_writable,
			'warnings'        => $this->getSecurityWarnings( $path, $perms ),
			'recommendations' => $this->getSecurityRecommendations( $path, $perms ),
		);
	}

	/**
	 * Get security warnings
	 *
	 * @param string $path File or directory path
	 * @param int    $perms File permissions
	 * @return array Security warnings
	 */
	protected function getSecurityWarnings( string $path, int $perms ): array {
		$warnings = array();

		if ( $perms & 0x0002 ) {
			$warnings[] = 'File is world-writable';
		}

		if (
			( $perms & 0x0004 ) && ! is_dir( $path ) &&
			preg_match( '/\.(php|inc|config)$/i', $path )
		) {
			$warnings[] = 'Potentially sensitive file is world-readable';
		}

		if ( $perms & 0x800 ) {
			$warnings[] = 'SETUID bit is set';
		}

		if ( $perms & 0x400 ) {
			$warnings[] = 'SETGID bit is set';
		}

		return $warnings;
	}

	/**
	 * Get security recommendations
	 *
	 * @param string $path File or directory path
	 * @param int    $perms File permissions
	 * @return array Security recommendations
	 */
	protected function getSecurityRecommendations( string $path, int $perms ): array {
		$recommendations = array();
		$is_dir          = is_dir( $path );

		if ( $perms & 0x0002 ) {
			$recommendations[] = sprintf(
				'Remove world-writable permission: chmod %s %s',
				$is_dir ? '755' : '644',
				$path
			);
		}

		if ( ! $this->get_ownership_Info( $path )['is_wp_owner'] ) {
			$wp_user           = $this->get_word_press_process_owner();
			$recommendations[] = sprintf(
				'Change ownership to WordPress user: chown %s:%s %s',
				$wp_user,
				$wp_user,
				$path
			);
		}

		return $recommendations;
	}

	/**
	 * Get WordPress process owner
	 *
	 * @return string WordPress process owner
	 */
	protected function get_word_press_process_owner(): string {
		if ( function_exists( 'posix_getpwuid' ) ) {
			$owner = posix_getpwuid( posix_geteuid() );
			return $owner['name'];
		}

		return get_current_user();
	}

	/**
	 * Convert permissions to human-readable format
	 *
	 * @param int $perms Permissions value
	 * @return string Human-readable permissions
	 */
	protected function getHumanReadablePermissions( int $perms ): string {
		$info = '';

		// File type
		switch ( $perms & 0xF000 ) {
			case 0xC000:
				$info = 's';
				break; // Socket
			case 0xA000:
				$info = 'l';
				break; // Symbolic Link
			case 0x8000:
				$info = '-';
				break; // Regular
			case 0x6000:
				$info = 'b';
				break; // Block special
			case 0x4000:
				$info = 'd';
				break; // Directory
			case 0x2000:
				$info = 'c';
				break; // Character special
			case 0x1000:
				$info = 'p';
				break; // FIFO pipe
			default:
				$info = 'u';
				break; // Unknown
		}

		// Owner permissions
		$info .= ( ( $perms & 0x0100 ) ? 'r' : '-' );
		$info .= ( ( $perms & 0x0080 ) ? 'w' : '-' );
		$info .= ( ( $perms & 0x0040 ) ?
			( ( $perms & 0x0800 ) ? 's' : 'x' ) :
			( ( $perms & 0x0800 ) ? 'S' : '-' ) );

		// Group permissions
		$info .= ( ( $perms & 0x0020 ) ? 'r' : '-' );
		$info .= ( ( $perms & 0x0010 ) ? 'w' : '-' );
		$info .= ( ( $perms & 0x0008 ) ?
			( ( $perms & 0x0400 ) ? 's' : 'x' ) :
			( ( $perms & 0x0400 ) ? 'S' : '-' ) );

		// World permissions
		$info .= ( ( $perms & 0x0004 ) ? 'r' : '-' );
		$info .= ( ( $perms & 0x0002 ) ? 'w' : '-' );
		$info .= ( ( $perms & 0x0001 ) ?
			( ( $perms & 0x0200 ) ? 't' : 'x' ) :
			( ( $perms & 0x0200 ) ? 'T' : '-' ) );

		return $info;
	}
}
