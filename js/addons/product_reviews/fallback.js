/* Adds support for the "gap" property to the Safari browser. */

/* TODO: Delete fallback.css and fallback.js files after Safari browser supports "gap" property */
(function (_, $) {
  // Modernizr: flexgap.js
  function isSupportedFlexGap() {
    // create flex container with row-gap set
    var flex = document.createElement('div');
    flex.style.display = 'flex';
    flex.style.flexDirection = 'column';
    flex.style.rowGap = '1px'; // create two elements inside it

    flex.appendChild(document.createElement('div'));
    flex.appendChild(document.createElement('div')); // append to the DOM (needed to obtain scrollHeight)

    document.body.appendChild(flex);
    var isSupported = flex.scrollHeight === 1; // flex container should be 1px high from the row-gap

    flex.parentNode.removeChild(flex);
    return isSupported;
  }

  function removeFallbackStyles() {
    if (isSupportedFlexGap()) {
      $(':root').addClass('supported-gap');
    }
  }

  $(_.doc).ready(function () {
    removeFallbackStyles();
  });
})(Tygh, Tygh.$);