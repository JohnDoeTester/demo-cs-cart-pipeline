(function ($) {
  var icon_magic_dark = '<span class="ty-icon redactor-custom-icon icon-magic icon-dark"></span>';

  $.Redactor.prototype.blockManager = function () {
    return {
      langs: {
        en: {
          "block-manager": Tygh.tr('block_manager'),
          "select-block": Tygh.tr('select_block')
        }
      },
      init: function init() {
        var that = this;
        var barDropdown = {};
        barDropdown.select = {
          title: that.lang.get('select-block'),
          func: that.blockManager.openPicker
        };
        var barBtn = this.button.add('blockManager', this.lang.get('block-manager'));
        this.button.setIcon(barBtn, icon_magic_dark);
        this.button.addDropdown(barBtn, barDropdown);
        var opener = $('<a href="' + fn_url('block_manager.manage_select') + '" data-ca-target-id="wysiwyg_bm_picker" class="hidden cm-dialog-opener" title="' + that.lang.get('select-block') + '"></a>');
        opener.appendTo('body');
      },
      openPicker: function openPicker() {
        var that = this;
        $.ceEvent('one', 'ce.bm.block.selected', that.blockManager.pasteCodeOfBlock(this));
        $('[data-ca-target-id="wysiwyg_bm_picker"]').click();
      },
      pasteCodeOfBlock: function pasteCodeOfBlock(context) {
        return function (data) {
          $.ceDialog('get_last').ceDialog('close');
          context.placeholder.hide();
          context.buffer.set();
          context.air.collapsed();
          context.insert.html('<hr class=\'wisywig-block-loader cm-block-loader\' data-ca-object-key=\'' + data.blockUid + '\' data-ca-block-name=\'' + data.caBlockName + '\'>');
          context.selection.restore();
        };
      }
    };
  };
})(jQuery);