<?php
/**
 * Post card.
 *
 * @package Dream2_MXIN
 */

$thumbnail = dream2_mxin_post_thumbnail_url();
$post_mode = get_post_meta(get_the_ID(), '_dream2_thumbnail_mode', true);
$mode = $post_mode ?: (is_sticky() ? dream2_get('top_thumbnail_mode', 'default') : dream2_get('thumbnail_mode', 'small'));
if ($mode === 'default' && is_sticky()) {
    $mode = dream2_get('thumbnail_mode', 'small');
}
if ($mode === 'small-alter') {
    global $wp_query;
    $mode = $wp_query->current_post % 2 ? 'small-right' : 'small';
}

if (is_sticky() && $mode === 'fold') :
    ?>
    <a id="post-<?php the_ID(); ?>" <?php post_class('card widget card-fold'); ?> href="<?php the_permalink(); ?>">
        <h2 class="title"><span class="top"><?php esc_html_e('置顶', 'dream2-mxin'); ?></span><span><?php the_title(); ?></span></h2>
        <p><?php echo esc_html(get_the_date('Y-m-d')); ?></p>
    </a>
<?php elseif ($mode === 'grid') : ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class('card widget dream-grid-card'); ?>>
        <?php if ($thumbnail) : ?>
            <a class="thumbnail" href="<?php the_permalink(); ?>"><div class="thumbnail-image" style="background-image:url('<?php echo esc_url($thumbnail); ?>')"></div></a>
        <?php endif; ?>
        <div class="card-content">
            <?php dream2_mxin_post_meta(true); ?>
            <h2 class="title"><?php if (is_sticky()) : ?><span class="top"><?php esc_html_e('置顶', 'dream2-mxin'); ?></span><?php endif; ?><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        </div>
    </article>
<?php elseif ($thumbnail && $mode === 'back') :
    ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class('card widget card-cover'); ?>>
        <a href="<?php the_permalink(); ?>">
            <div class="cover-image" style="background-image:url('<?php echo esc_url($thumbnail); ?>')"></div>
            <div class="details">
                <h2 class="title"><?php if (is_sticky()) : ?><span class="top"><?php esc_html_e('置顶', 'dream2-mxin'); ?></span><?php endif; ?><?php the_title(); ?></h2>
                <?php dream2_mxin_post_meta(true); ?>
            </div>
        </a>
        <div class="category"><?php the_category(' '); ?></div>
    </article>
<?php elseif ($thumbnail && in_array($mode, array('small', 'small-right'), true)) : ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class('card widget card-small ' . ($mode === 'small-right' ? 'dream-image-right' : '')); ?>>
        <?php if ($mode !== 'small-right') : ?>
            <a class="dream-thumbnail-link" href="<?php the_permalink(); ?>"><div class="small-image" style="background-image:url('<?php echo esc_url($thumbnail); ?>')"></div></a>
        <?php endif; ?>
        <div class="card-content main">
            <h2 class="title">
                <?php if (is_sticky()) : ?><span class="top"><?php esc_html_e('置顶', 'dream2-mxin'); ?></span><?php endif; ?>
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h2>
            <div class="main-content"><?php echo esc_html(dream2_mxin_post_card_excerpt()); ?></div>
            <hr>
            <div class="meta">
                <?php dream2_mxin_post_meta(true); ?>
                <?php dream2_mxin_category_links(true); ?>
            </div>
        </div>
        <?php if ($mode === 'small-right') : ?>
            <a class="dream-thumbnail-link" href="<?php the_permalink(); ?>"><div class="small-image" style="background-image:url('<?php echo esc_url($thumbnail); ?>')"></div></a>
        <?php endif; ?>
    </article>
<?php else : ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class('card widget'); ?>>
        <?php if ($thumbnail && $mode !== 'none') : ?>
            <a class="thumbnail" href="<?php the_permalink(); ?>">
                <div class="thumbnail-image" style="background-image:url('<?php echo esc_url($thumbnail); ?>')"></div>
            </a>
        <?php endif; ?>
        <div class="card-content main">
            <h2 class="title">
                <?php if (is_sticky()) : ?><span class="top"><?php esc_html_e('置顶', 'dream2-mxin'); ?></span><?php endif; ?>
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h2>
            <div class="meta"><?php dream2_mxin_post_meta(true); ?><?php dream2_mxin_category_links(true); ?></div>
            <hr>
            <div class="main-content"><?php echo esc_html(dream2_mxin_post_card_excerpt()); ?></div>
        </div>
    </article>
<?php endif; ?>
