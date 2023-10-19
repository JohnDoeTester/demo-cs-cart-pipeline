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

use Tygh\Addons\ProductVariations\Form\GenerateVariationsForm;
use Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection;
use Tygh\Addons\ProductVariations\Request\GenerateProductsAndAttachToGroupRequest;
use Tygh\Addons\ProductVariations\Request\GenerateProductsAndCreateGroupRequest;
use Tygh\Addons\ProductVariations\ServiceProvider;
use Tygh\Addons\ProductVariations\Product\FeaturePurposes;
use Tygh\Addons\ProductVariations\Product\Type\Type;
use Tygh\Enum\ObjectStatuses;
use Tygh\Registry;
use Illuminate\Support\Collection;

defined('BOOTSTRAP') or die('Access denied');

/**
 * @var string $mode
 * @var string $action
 * @var array  $auth
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'link') {
        $product_id = isset($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : 0;
        $product_ids = isset($_REQUEST['product_ids']) ? (array) array_filter($_REQUEST['product_ids']) : [];

        if (!$product_id) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        $product_data = fn_get_product_data($product_id, $auth, CART_LANGUAGE, '', false, false, false, false, false, false);

        if (!$product_data) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        $service = ServiceProvider::getService();
        $group_repository = ServiceProvider::getGroupRepository();

        $group_id = $group_repository->findGroupIdByProductId($product_id);

        if ($group_id) {
            $result = $service->attachProductsToGroup($group_id, $product_ids);
        } else {
            $result = $service->createGroup(array_merge([$product_id], $product_ids));
        }

        $result->showNotifications();

        if (defined('AJAX_REQUEST') && AJAX_REQUEST) {
            /** @var \Tygh\Ajax $ajax */
            $ajax = Tygh::$app['ajax'];
            $ajax->assign('force_redirection', fn_url('products.update?selected_section=variations&product_id=' . $product_id));
        } else {
            return [CONTROLLER_STATUS_OK, 'products.update?selected_section=variations&product_id=' . $product_id];
        }
    } elseif ($mode === 'add_product') {
        $group_id = isset($_REQUEST['group_id']) ? (int) $_REQUEST['group_id'] : 0;
        $product_id = isset($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : 0;

        if (!$product_id) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        if ($group_id) {
            $service = ServiceProvider::getService();
            $group_repository = ServiceProvider::getGroupRepository();

            if ($group_repository->findGroupIdByProductId($product_id)) {
                return [CONTROLLER_STATUS_NO_PAGE];
            }

            $result = $service->attachProductsToGroup($group_id, [$product_id]);
            $result->showNotifications();
        }

        if (defined('AJAX_REQUEST') && AJAX_REQUEST) {
            /** @var \Tygh\Ajax $ajax */
            $ajax = Tygh::$app['ajax'];
            $ajax->assign('force_redirection', fn_url('products.update?selected_section=variations&product_id=' . $product_id));
        } else {
            return [CONTROLLER_STATUS_OK, 'products.update?selected_section=variations&product_id=' . $product_id];
        }
    } elseif ($mode === 'delete_product') {
        $product_id = isset($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : 0;

        if (!$product_id) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        $service = ServiceProvider::getService();
        $group_repository = ServiceProvider::getGroupRepository();

        $group_id = $group_repository->findGroupIdByProductId($product_id);

        if (!$group_id) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        $result = $service->detachProductFromGroup($group_id, $product_id);
        $result->showNotifications();

        if (defined('AJAX_REQUEST') && AJAX_REQUEST) {
            /** @var \Tygh\Ajax $ajax */
            $ajax = Tygh::$app['ajax'];
            $ajax->assign('force_redirection', fn_url('products.update?selected_section=variations&product_id=' . $product_id));
        } else {
            return [CONTROLLER_STATUS_OK, 'products.update?selected_section=variations&product_id=' . $product_id];
        }
    } elseif ($mode === 'm_delete_product') {
        $product_ids = isset($_REQUEST['product_ids']) ? (array) $_REQUEST['product_ids'] : [];

        if (!$product_ids) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        $service = ServiceProvider::getService();
        $group_repository = ServiceProvider::getGroupRepository();

        $group_ids = $group_repository->findGroupIdsByProductIds($product_ids);
        $group_ids = array_unique($group_ids);

        if (!$group_ids) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        foreach ($group_ids as $group_id) {
            $result = $service->detachProductsFromGroup($group_id, $product_ids);
            $result->showNotifications();
        }
    } elseif ($mode === 'delete') {
        $product_id = isset($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : 0;

        if (!$product_id) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        $service = ServiceProvider::getService();
        $group_repository = ServiceProvider::getGroupRepository();

        $group_id = $group_repository->findGroupIdByProductId($product_id);

        if (!$group_id) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        $result = $service->removeGroup($group_id);
        $result->showNotifications();

        if (defined('AJAX_REQUEST') && AJAX_REQUEST) {
            /** @var \Tygh\Ajax $ajax */
            $ajax = Tygh::$app['ajax'];
            $ajax->assign('force_redirection', fn_url('products.update?selected_section=variations&product_id=' . $product_id));
        } else {
            return [CONTROLLER_STATUS_OK, 'products.update?selected_section=variations&product_id=' . $product_id];
        }
    } elseif ($mode === 'mark_main_product') {
        $product_id = isset($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : 0;

        if (!$product_id) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        $service = ServiceProvider::getService();
        $group_repository = ServiceProvider::getGroupRepository();

        $group_id = $group_repository->findGroupIdByProductId($product_id);

        if (!$group_id) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        $result = $service->setDefaultProduct($group_id, $product_id);
        $result->showNotifications();

        $redirect_url = 'products.update?selected_section=variations&product_id=' . $product_id;

        if (defined('AJAX_REQUEST') && AJAX_REQUEST) {
            /** @var \Tygh\Ajax $ajax */
            $ajax = Tygh::$app['ajax'];
            $ajax->assign('force_redirection', $redirect_url);
        } else {
            return [CONTROLLER_STATUS_OK, $redirect_url];
        }
    } elseif ($mode === 'generate') {
        $product_id = isset($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : 0;

        if (!$product_id) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        $generation_form = GenerateVariationsForm::create($product_id, $_REQUEST);
        $product_data = $generation_form->getProductData();

        if (!$product_data) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        $group_repository = ServiceProvider::getGroupRepository();
        $service = ServiceProvider::getService();

        $group_id = $group_repository->findGroupIdByProductId($product_id);

        if ($group_id) {
            $request = new GenerateProductsAndAttachToGroupRequest(
                $group_id,
                $product_id,
                $generation_form->getCombinationsData()
            );
            $request->setFeaturesVariantsMap($generation_form->getFeaturesVariantsMap());
            $result = $service->generateProductsAndAttachToGroup($request);
        } else {
            $request = new GenerateProductsAndCreateGroupRequest(
                $product_id,
                $generation_form->getCombinationsData(),
                $generation_form->getFeatureCollection()
            );
            $request->setFeaturesVariantsMap($generation_form->getFeaturesVariantsMap());
            $result = $service->generateProductsAndCreateGroup($request);
        }

        $result->showNotifications();

        $redirect_url = 'products.update?selected_section=variations&product_id=' . $product_id;

        if (defined('AJAX_REQUEST') && AJAX_REQUEST) {
            /** @var \Tygh\Ajax $ajax */
            $ajax = Tygh::$app['ajax'];
            $ajax->assign('force_redirection', $redirect_url);
        } else {
            return [CONTROLLER_STATUS_OK, $redirect_url];
        }
    }

    if ($mode !== 'create_variations') {
        return [CONTROLLER_STATUS_OK];
    }
}

if ($mode === 'manage') {
    $product_id = isset($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : 0;

    if (!defined('AJAX_REQUEST')) {
        return [CONTROLLER_STATUS_REDIRECT, 'products.update?selected_section=variations&product_id=' . $product_id];
    }

    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    $product_data = fn_get_product_data($product_id, $auth, CART_LANGUAGE, '', false, false, false, false, false, false, true);

    if (!$product_data) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    $group_repository = ServiceProvider::getGroupRepository();
    $product_repository = ServiceProvider::getProductRepository();

    $group = $group_repository->findGroupByProductId($product_id);

    if ($group) {
        $parent_to_child_map = [];

        foreach ($group->getProducts() as $group_product) {
            if (!$group_product->getParentProductId()) {
                continue;
            }

            $parent_to_child_map[$group_product->getParentProductId()] = $group_product->getProductId();
        }

        $params = array_merge($_REQUEST, [
            'sort_by' => 'null',
            'pid'     => $group->getProductIds(),
        ]);

        // FIXME its need to master products
        if (fn_allowed_for('MULTIVENDOR')) {
            $runtime_company_id = Registry::get('runtime.company_id');
            Registry::set('runtime.company_id', 0);
        }

        list($products, $search) = fn_get_products($params, 0, DESCR_SL);
        fn_gather_additional_products_data($products, [
            'get_icon'            => true,
            'get_detailed'        => true,
            'get_options'         => false,
            'get_discounts'       => false,
            'get_features'        => false,
            'get_product_type'    => true
        ]);

        // FIXME its need to master products
        if (fn_allowed_for('MULTIVENDOR')) {
            Registry::set('runtime.company_id', $runtime_company_id);
        }

        $selected_features = $product_repository->findFeaturesByFeatureCollection($group->getFeatures());
        $selected_features = $product_repository->loadFeaturesVariants($selected_features);

        foreach ($products as &$product) {
            $product['has_children'] = isset($parent_to_child_map[$product['product_id']]);
        }
        unset($product);

        $products = $product_repository->loadProductsFeatures($products, $group->getFeatures());

        $products = Collection::make($products)->sortBy(function ($item) {
            $key_1 = [];
            $key_2 = [];

            foreach ($item['variation_features'] as $feature) {
                if (FeaturePurposes::isCreateCatalogItem($feature['purpose'])) {
                    $key_1[] = $feature['variant_position'];
                    $key_1[] = $feature['variant_id'];
                } else {
                    $key_2[] = $feature['variant_position'];
                    $key_2[] = $feature['variant_id'];
                }
            }

            if ($item['parent_product_id']) {
                $key_1[] = 1;
            } else {
                $key_1[] = 0;
            }

            $key_2[] = $item['product_id'];

            return implode('_', array_merge($key_1, $key_2));
        })->all();

        if (fn_allowed_for('ULTIMATE')) {
            foreach (array_keys($products) as $_product_id) {
                $products[$_product_id]['shared_product'] = fn_ult_is_shared_product($_product_id);
            }
        }

        $view->assign([
            'product_id'        => $product_id,
            'product'           => $product_data,
            'group'             => $group,
            'products'          => $products,
            'search'            => $search,
            'selected_features' => $selected_features
        ]);
    } else {
        $features = $product_repository->findAvailableFeatures($product_id);
        $group_codes = $group_repository->findGroupCodesByFeatureIds(array_keys($features));

        $view->assign([
            'product_id'  => $product_id,
            'product'     => $product_data,
            'features'    => $features,
            'group_codes' => $group_codes
        ]);
    }
} elseif ($mode === 'create_variations') {
    $product_id = isset($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : 0;

    if (!defined('AJAX_REQUEST')) {
        return [CONTROLLER_STATUS_REDIRECT, 'products.update?selected_section=variations&product_id=' . $product_id];
    }

    $generation_form = GenerateVariationsForm::create($product_id, $_REQUEST);
    $product_data = $generation_form->getProductData();

    if (!$product_data) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    $product_type = Type::createByProduct($product_data);

    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];
    $view->assign([
        'product_data'                 => $product_data,
        'feature_value_collection'     => $generation_form->getFeatureValueCollection(),
        'group'                        => $generation_form->getGroup(),
        'combinations'                 => $generation_form->getCombinations(),
        'selected_features'            => $generation_form->getFeatures(),
        'new_combinations_count'       => $generation_form->getNewCombinationsCount(),
        'feature_ids'                  => array_keys($generation_form->getFeatures()),
        'features_variant_ids'         => $generation_form->getFeaturesVariantsMap(),
        'exists_features_variant_ids'  => $generation_form->getExistsFeaturesVariantsMap(),
        'is_allow_generate_variations' => $product_type->isAllowGenerateVariations(),
        'is_all_combinations_active'   => $generation_form->isAllCombinationsActive(),
    ]);

    if (defined('AJAX_REQUEST')) {
        Tygh::$app['view']->display('addons/product_variations/views/product_variations/create_variations.tpl');
        exit;
    }
} elseif ($mode === 'find_variations') {
    $product_id = isset($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : 0;

    if (!defined('AJAX_REQUEST')) {
        return [CONTROLLER_STATUS_REDIRECT, 'products.update?selected_section=variations&product_id=' . $product_id];
    }

    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    $product_data = fn_get_product_data($product_id, $auth, CART_LANGUAGE, '', false, false, false, false, false, false);

    if (!$product_data) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    $product_type = Type::createByProduct($product_data);
    $group_repository = ServiceProvider::getGroupRepository();
    $product_repository = ServiceProvider::getProductRepository();

    $generator = ServiceProvider::getCombinationsGenerator();
    $generator->setDefaulIsActive(!empty($_REQUEST['features_variants_ids']));

    $group = $group_repository->findGroupByProductId($product_id);

    $search = $products = [];
    $selected_features = [];

    if ($group) {
        $group_features = $group->getFeatures();
        $product_ids = $group->getProductIds();
        $feature_ids = $group->getFeatureIds();
    } else {
        $features = $product_repository->findAvailableFeatures($product_id);
        $group_features = GroupFeatureCollection::createFromFeatureList($features);
        $feature_ids = array_keys($features);
        $product_ids = [$product_id];
    }

    if ($feature_ids) {
        $selected_features = $product_repository->findFeaturesByFeatureCollection($group_features);

        $params = array_merge($_REQUEST, [
            'product_type'            => [Type::PRODUCT_TYPE_SIMPLE],
            'has_not_variation_group' => true,
            'has_features'            => $feature_ids,
            'exclude_pid'             => $product_ids,
            'subcats'                 => 'Y'
        ]);

        if (!isset($params['cid'])) {
            $params['cid'] = $product_data['main_category'];
        }

        list($products, $search) = fn_get_products($params, Registry::get('settings.Appearance.admin_elements_per_page'));

        fn_gather_additional_products_data($products, [
            'get_icon'            => true,
            'get_detailed'        => true,
            'get_options'         => false,
            'get_discounts'       => false,
            'get_features'        => false
        ]);

        $products = $product_repository->loadProductsFeatures($products, $group_features);
    }

    $view->assign([
        'product_data'                 => $product_data,
        'group'                        => $group,
        'selected_features'            => $selected_features,
        'feature_ids'                  => $feature_ids,
        'products'                     => $products,
        'search'                       => $search,
        'is_allow_generate_variations' => $product_type->isAllowGenerateVariations()
    ]);

    if (!empty($product_data['product_features'])) {
        $feature_params = [
            'feature_id'      => array_keys($product_data['product_features']),
            'plain'           => true,
            'statuses'        => [ObjectStatuses::ACTIVE, ObjectStatuses::HIDDEN],
            'variants'        => true,
            'exclude_group'   => true,
            'exclude_filters' => false
        ];
        // Preload variants selected at search form. They will be shown at AJAX variants loader as pre-selected.
        if (!empty($_REQUEST['feature_variants'])) {
            $feature_params['variants_only'] = $_REQUEST['feature_variants'];
        }

        list($features, $features_search) = fn_get_product_features($feature_params, PRODUCT_FEATURES_THRESHOLD);

        if ($features_search['total_items'] <= PRODUCT_FEATURES_THRESHOLD) {
            $view->assign('feature_items', $features);
        } else {
            $view->assign('feature_items_too_many', true);
        }
    }
}
