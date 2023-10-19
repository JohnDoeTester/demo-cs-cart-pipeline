<?php

namespace Tygh;

use InvalidArgumentException;
use Tygh\Core\ApplicationInterface;

/**
 * Class Tygh is an utility class for creation and convenient access for currently running Application class instance.
 *
 * @package Tygh
 */
class Tygh
{
    /**
     * @var Application
     */
    public static $app;

    /**
     * Creates application object and registers it at static variable.
     *
     * @param string $class     Application class name
     * @param string $root_path Application root directory path
     *
     * @return Application
     */
    public static function createApplication($app_fqcn = '\Tygh\Application', $root_path = null)
    {
        if ($root_path === null) {
            if (defined('DIR_ROOT')) {
                $root_path = DIR_ROOT;
            } else {
                $root_path = dirname(dirname(__DIR__));
            }
        }

        if (!is_a($app_fqcn, ApplicationInterface::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'Application class must implement the %s interface.',
                ApplicationInterface::class
            ));
        }

        self::$app = new $app_fqcn($root_path);

        Registry::setAppInstance(self::$app);

        return self::$app;
    }
}