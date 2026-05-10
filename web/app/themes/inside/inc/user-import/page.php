<?php

function eikon_user_import_page()
{
  if (!current_user_can('manage_options')) {
    wp_die(__('Vous n\'avez pas les permissions nécessaires.', 'eikon'));
  }

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
          <li><code>E-Mail</code> <?php _e('(obligatoire)', 'eikon'); ?></li>
          <li><code>Classe</code> <?php _e('(optionnel - parmi: imd11, imd22, prepa, mp2…)', 'eikon'); ?></li>
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
            <input type="file" id="eikon_csv_file" name="eikon_csv_file" accept=".csv" required style="padding: 5px; border: 1px solid #ddd; border-radius: 3px;">
            <p style="font-size: 12px; color: #666; margin-top: 5px;">
              <?php _e('Format accepté: CSV (.csv)', 'eikon'); ?>
            </p>
          </div>

          <div style="margin-bottom: 20px;">
            <label for="eikon_user_role" style="display: block; margin-bottom: 10px; font-weight: 600;">
              <?php _e('Rôle des nouveaux utilisateurs:', 'eikon'); ?>
            </label>
            <select id="eikon_user_role" name="eikon_user_role" required style="padding: 8px; border: 1px solid #ddd; border-radius: 3px;">
              <option value=""></option>
              <option value="none"><?php _e('— Aucun rôle —', 'eikon'); ?></option>
              <?php
              $wp_roles = wp_roles();
              foreach ($wp_roles->roles as $role_key => $role_data) {
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
            <input type="submit" name="submit" class="button button-primary" value="<?php esc_attr_e('Importer les utilisateurs', 'eikon'); ?>">
          </div>
        </form>

        <hr style="margin: 30px 0;">

        <h3><?php _e('Exemple de fichier CSV', 'eikon'); ?></h3>
        <p><?php _e('Voici un exemple du format attendu:', 'eikon'); ?></p>
        <pre style="background: #f5f5f5; padding: 15px; overflow-x: auto; border: 1px solid #ddd; border-radius: 3px;">Nom,Prénom,E-Mail,Classe
Dupont,Jean,jean.dupont@studentfr.ch,imd21
Martin,Marie,marie.martin@studentfr.ch,imd31
Bernard,Thomas,thomas.bernard@studentfr.ch
Dubois,Sophie,sophie.dubois@studentfr.ch,prepa</pre>

        <p>
          <a href="<?php echo esc_url(admin_url('admin.php?action=eikon_download_csv_template')); ?>" class="button">
            <?php _e('Télécharger un modèle CSV', 'eikon'); ?>
          </a>
        </p>
      </div>
    </div>

    <?php
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
    ?>
  </div>
<?php
}
