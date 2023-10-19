import { Tygh } from "../..";
import $ from "jquery";

const _ = Tygh;

export const methods = {
    open: function (params) {
        params = params || {};

        const dialog_state = {
            is_opening_allowed: true
        }
        $.ceEvent('trigger', 'ce.dialog.before_open', [dialog_state, params]);
        if (!dialog_state.is_opening_allowed) {
            return false;
        }

        var container = $(this);

        if (!container.length) {
            return false;
        }

        // Focusing on pop-up to prevent the address bar from collapsing in Google Chrome when the page is scrolled down.
        container.attr('tabindex', -1).focus();

        $('html').addClass('dialog-is-open');

        if (!container.hasClass('ui-dialog-content')) { // dialog is not generated yet, init if
            if (container.ceDialog('_load_content', params)) {
                return false;
            }

            container.ceDialog('_init', params);
        } else if (params.view_id && container.data('caViewId') != params.view_id && container.ceDialog('_load_content', params)) {
            return false;
        } else if (container.dialog('isOpen')) {
            container.height('auto');
            container.parent().height('auto');
            methods._resize($(this));
        }

        if ($.browser.msie && params.width == 'auto') {
            params.width = container.dialog('option', 'width');
        }

        if ($(".object-container", container).length == 0) {
            container.wrapInner('<div class="object-container ' + (params.containerClass ? params.containerClass : '') + '" />');
            container.find('.object-container').prepend('<div class="cm-notification-container-dialog notification-container-dialog"/>');
        }

        if (params) {
            container.dialog('option', params);
        }

        $.popupStack.add({
            name: container.prop('id'),
            close: function () {
                try {
                    container.dialog('close');
                } catch (e) {}
            }
        });

        if (_.isTouch == true) {
            // disable autofocus
            $.ui.dialog.prototype._focusTabbable = function () {};
        }

        var res = container.dialog('open');

        if (params.scroll) {
            $.scrollToElm(params.scroll, container);
        }

        return res;
    },

    _is_empty: function () {
        var container = $(this);

        var content = container.html().trim();

        if (content) {
            content = content.replace(/<!--(.*?)-->/g, '');
        }

        if (!content.trim()) {
            return true;
        }

        return false;
    },

    _load_content: function (params) {
        var container = $(this),
            data = {};

        params.href = params.href || '';

        if (params.href && (container.ceDialog('_is_empty') || (params.view_id && container.data('caViewId') != params.view_id))) {
            if (params.view_id) {
                container.data('caViewId', params.view_id);
            }

            if (params.contentRequestForm) {
                let $contentRequestForm = $(document.getElementById(params.contentRequestForm));

                if ($contentRequestForm.length) {
                    data = $contentRequestForm.serializeObject();
                }
            }

            if (params.actionContext) {
                data._action_context = params.actionContext;
            }

            $.ceAjax('request', params.href, {
                full_render: 0,
                result_ids: container.prop('id'),
                skip_result_ids_check: true,
                keep_status_box: true,
                data: data,
                callback: function () {

                    if (!container.ceDialog('_is_empty')) {
                        var images = container.find('img');
                        if (images.length) {
                            images.each(function (index) {
                                var img = new Image();
                                img.src = this.src;
                                img.onload = function () {
                                    if (++index == images.length) {
                                        $.toggleStatusBox('hide');
                                        container.ceDialog('open', params);
                                    }
                                }
                            });
                        } else {
                            $.toggleStatusBox('hide');
                            container.ceDialog('open', params);
                        }

                    } else {
                        // hide status indicator for empty response
                        $.toggleStatusBox('hide');

                        var last_item = $.ceDialog('get_last');
                        if (last_item.length === 0) {
                            $('html').removeClass('dialog-is-open');
                        }
                    }
                }
            });

            return true;
        }

        return false;
    },

    close: function () {
        var container = $(this);
        container.data('close', true);
        container.dialog('close');

        $.popupStack.remove(container.prop('id'));
    },

    reload: function () {
        const $dialog = $(this),
              dialogImgCount = $('img', $dialog).length;

        if (dialogImgCount > 0 && $dialog.dialog('option', 'destroyOnClose') === false) {
            const resizeAndCenterDebounced = $.debounce(methods._resize_and_center, 50);

            $('img', $dialog).on('load', () => {
                resizeAndCenterDebounced($dialog);
            });
        } else {
            methods._resize_and_center($dialog);
        }
    },

    /**
     * Resizes dialog and restores its positioning.
     *
     * @param {JQuery} $dialog Dialog to resize and center
     *
     * @private
     */
    _resize_and_center: function ($dialog) {
        $dialog.ceDialog('resize');
        $dialog.dialog('option', 'position', $dialog.dialog('option', 'position'));
    },

    resize: function () {
        var d = this;
        var container = d.find('.object-container');
        var buttonsElm = methods._get_buttons(d);

        // reset default height
        methods.reset_default_height(container, d, buttonsElm);

        methods._resize($(this));
    },

    change_title: function (title) {
        $(this).dialog('option', 'title', title);
    },

    destroy: function () {
        var id = $(this).prop('id'),
            position = stack.indexOf(id);

        $.popupStack.remove(id);

        if (position != -1) {
            stack.splice(position, 1);
        }

        try {
            $(this).dialog('destroy');
        } catch (e) {}

        var last_item = $.ceDialog('get_last');
        if (last_item.length === 0) {
            $('html').removeClass('dialog-is-open');
        }

        $.ceEvent('trigger', 'ce.dialogdestroy', [$(this)]);
    },

    _get_buttons: function (container) {
        var bts = container.find('.buttons-container');
        var elm = null;

        if (bts.length) {
            var openers = container.find('.cm-dialog-opener');
            if (openers.length) {
                // check buttons not located in other dialogs
                bts.each(function () {
                    var is_dl = false;
                    var bt = $(this);
                    openers.each(function () {
                        // "parents" method with dots in id support
                        function parents(el, selector) {
                            const parents = [];
                            while ((el = el.parentNode) && el !== document) {
                                if (!selector || el.matches(selector)) {
                                    parents.unshift(el)
                                };
                            }
                            return parents;
                        }
                        var dl_id = $(this).data('caTargetId');
                        if ($(parents(bt[0], '#' + dl_id)).length) {
                            is_dl = true;
                            return false;
                        }
                        return true;
                    });
                    if (!is_dl) {
                        elm = bt;
                    }
                    return true;
                });
            } else {
                elm = container.find('.buttons-container:last');
            }
        }

        return elm;
    },

    _init: function (params) {
        params = params || {};
        var container = $(this);
        var offset = 10;
        var max_width = params.max_width || 926;
        var width_border = 120;
        var height_border = 80;
        var zindex = 1099;
        var dialog_class = params.dialogClass || '';

        if ($.matchScreenSize(['xs', 'xs-large', 'sm'])) {
            height_border = 0;
        }

        var ws = $.getWindowSizes();
        var container_parent = container.parent();

        if (params.height !== 'auto' && _.area == "A") {
            params.height = (ws.view_height - height_border);
        }

        if (!container.find('form').length && !container.parents('.object-container').length && !container.data('caKeepInPlace')) {
            params.keepInPlace = true;
        }

        if (!$.ui.dialog.overlayInstances) {
            $.ui.dialog.overlayInstances = 1;
        }

        container.find('script[src]').remove();

        if ($.browser.msie && params.width == 'auto') {
            if ($.browser.version < 8) {
                container.appendTo(_.body);
            }
            params.width = container.outerWidth() + 10;
        }

        if ($.matchScreenSize(['xs', 'xs-large', 'sm'])) {
            params.height = ws.height;
        }

        container.dialog({
            title: params.title || null,
            autoOpen: false,
            draggable: false,
            modal: true,
            width: params.width || (ws.view_width > max_width ? max_width : ws.view_width - width_border),
            height: params.height,
            maxWidth: max_width,
            resizable: false,
            closeOnEscape: false,
            dialogClass: dialog_class,
            destroyOnClose: params.destroyOnClose || false,
            closeText: _.tr('close'),
            appendTo: params.keepInPlace ? container_parent : _.body,
            show: 150,
            hide: 150,

            open: function (e, u) {

                var d = $(this);
                var w = d.dialog('widget');

                // A workaround due to conflict between jQuery and Bootstrap.js: Bootstrap.js does not allow form submitting by pressing Enter if the close buttons do not have the type or dara-dismiss attributes.
                w.find('.ui-dialog-titlebar-close').attr({
                    'data-dismiss': 'modal',
                    'type': 'button'
                });

                // Needed to process HTML code in pop-up headings;
                // that way we can hide parts of pop-up titles on mobile devices.
                var useTemplating = typeof (params.titleFirstChunk) == typeof ("string") &&
                    typeof (params.titleSecondChunk) == typeof ("string") &&
                    typeof (params.titleTemplate) == typeof ("string");

                if (useTemplating) {
                    var dialogTitleString = $.sprintf(params.titleTemplate, [
                        params.titleFirstChunk, params.titleSecondChunk
                    ]);

                    var dialogTitle = w.find('.ui-dialog-title');
                    dialogTitle.html(dialogTitleString);
                }

                var _zindex = zindex;
                if (stack.length) {
                    var prev        = stack.pop(),
                        $prevParent = $(document.getElementById(prev)).parent('.ui-front');

                    stack.push(prev);
                    _zindex = $prevParent.css('z-index') ? $prevParent.css('z-index') : 0;
                }

                w.css('z-index', ++_zindex);
                w.prev().css('z-index', _zindex);

                var elm_id = d.prop('id');
                stack.push(elm_id);
                if (!params.keepInPlace) {
                    if (stackInitedBody.indexOf(elm_id) == -1) {
                        stackInitedBody.push(elm_id);
                    }
                }

                methods._resize(d);

                $('html').addClass('dialog-is-open');

                $.ceEvent('trigger', 'ce.dialogshow', [d, e, u]);

                $('textarea.cm-wysiwyg', d).ceEditor('destroy');
                $('textarea.cm-wysiwyg', d).ceEditor('recover');

                if (params.switch_avail) {
                    d.switchAvailability(false, false);
                }
            },

            beforeClose: function (e, u) {

                var d = $(this);

                var container = d.find('.object-container');
                var non_closable = params.nonClosable || false;
                var buttonsElm = methods._get_buttons(d);

                // reset default height
                methods.reset_default_height(container, d, buttonsElm);

                var ed = $('textarea.cm-wysiwyg', d);
                if (ed.length) {
                    ed.each(function () {
                        $(this).ceEditor('destroy');
                    });
                }

                if (non_closable && !d.data('close')) {
                    return false;
                }

                // treating dialog as opened in 'dialogclose' handlers
                stack.pop();
                if (params.switch_avail) {
                    d.switchAvailability(true, false);
                }

                $.ceEvent('trigger', 'ce.dialogbeforeclose', [d, e, u]);
            },

            close: function (e, u) {
                // Check if the dialog was removed when AJAX-requested.
                if (!$(this).length || !$.contains(document.documentElement, this)) {
                    return;
                }

                if ($(this).dialog('option', 'destroyNestedOnClose')) {
                    $('.cm-dialog-opener', this).each(function () {
                        var $nestedDialog = $(document.getElementById($(this).data('caTargetId')));
                        if (!$nestedDialog.length
                            || !$nestedDialog.data('uiDialog')
                        ) {
                            return;
                        }

                        $nestedDialog.dialog('destroy').remove();
                    });
                }

                if ($(this).dialog('option', 'destroyOnClose')) {
                    $(this).dialog('destroy').remove();
                }
                // dialog is open
                setTimeout(function () {
                    var last_item = $.ceDialog('get_last');
                    if (last_item.length === 0) {
                        $('html').removeClass('dialog-is-open');
                    }

                    if (params.onClose) {
                        params.onClose();
                    }
                }, 50);

                $.ceEvent('trigger', 'ce.dialogclose', [$(this), e, u]);
            }
        });

    },

    _resize: function (d) {

        var buttonsElm = methods._get_buttons(d);
        var optionsElm = d.find('.cm-picker-options-container');
        var container = d.find('.object-container');
        var max_height = $.getWindowSizes().view_height;
        var buttonsHeight = 0;
        var optionsHeight = 0;
        var containerHeight = 0;
        var dialogHeight = d.parent().outerHeight(true);
        var titleHeight = d.parent().find('.ui-dialog-titlebar').outerHeight();

        if (buttonsElm) {
            buttonsElm.addClass('buttons-container-picker');
            // change buttons elm with to prevent height change after changing the position
            buttonsHeight = buttonsElm.outerHeight(true);
        }

        if (optionsElm.length) {
            optionsHeight = optionsElm.outerHeight(true);
        }

        if (dialogHeight > max_height) {
            d.parent().outerHeight(max_height);
        }

        containerHeight = d.parent().outerHeight() - titleHeight;

        if (_.area == "C") {
            if (buttonsElm) {
                if (dialogHeight >= max_height) {
                    containerHeight = containerHeight - buttonsHeight;
                    buttonsElm.css({
                        position: 'absolute',
                        bottom: -buttonsHeight
                    });
                } else {
                    buttonsElm.css({
                        position: 'absolute',
                        bottom: 0
                    });
                }
                container.outerHeight(containerHeight);
            }
            if (dialogHeight > max_height) {
                container.outerHeight(containerHeight);
            }
        } else {
            if (buttonsElm && _.area == "A") {
                containerHeight = containerHeight - buttonsHeight;
                buttonsElm.css({
                    position: 'absolute',
                    bottom: 0,
                    left: 0,
                    right: 0
                });
            }

            if ($.matchScreenSize(['xs', 'xs-large', 'sm'])) {
                containerHeight = d.parent().outerHeight() - titleHeight;
            }

            container.outerHeight(containerHeight);
        }

        if (optionsHeight) {
            optionsElm.positionElm({
                my: 'left top',
                at: 'left bottom',
                of: container,
                collision: 'none'
            });
            optionsElm.css('width', container.outerWidth());
        }
    },

    reset_default_height: function (objectContainer, self, buttonsElm) {
        objectContainer.height('inherit');
        self.parent().height('auto');

        if (buttonsElm) {
            buttonsElm.css({
                position: 'static'
            });
        }
    }
};

