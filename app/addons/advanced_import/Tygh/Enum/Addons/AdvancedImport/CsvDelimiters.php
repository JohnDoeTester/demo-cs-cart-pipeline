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

namespace Tygh\Enum\Addons\AdvancedImport;

class CsvDelimiters
{
    const SEMICOLON = 'S';
    const COMMA = 'C';
    const AUTO = 'A';
    const TAB = 'T';

    public static function getAll()
    {
        return array(
            self::SEMICOLON => ';',
            self::COMMA     => ',',
            self::TAB       => "\t",
        );
    }
}