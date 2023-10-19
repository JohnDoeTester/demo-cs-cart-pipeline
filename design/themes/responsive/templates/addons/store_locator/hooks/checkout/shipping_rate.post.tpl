{if isset($shipping.pickup_rate_from) && !isset($shipping.free_shipping)}
    {capture name="formatted_min_rate"}
        {include file="common/price.tpl" value=$shipping.pickup_rate_from class="ty-nowrap"}
    {/capture}
    {if !isset($shipping.pickup_rate_to)}
        {$rate = __("store_locator.shipping_price_from", ['[price]' => $smarty.capture.formatted_min_rate]) scope=parent}
    {else}
        {capture name="formatted_max_rate"}
            {include file="common/price.tpl" value=$shipping.pickup_rate_to class="ty-nowrap"}
        {/capture}
        {$rate = __("store_locator.shipping_price_from_to", ['[from_price]' => $smarty.capture.formatted_min_rate, '[to_price]' => $smarty.capture.formatted_max_rate]) scope=parent}
    {/if}
{/if}
