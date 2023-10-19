{if $feature.feature_style == "ProductFeatureStyles::COLOR"|enum || $feature.filter_style == "ProductFilterStyles::COLOR"|enum}
    {$template_type = "color"}
    {$enable_images = false}
{elseif $feature.feature_style == "ProductFeatureStyles::BRAND"|enum}
    {$template_type = "image"}
    {$enable_images = true}
{else}
    {$template_type = "text"}
    {$enable_images = false}
{/if}

{if $feature.prefix}<span>{$feature.prefix}</span>{/if}
{if $feature.feature_type == "ProductFeatures::TEXT_SELECTBOX"|enum
    || $feature.feature_type == "ProductFeatures::NUMBER_SELECTBOX"|enum
    || $feature.feature_type == "ProductFeatures::EXTENDED"|enum}
    {assign var="suffix" value=$data_name|md5}

    {if $over}
        {assign var="input_id" value="field_`$field`__`$feature.feature_id`_"}
    {else}
        {assign var="input_id" value="feature_`$feature.feature_id`_`$suffix`"}
    {/if}
    <input type="hidden" name="{$data_name}[product_features][{$feature.feature_id}]" id="{$input_id}"
           value="{$selected|default:$feature.variant_id}"{if $over} disabled="disabled"{/if}/>
        {include file="views/product_features/components/variants_picker/picker.tpl"
            feature_id=$feature.feature_id
            input_name="{$data_name}[product_features][{$feature.feature_id}]"
            input_id=$input_id
            item_ids=$feature.variants|default:[]
            multiple=false
            template_type=$template_type
            allow_clear=true
            enable_image=$enable_images
            meta=($over) ? "elm-disabled" : ""
            disabled=$over
            lazy_load=true
            group_id="`$data_name`[product_features]"
        }
{elseif $feature.feature_type == "ProductFeatures::MULTIPLE_CHECKBOX"|enum}
    <input type="hidden" name="{$data_name}[product_features][{$feature.feature_id}]" value=""
           {if $over}id="field_{$field}__{$feature.feature_id}_" disabled="disabled"{/if} />
    {include file="views/product_features/components/variants_picker/picker.tpl"
        input_id=($over) ? "field_{$field}__{$feature.feature_id}_" : ""
        feature_id=$feature.feature_id
        input_name="{$data_name}[product_features][{$feature.feature_id}][]"
        item_ids=$feature.variants|default:[]
        multiple=true
        template_type=$template_type
        allow_clear=false
        enable_image=$enable_images
        meta=($over) ? "elm-disabled" : ""
        disabled=$over
        lazy_load=true
        group_id="`$data_name`[product_features]"
    }
{elseif $feature.feature_type == "ProductFeatures::SINGLE_CHECKBOX"|enum}
    <input type="hidden" name="{$data_name}[product_features][{$feature.feature_id}]" value="N" {if $over}disabled="disabled" id="field_{$field}__{$feature.feature_id}_copy"{/if} />
    <input type="checkbox" name="{$data_name}[product_features][{$feature.feature_id}]" value="Y" {if $over}id="field_{$field}__{$feature.feature_id}_" disabled="disabled" class="elm-disabled"{/if} {if $feature.value == "Y"}checked="checked"{/if} />
{elseif $feature.feature_type == "ProductFeatures::DATE"|enum}
    {if $over}
        {assign var="date_id" value="field_`$field`__`$feature.feature_id`_"}
        {assign var="date_extra" value=" disabled=\"disabled\""}
        {assign var="d_meta" value="input-text-disabled"}
    {else}
        {assign var="date_id" value="date_`$pid``$feature.feature_id`"}
        {assign var="date_extra" value=""}
        {assign var="d_meta" value=""}
    {/if}
    {$feature.value}{include file="common/calendar.tpl" date_id=$date_id date_name="`$data_name`[product_features][`$feature.feature_id`]" date_val=$feature.value_int start_year=$settings.Company.company_start_year extra=$date_extra date_meta=$d_meta}
{else}
    <input type="text" name="{$data_name}[product_features][{$feature.feature_id}]" value="{if $feature.feature_type == "ProductFeatures::NUMBER_FIELD"|enum}{if $feature.value_int != ""}{$feature.value_int|floatval}{/if}{else}{$feature.value}{/if}" {if $over} id="field_{$field}__{$feature.feature_id}_" disabled="disabled"{/if} class="input-text {if $over}input-text-disabled{/if} {if $feature.feature_type == "ProductFeatures::NUMBER_FIELD"|enum}cm-value-decimal{/if}" />
{/if}
{if $feature.suffix}<span>{$feature.suffix}</span>{/if}
<input type="hidden" name="{$data_name}[active_features][]" value="{$feature.feature_id}" />