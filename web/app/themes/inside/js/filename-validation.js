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
  var currentYear = eikonFilename.currentYear || '';
  var placeholders = eikonFilename.placeholders || [];

  /**
   * Validates a filename. Returns an error message string, or null if valid.
   */
  function validateFilename(name) {
    // 1. Basic format check
    if (!regex.test(name)) {
      return eikonFilename.errorMessage;
    }

    var base = name.replace(/\.[^.]+$/, ''); // strip extension

    // 2. Year validity: second segment must be first + 1 (catches "25_25" typos)
    var yearMatch = base.match(/^(\d{2,4})_(\d{2,4})/);
    if (yearMatch) {
      var y1 = parseInt(yearMatch[1], 10);
      var y2 = parseInt(yearMatch[2], 10);
      if (y2 !== y1 + 1) {
        return 'Erreur : L\'année "' + yearMatch[1] + '_' + yearMatch[2] + '" n\'est pas valide. Utilisez l\'année académique en cours : ' + currentYear + '. Exemple: ' + currentYear + '_IMD11_CIE_MonTitre_Dupont_Marie.jpg';
      }
    }

    // 3. Current academic year check (catches old years like "23_24" or "24_25")
    if (currentYear && base.indexOf(currentYear + '_') !== 0) {
      return 'Erreur : L\'année académique doit être "' + currentYear + '" pour l\'année en cours. Exemple: ' + currentYear + '_IMD11_CIE_MonTitre_Dupont_Marie.jpg';
    }

    // 4. Placeholder word check (catches copy-pasted example filenames)
    var segments = base.split('_').slice(2); // skip the two year segments
    for (var i = 0; i < segments.length; i++) {
      if (placeholders.indexOf(segments[i].toLowerCase()) !== -1) {
        return 'Erreur : Le nom de fichier contient des mots génériques ("' + segments[i] + '"). Remplacez-les par vos vraies informations. Exemple: ' + currentYear + '_IMD11_CIE_MonTitre_Dupont_Marie.jpg';
      }
    }

    return null;
  }

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
          var error = validateFilename(file.name);

          if (error !== null) {
            // Instantly remove from queue
            up.removeFile(file);

            // Delay the Error trigger slightly to let WP build the UI thumbnail
            // Use a custom negative code (-9999) to force WP to output our exact errorMessage
            setTimeout((function (f, msg) {
              return function () {
                if (up) {
                  up.trigger('Error', {
                    code: -9999,
                    message: msg,
                    file: f
                  });
                }
              };
            })(file, error), 50);
          }
        }
      });
    };
