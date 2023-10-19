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
use Tygh\Enum\SiteArea;
use Tygh\Exceptions\DeveloperException;

class ActionItem implements MenuItemInterface
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
     * @var string
     */
    protected $dispatch;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var \Closure|null
     */
    protected $permission_callback;

    /**
     * ActionItem constructor.
     *
     * @param array         $name
     * @param string        $dispatch
     * @param array         $data
     * @param \Closure|null $permission_callback
     */
    public function __construct(array $name, $dispatch, array $data = [], Closure $permission_callback = null)
    {
        $this->name = $name;
        $this->dispatch = $dispatch;
        $this->data = $data;
        $this->permission_callback = $permission_callback;
    }

    /**
     * @param array $schema
     *
     * @return static
     *
     * @throws \Tygh\Exceptions\DeveloperException Undefined dispatch exception.
     */
    public static function createFromSchema(array $schema)
    {
        if (empty($schema['dispatch'])) {
            throw new DeveloperException('Dispatch must be defined in ActionItem schema');
        }

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

        $data = empty($schema['data'])
            ? []
            : $schema['data'];

        $permission_callback = empty($schema['permission_callback'])
            ? null
            : $schema['permission_callback'];

        return new static($name, $schema['dispatch'], $data, $permission_callback);
    }

    /**
     * @return bool
     */
    protected function isDispatchAvailable()
    {
        list($controller, $mode) = explode('.', $this->dispatch);
        $request = [];
        if (strpos($this->dispatch, '?') !== false) {
            list(, $query_string) = explode('?', $this->dispatch);
            parse_str($query_string, $request);
        }

        return fn_check_permissions($controller, $mode, 'admin', 'post', $request, SiteArea::ADMIN_PANEL);
    }

    /** @inheritDoc */
    public function isAvailable(array $request, array $auth, array $runtime)
    {
        $is_available = $this->isDispatchAvailable();
        if ($this->permission_callback) {
            $is_available = $is_available && call_user_func($this->permission_callback, $request, $auth, $runtime);
        }

        return $is_available;
    }

    /** @inheritDoc */
    public function getTemplate()
    {
        return 'components/context_menu/items/action.tpl';
    }

    /** @inheritDoc */
    public function getData()
    {
        $this->data['name'] = $this->name;
        $this->data['dispatch'] = $this->dispatch;

        return $this->data;
    }
}
