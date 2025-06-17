(function ($) {
  'use strict';

  /**
   * Helper function to find the input element using consis      // Refresh button click
      $container.on('click', '.vod-video-refresh', function (e) {
        e.preventDefault();
        refreshSelectedVideo();
      });
    }ors
   * @param {jQuery} $field The field element to search within
   * @return {jQuery|null} The found input element or null
   */
  function findInputElement($field) {
    // First try to find input within the direct field container
    let $input = $field.find('> .acf-input > input.vod-video-input');

    if (!$input.length) {
      // If not found, check if we're in a repeater/flexible content
      const $parent = $field.closest('.acf-fields, .acf-field');
      if ($parent.length) {
        $input = $parent.find('input.vod-video-input[name^="acf"]').first();
      }
    }

    return $input.length ? $input : null;
  }

  /**
   * Initialize the VOD Video field
   *
   * @param {jQuery} $field The field element
   */
  function initVodVideoField($field) {
    if (!$field || !$field.jquery) {
      return;
    }

    // Find the container within the field
    const $container = $field.find('.vod-video-container');
    if (!$container.length) {
      return;
    }

    // Get field elements using the helper function
    const $input = findInputElement($field);
    const $modal = $container.find('.vod-video-modal');
    const $searchInput = $modal.find('.vod-video-search-input');
    const $results = $modal.find('.vod-video-results');
    const $selectButton = $container.find('.vod-video-button');

    // Verify input element exists
    if (!$input) {
      return;
    }

    /**
     * Initialize all event handlers
     */
    function initializeEvents() {
      // Select video button
      $selectButton.on('click', function (e) {
        e.preventDefault();
        openModal();
      });

      // Modal close button
      $modal.find('.vod-video-modal-close').on('click', function (e) {
        e.preventDefault();
        closeModal();
      });

      // Click outside modal to close
      $(document).on('click', function (e) {
        if ($(e.target).closest('.vod-video-modal-content').length === 0 &&
          $(e.target).closest('.vod-video-button').length === 0 &&
          $modal.is(':visible')) {
          closeModal();
        }
      });

      // Prevent modal content clicks from bubbling
      $modal.find('.vod-video-modal-content').on('click', function (e) {
        e.stopPropagation();
      });

      // Search input handling
      let searchTimeout;
      $searchInput.on('input', function () {
        clearTimeout(searchTimeout);
        const searchTerm = $(this).val();

        searchTimeout = setTimeout(function () {
          searchVideos(searchTerm);
        }, 500);
      });

      // Remove video button
      $container.on('click', '.vod-video-remove', function (e) {
        e.preventDefault();
        removeSelectedVideo();
      });

      // Refresh video button
      $container.on('click', '.vod-video-refresh', function (e) {
        e.preventDefault();
        refreshSelectedVideo();
      });
    }

    /**
     * Open the video selection modal
     */
    function openModal() {
      $modal.addClass('is-open').show();
      $('body').css('overflow', 'hidden');
      $searchInput.val('').focus();
      searchVideos('');
    }

    /**
     * Close the video selection modal
     */
    function closeModal() {
      $modal.removeClass('is-open').hide();
      $('body').css('overflow', '');
      $results.empty();
    }

    /**
     * Search for videos
     */
    function searchVideos(term) {
      $results.html('<div class="vod-video-loading">' + acf_vod_video_field.i18n.loading + '</div>');

      // Get the published_only setting from the field data
      const $acfInput = $field.find('.acf-input');
      const publishedOnly = $acfInput.data('published-only') !== undefined ? $acfInput.data('published-only') : true;

      $.ajax({
        url: acf_vod_video_field.ajax_url,
        type: 'POST',
        data: {
          action: 'acf_vod_video_search',
          nonce: acf_vod_video_field.nonce,
          search: term,
          published_only: publishedOnly
        },
        success: function (response) {
          if (response.success && response.data.videos) {
            displayResults(response.data.videos);
          } else {
            $results.html('<div class="vod-video-error">' + (response.data?.message || acf_vod_video_field.i18n.error) + '</div>');
          }
        },
        error: function () {
          $results.html('<div class="vod-video-error">' + acf_vod_video_field.i18n.error + '</div>');
        }
      });
    }

    /**
     * Display search results
     */
    function displayResults(videos) {
      $results.empty();

      if (!videos || videos.length === 0) {
        $results.html('<div class="vod-video-no-results">' + acf_vod_video_field.i18n.no_videos_found + '</div>');
        return;
      }

      const $grid = $('<div class="vod-video-grid"></div>');

      videos.forEach(function (video) {
        const $item = $('<div class="vod-video-item" data-video-id="' + video.id + '"></div>');

        // Thumbnail
        const $thumbnail = $('<div class="vod-video-item-thumbnail"></div>');
        if (video.poster) {
          $thumbnail.html('<img src="' + video.poster + '" alt="' + video.title + '">');
        } else {
          $thumbnail.html('<div class="vod-video-placeholder"><span class="dashicons dashicons-format-video"></span></div>');
        }

        // Details
        const $details = $('<div class="vod-video-item-details"></div>');
        $details.html('<h4>' + video.title + '</h4>');
        if (video.vod_id) {
          $details.append('<p><small>VOD ID: ' + video.vod_id + '</small></p>');
        }

        $item.append($thumbnail);
        $item.append($details);

        // Click handler
        $item.on('click', function () {
          selectVideo(video);
        });

        $grid.append($item);
      });

      $results.append($grid);
    }

    /**
     * Select a video and update the field
     */
    function selectVideo(videoData) {
      const $fieldInput = findInputElement($field);
      if (!$fieldInput) {
        console.error('Could not find input element');
        return;
      }

      // Store the video data as JSON
      const jsonValue = JSON.stringify(videoData);

      // Set the input value and trigger events
      $fieldInput
        .val(jsonValue)
        .attr('value', jsonValue)
        .trigger('change');

      // Trigger ACF's change event
      if (typeof acf !== 'undefined') {
        acf.doAction('change', $field);
      }

      // Update the preview
      updatePreview(videoData);

      // Close modal
      closeModal();
    }

    /**
     * Remove the selected video
     */
    function removeSelectedVideo() {
      const $fieldInput = findInputElement($field);
      if (!$fieldInput) {
        return;
      }

      // Clear the input value
      $fieldInput
        .val('')
        .attr('value', '')
        .trigger('change');

      // Trigger ACF's change event
      if (typeof acf !== 'undefined') {
        acf.doAction('change', $field);
      }

      // Update preview to empty state
      updatePreview(null);
    }

    /**
     * Refresh the selected video with fresh data from database
     */
    function refreshSelectedVideo() {
      const $fieldInput = findInputElement($field);
      if (!$fieldInput) {
        return;
      }

      const currentValue = $fieldInput.val();
      if (!currentValue) {
        return;
      }

      // Parse current video data to get VOD ID
      let currentVideoData;
      try {
        currentVideoData = JSON.parse(currentValue);
      } catch (e) {
        console.error('Error parsing current video data:', e);
        return;
      }

      const vodId = currentVideoData.vod_id || currentVideoData.id;
      if (!vodId) {
        console.error('No VOD ID found in current video data');
        return;
      }

      // Show loading state
      const $refreshBtn = $container.find('.vod-video-refresh');
      const originalText = $refreshBtn.text();
      $refreshBtn.text(acf_vod_video_field.i18n.loading).prop('disabled', true);

      // Make AJAX request to refresh video data
      $.ajax({
        url: acf_vod_video_field.ajax_url,
        type: 'POST',
        data: {
          action: 'acf_vod_video_refresh',
          nonce: acf_vod_video_field.nonce,
          vod_id: vodId
        },
        success: function (response) {
          if (response.success && response.data.video) {
            // Update field with fresh data
            const jsonValue = JSON.stringify(response.data.video);

            $fieldInput
              .val(jsonValue)
              .attr('value', jsonValue)
              .trigger('change');

            // Trigger ACF's change event
            if (typeof acf !== 'undefined') {
              acf.doAction('change', $field);
            }

            // Update the preview
            updatePreview(response.data.video);

            // Show success message
            showNotification(acf_vod_video_field.i18n.refresh_success, 'success');

            // Log for debugging
            console.log('VOD Video refreshed successfully:', response.data.video);
          } else {
            console.error('VOD Video refresh failed:', response.data?.message);
            showNotification(response.data?.message || acf_vod_video_field.i18n.refresh_error, 'error');
          }
        },
        error: function () {
          showNotification(acf_vod_video_field.i18n.refresh_error, 'error');
        },
        complete: function () {
          // Restore button state
          $refreshBtn.text(originalText).prop('disabled', false);
        }
      });
    }

    /**
     * Update the video preview
     */
    function updatePreview(videoData) {
      const $currentPreview = $container.find('.vod-video-preview, .vod-video-empty');
      $currentPreview.remove();

      if (videoData && videoData.title) {
        const $preview = $('<div class="vod-video-preview"></div>');

        // Create thumbnail
        const $thumbnail = $('<div class="vod-video-thumbnail"></div>');
        if (videoData.poster) {
          const $img = $('<img>')
            .attr('src', videoData.poster)
            .attr('alt', videoData.title)
            .on('error', function () {
              $(this).parent().html('<div class="vod-video-placeholder vod-video-error"><span class="dashicons dashicons-format-video"></span><small>Image indisponible</small></div>');
              $preview.addClass('vod-video-stale-image');
              showNotification('L\'image de la vidéo semble être obsolète. Utilisez le bouton "Actualiser" pour mettre à jour.', 'info');
            });
          $thumbnail.append($img);
        } else {
          $thumbnail.html('<div class="vod-video-placeholder"><span class="dashicons dashicons-format-video"></span></div>');
        }

        // Create details
        const $details = $('<div class="vod-video-details"></div>');
        $details.append('<h4>' + $('<div>').text(videoData.title).html() + '</h4>');

        if (videoData.vod_id) {
          $details.append('<p><small>VOD ID: ' + $('<div>').text(videoData.vod_id).html() + '</small></p>');
        }

        // Create actions
        const $actions = $('<div class="vod-video-actions"></div>');
        const $removeBtn = $('<a href="#" class="vod-video-remove button"><span class="dashicons dashicons-trash"></span> ' + acf_vod_video_field.i18n.remove_video + '</a>');
        const $refreshBtn = $('<a href="#" class="vod-video-refresh button" style="margin-left: 5px;"><span class="dashicons dashicons-update"></span> ' + acf_vod_video_field.i18n.refresh_video + '</a>');

        $actions.append($removeBtn).append($refreshBtn);
        $details.append($actions);

        // Assemble preview
        $preview.append($thumbnail).append($details);

        // Insert before select button
        $container.find('.vod-video-select').before($preview);
      } else {
        const $empty = $('<div class="vod-video-empty"><p>Aucune vidéo sélectionnée</p></div>');
        $container.find('.vod-video-select').before($empty);
      }
    }

    /**
     * Show a notification message
     */
    function showNotification(message, type = 'info') {
      // Remove any existing notifications
      $('.vod-video-notification').remove();

      // Create notification element
      const $notification = $('<div class="vod-video-notification vod-video-notification-' + type + '">' + message + '</div>');

      // Add to container
      $container.prepend($notification);

      // Auto-hide after 3 seconds
      setTimeout(function () {
        $notification.fadeOut(300, function () {
          $(this).remove();
        });
      }, 3000);
    }

    // Initialize event handlers
    initializeEvents();
  }

  /**
   * Helper function to initialize all fields that exist in the DOM
   */
  function initializeExistingFields() {
    const $fields = $('.acf-field-vod-video, .acf-field[data-type="vod_video"], div[data-type="vod_video"]');
    $fields.each(function () {
      initVodVideoField($(this));
    });
  }

  // Initialize fields on document ready
  $(document).ready(function () {
    initializeExistingFields();
  });

  // ACF-specific initialization
  if (typeof acf !== 'undefined') {
    // Initialize when ACF is fully ready
    acf.addAction('ready', function () {
      initializeExistingFields();
    });

    // Initialize field when it's ready (individual field)
    acf.addAction('ready_field/type=vod_video', function ($field) {
      if (!$field || !$field.jquery) {
        return;
      }
      initVodVideoField($field);
    });

    // Initialize field when it's loaded (via AJAX)
    acf.addAction('load_field/type=vod_video', function ($field) {
      if (!$field || !$field.jquery) {
        return;
      }
      initVodVideoField($field);
    });

    // Initialize fields after append (for repeaters, flexible content, etc.)
    acf.addAction('append', function ($el) {
      $el.find('.acf-field-vod-video, .acf-field[data-type="vod_video"], div[data-type="vod_video"]').each(function () {
        initVodVideoField($(this));
      });
    });
  }

})(jQuery);
