"use strict";

const Guestbook = {
    pageCreate: function () {
        var $pageContainer = $(":mobile-pagecontainer");

        var $textarea = $pageContainer.find("#message_form_message");
        var $form = $pageContainer.find("form[name='message_form']");

        $form.submit(function () {
            $form.find("#message_form_submit").prop('disabled', true);
        });

        var quote = function () {
            $textarea.focus();

            var $liComment = $(this).parent().next();
            var text = $liComment.children('div').text();
            var author = $liComment.find('span.comment-authorname>a').text();
            $textarea.val('[quote=' + author + ']' + text + '[/quote]');
            $textarea.keyup();

            return false;
        };
        var popup = function () {
            var $link = $(this);
            $($link.attr('href')).popup("open", {"transition": "turn", "positionTo": $link});

            return false;
        };

        $pageContainer.find('a[href^="#popup-"]').click(popup);
        $pageContainer.find("a.item-quote").click(quote);
    }
};
