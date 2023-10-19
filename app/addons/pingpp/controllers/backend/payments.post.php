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

use Tygh\Enum\Addons\Pingpp\Channels;
use Tygh\Payments\Addons\Pingpp\Pingpp;

/** @var string $mode */

if ($mode == 'processor') {
    $script = '';
    if (!empty($_REQUEST['processor_id'])) {
        $script = Tygh::$app['db']->getField(
            'SELECT processor_script FROM ?:payment_processors'
            . ' WHERE processor_id = ?i',
            $_REQUEST['processor_id']
        );
    } elseif (!empty($_REQUEST['payment_id'])) {
        $processor = fn_get_processor_data($_REQUEST['payment_id']);
        $script = $processor['processor_script'];
    }

    $processor_params = Tygh::$app['view']->getTemplateVars('processor_params');

    if ($script == Pingpp::getScriptName()) {
        Tygh::$app['view']->assign(array(
            'pingpp_channels' => fn_get_schema('pingpp', 'channels'),
            'wx_enabled'      =>
                !empty($processor_params['channels'][Channels::WX_LITE]['is_enabled'])
                && $processor_params['channels'][Channels::WX_LITE]['is_enabled'] == 'Y'
                ||
                !empty($processor_params['channels'][Channels::WX_PUB]['is_enabled'])
                && $processor_params['channels'][Channels::WX_PUB]['is_enabled'] == 'Y'
                ||
                !empty($processor_params['channels'][Channels::WX_WAP]['is_enabled'])
                && $processor_params['channels'][Channels::WX_WAP]['is_enabled'] == 'Y',
        ));
    }
}