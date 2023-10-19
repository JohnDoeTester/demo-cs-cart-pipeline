(function (_, $) {
  var regex_all = new RegExp("<script[^>\xA7]*>([\x01-\uFFFF]*?)</script>", 'img');
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $elems = $('.cm-gdpr-agreement-label', context);

    if (!$elems.length) {
      return;
    }

    $elems.each(function () {
      var $agreementLabel = $(this);
      var $block = $agreementLabel.closest('[data-ca-gdpr-agreement]');
      var $targetElem = $($agreementLabel.data('caGdprTargetElem'));

      if (!$targetElem.length || $targetElem.data('caIsInitedGdprTargetElem')) {
        return;
      }

      $targetElem.on('focus', function () {
        $block.removeClass('hidden');
      });
      $targetElem.data('caIsInitedGdprTargetElem', true);
    });
  });
  $.ceEvent('on', 'ce.ajaxdone', function (elms, scripts, params, data, responseText) {
    loadConsentsScriptFromAjax(data);
  });
  $('document').ready(function () {
    if (typeof cookieConfig !== 'undefined' && cookieConfig.services.length) {
      cookieConfig.callback = function (consent, service) {
        $.ceEvent('trigger', 'ce.gdpr_cookie_global_init', [consent, service]);
      };

      cookieConfig.services.forEach(function (serviceItem) {
        serviceItem.callback = function (consent, service) {
          $.ceEvent('trigger', 'ce.gdpr_cookie_init_' + service.name, [consent, service]);
        };

        serviceItem.onAccept = function (handlerOpts) {
          $.ceEvent('trigger', 'ce.gdpr_cookie_on_accept_' + handlerOpts.service.name, [handlerOpts]);
        };

        serviceItem.onDecline = function (handlerOpts) {
          $.ceEvent('trigger', 'ce.gdpr_cookie_on_decline_' + handlerOpts.service.name, [handlerOpts]);
        };
      });
      var normilizedConfig = normalizeTranslations(cookieConfig);
      klaro.setup(normilizedConfig);
      setTimeout(function () {
        return $.ceEvent('trigger', 'ce.gdpr_cookie_init', [$(_.doc)]);
      }, 1000);
    }
  });

  var normalizeTranslations = function normalizeTranslations(config) {
    config.translations.zz = addLangvar(config.translations.zz);
    config.services.forEach(function (service) {
      service.translations.zz = addLangvar(service.translations.zz);
    });
    return config;
  };

  var addLangvar = function addLangvar(translationSchema) {
    for (var key in translationSchema) {
      if (key === 'privacyPolicyUrl') {
        continue;
      }

      if (typeof translationSchema[key] === 'string') {
        translationSchema[key] = _.tr(translationSchema[key]);
      } else {
        translationSchema[key] = addLangvar(translationSchema[key]);
      }
    }

    return translationSchema;
  };

  function loadConsentsScriptFromAjax(data) {
    if (!data.html) {
      return;
    }

    for (var k in data.html) {
      matches = data.html[k].match(regex_all);

      if (matches === null || !matches.length) {
        continue;
      }

      ext_scripts = $(matches.join('\n')).filter('.cm-ajax-skip-load[type="text/plain"][data-type="application/javascript"][data-src]');

      if (!ext_scripts.length) {
        continue;
      }

      for (var i = 0; i < ext_scripts.length; i++) {
        if (!klaro.getManager().states[ext_scripts.eq(i).data('name')]) {
          continue;
        }

        $.getScript(ext_scripts.eq(i).prop('src'));
      }
    }
  }
})(Tygh, Tygh.$);