<?php
/**
 * Private comments, compatible with the behavior used on kr.mxin.moe.
 *
 * @package Dream2_MXIN
 */

if (!defined('ABSPATH')) {
    exit;
}

const DREAM2_MXIN_PRIVATE_META = 'private_comment';
const DREAM2_MXIN_PRIVATE_COOKIE_PREFIX = 'dream2_private_comment_';
const DREAM2_MXIN_COMMENT_IDENTITY_COOKIE = 'dream2_comment_identity_';

function dream2_mxin_normalize_comment_identity_name($name) {
    $name = sanitize_text_field((string) $name);
    $name = preg_replace('/\s+/u', ' ', trim($name));
    return is_string($name) ? $name : '';
}

function dream2_mxin_normalize_private_comment_email($email) {
    return strtolower(trim((string) $email));
}

function dream2_mxin_normalize_comment_identity_site($url) {
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }
    if (!preg_match('#^https?://#i', $url)) {
        $url = 'https://' . ltrim($url, '/');
    }
    $host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
    $host = rtrim($host, '.');
    if (strpos($host, 'www.') === 0) {
        $host = substr($host, 4);
    }
    return $host;
}

function dream2_mxin_comment_identity_token($name, $email, $url) {
    $name = dream2_mxin_normalize_comment_identity_name($name);
    $email = dream2_mxin_normalize_private_comment_email($email);
    $site = dream2_mxin_normalize_comment_identity_site($url);
    if ($name === '' || $email === '') {
        return '';
    }
    return hash_hmac('sha256', implode("\n", array('v1', $name, $email, $site)), wp_salt('auth'));
}

function dream2_mxin_comment_identity_cookie_name() {
    return DREAM2_MXIN_COMMENT_IDENTITY_COOKIE . COOKIEHASH;
}

function dream2_mxin_registered_comment_user($email) {
    $email = sanitize_email((string) $email);
    return $email ? get_user_by('email', $email) : false;
}

function dream2_mxin_comment_uses_registered_email_without_login($comment_data) {
    if (!empty($comment_data['user_id'])) {
        return false;
    }

    return (bool) dream2_mxin_registered_comment_user(
        $comment_data['comment_author_email'] ?? ''
    );
}

function dream2_mxin_redirect_registered_comment_email($comment_data) {
    if (!dream2_mxin_comment_uses_registered_email_without_login($comment_data)) {
        return $comment_data;
    }
    if (
        wp_doing_ajax()
        || (defined('REST_REQUEST') && REST_REQUEST)
        || wp_is_json_request()
    ) {
        return $comment_data;
    }

    $post_id = absint($comment_data['comment_post_ID'] ?? 0);
    $redirect = wp_get_referer();
    if (!$redirect && $post_id) {
        $redirect = get_permalink($post_id);
    }
    if (!$redirect) {
        return $comment_data;
    }

    $redirect = strtok($redirect, '#');
    $redirect = remove_query_arg('dream2_comment_error', $redirect);
    $redirect = add_query_arg(
        'dream2_comment_error',
        'registered_email',
        $redirect
    );
    wp_safe_redirect($redirect . '#respond', 303);
    exit;
}
add_filter('preprocess_comment', 'dream2_mxin_redirect_registered_comment_email', 5);

function dream2_mxin_protect_registered_comment_email($approved, $comment_data) {
    if (
        is_wp_error($approved)
        || !dream2_mxin_comment_uses_registered_email_without_login($comment_data)
    ) {
        return $approved;
    }

    return new WP_Error(
        'dream2_registered_comment_email',
        __('该邮箱已绑定账号，请登录后评论。', 'dream2-mxin'),
        403
    );
}
add_filter('pre_comment_approved', 'dream2_mxin_protect_registered_comment_email', 5, 2);

