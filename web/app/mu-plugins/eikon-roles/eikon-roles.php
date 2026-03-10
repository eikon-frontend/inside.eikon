<?php

/**
 * Plugin Name: Eikon Roles & Capabilities
 * Description: Custom role and capability management for Eikon's headless WordPress
 * Version: 1.0.0
 * Author: Eikon
 *
 * @package EikonRoles
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

/**
 * Initialize custom roles and capabilities on WordPress init
 * Hook into init to ensure WordPress is properly loaded
 */
function eikon_init_roles()
{
  // Create Editor role (full content management, no settings access)
  eikon_create_editor_role();

  // Create Teacher role
  eikon_create_teacher_role();

  // Create Student role
  eikon_create_student_role();

  // Update Admin capabilities
  eikon_configure_admin_capabilities();

  // Permanently remove all unwanted roles - try multiple naming conventions
  eikon_cleanup_unwanted_roles();
}
add_action('init', 'eikon_init_roles', 5);

/**
 * Remove unwanted roles and legacy naming variations
 */
function eikon_cleanup_unwanted_roles()
{
  $roles_to_remove = array(
    'supervisor',
    'subscriber',
    'responsable_de_branche',
    'responsable-de-branche',
    'responsable',
    'branch_manager',
    'branch-manager',
  );

  foreach ($roles_to_remove as $role_name) {
    remove_role($role_name);
  }

  // Clean up any lingering roles with 'responsable' or 'branch' in the name
  global $wp_roles;
  if ($wp_roles) {
    $all_roles = $wp_roles->get_names();
    $protected_roles = array('teacher', 'student', 'editor', 'administrator', 'super_admin');
    foreach ($all_roles as $role_name) {
      if (!in_array($role_name, $protected_roles, true)) {
        if (stripos($role_name, 'responsable') !== false || stripos($role_name, 'branch') !== false) {
          remove_role($role_name);
        }
      }
    }
  }
}

/**
 * Create Editor role with full content management capabilities
 *
 * Editors can:
 * - Access all post types (posts, projects, pages, departments)
 * - Create, edit, publish, and delete content
 * - Upload and manage media
 * - Manage access to published content
 *
 * Editors CANNOT:
 * - Manage users
 * - Access WordPress settings
 * - Activate plugins or manage themes
 * - Access tools
 */
function eikon_create_editor_role()
{
  $editor_caps = array(
    'read'                      => true,
    'read_posts'                => true,
    'read_pages'                => true,
    'edit_posts'                => true,
    'edit_others_posts'         => true,
    'edit_published_posts'      => true,
    'publish_posts'             => true,
    'delete_posts'              => true,
    'delete_others_posts'       => true,
    'delete_published_posts'    => true,
    'manage_posts'              => true,
    'create_posts'              => true,
    'edit_pages'                => true,
    'edit_others_pages'         => true,
    'edit_published_pages'      => true,
    'publish_pages'             => true,
    'delete_pages'              => true,
    'delete_others_pages'       => true,
    'delete_published_pages'    => true,
    'manage_pages'              => true,
    'create_pages'              => true,
    'upload_files'              => true,
    'edit_files'                => true,
  );

  // Check if role already exists
  $editor_role = get_role('editor');
  if ($editor_role) {
    // Role exists, update its capabilities
    foreach ($editor_caps as $cap => $grant) {
      if ($grant) {
        $editor_role->add_cap($cap);
      } else {
        $editor_role->remove_cap($cap);
      }
    }
  } else {
    // Create new role with specified capabilities
    add_role('editor', 'Editor', $editor_caps);
  }
}

/**
 * Create Teacher role with appropriate capabilities
 *
 * Teachers can:
 * - Access post and project post types
 * - Create new posts/projects
 * - Edit only their own posts/projects
 * - Publish posts/projects to draft or pending review only (not direct publish)
 * - Read posts/projects by other teachers
 * - Upload media
 * - Cannot access settings or user management
 */
