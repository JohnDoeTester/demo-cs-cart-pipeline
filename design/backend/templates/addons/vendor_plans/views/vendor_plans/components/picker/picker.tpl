{*
    $item_ids                   array                   List of product ID
    $picker_id                  string                  Picker unique ID
    $input_name                 string                  Select input name
    $multiple                   bool                    Whether to multiple selection
    $show_advanced              bool                    Show advanced button
    $autofocus                  bool                    Whether to auto focus on input
    $autoopen                   bool                    Whether to auto open dropdown
    $allow_clear                bool                    Show clear button
    $empty_variant_text         string                  Empty variant text
    $view_mode                  enum (simple|external)  View mode
    $meta                       string                  Object picker class
    $select_group_class         string                  Select group class
    $advanced_class             string                  Advanced class
    $simple_class               string                  Simple class
    $select_class               string                  Select class
    $selected_external_class    string                  Selected external class
    $selection_class            string                  Selection class
    $result_class               string                  Result class
*}

{$picker_id = $picker_id|default:uniqid()}
{$input_name = $input_name|default:"object_picker_simple_`$picker_id`"}
{$multiple = $multiple|default:false}
{$autofocus = $autofocus|default:false}
{$autoopen = $autoopen|default:false}
{$allow_add = $allow_add|default:false}
{$item_ids = $item_ids|default:[]|array_filter}
{$empty_variant_text = $empty_variant_text|default:__("none")}

<div class="object-picker object-picker--vendor-plan {$meta}" data-object-picker="object_picker_{$picker_id}">
    <div class="object-picker__select-group object-picker__select-group--vendor-plan {$select_group_class}">
        <div class="object-picker__simple {if $type == "list"}object-picker__simple--list{/if} object-picker__simple--vendor-plan {$simple_class}">
            <select readonly name="{$input_name}"
                    id="{$picker_id}_elem"
                    class="cm-object-picker object-picker__select object-picker__select--vendor-plan {$select_class}"
                    data-ca-current-plan-id="{$current_plan_id}"
                    data-ca-object-picker-object-type="vendor-plan"
                    data-ca-object-picker-escape-html="false"
                    data-ca-object-picker-ajax-url="{"vendor_plans.picker"|fn_url}"
                    data-ca-object-picker-ajax-delay="250"
                    data-ca-object-picker-template-result-selector="#object_picker_result_vendor_plan_template_{$picker_id}"
                    data-ca-object-picker-template-selection-selector="#object_picker_selection_vendor_plan_template_{$picker_id}"
                    data-ca-object-picker-template-selection-load-selector="#object_picker_selection_load_vendor_plan_template_{$picker_id}"
                    data-ca-object-picker-autofocus="{$autofocus|to_json}"
                    data-ca-object-picker-autoopen="{$autoopen}"
                    data-ca-object-picker-placeholder="{$empty_variant_text|escape:"javascript"}"
                    data-ca-object-picker-extended-picker-id="object_picker_advanced_{$picker_id}"
                    {if $allow_add}
                        data-ca-object-picker-enable-create-object="true"
                        data-ca-object-picker-template-result-new-selector="#object_picker_result_new_selector_vendor_plan_template_{$picker_id}"
                        data-ca-object-picker-template-selection-new-selector="#object_picker_selection_new_selector_vendor_plan_template_{$picker_id}"
                    {/if}
            >
                {foreach $item_ids as $item_id}
                    <option
                        value="{$item_id}"
                        selected="selected"
                    ></option>
                {/foreach}
            </select>
        </div>
    </div>
</div>

<script type="text/template" id="object_picker_result_vendor_plan_template_{$picker_id}" data-no-defer="true" data-no-execute="§">
    <div class="object-picker__result object-picker__result--vendor-plan {$result_class}">
        {include file="addons/vendor_plans/views/vendor_plans/components/picker/item.tpl"
            type="result"
        }
    </div>
</script>

<script type="text/template" id="object_picker_selection_vendor_plan_template_{$picker_id}" data-no-defer="true" data-no-execute="§">
    <div class="cm-object-picker-object object-picker__selection object-picker__selection--vendor-plan">
        {include file="addons/vendor_plans/views/vendor_plans/components/picker/item.tpl"
            type="selection"
        }
    </div>
</script>

<script type="text/template" id="object_picker_selection_load_vendor_plan_template_{$picker_id}" data-no-defer="true" data-no-execute="§">
    <div class="cm-object-picker-object object-picker__selection object-picker__selection--vendor-plan">
        {include file="addons/vendor_plans/views/vendor_plans/components/picker/item.tpl"
            type="load"
        }
    </div>
</script>

<script type="text/template" id="object_picker_result_new_selector_vendor_plan_template_{$picker_id}" data-no-defer="true" data-no-execute="§">
    <div class="object-picker__result-vendor-plan object-picker__result-vendor-plan--new">
        {include file="addons/vendor_plans/views/vendor_plans/components/picker/item.tpl"
            type="new_item"
            title_pre=__("add")
        }
    </div>
</script>

<script type="text/template" id="object_picker_selection_new_selector_vendor_plan_template_{$picker_id}" data-no-defer="true" data-no-execute="§">
    <div class="object-picker__selection-vendor-plan object-picker__selection-vendor-plan--new">
        {include file="addons/vendor_plans/views/vendor_plans/components/picker/item.tpl"
            type="new_item"
            icon=false
        }
    </div>
</script>

