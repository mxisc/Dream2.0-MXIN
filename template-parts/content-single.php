<?php
/**
 * Single content.
 *
 * @package Dream2_MXIN
 */

$thumbnail = dream2_mxin_post_thumbnail_url();
$tips = get_post_meta(get_the_ID(), '_dream2_tips', true);
$invalid_days = absint(dream2_get('invalid_tips_day', 0));
$updated_days = (int) floor((current_time('timestamp') - get_post_modified_time('U')) / DAY_IN_SECONDS);
?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <?php if ($thumbnail) : ?>
        <div class="card widget">
            <div class="cover-image dream-single-cover" style="background-image:url('<?php echo esc_url($thumbnail); ?>')">
                <?php if (get_post_type() === 'post') : ?><div class="category"><?php the_category(' '); ?></div><?php endif; ?>
                <div class="details">
                    <h1 class="title"><?php the_title(); ?></h1>
                    <?php dream2_mxin_post_meta(); ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($tips) : ?><div class="card tips brightness"><i class="ri-close-line click-close"></i><?php echo esc_html($tips); ?></div><?php endif; ?>
    <?php if ($invalid_days && $updated_days >= $invalid_days) : ?>
        <div class="card tips brightness"><i class="ri-time-line"></i> <?php esc_html_e('这篇文章较长时间未更新，部分内容可能已经失效，请结合当前版本验证。', 'dream2-mxin'); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-content main">
            <?php if (!$thumbnail) : ?>
                <h1 class="title"><?php the_title(); ?></h1>
                <div class="meta"><?php dream2_mxin_post_meta(); ?><?php if (get_post_type() === 'post') dream2_mxin_category_links(); ?></div>
                <hr>
            <?php endif; ?>
            <div class="main-content article">
                <?php the_content(); ?>
                <?php wp_link_pages(); ?>
            </div>

            <?php if (get_post_type() === 'post' && has_tag()) : ?>
                <div class="article-operation"><div class="level-item"><?php the_tags('', ' ', ''); ?></div></div>
            <?php endif; ?>

            <?php if (in_array(get_post_type(), array('post', 'page'), true) && dream2_enabled('enable_copyright', true)) : ?>
                <hr>
                <div class="copyright">
                    <div class="copyright-title">
                        <p><?php the_title(); ?></p>
                        <a href="<?php echo esc_url(get_permalink()); ?>"><?php echo esc_html(get_permalink()); ?></a>
                    </div>
                    <div class="copyright-meta level">
                        <div class="level-item">
                            <h6>作者</h6>
                            <p><?php echo esc_html(get_the_author()); ?></p>
                        </div>
                        <div class="level-item">
                            <h6>发布于</h6>
                            <p><?php echo esc_html(get_the_date('Y-m-d')); ?></p>
                        </div>
                        <div class="level-item">
                            <h6>更新于</h6>
                            <p><?php echo esc_html(get_the_modified_date('Y-m-d')); ?></p>
                        </div>
                        <div class="level-item">
                            <h6>许可协议</h6>
                            <a rel="noopener" target="_blank" title="CC BY 4.0" href="https://creativecommons.org/licenses/by/4.0/deed.zh"><i class="icon ri-creative-commons-line"></i>CC BY 4.0</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (dream2_enabled('enable_post_share')) : ?>
                <?php $share_url = rawurlencode(get_permalink()); $share_title = rawurlencode(get_the_title()); ?>
                <div class="dshare dshare-container" data-title="<?php echo esc_attr(get_the_title()); ?>" data-url="<?php echo esc_url(get_permalink()); ?>" data-image="<?php echo esc_url($thumbnail); ?>">
                    <a class="dshare-icon icon-qq" target="_blank" rel="noopener" href="<?php echo esc_url('https://connect.qq.com/widget/shareqq/index.html?url=' . $share_url . '&title=' . $share_title); ?>" title="QQ"></a>
                    <a class="dshare-icon icon-qzone" target="_blank" rel="noopener" href="<?php echo esc_url('https://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?url=' . $share_url . '&title=' . $share_title); ?>" title="QQ 空间"></a>
                    <a class="dshare-icon icon-weibo" target="_blank" rel="noopener" href="<?php echo esc_url('https://service.weibo.com/share/share.php?url=' . $share_url . '&title=' . $share_title); ?>" title="微博"></a>
                    <button class="dshare-icon dream-share-icon dream-copy-link" type="button" data-url="<?php echo esc_url(get_permalink()); ?>" title="复制链接"><i class="ri-link"></i></button>
                    <button class="dshare-icon dream-share-icon dream-native-share" type="button" data-url="<?php echo esc_url(get_permalink()); ?>" data-title="<?php echo esc_attr(get_the_title()); ?>" title="系统分享"><i class="ri-share-forward-line"></i></button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</article>
