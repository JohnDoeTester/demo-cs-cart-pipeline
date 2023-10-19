<div id="content_plan_shippings_{$id}">
    <div class="control-group">
        <label class="control-label">{__("order_fulfillment.fulfillment_by_marketplace")}:</label>
        <div class="controls">
            <input type="hidden"
                   name="plan_data[is_fulfillment_by_marketplace]"
                   value="{"YesNo::NO"|enum}"
            />
            <input type="checkbox" {if $plan.is_fulfillment_by_marketplace === "YesNo::YES"|enum}checked="checked"{/if} name="plan_data[is_fulfillment_by_marketplace]" value="{"YesNo::YES"|enum}" />
            <div class="muted description">
                <p>{__("order_fulfillment.fulfillment_tooltip") nofilter}</p>
            </div>
        </div>
    </div>
</div>