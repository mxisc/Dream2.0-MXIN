<?php
/**
 * Dream 2.0 MXIN theme bootstrap.
 *
 * Based on halo-theme-dream2.0 by Nineya, licensed under MIT.
 *
 * @package Dream2_MXIN
 */

if (!defined('ABSPATH')) {
    exit;
}

define('DREAM2_MXIN_VERSION', '0.7.46');

// Keep the public layout identical for signed-in and signed-out visitors.
add_filter('show_admin_bar', '__return_false');

// The links-page setting controls new submissions, not visibility of existing comments.
add_filter('comments_open', function ($open, $post_id) {
    if ('page-templates/links.php' === get_page_template_slug($post_id) && !dream2_enabled('link_enable_comment', true)) {
        return false;
    }
    return $open;
}, 10, 2);

require_once get_template_directory() . '/inc/mail-log.php';
require_once get_template_directory() . '/inc/theme-settings.php';
require_once get_template_directory() . '/inc/login.php';
require_once get_template_directory() . '/inc/sidebar-widgets.php';
require_once get_template_directory() . '/inc/comment-emojis.php';
require_once get_template_directory() . '/inc/private-comments.php';
require_once get_template_directory() . '/inc/link-application.php';
require_once get_template_directory() . '/inc/template-tags.php';

function dream2_mxin_setup() {
    load_theme_textdomain('dream2-mxin', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('automatic-feed-links');
    add_theme_support('custom-logo', array(
        'height'      => 72,
        'width'       => 260,
        'flex-height' => true,
        'flex-width'  => true,
    ));
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ));
    add_theme_support('align-wide');
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');

    register_nav_menus(array(
        'primary' => __('主导航', 'dream2-mxin'),
        'footer'  => __('页脚导航', 'dream2-mxin'),
    ));
}
add_action('after_setup_theme', 'dream2_mxin_setup');

// Dream2 sidebars use WP_Widget modules; the block widget editor only adds costly REST previews here.
add_filter('use_widgets_block_editor', '__return_false');

function dream2_mxin_builtin_route_slug($key, $fallback) {
    $slug = sanitize_title(dream2_get($key, $fallback));
    return $slug ?: $fallback;
}

function dream2_mxin_register_builtin_routes() {
    if (!dream2_enabled('enable_archivers_route', true)) {
        return;
    }

    $slug = dream2_mxin_builtin_route_slug('archivers_route_slug', 'archivers');
    add_rewrite_rule('^' . $slug . '/?$', 'index.php?dream2_builtin_page=archivers', 'top');
    add_rewrite_rule('^' . $slug . '/page/([0-9]{1,})/?$', 'index.php?dream2_builtin_page=archivers&paged=$matches[1]', 'top');
}
add_action('init', 'dream2_mxin_register_builtin_routes');

function dream2_mxin_archivers_route_url() {
    $slug = dream2_mxin_builtin_route_slug('archivers_route_slug', 'archivers');
    return home_url('/' . $slug . '/');
}

function dream2_mxin_nav_contains_url($items, $url) {
    $target_path = trim((string) wp_parse_url($url, PHP_URL_PATH), '/');
    if ($target_path === '') {
        return false;
    }

    if (!preg_match_all('/href=(["\'])(.*?)\1/i', $items, $matches)) {
        return false;
    }

    foreach ($matches[2] as $href) {
        $href = html_entity_decode((string) $href, ENT_QUOTES, get_bloginfo('charset'));
        if (dream2_mxin_nav_item_matches_url($href, $url)) {
            return true;
        }
    }
    return false;
}

function dream2_mxin_nav_item_matches_url($href, $url) {
    $target_path = trim((string) wp_parse_url($url, PHP_URL_PATH), '/');
    $item_path = trim((string) wp_parse_url($href, PHP_URL_PATH), '/');
    return $target_path !== '' && $item_path === $target_path;
}

