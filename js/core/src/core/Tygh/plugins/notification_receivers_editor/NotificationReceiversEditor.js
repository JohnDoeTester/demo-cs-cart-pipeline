import $ from 'jquery';

export const GROUP = 'group';
export const EVENT = 'event';

export class NotificationReceiversEditor {
    constructor($elem, options) {
        this.$elem = $elem;
        this.options = options;

        this.bindEvents();
        this.fireEvent('inited');
    }

    fireEvent(event, ...params) {
        this.$elem.trigger(`ce:notification_receivers_editor:${event}`, [this, ...params]);
        $.ceEvent('trigger', `ce.notification_receivers_editor.${event}`, [this, ...params]);
    }

    bindEvents() {
        const self = this;

        this.$elem.on('click', this.options.cancelButtonSelector, function () {
            self.resetChanges.apply(self);
        });

        this.$elem.on('click', this.options.updateButtonSelector, function () {
            self.saveReceivers.apply(self);
        });
    }

    // FIXME: Add proper reset functionality
    resetChanges() {
        const self = this;
        const data = {};

        $.ceAjax('request', this.options.loadUrl, {
            method: 'get',
            result_ids: this.options.resultIds,
            data,
            caching: true,
            hidden: true,
            callback: function (response) {
                self.options.resetCallback(self, data, response);
                self.fireEvent('reset');
            }
        });
    }

    saveReceivers() {
        const self = this;
        const data = {
            object_type: this.options.objectType,
            object_id: this.options.objectId,
            conditions: this.serialize(),
        };

        $.ceAjax('request', this.options.submitUrl, {
            method: 'post',
            result_ids: this.options.resultIds,
            data,
            caching: false,
            callback: function (response) {
                self.options.saveCallback(self, data, response);
                self.fireEvent('saved', data, response);
            }
        });
    }

    serialize() {
        let data = [],
            self = this;

        this.$elem.find(this.options.receiverPickerSelector).each(function () {
            const $elem = $(this);
            const receivers = self.getSelectedReceivers($elem);

            receivers.forEach((receiver) => {
                data.push(receiver);
            });
        });

        this.fireEvent('serialized', data);

        return data;
    }

    getSelectedReceivers($elem) {
        let data = [];

        const selectedItems = $elem.select2('data');
        selectedItems.forEach((selectedElem) => {
            let {method, criterion} = selectedElem.data;
            method = method || $elem.data('caNotificationReceiversEditorReceiverSearchMethod');
            criterion = criterion || selectedElem.text;

            data.push({method, criterion});
        });

        return data;
    }

    getEditorObjectType() {
        return this.options.groupName
            ? GROUP
            : EVENT;
    }

    getEditorObjectId() {
        return this.options.groupName
            ? this.options.groupName
            : this.options.eventName;
    }
}
