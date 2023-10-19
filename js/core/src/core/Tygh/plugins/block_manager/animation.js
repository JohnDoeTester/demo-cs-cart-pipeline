import { params } from './params';

export const animation = {
    up: function () {
        params._hover_element.addClass(params.block_got_up_class);

        setTimeout(function () {
            params._hover_element.removeClass(params.block_got_up_class);

            params._hover_element.trigger('block_manager:animation_complete', 'up');
        }, 300);
    },

    down: function () {
        params._hover_element.addClass(params.block_got_down_class);

        setTimeout(function () {
            params._hover_element.removeClass(params.block_got_down_class);

            params._hover_element.trigger('block_manager:animation_complete', 'down');
        }, 300);
    },
};
