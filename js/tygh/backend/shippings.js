(function (_, $) {
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $btnWeight = $('.cm-btn-weight', context);

    if (!$btnWeight.length) {
      return;
    }

    $('.cm-btn-weight').on('click', function () {
      var $selector = $(this).data('caExternalClickId');
      $('#' + $selector).val(this.value);
    });
  });
})(Tygh, Tygh.$);