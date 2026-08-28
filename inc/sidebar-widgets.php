<?php
/**
 * Dream2 sidebar widgets.
 *
 * @package Dream2_MXIN
 */

if (!defined('ABSPATH')) {
    exit;
}

function dream2_mxin_is_widget_rest_preview_request() {
    if (!defined('REST_REQUEST') || !REST_REQUEST) {
        return false;
    }

    $route = (string) get_query_var('rest_route');
    if ($route === '' && isset($_SERVER['REQUEST_URI'])) {
        $path = (string) wp_parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH);
        $prefix = '/' . trim(rest_get_url_prefix(), '/') . '/';
        $position = strpos($path, $prefix);
        if ($position !== false) {
            $route = '/' . ltrim(substr($path, $position + strlen($prefix)), '/');
        }
    }

    return (bool) preg_match('#^/wp/v2/widget-types/[^/]+/render/?$#', $route);
}

function dream2_mxin_is_widget_editor_request() {
    global $pagenow;

    return is_admin() && $pagenow === 'widgets.php';
}

function dream2_mxin_sidebar_widget_admin_assets($hook) {
    if ($hook !== 'widgets.php') {
        return;
    }
    wp_enqueue_media();
    wp_add_inline_script('jquery-core', <<<'JS'
jQuery(function($){
    function refreshMetingApiFields(root) {
        $(root || document).find('[data-dream-widget-meting-source]').each(function(){
            var source = $(this);
            var container = source.closest('.widget-content, form');
            var customApi = container.find('[data-dream-widget-meting-custom-api]');
            var enabled = source.val() === 'custom';
            customApi.prop('disabled', !enabled).attr('aria-disabled', enabled ? 'false' : 'true');
        });
    }

    $(document).on('click', '.dream-widget-media-button', function(event){
        event.preventDefault();
        var button = $(this);
        var target = button.closest('.dream-widget-media-field').find('input[type="url"]');
        var frame = wp.media({title: '选择媒体', button: {text: '使用此文件'}, multiple: false});
        frame.on('select', function(){
            var file = frame.state().get('selection').first();
            if (file && target.length) {
                target.val(file.toJSON().url).trigger('change');
            }
        });
        frame.open();
    });
    $(document).on('change', '[data-dream-widget-meting-source]', function(){
        refreshMetingApiFields($(this).closest('.widget-content, form'));
    });
    $(document).on('widget-added widget-updated', function(event, widget){
        refreshMetingApiFields(widget && widget.length ? widget : document);
    });
    refreshMetingApiFields(document);
});
JS);
}
add_action('admin_enqueue_scripts', 'dream2_mxin_sidebar_widget_admin_assets');

function dream2_mxin_render_sidebar_widget_preview_placeholder($type, $module, $widget_id = '') {
    $defaults = dream2_mxin_sidebar_widget_defaults($type);
    dream2_mxin_sidebar_widget_section_open($type, $module, $widget_id);
    dream2_mxin_sidebar_widget_title_html($module, $defaults['title'], $defaults['icon']);
    ?>
    <div class="card-content">
        <p><?php esc_html_e('Dream2 模块预览已简化，前台会按实际配置渲染。', 'dream2-mxin'); ?></p>
    </div>
    </section>
    <?php
}

function dream2_mxin_sidebar_stat_label($type) {
    $labels = array(
        'post'     => '文章',
        'category' => '分类',
        'tag'      => '标签',
        'comment'  => '评论',
        'visit'    => '访问',
    );
    return $labels[$type] ?? $labels['post'];
}

function dream2_mxin_sidebar_stat_value($type) {
    if ($type === 'category') {
        return (int) wp_count_terms(array('taxonomy' => 'category', 'hide_empty' => true));
    }
    if ($type === 'tag') {
        return (int) wp_count_terms(array('taxonomy' => 'post_tag', 'hide_empty' => true));
    }
    if ($type === 'comment') {
        return (int) wp_count_comments()->approved;
    }
    if ($type === 'visit') {
        return dream2_mxin_get_total_post_views();
    }
    return (int) wp_count_posts('post')->publish;
}

function dream2_mxin_sidebar_stat_display($value) {
    $value = max(0, (int) $value);
    if ($value >= 1000000) {
        return number_format($value / 1000000, 1, '.', '') . 'bw';
    }
    if ($value >= 10000) {
        return number_format($value / 10000, 2, '.', '') . 'w';
    }
    return (string) $value;
}

function dream2_mxin_sanitize_custom_widget_content($content) {
    $content = (string) $content;
    $content = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $content);
    return wp_kses_post($content ?? '');
}

function dream2_mxin_release_repo_slug($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $value)) {
        $host = strtolower((string) wp_parse_url($value, PHP_URL_HOST));
        if (!in_array($host, array('github.com', 'www.github.com'), true)) {
            return '';
        }
        $value = (string) wp_parse_url($value, PHP_URL_PATH);
    }
    $value = trim($value, " \t\n\r\0\x0B/");
    $value = preg_replace('#\.git$#i', '', $value);
    $parts = explode('/', (string) $value);
    if (count($parts) < 2) {
        return '';
    }
    $owner = $parts[0];
    $repo = $parts[1];
    if (!preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9-]{0,37}[A-Za-z0-9])?$/', $owner)) {
        return '';
    }
    if (!preg_match('/^[A-Za-z0-9._-]+$/', $repo)) {
        return '';
    }
    return $owner . '/' . $repo;
}

function dream2_mxin_release_last_option($repo) {
    return 'dream2_releases_last_' . md5(strtolower((string) $repo));
}

function dream2_mxin_release_limit_choices() {
    return array(5, 10, 15, 20, 25, 30);
}

function dream2_mxin_release_limit($value) {
    $value = (int) $value;
    $choices = dream2_mxin_release_limit_choices();
    if (in_array($value, $choices, true)) {
        return $value;
    }
    if ($value <= min($choices)) {
        return min($choices);
    }
    if ($value >= max($choices)) {
        return max($choices);
    }
    foreach ($choices as $choice) {
        if ($value <= $choice) {
            return $choice;
        }
    }
    return 10;
}

function dream2_mxin_release_normalize_items($items) {
    if (!is_array($items)) {
        return array();
    }
    $releases = array();
    foreach ($items as $item) {
        if (!is_array($item) || !empty($item['draft'])) {
            continue;
        }
        $tag = sanitize_text_field((string) ($item['tag_name'] ?? ''));
        $url = esc_url_raw((string) ($item['html_url'] ?? ''));
        if ($tag === '' || $url === '') {
            continue;
        }
        $releases[] = array(
            'tag_name'     => $tag,
            'name'         => sanitize_text_field((string) ($item['name'] ?? '')),
            'html_url'     => $url,
            'published_at' => sanitize_text_field((string) ($item['published_at'] ?? '')),
            'created_at'   => sanitize_text_field((string) ($item['created_at'] ?? '')),
            'body'         => sanitize_textarea_field((string) ($item['body'] ?? '')),
        );
    }
    return $releases;
}

