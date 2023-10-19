{capture name="mainbox"}
    {if "ULTIMATE"|fn_allowed_for}
        {$storefront_id=$app.storefront->storefront_id}
    {else}
        {$storefront_id=$app["storefront.switcher.selected_storefront_id"]}
    {/if}
<form action="{""|fn_url}" method="post" name="categories_m_addition_form">

<div class="table-responsive-wrapper">
    <table width="100%" class="table table-middle table--relative table-responsive">
    <thead>
    <tr class="cm-first-sibling">
        <th width="15%">{__("category_location")}</th>
        <th width="15%">{__("category_name")}</th>
        {if $runtime.is_multiple_storefronts}
            <th width="15%">{if "ULTIMATE"|fn_allowed_for}{__("vendor")}{else}{__("storefront")}{/if}</th>
        {elseif "ULTIMATE"|fn_allowed_for}
            <th class="hide"></th>
        {/if}
        <th width="15%">{__("usergroup")}</th>
        <th width="10%">{__("position")}</th>
        <th width="15%">{__("status")}</th>
        <th width="7%">&nbsp;</th>
    </tr>
    </thead>
    <tr id="box_new_cat_tag">
        <td data-th="{__("category_location")}">
            {if "categories"|fn_show_picker:$smarty.const.CATEGORY_THRESHOLD}
                {include file="pickers/categories/picker.tpl"
                    data_id="location_category"
                    input_name="categories_data[0][parent_id]"
                    item_ids=0
                    hide_link=true
                    hide_delete_button=true
                    default_name=__("root_level")
                    extra_url="&s_storefront=`$storefront_id`"
                }
            {else}
                {if "MULTIVENDOR"|fn_allowed_for}
                    {$storefront_ids=[$storefront_id]}
                {else}
                    {$storefront_ids=null}
                {/if}
                {include file="common/select_category.tpl"
                    name="categories_data[0][parent_id]"
                    select_class="input-medium"
                    root_text=__("root_level")
                    id=""
                    storefront_ids=$storefront_ids
                }
            {/if}
        </td>
        <td data-th="{__("category_name")}">
            <input class="span3" type="text" name="categories_data[0][category]" size="40" value="" />
        </td>
        {if $runtime.is_multiple_storefronts}
            {if "ULTIMATE"|fn_allowed_for}
                <td data-th="{__("vendor")}">
                    {include file="views/companies/components/company_field.tpl"
                        name="categories_data[0][company_id]"
                        id="categories_data_company_id_0"
                        no_wrap=true
                    }
                </td>
            {else}
                <td data-th="{__("storefront")}">
                    {if $storefront_id}
                        {$app.storefront->name}
                    {else}
                        {__("all_storefronts")}
                    {/if}
                    <input type="hidden" name="categories_data[0][storefront_id]" value="{$storefront_id}" />
                </td>
            {/if}
        {elseif "ULTIMATE"|fn_allowed_for}
            <td class="hide">
                <input type="hidden" name="categories_data[0][storefront_id]" value="{$storefront_id}" />
            </td>
        {/if}

        <td data-th="{__("usergroup")}">
            {include file="common/select_usergroups.tpl"
                id="ug"
                select_mode=true
                title=__("usergroup")
                id="ship_data_`$shipping.shipping_id`"
                name="categories_data[0][usergroup_ids]"
                usergroups=["type"=>"C", "status"=>["A", "H"]]|fn_get_usergroups:$smarty.const.DESCR_SL
                input_extra=""
            }
        </td>
        <td data-th="{__("position")}">
            <input class="input-micro" type="text" name="categories_data[0][position]" size="3" value="" />
        </td>
        <td data-th="{__("status")}">
            <select name="categories_data[0][status]" class="input-small">
                <option value="A">{__("active")}</option>
                <option value="H">{__("hidden")}</option>
                <option value="D">{__("disabled")}</option>
            </select>
        </td>
        <td class="right nowrap" data-th="{__("tools")}">
            {include file="buttons/multiple_buttons.tpl" item_id="new_cat_tag" on_add="fn_calculate_usergroups(Tygh.$(this).next('tr'));"}
        </td>
    </tr>
    </table>
</div>
</form>
{/capture}

{capture name="buttons"}
    {include file="buttons/create.tpl" but_name="dispatch[categories.m_add]" but_role="submit-link" but_target_form="categories_m_addition_form"}
{/capture}

{include file="common/mainbox.tpl"
    title=__("add_categories")
    content=$smarty.capture.mainbox
    buttons=$smarty.capture.buttons
    select_storefront=true
    show_all_storefront=true
    selected_storefront_id=$storefront_id
}
