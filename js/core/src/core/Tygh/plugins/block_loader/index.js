import { createPlugin } from "../../core_methods";
import $ from "jquery";
import {Tygh} from '../../index'
const _ = Tygh;

function getMultiScripts(arr) {
  var _arr = $.map(arr, function (scr) {
    return $.getScript(scr);
  });

  _arr.push($.Deferred(function (deferred) {
    $(deferred.resolve);
  }));

  return $.when.apply($, _arr);
}

const methods = {
  init() {
    $(this).each(function() {
      var $self = $(this);

      if (!$self.length) {
        return false;
      }
      
      const classes = $self[0].classList;
      let caObjectKey;
      classes.forEach(function(className) {
        if (className.indexOf('cm-block-loader--') === 0) {
          caObjectKey = className.split('--')[1];
        }
      });

      if (caObjectKey === undefined) {
        return;
      }

      const newContext = $(`<div class="cm-block-loader" data-ca-object-key="${caObjectKey}"></div>`);
      $self.after(newContext);
      $self.remove();

      $.ceAjax(
        'request',
        fn_url(`block_manager.render&object_key=${encodeURIComponent(caObjectKey)}&redirect_url=${encodeURIComponent(_.current_url)}`),
        {
          method: 'get',
          callback: methods.processResponse(newContext),
          hidden: true
        }
      );
    });
  },

  processResponse(context) {
    return response => {
      const content = $(response.block_content);
      content.toggleClass('cm-block-loaded');

      let scripts = [];
      content.find('script').each((i, script) => {
        script.src ? scripts.push(script.src) : null;
      });

      getMultiScripts(scripts)
        .done(function () {
          content.find('script[src]').remove();

          $('.cm-block-loaded', $(context)).remove();
          $(context).append(content);

          $.commonInit(context);
        });
    };
  },
};

export const ceBlockLoaderInit = function () {
  createPlugin(
    'ceBlockLoader',
    methods,
    'ce.block_loader',
    true
  )
};
