<div class="hidden" id="content_pickup">
    {if $pickup_locations}
        <div id="pickup_list">
            <div class="table-responsive-wrapper">
                <table class="table table--relative table-responsive" data-ca-sortable="true" data-ca-sort-list="[[0, 0]]">
                    <thead>
                    <tr>
                        <th data-ca-sortable-column="true">{__("store_locator.name")}</th>
                        <th data-ca-sortable-column="true">{__("store_locator.main_destination")}</th>
                        {hook name="store_locator:tab_list_extra_th"}{/hook}
                    </tr>
                    </thead>
                    <tbody>
                    {foreach $pickup_locations as $store}
                        <tr class="cm-row-status-{$store.status|strtolower}">
                            <td data-th="{__("store_locator.name")}">
                                <a href="{fn_url("store_locator.update?store_location_id={$store.store_location_id}")}"
                                   class="row-status"
                                >{$store.name}</a>
                                {include file="views/companies/components/company_name.tpl" object=$store}
                            </td>
                            <td data-th="{__("store_locator.main_destination")}"
                            >
                                {if $store.main_destination_id == $destination.destination_id}
                                    <span class="row-status">
                                        {$store.main_destination_id|fn_get_destination_name}
                                    </span>
                                {else}
                                    <a href="{fn_url("destinations.update?destination_id={$store.main_destination_id}")}"
                                       class="row-status"
                                    >{$store.main_destination_id|fn_get_destination_name}</a>
                                {/if}
                            </td>
                            {hook name="store_locator:tab_list_extra_td"}{/hook}
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
