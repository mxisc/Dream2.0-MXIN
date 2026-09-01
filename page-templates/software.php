<?php
/*
Template Name: Dream 软件页面
*/

get_header();
while (have_posts()) :
    the_post();

    $software_term = get_term_by('name', '实用软件', 'link_category');
    if (!$software_term || is_wp_error($software_term)) {
        $software_term = get_term_by('slug', 'software', 'link_category');
    }

    $software_links = array();
    if ($software_term && !is_wp_error($software_term)) {
        $software_links = get_bookmarks(array(
            'category' => $software_term->term_id,
            'orderby'  => 'name',
            'order'    => 'ASC',
        ));
    }

    $fallback_icon = dream2_get('links_default_avatar', dream2_mxin_asset('img/avatar.svg')) ?: dream2_mxin_asset('img/avatar.svg');
    $intro_content = get_the_content(null, false, get_the_ID());
    $intro_content = preg_replace('/\[\/?listol[^\]]*\]/i', '', $intro_content);
    $intro_content = trim(apply_filters('the_content', $intro_content));
    ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class('dream-software-page'); ?>>
        <div class="card">
            <div class="card-content main">
                <header class="dream-software-hero">
                    <span class="dream-software-kicker"><i class="ri-apps-line"></i><?php esc_html_e('软件工具箱', 'dream2-mxin'); ?></span>
                    <h1 class="title"><?php the_title(); ?></h1>
                    <?php if ($intro_content) : ?>
                        <div class="dream-software-intro"><?php echo wp_kses_post($intro_content); ?></div>
                    <?php endif; ?>
                    <div class="dream-software-meta">
                        <span><i class="ri-stack-line"></i><?php printf(esc_html__('%d 个软件', 'dream2-mxin'), count($software_links)); ?></span>
                        <span><i class="ri-folder-3-line"></i><?php echo esc_html($software_term && !is_wp_error($software_term) ? $software_term->name : __('未找到分组', 'dream2-mxin')); ?></span>
                    </div>
                </header>

                <?php if ($software_links) : ?>
                    <div class="dream-software-grid">
                        <?php foreach ($software_links as $software_link) :
                            $software_icon = $software_link->link_image ?: $fallback_icon;
                            $pending_icon_token = '';
                            $protected_icon = $software_link->link_image
                                ? dream2_mxin_avatar_privacy_url('software', $software_link->link_id . '|' . $software_link->link_image, $software_link->link_image, $pending_icon_token)
                                : '';
                            $display_icon = $protected_icon ?: (dream2_mxin_avatar_privacy_enabled() ? dream2_mxin_avatar_default_url() : $software_icon);
                            $software_desc = trim($software_link->link_description) ?: __('暂无介绍。', 'dream2-mxin');
                            ?>
                            <article class="dream-software-card">
                                <div class="dream-software-card-inner">
                                    <span class="dream-software-icon">
                                        <img class="not-gallery" src="<?php echo esc_url($display_icon); ?>"<?php if ($pending_icon_token) : ?> data-dream-avatar-token="<?php echo esc_attr($pending_icon_token); ?>"<?php endif; ?><?php if (dream2_mxin_avatar_privacy_enabled()) : ?> data-dream-avatar-fallback="<?php echo esc_url(dream2_mxin_avatar_default_url()); ?>"<?php endif; ?> alt="<?php echo esc_attr($software_link->link_name); ?>" loading="lazy" decoding="async">
                                    </span>
                                    <span class="dream-software-body">
                                        <a class="dream-software-title" href="<?php echo esc_url($software_link->link_url); ?>" target="<?php echo esc_attr($software_link->link_target ?: '_blank'); ?>" rel="noopener noreferrer"><?php echo esc_html($software_link->link_name); ?></a>
                                        <span class="dream-software-desc"><?php echo wp_kses_post($software_desc); ?></span>
                                    </span>
                                    <a class="dream-software-action" href="<?php echo esc_url($software_link->link_url); ?>" target="<?php echo esc_attr($software_link->link_target ?: '_blank'); ?>" rel="noopener noreferrer" aria-label="<?php echo esc_attr(sprintf(__('打开 %s', 'dream2-mxin'), $software_link->link_name)); ?>"><i class="ri-arrow-right-up-line"></i></a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="dream-software-empty">
                        <i class="ri-inbox-line"></i>
                        <p><?php esc_html_e('还没有可展示的软件。请先在友链分类“实用软件”中添加条目。', 'dream2-mxin'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </article>
    <?php
    if (comments_open() || get_comments_number()) {
        comments_template();
    }
endwhile;
get_footer();
