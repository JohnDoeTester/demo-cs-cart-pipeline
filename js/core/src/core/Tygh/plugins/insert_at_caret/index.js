import { Tygh } from "../..";

const _ = Tygh;

export const methods = {
    init: function (value) {
        var elm = this.get(0);

        if (document.selection) {
            elm.focus();
            var sel = document.selection.createRange();
            sel.text = value;
            elm.focus();
        } else if (elm.selectionStart || elm.selectionStart == '0') {
            var startPos = elm.selectionStart;
            var endPos = elm.selectionEnd;
            var scrollTop = elm.scrollTop;
            elm.value = elm.value.substring(0, startPos) + value + elm.value.substring(endPos, elm.value.length);
            elm.focus();
            elm.selectionStart = startPos + value.length;
            elm.selectionEnd = startPos + value.length;
            elm.scrollTop = scrollTop;
        } else {
            elm.value += value;
            elm.focus();
        }
    }
};

/**
 * Insert text to cursor position in textarea
 * @param {JQueryStatic} $ 
 */
export const ceInsertAtCaretInit = function ($) {
    $.fn.ceInsertAtCaret = function () {
        return methods.init.apply(this, arguments);
    };
}