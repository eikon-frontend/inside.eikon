<?php

/**
 * Dashboard de Publication pour les Relecteurs
 */

// 1. Enregistrer le menu
add_action('admin_menu', 'eikon_register_publication_dashboard');
function eikon_register_publication_dashboard()
{
    add_menu_page(
        'Dashboard Publication',
        'Publication',
        'publish_pages', // Capacité requise (Administrateurs / Relecteurs)
        'eikon-publication-dashboard',
        'eikon_render_publication_dashboard',
        'dashicons-visibility',
        4 // Position (après Dashboard)
    );
}

// 2. Enregistrer l'action AJAX pour la publication/archivage
add_action('wp_ajax_eikon_dashboard_action', 'eikon_dashboard_ajax_action');
function eikon_dashboard_ajax_action()
{
    check_ajax_referer('eikon_dashboard_action', 'nonce');

    if (!current_user_can('publish_pages')) {
        wp_send_json_error('Permission refusée.');
    }

    $action_type = sanitize_text_field($_POST['action_type']);
    $post_id     = (int) $_POST['post_id'];
    $post        = get_post($post_id);

    if (!$post) {
        wp_send_json_error('Contenu introuvable.');
    }

    if ($action_type === 'publish_project' || $action_type === 'publish_standalone_project') {
        wp_update_post([
            'ID'          => $post_id,
            'post_status' => 'publish'
        ]);
        wp_send_json_success('Projet publié.');
    } elseif ($action_type === 'archive_project') {
        wp_update_post([
            'ID'          => $post_id,
            'post_status' => 'archive'
        ]);
        wp_send_json_success('Projet archivé.');
    } elseif ($action_type === 'publish_mandat') {
        // Publier le mandat
        wp_update_post([
            'ID'          => $post_id,
            'post_status' => 'publish'
        ]);

        // Archiver tous les projets "Remis" (pending) liés à ce mandat qui ne sont pas des highlights
        $projects_query = new WP_Query([
            'post_type'      => 'project',
            'post_status'    => 'pending',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'   => 'eikon_current_mandat_id',
                    'value' => $post_id,
                ]
            ]
        ]);

        if ($projects_query->have_posts()) {
            foreach ($projects_query->posts as $proj) {
                $is_highlight = get_post_meta($proj->ID, 'eikon_mandat_highlight', true);
                // Si ce n'est pas un highlight (ou s'il n'est pas déjà publié), on l'archive
                if ($is_highlight !== '1') {
                    wp_update_post([
                        'ID'          => $proj->ID,
                        'post_status' => 'archive'
                    ]);
                }
            }
        }

        wp_send_json_success('Mandat publié et autres projets archivés.');
    } elseif ($action_type === 'archive_mandat') {
        wp_update_post([
            'ID'          => $post_id,
            'post_status' => 'archive'
        ]);
        wp_send_json_success('Mandat archivé.');
    }

    wp_send_json_error('Action non reconnue.');
}

