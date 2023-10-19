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
use Tygh\Storage;

/**
 * This class describes the hook handlers related to the Attachments add-on
 *
 * @package Tygh\Addons\ProductVariations\HookHandlers
 */
class AttachmentsHookHandler
{
    protected $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * The "get_attachments_pre" hook handler.
     *
     * Actions performed:
     *  - Replaces $object_id (the identifier of a product) with the identifier of its parent product, if available.
     *      This displays the attachments of the parent product on the pages of its child variations.
     *
     * @see fn_get_attachments
     */
    public function onGetAttachmentsPre($object_type, &$object_id, $type, $lang_code)
    {
        if ($object_type !== 'product' || !empty($params['skip_check_child_product'])) {
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
     *  - Moves the attachments from the old parent product to a new one.
     *    This is necessary because child variations can't have their own attachments.
     *
     * @see \Tygh\Addons\ProductVariations\Service::saveGroup
     */
    public function onVariationGroupMarkProductAsMainPost($service, $group, GroupProduct $from_group_product, GroupProduct $to_group_product)
    {
        $new_main_product_id = $to_group_product->getProductId();
        $old_main_product_id = $from_group_product->getProductId();

        $query = ServiceProvider::getQueryFactory()->createQuery(
            'attachments',
            ['object_type' => 'product', 'object_id' => [$old_main_product_id, $new_main_product_id]],
            ['attachment_id', 'object_id', 'filename']
        );

        $on_update_list = [];

        foreach ($query->select() as $item) {
            if ($item['object_id'] == $old_main_product_id) {
                $on_update_list[$new_main_product_id][] = $item['attachment_id'];
                $from_id = $old_main_product_id;
                $to_id = $new_main_product_id;
            } elseif ($item['object_id'] == $new_main_product_id) {
                $on_update_list[$old_main_product_id][] = $item['attachment_id'];
                $from_id = $new_main_product_id;
                $to_id = $old_main_product_id;
            }

            if (isset($from_id, $to_id)) {
                $source_file = sprintf('product/%s/%s', $from_id, $item['filename']);
                $destination_file = sprintf('product/%s/%s', $to_id, $item['filename']);

                Storage::instance('attachments')->copy($source_file, $destination_file);
                Storage::instance('attachments')->delete($source_file);
            }
        }

        foreach ($on_update_list as $product_id => $attachment_ids) {
            $query = ServiceProvider::getQueryFactory()->createQuery('attachments', ['attachment_id' => $attachment_ids]);
            $query->update(['object_id' => $product_id]);
        }
    }
}
