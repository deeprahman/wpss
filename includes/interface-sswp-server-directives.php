<?php

interface ISSWP_Server_Directives {

	public function protect_debug_log();

	public function unprotect_debug_log();


	public function protect_user_rest_apt( $page = '' );


	public function unprotect_user_rest_apt();

	public function allow_file_access( $file_pattern );

	public function disallow_file_access();
}
