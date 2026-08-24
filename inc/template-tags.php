<?php
/**
 * Template helpers.
 *
 * @package Dream2_MXIN
 */

if (!defined('ABSPATH')) {
    exit;
}

function dream2_mxin_logo() {
    $logo_id = get_theme_mod('custom_logo');
    $logo = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : '';
    if ($logo) {
        $night_logo = dream2_get('night_logo', '') ?: $logo;
        printf(
            '<img class="logo-img" src="%1$s" alt="%3$s" height="28"><img class="logo-img-dark" src="%2$s" alt="%3$s" height="28">',
            esc_url($logo),
            esc_url($night_logo),
            esc_attr(get_bloginfo('name'))
        );
    } else {
        echo esc_html(get_bloginfo('name'));
    }
}

function dream2_mxin_post_thumbnail_url($post_id = null) {
    $post_id = $post_id ?: get_the_ID();
    if (has_post_thumbnail($post_id)) {
        return get_the_post_thumbnail_url($post_id, 'large');
    }
    return esc_url_raw(dream2_get('default_thumbnail', ''));
}

function dream2_mxin_post_meta($compact = false) {
    ?>
    <ul class="breadcrumb">
        <li><?php echo esc_html(get_the_date('Y-m-d H:i')); ?></li>
        <li><i class="ri-eye-line"></i><?php echo esc_html(dream2_mxin_format_compact_count(dream2_mxin_get_post_views(get_the_ID()))); ?></li>
        <?php if (comments_open() || get_comments_number()) : ?>
            <li class="<?php echo $compact ? 'is-hidden-mobile' : ''; ?>"><i class="ri-question-answer-line"></i><?php echo esc_html((string) get_comments_number()); ?></li>
        <?php endif; ?>
        <li><i class="ri-quill-pen-line"></i><?php echo esc_html(dream2_mxin_format_compact_count(dream2_mxin_word_count(get_the_content(null, false, get_the_ID())))); ?></li>
    </ul>
    <?php
}

function dream2_mxin_format_compact_count($number) {
    $number = (int) $number;
    if ($number < 1000) {
        return (string) $number;
    }
    $formatted = number_format(round($number / 1000, 2), 2, '.', '');
    return rtrim(rtrim($formatted, '0'), '.') . 'k';
}

function dream2_mxin_get_post_views($post_id = null) {
    $post_id = $post_id ?: get_the_ID();
    if (!$post_id) {
        return '0';
    }
    $count = get_post_meta($post_id, 'post_views_count', true);
    if ($count === '') {
        return '0';
    }
    return (string) $count;
}

function dream2_mxin_get_total_post_views() {
    global $wpdb;
    $cache_key = 'dream2_mxin_total_post_views';
    $cached = get_transient($cache_key);
    if (false !== $cached) {
        return (int) $cached;
    }
    $total = (int) $wpdb->get_var(
        "SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key = 'post_views_count'"
    );
    set_transient($cache_key, $total, 5 * MINUTE_IN_SECONDS);
    return $total;
}

function dream2_mxin_set_post_views($post_id = null) {
    $post_id = $post_id ?: get_the_ID();
    if (!$post_id || get_post_type($post_id) !== 'post') {
        return;
    }
    global $wpdb;
    $updated = $wpdb->query($wpdb->prepare(
        "UPDATE {$wpdb->postmeta}
        SET meta_value = CAST(meta_value AS UNSIGNED) + 1
        WHERE post_id = %d AND meta_key = 'post_views_count'
        LIMIT 1",
        $post_id
    ));
    if (!$updated && !add_post_meta($post_id, 'post_views_count', '1', true)) {
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->postmeta}
            SET meta_value = CAST(meta_value AS UNSIGNED) + 1
            WHERE post_id = %d AND meta_key = 'post_views_count'
            LIMIT 1",
            $post_id
        ));
    }
    wp_cache_delete($post_id, 'post_meta');
}

