<div class="control-group">
    <label for="elm_datafeed_exclude_disapproved_products" class="control-label">{__("vendor_data_premoderation.exclude_disapproved_products")}:</label>
    <div class="controls">
        <input type="hidden" name="datafeed_data[params][exclude_disapproved_products]" value="N" />
        <input type="checkbox" name="datafeed_data[params][exclude_disapproved_products]" id="elm_datafeed_exclude_disapproved_products" value="Y" {if $datafeed_data.params.exclude_disapproved_products == "Y"}checked="checked"{/if} />
    </div>
</div>