<?php
/*
Template Name: Dream 留言板
*/

get_header();
while (have_posts()) :
    the_post();
    get_template_part('template-parts/content', 'single');
    if (comments_open() || get_comments_number()) comments_template();
endwhile;
get_footer();
