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

namespace Tygh\Navigation\LastView;

use Tygh;
use Tygh\Registry;

/**
 * Last view abstract class
 *
 * phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
 * phpcs:disable SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
 * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingTraversableTypeHintSpecification
 * phpcs:disable SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator
 * phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
 * phpcs:disable SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
 * phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
 * phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
 * phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore
 *
 * @psalm-consistent-constructor
 */
abstract class ACommon
{
    /**
     * @var array
     */
    protected $_schema;

    /**
     * @var string
     */
    protected $_controller;

    /**
     * @var string
     */
    protected $_mode;

    /**
     * @var string
     */
    protected $_action;

    /**
     * @var array
     */
    protected $_auth;

    /**
     * @var string|null
     */
    protected $object_type;

    /**
     * Prepares params for search
     *
     * @param array $params Request params
     *
     * @return bool Always true
     */
    abstract public function prepare(&$params);

    /**
     * Init search view params
     *
     * @param string $object Object to init view for
     * @param array  $params Request parameters
     *
     * @return array Filtered params
     */
    abstract public function update($object, $params);

    /**
     * Gets current view data
     *
     * @return array View data
     */
    abstract protected function _getCurrentView();

    /**
     * Saves current view
     *
     * @param array $data View data
     *
     * @return bool Always true
     */
    abstract protected function _updateCurrentView($data);

    /**
     * Checks if prev/next links should be shown on current page
     *
     * @param array $params Page request params
     *
     * @return bool Result of checking
     */
    abstract protected function _isNeedViewTools($params);

    /**
     * Create new last view instance object
     *
     * @param string      $area       Area identifier
     * @param string|null $controller Controller to init Last view for.
     *                                If set to null will be detected automatically from the current dispatch
     * @param string|null $mode       Controller mode to init Last view for.
     *                                If set to null will be detected automatically from the current dispatch
     * @param string|null $action     Controller mode action to init Last view for.
     *                                If set to null will be detected automatically from the current dispatch
     */
    public function __construct($area = AREA, $controller = null, $mode = null, $action = null)
    {
        $schema_name = fn_get_area_name($area);
        $this->_controller = $controller ?: Registry::get('runtime.controller');
        $this->_mode = $mode ?: Registry::get('runtime.mode');
        $this->_action = $action ?: Registry::get('runtime.action');

        /** @var array $common_schema */
        $common_schema = fn_get_schema('last_view', $schema_name);
        $this->_schema = !empty($common_schema[$this->_controller]) ? $common_schema[$this->_controller] : [];

        $this->_auth = & \Tygh::$app['session']['auth'];

        if ($this->_schema) {
            $this->object_type = $this->_controller;
        }
    }

    /**
     * Inits Last View
     *
     * @param array $params Request params
     *
     * @return void
     */
    public function init(&$params)
    {
        /**
         * Executes before Last View initialized. Allows to modify request params.
         *
         * @param \Tygh\Navigation\LastView\ACommon $this
         * @param array                             $params Request params
         */
        fn_set_hook('last_view_init_pre', $this, $params);

        $this->_saveViewResults($params);

        if ($this->_isNeedViewTools($params) && !$this->_initViewTools($params)) {
             $this->_initDefaultViewTools($params);
        }
    }

    /**
     * Initiates default view tools
     * if item was not found in default result
     *
     * @param array $params Request params
     *
     * @return bool Flag that determines if tools were inited
     */
    public function _initDefaultViewTools($params)
    {
        return false;
    }

