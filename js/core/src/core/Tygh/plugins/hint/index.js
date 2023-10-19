import { Tygh } from "../..";
import $ from "jquery";

const _ = Tygh;

export const methods = {
    init: function() {
        return this.each(function() {
            var elm = $(this);
            elm.bind ({
                click: function() {
                    $(this).ceHint('_check_hint');
                },
                focus: function() {
                    $(this).ceHint('_check_hint');
                },
                focusin: function() {
                    $(this).ceHint('_check_hint');
                },
                blur: function() {
                    $(this).ceHint('_check_hint_focused');
                },
                focusout: function() {
                    $(this).ceHint('_check_hint_focused');
                }
            });
            elm.addClass('cm-hint-focused');
            elm.removeClass('cm-hint');
            elm.ceHint('_check_hint_focused');
        });
    },

    is_hint: function() {
        return $(this).hasClass('cm-hint') && ($(this).val() == $(this).ceHint('_get_hint_value'));
    },

    _check_hint: function() {
        var elm = $(this);
        if (elm.ceHint('is_hint')) {
            elm.addClass('cm-hint-focused');
            elm.val('');
            elm.removeClass('cm-hint');
            elm.prop('name', elm.prop('name').str_replace('hint_', ''));
        }
    },

    _check_hint_focused: function() {
        var elm = $(this);
        if (elm.hasClass('cm-hint-focused')) {
            if (elm.val() == '' || (elm.val() == elm.ceHint('_get_hint_value'))) {
                elm.addClass('cm-hint');
                elm.removeClass('cm-hint-focused');
                elm.val(elm.ceHint('_get_hint_value'));
                elm.prop('name', 'hint_' + elm.prop('name'));
            }
        }
    },

    _get_hint_value: function() {
        return ($(this).prop('title') != '') ? $(this).prop('title') : $(this).prop('defaultValue');
    }

};


/**
 * Hint methods
 * @param {JQueryStatic} $ 
 */
export const ceHintInit = function ($) {
    $.fn.ceHint = function(method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if ( typeof method === 'object' || ! method ) {
            return methods.run.apply(this, arguments);
        } else {
            $.error('ty.hint: method ' +  method + ' does not exist');
        }
    }
}
