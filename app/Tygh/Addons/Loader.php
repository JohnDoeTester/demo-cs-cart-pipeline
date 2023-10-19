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

namespace Tygh\Addons;

use Composer\Autoload\ClassLoader;
use Tygh\Core\ApplicationInterface;
use Tygh\Core\HookHandlerProviderInterface;
use Tygh\Exceptions\DeveloperException;
use Closure;
use Tygh\Registry;

/**
 * Class Loader is used to bootstrap modern v4 add-ons.
 *
 * @package Tygh\Addons
 */
class Loader
{
    /**
     * @var \Composer\Autoload\ClassLoader
     */
    protected $class_loader;

    /**
     * @var \Tygh\Core\ApplicationInterface
     */
    protected $application;

    public function __construct(ClassLoader $class_loader, ApplicationInterface $application)
    {
        $this->class_loader = $class_loader;
        $this->application = $application;
    }

    public function bootstrap(XmlScheme4 $addon_scheme, $register_hooks = true)
    {
        $addon_scheme->registerAutoloadEntries();

        if ($addon_class = $addon_scheme->getBootstrapClass()) {
            /** @var \Tygh\Core\HookHandlerProviderInterface|\Tygh\Core\BootstrapInterface $bootstrapper */
            $bootstrapper = new $addon_class;

            if ($register_hooks && $bootstrapper instanceof HookHandlerProviderInterface) {
                $this->registerHooksFromHookProvider($addon_scheme->getId(), $bootstrapper);
            }

            $this->application->bootstrap([$bootstrapper]);
        }
    }

    /**
     * Registers hook handlers provided by add-on bootstrapper.
     *
     * @param string                                  $base_addon_id
     * @param \Tygh\Core\HookHandlerProviderInterface $hook_handler_provider Add-on bootstrap instance
     *
     * @throws \Tygh\Exceptions\DeveloperException
     */
    protected function registerHooksFromHookProvider($base_addon_id, HookHandlerProviderInterface $hook_handler_provider)
    {
        $hooks = Registry::get('hooks');

        $base_priority = Registry::get('addons.' . $base_addon_id . '.priority');

        foreach ($hook_handler_provider->getHookHandlerMap() as $hook_name => $hook_handler_definition) {
            if (is_object($hook_handler_definition) && $hook_handler_definition instanceof Closure) {
                $hook_handler_definition = [
                    'hook'     => $hook_name,
                    'handler'  => $hook_handler_definition,
                    'priority' => null,
                    'addon'    => null,
                ];
            } elseif (!isset($hook_handler_definition['hook'])) {
                $hook_handler_definition = [
                    'hook'     => $hook_name,
                    'handler'  => $hook_handler_definition,
                    'priority' => isset($hook_handler_definition[2]) ? $hook_handler_definition[2] : null,
                    'addon'    => isset($hook_handler_definition[3]) ? $hook_handler_definition[3] : null,
                ];
            }

            $hook_name = $hook_handler_definition['hook'];
            $hook_handler = $hook_handler_definition['handler'];
            $addon_id = $base_addon_id;
            $priority = $base_priority;

            if (!isset($hooks[$hook_name])) {
                $hooks[$hook_name] = [];
            }

            if (isset($hook_handler_definition['priority'])) {
                $priority = $hook_handler_definition['priority'];
            }

            if (isset($hook_handler_definition['addon'])) {
                $addon_id = $hook_handler_definition['addon'];

                if (Registry::get('addons.' . $addon_id . '.status') !== 'A') { // skip hook registration if addon is not enabled
                    continue;
                }

                if (!isset($hook_handler_definition['priority'])) {
                    $priority = Registry::get('addons.' . $addon_id . '.priority');
                }
            }

            $hooks[$hook_name][] = [
                'func'     => $this->resolveHookProviderHandlerCallback($hook_handler),
                'addon'    => $addon_id,
                'priority' => $priority,
            ];
        };

        Registry::set('hooks', $hooks, true);
    }

    /**
     * @param callable|array $callable
     *
     * @return callable
     */
    protected function resolveHookProviderHandlerCallback($callable)
    {
        return function (&...$args) use ($callable) {
            if (is_array($callable) && isset($callable[0], $callable[1])) {
                $container_component = $callable[0];
                $container_component_method = $callable[1];

                if ($this->application->has($container_component)) {
                    return call_user_func_array(
                        [
                            $this->application[$container_component],
                            $container_component_method,
                        ],
                        $args
                    );
                }
            }

            if (is_callable($callable)) {
                return call_user_func_array($callable, $args);
            }

            throw new DeveloperException(
                'Hook handler definition is neither of callable type, nor pointing to any container component method.'
            );
        };
    }

    public function __debugInfo()
    {
        return [];
    }
}
