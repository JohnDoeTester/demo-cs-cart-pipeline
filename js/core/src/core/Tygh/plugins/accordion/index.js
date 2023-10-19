import { Tygh } from "../..";
import $ from "jquery";

const _ = Tygh;

export const methods = {
    init: function (params) {
        params = params || {};
        params.heightStyle = params.heightStyle || "content";
        params.animate = params.animate || $(_.body).data('caAccordionAnimateDelay') || 300;

        var container = $(this);
        container.accordion(params);
    },

    reinit: function (params) {
        $(this).accordion(params);
    }
};

/**
 * Accordion
 * @param {JQueryStatic} $ 
 */
export const ceAccordionInit = function ($) {
    $.fn.ceAccordion = function (method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        } else {
            $.error('ty.accordion: method ' + method + ' does not exist');
        }
    };

    $.ceAccordion = function (method, params) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else {
            $.error('ty.notification: method ' + method + ' does not exist');
        }
    }
}
