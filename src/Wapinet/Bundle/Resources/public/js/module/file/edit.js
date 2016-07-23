"use strict";

const FileEdit = {
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
        var that = this;

        Autocomplete.text(
            Routing.generate('file_tags_search'),
            that.$container.find('#file_edit_form_tags'),
            that.$container.find('#suggestions')
        );

        that.$container.find('#edit-password').change(function () {
            that.$container.find('#edit-password-row').slideToggle();
            that.$container.find('#file_edit_form_tags').parent().parent().slideToggle();
        });

        if (that.$container.find("#file_edit_form_plainPassword").val() !== "") {
            that.$container.find('#edit-password').click();
        }
    }
};
