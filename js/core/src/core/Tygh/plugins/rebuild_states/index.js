import { Tygh } from "../..";
import $ from "jquery";

const _ = Tygh;

var options = {};
var init = false;

function _rebuildStates(section, elm) {
    elm = elm || $('.cm-state.cm-location-' + section).prop('id');

    const $sbox           = $('select#' + elm).length > 0 ? $('select#' + elm) : $(`select#${elm}_d`),
          $inp            = $('input#' + elm).length > 0 ? $('input#' + elm) : $(`input#${elm}_d`),
          $country        = $('.cm-country.cm-location-' + section).last(),
          defaultState    = $inp.val(),
          countryCode     = $country.length ? $country.val() : options.default_country,
          countryDisabled = $country.length ? $country.prop('disabled') : $sbox.prop('disabled'),
          isFocusStates   = $sbox.is(":focus") || $inp.is(":focus"),
          isFocusCountry  = $country.is(":focus");

    $sbox.prop('id', elm).prop('disabled', false).removeClass('hidden cm-skip-avail-switch');
    $inp.prop('id', elm + '_d').prop('disabled', true).addClass('hidden cm-skip-avail-switch');

    if (isFocusCountry) {
        $inp.val('');
    }

    if (!$inp.hasClass('disabled')) {
        $sbox.removeClass('disabled');
    }

    if (options.states && options.states[countryCode]) { // Populate selectbox with states
        $sbox.find('option').each(function(){
            const $option = $(this);

            if ($option.val() && $option.data('caRebuildStates') !== 'skip') {
                $option.remove();
            }
        });

        for (let i = 0; i < options.states[countryCode].length; i++) {
            $sbox.append(`<option value="${options.states[countryCode][i]['code']}" 
                            ${(options.states[countryCode][i]['code'] == defaultState ? ' selected' : '')}>
                            ${options.states[countryCode][i]['state']}
                         </option>`
            );
        }

        $sbox.prop('id', elm).prop('disabled', false).removeClass('cm-skip-avail-switch');
        $inp.prop('id', elm + '_d').prop('disabled', true).addClass('cm-skip-avail-switch').val('');

        if (isFocusStates) {
            $sbox.focus();
        }

        if (!$inp.hasClass('disabled')) {
            $sbox.removeClass('disabled');
        }
    } else { // Disable states
        $sbox.prop('id', elm + '_d').prop('disabled', true).addClass('hidden cm-skip-avail-switch');
        $inp.prop('id', elm).prop('disabled', false).removeClass('hidden cm-skip-avail-switch');

        if (isFocusStates) {
            $inp.focus();
        }

        if (!$sbox.hasClass('disabled')) {
            $inp.removeClass('disabled');
        }
    }

    if (countryDisabled === true) {
        $sbox.prop('disabled', true);
        $inp.prop('disabled', true);
    }

    $.ceEvent('trigger', 'ce.rebuild_states');
}

function _rebuildStatesInLocation() {
    const location_elm = $(this).prop('class').match(/cm-location-([^\s]+)/i);

    if (location_elm) {
        _rebuildStates(location_elm[1], $('.cm-state.cm-location-' + location_elm[1]).not(':disabled').last().prop('id'));
    }
}

export const methods = {
    init: function () {
        if ($(this).hasClass('cm-country')) {
            if (init == false) {
                $(_.doc).on('change', 'select.cm-country', _rebuildStatesInLocation);
                init = true;
            }
            $(this).trigger('change', {
                is_triggered_by_user: false
            });
        } else {
            _rebuildStatesInLocation.call(this);
        }
    }
}

/**
 * States field builder
 * @param {JQueryStatic} $ 
 */
export const ceRebuildStatesInit = function ($) {
    $.fn.ceRebuildStates = function (method) {
        const args = arguments;

        return $(this).each(function (i, elm) {
            if (methods[method]) {
                return methods[method].apply(this, Array.prototype.slice.call(args, 1));
            } else if (typeof method === 'object' || !method) {
                return methods.init.apply(this, args);
            } else {
                $.error('ty.rebuildstates: method ' + method + ' does not exist');
            }
        });
    };

    $.ceRebuildStates = function (action, params) {
        params = params || {};
        if (action == 'init') {
            options = params;
        }
    }
}
