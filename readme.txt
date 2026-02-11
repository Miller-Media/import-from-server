=== Import from Server ===
Contributors: jeremymiller
Tags: media, import, server, upload, files
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Browse files on the server and import them into the WordPress Media Library.

== Description ==

**Import from Server** lets WordPress administrators browse files already on the server and import them directly into the Media Library — no need to download and re-upload.

This is useful when:

* You've migrated files via FTP/SFTP and need to register them in WordPress
* A developer has placed assets on the server that need to be in the Media Library
* You have files in a non-standard location that should be accessible through WordPress

**Features:**

* AJAX-powered file browser with breadcrumb navigation
* Select individual files or use "Select All" to import in bulk
* Duplicate detection — files already in the Media Library are flagged
* Copy or move files from outside the uploads directory
* Restrict browsing to a configurable root directory (defaults to wp-content)
* Limit allowed file types via settings
* Full REST API for programmatic access
* Works with any user role that has the `upload_files` capability

**Security:**

* Path traversal protection via `realpath()` boundary checks
* MIME type validation using WordPress core functions
* Capability checks on all endpoints (`upload_files` for browse/import, `manage_options` for settings)
* CSRF protection via WordPress REST API nonces

== Installation ==

1. Upload the `import-from-server` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu
3. Go to **Media > Import from Server** to browse and import files
4. Optionally configure settings under **Settings > Import from Server**

== Frequently Asked Questions ==

= What file types can be imported? =

By default, any file type that WordPress allows for uploads. You can further restrict this with the "Allowed File Types" setting.

= Where do imported files end up? =

Files already in the WordPress uploads directory are used in place. Files from outside uploads are copied (or moved, based on your settings) into the standard uploads directory.

= Will this create duplicate files? =

The plugin checks for duplicates before importing. Files that have already been imported are shown as "Already imported" in the browser and cannot be re-imported.

= Can I change the root browsing directory? =

Yes. Go to **Settings > Import from Server** and set the Root Directory to any path on the server.

= Who can use this plugin? =

Any user with the `upload_files` capability can browse and import files. Only administrators (`manage_options`) can change the plugin settings.

== Screenshots ==

1. File browser with directory listing and import controls
2. Import progress with per-file status log
3. Settings page

== Changelog ==

= 1.0.0 =
* Initial release
* AJAX file browser with breadcrumb navigation
* Bulk import with progress tracking
* Duplicate detection
* Copy or move import behavior
* Configurable root directory and allowed file types
* REST API endpoints for browse and import
