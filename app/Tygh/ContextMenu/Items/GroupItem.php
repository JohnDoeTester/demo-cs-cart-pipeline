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

namespace Tygh\ContextMenu\Items;

use Closure;
use Tygh\ContextMenu\MenuItemInterface;

class GroupItem implements MenuItemInterface
{
    /**
     * @var array<string, string|array<string, string>>
     *
     * @psalm-var array{
     *   template: string,
     *   params: array<string, string>
     * }
     */
    protected $name;

    /**
     * @var array<MenuItemInterface> $items
     */
    protected $items;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var \Closure|null
     */
    protected $permission_callback;

    /**
     * ItemGroup constructor.
     *
     * @param array                    $name
     * @param array<MenuItemInterface> $items
     * @param array                    $data
     * @param \Closure|null            $permission_callback
     */
    public function __construct(array $name, array $items, array $data = [], Closure $permission_callback = null)
    {
        $this->name = $name;
        $this->items = $items;
        $this->data = $data;
        $this->permission_callback = $permission_callback;
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
        $schema['name'] = empty($schema['name'])
            ? []
            : $schema['name'];

        $name = array_merge(
            [
                'template' => '',
                'params'   => [],
            ],
            $schema['name']
        );

        $schema['items'] = empty($schema['items'])
            ? []
            : fn_sort_array_by_key($schema['items'], 'position', SORT_ASC);

        $data = empty($schema['data'])
            ? []
            : $schema['data'];

        $permission_callback = empty($schema['permission_callback'])
            ? null
            : $schema['permission_callback'];

        $items = [];
        foreach ($schema['items'] as $id => $item) {
            $type = isset($item['type'])
                ? $item['type']
                : GroupActionItem::class;

            switch ($type) {
                case GroupActionItem::class:
                    $items[$id] = GroupActionItem::createFromSchema($item);
                    break;
                case ComponentItem::class:
                    $items[$id] =  ComponentItem::createFromSchema($item);
                    break;
                case DividerItem::class:
                default:
                    $items[$id] =  new DividerItem();
            }
        }

        return new static($name, $items, $data, $permission_callback);
    }

    /**
     * @param array $request
     * @param array $auth
     * @param array $runtime
     *
     * @return bool
     */
    public function isAvailable(array $request, array $auth, array $runtime)
    {
        $is_available = true;
        if (is_callable($this->permission_callback)) {
            $is_available = call_user_func($this->permission_callback, $request, $auth, $runtime);
        }

        return $is_available && (bool) $this->getAvailableItems($request, $auth, $runtime);
    }

    /** @inheritDoc */
    public function getTemplate()
    {
        return 'components/context_menu/items/group.tpl';
    }

    /** @inheritDoc */
    public function getData()
    {
        $this->data['name'] = $this->name;

        return $this->data;
    }

    /**
     * @param array $request
     * @param array $auth
     * @param array $runtime
     *
     * @return array<MenuItemInterface>
     */
    public function getAvailableItems(array $request, array $auth, array $runtime)
    {
        $available_items = array_filter(
            $this->items,
            static function (MenuItemInterface $item) use ($request, $auth, $runtime) {
                return $item->isAvailable($request, $auth, $runtime);
            }
        );

        return $this->removeExtraDividers($available_items);
    }

    /**
     * @return array<string, string|array<string, string>>
     *
     * @psalm-return array{
     *   template: string,
     *   params: array<string, string>
     * }
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array<\Tygh\ContextMenu\MenuItemInterface>
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param array<\Tygh\ContextMenu\MenuItemInterface> $items
     *
     * @return array<\Tygh\ContextMenu\MenuItemInterface>
     */
    public function removeExtraDividers(array $items)
    {
        $previous_item = reset($items);
        foreach ($items as $item_id => $item) {
            if (
                $previous_item instanceof DividerItem
                && $item instanceof DividerItem
            ) {
                unset($items[$item_id]);
            } else {
                $previous_item = $item;
            }
        }

        end($items);
        $last_item_id = key($items);
        if (
            isset($items[$last_item_id])
            && $items[$last_item_id] instanceof DividerItem
        ) {
            unset($items[$last_item_id]);
        }

        return $items;
    }
}
