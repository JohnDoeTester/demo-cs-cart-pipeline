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

namespace Tygh\Api\Formats;

/**
 * Class FormData provides parsing for API requests with multipart/form-data content type.
 *
 * @package Tygh\Api\Formats
 */
class Form extends Text
{
    protected $mime_types = [
        'multipart/form-data',
        'application/x-www-form-urlencoded',
    ];

    public function decode($data)
    {
        $decoded_data = [];
        if (!empty($_POST)) {
            $decoded_data = $_POST;
        }

        return [$decoded_data, ''];
    }
}
