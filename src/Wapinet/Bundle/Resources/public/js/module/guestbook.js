"use strict";

const Guestbook = {
    $container: null,
    /**
     *
     * @param {jQuery} $container
     */
    setContainer: function ($container) {
        this.$container = $container;

        return this;
    },

    pageCreate: function () {
        var $textarea = this.$container.find("#message_form_message");
        var $form = this.$container.find("form[name='message_form']");

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
            alert('popup');
            var $link = $(this);
            $($link.attr('href')).popup("open", {"transition": "turn", "positionTo": $link});

            return false;
        };

        this.$container.find('a[href^="#popup-"]').click(popup);
        this.$container.find("a.item-quote").click(quote);
    }
};
