{if $lang_data}
    {$id = $lang_data.lang_id}
{else}
    {$id = "0"}
{/if}

{$hide_inputs = !""|fn_allow_save_object:"languages"}

<div id="content_group{$id}">

{if $id}
    <form action="{""|fn_url}" method="post" enctype="multipart/form-data" name="add_language_form" class="form-horizontal{if !""|fn_allow_save_object:"languages"} cm-hide-inputs{/if}">
    <input type="hidden" name="selected_section" value="languages" />
    <input type="hidden" name="lang_id" value="{$id}" />

    <div class="tabs cm-j-tabs">
        <ul class="nav nav-tabs">
            <li id="tab_general_{$id}" class="cm-js active"><a>{__("general")}</a></li>
            {if fn_allowed_for("MULTIVENDOR:ULTIMATE") || $is_sharing_enabled}
                <li id="tab_storefronts_{$id}" class="cm-js"><a>{__("storefronts")}</a></li>
            {/if}
            {hook name="languages:tabs_list"}{/hook}
        </ul>
    </div>

    <div class="cm-tabs-content">
        <div id="content_tab_general_{$id}">
            <fieldset>
                <div class="control-group">
                    <label for="elm_to_lang_code_{$id}" class="control-label cm-required">{__("language_code")}:</label>
                    <div class="controls">
                        <input id="elm_to_lang_code_{$id}" type="text" name="language_data[lang_code]" value="{$lang_data.lang_code}" size="6" maxlength="2">
                        <p class="muted description">{__("tt_views_languages_manage_language_code")}</p>
                    </div>
                </div>

                <div class="control-group">
                    <label for="elm_lang_name_{$id}" class="control-label cm-required">{__("name")}:</label>
                    <div class="controls">
                        <input id="elm_lang_name_{$id}" type="text" name="language_data[name]" value="{$lang_data.name}" maxlength="64">
                    </div>
                </div>

                <div class="control-group">
                    <label for="elm_lang_country_code_{$id}" class="control-label cm-required">{__("country")}:</label>
                    <div class="controls">
                        <select id="elm_lang_country_code_{$id}" name="language_data[country_code]">
                            {foreach from=$countries item="country" key="code"}
                                <option {if $code == $lang_data.country_code}selected="selected"{/if} value="{$code}">{$country}</option>
                            {/foreach}
                        </select>
                        <p class="muted description">{__("tt_views_languages_update_country")}</p>
                    </div>
                </div>

                {include file="common/select_status.tpl" obj=$lang_data display="radio" input_name="language_data[status]" hidden=true}

                {if !$id}
                <div class="control-group">
                    <label for="elm_from_lang_code_{$id}" class="control-label cm-required">{__("clone_from")}:</label>
                    <div class="controls">
                        <select name="language_data[from_lang_code]" id="elm_from_lang_code_{$id}">
                            {foreach from=""|fn_get_translation_languages item="language"}
                                <option value="{$language.lang_code}">{$language.name}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                {/if}

                {hook name="languages:update"}{/hook}

            </fieldset>
        <!--content_tab_general_{$id}--></div>

        {if fn_allowed_for("MULTIVENDOR:ULTIMATE")|| $is_sharing_enabled}
            <div class="hidden" id="content_tab_storefronts_{$id}">
                {$add_storefront_text = __("add_storefronts")}
                {include file="pickers/storefronts/picker.tpl"
                    multiple=true
                    input_name="language_data[storefront_ids]"
                    item_ids=$lang_data.storefront_ids
                    data_id="storefront_ids"
                    but_meta="pull-right"
                    no_item_text=__("all_storefronts")
                    but_text=$add_storefront_text
                    view_only=($is_sharing_enabled && $runtime.company_id)
                }
                <!--content_tab_storefronts_{$id}--></div>
        {/if}

        {hook name="languages:tabs_content"}{/hook}
    </div>

    {if !$hide_inputs}
        <div class="buttons-container">
            {include file="buttons/save_cancel.tpl" but_name="dispatch[languages.update]" cancel_action="close" save=$id cancel_meta="bulkedit-unchanged"}
        </div>
    {/if}

    </form>
{else}
    <div class="install-addon">
        <form action="{""|fn_url}" method="post" name="add_language_form" class="form-horizontal{if $hide_inputs} cm-hide-inputs{/if}" enctype="multipart/form-data">

            <div class="install-addon-wrapper">
                <img class="install-addon-banner" src="{$images_dir}/addon_box.png" width="151px" height="141px" />
                {include file="common/fileuploader.tpl" var_name="language_data[po_file]" allowed_ext="po, zip"}
            </div>

            {if !$hide_inputs}
                <div class="buttons-container">
                    {include file="buttons/save_cancel.tpl" but_name="dispatch[languages.install_from_po]" but_text=__("install") cancel_action="close" save=$id}
                </div>
            {/if}
        </form>
    </div>
{/if}

<!--content_group{$id}--></div>