<?php
/**
 * WordPress.org-safe theme option compatibility layer.
 *
 * The directory build intentionally excludes content and administration
 * features shipped by Dream 2.0 MXIN Plus. Appearance options are managed
 * through the WordPress Customizer.
 *
 * @package Dream2_MXIN
 */

if (!defined('ABSPATH')) {
    exit;
}

function dream2_mxin_translate_display_values($value) {
    if (is_array($value)) {
        foreach ($value as $key => $item) $value[$key] = dream2_mxin_translate_display_values($item);
        return $value;
    }
    return is_string($value) && preg_match('/[\x{3400}-\x{9fff}]/u', $value) ? translate($value, 'dream2-mxin') : $value;
}

function dream2_mxin_sidebar_hide_choices() {
    return array(
        'is-hidden-mobile' => __('移动设备隐藏', 'dream2-mxin'),
        'is-hidden-not-desktop' => __('移动、平板设备隐藏', 'dream2-mxin'),
        'is-hidden-desktop' => __('桌面设备隐藏', 'dream2-mxin'),
        'is-not-hidden' => __('不隐藏', 'dream2-mxin'),
    );
}

function dream2_mxin_normalize_repeater($value, $name = '') {
    if (is_string($value)) {
        $decoded = json_decode($value, true);
        $value = is_array($decoded) ? $decoded : array();
    }
    return is_array($value) ? array_values(array_filter($value, 'is_array')) : array();
}

function dream2_mxin_org_defaults() {
    return array(
        'theme_style' => 'default', 'default_theme' => 'system', 'theme_color' => '#50bfff',
        'night_theme_color' => '#5d93db', 'font_preset' => 'system', 'sidebar_column' => 'only-right',
        'left_sidebar_sticky' => 'top', 'right_sidebar_sticky' => 'top', 'thumbnail_mode' => 'default',
        'top_thumbnail_mode' => 'back', 'notice_show_mode' => 'default', 'load_progress' => 'none',
        'cursor_style' => 'none', 'cursor_move' => 'none', 'cursor_click' => 'none', 'music_mode' => 'none',
        'ad_mode' => 'none', 'enable_banner' => '1', 'enable_image_bg' => '1', 'enable_copyright' => '1',
        'enable_post_share' => '1', 'show_img_name' => '1', 'drawer_toc' => '1', 'enable_archivers_route' => '1',
        'archivers_route_slug' => 'archivers', 'link_enable_comment' => '1', 'show_exchange_info' => '1',
        'recent_posts_num' => 5, 'recent_comments_num' => 5, 'categories_num' => 10, 'tags_num' => 18,
        'tagcloud_num' => 32, 'code_fold_line' => 20, 'img_fold_height' => 400, 'invalid_tips_day' => 99999999,
        'card_opacity' => 60, 'background_image_opacity' => 60,
    );
}

function dream2_mxin_normalize_sidebar_column($value) {
    $legacy = array('both' => 'all', 'left' => 'only-left', 'right' => 'only-right');
    $value = sanitize_key((string) $value);
    $value = $legacy[$value] ?? $value;
    return in_array($value, array('all', 'only-left', 'only-right', 'module-left', 'module-right', 'none'), true) ? $value : 'only-right';
}

function dream2_mxin_normalize_avatar_source($value) {
    return (string) $value === 'qiniu' ? 'qiniu' : 'official';
}

function dream2_mxin_legacy_avatar_source() {
    return 'official';
}

function dream2_mxin_normalize_load_progress($value) {
    $value = (string) $value;
    if ($value === 'progress') $value = 'left';
    return in_array($value, array('none', 'left', 'center'), true) ? $value : 'none';
}

function dream2_mxin_normalize_hitokoto_mode($value) {
    $value = (string) $value;
    if (in_array($value, array('1', 'true', 'on'), true)) $value = 'official';
    return in_array($value, array('0', 'official', 'custom'), true) ? $value : '0';
}

function dream2_get($key, $fallback = null) {
    $options = get_option('dream2_options', array());
    if (is_array($options) && array_key_exists($key, $options)) {
        $value = $options[$key];
    } else {
        $defaults = dream2_mxin_org_defaults();
        $default = null !== $fallback ? $fallback : ($defaults[$key] ?? '');
        $theme_mods = array(
            'document_hidden_title' => 'dream2_hidden_title', 'document_visible_title' => 'dream2_visible_title',
            'index_inform' => 'dream2_index_notice', 'record_number' => 'dream2_record_number',
            'record_number_moe' => 'dream2_moe_record_number', 'record_number_ps' => 'dream2_public_record',
            'website_time' => 'dream2_website_time', 'background_pc' => 'dream2_background_pc',
            'background_mobile' => 'dream2_background_mobile', 'night_background_pc' => 'dream2_night_background_pc',
            'banner_image' => 'dream2_banner_image', 'banner_description' => 'dream2_banner_description',
            'default_theme' => 'dream2_default_theme', 'theme_color' => 'dream2_theme_color',
            'night_theme_color' => 'dream2_night_theme_color', 'default_thumbnail' => 'dream2_default_thumbnail',
            'thumbnail_mode' => 'dream2_thumbnail_mode', 'inline_css' => 'dream2_inline_css',
        );
        $value = isset($theme_mods[$key]) ? get_theme_mod($theme_mods[$key], $default) : $default;
    }
    if ($key === 'sidebar_column') return dream2_mxin_normalize_sidebar_column($value);
    if ($key === 'avatar_source') return dream2_mxin_normalize_avatar_source($value);
    if ($key === 'load_progress') return dream2_mxin_normalize_load_progress($value);
    if ($key === 'enable_hitokoto') return dream2_mxin_normalize_hitokoto_mode($value);
    return $value;
}

function dream2_enabled($key, $fallback = false) {
    return filter_var(dream2_get($key, $fallback), FILTER_VALIDATE_BOOLEAN);
}
