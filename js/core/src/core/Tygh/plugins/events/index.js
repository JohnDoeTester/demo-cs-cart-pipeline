import { Tygh } from "../..";

const _ = Tygh;

export const handlers = {};

export const methods = {
    on: function(event, handler, one)
    {
        one = one || false;
        if (!(event in handlers)) {
            handlers[event] = [];
        }
        handlers[event].push({
            handler: handler,
            one: one
        });
    },

    one: function(event, handler)
    {
        methods.on(event, handler, true);
    },

    trigger: function(event, data)
    {
        data = data || [];
        var result = true, _res;
        if (event in handlers) {
            for (var i = 0; i < handlers[event].length; i++) {
                _res = handlers[event][i].handler.apply(handlers[event][i].handler, data);

                if (handlers[event][i].one) {
                    handlers[event].splice(i, 1);
                    i --;
                }

                if (_res === false) {
                    result = false;
                    break;
                }
            }
        }

        return result;
    }
};

/**
 * Events
 * @param {JQueryStatic} $ 
 */
export const ceEventInit = function ($) {
    $.ceEvent = function(method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else {
            $.error('ty.event: method ' +  method + ' does not exist');
        }
    };
}