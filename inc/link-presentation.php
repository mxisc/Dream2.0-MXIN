<?php
/**
 * Presentational fallback for the core WordPress Links data.
 *
 * @package Dream2_MXIN
 */

if (!defined('ABSPATH')) {
    exit;
}
function dream2_mxin_theme_friend_link_group_terms() {
    $terms = get_terms(array('taxonomy' => 'link_category', 'hide_empty' => true));
    if (is_wp_error($terms)) return array();
    return array_map(static function ($term) {
        return array('label' => $term->name, 'term' => $term);
    }, $terms);
}
