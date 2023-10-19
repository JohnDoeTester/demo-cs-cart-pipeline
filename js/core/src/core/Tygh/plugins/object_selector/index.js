import {Tygh} from '../..';
import $ from "jquery";

const _ = Tygh;

var plugin_name = "ceObjectSelector",
    defaults = {
        pageSize: 10,
        enableSearch: true,
        closeOnSelect: true,
        loadViaAjax: false,
        dataUrl: null,
        enableImages: false,
        imageWidth: 20,
        imageHeight: 20,
        placeholder: null,
        allowClear: false,
        debug: false,
        autofocus: false,
        dropdownCssClass: '',
        delay: 0
    };

function ObjectSelector(element, options) {
    this.$el = $(element);
    this.settings = $.extend({}, defaults, options);

    this.init();
}

$.extend(ObjectSelector.prototype, {
    init: function () {
        var data = this.$el.data();
        
        this.settings.bulkEditMode = data.caBulkEditMode || false;
        this.settings.dropdownParent = data.caDropdownParent || false;
        this.settings.placeholder = data.caPlaceholder || this.settings.placeholder;
        this.settings.pageSize = data.caPageSize || this.settings.pageSize;
        this.settings.dataUrl = data.caDataUrl || this.settings.dataUrl;
        this.settings.loadViaAjax = data.caLoadViaAjax === undefined
            ? this.settings.loadViaAjax
            : data.caLoadViaAjax;
        this.settings.closeOnSelect = data.caCloseOnSelect === undefined
            ? this.settings.closeOnSelect
            : data.caCloseOnSelect;
        this.settings.enableImages = data.caEnableImages === undefined
            ? this.settings.enableImages
            : data.caEnableImages;
        this.settings.enableSearch = data.caEnableSearch === undefined
            ? this.settings.enableSearch
            : data.caEnableSearch;
        this.settings.imageWidth = data.caImageWidth === undefined
            ? this.settings.imageWidth
            : data.caImageWidth;
        this.settings.imageHeight = data.caImageHeight === undefined
            ? this.settings.imageHeight
            : data.caImageHeight;
        this.settings.multiple = this.settings.multiple === undefined
            ? this.$el.is('[multiple]')
            : this.settings.multiple;
        this.settings.debug = data.debug === undefined
            ? this.settings.debug
            : data.caDebug;
        this.settings.allowClear = data.caAllowClear === undefined
            ? this.settings.allowClear
            : data.caAllowClear;
        this.settings.autofocus = data.caAutofocus === undefined
            ? this.settings.autofocus
            : data.caAutofocus;
        this.settings.dropdownCssClass = data.caDropdownCssClass || this.settings.dropdownCssClass;
        this.settings.delay = data.caAjaxDelay || this.settings.delay;
        this.settings.allowSorting = data.caAllowSorting || false;
        this.settings.escapeHtml = data.caEscapeHtml === undefined
            ? true
            : data.caEscapeHtml;
        this.settings.addTemplateSelectionHook = data.caAddTemplateSelectionHook === undefined
            ? false
            : data.caAddTemplateSelectionHook;
        this.settings.isRequired = data.caRequired === undefined
            ? false
            : data.caRequired;
        this.settings.width = data.caSelectWidth === undefined
            ? false
            : data.caSelectWidth;
        this.settings.repaintDropdownOnChange = data.caRepaintDropdownOnChange || false;
        this.settings.templateType = data.caTemplateType || '';
        this.settings.enableAdd = data.caEnableAdd || false;
        this.settings.templateResultSelector = data.caTemplateResultSelector || '';
        this.settings.templateSelectionSelector = data.caTemplateSelectionSelector || '';
        this.settings.templateResultAddSelector = data.caTemplateResultAddSelector || '';
        this.settings.newValueHolderSelector = data.caNewValueHolderSelector || null;
        this.settings.newValueAllowMultiple = data.caNewValueAllowMultiple || false;

        this.initSelect2(this.settings);
    },
    initSelect2: function (_settings) {
        var self = this, select2config = {
            language: {
                loadingMore: function() {
                    return _.tr('loading');
                },
                searching: function() {
                    return _.tr('loading');
                },
                errorLoading: function() {
                    return _.tr('error');
                },
                noResults: function() {
                    return _.tr('nothing_found');
                }
            },
            closeOnSelect: this.settings.closeOnSelect,
            placeholder: this.settings.placeholder,
            allowClear: this.settings.allowClear,
            multiple: this.settings.multiple,
            dropdownCssClass: this.settings.dropdownCssClass
        };

        // Load variants via AJAX from given URL
        if (this.settings.loadViaAjax && this.settings.dataUrl !== null) {
            select2config.ajax = {
                url: this.settings.dataUrl,
                delay: this.settings.delay,
                data: function (params) {
                    var request = {
                        q: params.term,
                        page: params.page || 1,
                        page_size: self.settings.pageSize
                    };

                    if (self.settings.enableImages) {
                        request.image_width = self.settings.imageWidth;
                        request.image_height = self.settings.imageHeight;
                    }

                    return request;
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.objects,
                        pagination: {
                            more: (params.page * self.settings.pageSize) < data.total_objects
                        }
                    };
                },
                transport: function (params, success, failure) {
                    params.callback = success;
                    params.hidden = true;

                    return $.ceAjax('request', params.url, params);
                }
            };
        }

        if (!this.settings.escapeHtml) {
            select2config.escapeMarkup = function(m) { return m; };
        }

        select2config.templateSelection = function(object, item_elm) {
            if (!object.content) {
                object.content = {
                    text: object.text,
                    append: '',
                };
            }

            var templateSelection = '';
            object.type = self.settings.templateType;
            object.bulkEditMode = _settings.bulkEditMode;
            object.context = object.text;

            if (self.templateResultAddSelector && object.newTag) {
                templateSelection = $(self.settings.templateResultAddSelector).html();
            } else if (self.settings.templateSelectionSelector) {
                templateSelection = $(self.settings.templateSelectionSelector).html();
            }

            const { data } = Object.assign({}, $(object.element).data());

            if (data) {
                object.data = data.data;
            }

            if (typeof object.element !== "undefined" && $(object.element).data('caObjectSelectorItemTemplate')) {
                object.context = $(object.element).data('caObjectSelectorItemTemplate');
            }

            if (templateSelection){
                // Replace all entries
                Object.keys(object.content).forEach(function (item) {
                    templateSelection = templateSelection.str_replace(`[${item}]`, object.content[item]);
                });
                object.context = templateSelection;
            }

            $.ceEvent('trigger', 'ce.select_template_selection', [object, item_elm, self.$el]);

            return object.context;
        };

        var elm = this.$el;

        if (this.settings.data) {
            select2config.data = this.settings.data;

            if (Array.isArray(select2config.data)) {
                var options = {};

                elm.find('option').each(function () {
                    options[$(this).val()] = $(this);
                });

                this.settings.data.forEach(function (item) {
                    if (item.id && item.text && options[item.id] && !options[item.id].text()) {
                        options[item.id].text(item.text);
                    }
                });
            }
        }

        if (this.settings.templateSelectionSelector ||
            this.settings.enableImages ||
            this.settings.enableAdd) {
            select2config.templateResult = function(object) {
                if (object.loading) {
                    return object.text;
                }
                if (!object.content) {
                    object.content = {
                        text: object.text,
                        append: '',
                    };
                }
                if (self.settings.enableImages && !object.image_url) {
                    return $('<span>' + object.text + '</span>');
                }
                object.type = self.settings.templateType;
                object.context = object.text;

                var templateResult = '',
                    result = '';

                $.ceEvent('trigger', 'ce.select_template_result', [object, elm]);

                if (self.settings.enableImages && object.image_url) {
                    object.content.append = object.image_url;
                }

                if (self.settings.enableAdd && object.newTag) {
                    templateResult = $(self.settings.templateResultAddSelector).html();
                } else if (self.settings.templateResultSelector) {
                    templateResult = $(self.settings.templateResultSelector).html();
                }

                if (templateResult) {
                    // Replace all entries
                    Object.keys(object.content).forEach(function (item) {
                        const regex = new RegExp(`\\[${item.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&')}\\]`, 'g');
                        templateResult = templateResult.replace(regex, object.content[item]);
                    });
                    object.context = templateResult;
                }

                $.ceEvent('trigger', 'ce.change_select_list', [object, elm]);

                if (templateResult) {
                    return $(object.context);
                } else {
                    if (!object.image_url) {
                        return $('<span>' + object.context + '</span>');
                    }

                    return $('<img src="' + object.image_url + '" alt="' + object.text + '" /><span>' + object.context + '</span>');
                }
            };
        }

        if (!this.settings.enableSearch) {
            select2config.minimumResultsForSearch = Infinity;
        }

        if (this.settings.width) {
            select2config.width = this.settings.width;
        }

        if (this.settings.repaintDropdownOnChange) {
            this.$el.on('select2:select select2:unselect', function () {
                var select2 = $(this).data('select2');

                if (select2.isOpen()) {
                    select2.dropdown._positionDropdown();
                }
            });
        }

        if (this.settings.dropdownParent) {
            select2config.dropdownParent = $(this.settings.dropdownParent);
        }

        if (this.settings.enableAdd) {
            
            select2config.tags = true;
            select2config.createTag = function (params) {
                var term = $.trim(params.term);

                if (term === '') {
                    return null;
                }

                var object = {
                    id: term,
                    text: term,
                    newTag: true,
                    type: self.settings.templateType,
                    content: {
                        text: term,
                    }
                };

                $.ceEvent('trigger', 'ce.select2.create_tag', [elm, object, term]);

                if (!object.content.append) {
                    object.content.append = '';
                }

                return object;
            }
        }

        elm.select2(select2config);

        $.ceEvent('on', 'ce.window.resize', function (event, args) {
            var $select2Wrapper = elm.parent();
            if ($select2Wrapper.hasClass('select2-wrapper--width-auto')) {
                return;
            }

            $select2Wrapper.find('input.select2-search__field, .select2-container')
                    .css({ width: '100%' });
        });

        $.ceEvent('on', 'ce.tab.show', function (event, args) {


            if ($(window).width() <= 768) {
                
                elm.parent().find('input.select2-search__field, .select2-container')
                        .css({ width: '100%' }); 
            }
        });

        if (this.settings.allowSorting) {
            this.$el.select2Sortable();
        }

        if (this.settings.autofocus) {
            this.$el.select2('focus');
        }

        elm.on('select2:select', function (event) {
            var selectedItem = event.params.data;
            if (self.settings.newValueHolderSelector) {
                if (self.settings.newValueAllowMultiple) {
                    if (selectedItem.newTag) {
                        var $lastNewVariant = $(self.settings.newValueHolderSelector).last();
                        var $newVariant = $lastNewVariant.val()
                            ? $lastNewVariant.clone()
                            : $lastNewVariant;
                        $newVariant.val(selectedItem.id);
                        $newVariant.insertAfter($lastNewVariant);
                    }
                } else if (selectedItem.newTag) {
                    $(self.settings.newValueHolderSelector).val(selectedItem.id);
                } else {
                    $(self.settings.newValueHolderSelector).val(null);
                }
            }

            $.ceEvent('trigger', 'ce.select2.select', [elm, selectedItem, event]);
        });

        elm.on('select2:unselect', function (event) {
            var unselectedItem = event.params.data;
            if (self.settings.newValueHolderSelector && unselectedItem.newTag) {
                var $newVariants = $(self.settings.newValueHolderSelector);
                if ($newVariants.length > 1) {
                    $newVariants.each(function(i, newVariant) {
                        var $newVariant = $(newVariant);
                        if ($newVariant.val() === unselectedItem.id) {
                            $newVariant.remove();
                        }
                    });
                } else {
                    $newVariants.val(null);
                }
            }

            $.ceEvent('trigger', 'ce.select2.unselect', [elm, event]);
        });

        $.ceEvent('trigger', 'ce.select2.init', [elm]);
    }
});

