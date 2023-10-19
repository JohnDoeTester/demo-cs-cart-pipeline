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

namespace Tygh\Enum\Addons\Searchanise;

/**
 * Class ServerErrors contains returned serachanise server errors.
 *
 * @package Tygh\Enum\Addons\Searchanise
 */
class ServerErrors
{
    public const EMPTY_API_KEY                   = 'EMPTY_API_KEY';
    public const INVALID_API_KEY                 = 'INVALID_API_KEY';
    public const TO_BIG_START_INDEX              = 'TO_BIG_START_INDEX';
    public const SEARCH_DATA_NOT_IMPORTED        = 'SEARCH_DATA_NOT_IMPORTED';
    public const FULL_IMPORT_PROCESSED           = 'FULL_IMPORT_PROCESSED';
    public const FACET_ERROR_TOO_MANY_ATTRIBUTES = 'FACET_ERROR_TOO_MANY_ATTRIBUTES';
    public const NEED_RESYNC_YOUR_CATALOG        = 'NEED_RESYNC_YOUR_CATALOG';
    public const ENGINE_SUSPENDED                = 'ENGINE_SUSPENDED';
}
