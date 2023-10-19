import $ from "jquery";

export class ObjectStorage {
    static set(objectType, objects) {
        let self = this;

        try {
            objects.forEach(function (object) {
                sessionStorage.setItem(self.getItemKey(objectType, object.id), JSON.stringify(object));
            });
        } catch (e) {}
    }

    static get(objectType, objectId) {
        try {
            let object = sessionStorage.getItem(this.getItemKey(objectType, objectId));

            if (object) {
                object = JSON.parse(object);
                object = this.normalizeObject(object);
                return object;
            }
        } catch (e) {}

        return null;
    }

    static mget(objectType, objectIds) {
        let objects = [],
            self = this;

        objectIds.forEach(function (objectId) {
            let object = self.get(objectType, objectId);

            if (object) {
                objects.push(object);
            }
        });

        return objects;
    }

    static load(url, objectType, objectIds, method) {
        let defer = $.Deferred(),
            self = this;

        $.ceAjax('request', url, {
            method: method || 'get',
            hidden: true,
            caching: true,
            data: {ids: objectIds},
            error_callback: function () {
                defer.reject();
            },
            callback: function (response) {
                if (typeof response.objects === undefined) {
                    return;
                }

                let map = {};

                $.each(response.objects, function (key, object) {
                    object = self.normalizeObject(object);
                    object.loaded = true;
                    map[object.id] = object;
                });

                self.set(objectType, response.objects);
                defer.resolve(map);
            }
        });

        return defer.promise();
    }

    static find(url, objectType, params) {
        let defer = $.Deferred(),
            self = this;

        params = $.extend({}, params, {
            error_callback: function () {
                defer.reject();
            },
            callback: function (response) {
                defer.resolve(response);
            },
            hidden: true
        });

        $.ceAjax('request', url, params);

        return defer.promise();
    }

    static getItemKey(object_type, object_id) {
        return `${object_type}_${object_id}`;
    }

    static normalizeObject(object) {
        if (object.id && object.id !== null) {
           object.id = object.id.toString();
        }

        return object;
    }
}