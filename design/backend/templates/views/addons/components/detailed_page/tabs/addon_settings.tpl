<div class="hidden" data-ca-addons="tabsSetting" id="content_settings">

    <div class="tabs cm-j-tabs {if $subsections|count == 1}hidden{else}cm-track{/if}" data-ca-addons="tabsSettingNested" data-ca-tabs-input-name>
        <ul class="nav nav-tabs">
            {foreach from=$subsections key="section" item="subs"}
                {$tab_id = "`$_addon`_`$section`"}
                <li class="cm-js {if $smarty.request.selected_sub_section === $tab_id}active{/if}" id="{$tab_id}"><a>{$subs.description}</a></li>
            {/foreach}
        </ul>
    </div>
    <div class="cm-tabs-content" id="tabs_content_{$_addon}">
        <form action="{""|fn_url}" method="post" name="update_addon_{$_addon}_form" id="update_addon_{$_addon}_form" class=" form-edit form-horizontal" enctype="multipart/form-data">

            <input type="hidden" name="selected_section" value="{$smarty.request.selected_section}" />
            <input type="hidden" name="selected_sub_section" value="{$smarty.request.selected_sub_section}" />
            <input type="hidden" name="addon" value="{$smarty.request.addon}" />
            <input type="hidden" name="storefront_id" value="{$smarty.request.storefront_id}" />
            {if $smarty.request.return_url}
                <input type="hidden" name="redirect_url" value="{$smarty.request.return_url}" />
            {/if}
            {foreach from=$options key="section" item="field_item"}
                {capture name="separate_section"}
                    <div id="content_{$_addon}_{$section}" class="settings cm-hide-save-button">
                        {capture name="header_first"}false{/capture}
                        {component
                            name="settings.settings_section"
                            allow_global_individual_settings=true
                            subsection=$field_item
                            section_name=$_addon
                            html_id_prefix="addon_option"
                            html_name="addon_data[options]"
                            class="setting-wide"
                        }{/component}
                    </div>
                {/capture}
                {if $subsections.$section.type == "SEPARATE_TAB"}
                    {$sep_sections = "`$sep_sections` `$smarty.capture.separate_section`"}
                {else}
                    {$smarty.capture.separate_section nofilter}
                {/if}
            {/foreach}

        </form>

        {foreach $subsections as $section => $subs}
            {if $subsections.$section.type == "SEPARATE_TAB"}
                {$sep_sections nofilter}
            {/if}
        {/foreach}
        {hook name="addons:addon_settings"}
        {/hook}
    </div>

<!--content_settings--></div>
