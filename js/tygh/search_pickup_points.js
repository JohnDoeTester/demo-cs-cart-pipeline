(function (_, $) {
  // Define pseudo-selectors in jquery
  $.expr[':'].contains_case_insensitive = $.expr.createPseudo(function (arg) {
    return function (elem) {
      return searchPickupPoints($(elem), arg);
    };
  });
  $.expr[':'].not_contains_case_insensitive = $.expr.createPseudo(function (arg) {
    return function (elem) {
      return !searchPickupPoints($(elem), arg);
    };
  });

  searchPickupPoints = function searchPickupPoints(element, text) {
    var isFound = true;
    var elementText = element.text().replace(/[&\/\\#,+()$~%.'":*?<>{}\s]/g, '');
    var inputText = text.replace(/[&\/\\#,+()$~%.'":*?<>{}]/g, '').split(' ');

    for (var i = 0; i < inputText.length; i++) {
      if (inputText[i].length > 0) {
        isFound = elementText.toUpperCase().indexOf(inputText[i].toUpperCase()) >= 0;

        if (!isFound) {
          break;
        }
      }
    }

    return isFound;
  };
})(Tygh, Tygh.$);