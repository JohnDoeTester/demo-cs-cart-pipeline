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
// phpcs:disable

namespace Tygh\ContextMenu;

interface MenuItemInterface
{
    /**
     * Provides path for a template to render menu item with.
     *
     * @return string
     */
    public function getTemplate();

    /**
     * @return array
     */
    public function getData();

    /**
     * @param array $request
     * @param array $auth
     * @param array $runtime
     *
     * @return bool
     */
    public function isAvailable(array $request, array $auth, array $runtime);
}
