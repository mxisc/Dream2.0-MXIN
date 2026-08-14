<?php
/**
 * Temporary diagnostics for Dream2 mail delivery.
 *
 * @package Dream2_MXIN
 */

if (!defined('ABSPATH')) {
    exit;
}

const DREAM2_MXIN_MAIL_LOG_OPTION = 'dream2_mxin_mail_log';
const DREAM2_MXIN_MAIL_LOG_LOCK = 'dream2_mxin_mail_log_lock';
const DREAM2_MXIN_MAIL_LOG_LIMIT = 500;
const DREAM2_MXIN_MAIL_LOG_RETENTION_DAYS = 30;

function dream2_mxin_mail_log_recipients($recipients) {
    $emails = array();
    foreach ((array) $recipients as $recipient_group) {
        foreach (explode(',', (string) $recipient_group) as $recipient) {
            $recipient = trim($recipient);
            if (preg_match('/<([^>]+)>/', $recipient, $matches)) {
                $recipient = $matches[1];
            }
            $email = sanitize_email($recipient);
            if ($email && is_email($email)) {
                $emails[] = strtolower($email);
            }
        }
    }
    return array_values(array_unique($emails));
}

function dream2_mxin_mail_log_trim_text($value, $length = 240) {
    $value = sanitize_text_field(wp_strip_all_tags((string) $value));
    return wp_html_excerpt($value, $length, '...');
}

function dream2_mxin_mail_log_entries() {
    $entries = get_option(DREAM2_MXIN_MAIL_LOG_OPTION, array());
    if (!is_array($entries)) {
        return array();
    }
    $cutoff = time() - DREAM2_MXIN_MAIL_LOG_RETENTION_DAYS * DAY_IN_SECONDS;
    $allowed_statuses = array(
        'queued',
        'sent',
        'send_failed',
        'retry_scheduled',
        'final_failed',
        'schedule_failed',
    );
    return array_values(array_filter(
        $entries,
        static function ($entry) use ($cutoff, $allowed_statuses) {
            return is_array($entry)
                && absint($entry['timestamp'] ?? 0) >= $cutoff
                && in_array(($entry['status'] ?? ''), $allowed_statuses, true);
        }
    ));
}

function dream2_mxin_mail_log_acquire_lock() {
    $now = microtime(true);
    if (add_option(DREAM2_MXIN_MAIL_LOG_LOCK, $now, '', false)) {
        return true;
    }

    $locked_at = (float) get_option(DREAM2_MXIN_MAIL_LOG_LOCK, 0);
    if ($locked_at && $now - $locked_at > 5) {
        delete_option(DREAM2_MXIN_MAIL_LOG_LOCK);
        return add_option(DREAM2_MXIN_MAIL_LOG_LOCK, $now, '', false);
    }
    return false;
}

function dream2_mxin_mail_log_event($data) {
    if (!dream2_mxin_mail_log_acquire_lock()) {
        return false;
    }

    try {
        $allowed_statuses = array(
            'queued',
            'sent',
            'send_failed',
            'retry_scheduled',
            'final_failed',
            'schedule_failed',
        );
        $status = sanitize_key($data['status'] ?? '');
        if (!in_array($status, $allowed_statuses, true)) {
            return false;
        }

        $timestamp = time();
        $entry = array(
            'id'         => wp_generate_uuid4(),
            'timestamp'  => $timestamp,
            'created_at' => current_time('mysql'),
            'channel'    => in_array(($data['channel'] ?? ''), array('comment', 'link'), true)
                ? $data['channel']
                : 'other',
            'mail_type'  => sanitize_key($data['mail_type'] ?? ''),
            'status'     => $status,
            'source_id'  => absint($data['source_id'] ?? 0),
            'recipients' => dream2_mxin_mail_log_recipients($data['recipients'] ?? array()),
            'subject'    => dream2_mxin_mail_log_trim_text($data['subject'] ?? ''),
            'attempt'    => max(0, absint($data['attempt'] ?? 0)),
            'detail'     => dream2_mxin_mail_log_trim_text($data['detail'] ?? ''),
        );

        $cutoff = $timestamp - DREAM2_MXIN_MAIL_LOG_RETENTION_DAYS * DAY_IN_SECONDS;
        $entries = array_values(array_filter(
            dream2_mxin_mail_log_entries(),
            static function ($item) use ($cutoff) {
                return absint($item['timestamp'] ?? 0) >= $cutoff;
            }
        ));
        array_unshift($entries, $entry);
        $entries = array_slice($entries, 0, DREAM2_MXIN_MAIL_LOG_LIMIT);

        if (null === get_option(DREAM2_MXIN_MAIL_LOG_OPTION, null)) {
            return add_option(DREAM2_MXIN_MAIL_LOG_OPTION, $entries, '', false);
        }
        return update_option(DREAM2_MXIN_MAIL_LOG_OPTION, $entries, false);
    } finally {
        delete_option(DREAM2_MXIN_MAIL_LOG_LOCK);
    }
}

