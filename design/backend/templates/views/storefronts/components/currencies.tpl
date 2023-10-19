{*
array   $id                             Storefront ID
array   $all_currency_ids               All currencies
array   $all_currencies                 All currencies
boolean $is_localization_picker_allowed Is picker allowed
*}

<div class="control-group">
    <label for="currencies_{$id}"
           class="control-label"
    >
        {__("currencies")}
    </label>
    <div class="controls" id="currencies_{$id}">
        {if $is_localization_picker_allowed}
            {include file="common/adaptive_object_selection.tpl"
                input_name="storefront_data[currencies]"
                input_id="storefront_currrency"
                item_ids=$all_currency_ids
                items=$all_currencies
                id_field="currency_id"
                name_field="description"
                storefront_id=$id
                type="currencies"
                load_items_url="currencies.selector?storefront_id=`$id`"
                class_prefix="localization"
                close_on_select="false"
            }
        {else}
            {foreach $all_currencies as $currency}
                <p>{$currency.description}</p>
            {/foreach}
        {/if}
    </div>
</div>
