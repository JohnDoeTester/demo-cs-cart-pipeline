import {Tygh} from '../..';
import $ from "jquery";

const _ = Tygh;

const TOGGLEE_ROLE = 'togglee';
const TOGGLER_ROLE = 'toggler';

export const methods = {
    selectToggler: function($togglees, $toggler) {
        $togglees.prop('checked', false);
        $toggler.prop('checked', true).prop('disabled', true);
    },

    init: function () {
        const $self = $(this),
            role = $self.data('caCheckboxGroupRole'),
            groupId = $self.data('caCheckboxGroup'),
            $togglees = $(`[data-ca-checkbox-group="${groupId}"][data-ca-checkbox-group-role="${TOGGLEE_ROLE}"]`),
            $toggler = $(`[data-ca-checkbox-group="${groupId}"][data-ca-checkbox-group-role="${TOGGLER_ROLE}"]`);

        $self.on('change', function (e) {
            const isChecked = $self.is(':checked');

            let hasActiveTogglees = false;
            $togglees.each((i, elm) => {
                if ($(elm).is(':checked')) {
                    hasActiveTogglees = true;
                }
            });

            if (role === TOGGLER_ROLE && isChecked) {
                methods.selectToggler($togglees, $toggler);
            } else if (role === TOGGLEE_ROLE && isChecked) {
                $toggler.prop('checked', false).prop('disabled', false);
            } else if (role === TOGGLEE_ROLE && !hasActiveTogglees) {
                methods.selectToggler($togglees, $toggler);
            }
        });
    }
};

export const ceCheckboxGroupInit = function ($) {
    $.fn.ceCheckboxGroup = function (method) {
        let args = arguments;

        return $(this).each(function (i, elm) {
            if (methods[method]) {
                return methods[method].apply(this, Array.prototype.slice.call(args, 1));
            } else if (typeof method === 'object' || !method) {
                return methods.init.apply(this, args);
            } else {
                $.error('ty.checkboxGroup: method ' + method + ' does not exist');
            }
        });
    };
};
