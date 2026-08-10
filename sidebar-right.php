<aside class="column column-side column-right <?php echo esc_attr(dream2_get('right_sidebar_sticky', 'top')); ?>-sticky">
    <?php if (is_active_sidebar('sidebar-right')) : ?>
        <?php dynamic_sidebar('sidebar-right'); ?>
    <?php endif; ?>
</aside>
