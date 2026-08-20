<?php
/**
 * Frontend friend-link application form.
 *
 * @package Dream2_MXIN
 */

if (!defined('ABSPATH')) {
    exit;
}

const DREAM2_MXIN_LINK_APPLICATION_META = 'dream2_link_application';
const DREAM2_MXIN_LINK_APPLICATION_BACKLINK_META = 'dream2_link_application_backlink_url';
const DREAM2_MXIN_LINK_APPLICATION_MATCHED_META = 'dream2_link_application_matched_url';
const DREAM2_MXIN_LINK_APPLICATION_AVATAR_META = 'dream2_link_application_avatar_url';
const DREAM2_MXIN_LINK_APPLICATION_LINK_ID_META = 'dream2_link_application_link_id';

function dream2_mxin_is_link_application_post($post_id) {
    return $post_id && 'page-templates/links.php' === get_page_template_slug($post_id);
}

function dream2_mxin_link_application_enabled($post_id) {
    return dream2_mxin_is_link_application_post($post_id)
        && comments_open($post_id)
        && dream2_enabled('enable_link_application', true);
}

function dream2_mxin_link_application_clean_url($value, $required = true) {
    $value = trim((string) $value);
    if ($value === '') {
        return $required ? '' : '';
    }
    if (!preg_match('#^https?://#i', $value)) {
        $value = 'https://' . ltrim($value, '/');
    }
    $url = esc_url_raw($value);
    if (!$url || !wp_http_validate_url($url)) {
        return '';
    }
    return $url;
}

function dream2_mxin_link_application_status_message($status) {
    $messages = array(
        'success'              => array('type' => 'success', 'text' => '友链申请投递成功，传送门已经开好啦。'),
        'missing'              => array('type' => 'error', 'text' => '情报还没填满哦，名称、地址、邮箱和反链页都要给我。'),
        'invalid_nonce'        => array('type' => 'error', 'text' => '这个提交令牌过期啦，刷新页面再来一局。'),
        'closed'               => array('type' => 'error', 'text' => '当前友链申请通道还没开放，先等等。'),
        'invalid_site_url'     => array('type' => 'error', 'text' => '你的网站地址不像能访问的传送门，换个完整地址试试。'),
        'invalid_backlink_url' => array('type' => 'error', 'text' => '反链检测页进不去副本，换个可访问的完整地址吧。'),
        'backlink_host_mismatch' => array('type' => 'error', 'text' => '反链页看起来不是你家地盘，先去评论区手动丢申请吧。'),
        'invalid_avatar'       => array('type' => 'error', 'text' => '头像地址格式不太对，可以留空，别硬上。'),
        'invalid_email'        => array('type' => 'error', 'text' => '邮箱格式不对，通讯频道连不上。'),
        'backlink_unreachable' => array('type' => 'error', 'text' => '反链页暂时进不去，确认能访问后再来。'),
        'backlink_missing'     => array('type' => 'error', 'text' => '反链页找到了，但没看到本站链接。先把传送门放上去吧。'),
        'registered_email'     => array('type' => 'error', 'text' => '这个邮箱已经绑定站内账号啦，先登录再操作。'),
        'rate_limited'         => array('type' => 'error', 'text' => '检测请求太频繁，十分钟后再来一局。'),
        'bot'                  => array('type' => 'error', 'text' => '这次提交没有通过校验，请刷新页面重试。'),
        'failed'               => array('type' => 'error', 'text' => '申请投递失败，系统卡了一下，稍后再试。'),
    );
    return $messages[$status] ?? array();
}

function dream2_mxin_link_application_friend_category_id() {
    $category_id = absint(dream2_get('link_friend_category', dream2_mxin_default_link_category_id('友情链接')));
    $term = $category_id ? get_term($category_id, 'link_category') : null;
    if ($term && !is_wp_error($term)) {
        return $category_id;
    }
    return absint(dream2_mxin_default_link_category_id('友情链接'));
}

