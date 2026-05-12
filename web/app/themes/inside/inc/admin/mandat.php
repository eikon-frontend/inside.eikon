<?php

function eikon_add_mandat_description_label()
{
  $post = get_post();

  if (!$post || $post->post_type !== 'mandat') {
    return;
  }

  echo '<div style="margin-top: 16px; margin-bottom: 0; padding: 0;">';
  echo '<label style="display: block; font-weight: 600; font-size: 16px; margin-bottom: 4px;">Description du mandat</label>';
  echo '<p style="margin: 0; font-size: 12px; color: #6b7280;">Décrivez le brief en quelques paragraphes.</p>';
  echo '</div>';
}
add_action('edit_form_after_title', 'eikon_add_mandat_description_label', 10);

function eikon_render_current_mandat_selector()
{
  $post = get_post();

  if (!$post || $post->post_type !== 'project') {
    return;
  }

  $mandat_id = (int) get_post_meta($post->ID, 'eikon_current_mandat_id', true);
  $mandat = $mandat_id ? get_post($mandat_id) : null;
  $mandat_title = $mandat instanceof WP_Post ? $mandat->post_title : '';
  $mandat_summary = $mandat instanceof WP_Post ? eikon_get_mandat_summary($mandat->ID) : '';
  $nonce = wp_create_nonce('eikon-search-mandats');
  $current_years = wp_get_post_terms($post->ID, 'year', array('fields' => 'ids'));
  $current_sections = wp_get_post_terms($post->ID, 'section', array('fields' => 'ids'));
  $current_subjects = wp_get_post_terms($post->ID, 'subjects', array('fields' => 'ids'));
  $taxonomy_filters = array(
    'year' => array_map('intval', is_array($current_years) ? $current_years : array()),
    'section' => array_map('intval', is_array($current_sections) ? $current_sections : array()),
    'subjects' => array_map('intval', is_array($current_subjects) ? $current_subjects : array()),
  );
?>
  <div style="margin: 12px 0 0 0; padding: 0; border: 0; background: transparent; position: relative;">
    <label for="eikon_current_mandat_search" style="display: block; font-weight: 600; font-size: 16px; margin: 0 0 4px 0;">Mandat courant</label>
    <p style="margin: 0 0 8px 0; font-size: 12px; color: #6b7280;">Si ce projet a été fait dans le cadre d'un mandat, spécifiez ici Choisissez un mandat en cours pour ce projet.</p>
    <input type="hidden" id="eikon_current_mandat_id" name="eikon_current_mandat_id" value="<?php echo esc_attr($mandat_id); ?>">
    <div style="display: flex; align-items: stretch; gap: 0; position: relative;">
      <div style="position: relative; flex: 1 1 auto; min-width: 0; width: 100%;">
        <input
          type="text"
          id="eikon_current_mandat_search"
          value="<?php echo esc_attr($mandat_title); ?>"
          autocomplete="off"
          placeholder="Rechercher un mandat en cours..."
          style="width: 100%; box-sizing: border-box; padding: 10px 40px 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
        <button type="button" id="eikon_current_mandat_clear" aria-label="Retirer le mandat courant" style="position: absolute; top: 50%; right: 10px; transform: translateY(-50%); width: 24px; height: 24px; border: 0; border-radius: 999px; background: transparent; color: #64748b; cursor: pointer; font-size: 18px; line-height: 1; display: <?php echo $mandat_id > 0 ? 'flex' : 'none'; ?>; align-items: center; justify-content: center;">&times;</button>
      </div>
    </div>
    <p id="eikon_current_mandat_meta" style="margin: 8px 0 0 0; font-size: 12px; color: #6b7280; display: <?php echo '' !== $mandat_summary ? 'block' : 'none'; ?>;"><?php echo esc_html($mandat_summary); ?></p>
    <div id="eikon_current_mandat_results" style="position: absolute; z-index: 20; top: calc(100% + 4px); left: 0; right: 0; background: #fff; border: 1px solid #cbd5e1; border-radius: 6px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12); display: none; max-height: 260px; overflow-y: auto;"></div>
    <script>
      (function() {
        const searchInput = document.getElementById('eikon_current_mandat_search');
        const hiddenInput = document.getElementById('eikon_current_mandat_id');
        const metaLine = document.getElementById('eikon_current_mandat_meta');
        const resultsBox = document.getElementById('eikon_current_mandat_results');
        const clearButton = document.getElementById('eikon_current_mandat_clear');
        const ajaxUrl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
        const nonce = '<?php echo esc_js($nonce); ?>';
        const projectId = <?php echo (int) $post->ID; ?>;
        const taxonomyFilters = <?php echo wp_json_encode($taxonomy_filters); ?>;
        let timer = null;

        const hideResults = () => {
          resultsBox.style.display = 'none';
          resultsBox.innerHTML = '';
        };

        const syncClearButton = () => {
          clearButton.style.display = hiddenInput.value ? 'flex' : 'none';
        };

        const syncMetaLine = (meta) => {
          metaLine.textContent = meta || '';
          metaLine.style.display = meta ? 'block' : 'none';
        };

        const setSelection = (id, title, meta) => {
          hiddenInput.value = id || '';
          searchInput.value = title || '';
          syncClearButton();
          syncMetaLine(meta || '');
          hideResults();
        };

        const escapeHtml = (value) => String(value)
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#039;');

        const renderResults = (items) => {
          if (!items.length) {
            resultsBox.innerHTML = '<div style="padding: 10px 12px; color: #6b7280; font-size: 13px;">Aucun mandat trouvé.</div>';
            resultsBox.style.display = 'block';
            return;
          }

          resultsBox.innerHTML = items.map((item) => {
            const rawTitle = String(item.title || '');
            const title = escapeHtml(rawTitle);
            const encodedTitle = encodeURIComponent(rawTitle);
            const rawMeta = String(item.meta || '');
            const meta = rawMeta ? `<div style="font-size: 12px; color: #6b7280; margin-top: 2px;">${escapeHtml(rawMeta)}</div>` : '';
            const encodedMeta = encodeURIComponent(rawMeta);
            return `<button type="button" data-id="${escapeHtml(item.id)}" data-title="${encodedTitle}" data-meta="${encodedMeta}" style="display: block; width: 100%; text-align: left; padding: 10px 12px; border: 0; background: transparent; cursor: pointer; border-bottom: 1px solid #e2e8f0;"><div style="font-weight: 600; color: #0f172a;">${title}</div>${meta}</button>`;
          }).join('');
          resultsBox.style.display = 'block';
        };

        const fetchMandats = (term) => {
          const params = new URLSearchParams({
            action: 'eikon_search_mandats',
            nonce,
            term,
            project_id: String(projectId),
          });

          params.append('year_ids', (taxonomyFilters.year || []).join(','));
          params.append('section_ids', (taxonomyFilters.section || []).join(','));
          params.append('subjects_ids', (taxonomyFilters.subjects || []).join(','));

          fetch(ajaxUrl + '?' + params.toString(), {
              credentials: 'same-origin',
            })
            .then((response) => response.json())
            .then((response) => {
              if (!response || !response.success) {
                renderResults([]);
                return;
              }
              renderResults(response.data || []);
            })
            .catch(() => {
              renderResults([]);
            });
        };

        searchInput.addEventListener('input', function() {
          const term = this.value.trim();
          hiddenInput.value = '';
          syncClearButton();
          syncMetaLine('');
          clearTimeout(timer);
          if (term.length < 2) {
            hideResults();
            return;
          }
          timer = setTimeout(() => fetchMandats(term), 250);
        });

        searchInput.addEventListener('focus', function() {
          const term = this.value.trim();
          if (term.length >= 2) {
            fetchMandats(term);
          }
        });

        resultsBox.addEventListener('click', function(event) {
          const button = event.target.closest('button[data-id]');
          if (!button) {
            return;
          }
          setSelection(
            button.dataset.id,
            decodeURIComponent(button.dataset.title || ''),
            decodeURIComponent(button.dataset.meta || '')
          );
        });

        clearButton.addEventListener('click', function() {
          setSelection('', '', '');
          searchInput.focus();
        });

        document.addEventListener('click', function(event) {
          if (!event.target.closest('#eikon_current_mandat_search') && !event.target.closest('#eikon_current_mandat_results')) {
            hideResults();
          }
        });
      })();
    </script>
  </div>
<?php
}
add_action('edit_form_after_title', 'eikon_render_current_mandat_selector', 5);

