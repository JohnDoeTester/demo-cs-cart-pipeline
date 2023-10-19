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

namespace Tygh\Helpdesk\AuthStorage;

interface StorageInterface
{
    /**
     * @param int $user_id          User ID
     * @param int $external_user_id Helpdesk user ID
     *
     * @return void
     */
    public function setId($user_id, $external_user_id);

    /**
     * @param int $user_id User ID
     *
     * @return void
     */
    public function resetId($user_id);

    /**
     * @param int $user_id User ID
     *
     * @return int|null
     */
    public function getId($user_id);
}
