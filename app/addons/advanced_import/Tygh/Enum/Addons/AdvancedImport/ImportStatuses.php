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

class ImportStatuses
{
    const NOOP = 'X';
    const IN_PROGRESS = 'P';
    const SUCCESS = 'S';
    const FAIL = 'F';

    public static function getAll()
    {
        return array(static::NOOP, static::IN_PROGRESS, static::SUCCESS, static::FAIL);
    }

    public static function getFinished()
    {
        return array(static::FAIL, static::SUCCESS);
    }
}