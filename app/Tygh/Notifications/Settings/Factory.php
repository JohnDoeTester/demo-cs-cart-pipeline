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

namespace Tygh\Notifications\Settings;

use Tygh\Enum\YesNo;

/**
 * Class Factory creates notification settings rulesets.
 *
 * @package Tygh\Notifications\Settings
 */
class Factory
{
    /**
     * @var string[]
     */
    protected $receivers_schema;

    /**
     * @var string[]
     */
    protected $transports_schema;

    public function __construct(array $receivers_schema, array $transports_schema)
    {
        $this->receivers_schema = array_fill_keys($receivers_schema, true);
        $this->transports_schema = array_fill_keys($transports_schema, true);
    }

    /**
     * Creates ruleset instance.
     *
     * @param array<array-key, string|bool|array<string, bool>>|bool $rules Rules
     *
     * @return \Tygh\Notifications\Settings\Ruleset
     */
    public function create($rules)
    {
        $rules = $this->convertToRules($rules);
        $rules = $this->filterReceivers($rules);
        $rules = $this->normalizeRules($rules);

        return new Ruleset($rules);
    }

    /**
     * Converts enforced rules to the receiver- and transport-specific rules.
     *
     * @param array|bool $rules
     *
     * @return array
     */
    public function convertToRules($rules)
    {
        if (!is_array($rules)) {
            $rules = array_fill_keys(array_keys($this->receivers_schema), $rules);
        } else {
            foreach ($rules as $receiver => &$rule) {
                if (!is_array($rule)) {
                    $rule = array_fill_keys(array_keys($this->transports_schema), $rule);
                }
            }
        }

        return $rules;
    }

    /**
     * Filters out non-existing receivers from rules.
     *
     * @param array $rules
     *
     * @return array
     */
    public function filterReceivers(array $rules)
    {
        $rules = array_filter($rules, function ($receiver) {
            return isset($this->receivers_schema[$receiver]);
        }, ARRAY_FILTER_USE_KEY);

        return $rules;
    }

    /**
     * Normalizes receiver- and transport-specific rules to boolean values.
     *
     * @param array $rules
     *
     * @return array
     */
    public function normalizeRules(array $rules)
    {
        array_walk($rules, function (&$override) {
            if (is_array($override) && $override) {
                $override = $this->normalizeRules($override);
            } else {
                /** @var bool|string $override */
                $override = YesNo::toBool($override);
            }
        });

        return $rules;
    }
}
