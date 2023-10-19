{*
bool $warn_about_delay
string $shipping_delay
*}
{if $warn_about_delay}
    {$uid = uniqid()}
    {capture name="warehouse_shipping_delay_{$uid}"}
        {if $shipping_delay}
            {__("warehouses.shipping_delay.description", [
                "[shipping_delay]" => $shipping_delay
            ])}
        {else}
            {__("warehouses.shipping_delay.description.default")}
        {/if}
    {/capture}

    <div class="ty-warehouses-shipping__delay">
        {$smarty.capture["warehouse_shipping_delay_{$uid}"] nofilter}
    </div>
{/if}
