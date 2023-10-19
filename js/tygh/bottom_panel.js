function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

!function (t) {
  var e = {};

  function o(n) {
    if (e[n]) return e[n].exports;
    var s = e[n] = {
      i: n,
      l: !1,
      exports: {}
    };
    return t[n].call(s.exports, s, s.exports, o), s.l = !0, s.exports;
  }

  o.m = t, o.c = e, o.d = function (t, e, n) {
    o.o(t, e) || Object.defineProperty(t, e, {
      enumerable: !0,
      get: n
    });
  }, o.r = function (t) {
    "undefined" != typeof Symbol && Symbol.toStringTag && Object.defineProperty(t, Symbol.toStringTag, {
      value: "Module"
    }), Object.defineProperty(t, "__esModule", {
      value: !0
    });
  }, o.t = function (t, e) {
    if (1 & e && (t = o(t)), 8 & e) return t;
    if (4 & e && "object" == _typeof(t) && t && t.__esModule) return t;
    var n = Object.create(null);
    if (o.r(n), Object.defineProperty(n, "default", {
      enumerable: !0,
      value: t
    }), 2 & e && "string" != typeof t) for (var s in t) {
      o.d(n, s, function (e) {
        return t[e];
      }.bind(null, s));
    }
    return n;
  }, o.n = function (t) {
    var e = t && t.__esModule ? function () {
      return t.default;
    } : function () {
      return t;
    };
    return o.d(e, "a", e), e;
  }, o.o = function (t, e) {
    return Object.prototype.hasOwnProperty.call(t, e);
  }, o.p = "", o(o.s = 29);
}({
  29: function (_2) {
    function _(_x, _x2, _x3) {
      return _2.apply(this, arguments);
    }

    _.toString = function () {
      return _2.toString();
    };

    return _;
  }(function (t, e, o) {
    o.r(e);

    var n,
        s = o(3),
        i = {
      bottomPanelSelector: "#bp_bottom_panel",
      offBottomPanelSelector: "#bp_off_bottom_panel",
      bottomButtonsContainerSelector: "#bp_bottom_buttons",
      bottomButtonsSelector: "[data-bp-bottom-buttons]",
      bottomButtonsActiveClass: "bp-bottom-buttons--active",
      bottomButtonDisabledClass: "bp-bottom-button--disabled",
      onBottomPanelSelector: "#bp_on_bottom_panel",
      navItemSpecificSelector: '[data-bp-nav-item="{placeholder}"]',
      navItemSelector: "[data-bp-nav-item]",
      navItemActiveClass: "bp-nav__item--active",
      navActiveSelector: "#bp-nav__active",
      navActiveActivatedClass: "bp-nav__active--activated",
      modesItemSpecificSelector: '[data-bp-modes-item="{placeholder}"]',
      modesItemSelector: "[data-bp-modes-item]",
      modesItemNotDisabledSelector: "[data-bp-modes-item]:not(.bp-modes__item--disabled)",
      modesItemActiveClass: "bp-modes__item--active",
      modesActiveSelector: "#bp-modes__active",
      modesActiveClass: "bp-modes__active--{placeholder}",
      modesActiveClasses: ["bp-modes__active--preview", "bp-modes__active--build", "bp-modes__active--text", "bp-modes__active--theme"],
      dropdownSelector: '[data-bp-toggle="dropdown"]',
      dropdownMenuClass: "bp-dropdown-menu",
      dropdownMenuOpenClass: "bp-dropdown-menu--open",
      dropdownMenuItemClass: "bp-dropdown-menu__item",
      htmlSelector: "html",
      htmlActiveClass: "bp-panel-active"
    },
        a = {
      html: {},
      isAdminPanel: !0,
      bottomPanel: {},
      bottomButtonsContainer: {},
      mode: "default",
      isBottomPanelOpen: !0,
      navActive: "customer",
      modesActive: "preview",
      bottomButtons: [],
      dropdowns: [],
      nav: [],
      modes: []
    },
        c = function c() {
      a.html.addClass(i.htmlActiveClass);
    },
        l = function l() {
      a.html.removeClass(i.htmlActiveClass);
    },
        d = function d() {
      $(a.bottomButtonsContainer).addClass(i.bottomButtonsActiveClass), $(a.bottomButtons).each(function () {
        $(this).removeClass(i.bottomButtonDisabledClass + " " + i.bottomButtonDisabledClass + "-" + $(this).data("bpBottomButtons"));
      });
    },
        r = function r() {
      $(a.bottomButtonsContainer).removeClass(i.bottomButtonsActiveClass), $(a.bottomButtons).each(function () {
        $(this).addClass(i.bottomButtonDisabledClass + " " + i.bottomButtonDisabledClass + "-" + $(this).data("bpBottomButtons"));
      });
    },
        m = {
      _activate: function _activate() {
        a.isBottomPanelOpen = !0, c(), r(), m._setOpenCookie(!0);
      },
      _deactivate: function _deactivate() {
        a.isBottomPanelOpen = !1, l(), d(), m._setOpenCookie(!1);
      },
      _setOpenCookie: function _setOpenCookie(t) {
        $.cookie.set("pb_is_bottom_panel_open", +t);
      },
      _getCookie: function _getCookie() {
        var t = $.cookie.get("pb_is_bottom_panel_open");
        a.isBottomPanelOpen = t || !0;
      },
      _addActivateListeners: function _addActivateListeners() {
        $(Tygh.doc).on("click", i.onBottomPanelSelector, function () {
          return m._activate();
        });
      },
      _addDeactivateListeners: function _addDeactivateListeners() {
        $(Tygh.doc).on("click", i.offBottomPanelSelector, function () {
          return m._deactivate();
        });
      }
    },
        u = {
      _setActive: function _setActive(t) {
        t && (a.navActive = t.data("bpNavItem")), $(a.bottomPanel).data("navActive", a.navActive), u._setWidth(), u._setPosition(), u._setClass(t);
      },
      _getNav: function _getNav() {
        $(a.bottomPanel).find(i.navItemSelector).each(function () {
          a.nav.push($(this));
        });
      },
      _setWidth: function _setWidth() {
        $(i.navActiveSelector).width($(a.bottomPanel).find(i.navItemSpecificSelector.replace("{placeholder}", a.navActive)).outerWidth());
      },
      _setPosition: function _setPosition() {
        var t = $(a.bottomPanel).find(i.navItemSpecificSelector.replace("{placeholder}", a.navActive)),
            e = $(t).position().left;
        "rtl" === Tygh.language_direction && a.nav.length > 0 && 0 != (e = -Math.ceil($(a.nav[a.nav.length - 1 - $(t).index()]).position().left)) && (e += $(t).outerWidth() - $(t).width()), $(i.navActiveSelector).css("transform", "translate(" + e + "px)");
      },
      _setClass: function _setClass(t) {
        $(i.navActiveSelector).addClass(i.navActiveActivatedClass), t && ($(a.nav).each(function () {
          $(this).removeClass(i.navItemActiveClass);
        }), $(t).addClass(i.navItemActiveClass));
      },
      _addSetActiveListeners: function _addSetActiveListeners() {
        $(Tygh.doc).on("click", i.navItemSelector, function (t) {
          return u._setActive($(this));
        });
      }
    },
        p = {
      _setActive: function _setActive(t) {
        t && (a.modesActive = t.data("bpModesItem")), $(a.bottomPanel).data("modesActive", a.modesActive), p._setPosition(), p._setClass(t);
      },
      _getButtons: function _getButtons() {
        $(a.bottomPanel).find(i.modesItemSelector).each(function () {
          a.modes.push($(this));
        });
      },
      _setPosition: function _setPosition() {
        var t = $(a.bottomPanel).find(i.modesItemSpecificSelector.replace("{placeholder}", a.modesActive)).position().left;
        "rtl" === Tygh.language_direction && a.modes.length > 0 && (t -= $(a.modes[0]).position().left), $(i.modesActiveSelector).css("transform", "translate(" + t + "px)");
      },
      _setClass: function _setClass(t) {
        $(i.modesActiveSelector).removeClass(i.modesActiveClasses.join(" ")).addClass(i.modesActiveClass.replace("{placeholder}", a.modesActive)), t && ($(a.modes).each(function () {
          $(this).removeClass(i.modesItemActiveClass);
        }), $(t).addClass(i.modesItemActiveClass));
      },
      _addSetActiveListeners: function _addSetActiveListeners() {
        $(Tygh.doc).on("click", i.modesItemNotDisabledSelector, function (t) {
          return p._setActive($(this));
        });
      }
    },
        v = function v() {
      $(a.bottomPanel).find(i.dropdownSelector).each(function () {
        a.dropdowns.push($(this).parent()), $(this).on("click", function () {
          var t = $(this);
          $(a.dropdowns).each(function () {
            $(this)[0] !== t.parent()[0] && $(this).children("div").removeClass(i.dropdownMenuOpenClass);
          }), $(this).parent().children("div").toggleClass(i.dropdownMenuOpenClass);
        }), $(this).on("focusout", function (t) {
          $(t.relatedTarget).length && $(t.relatedTarget).hasClass(i.dropdownMenuItemClass) || $(a.dropdowns).each(function () {
            $(this).children("div").removeClass(i.dropdownMenuOpenClass);
          });
        }), $(Tygh.doc).on("click", "." + i.dropdownMenuItemClass, function () {
          $(a.dropdowns).each(function () {
            $(this).children("." + i.dropdownMenuClass).removeClass(i.dropdownMenuOpenClass);
          });
        });
      });
    },
        b = {
      init: function init() {
        n || (a.html = $(i.htmlSelector), a.bottomPanel = $(i.bottomPanelSelector), a.bottomButtonsContainer = $(i.bottomButtonsContainerSelector), a.mode = a.bottomPanel.data("bpMode"), a.isBottomPanelOpen = a.bottomPanel.data("bpIsBottomPanelOpen"), a.navActive = a.bottomPanel.data("bpNavActive"), a.modesActive = a.bottomPanel.data("bpModesActive"), a.bottomButtons = a.bottomButtonsContainer.find(i.bottomButtonsSelector), a.dropdowns = [], a.modes = [], m._getCookie(), m._addActivateListeners(), m._addDeactivateListeners(), u._getNav(), u._setActive(), u._addSetActiveListeners(), v(), $(a.bottomPanel).find(i.modesItemSelector).length && (p._getButtons(), p._setActive(), p._addSetActiveListeners()), n = !0);
      }
    };

    o.d(e, "methods", function () {
      return f;
    });
    var f = {
      init: b.init,
      defaults: i
    };
    $.fn.ceBottomPanel = function (t) {
      return f[t] ? f[t].apply(this, Array.prototype.slice.call(arguments, 1)) : "object" !== Object(s.a)(t) && t ? void $.error("ty.bottom_panel: method " + t + " does not exist") : f.init.apply(this, arguments);
    }, $.ceEvent("one", "ce.commoninit", function (t) {
      t = $(t || _.doc);
      var e = $("[data-ca-bottom-pannel]", t);
      e.length && e.ceBottomPanel();
    });
  }),
  3: function _(t, e, o) {
    function n(t) {
      return (n = "function" == typeof Symbol && "symbol" == _typeof(Symbol.iterator) ? function (t) {
        return _typeof(t);
      } : function (t) {
        return t && "function" == typeof Symbol && t.constructor === Symbol && t !== Symbol.prototype ? "symbol" : _typeof(t);
      })(t);
    }

    function s(t) {
      return (s = "function" == typeof Symbol && "symbol" === n(Symbol.iterator) ? function (t) {
        return n(t);
      } : function (t) {
        return t && "function" == typeof Symbol && t.constructor === Symbol && t !== Symbol.prototype ? "symbol" : n(t);
      })(t);
    }

    o.d(e, "a", function () {
      return s;
    });
  }
});