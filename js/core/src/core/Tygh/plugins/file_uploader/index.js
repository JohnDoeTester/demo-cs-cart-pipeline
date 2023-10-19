import { Tygh } from "../..";
import $ from "jquery";

const _ = Tygh;

var UploadedFilesContainer = function () {};

UploadedFilesContainer.prototype = Object.create(Array.prototype, {
    orderFilesByPosition: {
        value: function () {
            this.sort(function (a, b) {
                if (a.tygh.position < b.tygh.position) {
                    return -1;
                } else if (a.tygh.position > b.tygh.position) {
                    return 1;
                }

                return 0;
            });

            return this;
        },
            enumerable: false
        },
    getFirstActive: {
        value: function () {
            this.orderFilesByPosition();

            for (var i = 0; i < this.length; i++) {
                if (!this[i].removed) {
                    return this[i];
                }
            }

            return false;
        },
        enumerable: false
    },
    getMainFile: {
        value: function () {
            for (var i = 0; i < this.length; i++) {
                if (this[i].image_type === 'M') {
                    return this[i];
                }
            }

            return false;
        },
        enumerable: false
    },
    isUploadingInProgress: {
        value: function () {
            for (var i = 0; i < this.length; i++) {
                if (this[i].status === 'uploading') {
                    return true;
                }
            }

            return false;
        },
        enumerable: false
    }
});

/**
 * FileUploader widget constructor
 *
 * @param $el       jQuery object with "cm-file-uploader" class
 * @param $context  Context jQuery object
 *
 * @constructor
 */
var FileUploader = _.FileUploader = function ($el, $context) {
    this.$el = $el;
    this.el = $el.get(0);
    this.$context = $context;
    this.files = new UploadedFilesContainer();
};

