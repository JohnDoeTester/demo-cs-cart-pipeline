import { Tygh } from "../..";
import $ from "jquery";

const _ = Tygh;

const methods = {
    init: function (params) {
        params = params || {};

        return this.each(function () {
            let
                $elem = $(this),
                url = params.url || $elem.data('caInlineDialogUrl'),
                actionContext = params.actionContext || $elem.data('caInlineDialogActionContext'),
                data = params.data || $elem.data('caInlineDialogData') || {},
                targetId = $elem.prop('id');

            if (!url) {
                return;
            }

            if ($elem.data('caInlineDialog')) {
                methods._destroy($elem);
            }

            if (actionContext) {
                data._action_context = actionContext;
            }

            $elem.data('caInlineDialog', {
                placeholder: $elem.html()
            });
            $elem.addClass('cm-inline-dialog');

            let promise = $.ceAjax('request', url, {
                full_render: 0,
                result_ids: targetId,
                get_promise: true,
                skip_result_ids_check: true,
                data: data,
                pre_processing: function (responose) {
                    if (responose.html && responose.html[targetId]) {
                        responose.html[targetId] = responose.html[targetId]
                            .replace('<form', '<x-form')
                            .replace('</form', '</x-form')
                    }
                }
            });

            promise.done(function (responose) {
                let forms = [];

                $elem.find('x-form').each(function () {
                    let $form = $('<form>'),
                        $elem = $(this),
                        formId = $elem.attr('id') || targetId + '_' + forms.length;

                    $.each($elem.prop('attributes'), function () {
                        $form.attr(this.name, this.value);
                    });

                    $form.attr('id', formId);
                    $form.addClass('hidden cm-outside-inputs');

                    $(_.body).append($form);

                    $elem.attr('id', formId + '_base');
                    $elem.find(':input').attr('form', formId);

                    forms.push($form);
                    $form.ceFormValidator();
                });

                let data = $elem.data('caInlineDialog');

                data.forms = forms;

                $elem.data('caInlineDialog', data);
            });
            promise.fail(function () {
                methods._destroy($elem);
            });
        });
    },

    destroy: function () {
        methods._destroy(this);
    },

    opener: function () {
        this.on('click', function () {
            let $elem = $(this),
                containerId = $elem.data('caInlineDialogContainer');

            if (!containerId) {
                return;
            }

            let $container = $('#' + containerId);

            $container.ceInlineDialog();

            return false;
        });

        return this;
    },

    closer: function () {
        this.on('click', function () {
            let $elem = $(this),
                container = $elem.data('caInlineDialogContainer'),
                $container = null;

            if (container) {
                $container = $('#' + container);
            } else {
                $container = $elem.closest('.cm-inline-dialog');
            }

            if ($container.length) {
                methods._destroy($container);
                $.ceEvent('trigger', 'ce.inline_dialog.closed', [this]);
                $container.trigger('ce:inline_dialog:closed');
                return false;
            }
        });

        return this;
    },

    _destroy: function ($elem) {
        let data = $elem.data('caInlineDialog');

        if (!data) {
            return;
        }

        $elem.find('.cm-dialog-opener').each(function () {
            let targetId = $(this).data('caTargetId'),
                $dialogContainer = $('#' + targetId);

            if ($dialogContainer.length) {
                $dialogContainer.ceDialog('close');
            }
        });

        $elem.html(data.placeholder);
        $elem.removeData('caInlineDialog');
        $elem.removeClass('cm-inline-dialog');

        if (data.forms && data.forms.length) {
            for (var i in data.forms) {
                data.forms[i].remove();
            }
        }
    },
};

export const ceInlineDialogInit = function ($) {
    $.fn.ceInlineDialog = function (method) {
        if (!method) {
            method = 'init';
        }

        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else {
            $.error('ty.inlineDialog: method ' + method + ' does not exist');
        }
    };
}
