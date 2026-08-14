<?php
/**
 * Login screen styling and account-enumeration hardening.
 *
 * @package Dream2_MXIN
 */

if (!defined('ABSPATH')) {
    exit;
}

const DREAM2_MXIN_LOGIN_RATE_WINDOW = 15 * MINUTE_IN_SECONDS;
const DREAM2_MXIN_LOGIN_PAIR_LIMIT = 6;
const DREAM2_MXIN_LOGIN_IP_LIMIT = 20;
const DREAM2_MXIN_RESET_RATE_WINDOW = HOUR_IN_SECONDS;
const DREAM2_MXIN_RESET_SUBJECT_LIMIT = 3;
const DREAM2_MXIN_RESET_IP_LIMIT = 10;

function dream2_mxin_login_generic_error() {
    return __('登录信息不正确，请检查后重试。', 'dream2-mxin');
}

function dream2_mxin_login_request_ip() {
    $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
}

function dream2_mxin_login_rate_key($scope, $value) {
    $digest = hash_hmac('sha256', strtolower(trim((string) $value)), wp_salt('auth'));
    return 'dream2_login_' . sanitize_key($scope) . '_' . substr($digest, 0, 32);
}

function dream2_mxin_login_rate_count($key) {
    $bucket = get_transient($key);
    return is_array($bucket) ? max(0, (int) ($bucket['count'] ?? 0)) : 0;
}

function dream2_mxin_login_rate_hit($key, $ttl) {
    $count = dream2_mxin_login_rate_count($key) + 1;
    set_transient($key, array('count' => $count), $ttl);
    return $count;
}

function dream2_mxin_login_request_action() {
    $action = $_REQUEST['action'] ?? 'login';
    return is_string($action) ? sanitize_key(wp_unslash($action)) : '';
}

function dream2_mxin_login_is_interactive_request() {
    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $action = dream2_mxin_login_request_action();
    return $script === 'wp-login.php' && $method === 'POST' && ($action === '' || $action === 'login');
}

function dream2_mxin_login_pair_key($username) {
    return dream2_mxin_login_rate_key('pair', dream2_mxin_login_request_ip() . '|' . $username);
}

function dream2_mxin_login_ip_key() {
    return dream2_mxin_login_rate_key('ip', dream2_mxin_login_request_ip());
}

function dream2_mxin_login_limit_authentication($user, $username) {
    if (!dream2_mxin_login_is_interactive_request() || $username === '') {
        return $user;
    }
    if (
        dream2_mxin_login_rate_count(dream2_mxin_login_pair_key($username)) >= DREAM2_MXIN_LOGIN_PAIR_LIMIT
        || dream2_mxin_login_rate_count(dream2_mxin_login_ip_key()) >= DREAM2_MXIN_LOGIN_IP_LIMIT
    ) {
        return new WP_Error('dream2_login_limited', dream2_mxin_login_generic_error());
    }
    return $user;
}
add_filter('authenticate', 'dream2_mxin_login_limit_authentication', 30, 2);

function dream2_mxin_login_dummy_hash() {
    $hash = get_option('dream2_mxin_login_dummy_hash', '');
    if (is_string($hash) && $hash !== '') {
        return $hash;
    }
    $hash = wp_hash_password(wp_generate_password(64, true, true));
    if (!add_option('dream2_mxin_login_dummy_hash', $hash, '', false)) {
        update_option('dream2_mxin_login_dummy_hash', $hash, false);
    }
    return $hash;
}

function dream2_mxin_login_pad_unknown_account($value) {
    if ((string) $value !== '') {
        wp_check_password((string) $value, dream2_mxin_login_dummy_hash(), 0);
    }
}

function dream2_mxin_login_normalize_authentication($user, $username, $password) {
    if (!dream2_mxin_login_is_interactive_request() || !is_wp_error($user)) {
        return $user;
    }
    $codes = $user->get_error_codes();
    if (array_intersect($codes, array('invalid_username', 'invalid_email'))) {
        dream2_mxin_login_pad_unknown_account($password);
    }
    if (array_intersect($codes, array('invalid_username', 'invalid_email', 'incorrect_password', 'authentication_failed', 'spammer_account', 'dream2_login_limited'))) {
        return new WP_Error('authentication_failed', dream2_mxin_login_generic_error());
    }
    return $user;
}
add_filter('authenticate', 'dream2_mxin_login_normalize_authentication', 100, 3);

function dream2_mxin_login_record_failure($username) {
    if (!dream2_mxin_login_is_interactive_request() || (string) $username === '') {
        return;
    }
    dream2_mxin_login_rate_hit(dream2_mxin_login_pair_key($username), DREAM2_MXIN_LOGIN_RATE_WINDOW);
    dream2_mxin_login_rate_hit(dream2_mxin_login_ip_key(), DREAM2_MXIN_LOGIN_RATE_WINDOW);
}
add_action('wp_login_failed', 'dream2_mxin_login_record_failure');

function dream2_mxin_login_clear_rate_limit($username) {
    delete_transient(dream2_mxin_login_pair_key($username));
    delete_transient(dream2_mxin_login_ip_key());
}
add_action('wp_login', 'dream2_mxin_login_clear_rate_limit');

function dream2_mxin_login_normalize_page_errors($errors) {
    if (!($errors instanceof WP_Error)) {
        return $errors;
    }
    $sensitive = array('invalid_username', 'invalid_email', 'incorrect_password', 'authentication_failed', 'spammer_account', 'dream2_login_limited');
    if (!array_intersect($errors->get_error_codes(), $sensitive)) {
        return $errors;
    }
    foreach ($sensitive as $code) {
        $errors->remove($code);
    }
    $errors->add('authentication_failed', dream2_mxin_login_generic_error());
    return $errors;
}
add_filter('wp_login_errors', 'dream2_mxin_login_normalize_page_errors');

