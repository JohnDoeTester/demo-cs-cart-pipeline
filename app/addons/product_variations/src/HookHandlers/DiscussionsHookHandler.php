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


namespace Tygh\Addons\ProductVariations\HookHandlers;


use Tygh\Addons\ProductVariations\Product\Group\GroupProduct;
use Tygh\Addons\ProductVariations\ServiceProvider;
use Tygh\Application;

/**
 * This class describes hook handlers related to the Discussion add-on
 *
 * @package Tygh\Addons\ProductVariations\HookHandlers
 */
class DiscussionsHookHandler
{
    protected $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * The "get_discussion_pre" hook handler.
     *
     * Actions performed:
     *  - Replaces $object_id (the identifier of a product) with the identifier of its parent product, if available.
     *      This displays the comments and rating of the parent product on the pages of its variations.
     *
     * @see fn_get_discussion
     */
    public function onGetDiscussionPre(&$object_id, $object_type, $get_posts, $params)
    {
        if ($object_type !== DISCUSSION_OBJECT_TYPE_PRODUCT || !empty($params['skip_check_child_product'])) {
            return;
        }

        $product_id_map = ServiceProvider::getProductIdMap();

        if ($product_id_map->isChildProduct($object_id)) {
            $object_id = $product_id_map->getParentProductId($object_id);
        }
    }

    /**
     * The "variation_group_mark_product_as_main_post" hook handler.
     *
     * Actions performed:
     *  - Moves comments from the old parent product to a new one.
     *    This is necessary because child variations can't have their own comments.
     *
     * @see \Tygh\Addons\ProductVariations\Service::saveGroup
     */
    public function onVariationGroupMarkProductAsMainPost($service, $group, GroupProduct $from_group_product, GroupProduct $to_group_product)
    {
        $new_main_product_id = $to_group_product->getProductId();
        $old_main_product_id = $from_group_product->getProductId();

        $query = ServiceProvider::getQueryFactory()->createQuery(
            'discussion',
            ['object_type' => 'P', 'object_id' => [$old_main_product_id, $new_main_product_id]],
            ['*']
        );

        $on_insert_list = [];

        foreach ($query->select() as $item) {
            if ($item['object_id'] == $old_main_product_id) {
                $item['object_id'] = $new_main_product_id;
            } elseif ($item['object_id'] == $new_main_product_id) {
                $item['object_id'] = $old_main_product_id;
            }

            $on_insert_list[] = $item;
        }

        if ($on_insert_list) {
            $query = ServiceProvider::getQueryFactory()->createQuery(
                'discussion',
                ['object_type' => 'P', 'object_id' => [$old_main_product_id, $new_main_product_id]]
            );

            $query->delete();

            $query = ServiceProvider::getQueryFactory()->createQuery('discussion');
            $query->multipleInsert($on_insert_list);
        }
    }

    /**
     * The "discussion_is_user_eligible_to_write_review_for_product_post" hook handler.
     *
     * Actions performed:
     *  - Checks if another variation is bought by chosen user
     *
     * @see fn_discussion_is_user_eligible_to_write_review_for_product
     */
    public function onDiscussionIsUserEligibleToWriteReviewForProductPost($user_id, $product_id, &$result, $need_to_buy_first)
    {
        $product_id_map = ServiceProvider::getProductIdMap();

        if (empty($user_id) || !$need_to_buy_first ||  $result || !$product_id_map->isVariationProduct($product_id)) {
            return;
        }

        $product_ids = $product_id_map->getVariationSubGroupProductIds($product_id);

        $query = ServiceProvider::getQueryFactory()->createQuery(
            'orders',
            ['user_id' => $user_id],
            ['orders.order_id'],
            'orders'
        );
        $query->addInnerJoin('details', 'order_details', ['order_id' => 'order_id'], ['product_id' => $product_ids]);
        $query->setLimit(1);

        $result = (bool) $query->column();
    }
}
