"use strict";

const Archiver = {
    pageCreate: function () {
        let $pageContainer = $(":mobile-pagecontainer");
        let deletePath;
        let $row;

        let $listArchive = $pageContainer.find("#list-archive");

        $listArchive.find("a[href='#delete-popup']").on("click", function () {
            let $this = $(this);
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

export default Archiver;
