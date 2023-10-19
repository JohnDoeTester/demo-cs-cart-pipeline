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

namespace Tygh\Addons\VendorRating;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Addons\VendorRating\Calculator\Calculator;
use Tygh\Addons\VendorRating\Calculator\FormulaBackend;
use Tygh\Addons\VendorRating\Criteria\OrdersCriteria;
use Tygh\Addons\VendorRating\Criteria\ProductsCriteria;
use Tygh\Addons\VendorRating\Criteria\ReturnsCriteria;
use Tygh\Addons\VendorRating\Criteria\ReviewsCriteria;
use Tygh\Addons\VendorRating\Criteria\VendorPlansCriteria;
use Tygh\Addons\VendorRating\Criteria\VendorsCriteria;
use Tygh\Addons\VendorRating\HookHandlers\CompaniesHookHandler;
use Tygh\Addons\VendorRating\HookHandlers\LogHookHandler;
use Tygh\Addons\VendorRating\HookHandlers\MasterProductsHookHandler;
use Tygh\Addons\VendorRating\HookHandlers\ProductsHookHandler;
use Tygh\Addons\VendorRating\HookHandlers\VendorPlansHookHandler;
use Tygh\Addons\VendorRating\Rating\Storage;
use Tygh\Addons\VendorRating\Service\VendorPlanService;
use Tygh\Addons\VendorRating\Service\VendorService;
use Tygh\Application;
use Tygh\Enum\ObjectStatuses;
use Tygh\Registry;
use Tygh\Tygh;

/**
 * Class ServiceProvider is intended to register services and components of the "Product variations" add-on to the
 * application container.
 *
 * @package Tygh\Addons\ProductVariations
 *
 * @psalm-type CriteriaSchemaType = array{
 *   name: string[],
 *   description: string[],
 *   value_provider: string[],
 *   variable_name: string,
 *   addon?: string|array
 * }
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @return \Tygh\Addons\VendorRating\Service\VendorService
     */
    public static function getVendorService()
    {
        return Tygh::$app['addons.vendor_rating.vendor_service'];
    }

    /**
     * @return \Tygh\Addons\VendorRating\Service\VendorPlanService
     */
    public static function getVendorPlanService()
    {
        return Tygh::$app['addons.vendor_rating.vendor_plan_service'];
    }

    /**
     * @return \Tygh\Addons\VendorRating\Calculator\BackendInterface
     */
    public static function getCalculatorBackend()
    {
        return Tygh::$app['addons.vendor_rating.calculator_backend'];
    }

    /**
     * @inheritDoc
     */
    public function register(Container $app)
    {
        $app['addons.vendor_rating.criteria_schema'] = function (Application $app) {
            $addons = Registry::ifGet('addons', []);
            $active_addons = array_keys(
                array_filter(
                    $addons,
                    static function ($addon) {
                        return $addon['status'] === ObjectStatuses::ACTIVE;
                    }
                )
            );

            return $this->getActiveVariables($active_addons);
        };

        $app['addons.vendor_rating.orders_criteria'] = static function (Application $app) {
            return new OrdersCriteria($app, fn_get_settled_order_statuses());
        };

        $app['addons.vendor_rating.products_criteria'] = static function (Application $app) {
            return new ProductsCriteria($app);
        };

        $app['addons.vendor_rating.vendors_criteria'] = static function (Application $app) {
            return new VendorsCriteria();
        };

        $app['addons.vendor_rating.reviews_criteria'] = static function (Application $app) {
            return new ReviewsCriteria($app);
        };

        $app['addons.vendor_rating.vendor_plans_criteria'] = static function (Application $app) {
            return new VendorPlansCriteria($app);
        };

        $app['addons.vendor_rating.returns_criteria'] = static function (Application $app) {
            return new ReturnsCriteria($app);
        };

        $app['addons.vendor_rating.rating_storage'] = static function (Application $app) {
            return new Storage($app['db']);
        };

        $app['addons.vendor_rating.calculator'] = static function (Application $app) {
            return new Calculator(self::getCalculatorBackend());
        };

        $app['addons.vendor_rating.calculator_backend'] = static function (Application $app) {
            return new FormulaBackend();
        };

        $app['addons.vendor_rating.vendor_service'] = static function (Application $app) {
            $settings = Registry::get('addons.vendor_rating');

            return new VendorService(
                static::getCalculator(),
                static::getRatingStorage(),
                $app,
                $settings['formula'],
                static::getCriteriaSchema(),
                $settings['start_rating_period']
            );
        };

        $app['addons.vendor_rating.vendor_plan_service'] = static function (Application $app) {
            return new VendorPlanService(
                static::getRatingStorage()
            );
        };

        $app['addons.vendor_rating.hook_handlers.products'] = static function (Application $app) {
            return new ProductsHookHandler($app);
        };

        $app['addons.vendor_rating.hook_handlers.companies'] = static function (Application $app) {
            return new CompaniesHookHandler($app);
        };

        $app['addons.vendor_rating.hook_handlers.vendor_plans'] = static function (Application $app) {
            return new VendorPlansHookHandler($app);
        };

        $app['addons.vendor_rating.hook_handlers.log'] = static function (Application $app) {
            return new LogHookHandler();
        };

        $app['addons.vendor_rating.hook_handlers.master_products'] = static function (Application $app) {
            return new MasterProductsHookHandler();
        };
    }

    /**
     * @return \Tygh\Addons\VendorRating\Calculator\Calculator
     */
    public static function getCalculator()
    {
        return Tygh::$app['addons.vendor_rating.calculator'];
    }

    /**
     * @return \Tygh\Addons\VendorRating\Rating\Storage
     */
    public static function getRatingStorage()
    {
        return Tygh::$app['addons.vendor_rating.rating_storage'];
    }

    /**
     * @return array<CriteriaSchemaType>
     */
    public static function getCriteriaSchema()
    {
        return Tygh::$app['addons.vendor_rating.criteria_schema'];
    }

    /**
     * @param string[] $active_addons List of active modules
     *
     * @return array<CriteriaSchemaType>
     */
    private function getActiveVariables(array $active_addons)
    {
        $variables = fn_get_schema('vendor_rating', 'criteria');

        $active_variables = [];

        foreach ($variables as $variable) {
            if (!isset($variable['addon'])) {
                $active_variables[] = $variable;
            }
            if (is_string($variable['addon']) && in_array($variable['addon'], $active_addons, true)) {
                $active_variables[] = $variable;
            }

            if (!is_array($variable['addon']) || empty(array_intersect($active_addons, $variable['addon']))) {
                continue;
            }

            $active_variables[] = $variable;
        }

        return $active_variables;
    }
}
