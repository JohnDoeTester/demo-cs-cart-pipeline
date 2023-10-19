{capture name="mainbox"}
<form action="{""|fn_url}" method="post" name="ebay_templates_form" class="" id="ebay_templates_form">
<input type="hidden" name="fake" value="1" />
<input type="hidden" name="redirect_url" value="{$config.current_url}">

{include file="common/pagination.tpl" save_current_page=true save_current_url=true}

{assign var="return_current_url" value=$config.current_url|escape:url}
{assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
{include_ext file="common/icon.tpl" class="icon-`$search.sort_order_rev`" assign=c_icon}
{include_ext file="common/icon.tpl" class="icon-dummy" assign=c_dummy}

{if $templates}
    {capture name="ebay_templates_table"}
        <div class="table-responsive-wrapper longtap-selection">
            <table width="100%" class="table table-middle table--relative table-responsive">
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
                <th width="45%"><a class="cm-ajax" href="{"`$c_url`&sort_by=template&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("name")}{if $search.sort_by === "template"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                <th width="15%"><a class="cm-ajax" href="{"`$c_url`&sort_by=products&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("products")}{if $search.sort_by === "products"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                <th width="10%"></th>
                <th width="10%" class="right"><a class="cm-ajax" href="{"`$c_url`&sort_by=status&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{if $search.sort_by === "status"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}{__("status")}</a></th>
            </tr>
            </thead>
            {foreach $templates as $template}
            <tr class="cm-row-status-{$template.status|lower} cm-longtap-target"
                data-ca-longtap-action="setCheckBox"
                data-ca-longtap-target="input.cm-item"
                data-ca-id="{$template.template_id}"
            >
                {assign var="allow_save" value=$template|fn_allow_save_object:"ebay_templates"}
                {if $allow_save}
                    {assign var="no_hide_input" value="cm-no-hide-input"}
                    {assign var="display" value=""}
                {else}
                    {assign var="no_hide_input" value=""}
                    {assign var="display" value="text"}
                {/if}
                <td width="1%" class="left mobile-hide">
                    <input type="checkbox" name="template_ids[]" value="{$template.template_id}" class="cm-item hide cm-item-status-{$template.status|lower}" /></td>
                <td width="45%" class="row-status" data-th="{__("name")}">
                    <a href="{"ebay.update?template_id=`$template.template_id`"|fn_url}">{$template.name}</a>
                    {include file="views/companies/components/company_name.tpl" object=$template}
                </td>
                <td width="15%" data-th="{__("products")}">
                    <a href="{"products.manage?ebay_template_id=`$template.template_id`"|fn_url}">{$template.product_count}</a>
                </td>
                <td width="10%" class="nowrap right" data-th="{__("tools")}">
                    <div class="hidden-tools">
                        {capture name="tools_items"}
                            <li>{btn type="list" class="cm-ajax cm-comet" text=__("export_products_to_ebay") href="ebay.export_template?template_id=`$template.template_id`" method="POST"}</li>
                            <li>{btn type="list" class="cm-ajax cm-comet" text=__("ebay_end_template_on_ebay") href="ebay.end_template?template_id=`$template.template_id`" method="POST"}</li>
                            <li>{btn type="list" class="cm-ajax cm-comet" text=__("ebay_sync_products_status") href="ebay.update_template_product_status?template_id=`$template.template_id`" method="POST"}</li>
                            <li>{btn type="list" text=__("logs") href="ebay.product_logs?template_id=`$template.template_id`"}</li>
                            <li class="divider"></li>
                            <li>{btn type="list" text=__("edit") href="ebay.update?template_id=`$template.template_id`"}</li>
                            <li>{btn type="list" class="cm-confirm" data=["data-ca-confirm-text" => "{__("category_deletion_side_effects")}"] text=__("delete") href="ebay.delete_template?template_id=`$template.template_id`" method="POST"}</li>
                        {/capture}
                        {dropdown content=$smarty.capture.tools_items}
                    </div>
                </td>
                <td width="10%" class="right nowrap" data-th="{__("status")}">
                    {include file="common/select_popup.tpl" popup_additional_class="dropleft `$no_hide_input`" display=$display id=$template.template_id status=$template.status hidden=false object_id_name="template_id" table="ebay_templates"}
                </td>
            </tr>
            {/foreach}
            </table>
        </div>
    {/capture}

    {include file="common/context_menu_wrapper.tpl"
        form="ebay_templates_form"
        object="ebay_templates"
        items=$smarty.capture.ebay_templates_table
    }
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{include file="common/pagination.tpl"}
</form>

{capture name="adv_buttons"}
    {include file="common/tools.tpl" tool_href="ebay.add" prefix="top" hide_tools="true" title=__("add_ebay_template") icon="icon-plus"}
{/capture}

{/capture}
{include file="common/mainbox.tpl" title=__("ebay_templates") content=$smarty.capture.mainbox adv_buttons=$smarty.capture.adv_buttons}
