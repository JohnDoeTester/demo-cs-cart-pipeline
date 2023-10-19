{$readonly=$readonly|default:false}
{if $option.force_open}
<script>
Tygh.$(document).ready(function() {
    Tygh.$('#additional_{$set_id}').show();
});
</script>
{/if}

{if !$option.remove_indent}
<div class="control-group">
{if !$option.hide_label}
    <label for="{$html_id}" class="control-label {if $option.required} cm-required{/if}">{if $option.option_name}{__($option.option_name)}{else}{__($name)}{/if}</label>
{/if}
<div class="controls {if $editable}cm-no-hide-input{/if}">
{/if}

{** Checkbox **}
{if $option.type == "checkbox"}
    <input type="hidden" name="{$html_name}" value="N" />
    <input type="checkbox" name="{$html_name}" value="Y" id="{$html_id}" {if $value && $value == "Y" || !$value && $option.default_value == "Y"}checked="checked"{/if} {if $readonly}readonly="readonly"{/if} />
{** Selectbox **}
{elseif $option.type == "selectbox"}
    {assign var="value" value=$value|default:$option.default_value}

    <select id="{$html_id}" name="{$html_name}" {if $option.values_settings}class="cm-reload-form"{/if} {if $readonly}readonly="readonly"{/if}>
    {foreach from=$option.values key="k" item="v"}
        <option value="{$k}" {if $value == $k}selected="selected"{/if}>{if $option.no_lang}{$v}{else}{__($v)}{/if}</option>
    {/foreach}
    </select>

    {assign var="values_settings" value=$option.values_settings.$value}

    {if $values_settings}
        <div class="setting-select-values">
        {foreach from=$values_settings.settings item=setting_data key=setting_name}
            {include file="views/block_manager/components/setting_element.tpl" option=$setting_data name=$setting_name block=$block html_id="block_`$html_id`_properties_`$name`_`$setting_name`" html_name="block_data[properties][`$name`][`$value`][`$setting_name`]" editable=$editable value=$block.properties.$name.$value.$setting_name readonly=$readonly}
        {/foreach}
        </div>
    {/if}
{elseif $option.type == "input" || $option.type == "input_long"}
    <input type="text" id="{$html_id}" class="input-medium" name="{$html_name}" value="{if $value || $value === "0"}{$value}{else}{$option.default_value}{/if}" {if $readonly}readonly="readonly"{/if}/>

{elseif $option.type == "multiple_checkboxes"}

    {html_checkboxes name=$html_name options=$option.values columns=4 selected=$value readonly=$readonly}
{elseif $option.type == "text" || $option.type == "simple_text"}
    <textarea id="{$html_id}" name="{$html_name}" cols="55" rows="8" class="{if $option.type == "text"}cm-wysiwyg{/if} input-textarea-long" {if $readonly}readonly="readonly"{/if}>{$value}</textarea>
    {if $option.type == "text"}
        {* Process textarea with HTML editor *}
        <!--processForm-->
    {/if}
{elseif $option.type == "picker"}
    {foreach from=$option.picker_params key="picker_param_key" item="picker_param_value"}
        {assign var=$picker_param_key value=$picker_param_value}
    {/foreach}

    {include_ext file=$option.picker checkbox_name="block_items"
        data_id="objects_`$item.chain_id`_"
        input_name="`$html_name`"
        item_ids=$value
        params_array=$option.picker_params
    }
{elseif $option.type == "enum"}
    {if $option.fillings}
        <div class="control-group {if $editable}cm-no-hide-input{/if}">
            <label class="control-label" for="block_{$html_id}_filling">{__("filling")}</label>
            <div class="controls">
            <select id="block_{$html_id}_filling" name="block_data[content][{$name}][filling]" class="cm-reload-form" {if $readonly}readonly="readonly"{/if}>
                {foreach from=$option.fillings item=v key=k}
                    <option value="{$k}" {if $block.content.$name.filling == $k}selected="selected"{/if}>{__($k)}</option>
                {/foreach}
            </select>
            {assign var="filling" value=$block.content.$name.filling}
            </div>
        </div>
        {if $filling == 'manually'}
            <div class="control-group {if $editable}cm-no-hide-input{/if}">
                {include_ext
                    file=$option.fillings.manually.picker
                    checkbox_name="block_items"
                    data_id="objects_`$item.chain_id`_"
                    input_name="`$html_name`[item_ids]"
                    item_ids=$block.content.$name.item_ids
                    params_array=$option.fillings.manually.picker_params
                    placement="right"
                }
            </div>
        {/if}
        {if $option.fillings.$filling.settings|is_array}
            {foreach from=$option.fillings.$filling.settings item=setting_data key=setting_name}
                {include file="views/block_manager/components/setting_element.tpl" option=$setting_data name=$setting_name block=$block html_id="block_`$html_id`_properties_`$name`_`$setting_name`" html_name="block_data[content][`$name`][`$setting_name`]" editable=$editable value=$block.content.$name.$setting_name readonly=$readonly}
            {/foreach}
        {/if}
    {/if}
{elseif $option.type == "template"}
    {include file=$option.template value=$value}
{/if}

{if !$option.remove_indent}
{if $option.tooltip}
    <p class="muted description">{$option.tooltip nofilter}</p>
{/if}
</div></div>
{/if}
