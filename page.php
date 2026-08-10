<?php
/**
 * Page template.
 *
 * @package Dream2_MXIN
 */

get_header();
while (have_posts()) :
    the_post();
    get_template_part('template-parts/content', 'single');
    if (comments_open() || get_comments_number()) {
        comments_template();
    }
endwhile;
get_footer();

