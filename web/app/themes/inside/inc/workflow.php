<?php

/**
 * Eikon Workflow — Custom publish metabox for Mandat and Project CPTs
 *
 * Replaces the standard WordPress publish box with a cleaner UX:
 * - Colored status buttons with role-based visibility
 * - Last modification info and revisions link
 * - Legal declarations (projects only)
 * - Automatic status transitions (mandat in_review → projects pending)
 *
 * @package Inside\Workflow
 */

// ==========================================================================
// 1. CONFIGURATION — Status definitions per CPT
// ==========================================================================

/**
 * Get workflow status definitions for a given post type.
 *
 * @param string $post_type
 * @return array
 */
function eikon_get_workflow_statuses($post_type)
{
    if ('mandat' === $post_type) {
        return array(
            'draft' => array(
                'label'       => 'Brouillon',
                'color'       => '#6b7280',
                'description' => 'Le mandat n\'est pas encore visible pour les élèves.',
                'roles'       => array('teacher', 'editor', 'administrator'),
            ),
            'open' => array(
                'label'       => 'Ouvert',
                'color'       => '#2563eb',
                'description' => 'Les élèves peuvent associer ce mandat à leurs projets.',
                'roles'       => array('teacher', 'editor', 'administrator'),
            ),
            'in_review' => array(
                'label'       => 'En relecture',
                'color'       => '#d97706',
                'description' => 'Le mandat est terminé. Sélection des highlights en cours.',
                'roles'       => array('teacher', 'editor', 'administrator'),
            ),
            'publish' => array(
                'label'       => 'Publié',
                'color'       => '#16a34a',
                'description' => 'Le mandat est publié et visible sur le site.',
                'roles'       => array('editor', 'administrator'),
            ),
            'archive' => array(
                'label'       => 'Archivé',
                'color'       => '#991b1b',
                'description' => 'Le mandat est archivé et n\'est plus visible.',
                'roles'       => array('editor', 'administrator'),
            ),
        );
    }

    if ('project' === $post_type) {
        return array(
            'draft' => array(
                'label'       => 'Brouillon',
                'color'       => '#6b7280',
                'description' => 'Votre projet est en cours de saisie.',
                'roles'       => array('student', 'teacher', 'editor', 'administrator'),
            ),
            'pending' => array(
                'label'       => 'Remis',
                'color'       => '#d97706',
                'description' => 'Votre projet est soumis pour relecture par votre enseignant·e.',
                'roles'       => array('student', 'teacher', 'editor', 'administrator'),
            ),
            'publish' => array(
                'label'       => 'Publié',
                'color'       => '#16a34a',
                'description' => 'Le projet est publié et visible sur le site.',
                'roles'       => array('editor', 'administrator'),
            ),
            'archive' => array(
                'label'       => 'Archivé',
                'color'       => '#991b1b',
                'description' => 'Le projet est archivé et n\'est plus visible.',
                'roles'       => array('editor', 'administrator'),
            ),
        );
    }

    return array();
}

/**
 * Get the display label for a given post status + post type.
 *
 * @param string $status
 * @param string $post_type
 * @return string
 */
function eikon_get_status_label($status, $post_type)
{
    $statuses = eikon_get_workflow_statuses($post_type);
    return isset($statuses[$status]) ? $statuses[$status]['label'] : ucfirst($status);
}

/**
 * Get the color for a given post status + post type.
 *
 * @param string $status
 * @param string $post_type
 * @return string
 */
function eikon_get_status_color($status, $post_type)
{
    $statuses = eikon_get_workflow_statuses($post_type);
    return isset($statuses[$status]) ? $statuses[$status]['color'] : '#6b7280';
}

// ==========================================================================
// 2. METABOX — Replace the standard publish box
// ==========================================================================

/**
 * Remove the standard publish box and add our custom workflow metabox
 * for both mandat and project CPTs.
 */
function eikon_replace_publish_metabox()
{
    $post_types = array('mandat', 'project');

    foreach ($post_types as $pt) {
        remove_meta_box('submitdiv', $pt, 'side');

        add_meta_box(
            'eikon_workflow_metabox',
            'Publication',
            'eikon_render_workflow_metabox',
            $pt,
            'side',
            'high'
        );
    }
}
add_action('add_meta_boxes', 'eikon_replace_publish_metabox');

