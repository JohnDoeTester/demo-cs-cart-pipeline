/*
* tabSlideOUt v1.2 (altered by CS-cart team)
*
* Originally by William Paoli: http://wpaoli.building58.com
*/
(function ($) {
  $.fn.tabSlideOut = function (callerSettings) {
    var settings = $.extend({
      tabHandle: '.handle',
      speed: 300,
      action: 'click',
      tabLocation: 'top',
      topPos: '0px',
      leftPos: '20px',
      fixedPosition: false,
      positioning: 'absolute',
      pathToTabImage: null,
      imageHeight: null,
      imageWidth: null
    }, callerSettings || {});
    settings.tabHandle = $(settings.tabHandle);
    var obj = this;

    if (settings.fixedPosition === true) {
      settings.positioning = 'fixed';
    } else {
      settings.positioning = 'absolute';
    } //ie6 doesn't do well with the fixed option


    if (document.all && !window.opera && !window.XMLHttpRequest) {
      settings.positioning = 'absolute';
    } //set initial tabHandle css


    settings.tabHandle.css({
      'display': 'block',
      'width': settings.imageWidth,
      'height': settings.imageHeight,
      'textIndent': '0px',
      'outline': 'none',
      'position': 'absolute'
    });
    obj.css({
      'line-height': '1',
      'position': settings.positioning
    });
    var properties = {
      containerWidth: parseInt(obj.outerWidth(), 10) + 'px',
      containerHeight: parseInt(obj.outerHeight(), 10) + 'px',
      tabWidth: parseInt(settings.tabHandle.outerWidth(), 10) + 'px',
      tabHeight: parseInt(settings.tabHandle.outerHeight(), 10) + 'px'
    }; //set calculated css

    if (settings.tabLocation === 'top' || settings.tabLocation === 'bottom') {
      obj.css({
        'left': settings.leftPos
      });
      settings.tabHandle.css({
        'right': 0
      });
    }

    if (settings.tabLocation === 'top') {
      obj.css({
        'top': '-' + properties.containerHeight
      });
      settings.tabHandle.css({
        'bottom': '-' + properties.tabHeight
      });
    }

    if (settings.tabLocation === 'bottom') {
      obj.css({
        'bottom': '-' + properties.containerHeight,
        'position': 'fixed'
      });
      settings.tabHandle.css({
        'top': '-' + properties.tabHeight
      });
    }

    if (settings.tabLocation === 'left' || settings.tabLocation === 'right') {
      obj.css({
        'height': properties.containerHeight,
        'top': settings.topPos
      });
      settings.tabHandle.css({
        'top': 0
      });
    }

    if (settings.tabLocation === 'left') {
      obj.css({
        'left': '-' + properties.containerWidth
      });
      settings.tabHandle.css({
        'right': '-' + properties.tabWidth
      });
    }

    if (settings.tabLocation === 'right') {
      obj.css({
        'right': '-' + properties.containerWidth
      });
      settings.tabHandle.css({
        'left': '-' + properties.tabWidth
      });
      $('html').css('overflow-x', 'hidden');
    } //functions for animation events


    settings.tabHandle.click(function (event) {
      event.preventDefault();
    });

    var slideIn = function slideIn() {
      if (settings.tabLocation === 'top') {
        obj.animate({
          top: '-' + properties.containerHeight
        }, settings.speed).removeClass('open');
      } else if (settings.tabLocation === 'left') {
        obj.animate({
          left: '-' + properties.containerWidth
        }, settings.speed).removeClass('open');
      } else if (settings.tabLocation === 'right') {
        obj.animate({
          right: '-' + properties.containerWidth
        }, settings.speed).removeClass('open');
      } else if (settings.tabLocation === 'bottom') {
        obj.animate({
          bottom: '-' + properties.containerHeight
        }, settings.speed).removeClass('open');
      }
    };

    var slideOut = function slideOut() {
      if (settings.tabLocation == 'top') {
        obj.animate({
          top: '0px'
        }, settings.speed).addClass('open');
      } else if (settings.tabLocation == 'left') {
        obj.animate({
          left: '0px'
        }, settings.speed).addClass('open');
      } else if (settings.tabLocation == 'right') {
        obj.animate({
          right: '0px'
        }, settings.speed).addClass('open');
      } else if (settings.tabLocation == 'bottom') {
        obj.animate({
          bottom: '0px'
        }, settings.speed).addClass('open');
      }
    };

    var clickScreenToClose = function clickScreenToClose() {
      obj.click(function (event) {
        event.stopPropagation();
      });
      $(document).click(function () {
        slideIn();
      });
    };

    var clickAction = function clickAction() {
      settings.tabHandle.click(function (event) {
        if (obj.hasClass('open')) {
          slideIn();
        } else {
          slideOut();
        }
      }); //clickScreenToClose(); [snowman] The implementation breaks default cart click handlers. And we do not really need it, so it's temporary disabled
    };

    var hoverAction = function hoverAction() {
      obj.hover(function () {
        slideOut();
      }, function () {
        slideIn();
      });
      settings.tabHandle.click(function (event) {
        if (obj.hasClass('open')) {
          slideIn();
        }
      });
      clickScreenToClose();
    }; //choose which type of action to bind


    if (settings.action === 'click') {
      clickAction();
    }

    if (settings.action === 'hover') {
      hoverAction();
    }
  };
})(jQuery);