function dream2_mxin_word_count($content) {
    $text = wp_strip_all_tags(strip_shortcodes($content));
    if (preg_match('/[\x{4e00}-\x{9fff}]/u', $text)) {
        preg_match_all('/[\x{4e00}-\x{9fff}]|[A-Za-z0-9]+/u', $text, $matches);
        return count($matches[0]);
    }
    return str_word_count($text);
}

function dream2_mxin_post_card_excerpt($post_id = null) {
    $post_id = $post_id ?: get_the_ID();
    $excerpt = has_excerpt($post_id) ? get_the_excerpt($post_id) : '';
    if (trim((string) $excerpt) === '') {
        $excerpt = get_post_field('post_content', $post_id);
    }
    $excerpt = wp_strip_all_tags(strip_shortcodes($excerpt));
    $excerpt = trim((string) preg_replace('/\s+/u', ' ', $excerpt));
    $length = (int) apply_filters('dream2_mxin_post_card_excerpt_length', 150);
    if ($length > 0 && function_exists('mb_strlen')) {
        return mb_strlen($excerpt, 'UTF-8') > $length ? mb_substr($excerpt, 0, $length, 'UTF-8') : $excerpt;
    }
    if ($length > 0 && strlen($excerpt) > $length) {
        return substr($excerpt, 0, $length);
    }
    return $excerpt;
}

function dream2_mxin_category_links($hide_mobile = false) {
    $categories = get_the_category();
    if (!$categories) {
        return;
    }
    printf('<div class="level-item%s">', $hide_mobile ? ' is-hidden-mobile' : '');
    foreach ($categories as $category) {
        printf('<a href="%s">%s</a>&nbsp;', esc_url(get_category_link($category)), esc_html($category->name));
    }
    echo '</div>';
}

function dream2_mxin_render_pagination_links($links) {
    if (!$links) {
        return;
    }
    $previous = '<span class="pagination-previous is-invisible is-hidden-mobile">' . esc_html__('上一页', 'dream2-mxin') . '</span>';
    $next = '<span class="pagination-next is-invisible is-hidden-mobile">' . esc_html__('下一页', 'dream2-mxin') . '</span>';
    $items = array();

    foreach ($links as $link) {
        if (strpos($link, 'prev page-numbers') !== false) {
            $previous = str_replace('prev page-numbers', 'pagination-previous', $link);
            continue;
        }
        if (strpos($link, 'next page-numbers') !== false) {
            $next = str_replace('next page-numbers', 'pagination-next', $link);
            continue;
        }
        $link = str_replace('page-numbers current', 'pagination-link is-current', $link);
        $link = str_replace('page-numbers', 'pagination-link', $link);
        $items[] = '<li>' . wp_kses_post($link) . '</li>';
    }

    echo '<div class="card card-transparent"><nav class="pagination" role="navigation" aria-label="' . esc_attr__('pagination', 'dream2-mxin') . '">';
    echo wp_kses_post($previous);
    echo wp_kses_post($next);
    echo '<ul class="pagination-list is-hidden-mobile">' . implode('', $items) . '</ul>';
    echo '</nav></div>';
}

function dream2_mxin_pagination() {
    $links = paginate_links(array(
        'type'      => 'array',
        'prev_text' => __('上一页', 'dream2-mxin'),
        'next_text' => __('下一页', 'dream2-mxin'),
    ));
    dream2_mxin_render_pagination_links($links);
}

function dream2_mxin_parse_repeater($value) {
    if (is_array($value)) {
        return $value;
    }
    $value = trim((string) $value);
    if ($value === '') {
        return array();
    }
    $json = json_decode($value, true);
    if (is_array($json)) {
        return $json;
    }
    $items = array();
    foreach (preg_split('/\r\n|\r|\n/', $value) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $parts = array_map('trim', explode('|', $line));
        $items[] = array(
            'title' => $parts[0] ?? '',
            'url' => $parts[1] ?? '',
            'image' => $parts[2] ?? '',
            'tag' => $parts[3] ?? '',
            'target' => $parts[4] ?? '_self',
        );
    }
    return $items;
}

