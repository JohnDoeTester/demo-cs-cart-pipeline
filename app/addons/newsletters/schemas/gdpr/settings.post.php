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

use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

$schema['newsletters_subscribe'] = array(
    'description_langvar'      => 'newsletters.subscribe_to_newsletters',
    'full_agreement_langvar'   => 'newsletters.agreement_text_full_subscribe_to_newsletters',
    'short_agreement_langvar'  => 'newsletters.agreement_text_short_subscribe_to_newsletters',
);

return $schema;
