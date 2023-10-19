import { Tygh } from '../..';
import { defaultOptions } from './defaultOptions';
import { ObjectPicker } from "./objectPicker";
import $ from "jquery";

const _ = Tygh;
let isBindedGlobalEvents = false;

function execute($elems, method, ...params) {
    $elems.each(function () {
        let $elem = $(this),
            picker = $elem.data('caObjectPicker');

        if (!picker || typeof picker[method] !== 'function') {
            return;
        }

        picker[method](...params);
    });
}

function init($elems, options) {
    if (!$elems.length) {
        return;
    }

    loadSelect2().done(function () {
        let objectsMap = {},
            elemsMap = {};

        bindGlobalEvents();

        $elems.each(function () {
            let $elem = $(this);
            
            lazyLoadPrepare($elem);

            let options = $.extend({}, defaultOptions, getOptions($elem, defaultOptions), options);

            if (options.lazyLoad) {
                return;
            }

            let picker = new ObjectPicker($elem, options);

            $elem.data('caObjectPicker', picker);

            if (picker.isAjaxSource()) {
                if (!objectsMap[picker.options.objectType]) {
                    objectsMap[picker.options.objectType] = new Set();
                }

                if (!elemsMap[picker.options.objectType]) {
                    elemsMap[picker.options.objectType] = [];
                }

                picker.getSelectedObjectIds().forEach(function (v) {
                    if (v && v != 0) {
                        objectsMap[picker.options.objectType].add(v);
                    }
                });

                elemsMap[picker.options.objectType].push($elem);
            }
        });

        $.each(objectsMap, function (objectType, objectIds) {
            if (objectIds.size) {
                ObjectPicker.loadObjects($(elemsMap[objectType]), objectType, objectIds);
            }
        });
    });
}

function loadSelect2() {
    var defer = $.Deferred();

    if (!$.fn.select2) {
        $.getScript('js/lib/select2/dist/js/select2.full.min.js', defer.resolve);
    } else {
        defer.resolve();
    }

    return defer.promise();
}

function getOptions($elem) {
    var options = {};

    for (var key in defaultOptions) {
        options[key] = $elem.data($.camelCase(`ca-object-picker-${key}`));
    }

    return options;
}

function bindGlobalEvents() {
    if (isBindedGlobalEvents) {
        return;
    }
    $(_.doc).on('click', '[data-ca-object-picker="lazyLoadField"],[data-ca-object-picker="lazyLoadClear"]', function () {
        $('.cm-object-picker:first-child', $(this).closest('[data-object-picker]')).ceObjectPicker();
    });
    isBindedGlobalEvents = true;
}

function lazyLoadPrepare($elem) {
    const $lazyLoad = $('[data-ca-object-picker="lazyLoad"]', $elem.closest('[data-object-picker]'));

    if ($('[data-ca-object-picker="lazyLoadField"]', $lazyLoad).is(':checked')) {
        $elem.data({
            'caObjectPickerLazyLoad': false,
            'caObjectPickerAutoopen': true,
        });
        $lazyLoad.remove();
    }

    if ($('[data-ca-object-picker="lazyLoadClear"]', $lazyLoad).is(':checked')) {
        $elem.data('caObjectPickerLazyLoad', false);
        $lazyLoad.remove();
        const newSelectedValues = ($elem.attr('multiple'))
            ? $elem.val().filter(item => item !== ('' + $(this).data('caObjectPickerLazyLoadSelect2Id')))
            : null;
        $elem.val(newSelectedValues).trigger('change');
    }
}

/**
 * @param {JQueryStatic} $
 */
export const ceObjectPickerInit = function ($) {
    $.fn.ceObjectPicker = function (method, ...params) {
        if (!method || method === 'init') {
            init($(this), params[0] || {}, defaultOptions);
        } else {
            execute($(this), method, ...params);
        }

        return $(this);
    };
}