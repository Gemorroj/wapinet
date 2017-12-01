"use strict";

var PhpObfuscator = {
    pageCreate: function () {
        var $pageContainer = $(":mobile-pagecontainer");

        $pageContainer.on('click', '#downloadObfuscation', function () {
            Helper.downloadText(
                $pageContainer.find('#textObfuscation').val(),
                'obfuscation.txt'
            );
            return false;
        });
    }
};