/**
 * Render the custom workflow metabox.
 *
 * @param WP_Post $post
 */
function eikon_render_workflow_metabox($post)
{
    $post_type      = $post->post_type;
    $current_status = $post->post_status;
    $statuses       = eikon_get_workflow_statuses($post_type);
    $current_user   = wp_get_current_user();
    $user_roles     = (array) $current_user->roles;
    $is_new_post    = in_array($current_status, array('auto-draft', 'new'), true);

    if ($is_new_post) {
        $current_status = 'draft';
    }

    wp_nonce_field('eikon_workflow_save', 'eikon_workflow_nonce');

    // --- Legal declarations for projects (moved from post_submitbox_misc_actions) ---
    $show_legal = ('project' === $post_type);
    $ai_value = '';
    $copyright_value = '';
    if ($show_legal) {
        wp_nonce_field('eikon_project_legal_save', 'eikon_project_legal_nonce');
        $ai_value        = get_post_meta($post->ID, 'eikon_contains_ai_content', true);
        $copyright_value = get_post_meta($post->ID, 'eikon_contains_copyright_content', true);
    }
}
?>
    <style>
        #eikon_workflow_metabox .inside {
            padding: 0;
            margin: 0;
        }

        .eikon-wf-section {
            padding: 12px;
            border-bottom: 1px solid #dcdcde;
        }

        .eikon-wf-section:last-child {
            border-bottom: none;
        }

        .eikon-wf-label {
            display: block;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #1d2327;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .eikon-wf-status-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .eikon-wf-status-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            margin-bottom: 5px;
            border: 2px solid transparent;
            border-radius: 6px;
            background: #f0f0f1;
            color: #50575e;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all .15s ease;
            user-select: none;
        }

        .eikon-wf-status-btn:hover {
            background: #e0e0e1;
        }

        .eikon-wf-status-btn.active {
            color: #fff;
            border-color: transparent;
        }

        .eikon-wf-status-btn.active:hover {
            filter: brightness(0.9);
        }

        .eikon-wf-status-btn .eikon-wf-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .eikon-wf-description {
            margin: 8px 0 0;
            font-size: 12px;
            color: #6b7280;
            line-height: 1.4;
            font-style: italic;
        }

        .eikon-wf-separator {
            margin: 10px 0 6px;
            padding-top: 8px;
            border-top: 1px dashed #dcdcde;
            font-size: 11px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .eikon-wf-meta {
            font-size: 12px;
            color: #6b7280;
            line-height: 1.5;
            margin: 0;
        }

        .eikon-wf-meta strong {
            color: #374151;
        }

        .eikon-wf-actions {
            padding: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .eikon-wf-save-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 20px;
            border: none;
            border-radius: 6px;
            background: #2271b1;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s;
        }

        .eikon-wf-save-btn:hover {
            background: #135e96;
        }

        .eikon-wf-delete-link {
            color: #b32d2e;
            font-size: 12px;
            text-decoration: none;
        }

        .eikon-wf-delete-link:hover {
            text-decoration: underline;
        }

        /* Status indicator (read-only for statuses user can't change to) */
        .eikon-wf-readonly-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #fff;
        }

        /* Legal declarations */
        .eikon-legal-section {
            padding: 12px;
            border-bottom: 1px solid #dcdcde;
        }

        .eikon-legal-field {
            margin-bottom: 10px;
        }

        .eikon-legal-field:last-of-type {
            margin-bottom: 0;
        }

        .eikon-legal-field .eikon-field-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #1d2327;
            line-height: 1.4;
        }

        .eikon-legal-field .eikon-required {
            color: #d63638;
            margin-left: 2px;
        }

        .eikon-toggle-group {
            display: flex;
            gap: 5px;
        }

        .eikon-toggle-group input[type="radio"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .eikon-toggle-group .eikon-toggle-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 3px 14px;
            border: 1px solid #c3c4c7;
            border-radius: 3px;
            background: #f6f7f7;
            color: #50575e;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            letter-spacing: .04em;
            transition: background .12s, border-color .12s, color .12s;
            user-select: none;
        }

        .eikon-toggle-group input[type="radio"]:checked+.eikon-toggle-btn {
            color: #fff;
            border-color: transparent;
        }

        .eikon-toggle-group input[type="radio"][value="oui"]:checked+.eikon-toggle-btn {
            background: #d63638;
        }

        .eikon-toggle-group input[type="radio"][value="non"]:checked+.eikon-toggle-btn {
            background: #00a32a;
        }

        .eikon-toggle-group .eikon-toggle-btn:hover {
            background: #dcdcde;
            border-color: #a7aaad;
        }

        .eikon-toggle-group input[type="radio"]:checked+.eikon-toggle-btn:hover {
            filter: brightness(.92);
        }

        .eikon-legal-error {
            display: none;
            margin-top: 4px;
            font-size: 11px;
            color: #d63638;
            font-style: italic;
        }
    </style>

    <input type="hidden" id="eikon_workflow_status" name="eikon_workflow_status" value="<?php echo esc_attr($current_status); ?>">

    <?php // --- Status Buttons Section --- ?>
    <div class="eikon-wf-section">
        <span class="eikon-wf-label">Statut</span>

        <?php
        $has_restricted = false;
        $user_can_change = false;

        foreach ($statuses as $slug => $config) :
            $allowed_roles = $config['roles'];
            $user_has_access = array_intersect($user_roles, $allowed_roles) || is_super_admin();
            $is_active = ($slug === $current_status);

            if ($user_has_access) :
                $user_can_change = true;
                ?>
                <button type="button"
                    class="eikon-wf-status-btn<?php echo $is_active ? ' active' : ''; ?>"
                    data-status="<?php echo esc_attr($slug); ?>"
                    style="<?php echo $is_active ? 'background:' . esc_attr($config['color']) . ';' : ''; ?>">
                    <span class="eikon-wf-dot" style="background: <?php echo esc_attr($config['color']); ?>;<?php echo $is_active ? 'background:#fff;' : ''; ?>"></span>
                    <?php echo esc_html($config['label']); ?>
                </button>
                <?php
            else :
                $has_restricted = true;
            endif;
        endforeach;

        // Show read-only indicator if current status is one the user can't access
        if (!array_intersect($user_roles, $statuses[$current_status]['roles'] ?? array()) && !is_super_admin()) :
            $current_config = $statuses[$current_status] ?? null;
            if ($current_config) :
                ?>
                <div style="margin-top: 6px;">
                    <span class="eikon-wf-readonly-status" style="background: <?php echo esc_attr($current_config['color']); ?>;">
                        <span class="eikon-wf-dot" style="background: #fff;"></span>
                        <?php echo esc_html($current_config['label']); ?>
                    </span>
                    <span style="font-size: 11px; color: #9ca3af; margin-left: 4px;">(lecture seule)</span>
                </div>
                <?php
            endif;
        endif;
        ?>

        <p class="eikon-wf-description" id="eikon-wf-status-description">
            <?php
            $current_config = $statuses[$current_status] ?? null;
            echo $current_config ? esc_html($current_config['description']) : '';
            ?>
        </p>
    </div>

    <?php // --- Metadata Section (modification date, revisions) --- ?>
    <div class="eikon-wf-section">
        <?php
        $last_modified = get_the_modified_date(get_option('date_format') . ' à ' . get_option('time_format'), $post);
        $last_modified_author = '';

        // Get last revision author
        $revisions = wp_get_post_revisions($post->ID, array('numberposts' => 1));
        $revision_count = wp_get_post_revisions($post->ID, array('numberposts' => -1));
        $revision_count = is_array($revision_count) ? count($revision_count) : 0;

        if (!empty($revisions)) {
            $last_revision = reset($revisions);
            $revision_author = get_userdata($last_revision->post_author);
            if ($revision_author) {
                $last_modified_author = trim($revision_author->first_name . ' ' . $revision_author->last_name);
                if (empty($last_modified_author)) {
                    $last_modified_author = $revision_author->display_name;
                }
            }
        }
        ?>

        <p class="eikon-wf-meta">
            <strong>Dernière modification :</strong><br>
            <?php echo esc_html($last_modified); ?>
            <?php if (!empty($last_modified_author)) : ?>
                <br>par <?php echo esc_html($last_modified_author); ?>
            <?php endif; ?>
        </p>

        <?php if ($revision_count > 0) : ?>
            <p class="eikon-wf-meta" style="margin-top: 6px;">
                <strong>Révisions :</strong>
                <a href="<?php echo esc_url(admin_url('revision.php?revision=' . (reset($revisions) ? reset($revisions)->ID : $post->ID))); ?>">
                    <?php echo esc_html($revision_count); ?> révision<?php echo $revision_count > 1 ? 's' : ''; ?>
                </a>
            </p>
        <?php endif; ?>
    </div>

    <?php // --- Legal Declarations (projects only) --- ?>
    <?php if ($show_legal) : ?>
        <div class="eikon-legal-section">
            <span class="eikon-wf-label">Déclarations légales</span>

            <div class="eikon-legal-field">
                <span class="eikon-field-label">
                    Contenu généré par IA&nbsp;<span class="eikon-required">*</span>
                </span>
                <div class="eikon-toggle-group">
                    <input type="radio" name="eikon_contains_ai_content" id="eikon_ai_oui" value="oui"
                        <?php checked($ai_value, 'oui'); ?>>
                    <label class="eikon-toggle-btn" for="eikon_ai_oui">OUI</label>
                    <input type="radio" name="eikon_contains_ai_content" id="eikon_ai_non" value="non"
                        <?php checked($ai_value, 'non'); ?>>
                    <label class="eikon-toggle-btn" for="eikon_ai_non">NON</label>
                </div>
                <span class="eikon-legal-error" id="eikon-error-ai">Veuillez répondre à cette question.</span>
            </div>

            <div class="eikon-legal-field">
                <span class="eikon-field-label">
                    Droits d'auteur non clarifiés&nbsp;<span class="eikon-required">*</span>
                </span>
                <div class="eikon-toggle-group">
                    <input type="radio" name="eikon_contains_copyright_content" id="eikon_copyright_oui" value="oui"
                        <?php checked($copyright_value, 'oui'); ?>>
                    <label class="eikon-toggle-btn" for="eikon_copyright_oui">OUI</label>
                    <input type="radio" name="eikon_contains_copyright_content" id="eikon_copyright_non" value="non"
                        <?php checked($copyright_value, 'non'); ?>>
                    <label class="eikon-toggle-btn" for="eikon_copyright_non">NON</label>
                </div>
                <span class="eikon-legal-error" id="eikon-error-copyright">Veuillez répondre à cette question.</span>
            </div>
        </div>
    <?php endif; ?>

    <?php // --- Save / Delete Actions --- ?>
    <div class="eikon-wf-actions">
        <?php if (current_user_can('delete_post', $post->ID) && !$is_new_post) : ?>
            <a href="<?php echo esc_url(get_delete_post_link($post->ID)); ?>" class="eikon-wf-delete-link"
                onclick="return confirm('Êtes-vous sûr de vouloir mettre cet élément à la corbeille ?');">
                Supprimer
            </a>
        <?php else : ?>
            <span></span>
        <?php endif; ?>

        <button type="submit" name="save" id="publish" class="eikon-wf-save-btn" value="Enregistrer">
            ✓ Enregistrer
        </button>
    </div>

    <script>
        (function() {
            var statusInput = document.getElementById('eikon_workflow_status');
            var buttons = document.querySelectorAll('.eikon-wf-status-btn');
            var descriptionEl = document.getElementById('eikon-wf-status-description');
            var statusDescriptions = <?php echo wp_json_encode(
                array_map(function ($s) {
                    return $s['description'];
                }, $statuses)
            ); ?>;
            var statusColors = <?php echo wp_json_encode(
                array_map(function ($s) {
                    return $s['color'];
                }, $statuses)
            ); ?>;

            buttons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var newStatus = this.getAttribute('data-status');
                    statusInput.value = newStatus;

                    // Update active state
                    buttons.forEach(function(b) {
                        b.classList.remove('active');
                        b.style.background = '';
                        b.style.color = '';
                        var dot = b.querySelector('.eikon-wf-dot');
                        if (dot) dot.style.background = statusColors[b.getAttribute('data-status')] || '#6b7280';
                    });

                    this.classList.add('active');
                    this.style.background = statusColors[newStatus] || '#6b7280';
                    this.style.color = '#fff';
                    var activeDot = this.querySelector('.eikon-wf-dot');
                    if (activeDot) activeDot.style.background = '#fff';

                    // Update description
                    if (descriptionEl) {
                        descriptionEl.textContent = statusDescriptions[newStatus] || '';
                    }
                });
            });

            // Legal validation on save (projects only)
            var saveBtn = document.getElementById('publish');
            var postType = '<?php echo esc_js($post_type); ?>';

            if (saveBtn && postType === 'project') {
                saveBtn.addEventListener('click', function(e) {
                    var targetStatus = statusInput.value;

                    // Only validate legal fields when publishing
                    if (targetStatus !== 'publish') {
                        return;
                    }

                    var aiChecked = document.querySelector('input[name="eikon_contains_ai_content"]:checked');
                    var copyrightChecked = document.querySelector('input[name="eikon_contains_copyright_content"]:checked');
                    var aiError = document.getElementById('eikon-error-ai');
                    var copyrightError = document.getElementById('eikon-error-copyright');
                    var hasError = false;

                    if (!aiChecked) {
                        if (aiError) aiError.style.display = 'block';
                        hasError = true;
                    } else {
                        if (aiError) aiError.style.display = 'none';
                    }

                    if (!copyrightChecked) {
                        if (copyrightError) copyrightError.style.display = 'block';
                        hasError = true;
                    } else {
                        if (copyrightError) copyrightError.style.display = 'none';
                    }

                    if (hasError) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        var legalSection = document.querySelector('.eikon-legal-section');
                        if (legalSection) {
                            legalSection.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                        }
                    }
                });
            }
        })();
    </script>
    <?php
    }

