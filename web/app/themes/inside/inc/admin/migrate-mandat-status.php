<?php

/**
 * One-shot migration: Convert ACF mandat_status to native post_status
 *
 * Converts:
 *   - mandat_status = "en_cours" → post_status = "open"
 *   - mandat_status = "termine"  → post_status = "in_review"
 *   - Published mandats without mandat_status → remain "publish"
 *
 * This migration runs once and displays an admin notice with results.
 * After successful execution, it marks itself as done via an option.
 *
 * @package Inside\Migration
 */

// Only run for administrators
if (!current_user_can('manage_options')) {
    return;
}

// Check if migration has already been completed
if (get_option('eikon_mandat_status_migrated')) {
    return;
}

/**
 * Show admin notice with migration button.
 */
function eikon_mandat_migration_notice()
{
    // Check if migration was just completed
    $result = get_transient('eikon_mandat_migration_result');
    if ($result) {
        delete_transient('eikon_mandat_migration_result');
        $class = 'notice notice-success is-dismissible';
        echo '<div class="' . $class . '"><p><strong>Migration terminée !</strong> ' . esc_html($result) . '</p></div>';
        return;
    }

    // Show migration button
    $url = wp_nonce_url(
        admin_url('admin-post.php?action=eikon_migrate_mandat_status'),
        'eikon_migrate_mandat_status'
    );
    ?>
    <div class="notice notice-warning">
        <p>
            <strong>Migration requise :</strong>
            Le champ ACF « Statut du mandat » a été remplacé par des statuts natifs WordPress.
            Les mandats existants doivent être migrés.
        </p>
        <p>
            <a href="<?php echo esc_url($url); ?>" class="button button-primary"
               onclick="return confirm('Êtes-vous sûr de vouloir lancer la migration ? Cette action est irréversible.');">
                Lancer la migration
            </a>
        </p>
    </div>
    <?php
}
add_action('admin_notices', 'eikon_mandat_migration_notice');

/**
 * Handle the migration action.
 */
function eikon_handle_mandat_status_migration()
{
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    check_admin_referer('eikon_migrate_mandat_status');

    global $wpdb;

    $en_cours_count = 0;
    $termine_count = 0;
    $skipped_count = 0;

    // Get all mandats
    $mandats = get_posts(array(
        'post_type'      => 'mandat',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'no_found_rows'  => true,
    ));

    foreach ($mandats as $mandat) {
        $acf_status = get_post_meta($mandat->ID, 'mandat_status', true);

        if ('en_cours' === $acf_status) {
            $wpdb->update(
                $wpdb->posts,
                array('post_status' => 'open'),
                array('ID' => $mandat->ID),
                array('%s'),
                array('%d')
            );
            clean_post_cache($mandat->ID);
            $en_cours_count++;
        } elseif ('termine' === $acf_status) {
            $wpdb->update(
                $wpdb->posts,
                array('post_status' => 'in_review'),
                array('ID' => $mandat->ID),
                array('%s'),
                array('%d')
            );
            clean_post_cache($mandat->ID);
            $termine_count++;
        } else {
            $skipped_count++;
        }
    }

    // Mark migration as done
    update_option('eikon_mandat_status_migrated', true);

    $message = sprintf(
        '%d mandat(s) « En cours » → « Ouvert », %d mandat(s) « Terminé » → « En relecture », %d mandat(s) non modifié(s).',
        $en_cours_count,
        $termine_count,
        $skipped_count
    );

    set_transient('eikon_mandat_migration_result', $message, 120);

    wp_safe_redirect(admin_url('edit.php?post_type=mandat'));
    exit;
}
add_action('admin_post_eikon_migrate_mandat_status', 'eikon_handle_mandat_status_migration');
