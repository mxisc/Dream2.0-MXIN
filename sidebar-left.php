<aside class="column column-side column-left <?php echo esc_attr(dream2_get('left_sidebar_sticky', 'top')); ?>-sticky">
    <?php if (is_active_sidebar('sidebar-left')) : ?>
        <?php dynamic_sidebar('sidebar-left'); ?>
    <?php endif; ?>
</aside>
