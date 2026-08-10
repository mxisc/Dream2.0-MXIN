<?php
/**
 * Dream2 sidebar widgets.
 *
 * @package Dream2_MXIN
 */

if (!defined('ABSPATH')) {
    exit;
}

function dream2_mxin_sidebar_stat_label($type) {
    $labels = array(
        'post'     => '文章',
        'category' => '分类',
        'tag'      => '标签',
        'comment'  => '评论',
        'visit'    => '访问',
    );
    return translate($labels[$type] ?? $labels['post'], 'dream2-mxin');
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
    );
    return dream2_mxin_translate_display_values($defaults[$type] ?? array('title' => '', 'icon' => '', 'class' => ''));
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

    if ($type === 'notice' && dream2_get('notice_show_mode', 'default') === 'close') {
        return;
    }
    if ($type === 'notice' && dream2_get('notice_show_mode') === 'index' && !is_home() && !is_front_page()) {
        return;
    }
    if ($type === 'love' && (!dream2_get('love_oneself_avatar') || !dream2_get('love_opposite_avatar'))) {
        return;
    }
    if ($type === 'music' && dream2_get('music_mode', 'none') === 'none') {
        return;
    }
    if ($type === 'ad_piece' && dream2_get('ad_mode', 'none') === 'none') {
        return;
    }

    dream2_mxin_sidebar_widget_section_open($type, $module, $widget_id);

    if ($type === 'profile') : ?>
        <div class="card-content">
            <nav class="level"><div class="level-item" style="flex-direction:column">
                <figure class="image"><?php echo get_avatar(get_option('admin_email'), 86, '', '', array('class' => 'avatar')); ?></figure>
                <p class="nickname"><?php echo esc_html(dream2_get('metadata_name', get_bloginfo('name')) ?: get_bloginfo('name')); ?></p>
                <p class="motto spark-input"><?php bloginfo('description'); ?></p>
                <?php if (dream2_get('profile_location')) : ?><p class="address"><i class="ri-map-pin-line"></i> <?php echo esc_html(dream2_get('profile_location')); ?></p><?php endif; ?>
            </div></nav>
            <?php $custom_stats = dream2_mxin_normalize_repeater(dream2_get('custom_stats', array()), 'custom_stats'); if ($custom_stats) : ?>
                <nav class="level dream-custom-stats">
                    <?php foreach ($custom_stats as $stat) : $stat_type = sanitize_key($stat['type'] ?? 'post'); $stat_value = dream2_mxin_sidebar_stat_value($stat_type); ?>
                        <div class="level-item"><div><p class="heading"><?php echo esc_html(dream2_mxin_sidebar_stat_label($stat_type)); ?></p><p class="value" title="<?php echo esc_attr((string) $stat_value); ?>"><?php echo esc_html(dream2_mxin_sidebar_stat_display($stat_value)); ?></p></div></div>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
            <?php if (dream2_get('profile_theme_button')) : $button = explode('|', dream2_get('profile_theme_button'), 2); ?>
                <div class="level"><a class="level-item button is-link is-rounded" href="<?php echo esc_url($button[1] ?? '#'); ?>" target="_blank" rel="nofollow noopener noreferrer"><?php echo esc_html($button[0]); ?></a></div>
            <?php endif; ?>
            <?php $socials = dream2_mxin_normalize_repeater(dream2_get('custom_options', array()), 'custom_options'); if ($socials) : ?><div class="level"><?php foreach ($socials as $social) : ?>
                <a class="level-item button is-transparent" href="<?php echo esc_url($social['url'] ?? '#'); ?>" title="<?php echo esc_attr($social['name'] ?? ''); ?>" target="_blank" rel="nofollow noopener noreferrer"><i class="<?php echo esc_attr($social['icon'] ?? 'ri-links-line'); ?>"></i></a>
            <?php endforeach; ?></div><?php endif; ?>
        </div>
    <?php elseif ($type === 'toc') : ?>
        <?php dream2_mxin_sidebar_widget_title_html($module, $defaults['title'], $defaults['icon']); ?>
        <div class="card-content toc-content"></div>
    <?php elseif ($type === 'notice') : ?>
        <?php dream2_mxin_sidebar_widget_title_html($module, $defaults['title'], $defaults['icon']); ?>
        <div class="card-content"><?php echo wp_kses_post(dream2_get('notice_content', '<p>' . esc_html__('欢迎来访本站，博主还没有发布任何公告！', 'dream2-mxin') . '</p>')); ?></div>
    <?php elseif ($type === 'love') : ?>
        <?php dream2_mxin_sidebar_widget_title_html($module, $defaults['title'], $defaults['icon']); ?>
        <div class="card-content dream-love-avatars">
            <a href="<?php echo esc_url(dream2_get('love_oneself_url', '#')); ?>"><img src="<?php echo esc_url(dream2_get('love_oneself_avatar')); ?>" alt="<?php esc_attr_e('自己的头像', 'dream2-mxin'); ?>"></a>
            <i class="ri-heart-fill"></i>
            <a href="<?php echo esc_url(dream2_get('love_opposite_url', '#')); ?>"><img src="<?php echo esc_url(dream2_get('love_opposite_avatar')); ?>" alt="<?php esc_attr_e('对方的头像', 'dream2-mxin'); ?>"></a>
        </div>
        <?php if (dream2_get('love_time')) : ?><p class="dream-love-time" data-time="<?php echo esc_attr(dream2_get('love_time')); ?>"><?php echo esc_html(dream2_get('love_time')); ?></p><?php endif; ?>
    <?php elseif ($type === 'music') : ?>
        <?php dream2_mxin_sidebar_widget_title_html($module, $defaults['title'], $defaults['icon']); ?>
        <div class="card-content">
            <?php if (dream2_get('music_mode') === 'playlist' && dream2_get('netease_playlist_id')) : ?>
                <meting-js list-folded="true" server="netease" type="playlist" id="<?php echo esc_attr(dream2_get('netease_playlist_id')); ?>"></meting-js>
            <?php elseif (dream2_get('music_mode') === 'config') : ?>
                <?php echo wp_kses(dream2_get('music_config'), array('meting-js' => array('server' => true, 'type' => true, 'id' => true, 'api' => true, 'auto' => true, 'name' => true, 'artist' => true, 'url' => true, 'cover' => true, 'lrc' => true, 'list-folded' => true, 'fixed' => true, 'mini' => true, 'autoplay' => true))); ?>
            <?php endif; ?>
        </div>
    <?php elseif ($type === 'recent_posts') : ?>
        <?php dream2_mxin_sidebar_widget_title_html($module, $defaults['title'], $defaults['icon']); ?>
        <div class="card-content"><ul class="list">
            <?php foreach (wp_get_recent_posts(array('numberposts' => (int) dream2_get('recent_posts_num', 5), 'post_status' => 'publish')) as $recent_post) : ?>
                <li class="item"><a class="link" href="<?php echo esc_url(get_permalink($recent_post['ID'])); ?>"><?php echo esc_html($recent_post['post_title']); ?></a><i class="ri-link"></i></li>
            <?php endforeach; ?>
        </ul></div>
    <?php elseif ($type === 'recent_comments') : ?>
        <?php dream2_mxin_sidebar_widget_title_html($module, $defaults['title'], $defaults['icon']); ?>
        <div class="card-content"><ul class="list">
            <?php foreach (get_comments(array('number' => (int) dream2_get('recent_comments_num', 5), 'status' => 'approve')) as $recent_comment) : ?>
                <li class="item dream-recent-comment"><?php echo get_avatar($recent_comment, 32); ?><a class="link" href="<?php echo esc_url(get_comment_link($recent_comment)); ?>"><?php echo esc_html(wp_trim_words(get_comment_excerpt($recent_comment), 14)); ?></a></li>
            <?php endforeach; ?>
        </ul></div>
    <?php elseif ($type === 'categories') : ?>
        <?php dream2_mxin_sidebar_widget_title_html($module, $defaults['title'], $defaults['icon']); ?>
        <div class="card-content"><ul class="menu-list">
            <?php foreach (get_categories(array('hide_empty' => true, 'number' => (int) dream2_get('categories_num', 10))) as $dream_category) : ?>
                <li><a class="level" href="<?php echo esc_url(get_category_link($dream_category)); ?>"><span class="level-item"><?php echo esc_html($dream_category->name); ?></span><span class="level-item tag"><?php echo esc_html((string) $dream_category->count); ?></span></a></li>
            <?php endforeach; ?>
        </ul></div>
    <?php elseif ($type === 'tags') : ?>
        <?php dream2_mxin_sidebar_widget_title_html($module, $defaults['title'], $defaults['icon']); ?>
        <div class="card-content"><?php wp_tag_cloud(array('smallest' => 12, 'largest' => 12, 'unit' => 'px', 'number' => (int) dream2_get('tags_num', 18), 'format' => 'flat')); ?></div>
    <?php elseif ($type === 'tagcloud') : ?>
        <?php dream2_mxin_sidebar_widget_title_html($module, $defaults['title'], $defaults['icon']); ?>
        <div class="card-content"><?php wp_tag_cloud(array('smallest' => 14, 'largest' => 30, 'unit' => 'px', 'number' => (int) dream2_get('tagcloud_num', 32), 'format' => 'flat')); ?></div>
    <?php elseif ($type === 'ad_piece') : ?>
        <?php if (dream2_enabled('show_ad_tag')) : ?><span class="dream-ad-tag"><?php esc_html_e('广告', 'dream2-mxin'); ?></span><?php endif; ?>
        <?php if (dream2_enabled('ad_tag_close')) : ?><button class="dream-ad-close" type="button" aria-label="<?php esc_attr_e('关闭广告', 'dream2-mxin'); ?>"><i class="ri-close-line"></i></button><?php endif; ?>
        <?php if (dream2_get('ad_mode') === 'image') : ?><a href="<?php echo esc_url(dream2_get('ad_target_url', '#')); ?>"><img src="<?php echo esc_url(dream2_get('ad_image')); ?>" alt="<?php esc_attr_e('广告', 'dream2-mxin'); ?>"></a><?php else : echo dream2_get('ad_custom_code'); endif; // phpcs:ignore ?>
    <?php elseif ($type === 'custom') : ?>
        <?php dream2_mxin_sidebar_widget_title_html($module, $defaults['title'], $defaults['icon']); ?>
        <?php echo wp_kses_post($module['content'] ?? ''); ?>
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
            'description'                 => __('Dream2 侧栏小工具', 'dream2-mxin'),
            'customize_selective_refresh' => true,
        ));
    }

    public function widget($args, $instance) {
        $module = array(
            'title'   => $instance['title'] ?? $this->default_title,
            'icon'    => $instance['icon'] ?? $this->default_icon,
            'hide'    => $instance['hide'] ?? 'is-not-hidden',
            'content' => $instance['content'] ?? '',
        );
        dream2_mxin_render_sidebar_widget_module($this->module_type, $module, $args['widget_id'] ?? '');
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = sanitize_text_field($new_instance['title'] ?? '');
        $instance['icon'] = sanitize_text_field($new_instance['icon'] ?? '');
        $instance['hide'] = array_key_exists(($new_instance['hide'] ?? ''), dream2_mxin_sidebar_hide_choices())
            ? (string) $new_instance['hide']
            : 'is-not-hidden';
        if ($this->module_type === 'custom') {
            $instance['content'] = current_user_can('unfiltered_html')
                ? (string) ($new_instance['content'] ?? '')
                : wp_kses_post($new_instance['content'] ?? '');
        }
        return $instance;
    }

    public function form($instance) {
        $title = $instance['title'] ?? $this->default_title;
        $icon = $instance['icon'] ?? $this->default_icon;
        $hide = $instance['hide'] ?? 'is-not-hidden';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('标题', 'dream2-mxin'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('icon')); ?>"><?php esc_html_e('图标 Class', 'dream2-mxin'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('icon')); ?>" name="<?php echo esc_attr($this->get_field_name('icon')); ?>" type="text" value="<?php echo esc_attr($icon); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('hide')); ?>"><?php esc_html_e('隐藏方式', 'dream2-mxin'); ?></label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('hide')); ?>" name="<?php echo esc_attr($this->get_field_name('hide')); ?>">
                <?php foreach (dream2_mxin_sidebar_hide_choices() as $value => $label) : ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($hide, $value); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php if ($this->module_type === 'custom') : ?>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('content')); ?>"><?php esc_html_e('内容', 'dream2-mxin'); ?></label>
                <textarea class="widefat" rows="8" id="<?php echo esc_attr($this->get_field_id('content')); ?>" name="<?php echo esc_attr($this->get_field_name('content')); ?>"><?php echo esc_textarea($instance['content'] ?? ''); ?></textarea>
            </p>
        <?php endif;
    }
}

