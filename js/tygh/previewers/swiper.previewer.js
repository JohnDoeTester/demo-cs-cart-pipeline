/* previewer-description:carousel_swiper */
(function (_, $) {
  $.loadCss(['js/lib/swiper/swiper.min.css']);

  function fn_display(elm) {
    var imageId = elm.data('caImageId');
    var elms = $('a[data-ca-image-id="' + imageId + '"] img');
    var previewer = $('<div class="ty-swiper-previewer"></div>');
    var previewerContainer = $('<div class="ty-swiper-previewer__container swiper-container"></div>');
    var previewerWrapper = $('<div class="ty-swiper-previewer__wrapper swiper-wrapper"></div>');
    previewerContainer.attr('dir', _.language_direction);
    elms.each(function (index, elm) {
      var _clonedNode = $(elm).clone(),
          _imageContainer = $('<div class="ty-swiper-previewer__slide swiper-slide">&nbsp;</div>');

      _clonedNode.attr('srcset', '');

      _clonedNode.attr('src', $(elm).parent('a').attr('href') || _clonedNode.attr('src'));

      _clonedNode.addClass('ty-swiper-previewer__img');

      _clonedNode.appendTo(_imageContainer);

      _imageContainer.appendTo(previewerWrapper);
    });
    previewerContainer.append(previewerWrapper);

    if (elms.length > 1) {
      previewerContainer.append('<div class="ty-swiper-previewer__button-prev swiper-button-prev" data-button="prev"></div>');
      previewerContainer.append('<div class="ty-swiper-previewer__button-next swiper-button-next" data-button="next"></div>');
    }

    previewerContainer.appendTo(previewer);
    previewer.appendTo(_.body);
    var activeIndexImg = $(elm).closest(".owl-item.active").index();
    var swiper = new Swiper(previewerContainer, {
      initialSlide: activeIndexImg ? activeIndexImg : 0,
      navigation: {
        nextEl: elms.length > 1 ? '[data-button="next"]' : '',
        prevEl: elms.length > 1 ? '[data-button="prev"]' : ''
      },
      spaceBetween: 50
    }); //for the correct operation of the swipe animation in Firefox

    var isFirefox = typeof InstallTrigger !== 'undefined';

    if (isFirefox) {
      swiper.update();
    }

    var _scrollPosition = $(document).scrollTop();

    previewer.ceDialog('open', {
      dialogClass: 'ty-swiper-previewer__dialog',
      containerClass: 'ty-swiper-previewer__object-container',
      onClose: function onClose() {
        setTimeout(function () {
          $('html, body').animate({
            scrollTop: _scrollPosition
          }, 0);
          $.ceDialog('get_last').ceDialog('reload');
        }, 0);
        previewer.remove(); // unset scroll-prevent styles

        $(_.body).css({
          overflow: '',
          maxHeight: ''
        });
      }
    }); // set scroll-prevent styles (no Y-scroll when images slides)

    $(_.body).css({
      overflow: 'hidden',
      maxHeight: '100vh'
    });
  }

  $.cePreviewer('handlers', {
    display: function display(elm) {
      if (typeof Swiper === "undefined") {
        $.getScript('js/lib/swiper/swiper.min.js', function () {
          fn_display(elm);
        });
      } else {
        fn_display(elm);
      }
    }
  });
})(Tygh, Tygh.$);