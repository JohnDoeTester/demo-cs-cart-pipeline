import { Tygh } from "../..";
import $ from "jquery";

const _ = Tygh;

var els = [],
    els_params = {},
    $window = $(window);

export const methods = {
    init: function(params) {
        // initialize for browsers, that does not support `position: sticky`
        var notSupport = false;
            notSupport = notSupport || (($.browser.edge && (+$.browser.version) < 17.17134) || false);
            notSupport = notSupport || (($.browser.msie) || false);
            notSupport = notSupport || (($.browser.chrome && (+$.browser.version.split('.')[0]) < 63) || false);

        // browser doesnt support
        if (notSupport) {
            $('body').toggleClass('sticky-no-support');
            return; 
        }

        return this.each(function() {
            var $self   = $(this),
                screens = $self.data('caStickOnScreens') ? 
                          $self.data('caStickOnScreens').split(',') : 
                          undefined;

            $self.css({
                position: 'sticky',
                top: $self.data('caTop') || 0
            });

            // FIXME: Remove this fix, when safari drop support position: sticky with prefix
            if ($.browser.safari) {
                $self.css({
                    position: '-webkit-sticky'
                });
            }
        });
    }
};

/**
 * Sticky scroll
 * @param {JQueryStatic} $ 
 */
export const ceStickyScrollInit = function ($) {
    $.fn.ceStickyScroll = function (method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        } else {
            $.error('ty.stickyScroll: method ' + method + ' does not exist');
        }
    };
}
