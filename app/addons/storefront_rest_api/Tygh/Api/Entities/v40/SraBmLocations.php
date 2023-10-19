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


use Tygh\Addons\StorefrontRestApi\ASraEntity;
use Tygh\Api\Response;
use Tygh\BlockManager\Location;


/**
 * Class SraBmLocations
 *
 * @package Tygh\Api\Entities
 */
class SraBmLocations extends ASraEntity
{
    /**
     * @inheritDoc
     */
    public function index($id = '', $params = array())
    {
        $status = Response::STATUS_OK;
        $layout_id = 0;
        $lang_code = $this->getLanguageCode($params);

        if ($this->getParentName() === 'sra_bm_layouts') {
            $layout = $this->getParentData();
            $layout_id = $layout['layout_id'];
        }

        $location = Location::instance($layout_id);

        if ($id) {
            if (is_numeric($id)) {
                $data = $location->getById($id, $lang_code);
            } else {
                $data = $location->getList([
                    'dispatch' => $id,
                    'sort_by' => 'object_ids',
                    'sort_order' => 'desc',
                    'limit' => 1
                ], $lang_code);

                $data = $data ? reset($data) : $location->getDefault($lang_code);
            }

            if (empty($data)) {
                $status = Response::STATUS_NOT_FOUND;
            }
        } else {
            $data = $location->getList($params, $lang_code);
        }

        return array(
            'status' => $status,
            'data'   => $data,
        );
    }

    /**
     * @inheritDoc
     */
    public function create($params)
    {
        return array(
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
        );
    }

    /**
     * @inheritDoc
     */
    public function update($id, $params)
    {
        return array(
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
        );
    }

    /**
     * @inheritDoc
     */
    public function delete($id)
    {
        return array(
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
        );
    }

    /**
     * @inheritDoc
     */
    public function isValidIdentifier($id)
    {
        return !empty($id);
    }

    /**
     * @inheritDoc
     */
    public function privilegesCustomer()
    {
        return array(
            'index'  => true,
            'create' => false,
            'update' => false,
            'delete' => false,
        );
    }

    /**
     * @inheritDoc
     */
    public function privileges()
    {
        return array(
            'index'  => true,
            'create' => 'edit_blocks',
            'update' => 'edit_blocks',
            'delete' => 'edit_blocks',
        );
    }
}
