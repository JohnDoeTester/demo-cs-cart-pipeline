{capture name="mainbox"}

<form action="{""|fn_url}" method="post" id="promotion_form" name="promotion_form" class="{if ""|fn_check_form_permissions} cm-hide-inputs{/if}">

{include file="common/pagination.tpl" save_current_page=true save_current_url=true}

{$c_url=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
{$promotion_statuses=""|fn_get_default_statuses:true}
{include_ext file="common/icon.tpl" class="icon-`$search.sort_order_rev`" assign=c_icon}
{include_ext file="common/icon.tpl" class="icon-dummy" assign=c_dummy}

{if $promotions}
    {capture name="promotions_table"}
        <div class="table-responsive-wrapper">
            <table class="table table-middle table--relative table-responsive longtap-selection">
            <thead
                data-ca-bulkedit-default-object="true"
                data-ca-bulkedit-component="defaultObject"
            >
            <tr>
                <th width="5%" class="mobile-hide">
                    {include file="common/check_items.tpl" check_statuses=$promotion_statuses}

                    <input type="checkbox"
                        class="bulkedit-toggler hide"
                        data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                        data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                    />
                </th>
                <th>
                    <a class="cm-ajax" href="{"`$c_url`&sort_by=name&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("name")}{if $search.sort_by === "name"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                <th width="16%" class="center mobile-hide">{__("stop_other_rules")}</th>
                <th width="10%" class="nowrap center mobile-hide">
                    <a class="cm-ajax" href="{"`$c_url`&sort_by=priority&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("priority")}{if $search.sort_by === "priority"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                <th width="10%" class="mobile-hide">
                    <a class="cm-ajax" href="{"`$c_url`&sort_by=zone&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("zone")}{if $search.sort_by === "zone"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>

                {hook name="promotions:manage_header"}{/hook}

                <th width="8%" class="mobile-hide">&nbsp;</th>
                <th width="10%" class="right"><a class="cm-ajax" href="{"`$c_url`&sort_by=status&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("status")}{if $search.sort_by === "status"}{$c_icon nofilter}{/if}</a></th>
            </tr>
            </thead>

            {foreach from=$promotions item=promotion}

                {$allow_save=$promotion|fn_allow_save_object:"promotions"}

                {if $allow_save}
                    {$link_text=__("edit")}
                    {$additional_class="cm-no-hide-input"}
                    {$status_display=""}
                {else}
                    {$link_text=__("view")}
                    {$additional_class="cm-hide-inputs"}
                    {$status_display="text"}
                {/if}

            <tr class="cm-row-status-{$promotion.status|lower} cm-longtap-target {$additional_class}"
                data-ca-longtap-action="setCheckBox"
                data-ca-longtap-target="input.cm-item"
                data-ca-id="{$promotion.promotion_id}"
            >
                <td width="5%" class="mobile-hide">
                    <input name="promotion_ids[]" type="checkbox" value="{$promotion.promotion_id}" class="cm-item cm-item-status-{$promotion.status|lower} hide" /></td>
                <td data-th="{__("name")}">
                    <a class="row-status" href="{"promotions.update?promotion_id=`$promotion.promotion_id`"|fn_url}">{$promotion.name}</a>
                    {include file="views/companies/components/company_name.tpl" object=$promotion}
                <td width="16%" class="center mobile-hide" data-th="{__("stop_other_rules")}">
                    <span class="row-status">{if $promotion.stop_other_rules == "YesNo::YES"|enum}{__("yes")}{else}{__("no")}{/if}</span>
                </td>
                <td width="10%" class="center mobile-hide" data-th="{__("priority")}">
                    <span class="row-status">{$promotion.priority}</span>
                </td>
                <td width="10%" class="mobile-hide" data-th="{__("zone")}">
                    <span class="row-status">{__($promotion.zone)}</span>
                </td>

                {hook name="promotions:manage_data"}{/hook}

                <td width="8%" class="right mobile-hide">
                    <div class="hidden-tools">
                    {capture name="tools_list"}
                        {hook name="promotions:list_extra_links"}
                        <li>{btn type="list" text=$link_text href="promotions.update?promotion_id=`$promotion.promotion_id`"}</li>
                        {if $allow_save}
                            <li>{btn type="list" text=__("delete") class="cm-confirm" href="promotions.delete?promotion_id=`$promotion.promotion_id`" method="POST"}</li>
                        {/if}
                        {/hook}
                    {/capture}
                    {dropdown content=$smarty.capture.tools_list}
                    </div>
                </td>
                <td width="10%" class="nowrap right" data-th="{__("status")}">
                    {include file="common/select_popup.tpl" popup_additional_class="dropleft" display=$status_display id=$promotion.promotion_id status=$promotion.status hidden=true object_id_name="promotion_id" table="promotions"}
                </td>
            </tr>
            {/foreach}
            </table>
        </div>
    {/capture}

    {include file="common/context_menu_wrapper.tpl"
        form="promotion_form"
        object="promotions"
        items=$smarty.capture.promotions_table
    }
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{include file="common/pagination.tpl"}

{capture name="buttons"}
    {capture name="tools_list"}
        {hook name="promotions:manage_tools_list"}
        {/hook}
    {/capture}
    {dropdown content=$smarty.capture.tools_list class="mobile-hide"}
{/capture}

{capture name="adv_buttons"}
    {capture name="tools_list"}
        <li>{btn type="list" text=__("add_catalog_promotion") href="promotions.add?zone=catalog"}</li>
        <li>{btn type="list" text=__("add_cart_promotion") href="promotions.add?zone=cart"}</li>
    {/capture}
    {dropdown content=$smarty.capture.tools_list icon="icon-plus" no_caret=true placement="right"}
    {** Hook for the actions menu on the products manage page *}
{/capture}

</form>
{/capture}
{include file="common/mainbox.tpl"
    title=__("promotions")
    content=$smarty.capture.mainbox
    tools=$smarty.capture.tools
    select_languages=true
    buttons=$smarty.capture.buttons
    adv_buttons=$smarty.capture.adv_buttons
}
