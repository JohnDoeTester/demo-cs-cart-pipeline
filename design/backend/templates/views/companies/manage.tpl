{include file="views/profiles/components/profiles_scripts.tpl"}

{capture name="mainbox"}

<form action="{""|fn_url}" method="post" name="companies_form" id="companies_form">
<input type="hidden" name="fake" value="1" />

{include file="common/pagination.tpl" save_current_page=true save_current_url=true}

{$c_url=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
{$c_statuses="companies"|fn_get_predefined_statuses:$company.status}
{include_ext file="common/icon.tpl" class="icon-`$search.sort_order_rev`" assign=c_icon}
{include_ext file="common/icon.tpl" class="icon-dummy" assign=c_dummy}
{$return_url=$config.current_url|escape:"url"}
{$name_col_width = ("MULTIVENDOR"|fn_allowed_for) ? "18%" : "40%"}

{if $companies}
    {capture name="companies_table"}
        <div class="table-responsive-wrapper longtap-selection">
            <table width="100%" class="table table-middle table--relative table-responsive">
            <thead data-ca-bulkedit-default-object="true">
            <tr>
                <th class="left mobile-hide table__check-items-column">
                    {include file="common/check_items.tpl" check_statuses=$c_statuses meta="table__check-items"}

                    <input type="checkbox"
                        class="bulkedit-toggler hide"
                        data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                        data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                    />
                </th>
                <th width="8%"><a class="cm-ajax" href="{"`$c_url`&sort_by=id&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("id")}{if $search.sort_by === "id"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                <th width="{$name_col_width}"><a class="cm-ajax" href="{"`$c_url`&sort_by=company&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("name")}{if $search.sort_by === "company"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                {if "MULTIVENDOR"|fn_allowed_for}
                    <th width="25%"><a class="cm-ajax" href="{"`$c_url`&sort_by=email&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("email")}{if $search.sort_by === "email"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                {/if}
                {if "ULTIMATE"|fn_allowed_for}
                    <th width="25%"><a class="cm-ajax" href="{"`$c_url`&sort_by=storefront&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("storefront_url")}{if $search.sort_by === "storefront"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                {/if}
                <th><a class="cm-ajax nowrap" href="{"`$c_url`&sort_by=date&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("registered")}{if $search.sort_by === "date"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                {hook name="companies:list_extra_th"}{/hook}
                <th width="4%" class="nowrap">&nbsp;</th>
                {if "MULTIVENDOR"|fn_allowed_for}
                    <th width="7%" class="nowrap right"><a class="nowrap cm-ajax" href="{"`$c_url`&sort_by=status&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("status")}{if $search.sort_by === "status"}{$c_icon nofilter}{/if}</a></th>
                {else}
                    <th width="7%"><span class="nowrap cm-tooltip" title="{__("ttc_stores_status")}">{__("stores_status")}&nbsp;{include_ext file="common/icon.tpl" class="icon-question-sign"}{if $search.sort_by === "stores_status"}{$c_icon nofilter}{/if}</span></th>
                {/if}
            </tr>
            </thead>
            {foreach from=$companies item=company}
            <tr class="cm-row-status-{if "MULTIVENDOR"|fn_allowed_for}{$company.status|lower}{else}{$company.storefront_status|lower}{/if} cm-longtap-target"
                    data-ct-company-id="{$company.company_id}"
                    data-ca-longtap-action="setCheckBox"
                    data-ca-longtap-target="input.cm-item"
                    data-ca-id="{$company.company_id}"
                    data-ca-bulkedit-dispatch-parameter="company_ids[]"
            >
                <td class="left mobile-hide table__check-items-cell">
                    <input type="checkbox"
                        name="company_ids[]"
                        value="{$company.company_id}"
                        class="cm-item cm-item-status-{if "MULTIVENDOR"|fn_allowed_for}{$company.status|lower}{else}{$company.storefront_status|lower}{/if} hide"
                    />
                </td>
                <td width="8%" class="row-status table__first-column" data-th="{__("id")}"><a href="{"companies.update?company_id=`$company.company_id`"|fn_url}">&nbsp;<span>{$company.company_id}</span>&nbsp;</a></td>
                <td width="{$name_col_width}" class="row-status wrap" data-th="{__("name")}"><a href="{"companies.update?company_id=`$company.company_id`"|fn_url}">{$company.company}</a></td>
                {if "MULTIVENDOR"|fn_allowed_for}
                    <td width="25%" class="row-status wrap" data-th="{__("email")}"><a href="mailto:{$company.email}">{$company.email}</a></td>
                {/if}
                {if "ULTIMATE"|fn_allowed_for}
                    {$storefront_href = "http://`$company.storefront`"}
                    {if $company.storefront_status === "StorefrontStatuses::CLOSED"|enum && $company.store_access_key}
                        {$storefront_href = $storefront_href|fn_link_attach:"store_access_key=`$company.store_access_key`"}
                    {/if}
                    <td width="25%" data-th="{__("storefront")}" id="storefront_url_{$company.company_id}"><a href="{$storefront_href}">{$company.storefront|puny_decode}</a><!--storefront_url_{$company.company_id}--></td>
                {/if}
                <td class="nowrap row-status" data-th="{__("registered")}">{$company.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</td>
                {hook name="companies:list_extra_td"}{/hook}
                <td width="4%" class="nowrap" data-th="{__("tools")}">
                    {capture name="tools_items"}
                    {hook name="companies:list_extra_links"}
                        <li>{btn type="list" href="products.manage?company_id=`$company.company_id`" text=__("view_vendor_products")}</li>
                        {if "MULTIVENDOR"|fn_allowed_for}
                            <li>{btn type="list" href="profiles.manage?user_type={"UserTypes::VENDOR"|enum}&company_id=`$company.company_id`" text=__("view_vendor_admins")}</li>
                        {else}
                            <li>{btn type="list" href="profiles.manage?company_id=`$company.company_id`" text=__("view_vendor_users")}</li>
                        {/if}
                        <li>{btn type="list" href="orders.manage?company_id=`$company.company_id`" text=__("view_vendor_orders")}</li>
                        {if !"ULTIMATE"|fn_allowed_for && !$runtime.company_id}
                            <li>{btn type="list" href="companies.merge?company_id=`$company.company_id`" text=__("merge")}</li>
                        {/if}
                        {if !$runtime.company_id && fn_check_view_permissions("companies.update", "POST")}
                            <li>{btn type="list" href="companies.update?company_id=`$company.company_id`" text=__("edit")}</li>
                            <li class="divider"></li>
                            {if $runtime.simple_ultimate}
                                <li class="disabled"><a>{__("delete")}</a></li>
                            {else}
                                <li>{btn type="list" class="cm-confirm" href="companies.delete?company_id=`$company.company_id`&redirect_url=`$return_current_url`" text=__("delete") method="POST"}</li>
                            {/if}
                        {/if}
                    {/hook}
                    {/capture}
                    <div class="hidden-tools">
                        {dropdown content=$smarty.capture.tools_items}
                    </div>
                </td>
                <td width="7%"
                    {if "MULTIVENDOR"|fn_allowed_for}
                        class="right nowrap"
                        data-th="{__("status")}"
                    {else}
                        class="row-status"
                        data-th="{__("stores_status")}"
                    {/if}
                >
                    {include file="views/companies/components/status_on_manage.tpl"
                        id=$company.company_id
                        status=$company.status
                        items_status="companies"|fn_get_predefined_statuses:$company.status
                        company=$company
                    }
                </td>
            </tr>
            {/foreach}
            </table>
        </div>
    {/capture}

    {include file="common/context_menu_wrapper.tpl"
        form="companies_form"
        object="companies"
        items=$smarty.capture.companies_table
    }
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{if $companies}
    {if !$runtime.company_id}
        {foreach $c_statuses as $status => $status_name}
            {$capture_name="selected_make_status_{$status}"}

            {capture name=$capture_name}
                {include file="views/companies/components/reason_container.tpl" status=$status}
                <div class="buttons-container">
                    {include file="buttons/save_cancel.tpl"
                        but_text=__("proceed")
                        but_href="companies.m_update_statuses?status=`$status`"|fn_url
                        cancel_action="close"
                        but_meta="cm-ajax cm-post cm-ajax-send-form"
                        but_target_form="#companies_form"
                        but_target_id="pagination_contents"
                    }
                </div>
            {/capture}

            {include file="common/popupbox.tpl"
                id=$capture_name
                text=__("change_to_status", ["[status]" => "`$status_name`"])
                content=$smarty.capture.$capture_name
                link_text=__("change_to_status", ["[status]" => "`$status_name`"])
            }
        {/foreach}
    {/if}
{/if}

{include file="common/pagination.tpl"}
</form>
{/capture}
{capture name="buttons"}
    {capture name="tools_items"}
        {hook name="companies:manage_tools_list"}
        {/hook}
    {/capture}
    {dropdown content=$smarty.capture.tools_items class="mobile-hide"}

    {if "MULTIVENDOR"|fn_allowed_for}
        {hook name="companies:manage_invite_vendors"}
            {include
                file="buttons/button.tpl"
                but_role="text"
                but_href="companies.invite"
                title=__("invite_vendors_title")
                but_text=__("invite_vendors")
                but_meta="btn cm-dialog-opener"
            }
        {/hook}
    {/if}
{/capture}

{if fn_allowed_for("MULTIVENDOR")}
    {$add_vendor_text = __("add_vendor")}
{else}
    {$add_vendor_text = __("add_storefront")}
{/if}

{capture name="adv_buttons"}
    {hook name="companies:manage_adv_buttons"}
        {if $is_companies_limit_reached || "ULTIMATE:FREE"|fn_allowed_for}
            {$title_suffix = ""|fn_get_product_state_suffix}
            {$promo_popup_title = __("ultimate_or_storefront_license_required.`$title_suffix`", ["[product]" => $smarty.const.PRODUCT_NAME])}

            {include file="common/tools.tpl"
                tool_override_meta="btn cm-dialog-opener cm-dialog-auto-height"
                tool_href="functionality_restrictions.ultimate_or_storefront_license_required"
                prefix="top"
                hide_tools=true
                title=$add_vendor_text
                icon="icon-plus"
                meta_data="data-ca-dialog-title='{$promo_popup_title}'"}
        {else}
            {include file="common/tools.tpl"
                tool_href="companies.add"
                prefix="top"
                hide_tools=true
                title=$add_vendor_text
                icon="icon-plus"
            }
        {/if}
    {/hook}
{/capture}

{capture name="sidebar"}
    {hook name="companies:manage_sidebar"}
    {include file="common/saved_search.tpl" dispatch="companies.manage" view_type="companies"}
    {include file="views/companies/components/companies_search_form.tpl" dispatch="companies.manage"}
    {/hook}
{/capture}

{capture name="page_title"}
    {if fn_allowed_for("MULTIVENDOR")}
        {__("vendors")}
    {else}
        {__("storefronts")}
    {/if}
{/capture}

{include file="common/mainbox.tpl" title=$smarty.capture.page_title content=$smarty.capture.mainbox buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons sidebar=$smarty.capture.sidebar}