function dream2_mxin_capture_mail_error($error) {
    if (empty($GLOBALS['dream2_mxin_mail_log_active']) || !is_wp_error($error)) {
        return;
    }
    $GLOBALS['dream2_mxin_mail_log_error'] = $error->get_error_message();
}
add_action('wp_mail_failed', 'dream2_mxin_capture_mail_error');

function dream2_mxin_send_logged_mail(
    $recipients,
    $subject,
    $message,
    $headers = array(),
    $context = array()
) {
    $GLOBALS['dream2_mxin_mail_log_active'] = true;
    $GLOBALS['dream2_mxin_mail_log_error'] = '';
    try {
        $sent = wp_mail($recipients, $subject, $message, $headers);
    } catch (Throwable $error) {
        $sent = false;
        $GLOBALS['dream2_mxin_mail_log_error'] = $error->getMessage();
    } finally {
        $GLOBALS['dream2_mxin_mail_log_active'] = false;
    }

    $error_message = (string) ($GLOBALS['dream2_mxin_mail_log_error'] ?? '');
    unset($GLOBALS['dream2_mxin_mail_log_error']);
    dream2_mxin_mail_log_event(array_merge($context, array(
        'status'     => $sent ? 'sent' : 'send_failed',
        'recipients' => $recipients,
        'subject'    => $subject,
        'detail'     => $sent
            ? 'wp_mail() 返回成功'
            : ($error_message ?: 'wp_mail() 返回失败'),
    )));

    return (bool) $sent;
}

function dream2_mxin_mail_log_page_url($args = array()) {
    return add_query_arg($args, admin_url('admin.php?page=dream2-mail-log'));
}

function dream2_mxin_mail_log_type_label($channel, $mail_type) {
    $labels = array(
        'comment' => array(
            'post_author' => '文章作者通知',
            'moderator'   => '评论审核通知',
            'reply'       => '评论回复通知',
        ),
        'link' => array(
            'recovered' => '友链恢复通知',
            'one_way'   => '单向友链通知',
            'abnormal'  => '异常博客通知',
            'lost'      => '失联博客通知',
        ),
    );
    return $labels[$channel][$mail_type] ?? ($mail_type ?: '其他通知');
}

function dream2_mxin_mail_log_status_label($status) {
    $labels = array(
        'queued'          => '已排队',
        'sent'            => '发送成功',
        'send_failed'     => '发送失败',
        'retry_scheduled' => '等待重试',
        'final_failed'    => '最终失败',
        'schedule_failed' => '排队失败',
    );
    return $labels[$status] ?? $status;
}

function dream2_mxin_mail_log_source_link($entry) {
    $source_id = absint($entry['source_id'] ?? 0);
    if (!$source_id) {
        return '';
    }
    if (($entry['channel'] ?? '') === 'comment') {
        return get_edit_comment_link($source_id);
    }
    if (($entry['channel'] ?? '') === 'link' && function_exists('dream2_mxin_link_manager_url')) {
        return dream2_mxin_link_manager_url();
    }
    return '';
}

