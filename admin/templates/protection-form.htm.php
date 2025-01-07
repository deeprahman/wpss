<?php
/**
 * Protection form
 */
// Get the allowed_files option
global $wpss;

$file_types = $wpss->get_file_types();

?>

<form class="htaccess-form">
	<h3 class="">Protection Settings</h3>
	<ul>
		<li>
			<input type="checkbox" id="protect-debug-log" name="protect-debug-log">
			<label for="protect-debug-log" title="<?php echo esc_attr__( 'Protect the WordPress log at default location', 'secure-setup' ); ?>">
				<?php echo esc_html__( 'Protect Debug Log', 'secure-setup' ); ?>
			</label>
		</li>
		<li>
			<input type="checkbox" id="protect-update-directory" name="protect-update-directory">
			<label for="protect-update-directory" title="<?php echo esc_attr__( 'Select which file-types should have access to uploads directory', 'secure-setup' ); ?>">
				<?php echo esc_html__( 'Protect Update Directory', 'secure-setup' ); ?>
			</label>
			<div id="update-directory-options" style="display: none;">
				<h4><?php echo esc_html__( 'Give access to the selected file-types only', 'secure-setup' ); ?></h4>
				<select id="mySelect" name="allowed_files" multiple="multiple">
					<?php foreach ( $file_types as $ext ) : ?>
						<option value="<?php echo esc_attr( $ext ); ?>"><?php echo esc_html( strtoupper( $ext ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</li>
		<li>
			<input type="checkbox" id="protect-xml-rpc" name="protect-xml-rpc">
			<label for="protect-xml-rpc" title="<?php echo esc_attr__( 'Disable the system.multicall method', 'secure-setup' ); ?>">
				<?php echo esc_html__( 'Protect XML-RPC', 'secure-setup' ); ?>
			</label>
		</li>
		<li>
			<input type="checkbox" id="protect-rest-endpoint" name="protect-rest-endpoint">
			<label for="protect-rest-endpoint" title="<?php echo esc_attr__( 'Redirect requests to the users REST endpoint to 404 HTTP error', 'secure-setup' ); ?>">
				<?php echo esc_html__( 'Protect REST Endpoint', 'secure-setup' ); ?>
			</label>
		</li>
	</ul>
	<button type="submit" class="button button-primary">
		<?php echo esc_html__( 'Save Settings', 'secure-setup' ); ?>
	</button>
</form>

