<?php

/**
 * Class Eikon_Images
 *
 * Gère le traitement des images, la validation des noms de fichiers, et les conversions WebP.
 */
class Eikon_Images
{
    const FILENAME_REGEX = '/^[0-9]{2,4}_[0-9]{2,4}_[a-zA-Z0-9]+(?:_[a-zA-Z0-9]+){3,8}(?:_(Re|Ex)(?:_[0-9]+)?)?(?:_[0-9]+)?\.[a-zA-Z0-9]+$/';
    const FILENAME_ERROR = 'Erreur : Le nom de votre fichier ne respecte pas la nomenclature de l\'école. Exemple: 25_26_IMD11_CIE_MonTitre_Dupont_Marie.jpg';

    public function __construct()
    {
        add_theme_support('post-thumbnails');

        add_filter('wp_handle_upload_prefilter', array($this, 'validate_upload_filename'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_filename_validation'));

        add_filter('intermediate_image_sizes', array($this, 'filter_image_sizes'));
        add_filter('wp_generate_attachment_metadata', array($this, 'convert_to_webp'));
        add_action('delete_attachment', array($this, 'delete_webp_version'));
        add_filter('wp_get_attachment_url', array($this, 'serve_webp_url'), 10, 2);

        add_filter('upload_mimes', array($this, 'restrict_mimes_to_images'));
        add_filter('wp_check_filetype_and_ext', array($this, 'check_filetype_and_ext'), 10, 4);
        add_filter('upload_error_messages', array($this, 'custom_upload_error_message'));

        add_action('admin_menu', array($this, 'remove_add_new_media_page'));
        add_action('admin_init', array($this, 'redirect_media_new_page'));
        add_action('admin_menu', array($this, 'rename_media_library_to_images'), 999);
    }

    private function user_must_validate_filename()
    {
        $current_user = wp_get_current_user();
        if (!$current_user->exists()) {
            return false;
        }
        return in_array('student', $current_user->roles, true) || in_array('teacher', $current_user->roles, true);
    }

    private function get_current_academic_year()
    {
        $month = (int) date('n');
        $year  = (int) date('y');
        if ($month >= 9) {
            return sprintf('%02d_%02d', $year, $year + 1);
        }
        return sprintf('%02d_%02d', $year - 1, $year);
    }

    private function validate_filename_string($filename)
    {
        if (!preg_match(self::FILENAME_REGEX, $filename)) {
            return self::FILENAME_ERROR;
        }

        $base = pathinfo($filename, PATHINFO_FILENAME);
        $current_year = $this->get_current_academic_year();

        if (preg_match('/^(\d{2,4})_(\d{2,4})/', $base, $m)) {
            $y1 = (int) $m[1];
            $y2 = (int) $m[2];
            if ($y2 !== $y1 + 1) {
                return sprintf(
                    'Erreur : L\'année "%s_%s" n\'est pas valide (le deuxième chiffre doit être le suivant). Utilisez l\'année académique en cours : %s. Exemple: %s_IMD11_CIE_MonTitre_Dupont_Marie.jpg',
                    $m[1],
                    $m[2],
                    $current_year,
                    $current_year
                );
            }
        }

        $segments = array_slice(explode('_', $base), 2);
        $placeholders = ['titre', 'nom', 'prenom', 'montitre'];
        foreach ($segments as $segment) {
            if (in_array(strtolower($segment), $placeholders, true)) {
                return sprintf(
                    'Erreur : Le nom de fichier contient des mots génériques ("%s"). Remplacez-les par vos vraies informations. Exemple: %s_IMD11_CIE_MonTitre_Dupont_Marie.jpg',
                    $segment,
                    $current_year
                );
            }
        }

        return null;
    }

    public function validate_upload_filename($file)
    {
        if (!$this->user_must_validate_filename()) {
            return $file;
        }

        $error = $this->validate_filename_string($file['name']);
        if ($error !== null) {
            $file['error'] = $error;
        }

        return $file;
    }

    public function enqueue_filename_validation($hook)
    {
        if (!$this->user_must_validate_filename()) {
            return;
        }

        wp_enqueue_script(
            'eikon-filename-validation',
            get_template_directory_uri() . '/js/filename-validation.js',
            ['jquery'],
            filemtime(get_template_directory() . '/js/filename-validation.js'),
            true
        );

        wp_localize_script('eikon-filename-validation', 'eikonFilename', [
        'regex'        => '^' . ltrim(rtrim(self::FILENAME_REGEX, '/'), '/') . '$', // Format for JS
        'errorMessage' => self::FILENAME_ERROR,
        'currentYear'  => $this->get_current_academic_year(),
        'placeholders' => ['titre', 'nom', 'prenom', 'montitre'],
        ]);
    }

    public function filter_image_sizes($sizes)
    {
        return array_filter($sizes, function ($val) {
            return !in_array($val, ['medium_large', '1536x1536', '2048x2048']);
        });
    }

    public function convert_to_webp($metadata)
    {
        if (!isset($metadata['file'])) {
            return $metadata;
        }

        $upload_dir = wp_upload_dir();
        $original_file = $upload_dir['basedir'] . '/' . $metadata['file'];

        if (!preg_match('/\.webp$/i', $original_file)) {
            $original_webp_file = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $original_file);
            $original_image = wp_get_image_editor($original_file);
            if (!is_wp_error($original_image)) {
                $original_image->save($original_webp_file, 'image/webp');
            }
        }

        if (isset($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $size => $sizeInfo) {
                $file = $upload_dir['basedir'] . '/' . dirname($metadata['file']) . '/' . $sizeInfo['file'];
                if (!preg_match('/\.webp$/i', $file)) {
                    $webp_file = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $file);
                    $image = wp_get_image_editor($file);
                    if (!is_wp_error($image)) {
                        $image->save($webp_file, 'image/webp');
                        if (file_exists($file)) {
                              unlink($file);
                        }
                        $metadata['sizes'][$size]['file'] = basename($webp_file);
                    }
                }
            }
        }
        return $metadata;
    }

