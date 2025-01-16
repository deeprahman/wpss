<?php
/**
 * Main Plugin Page
 */
?>

<h1 id="wpss-page-heading"> <?php esc_html_e( 'WP Securing Setup', 'secure-setup' ); ?> </h1>
<hr>
<h2 id="wpss-tab-heading"> <?php esc_html_e( 'File Permission Page', 'secure-setup' ); ?> </h2>

<div id="my-tabs">
	<ul>
		<li><a href="#tab-1"><?php esc_html_e( 'File Permission Page', 'secure-setup' ); ?></a></li>
		<li><a href="#tab-2"><?php esc_html_e( '.htacces Config', 'secure-setup' ); ?></a></li>
		<li><a href="#tab-3"><?php esc_html_e( 'Site Migration', 'secure-setup' ); ?></a></li>
	</ul>
	<div id="tab-1">
		<wp-permissions-table></wp-permissions-table>
	</div>
	<div id="tab-2">
		<?php require_once SSWP_Secure_Setup::ROOT . DIRECTORY_SEPARATOR . 'admin/templates/protection-form.htm.php'; ?>
	</div>
	<div id="tab-3">
		<form id="form-3" class="tab-form" disabled>
			<h3><?php esc_html_e( 'Comming Soon...', 'secure-setup' ); ?></h3>
		</form>
	</div>
</div>