function eikon_create_teacher_role()
{
  $teacher_caps = array(
    'read'                      => true,
    'read_posts'                => true,
    'edit_posts'                => true,
    'edit_others_posts'         => false,    // Can read but not edit others' posts
    'publish_posts'             => false,    // Draft/pending only
    'delete_posts'              => true,
    'delete_published_posts'    => false,
    'manage_posts'              => true,     // Required for menu to appear
    'create_posts'              => true,
    'upload_files'              => true,
  );

  // Check if role already exists
  $teacher_role = get_role('teacher');
  if ($teacher_role) {
    // Role exists, update its capabilities
    foreach ($teacher_caps as $cap => $grant) {
      if ($grant) {
        $teacher_role->add_cap($cap);
      } else {
        $teacher_role->remove_cap($cap);
      }
    }
  } else {
    // Create new role with only specified capabilities
    add_role('teacher', 'Enseignant / enseignante', $teacher_caps);
  }
}

/**
 * Create Student role with restricted capabilities
 *
 * Students can:
 * - Create and manage their own projects only
 * - Edit only their own projects
 * - Save as draft or pending review (no direct publish)
 * - Upload and view only their own media
 *
 * Cannot:
 * - Access posts or other content types
 * - See other students' projects
 * - See other users' media
 * - Directly publish content
 * - Access settings or user management
 */
function eikon_create_student_role()
{
  // Note: Project CPT uses capability_type='post', so we use post capabilities
  $student_caps = array(
    'read'                      => true,
    'read_posts'                => true,      // Required for REST API and menu visibility
    'edit_posts'                => true,      // Edit own projects
    'edit_others_posts'         => false,
    'edit_published_posts'      => true,
    'delete_posts'              => true,      // Needed for menu visibility
    'delete_published_posts'    => false,
    'create_posts'              => true,      // Create projects
    'publish_posts'             => false,     // Draft/pending only
    'manage_posts'              => true,      // Required for menu to appear in admin
    'upload_files'              => true,
    'edit_files'                => true,
  );

  // Check if role already exists
  $student_role = get_role('student');
  if ($student_role) {
    // Role exists, update its capabilities
    foreach ($student_caps as $cap => $grant) {
      if ($grant) {
        $student_role->add_cap($cap);
      } else {
        $student_role->remove_cap($cap);
      }
    }
  } else {
    // Create the role
    add_role('student', 'Étudiant / étudiante', $student_caps);
  }
}

/**
 * Configure Admin role capabilities
 *
 * Admins can:
 * - Edit all content (posts, projects, pages, departments)
 * - Publish/unpublish content
 * - Manage users
 * - Upload media
 * - But CANNOT access settings, plugins, or themes
 */
function eikon_configure_admin_capabilities()
{
  $admin = get_role('administrator');
  if (!$admin) {
    return;
  }

  // Ensure administrator has user management capabilities
  $admin->add_cap('manage_users');
  $admin->add_cap('list_users');
  $admin->add_cap('create_users');
  $admin->add_cap('edit_users');
  $admin->add_cap('delete_users');
  $admin->add_cap('promote_users');

  // Ensure administrator can manage all content
  $admin->add_cap('edit_posts');
  $admin->add_cap('edit_published_posts');
  $admin->add_cap('publish_posts');
  $admin->add_cap('delete_posts');
  $admin->add_cap('delete_published_posts');

  $admin->add_cap('edit_projects');
  $admin->add_cap('edit_published_projects');
  $admin->add_cap('publish_projects');
  $admin->add_cap('delete_projects');
  $admin->add_cap('delete_published_projects');

  // Upload media
  $admin->add_cap('upload_files');
}

/**
 * Post content restrictions for teachers and students
 *
 * Teachers should see all posts (they can read others' but not edit)
 * Students should only see their own projects (not posts at all)
 */
