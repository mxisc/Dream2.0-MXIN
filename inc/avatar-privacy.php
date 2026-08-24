<?php
/**
 * Privacy-preserving avatar cache.
 *
 * @package Dream2_MXIN
 */

if (!defined('ABSPATH')) {
    exit;
}

function dream2_mxin_avatar_privacy_enabled() {
    return dream2_enabled('avatar_privacy', true);
}

function dream2_mxin_avatar_default_url() {
    return add_query_arg('ver', rawurlencode(DREAM2_MXIN_VERSION), dream2_mxin_asset('img/avatar.svg'));
}

function dream2_mxin_avatar_cache_token($type, $identity) {
    return substr(hash_hmac('sha256', sanitize_key($type) . ':' . (string) $identity, wp_salt('auth')), 0, 24);
}

function dream2_mxin_avatar_cache_location($type, $identity) {
    $type = in_array($type, array('comment', 'friend', 'user'), true) ? $type : 'comment';
    $token = dream2_mxin_avatar_cache_token($type, $identity);
    $relative = 'dream-avatar-cache/v1/' . $type . '/' . substr($token, 0, 2) . '/' . $token . '.webp';
    $uploads = wp_upload_dir(null, false);

    if (!empty($uploads['error'])) {
        return array();
    }

    return array(
        'path'         => trailingslashit($uploads['basedir']) . $relative,
        'url'          => trailingslashit($uploads['baseurl']) . str_replace(DIRECTORY_SEPARATOR, '/', $relative),
        'version_path' => trailingslashit($uploads['basedir']) . $relative . '.version',
        'failed_path'  => trailingslashit($uploads['basedir']) . $relative . '.failed',
    );
}

function dream2_mxin_avatar_cache_public_url($location) {
    if (empty($location['path']) || !is_file($location['path'])) {
        return '';
    }
    $version = dream2_mxin_avatar_cache_version($location);
    $url = $version ? add_query_arg('ver', $version, $location['url']) : $location['url'];
    return esc_url_raw(apply_filters('dream2_mxin_avatar_cache_url', $url, $location));
}

function dream2_mxin_avatar_cache_version($location) {
    if (!empty($location['version_path']) && is_file($location['version_path'])) {
        $version = trim((string) file_get_contents($location['version_path']));
        if (preg_match('/^[a-f0-9]{12}$/', $version)) {
            return $version;
        }
    }
    return !empty($location['path']) && is_file($location['path'])
        ? substr(hash_file('sha256', $location['path']), 0, 12)
        : '';
}

function dream2_mxin_avatar_cache_write_version($location, $version) {
    if (empty($location['version_path']) || !preg_match('/^[a-f0-9]{12}$/', (string) $version)) {
        return;
    }
    $temporary = $location['version_path'] . '.tmp-' . wp_generate_password(8, false, false);
    if (file_put_contents($temporary, $version, LOCK_EX) === false) {
        return;
    }
    if (!@rename($temporary, $location['version_path'])) {
        wp_delete_file($temporary);
    }
}

function dream2_mxin_avatar_cache_schedule($type, $identity, $source_url) {
    $source_url = esc_url_raw($source_url, array('http', 'https'));
    if ($source_url === '') {
        return;
    }

    $args = array($type, (string) $identity, $source_url);
    if (!wp_next_scheduled('dream2_mxin_refresh_avatar_cache', $args)) {
        $token = dream2_mxin_avatar_cache_token($type, $identity);
        $delay = 30 + (hexdec(substr($token, 0, 4)) % 600);
        wp_schedule_single_event(time() + $delay, 'dream2_mxin_refresh_avatar_cache', $args);
    }
}

function dream2_mxin_avatar_cache_unschedule($type, $identity, $source_url) {
    $args = array($type, (string) $identity, $source_url);
    $timestamp = wp_next_scheduled('dream2_mxin_refresh_avatar_cache', $args);
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'dream2_mxin_refresh_avatar_cache', $args);
    }
}

function dream2_mxin_avatar_pending_token($type, $identity, $source_url) {
    $token = dream2_mxin_avatar_cache_token($type, $identity);
    set_transient('dream2_avatar_' . $token, array(
        'type'        => $type,
        'identity'    => (string) $identity,
        'source_url'  => esc_url_raw($source_url, array('http', 'https')),
    ), 15 * MINUTE_IN_SECONDS);
    return $token;
}