$.extend(FileUploader.prototype, {
    options: {},
    elements: {},
    newAttachedFilesCounter: 0,
    orderPositionCounter: null,

    init: function () {
        this.initOptions();
        this.lookupDomElements();

        var formFileuploaderInstances = collectFormFileuploaders(this.elements.$parentForm);
        formFileuploaderInstances.push(this);
        saveFormFileuploaders(this.elements.$parentForm, formFileuploaderInstances);

        this.initDropzone();
        this.bindEvents();
        this.registerExistingFiles();
    },

    initOptions: function () {
        this.options.thumbnailWidth = this.$el.data('caThumbnailWidth');
        this.options.thumbnailHeight = this.$el.data('caThumbnailHeight');
        this.options.uploadUrl = this.$el.data('caUploadUrl');
        this.options.maxFileSize = this.$el.data('caMaxFileSize');

        this.options.newFilesParamName = this.$el.data('caNewFilesParamName');

        this.options.previewTemplateId = this.$el.data('caTemplateId');
        this.options.existingFiles = this.$el.data('caExistingPairs');
        this.options.maxFilesCount = this.$el.data('caMaxFilesCount');

        this.options.allowSorting = this.$el.data('caAllowSorting');
        this.options.destroyAfterInitializing = this.$el.data('caDestroyAfterInitializing');

        // @TODO move to ImagePairUploader separate class
        this.options.defaultImagePairType = this.$el.data('caDefaultImagePairType');
        this.options.imagePairTypes = this.$el.data('caImagePairTypes');
        this.options.imagePairObjectId = this.$el.data('caImagePairObjectId');
        this.options.existingPairThumbnails = this.$el.data('caExistingPairThumbnails');

        this.options.expandedDropzoneSelector = 'file-uploader__pickers--expanded';
    },

    lookupDomElements: function () {
        // @TODO data-ca-target-form as an alternative
        this.elements.$parentForm = this.$el.parents('form');
        this.elements.$filesContainerEl = $('[data-ca-fileuploader-files-container]', this.$el);
        this.elements.$pickerContainerEl = $('[data-ca-fileupload-picker-container]', this.$el);
        this.elements.$pickerBtnEl = $('[data-ca-fileupload-picker-btn]', this.$el);
        this.elements.$pickerMenuEl = $('[data-ca-fileupload-picker-menu]', this.$el);
        this.elements.$localFilePickerTrigger = $('[data-ca-fileupload-picker-local]', this.$el);
        this.elements.$serverFilePickerTrigger = $('[data-ca-fileupload-picker-server]', this.$el);
        this.elements.$urlFilePickerTrigger = $('[data-ca-fileupload-picker-url]', this.$el);
        this.elements.$removeAllFilesTrigger = $('[data-ca-fileupload-remove-all]', this.$el);
        this.elements.$previewTemplate = $('#' + this.options.previewTemplateId, this.$context);
        this.elements.$uploaderPickers = this.$el.find('.file-uploader__pickers');
    },

    initDropzone: function () {
        var options = {
            url: this.options.uploadUrl,
            thumbnailWidth: this.options.thumbnailWidth,
            thumbnailHeight: this.options.thumbnailHeight,
            paramName: this.options.newFilesParamName + '[]',
            autoProcessQueue: true,
            uploadMultiple: false,
            parallelUploads: 3,
            maxFiles: this.options.maxFilesCount,
            previewsContainer: this.elements.$filesContainerEl.get(0),
            clickable: this.elements.$localFilePickerTrigger.get(0),
            previewTemplate: this.elements.$previewTemplate.html()
        };

        if (this.options.maxFileSize) {
            options.maxFilesize = this.options.maxFileSize;
        }

        this.dropzone = new Dropzone(this.el, options);
    },

    reindexFileOrderPositions: function () {
        var index = 0;
        var self = this;

        this.elements.$filesContainerEl.children().each(function () {
            $(this).data('caNewPosition', index++);
        });

        $.each(this.files, function () {
            var $preview = $(this.previewElement);
            var newPosition = $preview.data('caNewPosition');

            $preview.data('caNewPosition', null);

            this.tygh.position = newPosition;

            this.dynamicData['image-position'] = {
                name: 'position',
                value: newPosition,
                postfix: '_data'
            };

            this.tygh.$sortingPositionInput.val(newPosition);
        });

        self.files.orderFilesByPosition();
        self._changeFilesInputNames();
    },

    _changeFilesInputNames: function () {
        var self = this;

        $.each(self.files, function () {
            if (self._isMainImagePair(this)) {
                self._changeMainFileToAdditional();
                self._markFileAsMain(this);
                self.filesGotChanged = true;
            } else {
                self._markFileAsAdditional(this);
                self.filesGotChanged = true;
            }

            self.refreshPreview(this);
        });
    },

    bindEvents: function () {
        var self = this;

        // Notify user about file uploading being in progress when he leaves/reloads the page
        $(window).on('beforeunload', function(e) {
            if (self.isUploadingInProgress()) {
                return _.tr('file_uploading_in_progress_please_wait');
            }

            if (self.filesGotChanged) {
                return _.tr('file_uploading_in_progress_please_wait');
            }

            return;
        });

        $('.cm-product-save-buttons').on('click', function (event) {
            self.filesGotChanged = false;
            event.preventDefault();
        })

        if (self.options.allowSorting) {
            this.elements.$filesContainerEl.sortable({
                tolerance: 'pointer',
                containment: self.elements.$filesContainerEl,
                cursor: 'move',
                placeholder: 'file-uploader__sortable-placeholder',
                forceHelperSize: true,
                axis: 'xy',
                items: '.file-uploader__file',
                update: function (event, ui) {
                    // We can just re-index positions starting from zero because we're managing all existing files at once
                    self.reindexFileOrderPositions();
                }
            });
        }

        this.elements.$pickerBtnEl.on('click', function (e) {
            var $elm = $(e.target);
            var elms = self.elements;

            var offsetX = (elms.$filesContainerEl.offset().left + elms.$filesContainerEl.outerWidth()) -
                ($elm.offset().left + $elm.outerWidth());
            var widthDropdownMenu = elms.$pickerMenuEl.outerWidth();

            var offsetY = (elms.$filesContainerEl.offset().top + elms.$filesContainerEl.outerHeight()) -
                ($elm.offset().top + $elm.outerHeight());
            var heightDropdownMenu = elms.$pickerMenuEl.outerHeight();

            elms.$pickerMenuEl.toggleClass('pull-right', offsetX < widthDropdownMenu);
            elms.$pickerContainerEl.toggleClass('dropup', offsetY < heightDropdownMenu);
        });

        this.elements.$serverFilePickerTrigger.on('click', function (e) {
            e.preventDefault();
            self.runElfinderFilePickerModal();
        });

        this.elements.$removeAllFilesTrigger.on('click', function () {
            $('.file-uploader__file-button-delete').trigger('removefiles');
        });

        this.elements.$urlFilePickerTrigger.on('click', function (e) {
            e.preventDefault();
            var url = '';

            if (url = prompt(_.tr('url')).trim()) {
                var mockFile = {
                    name: '',
                    size: null,
                    image_type: 'N',
                    dynamicData: {},
                    mock: {
                        type: 'url',
                        value: url
                    }
                };

                mockFile.dynamicData['upload-type'] = {
                    prefix: 'type_',
                    value: 'url',
                    postfix: '_detailed'
                };
                mockFile.dynamicData['upload-file'] = {
                    prefix: 'file_',
                    value: url,
                    postfix: '_detailed'
                };

                self.dropzone.emit('addedfile', mockFile);
                self.dropzone.emit('complete', mockFile);
                self.dropzone.emit('thumbnail', mockFile, url);
            }
        });

        this.dropzone.on('dragover', function (event) {

            if (self._dragTimer == undefined) {
                self._dragTimer = null;
            }

            var dataTransfer = event.dataTransfer;
            var indexOf = dataTransfer.types.indexOf
                ? dataTransfer.types.indexOf('Files') != 1
                : dataTransfer.types.contains('Files');

            if (dataTransfer.types[0] == 'application/x-moz-file') {
                indexOf = true;
            }

            if (dataTransfer.types && (indexOf)) {
                self._expandDropzone();
                window.clearTimeout(self._dragTimer);
            }
        });

        this.dropzone.on('dragleave', function (event) {
            self._dragTimer = window.setTimeout(function() {
                self._shrinkDropzone();
            }, 25);
        });

        this.dropzone.on('complete', function (file) {
            if (self.options.destroyAfterInitializing) {
                self.dropzone.destroy();
            }
        });

        this.dropzone.on('success', function (uploadedFile, serverResponse) {
            if (!serverResponse.local_data) {
                self.dropzone.emit('error', uploadedFile, {error: _.tr('cannot_upload_file')}, serverResponse);
            } else if ('path' in serverResponse.local_data) {
                // @TODO: replace magic strings with constants
                uploadedFile.dynamicData['upload-type'] = {
                    prefix: 'type_',
                    value: 'uploaded',
                    postfix: '_detailed'
                };
                uploadedFile.dynamicData['upload-file'] = {
                    prefix: 'file_',
                    value: serverResponse.local_data.path,
                    postfix: '_detailed'
                };

                if (self._isMainImagePair(uploadedFile)) {
                    self._changeMainFileToAdditional();
                    self._markFileAsMain(uploadedFile);
                }

                self.refreshPreview(uploadedFile);
            }
        });

        this.dropzone.on('sending', function (file, xhr, formData) {
            // Emulate CS-Cart's default AJAX implementation
            formData.append('is_ajax', 1);

            if (_.security_hash && _.security_hash.length) {
                formData.append('security_hash', _.security_hash);
            }
        });

        // The file can be either existing or drag'n'drop or user-selected or Elfinder or URL
        this.dropzone.on('addedfile', function (addedFile) {
            addedFile.tygh = addedFile.tygh || {};
            addedFile.dynamicData = addedFile.dynamicData || {};
            addedFile.dynamicData = addedFile.dynamicData || {};
            addedFile.tygh.index = self.getImageDataIndex(addedFile);

            self._shrinkDropzone();
            self._moveDropzoneToEnd();

            // @TODO: replace magic string
            // new image
            addedFile.image_type = addedFile.original_image_type = 'N';

            addedFile.dynamicData['image-type'] = {
                name: 'type',
                value: self.options.defaultImagePairType,
                postfix: '_data'
            };
            addedFile.dynamicData['image-object-id'] = {
                name: 'object_id',
                value: self.options.imagePairObjectId,
                postfix: '_data'
            };
            addedFile.dynamicData['is-new-file'] = {
                name: 'is_new',
                value: 'Y',
                postfix: '_data'
            };
            addedFile.dynamicData['alt-text-detailed'] = {
                name: 'detailed_alt',
                value: '',
                postfix: '_data'
            };

            if (addedFile.mock) {

                if (addedFile.mock.type === 'existing') {
                    if (addedFile.mock.existingPair) {
                        var sourceImage = addedFile.mock.existingPair.detailed
                            ? addedFile.mock.existingPair.detailed
                            : addedFile.mock.existingPair.icon;

                        addedFile.previewLink = sourceImage.image_path;
                        addedFile.dynamicData['image-pair-id'] = {
                            name: 'pair_id',
                            value: addedFile.mock.existingPair.pair_id,
                            postfix: '_data'
                        };
                        addedFile.dynamicData['alt-text-detailed'] = {
                            name: 'detailed_alt',
                            value: sourceImage.alt ? sourceImage.alt : "",
                            defaultValue: sourceImage.alt ? sourceImage.alt : "",
                            postfix: '_data'
                        };
                        addedFile.dynamicData['is-new-file'] = {
                            name: 'is_new',
                            value: 'N',
                            postfix: '_data'
                        };
                    }

                    addedFile.image_type = addedFile.original_image_type = 'A';
                } else {
                    addedFile.dynamicData['upload-type'] = {
                        prefix: 'type_',
                        value: addedFile.mock.type,
                        postfix: '_detailed'
                    };
                    addedFile.dynamicData['upload-file'] = {
                        prefix: 'file_',
                        value: addedFile.mock.value,
                        postfix: '_detailed'
                    };
                }

            }

            if (self.options.allowSorting) {
                if (addedFile.mock && addedFile.mock.type === 'existing') {
                    addedFile.tygh.position = addedFile.mock.position;
                } else {
                    addedFile.tygh.position = self.orderPositionCounter !== null
                        ? ++self.orderPositionCounter
                        : self.orderPositionCounter = 0;
                }

                addedFile.dynamicData['image-position'] = {
                    name: 'position',
                    value: addedFile.tygh.position,
                    postfix: '_data'
                };
            }

            if (addedFile.mock === undefined || addedFile.mock.type !== 'existing') {
                self.newAttachedFilesCounter++;
            }

            self.files.push(addedFile);

            if (self.newAttachedFilesCounter) {
                self.filesGotChanged = true;
            }

            if (self._isMainImagePair(addedFile)) {
                self._markFileAsMain(addedFile);
            }

            if (self.options.allowSorting) {
                self.elements.$filesContainerEl.sortable('refresh');
            }
            self.refreshPreview(addedFile);
            self.registerCustomRemoveEvent(addedFile);
            self.registerCustomAltUpdateEvent(addedFile);
            self.expandAltTextarea(addedFile);
        });
    },

    getImageDataIndex: function (added_file) {
        var imageDataIndex = 0;

        if (added_file.mock && added_file.mock.type === 'existing') {
            imageDataIndex = added_file.mock.index;
        } else {
            imageDataIndex = this.newAttachedFilesCounter;
        }

        return imageDataIndex;
    },

    registerExistingFiles: function () {
        var self = this;

        this.options.existingFiles.forEach(function (item, index) {
            item.index = index;
        });

        // Although the files list is passed already ordered by file position,
        // jQuery.data() method may weirdly reorder them
        this.options.existingFiles.sort(function (a, b) {
            var result = Number(a.position) - Number(b.position);

            if (result === 0) {
                result = a.index - b.index;
            }

            return result;
        });

        $.each(this.options.existingFiles, function () {
            var pairThumbnails = self.options.existingPairThumbnails[this.pair_id];

            self.orderPositionCounter = Math.max(self.orderPositionCounter, Number(this.position));

            if (this.detailed_id && this.detailed || this.image_id && this.icon) {
                var mockFile = {
                    name: null,
                    size: null,
                    mock: {
                        type: 'existing',
                        index: this.pair_id,
                        position: Number(this.position),
                        existingPair: this
                    }
                };

                self.dropzone.emit('addedfile', mockFile);
                self.dropzone.emit('complete', mockFile);

                if (pairThumbnails.detailed) {
                    self.dropzone.emit('thumbnail', mockFile, pairThumbnails.detailed.image_path);
                } else if (pairThumbnails.icon) {
                    self.dropzone.emit('thumbnail', mockFile, pairThumbnails.icon.image_path);
                }
            }
        });
    },

    expandAltTextarea: function (file) {
        var controlMenuSelector = 'file-uploader__file-control-menu';

        var domNode     = file.previewElement;
        var controlMenu = $(domNode).find('.' + controlMenuSelector);
        var textArea    = $(domNode).find('textarea');

        var toggleAreaExpansion = function () {
            controlMenu.toggleClass(controlMenuSelector + '--expanded');
        }

        controlMenu.on('click', function (event) {
            toggleAreaExpansion();
            textArea.focus().select();
        });

        textArea.on('blur', function (event) {
            toggleAreaExpansion();
        });
    },

    registerCustomRemoveEvent: function (file) {
        var self = this;

        $(file.previewElement).find('[data-ca-dz-remove]').each(function (key, removeLink) {
            $(removeLink).on('click touch removefiles', (function (file) {
                return function (e) {
                    var $preview = $(file.previewElement);
                    var pairId = '';

                    $preview.find('.cm-file-uploader-dynamic-field').prop('disabled', true);
                    $preview.find('.file-uploader__remove-overlay').removeClass('hidden');
                    $preview.find('[data-ca-dz-remove]').hide();
                    $preview.addClass('file-uploader__file--removed');

                    if ('mock' in file
                        && file.mock.type === 'existing'
                    ) {
                        pairId = file.mock.existingPair.pair_id;
                    }

                    file.removed = true;
                    file.dynamicData['image-remove'] = {
                        value: pairId,
                        update_name: false
                    };

                    var firstActiveFile = self.files.getFirstActive();

                    if (self._isMainImagePair(firstActiveFile)) {
                        self._markFileAsMain(firstActiveFile);
                        self.refreshPreview(firstActiveFile);
                    }

                    self.filesGotChanged = true;

                    self._markFileAsAdditional(file);
                    self.refreshPreview(file);
                };
            })(file));
        });

        $(file.previewElement).find('.file-uploader__remove-button-recover').each(function (key, recoverLink) {
            $(recoverLink).on('click touch', (function (file, dz) {
                return function (e) {
                    var $preview = $(file.previewElement);

                    $preview.find('.cm-file-uploader-dynamic-field').prop('disabled', false);
                    $preview.find('.file-uploader__remove-overlay').addClass('hidden');
                    $preview.find('[data-ca-dz-remove]').css('display', '');
                    $preview.removeClass('file-uploader__file--removed');

                    file.removed = false;
                    file.dynamicData['image-remove'] = {
                        value: '',
                        update_name: false
                    };

                    if (self._isMainImagePair(file)) {
                        self._changeMainFileToAdditional();
                        self._markFileAsMain(file);
                    }

                    self.filesGotChanged = true;

                    dz.refreshPreview(file);
                };
            })(file, self));
        });
    },

    registerCustomAltUpdateEvent: function (file) {
        var self = this;

        $(file.previewElement).find('.file-uploader__file-description-input').on('keyup', function () {
            file.dynamicData['alt-text-detailed'].value = $(this).val();
            self.refreshPreview(file);
        });
    },

    refreshPreview: function (previewFile) {
        if ('dynamicData' in previewFile) {
            var self = this;
            var $preview = $(previewFile.previewElement);
            var index = ['[', previewFile.tygh.index, ']'].join('');

            $.each(previewFile.dynamicData, function (selector, data) {
                var targetInput = $preview.find(['[data-ca-', selector, ']'].join(''));
                var imageTypeName = self.options.imagePairTypes[previewFile.image_type];

                if (data.update_name !== false) {
                    var isDefined = function (item) {
                        return item !== undefined;
                    };
                    var nameParts = [data.prefix, imageTypeName, data.postfix, index].filter(isDefined);

                    if (data.name) {
                        nameParts.push(['[', data.name, ']'].join(''));
                    }

                    targetInput.attr('name', nameParts.join(''));
                }

                $(targetInput).val(data.value);

                if ('defaultValue' in data && targetInput.length) {
                    targetInput.get(0).defaultValue = data.value;
                }
            });

            var $previewEl = $preview.find('[data-ca-preview-detailed]');

            if (previewFile.previewLink) {
                $previewEl.attr('href', previewFile.previewLink);
                $previewEl.show();
            } else {
                $previewEl.hide();
            }

            previewFile.tygh.$sortingPositionInput = $('[data-ca-image-position]', $preview)
        }
    },

    runElfinderFilePickerModal: function () {
        var self = this;

        if (!$.fn.elfinder) {
            $.loadCss(['js/lib/elfinder/css/elfinder.min.css']);
            $.loadCss(['js/lib/elfinder/css/theme.css']);
            $.getScript('js/lib/elfinder/js/elfinder.min.js', function () {
                if (_.cart_language !== 'en' && methods.isAvailableLanguage(_.cart_language)) {
                    $.getScript(`js/lib/elfinder/js/i18n/elfinder.${methods.tranformLanguage(_.cart_language)}.js`)
                        .then(null, function() { return $.getScript('js/lib/elfinder/js/i18n/elfinder.LANG.js'); })
                        .done(self._initElfinderFilePickerModal);
                } else {
                    self._initElfinderFilePickerModal();
                }
            });
        } else {
            this._initElfinderFilePickerModal();
        }
    },

    _initElfinderFilePickerModal: function () {
        var self = this;

        var minZedIndex = $.ceDialog('get_last').parent('.ui-front').css('z-index');
        var $tempElfinderDialogWrapper = $('<div id="server_file_browser"></div>');

        var options = $.extend(_.fileManagerOptions, {
            url: fn_url('elf_connector.files?security_hash=' + _.security_hash),
            cutURL: _.allowed_file_path,
            getFileCallback: function (file) {
                $tempElfinderDialogWrapper.dialog('close');
                self.registerFileFromElfinder(file);
            }
        });
        var $elfinderDialog = $tempElfinderDialogWrapper.elfinder(options).dialog({
            width: 900,
            height: 500,
            modal: true,
            title: _.tr('file_browser'),
            close: function (event, ui) {
                $tempElfinderDialogWrapper.dialog('destroy').elfinder('destroy').remove();
            }
        });

        if (minZedIndex) {
            $elfinderDialog.closest('.ui-dialog').css('z-index', minZedIndex + 1);
        }
    },

    /**
     * Called when user chooses a file at Elfinder's file picker modal window.
     *
     * @param elfinderChosenFile
     */
    registerFileFromElfinder: function (elfinderChosenFile) {
        var self = this;

        var parts = elfinderChosenFile.path.split('/');
        parts.shift();
        var relativeFilePath = parts.join('/');

        var fileuploaderMockFile = {
            name: elfinderChosenFile.name,
            size: Number(elfinderChosenFile.size),
            type: elfinderChosenFile.mime,
            lastModified: elfinderChosenFile.ts,
            mock: {
                type: 'server',
                value: relativeFilePath
            }
        };
        if (elfinderChosenFile.height) {
            fileuploaderMockFile.height = Number(elfinderChosenFile.height);
        }
        if (elfinderChosenFile.width) {
            fileuploaderMockFile.width = Number(elfinderChosenFile.width);
        }

        self.dropzone.emit('addedfile', fileuploaderMockFile);
        self.dropzone.emit('complete', fileuploaderMockFile);
        self.dropzone.emit('thumbnail', fileuploaderMockFile, elfinderChosenFile.url);
    },

    isUploadingInProgress: function () {
        return this.files.isUploadingInProgress();
    },

    _markFileAsMain: function (previewFile) {
        previewFile.image_type = 'M';
        previewFile.dynamicData['image-type'] = {
            name: 'type',
            value: 'M',
            postfix: '_data'
        };
    },

    _markFileAsAdditional: function (previewFile) {
        previewFile.image_type = previewFile.original_image_type;
        previewFile.dynamicData['image-type'] = {
            name: 'type',
            value: 'A',
            postfix: '_data'
        };
    },

    _changeMainFileToAdditional: function () {
        var self = this;
        var mainFile = this.files.getMainFile();

        if (mainFile) {
            self._markFileAsAdditional(mainFile);
            self.refreshPreview(mainFile);
        }
    },

    _isMainImagePair: function (previewFile) {
        var self = this;
        var firstActiveFile = self.files.getFirstActive();

        return firstActiveFile &&
            $(previewFile.previewElement).is($(firstActiveFile.previewElement));
    },

    _validateUrl: function (url) {
        if (!url.includes('://')) {
            url = 'http://' + url;
        }

        const regexp = /^[A-Za-z]+:\/\/[A-Za-z0-9-_:@]+\.[A-Za-z0-9-+_%~&\\?\/.=()]+$/;
        return regexp.test(url);
    },

    _expandDropzone: function () {
        var isContain = this.elements
            .$uploaderPickers
            .hasClass(this.options.expandedDropzoneSelector);

        if (!isContain) {
            this.elements
                .$uploaderPickers
                .addClass(this.options.expandedDropzoneSelector);
        }
    },

    _shrinkDropzone: function () {
        this.elements
            .$uploaderPickers
            .removeClass(this.options.expandedDropzoneSelector);
    },

    _moveDropzoneToEnd: function () {
        var els = this.elements;
        els.$filesContainerEl.append(els.$uploaderPickers);
    }
});

