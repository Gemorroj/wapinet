var Vk = {
    data: null,
    show: function (data) {
        if (data && (data.response || data.error)) {
            Vk.data = data;
            $("#user-vk").click(Vk.popup);
        }
    },
    popup: function () {
        var $link = $(this);
        var $popup = $($link.attr("href"));
        var content = Vk._makeContent();

        $popup.find('p').html(content);
        $popup.popup("open", {"transition": "pop", "positionTo": $link});

        return false;
    },
    _makeContent: function () {
        if (!Vk.data) {
            return 'Error';
        }

        if (Vk.data.error && Vk.data.error.error_code) {
            return Vk.data.error.error_msg;
        }

        if (Vk.data.response && Vk.data.response[0]) {
            var user = Vk.data.response[0];
            return '<a rel="external" href="https://vk.com/id' + user.uid + '"><img src="' + user.photo_200_orig + '" /></a><br/><span>' + user.first_name + ' ' + user.last_name + '</span><br/>' + (user.online ? '<span class="green">Онлайн</span>' : '<span class="gray">Офлайн</span>');
        }

        return 'No data';
    }
};

$(document).one("pagecreate", "#page", function () {
    var vkId = $("#user-vk").data("id");

    var script = document.createElement('script');
    script.type = "text/javascript";
    script.src = 'https://api.vk.com/method/users.get?callback=Vk.show&fields=online,photo_200_orig&user_ids=' + vkId;

    document.getElementsByTagName("head")[0].appendChild(script);
});
