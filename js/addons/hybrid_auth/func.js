(function (_, $) {
  $(document).on('click', '.cm-login-provider,.cm-link-provider', function (e) {
    var jelm = $(e.target);
    var login_provider = false;
    var link_provider = false;
    var url = "";
    var is_facebook_embedded_browser = /fbav/gi.test(window.navigator.userAgent);

    if (jelm.hasClass('cm-login-provider') || jelm.parents('.cm-login-provider').length > 0) {
      login_provider = true;
    }

    if (jelm.hasClass('cm-link-provider') || jelm.parents('.cm-link-provider').length > 0) {
      link_provider = true;
    }

    if (login_provider && !jelm.hasClass('cm-login-provider')) {
      jelm = jelm.closest('.cm-login-provider');
    } else if (link_provider && !jelm.hasClass('cm-link-provider')) {
      jelm = jelm.closest('.cm-link-provider');
    }

    var idp = jelm.data('idp');
    var provider = jelm.data('provider');
    var open_id = false;

    switch (provider) {
      case "wordpress":
      case "blogger":
      case "flickr":
      case "livejournal":
        var open_id = true;

        if (provider == "blogger") {
          var un = prompt("Please enter your blog name");
        } else {
          var un = prompt("Please enter your username");
        }

        break;

      case "openid":
        var open_id = true;
        var un = prompt("Please enter your OpenID URL");
    }

    if (!open_id) {
      if (login_provider) {
        url = 'auth.login_provider?provider_id=' + idp + '&redirect_url=' + encodeURIComponent($('input[name=redirect_url]').val()) + '&_ts=' + new Date().getTime();
      } else {
        url = 'profiles.link_provider?provider_id=' + idp + '&_ts=' + new Date().getTime();
      }
    } else {
      var oi = un;

      if (!un) {
        return false;
      }

      switch (provider) {
        case "wordpress":
          oi = "http://" + un + ".wordpress.com";
          break;

        case "livejournal":
          oi = "http://" + un + ".livejournal.com";
          break;

        case "blogger":
          oi = "http://" + un + ".blogspot.com";
          break;

        case "flickr":
          oi = "http://www.flickr.com/photos/" + un + "/";
          break;
      }

      if (login_provider) {
        url = 'auth.login_provider?provider_id=' + idp + '&_ts=' + new Date().getTime() + '&openid_identifier=' + encodeURIComponent(oi);
      } else {
        url = 'profiles.link_provider?provider_id=' + idp + '&_ts=' + new Date().getTime() + '&openid_identifier=' + encodeURIComponent(oi);
      }
    }

    if (_.embedded) {
      url += '&embedded=true';
    }

    if (is_facebook_embedded_browser) {
      window.location.href = fn_url(url);
    } else {
      window.open(fn_url(url), "hybridauth_social_sing_on", "location=0,status=0,scrollbars=0,width=800,height=500");
    }
  });
  $(document).on('click', '.cm-unlink-provider', function (e) {
    var jelm = $(e.target);

    if (!jelm.hasClass('cm-unlink-provider')) {
      jelm = jelm.closest('.cm-unlink-provider');
    }

    if (confirm(_.tr('text_are_you_sure_to_proceed'))) {
      var idp = jelm.data('idp');
      $.ceAjax('request', fn_url('profiles.unlink_provider?provider_id=' + idp), {
        method: 'post',
        result_ids: 'hybrid_providers'
      });
    }
  });
  $(document).on('change', '.cm-select-provider', function (e) {
    var jelm = $(e.target),
        option = $('option:selected', jelm),
        provider = option.data('provider'),
        id = option.data('id');
    $.ceAjax('request', fn_url('hybrid_auth.select_provider?provider=' + provider + '&id=' + id), {
      method: 'get',
      result_ids: 'content_keys_' + id + ',content_params_' + id + ',content_tab_callback_urls_' + id
    });
  });
})(Tygh, Tygh.$);