function eikon_restrict_manage_posts($query)
{
  if (!is_admin() || !$query->is_main_query()) {
    return;
  }

  $current_user = wp_get_current_user();

  // If user is super admin or administrator, show all posts
  if (in_array('administrator', $current_user->roles, true)) {
    return;
  }

  // Students can only see their own projects
  if (in_array('student', $current_user->roles, true)) {
    $post_types = $query->get('post_type');

    // If trying to view posts (default post type), restrict to projects only
    if (empty($post_types) || $post_types === 'post') {
      $query->set('post_type', 'project');
      $query->set('author', $current_user->ID);
      return;
    }

    // If explicitly requesting projects, filter by author
    if (!is_array($post_types)) {
      $post_types = array($post_types);
    }

    if (in_array('project', $post_types, true)) {
      $query->set('author', $current_user->ID);
    }
  }
}
add_action('pre_get_posts', 'eikon_restrict_manage_posts');

/**
 * Prevent draft/pending posts from being published by teacher/student
 *
 * These roles cannot use the "Publish" button
 */
function eikon_remove_publish_capability($caps, $cap, $user_id)
{
  // Get the user
  $user = get_userdata($user_id);
  if (!$user) {
    return $caps;
  }

  // Check if user is teacher or student
  $is_teacher = in_array('teacher', $user->roles, true);
  $is_student = in_array('student', $user->roles, true);

  if ($is_teacher || $is_student) {
    // For publish_posts capability (covers both posts and projects since project uses capability_type='post')
    if ($cap === 'publish_posts') {
      // Deny publish capability - they can only save as draft/pending
      $caps[] = 'do_not_allow';
    }
  }

  return $caps;
}
add_filter('user_has_cap', 'eikon_remove_publish_capability', 10, 3);

/**
 * Map meta capabilities to ensure proper WordPress permission checks
 * Required for menu visibility and AJAX operations
 */
function eikon_map_meta_cap($caps, $cap, $user_id, $args)
{
  $user = get_user_by('id', $user_id);
  if (!$user || !in_array('student', $user->roles, true)) {
    return $caps;
  }

  // For students, map edit_posts and manage_posts for menu visibility
  if ('edit_posts' === $cap || 'manage_posts' === $cap) {
    return array('read_posts', 'edit_posts', 'manage_posts');
  }

  return $caps;
}
add_filter('map_meta_cap', 'eikon_map_meta_cap', 10, 4);

/**
 * Remove admin menu items for roles that shouldn't see them
 *
 * Teachers should see: Posts, Projects, Media, Profile
 * Students should see: Projects, Media, Profile
 */
function eikon_remove_admin_menu_items()
{
  $current_user = wp_get_current_user();

  // If user is administrator or super admin, show everything
  if (in_array('administrator', $current_user->roles, true)) {
    return;
  }

  // For editors: remove settings, users, tools, and theme-related items
  if (in_array('editor', $current_user->roles, true)) {
    remove_menu_page('index.php'); // Dashboard
    remove_menu_page('tools.php'); // Tools
    remove_menu_page('options-general.php'); // Settings
    remove_menu_page('themes.php'); // Appearance
    remove_menu_page('plugins.php'); // Plugins

    // Keep: edit.php (Posts)
    // Keep: edit.php?post_type=page (Pages)
    // Keep: edit.php?post_type=project (Projects)
    // Keep: edit.php?post_type=department (Departments)
    // Keep: upload.php (Media)
  }

  // For students: remove everything except projects, media, and profile
  if (in_array('student', $current_user->roles, true)) {
    remove_menu_page('index.php'); // Dashboard
    remove_menu_page('edit.php'); // Posts
    remove_menu_page('edit.php?post_type=page'); // Pages
    remove_menu_page('edit.php?post_type=department'); // Departments
    remove_menu_page('tools.php'); // Tools
    remove_menu_page('options-general.php'); // Settings
    remove_menu_page('users.php'); // Users

    // Keep: edit.php?post_type=project (Projects)
    // Keep: upload.php (Media)
  }

  // For teachers: remove settings and theme-related items
  if (in_array('teacher', $current_user->roles, true)) {
    remove_menu_page('index.php'); // Dashboard
    remove_menu_page('edit.php?post_type=page'); // Pages
    remove_menu_page('edit.php?post_type=department'); // Departments
    remove_menu_page('tools.php'); // Tools
    remove_menu_page('options-general.php'); // Settings
    remove_menu_page('themes.php'); // Appearance
    remove_menu_page('plugins.php'); // Plugins

    // Keep: edit.php (Posts)
    // Keep: edit.php?post_type=project (Projects)
    // Keep: upload.php (Media)
    // Keep: profile.php is in Users submenu
  }
}
add_action('admin_menu', 'eikon_remove_admin_menu_items', 999);