// ==========================================================================
// 3. STATUS TRANSITION — Apply the workflow status on save
// ==========================================================================

/**
 * Apply the custom workflow status from our metabox when saving a post.
 *
 * Intercepts wp_insert_post_data to set post_status from our hidden field,
 * with role-based permission checks.
 */
    function eikon_workflow_apply_status($data, $postarr)
    {
        $post_type = $data['post_type'];

        if (!in_array($post_type, array('mandat', 'project'), true)) {
            return $data;
        }

        // Skip if our nonce is not set (REST API, WP-CLI, imports)
        if (
            !isset($_POST['eikon_workflow_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['eikon_workflow_nonce'])), 'eikon_workflow_save')
        ) {
            return $data;
        }

        if (!isset($_POST['eikon_workflow_status'])) {
            return $data;
        }

        $requested_status = sanitize_key($_POST['eikon_workflow_status']);
        $statuses         = eikon_get_workflow_statuses($post_type);

        // Validate the status exists in our definitions
        if (!isset($statuses[$requested_status])) {
            return $data;
        }

        // Check if user has permission for this status
        $current_user = wp_get_current_user();
        $user_roles   = (array) $current_user->roles;
        $allowed_roles = $statuses[$requested_status]['roles'];

        if (!array_intersect($user_roles, $allowed_roles) && !is_super_admin()) {
            return $data;
        }

        $data['post_status'] = $requested_status;

        return $data;
    }
    add_filter('wp_insert_post_data', 'eikon_workflow_apply_status', 5, 2);

// ==========================================================================
// 4. AUTOMATIC TRANSITIONS — Mandat in_review triggers project pending
// ==========================================================================

/**
 * When a mandat transitions to "in_review", automatically set all linked
 * draft projects to "pending" (Remis).
 */
    function eikon_mandat_auto_transition_projects($post_id, $post, $update)
    {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        if (empty($post) || 'mandat' !== $post->post_type) {
            return;
        }

        if ('in_review' !== $post->post_status) {
            return;
        }

        // Only trigger on actual status change
        $old_status = get_post_meta($post_id, '_eikon_previous_status', true);
        if ('in_review' === $old_status) {
            return;
        }

        // Find all linked projects still in draft
        $draft_projects = get_posts(array(
        'post_type'      => 'project',
        'post_status'    => 'draft',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_key'       => 'eikon_current_mandat_id',
        'meta_value'     => (string) $post_id,
        ));

        if (!empty($draft_projects)) {
            global $wpdb;
            $ids_placeholder = implode(',', array_map('intval', $draft_projects));
            $wpdb->query(
                "UPDATE {$wpdb->posts} SET post_status = 'pending' WHERE ID IN ({$ids_placeholder})"
            );

            // Clean object cache for each updated project
            foreach ($draft_projects as $project_id) {
                    clean_post_cache($project_id);
            }
        }
    }
    add_action('save_post_mandat', 'eikon_mandat_auto_transition_projects', 30, 3);

/**
 * Track previous status to detect transitions.
 */
    function eikon_track_previous_status($post_id, $post, $update)
    {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        if (!in_array($post->post_type, array('mandat', 'project'), true)) {
            return;
        }

        update_post_meta($post_id, '_eikon_previous_status', $post->post_status);
    }
    add_action('save_post', 'eikon_track_previous_status', 99, 3);

// ==========================================================================
// 5. LABEL OVERRIDES — Rename "pending" to "Remis" for projects
// ==========================================================================

/**
 * Rename the "pending" status label to "Remis" for the project CPT
 * in admin screens (list view, status counts, etc.)
 */
    function eikon_rename_pending_for_projects($translation, $text, $domain)
    {
        if (!is_admin()) {
            return $translation;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen) {
            return $translation;
        }

        // Only rename for project screens
        if ('project' !== $screen->post_type) {
            return $translation;
        }

        $replacements = array(
        'Pending'               => 'Remis',
        'En attente de relecture' => 'Remis',
        'Pending Review'        => 'Remis',
        );

        if (isset($replacements[$text])) {
            return $replacements[$text];
        }

        return $translation;
    }
    add_filter('gettext', 'eikon_rename_pending_for_projects', 10, 3);

// ==========================================================================
// 6. STATUS BADGES — Admin list columns
// ==========================================================================

/**
 * Add status badge styles globally for admin.
 */
    function eikon_workflow_admin_styles()
    {
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->post_type, array('mandat', 'project'), true)) {
            return;
        }

        echo '<style>
        .eikon-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            color: #fff;
            line-height: 1.6;
            white-space: nowrap;
        }
        .eikon-status-badge .eikon-badge-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: rgba(255,255,255,0.7);
        }
    </style>';
    }
    add_action('admin_head', 'eikon_workflow_admin_styles');

