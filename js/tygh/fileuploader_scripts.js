(function (_, $) {
  var fileuploader = {
    result_id: '',
    set_file: function set_file(name) {
      this.display_filename(this.result_id, 'server', name);
      $('.cm-popup-bg:last').click();
    },
    show_image: function show_image(name) {
      $('#fo_img').prop('src', _.current_location + '/' + name);
    },
    start_server_browser: function start_server_browser() {
      var minZ = $.ceDialog('get_last').parent('.ui-front').css('z-index');
      var options = $.extend(_.fileManagerOptions, {
        url: fn_url('elf_connector.files?security_hash=' + _.security_hash),
        cutURL: _.allowed_file_path,
        getFileCallback: function getFileCallback(file) {
          $('#server_file_browser').dialog('close');
          var parts = file.path.split('/');
          parts.shift();
          var path = parts.join('/');
          fileuploader.display_filename(fileuploader.result_id, 'server', path);
        }
      });
      var dlg = $('<div id="server_file_browser"></div>').elfinder(options).dialog({
        width: 900,
        height: 500,
        modal: true,
        title: _.tr('file_browser'),
        close: function close(event, ui) {
          $('#server_file_browser').dialog('destroy').elfinder('destroy').remove();
        }
      });

      if (minZ) {
        dlg.closest('.ui-dialog').css('z-index', minZ + 1);
      }
    },
    init: function init(dialog_id, result_id) {
      this.result_id = result_id;

      if (!$.fn.elfinder) {
        $.loadCss(['js/lib/elfinder/css/elfinder.min.css']);
        $.loadCss(['js/lib/elfinder/css/theme.css']);
        $.getScript('js/lib/elfinder/js/elfinder.min.js', function () {
          if (_.cart_language !== 'en' && $.ceFileUploader('isAvailableLanguage', _.cart_language)) {
            $.getScript("js/lib/elfinder/js/i18n/elfinder.".concat($.ceFileUploader('tranformLanguage', _.cart_language), ".js")).then(null, function () {
              return $.getScript('js/lib/elfinder/js/i18n/elfinder.LANG.js');
            }).done(fileuploader.start_server_browser);
          } else {
            fileuploader.start_server_browser();
          }
        });
      } else {
        this.start_server_browser();
      }
    },
    show_loader: function show_loader(elm_id) {
      var suffix = elm_id.str_replace('local_', '').str_replace('server_', '').str_replace('url_', '');
      var max_file_size_bytes;
      var max_file_size_mbytes;
      var file_holder;
      var native_file_holder;

      if (elm_id.indexOf('local') != -1) {
        file_holder = $('#' + elm_id);
        native_file_holder = file_holder[0];
        this.display_filename(suffix, 'local', file_holder.val()); // Check if file size more than available POST_MAX_SIZE

        if (native_file_holder.files && native_file_holder.files[0]) {
          if (parseInt(_.post_max_size_bytes) == 0 || parseInt(_.post_max_size_bytes) > parseInt(_.files_upload_max_size_bytes)) {
            max_file_size_bytes = _.files_upload_max_size_bytes;
            max_file_size_mbytes = _.files_upload_max_size_mbytes;
          } else {
            max_file_size_bytes = _.post_max_size_bytes;
            max_file_size_mbytes = _.post_max_size_mbytes;
          }

          if (native_file_holder.files[0].size > max_file_size_bytes) {
            $.ceNotification('show', {
              type: 'E',
              title: _.tr('error'),
              message: _.tr('file_is_too_large').replace('[size]', max_file_size_mbytes)
            });
            this.clean_form(suffix);
          } else if (parseInt(_.post_max_size_bytes)) {
            var sum_files_size = 0;
            $("[type=file]").each(function () {
              if ($(this)[0].files.length > 0) {
                sum_files_size += $(this)[0].files[0].size;
              }
            });

            if (sum_files_size > _.post_max_size_bytes) {
              $.ceNotification('show', {
                type: 'E',
                title: _.tr('error'),
                message: _.tr('files_are_too_large').replace('[size]', _.post_max_size_mbytes)
              });
              this.clean_form(suffix);
            }
          }
        }
      }

      if (elm_id.indexOf('server') != -1) {
        fileuploader.init('box_server_upload', suffix);
      }

      if (elm_id.indexOf('url') != -1) {
        var e_url = $('#message_' + suffix + ' .upload-filename').html();
        var n_url = '';

        if (n_url = prompt($('#' + elm_id).html(), e_url.indexOf('://') !== -1 ? e_url : '')) {
          this.display_filename(suffix, 'url', n_url);
        }
      }
    },
    display_filename: function display_filename(id, type, val) {
      // Highlight active link
      var types = ['local', 'server', 'url'];
      var file = $('#message_' + id + ' p.cm-fu-file');
      var no_file = $('#message_' + id + ' p.cm-fu-no-file');
      var hidden_input = $('#hidden_input_' + id);

      for (var i = 0; i < types.length; i++) {
        if (types[i] == type) {
          $('#' + types[i] + '_' + id).addClass('active');
        } else {
          $('#' + types[i] + '_' + id).removeClass('active');
        }
      }

      $('#type_' + id).val(type); // switch type

      $('#file_' + id).val(val); // set file name

      if (val == '') {
        file.hide();
        no_file.show(); //Clear the input[type=file]

        var file_container = $('#link_container_' + id + ' > .upload-file-local');
        var content = file_container.html();
        file_container.html(content);
      } else {
        no_file.hide(); // cut off the C:/Fakepath C:\Fake path or whatever standards compliant stuff proposed by IE7+ and Opera

        var pieces = val.split(/(\\|\/)/g);
        var val = pieces[pieces.length - 1];
        $('.upload-filename', file).html(val).prop('title', val); // display file name

        hidden_input.prop('disabled', false);
        file.show();
      }

      $.ceEvent('trigger', 'ce.fileuploader.display_filename', [id, type, val]);
    },
    clean_selection: function clean_selection(elm_id) {
      var suffix = elm_id.str_replace('clean_selection_', '');
      this.display_filename(suffix, '', '');
    },
    get_content_callback: function get_content_callback(data) {
      if (data.content.indexOf('text:') == 0) {
        $('#fo_img').hide();
        $('#fo_no_preview').hide();
        $('#fo_preview').show();
        $('#fo_preview').val(data.content.substr(5));
      } else if (data.content.indexOf('image:') == 0) {
        $('#fo_img').show();
        $('#fo_no_preview').hide();
        $('#fo_preview').hide();
        fileuploader.show_image(data.content.substr(6));
      } else {
        $('#fo_img').hide();
        $('#fo_no_preview').show();
        $('#fo_preview').hide();
      }
    },
    validate_url: function validate_url(url) {
      var protexpr = /:\/\//;

      if (!protexpr.test(url)) {
        url = 'http://' + url;
      }

      var regexp = /^[A-Za-z]+:\/\/[A-Za-z0-9-_:@]+\.[A-Za-z0-9-\+_%~&\\?\/.=()]+$/;
      return regexp.test(url);
    },
    switch_title: function switch_title(elm_id, plural) {
      fileuploader.reloadDialog();
      var cnt = $('#link_container_' + elm_id);
      $('[data-ca-multi="Y"]', cnt).toggleBy(!plural);
      $('[data-ca-multi="N"]', cnt).toggleBy(plural);
    },
    check_required_field: function check_required_field(id_var_name, label_id) {
      if (!label_id) {
        return false;
      }

      var found = false;
      var images_count = 0;
      $('#' + label_id).val('');
      $('div[id*=message_' + id_var_name + '] p:visible .upload-filename').each(function () {
        if ($(this).html() != '') {
          $('#' + label_id).val(id_var_name).change();
          found = true;
        }
      });
      elm_id = $('div[id*=message_' + id_var_name + ']:last').prop('id').str_replace('message_', '');
      fileuploader.switch_title(elm_id, found); // Check an ability to delete already uploaded files

      $('div[id*=message_' + id_var_name + '_].cm-uploaded-image p:visible .upload-filename').each(function () {
        if ($(this).html() != '') {
          images_count++;
        }
      });

      if (images_count == 0 || images_count > 1 || !$('label[for=' + label_id + ']').hasClass('cm-required')) {
        // Allow to delete
        $('img[id*=clean_selection_' + id_var_name + '_]').show();
      } else {
        // Disallow to delete
        $('img[id*=clean_selection_' + id_var_name + '_]').hide();
      }
    },
    check_image: function check_image(elm_id) {
      var suffix = elm_id.str_replace('local_', '').str_replace('server_', '').str_replace('url_', '');

      if ($('#message_' + suffix + ' .upload-filename').html() != '') {
        parent_id = $('#file_uploader_' + suffix).cloneNode(1).str_replace('file_uploader_', '');
        $('#link_container_' + suffix).hide();
        elm_id = parent_id;
        fileuploader.switch_title(elm_id, true);
      }
    },
    toggle_links: function toggle_links(elm_id, mode) {
      var suffix = elm_id.str_replace('local_', '').str_replace('server_', '').str_replace('url_', '').str_replace('clean_selection_', '');

      if (mode == 'hide') {
        if ($('#message_' + suffix + ' .upload-filename').html() != '') {
          $('#link_container_' + suffix).hide();
        }
      } else {
        $('#link_container_' + suffix).show();
        $('#message_' + suffix + ' .upload-filename').html('');
      }
    },
    clean_form: function clean_form(suffix) {
      suffix = suffix ? suffix : '';
      var $fileuploaderElement = $("#file_uploader_".concat(suffix));
      $fileuploaderElement.find('.cm-fu-file .cs-icon').trigger('click');
    },
    removeFile: function removeFile($removeFileButton) {
      var elementId = $removeFileButton.prop('id');
      var $fileInput = $('#hidden_input_' + elementId);
      $fileInput.prop('disabled', true);
      var $fileBlock = $("[data-file-id='" + elementId + "']");
      $fileBlock.remove();
      var required = $removeFileButton.hasClass('cm-file-required');

      if (required) {
        var $fileLabel = $("[for='type_" + elementId + "']");
        $fileLabel.addClass('cm-required');
      }
    },
    reloadDialog: function reloadDialog() {
      $.ceDialog('get_last').ceDialog('reload');
    }
  };
  _.fileuploader = fileuploader;
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $removeFileButton = $(context).find('.cm-file-remove');

    if ($removeFileButton.length > 0) {
      $removeFileButton.on('click', function (e) {
        fileuploader.removeFile($(this));
      });
    }
  });
})(Tygh, Tygh.$);