function dream2_mxin_registered_comment_email_notice() {
    $error = sanitize_key(wp_unslash($_GET['dream2_comment_error'] ?? ''));
    if ('registered_email' !== $error) {
        return '';
    }

    $redirect = remove_query_arg('dream2_comment_error');
    return sprintf(
        '<div class="dream-comment-notice dream-comment-notice-error" role="alert" data-dream-comment-identity-notice><i class="ri-error-warning-line" aria-hidden="true"></i><span>%1$s</span><a href="%2$s">%3$s</a></div>',
        esc_html__('该邮箱已绑定站内账号，请登录后再发表评论。', 'dream2-mxin'),
        esc_url(wp_login_url($redirect)),
        esc_html__('立即登录', 'dream2-mxin')
    );
}

function dream2_mxin_set_commenter_cookies($comment_id) {
    $comment = get_comment($comment_id);
    if (!$comment) {
        return;
    }

    $comment_cookie_lifetime = apply_filters('comment_cookie_lifetime', 30000000);
    $expires = time() + (int) $comment_cookie_lifetime;
    $path = defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/';
    $domain = defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '';
    $args = array(
        'expires'  => $expires,
        'path'     => $path,
        'secure'   => is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax',
    );
    if ($domain) {
        $args['domain'] = $domain;
    }

    $cookies = array(
        'comment_author_' . COOKIEHASH       => (string) $comment->comment_author,
        'comment_author_email_' . COOKIEHASH => (string) $comment->comment_author_email,
        'comment_author_url_' . COOKIEHASH   => esc_url_raw((string) $comment->comment_author_url),
    );

    foreach ($cookies as $name => $value) {
        setcookie($name, $value, $args);
        $_COOKIE[$name] = $value;
    }

    $identity_token = dream2_mxin_comment_identity_token(
        $comment->comment_author,
        $comment->comment_author_email,
        $comment->comment_author_url
    );
    if ($identity_token && !dream2_mxin_registered_comment_user($comment->comment_author_email)) {
        $identity_cookie = dream2_mxin_comment_identity_cookie_name();
        setcookie($identity_cookie, $identity_token, $args);
        $_COOKIE[$identity_cookie] = $identity_token;
    }
}

function dream2_mxin_force_commenter_cookies_after_core($comment) {
    dream2_mxin_set_commenter_cookies($comment);
}
add_action('set_comment_cookies', 'dream2_mxin_force_commenter_cookies_after_core', 20);

function dream2_mxin_private_comment_fields() {
    if (!dream2_enabled('enable_private_comment', true)) {
        return '';
    }
    return '<input class="dream-private-comment-input" id="private" name="private" type="checkbox" value="1"><button class="dream-private-comment-field" type="button" aria-pressed="false"><i class="ri-lock-line" aria-hidden="true"></i><span>'
        . esc_html__('私密评论', 'dream2-mxin') . '</span></button>';
}

function dream2_mxin_save_private_comment($comment_id) {
    if (dream2_enabled('enable_private_comment', true) && isset($_POST['private'])) {
        add_comment_meta($comment_id, DREAM2_MXIN_PRIVATE_META, '1', true);
        dream2_mxin_set_private_comment_cookie($comment_id);
    }
}
add_action('comment_post', 'dream2_mxin_save_private_comment');

function dream2_mxin_private_comment_token($comment_id, $email) {
    $email = dream2_mxin_normalize_private_comment_email($email);
    if (!$comment_id || !$email) {
        return '';
    }
    return hash_hmac('sha256', (int) $comment_id . '|' . $email, wp_salt('auth'));
}

function dream2_mxin_private_comment_cookie_name($comment_id) {
    return DREAM2_MXIN_PRIVATE_COOKIE_PREFIX . (int) $comment_id;
}

