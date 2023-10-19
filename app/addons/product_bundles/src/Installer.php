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

namespace Tygh\Addons\ProductBundles;

use Tygh\Addons\InstallerWithDemoInterface;
use Tygh\Addons\ProductBundles\Services\ProductBundleService;
use Tygh\BlockManager\Block;
use Tygh\BlockManager\ProductTabs;
use Tygh\Core\ApplicationInterface;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\ProductTabTypes;
use Tygh\Enum\SiteArea;
use Tygh\Enum\YesNo;
use Tygh\Languages\Languages;
use Tygh\Registry;

class Installer implements InstallerWithDemoInterface
{

    /**
     * @inheritDoc
     */
    public static function factory(ApplicationInterface $app)
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function onBeforeInstall()
    {
    }

    /**
     * @inheritDoc
     */
    public function onInstall()
    {
        if (fn_allowed_for('ULTIMATE')) {
            $company_ids = fn_get_all_companies_ids();
        } else {
            $company_ids = [0];
        }

        $block = Block::instance();
        $product_tabs = ProductTabs::instance();

        foreach ($company_ids as $company_id) {
            $block_data = [
                'type'         => 'product_bundles',
                'properties'   => [
                    'template' => 'addons/product_bundles/blocks/product_bundles.tpl',
                ],
                'content_data' => [],
                'company_id'   => $company_id,
            ];

            $block_description = [
                'lang_code' => DEFAULT_LANGUAGE,
                'name'      => __('product_bundles.tab_title'),
                'lang_var'  => 'product_bundles.tab_title',
            ];

            $block_id = $block->update($block_data, $block_description);

            $tab_data = [
                'tab_type'      => ProductTabTypes::BLOCK,
                'block_id'      => $block_id,
                'template'      => 'addons/product_bundles/blocks/product_bundles.tpl',
                'addon'         => 'product_bundles',
                'status'        => ObjectStatuses::ACTIVE,
                'is_primary'    => YesNo::NO,
                'position'      => false,
                'product_ids'   => null,
                'company_id'    => $company_id,
                'show_in_popup' => YesNo::NO,
                'lang_code'     => DEFAULT_LANGUAGE,
                'name'          => __('product_bundles.tab_title'),
                'lang_var'      => 'product_bundles.tab_title',
            ];

            $product_tabs->update($tab_data);
        }
    }

    /**
     * @inheritDoc
     */
    public function onUninstall()
    {
        $linked_promotion_ids = db_get_fields('SELECT linked_promotion_id FROM ?:product_bundles');
        $linked_promotion_ids = array_filter($linked_promotion_ids);
        //phpcs:ignore
        if ($linked_promotion_ids) {
            fn_delete_promotions($linked_promotion_ids);
        }
    }

    /**
     * @inheritDoc
     */
    public function onDemo()
    {
        $bundle_service = new ProductBundleService();
        $all_langs = array_keys(Languages::getAll());
        $current_allow_external_uploads = Registry::ifGet('runtime.allow_upload_external_paths', false);
        Registry::set('runtime.allow_upload_external_paths', true, true);

        $this->createBundleWithPants($bundle_service, $all_langs);
        $this->createBundleWithCarAudio($bundle_service, $all_langs);
        $this->createBundleWithBackpack($bundle_service, $all_langs);

        Registry::set('runtime.allow_upload_external_paths', $current_allow_external_uploads, true);
    }

