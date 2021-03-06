"use strict";

const FileView = {
    pageCreate: function () {
        let $pageContainer = $(":mobile-pagecontainer");

        $pageContainer.on("click", "#delete-button", function () {
            let id = $pageContainer.find("a[download]").filter(":visible").data('id');
            $pageContainer.find("#delete-popup-" + id).popup("open", {"transition": "flow", "positionTo": "window"});

            return false;
        });

        $pageContainer.on("click", "#delete-popup-do", function () {
            let id = $pageContainer.find("a[download]").filter(":visible").data('id');
            $.post(Routing.generate('file_delete', {'id': id}), function () {
                $pageContainer.pagecontainer("change", Routing.generate('file_index'));
                //window.location.assign(Routing.generate('file_index'));
            });
        });

        $pageContainer.on("click", "a[id^='meta-button-']", function () {
            let id = $pageContainer.find("a[download]").filter(":visible").data('id');
            $pageContainer.find("#meta-popup-" + id).popup("open", {"transition": "pop", "positionTo": "window"});

            return false;
        });
        $pageContainer.on("click", "a[id^='permissions-button-']", function () {
            let id = $pageContainer.find("a[download]").filter(":visible").data('id');
            $pageContainer.find("#permissions-popup-" + id).popup("open", {"transition": "pop", "positionTo": "window"});

            return false;
        });
    },
    pageShow: function ($pageContainer) {
        let data = $pageContainer.find("a[download]").filter(":visible").data();
        if (!data) {
            return;
        }

        if (data.video) {
            this.viewVideo($pageContainer, data);
        } else if (data.audio) {
            this.viewAudio($pageContainer, data);
        } else {
            $pageContainer.find("#jp_container_1").hide();
        }
    },
    /**
     *
     * @param {JQuery} $pageContainer
     * @param {Object} data
     */
    viewAudio: function ($pageContainer, data) {
        // (Default width: "420px")

        let width = $(window).width();
        let space = 30;
        let defaultWidth = 420;
        width = (width > (defaultWidth + space)) ? defaultWidth : (width - space);

        let media = {};
        media[data.format] = data.audio;

        $pageContainer.find("#jquery_jplayer_1").jPlayer({
            "ready": function () {
                $pageContainer.find("#jp_container_1").width(width + "px");
                $pageContainer.find(".jp-controls").width(width - 40 + "px");
                $pageContainer.find(".jp-progress, .jp-time-holder").css({width: width - 120 + "px", maxWidth: "186px"});
                $(this).jPlayer("setMedia", media);
            },
            "supplied": data.format,
            "size": {"width": width + "px"},
            "swfPath": Jplayer.swfPath
        });
    },
    /**
     *
     * @param {JQuery} $pageContainer
     * @param {Object} data
     */
    viewVideo: function ($pageContainer, data) {
        // (Default width: "480px")
        // (Default height: "270px")

        let width = $(window).width();
        let space = 30;
        let defaultWidth = 480;
        width = (width > (defaultWidth + space)) ? defaultWidth : (width - space);

        let media = {};
        media[data.format] = data.video;
        if (data.screenshot) {
            media.poster = data.screenshot;
        }

        $pageContainer.find("#jquery_jplayer_1").jPlayer({
            "ready": function () {
                $pageContainer.find(".jp-controls-holder").width(width - 40 + "px");
                $pageContainer.find(".jp-controls").css({"margin-left": width - 250 + "px"});
                $pageContainer.find("#jp_container_1").width(width + "px");
                $(this).jPlayer("setMedia", media);
            },
            "supplied": data.format,
            "size": {"width": width + "px"},
            "swfPath": Jplayer.swfPath
        });
    }
};

export default FileView;
