(function ($) {
  'use strict';

  /**
   * Debug function to log messages to console
   */
  function debug(message, data) {
    // Removed debug functionality
  }

  /**
   * Helper function to find the input element using consistent selectors
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

      // Video selection handling
      $results.on('click', '.vod-video-result', function (e) {
        e.preventDefault();
        const videoData = {
          id: $(this).data('id'),
          title: $(this).data('title'),
          thumbnail: $(this).data('thumbnail'),
          url: $(this).data('url'),
          media: $(this).data('media'),
          folder: $(this).data('folder')
        };

        selectVideo(videoData);
      });

      // Remove video button
      $container.on('click', '.vod-video-remove', function (e) {
        e.preventDefault();
        removeSelectedVideo();
      });

      // Debug form submission
      $field.closest('form').on('submit', function () {
        // Get all inputs with names starting with acf[]
        const acfInputs = $(this).find('input[name^="acf"]').map(function () {
          return {
            name: $(this).attr('name'),
            value: $(this).val(),
            id: $(this).attr('id')
          };
        }).get();
      });
    } // End of initializeEvents

    /**
     * Open the video selection modal
     */
    function openModal() {
      $modal.addClass('is-open').show();
      $('body').css('overflow', 'hidden');  // Prevent body scrolling
      $searchInput.val('').focus();
      searchVideos('');
    }

    /**
     * Close the video selection modal
     */
    function closeModal() {
      $modal.removeClass('is-open').hide();
      $('body').css('overflow', '');  // Restore body scrolling
      $results.empty();
    }

    /**
     * Search for videos
     */
    function searchVideos(term) {
      $.ajax({
        url: acf_vod_video_field.ajax_url,
        type: 'POST',
        data: {
          action: 'acf_vod_video_search',
          nonce: acf_vod_video_field.nonce,
          search: term
        },
        success: function (response) {
          if (response.success && response.data.videos) {
            displayResults(response.data.videos);
          } else {
            $results.html('<div class="vod-video-error">' + acf_vod_video_field.i18n.error + '</div>');
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
      if (!videos.length) {
        $results.html('<div class="vod-video-no-results">' + acf_vod_video_field.i18n.no_videos_found + '</div>');
        return;
      }

      let html = '<div class="vod-video-grid">';
      videos.forEach(function (video) {
        html += '<div class="vod-video-item" ' +
          'data-id="' + video.id + '" ' +
          'data-title="' + video.title + '" ' +
          'data-thumbnail="' + video.thumbnail + '" ' +
          'data-url="' + video.url + '" ' +
          'data-media="' + video.id + '" ' +
          'data-folder="' + video.folder + '">';
        html += '<div class="vod-video-item-thumbnail">';
        if (video.thumbnail) {
          html += '<img src="' + video.thumbnail + '" alt="' + video.title + '">';
        } else {
          html += '<div class="vod-video-placeholder"></div>';
        }
        html += '</div>';
        html += '<div class="vod-video-item-title">' + video.title + '</div>';
        html += '</div>';
      });
      html += '</div>';

      $results.html(html);

      // Add click handler for video selection
      $results.find('.vod-video-item').on('click', function (e) {
        e.preventDefault();
        const $item = $(this);
        const videoData = {
          id: $item.data('id'),
          title: $item.data('title'),
          thumbnail: $item.data('thumbnail'),
          url: $item.data('url'),
          media: $item.data('media'),
          folder: $item.data('folder')
        };
        selectVideo(videoData);
      });
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

      // Ensure all required data is present
      if (!videoData.id || !videoData.title) {
        console.error('Missing required video data');
        return;
      }

      // Structure the video data
      const valueToStore = {
        id: {
          media: videoData.media || videoData.id,
          thumbnail: videoData.thumbnail || '',
          url: videoData.url || '',
          folder: videoData.folder || ''
        },
        title: videoData.title
      };

      // Store as JSON string
      const jsonValue = JSON.stringify(valueToStore);

      // Set the input value and trigger events
      $fieldInput
        .val(jsonValue)
        .attr('value', jsonValue);

      // Trigger both the input change and ACF's own change event
      $fieldInput.trigger('change');
      acf.doAction('change', $field);

      // Update the preview immediately
      updatePreview({
        title: videoData.title,
        thumbnail: videoData.thumbnail,
        url: videoData.url
      });

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

      // Clear the input value completely
      $fieldInput.val('').attr('value', '');

      // Trigger all possible events for thorough update
      $fieldInput
        .trigger('input')
        .trigger('change')
        .trigger('blur');

      // Force update preview to empty state
      updatePreview(null);
    }

    /**
     * Update the video preview
     */
    function updatePreview(videoData) {
      const $currentPreview = $container.find('.vod-video-preview, .vod-video-empty');
      $currentPreview.remove();

      if (videoData && videoData.title) {
        let html = '<div class="vod-video-preview">';
        html += '<div class="vod-video-thumbnail">';
        if (videoData.thumbnail) {
          html += '<img src="' + videoData.thumbnail + '" alt="' + videoData.title + '">';
        } else {
          html += '<div class="vod-video-placeholder"></div>';
        }
        html += '</div>';
        html += '<div class="vod-video-details">';
        html += '<h4>' + videoData.title + '</h4>';
        html += '<div class="vod-video-actions">';
        html += '<a href="#" class="vod-video-remove button">' + acf_vod_video_field.i18n.remove_video + '</a>';
        html += '</div>';
        html += '</div>';
        html += '</div>';

        $container.find('.vod-video-select').before(html);
      } else {
        let html = '<div class="vod-video-empty">';
        html += '<p>' + acf_vod_video_field.i18n.no_video_selected + '</p>';
        html += '</div>';

        $container.find('.vod-video-select').before(html);
      }
    }

    // Initialize event handlers
    initializeEvents();
  }
  /**
   * Helper function to initialize all fields that exist in the DOM
   */
  function initializeExistingFields() {
    // Use multiple selectors to catch all possible ACF field structures
    const $fields = $('.acf-field-vod-video, .acf-field[data-type="vod_video"], div[data-type="vod_video"]');

    // Initialize each field
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