function dream2_mxin_release_atom_content($content) {
    $content = html_entity_decode((string) $content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $content = preg_replace('#</(?:h[1-6]|p|li)>#i', "\n", $content);
    $content = wp_strip_all_tags((string) $content);
    $content = preg_replace("/\n{2,}/", "\n", (string) $content);
    return trim((string) $content);
}

function dream2_mxin_release_fetch_atom_items($repo, $limit) {
    $repo = dream2_mxin_release_repo_slug($repo);
    $limit = dream2_mxin_release_limit($limit);
    if ($repo === '') {
        return array();
    }

    $response = wp_safe_remote_get(
        'https://github.com/' . $repo . '/releases.atom',
        array(
            'timeout' => 4,
            'headers' => array(
                'Accept'     => 'application/atom+xml',
                'User-Agent' => 'Dream2-MXIN/' . DREAM2_MXIN_VERSION,
            ),
        )
    );
    if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
        return array();
    }
    if (!function_exists('simplexml_load_string')) {
        return array();
    }

    $xml = @simplexml_load_string(wp_remote_retrieve_body($response), 'SimpleXMLElement', LIBXML_NOCDATA);
    if (!$xml || empty($xml->entry)) {
        return array();
    }

    $releases = array();
    foreach ($xml->entry as $entry) {
        $href = '';
        foreach ($entry->link as $link) {
            $attrs = $link->attributes();
            if ((string) ($attrs['rel'] ?? '') === 'alternate') {
                $href = (string) ($attrs['href'] ?? '');
                break;
            }
        }
        $tag = '';
        if ($href !== '' && preg_match('~/releases/tag/([^/?#]+)~', $href, $matches)) {
            $tag = rawurldecode($matches[1]);
        }
        if ($tag === '' && preg_match('/v?\d+(?:\.\d+)+(?:[-._A-Za-z0-9]+)?/', (string) $entry->title, $matches)) {
            $tag = $matches[0][0] === 'v' ? $matches[0] : 'v' . $matches[0];
        }
        if ($tag === '' || $href === '') {
            continue;
        }
        $updated = sanitize_text_field((string) $entry->updated);
        $releases[] = array(
            'tag_name'     => sanitize_text_field($tag),
            'name'         => sanitize_text_field((string) $entry->title),
            'html_url'     => esc_url_raw($href),
            'published_at' => $updated,
            'created_at'   => $updated,
            'body'         => sanitize_textarea_field(dream2_mxin_release_atom_content((string) $entry->content)),
        );
        if (count($releases) >= $limit) {
            break;
        }
    }
    return $releases;
}

function dream2_mxin_release_fetch_items($repo) {
    $repo = dream2_mxin_release_repo_slug($repo);
    if ($repo === '') {
        return new WP_Error('dream2_releases_invalid_repo', 'GitHub 仓库地址无效。');
    }

    $limit = max(dream2_mxin_release_limit_choices());

    list($owner, $name) = explode('/', $repo, 2);
    $response = wp_safe_remote_get(
        sprintf(
            'https://api.github.com/repos/%s/%s/releases?per_page=%d',
            rawurlencode($owner),
            rawurlencode($name),
            $limit
        ),
        array(
            'timeout' => 4,
            'headers' => array(
                'Accept'     => 'application/vnd.github+json',
                'User-Agent' => 'Dream2-MXIN/' . DREAM2_MXIN_VERSION,
            ),
        )
    );
    if (!is_wp_error($response) && (int) wp_remote_retrieve_response_code($response) === 200) {
        $releases = dream2_mxin_release_normalize_items(json_decode(wp_remote_retrieve_body($response), true));
        if ($releases) {
            update_option(dream2_mxin_release_last_option($repo), $releases, false);
            return $releases;
        }
    }

    $atom_releases = dream2_mxin_release_fetch_atom_items($repo, $limit);
    if ($atom_releases) {
        update_option(dream2_mxin_release_last_option($repo), $atom_releases, false);
        return $atom_releases;
    }

    $last_success = get_option(dream2_mxin_release_last_option($repo), array());
    if (is_array($last_success) && $last_success) {
        return $last_success;
    }
    if (is_wp_error($response)) {
        return $response;
    }
    return new WP_Error('dream2_releases_unavailable', '更新记录暂时不可用。');
}

function dream2_mxin_release_clean_line($line) {
    $line = trim((string) $line);
    $line = preg_replace('/^\s*[-*+]\s+/u', '', $line);
    $line = preg_replace('/^\s*#+\s*/u', '', $line);
    $line = preg_replace('/`([^`]+)`/u', '$1', $line);
    $line = preg_replace('/\[([^\]]+)\]\([^)]+\)/u', '$1', $line);
    $line = str_replace('**', '', $line);
    return trim((string) $line);
}

function dream2_mxin_release_is_duplicate_title($line, $release) {
    $normalized = strtolower((string) preg_replace('/\s+/u', ' ', dream2_mxin_release_clean_line($line)));
    $release_name = strtolower((string) preg_replace('/\s+/u', ' ', dream2_mxin_release_clean_line($release['name'] ?? '')));
    $tag = strtolower((string) ($release['tag_name'] ?? ''));
    $version = preg_replace('/^v/i', '', $tag);
    return in_array($normalized, array($release_name, $tag, $version, 'dream2 mxin ' . $version, 'dream2 mxin ' . $tag), true);
}

function dream2_mxin_release_notes($release) {
    $body = (string) ($release['body'] ?? '');
    $lines = preg_split('/\r?\n/', $body);
    $notes = array();
    foreach ($lines as $line) {
        $line = dream2_mxin_release_clean_line($line);
        if (
            $line === ''
            || dream2_mxin_release_is_duplicate_title($line, $release)
            || preg_match('/^SHA-?256[:：]?.*$/i', $line)
            || preg_match('/^[a-f0-9]{64}$/i', $line)
            || preg_match('/^下载\s*$/iu', $line)
        ) {
            continue;
        }
        $notes[] = '· ' . $line;
    }
    return $notes ? implode("\n", $notes) : '· 查看完整 Release 说明';
}

function dream2_mxin_widget_module_value($module, $key, $fallback = '') {
    if (is_array($module) && array_key_exists($key, $module)) {
        return $module[$key];
    }
    return dream2_get($key, $fallback);
}

function dream2_mxin_widget_module_enabled($module, $key, $fallback = false) {
    $value = dream2_mxin_widget_module_value($module, $key, $fallback ? '1' : '0');
    return $value === true || $value === 1 || $value === '1';
}

function dream2_mxin_widget_active_instances($id_base) {
    $all = get_option('widget_' . sanitize_key($id_base), array());
    if (!is_array($all)) {
        return array();
    }
    $sidebars = wp_get_sidebars_widgets();
    if (!is_array($sidebars)) {
        return array();
    }
    $instances = array();
    foreach ($sidebars as $sidebar_id => $widget_ids) {
        if ($sidebar_id === 'wp_inactive_widgets' || !is_array($widget_ids)) {
            continue;
        }
        foreach ($widget_ids as $widget_id) {
            if (preg_match('/^' . preg_quote((string) $id_base, '/') . '-(\d+)$/', (string) $widget_id, $matches)) {
                $number = (int) $matches[1];
                if (isset($all[$number]) && is_array($all[$number])) {
                    $instances[] = $all[$number];
                }
            }
        }
    }
    return $instances;
}

function dream2_mxin_widget_first_active_instance($id_base) {
    $instances = dream2_mxin_widget_active_instances($id_base);
    return $instances[0] ?? array();
}

function dream2_mxin_widget_first_active_instance_with_value($id_base, $key, $disabled = '0') {
    foreach (dream2_mxin_widget_active_instances($id_base) as $instance) {
        if (array_key_exists($key, $instance) && (string) $instance[$key] !== (string) $disabled) {
            return $instance;
        }
    }
    return dream2_mxin_widget_first_active_instance($id_base);
}

function dream2_mxin_widget_has_active_value($id_base, $key, $disabled = '0') {
    foreach (dream2_mxin_widget_active_instances($id_base) as $instance) {
        if ((string) ($instance[$key] ?? $disabled) !== (string) $disabled) {
            return true;
        }
    }
    return false;
}

function dream2_mxin_widget_number($value, $fallback, $min = 1, $max = 100) {
    $value = $value === '' || $value === null ? $fallback : $value;
    return max($min, min($max, (int) $value));
}

function dream2_mxin_notice_show_mode_choices() {
    return array('default' => '始终显示', 'index' => '仅首页', 'close' => '关闭');
}

function dream2_mxin_music_mode_choices() {
    return array('none' => '关闭', 'playlist' => '网易云歌单', 'config' => '自定义配置');
}

function dream2_mxin_meting_api_source_choices() {
    return array(
        'i_meto'  => 'i-meto API',
        'qijieya' => '祈杰 API',
        'mxin'     => '铭心 API',
        'custom'  => '自定义 API',
    );
}

function dream2_mxin_meting_api_source($module) {
    $source = (string) dream2_mxin_widget_module_value($module, 'meting_api_source', '');
    if (array_key_exists($source, dream2_mxin_meting_api_source_choices())) {
        return $source;
    }

    $api = (string) dream2_mxin_widget_module_value($module, 'meting_api', '');
    if (str_contains($api, 'api.qijieya.cn/meting/')) {
        return 'qijieya';
    }
    if (str_contains($api, 'api.mxin.moe/api/v1/meting')) {
        return 'mxin';
    }
    if ($api !== '' && !str_contains($api, 'api.i-meto.com/meting/api')) {
        return 'custom';
    }
    return 'i_meto';
}