/**
 * Render a status badge HTML snippet.
 *
 * @param string $status
 * @param string $post_type
 * @return string
 */
    function eikon_render_status_badge($status, $post_type)
    {
        $label = eikon_get_status_label($status, $post_type);
        $color = eikon_get_status_color($status, $post_type);

        return sprintf(
            '<span class="eikon-status-badge" style="background:%s;"><span class="eikon-badge-dot"></span>%s</span>',
            esc_attr($color),
            esc_html($label)
        );
    }

/**
 * Add a "Statut" column to the mandat admin list.
 */
    function eikon_mandat_status_column($columns)
    {
        $new_columns = array();

        foreach ($columns as $key => $label) {
            $new_columns[$key] = $label;
            if ('title' === $key) {
                $new_columns['mandat_status'] = __('Statut');
            }
        }

        return $new_columns;
    }
    add_filter('manage_mandat_posts_columns', 'eikon_mandat_status_column');

/**
 * Render the status badge in the mandat admin list.
 */
    function eikon_mandat_status_column_content($column, $post_id)
    {
        if ('mandat_status' !== $column) {
            return;
        }

        $post = get_post($post_id);
        if (!$post) {
            return;
        }

        echo eikon_render_status_badge($post->post_status, 'mandat');
    }
    add_action('manage_mandat_posts_custom_column', 'eikon_mandat_status_column_content', 10, 2);

