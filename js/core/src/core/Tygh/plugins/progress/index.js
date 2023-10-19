import { Tygh } from "../..";
import $ from "jquery";

const _ = Tygh;

export const getContainer = function (elm) {
    var self = $(elm);
    if (self.length == 0) {
        return false;
    }

    var comet_container_id = self.prop('href').split('#')[1];
    var comet_container = $('#' + comet_container_id);

    return comet_container;
}

export const methods = {

    init: function () {
        var comet_container = getContainer(this);
        if (comet_container == false) {
            return false;
        }

        comet_container.find('.bar').css('width', 0).prop('data-percentage', 0);

        this.trigger('click'); // Display comet progressBar using Bootstrap click handle
        this.data('ceProgressbar', true);

        $.ceEvent('trigger', 'ce.progress_init');
    },

    setValue: function (o) {

        var comet_container = getContainer(this);

        if (comet_container == false) {
            return false;
        }

        if (!this.data('ceProgressbar')) {
            this.ceProgress('init');
        }

        if (o.progress) {
            comet_container.find('.bar').css('width', o.progress + '%').prop('data-percentage', o.progress);
        }

        if (o.text) {
            comet_container.find('.modal-body p').html(o.text);
        }

        $.ceEvent('trigger', 'ce.progress', [o]);
    },

    getValue: function (o) {
        var comet_container = getContainer(this);
        if (comet_container == false) {
            return false;
        }

        if (!this.data('ceProgressbar')) {
            return 0;
        }

        return parseInt(comet_container.find('.bar').prop('data-percentage'));
    },

    setTitle: function (o) {
        var comet_container = getContainer(this);
        if (comet_container == false) {
            return false;
        }

        if (!this.data('ceProgressbar')) {
            this.ceProgress('init');
        }

        if (o.title) {
            $('#comet_title').text(o.title);
        }
    },

    finish: function () {
        var comet_container = getContainer(this);
        if (comet_container == false) {
            return false;
        }

        comet_container.find('.bar').css('width', 100).prop('data-percentage', 100);
        comet_container.modal('hide');

        this.removeData('ceProgressbar');
        $.ceEvent('trigger', 'ce.progress_finish');
    }
};


/**
 * Progress bar (COMET)
 * @param {JQueryStatic} $ 
 */
export const ceProgressInit = function ($) {
    $.fn.ceProgress = function (method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        } else {
            $.error('ty.progress: method ' + method + ' does not exist');
        }
    }
}
