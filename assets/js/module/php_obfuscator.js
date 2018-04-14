"use strict";

import {Helper} from "../core";

const PhpObfuscator = {
    pageCreate: function () {
        let $pageContainer = $(":mobile-pagecontainer");

        $pageContainer.on('click', '#downloadObfuscation', function () {
            Helper.downloadText(
                $pageContainer.find('#textObfuscation').val(),
                'obfuscation.txt'
            );
            return false;
        });
    }
};

export default PhpObfuscator;