var collectFormFileuploaders = function ($form) {
    var containedFileuploaders = $form.data('caContainedFileUploaders');

    return containedFileuploaders || [];
};

var saveFormFileuploaders = function ($form, fileuploaderInstances) {
    $form.data('caContainedFileUploaders', fileuploaderInstances);
};

function loadScripts($context) {
    var defer = $.Deferred();

    if (!$.fn.file_uploader) {
        $.getScript('js/lib/dropzone/dist/dropzone.js', defer.resolve);
    } else {
        defer.resolve();
    }

    Promise.resolve(defer).then(() => {
        $('.cm-file-uploader', $context).each(function () {
            var fileUploader = new FileUploader($(this), $context);
            fileUploader.init();
        });
    });

    return defer.promise();
}

function init($elems) {
    if (!$elems.length) {
        return;
    }

    $.ceEvent('on', 'ce.commoninit', function ($context) {

        // Prevent form submission while file uploading is in progress
        $.ceEvent('on', 'ce.form.beforeSubmit', function ($form, $clickedEl, allowSubmit) {
            var formFileuploaderInstances = collectFormFileuploaders($form);
            var fileuploadersHaveFilesBeingUploaded = false;

            $.each(formFileuploaderInstances, function () {
                if (this.isUploadingInProgress()) {
                    fileuploadersHaveFilesBeingUploaded = true;
                }
            });

            if (fileuploadersHaveFilesBeingUploaded) {
                $.ceNotification('show', {
                    type: 'W',
                    title: _.tr('warning'),
                    message: _.tr('file_uploading_in_progress_please_wait')
                });

                return false;
            } else {
                return allowSubmit;
            }
        });

        loadScripts($context);
    });
}

