"use strict";

var Rest = {
    pageCreate: function () {
        var $pageContainer = $(":mobile-pagecontainer");

        $pageContainer.find('#downloadHeaders').click(function () {
            Helper.downloadText(
                $pageContainer.find('#textHeaders').val(),
                'headers-' + window.location.host + '.txt'
            );
            return false;
        });

        $pageContainer.find('#downloadBody').click(function () {
            Helper.downloadText(
                $pageContainer.find('#textBody').val(),
                'body-' + window.location.host + '.txt'
            );
            return false;
        });
    }
};
