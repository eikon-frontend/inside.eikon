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

  // 1. Register a custom plupload file filter.
  // This runs before the upload starts and can reject files cleanly.
  if (typeof plupload !== 'undefined') {
    plupload.addFileFilter('eikon_filename', function (value, file, callback) {
      if (value && !regex.test(file.name)) {

        // Ensure WordPress Attachment UI immediately gets the HTML error message
        if (typeof wp !== 'undefined' && wp.media && wp.media.model && wp.media.model.Attachment) {
          // Sometimes wp links attachment via plupload file id
          var attachment = wp.media.model.Attachment.get(file.id);
          if (attachment) {
            attachment.set('error', errorMessage);
          }
        }

        // Trigger Plupload Error event. Use a custom error code so WordPress doesn't overwrite it.
        this.trigger('Error', {
          code: -999,
          message: errorMessage,
          file: file
        });
        callback(false);
      } else {
        callback(true);
      }
    });
  }

  // 2. Safely apply this filter to all WordPress Uploader defaults.
  // This avoids breaking the wp.Uploader init pipeline.
  if (typeof wp !== 'undefined' && wp.Uploader && wp.Uploader.defaults) {
    wp.Uploader.defaults.filters = wp.Uploader.defaults.filters || {};
    wp.Uploader.defaults.filters.eikon_filename = true;
  }

})(jQuery);
