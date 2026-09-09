<?php
/**
 * PHPUnit bootstrap file
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Pour tester les hooks WordPress (wp_insert_post_data), l'intégration de la librairie 
// de test WordPress officielle (wp-tests) ou wp-browser serait nécessaire ici.
// Ce fichier sert de point d'entrée pour lancer les tests via la CI.
