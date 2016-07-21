"use strict";

const PhpObfuscator = {
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

        that.$container.find('#downloadObfuscation').click(function () {
            Helper.downloadText(
                that.$container.find('#textObfuscation').val(),
                'obfuscation.txt'
            );
            return false;
        });
    }
};