function dream2_mxin_is_archivers_request() {
    if (get_query_var('dream2_builtin_page') === 'archivers') {
        return true;
    }
    $request_path = isset($_SERVER['REQUEST_URI']) ? (string) wp_parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH) : '';
    $home_path = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);
    if ($home_path && $home_path !== '/' && strpos($request_path, $home_path) === 0) {
        $request_path = substr($request_path, strlen($home_path));
    }
    $request_path = trim($request_path, '/');
    $archive_path = trim((string) wp_parse_url(dream2_mxin_archivers_route_url(), PHP_URL_PATH), '/');
    return $archive_path !== '' && $request_path === $archive_path;
}

add_filter('wp_nav_menu_items', function ($items, $args) {
    if (($args->theme_location ?? '') !== 'primary' || !dream2_enabled('enable_archivers_route', true)) {
        return $items;
    }

    $url = dream2_mxin_archivers_route_url();
    if (dream2_mxin_nav_contains_url($items, $url)) {
        return $items;
    }

    $current = dream2_mxin_is_archivers_request() ? ' current' : '';
    return $items . sprintf(
        '<li class="item-sub-li dream2-archivers-menu-item"><a class="item%3$s" href="%1$s">%2$s</a></li>',
        esc_url($url),
        esc_html__('文章归档', 'dream2-mxin'),
        esc_attr($current)
    );
}, 10, 2);

add_filter('nav_menu_css_class', function ($classes, $item, $args) {
    if (($args->theme_location ?? '') !== 'primary' || !dream2_mxin_is_archivers_request()) {
        return $classes;
    }

    $current_classes = array('current-menu-item', 'current-menu-parent', 'current-menu-ancestor', 'current_page_item', 'current_page_parent', 'current_page_ancestor');
    $classes = array_values(array_diff((array) $classes, $current_classes));
    if (dream2_mxin_nav_item_matches_url($item->url ?? '', dream2_mxin_archivers_route_url())) {
        $classes[] = 'current-menu-item';
    }
    return $classes;
}, 10, 3);

add_filter('wp_nav_menu_objects', function ($items, $args) {
    if (($args->theme_location ?? '') !== 'primary' || !dream2_mxin_is_archivers_request()) {
        return $items;
    }

    $current_classes = array('current-menu-item', 'current-menu-parent', 'current-menu-ancestor', 'current_page_item', 'current_page_parent', 'current_page_ancestor');
    foreach ($items as $item) {
        $item->classes = array_values(array_diff((array) $item->classes, $current_classes));
        if (dream2_mxin_nav_item_matches_url($item->url ?? '', dream2_mxin_archivers_route_url())) {
            $item->classes[] = 'current-menu-item';
        }
    }
    return $items;
}, 10, 2);

add_filter('query_vars', function ($vars) {
    $vars[] = 'dream2_builtin_page';
    return $vars;
});

add_filter('template_include', function ($template) {
    if ('archivers' === get_query_var('dream2_builtin_page')) {
        $archive_template = get_template_directory() . '/page-templates/archives.php';
        if (file_exists($archive_template)) {
            return $archive_template;
        }
    }
    return $template;
});

add_filter('pre_handle_404', function ($preempt, $wp_query) {
    return 'archivers' === get_query_var('dream2_builtin_page') ? true : $preempt;
}, 10, 2);

function dream2_mxin_flush_rewrites() {
    dream2_mxin_register_builtin_routes();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'dream2_mxin_flush_rewrites');

add_action('update_option_dream2_options', function ($old_options, $options) {
    $old_options = is_array($old_options) ? $old_options : array();
    $options = is_array($options) ? $options : array();
    $route_keys = array('enable_archivers_route', 'archivers_route_slug');

    foreach ($route_keys as $key) {
        if (($old_options[$key] ?? null) !== ($options[$key] ?? null)) {
            dream2_mxin_flush_rewrites();
            break;
        }
    }
}, 10, 2);

function dream2_mxin_content_width() {
    $GLOBALS['content_width'] = apply_filters('dream2_mxin_content_width', 920);
}
add_action('after_setup_theme', 'dream2_mxin_content_width', 0);

function dream2_mxin_widgets_init() {
    $sidebars = array(
        'sidebar-left'  => __('左侧边栏', 'dream2-mxin'),
        'sidebar-right' => __('右侧边栏', 'dream2-mxin'),
        'footer'        => __('页脚组件区', 'dream2-mxin'),
    );

    foreach ($sidebars as $id => $name) {
        register_sidebar(array(
            'name'          => $name,
            'id'            => $id,
            'before_widget' => '<section id="%1$s" class="card widget dream-widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<div class="card-title">',
            'after_title'   => '</div>',
        ));
    }
}
add_action('widgets_init', 'dream2_mxin_widgets_init');

