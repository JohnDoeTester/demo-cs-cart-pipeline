{script src="js/tygh/fileuploader_scripts.js"}

{include file="views/profiles/components/profiles_scripts.tpl" states=1|fn_get_all_states states_are_optional=true}

{if $smarty.request.highlight}
{assign var="highlight" value=","|explode:$smarty.request.highlight}
{/if}


{capture name="mainbox"}
<form action="{""|fn_url}" method="post" name="settings_form" class=" form-horizontal form-edit form-setting">
    <input type="hidden" name="section_id" value="{$section_id}" />
    <input type="hidden" name="storefront_id" value="{$selected_storefront_id}" />
    <input type="hidden" id="selected_section" name="selected_section" value="{$selected_section}" />
    <input type="text" class="hidden"> {* Prevents submitting form if form containts only one input text. *}

    {capture name="tabsbox"}
        {foreach from=$options item=subsection key="ukey"}
            <div id="content_{$ukey}" {if $subsections.$section.type == "SEPARATE_TAB"}class="cm-hide-save-button"{/if}>
                {component
                    name="settings.settings_section"
                    subsection=$subsection
                    section=$section_id
                    html_id_prefix="field_"
                    html_name="update"
                    highlight=$highlight
                }{/component}

            </div>
        {/foreach}

        {capture name="buttons"}
            {include file="buttons/save.tpl" but_name="dispatch[settings.update]" but_role="submit-link" but_target_form="settings_form"}
        {/capture}

    {/capture}
    {include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox track=true}

</form>
{/capture}

{include file="common/mainbox.tpl"
    title_start=__("settings")
    title_end=$settings_title
    buttons=$smarty.capture.buttons
    content=$smarty.capture.mainbox
    sidebar_position="left"
    select_storefront=$select_storefront
    show_all_storefront=true
    storefront_switcher_param_name="storefront_id"
}

