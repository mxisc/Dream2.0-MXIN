<?php
/**
 * Dream 2.0 settings registry and administration screen.
 *
 * @package Dream2_MXIN
 */

if (!defined('ABSPATH')) {
    exit;
}

function dream2_mxin_settings_groups() {
    return array(
        'site' => '站点信息', 'appearance' => '外观特效', 'home_layout' => '首页布局',
        'content_page' => '内容页面', 'communication' => '评论邮件',
        'performance' => '性能统计', 'ai' => 'AI 配置', 'advanced' => '高级设置',
    );
}

function dream2_mxin_ai_provider_base_urls() {
    return array(
        'openai'   => 'https://api.openai.com/v1',
        'deepseek' => 'https://api.deepseek.com',
        'glm'      => 'https://open.bigmodel.cn/api/paas/v4',
    );
}

function dream2_mxin_ai_provider_for_base_url($base_url) {
    $base_url = untrailingslashit(trim((string) $base_url));
    if ($base_url === '') {
        return 'openai';
    }
    foreach (dream2_mxin_ai_provider_base_urls() as $provider => $provider_base_url) {
        if (strcasecmp($base_url, $provider_base_url) === 0) {
            return $provider;
        }
    }
    return 'custom';
}

function dream2_mxin_ai_user_choices() {
    $choices = array('0' => '未关联');
    $users = get_users(array(
        'orderby' => 'display_name',
        'order'   => 'ASC',
        'fields'  => array('ID', 'display_name', 'user_login'),
    ));
    foreach ($users as $user) {
        $display_name = trim((string) $user->display_name);
        $user_login = (string) $user->user_login;
        $choices[(string) absint($user->ID)] = sprintf(
            '%s（%s）',
            $display_name !== '' ? $display_name : $user_login,
            $user_login
        );
    }
    return $choices;
}

function dream2_mxin_settings_subgroups() {
    return array(
        'site' => array(
            array('label' => '基础资料', 'fields' => array('site_favicon_size', 'document_hidden_title', 'document_visible_title', 'copy_explain')),
            array('label' => '备案与建站信息', 'fields' => array('record_number', 'record_number_moe', 'record_number_ps', 'website_time')),
        ),
        'appearance' => array(
            array('label' => '主题模式', 'fields' => array('theme_style', 'default_theme', 'theme_color', 'night_theme_color', 'night_logo')),
            array('label' => '字体设置', 'fields' => array('font_preset', 'web_font', 'custom_font')),
            array('label' => '背景图片', 'fields' => array('enable_image_bg', 'card_opacity', 'background_image_opacity', 'background_pc', 'background_mobile', 'night_background_pc', 'night_background_mobile')),
            array('label' => '鼠标效果', 'fields' => array('cursor_style', 'cursor_move', 'cursor_click')),
            array('label' => '场景特效', 'fields' => array('effects_lantern_mode', 'effects_sakura_mode', 'effects_snowflake_mode', 'effects_universe_mode', 'effects_circle_magic_mode')),
            array('label' => '全局显示', 'fields' => array('enable_gray_mode')),
        ),
        'home_layout' => array(
            array('label' => '横幅与通知', 'fields' => array('index_inform', 'enable_banner', 'banner_image', 'banner_description')),
            array('label' => '页面结构', 'fields' => array('sidebar_column', 'left_sidebar_sticky', 'right_sidebar_sticky')),
            array('label' => '首页内容模块', 'fields' => array('carousel_options', 'module_options')),
        ),
        'content_page' => array(
            array('label' => '封面与列表', 'fields' => array('default_thumbnail', 'top_thumbnail_mode', 'thumbnail_mode')),
            array('label' => '正文增强', 'fields' => array('code_pretty', 'code_fold_line', 'img_fold_height', 'show_img_name', 'enable_katex')),
            array('label' => '阅读与分享', 'fields' => array('drawer_toc', 'invalid_tips_day', 'enable_copyright', 'enable_post_share')),
            array('label' => '文章归档', 'fields' => array('enable_archivers_route', 'archivers_route_slug')),
            array('label' => '朋友圈页面', 'fields' => array('enable_friends_stats')),
        ),
        'communication' => array(
            array('label' => '评论设置', 'fields' => array('enable_private_comment', 'avatar_privacy')),
            array('label' => '评论表情', 'fields' => array('comment_emoji_groups')),
            array('label' => 'SMTP 发信', 'fields' => array('enable_smtp', 'smtp_host', 'smtp_port', 'smtp_secure', 'smtp_username', 'smtp_password', 'smtp_from_email', 'smtp_from_name')),
            array('label' => '邮件通知', 'fields' => array('email_notify_post_author', 'email_notify_moderator', 'email_notify_reply', 'link_notify_recovered', 'link_notify_one_way_reminder', 'link_notify_one_way_deleted', 'link_notify_lost')),
        ),
        'performance' => array(
            array('label' => '页面加载', 'fields' => array('load_progress', 'enable_pjax')),
            array('label' => '缓存优化', 'fields' => array('enable_sw')),
            array('label' => '统计与搜索推送', 'fields' => array('enable_busuanzi', 'enable_baidu_push', 'enable_toutiao_push')),
        ),
        'ai' => array(
            array('label' => 'OpenAI 兼容服务', 'fields' => array('ai_provider', 'ai_base_url', 'ai_api_key', 'ai_model')),
            array('label' => '角色卡与关联账户', 'fields' => array('ai_system_prompt', 'ai_user_id')),
            array('label' => '站内问答', 'fields' => array('enable_ai_search', 'ai_search_prompt')),
            array('label' => '文章总结', 'fields' => array('enable_article_summary', 'ai_summary_prompt')),
        ),
        'advanced' => array(
            array('label' => 'CSS', 'fields' => array('external_css', 'inline_css')),
            array('label' => 'Head 脚本', 'fields' => array('external_js_head', 'inline_js_head')),
            array('label' => 'Body 脚本', 'fields' => array('external_js_body', 'inline_js_body')),
        ),
    );
}

function dream2_mxin_link_settings_subgroups() {
    return array(
        array('label' => '接管与分类', 'fields' => array('link_takeover_categories', 'link_friend_category', 'link_one_way_category', 'link_lost_category')),
        array('label' => '自动检测', 'fields' => array('enable_auto_link_check', 'link_check_interval', 'link_check_batch_size', 'link_check_failure_threshold', 'link_auto_move_one_way', 'link_auto_move_lost', 'link_one_way_notice_days', 'link_one_way_delete_days')),
        array('label' => '页面展示', 'fields' => array('links_thumbnail', 'links_default_avatar')),
        array('label' => '友链申请', 'fields' => array('link_enable_comment', 'enable_link_application', 'link_application_blacklist')),
        array('label' => '交换信息', 'fields' => array('show_exchange_info', 'links_blogger_name', 'links_blogger_url', 'links_blogger_avatar', 'links_blogger_description')),
        array('label' => '补充内容', 'fields' => array('links_info')),
    );
}

function dream2_mxin_prepare_settings_subgroups($definitions, $field_names) {
    $available = array_fill_keys(array_values($field_names), true);
    $assigned = array();
    $groups = array();
    foreach ($definitions as $definition) {
        $fields = array_values(array_filter($definition['fields'] ?? array(), static function ($name) use ($available) {
            return isset($available[$name]);
        }));
        if (!$fields) {
            continue;
        }
        $groups[] = array(
            'label'  => $definition['label'] ?? '其他设置',
            'fields' => $fields,
        );
        $assigned = array_merge($assigned, $fields);
    }
    $remaining = array_values(array_diff(array_keys($available), $assigned));
    if ($remaining) {
        $groups[] = array('label' => '其他设置', 'fields' => $remaining);
    }
    return $groups;
}

function dream2_mxin_highlight_theme_choices() {
    $choices = array();
    $files = glob(get_template_directory() . '/assets/lib/highlightjs@11.5.1/styles/*.min.css');
    foreach ($files ?: array() as $file) {
        $value = basename($file, '.min.css');
        $label = ucwords(str_replace(array('-', '_'), ' ', $value));
        $choices[$value] = $label;
    }
    return $choices;
}

function dream2_mxin_sidebar_hide_choices() {
    return array(
        'is-hidden-mobile'      => '移动设备隐藏',
        'is-hidden-not-desktop' => '移动、平板设备隐藏',
        'is-hidden-desktop'     => '桌面设备隐藏',
        'is-not-hidden'         => '不隐藏',
    );
}

function dream2_mxin_sidebar_stat_choices() {
    return array(
        'post'     => '文章数量',
        'category' => '分类数量',
        'tag'      => '标签数量',
        'comment'  => '评论数量',
        'visit'    => '访问数量',
    );
}

function dream2_mxin_repeater_fields($name) {
    $common = array(
        'title'  => array('label' => '标题', 'type' => 'text'),
        'url'    => array('label' => '地址', 'type' => 'url'),
        'image'  => array('label' => '背景图', 'type' => 'image'),
        'target' => array(
            'label'   => '打开方式',
            'type'    => 'radio',
            'default' => '_blank',
            'choices' => array('_blank' => '新窗口', '_self' => '原窗口'),
        ),
    );
    if ($name === 'module_options') {
        return array('tag' => array('label' => '标签', 'type' => 'text')) + $common;
    }
    if ($name === 'custom_stats') {
        return array(
            'type' => array(
                'label'   => '统计项',
                'type'    => 'select',
                'default' => 'post',
                'choices' => dream2_mxin_sidebar_stat_choices(),
            ),
        );
    }
    if ($name === 'custom_options') {
        return array(
            'name' => array('label' => '名称', 'type' => 'text'),
            'icon' => array('label' => '图标', 'type' => 'text'),
            'url'  => array('label' => '地址', 'type' => 'textarea'),
        );
    }
    return $common;
}

function dream2_mxin_repeater_max($name) {
    if ($name === 'module_options') {
        return 6;
    }
    if ($name === 'custom_stats') {
        return 3;
    }
    return 0;
}

function dream2_mxin_normalize_repeater($value, $name = '') {
    if (is_string($value)) {
        $value = trim($value);
        if ($value === '') {
            return array();
        }
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            $value = $decoded;
        } else {
            $items = array();
            $lines = $name === 'custom_stats'
                ? preg_split('/\s*,\s*/', $value)
                : preg_split('/\r\n|\r|\n/', $value);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                if ($name === 'custom_stats') {
                    $items[] = array('type' => sanitize_key($line));
                } elseif ($name === 'custom_options') {
                    $parts = array_map('trim', explode('|', $line, 3));
                    $items[] = array(
                        'name' => $parts[0] ?? '',
                        'icon' => $parts[1] ?? '',
                        'url'  => $parts[2] ?? '',
                    );
                } else {
                    $parts = array_map('trim', explode('|', $line));
                    $items[] = array(
                        'title'  => $parts[0] ?? '',
                        'url'    => $parts[1] ?? '',
                        'image'  => $parts[2] ?? '',
                        'tag'    => $parts[3] ?? '',
                        'target' => $parts[4] ?? '_self',
                    );
                }
            }
            $value = $items;
        }
    }
    if (!is_array($value)) {
        return array();
    }
    $items = array_values($value);
    if ($name === 'custom_options') {
        foreach ($items as &$item) {
            if (is_array($item)) {
                $item['name'] = $item['name'] ?? ($item['title'] ?? '');
                $item['icon'] = $item['icon'] ?? ($item['tag'] ?? '');
            }
        }
        unset($item);
    }
    return $items;
}

function dream2_mxin_sanitize_repeater($name, $value) {
    $fields = dream2_mxin_repeater_fields($name);
    $items = array();
    foreach (dream2_mxin_normalize_repeater($value, $name) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $clean_item = array();
        foreach ($fields as $field_name => $field) {
            $field_value = isset($item[$field_name]) ? wp_unslash($item[$field_name]) : ($field['default'] ?? '');
            if ($field['type'] === 'url' || $field['type'] === 'image') {
                $clean_item[$field_name] = esc_url_raw($field_value);
            } elseif ($field['type'] === 'select') {
                $clean_item[$field_name] = array_key_exists((string) $field_value, $field['choices'])
                    ? (string) $field_value
                    : (string) $field['default'];
            } elseif ($field['type'] === 'radio') {
                $clean_item[$field_name] = array_key_exists((string) $field_value, $field['choices'])
                    ? (string) $field_value
                    : (string) $field['default'];
            } elseif ($field['type'] === 'textarea') {
                $clean_item[$field_name] = $field_name === 'content' && current_user_can('unfiltered_html')
                    ? (string) $field_value
                    : sanitize_textarea_field($field_value);
            } else {
                $clean_item[$field_name] = sanitize_text_field($field_value);
            }
        }
        $has_content = array_filter($clean_item, function ($field_value, $field_name) {
            return $field_name !== 'target' && $field_value !== '';
        }, ARRAY_FILTER_USE_BOTH);
        if ($has_content) {
            $items[] = $clean_item;
        }
    }
    $max = dream2_mxin_repeater_max($name);
    return $max ? array_slice($items, 0, $max) : $items;
}

