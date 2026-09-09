<?php
/**
 * PHPStan stubs for WPGraphQL and ACF functions.
 */

namespace {
    if (!function_exists('get_field')) {
        function get_field($selector, $post_id = false, $format_value = true) { return null; }
    }
    if (!function_exists('acf_get_field')) {
        function acf_get_field($key) { return null; }
    }
    if (!function_exists('register_graphql_field')) {
        function register_graphql_field($type_name, $field_name, $config) {}
    }
    if (!function_exists('register_graphql_input_type')) {
        function register_graphql_input_type($type_name, $config) {}
    }
}

namespace WPGraphQL\Data\Connection {
    class UserConnectionResolver {}
}

namespace WPGraphQL\Model {
    class Post {
        public function __construct($post) {}
    }
}
