export function PredefinedVariantsDecorator(decorated, $element, options) {
    decorated.call(this, $element, options);

    this.variants = options.get('predefinedVariants');
}

PredefinedVariantsDecorator.prototype.current = function (decorated, callback) {
    decorated.call(this, callback);

    var data = [];
    var self = this;

    var selectedVariants = this.$element.val();
    if (!Array.isArray(selectedVariants)) {
        selectedVariants = [selectedVariants];
    }

    this.variants.forEach(function (variant) {
        variant = self._normalizeVariant(variant);
        if (selectedVariants.indexOf(variant.id) === -1) {
            return;
        }
        data.push(variant);
    });

    if (data.length) {
        callback(data);
    }
}

PredefinedVariantsDecorator.prototype.query = function (decorated, params, callback) {  
    var self = this;

    if (params.term || params.page != null) {
        decorated.call(this, params, callback);
        return;
    }

    function wrapper (obj) {
        var data = obj.results;
        var options = [];

        self.variants.forEach(function (variant) {
            variant = self._normalizeVariant(variant);
            let $option = self.option(variant);
            $option.attr('data-select2-predefined-variant', true);

            options.push($option);
            self._insertVariant(data, variant);
        });

        obj.results = data;
        callback(obj);
    }

    decorated.call(this, params, wrapper);
}

PredefinedVariantsDecorator.prototype._insertVariant = function (_, data, variant) {
    data.unshift(variant);
};

PredefinedVariantsDecorator.prototype._normalizeVariant = function (_, variant) {
    return Object.assign(variant, {
        data: variant.data || {},
        loaded: true,
        isPredefined: true
    });
};