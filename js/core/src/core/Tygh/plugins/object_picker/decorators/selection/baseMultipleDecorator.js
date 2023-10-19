import $ from "jquery";

export function BaseMultipleDecorator(decorated, $element, options) {
    decorated.call(this, $element, options);
    this.unremovableItemsIds = options.get('unremovableItemIds');
    this.enablePermanentPlaceholder = options.get('enablePermanentPlaceholder');
}

BaseMultipleDecorator.prototype.bind = function (decorated, container, $container) {
    this.$selection.on('click', function (e) {
        if (!$(e.target).hasClass('select2-search__field') && !$(e.target).hasClass('select2-selection__rendered')) {
            // disable rendering dropdown if click was not on the search field
            e.stopImmediatePropagation();
        }
    });

    decorated.call(this, container, $container);
};

BaseMultipleDecorator.prototype.display = function (decorated, data, container) {
    if (data.id && Array.isArray(this.unremovableItemsIds) && this.unremovableItemsIds.indexOf(data.id) !== -1) {
        container.find('.select2-selection__choice__remove').remove();
        container.addClass('select2-selection__choice--unremovable');
    }

    return decorated.call(this, data, container);
};

BaseMultipleDecorator.prototype.searchRemoveChoice = function () {
    // prevent selected option from deletion (when pressing backspace and search box is empty)
    return false;
};

BaseMultipleDecorator.prototype.update = function (decorated, data) {
    decorated.call(this, data);

    if (this.enablePermanentPlaceholder) {
        this.$search.attr('placeholder', this.placeholder.text);
    }
};