(function (_, $) {
  $(document).on('change', '.cm-vendor-plans-selector', function (event) {
    var plan_id = event.target.value,
        container = $('.cm-vendor-plans-info');
    container.find('[data-ca-plan-id]').addClass('hidden');
    container.find('[data-ca-plan-id=' + plan_id + ']').removeClass('hidden');
  });
})(Tygh, Tygh.$);