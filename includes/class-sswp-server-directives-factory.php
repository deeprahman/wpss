<?php

require_once $sswp->root . DIRECTORY_SEPARATOR . 'includes/class-sswp-server-directives-apache.php';
final class SSWP_Server_Directives_Factory {


	private static $apache_instance = null;
	private static $nginx_instance  = null;
	private static $iis_instance    = null;

	public static function create_server_directives() {
		$class_name = self::get_class_name();

		if ( self::is_class_exists( $class_name ) ) {
			return self::instantiate( $class_name );
		} else {
			return self::class_not_available( $class_name );
		}
	}

	private static function get_class_name() {
		global $is_apache, $is_nginx, $is_IIS, $is_iis7;
		$name = array(
			'SSWP_Server_Directives_Apache' => $is_apache,
			'SSWP_Server_Directives_Nginx'  => $is_nginx,
			'SSWP_Server_Directives_IIS'    => $is_IIS,
		);

		foreach ( $name as $class => $condition ) {
			if ( $condition ) {
				return $class;
			}
		}

		return null;
	}

	private static function is_class_exists( $class_name ): bool {
		return class_exists( $class_name ) && is_subclass_of( $class_name, 'SSWP_Server_Directives' );
	}

	private static function class_not_available( $class_name ) {
		error_log( 'Attempted to instantiate unavailable class: ' . $class_name );
		return new WP_Error( 'class_not_available', 'The feature is coming soon' );
	}

	private static function instantiate( $class_name ): SSWP_Server_Directives {
		switch ( $class_name ) {
			case 'SSWP_Server_Directives_Apache':
				return self::instantiate_apache();
			case 'SSWP_Server_Directives_Nginx':
				return self::instantiate_nginx();
			case 'SSWP_Server_Directives_IIS':
				return self::instantiate_IIS();
			default:
				// throw new Exception( 'Unsupported server type: ' . $class_name );
				throw new Exception( 'Unsupported server type: ' );
		}
	}

	private static function instantiate_apache(): SSWP_Server_Directives_Apache {
		if ( self::$apache_instance === null ) {
			self::$apache_instance = new SSWP_Server_Directives_Apache();
		}
		return self::$apache_instance;
	}

	private static function instantiate_nginx(): SSWP_Server_Directives_Nginx {
		if ( self::$nginx_instance === null ) {
			self::$nginx_instance = new SSWP_Server_Directives_Nginx();
		}
		return self::$nginx_instance;
	}

	private static function instantiate_IIS(): SSWP_Server_Directives_IIS {
		if ( self::$iis_instance === null ) {
			self::$iis_instance = new SSWP_Server_Directives_IIS();
		}
		return self::$iis_instance;
	}
}