function dream2_mxin_asset($path) {
    return get_template_directory_uri() . '/assets/' . ltrim($path, '/');
}

function dream2_mxin_asset_version($path) {
    $file = get_template_directory() . '/assets/' . ltrim($path, '/');
    return file_exists($file) ? (string) filemtime($file) : DREAM2_MXIN_VERSION;
}

function dream2_mxin_current_singular_content() {
    if (!is_singular()) {
        return '';
    }
    $post_id = get_queried_object_id();
    return $post_id ? (string) get_post_field('post_content', $post_id) : '';
}

function dream2_mxin_singular_has_code() {
    $content = dream2_mxin_current_singular_content();
    return $content !== '' && (
        has_block('core/code', $content)
        || (bool) preg_match('/<(?:pre|code)\b/i', $content)
    );
}

function dream2_mxin_singular_has_katex() {
    $content = dream2_mxin_current_singular_content();
    return $content !== '' && (bool) preg_match('/\bkatex-(?:inline|block)\b/i', $content);
}

function dream2_mxin_mark_pjax_managed_style($html, $handle) {
    $managed = array(
        'dream2-post',
        'dream2-qmsg',
        'dream2-highlight',
        'dream2-katex',
        'dream2-share',
        'dream2-fancybox',
        'dream2-aplayer',
    );
    if (in_array($handle, $managed, true)) {
        $html = str_replace('<link ', '<link data-dream-pjax-managed="1" ', $html);
    }
    return $html;
}
add_filter('style_loader_tag', 'dream2_mxin_mark_pjax_managed_style', 10, 2);

function dream2_mxin_font_config() {
    $presets = array(
        'system' => array(
            'url'    => '',
            'family' => 'system-ui, -apple-system, BlinkMacSystemFont, Segoe UI, Microsoft YaHei, sans-serif',
        ),
        'lxgw_wenkai' => array(
            'url'    => 'https://cdn.jsdelivr.net/npm/lxgw-wenkai-webfont@1.7.0/lxgwwenkai-regular.css',
            'family' => 'LXGW WenKai, Microsoft YaHei, sans-serif',
        ),
        'noto_sans_sc' => array(
            'url'    => 'https://fonts.loli.net/css2?family=Noto+Sans+SC',
            'family' => 'Noto Sans SC, Microsoft YaHei, sans-serif',
        ),
    );
    $options = get_option('dream2_options', array());
    $options = is_array($options) ? $options : array();
    if (array_key_exists('font_preset', $options)) {
        $preset = sanitize_key((string) $options['font_preset']);
    } else {
        $preset = !empty($options['web_font']) || !empty($options['custom_font']) ? 'custom' : 'system';
    }
    if ($preset === 'custom') {
        return array(
            'url'    => esc_url_raw($options['web_font'] ?? ''),
            'family' => sanitize_text_field($options['custom_font'] ?? ''),
        );
    }
    return $presets[$preset] ?? $presets['system'];
}

