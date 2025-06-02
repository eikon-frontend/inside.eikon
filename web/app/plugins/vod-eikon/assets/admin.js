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
    var $icon = $(this);
    var videoId = $icon.data('video-id');
    var $row = $icon.closest('tr');
    var $dashicon = $icon.find('.dashicons');

    if (!confirm('Êtes-vous sûr de vouloir supprimer cette vidéo de la base de données locale ?')) {
      return;
    }

    // Show loading state
    $icon.addClass('loading');
    $dashicon.removeClass('dashicons-trash').addClass('dashicons-update spin');

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
          // Show success state briefly before removing row
          $dashicon.removeClass('spin dashicons-update').addClass('dashicons-yes-alt');
          $icon.removeClass('loading').addClass('success');

          setTimeout(function () {
            $row.fadeOut(function () {
              $row.remove();
            });
          }, 1000);
        } else {
          alert('Échec de la suppression de la vidéo : ' + response.data.message);
          // Reset to original state
          $icon.removeClass('loading');
          $dashicon.removeClass('spin dashicons-update').addClass('dashicons-trash');
        }
      },
      error: function () {
        alert('Une erreur s\'est produite lors de la suppression de la vidéo.');
        // Reset to original state
        $icon.removeClass('loading');
        $dashicon.removeClass('spin dashicons-update').addClass('dashicons-trash');
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

  // Toggle details functionality
  $('.toggle-details').on('click', function (e) {
    e.preventDefault();

    var $icon = $(this);
    var videoId = $icon.data('video-id');
    var $detailsRow = $('#details-' + videoId);
    var $dashicon = $icon.find('.dashicons');

    if ($detailsRow.is(':visible')) {
      // Hide details
      $detailsRow.slideUp(300);
      $dashicon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
      $icon.removeClass('details-expanded');
    } else {
      // Show details
      $detailsRow.slideDown(300);
      $dashicon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
      $icon.addClass('details-expanded');
    }
  });

  // Individual video sync functionality
  $('.sync-single-video').on('click', function (e) {
    e.preventDefault();

    var $icon = $(this);
    var vodId = $icon.data('vod-id');
    var $dashicon = $icon.find('.dashicons');

    // Disable icon and show loading state
    $icon.addClass('loading');
    $dashicon.addClass('spin');

    $.ajax({
      url: vodEikon.ajax_url,
      type: 'POST',
      data: {
        action: 'sync_single_video',
        vod_id: vodId,
        nonce: vodEikon.nonce
      },
      success: function (response) {
        if (response.success) {
          // Show success state temporarily
          $dashicon.removeClass('spin dashicons-update').addClass('dashicons-yes-alt');
          $icon.removeClass('loading').addClass('success');

          // Refresh the page after a short delay to show updated data
          setTimeout(function () {
            location.reload();
          }, 1500);
        } else {
          // Show error state
          $dashicon.removeClass('spin dashicons-update').addClass('dashicons-warning');
          $icon.removeClass('loading').addClass('error');

          // Reset after delay
          setTimeout(function () {
            $icon.removeClass('error');
            $dashicon.removeClass('dashicons-warning').addClass('dashicons-update');
          }, 3000);
        }
      },
      error: function () {
        // Show error state
        $dashicon.removeClass('spin dashicons-update').addClass('dashicons-warning');
        $icon.removeClass('loading').addClass('error');

        // Reset after delay
        setTimeout(function () {
          $icon.removeClass('error');
          $dashicon.removeClass('dashicons-warning').addClass('dashicons-update');
        }, 3000);
      }
    });
  });

  // Copy URL functionality
  $('.copy-url').on('click', function (e) {
    e.preventDefault();

    var $button = $(this);
    var url = $button.data('url');

    // Use modern clipboard API if available
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(url).then(function () {
        showCopySuccess($button);
      }).catch(function () {
        fallbackCopyToClipboard(url, $button);
      });
    } else {
      fallbackCopyToClipboard(url, $button);
    }
  });

  // Helper function to show copy success
  function showCopySuccess($button) {
    var originalHtml = $button.html();
    $button.html('<span class="dashicons dashicons-yes-alt"></span> Copié !');
    $button.addClass('button-success');

    setTimeout(function () {
      $button.removeClass('button-success');
      $button.html(originalHtml);
    }, 2000);
  }

  // Fallback copy function for older browsers
  function fallbackCopyToClipboard(text, $button) {
    var textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
      document.execCommand('copy');
      showCopySuccess($button);
    } catch (err) {
      console.error('Failed to copy text: ', err);
      var originalHtml = $button.html();
      $button.html('<span class="dashicons dashicons-warning"></span> Erreur');
      $button.addClass('button-error');

      setTimeout(function () {
        $button.removeClass('button-error');
        $button.html(originalHtml);
      }, 2000);
    }

    document.body.removeChild(textArea);
  }

  // Video Player Modal functionality
  var $modal = $('#vod-player-modal');
  var $modalTitle = $('#vod-player-modal-title');
  var $playerContainer = $('#vod-player-container');
  var currentPlayer = null;

  // Play video button click handler
  $('.vod-eikon-videos').on('click', '.play-video', function (e) {
    e.preventDefault();

    var $button = $(this);
    var vodId = $button.data('vod-id');
    var mpdUrl = $button.data('mpd-url');
    var poster = $button.data('poster');
    var title = $button.data('title');

    if (!mpdUrl) {
      alert('URL MPD non disponible pour cette vidéo.');
      return;
    }

    openVideoModal(vodId, mpdUrl, poster, title);
  });

  // Close modal handlers
  $('.vod-player-modal-close, .vod-player-modal-backdrop').on('click', function (e) {
    e.preventDefault();
    closeVideoModal();
  });

  // Prevent modal content click from closing modal
  $('.vod-player-modal-content').on('click', function (e) {
    e.stopPropagation();
  });

  // Escape key to close modal
  $(document).on('keydown', function (e) {
    if (e.keyCode === 27 && $modal.is(':visible')) { // ESC key
      closeVideoModal();
    }
  });

  function openVideoModal(vodId, mpdUrl, poster, title) {
    // Set modal title
    $modalTitle.text(title || 'Lecture Vidéo');

    // Clear previous player
    if (currentPlayer) {
      currentPlayer.destroy();
      currentPlayer = null;
    }

    // Create video element
    var playerId = 'vod-player-' + vodId;
    var videoElement = '<video id="' + playerId + '" controls';

    if (poster) {
      videoElement += ' poster="' + poster + '"';
    }

    videoElement += ' style="width: 100%; height: 100%;">';
    videoElement += 'Votre navigateur ne supporte pas la lecture de vidéos HTML5.';
    videoElement += '</video>';

    $playerContainer.html(videoElement);

    // Show modal
    $modal.show();
    $('body').addClass('modal-open');

    // Initialize DashJS player
    loadDashJSAndInitPlayer(playerId, mpdUrl);
  }

  function closeVideoModal() {
    // Hide modal
    $modal.hide();
    $('body').removeClass('modal-open');

    // Destroy player
    if (currentPlayer) {
      currentPlayer.destroy();
      currentPlayer = null;
    }

    // Clear player container
    $playerContainer.empty();
  }

  function loadDashJSAndInitPlayer(playerId, mpdUrl) {
    // Check if DashJS is already loaded
    if (typeof dashjs !== 'undefined') {
      initPlayer(playerId, mpdUrl);
    } else {
      // Load DashJS dynamically
      var script = document.createElement('script');
      script.src = 'https://cdn.dashjs.org/latest/dash.all.min.js';
      script.onload = function () {
        initPlayer(playerId, mpdUrl);
      };
      script.onerror = function () {
        console.error('Failed to load DashJS library');
        alert('Erreur lors du chargement du lecteur vidéo. Veuillez réessayer.');
      };
      document.head.appendChild(script);
    }
  }

  function initPlayer(playerId, mpdUrl) {
    try {
      var videoElement = document.getElementById(playerId);

      if (!videoElement) {
        console.error('Video element not found:', playerId);
        return;
      }

      currentPlayer = dashjs.MediaPlayer().create();
      currentPlayer.initialize(videoElement, mpdUrl, false); // false = no autoplay

      // Add event listeners for better user experience
      currentPlayer.on(dashjs.MediaPlayer.events.STREAM_INITIALIZED, function () {
        console.log('Stream initialized successfully');
      });

      currentPlayer.on(dashjs.MediaPlayer.events.ERROR, function (e) {
        console.error('DashJS Error:', e);
        alert('Erreur lors de la lecture de la vidéo: ' + (e.error ? e.error.message : 'Erreur inconnue'));
      });

    } catch (error) {
      console.error('Error initializing player:', error);
      alert('Erreur lors de l\'initialisation du lecteur vidéo.');
    }
  }

  // Test API logging functionality
  $('#test-api-logging').on('click', function (e) {
    e.preventDefault();

    console.log('VOD Eikon: Test API logging button clicked');

    var $button = $(this);
    var originalText = $button.text();

    // Disable button and show loading state
    $button.prop('disabled', true).text('Test en cours...');

    $.ajax({
      url: vodEikon.ajax_url,
      type: 'POST',
      data: {
        action: 'test_api_logging',
        nonce: vodEikon.nonce
      },
      success: function (response) {
        console.log('VOD Eikon: Test API logging response:', response);

        if (response.success) {
          alert('Test du logging API réussi ! Vérifiez les logs de débogage WordPress pour voir les entrées de log détaillées.');
        } else {
          alert('Erreur lors du test du logging API: ' + (response.data.message || 'Erreur inconnue'));
        }
      },
      error: function (xhr, status, error) {
        console.error('VOD Eikon: Test API logging AJAX error:', error);
        alert('Erreur AJAX lors du test du logging: ' + error);
      },
      complete: function () {
        // Re-enable button and restore original text
        $button.prop('disabled', false).text(originalText);
      }
    });
  });

  // Test callback endpoint functionality
  $('#test-callback-endpoint').on('click', function (e) {
    e.preventDefault();

    console.log('VOD Eikon: Test callback endpoint button clicked');

    var $button = $(this);
    var originalText = $button.text();

    // Disable button and show loading state
    $button.prop('disabled', true).text('Test en cours...');

    $.ajax({
      url: vodEikon.ajax_url,
      type: 'POST',
      data: {
        action: 'test_callback_endpoint',
        nonce: vodEikon.nonce
      },
      success: function (response) {
        console.log('VOD Eikon: Test callback endpoint response:', response);

        if (response.success) {
          alert('Test du callback endpoint réussi ! Vérifiez les logs de débogage WordPress pour voir les détails du test.\n\nURL du callback: ' + response.data.callback_url);
        } else {
          alert('Erreur lors du test du callback endpoint: ' + (response.data.message || 'Erreur inconnue'));
        }
      },
      error: function (xhr, status, error) {
        console.error('VOD Eikon: Test callback endpoint AJAX error:', error);
        alert('Erreur AJAX lors du test du callback: ' + error);
      },
      complete: function () {
        // Re-enable button and restore original text
        $button.prop('disabled', false).text(originalText);
      }
    });
  });
});
