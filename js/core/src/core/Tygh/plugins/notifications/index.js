import { Tygh } from "../..";
import $ from "jquery";

const _ = Tygh;

var container;
var timers = {};
var delay = 0;

let icon_live_edit = '<span class="ty-icon cm-icon-live-edit icon-live-edit ty-icon-live-edit"></span>';

function _duplicateNotification(key) {
    var dups = $('div[data-ca-notification-key=' + key + ']');
    if (dups.length) {

        if (!_addToDialog(dups)) {
            dups.fadeTo('fast', 0.5).fadeTo('fast', 1).fadeTo('fast', 0.5).fadeTo('fast', 1);
        }

        // Restart autoclose timer
        if (timers[key]) {
            clearTimeout(timers[key]);
            methods.close(dups, true);
        }

        return true;
    }

    return false;
}

function _closeNotification(notification) {
    if (notification.find('.cm-notification-close-ajax').length) {
        $.ceAjax('request', fn_url('notifications.close?notification_id=' + notification.data('caNotificationKey')), {
            hidden: true
        });
    }

    notification.fadeOut('fast', function () {
        notification.remove();
    });

    if (notification.hasClass('cm-notification-content-extended')) {
        var overlay = $('.ui-widget-overlay[data-ca-notification-key=' + notification.data('caNotificationKey') + ']');
        if (overlay.length) {
            overlay.fadeOut('fast', function () {
                overlay.remove();
            });
        }
    }
}

function _processTranslation(text) {
    if (_.live_editor_mode && text.indexOf('[lang') != -1) {
        text = '<var class="live-edit-wrap">' + icon_live_edit + '<var class="cm-live-edit live-edit-item" data-ca-live-edit="langvar::' + text.substring(text.indexOf('=') + 1, text.indexOf(']')) + '">' + text.substring(text.indexOf(']') + 1, text.lastIndexOf('[')) + '</var></var>';
    }

    return text;
}

function _pickFromDialog(event) {
    var nt = $('.cm-notification-content', $(event.target));
    if (nt.length) {
        if (!_addToDialog(nt)) {
            container.append(nt);
        }
    }
    return true;
}

function _addToDialog(notification) {
    var dlg = $.ceDialog('get_last');
    if (dlg.length) {
        $('.cm-notification-container-dialog', dlg).prepend(notification);
        dlg.off('dialogclose', _pickFromDialog);
        dlg.on('dialogclose', _pickFromDialog);
        return true;
    }
    return false;
}

export const methods = {
    show: function (data, key) {
        if (!key) {
            key = $.crc32(data.message);
        }

        if (typeof (data.message) == 'undefined') {
            return false;
        }

        if (_duplicateNotification(key)) {
            return true;
        }

        data.message = _processTranslation(data.message);
        data.title = _processTranslation(data.title);

        // Popup message in the screen center - should be only one at time
        if (data.type == 'I') {
            $('.cm-notification-content.cm-notification-content-extended').each(function () {
                methods.close($(this), false);
            });

            $(_.body).append(
                '<div class="ui-widget-overlay" style="z-index:1010" data-ca-notification-key="' + key + '"></div>'
            );

            var $notification = $('<div class="cm-notification-content cm-notification-content-extended notification-content-extended ' + (data.message_state == "I" ? ' cm-auto-hide' : '') + '" data-ca-notification-key="' + key + '">' +
                '<h1>' + data.title + '<span class="cm-notification-close close"></span></h1>' +
                '<div class="notification-body-extended">' +
                data.message +
                '</div>' +
                '</div>');

            methods.position($notification);

        } else {
            var n_class = 'alert';
            var b_class = '';

            if (data.type == 'N') {
                n_class += ' alert-success';
            } else if (data.type == 'W') {
                n_class += ' alert-warning';
            } else if (data.type == 'S') {
                n_class += ' alert-info';
            } else {
                n_class += ' alert-error';
            }

            if (data.message_state == 'I') {
                n_class += ' cm-auto-hide';
            } else if (data.message_state == 'S') {
                b_class += ' cm-notification-close-ajax';
            }

            var $notification = $('<div class="cm-notification-content notification-content ' + n_class + '" data-ca-notification-key="' + key + '">' +
                '<button type="button" class="close cm-notification-close ' + b_class + '" data-dismiss="alert">&times;</button>' +
                '<strong>' + data.title + '</strong>' + data.message +
                '</div>');

            if (!_addToDialog($notification)) {
                container.append($notification);
            }
        }

        $.ceEvent('trigger', 'ce.notificationshow', [$notification]);

        if (data.message_state == 'I') {
            methods.close($notification, true);
        }
    },

    showMany: function (data) {
        for (var key in data) {
            methods.show(data[key], key);
        }
    },

    closeAll: function () {
        var notifications = container.find('.cm-notification-content');
        var dlg = $.ceDialog('get_last');
        if (dlg.length) {
            notifications = notifications.add(dlg.find('.cm-notification-content'));
        }

        notifications.each(function () {
            var self = $(this);
            if (!self.hasClass('cm-notification-close-ajax')) {
                methods.close(self, false);
            }
        })
    },

    close: function (notification, delayed) {
        if (delayed == true) {
            if (delay === 0) { // do not auto-close
                return true;
            }

            timers[notification.data('caNotificationKey')] = setTimeout(function () {
                methods.close(notification, false);
            }, delay);

            return true;
        }

        _closeNotification(notification);
    },

    init: function () {
        delay = _.notice_displaying_time * 1000;
        container = $('.cm-notification-container');

        $(_.doc).on('click', '.cm-notification-close', function () {
            methods.close($(this).parents('.cm-notification-content:first'), false);
        })

        container.find('.cm-auto-hide').each(function () {
            methods.close($(this), true);
        });


        $('.cm-notification-content.notification-content-extended').each((id, elm) => {
            let $notification = $(elm);
            methods.position($notification)
        });
    },

    position: ($notification, is_append) => {
        is_append = (is_append === false) ? false : true;

        let w = $.getWindowSizes(),
            notificationMaxHeight = w.view_height - 300;
        $notification.find('.cm-notification-max-height').css({
            'max-height': notificationMaxHeight
        });
        if (is_append) {
            $(_.body).append($notification);
        }
        $notification.css('top', w.view_height / 2 - ($notification.height() / 2));
    }
};

/**
 * Notifications
 * @param {JQueryStatic} $
 */
export const ceNotificationInit = function ($) {
    $.ceNotification = function (method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else {
            $.error('ty.notification: method ' + method + ' does not exist');
        }
    };
}
