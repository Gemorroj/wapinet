"use strict";

const Gist = {
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
        var gistId;
        var that = this;

        that.$container.find("a[href='#delete-popup']").on("click", function () {
            gistId = $(this).data('id');
        });

        that.$container.find("#delete-popup-do").on("click", function () {
            $.post(Routing.generate('gist_delete', {'id': gistId}), function () {
                $(":mobile-pagecontainer").pagecontainer("change", Routing.generate('gist_index'));
                //window.location.assign(Routing.generate('gist_index'));
            });
        });
    }
};
