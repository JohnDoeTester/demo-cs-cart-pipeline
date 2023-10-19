function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

(function (_, $) {
  var ITEMS_COUNT_DEFAULT = 3;
  var scroller_type;
  var icon_left_circle = '<span class="ty-icon icon-left-circle ty-icon-left-circle"></span>',
      icon_right_circle = '<span class="ty-icon icon-right-circle ty-icon-right-circle"></span>',
      icon_left_open_thin = '<span class="ty-icon icon-left-open-thin ty-icon-left-open-thin"></span>',
      icon_right_open_thin = '<span class="ty-icon icon-right-open-thin ty-icon-right-open-thin"></span>';
  var methods = {
    init: function init() {
      var container = $(this);
      var params = {
        items_count: container.data('caItemsCount') ? container.data('caItemsCount') : ITEMS_COUNT_DEFAULT,
        items_responsive: container.data('caItemsResponsive') ? true : false
      };

      if (container.hasClass('jcarousel-skin') || container.parent().hasClass('jcarousel-skin')) {
        scroller_type = 'jcarousel';
      } else {
        scroller_type = 'owlcarousel';
      }

      if (methods.countElms(container) <= params.items_count) {
        container.removeClass('owl-carousel');
      }

      if (methods.countElms(container) > params.items_count || container.hasClass('jcarousel-skin') && methods.countElms(container) > params.items_count) {
        if (container.data('owl-carousel') || container.data('jcarousel')) {
          return true;
        }

        methods.check(container, params);
      }

      methods.bind(container);
      return true;
    },
    load: function load(container, params) {
      if (scroller_type == 'owlcarousel') {
        container.owlCarousel({
          direction: _.language_direction,
          items: params.items_count,
          singleItem: params.items_count == 1 ? true : false,
          responsive: params.items_responsive,
          pagination: false,
          navigation: true,
          lazyLoad: true,
          navigationText: params.items_count == 1 ? [icon_left_circle, icon_right_circle] : [icon_left_open_thin, icon_right_open_thin],
          theme: params.items_count == 1 ? 'owl-one-theme' : 'owl-more-theme',
          beforeInit: function beforeInit() {
            $.ceEvent('trigger', 'ce.product_image_gallery.inner.beforeInit', [this]);
          },
          afterInit: function afterInit(item) {
            $(item).css({
              'visibility': 'visible',
              'position': 'relative'
            });
          }
        });
      } else {
        $('li', container).show();
        container.jcarousel({
          scroll: 1,
          wrap: 'circular',
          animation: 'fast',
          initCallback: $.ceScrollerMethods.init_callback,
          itemFallbackDimension: params.i_width,
          item_width: params.i_width,
          item_height: params.i_height,
          clip_width: params.c_width,
          clip_height: params.i_height,
          buttonNextHTML: '<div>' + icon_right_open_thin + '</div>',
          buttonPrevHTML: '<div>' + icon_left_open_thin + '</div>',
          buttonNextEvent: 'click',
          buttonPrevEvent: 'click',
          size: methods.countElms(container)
        });
      }
    },
    check: function check(container, params) {
      if (container.data('owl-carousel') || container.data('jcarousel') || params.i_width || params.i_height) {
        return true;
      }

      var t_elm = false;

      if ($('.cm-gallery-item', container).length) {
        var load = false; // Check images are loaded

        $('.cm-gallery-item', container).each(function () {
          var elm = $(this);
          var i_elm = $('img', elm);

          if (i_elm.length) {
            if (i_elm.attr('width') && i_elm.attr('width').length || elm.outerWidth() >= i_elm.width()) {
              // find first loaded image
              t_elm = elm;
              return false;
            }

            load = true;
          }
        });

        if (!t_elm) {
          if (load) {
            var check_load = function check_load() {
              methods.check(container, params);
            }; // wait until image is loaded


            setTimeout(check_load, 500);
            return false;
          } else {
            t_elm = $('.cm-gallery-item:first', container);
          }
        }
      } else {
        t_elm = $('img:first', container);
      }

      params.i_width = t_elm.outerWidth(true);
      params.i_height = t_elm.outerHeight(true);
      params.c_width = params.i_width * params.items_count;
      container.closest('.cm-image-gallery-wrapper').width(params.c_width);
      return methods.load(container, params);
    },
    bind: function bind(container) {
      container.click(function (e) {
        var jelm = $(e.target);
        var pjelm;
        var in_elm; // Check elm clicking

        if (scroller_type == 'owlcarousel') {
          in_elm = jelm.parents('.cm-item-gallery') || jelm.parents('div.cm-thumbnails-mini') ? true : false;
        } else {
          in_elm = jelm.parents('li') || jelm.parents('div.cm-thumbnails-mini') ? true : false;
        }

        if (in_elm && !jelm.is('img')) {
          // Check if the object is image or SWF embed object or parent is SWF-container
          return false;
        }

        if (jelm.hasClass('cm-thumbnails-mini') || (pjelm = jelm.parents('a:first.cm-thumbnails-mini'))) {
          jelm = pjelm && pjelm.length ? pjelm : jelm;
          var c_id = jelm.data('caGalleryLargeId'),
              image_box;

          if (scroller_type == 'owlcarousel') {
            image_box = $('#' + c_id).closest('.cm-preview-wrapper');

            if (!image_box.length) {
              image_box = $('.cm-preview-wrapper:first');
            }
          } else {
            var jc_box = $(this).parents('.jcarousel-skin:first');
            image_box = jc_box.length ? jc_box.parents(':first') : $(this).parents(':first');
          }

          $(image_box).trigger('owl.goTo', $(jelm).data('caImageOrder') || 0);
        }
      });
    },
    countElms: function countElms(container) {
      if (scroller_type == 'owlcarousel') {
        return $('.cm-gallery-item', container).length;
      } else {
        return $('li', container).length;
      }
    }
  };

  $.fn.ceProductImageGallery = function (method) {
    if ($('.jcarousel-skin').length !== 0) {
      if (!$().jcarousel) {
        var gelms = $(this);
        $.getScript('js/lib/jcarousel/jquery.jcarousel.js', function () {
          gelms.ceProductImageGallery();
        });
        return false;
      }
    } else {
      if (!$().owlCarousel) {
        var gelms = $(this);
        $.getScript('js/lib/owlcarousel/owl.carousel.min.js', function () {
          gelms.ceProductImageGallery();
        });
        return false;
      }
    }

    return $(this).each(function (i, elm) {
      // These vars are local for each element
      var errors = {};

      if (methods[method]) {
        return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
      } else if (_typeof(method) === 'object' || !method) {
        return methods.init.apply(this, arguments);
      } else {
        $.error('ty.productimagegallery: method ' + method + ' does not exist');
      }
    });
  };
})(Tygh, Tygh.$);

