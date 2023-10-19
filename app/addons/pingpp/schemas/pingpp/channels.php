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

/**
 * Scopes are set accordingly to https://github.com/PingPlusPlus/pingpp-js/blob/master/README.md
 */
$schema = array(
    Channels::ALIPAY_WAP       => array(
        'scope' => array('mobile'),
    ),
    Channels::ALIPAY_QR        => array(
        'scope' => array('pc'),
    ),
    Channels::ALIPAY_PC_DIRECT => array(
        'scope' => array('pc'),
    ),
    Channels::WX_PUB           => array(
        'scope' => array('wx'),
    ),
    Channels::WX_LITE          => array(
        'scope' => array('wx'),
    ),
    Channels::WX_WAP           => array(
        'scope' => array('mobile'),
    ),
    Channels::WX_PUB_QR        => array(
        'scope' => array('pc'),
    ),
    Channels::BFB_WAP          => array(
        'scope' => array('mobile'),
    ),
    // cp_b2b can't return customer to a store, so this channel is disabled
    /*
    Channels::CP_B2B           => array(
        'scope' => array('pc'),
    ),
    */
    Channels::UPACP_WAP        => array(
        'scope' => array('mobile'),
    ),
    Channels::UPACP_PC         => array(
        'scope' => array('pc'),
    ),
    Channels::YEEPAY_WAP       => array(
        'scope'    => array('mobile'),
        'settings' => array(
            'product_category' => array(
                'type' => 'text',
            ),
            'identity_id'      => array(
                'type' => 'text',
            ),
            'identity_type'    => array(
                'type' => 'text',
            ),
            'terminal_type'    => array(
                'type'     => 'select',
                'variants' => array(0, 1, 2, 3),
            ),
            'terminal_id'      => array(
                'type' => 'text',
            ),
        ),
    ),
    Channels::JDPAY_WAP        => array(
        'scope' => array('mobile'),
    ),
    Channels::FQLPAY_WAP       => array(
        'scope'    => array('mobile'),
        'settings' => array(
            'c_merch_id' => array(
                'type' => 'text',
            ),
        ),
    ),
    Channels::QGBC_WAP         => array(
        'scope'    => array('mobile'),
        'settings' => array(
            'phone' => array(
                'type' => 'text',
            ),
        ),
    ),
);

return $schema;