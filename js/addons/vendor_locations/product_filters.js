(function (_, $) {
  function triggerProductFilter($elem) {
    var $checkbox = $('#' + $elem.data('caGeocompleteValueElemId')),
        data = [];
    $.each($elem.data('caFilterValue'), function (key, value) {
      data.push(value);
    });
    $checkbox.val($.ceGeolocate('base64encode', data.join('|'))).prop('checked', true).trigger('change');
  }

  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $sliders = context.find('.cm-zone-radius-slider');

    if ($sliders.length) {
      $sliders.each(function () {
        var $slider = $(this),
            $slider_value_input = $('#' + $slider.prop('id') + '_right'),
            $geocomplete_input = $('#' + $slider.data('caFilterGeocompleteElemId'));
        $slider.slider({
          disabled: !!$slider.data('caSliderDisabled'),
          range: 'min',
          min: $slider.data('caSliderMin'),
          max: $slider.data('caSliderMax'),
          value: $slider.data('caSliderValue'),
          slide: function slide(event, ui) {
            $slider_value_input.text(ui.value);
          },
          change: function change(event, ui) {
            var filter_value = $geocomplete_input.data('caFilterValue');

            if (filter_value && ui.value != filter_value.radius) {
              filter_value.radius = ui.value;
              $geocomplete_input.data('caFilterValue', filter_value);
              triggerProductFilter($geocomplete_input);
            }
          }
        });
      });
    }
  });
  $.ceEvent('on', 'ce.geocomplete.select', function ($elem, location, result) {
    var filter_type = $elem.data('caFilterType');

    if (!filter_type) {
      return;
    }

    if (filter_type === 'region') {
      $elem.data('caFilterValue', {
        place_id: location.place_id,
        country: location.country,
        state: null,
        locality: location.locality
      });
    } else {
      var $slider = $('#' + $elem.data('caFilterSliderElemId'));
      $elem.data('caFilterValue', {
        place_id: location.place_id,
        lat: location.lat,
        lng: location.lng,
        radius: $slider.slider('option', 'value')
      });
    }

    triggerProductFilter($elem);
  });
})(Tygh, Tygh.$);