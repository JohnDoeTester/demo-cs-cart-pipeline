import $ from 'jquery';

import {
    fn_print_r,
    fn_alert,
    fn_print_array,
    fn_url,
    fn_strip_tags,
    fn_reload_form,
    fn_get_listed_lang,
    fn_query_remove,
    fn_calculate_total_shipping
} from "./core/utils";

import { Tygh } from "./core/Tygh";
import { namespaceInitializer } from "./core/initializer";

// FIXME: Remove this from proto of std object
String.prototype.str_replace = function(src, dst) {
    return this.toString().split(src).join(dst);
};

if (!String.prototype.trim) {
    String.prototype.trim = function () {
        return this.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, '');
    };
}

(function () {
    // Register core namespace in global scope
    window.Tygh = Tygh;

    // Register utils functions in global scope
    window.fn_print_r = fn_print_r;
    window.fn_alert = fn_alert;
    window.fn_print_array = fn_print_array;
    window.fn_url = fn_url;
    window.fn_strip_tags = fn_strip_tags;
    window.fn_reload_form = fn_reload_form;
    window.fn_get_listed_lang = fn_get_listed_lang;
    window.fn_query_remove = fn_query_remove;
    window.fn_calculate_total_shipping = fn_calculate_total_shipping;

    // Initialize namespace
    namespaceInitializer(Tygh, $);
})();