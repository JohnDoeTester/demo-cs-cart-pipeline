{hook name="select_popup:notify_checkboxes"}
    {$name_prefix=$name_prefix|default:"__notify"}

    {if $notify}
        <li class="divider"></li>
        <li><a><label for="{$prefix}_{$id}_notify">
            <input type="checkbox" name="{$name_prefix}_user" id="{$prefix}_{$id}_notify" value="{"YesNo::YES"|enum}" {if $notify_customer_status == true} checked="checked" {/if} onclick="Tygh.$('input[name={$name_prefix}_user]').prop('checked', this.checked);" />
            {$notify_text|default:__("notify_customer")}</label></a>
        </li>
    {/if}
    {if $notify_department}
        <li><a><label for="{$prefix}_{$id}_notify_department">
            <input type="checkbox" name="{$name_prefix}_department" id="{$prefix}_{$id}_notify_department" value="{"YesNo::YES"|enum}" {if $notify_department_status == true} checked="checked" {/if} onclick="Tygh.$('input[name={$name_prefix}_department]').prop('checked', this.checked);" />
            {__("notify_orders_department")}</label></a>
        </li>
    {/if}
    {if "MULTIVENDOR"|fn_allowed_for && $notify_vendor}
        <li><a><label for="{$prefix}_{$id}_notify_vendor">
            <input type="checkbox" name="{$name_prefix}_vendor" id="{$prefix}_{$id}_notify_vendor" value="{"YesNo::YES"|enum}" {if $notify_vendor_status == true} checked="checked" {/if} onclick="Tygh.$('input[name={$name_prefix}_vendor]').prop('checked', this.checked);" />
            {__("notify_vendor")}</label></a>
        </li>
    {/if}            
{/hook}
