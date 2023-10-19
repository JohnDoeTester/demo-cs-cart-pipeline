{$data_id = $data_id|default:"companies_list"}
{if !$rnd}{$rnd = rand()}{/if}
{$data_id = "`$data_id`_`$rnd`"}
{$view_mode = $view_mode|default:"mixed"}
{$start_pos = $start_pos|default:0}
{$show_but_text = $show_but_text|default:true}
{$icon = $icon|default:"icon-plus"}

{script src="js/tygh/picker.js"}
{include file="views/profiles/components/profiles_scripts.tpl"}

{if $item_ids == ""}
    {$item_ids = null}
{/if}

{if $item_ids && $multiple && !$item_ids|is_array}
    {$item_ids = ","|explode:$item_ids}
{/if}

{if $show_add_button}
    {if $multiple}
        {$_but_text = $but_text|default:__("add_companies")}
        {$_but_role = "add"}
    {else}
        {$lang_choose = __("choose")}
        {include_ext file="common/icon.tpl" class="icon-" title=$lang_choose assign=_but_text}
        {$_but_role = "icon"}
    {/if}
    <div class="pull-right">
    {include file="buttons/button.tpl" but_id="opener_picker_`$data_id`" but_href="companies.picker?display=`$display`&picker_for=`$picker_for`&extra=`$extra_var`&checkbox_name=`$checkbox_name`&root=`$default_name`&except_id=`$except_id`&data_id=`$data_id``$extra_url`"|fn_url but_text=$_but_text but_role=$_but_role but_target_id="content_`$data_id`" but_meta="cm-dialog-opener btn" but_icon="icon-plus"}
    </div>
{/if}

{if $view_mode != "list"}

    {if $multiple == true}
        {$display = "checkbox"}
    {else}
        {$display = "radio"}
    {/if}

    {if !$extra_url}
        {$extra_url = "&get_tree=multi_level"}
    {/if}

    {if $extra_var}
        {$extra_var = $extra_var|escape:url}
    {/if}

    {if !$runtime.company_id || $runtime.controller != "companies"}
    {if !$no_container}<div class="{if !$multiple}choose-icon{else}button-container{/if}">{/if}
        {if $multiple}
            {$_but_text = $but_text|default:__("add_companies")}
            {$_but_role = "add"}
        {else}
            {$lang_choose = __("choose")}
            {include_ext file="common/icon.tpl" class="icon-" title=$lang_choose assign=_but_text}
            {$_but_role = "icon"}
        {/if}
    <div class="pull-right">
    {include file="buttons/button.tpl" but_id="opener_picker_`$data_id`" but_icon=$icon but_href="companies.picker?display=`$display`&picker_for=`$picker_for`&extra=`$extra_var`&checkbox_name=`$checkbox_name`&root=`$default_name`&except_id=`$except_id`&data_id=`$data_id``$extra_url`"|fn_url but_text=$_but_text but_role=$_but_role but_target_id="content_`$data_id`" but_meta="btn cm-dialog-opener"}
    </div>
    {if !$no_container}</div>{/if}
    {/if}
    <div class="hidden" id="content_{$data_id}" title="{$but_text|default:__("add_companies")}">
    </div>
{else}
    {$display = "checkbox"}

    {if !$extra_url}
        {$extra_url = "&get_tree=multi_level"}
    {/if}

    {if $extra_var}
        {$extra_var = $extra_var|escape:url}
    {/if}

    {if !$runtime.company_id || $runtime.controller != "companies"}
        {$_but_text = $but_text|default:__("add_companies")}
        {$_but_role = "add"}
    {/if}
    <div class="hidden" id="content_{$data_id}" title="{$but_text|default:__("add_companies")}">
    </div>
{/if}

{if !$extra_var && $view_mode != "button"}
    {if $multiple}
    <div class="clearfix"></div>
    <div class="table-responsive-wrapper">
        <table width="100%" class="table table-middle table--relative table-responsive table-responsive-w-titles">
        <thead>
        <tr>
            {if $positions}<th>{__("position_short")}</th>{/if}
            <th width="100%">{__("name")}</th>
            <th>&nbsp;</th>
        </tr>
        </thead>
        <tbody id="{$data_id}"{if !$item_ids} class="hidden"{/if}>
    {else}
        <div id="{$data_id}" class="{if $multiple && !$item_ids}hidden{elseif !$multiple}{if $view_mode != "list"}cm-display-radio{/if}{/if} choose-category">
    {/if}
    {if $multiple}
        <tr class="hidden">
            <td colspan="{if $positions}3{else}2{/if}" data-th="&nbsp;">
    {/if}
            <input id="{if $input_id}{$input_id}{else}m{$data_id}_ids{/if}" type="hidden" class="cm-picker-value" name="{$input_name}" value="{if $item_ids|is_array}{$_item_ids = $item_ids}{","|implode:$_item_ids}{else}{$item_ids}{/if}" {$extra} />
    {if $multiple}
            </td>
        </tr>
    {/if}
        {if $item_ids}
            {if $view_mode == "list"}
                {foreach from=$item_ids item="c_id" name="items"}
                    {include file="pickers/companies/js.tpl" company_id=$c_id holder=$data_id input_name=$input_name clone=false hide_link=$hide_link first_item=$smarty.foreach.items.first position_field=$positions position=$smarty.foreach.items.iteration+$start_pos}
                {foreachelse}
                    {include file="pickers/companies/js.tpl" company_id="" holder=$data_id input_name=$input_name clone=true hide_link=$hide_link}
                {/foreach}
            {else}
                {foreach from=$item_ids item="c_id" name="items"}
                    {include file="pickers/companies/js.tpl" company_id=$c_id holder=$data_id input_name=$input_name hide_link=$hide_link hide_delete_button=$hide_delete_button first_item=$smarty.foreach.items.first position_field=$positions position=$smarty.foreach.items.iteration+$start_pos}
                {foreachelse}
                    {if !$multiple}
                        {include file="pickers/companies/js.tpl" company_id="" holder=$data_id input_name=$input_name hide_link=$hide_link hide_delete_button=$hide_delete_button}
                    {/if}
                {/foreach}
            {/if}
        {/if}

        {if $multiple}
            {include file="pickers/companies/js.tpl" company_id="`$ldelim`company_id`$rdelim`" holder=$data_id input_name=$input_name radio_input_name=$radio_input_name clone=true hide_link=$hide_link hide_delete_button=$hide_delete_button position_field=$positions position="0"}
        {/if}
    {if $multiple}
        </tbody>
        <tbody id="{$data_id}_no_item"{if $item_ids} class="hidden"{/if}>
        <tr>
            <td colspan="{if $positions}3{else}2{/if}" data-th="&nbsp;"><p class="no-items">{$no_item_text|default:__("no_items") nofilter}</p></td>
        </tr>
        </tbody>
    </table>
    </div>
    {else}</div>{/if}
{/if}
