(function (_, $) {
  $(_.doc).ready(function () {
    $('#main_destination').on('change', function (e) {
      var destination_id = $(this).val();
      $('#destinations_' + destination_id).prop('checked', true);

      if (!destination_id) {
        $('.store-locator__pickup-destinations-list').addClass('hidden');
      } else {
        $('.store-locator__pickup-destinations-list').removeClass('hidden');
      }
    });
    $('.store-locator__destination').on('change', function (e) {
      var $option = $(this),
          is_checked = $option.prop('checked');

      if (is_checked) {
        return;
      }

      var destination_id = $option.val(),
          main_destination_id = $('#main_destination').val();

      if (destination_id === main_destination_id) {
        $option.prop('checked', true);
      }
    });
  });
})(Tygh, Tygh.$);