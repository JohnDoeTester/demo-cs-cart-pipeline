import { Tygh } from "../..";
import $ from "jquery";

const _ = Tygh;

export const methods = {

    init: function () {

        if ($.history) {

            $.history.init(function (hash, params) {

                if (params && 'result_ids' in params) {
                    var uri = methods.parseHash('#' + hash);
                    var href = uri.indexOf(_.current_location) != -1 ? uri : _.current_location + '/' + uri;
                    var target_id = params.result_ids;
                    var a_elm = $('a[data-ca-target-id="' + target_id + '"]:first'); // hm, used for callback only, so I think it will work with the first found link
                    var name = a_elm.prop('name');

                    $.ceAjax('request', href, {
                        full_render: params.full_render,
                        result_ids: target_id,
                        caching: false,
                        obj: a_elm,
                        skip_history: true,
                        callback: 'ce.ajax_callback_' + name
                    });

                } else if (_.embedded) {
                    // If the hash changed by user manually or by external script, perform redirect to
                    // the specified location
                    var url = fn_url(window.location.href);
                    if (url != _.current_url) {
                        $.redirect(url);
                    }
                }
            }, {
                unescape: false
            });
            return true;
        } else {
            return false;
        }
    },

    load: function (url, params) {
        var _params, current_url;

        url = methods.prepareHash(url);
        current_url = methods.prepareHash(_.current_url);

        _params = {
            result_ids: params.result_ids,
            full_render: params.full_render
        }

        $.ceEvent('trigger', 'ce.history_load', [url]);
        $.history.reload(current_url, _params);
        $.history.load(url, _params);
    },

    prepareHash: function (url) {
        try {
            url = decodeURI(url); // urls in original content are escaped, so we need to unescape them
        } finally {
            if (url.indexOf('://') !== -1) {
                url = url.str_replace(_.current_location + '/', '');
            }

            url = fn_query_remove(url, ['result_ids']);
            url = '!/' + url;

            return url;
        }
    },

    parseHash: function (hash) {
        if (hash.indexOf('%') !== -1) {
            hash = decodeURI(hash);
        }

        if (hash.indexOf('#!') != -1) {
            var parts = hash.split('#!/');

            return parts[1] || '';
        }

        return '';
    }
};

/**
 * History plugin
 * @param {JQueryStatic} $ 
 */
export const ceHistoryInit = function ($) {
    $.ceHistory = function (method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else {
            $.error('ty.history: method ' + method + ' does not exist');
        }
    }
}
