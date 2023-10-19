<div id="content_group_{$id}">

    <form action="{""|fn_url}" method="post" enctype="multipart/form-data" name="provider_form" class="form-horizontal form-edit">
        <input type="hidden" name="provider_data[provider_id]" value="{$id}" />

        <div class="tabs cm-j-tabs">
            <ul class="nav nav-tabs">
                <li id="tab_general_{$id}" class="cm-js active"><a>{__("general")}</a></li>
                {if fn_allowed_for("MULTIVENDOR:ULTIMATE")}
                    <li id="tab_storefronts_{$id}" class="cm-js"><a>{__("storefronts")}</a></li>
                {/if}
                <li id="tab_callback_urls_{$id}" class="cm-js"><a>{__('hybrid_auth.callback_url')}</a></li>
            </ul>
        </div>

        <div class="cm-tabs-content" id="tabs_content_{$id}">
            <div id="content_tab_general_{$id}">

                <div class="control-group">
                    <label for="section_provider_{$id}" class="control-label cm-required">{__("provider")}:</label>
                    <div class="controls">
                        <select name="provider_data[provider]" id="provider" class="cm-select-provider">
                            {foreach $available_providers as $provider_code}
                            <option value="{$provider_code}"{if $provider_code == $provider_data.provider} selected="selected"{/if} data-id="{$id}" data-provider="{$provider_code}">{$providers_schema.$provider_code.provider}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                <div class="control-group">
                    <label for="section_name_{$id}" class="control-label cm-required">{__("name")}:</label>
                    <div class="controls">
                        <input type="text" name="provider_data[name]" id="section_name_{$id}" value="{$provider_data.name}">
                    </div>
                </div>

                {include file="addons/hybrid_auth/views/hybrid_auth/provider_keys.tpl" provider=$provider}
                {include file="addons/hybrid_auth/views/hybrid_auth/provider_params.tpl" provider=$provider}
                {include file="common/select_status.tpl" input_name="provider_data[status]" id="provider_status" obj=$section}
            </div>
            {if fn_allowed_for("MULTIVENDOR:ULTIMATE")}
                <div class="hidden" id="content_tab_storefronts_{$id}">
                    {$add_storefront_text = __("add_storefronts")}
                    {if fn_allowed_for("ULTIMATE")}
                        {$add_storefront_text = __("add_companies")}
                    {/if}
                    {include file="pickers/storefronts/picker.tpl"
                        multiple=true
                        input_name="provider_data[storefront_ids]"
                        item_ids=$provider_data.storefront_ids
                        data_id="storefront_ids"
                        but_meta="pull-right"
                        no_item_text=__("all_storefronts")
                        but_text=$add_storefront_text
                        view_only=($is_sharing_enabled && $runtime.company_id)
                    }
                </div>
            {/if}
            <div class="hidden" id="content_tab_callback_urls_{$id}">
                {foreach $providers_schema[$provider].params as $param}
                    {if $param.type === "template"}
                        {include file=$param.template label=$param.label callback_url=$param.callback_url callback_urls=$callback_urls}
                    {/if}
                {/foreach}
            <!--content_tab_callback_urls_{$id}--></div>
        </div>

        <div class="buttons-container">
            {include file="buttons/save_cancel.tpl" but_name="dispatch[hybrid_auth.update_provider]" cancel_action="close" save=$id cancel_meta="bulkedit-unchanged"}
        </div>

    </form>
<!--content_group_{$id}--></div>
