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
use Tygh\Exceptions\DeveloperException;

class ComponentItem implements MenuItemInterface
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
    protected $template;

    /**
     * @var \Closure|null
     */
    protected $permission_callback;

    /**
     * @var \Closure|null
     */
    protected $data_provider;

    /**
     * @var array|null
     */
    protected $data = null;

    /**
     * ComponentItem constructor.
     *
     * @param array        $name
     * @param string       $template
     * @param Closure|null $permission_callback
     * @param Closure|null $data_provider
     */
    public function __construct(
        array $name,
        $template,
        Closure $permission_callback = null,
        Closure $data_provider = null
    ) {
        $this->name = $name;
        $this->template = $template;
        $this->permission_callback = $permission_callback;
        $this->data_provider = $data_provider;
    }

    /** @inheritDoc */
    public function isAvailable(array $request, array $auth, array $runtime)
    {
        if (!$this->permission_callback) {
            return true;
        }

        return call_user_func($this->permission_callback, $request, $auth, $runtime);
    }

    /**
     * @param array $schema
     *
     * @return static
     *
     * @throws \Tygh\Exceptions\DeveloperException Undefined template exception.
     */
    public static function createFromSchema(array $schema)
    {
        if (empty($schema['template'])) {
            throw new DeveloperException('Template must be defined in ComponentItem schema');
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

        $template = $schema['template'];

        $permission_callback = empty($schema['permission_callback'])
            ? null
            : $schema['permission_callback'];

        $data_provider = empty($schema['data_provider'])
            ? static function () use ($schema) {
                return isset($schema['data'])
                    ? $schema['data']
                    : [];
            }
            : $schema['data_provider'];

        return new static($name, $template, $permission_callback, $data_provider);
    }

    /** @inheritDoc */
    public function getTemplate()
    {
        return $this->template;
    }

    /** @inheritDoc */
    public function getData()
    {
        if ($this->data === null && is_callable($this->data_provider)) {
            $this->data = call_user_func($this->data_provider);
        }

        $this->data['name'] = $this->name;

        return $this->data;
    }
}