function dream2_mxin_link_application_existing_link_id($site_url) {
    global $wpdb;
    $site_url = esc_url_raw($site_url);
    $without_slash = untrailingslashit($site_url);
    $with_slash = trailingslashit($without_slash);
    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT link_id FROM {$wpdb->links} WHERE link_url IN (%s, %s, %s) ORDER BY link_id ASC LIMIT 1",
        $site_url,
        $without_slash,
        $with_slash
    ));
}

function dream2_mxin_link_application_upsert_link($site_name, $site_url, $avatar_url, $email, $backlink_url, $matched_url, $description, $status_code) {
    if (!function_exists('wp_insert_link')) {
        require_once ABSPATH . 'wp-admin/includes/bookmark.php';
    }

    $checked_at = current_time('mysql');
    $verification = array(
        'access'             => 'success',
        'backlink'           => 'success',
        'status'             => 'success',
        'message'            => '站点正常，已找到反链',
        'checked_at'         => $checked_at,
        'matched_url'        => $matched_url,
        'status_code'        => absint($status_code),
        'consecutive_failures' => 0,
        'first_failed_at'    => '',
        'last_success_at'    => $checked_at,
        'confirmed_failure'  => false,
        'moved_at'           => '',
        'access_failures'    => 0,
        'access_first_failed_at' => '',
        'backlink_failures'  => 0,
        'backlink_first_failed_at' => '',
        'one_way_notified_at' => '',
        'abnormal_notified_at' => '',
        'lost_notified_at'   => '',
    );
    $link_id = dream2_mxin_link_application_existing_link_id($site_url);
    $linkdata = array(
        'link_name'        => $site_name,
        'link_url'         => $site_url,
        'link_description' => $description,
        'link_image'       => $avatar_url,
        'link_target'      => '_blank',
        'link_visible'     => 'Y',
        'link_notes'       => dream2_mxin_link_notes($email, $backlink_url, $verification),
    );
    $category_id = dream2_mxin_link_application_friend_category_id();
    if ($category_id) {
        $linkdata['link_category'] = array($category_id);
    }

    if ($link_id) {
        $linkdata['link_id'] = $link_id;
        return wp_update_link($linkdata);
    }
    return wp_insert_link($linkdata, true);
}

function dream2_mxin_link_application_url_host($url) {
    $host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
    $host = rtrim($host, '.');
    return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
}

function dream2_mxin_link_application_hosts_match($site_url, $backlink_url) {
    $site_host = dream2_mxin_link_application_url_host($site_url);
    $backlink_host = dream2_mxin_link_application_url_host($backlink_url);
    return $site_host !== '' && $backlink_host !== '' && hash_equals($site_host, $backlink_host);
}

function dream2_mxin_link_application_is_ajax_request() {
    return !empty($_POST['dream2_link_application_ajax'])
        || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
}

function dream2_mxin_link_application_notice() {
    $status = sanitize_key(wp_unslash($_GET['dream2_link_apply'] ?? ''));
    $message = $status ? dream2_mxin_link_application_status_message($status) : array();
    if (!$message) {
        return '';
    }
    return sprintf(
        '<div class="dream-link-application-notice is-%1$s" role="alert"><i class="%2$s" aria-hidden="true"></i><span>%3$s</span></div>',
        esc_attr($message['type']),
        esc_attr($message['type'] === 'success' ? 'ri-checkbox-circle-line' : 'ri-error-warning-line'),
        esc_html($message['text'])
    );
}

function dream2_mxin_link_application_comment_content($site_name, $site_url, $avatar_url, $description) {
    $lines = array(
        '友链申请',
        '名称：' . $site_name,
        '地址：' . $site_url,
    );
    if ($avatar_url !== '') {
        $lines[] = '图标：' . $avatar_url;
    }
    if ($description !== '') {
        $lines[] = '描述：' . $description;
    }
    return implode("\n", $lines);
}