function dream2_mxin_render_mail_log_page() {
    if (!current_user_can('edit_theme_options')) {
        return;
    }

    $entries = dream2_mxin_mail_log_entries();
    $status_filter = isset($_GET['status'])
        ? sanitize_key(wp_unslash($_GET['status']))
        : '';
    $channel_filter = isset($_GET['channel'])
        ? sanitize_key(wp_unslash($_GET['channel']))
        : '';
    $filtered_entries = array_values(array_filter(
        $entries,
        static function ($entry) use ($status_filter, $channel_filter) {
            if ($status_filter && ($entry['status'] ?? '') !== $status_filter) {
                return false;
            }
            return !$channel_filter || ($entry['channel'] ?? '') === $channel_filter;
        }
    ));

    $per_page = 50;
    $page_number = max(1, absint($_GET['paged'] ?? 1));
    $page_entries = array_slice($filtered_entries, ($page_number - 1) * $per_page, $per_page);
    $counts = array(
        'sent'    => 0,
        'pending' => 0,
        'failed'  => 0,
    );
    foreach ($entries as $entry) {
        $status = $entry['status'] ?? '';
        if ($status === 'sent') {
            $counts['sent']++;
        } elseif (in_array($status, array('queued', 'retry_scheduled'), true)) {
            $counts['pending']++;
        } elseif (in_array($status, array('send_failed', 'final_failed', 'schedule_failed'), true)) {
            $counts['failed']++;
        }
    }
    ?>
    <div class="wrap dream2-settings-wrap dream2-mail-log">
        <header class="dream2-options-header">
            <h1>邮件记录</h1>
            <span class="dream2-version">最近 30 天 / 最多 500 条</span>
        </header>
        <div class="dream2-mail-log-content">
            <?php if (isset($_GET['cleared'])) : ?>
                <div class="notice notice-success is-dismissible"><p>邮件记录已清空。</p></div>
            <?php endif; ?>
            <div class="dream2-mail-log-metrics">
                <div><strong><?php echo esc_html((string) count($entries)); ?></strong><span>总记录</span></div>
                <div class="is-success"><strong><?php echo esc_html((string) $counts['sent']); ?></strong><span>发送成功</span></div>
                <div class="is-warning"><strong><?php echo esc_html((string) $counts['pending']); ?></strong><span>排队 / 重试</span></div>
                <div class="is-danger"><strong><?php echo esc_html((string) $counts['failed']); ?></strong><span>失败事件</span></div>
            </div>
            <div class="dream2-mail-log-toolbar">
                <form method="get">
                    <input type="hidden" name="page" value="dream2-mail-log">
                    <select name="channel">
                        <option value="">全部来源</option>
                        <option value="comment" <?php selected($channel_filter, 'comment'); ?>>评论通知</option>
                        <option value="link" <?php selected($channel_filter, 'link'); ?>>友链通知</option>
                    </select>
                    <select name="status">
                        <option value="">全部状态</option>
                        <?php foreach (array('queued', 'sent', 'send_failed', 'retry_scheduled', 'final_failed', 'schedule_failed') as $status) : ?>
                            <option value="<?php echo esc_attr($status); ?>" <?php selected($status_filter, $status); ?>>
                                <?php echo esc_html(dream2_mxin_mail_log_status_label($status)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="button">筛选</button>
                    <a class="button" href="<?php echo esc_url(dream2_mxin_mail_log_page_url()); ?>">刷新</a>
                </form>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('确认清空全部邮件记录？');">
                    <input type="hidden" name="action" value="dream2_mxin_clear_mail_log">
                    <?php wp_nonce_field('dream2_mxin_clear_mail_log'); ?>
                    <button type="submit" class="button button-secondary">清空记录</button>
                </form>
            </div>
            <div class="dream2-mail-log-table">
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>时间</th>
                            <th>类型</th>
                            <th>状态</th>
                            <th>收件人</th>
                            <th>主题 / 详情</th>
                            <th>来源</th>
                            <th>尝试</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$page_entries) : ?>
                        <tr><td colspan="7">暂无匹配的邮件记录。</td></tr>
                    <?php else : ?>
                        <?php foreach ($page_entries as $entry) : ?>
                            <?php
                            $source_link = dream2_mxin_mail_log_source_link($entry);
                            $source_id = absint($entry['source_id'] ?? 0);
                            $source_label = ($entry['channel'] ?? '') === 'comment' ? '评论' : '友链';
                            $recipient_text = implode(', ', (array) ($entry['recipients'] ?? array()));
                            $subject_text = (string) ($entry['subject'] ?? '');
                            $attempt = absint($entry['attempt'] ?? 0);
                            ?>
                            <tr>
                                <td><?php echo esc_html((string) ($entry['created_at'] ?? '')); ?></td>
                                <td><?php echo esc_html(dream2_mxin_mail_log_type_label($entry['channel'] ?? '', $entry['mail_type'] ?? '')); ?></td>
                                <td><span class="dream2-mail-status is-<?php echo esc_attr((string) ($entry['status'] ?? '')); ?>"><?php echo esc_html(dream2_mxin_mail_log_status_label($entry['status'] ?? '')); ?></span></td>
                                <td><?php echo esc_html($recipient_text ?: '—'); ?></td>
                                <td>
                                    <strong><?php echo esc_html($subject_text ?: '—'); ?></strong>
                                    <?php if (!empty($entry['detail'])) : ?><small><?php echo esc_html((string) $entry['detail']); ?></small><?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($source_link) : ?><a href="<?php echo esc_url($source_link); ?>"><?php echo esc_html($source_label . ' #' . $source_id); ?></a>
                                    <?php else : ?><?php echo esc_html($source_id ? $source_label . ' #' . $source_id : '—'); ?><?php endif; ?>
                                </td>
                                <td><?php echo esc_html($attempt ? (string) $attempt : '—'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php
            $total_pages = (int) ceil(count($filtered_entries) / $per_page);
            if ($total_pages > 1) {
                echo '<div class="tablenav"><div class="tablenav-pages">';
                echo wp_kses_post(paginate_links(array(
                    'base'      => add_query_arg('paged', '%#%', dream2_mxin_mail_log_page_url(array_filter(array(
                        'channel' => $channel_filter,
                        'status'  => $status_filter,
                    )))),
                    'format'    => '',
                    'current'   => $page_number,
                    'total'     => $total_pages,
                    'prev_text' => '上一页',
                    'next_text' => '下一页',
                )));
                echo '</div></div>';
            }
            ?>
        </div>
    </div>
    <?php
}

function dream2_mxin_clear_mail_log() {
    if (!current_user_can('edit_theme_options')) {
        wp_die(esc_html__('权限不足。', 'dream2-mxin'));
    }
    check_admin_referer('dream2_mxin_clear_mail_log');
    delete_option(DREAM2_MXIN_MAIL_LOG_OPTION);
    delete_option(DREAM2_MXIN_MAIL_LOG_LOCK);
    wp_safe_redirect(dream2_mxin_mail_log_page_url(array('cleared' => 1)));
    exit;
}
add_action('admin_post_dream2_mxin_clear_mail_log', 'dream2_mxin_clear_mail_log');

function dream2_mxin_register_mail_log_page() {
    $hook = add_submenu_page(
        'dream2-settings',
        '梦屿邮件记录',
        '邮件记录',
        'edit_theme_options',
        'dream2-mail-log',
        'dream2_mxin_render_mail_log_page'
    );
    $GLOBALS['dream2_mxin_mail_log_hook'] = $hook;
    if ($hook) {
        $GLOBALS['dream2_mxin_settings_hooks'][] = $hook;
    }
}
add_action('admin_menu', 'dream2_mxin_register_mail_log_page', 20);
