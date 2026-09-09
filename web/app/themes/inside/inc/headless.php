<?php

use QRcode\QRcode;
use QRcode\QRstr;

/**
 * Class Eikon_Headless
 *
 * Gère les fonctionnalités spécifiques au CMS Headless (prévisualisation, métaboxes, URLs).
 */
class Eikon_Headless
{
    public function __construct()
    {
        add_action('add_meta_boxes', array($this, 'add_custom_meta_box'));
        add_filter('rest_url', array($this, 'custom_rest_url'));
        add_action('admin_head', array($this, 'hide_preview_button_for_drafts'));
        add_filter('preview_post_link', array($this, 'customize_preview_link'), 10, 2);
        add_filter('get_sample_permalink', array($this, 'customize_sample_permalink'), 10, 5);
    }

    public function add_custom_meta_box()
    {
        add_meta_box(
            'project-review',
            'Examen du projet',
            array($this, 'project_review_metabox'),
            'project',
            'side',
            'high'
        );
    }

    public function project_review_metabox($post)
    {
        $this->project_completion_checklist_content($post);
        echo '<hr style="margin: 16px 0; border: none; border-top: 1px solid #e5e7eb;">';
        $this->info_box_content($post);
    }

    private function project_completion_checklist_content($post)
    {
        $has_title = !empty($post->post_title);
        $has_thumbnail = has_post_thumbnail($post->ID);

        $has_year = !empty(get_the_terms($post->ID, 'year'));
        $has_section = !empty(get_the_terms($post->ID, 'section'));
        $has_subjects = !empty(get_the_terms($post->ID, 'subjects'));
        $has_taxonomy_fields = $has_year && $has_section && $has_subjects;

        $project_fields = get_field('portfolio', $post->ID);

        $content_status = $this->get_content_status($post);
        $gallery_status = $this->get_gallery_status($project_fields);

        $total_items = 5;
        $completed_items = 0;
        $completed_items += $has_title ? 1 : 0;
        $completed_items += $has_thumbnail ? 1 : 0;
        $completed_items += ($content_status === 'perfect') ? 1 : 0;
        $completed_items += ($gallery_status === 'perfect') ? 1 : 0;
        $completed_items += $has_taxonomy_fields ? 1 : 0;

        $completion_percent = ($completed_items / $total_items) * 100;
        $is_complete = $completion_percent === 100;

        ?>
    <div style="margin: 0;">
      <div style="margin-bottom: 12px;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; align-items: center;">
          <strong style="font-size: 13px; color: #1f2937;">Complétude du projet</strong>
          <span style="font-size: 15px; font-weight: bold; color: <?php echo $is_complete ? '#10b981' : '#f59e0b'; ?>;">
            <?php echo (int) $completion_percent; ?>%
          </span>
        </div>
        <div style="width: 100%; height: 6px; background: #e5e7eb; border-radius: 4px; overflow: hidden;">
          <div style="width: <?php echo (int) $completion_percent; ?>%; height: 100%; background: <?php echo $is_complete ? '#10b981' : '#f59e0b'; ?>; transition: width 0.3s ease;"></div>
        </div>
      </div>
      <div style="border-top: 1px solid #e5e7eb; padding-top: 8px;">
        <?php
        $this->render_checklist_item('Titre du projet', $has_title);
        $this->render_checklist_item('Image à la une', $has_thumbnail);
        $this->render_checklist_item('Description du projet (50-100 mots)', $content_status);
        $this->render_checklist_item('Portfolio du projet (3+ éléments)', $gallery_status);
        $this->render_checklist_item('Métadonnées (année, classe, branche)', $has_taxonomy_fields);
        ?>
      </div>
      <div style="margin-top: 8px; padding: 8px; border-radius: 4px; background: <?php echo $is_complete ? '#d1fae5' : '#fef3c7'; ?>; border: 1px solid <?php echo $is_complete ? '#6ee7b7' : '#fcd34d'; ?>;">
        <p style="margin: 0; font-size: 12px; color: <?php echo $is_complete ? '#065f46' : '#92400e'; ?>;">
          <?php
            if ($is_complete) {
                echo '✓ Ton projet est complet.';
            } else {
                echo '⚠ Complète les sections manquantes.';
            }
            ?>
        </p>
      </div>
    </div>
        <?php
    }

    private function get_content_status($post)
    {
        if (empty($post->post_content)) {
            return 'not_good';
        }
        $word_count = count(preg_split('/\s+/u', trim(strip_tags($post->post_content)), -1, PREG_SPLIT_NO_EMPTY));
        if ($word_count >= 50 && $word_count <= 100) {
            return 'perfect';
        }
        return 'okay';
    }

