<?php

/**
 * Plugin Name: Eikon SSO — Microsoft Entra Integration
 * Description: Automatic role mapping and user matching for OpenID Connect SSO via Microsoft Entra.
 * Version: 1.0.0
 * Author: Eikon
 *
 * Works with the "OpenID Connect Generic" plugin (daggerhart).
 * Maps Microsoft Entra claims to WordPress roles:
 *   - @edufr.ch    → teacher
 *   - @studentfr.ch → student
 *
 * Matches existing WP users by email to preserve content ownership.
 *
 * @package EikonSSO
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Configure OIDC plugin settings dynamically.
 *
 * Sets nickname_key to 'email' because Microsoft Graph userinfo endpoint
 * provides 'email' but does not include 'preferred_username'.
 *
 * @param object $settings The plugin settings object.
 * @return object
 */
function eikon_sso_configure_settings($settings)
{
    $settings->nickname_key = 'email';
    return $settings;
}
add_filter('openid-connect-generic-settings', 'eikon_sso_configure_settings');

/**
 * Ensure both preferred_username and email are present in user claims.
 *
 * @param array<string, mixed> $user_claim The claims array from the IdP.
 * @return array<string, mixed>
 */
function eikon_sso_alter_user_claim($user_claim)
{
    if (empty($user_claim['email']) && !empty($user_claim['preferred_username'])) {
        $user_claim['email'] = $user_claim['preferred_username'];
    }

    if (empty($user_claim['preferred_username']) && !empty($user_claim['email'])) {
        $user_claim['preferred_username'] = $user_claim['email'];
    }

    return $user_claim;
}
add_filter('openid-connect-generic-alter-user-claim', 'eikon_sso_alter_user_claim');

/**
 * Assign WordPress role based on the Microsoft Entra preferred_username claim.
 *
 * The FRITIC token provides:
 *   - preferred_username: prenom.nom@edufr.ch (staff) or @studentfr.ch (students)
 *   - email, given_name, family_name
 *
 * Fires after the OIDC plugin creates or updates a user.
 *
 * @param WP_User $user       The WordPress user object.
 * @param array   $user_claim The OIDC user claims from the ID token.
 */
function eikon_sso_map_role_from_claims($user, $user_claim)
{
    // Don't change admin roles
    if (in_array('administrator', $user->roles, true) || is_super_admin($user->ID)) {
        return;
    }

    // Don't change editor roles
    if (in_array('editor', $user->roles, true)) {
        return;
    }

    $preferred_username = $user_claim['preferred_username'] ?? '';
    $email = $user_claim['email'] ?? $preferred_username;

    // Determine role based on email domain
    if (eikon_sso_is_student_email($preferred_username) || eikon_sso_is_student_email($email)) {
        $target_role = 'student';
    } elseif (eikon_sso_is_staff_email($preferred_username) || eikon_sso_is_staff_email($email)) {
        $target_role = 'teacher';
    } else {
        // Unknown domain — assign student as safe default
        $target_role = 'student';
    }

    // Only change if the user doesn't already have the right role
    if (!in_array($target_role, $user->roles, true)) {
        // Remove current non-admin roles
        foreach ($user->roles as $role) {
            if (!in_array($role, ['administrator', 'editor'], true)) {
                $user->remove_role($role);
            }
        }
        $user->add_role($target_role);
    }

    // Update display name from claims if available
    $given_name  = $user_claim['given_name'] ?? '';
    $family_name = $user_claim['family_name'] ?? '';

    if ($given_name && $family_name) {
        $display_name = $given_name . ' ' . $family_name;
        wp_update_user([
            'ID'           => $user->ID,
            'first_name'   => $given_name,
            'last_name'    => $family_name,
            'display_name' => $display_name,
        ]);
    }
}
add_action('openid-connect-generic-update-user-using-current-claim', 'eikon_sso_map_role_from_claims', 10, 2);

/**
 * Match existing WordPress users by email when logging in via SSO.
 *
 * Without this filter, the OIDC plugin may create duplicate accounts
 * instead of linking to existing users who were created manually.
 *
 * @param WP_User|null $user       The matched user, or null.
 * @param array        $user_claim The OIDC user claims.
 * @return WP_User|null
 */