function dream2_mxin_render_home_modules() {
    $carousel = dream2_mxin_parse_repeater(dream2_get('carousel_options', ''));
    $modules = dream2_mxin_parse_repeater(dream2_get('module_options', ''));
    if (!$carousel && !$modules) return;
    ?>
    <div class="model model-index">
        <?php if ($carousel) : ?>
            <div class="card widget swiper dream-carousel">
                <div class="swiper-wrapper">
                    <?php foreach ($carousel as $item) : ?>
                        <a class="swiper-slide bg-shadow cover-image" href="<?php echo esc_url($item['url'] ?? '#'); ?>" target="<?php echo esc_attr($item['target'] ?? '_self'); ?>" style="background-image:url('<?php echo esc_url($item['image'] ?? ''); ?>')">
                            <div class="swiper-slide-details"><p class="swiper-slide-details-title"><?php echo esc_html($item['title'] ?? ''); ?></p></div>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="swiper-pagination"></div><div class="swiper-button-prev"></div><div class="swiper-button-next"></div>
            </div>
        <?php endif; ?>
        <?php if ($modules) : ?><div class="model model-index-side"><?php foreach (array_slice($modules, 0, 2) as $item) : ?>
            <a class="card widget brightness bg-shadow" href="<?php echo esc_url($item['url'] ?? '#'); ?>" target="<?php echo esc_attr($item['target'] ?? '_self'); ?>" style="background-image:url('<?php echo esc_url($item['image'] ?? ''); ?>')"><div class="title"><?php echo esc_html($item['title'] ?? ''); ?></div><div class="tag"><?php echo esc_html($item['tag'] ?? ''); ?></div></a>
        <?php endforeach; ?></div><?php endif; ?>
    </div>
    <?php if (count($modules) > 2) : ?><div class="model model-attach model-attach-<?php echo esc_attr((string) min(4, count($modules) - 2)); ?>"><?php foreach (array_slice($modules, 2, 4) as $item) : ?>
        <a class="card widget brightness bg-shadow" href="<?php echo esc_url($item['url'] ?? '#'); ?>" target="<?php echo esc_attr($item['target'] ?? '_self'); ?>" style="background-image:url('<?php echo esc_url($item['image'] ?? ''); ?>')"><div class="title"><?php echo esc_html($item['title'] ?? ''); ?></div><div class="tag"><?php echo esc_html($item['tag'] ?? ''); ?></div></a>
    <?php endforeach; ?></div><?php endif; ?>
    <?php
}

function dream2_mxin_menu_fallback() {
    printf('<a class="item" href="%s">%s</a>', esc_url(home_url('/')), esc_html__('首页', 'dream2-mxin'));
    wp_list_pages(array(
        'title_li' => '',
        'depth'    => 1,
        'walker'   => new Dream2_MXIN_Page_Walker(),
    ));
}

class Dream2_MXIN_Page_Walker extends Walker_Page {
    public function start_el(&$output, $page, $depth = 0, $args = array(), $current_page = 0) {
        $output .= sprintf(
            '<a class="item" href="%s">%s</a>',
            esc_url(get_permalink($page->ID)),
            esc_html($page->post_title)
        );
    }

    public function end_el(&$output, $page, $depth = 0, $args = array()) {
    }
}

function dream2_mxin_loli_option($name, $default = false) {
    $options = get_option('options-framework-theme', array());
    return is_array($options) && array_key_exists($name, $options) ? $options[$name] : $default;
}

function dream2_mxin_gravatar_url($email, $host = 'secure.gravatar.com/avatar') {
    $email = trim(strtolower((string) $email));
    return esc_url_raw('https://' . $host . '/' . md5($email));
}

function dream2_mxin_qq_avatar_host() {
    switch (dream2_mxin_loli_option('qqavatar_url')) {
        case 'Q1':
            return 'q1.qlogo.cn';
        case 'Q2':
            return 'q2.qlogo.cn';
        case 'Q3':
            return 'q3.qlogo.cn';
        case 'Q4':
        default:
            return 'q2.qlogo.cn';
    }
}

