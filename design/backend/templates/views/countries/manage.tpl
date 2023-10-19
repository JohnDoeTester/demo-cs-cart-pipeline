{capture name="mainbox"}

<form action="{""|fn_url}" method="post" name="countries_form" class="{if ""|fn_check_form_permissions} cm-hide-inputs{/if}">

{$has_permission = fn_check_permissions("countries", "m_activate", "admin", "POST", ["table" => "countries"]) && fn_check_permissions("countries", "m_disable", "admin", "POST", ["table" => "countries"])}
{$country_statuses=""|fn_get_default_statuses:false}

{include file="common/pagination.tpl" save_current_page=true save_current_url=true}

<div data-ca-longtap>
    {if $has_permission}
        {hook name="countries:context_menu"}
            {component
                name="context_menu.context_menu"
                object="countries"
                form="countries_form"
            }{/component}
        {/hook}
    {/if}

    <div class="table-responsive-wrapper longtap-selection">
        <table width="100%" class="table table-middle table--relative table-responsive">
        <thead data-ca-bulkedit-default-object="true" data-ca-bulkedit-component="defaultObject">
            <tr>
                <th width="6%" class="left mobile-hide">
                    {include file="common/check_items.tpl" check_statuses=($has_permission) ? ($country_statuses) : ""}

                    <input type="checkbox"
                        class="bulkedit-toggler hide"
                        data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]" 
                        data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                    />
                </th>
                <th width="10%" class="center">{__("code")}</th>
                <th width="10%" class="center">{__("code")}&nbsp;A3</th>
                <th width="10%" class="center">{__("code")}&nbsp;N3</th>
                <th>{__("country")}</th>
                <th width="10%" class="center">{__("region")}</th>
                <th class="right" width="10%">{__("status")}</th>
            </tr>
        </thead>
        {foreach from=$countries item=country}
        <tr class="cm-row-status-{$country.status|lower} cm-longtap-target"
            {if $has_permission}
                data-ca-longtap-action="setCheckBox"
                data-ca-longtap-target="input.cm-item"
                data-ca-id="{$country.code}"
            {/if}
        >
        <td width="6%" class="center">
                {* <input type="checkbox" name="delete[{$country.code}]" id="delete_checkbox" value="Y" class="checkbox cm-item" /> *}
                <input type="checkbox" name="countries[]" value="{$country.code}" class="cm-item cm-item-status-{$country.status|lower} hide" />
            </td>  
            <td width="10%" class="center row-status" data-th="{__("code")}">
                {* <input type="text" name="country_data[{$country.code}][code]" size="2" value="{$country.code}" class="input-small input-hidden" />*}{$country.code}
            </td>
            <td width="10%" class="center row-status" data-th="{__("code")}&nbsp;A3">
                {*<input type="text" name="country_data[{$country.code}][code_A3]" size="3" value="{$country.code_A3}" class="input-small input-hidden" />*}{$country.code_A3}
            </td>
            <td width="10%" class="center row-status" data-th="{__("code")}&nbsp;N3">
                {*<input type="text" name="country_data[{$country.code}][code_N3]" size="5" value="{$country.code_N3}" class="input-small input-hidden" />*}{$country.code_N3}
            </td>
            <td data-th="{__("country")}"> 
                <input type="text" name="country_data[{$country.code}][country]" size="55" value="{$country.country}" class="span4 input-hidden" />
            </td>
            <td width="10%" class="center row-status" data-th="{__("region")}">
                {*<input type="text" name="country_data[{$country.code}][region]" size="3" value="{$country.region}" class="input-medium input-hidden" />*}{$country.region}
            </td>
            <td width="10%" class="right" data-th="{__("status")}">
                {$has_permission_update_status = fn_check_permissions("tools", "update_status", "admin", "GET", ["table" => "countries"])}
                {include file="common/select_popup.tpl" id=$country.code status=$country.status hidden="" object_id_name="code" table="countries" non_editable=!$has_permission_update_status}
            </td>
        </tr>
        {/foreach}
        </table>
    </div>
</div>
{include file="common/pagination.tpl"}

</form>

{capture name="buttons"}
{include file="buttons/save.tpl" but_name="dispatch[countries.m_update]" but_role="submit-link" but_target_form="countries_form"}

{* Deletion of existent countries functionality is disabled by default *}
    {*capture name="tools_list"}
        <li><a data-ca-dispatch="dispatch[countries.delete]" class="cm-process-items cm-submit cm-confirm" data-ca-target-form="countries_form">{__("delete_selected")}</a></li>
    {/capture}
{*include file="common/tools.tpl" prefix="main" hide_actions=true tools_list=$smarty.capture.tools_list display="inline" link_text=__("choose_action")*}
{/capture}
 {* Add new country functionality is disabled by default *}

{/capture}
{include file="common/mainbox.tpl" title=__("countries") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons select_languages=true}
