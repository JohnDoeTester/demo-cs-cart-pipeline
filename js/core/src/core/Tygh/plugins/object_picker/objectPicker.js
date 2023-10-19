import { Tygh } from '../..';
import { ObjectStorage } from './objectStorage';
import { BaseMultipleDecorator } from "./decorators/selection/baseMultipleDecorator";
import { ExternalContainerDecorator } from "./decorators/selection/externalContainerDecorator";
import { HideSelectionDecorator } from "./decorators/selection/hideSelectionDecorator";
import { PredefinedVariantsDecorator } from "./decorators/data/predefinedVariantsDecorator";
import { BaseResultDecorator } from "./decorators/data/baseResultDecorator";
import $ from "jquery";

const _ = Tygh;

export class ObjectPicker {
    constructor($elem, options) {
        this.$elem = $elem;
        this.options = options;

        this.tryLoadFromStorage();

        let select2 = this.$elem.select2(this.buildSelect2Options());

        if (this.options.allowSorting) {
            this.$elem.select2Sortable();
        }

        if (this.options.autofocus) {
            this.$elem.select2('focus');
        }

        if (this.options.autoopen) {
            this.openDropdown();
        }

        this.bindEvents();
        this.fireEvent('inited');
    }

    destroy() {
        this.$elem.select2('destroy');
    }

    resize() {
        let $select2Wrapper = this.$elem.parent();

        if ($select2Wrapper.hasClass('select2-wrapper--width-auto')) {
            return;
        }

        $select2Wrapper
            .find('input.select2-search__field, .select2-container')
            .css({ width: '100%' });

        this.fireEvent('resized');
    }

    getSelectedObjectIds() {
        let ids = this.$elem.val();

        if (!Array.isArray(ids)) {
            ids = [ids];
        }

        return ids;
    }

    setSelectedObjectIds(value) {
        this.$elem.val(value);
        this.$elem.trigger('change');
    }

    selectObjectId(value) {
        if (this.isMultiple()) {
            let ids = new Set(this.getSelectedObjectIds());
            ids.add(value);

            value = Array.from(ids);
        }

        this.setSelectedObjectIds(value);
    }

    unselectObjectId(value) {
        if (this.isMultiple()) {
            let ids = new Set(this.getSelectedObjectIds());
            ids.delete(String(value));

            value = Array.from(ids);
        } else {
            value = null;
        }

        this.setSelectedObjectIds(value);
    }

    addObjects(objects, selected = true, load = true) {
        let self = this,
            objectIds = new Set();

        objects.forEach(function (object) {
            let $option = self.$elem.find(`option[value="${object.id}"]`);

            if (!$option.length) {
                let option = new Option(object.text, object.id, selected, selected);
                self.$elem.append(option);

                if (!load) {
                    object.loaded = true;
                    $(option).data('data', object);
                }
            } else if (selected) {
                $option.prop('selected', true);
            }

            objectIds.add(object.id);
        });

        this.$elem.trigger('change');

        if (this.isAjaxSource() && load) {
            ObjectPicker.loadObjects($([this.$elem]), this.options.objectType, objectIds);
        }
    }

    updateObjects(objects) {
        let self = this,
            select2 = this.$elem.data('select2');

        objects.forEach(function (object) {
            let $option = self.$elem.find(`option[value="${object.id}"]`),
                currentData = $option.data('data') || {},
                data = $.extend({}, currentData, object);

            currentData.isChanged = data.isChanged = false;
            data.isChanged = JSON.stringify(currentData) !== JSON.stringify(data);

            $option.text(object.text);
            $option.data('data', data);
            $option.removeAttr('data-select2-id');
        });

        if (select2) {
            select2.dataAdapter.current(function (data) {
                select2.trigger('selection:update', {
                    data: data
                });
            });
        }
    }

    openDropdown() {
        this.$elem.select2('open');
    }

    closeDropdown() {
        this.$elem.select2('close');
    }

    isMultiple() {
        return this.$elem.is('[multiple]');
    }

    isCreateObjectAvailable() {
        return Boolean(this.options.enableCreateObject);
    }

    isAjaxSource() {
        return Boolean(this.options.ajaxUrl);
    }

    getObjectType() {
        return this.options.objectType;
    }

    extendSearchRequestData(data) {
        this.options.searchRequestData = $.extend({}, this.options.searchRequestData, data);
    }

    isInited() {
        return this.$elem.data('caObjectPickerInited') === true;
    }

    isDropdownOpen() {
        return this.$elem.data('select2').isOpen();
    }