export const methods = {
    init: function () {
        init($(this));
        return $(this);
    },
    
    // js/lib/elfinder/js/i18n/elfinder.[langCode].js
    isAvailableLanguage: function (langCode) {
        return [
            'de', 'ar', 'bg', 'ca', 'cs', 'da', 'el', 'es',    'fa', 'fo', 'fr_CA', 'fr',
            'he', 'hr', 'hu', 'id', 'it', 'ja', 'ko', 'nl',    'no', 'pl', 'pt_BR', 'ro',
            'ru', 'si', 'sk', 'sl', 'sr', 'sv', 'tr', 'ug_CN', 'uk', 'vi', 'zh_CN', 'zh_TW',
        ].includes(methods.tranformLanguage(langCode));
    },

    tranformLanguage: function (langCode) {
        const languageRelations = {
            pt: 'pt_BR',
            ug: 'ug_CN',
            zh: 'zh_CN',
        }
        return (Object.keys(languageRelations).includes(langCode))
            ? languageRelations[langCode]
            : langCode;
    },

    getAvailableLanguage: function (langCode) {
        return methods.isAvailableLanguage(langCode) ? methods.tranformLanguage(langCode) : 'en';
    }
};

export const ceFileUploaderInit = function ($) {
    $.fn.ceFileUploader = function (method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        } else {
            $.error('ty.file_uploader: method ' + method + ' does not exist');
        }
    };

    $.ceFileUploader = function (method, params) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else {
            $.error('ty.file_uploader: method ' + method + ' does not exist');
        }
    }
}
