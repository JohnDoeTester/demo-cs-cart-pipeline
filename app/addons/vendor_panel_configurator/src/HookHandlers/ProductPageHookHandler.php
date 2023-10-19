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

namespace Tygh\Addons\VendorPanelConfigurator\HookHandlers;

use Smarty_Internal_Template;
use Tygh\Addons\VendorPanelConfigurator\Enum\PageEntity;
use Tygh\Addons\VendorPanelConfigurator\ServiceProvider;
use Tygh\Enum\SiteArea;
use Tygh\Enum\UserTypes;
use Tygh\Registry;
use Tygh\Tygh;

class ProductPageHookHandler
{
    /**
     * @var array<
     *   string, array{
     *     is_visible: bool,
     *     is_optional: bool,
     *     sections: array<
     *       string, array{
     *         fields: array<
     *           string, array{
     *             title: string,
     *             position: int,
     *             is_optional: bool,
     *             is_visible: bool,
     *           }
     *         >
     *       }
     *     >
     *   }
     * >|null
     */
    protected $page_configuration;

    /** @var string */
    protected $user_type;

    /**
     * ProductPageHookHandler constructor.
     *
     * @param string                                         $user_type          Current user type
     * @param array<string, array<string, string|bool>>|null $page_configuration Product page configuration.
     *                                                                           Pass null to load schema lazily from the
     *                                                                           service provider
     *
     * @psalm-param array<
     *   string, array{
     *     is_visible: bool,
     *     is_optional: bool,
     *     sections: array<
     *       string, array{
     *         fields: array<
     *           string, array{
     *             title: string,
     *             position: int,
     *             is_optional: bool,
     *             is_visible: bool,
     *           }
     *         >
     *       }
     *     >
     *   }
     * > $page_configuration
     */
    public function __construct($user_type, $page_configuration = null)
    {
        $this->user_type = $user_type;
        $this->page_configuration = $page_configuration;
    }

    /**
     * The "smarty_component_configurable_page_field_before_output" hook handler.
     *
     * Actions performed:
     * - Hides product field that are disabled in the add-on settings.
     *
     * @param string                         $entity       Page entity
     * @param string                         $tab          Tab of the field on the page
     * @param string                         $section      Section of the field in the tab
     * @param string                         $field        Field identifier
     * @param array<string, string|bool|int> $field_config Field configuration
     * @param array<string, string>          $params       Component parameters
     * @param string                         $content      Output field content
     * @param \Smarty_Internal_Template      $template     Template instance
     *
     * @return void
     *
     * @see \smarty_component_configurable_page_field()
     */
    public function onBeforeFieldOutput(
        $entity,
        $tab,
        $section,
        $field,
        array &$field_config,
        array $params,
        $content,
        Smarty_Internal_Template $template
    ) {
        if (
            !UserTypes::isVendor($this->user_type)
            || !PageEntity::isProduct($entity)
            || !$field_config['is_optional']
        ) {
            return;
        }

        if (!isset($this->getPageConfiguration()[$tab]['sections'][$section]['fields'][$field])) {
            return;
        }

        $field_config['is_visible'] = $this->getPageConfiguration()[$tab]['sections'][$section]['fields'][$field]['is_visible'];
    }

    /**
     * The "smarty_component_configurable_page_section_before_output" hook handler.
     *
     * Actions performed:
     * - Hides product sections that have only disabled fields in the add-on settings.
     *
     * @param string                         $entity         Page entity
     * @param string                         $tab            Tab on the page
     * @param string                         $section        Section in the tab
     * @param array<string, string|bool|int> $section_config Section configuration
     * @param array<string, string>          $params         Component parameters
     * @param string                         $content        Output section content
     * @param \Smarty_Internal_Template      $template       Template instance
     *
     * @return void
     *
     * @see \smarty_component_configurable_page_section()
     */
    public function onBeforeSectionOutput(
        $entity,
        $tab,
        $section,
        array &$section_config,
        array $params,
        $content,
        Smarty_Internal_Template $template
    ) {
        if (
            !UserTypes::isVendor($this->user_type)
            || !PageEntity::isProduct($entity)
        ) {
            return;
        }

        if (!isset($this->getPageConfiguration()[$tab]['sections'][$section])) {
            return;
        }

        $section_config['is_visible'] = false;
        foreach ($this->getPageConfiguration()[$tab]['sections'][$section]['fields'] as $field) {
            if ($field['is_visible']) {
                $section_config['is_visible'] = true;

                return;
            }
        }
    }

    /**
     * The "dispatch_before_display" hook handler.
     *
     * Actions performed:
     * - Hides product page tabs that are disabled in the add-on settings.
     *
     * @return void
     *
     * @see \fn_dispatch()
     */
    public function onDispatchBeforeDisplay()
    {
        if (
            !UserTypes::isVendor($this->user_type)
            || !SiteArea::isAdmin(AREA)
        ) {
            return;
        }

        $controller = Registry::get('runtime.controller');
        $mode = Registry::get('runtime.mode');

        if (
            $controller !== 'products'
            || (!in_array($mode, ['update', 'add']))
        ) {
            return;
        }

        $tabs = Registry::get('navigation.tabs');

        if ($tabs === null) {
            return;
        }

        foreach ($tabs as $tab_id => &$tab) {
            if (
                !empty($tab['hidden'])
                || !isset($this->getPageConfiguration()[$tab_id])
            ) {
                continue;
            }

            $tab['hidden'] = !$this->getPageConfiguration()[$tab_id]['is_visible'];
        }
        unset($tab);

        Registry::set('navigation.tabs', $tabs);
    }

    /**
     * Gets product page configuration. Returns the value provided on the hook handler initialization or lazy-loads it
     * from the service provider.
     *
     * @return array<
     *   string, array{
     *     is_visible: bool,
     *     is_optional: bool,
     *     sections: array<
     *       string, array{
     *         fields: array<
     *           string, array{
     *             title: string,
     *             position: int,
     *             is_optional: bool,
     *             is_visible: bool,
     *           }
     *         >
     *       }
     *     >
     *   }
     * >
     */
    private function getPageConfiguration()
    {
        if ($this->page_configuration === null) {
            $this->page_configuration = ServiceProvider::getSettingsService()->getProductPageConfiguration();
        }

        return $this->page_configuration;
    }
}
