# Inside Eikon - Improvements & Recommendations

## Priority System

- 🔴 **Critical**: Security, data integrity, or blocking issues
- 🟠 **High**: Performance, user experience, or code quality
- 🟡 **Medium**: Nice-to-have improvements, optimization
- 🟢 **Low**: Minor enhancements, code polish

---

## 🔴 Critical Priority

### 3. Database Query: SQL Injection Prevention

**File**: `web/app/themes/inside/inc/cpt.php:291-295`

**Issue**:

```php
$count = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(ID) FROM $wpdb->posts WHERE
     post_type = 'project' AND post_author = %d",
    $id
));
```

**Problem**: `$wpdb->posts` variable interpolation happens BEFORE `$wpdb->prepare()`, which is correct, but the query has unnecessary line breaks that could cause issues.

**Fix**:

```php
$count = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(ID) FROM $wpdb->posts WHERE post_type = 'project' AND post_author = %d",
    $id
));
```

**Priority**: 🔴 Critical - Code quality and SQL best practices

---

## 🟠 High Priority

### 4. Missing Error Handling in Image Conversion

**File**: `web/app/themes/inside/inc/images.php:12-39`

**Issue**: WebP conversion filter doesn't handle errors or inform users of failures.

**Problem**:

- Silent failures if GD/Imagick not available
- No logging when conversion fails
- Original images deleted without verifying WebP creation success

**Fix**:

```php
add_filter('wp_generate_attachment_metadata', function ($metadata) {
    if (!function_exists('wp_get_image_editor')) {
        error_log('WebP conversion: wp_get_image_editor not available');
        return $metadata;
    }

    $upload_dir = wp_upload_dir();

    // Convert original image
    $original_file = $upload_dir['basedir'] . '/' . $metadata['file'];
    if (!preg_match('/\.webp$/i', $original_file)) {
        $original_webp_file = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $original_file);
        $original_image = wp_get_image_editor($original_file);
        if (!is_wp_error($original_image)) {
            $save_result = $original_image->save($original_webp_file, 'image/webp');
            if (is_wp_error($save_result)) {
                error_log('WebP conversion failed for ' . $original_file . ': ' . $save_result->get_error_message());
            }
        } else {
            error_log('WebP editor error for ' . $original_file . ': ' . $original_image->get_error_message());
        }
    }

    // Convert variations
    foreach ($metadata['sizes'] as $size => $sizeInfo) {
        $file = $upload_dir['basedir'] . '/' . dirname($metadata['file']) . '/' . $sizeInfo['file'];
        if (!preg_match('/\.webp$/i', $file)) {
            $webp_file = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $file);
            $image = wp_get_image_editor($file);
            if (!is_wp_error($image)) {
                $save_result = $image->save($webp_file, 'image/webp');
                if (!is_wp_error($save_result) && file_exists($webp_file)) {
                    unlink($file); // Only delete after successful conversion
                    $metadata['sizes'][$size]['file'] = basename($webp_file);
                } else {
                    error_log('WebP variation conversion failed for ' . $file);
                }
            }
        }
    }
    return $metadata;
});
```

**Priority**: 🟠 High - Data integrity and user experience

---

### 5. VOD Plugin: Missing Nonce Verification

**File**: `web/app/plugins/vod-eikon/vod-eikon.php`

**Issue**: AJAX endpoints need nonce verification audit.

**Problem**: Security best practice - all AJAX actions should verify nonces.

**Check Required**:

```php
// Ensure all AJAX handlers have:
check_ajax_referer('vod_eikon_nonce', 'nonce');
```

**Priority**: 🟠 High - Security

---

### 6. Performance: N+1 Query in Project Column

**File**: `web/app/themes/inside/inc/cpt.php:287-298`

**Issue**: User column queries database for each user row individually.

**Problem**: If you have 100 users, this creates 100 separate database queries.

**Fix**:

