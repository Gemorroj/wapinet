"use strict";

const Rest = {
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

        that.$container.find('#downloadHeaders').click(function () {
            Helper.downloadText(
                that.$container.find('#textHeaders').val(),
                'headers-' + window.location.host + '.txt'
            );
            return false;
        });

        that.$container.find('#downloadBody').click(function () {
            Helper.downloadText(
                that.$container.find('#textBody').val(),
                'body-' + window.location.host + '.txt'
            );
            return false;
        });
    }
};
