(function (_, $) {
  var link_iterator = 0;
  var disable_value_changer = false;
  var style_changed = false;
  var form_initial_state = '';
  var patterns_url = 'patterns.cart-services.com';
  var css_inited = false;
  var icon_image = '<span class="ty-icon cs-icon icon-image"></span>'; // Do not initialize in embedded mode

  if (_.embedded) {
    return false;
  }

  var style_properties = '';
  var use_dynamic_style = true;

  function formParams() {
    // FIXME: Backward presets compatibility
    var elms = $('[name^="style[data]"],[name^="preset[data]"]', $('#theme_editor'));
    var s = '';
    var params = {};
    elms.each(function () {
      var self = $(this);

      if (self.hasClass('cm-te-skip-css')) {
        return;
      }

      if (self.is('input[type=checkbox]') && !self.prop('checked')) {
        return;
      }

      if (self.is('input[type=radio]') && !self.prop('checked')) {
        return;
      }

      params[self.prop('name')] = self.val();
    });

    for (var k in params) {
      s += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
    }

    return s;
  }

  function updateCss(url, callback) {
    link_iterator++;
    $.toggleStatusBox('show');
    var link = $('<link/>', {
      type: 'text/css',
      rel: 'stylesheet',
      media: 'screen',
      id: 'theme_editor_css_' + link_iterator
    }).appendTo('head');
    link.prop('href', $.attachToUrl(url, 'x=' + Math.random()));
    link.on('load', function () {
      if (link_iterator - 1 == 0) {
        $('link[href*=standalone]:first').remove();
      } // We should keep 2 link elements to avoid flickering when styles are reloaded


      var obsolete_link = $('#theme_editor_css_' + (link_iterator - 1));

      if (obsolete_link.length) {
        obsolete_link.remove();
      }

      if (callback) {
        callback();
      }

      $.toggleStatusBox('hide');
    });
  }

  function serializeForm() {
    var form = $('form[name=theme_editor_form]'); // FIXME: BAckward presets compatibility

    var serialized_data = $('[name^="style[data]"],[name^="style[custom_css]"],[name^="preset[data]"],[name^="preset[custom_css]"]', form).serialize();
    $('input[type=file]', form).each(function () {
      serialized_data += $(this).val();
    });
    $('.cm-te-css-editor', form).each(function () {
      serialized_data += $(this).ceCodeEditor('value');
    });
    return serialized_data;
  }

  function isFormChanged() {
    if (serializeForm() == form_initial_state) {
      return false;
    }

    return true;
  }

  function setStyleStatus(status) {
    // FIXME: Backward presets compatibility
    var s_elm = $('#theme_editor .cm-te-load-style.active,#theme_editor .cm-te-load-preset.active');
    var t_elm = $('#theme_editor span.cm-style-title,#theme_editor span.cm-preset-title');
    var text = s_elm.data('caStyleName') ? s_elm.data('caStyleName') : s_elm.data('caStyleId');
    var changed_text = ' *';

    if (!text) {
      return false;
    }

    if (status == 'changed') {
      style_changed = true;
      s_elm.html(text + changed_text);
      t_elm.html(text + changed_text);
    } else if (status === 'clear') {
      style_changed = false;
      s_elm.html(text);
      t_elm.html(text);
    }

    return true;
  }

  function getUrlFromCss(prop) {
    var url = prop.str_replace('url(', '');
    url = url.str_replace(')', '');
    url = url.str_replace('"', '');
    return url;
  }

  function initContainer($themeEditor) {
    var contentContainer = $themeEditor.data('caTeContentContainer');

    if (typeof contentContainer === 'undefined' || contentContainer === _.container) {
      return;
    }

    _.container = contentContainer;
  }

  function initPatterns() {
    $.ceAjax('request', window.location.protocol + '//' + patterns_url, {
      hidden: true,
      callback: function callback(data) {
        $('ul.cm-te-pattern-list').append(data.map(function (item) {
          return $('<li>', {
            html: $('<div>', {
              class: 'te-pattern-preview cm-te-select-pattern',
              html: $('<div/>', {
                class: 'te-pattern-preview__img cm-pattern-preview__img',
                style: '--te-pattern-preview-img: url(\'' + item + '\');',
                'data-te-pattern-preview-img-url': item
              })
            })
          });
        }));
      }
    });
  }

  function initGoogleFonts() {
    if (!('google_fonts' in _)) {
      return false;
    }

    var lis = {};
    var container = $('.cm-te-google');
    var step = 28;
    var i, genericName;

    for (var k in _.google_fonts) {
      lis[k] = '';

      for (i = 0; i < _.google_fonts[k].length; i++) {
        genericName = [_.google_fonts[k][i]['name'], _.google_fonts[k][i]['generic-family']].join(',');
        lis[k] += '<li class="cm-te-google-font te-google-font-' + k + '" data-ca-select-box-value="' + genericName + '">' + '<span style="background-position: 0 -' + i * step + 'px">' + _.google_fonts[k][i]['name'] + '</span>' + '</li>';
      }
    }

    if (typeof _.te_custom_fonts !== 'undefined' && _.te_custom_fonts && _.te_custom_fonts.length) {
      lis['custom'] = '';

      for (i = 0; i < _.te_custom_fonts.length; i++) {
        lis['custom'] += '<li style="font-family: ' + _.te_custom_fonts[i] + '" data-ca-select-box-value="' + _.te_custom_fonts[i] + '">' + _.te_custom_fonts[i] + '</li>';
      }
    }

    container.each(function () {
      var $self = $(this),
          $elms,
          selectedVariant = $self.data('caSelectBoxDefault');

      for (var k in lis) {
        $elms = $self.find('.cm-te-google-' + k);
        $elms.after(lis[k]);
      }

      $self.find('ul li').each(function (i, variant) {
        var $variant = $(variant),
            variantValue = $variant.data('caSelectBoxValue');

        if (variantValue === selectedVariant) {
          $variant.addClass('active');
          $self.find('span:first').html($variant.text());
        }
      });
    });
  }

  function toggleSectionScroll() {
    var container = $('.cm-te-disable-scroll');

    if (container.css('overflow-y') == 'auto') {
      container.css('overflow-y', 'hidden');
    } else {
      container.css('overflow-y', 'auto');
    }
  }

  function validStyleName() {
    // FIXME: Backward presets compatibility
    var prompt_message = _.tr('theme_editor.style_name') || _.tr('theme_editor.preset_name');

    var style_name = prompt(prompt_message, '');

    if (!style_name) {
      return false;
    } // FIXME: Backward presets compatibility


    var existing_styles = $('#elm_te_styles li a.cm-te-duplicate-style,#elm_te_presets li a.cm-te-duplicate-preset').each(function () {
      var self = $(this);
      var name = self.hasClass('cm-te-duplicate-style') ? self.data('caStyleId') : self.data('caPresetId');

      if (name.toString().toLowerCase() == style_name.toString().toLowerCase().trim()) {
        style_name = '';
        $.ceNotification('show', {
          type: 'E',
          title: _.tr('error'),
          // FIXME: Backward presets compatibility
          message: _.tr('theme_editor.error_style_exists') || _.tr('theme_editor.error_preset_exists')
        });
        return false;
      }
    });

    if (style_name && (!style_name.match(/^[^\\\#\%\/\?\*:;\{\}\+]+$/) || !style_name.trim())) {
      $.ceNotification('show', {
        type: 'E',
        title: _.tr('error'),
        // FIXME: Backward presets compatibility
        message: _.tr('theme_editor.incorrect_style_name') || _.tr('theme_editor.incorrect_preset_name')
      });
      return false;
    }

    return style_name.trim();
  }

  function getEditorUrl(css_filename) {
    if (!css_filename) {
      var current_css = $('link[href*=standalone]');
      var css_filename = current_css.length ? current_css.prop('href').split('/').pop() : 'standalone.css'; // support for dev_css dev mode

      if (css_filename.indexOf('?') > 0) {
        css_filename = css_filename.substr(0, css_filename.indexOf('?'));
      }
    }

    return 'theme_editor.get_css?css_filename=' + encodeURIComponent(css_filename) + '&';
  }

  function getGoogleFontData(font) {
    for (var section in _.google_fonts) {
      for (var i = 0; i < _.google_fonts[section].length; i++) {
        if (_.google_fonts[section][i]['name'] == font) {
          return _.google_fonts[section][i];
        }
      }
    }

    return {};
  }

  function updateLogos() {
    $('div.cm-te-logo').each(function () {
      var self = $(this);

      if (self.data('caImageArea') && self.data('caImageArea') == 'theme') {
        var selfSrc = getUrlFromCss(self.css('background-image'));
        $('img.logo, .ty-logo-container__image, div.logo-container, div.ty-logo-container').prop('src', selfSrc).css({
          width: 'auto',
          height: 'auto'
        });
      }

      if (self.data('caImageArea') && self.data('caImageArea') == 'favicon') {
        $('link[rel="shortcut icon"]').remove();
        $('<link rel="shortcut icon" href="' + getUrlFromCss(self.css('background-image')) + '">').appendTo('head');
      }
    });
  }
  /**
   * Replaces vendor-specific style with the admin one when opening theme editor on a vendor page
   */


  function initCss() {
    if (css_inited) {
      return false;
    }

    use_dynamic_style = $('[data-ca-te-use-dynamic-style]').data('caTeUseDynamicStyle');
    use_dynamic_style = use_dynamic_style === null ? true : use_dynamic_style;
    var style_url = $('link[href*=standalone]:first').prop('href');

    if (!use_dynamic_style && /_\d+\.css/.test(style_url)) {
      updateCss(fn_url(getEditorUrl() + formParams()), function () {
        link_iterator--;
        css_inited = true;
      });
    }
  }

  $(document).ready(function () {
    var init_data = {
      theme_url: _.current_url
    };

    if ('query_string' in _) {
      init_data.query_string = _.query_string;
    }

    $.ceAjax('request', fn_url('theme_editor.view'), {
      result_ids: 'theme_editor',
      data: init_data,
      callback: function callback() {
        $.ceEvent('trigger', 'ce.themeeditor.loaded'); // FIXME: Backward compatibility for theme editors that don't define 'query_string' property
        // Perform request and lock theme editor if location can't be edited

        if (!('query_string' in _)) {
          $.ceAjax('request', _.current_url, {
            hidden: true
          });
        }

        var editor_url = getEditorUrl(); // Patterns

        $('#theme_editor').on('click', '.cm-te-select-pattern', function (e) {
          var self = $(this);
          var parent = self.closest('.cm-te-pattern-list');
          var value_holder = $('#' + parent.data('caHolderId'));
          var self_img = self.find('.cm-pattern-preview__img');

          if (self_img.length) {
            value_holder.val(self_img.data('tePatternPreviewImgUrl')).change();
          } else {
            // transparent
            value_holder.val('transparent').change();
          }

          self.closest('.cm-te-patterns-container').hide().addClass('hidden');
          toggleSectionScroll();
        });
        $('#theme_editor').on('change', '.cm-te-pattern-holder', function () {
          var self = $(this);
          var preview = $('#' + self.data('caPreviewId'));
          var preview_img = preview.find('img');

          if (preview_img.length == 0 && self.val() !== 'transparent') {
            var $preview_img = $('<div/>', {
              class: 'te-pattern-preview__img cm-pattern-preview__img',
              style: '--te-pattern-preview-img: url(\'' + self.val() + '\');',
              'data-te-pattern-preview-img-url': self.val()
            });
            preview.empty().append($preview_img);
          }

          if (!self.val() || self.val() == 'transparent') {
            preview_img.hide();
            preview.addClass('te-pattern-empty').append(icon_image);
          } else {
            preview.removeClass('te-pattern-empty');
            preview_img.prop('src', self.val()).show();
          }
        });
        $('#theme_editor').on('click', '.cm-te-pattern-selector', function () {
          var dlg = $('#' + $(this).data('caPatternDialog')); // Calculate initial position

          dlg.css({
            'top': $(this).offset().top - $(document).scrollTop() + $(this).height()
          }); // Hide containers without this

          $('.cm-te-patterns-container').not(dlg).hide();

          if (dlg.is(':visible')) {
            dlg.hide().addClass('hidden');
          } else {
            dlg.show().removeClass('hidden');
          }

          toggleSectionScroll();
        });
        $('#theme_editor').on('click', function (e) {
          var elm = $(e.target);

          if (!elm.closest('.cm-te-pattern-selector,.cm-te-patterns-container').length) {
            $('.cm-te-pattern-selector').each(function () {
              var self = $(this);
              var dlg = $('#' + self.data('caPatternDialog'));

              if (dlg.is(':visible')) {
                dlg.hide();
                toggleSectionScroll();
              }
            });
          }
        }); // Google fonts

        $.getJSON('js/tygh/google_fonts_list.js', function (data) {
          _.google_fonts = data;
          initGoogleFonts();
        });
        $('#theme_editor').on('click', '.cm-te-google-font', function () {
          var self = $(this);
          var font_data = getGoogleFontData(self.data('caSelectBoxValue'));
          var font_name = self.data('caSelectBoxValue').replace(/(,sans-serif|,serif|,cursive)/, "");
          $('link:last').after('<link href="//fonts.googleapis.com/css?family=' + font_name + ('weight' in font_data ? ':' + font_data['weight'] : '') + '" rel="stylesheet" type="text/css">');
          self.removeClass('cm-te-google-font');
        }); // FIXME: this event catches logout link click

        $(_.doc).on('click', 'a.account,a.relogin,.cm-te-change-layout', function (e) {
          e.stopImmediatePropagation();
          return true;
        });
        $('#theme_editor').on('click', '.cm-te-close-editor', function (e) {
          e.stopImmediatePropagation();
          var langvar = style_changed ? _.tr('theme_editor.text_close_editor_unsaved') : _.tr('theme_editor.text_close_editor');

          if (confirm(langvar)) {
            var self = $(this);
            self.prop('href', $.attachToUrl(self.prop('href'), 'redirect_url=' + encodeURIComponent(_.current_url)));
            return true;
          }

          return false;
        });
        $.ceEvent('on', 'ce.colorpicker.hide', function () {
          toggleSectionScroll();
        });
        $.ceEvent('on', 'ce.colorpicker.show', function () {
          toggleSectionScroll();
        });
        $('#theme_editor').on('change', '.cm-colorpicker', function () {
          var self = $(this);
          var gradient = $('#' + self.prop('id') + '_gradient');
          var custom_disable = false;

          if (gradient.length) {
            if (!disable_value_changer) {
              disable_value_changer = true;
              custom_disable = true;
            }

            gradient.ceColorpicker('set', self.val());

            if (custom_disable) {
              disable_value_changer = false;
            }
          }
        });
        $('#theme_editor').on('change', '.cm-te-value-changer', function () {
          if (disable_value_changer === true) {
            return false;
          }

          updateCss(fn_url(editor_url + formParams()));
        }); // FIXME: Backward presets compatibility

        $('#theme_editor').on('click', '.cm-te-load-style,.cm-te-load-preset', function (e) {
          var self = $(this);

          if (isFormChanged() && confirm(_.tr('text_changes_not_saved')) === false) {
            return false;
          } // FIXME: Backward presets compatibility


          var _style_id = self.data('caStyleId') || self.data('caPresetId');

          $.ajaxLink(e, '', function () {
            updateCss(fn_url(editor_url + 'style_id=' + _style_id));
            self.addClass('active');
            form_initial_state = serializeForm();
            updateLogos();
          });
          e.preventDefault();
          return false;
        });
        $('#theme_editor').on('click', '.cm-te-change-css-file', function (e) {
          if (isFormChanged() && confirm(_.tr('text_changes_not_saved')) === false) {
            return false;
          }

          $.ajaxLink(e, '', function () {
            form_initial_state = serializeForm();
          });
          e.preventDefault();
          return false;
        });
        $('#theme_editor').on('click', '.cm-te-change-layout', function (e) {
          var self = $(this);

          if (isFormChanged() && confirm(_.tr('text_changes_not_saved')) === false) {
            return false;
          }
        }); // Set changed flag

        $('#theme_editor').on('change', 'input', function () {
          setStyleStatus('changed');
        }); // Special for textarea

        $('#theme_editor').on('input propertychange', 'textarea', function () {
          setStyleStatus('changed');
        }); // Set changed flag for selectbox

        $('#theme_editor').on('change', '.cm-te-selectbox', function () {
          setStyleStatus('changed');
        }); // Close opened select boxes

        $('#theme_editor').on('click', function (e) {
          if ($(e.target).hasClass('cm-te-selectbox') || $(e.target).parents('.cm-te-selectbox').length) {
            return;
          }

          if ($(e.target).parents('.te-select-dropdown').length === 0) {
            $('.te-select-dropdown:visible').hide();
          }
        }); // Display opened select box

        $('#theme_editor').on('click', '.cm-te-selectbox', function (e) {
          var self = $(this);
          var ul = self.find('ul');
          $('ul.te-select-dropdown').not(ul).hide();

          if (ul.is(':visible')) {
            ul.hide();
          } else {
            ul.show();
          }
        }); // selectbox: select element

        $('#theme_editor').on('click', '.cm-te-selectbox li', function (e, stop_propagation) {
          stop_propagation = stop_propagation || false;
          var self = $(this);
          var container = self.parents('.cm-te-selectbox');

          if (self.hasClass('cm-te-selectbox-group')) {
            return false;
          } // set selectbox value


          container.find('input[type=text]').val(self.data('caSelectBoxValue')); // set selectbox title

          container.find('span:first').html(self.text().replace(/(,sans-serif|,serif|,cursive)/, "")); // highlight active item

          container.find('li').removeClass('active');
          self.addClass('active');

          if (container.hasClass('cm-te-value-changer')) {
            container.trigger('change');
          }

          if (stop_propagation) {
            e.stopImmediatePropagation();
          }
        }); // tabs

        $('#theme_editor').on('click', '.cm-te-tabs a', function () {
          var self = $(this);
          var ul = self.parents('ul');
          var container = self.parents('.cm-te-tabs');
          $('li', ul).removeClass('active');
          $('.cm-te-tab-contents', container).hide();
          $('#' + self.data('caTargetId')).show();
          self.parent('li').addClass('active');
        }); // Show editor sections

        $('#theme_editor').on('click', '.cm-te-sections li', function () {
          $('.cm-te-section').addClass('hidden');
          $('#' + $(this).data('caTargetId')).removeClass('hidden');
          $('input[name=selected_section]', $('#theme_editor')).val($(this).data('caTargetId'));
        }); // Reset button

        $('#theme_editor').on('click', '.cm-te-reset', function () {
          result = confirm(_.tr('theme_editor.text_reset_changes'));

          if (!result) {
            return false;
          }

          var container = $(this).parents('.cm-te-section'); // FIXME: backward presets compatibility

          var elms = $('[name^="style[data]"],[name^="style[custom_css]"],[name^="preset[data]"],[name^="preset[custom_css]"]', container);
          disable_value_changer = true; // disable cm-te-value-changer event

          elms.each(function () {
            var self = $(this);

            if (self.is('input[type=checkbox]') || self.is('input[type=radio]')) {
              self.prop('checked', self.prop('defaultChecked'));
            } else {
              self.val(self.prop('defaultValue')).trigger('change'); // dirty, fix to allow selectbox work

              if (self.hasClass('cm-te-selectbox-storage')) {
                $('li[data-ca-select-box-value="' + self.val() + '"]', self.parents('.cm-te-selectbox')).trigger('click', [true]);
              }

              if (self.hasClass('cm-colorpicker')) {
                self.ceColorpicker('reset');
              }
            }
          });
          disable_value_changer = false;
          updateCss(fn_url(editor_url + formParams()));

          if (isFormChanged() === false) {
            setStyleStatus('clear');
          }

          return false; // prevent default action (form submit)
        }); // Convert to CSS button

        $('#theme_editor').on('click', '.cm-te-convert-to-css', function (e) {
          var convertToCss = function convertToCss() {
            $.ceAjax('request', fn_url('theme_editor.convert_to_css'), {
              method: 'POST',
              result_ids: 'theme_editor',
              callback: function callback(data) {
                if (data.css_url) {
                  updateCss(data.css_url, function () {
                    editor_url = getEditorUrl();
                  });
                }

                form_initial_state = serializeForm();
              }
            });
          };

          if ($(this).hasClass('cm-confirm')) {
            $.ceEvent('one', 'ce.form_confirm', convertToCss);
          } else {
            convertToCss();
          }
        }); // Restore LESS button

        $('#theme_editor').on('click', '.cm-te-restore-less', function (e) {
          if (confirm(_.tr('theme_editor.confirm_enable_less'))) {
            $.ceAjax('request', fn_url('theme_editor.restore_less'), {
              method: 'POST',
              result_ids: 'theme_editor',
              callback: function callback(data) {
                if (data.css_url) {
                  updateCss(data.css_url, function () {
                    editor_url = getEditorUrl();
                  });
                }
              }
            });
          }

          return false;
        }); // FIXME: Backward presets compatibility

        $('#theme_editor').on('click', '.cm-te-duplicate-style,.cm-te-duplicate-preset', function () {
          var style_name = validStyleName();

          if (style_name) {
            // FIXME: Backward presets compatibility
            var _style_id = $(this).data('caStyleId') || $(this).data('caPresetId');

            $.ceAjax('request', fn_url('theme_editor.duplicate'), {
              data: {
                style_id: _style_id,
                name: style_name
              },
              result_ids: 'theme_editor',
              callback: function callback() {
                updateCss(fn_url(editor_url + formParams()));
              }
            });
          }
        }); // Enable embedded mode to allow navigation during theme editing

        _.embedded = true;
        _.doc = $('#' + _.init_container);
        _.body = $('#' + _.container);
        form_initial_state = serializeForm();
      }
    });
  }); // Save theme

  $.ceEvent('on', 'ce.formpre_theme_editor_form', function (form, elm) {
    // FIXME: Backward presets compatibility
    var s_name = $('input[name="style[name]"],input[name="preset[name]"]', form);
    var s_id = $('input[name="style_id"],input[name="preset_id"]', form);
    var isCanChangeStyleName = typeof s_id.data('caIsCanChangeStyleName') === 'undefined' ? true : s_id.data('caIsCanChangeStyleName');

    if (s_id.data('caIsDefault') && isCanChangeStyleName) {
      var style_name = validStyleName();

      if (!style_name) {
        return false;
      }

      s_id.val('');
      s_name.val(style_name);
    }

    if ($('.cm-te-css-editor').length) {
      $('.cm-te-css-editor').each(function () {
        $('<input type="hidden">').prop({
          name: $(this).prop('id'),
          value: $(this).ceCodeEditor('value')
        }).appendTo(form);
      });
    }

    return true;
  });
  $.ceEvent('on', 'ce.formajaxpost_theme_editor_form', function (data) {
    updateLogos();
    $('.cm-te-value-changer:first').trigger('change');
    setStyleStatus('clear');
    form_initial_state = serializeForm();

    if (data.css_url) {
      updateCss(data.css_url);
    }
  });
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $themeEditor = context.find('#theme_editor_container');

    if ($themeEditor.length) {
      initContainer($themeEditor);
      initCss();
      initGoogleFonts();
      initPatterns();
    }
  });
  $.ceEvent('on', 'ce.switch_theme_editor_container', function (flag) {
    $('[data-ca-theme-editor="minimizeIconClose"],[data-ca-theme-editor="minimizeIconOpen"]').toggleClass('hidden');
    var $container = $('[data-ca-element="mainContainer"]');

    if ($('#tygh_container').length) {
      $container = $container.add('#tygh_container');
    }

    if (flag) {
      $('#sw_theme_editor_container').addClass('hidden');
      $container.removeClass('te-mode');
      $(':root').removeClass('te-theme-editor-active');
    } else {
      $('#sw_theme_editor_container').removeClass('hidden');
      $container.addClass('te-mode');
      $(':root').addClass('te-theme-editor-active');
    }
  }); // Update URL in layout selector

  $.ceEvent('on', 'ce.ajaxdone', function (elms, scripts, params, response_data, response_text) {
    if (response_data && response_data.current_url) {
      $('a.cm-te-change-layout').each(function () {
        var s = $(this);

        if (s.prop('href')) {
          s.prop('href', $.attachToUrl(response_data.current_url, 's_layout=' + s.data('caLayoutId')));
        }
      });
    }

    if (use_dynamic_style) {
      var te_overlay = $('.te-overlay');
      var te_enabled = te_overlay.hasClass('hidden') || te_overlay.length === 0;
      var style_info = /standalone\.[^&]+\.css/.exec($('link[href*=standalone]:first').prop('href')); // switch theme editor availability

      if ('is_theme_editor_allowed' in response_data) {
        if (!response_data.is_theme_editor_allowed && te_enabled) {
          // extract and store style properties
          style_properties = formParams();
        }

        te_enabled = response_data.is_theme_editor_allowed;
        te_overlay.toggleClass('hidden', response_data.is_theme_editor_allowed);
      } // reload style


      if ('style_file_url' in response_data) {
        var new_style_info = /standalone\.[^&]+\.css/.exec(response_data.style_file_url);
        var new_style_url = '';

        if (te_enabled && style_properties) {
          // context is switched to a vendor-specific content: load vendor style, apply stored style properties
          new_style_url = fn_url(getEditorUrl(new_style_info[0]) + style_properties);
          style_properties = '';
        } else if (new_style_info[0] !== style_info[0]) {
          // context is switched to a general content: reload the whole style
          new_style_url = response_data.style_file_url;
        }

        if (new_style_url) {
          updateCss(new_style_url);
        }
      }
    }

    if ($('#push').length > 0) {
      // StickyFooter
      $.stickyFooter();
    }

    if ($('.cm-te-css-editor').length > 0) {
      $('.cm-te-css-editor').ceCodeEditor('init', 'ace/mode/css');
      $('.cm-te-css-editor').ceCodeEditor('set_show_gutter', false);
    }
  });
  $(window).on('beforeunload', function (e) {
    if (isFormChanged()) {
      return _.tr('text_changes_not_saved');
    }
  });
})(Tygh, Tygh.$);