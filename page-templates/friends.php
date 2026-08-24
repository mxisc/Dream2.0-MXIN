<?php
/*
Template Name: Dream 朋友圈
*/

get_header();
$friends = get_bookmarks(array('orderby' => 'updated', 'order' => 'DESC'));
$categories = get_terms(array('taxonomy' => 'link_category', 'hide_empty' => true));
$category_count = is_wp_error($categories) ? 0 : count($categories);
if (dream2_enabled('enable_friends_stats')) :
?>
<div class="card card-content friends">
    <div class="card-tab"><div><?php echo esc_html(get_the_title() ?: __('朋友圈', 'dream2-mxin')); ?></div></div>
    <nav class="level">
        <div class="level-item"><div><p class="heading"><?php esc_html_e('订阅数', 'dream2-mxin'); ?></p><p class="value"><?php echo esc_html((string) count($friends)); ?></p></div></div>
        <div class="level-item"><div><p class="heading"><?php esc_html_e('订阅成功数', 'dream2-mxin'); ?></p><p class="value"><?php echo esc_html((string) count(array_filter($friends, function ($friend) { return !empty($friend->link_url); }))); ?></p></div></div>
        <div class="level-item"><div><p class="heading"><?php esc_html_e('订阅分组数', 'dream2-mxin'); ?></p><p class="value"><?php echo esc_html((string) $category_count); ?></p></div></div>
    </nav>
</div>
<?php endif; ?>
<?php if ($friends) : foreach ($friends as $friend) : ?>
    <article class="widget card friends">
        <div class="card-content main">
            <h2 class="title"><a href="<?php echo esc_url($friend->link_url); ?>" target="_blank" rel="noopener"><?php echo esc_html($friend->link_name); ?></a></h2>
            <div class="main-content not-toc"><?php echo esc_html($friend->link_description ?: __('这个朋友还没有填写简介。', 'dream2-mxin')); ?></div>
            <hr>
            <div class="meta">
                <a class="has-link-grey" href="<?php echo esc_url($friend->link_url); ?>" target="_blank" rel="noopener">
                    <?php $fallback_avatar = dream2_mxin_default_avatar_url(); ?>
                    <?php $pending_avatar_token = ''; ?>
                    <?php $protected_avatar = $friend->link_image ? dream2_mxin_avatar_privacy_url('friend', $friend->link_id . '|' . $friend->link_image, $friend->link_image, $pending_avatar_token) : ''; ?>
                    <img src="<?php echo esc_url($protected_avatar ?: $fallback_avatar); ?>"<?php if ($pending_avatar_token) : ?> data-dream-avatar-token="<?php echo esc_attr($pending_avatar_token); ?>"<?php elseif (!$protected_avatar && $friend->link_image) : ?> data-dream-avatar="<?php echo esc_url($friend->link_image); ?>"<?php endif; ?> alt="">
                    <span><?php echo esc_html($friend->link_name); ?></span>
                </a>
                <em><?php esc_html_e('来自友情链接', 'dream2-mxin'); ?></em>
            </div>
        </div>
    </article>
<?php endforeach; else : ?>
    <div class="card card-content"><div class="empty"><?php esc_html_e('还没有可展示的朋友圈订阅。', 'dream2-mxin'); ?></div></div>
<?php endif; ?>
<?php get_footer(); ?>
