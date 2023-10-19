import $ from "jquery";

export function ExternalContainerDecorator(decorated, $element, options) {
    decorated.call(this, $element, options);

    this.$externalSelectionContainer = $(options.get('externalContainerSelector'));
}

ExternalContainerDecorator.prototype.update = function (decorated, data) {
    decorated.call(this, []);

    let Utils = $.fn.select2.amd.require('select2/utils');
    let $selections = [];
    let selectionMap = new Map();

    this.$externalSelectionContainer.children().each(function () {
        let $selection = $(this),
            data = Utils.GetData($selection[0], 'data');

        if (!data) {
            return;
        }

        selectionMap.set(data.id, $selection);
    });

    for (var d = 0; d < data.length; d++) {
        let selection = data[d];

        if (selection.data) {
            selection.data._index = d;
        }

        let $selection = $(this.display(selection, ''));

        if (selectionMap.has(selection.id)) {
            let $currentSelection = selectionMap.get(selection.id);
            let currentSelection = Utils.GetData($currentSelection[0], 'data');

            if (selection.isChanged) {
                $currentSelection.replaceWith($selection);
            }

            selectionMap.delete(selection.id);
        } else {
            $selections.push($selection);
        }
        Utils.StoreData($selection[0], 'data', selection);
    }

    if ($selections.length) {
        Utils.appendMany(this.$externalSelectionContainer, $selections);
    }

    if (selectionMap.size) {
        selectionMap.forEach(function ($selection) {
            Utils.RemoveData($selection[0]);
            $selection.remove();
        });
    }
}

ExternalContainerDecorator.prototype.bind = function (decorated, container, $container) {
    decorated.call(this, container, $container);

    let self = this;
    let Utils = $.fn.select2.amd.require('select2/utils');

    this.$externalSelectionContainer.on(
        'click',
        '.cm-object-picker-remove-object',
        function (evt) {
            // Ignore the event if it is disabled
            if (self.options.get('disabled')) {
                return;
            }

            let $remove = $(this);
            let $selection = $remove.closest('.cm-object-picker-object');
            let data = Utils.GetData($selection[0], 'data');

            // On mobile, prevents keyboard from appearing when an item is deleted.
            if (evt.originalEvent) {
                evt.originalEvent = Object.assign({}, evt.originalEvent, {
                    metaKey: true,
                });
            }

            self.trigger('unselect', {
                originalEvent: evt,
                data: data
            });
        }
    );
};