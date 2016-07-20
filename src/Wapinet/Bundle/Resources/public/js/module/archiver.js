"use strict";

const Archiver = {
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
        var deletePath;
        var $row;

        var $listArchive = this.$container.find("#list-archive");

        $listArchive.find("a[href='#delete-popup']").on("click", function () {
            var $this = $(this);
            deletePath = $this.data('path');
            $row = $this.closest('li');
        });

        this.$container.find("#delete-popup-do").on("click", function () {
            $.post(Routing.generate('archiver_delete_file', {'archive': $listArchive.data('name'), 'name': 'file', 'path': deletePath}), function () {
                $row.remove();
            });
        });
    }
};
