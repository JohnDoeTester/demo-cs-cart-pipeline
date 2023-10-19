{$data_id = $data_id|default:"storefronts_list"}
{$rnd = rand()}
{$data_id = "`$data_id`_`$rnd`"}

{script src="js/tygh/picker.js"}

{if fn_allowed_for('MULTIVENDOR:ULTIMATE') && !empty($auth.storefront_id)}
    {$view_only = true}

    {if !isset($item_ids)}
        {$item_ids = $auth.storefront_id}
    {/if}
{/if}

{if $item_ids && !$item_ids|is_array}
    {$item_ids =","|explode:$item_ids}
{/if}

<div class="clearfix">
    {if $extra_var}
        {$extra_var = $extra_var|escape:url}
    {/if}

    {if !$multiple}
        <div class="choose-input">
    {/if}

    {capture name="add_buttons"}
        {if $multiple == true}
            {$display = "checkbox"}
        {else}
            {$display = "radio"}
        {/if}

        {if $extra_var}
            {$extra_var = $extra_var|escape:url}
        {/if}

        {if !$view_only}
            <div class="pull-right">
                {if !$no_container}
                    <div class="{if !$multiple}choose-icon input-append{else}buttons-container{/if}">
                {/if}
                {if $multiple}
                    {$_but_text = $but_text|default:__("add_storefronts")}
                    {$_but_role = "add"}
                    {$_but_icon = $but_icon|default:"icon-plus"}
                {else}
                    {include_ext file="common/icon.tpl" class="icon-plus" assign=_but_text}
                    {$_but_role = "icon"}
                {/if}

                {include file="buttons/button.tpl"
                    but_id="opener_picker_`$data_id`"
                    but_href="storefronts.picker?display={$display}&select_mode={if $multiple}multiple{else}single{/if}&extra={$extra_var}&checkbox_name={$checkbox_name}&data_id={$data_id}"|fn_url
                    but_text=$_but_text
                    but_role=$_but_role
                    but_icon=$_but_icon
                    but_target_id="content_{$data_id}"
                    but_meta="cm-dialog-opener add-on btn"
                    method="GET"
                }
                {if !$no_container}
                    </div>
                {/if}
            </div>
        {/if}

        <div class="hidden"
             id="content_{$data_id}"
             title="{$but_text|default:__("add_storefronts")}"
        ></div>
    {/capture}
    {if !$prepend}
        {$smarty.capture.add_buttons nofilter}
        {capture name="add_buttons"}{/capture}
    {/if}

    {if $multiple}
        <div class="clearfix"></div>
        <div class="table-responsive-wrapper">
            <table width="100%" class="table table-middle table--relative table-responsive table-responsive-w-titles">
            <thead>
            <tr>
                <th width="100%">
                    {__("name")}
                </th>
                <th>&nbsp;</th>
            </tr>
            </thead>
            <tbody id="{$data_id}"
                    {if !$item_ids}class="hidden"{/if}
            >
    {else}
        <div id="{$data_id}"
             class="{if $multiple && !$item_ids}hidden{elseif !$multiple}cm-display-radio pull-left{/if}"
        >
    {/if}

    {if $multiple}
        <tr class="hidden">
            <td colspan="2" data-th="&nbsp;">
    {/if}
    <input id="a{$data_id}_ids"
           type="hidden"
           class="cm-picker-value"
           name="{$input_name}"
           value="{if $item_ids}{","|implode:$item_ids}{/if}"
    />
    {if $multiple}
            </td>
        </tr>
    {/if}

    {if $item_ids}
        {foreach $item_ids as $p_id}
            <div class="input-append">
                <div class="pull-left">
                    {include file="pickers/storefronts/js.tpl"
                        storefront_id=$p_id
                        holder=$data_id
                        input_name=$input_name
                        hide_input=true
                    }
                </div>
                {$smarty.capture.add_buttons nofilter}
            </div>
        {/foreach}
    {elseif !$multiple}
        <div class="input-append">
            <div class="pull-left">
                {include file="pickers/storefronts/js.tpl"
                    storefront_id=""
                    holder=$data_id
                    input_name=$input_name
                }
            </div>
            {$smarty.capture.add_buttons nofilter}
        </div>
    {/if}

    {if $multiple}
        {include file="pickers/storefronts/js.tpl"
            storefront_id="{$ldelim}storefront_id{$rdelim}"
            holder=$data_id
            input_name=$input_name
            hide_input=true
            clone=true
        }
    {/if}

    {if $multiple}
            </tbody>
            <tbody id="{$data_id}_no_item"
                   {if $item_ids}class="hidden"{/if}
            >
            <tr>
                <td colspan="2" data-th="&nbsp;">
                    <p class="no-items">
                        {$no_item_text|default:__("no_items") nofilter}
                    </p>
                </td>
            </tr>
            </tbody>
            </table>
        </div><!--/table-wrapper-->
    {else}
        </div>
    {/if}

    {if !$multiple}
        </div><!--/choose-input-->
    {/if}
</div><!--/clearfix-->