function dream2_mxin_meting_api_url($module) {
    $source = dream2_mxin_meting_api_source($module);
    if ($source === 'qijieya') {
        return 'https://api.qijieya.cn/meting/?server=:server&type=:type&id=:id';
    }
    if ($source === 'mxin') {
        return 'https://api.mxin.moe/api/v1/meting?server=:server&type=:type&id=:id&r=:r';
    }
    if ($source === 'custom') {
        return (string) dream2_mxin_widget_module_value($module, 'meting_api', '');
    }
    return 'https://api.i-meto.com/meting/api?server=:server&type=:type&id=:id&r=:r';
}

function dream2_mxin_ad_mode_choices() {
    return array('none' => '关闭', 'image' => '图片', 'custom' => '自定义代码');
}

function dream2_mxin_hitokoto_category_choices() {
    return array('all'=>'全部','a'=>'动画','b'=>'漫画','c'=>'游戏','d'=>'文学','e'=>'原创','f'=>'来自网络','g'=>'其他','h'=>'影视','i'=>'诗词','j'=>'网易云','k'=>'哲学','l'=>'抖机灵');
}

function dream2_mxin_render_release_widget($module) {
    $repo = dream2_mxin_release_repo_slug($module['repo'] ?? '');
    $limit = dream2_mxin_release_limit($module['limit'] ?? 10);
    $repo_url = $repo !== '' ? 'https://github.com/' . $repo . '/releases' : '';
    $releases = dream2_mxin_release_fetch_items($repo);
    ?>
    <div class="card-content dream-release-list">
        <?php if (is_wp_error($releases)) : ?>
            <p class="dream-release-error">
                <?php echo esc_html($releases->get_error_message()); ?>
                <?php if ($repo_url !== '') : ?><a href="<?php echo esc_url($repo_url); ?>" target="_blank" rel="nofollow noopener noreferrer">查看 GitHub Releases</a><?php endif; ?>
            </p>
        <?php else : ?>
            <?php foreach (array_slice($releases, 0, $limit) as $release) : $timestamp = strtotime($release['published_at'] ?: $release['created_at']); ?>
                <div class="dream-release-item">
                    <p class="dream-release-head">
                        <a class="dream-release-version" href="<?php echo esc_url($release['html_url']); ?>" target="_blank" rel="nofollow noopener noreferrer"><?php echo esc_html($release['tag_name']); ?></a>
                        <?php if ($timestamp) : ?><span class="dream-release-date"><?php echo esc_html(wp_date('Y-m-d H:i:s', $timestamp)); ?></span><?php endif; ?>
                    </p>
                    <div class="dream-release-notes"><?php echo esc_html(dream2_mxin_release_notes($release)); ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
}

function dream2_mxin_sidebar_widget_defaults($type) {
    $defaults = array(
        'profile'         => array('title' => '', 'icon' => '', 'class' => 'profile'),
        'toc'             => array('title' => '目录', 'icon' => 'ri-book-2-line', 'class' => 'toc is-hidden-all'),
        'notice'          => array('title' => '公告', 'icon' => 'ri-volume-up-line', 'class' => 'notice'),
        'love'            => array('title' => '恋爱墙', 'icon' => 'ri-heart-3-line', 'class' => 'dream-love'),
        'music'           => array('title' => '音乐', 'icon' => 'ri-music-2-line', 'class' => 'dream-music'),
        'recent_posts'    => array('title' => '最新文章', 'icon' => 'ri-history-line', 'class' => 'recent-posts'),
        'recent_comments' => array('title' => '最新评论', 'icon' => 'ri-chat-3-line', 'class' => 'recent-comments'),
        'categories'      => array('title' => '分类', 'icon' => 'ri-apps-line', 'class' => ''),
        'tags'            => array('title' => '标签', 'icon' => 'ri-price-tag-3-line', 'class' => 'tags'),
        'tagcloud'        => array('title' => '标签云', 'icon' => 'ri-cloud-line', 'class' => 'tagcloud'),
        'ad_piece'        => array('title' => '', 'icon' => '', 'class' => 'dream-ad'),
        'custom'          => array('title' => '自定义模块', 'icon' => 'ri-apps-2-line', 'class' => ''),
        'releases'        => array('title' => '更新记录', 'icon' => 'ri-rocket-line', 'class' => 'dream-releases'),
    );
    return $defaults[$type] ?? array('title' => '', 'icon' => '', 'class' => '');
}

function dream2_mxin_sidebar_widget_title($module, $fallback) {
    return isset($module['title']) && trim((string) $module['title']) !== ''
        ? (string) $module['title']
        : $fallback;
}

function dream2_mxin_sidebar_widget_icon($module, $fallback) {
    return isset($module['icon']) && trim((string) $module['icon']) !== ''
        ? (string) $module['icon']
        : $fallback;
}

function dream2_mxin_sidebar_widget_hide($module) {
    $hide = $module['hide'] ?? 'is-not-hidden';
    return array_key_exists($hide, dream2_mxin_sidebar_hide_choices()) ? $hide : 'is-not-hidden';
}

function dream2_mxin_sidebar_widget_section_open($type, $module, $widget_id = '') {
    $defaults = dream2_mxin_sidebar_widget_defaults($type);
    $classes = array_filter(array(
        'card',
        'widget',
        $defaults['class'],
        dream2_mxin_sidebar_widget_hide($module),
    ));
    if ($type === 'tags' && dream2_mxin_widget_module_enabled($module, 'enable_tag_color', dream2_enabled('enable_tag_color'))) {
        $classes[] = 'dream-tags-colored';
    }
    if ($type === 'tagcloud' && dream2_mxin_widget_module_enabled($module, 'enable_tagcloud_color', dream2_enabled('enable_tagcloud_color'))) {
        $classes[] = 'dream-tagcloud-colored';
    }
    printf(
        '<section%1$s class="%2$s">',
        $widget_id ? ' id="' . esc_attr($widget_id) . '"' : '',
        esc_attr(implode(' ', $classes))
    );
}

function dream2_mxin_sidebar_widget_title_html($module, $fallback_title, $fallback_icon) {
    $title = dream2_mxin_sidebar_widget_title($module, $fallback_title);
    if ($title === '') {
        return;
    }
    $icon = dream2_mxin_sidebar_widget_icon($module, $fallback_icon);
    ?>
    <div class="card-title">
        <?php if ($icon !== '') : ?><i class="<?php echo esc_attr($icon); ?> card-title-label"></i><?php endif; ?>
        <span><?php echo esc_html($title); ?></span>
    </div>
    <?php
}

