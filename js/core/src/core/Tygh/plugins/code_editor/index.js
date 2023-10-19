import { Tygh } from "../..";
import $ from "jquery";

const _ = Tygh;

export const methods = {
    _init: function(self) {

        if (!self.data('codeEditor')) {
            var editor = ace.edit(self.prop('id'));
            editor.session.setUseWrapMode(true);
            editor.session.setWrapLimitRange();
            editor.setFontSize("14px");
            editor.renderer.setShowPrintMargin(false);

            editor.getSession().on('change', function(e) {
                self.addClass('cm-item-modified');
            });

            self.data('codeEditor', editor);
        }

        return $(this);
    },

    init: function(mode) {
        var self = $(this);
        methods._init(self);

        if (mode) {
            self.data('codeEditor').getSession().setMode(mode);
        }

        return $(this);
    },

    set_value: function(val, mode) {
        var self = $(this);
        methods._init(self);

        if(mode == undefined) {
            mode = 'ace/mode/html';
        }

        self.data('codeEditor').getSession().setMode(mode);
        self.data('codeEditor').setValue(val);
        self.data('codeEditor').navigateLineStart();
        self.data('codeEditor').clearSelection();
        self.data('codeEditor').scrollToRow(0);

        return $(this);
    },

    set_show_gutter: function(value) {
        $(this).data('codeEditor').renderer.setShowGutter(value);
    },

    value: function() {
        var self = $(this);
        methods._init(self);

        return self.data('codeEditor').getValue();
    },

    focus: function() {
        var self = $(this);
        var session = self.data('codeEditor').getSession();
        var count = session.getLength();
        self.data('codeEditor').focus();
        self.data('codeEditor').gotoLine(count, session.getLine(count-1).length);
    },

    set_listener: function(event_name, callback) {
        $(this).data('codeEditor').getSession().on(event_name, function(e) {
            callback(e);
        });

        return $(this);
    }
};

/**
 * Code editor
 * @param {JQueryStatic} $ 
 */
export const ceCodeEditorInit = function ($) {
    $.fn.ceCodeEditor = function(method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if ( typeof method === 'object' || ! method ) {
            return methods.init.apply(this, arguments);
        } else {
            $.error('ty.codeeditor: method ' +  method + ' does not exist');
        }
    };
}