    fireEvent(event, ...params) {
        this.$elem.trigger(`ce:object_picker:${event}`, [this, ...params]);
        $.ceEvent('trigger', `ce.object_picker.${event}`, [this, ...params]);
    }

    buildSelect2Options() {
        let self = this;
        let options = {
            width: this.options.width,
            allowClear: this.options.allowClear,
            closeOnSelect: this.options.closeOnSelect,
            containerCssClass: this.options.containerCssClass,
            dropdownCssClass: this.options.dropdownCssClass,
            showDropdown: this.options.showDropdown,
            language: {
                loadingMore: function () {
                    return _.tr(self.options.languageLoadingMore);
                },
                searching: function () {
                    return _.tr(self.options.languageSearching);
                },
                errorLoading: function () {
                    return _.tr(self.options.languageErrorLoading);
                },
                noResults: function () {
                    return _.tr(self.options.languageNoResults);
                }
            },
            maximumInputLength: this.options.maximumInputLength,
            maximumSelectionLength: this.options.maximumSelectionLength,
            minimumInputLength: this.options.minimumInputLength,
            minimumResultsForSearch: this.options.enableSearch ? this.options.minimumResultsForSearch : Infinity,
            externalContainerSelector: this.options.externalContainerSelector,
            unremovableItemIds: this.options.unremovableItemIds,
            enablePermanentPlaceholder: this.options.enablePermanentPlaceholder,
            placeholder: {
                id: this.options.placeholderValue,
                text: this.options.placeholder,
                loaded: true,
                data: {
                    name: this.options.placeholder,
                },
            },
            selectOnClose: this.options.selectOnClose,
            templateResult: function (object) {
                return self.renderResultItemTemplate(object);
            },
            templateSelection: function (object, container) {
                return self.renderSelectionItemTemplate(object, container);
            },
            predefinedVariants: this.options.predefinedVariants,
            tokenSeparators: this.options.tokenSeparators
        };

        if (!this.options.showDropdown) {
            options.containerCssClass += ' ' + this.options.containerHideCssClass + ' ';
            options.dropdownCssClass += ' ' + this.options.dropdownHideCssClass + ' ';
        }

        if (this.options.dropdownParentSelector) {
            options.dropdownParent = $(this.options.dropdownParentSelector);
        }

        if (this.options.hasStrictComplianceMatcher) {
            options.matcher = function (params, data) {
                if ($.trim(params.term) === '') {
                    return data;
                }

                if (typeof data.text === 'undefined') {
                    return null;
                }

                if (data.text.toUpperCase() == params.term.toUpperCase()) {
                    return data
                }
                return null;
            }
        }

        if (this.isAjaxSource()) {
            options.ajax = {
                url: this.options.ajaxUrl,
                delay: this.options.ajaxDelay,
                data: function (params) {
                    let request = {
                        q: params.term,
                        page: params.page || 1,
                        page_size: self.options.ajaxPageSize
                    };

                    if (self.options.searchRequestData) {
                        request = $.extend({}, self.options.searchRequestData, request);
                    }

                    return request;
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    data.objects = data.objects || [];

                    data.objects.forEach(function (object) {
                        object = ObjectStorage.normalizeObject(object);
                        object.loaded = true;
                    });

                    return {
                        results: data.objects,
                        pagination: {
                            more: (params.page * self.options.ajaxPageSize) < data.total_objects
                        }
                    };
                },
                transport: function (params, success, failure) {
                    ObjectStorage.find(params.url, self.options.objectType, params)
                        .done(success)
                        .fail(failure);
                }
            }
        }

        if (this.isCreateObjectAvailable()) {
            options.tags = true;
            options.createTag = function (params) {
                return self.createNewObjectCallback(params);
            }

            options.insertTag = function (data, tag) {
                return self.insertNewObjectCallback(data, tag);
            }
        }

        if (!this.options.escapeHtml) {
            options.escapeMarkup = function (m) { return m; };
        }

        let Options = $.fn.select2.amd.require('select2/options'),
            Utils = $.fn.select2.amd.require('select2/utils'),
            opts = new Options(options, this.$elem);

        if (this.isMultiple()) {
            let selectionAdapter = opts.get('selectionAdapter');

            selectionAdapter = Utils.Decorate(selectionAdapter, BaseMultipleDecorator);

            if (this.options.externalContainerSelector) {
                selectionAdapter = Utils.Decorate(selectionAdapter, ExternalContainerDecorator);
            }

            opts.set('selectionAdapter', selectionAdapter);
        }

        if (this.options.hideSelection) {
            let selectionAdapter = opts.get('selectionAdapter');

            selectionAdapter = Utils.Decorate(selectionAdapter, HideSelectionDecorator);
            opts.set('selectionAdapter', selectionAdapter);
        }

        if (this.options.predefinedVariants.length) {
            let dataAdapter = opts.get('dataAdapter');

            dataAdapter = Utils.Decorate(dataAdapter, PredefinedVariantsDecorator);

            opts.set('dataAdapter', dataAdapter);
        }

        let resultAdapter = opts.get('resultsAdapter');
        resultAdapter = Utils.Decorate(resultAdapter, BaseResultDecorator);
        opts.set('resultsAdapter', resultAdapter);

        return opts.options;
    }

