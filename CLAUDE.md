# Import from Server

## Overview
Browse files on the server and import them into the WordPress Media Library. AJAX-powered file browser with bulk import, duplicate detection, and configurable root directory.

## Architecture

```
import-from-server.php          # Entry point, defines constants, loads files (admin-only)
inc/
├── class-ifs-plugin.php        # IFS_Plugin - admin menu, enqueue, page shell
├── class-ifs-file-browser.php  # IFS_FileBrowser - path validation, directory listing
├── class-ifs-importer.php      # IFS_Importer - file import into Media Library
├── class-ifs-rest-api.php      # IFS_REST_API - REST endpoint registration
├── class-ifs-settings.php      # IFS_Settings - settings page (Settings API)
└── class-ifs-review-notice.php # IFS_ReviewNotice - review prompt after 14 days
assets/
├── css/admin.css               # Admin page styles
└── js/admin.js                 # AJAX file browser + import UI
```

## Key Classes

### IFS_FileBrowser (inc/class-ifs-file-browser.php)
Static class for file system operations.

- `validatePath($path)` — realpath() + root boundary check, returns resolved path or WP_Error
- `listDirectory($path)` — returns directories and files with metadata (name, size, type, modified, imported status)
- `isFileImportable($filepath)` — checks MIME type via wp_check_filetype() and allowed_types setting
- `isAlreadyImported($filepath)` — checks `_ifs_original_path` and `_wp_attached_file` meta

### IFS_Importer (inc/class-ifs-importer.php)
Static class for importing files into Media Library.

- `importFile($filepath)` — validates, checks MIME/duplicates, copies or moves to uploads, creates attachment

### IFS_REST_API (inc/class-ifs-rest-api.php)
Two endpoints, both requiring `upload_files` capability:

| Method | Route | Purpose |
|--------|-------|---------|
| GET | `/import-from-server/v1/browse?path=...` | List directory contents |
| POST | `/import-from-server/v1/import` | Import array of file paths |

### IFS_Settings (inc/class-ifs-settings.php)
Settings page under Settings > Import from Server (`manage_options` capability).

### IFS_Plugin (inc/class-ifs-plugin.php)
Registers admin page under Media > Import from Server, enqueues assets, renders page shell.

## Constants
- `IFS_PLUGIN_VERSION` — current version string
- `IFS_PLUGIN_PATH` — filesystem path to plugin directory
- `IFS_PLUGIN_URL` — URL to plugin directory

## Settings (wp_options)
- `ifs_settings` — array with keys: `root_path`, `import_behavior` (copy/move), `allowed_types`
- `ifs_activated_on` — activation timestamp (for review notice)

## Security Model
- `upload_files` capability for browse/import endpoints
- `manage_options` capability for settings page
- Path validation: realpath() resolves symlinks/traversal, strpos() check against root boundary
- Default root: WP_CONTENT_DIR (not filesystem root)
- MIME validation via wp_check_filetype_and_ext()

## Testing
Tests are in `../tests/unit/import-from-server/`. Run with:
```bash
make test-plugin PLUGIN=import-from-server
```

## Important Notes
- Plugin only loads in admin context (`is_admin()` check in main file)
- Files inside uploads dir are used in place; files outside are copied/moved
- Original file path stored as `_ifs_original_path` post meta for dedup/traceability
- REST API uses `wp.apiRequest()` which automatically sends X-WP-Nonce header
