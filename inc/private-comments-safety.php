<?php
/**
 * Fail-closed display protection for comments created as private by Plus.
 *
 * This file does not provide private-comment submission or management. It only
 * prevents stored private content from becoming public when Plus is inactive.
 *
 * @package Dream2_MXIN
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('dream2_mxin_is_private_comment')) {
    function dream2_mxin_comment_is_private_safety($comment) {
        $comment = get_comment($comment);
        return $comment && (bool) get_comment_meta($comment->comment_ID, 'private_comment', true);
    }

    function dream2_mxin_private_comment_safety_text($text, $comment = null) {
        if ($comment && dream2_mxin_comment_is_private_safety($comment)) {
            return '<span class="dream-private-placeholder"><i class="ri-lock-line"></i> ' . esc_html__('此评论为私密评论，增强插件停用期间内容保持隐藏。', 'dream2-mxin') . '</span>';
        }
        return $text;
    }
    add_filter('get_comment_text', 'dream2_mxin_private_comment_safety_text', 1, 2);
}