```php
// Cache the counts
function user_projects_column_value($value, $column_name, $id) {
    if ($column_name == 'user_projects') {
        static $project_counts = null;

        if ($project_counts === null) {
            global $wpdb;
            $results = $wpdb->get_results(
                "SELECT post_author, COUNT(ID) as count
                 FROM $wpdb->posts
                 WHERE post_type = 'project'
                 GROUP BY post_author",
                OBJECT_K
            );
            $project_counts = array_map(function($r) { return $r->count; }, $results);
        }

        return isset($project_counts[$id]) ? (int) $project_counts[$id] : 0;
    }
}
```

**Priority**: 🟠 High - Performance (scales badly with user count)

---

### 7. Inconsistent Hook Priority

**File**: `web/app/themes/inside/inc/acf.php:23-25`

**Issue**:

```php
add_filter('acf/fields/wysiwyg/toolbars', function ($toolbars) {
    return $toolbars;
}, 20);
```

**Problem**: Filter added with priority 20 that does nothing (just returns input). Likely a leftover from development.

**Fix**: Remove lines 23-25 entirely.

**Priority**: 🟠 High - Code cleanliness

---

### 8. Hardcoded URLs in Deployment

**File**: `.github/workflows/deploy.yml:65`

**Issue**:

```yaml
ssh ... "curl -s https://inside.eikon.ch/opcache_clear.php"
```

**Problem**: Production URL hardcoded in workflow.

**Fix**:

```yaml
- name: Clear opcache
  run: |
    ssh ${{ vars.SSH_USER }}@${{ vars.SSH_HOST }} "echo '<?php opcache_reset(); ?>' > ${{ vars.DEPLOY_PATH }}/current/web/opcache_clear.php"
    ssh ${{ vars.SSH_USER }}@${{ vars.SSH_HOST }} "curl -s ${{ vars.SITE_URL }}/opcache_clear.php"
    ssh ${{ vars.SSH_USER }}@${{ vars.SSH_HOST }} "rm -rf ${{ vars.DEPLOY_PATH }}/current/web/opcache_clear.php"
```

Add `SITE_URL` to GitHub variables.

**Priority**: 🟠 High - Deployment flexibility

---

## 🟡 Medium Priority

### 9. Missing Translation Text Domain

**File**: Various `inc/*.php` files

**Issue**: Many `__()` calls use incorrect or missing text domains.

**Examples**:

- `web/app/themes/inside/inc/cpt.php` uses 'project', 'department' text domains
- `web/app/themes/inside/inc/admin.php:40` has no text domain

**Fix**: Standardize to theme text domain:

```php
// Replace
__('Text', 'project')
// With
__('Text', 'inside-eikon')
```

**Priority**: 🟡 Medium - Internationalization (if needed)

---

### 10. Comment Inconsistency

**File**: Multiple files

**Issue**: Comments in French mixed with English variable names.

**Examples**:

```php
// web/app/themes/inside/inc/cpt.php
'name' => _x('Projets', 'Post Type General Name', 'project'),
```

**Recommendation**: Decide on primary language for code comments and stick to it (English is standard in international projects).

**Priority**: 🟡 Medium - Code maintainability

---

### 11. Redundant Theme Support Call

**File**: `web/app/themes/inside/inc/cpt.php:273` and `web/app/themes/inside/inc/images.php:3`

**Issue**: `add_theme_support('post-thumbnails')` called twice.

**Fix**: Keep only in `images.php` and remove from `cpt.php`.

**Priority**: 🟡 Medium - Code cleanliness

---

### 12. Magic Numbers in Theme Configuration

**File**: `web/app/themes/inside/theme.json`

**Issue**: Hardcoded pixel values throughout without documentation.

**Recommendation**: Add comments explaining the design system:

```json
{
  "settings": {
    "spacing": {
      "spacingSizes": [
        { "name": "Small", "slug": "small", "size": "10px" },
        { "name": "Medium", "slug": "medium", "size": "20px" },
        { "name": "Big", "slug": "big", "size": "30px" }
      ]
    }
  }
}
```

