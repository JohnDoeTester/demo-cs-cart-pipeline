<div class="object-picker__shipping-rate-main {literal}${data.additional_class}{/literal}">
    {$destination_id = "${$ldelim}data.destination_id{$rdelim}"}

    <div class="shipping-rate" id="shipping_rate_{$destination_id}">
        <div class="shipping-rate__container">
            <div class="shipping-rate__main-content">
                <h4 class="shipping-rate__header">{literal}${data.destination}{/literal}</h4>
                <div class="shipping-rate__delivery-time">
                    <label>{__("shipping_time")}:</label>
                    <input type="text" 
                        class="input-small input-hidden"
                        name="shipping_data[rates][delivery_time][{$destination_id}]" 
                        value="${$ldelim}data.delivery_time{$rdelim}"
                        {literal}${data.is_disabled ? 'readonly': ''}{/literal}
                    />             
                </div>
                <div class="shipping-rate__base-rate">
                    <label>{__("shipping_rate")}:</label>
                    {$can_specify_base_rate = $shipping.rate_calculation == "M"}
                    {hook name="shippings:base_rate"}
                        {if $can_specify_base_rate}
                            <input type="text"
                                name="shipping_data[rates][{$destination_id}][base_rate]"
                                value="${$ldelim}data.price{$rdelim}"
                                class="cm-numeric input-small input-hidden"
                                data-a-sign="{$currencies.$primary_currency.symbol|strip_tags nofilter}"
                                data-a-dec="."
                                data-a-sep=","
                                {if $currencies.$primary_currency.after == "Y"}data-p-sign="s"{/if}
                                {literal}${data.is_disabled ? 'readonly' : ''}{/literal}
                            />
                        {else}
                            <input type="hidden" name="shipping_data[rates][{$destination_id}][base_rate]" {literal}${data.is_disabled ? 'readonly' : ''}{/literal}/>
                            {__("calculated_automatically")}
                        {/if}
                    {/hook}
                    <div class="shipping-rate__button-list" data-destination-id="{$destination_id}" data-types-conditions="C,W,I" data-disabled="{literal}${data.is_disabled ? 'readonly' : ''}{/literal}">
                        {if $allow_save}
                            <a id="sw_add_cond_{$destination_id}" class="cm-combinations shipping-rate__empty-conditions-tool shipping-rate__add-conditions">
                                {__("shipping_add_conditions")}
                                <span class="icon-caret-down hidden" data-ca-switch-id="add_cond_{$destination_id}"> </span>
                                <span class="icon-caret-right" data-ca-switch-id="add_cond_{$destination_id}"> </span>
                            </a>
                        {/if}
                        <a class="shipping-rate__not-empty-conditions-tool shipping-rate__show-conditions hidden">
                            <span class="shipping-rate__range"></span>
                            <span class="icon-caret-down"> </span>
                        </a>
                        <a class="shipping-rate__not-empty-conditions-tool shipping-rate__hide-conditions hidden">
                            <span>{__("shipping_hide_conditions")}</span>
                            <span class="icon-caret-down"> </span>
                        </a>
                    </div>
                </div>


                {if $allow_save}
                    <div class="shipping-rate__tools" data-th="{__("tools")}">
                        {capture name="tools_items"}
                            <li class="shipping-rate-tools__add-table shipping-rate-area__tools-item" data-type="C" data-destination-id="{$destination_id}">
                                <a>{__("shipping_add_price_condition")}</a>
                            </li>
                            <li class="shipping-rate-tools__remove-table hidden shipping-rate-area__tools-item" data-type="C" data-destination-id="{$destination_id}">
                                <a class="cm-confirm">{__("shipping_remove_price_condition")}</a>
                            </li>

                            <li class="shipping-rate-tools__add-table shipping-rate-area__tools-item" data-type="W" data-destination-id="{$destination_id}">
                                <a>{__("shipping_add_weight_condition")}</a>
                            </li>
                            <li class="shipping-rate-tools__remove-table hidden shipping-rate-area__tools-item" data-type="W" data-destination-id="{$destination_id}">
                                <a class="cm-confirm">{__("shipping_remove_weight_condition")}</a>
                            </li>

                            <li class="shipping-rate-tools__add-table shipping-rate-area__tools-item" data-type="I" data-destination-id="{$destination_id}">
                                <a>{__("shipping_add_items_condition")}</a>
                            </li>
                            <li class="shipping-rate-tools__remove-table hidden shipping-rate-area__tools-item" data-type="I" data-destination-id="{$destination_id}">
                                <a class="cm-confirm">{__("shipping_remove_items_condition")}</a>
                            </li>
                            <li class="divider shipping-rate-area__tools-item"></li>
                            <li>{btn type="list" href="{"destinations.update?destination_id=`$destination_id`"|fn_url}" text=__(shipping_edit_rate_area)}</li>
                            <li class="rate-tools__remove-shipping-rate" data-destination-id="{$destination_id}">
                                <a class="cm-object-picker-remove-object object-picker__shipping-rate-delete">{__("shipping_remove_rate_area")}</a>
                            </li>
                        {/capture}
                        <div class="hidden-tools" >
                            {dropdown content=$smarty.capture.tools_items}
                        </div>
                    </div>
                {/if}
            </div>

            <div class="shipping-rate__description">
                <label>{literal}${data.description}{/literal}</label>
            </div> 
        </div>
        
        <div id="tables_rate_condition_{$destination_id}" class="hidden tables-rate-condition">
        </div>               
    </div>
</div>