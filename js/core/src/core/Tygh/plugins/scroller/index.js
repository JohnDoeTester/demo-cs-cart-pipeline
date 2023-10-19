import { Tygh } from "../..";
import $ from "jquery";

const _ = Tygh;

export const methods = {
    in_out_callback: function(carousel, item, i, state, evt) {
        if (carousel.allow_in_out_callback) {
            if (carousel.options.autoDirection == 'next') {
                carousel.add(i + carousel.options.item_count, $(item).html());
                carousel.remove(i);
            } else {
                var last_item = $('li:last', carousel.list);
                carousel.add(last_item.data('caJcarouselindex') - carousel.options.item_count, last_item.html());
                carousel.remove(last_item.data('caJcarouselindex'));
            }
        }
    },

    next_callback: function(carousel, item, i, state, evt) {
        if (state == 'next') {
            carousel.add(i + carousel.options.item_count, $(item).html());
            carousel.remove(i);
        }
    },

    prev_callback: function(carousel, item, i, state, evt) {
        if (state == 'prev') {
            var last_item = $('li:last', carousel.list);
            var item = last_item.html();
            var count = last_item.data('caJcarouselindex') - carousel.options.item_count;
            carousel.remove(last_item.data('caJcarouselindex'));
            carousel.add(count, item);
        }
    },

    init_callback: function(carousel, state) {
        if (carousel.options.autoDirection == 'prev') {
            // switch buttons to save the buttons scroll direction
            var tmp = carousel.buttonNext;
            carousel.buttonNext = carousel.buttonPrev;
            carousel.buttonPrev = tmp;
        }
        $('.jcarousel-clip', carousel.container).height(carousel.options.clip_height + 'px');
        $('.jcarousel-clip', carousel.container).width(carousel.options.clip_width + 'px');

        var container_width = carousel.options.clip_width;
        carousel.container.width(container_width);
        if (container_width > carousel.container.width()) {
            var p = carousel.pos(carousel.options.start, true);
            carousel.animate(p, false);
        }

        carousel.clip.hover(function() {
            carousel.stopAuto();
        }, function() {
            carousel.startAuto();
        });

        if (!$.browser.msie || $.browser.version > 8) {
            $(window).on('beforeunload', function() {
                carousel.allow_in_out_callback = false;
            });
        }

        if ($.browser.chrome) {
            $.jcarousel.windowLoaded();
        }
    }
};

/**
 * Scroller
 * FIXME: Backward compability
 * @param {JQueryStatic} $ 
 */
export const ceScrollerInit = function ($) {
    $.ceScrollerMethods = methods;
}
