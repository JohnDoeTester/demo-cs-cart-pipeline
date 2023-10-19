(function (_, $) {
  $.ceEvent('on', 'ce.commoninit', function (context) {
    fn_init_show_mode_block(context);
    $('.cm-show-more__btn-more, .cm-show-more__btn-less', context).on('click', function (event) {
      event.preventDefault();
      var parent = $(this).parent();
      parent.parent().find('.cm-show-more__wrapper').toggleClass('cm-show-more__expanded');
      parent.find('.cm-show-more__btn-more').toggle();
      parent.find('.cm-show-more__btn-less').toggle();
    });
  });
  $.ceEvent('on', 'ce.tab.show', function (tab_id) {
    fn_init_show_mode_block($('#content_' + tab_id));
  });
  $.ceEvent('on', 'ce.dialogshow', function (dialog) {
    fn_init_show_mode_block(dialog);
  });

  function fn_init_show_mode_block(context) {
    var $elems = $('.cm-show-more__block', context);
    $elems.each(function (el) {
      var $self = $(this),
          $parent = $self.parent();

      if ($self.height() > $parent.height()) {
        $parent.parent().find('.cm-show-more__btn').addClass('cm-show-more__btn-more');
      }
    });
  }
})(Tygh, Tygh.$);