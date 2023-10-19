
{if !$rnd}{$rnd = rand()}{/if}

{$data_id = $data_id|default:"categories_list"}
{$data_id = $picker_id|default:"`$data_id`_`$rnd`"}
{$view_mode = $view_mode|default:"mixed"}
{$start_pos = $start_pos|default:0}
{$default_name = $default_name|escape:"url"}

{if $but_text}
    {$_but_title = $but_text}
{elseif $but_title}
    {$_but_title = $but_title}
{else}
    {$_but_title = __("select_categories")}
{/if}

{script src="js/tygh/picker.js"}

{if $item_ids == ""}
    {$item_ids=null}
{/if}

{if $item_ids && !$item_ids|is_array}
    {$item_ids=","|explode:$item_ids}
{/if}

{if $view_mode != "blocks"}
    {capture name="add_buttons"}
        {if $view_mode != "list"}

            {if $multiple == true}
                {$display="checkbox"}
                {else}
                {$display="radio"}
            {/if}

            {if !$extra_url}
                {$extra_url="&get_tree=multi_level"}
            {/if}

            {if $disable_cancel}
                {$extra_url = "`$extra_url`&disable_cancel=true"}
            {/if}

            {if $extra_var}
                {$extra_var=$extra_var|escape:url}
            {/if}

            {if !$runtime.company_id || $runtime.controller != "companies"}

                {if $multiple}
                    {$_but_text=(isset($but_text)) ? ($but_text) : (__("add_categories"))}
                    {$_but_role="add"}
                    {$_but_icon=$but_icon|default:"icon-plus"}
                {else}
                    {$icon_class = ($but_role) ? "" : "icon-plus"}
                    {include_ext file="common/icon.tpl" class=$icon_class assign=_but_text}
                    {$_but_role=$but_role|default:"icon"}
                    {$_but_icon=$but_icon|default:""}
                {/if}

                {if $_but_role != "icon"}
                    {if $placement == 'right'}
                    <div class="clearfix">
                        <div class="pull-right">
                    {/if}

                        {include file="buttons/button.tpl" but_id="opener_picker_`$data_id`" but_href="categories.picker?display=`$display`&company_ids=`$company_ids`&picker_for=`$picker_for`&extra=`$extra_var`&checkbox_name=`$checkbox_name`&root=`$default_name`&except_id=`$except_id`&data_id=`$data_id`&is_tristate_checkbox=`$is_tristate_checkbox``$extra_url`"|fn_url but_text=$_but_text but_role=$_but_role but_icon=$_but_icon but_target_id="content_`$data_id`" but_meta="`$but_meta` btn cm-dialog-opener"}
                    {if $placement == 'right'}
                    </div>
                        </div>
                    {/if}
                {/if}
                <div class="hidden" id="content_{$data_id}" title="{$_but_title}"></div>
            {/if}

        {else}

            {$display="checkbox"}

            {if !$extra_url}
                {$extra_url="&get_tree=multi_level"}
            {/if}

            {if $extra_var}
                {$extra_var=$extra_var|escape:url}
            {/if}

            {if !$runtime.company_id || $runtime.controller != "companies"}
                {$_but_text=$but_text|default:__("add_categories")}
                {$_but_role="add"}
                {$_but_icon="icon-plus"}

            {if $disable_cancel}
                {$extra_url = "`$extra_url`&disable_cancel=true"}
            {/if}

            {include file="buttons/button.tpl"
                     but_id="opener_picker_`$data_id`"
                     but_href="categories.picker?display=`$display`&data_id=`$data_id`&company_ids=`$company_ids``$extra_url`"|fn_url
                     but_text=$_but_text
                     but_role=$_but_role
                     but_icon=$_but_icon
                     but_meta=$but_
                     but_target_id="content_`$data_id`"
                     but_meta="`$but_meta` cm-dialog-opener"
            }

            {/if}
            <div class="hidden" id="content_{$data_id}" title="{$_but_title}"></div>
        {/if}
    {/capture}

    {if !$prepend}
        {$smarty.capture.add_buttons nofilter}
        {capture name="add_buttons"}{/capture}
    {/if}

{/if}

{if !$extra_var && $view_mode != "button"}
    {if $multiple}
    <div class="clearfix"></div>
    <div id="categories_table_{$data_id}" class="table-responsive-wrapper">
        <table width="100%" class="table table-middle table--relative table-responsive">
        <thead>
        <tr>
            {if $positions}<th width="5%">{__("position_short")}</th>{/if}
            <th>{__("name")}</th>
            {hook name="category_picker:manage_header"}{/hook}
            <th>&nbsp;</th>
        </tr>
        </thead>
        <tbody id="{$data_id}"{if !$item_ids} class="hidden"{/if}>
    {else}
        <div id="{$data_id}" class="{if $multiple && !$item_ids}hidden{elseif !$multiple}{if $view_mode != "list"}cm-display-radio{/if}{/if} choose-category">
    {/if}
    {if $multiple}
        <tr class="hidden">
            <td colspan="{if $positions}3{else}2{/if}">
    {/if}
            <input id="{if $input_id}{$input_id}{else}c{$data_id}_ids{/if}" type="hidden" class="cm-picker-value" name="{$input_name}" value="{if $item_ids|is_array}{","|implode:$item_ids}{/if}" {$extra} />
    {if $multiple}
            </td>
        </tr>
    {/if}
        {if $multiple}
            {include file="pickers/categories/js.tpl" category_id="`$ldelim`category_id`$rdelim`" holder=$data_id hide_input=$hide_input input_name=$input_name radio_input_name=$radio_input_name clone=true hide_link=$hide_link hide_delete_button=$hide_delete_button position_field=$positions position="0"}
        {/if}
        {if $view_mode == "list"}
            {foreach from=$item_ids item="c_id" name="items"}
                {include file="pickers/categories/js.tpl" main_category=$main_category category_id=$c_id holder=$data_id hide_input=$hide_input input_name=$input_name clone=true hide_link=$hide_link first_item=$smarty.foreach.items.first view_mode="list"}
            {foreachelse}
                {include file="pickers/categories/js.tpl" category_id="" holder=$data_id hide_input=$hide_input input_name=$input_name clone=true hide_link=$hide_link view_mode="list"}
            {/foreach}
        {else}
            {foreach from=$item_ids item="c_id" name="items"}
                {if !$multiple}<div class="input-append choose-input">{/if}
                {include file="pickers/categories/js.tpl" category_id=$c_id holder=$data_id hide_input=$hide_input input_name=$input_name hide_link=$hide_link hide_delete_button=$hide_delete_button first_item=$smarty.foreach.items.first position_field=$positions position=$smarty.foreach.items.iteration+$start_pos}
                {if !$multiple}</div>{/if}<!-- /.choose-input -->
            {foreachelse}
                {if !$multiple}
                <div class="input-append choose-input">
                    {include file="pickers/categories/js.tpl" category_id="" holder=$data_id hide_input=$hide_input input_name=$input_name hide_link=$hide_link hide_delete_button=$hide_delete_button}
                    {$smarty.capture.add_buttons nofilter}
                </div>
                {/if}
            {/foreach}
        {/if}
    {if $multiple}
        </tbody>
        <tbody id="{$data_id}_no_item"{if $item_ids} class="hidden"{/if}>
        <tr>
            <td colspan="{if $positions}3{else}2{/if}"><p class="no-items">{$no_item_text|default:__("no_items") nofilter}</p></td>
        </tr>
        </tbody>
    </table>
    </div>
    {else}</div>{/if}
{/if}