function dream2_mxin_enqueue_assets() {
    $lightweight_widget_context = dream2_mxin_is_widget_rest_preview_request();
    $singular_has_code = is_singular() && dream2_mxin_singular_has_code();
    $singular_has_katex = is_singular() && dream2_enabled('enable_katex') && dream2_mxin_singular_has_katex();
    $home_has_carousel = is_home() && (bool) dream2_mxin_parse_repeater(dream2_get('carousel_options', ''));
    $style_dependencies = array('dream2-theme');
    $font = dream2_mxin_font_config();
    if ($font['url']) {
        wp_enqueue_style('dream2-web-font', $font['url'], array(), null);
        $style_dependencies[] = 'dream2-web-font';
    }

    wp_enqueue_style('dream2-theme', dream2_mxin_asset('css/theme.min.css'), array(), DREAM2_MXIN_VERSION);
    wp_enqueue_style('dream2-icons', dream2_mxin_asset('lib/remixicon@3.5.0/remixicon.min.css'), array(), '3.5.0');
    wp_enqueue_style('dream2-style', dream2_mxin_asset('css/style.min.css'), $style_dependencies, DREAM2_MXIN_VERSION);
    wp_enqueue_style('dream2-port', get_template_directory_uri() . '/assets/css/wordpress-port.css', array('dream2-style'), dream2_mxin_asset_version('css/wordpress-port.css'));
    if (dream2_get('cursor_style', 'none') !== 'none') {
        wp_enqueue_style('dream2-cursor', dream2_mxin_asset('css/cursor.min.css'), array('dream2-style'), DREAM2_MXIN_VERSION);
    }

    if (is_page_template('page-templates/photos.php')) {
        wp_enqueue_style('dream2-fancybox', dream2_mxin_asset('lib/fancybox@5.3.7/jquery.fancybox.min.css'), array(), '5.3.7');
        wp_enqueue_script('dream2-justified-gallery', dream2_mxin_asset('lib/justifiedGallery@3.8.1/jquery.justifiedGallery.min.js'), array('jquery'), '3.8.1', true);
        wp_enqueue_script('dream2-fancybox', dream2_mxin_asset('lib/fancybox@5.3.7/jquery.fancybox.min.js'), array('jquery'), '5.3.7', true);
    }

    if (is_singular()) {
        wp_enqueue_style('dream2-post', dream2_mxin_asset('css/post.min.css'), array('dream2-style'), DREAM2_MXIN_VERSION);
        if ($singular_has_code) {
            wp_enqueue_style('dream2-qmsg', dream2_mxin_asset('lib/qmsg/qmsg.min.css'), array(), DREAM2_MXIN_VERSION);
            $highlight_theme = sanitize_file_name(dream2_get('code_pretty', 'atom-one-light'));
            $highlight_path = get_template_directory() . '/assets/lib/highlightjs@11.5.1/styles/' . $highlight_theme . '.min.css';
            if (!file_exists($highlight_path)) {
                $highlight_theme = 'atom-one-light';
            }
            wp_enqueue_style('dream2-highlight', dream2_mxin_asset('lib/highlightjs@11.5.1/styles/' . $highlight_theme . '.min.css'), array(), '11.5.1');
            wp_enqueue_script('dream2-qmsg', dream2_mxin_asset('lib/qmsg/qmsg.min.js'), array(), DREAM2_MXIN_VERSION, true);
            wp_enqueue_script('dream2-clipboard', dream2_mxin_asset('lib/clipboard@2.0.10/clipboard.min.js'), array(), '2.0.10', true);
            wp_enqueue_script('dream2-highlight', dream2_mxin_asset('lib/highlightjs@11.5.1/highlight.min.js'), array(), '11.5.1', true);
        }
        if ($singular_has_katex) {
            wp_enqueue_style('dream2-katex', dream2_mxin_asset('lib/katex@0.18.1/katex.min.css'), array(), '0.18.1');
            wp_enqueue_script('dream2-katex', dream2_mxin_asset('lib/katex@0.18.1/katex.min.js'), array(), '0.18.1', true);
        }
        if (dream2_enabled('enable_post_share')) {
            wp_enqueue_style('dream2-share', dream2_mxin_asset('css/dshare.min.css'), array(), DREAM2_MXIN_VERSION);
        }
    }

    wp_enqueue_script('jquery');
    $port_dependencies = array('jquery');
    if ($home_has_carousel) {
        wp_enqueue_script('dream2-swiper', dream2_mxin_asset('lib/swiper@8.4.6/swiper-bundle.min.js'), array(), '8.4.6', true);
        $port_dependencies[] = 'dream2-swiper';
    }
    $color_character_enabled = !$lightweight_widget_context && dream2_enabled('enable_color_character');
    $hitokoto_mode = dream2_get('enable_hitokoto', '0');
    $hitokoto_enabled = $color_character_enabled && $hitokoto_mode !== '0';
    $hitokoto_category = sanitize_key(dream2_get('hitokoto_category', 'all'));
    $hitokoto_url = 'https://v1.hitokoto.cn/?encode=json';
    if ($hitokoto_mode === 'custom' && dream2_get('hitokoto_custom_url')) {
        $hitokoto_url = esc_url_raw(dream2_get('hitokoto_custom_url'));
    } elseif ($hitokoto_mode === 'official' && preg_match('/^[a-l]$/', $hitokoto_category)) {
        $hitokoto_url = add_query_arg(array('encode' => 'json', 'c' => $hitokoto_category), 'https://v1.hitokoto.cn/');
    }
    if ($singular_has_code) {
        $port_dependencies = array_merge($port_dependencies, array('dream2-qmsg', 'dream2-clipboard', 'dream2-highlight'));
    }
    if ($singular_has_katex) {
        $port_dependencies[] = 'dream2-katex';
    }
    wp_enqueue_script('dream2-port', get_template_directory_uri() . '/assets/js/wordpress-port.js', $port_dependencies, dream2_mxin_asset_version('js/wordpress-port.js'), true);

    $desktop_effect_scripts = array();
    $effects = array(
        'effects_lantern_mode'      => 'lantern',
        'effects_sakura_mode'       => 'sakura',
        'effects_snowflake_mode'    => 'snowflake',
        'effects_universe_mode'     => 'universe',
        'effects_circle_magic_mode' => 'circleMagic',
    );
    foreach ($effects as $option => $file) {
        if (dream2_get($option, 'none') !== 'none') {
            $desktop_effect_scripts[] = 'assets/js/effects/' . $file . '.min.js';
        }
    }

    $cursor_move = sanitize_file_name(dream2_get('cursor_move', 'none'));
    $cursor_click = sanitize_file_name(dream2_get('cursor_click', 'none'));
    if ($cursor_move !== 'none' && file_exists(get_template_directory() . '/assets/js/cursor/move/' . $cursor_move . '.min.js')) {
        $desktop_effect_scripts[] = 'assets/js/cursor/move/' . $cursor_move . '.min.js';
    }
    if ($cursor_click !== 'none' && file_exists(get_template_directory() . '/assets/js/cursor/click/' . $cursor_click . '.min.js')) {
        $desktop_effect_scripts[] = 'assets/js/cursor/click/' . $cursor_click . '.min.js';
    }
    if (dream2_get('load_progress', 'none') !== 'none') {
        wp_enqueue_script('dream2-progress', dream2_mxin_asset('js/dprogress.min.js'), array('dream2-port'), dream2_mxin_asset_version('js/dprogress.min.js'), true);
    }
    if (!$lightweight_widget_context && dream2_enabled('enable_busuanzi')) {
        wp_enqueue_script('dream2-busuanzi', 'https://busuanzi.ibruce.info/busuanzi/2.3/busuanzi.pure.mini.js', array(), null, true);
    }
    if (!$lightweight_widget_context && dream2_get('music_mode', 'none') !== 'none') {
        wp_enqueue_style('dream2-aplayer', dream2_mxin_asset('lib/aplayer@1.10.1/APlayer.min.css'), array(), '1.10.1');
        wp_enqueue_script('dream2-aplayer', dream2_mxin_asset('lib/aplayer@1.10.1/APlayer.min.js'), array(), '1.10.1', true);
        wp_enqueue_script('dream2-meting', dream2_mxin_asset('lib/meting@2.0.1/Meting.min.js'), array('dream2-aplayer'), '2.0.1', true);
    }

    foreach (preg_split('/\R+/', (string) dream2_get('external_css', '')) as $index => $url) {
        $url = esc_url_raw(trim($url));
        if ($url) {
            wp_enqueue_style('dream2-external-' . $index, $url, array(), null);
        }
    }
    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }

    wp_localize_script('dream2-port', 'Dream2WP', array(
        'themeBase'     => trailingslashit(get_template_directory_uri()),
        'emojiEndpoint' => add_query_arg('action', 'dream2_mxin_comment_emojis', admin_url('admin-ajax.php')),
        'themeVersion'  => DREAM2_MXIN_VERSION,
        'defaultTheme'  => dream2_get('default_theme', 'system'),
        'hiddenTitle'   => dream2_get('document_hidden_title', '你别走呀 (´；ω；`)'),
        'visibleTitle'  => dream2_get('document_visible_title', '欢迎回来 (｡･ω･｡)'),
        'websiteTime'   => dream2_get('website_time', ''),
        'copyExplain'   => dream2_get('copy_explain', ''),
        'cursorMove'    => dream2_get('cursor_move', 'none'),
        'cursorClick'   => dream2_get('cursor_click', 'none'),
        'desktopEffectScripts' => $desktop_effect_scripts,
        'noticeMode'    => dream2_get('notice_show_mode', 'default'),
        'sakuraMode'    => dream2_get('effects_sakura_mode', 'none'),
        'snowflakeMode' => dream2_get('effects_snowflake_mode', 'none'),
        'universeMode'  => dream2_get('effects_universe_mode', 'none'),
        'circleMode'    => dream2_get('effects_circle_magic_mode', 'none'),
        'lanternMode'   => dream2_get('effects_lantern_mode', 'none'),
        'loadProgress'  => dream2_get('load_progress', 'none'),
        'codeFoldLine'  => absint(dream2_get('code_fold_line', 0)),
        'imageFoldHeight' => absint(dream2_get('img_fold_height', 0)),
        'showImageName' => dream2_enabled('show_img_name'),
        'enableKatex'   => $singular_has_katex,
        'enableShare'   => dream2_enabled('enable_post_share'),
        'metingApi'     => dream2_get('meting_api', ''),
        'enablePjax'    => dream2_enabled('enable_pjax'),
        'enableServiceWorker' => dream2_enabled('enable_sw'),
        'serviceWorkerUrl' => add_query_arg('dream2_sw', '1', home_url('/')),
        'enableHitokoto' => $hitokoto_enabled,
        'hitokotoUrl'    => $hitokoto_url,
        'enableColorCharacter' => $color_character_enabled,
        'colorCharacters' => $color_character_enabled && !$hitokoto_enabled ? preg_split('/\R+/', (string) dream2_get('color_character', '')) : array(),
        'colorTags'      => dream2_enabled('enable_tag_color'),
        'colorTagCloud'  => dream2_enabled('enable_tagcloud_color'),
        'enableBaiduPush'   => !$lightweight_widget_context && dream2_enabled('enable_baidu_push'),
        'enableToutiaoPush' => !$lightweight_widget_context && dream2_enabled('enable_toutiao_push'),
        'ajaxUrl'            => admin_url('admin-ajax.php'),
        'searchRestUrl'      => rest_url('wp/v2/search'),
        'defaultAvatar'      => dream2_mxin_default_avatar_url(),
        'searchUrl'     => home_url('/'),
        'backToTopText' => __('返回顶部', 'dream2-mxin'),
    ));
}
add_action('wp_enqueue_scripts', 'dream2_mxin_enqueue_assets');

