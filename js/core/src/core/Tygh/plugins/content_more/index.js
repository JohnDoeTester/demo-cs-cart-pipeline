import { Tygh } from "../..";

const _ = Tygh;

export const methods = {
    init: function () {
        methods.setMoreVisibility($(this));
        methods.bindEvents();
    },

    showFullText: function ($contentMoreBtn) {
        methods._getElement('contentMoreBtnWrapper', $contentMoreBtn)
            .addClass('hidden');

        methods._getElement('contentMoreText', $contentMoreBtn)
            .addClass('cs-content-more__text--full');
    },

    setMoreVisibility: function ($contentMore) {
        methods._getElement('contentMoreText', $contentMore).each(function () {
            const $contentMoreText = $(this);

            methods._getElement('contentMoreBtnWrapper', $contentMoreText)
                .toggleClass('hidden',
                    Math.round($contentMoreText.height()) >= $contentMoreText[0].scrollHeight
                );
        });
    },

    _getElement: function (elem, $target) {
        return $target.closest('[data-ca-elem="contentMore"]')
            .find(`[data-ca-elem="${elem}"]`);
    },

    bindEvents: function () {
        $(_.doc).on('click', '[data-ca-elem="contentMoreBtn"]', function () {
            methods.showFullText($(this));
        });
    },
};

/**
 * Collapse the content and add an More button
 * @param {JQueryStatic} $
 */
export const ceContentMoreInit = function ($) {
    $.fn.ceContentMore = function (method) {
        return $(this).each(function (i, elm) {
            let methodCallback = methods[method] || methods.init;
            methodCallback.apply(this, Array.prototype.slice.call(arguments, 1));
        });
    };
}
