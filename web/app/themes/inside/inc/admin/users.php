<?php

/**
 * Custom function to validate user registration email addresses to restrict to edufr.ch
 *
 * @param WP_Error $errors An object containing any errors encountered during registration.
 * @param string $sanitized_user_login The sanitized username.
 * @param string $user_email The user's email address.
 * @return WP_Error The updated error object.
 */
add_filter('registration_errors', 'myplugin_registration_errors', 10, 3);
function myplugin_registration_errors($errors, $sanitized_user_login, $user_email)
{
  if (! preg_match('/( |^)[^ ]+@edufr\.ch( |$)/', $user_email)) {
    $errors->add('invalid_email', __("Seule l'adresse e-mail « edufr.ch » est autorisée."));
    $user_email = '';
  }
  return $errors;
}

/**
 * Add "Classe" column to the users list table
 */
add_filter('manage_users_columns', 'eikon_add_classe_column');
function eikon_add_classe_column($columns)
{
  $columns['classe'] = __('Classe');
  return $columns;
}

add_filter('manage_users_custom_column', 'eikon_show_classe_column', 10, 3);
function eikon_show_classe_column($value, $column_name, $user_id)
{
  if ('classe' === $column_name) {
    $classe = get_user_meta($user_id, 'classe', true);
    return $classe ? esc_html($classe) : '—';
  }
  return $value;
}

/**
 * Add a "Classe" filter dropdown above the users list table
 */
add_action('views_users', 'eikon_add_classe_filter_links');
function eikon_add_classe_filter_links($views)
{
  $role = isset($_GET['role']) ? sanitize_text_field($_GET['role']) : '';

  if ('student' !== $role) {
    return $views;
  }

  $classes = ['imd11', 'imd12', 'imd21', 'imd31', 'imd32', 'mp2', 'prepa'];
  $selected = isset($_GET['classe_filter']) ? sanitize_text_field($_GET['classe_filter']) : '';
  $classe_links = [];

  $all_url = add_query_arg(['role' => 'student'], admin_url('users.php'));
  $all_count = count(get_users(['role' => 'student', 'fields' => 'ID']));
  $all_class = empty($selected) ? 'current' : '';
  $classe_links[] = sprintf(
    '<a href="%s" class="%s">Toutes les classes <span class="count">(%d)</span></a>',
    esc_url($all_url),
    esc_attr($all_class),
    $all_count
  );

  foreach ($classes as $classe) {
    $count = count(get_users([
      'role'       => 'student',
      'meta_key'   => 'classe',
      'meta_value' => $classe,
      'fields'     => 'ID',
    ]));
    if ($count === 0) {
      continue;
    }
    $url = add_query_arg(['role' => 'student', 'classe_filter' => $classe], admin_url('users.php'));
    $css_class = ($selected === $classe) ? 'current' : '';
    $classe_links[] = sprintf(
      '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
      esc_url($url),
      esc_attr($css_class),
      esc_html($classe),
      $count
    );
  }

  $last_key = array_key_last($views);
  $views[$last_key] .= '</ul><ul class="subsubsub" style="clear:both; width:100%;">'
    . implode(' | ', $classe_links);

  return $views;
}

/**
 * Filter the users list by "Classe" ACF field
 */
add_filter('pre_get_users', 'eikon_filter_users_by_classe');
function eikon_filter_users_by_classe($query)
{
  global $pagenow;

  if (!is_admin() || 'users.php' !== $pagenow) {
    return;
  }

  if (empty($_GET['classe_filter'])) {
    return;
  }

  $classe = sanitize_text_field($_GET['classe_filter']);

  $meta_query = $query->get('meta_query') ?: [];
  $meta_query[] = [
    'key'     => 'classe',
    'value'   => $classe,
    'compare' => '=',
  ];
  $query->set('meta_query', $meta_query);
}

/**
 * Add "Changer de classe" dropdown next to bulk actions on users page
 */
