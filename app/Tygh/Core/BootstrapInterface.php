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

namespace Tygh\Core;

/**
 * Interface BootstrapInterface should be implemented when you need to execute any code at application initialisation
 * phase. Remember that the bootstrapping is performed for each request or application runtime, meaning no heavy or
 * slow code should be placed to application bootstrapper. Usually you'll only want to register service providers here.
 *
 * @package Tygh\Core
 */
interface BootstrapInterface
{
    /**
     * @param \Tygh\Core\ApplicationInterface $app Application
     *
     * @return void
     */
    public function boot(ApplicationInterface $app);
}
