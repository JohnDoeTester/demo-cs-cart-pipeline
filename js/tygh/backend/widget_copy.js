(function (_, $) {
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $widgetCopyBtn = $('.cm-widget-copy__btn', context);

    if (!$widgetCopyBtn.length) {
      return;
    }

    $widgetCopyBtn.on('click', function () {
      var codeText = $(this).parent().find('.cm-widget-copy__code-text')[0];
      $(this).prop('title', $(this).data('title'));
      $(this).ceTooltip();
      $(this).data('tooltip').show();
      $(this).on("mouseout", function () {
        $(this).data('tooltip').getTip().remove();
        $(this).removeData('tooltip').off('mouseout mouseover');
      });
      window.getSelection().removeAllRanges();
      var range = document.createRange();
      range.selectNode(codeText);
      window.getSelection().addRange(range);
      document.execCommand("copy");
    });
  });
})(Tygh, Tygh.$);