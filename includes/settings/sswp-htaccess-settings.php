<?php
/**
 * Contains default settings
 * for htaccess protect from
 */


$htaccess_from_settings['ht_form'] = array(
	array(
		'name'  => 'protect-debug-log',
		'value' => 'off',
	),
	array(
		'name'  => 'protect-update-directory',
		'value' => 'off',
	),
	array(
		'name'  => 'protect-xml-rpc',
		'value' => 'off',
	),
	array(
		'name'  => 'protect-rest-endpoint',
		'value' => 'off',
	),
	array(
		'name'  => 'sswp_allowed_files',
		'value' => array(),
	),
);

$htaccess_from_settings['file_types'] = array(
	'jpeg',
	'gif',
	'pdf',
	'doc',
	'mov',
	'png',
	'mkv',
	'txt',
	'xls',
	'webp',
);

$htaccess_from_settings['extension_map'] = array(
	'jpg'  => 'jpe?g',
	'jpeg' => 'jpe?g',
	'tif'  => 'tiff?',
	'tiff' => 'tiff?',
);

return $htaccess_from_settings;
