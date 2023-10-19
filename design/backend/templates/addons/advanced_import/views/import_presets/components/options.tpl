{$tab = $tab|default:"general"}
{$section = $section|default:"general"}

{if $display|default:true}
    {foreach $options as $option_id => $option}
        {if !$option|is_array
            || $tab != $option.tab|default:"general"
            || $section != $option.section|default:"general"
            || $option.hidden
            || $option.export_only
        }
            {continue}
        {/if}
        <div class="control-group{if $option.control_group_meta} {$option.control_group_meta}{/if}">
            <label for="{$option_id}" class="control-label">
                {strip}
                    {__($option.title)}:
                {/strip}
            </label>
            <div class="controls">
                {if $option.type == "checkbox"}
                    <input type="hidden" name="{$field_name_prefix}[{$option_id}]" value="N"/>
                    <input id="{$option_id}"
                           type="checkbox"
                           name="{$field_name_prefix}[{$option_id}]"
                           value="Y"
                           {if $option.selected_value|default:$option.default_value == "Y"}checked="checked"{/if}
                    />
                {elseif $option.type == "input"}
                    {if $option.option_template}
                        {include file=$option.option_template option=$option field_name_prefix=$field_name_prefix option_id=$option_id}
                    {else}
                        <input id="{$option_id}"
                               class="input-large"
                               type="text"
                               name="{$field_name_prefix}[{$option_id}]"
                               value="{$option.selected_value|default:$option.default_value}"
                        />
                    {/if}
                {elseif $option.type == "select"}
                    <select name="{$field_name_prefix}[{$option_id}]" id="{$option_id}">
                        {foreach $option.variants as $variant_id => $variant}
                            <option value="{$variant_id}"
                                    {if $variant_id == $option.selected_value|default:$option.default_value}selected="selected"{/if}
                            >{__($variant)}</option>
                        {/foreach}
                    </select>
                {/if}

                {if $option.notes}
                    <p class="muted description">{$option.notes nofilter}</p>
                {/if}

                {if $option.description}
                    <p class="muted description">{__($option.description, $option.description_params|default:[])}</p>
                {/if}
            </div>
        </div>
    {/foreach}
{else}
    {foreach $options as $option_id => $option}
        <input type="hidden"
               name="{$field_name_prefix}[{$option_id}]"
               {if $option|is_array}
                   value="{$option.selected_value|default:$option.default_value}"
               {else}
                   value="{$option}"
               {/if}
        />
    {/foreach}
{/if}