function dream2_mxin_render_sidebar_widget_module($type, $module = array(), $widget_id = '') {
    $type = sanitize_key($type);
    $module = is_array($module) ? $module : array();
    $defaults = dream2_mxin_sidebar_widget_defaults($type);

    $notice_show_mode = (string) dream2_mxin_widget_module_value($module, 'notice_show_mode', 'default');
    $music_mode = (string) dream2_mxin_widget_module_value($module, 'music_mode', 'none');
    $ad_mode = (string) dream2_mxin_widget_module_value($module, 'ad_mode', 'none');

    if ($type === 'notice' && $notice_show_mode === 'close') {
        return;
    }
    if ($type === 'notice' && $notice_show_mode === 'index' && !is_home() && !is_front_page()) {
        return;
    }
    if ($type === 'love' && (!dream2_mxin_widget_module_value($module, 'love_oneself_avatar', '') || !dream2_mxin_widget_module_value($module, 'love_opposite_avatar', ''))) {
        return;
    }
    if ($type === 'music' && $music_mode === 'none') {
        return;
    }
    if ($type === 'ad_piece' && $ad_mode === 'none') {
        return;
    }
    if ($type === 'releases' && dream2_mxin_release_repo_slug($module['repo'] ?? '') === '') {
        return;
    }
    if (dream2_mxin_is_widget_rest_preview_request()) {
        dream2_mxin_render_sidebar_widget_preview_placeholder($type, $module, $widget_id);
        return;
    }

    dream2_mxin_sidebar_widget_section_open($type, $module, $widget_id);

    if ($type === 'profile') : ?>
        <div class="card-content">
            <nav class="level"><div class="level-item" style="flex-direction:column">
                <figure class="image"><?php echo get_avatar(dream2_mxin_profile_avatar_target(), 86, '', '', array('class' => 'avatar')); ?></figure>
                <p class="nickname"><?php echo esc_html(dream2_mxin_profile_display_name()); ?></p>
                <p class="motto spark-input"><?php bloginfo('description'); ?></p>
                <?php $profile_location = (string) dream2_mxin_widget_module_value($module, 'profile_location', ''); if ($profile_location !== '') : ?><p class="address"><i class="ri-map-pin-line"></i> <?php echo esc_html($profile_location); ?></p><?php endif; ?>
            </div></nav>
            <?php $custom_stats = dream2_mxin_normalize_repeater(dream2_mxin_widget_module_value($module, 'custom_stats', array()), 'custom_stats'); if ($custom_stats) : ?>
                <nav class="level dream-custom-stats">
                    <?php foreach ($custom_stats as $stat) : $stat_type = sanitize_key($stat['type'] ?? 'post'); $stat_value = dream2_mxin_sidebar_stat_value($stat_type); ?>
                        <div class="level-item"><div><p class="heading"><?php echo esc_html(dream2_mxin_sidebar_stat_label($stat_type)); ?></p><p class="value" title="<?php echo esc_attr((string) $stat_value); ?>"><?php echo esc_html(dream2_mxin_sidebar_stat_display($stat_value)); ?></p></div></div>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
            <?php $socials = dream2_mxin_normalize_repeater(dream2_mxin_widget_module_value($module, 'custom_options', array()), 'custom_options'); if ($socials) : ?><div class="level"><?php foreach ($socials as $social) : ?>
                <a class="level-item button is-transparent" href="<?php echo esc_url($social['url'] ?? '#'); ?>" title="<?php echo esc_attr($social['name'] ?? ''); ?>" target="_blank" rel="nofollow noopener noreferrer"><i class="<?php echo esc_attr($social['icon'] ?? 'ri-links-line'); ?>"></i></a>
            <?php endforeach; ?></div><?php endif; ?>
        </div>
    <?php elseif ($type === 'toc') : ?>
        <?php dream2_mxin_sidebar_widget_title_html($module, $defaults['title'], $defaults['icon']); ?>
        <div class="card-content toc-content"></div>
    <?php elseif ($type === 'notice') : ?>
        <?php dream2_mxin_sidebar_widget_title_html($module, $defaults['title'], $defaults['icon']); ?>
        <div class="card-content"><?php echo wp_kses_post(dream2_mxin_widget_module_value($module, 'notice_content', '<p>欢迎来访本站，博主还没有发布任何公告！</p>')); ?></div>
    <?php elseif ($type === 'love') : ?>
        <?php dream2_mxin_sidebar_widget_title_html($module, $defaults['title'], $defaults['icon']); ?>
        <div class="card-content dream-love-avatars">
            <a href="<?php echo esc_url(dream2_mxin_widget_module_value($module, 'love_oneself_url', '#')); ?>"><img src="<?php echo esc_url(dream2_mxin_widget_module_value($module, 'love_oneself_avatar', '')); ?>" alt="自己的头像"></a>
            <i class="ri-heart-fill"></i>
            <a href="<?php echo esc_url(dream2_mxin_widget_module_value($module, 'love_opposite_url', '#')); ?>"><img src="<?php echo esc_url(dream2_mxin_widget_module_value($module, 'love_opposite_avatar', '')); ?>" alt="对方的头像"></a>
        </div>
        <?php $love_time = (string) dream2_mxin_widget_module_value($module, 'love_time', ''); if ($love_time !== '') : ?><p class="dream-love-time" data-time="<?php echo esc_attr($love_time); ?>"><?php echo esc_html($love_time); ?></p><?php endif; ?>
    <?php elseif ($type === 'music') : ?>
        <?php dream2_mxin_sidebar_widget_title_html($module, $defaults['title'], $defaults['icon']); ?>
        <div class="card-content">
            <?php $netease_playlist_id = (string) dream2_mxin_widget_module_value($module, 'netease_playlist_id', ''); if ($music_mode === 'playlist' && $netease_playlist_id !== '') : ?>
                <meting-js list-folded="true" server="netease" type="playlist" id="<?php echo esc_attr($netease_playlist_id); ?>"></meting-js>
            <?php elseif ($music_mode === 'config') : ?>
                <?php echo wp_kses(dream2_mxin_widget_module_value($module, 'music_config', ''), array('meting-js' => array('server' => true, 'type' => true, 'id' => true, 'api' => true, 'auto' => true, 'name' => true, 'artist' => true, 'url' => true, 'cover' => true, 'lrc' => true, 'list-folded' => true, 'fixed' => true, 'mini' => true, 'autoplay' => true))); ?>
            <?php endif; ?>
        </div>
    <?php elseif ($type === 'recent_posts') : ?>
        <?php dream2_mxin_sidebar_widget_title_html($module, $defaults['title'], $defaults['icon']); ?>
        <div class="card-content"><ul class="list">
            <?php foreach (wp_get_recent_posts(array('numberposts' => dream2_mxin_widget_number(dream2_mxin_widget_module_value($module, 'recent_posts_num', 5), 5, 1, 20), 'post_status' => 'publish')) as $recent_post) : ?>
                <li class="item"><a class="link" href="<?php echo esc_url(get_permalink($recent_post['ID'])); ?>"><?php echo esc_html($recent_post['post_title']); ?></a><i class="ri-link"></i></li>
            <?php endforeach; ?>
        </ul></div>
    <?php elseif ($type === 'recent_comments') : ?>
        <?php dream2_mxin_sidebar_widget_title_html($module, $defaults['title'], $defaults['icon']); ?>
        <div class="card-content"><ul class="list">
            <?php foreach (get_comments(array('number' => dream2_mxin_widget_number(dream2_mxin_widget_module_value($module, 'recent_comments_num', 5), 5, 1, 20), 'status' => 'approve')) as $recent_comment) : ?>
                <li class="item dream-recent-comment"><?php echo get_avatar($recent_comment, 32); ?><a class="link" href="<?php echo esc_url(get_comment_link($recent_comment)); ?>"><?php echo esc_html(wp_trim_words(get_comment_excerpt($recent_comment), 14)); ?></a></li>
            <?php endforeach; ?>
        </ul></div>
    <?php elseif ($type === 'categories') : ?>
        <?php dream2_mxin_sidebar_widget_title_html($module, $defaults['title'], $defaults['icon']); ?>
        <div class="card-content"><ul class="menu-list">
            <?php foreach (get_categories(array('hide_empty' => true, 'number' => dream2_mxin_widget_number(dream2_mxin_widget_module_value($module, 'categories_num', 10), 10, 1, 100))) as $dream_category) : ?>
                <li><a class="level" href="<?php echo esc_url(get_category_link($dream_category)); ?>"><span class="level-item"><?php echo esc_html($dream_category->name); ?></span><span class="level-item tag"><?php echo esc_html((string) $dream_category->count); ?></span></a></li>
            <?php endforeach; ?>
        </ul></div>
    <?php elseif ($type === 'tags') : ?>
        <?php dream2_mxin_sidebar_widget_title_html($module, $defaults['title'], $defaults['icon']); ?>
        <div class="card-content"><?php wp_tag_cloud(array('smallest' => 12, 'largest' => 12, 'unit' => 'px', 'number' => dream2_mxin_widget_number(dream2_mxin_widget_module_value($module, 'tags_num', 18), 18, 1, 100), 'format' => 'flat')); ?></div>
    <?php elseif ($type === 'tagcloud') : ?>
        <?php dream2_mxin_sidebar_widget_title_html($module, $defaults['title'], $defaults['icon']); ?>
        <div class="card-content"><?php wp_tag_cloud(array('smallest' => 14, 'largest' => 30, 'unit' => 'px', 'number' => dream2_mxin_widget_number(dream2_mxin_widget_module_value($module, 'tagcloud_num', 32), 32, 1, 100), 'format' => 'flat')); ?></div>
    <?php elseif ($type === 'ad_piece') : ?>
        <?php if (dream2_mxin_widget_module_enabled($module, 'show_ad_tag')) : ?><span class="dream-ad-tag">广告</span><?php endif; ?>
        <?php if (dream2_mxin_widget_module_enabled($module, 'ad_tag_close')) : ?><button class="dream-ad-close" type="button" aria-label="关闭广告"><i class="ri-close-line"></i></button><?php endif; ?>
        <?php if ($ad_mode === 'image') : ?><a href="<?php echo esc_url(dream2_mxin_widget_module_value($module, 'ad_target_url', '#')); ?>"><img src="<?php echo esc_url(dream2_mxin_widget_module_value($module, 'ad_image', '')); ?>" alt="广告"></a><?php else : echo dream2_mxin_sanitize_custom_widget_content(dream2_mxin_widget_module_value($module, 'ad_custom_code', '')); endif; ?>
    <?php elseif ($type === 'releases') : ?>
        <?php dream2_mxin_sidebar_widget_title_html($module, $defaults['title'], $defaults['icon']); ?>
        <?php dream2_mxin_render_release_widget($module); ?>
    <?php elseif ($type === 'custom') : ?>
        <?php dream2_mxin_sidebar_widget_title_html($module, $defaults['title'], $defaults['icon']); ?>
        <?php
        if (!empty($module['content_unfiltered'])) {
            echo (string) ($module['content'] ?? ''); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } else {
            echo dream2_mxin_sanitize_custom_widget_content($module['content'] ?? '');
        }
        ?>
    <?php endif;

    echo '</section>';
}