function dream2_mxin_render_repeater_item($name, $index, $item = array()) {
    $fields = dream2_mxin_repeater_fields($name);
    ?>
    <div class="dream2-repeater-item" data-dream-repeater-item>
        <div class="dream2-repeater-item-header">
            <strong data-dream-repeater-label><?php echo esc_html('项目 ' . ((int) $index + 1)); ?></strong>
            <button type="button" class="button-link-delete" data-dream-repeater-remove>删除</button>
        </div>
        <div class="dream2-repeater-fields">
            <?php foreach ($fields as $field_name => $field) :
                $field_value = $item[$field_name] ?? ($field['default'] ?? '');
                $input_name = 'dream2_options[' . $name . '][' . $index . '][' . $field_name . ']';
                ?>
                <div class="dream2-repeater-field dream2-repeater-field-<?php echo esc_attr($field['type']); ?> dream2-repeater-field-<?php echo esc_attr($field_name); ?>">
                    <label><?php echo esc_html($field['label']); ?></label>
                    <?php if ($field['type'] === 'image') : ?>
                        <div class="dream2-hoshi-media-field">
                            <input type="url" name="<?php echo esc_attr($input_name); ?>" value="<?php echo esc_attr($field_value); ?>">
                            <button type="button" class="button dream2-media-button">选择图片</button>
                        </div>
                    <?php elseif ($field['type'] === 'radio') : ?>
                        <div class="dream2-radio-group">
                            <?php foreach ($field['choices'] as $choice => $choice_label) : ?>
                                <label><input type="radio" name="<?php echo esc_attr($input_name); ?>" value="<?php echo esc_attr($choice); ?>" <?php checked($field_value, $choice); ?>> <span><?php echo esc_html($choice_label); ?></span></label>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($field['type'] === 'select') : ?>
                        <?php if (!array_key_exists((string) $field_value, $field['choices'])) $field_value = $field['default'] ?? array_key_first($field['choices']); ?>
                        <select name="<?php echo esc_attr($input_name); ?>">
                            <?php foreach ($field['choices'] as $choice => $choice_label) : ?>
                                <option value="<?php echo esc_attr($choice); ?>" <?php selected($field_value, $choice); ?>><?php echo esc_html($choice_label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($field['type'] === 'textarea') : ?>
                        <textarea name="<?php echo esc_attr($input_name); ?>" rows="3"><?php echo esc_textarea($field_value); ?></textarea>
                    <?php else : ?>
                        <input type="<?php echo $field['type'] === 'url' ? 'url' : 'text'; ?>" name="<?php echo esc_attr($input_name); ?>" value="<?php echo esc_attr($field_value); ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

function dream2_mxin_link_settings_fields() {
    return array('link_takeover_categories', 'link_friend_category', 'link_one_way_category', 'link_lost_category', 'enable_auto_link_check', 'link_check_interval', 'link_check_batch_size', 'link_check_failure_threshold', 'link_auto_move_one_way', 'link_auto_move_lost', 'link_one_way_notice_days', 'link_one_way_delete_days', 'links_thumbnail', 'links_default_avatar', 'show_exchange_info', 'links_blogger_name', 'links_blogger_url', 'links_blogger_avatar', 'links_blogger_description', 'links_info', 'link_enable_comment', 'enable_link_application', 'link_application_blacklist');
}

function dream2_mxin_normalize_link_application_host($value) {
    $value = strtolower(trim((string) $value));
    if ($value === '') {
        return '';
    }
    $url = preg_match('#^https?://#i', $value) ? $value : 'https://' . ltrim($value, '/');
    $host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
    $host = rtrim($host, '.');
    if (str_starts_with($host, 'www.')) {
        $host = substr($host, 4);
    }
    if ($host !== '' && function_exists('idn_to_ascii')) {
        $ascii_host = idn_to_ascii($host, 0);
        if ($ascii_host !== false) {
            $host = strtolower($ascii_host);
        }
    }
    return preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $host) ? $host : '';
}

function dream2_mxin_normalize_link_application_blacklist($value) {
    $hosts = array();
    foreach (preg_split('/[\r\n,]+/', (string) $value) as $entry) {
        $host = dream2_mxin_normalize_link_application_host($entry);
        if ($host !== '') {
            $hosts[$host] = true;
        }
    }
    return implode("\n", array_keys($hosts));
}

function dream2_mxin_default_link_category_id($name) {
    $term = get_term_by('name', $name, 'link_category');
    return $term && !is_wp_error($term) ? (int) $term->term_id : 0;
}

function dream2_mxin_find_link_category_id($name) {
    $name = trim((string) $name);
    if ($name === '') {
        return 0;
    }

    $existing = term_exists($name, 'link_category');
    if ($existing) {
        return is_array($existing) ? (int) $existing['term_id'] : (int) $existing;
    }

    $term = get_term_by('name', $name, 'link_category');
    if ($term && !is_wp_error($term)) {
        return (int) $term->term_id;
    }

    $term = get_term_by('slug', sanitize_title($name), 'link_category');
    return $term && !is_wp_error($term) ? (int) $term->term_id : 0;
}

function dream2_mxin_base_link_category_ids() {
    $friend_id = absint(dream2_get('link_friend_category', dream2_mxin_default_link_category_id('友情链接')));
    $one_way_id = absint(dream2_get('link_one_way_category', dream2_mxin_default_link_category_id('单向友链')));
    $lost_id = absint(dream2_get('link_lost_category', dream2_mxin_default_link_category_id('失联博客')));
    return array_values(array_unique(array_filter(array($friend_id, $one_way_id, $lost_id))));
}

function dream2_mxin_extra_link_category_ids() {
    $ids = get_option('dream2_link_managed_category_ids', array());
    if (!is_array($ids)) {
        return array();
    }

    $base_ids = dream2_mxin_base_link_category_ids();
    $ids = array_values(array_unique(array_filter(array_map('absint', $ids), function ($term_id) use ($base_ids) {
        if (in_array($term_id, $base_ids, true)) {
            return false;
        }
        $term = get_term($term_id, 'link_category');
        return $term && !is_wp_error($term);
    })));

    return $ids;
}

function dream2_mxin_update_extra_link_category_ids($ids) {
    $base_ids = dream2_mxin_base_link_category_ids();
    $ids = array_values(array_unique(array_filter(array_map('absint', (array) $ids), function ($term_id) use ($base_ids) {
        if (in_array($term_id, $base_ids, true)) {
            return false;
        }
        $term = get_term($term_id, 'link_category');
        return $term && !is_wp_error($term);
    })));

    update_option('dream2_link_managed_category_ids', $ids, false);
    return $ids;
}

function dream2_mxin_add_extra_link_category_id($term_id) {
    $term_id = absint($term_id);
    if (!$term_id || in_array($term_id, dream2_mxin_base_link_category_ids(), true)) {
        return;
    }

    $ids = dream2_mxin_extra_link_category_ids();
    if (!in_array($term_id, $ids, true)) {
        $ids[] = $term_id;
        dream2_mxin_update_extra_link_category_ids($ids);
    }
}

function dream2_mxin_add_link_category_to_order($term_id) {
    $term_id = absint($term_id);
    if (!$term_id) {
        return;
    }

    $order = dream2_mxin_link_category_order();
    if (!in_array($term_id, $order, true)) {
        $order[] = $term_id;
        update_option('dream2_link_category_order', $order, false);
    }
}

function dream2_mxin_friend_link_category_ids() {
    return array_values(array_unique(array_filter(array_merge(
        dream2_mxin_base_link_category_ids(),
        dream2_mxin_extra_link_category_ids()
    ))));
}

function dream2_mxin_link_category_order() {
    $order = get_option('dream2_link_category_order', array());
    return is_array($order) ? array_values(array_filter(array_map('absint', $order))) : array();
}

function dream2_mxin_friend_link_group_terms() {
    $groups = array(
        'friend' => array(
            'label' => '友情链接',
            'term_id' => absint(dream2_get('link_friend_category', dream2_mxin_default_link_category_id('友情链接'))),
        ),
        'one_way' => array(
            'label' => '单向友链',
            'term_id' => absint(dream2_get('link_one_way_category', dream2_mxin_default_link_category_id('单向友链'))),
        ),
        'lost' => array(
            'label' => '失联博客',
            'term_id' => absint(dream2_get('link_lost_category', dream2_mxin_default_link_category_id('失联博客'))),
        ),
    );

    foreach ($groups as $key => $group) {
        $term = $group['term_id'] ? get_term($group['term_id'], 'link_category') : null;
        if (!$term || is_wp_error($term)) {
            unset($groups[$key]);
            continue;
        }
        $groups[$key]['term'] = $term;
    }
    $seen = array_map('intval', wp_list_pluck($groups, 'term_id'));
    foreach (dream2_mxin_extra_link_category_ids() as $term_id) {
        if (in_array((int) $term_id, $seen, true)) {
            continue;
        }
        $term = get_term($term_id, 'link_category');
        if (!$term || is_wp_error($term)) {
            continue;
        }
        $groups['managed_' . $term_id] = array(
            'label'   => $term->name,
            'term_id' => (int) $term_id,
            'term'    => $term,
        );
        $seen[] = (int) $term_id;
    }
    $order = dream2_mxin_link_category_order();
    if ($order) {
        usort($groups, function ($a, $b) use ($order) {
            $index_a = array_search((int) $a['term_id'], $order, true);
            $index_b = array_search((int) $b['term_id'], $order, true);
            $index_a = false === $index_a ? PHP_INT_MAX : $index_a;
            $index_b = false === $index_b ? PHP_INT_MAX : $index_b;
            return $index_a <=> $index_b;
        });
    }
    return $groups;
}

function dream2_mxin_friend_link_terms() {
    $terms = array();
    foreach (dream2_mxin_friend_link_group_terms() as $group) {
        $terms[] = $group['term'];
    }
    return $terms;
}

function dream2_mxin_link_extra_defaults() {
    return array(
        'admin_email'  => '',
        'backlink_url' => '',
        'verification' => array(
            'access'      => 'pending',
            'backlink'    => 'pending',
            'status'      => 'pending',
            'message'     => '等待检测',
            'checked_at'  => '',
            'matched_url' => '',
            'status_code' => 0,
            'consecutive_failures' => 0,
            'first_failed_at'      => '',
            'last_success_at'      => '',
            'confirmed_failure'    => false,
            'moved_at'             => '',
            'access_failures'       => 0,
            'access_first_failed_at'=> '',
            'backlink_failures'     => 0,
            'backlink_first_failed_at' => '',
            'one_way_notified_at'   => '',
            'one_way_entered_at'    => '',
            'lost_notified_at'      => '',
        ),
    );
}

function dream2_mxin_link_extra($bookmark) {
    $extra = dream2_mxin_link_extra_defaults();
    $notes = isset($bookmark->link_notes) ? trim((string) $bookmark->link_notes) : '';
    if ($notes === '') {
        return $extra;
    }
    $data = json_decode($notes, true);
    if (!is_array($data)) {
        return $extra;
    }
    $source = isset($data['dream2']) && is_array($data['dream2']) ? $data['dream2'] : $data;
    $extra['admin_email'] = isset($source['admin_email']) ? sanitize_email($source['admin_email']) : '';
    $extra['backlink_url'] = isset($source['backlink_url']) ? esc_url_raw($source['backlink_url']) : '';
    if (!empty($source['verification']) && is_array($source['verification'])) {
        $extra['verification'] = array_merge($extra['verification'], $source['verification']);
    }
    if ($extra['backlink_url'] === '') {
        $extra['verification']['backlink'] = 'skip';
        if (empty($extra['verification']['checked_at'])) {
            $extra['verification']['status'] = 'pending';
            $extra['verification']['message'] = '等待访问检测，未配置反链检测页';
        } elseif ($extra['verification']['status'] === 'missing') {
            $extra['verification']['status'] = $extra['verification']['access'] === 'success' ? 'success' : 'pending';
            if ($extra['verification']['access'] === 'success') {
                $extra['verification']['message'] = '站点正常，未配置反链检测页';
            }
        }
    }
    return $extra;
}

function dream2_mxin_link_notes($admin_email, $backlink_url, $verification = array()) {
    $backlink_url = esc_url_raw($backlink_url);
    $defaults = dream2_mxin_link_extra_defaults()['verification'];
    $verification = array_merge($defaults, is_array($verification) ? $verification : array());
    if ($backlink_url === '') {
        $verification['backlink'] = 'skip';
        if (empty($verification['checked_at']) || $verification['status'] === 'missing') {
            $verification['status'] = $verification['access'] === 'success' ? 'success' : 'pending';
            $verification['message'] = $verification['access'] === 'success'
                ? '站点正常，未配置反链检测页'
                : '等待访问检测，未配置反链检测页';
        }
    }
    return wp_json_encode(array(
        'dream2' => array(
            'admin_email' => sanitize_email($admin_email),
            'backlink_url' => $backlink_url,
            'verification' => $verification,
        ),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function dream2_mxin_link_http_check($url, $body_limit) {
    $response = wp_safe_remote_get($url, array(
        'timeout'             => 5,
        'redirection'         => 3,
        'limit_response_size' => $body_limit,
        'user-agent'          => 'Mozilla/5.0 (compatible; Dream2 Link Checker/1.0; +' . home_url('/') . ')',
        'headers'             => array('Accept' => 'text/html,application/xhtml+xml,application/json;q=0.9,*/*;q=0.1'),
    ));
    if (is_wp_error($response)) {
        return array('ok' => false, 'code' => 0, 'body' => '', 'message' => $response->get_error_message());
    }
    $code = (int) wp_remote_retrieve_response_code($response);
    return array(
        'ok'      => $code >= 200 && $code < 300,
        'code'    => $code,
        'body'    => (string) wp_remote_retrieve_body($response),
        'content_type' => (string) wp_remote_retrieve_header($response, 'content-type'),
        'message' => $code >= 200 && $code < 300 ? '' : 'HTTP ' . $code,
    );
}

function dream2_mxin_link_normalize_host($host) {
    $host = strtolower(rtrim((string) $host, '.'));
    return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
}

function dream2_mxin_link_url_points_to($href, $target_url, $base_url) {
    $href = html_entity_decode(trim((string) $href), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if ($href === '' || str_starts_with($href, '#')) {
        return false;
    }
    $absolute = WP_Http::make_absolute_url($href, $base_url);
    $link = wp_parse_url($absolute);
    $target = wp_parse_url($target_url);
    if (!$link || !$target || empty($link['scheme']) || empty($link['host']) || empty($target['scheme']) || empty($target['host'])) {
        return false;
    }
    $port = function ($parts) {
        if (isset($parts['port'])) return (int) $parts['port'];
        return strtolower($parts['scheme']) === 'https' ? 443 : 80;
    };
    if (
        strtolower($link['scheme']) !== strtolower($target['scheme'])
        || dream2_mxin_link_normalize_host($link['host']) !== dream2_mxin_link_normalize_host($target['host'])
        || $port($link) !== $port($target)
    ) {
        return false;
    }
    $normalize_path = function ($path) {
        $path = '/' . ltrim((string) $path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    };
    $target_path = $normalize_path($target['path'] ?? '/');
    $link_path = $normalize_path($link['path'] ?? '/');
    return $target_path === '/' || $link_path === $target_path || str_starts_with($link_path, $target_path . '/');
}

function dream2_mxin_backlink_json_url_key($key) {
    $key = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', (string) $key);
    $key = strtolower(str_replace(array('-', '.', ' '), '_', $key));
    if (preg_match('/(?:^|_)(?:avatar|icon|image|logo|cover|thumbnail)(?:$|_)/', $key)) {
        return false;
    }
    return (bool) preg_match('/(?:^|_)(?:url|href|link|links|website|homepage|site)(?:$|_)/', $key);
}

function dream2_mxin_find_backlink_in_json($value, $target_url, $base_url, &$candidate_count, $value_is_url = false, $depth = 0) {
    if ($depth > 24) {
        return '';
    }
    if (is_array($value)) {
        foreach ($value as $key => $child) {
            $child_is_url = is_int($key)
                ? $value_is_url
                : dream2_mxin_backlink_json_url_key($key);
            $matched = dream2_mxin_find_backlink_in_json(
                $child,
                $target_url,
                $base_url,
                $candidate_count,
                $child_is_url,
                $depth + 1
            );
            if ($matched !== '') {
                return $matched;
            }
        }
        return '';
    }
    if (!$value_is_url || !is_string($value)) {
        return '';
    }
    $value = html_entity_decode(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if ($value === '') {
        return '';
    }
    $absolute = WP_Http::make_absolute_url($value, $base_url);
    $parts = wp_parse_url($absolute);
    if (!$parts || empty($parts['scheme']) || empty($parts['host']) || !in_array(strtolower($parts['scheme']), array('http', 'https'), true)) {
        return '';
    }
    $candidate_count++;
    return dream2_mxin_link_url_points_to($value, $target_url, $base_url) ? $absolute : '';
}

function dream2_mxin_find_backlink_in_html($html, $target_url, $base_url, &$anchor_count) {
    if (!class_exists('WP_HTML_Tag_Processor')) {
        require_once ABSPATH . WPINC . '/html-api/html5-named-character-references.php';
        require_once ABSPATH . WPINC . '/html-api/class-wp-html-decoder.php';
        require_once ABSPATH . WPINC . '/html-api/class-wp-html-attribute-token.php';
        require_once ABSPATH . WPINC . '/html-api/class-wp-html-span.php';
        require_once ABSPATH . WPINC . '/html-api/class-wp-html-text-replacement.php';
        require_once ABSPATH . WPINC . '/html-api/class-wp-html-token.php';
        require_once ABSPATH . WPINC . '/html-api/class-wp-html-tag-processor.php';
    }
    $processor = new WP_HTML_Tag_Processor($html);
    $anchor_count = 0;
    while ($processor->next_tag('a')) {
        $anchor_count++;
        $href = $processor->get_attribute('href');
        if (is_string($href) && dream2_mxin_link_url_points_to($href, $target_url, $base_url)) {
            return WP_Http::make_absolute_url($href, $base_url);
        }
    }
    return '';
}

function dream2_mxin_inspect_backlink_content($body, $target_url, $base_url, $content_type = '') {
    $body = (string) $body;
    $trimmed = ltrim($body);
    $content_type = strtolower((string) $content_type);
    $json_like = str_contains($content_type, 'json')
        || str_starts_with($trimmed, '{')
        || str_starts_with($trimmed, '[');

    if ($json_like) {
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            return array(
                'status' => 'error',
                'matched_url' => '',
                'message' => '反链页返回的 JSON 无法解析',
            );
        }
        $candidate_count = 0;
        $matched_url = dream2_mxin_find_backlink_in_json(
            $decoded,
            $target_url,
            $base_url,
            $candidate_count
        );
        if ($matched_url !== '') {
            return array(
                'status' => 'success',
                'matched_url' => $matched_url,
                'message' => '站点正常，已在 JSON 中找到反链',
            );
        }
        if ($candidate_count > 0) {
            return array(
                'status' => 'missing',
                'matched_url' => '',
                'message' => '站点正常，JSON 中未找到反链',
            );
        }
        return array(
            'status' => 'error',
            'matched_url' => '',
            'message' => '反链页返回 JSON，但没有可识别的链接字段',
        );
    }

    $anchor_count = 0;
    $matched_url = dream2_mxin_find_backlink_in_html($body, $target_url, $base_url, $anchor_count);
    if ($matched_url !== '') {
        return array(
            'status' => 'success',
            'matched_url' => $matched_url,
            'message' => '站点正常，已在 HTML 中找到反链',
        );
    }

    $embedded_candidate_count = 0;
    // Parse attributes as HTML so minified, unquoted JSON script tags work too.
    if (preg_match_all(
        '~<script\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>(.*?)</script\s*>~is',
        $body,
        $scripts,
        PREG_SET_ORDER
    )) {
        foreach ($scripts as $script) {
            $script_processor = new WP_HTML_Tag_Processor($script[0]);
            if (!$script_processor->next_tag('script')) {
                continue;
            }
            $type = strtolower(trim((string) $script_processor->get_attribute('type')));
            $id = (string) $script_processor->get_attribute('id');
            if (
                !in_array($type, array('application/json', 'application/ld+json'), true)
                && !in_array($id, array('__NEXT_DATA__', '__NUXT_DATA__'), true)
            ) {
                continue;
            }
            $decoded = json_decode(trim($script[1]), true);
            if (!is_array($decoded)) {
                continue;
            }
            $matched_url = dream2_mxin_find_backlink_in_json(
                $decoded,
                $target_url,
                $base_url,
                $embedded_candidate_count
            );
            if ($matched_url !== '') {
                return array(
                    'status' => 'success',
                    'matched_url' => $matched_url,
                    'message' => '站点正常，已在页面数据中找到反链',
                );
            }
        }
    }

    $client_rendered = (bool) preg_match(
        '~(?:id=["\'](?:app|root|__next)["\']|data-reactroot|__NUXT__|/_next/static/|type=["\']module["\'])~i',
        $body
    );
    if ($anchor_count === 0 || ($client_rendered && $embedded_candidate_count === 0)) {
        return array(
            'status' => 'error',
            'matched_url' => '',
            'message' => '反链页未返回可可靠解析的链接，可能由 JavaScript 动态渲染',
        );
    }
    return array(
        'status' => 'missing',
        'matched_url' => '',
        'message' => '站点正常，HTML 中未找到反链',
    );
}

function dream2_mxin_find_backlink($body, $target_url, $base_url, $content_type = '') {
    $inspection = dream2_mxin_inspect_backlink_content($body, $target_url, $base_url, $content_type);
    return $inspection['matched_url'];
}

function dream2_mxin_verify_friend_link($bookmark) {
    $extra = dream2_mxin_link_extra($bookmark);
    $checked_at = current_time('mysql');
    $access = dream2_mxin_link_http_check($bookmark->link_url, 64 * 1024);
    if (!$access['ok']) {
        return array(
            'access'      => 'error',
            'backlink'    => $extra['backlink_url'] ? 'pending' : 'skip',
            'status'      => 'error',
            'message'     => '站点访问失败：' . $access['message'],
            'checked_at'  => $checked_at,
            'matched_url' => '',
            'status_code' => $access['code'],
        );
    }
    if ($extra['backlink_url'] === '') {
        return array(
            'access'      => 'success',
            'backlink'    => 'skip',
            'status'      => 'success',
            'message'     => '站点正常，未配置反链检测页',
            'checked_at'  => $checked_at,
            'matched_url' => '',
            'status_code' => $access['code'],
        );
    }
    $backlink = dream2_mxin_link_http_check($extra['backlink_url'], 1024 * 1024);
    if (!$backlink['ok']) {
        return array(
            'access'      => 'success',
            'backlink'    => 'error',
            'status'      => 'error',
            'message'     => '反链页访问失败：' . $backlink['message'],
            'checked_at'  => $checked_at,
            'matched_url' => '',
            'status_code' => $backlink['code'],
        );
    }
    $target_url = dream2_get('links_blogger_url', home_url('/')) ?: home_url('/');
    $inspection = dream2_mxin_inspect_backlink_content(
        $backlink['body'],
        $target_url,
        $extra['backlink_url'],
        $backlink['content_type'] ?? ''
    );
    $matched_url = $inspection['matched_url'];
    return array(
        'access'      => 'success',
        'backlink'    => $inspection['status'],
        'status'      => $inspection['status'],
        'message'     => $inspection['message'],
        'checked_at'  => $checked_at,
        'matched_url' => $matched_url,
        'status_code' => $backlink['code'],
    );
}

function dream2_mxin_prepare_link_verification($previous, $result, $advance_counters = true) {
    $defaults = dream2_mxin_link_extra_defaults()['verification'];
    $previous = array_merge($defaults, is_array($previous) ? $previous : array());
    $result = array_merge($defaults, is_array($result) ? $result : array());
    $progress_fields = array(
        'access_failures',
        'access_first_failed_at',
        'backlink_failures',
        'backlink_first_failed_at',
        'last_success_at',
        'moved_at',
        'one_way_notified_at',
        'one_way_entered_at',
        'lost_notified_at',
    );
    foreach ($progress_fields as $field) {
        $result[$field] = $previous[$field];
    }

    $access_failed = $result['access'] === 'error';
    $backlink_failed = !$access_failed
        && in_array($result['backlink'], array('missing', 'error'), true);
    $threshold = max(1, absint(dream2_get('link_check_failure_threshold', 3)));

    if ($advance_counters) {
        if ($access_failed) {
            $result['access_failures'] = absint($previous['access_failures']) + 1;
            $result['access_first_failed_at'] = $previous['access_first_failed_at']
                ?: $result['checked_at'];
        } else {
            $result['access_failures'] = 0;
            $result['access_first_failed_at'] = '';
        }

        if ($backlink_failed) {
            $result['backlink_failures'] = absint($previous['backlink_failures']) + 1;
            $result['backlink_first_failed_at'] = $previous['backlink_first_failed_at']
                ?: $result['checked_at'];
        } elseif (!$access_failed) {
            $result['backlink_failures'] = 0;
            $result['backlink_first_failed_at'] = '';
        }
    }

    if ($access_failed) {
        $result['consecutive_failures'] = absint($result['access_failures']);
        $result['first_failed_at'] = $result['access_first_failed_at'];
        $result['confirmed_failure'] = $result['access_failures'] >= $threshold;
    } elseif ($backlink_failed) {
        $result['consecutive_failures'] = absint($result['backlink_failures']);
        $result['first_failed_at'] = $result['backlink_first_failed_at'];
        $result['confirmed_failure'] = $result['backlink_failures'] >= $threshold;
    } else {
        $result['consecutive_failures'] = 0;
        $result['first_failed_at'] = '';
        $result['confirmed_failure'] = false;
        if ($advance_counters) {
            $result['last_success_at'] = $result['checked_at'];
        }
    }

    if ($access_failed || $backlink_failed) {
        if ($advance_counters) {
            $failure_label = $access_failed ? '站点访问' : '反链';
            $result['message'] .= $result['confirmed_failure']
                ? '；' . $failure_label . '已连续失败 ' . $result['consecutive_failures'] . ' 次'
                : '；' . $failure_label . '连续失败 ' . $result['consecutive_failures'] . '/' . $threshold . ' 次，等待复检';
        } else {
            $result['message'] .= '；手动检测不计入自动迁移次数';
        }
    }

    return $result;
}

function dream2_mxin_link_group_name($option_name, $default_name) {
    $term_id = absint(dream2_get($option_name, dream2_mxin_default_link_category_id($default_name)));
    $term = $term_id ? get_term($term_id, 'link_category') : null;

    return $term && !is_wp_error($term) ? $term->name : $default_name;
}

function dream2_mxin_send_link_group_notification($bookmark, $type, $verification) {
    $option_map = array(
        'recovered'        => 'link_notify_recovered',
        'one_way_reminder' => 'link_notify_one_way_reminder',
        'one_way_deleted'  => 'link_notify_one_way_deleted',
        'lost'             => 'link_notify_lost',
    );
    if (empty($option_map[$type]) || !dream2_enabled($option_map[$type])) {
        return false;
    }
    $extra = dream2_mxin_link_extra($bookmark);
    $email = sanitize_email($extra['admin_email'] ?? '');
    if (!$email || !is_email($email)) {
        return false;
    }

    $site_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
    $headers = dream2_mxin_mail_headers();
    $friend_group = dream2_mxin_link_group_name('link_friend_category', '友情链接');
    $one_way_group = dream2_mxin_link_group_name('link_one_way_category', '单向友链');
    $lost_group = dream2_mxin_link_group_name('link_lost_category', '失联博客');
    if (in_array($type, array('one_way_reminder', 'one_way_deleted'), true)) {
        $is_deleted = $type === 'one_way_deleted';
        $days = max(1, absint(dream2_get(
            $is_deleted ? 'link_one_way_delete_days' : 'link_one_way_notice_days',
            $is_deleted ? 7 : 1
        )));
        $delete_days = max(
            max(1, absint(dream2_get('link_one_way_notice_days', 1))),
            absint(dream2_get('link_one_way_delete_days', 7))
        );
        $subject = sprintf('[%s] %s：%s', $site_name, $is_deleted ? '友情链接已删除' : '单向友链提醒', $bookmark->link_name);
        $rows = array(
            array('label' => '友链名称', 'value' => $bookmark->link_name),
            array('label' => '友链地址', 'value' => $bookmark->link_url),
            array('label' => '反链检测页', 'value' => $extra['backlink_url'] ?? ''),
            array('label' => '处理规则', 'value' => sprintf('进入%s分组 %d 天后%s', $one_way_group, $days, $is_deleted ? '删除' : '提醒')),
            array('label' => '检测结果', 'value' => $verification['message'] ?? ''),
            array('label' => '进入时间', 'value' => $verification['one_way_entered_at'] ?? ''),
        );
        if (!$is_deleted) {
            $rows[] = array(
                'label' => '后续处理',
                'value' => sprintf('进入%s分组满 %d 天时，如果仍未检测到反链，该友链将被自动删除。', $one_way_group, $delete_days),
            );
        }
        $message = dream2_mxin_mail_template(
            $is_deleted ? '友情链接已删除' : '单向友链提醒',
            $is_deleted
                ? sprintf('该友链进入%s分组后仍未恢复反链，现已按站点规则删除。', $one_way_group)
                : '本站暂未在反链检测页找到友链。新主题可能导致反链无法识别，友链页面路径变更也可能触发提醒；如果属于误判，或页面、路径已经更新，请联系本站博主处理。',
            $rows,
            '访问本站',
            home_url('/')
        );
        if ($is_deleted) {
            $site_admin = sanitize_email(get_option('admin_email'));
            if ($site_admin && is_email($site_admin) && strtolower($site_admin) !== strtolower($email)) {
                $headers[] = 'Cc: ' . $site_admin;
            }
        }
    } elseif ($type === 'recovered') {
        $subject = sprintf('[%s] 友链已恢复正常：%s', $site_name, $bookmark->link_name);
        $message = dream2_mxin_mail_template(
            '友链已恢复正常',
            sprintf('友链检测成功，已恢复到%s分组。', $friend_group),
            array(
                array('label' => '友链名称', 'value' => $bookmark->link_name),
                array('label' => '友链地址', 'value' => $bookmark->link_url),
                array('label' => '目标分组', 'value' => $friend_group),
                array('label' => '检测结果', 'value' => $verification['message'] ?? ''),
                array('label' => '检测时间', 'value' => $verification['checked_at'] ?? current_time('mysql')),
            ),
            '访问友链',
            $bookmark->link_url
        );
    } else {
        $labels = array(
            'one_way'  => $one_way_group,
            'lost'     => $lost_group,
        );
        $label = $labels[$type] ?? '友链状态变更';
        $subject = sprintf('[%s] 友链已进入%s分组：%s', $site_name, $label, $bookmark->link_name);
        if ($type === 'lost') {
            $rule = dream2_mxin_link_lost_rule_text();
        } elseif ($type === 'one_way') {
            $rule = dream2_mxin_link_one_way_rule_text();
        } else {
            $rule = dream2_mxin_link_lost_rule_text();
        }
        $failure_count = $type === 'one_way'
            ? absint($verification['backlink_failures'] ?? 0)
            : absint($verification['access_failures'] ?? 0);
        $message = dream2_mxin_mail_template(
            '友链已进入' . $label . '分组',
            $type === 'one_way'
                ? '对方站点可以访问，但反链页异常或未找到本站链接。'
                : '对方站点访问检测触发了分组迁移规则。',
            array(
                array('label' => '友链名称', 'value' => $bookmark->link_name),
                array('label' => '友链地址', 'value' => $bookmark->link_url),
                array('label' => '目标分组', 'value' => $label),
                array('label' => '触发规则', 'value' => $rule),
                array('label' => '检测结果', 'value' => $verification['message'] ?? ''),
                array('label' => '检测时间', 'value' => $verification['checked_at'] ?? current_time('mysql')),
                array('label' => '连续失败', 'value' => $failure_count . ' 次'),
            ),
            '访问友链',
            $bookmark->link_url
        );
    }

    return dream2_mxin_send_logged_mail(
        $email,
        $subject,
        $message,
        $headers,
        array(
            'channel'   => 'link',
            'mail_type' => $type,
            'source_id' => (int) $bookmark->link_id,
            'attempt'   => 1,
        )
    );
}

function dream2_mxin_process_one_way_lifecycle($bookmark, $verification) {
    $one_way_id = absint(dream2_get('link_one_way_category', dream2_mxin_default_link_category_id('单向友链')));
    if (!$one_way_id) {
        return false;
    }
    $term_ids = wp_get_object_terms((int) $bookmark->link_id, 'link_category', array('fields' => 'ids'));
    if (is_wp_error($term_ids) || !in_array($one_way_id, array_map('intval', $term_ids), true)) {
        return false;
    }

    $extra = dream2_mxin_link_extra($bookmark);
    $verification = array_merge($extra['verification'], is_array($verification) ? $verification : array());
    if (empty($verification['one_way_entered_at'])) {
        $verification['one_way_entered_at'] = current_time('mysql');
        $verification['one_way_notified_at'] = '';
    }
    $entered_at = strtotime($verification['one_way_entered_at']);
    if (!$entered_at) {
        return false;
    }
    $elapsed = current_time('timestamp') - $entered_at;
    $notice_days = max(1, absint(dream2_get('link_one_way_notice_days', 1)));
    $delete_days = max($notice_days, absint(dream2_get('link_one_way_delete_days', 7)));

    if ($elapsed >= $notice_days * DAY_IN_SECONDS && empty($verification['one_way_notified_at'])) {
        if (dream2_mxin_send_link_group_notification($bookmark, 'one_way_reminder', $verification)) {
            $verification['one_way_notified_at'] = current_time('mysql');
        }
    }

    global $wpdb;
    $wpdb->update(
        $wpdb->links,
        array('link_notes' => dream2_mxin_link_notes($extra['admin_email'], $extra['backlink_url'], $verification)),
        array('link_id' => (int) $bookmark->link_id),
        array('%s'),
        array('%d')
    );
    clean_bookmark_cache((int) $bookmark->link_id);

    if ($elapsed < $delete_days * DAY_IN_SECONDS) {
        return false;
    }
    require_once ABSPATH . 'wp-admin/includes/bookmark.php';
    $deleted = (bool) wp_delete_link((int) $bookmark->link_id);
    if ($deleted) {
        dream2_mxin_send_link_group_notification($bookmark, 'one_way_deleted', $verification);
    }
    return $deleted;
}

function dream2_mxin_store_link_verification(
    $bookmark,
    $result,
    $allow_auto_move = false,
    $advance_counters = true
) {
    $extra = dream2_mxin_link_extra($bookmark);
    $verification = dream2_mxin_prepare_link_verification(
        $extra['verification'],
        $result,
        $advance_counters
    );
    $move_to_id = 0;
    $move_type = '';
    $access_failed = $verification['access'] === 'error';
    $backlink_failed = !$access_failed
        && in_array($verification['backlink'], array('missing', 'error'), true);
    $friend_id = absint(dream2_get('link_friend_category', dream2_mxin_default_link_category_id('友情链接')));
    $one_way_id = absint(dream2_get('link_one_way_category', dream2_mxin_default_link_category_id('单向友链')));
    $lost_id = absint(dream2_get('link_lost_category', dream2_mxin_default_link_category_id('失联博客')));
    $term_ids = wp_get_object_terms((int) $bookmark->link_id, 'link_category', array('fields' => 'ids'));
    $term_ids = is_wp_error($term_ids) ? array() : array_map('intval', $term_ids);
    $is_one_way = $one_way_id && in_array($one_way_id, $term_ids, true);
    $is_lost = $lost_id && in_array($lost_id, $term_ids, true);
    $friend_group = dream2_mxin_link_group_name('link_friend_category', '友情链接');
    $one_way_group = dream2_mxin_link_group_name('link_one_way_category', '单向友链');
    $lost_group = dream2_mxin_link_group_name('link_lost_category', '失联博客');

    $auto_move_one_way = dream2_enabled('link_auto_move_one_way');
    $auto_move_lost = dream2_enabled('link_auto_move_lost');
    if ($allow_auto_move) {
        if (
            !$access_failed
            && !$backlink_failed
            && (
                ($is_one_way && $auto_move_one_way)
                || ($is_lost && $auto_move_lost)
            )
            && $friend_id
        ) {
            $move_to_id = $friend_id;
            $move_type = 'normal';
        } elseif (
            $auto_move_lost
            && $access_failed
            && $verification['access_failures'] >= max(
                1,
                absint(dream2_get('link_check_failure_threshold', 3))
            )
        ) {
            if (!$is_lost && $lost_id) {
                $move_to_id = $lost_id;
                $move_type = 'lost';
            }
        } elseif ($auto_move_one_way && $backlink_failed && !$is_one_way && $one_way_id) {
            $backlink_confirmed = $verification['backlink_failures']
                >= max(1, absint(dream2_get('link_check_failure_threshold', 3)));
            if ($backlink_confirmed || $is_lost) {
                $move_to_id = $one_way_id;
                $move_type = 'one_way';
            }
        }
    }
    global $wpdb;
    $save_notes = function () use ($wpdb, $bookmark, $extra, &$verification) {
        $wpdb->update(
            $wpdb->links,
            array('link_notes' => dream2_mxin_link_notes($extra['admin_email'], $extra['backlink_url'], $verification)),
            array('link_id' => (int) $bookmark->link_id),
            array('%s'),
            array('%d')
        );
        clean_bookmark_cache((int) $bookmark->link_id);
    };
    $save_notes();
    if (
        $verification['access'] === 'success'
        && !empty($bookmark->link_image)
        && function_exists('dream2_mxin_avatar_privacy_enabled')
        && dream2_mxin_avatar_privacy_enabled()
    ) {
        dream2_mxin_avatar_cache_schedule(
            'friend',
            $bookmark->link_id . '|' . $bookmark->link_image,
            $bookmark->link_image
        );
    }
    if ($move_to_id) {
        $moved = wp_set_object_terms((int) $bookmark->link_id, array($move_to_id), 'link_category', false);
        if (!is_wp_error($moved)) {
            $verification['moved_at'] = current_time('mysql');
            if ($move_type === 'normal') {
                $verification['message'] .= sprintf('；已恢复到%s分组', $friend_group);
                dream2_mxin_send_link_group_notification($bookmark, 'recovered', $verification);
                $verification['one_way_notified_at'] = '';
                $verification['one_way_entered_at'] = '';
                $verification['lost_notified_at'] = '';
            } elseif ($move_type === 'one_way') {
                $verification['message'] .= sprintf('；已移动到%s分组', $one_way_group);
                $verification['one_way_entered_at'] = current_time('mysql');
                $verification['one_way_notified_at'] = '';
                $verification['lost_notified_at'] = '';
            } elseif ($move_type === 'lost') {
                $verification['message'] .= sprintf('；已移动到%s分组', $lost_group);
                if (!$verification['lost_notified_at'] && dream2_mxin_send_link_group_notification($bookmark, 'lost', $verification)) {
                    $verification['lost_notified_at'] = current_time('mysql');
                }
                $verification['one_way_notified_at'] = '';
                $verification['one_way_entered_at'] = '';
            }
            $save_notes();
        }
    }
    return $verification;
}

function dream2_mxin_ajax_check_friend_link() {
    check_ajax_referer('dream2_link_check', 'nonce');
    if (!current_user_can('edit_theme_options')) {
        wp_send_json_error(array('message' => '权限不足。'), 403);
    }
    $link_id = isset($_POST['link_id']) ? absint($_POST['link_id']) : 0;
    $bookmark = $link_id ? get_bookmark($link_id) : null;
    if (!$bookmark) {
        wp_send_json_error(array('message' => '友链不存在。'), 404);
    }
    $term_ids = wp_get_object_terms($link_id, 'link_category', array('fields' => 'ids'));
    if (is_wp_error($term_ids) || !array_intersect(array_map('intval', $term_ids), dream2_mxin_friend_link_category_ids())) {
        wp_send_json_error(array('message' => '该链接不属于友链分组。'), 400);
    }
    $verification = dream2_mxin_store_link_verification(
        $bookmark,
        dream2_mxin_verify_friend_link($bookmark),
        false,
        false
    );
    wp_send_json_success(array_merge(array(
        'link_id' => $link_id,
        'name'    => $bookmark->link_name,
    ), $verification));
}
add_action('wp_ajax_dream2_check_friend_link', 'dream2_mxin_ajax_check_friend_link');

function dream2_mxin_link_check_interval_seconds() {
    $interval = dream2_get('link_check_interval', 'daily');
    if ($interval === 'three_days') return 3 * DAY_IN_SECONDS;
    if ($interval === 'weekly') return WEEK_IN_SECONDS;
    return DAY_IN_SECONDS;
}

function dream2_mxin_link_check_state() {
    $defaults = array(
        'queue'             => array(),
        'cycle_started_at'  => 0,
        'cycle_total'       => 0,
        'cycle_processed'   => 0,
        'cycle_failures'    => 0,
        'last_batch_at'     => 0,
        'last_completed_at' => 0,
    );
    $state = get_option('dream2_link_check_state', array());
    return array_merge($defaults, is_array($state) ? $state : array());
}

function dream2_mxin_save_link_check_state($state) {
    $state['queue'] = array_values(array_unique(array_filter(array_map('absint', $state['queue'] ?? array()))));
    update_option('dream2_link_check_state', $state, false);
}

function dream2_mxin_start_link_check_cycle($state) {
    $links = get_bookmarks(array(
        'category'       => implode(',', dream2_mxin_friend_link_category_ids()),
        'hide_invisible' => true,
        'orderby'        => 'name',
        'order'          => 'ASC',
    ));
    $state['queue'] = array_map('intval', wp_list_pluck($links, 'link_id'));
    $state['cycle_started_at'] = time();
    $state['cycle_total'] = count($state['queue']);
    $state['cycle_processed'] = 0;
    $state['cycle_failures'] = 0;
    return $state;
}

function dream2_mxin_run_scheduled_link_check($force = false) {
    if (!$force && !dream2_enabled('enable_auto_link_check')) {
        return array('status' => 'disabled', 'processed' => 0, 'remaining' => 0);
    }
    $lock_key = 'dream2_link_check_lock';
    $locked_at = (int) get_option($lock_key, 0);
    if ($locked_at && time() - $locked_at < 15 * MINUTE_IN_SECONDS) {
        return array('status' => 'locked', 'processed' => 0, 'remaining' => count(dream2_mxin_link_check_state()['queue']));
    }
    delete_option($lock_key);
    if (!add_option($lock_key, time(), '', false)) {
        return array('status' => 'locked', 'processed' => 0, 'remaining' => count(dream2_mxin_link_check_state()['queue']));
    }
    try {
        $state = dream2_mxin_link_check_state();
        if (!$state['queue']) {
            $due = !$state['last_completed_at'] || time() - (int) $state['last_completed_at'] >= dream2_mxin_link_check_interval_seconds();
            if (!$force && !$due) {
                return array('status' => 'not_due', 'processed' => 0, 'remaining' => 0);
            }
            $state = dream2_mxin_start_link_check_cycle($state);
            dream2_mxin_save_link_check_state($state);
        }
        $batch_size = min(10, max(1, absint(dream2_get('link_check_batch_size', 3))));
        $processed = 0;
        while ($state['queue'] && $processed < $batch_size) {
            $link_id = (int) $state['queue'][0];
            $bookmark = get_bookmark($link_id);
            if ($bookmark) {
                $verification = dream2_mxin_store_link_verification(
                    $bookmark,
                    dream2_mxin_verify_friend_link($bookmark),
                    true
                );
                $bookmark = get_bookmark($link_id);
                if ($bookmark) {
                    dream2_mxin_process_one_way_lifecycle($bookmark, $verification);
                }
                if (!empty($verification['confirmed_failure'])) {
                    $state['cycle_failures']++;
                }
            }
            array_shift($state['queue']);
            $state['cycle_processed']++;
            $state['last_batch_at'] = time();
            $processed++;
            dream2_mxin_save_link_check_state($state);
        }
        if (!$state['queue']) {
            $state['last_completed_at'] = time();
            dream2_mxin_save_link_check_state($state);
        }
        return array(
            'status'    => $state['queue'] ? 'batch_complete' : 'cycle_complete',
            'processed' => $processed,
            'remaining' => count($state['queue']),
            'failures'  => (int) $state['cycle_failures'],
        );
    } finally {
        delete_option($lock_key);
    }
}

function dream2_mxin_link_check_cron_dispatch() {
    dream2_mxin_run_scheduled_link_check(false);
}
add_action('dream2_mxin_link_check_cron', 'dream2_mxin_link_check_cron_dispatch');

function dream2_mxin_link_check_cron_schedules($schedules) {
    $schedules['dream2_five_minutes'] = array(
        'interval' => 5 * MINUTE_IN_SECONDS,
        'display'  => '每 5 分钟',
    );
    return $schedules;
}
add_filter('cron_schedules', 'dream2_mxin_link_check_cron_schedules');

function dream2_mxin_sync_link_check_schedule() {
    if (dream2_enabled('enable_auto_link_check')) {
        if (!wp_next_scheduled('dream2_mxin_link_check_cron')) {
            wp_schedule_event(time() + MINUTE_IN_SECONDS, 'dream2_five_minutes', 'dream2_mxin_link_check_cron');
        }
    } else {
        wp_clear_scheduled_hook('dream2_mxin_link_check_cron');
    }
}
add_action('init', 'dream2_mxin_sync_link_check_schedule', 20);
add_action('switch_theme', function () {
    wp_clear_scheduled_hook('dream2_mxin_link_check_cron');
});

function dream2_mxin_link_manager_url($message = '') {
    $url = admin_url('admin.php?page=dream2-link-manager');
    return $message ? add_query_arg('dream2_link_message', $message, $url) : $url;
}

function dream2_mxin_system_cron_command() {
    $cron_url = home_url('/wp-cron.php?doing_wp_cron');
    if (!$cron_url) {
        $cron_url = 'https://example.com/wp-cron.php?doing_wp_cron';
    }

    return '*/5 * * * * curl -fsS ' . escapeshellarg($cron_url) . ' >/dev/null 2>&1';
}

// WordPress hides the native Links menu unless a theme or plugin enables it.
add_filter('pre_option_link_manager_enabled', '__return_true');

function dream2_mxin_link_takeover_enabled() {
    return dream2_enabled('link_takeover_categories', true);
}

function dream2_mxin_is_native_links_admin() {
    if (!is_admin()) {
        return false;
    }
    global $pagenow;
    if (in_array($pagenow, array('link-manager.php', 'link-add.php', 'link.php'), true)) {
        return true;
    }
    if (in_array($pagenow, array('edit-tags.php', 'term.php'), true)) {
        return isset($_GET['taxonomy']) && wp_unslash($_GET['taxonomy']) === 'link_category';
    }
    return false;
}

function dream2_mxin_native_hidden_link_category_ids() {
    return dream2_mxin_link_takeover_enabled() ? dream2_mxin_friend_link_category_ids() : array();
}

function dream2_mxin_filter_native_link_bookmarks($bookmarks, $args) {
    if (!dream2_mxin_is_native_links_admin()) {
        return $bookmarks;
    }
    $hidden_ids = dream2_mxin_native_hidden_link_category_ids();
    if (!$hidden_ids) {
        return $bookmarks;
    }
    $requested_ids = !empty($args['category']) ? wp_parse_id_list($args['category']) : array();
    if ($requested_ids && array_intersect($requested_ids, $hidden_ids)) {
        return array();
    }
    return array_values(array_filter($bookmarks, function ($bookmark) use ($hidden_ids) {
        $term_ids = wp_get_object_terms((int) $bookmark->link_id, 'link_category', array('fields' => 'ids'));
        return is_wp_error($term_ids) || !array_intersect(array_map('intval', $term_ids), $hidden_ids);
    }));
}
add_filter('get_bookmarks', 'dream2_mxin_filter_native_link_bookmarks', 10, 2);

function dream2_mxin_filter_native_link_terms($terms, $taxonomies) {
    if (!dream2_mxin_is_native_links_admin() || !in_array('link_category', (array) $taxonomies, true)) {
        return $terms;
    }
    $hidden_ids = dream2_mxin_native_hidden_link_category_ids();
    if (!$hidden_ids || !is_array($terms)) {
        return $terms;
    }
    return array_values(array_filter($terms, function ($term) use ($hidden_ids) {
        return !isset($term->term_id) || !in_array((int) $term->term_id, $hidden_ids, true);
    }));
}
add_filter('get_terms', 'dream2_mxin_filter_native_link_terms', 10, 2);

function dream2_mxin_handle_link_manager_action() {
    if (empty($_POST['dream2_link_manager_action'])) {
        return;
    }
    if (!current_user_can('edit_theme_options')) {
        wp_die(esc_html__('权限不足。', 'dream2-mxin'));
    }
    check_admin_referer('dream2_link_manager');

    require_once ABSPATH . 'wp-admin/includes/bookmark.php';

    $action = sanitize_key(wp_unslash($_POST['dream2_link_manager_action']));
    if ($action === 'delete_link') {
        $link_id = isset($_POST['link_id']) ? absint($_POST['link_id']) : 0;
        if ($link_id) {
            wp_delete_link($link_id);
        }
        wp_safe_redirect(dream2_mxin_link_manager_url('deleted'));
        exit;
    }

    if ($action === 'sort_groups') {
        $friend_term_ids = array_map('intval', wp_list_pluck(dream2_mxin_friend_link_terms(), 'term_id'));
        $order = isset($_POST['link_category_order']) && is_array($_POST['link_category_order']) ? wp_unslash($_POST['link_category_order']) : array();
        $order = array_values(array_filter(array_map('absint', $order), function ($term_id) use ($friend_term_ids) {
            return in_array($term_id, $friend_term_ids, true);
        }));
        foreach ($friend_term_ids as $term_id) {
            if (!in_array($term_id, $order, true)) {
                $order[] = $term_id;
            }
        }
        update_option('dream2_link_category_order', $order, false);
        wp_safe_redirect(dream2_mxin_link_manager_url('saved'));
        exit;
    }

    if ($action === 'save_category') {
        $category_name = isset($_POST['link_category_name']) ? sanitize_text_field(wp_unslash($_POST['link_category_name'])) : '';
        if ($category_name === '') {
            wp_safe_redirect(dream2_mxin_link_manager_url('category_missing'));
            exit;
        }

        $term_id = dream2_mxin_find_link_category_id($category_name);
        if (!$term_id) {
            $result = wp_insert_term($category_name, 'link_category');
            if (is_wp_error($result)) {
                wp_safe_redirect(dream2_mxin_link_manager_url('category_error'));
                exit;
            }
            $term_id = (int) $result['term_id'];
        }

        dream2_mxin_add_extra_link_category_id($term_id);
        dream2_mxin_add_link_category_to_order($term_id);
        wp_safe_redirect(dream2_mxin_link_manager_url('category_saved'));
        exit;
    }

    if ($action !== 'save_link') {
        return;
    }

    $link_id = isset($_POST['link_id']) ? absint($_POST['link_id']) : 0;
    $link_name = isset($_POST['link_name']) ? sanitize_text_field(wp_unslash($_POST['link_name'])) : '';
    $link_url = isset($_POST['link_url']) ? esc_url_raw(wp_unslash($_POST['link_url'])) : '';
    if ($link_name === '' || $link_url === '') {
        wp_safe_redirect(dream2_mxin_link_manager_url('missing'));
        exit;
    }

    $friend_term_ids = array_map('intval', wp_list_pluck(dream2_mxin_friend_link_terms(), 'term_id'));
    $link_category = isset($_POST['link_category']) ? absint($_POST['link_category']) : 0;
    if (!in_array($link_category, $friend_term_ids, true)) {
        $link_category = $friend_term_ids[0] ?? 0;
    }
    $admin_email = isset($_POST['admin_email']) ? sanitize_email(wp_unslash($_POST['admin_email'])) : '';
    $backlink_url = isset($_POST['backlink_url']) ? esc_url_raw(wp_unslash($_POST['backlink_url'])) : '';
    $verification = array();
    $previous_category_ids = array();
    if ($link_id) {
        $existing_bookmark = get_bookmark($link_id);
        if ($existing_bookmark) {
            $previous_category_ids = wp_get_object_terms($link_id, 'link_category', array('fields' => 'ids'));
            $previous_category_ids = is_wp_error($previous_category_ids) ? array() : array_map('intval', $previous_category_ids);
            $existing_extra = dream2_mxin_link_extra($existing_bookmark);
            $same_check_targets = esc_url_raw($existing_bookmark->link_url) === $link_url
                && esc_url_raw($existing_extra['backlink_url']) === $backlink_url;
            if ($same_check_targets) {
                $verification = $existing_extra['verification'];
            }
        }
    }
    $one_way_id = absint(dream2_get('link_one_way_category', dream2_mxin_default_link_category_id('单向友链')));
    $was_one_way = $one_way_id && in_array($one_way_id, $previous_category_ids, true);
    $is_one_way = $one_way_id && $link_category === $one_way_id;
    if ($is_one_way && !$was_one_way) {
        $verification = array_merge(dream2_mxin_link_extra_defaults()['verification'], $verification);
        $verification['one_way_entered_at'] = current_time('mysql');
        $verification['one_way_notified_at'] = '';
    } elseif (!$is_one_way) {
        $verification['one_way_entered_at'] = '';
        $verification['one_way_notified_at'] = '';
    }
    $linkdata = array(
        'link_name'        => $link_name,
        'link_url'         => $link_url,
        'link_description' => isset($_POST['link_description']) ? sanitize_textarea_field(wp_unslash($_POST['link_description'])) : '',
        'link_image'       => isset($_POST['link_image']) ? esc_url_raw(wp_unslash($_POST['link_image'])) : '',
        'link_target'      => '_blank',
        'link_visible'     => 'Y',
        'link_notes'       => dream2_mxin_link_notes(
            $admin_email,
            $backlink_url,
            $verification
        ),
    );
    $linkdata['link_category'] = $link_category ? array($link_category) : array();
    if ($link_id) {
        $linkdata['link_id'] = $link_id;
        $result = wp_update_link($linkdata);
    } else {
        $result = wp_insert_link($linkdata, true);
    }

    wp_safe_redirect(dream2_mxin_link_manager_url(is_wp_error($result) ? 'error' : 'saved'));
    exit;
}

function dream2_mxin_site_icon_size_choices() {
    $choices = array('wordpress' => '跟随 WordPress（32 × 32）');
    $site_icon_id = absint(get_option('site_icon'));
    if (!$site_icon_id) {
        return $choices;
    }

    $metadata = wp_get_attachment_metadata($site_icon_id);
    if (!is_array($metadata)) {
        return $choices;
    }

    $dimensions = array();
    $full_width = absint($metadata['width'] ?? 0);
    $full_height = absint($metadata['height'] ?? 0);
    if ($full_width > 0 && $full_width === $full_height) {
        $dimensions[$full_width] = true;
    }

    foreach ((array) ($metadata['sizes'] ?? array()) as $size) {
        $width = absint($size['width'] ?? 0);
        $height = absint($size['height'] ?? 0);
        if ($width > 0 && $width === $height) {
            $dimensions[$width] = true;
        }
    }

    ksort($dimensions, SORT_NUMERIC);
    foreach (array_keys($dimensions) as $dimension) {
        $choices['size-' . $dimension] = sprintf('%1$d × %1$d', $dimension);
    }

    return $choices;
}

function dream2_mxin_default_ai_search_prompt() {
    return '用简洁、准确、自然的中文回答用户问题。涉及站内事实时，只依据本次提供的公开站内资料，不要编造。只有资料能直接回答问题时才引用相关来源，用 [1]、[2] 等编号；需要时才列要点。资料不足、资料与问题无关或问题涉及无法访问的本地环境时，只用一句话说明无法确认，不列要点、不提无关资料、不附参考来源。输出纯文本，不使用 Markdown 标题、加粗、代码围栏或链接语法，回答不超过 600 个汉字。';
}

function dream2_mxin_default_ai_summary_prompt() {
    return '概括用户提供的文章，只提炼文章中的主要信息，不添加文章外的事实。用自然中文写一段不超过 180 个汉字的简短总结；只输出纯文本，不使用标题、列表、Markdown 或引文。';
}

function dream2_mxin_ai_feature_prompt_value($name, $value) {
    $defaults = array(
        'ai_search_prompt' => array('prompt' => dream2_mxin_default_ai_search_prompt(), 'previous_hash' => '29f51afa6951987e86570b2edcb05870e8f75ca1f836daea521600f1d0dd44ec'),
        'ai_summary_prompt' => array('prompt' => dream2_mxin_default_ai_summary_prompt(), 'previous_hash' => 'fc2b5ff3a018251e6babfe633ec0c7fd41eb9d7b333c7b92a1dbb76e6f63098d'),
    );
    if (!isset($defaults[$name])) return '';
    $value = is_scalar($value) ? trim((string) $value) : '';
    $previous_search_prompt = '用简洁、准确、自然的中文回答用户问题。涉及站内事实时，只依据本次提供的公开站内资料；资料不足就明确说明，不要编造。引用资料时使用 [1]、[2] 等来源编号。输出纯文本，不使用 Markdown 标题、加粗、代码围栏或链接语法。回答不超过 600 个汉字，先给直接结论，再列出不超过 6 个要点，并确保结尾完整。';
    return $value === '' || hash_equals($defaults[$name]['previous_hash'], hash('sha256', $value))
        || ($name === 'ai_search_prompt' && hash_equals(hash('sha256', $previous_search_prompt), hash('sha256', $value)))
        ? $defaults[$name]['prompt']
        : $value;
}

function dream2_mxin_settings_registry() {
    static $registry;
    if (isset($registry)) return $registry;

    $definitions = array(
        'site' => 'site_favicon_size|站点图标尺寸,document_hidden_title|离屏文案（离开）,document_visible_title|离屏文案（回来）,copy_explain|拷贝说明,record_number|备案号,record_number_moe|萌备号,record_number_ps|公安备案号,website_time|建站时间',
        'appearance' => 'theme_style|主题风格,default_theme|默认主题模式,theme_color|明亮模式主题色,night_theme_color|黑暗模式主题色,font_preset|博客字体,web_font|自定义字体 CSS 链接,custom_font|自定义字体名称,night_logo|黑暗模式 Logo,enable_image_bg|开启博客背景图,card_opacity|卡片透明度,background_image_opacity|背景图透明度,background_pc|明亮模式 PC 背景图,background_mobile|明亮模式移动端背景图,night_background_pc|黑暗模式 PC 背景图,night_background_mobile|黑暗模式移动端背景图,cursor_style|鼠标风格,cursor_move|鼠标移动特效,cursor_click|鼠标点击特效,effects_lantern_mode|灯笼特效,effects_sakura_mode|樱花特效,effects_snowflake_mode|雪花特效,effects_universe_mode|宇宙星空特效,effects_circle_magic_mode|上升圆点特效,enable_gray_mode|灰色模式',
        'home_layout' => 'index_inform|首页通知,enable_banner|开启博客横幅大图,banner_image|横幅背景图,banner_description|横幅文字描述,sidebar_column|博客布局方式,carousel_options|首页大图轮播选项,module_options|模块化布局选项,left_sidebar_sticky|左侧边栏悬浮,right_sidebar_sticky|右侧边栏悬浮',
        'content_page' => 'default_thumbnail|默认文章封面图,top_thumbnail_mode|置顶文章封面模式,thumbnail_mode|文章列表封面模式,drawer_toc|侧边抽屉式目录,code_pretty|代码块高亮主题,code_fold_line|代码块折叠行数（0-500）,img_fold_height|正文长图折叠高度（0-3000px）,show_img_name|显示图片名称,invalid_tips_day|文章失效提示天数,enable_katex|KaTeX 公式支持,enable_copyright|开启文章版权声明,enable_post_share|开启文章分享,enable_archivers_route|启用文章归档页,archivers_route_slug|文章归档路径,enable_friends_stats|朋友圈统计信息',
        'communication' => 'enable_private_comment|允许私密评论,avatar_privacy|头像隐私保护,comment_emoji_groups|评论表情分组,enable_smtp|启用 SMTP,smtp_host|SMTP 主机,smtp_port|SMTP 端口,smtp_secure|加密方式,smtp_username|SMTP 账号,smtp_password|SMTP 密码,smtp_from_email|发件邮箱,smtp_from_name|发件名称,email_notify_post_author|新评论提醒,email_notify_moderator|待审核评论提醒,email_notify_reply|回评提醒,link_notify_recovered|友链博客恢复提醒,link_notify_one_way_reminder|单向友链提醒通知,link_notify_one_way_deleted|单向友链删除通知,link_notify_lost|友链博客失联提醒',
        'link' => 'link_takeover_categories|梦屿接管友链分类,link_friend_category|友情链接分类,link_one_way_category|单向友链分类,link_lost_category|失联博客分类,enable_auto_link_check|自动检测友链,link_check_interval|自动检测周期,link_check_batch_size|每批检测数量,link_check_failure_threshold|连续失败阈值,link_auto_move_one_way|自动移动单向友链,link_auto_move_lost|自动移动失联博客,link_one_way_notice_days|单向友链提醒天数,link_one_way_delete_days|单向友链删除天数,links_thumbnail|友链页面封面图,links_default_avatar|友链默认 Logo,show_exchange_info|显示友链交换信息,links_blogger_name|交换信息名称,links_blogger_url|交换信息地址,links_blogger_avatar|交换信息 Logo,links_blogger_description|交换信息描述,links_info|友链补充信息,link_enable_comment|友链页面评论,enable_link_application|自助申请友链,link_application_blacklist|友链申请黑名单',
        'performance' => 'load_progress|加载进度条,enable_sw|Service Worker 优化,enable_pjax|PJAX 加载,enable_busuanzi|不蒜子访客统计,enable_baidu_push|百度 URL 自动推送,enable_toutiao_push|头条 URL 自动推送',
        'ai' => 'ai_provider|API 服务,ai_base_url|Base URL,ai_api_key|API Key,ai_model|模型,ai_system_prompt|角色卡提示词,ai_user_id|关联账户,enable_ai_search|启用 AI 站内问答,ai_search_prompt|站内问答提示词,enable_article_summary|启用文章内 AI 总结,ai_summary_prompt|文章总结提示词',
        'advanced' => 'external_css|外部 CSS 链接,inline_css|内嵌 CSS,external_js_head|外部 JS（head）,inline_js_head|内嵌 JS（head）,external_js_body|外部 JS（body）,inline_js_body|内嵌 JS（body）',
    );
    $toggles = array('enable_image_bg','enable_banner','drawer_toc','show_img_name','enable_katex','enable_copyright','enable_post_share','enable_color_character','show_ad_tag','ad_tag_close','enable_tag_color','enable_tagcloud_color','enable_archivers_route','link_takeover_categories','show_exchange_info','link_enable_comment','enable_link_application','enable_friends_stats','enable_auto_link_check','link_auto_move_one_way','link_auto_move_lost','link_notify_recovered','link_notify_one_way_reminder','link_notify_one_way_deleted','link_notify_lost','enable_smtp','email_notify_post_author','email_notify_moderator','email_notify_reply','avatar_privacy','enable_sw','enable_pjax','enable_gray_mode','enable_busuanzi','enable_baidu_push','enable_toutiao_push','enable_private_comment','enable_ai_search','enable_article_summary');
    $images = array('night_logo','background_pc','background_mobile','night_background_pc','night_background_mobile','banner_image','default_thumbnail','love_oneself_avatar','love_opposite_avatar','ad_image','links_thumbnail','links_default_avatar','links_blogger_avatar');
    $textareas = array('copy_explain','color_character','notice_content','music_config','ad_custom_code','links_info','link_application_blacklist','ai_system_prompt','ai_search_prompt','ai_summary_prompt','external_css','inline_css','external_js_head','inline_js_head','external_js_body','inline_js_body');
    $dates = array('website_time');
    $urls = array('links_blogger_url','hitokoto_custom_url','ai_base_url');
    $emails = array('smtp_from_email');
    $numbers = array(
        'code_fold_line'=>array('min'=>0,'max'=>500,'step'=>1),
        'img_fold_height'=>array('min'=>0,'max'=>3000,'step'=>1),
        'invalid_tips_day'=>array('min'=>0,'step'=>1),
        'link_check_batch_size'=>array('min'=>1,'max'=>10,'step'=>1),
        'link_check_failure_threshold'=>array('min'=>1,'max'=>10,'step'=>1),
        'link_one_way_notice_days'=>array('min'=>1,'max'=>90,'step'=>1),
        'link_one_way_delete_days'=>array('min'=>1,'max'=>365,'step'=>1),
        'smtp_port'=>array('min'=>1,'max'=>65535,'step'=>1),
    );
    $ranges = array(
        'card_opacity'=>array('min'=>0,'max'=>100,'step'=>5,'suffix'=>'%'),
        'background_image_opacity'=>array('min'=>0,'max'=>100,'step'=>5,'suffix'=>'%'),
    );
    $passwords = array('smtp_password','ai_api_key');
    $user_selects = array('ai_user_id');
    $emoji_groups = array('comment_emoji_groups');
    $repeaters = array('carousel_options','module_options','custom_stats','custom_options');
    $defaults = array(
        'site_favicon_size'=>'wordpress','document_hidden_title'=>'你别走呀 (´；ω；`)','document_visible_title'=>'欢迎回来 (｡･ω･｡)','enable_image_bg'=>'1','card_opacity'=>60,'background_image_opacity'=>60,'enable_banner'=>'1','banner_description'=>get_bloginfo('description'),'theme_style'=>'default','default_theme'=>'system','theme_color'=>'#50bfff','night_theme_color'=>'#5d93db','font_preset'=>'system','sidebar_column'=>'only-right','left_sidebar_sticky'=>'top','right_sidebar_sticky'=>'top','thumbnail_mode'=>'default','top_thumbnail_mode'=>'back','drawer_toc'=>'1','code_pretty'=>'atom-one-light','code_fold_line'=>20,'img_fold_height'=>400,'show_img_name'=>'1','invalid_tips_day'=>99999999,'enable_katex'=>'0','enable_copyright'=>'1','enable_post_share'=>'1','enable_private_comment'=>'1',
        'custom_stats'=>array(
            array('type'=>'post'),
            array('type'=>'category'),
            array('type'=>'tag'),
        ),
        'custom_options'=>array(),
        'notice_show_mode'=>'default','recent_posts_num'=>'5','recent_comments_num'=>'5','categories_num'=>'10','tags_num'=>'18','tagcloud_num'=>'32','enable_hitokoto'=>'0','hitokoto_category'=>'all','enable_archivers_route'=>'1','archivers_route_slug'=>'archivers','link_takeover_categories'=>'1','link_friend_category'=>dream2_mxin_default_link_category_id('友情链接'),'link_one_way_category'=>dream2_mxin_default_link_category_id('单向友链'),'link_lost_category'=>dream2_mxin_default_link_category_id('失联博客'),'enable_auto_link_check'=>'0','link_check_interval'=>'daily','link_check_batch_size'=>3,'link_check_failure_threshold'=>3,'link_auto_move_one_way'=>'0','link_auto_move_lost'=>'0','link_one_way_notice_days'=>1,'link_one_way_delete_days'=>7,'link_notify_recovered'=>'0','link_notify_one_way_reminder'=>'0','link_notify_one_way_deleted'=>'0','link_notify_lost'=>'0','enable_smtp'=>'0','smtp_port'=>587,'smtp_secure'=>'tls','smtp_from_email'=>get_option('admin_email'),'smtp_from_name'=>get_bloginfo('name'),'email_notify_post_author'=>'1','email_notify_moderator'=>'1','email_notify_reply'=>'0','link_enable_comment'=>'1','enable_link_application'=>'1','link_application_blacklist'=>'','show_exchange_info'=>'1','links_blogger_name'=>get_bloginfo('name'),'links_blogger_url'=>home_url('/'),'links_blogger_description'=>get_bloginfo('description'),'load_progress'=>'none','avatar_privacy'=>'1','cursor_style'=>'none','cursor_move'=>'none','cursor_click'=>'none','effects_lantern_mode'=>'none','effects_sakura_mode'=>'none','effects_snowflake_mode'=>'none','effects_universe_mode'=>'none','effects_circle_magic_mode'=>'none',
        'ai_provider'=>'openai','ai_base_url'=>'https://api.openai.com/v1','ai_model'=>'','ai_search_prompt'=>dream2_mxin_default_ai_search_prompt(),'ai_summary_prompt'=>dream2_mxin_default_ai_summary_prompt(),'enable_ai_search'=>'1','enable_article_summary'=>'1','ai_user_id'=>0
    );
    $selects = array(
        'site_favicon_size'=>dream2_mxin_site_icon_size_choices(),
        'ai_provider'=>array('openai'=>'OpenAI','deepseek'=>'DeepSeek','glm'=>'GLM','custom'=>'自定义兼容服务'),
        'load_progress'=>array('none'=>'关闭','left'=>'左侧展开','center'=>'居中展开'),
        'theme_style'=>array('default'=>'默认','clean'=>'简洁'),
        'default_theme'=>array('system'=>'跟随系统','light'=>'明亮','night'=>'黑暗'),
        'font_preset'=>array('system'=>'系统默认','lxgw_wenkai'=>'霞鹜文楷','noto_sans_sc'=>'思源黑体','custom'=>'自定义'),
        'sidebar_column'=>array('all'=>'三栏','only-left'=>'左侧栏','only-right'=>'右侧栏','module-left'=>'模块左栏','module-right'=>'模块右栏'),
        'thumbnail_mode'=>array('default'=>'默认模式','back'=>'背景图模式','small'=>'小图模式（左侧）','small-right'=>'小图模式（右侧）','small-alter'=>'小图模式（交替）','grid'=>'网格模式（强优先）'),
        'top_thumbnail_mode'=>array('default'=>'默认模式','back'=>'背景图模式','small'=>'小图模式（左侧）','small-right'=>'小图模式（右侧）','small-alter'=>'小图模式（交替）','fold'=>'折叠模式','grid'=>'网格模式（强优先）'),
        'code_pretty'=>dream2_mxin_highlight_theme_choices(),
        'notice_show_mode'=>array('default'=>'始终显示','index'=>'仅首页','close'=>'关闭'),
        'hitokoto_category'=>array('all'=>'全部','a'=>'动画','b'=>'漫画','c'=>'游戏','d'=>'文学','e'=>'原创','f'=>'来自网络','g'=>'其他','h'=>'影视','i'=>'诗词','j'=>'网易云','k'=>'哲学','l'=>'抖机灵'),
        'link_check_interval'=>array('daily'=>'每天','three_days'=>'每 3 天','weekly'=>'每周'),
        'smtp_secure'=>array('none'=>'不加密','tls'=>'TLS','ssl'=>'SSL'),
        'music_mode'=>array('none'=>'关闭','playlist'=>'网易云歌单','config'=>'自定义配置'),
        'ad_mode'=>array('none'=>'关闭','image'=>'图片','custom'=>'自定义代码'),
        'cursor_style'=>array('none'=>'关闭','OwO'=>'OwO','UwU'=>'UwU','breeze'=>'清风（深色）','mellow'=>'卡通圆润','water_01'=>'彩虹水滴（一）','water_02'=>'彩虹水滴（二）','horse'=>'彩虹小马','debris'=>'彩色碎片','overwatch'=>'守望先锋','rainbow_rain'=>'彩虹云雨','marry'=>'小樱茉莉','black_cat'=>'黑色小猫','music_cat_01'=>'音乐小猫（一）','music_cat_02'=>'音乐小猫（二）'),
        'cursor_move'=>array('none'=>'关闭','bubbleCursor'=>'气泡跟随','emojiCursor'=>'表情包跟随','springyEmojiCursor'=>'弹性表情包跟随','fairyDustCursor'=>'仙女棒效果','snowflakeCursor'=>'雪花跟随','followingDotCursor'=>'圆点跟随','ghostCursor'=>'移动残影（疏）','trailingCursor'=>'移动残影（密）'),
        'cursor_click'=>array('none'=>'关闭','firework'=>'烟花特效','granule'=>'粒子爆炸','prosperous'=>'富强民主','heart'=>'爱心特效'),
        'effects_lantern_mode'=>array('none'=>'关闭','day'=>'浅色模式','night'=>'黑暗模式','all'=>'全模式'),
        'effects_sakura_mode'=>array('none'=>'关闭','day'=>'浅色模式','night'=>'黑暗模式','all'=>'全模式'),
        'effects_snowflake_mode'=>array('none'=>'关闭','day'=>'浅色模式','night'=>'黑暗模式','all'=>'全模式'),
        'effects_universe_mode'=>array('none'=>'关闭','day'=>'浅色模式','night'=>'黑暗模式','all'=>'全模式'),
        'effects_circle_magic_mode'=>array('none'=>'关闭','day'=>'浅色模式','night'=>'黑暗模式','all'=>'全模式'),
    );
    $radios = array(
        'left_sidebar_sticky'=>array('top'=>'固定顶部','bottom'=>'固定底部','none'=>'不悬浮'),
        'right_sidebar_sticky'=>array('top'=>'固定顶部','bottom'=>'固定底部','none'=>'不悬浮'),
        'enable_hitokoto'=>array('0'=>'关闭','official'=>'官方','custom'=>'自定义'),
    );
    foreach ($toggles as $name) {
        $radios[$name] = array('1'=>'开启','0'=>'关闭');
    }
    $registry = array();
    foreach ($definitions as $group => $list) {
        foreach (explode(',', $list) as $definition) {
            list($name, $label) = explode('|', $definition, 2);
            $type = in_array($name, $user_selects, true) ? 'user_select' : (in_array($name, $emoji_groups, true) ? 'emoji_groups' : (in_array($name, $repeaters, true) ? 'repeater' : (in_array($name, $images, true) ? 'image' : (in_array($name, $textareas, true) ? 'textarea' : (in_array($name, $dates, true) ? 'date' : (in_array($name, $urls, true) ? 'url' : (in_array($name, $emails, true) ? 'email' : (in_array($name, $passwords, true) ? 'password' : (isset($ranges[$name]) ? 'range' : (isset($numbers[$name]) ? 'number' : (isset($radios[$name]) ? 'radio' : (isset($selects[$name]) ? 'select' : (in_array($name, array('theme_color','night_theme_color'), true) ? 'color' : 'text')))))))))))));
            $registry[$name] = array('group'=>$group,'label'=>$label,'type'=>$type,'default'=>$defaults[$name] ?? ($type === 'repeater' ? array() : ''),'choices'=>$selects[$name] ?? ($radios[$name] ?? array()),'attributes'=>$ranges[$name] ?? ($numbers[$name] ?? array()),'boolean'=>in_array($name,$toggles,true));
        }
    }
    return $registry;
}

function dream2_get($key, $fallback = null) {
    $registry = dream2_mxin_settings_registry();
    $options = get_option('dream2_options', array());
    if (array_key_exists($key, $options)) {
        if ($key === 'load_progress') return dream2_mxin_normalize_load_progress($options[$key]);
        if ($key === 'enable_hitokoto') return dream2_mxin_normalize_hitokoto_mode($options[$key]);
        if ($key === 'sidebar_column') return dream2_mxin_normalize_sidebar_column($options[$key]);
        return $options[$key];
    }
    if ($key === 'ai_provider') {
        return dream2_mxin_ai_provider_for_base_url($options['ai_base_url'] ?? '');
    }
    $legacy = array('document_hidden_title'=>'dream2_hidden_title','document_visible_title'=>'dream2_visible_title','index_inform'=>'dream2_index_notice','record_number'=>'dream2_record_number','record_number_moe'=>'dream2_moe_record_number','record_number_ps'=>'dream2_public_record','website_time'=>'dream2_website_time','background_pc'=>'dream2_background_pc','background_mobile'=>'dream2_background_mobile','night_background_pc'=>'dream2_night_background_pc','banner_image'=>'dream2_banner_image','banner_description'=>'dream2_banner_description','theme_style'=>'dream2_theme_style','default_theme'=>'dream2_default_theme','theme_color'=>'dream2_theme_color','night_theme_color'=>'dream2_night_theme_color','sidebar_column'=>'dream2_layout','default_thumbnail'=>'dream2_default_thumbnail','thumbnail_mode'=>'dream2_thumbnail_mode','inline_css'=>'dream2_inline_css');
    $default = null !== $fallback ? $fallback : ($registry[$key]['default'] ?? '');
    $value = isset($legacy[$key]) ? get_theme_mod($legacy[$key], $default) : $default;
    if ($key === 'load_progress') return dream2_mxin_normalize_load_progress($value);
    if ($key === 'enable_hitokoto') return dream2_mxin_normalize_hitokoto_mode($value);
    if ($key === 'sidebar_column') return dream2_mxin_normalize_sidebar_column($value);
    return $value;
}

function dream2_mxin_selected_site_icon_size() {
    $selection = (string) dream2_get('site_favicon_size', 'wordpress');
    if ($selection === 'wordpress' || !preg_match('/^size-(\d+)$/', $selection, $matches)) {
        return 0;
    }

    $choices = dream2_mxin_site_icon_size_choices();
    return isset($choices[$selection]) ? absint($matches[1]) : 0;
}

function dream2_mxin_override_favicon() {
    $size = dream2_mxin_selected_site_icon_size();
    if (!$size) {
        return;
    }

    $url = get_site_icon_url($size);
    if (!$url) {
        return;
    }

    wp_redirect($url, 302, 'Dream2');
    exit;
}
add_action('do_favicon', 'dream2_mxin_override_favicon', 1);

function dream2_mxin_normalize_sidebar_column($value) {
    $legacy = array(
        'both'  => 'all',
        'left'  => 'only-left',
        'right' => 'only-right',
    );
    $value = sanitize_key((string) $value);
    if (isset($legacy[$value])) {
        $value = $legacy[$value];
    }
    return in_array($value, array('all', 'only-left', 'only-right', 'module-left', 'module-right', 'none'), true)
        ? $value
        : 'only-right';
}

function dream2_mxin_normalize_load_progress($value) {
    $value = (string) $value;
    if ($value === 'progress') {
        return 'left';
    }
    return in_array($value, array('none', 'left', 'center'), true) ? $value : 'none';
}

function dream2_mxin_normalize_hitokoto_mode($value) {
    $value = (string) $value;
    if ($value === '1' || $value === 'true' || $value === 'on') {
        return 'official';
    }
    return in_array($value, array('0', 'official', 'custom'), true) ? $value : '0';
}

function dream2_enabled($key, $fallback = false) {
    return filter_var(dream2_get($key, $fallback), FILTER_VALIDATE_BOOLEAN);
}

function dream2_mxin_mail_headers() {
    return array('Content-Type: text/html; charset=UTF-8');
}

function dream2_mxin_mail_theme_color($fallback = '#50bfff') {
    $color = sanitize_hex_color(dream2_get('theme_color', $fallback));
    return $color ?: $fallback;
}

function dream2_mxin_mail_rows($rows) {
    $html = '';
    foreach ($rows as $row) {
        $label = isset($row['label']) ? (string) $row['label'] : '';
        $value = isset($row['value']) ? (string) $row['value'] : '';
        if ($label === '' || $value === '') {
            continue;
        }
        $html .= '<tr>';
        $html .= '<td style="width:116px;padding:12px 14px;border-bottom:1px solid #edf0f5;color:#64748b;font-size:13px;vertical-align:top;">' . esc_html($label) . '</td>';
        $html .= '<td style="padding:12px 14px;border-bottom:1px solid #edf0f5;color:#111827;font-size:14px;line-height:1.75;vertical-align:top;">' . nl2br(esc_html($value)) . '</td>';
        $html .= '</tr>';
    }
    return $html;
}

function dream2_mxin_mail_template($title, $summary, $rows = array(), $action_text = '', $action_url = '', $secondary_actions = array()) {
    $site_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
    $theme = dream2_mxin_mail_theme_color();
    $night = dream2_mxin_mail_theme_color('#5d93db');
    $rows_html = dream2_mxin_mail_rows($rows);
    $action_html = '';
    if ($action_text !== '' && $action_url !== '') {
        $action_html = '<p style="margin:22px 0 0;"><a href="' . esc_url($action_url) . '" style="display:inline-block;padding:11px 20px;border-radius:999px;background:' . esc_attr($theme) . ';color:#fff;font-size:14px;font-weight:700;text-decoration:none;">' . esc_html($action_text) . '</a></p>';
    }
    if ($secondary_actions) {
        $cells = array();
        foreach ($secondary_actions as $action) {
            $text = isset($action['text']) ? (string) $action['text'] : '';
            $url = isset($action['url']) ? (string) $action['url'] : '';
            if ($text === '' || $url === '') {
                continue;
            }
            if (!empty($action['danger'])) {
                $background = '#fff1f2';
                $border = '#fecdd3';
                $color = '#dc2626';
            } elseif (!empty($action['primary'])) {
                $background = $theme;
                $border = $theme;
                $color = '#ffffff';
            } else {
                $background = '#f8fafc';
                $border = '#cbd5e1';
                $color = '#334155';
            }
            $cells[] = '<td style="padding:0 8px 0 0;">'
                . '<a href="' . esc_url($url) . '" style="display:inline-block;padding:9px 14px;border:1px solid ' . esc_attr($border) . ';border-radius:8px;background:' . esc_attr($background) . ';color:' . esc_attr($color) . ';font-size:13px;font-weight:700;line-height:1.2;text-decoration:none;">' . esc_html($text) . '</a>'
                . '</td>';
        }
        if ($cells) {
            $action_html .= '<div style="margin:' . ($action_html === '' ? '22px' : '14px') . ' 0 0;padding:14px 16px;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;">'
                . '<div style="margin:0 0 10px;color:#64748b;font-size:12px;font-weight:700;letter-spacing:.08em;">快捷处理</div>'
                . '<table role="presentation" cellspacing="0" cellpadding="0"><tr>' . implode('', $cells) . '</tr></table>'
                . '</div>';
        }
    }

    return '<!doctype html><html><body style="margin:0;padding:0;background:#f4f7fb;color:#111827;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,Microsoft YaHei,sans-serif;">'
        . '<div style="padding:28px 14px;">'
        . '<div style="max-width:680px;margin:0 auto;border-radius:18px;overflow:hidden;background:#fff;box-shadow:0 18px 48px rgba(15,23,42,.12);">'
        . '<div style="padding:24px 28px;background:linear-gradient(135deg,' . esc_attr($theme) . ',' . esc_attr($night) . ');color:#fff;">'
        . '<div style="font-size:12px;letter-spacing:.14em;text-transform:uppercase;opacity:.86;">Dream2 Notice</div>'
        . '<h1 style="margin:10px 0 0;color:#fff;font-size:24px;line-height:1.35;font-weight:750;">' . esc_html($title) . '</h1>'
        . '<p style="margin:10px 0 0;color:rgba(255,255,255,.88);font-size:14px;line-height:1.7;">' . esc_html($summary) . '</p>'
        . '</div>'
        . '<div style="padding:24px 28px 28px;">'
        . ($rows_html ? '<table role="presentation" cellspacing="0" cellpadding="0" style="width:100%;border-collapse:collapse;border:1px solid #edf0f5;border-radius:12px;overflow:hidden;">' . $rows_html . '</table>' : '')
        . $action_html
        . '<p style="margin:24px 0 0;color:#94a3b8;font-size:12px;line-height:1.7;">这封邮件由 ' . esc_html($site_name) . ' 的梦屿主题自动发送。</p>'
        . '</div></div></div></body></html>';
}

function dream2_mxin_link_interval_label() {
    $choices = dream2_mxin_settings_registry()['link_check_interval']['choices'] ?? array();
    $value = (string) dream2_get('link_check_interval', 'daily');
    return $choices[$value] ?? '每天';
}

function dream2_mxin_link_interval_days() {
    $value = (string) dream2_get('link_check_interval', 'daily');
    return array('daily' => 1, 'three_days' => 3, 'weekly' => 7)[$value] ?? 1;
}

function dream2_mxin_link_lost_rule_text() {
    $interval_days = dream2_mxin_link_interval_days();
    $threshold = max(1, absint(dream2_get('link_check_failure_threshold', 3)));
    return sprintf('站点连续访问失败 %d 次（按当前周期约 %d 天）', $threshold, $interval_days * $threshold);
}

function dream2_mxin_link_one_way_rule_text() {
    return '反链连续检测失败 ' . max(
        1,
        absint(dream2_get('link_check_failure_threshold', 3))
    ) . ' 次';
}

function dream2_mxin_smtp_enabled() {
    return dream2_enabled('enable_smtp') && trim((string) dream2_get('smtp_host')) !== '';
}

function dream2_mxin_smtp_from_email() {
    $email = sanitize_email(dream2_get('smtp_from_email', get_option('admin_email')));
    return $email ?: get_option('admin_email');
}

function dream2_mxin_smtp_from_name() {
    $name = sanitize_text_field(dream2_get('smtp_from_name', get_bloginfo('name')));
    return $name ?: get_bloginfo('name');
}

add_action('phpmailer_init', function ($phpmailer) {
    if (!dream2_mxin_smtp_enabled()) {
        return;
    }

    $phpmailer->isSMTP();
    $phpmailer->Host = trim((string) dream2_get('smtp_host'));
    $phpmailer->Port = max(1, min(65535, absint(dream2_get('smtp_port', 587))));
    $secure = (string) dream2_get('smtp_secure', 'tls');
    $phpmailer->SMTPSecure = $secure === 'none' ? '' : $secure;
    if ($secure === 'none') {
        $phpmailer->SMTPAutoTLS = false;
    }
    $username = (string) dream2_get('smtp_username');
    $password = (string) dream2_get('smtp_password');
    $phpmailer->SMTPAuth = $username !== '' || $password !== '';
    $phpmailer->Username = $username;
    $phpmailer->Password = $password;

    $from_email = dream2_mxin_smtp_from_email();
    $from_name = dream2_mxin_smtp_from_name();
    if ($from_email) {
        try {
            $phpmailer->setFrom($from_email, $from_name, false);
        } catch (Exception $e) {
            $phpmailer->From = $from_email;
            $phpmailer->FromName = $from_name;
        }
    }
});

add_filter('wp_mail_from', function ($email) {
    return dream2_mxin_smtp_enabled() ? dream2_mxin_smtp_from_email() : $email;
});

add_filter('wp_mail_from_name', function ($name) {
    return dream2_mxin_smtp_enabled() ? dream2_mxin_smtp_from_name() : $name;
});

add_filter('notify_post_author', '__return_false', 10, 2);
add_filter('notify_moderator', '__return_false', 10, 2);

function dream2_mxin_comment_mail_meta_key($type, $state = 'sent') {
    $map = array(
        'post_author' => '_dream2_mail_post_author',
        'moderator'   => '_dream2_mail_moderator',
        'reply'       => '_dream2_reply_notification',
    );
    if (empty($map[$type])) {
        return '';
    }
    return $map[$type] . '_' . $state;
}

function dream2_mxin_clear_comment_mail_queue_state($comment_id, $type, $clear_failed = false) {
    foreach (array('queued', 'attempts') as $state) {
        $key = dream2_mxin_comment_mail_meta_key($type, $state);
        if ($key) {
            delete_comment_meta($comment_id, $key);
        }
    }
    if ($clear_failed) {
        $failed_key = dream2_mxin_comment_mail_meta_key($type, 'failed');
        if ($failed_key) {
            delete_comment_meta($comment_id, $failed_key);
        }
    }
}

function dream2_mxin_retry_comment_mail($comment_id, $type) {
    $comment_id = absint($comment_id);
    $attempts_key = dream2_mxin_comment_mail_meta_key($type, 'attempts');
    $queued_key = dream2_mxin_comment_mail_meta_key($type, 'queued');
    $failed_key = dream2_mxin_comment_mail_meta_key($type, 'failed');
    if (!$attempts_key || !$queued_key || !$failed_key) {
        return false;
    }

    $attempts = absint(get_comment_meta($comment_id, $attempts_key, true)) + 1;
    update_comment_meta($comment_id, $attempts_key, $attempts);
    $retry_delays = array(5 * MINUTE_IN_SECONDS, 30 * MINUTE_IN_SECONDS);
    $args = array($comment_id, $type);
    $mail_context = dream2_mxin_comment_mail_log_context($comment_id, $type);

    if (isset($retry_delays[$attempts - 1])) {
        if (wp_next_scheduled('dream2_mxin_async_comment_mail', $args)) {
            dream2_mxin_mail_log_event(array_merge($mail_context, array(
                'channel'   => 'comment',
                'mail_type' => $type,
                'source_id' => $comment_id,
                'status'    => 'retry_scheduled',
                'attempt'   => $attempts + 1,
                'detail'    => '重试任务已存在',
            )));
            return true;
        }
        $scheduled = wp_schedule_single_event(
            time() + $retry_delays[$attempts - 1],
            'dream2_mxin_async_comment_mail',
            $args
        );
        if (!is_wp_error($scheduled) && $scheduled) {
            dream2_mxin_mail_log_event(array_merge($mail_context, array(
                'channel'   => 'comment',
                'mail_type' => $type,
                'source_id' => $comment_id,
                'status'    => 'retry_scheduled',
                'attempt'   => $attempts + 1,
                'detail'    => sprintf(
                    '%d 分钟后重试',
                    (int) ($retry_delays[$attempts - 1] / MINUTE_IN_SECONDS)
                ),
            )));
            return true;
        }
    }

    update_comment_meta($comment_id, $failed_key, current_time('mysql'));
    delete_comment_meta($comment_id, $queued_key);
    dream2_mxin_mail_log_event(array_merge($mail_context, array(
        'channel'   => 'comment',
        'mail_type' => $type,
        'source_id' => $comment_id,
        'status'    => 'final_failed',
        'attempt'   => $attempts,
        'detail'    => isset($retry_delays[$attempts - 1])
            ? '重试任务排队失败'
            : '已达到最大重试次数',
    )));
    return false;
}

function dream2_mxin_defer_comment_mail_cron_spawn() {
    static $registered = false;
    if ($registered || !function_exists('spawn_cron')) {
        return;
    }
    $registered = true;
    add_action('shutdown', static function () {
        spawn_cron(time());
    }, PHP_INT_MAX);
}

function dream2_mxin_queue_comment_mail($comment_id, $type) {
    $comment_id = absint($comment_id);
    if (!$comment_id || !in_array($type, array('post_author', 'moderator', 'reply'), true)) {
        return false;
    }
    $comment = get_comment($comment_id);
    if (!$comment) {
        return false;
    }
    if ($type === 'post_author' && !dream2_enabled('email_notify_post_author', true)) {
        return false;
    }
    if ($type === 'moderator' && !dream2_enabled('email_notify_moderator', true)) {
        return false;
    }
    if ($type === 'reply' && (!dream2_enabled('email_notify_reply') || empty($comment->comment_parent))) {
        return false;
    }
    $mail_context = dream2_mxin_comment_mail_log_context($comment, $type);
    if (!$mail_context) {
        return false;
    }
    $sent_key = dream2_mxin_comment_mail_meta_key($type, 'sent');
    $queued_key = dream2_mxin_comment_mail_meta_key($type, 'queued');
    if (!$sent_key || get_comment_meta($comment_id, $sent_key, true) || get_comment_meta($comment_id, $queued_key, true)) {
        return false;
    }
    foreach (array('attempts', 'failed') as $state) {
        delete_comment_meta($comment_id, dream2_mxin_comment_mail_meta_key($type, $state));
    }
    if (!add_comment_meta($comment_id, $queued_key, current_time('mysql'), true)) {
        return false;
    }
    $scheduled = wp_schedule_single_event(time(), 'dream2_mxin_async_comment_mail', array($comment_id, $type));
    if (is_wp_error($scheduled) || !$scheduled) {
        delete_comment_meta($comment_id, $queued_key);
        update_comment_meta($comment_id, dream2_mxin_comment_mail_meta_key($type, 'failed'), current_time('mysql'));
        dream2_mxin_mail_log_event(array_merge($mail_context, array(
            'channel'   => 'comment',
            'mail_type' => $type,
            'source_id' => $comment_id,
            'status'    => 'schedule_failed',
            'attempt'   => 1,
            'detail'    => is_wp_error($scheduled)
                ? $scheduled->get_error_message()
                : 'WP-Cron 任务创建失败',
        )));
        return false;
    }
    dream2_mxin_mail_log_event(array_merge($mail_context, array(
        'channel'   => 'comment',
        'mail_type' => $type,
        'source_id' => $comment_id,
        'status'    => 'queued',
        'attempt'   => 1,
        'detail'    => '等待 WP-Cron 发送',
    )));
    dream2_mxin_defer_comment_mail_cron_spawn();
    return true;
}

function dream2_mxin_comment_excerpt_for_mail($comment) {
    return wp_trim_words(wp_strip_all_tags(get_comment_text($comment)), 80, '...');
}

function dream2_mxin_comment_mail_recipients($comment, $type) {
    if ($type === 'moderator') {
        $emails = apply_filters('comment_moderation_recipients', array(get_option('admin_email')), $comment->comment_ID);
    } elseif ($type === 'reply') {
        $parent = $comment->comment_parent ? get_comment($comment->comment_parent) : null;
        if (!$parent || empty($parent->comment_author_email) || !is_email($parent->comment_author_email)) {
            return array();
        }
        if (strtolower((string) $parent->comment_author_email) === strtolower((string) $comment->comment_author_email)) {
            return array();
        }
        $emails = array($parent->comment_author_email);
    } else {
        $post = get_post($comment->comment_post_ID);
        $author = $post ? get_userdata((int) $post->post_author) : null;
        $emails = $author && is_email($author->user_email) ? array($author->user_email) : array(get_option('admin_email'));
        $emails = apply_filters('comment_notification_recipients', $emails, $comment->comment_ID);
    }

    $emails = array_map('sanitize_email', (array) $emails);
    $emails = array_values(array_unique(array_filter($emails, 'is_email')));
    if ($type === 'post_author') {
        $commenter = strtolower((string) $comment->comment_author_email);
        $emails = array_values(array_filter($emails, static function ($email) use ($commenter) {
            return strtolower((string) $email) !== $commenter;
        }));
        if (dream2_enabled('email_notify_reply') && !empty($comment->comment_parent)) {
            $reply_recipients = dream2_mxin_comment_mail_recipients($comment, 'reply');
            $reply_recipient_map = array_fill_keys(
                array_map('strtolower', $reply_recipients),
                true
            );
            $emails = array_values(array_filter(
                $emails,
                static function ($email) use ($reply_recipient_map) {
                    return !isset($reply_recipient_map[strtolower((string) $email)]);
                }
            ));
        }
    }
    return $emails;
}

function dream2_mxin_comment_mail_payload($comment, $type) {
    if (!$comment || !in_array($type, array('post_author', 'moderator', 'reply'), true)) {
        return array();
    }

    $recipients = dream2_mxin_comment_mail_recipients($comment, $type);
    if (!$recipients) {
        return array();
    }

    $post = get_post($comment->comment_post_ID);
    $post_title = $post
        ? wp_specialchars_decode(get_the_title($post), ENT_QUOTES)
        : wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
    $site_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
    $comment_time = get_comment_date('Y-m-d H:i:s', $comment);
    $rows = array(
        array('label' => '文章', 'value' => $post_title),
        array('label' => '评论者', 'value' => $comment->comment_author ?: '访客'),
        array('label' => '网站', 'value' => $comment->comment_author_url ?: '未填写'),
        array('label' => '评论内容', 'value' => dream2_mxin_comment_excerpt_for_mail($comment)),
        array('label' => '评论时间', 'value' => $comment_time),
    );
    if ($type !== 'reply') {
        array_splice($rows, 2, 0, array(
            array('label' => '邮箱', 'value' => $comment->comment_author_email ?: '未填写'),
        ));
    }

    if ($type === 'moderator') {
        $subject = sprintf('[%s] 新评论待审核：%s', $site_name, $post_title);
        $message = dream2_mxin_mail_template(
            '新评论等待审核',
            '有一条新评论正在等待审核，处理后才会出现在前台。',
            $rows,
            '',
            '',
            array(
                array('text' => '批准评论', 'url' => admin_url('comment.php?action=approve&c=' . (int) $comment->comment_ID), 'primary' => true),
                array('text' => '标为垃圾评论', 'url' => admin_url('comment.php?action=spam&c=' . (int) $comment->comment_ID)),
                array('text' => '删除评论', 'url' => admin_url('comment.php?action=trash&c=' . (int) $comment->comment_ID), 'danger' => true),
            )
        );
    } elseif ($type === 'reply') {
        $parent = get_comment($comment->comment_parent);
        array_unshift($rows, array('label' => '原评论者', 'value' => $parent ? ($parent->comment_author ?: '访客') : '访客'));
        $subject = sprintf('[%s] 您在《%s》的评论有新回复', $site_name, $post_title);
        $message = dream2_mxin_mail_template(
            '你的评论有新回复',
            '有人回复了你之前留下的评论。',
            $rows,
            '查看回复',
            get_comment_link($comment)
        );
    } else {
        $subject = sprintf('[%s] 新评论：%s', $site_name, $post_title);
        $message = dream2_mxin_mail_template(
            '收到一条新评论',
            '你的文章有一条新的已发布评论。',
            $rows,
            '查看评论',
            get_comment_link($comment),
            array(
                array('text' => '编辑评论', 'url' => admin_url('comment.php?action=editcomment&c=' . (int) $comment->comment_ID)),
                array('text' => '删除评论', 'url' => admin_url('comment.php?action=trash&c=' . (int) $comment->comment_ID), 'danger' => true),
            )
        );
    }

    return array(
        'recipients' => $recipients,
        'subject'    => $subject,
        'message'    => $message,
        'headers'    => dream2_mxin_mail_headers(),
    );
}

function dream2_mxin_comment_mail_log_context($comment, $type) {
    $comment = is_object($comment) ? $comment : get_comment(absint($comment));
    $payload = dream2_mxin_comment_mail_payload($comment, $type);
    if (!$payload) {
        return array();
    }
    return array(
        'sender'     => dream2_mxin_mail_log_sender_from_headers($payload['headers']),
        'recipients' => dream2_mxin_mail_log_all_recipients($payload['recipients'], $payload['headers']),
        'subject'    => $payload['subject'],
        'content'    => $payload['message'],
    );
}

function dream2_mxin_send_queued_comment_mail($comment_id, $type) {
    $comment_id = absint($comment_id);
    $comment = get_comment($comment_id);
    if (!$comment || !in_array($type, array('post_author', 'moderator', 'reply'), true)) {
        return false;
    }
    if ($type !== 'moderator' && (string) $comment->comment_approved !== '1') {
        dream2_mxin_clear_comment_mail_queue_state($comment->comment_ID, $type);
        return false;
    }
    if ($type === 'moderator' && (string) $comment->comment_approved !== '0') {
        dream2_mxin_clear_comment_mail_queue_state($comment->comment_ID, $type);
        return false;
    }
    if ($type === 'post_author' && !dream2_enabled('email_notify_post_author', true)) {
        dream2_mxin_clear_comment_mail_queue_state($comment->comment_ID, $type);
        return false;
    }
    if ($type === 'moderator' && !dream2_enabled('email_notify_moderator', true)) {
        dream2_mxin_clear_comment_mail_queue_state($comment->comment_ID, $type);
        return false;
    }
    if ($type === 'reply' && !dream2_enabled('email_notify_reply')) {
        dream2_mxin_clear_comment_mail_queue_state($comment->comment_ID, $type);
        return false;
    }

    $sent_key = dream2_mxin_comment_mail_meta_key($type, 'sent');
    if (!$sent_key || get_comment_meta($comment->comment_ID, $sent_key, true)) {
        dream2_mxin_clear_comment_mail_queue_state($comment->comment_ID, $type);
        return false;
    }

    $payload = dream2_mxin_comment_mail_payload($comment, $type);
    if (!$payload) {
        dream2_mxin_clear_comment_mail_queue_state($comment->comment_ID, $type);
        return false;
    }

    $sent = dream2_mxin_send_logged_mail(
        $payload['recipients'],
        $payload['subject'],
        $payload['message'],
        $payload['headers'],
        array(
            'channel'   => 'comment',
            'mail_type' => $type,
            'source_id' => $comment->comment_ID,
            'attempt'   => absint(get_comment_meta(
                $comment->comment_ID,
                dream2_mxin_comment_mail_meta_key($type, 'attempts'),
                true
            )) + 1,
        )
    );
    if ($sent) {
        dream2_mxin_clear_comment_mail_queue_state($comment->comment_ID, $type, true);
        update_comment_meta($comment->comment_ID, $sent_key, current_time('mysql'));
        return true;
    }
    dream2_mxin_retry_comment_mail($comment_id, $type);
    return false;
}
add_action('dream2_mxin_async_comment_mail', 'dream2_mxin_send_queued_comment_mail', 10, 2);

function dream2_mxin_send_reply_notification($comment_id) {
    return dream2_mxin_queue_comment_mail($comment_id, 'reply');
}

add_action('comment_post', function ($comment_id, $comment_approved) {
    if ((string) $comment_approved === '1') {
        dream2_mxin_queue_comment_mail($comment_id, 'post_author');
        dream2_mxin_queue_comment_mail($comment_id, 'reply');
    } elseif ((string) $comment_approved === '0') {
        dream2_mxin_queue_comment_mail($comment_id, 'moderator');
    }
}, 20, 2);

add_action('transition_comment_status', function ($new_status, $old_status, $comment) {
    if ($new_status === 'approved' && $old_status !== 'approved' && $comment) {
        dream2_mxin_queue_comment_mail($comment->comment_ID, 'post_author');
        dream2_mxin_queue_comment_mail($comment->comment_ID, 'reply');
    }
}, 20, 3);

function dream2_mxin_deprecated_option_keys() {
    return array(
        'sw_cdn_source', 'enable_tags_tag_color', 'providerMirror', 'enable_debug',
        'journals_fold_height', 'enable_journals_comment', 'enable_journals_share',
        'journals_share_image', 'metadata_name', 'sidebar_show', 'avatar_source',
        'link_abnormal_category', 'link_check_abnormal_days', 'link_auto_move_abnormal',
        'link_notify_one_way', 'link_notify_abnormal',
    );
}

function dream2_mxin_migrate_removed_abnormal_link_group() {
    if (get_option('dream2_link_group_state_version') === '3') {
        return true;
    }
    $lock_key = 'dream2_link_group_state_migration_lock';
    $locked_at = (int) get_option($lock_key, 0);
    if ($locked_at && time() - $locked_at >= 15 * MINUTE_IN_SECONDS) {
        delete_option($lock_key);
    }
    if (!add_option($lock_key, time(), '', false)) {
        return false;
    }

    $success = true;
    try {
        $options = get_option('dream2_options', array());
        $options = is_array($options) ? $options : array();
        if (!array_key_exists('link_auto_move_lost', $options) && array_key_exists('link_auto_move_abnormal', $options)) {
            $options['link_auto_move_lost'] = filter_var($options['link_auto_move_abnormal'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        }
        if (array_key_exists('link_notify_one_way', $options)) {
            $legacy_one_way_notice = filter_var($options['link_notify_one_way'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            if (!array_key_exists('link_notify_one_way_reminder', $options)) {
                $options['link_notify_one_way_reminder'] = $legacy_one_way_notice;
            }
            if (!array_key_exists('link_notify_one_way_deleted', $options)) {
                $options['link_notify_one_way_deleted'] = $legacy_one_way_notice;
            }
        }
        $abnormal_id = absint($options['link_abnormal_category'] ?? 0);
        if (!$abnormal_id) {
            $abnormal_id = dream2_mxin_default_link_category_id('异常博客')
                ?: dream2_mxin_default_link_category_id('检测异常');
        }
        $lost_id = absint($options['link_lost_category'] ?? dream2_mxin_default_link_category_id('失联博客'));
        if ($abnormal_id && !$lost_id) {
            $success = false;
            return false;
        }

        if ($abnormal_id && $abnormal_id !== $lost_id) {
            $links = get_bookmarks(array('category' => $abnormal_id, 'hide_invisible' => false));
            foreach ($links as $bookmark) {
                $moved = wp_set_object_terms((int) $bookmark->link_id, array($lost_id), 'link_category', false);
                if (is_wp_error($moved)) {
                    $success = false;
                }
            }
            if (!$success) {
                return false;
            }
            $order = array_values(array_filter(dream2_mxin_link_category_order(), static function ($term_id) use ($abnormal_id) {
                return (int) $term_id !== $abnormal_id;
            }));
            update_option('dream2_link_category_order', $order, false);
            $managed = array_values(array_filter((array) get_option('dream2_link_managed_category_ids', array()), static function ($term_id) use ($abnormal_id) {
                return (int) $term_id !== $abnormal_id;
            }));
            update_option('dream2_link_managed_category_ids', $managed, false);
        }
        update_option('dream2_options', dream2_mxin_strip_deprecated_options($options), false);
        update_option('dream2_link_group_state_version', '3', false);
        return true;
    } finally {
        delete_option($lock_key);
    }
}

function dream2_mxin_strip_deprecated_options($options) {
    if (!is_array($options)) {
        return array();
    }
    foreach (dream2_mxin_deprecated_option_keys() as $key) {
        unset($options[$key]);
    }
    return $options;
}

function dream2_mxin_sanitize_secret($value) {
    if (!is_scalar($value)) {
        return '';
    }
    $value = preg_replace('/[\x00-\x1F\x7F]/', '', (string) $value);
    return substr((string) $value, 0, 4096);
}

function dream2_mxin_sanitize_ai_system_prompt($value) {
    if (!is_scalar($value)) {
        return '';
    }
    $value = wp_check_invalid_utf8((string) $value);
    $value = str_replace(array("\r\n", "\r", "\0"), array("\n", "\n", ''), $value);
    $value = preg_replace('/[\x01-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($value, 'UTF-8') > 20000 ? mb_substr($value, 0, 20000, 'UTF-8') : $value;
    }
    $characters = preg_split('//u', $value, 20001, PREG_SPLIT_NO_EMPTY);
    return is_array($characters) && count($characters) > 20000
        ? implode('', array_slice($characters, 0, 20000))
        : $value;
}

function dream2_mxin_sanitize_ai_model($value) {
    if (!is_scalar($value)) {
        return '';
    }
    return substr(sanitize_text_field((string) $value), 0, 255);
}

function dream2_mxin_sanitize_openai_base_url($value) {
    if (!is_scalar($value)) {
        return '';
    }
    $url = esc_url_raw(trim((string) $value), array('https'));
    if ($url === '') {
        return '';
    }
    $parts = wp_parse_url($url);
    if (!is_array($parts)
        || strtolower((string) ($parts['scheme'] ?? '')) !== 'https'
        || empty($parts['host'])
        || isset($parts['user'])
        || isset($parts['pass'])
        || isset($parts['query'])
        || isset($parts['fragment'])) {
        return '';
    }
    return untrailingslashit($url);
}

function dream2_mxin_resolve_ai_base_url($provider, $base_url) {
    $provider = is_scalar($provider) ? sanitize_key((string) $provider) : '';
    $provider_base_urls = dream2_mxin_ai_provider_base_urls();
    if (isset($provider_base_urls[$provider])) {
        return $provider_base_urls[$provider];
    }
    if ($provider === 'custom') {
        $base_url = dream2_mxin_sanitize_openai_base_url($base_url);
        return $base_url !== ''
            ? $base_url
            : new WP_Error('dream2_ai_missing_base_url', '请填写有效的 HTTPS Base URL。');
    }
    return new WP_Error('dream2_ai_invalid_provider', 'API 服务无效。');
}

function dream2_mxin_ai_connection_fingerprint($provider, $base_url, $api_key) {
    $base_url = dream2_mxin_resolve_ai_base_url($provider, $base_url);
    if (is_wp_error($base_url)) {
        return '';
    }
    $api_key = dream2_mxin_sanitize_secret($api_key);
    if ($api_key === '') {
        return '';
    }
    return hash_hmac(
        'sha256',
        sanitize_key((string) $provider) . "\n" . $base_url . "\n" . $api_key,
        wp_salt('nonce')
    );
}

function dream2_mxin_ai_connection_matches_options($input, $options) {
    $input = is_array($input) ? $input : array();
    $options = is_array($options) ? $options : array();

    $stored_provider = isset($options['ai_provider'])
        ? sanitize_key((string) $options['ai_provider'])
        : dream2_mxin_ai_provider_for_base_url($options['ai_base_url'] ?? '');
    $submitted_provider = isset($input['ai_provider']) && is_scalar($input['ai_provider'])
        ? sanitize_key(wp_unslash($input['ai_provider']))
        : $stored_provider;
    if ($submitted_provider !== $stored_provider) {
        return false;
    }

    $stored_base_url = dream2_mxin_resolve_ai_base_url($stored_provider, $options['ai_base_url'] ?? '');
    $submitted_base_url = dream2_mxin_resolve_ai_base_url(
        $submitted_provider,
        isset($input['ai_base_url']) && is_scalar($input['ai_base_url'])
            ? wp_unslash($input['ai_base_url'])
            : ($options['ai_base_url'] ?? '')
    );
    if (is_wp_error($stored_base_url)
        || is_wp_error($submitted_base_url)
        || $submitted_base_url !== $stored_base_url) {
        return false;
    }

    $stored_api_key = dream2_mxin_sanitize_secret($options['ai_api_key'] ?? '');
    $submitted_api_key = isset($input['ai_api_key']) && is_scalar($input['ai_api_key'])
        ? dream2_mxin_sanitize_secret(wp_unslash($input['ai_api_key']))
        : $stored_api_key;
    $stored_model = dream2_mxin_sanitize_ai_model($options['ai_model'] ?? '');
    $submitted_model = isset($input['ai_model']) && is_scalar($input['ai_model'])
        ? dream2_mxin_sanitize_ai_model(wp_unslash($input['ai_model']))
        : $stored_model;

    return hash_equals($stored_api_key, $submitted_api_key)
        && hash_equals($stored_model, $submitted_model);
}

function dream2_mxin_check_ai_connection($provider, $base_url, $api_key) {
    $base_url = dream2_mxin_resolve_ai_base_url($provider, $base_url);
    if (is_wp_error($base_url)) {
        return $base_url;
    }
    $api_key = dream2_mxin_sanitize_secret($api_key);
    if ($api_key === '') {
        return new WP_Error('dream2_ai_missing_api_key', '请填写 API Key。');
    }

    $response = wp_safe_remote_get(
        trailingslashit($base_url) . 'models',
        array(
            'headers' => array(
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'timeout'             => 15,
            'redirection'         => 0,
            'limit_response_size' => 1024 * 1024,
            'user-agent'          => 'Dream2-MXIN/' . (defined('DREAM2_MXIN_VERSION') ? DREAM2_MXIN_VERSION : 'unknown'),
        )
    );
    if (is_wp_error($response)) {
        return new WP_Error('dream2_ai_connection_failed', '连接失败：' . $response->get_error_message());
    }

    $status = (int) wp_remote_retrieve_response_code($response);
    $body = (string) wp_remote_retrieve_body($response);
    $payload = json_decode($body, true);
    if ($status < 200 || $status >= 300) {
        $message = is_array($payload) ? (string) ($payload['error']['message'] ?? $payload['message'] ?? '') : '';
        $message = sanitize_text_field($message);
        if ($status === 401 || $status === 403) {
            $message = '鉴权失败，请检查 API Key。';
        } elseif ($status === 404) {
            $message = '模型接口不存在，请检查 Base URL。';
        } elseif ($message === '') {
            $message = '服务返回 HTTP ' . $status . '。';
        }
        return new WP_Error('dream2_ai_http_error', '连接失败：' . $message);
    }
    if (!is_array($payload) || !isset($payload['data']) || !is_array($payload['data'])) {
        return new WP_Error('dream2_ai_invalid_response', '连接成功，但返回结果不符合 OpenAI 模型列表协议。');
    }

    $models = array();
    foreach ($payload['data'] as $model) {
        $model_id = is_array($model) ? dream2_mxin_sanitize_ai_model($model['id'] ?? '') : '';
        if ($model_id !== '') {
            $models[] = $model_id;
        }
    }
    $models = array_values(array_unique($models));
    natcasesort($models);
    $models = array_values($models);
    if (!$models) {
        return new WP_Error('dream2_ai_empty_models', '连接成功，但接口未返回可用模型。');
    }
    $model_count = count($models);
    $models = array_slice($models, 0, 500);

    return array(
        'message' => $model_count > count($models)
            ? sprintf('连接成功，获取到 %d 个模型，显示前 %d 个。', $model_count, count($models))
            : sprintf('连接成功，获取到 %d 个模型。', $model_count),
        'models'  => $models,
    );
}

function dream2_mxin_create_ai_test_token($provider, $base_url, $api_key, $models) {
    $fingerprint = dream2_mxin_ai_connection_fingerprint($provider, $base_url, $api_key);
    if ($fingerprint === '') {
        return new WP_Error('dream2_ai_invalid_test_state', '测试配置无效。');
    }
    $models = array_values(array_unique(array_filter(array_map('dream2_mxin_sanitize_ai_model', (array) $models))));
    if (!$models) {
        return new WP_Error('dream2_ai_empty_models', '接口未返回可保存的模型。');
    }

    $token = wp_generate_uuid4();
    $stored = set_transient(
        'dream2_ai_test_' . md5(get_current_user_id() . '|' . $token),
        array(
            'user_id'     => get_current_user_id(),
            'fingerprint' => $fingerprint,
            'models'      => $models,
        ),
        30 * MINUTE_IN_SECONDS
    );
    return $stored ? $token : new WP_Error('dream2_ai_test_state_failed', '测试成功，但测试状态保存失败。');
}

function dream2_mxin_verify_ai_test_token($input, $token) {
    $token = is_scalar($token) ? sanitize_key((string) $token) : '';
    if ($token === '') {
        return new WP_Error('dream2_ai_test_required', '请先测试连接成功后再保存 AI 配置。');
    }

    $transient_key = 'dream2_ai_test_' . md5(get_current_user_id() . '|' . $token);
    $state = get_transient($transient_key);
    if (!is_array($state) || (int) ($state['user_id'] ?? 0) !== get_current_user_id()) {
        return new WP_Error('dream2_ai_test_expired', '连接测试已失效，请重新测试后保存。');
    }

    $provider = isset($input['ai_provider']) && is_scalar($input['ai_provider']) ? wp_unslash($input['ai_provider']) : '';
    $base_url = isset($input['ai_base_url']) && is_scalar($input['ai_base_url']) ? wp_unslash($input['ai_base_url']) : '';
    $api_key = isset($input['ai_api_key']) && is_scalar($input['ai_api_key']) ? wp_unslash($input['ai_api_key']) : '';
    $fingerprint = dream2_mxin_ai_connection_fingerprint($provider, $base_url, $api_key);
    if ($fingerprint === '' || !hash_equals((string) ($state['fingerprint'] ?? ''), $fingerprint)) {
        return new WP_Error('dream2_ai_test_mismatch', 'API 连接配置已变化，请重新测试后保存。');
    }

    $model = dream2_mxin_sanitize_ai_model($input['ai_model'] ?? '');
    if ($model === '' || !in_array($model, (array) ($state['models'] ?? array()), true)) {
        return new WP_Error('dream2_ai_model_not_tested', '请选择本次连接测试返回的模型。');
    }

    delete_transient($transient_key);
    return true;
}

function dream2_mxin_handle_ai_connection_test() {
    if (!current_user_can('edit_theme_options')) {
        wp_send_json_error(array('message' => '权限不足。'), 403);
    }
    check_ajax_referer('dream2_mxin_test_ai_connection', 'nonce');

    $provider = isset($_POST['provider']) && is_scalar($_POST['provider']) ? sanitize_key(wp_unslash($_POST['provider'])) : '';
    $base_url = isset($_POST['base_url']) && is_scalar($_POST['base_url']) ? wp_unslash($_POST['base_url']) : '';
    $has_api_key = isset($_POST['api_key']) && is_scalar($_POST['api_key']);
    $api_key = $has_api_key ? dream2_mxin_sanitize_secret(wp_unslash($_POST['api_key'])) : '';
    if (!$has_api_key) {
        $options = get_option('dream2_options', array());
        $api_key = is_array($options) ? (string) ($options['ai_api_key'] ?? '') : '';
    }

    $result = dream2_mxin_check_ai_connection($provider, $base_url, $api_key);
    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()), 400);
    }
    $test_token = dream2_mxin_create_ai_test_token($provider, $base_url, $api_key, $result['models']);
    if (is_wp_error($test_token)) {
        wp_send_json_error(array('message' => $test_token->get_error_message()), 500);
    }
    $result['test_token'] = $test_token;
    wp_send_json_success($result);
}
add_action('wp_ajax_dream2_mxin_test_ai_connection', 'dream2_mxin_handle_ai_connection_test');

function dream2_mxin_sanitize_options($input) {
    $input = is_array($input) ? $input : array();
    $clean = get_option('dream2_options', array());
    $clean = is_array($clean) ? $clean : array();
    $clean = dream2_mxin_strip_deprecated_options($clean);
    $submitted_fields = array_keys(dream2_mxin_settings_registry());
    if (isset($_POST['dream2_options_fields'])) {
        $submitted_fields = array_filter(array_map('sanitize_key', explode(',', wp_unslash($_POST['dream2_options_fields']))));
    }
    if (in_array('ai_provider', $submitted_fields, true)
        && !dream2_mxin_ai_connection_matches_options($input, $clean)) {
        $test_token = isset($_POST['dream2_ai_test_token']) ? wp_unslash($_POST['dream2_ai_test_token']) : '';
        $test_result = dream2_mxin_verify_ai_test_token($input, $test_token);
        if (is_wp_error($test_result)) {
            add_settings_error('dream2_options', $test_result->get_error_code(), $test_result->get_error_message(), 'error');
            return $clean;
        }
    }
    foreach (dream2_mxin_settings_registry() as $name => $field) {
        if (!in_array($name, $submitted_fields, true)) {
            continue;
        }
        if ($field['type'] === 'repeater') {
            $clean[$name] = dream2_mxin_sanitize_repeater($name, $input[$name] ?? array());
        } elseif (isset($input[$name])) {
            $value = wp_unslash($input[$name]);
            if ($name === 'load_progress') $value = dream2_mxin_normalize_load_progress($value);
            if ($name === 'enable_hitokoto') $value = dream2_mxin_normalize_hitokoto_mode($value);
            if ($name === 'ai_user_id') {
                $user_id = absint($value);
                if ($user_id > 0 && !get_user_by('id', $user_id)) {
                    add_settings_error('dream2_options', 'dream2_ai_user_not_found', '关联账户不存在，已取消关联。', 'error');
                    $user_id = 0;
                }
                $clean[$name] = $user_id;
            }
            elseif ($name === 'ai_api_key') $clean[$name] = dream2_mxin_sanitize_secret($value);
            elseif ($field['type'] === 'email') $clean[$name] = sanitize_email($value);
            elseif (in_array($name, array('link_friend_category', 'link_one_way_category', 'link_lost_category'), true)) $clean[$name] = absint($value);
            elseif ($name === 'archivers_route_slug') $clean[$name] = sanitize_title($value) ?: 'archivers';
            elseif ($field['type'] === 'color') $clean[$name] = sanitize_hex_color($value);
            elseif ($field['type'] === 'date') $clean[$name] = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value) ? (string) $value : '';
            elseif ($name === 'ai_model') $clean[$name] = dream2_mxin_sanitize_ai_model($value);
            elseif ($name === 'ai_system_prompt') $clean[$name] = dream2_mxin_sanitize_ai_system_prompt($value);
            elseif ($name === 'ai_search_prompt' || $name === 'ai_summary_prompt') $clean[$name] = dream2_mxin_ai_feature_prompt_value($name, dream2_mxin_sanitize_ai_system_prompt($value));
            elseif ($name === 'ai_base_url') $clean[$name] = dream2_mxin_sanitize_openai_base_url($value);
            elseif ($name === 'link_application_blacklist') $clean[$name] = dream2_mxin_normalize_link_application_blacklist($value);
            elseif ($field['type'] === 'image' || str_ends_with($name, '_url')) $clean[$name] = esc_url_raw($value);
            elseif ($field['type'] === 'emoji_groups') $clean[$name] = dream2_mxin_sanitize_comment_emoji_groups($value);
            elseif ($field['type'] === 'textarea') $clean[$name] = current_user_can('unfiltered_html') ? $value : wp_kses_post($value);
            elseif (in_array($field['type'], array('number', 'range'), true)) {
                $number = $field['type'] === 'range' ? (int) $value : absint($value);
                $minimum = isset($field['attributes']['min']) ? absint($field['attributes']['min']) : 0;
                $maximum = isset($field['attributes']['max']) ? absint($field['attributes']['max']) : PHP_INT_MAX;
                $allow_zero = !in_array($name, array('link_check_batch_size', 'link_check_failure_threshold', 'link_one_way_notice_days', 'link_one_way_delete_days'), true);
                $clean[$name] = $number === 0 && $allow_zero ? 0 : min($maximum, max($minimum, $number));
            }
            elseif ($field['type'] === 'select' || $field['type'] === 'radio') $clean[$name] = array_key_exists((string) $value, $field['choices']) ? (string) $value : (string) $field['default'];
            else $clean[$name] = sanitize_text_field($value);
        }
    }
    if (isset($clean['link_enable_comment']) && (string) $clean['link_enable_comment'] !== '1') {
        $clean['enable_link_application'] = '0';
    }
    if (in_array('ai_provider', $submitted_fields, true)) {
        $provider = isset($clean['ai_provider']) ? sanitize_key($clean['ai_provider']) : 'openai';
        $provider_base_urls = dream2_mxin_ai_provider_base_urls();
        if (isset($provider_base_urls[$provider])) {
            $clean['ai_base_url'] = $provider_base_urls[$provider];
        }
    }
    if (isset($clean['link_one_way_notice_days'], $clean['link_one_way_delete_days'])) {
        $clean['link_one_way_delete_days'] = max(
            absint($clean['link_one_way_notice_days']),
            absint($clean['link_one_way_delete_days'])
        );
    }
    return $clean;
}

add_action('admin_init', function () {
    dream2_mxin_migrate_removed_abnormal_link_group();
    dream2_mxin_handle_link_manager_action();
    $options = get_option('dream2_options', array());
    if (is_array($options)) {
        $clean_options = dream2_mxin_strip_deprecated_options($options);
        if ($clean_options !== $options) {
            update_option('dream2_options', $clean_options, false);
        }
    }
    register_setting('dream2_options_group', 'dream2_options', array('sanitize_callback'=>'dream2_mxin_sanitize_options','default'=>array()));
});
add_action('admin_menu', function () {
    $GLOBALS['dream2_mxin_settings_hook'] = add_menu_page('梦屿设置', '梦屿', 'edit_theme_options', 'dream2-settings', 'dream2_mxin_render_settings_page', 'dashicons-palmtree', 6);
    $GLOBALS['dream2_mxin_settings_hooks'] = array($GLOBALS['dream2_mxin_settings_hook']);
    foreach (dream2_mxin_settings_groups() as $group => $group_label) {
        $slug = $group === array_key_first(dream2_mxin_settings_groups()) ? 'dream2-settings' : 'dream2-settings-' . $group;
        $GLOBALS['dream2_mxin_settings_hooks'][] = add_submenu_page('dream2-settings', '梦屿' . $group_label, $group_label, 'edit_theme_options', $slug, 'dream2_mxin_render_settings_page');
    }
    $GLOBALS['dream2_mxin_link_settings_hook'] = add_submenu_page('dream2-settings', '梦屿友链管理', '友链管理', 'edit_theme_options', 'dream2-link-manager', 'dream2_mxin_render_link_settings_page');
});
add_action('admin_enqueue_scripts', function ($hook) {
    $settings_hooks = array_filter(array_merge(
        $GLOBALS['dream2_mxin_settings_hooks'] ?? array($GLOBALS['dream2_mxin_settings_hook'] ?? ''),
        array(
        $GLOBALS['dream2_mxin_link_settings_hook'] ?? '',
        )
    ));
    if (!in_array($hook, $settings_hooks, true)) {
        return;
    }
    wp_enqueue_media(); wp_enqueue_style('wp-color-picker'); wp_enqueue_script('wp-color-picker');
    $settings_page = isset($_GET['page']) && is_scalar($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    if (in_array($settings_page, array('dream2-settings-communication', 'dream2-settings-ai'), true)) {
        wp_enqueue_script('user-profile');
    }
    $admin_dream_css = get_template_directory() . '/assets/css/admin-dream.css';
    wp_enqueue_style('dream2-admin-dream', get_template_directory_uri() . '/assets/css/admin-dream.css', array(), file_exists($admin_dream_css) ? (string) filemtime($admin_dream_css) : DREAM2_MXIN_VERSION);
    wp_enqueue_style('dream2-admin-emoji', get_template_directory_uri() . '/assets/css/admin-emoji.css', array(), DREAM2_MXIN_VERSION);
    wp_enqueue_script('dream2-admin-emoji', get_template_directory_uri() . '/assets/js/admin-emoji.js', array(), DREAM2_MXIN_VERSION, true);
    wp_localize_script('dream2-admin-emoji', 'Dream2EmojiAdmin', array('ajaxUrl'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('dream2_mxin_emoji_admin')));
    wp_add_inline_script('dream2-admin-emoji', <<<'JS'
(function() {
    document.documentElement.classList.add('dream2-tooltip-portal-enabled');
    var tooltip;
    function ensureTooltip() {
        if (!tooltip) {
            tooltip = document.createElement('div');
            tooltip.className = 'dream2-tooltip-portal';
            document.body.appendChild(tooltip);
        }
        return tooltip;
    }
    function hideTooltip() {
        if (tooltip) {
            tooltip.classList.remove('is-visible', 'is-below');
        }
    }
    function showTooltip(target) {
        var text = target.getAttribute('data-tooltip');
        if (!text) {
            return;
        }
        var node = ensureTooltip();
        node.textContent = text;
        node.classList.add('is-visible');
        node.classList.remove('is-below');
        node.style.left = '0px';
        node.style.top = '0px';
        var rect = target.getBoundingClientRect();
        var box = node.getBoundingClientRect();
        var left = rect.left + rect.width / 2 - box.width / 2;
        left = Math.max(12, Math.min(left, window.innerWidth - box.width - 12));
        var top = rect.top - box.height - 10;
        if (top < 12) {
            top = rect.bottom + 10;
            node.classList.add('is-below');
        }
        node.style.left = left + 'px';
        node.style.top = top + 'px';
    }
    document.addEventListener('mouseover', function(event) {
        var target = event.target.closest && event.target.closest('.dream2-help-tooltip[data-tooltip]');
        if (target) {
            showTooltip(target);
        }
    });
    document.addEventListener('mouseout', function(event) {
        var target = event.target.closest && event.target.closest('.dream2-help-tooltip[data-tooltip]');
        if (target && (!event.relatedTarget || !target.contains(event.relatedTarget))) {
            hideTooltip();
        }
    });
    document.addEventListener('focusin', function(event) {
        if (event.target.matches && event.target.matches('.dream2-help-tooltip[data-tooltip]')) {
            showTooltip(event.target);
        }
    });
    document.addEventListener('focusout', function(event) {
        if (event.target.matches && event.target.matches('.dream2-help-tooltip[data-tooltip]')) {
            hideTooltip();
        }
    });
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            hideTooltip();
        }
    });
    document.addEventListener('click', hideTooltip, true);
    window.addEventListener('scroll', hideTooltip, true);
    window.addEventListener('resize', hideTooltip);
})();
JS);
});

function dream2_mxin_render_settings_page() {
    if (!current_user_can('edit_theme_options')) return;
    $groups = dream2_mxin_settings_groups(); $registry = dream2_mxin_settings_registry(); $options = get_option('dream2_options', array());
    $ai_provider_base_urls = dream2_mxin_ai_provider_base_urls();
    $link_fields = dream2_mxin_link_settings_fields();
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : 'dream2-settings';
    $active_group = array_key_first($groups);
    if (strpos($page, 'dream2-settings-') === 0) {
        $active_group = substr($page, strlen('dream2-settings-'));
    } elseif (isset($_GET['section'])) {
        $active_group = sanitize_key(wp_unslash($_GET['section']));
    }
    if (!isset($groups[$active_group])) {
        $active_group = array_key_first($groups);
    }
    $submitted_fields = array();
    foreach ($registry as $name => $field) {
        if ($field['group'] === $active_group && !in_array($name, $link_fields, true)) {
            $submitted_fields[] = $name;
        }
    }
    $platform_notes = array(
        'enable_busuanzi' => '不蒜子为第三方前端统计服务，开启后从接入并被访问时开始累计，无法继承现有浏览量历史；仅新站点或不需要沿用历史统计时推荐开启。',
        'enable_private_comment' => '关闭后仅禁止提交新的私密评论并隐藏前台复选框，已有私密评论仍保持私密。',
        'avatar_privacy' => '开启后将头像匿名缓存到本站，保护访客标识，但会增加服务器负担，推荐搭配 CDN 使用。关闭后恢复第三方直连，服务器负担较低，但部分头像地址可能暴露 QQ 号或账户标识。',
        'link_notify_recovered' => '友链恢复时会邮件通知对应博客管理员',
        'link_notify_one_way_reminder' => '单向友链达到提醒天数后通知对应博客管理员',
        'link_notify_one_way_deleted' => '单向友链达到删除天数后通知对应博客管理员，并抄送网站管理员',
        'link_notify_lost' => '友链进入失联分组时会邮件通知对应博客管理员',
    );
    $font_preset_value = array_key_exists('font_preset', $options)
        ? (string) $options['font_preset']
        : (!empty($options['web_font']) || !empty($options['custom_font']) ? 'custom' : 'system');
    ?>
    <div class="wrap dream2-settings-wrap dream2-options-wrap">
    <header class="dream2-options-header"><h1>梦屿主题设置</h1><span class="dream2-version">版本 <?php echo esc_html(DREAM2_MXIN_VERSION); ?></span></header>
    <form method="post" action="options.php"><?php settings_fields('dream2_options_group'); ?>
    <input type="hidden" name="dream2_options_fields" value="<?php echo esc_attr(implode(',', $submitted_fields)); ?>">
    <?php if($active_group === 'ai'): ?><input type="hidden" name="dream2_ai_test_token" value="" data-dream-ai-test-token><?php endif; ?>
    <div class="dream2-options-layout is-sidebar-extracted">
        <div class="dream2-options-content">
        <?php
        $group = $active_group;
        $group_label = $groups[$active_group];
        $subgroup_definitions = dream2_mxin_settings_subgroups()[$group] ?? array();
        $settings_subgroups = dream2_mxin_prepare_settings_subgroups($subgroup_definitions, $submitted_fields);
        ?>
        <section class="dream2-options-section is-active" data-section="<?php echo esc_attr($group); ?>">
        <h2><?php echo esc_html($group_label); ?></h2>
        <div class="dream2-settings-subgroups">
        <?php foreach($settings_subgroups as $subgroup): ?>
        <section class="dream2-settings-subgroup">
        <h3><?php echo esc_html($subgroup['label']); ?></h3>
        <div class="dream2-option-group"><div class="dream2-option-grid">
        <?php foreach($subgroup['fields'] as $name): $field=$registry[$name]; $value=array_key_exists($name,$options)?$options[$name]:$field['default']; if(in_array($name,array('ai_search_prompt','ai_summary_prompt'),true)) $value=dream2_mxin_ai_feature_prompt_value($name,$value); if($name==='ai_provider' && !array_key_exists($name,$options)) $value = dream2_mxin_ai_provider_for_base_url($options['ai_base_url'] ?? ''); if($name==='load_progress') $value = dream2_mxin_normalize_load_progress($value); if($name==='enable_hitokoto') $value = dream2_mxin_normalize_hitokoto_mode($value); ?>
        <?php if($field['type']==='emoji_groups'): $emoji_value = function_exists('dream2_mxin_comment_emoji_groups') ? dream2_mxin_comment_emoji_groups() : (array_key_exists($name,$options) && is_array($value) ? $value : dream2_mxin_default_emoji_groups()); ?><div class="dream2-option dream2-option-wide"><label><strong><?php echo esc_html($field['label']); ?></strong></label><div class="dream2-emoji-manager" data-groups="<?php echo esc_attr(wp_json_encode($emoji_value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>"><input class="dream2-emoji-data" type="hidden" name="dream2_options[<?php echo esc_attr($name); ?>]" value="<?php echo esc_attr(wp_json_encode($emoji_value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>"><input class="dream2-emoji-files" type="file" accept="image/png,image/jpeg,image/gif,image/webp,image/avif" multiple hidden><div class="dream2-emoji-groups"></div><div class="dream2-emoji-actions"><button type="button" class="button dream2-emoji-add-group">添加分组</button><button type="button" class="button dream2-emoji-import-files">导入表情</button><button type="button" class="button dream2-emoji-scan">扫描目录</button></div></div></div>
        <?php elseif($field['type']==='repeater'): $repeater_items = dream2_mxin_normalize_repeater($value, $name); $repeater_max = dream2_mxin_repeater_max($name); ?><div class="dream2-option dream2-option-wide"><label><strong><?php echo esc_html($field['label']); ?></strong></label><div class="dream2-repeater" data-dream-repeater="<?php echo esc_attr($name); ?>" data-max="<?php echo esc_attr($repeater_max); ?>"><div class="dream2-repeater-items" data-dream-repeater-items><?php foreach($repeater_items as $item_index=>$item) dream2_mxin_render_repeater_item($name,$item_index,$item); ?></div><div class="dream2-repeater-actions"><button type="button" class="button" data-dream-repeater-add>添加项目</button></div><template data-dream-repeater-template><?php dream2_mxin_render_repeater_item($name,'__INDEX__'); ?></template></div></div>
        <?php else: $is_custom_font_field = in_array($name, array('web_font', 'custom_font'), true); $color_enabled = !empty($options['enable_color_character']); $hitokoto_mode = dream2_mxin_normalize_hitokoto_mode($options['enable_hitokoto'] ?? '0'); $hitokoto_enabled = $color_enabled && $hitokoto_mode !== '0'; $hitokoto_custom = $hitokoto_enabled && $hitokoto_mode === 'custom'; $option_attrs = $is_custom_font_field ? ' data-dream-font-custom' : ''; if ($name === 'color_character') $option_attrs .= ' data-dream-color-character-field'; if ($name === 'hitokoto_category') $option_attrs .= ' data-dream-hitokoto-field'; if ($name === 'hitokoto_custom_url') $option_attrs .= ' data-dream-hitokoto-custom-field'; ?><div class="dream2-option<?php echo $field['type'] === 'textarea' ? ' dream2-option-wide' : ''; ?>"<?php echo $option_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><label for="<?php echo esc_attr($field['type'] === 'password' ? 'mailserver_pass' : 'dream2-' . $name); ?>"><strong><?php echo esc_html($field['label']); ?></strong></label>
        <?php if($field['type']==='user_select'): $user_choices = dream2_mxin_ai_user_choices(); ?><select id="dream2-<?php echo esc_attr($name); ?>" name="dream2_options[<?php echo esc_attr($name); ?>]"><?php foreach($user_choices as $choice=>$choice_label): ?><option value="<?php echo esc_attr($choice); ?>" <?php selected((string) $value,(string) $choice); ?>><?php echo esc_html($choice_label); ?></option><?php endforeach; ?></select>
        <?php elseif($name==='ai_model'): ?><select id="dream2-ai_model" name="dream2_options[ai_model]" data-dream-ai-model><option value=""><?php echo (string) $value === '' ? '请先测试连接' : '请选择模型'; ?></option><?php if((string) $value !== ''): ?><option value="<?php echo esc_attr($value); ?>" selected><?php echo esc_html($value); ?></option><?php endif; ?></select>
        <?php elseif($field['type']==='select'): $is_hitokoto_select_disabled = $name === 'hitokoto_category' && (!$hitokoto_enabled || $hitokoto_custom); ?><select id="dream2-<?php echo esc_attr($name); ?>" name="dream2_options[<?php echo esc_attr($name); ?>]"<?php echo $name === 'font_preset' ? ' data-dream-font-preset' : ''; ?><?php echo $name === 'ai_provider' ? ' data-dream-ai-provider' : ''; ?><?php echo $is_hitokoto_select_disabled ? ' disabled aria-disabled="true"' : ''; ?>><?php foreach($field['choices'] as $choice=>$choice_label): ?><option value="<?php echo esc_attr($choice); ?>" <?php selected($name === 'font_preset' ? $font_preset_value : $value,$choice); ?>><?php echo esc_html($choice_label); ?></option><?php endforeach; ?></select>
        <?php elseif($field['type']==='radio'): $radio_value = !empty($field['boolean']) ? (filter_var($value,FILTER_VALIDATE_BOOLEAN)?'1':'0') : (string)$value; ?><div class="dream2-radio-group"><?php foreach($field['choices'] as $choice=>$choice_label): ?><label><input type="radio" name="dream2_options[<?php echo esc_attr($name); ?>]" value="<?php echo esc_attr($choice); ?>" <?php checked($radio_value,(string)$choice); ?>> <span><?php echo esc_html($choice_label); ?></span></label><?php endforeach; ?><?php if (isset($platform_notes[$name])) : ?><span class="dream2-help-tooltip" tabindex="0" aria-label="<?php echo esc_attr($platform_notes[$name]); ?>" data-tooltip="<?php echo esc_attr($platform_notes[$name]); ?>">?</span><?php endif; ?></div>
        <?php elseif($field['type']==='range'): $range_suffix = (string) ($field['attributes']['suffix'] ?? ''); ?><div class="dream2-range-field"><input id="dream2-<?php echo esc_attr($name); ?>" type="range" name="dream2_options[<?php echo esc_attr($name); ?>]" value="<?php echo esc_attr($value); ?>" min="<?php echo esc_attr($field['attributes']['min'] ?? 0); ?>" max="<?php echo esc_attr($field['attributes']['max'] ?? 100); ?>" step="<?php echo esc_attr($field['attributes']['step'] ?? 1); ?>" data-dream-range data-suffix="<?php echo esc_attr($range_suffix); ?>"><output for="dream2-<?php echo esc_attr($name); ?>"><?php echo esc_html((string) $value . $range_suffix); ?></output></div>
        <?php elseif($field['type']==='number'): ?><input id="dream2-<?php echo esc_attr($name); ?>" class="regular-text" type="number" name="dream2_options[<?php echo esc_attr($name); ?>]" value="<?php echo esc_attr($value); ?>" min="<?php echo esc_attr($field['attributes']['min'] ?? 0); ?>"<?php echo isset($field['attributes']['max']) ? ' max="' . esc_attr($field['attributes']['max']) . '"' : ''; ?> step="<?php echo esc_attr($field['attributes']['step'] ?? 1); ?>">
        <?php elseif($field['type']==='textarea'): $is_hitokoto_disabled_field = $name === 'color_character' && (!$color_enabled || $hitokoto_enabled); $is_ai_prompt = in_array($name, array('ai_system_prompt', 'ai_search_prompt', 'ai_summary_prompt'), true); $is_feature_prompt = in_array($name, array('ai_search_prompt', 'ai_summary_prompt'), true); $textarea_rows = $name === 'ai_system_prompt' ? 10 : ($is_ai_prompt ? 7 : 4); ?><?php if ($is_ai_prompt) : ?><div class="dream2-ai-system-prompt-frame"><?php endif; ?><textarea id="dream2-<?php echo esc_attr($name); ?>" class="large-text code" rows="<?php echo esc_attr((string) $textarea_rows); ?>" name="dream2_options[<?php echo esc_attr($name); ?>]"<?php echo $is_hitokoto_disabled_field ? ' disabled aria-disabled="true"' : ''; ?><?php echo $is_feature_prompt ? ' readonly aria-readonly="true"' : ''; ?>><?php echo esc_textarea($value); ?></textarea><?php if ($is_ai_prompt) : ?></div><?php endif; ?><?php if ($is_feature_prompt) : ?><button type="button" class="button dream2-ai-prompt-edit" data-dream-ai-prompt-edit="dream2-<?php echo esc_attr($name); ?>" data-prompt-label="<?php echo esc_attr($field['label']); ?>" aria-haspopup="dialog" aria-controls="dream2-ai-prompt-dialog">编辑提示词</button><p class="description">默认只读。清空并保存会恢复默认提示词；修改后相关缓存将重新生成。</p><?php elseif ($name === 'ai_system_prompt') : ?><p class="description">角色卡可选；配置后同时用于站内问答和文章总结，留空时不发送角色设定。</p><?php endif; ?>
        <?php elseif($field['type']==='date'): ?><input id="dream2-<?php echo esc_attr($name); ?>" class="regular-text dream2-date-picker-only" type="date" inputmode="none" autocomplete="off" name="dream2_options[<?php echo esc_attr($name); ?>]" value="<?php echo esc_attr($value); ?>">
        <?php elseif($field['type']==='image'): ?><div class="dream2-hoshi-media-field"><input id="dream2-<?php echo esc_attr($name); ?>" class="regular-text" type="url" name="dream2_options[<?php echo esc_attr($name); ?>]" value="<?php echo esc_attr($value); ?>"><button type="button" class="button dream2-media-button" data-target="dream2-<?php echo esc_attr($name); ?>">上传文件</button></div>
        <?php elseif($field['type']==='password'): ?><div class="mailserver-pass-wrap"><span class="wp-pwd"><input id="mailserver_pass" class="regular-text ltr" type="password" name="dream2_options[<?php echo esc_attr($name); ?>]" value="<?php echo esc_attr($value); ?>" autocomplete="off" autocapitalize="off" spellcheck="false"><button type="button" class="button wp-hide-pw hide-if-no-js" data-toggle="0" aria-label="显示密码"><span class="dashicons dashicons-visibility" aria-hidden="true"></span><span class="text">显示</span></button></span></div>
        <?php else: $is_hitokoto_url_disabled = $name === 'hitokoto_custom_url' && (!$hitokoto_enabled || !$hitokoto_custom); $input_type = in_array($field['type'], array('url', 'email', 'password'), true) ? $field['type'] : 'text'; ?><input id="dream2-<?php echo esc_attr($name); ?>" class="<?php echo $field['type']==='color'?'dream2-color':'regular-text'; ?>" type="<?php echo esc_attr($input_type); ?>" name="dream2_options[<?php echo esc_attr($name); ?>]" value="<?php echo esc_attr($value); ?>"<?php echo $is_custom_font_field && $font_preset_value !== 'custom' ? ' readonly' : ''; ?><?php echo $is_hitokoto_url_disabled ? ' disabled aria-disabled="true"' : ''; ?>>
        <?php endif; ?></div><?php endif; ?>
        <?php endforeach; ?>
        <?php if($group === 'ai' && in_array('ai_provider', $subgroup['fields'], true)): ?><div class="dream2-option dream2-option-wide dream2-ai-test"><button type="button" class="button" data-dream-ai-test data-nonce="<?php echo esc_attr(wp_create_nonce('dream2_mxin_test_ai_connection')); ?>"><span class="dashicons dashicons-controls-repeat" aria-hidden="true"></span><span>测试并获取模型</span></button><span class="spinner" aria-hidden="true"></span><span class="dream2-ai-test-result" role="status" aria-live="polite"></span></div><?php endif; ?>
        </div></div></section>
        <?php endforeach; ?>
        </div>
        </section>
        </div>
    </div>
    <div class="dream2-options-actions"><?php echo get_submit_button('保存主题设置', 'primary', 'submit', false, $active_group === 'ai' ? array('data-dream-ai-save'=>'') : array()); ?></div></form>
    <?php if ($active_group === 'ai') : ?><dialog id="dream2-ai-prompt-dialog" class="dream2-ai-prompt-dialog" aria-labelledby="dream2-ai-prompt-dialog-title"><h2 id="dream2-ai-prompt-dialog-title">确认编辑<span data-dream-ai-prompt-name></span>？</h2><p>修改内置提示词可能削弱角色一致性、站内资料约束与防注入要求，导致回答不准确或泄露内部信息。不要在提示词中写入密钥。</p><div class="dream2-ai-prompt-dialog-actions"><button type="button" class="button" data-dream-ai-prompt-cancel>取消</button><button type="button" class="button button-primary" data-dream-ai-prompt-confirm>了解风险，继续编辑</button></div></dialog><?php endif; ?></div>
    <script>
    jQuery(function($){
        var aiProviderBaseUrls=<?php echo wp_json_encode($ai_provider_base_urls, JSON_UNESCAPED_SLASHES); ?>;
        var promptDialog=document.getElementById('dream2-ai-prompt-dialog'),pendingPrompt=null,pendingEditButton=null;
        function unlockFeaturePrompt(){
            if(!pendingPrompt||!pendingEditButton){return}
            pendingPrompt.readOnly=false;
            pendingPrompt.removeAttribute('aria-readonly');
            pendingEditButton.textContent='已解锁编辑';
            pendingEditButton.disabled=true;
            pendingPrompt.focus();
            pendingPrompt=null;
            pendingEditButton=null
        }
        $(document).on('click','[data-dream-ai-prompt-edit]',function(){
            var field=document.getElementById(this.dataset.dreamAiPromptEdit);
            if(!field||!field.readOnly){return}
            pendingPrompt=field;
            pendingEditButton=this;
            if(promptDialog&&typeof promptDialog.showModal==='function'){
                promptDialog.querySelector('[data-dream-ai-prompt-name]').textContent=this.dataset.promptLabel||'提示词';
                promptDialog.showModal()
            }else if(window.confirm('修改内置提示词可能影响角色口吻、内容准确性和安全约束。不要写入密钥。确认继续编辑？')){
                unlockFeaturePrompt()
            }else{pendingPrompt=null;pendingEditButton=null}
        });
        $(document).on('click','[data-dream-ai-prompt-confirm]',function(){
            var field=pendingPrompt;
            unlockFeaturePrompt();
            if(promptDialog){promptDialog.close()}
            if(field){field.focus()}
        });
        $(document).on('click','[data-dream-ai-prompt-cancel]',function(){
            if(promptDialog){promptDialog.close()}
            pendingPrompt=null;
            pendingEditButton=null
        });
        if(promptDialog){promptDialog.addEventListener('close',function(){pendingPrompt=null;pendingEditButton=null})}
        function initDreamColors(){
            if($.fn.wpColorPicker){$('.dream2-color').wpColorPicker()}
        }
        function resetAiModels(){
            $('[data-dream-ai-model]').empty().append($('<option>',{value:'',text:'请先测试连接'}))
        }
        function populateAiModels(models){
            var select=$('[data-dream-ai-model]'),current=select.val()||'';
            select.empty().append($('<option>',{value:'',text:'请选择模型'}));
            $.each(models||[],function(_,model){select.append($('<option>',{value:model,text:model}))});
            select.val(models.indexOf(current)!==-1?current:(models[0]||''))
        }
        function syncAiProvider(resetModels){
            var provider=$('[data-dream-ai-provider]').val();
            var baseUrl=$('#dream2-ai_base_url');
            if(!baseUrl.length){return}
            if(aiProviderBaseUrls[provider]){
                baseUrl.val(aiProviderBaseUrls[provider]).prop('readonly',true).attr('aria-readonly','true')
            }else{
                baseUrl.prop('readonly',false).removeAttr('aria-readonly')
            }
            if(resetModels){resetAiModels()}
        }
        function setAiTestResult(message,state){
            $('[data-dream-ai-test]').closest('.dream2-ai-test').find('.dream2-ai-test-result').text(message||'').removeClass('is-success is-error').addClass(state?'is-'+state:'')
        }
        function resetAiVerification(){
            $('[data-dream-ai-test-token]').val('');
            $('[data-dream-ai-save]').prop('disabled',true)
        }
        function toggleDreamFontFields(){
            var custom=$('[data-dream-font-preset]').val()==='custom';
            $('[data-dream-font-custom] input').prop('readonly',!custom)
        }
        function toggleHitokotoFields(){
            var colorEnabled=$('input[name="dream2_options[enable_color_character]"]:checked').val()==='1';
            var hitokoto=$('input[name="dream2_options[enable_hitokoto]"]');
            var hitokotoMode=hitokoto.filter(':checked').val()||'0';
            var hitokotoEnabled=colorEnabled&&hitokotoMode!=='0';
            var category=$('#dream2-hitokoto_category');
            var customEnabled=colorEnabled&&hitokotoMode==='custom';
            var officialEnabled=hitokotoEnabled&&!customEnabled;
            hitokoto.prop('disabled',!colorEnabled).attr('aria-disabled',colorEnabled?'false':'true');
            category.prop('disabled',!officialEnabled).attr('aria-disabled',officialEnabled?'false':'true');
            $('#dream2-hitokoto_custom_url').prop('disabled',!customEnabled).attr('aria-disabled',customEnabled?'false':'true');
            $('#dream2-color_character').prop('disabled',!colorEnabled||hitokotoEnabled).attr('aria-disabled',(!colorEnabled||hitokotoEnabled)?'true':'false')
        }
        function refreshRepeater(repeater){
            var items=repeater.find('[data-dream-repeater-item]'),max=parseInt(repeater.data('max'),10)||0;
            items.each(function(index){$(this).find('[data-dream-repeater-label]').text('项目 '+(index+1))});
            repeater.find('[data-dream-repeater-add]').prop('disabled',max>0&&items.length>=max);
        }
        initDreamColors();
        syncAiProvider(false);
        toggleDreamFontFields();
        toggleHitokotoFields();
        $('[data-dream-range]').each(function(){
            $(this).next('output').text(this.value+($(this).data('suffix')||''))
        });
        $('.dream2-repeater').each(function(){refreshRepeater($(this))});
        $(window).on('load',initDreamColors);
        $('[data-dream-ai-provider]').on('change',function(){syncAiProvider(true);resetAiVerification();setAiTestResult('','')});
        $(document).on('input','#dream2-ai_base_url,#mailserver_pass',function(){resetAiModels();resetAiVerification();setAiTestResult('','')});
        $(document).on('click','[data-dream-ai-test]',function(){
            var button=$(this),container=button.closest('.dream2-ai-test'),spinner=container.find('.spinner');
            button.prop('disabled',true);
            spinner.addClass('is-active');
            resetAiVerification();
            setAiTestResult('正在测试连接...','');
            $.post(ajaxurl,{
                action:'dream2_mxin_test_ai_connection',
                nonce:button.data('nonce'),
                provider:$('#dream2-ai_provider').val()||'',
                base_url:$('#dream2-ai_base_url').val()||'',
                api_key:$('#mailserver_pass').val()||''
            }).done(function(response){
                var message=response&&response.data&&response.data.message?response.data.message:'连接成功。';
                if(response&&response.success){
                    populateAiModels(response.data.models||[]);
                    $('[data-dream-ai-test-token]').val(response.data.test_token||'');
                    $('[data-dream-ai-save]').prop('disabled',!response.data.test_token)
                }
                setAiTestResult(message,response&&response.success?'success':'error')
            }).fail(function(xhr){
                var response=xhr.responseJSON;
                var message=response&&response.data&&response.data.message?response.data.message:'连接失败，请稍后重试。';
                setAiTestResult(message,'error')
            }).always(function(){
                button.prop('disabled',false);
                spinner.removeClass('is-active')
            })
        });
        $('[data-dream-font-preset]').on('change',toggleDreamFontFields);
        $(document).on('input change','[data-dream-range]',function(){
            $(this).next('output').text(this.value+($(this).data('suffix')||''))
        });
        $(document).on('change','input[name="dream2_options[enable_color_character]"],input[name="dream2_options[enable_hitokoto]"],#dream2-hitokoto_category',toggleHitokotoFields);
        $('.dream2-date-picker-only').on('keydown paste drop',function(e){e.preventDefault()}).on('click focus',function(){if(this.showPicker){try{this.showPicker()}catch(e){}}});
        $(document).on('click','.dream2-media-button',function(){
            var button=$(this),target=button.closest('.dream2-hoshi-media-field').find('input[type="url"]'),targetId=button.data('target'),frame;
            if(targetId){target=$('#'+targetId)}
            frame=wp.media({title:'选择媒体',button:{text:'使用此文件'},multiple:false});
            frame.on('select',function(){target.val(frame.state().get('selection').first().toJSON().url)});
            frame.open()
        }).on('click','[data-dream-repeater-add]',function(){
            var repeater=$(this).closest('[data-dream-repeater]'),items=repeater.find('[data-dream-repeater-items]'),index=Date.now(),template=repeater.find('[data-dream-repeater-template]').html().replaceAll('__INDEX__',index);
            items.append(template);
            refreshRepeater(repeater)
        }).on('click','[data-dream-repeater-remove]',function(){
            var repeater=$(this).closest('[data-dream-repeater]');
            $(this).closest('[data-dream-repeater-item]').remove();
            refreshRepeater(repeater)
        })
    });
    </script>
    <?php
}

function dream2_mxin_render_link_settings_page() {
    if (!current_user_can('edit_theme_options')) return;
    $registry = dream2_mxin_settings_registry(); $options = get_option('dream2_options', array());
    $system_cron_command = dream2_mxin_system_cron_command();
    $all_link_terms = get_terms(array('taxonomy' => 'link_category', 'hide_empty' => false));
    $all_link_terms = is_wp_error($all_link_terms) ? array() : $all_link_terms;
    $link_config_fields = dream2_mxin_link_settings_fields();
    $link_config_subgroups = dream2_mxin_prepare_settings_subgroups(dream2_mxin_link_settings_subgroups(), $link_config_fields);
    $link_terms = dream2_mxin_friend_link_terms();
    $link_term_ids = array_map('intval', wp_list_pluck($link_terms, 'term_id'));
    $lost_term_id = absint(dream2_get('link_lost_category', dream2_mxin_default_link_category_id('失联博客')));
    $bookmarks = $link_term_ids ? get_bookmarks(array(
        'category'       => implode(',', $link_term_ids),
        'orderby'        => 'name',
        'order'          => 'ASC',
        'hide_invisible' => false,
    )) : array();
    $bookmarks_by_term = array();
    foreach ($link_terms as $term) {
        $bookmarks_by_term[(int) $term->term_id] = array();
    }
    foreach ($bookmarks as $bookmark) {
        $term_ids = wp_get_object_terms((int) $bookmark->link_id, 'link_category', array('fields' => 'ids'));
        $term_id = is_wp_error($term_ids) || empty($term_ids) ? 0 : (int) $term_ids[0];
        if (isset($bookmarks_by_term[$term_id])) {
            $bookmarks_by_term[$term_id][] = $bookmark;
        }
    }
    $message = isset($_GET['dream2_link_message']) ? sanitize_key(wp_unslash($_GET['dream2_link_message'])) : '';
    $interval_value = (string) ($options['link_check_interval'] ?? ($registry['link_check_interval']['default'] ?? 'daily'));
    $interval_choices = $registry['link_check_interval']['choices'] ?? array();
    $interval_label = $interval_choices[$interval_value] ?? '每天';
    $interval_days = array('daily' => 1, 'three_days' => 3, 'weekly' => 7)[$interval_value] ?? 1;
    $failure_threshold = max(1, absint($options['link_check_failure_threshold'] ?? ($registry['link_check_failure_threshold']['default'] ?? 3)));
    $notice_days = max(1, absint($options['link_one_way_notice_days'] ?? 1));
    $delete_days = max(1, absint($options['link_one_way_delete_days'] ?? 7));
    $one_way_rule = sprintf('反链连续检测失败 %d 次后迁移；进入 %d 天后提醒，进入 %d 天后删除并通知', $failure_threshold, $notice_days, $delete_days);
    $lost_rule = sprintf('站点连续访问失败 %d 次后直接迁移到失联博客（按当前周期约 %d 天）', $failure_threshold, $interval_days * $failure_threshold);
    ?>
    <div class="wrap dream2-settings-wrap dream2-options-wrap dream2-link-manager" data-dream-link-ajax="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" data-dream-link-nonce="<?php echo esc_attr(wp_create_nonce('dream2_link_check')); ?>">
    <header class="dream2-options-header"><h1>梦屿友链管理</h1><span class="dream2-version">版本 <?php echo esc_html(DREAM2_MXIN_VERSION); ?></span></header>
    <div class="dream2-link-manager-content">
    <?php if ($message) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html(array('saved'=>'操作已保存。','deleted'=>'操作已删除。','missing'=>'网站名称和网站地址不能为空。','error'=>'友链保存失败。','group_not_empty'=>'分组下仍有链接，不能删除。','category_saved'=>'分类已创建并加入梦屿友链管理。','category_missing'=>'分类名称不能为空。','category_error'=>'分类创建失败。')[$message] ?? '操作完成。'); ?></p></div><?php endif; ?>
    <div class="dream2-link-page-heading"><h2>友链管理</h2><a class="button" href="<?php echo esc_url(home_url('/links')); ?>" target="_blank" rel="noopener noreferrer">预览前台</a></div>
    <div class="dream2-link-toolbar">
        <div class="dream2-link-toolbar-primary">
            <button type="button" class="button" data-dream-link-sort>调整排序</button>
            <button type="button" class="button" data-dream-link-new-category>新建分类</button>
            <button type="button" class="button" data-dream-link-import>批量导入</button>
            <button type="button" class="button" data-dream-link-check-all>检测全部</button>
            <button type="button" class="button" data-dream-link-config>友链配置</button>
        </div>
        <div class="dream2-link-toolbar-secondary">
            <label>状态：<select data-dream-link-filter><option value="all">全部</option><option value="access-error">访问异常的链接</option><option value="backlink-missing">没有反链的链接</option></select></label>
            <button type="button" class="button" data-dream-link-refresh>刷新</button>
        </div>
    </div>
    <section class="dream2-link-modal dream2-link-config" hidden><div class="dream2-link-modal-card dream2-link-config-card"><form method="post" action="options.php"><?php settings_fields('dream2_options_group'); ?>
    <input type="hidden" name="dream2_options_fields" value="<?php echo esc_attr(implode(',', $link_config_fields)); ?>">
    <section class="dream2-settings-panel"><h2>友链配置</h2><div class="dream2-settings-subgroups dream2-link-config-subgroups">
    <?php foreach($link_config_subgroups as $subgroup): ?>
    <section class="dream2-settings-subgroup"><h3><?php echo esc_html($subgroup['label']); ?><?php if (($subgroup['label'] ?? '') === '自动检测') : ?><button type="button" class="dream2-help-tooltip dream2-cron-help" data-tooltip="点击查看自动检测配置说明" data-dream-link-cron-help aria-label="自动检测配置说明">?</button><?php endif; ?></h3><div class="dream2-option-group"><div class="dream2-option-grid">
    <?php foreach($subgroup['fields'] as $name): $field=$registry[$name]; $value=array_key_exists($name,$options)?$options[$name]:$field['default']; $option_attrs = $name === 'enable_link_application' ? ' data-dream-link-application-option' : ''; ?><div class="dream2-option<?php echo $field['type']==='textarea' ? ' dream2-option-wide' : ''; ?>"<?php echo $option_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><label for="dream2-<?php echo esc_attr($name); ?>"><strong><?php echo esc_html($field['label']); ?></strong></label>
    <?php if($field['type']==='radio'): $radio_value = !empty($field['boolean']) ? (filter_var($value,FILTER_VALIDATE_BOOLEAN)?'1':'0') : (string)$value; $is_link_application_disabled = $name === 'enable_link_application' && !filter_var($options['link_enable_comment'] ?? ($registry['link_enable_comment']['default'] ?? '1'), FILTER_VALIDATE_BOOLEAN); if ($is_link_application_disabled) $radio_value = '0'; ?><?php if ($is_link_application_disabled) : ?><input type="hidden" name="dream2_options[<?php echo esc_attr($name); ?>]" value="0"><?php endif; ?><div class="dream2-radio-group"><?php foreach($field['choices'] as $choice=>$choice_label): ?><label><input type="radio" name="dream2_options[<?php echo esc_attr($name); ?>]" value="<?php echo esc_attr($choice); ?>" <?php checked($radio_value,(string)$choice); ?><?php echo $is_link_application_disabled ? ' disabled aria-disabled="true"' : ''; ?>> <span><?php echo esc_html($choice_label); ?></span></label><?php endforeach; ?><?php if ($name === 'enable_link_application') : ?><span class="description" data-dream-link-application-hint<?php echo $is_link_application_disabled ? '' : ' hidden'; ?>>需先开启友链页面评论</span><?php endif; ?></div>
    <?php elseif(in_array($name, array('link_friend_category', 'link_one_way_category', 'link_lost_category'), true)): ?><select id="dream2-<?php echo esc_attr($name); ?>" name="dream2_options[<?php echo esc_attr($name); ?>]"><?php if ($name === 'link_one_way_category') : ?><option value="0" <?php selected((int) $value, 0); ?>>未设置</option><?php endif; ?><?php foreach($all_link_terms as $term): ?><option value="<?php echo esc_attr((string) $term->term_id); ?>" <?php selected((int) $value, (int) $term->term_id); ?>><?php echo esc_html($term->name); ?></option><?php endforeach; ?></select>
    <?php elseif($field['type']==='select'): ?><select id="dream2-<?php echo esc_attr($name); ?>" name="dream2_options[<?php echo esc_attr($name); ?>]"><?php foreach($field['choices'] as $choice=>$choice_label): ?><option value="<?php echo esc_attr((string) $choice); ?>" <?php selected((string) $value, (string) $choice); ?>><?php echo esc_html($choice_label); ?></option><?php endforeach; ?></select>
    <?php elseif($field['type']==='number'): ?><input id="dream2-<?php echo esc_attr($name); ?>" class="small-text" type="number" name="dream2_options[<?php echo esc_attr($name); ?>]" value="<?php echo esc_attr((string) $value); ?>" min="<?php echo esc_attr((string) ($field['attributes']['min'] ?? 0)); ?>" max="<?php echo esc_attr((string) ($field['attributes']['max'] ?? '')); ?>" step="<?php echo esc_attr((string) ($field['attributes']['step'] ?? 1)); ?>">
    <?php elseif($field['type']==='textarea'): ?><textarea id="dream2-<?php echo esc_attr($name); ?>" class="large-text code" rows="6" name="dream2_options[<?php echo esc_attr($name); ?>]"<?php echo $name === 'link_application_blacklist' ? ' placeholder="每行一个域名，例如 example.com"' : ''; ?>><?php echo esc_textarea($value); ?></textarea><?php if ($name === 'link_application_blacklist') : ?><p class="description">仅拦截已填写的域名；www 前缀不作区分，例如 www.mxin.me 与 mxin.me 视为同一域名。</p><?php endif; ?>
    <?php elseif($field['type']==='image'): ?><div class="dream2-hoshi-media-field"><input id="dream2-<?php echo esc_attr($name); ?>" class="regular-text" type="url" name="dream2_options[<?php echo esc_attr($name); ?>]" value="<?php echo esc_attr($value); ?>"><button type="button" class="button dream2-media-button" data-target="dream2-<?php echo esc_attr($name); ?>">选择媒体</button></div>
    <?php else: $input_type = in_array($field['type'], array('url', 'email', 'password'), true) ? $field['type'] : 'text'; ?><input id="dream2-<?php echo esc_attr($name); ?>" class="regular-text" type="<?php echo esc_attr($input_type); ?>" name="dream2_options[<?php echo esc_attr($name); ?>]" value="<?php echo esc_attr($value); ?>">
    <?php endif; ?></div><?php endforeach; ?></div></div></section>
    <?php endforeach; ?></div></section><p class="submit dream2-link-config-actions"><button type="button" class="button" data-dream-link-close>取消</button><button type="submit" name="submit" class="button button-primary">保存设置</button></p></form></div></section>
    <section class="dream2-link-modal dream2-link-new" hidden><div class="dream2-link-modal-card"><h2>新增友链</h2><form id="dream2-link-new-form" method="post" action="<?php echo esc_url(dream2_mxin_link_manager_url()); ?>" class="dream2-link-form"><?php wp_nonce_field('dream2_link_manager'); ?><input type="hidden" name="dream2_link_manager_action" value="save_link">
    <label>网站名称<input class="regular-text" name="link_name" type="text" required></label><label>网站地址<input class="regular-text" name="link_url" type="url" required placeholder="https://example.com/"></label><label>网站图标<input class="regular-text" name="link_image" type="url"></label><label>管理员邮箱<input class="regular-text" name="admin_email" type="email"></label><label class="dream2-link-field-wide">网站介绍<textarea class="large-text" name="link_description" rows="3"></textarea></label><label>友链分组<select name="link_category"><option value="">未分组</option><?php foreach ($link_terms as $term) : ?><option value="<?php echo esc_attr((string) $term->term_id); ?>"><?php echo esc_html($term->name); ?></option><?php endforeach; ?></select></label><label>反链检测页（可选）<input class="regular-text" name="backlink_url" type="url" placeholder="留空则不检测反链"></label></form><div class="dream2-link-modal-footer"><button type="button" class="button" data-dream-link-close>取消</button><button type="submit" class="button button-primary" form="dream2-link-new-form">新增友链</button></div></div></section>
    <section class="dream2-link-modal dream2-link-new-category-modal" hidden><div class="dream2-link-modal-card dream2-link-action-card"><h2>新建分类</h2><form id="dream2-link-new-category-form" method="post" action="<?php echo esc_url(dream2_mxin_link_manager_url()); ?>" class="dream2-link-form"><?php wp_nonce_field('dream2_link_manager'); ?><input type="hidden" name="dream2_link_manager_action" value="save_category"><label>分类名称<input class="regular-text" name="link_category_name" type="text" required placeholder="例如：技术支持"></label></form><div class="dream2-link-modal-footer"><button type="button" class="button" data-dream-link-close>取消</button><button type="submit" class="button button-primary" form="dream2-link-new-category-form">创建分类</button></div></div></section>
    <section class="dream2-link-modal dream2-link-cron-help-modal" hidden><div class="dream2-link-modal-card dream2-link-action-card"><h2>自动检测配置说明</h2><div class="dream2-link-modal-body dream2-cron-help-content"><p><strong>单向友链：</strong>站点可以访问，但反链页异常或未找到本站链接。</p><code><?php echo esc_html($one_way_rule); ?></code><p><strong>失联博客：</strong>友链站点连续访问失败达到阈值后直接迁移。</p><code><?php echo esc_html($lost_rule); ?></code><p><strong>恢复规则：</strong>站点与反链均正常时恢复到友情链接；失联站点恢复但反链仍异常时迁移到单向友链。手动检测只更新即时结果，不推进自动迁移计数。</p><p>提醒与删除在自动检测批次中执行，实际触发时间可能晚于配置天数，最长受自动检测周期影响。</p><p>WordPress Cron 默认需要网站有访问才会触发。想让友链自动检测稳定执行，可以改为服务器系统 Cron 触发。</p><p>在 <code>wp-config.php</code> 添加：</p><code>define('DISABLE_WP_CRON', true);</code><p>然后在服务器系统 Cron 添加定时任务：</p><code><?php echo esc_html($system_cron_command); ?></code></div><div class="dream2-link-modal-footer"><button type="button" class="button button-primary" data-dream-link-close>知道了</button></div></div></section>
    <section class="dream2-link-modal dream2-link-sort-modal" hidden><div class="dream2-link-modal-card dream2-link-action-card"><h2>调整排序</h2><form method="post" action="<?php echo esc_url(dream2_mxin_link_manager_url()); ?>"><?php wp_nonce_field('dream2_link_manager'); ?><input type="hidden" name="dream2_link_manager_action" value="sort_groups"><div class="dream2-link-modal-body dream2-link-sort-list" data-dream-link-sort-list><?php foreach ($link_terms as $term) : $group_links = $bookmarks_by_term[(int) $term->term_id] ?? array(); ?><div class="dream2-link-sort-item dream2-link-sort-link" draggable="true"><span class="dashicons dashicons-menu"></span><strong><?php echo esc_html($term->name); ?></strong><em><?php echo esc_html((string) count($group_links)); ?> 条</em><input type="hidden" name="link_category_order[]" value="<?php echo esc_attr((string) $term->term_id); ?>"></div><?php endforeach; ?></div><p class="submit dream2-link-submit"><button type="button" class="button" data-dream-link-close>取消</button><button type="submit" class="button button-primary">保存</button></p></form></div></section>
    <section class="dream2-link-modal dream2-link-import-modal" hidden><div class="dream2-link-modal-card dream2-link-action-card"><h2>批量导入</h2><div class="dream2-link-modal-body"><textarea class="large-text code dream2-link-import-textarea" rows="9"></textarea></div><p class="submit dream2-link-submit"><button type="button" class="button" data-dream-link-close>取消</button><button type="button" class="button button-primary" data-dream-link-close>导入</button></p></div></section>
    <section class="dream2-link-modal dream2-link-check-modal" hidden><div class="dream2-link-modal-card dream2-link-action-card"><h2>检测全部</h2><div class="dream2-link-modal-body dream2-link-check-panel"><?php foreach ($link_terms as $term) : ?><div class="dream2-link-check-row" data-dream-link-check-term="<?php echo esc_attr((string) $term->term_id); ?>"><strong><?php echo esc_html($term->name); ?></strong><span>等待检测</span></div><?php endforeach; ?></div><p class="submit dream2-link-submit"><button type="button" class="button" data-dream-link-close>关闭</button></p></div></section>
    <div class="dream2-link-groups">
        <?php foreach ($link_terms as $term) : $group_links = $bookmarks_by_term[(int) $term->term_id] ?? array(); ?>
        <section class="dream2-link-group">
            <div class="dream2-link-group-header">
                <strong><?php echo esc_html($term->name); ?>（<?php echo esc_html((string) count($group_links)); ?>）</strong>
                <button type="button" class="button button-primary" data-dream-link-new="<?php echo esc_attr((string) $term->term_id); ?>">新建友链</button>
                <button type="button" class="button" data-dream-link-group-check>检测本组</button>
            </div>
            <?php if (!$group_links) : ?>
                <div class="dream2-link-empty">此分组下暂无链接</div>
            <?php else : ?>
            <div class="dream2-link-grid">
            <?php foreach ($group_links as $bookmark) : ?>
                <?php
                $extra = dream2_mxin_link_extra($bookmark);
                $term_ids = wp_get_object_terms((int) $bookmark->link_id, 'link_category', array('fields' => 'ids'));
                $term_id = is_wp_error($term_ids) || empty($term_ids) ? 0 : (int) $term_ids[0];
                $is_lost = $lost_term_id && $term_id === $lost_term_id;
                $avatar = $is_lost
                    ? dream2_mxin_asset('img/avatar.svg')
                    : ($bookmark->link_image ?: dream2_get('links_default_avatar', dream2_mxin_asset('img/avatar.svg')));
                $verification = $extra['verification'];
                $access_labels = array('pending' => '待检', 'success' => '正常', 'error' => '异常');
                $backlink_labels = array('pending' => '反链待检', 'success' => '反链正常', 'missing' => '无反链', 'error' => '反链异常', 'skip' => '未配置反链');
                $access_state = isset($access_labels[$verification['access']]) ? $verification['access'] : 'pending';
                $backlink_state = $extra['backlink_url'] === '' ? 'skip' : (isset($backlink_labels[$verification['backlink']]) ? $verification['backlink'] : 'pending');
                $overall_state = in_array($verification['status'], array('pending', 'success', 'missing', 'error', 'skip'), true) ? $verification['status'] : 'pending';
                $status_title = trim($verification['message'] . ($verification['checked_at'] ? '；' . $verification['checked_at'] : ''));
                ?>
                <article class="dream2-link-card" data-dream-link-id="<?php echo esc_attr((string) $bookmark->link_id); ?>" data-dream-link-term="<?php echo esc_attr((string) $term_id); ?>" data-dream-link-status="<?php echo esc_attr($overall_state); ?>" data-dream-link-access="<?php echo esc_attr($access_state); ?>" data-dream-link-backlink="<?php echo esc_attr($backlink_state); ?>" data-dream-link-has-backlink="<?php echo esc_attr($extra['backlink_url'] === '' ? '0' : '1'); ?>">
                    <div class="dream2-link-preview" data-dream-link-toggle>
                        <img class="dream2-link-preview-avatar" src="<?php echo esc_url($avatar); ?>" alt="">
                        <span class="dream2-link-preview-body">
                            <strong><?php echo esc_html($bookmark->link_name); ?></strong>
                            <em><?php echo esc_html($bookmark->link_url); ?></em>
                            <span><?php echo esc_html($bookmark->link_description ?: __('这个朋友还没有填写简介。', 'dream2-mxin')); ?></span>
                        </span>
                        <span class="dream2-link-status-dock" title="<?php echo esc_attr($status_title); ?>">
                            <span class="dream2-link-status is-<?php echo esc_attr($access_state); ?>" data-dream-link-access>◉ <?php echo esc_html($access_labels[$access_state]); ?></span>
                            <?php if ($extra['backlink_url'] !== '') : ?>
                                <span class="dream2-link-status is-<?php echo esc_attr($backlink_state); ?>" data-dream-link-backlink>⌁ <?php echo esc_html($backlink_labels[$backlink_state]); ?></span>
                            <?php endif; ?>
                            <a class="dream2-link-visit" href="<?php echo esc_url($bookmark->link_url); ?>" target="_blank" rel="noopener noreferrer" onclick="event.stopPropagation();">访问</a>
                        </span>
                    </div>
                    <section class="dream2-link-modal dream2-link-edit-modal" hidden>
                        <div class="dream2-link-modal-card dream2-link-edit-card">
                            <h2>编辑友链</h2>
                            <form id="dream2-link-save-<?php echo esc_attr((string) $bookmark->link_id); ?>" method="post" action="<?php echo esc_url(dream2_mxin_link_manager_url()); ?>" class="dream2-link-form dream2-link-editor-form">
                                <?php wp_nonce_field('dream2_link_manager'); ?>
                                <input type="hidden" name="dream2_link_manager_action" value="save_link">
                                <input type="hidden" name="link_id" value="<?php echo esc_attr((string) $bookmark->link_id); ?>">
                                <label>网站名称<input class="regular-text" name="link_name" type="text" value="<?php echo esc_attr($bookmark->link_name); ?>" required></label>
                                <label>网站地址<input class="regular-text" name="link_url" type="url" value="<?php echo esc_url($bookmark->link_url); ?>" required></label>
                                <label>网站图标<input class="regular-text" name="link_image" type="url" value="<?php echo esc_url($bookmark->link_image); ?>"></label>
                                <label>管理员邮箱<input class="regular-text" name="admin_email" type="email" value="<?php echo esc_attr($extra['admin_email']); ?>"></label>
                                <label class="dream2-link-field-wide">网站介绍<textarea class="large-text" name="link_description" rows="3"><?php echo esc_textarea($bookmark->link_description); ?></textarea></label>
                                <label>友链分组<select name="link_category"><option value="">未分组</option><?php foreach ($link_terms as $term) : ?><option value="<?php echo esc_attr((string) $term->term_id); ?>" <?php selected($term_id, (int) $term->term_id); ?>><?php echo esc_html($term->name); ?></option><?php endforeach; ?></select></label>
                                <label>反链检测页（可选）<input class="regular-text" name="backlink_url" type="url" value="<?php echo esc_url($extra['backlink_url']); ?>" placeholder="留空则不检测反链"></label>
                            </form>
                            <div class="dream2-link-edit-footer">
                                <form method="post" action="<?php echo esc_url(dream2_mxin_link_manager_url()); ?>" onsubmit="return confirm('确认删除这个友链？');">
                                    <?php wp_nonce_field('dream2_link_manager'); ?>
                                    <input type="hidden" name="dream2_link_manager_action" value="delete_link">
                                    <input type="hidden" name="link_id" value="<?php echo esc_attr((string) $bookmark->link_id); ?>">
                                    <button type="submit" class="button button-link-delete">删除</button>
                                </form>
                                <button type="button" class="button" data-dream-link-check<?php echo $extra['backlink_url'] === '' ? ' disabled title="未配置反链检测页，保存反链检测页后可检测"' : ''; ?>>检测</button>
                                <button type="button" class="button" data-dream-link-close>取消</button>
                                <button type="submit" class="button button-primary" form="dream2-link-save-<?php echo esc_attr((string) $bookmark->link_id); ?>">保存</button>
                            </div>
                        </div>
                    </section>
                </article>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
        <?php endforeach; ?>
        </div>
    </div>
    </div>
    <script>
    jQuery(function($) {
        $('.dream2-media-button').on('click', function() {
            var target = $('#' + $(this).data('target'));
            var frame = wp.media({title: '选择媒体', multiple: false});
            frame.on('select', function() {
                target.val(frame.state().get('selection').first().toJSON().url);
            });
            frame.open();
        });
        $('[data-dream-link-config]').on('click', function() {$('.dream2-link-config').prop('hidden', false);});
        $('[data-dream-link-cron-help]').on('click', function() {$('.dream2-link-cron-help-modal').prop('hidden', false);});
        $('[data-dream-link-sort]').on('click', function() {$('.dream2-link-sort-modal').prop('hidden', false);});
        $('[data-dream-link-new-category]').on('click', function() {$('.dream2-link-new-category-modal').prop('hidden', false);});
        $('[data-dream-link-import]').on('click', function() {$('.dream2-link-import-modal').prop('hidden', false);});
        $('[data-dream-link-new]').on('click', function() {
            var group = $(this).data('dream-link-new');
            if (group) {
                $('.dream2-link-new select[name="link_category"]').val(String(group));
            }
            $('.dream2-link-new').prop('hidden', false);
        });
        $('[data-dream-link-close]').on('click', function() {
            $(this).closest('.dream2-link-modal').prop('hidden', true);
            $('.dream2-link-card').removeClass('is-editing');
        });
        $('.dream2-link-edit-modal,.dream2-link-editor,.dream2-link-actions,.dream2-link-card > form:last-child').on('click', function(e) {
            e.stopPropagation();
        });
        $('[data-dream-link-toggle]').on('click', function(e) {
            e.stopPropagation();
            $('.dream2-link-card').not($(this).closest('.dream2-link-card')).removeClass('is-editing');
            $(this).closest('.dream2-link-card').toggleClass('is-editing');
        });
        $('[data-dream-link-refresh]').on('click', function() {location.reload();});
        function dream2SortAfter(container, y) {
            var items = [].slice.call(container.querySelectorAll('.dream2-link-sort-link:not(.is-dragging)'));
            return items.reduce(function(closest, item) {
                var box = item.getBoundingClientRect();
                var offset = y - box.top - box.height / 2;
                return offset < 0 && offset > closest.offset ? {offset: offset, element: item} : closest;
            }, {offset: Number.NEGATIVE_INFINITY, element: null}).element;
        }
        $(document)
            .on('dragstart', '.dream2-link-sort-link', function(e) {
                this.classList.add('is-dragging');
                e.originalEvent.dataTransfer.effectAllowed = 'move';
                e.originalEvent.dataTransfer.setData('text/plain', this.querySelector('strong').textContent);
            })
            .on('dragend', '.dream2-link-sort-link', function() {
                this.classList.remove('is-dragging');
            })
            .on('dragover', '[data-dream-link-sort-list]', function(e) {
                e.preventDefault();
                var after = dream2SortAfter(this, e.originalEvent.clientY);
                var dragging = document.querySelector('.dream2-link-sort-link.is-dragging');
                if (!dragging || dragging.parentNode !== this) return;
                if (after) {
                    this.insertBefore(dragging, after);
                } else {
                    this.appendChild(dragging);
                }
            });
    });
    </script>
    <script>
    jQuery(function($) {
        var manager = $('.dream2-link-manager');
        var ajaxUrl = manager.data('dream-link-ajax');
        var nonce = manager.data('dream-link-nonce');
        var accessLabels = {pending: '待检', success: '正常', error: '异常'};
        var backlinkLabels = {pending: '反链待检', success: '反链正常', missing: '无反链', error: '反链异常', skip: '未配置反链'};

        function setPill(pill, type, state) {
            if (!pill.length) {
                return;
            }
            var labels = type === 'access' ? accessLabels : backlinkLabels;
            var prefix = type === 'access' ? '◉ ' : '⌁ ';
            pill.removeClass('is-pending is-success is-missing is-error is-muted')
                .addClass('is-' + (state === 'skip' ? 'muted' : state))
                .text(prefix + (labels[state] || labels.pending));
        }

        function setChecking(card) {
            card.addClass('is-checking');
            card.find('.dream2-link-status-dock').attr('title', '检测中，当前状态暂不覆盖');
        }

        function applyResult(card, result) {
            card.removeClass('is-checking');
            card.attr('data-dream-link-status', result.status).data('dream-link-status', result.status);
            card.attr('data-dream-link-access', result.access).data('dream-link-access', result.access);
            card.attr('data-dream-link-backlink', result.backlink).data('dream-link-backlink', result.backlink);
            setPill(card.find('[data-dream-link-access]'), 'access', result.access);
            setPill(card.find('[data-dream-link-backlink]'), 'backlink', result.backlink);
            card.find('.dream2-link-status-dock').attr('title', result.message + (result.checked_at ? '；' + result.checked_at : ''));
        }

        function applyRequestError(card, message) {
            card.removeClass('is-checking');
            var backlinkState = card.data('dream-link-backlink') === 'skip' ? 'skip' : 'error';
            card.attr('data-dream-link-status', 'error').data('dream-link-status', 'error');
            card.attr('data-dream-link-access', 'error').data('dream-link-access', 'error');
            card.attr('data-dream-link-backlink', backlinkState).data('dream-link-backlink', backlinkState);
            setPill(card.find('[data-dream-link-access]'), 'access', 'error');
            setPill(card.find('[data-dream-link-backlink]'), 'backlink', backlinkState);
            card.find('.dream2-link-status-dock').attr('title', message);
        }

        function checkCard(card) {
            if (String(card.data('dream-link-has-backlink')) === '0') {
                card.find('.dream2-link-status-dock').attr('title', '未配置反链检测页，已跳过检测');
                return Promise.resolve();
            }
            setChecking(card);
            return new Promise(function(resolve) {
                $.ajax({
                    url: ajaxUrl,
                    method: 'POST',
                    timeout: 12000,
                    data: {
                        action: 'dream2_check_friend_link',
                        nonce: nonce,
                        link_id: card.data('dream-link-id')
                    }
                }).done(function(response) {
                    if (response && response.success) {
                        applyResult(card, response.data);
                    } else {
                        applyRequestError(card, response && response.data && response.data.message ? response.data.message : '检测失败');
                    }
                }).fail(function(xhr, status) {
                    var message = status === 'timeout' ? '检测超时（12 秒）' : '检测请求失败';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        message = xhr.responseJSON.data.message;
                    }
                    applyRequestError(card, message);
                }).always(resolve);
            });
        }

        function updateGroupProgress(cards) {
            var totals = {};
            var done = {};
            cards.each(function() {
                var term = String($(this).data('dream-link-term'));
                totals[term] = (totals[term] || 0) + 1;
                if ($(this).data('dream-link-check-complete')) {
                    done[term] = (done[term] || 0) + 1;
                }
            });
            $('.dream2-link-check-row').each(function() {
                var term = String($(this).data('dream-link-check-term'));
                var total = totals[term] || 0;
                $(this).find('span').text(total ? (done[term] || 0) + ' / ' + total : '无可检测链接');
            });
        }

        async function runChecks(cards, progress) {
            cards.removeData('dream-link-check-complete');
            var cursor = 0;
            if (progress) updateGroupProgress(cards);
            async function worker() {
                while (cursor < cards.length) {
                    var index = cursor++;
                    var card = cards.eq(index);
                    await checkCard(card);
                    card.data('dream-link-check-complete', true);
                    if (progress) updateGroupProgress(cards);
                }
            }
            var workerCount = Math.min(3, cards.length);
            await Promise.all(Array.from({length: workerCount}, worker));
            return cards.length;
        }

        $('[data-dream-link-filter]').off('change').on('change', function() {
            var value = this.value;
            $('.dream2-link-card').each(function() {
                var card = $(this);
                var show = value === 'all'
                    || (value === 'access-error' && card.data('dream-link-access') === 'error')
                    || (value === 'backlink-missing' && card.data('dream-link-backlink') === 'missing');
                card.toggle(show);
            });
        });

        $('[data-dream-link-check-all]').off('click').on('click', async function() {
            var button = $(this);
            var original = button.text();
            button.prop('disabled', true).text('检测中...');
            $('.dream2-link-check-modal').prop('hidden', false);
            try {
                var count = await runChecks($('.dream2-link-card').filter(function(){return String($(this).data('dream-link-has-backlink')) !== '0'}), true);
                button.text(count ? '检测完成' : '没有可检测链接');
            } finally {
                window.setTimeout(function() {
                    button.prop('disabled', false).text(original);
                }, 1000);
            }
        });

        $('[data-dream-link-group-check]').off('click').on('click', async function() {
            var button = $(this);
            var original = button.text();
            var cards = button.closest('.dream2-link-group').find('.dream2-link-card').filter(function(){return String($(this).data('dream-link-has-backlink')) !== '0'});
            button.prop('disabled', true).text('检测中...');
            try {
                var count = await runChecks(cards, false);
                button.text(count ? '检测完成' : '没有可检测链接');
            } finally {
                window.setTimeout(function() {
                    button.prop('disabled', false).text(original);
                }, 1000);
            }
        });

        $('[data-dream-link-check]').on('click', async function(event) {
            event.stopPropagation();
            var button = $(this);
            if (button.prop('disabled')) {
                return;
            }
            var original = button.text();
            button.prop('disabled', true).text('检测中...');
            try {
                await checkCard(button.closest('.dream2-link-card'));
                button.text('检测完成');
            } finally {
                window.setTimeout(function() {
                    button.prop('disabled', false).text(original);
                }, 1000);
            }
        });
    });
    </script>
    <script>jQuery(function($){function syncModalState(){var open=$('.dream2-link-modal:not([hidden])').length>0;$('.dream2-link-manager').toggleClass('is-modal-open',open)}function syncLinkApplicationToggle(){var commentsEnabled=$('input[name="dream2_options[link_enable_comment]"]:checked').val()==='1',option=$('[data-dream-link-application-option]'),inputs=option.find('input[type="radio"]');inputs.prop('disabled',!commentsEnabled).attr('aria-disabled',commentsEnabled?'false':'true');option.find('[data-dream-link-application-hint]').prop('hidden',commentsEnabled);if(!commentsEnabled){option.find('input[type="radio"][value="0"]').prop('checked',true)}}try{var url=new URL(window.location.href);if(url.searchParams.has('dream2_link_message')){url.searchParams.delete('dream2_link_message');window.history.replaceState(null,'',url.toString())}}catch(e){}$('[data-dream-link-config],[data-dream-link-cron-help],[data-dream-link-sort],[data-dream-link-new-category],[data-dream-link-import],[data-dream-link-check-all],[data-dream-link-new]').on('click',function(){setTimeout(syncModalState,0)});$('[data-dream-link-close]').on('click',function(){setTimeout(syncModalState,0)});$(document).on('change','input[name="dream2_options[link_enable_comment]"]',syncLinkApplicationToggle);syncModalState();syncLinkApplicationToggle()});</script>
    <?php
}
