import { Tygh } from "../..";
import $ from "jquery";

const _ = Tygh;

export var handlers = {};
export var state = 'not-loaded';
export var pool = [];

export const methods = {
    run: function (params) {

        if (!this.length) {
            return false;
        }

        if ($.ceEditor('state') == 'loading') {
            $.ceEditor('push', this);
        } else {
            $.ceEditor('run', this, params);
        }
    },

    destroy: function () {

        if (!this.length || $.ceEditor('state') != 'loaded') {
            return false;
        }

        $.ceEditor('destroy', this);
    },

    recover: function () {

        if (!this.length || $.ceEditor('state') != 'loaded') {
            return false;
        }

        $.ceEditor('recover', this);
    },

    val: function (value) {

        if (!this.length) {
            return false;
        }

        return $.ceEditor('val', this, value);
    },

    disable: function (value) {

        if (!this.length || $.ceEditor('state') != 'loaded') {
            return false;
        }

        $.ceEditor('disable', this, value);
    },

    change: function (callback) {
        var onchange = this.data('ceeditor_onchange') || [];
        onchange.push(callback);
        this.data('ceeditor_onchange', onchange);
    },

    changed: function (html) {
        var onchange = this.data('ceeditor_onchange') || [];
        for (var i = 0; i < onchange.length; i++) {
            onchange[i](html);
        }
    },

    insert: function (text) {
        if (!this.length) {
            return false;
        }

        return $.ceEditor('insert', this, text);
    }
};

/**
 * WYSIWYG opener
 * @param {JQueryStatic} $ 
 */
export const ceEditorInit = function ($) {

    $.fn.ceEditor = function (method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.run.apply(this, arguments);
        } else {
            $.error('ty.editor: method ' + method + ' does not exist');
        }
    };

    $.ceEditor = function (action, data, params) {
        if (action == 'push') {
            if (data) {
                pool.push(data);
            } else {
                return pool.unshift();
            }
        } else if (action == 'state') {
            if (data) {
                state = data;

                if (data == 'loaded' && pool.length) {
                    for (var i = 0; i < pool.length; i++) {
                        pool[i].ceEditor('run', params);
                    }
                    pool = [];
                }
            } else {
                return state;
            }
        } else if (action == 'handlers') {
            handlers = data;
        } else if (action == 'run' || action == 'destroy' || action == 'updateTextFields' || action == 'recover' || action == 'val' || action == 'disable' || action == 'insert') {
            return handlers[action](data, params);
        }
    }

}
