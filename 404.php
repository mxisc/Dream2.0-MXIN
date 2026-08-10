<?php
/**
 * Not found.
 *
 * @package Dream2_MXIN
 */

get_header();
?>
<div class="card card-empty">
    <i class="ri-error-warning-line"></i>
    <h1>404</h1>
    <p><?php esc_html_e('这张地图没有这个关卡。', 'dream2-mxin'); ?></p>
    <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('返回首页', 'dream2-mxin'); ?></a>
</div>
<?php get_footer(); ?>

