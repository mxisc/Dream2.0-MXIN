<?php
/*
Template Name: Dream 友情链接
*/

get_header();
$link_groups = array();
foreach (dream2_mxin_friend_link_group_terms() as $group) {
    $term = $group['term'];
    $group_links = get_bookmarks(array(
        'category' => $term->term_id,
        'orderby'  => 'name',
        'order'    => 'ASC',
    ));
    if ($group_links) {
        $link_groups[$group['label']] = $group_links;
    }
}
$bookmarks = array_merge(...array_values($link_groups ?: array(array())));
$link_category_count = count($link_groups);
$fallback_avatar = dream2_mxin_default_avatar_url();
?>
<div class="card dream-links-page">
    <?php if (dream2_get('links_thumbnail')) : ?><div class="card-image cover-image" style="background-image:url('<?php echo esc_url(dream2_get('links_thumbnail')); ?>')"></div><?php endif; ?>
    <div class="card-content main">
        <h1 class="title"><?php printf(esc_html__('友情链接 - %s的小伙伴们', 'dream2-mxin'), esc_html(get_the_author_meta('display_name', (int) get_post_field('post_author', get_the_ID())))); ?></h1>
        <div class="main-content">
            <?php if ($bookmarks) : ?>
                <?php foreach ($link_groups as $group_name => $group_links) : ?>
                    <div class="links">
                        <h3 class="link-title"><?php echo esc_html($group_name); ?></h3>
                        <ul class="link-items">
                            <?php foreach ($group_links as $bookmark) : ?>
                                <li>
                                    <a class="links-item" href="<?php echo esc_url($bookmark->link_url); ?>" target="<?php echo esc_attr($bookmark->link_target ?: '_blank'); ?>" rel="noopener noreferrer" title="<?php echo esc_attr(wp_strip_all_tags($bookmark->link_description)); ?>">
                                        <?php $pending_avatar_token = ''; ?>
                                        <?php $protected_avatar = $bookmark->link_image ? dream2_mxin_avatar_privacy_url('friend', $bookmark->link_id . '|' . $bookmark->link_image, $bookmark->link_image, $pending_avatar_token) : ''; ?>
                                        <img class="not-gallery" src="<?php echo esc_url($protected_avatar ?: $fallback_avatar); ?>"<?php if ($pending_avatar_token) : ?> data-dream-avatar-token="<?php echo esc_attr($pending_avatar_token); ?>"<?php elseif (!$protected_avatar && $bookmark->link_image) : ?> data-dream-avatar="<?php echo esc_url($bookmark->link_image); ?>"<?php endif; ?> alt="<?php echo esc_attr($bookmark->link_name); ?>">
                                        <span class="link-name"><?php echo esc_html($bookmark->link_name); ?></span>
                                        <div class="link-desc"><?php echo esc_html($bookmark->link_description ?: __('他还没有自我介绍呢~', 'dream2-mxin')); ?></div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <?php the_content(); ?>
            <?php endif; ?>

            <?php if (dream2_enabled('show_exchange_info', true) || dream2_get('links_info')) : ?><hr><?php endif; ?>
            <?php if (dream2_enabled('show_exchange_info', true)) :
                $blogger_name = dream2_get('links_blogger_name', get_bloginfo('name')) ?: get_bloginfo('name');
                $blogger_url = dream2_get('links_blogger_url', home_url('/')) ?: home_url('/');
                $blogger_avatar = dream2_get('links_blogger_avatar', get_site_icon_url()) ?: $fallback_avatar;
                $blogger_description = dream2_get('links_blogger_description', get_bloginfo('description'));
                ?>
                <?php esc_html_e('本站信息：', 'dream2-mxin'); ?>
                <ul>
                    <li><?php printf(esc_html__('名称：%s', 'dream2-mxin'), esc_html($blogger_name)); ?></li>
                    <li><?php esc_html_e('地址：', 'dream2-mxin'); ?><a href="<?php echo esc_url($blogger_url); ?>"><?php echo esc_html($blogger_url); ?></a></li>
                    <li><?php esc_html_e('图标：', 'dream2-mxin'); ?><a href="<?php echo esc_url($blogger_avatar); ?>"><?php echo esc_html($blogger_avatar); ?></a></li>
                    <li><?php printf(esc_html__('描述：%s', 'dream2-mxin'), esc_html($blogger_description)); ?></li>
                </ul>
            <?php endif; ?>
            <?php if (dream2_get('links_info')) : ?><?php echo wp_kses_post(dream2_get('links_info')); ?><?php endif; ?>

            <?php dream2_mxin_render_link_application_form(get_the_ID()); ?>
        </div>
    </div>
</div>
<?php
if (comments_open() || get_comments_number()) {
    comments_template();
}
get_footer();
