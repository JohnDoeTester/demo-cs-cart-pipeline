(function (_, $) {
  var prevNameText = '';

  function setNameFieldFromSelect($elem) {
    var $name = $('[data-ca-payment="name"]', $elem.closest('[data-ca-payment="tabDetails"]'));
    var nameText = $name.val();
    var selectFieldText = $(':selected', $elem).text();

    if (nameText === '' || prevNameText === nameText) {
      $name.val(selectFieldText);
    }

    prevNameText = selectFieldText;
  }

  function updateIcon($elem) {
    var $tabDetails = $elem.closest('[data-ca-payment="tabDetails"]');
    var $iconNoImage = $('[data-ca-payment="iconWrapper"] .no-image', $tabDetails);
    var processorAddon = $(':selected', $elem).data('caPaymentAddon');

    if ($('[name="payment_id"]', $tabDetails.closest('[data-ca-payment="paymentsForm"]')).val() === '0') {
      $iconNoImage.removeClass('no-image--' + $iconNoImage.data('prevProcessorAddon')).addClass('no-image--' + processorAddon).data('prevProcessorAddon', processorAddon);
    }
  }

  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $templateField = $('[data-ca-payment="template"]', context);
    var $processorId = $('[data-ca-payment="processor_id"]', context);

    if (!$templateField.length || !$processorId.length) {
      return;
    }

    $processorId.on('change', function () {
      setNameFieldFromSelect($(this));
      updateIcon($(this));
    });
    $templateField.on('change', function () {
      setNameFieldFromSelect($(this));
    });
  });
})(Tygh, Tygh.$);