function dream2_mxin_qq_avatar_url($email) {
    $email = trim((string) $email);
    if (false === stripos($email, '@qq.com')) {
        return '';
    }

    $qq = str_ireplace('@qq.com', '', $email);
    return preg_match('/^\d+$/', $qq)
        ? esc_url_raw('https://' . dream2_mxin_qq_avatar_host() . '/headimg_dl?dst_uin=' . $qq . '&spec=100')
        : '';
}

function dream2_mxin_default_avatar_url() {
    if (function_exists('dream2_mxin_avatar_privacy_enabled') && dream2_mxin_avatar_privacy_enabled()) {
        return esc_url_raw(dream2_mxin_avatar_default_url());
    }
    return esc_url_raw(dream2_get('links_default_avatar', dream2_mxin_asset('img/avatar.svg')) ?: dream2_mxin_asset('img/avatar.svg'));
}

function dream2_mxin_profile_user() {
    static $user = null;
    static $resolved = false;

    if ($resolved) {
        return $user;
    }

    $resolved = true;
    $admin_email = sanitize_email(get_option('admin_email'));
    if ($admin_email) {
        $user = get_user_by('email', $admin_email);
    }

    if (!$user) {
        $user = get_userdata(1);
    }

    return $user instanceof WP_User ? $user : null;
}

function dream2_mxin_profile_avatar_target() {
    $user = dream2_mxin_profile_user();
    return $user ? (int) $user->ID : get_option('admin_email');
}

function dream2_mxin_profile_display_name() {
    $user = dream2_mxin_profile_user();
    if ($user) {
        foreach (array($user->display_name, $user->nickname, $user->user_login) as $name) {
            $name = trim((string) $name);
            if ($name !== '') {
                return $name;
            }
        }
    }
    return get_bloginfo('name');
}

function dream2_mxin_avatar_email_and_user($id_or_email) {
    $email = '';
    $user_id = 0;

    if (is_numeric($id_or_email)) {
        $user_id = (int) $id_or_email;
        $user = get_userdata($user_id);
        if ($user) {
            $email = $user->user_email;
        }
    } elseif (is_object($id_or_email)) {
        $user_id = !empty($id_or_email->user_id) ? (int) $id_or_email->user_id : 0;
        if ($user_id) {
            $user = get_userdata($user_id);
            if ($user) {
                $email = $user->user_email;
            }
        } elseif (!empty($id_or_email->comment_author_email)) {
            $email = $id_or_email->comment_author_email;
        }
    } else {
        $email = (string) $id_or_email;
    }

    return array($email, $user_id);
}

function dream2_mxin_avatar_url($id_or_email, $size = 48, &$pending_token = '') {
    $pending_token = '';
    list($email, $user_id) = dream2_mxin_avatar_email_and_user($id_or_email);

    if ($user_id) {
        $user_avatar_url = get_user_meta($user_id, 'user_avatar', true);
        if ($user_avatar_url) {
            if (dream2_mxin_avatar_privacy_enabled()) {
                return dream2_mxin_avatar_privacy_url('user', $user_id . '|' . $user_avatar_url, $user_avatar_url, $pending_token);
            }
            return esc_url_raw($user_avatar_url);
        }
    }

    if (dream2_mxin_is_widget_editor_request() || dream2_mxin_is_widget_rest_preview_request()) {
        return dream2_mxin_default_avatar_url();
    }

    $qq_avatar_url = dream2_mxin_qq_avatar_url($email);
    if ($qq_avatar_url !== '') {
        if (dream2_mxin_avatar_privacy_enabled()) {
            return dream2_mxin_avatar_privacy_url('comment', trim(strtolower((string) $email)) . '|' . $qq_avatar_url, $qq_avatar_url, $pending_token);
        }
        return $qq_avatar_url;
    }

    $gravatar_url = dream2_mxin_gravatar_url($email, 'secure.gravatar.com/avatar');
    if (dream2_mxin_avatar_privacy_enabled()) {
        return dream2_mxin_avatar_privacy_url('comment', trim(strtolower((string) $email)) . '|' . $gravatar_url, $gravatar_url, $pending_token);
    }
    return $gravatar_url;
}