abstract class Dream2_MXIN_Sidebar_Widget extends WP_Widget {
    protected $module_type = '';
    protected $default_title = '';
    protected $default_icon = '';

    public function __construct($id_base, $name, $type, $default_title, $default_icon) {
        $this->module_type = $type;
        $this->default_title = $default_title;
        $this->default_icon = $default_icon;
        parent::__construct($id_base, $name, array(
            'classname'                   => 'dream2-widget-' . sanitize_html_class($type),
            'description'                 => 'Dream2 侧栏小工具',
            'customize_selective_refresh' => true,
        ));
    }

    public function widget($args, $instance) {
        $module = is_array($instance) ? $instance : array();
        if ($this->supports_title_fields()) {
            $module['title'] = $module['title'] ?? $this->default_title;
            $module['icon'] = $module['icon'] ?? $this->default_icon;
        }
        $module['hide'] = $module['hide'] ?? 'is-not-hidden';
        $module['content'] = $module['content'] ?? '';
        $module['content_unfiltered'] = !empty($module['content_unfiltered']);
        dream2_mxin_render_sidebar_widget_module($this->module_type, $module, $args['widget_id'] ?? '');
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        if ($this->supports_title_fields()) {
            $instance['title'] = sanitize_text_field($new_instance['title'] ?? '');
            $instance['icon'] = sanitize_text_field($new_instance['icon'] ?? '');
        }
        $instance['hide'] = array_key_exists(($new_instance['hide'] ?? ''), dream2_mxin_sidebar_hide_choices())
            ? (string) $new_instance['hide']
            : 'is-not-hidden';
        if ($this->module_type === 'custom') {
            $can_unfiltered_html = current_user_can('unfiltered_html');
            $instance['content'] = $can_unfiltered_html
                ? (string) ($new_instance['content'] ?? '')
                : dream2_mxin_sanitize_custom_widget_content($new_instance['content'] ?? '');
            $instance['content_unfiltered'] = $can_unfiltered_html ? 1 : 0;
        }
        if ($this->module_type === 'profile') {
            $instance['profile_location'] = sanitize_text_field($new_instance['profile_location'] ?? '');
            $custom_stats = array_filter($new_instance['custom_stats'] ?? array(), static function($item) {
                return is_array($item) && trim((string) ($item['type'] ?? '')) !== '';
            });
            $instance['custom_stats'] = dream2_mxin_sanitize_repeater('custom_stats', $custom_stats);
            $instance['custom_options'] = dream2_mxin_sanitize_repeater('custom_options', $new_instance['custom_options'] ?? array());
            $instance['enable_color_character'] = !empty($new_instance['enable_color_character']) ? '1' : '0';
            $instance['enable_hitokoto'] = array_key_exists('enable_hitokoto', $new_instance)
                ? dream2_mxin_normalize_hitokoto_mode($new_instance['enable_hitokoto'])
                : dream2_mxin_normalize_hitokoto_mode($old_instance['enable_hitokoto'] ?? '0');
            $category_value = array_key_exists('hitokoto_category', $new_instance) ? (string) $new_instance['hitokoto_category'] : (string) ($old_instance['hitokoto_category'] ?? 'all');
            $instance['hitokoto_category'] = preg_match('/^[a-l]$/', $category_value) ? $category_value : 'all';
            $instance['hitokoto_custom_url'] = array_key_exists('hitokoto_custom_url', $new_instance)
                ? esc_url_raw($new_instance['hitokoto_custom_url'])
                : esc_url_raw($old_instance['hitokoto_custom_url'] ?? '');
            $instance['color_character'] = array_key_exists('color_character', $new_instance)
                ? sanitize_textarea_field($new_instance['color_character'])
                : sanitize_textarea_field($old_instance['color_character'] ?? '');
        } elseif ($this->module_type === 'notice') {
            $instance['notice_content'] = current_user_can('unfiltered_html')
                ? (string) ($new_instance['notice_content'] ?? '')
                : wp_kses_post($new_instance['notice_content'] ?? '');
            $instance['notice_show_mode'] = array_key_exists((string) ($new_instance['notice_show_mode'] ?? ''), dream2_mxin_notice_show_mode_choices())
                ? (string) $new_instance['notice_show_mode']
                : 'default';
        } elseif ($this->module_type === 'recent_posts') {
            $instance['recent_posts_num'] = dream2_mxin_widget_number($new_instance['recent_posts_num'] ?? 5, 5, 1, 20);
        } elseif ($this->module_type === 'recent_comments') {
            $instance['recent_comments_num'] = dream2_mxin_widget_number($new_instance['recent_comments_num'] ?? 5, 5, 1, 20);
        } elseif ($this->module_type === 'categories') {
            $instance['categories_num'] = dream2_mxin_widget_number($new_instance['categories_num'] ?? 10, 10, 1, 100);
        } elseif ($this->module_type === 'tags') {
            $instance['tags_num'] = dream2_mxin_widget_number($new_instance['tags_num'] ?? 18, 18, 1, 100);
            $instance['enable_tag_color'] = !empty($new_instance['enable_tag_color']) ? '1' : '0';
        } elseif ($this->module_type === 'tagcloud') {
            $instance['tagcloud_num'] = dream2_mxin_widget_number($new_instance['tagcloud_num'] ?? 32, 32, 1, 100);
            $instance['enable_tagcloud_color'] = !empty($new_instance['enable_tagcloud_color']) ? '1' : '0';
        } elseif ($this->module_type === 'love') {
            $instance['love_oneself_avatar'] = esc_url_raw($new_instance['love_oneself_avatar'] ?? '');
            $instance['love_oneself_url'] = esc_url_raw($new_instance['love_oneself_url'] ?? '');
            $instance['love_opposite_avatar'] = esc_url_raw($new_instance['love_opposite_avatar'] ?? '');
            $instance['love_opposite_url'] = esc_url_raw($new_instance['love_opposite_url'] ?? '');
            $instance['love_time'] = sanitize_text_field($new_instance['love_time'] ?? '');
        } elseif ($this->module_type === 'music') {
            $music_choices = dream2_mxin_music_mode_choices();
            $instance['music_mode'] = array_key_exists((string) ($new_instance['music_mode'] ?? ''), $music_choices)
                ? (string) $new_instance['music_mode']
                : 'none';
            $instance['netease_playlist_id'] = sanitize_text_field($new_instance['netease_playlist_id'] ?? '');
            $api_sources = dream2_mxin_meting_api_source_choices();
            $instance['meting_api_source'] = array_key_exists((string) ($new_instance['meting_api_source'] ?? ''), $api_sources)
                ? (string) $new_instance['meting_api_source']
                : 'i_meto';
            $instance['meting_api'] = array_key_exists('meting_api', $new_instance)
                ? esc_url_raw($new_instance['meting_api'])
                : esc_url_raw($old_instance['meting_api'] ?? '');
            $instance['music_config'] = current_user_can('unfiltered_html')
                ? (string) ($new_instance['music_config'] ?? '')
                : wp_kses_post($new_instance['music_config'] ?? '');
        } elseif ($this->module_type === 'ad_piece') {
            $ad_choices = dream2_mxin_ad_mode_choices();
            $instance['ad_mode'] = array_key_exists((string) ($new_instance['ad_mode'] ?? ''), $ad_choices)
                ? (string) $new_instance['ad_mode']
                : 'none';
            $instance['show_ad_tag'] = !empty($new_instance['show_ad_tag']) ? '1' : '0';
            $instance['ad_tag_close'] = !empty($new_instance['ad_tag_close']) ? '1' : '0';
            $instance['ad_target_url'] = esc_url_raw($new_instance['ad_target_url'] ?? '');
            $instance['ad_image'] = esc_url_raw($new_instance['ad_image'] ?? '');
            $instance['ad_custom_code'] = current_user_can('unfiltered_html')
                ? (string) ($new_instance['ad_custom_code'] ?? '')
                : dream2_mxin_sanitize_custom_widget_content($new_instance['ad_custom_code'] ?? '');
        }
        return $instance;
    }

