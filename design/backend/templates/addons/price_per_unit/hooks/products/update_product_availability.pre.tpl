{component name="configurable_page.section" entity="products" tab="detailed" section="price_per_unit"}
    <hr>
    {include file="common/subheader.tpl" title=__("price_per_unit") target="#acc_price_per_unit"}

    <div id="acc_price_per_unit" class="collapse in">
        {hook name="products:update_product_unit_name"}
        {component name="configurable_page.field" entity="products" tab="detailed" section="price_per_unit" field="unit_name"}
            <div class="control-group">
                <label class="control-label" for="elm_product_unit_name">{__("unit_name")}:</label>
                <div class="controls">
                    <div class="input-group {$input_append}">
                        <input type="text"
                            name="product_data[unit_name]"
                            id="elm_product_unit_name"
                            value="{$product_data.unit_name|default:''}"
                            class="input-long"
                        />
                        {include file="components/append_language.tpl"
                            hide_inputs=$hide_inputs
                        }
                    </div>
                    <p class="muted description">{__("unit_name_field_description")}</p>
                </div>
            </div>
        {/component}
        {/hook}

        {hook name="products:update_product_units_in_product"}
        {component name="configurable_page.field" entity="products" tab="detailed" section="price_per_unit" field="units_in_product"}
            <div class="control-group">
                <label class="control-label" for="elm_product_units_in_product">{__("units_in_product")}:</label>
                <div class="controls">
                    <input type="text" name="product_data[units_in_product]" id="elm_product_units_in_product" size="55" value="{$product_data.units_in_product|default:''}" class="input-long" />
                    <p class="muted description">{__("units_in_product_field_description")}</p>
                </div>
            </div>
        {/component}
        {/hook}

        {hook name="products:update_product_show_price_per_x_units"}
        {component name="configurable_page.field" entity="products" tab="detailed" section="price_per_unit" field="show_price_per_x_units"}
            <div class="control-group">
                <label class="control-label" for="elm_product_show_price_per_x_units">{__("show_price_per_x_units")}:</label>
                <div class="controls">
                    <input type="text" name="product_data[show_price_per_x_units]" id="elm_product_show_price_per_x_units" size="55" value="{$product_data.show_price_per_x_units|default:''}" class="input-long" />
                    <p class="muted description">{__("show_price_per_x_units_field_description")}</p>
                </div>
            </div>
        {/component}
        {/hook}
    </div>
{/component} {* detailed :: price_per_unit *}
