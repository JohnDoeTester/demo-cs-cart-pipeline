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

use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\SiteArea;
use Tygh\Enum\YesNo;

defined('BOOTSTRAP') or die('Access denied');

/** @var string $mode */

// Ajax content
if ($mode === 'get_companies_list') {
    // Check if we trying to get list by non-ajax
    if (!defined('AJAX_REQUEST')) {
        return [CONTROLLER_STATUS_REDIRECT, fn_url()];
    }

    //TODO make single function
    $params = array_merge(
        [
            'render_html' => YesNo::YES,
        ],
        $_REQUEST
    );

    $condition = '';
    if (!empty($params['q'])) {
        $pattern = $params['q'];
    } elseif (!empty($params['pattern'])) {
        $pattern = $params['pattern'];
    } else {
        $pattern = '';
    }
    if (isset($_REQUEST['page'], $_REQUEST['page_size'])) {
        $limit = (int) $_REQUEST['page_size'];
        $start = ($_REQUEST['page'] - 1) * $limit;
    } else {
        $start = !empty($params['start']) ? $params['start'] : 0;
        $limit = (!empty($params['limit']) ? $params['limit'] : 10);
    }

    $condition = '1=1';

    if (SiteArea::isStorefront(AREA)) {
        $condition .= db_quote(' AND status = ?s', ObjectStatuses::ACTIVE);
        /** @var \Tygh\Storefront\Storefront $storefront */
        $storefront = Tygh::$app['storefront'];
        $ids = $storefront->getCompanyIds();
        if (!empty($ids)) {
            $condition .= db_quote(' AND company_id IN (?n)', $ids);
        }
    }

    fn_set_hook('get_companies_list', $condition, $pattern, $start, $limit, $params);

    if ($pattern) {
        $condition .= db_quote(' AND company LIKE ?l', $pattern . '%');
    }

    if (!empty($params['ids'])) {
        $condition .= db_quote(' AND company_id IN (?n)', $params['ids']);
    }

    $objects = db_get_hash_array(
        "SELECT company_id, company_id AS value, company_id AS id, company AS name, company AS text, CONCAT('switch_company_id=', company_id) AS append" .
        ' FROM ?:companies' .
        ' WHERE ?p' .
        ' ORDER BY company' .
        ' LIMIT ?i, ?i',
        'value',
        $condition,
        $start,
        $limit
    );
    $total = (int) db_get_field('SELECT COUNT(*) FROM ?:companies WHERE ?p', $condition);

    if (fn_allowed_for('ULTIMATE')) {
        foreach ($objects as &$object) {
            $object['storefront_status'] = fn_ult_get_storefront_status($object['company_id']);
        }
        unset($object);
    }

    if (defined('AJAX_REQUEST') && sizeof($objects) < $limit) {
        Tygh::$app['ajax']->assign('completed', true);
    }

    if (empty($params['start']) && empty($params['pattern'])) {
        $all_vendors = [];
        $is_search = !empty($params['search']) && YesNo::toBool($params['search']);
        if (!empty($params['show_all']) && YesNo::toBool($params['show_all'])) {
            $all_vendors[0] = [
                'id'         => $is_search ? '' : 0,
                'company_id' => $is_search ? '' : 0,
                'value'      => $is_search ? '' : 0,
                'text'       => empty($params['default_label']) ? __('all_vendors') : __($params['default_label']),
                'name'       => empty($params['default_label']) ? __('all_vendors') : __($params['default_label']),
                'append'     => '',
                'data'       => [
                    'id'         => $is_search ? '' : 0,
                    'company_id' => $is_search ? '' : 0,
                    'value'      => $is_search ? '' : 0,
                    'text'       => empty($params['default_label']) ? __('all_vendors') : __($params['default_label']),
                    'name'       => empty($params['default_label']) ? __('all_vendors') : __($params['default_label']),
                    'append'     => '',
                    'url'        => fn_url('products.update?product_id=0'),
                ],
            ];
            $total++;
        }

        $objects = $all_vendors + $objects;
    }

    $objects = array_values(array_map(static function ($company) {
        return [
            'id'         => $company['id'],
            'company_id' => $company['company_id'],
            'value'      => $company['value'],
            'text'       => $company['text'],
            'name'       => $company['name'],
            'append'     => $company['append'],
            'data'       => [
                'id'         => $company['id'],
                'company_id' => $company['company_id'],
                'value'      => $company['value'],
                'text'       => $company['text'],
                'name'       => $company['name'],
                'append'     => $company['append'],
                'url'        => fn_url('products.update?product_id=' . $company['id']),
            ],
        ];
    }, $objects));

    Tygh::$app['ajax']->assign('objects', $objects);
    Tygh::$app['ajax']->assign('total_objects', $total);

    if (defined('AJAX_REQUEST') && !empty($params['action'])) {
        Tygh::$app['ajax']->assign('action', $params['action']);
    }

    if (!empty($params['onclick'])) {
        Tygh::$app['view']->assign('onclick', $params['onclick']);
    }

    Tygh::$app['view']->assign(
        [
            'objects'     => $objects,
            'id'          => !empty($params['result_ids']) ? $params['result_ids'] : '',
            'object_type' => 'companies',
        ]
    );

    if (YesNo::toBool($params['render_html'])) {
        Tygh::$app['view']->display('common/ajax_select_object.tpl');
    }

    return [CONTROLLER_STATUS_NO_CONTENT];
}
