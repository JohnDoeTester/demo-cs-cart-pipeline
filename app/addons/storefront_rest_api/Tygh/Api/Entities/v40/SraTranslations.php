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

namespace Tygh\Api\Entities\v40;

use Tygh\Api\Entities\Langvars;
use Tygh\Api\Response;
use Tygh\Languages\Values;
use Tygh\Registry;

/**
 * Class SraPages
 *
 * @package Tygh\Api\Entities
 */
class SraTranslations extends Langvars
{
    /**
     * @inheritdoc
     */
    public function index($id = 0, $params = [])
    {
        $params = array_merge([
            'name' => null,
        ], $params);

        if (!$this->isAllowedVariablePrefix($params['name'])) {
            return [
                'status' => Response::STATUS_FORBIDDEN,
                'data'   => [],
            ];
        }

        $params['items_per_page'] = 0;
        $result = parent::index($id, $params);

        if (!$id) {
            $area = fn_get_area_name(AREA);
            $default_language = Registry::get('settings.Appearance.' . $area . '_default_language');
            if ($default_language === $result['data']['params']['lang_code']) {
                $defaults = $result['data']['langvars'];
            } else {
                $defaults_params = $result['data']['params'];
                $defaults_params['lang_code'] = $default_language;
                list($defaults,) = Values::getVariables($defaults_params);
            }
            $result['data']['langvars'] = $this->loadOriginalValues($result['data']['langvars'], $defaults);
        }

        return $result;
    }

    /** @inheritdoc */
    public function privileges()
    {
        return [
            'index' => true,
        ];
    }

    /** @inheritdoc */
    public function privilegesCustomer()
    {
        return [
            'index' => true,
        ];
    }

    /** @inheritdoc */
    public function create($params)
    {
        return [
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
        ];
    }

    /** @inheritdoc */
    public function update($id, $params)
    {
        return [
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
        ];
    }

    /** @inheritdoc */
    public function delete($id)
    {
        return [
            'status' => Response::STATUS_METHOD_NOT_ALLOWED,
        ];
    }

    /**
     * Injects original values into the language variables.
     * When original value is not found, value in the default language will be provided instead.
     *
     * @param array $variables Variables to load original values for
     * @param array $defaults Variables in the default language
     *
     * @return array Variables with original values injected
     */
    protected function loadOriginalValues(array $variables, array $defaults)
    {
        $langvars_with_original_values = Values::loadOriginalValues($variables);

        $defaults = array_column($defaults, 'value', 'name');

        $langvars_with_original_values = array_map(
            function ($variable) use ($defaults) {
                if ($variable['original_value'] === null && isset($defaults[$variable['name']])) {
                    $variable['original_value'] = $defaults[$variable['name']];
                }

                return $variable;
            },
            $langvars_with_original_values
        );

        return $langvars_with_original_values;
    }

    /**
     * Checks whether language variable prefix is allowed.
     *
     * This function is used to prevent disclosure of add-ons installed in the store.
     *
     * @param string $prefix
     *
     * @return boolean Whether language variables with the specified prefix can be loaded
     */
    protected function isAllowedVariablePrefix($prefix)
    {
        $prefix = (string) $prefix;

        $allowed_prefixes = fn_get_schema('storefront_rest_api', 'translation_prefixes');

        $is_all_allowed = !empty($allowed_prefixes['*']);
        $is_prefix_exists = isset($allowed_prefixes[$prefix]);
        $is_prefix_allowed = $is_prefix_exists && $allowed_prefixes[$prefix] === true;

        return $is_all_allowed && !$is_prefix_exists
            || $is_prefix_allowed;
    }
}
