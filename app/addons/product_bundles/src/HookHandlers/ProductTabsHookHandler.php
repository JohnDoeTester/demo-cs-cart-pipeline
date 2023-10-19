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

namespace Tygh\Addons\ProductBundles\HookHandlers;

use Tygh\Registry;

class ProductTabsHookHandler
{
    /**
     * The `get_product_tabs_post` hook handler.
     *
     * Action performed:
     *     - Hides setting for showing tab in pop-up when add-on settings won't allow that.
     *
     * @param array<array<string, string|bool>> $tabs      Array of product tabs data.
     * @param string                            $lang_code Language code.
     *
     * @return void
     */
    public function onGetProductTabsPost(array &$tabs, $lang_code)
    {
        if (empty($tabs)) {
            return;
        }
        foreach ($tabs as &$tab) {
            if (
                $tab['addon'] === 'product_bundles'
                && $tab['template'] === 'addons/product_bundles/blocks/product_bundles.tpl'
            ) {
                $tab['available_in_popup'] = Registry::get('addons.product_bundles.show_bundles') !== 'before_product_description';
                break;
            }
        }
        unset($tab);
    }
}
