(function (_, $) {
  $.ceEvent('on', 'ce.calculate_total_shipping.before_send_request', function (data, parent) {
    data.vendor_id = parent.find('input[name="vendor_id"]').first().val();
  });
})(Tygh, Tygh.$);