(function (_, $) {
  _.doc.addEventListener('DOMContentLoaded', function () {
    $('form:not(.autocomplete-on)').each(function (i, elem) {
      var $hiddenInput = '<input type="password" class="hidden" autocomplete="new-password"/> <!-- turn off Chrome autocomplete -->';
      $(elem).prepend($hiddenInput);
    });
  });
})(Tygh, Tygh.$);