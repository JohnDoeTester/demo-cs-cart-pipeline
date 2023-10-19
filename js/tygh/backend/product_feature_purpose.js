(function (_, $) {
  var PurposeRepository = function PurposeRepository(purposes) {
    this.purposes = purposes;
    this.purpose_instances = {};
  };

  var Purpose = function Purpose(purpose_data) {
    this.data = purpose_data;
  };

  var PurposeStyleItem = function PurposeStyleItem(style_item) {
    this.data = style_item;
  };

  var PurposeControl = function PurposeControl($purpose_elem) {
    this.$elem = $purpose_elem;
    this.$feature_style_elem = $('#' + $purpose_elem.data('caFeatureStyleElemId'));
    this.$filter_style_elem = $('#' + $purpose_elem.data('caFilterStyleElemId'));
    this.$feature_type_elem = $('#' + $purpose_elem.data('caFeatureTypeElemId'));
    this.$variants_list_elem = $('#' + $purpose_elem.data('caVariantsListElemId'));
    this.feature_id = $purpose_elem.data('caFeatureId');
    this.feature_purpose = $purpose_elem.data('caFeaturePurpose');
    this.purpose_repository = new PurposeRepository($purpose_elem.data('caFeaturePurposes'));
    this.feature_type = $purpose_elem.data('caFeatureType');
    this.feature_style = $purpose_elem.data('caFeatureStyle');
    this.filter_style = $purpose_elem.data('caFilterStyle');
    $purpose_elem.data('caPurposeControl', this);
  };

  var self;
  $.extend(PurposeControl.prototype, {
    init: function init() {
      self = this;
      var style_item;

      if (!this.feature_type) {
        style_item = this.getPurpose().getDefaultStyleItem();
        this.feature_type = style_item.getFeatureType();
        this.feature_style = style_item.getFeatureStyle();
        this.filter_style = style_item.getFilterStyle();
        this.$feature_type_elem.val(this.feature_type);
      }

      if (this.feature_type && (!this.feature_style || !this.filter_style)) {
        style_item = this.getPurpose().getStyleItem(this.feature_type);
        this.feature_style = style_item.getFeatureStyle();
        this.filter_style = style_item.getFilterStyle();
      }

      this.renderFeatureStyleSelectBox();
      this.renderFilterStyleSelectBox();
      this.bindEvents();

      if ($.fn.ceProductFeature) {
        this.$feature_type_elem.ceProductFeature('checkType');
      }
    },
    bindEvents: function bindEvents() {
      var self = this;
      this.$elem.on('click', 'input[type=radio]', function () {
        self.onChangeFeaturePurpose($(this).val());
      });
      this.$feature_style_elem.on('change', function () {
        self.onChangeFeatureStyle($(this).val());
      });
      this.$filter_style_elem.on('change', function () {
        self.onChangeFilterStyle($(this).val());
      });
    },
    getPurpose: function getPurpose() {
      return this.purpose_repository.getPurpose(this.feature_purpose);
    },
    renderFeatureStyleSelectBox: function renderFeatureStyleSelectBox() {
      this._renderSelectBox(this.$feature_style_elem, this.getPurpose().getFeatureStyles(), this.feature_style);
    },
    renderFilterStyleSelectBox: function renderFilterStyleSelectBox() {
      this._renderSelectBox(this.$filter_style_elem, this.getPurpose().getFilterStyles(this.feature_style), this.filter_style);
    },
    showFeatureSpecificColumns: function showFeatureSpecificColumns() {
      this.$variants_list_elem.find('.js-feature-variant-conditional-column').addClass('hidden');
      this.$variants_list_elem.find('[data-ca-column-for-feature-style=' + this.feature_style + ']').removeClass('hidden');
      this.$variants_list_elem.find('[data-ca-column-for-filter-style=' + this.filter_style + ']').removeClass('hidden');
    },
    changeFeaturePurpose: function changeFeaturePurpose(feature_purpose) {
      if (this.feature_purpose === feature_purpose) {
        return;
      }

      this.$elem.find('.cm-feature-purpose-description').addClass('hidden');
      this.feature_purpose = feature_purpose;
      var description_element_id = this.$elem.find('[value="' + feature_purpose + '"]').data('caPurposeDescriptionElemId');
      $('#' + description_element_id).removeClass('hidden');
    },
    changeFeatureType: function changeFeatureType(feature_type) {
      if (this.feature_type === feature_type) {
        return;
      }

      this.feature_type = feature_type;
      this.$feature_type_elem.val(feature_type);

      if ($.fn.ceProductFeature) {
        this.$feature_type_elem.ceProductFeature('checkType');
      }
    },
    onChangeFeaturePurpose: function onChangeFeaturePurpose(feature_purpose) {
      if (this.feature_purpose === feature_purpose) {
        return;
      }

      this.changeFeaturePurpose(feature_purpose);
      var style_item = this.getPurpose().getDefaultStyleItem();
      this.feature_style = style_item.getFeatureStyle();
      this.filter_style = style_item.getFilterStyle();
      this.renderFeatureStyleSelectBox();
      this.renderFilterStyleSelectBox();
      this.changeFeatureType(style_item.getFeatureType());
    },
    onChangeFeatureStyle: function onChangeFeatureStyle(feature_style) {
      if (this.feature_style === feature_style) {
        return;
      }

      var style_item = this.getPurpose().getStyleItemByFeatureStyle(feature_style);
      this.feature_style = feature_style;
      this.filter_style = style_item.getFilterStyle();
      this.changeFeatureType(style_item.getFeatureType());
      this.renderFilterStyleSelectBox();
      this.showFeatureSpecificColumns();
    },
    onChangeFilterStyle: function onChangeFilterStyle(filter_style) {
      if (this.filter_style === filter_style) {
        return;
      }

      var style_item = this.getPurpose().getStyleItemByFeatureStyleAndFilterStyle(this.feature_style, filter_style);
      this.filter_style = filter_style;
      this.changeFeatureType(style_item.getFeatureType());
      this.showFeatureSpecificColumns();
    },
    reset: function reset() {
      this.changeFeaturePurpose(this.$elem.data("caFeaturePurpose"));
      this.feature_style = this.$elem.data("caFeatureStyle");
      this.filter_style = this.$elem.data("caFilterStyle");
      this.changeFeatureType(this.$elem.data("caFeatureType"));
      this.renderFeatureStyleSelectBox();
      this.renderFilterStyleSelectBox();
    },
    _renderSelectBox: function _renderSelectBox($elem, values, value) {
      $elem.empty();
      $.each(values, function (key, text) {
        $elem.append($('<option/>').text(text).val(key));
      });
      $elem.val(value);
      $elem.prop('disabled', $.isEmptyObject(values));
    }
  });
  $.extend(PurposeStyleItem.prototype, {
    getFeatureStyle: function getFeatureStyle() {
      return this.data.feature_style;
    },
    getFeatureStyleText: function getFeatureStyleText() {
      return this.data.feature_style_text;
    },
    getFilterStyle: function getFilterStyle() {
      return this.data.filter_style;
    },
    getFilterStyleText: function getFilterStyleText() {
      return this.data.filter_style_text;
    },
    getFeatureType: function getFeatureType() {
      return this.data.feature_type;
    }
  });
  $.extend(Purpose.prototype, {
    /**
     * @returns {PurposeStyleItem}
     */
    getDefaultStyleItem: function getDefaultStyleItem() {
      var key = Object.keys(this.data.styles_map)[0];
      return new PurposeStyleItem(this.data.styles_map[key]);
    },

    /**
     * @returns {PurposeStyleItem}
     */
    getStyleItem: function getStyleItem(feature_type) {
      var key = Object.keys(this.data.types[feature_type])[0];
      return new PurposeStyleItem(this.data.styles_map[key]);
    },
    getFeatureStyles: function getFeatureStyles() {
      var feature_styles = {};
      $.each(this.data.styles_map, function (key, item) {
        if (item && item.feature_style) {
          feature_styles[item.feature_style] = item.feature_style_text;
        }
      });
      return feature_styles;
    },
    getFilterStyles: function getFilterStyles(feature_style) {
      var filter_styles = {};
      $.each(this.data.styles_map, function (key, item) {
        if (item && item.feature_style === feature_style && item.filter_style) {
          filter_styles[item.filter_style] = item.filter_style_text;
        }
      });
      return filter_styles;
    },

    /**
     * @returns {PurposeStyleItem}
     */
    getStyleItemByFeatureStyle: function getStyleItemByFeatureStyle(feature_style) {
      var result;
      $.each(this.data.styles_map, function (key, item) {
        if (item && item.feature_style === feature_style) {
          result = new PurposeStyleItem(item);
          return false;
        }
      });
      return result;
    },

    /**
     * @returns {PurposeStyleItem}
     */
    getStyleItemByFeatureStyleAndFilterStyle: function getStyleItemByFeatureStyleAndFilterStyle(feature_style, filter_style) {
      var result;
      $.each(this.data.styles_map, function (key, item) {
        if (item && item.feature_style === feature_style && item.filter_style === filter_style) {
          result = new PurposeStyleItem(item);
          return false;
        }
      });
      return result;
    }
  });
  $.extend(PurposeRepository.prototype, {
    /**
     * @param purpose
     * @returns {Purpose}
     */
    getPurpose: function getPurpose(purpose) {
      if (typeof this.purposes[purpose] === 'undefined') {
        throw "Undefined purpose";
      }

      if (typeof this.purpose_instances[purpose] === 'undefined') {
        this.purpose_instances[purpose] = new Purpose(this.purposes[purpose]);
      }

      return this.purpose_instances[purpose];
    }
  });
  $.ceEvent('on', 'ce.cm_cancel.clean_form', function ($form) {
    var $purpose_elem = $form.find('.cm-feature-purpose');

    if ($purpose_elem.length) {
      var purpose_control = $purpose_elem.data('caPurposeControl');

      if (purpose_control) {
        purpose_control.reset();
      }
    }
  });
  $.ceEvent('on', 'ce.commoninit', function ($context) {
    var $purpose_elem = $context.find('.cm-feature-purpose');

    if ($purpose_elem.length) {
      var purpose_control = new PurposeControl($purpose_elem);
      purpose_control.init();
    }

    if (typeof self !== 'undefined') {
      var $variants_list_elem = $context.find('#' + self.$elem.data('caVariantsListElemId'));

      if ($variants_list_elem) {
        self.$variants_list_elem = $variants_list_elem;
        self.showFeatureSpecificColumns();
      }
    }
  });
})(Tygh, Tygh.$);