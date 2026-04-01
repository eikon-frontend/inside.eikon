/**
 * Client-side filename validation for WordPress media uploader.
 * Intercepts files BEFORE they start uploading so large files
 * are never sent to the server if the name is invalid.
 *
 * Uses a custom plupload file filter so the normal upload pipeline
 * is not disrupted.
 */
(function ($) {
  'use strict';

  if (typeof eikonFilename === 'undefined') {
    return;
  }

  var regex = new RegExp(eikonFilename.regex);
  var errorMessage = eikonFilename.errorMessage;

  // Register a custom plupload file filter.
  // This runs before the upload starts and can reject files cleanly.
  if (typeof plupload !== 'undefined') {
    plupload.addFileFilter('eikon_filename', function (value, file, callback) {
      if (value && !regex.test(file.name)) {
        this.trigger('Error', {
          code: plupload.FILE_EXTENSION_ERROR,
          message: errorMessage,
          file: file
        });
        callback(false);
      } else {
        callback(true);
      }
    });
  }

  // Inject the custom filter into every wp.Uploader instance.
  if (typeof wp !== 'undefined' && wp.Uploader) {
    var originalInit = wp.Uploader.prototype.init;

    wp.Uploader.prototype.init = function () {
      // Add our filter to the plupload configuration
      this.uploader.settings.filters = this.uploader.settings.filters || {};
      this.uploader.settings.filters.eikon_filename = true;

      // Call the original init so the upload pipeline works normally
      if (originalInit) {
        originalInit.call(this);
      }
    };
  }

})(jQuery);
