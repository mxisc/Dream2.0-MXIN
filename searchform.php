<?php
/**
 * Search form.
 *
 * @package Dream2_MXIN
 */
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
    <label>
        <span class="screen-reader-text"><?php esc_html_e('搜索：', 'dream2-mxin'); ?></span>
        <input type="search" class="search-field" placeholder="<?php esc_attr_e('输入关键词…', 'dream2-mxin'); ?>" value="<?php echo esc_attr(get_search_query()); ?>" name="s">
    </label>
    <button type="submit" class="search-submit"><i class="ri-search-line"></i><span class="screen-reader-text"><?php esc_html_e('搜索', 'dream2-mxin'); ?></span></button>
</form>

