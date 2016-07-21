"use strict";

const Translate = {
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

        that.$container.find('#downloadTranslation').click(function () {
            Helper.downloadText(
                that.$container.find('#textTranslation').val(),
                'translate.txt'
            );
            return false;
        });
    }
};
