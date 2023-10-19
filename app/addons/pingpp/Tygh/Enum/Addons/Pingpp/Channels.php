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

namespace Tygh\Enum\Addons\Pingpp;

use ReflectionClass;

class Channels
{
    const ALIPAY_PC_DIRECT = 'alipay_pc_direct';
    const ALIPAY_WAP = 'alipay_wap';
    const ALIPAY_QR = 'alipay_qr';
    const BFB_WAP = 'bfb_wap';
    const FQLPAY_WAP = 'fqlpay_wap';
    const JDPAY_WAP = 'jdpay_wap';
    const QGBC_WAP = 'qgbc_wap';
    const UPACP_PC = 'upacp_pc';
    const UPACP_WAP = 'upacp_wap';
    const WX_LITE = 'wx_lite';
    const WX_PUB = 'wx_pub';
    const WX_PUB_QR = 'wx_pub_qr';
    const WX_WAP = 'wx_wap';
    const CP_B2B = 'cp_b2b';
    const YEEPAY_WAP = 'yeepay_wap';

    public function getAll()
    {
        $reflector = new ReflectionClass(__CLASS__);

        return $reflector->getConstants();
    }
}