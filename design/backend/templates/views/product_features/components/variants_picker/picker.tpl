{*
    $feature_id                         int                     Product feature ID
    $item_ids                           array                   List of product ID
    $picker_id                          string                  Picker unique ID
    $input_name                         string                  Select input name
    $input_id                           string                  Select input id
    $multiple                           bool                    Whether to multiple selection
    $show_advanced                      bool                    Show advanced button
    $autofocus                          bool                    Whether to auto focus on input
    $autoopen                           bool                    Whether to auto open dropdown
    $allow_clear                        bool                    Show clear button
    $empty_variant_text                 string                  Empty variant text
    $meta                               string                  Object picker class
    $select_group_class                 string                  Select group class
    $advanced_class                     string                  Advanced class
    $simple_class                       string                  Simple class
    $select_class                       string                  Select class
    $selection_class                    string                  Selection class
    $result_class                       string                  Result class
    $unremovable_item_ids               array                   List of item IDs that non  removable
    $close_on_select                    bool                    Close dropdown after selection (default true)
    $enable_permanent_placeholder       bool                    Enable permanent placeholder for multiple selection
    $template_type                      string                  Type of product feature
*}

{$picker_id = $picker_id|default:uniqid()}
{$input_name = $input_name|default:"object_picker_simple_`$picker_id`"}
{$group_id = $group_id|default:"`$input_name`_group"}
{$multiple = $multiple|default:false}
{$show_advanced = $show_advanced|default:true}
{$autofocus = $autofocus|default:false}
{$autoopen = $autoopen|default:false}
{$close_on_select = $close_on_select|default:true}
{$allow_clear = $allow_clear|default:false}
{$item_ids = $item_ids|default:[]|array_filter}
{$search_data = $search_data|default:[]}
{$search_data.feature_id = $feature_id}
{$allow_add = $allow_add|default:false}
{$template_type = $template_type|default:""}
{$unremovable_item_ids = $unremovable_item_ids|default:[]}
{$enable_color = $enable_color|default:true}
{$disabled = $disabled|default:false}
{$lazy_load = $lazy_load|default:false}

{$predefined_variants = $predefined_variants|default:[]}
{$predefined_variant_items = []}

{if $show_empty_variant}
    {$predefined_variants["0"] = $empty_variant_text}
{/if}

{foreach $predefined_variants as $id => $variant}
    {$predefined_variant_items[] = ["id" => $id, "text" => $variant, "data" => ["name" => $variant]]}
{/foreach}

{if $multiple && $show_advanced}
    {$empty_variant_text = $empty_variant_text|default:__("type_to_search")}
{else}
    {$empty_variant_text = $empty_variant_text|default:__("none")}
{/if}

{$url = "product_features.get_variants_list"|fn_url}
{$meta = "cm-object-feature-variants-add-container `$meta`"}