function eikon_get_mandat_summary($mandat_id)
{
  $year_terms = wp_get_post_terms($mandat_id, 'year', array('fields' => 'names'));
  $section_terms = wp_get_post_terms($mandat_id, 'section', array('fields' => 'names'));
  $subject_terms = wp_get_post_terms($mandat_id, 'subjects', array('fields' => 'names'));
  $summary_parts = array();

  if (!empty($year_terms) && !is_wp_error($year_terms)) {
    $summary_parts[] = 'Année: ' . implode(', ', $year_terms);
  }
  if (!empty($section_terms) && !is_wp_error($section_terms)) {
    $summary_parts[] = 'Classe: ' . implode(', ', $section_terms);
  }
  if (!empty($subject_terms) && !is_wp_error($subject_terms)) {
    $summary_parts[] = 'Branche: ' . implode(', ', $subject_terms);
  }

  return !empty($summary_parts) ? implode(' | ', $summary_parts) : '';
}

function eikon_get_project_student_name_parts($project)
{
  $author = get_userdata($project->post_author);
  if (!$author) {
    return array('', '');
  }

  $first_name = trim((string) $author->first_name);
  $last_name = trim((string) $author->last_name);

  if ('' === $first_name && '' === $last_name) {
    return array(trim((string) $author->display_name), '');
  }

  return array($first_name, $last_name);
}