    /**
     * Creates demo bundle with specified products.
     *
     * @param ProductBundleService $service Service by creating bundles.
     * @param array<string>        $langs   All language codes.
     *
     * @return void
     */
    protected function createBundleWithPants(ProductBundleService $service, array $langs)
    {
        $bundle_data = [
            'name' => 'Sports pants bundle',
            'storefront_name' => 'Sports pants bundle',
            'company_id' => 1,
            'description' => 'Get 3 pairs of pants at the price of 2',
            'display_in_promotions' => YesNo::YES,
            'lang_code' => 'en',
            'date_from' => 0,
            'date_to' => 0,
            'status' => 'A',
            'products' => [
                '133857165' => [
                    'product_id' => 12,
                    'amount' => 3,
                    'modifier_type' => 'by_percentage',
                    'modifier' => 33.33,
                    'show_on_product_page' => YesNo::YES,
                    'aoc' => YesNo::YES,
                ],
            ],
        ];

        $_REQUEST['bundle_main_image_data'] = [
            [
                'pair_id' => '',
                'type' => 'M',
                'object_id' => '0',
                'image_alt' => '',
            ]
        ];
        $_REQUEST['file_bundle_main_image_icon'] = [
            fn_get_theme_path('[themes]/[theme]/media/images/addons/product_bundles/3.jpg'),
            SiteArea::ADMIN_PANEL
        ];
        $_REQUEST['type_bundle_main_image_icon'] = [
            'server'
        ];

        $bundle_id = $service->updateBundle($bundle_data);

        if (!in_array('ru', $langs)) {
            return;
        }

        $_REQUEST['bundle_main_image_data'][0]['object_id'] = $bundle_id;
        $bundle_data_ru = [
            'name' => 'Комплект спортивных брюк',
            'storefront_name' => 'Комплект спортивных брюк',
            'company_id' => 1,
            'description' => 'Купите 3 пары брюк по цене 2',
            'display_in_promotions' => YesNo::YES,
            'date_from' => 0,
            'lang_code' => 'ru',
            'date_to' => 0,
            'status' => 'A',
            'products' => [
                '133857165' => [
                    'product_id' => 12,
                    'amount' => 3,
                    'modifier_type' => 'by_percentage',
                    'modifier' => 33.33,
                    'show_on_product_page' => YesNo::YES,
                    'aoc' => YesNo::YES
                ],
            ],
        ];
        $service->updateBundle($bundle_data_ru, $bundle_id);
    }

    /**
     * Creates demo bundle with specified products.
     *
     * @param ProductBundleService $service Service by creating bundles.
     * @param array<string>        $langs   All language codes.
     *
     * @return void
     */
    protected function createBundleWithCarAudio(ProductBundleService $service, array $langs)
    {
        $bundle_data = [
            'name' => 'Car audio bundle',
            'storefront_name' => 'Car audio bundle',
            'company_id' => 1,
            'description' => 'Get everything you need for your car in one package, with 50% off',
            'display_in_promotions' => YesNo::YES,
            'date_from' => 0,
            'date_to' => 0,
            'status' => 'A',
            'lang_code' => 'en',
            'products' => [
                '357040185' => [
                    'product_id' => 39,
                    'amount' => 1,
                    'modifier_type' => 'by_percentage',
                    'modifier' => 50,
                    'show_on_product_page' => YesNo::YES,
                ],
                '1305030541' => [
                    'product_id' => 51,
                    'amount' => 1,
                    'modifier_type' => 'by_percentage',
                    'modifier' => 50,
                    'show_on_product_page' => YesNo::YES,
                ],
                '725582281' => [
                    'product_id' => 52,
                    'amount' => 1,
                    'modifier_type' => 'by_percentage',
                    'modifier' => 50,
                    'show_on_product_page' => YesNo::YES,
                ],
            ],
        ];

        $_REQUEST['bundle_main_image_data'] = [
            [
                'pair_id' => '',
                'type' => 'M',
                'object_id' => '0',
                'image_alt' => '',
            ]
        ];
        $_REQUEST['file_bundle_main_image_icon'] = [
            fn_get_theme_path('[themes]/[theme]/media/images/addons/product_bundles/1.jpg'),
            SiteArea::ADMIN_PANEL
        ];
        $_REQUEST['type_bundle_main_image_icon'] = [
            'server'
        ];

        $bundle_id = $service->updateBundle($bundle_data);

        if (!in_array('ru', $langs)) {
            return;
        }

        $_REQUEST['bundle_main_image_data'][0]['object_id'] = $bundle_id;
        $bundle_data_ru = [
            'name'                  => 'Аудиокомплект для автомобиля',
            'storefront_name'       => 'Аудиокомплект для автомобиля',
            'company_id'            => 1,
            'description'           => 'Всё, что нужно для автомобиля, в одном комплекте и со скидкой 50%',
            'display_in_promotions' => YesNo::YES,
            'date_from'             => 0,
            'date_to'               => 0,
            'status'                => 'A',
            'lang_code'             => 'ru',
            'products'              => [
                '357040185' => [
                    'product_id'           => 39,
                    'amount'               => 1,
                    'modifier_type'        => 'by_percentage',
                    'modifier'             => 50,
                    'show_on_product_page' => YesNo::YES,
                ],
                '1305030541' => [
                    'product_id'           => 51,
                    'amount'               => 1,
                    'modifier_type'        => 'by_percentage',
                    'modifier'             => 50,
                    'show_on_product_page' => YesNo::YES,
                ],
                '725582281' => [
                    'product_id'           => 52,
                    'amount'               => 1,
                    'modifier_type'        => 'by_percentage',
                    'modifier'             => 50,
                    'show_on_product_page' => YesNo::YES,
                ],
            ],
        ];
        $service->updateBundle($bundle_data_ru, $bundle_id);
    }