add_action('restrict_manage_users', 'eikon_add_classe_bulk_change');
function eikon_add_classe_bulk_change($which)
{
  $classes = ['imd11', 'imd12', 'imd21', 'imd31', 'imd32', 'mp2', 'prepa'];
?>
  <label class="screen-reader-text" for="eikon_classe_<?php echo esc_attr($which); ?>">Changer de classe pour&hellip;</label>
  <select name="eikon_classe_<?php echo esc_attr($which); ?>" id="eikon_classe_<?php echo esc_attr($which); ?>">
    <option value="">Changer de classe pour&hellip;</option>
    <?php foreach ($classes as $classe) : ?>
      <option value="<?php echo esc_attr($classe); ?>"><?php echo esc_html($classe); ?></option>
    <?php endforeach; ?>
  </select>
  <?php submit_button('Modifier', 'secondary', 'eikon_changeit', false); ?>
  <script>
    (function() {
      var select = document.getElementById('eikon_classe_<?php echo esc_js($which); ?>');
      var tablenav = select.closest('.tablenav');
      var wpBtn = tablenav.querySelector('#changeit');
      if (wpBtn) wpBtn.style.display = 'none';
      var btn = tablenav.querySelector('[name="eikon_changeit"]');
      if (btn) {
        btn.addEventListener('click', function(e) {
          var roleSelect = tablenav.querySelector('[name^="new_role"]');
          var classeSelect = document.getElementById('eikon_classe_<?php echo esc_js($which); ?>');
          var hasRole = roleSelect && roleSelect.value !== '' && roleSelect.value !== '-1';
          var hasClasse = classeSelect && classeSelect.value !== '';
          if (hasRole && !hasClasse) {
            e.preventDefault();
            wpBtn.click();
          }
        });
      }
    })();
  </script>
<?php
}

/**
 * Handle the bulk classe change
 */
add_action('admin_init', 'eikon_handle_bulk_classe_change');
function eikon_handle_bulk_classe_change()
{
  global $pagenow;

  if ('users.php' !== $pagenow) {
    return;
  }

  if (!isset($_REQUEST['eikon_changeit'])) {
    return;
  }

  if (!current_user_can('manage_users')) {
    return;
  }

  $user_ids = isset($_REQUEST['users']) ? array_map('intval', (array) $_REQUEST['users']) : [];
  if (empty($user_ids)) {
    return;
  }

  check_admin_referer('bulk-users');

  $classe = '';
  if (!empty($_REQUEST['eikon_classe_top'])) {
    $classe = sanitize_text_field($_REQUEST['eikon_classe_top']);
  } elseif (!empty($_REQUEST['eikon_classe_bottom'])) {
    $classe = sanitize_text_field($_REQUEST['eikon_classe_bottom']);
  }

  $new_role = '';
  if (!empty($_REQUEST['new_role'])) {
    $new_role = sanitize_text_field($_REQUEST['new_role']);
  } elseif (!empty($_REQUEST['new_role2'])) {
    $new_role = sanitize_text_field($_REQUEST['new_role2']);
  }

  if (empty($classe) && empty($new_role)) {
    return;
  }

  $messages = [];

  if (!empty($classe)) {
    foreach ($user_ids as $user_id) {
      update_user_meta($user_id, 'classe', $classe);
    }
    $messages[] = 'classe_changed';
  }

  if (!empty($new_role) && $new_role !== '-1') {
    $editable_roles = array_keys(get_editable_roles());
    if (in_array($new_role, $editable_roles, true)) {
      foreach ($user_ids as $user_id) {
        $user = get_userdata($user_id);
        if ($user) {
          $user->set_role($new_role);
        }
      }
      $messages[] = 'role_changed';
    }
  }

  $redirect = add_query_arg([
    'update' => implode(',', $messages),
    'count'  => count($user_ids),
  ], wp_get_referer() ?: admin_url('users.php'));
  wp_safe_redirect($redirect);
  exit;
}

/**
 * Display admin notice after bulk classe change
 */
add_action('admin_notices', 'eikon_bulk_classe_change_notice');
function eikon_bulk_classe_change_notice()
{
  global $pagenow;

  if ('users.php' !== $pagenow || empty($_GET['update'])) {
    return;
  }

  $updates = explode(',', sanitize_text_field($_GET['update']));
  $count = isset($_GET['count']) ? intval($_GET['count']) : 0;
  $messages = [];

  if (in_array('classe_changed', $updates, true)) {
    $messages[] = sprintf('Classe modifiée pour %d compte(s).', $count);
  }
  if (in_array('role_changed', $updates, true)) {
    $messages[] = sprintf('Rôle modifié pour %d compte(s).', $count);
  }

  foreach ($messages as $msg) {
    printf('<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html($msg));
  }
}
