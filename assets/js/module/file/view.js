"use strict";

const FileView = {
    pageCreate: function () {
        let $pageContainer = $(":mobile-pagecontainer");

        $pageContainer.on("click", "#delete-button", function () {
            let id = $pageContainer.find("a[download]").filter(":visible").data('id');
            $pageContainer.find("#delete-popup-" + id).popup("open", {"transition": "flow", "positionTo": "window"});

            return false;
        });

        $pageContainer.on("click", "#delete-popup-do", function () {
            let id = $pageContainer.find("a[download]").filter(":visible").data('id');
            $.post(Routing.generate('file_delete', {'id': id}), function () {
                $pageContainer.pagecontainer("change", Routing.generate('file_index'));
                //window.location.assign(Routing.generate('file_index'));
            });
        });

        $pageContainer.on("click", "a[id^='meta-button-']", function () {
            let id = $pageContainer.find("a[download]").filter(":visible").data('id');
            $pageContainer.find("#meta-popup-" + id).popup("open", {"transition": "pop", "positionTo": "window"});

            return false;
        });
        $pageContainer.on("click", "a[id^='permissions-button-']", function () {
            let id = $pageContainer.find("a[download]").filter(":visible").data('id');
            $pageContainer.find("#permissions-popup-" + id).popup("open", {"transition": "pop", "positionTo": "window"});

            return false;
        });
    },
};

export default FileView;
