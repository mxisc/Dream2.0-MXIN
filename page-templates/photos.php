<?php
/*
Template Name: Dream 图库
*/

get_header();
$paged = max(1, get_query_var('paged'), get_query_var('page'));
$photos = new WP_Query(array(
    'post_type'      => 'attachment',
    'post_status'    => 'inherit',
    'post_mime_type' => 'image',
    'posts_per_page' => 24,
    'paged'          => $paged,
));
?>
<div class="card card-content photos">
    <div class="card-tab"><div><?php echo esc_html(get_the_title() ?: __('图库', 'dream2-mxin')); ?></div></div>
    <div class="photos-teams"><a class="item active" href="<?php the_permalink(); ?>"><?php esc_html_e('全部', 'dream2-mxin'); ?></a></div>
</div>
<?php if ($photos->have_posts()) : ?>
    <div class="photos-gallery load-block loading">
        <?php while ($photos->have_posts()) : $photos->the_post();
            $full = wp_get_attachment_image_url(get_the_ID(), 'full');
            $caption = wp_get_attachment_caption(get_the_ID()) ?: get_the_title();
            ?>
            <div href="<?php echo esc_url($full); ?>" data-fancybox="gallery" data-caption="<?php echo esc_attr($caption); ?>">
                <?php echo wp_get_attachment_image(get_the_ID(), 'large', false, array('alt' => get_the_title())); ?>
                <div class="info">
                    <div><i class="ri-camera-line"></i><p><?php the_title(); ?></p></div>
                    <div><i class="ri-time-line"></i><p><?php echo esc_html(get_the_date('Y-m-d')); ?></p></div>
                    <?php if ($caption) : ?><p><?php echo esc_html($caption); ?></p><?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
    <?php
    $links = paginate_links(array('total' => $photos->max_num_pages, 'current' => $paged, 'type' => 'array'));
    if ($links) {
        echo '<div class="card card-transparent"><nav class="pagination"><ul class="pagination-list">';
        foreach ($links as $link) echo '<li>' . wp_kses_post(str_replace('page-numbers', 'pagination-link', $link)) . '</li>';
        echo '</ul></nav></div>';
    }
    ?>
<?php else : ?>
    <div class="card card-content"><div class="empty"><?php esc_html_e('媒体库还没有图片。', 'dream2-mxin'); ?></div></div>
<?php endif; wp_reset_postdata(); ?>
<?php get_footer(); ?>
