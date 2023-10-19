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

namespace Tygh\Gdpr\DataUpdater;

/**
 * The interface of the data updater class responsible for updating data collection.
 *
 * @package Tygh\Gdpr\DataUpdater
 */
interface IDataUpdater
{
    /**
     * Updates user data
     *
     * @param array $user_data User data to update
     *
     * @return bool
     */
    public function update(array $user_data);
}
