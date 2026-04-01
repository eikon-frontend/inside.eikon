/**
 * Client-side filename validation for WordPress media uploader.
 * Intercepts files BEFORE they start uploading so large files
 * are never sent to the server if the name is invalid.
 */
(function ($) {
  'use strict';

  if (typeof eikonFilename === 'undefined') {
    return;
  }

  var regex = new RegExp(eikonFilename.regex);
  var errorMessage = eikonFilename.errorMessage;

  /**
   * Hook into wp.Uploader (plupload wrapper) to validate filenames
   * before the upload begins.
   */
  if (typeof wp !== 'undefined' && wp.Uploader) {
    $.extend(wp.Uploader.prototype, {
      init: function () {
        this.uploader.bind('FilesAdded', function (uploader, files) {
          var invalidFiles = [];

          // Check each file against the naming regex
          for (var i = files.length - 1; i >= 0; i--) {
            if (!regex.test(files[i].name)) {
              invalidFiles.push(files[i].name);
              uploader.removeFile(files[i]);
            }
          }

          if (invalidFiles.length > 0) {
            alert(errorMessage);
          }
        });
      }
    });
  }

  /**
   * Fallback: also validate on drag-and-drop and file input changes
   * in the media modal.
   */
  if (typeof wp !== 'undefined' && wp.media) {
    var originalMediaFrameOpen = wp.media.view.MediaFrame.prototype.open;
    wp.media.view.MediaFrame.prototype.open = function () {
      var result = originalMediaFrameOpen.apply(this, arguments);

      // Attach validation when the uploader is ready
      if (this.uploader && this.uploader.uploader && this.uploader.uploader.uploader) {
        var pluploadInstance = this.uploader.uploader.uploader;

        // Avoid double-binding
        if (!pluploadInstance._eikonValidationBound) {
          pluploadInstance.bind('FilesAdded', function (uploader, files) {
            for (var i = files.length - 1; i >= 0; i--) {
              if (!regex.test(files[i].name)) {
                uploader.removeFile(files[i]);
                alert(errorMessage);
                return;
              }
            }
          });
          pluploadInstance._eikonValidationBound = true;
        }
      }

      return result;
    };
  }

})(jQuery);
