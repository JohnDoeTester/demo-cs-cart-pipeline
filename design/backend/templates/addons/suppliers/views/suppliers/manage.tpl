{include file="views/profiles/components/profiles_scripts.tpl"}
{script src="js/addons/suppliers/bulk_edit.js"}

{capture name="mainbox"}

    <form action="{""|fn_url}" method="post" name="suppliers_list_form" id="suppliers_list_form">
        <input type="hidden" name="fake" value="1" />

        {$c_url=$config.current_url|fn_query_remove:"sort_by":"sort_order"}

        {include_ext file="common/icon.tpl" class="icon-`$search.sort_order_rev`" assign=c_icon}
        {include_ext file="common/icon.tpl" class="icon-dummy" assign=c_dummy}

        {include file="common/pagination.tpl" save_current_page=true save_current_url=true}

        {if $suppliers}
            {capture name="suppliers_table"}
                <div class="table-responsive-wrapper longtap-selection">
                    <table width="100%" class="table table-middle table--relative table-responsive">
                        <thead
                                data-ca-bulkedit-default-object="true"
                                data-ca-bulkedit-component="defaultObject"
                        >
                            <tr>
                                <th width="6%" class="left mobile-hide">
                                    {include file="common/check_items.tpl"}

                                    <input type="checkbox"
                                           class="bulkedit-toggler hide"
                                           data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]"
                                           data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                                    />
                                </th>
                                <th width="5%"><a class="cm-ajax" href="{"`$c_url`&sort_by=id&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("id")}{if $search.sort_by === "id"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                                <th width="25%"><a class="cm-ajax" href="{"`$c_url`&sort_by=name&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("name")}{if $search.sort_by === "name"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                                <th width="20%"><a class="cm-ajax" href="{"`$c_url`&sort_by=email&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("email")}{if $search.sort_by === "email"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                                <th width="15%"><a class="cm-ajax" href="{"`$c_url`&sort_by=date&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("registered")}{if $search.sort_by === "date"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>

                                {hook name="suppliers:manage_header"}{/hook}

                                <th width="15%" class="">&nbsp;</th>
                                <th width="10%" class="right"><a class="cm-ajax" href="{"`$c_url`&sort_by=status&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("status")}{if $search.sort_by === "status"}{$c_icon nofilter}{/if}</a></th>
                            </tr>
                        </thead>

                        {foreach $suppliers as $supplier}

                            {$allow_save=$supplier|fn_allow_save_object:"suppliers"}
                            {if !$allow_save && !"RESTRICTED_ADMIN"|defined && $auth.is_root != 'Y'}
                                {$link_text=__("view")}
                                {$popup_additional_class=""}
                            {elseif $allow_save || "RESTRICTED_ADMIN"|defined || $auth.is_root == 'Y'}
                                {$link_text="edit"}
                                {$popup_additional_class="cm-no-hide-input"}
                            {else}
                                {$popup_additional_class=""}
                                {$link_text="edit"}
                            {/if}

                            <tr class="cm-row-status-{$supplier.status|lower} cm-longtap-target"
                                data-ca-longtap-action="setCheckBox"
                                data-ca-longtap-target="input.cm-item"
                                data-ca-id="{$supplier.supplier_id}"
                            >
                                <td width="6%" class="center {$no_hide_input} mobile-hide">
                                    <input type="checkbox" name="supplier_ids[]" value="{$supplier.supplier_id}" class="cm-item cm-item-status-{$supplier.status|lower} hide" />
                                </td>
                                <td width="5%" data-th="{__("id")}"><a class="row-status" href="{"suppliers.update?supplier_id=`$supplier.supplier_id`"|fn_url}">{$supplier.supplier_id}</a></td>
                                <td width="25%" class="row-status" data-th="{__("name")}">{if $supplier.name}<a href="{"suppliers.update?supplier_id=`$supplier.supplier_id`"|fn_url}">{$supplier.name}</a>{else}-{/if}{include file="views/companies/components/company_name.tpl" object=$supplier}</td>
                                <td width="20%" data-th="{__("email")}"><a class="row-status" href="mailto:{$supplier.email|escape:url}">{$supplier.email}</a></td>
                                <td width="15%" class="row-status" data-th="{__("registered")}">{$supplier.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</td>

                                {hook name="suppliers:manage_data"}{/hook}

                                <td width="15%" class="center" data-th="{__("tools")}">
                                    <div class="hidden-tools">
                                    {capture name="tools_list"}
                                        {hook name="companies:list_extra_links"}
                                            {$return_current_url=$config.current_url|escape:url}
                                            <li>{btn type="list" text=__("view_supplier_products") href="products.manage?supplier_id=`$supplier.supplier_id`"}</li>
                                            <li class="divider"></li>
                                            <li>{btn type="list" text=__($link_text) href="suppliers.update?supplier_id=`$supplier.supplier_id`"}</li>
                                            {$return_current_url=$config.current_url|escape:url}
                                            <li>{btn type="list" class="cm-confirm" text=__("delete") href="suppliers.delete?supplier_id=`$supplier.supplier_id`&redirect_url=`$return_current_url`" method="POST"}</li>
                                        {/hook}
                                    {/capture}
                                    {dropdown content=$smarty.capture.tools_list}
                                    </div>
                                </td>
                                <td width="10%" class="right" data-th="{__("status")}">
                                    {include file="common/select_popup.tpl" id=$supplier.supplier_id status=$supplier.status hidden="" update_controller="suppliers" popup_additional_class="`$popup_additional_class` dropleft"}
                                </td>
                            </tr>
                        {/foreach}
                    </table>
                </div>
            {/capture}

            {include file="common/context_menu_wrapper.tpl"
                form="suppliers_list_form"
                object="suppliers"
                items=$smarty.capture.suppliers_table
            }
        {else}
            <p class="no-items">{__("no_data")}</p>
        {/if}

        {include file="common/pagination.tpl"}
    </form>
{/capture}

{capture name="adv_buttons"}
    {btn type="add" title=__("add_supplier") href="suppliers.add"}
{/capture}

{capture name="sidebar"}
    {include file="common/saved_search.tpl" dispatch="suppliers.manage" view_type="suppliers"}
    {include file="views/profiles/components/users_search_form.tpl" dispatch="suppliers.manage"}
{/capture}

{include file="common/mainbox.tpl"
    title=__("suppliers")
    content=$smarty.capture.mainbox
    sidebar=$smarty.capture.sidebar
    adv_buttons=$smarty.capture.adv_buttons
}