function dream2_mxin_reset_dummy_user() {
    static $user = null;
    if (!$user) {
        $user = new WP_User();
    }
    return $user;
}

function dream2_mxin_reset_subject_key($subject) {
    return dream2_mxin_login_rate_key('reset_subject', dream2_mxin_login_request_ip() . '|' . $subject);
}

function dream2_mxin_reset_ip_key() {
    return dream2_mxin_login_rate_key('reset_ip', dream2_mxin_login_request_ip());
}

function dream2_mxin_lostpassword_user_data($user_data, $errors) {
    $submitted = $_POST['user_login'] ?? '';
    $subject = is_string($submitted) ? trim(wp_unslash($submitted)) : '';
    if ($subject === '' || !($errors instanceof WP_Error)) {
        return $user_data;
    }
    $subject_key = dream2_mxin_reset_subject_key($subject);
    $ip_key = dream2_mxin_reset_ip_key();
    $limited = dream2_mxin_login_rate_count($subject_key) >= DREAM2_MXIN_RESET_SUBJECT_LIMIT
        || dream2_mxin_login_rate_count($ip_key) >= DREAM2_MXIN_RESET_IP_LIMIT;
    dream2_mxin_login_rate_hit($subject_key, DREAM2_MXIN_RESET_RATE_WINDOW);
    dream2_mxin_login_rate_hit($ip_key, DREAM2_MXIN_RESET_RATE_WINDOW);
    if (!$user_data) {
        dream2_mxin_login_pad_unknown_account($subject);
    }
    if ($limited || !$user_data) {
        $GLOBALS['dream2_mxin_reset_uses_dummy'] = true;
        return dream2_mxin_reset_dummy_user();
    }
    return $user_data;
}
add_filter('lostpassword_user_data', 'dream2_mxin_lostpassword_user_data', 10, 2);

function dream2_mxin_lostpassword_errors($errors, $user_data) {
    if (!empty($GLOBALS['dream2_mxin_reset_uses_dummy']) && $user_data === dream2_mxin_reset_dummy_user()) {
        foreach (array('invalid_email', 'invalid_username', 'invalidcombo') as $code) {
            $errors->remove($code);
        }
    }
    return $errors;
}
add_filter('lostpassword_errors', 'dream2_mxin_lostpassword_errors', 100, 2);

function dream2_mxin_skip_dummy_password_email($send, $user_login, $user_data) {
    if (!empty($GLOBALS['dream2_mxin_reset_uses_dummy']) && $user_data === dream2_mxin_reset_dummy_user()) {
        return false;
    }
    return $send;
}
add_filter('send_retrieve_password_email', 'dream2_mxin_skip_dummy_password_email', 10, 3);

function dream2_mxin_hide_public_user_rest_routes($endpoints) {
    if (is_user_logged_in()) {
        return $endpoints;
    }
    foreach (array_keys($endpoints) as $route) {
        if (preg_match('#^/wp/v2/users(?:/|$)#', $route)) {
            unset($endpoints[$route]);
        }
    }
    return $endpoints;
}
add_filter('rest_endpoints', 'dream2_mxin_hide_public_user_rest_routes');

function dream2_mxin_block_numeric_author_enumeration() {
    if (is_user_logged_in() || is_admin() || !isset($_GET['author'])) {
        return;
    }
    $author = is_string($_GET['author']) ? wp_unslash($_GET['author']) : '';
    if ($author === '' || !ctype_digit($author)) {
        return;
    }
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    nocache_headers();
    $template = get_404_template();
    if ($template) {
        include $template;
    }
    exit;
}
add_action('template_redirect', 'dream2_mxin_block_numeric_author_enumeration', 0);

function dream2_mxin_login_enqueue_assets() {
    wp_enqueue_style(
        'dream2-mxin-login',
        dream2_mxin_asset('css/login.css'),
        array(),
        dream2_mxin_asset_version('css/login.css')
    );
    $theme = sanitize_hex_color(dream2_get('theme_color', '#50bfff')) ?: '#50bfff';
    $night = sanitize_hex_color(dream2_get('night_theme_color', '#5d93db')) ?: '#5d93db';
    wp_add_inline_style(
        'dream2-mxin-login',
        ':root{--dream2-login-accent:' . $theme . ';--dream2-login-accent-night:' . $night . ';}'
    );
}
add_action('login_enqueue_scripts', 'dream2_mxin_login_enqueue_assets');

function dream2_mxin_login_body_class($classes) {
    $classes[] = 'dream2-login';
    return $classes;
}
add_filter('login_body_class', 'dream2_mxin_login_body_class');

function dream2_mxin_login_header_url() {
    return home_url('/');
}
add_filter('login_headerurl', 'dream2_mxin_login_header_url');

function dream2_mxin_login_header_text() {
    return sprintf(__('%s · 管理入口', 'dream2-mxin'), get_bloginfo('name'));
}
add_filter('login_headertext', 'dream2_mxin_login_header_text');

function dream2_mxin_login_message($message) {
    $action = dream2_mxin_login_request_action();
    if (($action === '' || $action === 'login') && trim((string) $message) === '') {
        return '<p class="dream2-login-intro">' . esc_html__('登录站点后台', 'dream2-mxin') . '</p>';
    }
    return $message;
}
add_filter('login_message', 'dream2_mxin_login_message');

function dream2_mxin_prepare_login_security() {
    dream2_mxin_login_dummy_hash();
}
add_action('after_switch_theme', 'dream2_mxin_prepare_login_security');
