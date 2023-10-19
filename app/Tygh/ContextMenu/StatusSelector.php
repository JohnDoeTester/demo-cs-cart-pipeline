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

class StatusSelector
{
    /**
     * @var array<string, string> $statuses
     */
    protected $statuses;

    /**
     * StatusSelector constructor.
     *
     * @param array<string, string> $statuses Statuses
     */
    public function __construct(array $statuses = [])
    {
        $this->statuses = $statuses;
    }

    /**
     * @return array<string, string> $statuses
     */
    public function getStatuses()
    {
        return $this->statuses;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return 'components/context_menu/status_selector.tpl';
    }
}
