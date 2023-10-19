(function (_, $) {
  // Proxies event handler to class method
  window.onRecaptchaV2Loaded = function () {
    _.onRecaptchaV2Loaded();
  };

  window.onRecaptchaV3Loaded = function () {
    _.onRecaptchaV3Loaded();
  };

  var pluginName = "ceRecaptcha";
  $.extend(_, {
    // A flag indicating the Recaptcha library is ready to use
    recaptchaV2Loaded: false,
    recaptchaV3Loaded: false,
    recaptchaV3Token: '',
    // Stores jQuery object instances that required Recaptcha to be applied before Recaptcha was loaded
    recaptchaV2InitQueue: [],
    // Callback triggered by Recaptcha "onload" event
    onRecaptchaV2Loaded: function onRecaptchaV2Loaded() {
      this.recaptchaV2Loaded = true;

      if (this.recaptchaV2InitQueue.length) {
        $.each(this.recaptchaV2InitQueue, function (a, b) {
          $(this).ceRecaptcha();
        });
      }
    },
    onRecaptchaV3Loaded: function onRecaptchaV3Loaded() {
      this.recaptchaV3Loaded = true;
      grecaptcha.execute(_.google_recaptcha_v3_site_key, {
        action: 'google_recaptcha'
      }).then(function (token) {
        if (token) {
          $('.cm-recaptcha-v3').val(token);
          _.recaptchaV3Token = token;
          var req = {};
          req[_.google_recaptcha_v3_token_param] = token;
          req['validate_token'] = true;
          $.ceAjax('request', fn_url('antibot.valid_recaptcha'), {
            method: 'POST',
            caching: false,
            hidden: true,
            data: req
          });
        }
      });
    }
  });
  $.ceEvent('on', 'ce.commoninit', function (context) {
    recaptchaInit(context);
  });
  $.ceEvent('on', 'ce.gdpr_cookie_init', function (context) {
    recaptchaInit(context);
  }); // jQuery plugin constructor

  function ReCaptcha(element, options) {
    this.$el = $(element);
    this.$input = null;
    this.settings = $.extend({}, _.recaptcha_settings, options);
    this.grecaptcha = null;
    this.isValidationPassed = null;
  }

  $.extend(ReCaptcha.prototype, {
    init: function init(grecaptcha) {
      this.grecaptcha = grecaptcha;
      this.isValidationPassed = false;
      this.render();
    },
    render: function render() {
      var self = this;
      grecaptcha.render(this.$el[0], {
        sitekey: this.settings.site_key,
        theme: this.settings.theme,
        size: this.settings.size,
        callback: function callback(response) {
          self.isValidationPassed = true;
          $.ceEvent('trigger', 'ce.image_verification.passed', [response, self.$input]);
        },
        'expired-callback': function expiredCallback() {
          self.isValidationPassed = false;
          $.ceEvent('trigger', 'ce.image_verification.failed', [self.$input]);
        }
      });
      this.$input = this.$el.find('[name="' + _.google_recaptcha_v2_token_param + '"]');
      $.ceEvent('trigger', 'ce.image_verification.ready', [_.google_recaptcha_v2_token_param, this.$input]);

      if ($.ceDialog('inside_dialog', {
        jelm: this.$input
      })) {
        $.ceDialog('reload_parent', {
          jelm: this.$input
        });
      }
    },
    checkIsValidationPassed: function checkIsValidationPassed() {
      return this.isValidationPassed;
    }
  }); // Register jQuery plugin

  $.fn[pluginName] = function (options) {
    var self = this,
        createPluginInstances = function createPluginInstances() {
      return self.each(function () {
        var recaptcha,
            $el = $(this),
            el_id = $el.attr('id');

        if (!el_id) {
          return;
        }

        if (_.recaptchaV2Loaded) {
          if (!$.data(this, "plugin_" + pluginName)) {
            recaptcha = new ReCaptcha(this, options);
            recaptcha.init(window.grecaptcha);
            $.data(this, "plugin_" + pluginName, recaptcha);
          }
        } else {
          _.recaptchaV2InitQueue.push($el);
        }
      });
    };

    if (this.length) {
      return createPluginInstances();
    }

    return this;
  };

  function recaptchaInit(context) {
    var recaptchaV3 = $(context).find('.cm-recaptcha-v3');
    var recaptchaV2 = $(context).find('.cm-recaptcha');

    if (recaptchaV3.length > 0) {
      if (_.recaptchaV3Loaded === false) {
        $.getScript('https://www.google.com/recaptcha/api.js?onload=onRecaptchaV3Loaded&render=' + _.google_recaptcha_v3_site_key);
      } else {
        $(recaptchaV3).val(_.recaptchaV3Token);
      }
    }

    if (recaptchaV2.length > 0) {
      if (_.recaptchaV2Loaded === false) {
        $.getScript('https://www.google.com/recaptcha/api.js?onload=onRecaptchaV2Loaded&render=explicit');
      } // Register custom Recaptcha form validator.
      // In order to validation work, Recaptcha container DOM element must have:
      // * an 'id' HTML attribute set;
      // * an associated 'label' tag with 'for' attribute set pointing to Recaptcha container and 'cm-recaptcha cm-required' classes set;


      $.ceFormValidator('registerValidator', {
        class_name: 'cm-recaptcha',
        'func': function func(recaptcha_container_id, $container, $label) {
          var recaptcha = $.data($container[0], "plugin_" + pluginName);

          if (recaptcha instanceof ReCaptcha) {
            return recaptcha.checkIsValidationPassed();
          }

          return true;
        },
        message: _.tr('error_validator_recaptcha')
      });
      $('.cm-recaptcha:not(label)', context).ceRecaptcha();
    }
  }
})(Tygh, Tygh.$);