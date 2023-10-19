function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

(function (_, $) {
  var prevent_scroll = false;

  (function ($) {
    var inputs_selector = 'input, textarea, select';
    var place_order_buttons_selector = '.cm-checkout-place-order-buttons';
    var recalculate_buttons_selector = '.cm-checkout-recalculate-buttons';
    var form_values = {};

    function changed() {
      var form = $(this);

      if (checkFormChanged(form) && form.ceFormValidator('check')) {
        displayRecalculate(form);
      }
    }

    function displayRecalculate(form) {
      var changed_groups = {
        user_data: false
      };
      form.find(inputs_selector).each(function () {
        var elm = $(this);

        if (elm.attr('type') != 'hidden' && form_values[elm.attr('name') + '_' + elm.attr('id')] != getVal(elm)) {
          var elm_name = elm.attr('name');

          if (elm_name) {
            var elm_group = elm_name.split('[')[0];
            changed_groups[elm_group] = true;
          }
        }
      }); // require recalculate only when user data changed

      if (changed_groups['user_data']) {
        form.find(place_order_buttons_selector).hide();
        form.find(recalculate_buttons_selector).show();
      }
    }

    function saveFormValues(form) {
      form.find(inputs_selector).each(function () {
        var elm = $(this);
        form_values[elm.attr('name') + '_' + elm.attr('id')] = getVal(elm);
      });
    }

    function checkFormChanged(form) {
      var result = false;
      form.find(inputs_selector).each(function () {
        var elm = $(this);

        if (elm.attr('type') != 'hidden' && form_values[elm.attr('name') + '_' + elm.attr('id')] != getVal(elm)) {
          result = true;
        }
      });
      return result;
    }

    function getVal(elm) {
      return ['checkbox', 'radio'].indexOf(elm.attr('type')) != -1 ? elm.is(':checked') : elm.val();
    }

    var methods = {
      init: function init(v1) {
        var form = $(this);

        if (form.data('CheckoutRecalculateFormInited')) {
          return false;
        }

        form.data('CheckoutRecalculateFormInited', true);

        if (!form.find(recalculate_buttons_selector).length) {
          return true;
        }

        saveFormValues(form);
        form.find(inputs_selector).on('input propertychange change', function (e) {
          changed.apply(form);
        });
      }
    };

    $.fn.ceCheckoutRecalculateForm = function (method) {
      if (methods[method]) {
        return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
      } else if (_typeof(method) === 'object' || !method) {
        return methods.init.apply(this, arguments);
      } else {
        $.error('ty.ceCheckoutRecalculateForm: method ' + method + ' does not exist');
      }
    };
  })($);

  $.ceEvent('on', 'ce.commoninit', function (context) {
    var elm = context.find('.ty-step__title-active');

    if (elm.length && !prevent_scroll) {
      $.scrollToElm(elm);
    }

    prevent_scroll = false;
    elm = context.find('.cm-checkout-recalculate-form');

    if (elm.length) {
      elm.ceCheckoutRecalculateForm();
    }

    $customerNotesText = $('.ty-customer-notes__text');

    if (!$customerNotesText.length) {
      return;
    }

    restoreCustomerNote($('.ty-customer-notes__text'));
  });
  $(_.doc).on('click', '.cm-checkout-place-order', function () {
    var jelm = $(this),
        form = jelm.parents('form');

    if (!form.ceFormValidator('check')) {
      return;
    }

    form.removeClass('cm-ajax').removeClass('cm-ajax-full-render');
    $.ceEvent('on', 'ce.formpost_' + $(form).attr('name'), function () {
      $.toggleStatusBox('show', {
        statusContent: '<span class="ty-ajax-loading-box-with__text-wrapper">' + _.tr('placing_order') + '</span>',
        statusClass: 'ty-ajax-loading-box_text_block'
      });
    });
  });
  $(_.doc).on('click', '.cm-checkout-recalculate', function () {
    prevent_scroll = true;
  });
  $(_.doc).on('click', '.cm-select-payment', function () {
    var jelm = $(this),
        url = jelm.data('caUrl'),
        result_ids = jelm.data('caResultIds');
    $.ceAjax('request', fn_url(url + '&payment_id=' + jelm.val()), {
      result_ids: result_ids,
      full_render: true
    });
  });
  $.ceEvent('on', 'ce.loadershow', function () {
    var forms = $('#checkout_steps', _.doc).find('form');
    forms.on('submit.checkout_lock', function () {
      return false;
    });
    forms.find('button[type="submit"]').on('click.checkout_lock', function () {
      return false;
    });
  });
  $.ceEvent('on', 'ce.loaderhide', function () {
    var forms = $('#checkout_steps', _.doc).find('form');
    forms.off('submit.checkout_lock');
    forms.find('button[type="submit"]').off('click.checkout_lock');
  });
  $.ceEvent('on', 'ce.payments_tab_switch', function () {
    restoreCustomerNote($('.ty-customer-notes__text'));
  });
  var customerNote = '';

  function saveCustomerNote($textarea) {
    customerNote = $textarea.val();
  }

  function restoreCustomerNote($textarea) {
    $textarea.val(customerNote);
  }

  $(_.doc).on('input', '.ty-customer-notes__text', function () {
    saveCustomerNote($(this));
  });
})(Tygh, Tygh.$);