function dream2_mxin_avatar_privacy_url($type, $identity, $source_url, &$pending_token = '') {
    $pending_token = '';
    if (!dream2_mxin_avatar_privacy_enabled()) {
        return '';
    }

    $location = dream2_mxin_avatar_cache_location($type, $identity);
    if (!$location) {
        return dream2_mxin_avatar_default_url();
    }

    if (is_file($location['path'])) {
        if (filemtime($location['path']) < time() - 7 * DAY_IN_SECONDS) {
            dream2_mxin_avatar_cache_schedule($type, $identity, $source_url);
        }
        return dream2_mxin_avatar_cache_public_url($location);
    }

    $failed_recently = !empty($location['failed_path'])
        && is_file($location['failed_path'])
        && filemtime($location['failed_path']) >= time() - HOUR_IN_SECONDS;
    if (!$failed_recently) {
        $pending_token = dream2_mxin_avatar_pending_token($type, $identity, $source_url);
        dream2_mxin_avatar_cache_schedule($type, $identity, $source_url);
    }
    return dream2_mxin_avatar_default_url();
}

function dream2_mxin_avatar_cache_fetch($type, $identity, $source_url) {
    $location = dream2_mxin_avatar_cache_location($type, $identity);
    if (!$location || !wp_mkdir_p(dirname($location['path']))) {
        return false;
    }
    $lock = fopen($location['path'] . '.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
        if ($lock) {
            fclose($lock);
        }
        return false;
    }
    try {
        return dream2_mxin_avatar_cache_fetch_unlocked($type, $identity, $source_url);
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
        wp_delete_file($location['path'] . '.lock');
    }
}

