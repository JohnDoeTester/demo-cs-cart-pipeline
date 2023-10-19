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

namespace Tygh\Addons\AdvancedImport\Presets;

use Tygh\Addons\AdvancedImport\Modifiers\Parsers\IModifierParser;
use Tygh\Addons\AdvancedImport\SchemasManager;
use Tygh\Enum\Addons\AdvancedImport\RelatedObjectTypes;
use Tygh\Addons\AdvancedImport\Exceptions\InvalidModifierFormatException;
use Tygh\Addons\AdvancedImport\Exceptions\InvalidModifierParameterException;

class Importer
{
    /** @var SchemasManager $schemas_manager */
    protected $schemas_manager;

    /** @var \Tygh\Addons\AdvancedImport\Modifiers\Parsers\IModifierParser $modifier_parser */
    protected $modifier_parser;

    /** @var array $pattern */
    protected $pattern = array();

    /**
     * Importer constructor.
     *
     * @param SchemasManager $schemas_manager Schemas manager instance
     */
    public function __construct(SchemasManager $schemas_manager, IModifierParser $modifier_parser)
    {
        $this->schemas_manager = $schemas_manager;
        $this->modifier_parser = $modifier_parser;
    }

    /**
     * Renames imported items' fields to database fields and exim columns.
     *
     * @param array $import_schema  List of properties of an import item
     * @param array $fields_mapping Fields mapping of a preset
     * @param array $pattern        Exim pattern
     *
     * @return array|null Exim-compatible schema or null on error
     */
    public function getEximSchema(array $import_schema, array $fields_mapping, array $pattern)
    {
        foreach ($import_schema as &$field_name) {
            if (isset($fields_mapping[$field_name]) && is_array($fields_mapping[$field_name])) {
                $field_name = $this->getEximFieldName($fields_mapping[$field_name]);
            } else {
                $field_name = null;
            }
        }
        unset($field_name);

        if (fn_exim_analyze_schema($import_schema, $pattern)) {
            return $import_schema;
        }

        return null;
    }

    /**
     * Gets exim-compatible field name.
     *
     * @param array $field Field definition. Must contain 'related_object_type' and 'related_object' values
     *
     * @return string
     */
    protected function getEximFieldName(array $field)
    {
        if ($field['related_object_type'] == RelatedObjectTypes::PROPERTY) {
            return $field['related_object'];
        }

        return $field['related_object_type'] . '_' . $field['related_object'];
    }

    /**
     * Applies modifiers and remaps fields to exim naming in the imported items list.
     *
     * @param array  $items_list       Imported items
     * @param array  $fields           Fields mapping of a preset
     * @param string $object_type      Preset type
     * @param bool   $rewrite_value    Whether to rewrite field value on modifier apply
     * @param array  $remapping_schema Array with new property names of imported items
     *
     * @return array
     */
    public function prepareImportItems(
        array $items_list,
        array $fields,
        $object_type,
        $rewrite_value = false,
        array $remapping_schema = null
    ) {
        $relations = $this->schemas_manager->getRelations();

        foreach ($items_list as &$item) {
            if ($item === false) {
                continue;
            }

            if ($remapping_schema
                && !isset($rename_fields_schema)
            ) {
                $rename_fields_schema = array_combine(
                    array_keys($item),
                    $remapping_schema
                );
            }

            $aggregations = array();

            foreach ($item as $field_name => &$field_value) {
                $modifier = isset($fields[$field_name]['modifier'])
                    ? $fields[$field_name]['modifier']
                    : '';
                $related_object_type = isset($fields[$field_name]['related_object_type'])
                    ? $fields[$field_name]['related_object_type']
                    : RelatedObjectTypes::SKIP;
                if ($rewrite_value) {
                    $field_value = $this->applyModifier($field_value, $modifier, $item);
                } else {
                    $field_value = array(
                        'original' => $field_value,
                        'modified' => $this->applyModifier($field_value, $modifier, $item),
                    );
                }

                if ($related_object_type == RelatedObjectTypes::SKIP) {
                    continue;
                }

                if ($remapping_schema &&
                    isset($relations[$object_type][$related_object_type])
                ) {
                    $relation = $relations[$object_type][$related_object_type];
                    $aggregate_field = $relation['aggregate_field'];
                    if (!isset($aggregations[$aggregate_field])) {
                        $aggregations[$aggregate_field] = $relation;
                    }
                    $aggregations[$aggregate_field]['values'][$rename_fields_schema[$field_name]] = $field_value;

                    $item[$aggregate_field] = '';
                    $rename_fields_schema[$aggregate_field] = $aggregate_field;
                }
            }
            unset($field_value);

            if ($remapping_schema) {
                $item = $this->remapItem($item, $remapping_schema, $aggregations);
            }
        }
        unset($item);

        return $items_list;
    }

