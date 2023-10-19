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

namespace Tygh\Providers;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\BlockManager\Layout;
use Tygh\Common\Robots;
use Tygh\Embedded;
use Tygh\Enum\SiteArea;
use Tygh\Registry;
use Tygh\Storefront\RelationsManager;
use Tygh\Storefront\Factory;
use Tygh\Storefront\Normalizer;
use Tygh\Storefront\Repository;
use Tygh\Tygh;

class StorefrontProvider implements ServiceProviderInterface
{
    /**
     * @var array
     */
    protected $params;

    /**
     * @var string
     */
    protected $url;

    /**
     * StorefrontProvider constructor.
     *
     * @param string $url
     * @param array  $request
     */
    public function __construct($url, array $request = [])
    {
        $this->url = $url;
        $this->params = $request;
    }

    /**
     * Gets current storefront.
     *
     * @return \Tygh\Storefront\Storefront
     */
    public static function getStorefront()
    {
        return Tygh::$app['storefront'];
    }

    /**
     * @inheritdoc
     */
    public function register(Container $app)
    {
        $app['storefront.repository'] = function (Container $app) {
            return new Repository(
                $app['db'],
                $app['storefront.factory'],
                $app['storefront.normalizer'],
                $app['storefront.relations_manager'],
                new Robots()
            );
        };

        $app['storefront.repository.init'] = function (Container $app) {
            return new Repository(
                $app['db'],
                $app['storefront.factory.init'],
                $app['storefront.normalizer'],
                $app['storefront.relations_manager.init'],
                new Robots()
            );
        };

        $app['storefront.factory'] = function (Container $app) {
            return new Factory(
                $app['db'],
                $app['storefront.relations_manager'],
                $app['storefront.normalizer']
            );
        };

        $app['storefront.factory.init'] = function (Container $app) {
            return new Factory(
                $app['db'],
                $app['storefront.relations_manager.init'],
                $app['storefront.normalizer']
            );
        };

        $app['storefront.relations_schema'] = function (Container $app) {
            $schema = fn_get_schema('storefronts', 'relations');

            return $schema;
        };

        $app['storefront.relations_schema.init'] = function (Container $app) {
            $schema = [
                'country_codes' => [
                    'table'               => '?:storefronts_countries',
                    'table_alias'         => 'countries',
                    'type'                => 'value',
                    'id_field'            => 'country_code',
                    'storefront_id_field' => 'storefront_id',
                ],
                'company_ids'   => [
                    'table'               => '?:storefronts_companies',
                    'table_alias'         => 'companies',
                    'type'                => 'value',
                    'id_field'            => 'company_id',
                    'storefront_id_field' => 'storefront_id',
                ],
            ];

            return $schema;
        };

        $app['storefront.relations_manager'] = function (Container $app) {
            return new RelationsManager(
                $app['db'],
                $app['storefront.relation_name_resolver'],
                $app['storefront.relations_schema']
            );
        };

        $app['storefront.relations_manager.init'] = function (Container $app) {
            return new RelationsManager(
                $app['db'],
                $app['storefront.relation_name_resolver'],
                $app['storefront.relations_schema.init']
            );
        };

        $app['storefront.normalizer'] = function (Container $app) {
            return new Normalizer();
        };

        $app['storefront.relation_name_resolver'] = function (Container $app) {
            return 'fn_uncamelize';
        };

        $app['storefront'] = function (Container $app) {
            /** @var \Tygh\Storefront\Repository $repository */
            $repository = $app['storefront.repository'];
            $storefront_id = $storefront = null;
            $is_storefront_stored = false;
            $runtime_company_id = fn_get_runtime_company_id();

            if (defined('CONSOLE') && isset($this->params['switch_storefront_id'])) {
                $storefront_id = (int) $this->params['switch_storefront_id'];
            } elseif (isset($this->params['s_storefront'])) {
                $storefront_id = (int) $this->params['s_storefront'];
                $is_storefront_stored = true;
            } elseif (SiteArea::isStorefront(AREA) && Registry::get('runtime.storefront_id')) {
                $storefront_id = Registry::get('runtime.storefront_id');
            }

            $embedded_suffix = Embedded::isEnabled()
                ? '_embedded'
                : '';
            $key_name = 'stored_storefront' . $embedded_suffix;
            $storefront_data = fn_get_session_data($key_name);

            if (is_array($storefront_data)) {
                $storefront = $repository->findById($storefront_data['storefront_id']);
                if (!$storefront) {
                    fn_set_session_data($key_name, $storefront);
                }
            }

            if ($storefront_id) {
                $storefront = $repository->findById($storefront_id);
            }

            if ($runtime_company_id
                && (!$storefront
                    || $storefront->getCompanyIds()
                    && !in_array($runtime_company_id, $storefront->getCompanyIds())
                )
            ) {
                $storefront = $repository->findAvailableForCompanyId($runtime_company_id);
            }

            if (!$storefront) {
                $storefront = $repository->findByUrl($this->url);
            }
            if (!$storefront) {
                $storefront = $repository->findDefault();
            }

            if ($is_storefront_stored) {
                fn_set_session_data($key_name, $storefront->toArray());
            }

            return $storefront;
        };

        $app['storefront.switcher.selected_storefront_id'] = function (Container $app) {
            $runtime_company_id = fn_get_runtime_company_id();
            $storefront_id = 0;

            if ($runtime_company_id) {
                /** @var \Tygh\Storefront\Repository $repository */
                $repository = $app['storefront.repository'];

                /** @var \Tygh\Storefront\Storefront $storefront */
                $storefront = $repository->findAvailableForCompanyId($runtime_company_id);

                if ($storefront) {
                    $storefront_id = $storefront->storefront_id;
                }
            }

            if (isset($this->params['s_storefront'])) {
                $storefront_id = $this->params['s_storefront'];
            }

            return $storefront_id;
        };

        $app['storefront.switcher.dispatches_schema'] = static function () {
            if (fn_allowed_for('MULTIVENDOR') && !empty(fn_get_runtime_company_id())) {
                return fn_get_schema('storefronts', 'switcher_dispatches_vendor');
            }

            return fn_get_schema('storefronts', 'switcher_dispatches');
        };

        $app['storefront.switcher.is_available_for_dispatch'] = function (Container $app) {
            $controller = Registry::get('runtime.controller');
            $mode = Registry::get('runtime.mode');
            $action = Registry::get('runtime.action');

            $dispatches = [
                sprintf('%s.%s.%s', $controller, $mode, $action),
                sprintf('%s.%s', $controller, $mode),
                $controller
            ];

            $schema = $app['storefront.switcher.dispatches_schema'];

            foreach ($dispatches as $dispatch) {
                if (!isset($schema[$dispatch])) {
                    continue;
                }

                $value = $schema[$dispatch];

                if (is_callable($value)) {
                    return call_user_func($value, $this->params);
                }

                if (is_array($value)) {
                    $allow = isset($value['allow']) ? (bool) $value['allow'] : true;

                    if (isset($value['variants'])) {
                        foreach ($value['variants'] as $variant) {
                            if (empty($variant['params']) || !isset($variant['allow'])) {
                                continue;
                            }

                            if (array_intersect_assoc((array) $variant['params'], $this->params)) {
                                return $variant['allow'];
                            }
                        }
                    }

                    return $allow;
                }

                return $value;
            }

            if (fn_allowed_for('MULTIVENDOR') && !empty(fn_get_runtime_company_id())) {
                return false;
            }

            return true;
        };

        $app['storefront.switcher.is_enabled'] = function (Container $app) {
            return fn_check_change_storefront_permission()
                && (fn_allowed_for('ULTIMATE') || fn_allowed_for('MULTIVENDOR:ULTIMATE'));
        };

        $app['storefront.switcher.preset_data.factory'] = function (Container $app) {
            return function ($current_storefront_id, $storefronts_threshold = 3) use ($app) {
                $is_ultimate = fn_allowed_for('ULTIMATE');
                $result = [
                    'storefronts' => [],
                    'threshold'   => $storefronts_threshold
                ];

                /** @var \Tygh\Storefront\Repository $repository */
                $repository = $app['storefront.repository'];

                if (fn_allowed_for('MULTIVENDOR') && !empty(Registry::get('runtime.company_id'))) {
                    /** @var \Tygh\Storefront\Storefront[] $storefronts */
                    $storefronts = $repository->findAvailableForCompanyId((int) Registry::get('runtime.company_id'), false);
                } else {
                    /** @var \Tygh\Storefront\Storefront[] $storefronts */
                    list($storefronts) = $repository->find(['get_total' => false]);
                }

                if (empty($storefronts)) {
                    return false;
                }

                /** @var \Tygh\Storefront\Storefront[] $visible_storefronts */
                $visible_storefronts = array_slice($storefronts, 0, $storefronts_threshold, true);

                if (!isset($visible_storefronts[$current_storefront_id]) && isset($storefronts[$current_storefront_id])) {
                    array_pop($visible_storefronts);
                    $visible_storefronts[$current_storefront_id] = $storefronts[$current_storefront_id];
                }

                foreach ($visible_storefronts as $storefront) {
                    $storefront_id = $storefront->storefront_id;
                    $company_id = 0;

                    if ($is_ultimate) {
                        $storefront_company_ids = $storefront->getCompanyIds();
                        $company_id = reset($storefront_company_ids);
                    }

                    $layout = Layout::instance($company_id, [], $storefront->storefront_id)->getDefault($storefront->theme_name);
                    $layout_id = isset($layout['layout_id']) ? $layout['layout_id'] : null;
                    $style_id = isset($layout['style_id']) ? $layout['style_id'] : null;

                    $storefront_logos = fn_get_logos($company_id, $layout_id, $style_id, $storefront->storefront_id);

                    $result['storefronts'][] = [
                        'storefront_id' => $storefront_id,
                        'company_id'    => $company_id,
                        'name'          => $storefront->name,
                        'is_default'    => $storefront->is_default,
                        'is_selected'   => $storefront_id == $current_storefront_id,
                        'status'        => $storefront->status,
                        'images'        => !empty($storefront_logos['theme']['image']) ? $storefront_logos['theme']['image'] : ''
                    ];
                }

                return $result;
            };
        };
    }

    /**
     * Gets storefront repository.
     *
     * @return \Tygh\Storefront\Repository
     */
    public static function getRepository()
    {
        return Tygh::$app['storefront.repository'];
    }
}
