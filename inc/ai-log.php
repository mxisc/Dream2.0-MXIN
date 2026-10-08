<?php
/** AI request records. */
if (!defined('ABSPATH')) { exit; }

const DREAM2_MXIN_AI_LOG_OPTION = 'dream2_mxin_ai_log';
const DREAM2_MXIN_AI_LOG_LOCK = 'dream2_mxin_ai_log_lock';

function dream2_mxin_ai_log_entries() {
    $entries = get_option(DREAM2_MXIN_AI_LOG_OPTION, array());
    if (!is_array($entries)) { return array(); }
    $cutoff = time() - 30 * DAY_IN_SECONDS;
    return array_values(array_filter($entries, static function ($entry) use ($cutoff) {
        return is_array($entry) && absint($entry['timestamp'] ?? 0) >= $cutoff;
    }));
}

function dream2_mxin_ai_log_detail($value) {
    $value = sanitize_text_field(wp_strip_all_tags((string) $value));
    $value = preg_replace('~https?://\S+~i', '[URL]', $value);
    $value = preg_replace('/\b(?:sk-[A-Za-z0-9_-]{8,}|Bearer\s+\S+)\b/i', '[REDACTED]', $value);
    $value = preg_replace('/\b[A-Za-z0-9_-]{40,}\b/', '[REDACTED]', $value);
    return wp_html_excerpt($value, 400, '…');
}

function dream2_mxin_ai_log_upstream_detail($payload) {
    if (!is_array($payload) || !isset($payload['error']) || !is_array($payload['error'])) { return ''; }
    $error = $payload['error'];
    $parts = array();
    foreach (array('type', 'code', 'message') as $field) {
        if (isset($error[$field]) && is_scalar($error[$field])) {
            $parts[] = $field . ': ' . (string) $error[$field];
        }
    }
    return dream2_mxin_ai_log_detail(implode('; ', $parts));
}

function dream2_mxin_ai_log_event($feature, $configuration, $outcome, $http_status, $started, $reason = '', $detail = '', $context = array()) {
    foreach (array('api_key', 'system_prompt', 'search_prompt', 'summary_prompt') as $sensitive_field) {
        $sensitive_value = (string) ($configuration[$sensitive_field] ?? '');
        if ($sensitive_value !== '') { $detail = str_replace($sensitive_value, '[REDACTED]', (string) $detail); }
    }
    $now = microtime(true);
    if (!add_option(DREAM2_MXIN_AI_LOG_LOCK, $now, '', false)) {
        $locked_at = (float) get_option(DREAM2_MXIN_AI_LOG_LOCK, 0);
        if ($locked_at && $now - $locked_at > 5) {
            delete_option(DREAM2_MXIN_AI_LOG_LOCK);
        }
        if (!add_option(DREAM2_MXIN_AI_LOG_LOCK, $now, '', false)) { return false; }
    }
    try {
        $entries = dream2_mxin_ai_log_entries();
        array_unshift($entries, array(
            'timestamp' => time(),
            'created_at' => current_time('mysql'),
            'feature' => in_array($feature, array('search', 'summary', 'summary_stream'), true) ? $feature : 'other',
            'model' => sanitize_text_field((string) ($configuration['model'] ?? '')),
            'outcome' => $outcome === 'success' ? 'success' : 'failed',
            'http_status' => absint($http_status),
            'duration_ms' => max(0, (int) round((microtime(true) - $started) * 1000)),
            'reason' => sanitize_key($reason),
            'detail' => dream2_mxin_ai_log_detail($detail),
            'request_id' => sanitize_key($context['request_id'] ?? ''),
            'post_id' => absint($context['post_id'] ?? 0),
        ));
        $entries = array_slice($entries, 0, 500);
        if (null === get_option(DREAM2_MXIN_AI_LOG_OPTION, null)) {
            return add_option(DREAM2_MXIN_AI_LOG_OPTION, $entries, '', false);
        }
        return update_option(DREAM2_MXIN_AI_LOG_OPTION, $entries, false);
    } finally {
        delete_option(DREAM2_MXIN_AI_LOG_LOCK);
    }
}

function dream2_mxin_ai_log_url($args = array()) {
    return add_query_arg($args, admin_url('admin.php?page=dream2-ai-log'));
}

