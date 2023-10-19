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

use Tygh\BlockManager\Block;
use Tygh\BlockManager\Container;
use Tygh\BlockManager\Grid;
use Tygh\BlockManager\SchemesManager;
use Tygh\Enum\BlockManagerActions;
use Tygh\Enum\UserTypes;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

$request_method = $_SERVER['REQUEST_METHOD'];

if (!Registry::ifGet('config.demo_mode', false)) {
    $has_permissions = fn_check_permissions('block_manager', $mode, 'admin', $request_method, $_REQUEST, AREA, $auth['user_id']);

    if (AREA === 'C' && ($auth['user_type'] !== UserTypes::ADMIN || !$has_permissions)) {
        return [CONTROLLER_STATUS_DENIED];
    }
}

if ($request_method === 'POST') {

    if ($mode == 'update_status') {

        $type = empty($_REQUEST['type']) ? 'block' : $_REQUEST['type'];

        if ($type == 'block') {
            Block::instance()->updateStatus($_REQUEST);

        } elseif ($type == 'grid') {
            if (SchemesManager::isGridWrapperAvailable($_REQUEST)) {
                Grid::update($_REQUEST);
            }
        } elseif ($type == 'container') {
            Container::update($_REQUEST);
        }
    }

    if ($mode == 'snapping' && isset($_REQUEST['snappings']) && is_array($_REQUEST['snappings'])) {
        foreach ($_REQUEST['snappings'] as $snapping_data) {
            if (!empty($snapping_data['action'])) {
                if ($snapping_data['action'] == 'update' || $snapping_data['action'] == 'add') {
                    $snapping_id = Block::instance()->updateSnapping($snapping_data);

                    if ($snapping_data['action'] == 'add') {
                        $block_data = Block::instance()->getSnappingData(array('?:bm_blocks.type'), $snapping_id);
                        $bm_actions[BlockManagerActions::ACT_PROPERTIES] = SchemesManager::isManageable($block_data['type']);
                        $result = $snapping_id;
                    }
                } elseif ($snapping_data['action'] == 'delete' && !empty($snapping_data['snapping_id'])) {
                    $result = Block::instance()->removeSnapping($snapping_data['snapping_id']);
                }
            }
        }
    }

    if (defined('AJAX_REQUEST')) {
        /** @var \Tygh\Ajax $ajax */
        $ajax = Tygh::$app['ajax'];
        if (isset($result)) {
            $ajax->assign('result', $result);
        }
    }

    if (AREA === 'C') {
        exit;
    }
}
