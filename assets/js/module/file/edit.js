"use strict";

import {Autocomplete} from "../../core";

const FileEdit = {
    pageCreate: function () {
        let $pageContainer = $(":mobile-pagecontainer");

        Autocomplete.text(
            Routing.generate('file_tags_search'),
            $pageContainer.find('#file_edit_form_tags'),
            $pageContainer.find('#suggestions')
        );

        $pageContainer.find('#edit-password').change(function () {
            $pageContainer.find('#edit-password-row').slideToggle();
            $pageContainer.find('#file_edit_form_tags').parent().parent().slideToggle();
        });

        if ($pageContainer.find("#file_edit_form_plainPassword").val() !== "") {
            $pageContainer.find('#edit-password').click();
        }
    }
};

export default FileEdit;