function dream2_mxin_avatar_fallback_urls($id_or_email, $size = 48) {
    if (dream2_mxin_avatar_privacy_enabled()) {
        return array(dream2_mxin_default_avatar_url());
    }

    list($email, $user_id) = dream2_mxin_avatar_email_and_user($id_or_email);
    $fallbacks = array();

    $email = trim((string) $email);
    if ($email !== '' && dream2_mxin_qq_avatar_url($email) === '') {
        $fallbacks[] = dream2_mxin_gravatar_url($email, 'dn-qiniu-avatar.qbox.me/avatar');
    }
    $fallbacks[] = dream2_mxin_default_avatar_url();

    return array_values(array_unique(array_filter($fallbacks)));
}

function dream2_mxin_normalize_friend_site_url($url) {
    $url = trim((string) $url);
    if ($url === '') {
        return false;
    }
    if (!preg_match('#^https?://#i', $url)) {
        $url = 'https://' . ltrim($url, '/');
    }
    $parts = wp_parse_url($url);
    if (empty($parts['host'])) {
        return false;
    }
    $host = strtolower($parts['host']);
    if (str_starts_with($host, 'www.')) {
        $host = substr($host, 4);
    }
    return $host;
}

function dream2_mxin_friend_site_urls_match($comment_url, $link_url) {
    $comment = dream2_mxin_normalize_friend_site_url($comment_url);
    $link = dream2_mxin_normalize_friend_site_url($link_url);
    return $comment && $link && $comment === $link;
}

function dream2_mxin_comment_friend_avatar_url($id_or_email, &$pending_token = '') {
    $pending_token = '';
    if (!is_object($id_or_email) || empty($id_or_email->comment_author_url)) {
        return '';
    }

    $comment_host = dream2_mxin_normalize_friend_site_url($id_or_email->comment_author_url);
    if (!$comment_host) {
        return '';
    }
    static $friend_avatars = null;
    if ($friend_avatars === null) {
        $args = array(
            'hide_invisible' => true,
            'orderby'        => 'name',
            'order'          => 'ASC',
        );
        if (function_exists('dream2_mxin_friend_link_category_ids')) {
            $category_ids = dream2_mxin_friend_link_category_ids();
            if ($category_ids) {
                $args['category'] = implode(',', array_map('intval', $category_ids));
            }
        }
        $bookmarks = get_bookmarks($args);
        $friend_avatars = array();
        foreach ($bookmarks as $bookmark) {
            if (empty($bookmark->link_url) || empty($bookmark->link_image)) {
                continue;
            }
            $host = dream2_mxin_normalize_friend_site_url($bookmark->link_url);
            if ($host && !isset($friend_avatars[$host])) {
                $friend_avatars[$host] = array(
                    'id'  => (string) $bookmark->link_id,
                    'url' => esc_url_raw($bookmark->link_image),
                );
            }
        }
    }
    $friend_avatar = $friend_avatars[$comment_host] ?? array();
    if (!$friend_avatar) {
        return '';
    }
    if (dream2_mxin_avatar_privacy_enabled()) {
        return dream2_mxin_avatar_privacy_url('friend', $friend_avatar['id'] . '|' . $friend_avatar['url'], $friend_avatar['url'], $pending_token);
    }
    return $friend_avatar['url'];
}

