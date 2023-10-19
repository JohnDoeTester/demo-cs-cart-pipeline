import $ from "jquery";
import { Tygh } from ".";
const _ = Tygh;

/*
 * Add browser detection
 * It's deprecated since jQuery 1.9, but a lot of code still use this
 */
(function($){
    var ua = navigator.userAgent.toLowerCase();
    var match = /(edge)[ \/]([\w.]+)/.exec( ua ) ||
                /(chrome)[ \/]([\w.]+)/.exec( ua ) ||
                /(webkit)[ \/]([\w.]+)/.exec( ua ) ||
                /(opera)(?:.*version|)[ \/]([\w.]+)/.exec( ua ) ||
                /(msie) ([\w.]+)/.exec( ua ) ||
                (/(trident\/7.0;)/.exec( ua ) ? [null, 'msie', '11'] : undefined) ||
                ua.indexOf("compatible") < 0 && /(mozilla)(?:.*? rv:([\w.]+)|)/.exec( ua ) ||
                [];
    var matched = {
        browser: match[ 1 ] || "",
        version: match[ 2 ] || "0"
    };

    var browser = {};

    if ( matched.browser ) {
        browser[ matched.browser ] = true;
        browser.version = matched.version;
    }

    // Chrome is Webkit, but Webkit is also Safari.
    if ( browser.chrome ) {
        browser.webkit = true;
    } else if ( browser.webkit ) {
        browser.safari = true;
    }

    $.browser = browser;
})($);

export var lastClickedElement = null;

export const getWindowSizes = function()
{
    var iebody = (document.compatMode && document.compatMode != 'BackCompat') ? document.documentElement : document.body;
    return {
        'offset_x'   : iebody.scrollLeft ? iebody.scrollLeft : (self.pageXOffset ? self.pageXOffset : 0),
        'offset_y'   : iebody.scrollTop  ? iebody.scrollTop : (self.pageYOffset ? self.pageYOffset : 0),
        'view_height': self.innerHeight ? self.innerHeight : iebody.clientHeight,
        'view_width' : self.innerWidth ? self.innerWidth : iebody.clientWidth,
        'height'     : iebody.scrollHeight ? iebody.scrollHeight : window.height,
        'width'      : iebody.scrollWidth ? iebody.scrollWidth : window.width
    };
}

export const disable_elms = function(ids, flag)
{
    $('#' + ids.join(',#')).prop('disabled', flag);
}

export const ua = {
    version: (navigator.userAgent.toLowerCase().indexOf("chrome") >= 0) ? (navigator.userAgent.match(/.+(?:chrome)[\/: ]([\d.]+)/i) || [])[1] : ((navigator.userAgent.toLowerCase().indexOf("msie") >= 0)? (navigator.userAgent.match(/.*?msie[\/:\ ]([\d.]+)/i) || [])[1] : (navigator.userAgent.match(/.+(?:it|pera|irefox|ersion)[\/: ]([\d.]+)/i) || [])[1]),
    browser: (navigator.userAgent.toLowerCase().indexOf("chrome") >= 0) ? 'Chrome' : ($.browser.safari ? 'Safari' : ($.browser.opera ? 'Opera' : ($.browser.msie ? 'Internet Explorer' : 'Firefox'))),
    os: (navigator.platform.toLowerCase().indexOf('mac') != -1 ? 'MacOS' : (navigator.platform.toLowerCase().indexOf('win') != -1 ? 'Windows' : 'Linux')),
    language: (navigator.language ? navigator.language : (navigator.browserLanguage ? navigator.browserLanguage : (navigator.userLanguage ? navigator.userLanguage : (navigator.systemLanguage ? navigator.systemLanguage : ''))))
}