    /**
     * Initiates view tools
     *
     * @param array $params Request params
     *
     * @return bool Flag that determines if tools were inited
     */
    public function _initViewTools($params)
    {
        /**
         * Executes before Last View Tools initialized. Allows to modify request params.
         *
         * @param \Tygh\Navigation\LastView\ACommon $this
         * @param array                             $params Request params
         */
        fn_set_hook('last_view_init_view_tools_pre', $this, $params);

        $data = $this->_getCurrentView();

        if (empty($data)) {
            return false;
        }

        $view_results = unserialize($data['view_results']);
        if (empty($view_results['items_ids'])) {
            return false;
        }

        $items_ids = $view_results['items_ids'];

        $current_id = $params[$this->_schema['item_id']];
        $prev_id = $next_id = $current_page = $current_pos = 0;

        foreach ($items_ids as $page => $items) {
            if (in_array($current_id, $items)) {
                $items_count = count($items);

                for ($i = 0; $i < $items_count; $i++) {
                    if ($items[$i] == $current_id) {
                        $prev_id = !empty($items[$i - 1]) ? $items[$i - 1] : 0;
                        $next_id = !empty($items[$i + 1]) ? $items[$i + 1] : 0;
                        $current_page = $page;
                        $current_pos = $i + 1;
                        break;
                    }
                }
            }
        }

        if (empty($current_pos)) {
            return false;
        }

        $next_page = $current_page + 1;
        $prev_page = $current_page - 1;
        $current_pos += $prev_page * $view_results['items_per_page'];
        $update_view = false;

        if (empty($next_id) && ($next_page <= $view_results['total_pages'])) {
            if (!empty($items_ids[$next_page])) {
                $next_id = !empty($items_ids[$next_page][0]) ? $items_ids[$next_page][0] : 0;
            } else {
                $next_items_ids = $this->_getAnotherPageIds($data['params'], $view_results['items_per_page'], $next_page);
                $next_id = !empty($next_items_ids[$next_page][0]) ? $next_items_ids[$next_page][0] : 0;

                //store new ids
                foreach ($next_items_ids as $page => $items) {
                    $items_ids[$page] = $items;
                }
                $update_view = true;
            }
        }

        if (empty($prev_id) && ($prev_page > 0)) {
            if (!empty($items_ids[$prev_page])) {
                $prev_id = !empty($items_ids[$prev_page][count($items_ids[$prev_page]) - 1])
                    ? $items_ids[$prev_page][count($items_ids[$prev_page]) - 1]
                    : 0; //last on previus page
            } else {
                $prev_items_ids = $this->_getAnotherPageIds($data['params'], $view_results['items_per_page'], $prev_page);
                $prev_id = !empty($prev_items_ids[$prev_page]) && !empty($prev_items_ids[$prev_page][count($prev_items_ids[$prev_page]) - 1])
                    ? $prev_items_ids[$prev_page][count($prev_items_ids[$prev_page]) - 1]
                    : 0;

                //store new ids
                foreach ($prev_items_ids as $page => $items) {
                    $items_ids[$page] = $items;
                }
                $update_view = true;
            }
        }

        if ($update_view) {
            $updated_results = [
                'items_ids'      => $items_ids,
                'total_pages'    => $view_results['total_pages'],
                'items_per_page' => $view_results['items_per_page'],
                'total_items'    => $view_results['total_items'],
            ];

            $this->_updateCurrentView([
                'view_id'      => !empty($data['view_id']) ? $data['view_id'] : 0,
                'view_results' => serialize($updated_results)
            ]);
        }

        $this ->_setViewTools($current_pos, $next_id, $prev_id, $view_results['total_items']);

        return true;
    }

    /**
     * Sets view tools
     *
     * @param int   $current_pos Current position
     * @param int   $next_id     Next element id
     * @param int   $prev_id     Previous element id
     * @param int   $total_items Total items
     * @param array $url_params  Additional url params (key => value)
     *
     * @return bool Always true
     */
    protected function _setViewTools($current_pos, $next_id, $prev_id, $total_items, array $url_params = [])
    {
        fn_set_hook('view_set_view_tools_pre', $this, $current_pos, $next_id, $prev_id, $total_items, $url_params);

        $dispatch = $this->_controller . '.' . $this->_mode;

        $view_tools = [
            'prev_id'  => $prev_id,
            'next_id'  => $next_id,
            'total'    => $total_items,
            'current'  => $current_pos,
            'prev_url' => fn_url($dispatch . '?' . http_build_query(array_merge($url_params, [$this->_schema['item_id'] => $prev_id]))),
            'next_url' => fn_url($dispatch . '?' . http_build_query(array_merge($url_params, [$this->_schema['item_id'] => $next_id])))
        ];

        if (!empty($this->_schema['show_item_id'])) {
            $view_tools['show_item_id'] = $this->_schema['show_item_id'];
        }
        if (!empty($this->_schema['links_label'])) {
            $view_tools['links_label'] = __($this->_schema['links_label']);
        }

        Tygh::$app['view']->assign('view_tools', $view_tools);

        return true;
    }

