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

namespace Tygh\Providers;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Application;
use Tygh\Languages\Languages;
use Tygh\Registry;
use Tygh\Tools\Backup\DatabaseBackupperFactory;
use Tygh\Tools\Formatter;
use Tygh\Web\Antibot;
use Tygh\Web\Antibot\NullDriver;

/**
 * The provider class that registers factories to create backuppers.
 *
 * @package Tygh\Providers
 */
class BackupperProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $app)
    {
        $app['backupper.database'] = function ($app) {
            return new DatabaseBackupperFactory();
        };
    }
}
