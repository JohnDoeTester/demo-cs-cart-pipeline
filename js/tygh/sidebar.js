function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

/*
 * Sidebar
 *
 */
(function ($) {
  var sidebars = [];
  var methods = {
    init: function init() {
      var $self = $(this);
      $self.find('.sidebar-toggle').on('click', function () {
        methods._toggle($self);
      });

      methods._resize($self);

      sidebars.push($self);

      if ($self.hasClass('cm-sidebar-open-state-save') && methods._getCookie()) {
        methods._open($self);
      }
    },
    resize: function resize() {
      return methods._resize(this);
    },
    toggle: function toggle() {
      $(this).toggleClass('sidebar-open');
    },
    open: function open() {
      methods._open(this);
    },
    close: function close() {
      if (methods._is_open(this)) {
        methods._setOpenCookie(false);

        $(this).removeClass('sidebar-open');
      }
    },
    is_open: function is_open() {
      return methods._is_open(this);
    },
    _open: function _open(elem) {
      if (!methods._is_open(elem)) {
        methods._setOpenCookie(true);

        $(elem).addClass('sidebar-open');
      }
    },
    _setOpenCookie: function _setOpenCookie(isOpen) {
      $.cookie.set('sb_is_sidebar_open', isOpen);
    },
    _getCookie: function _getCookie() {
      var sidebarOpenCookie = $.cookie.get('sb_is_sidebar_open') === 'true';
      return sidebarOpenCookie;
    },
    _toggle: function _toggle(elem) {
      $(elem).toggleClass('sidebar-open');

      methods._setOpenCookie(methods._is_open(elem));
    },
    _resize: function _resize(elem) {
      $(elem).css({
        "top": $('#actions_panel').height() + 'px'
      });
    },
    _is_open: function _is_open(elem) {
      return $(elem).hasClass('sidebar-open');
    }
  };

  $.fn.ceSidebar = function (method) {
    if (methods[method]) {
      return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
    } else if (_typeof(method) === 'object' || !method) {
      return methods.init.apply(this, arguments);
    } else {
      $.error('ty.sidebar: method ' + method + ' does not exist');
    }
  };

  $(window).on('resize', function (e) {
    for (var i in sidebars) {
      methods._resize(sidebars[i]);
    }
  });
})(Tygh.$);