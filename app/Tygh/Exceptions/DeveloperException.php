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

namespace Tygh\Exceptions;

class DeveloperException extends AException
{
    /**
     * @param string $cache_level Cache level
     */
    public static function undefinedCacheLevel($cache_level)
    {
        self::throwException(sprintf('Registry: undefined cache level %s', $cache_level));
    }

    public static function hookHandlerIsNotCallable($func)
    {
        self::throwException(sprintf('Hook %s is not callable', $func));
    }

    public static function undefinedStorageDriver()
    {
        self::throwException('Storage: undefined storage backend');
    }

    public static function undefinedStorageType($type)
    {
        self::throwException('Storage: undefined storage type - ' . $type);
    }

    public static function throwException($message)
    {
        throw new DeveloperException($message);
    }
}
