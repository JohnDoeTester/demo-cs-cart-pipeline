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

namespace Tygh\Gdpr\DataModifier;

/**
 * The interface of the data modifier class responsible for modifying data collection.
 *
 * @package Tygh\Gdpr\DataModifier
 */
interface IDataModifier
{
    /**
     * Modifies provided user data
     *
     * @param array $user_data Raw user data array to be modifier
     *
     * @return mixed
     */
    public function modify(array $user_data);
}
