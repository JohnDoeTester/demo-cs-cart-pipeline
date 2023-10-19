(function (_, $) {
  /**
   * Search class.
   * @param {any} o options, { searchInputSelector: 'string', blockSelector: 'string', blockCity: 'string', blockNotFound: 'string' }
   */
  function Search(o) {
    this.searchInputSelector = o.searchInputSelector;
    this.blockSelector = o.blockSelector;
    this.blockCity = o.blockCity;
    this.blockNotFound = o.blockNotFound;
    return this;
  }
  /**
   * Init method.
   * @param {jQuery} $self DOM Node, that contains search input and blocks
   */


  Search.prototype.init = function searchinit($self) {
    this.$self = $self;
    this.$searchInput = $(this.searchInputSelector, $self);
    this.$searchInput.on('input', this.search(this));
    this.$searchInput.on('keypress', function (e) {
      return e.keyCode != 13;
    });
    return this;
  };

  Search.prototype.search = function searchsearch(scope) {
    return function (jQevent) {
      var $found = scope.$self.find(scope.blockSelector + ':contains_case_insensitive(' + $(this).val() + ')');
      var $notFound = scope.$self.find(scope.blockSelector + ':not_contains_case_insensitive(' + $(this).val() + ')');
      var $foundCity = scope.$self.find(scope.blockCity);
      var $blockNotFound = scope.$self.find(scope.blockNotFound);
      $found.toggleClass('hidden', false);
      $notFound.toggleClass('hidden', true);
      $foundCity.removeClass('ty-one-city__hidden');
      $foundCity.each(function () {
        if ($(this).children(scope.blockSelector + ':visible').length === 0) {
          $(this).addClass('ty-one-city__hidden');
        }
      });
      $blockNotFound.toggleClass('ty-store-locator__not-found__hidden', $found.length !== 0);
    };
  };

  $.ceEvent('on', 'ce.commoninit', initSearch);

  function initSearch(context) {
    var $searchInput = $('.js-store-locator-search-input', context);

    if (!$searchInput.length) {
      return;
    }

    var _Search = new Search({
      searchInputSelector: '.js-store-locator-search-input',
      blockSelector: '.js-store-locator-search-block',
      blockCity: '.js-one-city',
      blockNotFound: '.js-store-locator__not-found'
    }).init($(context));
  }
})(Tygh, Tygh.$);