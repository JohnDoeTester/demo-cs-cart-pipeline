import { Tygh } from "../..";
import $ from "jquery";

const _ = Tygh;

export const methods = {
    init: function () {
        if (typeof _.deferred_scripts === 'undefined' || _.deferred_scripts.length === 0) {
            return;
        }

        _.deferred_scripts.map((scriptData) => {
            if ((scriptData.readyState === 'loading')
                || (scriptData.readyState === 'complete'
                    && (typeof scriptData.event_suffix === 'undefined' || scriptData.event_suffix === '')
                )
            ) {
                return false;
            }
            scriptData.readyState = 'loading';

            setTimeout(() => {
                (scriptData.readyState === 'complete')
                    ? methods.trigger(scriptData)
                    : ((function () {
                        $.getScript(scriptData.src, () => {
                            scriptData.readyState = 'complete';
                            if (typeof scriptData.event_suffix === 'undefined' || scriptData.event_suffix === '') {
                                return;
                            }
                            methods.trigger(scriptData);
                        })
                    })());
            }, scriptData.delay || 3000);
        });
    },

    trigger: function (data) {
        if (data.callback) {
            data.callback(data, data.event_suffix, data.readyState);
        }
        $.ceEvent('trigger', `ce.lazy_script_load_${data.event_suffix}`, [data, data.event_suffix, data.readyState]);
    },

    add: function (data) {
        if (typeof data.src === 'undefined'
            || data.src === ''
            || (data.event_suffix
                && _.deferred_scripts.some(script => script.event_suffix === data.event_suffix)
            )
        ) {
            return;
        }
        data.readyState = 'unset';
        _.deferred_scripts.push(data);
    },

    run: function (data) {
        methods.add(data);

        if (document.readyState === 'complete') {
            window.setTimeout($.ceLazyLoader('init'));
        }
    },
};

/**
 * Lazy loader
 * @param {JQueryStatic} $ 
 *
 * Usage:
 * $.ceLazyLoader({
 *     src: 'https://example.com/script.js',
 *     event_suffix: 'script_id',
 *     callback: function () {
 *         ...
 *     },
 * });
 */
export const ceLazyLoaderInit = function ($) {
    $.ceLazyLoader = function (method) {
        if (typeof method !== 'undefined' && $.isPlainObject(method) && method.src) {
            return methods.run.apply(this, arguments);
        } else if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method !== 'undefined' && $.isPlainObject(method) && typeof method.src === 'undefined') {
            $.error('ty.lazyloader: src in $.ceLazyLoader does not exist');
        } else {
            $.error('ty.lazyloader: method ' + method + ' does not exist');
        }
    };
}
