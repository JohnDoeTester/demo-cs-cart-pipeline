<input type="hidden" name="shipping_id" value="{$id}" />
<input type="hidden" name="shipping_data[rates][]" value="" />

<script>
    Tygh.tr({
        'C_condition_name': '{__("shipping_price_condition")|escape:"javascript"}',
        'W_condition_name': '{__("shipping_weight_condition")|escape:"javascript"}',
        'I_condition_name': '{__("shipping_items_condition")|escape:"javascript"}',
        'surcharge_discount_name': '{__("shipping_surcharge_discount")|escape:"javascript"}',
        'per': '{__("shipping_per")|escape:"javascript"} ',
        'C_unit': '{$currencies.$primary_currency.symbol|strip_tags|escape:"javascript"}',
        'W_unit': '{$settings.General.weight_symbol|escape:"javascript"}',
        'I_unit': '{__("shipping_item")|escape:"javascript"}',
        'text_are_you_sure_to_proceed': '{__("text_are_you_sure_to_proceed")|escape:"javascript"}',
        'from': '{__("shipping_from_value")|escape:"javascript"}',
        'to': '{__("shipping_to_value")|escape:"javascript"}',
        'shipping_and_up': '{__("shipping_and_up")|escape:"javascript"}',
        'currencies_after': '{$currencies.$primary_currency.after|escape:"javascript"}',
        'rate_range_overlap_error_message': '{__("shipping_rate_range_overlap_error_message")|escape:"javascript"}',
        'rate_range_limit_error_message': '{__("shipping_rate_range_limit_error_message")|escape:"javascript"}'
    });
    Tygh.currencies_after = {if $currencies.$primary_currency.after == 'Y'} true {else} false {/if};
</script>
{script src="js/tygh/backend/shipping_rates.js"}

<div class="dashboard-shipping" id="dashboard_shipping_rate">

    {include file="views/shippings/components/picker/rates/picker.tpl"
        multiple = true
        view_mode = "external"
        item_ids = $ids
        shipping_id = $id
    }

    <template id="template_table_row">
        {include file="views/shippings/components/condition_row.tpl"}
    </template>
<!--dashboard_shipping_rate--></div>
