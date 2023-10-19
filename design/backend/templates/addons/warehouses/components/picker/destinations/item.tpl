{if $view_mode === "external" && $type === "selection"}
    {$item_meta = "object-picker__selection-extended-item object-picker__selection-extended-item--table"}
{elseif $type === "selection"}
    {$item_meta = "object-picker__selection-item"}
{elseif $type === "result"}
    {$item_meta = "object-picker__result-item"}
{/if}

<div class="object-picker__destinations-main {$item_meta}" data-th="{__("warehouses.rate_area")}">
    <div class="object-picker__name">
        {if $view_mode === "external" && $type === "selection"}
            <a href="{literal}${data.url}{/literal}" class="object-picker__name-content object-picker__name-content--link">{literal}${data.destination}{/literal}</a>
        {else}
            <div class="object-picker__name-content">{literal}${data.destination}{/literal}</div>
        {/if}
    </div>
</div>
{if $type === "selection"}
    <div class="object-picker__shipping-delay {$item_meta}" data-th="{__("warehouses.shipping_delay")}">
        <input type="hidden"
               name="store_location_data[shipping_destinations][{literal}${data.destination_id}{/literal}][destination_id]"
               value="{literal}${data.destination_id}{/literal}"
        />
        <input type="hidden"
               name="store_location_data[shipping_destinations][{literal}${data.destination_id}{/literal}][position]"
               value="{literal}${data.position}{/literal}"
        />
        <input type="text"
               name="store_location_data[shipping_destinations][{literal}${data.destination_id}{/literal}][shipping_delay]"
               value="{literal}${data.shipping_delay}{/literal}"
               class="input-small"
               {literal}${data.is_disabled ? 'disabled': ''}{/literal}
        />
    </div>
    <div class="object-picker__warn-about-delay {$item_meta}" data-th="{__("warehouses.warn_about_delay")}">
        <input type="hidden"
               name="store_location_data[shipping_destinations][{literal}${data.destination_id}{/literal}][warn_about_delay]"
               value="0"
        />
        <input type="checkbox"
               name="store_location_data[shipping_destinations][{literal}${data.destination_id}{/literal}][warn_about_delay]"
               value="1"
               {literal}
                   ${data.warn_about_delay
                       ? `checked="checked"`
                       : ``
                   }
               {/literal}
               {literal}${data.is_disabled ? 'disabled': ''}{/literal}
        />
    </div>
{/if}
