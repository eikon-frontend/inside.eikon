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
    // Try selectors in order of specificity
    const selectors = [
      '> .acf-input > input.vod-video-input',               // Standard ACF structure
      '> input.vod-video-input',                            // Direct child
      '.acf-input > input.vod-video-input',                 // Non-direct ACF structure
      '.acf-input input.vod-video-input',                   // Nested in ACF input
      'input.vod-video-input[name^="acf["]',                // By ACF name format
      'input.vod-video-input',                              // Any descendant (last resort)
      'input[name^="acf["][data-key]',                      // Any ACF input with data-key
      'input[type="hidden"][name^="acf["]'                  // Any hidden ACF input
    ];

    let $input = null;
    for (const selector of selectors) {
      const $result = $field.find(selector);
      if ($result.length) {
        $input = $result;
        break;
      }
    }

    // If still not found, try searching in parent container (for flexible content fields)
    if (!$input || !$input.length) {
      const $parent = $field.closest('.acf-field, .acf-fields, .acf-row');
      if ($parent.length && !$parent.is($field)) {
        for (const selector of selectors) {
          const $result = $parent.find(selector);
          if ($result.length) {
            $input = $result;
            break;
          }
        }
      }
    }

    if (!$input || !$input.length) {
      return null;
    }

    return $input;
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
      $modal.on('click', function (e) {
        if ($(e.target).is($modal)) {
          closeModal();
        }
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
      $modal.show();
      $searchInput.val('').focus();
      searchVideos('');
    }

    /**
     * Close the video selection modal
     */
    function closeModal() {
      $modal.hide();
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

      let html = '';
      videos.forEach(function (video) {
        html += '<div class="vod-video-result" ' +
          'data-id="' + video.id + '" ' +
          'data-title="' + video.title + '" ' +
          'data-thumbnail="' + video.thumbnail + '" ' +
          'data-media="' + video.media + '" ' +
          'data-url="' + video.url + '" ' +
          'data-folder="' + video.folder + '">'; // Add folder attribute
        html += '<div class="vod-video-thumbnail">';
        if (video.thumbnail) {
          html += '<img src="' + video.thumbnail + '" alt="' + video.title + '">';
        } else {
          html += '<div class="vod-video-placeholder"></div>';
        }
        html += '</div>';
        html += '<div class="vod-video-title">' + video.title + '</div>';
        html += '</div>';
      });

      $results.html(html);
    }

    /**
     * Select a video and update the field
     */
    function selectVideo(videoData) {
      // Use helper function to find input consistently
      const $fieldInput = findInputElement($field);
      if (!$fieldInput) {
        return;
      }

      // Update hidden input
      $fieldInput.val(videoData.id);

      // Trigger change event for ACF - CRITICAL for saving
      $fieldInput.trigger('change');

      // Update preview
      updatePreview(videoData);

      // Close modal
      closeModal();
    }

    /**
     * Remove the selected video
     */
    function removeSelectedVideo() {
      // Use the helper function to find the input element
      const $fieldInput = findInputElement($field);
      if (!$fieldInput) {
        return;
      }

      // Clear input and trigger change
      $fieldInput.val('').trigger('change');

      // Update preview
      updatePreview(null);
    }

    /**
     * Update the video preview
     */
    function updatePreview(videoData) {
      const $currentPreview = $container.find('.vod-video-preview, .vod-video-empty');
      $currentPreview.remove();

      if (videoData) {
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
        html += '<p>' + acf_vod_video_field.i18n.folder + ': ' + (videoData.folder || '-') + '</p>'; // Display folder attribute
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
