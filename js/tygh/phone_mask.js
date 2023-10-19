(function (_, $) {
  var mask_list;
  var is_custom_format;
  $.ceEvent('on', 'ce.commoninit', function (context) {
    is_custom_format = !!_.call_phone_mask;
    var $phone_elems = context.find('.cm-mask-phone'),
        phone_validation_mode = _.phone_validation_mode || 'phone_number_with_country_selection',
        is_international_format = phone_validation_mode === 'international_format',
        is_phone_number_with_country_selection = phone_validation_mode === 'phone_number_with_country_selection',
        is_any_digits = phone_validation_mode === 'any_digits';

    if (!$phone_elems.length || is_international_format && !window.localStorage) {
      return;
    }

    if (is_international_format) {
      $phone_elems.attr('inputmode', 'numeric');
    }

    if (is_phone_number_with_country_selection) {
      $phone_elems.each(function () {
        if (!$('label[for="' + $(this).attr('id') + '"]').length) {
          return;
        }

        phoneNumberWithCountrySelectionInit($(this));
        bindEvents(this);
        $(this).addClass('js-mask-phone-inited');
        validatePhone($(this));
      });
      registerValidatorPhoneMask();
    } else if (is_international_format || is_custom_format) {
      loadPhoneMasks().then(function (phone_masks) {
        _.phone_masks_list = phone_masks; // backward compatibility

        _.call_requests_phone_masks_list = _.phone_masks_list;
        mask_list = $.masksSort(_.phone_masks_list, ['#'], /[0-9]|#/, "mask");
        var mask_opts = {
          inputmask: {
            definitions: {
              '#': {
                validator: "[0-9]",
                cardinality: 1
              }
            },
            showMaskOnHover: false,
            autoUnmask: false,
            onKeyDown: function onKeyDown() {
              $(this).trigger('_input');
            }
          },
          match: /[0-9]/,
          replace: '#',
          list: mask_list,
          listKey: "mask"
        };
        $phone_elems.each(function (index, elm) {
          if (is_custom_format && $(elm).data('enableCustomMask')) {
            $(elm).inputmask({
              mask: _.call_phone_mask,
              showMaskOnHover: false,
              autoUnmask: false,
              onKeyDown: function onKeyDown() {
                $(this).trigger('_input');
              }
            });
          } else {
            if (!isMaskRemoveValue($(elm).val(), mask_opts)) {
              afterMaskRemoveValueProcess(elm, mask_opts);
              return;
            }

            $(elm).inputmasks(mask_opts);
          }

          bindEvents(elm);
          $(elm).addClass('js-mask-phone-inited');

          if ($(elm).val()) {
            $(elm).oneFirst('keypress keydown', function () {
              if (!validatePhone($(elm))) {
                $(elm).trigger('paste');
              }
            });
            $(elm).prop('defaultValue', $(elm).val());
          }
        });
      });
      registerValidatorPhoneMask();
    } else if (is_any_digits) {
      registerValidatorPhoneMask('is_any_digits');
    }
  });
  $(_.doc).on('click', '.cm-phone-number-with-country-selection-li-link, ' + '.cm-phone-number-with-country-selection-li-link .cs-icon, ' + '.cm-phone-number-with-country-selection-li-link .ty-icon', function (e) {
    e.preventDefault();
    var $listItemLink = $(this);
    var $input = $('.cm-mask-phone', $listItemLink.closest('.cm-mask-phone-group'));
    var country = $listItemLink.data('caName') ? $listItemLink.data('caName') : $listItemLink.attr('name');
    setCountry($input, country, $listItemLink.data('caListItemSymbol'));
  }); // Click the link item when clicking on the icon
  // Fix: core_methods.js: change bootstrap dropdown behavior

  $.ceEvent('on', 'dispatch_event_pre', function (e, jelm, processed) {
    if (_.area !== 'A' || e.type !== 'click' || !$(e.currentTarget).hasClass('dropdown-menu') || !$(e.currentTarget).closest('.cm-mask-phone-group') || !$(e.target).is('.cs-icon.flag')) {
      return;
    }

    e.preventDefault();
    $(e.target).closest('.cm-phone-number-with-country-selection-li-link').click();
  });

  function validatePhone($input) {
    if (!$input.length) {
      return false;
    }

    var input = $input[0];

    if ($.is.blank($input.val()) || !$input.hasClass('js-mask-phone-inited')) {
      if (_.phone_validation_mode === 'phone_number_with_country_selection') {
        input.setCustomValidity('');
      }

      return true;
    }

    var mask_is_valid = false;

    if (_.phone_validation_mode === 'phone_number_with_country_selection') {
      var country = _.default_country;

      if ($input.data('caPhoneMaskCountry') && $input.data('caPhoneMaskCountry') === 'UNDEFINED_COUNTRY') {} else if ($input.data('caPhoneMaskCountry')) {
        country = $input.data('caPhoneMaskCountry');
      }

      mask_is_valid = libphonenumber.isValidPhoneNumber($input.val());
      var errorCode = libphonenumber.validatePhoneNumberLength($input.val()); // Do not show an error if phone number input has just started

      if ($input.data('caSkipTooShort') && (errorCode === 'TOO_SHORT' || $input.val() === '+')) {
        mask_is_valid = true;
        input.setCustomValidity('');
        var asYouType = new libphonenumber.AsYouType();
        asYouType.input($input.val());
        setCountry($input, asYouType.getNumber() && asYouType.getNumber().country || 'UNDEFINED_COUNTRY');
      } else if (mask_is_valid) {
        input.setCustomValidity('');
        var phoneNumber = libphonenumber.parsePhoneNumber($input.val());

        if (!phoneNumber) {
          mask_is_valid = false;
        }

        setCountry($input, phoneNumber.country);
      } else {
        input.setCustomValidity(_.tr('error_validator_phone_phone_number_with_country_selection'));
      }
    } else if (is_custom_format && $input.data('enableCustomMask')) {
      mask_is_valid = _toRegExp(_.call_phone_mask).test($input.val()) && $input.inputmask('isComplete');
    } else {
      mask_list.forEach(function (mask) {
        mask_is_valid = mask_is_valid || _toRegExp(mask.mask).test($input.val());
      });
      mask_is_valid = mask_is_valid && $input.inputmask('isComplete');
    }

    return mask_is_valid;

    function _toRegExp(mask) {
      var _convertedMask = mask.str_replace('#', '.').str_replace('+', '\\+').str_replace('(', '\\(').str_replace(')', '\\)').str_replace('9', '[0-9]').str_replace('\\[0-9]', '9');

      return new RegExp(_convertedMask);
    }
  }

  function loadPhoneMasks() {
    var oldHashOfAvailableCountries = window.localStorage.getItem('availableCountriesHash'),
        newHashOfAvailableCountries = _.hash_of_available_countries,
        oldHashPhoneMasks = window.localStorage.getItem('phoneMasksHash'),
        newHashPhonesMasks = _.hash_of_phone_masks,
        rawPhoneMasks = window.localStorage.getItem('phoneMasks'),
        phoneMasks,
        d = $.Deferred();

    if (rawPhoneMasks && oldHashPhoneMasks === newHashPhonesMasks) {
      phoneMasks = JSON.parse(rawPhoneMasks);
    }

    if (!phoneMasks || newHashOfAvailableCountries !== undefined && oldHashOfAvailableCountries !== newHashOfAvailableCountries) {
      $.ceAjax('request', fn_url('phone_masks.get_masks'), {
        method: 'get',
        caching: false,
        data: {},
        callback: function callback(response) {
          if (!response || !response.phone_mask_codes) {
            return;
          }

          $.ceEvent('trigger', 'ce.phone_masks.masks_loaded', [response]);
          phoneMasks = Object.keys(response.phone_mask_codes).map(function (key) {
            return response.phone_mask_codes[key];
          });
          window.localStorage.setItem('phoneMasksHash', newHashPhonesMasks);
          window.localStorage.setItem('phoneMasks', JSON.stringify(phoneMasks));
          d.resolve(phoneMasks);
        },
        repeat_on_error: false,
        hidden: true,
        pre_processing: function pre_processing(response) {
          if (response.force_redirection) {
            delete response.force_redirection;
          }

          return false;
        },
        error_callback: function error_callback() {
          d.reject();
        }
      });
      window.localStorage.setItem('availableCountriesHash', newHashOfAvailableCountries);
    } else {
      d.resolve(phoneMasks);
    }

    return d.promise();
  }

  function bindEvents(elm) {
    if (_.phone_validation_mode === 'phone_number_with_country_selection') {
      $(elm).on('focus blur', function (e) {
        togglePhoneMaskPrefix($(this), e.type);
      });
      $(elm).on('input blur', function (e) {
        var tempData = undefined;
        var isShowValidationErrors = true;

        if (e.type === 'input') {
          tempData = {
            'caSkipTooShort': true
          };
        } else {
          isShowValidationErrors = !($(elm).prop('defaultValue') === '' && $(elm).val() === '');
        }

        checkFieldWithoutScroll($(elm), true, tempData, isShowValidationErrors);
      });
    } else {
      // Hide the mask if the field is empty
      $(elm).on('blur.inputmasks', function () {
        if ($(this).val() === this.inputmask.maskset._buffer.join('')) {
          $(this).val('');
        }

        if (this.value !== this.defaultValue) {
          $(this).trigger('change');
        }
      });
    }
  }

  function registerValidatorPhoneMask(type) {
    $.ceFormValidator('registerValidator', {
      class_name: 'cm-mask-phone-label',
      message: type === 'is_any_digits' ? _.tr('error_validator_phone') : _.tr('error_validator_phone_mask'),
      func: type === 'is_any_digits' ? function (elm_id, elm, lbl) {
        return $.is.blank(elm.val()) || $.is.phone(elm.val());
      } : function (id) {
        return validatePhone($('#' + id));
      }
    });
  }

  function isMaskRemoveValue(prevValue, mask_opts) {
    var $virtualElem = $('<input>', {
      value: prevValue
    });
    $virtualElem.inputmasks(mask_opts);
    return prevValue === '' || prevValue !== '' && $virtualElem.val() !== '';
  }

  function afterMaskRemoveValueProcess(phoneField, mask_opts) {
    var $phoneField = $(phoneField);
    var $phoneLabel = $('label[for="' + $phoneField.attr('id') + '"]'); // Register validator for invalid phone field

    $phoneLabel.addClass('cm-mask-phone-with-phone-label');
    $.ceFormValidator('registerValidator', {
      class_name: 'cm-mask-phone-with-phone-label',
      message: _.tr('error_validator_phone_mask_with_phone').str_replace('[phone]', $phoneField.val()),
      func: function func(elmId, elm) {
        return isMaskRemoveValue($(elm).val(), mask_opts);
      }
    });
    checkFieldWithoutScroll($phoneField); // Mask initialization on invalid phone field focus

    $phoneField.on('focus.maskPhoneWithPhoneLabel', function () {
      $phoneField.off('focus.maskPhoneWithPhoneLabel');
      $phoneLabel.removeClass('cm-mask-phone-with-phone-label');
      $phoneField.inputmasks(mask_opts);
      bindEvents(phoneField);
      $phoneField.addClass('js-mask-phone-inited');
      registerValidatorPhoneMask();
    });
  } // Temporarily disable scrolling and show validator notice for invalid phone field


  function checkFieldWithoutScroll($input, isFieldClickedElm, tempData, isShowValidationErrors) {
    var $form = $input.closest('form');
    var $fieldContainer = $input.closest('.cm-field-container');
    var isUndefinedNoScroll = typeof $input.data('caNoScroll') === 'undefined';
    isShowValidationErrors = typeof isShowValidationErrors === 'undefined' ? true : isShowValidationErrors;
    isUndefinedNoScroll && $input.data('caNoScroll', true);
    isUndefinedNoScroll && $fieldContainer.length && $fieldContainer.data('caNoScroll', true);

    if (isFieldClickedElm) {
      $form.ceFormValidator('setClicked', $input);
    } else {
      !$('[type=submit]', $form).length && !$('input[type=image]', $form).length && $form.ceFormValidator('setClicked', $('.cm-submit', $form).length ? $('.cm-submit:first', $form) : $input);
    }

    tempData && $input.data(tempData);
    $form.ceFormValidator('check', true, null, isShowValidationErrors);
    tempData && $input.removeData(Object.keys(tempData));
    isUndefinedNoScroll && $input.removeData('caNoScroll');
    isUndefinedNoScroll && $fieldContainer.length && $fieldContainer.removeData('caNoScroll');
  }

  function phoneNumberWithCountrySelectionInit($input) {
    if (!$input.length || $input.closest('.cm-mask-phone-group').length) {
      return;
    }

    var inputId = $input.attr('id');
    var $labelField = $('label[for="' + inputId + '"]');

    if (!$labelField.length) {
      return;
    }

    $labelField.parent().addClass('cm-mask-phone-group').attr('data-ca-phone-mask-group-id', inputId);
    $input.data('caCheckFilter', '[data-ca-phone-mask-group-id="' + inputId + '"]');
  }

  function togglePhoneMaskPrefix($input, eventType) {
    if (!$input.length) {
      return;
    }

    var symbol = $input.data('caPhoneMaskSymbol');

    if (eventType === 'focus' && $input.val() === '' && symbol) {
      $input.val(symbol);
    } else if (eventType === 'blur' && ($input.val() === symbol || $input.val() === '+' || /^\+\d$/.test($input.val()) // +1, +2, ..., +9
    )) {
      $input.val('');
    }
  }

  function setCountry($input, country, phoneCode) {
    if (!$input.length || !country) {
      return;
    }

    var isUndefinedCountry = country === 'UNDEFINED_COUNTRY';
    var flagIconCode = isUndefinedCountry ? '01' : country.toLowerCase();
    var flagClass = _.area === 'A' ? 'flag' : 'ty-flag';
    var toggleAttrSelector = _.area === 'A' ? '[data-toggle="dropdown"]' : '[data-ca-toggle="dropdown"]';
    var $icon = $(toggleAttrSelector + ' .' + flagClass, $input.parent());

    if ($icon.length) {
      $.each($icon.prop('classList'), function (index, className) {
        if (!className.startsWith(flagClass + '-')) {
          return;
        }

        $icon.removeClass(className);
      });
      $icon.addClass(flagClass + '-' + flagIconCode);
    }

    $input.data('caPhoneMaskCountry', country.toUpperCase());

    if (phoneCode) {
      $input.data('caPhoneMaskSymbol', phoneCode);
      $input.val(phoneCode).focus();
      var placeholderShownText = ' ';

      if (typeof $input.attr('placeholder') !== 'undefined' && $input.attr('placeholder') !== placeholderShownText) {
        $input.attr('placeholder', phoneCode);
      }
    }

    if (_.area === 'C') {
      var $button = $('.cm-combination:first', $input.parent());

      if ($button.length && $button.hasClass('open')) {
        $.toggleCombination($button);
      }
    }
  }
})(Tygh, Tygh.$);