function dream2_mxin_set_private_comment_cookie($comment_id) {
    $comment = get_comment($comment_id);
    if (!$comment) {
        return;
    }

    $token = dream2_mxin_private_comment_token($comment->comment_ID, $comment->comment_author_email);
    if (!$token) {
        return;
    }

    $name = dream2_mxin_private_comment_cookie_name($comment->comment_ID);
    $path = defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/';
    $domain = defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '';
    $expires = time() + YEAR_IN_SECONDS;
    $args = array(
        'expires'  => $expires,
        'path'     => $path,
        'secure'   => is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax',
    );
    if ($domain) {
        $args['domain'] = $domain;
    }

    setcookie($name, $token, $args);
    $_COOKIE[$name] = $token;
}

function dream2_mxin_private_comment_cookie_matches($comment) {
    $comment = get_comment($comment);
    if (!$comment) {
        return false;
    }

    $name = dream2_mxin_private_comment_cookie_name($comment->comment_ID);
    $token = sanitize_text_field(wp_unslash($_COOKIE[$name] ?? ''));
    $expected = dream2_mxin_private_comment_token($comment->comment_ID, $comment->comment_author_email);
    return $token && $expected && hash_equals($expected, $token);
}

function dream2_mxin_comment_identity_cookie_matches($comment) {
    $comment = get_comment($comment);
    if (!$comment || dream2_mxin_registered_comment_user($comment->comment_author_email)) {
        return false;
    }

    $token = sanitize_text_field(wp_unslash($_COOKIE[dream2_mxin_comment_identity_cookie_name()] ?? ''));
    $expected = dream2_mxin_comment_identity_token(
        $comment->comment_author,
        $comment->comment_author_email,
        $comment->comment_author_url
    );
    return $token && $expected && hash_equals($expected, $token);
}

function dream2_mxin_private_comment_viewer_emails() {
    $emails = array();
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        if (!empty($user->user_email)) {
            $emails[] = $user->user_email;
        }
    }

    $emails = array_map('dream2_mxin_normalize_private_comment_email', $emails);
    return array_values(array_unique(array_filter($emails)));
}

function dream2_mxin_is_private_comment($comment) {
    $comment = get_comment($comment);
    return $comment && (bool) get_comment_meta($comment->comment_ID, DREAM2_MXIN_PRIVATE_META, true);
}

function dream2_mxin_can_view_private_comment($comment) {
    $comment = get_comment($comment);
    if (!$comment || !dream2_mxin_is_private_comment($comment)) {
        return true;
    }

    $viewer_emails = dream2_mxin_private_comment_viewer_emails();
    $author_email = dream2_mxin_normalize_private_comment_email($comment->comment_author_email);
    $parent_email = $comment->comment_parent
        ? dream2_mxin_normalize_private_comment_email(get_comment_author_email($comment->comment_parent))
        : '';
    $parent = $comment->comment_parent ? get_comment($comment->comment_parent) : null;

    return current_user_can('delete_user')
        || dream2_mxin_private_comment_cookie_matches($comment)
        || dream2_mxin_comment_identity_cookie_matches($comment)
        || ($parent && dream2_mxin_comment_identity_cookie_matches($parent))
        || ($author_email && in_array($author_email, $viewer_emails, true))
        || ($parent_email && in_array($parent_email, $viewer_emails, true));
}

function dream2_mxin_private_comment_placeholder() {
    return '<span class="dream-private-comment-hidden"><i class="ri-lock-line" aria-hidden="true"></i>'
        . esc_html__('此评论为私密评论。', 'dream2-mxin') . '</span>';
}

function dream2_mxin_protect_private_comment_text($text, $comment = null) {
    $comment = $comment ?: get_comment();
    if (!$comment || !dream2_mxin_is_private_comment($comment)) {
        return $text;
    }
    if (!dream2_mxin_can_view_private_comment($comment)) {
        return dream2_mxin_private_comment_placeholder();
    }
    return '<span class="dream-private-comment-notice">#私密#</span> ' . $text;
}
add_filter('get_comment_text', 'dream2_mxin_protect_private_comment_text', 10, 2);

