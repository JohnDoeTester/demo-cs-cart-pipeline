import { params } from './params';
import { state } from './state';

export const nav = {
    _setActive: function (elem) {
        if (elem) {
            state.navActive = elem.data('bpNavItem');
        }
        $(state.bottomPanel).data('navActive', state.navActive);
        nav._setWidth();
        nav._setPosition();
        nav._setClass(elem);
    },

    _getNav: function () {
        $(state.bottomPanel).find(params.navItemSelector).each(function () {
            state.nav.push($(this));
        });
    },

    _setWidth: function () {
        $(params.navActiveSelector).width(
            $(state.bottomPanel).find(params.navItemSpecificSelector.replace('{placeholder}', state.navActive)).outerWidth()
        );
    },

    _setPosition: function () {
        let activeNav = $(state.bottomPanel).find(params.navItemSpecificSelector.replace('{placeholder}', state.navActive));
        let position = $(activeNav).position().left;

        if (Tygh.language_direction === 'rtl' && state.nav.length > 0) {
            position = -Math.ceil($(state.nav[(state.nav.length - 1) - $(activeNav).index()]).position().left);
            if (position != 0) {
                position += $(activeNav).outerWidth() - $(activeNav).width();
            }
        }

        $(params.navActiveSelector).css('transform', 'translate(' + position + 'px)');
    },

    _setClass: function (elem) {
        $(params.navActiveSelector)
            .addClass(params.navActiveActivatedClass);

        if (elem) {
            $(state.nav).each(function () {
                $(this).removeClass(params.navItemActiveClass);
            });
            $(elem).addClass(params.navItemActiveClass);

        }
    },

    _addSetActiveListeners: function () {
        $(Tygh.doc).on('click', params.navItemSelector, function (e) {
            return nav._setActive($(this));
        });
    },
};
