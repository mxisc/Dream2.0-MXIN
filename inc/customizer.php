<?php
/**
 * Theme Customizer options.
 *
 * @package Dream2_MXIN
 */

if (!defined('ABSPATH')) {
    exit;
}

function dream2_mxin_customize_register($wp_customize) {
    $wp_customize->add_panel('dream2_options', array(
        'title'       => __('Dream 2.0 设置', 'dream2-mxin'),
        'priority'    => 30,
        'description' => __('Dream 2.0 WordPress 移植版的外观和内容设置。', 'dream2-mxin'),
    ));

    $sections = array(
        'dream2_basic'  => __('基础信息', 'dream2-mxin'),
        'dream2_style'  => __('基础样式', 'dream2-mxin'),
        'dream2_posts'  => __('文章设置', 'dream2-mxin'),
        'dream2_footer' => __('页脚设置', 'dream2-mxin'),
        'dream2_custom' => __('自定义代码', 'dream2-mxin'),
    );
    foreach ($sections as $id => $title) {
        $wp_customize->add_section($id, array(
            'title' => $title,
            'panel' => 'dream2_options',
        ));
    }

    $text_controls = array(
        'dream2_banner_description' => array('dream2_basic', __('横幅描述', 'dream2-mxin'), '梦之城，童话梦境'),
        'dream2_index_notice'        => array('dream2_basic', __('首页通知', 'dream2-mxin'), ''),
        'dream2_hidden_title'        => array('dream2_basic', __('离开页面标题', 'dream2-mxin'), '你别走呀 (´；ω；`)'),
        'dream2_visible_title'       => array('dream2_basic', __('返回页面标题', 'dream2-mxin'), '欢迎回来 (｡･ω･｡)'),
        'dream2_default_thumbnail'   => array('dream2_posts', __('默认文章封面 URL', 'dream2-mxin'), ''),
        'dream2_record_number'       => array('dream2_footer', __('ICP备案号', 'dream2-mxin'), ''),
        'dream2_moe_record_number'   => array('dream2_footer', __('萌备号', 'dream2-mxin'), ''),
        'dream2_public_record'       => array('dream2_footer', __('公安备案号', 'dream2-mxin'), ''),
        'dream2_website_time'        => array('dream2_footer', __('建站时间', 'dream2-mxin'), ''),
    );
    foreach ($text_controls as $id => $data) {
        $wp_customize->add_setting($id, array(
            'default'           => $data[2],
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control($id, array(
            'section' => $data[0],
            'label'   => $data[1],
            'type'    => 'text',
        ));
    }

    $image_controls = array(
        'dream2_banner_image'        => array('dream2_style', __('横幅背景图', 'dream2-mxin')),
        'dream2_background_pc'       => array('dream2_style', __('明亮模式背景图', 'dream2-mxin')),
        'dream2_background_mobile'   => array('dream2_style', __('移动端背景图', 'dream2-mxin')),
        'dream2_night_background_pc' => array('dream2_style', __('黑暗模式背景图', 'dream2-mxin')),
    );
    foreach ($image_controls as $id => $data) {
        $wp_customize->add_setting($id, array(
            'default'           => '',
            'sanitize_callback' => 'esc_url_raw',
        ));
        $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, $id, array(
            'section' => $data[0],
            'label'   => $data[1],
        )));
    }

    foreach (array(
        'dream2_theme_color'       => array(__('明亮模式主题色', 'dream2-mxin'), '#3273dc'),
        'dream2_night_theme_color' => array(__('黑暗模式主题色', 'dream2-mxin'), '#ff6b81'),
    ) as $id => $data) {
        $wp_customize->add_setting($id, array('default' => $data[1], 'sanitize_callback' => 'sanitize_hex_color'));
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, $id, array(
            'section' => 'dream2_style',
            'label'   => $data[0],
        )));
    }

    $wp_customize->add_setting('dream2_enable_banner', array('default' => true, 'sanitize_callback' => 'wp_validate_boolean'));
    $wp_customize->add_control('dream2_enable_banner', array(
        'section' => 'dream2_style',
        'label'   => __('显示首页横幅', 'dream2-mxin'),
        'type'    => 'radio',
        'choices' => array(
            '1' => __('开启', 'dream2-mxin'),
            '0' => __('关闭', 'dream2-mxin'),
        ),
    ));

    $wp_customize->add_setting('dream2_options[sidebar_column]', array(
        'type'              => 'option',
        'default'           => 'only-right',
        'sanitize_callback' => 'dream2_mxin_normalize_sidebar_column',
    ));
    $wp_customize->add_control('dream2_options[sidebar_column]', array(
        'section' => 'dream2_style',
        'label'   => __('博客布局', 'dream2-mxin'),
        'type'    => 'select',
        'choices' => array(
            'all'          => __('三列布局', 'dream2-mxin'),
            'only-left'    => __('左侧栏', 'dream2-mxin'),
            'only-right'   => __('右侧栏', 'dream2-mxin'),
            'module-left'  => __('模块左栏', 'dream2-mxin'),
            'module-right' => __('模块右栏', 'dream2-mxin'),
            'none'         => __('无侧栏', 'dream2-mxin'),
        ),
    ));

    $wp_customize->add_setting('dream2_default_theme', array('default' => 'system', 'sanitize_callback' => 'sanitize_key'));
    $wp_customize->add_control('dream2_default_theme', array(
        'section' => 'dream2_style',
        'label'   => __('默认主题模式', 'dream2-mxin'),
        'type'    => 'select',
        'choices' => array(
            'light'  => __('明亮', 'dream2-mxin'),
            'night'  => __('黑暗', 'dream2-mxin'),
            'system' => __('跟随系统', 'dream2-mxin'),
        ),
    ));

    $wp_customize->add_setting('dream2_thumbnail_mode', array('default' => 'small-alter', 'sanitize_callback' => 'sanitize_key'));
    $wp_customize->add_control('dream2_thumbnail_mode', array(
        'section' => 'dream2_posts',
        'label'   => __('文章列表封面模式', 'dream2-mxin'),
        'type'    => 'select',
        'choices' => array(
            'small'       => __('左侧小图', 'dream2-mxin'),
            'small-right' => __('右侧小图', 'dream2-mxin'),
            'small-alter' => __('左右交替', 'dream2-mxin'),
            'back'        => __('背景大图', 'dream2-mxin'),
            'none'        => __('无封面', 'dream2-mxin'),
        ),
    ));

    $wp_customize->add_setting('dream2_inline_css', array('default' => '', 'sanitize_callback' => 'wp_strip_all_tags'));
    $wp_customize->add_control('dream2_inline_css', array(
        'section' => 'dream2_custom',
        'label'   => __('内嵌 CSS', 'dream2-mxin'),
        'type'    => 'textarea',
    ));
}
add_action('customize_register', 'dream2_mxin_customize_register');
