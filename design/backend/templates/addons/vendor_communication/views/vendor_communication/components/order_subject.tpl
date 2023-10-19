{*
    $order array Order data
*}

<a href="{"orders.details?order_id=`$order.order_id`"|fn_url}">
    <small>
        {__("order")} #{$order.order_id}, {include file="common/price.tpl" value=$order.total}
    </small>
</a>
