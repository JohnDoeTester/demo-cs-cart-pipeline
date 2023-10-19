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
 * This schema describes synchronization providers and the data sources that will be used to show synchronization information.
 *
 * You can use the following array structure in your addon to specify your synchronization provider:
 *
 * '%SYNC_PROVIDER_ID' => [      - synchronization provider identifier
 *     'name'           => ''    - name of the synchronization - wil be shown on the sync_data.manage page
 *     'update_template => ''    - path to the template of the sync_data.update page
 *     'last_sync_info' => [
 *         'function' => ''      - callable function to get information of last synchronization. It will provides $provider_id and $company_id @see fn_sync_data_commerceml_get_last_sync_info()
 *     ]
 * ];
 *
 * last_sync_info function must provide the following array:
 *
 * array{
 *     status: string,               - status of the last synchronization
 *     last_sync_timestamp: int,     - timestamp of the last synchronization
 *     log_file_url: string,         - url to log file (can be empty)
 *     status_code?: string          - code of the status (can be NULL)
 * }
 */

return [];
