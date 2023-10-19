<div id="content_additional_settings">
{hook name="shippings:additional_settings"}
{include file="common/subheader.tpl" title=__("shipping.pricing") target="#shipping_pricing"}
<fieldset id="shipping_pricing" class="collapse-visible collapse in">

    <div class="control-group">
        <label class="control-label">{__("taxes")}:</label>
        <div class="controls">
            {include file="common/adaptive_object_selection.tpl"
                input_name="shipping_data[tax_ids]"
                input_id="elm_shippings_taxes"
                item_ids=$shipping.tax_ids
                items=$taxes
                id_field="tax_id"
                name_field="tax"
                type="taxes"
                class_prefix="taxes"
                close_on_select="false"
                list_mode=false
            }
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="free_shipping">{__("use_for_free_shipping")}:</label>
        <div class="controls">
            <input type="hidden" name="shipping_data[free_shipping]" value={"YesNo::NO"|enum} />
            <input type="checkbox" name="shipping_data[free_shipping]" id="free_shipping" {if $shipping.free_shipping == "YesNo::YES"|enum}checked="checked"{/if} value={"YesNo::YES"|enum} />
            <p class="muted description">{__("tt_views_shippings_update_use_for_free_shipping")}</p>
        </div>
    </div>

</fieldset>

{include file="common/subheader.tpl" title=__("customer_information") target="#customer_information"}
<fieldset id="customer_information" class="collapse-visible collapse in">

    <div class="control-group">
        <label class="control-label" for="elm_is_address_required"
        >{__("is_address_required")}:</label>
        <div class="controls">
            <input type="hidden"
                   name="shipping_data[is_address_required]"
                   value={"YesNo::NO"|enum}
            />
            <input type="checkbox"
                   name="shipping_data[is_address_required]"
                   id="is_address_required"
                   {if $shipping.is_address_required|default:{"YesNo::YES"|enum} === "YesNo::YES"|enum}checked="checked"{/if}
                   value={"YesNo::YES"|enum}
            />
            <p class="muted description">{__("tt_views_shippings_update_is_address_required")}</p>
        </div>
    </div>

</fieldset>
{/hook}

<!--content_additional_settings--></div>