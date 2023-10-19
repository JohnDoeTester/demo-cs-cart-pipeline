<div class="control-group setting-wide">
    <label for="elm_rating_above_price" class="control-label">
        {__("vendor_rating.rating_above_price")}
        <div class="muted description">{__("vendor_rating.rating_above_price_tooltip")}</div>
    </label>

    <div class="controls">
        <input type="checkbox"
            id = elm_rating_above_price
            name = "addon_settings[rating_above_price]"
            value="Y"
            {if $addons.vendor_rating.rating_above_price === "YesNo::YES"|enum}
                checked="checked"
            {/if}
        />
    </div>
</div>