function dream2_mxin_link_application_redirect($post_id, $status, $comment_id = 0) {
    if (dream2_mxin_link_application_is_ajax_request()) {
        $message = dream2_mxin_link_application_status_message($status);
        if (!$message) {
            $message = dream2_mxin_link_application_status_message('failed');
        }
        $data = array(
            'code'    => sanitize_key($status),
            'type'    => $message['type'],
            'message' => $message['text'],
        );
        if ($comment_id) {
            $comment = get_comment($comment_id);
            $data['comment_id'] = (int) $comment_id;
            $data['approved'] = $comment && (string) $comment->comment_approved === '1';
            $data['refresh_url'] = get_permalink($post_id);
        }
        if ($message['type'] === 'success') {
            wp_send_json_success($data);
        }
        $http_status = in_array($status, array('closed', 'invalid_nonce'), true) ? 403 : 400;
        if ($status === 'rate_limited') {
            $http_status = 429;
        }
        wp_send_json_error($data, $http_status);
    }

    $redirect = wp_get_referer();
    if (!$redirect && $post_id) {
        $redirect = get_permalink($post_id);
    }
    $redirect = wp_validate_redirect($redirect, home_url('/'));
    $redirect = strtok($redirect, '#');
    $redirect = remove_query_arg(array('dream2_link_apply', 'dream2_link_comment'), $redirect);
    $redirect = add_query_arg('dream2_link_apply', sanitize_key($status), $redirect);
    if ($comment_id) {
        $redirect = add_query_arg('dream2_link_comment', (int) $comment_id, $redirect);
    }
    wp_safe_redirect($redirect . ($comment_id ? '#comment-' . (int) $comment_id : '#dream2-link-application'), 303);
    exit;
}

function dream2_mxin_link_application_rate_limit($scope, $value, $limit, $ttl = 600) {
    $key = 'dream2_link_rate_' . sanitize_key($scope) . '_' . hash_hmac('sha256', (string) $value, wp_salt('nonce'));
    $attempts = (int) get_transient($key);
    if ($attempts >= $limit) {
        return false;
    }
    set_transient($key, $attempts + 1, $ttl);
    return true;
}