    protected function supports_title_fields() {
        return !in_array($this->module_type, array('profile', 'ad_piece'), true);
    }

    protected function field_value($instance, $key, $fallback = '') {
        return array_key_exists($key, $instance) ? $instance[$key] : dream2_get($key, $fallback);
    }

    protected function text_field($key, $label, $value, $type = 'text') {
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id($key)); ?>"><?php echo esc_html($label); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id($key)); ?>" name="<?php echo esc_attr($this->get_field_name($key)); ?>" type="<?php echo esc_attr($type); ?>" value="<?php echo esc_attr((string) $value); ?>">
        </p>
        <?php
    }

    protected function media_field($key, $label, $value) {
        ?>
        <p class="dream-widget-media-field">
            <label for="<?php echo esc_attr($this->get_field_id($key)); ?>"><?php echo esc_html($label); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id($key)); ?>" name="<?php echo esc_attr($this->get_field_name($key)); ?>" type="url" value="<?php echo esc_attr((string) $value); ?>">
            <button type="button" class="button dream-widget-media-button">选择媒体</button>
        </p>
        <?php
    }

    protected function number_field($key, $label, $value, $min = 1, $max = 100) {
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id($key)); ?>"><?php echo esc_html($label); ?></label>
            <input class="small-text" id="<?php echo esc_attr($this->get_field_id($key)); ?>" name="<?php echo esc_attr($this->get_field_name($key)); ?>" type="number" min="<?php echo esc_attr((string) $min); ?>" max="<?php echo esc_attr((string) $max); ?>" value="<?php echo esc_attr((string) $value); ?>">
        </p>
        <?php
    }

    protected function textarea_field($key, $label, $value, $rows = 4) {
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id($key)); ?>"><?php echo esc_html($label); ?></label>
            <textarea class="widefat" rows="<?php echo esc_attr((string) $rows); ?>" id="<?php echo esc_attr($this->get_field_id($key)); ?>" name="<?php echo esc_attr($this->get_field_name($key)); ?>"><?php echo esc_textarea((string) $value); ?></textarea>
        </p>
        <?php
    }

    protected function select_field($key, $label, $value, $choices) {
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id($key)); ?>"><?php echo esc_html($label); ?></label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id($key)); ?>" name="<?php echo esc_attr($this->get_field_name($key)); ?>">
                <?php foreach ($choices as $choice => $choice_label) : ?>
                    <option value="<?php echo esc_attr((string) $choice); ?>" <?php selected((string) $value, (string) $choice); ?>><?php echo esc_html((string) $choice_label); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
    }

    protected function checkbox_field($key, $label, $checked) {
        ?>
        <p>
            <label>
                <input type="checkbox" name="<?php echo esc_attr($this->get_field_name($key)); ?>" value="1" <?php checked($checked); ?>>
                <?php echo esc_html($label); ?>
            </label>
        </p>
        <?php
    }

    protected function custom_stats_fields($instance) {
        $items = dream2_mxin_normalize_repeater($this->field_value($instance, 'custom_stats', array()), 'custom_stats');
        $choices = dream2_mxin_sidebar_stat_choices();
        ?>
        <p><strong>侧栏统计项目</strong></p>
        <?php for ($index = 0; $index < 3; $index++) : $value = sanitize_key($items[$index]['type'] ?? ''); ?>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('custom_stats_' . $index)); ?>">统计项 <?php echo esc_html((string) ($index + 1)); ?></label>
                <select class="widefat" id="<?php echo esc_attr($this->get_field_id('custom_stats_' . $index)); ?>" name="<?php echo esc_attr($this->get_field_name('custom_stats')); ?>[<?php echo esc_attr((string) $index); ?>][type]">
                    <option value="">不显示</option>
                    <?php foreach ($choices as $choice => $label) : ?>
                        <option value="<?php echo esc_attr($choice); ?>" <?php selected($value, $choice); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
        <?php endfor;
    }

    protected function custom_option_item($index, $item = array()) {
        $base_name = $this->get_field_name('custom_options') . '[' . $index . ']';
        ?>
        <div class="dream-widget-social-item" data-dream-widget-social-item>
            <p>
                <label>名称</label>
                <input class="widefat" type="text" name="<?php echo esc_attr($base_name . '[name]'); ?>" value="<?php echo esc_attr($item['name'] ?? ''); ?>">
            </p>
            <p>
                <label>图标 Class</label>
                <input class="widefat" type="text" name="<?php echo esc_attr($base_name . '[icon]'); ?>" value="<?php echo esc_attr($item['icon'] ?? ''); ?>">
            </p>
            <p>
                <label>地址</label>
                <textarea class="widefat" rows="2" name="<?php echo esc_attr($base_name . '[url]'); ?>"><?php echo esc_textarea($item['url'] ?? ''); ?></textarea>
            </p>
            <p><button type="button" class="button-link-delete" data-dream-widget-social-remove>删除渠道</button></p>
        </div>
        <?php
    }

    protected function custom_options_fields($instance) {
        $items = dream2_mxin_normalize_repeater($this->field_value($instance, 'custom_options', array()), 'custom_options');
        ?>
        <div class="dream-widget-socials" data-dream-widget-socials>
            <p><strong>社交渠道</strong></p>
            <div data-dream-widget-social-items>
                <?php foreach ($items as $index => $item) : ?>
                    <?php $this->custom_option_item($index, is_array($item) ? $item : array()); ?>
                <?php endforeach; ?>
            </div>
            <p><button type="button" class="button" data-dream-widget-social-add>添加渠道</button></p>
            <template data-dream-widget-social-template>
                <?php $this->custom_option_item('__INDEX__'); ?>
            </template>
        </div>
        <script>
        (function(){
            if (window.dreamWidgetSocialsReady) return;
            window.dreamWidgetSocialsReady = true;
            document.addEventListener('click', function(event) {
                var add = event.target.closest('[data-dream-widget-social-add]');
                if (add) {
                    var root = add.closest('[data-dream-widget-socials]');
                    if (!root) return;
                    var list = root.querySelector('[data-dream-widget-social-items]');
                    var template = root.querySelector('[data-dream-widget-social-template]');
                    if (!list || !template) return;
                    var index = Date.now().toString();
                    list.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__INDEX__/g, index));
                    return;
                }
                var remove = event.target.closest('[data-dream-widget-social-remove]');
                if (remove) {
                    var item = remove.closest('[data-dream-widget-social-item]');
                    if (item) item.remove();
                }
            });
        })();
        </script>
        <?php
    }

    protected function profile_hitokoto_fields($instance) {
        $color_enabled = $this->field_value($instance, 'enable_color_character', '0') === '1';
        $hitokoto_mode = dream2_mxin_normalize_hitokoto_mode($this->field_value($instance, 'enable_hitokoto', '0'));
        $hitokoto_category = $this->field_value($instance, 'hitokoto_category', 'all');
        ?>
        <p>
            <label>
                <input type="checkbox" name="<?php echo esc_attr($this->get_field_name('enable_color_character')); ?>" value="1" <?php checked($color_enabled); ?> data-dream-widget-color-character>
                开启彩字切换
            </label>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('enable_hitokoto')); ?>">一言 API</label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('enable_hitokoto')); ?>" name="<?php echo esc_attr($this->get_field_name('enable_hitokoto')); ?>" data-dream-widget-hitokoto-mode>
                <?php foreach (array('0' => '关闭', 'official' => '官方', 'custom' => '自定义') as $choice => $label) : ?>
                    <option value="<?php echo esc_attr($choice); ?>" <?php selected($hitokoto_mode, $choice); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('hitokoto_category')); ?>">一言分类</label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('hitokoto_category')); ?>" name="<?php echo esc_attr($this->get_field_name('hitokoto_category')); ?>" data-dream-widget-hitokoto-category>
                <?php foreach (dream2_mxin_hitokoto_category_choices() as $choice => $label) : ?>
                    <option value="<?php echo esc_attr($choice); ?>" <?php selected((string) $hitokoto_category, (string) $choice); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('hitokoto_custom_url')); ?>">自定义一言 URL</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('hitokoto_custom_url')); ?>" name="<?php echo esc_attr($this->get_field_name('hitokoto_custom_url')); ?>" type="url" value="<?php echo esc_attr((string) $this->field_value($instance, 'hitokoto_custom_url', '')); ?>" data-dream-widget-hitokoto-custom-url>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('color_character')); ?>">侧栏彩字内容</label>
            <textarea class="widefat" rows="4" id="<?php echo esc_attr($this->get_field_id('color_character')); ?>" name="<?php echo esc_attr($this->get_field_name('color_character')); ?>" data-dream-widget-color-text><?php echo esc_textarea((string) $this->field_value($instance, 'color_character', '')); ?></textarea>
        </p>
        <script>
        (function(){
            if (window.dreamWidgetHitokotoReady) return;
            window.dreamWidgetHitokotoReady = true;
            function refresh(root) {
                root = root || document;
                var widgets = root.querySelectorAll('[data-dream-widget-color-character]');
                widgets.forEach(function(toggle) {
                    var container = toggle.closest('.widget-content') || toggle.closest('form') || document;
                    var colorEnabled = !!toggle.checked;
                    var mode = container.querySelector('[data-dream-widget-hitokoto-mode]');
                    var category = container.querySelector('[data-dream-widget-hitokoto-category]');
                    var customUrl = container.querySelector('[data-dream-widget-hitokoto-custom-url]');
                    var colorText = container.querySelector('[data-dream-widget-color-text]');
                    var hitokotoMode = mode ? mode.value : '0';
                    var hitokotoEnabled = colorEnabled && hitokotoMode !== '0';
                    var customEnabled = colorEnabled && hitokotoMode === 'custom';
                    var officialEnabled = hitokotoEnabled && !customEnabled;
                    if (mode) {
                        mode.disabled = !colorEnabled;
                        mode.setAttribute('aria-disabled', colorEnabled ? 'false' : 'true');
                    }
                    if (category) {
                        category.disabled = !officialEnabled;
                        category.setAttribute('aria-disabled', officialEnabled ? 'false' : 'true');
                    }
                    if (customUrl) {
                        customUrl.disabled = !customEnabled;
                        customUrl.setAttribute('aria-disabled', customEnabled ? 'false' : 'true');
                    }
                    if (colorText) {
                        colorText.disabled = !colorEnabled || hitokotoEnabled;
                        colorText.setAttribute('aria-disabled', (!colorEnabled || hitokotoEnabled) ? 'true' : 'false');
                    }
                });
            }
            document.addEventListener('change', function(event) {
                if (event.target.matches('[data-dream-widget-color-character],[data-dream-widget-hitokoto-mode]')) {
                    refresh(event.target.closest('.widget-content') || document);
                }
            });
            document.addEventListener('DOMContentLoaded', function(){refresh(document);});
            if (window.jQuery) {
                jQuery(document).on('widget-added widget-updated', function(event, widget){refresh(widget && widget[0] ? widget[0] : document);});
            }
            refresh(document);
        })();
        </script>
        <?php
    }

    public function form($instance) {
        $title = $instance['title'] ?? $this->default_title;
        $icon = $instance['icon'] ?? $this->default_icon;
        $hide = $instance['hide'] ?? 'is-not-hidden';
        ?>
        <?php if ($this->supports_title_fields()) : ?>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">标题</label>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
            </p>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('icon')); ?>">图标 Class</label>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('icon')); ?>" name="<?php echo esc_attr($this->get_field_name('icon')); ?>" type="text" value="<?php echo esc_attr($icon); ?>">
            </p>
        <?php endif; ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('hide')); ?>">隐藏方式</label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('hide')); ?>" name="<?php echo esc_attr($this->get_field_name('hide')); ?>">
                <?php foreach (dream2_mxin_sidebar_hide_choices() as $value => $label) : ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($hide, $value); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php if ($this->module_type === 'custom') : ?>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('content')); ?>">内容</label>
                <textarea class="widefat" rows="8" id="<?php echo esc_attr($this->get_field_id('content')); ?>" name="<?php echo esc_attr($this->get_field_name('content')); ?>"><?php echo esc_textarea($instance['content'] ?? ''); ?></textarea>
                <small>具备 <code>unfiltered_html</code> 权限的管理员保存时可输出完整 HTML、CSS 和脚本；其他用户保存时仍会自动过滤为安全 HTML。</small>
            </p>
        <?php endif;
        if ($this->module_type === 'profile') {
            $this->text_field('profile_location', '地理位置', $this->field_value($instance, 'profile_location', ''));
            $this->custom_stats_fields($instance);
            $this->custom_options_fields($instance);
            $this->profile_hitokoto_fields($instance);
        } elseif ($this->module_type === 'notice') {
            $this->textarea_field('notice_content', '博客公告', $this->field_value($instance, 'notice_content', '<p>欢迎来访本站，博主还没有发布任何公告！</p>'), 5);
            $this->select_field('notice_show_mode', '公告显示模式', $this->field_value($instance, 'notice_show_mode', 'default'), dream2_mxin_notice_show_mode_choices());
        } elseif ($this->module_type === 'recent_posts') {
            $this->number_field('recent_posts_num', '最近文章数量', $this->field_value($instance, 'recent_posts_num', 5), 1, 20);
        } elseif ($this->module_type === 'recent_comments') {
            $this->number_field('recent_comments_num', '最近评论数量', $this->field_value($instance, 'recent_comments_num', 5), 1, 20);
        } elseif ($this->module_type === 'categories') {
            $this->number_field('categories_num', '分类展示数量', $this->field_value($instance, 'categories_num', 10), 1, 100);
        } elseif ($this->module_type === 'tags') {
            $this->number_field('tags_num', '标签展示数量', $this->field_value($instance, 'tags_num', 18), 1, 100);
            $this->checkbox_field('enable_tag_color', '开启标签颜色', $this->field_value($instance, 'enable_tag_color', '0') === '1');
        } elseif ($this->module_type === 'tagcloud') {
            $this->number_field('tagcloud_num', '标签云展示数量', $this->field_value($instance, 'tagcloud_num', 32), 1, 100);
            $this->checkbox_field('enable_tagcloud_color', '开启标签云颜色', $this->field_value($instance, 'enable_tagcloud_color', '0') === '1');
        } elseif ($this->module_type === 'love') {
            $this->media_field('love_oneself_avatar', '恋爱墙自己的头像', $this->field_value($instance, 'love_oneself_avatar', ''));
            $this->text_field('love_oneself_url', '恋爱墙自己的主页', $this->field_value($instance, 'love_oneself_url', ''), 'url');
            $this->media_field('love_opposite_avatar', '恋爱墙对方的头像', $this->field_value($instance, 'love_opposite_avatar', ''));
            $this->text_field('love_opposite_url', '恋爱墙对方的主页', $this->field_value($instance, 'love_opposite_url', ''), 'url');
            $this->text_field('love_time', '恋爱时间', $this->field_value($instance, 'love_time', ''));
        } elseif ($this->module_type === 'music') {
            $this->select_field('music_mode', '音乐播放器配置方式', $this->field_value($instance, 'music_mode', 'none'), dream2_mxin_music_mode_choices());
            $this->text_field('netease_playlist_id', '网易云歌单 ID', $this->field_value($instance, 'netease_playlist_id', ''));
            $meting_api_source = dream2_mxin_meting_api_source($instance);
            ?>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('meting_api_source')); ?>">Meting API 来源</label>
                <select class="widefat" id="<?php echo esc_attr($this->get_field_id('meting_api_source')); ?>" name="<?php echo esc_attr($this->get_field_name('meting_api_source')); ?>" data-dream-widget-meting-source>
                    <?php foreach (dream2_mxin_meting_api_source_choices() as $choice => $label) : ?>
                        <option value="<?php echo esc_attr($choice); ?>" <?php selected($meting_api_source, $choice); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('meting_api')); ?>">自定义 Meting API</label>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('meting_api')); ?>" name="<?php echo esc_attr($this->get_field_name('meting_api')); ?>" type="url" value="<?php echo esc_attr((string) $this->field_value($instance, 'meting_api', '')); ?>" data-dream-widget-meting-custom-api <?php disabled($meting_api_source !== 'custom'); ?>>
                <small class="description">仅支持 Meting API 格式</small>
            </p>
            <?php
            $this->textarea_field('music_config', '音乐参数进阶配置', $this->field_value($instance, 'music_config', ''), 5);
        } elseif ($this->module_type === 'ad_piece') {
            $this->select_field('ad_mode', '广告展示方法', $this->field_value($instance, 'ad_mode', 'none'), dream2_mxin_ad_mode_choices());
            $this->checkbox_field('show_ad_tag', '显示广告标签', $this->field_value($instance, 'show_ad_tag', '0') === '1');
            $this->checkbox_field('ad_tag_close', '广告标签可关闭', $this->field_value($instance, 'ad_tag_close', '0') === '1');
            $this->text_field('ad_target_url', '广告目标地址', $this->field_value($instance, 'ad_target_url', ''), 'url');
            $this->media_field('ad_image', '广告图片链接', $this->field_value($instance, 'ad_image', ''));
            $this->textarea_field('ad_custom_code', '自定义广告代码', $this->field_value($instance, 'ad_custom_code', ''), 5);
        }
    }
}

