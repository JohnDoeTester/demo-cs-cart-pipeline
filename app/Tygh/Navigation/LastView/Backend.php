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
use Tygh\Enum\YesNo;
use Tygh\Registry;

/**
 * Last view backend class
 *
 * phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
 * phpcs:disable SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
 * phpcs:disable SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
 * phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
 * phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
 */
class Backend extends ACommon
{
    /**
     * Indicates View is default
     */
    const IS_DEFAULT = 'Y';

    /**
     * Indicates View is not default
     */
    const IS_NOT_DEFAULT = 'N';

    /**
     * Prepares params for search
     *
     * @param array $params Request params
     *
     * @return bool Always true
     */
    public function prepare(&$params)
    {
        $this->tryToApplyDefaultView($params);

        if (!empty($params['return_to_list']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $params['redirect_url'] = $this->_controller . '.' . (empty($this->_schema['list_mode'])
                    ? 'manage'
                    : $this->_schema['list_mode']) . '.last_view';

            if ($this->_controller === 'profiles' && !empty($params['user_type'])) {
                $params['redirect_url'] .= '&user_type=' . $params['user_type'];
            }

            if (!empty($this->_schema['selected_section'])) {
                $params['selected_section'] = $this->_schema['selected_section'];
            } elseif (
                !empty($this->_schema['update_mode'])
                && is_array($this->_schema['update_mode'])
                && isset($this->_schema['update_mode'][$this->_mode])
                && !empty($this->_schema['update_mode'][$this->_mode]['selected_section'])
            ) {
                $params['selected_section'] = $this->_schema['update_mode'][$this->_mode]['selected_section'];
            } else {
                unset($params['selected_section']);
            }

            return true;
        }

        if (
            isset($this->_schema['func'])
            && (
                (!empty($this->_schema['list_mode']) && $this->_schema['list_mode'] === $this->_mode)
                || $this->_mode === 'manage'
            )
            && (
                empty($this->_schema['update_mode'])
                || (!empty($this->_schema['update_mode']) && !is_array($this->_schema['update_mode']))
            )
        ) {
            $sort_data = ['sort_by' => '', 'sort_order' => ''];

            if ($this->_action === 'last_view' && empty($params['view_id'])) {
                $data = $this->_getCurrentView();

                if (!empty($data)) {
                    $data['active'] = 'N';
                    $this->_updateCurrentView($data);

                    $view_params = unserialize($data['params']);
                    if (!empty($params['sort_by']) && !empty($view_params['sort_by'])) {
                        $sort_data['sort_by'] = $view_params['sort_by'];
                        unset($view_params['sort_by']);
                    }
                    if (!empty($params['sort_order']) && !empty($view_params['sort_order'])) {
                        $sort_data['sort_order'] = $view_params['sort_order'];
                        unset($view_params['sort_order']);
                    }
                    if (!empty($view_params['dispatch'])) {
                        unset($params['dispatch']);
                    }
                    $params = fn_array_merge($view_params, $params);
                }
            } elseif (!isset($params['items_per_page'])) {
                $data = $this->_getCurrentView();

                if (!empty($data)) {
                    $view_params = unserialize($data['params']);

                    if (!empty($view_params['items_per_page'])) {
                        $params['items_per_page'] = $view_params['items_per_page'];
                    }
                }
            }

            $sort_params = [
                'sort_by'    => !empty($params['sort_by']) ? $params['sort_by'] : '',
                'sort_order' => !empty($params['sort_order']) ? $params['sort_order'] : ''
            ];

            $_actions = ['save_view', 'delete_view'];

            if (
                !in_array($this->_action, $_actions, true)
                && (
                    !($this->_action === 'last_view' && empty($params['view_id']))
                    || (
                        $this->_action === 'last_view'
                        && empty($params['view_id'])
                        && $sort_data !== $sort_params
                    )
                )
            ) {
                $_params = $params;
                unset($_params['dispatch'], $_params['page']);
                $view = $this->_getCurrentView();

                if (empty($view)) {
                    $data = [
                        'object'       => 'lv_' . $this->_controller,
                        'params'       => serialize($_params),
                        'view_results' => serialize([
                            'items_ids'      => [],
                            'total_pages'    => 0,
                            'items_per_page' => 0,
                            'total_items'    => 0
                        ]),
                        'user_id'      => $this->_auth['user_id']
                    ];
                    $this->_updateCurrentView($data);
                }

                $params['save_view_results'] = $this->_schema['item_id'];
            }
        }

        return true;
    }

    /**
     * Init search view params
     *
     * @param string $object Object to init view for
     * @param array  $params Request parameters
     *
     * @return array Filtered params
     */
    public function update($object, $params)
    {
        if (!empty($params['skip_view'])) {
            return $params;
        }

        $this->_checkUpdateActions($object, $params);

        if (!empty($params['view_id'])) {
            $data = $this->getViewParams($params['view_id']);
            if (!empty($data)) {
                $result = fn_array_merge($data, $params);

                if (!empty($params['sort_by'])) {
                    $result['sort_by'] = $params['sort_by'];
                }

                if (!empty($params['sort_order'])) {
                    $result['sort_order'] = $params['sort_order'];
                }

                db_query(
                    'UPDATE ?:views SET active = (CASE WHEN view_id = ?i THEN ?s ELSE ?s END) WHERE user_id = ?i AND object = ?s',
                    $params['view_id'],
                    YesNo::YES,
                    YesNo::NO,
                    $this->_auth['user_id'],
                    $object
                );

                return $result;
            }
        } elseif (isset($params['is_search']) && YesNo::toBool($params['is_search'])) {
            $params['temp_view'] = true;
        }

        return $params;
    }

    /**
     * Gets view params
     *
     * @param int $view_id View ID
     *
     * @return array Unserialized params list
     */
    public function getViewParams($view_id)
    {
        $params = db_get_field('SELECT params FROM ?:views WHERE view_id = ?i', $view_id);

        if (!empty($params)) {
            $params = unserialize($params);
        } else {
            $params = [];
        }

        return $params;
    }

    /**
     * @inheritDoc
     */
    public function init(&$params)
    {
        parent::init($params);

        if ($this->_schema && Tygh::$app->hasInstance('view')) {
            Tygh::$app['view']->assign('last_view_current_object_schema', $this->_schema);
        }
    }


    /**
     * Gets current view data
     *
     * @return array View data
     */
    protected function _getCurrentView()
    {
        return db_get_row('SELECT * FROM ?:views WHERE user_id = ?i AND object = ?s', $this->_auth['user_id'], 'lv_' . $this->_controller);
    }

    /**
     * Saves current view
     *
     * @param array $data View data
     *
     * @return bool Always true
     */
    protected function _updateCurrentView($data)
    {
        if (!empty($data['view_id'])) {
            db_query('UPDATE ?:views SET ?u WHERE view_id = ?i', $data, $data['view_id']);
        } else {
            db_query('INSERT INTO ?:views ?e', $data);
        }

        return true;
    }

    /**
     * Checks if prev/next links should be shown on current page
     *
     * @param array $params Page request params
     *
     * @return bool Result of checking
     */
    protected function _isNeedViewTools($params)
    {
        if (!isset($this->_schema['item_id'], $params[$this->_schema['item_id']])) {
            return false;
        }

        if (empty($this->_schema['update_mode']) && $this->_mode === 'update') {
            return true;
        }

        if (
            !empty($this->_schema['update_mode'])
            && !is_array($this->_schema['update_mode'])
            && $this->_schema['update_mode'] === $this->_mode
        ) {
            return true;
        }

        return false;
    }

    /**
     * Processes view actions
     *
     * @param string $object Object to init view for
     * @param array  $params Request parameters
     *
     * @return bool Always true
     */
    protected function _checkUpdateActions($object, $params)
    {
        // Save view
        if ($this->_action === 'save_view' && !empty($params['new_view'])) {
            $name = $params['new_view'];
            $update_view_id = empty($params['update_view_id']) ? 0 : $params['update_view_id'];
            unset($params['dispatch'], $params['page'], $params['new_view'], $params['update_view_id']);

            $data = [
                'object'  => $object,
                'name'    => $name,
                'params'  => serialize($params),
                'user_id' => $this->_auth['user_id']
            ];

            if ($update_view_id) {
                db_query('UPDATE ?:views SET ?u WHERE view_id = ?i', $data, $update_view_id);
                $params['view_id'] = $update_view_id;
            } else {
                $params['view_id'] = db_replace_into('views', $data);
            }

            fn_redirect(Registry::get('runtime.controller') . '.' . Registry::get('runtime.mode') . '?' . http_build_query($params));
        } elseif ($this->_action === 'delete_view' && !empty($params['view_id'])) {
            db_query('DELETE FROM ?:views WHERE view_id = ?i', $params['view_id']);
        } elseif ($this->_action === 'reset_view') {
            db_query('UPDATE ?:views SET active = ?s WHERE user_id = ?i AND object = ?s', YesNo::NO, $this->_auth['user_id'], $object);
        } elseif ($this->_action === 'set_default_view' && !empty($params['view_id'])) {
            db_query(
                'UPDATE ?:views SET is_default = ?s WHERE user_id = ?i AND object = ?s AND view_id = ?i',
                self::IS_DEFAULT,
                $this->_auth['user_id'],
                $object,
                $params['view_id']
            );
            db_query(
                'UPDATE ?:views SET is_default = ?s WHERE user_id = ?i AND object = ?s AND view_id != ?i',
                self::IS_NOT_DEFAULT,
                $this->_auth['user_id'],
                $object,
                $params['view_id']
            );
        } elseif ($this->_action === 'unset_default_view' && !empty($params['view_id'])) {
            db_query(
                'UPDATE ?:views SET is_default = ?s WHERE user_id = ?i AND object = ?s AND view_id = ?i',
                self::IS_NOT_DEFAULT,
                $this->_auth['user_id'],
                $object,
                $params['view_id']
            );
        }

        return true;
    }

    /**
     * Gets currently selected view identifier.
     *
     * @return int|null
     */
    public function getCurrentViewId()
    {
        $view_data = $this->_getCurrentView();

        if (!empty($view_data)) {
            return $view_data['view_id'];
        }

        return null;
    }

    /**
     * Applies default view if required
     *
     * @param array $params Request params
     *
     * @return void
     */
    private function tryToApplyDefaultView(array $params)
    {
        $allowed_modes = ['manage'];

        if (!empty($this->_schema['list_mode'])) {
            $allowed_modes[] = $this->_schema['list_mode'];
        }

        if (
            empty($this->_schema['allow_default_view'])
            || $this->_action
            || $_SERVER['REQUEST_METHOD'] !== 'GET'
            || defined('AJAX_REQUEST')
            || !in_array($this->_mode, $allowed_modes, true)
        ) {
            return;
        }

        $params = array_diff_key($params, array_flip(['dispatch']));
        $is_default_view_applyable = empty($params);

        /**
         * Executes before apply default view.
         *
         * @param \Tygh\Navigation\LastView\Backend $this                      Backend LastView instance
         * @param array                             $params                    Request params
         * @param bool                              $is_default_view_applyable Whether to apply default view
         */
        fn_set_hook('last_view_is_default_view_applyable', $this, $params, $is_default_view_applyable);

        if (!$is_default_view_applyable) {
            return;
        }

        $view_id = $this->findDefaultViewId();

        if (!$view_id) {
            return;
        }

        fn_redirect(fn_link_attach(Registry::get('config.current_url'), '&view_id=' . $view_id));
    }

    /**
     * Gets default view ID
     *
     * @return int|null
     */
    private function findDefaultViewId()
    {
        if (!$this->object_type) {
            return null;
        }

        $view_id = (int) db_get_field(
            'SELECT view_id FROM ?:views WHERE user_id = ?i AND object = ?s AND is_default = ?s',
            $this->_auth['user_id'],
            $this->object_type,
            self::IS_DEFAULT
        );

        return $view_id ?: null;
    }
}
