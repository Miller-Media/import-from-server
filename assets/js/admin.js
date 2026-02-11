/* global jQuery, ifsData, wp */
(function ($) {
	'use strict';

	var state = {
		currentPath: ifsData.rootPath,
		selectedFiles: [],
		importing: false
	};

	/**
	 * Format bytes to human-readable size.
	 */
	function formatSize(bytes) {
		if (bytes === 0) return '0 B';
		var units = ['B', 'KB', 'MB', 'GB'];
		var i = Math.floor(Math.log(bytes) / Math.log(1024));
		return (bytes / Math.pow(1024, i)).toFixed(i > 0 ? 1 : 0) + ' ' + units[i];
	}

	/**
	 * Format a Unix timestamp to a locale date string.
	 */
	function formatDate(timestamp) {
		var d = new Date(timestamp * 1000);
		return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
	}

	/**
	 * Get a dashicon class based on MIME type.
	 */
	function getFileIcon(mimeType) {
		if (!mimeType) return 'dashicons-media-default';
		if (mimeType.indexOf('image/') === 0) return 'dashicons-format-image';
		if (mimeType.indexOf('video/') === 0) return 'dashicons-format-video';
		if (mimeType.indexOf('audio/') === 0) return 'dashicons-format-audio';
		if (mimeType === 'application/pdf') return 'dashicons-pdf';
		if (mimeType.indexOf('text/') === 0) return 'dashicons-media-text';
		if (mimeType.indexOf('application/zip') === 0 || mimeType.indexOf('application/x-') === 0) return 'dashicons-media-archive';
		return 'dashicons-media-default';
	}

	/**
	 * Browse a directory via REST API.
	 */
	function browse(path) {
		state.currentPath = path;
		state.selectedFiles = [];
		updateSelectedCount();

		$('#ifs-file-list').html('<p class="ifs-loading">' + ifsData.i18n.loading + '</p>');
		$('#ifs-select-all').prop('checked', false);

		wp.apiRequest({
			path: 'import-from-server/v1/browse',
			data: { path: path },
			type: 'GET'
		}).done(function (response) {
			renderBreadcrumbs(response.breadcrumbs);
			renderFileList(response.directories, response.files);
		}).fail(function (xhr) {
			var msg = xhr.responseJSON && xhr.responseJSON.message
				? xhr.responseJSON.message
				: ifsData.i18n.errorLoading;
			$('#ifs-file-list').html('<p class="ifs-error">' + msg + '</p>');
		});
	}

	/**
	 * Render breadcrumb navigation.
	 */
	function renderBreadcrumbs(breadcrumbs) {
		var html = '';
		for (var i = 0; i < breadcrumbs.length; i++) {
			if (i > 0) {
				html += '<span class="ifs-breadcrumb-separator">/</span>';
			}
			if (i === breadcrumbs.length - 1) {
				html += '<span class="ifs-breadcrumb-current">' + escHtml(breadcrumbs[i].label) + '</span>';
			} else {
				html += '<a href="#" class="ifs-breadcrumb-link" data-path="' + escAttr(breadcrumbs[i].path) + '">' + escHtml(breadcrumbs[i].label) + '</a>';
			}
		}
		$('#ifs-breadcrumbs').html(html);
	}

	/**
	 * Render the file listing table.
	 */
	function renderFileList(directories, files) {
		if (directories.length === 0 && files.length === 0) {
			$('#ifs-file-list').html('<p class="ifs-empty">' + ifsData.i18n.noFiles + '</p>');
			return;
		}

		var html = '<table class="ifs-file-table">';
		html += '<thead><tr>';
		html += '<th></th>';
		html += '<th>' + escHtml('Name') + '</th>';
		html += '<th>' + escHtml('Type') + '</th>';
		html += '<th>' + escHtml('Size') + '</th>';
		html += '<th>' + escHtml('Modified') + '</th>';
		html += '<th>' + escHtml('Status') + '</th>';
		html += '</tr></thead><tbody>';

		// Directories first.
		for (var d = 0; d < directories.length; d++) {
			html += '<tr class="ifs-row-dir">';
			html += '<td></td>';
			html += '<td colspan="5"><a href="#" class="ifs-dir-link" data-path="' + escAttr(directories[d].path) + '">';
			html += '<span class="dashicons dashicons-category"></span>';
			html += escHtml(directories[d].name);
			html += '</a></td>';
			html += '</tr>';
		}

		// Files.
		for (var f = 0; f < files.length; f++) {
			var file = files[f];
			var rowClass = 'ifs-row-file';
			var checkboxHtml = '';
			var statusHtml = '';

			if (file.imported) {
				rowClass += ' ifs-row-imported';
				statusHtml = '<span class="ifs-badge ifs-badge-imported">' + ifsData.i18n.alreadyImported + '</span>';
			} else if (!file.importable) {
				rowClass += ' ifs-row-not-importable';
				statusHtml = '<span class="ifs-badge ifs-badge-not-importable">' + escHtml('Not allowed') + '</span>';
			} else {
				checkboxHtml = '<input type="checkbox" class="ifs-file-checkbox" data-path="' + escAttr(file.path) + '" />';
			}

			html += '<tr class="' + rowClass + '">';
			html += '<td>' + checkboxHtml + '</td>';
			html += '<td><span class="ifs-file-icon"><span class="dashicons ' + getFileIcon(file.mime_type) + '"></span></span>' + escHtml(file.name) + '</td>';
			html += '<td class="ifs-col-type">' + escHtml(file.mime_type || '-') + '</td>';
			html += '<td class="ifs-col-size">' + formatSize(file.size) + '</td>';
			html += '<td class="ifs-col-date">' + formatDate(file.modified) + '</td>';
			html += '<td>' + statusHtml + '</td>';
			html += '</tr>';
		}

		html += '</tbody></table>';
		$('#ifs-file-list').html(html);
	}

	/**
	 * Update the selected file count in the toolbar.
	 */
	function updateSelectedCount() {
		$('#ifs-selected-count').text(state.selectedFiles.length);
		$('#ifs-import-btn').prop('disabled', state.selectedFiles.length === 0 || state.importing);
	}

	/**
	 * Run the import for selected files.
	 */
	function importFiles() {
		if (state.selectedFiles.length === 0 || state.importing) {
			return;
		}

		state.importing = true;
		$('#ifs-import-btn').prop('disabled', true).text(ifsData.i18n.importing);
		$('#ifs-progress').show();
		$('#ifs-import-log').empty();
		$('#ifs-progress-text').text('0 / ' + state.selectedFiles.length);
		$('#ifs-progress-bar').css('width', '0%');

		wp.apiRequest({
			path: 'import-from-server/v1/import',
			type: 'POST',
			data: JSON.stringify({ files: state.selectedFiles }),
			contentType: 'application/json'
		}).done(function (response) {
			var results = response.results;
			var total = results.length;

			for (var i = 0; i < results.length; i++) {
				var r = results[i];
				var pct = Math.round(((i + 1) / total) * 100);
				$('#ifs-progress-bar').css('width', pct + '%');
				$('#ifs-progress-text').text((i + 1) + ' / ' + total);

				if (r.success) {
					$('#ifs-import-log').append(
						'<div class="ifs-log-entry ifs-log-success">' + escHtml(r.file) + ' — ' + ifsData.i18n.imported + '</div>'
					);
				} else {
					$('#ifs-import-log').append(
						'<div class="ifs-log-entry ifs-log-error">' + escHtml(r.file) + ' — ' + escHtml(r.error) + '</div>'
					);
				}
			}

			$('#ifs-progress-text').text(ifsData.i18n.complete + ' (' + total + ' files)');
			state.importing = false;
			$('#ifs-import-btn').text(ifsData.i18n.importSelected + ' (0)');

			// Refresh the file list to show updated "imported" status.
			browse(state.currentPath);

		}).fail(function (xhr) {
			var msg = xhr.responseJSON && xhr.responseJSON.message
				? xhr.responseJSON.message
				: ifsData.i18n.failed;
			$('#ifs-import-log').append(
				'<div class="ifs-log-entry ifs-log-error">' + escHtml(msg) + '</div>'
			);
			state.importing = false;
			$('#ifs-import-btn').prop('disabled', false).text(ifsData.i18n.importSelected + ' (' + state.selectedFiles.length + ')');
		});
	}

	/**
	 * HTML-escape a string.
	 */
	function escHtml(str) {
		var div = document.createElement('div');
		div.appendChild(document.createTextNode(str));
		return div.innerHTML;
	}

	/**
	 * Attribute-escape a string.
	 */
	function escAttr(str) {
		return escHtml(str).replace(/"/g, '&quot;');
	}

	// --- Event Handlers ---

	$(document).ready(function () {
		if ($('#ifs-app').length === 0) {
			return;
		}

		// Initial load.
		browse(ifsData.rootPath);

		// Breadcrumb navigation.
		$('#ifs-breadcrumbs').on('click', '.ifs-breadcrumb-link', function (e) {
			e.preventDefault();
			browse($(this).data('path'));
		});

		// Directory navigation.
		$('#ifs-file-list').on('click', '.ifs-dir-link', function (e) {
			e.preventDefault();
			browse($(this).data('path'));
		});

		// File checkbox toggle.
		$('#ifs-file-list').on('change', '.ifs-file-checkbox', function () {
			var path = $(this).data('path');
			if ($(this).is(':checked')) {
				if (state.selectedFiles.indexOf(path) === -1) {
					state.selectedFiles.push(path);
				}
			} else {
				state.selectedFiles = state.selectedFiles.filter(function (f) { return f !== path; });
			}
			updateSelectedCount();

			// Update select-all checkbox state.
			var total = $('.ifs-file-checkbox').length;
			var checked = $('.ifs-file-checkbox:checked').length;
			$('#ifs-select-all').prop('checked', total > 0 && checked === total);
		});

		// Select All.
		$('#ifs-select-all').on('change', function () {
			var isChecked = $(this).is(':checked');
			$('.ifs-file-checkbox').prop('checked', isChecked);
			state.selectedFiles = [];
			if (isChecked) {
				$('.ifs-file-checkbox').each(function () {
					state.selectedFiles.push($(this).data('path'));
				});
			}
			updateSelectedCount();
		});

		// Import button.
		$('#ifs-import-btn').on('click', function () {
			importFiles();
		});
	});

})(jQuery);