/**
 * Hide Users menu for all non-administrator users
 */
function eikon_remove_users_menu()
{
  if (!current_user_can('manage_users')) {
    remove_menu_page('users.php');
  }
}
add_action('admin_menu', 'eikon_remove_users_menu', 999);

/**
 * Prevent unwanted default role assignment
 * Ensures new users are not assigned editor or subscriber roles
 */
function eikon_set_default_user_role()
{
  // Set the default role for new user registrations to 'student'
  update_option('default_role', 'student');
}
add_action('init', 'eikon_set_default_user_role', 10);

/**
 * Filter to prevent old roles from being created via API or plugins
 * This runs on user registration to ensure no one gets assigned unwanted roles
 */
function eikon_sanitize_user_roles($user_id)
{
  $user = get_user_by('id', $user_id);
  if (!$user) {
    return;
  }

  $unwanted_roles = array('subscriber', 'responsable_de_branche', 'branch_manager');
  $user_roles = $user->roles;

  // If user has unwanted roles, remove them
  foreach ($unwanted_roles as $role) {
    if (in_array($role, $user_roles, true)) {
      $user->remove_role($role);
    }
  }
}
// Hook to clean up user roles on save/registration
add_action('user_register', 'eikon_sanitize_user_roles', 10, 1);
add_action('save_user_data', 'eikon_sanitize_user_roles', 10, 1);

/**
 * Restrict media library to show only user's uploads for teachers and students
 *
 * Teachers and students should only see media they uploaded
 * Admins see all media
 */
function eikon_restrict_media_library($query)
{
  // Only apply in admin
  if (!is_admin()) {
    return;
  }

  $current_user = wp_get_current_user();

  // If user is super admin or administrator, show all media
  if (!$current_user->ID || in_array('administrator', $current_user->roles, true)) {
    return;
  }

  // Only filter attachment queries
  if ($query->get('post_type') !== 'attachment') {
    return;
  }

  // For teacher/student roles, restrict to own uploads only
  if (in_array('teacher', $current_user->roles, true) || in_array('student', $current_user->roles, true)) {
    $query->set('author', $current_user->ID);
  }
}

/**
 * Allow students and teachers to read their own draft/pending projects
 *
 * This enables the preview/view link in the publish box for draft posts.
 * WordPress checks the `read_post` capability before showing the preview/visit link.
 */
function eikon_allow_draft_post_reading($caps, $cap, $user_id, $args)
{
  // Only handle read_post capability mapping
  if ($cap !== 'read_post') {
    return $caps;
  }

  $user = get_userdata($user_id);
  if (!$user) {
    return $caps;
  }

  // Check if user is student or teacher
  $is_student = in_array('student', $user->roles, true);
  $is_teacher = in_array('teacher', $user->roles, true);

  if (!$is_student && !$is_teacher) {
    return $caps;
  }

  // Get the post from args (typically args[0] is the post ID)
  $post_id = isset($args[0]) ? (int) $args[0] : 0;
  if ($post_id === 0) {
    return $caps;
  }

  $post = get_post($post_id);
  if (!$post || $post->post_type !== 'project') {
    return $caps;
  }

  // Allow students/teachers to read their own draft/pending/future projects
  if (in_array($post->post_status, ['draft', 'pending', 'future'], true)) {
    if ((int) $post->post_author === $user_id) {
      // Map to read_posts capability since they own the post
      return array('read_posts');
    }
  }

  return $caps;
}
add_filter('map_meta_cap', 'eikon_allow_draft_post_reading', 10, 4);


add_action('pre_get_posts', 'eikon_restrict_media_library');
