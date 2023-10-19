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

use Tygh\Providers\StorefrontProvider;

defined('BOOTSTRAP') or die('Access denied');

if ($mode === 'list') {
    $promotions = Tygh::$app['view']->getTemplateVars('promotions');
    $storefront = StorefrontProvider::getStorefront();
    $company_ids = $storefront->getCompanyIds();

    if (!empty($company_ids)) {
        $promotions = array_filter((array) $promotions, static function ($promotion) use ($company_ids) {
            /** @var array $promotion */
            return empty($promotion['company_id']) || in_array($promotion['company_id'], $company_ids);
        });
    }

    Tygh::$app['view']->assign('promotions', $promotions);
}
