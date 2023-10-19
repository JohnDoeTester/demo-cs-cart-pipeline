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
 * Query contains the possible multi query types.
 *
 * @package Tygh\Enum
 */
class MultiQueryTypes
{
    const ARR = 'array';
    const FIELD = 'field';
    const COLUMN = 'column';
    const ROW = 'row';
    const HASH = 'hash';
    const SINGLE_HASH = 'single_hash';
    const MULTI_HASH = 'multi_hash';
    const QUERY = 'query';
}
