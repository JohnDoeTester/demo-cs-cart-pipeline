(function (_, $) {
  // Support for the 'srcset' attribute.
  var Carousel = {
    newLazyPreload: function newLazyPreload($item, $lazyImg) {
      var isOverrideEnabled = this.$elem.data('caScrollerIsOverrideEnabled');
      var base = this,
          iterations = 0,
          isBackgroundImg;

      if ($lazyImg.prop("tagName") === "DIV") {
        $lazyImg.css("background-image", "url(" + $lazyImg.data("src") + ")");
        isBackgroundImg = true;
      } else {
        if (isOverrideEnabled) {
          Object.assign($lazyImg[0], {
            srcset: $lazyImg.data("srcset"),
            src: $lazyImg.data("src")
          });
        } else {
          $lazyImg[0].src = $lazyImg.data("src");
        }
      }

      function showImage() {
        $item.data("owl-loaded", "loaded").removeClass("loading");

        if (isOverrideEnabled) {
          $lazyImg.removeAttr("data-srcset data-src");
        } else {
          $lazyImg.removeAttr("data-src");
        }

        if (base.options.lazyEffect === "fade") {
          $lazyImg.fadeIn(400);
        } else {
          $lazyImg.show();
        }

        if (typeof base.options.afterLazyLoad === "function") {
          base.options.afterLazyLoad.apply(this, [base.$elem]);
        }
      }

      function checkLazyImage() {
        iterations += 1;

        if (base.completeImg($lazyImg.get(0)) || isBackgroundImg === true) {
          showImage();
        } else if (iterations <= 100) {
          //if image loads in less than 10 seconds 
          window.setTimeout(checkLazyImage, 100);
        } else {
          showImage();
        }
      }

      checkLazyImage();
    }
  };

  function overrideLazyPreloadFunction(scrollerData) {
    scrollerData.$elem.data('caScrollerIsOverrideEnabled', true);
    scrollerData.lazyPreload = Carousel.newLazyPreload;
  } // Events


  $.ceEvent('on', 'ce.scroller.beforeInit', function (scrollerData) {
    overrideLazyPreloadFunction(scrollerData);
  });
  $.ceEvent('on', 'ce.scroller_init_with_quantity.beforeInit', function (scrollerData) {
    overrideLazyPreloadFunction(scrollerData);
  });
  $.ceEvent('on', 'ce.product_image_gallery.beforeInit', function (scrollerData) {
    overrideLazyPreloadFunction(scrollerData);
  });
  $.ceEvent('on', 'ce.product_image_gallery.inner.beforeInit', function (scrollerData) {
    overrideLazyPreloadFunction(scrollerData);
  });
  $.ceEvent('on', 'ce.previewers.beforeInit', function (scrollerData) {
    overrideLazyPreloadFunction(scrollerData);
  });
  $.ceEvent('on', 'ce.banner.carousel.beforeInit', function (scrollerData) {
    overrideLazyPreloadFunction(scrollerData);
  });
})(Tygh, Tygh.$);