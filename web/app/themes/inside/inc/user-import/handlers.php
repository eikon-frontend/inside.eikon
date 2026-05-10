<?php

function eikon_process_user_import()
{
  if (
    !isset($_POST['eikon_import_users_nonce']) ||
    !wp_verify_nonce($_POST['eikon_import_users_nonce'], 'eikon_import_users')
  ) {
    wp_die(__('Erreur de sécurité.', 'eikon'));
  }

  if (!current_user_can('manage_options')) {
    wp_die(__('Vous n\'avez pas les permissions nécessaires.', 'eikon'));
  }

  if (empty($_FILES['eikon_csv_file']) || $_FILES['eikon_csv_file']['error'] !== UPLOAD_ERR_OK) {
    wp_die(__('Erreur lors du téléchargement du fichier.', 'eikon'));
  }

  if (!isset($_POST['eikon_user_role']) || $_POST['eikon_user_role'] === '') {
    wp_die(__('Erreur: Veuillez sélectionner un rôle pour les nouveaux utilisateurs.', 'eikon'));
  }

  $file = $_FILES['eikon_csv_file']['tmp_name'];
  $send_emails = isset($_POST['eikon_send_emails']) && $_POST['eikon_send_emails'] == '1';
  $user_role = sanitize_text_field($_POST['eikon_user_role']);

  if (!is_uploaded_file($file) || mime_content_type($file) !== 'text/plain') {
    if (pathinfo($_FILES['eikon_csv_file']['name'], PATHINFO_EXTENSION) !== 'csv') {
      wp_die(__('Le fichier doit être un fichier CSV valide.', 'eikon'));
    }
  }

  $success_count = 0;
  $updated_count = 0;
  $error_count = 0;
  $messages = array();

  if (($handle = fopen($file, 'r')) !== false) {
    $header_row = true;
    $row_number = 0;

    while (($data = fgetcsv($handle, 1000, ',', '"', '\\')) !== false) {
      $row_number++;

      if ($header_row) {
        $header_row = false;
        continue;
      }

      if (count($data) < 3) {
        $error_count++;
        $messages[] = sprintf(
          __('Ligne %d: Format invalide (colonnes manquantes).', 'eikon'),
          $row_number
        );
        continue;
      }

      $nom = sanitize_text_field(trim($data[0]));
      $prenom = sanitize_text_field(trim($data[1]));
      $email = strtolower(sanitize_email(trim($data[2])));
      $classe = isset($data[3]) ? strtolower(sanitize_text_field(trim($data[3]))) : '';

      if (empty($nom) || empty($prenom) || empty($email)) {
        $error_count++;
        $messages[] = sprintf(
          __('Ligne %d: Nom, prénom ou email manquant.', 'eikon'),
          $row_number
        );
        continue;
      }

      if (!is_email($email)) {
        $error_count++;
        $messages[] = sprintf(
          __('Ligne %d: Email invalide (%s).', 'eikon'),
          $row_number,
          $email
        );
        continue;
      }

      $existing_user_id = email_exists($email);

      if ($existing_user_id) {
        wp_update_user([
          'ID' => $existing_user_id,
          'first_name' => $prenom,
          'last_name' => $nom,
          'display_name' => $prenom . ' ' . $nom,
        ]);

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

        $user = new WP_User($existing_user_id);
        $user->set_role($user_role === 'none' ? '' : $user_role);

        $updated_count++;
        $messages[] = sprintf(
          __('Ligne %d: Utilisateur %s mis à jour.', 'eikon'),
          $row_number,
          $email
        );
      } else {
        $username = strtolower(sanitize_user(explode('@', $email)[0]));
        $base_username = $username;
        $counter = 1;

        while (username_exists($username)) {
          $username = $base_username . $counter;
          $counter++;
        }

        $temporary_password = wp_generate_password(12, true);

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

        wp_update_user([
          'ID' => $user_id,
          'display_name' => $prenom . ' ' . $nom,
        ]);
        update_user_meta($user_id, 'first_name', $prenom);
        update_user_meta($user_id, 'last_name', $nom);

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

        $user = new WP_User($user_id);
        $user->set_role($user_role === 'none' ? '' : $user_role);

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

  $import_log = get_option('eikon_user_import_log', array());
  $import_log[] = array(
    'success' => $success_count,
    'updated' => $updated_count,
    'errors' => $error_count,
    'date' => current_time('mysql'),
    'messages' => array_slice($messages, 0, 50)
  );
  update_option('eikon_user_import_log', array_slice($import_log, -10));

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

  $csv = "Nom,Prénom,E-Mail,Classe\n";
  $csv .= "Dupont,Jean,jean.dupont@studentfr.ch,imd21\n";
  $csv .= "Martin,Marie,marie.martin@studentfr.ch,imd31\n";
  $csv .= "Bernard,Thomas,thomas.bernard@studentfr.ch\n";
  $csv .= "Dubois,Sophie,sophie.dubois@studentfr.ch,prepa\n";

  echo $csv;
  exit;
}
add_action('admin_init', 'eikon_download_csv_template');

function eikon_get_valid_classes()
{
  $field = acf_get_field('field_69b4097379354');
  if ($field && !empty($field['choices'])) {
    return array_map('strtolower', array_keys($field['choices']));
  }

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
