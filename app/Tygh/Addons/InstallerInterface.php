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

namespace Tygh\Addons;

use Tygh\Core\ApplicationInterface;

/**
 * Interface InstallerInterface should be implemented by classes specified at add-on XML scheme v4 as custom installers.
 *
 * @package Tygh\Addons
 */
interface InstallerInterface
{
    /**
     * @return InstallerInterface
     */
    public static function factory(ApplicationInterface $app);

    /**
     * @return void
     */
    public function onBeforeInstall();

    /**
     * @return void
     */
    public function onInstall();

    /**
     * @return void
     */
    public function onUninstall();
}