function dream2_mxin_inline_theme_css() {
    $light = sanitize_hex_color(dream2_get('theme_color', '#50bfff')) ?: '#50bfff';
    $night = sanitize_hex_color(dream2_get('night_theme_color', '#5d93db')) ?: '#5d93db';
    $font = dream2_mxin_font_config();
    $desktop_bg = esc_url_raw(dream2_get('background_pc', ''));
    $mobile_bg = esc_url_raw(dream2_get('background_mobile', ''));
    $night_bg = esc_url_raw(dream2_get('night_background_pc', ''));
    $night_mobile = esc_url_raw(dream2_get('night_background_mobile', ''));
    $card_opacity = min(100, max(0, absint(dream2_get('card_opacity', 60))));
    $card_hover_opacity = min(100, $card_opacity + 20);
    $background_image_opacity = min(100, max(0, absint(dream2_get('background_image_opacity', 60))));
    $card_alpha = number_format($card_opacity / 100, 2, '.', '');
    $card_hover_alpha = number_format($card_hover_opacity / 100, 2, '.', '');
    $background_image_alpha = number_format($background_image_opacity / 100, 2, '.', '');
    $cursor = sanitize_file_name((string) dream2_get('cursor_style', 'none'));
    $cursor_files = array(
        'breeze'       => array('Arrow.cur', 'Hand.cur'),
        'overwatch'    => array('pointer.cur', 'link.cur'),
        'rainbow_rain' => array('normal.cur', 'link.cur'),
        'marry'        => array('arrow.cur', 'move.cur'),
        'black_cat'    => array('normal.cur', 'Alternative.cur'),
    );
    $cursor_pair = $cursor_files[$cursor] ?? array('arrow.cur', 'hand.cur');
    $cursor_dir = get_template_directory() . '/assets/cursor/' . $cursor;
    if ($cursor !== 'none' && (!is_dir($cursor_dir) || !file_exists($cursor_dir . '/' . $cursor_pair[0]) || !file_exists($cursor_dir . '/' . $cursor_pair[1]))) {
        $cursor = 'none';
    }
    ?>
    <style id="dream2-custom-properties">
        html{--theme:<?php echo esc_html($light); ?>}
        html.night{--theme:<?php echo esc_html($night); ?>}
        .section .card{background-color:rgba(255,255,255,<?php echo esc_html($card_alpha); ?>)}
        html.night .section .card{background-color:rgba(40,44,52,<?php echo esc_html($card_alpha); ?>)}
        html .section .card:hover{background-color:rgba(255,255,255,<?php echo esc_html($card_hover_alpha); ?>)}
        html.night .section .card:hover{background-color:rgba(40,44,52,<?php echo esc_html($card_hover_alpha); ?>)}
        body:before{opacity:<?php echo esc_html($background_image_alpha); ?>}
        <?php if (dream2_enabled('enable_gray_mode')) : ?>html{filter:grayscale(1)!important}<?php endif; ?>
        <?php if (dream2_enabled('enable_image_bg', true) && $desktop_bg) : ?>
        body:before{background:url('<?php echo esc_url($desktop_bg); ?>') center top no-repeat}
        <?php endif; ?>
        <?php if ($night_bg) : ?>
        html.night body:before{background:url('<?php echo esc_url($night_bg); ?>') center top no-repeat}
        <?php endif; ?>
        <?php if ($mobile_bg) : ?>
        @media(max-width:768px){body:before{background:url('<?php echo esc_url($mobile_bg); ?>') center top no-repeat}}
        <?php endif; ?>
        <?php if ($night_mobile) : ?>
        @media(max-width:768px){html.night body:before{background:url('<?php echo esc_url($night_mobile); ?>') center top no-repeat}}
        <?php endif; ?>
        <?php if ($cursor !== 'none') : ?>
        html{--cursor-default:url('<?php echo esc_url(dream2_mxin_asset('cursor/' . $cursor . '/' . $cursor_pair[0])); ?>'),auto;--cursor-pointer:url('<?php echo esc_url(dream2_mxin_asset('cursor/' . $cursor . '/' . $cursor_pair[1])); ?>'),pointer}
        <?php endif; ?>
        <?php if ($font['family']) : ?>body{font-family:<?php echo esc_html($font['family']); ?>}<?php endif; ?>
        <?php echo dream2_get('inline_css', ''); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </style>
    <?php
}
add_action('wp_head', 'dream2_mxin_inline_theme_css', 30);

