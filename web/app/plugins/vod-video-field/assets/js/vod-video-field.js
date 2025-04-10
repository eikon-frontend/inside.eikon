(function ($) {
  'use strict';

  /**
   * Debug function to log messages to console
   */
  function debug(message, data) {
    if (console && console.log) {
      console.log('ACF VOD Video -', message, data || '');
    }
  }

  /**
   * Helper function to find the input element using consistent selectors
   * @param {jQuery} $field The field element to search within
   * @return {jQuery|null} The found input element or null
   */
  function findInputElement($field) {
    debug('Finding input element', {
      field_id: $field.attr('id'),
      field_class: $field.attr('class'),
      field_data_type: $field.data('type'),
      field_data_key: $field.data('key')
    });

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
        debug('Found input using selector', {
          selector: selector,
          input_name: $result.attr('name'),
          input_id: $result.attr('id'),
          input_value: $result.val()
        });
        $input = $result;
        break;
      }
    }

    // If still not found, try searching in parent container (for flexible content fields)
    if (!$input || !$input.length) {
      const $parent = $field.closest('.acf-field, .acf-fields, .acf-row');
      if ($parent.length && !$parent.is($field)) {
        debug('Trying parent container search', {
          parent_id: $parent.attr('id'),
          parent_class: $parent.attr('class')
        });

        for (const selector of selectors) {
          const $result = $parent.find(selector);
          if ($result.length) {
            debug('Found input in parent container', {
              selector: selector,
              input_name: $result.attr('name'),
              input_id: $result.attr('id'),
              input_value: $result.val()
            });
            $input = $result;
            break;
          }
        }
      }
    }

    if (!$input || !$input.length) {
      // Detailed error reporting for debugging
      debug('Error: Input element not found', {
        field_id: $field.attr('id'),
        field_class: $field.attr('class'),
        field_data: {
          type: $field.data('type'),
          key: $field.data('key'),
          name: $field.data('name')
        },
        field_html_sample: $field.html().substring(0, 300) + '...',
        field_structure: $field.children().map(function () {
          return {
            tag: this.tagName,
            class: $(this).attr('class'),
            id: $(this).attr('id')
          };
        }).get(),
        selectors_tried: selectors
      });
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
      debug('Error: $field is not a valid jQuery object', $field);
      return;
    }

    debug('Initializing field', {
      field_id: $field.attr('id'),
      field_class: $field.attr('class')
    });

    // Add detailed DOM structure debugging
    debug('Field DOM structure', {
      data_type: $field.data('type'),
      data_key: $field.data('key'),
      dom_path: $field.parents().map(function () { return this.tagName + (this.id ? '#' + this.id : ''); }).get().reverse().join(' > '),
      outer_html_sample: $field.prop('outerHTML').substring(0, 300) + '...'
    });

    // Find the container within the field
    const $container = $field.find('.vod-video-container');
    if (!$container.length) {
      debug('Error: Container not found', $field);
      return;
    }

    // Get field elements using the helper function
    const $input = findInputElement($field);
    const $modal = $container.find('.vod-video-modal');
    const $searchInput = $modal.find('.vod-video-search-input');
    const $results = $modal.find('.vod-video-results');
    const $selectButton = $container.find('.vod-video-button');

    // Debug field elements
    debug('Field elements', {
      input_exists: $input.length > 0,
      input_name: $input.attr('name'),
      input_value: $input.val(),
      input_structure: $input.length ? {
        parent_class: $input.parent().attr('class'),
        input_html: $input.prop('outerHTML'),
        data_key: $input.data('key'),
        data_name: $input.data('name')
      } : 'input not found',
      modal_exists: $modal.length > 0,
      results_exists: $results.length > 0
    });

    // Verify input element exists
    if (!$input) {
      debug('Error: Cannot initialize field without input element');
      return;
    }

    /**
     * Initialize all event handlers
     */
    function initializeEvents() {
      // Select video button
      $selectButton.on('click', function (e) {
        e.preventDefault();
        debug('Select button clicked');
        openModal();
      });

      // Modal close button
      $modal.find('.vod-video-modal-close').on('click', function (e) {
        e.preventDefault();
        debug('Close button clicked');
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
          url: $(this).data('url')
        };

        debug('Video selected', videoData);
        selectVideo(videoData);
      });

      // Remove video button
      $container.on('click', '.vod-video-remove', function (e) {
        e.preventDefault();
        debug('Remove button clicked');
        removeSelectedVideo();
      });

      // Debug form submission
      // Debug form submission with enhanced tracking
      $field.closest('form').on('submit', function () {
        // Get all inputs with names starting with acf[]
        const acfInputs = $(this).find('input[name^="acf"]').map(function () {
          return {
            name: $(this).attr('name'),
            value: $(this).val(),
            id: $(this).attr('id')
          };
        }).get();

        debug('Form submitting', {
          input_value: $input.val(),
          input_name: $input.attr('name'),
          field_key: $field.data('key'),
          form_action: $(this).attr('action'),
          all_acf_inputs: acfInputs,
          serialized_partial: $(this).serialize().substring(0, 200) + '...'
        });
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
      debug('Searching videos', term);

      $results.html('<div class="vod-video-loading">' + acf_vod_video_field.i18n.loading + '</div>');

      $.ajax({
        url: acf_vod_video_field.ajax_url,
        type: 'POST',
        data: {
          action: 'acf_vod_video_search',
          nonce: acf_vod_video_field.nonce,
          search: term
        },
        success: function (response) {
          debug('Search response', response);

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
          'data-url="' + video.url + '">';
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
      debug('Setting video value', videoData.id);

      // Use helper function to find input consistently
      const $fieldInput = findInputElement($field);
      if (!$fieldInput) {
        debug('Error: Cannot update field without input element');
        return;
      }

      // Debug input element before update
      debug('Input element pre-update', {
        input_name: $fieldInput.attr('name'),
        input_id: $fieldInput.attr('id'),
        current_value: $fieldInput.val(),
        field_id: $field.attr('id')
      });

      // Update hidden input
      $fieldInput.val(videoData.id);

      // Verify value was set correctly
      debug('Input value verification', {
        expected: videoData.id,
        actual: $fieldInput.val(),
        input_name: $fieldInput.attr('name'),
        input_id: $fieldInput.attr('id')
      });

      // Trigger change event for ACF - CRITICAL for saving
      $fieldInput.trigger('change');

      // Add a one-time change event listener to verify the change was processed
      $fieldInput.one('change', function () {
        debug('Change event fired on input', {
          element_id: $(this).attr('id'),
          name: $(this).attr('name'),
          value: $(this).val(),
          timestamp: new Date().toISOString()
        });
      });

      // Update preview
      updatePreview(videoData);

      // Close modal
      closeModal();
    }

    /**
     * Remove the selected video
     */
    function removeSelectedVideo() {
      debug('Removing video');

      // Use the helper function to find the input element
      const $fieldInput = findInputElement($field);
      if (!$fieldInput) {
        debug('Error: Cannot clear field without input element');
        return;
      }

      // Debug input element before clearing
      debug('Input element before clearing', {
        input_name: $fieldInput.attr('name'),
        input_id: $fieldInput.attr('id'),
        current_value: $fieldInput.val()
      });

      // Clear input and trigger change
      $fieldInput.val('').trigger('change');
      debug('Input value cleared');

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

    // Final verification of field initialization state
    debug('Field initialization complete', {
      field_id: $field.attr('id'),
      field_key: $field.data('key'),
      input_element: {
        found: $input.length > 0,
        name: $input.attr('name'),
        current_value: $input.val()
      },
      events_bound: true
    });
  }
  /**
   * Helper function to initialize all fields that exist in the DOM
   */
  function initializeExistingFields() {
    debug('Initializing existing fields');

    // Enhanced debugging of page field structure
    debug('Analyzing page structure', {
      all_acf_fields: $('.acf-field').length,
      acf_field_types: $('.acf-field').map(function () {
        return $(this).data('type');
      }).get(),
      potential_matches: {
        by_class: $('.acf-field-vod-video').length,
        by_data_type: $('[data-type="vod_video"]').length,
        by_combined: $('.acf-field[data-type="vod_video"]').length
      }
    });

    // Use multiple selectors to catch all possible ACF field structures
    const $fields = $('.acf-field-vod-video, .acf-field[data-type="vod_video"], div[data-type="vod_video"]');

    debug('Found fields to initialize', {
      count: $fields.length,
      field_details: $fields.map(function () {
        return {
          id: $(this).attr('id'),
          class: $(this).attr('class'),
          data_type: $(this).data('type'),
          has_input: $(this).find('input.vod-video-input').length > 0
        };
      }).get()
    });

    // Initialize each field
    $fields.each(function () {
      initVodVideoField($(this));
    });
  }

  // Initialize fields on document ready
  $(document).ready(function () {
    debug('Document ready - initializing fields');
    initializeExistingFields();
  });

  // ACF-specific initialization
  if (typeof acf !== 'undefined') {
    // Initialize when ACF is fully ready
    acf.addAction('ready', function () {
      debug('ACF ready event - initializing fields');
      initializeExistingFields();
    });

    // Initialize field when it's ready (individual field)
    acf.addAction('ready_field/type=vod_video', function ($field) {
      if (!$field || !$field.jquery) {
        debug('Error: $field is not a valid jQuery object in ready_field', $field);
        return;
      }
      debug('ACF field ready event', {
        field_id: $field.attr('id'),
        field_key: $field.data('key')
      });
      initVodVideoField($field);
    });

    // Initialize field when it's loaded (via AJAX)
    acf.addAction('load_field/type=vod_video', function ($field) {
      if (!$field || !$field.jquery) {
        debug('Error: $field is not a valid jQuery object in load_field', $field);
        return;
      }
      debug('ACF field load event', {
        field_id: $field.attr('id'),
        field_key: $field.data('key')
      });
      initVodVideoField($field);
    });

    // Initialize fields after append (for repeaters, flexible content, etc.)
    acf.addAction('append', function ($el) {
      debug('ACF append event', {
        container_id: $el.attr('id'),
        container_class: $el.attr('class')
      });

      $el.find('.acf-field-vod-video, .acf-field[data-type="vod_video"], div[data-type="vod_video"]').each(function () {
        initVodVideoField($(this));
      });
    });
  }

})(jQuery);