function eikon_render_mandat_projects_table($post)
{
  if (!$post || $post->post_type !== 'mandat') {
    return;
  }

  if (empty($post->ID) || 'auto-draft' === $post->post_status) {
    return;
  }

  $projects = get_posts(array(
    'post_type' => 'project',
    'post_status' => array('publish', 'draft', 'pending', 'future', 'private'),
    'posts_per_page' => -1,
    'orderby' => 'modified',
    'order' => 'DESC',
    'meta_key' => 'eikon_current_mandat_id',
    'meta_value' => (string) $post->ID,
  ));

  echo '<div style="margin-top: 24px;">';
  echo '<h2 style="margin: 0 0 12px 0; font-size: 18px; line-height: 1.4;">Projets liés</h2>';
  echo '<p style="margin: 0 0 12px 0; color: #6b7280;">Cochez "Highlight" pour signaler les projets retenus lors des corrections.</p>';

  if (empty($projects)) {
    echo '<p style="margin: 0; color: #6b7280;">Aucun projet n\'est actuellement rattaché à ce mandat.</p>';
    echo '</div>';
    return;
  }

  echo '<style>
    #eikon-mandat-projects-table th[data-sort] { cursor: pointer; user-select: none; white-space: nowrap; }
    #eikon-mandat-projects-table th[data-sort]:hover { background: #f0f0f0; }
    #eikon-mandat-projects-table th[data-sort] .sort-indicator { display: inline-block; margin-left: 5px; opacity: 0.35; font-size: 10px; }
    #eikon-mandat-projects-table th[data-sort].sort-asc .sort-indicator,
    #eikon-mandat-projects-table th[data-sort].sort-desc .sort-indicator { opacity: 1; }
    #eikon-mandat-projects-table th[data-sort].sort-asc .sort-indicator::after { content: "▲"; }
    #eikon-mandat-projects-table th[data-sort].sort-desc .sort-indicator::after { content: "▼"; }
    #eikon-mandat-projects-table th[data-sort]:not(.sort-asc):not(.sort-desc) .sort-indicator::after { content: "⇅"; }
    #eikon-mandat-projects-table tr.eikon-highlight-row td { background: #fff8db; }
  </style>';
  echo '<table id="eikon-mandat-projects-table" class="widefat striped" style="margin: 0; table-layout: fixed;">';
  echo '<thead><tr>';
  echo '<th scope="col" data-sort="0">Prénom <span class="sort-indicator"></span></th>';
  echo '<th scope="col" data-sort="1">Nom <span class="sort-indicator"></span></th>';
  echo '<th scope="col">Projet</th>';
  echo '<th scope="col" data-sort="3">Créé <span class="sort-indicator"></span></th>';
  echo '<th scope="col" data-sort="4">Mis à jour <span class="sort-indicator"></span></th>';
  echo '<th scope="col">Liens</th>';
  echo '<th scope="col">Highlight</th>';
  echo '</tr></thead>';
  echo '<tbody>';

  foreach ($projects as $project) {
    if (!$project instanceof WP_Post) {
      continue;
    }

    list($student_first_name, $student_last_name) = eikon_get_project_student_name_parts($project);
    $subtitle = trim((string) get_post_meta($project->ID, 'subtitle', true));
    $visit_link = $project->post_name ? trailingslashit(home_url('/')) . 'projets/' . $project->post_name . '/' : '';
    $edit_link = get_edit_post_link($project->ID, '');
    $created_ts = get_post_time('U', true, $project);
    $modified_ts = get_post_modified_time('U', true, $project);
    $is_highlighted = '1' === (string) get_post_meta($project->ID, 'eikon_mandat_highlight', true);

    echo '<tr' . ($is_highlighted ? ' class="eikon-highlight-row"' : '') . '>';
    echo '<td data-value="' . esc_attr(strtolower($student_first_name)) . '">' . esc_html($student_first_name) . '</td>';
    echo '<td data-value="' . esc_attr(strtolower($student_last_name)) . '">' . esc_html($student_last_name) . '</td>';
    echo '<td>';
    echo '<div style="font-weight: 600; color: #1d2327;">' . esc_html(get_the_title($project)) . '</div>';
    if ('' !== $subtitle) {
      echo '<div style="margin-top: 4px; font-size: 12px; color: #6b7280;">' . esc_html($subtitle) . '</div>';
    }
    echo '</td>';
    echo '<td data-value="' . esc_attr((string) $created_ts) . '">' . esc_html(get_the_date(get_option('date_format') . ' ' . get_option('time_format'), $project)) . '</td>';
    echo '<td data-value="' . esc_attr((string) $modified_ts) . '">' . esc_html(get_the_modified_date(get_option('date_format') . ' ' . get_option('time_format'), $project)) . '</td>';
    echo '<td>';
    if (!empty($visit_link)) {
      echo '<a href="' . esc_url($visit_link) . '" target="_blank" rel="noopener noreferrer">Voir</a>';
    } else {
      echo '<span style="color: #6b7280;">Voir</span>';
    }
    if (!empty($edit_link)) {
      echo ' | <a href="' . esc_url($edit_link) . '">Modifier</a>';
    }
    echo '</td>';
    echo '<td>';
    echo '<input type="hidden" name="eikon_mandat_project_ids[]" value="' . (int) $project->ID . '">';
    echo '<input type="checkbox" style="display: inline-block; margin: 0;" aria-label="Mettre en highlight" name="eikon_mandat_highlight_projects[]" value="' . (int) $project->ID . '"' . checked($is_highlighted, true, false) . '>';
    echo '</td>';
    echo '</tr>';
  }

  echo '</tbody>';
  echo '</table>';
  echo '<script>
  (function () {
    var table = document.getElementById("eikon-mandat-projects-table");
    if (!table) return;
    var headers = table.querySelectorAll("th[data-sort]");
    var sortCol = null, sortDir = 1;
    headers.forEach(function (th) {
      th.addEventListener("click", function () {
        var col = parseInt(th.getAttribute("data-sort"), 10);
        if (sortCol === col) {
          sortDir *= -1;
        } else {
          sortCol = col;
          sortDir = 1;
        }
        headers.forEach(function (h) { h.classList.remove("sort-asc", "sort-desc"); });
        th.classList.add(sortDir === 1 ? "sort-asc" : "sort-desc");
        var tbody = table.querySelector("tbody");
        var rows = Array.prototype.slice.call(tbody.querySelectorAll("tr"));
        rows.sort(function (a, b) {
          var aCell = a.querySelectorAll("td")[col];
          var bCell = b.querySelectorAll("td")[col];
          var aVal = aCell ? (aCell.getAttribute("data-value") || aCell.textContent.trim()) : "";
          var bVal = bCell ? (bCell.getAttribute("data-value") || bCell.textContent.trim()) : "";
          var aNum = parseFloat(aVal), bNum = parseFloat(bVal);
          if (!isNaN(aNum) && !isNaN(bNum)) return (aNum - bNum) * sortDir;
          return aVal.localeCompare(bVal, "fr") * sortDir;
        });
        rows.forEach(function (row) { tbody.appendChild(row); });
      });
    });
  })();
  </script>';
  echo '</div>';
}
add_action('edit_form_after_editor', 'eikon_render_mandat_projects_table');