function dream2_mxin_print_script_group($external_key, $inline_key) {
    foreach (preg_split('/\R+/', (string) dream2_get($external_key, '')) as $url) {
        $url = esc_url(trim($url));
        if ($url) {
            echo '<script src="' . $url . '"></script>';
        }
    }
    $inline = trim((string) dream2_get($inline_key, ''));
    if ($inline !== '') {
        echo "<script>\n" . $inline . "\n</script>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
add_action('wp_head', function () {
    dream2_mxin_print_script_group('external_js_head', 'inline_js_head');
}, 90);
add_action('wp_footer', function () {
    dream2_mxin_print_script_group('external_js_body', 'inline_js_body');
}, 90);

function dream2_mxin_body_classes($classes) {
    $classes = array_values(array_diff($classes, array('tag')));
    $classes[] = 'dream2-wordpress';
    $classes[] = 'layout-' . sanitize_html_class(dream2_get('sidebar_column', 'only-right'));
    if (dream2_get('theme_style', 'default') === 'clean') {
        $classes[] = 'theme-clean';
    }
    return $classes;
}
add_filter('body_class', 'dream2_mxin_body_classes');

function dream2_mxin_service_worker_endpoint() {
    if (!isset($_GET['dream2_sw']) || $_GET['dream2_sw'] !== '1') {
        return;
    }

    if (!dream2_enabled('enable_sw')) {
        status_header(404);
        nocache_headers();
        exit;
    }

    nocache_headers();
    header('Content-Type: application/javascript; charset=utf-8');
    header('Service-Worker-Allowed: /');
    ?>
const CACHE_NAME = 'dream2-wordpress-<?php echo esc_js(DREAM2_MXIN_VERSION); ?>';
const STATIC_PATTERN = /\/wp-content\/(?:themes\/dream2-mxin|uploads)\//;
self.addEventListener('install', event => event.waitUntil(self.skipWaiting()));
self.addEventListener('activate', event => event.waitUntil(
    caches.keys().then(keys => Promise.all(keys.filter(key => key.startsWith('dream2-wordpress-') && key !== CACHE_NAME).map(key => caches.delete(key)))).then(() => self.clients.claim())
));
self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);
    if (request.method !== 'GET' || url.origin !== location.origin || url.pathname.startsWith('/wp-admin') || url.pathname.startsWith('/wp-login')) return;
    if (STATIC_PATTERN.test(url.pathname)) {
        event.respondWith(caches.open(CACHE_NAME).then(cache => cache.match(request).then(hit => hit || fetch(request).then(response => {
            if (response.ok) cache.put(request, response.clone());
            return response;
        }))));
    }
});
    <?php
    exit;
}
add_action('template_redirect', 'dream2_mxin_service_worker_endpoint', 0);

