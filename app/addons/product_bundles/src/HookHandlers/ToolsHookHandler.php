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

use Tygh\Addons\ProductBundles\ServiceProvider;

class ToolsHookHandler
{
    /**
     * The `tools_change_status` hook handler.
     *
     * @param array<string> $params Parameters of request.
     * @param mixed         $result Result of updating status.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint
     *
     * @return void
     */
    public function onToolsChangeStatus(array $params, $result)
    {
        if (!$result) {
            return;
        }
        if ($params['table'] !== 'product_bundles') {
            return;
        }
        $bundle_service = ServiceProvider::getService();
        $bundle_service->updateBundleStatus((int) $params['id'], $params['status']);
    }
}
