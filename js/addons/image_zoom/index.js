(function (_, $) {
  var FLYOUT_WIDTH = 450,
      FLYOUT_HEIGHT = 450,
      FLYOUT_OFFSET = 10,
      VISIBLE_Z_INDEX = 1100,
      HIDDEN_Z_INDEX = -9001,
      FLYOUT_CLASS = 'ty-image-zoom__flyout',
      FLYOUT_VISIBLE_CLASS = FLYOUT_CLASS + '--visible',
      QUICK_VIEW_SELECTOR = '[aria-describedby="product_quick_view"]:visible',
      FLYOUT_DELAY_BEFORE_DISPLAY = 200,
      FLYOUT_DELAY_BEFORE_HIDE = 100,
      VIEW_BOX_SIZE = 100;
  /**
   * Possible image zoom previewer positions.
   */

  var POS_TOP_CENTER = 0,
      POS_TOP_RIGHT = 1,
      POS_TOP_RIGHT_OUT = 2,
      POS_RIGHT_TOP = 3,
      POS_RIGHT_CENTER = 4,
      POS_RIGHT_BOTTOM = 5,
      POS_RIGHT_BOTTOM_OUT = 6,
      POS_BOTTOM_RIGHT = 7,
      POS_BOTTOM_CENTER = 8,
      POS_BOTTOM_LEFT = 9,
      POS_LEFT_BOTTOM_OUT = 10,
      POS_LEFT_BOTTOM = 11,
      POS_LEFT_CENTER = 12,
      POS_LEFT_TOP = 13,
      POS_TOP_LEFT_OUT = 14,
      POS_TOP_LEFT = 15,
      POSITION_EDGE = 16;
  var thumbnailPosition, flyoutSize, thumbnailSize, pointerPosition, ratioX, ratioY, pointerPositionChecker, hasActiveFlyout, positionId, $thumbnail, $previewerWrapper;
  /**
   * Checks whether a point is bounded by a rectangle.
   *
   * @param {Number} pointX Point X coordinate
   * @param {Number} pointY Point Y coordinate
   * @param {Number} rectX Rectangle top left corner X coordinate
   * @param {Number} rectY Rectangle top left corner Y coordinate
   * @param {Number} rectWidth Rectangle width
   * @param {Number} rectHeight Rectangle height
   *
   * @returns {boolean}
   */

  function isPointBoundedByRectangle(pointX, pointY, rectX, rectY, rectWidth, rectHeight) {
    return pointX > rectX && pointX < rectX + rectWidth && pointY > rectY && pointY < rectY + rectHeight;
  }
  /**
   * Overrides EasyZoom's _move handler to add view box functionality.
   *
   * @param {MouseEvent} e
   * @private
   */


  EasyZoom.prototype._move = function (e) {
    var self = this;

    if (!pointerPosition) {
      return;
    }

    if (!hasActiveFlyout) {
      /**
       * If an image is placed within an Owl carousel gallery AND a carousel item is currently moving,
       * an image zoom previewer will be shown in the wrong place.
       * To prevent this issue, the delay before showing image zoom previewer is added.
       */
      $.debounce(function () {
        $.ceImageZoom('getThumbnailPosition');

        if (!$.ceImageZoom('isPointerInThumbnail')) {
          return;
        }

        var flyoutPosition = $.ceImageZoom('getFlyoutPosition', positionId);
        self.$flyout.css({
          left: flyoutPosition.left,
          top: flyoutPosition.top,
          zIndex: $(QUICK_VIEW_SELECTOR).length ? $(QUICK_VIEW_SELECTOR).css('zIndex') + 1 : VISIBLE_Z_INDEX
        });
        self.$flyout.addClass(FLYOUT_VISIBLE_CLASS);
        hasActiveFlyout = true;
      }, FLYOUT_DELAY_BEFORE_DISPLAY)();
    }

    if (!pointerPositionChecker) {
      pointerPositionChecker = setInterval(function () {
        if (hasActiveFlyout && !$.ceImageZoom('isPointerInThumbnail')) {
          $.ceImageZoom('hideAllFlyouts', self.$flyout);
        }
      }, FLYOUT_DELAY_BEFORE_HIDE);
    }

    if (!hasActiveFlyout) {
      return;
    }

    var relativePositionX = pointerPosition.pageX - thumbnailPosition.left,
        relativePositionY = pointerPosition.pageY - thumbnailPosition.top;
    var centerDistanceX = 2 * relativePositionX / thumbnailSize.width - 1,
        centerDistanceY = 2 * relativePositionY / thumbnailSize.height - 1;
    relativePositionX += centerDistanceX * VIEW_BOX_SIZE;
    relativePositionX = Math.max(relativePositionX, 0);
    relativePositionX = Math.min(relativePositionX, thumbnailSize.width);
    relativePositionY += centerDistanceY * VIEW_BOX_SIZE;
    relativePositionY = Math.max(relativePositionY, 0);
    relativePositionY = Math.min(relativePositionY, thumbnailSize.height);
    var moveX = Math.ceil(relativePositionX * ratioX),
        moveY = Math.ceil(relativePositionY * ratioY);
    this.$zoom.css({
      top: moveY * -1,
      left: moveX * -1
    });
  };

  var methods = {
    /**
     * Translates flyout position for right-to-left directed languages.
     *
     * @param {Number} positionId Position identifier
     *
     * @returns {Number}
     */
    translateFlyoutPositionToRtl: function translateFlyoutPositionToRtl(positionId) {
      if (positionId === POS_TOP_CENTER || positionId === POS_BOTTOM_CENTER) {
        return positionId;
      }

      return POSITION_EDGE - positionId;
    },

    /**
     * Gets image zoom previewer position on page.
     *
     * @param {Number} positionId Position identifier
     *
     * @returns {{top: number, left: number}} Image zoom previewer top and left offset
     */
    getFlyoutPosition: function getFlyoutPosition(positionId) {
      var flyoutPosition = {};
      var flyoutOuterSize = flyoutSize.width + FLYOUT_OFFSET;
      var thumbnailPositionRight = thumbnailPosition.left + thumbnailSize.width; // Correct overflow flyout position

      if ($(window).width() - thumbnailPositionRight < flyoutOuterSize) {
        switch (positionId) {
          case POS_TOP_RIGHT_OUT:
            positionId = POS_TOP_LEFT_OUT;
            break;

          case POS_RIGHT_TOP:
            positionId = POS_LEFT_TOP;
            break;

          case POS_RIGHT_CENTER:
            positionId = POS_LEFT_CENTER;
            break;

          case POS_RIGHT_BOTTOM:
            positionId = POS_LEFT_BOTTOM;
            break;

          case POS_RIGHT_BOTTOM_OUT:
            positionId = POS_LEFT_BOTTOM_OUT;
            break;
        }

        ;
      } else if (thumbnailPosition.left < flyoutOuterSize) {
        switch (positionId) {
          case POS_LEFT_BOTTOM_OUT:
            positionId = POS_RIGHT_BOTTOM_OUT;
            break;

          case POS_LEFT_BOTTOM:
            positionId = POS_RIGHT_BOTTOM;
            break;

          case POS_LEFT_CENTER:
            positionId = POS_RIGHT_CENTER;
            break;

          case POS_LEFT_TOP:
            positionId = POS_RIGHT_TOP;
            break;

          case POS_TOP_LEFT_OUT:
            positionId = POS_TOP_RIGHT_OUT;
            break;
        }

        ;
      }

      ; // Calculate flyout position

      switch (positionId) {
        case POS_TOP_LEFT:
          flyoutPosition = {
            top: thumbnailPosition.top - flyoutSize.height - FLYOUT_OFFSET,
            left: thumbnailPosition.left
          };
          break;

        case POS_TOP_CENTER:
          flyoutPosition = {
            top: thumbnailPosition.top - flyoutSize.height - FLYOUT_OFFSET,
            left: thumbnailPosition.left + thumbnailSize.width / 2 - flyoutSize.width / 2
          };
          break;

        case POS_TOP_RIGHT:
          flyoutPosition = {
            top: thumbnailPosition.top - flyoutSize.height - FLYOUT_OFFSET,
            left: thumbnailPosition.left + thumbnailSize.width - flyoutSize.width
          };
          break;

        case POS_TOP_RIGHT_OUT:
          flyoutPosition = {
            top: thumbnailPosition.top - flyoutSize.height - FLYOUT_OFFSET,
            left: thumbnailPosition.left + thumbnailSize.width + FLYOUT_OFFSET
          };
          break;

        case POS_RIGHT_TOP:
          flyoutPosition = {
            top: thumbnailPosition.top,
            left: thumbnailPosition.left + thumbnailSize.width + FLYOUT_OFFSET
          };
          break;

        case POS_RIGHT_CENTER:
          flyoutPosition = {
            top: thumbnailPosition.top + thumbnailSize.height / 2 - flyoutSize.height / 2,
            left: thumbnailPosition.left + thumbnailSize.width + FLYOUT_OFFSET
          };
          break;

        case POS_RIGHT_BOTTOM:
          flyoutPosition = {
            top: thumbnailPosition.top + thumbnailSize.height - flyoutSize.height,
            left: thumbnailPosition.left + thumbnailSize.width + FLYOUT_OFFSET
          };
          break;

        case POS_RIGHT_BOTTOM_OUT:
          flyoutPosition = {
            top: thumbnailPosition.top + thumbnailSize.height + FLYOUT_OFFSET,
            left: thumbnailPosition.left + thumbnailSize.width + FLYOUT_OFFSET
          };
          break;

        case POS_BOTTOM_RIGHT:
          flyoutPosition = {
            top: thumbnailPosition.top + thumbnailSize.height + FLYOUT_OFFSET,
            left: thumbnailPosition.left + thumbnailSize.width - flyoutSize.width
          };
          break;

        case POS_BOTTOM_CENTER:
          flyoutPosition = {
            top: thumbnailPosition.top + thumbnailSize.height + FLYOUT_OFFSET,
            left: thumbnailPosition.left + thumbnailSize.width / 2 - flyoutSize.width / 2
          };
          break;

        case POS_BOTTOM_LEFT:
          flyoutPosition = {
            top: thumbnailPosition.top + thumbnailSize.height + FLYOUT_OFFSET,
            left: thumbnailPosition.left
          };
          break;

        case POS_LEFT_BOTTOM_OUT:
          flyoutPosition = {
            top: thumbnailPosition.top + thumbnailSize.height + FLYOUT_OFFSET,
            left: thumbnailPosition.left - flyoutSize.width - FLYOUT_OFFSET
          };
          break;

        case POS_LEFT_BOTTOM:
          flyoutPosition = {
            top: thumbnailPosition.top + thumbnailSize.height - flyoutSize.height,
            left: thumbnailPosition.left - flyoutSize.width - FLYOUT_OFFSET
          };
          break;

        case POS_LEFT_CENTER:
          flyoutPosition = {
            top: thumbnailPosition.top + thumbnailSize.height / 2 - flyoutSize.height / 2,
            left: thumbnailPosition.left - flyoutSize.width - FLYOUT_OFFSET
          };
          break;

        case POS_LEFT_TOP:
          flyoutPosition = {
            top: thumbnailPosition.top,
            left: thumbnailPosition.left - flyoutSize.width - FLYOUT_OFFSET
          };
          break;

        case POS_TOP_LEFT_OUT:
          flyoutPosition = {
            top: thumbnailPosition.top - flyoutSize.height - FLYOUT_OFFSET,
            left: thumbnailPosition.left - flyoutSize.width - FLYOUT_OFFSET
          };
          break;

        default:
          flyoutPosition = {
            top: 0,
            left: 0
          };
      }

      ;
      return flyoutPosition;
    },

    /**
     * Attaches Image Zoom previewer on image previewer.
     *
     * @param {jQuery} $previewer Image previewer
     * @param {Number} position Position identifier
     */
    init: function init($previewer, position) {
      positionId = position;
      var self = methods;
      $previewer.wrap('<span class="ty-image-zoom__wrapper easyzoom easyzoom--adjacent"></span>');
      $(_.doc).on('mousemove mouseover', function (event) {
        pointerPosition = {
          pageX: event.pageX,
          pageY: event.pageY
        };
      });
      var $thumbnailWrapper = $previewer.closest('.ty-image-zoom__wrapper');
      $thumbnailWrapper.easyZoom({
        loadingNotice: '',
        errorNotice: '',
        beforeShow: function beforeShow() {
          $previewerWrapper = $previewer.closest('.cm-preview-wrapper');
          $thumbnail = $('.cm-image', $previewer);
          self.getThumbnailPosition();
          thumbnailSize = {
            width: Math.min($thumbnail.width(), $previewer.width()),
            height: Math.max($thumbnail.height(), $previewer.height())
          };
          this.$target = $('.ty-tygh');
          self.hideAllFlyouts(this.$flyout);
          this.$flyout.addClass([FLYOUT_CLASS, 'hidden-tablet', 'hidden-phone'].join(' '));
        },
        onShow: function onShow() {
          /**
           * Actual image zoom previewer display happens in the overriden EasyZoom.prototype._move handler.
           */
          flyoutSize = {
            width: Math.min(FLYOUT_WIDTH, this.$zoom.width()),
            height: Math.min(FLYOUT_HEIGHT, this.$zoom.height())
          };
          var flyoutPosition = $.ceImageZoom('getFlyoutPosition', positionId);
          this.$flyout.css({
            left: flyoutPosition.left,
            top: flyoutPosition.top,
            width: flyoutSize.width,
            height: flyoutSize.height
          });
          ratioX = (this.$zoom.width() - flyoutSize.width) / thumbnailSize.width;
          ratioY = (this.$zoom.height() - flyoutSize.height) / thumbnailSize.height;
        },
        beforeHide: function beforeHide() {
          /**
           * Actual image zoom previewer hide happens in the overriden EasyZoom.prototype._move handler.
           */
          return !self.isPointerInThumbnail();
        }
      });
    },

    /**
     * Updates and returns current thumbnail position.
     *
     * @returns {{top: number, left: number}} Thumbnail position.
     */
    getThumbnailPosition: function getThumbnailPosition() {
      thumbnailPosition = {
        left: Math.max($thumbnail.offset().left, $previewerWrapper.offset().left),
        top: Math.min($thumbnail.offset().top, $previewerWrapper.offset().top)
      };
      return thumbnailPosition;
    },

    /**
     * Hides all image zoom previewers.
     *
     * @param {jQuery} $activeFlyout
     */
    hideAllFlyouts: function hideAllFlyouts($activeFlyout) {
      $('.' + FLYOUT_CLASS).removeClass(FLYOUT_VISIBLE_CLASS).css({
        zIndex: HIDDEN_Z_INDEX
      }); // fixes blinking in Chrome

      if ($activeFlyout) {
        $activeFlyout.removeClass(FLYOUT_VISIBLE_CLASS).css({
          zIndex: HIDDEN_Z_INDEX
        });
      }

      hasActiveFlyout = false;

      if (pointerPositionChecker) {
        clearInterval(pointerPositionChecker);
        pointerPositionChecker = null;
      }
    },

    /**
     * Checks whether a pointer on page is placed over an image thumbnail.
     *
     * @returns {boolean}
     */
    isPointerInThumbnail: function isPointerInThumbnail() {
      return pointerPosition && isPointBoundedByRectangle(pointerPosition.pageX, pointerPosition.pageY, thumbnailPosition.left, thumbnailPosition.top, thumbnailSize.width, thumbnailSize.height);
    }
  };
  $.extend({
    ceImageZoom: function ceImageZoom(method) {
      if (methods[method]) {
        return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
      } else {
        $.error('ty.imageZoom: method ' + method + ' does not exist');
      }
    }
  });
})(Tygh, Tygh.$);