function dream2_mxin_handle_link_application() {
    $post_id = absint($_POST['comment_post_ID'] ?? 0);
    if (!dream2_mxin_link_application_enabled($post_id)) {
        dream2_mxin_link_application_redirect($post_id, 'closed');
    }
    if (
        empty($_POST['dream2_link_application_nonce'])
        || !wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['dream2_link_application_nonce'])),
            'dream2_link_application_' . $post_id
        )
    ) {
        dream2_mxin_link_application_redirect($post_id, 'invalid_nonce');
    }
    if (trim((string) wp_unslash($_POST['dream2_link_company'] ?? '')) !== '') {
        dream2_mxin_link_application_redirect($post_id, 'bot');
    }

    $site_url_raw = trim((string) wp_unslash($_POST['dream2_link_site_url'] ?? ''));
    $avatar_url_raw = trim((string) wp_unslash($_POST['dream2_link_site_avatar'] ?? ''));
    $email_raw = trim((string) wp_unslash($_POST['dream2_link_contact_email'] ?? ''));
    $backlink_url_raw = trim((string) wp_unslash($_POST['dream2_link_backlink_url'] ?? ''));
    $site_name = sanitize_text_field(wp_unslash($_POST['dream2_link_site_name'] ?? ''));
    $site_url = dream2_mxin_link_application_clean_url($site_url_raw);
    $avatar_url = dream2_mxin_link_application_clean_url($avatar_url_raw, false);
    $email = sanitize_email($email_raw);
    $backlink_url = dream2_mxin_link_application_clean_url($backlink_url_raw);
    $description = sanitize_textarea_field(wp_unslash($_POST['dream2_link_description'] ?? ''));

    if ($site_name === '' || $site_url_raw === '' || $email_raw === '' || $backlink_url_raw === '') {
        dream2_mxin_link_application_redirect($post_id, 'missing');
    }
    if ($site_url === '') {
        dream2_mxin_link_application_redirect($post_id, 'invalid_site_url');
    }
    if ($backlink_url === '') {
        dream2_mxin_link_application_redirect($post_id, 'invalid_backlink_url');
    }
    if ($avatar_url_raw !== '' && $avatar_url === '') {
        dream2_mxin_link_application_redirect($post_id, 'invalid_avatar');
    }
    if (!dream2_mxin_link_application_hosts_match($site_url, $backlink_url)) {
        dream2_mxin_link_application_redirect($post_id, 'backlink_host_mismatch');
    }
    if (!is_email($email)) {
        dream2_mxin_link_application_redirect($post_id, 'invalid_email');
    }
    if (!is_user_logged_in() && function_exists('dream2_mxin_registered_comment_user') && dream2_mxin_registered_comment_user($email)) {
        dream2_mxin_link_application_redirect($post_id, 'registered_email');
    }

    $remote_address = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $backlink_host = dream2_mxin_link_application_url_host($backlink_url);
    if (
        !dream2_mxin_link_application_rate_limit('ip', $remote_address, 5)
        || !dream2_mxin_link_application_rate_limit('host', $backlink_host, 3)
    ) {
        dream2_mxin_link_application_redirect($post_id, 'rate_limited');
    }

    $backlink = dream2_mxin_link_http_check($backlink_url, 256 * 1024);
    if (!$backlink['ok']) {
        dream2_mxin_link_application_redirect($post_id, 'backlink_unreachable');
    }
    $target_url = dream2_get('links_blogger_url', home_url('/')) ?: home_url('/');
    $matched_url = dream2_mxin_find_backlink($backlink['body'], $target_url, $backlink_url);
    if (!$matched_url) {
        dream2_mxin_link_application_redirect($post_id, 'backlink_missing');
    }

    $link_id = dream2_mxin_link_application_upsert_link(
        $site_name,
        $site_url,
        $avatar_url,
        $email,
        $backlink_url,
        $matched_url,
        $description,
        $backlink['code']
    );
    if (is_wp_error($link_id) || !$link_id) {
        dream2_mxin_link_application_redirect($post_id, 'failed');
    }

    $content = dream2_mxin_link_application_comment_content($site_name, $site_url, $avatar_url, $description);
    $comment_id = wp_new_comment(wp_slash(array(
        'comment_post_ID'      => $post_id,
        'comment_author'       => $site_name,
        'comment_author_email' => $email,
        'comment_author_url'   => $site_url,
        'comment_content'      => $content,
        'comment_type'         => 'comment',
        'comment_parent'       => 0,
        'user_id'              => get_current_user_id(),
    )), true);

    if (is_wp_error($comment_id)) {
        dream2_mxin_link_application_redirect($post_id, 'success');
    }

    update_comment_meta($comment_id, DREAM2_MXIN_LINK_APPLICATION_META, '1');
    update_comment_meta($comment_id, DREAM2_MXIN_LINK_APPLICATION_BACKLINK_META, $backlink_url);
    update_comment_meta($comment_id, DREAM2_MXIN_LINK_APPLICATION_MATCHED_META, $matched_url);
    update_comment_meta($comment_id, DREAM2_MXIN_LINK_APPLICATION_LINK_ID_META, (int) $link_id);
    if ($avatar_url !== '') {
        update_comment_meta($comment_id, DREAM2_MXIN_LINK_APPLICATION_AVATAR_META, $avatar_url);
    }
    if (function_exists('dream2_mxin_set_commenter_cookies')) {
        dream2_mxin_set_commenter_cookies($comment_id);
    }

    dream2_mxin_link_application_redirect($post_id, 'success', $comment_id);
}
add_action('admin_post_dream2_submit_link_application', 'dream2_mxin_handle_link_application');
add_action('admin_post_nopriv_dream2_submit_link_application', 'dream2_mxin_handle_link_application');
add_action('wp_ajax_dream2_submit_link_application', 'dream2_mxin_handle_link_application');
add_action('wp_ajax_nopriv_dream2_submit_link_application', 'dream2_mxin_handle_link_application');