/**
 * Add a "Statut" column to the project admin list.
 */
    function eikon_project_status_column($columns)
    {
        $new_columns = array();

        foreach ($columns as $key => $label) {
            $new_columns[$key] = $label;
            if ('title' === $key) {
                $new_columns['project_status'] = __('Statut');
            }
        }

        return $new_columns;
    }
    add_filter('manage_project_posts_columns', 'eikon_project_status_column');

/**
 * Render the status badge in the project admin list.
 */
    function eikon_project_status_column_content($column, $post_id)
    {
        if ('project_status' !== $column) {
            return;
        }

        $post = get_post($post_id);
        if (!$post) {
            return;
        }

        echo eikon_render_status_badge($post->post_status, 'project');
    }
    add_action('manage_project_posts_custom_column', 'eikon_project_status_column_content', 10, 2);

// ==========================================================================
// 7. STATUS FILTER DROPDOWNS — Admin list filters
// ==========================================================================

/**
 * Add a status filter dropdown to the mandat admin list.
 */
    function eikon_mandat_status_filter($post_type)
    {
        if ('mandat' !== $post_type) {
            return;
        }

        $selected = sanitize_text_field($_GET['eikon_mandat_status'] ?? '');
        $statuses = eikon_get_workflow_statuses('mandat');

        echo '<select name="eikon_mandat_status" id="eikon_mandat_status">';
        echo '<option value="">' . esc_html__('Tous les statuts') . '</option>';

        foreach ($statuses as $slug => $config) {
            echo '<option value="' . esc_attr($slug) . '"' . selected($selected, $slug, false) . '>'
            . esc_html($config['label'])
            . '</option>';
        }

        echo '</select>';
    }
    add_action('restrict_manage_posts', 'eikon_mandat_status_filter');

