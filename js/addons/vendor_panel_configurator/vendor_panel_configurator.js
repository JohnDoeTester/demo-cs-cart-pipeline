(function (_, $) {
  $.ceEvent('on', 'ce.block_manager.change', function (action, data, controllerData, response) {
    if (!response || !('root_hidden' in response) || !('block_id' in response) || action !== 'switch' && action !== 'delete') {
      return;
    }

    $("[data-ca-menu=\"navMenuItem\"][href=\"#".concat(response.block_id, "\"]")).toggleClass('nav__menu-item--root-hidden', response.root_hidden);
  });
})(Tygh, Tygh.$);