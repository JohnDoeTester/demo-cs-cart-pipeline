import { params } from './params';
import { init } from './init';
import { api } from './api';

export const methods = {
    init: init.init,
    api: {
        sendRequest: api.sendRequest,
    },
    defaults: params,
};

/**
 * Block manager
 * @param {JQueryStatic} $ 
 */
export const ceBlockManagerInit = function ($) {
    $.fn.ceBlockManager = function (method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        } else {
            $.error('ty.accordion: method ' + method + ' does not exist');
        }
    };

    $.ceBlockManager = function (method, params) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else {
            $.error('ty.notification: method ' + method + ' does not exist');
        }
    }
}
