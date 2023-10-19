{__("vendor_rating.rating_ranges_description")}

<hr>

<div class="control-group setting-wide">
    <label for="elm_bronze_rating_lower_limit" class="control-label cm-required">
        {__("vendor_rating.bronze_rating_lower_limit")}
    </label>
    <div class="controls">
        <input type="text"
               id="elm_bronze_rating_lower_limit"
               class="input input-small cm-numeric"
               name="addon_settings[bronze_rating_lower_limit]"
               data-a-sign="%"
               data-p-sign="s"
               data-m-dec="0"
               data-v-min="0"
               data-v-max="100"
               value="{$addons.vendor_rating.bronze_rating_lower_limit}"
        />
    </div>
</div>

<div class="control-group setting-wide">
    <label for="elm_silver_rating_lower_limit" class="control-label cm-required">
        {__("vendor_rating.silver_rating_lower_limit")}
    </label>
    <div class="controls">
        <input type="text"
               id="elm_silver_rating_lower_limit"
               class="input input-small cm-numeric"
               name="addon_settings[silver_rating_lower_limit]"
               data-a-sign="%"
               data-p-sign="s"
               data-m-dec="0"
               data-v-min="0"
               data-v-max="100"
               value="{$addons.vendor_rating.silver_rating_lower_limit}"
        />
    </div>
</div>

<div class="control-group setting-wide">
    <label for="elm_gold_rating_lower_limit" class="control-label cm-required">
        {__("vendor_rating.gold_rating_lower_limit")}
    </label>
    <div class="controls">
        <input type="text"
               id="elm_gold_rating_lower_limit"
               class="input input-small cm-numeric"
               name="addon_settings[gold_rating_lower_limit]"
               data-a-sign="%"
               data-p-sign="s"
               data-m-dec="0"
               data-v-min="0"
               data-v-max="100"
               value="{$addons.vendor_rating.gold_rating_lower_limit}"
        />
    </div>
</div>
