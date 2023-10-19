{$input_name = $input_name|default:"storefront_data[is_accessible_for_authorized_customers_only]"}

<div class="control-group">
    <label for="is_accessible_for_authorized_customers_only_{$id}" class="control-label">
        {__("access_for_authorized_customers_only")}
    </label>
    <div class="controls">
        <input type="hidden" name="{$input_name}" value="{"YesNo::NO"|enum}" />

        {include file="common/switcher.tpl"
            checked=$is_accessible_for_authorized_customers_only
            input_name="{$input_name}"
            input_value="YesNo::YES"|enum
            input_id="is_accessible_for_authorized_customers_only_{$id}"
        }
    </div>
</div>