    renderTemplate(data, template) {
        let templater = new Function('data', `return \`${template}\`;`);

        return templater(data);
    }

    getItemTemplate(selector) {
        return $(selector).html();
    }

    renderResultItemTemplate(object) {
        if (object.loading) {
            return object.text;
        }

        let template;

        object.data = object.data || {};
        object.data.text = object.text;

        if (object.isPredefined && this.options.templateResultPredefinedSelector) {
            template = this.renderTemplate(object.data, this.getItemTemplate(this.options.templateResultPredefinedSelector));
        } else if (object.isNew && this.options.templateResultNewSelector) {
            template = this.renderTemplate(object.data, this.getItemTemplate(this.options.templateResultNewSelector));
        } else if (this.options.templateResultSelector) {
            template = this.renderTemplate(object.data, this.getItemTemplate(this.options.templateResultSelector));
        }

        if (!template) {
            template = object.text;
        }

        this.fireEvent('init_template_result_item', object, template);

        return template;
    }

    renderSelectionItemTemplate(object, container) {
        let template;

        object.data = object.data || {};
        object.data.text = object.text;

        if (object.isPredefined && this.options.templateSelectionPredefinedSelector) {
            template = this.renderTemplate(object.data, this.getItemTemplate(this.options.templateSelectionPredefinedSelector));
        } else if (object.isNew && this.options.templateSelectionNewSelector) {
            template = this.renderTemplate(object.data, this.getItemTemplate(this.options.templateSelectionNewSelector));
        } else if (this.isAjaxSource() && !object.loaded && this.options.templateSelectionLoadSelector) {
            template = this.renderTemplate(object.data, this.getItemTemplate(this.options.templateSelectionLoadSelector));
        } else if (this.options.templateSelectionSelector) {
            template = this.renderTemplate(object.data, this.getItemTemplate(this.options.templateSelectionSelector));
        }

        if (!template) {
            template = object.text;
        }

        if (this.options.allowSorting) {
            $(container).data('optionId', object.id ? object.id : 0);
        }

        this.fireEvent('init_template_selection_item', object, template, container);

        return template;
    }

    createNewObjectCallback(params) {
        let term = $.trim(params.term);
        params.enableCreateNewObject = true;

        this.fireEvent('before_create_object', params, object);

        if (term === '' || !params.enableCreateNewObject) {
            return null;
        }

        let object = {
            id: term,
            text: term,
            isNew: true,
            content: {
                text: term,
            },
            data: {
                name: term
            }
        };

        this.fireEvent('create_object', params, object);

        return object;
    }

    insertNewObjectCallback(data, tag) {
        this.fireEvent('before_insert_object', data, tag);

        if (this.options.createObjectToEnd) {
            data.push(tag);
        } else {
            data.unshift(tag);
        }

        this.fireEvent('insert_object', data, tag);
    }