function eikon_sso_match_existing_user_by_email($user, $user_claim)
{
    // If a user was already matched, don't override
    if ($user instanceof WP_User) {
        return $user;
    }

    // Try to find existing user by email from claims
    $email = $user_claim['email'] ?? ($user_claim['preferred_username'] ?? '');

    if (empty($email) || !is_email($email)) {
        return $user;
    }

    $existing_user = get_user_by('email', $email);

    if ($existing_user instanceof WP_User) {
        return $existing_user;
    }

    return $user;
}
add_filter('openid-connect-generic-user-login-test', 'eikon_sso_match_existing_user_by_email', 10, 2);

/**
 * Also match by email during the user creation phase.
 *
 * The OIDC plugin calls this filter to check if a user already exists
 * before creating a new one. We ensure matching by email works.
 *
 * @param WP_User|false $user       The matched user, or false.
 * @param array         $user_claim The OIDC user claims.
 * @return WP_User|false
 */
function eikon_sso_pre_create_user_match($user, $user_claim)
{
    if ($user instanceof WP_User) {
        return $user;
    }

    $email = $user_claim['email'] ?? ($user_claim['preferred_username'] ?? '');

    if (empty($email) || !is_email($email)) {
        return $user;
    }

    $existing_user = get_user_by('email', $email);

    if ($existing_user instanceof WP_User) {
        return $existing_user;
    }

    return $user;
}
add_filter('openid-connect-generic-user-creation-test', 'eikon_sso_pre_create_user_match', 10, 2);

/**
 * Check if an email belongs to the student domain.
 *
 * @param string $email
 * @return bool
 */
function eikon_sso_is_student_email($email)
{
    return !empty($email) && str_ends_with(strtolower($email), '@studentfr.ch');
}

/**
 * Check if an email belongs to the staff/teacher domain.
 *
 * @param string $email
 * @return bool
 */
function eikon_sso_is_staff_email($email)
{
    return !empty($email) && str_ends_with(strtolower($email), '@edufr.ch');
}

/**
 * Customize the SSO login button text on the wp-login page.
 *
 * @param string $text Default button text.
 * @return string
 */
function eikon_sso_login_button_text($text)
{
    return 'Se connecter avec edufr.ch';
}
add_filter('openid-connect-generic-login-button-text', 'eikon_sso_login_button_text');

/**
 * Style the SSO login button on the wp-login page.
 */
function eikon_sso_login_styles()
{
    echo '<style>
        .openid-connect-login-button {
            display: block !important;
            width: 100% !important;
            text-align: center !important;
            padding: 12px 20px !important;
            margin: 16px 0 24px !important;
            color: #fff !important;
            border: none !important;
            border-radius: 4px !important;
            font-size: 14px !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            text-decoration: none !important;
            box-sizing: border-box !important;
            transition: background-color 0.2s ease !important;
        }
        .openid-connect-login-button:hover,
        .openid-connect-login-button:focus {
            color: #fff !important;
        }
        /* Separator between SSO and local login */
        .openid-connect-login-button + #loginform::before {
            content: "— ou connexion locale —";
            display: block;
            text-align: center;
            color: #72777c;
            font-size: 13px;
            margin: 0 0 16px;
        }
    </style>';
}
add_action('login_enqueue_scripts', 'eikon_sso_login_styles');

/**
 * Log SSO events for debugging (only in development/staging).
 *
 * @param WP_User $user       The WordPress user object.
 * @param array   $user_claim The OIDC user claims.
 */
function eikon_sso_log_login($user, $user_claim)
{
    if (defined('WP_ENV') && WP_ENV === 'production') {
        return;
    }

    $preferred_username = $user_claim['preferred_username'] ?? 'unknown';
    $email = $user_claim['email'] ?? 'unknown';

    error_log(sprintf(
        '[Eikon SSO] User login: WP_ID=%d, email=%s, preferred_username=%s, roles=%s',
        $user->ID,
        $email,
        $preferred_username,
        implode(',', $user->roles)
    ));
}
add_action('openid-connect-generic-update-user-using-current-claim', 'eikon_sso_log_login', 20, 2);
