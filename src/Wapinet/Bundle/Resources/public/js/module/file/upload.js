"use strict";

const FileUpload = {
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
            that.$container.find('#file_upload_form_tags'),
            that.$container.find('#suggestions')
        );

        that.$container.find('#upload-password').change(function () {
            that.$container.find('#upload-password-row').slideToggle();
            that.$container.find('#file_upload_form_tags').parent().parent().slideToggle();
        });
    }
};
