(function (_, $) {
  $(document).ready(function () {
    var $mobileMenuToggler = $('.mobile-menu-toggler');

    if (!$mobileMenuToggler.length) {
      return;
    }

    var isConvertDropdown = $mobileMenuToggler.data('caMobileMenuIsConvertDropdown'); // Toggle mobile navbar

    $(document).on('click', '.mobile-menu-toggler', toggleMobileMenu);
    $(document).on('click', '.mobile-menu-closer', toggleMobileMenu); // Toggle submenu in the top of main menu

    $(document).on('click', '.menu-heading__title-block', toggleMobileMenuSubmenu); // Toggle mobile overlay from dropdown element

    if (isConvertDropdown) {
      $(document).on('click', '.overlayed-mobile-menu-closer', toggleMobileOverlay);
      $(document).on('click', '#header_subnav .dropdown .dropdown-toggle', createSubmenuFromDropdown);
    } // Toggle mobile search


    initMobileSearch();
  });
  /**
   * Toggle mobile navbar
   * @param {Event} e event
   */

  function toggleMobileMenu(e) {
    $('.navbar-admin-top').toggleClass('open');
    $('body').toggleClass('noscrolling');
  }
  /**
   * Toggle mobile navbar submenu
   * @param {Event} e event
   */


  function toggleMobileMenuSubmenu(e) {
    $('.menu-heading__title-block').toggleClass('openned');
    var targetChild = $('.menu-heading__dropdowned-menu');
    var targetContainer = $('.menu-heading__dropdowned');
    var magicBottomOffset = 5; // need for bottom shadow visibility

    if (targetContainer.height()) {
      targetContainer.height(0);
    } else {
      targetContainer.height(targetChild.height() + magicBottomOffset);
    }
  }
  /**
   * Toggle mobile overlay
   * @param {Event} e event
   */


  function toggleMobileOverlay(e) {
    $('.overlayed-mobile-menu').toggleClass('open');
  }
  /**
   * Creating overlay submenu from dropdown element
   * @param {Event} e event
   */


  function createSubmenuFromDropdown(e) {
    // Stop function, if not mobile resolution
    if (!$.matchScreenSize(['xs', 'xs-large', 'sm'])) {
      return;
    }

    var self = e.target,
        parent = self.parentElement,
        children = parent.childNodes,
        title = self.dataset && self.dataset.caMobileMenuTitle || self.text,
        dropdown = undefined; // Stop function, if dropdown processing disabled manually

    if ($(self).data('disableDropdownProcessing') || $(parent).data('disableDropdownProcessing')) {
      return;
    } // Find target dropdown (will be converted into overlay menu)


    for (var childIndex = 0; childIndex < children.length; childIndex++) {
      var child = children[childIndex];

      if (child.classList) {
        if (child.classList.contains('dropdown-menu')) {
          dropdown = child;
        }
      }
    } // Stop function, if target dropdown not found


    if ($.isUndefined(dropdown)) {
      return;
    } // Converting


    e.preventDefault();
    convertDropdownToOverlayMenu(dropdown, title);
    $.ceEvent('trigger', 'ce.mobile_menu.dropdownMenu_created');
  }
  /**
   * Convert passed dropdown and title into overlay menu.
   * @param {HTMLElement} dropdown target dropdown
   * @param {string} title overlay title
   */


  function convertDropdownToOverlayMenu(dropdown, title) {
    var $secondMenu = $('.overlayed-mobile-menu-container'),
        $secondMenuTitle = $('.overlayed-mobile-menu__content'); // Clean menu

    $secondMenu.empty(); // Apply title for menu

    $secondMenuTitle.find('.overlayed-mobile-menu-title').text(title); // Apply dropdown content

    $(dropdown).clone().appendTo($secondMenu); // Open menu

    toggleMobileOverlay();
  }
  /**
   * Initialization mobile search
   */


  function initMobileSearch() {
    var $searchGroup = $('.cm-search-mobile-group');
    var searchBlockSelector = $searchGroup.data('caSearchMobileBlock');
    var searchInputSelector = $searchGroup.data('caSearchMobileInput');
    var searchBtnSelector = $searchGroup.data('caSearchMobileBtn');
    var searchBackSelector = $searchGroup.data('caSearchMobileBack');
    $(document).on('click', searchBtnSelector, function (e) {
      e.preventDefault();
      $(searchBlockSelector).removeClass('hidden');
      $(searchInputSelector).prop("disabled", false);
      $(searchInputSelector).focus();
    });
    $(document).on('click', searchBackSelector, function (e) {
      e.preventDefault();
      $(searchInputSelector).prop("disabled", true);
      $(searchBlockSelector).addClass('hidden');
    });
  }
})(Tygh, Tygh.$);