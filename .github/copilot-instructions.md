# Inside Eikon - Copilot Documentation

## Overview
**Inside Eikon** is a headless WordPress CMS built on the Bedrock framework, serving content exclusively via WP GraphQL to a Nuxt.js frontend. It's designed specifically for the Eikon art school portfolio website at [eikon.ch](https://eikon.ch).

**Repository**: [eikon-frontend/eikon.ch](https://github.com/eikon-frontend/eikon.ch) (frontend)  
**Tech Stack**: WordPress 6.6.2 + Bedrock, PHP 8.3, ACF Pro, WP GraphQL  
**Deployment**: Automated GitHub Actions on `main` branch push  

---

## Architecture Philosophy

### Headless-First Design
- **No WordPress frontend**: The WP instance serves ONLY as a data API
- **GraphQL-powered**: All data consumed by Nuxt frontend via WP GraphQL
- **Dual URLs**: 
  - `WP_HOME`: Frontend URL (Nuxt)
  - `WP_HOME_ADMIN`: Backend admin URL (WordPress)
- **Preview functionality**: Draft/pending projects accessible via slug for previews

### Bedrock Structure
The project uses [Roots Bedrock](https://roots.io/bedrock/), a WordPress boilerplate with modern development practices:

```
/
├── config/                    # Environment-specific configs
│   └── application.php        # Main config (uses .env)
├── web/                       # Document root
│   ├── app/                   # WordPress content
│   │   ├── mu-plugins/        # Must-use plugins (autoloaded)
│   │   │   ├── eikonblocks-*/ # Custom Gutenberg blocks
│   │   │   ├── bedrock-autoloader.php
│   │   │   └── register-theme-directory.php
│   │   ├── plugins/           # Regular plugins
│   │   │   ├── vod-eikon/     # Infomaniak VOD integration
│   │   │   └── vod-video-field/ # ACF video field
│   │   └── themes/            # Themes
│   │       └── inside/        # Main theme
│   ├── wp/                    # WordPress core (managed by Composer)
│   └── index.php
├── vendor/                    # PHP dependencies
├── composer.json              # PHP dependency management
├── .env                       # Environment variables (ignored by git)
└── .env.example               # Environment template
```

---

## Core Components

### 1. Theme: `web/app/themes/inside/`

**Purpose**: Minimal theme focused on Gutenberg editor experience and GraphQL configuration.

**Structure**:
```
inside/
├── functions.php              # Loads all inc/*.php files
├── inc/                       # Modular functionality
│   ├── acf.php                # ACF field configurations
│   ├── admin.php              # Admin UI customizations
│   ├── cpt.php                # Custom Post Types & Taxonomies
│   ├── graphql.php            # GraphQL customizations
│   ├── gutenberg.php          # Block editor configuration
│   ├── headless.php           # Headless-specific features (QR codes, etc.)
│   ├── images.php             # Image handling & WebP conversion
│   ├── menu.php               # Navigation menus
│   └── roles.php              # User roles setup
├── acf-json/                  # ACF field groups (JSON sync)
├── theme.json                 # Gutenberg theme configuration
├── css/editor.css             # Gutenberg editor styles
└── js/                        # Editor scripts
```

**Key Features**:

#### Custom Post Types (cpt.php)
- **Project**: Main content type for student portfolios
  - Taxonomies: `year`, `section`, `subjects`
  - GraphQL enabled with names: `project/projects`
  - Auto-generates slugs even for draft posts (essential for previews)
  - Gutenberg disabled (uses ACF flexible content)
  
- **Department**: School departments/sections
  - GraphQL enabled: `department/departments`
  - Uses Gutenberg blocks

#### GraphQL Customizations (graphql.php)
- **Random ordering**: Adds `RAND` to order enum
- **Draft visibility**: Makes draft/pending/future `project` posts fully accessible via GraphQL for preview functionality
- **Single-item queries**: Allows fetching unpublished projects by slug without exposing them in listings

#### Image Processing (images.php)
- **Auto WebP conversion**: All uploaded images automatically converted to WebP
- **Restricted uploads**: Only images (JPG, PNG, WebP, SVG) and PDFs allowed
- **Filtered sizes**: Removes WordPress default sizes (medium_large, 1536x1536, 2048x2048)
- **Renamed media**: "Médiathèque" → "Images" in admin

#### ACF Configuration (acf.php)
- **Layout filtering**: Non-admin users can't see video/twitch layouts
- **Custom WYSIWYG toolbar**: Simplified "Portfolio Layout" toolbar
- **Block formats**: Restricted to paragraph and H3 only

### 2. Custom Gutenberg Blocks: `web/app/mu-plugins/eikonblocks-*/`

**Philosophy**: Modular, isolated blocks built with `@wordpress/scripts`

**Available Blocks**:
- `eikonblocks-accordion` - Collapsible content
- `eikonblocks-buttons` - Button groups
- `eikonblocks-card` - Image + content cards
- `eikonblocks-departments-teaser` - Department previews
- `eikonblocks-grid` - Grid layouts
- `eikonblocks-heading` - Styled headings
- `eikonblocks-marquee` - Scrolling text
- `eikonblocks-mixed-posts` - Post listings
- `eikonblocks-numbers` - Number displays
- `eikonblocks-projects` - Project filtering widget
- `eikonblocks-section` - Container block with styling

**Block Structure** (each block follows this pattern):
```
eikonblocks-example/
├── block.php                  # Registration (register_block_type)
├── package.json               # npm scripts (@wordpress/scripts)
├── src/                       # Source files
│   ├── block.json             # Block metadata
│   ├── edit.js                # Editor component
│   ├── save.js                # Save/render (often null for dynamic)
│   ├── index.js               # Entry point
│   └── editor.scss            # Editor styles
├── build/                     # Compiled assets (gitignored)
└── .gitignore
```

**Key Characteristics**:
- Built with `wp-scripts` (WordPress official tooling)
- Parent-child relationships (e.g., `card` must be inside `section`)
- Use `InnerBlocks` for nested content
- Inspector controls for block settings
- Custom color/spacing from `theme.json`

**Build Process**:
```bash
# Build single block
cd web/app/mu-plugins/eikonblocks-card
npm run build

# Build all blocks
node build-all-blocks.js
```

### 3. VOD Integration: `web/app/plugins/vod-eikon/`

**Purpose**: Infomaniak VOD (Video on Demand) API integration for video hosting

**Features**:
- Video upload directly from WordPress admin
- Sync with Infomaniak VOD API
- Webhook callbacks for video processing status
- Custom database table for video metadata
- Admin UI under Media → Vidéos

**Database Schema**:
```sql
wp_vod_eikon_videos (
  id, vod_id, name, poster, mpd_url, 
  published, created_at, updated_at
)
```

**API Token**: Stored in `.env` as `INFOMANIAK_VOD_API_TOKEN`

### 4. VOD Video Field: `web/app/plugins/vod-video-field/`

**Purpose**: Custom ACF field type for selecting VOD videos

**Integration**: Works with `vod-eikon` plugin to provide ACF field for video selection in flexible content layouts

---

## Development Workflow

### Local Setup

1. **Prerequisites**:
   ```bash
   composer    # PHP dependency manager
   node/npm    # JavaScript tooling
   ```

2. **Installation**:
   ```bash
   # Copy environment config
   cp .env.example .env
   vim .env  # Edit database & URLs
   
   # Install dependencies
   composer install
   
   # Build blocks
   node build-all-blocks.js
   ```

3. **Environment Variables** (`.env`):
   ```env
   DB_NAME='database_name'
   DB_USER='database_user'
   DB_PASSWORD='database_password'
   DB_HOST='localhost'
   
   WP_ENV='development'
   WP_HOME='http://nuxt-frontend.local'        # Nuxt URL
   WP_SITEURL="${WP_HOME}/wp"
   WP_HOME_ADMIN='http://wp-admin.local'       # WordPress admin URL
   
   # Security keys (generate at https://roots.io/salts.html)
   AUTH_KEY='...'
   # ... other keys
   
   INFOMANIAK_VOD_API_TOKEN='your_token'
   ```

### ACF Development

**JSON Sync**: ACF fields stored as JSON in `web/app/themes/inside/acf-json/`
- Auto-imports on theme activation
- Version controlled for team collaboration
- Changes auto-exported when saving fields in admin

**Field Groups**:
- Project layouts (gallery, text, video, etc.)
- Department content
- Page builder fields

### Block Development

**Creating a New Block**:
```bash
cd web/app/mu-plugins
npx @wordpress/create-block eikonblocks-newblock
cd eikonblocks-newblock
npm install
npm run start  # Dev mode with watch
```

**Block Registration**: Automatically loaded via `bedrock-autoloader.php`

**Parent Block**: `eikonblocks-section`
- Container for all custom blocks
- Provides background color, text color, padding controls
- Must be used as parent for most blocks (defined in `block.json`)

### Security & Permissions

**User Roles**:
- **Administrator**: Full access
- **Supervisor** (Enseignant): Teachers, custom role
- **Student**: Can only publish own projects
- **Registration**: Restricted to `@edufr.ch` email domains

**Restrictions**:
- File editor disabled (`DISALLOW_FILE_EDIT`)
- Plugin/theme installation disabled (`DISALLOW_FILE_MODS`)
- Automatic updates disabled
- Comments disabled globally

---

## Deployment

### GitHub Actions (`.github/workflows/deploy.yml`)

**Trigger**: Push to `main` branch

**Process**:
1. Checkout code
2. Install PHP 8.3
3. Add ACF Pro auth credentials
4. Install Composer dependencies (production mode)
5. Set up SSH keys
6. Rsync to production server (excludes `.env`, `.htaccess`, `uploads`)
7. Create symlinks for shared files
8. Clear OPcache

**Shared Files** (not deployed, symlinked):
- `.env` - Environment config
- `web/.htaccess` - Apache config
- `web/app/uploads/` - Media files

**Secrets Required**:
- `COMPOSER_AUTH_JSON` - ACF Pro credentials
- `DEPLOY_KEY` - SSH private key
- `HOST_KEY` - Server SSH fingerprint

**Variables**:
- `SSH_HOST`, `SSH_USER`, `DEPLOY_PATH`

---

## GraphQL Schema

### Available Types

**Post Types**:
- `Project/Projects` - Student portfolio projects
- `Department/Departments` - School departments
- `Page/Pages` - WordPress pages
- `MediaItem/MediaItems` - Media library

**Taxonomies**:
- `Year/Years` - Academic years
- `Section/Sections` - School sections
- `Subject/Subjects` - Academic subjects (branches)

**Custom Fields**: All ACF fields exposed via `wpgraphql-acf` plugin

### Key Features

**Random Ordering**:
```graphql
query {
  projects(where: { orderby: { field: RAND } }) {
    nodes { ... }
  }
}
```

**Draft Access** (single items only):
```graphql
query {
  projectBy(slug: "my-draft-project") {
    title
    content
    projectFields { ... }  # ACF data available
  }
}
```

---

## Plugin Dependencies

### Composer (PHP)
```json
{
  "roots/wordpress": "^6.6.2",
  "roots/bedrock-autoloader": "^1.0",
  "wpengine/advanced-custom-fields-pro": "^6.5",
  "wpackagist-plugin/wp-graphql": "^2.3",
  "wpackagist-plugin/wpgraphql-acf": "^2.4",
  "wpackagist-plugin/members": "^3.2",
  "wpackagist-plugin/wp-mail-smtp": "^4.0",
  "jysperu/php-qr-code": "^2",           # QR code generation
  "guzzlehttp/guzzle": "^7.9"            # HTTP client
}
```

### NPM (per block)
```json
{
  "@wordpress/scripts": "^27.0.0"
}
```

---

## Code Standards

### PHP
- PSR-12 compatible
- Namespace: `Roots\Bedrock` for core files
- Hooks over classes for theme functions
- Security: Always escape output, sanitize input, use nonces

### JavaScript
- ES6+ with JSX
- WordPress components (@wordpress/*)
- No inline styles (use SCSS)
- Follow `@wordpress/scripts` defaults

### File Organization
- **Modular**: One feature per file in `inc/`
- **No business logic in templates**: Theme has no PHP templates (headless)
- **JSON config**: ACF, block.json for declarative configuration

---

## Common Tasks

### Adding a New Custom Post Type
1. Edit `web/app/themes/inside/inc/cpt.php`
2. Add GraphQL support in `register_post_type()` args
3. Create ACF field group via admin
4. Fields auto-export to `acf-json/`

### Adding a New Block
1. Create block: `npx @wordpress/create-block eikonblocks-name`
2. Configure `block.json` (parent, attributes, etc.)
3. Develop `edit.js` and `save.js`
4. Build: `npm run build`
5. Add to allowed blocks in `gutenberg.php`

### Debugging GraphQL
- Enable debug log: `WP_DEBUG_LOG=true` in config
- Check `/web/app/debug.log`
- Use GraphiQL IDE in WordPress admin (WP GraphQL plugin)
- Disable display_errors in production (already set)

### Syncing ACF Fields
- Import: WordPress admin → Custom Fields → Tools → Import
- Export: Automatic when saving field groups
- JSON files: `web/app/themes/inside/acf-json/`

---

## Troubleshooting

### Blocks Not Showing
- Check if built: `node build-all-blocks.js`
- Verify in `gutenberg.php` allowed blocks list
- Check parent block restrictions in `block.json`

### Draft Projects Not Accessible
- Verify `graphql.php` filters are active
- Check slug exists (auto-generated on save)
- Use single-item query, not list query

### Images Not Converting to WebP
- Check GD/Imagick PHP extensions installed
- Verify file permissions in uploads directory
- Check error log for conversion failures

### VOD Videos Not Syncing
- Verify API token in `.env`
- Check webhook endpoint: `/wp-json/vod-eikon/v1/callback`
- Test manually: Admin → Vidéos → Test buttons

---

## Key Files Reference

### Configuration
- `config/application.php` - Main WordPress config
- `.env` - Environment variables (ignored by git)
- `composer.json` - PHP dependencies
- `web/app/themes/inside/theme.json` - Gutenberg settings

### Theme Core
- `web/app/themes/inside/functions.php` - Theme entry point
- `web/app/themes/inside/inc/*.php` - Modular functionality

### Custom Development
- `web/app/mu-plugins/eikonblocks-*/` - Custom blocks
- `web/app/plugins/vod-eikon/` - VOD integration
- `web/app/themes/inside/acf-json/` - ACF field definitions

### Build Tools
- `build-all-blocks.js` - Batch block builder
- `.github/workflows/deploy.yml` - CI/CD pipeline

---

## External Dependencies

### Services
- **Infomaniak VOD**: Video hosting and streaming
- **GitHub Actions**: CI/CD deployment
- **ACF Pro**: License required (auth.json)

### Documentation
- [Bedrock Docs](https://roots.io/bedrock/docs/)
- [WP GraphQL Docs](https://www.wpgraphql.com/)
- [ACF Docs](https://www.advancedcustomfields.com/resources/)
- [WordPress Block Editor Handbook](https://developer.wordpress.org/block-editor/)

---

## Notes for AI Coding Assistants

### Project Context
- **Headless-only**: No PHP rendering of HTML for frontend
- **GraphQL schema drives frontend**: Changes here affect Nuxt app
- **ACF as primary content builder**: Not Gutenberg for projects
- **Education context**: Portfolio for art school students

### When Adding Features
- ✅ Consider GraphQL exposure
- ✅ Test with draft content (preview requirement)
- ✅ Add to allowed blocks list if Gutenberg
- ✅ Follow existing patterns (modular inc/ files)
- ✅ Use theme.json colors/spacing
- ❌ Don't create PHP templates (no frontend)
- ❌ Don't expose sensitive student data
- ❌ Don't break draft preview functionality

### Testing Checklist
- [ ] GraphQL query works
- [ ] ACF fields export to JSON
- [ ] Blocks build without errors
- [ ] Image uploads convert to WebP
- [ ] Draft projects accessible by slug
- [ ] Admin UI works for non-admins
- [ ] Deployment doesn't overwrite shared files
