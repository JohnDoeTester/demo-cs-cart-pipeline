{$data_id = $data_id|default:"filters_list"}
{$rnd = rand()}
{$data_id = "`$data_id`_`$rnd`"}
{$view_mode = $view_mode|default:"mixed"}
{$start_pos = $start_pos|default:0}

{script src="js/tygh/picker.js"}

{if ($item_ids) && !$item_ids|is_array}
    {$item_ids = ","|explode:$item_ids}
{/if}

<div class="clearfix">
    {if $view_mode != "list"}
        {capture name="add_buttons"}
            {if $multiple == true}
                {$display = "checkbox"}
            {else}
                {$display = "radio"}
            {/if}

            {if !$extra_url}
                {$extra_url = ""}
            {/if}

            {if $extra_var}
                {$extra_var = $extra_var|escape:url}
            {/if}
            <div class="pull-right">
                {if !$no_container}<div class="{if !$multiple}choose-icon{else}buttons-container{/if}">{/if}
                    {if $multiple}
                        {$lang_add_filters = __("add_filters")}
                        {include_ext file="common/icon.tpl" class="icon-plus" assign=but_text_icon}
                        {$_but_text = $but_text|default:"`$but_text_icon` `$lang_add_filters`"}
                        {$_but_role = "add"}
                    {else}
                        {include_ext file="common/icon.tpl" class="icon-plus" assign=_but_text}
                        {$_but_role = "icon"}
                    {/if}

                    {include file="buttons/button.tpl" but_id="opener_picker_`$data_id`" but_href="product_filters.picker?display=`$display`&picker_for=`$picker_for`&extra=`$extra_var`&checkbox_name=`$checkbox_name`&root=`$default_name`&except_id=`$except_id`&data_id=`$data_id``$extra_url`&company_id=`$company_id`"|fn_url but_text=$_but_text but_role=$_but_role but_target_id="content_`$data_id`" but_meta="cm-dialog-opener btn"}

                {if !$no_container}</div>{/if}
            </div>

            <div class="hidden" id="content_{$data_id}" title="{$but_text|default:__("add_filters")}">
            </div>
        {/capture}
        {if !$prepend}
            {$smarty.capture.add_buttons nofilter}
        {/if}
    {/if}
{if $view_mode != "button"}
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
        <div id="{$data_id}" class="{if $multiple && !$item_ids}hidden{elseif !$multiple}cm-display-radio pull-left{/if}">
    {/if}

    {if $multiple}
        <tr class="hidden">
            <td colspan="{if $positions}3{else}2{/if}" data-th="&nbsp;">
    {/if}
    <input id="f{$data_id}_ids" type="hidden" class="cm-picker-value" name="{$input_name}" value="{if $item_ids}{","|implode:$item_ids}{/if}" />
    {if $multiple}
            </td>
        </tr>
    {/if}

    {if $item_ids}
    {foreach name="items" from=$item_ids item="f_id"}
        {include file="pickers/filters/js.tpl" filter_id=$f_id holder=$data_id input_name=$input_name hide_link=$hide_link hide_delete_button=$hide_delete_button hide_input=true first_item=$smarty.foreach.items.first position_field=$positions position=$smarty.foreach.items.iteration+$start_pos extra_class="input-large"}
    {/foreach}
    {elseif !$multiple}
        <div class="input-append">
            <div class="pull-left">
                {include file="pickers/filters/js.tpl" filter_id="" holder=$data_id input_name=$input_name hide_link=$hide_link hide_delete_button=$hide_delete_button extra_class="input-large"}
            </div>
            {$smarty.capture.add_buttons nofilter}
        </div>
    {/if}

    {if $multiple}
        {include file="pickers/filters/js.tpl" filter_id="`$ldelim`filter_id`$rdelim`" holder=$data_id input_name=$input_name clone=true hide_link=$hide_link hide_delete_button=$hide_delete_button hide_input=true position_field=$positions position="0"}
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
    {else}
    </div>
    {/if}
{/if}
</div>
