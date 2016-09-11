"use strict";

var Guestbook = {
    pageCreate: function () {
        var $pageContainer = $(":mobile-pagecontainer");

        var quote = function () {
            var $link = $(this);
            var $textarea = $pageContainer.find("textarea[name='message_form[message]']").filter(":visible");
            $textarea.focus();

            var $liComment = $link.parent().next();
            var text = $liComment.children('div').text();
            var author = $link.next('a').text();
            $textarea.val('[quote=' + author + ']' + text + '[/quote]');
            $textarea.keyup();

            return false;
        };
        var popup = function () {
            var $link = $(this);
            var selector = $link.attr('href');

            $pageContainer.find(selector).popup("open", {"transition": "turn", "positionTo": $link});

            return false;
        };

        $pageContainer.on("click", "a[href^='#popup-']", popup);
        $pageContainer.on("click", "a.item-quote", quote);
    }
};
