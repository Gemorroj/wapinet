"use strict";

var Http = {
    pageCreate: function () {
        var $pageContainer = $(":mobile-pagecontainer");

        $pageContainer.on('click', '#downloadHeaders', function () {
            Helper.downloadText(
                $pageContainer.find('#textHeaders').val(),
                'headers-' + window.location.host + '.txt'
            );
            return false;
        });

        $pageContainer.on('click', '#downloadBody', function () {
            Helper.downloadText(
                $pageContainer.find('#textBody').val(),
                'body-' + window.location.host + '.txt'
            );
            return false;
        });
    }
};