function eikon_search_mandats_ajax()
{
  check_ajax_referer('eikon-search-mandats', 'nonce');

  if (!current_user_can('edit_posts')) {
    wp_send_json_error('Unauthorized', 403);
  }

  $term = sanitize_text_field($_GET['term'] ?? '');
  if (strlen($term) < 2) {
    wp_send_json_success(array());
  }

  $year_ids = array_filter(array_map('intval', explode(',', sanitize_text_field($_GET['year_ids'] ?? ''))));
  $section_ids = array_filter(array_map('intval', explode(',', sanitize_text_field($_GET['section_ids'] ?? ''))));
  $subjects_ids = array_filter(array_map('intval', explode(',', sanitize_text_field($_GET['subjects_ids'] ?? ''))));

  $tax_query = array('relation' => 'AND');
  if (!empty($year_ids)) {
    $tax_query[] = array(
      'taxonomy' => 'year',
      'field'    => 'term_id',
      'terms'    => $year_ids,
      'operator' => 'IN',
    );
  }
  if (!empty($section_ids)) {
    $tax_query[] = array(
      'taxonomy' => 'section',
      'field'    => 'term_id',
      'terms'    => $section_ids,
      'operator' => 'IN',
    );
  }
  if (!empty($subjects_ids)) {
    $tax_query[] = array(
      'taxonomy' => 'subjects',
      'field'    => 'term_id',
      'terms'    => $subjects_ids,
      'operator' => 'IN',
    );
  }

  $query_args = array(
    'post_type'      => 'mandat',
    'post_status'    => array('publish', 'draft', 'pending', 'future', 'private'),
    'posts_per_page' => 10,
    's'              => $term,
    'orderby'        => 'title',
    'order'          => 'ASC',
    'no_found_rows'  => true,
    'tax_query'      => count($tax_query) > 1 ? $tax_query : array(),
    'meta_query'     => array(
      array(
        'key'     => 'mandat_status',
        'value'   => 'en_cours',
        'compare' => '=',
      ),
    ),
  );

  $query = new WP_Query($query_args);
  $results = array();

  foreach ($query->posts as $mandat) {
    if (!$mandat instanceof WP_Post) {
      continue;
    }

    $results[] = array(
      'id' => $mandat->ID,
      'title' => get_the_title($mandat),
      'meta' => eikon_get_mandat_summary($mandat->ID),
    );
  }

  wp_send_json_success($results);
}
add_action('wp_ajax_eikon_search_mandats', 'eikon_search_mandats_ajax');

