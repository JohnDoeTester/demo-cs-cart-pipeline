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

use Tygh\ContextMenu\Items\ActionItem;
use Tygh\ContextMenu\Items\ComponentItem;
use Tygh\ContextMenu\Items\GroupItem;
use Tygh\Exceptions\DeveloperException;

class ContextMenu
{
    /**
     * @var array<\Tygh\ContextMenu\MenuItemInterface>
     */
    protected $items;

    /**
     * @var \Tygh\ContextMenu\StatusSelector
     */
    protected $status_selector;

    /**
     * ContextMenu constructor.
     *
     * @param \Tygh\ContextMenu\StatusSelector           $status_selector Status selector
     * @param array<\Tygh\ContextMenu\MenuItemInterface> $items           Context menu items
     */
    public function __construct(StatusSelector $status_selector, array $items)
    {
        $this->items = $items;
        $this->status_selector = $status_selector;
    }

    /**
     * @param array $schema
     *
     * @return static
     *
     * @throws \Tygh\Exceptions\DeveloperException Undefined field exception in included items.
     */
    public static function createFromSchema(array $schema)
    {
        if (empty($schema['items'])) {
            throw new DeveloperException('Empty context menu schema');
        }

        $selectable_statuses = empty($schema['selectable_statuses'])
            ? []
            : $schema['selectable_statuses'];

        $status_selector = new StatusSelector($selectable_statuses);

        $schema['items'] = fn_sort_array_by_key($schema['items'], 'position', SORT_ASC);

        $items = [];
        foreach ($schema['items'] as $id => $item) {
            switch ($item['type']) {
                case ActionItem::class:
                    $items[$id] = ActionItem::createFromSchema($item);
                    break;
                case GroupItem::class:
                    $items[$id] = GroupItem::createFromSchema($item);
                    break;
                case ComponentItem::class:
                    $items[$id] = ComponentItem::createFromSchema($item);
                    break;
            }
        }

        return new static($status_selector, $items);
    }

    /**
     * @return \Tygh\ContextMenu\StatusSelector
     */
    public function getStatusSelector()
    {
        return $this->status_selector;
    }

    /**
     * Provides path for a template to render menu item with.
     *
     * @return string
     */
    public function getTemplate()
    {
        return 'components/context_menu/context_menu.tpl';
    }

    /**
     * @param array $request
     * @param array $auth
     * @param array $runtime
     *
     * @return array<\Tygh\ContextMenu\MenuItemInterface>
     */
    public function getAvailableItems(array $request, array $auth, array $runtime)
    {
        $available_items = [];

        foreach ($this->items as $id => $item) {
            if (!$item->isAvailable($request, $auth, $runtime)) {
                continue;
            }

            if ($item instanceof GroupItem) {
                $available_items[$id] = new GroupItem($item->getName(), $item->getAvailableItems($request, $auth, $runtime), $item->getData());
            } else {
                $available_items[$id] = $item;
            }
        }

        return $available_items;
    }
}
