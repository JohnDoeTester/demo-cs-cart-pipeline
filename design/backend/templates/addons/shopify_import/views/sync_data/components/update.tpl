{$sync_provider_id = $smarty.request.sync_provider_id}

{capture name="mainbox"}
    {capture name="tabsbox"}
        <div id="content_shopify_import">
            <form class="form-edit form-horizontal cm-check-changes cm-ajax cm-comet" action="{""|fn_url}" method="post" id="sync_data_settings_form" enctype="multipart/form-data">
                <input type="hidden" name="sync_provider_id" value="{$sync_provider_id}" />
                <input type="hidden" name="selected_section" value="{$smarty.request.selected_section|default:"general"}" />
                <input type="hidden" name="result_ids" value="content_shopify_import" />

                <div id="content_general">
                    {include file="addons/shopify_import/views/sync_data/components/general.tpl"}
                </div>
            </form>
            <!--content_shopify_import--></div>
    {/capture}

    {include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox group_name="shopify_import" active_tab=$smarty.request.selected_section track=true}

{/capture}

{capture name="buttons"}
    {include file="buttons/button.tpl" but_permission_data="sync_data.update?sync_provider_id={$sync_provider_id}" but_role="submit-link" but_name="dispatch[shopify_import.import]" but_target_form="sync_data_settings_form" but_text=__("import") but_meta="btn-primary"}
{/capture}

{include file="common/mainbox.tpl" title=$provider_data.name content=$smarty.capture.mainbox buttons=$smarty.capture.buttons show_all_storefront=false}
