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

namespace Tygh\Addons\GraphqlApi;

class Cursor
{
    const SEPARATOR = ':';

    public $page;

    public $items_per_page;

    public function __construct($page, $items_per_page)
    {

        $this->page = $page;
        $this->items_per_page = $items_per_page;
    }

    public static function instanceByCursor($cursor)
    {
        $cursor_decoded = base64_decode($cursor);
        list($page, $items_per_page) = explode(self::SEPARATOR, $cursor_decoded);

        return new self($page, $items_per_page);
    }

    public function getValue()
    {
        $cursor = sprintf('%d%s%d', $this->page, self::SEPARATOR, $this->items_per_page);

        $cursor_encoded = base64_encode($cursor);

        return $cursor_encoded;
    }

    public static function getValueByPagination($page, $items_per_page)
    {
        $cursor = new self($page, $items_per_page);

        return $cursor->getValue();
    }
}
