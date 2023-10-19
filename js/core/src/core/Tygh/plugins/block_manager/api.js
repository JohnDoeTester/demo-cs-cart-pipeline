import $ from "jquery";
import { params } from './params';

export const api = {
    sendRequest: function (action, data) {
        const controllerData = api._getControllerData(action);

        $.ceAjax('request', fn_url(controllerData.dispatch + '.' + controllerData.mode), {
            data: data,
            method: controllerData.method,
            hidden: true,
            callback: function (responseData) {
                const params = [action, data, controllerData];
                
                if (responseData && responseData.data) {
                    params.push(responseData.data);
                }

                $.ceEvent('trigger', 'ce.block_manager.change', params);
            }
        });
    },

    _getControllerData: function (action) {
        let mode = '';
        const controllerData = {
            mode: '',
            method: 'post',
            dispatch: 'block_manager',
        };

        if (action === 'move') {
            mode = 'snapping';
        } else if (action === 'switch') {
            mode = 'update_status';
        }

        if (!params._hasLayout) {
            controllerData.dispatch = 'index';

            if (action === 'move') {
                controllerData.dispatch = 'tools';
                controllerData.method = 'get';
                mode = 'update_position';
            } else if (action === 'delete') {
                mode = 'delete_block';
            } else if (action === 'switch') {
                mode = 'update_block';
            }
        }

        controllerData.mode = mode;
        if (typeof params._blockManager.data('caBlockManagerDispatch') !== 'undefined') {
            controllerData.dispatch = params._blockManager.data('caBlockManagerDispatch');
        }
        
        return controllerData;
    }
};