function dream2_mxin_loli_avatar($avatar, $id_or_email, $size = 96, $default = '', $alt = '', $args = array()) {
    $pending_token = '';
    $friend_avatar_url = dream2_mxin_comment_friend_avatar_url($id_or_email, $pending_token);
    $fallback_urls = array();
    if ($friend_avatar_url && dream2_mxin_avatar_privacy_enabled()) {
        $display_url = $friend_avatar_url;
        $fallback_urls[] = dream2_mxin_default_avatar_url();
    } else {
        $url_token = '';
        $url = dream2_mxin_avatar_url($id_or_email, $size, $url_token);
        $display_url = $friend_avatar_url ?: $url;
        if (!$friend_avatar_url) {
            $pending_token = $url_token;
        }
        if ($friend_avatar_url && $url !== $friend_avatar_url) {
            $fallback_urls[] = $url;
        }
        $fallback_urls = array_merge($fallback_urls, dream2_mxin_avatar_fallback_urls($id_or_email, $size));
    }
    $fallback_urls = array_values(array_filter(array_unique(array_diff($fallback_urls, array($display_url)))));
    $classes = array('avatar', 'avatar-' . (int) $size, 'photo');
    if (!empty($args['class'])) {
        $classes = array_merge($classes, array_filter(array_map('sanitize_html_class', explode(' ', (string) $args['class']))));
    }
    $classes = array_values(array_unique($classes));

    return sprintf(
        '<img src="%1$s" class="%2$s" alt="%3$s" width="%4$d" height="%4$d"%5$s%6$s loading="lazy" decoding="async">',
        esc_url($display_url),
        esc_attr(implode(' ', $classes)),
        esc_attr($alt),
        (int) $size,
        $fallback_urls ? ' data-dream-avatar-fallbacks="' . esc_attr(implode('|', $fallback_urls)) . '"' : '',
        $pending_token ? ' data-dream-avatar-token="' . esc_attr($pending_token) . '"' : ''
    );
}
add_filter('get_avatar', 'dream2_mxin_loli_avatar', 10, 6);

function dream2_mxin_comment_callback($comment, $args, $depth) {
    $is_private = dream2_mxin_is_private_comment($comment);
    ?>
    <li <?php comment_class('media dream-comment' . ($is_private ? ' dream-private-comment' : '')); ?> id="comment-<?php comment_ID(); ?>">
        <div class="media-left"><?php echo get_avatar($comment, 48, '', '', array('class' => 'avatar')); ?></div>
        <div class="media-content" id="comment-body-<?php comment_ID(); ?>">
            <div class="comment-meta">
                <strong><?php comment_author_link(); ?></strong>
                <time datetime="<?php comment_time('c'); ?>"><?php comment_date('Y-m-d H:i'); ?></time>
            </div>
            <div class="comment-content"><?php comment_text(); ?></div>
            <?php
            comment_reply_link(array_merge($args, array(
                'add_below' => 'comment-body',
                'depth'     => $depth,
                'max_depth' => max((int) $args['max_depth'], (int) $depth + 1),
                'reply_text'=> __('回复', 'dream2-mxin'),
            )));
            ?>
        </div>
    <?php
}

class Dream2_MXIN_Nav_Walker extends Walker_Nav_Menu {
    public function start_lvl(&$output, $depth = 0, $args = null) {
        $output .= '<ul class="item-dropdown-menu">';
    }

    public function end_lvl(&$output, $depth = 0, $args = null) {
        $output .= '</ul>';
    }

    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
        $has_children = in_array('menu-item-has-children', $item->classes, true);
        $classes = $depth === 0 && $has_children ? 'item-dropdown' : 'item-sub-li';
        $link_classes = array('item');
        if (array_intersect(array('current-menu-item', 'current-menu-parent', 'current-menu-ancestor'), $item->classes)) {
            $link_classes[] = 'current';
        }
        $output .= '<li class="' . esc_attr($classes) . '">';
        $output .= '<a class="' . esc_attr(implode(' ', $link_classes)) . '" href="' . esc_url($item->url) . '"';
        if ($item->target) {
            $output .= ' target="' . esc_attr($item->target) . '"';
        }
        $output .= '>' . esc_html($item->title) . '</a>';
    }

    public function end_el(&$output, $item, $depth = 0, $args = null) {
        $output .= '</li>';
    }
}
