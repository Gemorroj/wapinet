"use strict";

const Message = {
    pageCreate: function () {
        var $pageContainer = $(":mobile-pagecontainer");
        var threadId;
        var $row;

        var $listThreads = $pageContainer.find("#list-threads");
        $listThreads.find("a[href='#delete-popup-inbox'], a[href='#delete-popup-sent'], a[href='#delete-popup-deleted']").on("click", function () {
            var $this = $(this);
            threadId = $this.data('id');
            $row = $this.closest('li');
        });
        $listThreads.find("a[href='#restore-popup-inbox'], a[href='#restore-popup-sent'], a[href='#restore-popup-deleted']").on("click", function () {
            var $this = $(this);
            threadId = $this.data('id');
            $row = $this.closest('li');
        });
        $pageContainer.on("click", "a[data-thread]", function () {
            $pageContainer.pagecontainer("change", this.href);
        });

        $pageContainer.find("#delete-popup-inbox-do, #delete-popup-sent-do, #delete-popup-deleted-do").on("click", function () {
            $.post(Routing.generate('wapinet_message_thread_delete', {'threadId': threadId}), function () {
                $row.prev("li[role='heading']").remove();
                $row.remove();
            });
        });
        $pageContainer.find("#restore-popup-inbox-do, #restore-popup-sent-do, #restore-popup-deleted-do").on("click", function () {
            $.post(Routing.generate('wapinet_message_thread_undelete', {'threadId': threadId}), function () {
                $row.prev("li[role='heading']").remove();
                $row.remove();
            });
        });
    }
};
