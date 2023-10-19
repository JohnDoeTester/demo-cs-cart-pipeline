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

use Tygh\Enum\ProductTracking;
use Tygh\SoftwareProductEnvironment;
use Tygh\UpgradeCenter\App;

defined('BOOTSTRAP') or die('Access denied');

/**
 * Returns additional input or label attributes for default_tracking setting
 *
 * @return array<string, array<string, int|string>> List of the attributes
 */
function fn_settings_handlers_general_global_tracking()
{
    return [
        'input_attributes' => [
            'checked_value' => ProductTracking::TRACK,
            'unchecked_value' => ProductTracking::DO_NOT_TRACK,
        ]
    ];
}

/**
 * Returns additional input or label attributes for default_tracking setting
 *
 * @return array<string, array<string, int|string>> List of the attributes
 */
function fn_settings_handlers_general_default_tracking()
{
    return fn_settings_handlers_general_global_tracking();
}

/**
 * Provides stub for the "Close storefront" setting.
 *
 * @return string
 */
function fn_get_store_mode_notice()
{
    // FIXME: Bad style
    $highlight = isset($_REQUEST['highlight'])
        ? explode(',', $_REQUEST['highlight'])
        : [];

    $tpl = <<<HTML
<div class="control-group %s">
    <label class="control-label">%s:</label>
    <div class="controls">
        <p>
            <a href="%s">%s</a>
        </p>
    </div>
</div>
HTML;

    $label = __('close_storefront');
    $class = in_array('store_mode', $highlight) ? 'row-highlight' : '';
    $url = fn_get_storefront_status_manage_url();
    $text = __('close_storefront.setting_notice');

    return sprintf($tpl, $class, $label, $url, $text);
}

/**
 * Provides stub for the "Access key to temporarily closed storefront" setting.
 *
 * @return string
 */
function fn_get_store_access_key_notice()
{
    // FIXME: Bad style
    $highlight = isset($_REQUEST['highlight'])
        ? explode(',', $_REQUEST['highlight'])
        : [];

    $tpl = <<<HTML
<div class="control-group %s">
    <label class="control-label">%s:</label>
    <div class="controls">
        <p>
            <a href="%s">%s</a>
        </p>
    </div>
</div>
HTML;

    $label = __('storefront_access_key');
    $class = in_array('store_access_key', $highlight) ? 'row-highlight' : '';
    $url = fn_get_storefront_status_manage_url();
    $text = __('storefront_access_key.setting_notice');

    return sprintf($tpl, $class, $label, $url, $text);
}

/**
 * Shows current product version, available upgrade and latest release dates.
 *
 * @return string
 *
 * @psalm-suppress MissingThrowsDocblock
 */
function fn_settings_handlers_upgrade_center_product_release_info()
{
    $tpl = <<<HTML
<div class="control-group">
    <label class="control-label">%s:</label>
    <div class="controls">
        <p>
            %s
        </p>
    </div>
</div>
HTML;

    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];
    /** @var \Tygh\SoftwareProductEnvironment $env */
    $env = Tygh::$app['product.env'];

    $response = sprintf($tpl, __('product_env.current_version'), $view->fetch('common/product_release_info.tpl'));

    $app = App::instance();
    $app->checkUpgrades(false);
    $upgrade_packages = $app->getPackagesList();

    if (!empty($upgrade_packages['core']['core'])) {
        $upgrade = $upgrade_packages['core']['core'];
        $env = new SoftwareProductEnvironment(
            $env->getProductName(),
            $upgrade['to_version'],
            $env->getStoreMode(),
            $env->getProductStatus(),
            $env->getProductBuild(),
            $env->getProductEdition(),
            $upgrade['timestamp']
        );
        $view->assign('env_provider', $env);
        $response .= sprintf($tpl, __('product_env.available_upgrade'), $view->fetch('common/product_release_info.tpl'));

        if (
            !empty($upgrade['latest_available_version']) &&
            $upgrade['latest_available_version'] !== $upgrade['to_version']
        ) {
            $env = new SoftwareProductEnvironment(
                $env->getProductName(),
                $upgrade['latest_available_version'],
                $env->getStoreMode(),
                $env->getProductStatus(),
                $env->getProductBuild(),
                $env->getProductEdition(),
                $upgrade['latest_available_version_timestamp']
            );
            $view->assign('env_provider', $env);
            $response .= sprintf($tpl, __('product_env.latest_version'), $view->fetch('common/product_release_info.tpl'));
        }
    }

    return $response;
}
