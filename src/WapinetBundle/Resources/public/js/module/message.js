"use strict";

var Message = {
    pageCreate: function () {
        var $pageContainer = $(":mobile-pagecontainer");
        var threadId;
        var $row;

        $pageContainer.on("click", "a[href='#delete-popup']", function () {
            var $popup = $pageContainer.find("div[data-id='delete-popup']").filter(":visible");

            if ($popup.length) {
                var $this = $(this);
                threadId = $this.data('id');
                $row = $this.closest('li');

                $popup.popup("open", {"transition": "flow", "positionTo": "window"});
            }

            return false;
        });
        $pageContainer.on("click", "a[href='#restore-popup']", function () {
            var $popup = $pageContainer.find("div[data-id='restore-popup']").filter(":visible");

            if ($popup.length) {
                var $this = $(this);
                threadId = $this.data('id');
                $row = $this.closest('li');

                $popup.popup("open", {"transition": "flow", "positionTo": "window"});
            }

            return false;
        });

        $pageContainer.on("click", "a[data-thread]", function () {
            $pageContainer.pagecontainer("change", this.href);
        });

        $pageContainer.on("click", "a[data-id='delete-popup-do']", function () {
            $.post(Routing.generate('wapinet_message_thread_delete', {'threadId': threadId}), function () {
                $row.prev("li[role='heading']").remove();
                $row.remove();
            });
        });
        $pageContainer.on("click", "a[data-id='restore-popup-do']", function () {
            $.post(Routing.generate('wapinet_message_thread_undelete', {'threadId': threadId}), function () {
                $row.prev("li[role='heading']").remove();
                $row.remove();
            });
        });
    }
};
