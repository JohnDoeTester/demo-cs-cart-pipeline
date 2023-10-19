import { Tygh } from "../..";
import $ from "jquery";

const _ = Tygh;

export const methods = {
    init: function () {
        var $self = $(this);

        if (!$self.length) {
            return $self;
        }

        if ($.fn.bootstrapSwitch) {
            $self.bootstrapSwitch();
        } else {
            $.getScript('js/lib/bootstrap_switch/js/bootstrapSwitch.js', function () {
                $self.bootstrapSwitch();
            });
        }
        return $self;
    },

    isActive: function () {
        return $(this).bootstrapSwitch('isActive');
    },

    setActive: function (active) {
        $(this).bootstrapSwitch('setActive', active);
        $(this).find('input').prop('disabled', !active);
    },

    getStatus: function () {
        return $(this).bootstrapSwitch('status');
    }
};

/**
 * Switch Checkbox
 * @param {JQueryStatic} $ 
 */
export const ceSwitchCheckboxInit = function ($) {
    $.fn.ceSwitchCheckbox = function (method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        } else {
            $.error('ty.switchcheckbox: method ' + method + ' does not exist');
        }
    }
}
