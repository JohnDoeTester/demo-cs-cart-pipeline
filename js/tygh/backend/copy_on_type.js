(function (_, $) {
  $(_.doc).on('input change', '[data-ca-copy-on-type-active]', function (e) {
    if ($(this).data('caCopyOnTypeActive')) {
      $($(this).data('caCopyOnTypeTextSelector')).text($(this).val());
      $($(this).data('caCopyOnTypeTargetSelector')).val($(this).val());
    }
  });
  $(_.doc).on('click', '[data-ca-copy-on-type-source-selector]', function (e) {
    $($(this).data('caCopyOnTypeTextWrapperSelector')).addClass('hidden');
    $($(this).data('caCopyOnTypeTargetWrapperSelector')).removeClass('hidden');
    $($(this).data('caCopyOnTypeTargetSelector')).focus();
    $($(this).data('caCopyOnTypeSourceSelector')).data('caCopyOnTypeActive', false);
  });
})(Tygh, Tygh.$);