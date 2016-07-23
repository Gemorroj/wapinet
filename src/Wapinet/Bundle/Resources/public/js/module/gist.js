"use strict";

const Gist = {
    pageCreate: function () {
        var $pageContainer = $(":mobile-pagecontainer");
        var gistId;

        $pageContainer.find("a[href='#delete-popup']").on("click", function () {
            gistId = $(this).data('id');
        });

        $pageContainer.find("#delete-popup-do").on("click", function () {
            $.post(Routing.generate('gist_delete', {'id': gistId}), function () {
                $pageContainer.pagecontainer("change", Routing.generate('gist_index'));
                //window.location.assign(Routing.generate('gist_index'));
            });
        });
    }
};
