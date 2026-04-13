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

  if (typeof wp !== 'undefined' && wp.Uploader) {
    var originalInit = wp.Uploader.prototype.init;

    wp.Uploader.prototype.init = function () {
      // 1. Let WordPress construct the Uploader to initialize the internal UI
      if (originalInit) {
        originalInit.apply(this, arguments);
      }

      // 2. Bind validation AFTER WordPress processes the 'FilesAdded' drop event
      this.uploader.bind('FilesAdded', function (up, files) {

        // Loop backwards to safely call removeFile during iteration
        for (var i = files.length - 1; i >= 0; i--) {
          var file = files[i];

          if (!regex.test(file.name)) {
            // Instantly remove from queue
            up.removeFile(file);

            // Delay the Error trigger slightly to let WP build the UI thumbnail
            // Use a custom negative code (-9999) to force WP to output our exact errorMessage
            setTimeout((function (f) {
              return function () {
                if (up) {
                  up.trigger('Error', {
                    code: -9999,
                    message: errorMessage,
                    file: f
                  });
                }
              };
            })(file), 50);
          }
        }
      });
    };
