{$show_price = $show_price|default:true}
{if $show_price}
    <div class="addons-addon-price">
        {if $a.addon_marketplace_list_price && $a.addon_marketplace_price < $a.addon_marketplace_list_price}
            <small class="addons-addon-price__list-price muted" title="{__("cscart_marketplace.list_price")}">
                <s>{$a.currency}{$a.addon_marketplace_list_price}</s>
            </small>
        {/if}
        {if $a.addon_marketplace_price|floatval}
            <span class="addons-addon-price__price">
                {$a.currency}{$a.addon_marketplace_price}
            </span>
        {else}
            <span class="addons-addon-price__price addons-addon-price__price--free">
                {__("cscart_marketplace.price_free")}
            </span>
        {/if}
    </div>
{/if}
