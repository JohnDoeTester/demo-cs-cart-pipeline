import * as methods from "./Tygh/methods";
import * as coreMethods from "./Tygh/core_methods";
import { registerAllPlugins } from "./Tygh/plugins";

/**
 * Namespace initialization
 * @param {Tygh} _ Main namespace
 * @param {JQueryStatic} $ jQuery
 */
export const namespaceInitializer = function (_, $) {
    // Copy given jQuery to namespace
    _.$ = $;

    // Define utility functions
    $.fn.extend({
        select2Sortable: methods.select2Sortable,
        toggleBy: methods.toggleBy,
        moveOptions: methods.moveOptions,
        swapOptions: methods.swapOptions,
        selectOptions: methods.selectOptions,
        alignElement: methods.alignElement,
        formIsChanged: methods.formIsChanged,
        fieldIsChanged: methods.fieldIsChanged,
        disableFields: methods.disableFields,
        click: methods.click,
        switchAvailability: methods.switchAvailability,
        serializeObject: methods.serializeObject,
        positionElm: methods.positionElm
    });

    // Define core methods
    $.extend(coreMethods);

    // Register plugins
    registerAllPlugins($);

    /**
     * Bind css-classes to body for tracking web-page width
     */
    (function($) {

        var _first       = true,
            _widths      = {
                'screen--xs':       [0, 350],
                'screen--xs-large': [350, 480],
                'screen--sm':       [481, 767],
                'screen--sm-large': [768, 1024],
                'screen--md':       [1024, 1280],
                'screen--md-large': [1280, 1440],
                'screen--lg':       [1440, 1920],
                'screen--uhd':      [1920, 9999]
            };

        var windowResizeHandler = function (event) {

            var classes = {
                    old: '',
                    new: ''
                },
                windowWidth = $(window).width();

            for (let className in _widths) {
                if ($('body').hasClass(className)) {
                    classes.old = className;
                    $('body').removeClass(className);
                }

                var width = _widths[className];
                if ((windowWidth >= width[0]) && (windowWidth <= width[1])) {
                    $('body').addClass(className);
                    classes.new = className;
                }
            }

            $.ceEvent('trigger', 'ce.window.resize', [event, classes]);

            if (_first) {
                _first = false;
                $.ceEvent('trigger', 'ce.responsive_classes.ready', []);
            }
        }

        // Debounce wrapper for windowResizeHandler
        var windowResizeHandlerDebounced = $.debounce(windowResizeHandler);

        $.ceEvent('on', 'ce.commoninit', () => {
            // bind onresize event handler to web page
            $(window).on('resize', windowResizeHandlerDebounced);

            // one-time setting class to body
            $(window).trigger('resize');
        });

    })($);

    // Post initialization
    // If page is loaded with URL in hash parameter, redirect to this URL
    if (!_.embedded && location.hash && decodeURIComponent(location.hash).indexOf('#!/') === 0) {
        var components = $.parseUrl(location.href)
        var uri = $.ceHistory('parseHash', location.hash);
        $.redirect(components.protocol + '://' + components.host + components.directory + uri);
    }
}
