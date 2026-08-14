<?php
/**
 * Configurable comment emoji groups.
 *
 * @package Dream2_MXIN
 */

if (!defined('ABSPATH')) {
    exit;
}

const DREAM2_MXIN_EMOJI_CACHE_TTL = 5 * MINUTE_IN_SECONDS;
const DREAM2_MXIN_EMOJI_UPLOAD_MAX_FILES = 50;
const DREAM2_MXIN_EMOJI_UPLOAD_MAX_FILE_SIZE = 2 * MB_IN_BYTES;
const DREAM2_MXIN_EMOJI_GROUP_MAX_FILES = 200;
const DREAM2_MXIN_EMOJI_GROUP_MAX_SIZE = 50 * MB_IN_BYTES;

function dream2_mxin_flush_comment_emoji_cache() {
    unset($GLOBALS['dream2_mxin_comment_emoji_groups_cache'], $GLOBALS['dream2_mxin_comment_emoji_replacements_cache']);
    delete_transient('dream2_mxin_comment_emoji_groups');
}

function dream2_mxin_emoji_upload_base() {
    $uploads = wp_upload_dir(null, false);
    if (!empty($uploads['error'])) {
        return array('dir' => '', 'url' => '', 'error' => (string) $uploads['error']);
    }
    return array(
        'dir'   => trailingslashit($uploads['basedir']) . 'dream2-mxin/emoji',
        'url'   => trailingslashit($uploads['baseurl']) . 'dream2-mxin/emoji',
        'error' => '',
    );
}

function dream2_mxin_emoji_upload_group_dir($slug, $create = false) {
    $uploads = dream2_mxin_emoji_upload_base();
    if ($uploads['dir'] === '') return '';
    $dir = $uploads['dir'] . '/' . sanitize_key($slug);
    if ($create && !is_dir($dir) && !wp_mkdir_p($dir)) return '';
    return $dir;
}

function dream2_mxin_emoji_resolve_file($slug, $file) {
    $slug = sanitize_key($slug);
    $file = sanitize_file_name($file);
    if ($slug === '' || $file === '') return array();

    $uploads = dream2_mxin_emoji_upload_base();
    $upload_path = $uploads['dir'] !== '' ? $uploads['dir'] . '/' . $slug . '/' . $file : '';
    if ($upload_path !== '' && is_file($upload_path)) {
        return array('path' => $upload_path, 'url' => $uploads['url'] . '/' . rawurlencode($slug) . '/' . rawurlencode($file));
    }

    return array();
}

function dream2_mxin_emoji_group_files($slug) {
    $files = array();
    $base_dir = dream2_mxin_emoji_upload_base()['dir'];
    if ($base_dir !== '') {
        foreach (glob($base_dir . '/' . sanitize_key($slug) . '/*.{png,jpg,jpeg,gif,webp,avif}', GLOB_BRACE) ?: array() as $path) {
            $files[basename($path)] = $path;
        }
    }
    natcasesort($files);
    return array_slice($files, 0, 200, true);
}

