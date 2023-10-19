function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

(function (_, $) {
  var requestDelay = 300;
  var searchAddonsDebounce = $.debounce(function () {
    var $marketplaceSearch = $(this);
    var $marketplaceSearchForm = $marketplaceSearch.closest('[data-ca-addons-marketplace="marketplaceSearchForm"]');
    var resultIds = $('[name="result_ids"]', $marketplaceSearchForm).val();
    var $submitBtn = $('[type="submit"]', $marketplaceSearchForm);
    $.ceAjax('request', fn_url(''), {
      result_ids: resultIds,
      hidden: true,
      data: _defineProperty({
        q: $marketplaceSearch.val(),
        lang_code: _.cart_language
      }, $submitBtn.attr('name'), $submitBtn.val())
    });
  }, requestDelay);
  $.ceEvent('on', 'ce.commoninit', function ($context) {
    var $marketplaceSearch = $('[data-ca-addons-marketplace="marketplaceSearch"]', $context);

    if (!$marketplaceSearch.length) {
      return;
    }

    $marketplaceSearch.on('input', searchAddonsDebounce);
  });
})(Tygh, Tygh.$);