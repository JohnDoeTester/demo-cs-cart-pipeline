(function (_, $) {
  var $checkout, $country, $city, $zipCode, $autocompleteCity, $shippingMethods, $userProfiles, $shipToAnother;
  var SHIPPING_PREFIX = 's_',
      BILLING_PREFIX = 'b_',
      NAME_SEPARATOR = ' ',
      EMPTY_STATE_CODE = "\xA0",
      // "\xc2\xa0"
  KEY_CODE_ENTER = 13,
      KEY_CODE_UP_ARROW = 38,
      KEY_CODE_DOWN_ARROW = 40,
      LONG_REQUEST_DELAY = 5000,
      SHORT_REQUEST_DELAY = 300;

  function assign(obj, keyPath, value) {
    var lastKeyIndex = keyPath.length - 1;

    for (var i = 0; i < lastKeyIndex; ++i) {
      var key = keyPath[i];
      if (!(key in obj)) obj[key] = {};
      obj = obj[key];
    }

    obj[keyPath[lastKeyIndex]] = value;
  }

  var methods = {
    init: function init($jelm) {
      $checkout = $jelm;
      $country = methods.getElement('country');
      $city = methods.getElement('city');
      $zipCode = methods.getZipCode();
      $autocompleteCity = methods.getElement('city-autocomplete');
      $shippingMethods = methods.getElement('shipping-methods');
      $userProfiles = methods.getElement('user-profiles');

      if ($autocompleteCity.length) {
        methods.initAutocompleteCity();
      } else {
        methods.initPlainCityInput();
      }

      methods.initZipCode();
      methods.bindAbandonedCartFieldsSaving();
      methods.initMultipleProfileRoutines();
      methods.initCustomValidation(methods.getElement('checkout-form'));
      methods.toggleAddress();
      methods.registerValidators();
      methods.setupCheckoutForm(methods.getElement('checkout-form'));
    },
    bindAbandonedCartFieldsSaving: function bindAbandonedCartFieldsSaving() {
      var addressFields = ['s_country', 's_state', 's_city', 's_zipcode', 's_address', 's_address_2', 'b_country'];
      var debouncedUpdateCustomerInfo = methods.debounceUpdateCustomerInfo(addressFields); // Events for inputs and textareas

      $('[data-ca-lite-checkout-auto-save="true"]', $checkout).on('input _input change', debouncedUpdateCustomerInfo); // Events for selects, checkboxes, radios, hidden and disabled inputs

      $('[data-ca-lite-checkout-auto-save-on-change="true"]', $checkout).on('change', debouncedUpdateCustomerInfo);
    },
    debounce: function debounce(fn) {
      var timer;
      return function debounced() {
        clearTimeout(timer);
        var args = arguments;
        var that = this;
        var event = args[0];

        if (event.type === 'change') {
          wait = SHORT_REQUEST_DELAY;
        } else {
          wait = LONG_REQUEST_DELAY;
        }

        timer = setTimeout(function callOriginalFn() {
          fn.apply(that, args);
        }, wait);
      };
    },
    debounceUpdateCustomerInfo: function debounceUpdateCustomerInfo(addressFields, timeout) {
      return methods.debounce(function () {
        var $input = $(this);
        var fieldName = $input.data('caLiteCheckoutField').replace(/^user_data\./, '');
        /**
         * Shipping address affects available shipping methods, thus afffecting available payment methods.
         * So, when updating the shipping address, the checkout page must be redrawn completely.
         */

        var isAddressField = addressFields.indexOf(fieldName) !== -1;
        methods.updateCustomerInfo($.noop, false, true, true, isAddressField);
      });
    },
    initMultipleProfileRoutines: function initMultipleProfileRoutines() {
      if ($userProfiles.length === 0) {
        return;
      }

      $('.js-lite-checkout-edit-profile-popup-opener').on('change click', methods.openEditProfilePopup);
      $('.js-lite-checkout-profile-selector', $userProfiles).on('change', methods.switchProfile);
    },
    initCustomValidation: function initCustomValidation($form) {
      var $customValidationFields = $form.find('[data-ca-custom-validation]');

      if (!$customValidationFields.length) {
        return;
      }

      ;
      $customValidationFields.each(function () {
        var $field = $(this);

        if ($form.find('label[for="' + $field.attr('id') + '"]').length) {
          $field.prop('required', false);
        }
      });
    },
    lockShippingMethodSelector: function lockShippingMethodSelector() {
      $shippingMethods.toggleClass($shippingMethods.data('caLiteCheckoutOverlayClass'), true);
    },
    unlockShippingMethodSelector: function unlockShippingMethodSelector() {
      $shippingMethods.toggleClass($shippingMethods.data('caLiteCheckoutOverlayClass'), false);
    },
    setupCheckoutForm: function setupCheckoutForm($checkoutForm) {
      $.ceEvent('on', 'ce.formpost_' + $checkoutForm.prop('name'), function (form) {
        var $checkoutForm = $(form);

        if ($checkoutForm.data('caLiteCheckoutReadyForCheckout') === true) {
          $.toggleStatusBox('show', {
            statusContent: '<span class="ty-ajax-loading-box-with__text-wrapper">' + _.tr('placing_order') + '</span>',
            statusClass: 'ty-ajax-loading-box_text_block'
          });
          return true;
        }

        return methods.check(function (result) {
          if (!result) {
            return false;
          }

          methods.updateCustomerInfo(function (data) {
            setTimeout(function () {
              $checkoutForm.data('caLiteCheckoutReadyForCheckout', true);
              $checkoutForm.submit();
            }, 100);
          }, false, false);
          return false;
        });
      });
    },
    check: function check(callback) {
      callback = callback || false;

      if (callback === false) {
        return true;
      }

      var $checkoutForm = methods.getElement('checkout-form'),
          result = $checkoutForm.ceFormValidator('check'); // check

      $checkoutForm.ceFormValidator('check', false); // notice

      return callback(result);
    },
    registerValidators: function registerValidators() {
      $.ceFormValidator('registerValidator', {
        class_name: 'cm-shipping-available-label',
        message: '',
        func: function func() {
          return methods.isValidActiveShipping();
        }
      });
    },
    isValidActiveShipping: function isValidActiveShipping() {
      var isOverlayActive = $shippingMethods.hasClass($shippingMethods.data('caLiteCheckoutOverlayClass'));
      return !isOverlayActive && !$shippingMethods.find('[data-ca-lite-checkout-element="shipping-method"]' + '[data-ca-lite-checkout-shipping-method-disabled="true"]:checked').length;
    },
    serializeCheckout: function serializeCheckout(userDataExtend) {
      userDataExtend = typeof userDataExtend === 'undefined' ? {} : userDataExtend;
      var serializedCheckout = {
        result_ids: 'litecheckout_form,checkout_info*,checkout_order_info*,geo_maps_location_block*',
        dispatch: 'checkout.update_steps',
        full_render: 'Y',
        user_data: {}
      };
      var $serializedFields = $('[data-ca-lite-checkout-field]', $checkout);
      $serializedFields.filter(function (key, field) {
        var $field = $(field),
            is_checkable = $field.is(':checkbox') || $field.is(':radio');
        return !$field.prop('disabled') && (!is_checkable || $field.is(':checked'));
      }).each(function (index, elm) {
        var $elm = $(elm),
            value;

        if ($(elm).data('caLiteCheckoutElement')) {
          value = methods.getElement($(elm).data('caLiteCheckoutElement')).val();
        } else {
          value = $elm.val();
        }

        var keyPath = $elm.data('caLiteCheckoutField').split('.');
        assign(serializedCheckout, keyPath, value);
      });
      var userData = serializedCheckout.user_data;
      var fullnameFormat, fullnameParts;

      if (userData.fullname) {
        fullnameFormat = $('[data-ca-lite-checkout-field="user_data.fullname"]').data('caFullnameFormat');
        fullnameParts = methods.splitFullname(userData.fullname, fullnameFormat);
        userData.firstname = fullnameParts.firstname;
        userData.lastname = fullnameParts.lastname;
      }

      if (userData.s_fullname) {
        fullnameFormat = $('[data-ca-lite-checkout-field="user_data.s_fullname"]').data('caFullnameFormat');
        fullnameParts = methods.splitFullname(userData.s_fullname, fullnameFormat);
        userData.s_firstname = fullnameParts.firstname;
        userData.s_lastname = fullnameParts.lastname;
      }

      if (userData.b_fullname) {
        fullnameFormat = $('[data-ca-lite-checkout-field="user_data.b_fullname"]').data('caFullnameFormat');
        fullnameParts = methods.splitFullname(userData.b_fullname, fullnameFormat);
        userData.b_firstname = fullnameParts.firstname;
        userData.b_lastname = fullnameParts.lastname;
      }

      var addressPosition = methods.getElement('address-group').data('caAddressPosition') || "shipping_first",
          mainAddressZone = addressPosition == "shipping_first" ? SHIPPING_PREFIX : BILLING_PREFIX,
          altAddressZone = addressPosition == "shipping_first" ? BILLING_PREFIX : SHIPPING_PREFIX;

      if (!userData.fullname && !$('[data-ca-lite-checkout-field="user_data.firstname"]').length) {
        if (userData[mainAddressZone + "firstname"]) {
          userData.firstname = userData[mainAddressZone + "firstname"];
        } else if (userData[altAddressZone + "firstname"]) {
          userData.firstname = userData[altAddressZone + "firstname"];
        }
      }

      if (!userData.fullname && !$('[data-ca-lite-checkout-field="user_data.lastname"]').length) {
        if (userData[mainAddressZone + "lastname"]) {
          userData.lastname = userData[mainAddressZone + "lastname"];
        } else if (userData[altAddressZone + "lastname"]) {
          userData.lastname = userData[altAddressZone + "lastname"];
        }
      }

      if (!$('[data-ca-lite-checkout-field="user_data.phone"]').length) {
        if (userData[mainAddressZone + "phone"]) {
          userData.phone = userData[mainAddressZone + "phone"];
        } else if (userData[altAddressZone + "phone"]) {
          userData.phone = userData[altAddressZone + "phone"];
        }
      }

      if (userData.customer_notes) {
        serializedCheckout.customer_notes = userData.customer_notes;
      }

      serializedCheckout.user_data = userData;

      if (userDataExtend) {
        serializedCheckout.user_data = $.extend(serializedCheckout.user_data, userDataExtend);
      }

      return serializedCheckout;
    },
    splitFullname: function splitFullname(fullname, fullnameFormat) {
      fullnameFormat = fullnameFormat || 'firstname_first';
      var nameParts = fullname.split(NAME_SEPARATOR).map(function (part) {
        return part.trim();
      }).filter(function (part) {
        return part !== '';
      }),
          firstname = '',
          lastname = '';

      if (fullnameFormat === 'firstname_first') {
        if (nameParts.length > 1) {
          lastname = nameParts.pop();
        }

        firstname = nameParts.join(NAME_SEPARATOR);
      } else {
        lastname = nameParts.shift();
        firstname = nameParts.join(NAME_SEPARATOR);
      }

      return {
        firstname: firstname,
        lastname: lastname
      };
    },
    updateShipping: function updateShipping(userDataExtend) {
      $.ceAjax('request', fn_url(''), {
        method: 'post',
        caching: false,
        data: methods.serializeCheckout(userDataExtend)
      });
    },
    autocompleteCity: function autocompleteCity(query, countryCode, callback) {
      var url = $autocompleteCity.data('caLiteCheckoutAutocompleteUrl'),
          method = $autocompleteCity.data('caLiteCheckoutAutocompleteRequestMethod'),
          cityParam = $autocompleteCity.data('caLiteCheckoutAutocompleteCityParam'),
          countryParam = $autocompleteCity.data('caLiteCheckoutAutocompleteCountryParam'),
          itemsPerPageParam = $autocompleteCity.data('caLiteCheckoutAutocompleteItemsPerPageParam'),
          itemsPerPage = $autocompleteCity.data('caLiteCheckoutAutocompleteItemsPerPage'),
          hidden = $autocompleteCity.data('caLiteCheckoutAutocompleteHidden') !== false,
          caching = $autocompleteCity.data('caLiteCheckoutAutocompleteCaching') !== false,
          ajaxCallback = callback || $.noop;
      var requestData = {};
      requestData[cityParam] = query;
      requestData[countryParam] = countryCode;
      requestData[itemsPerPageParam] = itemsPerPage;
      $.ceAjax('request', url, {
        method: method,
        hidden: hidden,
        caching: caching,
        data: requestData,
        callback: function callback(data) {
          ajaxCallback(data);
        }
      });
    },
    setLocation: function setLocation(city, stateCode, state, zipcode) {
      // update plain fields
      $city.val(city);

      if ($zipCode.length && typeof zipcode !== 'undefined' && typeof zipcode === 'string') {
        $zipCode.val(zipcode);
      }

      methods.getElement('state').val(stateCode ? stateCode : state); // update autocomplete field

      $autocompleteCity.val(city + (state ? ' (' + state + ')' : ''));
      methods.updateShipping({
        s_zipcode: zipcode
      });
    },
    setLocationByFields: function setLocationByFields(fields) {
      if (!fields) {
        return;
      }

      methods.updateShipping(fields);
    },
    initAutocompleteCity: function initAutocompleteCity() {
      $autocompleteCity.on('focus', function (e) {
        if ($autocompleteCity.val() !== '') {
          return;
        }

        methods.lockShippingMethodSelector();
      }).on('keydown', function (e) {
        if (e.keyCode === KEY_CODE_ENTER) {
          e.preventDefault();
          var cityState = $autocompleteCity.val(),
              lastSelectedCityState = $autocompleteCity.data('caLiteCheckoutLastValue'),
              autocompleteList = $autocompleteCity.data('caLiteCheckoutAutocompleteList');

          if (!$(this).data('caLiteCheckoutAutocompleteIsOpen')) {
            if (autocompleteList.length && $.trim(cityState) !== '') {
              // Show autocomplete
              setTimeout(function () {
                $autocompleteCity.autocomplete('search');
              }, 0);
            } else if ($.trim(cityState) === '' || cityState === lastSelectedCityState) {
              if ($country.val() === $country.data('caLiteCheckoutLastValue')) {
                $(this).val($(this).data('caLiteCheckoutLastValue'));
                methods.unlockShippingMethodSelector();
              }

              $(this).blur();
            } else if (cityState !== lastSelectedCityState) {
              methods.setLocation(cityState, EMPTY_STATE_CODE);
              $(this).blur();
            }
          }
        }
      }).on('input', function (e) {
        methods.lockShippingMethodSelector();
      });
      $autocompleteCity.autocomplete({
        appendTo: "#litecheckout_autocomplete_dropdown",
        autoFocus: true,
        source: function source(request, response) {
          var countryCode = $country.val();
          methods.autocompleteCity(request.term, countryCode, function (data) {
            for (var i = 0; i < data.autocomplete.length; i++) {
              data.autocomplete[i].label = data.autocomplete[i].value + (data.autocomplete[i].state ? ' (' + data.autocomplete[i].state + ')' : '');
            }

            $autocompleteCity.data('caLiteCheckoutAutocompleteList', data.autocomplete);
            response(data.autocomplete);
          });
        },
        open: function open(event, ui) {
          $(this).data('caLiteCheckoutAutocompleteIsOpen', true);
        },
        close: function close(event, ui) {
          $(this).data('caLiteCheckoutAutocompleteIsOpen', false);
        },
        select: function select(event, ui) {
          event.preventDefault();
          $(this).blur();
          methods.setLocation(ui.item.value, ui.item.state_code, ui.item.state, ui.item.zipcode);
        }
      });
      $country.on('change', function (e, extra) {
        if (extra && !extra.is_triggered_by_user) {
          return;
        }

        methods.getElement('state').val('');
        $city.val('');
        $autocompleteCity.val('');
        methods.lockShippingMethodSelector();
        $autocompleteCity.focus();
      }).on('keypress', function (e) {
        e.preventDefault();
      });
      $shippingMethods.on('click', function (e) {
        if (!$(this).hasClass($(this).data('caLiteCheckoutOverlayClass'))) {
          return;
        }

        var cityState = $autocompleteCity.val(),
            lastSelectedCityState = $autocompleteCity.data('caLiteCheckoutLastValue'),
            autocompleteList = $autocompleteCity.data('caLiteCheckoutAutocompleteList'),
            zipCode = $zipCode.val(),
            lastSelectedZipCode = $zipCode.data('caLiteCheckoutLastValue');

        if (cityState) {
          methods.unlockShippingMethodSelector();

          if (cityState !== lastSelectedCityState) {
            if (autocompleteList.length) {
              methods.setLocation(autocompleteList[0].value, autocompleteList[0].state_code, autocompleteList[0].state, autocompleteList[0].zipcode);
            } else {
              methods.setLocation(cityState, EMPTY_STATE_CODE);
            }
          } else if (zipCode !== lastSelectedZipCode) {
            methods.setLocationByFields({
              s_zipcode: zipCode
            });
          }
        }
      });
    },
    initPlainCityInput: function initPlainCityInput() {
      var $states = methods.getElement('state', true);
      $city.on('focus', function (e) {
        if ($(this).val() !== '') {
          return;
        }

        methods.lockShippingMethodSelector();
      }).on('keydown', function (e) {
        if (e.keyCode === KEY_CODE_ENTER) {
          e.preventDefault();
          $(this).blur();
          methods.setLocationByPlainCityInput();
        }
      }).on('input', function (e) {
        methods.lockShippingMethodSelector();
      });
      $states.on('focus', function (e) {
        if ($(this).val() !== '') {
          return;
        }

        methods.lockShippingMethodSelector();
      }).on('keydown', function (e) {
        $(e.target).data('caEventType', e.type);
        $(e.target).data('caEventKeyCode', e.keyCode);

        if (e.keyCode === KEY_CODE_ENTER) {
          e.preventDefault();
          $city.focus();
        }
      }).on('input change', function (e) {
        $states.val(methods.getElement('state').val());
        methods.lockShippingMethodSelector();
      }).on('change', function (e) {
        if ($(e.target).data('caEventType') !== 'keydown' || $(e.target).data('caEventKeyCode') === KEY_CODE_DOWN_ARROW || $(e.target).data('caEventKeyCode') === KEY_CODE_UP_ARROW) {
          $city.focus();
        }
      }).on('keypress', function (e) {
        var target = $(e.target);

        if (target.is('select')) {
          e.preventDefault();
        }
      });
      $country.on('change', function (e, extra) {
        if (extra && !extra.is_triggered_by_user) {
          $states.val(methods.getElement('state').val());
          return;
        }

        methods.lockShippingMethodSelector();
        $city.val('');
        $states.filter(':not(:disabled)').focus();
      }).on('keypress', function (e) {
        e.preventDefault();
      });
      $shippingMethods.on('click', function (e) {
        methods.setLocationByPlainCityInput();
      });
    },
    getZipCode: function getZipCode() {
      var $zipCodes = $('[data-ca-lite-checkout-field="user_data.s_zipcode"]', $checkout);
      var $zipCodePrimary = $();
      var $zipCodeSecondary = $();

      if ($zipCodes.length > 1) {
        $zipCodePrimary = $zipCodes.filter(':not([data-ca-lite-checkout-type="secondary"])').first();
        $zipCodeSecondary = $zipCodes.filter('[data-ca-lite-checkout-type="secondary"]').first();
        $zipCodePrimary.data('caLiteCheckoutLastValue', $zipCodePrimary.val());
        $zipCodeSecondary.remove();
      } else if ($zipCodes.length === 1) {
        $zipCodePrimary = $zipCodes;
      }

      return $zipCodePrimary;
    },
    initZipCode: function initZipCode() {
      if (!$zipCode.length) {
        return;
      }

      $zipCode.on('input', function (e) {
        methods.lockShippingMethodSelector();
      });
    },
    setLocationByPlainCityInput: function setLocationByPlainCityInput() {
      if (!$shippingMethods.hasClass($shippingMethods.data('caLiteCheckoutOverlayClass'))) {
        return;
      }

      var $state = methods.getElement('state'),
          city = $city.val(),
          state = $state.val(),
          country = $country.val(),
          lastSelectedCity = $city.data('caLiteCheckoutLastValue'),
          lastSelectedState = $state.data('caLiteCheckoutLastValue'),
          lastSelectedCountry = $country.data('caLiteCheckoutLastValue');
      var isCityChanged = city !== lastSelectedCity,
          isStateChanged = state !== lastSelectedState,
          isCountryChanged = country !== lastSelectedCountry;
      var stateCode = '';

      if ($state.data('caLiteCheckoutIsStateCodeContainer')) {
        stateCode = state;
        state = $state.find('option:selected').text();
      }

      methods.unlockShippingMethodSelector();

      if (isCityChanged || isStateChanged || isCountryChanged) {
        methods.setLocation(city, stateCode, state);
      }
    },
    toggleAddress: function toggleAddress(isEnabled) {
      if (typeof isEnabled === 'undefined') {
        var $selectedShippingMethods = $('[data-ca-lite-checkout-element="shipping-method"]:checked');
        isEnabled = false;
        $selectedShippingMethods.each(function (i, elm) {
          isEnabled = isEnabled || $(elm).data('caLiteCheckoutIsAddressRequired');
        }); // when no one shipping method available

        if (!$selectedShippingMethods.length) {
          isEnabled = true;
        }
      }

      var $addressRow = methods.getElement('address-group');
      $addressRow.parent('.litecheckout__container').toggleClass('hidden', !isEnabled);
      $addressRow.toggleClass('hidden', !isEnabled);
      $addressRow.find('.cm-required,.cm-required-removed').toggleClass('cm-required-removed', !isEnabled).toggleClass('cm-required', isEnabled);
    },
    openEditProfilePopup: function openEditProfilePopup(e) {
      e.preventDefault();
      var $target = $(e.target),
          profileId = $target.data('caProfileId');
      $('.js-edit-profile-' + profileId).click();
    },
    switchProfile: function switchProfile(e) {
      var profileId = parseInt($(e.target).val());

      if (!profileId) {
        return;
      }

      $.ceAjax('request', fn_url('checkout.customer_info'), {
        method: 'POST',
        hidden: false,
        caching: false,
        result_ids: 'litecheckout_final_section,litecheckout_step_payment,shipping_rates_list,litecheckout_use_billing_address_form,litecheckout_billing_address_wrapper,checkout*',
        data: {
          profile_id: profileId,
          full_render: 'Y'
        },
        callback: function callback() {
          methods.bindAbandonedCartFieldsSaving();
          methods.updateCustomerInfo();
        }
      });
    },

    /**
     *
     * @param {function} callback
     * @param {boolean} recalculateCart
     * @param {boolean} isHidden
     * @param {boolean} clearNotification
     * @param {boolean} redraw
     * @returns {boolean}
     */
    updateCustomerInfo: function updateCustomerInfo(_callback, recalculateCart, isHidden, clearNotification, redraw) {
      _callback = _callback || $.noop;

      if (typeof recalculateCart === 'undefined') {
        recalculateCart = true;
      }

      isHidden = isHidden || false;
      clearNotification = clearNotification || false;
      redraw = redraw || false;
      var data = methods.serializeCheckout();
      data.dispatch = 'checkout.customer_info';
      data.result_ids = '';
      data.full_render = null;
      data.recalculate_cart = recalculateCart ? 'Y' : 'N';
      $.ceAjax('request', fn_url(''), {
        method: 'post',
        caching: false,
        hidden: isHidden,
        data: data,
        full_render: redraw,
        result_ids: redraw ? 'litecheckout_final_section,litecheckout_step_payment,shipping_rates_list,checkout*' : '',
        callback: function callback(response) {
          for (var i in response.notifications) {
            if (response.notifications[i].type === 'E') {
              return;
            }
          }

          if (response.has_errors) {
            return;
          }

          methods.toggleAddress();

          _callback(data, response);
        },
        pre_processing: function pre_processing(response) {
          if (response.notifications && clearNotification) {
            delete response.notifications;
          }
        }
      });
    },
    getElement: function getElement(role, getAll) {
      var selector = '[data-ca-lite-checkout-element="' + role + '"]';

      if (getAll !== true) {
        selector += ':not(:disabled)';
      }

      return $(selector, $checkout);
    }
  };
  $.extend({
    ceLiteCheckout: function ceLiteCheckout(method) {
      if (methods[method]) {
        return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
      } else {
        $.error('ty.ceLiteCheckout: method ' + method + ' does not exist');
      }
    }
  });
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $liteCheckoutForm = $('[data-ca-lite-checkout-element="form"]', context);

    if ($liteCheckoutForm.length) {
      $.ceLiteCheckout('init', $liteCheckoutForm);
    }
  });
  $.ceEvent('on', 'ce:geomap:location_set_after', function (location, $container, response, auto_detect) {
    $.redirect(fn_url('checkout.checkout?is_location_changed=true'));
  });
  $(_.doc).on('click', '.cm-open-pickups', function (e) {
    $($(this).data('caTarget')).toggleClass('hidden-phone', false).ceDialog('open', {
      title: $(this).data('caTitle')
    });
    var $dialogBody = $.ceDialog('get_last'),
        $dialogBodyContainer = $dialogBody.parents('.ui-dialog'),
        $objectContainer = $('.object-container', $dialogBody);

    _resize();

    $.ceEvent('on', 'ce.window.resize', function () {
      _resize();
    });
    $.ceEvent('on', 'ce.shipping.select-store', function () {
      try {
        $dialogBody.ceDialog('close'); // force closing dialog
      } catch (e) {
        /* dummy */
      }
    });

    function _resize() {
      $dialogBodyContainer.css({
        width: 'auto',
        left: 0,
        right: 0,
        top: 0,
        bottom: 0,
        position: 'fixed'
      });
      $objectContainer.css({
        padding: 0
      });
    }
  });
})(Tygh, Tygh.$);