# Eikon Roles & Capabilities Plugin

A custom WordPress must-use plugin that manages user roles and capabilities for the Eikon headless WordPress installation.

This plugin **replaces the Members plugin** with a lightweight, custom solution tailored to Eikon's specific architectural and educational needs.

## Overview

**Available roles**:

- **Super Admin**: Full administrative access (unchanged from WordPress default)
- **Administrator**: Manage all content and users (restricted from settings/plugins/themes)
- **Teacher** (`teacher`, "Enseignant / enseignante"): Posts + Projects, can see all but only edit own content
- **Student** (`student`, "Étudiant / étudiante"): Projects only, can only see and edit own projects

All default WordPress roles (Editor, Subscriber, etc.) are automatically removed on init.

## Installation

This is a **must-use plugin** (mu-plugin) that loads automatically. No activation required.

**Location**: `/web/app/mu-plugins/eikon-roles/eikon-roles.php`

## Key Architecture Decisions

### Project CPT Capabilities

The `project` custom post type uses `capability_type='post'`, which means:

- Project capabilities automatically map to standard post capabilities
- The plugin assigns post-type capabilities to roles (not custom project capabilities)
- This simplifies capability management and follows WordPress conventions

## Roles & Capabilities

### Super Admin

- Default WordPress role (unchanged)
- Full unrestricted access

### Administrator

**Permissions**:

- Edit, publish, and delete all posts, projects, pages, departments
- Create and manage user accounts
- Upload and manage media (all files)
- **Cannot access**: WordPress settings, plugins, themes installation

**Menu Access**: Full admin menu except Settings/Plugins/Appearance

### Teacher (`teacher`)

**French display**: "Enseignant / enseignante"

**Can do**:

- Create and edit **posts** and **projects**
- View and read all posts/projects created by other teachers
- Edit only their own posts/projects
- Save content as draft or pending review (no direct publish)
- Upload files and view their own media uploads

**Cannot do**:

- Publish content (only draft/pending)
- Edit other teachers' content
- Delete content
- See other users' media
- Access pages, departments, settings, or user management

**Admin Menu**: Posts, Projects, Media, Profile

### Student (`student`)

**French display**: "Étudiant / étudiante"

**Can do**:

- Create, edit, and manage **own projects only**
- Save projects as draft or pending review (no direct publish)
- Upload files and view their own media uploads

**Cannot do**:

- Access posts or other content types
- See other students' projects or media
- Publish content directly
- Delete anything
- See media uploaded by others
- Access settings or user management

**Admin Menu**: Projects, Media, Profile

## How It Works

### 1. Initialization (`init` hook, priority 5)

The plugin:

- Removes unwanted legacy roles (supervisor, editor, subscriber, responsable_de_branche, etc.)
- Creates teacher and student roles with appropriate capabilities
- Configures administrator capabilities
- Sets default registration role to student
- Automatically adds/updates capabilities if roles already exist

### 2. Content Visibility Restrictions

**Teachers**:

- Can see all posts/projects (using `edit_others_posts: false` +capabilities)
- Pre_get_posts filter enforces single-post queries only by author

**Students**:

- Can ONLY see their own projects
- Uses `pre_get_posts` to filter queries by `author = current_user_id`
- Trying to access posts redirects to projects instead

### 3. Publishing Prevention

Teachers and students cannot publish content:

- Uses `user_has_cap` filter to deny `publish_posts` capability
- They can only save as Draft or Pending Review

### 4. Menu & Media Filtering

**Menu items are hidden** based on role via `admin_menu` hook:

- Students: Dashboard, Posts, Pages, Departments, Tools, Settings, Users removed
- Teachers: Dashboard, Pages, Departments, Tools, Settings, Appearance, Plugins removed

**Media library filtering** restricts uploads by author:

- Teachers/students only see media they uploaded
- Uses `pre_get_posts` filter on `post_type=attachment`
- Admins see all media

### 5. Meta Capability Mapping

WordPress' internal permission checks are mapped via `map_meta_cap` filter:

- Ensures WordPress recognizes student permissions for menu visibility
- Translates capability checks to assigned capabilities

## Customization

### Adding a capability

Edit the appropriate role function and add to the `$*_caps` array:

```php
$student_caps = array(
    // ... existing caps
    'new_capability' => true,
);
```

The role creation functions automatically update existing roles if re-run.

### Removing a capability

Set to `false` and the role update will remove it:

```php
$teacher_caps = array(
    'delete_posts' => false,  // This will be removed from teacher role
);
```

## Available Capabilities Reference

Core capabilities used in this plugin:

| Capability            | Purpose                      |
| --------------------- | ---------------------------- |
| `read`                | Basic site access            |
| `read_posts`          | Read post type               |
| `edit_posts`          | Edit own posts               |
| `edit_others_posts`   | Edit any post                |
| `publish_posts`       | Publish directly             |
| `delete_posts`        | Delete own posts             |
| `delete_others_posts` | Delete any post              |
| `create_posts`        | Create new posts             |
| `manage_posts`        | Required for menu visibility |
| `upload_files`        | Upload media                 |
| `manage_users`        | User management              |
| `manage_options`      | Settings access              |

