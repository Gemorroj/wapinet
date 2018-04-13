"use strict";

import {Helper} from "../core";

const Translate = {
    pageCreate: function () {
        var $pageContainer = $(":mobile-pagecontainer");

        $pageContainer.on('click', '#downloadTranslation', function () {
            Helper.downloadText(
                $pageContainer.find('#textTranslation').val(),
                'translate.txt'
            );
            return false;
        });
    }
};

export default Translate;