export const is = {
    email: function(email) {
        return /^([^@\s]+<?)([^@\s]*)@(([^@\.\s]+?)|([^@\s]+?\.[^@\s]+?))(>?)$/i.test(email) ? true : false;
    },

    blank: function(val) {

        if(($.isArray(val) && val.length == 0) || $.type(val) === 'null' || ("" + val).replace(/[\n\r\t]/gi, '') == '') {
            return true;
        }

        return false;
    },

    integer: function(val) {
        return (/^[0-9]+$/.test(val) && !$.is.blank(val)) ? true : false;
    },

    rgbColor: function (val) {
        return (/^(rgb)\((\d*)(,|, *)(\d*)(,|, *)(\d*)\)$/.test(val));
    },

    rgbaColor: function (val) {
        return (/^(rgba)\((\d*)(,|, *)(\d*)(,|, *)(\d*)(,|, *)(\d*|\d.\d*)\)$/.test(val));
    },

    hex6Color: function (val) {
        return (/^\#[0-9a-fA-F]{6}$/.test(val));
    },

    color: function(val) {
        return ($.is.rgbColor(val) || $.is.rgbaColor(val) || $.is.hex6Color(val));
    },

    phone: function(val) {
        var regexp = /^[\s()+-]*([0-9][\s()+-]*){6,20}$/;

        return (regexp.test(val) && val.length) ? true: false;
    }
}

export const cookie = {
    get: function(name)
    {
        var arg = name + "=";
        var alen = arg.length;
        var clen = document.cookie.length;
        var i = 0;
        while (i < clen) {
            var j = i + alen;
            if (document.cookie.substring(i, j) == arg) {
                var endstr = document.cookie.indexOf (";", j);
                if (endstr == -1) {
                    endstr = document.cookie.length;
                }

                return decodeURI(document.cookie.substring(j, endstr));
            }

            i = document.cookie.indexOf(" ", i) + 1;
            if (i == 0) {
                break;
            }
        }
        return null;
    },

    set: function(name, value, expires, path, domain, secure)
    {
        document.cookie = name + "=" + encodeURIComponent(value) + ((expires) ? "; expires=" + expires.toGMTString() : "") + ((path) ? "; path=" + path : "") + ((domain) ? "; domain=" + domain : "") + ((secure) ? "; secure" : "");
    },

    remove: function(name, path, domain)
    {
        if ($.cookie.get(name)) {
            document.cookie = name + "=" + ((path) ? "; path=" + path : "") + ((domain) ? "; domain=" + domain : "") + "; expires=Thu, 01-Jan-70 00:00:01 GMT";
        }
    }
}

export const redirect = function(url, replace)
{
    replace = replace || false;

    if ($('base').length && url.indexOf('/') != 0 && url.indexOf('http') !== 0) {
        url = $('base').prop('href') + url;
    }

    if (_.embedded) {
        $.ceAjax('request', url, {result_ids: _.container});
    } else {
        if (replace) {
            window.location.replace(url);
        } else {
            window.location.href = url;
        }
    }
}

export const dispatchEvent = function(e)
{
    var jelm = $(e.target);
    var elm = e.target;
    var s;
    e.which = e.which || 1;

    if ((e.type == 'click' || e.type == 'mousedown') && $.browser.mozilla && e.which != 1) {
        return true;
    }

    var processed = {
        status: false,
        to_return: true
    };
    $.ceEvent('trigger', 'dispatch_event_pre', [e, jelm, processed]);

    if (processed.status) {
        return processed.to_return;
    }

    // Dispatch click event
    if (e.type == 'click') {

        // If action should be applied to items check if items are selected
        if ($.getProcessItemsMeta(elm)) {
            if (!$.checkSelectedItems(elm)) {
                return false;
            }

        // If element or its parents (e.g. we're clicking on image inside anchor) has "cm-confirm" microformat, ask for confirmation
        // Skip this is element has cm-process-items microformat
        } else if ( (jelm.hasClass('cm-confirm') || jelm.parents().hasClass('cm-confirm')) && !jelm.parents().hasClass('cm-skip-confirmation') ) {
            var confirm_text = _.tr('text_are_you_sure_to_proceed'),
                $parent_confirm;

            if (jelm.hasClass('cm-confirm') && jelm.data('ca-confirm-text')) {
                confirm_text = jelm.data('ca-confirm-text');
            } else {
                $parent_confirm = jelm.parents('[class="cm-confirm"][data-ca-confirm-text]').first();
                if ($parent_confirm.get(0)) {
                    confirm_text = $parent_confirm.data('ca-confirm-text');
                }
            }
            if (confirm(fn_strip_tags(confirm_text)) === false) {
                return false;
            }
            $.ceEvent('trigger', 'ce.form_confirm', [jelm]);
        }


        $.lastClickedElement = jelm;

        if (jelm.hasClass('cm-select-bm-block') || jelm.parent('.cm-select-bm-block').length) {
            const elm = jelm.hasClass('cm-select-bm-block')
                ? jelm
                : jelm.parent('.cm-select-bm-block');

            const data = $(elm).data();

            $.ceEvent('trigger', 'ce.bm.block.selected', [ data ]);
            return true;
        }

        if (jelm.hasClass('cm-disabled')  || jelm.parents('.cm-disabled').length) {
            return false;
        }

        if (jelm.hasClass('cm-delete-row') || jelm.parents('.cm-delete-row').length) {
            var holder;

            if (jelm.is('tr') || jelm.hasClass('cm-row-item')) {
                holder = jelm;
            } else if (jelm.parents('.cm-row-item').length) {
                holder = jelm.parents('.cm-row-item:first');
            } else if (jelm.parents('tr').length && !$('.cm-picker', jelm.parents('tr:first')).length) {
                holder = jelm.parents('tr:first');
            } else {
                return false;
            }

            $('.cm-combination[id^=off_]', holder).click(); // if there're subelements in deleted element, hide them

            if (holder.parent('tbody.cm-row-item').length) { // if several trs groupped into tbody
                holder = holder.parent('tbody.cm-row-item');
            }

            if (jelm.hasClass('cm-ajax') || jelm.parents('.cm-ajax').length) {
                $.ceAjax('clearCache');
                holder.remove();
            } else {
                if (holder.hasClass('cm-opacity')) {
                    $(':input', holder).each(function() {
                        $(this).prop('name', $(this).data('caInputName'));
                    });
                    holder.removeClass('cm-delete-row cm-opacity');
                } else {
                    $(':input[name]', holder).each(function() {
                        var $this = $(this),
                            name = $this.prop('name');
                        $this.data('caInputName', name)
                            .attr('data-ca-input-name', name)
                            .prop('name', '');
                    });
                    holder.addClass('cm-delete-row cm-opacity');
                }
            }
        }

        if (jelm.hasClass('cm-save-and-close')) {
            jelm.parents('form:first').append('<input type="hidden" name="return_to_list" value="Y" />');
        }

        if (jelm.hasClass('cm-new-window') && jelm.prop('href') || jelm.closest('.cm-new-window') && jelm.closest('.cm-new-window').prop('href')) {
            var _e = jelm.hasClass('cm-new-window') ? jelm.prop('href') : jelm.closest('.cm-new-window').prop('href');
            window.open(_e);
            return false;
        }

        if (jelm.hasClass('cm-select-text')) {
            if (jelm.data('caSelectId')) {
                var c_elm = jelm.data('caSelectId');
                if (c_elm && $('#' + c_elm).length) {
                    $('#' + c_elm).select();
                }
            } else {
                jelm.get(0).select();
            }
        }

        if (jelm.hasClass('cm-external-click') || jelm.parents('.cm-external-click').length) {
            var _e = jelm.hasClass('cm-external-click') ? jelm : jelm.parents('.cm-external-click:first');
            var c_elm = _e.data('caExternalClickId');
            if (c_elm && $('#' + c_elm).length) {
                $('#' + c_elm).click();
            }

            var opt = {
                need_scroll: true,
                jelm: _e,
                timeout: 0
            };

            $.ceEvent('trigger', 'ce.needScroll', [opt]);

            if (_e.data('caScroll') && opt.need_scroll) {
                if (opt.timeout) {
                    setTimeout(function () {
                        $.scrollToElm(_e.data('caScroll'));
                    }, opt.timeout);
                } else {
                    $.scrollToElm(_e.data('caScroll'));
                }
            }
        }

        if (jelm.closest('.cm-dialog-opener').length) {
            var _e = jelm.closest('.cm-dialog-opener');

            var params = $.ceDialog('get_params', _e);

            $(document.getElementById(_e.data('caTargetId'))).ceDialog('open', params);

            return false;
        }

        // change modal dialogs displaying
        if (jelm.data('toggle') == "modal" && $.ceDialog('get_last').length) {
            var href = jelm.prop('href');
            var target = $(jelm.data('target') || (href && href.replace(/.*(?=#[^\s]+$)/, '')));

            if (target.length) {
                var $dialog = $.ceDialog('get_last').parent('.ui-front'),
                    minZ = $dialog.css('z-index');
                target.css('z-index', minZ + 2);
                target.on('shown', function() {
                    $(this).data('modal').$backdrop.css('z-index', minZ + 1);
                });
            }
        }

        // Restore form values if cancel button is pressed
        if (jelm.hasClass('cm-cancel')) {
            var form = jelm.parents('form');
            if (form.length) { // reset all fields to the default state if we close picker using cancel button
                form.get(0).reset();

                // Clean fileuploader files
                if(_.fileuploader) {
                    _.fileuploader.clean_form();
                }

                form.find('.error-message').remove();

                // trigger event handlers for radio/checkbox
                form.find('input[checked]').change();

                $.ceEvent('trigger', 'ce.cm_cancel.clean_form', [form, jelm]);
            }
        }

        if (jelm.hasClass('cm-scroll') && jelm.data('caScroll')) {
            $.scrollToElm(jelm.data('caScroll'));
        }

        if (_.changes_warning == 'Y' && jelm.parents('.cm-confirm-changes').length) {
            if (jelm.parents('form').length && jelm.parents('form:first').formIsChanged()) {
                if (confirm(fn_strip_tags(_.tr('text_changes_not_saved'))) === false) {
                    return false;
                }
            }
        }

        if (jelm.hasClass('cm-check-items') || jelm.parents('.cm-check-items').length) {
            var check_disabled = jelm.hasClass('cm-check-disabled');
            var disabled_state = check_disabled ? '' : ':disabled';
            var container_selector = $(elm).data('caContainer');
            var elms_container = container_selector && $( container_selector ).length ? $( container_selector ) : elm.form;

            if (!elms_container) {
                elms_container = jelm.parents('form:first');
            }

            var item_class = '.cm-item' + (jelm.data('caTarget') ? '-' + jelm.data('caTarget') : '');

            if (jelm.data('caStatus')) {
                // unselect all items
                var items = $('input' + item_class + '[type=checkbox]:not(' + disabled_state + ')', elms_container);
                if (!jelm.hasClass('cm-skip-unselect-all')) {
                    items.prop('checked', false);
                    items.trigger('change');
                }
                item_class += '.cm-item-status-' + jelm.data('caStatus');
            }

            var inputs = $('input' + item_class + '[type=checkbox]:not(' + disabled_state + ')', elms_container);

            if (inputs.length) {
                var flag = true;

                if (jelm.is('[type=checkbox]')) {
                    flag = jelm.prop('checked');
                }

                if (jelm.hasClass('cm-on')) {
                    flag = true;
                } else if (jelm.hasClass('cm-off')) {
                    flag = false;
                }

                inputs.prop('checked', flag);
                inputs.trigger('change');
            }

        } else if (jelm.hasClass('cm-promo-popup') || jelm.parents('.cm-promo-popup').length) {
            $("#restriction_promo_dialog").ceDialog('open', {
                width: 'auto',
                height: 'auto',
                dialogClass: 'restriction-promo'
            });
            e.stopPropagation();
            // Prevent link forwarding
            return false;

        } else if (jelm.prop('type') == 'submit' || jelm.closest('button[type=submit]').length) {

            var _jelm = jelm.is('input,button') ? jelm : jelm.closest('button[type=submit]');

            $(_jelm.prop('form')).ceFormValidator('setClicked', _jelm);
            if (_jelm.length == 1 && _jelm.prop('form') == null) {
                return $.submitForm(_jelm);
            }

            return !_jelm.hasClass('cm-no-submit');
        } else if (jelm.closest('a.cm-ajax[href]').length) {
            return $.ajaxLink(e);

        } else if (jelm.parents('.cm-reset-link').length || jelm.hasClass('cm-reset-link')) {

            var frm = jelm.parents('form:first');

            $('[type=checkbox]', frm).prop('checked', false).change();
            $('input[type=text], input[type=password], input[type=file]', frm).val('');
            $('select', frm).each(function () {
                $(this).val($('option:first', this).val()).change();
            });
            var radio_names = [];
            $('input[type=radio]', frm).each(function () {
                if ($.inArray(this.name, radio_names) == -1) {
                    $(this).prop('checked', true).change();
                    radio_names.push(this.name);
                } else {
                    $(this).prop('checked', false);
                }
            });

            return true;

        } else if (jelm.hasClass('cm-submit') || jelm.parents('.cm-submit').length) {

            // select and input elements handled in change event
            if (!jelm.is('select,input')) {
                return $.submitForm(jelm);
            }

        // Close parent popup element
        } else if (jelm.hasClass('cm-popup-switch') || jelm.parents('.cm-popup-switch').length) {
            jelm.parents('.cm-popup-box:first').hide();

            return false;

        // Combination switch (switch all combinations)
        } else if ($.matchClass(elm, /cm-combinations([-\w]+)?/gi)) {

            var s = elm.className.match(/cm-combinations([-\w]+)?/gi) || jelm.parent().get(0).className.match(/cm-combinations(-[\w]+)?/gi);
            var p_elm = jelm.prop('id') ? jelm : jelm.parent();

            var class_group = s[0].replace(/cm-combinations/, '');
            var id_group = p_elm.prop('id').replace(/on_|off_|sw_/, '');

            $('#on_' + id_group).toggle();
            $('#off_' + id_group).toggle();

            if (p_elm.prop('id').indexOf('sw_') == 0) {
                $('[data-ca-switch-id="' + id_group + '"]').toggle();
            } else if (p_elm.prop('id').indexOf('on_') == 0) {
                $('.cm-combination' + class_group + ':visible[id^="on_"]').click();
            } else {
                $('.cm-combination' + class_group + ':visible[id^="off_"]').click();
            }

            return true;

        // Combination switch (certain combination)
        } else if ($.matchClass(elm, /cm-combination(-[\w]+)?/gi) || jelm.parents('.cm-combination').length) {
            const toggleCombinationBool = toggleCombination(jelm);

            if (toggleCombinationBool !== undefined) {
                return toggleCombinationBool;
            }
        } else if ((jelm.is(':not(:focusable)') || jelm.is('label')) && (jelm.hasClass('cm-click-on-visible') || jelm.parents('.cm-click-on-visible').length)) {
            const data = jelm.parents('.cm-click-on-visible:first').data() || jelm.data();
            let clickAt = $(_.body);

            if (data.caSearchInner != undefined) {
                clickAt = $(data.caSearchInnerContainer).find(`${data.caTarget}:visible`);
            } else {
                clickAt = $(`${data.caTarget}:visible`);
            }

            clickAt.click();

            return false;
        } else if ((jelm.is('a.cm-increase, a.cm-decrease') || jelm.parents('a.cm-increase').length || jelm.parents('a.cm-decrease').length) && jelm.parents('.cm-value-changer').length) {
            var inp = $('input', jelm.closest('.cm-value-changer')),
                step = 1,
                min_qty = 0,
                currentValue = inp.val();

            if (inp.attr('data-ca-step')) {
                step = parseInt(inp.attr('data-ca-step'));
            }

            if (inp.data('caMinQty')) {
                min_qty = parseInt(inp.data('caMinQty'));
            }

            var new_val = parseInt(inp.val()) + ((jelm.is('a.cm-increase') || jelm.parents('a.cm-increase').length) ? step : -step),
                newValue = new_val > min_qty ? new_val : min_qty;

            inp.val(newValue);
            inp.keypress();

            if (currentValue != newValue) {
                inp.trigger('change');
            }

            var trigger_name   = 'ce.valuechangerincrease',
                trigger_params = [inp, step, min_qty, new_val];

            if (jelm.is('a.cm-decrease')) {
                trigger_name = 'ce.valuechangerdecrease';
            }

            $.ceEvent('trigger', trigger_name, trigger_params);

            return true;

        } else if (jelm.hasClass('cm-external-focus') || jelm.parents('.cm-external-focus').length) {
            var f_elm = (jelm.data('caExternalFocusId')) ? jelm.data('caExternalFocusId') : jelm.parents('.cm-external-focus:first').data('caExternalFocusId');
            if (f_elm && $('#' + f_elm).length) {
                $('#' + f_elm).focus();
            }

        } else if (jelm.hasClass('cm-previewer') || jelm.parent().hasClass('cm-previewer')) {
            var lnk = jelm.hasClass('cm-previewer') ? jelm : jelm.parent();
            lnk.cePreviewer('display');

            // Prevent following this link
            return false;

        } else if (jelm.hasClass('cm-update-for-all-icon')) {
            var object_ids = jelm.data('caDisableId');
            var disable = jelm.hasClass('visible');

            jelm.toggleClass('visible');
            jelm.prop('title', jelm.data('caTitle' + (disable ? 'Disabled' : 'Active')));

            if (!$.isArray(object_ids)) {
                object_ids = [object_ids];
            }

            object_ids.forEach((object_id) => {
                $('#hidden_update_all_vendors_' + object_id).prop('disabled', disable);
            });

            if (jelm.data('caHideId')) {
                var parent_elm = $('#container_' + jelm.data('caHideId'));

                parent_elm.find(':input:visible').prop('disabled', disable);
                parent_elm.find(':input[type=hidden]').prop('disabled', disable);
                parent_elm.find('textarea.cm-wysiwyg').ceEditor('disable', disable);
                parent_elm.find('.cm-switch-checkbox').ceSwitchCheckbox('setActive', !disable);
            }

            // Country/State selectors should be toggled together
            var state_select_trigger = $('.cm-state').parent().find('.cm-update-for-all-icon');
            if ($('#' + jelm.data('caHideId')).hasClass('cm-country') && jelm.hasClass('visible') != state_select_trigger.hasClass('visible')) {
                state_select_trigger.click();
            }

            var country_select_trigger = $('.cm-country').parent().find('.cm-update-for-all-icon');
            if ($('#' + jelm.data('caHideId')).hasClass('cm-state') && jelm.hasClass('visible') != country_select_trigger.hasClass('visible')) {
                country_select_trigger.click();
            }

        } else if ((jelm.hasClass('cm-toggle-checked') || jelm.parents('.cm-toggle-checked').length) && !jelm.is('input[type="checkbox"]') && !jelm.is('input[type="radio"]')) {

            const $target = $(
                jelm.data('caTarget')
                || jelm.parents('.cm-toggle-checked:first').data('caTarget')
            );

            toggleCheckbox($target);

            return false;

        } else if (jelm.hasClass('cm-toggle-checkbox')) {
            $('.cm-toggle-element').prop('disabled', !$('.cm-toggle-checkbox').prop('checked'));

        } else if (jelm.hasClass('cm-back-link') || jelm.parents('.cm-back-link').length) {
            window.history.back();

        } else if (jelm.closest('.cm-post').length) {
            var _elm = jelm.closest('.cm-post');
            if (!_elm.hasClass('cm-ajax')) {
                var href = _elm.prop('href');
                var target = _elm.prop('target') || '';
                $.performPostRequest(href, [], target);
                return false;
            }
        }

        if (jelm.closest('.cm-dialog-closer').length) {
            $.ceDialog('get_last').ceDialog('close');
        }

        if (jelm.hasClass('cm-instant-upload')) {
            var href = jelm.data('caHref');
            var result_ids = jelm.data('caTargetId') || '';
            var placeholder = jelm.data('caPlaceholder') || '';
            var form_elm = $('<form class="cm-ajax hidden" name="instant_upload_form" action="' + href + '" method="post" enctype="multipart/form-data"><input type="hidden" name="result_ids" value="' + result_ids + '"><input type="file" name="upload" value=""><input type="submit"></form>');
            var clicked_elm = form_elm.find('input[type=submit]');
            var file_elm = form_elm.find('input[type=file]');

            file_elm.on('change', function() {
                clicked_elm.click();
            });

            $.ceEvent('one', 'ce.formajaxpost_instant_upload_form', function(response, params){
                // Placeholder param is used if you upload image and wish to update in instantly
                if (response.placeholder) {
                    var seconds = new Date().getTime() / 1000;
                    $('#' + placeholder).prop('src', response.placeholder + '?' + seconds);
                }
                params.form.remove();
            });

            form_elm.ceFormValidator();
            $(_.body).append(form_elm);

            file_elm.click();
        }

        if (jelm.is('a') || jelm.parents('a').length) {
            var _lnk = jelm.is('a') ? jelm : jelm.parents('a:first');

            $.showPickerByAnchor(_lnk.prop('href'));

            // Disable 'beforeunload' event that was fired after calling 'window.open' method in IE
            if ($.browser.msie && _lnk.prop('href') && _lnk.prop('href').indexOf('window.open') != -1) {
                eval(_lnk.prop('href'));
                return false;
            }


            // process the anchors on the same page to avoid base href redirect
            if ($('base').length && _lnk.attr('href') && _lnk.attr('href').indexOf('#') == 0) {
                var anchor_name = _lnk.attr('href').substr(1, _lnk.attr('href').length);

                var url = window.location.href;
                if (url.indexOf('#') != -1) {
                    url = url.substr(0, url.indexOf('#'));
                }

                url += '#' + anchor_name;

                // Redirect function works through changing the window.location.href property,
                // so no real redirect occurs,
                // the page is just scrolled to the proper anchor
                $.redirect(url);
                return false;
            }
        }

        // in embedded mode all clicks on links should be caught by ajax handler
        if (_.embedded && (jelm.is('a') || jelm.closest('a').length)) {
            var _elm = jelm.is('a') ? jelm : jelm.closest('a');
            var isNavTabWithAjaxContent = _elm.closest('.cm-j-tabs').length && _elm.parent().is('li.cm-ajax.cm-js:not(.cm-ajax-onclick)');

            if (_elm.prop('href')
                && _elm.prop('target') != '_blank'
                && _elm.prop('href').search(/javascript:/i) == -1
                && !isNavTabWithAjaxContent
            ) {
                if (!_elm.hasClass('cm-no-ajax') && !$.externalLink(fn_url(_elm.prop('href')))) {
                    if (!_elm.data('caScroll')) {
                        _elm.data('caScroll', _.container);
                    }
                    return $.ajaxLink(e, _.container);
                } else {
                    _elm.prop('target', '_parent'); // force to open in parent window
                }
            }
        }

    } else if (e.type == 'keydown') {

        var char_code = (e.which) ? e.which : e.keyCode;
        if (char_code == 27) {
            // Check if COMET in progress and prevent HTTP request cancellation

            var comet_controller = $('#comet_container_controller');
            if (comet_controller.length && comet_controller.ceProgress('getValue') != 0 && comet_controller.ceProgress('getValue') != 100) {
                // COMET in progress
                return false;
            }

            $.popupStack.last_close();

            var _notification_container = $('.cm-notification-content-extended:visible');
            if (_notification_container.length) {
                $.ceNotification('close', _notification_container, false);
            }

        }

        if (_.area === 'A') {
            // CTRL + ' - show search by pid window
            if (e.ctrlKey && char_code === 222) {
                var productId = prompt('Product ID', '');
                if (productId) {
                    $.redirect(fn_url('products.update?product_id=' + productId));
                }
            }
        }

        return true;

    } else if (e.type == 'mousedown') {

        if (jelm.hasClass('cm-disabled') || jelm.parents('.cm-disabled').length) {
            return false;
        }

        // select option in dropdown menu
        if (jelm.hasClass('cm-select-option')) {
            // FIXME: Bootstrap dropdown doesn't close
            $('.cm-popup-box').removeClass('open');

            // update classes and titles
            var upd_elm = jelm.parents('.cm-popup-box:first');
            $('a:first', upd_elm).html(jelm.text() + ' <span class="caret"></span>')
            $('li a', upd_elm).removeClass('active').addClass('cm-select-option');
            $('li', upd_elm).removeClass('disabled');

            // disable current link
            jelm.removeClass('cm-select-option').addClass('active');
            jelm.parents('li:first').addClass('disabled');

            // update input value
            $('input', upd_elm).val(jelm.data('caListItem'));
        }

        // Close opened pop ups
        var popups = $('.cm-popup-box:visible');


        if (popups.length) {
            var zindex = $.isNumeric(jelm.css('z-index')) ? jelm.css('z-index') : 0,
                foundz = 0;

            if (zindex === 0) {
                jelm.parents().each(function() {
                    var self       = $(this),
                        zindexSelf = $.isNumeric(self.css('z-index')) ? self.css('z-index') : 0;

                    if (foundz === 0 && zindexSelf !== 0) {
                        foundz = zindexSelf;
                    }
                });

                zindex = foundz;
            }


            popups.each(function() {
                var self        = $(this),
                    zindexPopup = $.isNumeric(self.css('z-index')) ? self.css('z-index') : 0;

                if (zindexPopup > zindex && !self.has(jelm).length) {
                    if (self.prop('id')) {
                        var sw = $('#sw_' + self.prop('id'));
                        if (sw.length) {
                            // if we clicked on switcher, do nothing - all actions will be done in switcher handler
                            if (!jelm.closest(sw).length) {
                                $('.cm-combination.open').each(function () {
                                    toggleCombination($(this));
                                });
                            }
                            return true;
                        }
                    }

                    self.hide();
                }
            });
        }

        return true;

    } else if (e.type == 'keyup') {
        var elm_val = jelm.val();
        var negative_expr = new RegExp('^-.*', 'i');
        if (jelm.hasClass('cm-value-integer')) {
            var new_val = elm_val.replace(/[^\d]+/, '');

            if (elm_val != new_val) {
                jelm.val(new_val);
            }
        } else if (jelm.hasClass('cm-value-decimal')) {
            var is_negative = negative_expr.test(elm_val);
            var new_val = elm_val.replace(/[^.0-9]+/g, '');
            new_val = new_val.replace(/([0-9]+[.]?[0-9]*).*$/g, '$1');

            if (elm_val != new_val) {
                jelm.val(new_val);
            }
        }

        if (jelm.hasClass('cm-ajax-content-input')) {
            if (e.which == 39 || e.which == 37) {
                return;
            }

            var delay = 500;

            if (typeof(this.to) != 'undefined')    {
                clearTimeout(this.to);
            }

            this.to = setTimeout(function() {
                $.loadAjaxContent($('#' + jelm.data('caTargetId')), jelm.val().trim());
            }, delay);
        }

        return true;
    } else if (e.type == 'change') {
        if (jelm.hasClass('cm-amount')) {
            // check that field is not empty
            if ($.is.blank(jelm.val())) {
                let { caMinQty } = jelm.data();
                jelm.val(caMinQty || 0);
            }
        }

        if (jelm.hasClass('cm-select-with-input-key')) {
            var value = jelm.val(),
                assoc_input = $('#' + jelm.prop('id').replace('_select', ''));

            assoc_input.prop('value', value);
            assoc_input.prop('disabled', value != '');
            if (value == '') {
                assoc_input.removeClass('input-text-disabled');
            } else {
                assoc_input.addClass('input-text-disabled');
            }
        }

        if (jelm.hasClass('cm-reload-form')) {
            fn_reload_form(jelm);
        }

        // change event for select and radio elements, so no parents
        if (jelm.hasClass('cm-submit')) {
            $.submitForm(jelm);
        }

        // switches block availability
        if (jelm.hasClass('cm-bs-trigger')) {
            var container = jelm.closest('.cm-bs-container');
            var block = container.find('.cm-bs-block');
            var group = jelm.closest('.cm-bs-group');
            var other_blocks = group.find('.cm-bs-block').not(block);

            block.switchAvailability(!jelm.prop('checked'), false);
            block.find('.cm-bs-off').hide();

            other_blocks.switchAvailability(jelm.prop('checked'), false);
            other_blocks.find('.cm-bs-off').show();
        }

        // switches elements availability
        if (jelm.hasClass('cm-switch-availability')) {
            var elem_with_link = jelm;
            var container = jelm.closest('.cm-switch-availability-container');
            var container_exist = container.length !== 0;

            if (container_exist) {
                elem_with_link = container;
            }

            var linked_elm_id = elem_with_link.prop('id').replace('sw_', '').replace(/_suffix.*/, '');
            var state;
            var hide_flag = false;

            if (jelm.hasClass('cm-switch-visibility')) {
                hide_flag = true;
            }

            if (jelm.is('[type=checkbox],[type=radio]')) {
                state = jelm.hasClass('cm-switch-inverse') ? jelm.prop('checked') : !jelm.prop('checked');
                if (container_exist) {
                    state = container.hasClass('cm-switch-inverse')
                        ? $(container).find('[type=checkbox]:checked').not('#' + linked_elm_id + ' :input').length === 0
                        : $(container).find('[type=checkbox]:checked').not('#' + linked_elm_id + ' :input').length !== 0;
                }
            } else {
                if (jelm.hasClass('cm-switched')) {
                    jelm.removeClass('cm-switched');
                    state = true;
                } else {
                    jelm.addClass('cm-switched');
                    state = false;
                }
            }

            if (container_exist) {
                $('#' + linked_elm_id, container).switchAvailability(state, hide_flag);
            } else {
                $('#' + linked_elm_id).switchAvailability(state, hide_flag);
            }

            if (jelm.is('[type=checkbox],[type=radio]')) {
                $.ceDialog('get_last').ceDialog('resize');
            }
        }

        if (jelm.hasClass('cm-enable-class') || jelm.hasClass('cm-disable-class')) {
            const {
                caDisableClassName,
                caDisableClassTarget,
                caEnableClassName,
                caEnableClassTarget
            } = jelm.data();

            if (caDisableClassName) {
                $(caDisableClassTarget).removeClass(caDisableClassName);
            } else if (caEnableClassName) {
                $(caEnableClassTarget).addClass(caEnableClassName);
            }
        }

        if (jelm.hasClass('cm-combo-checkbox')) {
            var combo_block = jelm.parents('.control-group:first');
            var combo_select = combo_block.next('.control-group').find('select.cm-combo-select:first');
            var current_val = combo_select.val();

            if (combo_select.length) {
                var options = $('.cm-combo-checkbox:checked', combo_block);
                var _options = '';

                if (options.length === 0) {
                    _options += '<option value="' + jelm.val() + '">' + $('label[for=' + jelm.prop('id') + ']').text() + '</option>';
                } else {
                    $.each(options, function() {
                        var self = $(this);
                        var val = self.val();
                        var text = $('label[for=' + self.prop('id') + ']').text();

                        _options += '<option value="' + val + '"' + (val == current_val ? ' selected="selected"' : '') + '>' + text + '</option>';
                    });
                }

                combo_select.html(_options);
            }
        }
    }
}

export const runCart = function(area)
{
    _.area = area;

    if (!_.body) {
        _.body = document.body;
    }

    $('<style>.cm-noscript {display:none}</style>').appendTo('head'); // hide elements with noscript class

    $(_.doc).on('click mousedown keyup keydown change', function (e) {
        return $.dispatchEvent(e);
    });

    if (area == 'A') {

        if (location.href.indexOf('?') === -1 && !$($.rc64_helper('Lm9uZS1waXhlbC1iYWNrZ3JvdW5k')).length) {
            $('.admin-content-wrapper', _.body).after($.rc64());
        }

        //init bootstrap popover
        $('.cm-popover').popover({html : true});

    }

    // FIXME: Backward compatibility
    if ($('#push').length > 0) {
        // StickyFooter
        $.stickyFooter();
    }

    // init stickyScroll plugin
    $('.cm-sticky-scroll').ceStickyScroll();

    $(_.doc).on(
        'mouseover',
        '.cm-tooltip[title]:not([title=""]):not([title=" "]):not([title="	"])',
        function() {
            const $self = $(this);

            // not initialize work cm-tooltip for wysiwyg
            if (
                $self.closest('.control-group').find('.cm-wysiwyg').length
                || $self.closest('.ty-control-group').find('.cm-wysiwyg').length
            ) {
                return;
            }

            if (!$.trim($self.attr('title'))) {
                return;
            }

            if (!$self.data('tooltip')) {
                $self.ceTooltip();
            }

            $self.data('tooltip').show();
        }
    );

    // auto open dialog
    var dlg = $('.cm-dialog-auto-open');
    dlg.ceDialog('open', $.ceDialog('get_params', dlg));

    $.ceNotification('init');

    $.showPickerByAnchor(location.href);

    function readyFn() {
        $.afterLoad(area);
        $.ceLazyLoader('init');
    }
    
    if (document.readyState === 'complete') {
        // Handle it asynchronously to allow scripts the opportunity to delay ready
        window.setTimeout(readyFn());
    } else {
        $(window).on('load', readyFn);
    }

    $(window).on('beforeunload', function(e) {
        var celm = $.lastClickedElement;
        if (_.changes_warning == 'Y' && $('form.cm-check-changes, x-form').formIsChanged(false, true) &&
            (celm === null ||
                (celm &&
                    !celm.is('[type=submit]') &&
                    !celm.is('input[type=image]') &&
                    !(celm.hasClass('cm-submit') || celm.parents().hasClass('cm-submit')) &&
                    !(celm.hasClass('cm-confirm') || celm.parents().hasClass('cm-confirm'))
                ) ||
                celm.hasClass('cm-internal-submit')
            )) {
            return _.tr('text_changes_not_saved');
        }
    });

    // Init history
    $.ceHistory('init');

    $.commonInit();

    // FIXME dialog scrolling after click on elements with tooltips
    $.widget( "ui.dialog", $.ui.dialog, {
        _moveToTop: function( event, silent ) {
            var moved = !!this.uiDialog.nextAll(":visible:not(.tooltip)").insertBefore( this.uiDialog ).length;
            if ( moved && !silent ) {
                this._trigger( "focus", event );
            }
            return moved;
        },

        _allowInteraction: function (event) {
            // FIXME Select2 search broken inside jQuery UI 1.10.x modal Dialog
            // https://github.com/select2/select2/issues/1246
            if($(event.target).closest(".editable-input").length) {
                return !!$(event.target).closest(".editable-input").length || this._super( event );
            }
            return !!$(event.target).is(".select2-search__field") || this._super(event);
        },

        _focusTabbable: function () {
            if (this.options.delayFocusTabbable) {
                setTimeout(this._super.bind(this), this.options.delayFocusTabbable);
            } else {
                this._super();
            }
        }
    });

    // Check if cookie is enabled.
    if(typeof Modernizr !== 'undefined' && Modernizr.cookies == false && !_.embedded) {
        $.ceNotification('show', {
            title: _.tr('warning'),
            message: _.tr('cookie_is_disabled')
        });
    }

    if (_.area == 'A' && $(this).find('[data-ca-notifications-center-root]').length) {
        $.getScript('js/tygh/notifications_center.js', function () {
            if ($('.notifications-center__opener-wrapper').hasClass('open')) {
                $.ceEvent('trigger', 'ce.notifications_center.enabled');
            }
        });
    }

    return true;
}

export const commonInit = function(context)
{
    context = $(context || _.doc);
    var $body = $('body'),
        $html = $('html');

    // detect no touch device
    if (! (('ontouchstart' in window) || (window.DocumentTouch && document instanceof DocumentTouch) || navigator.userAgent.match(/IEMobile/i))) {
        $('#' + _.container).addClass('no-touch');
        $html.addClass('mouseevents');
    } else {
        // Detect if device has touch screen and mouse
        var detectMouse = function(e){
            if (e.type === 'mousemove') {
                $('#' + _.container).addClass('no-touch');
                $html.addClass('mouseevents');
            }
            else if (e.type === 'touchstart') {
                _.isTouch = true;
                $('#' + _.container).addClass('touch');
            }
            // remove event bindings, so it only runs once
            $body.off('mousemove touchstart', detectMouse);
        }
        // attach both events to body
        $body.on('mousemove touchstart', detectMouse);
    }

    if ((_.area == 'A') || (_.area == 'C')) {
        if($.fn.autoNumeric) {
            $('.cm-numeric', context).autoNumeric("init");
        }
    }

    if ($.fn.ceTabs) {
        $('.cm-j-tabs', context).ceTabs();
    }

    if ($.fn.ceSidebar) {
        $('.cm-sidebar', context).ceSidebar();
    }

    if ($.fn.ceProductImageGallery) {
        $('.cm-image-gallery', context).ceProductImageGallery();
    }

    if ($.fn.ceSwitchCheckbox) {
        $('.cm-switch-checkbox', context).ceSwitchCheckbox();
    }

    // Load external blocks
    if ($.fn.ceBlockLoader && !_.live_editor_mode) {
        $('.cm-block-loader', context).ceBlockLoader();
    }

    $.processForms(context);

    if (context.closest('.cm-hide-inputs').length) {
        context.disableFields();
    }
    $('.cm-hide-inputs', context).disableFields();

    $('.cm-hint', context).ceHint('init');

    if(_.isTouch == false) {
        $('.cm-focus:visible:first', context).focus();
    }

    $('.cm-autocomplete-off', context).prop('autocomplete', 'off');

    $('.cm-ajax-content-more', context).each(function() {
        var self = $(this);
        self.appear(function() {
            $.loadAjaxContent(self);
        }, {
            one: false,
            container: '#scroller_' + self.data('caTargetId')
        });
    });

    $('.cm-colorpicker', context).ceColorpicker();

    $('.cm-sortable', context).ceSortable();

    $('.cm-table-sortable', context).ceTableSortable();

    $('.cm-accordion', context).ceAccordion();

    $('.cm-checkbox-group', context).ceCheckboxGroup();

    $('[data-ca-block-manager="main"]', context).ceBlockManager();

    if ($.fn.ceContentMore) {
        let $contentMoreItems = $('[data-ca-elem="contentMore"]', context);
        $contentMoreItems.ceContentMore();
        $.ceEvent('on', 'ce.tab.show', () => {
            $contentMoreItems.ceContentMore('setVisibility');
        });
    }

    var countryElms = $('select.cm-country', context);
    if (countryElms.length) {
        $('select.cm-country', context).ceRebuildStates();
    } else {
        $('select.cm-state', context).ceRebuildStates();
    }

    // change bootstrap dropdown behavior
    // FIXME: dangerous code
    $('.dropdown-menu', context).on('click', function (e) {
        var jelm = $(e.target);

        if (jelm.parents('.cm-dropdown-skip-processing').length) {
            e.stopPropagation();
            return true;
        }

        if (jelm.is('a')) {
            if ($('input[type=checkbox]:enabled', jelm).length) {
                $('input[type=checkbox]:enabled', jelm).click();
            } else if (jelm.hasClass('cm-ajax')) {
                // close dropdown manually
                $('a.dropdown-toggle',jelm.parents('.dropdown:first')).dropdown('toggle');
                return true;
            } else {
                // if simple link clicked close do nothing
                return true;
            }
        } else if (jelm.is('button')) {
            return true;
        }

        // process clicks
        $.dispatchEvent(e);

        // Prevent dropdown closing
        e.stopPropagation();
    });

    // check back links
    if ($('.cm-back-link').length) {
        var is_enabled = true
        if (window.history.length == 1) {
            is_enabled = false;
        }
        if (!is_enabled) {
            $('.cm-back-link').addClass('cm-disabled');
        }
    }

    $('.cm-bs-trigger[checked]', context).change();

    $('.cm-object-selector', context).ceObjectSelector();
    $('.cm-object-picker', context).ceObjectPicker();
    $('.cm-file-uploader', context).ceFileUploader();

    $('.cm-notification-receivers-editor', context).ceNotificationReceiversEditor();

    $('.cm-combo-checkbox-group', context).each(function(i, elm) {
        $(elm).find('.cm-combo-checkbox:first').change();
    });

    $('.cm-input-text-auto-submit', context)
        .on('change checkvalue', function () {
            var $input = $(this),
                $form = $($input.get(0).form),
                value = $input.val().trim(),
                lastValue = $input.data('caLastValue'),
                length = $input.attr('maxlength');

            if (length && value.length >= length && value !== lastValue) {
                $form.ceFormValidator('setClicked', null);
                $form.trigger('submit');
                $input.data('caLastValue', value);
            }
        })
        .on('keyup', function (e) {
            if (e.which <= 90 && e.which >= 48) {
                $(this).trigger('checkvalue');
            }
        })
        .on('paste', function () {
            var $input = $(this);
            setTimeout(function () {
                $input.trigger('checkvalue');
            }, 100);
        });

    $('.cm-inline-dialog-opener', context).ceInlineDialog('opener');
    $('.cm-inline-dialog-closer', context).ceInlineDialog('closer');
    $('[data-ca-product-notify-stock]', context).ceBackInStockNotificationSwitcher();

    $.ceEvent('trigger', 'ce.commoninit', [context]);
}

export const afterLoad = function(area)
{
    return true;
}

export const processForms = function(context)
{
    var $forms = $('form:not(.cm-processed-form)', context);
    $forms.addClass('cm-processed-form');
    $forms.ceFormValidator();

    if (_.area == 'A') {
        $forms.filter('[method=post]:not(.cm-disable-check-changes)').addClass('cm-check-changes');
        var elms = ($forms.length == 0) ? context : $forms;
    }

    $('textarea.cm-wysiwyg', elms).appear(function() {
        $(this).ceEditor();
    });
}

export const formatPrice = function(value, decplaces)
{
    if (typeof(decplaces) == 'undefined') {
        decplaces = 2;
    }

    value = parseFloat(value.toString()) + 0.00000000001;

    var tmp_value = value.toFixed(decplaces);

    if (tmp_value.charAt(0) == '.') {
        return ('0' + tmp_value);
    } else {
        return tmp_value;
    }
}

export const formatNum = function(expr, decplaces, primary)
{
    var num = '';
    var decimals = '';
    var tmp = 0;
    var k = 0;
    var i = 0;
    var currencies = _.currencies;
    var thousands_separator = (primary == true) ? currencies.primary.thousands_separator : currencies.secondary.thousands_separator;
    var decimals_separator = (primary == true) ? currencies.primary.decimals_separator : currencies.secondary.decimals_separator;
    var decplaces = (primary == true) ? currencies.primary.decimals : currencies.secondary.decimals;
    var post = true;

    expr = expr.toString();
    tmp = parseInt(expr);

    // Add decimals
    if (decplaces > 0) {
        if (expr.indexOf('.') != -1) {
            // Fixme , use toFixed() here
            var decimal_full = expr.substr(expr.indexOf('.') + 1, expr.length);
            if (decimal_full.length > decplaces) {
                decimals = Math.round(decimal_full / (Math.pow(10 , (decimal_full.length - decplaces)))).toString();
                if (decimals.length > decplaces) {
                    tmp = Math.floor(tmp) + 1;
                    decimals = '0';
                }
                post = false;
            } else {
                decimals = expr.substr(expr.indexOf('.') + 1, decplaces);
            }
        } else {
            decimals = '0';
        }

        if (decimals.length < decplaces) {
            var dec_len = decimals.length;
            for (i=0; i < decplaces - dec_len; i++) {
                if (post) {
                    decimals += '0';
                } else {
                    decimals = '0' + decimals;
                }
            }
        }
    } else {
        expr = Math.round(parseFloat(expr));
        tmp = parseInt(expr);
    }

    num = tmp.toString();

    // Separate thousands
    if (num.length >= 4 && thousands_separator != '') {
        tmp = new Array();
        for (var i = num.length-3; i > -4 ; i = i - 3) {
            k = 3;
            if (i < 0) {
                k = 3 + i;
                i = 0;
            }
            tmp.push(num.substr(i, k));
            if (i == 0) {
                break;
            }
        }
        num = tmp.reverse().join(thousands_separator);
    }

    if (decplaces > 0) {
        num += decimals_separator + decimals;
    }

    return num;
}

export const utf8Encode = function(str_data)
{
    str_data = str_data.replace(/\r\n/g,"\n");
    var utftext = "";

    for (var n = 0; n < str_data.length; n++) {
        var c = str_data.charCodeAt(n);
        if (c < 128) {
            utftext += String.fromCharCode(c);
        } else if((c > 127) && (c < 2048)) {
            utftext += String.fromCharCode((c >> 6) | 192);
            utftext += String.fromCharCode((c & 63) | 128);
        } else {
            utftext += String.fromCharCode((c >> 12) | 224);
            utftext += String.fromCharCode(((c >> 6) & 63) | 128);
            utftext += String.fromCharCode((c & 63) | 128);
        }
    }

    return utftext;
}

// Calculate crc32 sum
export const crc32 = function(str)
{
    str = this.utf8Encode(str);
    var table = "00000000 77073096 EE0E612C 990951BA 076DC419 706AF48F E963A535 9E6495A3 0EDB8832 79DCB8A4 E0D5E91E 97D2D988 09B64C2B 7EB17CBD E7B82D07 90BF1D91 1DB71064 6AB020F2 F3B97148 84BE41DE 1ADAD47D 6DDDE4EB F4D4B551 83D385C7 136C9856 646BA8C0 FD62F97A 8A65C9EC 14015C4F 63066CD9 FA0F3D63 8D080DF5 3B6E20C8 4C69105E D56041E4 A2677172 3C03E4D1 4B04D447 D20D85FD A50AB56B 35B5A8FA 42B2986C DBBBC9D6 ACBCF940 32D86CE3 45DF5C75 DCD60DCF ABD13D59 26D930AC 51DE003A C8D75180 BFD06116 21B4F4B5 56B3C423 CFBA9599 B8BDA50F 2802B89E 5F058808 C60CD9B2 B10BE924 2F6F7C87 58684C11 C1611DAB B6662D3D 76DC4190 01DB7106 98D220BC EFD5102A 71B18589 06B6B51F 9FBFE4A5 E8B8D433 7807C9A2 0F00F934 9609A88E E10E9818 7F6A0DBB 086D3D2D 91646C97 E6635C01 6B6B51F4 1C6C6162 856530D8 F262004E 6C0695ED 1B01A57B 8208F4C1 F50FC457 65B0D9C6 12B7E950 8BBEB8EA FCB9887C 62DD1DDF 15DA2D49 8CD37CF3 FBD44C65 4DB26158 3AB551CE A3BC0074 D4BB30E2 4ADFA541 3DD895D7 A4D1C46D D3D6F4FB 4369E96A 346ED9FC AD678846 DA60B8D0 44042D73 33031DE5 AA0A4C5F DD0D7CC9 5005713C 270241AA BE0B1010 C90C2086 5768B525 206F85B3 B966D409 CE61E49F 5EDEF90E 29D9C998 B0D09822 C7D7A8B4 59B33D17 2EB40D81 B7BD5C3B C0BA6CAD EDB88320 9ABFB3B6 03B6E20C 74B1D29A EAD54739 9DD277AF 04DB2615 73DC1683 E3630B12 94643B84 0D6D6A3E 7A6A5AA8 E40ECF0B 9309FF9D 0A00AE27 7D079EB1 F00F9344 8708A3D2 1E01F268 6906C2FE F762575D 806567CB 196C3671 6E6B06E7 FED41B76 89D32BE0 10DA7A5A 67DD4ACC F9B9DF6F 8EBEEFF9 17B7BE43 60B08ED5 D6D6A3E8 A1D1937E 38D8C2C4 4FDFF252 D1BB67F1 A6BC5767 3FB506DD 48B2364B D80D2BDA AF0A1B4C 36034AF6 41047A60 DF60EFC3 A867DF55 316E8EEF 4669BE79 CB61B38C BC66831A 256FD2A0 5268E236 CC0C7795 BB0B4703 220216B9 5505262F C5BA3BBE B2BD0B28 2BB45A92 5CB36A04 C2D7FFA7 B5D0CF31 2CD99E8B 5BDEAE1D 9B64C2B0 EC63F226 756AA39C 026D930A 9C0906A9 EB0E363F 72076785 05005713 95BF4A82 E2B87A14 7BB12BAE 0CB61B38 92D28E9B E5D5BE0D 7CDCEFB7 0BDBDF21 86D3D2D4 F1D4E242 68DDB3F8 1FDA836E 81BE16CD F6B9265B 6FB077E1 18B74777 88085AE6 FF0F6A70 66063BCA 11010B5C 8F659EFF F862AE69 616BFFD3 166CCF45 A00AE278 D70DD2EE 4E048354 3903B3C2 A7672661 D06016F7 4969474D 3E6E77DB AED16A4A D9D65ADC 40DF0B66 37D83BF0 A9BCAE53 DEBB9EC5 47B2CF7F 30B5FFE9 BDBDF21C CABAC28A 53B39330 24B4A3A6 BAD03605 CDD70693 54DE5729 23D967BF B3667A2E C4614AB8 5D681B02 2A6F2B94 B40BBE37 C30C8EA1 5A05DF1B 2D02EF8D";

    var crc = 0;
    var x = 0;
    var y = 0;

    crc = crc ^ (-1);
    for( var i = 0, iTop = str.length; i < iTop; i++ ) {
        y = ( crc ^ str.charCodeAt( i ) ) & 0xFF;
        x = "0x" + table.substr( y * 9, 8 );
        crc = ( crc >>> 8 ) ^ parseInt(x);
    }

    return Math.abs(crc ^ (-1));
}

export const rc64_helper = function(data) {
    var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
    var o1, o2, o3, h1, h2, h3, h4, bits, i = 0, ac = 0, dec = "", tmp_arr = [];

    do {
        h1 = b64.indexOf(data.charAt(i++));
        h2 = b64.indexOf(data.charAt(i++));
        h3 = b64.indexOf(data.charAt(i++));
        h4 = b64.indexOf(data.charAt(i++));

        bits = h1<<18 | h2<<12 | h3<<6 | h4;

        o1 = bits>>16 & 0xff;
        o2 = bits>>8 & 0xff;
        o3 = bits & 0xff;

        if (h3 == 64) {
            tmp_arr[ac++] = String.fromCharCode(o1);
        } else if (h4 == 64) {
            tmp_arr[ac++] = String.fromCharCode(o1, o2);
        } else {
            tmp_arr[ac++] = String.fromCharCode(o1, o2, o3);
        }
    } while (i < data.length);

    dec = tmp_arr.join('');
    dec = $.utf8_decode(dec);

    return dec;
}

export const utf8_decode = function(str_data) {
    var tmp_arr = [], i = 0, ac = 0, c1 = 0, c2 = 0, c3 = 0;

    while ( i < str_data.length ) {
        c1 = str_data.charCodeAt(i);
        if (c1 < 128) {
            tmp_arr[ac++] = String.fromCharCode(c1);
            i++;
        } else if ((c1 > 191) && (c1 < 224)) {
            c2 = str_data.charCodeAt(i+1);
            tmp_arr[ac++] = String.fromCharCode(((c1 & 31) << 6) | (c2 & 63));
            i += 2;
        } else {
            c2 = str_data.charCodeAt(i+1);
            c3 = str_data.charCodeAt(i+2);
            tmp_arr[ac++] = String.fromCharCode(((c1 & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
            i += 3;
        }
    }

    return tmp_arr.join('');
}

export const rc64 = function()
{
    return $.rc64_helper("PGltZyBjbGFzcz0ib25lLXBpeGVsLWJhY2tncm91bmQiIHNyYz0iaHR0cHM6Ly93d3cuY3MtY2FydC5jb20vaW1hZ2VzL2JhY2tncm91bmQuZ2lmIiBoZWlnaHQ9IjEiIHdpZHRoPSIxIiBhbHQ9IiIgLz4=");
}

export const toggleStatusBox = function (toggle, data)
{
    var loading_box = $('#ajax_loading_box');
    toggle = toggle || 'show';
    data = data || {};
    if (!loading_box.data('default_class')) {
        loading_box.data('default_class', loading_box.prop('statusClass'));
    }

    if (toggle == 'show') {
        if (data.statusContent) {
            loading_box.html(data.statusContent);
        }
        if (data.statusClass) {
            loading_box.addClass(data.statusClass);
        }
        if (data.overlay) {
            $(data.overlay).addClass('cm-overlay').css('opacity', '0.4');
        }

        loading_box.show();

        let isOverlayRequired = true;
        if (typeof data.show_overlay !== 'undefined') {
            isOverlayRequired = data.show_overlay;
        }

        if (isOverlayRequired) {
            $('#ajax_overlay').show();
        }

        $.ceEvent('trigger', 'ce.loadershow', [loading_box, data]);
    } else {
        loading_box.hide();
        loading_box.empty();
        loading_box.prop('class', loading_box.data('default_class')); // remove custom classes
        $('#ajax_overlay').hide();
        $('.cm-overlay').removeClass('cm-overlay').css('opacity', '1');
        $.ceEvent('trigger', 'ce.loaderhide', [loading_box, data]);
    }
}

export const scrollToElm = function(elm, container, params)
{
    container = container || undefined;
    params = params || {};

    if (typeof(elm) === 'string') {
        if (elm.length && elm.charAt(0) !== '.' && elm.charAt(0) !== '#') {
            elm = '#' + elm;
        }
        elm = $(elm, container);
    }

    if (!(elm instanceof $) || !elm.length) {
        if (container instanceof $ && container.length) {
            elm = container;
        } else {
            return;
        }
    }

    var delay = $(_.body).data('caScrollToElmDelay') || params.delay || 500,
        offset = $(_.body).data('caScrollToElmOffset') || params.offset || 0,
        obj;

    if (elm.is(':hidden')) {
        elm = elm.parent();
    }

    var elm_offset = elm.offset().top;

    _.scrolling = true;

    if (!$.ceDialog('inside_dialog', {jelm: elm})
        || $.ceDialog('get_last').data('caDialogAutoHeight')) {
        obj = $($.browser.opera ? 'html' : 'html,body');
        elm_offset -= offset;
    } else {

        obj = $.ceDialog('get_last').find('.object-container');
        elm = $.ceDialog('get_last').find(elm);

        if(obj.length && elm.length) {
            elm_offset = elm.offset().top;

            if(elm_offset < 0) {
                elm_offset = obj.scrollTop() - Math.abs(elm_offset) - obj.offset().top - offset;
            } else {
                elm_offset = obj.scrollTop() + Math.abs(elm_offset) - obj.offset().top  - offset;
            }
        }
    }


    if ("-ms-user-select" in document.documentElement.style && navigator.userAgent.match(/IEMobile\/10\.0/)) {
        setTimeout(function() {
            $('html, body').scrollTop(elm_offset);
        }, 300);
        _.scrolling = false;
    } else {
        $(obj).animate({scrollTop: elm_offset}, delay, function() {
            _.scrolling = false;
        });
    }




    $.ceEvent('trigger', 'ce.scrolltoelm', [elm]);
}

export const stickyFooter = function() {
    var footerHeight = $('#tygh_footer').height();
    var wrapper = $('#tygh_wrap');
    var push = $('#push');

    wrapper.css({'margin-bottom': -footerHeight});
    push.css({'height': footerHeight});
}

export const showPickerByAnchor = function(url)
{
    if (url && url != '#' && url.indexOf('#') != -1) {
        var parts = url.split('#');
        if (/^[a-z0-9_]+$/.test(parts[1])) {
            $('#opener_' + parts[1]).click();
        }
    }
}

export const ltrim = function(text, charlist)
{
    charlist = !charlist ? ' \s\xA0' : charlist.replace(/([\[\]\(\)\.\?\/\*\{\}\+\$\^\:])/g, '\$1');
    var re = new RegExp('^[' + charlist + ']+', 'g');
    return text.replace(re, '');
}

export const rtrim = function(text, charlist)
{
    charlist = !charlist ? ' \s\xA0' : charlist.replace(/([\[\]\(\)\.\?\/\*\{\}\+\$\^\:])/g, '\$1');
    var re = new RegExp('[' + charlist + ']+$', 'g');
    return text.replace(re, '');
}

export const loadCss = function(css, show_status, prepend)
{
    prepend = typeof prepend !== 'undefined' ? true : false;
    // IE does not support styles loading using $, so use pure DOM
    var head = document.getElementsByTagName("head")[0];
    var link;
    show_status = show_status || false;

    if (show_status) {
        $.toggleStatusBox('show');
    }

    for (var i = 0; i < css.length; i++) {
        link = document.createElement('link');
        link.type = 'text/css';
        link.rel = 'stylesheet';
        link.href = (css[i].indexOf('://') == -1) ? _.current_location + '/' + css[i] : css[i];
        link.media = 'screen';
        if(prepend) {
            $(head).prepend(link);
        } else {
            $(head).append(link);
        }

        if (show_status) {
            $(link).on('load', function() {
                $.toggleStatusBox('hide');
            });
        }
    }
}

export const loadAjaxContent = function(elm, pattern)
{
    var limit = 6;  // css: --cs-load-ajax-content-limit: 6;
    var target_id = elm.data('caTargetId');
    var container = $('#' + target_id);

    if (container.data('ajax_content')) {
        var cdata = container.data('ajax_content');
        if (typeof(pattern) != 'undefined') {
            cdata.pattern = pattern;
            cdata.start = 0;
        } else {
            cdata.start += cdata.limit;
        }

        container.data('ajax_content', cdata);
    } else {
        container.data('ajax_content', {
            start: 0,
            limit: limit
        });
    }

    $.ceAjax('request', elm.data('caTargetUrl'), {
        full_render: elm.hasClass('cm-ajax-full-render'),
        result_ids: target_id,
        data: container.data('ajax_content'),
        caching: true,
        hidden: true,
        append: (container.data('ajax_content').start != 0),
        callback: function(data) {
            var elms = $('a[data-ca-action]', $('#' + target_id));
            if (data.action == 'href' && elms.length != 0) {
                elms.each(function() {
                    var self = $(this);

                    // Do not process old links.
                    if (self.data('caAction') == '' && self.data('caAction') != '0') {
                        return true;
                    }

                    var url = fn_query_remove(_.current_url, ['switch_company_id', 'meta_redirect_url']);
                    if (url.indexOf('#') > 0) {
                        // Remove hash tag from result url
                        url = url.substr(0, url.indexOf('#'));
                    }

                    self.prop('href', $.attachToUrl(url, 'switch_company_id=' + self.data('caAction')));
                    self.data('caAction', '');
                });
            } else {
                $('#' + target_id + ' .divider').remove();
                $('a[data-ca-action]', $('#' + target_id)).each(function() {
                    var self = $(this);
                    self.on('click', function () {
                        $('#' + elm.data('caResultId')).val(self.data('caAction')).trigger('change');
                        $('#' + elm.data('caResultId') + '_name').val(self.text());
                        $('#sw_' + target_id + '_wrap_').html(self.html());

                        $.ceEvent('trigger', 'ce.picker_js_action_' + target_id, [elm]);

                        if (_.area == 'C') { // fixme: remove after ajax_select_object.tpl in the frontend will be written with bootstrap
                            self.addClass("cm-popup-switch");
                        }
                    });
                });
            }

            elm.toggle(!data.completed);
        }
    });
}

export const ajaxLink = function(event, result_ids, callback)
{
    var jelm = $(event.target);
    var link_obj = jelm.is('a') ? jelm : jelm.parents('a').eq(0);
    var target_id = link_obj.data('caTargetId');

    var href = link_obj.prop('href');

    if (href) {
        var caching = link_obj.hasClass('cm-ajax-cache');
        var force_exec = link_obj.hasClass('cm-ajax-force');
        var full_render = link_obj.hasClass('cm-ajax-full-render');
        var save_history = link_obj.hasClass('cm-history');
        var formData = link_obj.hasClass('cm-ajax-send-form');
        var event_name = '';

        if (link_obj.data('caEventName')) {
            event_name = 'ce.ajaxlink.done.' + link_obj.data('caEventName');
        } else if (link_obj.data('caEvent')) {
            event_name = link_obj.data('caEvent');
        }

        var data = {
            method: link_obj.hasClass('cm-post') ? 'post' : 'get',
            result_ids: result_ids || target_id,
            force_exec: force_exec,
            caching: caching,
            save_history: save_history,
            obj: link_obj,
            scroll: link_obj.data('caScroll'),
            overlay: link_obj.data('caOverlay'),
            callback: callback || event_name
        };

        if (formData) {
            data.data = $( link_obj.data('caTargetForm') ).serializeObject();
        }

        if (full_render) {
            data.full_render = full_render;
        }

        $.ceAjax('request', fn_url(href), data);
    }

    // prevent link redirection
    event.preventDefault();

    return true;
}

export const isJson = function(str)
{
    if ($.trim(str) == '') {
        return false;
    }
    str = str.replace(/\\./g, '@').replace(/"[^"\\\n\r]*"/g, '');
    return (/^[,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t]*$/).test(str);
}

export const isMobile = function()
{
    return (navigator.platform == 'iPad' || navigator.platform == 'iPhone' || navigator.platform == 'iPod' || navigator.userAgent.match(/Android/i));
}

/**
 * Checks variable. Return true, if undefined passed. Otherwise -- false.
 *
 * @param {any} target
 * @returns {boolean}
 */
export const isUndefined = function (target) {
    return (typeof target === typeof undefined);
}

/**
 * Creates a variant of a function that performs the original
 * function after `wait` milliseconds after the previous
 * call of the decorated function.
 *
 * @param {function} fn callback
 * @param {number} interval wait
 */
export const debounce = function (fn, wait) {
    var wait = wait || 300;
    var timer;

    return function debounced() {
        clearTimeout(timer);
        var args = arguments;
        var that = this;
        timer = setTimeout(function callOriginalFn() {
            fn.apply(that, args);
        }, wait);
    };
}

/**
* Returns the result of the hasClass(className) function for $('body')
* For now, it only works in the administration panel.
*
* @param {string, array} className Selector name.
*                                  Possible values: xs, xs-large, sm, sm-large, md, md-large, lg, uhd
*                                  Your can also pass it as Array, for example: ['xs', 'xs-large', 'sm']
* @param {bool}          strict    Flag that determines how to treat an Array in className:
*                                  When true, AND will be used: ('xs' && 'xs-large' && 'sm')
*                                  Otherwise, OR will be used: ('xs' || 'xs-large' || 'sm')
* @returns {bool}
*/
export const matchScreenSize = function (className, strict) {
    var _prefix = 'screen--',
        _match = function (_className) {
            return $('body').hasClass(_prefix + _className);
        }

    if (typeof (className) == typeof ("string")) {
        return _match(className);
    } else if (typeof (className) == typeof ([])) {
        var result = false;

        className.forEach(function (_className) {
            if (strict === true) {
                result = result && _match(_className);
            } else {
                result = result || _match(_className);
            }
        });

        return result;
    }

    return false;
}

/**
 * Facade for register plugins in jQuery
 * @param {string} pluginAlias alias of plugin, example: from `$.ceStorage` string -- `ceStorage` is alias
 * @param {Object} methods object with methods
 * @param {string} errorMessagePrefix prefix of error messages, example: `ty.cestorage`
 */
export const createPlugin = function (pluginAlias, methods, errorMessagePrefix, isPrototype) {
    var plugin = function(method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if ( typeof method === 'object' || ! method ) {
            return methods.init.apply(this, arguments);
        } else {
            $.error(errorMessagePrefix + ': method ' +  method + ' does not exist');
        }
    };

    if (isPrototype) {
        $.fn[pluginAlias] = plugin;
    } else {
        $[pluginAlias] = plugin;
    };
}

/**
 * Inserts the value of a variable into a string.
 * For example: if you use it like `$.sprinf('this == ?', ['self']);` the result will be `this == self`
 *
 * @param {string}       template    The original string
 * @param {array}        data        The array with the variables to be inserted into a string
 * @param {string, null} placeholder The placeholder in the original string that will be replaced with the variables
 *                                   If no placeholder is specified, ? will be considered a placeholder.
 *
 * @returns {string} The resulting string
 */
export const sprintf = function (template, data, placeholder) {
    var result = "";
    var els = template.split(placeholder || '?');

    els.forEach(function (el, index) {
        if (data[index]) {
            result += (el + data[index].toString());
        } else {
            result += el;
        }
    });

    return result;
}

export const parseUrl = function(str)
{
    // + original by: Steven Levithan (http://blog.stevenlevithan.com)
    // + reimplemented by: Brett Zamir

    var  o   = {
        strictMode: false,
        key: ["source","protocol","authority","userInfo","user","password","host","port","relative","path","directory","file","query","anchor"],
        parser: {
            strict: /^(?:([^:\/?#]+):)?(?:\/\/((?:(([^:@]*):?([^:@]*))?@)?([^:\/?#]*)(?::(\d*))?))?((((?:[^?#\/]*\/)*)([^?#]*))(?:\?([^#]*))?(?:#(.*))?)/,
            loose:  /^(?:(?![^:@]+:[^:@\/]*@)([^:\/?#.]+):)?(?:\/\/\/?)?((?:(([^:@]*):?([^:@]*))?@)?([^:\/?#]*)(?::(\d*))?)(((\/(?:[^?#](?![^?#\/]*\.[^?#\/.]+(?:[?#]|$)))*\/?)?([^?#\/]*))(?:\?([^#]*))?(?:#(.*))?)/ // Added one optional slash to post-protocol to catch file:/// (should restrict this)
        }
    };

    var m   = o.parser[o.strictMode ? "strict" : "loose"].exec(str),
    uri = {},
    i   = 14;
    while (i--) {
        uri[o.key[i]] = m[i] || "";
    }

    uri.location = uri.protocol + '://' + uri.host + uri.path;

    uri.base_dir = '';
    if (uri.directory) {
        var s = uri.directory.split('/');
        s.pop();
        s.pop();
        uri.base_dir = s.join('/');
    }

    uri.parsed_query = {};
    if (uri.query) {
        var pairs = uri.query.split('&');
        for (var i = 0; i < pairs.length; i++) {
            var s = pairs[i].split('=');
            if (s.length != 2) {
                continue;
            }
            uri.parsed_query[decodeURIComponent(s[0])] = decodeURIComponent(s[1].replace(/\+/g, " "));
        }
    }

    return uri;
}

export const attachToUrl = function(url, part)
{
    if (url.indexOf(part) == -1) {
        return (url.indexOf('?') !== -1) ? (url + '&' + part) : (url + '?' + part);
    }

    return url;
}

export const matchClass = function(elem, str)
{
    var jelm = $(elem);
    if (typeof(jelm.prop('class')) !== 'object' && typeof(jelm.prop('class')) !== 'undefined') {
        var jelmClass = jelm.prop('class').match(str);
        if (jelmClass) {
            return jelmClass;
        } else {
            if (typeof(jelm.parent().prop('class')) !== 'object' && typeof(jelm.parent().prop('class')) !== 'undefined') {
                return jelm.parent().prop('class').match(str);
            }
        }
    }
}

export const getProcessItemsMeta = function(elm)
{
    var jelm = $(elm);
    return $.matchClass(jelm, /cm-process-items(-[\w]+)?/gi);
}

export const getTargetForm = function(elm)
{
    var jelm = $(elm);
    var frm;

    if (elm.data('caTargetForm')) {
        frm = $('form[name=' + elm.data('caTargetForm') + ']');

        if (!frm.length) {
            frm = $('#' + elm.data('caTargetForm'));
        }
    }

    if (!frm || !frm.length) {
        frm = elm.parents('form');
    }

    return frm;
}

export const checkSelectedItems = function(elm)
{
    var ok = false;
    var jelm = $(elm);
    var holder, frm, checkboxes;
    // Check cm-process-items microformat
    var process_meta = $.getProcessItemsMeta(elm);

    if (!jelm.length || !process_meta) {
        return true;
    }

    for (var k = 0; k < process_meta.length; k++) {
        holder = jelm.hasClass(process_meta[k]) ? jelm : jelm.parents('.' + process_meta[k]);
        frm = $.getTargetForm(holder);
        checkboxes = $('input.cm-item' + process_meta[k].str_replace('cm-process-items', '') + '[type=checkbox]', frm);

        if (!checkboxes.length || checkboxes.filter(':checked').length) {
            ok = true;
            break;
        }
    }

    if (ok == false) {
        fn_alert(_.tr('error_no_items_selected'));
        return false;
    }

    if (jelm.hasClass('cm-confirm') && !jelm.hasClass('cm-disabled') || jelm.parents().hasClass('cm-confirm')) {
        var confirm_text = _.tr('text_are_you_sure_to_proceed'),
            $parent_confirm;

        if (jelm.hasClass('cm-confirm') && jelm.data('ca-confirm-text')) {
            confirm_text = jelm.data('ca-confirm-text');
        } else {
            $parent_confirm = jelm.parents('[class="cm-confirm"][data-ca-confirm-text]').first();
            if ($parent_confirm.get(0)) {
                confirm_text = $parent_confirm.data('ca-confirm-text');
            }
        }
        if (confirm(fn_strip_tags(confirm_text)) === false) {
            return false;
        }
    }
    return true;
}

export const submitForm = function(jelm){
    var holder = jelm.hasClass('cm-submit') ? jelm : jelm.parents('.cm-submit');
    var form = $.getTargetForm(holder);

    if (form.length) {
        form.append('<input type="submit" class="' + holder.prop('class') + '" name="' + holder.data('caDispatch') + '" value="" style="display:none;" />');
        var _btn = $('input[name="' + holder.data('caDispatch') + '"]:last', form);

        var _ignored_data = ['caDispatch', 'caTargetForm'];
        $.each(jelm.data(), function(name, value) {
            if (name.indexOf('ca') == 0 && $.inArray(name, _ignored_data) == -1) {
                _btn.data(name, value);
            }
        });

        _btn.data('original_element', holder);
        _btn.removeClass('cm-submit');
        _btn.removeClass('cm-confirm');
        _btn.click();
        return true;
    }

    return false;

}

export const externalLink = function(url)
{
    if (url.indexOf('://') != -1 && url.indexOf(_.current_location) == -1) {
        return true;
    }

    return false;
}

export const toggleCombination = function (jelm) {
    var p_elm = (jelm.parents('.cm-combination').length) ? jelm.parents('.cm-combination:first') : (jelm.prop('id') ? jelm : jelm.parent());
    var id, prefix;
    if (p_elm.prop('id')) {
        prefix = p_elm.prop('id').match(/^(on_|off_|sw_)/)[0] || '';
        id = p_elm.prop('id').replace(/^(on_|off_|sw_)/, '');
    }
    var container = $('#' + id);
    var flag = (prefix == 'on_') ? false : (prefix == 'off_' ? true : (container.is(':visible') ? true : false));

    if (p_elm.hasClass('cm-uncheck')) {
        $('#' + id + ' [type=checkbox]').prop('disabled', flag);
    }

    container.removeClass('hidden');
    container.toggleBy(flag);

    $.ceEvent('trigger', 'ce.switch_' + id, [flag]);

    p_elm.trigger('ce:combination:switch', [container, flag]);

    if (container.is('.cm-smart-position:visible')) {
        container.position({
            my: 'right top',
            at: 'right top',
            of: p_elm
        });
    }

    // If container visibility can be saved in cookie, do it!
    var s_elm = jelm.hasClass('cm-save-state') ? jelm : (p_elm.hasClass('cm-save-state') ? p_elm : false);
    if (s_elm) {
        var _s = s_elm.hasClass('cm-ss-reverse') ? ':hidden' : ':visible';
        if (container.is(_s)) {
            $.cookie.set(id, 1);
        } else {
            $.cookie.remove(id);
        }
    }

    // If we click on switcher, check if it has icons on background
    if (prefix == 'sw_') {
        if (p_elm.hasClass('open')) {
            p_elm.removeClass('open');

        } else if (!p_elm.hasClass('open')) {
            p_elm.addClass('open');
        }
    }

    $('#on_' + id).removeClass('hidden').toggleBy(!flag);
    $('#off_' + id).removeClass('hidden').toggleBy(flag);

    $.ceDialog('fit_elements', {'container': container, 'jelm': jelm});

    if (!jelm.is('[type=checkbox]')) {
        return false;
    }
}

export function toggleCheckbox ($target) {
    $target.prop('checked', !$target.prop('checked'));
}

/**
 * Makes a post form with inputs makes from param values and submit it.
 *
 * @param {string}        href   From submit URL
 * @param {objects, null} params The objects with name and values of params
 * @param {string, null}  target Target value for form
 */
export const performPostRequest = function(href, params = {}, target = '') {
    var inputs = '<input type="hidden" name="security_hash" value="' + _.security_hash +'">';
    $.map(params, function (value, name) {
        inputs += '<input type="hidden" name="' + name + '" value="' + value + '">'
    });

    $('<form class="hidden" action="' + href +'" method="post" target="' + target + '">' + inputs +'</form>').appendTo(_.body).submit();
}
