"use strict";

const Translate = {
    pageCreate: function () {
        var $pageContainer = $(":mobile-pagecontainer");

        $pageContainer.find('#downloadTranslation').click(function () {
            Helper.downloadText(
                $pageContainer.find('#textTranslation').val(),
                'translate.txt'
            );
            return false;
        });
    }
};
