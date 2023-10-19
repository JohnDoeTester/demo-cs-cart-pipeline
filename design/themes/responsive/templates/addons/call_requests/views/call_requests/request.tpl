{if $product}
    {$id = "buy_now_with_one_click_{$obj_prefix}{$product.product_id}"}
{else}
    {$id = "call_request_{$obj_prefix}{$obj_id}"}
{/if}

<div class="hidden" title="{__("call_requests.buy_now_with_one_click")}" id="content_{$id}">
    {include file="addons/call_requests/views/call_requests/components/call_requests_content.tpl" product=$product id=$id}
<!--content_{$id}--></div>
