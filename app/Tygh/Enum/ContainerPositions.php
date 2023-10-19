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

namespace Tygh\Enum;

/**
 * Class ContainerPositions
 * @package Tygh\Enum
 */
class ContainerPositions
{
    const TOP_PANEL = 'TOP_PANEL';
    const HEADER = 'HEADER';
    const CONTENT = 'CONTENT';
    const FOOTER = 'FOOTER';

    public static function getAll()
    {
        return array(
            self::TOP_PANEL => self::TOP_PANEL,
            self::HEADER => self::HEADER,
            self::CONTENT => self::CONTENT,
            self::FOOTER => self::FOOTER,
        );
    }
}