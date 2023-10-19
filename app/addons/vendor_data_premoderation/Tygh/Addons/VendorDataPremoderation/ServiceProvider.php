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

namespace Tygh\Addons\VendorDataPremoderation;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Application;
use Tygh\Enum\YesNo;
use Tygh\Registry;
use Tygh\Tygh;

class ServiceProvider implements ServiceProviderInterface
{
    /** @inheritDoc */
    public function register(Container $app)
    {
        $app['addons.vendor_data_premoderation.product_premoderation_settings'] = function (Application $app) {
            $fields = Registry::ifGet('addons.vendor_data_premoderation.product_premoderation_fields', []);
            $fields = array_filter(
                $fields,
                function ($value) {
                    return YesNo::toBool($value);
                }
            );

            return new ProductPremoderationSettings(array_keys($fields));
        };

        $app['addons.vendor_data_premoderation.product_comparator'] = function (Application $app) {
            return new Comparator(static::getProductPremoderationSchema(), $app);
        };

        $app['addons.vendor_data_premoderation.product_state_factory'] = function (Application $app) {
            return new StateFactory(static::getProductStructure(), static::getProductDataLoader());
        };

        $app['addons.vendor_data_premoderation.product_data_loader'] = function (Application $app) {
            /** @var \Tygh\Database\Connection $db */
            $db = $app['db'];

            return static function ($name, $conditions, $joins = []) use ($db) {
                foreach ($joins as $table => &$join_conditions) {
                    $ons = [];
                    foreach ($join_conditions as $left_table_field => $right_table_field) {
                        $ons[] = db_quote('?:?p = ?:?p', $left_table_field, $right_table_field);
                    }
                    $join_conditions = db_quote('LEFT JOIN ?:?p ON ?p', $table, implode(' AND ', $ons));
                }
                unset($join_conditions);
                $joins = implode(' ', $joins);

                return $db->getArray('SELECT * FROM ?:?f ?p WHERE ?w', $name, $joins, $conditions);
            };
        };

        $app['addons.vendor_data_premoderation.product_structure'] = function (Application $app) {
            $schema = array_merge(
                [
                    'include_tables' => [],
                    'exclude_tables' => [],
                ],
                fn_get_schema('object_state', 'products')
            );

            $structure = [];

            /** @var \Tygh\Database\Connection $db */
            $db = $app['db'];

            list(, $all_tables) = fn_get_stats_tables();
            $table_name_prefix = $db->process('?:');
            foreach ($all_tables as $table_name) {
                if (strpos($table_name, $table_name_prefix) !== 0) {
                    continue;
                }

                $table_name = substr_replace($table_name, '', 0, strlen($table_name_prefix));
                if (in_array($table_name, $schema['exclude_tables'])) {
                    continue;
                }

                $table_fields = (array) $db->getTableFields($table_name);
                if (in_array('product_id', $table_fields)) {
                    $structure[$table_name] = [
                        'product_id' => StateFactory::OBJECT_ID_PLACEHOLDER,
                    ];
                }
            }

            $structure = array_merge($structure, $schema['include_tables']);

            return $structure;
        };

        $app['addons.vendor_data_premoderation.product_premoderation_schema'] = function (Application $app) {
            $schema = fn_get_schema('premoderation', 'products');

            return new PremoderationSchema($schema);
        };
    }

    /**
     * @return \Tygh\Addons\VendorDataPremoderation\PremoderationSchema
     */
    protected static function getProductPremoderationSchema()
    {
        return Tygh::$app['addons.vendor_data_premoderation.product_premoderation_schema'];
    }

    /**
     * @return array
     */
    protected static function getProductStructure()
    {
        return Tygh::$app['addons.vendor_data_premoderation.product_structure'];
    }

    /**
     * @return callable
     */
    protected static function getProductDataLoader()
    {
        return Tygh::$app['addons.vendor_data_premoderation.product_data_loader'];
    }

    /**
     * Gets comparator that can compare product states.
     *
     * @return \Tygh\Addons\VendorDataPremoderation\Comparator
     */
    public static function getProductComparator()
    {
        return Tygh::$app['addons.vendor_data_premoderation.product_comparator'];
    }

    /**
     * Gets factory that builds product states.
     *
     * @return \Tygh\Addons\VendorDataPremoderation\StateFactory
     */
    public static function getProductStateFactory()
    {
        return Tygh::$app['addons.vendor_data_premoderation.product_state_factory'];
    }
}
