import { defaultOptions } from './defaultOptions';
import { NotificationReceiversEditor } from './NotificationReceiversEditor';

const init = ($elems, options) => {
    $elems.each(function () {
        let $elem = $(this),
            options = $.extend({}, defaultOptions, getOptions($elem, defaultOptions), options);

        let editor = new NotificationReceiversEditor($elem, options);

        $elem.data('caNotificationReceiversEditor', editor);
    });
};

const execute = () => {

};

const getOptions = ($elem) => {
    var options = {};

    for (var key in defaultOptions) {
        options[key] = $elem.data($.camelCase(`ca-notification-receivers-editor-${key}`));
    }

    return options;
}

/**
 * @param {JQueryStatic} $
 */
export const ceNotificationReceiversEditorInit = function ($) {
    $.fn.ceNotificationReceiversEditor = function (method, ...params) {
        if (!method || method === 'init') {
            init($(this), params[0] || {}, defaultOptions);
        } else {
            execute($(this), method, ...params);
        }

        return $(this);
    };
}

