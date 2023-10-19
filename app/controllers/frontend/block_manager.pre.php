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
use Tygh\BlockManager\RenderManager;
use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

if ($mode === 'render') {
    if (empty($_REQUEST['object_key'])) {
        exit;
    }

    $object_key = $_REQUEST['object_key'];
    $object_key = fn_decrypt_text($object_key);
    list($block_id, $snapping_id) = explode(':', $object_key);
    $block_id = (int) $block_id;
    $snapping_id = (int) $snapping_id;

    $block = Block::instance()->getById($block_id, $snapping_id);
    if ($block) {
        $block = array_merge([
            'grid_id' => 0,
            'order'   => 0,
        ], $block);

        if (!empty($_REQUEST['redirect_url'])) {
            /** @var \Tygh\SmartyEngine\Core $smarty */
            $smarty = Tygh::$app['view'];
            $smarty->assign('redirect_url', $_REQUEST['redirect_url']);
        }

        /** @var \Tygh\Ajax $ajax */
        $ajax = Tygh::$app['ajax'];
        $ajax->assign('block_content', RenderManager::renderBlock($block));
    }

    exit;
}