<div class="object-picker object-picker--feature-variants {$meta}" data-object-picker="object_picker_{$picker_id}">
    <div class="object-picker__select-group object-picker__select-group--feature-variants {$select_group_class}">
        <div class="object-picker__simple {if $type == "list"}object-picker__simple--list{/if} {if $show_advanced}object-picker__simple--advanced{/if} object-picker__simple--feature-variants {$simple_class}">
            <select {if $multiple}multiple{/if}
                    name="{$input_name}"
                    {if $input_id}id="{$input_id}"{/if}
                    {if $disabled}disabled{/if}
                    class="cm-object-picker object-picker__select object-picker__select--feature-variants {$select_class}"
                    data-ca-object-picker-object-type="product_feature_variants"
                    data-ca-object-picker-escape-html="false"
                    data-ca-object-picker-ajax-url="{$url}"
                    data-ca-object-picker-ajax-delay="250"
                    data-ca-object-picker-autofocus="{$autofocus|to_json}"
                    data-ca-object-picker-close-on-select="{$close_on_select|to_json}"
                    data-ca-object-picker-autoopen="{$autoopen}"
                    data-ca-object-picker-placeholder="{$empty_variant_text}"
                    data-ca-object-picker-placeholder-value=""
                    data-ca-object-picker-search-request-data="{$search_data|to_json}"
                    data-ca-object-picker-width="{$width}"
                    data-ca-object-picker-unremovable-item-ids="{$unremovable_item_ids|array_values|to_json}"
                    data-ca-object-picker-allow-clear="{$allow_clear}"
                    data-ca-object-picker-template-result-selector="#product_feature_picker_result_template_{$picker_id}"
                    data-ca-object-picker-template-selection-selector="#product_feature_picker_selection_template_{$picker_id}"
                    data-ca-object-picker-template-selection-load-selector="#product_feature_picker_selection_load_template_{$picker_id}"
                    data-ca-object-picker-allow-multiple-created-objects="{$multiple}"
                    {if $allow_add}
                        data-ca-object-picker-enable-create-object="true"
                        data-ca-object-picker-template-result-new-selector="#product_feature_picker_result_new_selector_template_{$picker_id}"
                        data-ca-object-picker-template-selection-new-selector="#product_feature_picker_selection_new_selector_template_{$picker_id}"
                        data-ca-object-picker-created-object-holder-selector="{$new_value_holder_selector}"
                    {/if}
                    {if $dropdown_parent_selector}
                        data-ca-object-picker-dropdown-parent-selector="{$dropdown_parent_selector}"
                    {/if}
                    {if $enable_permanent_placeholder}
                        data-ca-object-picker-enable-permanent-placeholder="true"
                    {/if}
                    {if $predefined_variant_items}
                        data-ca-object-picker-predefined-variants="{$predefined_variant_items|array_reverse|to_json}"
                    {/if}
                    {if $lazy_load}
                        data-ca-object-picker-lazy-load="true"
                    {/if}
            >
                <option value="">-{__("none")}-</option>
                {foreach $item_ids as $item_id}
                    {if $template_type == "color" || $template_type == "image" || $template_type == "text"}
                        <option value="{$item_id.variant_id}" 
                            {if $item_id.selected} selected="selected"{/if}
                            data-data="{["id" => $item_id.variant_id, "loaded" => "true", "data" => ["name" => $item_id.variant, "color" => $item_id.color]]|to_json}">
                        </option>
                    {else} 
                        <option value="{$item_id}" selected="selected"></option>
                    {/if}
                {/foreach}
            </select>
            {if $lazy_load}
                {include file="views/product_features/components/variants_picker/lazy_load.tpl"
                    item_ids=$item_ids
                    template_type=$template_type
                    empty_variant_text=$empty_variant_text
                    group_id=$group_id
                    input_id=$input_id
                }
            {/if}
        </div>
    </div>
</div>

<script type="text/template" id="product_feature_picker_result_template_{$picker_id}" data-no-defer="true" data-no-execute="§">
    <div class="object-picker__result-product-feature">
        {if $template_type == "color"}
            {include file="views/product_features/components/variants_picker/item_color.tpl" help=false enable_color=$enable_color}
        {elseif $template_type == "image"}
            {include file="views/product_features/components/variants_picker/item_image.tpl" enable_image=$enable_images}
        {else}
            {include file="views/product_features/components/variants_picker/item.tpl"}
        {/if}           
    </div>
</script>

<script type="text/template" id="product_feature_picker_selection_template_{$picker_id}" data-no-defer="true" data-no-execute="§">
    <div class="object-picker__selection-product-feature">
        {if $template_type == "color"}
            {include file="views/product_features/components/variants_picker/item_color.tpl" help=false enable_color=$enable_color}
        {elseif $template_type == "image"}
            {include file="views/product_features/components/variants_picker/item_image.tpl" enable_image=false}
        {else}
            {include file="views/product_features/components/variants_picker/item.tpl"}
        {/if}
    </div>
</script>

<script type="text/template" id="product_feature_picker_result_new_selector_template_{$picker_id}" data-no-defer="true" data-no-execute="§">
    <div class="object-picker__result-product-feature object-picker__result-product-feature--new">
        {if $template_type == "color"}
            {include file="views/product_features/components/variants_picker/item_color.tpl" title_pre=__("add") help=true enable_color=$enable_color}
        {elseif $template_type == "image"}
            {include file="views/product_features/components/variants_picker/item_image.tpl" enable_image=false title_pre=__("add")}
        {else}
            {include file="views/product_features/components/variants_picker/item.tpl" title_pre=__("add")}
        {/if}
    </div>
</script>

<script type="text/template" id="product_feature_picker_selection_new_selector_template_{$picker_id}" data-no-defer="true" data-no-execute="§">
    <div class="object-picker__selection-product-feature object-picker__selection-product-feature--new">
        {if $template_type == "color"}
            {include file="views/product_features/components/variants_picker/item_color.tpl" help=false enable_color=$enable_color}
        {elseif $template_type == "image"}
            {include file="views/product_features/components/variants_picker/item_image.tpl" enable_image=false}
        {else}
            {include file="views/product_features/components/variants_picker/item.tpl"}
        {/if}
    </div>
</script>

<script type="text/template" id="product_feature_picker_selection_load_template_{$picker_id}" data-no-defer="true" data-no-execute="§">
    <div class="object-picker__skeleton-product-feature">...</div>
</script>