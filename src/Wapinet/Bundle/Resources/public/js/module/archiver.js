"use strict";

const Archiver = {
    pageCreate: function () {
        var $pageContainer = $(":mobile-pagecontainer");
        var deletePath;
        var $row;

        var $listArchive = $pageContainer.find("#list-archive");

        $listArchive.find("a[href='#delete-popup']").on("click", function () {
            var $this = $(this);
            deletePath = $this.data('path');
            $row = $this.closest('li');
        });

        $pageContainer.find("#delete-popup-do").on("click", function () {
            $.post(Routing.generate('archiver_delete_file', {'archive': $listArchive.data('name'), 'name': 'file', 'path': deletePath}), function () {
                $row.remove();
            });
        });
    }
};
