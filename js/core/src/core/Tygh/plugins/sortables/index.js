import { Tygh } from "../..";
import $ from "jquery";

const _ = Tygh;

export const methods = {
    init: function (params) {
        return this.each(function () {
            var params = params || {};
            var update_text = _.tr('text_position_updating');
            var self = $(this);

            var table = self.data('caSortableTable');
            var id_name = self.data('caSortableIdName')

            var sortable_params = {
                accept: 'cm-sortable-row',
                items: '.cm-row-item',
                tolerance: 'pointer',
                axis: 'y',
                containment: 'parent',
                opacity: '0.9',
                update: function (event, ui) {
                    var positions = [],
                        ids = [];
                    var container = $(ui.item).closest('.cm-sortable');

                    $('.cm-row-item', container).each(function () { // FIXME: replace with data -attribute
                        var matched = $(this).prop('class').match(/cm-sortable-id-([^\s]+)/i);
                        var index = $(this).index();

                        positions[index] = index;
                        ids[index] = matched[1];
                    });

                    var data_obj = {
                        positions: positions.join(','),
                        ids: ids.join(',')
                    };

                    $.ceAjax('request', fn_url('tools.update_position?table=' + table + '&id_name=' + id_name), {
                        method: 'get',
                        caching: false,
                        message: update_text,
                        data: data_obj
                    });

                    return true;
                }
            };

            // If we have sortable handle, update default params
            if ($('.cm-sortable-handle', self).length) {
                sortable_params = $.extend(sortable_params, {
                    opacity: '0.5',
                    handle: '.cm-sortable-handle'
                });
            }

            self.sortable(sortable_params);
        });
    }
};

/**
 * Sortables
 * @param {JQueryStatic} $ 
 */
export const ceSortableInit = function ($) {
    $.fn.ceSortable = function (method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        } else {
            $.error('ty.sortable: method ' + method + ' does not exist');
        }
    }
}