function dream2_mxin_excerpt_length() {
    return 42;
}
add_filter('excerpt_length', 'dream2_mxin_excerpt_length', 99);

function dream2_mxin_excerpt_more() {
    return '…';
}
add_filter('excerpt_more', 'dream2_mxin_excerpt_more');

function dream2_mxin_add_post_meta_box() {
    add_meta_box(
        'dream2-post-options',
        __('Dream 文章设置', 'dream2-mxin'),
        'dream2_mxin_render_post_meta_box',
        array('post', 'page'),
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'dream2_mxin_add_post_meta_box');

function dream2_mxin_render_post_meta_box($post) {
    wp_nonce_field('dream2_save_post_meta', 'dream2_post_meta_nonce');
    $tips = get_post_meta($post->ID, '_dream2_tips', true);
    $thumbnail_mode = get_post_meta($post->ID, '_dream2_thumbnail_mode', true);
    ?>
    <p>
        <label for="dream2_tips"><?php esc_html_e('文章提示文字', 'dream2-mxin'); ?></label>
        <textarea class="widefat" id="dream2_tips" name="dream2_tips" rows="4"><?php echo esc_textarea($tips); ?></textarea>
    </p>
    <p>
        <label for="dream2_thumbnail_mode"><?php esc_html_e('封面显示模式', 'dream2-mxin'); ?></label>
        <select class="widefat" id="dream2_thumbnail_mode" name="dream2_thumbnail_mode">
            <?php foreach (array('' => '跟随主题', 'default' => '大图卡片', 'small' => '小图卡片', 'back' => '背景封面', 'fold' => '折叠封面', 'none' => '不显示封面') as $value => $label) : ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($thumbnail_mode, $value); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <?php
}

function dream2_mxin_save_post_meta($post_id) {
    if (!isset($_POST['dream2_post_meta_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['dream2_post_meta_nonce'])), 'dream2_save_post_meta') ||
        (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ||
        !current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['dream2_tips'])) {
        update_post_meta($post_id, '_dream2_tips', sanitize_textarea_field(wp_unslash($_POST['dream2_tips'])));
    }
    if (isset($_POST['dream2_thumbnail_mode'])) {
        $mode = sanitize_key(wp_unslash($_POST['dream2_thumbnail_mode']));
        if (in_array($mode, array('', 'default', 'small', 'back', 'fold', 'none'), true)) {
            update_post_meta($post_id, '_dream2_thumbnail_mode', $mode);
        }
    }
}
add_action('save_post', 'dream2_mxin_save_post_meta');