function eikon_save_project_current_mandat($post_id, $post, $update)
{
  if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
    return;
  }

  if (empty($post) || $post->post_type !== 'project') {
    return;
  }

  if (!isset($_POST['eikon_current_mandat_id'])) {
    return;
  }

  $previous_mandat_id = (int) get_post_meta($post_id, 'eikon_current_mandat_id', true);
  $mandat_id = absint($_POST['eikon_current_mandat_id']);
  if ($mandat_id > 0) {
    update_post_meta($post_id, 'eikon_current_mandat_id', $mandat_id);
  } else {
    delete_post_meta($post_id, 'eikon_current_mandat_id');
  }

  // Highlight is mandate-specific; clear it only when mandate relation changes.
  if ($previous_mandat_id !== $mandat_id) {
    delete_post_meta($post_id, 'eikon_mandat_highlight');
  }
}
add_action('save_post_project', 'eikon_save_project_current_mandat', 20, 3);

function eikon_save_mandat_project_highlights($post_id, $post, $update)
{
  if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
    return;
  }

  if (empty($post) || $post->post_type !== 'mandat') {
    return;
  }

  if (!current_user_can('edit_post', $post_id)) {
    return;
  }

  if (!isset($_POST['eikon_mandat_project_ids']) || !is_array($_POST['eikon_mandat_project_ids'])) {
    return;
  }

  $project_ids = array_unique(array_filter(array_map('absint', $_POST['eikon_mandat_project_ids'])));
  $highlighted_ids = isset($_POST['eikon_mandat_highlight_projects']) && is_array($_POST['eikon_mandat_highlight_projects'])
    ? array_unique(array_filter(array_map('absint', $_POST['eikon_mandat_highlight_projects'])))
    : array();

  foreach ($project_ids as $project_id) {
    if ('project' !== get_post_type($project_id)) {
      continue;
    }

    $linked_mandat_id = (int) get_post_meta($project_id, 'eikon_current_mandat_id', true);
    if ((int) $post_id !== $linked_mandat_id) {
      continue;
    }

    if (in_array($project_id, $highlighted_ids, true)) {
      update_post_meta($project_id, 'eikon_mandat_highlight', '1');
    } else {
      delete_post_meta($project_id, 'eikon_mandat_highlight');
    }
  }
}
add_action('save_post_mandat', 'eikon_save_mandat_project_highlights', 20, 3);

function eikon_add_mandat_projects_count_column($columns)
{
  $new_columns = array();

  foreach ($columns as $key => $label) {
    $new_columns[$key] = $label;
    if ('title' === $key) {
      $new_columns['mandat_projects_count'] = __('Projets liés');
    }
  }

  return $new_columns;
}
add_filter('manage_mandat_posts_columns', 'eikon_add_mandat_projects_count_column');

function eikon_render_mandat_projects_count_column($column, $post_id)
{
  if ('mandat_projects_count' !== $column) {
    return;
  }

  $linked_project_ids = get_posts(array(
    'post_type' => 'project',
    'post_status' => array('publish', 'draft', 'pending', 'future', 'private'),
    'posts_per_page' => -1,
    'fields' => 'ids',
    'no_found_rows' => true,
    'meta_key' => 'eikon_current_mandat_id',
    'meta_value' => (string) $post_id,
  ));

  echo esc_html((string) count($linked_project_ids));
}
add_action('manage_mandat_posts_custom_column', 'eikon_render_mandat_projects_count_column', 10, 2);
