<?php
/**
 * Single post.
 *
 * @package Dream2_MXIN
 */

get_header();
while (have_posts()) :
    the_post();
    dream2_mxin_set_post_views(get_the_ID());
    get_template_part('template-parts/content', 'single');
    $previous = get_next_post();
    $next = get_previous_post();
    if ($previous || $next) :
        ?>
        <div class="card"><div class="level post-navigation card-content">
            <?php if ($previous) : ?><a class="level-item" href="<?php echo esc_url(get_permalink($previous)); ?>"><i class="ri-arrow-left-s-line"></i><span><?php echo esc_html(get_the_title($previous)); ?></span></a><?php endif; ?>
            <?php if ($next) : ?><a class="level-item" href="<?php echo esc_url(get_permalink($next)); ?>"><span><?php echo esc_html(get_the_title($next)); ?></span><i class="ri-arrow-right-s-line"></i></a><?php endif; ?>
        </div></div>
        <?php
    endif;
    if (comments_open() || get_comments_number()) {
        comments_template();
    }
endwhile;
get_footer();