/**
 * Object Selector [Select2]
 * @param {JQueryStatic} $ 
 */
export const ceObjectSelectorInit = function ($) {
    $.fn[plugin_name] = function (options) {
        var self = this, createPluginInstances = function () {
            var MultipleSelection = $.fn.select2.amd.require('select2/selection/multiple'),
                SelectionSearch = $.fn.select2.amd.require('select2/selection/search'),
                base_bind = MultipleSelection.prototype.bind;

            MultipleSelection.prototype.bind = function (container, $container) {
                this.$selection.on('click', function (e) {
                    if (!$(e.target).hasClass('select2-search__field') && !$(e.target).hasClass('select2-selection__rendered')) {
                        // disable rendering dropdown if click was not on the search field
                        e.stopImmediatePropagation();
                    }
                });

                base_bind.apply(this, arguments);
            };

            SelectionSearch.prototype.searchRemoveChoice = function () {
                // prevent selected option from deletion (when pressing backspace and search box is empty)
                return false;
            };

            return self.each(function () {
                if ($.data(this, "plugin_" + plugin_name)) {
                    var objectSelector = $.data(this, "plugin_" + plugin_name);
                    objectSelector.settings = $.extend({}, defaults, options);
                    objectSelector.init();
                } else {
                    $.data(this, "plugin_" + plugin_name, new ObjectSelector(this, options));
                }
            });
        };

        if (this.length) {
            if ($.fn.select2) {
                return createPluginInstances();
            } else {
                $.getScript('js/lib/select2/dist/js/select2.full.min.js', function () {
                    createPluginInstances();
                });
            }
        }

        return this;
    };
}
