<?php
/**
 * Script WP-CLI pour supprimer l'enseignant (post_author avec le rôle 'teacher') 
 * du champ répéteur ACF 'project_authors' sur les projets.
 * 
 * Utilisation (dry run) : wp eval-file clean_project_authors.php
 * Utilisation (réelle)  : wp eval-file clean_project_authors.php --execute
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    die( 'Ce script doit être exécuté via WP-CLI.' );
}

$args = isset($args) ? $args : []; // arguments passés après le nom du fichier
$execute = in_array('--execute', $args);

if (!$execute) {
    WP_CLI::log("==========================================");
    WP_CLI::log("             MODE DRY RUN");
    WP_CLI::log(" Ajoutez l'argument --execute pour appliquer");
    WP_CLI::log(" les modifications dans la base de données.");
    WP_CLI::log(" Exemple: wp eval-file clean_project_authors.php --execute");
    WP_CLI::log("==========================================");
}

// 1. Récupérer tous les projets (peu importe le statut)
$query = new WP_Query([
    'post_type'      => 'project',
    'posts_per_page' => -1,
    'post_status'    => 'any',
]);

$total_modified = 0;
$total_projects = $query->post_count;

WP_CLI::log("Analyse de $total_projects projets en cours...\n");

foreach ($query->posts as $project) {
    $post_id = $project->ID;
    $post_author_id = $project->post_author;
    
    // 2. Vérifier si l'auteur du post a bien le rôle 'teacher'
    $author_user = get_userdata($post_author_id);
    if (!$author_user || !in_array('teacher', (array) $author_user->roles)) {
        continue;
    }
    
    // 3. Récupérer le champ répéteur ACF
    $authors = get_field('project_authors', $post_id);
    if (empty($authors) || !is_array($authors)) {
        continue;
    }
    
    $new_authors = [];
    $modified = false;
    
    // 4. Parcourir les auteurs ACF
    foreach ($authors as $row) {
        $acf_user = $row['author'];
        // Selon le format de retour (array ou ID), on extrait l'ID
        $acf_user_id = is_array($acf_user) ? $acf_user['ID'] : $acf_user;
        
        if ($acf_user_id == $post_author_id) {
            $modified = true;
        } else {
            // On garde les autres auteurs
            $new_authors[] = [
                'author' => $acf_user_id
            ];
        }
    }
    
    // 5. Mettre à jour si des modifications ont eu lieu
    if ($modified) {
        $total_modified++;
        if ($execute) {
            update_field('project_authors', $new_authors, $post_id);
            WP_CLI::success("Projet #{$post_id} '{$project->post_title}' : Enseignant (ID: $post_author_id) retiré des auteurs ACF.");
        } else {
            WP_CLI::log("[DRY RUN] Projet #{$post_id} '{$project->post_title}' : Enseignant (ID: $post_author_id) serait retiré.");
        }
    }
}

WP_CLI::log("\n==========================================");
if (!$execute) {
    WP_CLI::log("Bilan [DRY RUN] : $total_modified projets seraient modifiés.");
} else {
    WP_CLI::success("Bilan : $total_modified projets ont été modifiés avec succès.");
}
