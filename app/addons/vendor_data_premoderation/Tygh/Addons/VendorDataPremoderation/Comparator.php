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

namespace Tygh\Addons\VendorDataPremoderation;

use Tygh\Core\ApplicationInterface;

/**
 * Class Comparator checks objects for changes that require premoderation.
 *
 * @package Tygh\Addons\VendorDataPremoderation
 */
class Comparator
{
    /**
     * @var \Tygh\Addons\VendorDataPremoderation\PremoderationSchema
     */
    protected $schema;

    /**
     * @var \Tygh\Core\ApplicationInterface
     */
    protected $service_provider;

    public function __construct(PremoderationSchema $schema, ApplicationInterface $service_provider)
    {
        $this->schema = $schema;
        $this->service_provider = $service_provider;
    }

    /**
     * Compares two states of an object.
     *
     * @param \Tygh\Addons\VendorDataPremoderation\State $initial_state   Initial object state
     * @param \Tygh\Addons\VendorDataPremoderation\State $resulting_state Resulting object state
     * @param bool                                       $get_full_diff   Whether to check all object data sources.
     *                                                                    If set to false, comparison process will be
     *                                                                    stopped on the first change found
     *
     * @return \Tygh\Addons\VendorDataPremoderation\Diff Contains sources with changed data that require premoderation
     */
    public function compare(State $initial_state, State $resulting_state, $get_full_diff = false)
    {
        $diff = new Diff();

        $sources = $initial_state->getSources();

        foreach ($sources as $source_name) {
            if (!$this->isPremoderatedSource($source_name)) {
                continue;
            }

            $initial_data = $initial_state->getSourceData($source_name);
            $resulting_data = $resulting_state->getSourceData($source_name);
            if ($initial_data === $resulting_data) {
                continue;
            }

            $fields = $initial_state->getSourceSchema($source_name)
                ?: $resulting_state->getSourceSchema($source_name);
            if (!$fields) {
                continue;
            }

            $is_changed = count($initial_data) !== count($resulting_data);
            if (!$is_changed) {
                foreach ($initial_data as $i => $initial_datum) {
                    $resulting_datum = $resulting_data[$i];
                    foreach ($fields as $field_name) {
                        if (!$this->isPremoderatedField($source_name, $field_name)) {
                            continue;
                        }

                        if ($resulting_datum[$field_name] !== $initial_datum[$field_name]) {
                            $is_changed = true;
                            if (!$get_full_diff) {
                                break 2;
                            }

                            $diff->addChangedField($source_name, $field_name);
                        }
                    }
                }
            }

            if ($is_changed) {
                $diff->addChangedSource($source_name);
                if (!$get_full_diff) {
                    return $diff;
                }
            }
        }

        return $diff;
    }

    /**
     * Checks whether changes in source data trigger premoderation process.
     *
     * @param string $source_name
     *
     * @return bool Whether premoderation is required for the source at all.
     *              If false is returned, the changes in the source do not trigger premoderation.
     *              If true is returned, each field of source data must be checked
     */
    protected function isPremoderatedSource($source_name)
    {
        $premoderation = $this->schema->getSourcePremoderation($source_name);
        if (is_bool($premoderation)) {
            return $premoderation;
        }

        list($service_name, $method) = $premoderation;

        return call_user_func([$this->service_provider[$service_name], $method], $source_name);
    }

    /**
     * Checks whether changes in source data field trigger premoderation process.
     *
     * @param string $source_name
     * @param string $field_name
     *
     * @return bool
     */
    protected function isPremoderatedField($source_name, $field_name)
    {
        $premoderation = $this->schema->getFieldPremoderation($source_name, $field_name);
        if (is_bool($premoderation)) {
            return $premoderation;
        }

        list($service_name, $method) = $premoderation;

        return call_user_func([$this->service_provider[$service_name], $method], $source_name, $field_name);
    }
}
