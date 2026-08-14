<?php
/**
 * Footer template.
 *
 * @package Dream2_MXIN
 */
?>
            </main>
            <?php $dream_layout = dream2_get('sidebar_column', 'only-right'); ?>
            <?php if (in_array($dream_layout, array('all', 'only-left', 'module-right'), true)) : ?>
                <?php get_sidebar('left'); ?>
            <?php endif; ?>
            <?php if (in_array($dream_layout, array('all', 'only-right', 'module-left'), true)) : ?>
                <?php get_sidebar('right'); ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="actions">
    <div id="toggle-mode" role="button" aria-label="<?php esc_attr_e('切换深色模式', 'dream2-mxin'); ?>"><i class="ri-contrast-fill"></i></div>
    <div id="back-to-top" role="button" aria-label="<?php esc_attr_e('返回顶部', 'dream2-mxin'); ?>"><i class="ri-arrow-up-line"></i></div>
</div>

<footer class="footer">
    <div class="container">
        <ul class="footer-container dream-footer-layout">
            <li class="dream-footer-info">
                <p class="dream-footer-line dream-footer-inline">
                    <span>&copy; <?php echo esc_html(wp_date('Y')); ?> <?php bloginfo('name'); ?></span>
                    <span class="dream-footer-dot">·</span>
                    <span>Powered by <a class="powered" href="https://wordpress.org/" target="_blank" rel="noopener">WordPress</a> &amp; <a class="powered" href="https://github.com/mxisc/Dream2.0-MXIN" target="_blank" rel="noopener">Dream</a></span>
                    <?php if (dream2_get('record_number')) : ?>
                        <span class="dream-footer-dot">·</span>
                        <a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener noreferrer nofollow"><?php echo esc_html(dream2_get('record_number')); ?></a>
                    <?php endif; ?>
                    <?php if (dream2_get('record_number_moe')) : ?>
                        <span class="dream-footer-dot">·</span>
                        <a href="https://icp.gov.moe/" target="_blank" rel="noopener noreferrer nofollow"><?php echo esc_html(dream2_get('record_number_moe')); ?></a>
                    <?php endif; ?>
                    <?php if (dream2_get('record_number_ps')) : ?>
                        <span class="dream-footer-dot">·</span>
                        <span class="dream-footer-police"><img src="<?php echo esc_url(dream2_mxin_asset('img/ga.png')); ?>" alt="" class="dream-footer-police-icon"><?php echo esc_html(dream2_get('record_number_ps')); ?></span>
                    <?php endif; ?>
                    <?php if (dream2_get('website_time')) : ?>
                        <span class="dream-footer-dot">·</span>
                        <span id="websiteDate" data-start="<?php echo esc_attr(dream2_get('website_time')); ?>">建站时间</span>
                    <?php endif; ?>
                    <?php if (dream2_enabled('enable_busuanzi')) : ?>
                        <span class="dream-footer-dot">·</span>
                        <span id="busuanzi_container_site_pv">访问 <span id="busuanzi_value_site_pv"></span> 次</span>
                        <span id="busuanzi_container_site_uv">· <span id="busuanzi_value_site_uv"></span> 位访客</span>
                    <?php endif; ?>
                </p>
            </li>
            <li class="dream-footer-widget">
                <?php if (is_active_sidebar('footer')) : ?>
                    <?php dynamic_sidebar('footer'); ?>
                <?php endif; ?>
            </li>
        </ul>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
