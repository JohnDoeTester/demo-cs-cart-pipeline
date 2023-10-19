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

namespace Tygh\Addons\MobileApp;

use Tygh\Storage;

/**
 * Provides methods for handling google-services config file.
 *
 * @package Tygh\Addons\MobileApp\GoogleServicesConfig
 */
class GoogleServicesConfig
{
    /** @var string */
    protected static $file_path = 'mobile_app';

    /** @var string */
    protected static $file_name = 'google-services.json';

    /**
     * Uploads data
     *
     * @param array<array<string|int>> $uploaded_data Uploaded data
     * @param int                      $storefront_id Storefront identifier
     *
     * @return bool
     */
    public static function upload($uploaded_data, $storefront_id = 0)
    {
        if (empty($uploaded_data['google_services_config_file']['path'])) {
            return false;
        }

        list($size) = Storage::instance('downloads')->put(self::getFullFilePath($storefront_id), [
            'file'      => $uploaded_data['google_services_config_file']['path'],
            'overwrite' => true,
        ]);

        return $size > 0;
    }

    /**
     * Checks if a file exists
     *
     * @param int $storefront_id Storefront identifier
     *
     * @return bool
     */
    public static function isExist($storefront_id = 0)
    {
        return Storage::instance('downloads')->isExist(self::getFullFilePath($storefront_id));
    }

    /**
     * Gets file path
     *
     * @param int $storefront_id Storefront identifier
     *
     * @return string
     */
    public static function getFilePath($storefront_id = 0)
    {
        return Storage::instance('downloads')->getAbsolutePath(self::getFullFilePath($storefront_id));
    }

    /**
     * Gets the file
     *
     * @param int $storefront_id Storefront identifier
     *
     * @return bool
     */
    public static function getFile($storefront_id = 0)
    {
        return Storage::instance('downloads')->get(self::getFullFilePath($storefront_id));
    }

    /**
     * Deletes the file
     *
     * @param int $storefront_id Storefront identifier
     *
     * @return bool
     */
    public static function deleteFile($storefront_id = 0)
    {
        return Storage::instance('downloads')->delete(self::getFullFilePath($storefront_id));
    }

    /**
     * Gets full file path
     *
     * @param int $storefront_id Storefront identifier
     *
     * @return string
     */
    public static function getFullFilePath($storefront_id)
    {
        return implode(DIRECTORY_SEPARATOR, [self::$file_path, $storefront_id, self::$file_name]);
    }
}