class Dream2_MXIN_Profile_Widget extends Dream2_MXIN_Sidebar_Widget { public function __construct() { parent::__construct('dream2_profile', 'Dream2 信息模块', 'profile', '', ''); } }
class Dream2_MXIN_Toc_Widget extends Dream2_MXIN_Sidebar_Widget { public function __construct() { parent::__construct('dream2_toc', 'Dream2 目录模块', 'toc', '目录', 'ri-book-2-line'); } }
class Dream2_MXIN_Notice_Widget extends Dream2_MXIN_Sidebar_Widget { public function __construct() { parent::__construct('dream2_notice', 'Dream2 公告', 'notice', '公告', 'ri-volume-up-line'); } }
class Dream2_MXIN_Love_Widget extends Dream2_MXIN_Sidebar_Widget { public function __construct() { parent::__construct('dream2_love', 'Dream2 恋爱墙', 'love', '恋爱墙', 'ri-heart-3-line'); } }
class Dream2_MXIN_Music_Widget extends Dream2_MXIN_Sidebar_Widget { public function __construct() { parent::__construct('dream2_music', 'Dream2 音乐播放器', 'music', '音乐', 'ri-music-2-line'); } }
class Dream2_MXIN_Recent_Posts_Widget extends Dream2_MXIN_Sidebar_Widget { public function __construct() { parent::__construct('dream2_recent_posts', 'Dream2 最新文章', 'recent_posts', '最新文章', 'ri-history-line'); } }
class Dream2_MXIN_Recent_Comments_Widget extends Dream2_MXIN_Sidebar_Widget { public function __construct() { parent::__construct('dream2_recent_comments', 'Dream2 最新评论', 'recent_comments', '最新评论', 'ri-chat-3-line'); } }
class Dream2_MXIN_Categories_Widget extends Dream2_MXIN_Sidebar_Widget { public function __construct() { parent::__construct('dream2_categories', 'Dream2 文章分类', 'categories', '分类', 'ri-apps-line'); } }
class Dream2_MXIN_Tags_Widget extends Dream2_MXIN_Sidebar_Widget { public function __construct() { parent::__construct('dream2_tags', 'Dream2 文章标签', 'tags', '标签', 'ri-price-tag-3-line'); } }
class Dream2_MXIN_Tagcloud_Widget extends Dream2_MXIN_Sidebar_Widget { public function __construct() { parent::__construct('dream2_tagcloud', 'Dream2 标签云', 'tagcloud', '标签云', 'ri-cloud-line'); } }
class Dream2_MXIN_Ad_Widget extends Dream2_MXIN_Sidebar_Widget { public function __construct() { parent::__construct('dream2_ad', 'Dream2 广告模块', 'ad_piece', '', ''); } }
class Dream2_MXIN_Custom_Widget extends Dream2_MXIN_Sidebar_Widget { public function __construct() { parent::__construct('dream2_custom', 'Dream2 自定义模块', 'custom', '自定义模块', 'ri-apps-2-line'); } }

