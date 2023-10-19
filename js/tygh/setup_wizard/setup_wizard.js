(function (_, $) {
  var loading_form;
  var tab_slide_out_speed = 150;
  $._defaultToggleStatusBox = $.toggleStatusBox;
  $.toggleStatusBox = function (toggle) {
    if (loading_form) {
      $('button[type=submit].ladda-button', $('form[name=' + loading_form + ']')).each(function () {
        var l = Ladda.create(this);
        l.start();
      });
    } else {
      $._defaultToggleStatusBox.apply(this, arguments);
    }
  }, function ($) {
    var methods = {
      swShowAll: function swShowAll(notifications) {
        var notifications_box = $('.sw-notifications-box', $('form[name=' + loading_form + ']'));
        var delay = 3000;

        if (notifications_box.length) {
          notifications_box.hide();
          notifications_box.html('');
        }

        $.each(notifications, function (k, v) {
          if (v.type == 'I') {
            $._defaultCeNotification('show', v);
          } else {
            notifications_box.append('<span class="sw-notification-' + v.type + '">' + v.message + '</span>');
          }
        });

        if (notifications_box.length) {
          notifications_box.fadeIn();
          setTimeout(function () {
            notifications_box.fadeOut();
          }, delay);
        }
      }
    };
    $._defaultCeNotification = $.ceNotification;

    $.ceNotification = function (method) {
      if (loading_form) {
        if (methods[method]) {
          return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        }
      } else {
        return $._defaultCeNotification.apply(this, arguments);
      }
    };
  }($); // Override LiquidSlider functions

  LiquidSlider.makeResponsive = function () {
    var _this = this; // Adjust widths and add classes to make responsive


    jQuery(_this.sliderId + '-wrapper').addClass('ls-responsive').css({
      'max-width': jQuery(_this.sliderId + ' .panel:first-child').width(),
      'width': '100%'
    }); // Update widths

    jQuery(_this.sliderId + ' .panel-container').css('width', 100 * _this.panelCountTotal + _this.pSign);
    jQuery(_this.sliderId + ' .panel').css({
      'width': 100 / _this.panelCountTotal + _this.pSign,
      'min-width': 100 / _this.panelCountTotal + _this.pSign
    }); // convert to pixels

    jQuery(_this.sliderId + ' .panel-container').css('width', jQuery(_this.sliderId + ' .panel-container').outerWidth(true));
    jQuery(_this.sliderId + ' .panel').css('width', jQuery(_this.sliderId + ' .panel').outerWidth(true)); // Cache the padding for add/removing arrows

    if (_this.options.hideArrowsWhenMobile) {
      _this.leftWrapperPadding = jQuery(_this.sliderId + '-wrapper').css('padding-left');
      _this.rightWrapperPadding = _this.$sliderWrap.css('padding-right');
    } // Set events and fire on browser resize


    _this.responsiveEvents();

    jQuery(window).bind('resize orientationchange', function () {
      _this.responsiveEvents();

      clearTimeout(_this.resizingTimeout);
      _this.resizingTimeout = setTimeout(function () {
        _this.readjust();
      });
    });
  };

  LiquidSlider.readjust = function () {
    var height = this.options.autoHeight ? this.getHeight() : this.getHeighestPanel(this.nextPanel);
    this.adjustHeight(false, height); // convert to pixels

    jQuery(this.sliderId + ' .panel-container').css('width', 100 * this.panelCountTotal + this.pSign);
    jQuery(this.sliderId + ' .panel').css('width', jQuery(this.sliderId + ' .panel').outerWidth(true));
  }; // RTL slide support


  LiquidSlider.getTransitionMargin = function () {
    var _this = this;

    return (Tygh.language_direction !== 'rtl' || -1) * (-(_this.nextPanel * _this.slideDistance) - _this.slideDistance * ~~_this.options.continuous);
  };

  $.ceEvent('on', 'ce.ajaxlink.done.setupwizard', function () {
    var elm = $('.setup-wizard-panel');
    $('#setup-wizard-main-slider').liquidSlider({
      continuous: false,
      hideArrowsWhenMobile: false
    });
    var liquidSlider = $('#setup-wizard-main-slider').data('liquidSlider');
    $('#setup-wizard-main-slider-nav-ul').children().each(function (i, li) {
      $(li).addClass($(liquidSlider.$panelClass[i]).prop('id'));
    });
    $('.setup-wizard-content').on('click', 'button[type=submit].ladda-button', function () {
      loading_form = $(this).parents('form').prop('name');
      $.ceEvent('one', 'ce.formajaxpost_' + loading_form, function (data) {
        $.ceNotification('swShowAll', data.notifications);
        setTimeout(function () {
          Ladda.stopAll();
          loading_form = null;
        });
      });
    });
    $('#setup-wizard-main-slider').on('click', '.cm-combination', function () {
      liquidSlider.readjust();
    }); // Tabs

    $('#setup-wizard-main-slider').on('click', '.cm-sw-tabs a', function () {
      var self = $(this);
      var ul = self.parents('ul');
      var container = self.parents('.cm-sw-tabs');
      $('li', ul).removeClass('active');
      $('.cm-sw-tab-contents', container).hide();
      $('#' + self.data('caTargetId')).removeClass('hidden');
      $('#' + self.data('caTargetId')).show();
      self.parent('li').addClass('active');
    });
    $('.setup-wizard-content').on('click touchstart', '.cm-sw-light-bg', function () {
      $(this).parent().removeClass('sw-dark-bg').addClass('sw-light-bg');
    });
    $('.setup-wizard-content').on('click touchstart', '.cm-sw-dark-bg', function () {
      $(this).parent().removeClass('sw-light-bg').addClass('sw-dark-bg');
    });
    $('.setup-wizard-content').on('click', '.cm-sw-double-confirm', function (e) {
      var self = $(this);
      var params = $.ceDialog('get_params', self);

      if (self.data('caDoubleConfirmed')) {
        self.data('caDoubleConfirmed', null);
        return true;
      }

      var confirm_dialog = $('#' + self.data('caTargetId'));
      confirm_dialog.off('click').on('click', '.cm-sw-second-confirm', function (e) {
        e.stopPropagation();

        if (confirm(_.tr('text_are_you_sure_to_proceed'))) {
          self.data('caDoubleConfirmed', true);
          confirm_dialog.ceDialog('close');
          self.click();
        }

        return false;
      });
      confirm_dialog.ceDialog('open', params);
      return false;
    });
    $('.setup-wizard-panel').find('.handle.open').removeClass('cm-ajax').removeAttr('href');
    $('#sw_wizard_subcontainer').removeClass('hidden');
    $('#setup-wizard-main-slider').data('liquidSlider').readjust();
  });
  $(document).ready(function () {
    var elm = $('.setup-wizard-panel');
    elm.appendTo($('body'));
    elm.tabSlideOut({
      tabHandle: '.handle',
      imageHeight: '122px',
      imageWidth: '140px',
      tabLocation: 'top',
      speed: tab_slide_out_speed,
      action: 'click',
      topPos: '0px',
      fixedPosition: false
    });
    elm.on('click', '.handle', function () {
      if (elm.hasClass('open')) {
        $('#main_column').css({
          'max-height': '100%',
          'overflow': 'hidden'
        }); // Add events

        $(_.doc).on('keydown.setupWizard', function (e) {
          if (!elm.hasClass('open')) {
            return;
          } // Close the popup by pressing the Esc key.


          if (e.keyCode === 27) {
            $('.setup-wizard-panel .handle.close').click();
          }
        });
      } else {
        $('#main_column').css({
          'max-height': 'none',
          'overflow': 'initial'
        }); // Remove events

        $(_.doc).off('keydown.setupWizard');
      }
    });
  });
})(Tygh, Tygh.$);