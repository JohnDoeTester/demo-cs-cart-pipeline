(function (_, $) {
  /**
   * Class TextToggle provides the means to change the text input element contents based on the state of the checkbox element.
   *
   * @param {jQuery} $elem Element to bind an toggler to
   * @constructor
   */
  var TextToggle = function TextToggle($elem) {
    this.ON_ENABLE = 'onEnable';
    this.ON_DISABLE = 'onDisable';
    this.$elem = $elem;
    this.$target_elem = $('#' + $elem.data('caToggleTextTargetElemId'));
    this.separator = $elem.data('caToggleTextSeparator') || ' ';
    this.mode = $elem.data('caToggleTextMode') || this.ON_ENABLE;
    this.text = this.splitText($elem.data('caToggleText'));
  };

  $.extend(TextToggle.prototype, {
    /**
     * Bootstraps toogler functionality.
     */
    init: function init() {
      this.bindEvents();
    },

    /**
     * Binds event handlers to the toggler and the connected element.
     */
    bindEvents: function bindEvents() {
      var self = this;
      this.$elem.on('change', function (event) {
        self.onChangeToggleState(self.$elem.is(':checked'));
      });
      this.$target_elem.on('change', function (event) {
        self.onChangeTargetElementContent(self.$target_elem.val());
      });
      this.$target_elem.on('keyup', function (event) {
        self.onChangeTargetElementContent(self.$target_elem.val());
      });
    },

    /**
     * Checks if all items of one array are present in the another.
     *
     * @param {Array} haystack Array to search in
     * @param {Array} needles Array to search for
     * @returns {boolean}
     */
    containsText: function containsText(haystack, needles) {
      var result = true;
      needles.forEach(function (part) {
        result = result && haystack.indexOf(part) !== -1;
      });
      return result;
    },

    /**
     * Adds selected words to the target element value.
     *
     * @param {String[]} text_parts
     */
    addTextToTarget: function addTextToTarget(text_parts) {
      this.text.forEach(function (part) {
        text_parts.push(part);
      });
      this.setTargetText(text_parts);
    },

    /**
     * Removes specified words from the target element value.
     *
     * @param {String[]} text_parts
     */
    removeTextFromTarget: function removeTextFromTarget(text_parts) {
      var self = this;
      text_parts = text_parts.filter(function (part) {
        return self.text.indexOf(part) === -1;
      });
      this.setTargetText(text_parts);
    },

    /**
     * Sets target element value.
     *
     * @param {String[]} text_parts
     */
    setTargetText: function setTargetText(text_parts) {
      this.$target_elem.val(text_parts.join(this.separator));
    },

    /**
     * Sets target element value on the toggler state change.
     *
     * @param {boolean} value
     */
    onChangeToggleState: function onChangeToggleState(value) {
      var text_parts = this.splitText(this.$target_elem.val()),
          is_present = this.containsText(text_parts, this.text);

      if (value === false && this.mode === this.ON_DISABLE && !is_present || value === true && this.mode === this.ON_ENABLE && !is_present) {
        this.addTextToTarget(text_parts);
      } else {
        this.removeTextFromTarget(text_parts);
      }
    },

    /**
     * Sets toggler state on the target element value change.
     *
     * @param {String} text
     */
    onChangeTargetElementContent: function onChangeTargetElementContent(text) {
      var text_parts = this.splitText(text),
          is_present = this.containsText(text_parts, this.text);

      if (is_present && this.mode === this.ON_ENABLE || !is_present && this.mode === this.ON_DISABLE) {
        this.$elem.prop('checked', 'checked');
      } else {
        this.$elem.prop('checked', null);
      }
    },

    /**
     * Splits text into words.
     *
     * @param {String} text
     * @returns {String[]}
     */
    splitText: function splitText(text) {
      return (text || '').split(this.separator).map(function (part) {
        return part.trim();
      }).filter(function (part) {
        return part.length !== 0;
      });
    }
  });
  $.ceEvent('on', 'ce.commoninit', function (context) {
    var $text_toggle = $(context).find('.cm-text-toggle');

    if (!$text_toggle.length) {
      return;
    }

    $text_toggle.each(function (i, elm) {
      var text_toggle = new TextToggle($(elm));
      text_toggle.init();
    });
  });
})(Tygh, Tygh.$);