<?php
get_header();
$years = array();
$paged = max(1, get_query_var('paged'), get_query_var('page'));
$archive_query = new WP_Query(array(
    'post_type'           => 'post',
    'post_status'         => 'publish',
    'posts_per_page'      => 10,
    'paged'               => $paged,
    'ignore_sticky_posts' => true,
));
$posts = $archive_query->posts;
foreach ($posts as $archive_post) {
    $year = get_the_date('Y', $archive_post);
    $years[$year][] = $archive_post;
}
foreach ($years as $year => $year_posts) :
    ?>
    <div class="card card-content">
        <div class="timeline-title"><?php echo esc_html($year); ?></div>
        <div class="timeline">
            <?php foreach ($year_posts as $archive_post) : ?>
                <article class="media">
                    <?php if (has_post_thumbnail($archive_post)) : ?>
                        <a href="<?php echo esc_url(get_permalink($archive_post)); ?>" class="media-left"><?php echo get_the_post_thumbnail($archive_post, 'thumbnail', array('class' => 'not-gallery')); ?></a>
                    <?php endif; ?>
                    <div class="media-content">
                        <time><?php echo esc_html(get_the_date('Y-m-d', $archive_post)); ?></time>
                        <a href="<?php echo esc_url(get_permalink($archive_post)); ?>" class="title has-link-grey"><?php echo esc_html(get_the_title($archive_post)); ?></a>
                        <?php $archive_categories = get_the_category($archive_post->ID); ?>
                        <?php if ($archive_categories) : ?>
                            <p>
                                <?php foreach ($archive_categories as $archive_category) : ?>
                                    <a class="has-link-grey" href="<?php echo esc_url(get_category_link($archive_category)); ?>"><?php echo esc_html($archive_category->name); ?></a>&nbsp;
                                <?php endforeach; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>
<?php
$pagination = paginate_links(array(
    'total'     => $archive_query->max_num_pages,
    'current'   => $paged,
    'type'      => 'array',
    'prev_text' => __('上一页', 'dream2-mxin'),
    'next_text' => __('下一页', 'dream2-mxin'),
));
dream2_mxin_render_pagination_links($pagination);
wp_reset_postdata();
get_footer();
