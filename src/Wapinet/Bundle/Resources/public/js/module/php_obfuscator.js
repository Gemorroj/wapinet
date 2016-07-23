"use strict";

const PhpObfuscator = {
    pageCreate: function () {
        var $pageContainer = $(":mobile-pagecontainer");

        $pageContainer.find('#downloadObfuscation').click(function () {
            Helper.downloadText(
                $pageContainer.find('#textObfuscation').val(),
                'obfuscation.txt'
            );
            return false;
        });
    }
};
