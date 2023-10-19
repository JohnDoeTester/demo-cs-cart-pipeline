<div class="hidden" id="content_warehouses_quantity">
    {if $warehouses}
    <div class="table-responsive-wrapper">
        <table class="table table-middle table--relative table-responsive" width="100%">
            <thead>
                <tr>
                    <th>{__("warehouses.name")}</th>
                    <th width="30%">{__("warehouses.city")}</th>
                    <th width="15%">{__("warehouses.store_type")}</th>
                    <th width="15%">{__("warehouses.quantity")}</th>
                </tr>
            </thead>
            <tbody class="cm-hide-inputs">
                {foreach $warehouses as $warehouse}
                    <tr class="cm-row-item cm-row-status-{$warehouse.status|strtolower}">
                        <td data-th="{__("warehouses.name")}">
                            <a href="{"store_locator.update?store_location_id=`$warehouse.store_location_id`"|fn_url}"
                               class="row-status"
                               target="_blank"
                            >{$warehouse.name}</a>
                            {if "ULTIMATE"|fn_allowed_for}
                                {include file="views/companies/components/company_name.tpl" object=$warehouse}
                            {/if}
                        </td>
                        <td data-th="{__("warehouses.city")}">
                            <span class="row-status">
                                {$warehouse.city}
                            </span>
                        </td>
                        <td data-th="{__("warehouses.store_type")}">
                            <span class="row-status">
                                {$store_types[$warehouse.store_type]}
                            </span>
                        </td>
                        <td data-th="{__("warehouses.quantity")}">
                            {$amount = ""}
                            {if $warehouses_amounts[$warehouse.warehouse_id]}
                                {$amount = $warehouses_amounts[$warehouse.warehouse_id]["amount"]}
                            {/if}
                            {$class = ""}
                            {if !$runtime.company_id || $runtime.company_id == $warehouse.company_id}
                                {$class = "cm-no-hide-input"}
                            {/if}
                            <input type="text" name="product_data[warehouses][{$warehouse.warehouse_id}]" value="{$amount}" class="input-small cm-value-decimal {$class}"/>
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
    {else}
        {if $runtime.company_id}
            {$store_locator_type_warehouse = "\Tygh\Addons\Warehouses\Manager::STORE_LOCATOR_TYPE_WAREHOUSE"|constant}
            {$store_locator_type_store = "\Tygh\Addons\Warehouses\Manager::STORE_LOCATOR_TYPE_STORE"|constant}

            <p class="no-items">{__("warehouses.quantity_tab.no_data", [
                    "[create_url]" => "store_locator.add?store_type={$store_locator_type_store}"|fn_url,
                    "[list_url]"   => "store_locator.manage?store_types[]={$store_locator_type_warehouse}&store_types[]={$store_locator_type_store}&switch_company_id=0"|fn_url
                ]) nofilter}
            </p>
        {else}
            <p class="no-items">{__("no_data")}</p>
        {/if}
    {/if}
</div>