function dream2_mxin_avatar_cache_fetch_unlocked($type, $identity, $source_url) {
    if (!dream2_mxin_avatar_privacy_enabled()) {
        return false;
    }

    $location = dream2_mxin_avatar_cache_location($type, $identity);
    if (!$location) {
        return false;
    }

    $response = wp_safe_remote_get($source_url, array(
        'timeout'             => 8,
        'redirection'         => 3,
        'limit_response_size' => 2 * MB_IN_BYTES,
        'headers'             => array('Accept' => 'image/avif,image/webp,image/png,image/jpeg,image/gif'),
        'user-agent'          => 'Dream2-MXIN Avatar Cache/' . DREAM2_MXIN_VERSION,
    ));
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        dream2_mxin_avatar_cache_mark_failed($location);
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    if ($body === '' || strlen($body) > 2 * MB_IN_BYTES) {
        dream2_mxin_avatar_cache_mark_failed($location);
        return false;
    }

    $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif', 'image/x-icon', 'image/vnd.microsoft.icon');

    require_once ABSPATH . 'wp-admin/includes/file.php';
    $tmp = wp_tempnam('dream-avatar');
    if (!$tmp || file_put_contents($tmp, $body, LOCK_EX) === false) {
        if ($tmp) {
            wp_delete_file($tmp);
        }
        dream2_mxin_avatar_cache_mark_failed($location);
        return false;
    }

    $image_info = @getimagesize($tmp);
    if (!$image_info || !in_array($image_info['mime'] ?? '', $allowed_types, true)) {
        wp_delete_file($tmp);
        dream2_mxin_avatar_cache_mark_failed($location);
        return false;
    }

    if (in_array($image_info['mime'], array('image/x-icon', 'image/vnd.microsoft.icon'), true)) {
        $normalized = dream2_mxin_avatar_cache_normalize_ico($tmp);
        if ($normalized === '') {
            wp_delete_file($tmp);
            dream2_mxin_avatar_cache_mark_failed($location);
            return false;
        }
        wp_delete_file($tmp);
        $tmp = $normalized;
        $image_info = @getimagesize($tmp);
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    $editor = wp_get_image_editor($tmp);
    if (is_wp_error($editor)) {
        wp_delete_file($tmp);
        dream2_mxin_avatar_cache_mark_failed($location);
        return false;
    }

    $width = (int) ($image_info[0] ?? 0);
    $height = (int) ($image_info[1] ?? 0);
    $target_size = min(256, $width, $height);
    if ($target_size < 1) {
        wp_delete_file($tmp);
        dream2_mxin_avatar_cache_mark_failed($location);
        return false;
    }
    if ($width !== $height || $width > $target_size) {
        $resized = $editor->resize($target_size, $target_size, true);
        if (is_wp_error($resized)) {
            wp_delete_file($tmp);
            dream2_mxin_avatar_cache_mark_failed($location);
            return false;
        }
    }
    $directory = dirname($location['path']);
    if (!wp_mkdir_p($directory)) {
        wp_delete_file($tmp);
        return false;
    }

    $temporary_output = $location['path'] . '.tmp-' . wp_generate_password(8, false, false);
    $saved = $editor->save($temporary_output, 'image/webp');
    wp_delete_file($tmp);
    if (is_wp_error($saved) || empty($saved['path']) || !is_file($saved['path'])) {
        if (is_file($temporary_output)) {
            wp_delete_file($temporary_output);
        }
        dream2_mxin_avatar_cache_mark_failed($location);
        return false;
    }

    $new_version = substr(hash_file('sha256', $saved['path']), 0, 12);
    $current_version = is_file($location['path'])
        ? substr(hash_file('sha256', $location['path']), 0, 12)
        : '';
    if ($new_version === $current_version) {
        wp_delete_file($saved['path']);
        touch($location['path']);
        dream2_mxin_avatar_cache_write_version($location, $new_version);
        dream2_mxin_avatar_cache_clear_failed($location);
        return true;
    }

    $previous_output = $location['path'] . '.old';
    if (is_file($previous_output)) {
        wp_delete_file($previous_output);
    }
    if (is_file($location['path']) && !@rename($location['path'], $previous_output)) {
        wp_delete_file($saved['path']);
        return false;
    }
    if (!@rename($saved['path'], $location['path'])) {
        if (is_file($previous_output)) {
            @rename($previous_output, $location['path']);
        }
        wp_delete_file($saved['path']);
        return false;
    }
    if (is_file($previous_output)) {
        wp_delete_file($previous_output);
    }
    @chmod($location['path'], 0644);
    dream2_mxin_avatar_cache_write_version($location, $new_version);
    dream2_mxin_avatar_cache_clear_failed($location);
    return true;
}
add_action('dream2_mxin_refresh_avatar_cache', 'dream2_mxin_avatar_cache_fetch', 10, 3);

function dream2_mxin_avatar_cache_normalize_ico($source_path) {
    if (!class_exists('Imagick')) {
        return '';
    }
    $output_path = $source_path . '.png';
    try {
        $image = new Imagick('ico:' . $source_path);
        $best_index = 0;
        $best_area = 0;
        foreach ($image as $index => $frame) {
            $area = $frame->getImageWidth() * $frame->getImageHeight();
            if ($area > $best_area) {
                $best_area = $area;
                $best_index = $index;
            }
        }
        $image->setIteratorIndex($best_index);
        $image->setImageFormat('png');
        $image->stripImage();
        $written = $image->writeImage($output_path);
        $image->clear();
        $image->destroy();
        return $written && is_file($output_path) ? $output_path : '';
    } catch (Throwable $error) {
        if (is_file($output_path)) {
            wp_delete_file($output_path);
        }
        return '';
    }
}

function dream2_mxin_avatar_cache_mark_failed($location) {
    if (empty($location['failed_path'])) {
        return;
    }
    $directory = dirname($location['failed_path']);
    if (wp_mkdir_p($directory)) {
        touch($location['failed_path']);
    }
}

function dream2_mxin_avatar_cache_clear_failed($location) {
    if (!empty($location['failed_path']) && is_file($location['failed_path'])) {
        wp_delete_file($location['failed_path']);
    }
}

function dream2_mxin_avatar_cache_batch() {
    if (!dream2_mxin_avatar_privacy_enabled()) {
        wp_send_json_error(array('message' => 'disabled'), 403);
    }
    check_ajax_referer('dream2_mxin_avatar_cache', 'nonce');

    $tokens = array_slice(array_values(array_unique(array_filter(array_map(
        'sanitize_key',
        (array) wp_unslash($_POST['tokens'] ?? array())
    )))), 0, 12);
    $avatars = array();

    foreach ($tokens as $token) {
        if (!preg_match('/^[a-f0-9]{24}$/', $token)) {
            continue;
        }
        $request = get_transient('dream2_avatar_' . $token);
        if (!is_array($request) || empty($request['type']) || empty($request['identity']) || empty($request['source_url'])) {
            continue;
        }
        $expected = dream2_mxin_avatar_cache_token($request['type'], $request['identity']);
        if (!hash_equals($expected, $token)) {
            continue;
        }

        $location = dream2_mxin_avatar_cache_location($request['type'], $request['identity']);
        if (!$location) {
            continue;
        }
        if (!is_file($location['path'])) {
            dream2_mxin_avatar_cache_fetch($request['type'], $request['identity'], $request['source_url']);
        }
        $url = dream2_mxin_avatar_cache_public_url($location);
        if ($url !== '') {
            dream2_mxin_avatar_cache_unschedule($request['type'], $request['identity'], $request['source_url']);
        }
        $avatars[$token] = $url ?: dream2_mxin_avatar_default_url();
        delete_transient('dream2_avatar_' . $token);
    }

    wp_send_json_success(array('avatars' => $avatars));
}
add_action('wp_ajax_dream2_mxin_avatar_cache', 'dream2_mxin_avatar_cache_batch');
add_action('wp_ajax_nopriv_dream2_mxin_avatar_cache', 'dream2_mxin_avatar_cache_batch');
