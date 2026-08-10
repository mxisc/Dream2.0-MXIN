<?php
/**
 * Archive template.
 *
 * @package Dream2_MXIN
 */

get_header();

$archive_slug       = function_exists('dream2_mxin_builtin_route_slug') ? dream2_mxin_builtin_route_slug('archivers_route_slug', 'archivers') : 'archivers';
$archive_root_label = __('文章', 'dream2-mxin');
$archive_root_url   = home_url('/' . $archive_slug . '/');
$archive_root_icon  = 'ri-archive-line';
$archive_label      = wp_strip_all_tags(get_the_archive_title());

if (is_category()) {
    $archive_root_label = __('分类', 'dream2-mxin');
    $archive_root_url   = '';
    $archive_root_icon  = 'ri-apps-line';
    $archive_label      = single_cat_title('', false);
} elseif (is_tag()) {
    $archive_root_label = __('标签', 'dream2-mxin');
    $archive_root_url   = '';
    $archive_root_icon  = 'ri-price-tag-3-line';
    $archive_label      = single_tag_title('', false);
}
?>
<div class="card card-content main-title">
    <ul class="breadcrumb">
        <?php if ($archive_root_url) : ?>
        <li><a href="<?php echo esc_url($archive_root_url); ?>"><i class="<?php echo esc_attr($archive_root_icon); ?>"></i><?php echo esc_html($archive_root_label); ?></a></li>
        <?php endif; ?>
        <li><?php echo esc_html($archive_label); ?></li>
    </ul>
    <?php the_archive_description('<div class="archive-description">', '</div>'); ?>
</div>
<?php if (have_posts()) : ?>
    <?php while (have_posts()) : the_post(); ?>
        <?php get_template_part('template-parts/content', get_post_type()); ?>
    <?php endwhile; ?>
    <?php dream2_mxin_pagination(); ?>
<?php else : ?>
    <?php get_template_part('template-parts/content', 'none'); ?>
<?php endif; ?>
<?php get_footer(); ?>
