<?php

/**
 * Class Eikon_Security
 *
 * Gère la sécurité globale du CMS Headless.
 */
class Eikon_Security
{
    public function __construct()
    {
      // Restreindre l'accès à l'API REST pour les utilisateurs non authentifiés
        add_filter('rest_authentication_errors', array($this, 'restrict_rest_api_to_authenticated_users'));

      // Désactiver l'introspection GraphQL en production
        add_filter('graphql_enable_introspection', array($this, 'disable_graphql_introspection'));
    }

  /**
   * Bloque l'accès à l'API REST pour les visiteurs non connectés.
   *
   * @param mixed $result Error result.
   * @return mixed WP_Error if unauthenticated, else $result.
   */
    public function restrict_rest_api_to_authenticated_users($result)
    {
        if (!empty($result)) {
            return $result;
        }
        if (!is_user_logged_in()) {
            return new WP_Error(
                'rest_not_logged_in',
                'Accès refusé. Vous devez être authentifié pour utiliser cette API.',
                array('status' => 401)
            );
        }
        return $result;
    }

  /**
   * Désactive l'introspection GraphQL en production pour éviter la fuite du schéma.
   *
   * @param bool $is_enabled Whether introspection is enabled.
   * @return bool
   */
    public function disable_graphql_introspection(bool $is_enabled)
    {
      // Active l'introspection uniquement en environnement de développement
        if (defined('WP_ENV') && WP_ENV === 'development') {
            return true;
        }

      // Si l'utilisateur est authentifié avec le rôle approprié, on peut l'autoriser
        if (is_user_logged_in() && current_user_can('edit_posts')) {
            return true;
        }

        return false;
    }
}

new Eikon_Security();
