import $ from "jquery";

/**
 * Print variable contents
 * TODO: jsdoc this
 */
export const fn_print_r = function (value) {
    fn_alert(fn_print_array(value));
}

/**
 * Show alert
 * @param {string} msg Message
 * @param {boolean} not_strip Strip HTML-tags from message
 * TODO: jsdoc this
 */
export const fn_alert = function (msg, not_strip) {
    msg = not_strip ? msg : fn_strip_tags(msg);
    alert(msg);
}

/**
 * Dump object to string, act like var_dump in PHP
 * @param {any} arr Object, that should dumped
 * @param {*} level Adds padding in dump string
 */
export const fn_print_array = function (arr, level) {
    var dumped_text = "";
    if (!level) {
        level = 0;
    }

    //The padding given at the beginning of the line.
    var level_padding = "";
    for (var j = 0; j < level + 1; j++) {
        level_padding += "    ";
    }

    if (typeof (arr) == 'object') { //Array/Hashes/Objects
        for (var item in arr) {
            var value = arr[item];

            if (typeof (value) == 'object') { //If it is an array,
                dumped_text += level_padding + "'" + item + "' ...\n";
                dumped_text += fn_print_array(value, level + 1);
            } else {
                dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
            }
        }
    } else { //Stings/Chars/Numbers etc.
        dumped_text = arr + " (" + typeof (arr) + ")";
    }

    return dumped_text;
}

/**
 * Converts raw dispatch to request-compatible URL
 * @param {string} url 
 */
export const fn_url = function (url) {
    var index_url = Tygh.current_location + '/' + Tygh.index_script;
    var components = $.parseUrl(url);

    if (url == '') {
        url = index_url;

    } else if (components.protocol) {

        if (Tygh.embedded) {

            var s, spos;
            if (Tygh.facebook && Tygh.facebook.url.indexOf(components.location) != -1) {
                s = '&app_data=';
            } else if (Tygh.init_context == components.source.str_replace('#' + components.anchor, '')) {
                s = '#!';
            }

            if (s) {

                var q = '';
                if ((spos = url.indexOf(s)) != -1) {
                    q = decodeURIComponent(url.substr(spos + s.length)).replace('&amp;', '&');
                }

                url = Tygh.current_location + q;
            }
        }

    } else if (components.file != Tygh.index_script) {
        if (url.indexOf('?') == 0) {
            url = index_url + url;
        } else {
            url = index_url + '?dispatch=' + url.replace('?', '&');
        }
    }

    return url;
}

/**
 * String tags from string
 * @param {string} str 
 */
export const fn_strip_tags = function (str) {
    str = String(str).replace(/<.*?>/g, '');
    return str;
}

/**
 * TODO: jsdoc this
 */
export const fn_reload_form = function (jelm) {
    var form = jelm.parents('form');
    var container = form.parent();

    var submit_btn = form.find("input[type='submit']");
    if (!submit_btn.length) {
        submit_btn = Tygh.$('[data-ca-target-form=' + form.prop('name') + ']');
    }

    if (container.length && submit_btn.length) {

        var url = form.prop('action') + '?reload_form=1&' + submit_btn.prop('name');

        var data = form.serializeObject();
        var result_ids;
        // If not preset result_ids in form get form container id
        if (data.result_ids != 'undefined') {
            result_ids = data.result_ids;
        } else {
            result_ids = container.prop('id');
        }
        Tygh.$.ceAjax('request', fn_url(url), {
            data: data,
            result_ids: result_ids
        });
    }
}

/**
 * TODO: jsdoc this
 */
export const fn_get_listed_lang = function (langs) {
    var $ = Tygh.$;
    // check langs priority
    var check_langs = [Tygh.cart_language, Tygh.default_language, 'en'];
    var lang = '';

    if (langs.length) {
        lang = langs[0];

        for (var i = 0; i < check_langs.length; i++) {
            if (Tygh.$.inArray(check_langs[i], langs) != -1) {
                lang = check_langs[i];
                break;
            }
        }
    }

    return lang;
}

/**
 * TODO: jsdoc this
 */
export const fn_query_remove = function (query, vars) {
    if (typeof (vars) == 'undefined') {
        return query;
    }
    if (typeof vars == 'string') {
        vars = [vars];
    }
    var start = query;
    if (query.indexOf('?') >= 0) {
        start = query.substr(0, query.indexOf('?') + 1);
        var search = query.substr(query.indexOf('?') + 1);
        var srch_array = search.split("&");
        var temp_array = [];
        var concat = true;
        var amp = '';

        for (var i = 0; i < srch_array.length; i++) {
            temp_array = srch_array[i].split("=");
            concat = true;
            for (var j = 0; j < vars.length; j++) {
                if (vars[j] == temp_array[0] || temp_array[0].indexOf(vars[j] + '[') != -1) {
                    concat = false;
                    break;
                }
            }
            if (concat == true) {
                start += amp + temp_array[0] + '=' + temp_array[1];
            }
            amp = '&';
        }
    }
    return start;
}

/**
 * TODO: jsdoc this
 */
export const fn_calculate_total_shipping = function (wrapper_id) {
    var $ = Tygh.$;

    wrapper_id = wrapper_id || 'shipping_estimation';
    var parent = $('#' + wrapper_id);

    var radio = $('input[type=radio]:checked', parent);
    var params = [];

    $.each(radio, function (id, elm) {
        params.push({
            name: elm.name,
            value: elm.value
        });
    });

    var url = fn_url('checkout.shipping_estimation.get_total');

    for (var i in params) {
        url += '&' + params[i]['name'] + '=' + encodeURIComponent(params[i]['value']);
    }

    var suffix = parent.find('input[name="suffix"]').first().val();

    var data = {
        additional_id: parent.find('input[name="additional_id"]').first().val()
    }

    $.ceEvent('trigger', 'ce.calculate_total_shipping.before_send_request', [data, parent]);

    $.ceAjax('request', url, {
        result_ids: 'rate_extra_*,shipping_label_*,shipping_estimation_total' + suffix,
        data: data,
        method: 'post'
    });
}
