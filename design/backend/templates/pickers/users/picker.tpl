{$rnd = rand()}
{$data_id=$picker_id|default:"{$data_id}_{$rnd}"}
{$view_mode=$view_mode|default:"mixed"}
{$show_but_text=$show_but_text|default:"true"}

{script src="js/tygh/picker.js"}

{if $item_ids && !$item_ids|is_array}
    {$item_ids=","|explode:$item_ids}
{/if}

{$display=$display|default:"checkbox"}

{if $view_mode != "list" && $view_mode != "single_button"}

    {include file="views/profiles/components/profiles_scripts.tpl"}

    {if $extra_var}
        {$extra_var=$extra_var|escape:url}
    {/if}

    {if $display == "checkbox"}
        {$_but_text=__("add_users")}
    {elseif $display == "radio"}
        {$_but_text=__("choose")}
    {/if}

    {if $but_text}
        {$_but_text=$but_text}
    {/if}

    {if $placement == 'right'}
        <div class="clearfix">
            <div class="pull-right">
    {/if}

    {if $show_but_text}
        {$but_text=$_but_text}
    {else}
        {$but_text=""}
    {/if}

    {include file="buttons/button.tpl" but_id="opener_picker_`$data_id`" but_href="profiles.picker?display=`$display`&extra=`$extra_var`&picker_for=`$picker_for`&data_id=`$data_id`&shared_force=`$shared_force``$extra_url`"|fn_url but_role="add" but_target_id="content_`$data_id`" but_meta="cm-dialog-opener `$but_meta`" but_icon=$but_icon}
    
    {if $placement == 'right'}
        </div></div>
    {/if}

{/if}

{if $view_mode == "single_button"}
    {if $user_info}
        {$user_name = "`$user_info.firstname` `$user_info.lastname`"}
        {$item_ids = $user_info.user_id}
    {/if}

    {$_but_text=__("choose_user")}
    <div class="mixed-controls">
    <div class="form-inline">
    <span id="{$data_id}" class="cm-js-item cm-display-radio">

    <div class="input-append">
    <input class="cm-picker-value-description {$extra_class}" type="text" value="{$user_name}" {if $display_input_id}id="{$display_input_id}"{/if} size="10" name="user_name" readonly="readonly" {$extra}>

    {include file="buttons/button.tpl" but_id="opener_picker_`$data_id`" but_href="profiles.picker?display=`$display`&picker_for=`$picker_for`&extra=`$extra_var`&checkbox_name=`$checkbox_name`&root=`$default_name`&except_id=`$except_id`&data_id=`$data_id``$extra_url`"|fn_url but_role="text" but_icon="icon-plus" but_target_id="content_`$data_id`" but_meta="`$but_meta` cm-dialog-opener add-on btn"}

    <input id="{if $input_id}{$input_id}{else}u{$data_id}_ids{/if}" type="hidden" class="cm-picker-value" name="{$input_name}" value="{if $item_ids|is_array}{","|implode:$item_ids}{else}{$item_ids}{/if}" {$extra} />

    </div>
    </span>
    </div>
    </div>
{elseif $view_mode != "button"}
    {if $display != "radio"}
        <input id="u{$data_id}_ids" type="hidden" name="{$input_name}" value="{if $item_ids}{","|implode:$item_ids}{/if}" />

        <div class="clearfix"></div>
        <div class="table-responsive-wrapper">
            <table width="100%" class="table table-middle table--relative table-responsive table-responsive-w-titles">
            <thead>
            <tr>
                <th width="100%">{__("person_name")}</th>
                <th>&nbsp;</th>
            </tr>
            </thead>
            <tbody id="{$data_id}"{if !$item_ids} class="hidden"{/if}>
            {include file="pickers/users/js.tpl" user_id="`$ldelim`user_id`$rdelim`" email="`$ldelim`email`$rdelim`" user_name="`$ldelim`user_name`$rdelim`" holder=$data_id clone=true}
            {if $item_ids}
            {foreach from=$item_ids item="user" name="items"}
                {$user_info=$user|fn_get_user_short_info}
                {include file="pickers/users/js.tpl" user_id=$user email=$user_info.email user_name="`$user_info.firstname` `$user_info.lastname`" holder=$data_id first_item=$smarty.foreach.items.first}
            {/foreach}
            {/if}
            </tbody>
            <tbody id="{$data_id}_no_item"{if $item_ids} class="hidden"{/if}>
            <tr>
                <td colspan="2" data-th="&nbsp;"><p class="no-items">{$no_item_text|default:__("no_items") nofilter}</p></td>
            </tr>
            </tbody>
            </table>
        </div>
    {/if}
{/if}

{if $view_mode != "list"}
    <div class="hidden" id="content_{$data_id}" title="{$_but_text}">
    </div>
{/if}