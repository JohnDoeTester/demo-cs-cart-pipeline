(function (_, $) {
  $(_.doc).on('change', '[data-ca-stripe-description-element-id]', function () {
    var $selector = $(this);

    if (!$selector.is(':checked')) {
      return;
    }

    var descriptionContainerId = $selector.data('caStripeDescriptionElementId'),
        $descriptionContainer = $('#' + descriptionContainerId),
        showPaymentButtonLabelId = $selector.data('caStripeShowPaymentButtonLabelId'),
        $showPaymentButtonLabel = $('#' + showPaymentButtonLabelId);
    var $form = $selector.closest('form');
    $('.stripe-description', $form).addClass('hidden');
    $descriptionContainer.removeClass('hidden');
    $showPaymentButtonLabel.closest('.control-group').toggleClass('hidden', ['card', 'stripe_checkout'].includes($selector.val()));
    var buttonName = $selector.data('caStripePaymentButtonName'),
        showPaymentButtonTemplate = $showPaymentButtonLabel.data('caStripeShowPaymentButtonTemplate');
    $showPaymentButtonLabel.text(showPaymentButtonTemplate.replace('[button_name]', buttonName));
  });
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $descriptionElementId = $('[data-ca-stripe-description-element-id]', context);

    if (!$descriptionElementId.length) {
      return;
    }

    $('[data-ca-stripe-description-element-id]', context).trigger('change');
  });
})(Tygh, Tygh.$);