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
    var title = $('#video-title').val().trim();
    var fileInput = $('#video-file')[0];

    console.log('VOD Eikon: Validating form - Title: ' + title);
    console.log('VOD Eikon: File input files count: ' + fileInput.files.length);

    if (!title) {
      console.log('VOD Eikon: Validation failed - no title');
      $status.html('<div class="notice notice-error inline"><p>Please enter a video title.</p></div>');
      return;
    }

    if (!fileInput.files.length) {
      console.log('VOD Eikon: Validation failed - no file selected');
      $status.html('<div class="notice notice-error inline"><p>Please select a video file.</p></div>');
      return;
    }

    var file = fileInput.files[0];
    var maxSize = 2 * 1024 * 1024 * 1024; // 2GB

    console.log('VOD Eikon: File details - Name: ' + file.name + ', Size: ' + file.size + ', Type: ' + file.type);

    if (file.size > maxSize) {
      console.log('VOD Eikon: Validation failed - file too large');
      $status.html('<div class="notice notice-error inline"><p>File size exceeds 2GB limit.</p></div>');
      return;
    }

    // Check file type
    var allowedTypes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska'];
    if (!allowedTypes.includes(file.type)) {
      console.log('VOD Eikon: Validation failed - invalid file type: ' + file.type);
      $status.html('<div class="notice notice-error inline"><p>Invalid file type. Please upload MP4, MOV, AVI, or MKV files only.</p></div>');
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
        $('.progress-text').text('Uploading... ' + Math.round(percentComplete) + '%');
      }
    });

    xhr.addEventListener('load', function () {
      console.log('VOD Eikon: Upload request completed with status: ' + xhr.status);
      console.log('VOD Eikon: Response text: ' + xhr.responseText);

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
          $status.html('<div class="notice notice-error inline"><p>Invalid response from server. Please try again.</p></div>');
        }
      } else {
        console.log('VOD Eikon: Upload failed with HTTP status: ' + xhr.status);
        $status.html('<div class="notice notice-error inline"><p>Upload failed. Please try again.</p></div>');
      }

      // Reset form state
      $progress.hide();
      $uploadBtn.show();
      $cancelBtn.hide();
      $form.find('input, textarea').prop('disabled', false);
      $('.progress-fill').css('width', '0%');
      $('.progress-text').text('Uploading... 0%');
    });

    xhr.addEventListener('error', function () {
      console.error('VOD Eikon: XMLHttpRequest error occurred');
      $status.html('<div class="notice notice-error inline"><p>Upload failed. Please check your connection and try again.</p></div>');

      // Reset form state
      $progress.hide();
      $uploadBtn.show();
      $cancelBtn.hide();
      $form.find('input, textarea').prop('disabled', false);
      $('.progress-fill').css('width', '0%');
      $('.progress-text').text('Uploading... 0%');
    });

    // Send the request
    console.log('VOD Eikon: Sending request to: ' + vodEikon.ajax_url);
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
    $('.progress-text').text('Uploading... 0%');
    $('#upload-status').html('<div class="notice notice-warning inline"><p>Upload cancelled.</p></div>');
  });

  // Sync videos functionality
  $('#sync-videos').on('click', function () {
    var $button = $(this);
    var $status = $('#sync-status');

    $button.prop('disabled', true).find('.dashicons').addClass('spin');
    $status.html('<span class="spinner is-active"></span> Synchronizing videos...');

    $.ajax({
      url: vodEikon.ajax_url,
      type: 'POST',
      data: {
        action: 'sync_vod_videos',
        nonce: vodEikon.nonce
      },
      success: function (response) {
        if (response.success) {
          $status.html('<span class="notice notice-success inline"><p>' + response.data.message + '</p></span>');

          // Reload the page to show updated videos
          setTimeout(function () {
            location.reload();
          }, 2000);
        } else {
          $status.html('<span class="notice notice-error inline"><p>' + response.data.message + '</p></span>');
        }
      },
      error: function () {
        $status.html('<span class="notice notice-error inline"><p>An error occurred while synchronizing videos.</p></span>');
      },
      complete: function () {
        $button.prop('disabled', false).find('.dashicons').removeClass('spin');

        // Clear status message after 5 seconds
        setTimeout(function () {
          $status.empty();
        }, 5000);
      }
    });
  });

  // Debug API response functionality
  $('#debug-api').on('click', function () {
    var $button = $(this);
    var $status = $('#sync-status');

    $button.prop('disabled', true).find('.dashicons').addClass('spin');
    $status.html('<span class="spinner is-active"></span> Debugging API response...');

    $.ajax({
      url: vodEikon.ajax_url,
      type: 'POST',
      data: {
        action: 'debug_vod_api',
        nonce: vodEikon.nonce
      },
      success: function (response) {
        if (response.success) {
          $status.html('<span class="notice notice-success inline"><p>' + response.data.message + '</p></span>');
        } else {
          $status.html('<span class="notice notice-error inline"><p>' + response.data.message + '</p></span>');
        }
      },
      error: function () {
        $status.html('<span class="notice notice-error inline"><p>An error occurred while debugging API.</p></span>');
      },
      complete: function () {
        $button.prop('disabled', false).find('.dashicons').removeClass('spin');

        // Clear status message after 5 seconds
        setTimeout(function () {
          $status.empty();
        }, 5000);
      }
    });
  });

  // Delete video functionality
  $('.delete-video').on('click', function () {
    var $button = $(this);
    var videoId = $button.data('video-id');
    var $row = $button.closest('tr');

    if (!confirm('Are you sure you want to delete this video from the local database?')) {
      return;
    }

    $button.prop('disabled', true).text('Deleting...');

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
          alert('Failed to delete video: ' + response.data.message);
          $button.prop('disabled', false).text('Delete');
        }
      },
      error: function () {
        alert('An error occurred while deleting the video.');
        $button.prop('disabled', false).text('Delete');
      }
    });
  });

  // Copy MPD URL to clipboard
  $('.vod-eikon-videos').on('click', 'code', function () {
    var text = $(this).text();

    if (navigator.clipboard) {
      navigator.clipboard.writeText(text).then(function () {
        // Create temporary tooltip
        var $tooltip = $('<span class="vod-copy-tooltip">Copied!</span>');
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

      var $tooltip = $('<span class="vod-copy-tooltip">Copied!</span>');
      $(this).after($tooltip);

      setTimeout(function () {
        $tooltip.fadeOut(function () {
          $tooltip.remove();
        });
      }, 2000);
    }
  });

  // Test AJAX functionality
  $('#test-ajax').on('click', function () {
    var $button = $(this);
    var $status = $('#sync-status');

    console.log('VOD Eikon: Testing AJAX connectivity...');
    $button.prop('disabled', true);
    $status.html('<span class="spinner is-active"></span> Testing AJAX...');

    $.ajax({
      url: vodEikon.ajax_url,
      type: 'POST',
      data: {
        action: 'test_vod_ajax',
        nonce: vodEikon.nonce
      },
      success: function (response) {
        console.log('VOD Eikon: AJAX test response: ', response);
        if (response.success) {
          $status.html('<span class="notice notice-success inline"><p>AJAX test successful!</p></span>');
        } else {
          $status.html('<span class="notice notice-error inline"><p>AJAX test failed!</p></span>');
        }
        $button.prop('disabled', false);
        setTimeout(function () {
          $status.empty();
        }, 3000);
      },
      error: function (xhr, status, error) {
        console.error('VOD Eikon: AJAX test error: ', error);
        $status.html('<span class="notice notice-error inline"><p>AJAX connection failed!</p></span>');
        $button.prop('disabled', false);
        setTimeout(function () {
          $status.empty();
        }, 3000);
      }
    });
  });
});
