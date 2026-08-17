<?php
/**
 * Header template.
 *
 * @package Dream2_MXIN
 */
?><!doctype html>
<html <?php language_attributes(); ?> class="<?php echo esc_attr(dream2_get('theme_style', 'default')); ?>">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php $dream_layout = dream2_get('sidebar_column', 'only-right'); ?>
<header class="navbar">
    <div class="navbar-above">
        <div class="<?php echo $dream_layout === 'all' ? 'container' : 'container two-column'; ?>">
            <button class="navbar-slideicon" type="button" aria-label="<?php esc_attr_e('打开导航', 'dream2-mxin'); ?>">
                <i class="ri-list-unordered" aria-hidden="true"></i>
            </button>
            <a class="navbar-item logo-title" href="<?php echo esc_url(home_url('/')); ?>">
                <?php dream2_mxin_logo(); ?>
            </a>
            <nav class="navbar-nav active-animate" aria-label="<?php esc_attr_e('主导航', 'dream2-mxin'); ?>">
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'container'      => false,
                    'fallback_cb'    => 'dream2_mxin_menu_fallback',
                    'items_wrap'     => '%3$s',
                    'walker'         => new Dream2_MXIN_Nav_Walker(),
                    'depth'          => 3,
                ));
                ?>
            </nav>
            <div class="navbar-search">
                <button class="submit dream-search-toggle" type="button" aria-label="<?php esc_attr_e('搜索按钮', 'dream2-mxin'); ?>">
                    <i class="ri-search-line"></i>
                </button>
            </div>
            <i class="ri-search-line navbar-searchicon dream-search-toggle" role="button" aria-label="<?php esc_attr_e('搜索', 'dream2-mxin'); ?>"></i>
        </div>
    </div>

    <div class="navbar-slideout">
        <div class="navbar-slideout-wrap">
            <div class="navbar-slideout-author">
                <?php echo get_avatar(dream2_mxin_profile_avatar_target(), 50, '', '', array('class' => 'avatar')); ?>
                <div class="info">
                    <p class="link"><?php echo esc_html(dream2_mxin_profile_display_name()); ?></p>
                    <p class="motto"><?php bloginfo('description'); ?></p>
                </div>
            </div>
            <?php
            $drawer_stats = dream2_mxin_normalize_repeater(
                dream2_mxin_widget_module_value(
                    dream2_mxin_widget_first_active_instance('dream2_profile'),
                    'custom_stats',
                    dream2_get('custom_stats', '')
                ),
                'custom_stats'
            );
            if ($drawer_stats) :
                $stat_values = array(
                    'post'     => (int) wp_count_posts('post')->publish,
                    'comment'  => (int) wp_count_comments()->approved,
                    'category' => (int) wp_count_terms(array('taxonomy' => 'category', 'hide_empty' => true)),
                    'tag'      => (int) wp_count_terms(array('taxonomy' => 'post_tag', 'hide_empty' => true)),
                    'visit'    => dream2_mxin_get_total_post_views(),
                );
                ?>
                <ul class="navbar-slideout-menu dream-drawer-stats">
                    <?php foreach ($drawer_stats as $stat) :
                        $type = sanitize_key($stat['type'] ?? 'post');
                        $label = $stat['title'] ?? array('post'=>'文章','comment'=>'评论','category'=>'分类','tag'=>'标签','visit'=>'访问')[$type] ?? $type;
                        $value = $stat_values[$type] ?? ($stat['value'] ?? $stat['tag'] ?? '');
                        ?>
                        <li class="item"><div><i class="<?php echo esc_attr($stat['icon'] ?? 'ri-bar-chart-line'); ?>"></i><span><?php echo esc_html($label); ?> <strong><?php echo esc_html((string) $value); ?></strong></span></div></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <ul class="navbar-slideout-menu not-toc">
                <li>
                    <button class="link panel" type="button" data-no-pjax>
                        <span><?php esc_html_e('导航', 'dream2-mxin'); ?></span>
                        <i class="ri-arrow-right-s-line"></i>
                    </button>
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'primary',
                        'container'      => false,
                        'fallback_cb'    => 'dream2_mxin_menu_fallback',
                        'items_wrap'     => '<ul class="slides panel-body panel-side-menu">%3$s</ul>',
                        'depth'          => 3,
                    ));
                    ?>
                </li>
            </ul>
            <?php if (is_singular() && dream2_enabled('drawer_toc', true)) : ?>
                <ul class="navbar-slideout-menu dream-drawer-toc"><li><span class="link">文章目录</span><ol></ol></li></ul>
            <?php endif; ?>
        </div>
    </div>
    <div class="navbar-mask"></div>
</header>

<div class="dream-search-overlay" hidden>
    <div class="card dream-search-card">
        <?php get_search_form(); ?>
        <div class="dream-search-results" hidden></div>
        <div class="dream-search-empty"><?php esc_html_e('没有搜索结果', 'dream2-mxin'); ?></div>
        <div class="dream-search-footer">
            <span><kbd>↑</kbd><kbd>↓</kbd> <?php esc_html_e('选择', 'dream2-mxin'); ?></span>
            <span><kbd class="dream-search-enter-key"><span class="dream-search-enter-symbol" aria-hidden="true"></span></kbd> <?php esc_html_e('确认', 'dream2-mxin'); ?></span>
            <button class="dream-search-close" type="button"><kbd>ESC</kbd> <?php esc_html_e('关闭', 'dream2-mxin'); ?></button>
        </div>
    </div>
</div>

<?php if (dream2_enabled('enable_banner', true)) : ?>
    <div class="banner"<?php $banner = dream2_get('banner_image', ''); echo $banner ? ' style="background-image:url(' . esc_url($banner) . ')"' : ''; ?>>
        <div class="banner-info">
            <div class="banner-info-title"><?php bloginfo('name'); ?></div>
            <div class="banner-info-desc"><?php echo esc_html(dream2_get('banner_description', get_bloginfo('description'))); ?></div>
        </div>
        <svg class="banner-waves" xmlns="http://www.w3.org/2000/svg" viewBox="0 24 150 28" preserveAspectRatio="none">
            <defs><path id="gentle-wave" d="M-160 44c30 0 58-18 88-18s58 18 88 18 58-18 88-18 58 18 88 18v44h-352z"/></defs>
            <g class="parallax"><use href="#gentle-wave" x="48" y="0"/><use href="#gentle-wave" x="48" y="3"/><use href="#gentle-wave" x="48" y="5"/><use href="#gentle-wave" x="48" y="7"/></g>
        </svg>
    </div>
<?php endif; ?>

<section class="section">
    <div class="<?php echo $dream_layout === 'all' ? 'container' : 'container two-column'; ?>">
        <div class="columns">
            <main id="primary" class="column column-main">
