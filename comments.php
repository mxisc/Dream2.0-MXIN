<?php
/**
 * Comments.
 *
 * @package Dream2_MXIN
 */

if (post_password_required()) {
    return;
}
?>
<section id="comments" class="card card-content comments-area dream-comment-widget">
    <?php if (have_comments()) : ?>
        <h3 class="comment-title"><?php printf(esc_html__('%s 条评论', 'dream2-mxin'), esc_html((string) get_comments_number())); ?></h3>
        <ol class="comment-list">
            <?php wp_list_comments(array('style' => 'ol', 'short_ping' => true, 'callback' => 'dream2_mxin_comment_callback')); ?>
        </ol>
        <?php the_comments_navigation(); ?>
    <?php endif; ?>
    <?php
    $commenter = wp_get_current_commenter();
    $required  = get_option('require_name_email');
    $aria_req  = $required ? ' aria-required="true" required' : '';
    $private_comment_field = function_exists('dream2_mxin_private_comment_fields') ? dream2_mxin_private_comment_fields() : '';
    $registered_email_notice = function_exists('dream2_mxin_registered_comment_email_notice') ? dream2_mxin_registered_comment_email_notice() : '';
    $logged_in_as = '';
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $display_name = $current_user->display_name ?: $current_user->user_login;
        $hour = (int) current_time('G');
        if ($hour >= 5 && $hour < 9) {
            $greeting = __('早上好', 'dream2-mxin');
        } elseif ($hour >= 9 && $hour < 12) {
            $greeting = __('上午好', 'dream2-mxin');
        } elseif ($hour >= 12 && $hour < 14) {
            $greeting = __('中午好', 'dream2-mxin');
        } elseif ($hour >= 14 && $hour < 18) {
            $greeting = __('下午好', 'dream2-mxin');
        } elseif ($hour >= 18 && $hour < 24) {
            $greeting = __('晚上好', 'dream2-mxin');
        } else {
            $greeting = __('夜深啦', 'dream2-mxin');
        }
        $logged_in_as = sprintf(
            '<p class="logged-in-as"><span>%1$s</span></p>',
            esc_html(sprintf(__('%1$s，%2$s，欢迎回来。', 'dream2-mxin'), $greeting, $display_name))
        );
    }
    $fields    = array(
        'author' => '<p class="comment-form-author"><label for="author">' . esc_html__('昵称', 'dream2-mxin') . ($required ? ' *' : '') . '</label><input id="author" name="author" type="text" value="' . esc_attr($commenter['comment_author']) . '"' . $aria_req . '></p>',
        'email'  => '<p class="comment-form-email"><label for="email">' . esc_html__('邮箱', 'dream2-mxin') . ($required ? ' *' : '') . '</label><input id="email" name="email" type="email" value="' . esc_attr($commenter['comment_author_email']) . '"' . $aria_req . '></p>',
        'url'    => '<p class="comment-form-url"><label for="url">' . esc_html__('网站', 'dream2-mxin') . '</label><input id="url" name="url" type="url" value="' . esc_attr($commenter['comment_author_url']) . '"></p>',
        'cookies' => '',
    );
    comment_form(array(
        'class_form'           => 'comment-form dream-comment-form',
        'class_submit'         => 'button dream-submit',
        'title_reply'          => '<i class="ri-message-3-line" aria-hidden="true"></i>' . __('评论', 'dream2-mxin'),
        'title_reply_before'   => '<h3 id="reply-title" class="comment-reply-title">',
        'title_reply_after'    => '</h3>',
        'fields'               => $fields,
        'comment_field'        => '<div class="dream-comment-editor"><p class="comment-form-comment"><label class="screen-reader-text" for="comment">' . esc_html__('评论内容', 'dream2-mxin') . '</label><textarea id="comment" class="dream-comment-source" name="comment" cols="45" rows="6" maxlength="65525"></textarea><div class="dream-comment-rich-editor" contenteditable="true" role="textbox" aria-multiline="true" aria-label="' . esc_attr__('评论内容', 'dream2-mxin') . '" data-placeholder="' . esc_attr__('编写评论', 'dream2-mxin') . '"></div></p><div class="dream-comment-toolbar"><button type="button" data-comment-action="bold" title="' . esc_attr__('粗体', 'dream2-mxin') . '"><i class="ri-bold"></i></button><button type="button" data-comment-action="italic" title="' . esc_attr__('斜体', 'dream2-mxin') . '"><i class="ri-italic"></i></button><button type="button" data-comment-action="underline" title="' . esc_attr__('下划线', 'dream2-mxin') . '"><i class="ri-underline"></i></button><button type="button" data-comment-action="strike" title="' . esc_attr__('删除线', 'dream2-mxin') . '"><i class="ri-strikethrough"></i></button><button type="button" data-comment-action="inline-code" title="' . esc_attr__('行内代码', 'dream2-mxin') . '"><i class="ri-code-line"></i></button><button type="button" data-comment-action="quote" title="' . esc_attr__('引用', 'dream2-mxin') . '"><i class="ri-double-quotes-l"></i></button><button type="button" data-comment-action="code-block" title="' . esc_attr__('代码块', 'dream2-mxin') . '"><i class="ri-code-box-line"></i></button><button type="button" data-comment-action="emoji" title="' . esc_attr__('表情', 'dream2-mxin') . '"><i class="ri-emotion-line"></i></button>' . $private_comment_field . '</div></div>',
        'logged_in_as'         => $logged_in_as,
        'comment_notes_before' => $registered_email_notice,
        'comment_notes_after'  => '',
        'label_submit'         => __('提交评论', 'dream2-mxin'),
        'submit_button'        => '<button name="%1$s" type="submit" id="%2$s" class="%3$s"><i class="ri-send-plane-line" aria-hidden="true"></i> %4$s</button>',
    ));
    ?>
</section>