    /**
     * Applies modifier to a value.
     *
     * @param string|int|float $value          Field value
     * @param string|null      $modifier       Modifier
     * @param array            $item           Whole imported item
     * @param array|null       $modifiers_list Modifiers schema
     *
     * @return mixed
     */
    public function applyModifier($value, $modifier, array $item, $modifiers_list = null)
    {
        if (!$modifier) {
            return $value;
        }

        try {
            $parsed = $this->modifier_parser->parse($modifier);
        } catch (InvalidModifierFormatException $e) {
            fn_set_notification('E', __('error'), $e->getMessage());
        } catch (InvalidModifierParameterException $e) {
            fn_set_notification('E', __('error'), __('advanced_import.invalid_modifier_message', array('[modifier]' => $modifier, '[message]' => $e->getMessage())));
        }

        if ($modifiers_list === null) {
            $modifiers_list = $this->schemas_manager->getModifiers();
        }

        if (!empty($parsed['function']) && isset($parsed['parameters'])) {

            if (!empty($modifiers_list['operations'][$parsed['function']])) {
                $operation = $modifiers_list['operations'][$parsed['function']];

                if (!is_callable($operation['operation'])) {
                    return $value;
                }

                $expected_params_qty = $operation['parameters'];
                $params_qty = count($parsed['parameters']);

                $params = $parsed['parameters'];

                if (!is_null($expected_params_qty)) {

                    if ($params_qty != $expected_params_qty) {
                        fn_set_notification(
                            'E',
                            __('error'),
                            __('advanced_import.invalid_number_of_parameters_provided', array(
                                '[modifier]'       => $modifier,
                                '[expected_count]' => $expected_params_qty,
                                '[actual_count]'   => $params_qty,
                            )));

                        return $value;
                    }
                }

                if (isset($operation['current'])) {
                    $self_key = array_search($operation['current'], $params);

                    if ($self_key !== false) {
                        $params[$self_key] = $value;
                    }

                    $params = array_map(function ($item) use ($operation, $value) {
                        return str_replace($operation['current'], $value, $item);
                    }, $params);
                }

                $value = call_user_func_array($operation['operation'], $params);
            } else {
                fn_set_notification('E', __('error'), __('advanced_import.unrecognized_modifier', array('[modifier]' => $modifier)));
            }
        }

        return $value;
    }

    /**
     * Remaps imported item in accordance with the remapping schema.
     *
     * @param array $item             Item to remap
     * @param array $remapping_schema Schema that contains new names of product fields
     * @param array $aggregations     Fields aggregations
     *
     * @return array Remaped item
     */
    protected function remapItem(array $item, array $remapping_schema, array $aggregations = array())
    {
        $source_schema = array_keys($item);
        $remapped_item = array();
        foreach ($remapping_schema as $i => $remapped_field) {
            // when multiple columns are mapped to a single field, values can be aggregated into an array
            if (isset($remapped_item[$remapped_field])
                && !empty($this->pattern['export_fields'][$remapped_field]['is_aggregatable'])
            ) {
                $remapped_item[$remapped_field] = (array) $remapped_item[$remapped_field];
                $remapped_item[$remapped_field][] = $item[$source_schema[$i]];
            } else {
                $remapped_item[$remapped_field] = $item[$source_schema[$i]];
            }
        }

        foreach ($aggregations as $aggregate_field => $aggregated_data) {
            $remapped_item[$aggregate_field] = call_user_func(
                $aggregated_data['aggregate_function'],
                $item,
                $aggregated_data
            );
        }

        return $remapped_item;
    }

    /**
     * Sets pattern to use in the further import operations.
     *
     * @param array $pattern Pattern
     */
    public function setPattern(array $pattern)
    {
        $this->pattern = $pattern;
    }
}