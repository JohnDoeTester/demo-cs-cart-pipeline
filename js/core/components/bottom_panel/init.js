import { params } from './params';
import { state } from './state';
import { bottomPanel } from './bottom_panel';
import { nav } from './nav';
import { modes } from './modes';
import { dropdowns } from './dropdowns';

let isInit;

export const init = {
    init: function () {
        if (isInit) {
            return;
        }

        state.html = $(params.htmlSelector);
        state.bottomPanel = $(params.bottomPanelSelector);
        state.bottomButtonsContainer = $(params.bottomButtonsContainerSelector);
        state.mode = state.bottomPanel.data('bpMode');
        state.isBottomPanelOpen = state.bottomPanel.data('bpIsBottomPanelOpen');
        state.navActive = state.bottomPanel.data('bpNavActive');
        state.modesActive = state.bottomPanel.data('bpModesActive');
        state.bottomButtons = state.bottomButtonsContainer.find(params.bottomButtonsSelector);
        state.dropdowns = [];
        state.modes = [];

        bottomPanel._getCookie();
        bottomPanel._addActivateListeners();
        bottomPanel._addDeactivateListeners();
        nav._getNav();
        nav._setActive();
        nav._addSetActiveListeners();
        dropdowns._activate();

        if ($(state.bottomPanel).find(params.modesItemSelector).length) {
            modes._getButtons();
            modes._setActive();
            modes._addSetActiveListeners();
        }

        isInit = true;
    }
};
