jQuery(document).ready(function ($) {

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
});
