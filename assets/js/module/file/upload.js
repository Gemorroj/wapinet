"use strict";

import {Autocomplete} from "../../core";

const FileUpload = {
    pageCreate: function () {
        let $pageContainer = $(":mobile-pagecontainer");

        Autocomplete.text(
            Routing.generate('file_tags_search'),
            $pageContainer.find('#file_upload_form_tags'),
            $pageContainer.find('#suggestions')
        );

        $pageContainer.find('#upload-password').change(function () {
            $pageContainer.find('#upload-password-row').slideToggle();
            $pageContainer.find('#file_upload_form_tags').parent().parent().slideToggle();
        });
    }
};

export default FileUpload;
