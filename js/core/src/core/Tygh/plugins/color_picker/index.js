import { Tygh } from "../..";
import $ from "jquery";

const _ = Tygh;

export const methods = {
    init: function (params) {
        if (!$(this).length) {
            return false;
        }

        if (!$.fn.spectrum) {
            var elms = $(this);
            $.loadCss(['js/lib/spectrum/spectrum.css'], false, true);
            $.getScript('js/lib/spectrum/spectrum.js', function () {
                elms.ceColorpicker();
            });
            return false;
        }

        var palette = [
            ["#000000", "#434343", "#666666", "#999999", "#b7b7b7", "#cccccc", "#d9d9d9", "#efefef", "#f3f3f3", "#ffffff"],
            ["#980000", "#ff0000", "#ff9900", "#ffff00", "#00ff00", "#00ffff", "#4a86e8", "#0000ff", "#9900ff", "#ff00ff"],
            ["#e6b8af", "#f4cccc", "#fce5cd", "#fff2cc", "#d9ead3", "#d0e0e3", "#c9daf8", "#cfe2f3", "#d9d2e9", "#ead1dc"],
            ["#dd7e6b", "#ea9999", "#f9cb9c", "#ffe599", "#b6d7a8", "#a2c4c9", "#a4c2f4", "#9fc5e8", "#b4a7d6", "#d5a6bd"],
            ["#cc4125", "#e06666", "#f6b26b", "#ffd966", "#93c47d", "#76a5af", "#6d9eeb", "#6fa8dc", "#8e7cc3", "#c27ba0"],
            ["#a61c00", "#cc0000", "#e69138", "#f1c232", "#6aa84f", "#45818e", "#3c78d8", "#3d85c6", "#674ea7", "#a64d79"],
            ["#85200c", "#990000", "#b45f06", "#bf9000", "#38761d", "#134f5c", "#1155cc", "#0b5394", "#351c75", "#741b47"],
            ["#5b0f00", "#660000", "#783f04", "#7f6000", "#274e13", "#0c343d", "#1c4587", "#073763", "#20124d", "#4c1130"]
        ];

        return this.each(function () {
            var jelm = $(this);
            var params = {
                showInput:            jelm.data('caSpectrumShowInput')            ? jelm.data('caSpectrumShowInput')            : true,
                showInitial:          jelm.data('caSpectrumShowInitial')          ? jelm.data('caSpectrumShowInitial')          : false,
                showPalette:          jelm.data('caSpectrumShowPalette')          ? jelm.data('caSpectrumShowPalette')          : false,
                showAlpha:            jelm.data('caSpectrumShowAlpha')            ? jelm.data('caSpectrumShowAlpha')            : false,
                showSelectionPalette: jelm.data('caSpectrumShowSelectionPalette') ? jelm.data('caSpectrumShowSelectionPalette') : false,
                palette:              jelm.data('caSpectrumPalette')              ? JSON.parse(jelm.data('caSpectrumPalette'))  : palette,
                preferredFormat:      jelm.data('caSpectrumPreferredFormat')      ? jelm.data('caSpectrumPreferredFormat')      : 'hex6',
                beforeShow: function () {
                    jelm.spectrum('option', 'showPalette', true);
                    jelm.spectrum('option', 'showInitial', true);
                    jelm.spectrum('option', 'showSelectionPalette', true);
                },
                hide: function () {
                    $.ceEvent('trigger', 'ce.colorpicker.hide');
                },
                show: function () {
                    $.ceEvent('trigger', 'ce.colorpicker.show');
                }

            };

            if (jelm.data('caView') && jelm.data('caView') == 'palette') {
                params.showPaletteOnly = true;
            }

            if (jelm.data('caStorage')) {
                params.localStorageKey = jelm.data('caStorage');
            }

            jelm.spectrum(params);
            jelm.spectrum('container').appendTo(jelm.parent());
        });
    },

    destroy: function () {
        if (!$.fn.spectrum) {
            return;
        }

        this.spectrum('destroy');
    },

    reset: function () {
        if (!$.fn.spectrum) {
            return;
        }

        this.spectrum('set', this.val());
    },

    set: function (val) {
        if (!$.fn.spectrum) {
            return;
        }

        this.spectrum('set', val);
    }
};

/**
 * Color picker
 * @param {JQueryStatic} $ 
 */
export const ceColorpickerInit = function ($) {
    $.fn.ceColorpicker = function (method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        } else {
            $.error('ty.colorpicker: method ' + method + ' does not exist');
        }
    }
}
