(function (_, $) {
  $.ceEvent('on', 'ce.commoninit', function (context) {
    // Prevent sorting pickup items if their height more then container height
    setTimeout(function () {
      var $pickupOfficesList = $('.pickup__offices.pickup__offices--list:not(".pickup__offices--list-no-height")', context);

      if (!$pickupOfficesList.length) {
        return;
      }

      handlePickupsContainerSizes($pickupOfficesList)();
    }, 0);
  });

  function handlePickupsContainerSizes($container) {
    var container = $container.get(0),
        _offset = 50,
        _class = 'pickup__offices--list--no-sorting';
    return function () {
      if (!container) return;

      if (container.clientHeight + _offset > container.scrollHeight) {
        $container.toggleClass(_class, true);
        return;
      }

      $container.toggleClass(_class, false);
    };
  }
})(Tygh, Tygh.$);