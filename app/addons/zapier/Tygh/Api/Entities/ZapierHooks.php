<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

namespace Tygh\Api\Entities;

use Tygh\Api\AEntity;
use Tygh\Api\Response;
use Tygh\Registry;

class ZapierHooks extends AEntity
{
    /**
     * Gets zapier hooks via API.
     *
     * @param int                        $id     Zapier hook identifier
     * @param array<string, string>|null $params Request parameters
     *
     * @return array{status: int, data: array}
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    public function index($id = 0, $params = [])
    {
        if (!empty($id)) {
            $data = $this->getZapierHook($id);
            if ($data) {
                $status = Response::STATUS_OK;
            } else {
                $status = Response::STATUS_NOT_FOUND;
            }
        } else {
            $params['items_per_page'] = $this->safeGet(
                $params,
                'items_per_page',
                Registry::get('settings.Appearance.admin_elements_per_page')
            );

            list($zapier_hooks, $search) = fn_zapier_get_hooks($params);

            $data = [
                'zapier_hooks' => array_values($zapier_hooks),
                'params' => $search
            ];
            $status = Response::STATUS_OK;
        }
        /** @psalm-suppress LessSpecificReturnStatement */
        return [
            'status' => $status,
            'data' => $data
        ];
    }

    /**
     * Adds zapier hook via API.
     *
     * @param array<string>|null $params Request parameters
     *
     * @return array{status: string|int, data: array<string|int>}
     */
    public function create($params)
    {
        $data = [];
        $status = Response::STATUS_BAD_REQUEST;

        if (empty($params['hook_url'])) {
            $data['message'] = __('api_required_field', ['[field]' => 'hook_url']);
        } else {
            $hook_id = fn_zapier_update_hook($params);

            if ($hook_id) {
                $status = Response::STATUS_CREATED;
                $data = [
                    'hook_id' => $hook_id,
                ];
            }
        }

        return [
            'status' => $status,
            'data' => $data
        ];
    }

    /**
     * Updates zapier hook via API.
     *
     * @param int                        $id     Zapier hook identifier
     * @param array<string, string>|null $params Request parameters
     *
     * @return array{status: string|int, data: array<string|int>}
     */
    public function update($id, $params)
    {
        $data = [];
        $status = Response::STATUS_BAD_REQUEST;

        if ($this->getZapierHook($id)) {
            if (fn_zapier_update_hook($params, $id)) {
                $status = Response::STATUS_OK;
                $data = [
                    'hook_id' => $id
                ];
            }
        }

        return [
            'status' => $status,
            'data' => $data
        ];
    }

    /**
     * Deletes zapier hook via API.
     *
     * @param int $id Zapier hook identifier
     *
     * @return array{status: string|int, data: array<string|int>}
     */
    public function delete($id)
    {
        $status = Response::STATUS_NOT_FOUND;
        $data = [];

        if ($this->getZapierHook($id) && fn_zapier_delete_hook($id)) {
            $status = Response::STATUS_NO_CONTENT;
        }

        return [
            'status' => $status,
            'data' => $data
        ];
    }

    /**
     * Returns privileges for ZapierHooks entity
     *
     * @return array<string, string>
     */
    public function privileges()
    {
        return [
            'create' => 'manage_zapier_hooks',
            'update' => 'manage_zapier_hooks',
            'delete' => 'manage_zapier_hooks',
            'index'  => 'view_zapier_hooks'
        ];
    }

    /**
     * Normalizes data for getting zapier hook.
     *
     * @param int $id Zapier hook identifier
     *
     * @return array<string>|void
     */
    protected function getZapierHook($id)
    {
        list($zapier_hooks) = fn_zapier_get_hooks(['hook_id' => $id]);
        if ($zapier_hooks) {
            return reset($zapier_hooks);
        }

        return;
    }
}
