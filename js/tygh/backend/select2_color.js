(function (_, $) {
  $.ceEvent('on', 'ce.select2.create_tag', function (elm, object, term) {
    if (object.type !== 'color') {
      return;
    }

    var text = $.trim(term);

    if (text === '') {
      return;
    }

    var color = '#ffffff';
    var paramIndex = text.indexOf('#');

    if (paramIndex !== -1) {
      color = text.substring(paramIndex, paramIndex + 7).toLowerCase(); // Check that the string is a HEX color code

      if (!/(^#[0-9A-F]{6}$)|(^#[0-9A-F]{3}$)/i.test(color)) {
        color = '#ffffff';
      }

      text = text.slice(0, paramIndex - 1);
    }

    if (color) {
      Object.assign(object, {
        text: text,
        color: color,
        content: {
          text: text,
          append: color
        }
      });
    }
  });
  $.ceEvent('on', 'ce.select_template_result', function (object, elm) {
    if (object.type !== 'color') {
      return;
    }

    var dataColor = $(object.element).data('caFeatureColor');

    if (!dataColor) {
      return;
    }

    object.content.append = dataColor || '#ffffff';
  });
  $.ceEvent('on', 'ce.select_template_selection', function (object, list_elm, $container) {
    if (object.type !== 'color') {
      return;
    }

    var dataColor = $(object.element).data('caFeatureColor');

    if (!dataColor) {
      return;
    }

    if (!object.content.append) {
      setTimeout(function () {
        $($container).trigger('change');
      }, 100);
    }

    object.content.append = dataColor || '#ffffff';
  });
})(Tygh, Tygh.$);