(function (_, $) {
  $.ceEvent('on', 'ce.commoninit', function (context) {
    $('.cm-preview-wrapper', context).owlCarousel({
      direction: _.language_direction,
      pagination: false,
      singleItem: true,
      addClassActive: true,
      beforeInit: function beforeInit() {
        $.ceEvent('trigger', 'ce.product_image_gallery.beforeInit', [this]);
      },
      afterInit: function afterInit(item) {
        var thumbnails = $('.cm-thumbnails-mini', item.parents('[data-ca-previewer]')),
            previewers = $('.cm-image-previewer', item.parents('[data-ca-previewer]')),
            previousScreenX = 0,
            newScreenX = 0,
            swipeThreshold = 7;
        previewers.each(function (index, elm) {
          $(elm).data('caImageOrder', index);
        });
        thumbnails.on('click', function () {
          item.trigger('owl.goTo', $(this).data('caImageOrder') ? $(this).data('caImageOrder') : 0);
        });
        item.on('touchstart', function (e) {
          previousScreenX = e.changedTouches[0].screenX;
        });
        item.on('touchmove', function (e) {
          newScreenX = e.changedTouches[0].screenX;

          if (Math.abs(newScreenX - previousScreenX) > swipeThreshold && e.cancelable) {
            e.preventDefault();
          }

          previousScreenX = newScreenX;
        });
        $('.cm-image-previewer.hidden', item).toggleClass('hidden', false);
        $.ceEvent('trigger', 'ce.product_image_gallery.ready');
      },
      afterMove: function afterMove(item) {
        var _parent = item.parent(); // inactive all thumbnails


        $('.cm-thumbnails-mini', _parent).toggleClass('active', false); // active only current thumbnail

        var elmOrderInGallery = $('.active', item).index(); // order of active image in carousel

        $('[data-ca-image-order=' + elmOrderInGallery + ']', _parent).toggleClass('active', true); // move mini-thumbnail-gallery

        $('.owl-carousel.cm-image-gallery', _parent).trigger('owl.goTo', elmOrderInGallery);
        $.ceEvent('trigger', 'ce.product_image_gallery.image_changed');
      }
    });
  });
})(Tygh, Tygh.$);