    private function get_gallery_status($project_fields)
    {
        if (!is_array($project_fields) || empty($project_fields)) {
            return 'not_good';
        }
        $gallery_count = count($project_fields);
        if ($gallery_count >= 3) {
            return 'perfect';
        }
        return 'okay';
    }

    private function render_checklist_item($label, $status)
    {
        if (is_bool($status)) {
            $status = $status ? 'perfect' : 'not_good';
        }

        $status_config = array(
        'perfect' => array(
        'icon' => '✓',
        'color' => '#10b981',
        'text_color' => '#065f46',
        'bg_color' => '#ecfdf5'
        ),
        'okay' => array(
        'icon' => '◐',
        'color' => '#f59e0b',
        'text_color' => '#92400e',
        'bg_color' => '#fffbeb'
        ),
        'not_good' => array(
        'icon' => '◯',
        'color' => '#d1d5db',
        'text_color' => '#6b7280',
        'bg_color' => '#f9fafb'
        )
        );

        $config = isset($status_config[$status]) ? $status_config[$status] : $status_config['not_good'];
        ?>
    <div style="display: flex; align-items: center; margin: 4px 0; padding: 6px; background: <?php echo $config['bg_color']; ?>; border-radius: 4px; border-left: 3px solid <?php echo $config['color']; ?>;">
      <span style="color: <?php echo $config['color']; ?>; font-size: 16px; font-weight: bold; margin-right: 8px; min-width: 18px; text-align: center;">
        <?php echo esc_html($config['icon']); ?>
      </span>
      <span style="color: <?php echo $config['text_color']; ?>; font-size: 13px;">
        <?php echo esc_html($label); ?>
      </span>
    </div>
        <?php
    }

    private function info_box_content($post)
    {
        if ($post->post_name) {
            $frontend_url = home_url('/');

            if ($post->post_type === 'project') {
                $post_external_url = trailingslashit($frontend_url) . 'projets/' . $post->post_name . '/';
            } else {
                $post_external_url = get_permalink($post->ID);
            }

            echo '<h4 style="margin: 0 0 12px 0; font-size: 12px; color: #1f2937;">URL Externe & QR Code</h4>';
            echo '<div style="text-align: center;">';
            if ($post_external_url && filter_var($post_external_url, FILTER_VALIDATE_URL)) {
                echo '<p style="margin-bottom: 12px; word-break: break-all; font-size: 12px; color: #6b7280;">';
                echo '<a href="' . esc_url($post_external_url) . '" target="_blank" style="color: #0891b2; text-decoration: none;">' . esc_url($post_external_url) . '</a>';
                echo '</p>';
                $previous_error_reporting = error_reporting(E_ALL & ~E_DEPRECATED);
                $base64_data = QRcode::base64_webp($post_external_url, QRstr::QR_ECLEVEL_L, 50, 0);
                error_reporting($previous_error_reporting);
                echo '<img style="width: 100%; max-width: 200px; border-radius: 4px; border: 1px solid #e5e7eb;" src="' . esc_attr($base64_data) . '" alt="QR Code" />';
                echo '<p style="margin-top: 12px; font-size: 12px; color: #6b7280;">Scannez pour accéder au projet</p>';
            } else {
                echo '<p style="color: #d63638;">URL invalide pour la génération du QR code.</p>';
            }
            echo '</div>';
        } else {
            echo '<p style="padding: 12px; background: #fef3c7; border-left: 3px solid #fcd34d; border-radius: 4px; color: #92400e; font-size: 12px;">Enregistrez d\'abord le projet pour obtenir l\'URL externe et le QR Code.</p>';
        }
    }

    public function custom_rest_url($url)
    {
        return getenv('WP_HOME_ADMIN') . '/wp-json/';
    }

    public function hide_preview_button_for_drafts()
    {
        global $post;
        if (!$post) {
            return;
        }

        if ($post->post_status === 'auto-draft' || empty($post->post_name)) {
            echo '<style>
          #post-preview,
          .editor-post-preview,
          .block-editor-post-preview__button-toggle,
          .block-editor-post-preview__dropdown {
              display: none !important;
          }
      </style>';
        }
    }

    public function customize_preview_link($link, $post)
    {
        if ($post->post_status === 'auto-draft' || empty($post->post_name)) {
            return '';
        }

        $frontend_url = home_url('/');

        if ($post->post_type === 'project') {
            return trailingslashit($frontend_url) . 'projets/' . $post->post_name . '/';
        }

        return get_permalink($post);
    }

    public function customize_sample_permalink($permalink, $post_id, $title, $name, $post)
    {
        if (!$post || $post->post_type !== 'project' || empty($post->post_name)) {
            return $permalink;
        }

        $frontend_url = home_url('/');
        $project_url = trailingslashit($frontend_url) . 'projets/%postname%/';
        $slug = !empty($name) ? $name : $post->post_name;

        return array($project_url, $slug);
    }
}

new Eikon_Headless();
