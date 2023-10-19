function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance"); }

function _iterableToArrayLimit(arr, i) { var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }

(function (_, $) {
  var base_url;
  var ajax_ids;
  var no_trigger = false;
  var changed_objects = [];
  var filtered_tables_state = {};
  var dropdowns_state = {};
  var cursorState = {};
  var ajax_promise;
  var timer;
  var timerStatusBox;
  var lastSelectedElms = {};
  var HASH_SEPARATOR = '_';
  var HASH_FEATURE_SEPARATOR = '-';
  var REQUEST_DELAY = 1000;
  var HIDE_DELAY = 5000;

  (function ($) {
    function generateHash(container) {
      var features = {};
      var hash = [];
      container.find('input.cm-product-filters-checkbox:checked').each(function () {
        var elm = $(this);

        if (!features[elm.data('caFilterId')]) {
          features[elm.data('caFilterId')] = [];
        }

        features[elm.data('caFilterId')].push(elm.val());
      });

      for (var k in features) {
        hash.push(k + HASH_FEATURE_SEPARATOR + features[k].join(HASH_FEATURE_SEPARATOR));
      }

      return hash.join(HASH_SEPARATOR);
    }

    function resetFilters(obj) {
      obj.prop('checked', !obj.prop('checked'));

      if (obj.data('prevVal')) {
        no_trigger = true;
        var vals = obj.data('prevVal').split('-');
        var sli = obj.parent().find('.cm-range-slider');

        if (sli.length) {
          sli.slider('values', [vals[0], vals[1]]);
          sli.slider('option', 'slide').call(sli, {}, {
            values: [vals[0], vals[1]]
          }); // that's so dirty, but it works
        }

        var da = obj.parent().find('.cm-date-range');

        if (da.length) {
          da.daterangepicker({
            startDate: vals[0],
            endDate: vals[1]
          });
        }

        no_trigger = false;
      }
    }

    function getProducts(url, obj, objs, container) {
      if (ajax_ids) {
        loadProducts(url, obj, objs, container);
      } else {
        $.redirect(url);
      }

      return false;
    }

    function loadProducts(url, obj, objs, container) {
      saveFilteredTablesState(container);
      saveDropdownsState(container);
      saveCursorState();
      ajax_promise = $.ceAjax('request', url, {
        result_ids: ajax_ids,
        full_render: true,
        save_history: true,
        get_promise: true,
        hidden: true,
        caching: false,
        obj: obj
      });
      ajax_promise.done(function (response) {
        moveScrollToLastPosition(container);

        if (response.products_found_message) {
          var $checkboxContainer = $('#' + obj.attr('id')).closest('.cm-product-filters-checkbox-container');
          showTooltip($checkboxContainer, response.products_found_message);
        }

        changed_objects = [];
        setVisibilityResetFilters(container);
      });
    }

    function moveScrollToLastPosition($container) {
      if ($container && $container.hasClass('cm-filter-table-no-scroll')) {
        return;
      }

      var _arr = Object.entries(lastSelectedElms);

      for (var _i = 0; _i < _arr.length; _i++) {
        var _arr$_i = _slicedToArray(_arr[_i], 2),
            key = _arr$_i[0],
            scrollTop = _arr$_i[1];

        var $variantList = $('#' + key);

        if (!$variantList.length || $variantList.get(0).scrollHeight <= $variantList.get(0).clientHeight) {
          continue;
        }

        $variantList.scrollTop(scrollTop);
      }
    }

    function saveDropdownsState($container) {
      dropdowns_state = {};
      $container.find('.cm-horizontal-filters-content').each(function () {
        var $elem = $(this);
        dropdowns_state[$elem.attr('id')] = {
          open: $elem.is(':visible')
        };
      });
    }

    function restoreDropdownsState($container) {
      $container.find('.cm-horizontal-filters-content').each(function () {
        var $elem = $(this),
            id = $elem.attr('id');

        if (dropdowns_state[id] && dropdowns_state[id].open) {
          $elem.removeClass('hidden');
          $('#sw_' + id).addClass('open');
          delete dropdowns_state[id];
        }
      });
    }

    function restoreVariantsState($productFilters) {
      $('.cm-save-state', $productFilters).each(function () {
        var $elem = $(this);
        var id = $elem.attr('id');
        var prefix = 'sw_';

        if (!id.startsWith(prefix)) {
          return;
        }

        var combination_id = id.substr(prefix.length);

        if (!$.cookie.get(combination_id) || $elem.hasClass('cm-ss-reverse') && !$elem.hasClass('open')) {
          return;
        }

        $.toggleCombination($elem);
      });
    }

    function saveFilteredTablesState($container) {
      filtered_tables_state = {};
      $container.find('.cm-filter-table').each(function () {
        var $elem = $(this),
            $input = $('#' + $elem.data('caInputId'));
        filtered_tables_state[$elem.attr('id')] = {
          top: $elem.scrollTop(),
          input_value: $input.val()
        };
      });
    }

    function restoreFilteredTablesState($container) {
      $container.find('.cm-filter-table').each(function () {
        var $elem = $(this),
            id = $elem.attr('id');

        if (filtered_tables_state[id]) {
          $elem.scrollTop(filtered_tables_state[id].top);
          $elem.data('caInputValue', filtered_tables_state[id].input_value);
          $elem.data('caScrollTop', filtered_tables_state[id].top);
          delete filtered_tables_state[id];
        }
      });
    }

    function saveCursorState() {
      cursorState = {};
      var $focused = $(':focus');

      if (!$focused.length) {
        return;
      }

      cursorState.id = $focused.attr('id');
      cursorState.type = $focused.attr('type');

      if (cursorState.type === 'text') {
        cursorState.val = $focused.val();
        cursorState.selectionStart = $focused[0].selectionStart;
        cursorState.selectionEnd = $focused[0].selectionEnd;
      }
    }

    function restoreCursorState() {
      if (!cursorState.id) {
        return;
      }

      var $activeElem = $('#' + cursorState.id);
      $activeElem.focus();

      if (cursorState.type !== 'text') {
        return;
      }

      var activeElem = $activeElem[0];

      if ($activeElem.val() === cursorState.val) {
        activeElem.setSelectionRange(cursorState.selectionStart, cursorState.selectionEnd);
      } else {
        activeElem.selectionStart = activeElem.selectionEnd = activeElem.value.length;
      }
    }

    function setVisibilityResetFilters($filters) {
      var $wrapperFilter = $filters.find('[data-ca-product-filters="wrapper"]');
      var $toolsFilter = $filters.find('[data-ca-product-filters="tools"]');

      if ($wrapperFilter.data('caProductFiltersStatus') === 'active') {
        $toolsFilter.removeClass('hidden');
      } else {
        $toolsFilter.addClass('hidden');
      }
    }

    function abortRequest() {
      if (ajax_promise) {
        ajax_promise.abort();
      }

      clearTimeout(timer);
      clearTimeout(timerStatusBox);
      $.toggleStatusBox('hide');
    }

    function setHandler() {
      $(_.doc).on('click', '.ty-product-filters__reset-button', function () {
        lastSelectedElms = {};
      });
      $(_.doc).on('click', '.ty-product-filters__block--variants .ty-reset-filters', function () {
        var $parent = $(this).closest('.ty-product-filters__block'),
            $variantsList = $parent.find('.ty-product-filters__variants');

        if ($variantsList.length) {
          var idVariantList = $variantsList.attr('id');
          delete lastSelectedElms[idVariantList];
        }
      });
      $(_.doc).on('change', '.cm-product-filters-checkbox:enabled', function () {
        if (no_trigger) {
          return;
        }

        if (ajax_promise) {
          ajax_promise.abort();
        }

        var $self = $(this),
            $parent = $self.closest('.cm-filter-table'),
            variantListId = $parent.attr('id');
        changed_objects.push($self);
        lastSelectedElms[variantListId] = $parent.scrollTop();
        clearTimeout(timer);
        $.toggleStatusBox('show', {
          show_overlay: false
        });
        timer = setTimeout(function () {
          prepareGetProducts.apply($self);
        }, REQUEST_DELAY);
      });
      $(_.doc).on('ce:combination:switch', '.cm-product-filters .cm-combination', function (event, container, flag) {
        if (!flag) {
          return;
        }

        var $tooltip = $(container).find('[data-ce-tooltip="true"]');

        if ($tooltip.data('tooltip')) {
          $tooltip.data('tooltip').getTip().remove();
          $tooltip.remove();
          ;
        }
      });
    }

    function prepareGetProducts() {
      var self = $(this);
      var container = self.parents('.cm-product-filters');
      return getProducts($.attachToUrl(base_url, 'features_hash=' + generateHash(container)), self, changed_objects, container);
    }

    function showTooltip($container, message) {
      if (!$container.is(':visible')) {
        return;
      }

      var $filters = $container.closest('.cm-product-filters');
      var tooltipClass = $filters.data('caTooltipClass');
      var tooltipRightClass = $filters.data('caTooltipRightClass');
      var tooltipMobileClass = $filters.data('caTooltipMobileClass');
      var tooltipLayoutSelector = $filters.data('caTooltipLayoutSelector');
      var tooltipEventsTooltip = $filters.data('ceTooltipEventsTooltip');
      var containerFirstChild = $container.children(':first-child');
      var tooltipPosition = 'centerRight';
      var isRightOffsetCheckboxContainer = $(window).width() - ($container.offset().left + $container.outerWidth()) < $container.offset().left;

      if (Tygh.language_direction !== 'rtl' && isRightOffsetCheckboxContainer || Tygh.language_direction === 'rtl' && !isRightOffsetCheckboxContainer) {
        tooltipPosition = 'centerLeft';
        tooltipClass += ' ' + tooltipRightClass;
      }

      var $tooltip = $('<div/>', {
        title: message,
        'data-ce-tooltip': true,
        'data-ce-tooltip-position': tooltipPosition,
        'data-ca-tooltip-layout-selector': tooltipLayoutSelector,
        'data-ce-tooltip-events-tooltip': tooltipEventsTooltip,
        'class': tooltipClass,
        html: '&#8203;',
        height: containerFirstChild ? containerFirstChild.outerHeight() : ''
      });
      $tooltip.prependTo($container).ceTooltip({
        onShow: function onShow() {
          var $trigger = this.getTrigger();
          var triggerRect = $trigger[0].getBoundingClientRect();
          var $tip = this.getTip();

          if (Tygh.language_direction === 'rtl') {
            var checkboxContainer = $tooltip.closest('.ty-product-filters').length > 0 ? $tooltip.closest('.ty-product-filters') : $tooltip;
            $tip.css({
              left: isRightOffsetCheckboxContainer ? checkboxContainer.offset().left - $tip.outerWidth(true) + 'px' : checkboxContainer.offset().left + checkboxContainer.outerWidth(true) + 'px'
            });
          } // Set tooltip position when scrolling.


          var $filterBlock = $trigger.closest('.cm-filter-table');

          if ($filterBlock.length > 0) {
            var topFilterBlockPosition = $filterBlock.position().top;
            var bottomFilterBlockPosition = $filterBlock.position().top + $filterBlock.outerHeight();

            if ($tip.position().top > bottomFilterBlockPosition) {
              $tip.css({
                top: bottomFilterBlockPosition - $tip.outerHeight() + 'px'
              });
            } else if ($tip.position().top < topFilterBlockPosition) {
              $tip.css({
                top: $filterBlock.position().top + 'px'
              });
            }
          }
        }
      }).ceTooltip('show').on('onHide', function () {
        $tooltip.data('tooltip').getTip().remove();
        $tooltip.remove();
      });
      var isMobile = $('body').hasClass('screen--xs') || $('body').hasClass('screen--xs-large') || $('body').hasClass('screen--sm');

      if (isMobile) {
        $('.tooltip').addClass(tooltipMobileClass);
      }

      setTimeout(function () {
        $tooltip.ceTooltip('hide').remove();
      }, HIDE_DELAY);
    }

    function setCallback() {
      // re-init filters
      $.ceEvent('on', 'ce.commoninit', function (context) {
        var $productFilters = $('.cm-product-filters', context);

        if (context.is('.cm-product-filters')) {
          $productFilters = $productFilters.add(context);
        }

        initSlider(context);

        if (context && (context.is(document) || context.hasClass('cm-product-filters'))) {
          $('.cm-filter-table').on('scroll', $.debounce(function (e) {
            var $self = $(this),
                variantListId = $self.attr('id');
            lastSelectedElms[variantListId] = $self.scrollTop();
          }, 100));
          moveScrollToLastPosition();
        }

        if (!$productFilters.length) {
          return;
        }

        $productFilters.each(function () {
          var self = $(this);

          if (self.data('caBaseUrl')) {
            base_url = self.data('caBaseUrl');
            ajax_ids = self.data('caTargetId');
          }

          setVisibilityResetFilters(self);
        });
        restoreDropdownsState(context);
        restoreFilteredTablesState(context);
        restoreCursorState();
        restoreVariantsState($productFilters);
        var $color_filter_selectors = context.find('[data-cm-product-color-filter="true"]:has(.cm-product-filters-checkbox:enabled)');

        if ($color_filter_selectors.length) {
          $color_filter_selectors.on('click touch', function (e) {
            var $color_filter_selector = $(this),
                dependent_checkbox_id = $color_filter_selector.data('caProductColorFilterCheckboxId'),
                $dependent_checkbox = $('#' + dependent_checkbox_id);
            $color_filter_selector.toggleClass('selected');
            $dependent_checkbox.prop('checked', !$dependent_checkbox.prop('checked'));
            $dependent_checkbox.trigger('change');
          });
        }
      });
      $.ceEvent('on', 'ce.filterdate', function (elm, time_from, time_to) {
        var cb = $('#elm_checkbox_' + elm.prop('id'));
        cb.data('prevVal', cb.val());
        cb.val(time_from + '-' + time_to).prop('checked', true).trigger('change');
      });
    }

    function initSlider(parent) {
      parent.find('.cm-range-slider').each(function () {
        var $el = $(this);
        var id = $el.prop('id');
        var json_data = $('#' + id + '_json').val();

        if ($el.data('uiSlider') || !json_data) {
          return false;
        }

        var data = $.parseJSON(json_data) || null;

        if (!data) {
          return false;
        }

        $el.slider({
          disabled: data.disabled,
          range: true,
          min: data.min,
          max: data.max,
          step: data.step,
          values: [data.left, data.right],
          slide: function slide(event, ui) {
            abortRequest();
            $('#' + id + '_left').val(ui.values[0]);
            $('#' + id + '_right').val(ui.values[1]);
          },
          change: function change(event, ui) {
            abortRequest();
            var statusBoxDelay = REQUEST_DELAY;
            var loadDelay = REQUEST_DELAY * 3; // If the slider is dragged, remove the delay.

            if (event.handleObj) {
              loadDelay = statusBoxDelay = REQUEST_DELAY / 3;
            }

            timerStatusBox = setTimeout(function () {
              $.toggleStatusBox('show', {
                show_overlay: false
              });
            }, statusBoxDelay);
            var replacement = ui.values[0] + '-' + ui.values[1];

            if (data.extra) {
              replacement = replacement + '-' + data.extra;
            }

            var $checkbox = $('#elm_checkbox_' + id);
            timer = setTimeout(function () {
              $checkbox.data('prevVal', $checkbox.val());
              $checkbox.val(replacement).prop('checked', true).trigger('change');
            }, loadDelay);
          },
          start: function start(event, ui) {
            abortRequest();
          }
        });

        if (data.left != data.min || data.right != data.max) {
          var replacement = data.left + '-' + data.right;

          if (data.extra) {
            replacement = replacement + '-' + data.extra;
          }

          $('#elm_checkbox_' + id).val(replacement).prop('checked', true);
        }

        $('#' + id + '_left, #' + id + '_right').off('change input focus').on('change input focus', function () {
          var $inputsContainer = $(this).closest('.cm-product-filters-checkbox-container');
          var $inputLeft = $inputsContainer.find('#' + id + '_left');
          var $inputRight = $inputsContainer.find('#' + id + '_right');

          var inputLeftValue = _.toNumeric($inputLeft.val());

          var inputRightValue = _.toNumeric($inputRight.val());

          if (inputLeftValue === $inputLeft.data('caPreviousValue') && inputRightValue === $inputRight.data('caPreviousValue')) {
            abortRequest();
            return;
          }

          $inputLeft.data('previousValue', inputLeftValue);
          $inputRight.data('previousValue', inputRightValue);
          $el.slider('values', [inputLeftValue, inputRightValue]);
        });

        if ($el.parents('.filter-wrap').hasClass('open')) {
          $el.parent('.price-slider').show();
        }
      });
    }

    setCallback();
    setHandler();
  })($);
})(Tygh, Tygh.$);