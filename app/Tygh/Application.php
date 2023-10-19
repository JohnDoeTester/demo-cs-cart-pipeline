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

namespace Tygh;

use InvalidArgumentException;
use Pimple\Container;
use Tygh\Core\ApplicationInterface;
use Tygh\Core\BootstrapInterface;

/**
 * Application class provides methods for handling current request and stores common runtime state.
 * It is also an IoC container.
 *
 * @package Tygh
 *
 * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingTraversableTypeHintSpecification
 */
class Application extends Container implements ApplicationInterface
{
    /**
     * @var string Application root directory path
     */
    protected $root_path;

    /**
     * @var array
     */
    private $instance_ids = [];

    /**
     * Application constructor.
     *
     * @param string $root_path Root path
     */
    public function __construct($root_path)
    {
        parent::__construct();

        $this->registerCoreServices();
    }

    /**
     * @param string $root_path Application root directory path
     */
    protected function setRootPath($root_path)
    {
        $this->root_path = rtrim($root_path, '\\/');
    }

    /**
     * Registers core services at IoC container.
     *
     * @return void
     */
    protected function registerCoreServices()
    {
        $this['app'] = $this;
    }

    /**
     * @inheritdoc
     */
    public function getRootPath()
    {
        return $this->root_path;
    }

    /**
     * @inheritdoc
     */
    public function bootstrap(array $bootstrapper_list = [])
    {
        foreach ($bootstrapper_list as $bootstrapper) {
            if (is_string($bootstrapper) && is_a($bootstrapper, BootstrapInterface::class, true)) {
                $bootstrapper = new $bootstrapper();
            }

            if (!$bootstrapper instanceof BootstrapInterface) {
                throw new InvalidArgumentException(sprintf(
                    'An application bootstrapper must implement the %s interface.',
                    BootstrapInterface::class
                ));
            }

            /** @var BootstrapInterface $bootstrapper */
            $bootstrapper->boot($this);
        }
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        return $this[$id];
    }

    /**
     * @inheritdoc
     */
    public function has($id): bool
    {
        return isset($this[$id]);
    }

    /**
     * Returns a value indicating whether the container has already instantiated instance of the specified name.
     *
     * @param string $id Service name
     *
     * @return bool Whether the container has instance of class specified.
     */
    public function hasInstance($id)
    {
        return isset($this->instance_ids[$id]);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($id)
    {
        $value = parent::offsetGet($id);
        $this->instance_ids[$id] = $id;

        return $value;
    }
}

