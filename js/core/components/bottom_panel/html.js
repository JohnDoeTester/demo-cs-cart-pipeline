import { params } from './params';
import { state } from './state';

export const html = {
    _activatePanel: function () {
        state.html.addClass(params.htmlActiveClass);
    },

    _deactivatePanel: function () {
        state.html.removeClass(params.htmlActiveClass);
    },
};