class Dream2_MXIN_Releases_Widget extends Dream2_MXIN_Sidebar_Widget {
    public function __construct() {
        parent::__construct('dream2_releases', 'Dream2 GitHub 发版跟踪', 'releases', '更新记录', 'ri-rocket-line');
    }

    public function widget($args, $instance) {
        $module = array(
            'title'         => $instance['title'] ?? $this->default_title,
            'icon'          => $instance['icon'] ?? $this->default_icon,
            'hide'          => $instance['hide'] ?? 'is-not-hidden',
            'repo'          => $instance['repo'] ?? '',
            'limit'         => $instance['limit'] ?? 10,
        );
        dream2_mxin_render_sidebar_widget_module($this->module_type, $module, $args['widget_id'] ?? '');
    }

    public function update($new_instance, $old_instance) {
        $instance = parent::update($new_instance, $old_instance);
        $repo = dream2_mxin_release_repo_slug($new_instance['repo'] ?? '');
        $instance['repo'] = $repo;
        $instance['limit'] = dream2_mxin_release_limit($new_instance['limit'] ?? 10);
        return $instance;
    }

    public function form($instance) {
        parent::form($instance);
        $repo = $instance['repo'] ?? '';
        $limit = dream2_mxin_release_limit($instance['limit'] ?? 10);
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('repo')); ?>">GitHub 仓库</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('repo')); ?>" name="<?php echo esc_attr($this->get_field_name('repo')); ?>" type="text" value="<?php echo esc_attr($repo); ?>" placeholder="owner/repo 或 GitHub 仓库地址">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('limit')); ?>">显示版本数</label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('limit')); ?>" name="<?php echo esc_attr($this->get_field_name('limit')); ?>">
                <?php foreach (dream2_mxin_release_limit_choices() as $choice) : ?>
                    <option value="<?php echo esc_attr((string) $choice); ?>" <?php selected($limit, $choice); ?>><?php echo esc_html((string) $choice); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
    }
}

function dream2_mxin_register_sidebar_widgets() {
    foreach (array(
        'Dream2_MXIN_Profile_Widget',
        'Dream2_MXIN_Toc_Widget',
        'Dream2_MXIN_Notice_Widget',
        'Dream2_MXIN_Love_Widget',
        'Dream2_MXIN_Music_Widget',
        'Dream2_MXIN_Recent_Posts_Widget',
        'Dream2_MXIN_Recent_Comments_Widget',
        'Dream2_MXIN_Categories_Widget',
        'Dream2_MXIN_Tags_Widget',
        'Dream2_MXIN_Tagcloud_Widget',
        'Dream2_MXIN_Ad_Widget',
        'Dream2_MXIN_Custom_Widget',
        'Dream2_MXIN_Releases_Widget',
    ) as $widget_class) {
        register_widget($widget_class);
    }
}
add_action('widgets_init', 'dream2_mxin_register_sidebar_widgets');