class Dream2_MXIN_Profile_Widget extends Dream2_MXIN_Sidebar_Widget { public function __construct() { parent::__construct('dream2_profile', __('Dream2 信息模块', 'dream2-mxin'), 'profile', '', ''); } }
class Dream2_MXIN_Toc_Widget extends Dream2_MXIN_Sidebar_Widget { public function __construct() { parent::__construct('dream2_toc', __('Dream2 目录模块', 'dream2-mxin'), 'toc', __('目录', 'dream2-mxin'), 'ri-book-2-line'); } }
class Dream2_MXIN_Notice_Widget extends Dream2_MXIN_Sidebar_Widget { public function __construct() { parent::__construct('dream2_notice', __('Dream2 公告模块', 'dream2-mxin'), 'notice', __('公告', 'dream2-mxin'), 'ri-volume-up-line'); } }
class Dream2_MXIN_Love_Widget extends Dream2_MXIN_Sidebar_Widget { public function __construct() { parent::__construct('dream2_love', __('Dream2 恋爱墙模块', 'dream2-mxin'), 'love', __('恋爱墙', 'dream2-mxin'), 'ri-heart-3-line'); } }
class Dream2_MXIN_Music_Widget extends Dream2_MXIN_Sidebar_Widget { public function __construct() { parent::__construct('dream2_music', __('Dream2 音乐模块', 'dream2-mxin'), 'music', __('音乐', 'dream2-mxin'), 'ri-music-2-line'); } }
class Dream2_MXIN_Recent_Posts_Widget extends Dream2_MXIN_Sidebar_Widget { public function __construct() { parent::__construct('dream2_recent_posts', __('Dream2 最新文章模块', 'dream2-mxin'), 'recent_posts', __('最新文章', 'dream2-mxin'), 'ri-history-line'); } }
class Dream2_MXIN_Recent_Comments_Widget extends Dream2_MXIN_Sidebar_Widget { public function __construct() { parent::__construct('dream2_recent_comments', __('Dream2 最新评论模块', 'dream2-mxin'), 'recent_comments', __('最新评论', 'dream2-mxin'), 'ri-chat-3-line'); } }
class Dream2_MXIN_Categories_Widget extends Dream2_MXIN_Sidebar_Widget { public function __construct() { parent::__construct('dream2_categories', __('Dream2 文章分类模块', 'dream2-mxin'), 'categories', __('分类', 'dream2-mxin'), 'ri-apps-line'); } }
class Dream2_MXIN_Tags_Widget extends Dream2_MXIN_Sidebar_Widget { public function __construct() { parent::__construct('dream2_tags', __('Dream2 文章标签模块', 'dream2-mxin'), 'tags', __('标签', 'dream2-mxin'), 'ri-price-tag-3-line'); } }
class Dream2_MXIN_Tagcloud_Widget extends Dream2_MXIN_Sidebar_Widget { public function __construct() { parent::__construct('dream2_tagcloud', __('Dream2 标签云模块', 'dream2-mxin'), 'tagcloud', __('标签云', 'dream2-mxin'), 'ri-cloud-line'); } }
class Dream2_MXIN_Ad_Widget extends Dream2_MXIN_Sidebar_Widget { public function __construct() { parent::__construct('dream2_ad', __('Dream2 广告模块', 'dream2-mxin'), 'ad_piece', '', ''); } }
class Dream2_MXIN_Custom_Widget extends Dream2_MXIN_Sidebar_Widget { public function __construct() { parent::__construct('dream2_custom', __('Dream2 自定义模块', 'dream2-mxin'), 'custom', __('自定义模块', 'dream2-mxin'), 'ri-apps-2-line'); } }

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
    ) as $widget_class) {
        register_widget($widget_class);
    }
}
add_action('widgets_init', 'dream2_mxin_register_sidebar_widgets');
