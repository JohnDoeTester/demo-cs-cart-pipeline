export function BaseResultDecorator(decorated, $element, options, dataAdapter) {
    decorated.call(this, $element, options, dataAdapter);

    this.unremovableItemsIds = options.get('unremovableItemIds');
}

BaseResultDecorator.prototype.option = function (decorated, data) {
    if (data.id && Array.isArray(this.unremovableItemsIds) && this.unremovableItemsIds.indexOf(data.id) !== -1) {
        data.disabled = true;
    }

    return decorated.call(this, data);
}