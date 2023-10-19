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

namespace Tygh\Addons\AdvancedImport;

class FeaturesMapper
{
    /** @var string $variants_delimiter */
    protected $variants_delimiter;

    /**
     * FeaturesMapper constructor.
     *
     * @param string $variants_delimiter
     */
    public function __construct($variants_delimiter = '///')
    {
        $this->variants_delimiter = $variants_delimiter;
    }

    /**
     * Remaps array of features to the format compatible with \fn_exim_save_product_features_values().
     *
     * @param array $features_list      Features list
     * @param null  $variants_delimiter Feature variants delimiter (for multi-select)
     *
     * @return array
     */
    public function remap(array $features_list, $variants_delimiter = null)
    {
        if ($variants_delimiter === null) {
            $variants_delimiter = $this->variants_delimiter;
        }

        $main_lang = $this->getMainLanguageCode($features_list);

        $features_list_rebuilt = array();

        foreach ($features_list as $lang_code => &$features_values) {
            foreach ($features_values as $feature_id => &$feature) {

                if (!isset($features_list_rebuilt[$feature_id])) {
                    $features_list_rebuilt[$feature_id] = array(
                        'feature_id' => $feature_id,
                        'variants'   => array(),
                    );
                }

                foreach (explode($variants_delimiter, $feature) as $id => $variant) {
                    $features_list_rebuilt[$feature_id]['variants'][$id][$lang_code] = $variant;
                }
            }
            unset($feature);
        }
        unset($features_values);

        foreach ($features_list_rebuilt as $feature_id => &$feature) {
            foreach ($feature['variants'] as &$variant) {
                $variant = array(
                    'name'  => $variant[$main_lang],
                    'names' => $variant,
                );
            }
            unset($variant);
        }
        unset($feature);

        return $features_list_rebuilt;
    }

    /**
     * Gets main language code from features list.
     *
     * @param array $features_list Features list
     *
     * @return string Language code
     */
    public function getMainLanguageCode(array $features_list)
    {
        reset($features_list);
        $main_lang = key($features_list);

        return $main_lang;
    }
}