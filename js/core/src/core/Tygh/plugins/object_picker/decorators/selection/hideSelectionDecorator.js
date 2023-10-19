export function HideSelectionDecorator(decorated, $element, options) {
    decorated.call(this, $element, options);
}

HideSelectionDecorator.prototype.update = function (decorated, data) {
    decorated.call(this, []);
}
