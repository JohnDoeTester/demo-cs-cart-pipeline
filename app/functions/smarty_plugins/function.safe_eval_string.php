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
// phpcs:ignoreFile

use Tygh\SmartyEngine\StrongSecurity;

defined('BOOTSTRAP') or die('Access denied');

/**
 * Evaluates string which contains smarty syntax and falls back to custom error message instead fatal error
 *
 * Type:     function<br>
 * Name:     eval_string<br>
 *
 * @return string
 */
function smarty_function_safe_eval_string($params, &$smarty)
{
    try {
        $security = new StrongSecurity($smarty);
        $smarty->enableSecurity($security);

        $contents = $smarty->fetch('string:' . $params['var']);
    } catch (Exception $e) {
        $contents = $e->getMessage();
    }

    $smarty->disableSecurity();

    return $contents;
}
