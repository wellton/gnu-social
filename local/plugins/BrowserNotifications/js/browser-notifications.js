(function () {
    /*global RealtimeUpdate: false, BrowserNotifications: false*/
    "use strict";

    if (!("Notification" in window) || typeof (RealtimeUpdate) === "undefined" || BrowserNotifications.enabled === false) {
        return;
    }

    var gs_makeNoticeItem = RealtimeUpdate.makeNoticeItem, // Monkey-patch
        showNotification,
        isMention;

    // Ask for notification permissions on page load
    Notification.requestPermission();

    showNotification = function (node) {
        /* Request permission again if previous requests were missed/ignored
           If request was previouly granted, it doesn't ask again; it just shows
           If request was previouly denied, nothing happens */
        Notification.requestPermission(function (permission) {
            if (permission === "granted") {
                var $node = $(node),
                    content = $.trim($node.find(".e-content").text()),
                    isRepeat = $node.find(".repeat").length > 0,
                    isSystem = $node.find(".system-activity").length > 0,
                    author,
                    repeater,
                    title,
                    icon = $node.find(".avatar").attr("src") || "";

                if (isSystem) {
                    title = "";
                    content = $.trim($node.find(".system-activity").text());
                } else if (isRepeat) {
                    author = $.trim($node.find(".notice-headers .h-card").first().text());
                    repeater = $.trim($node.find(".repeat .p-author").text());
                    title = repeater + " repeated a notice by " + author;
                } else {
                    author = $.trim($node.find(".notice-headers .p-author").text());
                    title = "New notice from " + author;
                }

                new Notification(title, {body: content, icon: icon});
            }
        });
    };

    isMention = function ( /* node */) {
        /* TODO */

        return true;
    };

    RealtimeUpdate.makeNoticeItem = function (data, callback) {
        /* Monkey-patch */
        var gs_callback = callback;

        // Call the original "makeNoticeItem", but with our own callback
        gs_makeNoticeItem(data, function (node) {
            /* Call the original callback */
            gs_callback(node);

            if (BrowserNotifications.mentions_only === true) {
                if (isMention(node) === true) {
                    showNotification(node);
                }
            } else {
                showNotification(node);
            }
        });
    };
}());
