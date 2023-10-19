(function (_, $) {
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $updateForAllIcon = $('.cm-update-for-all-icon[href="#"]', context);

    if (!$updateForAllIcon.length) {
      return;
    }

    $(_.doc).on('click', '.cm-update-for-all-icon[href="#"]', function (e) {
      e.preventDefault();
    });
  });
})(Tygh, Tygh.$);