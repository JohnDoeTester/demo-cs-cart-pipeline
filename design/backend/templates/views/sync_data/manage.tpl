{capture name="mainbox"}
    {include_ext file="common/icon.tpl" class="icon-`$search.sort_order_rev`" assign=c_icon}
    {include_ext file="common/icon.tpl" class="icon-dummy" assign=c_dummy}

    {if $sync_provider_list}
        {$display_table = true}
        {if count($sync_provider_list) === 1}
            {foreach $sync_provider_list as $provider_id => $provider}
                {$display_table = fn_check_permissions("sync_data", "update", "admin", "GET", ["sync_provider_id" => $provider_id])}
            {/foreach}
        {/if}
        {if $display_table}
            <div class="table-responsive-wrapper">
                <table width="100%" class="table table-middle table--relative table-responsive">
                    <thead>
                    <tr>
                        <th><a class="cm-ajax" href="{"`$c_url`&sort_by=name&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("name")}{if $search.sort_by == "name"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                        <th>{__("last_sync")}</th>
                        <th>{__("status")}</th>
                        <th>{__("log_file")}</th>
                    </tr>
                    </thead>
                    <tbody>
                    {foreach $sync_provider_list as $provider_id => $provider}
                        {include file="views/sync_data/components/sync_provider.tpl" last_sync_info=$last_sync_info.$provider_id}
                    {/foreach}
                    </tbody>
                </table>
            </div>
        {else}
            <p class="no-items">{__("no_items_for_marketplace_administrator")}</p>
        {/if}
    {else}
        <p class="no-items">{__("no_items")}</p>
    {/if}
{/capture}

{include file="common/mainbox.tpl" title=__("sync_data") content=$smarty.capture.mainbox show_all_storefront=false}
