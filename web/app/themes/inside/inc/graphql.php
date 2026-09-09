<?php

/**
 * Class Eikon_GraphQL
 *
 * Gère les personnalisations de WPGraphQL (visibilité des brouillons, filtres personnalisés, etc.).
 */
class Eikon_GraphQL
{
    public function __construct()
    {
        add_filter('graphql_PostObjectsConnectionOrderbyEnum_values', array($this, 'add_random_orderby'));
        add_filter('graphql_object_visibility', array($this, 'make_draft_projects_public'), 10, 5);
        add_filter('graphql_object_visibility', array($this, 'make_all_users_visible'), 10, 3);
        add_filter('graphql_connection_query_args', array($this, 'remove_published_posts_restriction_for_users'), 10, 2);
        add_action('pre_get_posts', array($this, 'allow_resolving_draft_projects_by_slug'));
        add_filter('graphql_resolve_field', array($this, 'clean_media_captions'), 20, 7);
        add_action('graphql_register_types', array($this, 'register_custom_graphql_types'));
    }

    public function add_random_orderby($values)
    {
        $values['RAND'] = [
        'value' => 'rand',
        'description' => __('Order randomly', 'wp-graphql'),
        ];
        return $values;
    }

    public function make_draft_projects_public($visibility, $model_name, $data, $owner, $current_user)
    {
        if ('PostObject' !== $model_name || !($data instanceof \WP_Post)) {
            return $visibility;
        }

        if (
            'project' === $data->post_type &&
            in_array($data->post_status, ['draft', 'pending', 'future'], true)
        ) {
            return 'public';
        }

        if ('mandat' === $data->post_type && 'publish' === $data->post_status) {
            return 'public';
        }

        if ('attachment' === $data->post_type && !empty($data->post_parent)) {
            $parent = get_post((int) $data->post_parent);
            if (
                $parent instanceof \WP_Post &&
                'project' === $parent->post_type &&
                in_array($parent->post_status, ['draft', 'pending', 'future'], true)
            ) {
                return 'public';
            }
        }

        return $visibility;
    }

    public function make_all_users_visible($visibility, $model_name, $data)
    {
        if ('UserObject' === $model_name && $data instanceof \WP_User) {
            return 'public';
        }
        return $visibility;
    }

    public function remove_published_posts_restriction_for_users($query_args, $connection_resolver)
    {
        if ($connection_resolver instanceof \WPGraphQL\Data\Connection\UserConnectionResolver) {
            unset($query_args['has_published_posts']);
        }
        return $query_args;
    }

    public function allow_resolving_draft_projects_by_slug($query)
    {
        if (!defined('GRAPHQL_REQUEST') || !GRAPHQL_REQUEST) {
            return;
        }

        $post_type = $query->get('post_type');
        $is_project = $post_type === 'project' || (is_array($post_type) && in_array('project', $post_type, true));
        if (!$is_project) {
            return;
        }

        if (empty($query->get('name')) && empty($query->get('p'))) {
            return;
        }

        $query->set('post_status', ['publish', 'draft', 'pending', 'future']);
    }

    public function clean_media_captions($result, $source, $args, $context, $info, $type_name, $field_key)
    {
        if ('MediaItem' !== $type_name || 'caption' !== $field_key) {
            return $result;
        }

        if (!is_string($result) || '' === $result) {
            return $result;
        }

        $plain_text = wp_strip_all_tags($result, true);
        $plain_text = html_entity_decode($plain_text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
        $plain_text = trim(preg_replace('/\s+/u', ' ', $plain_text));

        return '' === $plain_text ? null : $plain_text;
    }

    public function register_custom_graphql_types()
    {
        register_graphql_input_type('MandatLinkedProjectsWhereArgs', [
        'description' => __('Filter arguments for mandat linked projects.', 'wp-graphql'),
        'fields' => [
        'highlight' => [
          'type' => 'Boolean',
          'description' => __('Filter by mandate highlight flag.', 'wp-graphql'),
        ],
        ],
        ]);

        register_graphql_field('Project', 'currentMandatId', [
        'type' => 'Int',
        'description' => __('Selected current mandate ID.', 'wp-graphql'),
        'resolve' => function ($source) {
            if (empty($source->databaseId)) {
                return null;
            }
            $mandat_id = absint(get_post_meta((int) $source->databaseId, 'eikon_current_mandat_id', true));
            return $mandat_id > 0 ? $mandat_id : null;
        },
        ]);

        register_graphql_field('Project', 'currentMandat', [
        'type' => 'Mandat',
        'description' => __('Selected current mandate.', 'wp-graphql'),
        'resolve' => function ($source) {
            if (empty($source->databaseId)) {
                return null;
            }
            $mandat_id = absint(get_post_meta((int) $source->databaseId, 'eikon_current_mandat_id', true));
            if ($mandat_id <= 0) {
                return null;
            }
            $mandat = get_post($mandat_id);
            return $mandat instanceof \WP_Post ? $mandat : null;
        },
        ]);

        register_graphql_field('Project', 'mandatHighlight', [
        'type' => 'Boolean',
        'description' => __('Whether this project is highlighted in its current mandate.', 'wp-graphql'),
        'resolve' => function ($source) {
            if (empty($source->databaseId)) {
                return false;
            }
            return '1' === (string) get_post_meta((int) $source->databaseId, 'eikon_mandat_highlight', true);
        },
        ]);

        register_graphql_field('Mandat', 'linkedProjects', [
        'type'        => ['list_of' => 'Project'],
        'description' => __('Projects linked to this mandate via eikon_current_mandat_id.', 'wp-graphql'),
        'args'        => [
        'where' => [
          'type' => 'MandatLinkedProjectsWhereArgs',
        ],
        ],
        'resolve'     => function ($source, $args) {
            if (empty($source->databaseId)) {
                return [];
            }

            $meta_query = [[
            'key'     => 'eikon_current_mandat_id',
            'value'   => (int) $source->databaseId,
            'compare' => '=',
            'type'    => 'NUMERIC',
            ]];

            if (isset($args['where']['highlight'])) {
                if (true === $args['where']['highlight']) {
                    $meta_query[] = [
                    'key'     => 'eikon_mandat_highlight',
                    'value'   => '1',
                    'compare' => '=',
                    ];
                } else {
                    $meta_query[] = [
                    'relation' => 'OR',
                    [
                    'key'     => 'eikon_mandat_highlight',
                    'compare' => 'NOT EXISTS',
                    ],
                    [
                    'key'     => 'eikon_mandat_highlight',
                    'value'   => '1',
                    'compare' => '!=',
                    ],
                    ];
                }
            }

            $posts = get_posts([
            'post_type'      => 'project',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_status'    => ['publish', 'draft', 'pending', 'future'],
            'meta_query'     => $meta_query,
            ]);

            if (empty($posts)) {
                return [];
            }

            return array_map(
                fn($post) => new \WPGraphQL\Model\Post($post),
                $posts
            );
        },
        ]);
    }
}

new Eikon_GraphQL();
