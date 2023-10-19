function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

(function (_, $) {
  var liveEditorObject = null,
      $editorInput = null;
  var icon_live_edit = '<span class="ty-icon cm-icon-live-edit icon-live-edit ty-icon-live-edit"></span>';
  var methods = {
    _getPhrase: function _getPhrase(elm) {
      var currentValue = elm.data('caLiveEditorPhrase'),
          originalValue = elm.data('caLiveEditOriginalValue');

      if (typeof currentValue !== 'undefined') {
        return currentValue;
      } else if (elm.is('var, option')) {
        if (originalValue) {
          return originalValue;
        }

        return elm.html();
      } else if (!elm.is('input[type=checkbox]') && !elm.is('input[type=image]') && !elm.is('img')) {
        return elm.val();
      } else {
        return elm.prop('title');
      }
    },
    createEditor: function createEditor(e) {
      var $liveEditValueWrapper = $(e.target);

      if (!$liveEditValueWrapper.closest('.cm-icon-live-edit').length) {
        return;
      }

      var targetElement = $('.cm-live-edit', $liveEditValueWrapper.closest('var')).first();

      if (targetElement.is('select')) {
        targetElement = $('option[value="' + targetElement.val() + '"]', targetElement);
      }

      if (!targetElement.length || !targetElement.data('caLiveEdit')) {
        return;
      }

      var phrase = methods._getPhrase(targetElement),
          hasPlaceholders = /\[\S+\]/.test(phrase);

      liveEditorObject = {
        name: targetElement.data('caLiveEdit'),
        old_phrase: phrase,
        type: '',
        has_placeholders: hasPlaceholders,
        original_val: targetElement.data('caLiveEditOriginalValue'),
        need_render: !!targetElement.data('caLiveEditorNeedRender') || hasPlaceholders,
        input_type: targetElement.data('caLiveEditorInputType'),
        object_id: targetElement.data('caLiveEditorObjectId'),
        object_type: targetElement.data('caLiveEditorObjectType')
      };
      var objectData = liveEditorObject.name.split(':'),
          objectName = objectData[0],
          objectField = objectData[1],
          rule = _.live_editor_objects[objectName];

      if (objectName && rule) {
        if (liveEditorObject.input_type) {
          liveEditorObject.type = liveEditorObject.input_type;
        } else if (rule['input_type_fields'] && rule['input_type_fields'][objectField]) {
          liveEditorObject.type = rule['input_type_fields'][objectField];
        } else if (rule['input_type']) {
          liveEditorObject.type = rule['input_type'];
        }
      }

      var inputByTypeMap = {
        input: '<input type="text" class="ty-live-editor__input ty-input-text ty-input-text-large" />',
        price: '<input type="text" class="ty-live-editor__input ty-input-text ty-input-text-large cm-numeric" />',
        textarea: '<textarea class="ty-live-editor__input ty-live-editor__input--textarea cm-textarea-autosize ty-input-textarea ty-input-text-large"></textarea>',
        wysiwyg: '<textarea class="ty-live-editor__input cm-wysiwyg"></textarea>'
      };
      var inputTemplate = inputByTypeMap[liveEditorObject.type] || inputByTypeMap.textarea,
          $dialog = $("<div class=\"ty-live-editor hidden\">\n                        <div class=\"ty-live-editor__input-wrapper\">\n                            ".concat(inputTemplate, "\n                        </div>\n                    </div>")).appendTo('body');
      $editorInput = $('.ty-live-editor__input', $dialog);
      $editorInput.val(liveEditorObject.old_phrase);
      var dialogParams = {
        dialogClass: 'ty-live-editor-dialog',
        destroyOnClose: true,
        titleFirstChunk: "<button class=\"ty-btn ty-btn__primary ty-live-editor__saver\">".concat(_.tr('save_raw'), "</button>"),
        titleSecondChunk: "<button class=\"ty-btn ty-live-editor__closer\">".concat(_.tr('cancel_raw'), "</button>"),
        titleTemplate: "".concat(_.tr('text_editing_raw'), " <div class=\"ty-float-right\">? ?</div>")
      };

      if (liveEditorObject.type !== 'wysiwyg') {
        dialogParams.width = 'auto';
        dialogParams.height = 'auto';
      }

      $dialog.ceDialog('open', dialogParams); // Init wysiwyg

      if ($editorInput.hasClass('cm-wysiwyg')) {
        $editorInput.ceEditor();
        setTimeout(function () {
          return $dialog.ceDialog('reload');
        }, 300);
      } // Init autonumeric


      if ($.fn.autoNumeric && $editorInput.hasClass('cm-numeric')) {
        $editorInput.autoNumeric();
      } // Init autosize


      if ($.fn.autosize && $editorInput.hasClass('cm-textarea-autosize')) {
        $editorInput.autosize();
      }
    },
    load: function load(content) {
      $('[data-ca-live-editor-obj]', content).each(function () {
        var $element = $(this),
            objName = $element.data('caLiveEditorObj'),
            originalContent = $element.html(),
            currentValue = $element.data('caLiveEditorPhrase'),
            editorAttributes = '',
            needsRender = $element.data('caLiveEditorNeedRender'),
            inputType = $element.data('caLiveEditorInputType'),
            objectId = $element.data('caLiveEditorObjectId'),
            objectType = $element.data('caLiveEditorObjectType');

        if (currentValue) {
          var currentValueEscaped = $('<div>').text(currentValue).html();
          editorAttributes += " data-ca-live-editor-phrase=\"".concat(currentValueEscaped, "\"");
        }

        if (needsRender) {
          editorAttributes += ' data-ca-live-editor-need-render="true"';
        }

        if (inputType) {
          editorAttributes += " data-ca-live-editor-input-type=\"".concat(inputType, "\"");
        }

        if (objectId) {
          editorAttributes += " data-ca-live-editor-object-id=\"".concat(objectId, "\"");
        }

        if (objectType) {
          editorAttributes += " data-ca-live-editor-object-type=\"".concat(objectType, "\"");
        }

        $element.html("<var class=\"live-edit-wrap\">\n                        ".concat(icon_live_edit, "\n                        <var data-ca-live-edit=\"").concat(objName, "\" ").concat(editorAttributes, " class=\"cm-live-edit live-edit-item\">").concat(originalContent, "</var>\n                    </var>")).removeAttr('data-ca-live-editor-obj').removeAttr('data-ca-live-editor-phrase').removeAttr('data-ca-live-editor-need-render').removeAttr('data-ca-live-editor-input-type');
      }); // wrapping

      $('.cm-live-editor-need-wrap', content).each(function () {
        var elm = $(this);

        if (elm.is('option')) {
          if (!elm.hasClass('cm-live-editor-need-wrap')) {
            return true;
          }

          elm = elm.closest('select');
          $('option', elm).removeClass('cm-live-editor-need-wrap');
        }

        elm.wrap('<var class="live-edit-wrap">');
        elm.before(icon_live_edit);
        elm.removeClass('cm-live-editor-need-wrap');
        elm.addClass('cm-live-edit live-edit-item');
      }); // Mark empty elements

      $('[data-ca-live-edit]').each(function () {
        var elm = $(this),
            phrase = methods._getPhrase(elm);

        if (!$.trim(phrase)) {
          var name = elm.data('caLiveEdit'),
              value = elm.data('caLiveEditOriginalValue') ? elm.data('caLiveEditOriginalValue') : '';
          methods.set(value, name);
        }
      });
      $('.cm-live-edit').parents('.cm-button-main').removeClass('cm-button-main');
      $('.cm-live-edit:has(p,div,ul)').css('display', 'block');

      if ($.browser.msie) {
        $('.cm-live-edit:has(p)').each(function () {
          $(this).html($(this).html());
        });
      }
    },
    init: function init() {
      $(_.doc).on('click', '.cm-icon-live-edit', function (e) {
        // attach translation icon click processing with highest priority
        // to prevent processing of events attached to translation icon parents
        e.stopPropagation();
        e.preventDefault();
        return $.ceLiveEditorMode('createEditor', e);
      });
      $(_.doc).on('click', '.ty-live-editor__closer', function () {
        $('.ty-live-editor').ceDialog('close');
      });
      $(_.doc).on('click', '.ty-live-editor__saver', function () {
        methods.save($editorInput.val());
        $('.ty-live-editor').ceDialog('close');
      });
    },
    save: function save(value) {
      if (!liveEditorObject) {
        return;
      }

      var data = {
        name: liveEditorObject.name,
        value: value,
        lang_code: _.cart_language,
        object_id: liveEditorObject.object_id,
        object_type: liveEditorObject.object_type
      };

      if (liveEditorObject.has_placeholders || liveEditorObject.need_render) {
        data.full_render = true;
        data.result_ids = 'tygh_container';
        data.return_url = _.current_url;
        data.need_render = true;
      } else {
        $.ceLiveEditorMode('set', value);
      }

      $.ceAjax('request', fn_url('design_mode.live_editor_update'), {
        method: 'post',
        data: data
      });
      liveEditorObject = null;
      $editorInput = null;
    },
    set: function set(new_phrase, name) {
      if (!name && liveEditorObject) {
        name = liveEditorObject.name;
      }

      if (name) {
        var display_phrase = new_phrase,
            is_empty = false;

        if (!new_phrase) {
          display_phrase = '--' + fn_strip_tags(_.tr('empty')) + '--';
          is_empty = true;
        }

        $('[data-ca-live-edit="' + name + '"]').each(function () {
          var jelm = $(this);

          if (!liveEditorObject || liveEditorObject && !liveEditorObject.has_placeholders) {
            if (jelm.is('var.cm-live-edit, option')) {
              jelm.html(display_phrase);
            } else if (jelm.is('input[type=checkbox]')) {
              jelm.prop('title', display_phrase);
            } else if (jelm.is('input[type=image], img')) {
              jelm.prop('title', display_phrase);
              jelm.prop('alt', display_phrase);
            } else {
              jelm.val(display_phrase);
            }
          }

          jelm.toggleClass('cm-live-editor-empty-element', is_empty);

          if (liveEditorObject && liveEditorObject.type === 'price') {
            display_phrase = $.formatNum(new_phrase);
          }

          if (!is_empty) {
            jelm.data('caLiveEditorPhrase', new_phrase);
          }
        });
      }
    },
    cancel: function cancel() {
      if (liveEditorObject) {
        $('.editable-input textarea.cm-wysiwyg').ceEditor('destroy');
        var old_phrase = liveEditorObject.old_phrase;

        if (liveEditorObject.type === 'price') {
          old_phrase = $.formatPrice(old_phrase.replace(',', ''));
        }

        methods.set(old_phrase);
        liveEditorObject = null;
      }
    }
  };

  $.ceLiveEditorMode = function (method) {
    if (methods[method]) {
      return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
    } else if (_typeof(method) === 'object' || !method) {
      return methods.init.apply(this, arguments);
    } else {
      $.error('ty.ceLiveEditorMode: method ' + method + ' does not exist');
    }
  };

  $.ceEvent('on', 'ce.commoninit', function (content) {
    $.ceLiveEditorMode('load', content);
  });
  $.ceLiveEditorMode('init');
})(Tygh, Tygh.$);