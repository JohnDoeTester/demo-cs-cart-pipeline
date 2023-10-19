{if $plan.is_fulfillment_by_marketplace === "YesNo::YES"|enum}
    <p>
        {__("order_fulfillment.fulfillment_by_marketplace")}
    </p>
{/if}