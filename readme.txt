=== Secure Setup ===
Contributors: deeprahman
Tags: security, file permissions, .htaccess, WordPress security, REST API
Requires at least: 5.2
Tested up to: 6.7
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enhance WordPress security by setting recommended file permissions, securing .htaccess, and disabling sensitive endpoints.

== Description ==
**Securing Setup** helps protect your WordPress installation by:
1. Allowing users to set recommended file permissions for directories and subdirectories.
2. Automatically modifying the `.htaccess` file to:
   - Protect the `debug.log` file from being accessed via the web.
   - Restrict execution of specific file types (e.g., `.png`, `.jpg`), ensuring only selected file types are processed by the web server.
3. Disabling sensitive WordPress endpoints such as:
   - `system.multicall` from XML-RPC.
   - The `users` endpoint in the REST API.

The plugin is user-friendly and includes an easy-to-access settings page.

You can view or contribute to the plugin's source code on GitHub:  
[GitHub Repository]https://github.com/deeprahman/wpss)

== Features ==
- Set directory and subdirectory permissions for enhanced security.
- Automate `.htaccess` file modifications.
- Disable potentially vulnerable endpoints.
- Tested with the latest version of WordPress.

== Installation ==
1. Upload the `securing-setup` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Tools > File Permission** to configure settings.

== Frequently Asked Questions ==

= What are recommended file permissions? =
The plugin will recommend secure file permissions (e.g., `755` for directories and `644` for files) to reduce risks from unauthorized access.

= Can I undo `.htaccess` modifications? =
Yes, the plugin provides options to revert changes made to the `.htaccess` file.

= Will this plugin break my media uploads or other file handling? =
No, you can configure which file types are allowed for execution by the web server, ensuring normal functionality.

= What endpoints are disabled by this plugin? =
The plugin disables:
- The `system.multicall` function in XML-RPC to prevent potential attacks.
- The `users` endpoint in the REST API to hide user enumeration.

== Screenshots ==
1. **Settings Page** - The File Permission settings and `.htaccess` configuration panel.
2. **Recommended File Permissions** - Displays the recommended permissions for a secure WordPress setup.

== Changelog ==

= 1.0.0 =
* Initial release.
* File permissions management for directories and files.
* `.htaccess` customization for secure file handling.
* Disabled `system.multicall` and `users` REST endpoint for added protection.

== Upgrade Notice ==
= 1.0.0 =
Initial release. Ensure your PHP version is 7.2 or higher and WordPress is updated to the latest version.

== Notes ==
After activation, the plugin adds a submenu named **File Permission** under the Tools menu, where you can configure settings.