var stack = [];
var stackInitedBody = [];

/**
 * Dialog opener
 * @param {JQueryStatic} $
 */
export const ceDialogInit = function ($) {
    $.fn.ceDialog = function (method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods._init.apply(this, arguments);
        } else {
            $.error('ty.dialog: method ' + method + ' does not exist');
        }
    };

    $.ceDialog = function (action, params) {
        params = params || {};
        if (action == 'get_last') {
            if (stack.length == 0) {
                return $();
            }

            var dlg = $(document.getElementById(stack[stack.length - 1]));

            return params.getWidget ? dlg.dialog('widget') : dlg;

        } else if (action == 'fit_elements') {
            var jelm = params.jelm;

            if (jelm.parents('.cm-picker-options-container').length) {
                $.ceDialog('get_last').data('dialog')._trigger('resize');
            }

        } else if (action == 'reload_parent') {
            var jelm = params.jelm;
            var dlg = jelm.closest('.ui-dialog-content');
            var container = $('.object-container', dlg);

            if (!container.length) {
                dlg.wrapInner('<div class="object-container" />');
                dlg.find('.object-container').prepend('<div class="cm-notification-container-dialog notification-container-dialog"/>');
            }

            if (dlg.length && dlg.is(':visible')) {
                var scrollPosition = container.scrollTop();
                dlg.ceDialog('reload');
                container.animate({
                    scrollTop: scrollPosition
                }, 0);
            }

        } else if (action == 'inside_dialog') {

            return (params.jelm.closest('.ui-dialog-content').length != 0);

        } else if (action == 'get_params') {
            if (!params.length) {
                return;
            }

            var dialog_params = {
                keepInPlace: params.hasClass('cm-dialog-keep-in-place'),
                nonClosable: params.hasClass('cm-dialog-non-closable'),
                scroll: params.data('caScroll') ? params.data('caScroll') : '',
                titleTemplate: params.data('caDialogTemplate') || null,
                titleFirstChunk: params.data('caDialogTextFirst') || null,
                titleSecondChunk: params.data('caDialogTextSecond') || null,
                purpose: params.data('caDialogPurpose') || null
            };

            // `title` field should exist when title data-attribute exist too
            if (params.data('caDialogTitle')) {
                dialog_params.title = params.data('caDialogTitle');
            } else {
                dialog_params.title = params.prop('title') || $(document.getElementById(params.data('caTargetId'))).prop('title') || '';
                params.prop('title', dialog_params.title)
            }

            if (params.prop('href')) {
                dialog_params['href'] = params.prop('href');
            }

            if (params.hasClass('cm-dialog-auto-size')) {
                dialog_params['width'] = 'auto';
                dialog_params['height'] = 'auto';
                dialog_params['dialogClass'] = 'dialog-auto-sized';
            } else if (params.hasClass('cm-dialog-auto-width')) {
                dialog_params['width'] = 'auto';
            } else if (params.hasClass('cm-dialog-auto-height')) {
                dialog_params['height'] = 'auto';
            }

            if (params.data('caMaxWidth')) {
                dialog_params['max_width'] = params.data('caMaxWidth');
            }

            if (params.hasClass('cm-dialog-switch-avail')) {
                dialog_params['switch_avail'] = true;
            }

            if (params.hasClass('cm-dialog-destroy-on-close')) {
                dialog_params['destroyOnClose'] = true;
            }

            if (params.hasClass('cm-dialog-destroy-nested-on-close')) {
                dialog_params['destroyNestedOnClose'] = true;
            }

            if ($(document.getElementById(params.data('caTargetId'))).length == 0) {
                // Auto-create dialog container
                var title = params.data('caDialogTitle') ? params.data('caDialogTitle') : params.prop('title');
                $('<div class="hidden" title="' + title + '" id="' + params.data('caTargetId') + '"><!--' + params.data('caTargetId') + '--></div>').appendTo(_.body);
            }

            if (params.prop('href') && params.data('caViewId')) {
                dialog_params['view_id'] = params.data('caViewId');
            }

            if (params.data('caDialogClass')) {
                dialog_params['dialogClass'] = params.data('caDialogClass');
            }

            if (params.data('caDialogContentRequestForm')) {
                dialog_params['contentRequestForm'] = params.data('caDialogContentRequestForm');
            }

            if (params.data('caDialogActionContext')) {
                dialog_params['actionContext'] = params.data('caDialogActionContext');
            }

            return dialog_params;
        } else if (action == 'clear_stack') {
            $.popupStack.clear_stack();
            return stack = [];
        } else if (action == 'destroy_loaded') {
            var content = $('<div>').html(params.content);
            $.each(stackInitedBody, function (i, id) {
                if (content.find(document.getElementById(id)).length) {
                    $(document.getElementById(id)).ceDialog('destroy');
                }
            });
        } else if (action === 'destroy_loaded_post') {
            if ($('.ui-widget-overlay').length === 0) {
                $('html').removeClass('dialog-is-open');
            }
        }
    };

    $.extend({
        popupStack: {
            stack: [],
            add: function (params) {
                return this.stack.push(params);
            },
            remove: function (name) {
                var position = this.stack.indexOf(name);
                if (position != -1) {
                    return this.stack.splice(position, 1);
                }
            },
            last_close: function () {
                var obj = this.stack.pop();
                if (obj && obj.close) {
                    obj.close();
                    return true;
                }
                return false;
            },
            last: function () {
                return this.stack[this.stack.length - 1];
            },
            close: function (name) {
                var position = this.stack.indexOf(name);
                if (position != -1) {
                    var object = this.stack.splice(position, 1)[0];
                    if (object.close) {
                        object.close();
                    }
                    return true;
                }
                return false;
            },
            clear_stack: function () {
                return this.stack = [];
            }
        }
    });
}
