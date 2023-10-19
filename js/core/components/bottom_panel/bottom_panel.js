import { params } from './params';
import { state } from './state';
import { html } from './html';
import { bottomButtons } from './bottom_buttons';

export const bottomPanel = {
    _activate: function () {
        state.isBottomPanelOpen = true;
        html._activatePanel();
        bottomButtons._hide();
        bottomPanel._setOpenCookie(true);
    },

    _deactivate: function () {
        state.isBottomPanelOpen = false;
        html._deactivatePanel();
        bottomButtons._show();
        bottomPanel._setOpenCookie(false);
    },

    _setOpenCookie: function (isOpen) {
        $.cookie.set('pb_is_bottom_panel_open', +isOpen);
    },

    _getCookie: function () {
        var bottomPanelOpenCookie = $.cookie.get('pb_is_bottom_panel_open');
        if (!!bottomPanelOpenCookie) {
            state.isBottomPanelOpen  = bottomPanelOpenCookie;
        } else {
            state.isBottomPanelOpen = true;
        }
    },

    _addActivateListeners: function () {
        $(Tygh.doc).on('click', params.onBottomPanelSelector, function () {
            return bottomPanel._activate();
        });
    },

    _addDeactivateListeners: function () {
        $(Tygh.doc).on('click', params.offBottomPanelSelector, function () {
            return bottomPanel._deactivate();
        });
    },
};
