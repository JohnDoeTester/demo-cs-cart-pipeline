{$sync_provider_id = $smarty.request.sync_provider_id}

{capture name="mainbox"}
    <p class="text-error">{__('sync_data_update_template_not_found')}</p>
{/capture}

{include file="common/mainbox.tpl" title=$provider_data.name content=$smarty.capture.mainbox show_all_storefront=false}