Consider using CSS custom properties for better maintainability.

**Priority**: 🟡 Medium - Maintainability

---

### 13. Missing .nvmrc or .node-version

**File**: Root directory

**Issue**: No Node.js version specified.

**Problem**: Team members might use different Node versions causing build inconsistencies.

**Fix**: Add `.nvmrc`:

```
20
```

Or `.node-version` for better tool compatibility.

**Priority**: 🟡 Medium - Developer experience

---

### 14. README Typo and Outdated Info

**File**: `README.md`

**Issues**:

- Line 18: `.env.exemple` should be `.env.example`
- Line 19: `vim .emv` should be `vim .env`
- Missing block build instructions in setup

**Fix**:

````markdown
## Setup

1. Duplicate and edit the .env file:
   ```bash
   cp .env.example .env
   vim .env
   ```
````

2. Configure your database and URLs in `.env`

3. Install dependencies:
   ```bash
   composer install
   node build-all-blocks.js
   ```

````

**Priority**: 🟡 Medium - Documentation

---

### 15. Unused Plugin Field Comments
**File**: `web/app/mu-plugins/eikonblocks-projects/block.php:4-5`

**Issue**:
```php
* Plugin Name:       eikonblocks: PROJECTS
* Description:       Maruqee block scaffolded...
````

**Problems**:

- Typo: "Maruqee" should be "Marquee" (but this is the Projects block, not Marquee)
- Inconsistent capitalization

**Fix**: Update all block headers to be consistent:

```php
/**
 * Plugin Name:       Eikon Blocks: Projects
 * Description:       Projects filtering block for Eikon portfolio.
 * ...
 */
```

**Priority**: 🟡 Medium - Code quality

---

## 🟢 Low Priority

### 16. Add phpcs.xml Configuration

**File**: Root directory (missing)

**Issue**: `composer.json` includes PHP_CodeSniffer but no ruleset configuration.

**Recommendation**: Add `phpcs.xml`:

```xml
<?xml version="1.0"?>
<ruleset name="Inside Eikon">
    <description>Coding standards for Inside Eikon</description>

    <file>web/app/themes/inside</file>
    <file>web/app/plugins/vod-eikon</file>

    <exclude-pattern>*/vendor/*</exclude-pattern>
    <exclude-pattern>*/node_modules/*</exclude-pattern>

    <rule ref="PSR12"/>

    <!-- Allow WordPress-style hooks -->
    <rule ref="PSR12.Functions.NullableTypeDeclaration.SpaceBeforeNullabilityIndicator">
        <severity>0</severity>
    </rule>
</ruleset>
```

**Priority**: 🟢 Low - Code quality tooling

---

### 17. Add Block Documentation

**File**: Each `eikonblocks-*/` directory

**Issue**: No README files explaining block usage.

**Recommendation**: Add `README.md` to each block:

````markdown
# Eikon Block: Card

## Description

Card block with image and inner blocks for content.

## Usage

Must be nested inside a Section block.

## Attributes

- `imageUrl` (string): Card image URL
- `imagePosition` (string): 'left' or 'right'

## Allowed Inner Blocks

- Heading, Buttons, Paragraph, List, Image

## Development

```bash
npm run start  # Development with watch
npm run build  # Production build
```
````

````

**Priority**: 🟢 Low - Documentation

---

### 18. Consolidate Build Scripts
**File**: Root `package.json`

**Issue**: Only has `build-all-blocks` script, missing common development commands.

**Recommendation**:
```json
{
  "scripts": {
    "build-all-blocks": "node build-all-blocks.js",
    "build:blocks": "node build-all-blocks.js",
    "watch:blocks": "node build-all-blocks.js --watch",
    "lint:blocks": "for dir in web/app/mu-plugins/eikonblocks-*; do (cd $dir && npm run lint:js); done"
  }
}
````