## Troubleshooting

### Menu items still showing incorrectly

1. Hard refresh browser (Cmd+Shift+R on Mac)
2. Log out completely
3. Clear WordPress object cache: `wp cache flush`
4. Log back in

### Unwanted roles still exist

Run the cleanup script:

```bash
wp eval-file web/app/mu-plugins/eikon-roles/cleanup-roles.php
```

### Students can see other students' projects

This is prevented by the content filter. Clear cache and verify the `eikon_restrict_manage_posts()` function is active in pre_get_posts hook.

### Project creation permissions

Students need `create_posts` capability. Verify in:

```bash
wp cap list student
```

## Technical Notes

- Plugin runs at `init` hook priority 5 (before most other plugins)
- Menu removal runs at priority 999 (after all menu items are registered)
- Role updates run on every page load (safe, uses `get_role()` checks)
- No custom database tables or options (uses standard WordPress roles table)
- Fully compatible with WP GraphQL (headless frontend)

## Related Files

- Theme roles init: `/web/app/themes/inside/inc/roles.php` (legacy, uses this plugin)
- One-time cleanup: `/web/app/mu-plugins/eikon-roles/cleanup-roles.php`
- Composer: `Members` plugin removed from dependencies

````

## Migration from Members Plugin

When removing the Members plugin:

1. **Remove from `composer.json`**:

   ```bash
   "wpackagist-plugin/members": "^3.2"  # Remove this line
````

2. **Run Composer Update**:

   ```bash
   composer update
   ```

3. The `eikon-roles` plugin will automatically:
   - Remove the old `supervisor` role on init
   - Create new `teacher` and `student` roles with correct capabilities
   - Hide Members plugin menu items

## Troubleshooting

### "Responsable de branche" role still appears

The cleanup process tries multiple naming conventions (underscores, hyphens, spaces). If it still appears:

1. Hard refresh browser (Cmd+Shift+R on Mac)
2. The plugin runs role cleanup on every `init` hook, so it will remove itself over time
3. Use the included `cleanup-roles.php` script for manual cleanup:
   ```bash
   wp eval-file web/app/mu-plugins/eikon-roles/cleanup-roles.php
   ```

### Student can't see Projects menu

Ensure the student has `read`, `edit_posts`, `create_posts` capabilities. This is fixed as of the latest update.

### Media library still showing all images

1. **Hard refresh** the browser (Cmd+Shift+R on Mac)
2. Clear WordPress cache if you have a caching plugin
3. Log out and log back in
4. Make sure the user didn't upload all the images themselves (if they did, they'll see them all, which is correct)

### Old roles still appearing (Editor, Subscriber, etc.)

The plugin automatically removes these on every page load. If they still appear:

1. **Clear browser cache** (Cmd+Shift+R on Mac)
2. **Hard refresh** the WordPress admin
3. Check the database directly: `wp user list --role=editor` should return no results
4. The cleanup runs on every `init` hook, so roles will be removed automatically

### Users can't see their menu items

Check if the role name matches exactly. Update [eikon_remove_admin_menu_items()] if needed.

### Users can still publish

Ensure the `user_has_cap` filter is active and check browser cache.

### Media library still showing all images for teachers/students

1. **Hard refresh** the browser (Cmd+Shift+R on Mac)
2. Make sure you're logged in with the correct role
3. Clear WordPress cache if you have a caching plugin
4. Check that the user didn't upload all images themselves

### New capability not working

Remember to:

1. Add the capability to the role
2. Clear any capability caching
3. Log out and back in for full effect

## Development Notes

- Hook into `wp_roles_init` if you need to run custom logic after role setup
- Use `current_user_can('capability_name')` to check permissions in code
- The plugin runs at priority 5 on `init` hook to load before other plugins
- GraphQL access is controlled via WP GraphQL's role-based field visibility

## Files Modified

- `/web/app/themes/inside/inc/roles.php` - Removed supervisor role creation
- `composer.json` - Removed Members plugin dependency

## Removed Roles

The following WordPress default and legacy roles are automatically removed:

- `editor` - WordPress default Editor role
- `subscriber` - WordPress default Subscriber role
- `supervisor` - Old custom role (from previous setup)
- `responsable_de_branche` - Old custom role (various naming conventions)
- `branch_manager` - Old custom role

## Audit Findings & Fixes

**Issue Discovered**: Capabilities mapping for the project custom post type

- The `project` CPT uses `capability_type='post'`, so it doesn't have its own capability names
- This means `edit_projects` capability doesn't exist; instead, `edit_posts` controls project editing
- **Fix Applied**: Updated roles to use correct `post` capabilities

**Issue Discovered**: "Responsable de branche" role lingering in database

- The role exists but wasn't properly removed in previous code versions
- **Fix Applied**: Enhanced cleanup function to remove multiple naming conventions (underscores, hyphens, spaces)

**Issue Discovered**: Media library showing all images for non-admin users

- Screen detection wasn't working reliably
- **Fix Applied**: Improved screen detection and post_type checking
