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

namespace Tygh\Addons\ProductVariations;

use Tygh\Addons\InstallerInterface;
use Tygh\BlockManager\Block;
use Tygh\BlockManager\ProductTabs;
use Tygh\Core\ApplicationInterface;

/**
 * This class describes the instractions for installing and uninstalling the product_variations add-on
 *
 * @package Tygh\Addons\ProductVariations
 */
class Installer implements InstallerInterface
{
    /**
     * @inheritDoc
     */
    public static function factory(ApplicationInterface $app)
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function onInstall()
    {
        if (fn_allowed_for('ULTIMATE')) {
            $company_ids = fn_get_all_companies_ids();
        } else {
            $company_ids = [0];
        }

        $block = Block::instance();
        $product_tabs = ProductTabs::instance();

        foreach ($company_ids as $company_id) {
            $block_data = [
                'type'         => 'products',
                'properties'   => [
                    'template'                                       => 'addons/product_variations/blocks/products/variations_list.tpl',
                    'product_variations.hide_add_to_wishlist_button' => 'N',
                    'hide_add_to_cart_button'                        => 'N',
                    'product_variations.show_product_code'           => 'Y',
                    'product_variations.show_variation_thumbnails'   => 'Y',
                ],
                'content_data' => [
                    'content' => [
                        'items' => [
                            'filling'             => 'product_variations.variations_filling',
                            'limit'               => '100',
                            'variations_in_stock' => 'N',
                        ],
                    ],
                ],
                'company_id'   => $company_id,
            ];

            $block_description = [
                'lang_code' => DEFAULT_LANGUAGE,
                'name'      => __('product_variations.variations_list_block_name', [], DEFAULT_LANGUAGE),
                'lang_var' => 'product_variations.variations_list_block_name',
            ];

            $block_id = $block->update($block_data, $block_description);

            $tab_data = [
                'tab_type'      => 'B',
                'block_id'      => $block_id,
                'template'      => '',
                'addon'         => 'product_variations',
                'status'        => 'A',
                'is_primary'    => 'N',
                'position'      => false,
                'product_ids'   => null,
                'company_id'    => $company_id,
                'show_in_popup' => 'Y',
                'lang_code'     => DEFAULT_LANGUAGE,
                'name'          => __('product_variations.variations_list_tab_name', [], DEFAULT_LANGUAGE),
                'lang_var' => 'product_variations.variations_list_tab_name'
            ];

            $product_tabs->update($tab_data);
        }
    }

    /**
     * @inheritDoc
     */
    public function onUninstall()
    {

    }

    /**
     * @inheritDoc
     */
    public function onBeforeInstall()
    {

    }
}
