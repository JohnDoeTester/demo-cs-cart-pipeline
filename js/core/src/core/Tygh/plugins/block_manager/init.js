import { Tygh } from '../..';
import { params } from './params';
import { actions } from './actions';
import { sortable } from './sortable';
import $ from "jquery";

let isInit;

export const init = {
    init: function () {
        this.each(function() {
            const $blockManager = $(this);
            
            if ($blockManager.data('caBlockManagerIsInit') || !$blockManager.length) {
                return;
            }
            if (typeof $blockManager.data('caBlockManagerHasLayout') !== 'undefined') {
                params._hasLayout = !!$blockManager.data('caBlockManagerHasLayout');
            }
            if (!params._hasLayout) {
                params._isEnabledMoveBetweenGrids = false;
            }

            sortable._sortable();

            $(params.block_selector).each(function () {
                actions._setMenuPosition($(this));
            });

            $blockManager.data('caBlockManagerIsInit', true);
        });

        if (isInit) {
            return;
        }

        $.ceEvent('on', 'ce.commoninit', function (context) {
            const $blockManager = $('[data-ca-block-manager="main"]', context);
            if (!$blockManager.length) {
                return;
            }
            $blockManager.ceBlockManager();
        });

        $(Tygh.doc).on('click', params.action_selector, function (e) {
            params._self = $(this);
            var jelm = params._self.parents(params.menu_selector).parent().parent();

            params._hover_element = jelm;
            params._blockManager = jelm.closest('[data-ca-block-manager="main"]');
            var action = params._self.data('caBlockManagerAction');

            return actions._executeAction(action);
        });

        $(Tygh.doc).on('block_manager:animation_complete', function (event) {
            actions._setMenuPosition($(event.target));
        });

        isInit = true;
    }
};