// 3. Rendu du Dashboard
function eikon_render_publication_dashboard()
{
    if (!current_user_can('publish_pages')) {
        return;
    }

    // Récupérer les mandats en attente
    $mandats_query = new WP_Query([
        'post_type'      => 'mandat',
        'post_status'    => 'in_review',
        'posts_per_page' => -1,
        'orderby'        => 'modified',
        'order'          => 'DESC'
    ]);

    // Récupérer les projets libres en attente (sans mandat)
    $standalone_projects_query = new WP_Query([
        'post_type'      => 'project',
        'post_status'    => 'pending',
        'posts_per_page' => -1,
        'meta_query'     => [
            'relation' => 'OR',
            [
                'key'     => 'eikon_current_mandat_id',
                'compare' => 'NOT EXISTS'
            ],
            [
                'key'     => 'eikon_current_mandat_id',
                'value'   => '',
                'compare' => '='
            ]
        ]
    ]);

    $stats_mandats = $mandats_query->found_posts;
    $stats_projects = $standalone_projects_query->found_posts;

?>
    <div class="wrap epd-wrap">

        <!-- Tabs -->
        <div class="epd-tabs">
            <button type="button" class="epd-tab epd-tab--active" data-tab="mandats">
                Mandats
                <span class="epd-tab-count"><?php echo (int) $stats_mandats; ?></span>
            </button>
            <button type="button" class="epd-tab" data-tab="projets">
                Projets libres
                <span class="epd-tab-count"><?php echo (int) $stats_projects; ?></span>
            </button>
        </div>

        <!-- Tab: Mandats -->
        <div class="epd-panel" id="epd-panel-mandats">
            <p class="epd-hint">
                Cette page contient le listing des mandats en attente de publication.<br>
                Validez d'abord les projets highlights, puis publiez le mandat. Les projets non-highlights seront automatiquement archivés.
            </p>

            <?php if ($mandats_query->have_posts()) : ?>
                <table class="epd-table">
                    <thead>
                        <tr>
                            <th style="width: 28px;"></th>
                            <th>Mandat</th>
                            <th>Enseignant</th>
                            <th>Projets</th>
                            <th>Modifié</th>
                            <th style="width: 1%; white-space: nowrap;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mandats_query->posts as $mandat) : ?>
                            <?php
                            $author = get_userdata((int) $mandat->post_author);
                            $author_name = $author ? $author->display_name : 'Inconnu';

                            // Get ALL projects linked to this mandat
                            $all_projects = new WP_Query([
                                'post_type'      => 'project',
                                'post_status'    => ['pending', 'publish'],
                                'posts_per_page' => -1,
                                'meta_query'     => [
                                    [
                                        'key'   => 'eikon_current_mandat_id',
                                        'value' => $mandat->ID,
                                    ]
                                ]
                            ]);
                            $total_projects = $all_projects->found_posts;
                            $total_published = 0;
                            foreach ($all_projects->posts as $ap) {
                                if ($ap->post_status === 'publish') {
                                    $total_published++;
                                }
                            }

                            // Get highlight projects only
                            $highlights = new WP_Query([
                                'post_type'      => 'project',
                                'post_status'    => ['pending', 'publish'],
                                'posts_per_page' => -1,
                                'meta_query'     => [
                                    'relation' => 'AND',
                                    [
                                        'key'   => 'eikon_current_mandat_id',
                                        'value' => $mandat->ID,
                                    ],
                                    [
                                        'key'   => 'eikon_mandat_highlight',
                                        'value' => '1',
                                    ]
                                ]
                            ]);

                            $all_published = true;
                            $has_highlights = $highlights->have_posts();
                            $hl_count = $highlights->found_posts;
                            $hl_published = 0;

                            if ($has_highlights) {
                                foreach ($highlights->posts as $h) {
                                    if ($h->post_status === 'publish') {
                                        $hl_published++;
                                    } else {
                                        $all_published = false;
                                    }
                                }
                            }
                            ?>
                            <tr class="epd-row-mandat" id="mandat-row-<?php echo (int) $mandat->ID; ?>">
                                <td class="epd-expand-toggle" data-mandat="<?php echo (int) $mandat->ID; ?>">
                                    <?php if ($has_highlights) : ?>
                                        <span class="epd-chevron">&#9654;</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url(get_edit_post_link($mandat->ID)); ?>" class="epd-title" target="_blank"><?php echo esc_html($mandat->post_title); ?></a>
                                </td>
                                <td class="epd-meta-cell"><?php echo esc_html($author_name); ?></td>
                                <td class="epd-badges-cell">
                                    <span class="epd-badge epd-badge--neutral"><?php echo (int) $total_projects; ?> soumis</span>
                                    <?php if ($has_highlights) : ?>
                                        <span class="epd-badge epd-badge--blue"><?php echo (int) $hl_count; ?> highlight<?php echo $hl_count > 1 ? 's' : ''; ?></span>
                                    <?php endif; ?>
                                    <?php if ($total_published > 0) : ?>
                                        <span class="epd-badge epd-badge--green"><?php echo (int) $total_published; ?> publié<?php echo $total_published > 1 ? 's' : ''; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="epd-meta-cell"><?php echo get_the_modified_date('d/m/Y', $mandat); ?></td>
                                <td class="epd-actions-cell">
                                    <button type="button" class="epd-btn epd-btn--primary action-publish-mandat" data-id="<?php echo (int) $mandat->ID; ?>" <?php echo (!$all_published || !$has_highlights) ? 'disabled title="Validez tous les highlights d\'abord"' : ''; ?>>Publier</button>
                                    <div class="epd-dropdown">
                                        <button type="button" class="epd-btn epd-btn--ghost epd-dropdown-trigger" aria-label="Plus d'actions">&hellip;</button>
                                        <div class="epd-dropdown-menu">
                                            <a href="<?php echo esc_url(get_edit_post_link($mandat->ID)); ?>" target="_blank">Ouvrir</a>
                                            <button type="button" class="epd-dropdown-danger action-archive-mandat" data-id="<?php echo (int) $mandat->ID; ?>">Archiver</button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php if ($has_highlights) : ?>
                                <tr class="epd-row-highlights" id="highlights-<?php echo (int) $mandat->ID; ?>" style="display: none;">
                                    <td colspan="6">
                                        <div class="epd-sub-table-wrap">
                                            <table class="epd-sub-table">
                                                <thead>
                                                    <tr>
                                                        <th>Projet</th>
                                                        <th>Élève</th>
                                                        <th>Statut</th>
                                                        <th style="width: 1%; white-space: nowrap;">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($highlights->posts as $proj) : ?>
                                                        <?php
                                                        $p_author = get_userdata((int) $proj->post_author);
                                                        $is_published = ($proj->post_status === 'publish');

                                                        // Check legal status
                                                        $ai_val = get_post_meta($proj->ID, 'eikon_contains_ai_content', true);
                                                        $cr_val = get_post_meta($proj->ID, 'eikon_copyright_cleared', true);
                                                        $legal_warning = '';
                                                        if ('oui' === $ai_val) {
                                                            $legal_warning = 'Contient de l\'IA';
                                                        } elseif ('oui' !== $cr_val) {
                                                            $legal_warning = 'Droits non clarifiés';
                                                        }
                                                        ?>
                                                        <tr id="proj-item-<?php echo (int) $proj->ID; ?>">
                                                            <td>
                                                                <div class="epd-title-wrapper">
                                                                    <a href="<?php echo esc_url(get_edit_post_link($proj->ID)); ?>" class="epd-title" target="_blank"><?php echo esc_html($proj->post_title); ?></a>
                                                                    <?php if ($legal_warning) : ?>
                                                                        <span class="epd-warning-badge" title="<?php echo esc_attr($legal_warning); ?>">⚠️</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </td>
                                                            <td class="epd-meta-cell"><?php echo $p_author ? esc_html($p_author->display_name) : 'Inconnu'; ?></td>
                                                            <td>
                                                                <?php if ($is_published) : ?>
                                                                    <span class="epd-badge epd-badge--green">Publié</span>
                                                                <?php else : ?>
                                                                    <span class="epd-badge epd-badge--yellow">En attente</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="epd-actions-cell">
                                                                <?php if (!$is_published) : ?>
                                                                    <button type="button" class="epd-btn epd-btn--primary epd-btn--sm action-publish-project" data-id="<?php echo (int) $proj->ID; ?>" data-mandat="<?php echo (int) $mandat->ID; ?>">Publier</button>
                                                                <?php endif; ?>
                                                                <div class="epd-dropdown">
                                                                    <button type="button" class="epd-btn epd-btn--ghost epd-btn--sm epd-dropdown-trigger" aria-label="Plus d'actions">&hellip;</button>
                                                                    <div class="epd-dropdown-menu">
                                                                        <a href="<?php echo esc_url(get_edit_post_link($proj->ID)); ?>" target="_blank">Ouvrir</a>
                                                                        <?php if (!$is_published) : ?>
                                                                            <button type="button" class="epd-dropdown-danger action-archive-project" data-id="<?php echo (int) $proj->ID; ?>">Archiver</button>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="epd-empty">Aucun mandat en attente de relecture.</div>
            <?php endif; ?>
        </div>

        <!-- Tab: Projets libres -->
        <div class="epd-panel" id="epd-panel-projets" style="display: none;">
            <p class="epd-hint">
                Cette page contient le listing des projets en attente de publication qui ne sont pas liés à un mandat.<br>
            </p>

            <?php if ($standalone_projects_query->have_posts()) : ?>
                <table class="epd-table">
                    <thead>
                        <tr>
                            <th>Projet</th>
                            <th>Élève</th>
                            <th>Date</th>
                            <th style="width: 1%; white-space: nowrap;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($standalone_projects_query->posts as $proj) : ?>
                            <?php
                            $author = get_userdata((int) $proj->post_author);

                            // Check legal status
                            $ai_val = get_post_meta($proj->ID, 'eikon_contains_ai_content', true);
                            $cr_val = get_post_meta($proj->ID, 'eikon_copyright_cleared', true);
                            $legal_warning = '';
                            if ('oui' === $ai_val) {
                                $legal_warning = 'Contient de l\'IA';
                            } elseif ('oui' !== $cr_val) {
                                $legal_warning = 'Droits non clarifiés';
                            }
                            ?>
                            <tr id="standalone-proj-<?php echo (int) $proj->ID; ?>">
                                <td>
                                    <div class="epd-title-wrapper">
                                        <a href="<?php echo esc_url(get_edit_post_link($proj->ID)); ?>" class="epd-title" target="_blank"><?php echo esc_html($proj->post_title); ?></a>
                                        <?php if ($legal_warning) : ?>
                                            <span class="epd-warning-badge" title="<?php echo esc_attr($legal_warning); ?>">⚠️</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="epd-meta-cell"><?php echo $author ? esc_html($author->display_name) : 'Inconnu'; ?></td>
                                <td class="epd-meta-cell"><?php echo get_the_modified_date('d/m/Y', $proj); ?></td>
                                <td class="epd-actions-cell">
                                    <button type="button" class="epd-btn epd-btn--primary epd-btn--sm action-standalone-publish" data-id="<?php echo (int) $proj->ID; ?>">Publier</button>
                                    <div class="epd-dropdown">
                                        <button type="button" class="epd-btn epd-btn--ghost epd-btn--sm epd-dropdown-trigger" aria-label="Plus d'actions">&hellip;</button>
                                        <div class="epd-dropdown-menu">
                                            <a href="<?php echo esc_url(get_edit_post_link($proj->ID)); ?>" target="_blank">Ouvrir</a>
                                            <button type="button" class="epd-dropdown-danger action-standalone-archive" data-id="<?php echo (int) $proj->ID; ?>">Archiver</button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="epd-empty">Aucun projet libre en attente.</div>
            <?php endif; ?>
        </div>

    </div>

    <style>
        /* ── Reset & Base ── */
        .epd-wrap {
            max-width: 1100px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        .epd-wrap * {
            box-sizing: border-box;
        }


        /* ── Tabs ── */
        .epd-tabs {
            display: flex;
            gap: 4px;
            border-bottom: 1px solid #e2e4e7;
            margin-bottom: 0;
        }

        .epd-tab {
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 500;
            color: #8c8f94;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color .15s, border-color .15s;
        }

        .epd-tab:hover {
            color: #1d2327;
        }

        .epd-tab--active {
            color: #1d2327;
            border-bottom-color: #1d2327;
        }

        .epd-tab-count {
            background: #f0f0f1;
            color: #50575e;
            border-radius: 10px;
            padding: 1px 8px;
            font-size: 11px;
            font-weight: 600;
        }

        .epd-tab--active .epd-tab-count {
            background: #1d2327;
            color: #fff;
        }

        /* ── Panel ── */
        .epd-panel {
            margin-top: 0;
        }

        .epd-hint {
            font-size: 13px;
            margin: 16px 0 12px;
        }

        /* ── Table ── */
        .epd-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .epd-table thead th {
            text-align: left;
            font-weight: 500;
            color: #8c8f94;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 12px;
            border-bottom: 1px solid #e2e4e7;
            background: none;
        }

        .epd-table tbody tr {
            border-bottom: 1px solid #f0f0f1;
            transition: background .1s;
        }

        .epd-table tbody tr:hover {
            background: #fafbfc;
        }

        .epd-table tbody td {
            padding: 12px;
            vertical-align: middle;
        }

        .epd-title {
            font-weight: 600;
            color: #1d2327;
            text-decoration: none;
        }

        .epd-title:hover {
            color: #2271b1;
        }

        .epd-title-wrapper {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .epd-warning-badge {
            cursor: help;
            font-size: 14px;
            line-height: 1;
        }

        .epd-row-mandat--expanded {
            background: #fff !important;
        }

        .epd-meta-cell {
            color: #646970;
        }

        /* ── Expand / Chevron ── */
        .epd-expand-toggle {
            cursor: pointer;
            text-align: center;
            user-select: none;
        }

        .epd-chevron {
            display: inline-block;
            font-size: 10px;
            color: #8c8f94;
            transition: transform .2s;
        }

        .epd-chevron--open {
            transform: rotate(90deg);
        }

        /* ── Sub-table (Highlights) ── */
        .epd-row-highlights>td {
            padding: 0 !important;
            background: #fafbfc;
        }

        .epd-sub-table-wrap {
            padding: 4px 12px 12px 40px;
        }

        .epd-sub-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .epd-sub-table thead th {
            text-align: left;
            font-weight: 500;
            color: #8c8f94;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            border-bottom: 1px solid #e2e4e7;
            background: none;
        }

        .epd-sub-table tbody td {
            padding: 8px 10px;
            vertical-align: middle;
        }

        .epd-sub-table tbody tr {
            border-bottom: 1px solid #f0f0f1;
        }

        .epd-sub-table tbody tr:last-child {
            border-bottom: none;
        }

        /* ── Badges ── */
        .epd-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            line-height: 18px;
            white-space: nowrap;
        }

        .epd-badge--blue {
            background: #e8f0fe;
            color: #1a56db;
        }

        .epd-badge--green {
            background: #d1fae5;
            color: #065f46;
        }

        .epd-badge--yellow {
            background: #fef3c7;
            color: #92400e;
        }

        .epd-badge--neutral {
            background: #f0f0f1;
            color: #646970;
        }

        .epd-badges-cell {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            align-items: center;
        }

        /* ── Buttons ── */
        .epd-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid transparent;
            cursor: pointer;
            transition: background .15s, box-shadow .15s;
            white-space: nowrap;
            line-height: 1.4;
        }

        .epd-btn--sm {
            padding: 4px 10px;
            font-size: 12px;
        }

        .epd-btn--primary {
            background: #1d2327;
            color: #fff;
            border-color: #1d2327;
        }

        .epd-btn--primary:hover:not(:disabled) {
            background: #2c3338;
        }

        .epd-btn--primary:disabled {
            opacity: 0.35;
            cursor: not-allowed;
        }

        .epd-btn--ghost {
            background: none;
            color: #8c8f94;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 16px;
            line-height: 1;
            font-weight: 700;
        }

        .epd-btn--ghost:hover {
            background: #f0f0f1;
            color: #1d2327;
        }

        /* ── Actions cell ── */
        .epd-actions-cell {
            display: flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
        }

        /* ── Dropdown ── */
        .epd-dropdown {
            position: relative;
            display: inline-block;
        }

        .epd-dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: calc(100% + 4px);
            background: #fff;
            border: 1px solid #e2e4e7;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .08);
            min-width: 140px;
            z-index: 100;
            overflow: hidden;
        }

        .epd-dropdown.open .epd-dropdown-menu {
            display: block;
        }

        .epd-dropdown-menu a,
        .epd-dropdown-menu button {
            display: block;
            width: 100%;
            text-align: left;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 400;
            color: #1d2327;
            background: none;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }

        .epd-dropdown-menu a:hover,
        .epd-dropdown-menu button:hover {
            background: #fafbfc;
        }

        .epd-dropdown-danger {
            color: #d63638 !important;
        }

        .epd-dropdown-danger:hover {
            background: #fef2f2 !important;
        }

        /* ── Empty state ── */
        .epd-empty {
            padding: 48px 24px;
            text-align: center;
            color: #8c8f94;
            font-size: 14px;
            border: 1px dashed #e2e4e7;
            border-radius: 8px;
            margin-top: 12px;
        }

        /* ── Responsive ── */
        @media (max-width: 782px) {
            .epd-stats {
                flex-direction: column;
                gap: 12px;
            }

            .epd-sub-table-wrap {
                padding-left: 12px;
            }
        }
    </style>

    <script>
        jQuery(document).ready(function($) {
            const nonce = '<?php echo esc_js(wp_create_nonce("eikon_dashboard_action")); ?>';

            // ── Tab switching ──
            $('.epd-tab').on('click', function() {
                const tab = $(this).data('tab');
                $('.epd-tab').removeClass('epd-tab--active');
                $(this).addClass('epd-tab--active');
                $('.epd-panel').hide();
                $('#epd-panel-' + tab).show();
            });

            // ── Expand/collapse highlights ──
            $('.epd-expand-toggle').on('click', function() {
                const mandatId = $(this).data('mandat');
                if (!mandatId) return;
                const hlRow = $('#highlights-' + mandatId);
                const mandatRow = $('#mandat-row-' + mandatId);
                const chevron = $(this).find('.epd-chevron');
                hlRow.toggle();
                chevron.toggleClass('epd-chevron--open');
                mandatRow.toggleClass('epd-row-mandat--expanded');
            });

            // ── Dropdown menus ──
            $(document).on('click', '.epd-dropdown-trigger', function(e) {
                e.stopPropagation();
                const dd = $(this).closest('.epd-dropdown');
                const wasOpen = dd.hasClass('open');
                $('.epd-dropdown').removeClass('open');
                if (!wasOpen) dd.addClass('open');
            });
            $(document).on('click', function() {
                $('.epd-dropdown').removeClass('open');
            });

            // ── AJAX helper ──
            function eikonDashboardAction(actionType, postId, button, onSuccess) {
                button.prop('disabled', true).css('opacity', '0.5');
                $.post(ajaxurl, {
                    action: 'eikon_dashboard_action',
                    nonce: nonce,
                    action_type: actionType,
                    post_id: postId
                }, function(response) {
                    if (response.success) {
                        onSuccess();
                    } else {
                        alert("Erreur: " + response.data);
                        button.prop('disabled', false).css('opacity', '1');
                    }
                }).fail(function() {
                    alert("Erreur serveur.");
                    button.prop('disabled', false).css('opacity', '1');
                });
            }

            // ── Publish highlight project ──
            $(document).on('click', '.action-publish-project', function() {
                const btn = $(this);
                const projectId = btn.data('id');
                const mandatId = btn.data('mandat');
                const row = $('#proj-item-' + projectId);
                const mandatRow = $('#mandat-row-' + mandatId);

                eikonDashboardAction('publish_project', projectId, btn, function() {
                    // Update status badge
                    row.find('.epd-badge').removeClass('epd-badge--yellow').addClass('epd-badge--green').text('Publié');
                    btn.remove();

                    // Update highlight counter badge on mandat row
                    var hlTable = $('#highlights-' + mandatId);
                    var total = hlTable.find('tbody tr').length;
                    var published = hlTable.find('.epd-badge--green').length;
                    mandatRow.find('.epd-badge').text(published + '/' + total + ' publiés');

                    // Enable publish-mandat if all highlights are now published
                    if (hlTable.find('.action-publish-project').length === 0) {
                        mandatRow.find('.action-publish-mandat').prop('disabled', false).removeAttr('title');
                    }
                });
            });

            // ── Publish Mandat ──
            $(document).on('click', '.action-publish-mandat', function() {
                const btn = $(this);
                const mandatId = btn.data('id');
                const mandatRow = $('#mandat-row-' + mandatId);
                const hlRow = $('#highlights-' + mandatId);

                if (confirm('Publier ce mandat ? Les projets non-highlights seront archivés.')) {
                    eikonDashboardAction('publish_mandat', mandatId, btn, function() {
                        mandatRow.fadeOut(function() {
                            $(this).remove();
                        });
                        hlRow.fadeOut(function() {
                            $(this).remove();
                        });
                    });
                }
            });

            // ── Archive Mandat ──
            $(document).on('click', '.action-archive-mandat', function() {
                const btn = $(this);
                const mandatId = btn.data('id');
                const mandatRow = $('#mandat-row-' + mandatId);
                const hlRow = $('#highlights-' + mandatId);

                if (confirm('Archiver ce mandat ?')) {
                    eikonDashboardAction('archive_mandat', mandatId, btn, function() {
                        mandatRow.fadeOut(function() {
                            $(this).remove();
                        });
                        hlRow.fadeOut(function() {
                            $(this).remove();
                        });
                    });
                }
            });

            // ── Standalone Publish ──
            $(document).on('click', '.action-standalone-publish', function() {
                const btn = $(this);
                const projectId = btn.data('id');
                const row = $('#standalone-proj-' + projectId);

                eikonDashboardAction('publish_standalone_project', projectId, btn, function() {
                    row.fadeOut(function() {
                        $(this).remove();
                    });
                });
            });

            // ── Standalone Archive ──
            $(document).on('click', '.action-standalone-archive', function() {
                const btn = $(this);
                const projectId = btn.data('id');
                const row = $('#standalone-proj-' + projectId);

                if (confirm('Archiver ce projet ?')) {
                    eikonDashboardAction('archive_project', projectId, btn, function() {
                        row.fadeOut(function() {
                            $(this).remove();
                        });
                    });
                }
            });

            // ── Archive highlight project ──
            $(document).on('click', '.action-archive-project', function() {
                const btn = $(this);
                const projectId = btn.data('id');
                const row = $('#proj-item-' + projectId);
                const mandatRow = btn.closest('.epd-row-highlights').prev('.epd-row-mandat');
                const mandatId = mandatRow.attr('id') ? mandatRow.attr('id').replace('mandat-row-', '') : null;

                if (confirm('Archiver ce projet ?')) {
                    eikonDashboardAction('archive_project', projectId, btn, function() {
                        row.fadeOut(function() {
                            $(this).remove();

                            // Update counter
                            if (mandatId) {
                                var hlTable = $('#highlights-' + mandatId);
                                var total = hlTable.find('tbody tr').length;
                                var published = hlTable.find('.epd-badge--green').length;
                                var mandatRowEl = $('#mandat-row-' + mandatId);

                                if (total === 0) {
                                    mandatRowEl.find('.epd-badge').removeClass('epd-badge--blue').addClass('epd-badge--neutral').text('Aucun');
                                    hlTable.closest('.epd-row-highlights').remove();
                                    mandatRowEl.find('.epd-chevron').remove();
                                } else {
                                    mandatRowEl.find('.epd-badge').text(published + '/' + total + ' publiés');
                                }

                                // Enable publish mandat if no more pending highlights
                                if (hlTable.find('.action-publish-project').length === 0) {
                                    mandatRowEl.find('.action-publish-mandat').prop('disabled', false).removeAttr('title');
                                }
                            }
                        });
                    });
                }
            });
        });
    </script>
<?php
}
