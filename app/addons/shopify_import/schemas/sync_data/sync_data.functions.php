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

defined('BOOTSTRAP') or die('Access denied');

/**
 * Gets shopify import last sync information
 *
 * @param string $provider_id Provider identifier
 * @param int    $company_id  Company identifier
 *
 * @return array{status: string, last_sync_timestamp: int, log_file_url: string, status_code?: string}
 */
function fn_sync_data_shopify_import_get_last_sync_info($provider_id, $company_id)
{
    return fn_shopify_import_get_last_sync_info(['company_id' => $company_id]);
}
