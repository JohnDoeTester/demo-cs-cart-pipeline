import { params } from './params';
import { state } from './state';

export const bottomButtons = {
    _show: function () {
        $(state.bottomButtonsContainer).addClass(params.bottomButtonsActiveClass);

        $(state.bottomButtons).each(function () {
            $(this).removeClass(params.bottomButtonDisabledClass + ' ' +
                params.bottomButtonDisabledClass + '-' + $(this).data('bpBottomButtons'));
        });
    },

    _hide: function () {
        $(state.bottomButtonsContainer).removeClass(params.bottomButtonsActiveClass);

        $(state.bottomButtons).each(function () {
            $(this).addClass(params.bottomButtonDisabledClass + ' ' +
                params.bottomButtonDisabledClass + '-' + $(this).data('bpBottomButtons'));
        });
    },
};