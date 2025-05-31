jQuery(document).ready(function ($) {

  // Tab functionality
  $('.vod-tab-nav').on('click', function (e) {
    e.preventDefault();

    var targetTab = $(this).data('tab');

    // Remove active class from all tabs and panels
    $('.vod-tab-nav').removeClass('active');
    $('.vod-tab-panel').removeClass('active');

    // Add active class to clicked tab and corresponding panel
    $(this).addClass('active');
    $('#' + targetTab + '-tab').addClass('active');
  });

  // Upload functionality
  $('#vod-upload-form').on('submit', function (e) {
    e.preventDefault();

    console.log('VOD Eikon: Upload form submitted');

    var formData = new FormData(this);
    formData.append('action', 'upload_vod_video');
    formData.append('nonce', vodEikon.nonce);

    // Log form data for debugging
    console.log('VOD Eikon: Form data prepared');
    for (var pair of formData.entries()) {
      if (pair[0] !== 'video_file') { // Don't log file content
        console.log('VOD Eikon: ' + pair[0] + ': ' + pair[1]);
      } else {
        console.log('VOD Eikon: video_file: [File object]');
      }
    }

    var $form = $(this);
    var $uploadBtn = $('#upload-video');
    var $cancelBtn = $('#cancel-upload');
    var $progress = $('#upload-progress');
    var $status = $('#upload-status');

    // Reset status
    $status.empty();

    // Validate required fields
    var fileInput = $('#video-file')[0];

    console.log('VOD Eikon: File input files count: ' + fileInput.files.length);

    if (!fileInput.files.length) {
      console.log('VOD Eikon: Validation failed - no file selected');
      $status.html('<div class="notice notice-error inline"><p>Veuillez sélectionner un fichier vidéo.</p></div>');
      return;
    }

    var file = fileInput.files[0];
    var maxSize = vodEikon.max_upload_size; // Use server upload limit

    console.log('VOD Eikon: File details - Name: ' + file.name + ', Size: ' + file.size + ', Type: ' + file.type);
    console.log('VOD Eikon: Server upload limit: ' + vodEikon.max_upload_size_formatted);

    if (file.size > maxSize) {
      console.log('VOD Eikon: Validation failed - file too large');
      $status.html('<div class="notice notice-error inline"><p>La taille du fichier dépasse la limite du serveur de ' + vodEikon.max_upload_size_formatted + '.</p></div>');
      return;
    }

    // Check file type
    var allowedTypes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska'];
    if (!allowedTypes.includes(file.type)) {
      console.log('VOD Eikon: Validation failed - invalid file type: ' + file.type);
      $status.html('<div class="notice notice-error inline"><p>Type de fichier invalide. Veuillez télécharger uniquement des fichiers MP4, MOV, AVI ou MKV.</p></div>');
      return;
    }

    console.log('VOD Eikon: Validation passed, starting upload');

    // Show progress and disable form
    $progress.show();
    $uploadBtn.hide();
    $cancelBtn.show();
    $form.find('input, textarea').prop('disabled', true);

    // Create XMLHttpRequest for progress tracking
    var xhr = new XMLHttpRequest();

    // Track upload progress
    xhr.upload.addEventListener('progress', function (e) {
      if (e.lengthComputable) {
        var percentComplete = (e.loaded / e.total) * 100;
        console.log('VOD Eikon: Upload progress: ' + Math.round(percentComplete) + '%');
        $('.progress-fill').css('width', percentComplete + '%');
        $('.progress-text').text('Téléchargement... ' + Math.round(percentComplete) + '%');
      }
    });

    xhr.addEventListener('load', function () {
      console.log('VOD Eikon: Upload request completed with status: ' + xhr.status);
      console.log('VOD Eikon: Response text length: ' + xhr.responseText.length);
      console.log('VOD Eikon: Response text (first 500 chars): ' + xhr.responseText.substring(0, 500));

      if (xhr.status === 200) {
        try {
          var response = JSON.parse(xhr.responseText);
          console.log('VOD Eikon: Parsed response: ', response);

          if (response.success) {
            console.log('VOD Eikon: Upload successful');
            $status.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
            $form[0].reset();

            // Switch to videos tab and refresh
            setTimeout(function () {
              $('.vod-tab-nav[data-tab="videos"]').click();
              location.reload();
            }, 2000);
          } else {
            console.log('VOD Eikon: Upload failed - server error: ' + response.data.message);
            $status.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
          }
        } catch (e) {
          console.error('VOD Eikon: Failed to parse response JSON: ', e);
          console.log('VOD Eikon: Raw response: ', xhr.responseText);
          // Check if response contains PHP error about POST size limit
          if (xhr.responseText.includes('POST Content-Length') && xhr.responseText.includes('exceeds the limit')) {
            $status.html('<div class="notice notice-error inline"><p>Fichier trop volumineux pour la configuration du serveur. Veuillez contacter votre administrateur.</p></div>');
          } else if (xhr.responseText.includes('Fatal error') || xhr.responseText.includes('PHP Fatal error')) {
            $status.html('<div class="notice notice-error inline"><p>Erreur serveur pendant le téléchargement. Veuillez vérifier les logs du serveur et réessayer.</p></div>');
          } else {
            $status.html('<div class="notice notice-error inline"><p>Réponse invalide du serveur. Veuillez réessayer.</p></div>');
          }
        }
      } else if (xhr.status === 400) {
        // Check if it's a PHP POST size limit error
        if (xhr.responseText.includes('POST Content-Length') && xhr.responseText.includes('exceeds the limit')) {
          $status.html('<div class="notice notice-error inline"><p>La configuration du serveur a été mise à jour. Veuillez réessayer de télécharger.</p></div>');
        } else {
          $status.html('<div class="notice notice-error inline"><p>Requête incorrecte. Veuillez vérifier votre fichier et réessayer.</p></div>');
        }
      } else {
        console.log('VOD Eikon: Upload failed with HTTP status: ' + xhr.status);
        $status.html('<div class="notice notice-error inline"><p>Échec du téléchargement (HTTP ' + xhr.status + '). Veuillez réessayer.</p></div>');
      }

      // Reset form state
      $progress.hide();
      $uploadBtn.show();
      $cancelBtn.hide();
      $form.find('input, textarea').prop('disabled', false);
      $('.progress-fill').css('width', '0%');
      $('.progress-text').text('Téléchargement... 0%');
    });

    xhr.addEventListener('error', function () {
      console.error('VOD Eikon: XMLHttpRequest error occurred');
      $status.html('<div class="notice notice-error inline"><p>Échec du téléchargement. Veuillez vérifier votre connexion et réessayer.</p></div>');

      // Reset form state
      $progress.hide();
      $uploadBtn.show();
      $cancelBtn.hide();
      $form.find('input, textarea').prop('disabled', false);
      $('.progress-fill').css('width', '0%');
      $('.progress-text').text('Téléchargement... 0%');
    });

    xhr.addEventListener('timeout', function () {
      console.error('VOD Eikon: XMLHttpRequest timeout occurred');
      $status.html('<div class="notice notice-error inline"><p>Délai d\'attente du téléchargement dépassé. Veuillez réessayer ou contacter le support si le problème persiste.</p></div>');

      // Reset form state
      $progress.hide();
      $uploadBtn.show();
      $cancelBtn.hide();
      $form.find('input, textarea').prop('disabled', false);
      $('.progress-fill').css('width', '0%');
      $('.progress-text').text('Téléchargement... 0%');
    });

    // Send the request
    console.log('VOD Eikon: Sending request to: ' + vodEikon.ajax_url);
    xhr.timeout = 600000; // 10 minutes timeout
    xhr.open('POST', vodEikon.ajax_url);
    xhr.send(formData);

    // Store xhr for potential cancellation
    $form.data('xhr', xhr);
  });

  // Cancel upload functionality
  $('#cancel-upload').on('click', function () {
    var $form = $('#vod-upload-form');
    var xhr = $form.data('xhr');

    if (xhr) {
      xhr.abort();
    }

    // Reset form state
    $('#upload-progress').hide();
    $('#upload-video').show();
    $('#cancel-upload').hide();
    $form.find('input, textarea').prop('disabled', false);
    $('.progress-fill').css('width', '0%');
    $('.progress-text').text('Téléchargement... 0%');
    $('#upload-status').html('<div class="notice notice-warning inline"><p>Téléchargement annulé.</p></div>');
  });

  // Sync videos functionality
  $('#sync-videos').on('click', function () {
    var $button = $(this);
    var $status = $('#sync-status');
    var isSuccess = false;

    $button.prop('disabled', true).find('.dashicons').addClass('spin');
    $status.html('<span class="spinner is-active"></span> Synchronisation des vidéos...');

    $.ajax({
      url: vodEikon.ajax_url,
      type: 'POST',
      data: {
        action: 'sync_vod_videos',
        nonce: vodEikon.nonce
      },
      success: function (response) {
        if (response.success) {
          isSuccess = true;
          $status.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');

          // Reload the page to show updated videos
          setTimeout(function () {
            location.reload();
          }, 2000);
        } else {
          $status.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
        }
      },
      error: function () {
        $status.html('<div class="notice notice-error inline"><p>Une erreur s\'est produite lors de la synchronisation des vidéos.</p></div>');
      },
      complete: function () {
        $button.prop('disabled', false).find('.dashicons').removeClass('spin');

        // Only clear status message on error cases (success cases reload the page)
        if (!isSuccess) {
          setTimeout(function () {
            $status.empty();
          }, 5000);
        }
      }
    });
  });

  // Delete video functionality
  $('.delete-video').on('click', function () {
    var $button = $(this);
    var videoId = $button.data('video-id');
    var $row = $button.closest('tr');

    if (!confirm('Êtes-vous sûr de vouloir supprimer cette vidéo de la base de données locale ?')) {
      return;
    }

    $button.prop('disabled', true).text('Suppression...');

    $.ajax({
      url: vodEikon.ajax_url,
      type: 'POST',
      data: {
        action: 'delete_vod_video',
        video_id: videoId,
        nonce: vodEikon.nonce
      },
      success: function (response) {
        if (response.success) {
          $row.fadeOut(function () {
            $row.remove();
          });
        } else {
          alert('Échec de la suppression de la vidéo : ' + response.data.message);
          $button.prop('disabled', false).text('Supprimer');
        }
      },
      error: function () {
        alert('Une erreur s\'est produite lors de la suppression de la vidéo.');
        $button.prop('disabled', false).text('Supprimer');
      }
    });
  });

  // Copy MPD URL to clipboard
  $('.vod-eikon-videos').on('click', 'code', function () {
    var text = $(this).text();

    if (navigator.clipboard) {
      navigator.clipboard.writeText(text).then(function () {
        // Create temporary tooltip
        var $tooltip = $('<span class="vod-copy-tooltip">Copié !</span>');
        $(this).after($tooltip);

        setTimeout(function () {
          $tooltip.fadeOut(function () {
            $tooltip.remove();
          });
        }, 2000);
      }.bind(this));
    } else {
      // Fallback for older browsers
      var $temp = $('<textarea>');
      $('body').append($temp);
      $temp.val(text).select();
      document.execCommand('copy');
      $temp.remove();

      var $tooltip = $('<span class="vod-copy-tooltip">Copié !</span>');
      $(this).after($tooltip);

      setTimeout(function () {
        $tooltip.fadeOut(function () {
          $tooltip.remove();
        });
      }, 2000);
    }
  });

  // Update incomplete videos functionality
  $('#update-incomplete-videos').on('click', function () {
    var $button = $(this);
    var $status = $('#sync-status');
    var isSuccess = false;

    $button.prop('disabled', true).find('.dashicons').addClass('spin');
    $status.html('<span class="spinner is-active"></span> Mise à jour des vidéos incomplètes...');

    $.ajax({
      url: vodEikon.ajax_url,
      type: 'POST',
      data: {
        action: 'update_incomplete_videos',
        nonce: vodEikon.nonce
      },
      success: function (response) {
        if (response.success) {
          isSuccess = true;
          $status.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');

          // Reload the page to show updated videos after a short delay
          setTimeout(function () {
            location.reload();
          }, 2000);
        } else {
          $status.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
        }
      },
      error: function () {
        $status.html('<div class="notice notice-error inline"><p>Une erreur s\'est produite lors de la mise à jour des vidéos incomplètes.</p></div>');
      },
      complete: function () {
        $button.prop('disabled', false).find('.dashicons').removeClass('spin');

        // Only clear status message on error cases (success cases reload the page)
        if (!isSuccess) {
          setTimeout(function () {
            $status.empty();
          }, 5000);
        }
      }
    });
  });
});