    public function delete_webp_version($post_id)
    {
        $file = get_attached_file($post_id);
        if (!$file) {
            return;
        }

        $webp_file = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $file);
        if (file_exists($webp_file)) {
            unlink($webp_file);
        }

        $metadata = wp_get_attachment_metadata($post_id);
        if (isset($metadata['sizes'])) {
            $upload_dir = wp_upload_dir();
            foreach ($metadata['sizes'] as $size => $sizeInfo) {
                $size_file = $upload_dir['basedir'] . '/' . dirname($metadata['file']) . '/' . $sizeInfo['file'];
                $size_webp_file = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $size_file);
                if (file_exists($size_webp_file)) {
                    unlink($size_webp_file);
                }
            }
        }
    }

    public function serve_webp_url($url, $post_id)
    {
        $upload_dir = wp_upload_dir();
        $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $url);
        $webp_file_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $file_path);

        if (file_exists($webp_file_path)) {
            $url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $webp_file_path);
        }

        return $url;
    }

    public function restrict_mimes_to_images($mimes)
    {
        $mimes = array();
        $mimes['jpg|jpeg|jpe'] = 'image/jpeg';
        $mimes['png'] = 'image/png';
        $mimes['webp'] = 'image/webp';
        $mimes['zip'] = 'application/zip';
        return $mimes;
    }

    public function check_filetype_and_ext($data, $file, $filename, $mimes)
    {
        $wp_filetype = wp_check_filetype($filename, $mimes);
        $ext = $wp_filetype['ext'];

        $allowed_extensions = array('jpg', 'jpeg', 'jpe', 'gif', 'png', 'bmp', 'tiff', 'tif', 'webp', 'ico', 'heic', 'svg');

        if (!in_array($ext, $allowed_extensions)) {
            return array(
            'ext' => false,
            'type' => false,
            'proper_filename' => false
            );
        }

        return $data;
    }

    public function custom_upload_error_message($messages)
    {
        $messages[false] = __('Sorry! This file type is not allowed. Only image files (JPG, PNG, WebP, SVG, etc.) are permitted.');
        return $messages;
    }

    public function remove_add_new_media_page()
    {
        remove_submenu_page('upload.php', 'media-new.php');
    }

    public function redirect_media_new_page()
    {
        global $pagenow;
        if ($pagenow === 'media-new.php') {
            wp_redirect(admin_url('upload.php'));
            exit;
        }
    }

    public function rename_media_library_to_images()
    {
        global $submenu;
        if (isset($submenu['upload.php'])) {
            foreach ($submenu['upload.php'] as $key => $item) {
                if ($item[2] === 'upload.php') {
                    $submenu['upload.php'][$key][0] = 'Images';
                    break;
                }
            }
        }
    }
}

new Eikon_Images();
