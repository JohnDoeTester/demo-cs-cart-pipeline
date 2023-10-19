function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; var ownKeys = Object.keys(source); if (typeof Object.getOwnPropertySymbols === 'function') { ownKeys = ownKeys.concat(Object.getOwnPropertySymbols(source).filter(function (sym) { return Object.getOwnPropertyDescriptor(source, sym).enumerable; })); } ownKeys.forEach(function (key) { _defineProperty(target, key, source[key]); }); } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

(function (_, $) {
  $(function () {
    var url = fn_url('addons.update.rebuild?addon=vendor_panel_configurator'); // Изменение цветов

    $(_.doc).on('change', '.js-vendor-panel-configurator-colors-input', function () {
      var stored_colors = {};
      $('.js-vendor-panel-configurator-colors-input').each(function (index, element) {
        if (!stored_colors[element.dataset.target]) {
          stored_colors[element.dataset.target] = {};
        }

        var elem = $('#' + element.dataset.targetInputName)[0];
        stored_colors[element.dataset.target] = elem.value;
      });
      var req = {
        vendor_panel: _objectSpread({}, stored_colors, {
          color_schema: 'Custom'
        })
      };
      $.ceAjax('request', url, {
        method: 'get',
        data: {
          vendor_panel: _objectSpread({}, stored_colors, {
            color_schema: 'Custom'
          })
        },
        result_ids: 'vendor_panel_config'
      });
    }); // Смена пресета

    $(_.doc).on('change', '.js-vendor-panel-color-schema-input', function () {
      $.ceAjax('request', url, {
        result_ids: 'vendor_panel_config',
        method: 'get',
        data: {
          vendor_panel: {
            color_schema: $(this).val()
          }
        }
      });
    });
  });
})(Tygh, Tygh.$);