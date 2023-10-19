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

namespace Tygh\Template\Mail;


use Tygh\BlockManager\Layout;
use Tygh\Enum\SiteArea;
use Tygh\Registry;
use Tygh\Template\IContext;
use Tygh\Themes\Styles;
use Tygh\Tygh;

/**
 * The context class for email notifications.
 *
 * @package Tygh\Template\Mail
 */
class Context implements IContext
{
    /** @var string */
    protected $lang_code;

    /** @var array */
    public $data;

    /** @var string */
    protected $area;

    /**
     * Context constructor.
     *
     * @param array     $data       Mail message data.
     * @param string    $area       Area code.
     * @param string    $lang_code  Language code.
     */
    public function __construct(array $data, $area, $lang_code)
    {
        $company_id = 0;
        $data['settings'] = Registry::get('settings');

        if (!empty($data['company_data']['company_id'])) {
            $data['company_name'] = $data['company_data']['company_name'];
            $company_id = $data['company_data']['company_id'];
        }

        if (empty($company_id) && fn_allowed_for('ULTIMATE')) {
            $company_id = fn_get_runtime_company_id();

            if (empty($company_id)) {
                $company_id = fn_get_default_company_id();
            }
        }

        $storefront_id = null;

        if (fn_allowed_for('ULTIMATE')) {
            fn_ult_bootstrap_company_storefront($company_id, $storefront_id);
        } elseif (!empty($data['storefront_data']['storefront_id'])) {
            $storefront_id = $data['storefront_data']['storefront_id'];
        }

        if (empty($storefront_id) && !SiteArea::isStorefront(AREA)) {
            $storefront_id = Tygh::$app['storefront.repository']->findDefault()->storefront_id;
        }

        if (empty($data['company_data']) || !isset($data['company_data'])) {
            $data['company_data'] = fn_get_company_placement_info((int) $company_id);
            $data['company_name'] = $data['company_data']['company_name'];
        }

        $data['logos'] = $this->getCompanyLogos((int) $company_id, $storefront_id);
        $data['styles'] = $this->getStyles((int) $company_id, $storefront_id);
        $data['lang_code'] = $lang_code;
        $data['language_direction'] = fn_is_rtl_language($lang_code) ? 'rtl' : 'ltr';

        $this->data = $data;
        $this->lang_code = $lang_code;
        $this->area = $area;
    }

    /**
     * Gets company logos by company identifier
     *
     * @param int      $company_id    Company identifier
     * @param int|null $storefront_id Storefront identifier
     *
     * @return array
     */
    protected function getCompanyLogos($company_id, $storefront_id)
    {
        return fn_get_logos($company_id, null, null, $storefront_id);
    }

    /**
     * Gets company theme styles by company identifier
     *
     * @param int      $company_id    Company identifier
     * @param int|null $storefront_id Storefront identifier
     *
     * @return array
     */
    protected function getStyles($company_id, $storefront_id)
    {
        $theme_name = fn_get_theme_path('[theme]', 'C', $company_id, true, $storefront_id);
        $layout = Layout::instance($company_id, [], $storefront_id)->getDefault($theme_name);
        $styles = Styles::factory($theme_name)->get($layout['style_id'], array(
            'parse' => true,
        ));

        return isset($styles['data']) ? $styles['data'] : array();
    }

    /**
     * @inheritDoc
     */
    public function getLangCode()
    {
        return $this->lang_code;
    }

    /**
     * @inheritDoc
     */
    public function getArea()
    {
        return $this->area;
    }
}
