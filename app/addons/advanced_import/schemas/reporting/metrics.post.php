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

use Tygh\Enum\Addons\AdvancedImport\ImportStatuses;

defined('BOOTSTRAP') or die('Access denied');

/** @var array $schema */

$schema['advanced_import'] = function () {

    return (bool) db_get_field(
        'SELECT preset_id FROM ?:import_preset_states WHERE last_status IN (?a) LIMIT 1',
        ImportStatuses::getFinished()
    );
};

return $schema;