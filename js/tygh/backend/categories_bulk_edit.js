(function (_, $) {
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $longtap = $('[data-ca-longtap]', context);

    if (!$longtap.length) {
      return;
    }

    $.ceEvent('on', 'ce.tap.toggle', function (selected) {
      // If the list has more categories than the category threshold.
      if (selected && event && $(event.target).is('td')) {
        $('.categories-company .cm-combination:visible[id^="on_"]').click();
      }
    });
  });
})(Tygh, Tygh.$);