function dream2_mxin_render_ai_log_page() {
    if (!current_user_can('edit_theme_options')) { return; }
    $entries = dream2_mxin_ai_log_entries();
    $feature = isset($_GET['feature']) ? sanitize_key(wp_unslash($_GET['feature'])) : '';
    $outcome = isset($_GET['outcome']) ? sanitize_key(wp_unslash($_GET['outcome'])) : '';
    $filtered = array_values(array_filter($entries, static function ($entry) use ($feature, $outcome) {
        return (!$feature || $entry['feature'] === $feature) && (!$outcome || $entry['outcome'] === $outcome);
    }));
    $page = max(1, absint($_GET['paged'] ?? 1));
    $rows = array_slice($filtered, ($page - 1) * 50, 50);
    $success = count(array_filter($entries, static function ($entry) { return $entry['outcome'] === 'success'; }));
    ?>
    <div class="wrap dream2-settings-wrap dream2-mail-log dream2-ai-log">
      <header class="dream2-options-header"><h1>AI 请求记录</h1><span class="dream2-version">最近 30 天 / 最多 500 条</span></header>
      <div class="dream2-mail-log-content">
        <?php if (isset($_GET['cleared'])) : ?><div class="notice notice-success is-dismissible"><p>AI 请求记录已清空。</p></div><?php endif; ?>
        <div class="dream2-mail-log-metrics">
          <div><strong><?php echo esc_html((string) count($entries)); ?></strong><span>总请求</span></div>
          <div class="is-success"><strong><?php echo esc_html((string) $success); ?></strong><span>成功</span></div>
          <div class="is-danger"><strong><?php echo esc_html((string) (count($entries) - $success)); ?></strong><span>失败</span></div>
        </div>
        <div class="dream2-mail-log-toolbar">
          <form method="get"><input type="hidden" name="page" value="dream2-ai-log">
            <select name="feature"><option value="">全部功能</option><?php foreach (array('search' => '站内问答', 'summary' => '文章总结', 'summary_stream' => '文章总结流式') as $key => $label) : ?><option value="<?php echo esc_attr($key); ?>" <?php selected($feature, $key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select>
            <select name="outcome"><option value="">全部结果</option><option value="success" <?php selected($outcome, 'success'); ?>>成功</option><option value="failed" <?php selected($outcome, 'failed'); ?>>失败</option></select>
            <button type="submit" class="button">筛选</button><a class="button" href="<?php echo esc_url(dream2_mxin_ai_log_url()); ?>">刷新</a>
          </form>
          <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('确认清空全部 AI 请求记录？');"><input type="hidden" name="action" value="dream2_mxin_clear_ai_log"><?php wp_nonce_field('dream2_mxin_clear_ai_log'); ?><button type="submit" class="button button-secondary">清空记录</button></form>
        </div>
        <div class="dream2-mail-log-table"><table class="widefat striped"><thead><tr><th>时间</th><th>功能</th><th>结果</th><th>模型</th><th>HTTP 状态</th><th>耗时</th><th>错误类别 / 诊断详情</th></tr></thead><tbody>
          <?php if (!$rows) : ?><tr><td colspan="7">暂无匹配记录。</td></tr><?php endif; ?>
          <?php foreach ($rows as $entry) : ?><tr>
            <td><?php echo esc_html($entry['created_at']); ?></td><td><?php echo esc_html(array('search' => '站内问答', 'summary' => '文章总结', 'summary_stream' => '文章总结流式')[$entry['feature']] ?? $entry['feature']); ?></td>
            <td><?php echo esc_html($entry['outcome'] === 'success' ? '成功' : '失败'); ?></td><td><?php echo esc_html($entry['model'] ?: '—'); ?></td><td><?php echo esc_html($entry['http_status'] ? (string) $entry['http_status'] : '—'); ?></td><td><?php echo esc_html((string) $entry['duration_ms']); ?> ms</td><td><?php echo esc_html($entry['reason'] ?: '—'); ?><?php if (!empty($entry['detail']) || !empty($entry['request_id']) || !empty($entry['post_id'])) : ?><details><summary>查看详情</summary><?php if (!empty($entry['detail'])) : ?><p><?php echo esc_html($entry['detail']); ?></p><?php endif; ?><?php if (!empty($entry['post_id'])) : ?><p>文章 ID：<?php echo esc_html((string) $entry['post_id']); ?></p><?php endif; ?><?php if (!empty($entry['request_id'])) : ?><p>请求 ID：<?php echo esc_html($entry['request_id']); ?></p><?php endif; ?></details><?php elseif ($entry['outcome'] !== 'success') : ?><small>旧记录无详情</small><?php endif; ?></td>
          </tr><?php endforeach; ?>
        </tbody></table></div>
        <?php $pages = (int) ceil(count($filtered) / 50); if ($pages > 1) { echo '<div class="tablenav"><div class="tablenav-pages">'; echo wp_kses_post(paginate_links(array('base' => add_query_arg('paged', '%#%', dream2_mxin_ai_log_url(array_filter(array('feature' => $feature, 'outcome' => $outcome)))), 'format' => '', 'current' => $page, 'total' => $pages))); echo '</div></div>'; } ?>
      </div>
    </div>
    <?php
}

function dream2_mxin_clear_ai_log() {
    if (!current_user_can('edit_theme_options')) { wp_die(esc_html__('权限不足。', 'dream2-mxin')); }
    check_admin_referer('dream2_mxin_clear_ai_log');
    delete_option(DREAM2_MXIN_AI_LOG_OPTION);
    delete_option(DREAM2_MXIN_AI_LOG_LOCK);
    wp_safe_redirect(dream2_mxin_ai_log_url(array('cleared' => 1)));
    exit;
}
add_action('admin_post_dream2_mxin_clear_ai_log', 'dream2_mxin_clear_ai_log');

function dream2_mxin_register_ai_log_page() {
    $hook = add_submenu_page('dream2-settings', '梦屿 AI 请求记录', 'AI 请求记录', 'edit_theme_options', 'dream2-ai-log', 'dream2_mxin_render_ai_log_page');
    if ($hook) { $GLOBALS['dream2_mxin_settings_hooks'][] = $hook; }
}
add_action('admin_menu', 'dream2_mxin_register_ai_log_page', 20);