    /**
     * Saves current search results
     *
     * @param array $params Request params
     *
     * @return bool Always true
     */
    public function _saveViewResults($params)
    {
        if (!empty($params['save_view_results'])) {
            $view_results = Registry::get('view_results.' . $this->_schema['func']);

            $view = $this->_getCurrentView();

            if (!empty($view_results)) {
                $stored_items_ids = [];

                if (!empty($view['view_results'])) {
                    $old_params = unserialize($view['params']);
                    $new_params = $view_results['params'];
                    unset($old_params['page'], $new_params['page']);
                    unset($old_params['is_ajax'], $new_params['is_ajax']);
                    unset($old_params['result_ids'], $new_params['result_ids']);

                    if ($old_params == $new_params) {
                        $stored_data = unserialize($view['view_results']);
                        $stored_items_ids = $stored_data['items_ids'];
                    }
                }
                foreach ($view_results['items_ids'] as $page => $items) {
                    $stored_items_ids[$page] = $items;
                }

                $updated_data = $view;
                $updated_data['params'] = serialize($view_results['params']);
                $updated_data['view_results'] = serialize([
                    'items_ids'      => $stored_items_ids,
                    'total_pages'    => $view_results['total_pages'],
                    'items_per_page' => $view_results['items_per_page'],
                    'total_items'    => $view_results['total_items'],
                ]);

                $this->_updateCurrentView($updated_data);
            }
        }

        return true;
    }

    /**
     * Processes search results
     *
     * @param string $func   Func Name
     * @param array  $items  Search result items
     * @param array  $params Search params
     *
     * @return void
     */
    public function processResults($func, $items, $params)
    {
        fn_set_hook('view_process_results_pre', $func, $items, $params);

        if (!empty($params['save_view_results']) && !empty($params['page'])) {
            $id = $params['save_view_results'];
            $pagination = fn_generate_pagination($params);

            if (empty($pagination)) {
                return;
            }

            $current_page = $pagination['current_page'];

            $view_results = [
                'items_ids'      => [],
                'total_pages'    => $pagination['total_pages'],
                'items_per_page' => $pagination['items_per_page'],
                'total_items'    => $pagination['total_items'],
                'params'         => $params,
            ];

            foreach ($items as $item) {
                $view_results['items_ids'][$current_page][] = $item[$id];
            }

            Registry::set('view_results.fn_get_' . $func, $view_results);
        }
    }

    /**
     * @return string
     */
    public function getController()
    {
        return $this->_controller;
    }

    /**
     * @return string
     */
    public function getMode()
    {
        return $this->_mode;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->_action;
    }

    /**
     * Gets next page item ids
     *
     * @param string $params         Search parameters
     * @param int    $items_per_page Items per page
     * @param int    $page           Page number
     *
     * @return array Next page items ids
     */
    protected function _getAnotherPageIds($params, $items_per_page, $page)
    {
        $_ids = [];
        $params = (array) unserialize($params);
        if (!empty($this->_schema['additional_data'])) {
            $params = fn_array_merge($params, $this->_schema['additional_data']);
        }
        $params = fn_array_merge($params, ['page' => $page]);

        if (!empty($this->_schema['auth'])) {
            list($items,) = $this->_schema['func']($params, $this->_auth, $items_per_page);
        } elseif (!empty($this->_schema['skip_param'])) {
            list($items,) = $this->_schema['func']($params, [], $items_per_page);
        } else {
            list($items,) = $this->_schema['func']($params, $items_per_page);
        }

        foreach ($items as $v) {
            $_ids[$page][] = $v[$this->_schema['item_id']];
        }

        return $_ids;
    }
}