function dream2_mxin_render_link_application_form($post_id) {
    if (!dream2_mxin_link_application_enabled($post_id)) {
        return;
    }
    ?>
    <section id="dream2-link-application" class="dream-link-application">
        <h2><i class="ri-links-line" aria-hidden="true"></i><?php esc_html_e('自助申请友链', 'dream2-mxin'); ?></h2>
        <p class="dream-link-application-help"><?php esc_html_e('提交前请先在你的站点放好本站链接。填写贵站友链页面的URL可以自动审核。', 'dream2-mxin'); ?></p>
        <?php echo dream2_mxin_link_application_notice(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <form class="dream-link-application-form" method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
            <input type="hidden" name="action" value="dream2_submit_link_application">
            <input type="hidden" name="comment_post_ID" value="<?php echo esc_attr((string) $post_id); ?>">
            <?php wp_nonce_field('dream2_link_application_' . $post_id, 'dream2_link_application_nonce'); ?>
            <label aria-hidden="true" style="position:absolute;left:-10000px;width:1px;height:1px;overflow:hidden;">公司<input name="dream2_link_company" type="text" tabindex="-1" autocomplete="off"></label>
            <label><?php esc_html_e('网站名称', 'dream2-mxin'); ?><input name="dream2_link_site_name" type="text" maxlength="80" required></label>
            <label><?php esc_html_e('网站地址', 'dream2-mxin'); ?><input name="dream2_link_site_url" type="url" placeholder="https://example.com/" required></label>
            <label><?php esc_html_e('联系邮箱', 'dream2-mxin'); ?><input name="dream2_link_contact_email" type="email" required></label>
            <label><?php esc_html_e('反链检测页', 'dream2-mxin'); ?><input name="dream2_link_backlink_url" type="url" placeholder="已放置本站链接的页面" required></label>
            <label><?php esc_html_e('网站图标', 'dream2-mxin'); ?><input name="dream2_link_site_avatar" type="url" placeholder="https://example.com/avatar.png"></label>
            <label class="dream-link-application-wide"><?php esc_html_e('网站介绍', 'dream2-mxin'); ?><textarea name="dream2_link_description" rows="3" maxlength="220"></textarea></label>
            <p class="dream-link-application-actions"><button class="dream-submit" type="submit"><i class="ri-send-plane-line" aria-hidden="true"></i><?php esc_html_e('提交申请', 'dream2-mxin'); ?></button></p>
        </form>
    </section>
    <?php
}

function dream2_mxin_link_application_comment_columns($columns) {
    $columns['dream_link_application'] = __('友链申请', 'dream2-mxin');
    return $columns;
}
add_filter('manage_edit-comments_columns', 'dream2_mxin_link_application_comment_columns');

function dream2_mxin_render_link_application_comment_column($column, $comment_id) {
    if ($column !== 'dream_link_application' || !get_comment_meta($comment_id, DREAM2_MXIN_LINK_APPLICATION_META, true)) {
        return;
    }
    $backlink_url = get_comment_meta($comment_id, DREAM2_MXIN_LINK_APPLICATION_BACKLINK_META, true);
    $matched_url = get_comment_meta($comment_id, DREAM2_MXIN_LINK_APPLICATION_MATCHED_META, true);
    echo '<strong>' . esc_html__('友链申请', 'dream2-mxin') . '</strong>';
    if ($backlink_url) {
        echo '<br><a href="' . esc_url($backlink_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('反链页', 'dream2-mxin') . '</a>';
    }
    if ($matched_url) {
        echo '<br><span>' . esc_html__('已找到本站链接', 'dream2-mxin') . '</span>';
    }
}
add_action('manage_comments_custom_column', 'dream2_mxin_render_link_application_comment_column', 10, 2);
