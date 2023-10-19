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

namespace Tygh\Addons\MobileApp;

use Tygh\Languages\Values;

class TranslationManager
{
    const APP_VARIABLE_PREFIX = 'mobile_app.mobile_';

    /**
     * @var \Tygh\Languages\Values
     */
    protected $variables_manager;

    /**
     * @var array
     */
    protected $app_variables = [];

    /**
     * @var array
     */
    protected $variable_names_cache = [];

    /**
     * @var string
     */
    protected $language_code;

    /**
     * TranslationManager constructor.
     *
     * @param \Tygh\Languages\Values $variables_manager
     * @param string                 $language_code
     * @param array                  $app_variables
     */
    public function __construct(Values $variables_manager, $language_code, array $app_variables = [])
    {
        $this->variables_manager = $variables_manager;
        $this->app_variables = $app_variables;
        $this->language_code = $language_code;
    }

    /**
     * Gets variables from the language pack.
     *
     * @param array $variables_pack Language pack loaded from the locale file
     *
     * @return array[]
     */
    public function getVariables(array $variables_pack)
    {
        $variables = [];
        foreach ($variables_pack as $original_value => $value) {
            if (!is_string($value)) {
                continue;
            }

            $name = $this->getVariableName($original_value);
            if (!$name) {
                continue;
            }

            $variables[] = [
                'name'  => $name,
                'value' => $value,
            ];
        }

        return $variables;
    }

    /**
     * Updates language variables in the store.
     *
     * @param array[] $variables
     * @param string  $language_code
     *
     * @return array
     */
    public function update(array $variables, $language_code)
    {
        return $this->variables_manager->updateLangVar(
            $variables,
            $language_code
        );
    }

    /**
     * Gets variable name by its original value.
     *
     * @param string $original_value
     *
     * @return string|null
     */
    protected function getVariableName($original_value)
    {
        if (!isset($this->variable_names_cache[$original_value])) {
            $app_variables = $this->getAppVariables($this->language_code);

            $variable_name = false;
            foreach ($app_variables as $variable) {
                if ($variable['original_value'] === $original_value) {
                    $variable_name = $variable['name'];
                }
            }

            if ($variable_name === null) {
                foreach ($app_variables as $variable) {
                    if ($variable['value'] === $original_value) {
                        return $variable['name'];
                    }
                }
            }

            $this->variable_names_cache[$original_value] = $variable_name;
        }

        return $this->variable_names_cache[$original_value] === false
            ? null
            : $this->variable_names_cache[$original_value];
    }

    /**
     * Gets mobile app language variables in the specified language.
     *
     * @param string $language_code
     *
     * @return array[]
     */
    protected function getAppVariables($language_code)
    {
        if (!isset($this->app_variables[$language_code])) {
            list($variables,) = $this->variables_manager->getVariables(
                [
                    'name' => self::APP_VARIABLE_PREFIX,
                ],
                0,
                $language_code
            );

            $variables = $this->variables_manager->loadOriginalValues($variables);

            $this->app_variables[$language_code] = $variables;
        }

        return $this->app_variables[$language_code];
    }
}