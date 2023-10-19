{if $key == "paypal_commerce_platform.withdrawal"}
    {include file="common/price.tpl" value=$item}
{else}
    {$item}
{/if}