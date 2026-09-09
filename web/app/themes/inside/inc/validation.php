<?php

/**
 * Validation des projets avant sauvegarde
 */

add_action('wp_insert_post_data', function ($data, $postarr) {
    if (!isset($data['post_type']) || $data['post_type'] !== 'project') {
        return $data;
    }

    if (current_user_can('manage_options')) {
        return $data;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $data;
    }

    if (in_array($data['post_status'], ['trash', 'auto-draft'])) {
        return $data;
    }

    $errors = [];

    if (empty($data['post_title']) || trim($data['post_title']) === '') {
        $errors[] = 'Le titre du projet est obligatoire.';
    }

    if (!empty($errors)) {
        if (in_array($data['post_status'], ['publish', 'pending', 'future'])) {
            $data['post_status'] = 'draft';
            add_filter('redirect_post_location', function ($location) use ($errors) {
                return add_query_arg('eikon_validation_error', urlencode(implode('|', $errors)), $location);
            });
        }
    }

    return $data;
}, 10, 2);

add_action('admin_notices', function () {
    if (isset($_GET['eikon_validation_error'])) {
        $errors = explode('|', urldecode(sanitize_text_field($_GET['eikon_validation_error'])));
        echo '<div class="notice notice-error is-dismissible">';
        foreach ($errors as $error) {
            echo '<p><strong>Erreur de validation :</strong> ' . esc_html($error) . '</p>';
        }
        echo '<p>Le projet a été enregistré en tant que brouillon.</p>';
        echo '</div>';
    }
});

/**
 * Validation ACF spécifique (ex: champs requis manquants)
 */
add_action('acf/validate_save_post', function () {
    if (!isset($_POST['post_type']) || $_POST['post_type'] !== 'project') {
        return;
    }

    if (current_user_can('manage_options')) {
        return;
    }

    // Exemple : validation d'un champ s'il existe dans $_POST['acf']
    // if (empty($_POST['acf']['field_xxxx'])) {
    //     acf_add_validation_error('acf[field_xxxx]', 'Ce champ est obligatoire.');
    // }
});
