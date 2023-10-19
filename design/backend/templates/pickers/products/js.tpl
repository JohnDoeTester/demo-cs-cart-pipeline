{$product_data = $product_id|fn_get_product_data:$smarty.session.auth:$smarty.const.CART_LANGUAGE:"?:products.company_id,?:product_descriptions.product":false:false:false:false:false:false:true}
{if $runtime.company_id && $product_data.company_id != $runtime.company_id}
    {$product = $product_data.product|default:$product}
    {if $owner_company_id && $owner_company_id != $runtime.company_id}
        {$show_only_name = true}
    {/if}
{/if}

{if $type == "options"}
<tr {if !$clone}id="{$root_id}_{$delete_id}" {/if}class="cm-js-item{if $clone} cm-clone hidden{/if}">
{if $position_field}<td data-th="&nbsp;"><input type="text" name="{$input_name}[{$delete_id}]" value="{math equation="a*b" a=$position b=10}" size="3" class="input-micro" {if $clone}disabled="disabled"{/if} /></td>{/if}
<td name="product_picker_object_name" data-th="{__("name")}">
    {$product}{if $show_only_name}{include file="views/companies/components/company_name.tpl" object=$product_data}{/if}
    {if $options}
        <br>
        <small>{$options nofilter}</small>
    {/if}
    {if $options_array|is_array}
        {foreach $options_array as $option_id => $value}
            {if $value|is_array}
                <input type="hidden" name="{$input_name}[product_options][{$value.option_id}]" value="{$value.value}"{if $clone} disabled="disabled"{/if} />
            {else}
                <input type="hidden" name="{$input_name}[product_options][{$option_id}]" value="{$value}"{if $clone} disabled="disabled"{/if} />
            {/if}
        {/foreach}
    {/if}
    {if $aoc}
        <input type="hidden"
            name="{$input_name}[aoc]"
            value="{$aoc}"
            data-ca-pickers-product-js="aoc"
            {if $clone}
                disabled="disabled"
            {/if}
        />
    {/if}
    {if $product_id}
        <input type="hidden" name="{$input_name}[product_id]" value="{$product_id}"{if $clone} disabled="disabled"{/if} />
    {/if}
    {if $amount_input == "hidden"}
        <input type="hidden" name="{$input_name}[amount]" value="{$amount}"{if $clone} disabled="disabled"{/if} />
    {/if}
</td>
    {if $amount_input == "text"}
<td class="center" data-th="{__("quantity")}">
    {if $show_only_name}
        {$amount}
    {else}
        <input type="text" name="{$input_name}[amount]" value="{$amount}" size="3" class="input-micro cm-value-decimal product-picker__amount"{if $clone} disabled="disabled"{/if} />
    {/if}
</td>
    {/if}

    {hook name="product_picker:table_column_options"}
    {/hook}
<td class="nowrap">
    {if !$hide_delete_button && !$show_only_name}
        {capture name="tools_list"}
            <li>{btn type="list" text=__("delete") onclick="Tygh.$.cePicker('delete_js_item', '{$root_id}', '{$delete_id}', 'p'); return false;"}</li>
        {/capture}
        <div class="hidden-tools">
            {dropdown content=$smarty.capture.tools_list}
        </div>
    {else}&nbsp;{/if}
</td>
</tr>

{elseif $type == "product"}
    <tr {if !$clone}id="{$root_id}_{$delete_id}" {/if}class="cm-js-item{if $clone} cm-clone hidden{/if}">
        {if $position_field}<td data-th="{__("position_short")}"><input type="text" name="{$input_name}[{$delete_id}]" value="{math equation="a*b" a=$position b=10}" size="3" class="input-micro" {if $clone}disabled="disabled"{/if} /></td>{/if}
        <td data-th="{__("name")}">
            {hook name="product_picker:table_column_name"}
                {if !$show_only_name}
                    <a href="{"products.update?product_id=`$delete_id`"|fn_url}">{$product nofilter}</a>
                {else}
                    {$product nofilter}
                {/if}

                {$product_data_for_company_name = [
                    "company_name" => $company_name
                ]}

                {if !$clone}
                    {$product_data_for_company_name = $product_data}
                {/if}
                {include file="views/companies/components/company_name.tpl"
                    object=$product_data_for_company_name
                }
            {/hook}
        </td>
        <td class="mobile-hide" data-th="&nbsp;">&nbsp;</td>
        <td class="nowrap" data-th="{__("tools")}">{if !$hide_delete_button && !$show_only_name}
            {capture name="tools_list"}
                <li>{btn type="list" text=__("edit") href="products.update?product_id=`$delete_id`"}</li>
                <li>{btn type="list" text=__("remove") onclick="Tygh.$.cePicker('delete_js_item', '{$root_id}', '{$delete_id}', 'p'); return false;"}</li>
            {/capture}
            <div class="hidden-tools">
                {dropdown content=$smarty.capture.tools_list}
            </div>
        {/if}</td>
    </tr>

{elseif $type == "single"}
<span {if !$clone}id="{$holder}_{$product_id}" {/if}class="cm-js-item {if $clone} cm-clone hidden{/if}">
    {if !$first_item && $single_line}<span class="cm-comma{if $clone} hidden{/if}">,&nbsp;&nbsp;</span>{/if}

    <div class="input-append">
    <input class="cm-picker-value-description {$extra_class}" type="text" value="{$product}" {if $display_input_id}id="{$display_input_id}"{/if} size="10" name="product_name" readonly="readonly" {$extra} id="appendedInputButton">

    {include_ext file="common/icon.tpl" class="icon-plus" assign=_but_text}
    {$_but_role = "icon"}

    {include file="buttons/button.tpl" but_id="opener_picker_`$data_id`" but_href="products.picker?display=radio&company_ids=`$company_ids`&picker_for=`$picker_for`&extra=`$extra_var`&checkbox_name=`$checkbox_name`&except_id=`$except_id`&data_id=`$data_id``$extra_url`"|fn_url but_text=$_but_text but_role=$_but_role but_icon=$_but_icon but_target_id="content_`$data_id`" but_meta="`$but_meta` cm-dialog-opener add-on btn"}

    </div>
    </span>
{/if}
