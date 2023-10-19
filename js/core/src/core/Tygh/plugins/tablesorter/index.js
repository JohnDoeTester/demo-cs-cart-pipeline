import { Tygh } from "../..";
import $ from "jquery";

const _ = Tygh;

export const ceTableSorterInit = function ($) {
    $.fn.ceTableSortable = function () {
        if ($('[data-ca-sortable="true"]').length === 0) {
            return;
        }

        $.getScript('js/lib/tablesorter/jquery.tablesorter.combined.min.js', function() {
            $('[data-ca-sortable-column="false"]').data('sorter', false);
            $('[data-ca-sortable="true"]').each(function (i, table) {
                var $table = $(table);
                $table.tablesorter({
                    sortList: $table.data('caSortList') || [[0, 0]],
                    emptyTo: $table.data('caEmptyTo') || 'emptyMin',
                    widgets: ["saveSort"],
                    saveSort: true,
                    widgetOptions: {
                        storage_useSessionStorage: true
                    }
                });
            });
        });
    }
}