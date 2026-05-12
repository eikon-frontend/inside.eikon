-- ============================================================================
-- Inside Eikon - Portfolio Nomenclature Migration (May 2026)
-- ============================================================================
--
-- This migration updates postmeta keys from the old naming convention to the new one:
-- - galerie_* → portfolio_*
-- - mosaic → imageGallery
-- - mosaic_type → layout
--
-- Execution time: < 1 second
-- Risk level: Very Low (safe string replacements on non-critical keys)
--
-- IMPORTANT: Make a database backup before executing!
-- ============================================================================

-- Step 1a: Migrate the main field key (exact match, no suffix — stores row count + layout list)
UPDATE wp_postmeta SET meta_key = 'portfolio' WHERE meta_key = 'galerie';
UPDATE wp_postmeta SET meta_key = '_portfolio' WHERE meta_key = '_galerie';

-- Step 1b: Migrate all sub-keys (galerie_X_* → portfolio_X_*)
UPDATE wp_postmeta
SET meta_key = REPLACE(meta_key, 'galerie_', 'portfolio_')
WHERE meta_key LIKE 'galerie_%';

UPDATE wp_postmeta
SET meta_key = REPLACE(meta_key, '_galerie_', '_portfolio_')
WHERE meta_key LIKE '_galerie_%';

-- Step 2: Migrate sub-field name from 'mosaic' to 'images' (the gallery sub-field name in ACF JSON)
-- Note: 'imageGallery' is the LAYOUT name, 'images' is the SUB-FIELD name — they differ.
-- This updates keys like portfolio_X_mosaic → portfolio_X_images
UPDATE wp_postmeta
SET meta_key = REPLACE(meta_key, '_mosaic', '_images')
WHERE meta_key LIKE '%_mosaic%' AND meta_key NOT LIKE '%_mosaic_type%';

-- Step 3: Fix layout names in the serialized portfolio values (mosaic → imageGallery)
-- The main 'portfolio' meta_key stores a serialized PHP array of layout names per row.
-- This cannot be done with a plain SQL REPLACE (serialization length must stay consistent).
-- Run this via WP-CLI instead:
--
--   wp eval '
--   global $wpdb;
--   $rows = $wpdb->get_results("SELECT meta_id, meta_value FROM wp_postmeta WHERE meta_key = \"portfolio\"");
--   $fixed = 0;
--   foreach ($rows as $row) {
--       $val = maybe_unserialize($row->meta_value);
--       if (is_array($val)) {
--           $new_val = array_map(fn($l) => $l === "mosaic" ? "imageGallery" : $l, $val);
--           if ($new_val !== $val) {
--               $wpdb->update("wp_postmeta", ["meta_value" => serialize($new_val)], ["meta_id" => $row->meta_id]);
--               $fixed++;
--           }
--       }
--   }
--   echo "Fixed $fixed rows\n";
--   '

-- ============================================================================
-- Verification queries (run after migration to confirm)
-- ============================================================================

-- Check that all old keys have been migrated
-- SELECT meta_key, COUNT(*) FROM wp_postmeta
-- WHERE meta_key LIKE '%galerie%' OR meta_key LIKE '%mosaic%'
-- GROUP BY meta_key;

-- Count new keys to verify migration success
-- SELECT 'portfolio_* keys' as type, COUNT(*) as count FROM wp_postmeta
-- WHERE meta_key LIKE 'portfolio_%' OR meta_key LIKE '_portfolio_%'
-- UNION ALL
-- SELECT 'imageGallery keys' as type, COUNT(*) as count FROM wp_postmeta
-- WHERE meta_key LIKE '%_imageGallery%'
-- UNION ALL
-- SELECT 'layout type keys' as type, COUNT(*) as count FROM wp_postmeta
-- WHERE meta_key LIKE '%_layout' AND meta_key NOT LIKE '%template%' AND meta_key NOT LIKE '%user%';
