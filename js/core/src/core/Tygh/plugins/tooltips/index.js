import { Tygh } from "../..";
import $ from "jquery";

const _ = Tygh;

export const methods = {

    init: function (params) {

        var default_params = {
            events: {
                def: 'mouseover, mouseout',
                input: 'focus, blur'
            },
            layout: '<div><span class="tooltip-arrow"></span></div>',
            use_dynamic_plugin: true
        };

        $.extend(default_params, params);

        return this.each(function () {
            var elm = $(this);
            var params = default_params;

            if (elm.data('tooltip')) {
                return false;
            }

            if (elm.data('ceTooltipPosition') === 'top') {
                params.position = 'top left';
                params.tipClass = 'tooltip arrow-top';
                params.offset = [-10, 7];

                if (_.language_direction == 'rtl') {
                    params.offset = [-10, -7];
                    params.position = 'top right';
                }
            } else if (elm.data('ceTooltipPosition') === 'center') {
                params.offset = [10, 7];
                params.tipClass = 'tooltip arrow-down center';
                params.position = 'bottom center';

                if (_.language_direction == 'rtl') {
                    params.offset = [10, -7];
                    params.position = 'bottom center';
                }
            } else if (elm.data('ceTooltipPosition') === 'centerRight') {
                params.offset = [0, 7];
                params.tipClass = 'tooltip arrow-right center';
                params.position = 'center right';

                if (_.language_direction == 'rtl') {
                    params.offset = [0, 7];
                    params.position = 'center right';
                }
            } else if (elm.data('ceTooltipPosition') === 'centerLeft') {
                params.offset = [0, -7];
                params.tipClass = 'tooltip arrow-left center';
                params.position = 'center left';

                if (_.language_direction == 'rtl') {
                    params.offset = [0, -7];
                    params.position = 'center left';
                }
            } else {
                params.offset = [10, 7];
                params.tipClass = 'tooltip arrow-down';
                params.position = 'bottom left';

                if (_.language_direction == 'rtl') {
                    params.offset = [10, -7];
                    params.position = 'bottom right';
                }
            }

            if (elm.data('ceTooltipClass') !== "undefined") {
                params.tipClass += ' ' + elm.data('ceTooltipClass');
            }

            if (elm.data('caTooltipLayoutSelector')) {
                params.layout = $(elm.data('caTooltipLayoutSelector')).html();
            }

            if (elm.data('ceTooltipEventsTooltip')) {
                params.events.tooltip = elm.data('ceTooltipEventsTooltip');
            }

            elm.tooltip(params);

            if (params.use_dynamic_plugin) {
                if (typeof elm.dynamic === 'function') {
                    elm.dynamic({
                        right: {},
                        left: {}
                    });
                }
            }


            // Hide the tooltip before the DOM element removal
            elm.get(0).addEventListener('DOMNodeRemovedFromDocument', function (e) {
                var $target = $(e.target);
                $target.trigger('mouseout');
            }, false);

            elm.on("remove", function () {
                $(this).trigger('mouseout');
            });
        });
    },

    show: function () {
        return this.each(function () {
            var $elm = $(this);

            if (!$elm.data('tooltip')) {
                return false;
            }

            $elm.data('tooltip').show();
        });
    },

    hide: function () {
        return this.each(function () {
            var $elm = $(this);

            if (!$elm.data('tooltip')) {
                return false;
            }

            $elm.data('tooltip').hide();
        });
    }
};

/**
 * Tooltips
 * @param {JQueryStatic} $ 
 */
export const ceTooltipInit = function ($) {
    $.fn.ceTooltip = function (method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        } else {
            $.error('ty.tooltip: method ' + method + ' does not exist');
        }
    }
}