    /**
     * Creates demo bundle with specified products.
     *
     * @param ProductBundleService $service Service by creating bundles.
     * @param array<string>        $langs   All language codes.
     *
     * @return void
     */
    protected function createBundleWithBackpack(ProductBundleService $service, array $langs)
    {
        $bundle_data = [
            'name' => 'Camping bundle',
            'storefront_name' => 'Camping bundle',
            'company_id' => 1,
            'description' => '33% off everything a traveler needs',
            'display_in_promotions' => YesNo::YES,
            'date_from' => 0,
            'date_to' => 0,
            'status' => 'A',
            'lang_code' => 'en',
            'products' => [
                '1804064557' => [
                    'product_id' => 237,
                    'amount' => 1,
                    'modifier_type' => 'by_percentage',
                    'modifier' => 33,
                    'show_on_product_page' => YesNo::YES,
                ],
                '169655664' => [
                    'product_id' => 230,
                    'amount' => 1,
                    'modifier_type' => 'by_percentage',
                    'modifier' => 33,
                    'show_on_product_page' => YesNo::YES,
                ],
                '225540457' => [
                    'product_id' => 234,
                    'amount' => 1,
                    'modifier_type' => 'by_percentage',
                    'modifier' => 33,
                    'show_on_product_page' => YesNo::YES,
                ]
            ],
        ];

        $_REQUEST['bundle_main_image_data'] = [
            [
                'pair_id' => '',
                'type' => 'M',
                'object_id' => '0',
                'image_alt' => '',
            ]
        ];
        $_REQUEST['file_bundle_main_image_icon'] = [
            fn_get_theme_path('[themes]/[theme]/media/images/addons/product_bundles/2.jpg'),
            SiteArea::ADMIN_PANEL
        ];
        $_REQUEST['type_bundle_main_image_icon'] = [
            'server'
        ];

        $bundle_id = $service->updateBundle($bundle_data);

        if (!in_array('ru', $langs)) {
            return;
        }

        $_REQUEST['bundle_main_image_data'][0]['object_id'] = $bundle_id;
        $bundle_data_ru = [
            'name'                  => 'Набор для походов',
            'storefront_name'       => 'Набор для походов',
            'company_id'            => 1,
            'description'           => 'Всё, что нужно путешественнику, со скидкой 33%',
            'display_in_promotions' => YesNo::YES,
            'date_from'             => 0,
            'date_to'               => 0,
            'status'                => 'A',
            'lang_code'             => 'ru',
            'products'              => [
                '1804064557' => [
                    'product_id'           => 237,
                    'amount'               => 1,
                    'modifier_type'        => 'by_percentage',
                    'modifier'             => 33,
                    'show_on_product_page' => YesNo::YES,
                ],
                '169655664' => [
                    'product_id'           => 230,
                    'amount'               => 1,
                    'modifier_type'        => 'by_percentage',
                    'modifier'             => 33,
                    'show_on_product_page' => YesNo::YES,
                ],
                '225540457' => [
                    'product_id'           => 234,
                    'amount'               => 1,
                    'modifier_type'        => 'by_percentage',
                    'modifier'             => 33,
                    'show_on_product_page' => YesNo::YES,
                ]
            ],
        ];
        $service->updateBundle($bundle_data_ru, $bundle_id);
    }
}
