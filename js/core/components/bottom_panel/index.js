import { params } from './params';
import { init } from './init';

export const methods = {
    init: init.init,
    defaults: params,
};

/**
 * Block manager
 * @param {JQueryStatic} $ 
 */

$.fn.ceBottomPanel = function (method) {
    if (methods[method]) {
        return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
    } else if (typeof method === 'object' || !method) {
        return methods.init.apply(this, arguments);
    } else {
        $.error('ty.bottom_panel: method ' + method + ' does not exist');
    }
};

$.ceEvent('one', 'ce.commoninit', function (context) {
    context = $(context || _.doc);
    var bottomPanel = $('[data-ca-bottom-pannel]', context);

    if (bottomPanel.length) {
        bottomPanel.ceBottomPanel();
    }
});