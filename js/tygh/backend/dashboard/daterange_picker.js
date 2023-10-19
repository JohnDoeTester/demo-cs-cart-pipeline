(function (_, $) {
  $.ceEvent('on', 'ce.dashboard.daterange_picker', function ($el, selected_from, selected_to, start, end) {
    $('.cm-date-range__selected-date-text', $('#' + $el.data('caTargetId'))).html(start.format($el.data('caDisplayedFormat')) + ' — ' + end.format($el.data('caDisplayedFormat')));
  });
})(Tygh, Tygh.$);