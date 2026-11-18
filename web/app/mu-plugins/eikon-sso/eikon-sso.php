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
 * - Sets nickname_key to 'email' because Microsoft Graph userinfo endpoint
 *   provides 'email' but does not include 'preferred_username'.
 * - Enables linking existing users by email.
 * - Enables linking unverified users (Microsoft Entra does not send email_verified claim).
 *
 * @param object $settings The plugin settings object.
 * @return object
 */
function eikon_sso_configure_settings($settings)
{
    $settings->nickname_key = 'email';
    $settings->link_existing_users = true;
    $settings->link_unverified_users = true;
    return $settings;
}
add_filter('openid-connect-generic-settings', 'eikon_sso_configure_settings');

/**
 * Ensure both preferred_username and email are present in user claims,
 * and mark email as verified for Entra ID accounts.
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

    // Mark email as verified since edufr.ch / studentfr.ch accounts are verified by the state.
    $user_claim['email_verified'] = true;

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
add_action('openid-connect-generic-user-create', 'eikon_sso_map_role_from_claims', 10, 2);

/**
 * Allow inside.eikon.ch as a valid redirect host.
 *
 * In Bedrock headless setup, WP_HOME is eikon.ch (frontend), so WordPress
 * wp_validate_redirect treats inside.eikon.ch (backend) as external by default.
 *
 * @param array<string> $hosts List of allowed redirect hosts.
 * @return array<string>
 */
function eikon_sso_allowed_redirect_hosts($hosts)
{
    $admin_host = wp_parse_url(admin_url(), PHP_URL_HOST);
    if (!empty($admin_host)) {
        $hosts[] = (string) $admin_host;
    }
    return array_unique($hosts);
}
add_filter('allowed_redirect_hosts', 'eikon_sso_allowed_redirect_hosts');

/**
 * Default the OIDC client redirect destination to the WP Admin dashboard.
 *
 * @return string
 */
function eikon_sso_default_redirect_to()
{
    return admin_url();
}
add_filter('openid-connect-generic-client-redirect-to', 'eikon_sso_default_redirect_to');

/**
 * Catch and redirect to the WP Admin dashboard after SSO login.
 *
 * Prevents redirecting to home_url() (the headless frontend eikon.ch).
 *
 * @param string $location The redirect location.
 * @return string
 */
function eikon_sso_fix_login_redirect($location)
{
    if (isset($_GET['action']) && $_GET['action'] === 'openid-connect-authorize') {
        if (rtrim($location, '/') === rtrim(home_url(), '/')) {
            return admin_url();
        }
    }
    return $location;
}
add_filter('wp_redirect', 'eikon_sso_fix_login_redirect', 10, 1);

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
