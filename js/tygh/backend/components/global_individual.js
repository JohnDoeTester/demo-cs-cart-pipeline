(function (_, $) {
  $(_.doc).on('click', '[data-ca-global-individual="button"]', function (e) {
    // Component
    var $buttonSwitcher = $(this);
    var $component = $buttonSwitcher.closest('[data-ca-global-individual="component"]');
    var isGlobal = $component.data('caGlobalIndividualIsGlobal');
    var defaultText = $component.data('caGlobalIndividualDefaultText');
    var htmlId = $component.data('caGlobalIndividualHtmlId');
    var individualHtmlName = $component.data('caGlobalIndividualIndividualHtmlName');
    var globalHtmlName = $component.data('caGlobalIndividualGlobalHtmlName'); // New data

    var isNewGlobal = !isGlobal; // Component elements

    var $componentContainer = $component.closest("#container_" + htmlId);
    var $emptyGlobalField = $component.find('[data-ca-global-individual="hiddenInput"]');
    var $fieldWrapper = $component.find('[data-ca-global-individual="fieldWrapper"]');
    var $field = $component.find('#' + htmlId);
    var afterValueText = isNewGlobal ? '' : " (".concat(defaultText, ")");
    var $tooltipTexts = $("\n            [data-ca-global-individual-html-id=\"tooltip_individual_".concat(htmlId, "\"],\n            [data-ca-global-individual-html-id=\"tooltip_global_").concat(htmlId, "\"]\n        "));

    if (isNewGlobal) {
      $fieldWrapper.find("[name=\"".concat(individualHtmlName, "\"]")).prop('name', globalHtmlName);
    } else {
      $fieldWrapper.find("[name=\"".concat(globalHtmlName, "\"]")).prop('name', individualHtmlName);
    } // Set new component


    $component.data('caGlobalIndividualIsGlobal', isNewGlobal); // Change global and individual switcher

    $buttonSwitcher.toggleClass('global-individual__btn--individual');
    $tooltipTexts.toggleClass('hidden'); // Change select

    var $selectDefault = $component.find('[data-ca-type="defaultOption"]');

    if ($selectDefault.length) {
      $field.find('option:not([data-ca-type="defaultOption"])').each(function () {
        $(this).text($(this).data('caValue') + afterValueText);
      });
      $selectDefault.toggleClass('hidden', isNewGlobal);
    } // Change input


    if ($field.is('input[type=text], input[type=password], textarea')) {
      $field.prop('placeholder', isNewGlobal ? '' : defaultText);
    } // Set focus on field


    $field.focus();
  }); // Update for all button

  $(_.doc).on('click', '.cm-update-for-all-icon', function (e) {
    var $component = $('#' + $(this).data('caHideId')).closest('[data-ca-global-individual="component"]');

    if (!$component.length) {
      return;
    }

    $component.find('[data-ca-global-individual="button"]').toggleClass('global-individual__btn--disabled');
  });
})(Tygh, Tygh.$);