/**
 * Apply the status filter to the mandat admin list query.
 */
    function eikon_mandat_status_filter_query($query)
    {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        if ('mandat' !== $query->get('post_type')) {
            return;
        }

        $status = sanitize_key($_GET['eikon_mandat_status'] ?? '');
        if ('' === $status) {
            return;
        }

        $valid_statuses = array_keys(eikon_get_workflow_statuses('mandat'));
        if (in_array($status, $valid_statuses, true)) {
            $query->set('post_status', $status);
        }
    }
    add_action('pre_get_posts', 'eikon_mandat_status_filter_query');

/**
 * Ensure custom statuses appear in the "All" view of admin lists.
 */
    function eikon_include_custom_statuses_in_admin($query)
    {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        $post_type = $query->get('post_type');

        if (!in_array($post_type, array('mandat', 'project'), true)) {
            return;
        }

        // Don't override if a specific status is already requested
        $requested_status = $query->get('post_status');
        if (!empty($requested_status) && 'all' !== $requested_status) {
            return;
        }

        if ('mandat' === $post_type) {
            $query->set('post_status', array('draft', 'open', 'in_review', 'publish', 'archive', 'trash'));
        }
    }
    add_action('pre_get_posts', 'eikon_include_custom_statuses_in_admin');

/**
 * Add custom status counts to the admin views (status links at top of list table).
 */
    function eikon_mandat_admin_views($views)
    {
        global $wpdb;

        $statuses = eikon_get_workflow_statuses('mandat');
        $current_status = sanitize_key($_GET['post_status'] ?? '');

        foreach ($statuses as $slug => $config) {
            // Skip native statuses that WordPress already handles
            if (in_array($slug, array('draft', 'publish'), true)) {
                continue;
            }

            $count = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'mandat' AND post_status = %s",
                $slug
            ));

            if ($count > 0) {
                $class = ($current_status === $slug) ? ' class="current"' : '';
                $url = admin_url('edit.php?post_type=mandat&post_status=' . $slug);
                $views[$slug] = sprintf(
                    '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
                    esc_url($url),
                    $class,
                    esc_html($config['label']),
                    $count
                );
            }
        }

        return $views;
    }
    add_filter('views_edit-mandat', 'eikon_mandat_admin_views');