function fn_switch_checkout_type() {
  var $ = Tygh.$;
  $.ceNotification('closeAll');
  $('#step_one_register').show();
  $('#step_one_login').hide();
}

function fn_show_checkout_buttons(type) {
  var $ = Tygh.$;

  if (type == 'register') {
    $('#register_checkout').show();
    $('#anonymous_checkout').hide();
  } else {
    $('#register_checkout').hide();
    $('#anonymous_checkout').show();
  }
}
/**
 * This workaround is mainly required for a shipping estimator block to work outside of cart page.
 * This function cannot be just moved from checkout.js, because some themes ("boilerplate" for example)
 * might not include cs-cart's native core.js, which will lead to breaking delivery estimation
 * functionality on the cart page.
 **/


if (window.fn_calculate_total_shipping === undefined) {
  var fn_calculate_total_shipping = function fn_calculate_total_shipping(wrapper_id) {
    var $ = Tygh.$;
    wrapper_id = wrapper_id || 'shipping_estimation';
    var parent = $('#' + wrapper_id);
    var radio = $('input[type=radio]:checked', parent);
    var params = [];
    $.each(radio, function (id, elm) {
      params.push({
        name: elm.name,
        value: elm.value
      });
    });
    var url = fn_url('checkout.shipping_estimation.get_total');

    for (var i in params) {
      url += '&' + params[i]['name'] + '=' + encodeURIComponent(params[i]['value']);
    }

    var suffix = parent.find('input[name="suffix"]').first().val();
    $.ceAjax('request', url, {
      result_ids: 'shipping_estimation_total' + suffix,
      data: {
        additional_id: parent.find('input[name="additional_id"]').first().val()
      },
      method: 'post'
    });
  };
}

function fn_calculate_total_shipping_cost(is_hidden) {
  is_hidden = is_hidden || false;
  var params = [],
      $parents = Tygh.$('#shipping_rates_list'),
      $radio = Tygh.$('input[type=radio]:checked', $parents),
      $shippingInfo = Tygh.$('[data-ca-shipping-field]'),
      url = fn_url('checkout.checkout'),
      result_ids = 'shipping_rates_list,checkout_info_summary_*,checkout_info_order_info_*',
      $additional_result_ids = Tygh.$('input[name="additional_result_ids[]"]', $parents);
  Tygh.$.each($radio, function (id, elm) {
    params.push({
      name: elm.name,
      value: elm.value
    });
  });
  Tygh.$.each($shippingInfo, function (id, elm) {
    params.push({
      name: elm.name,
      value: elm.value
    });
  });
  Tygh.$.each($additional_result_ids, function (id, elm) {
    result_ids += ',' + $(elm).val();
  });

  for (var i in params) {
    url += '&' + params[i]['name'] + '=' + encodeURIComponent(params[i]['value']);
  }

  Tygh.$.ceAjax('request', url, {
    result_ids: result_ids,
    method: 'get',
    full_render: true,
    hidden: is_hidden,
    callback: function callback(response) {
      $.ceEvent('trigger', 'ce.total-shipping-cost.calculated', [response]);
    }
  });
}