function dream2_mxin_protect_private_comment_excerpt($excerpt, $comment_id) {
    $comment = get_comment($comment_id);
    if ($comment && dream2_mxin_is_private_comment($comment) && !dream2_mxin_can_view_private_comment($comment)) {
        return __('此评论为私密评论。', 'dream2-mxin');
    }
    return $excerpt;
}
add_filter('get_comment_excerpt', 'dream2_mxin_protect_private_comment_excerpt', 10, 2);

function dream2_mxin_protect_private_comment_rest($response, $comment) {
    if (dream2_mxin_is_private_comment($comment) && !dream2_mxin_can_view_private_comment($comment)) {
        $data = $response->get_data();
        $data['content']['rendered'] = dream2_mxin_private_comment_placeholder();
        $data['content']['raw'] = '';
        $response->set_data($data);
    }
    return $response;
}
add_filter('rest_prepare_comment', 'dream2_mxin_protect_private_comment_rest', 10, 2);

function dream2_mxin_private_comment_column($columns) {
    $columns['dream_private_comment'] = __('私密评论', 'dream2-mxin');
    return $columns;
}
add_filter('manage_edit-comments_columns', 'dream2_mxin_private_comment_column');

function dream2_mxin_render_private_comment_column($column, $comment_id) {
    if ('dream_private_comment' !== $column || !current_user_can('delete_user')) {
        return;
    }
    $is_private = (bool) get_comment_meta($comment_id, DREAM2_MXIN_PRIVATE_META, true);
    printf(
        '<button type="button" class="button-link dream-private-toggle" data-comment-id="%1$d" data-nonce="%2$s">%3$s</button>',
        (int) $comment_id,
        esc_attr(wp_create_nonce('dream2_private_comment_' . $comment_id)),
        esc_html($is_private ? __('私密', 'dream2-mxin') : __('公开', 'dream2-mxin'))
    );
}
add_action('manage_comments_custom_column', 'dream2_mxin_render_private_comment_column', 10, 2);

function dream2_mxin_toggle_private_comment() {
    $comment_id = absint($_POST['comment_id'] ?? 0);
    if (!$comment_id || !current_user_can('delete_user')) {
        wp_send_json_error(array('message' => __('没有权限。', 'dream2-mxin')), 403);
    }
    check_ajax_referer('dream2_private_comment_' . $comment_id, 'nonce');
    $is_private = (bool) get_comment_meta($comment_id, DREAM2_MXIN_PRIVATE_META, true);
    if ($is_private) {
        delete_comment_meta($comment_id, DREAM2_MXIN_PRIVATE_META);
    } else {
        update_comment_meta($comment_id, DREAM2_MXIN_PRIVATE_META, '1');
    }
    wp_send_json_success(array('private' => !$is_private, 'label' => !$is_private ? __('私密', 'dream2-mxin') : __('公开', 'dream2-mxin')));
}
add_action('wp_ajax_dream2_toggle_private_comment', 'dream2_mxin_toggle_private_comment');

function dream2_mxin_private_comment_admin_script() {
    $screen = get_current_screen();
    if (!$screen || 'edit-comments' !== $screen->id) {
        return;
    }
    ?>
    <script>
    document.addEventListener('click', function (event) {
        var button = event.target.closest('.dream-private-toggle');
        if (!button) return;
        event.preventDefault();
        button.disabled = true;
        var body = new URLSearchParams({action: 'dream2_toggle_private_comment', comment_id: button.dataset.commentId, nonce: button.dataset.nonce});
        fetch(ajaxurl, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: body.toString()})
            .then(function (response) { return response.json(); })
            .then(function (result) { if (result.success) button.textContent = result.data.label; })
            .finally(function () { button.disabled = false; });
    });
    </script>
    <?php
}
add_action('admin_footer-edit-comments.php', 'dream2_mxin_private_comment_admin_script');
