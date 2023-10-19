{script src="js/tygh/tabs.js"}

{capture name="mainbox"}

<form action="{""|fn_url}" method="post" name="vendor_plans_form" id="vendor_plans_form">

{$has_management_permissions = fn_check_permissions("vendor_plans", "update", "admin", "POST")}

{include file="common/pagination.tpl" save_current_page=true save_current_url=true}

{assign var="return_current_url" value=$config.current_url|escape:url}
{assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
{assign var="extra_status" value=$config.current_url|escape:"url"}
{include_ext file="common/icon.tpl" class="icon-`$search.sort_order_rev`" assign=c_icon}
{include_ext file="common/icon.tpl" class="icon-dummy" assign=c_dummy}

{if $plans}
    {capture name="vendor_plans_table"}
        <div class="table-responsive-wrapper longtap-selection">
            <table width="100%" class="table table-middle table--relative{if !$has_management_permissions} cm-hide-inputs{/if} table-responsive">
                <thead
                        data-ca-bulkedit-default-object="true"
                        data-ca-bulkedit-component="defaultObject"
                >
                    <tr>
                        <th width="1%" class="left mobile-hide">
                            {include file="common/check_items.tpl"}

                            <input type="checkbox"
                                   class="bulkedit-toggler hide"
                                   data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                                   data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                            />
                        </th>
                        <th width="6%" class="nowrap">
                            <a class="cm-ajax" href="{"`$c_url`&sort_by=position&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("position_short")}{if $search.sort_by === "position"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a>
                        </th>
                        <th width="28%">
                            <a class="cm-ajax" href="{"`$c_url`&sort_by=plan&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("name")}{if $search.sort_by === "plan"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a>
                        </th>
                        <th width="22%" class="center">
                            <a class="cm-ajax" href="{"`$c_url`&sort_by=price&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("price")} ({$currencies.$primary_currency.symbol nofilter}){if $search.sort_by === "price"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a>
                        </th>
                        <th width="10%" class="center nowrap">{__("vendor_plans.best_choise_short")}</th>
                        <th width="12%" class="center">{__("vendors")}</th>
                        <th width="10%" class="nowrap">&nbsp;</th>
                        <th width="10%" class="right">
                            <a class="cm-ajax" href="{"`$c_url`&sort_by=status&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("status")}{if $search.sort_by === "status"}{$c_icon nofilter}{/if}</a>
                        </th>
                    </tr>
                </thead>
                {foreach $plans as $plan}
                    <tr class="cm-row-status-{$plan.status|lower} cm-longtap-target" data-ct-company-id="{$plan.plan_id}"
                        data-ca-longtap-action="setCheckBox"
                        data-ca-longtap-target="input.cm-item"
                        data-ca-id="{$plan.plan_id}"
                        data-ca-category-ids="{$plan.category_ids|to_json}"
                    >
                        <td class="left mobile-hide">
                            <input type="checkbox" name="plan_ids[]" value="{$plan.plan_id}" class="cm-item cm-item-status-{$plan.status|lower} hide" />
                        </td>
                        <td class="left" data-th="{__("position_short")}">
                            <input type="text" name="plans_data[{$plan.plan_id}][position]" value="{$plan.position}" size="3" class="input-micro input-hidden" />
                        </td>
                        <td class="row-status" data-th="{__("name")}">
                            {if $has_management_permissions}
                                <a class="row-status cm-external-click" data-ca-external-click-id="{"opener_plan_`$plan.plan_id`"}">
                            {/if}
                                {$plan.plan}
                            {if $has_management_permissions}
                                </a>
                            {/if}
                        </td>
                        <td class="row-status" data-th="{__("price")} ({$currencies[$smarty.const.CART_PRIMARY_CURRENCY].symbol})">
                            {strip}
                            <input type="text" name="plans_data[{$plan.plan_id}][price]" value="{$plan.price}" size="6" class="input-mini input-hidden" />
                            &nbsp;
                            <select name="plans_data[{$plan.plan_id}][periodicity]" class="input-small input-hidden">
                                {foreach from=$periodicities key=key item=item}
                                    <option value="{$key}"{if $key == $plan.periodicity} selected="selected"{/if}>{$item}</option>
                                {/foreach}
                            </select>
                            {/strip}
                        </td>
                        <td class="center" data-th="{__("vendor_plans.best_choise_short")}">
                            <input type="radio" name="default_plan" value="{$plan.plan_id}"{if $plan.is_default} checked="checked"{/if} {if $plan.status === "ObjectStatuses::DISABLED"|enum}disabled="disabled"{/if} />
                        </td>
                        <td class="center" data-th="{__("vendors")}">
                            <a href="{"companies.manage?plan_id=`$plan.plan_id`"|fn_url}" class="badge">{$plan.companies_count}</a>
                        </td>
                        <td class="nowrap" data-th="{__("tools")}">
                            {capture name="tools_items"}
                            {hook name="vendor_plans:list_extra_links"}
                                {if $has_management_permissions}
                                    <li>{include file="common/popupbox.tpl" id="plan_`$plan.plan_id`" text=$plan.plan link_text=__("edit") act="link" href="vendor_plans.update?plan_id=`$plan.plan_id`"}</li>
                                    <li>{btn type="list" class="cm-confirm" href="vendor_plans.delete?plan_id=`$plan.plan_id`&redirect_url=`$return_current_url`" text=__("delete") method="POST"}</li>
                                {/if}
                            {/hook}
                            {/capture}
                            <div class="hidden-tools">
                                {dropdown content=$smarty.capture.tools_items}
                            </div>
                        </td>
                        <td class="right nowrap" data-th="{__("status")}">
                            {include file="common/select_popup.tpl"
                                id=$plan.plan_id
                                status=$plan.status
                                items_status=$plan.status|fn_get_default_statuses:true
                                hidden=true
                                update_controller="vendor_plans"
                                hide_for_vendor=!$has_management_permissions
                                extra="&return_url=`$extra_status`"
                                status_target_id="pagination_contents"
                            }
                        </td>
                    </tr>
                {/foreach}
            </table>
        </div>
    {/capture}

    {include file="common/context_menu_wrapper.tpl"
        form="vendor_plans_form"
        object="vendor_plans"
        items=$smarty.capture.vendor_plans_table
    }
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{include file="common/pagination.tpl"}

</form>
{/capture}

{capture name="buttons"}
    {if $plans && $has_management_permissions}
        {capture name="tools_items"}
            <li>{btn type="list" target="_blank" text=__("preview") href=$preview_uri}</li>
        {/capture}
        {dropdown content=$smarty.capture.tools_items}

        {include file="buttons/save.tpl"
            but_name="dispatch[vendor_plans.m_update]"
            but_role="submit-button"
            but_target_form="vendor_plans_form"
            but_meta="bulkedit-disable-save-button"
        }
    {/if}
{/capture}

{capture name="adv_buttons"}
    {if $has_management_permissions}
        {include file="common/popupbox.tpl" id="add_new_usergroups" text=__("vendor_plans.new_vendor_plan") title=__("vendor_plans.add_vendor_plan") href="vendor_plans.add"|fn_url act="general" icon="icon-plus"}
    {/if}
{/capture}

{capture name="sidebar"}
    {include file="common/saved_search.tpl" dispatch="vendor_plans.manage" view_type="vendor_plans"}
    {include file="addons/vendor_plans/views/vendor_plans/components/plans_search_form.tpl" dispatch="vendor_plans.manage"}
{/capture}

{include file="common/mainbox.tpl" title=__("vendor_plans.vendor_plans") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons sidebar=$smarty.capture.sidebar}
