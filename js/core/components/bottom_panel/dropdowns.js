import { params } from './params';
import { state } from './state';

export const dropdowns = {
    _activate: function () {
        $(state.bottomPanel).find(params.dropdownSelector).each(function () {
            state.dropdowns.push($(this).parent());

            $(this).on('click', function () {
                var self = $(this);
                $(state.dropdowns).each(function () {
                    if ($(this)[0] !== self.parent()[0]) {
                        $(this).children('div').removeClass(params.dropdownMenuOpenClass);
                    }
                });

                $(this).parent().children('div').toggleClass(params.dropdownMenuOpenClass);
            });

            $(this).on('focusout', function (e) {
                if (!$(e.relatedTarget).length || !$(e.relatedTarget).hasClass(params.dropdownMenuItemClass)) {
                    $(state.dropdowns).each(function () {
                        $(this).children('div').removeClass(params.dropdownMenuOpenClass);
                    });
                }
            });

            $(Tygh.doc).on('click', '.' + params.dropdownMenuItemClass, function () {
                $(state.dropdowns).each(function () {
                    $(this).children('.' + params.dropdownMenuClass).removeClass(params.dropdownMenuOpenClass);
                });
            });
        });
    },
};