    bindEvents() {
        let self = this,
            Clear = $.fn.select2.amd.require('select2/selection/allowClear'),
            KEYS = $.fn.select2.amd.require('select2/keys'),
            Utils = $.fn.select2.amd.require('select2/utils');

        Clear.prototype._handleKeyboardClear = function (_, e, container) {
            if (container.isOpen()) {
                return;
            }
          
            if (e.which == KEYS.DELETE) {
                this._handleClear(e);
            } else if (e.which == KEYS.BACKSPACE) {
                if (container.$element[0].lastChild) {
                    let $clear = this.$selection.find('.select2-selection__clear'),
                        data = Utils.GetData($clear[0], 'data');

                    container.$element.trigger({
                        type: 'select2:unselect',
                        params: {
                            data: data[data.length-1]
                        }
                    });
                    container.$element[0].lastChild.remove();
                }
            }
        };

        if (this.options.redrawDropdownOnChange) {
            this.$elem.on('select2:select select2:unselect', function () {
                var select2 = $(this).data('select2');

                if (select2.isOpen()) {
                    select2.dropdown._positionDropdown();
                }
            });
        }

        this.$elem.on('select2:select', function (event) {
            let object = event.params.data;

            if (self.options.createdObjectHolderSelector) {
                if (self.options.allowMultipleCreatedObjects) {
                    if (object.isNew) {
                        let $lastNewVariant = $(self.options.createdObjectHolderSelector).last();
                        let $newVariant = $lastNewVariant.val()
                            ? $lastNewVariant.clone()
                            : $lastNewVariant;

                        $newVariant.val(object.id);
                        $newVariant.insertAfter($lastNewVariant);
                    }
                } else if (object.isNew) {
                    $(self.options.createdObjectHolderSelector).val(object.id);
                } else {
                    $(self.options.createdObjectHolderSelector).val(null);
                }
            }

            self.fireEvent('object_selected', object, event);
        });

        this.$elem.on('select2:unselect', function (event) {
            var object = event.params.data;

            if (self.options.createdObjectHolderSelector && object.isNew) {
                var $newVariants = $(self.options.createdObjectHolderSelector);

                if ($newVariants.length > 1) {
                    $newVariants.each(function(i, newVariant) {
                        let $newVariant = $(newVariant);
                        if ($newVariant.val() === object.id) {
                            $newVariant.remove();
                        }
                    });
                } else {
                    $newVariants.val(null);
                }
            }

            self.fireEvent('object_unselected', object, event);
        });

        this.$elem.on('change', function () {
            let $options = $(this).find('option:selected'),
                selected = [];

            $options.each(function () {
                selected.push(Utils.GetData(this, 'data'));
            });

            self.fireEvent('change', self.isMultiple() ? selected : selected.shift());
        });

        this.$elem.on('select2:open', function () {
            self.fireEvent('dropdown_opened');
        });

        this.$elem.on('select2:close', function () {
            self.fireEvent('dropdown_closed');
        });

        this.$elem.on('select2:clear', function () {
            self.fireEvent('cleared');
        });

        $.ceEvent('on', 'ce.window.resize', function (event, args) {
            self.resize();
        });

        $.ceEvent('on', 'ce.tab.show', function(event, args) {
            self.resize();
        });

        if (this.options.extendedPickerId) {
            $.ceEvent('on', 'ce.picker_add_js_items', function (picker, items, data) {
                if (self.options.extendedPickerId !== data.root_id) {
                    return;
                }

                let objects = [];

                $.map(items, function (data, id) {
                    if (data instanceof Object) {
                        var text = data[self.options.extendedPickerTextKey];
                    } else {
                        var text = data;
                    }

                    objects.push({
                        id: id,
                        text: text,
                        loaded: !self.isAjaxSource(),
                        extended_picker_data: data,
                        data: {}
                    });
                });

                if (objects.length) {
                    self.addObjects(objects);
                }
            });

            $.ceEvent('on', 'ce.picker_delete_js_items', function (picker, items, data) {
                if (self.options.extendedPickerId !== data.root_id) {
                    return;
                }

                $.map(items, function (data, id) {
                    self.unselectObjectId(id);
                });
            });
        }

        this.$elem.data('select2').on('selection:update', function () {
            self.fireEvent('selection_before_update');

            if (self.options.externalContainerSelector) {
                let $parentWithHideInputs = self.$elem.closest('.cm-hide-inputs');

                if ($parentWithHideInputs.length) {
                    $(self.options.externalContainerSelector).disableFields();
                }
            }

            self.fireEvent('selection_updated');
        });
    }

    tryLoadFromStorage() {
        if (!this.isAjaxSource()) {
            return;
        }

        let objects = ObjectStorage.mget(this.options.objectType, this.getSelectedObjectIds());

        if (objects.length) {
            this.updateObjects(objects);
        }
    }

    static loadObjects($elems, objectType, objectIds) {
        objectIds = Array.from(objectIds);
        let options = $elems.get(0).data('caObjectPicker').options;

        ObjectStorage.load(options.ajaxUrl, objectType, objectIds, options.ajaxMethod).done(function (map) {
            $elems.each(function (key, $elem) {
                if (!$elem.data('caObjectPicker')) {
                    return;
                }

                let picker = $elem.data('caObjectPicker'),
                    selectedIds = picker.getSelectedObjectIds(),
                    objects = [];

                $.each(selectedIds, function (key, id) {
                    if (map[id]) {
                        objects.push(map[id]);
                    }
                });

                if (objects.length) {
                    picker.updateObjects(objects);
                }
            });
        });
    }
}
