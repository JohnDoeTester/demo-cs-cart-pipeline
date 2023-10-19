<div class="hidden" id="content_warehouses">
    {if $stores}
    <div id="warehouses_list"
         class="items-container cm-sortable ui-sortable"
         data-ca-sortable-table="store_location_destination_links"
         data-ca-sortable-id-name="link_id"
    >
        <div class="table-responsive-wrapper">
            <table class="table table-responsive">
                <thead>
                    <tr>
                        <th width="1%">&nbsp;</th>
                        <th>{__("warehouses.store_warehouse")}</th>
                        <th width="25%">{__("store_locator.main_destination")}</th>
                        <th width="10%">{__("warehouses.store_type")}</th>
                        <th width="35%">
                            {__("warehouses.shipping_delay_notification")}
                            {include file="common/tooltip.tpl"
                                tooltip=__("warehouses.shipping_delay_notification.tooltip")
                            }
                        </th>
                    </tr>
                </thead>
                <tbody >
                    {foreach $stores as $store}
                        <tr class="cm-sortable-row cm-sortable-id-{$store.link_id} cm-row-item cm-row-status-{$store.status|strtolower}">
                            <td class="no-padding-td" width="1%">
                                <span class="handler cm-sortable-handle ui-sortable-handle"></span>
                                <input type="hidden"
                                       name="destination_data[destination_links][{$store.store_location_id}][store_location_id]"
                                       value="{$store.store_location_id}"
                                />
                            </td>
                            <td data-th="{__("warehouses.store_warehouse")}">
                                <a href="{fn_url("store_locator.update?store_location_id={$store.store_location_id}")}"
                                   class="row-status"
                                >{$store.name}</a>
                                {include file="views/companies/components/company_name.tpl" object=$store}
                            </td>
                            <td data-th="{__("store_locator.main_destination")}">
                                {if $store.main_destination_id == $destination.destination_id}
                                    <span class="row-status">
                                        {$store.main_destination}
                                    </span>
                                {else}
                                    <a href="{fn_url("destinations.update?destination_id={$store.main_destination_id}")}"
                                       class="row-status"
                                    >{$store.main_destination}</a>
                                {/if}
                            </td>
                            <td data-th="{__("warehouses.store_type")}">
                                <span class="row-status">
                                    {$store_types[$store.store_type]}
                                </span>
                            </td>
                            <td data-th="{__("warehouses.shipping_delay_notification")}">
                                {if $store.warn_about_delay}
                                    <p class="muted">
                                        {if $store.shipping_delay}
                                            {__("warehouses.shipping_delay.description", [
                                                "[shipping_delay]" => $store.shipping_delay
                                            ])}
                                        {else}
                                            {__("warehouses.shipping_delay.description.default")}
                                        {/if}
                                    </p>
                                {/if}
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    </div>
    {else}
    <p class="no-items">{__("no_data")}</p>
    {/if}
</div>
