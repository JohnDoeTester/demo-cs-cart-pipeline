import { Tygh } from "../..";
import $ from "jquery";

const _ = Tygh;

export const methods = {
    display: function() {
        $.cePreviewer('display', this);
    }
};

/**
 * Previewer methods
 * @param {JQueryStatic} $ 
 */
export const cePreviewerInit = function ($) {

    $.fn.cePreviewer = function(method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if ( typeof method === 'object' || ! method ) {
            return methods.run.apply(this, arguments);
        } else {
            $.error('ty.previewer: method ' +  method + ' does not exist');
        }
    };

    $.cePreviewer = function(action, data) {
        if (action == 'handlers') {
            this.handlers = data;
        } else if (action == 'display') {
            return this.handlers[action](data);
        }
    }

}
