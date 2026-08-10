<?php
/**
 * Main index.
 *
 * @package Dream2_MXIN
 */

get_header();

if (!is_paged()) {
    dream2_mxin_render_home_modules();
}

$notice = dream2_get('index_inform', '');
if ($notice && !is_paged()) :
    ?>
    <div class="card tips brightness"><?php echo wp_kses_post($notice); ?></div>
<?php endif; ?>

<?php if (have_posts()) : ?>
    <div class="dream-post-list <?php echo dream2_get('thumbnail_mode') === 'grid' ? 'column-main-grid' : ''; ?>">
        <?php while (have_posts()) : the_post(); ?>
            <?php get_template_part('template-parts/content', get_post_type()); ?>
        <?php endwhile; ?>
    </div>
    <?php dream2_mxin_pagination(); ?>
<?php else : ?>
    <?php get_template_part('template-parts/content', 'none'); ?>
<?php endif; ?>

<?php get_footer(); ?>

