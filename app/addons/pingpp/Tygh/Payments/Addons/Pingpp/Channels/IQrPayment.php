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

namespace Tygh\Payments\Addons\Pingpp\Channels;

interface IQrPayment extends IChannel
{
    /**
     * Provides QR code payment URL.
     *
     * @param array $charge Charge data from API
     *
     * @return mixed
     */
    public function getQrCodeUrl(array $charge);

    /**
     * Provides payment instructions.
     *
     * @return string
     */
    public function getInstructions();
}