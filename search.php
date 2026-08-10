<?php
/**
 * Search results.
 *
 * @package Dream2_MXIN
 */

get_header();
?>
<div class="card card-content"><div class="card-tab"><div><?php printf(esc_html__('“%s”的搜索结果', 'dream2-mxin'), esc_html(get_search_query())); ?></div></div></div>
<?php if (have_posts()) : ?>
    <?php while (have_posts()) : the_post(); ?>
        <?php get_template_part('template-parts/content', get_post_type()); ?>
    <?php endwhile; ?>
    <?php dream2_mxin_pagination(); ?>
<?php else : ?>
    <?php get_template_part('template-parts/content', 'none'); ?>
<?php endif; ?>
<?php get_footer(); ?>