function dream2_mxin_write_emoji_manifest($slug, $name, $items) {
    $dir = dream2_mxin_emoji_upload_group_dir($slug, true);
    if ($dir === '' || !is_writable($dir)) return false;
    $content = wp_json_encode(array('name' => $name, 'items' => array_map(static function ($item) {
        return array('code' => $item['code'], 'file' => $item['file']);
    }, $items)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    return false !== @file_put_contents($dir . '/manifest.json', $content, LOCK_EX);
}

function dream2_mxin_write_emoji_index($groups) {
    $uploads = dream2_mxin_emoji_upload_base();
    if ($uploads['dir'] === '' || (!is_dir($uploads['dir']) && !wp_mkdir_p($uploads['dir'])) || !is_writable($uploads['dir'])) return false;
    $content = wp_json_encode(array('groups' => array_map(static function ($group) {
        return array('name' => $group['name'], 'slug' => $group['slug']);
    }, $groups)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    return false !== @file_put_contents($uploads['dir'] . '/manifest.json', $content, LOCK_EX);
}

function dream2_mxin_emoji_group_slug($slug, $name = '') {
    $slug = sanitize_key($slug);
    return $slug ?: 'group-' . substr(md5($name . wp_generate_uuid4()), 0, 12);
}

function dream2_mxin_default_emoji_groups() {
    $groups = array();
    $uploads = dream2_mxin_emoji_upload_base();
    if ($uploads['dir'] === '') return $groups;
    $definitions = array();
    $index_path = $uploads['dir'] . '/manifest.json';
    if (is_file($index_path)) {
        $index = json_decode(file_get_contents($index_path), true);
        $definitions = is_array($index['groups'] ?? null) ? $index['groups'] : array();
    }
    $known = array_fill_keys(array_filter(array_map(static function ($definition) {
        return sanitize_key($definition['slug'] ?? '');
    }, $definitions)), true);
    foreach (glob($uploads['dir'] . '/*', GLOB_ONLYDIR) ?: array() as $dir) {
        $slug = sanitize_key(basename($dir));
        if ($slug !== '' && !isset($known[$slug])) $definitions[] = array('name' => $slug, 'slug' => $slug);
    }
    foreach (array_slice($definitions, 0, 20) as $definition) {
        $slug = sanitize_key($definition['slug'] ?? '');
        if ($slug === '') continue;
        $group = dream2_mxin_emoji_scan_group(array('name' => sanitize_text_field($definition['name'] ?? $slug), 'slug' => $slug));
        if (!empty($group['items'])) $groups[] = $group;
    }
    return $groups;
}

function dream2_mxin_comment_emoji_groups() {
    if (isset($GLOBALS['dream2_mxin_comment_emoji_groups_cache'])) {
        return $GLOBALS['dream2_mxin_comment_emoji_groups_cache'];
    }
    $cached = get_transient('dream2_mxin_comment_emoji_groups');
    if (is_array($cached)) {
        $GLOBALS['dream2_mxin_comment_emoji_groups_cache'] = $cached;
        return $cached;
    }
    $options = get_option('dream2_options', array());
    if (is_array($options) && array_key_exists('comment_emoji_groups', $options)) {
        $groups = is_array($options['comment_emoji_groups'])
            ? array_map('dream2_mxin_emoji_scan_group', array_slice($options['comment_emoji_groups'], 0, 20))
            : array();
    } else {
        $groups = dream2_mxin_default_emoji_groups();
    }
    set_transient('dream2_mxin_comment_emoji_groups', $groups, DREAM2_MXIN_EMOJI_CACHE_TTL);
    $GLOBALS['dream2_mxin_comment_emoji_groups_cache'] = $groups;
    return $groups;
}

function dream2_mxin_emoji_scan_group($group) {
    $slug = dream2_mxin_emoji_group_slug($group['slug'] ?? '', $group['name'] ?? '');
    $upload_dir = dream2_mxin_emoji_upload_group_dir($slug);
    $manifest = array();
    if ($upload_dir !== '' && is_file($upload_dir . '/manifest.json')) {
        $manifest = json_decode(file_get_contents($upload_dir . '/manifest.json'), true);
        $manifest = is_array($manifest) ? $manifest : array();
    }
    $name = mb_substr(sanitize_text_field($manifest['name'] ?? $group['name'] ?? $slug), 0, 12);
    $items = array();
    $configured_items = !empty($manifest['items']) && is_array($manifest['items']) ? $manifest['items'] : ($group['items'] ?? array());
    if (is_array($configured_items) && $configured_items) {
        $configured_files = array();
        foreach (array_slice($configured_items, 0, 200) as $item) {
            $file = sanitize_file_name($item['file'] ?? '');
            $code = mb_substr(sanitize_text_field($item['code'] ?? ''), 0, 40);
            $resolved = dream2_mxin_emoji_resolve_file($slug, $file);
            if ($file && $code && $resolved) {
                $items[] = array('code' => $code, 'file' => $file, 'url' => $resolved['url']);
                $configured_files[$file] = true;
            }
        }
        $uploaded_files = $upload_dir !== '' ? (glob($upload_dir . '/*.{png,jpg,jpeg,gif,webp,avif}', GLOB_BRACE) ?: array()) : array();
        foreach ($uploaded_files as $file_path) {
            $file = basename($file_path);
            if (isset($configured_files[$file]) || count($items) >= 200) continue;
            $resolved = dream2_mxin_emoji_resolve_file($slug, $file);
            if ($resolved) $items[] = array('code' => '[' . mb_substr(pathinfo($file, PATHINFO_FILENAME), 0, 36) . ']', 'file' => $file, 'url' => $resolved['url']);
        }
    } else {
        foreach (dream2_mxin_emoji_group_files($slug) as $file => $file_path) {
            $resolved = dream2_mxin_emoji_resolve_file($slug, $file);
            if ($resolved) $items[] = array('code' => '[' . mb_substr(pathinfo($file, PATHINFO_FILENAME), 0, 36) . ']', 'file' => $file, 'url' => $resolved['url']);
        }
    }
    return array('name' => $name, 'slug' => $slug, 'items' => $items);
}

function dream2_mxin_sanitize_comment_emoji_groups($value) {
    $groups = is_string($value) ? json_decode(wp_unslash($value), true) : $value;
    if (!is_array($groups)) return array();
    $output = array();
    foreach (array_slice($groups, 0, 20) as $group) {
        if (!is_array($group)) continue;
        $name = mb_substr(sanitize_text_field($group['name'] ?? ''), 0, 12);
        if ('' === $name) continue;
        $slug = dream2_mxin_emoji_group_slug($group['slug'] ?? '', $name);
        $clean = array('name' => $name, 'slug' => $slug, 'items' => array());
        foreach (array_slice(is_array($group['items'] ?? null) ? $group['items'] : array(), 0, 200) as $item) {
            $file = sanitize_file_name($item['file'] ?? basename(wp_parse_url($item['url'] ?? '', PHP_URL_PATH) ?: ''));
            $code = mb_substr(sanitize_text_field($item['code'] ?? ''), 0, 40);
            $resolved = dream2_mxin_emoji_resolve_file($slug, $file);
            if (!$file || !$code || !$resolved) continue;
            $clean['items'][] = array('code' => $code, 'file' => $file, 'url' => $resolved['url']);
        }
        $output[] = $clean;
        if (!dream2_mxin_write_emoji_manifest($slug, $name, $clean['items'])) {
            add_settings_error('dream2_options', 'dream2_emoji_manifest_write_failed', '表情配置已保存，但表情清单无法写入上传目录。');
        }
    }
    if (!dream2_mxin_write_emoji_index($output)) {
        add_settings_error('dream2_options', 'dream2_emoji_index_write_failed', '表情配置已保存，但表情分组索引无法写入上传目录。');
    }
    dream2_mxin_flush_comment_emoji_cache();
    return $output;
}

function dream2_mxin_emoji_admin_check() {
    if (!current_user_can('edit_theme_options')) wp_send_json_error(array('message' => '权限不足。'), 403);
    check_ajax_referer('dream2_mxin_emoji_admin', 'nonce');
}

function dream2_mxin_emoji_admin_groups_from_request() {
    $groups = json_decode(wp_unslash($_POST['groups'] ?? '[]'), true);
    return is_array($groups) ? array_slice($groups, 0, 20) : array();
}

function dream2_mxin_ajax_scan_emojis() {
    dream2_mxin_emoji_admin_check();
    wp_send_json_success(array('groups' => array_map('dream2_mxin_emoji_scan_group', dream2_mxin_emoji_admin_groups_from_request())));
}
add_action('wp_ajax_dream2_mxin_scan_emojis', 'dream2_mxin_ajax_scan_emojis');

function dream2_mxin_ajax_comment_emojis() {
    wp_send_json_success(array('groups' => dream2_mxin_comment_emoji_groups()));
}
add_action('wp_ajax_dream2_mxin_comment_emojis', 'dream2_mxin_ajax_comment_emojis');
add_action('wp_ajax_nopriv_dream2_mxin_comment_emojis', 'dream2_mxin_ajax_comment_emojis');

function dream2_mxin_ajax_upload_emojis() {
    dream2_mxin_emoji_admin_check();
    $slug = sanitize_key(wp_unslash($_POST['group'] ?? ''));
    if ($slug === '') wp_send_json_error(array('message' => '表情分组无效。'), 400);
    $dir = dream2_mxin_emoji_upload_group_dir($slug, true);
    if ($dir === '' || !is_writable($dir)) wp_send_json_error(array('message' => 'WordPress 上传目录不可写。'), 500);
    $files = $_FILES['emoji_files'] ?? array();
    $names = is_array($files['name'] ?? null) ? $files['name'] : array();
    if (!$names) wp_send_json_error(array('message' => '没有收到表情文件。'), 400);
    if (count($names) > DREAM2_MXIN_EMOJI_UPLOAD_MAX_FILES) {
        wp_send_json_error(array('message' => '单次最多上传 ' . DREAM2_MXIN_EMOJI_UPLOAD_MAX_FILES . ' 个表情。'), 400);
    }
    $existing_paths = glob($dir . '/*.{png,jpg,jpeg,gif,webp,avif}', GLOB_BRACE) ?: array();
    if (count($existing_paths) + count($names) > DREAM2_MXIN_EMOJI_GROUP_MAX_FILES) {
        wp_send_json_error(array('message' => '每个分组最多保存 ' . DREAM2_MXIN_EMOJI_GROUP_MAX_FILES . ' 个表情。'), 400);
    }
    $group_size = array_sum(array_map(static function ($path) {
        return is_file($path) ? (int) filesize($path) : 0;
    }, $existing_paths));
    $validated = array();
    $upload_size = 0;
    foreach ($names as $index => $original_name) {
        $tmp = $files['tmp_name'][$index] ?? '';
        $error = (int) ($files['error'][$index] ?? UPLOAD_ERR_NO_FILE);
        $size = (int) ($files['size'][$index] ?? 0);
        if ($error !== UPLOAD_ERR_OK || !$tmp || !is_uploaded_file($tmp)) {
            wp_send_json_error(array('message' => '有文件上传失败，请重新选择后再试。'), 400);
        }
        if ($size <= 0 || $size > DREAM2_MXIN_EMOJI_UPLOAD_MAX_FILE_SIZE) {
            wp_send_json_error(array('message' => '单个表情不能超过 2 MB。'), 400);
        }
        $type = wp_check_filetype_and_ext($tmp, $original_name, array('png' => 'image/png', 'jpg|jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp', 'avif' => 'image/avif'));
        if (empty($type['ext']) || empty($type['type'])) {
            wp_send_json_error(array('message' => '只允许 PNG、JPG、GIF、WebP 或 AVIF 图片。'), 400);
        }
        $upload_size += $size;
        $validated[] = array('tmp' => $tmp, 'name' => sanitize_file_name($original_name));
    }
    if ($group_size + $upload_size > DREAM2_MXIN_EMOJI_GROUP_MAX_SIZE) {
        wp_send_json_error(array('message' => '单个表情分组不能超过 50 MB。'), 400);
    }
    $moved = array();
    foreach ($validated as $file_data) {
        $file = wp_unique_filename($dir, $file_data['name']);
        $destination = $dir . '/' . $file;
        if (!move_uploaded_file($file_data['tmp'], $destination)) {
            foreach ($moved as $path) {
                wp_delete_file($path);
            }
            wp_send_json_error(array('message' => '表情写入失败，已撤销本次上传。'), 500);
        }
        $moved[] = $destination;
    }
    dream2_mxin_flush_comment_emoji_cache();
    wp_send_json_success(array('uploaded' => count($moved)));
}
add_action('wp_ajax_dream2_mxin_upload_emojis', 'dream2_mxin_ajax_upload_emojis');

function dream2_mxin_ajax_delete_emoji() {
    dream2_mxin_emoji_admin_check();
    $slug = sanitize_key(wp_unslash($_POST['group'] ?? ''));
    $file = sanitize_file_name(wp_unslash($_POST['file'] ?? ''));
    $dir = dream2_mxin_emoji_upload_group_dir($slug);
    $path = $dir !== '' ? $dir . '/' . $file : '';
    if ($slug && $file && $path !== '' && is_file($path)) wp_delete_file($path);
    dream2_mxin_flush_comment_emoji_cache();
    wp_send_json_success();
}
add_action('wp_ajax_dream2_mxin_delete_emoji', 'dream2_mxin_ajax_delete_emoji');

function dream2_mxin_render_comment_emojis($text) {
    if (!isset($GLOBALS['dream2_mxin_comment_emoji_replacements_cache'])) {
        $replacements = array();
        foreach (dream2_mxin_comment_emoji_groups() as $group) {
            foreach (array_slice($group['items'] ?? array(), 0, DREAM2_MXIN_EMOJI_GROUP_MAX_FILES) as $item) {
                $replacements[$item['code']] = '<img class="dream-comment-emoji-rendered" src="' . esc_url($item['url']) . '" alt="' . esc_attr($item['code']) . '" loading="lazy">';
            }
        }
        $GLOBALS['dream2_mxin_comment_emoji_replacements_cache'] = $replacements;
    }
    $replacements = $GLOBALS['dream2_mxin_comment_emoji_replacements_cache'];
    return $replacements ? strtr($text, $replacements) : $text;
}
add_filter('comment_text', 'dream2_mxin_render_comment_emojis', 30);
