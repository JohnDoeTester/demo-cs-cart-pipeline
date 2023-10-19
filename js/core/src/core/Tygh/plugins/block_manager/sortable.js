import { params } from './params';
import { actions } from './actions';
import { api } from './api';
import $ from "jquery";

export const sortable = {
    _sortable: function () {
        var sortable_params = {
            items: params.sortable_items_selector,
            update: function (event, ui) {
                const $elem = $(ui.item);
                params._self = $elem;
                params._hover_element = $elem;
                params._blockManager = $elem.closest('[data-ca-block-manager="main"]');

                api.sendRequest('move', actions._snapBlocksData($elem));
            }
        };

        if (params._hasLayout) {
            sortable_params.connectWith = params.blocks_place_selector;
        }

        $.extend(params, sortable_params);

        $(params.blocks_place_selector).sortable(params);
    }
};
