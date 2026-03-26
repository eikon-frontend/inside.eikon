<?php

/**
 * User Import from CSV
 *
 * Allows administrators to import users from a CSV file
 * CSV format: Nom, Prénom, Classe, E-Mail
 */

/**
 * Add User Import menu page to WordPress admin
 */
function eikon_add_user_import_menu()
{
  add_users_page(
    __('Importer', 'eikon'),
    __('Importer', 'eikon'),
    'manage_options',
    'eikon-user-import',
    'eikon_user_import_page'
  );
}
add_action('admin_menu', 'eikon_add_user_import_menu');

/**
 * User Import Page Content
 */
function eikon_user_import_page()
{
  // Check user capabilities
  if (!current_user_can('manage_options')) {
    wp_die(__('Vous n\'avez pas les permissions nécessaires.', 'eikon'));
  }

  // Handle form submission
  if (isset($_POST['eikon_import_users_nonce'])) {
    eikon_process_user_import();
  }

?>
  <div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div style="max-width: 700px; margin: 20px 0;">
      <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
        <h2><?php _e('Importer des utilisateurs depuis un fichier CSV', 'eikon'); ?></h2>

        <p><?php _e('Le fichier CSV doit contenir les colonnes suivantes:', 'eikon'); ?></p>
        <ul style="list-style: disc; margin-left: 20px;">
          <li><code>Nom</code> <?php _e('(obligatoire)', 'eikon'); ?></li>
          <li><code>Prénom</code> <?php _e('(obligatoire)', 'eikon'); ?></li>
          <li><code>Classe</code> <?php _e('(optionnel - parmi: imd11, imd22, prepa, mp2…)', 'eikon'); ?></li>
          <li><code>E-Mail</code> <?php _e('(obligatoire)', 'eikon'); ?></li>
        </ul>

        <p style="background: #f0f6fc; padding: 10px; border-left: 4px solid #0073aa; margin: 20px 0;">
          <strong><?php _e('Note:', 'eikon'); ?></strong>
          <?php _e('Un mot de passe temporaire sera généré pour chaque utilisateur. Un email de confirmation avec un lien pour définir le mot de passe final sera envoyé à chaque utilisateur.', 'eikon'); ?>
        </p>

        <form method="post" enctype="multipart/form-data" style="margin-top: 20px;">
          <?php wp_nonce_field('eikon_import_users', 'eikon_import_users_nonce'); ?>

          <div style="margin-bottom: 20px;">
            <label for="eikon_csv_file" style="display: block; margin-bottom: 10px; font-weight: 600;">
              <?php _e('Sélectionner le fichier CSV:', 'eikon'); ?>
            </label>
            <input
              type="file"
              id="eikon_csv_file"
              name="eikon_csv_file"
              accept=".csv"
              required
              style="padding: 5px; border: 1px solid #ddd; border-radius: 3px;">
            <p style="font-size: 12px; color: #666; margin-top: 5px;">
              <?php _e('Format accepté: CSV (.csv)', 'eikon'); ?>
            </p>
          </div>

          <div style="margin-bottom: 20px;">
            <label for="eikon_user_role" style="display: block; margin-bottom: 10px; font-weight: 600;">
              <?php _e('Rôle des nouveaux utilisateurs:', 'eikon'); ?>
            </label>
            <select
              id="eikon_user_role"
              name="eikon_user_role"
              required
              style="padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
              <option value=""></option>
              <option value="none"><?php _e('— Aucun rôle —', 'eikon'); ?></option>
              <?php
              $wp_roles = wp_roles();
              foreach ($wp_roles->roles as $role_key => $role_data) {
                // Skip administrator role
                if ($role_key === 'administrator') {
                  continue;
                }
                echo '<option value="' . esc_attr($role_key) . '">' . esc_html($role_data['name']) . '</option>';
              }
              ?>
            </select>
          </div>

          <div style="margin-bottom: 20px;">
            <label style="display: flex; align-items: center;">
              <input type="checkbox" name="eikon_send_emails" value="0">
              <span style="margin-left: 10px;">
                <?php _e('Envoyer les emails de confirmation aux utilisateurs', 'eikon'); ?>
              </span>
            </label>
          </div>

          <div>
            <input
              type="submit"
              name="submit"
              class="button button-primary"
              value="<?php esc_attr_e('Importer les utilisateurs', 'eikon'); ?>">
          </div>
        </form>

        <hr style="margin: 30px 0;">

        <h3><?php _e('Exemple de fichier CSV', 'eikon'); ?></h3>
        <p><?php _e('Voici un exemple du format attendu:', 'eikon'); ?></p>
        <pre style="background: #f5f5f5; padding: 15px; overflow-x: auto; border: 1px solid #ddd; border-radius: 3px;">Nom,Prénom,Classe,E-Mail
Dupont,Jean,imd21,jean.dupont@studentfr.ch
Martin,Marie,imd31,marie.martin@studentfr.ch
Bernard,Thomas,,thomas.bernard@studentfr.ch
Dubois,Sophie,prepa,sophie.dubois@studentfr.ch</pre>

        <p>
          <a href="<?php echo esc_url(admin_url('admin.php?action=eikon_download_csv_template')); ?>" class="button">
            <?php _e('Télécharger un modèle CSV', 'eikon'); ?>
          </a>
        </p>
      </div>
    </div>

    <?php
    // Display import history if exists
    $import_log = get_option('eikon_user_import_log', array());
    if (!empty($import_log)) {
      $last_import = array_pop($import_log);
      update_option('eikon_user_import_log', $import_log);
    ?>
      <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; margin-top: 20px;">
        <h3><?php _e('Résultat de la dernière importation', 'eikon'); ?></h3>
        <div style="background: #f0f6fc; padding: 10px; border-left: 4px solid #0073aa; margin: 10px 0;">
          <p><strong><?php _e('Créés:', 'eikon'); ?></strong> <?php echo esc_html($last_import['success']); ?></p>
          <p><strong><?php _e('Mis à jour:', 'eikon'); ?></strong> <?php echo esc_html($last_import['updated'] ?? 0); ?></p>
          <p><strong><?php _e('Erreurs:', 'eikon'); ?></strong> <?php echo esc_html($last_import['errors']); ?></p>
          <p><strong><?php _e('Date:', 'eikon'); ?></strong> <?php echo esc_html($last_import['date']); ?></p>
        </div>
        <?php if (!empty($last_import['messages'])) : ?>
          <h4><?php _e('Messages détaillés:', 'eikon'); ?></h4>
          <ul style="margin-left: 20px;">
            <?php foreach ($last_import['messages'] as $message) : ?>
              <li><?php echo esc_html($message); ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    <?php
    }
  }

  /**
   * Process user import from CSV
   */
  function eikon_process_user_import()
  {
    // Verify nonce
    if (
      !isset($_POST['eikon_import_users_nonce']) ||
      !wp_verify_nonce($_POST['eikon_import_users_nonce'], 'eikon_import_users')
    ) {
      wp_die(__('Erreur de sécurité.', 'eikon'));
    }

    // Check capabilities
    if (!current_user_can('manage_options')) {
      wp_die(__('Vous n\'avez pas les permissions nécessaires.', 'eikon'));
    }

    // Check file upload
    if (empty($_FILES['eikon_csv_file']) || $_FILES['eikon_csv_file']['error'] !== UPLOAD_ERR_OK) {
      wp_die(__('Erreur lors du téléchargement du fichier.', 'eikon'));
    }

    // Check role selection
    if (!isset($_POST['eikon_user_role']) || $_POST['eikon_user_role'] === '') {
      wp_die(__('Erreur: Veuillez sélectionner un rôle pour les nouveaux utilisateurs.', 'eikon'));
    }

    $file = $_FILES['eikon_csv_file']['tmp_name'];
    $send_emails = isset($_POST['eikon_send_emails']) && $_POST['eikon_send_emails'] == '1';
    $user_role = sanitize_text_field($_POST['eikon_user_role']);

    // Validate file
    if (!is_uploaded_file($file) || mime_content_type($file) !== 'text/plain') {
      // More lenient check for CSV
      if (pathinfo($_FILES['eikon_csv_file']['name'], PATHINFO_EXTENSION) !== 'csv') {
        wp_die(__('Le fichier doit être un fichier CSV valide.', 'eikon'));
      }
    }

    // Read and process CSV
    $success_count = 0;
    $updated_count = 0;
    $error_count = 0;
    $messages = array();

    if (($handle = fopen($file, 'r')) !== false) {
      $header_row = true;
      $row_number = 0;

      while (($data = fgetcsv($handle, 1000, ',', '"', '\\')) !== false) {
        $row_number++;

        // Skip header row
        if ($header_row) {
          $header_row = false;
          continue;
        }

        // Validate row has required columns
        if (count($data) < 4) {
          $error_count++;
          $messages[] = sprintf(
            __('Ligne %d: Format invalide (colonnes manquantes).', 'eikon'),
            $row_number
          );
          continue;
        }

        $nom = sanitize_text_field(trim($data[0]));
        $prenom = sanitize_text_field(trim($data[1]));
        $classe = strtolower(sanitize_text_field(trim($data[2])));
        $email = sanitize_email(trim($data[3]));

        // Validate data
        if (empty($nom) || empty($prenom) || empty($email)) {
          $error_count++;
          $messages[] = sprintf(
            __('Ligne %d: Nom, prénom ou email manquant.', 'eikon'),
            $row_number
          );
          continue;
        }

        // Validate email format
        if (!is_email($email)) {
          $error_count++;
          $messages[] = sprintf(
            __('Ligne %d: Email invalide (%s).', 'eikon'),
            $row_number,
            $email
          );
          continue;
        }

        // Check if email already exists
        $existing_user_id = email_exists($email);

        if ($existing_user_id) {
          // Update existing user
          wp_update_user([
            'ID' => $existing_user_id,
            'first_name' => $prenom,
            'last_name' => $nom,
          ]);

          // Validate and update classe field
          if (!empty($classe)) {
            $valid_classes = eikon_get_valid_classes();
            if (in_array($classe, $valid_classes)) {
              update_user_meta($existing_user_id, 'classe', $classe);
            } else {
              $messages[] = sprintf(
                __('Ligne %d: Classe "%s" invalide (classe non définie).', 'eikon'),
                $row_number,
                $classe
              );
            }
          } else {
            delete_user_meta($existing_user_id, 'classe');
          }

          // Update role
          $user = new WP_User($existing_user_id);
          $user->set_role($user_role === 'none' ? '' : $user_role);

          $updated_count++;
          $messages[] = sprintf(
            __('Ligne %d: Utilisateur %s mis à jour.', 'eikon'),
            $row_number,
            $email
          );
        } else {
          // Create new user
          $username = sanitize_user(explode('@', $email)[0]);
          $base_username = $username;
          $counter = 1;

          // Ensure username is unique
          while (username_exists($username)) {
            $username = $base_username . $counter;
            $counter++;
          }

          // Generate temporary password
          $temporary_password = wp_generate_password(12, true);

          // Create user without automatic notifications
          add_filter('wp_send_new_user_notification_emails', '__return_false');
          $user_id = wp_create_user($username, $temporary_password, $email);
          remove_filter('wp_send_new_user_notification_emails', '__return_false');

          if (is_wp_error($user_id)) {
            $error_count++;
            $messages[] = sprintf(
              __('Ligne %d: Erreur lors de la création de l\'utilisateur (%s).', 'eikon'),
              $row_number,
              $user_id->get_error_message()
            );
            continue;
          }

          // Set user metadata
          update_user_meta($user_id, 'first_name', $prenom);
          update_user_meta($user_id, 'last_name', $nom);

          // Validate and set classe field if provided
          if (!empty($classe)) {
            $valid_classes = eikon_get_valid_classes();
            if (in_array($classe, $valid_classes)) {
              update_user_meta($user_id, 'classe', $classe);
            } else {
              $messages[] = sprintf(
                __('Ligne %d: Classe "%s" invalide (classe non définie).', 'eikon'),
                $row_number,
                $classe
              );
            }
          }

          // Set role
          $user = new WP_User($user_id);
          $user->set_role($user_role === 'none' ? '' : $user_role);

          // Send password reset email
          if ($send_emails) {
            $reset_key = get_password_reset_key($user);
            if (!is_wp_error($reset_key)) {
              eikon_send_password_reset_email($user, $reset_key);
            }
          }

          $success_count++;
          $messages[] = sprintf(
            __('Ligne %d: Utilisateur %s créé avec succès.', 'eikon'),
            $row_number,
            $email
          );
        }
      }

      fclose($handle);
    } else {
      wp_die(__('Impossible de lire le fichier CSV.', 'eikon'));
    }

    // Save import log
    $import_log = get_option('eikon_user_import_log', array());
    $import_log[] = array(
      'success' => $success_count,
      'updated' => $updated_count,
      'errors' => $error_count,
      'date' => current_time('mysql'),
      'messages' => array_slice($messages, 0, 50) // Limit to last 50 messages
    );
    update_option('eikon_user_import_log', array_slice($import_log, -10)); // Keep last 10 imports

    // Display success message
    ?>
    <div class="notice notice-success is-dismissible">
      <p>
        <strong><?php _e('Import terminé!', 'eikon'); ?></strong><br>
        <?php printf(
          __('Utilisateurs créés: %d | Mis à jour: %d | Erreurs: %d', 'eikon'),
          $success_count,
          $updated_count,
          $error_count
        ); ?>
      </p>
    </div>

    <?php if (!empty($messages)) : ?>
      <div style="max-width: 700px; margin: 20px 0; background: #fff8f0; padding: 15px; border-left: 4px solid #ffb900; border-radius: 3px;">
        <h3><?php _e('Détails de l\'importation:', 'eikon'); ?></h3>
        <ul style="margin: 10px 0; margin-left: 20px; max-height: 400px; overflow-y: auto;">
          <?php foreach ($messages as $message) : ?>
            <li><?php echo esc_html($message); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
  <?php endif;
  }

  /**
   * Download CSV template
   */
  function eikon_download_csv_template()
  {
    if (!isset($_GET['action']) || $_GET['action'] !== 'eikon_download_csv_template') {
      return;
    }

    if (!current_user_can('manage_options')) {
      wp_die(__('Vous n\'avez pas les permissions nécessaires.', 'eikon'));
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="modele-utilisateurs.csv"');

    $csv = "Nom,Prénom,Classe,E-Mail\n";
    $csv .= "Dupont,Jean,imd21,jean.dupont@studentfr.ch\n";
    $csv .= "Martin,Marie,imd31,marie.martin@studentfr.ch\n";
    $csv .= "Bernard,Thomas,,thomas.bernard@studentfr.ch\n";
    $csv .= "Dubois,Sophie,prepa,sophie.dubois@studentfr.ch\n";

    echo $csv;
    exit;
  }
  add_action('admin_init', 'eikon_download_csv_template');

  /**
   * Get valid classe options from ACF
   */
  function eikon_get_valid_classes()
  {
    $field = acf_get_field('field_69b4097379354');
    if ($field && !empty($field['choices'])) {
      return array_map('strtolower', array_keys($field['choices']));
    }

    // Fallback if ACF field not found
    return [
      'imd11',
      'imd12',
      'imd21',
      'imd22',
      'imd31',
      'imd32',
      'imd41',
      'imd42',
      'mp2',
      'prepa'
    ];
  }

  /**
   * Send password reset email to newly created user
   */
  function eikon_send_password_reset_email($user, $reset_key)
  {
    $reset_url = network_site_url("login/?action=rp&key=$reset_key&login=" . rawurlencode($user->user_login), 'login');

    $message = sprintf(
      __("Bonjour %s,\n\n", 'eikon') .
        __("Un nouveau compte a été créé pour vous sur le site Eikon.\n\n", 'eikon') .
        __("Pour configurer votre mot de passe, cliquez sur le lien suivant:\n%s\n\n", 'eikon') .
        __("Ce lien expire dans 24 heures.\n\n", 'eikon') .
        __("À bientôt sur le site Eikon!", 'eikon'),
      $user->first_name . ' ' . $user->last_name,
      $reset_url
    );

    $headers = 'Content-Type: text/plain; charset=UTF-8';

    wp_mail(
      $user->user_email,
      __('[Eikon] Activez votre compte', 'eikon'),
      $message,
      $headers
    );
  }