/**
 * Add custom status counts for projects admin views.
 */
    function eikon_project_admin_views($views)
    {
        global $wpdb;

        // Rename "Pending" to "Remis" in views
        if (isset($views['pending'])) {
            $views['pending'] = str_replace(
                array('Pending', 'En attente de relecture'),
                'Remis',
                $views['pending']
            );
        }

        // Add archive status view
        $count = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'project' AND post_status = 'archive'"
        );

        if ($count > 0) {
            $current_status = sanitize_key($_GET['post_status'] ?? '');
            $class = ($current_status === 'archive') ? ' class="current"' : '';
            $url = admin_url('edit.php?post_type=project&post_status=archive');
            $views['archive'] = sprintf(
                '<a href="%s"%s>Archivé <span class="count">(%d)</span></a>',
                esc_url($url),
                $class,
                $count
            );
        }

        return $views;
    }
    add_filter('views_edit-project', 'eikon_project_admin_views');

// ==========================================================================
// 8. VISIBILITY — Remove the standard WP Visibility option
// ==========================================================================

/**
 * Hide the visibility option from the classic editor.
 * Since we use a headless setup, visibility (public/private/password) is irrelevant.
 */
    function eikon_hide_visibility_option()
    {
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->post_type, array('mandat', 'project', 'post', 'page', 'department'), true)) {
            return;
        }

        echo '<style>
        #visibility-radio-public,
        #visibility-radio-password,
        #visibility-radio-private,
        .misc-pub-visibility { display: none !important; }
    </style>';
    }
    add_action('admin_head', 'eikon_hide_visibility_option');