**Priority**: 🟢 Low - Developer experience

---

### 19. Add Git Hooks with Husky

**File**: Root directory (missing)

**Recommendation**: Add pre-commit hooks to ensure code quality:

```bash
npm install --save-dev husky lint-staged
npx husky init
```

Configure `.husky/pre-commit`:

```bash
#!/bin/sh
. "$(dirname "$0")/_/husky.sh"

# Build blocks before commit
node build-all-blocks.js

# Run PHP linter if files changed
git diff --cached --name-only --diff-filter=ACMR "*.php" | xargs -r ./vendor/bin/phpcs
```

**Priority**: 🟢 Low - Developer experience

---

### 20. Add Composer Scripts

**File**: `composer.json`

**Issue**: Only has `test` script, could add more useful commands.

**Recommendation**:

```json
"scripts": {
    "test": ["phpcs"],
    "lint": ["phpcs"],
    "lint:fix": ["phpcbf"],
    "post-install-cmd": ["@php -r \"!file_exists('.env') && copy('.env.example', '.env');\""]
}
```

**Priority**: 🟢 Low - Developer experience

---

### 21. Environment-Specific Configurations

**File**: `config/environments/` directory

**Issue**: Only production config exists, but Bedrock supports environment-specific configs.

**Recommendation**: Add `config/environments/development.php`:

```php
<?php
use Roots\WPConfig\Config;

Config::define('SAVEQUERIES', true);
Config::define('WP_DEBUG', true);
Config::define('WP_DEBUG_DISPLAY', true);
Config::define('WP_DISABLE_FATAL_ERROR_HANDLER', true);
Config::define('SCRIPT_DEBUG', true);

ini_set('display_errors', '1');
```

**Priority**: 🟢 Low - Developer experience

---

### 22. Add Block Screenshots

**File**: Each `eikonblocks-*/` directory

**Issue**: Blocks don't have preview screenshots for documentation.

**Recommendation**: Add `screenshot.png` to each block showing how it looks in the editor.

**Priority**: 🟢 Low - Documentation

---

### 23. Standardize Block Naming

**File**: Various block files

**Issue**: Inconsistent block naming conventions:

- Some use "eikonblocks/card"
- Directory names use "eikonblocks-card"
- Display names vary

**Recommendation**: Document naming convention:

- Directory: `eikonblocks-{name}` (kebab-case)
- Block namespace: `eikonblocks/{name}` (lowercase)
- Display name: Proper case (e.g., "Projects", "Card")

**Priority**: 🟢 Low - Consistency

---

## Summary Statistics

- **🔴 Critical**: 3 issues
- **🟠 High**: 5 issues
- **🟡 Medium**: 7 issues
- **🟢 Low**: 8 issues

**Total**: 23 improvements identified

---

## Recommended Action Plan

### Phase 1 (Week 1): Critical & High Priority

1. Fix error display suppression in graphql.php
2. Add URL validation to QR code generation
3. Fix SQL query formatting
4. Add error handling to WebP conversion
5. Audit VOD plugin nonce verification
6. Optimize user project count query
7. Remove unused ACF filter
8. Fix hardcoded deployment URL

### Phase 2 (Week 2-3): Medium Priority

9. Standardize text domains
10. Clean up duplicate theme support calls
11. Add Node version file
12. Fix README typos
13. Update block descriptions
14. Document theme.json design tokens

### Phase 3 (Future): Low Priority

14. Add phpcs.xml configuration
15. Create block documentation
16. Add development scripts
17. Set up git hooks
18. Add environment configs
19. Add block screenshots
20. Document naming conventions

---

## Notes

Most of these improvements are **non-breaking** and can be implemented gradually. The critical and high-priority items should be addressed soon as they involve security, performance, and data integrity concerns.

The codebase is generally well-structured and follows modern WordPress/Bedrock best practices. These improvements would further enhance maintainability, security, and developer experience.
