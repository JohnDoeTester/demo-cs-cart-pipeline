(function (_, $) {
  (function ($) {
    /**
     * Creates mini-browser to load sign up process in.
     *
     * @param {string} url    Url to load in the window
     * @param {string} name   Window name
     * @param {int}    width  Window width
     * @param {int}    height Window height
     *
     * @returns {Window}
     */
    function create_mini_browser(url, name, width, height) {
      // Fixes dual-screen position                         Most browsers      Firefox
      var dual_scr_left = window.screenLeft != undefined ? window.screenLeft : screen.left;
      var dual_scr_top = window.screenTop != undefined ? window.screenTop : screen.top;
      var scr_width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
      var scr_height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;
      var left = scr_width / 2 - width / 2 + dual_scr_left;
      var top = scr_height / 2 - height / 2 + dual_scr_top;
      var new_window = window.open(url, name, 'scrollbars=yes, width=' + width + ', height=' + height + ', top=' + top + ', left=' + left); // Puts focus on the newWindow

      if (window.focus) {
        new_window.focus();
      }

      return new_window;
    }
    /**
     * Shows/hides payment configuration form.
     *
     * @param {*|jQuery|HTMLElement} dialog Dialog container
     * @param {boolean} state If set to true, form will be shown, hidden otherwise.
     */


    function toggle_payment_form(dialog, state) {
      dialog.ceDialog(state ? 'open' : 'close');
    }

    var methods = {
      init: function init(elm) {
        var dialog = $.ceDialog('get_last');
        toggle_payment_form(dialog, false);
        var mini_browser = create_mini_browser('', 'PPFrame', 450, 600);
        var ping = setInterval(function () {
          if (mini_browser.closed) {
            clearInterval(ping);
            toggle_payment_form(dialog, true);
          }
        }, 500);
        $(elm).data('clicked', false);
      }
    };
    $.extend({
      ceConnectToPaypal: function ceConnectToPaypal(method) {
        if (methods[method]) {
          return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else {
          $.error('ty.ceConnectToPaypal: method ' + method + ' does not exist');
        }
      }
    });
  })($);

  $(document).ready(function () {
    $(_.doc).on('click', '.btn-connect-to-paypal', function (e) {
      $.ceConnectToPaypal('init', e.target);
    });
  });
})(Tygh, Tygh.$);