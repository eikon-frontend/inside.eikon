<?php
/**
 * Migration script: Convert individual images to mosaic layouts
 * 
 * This script converts consecutive individual image layouts in the project gallery
 * flexible content into mosaic (grid) layouts.
 * 
 * Usage via WP-CLI:
 * wp eval-file web/app/themes/inside/inc/migrate-images-to-mosaic.php
 * 
 * Or add to functions.php temporarily and access via admin dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

class ImageToMosaicMigration {
    
    private $dry_run = true;
    private $stats = [
        'projects_processed' => 0,
        'projects_modified' => 0,
        'images_converted' => 0,
        'mosaics_created' => 0,
        'errors' => []
    ];
    
    /**
     * Run the migration
     * 
     * @param bool $dry_run If true, only simulate changes without saving
     * @return array Migration statistics
     */
    public function run($dry_run = true) {
        $this->dry_run = $dry_run;
        
        $this->log("=== Starting Image to Mosaic Migration ===");
        $this->log("Mode: " . ($dry_run ? "DRY RUN (no changes will be saved)" : "LIVE (changes will be saved)"));
        $this->log("");
        
        // Get all projects
        $args = [
            'post_type' => 'project',
            'post_status' => ['publish', 'draft', 'pending', 'future'],
            'posts_per_page' => -1,
            'orderby' => 'ID',
            'order' => 'ASC'
        ];
        
        $projects = get_posts($args);
        
        if (empty($projects)) {
            $this->log("No projects found.");
            return $this->stats;
        }
        
        $this->log("Found " . count($projects) . " projects to process.");
        $this->log("");
        
        foreach ($projects as $project) {
            $this->process_project($project);
        }
        
        $this->print_summary();
        
        return $this->stats;
    }
    
    /**
     * Process a single project
     */
    private function process_project($project) {
        $this->stats['projects_processed']++;
        
        // Get the gallery flexible content
        $galerie = get_field('galerie', $project->ID);
        
        if (empty($galerie) || !is_array($galerie)) {
            return;
        }
        
        // Check if there are any individual images to convert
        $has_individual_images = false;
        foreach ($galerie as $layout) {
            if ($layout['acf_fc_layout'] === 'image') {
                $has_individual_images = true;
                break;
            }
        }
        
        if (!$has_individual_images) {
            return;
        }
        
        $this->log("Processing: {$project->post_title} (ID: {$project->ID})");
        
        // Convert the gallery
        $new_galerie = $this->convert_gallery($galerie, $project->ID);
        
        // Check if anything changed
        if ($new_galerie === $galerie) {
            $this->log("  → No changes needed");
            return;
        }
        
        $this->stats['projects_modified']++;
        
        // Save if not dry run
        if (!$this->dry_run) {
            update_field('galerie', $new_galerie, $project->ID);
            $this->log("  ✓ Changes saved");
        } else {
            $this->log("  ✓ Changes detected (not saved - dry run)");
        }
        
        $this->log("");
    }
    
    /**
     * Convert gallery layouts
     * Groups consecutive individual images into mosaic layouts
     */
    private function convert_gallery($galerie, $post_id) {
        $new_galerie = [];
        $image_buffer = [];
        
        foreach ($galerie as $index => $layout) {
            $layout_type = $layout['acf_fc_layout'];
            
            if ($layout_type === 'image') {
                // Add to buffer
                $image_buffer[] = $layout;
            } else {
                // Flush buffer if we have images
                if (!empty($image_buffer)) {
                    $this->flush_image_buffer($image_buffer, $new_galerie, $post_id);
                    $image_buffer = [];
                }
                
                // Add the non-image layout as-is
                $new_galerie[] = $layout;
            }
        }
        
        // Flush any remaining images at the end
        if (!empty($image_buffer)) {
            $this->flush_image_buffer($image_buffer, $new_galerie, $post_id);
        }
        
        return $new_galerie;
    }
    
    /**
     * Convert buffered images to mosaic or keep single image
     */
    private function flush_image_buffer(&$image_buffer, &$new_galerie, $post_id) {
        $count = count($image_buffer);
        
        if ($count === 0) {
            return;
        }
        
        // If only one image, keep it as-is
        if ($count === 1) {
            $new_galerie[] = $image_buffer[0];
            $this->log("  → Single isolated image kept as-is");
            return;
        }
        
        // Create mosaic with multiple images
        $mosaic_images = [];
        
        foreach ($image_buffer as $image_layout) {
            if (!empty($image_layout['image'])) {
                $mosaic_images[] = $image_layout['image'];
            }
        }
        
        // Only create mosaic if we have at least 2 valid images
        if (count($mosaic_images) < 2) {
            // Fallback: keep original layouts
            foreach ($image_buffer as $layout) {
                $new_galerie[] = $layout;
            }
            return;
        }
        
        // Create new mosaic layout
        $mosaic_layout = [
            'acf_fc_layout' => 'mosaic',
            'mosaic' => $mosaic_images,
            'mosaic_type' => 'grid'
        ];
        
        $new_galerie[] = $mosaic_layout;
        
        $this->stats['images_converted'] += count($mosaic_images);
        $this->stats['mosaics_created']++;
        
        $this->log("  → Converted {$count} consecutive images into mosaic (grid)");
    }
    
    /**
     * Print migration summary
     */
    private function print_summary() {
        $this->log("=== Migration Summary ===");
        $this->log("Projects processed: {$this->stats['projects_processed']}");
        $this->log("Projects modified: {$this->stats['projects_modified']}");
        $this->log("Images converted: {$this->stats['images_converted']}");
        $this->log("Mosaics created: {$this->stats['mosaics_created']}");
        
        if (!empty($this->stats['errors'])) {
            $this->log("");
            $this->log("Errors:");
            foreach ($this->stats['errors'] as $error) {
                $this->log("  - {$error}");
            }
        }
        
        $this->log("");
        if ($this->dry_run) {
            $this->log("This was a DRY RUN. No changes were saved.");
            $this->log("Run with run(false) to apply changes.");
        } else {
            $this->log("Migration completed successfully!");
        }
    }
    
    /**
     * Log message
     */
    private function log($message) {
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::log($message);
        } else {
            echo $message . "\n";
        }
    }
}

// ===========================
// EXECUTION
// ===========================

// Run in DRY RUN mode first to see what would change
$migration = new ImageToMosaicMigration();
// $stats = $migration->run(true);

// Run in LIVE mode
$stats = $migration->run(false);

// If running via WP-CLI, output JSON stats
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::success("Migration completed!");
}
