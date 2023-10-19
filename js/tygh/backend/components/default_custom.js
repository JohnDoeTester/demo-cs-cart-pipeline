(function (_, $) {
  $(_.doc).on('change', '[data-ca-default-custom="select"]', function (e) {
    var $select = $(this);
    var $block = $select.closest('[data-ca-default-custom="main"]');
    var $textbox = $block.find('[data-ca-default-custom="textbox"]');
    var $selected = $select.find(':selected');
    var prevSelectedValue = $block.data('caDefaultCustomSelectedValue');
    var selectedType = $selected.data('caDefaultCustom');
    var selectedUrl = $selected.data('caDefaultCustomUrl') || ''; // Select non custom option

    if (selectedType !== 'custom_edit') {
      $textbox.prop('disabled', true);
      $textbox.addClass('hidden');
    }

    if (selectedType === 'custom_edit') {
      // Select custom option
      $textbox.prop('disabled', false);
      $textbox.removeClass('hidden');
      $textbox.select();
    } else if (selectedType === 'inheritance_edit') {
      // Open edit parent and global page in new tab
      e.preventDefault();
      $select.val(prevSelectedValue).change();
      window.open(fn_url(selectedUrl));
    }
  });
})(Tygh, Tygh.$);