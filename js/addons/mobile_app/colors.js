function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; var ownKeys = Object.keys(source); if (typeof Object.getOwnPropertySymbols === 'function') { ownKeys = ownKeys.concat(Object.getOwnPropertySymbols(source).filter(function (sym) { return Object.getOwnPropertyDescriptor(source, sym).enumerable; })); } ownKeys.forEach(function (key) { _defineProperty(target, key, source[key]); }); } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

(function (_, $) {
  $(function () {
    var url = fn_url('addons.update.rebuild?addon=mobile_app');
    $(_.doc).on('change', '.js-mobile-app-input', function () {
      var stored_colors = {};
      $('.js-mobile-app-input').each(function (index, element) {
        if (!stored_colors[element.dataset.targetInputName]) {
          stored_colors[element.dataset.targetInputName] = {};
        }

        stored_colors[element.dataset.targetInputName][element.dataset.target] = {
          'value': element.value
        };
      });
      $.ceAjax('request', url, {
        method: "get",
        data: {
          colors: stored_colors
        },
        result_ids: "colors_variables,color_presets"
      });
    });
    $(_.doc).on('change', '.js-mobile-app-color-preset-input', function () {
      var selected_preset = $(this).val(),
          request_data = {
        selected_preset: selected_preset
      };

      if (selected_preset === 'C') {
        var stored_colors = {};
        $('.js-mobile-app-input').each(function () {
          var $self = $(this),
              inputVal = $self.val(),
              inputName = $self.data('targetInputName'),
              target = $self.data('target');
          stored_colors[inputName] = _objectSpread({}, stored_colors[inputName], _defineProperty({}, target, {
            'value': inputVal
          }));
        });
        request_data = _objectSpread({}, request_data, {
          colors: stored_colors
        });
      }

      $.ceAjax('request', url, {
        result_ids: 'colors_variables,color_presets',
        method: 'get',
        data: request_data,
        scroll: selected_preset === 'C' ? 'mobile_app_custom_settings' : '',
        callback: function callback(data) {
          var $collapseBlock = $('#mobile_app_screens_app');

          if (selected_preset === 'C' || selected_preset !== 'C' && $collapseBlock.hasClass('in')) {
            $collapseBlock.collapse();
          }
        }
      });
    });
  });
})(Tygh, Tygh.$);