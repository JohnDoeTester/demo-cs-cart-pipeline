function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

(function (_, $) {
  function globalHandlers() {
    // Selector ':containsi' is deprecated
    // Add new selector to search text inside matched elements
    $.extend($.expr[':'], {
      'containsi': function containsi(elem, i, match, array) {
        var haystack = (elem.textContent || elem.innerText || '').toLowerCase();
        var needle = (match[3] || '').toLowerCase().split(' ');

        for (var k = 0; k < needle.length; k++) {
          if (haystack.indexOf(needle[k]) != -1) {
            return true;
          }
        }

        return false;
      }
    }); // /Deprecated
    // Re-init search after ajax request

    $.ceEvent('on', 'ce.commoninit', function (context) {
      var $filterTables = $('.cm-filter-table', context);

      if (!$filterTables.length) {
        return;
      }

      $filterTables.ceFilterTable();
    });
  }

  (function ($) {
    function setHandlers(container) {
      var data = container.data('ceFilterTable'),
          input_elm = data.input_elm,
          clear_elm = data.clear_elm; // Clear input

      clear_elm.on('click', function () {
        input_elm.val('').trigger('input');
        clear_elm.addClass('hidden');
      }); // Perform search and show/hide clear button

      input_elm.on('keyup input', function () {
        _filter(container);
      });
    }

    function showItems(container, items, empty_elm) {
      var data = {
        items: items,
        empty_elm: empty_elm
      };
      $.ceEvent('trigger', 'ce.filter_table_show_items', [container, data]);
      data.items.show();

      if (data.items.length === 0) {
        container.addClass('hidden');
        data.empty_elm.removeClass('hidden');
      } else {
        container.removeClass('hidden');
        data.empty_elm.addClass('hidden');
      }
    }

    function _filter(container) {
      var data = container.data('ceFilterTable');

      if (typeof data == 'undefined') {
        return;
      }

      var input_elm = data.input_elm,
          clear_elm = data.clear_elm,
          empty_elm = data.empty_elm,
          is_logical_and = data.is_logical_and;
      var found_items;
      var items = container.is('table') ? container.find('tbody > tr') : container.find('li');
      items.hide();

      if (input_elm.val() === '') {
        showItems(container, items, empty_elm);
        return;
      }

      found_items = items.filter(function () {
        var haystack = (this.textContent || this.innerText || '').toLowerCase();
        var needle = (input_elm.val() || '').toLowerCase().split(' ');
        var is_found = false;

        for (var k = 0; k < needle.length; k++) {
          is_found = haystack.indexOf(needle[k]) != -1;

          if (is_logical_and && !is_found || !is_logical_and && is_found) {
            break;
          }
        }

        return is_found;
      });
      showItems(container, found_items, empty_elm);

      if (input_elm.val().length > 0) {
        clear_elm.removeClass('hidden');
      } else {
        clear_elm.addClass('hidden');
      }
    }

    var methods = {
      init: function init(params) {
        return this.each(function () {
          var self = $(this),
              $input = $('#' + self.data('caInputId'));
          self.data('ceFilterTable', {
            input_elm: $input,
            clear_elm: $('#' + self.data('caClearId')),
            empty_elm: $('#' + self.data('caEmptyId')),
            is_logical_and: self.data('caFilterTableIsLogicalAnd') || false
          });
          setHandlers(self);

          if (self.data('caInputValue')) {
            $input.val(self.data('caInputValue'));

            _filter(self);

            if (self.data('caScrollTop')) {
              self.scrollTop(self.data('caScrollTop'));
            }
          }
        });
      },
      filter: function filter() {
        return this.each(function () {
          _filter($(this));
        });
      }
    };

    $.fn.ceFilterTable = function (method) {
      if (methods[method]) {
        return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
      } else if (_typeof(method) === 'object' || !method) {
        return methods.init.apply(this, arguments);
      } else {
        $.error('ty.filterTable: method ' + method + ' does not exist');
      }
    };
  })($);

  $(document).ready(function () {
    globalHandlers();
    $('.cm-filter-table').ceFilterTable();
  });
})(Tygh, Tygh.$);