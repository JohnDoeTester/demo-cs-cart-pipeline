{script src="js/tygh/tabs.js"}
{script src="js/tygh/backend/product_features_bulk_edit.js"}

{capture name="mainbox"}

    {include file="common/pagination.tpl"}

    {$r_url=$config.current_url|escape:url}
    {$c_url=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
    {$rev=$smarty.request.content_id|default:"pagination_contents"}
    {$product_feature_statuses=""|fn_get_default_statuses:true}
    {$has_permission=fn_check_permissions("product_features", "update", "admin", "POST")}
    {$has_available_features = empty($runtime.company_id) || in_array($runtime.company_id, array_column($features, 'company_id'))}

    <form action="{""|fn_url}" method="post" name="manage_product_features_form" id="manage_product_features_form">
        {capture name="product_features_table"}
            <input type="hidden" name="return_url" value="{$config.current_url}">
            <div class="items-container{if ""|fn_check_form_permissions} cm-hide-inputs{/if} longtap-selection" id="update_features_list">
                {if $features}
                    <div class="table-responsive-wrapper">
                        <table width="100%" class="table table-middle table--relative table-responsive table--overflow-hidden">
                            <thead 
                                data-ca-bulkedit-default-object="true" 
                                data-ca-bulkedit-component="defaultObject"
                            >
                            <tr>
                                <th class="left mobile-hide table__check-items-column">
                                    {include file="common/check_items.tpl" 
                                        check_statuses=$product_feature_statuses
                                        is_check_disabled=!$has_available_features
                                        meta="table__check-items"
                                    }
                                    <input type="checkbox"
                                        class="bulkedit-toggler hide"
                                        data-ca-bulkedit-disable="[data-ca-bulkedit-default-object=true]" 
                                        data-ca-bulkedit-enable="[data-ca-bulkedit-expanded-object=true]"
                                    />
                                </th>
                                <th width="29%">
                                    <div class="th-text-overflow-wrapper">
                                        {include file="common/table_col_head.tpl"
                                            type="name"
                                            text=__("name_short")
                                        }
                                        {include file="common/tooltip.tpl" tooltip=__("internal_feature_name_tooltip")}
                                        {include file="common/table_col_head.tpl"
                                            type="storefront_name"
                                            text=__("storefront_name_short")
                                            title=__("storefront_name")
                                        }
                                    </div>
                                </th>
                                <th width="20%">
                                    {include file="common/table_col_head.tpl" type="group"}
                                </th>
                                <th class="mobile-hide" width="33%">
                                    {include file="common/table_col_head.tpl" type="categories"}
                                </th>
                                <th class="mobile-hide" width="8%">&nbsp;</th>
                                <th width="10%" class="right">
                                    {include file="common/table_col_head.tpl" type="status"}
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            {foreach $features as $p_feature}
                                {if $p_feature.feature_type == "ProductFeatures::EXTENDED"|enum}
                                    {$show_in_popup = false}
                                {else}
                                    {$show_in_popup = true}
                                {/if}

                                {$non_editable = !$has_permission || !$p_feature|fn_allow_save_object:"product_features"}

                                {if $p_feature.parent_id && isset($group_features[$p_feature.parent_id])}
                                    {$group_feature = $group_features[$p_feature.parent_id]}
                                {else}
                                    {$group_feature = false}
                                {/if}
                                {$href_edit="product_features.update?feature_id=`$p_feature.feature_id`{if $show_in_popup}&return_url=`$r_url`{/if}"}
                                {$href_delete="product_features.delete?feature_id=`$p_feature.feature_id`&return_url=$r_url"}
                                {$feature_category_ids = ($p_feature.categories_path) ? (","|explode:$p_feature.categories_path) : ([])}

                                <tr class="cm-row-item cm-row-status-{$p_feature.status|lower}{if $has_permission} cm-longtap-target{/if}{if $non_editable} longtap-selection-disable{/if}" 
                                    data-ct-product_features="{$p_feature.feature_id}"
                                    data-ca-longtap-action="setCheckBox"
                                    data-ca-longtap-target="input.cm-item"
                                    data-ca-id="{$p_feature.feature_id}"
                                    data-ca-category-ids="{($group_feature) ? ([]|to_json) : ($feature_category_ids|to_json)}"
                                    data-ca-feature-group="{($group_feature) ? "true" : "false"}"
                                    {if $non_editable && $has_permission}
                                        {if fn_allowed_for("MULTIVENDOR")}
                                            data-ca-bulkedit-disabled-notice="{__("product_features_of_marketplace_are_not_selectable")}"
                                        {else}
                                            data-ca-bulkedit-disabled-notice="{__("product_features_are_not_selectable_for_context_menu")}"
                                        {/if}
                                    {/if}
                                >
                                    <td class="left mobile-hide table__check-items-cell">
                                        <input type="checkbox" name="feature_ids[]" value="{$p_feature.feature_id}" class="hide cm-item cm-item-status-{$p_feature.status|lower}" />
                                    </td>
                                    <td width="29%" data-th="{__("feature")}">
                                        <div class="object-group-link-wrap">
                                            <a {if !$show_in_popup}href="{$href_edit|fn_url}"{/if} class="row-status cm-external-click bulkedit-deselect {if $non_editable} no-underline{/if}" data-ca-external-click-id="opener_group{$p_feature.feature_id}">{$p_feature.internal_name}</a>
                                            <span class="muted"><small> #{$p_feature.feature_id}</small></span>
                                            <div><small>{$p_feature.description}</small></div>
                                            {include file="views/companies/components/company_name.tpl" object=$p_feature}
                                        </div>
                                    </td>
                                    <td width="20%" data-th="{__("group")}">
                                        {if $group_feature}
                                            <div class="object-group-link-wrap cm-row-status-{$group_feature.status|lower}">
                                                {if $group_feature.status != "A"}
                                                    {$group_link_class = "row-status"}
                                                {else}
                                                    {$group_link_class = ""}
                                                {/if}
                                                {if !$non_editable}
                                                    {include file="common/popupbox.tpl" link_class="{$group_link_class}" id="group`$group_feature.feature_id`" link_text=$group_feature.internal_name text=$group_feature.internal_name act="edit" href="product_features.update?feature_id=`$group_feature.feature_id`&return_url=`$r_url`" no_icon_link=true}
                                                {else}
                                                    <span class="unedited-element block">{$group_feature.internal_name|default:__("view")}</span>
                                                {/if}
                                            </div>
                                        {else}
                                            -
                                        {/if}
                                    </td>
                                    <td width="33%" class="mobile-hide" data-th="{__("categories")}">
                                        <div class="row-status object-group-details">
                                        {if $group_feature}
                                            {$group_feature.feature_description nofilter}
                                        {else}
                                            {$p_feature.feature_description nofilter}
                                        {/if}
                                        </div>
                                    </td>
                                    <td width="8%" class="nowrap mobile-hide">
                                        <div class="hidden-tools">
                                            {capture name="tools_list"}
                                                {if !$non_editable}
                                                    {if !$show_in_popup}
                                                        <li>{btn type="list" text=__("edit") href=$href_edit}</li>
                                                    {else}
                                                        <li>{include file="common/popupbox.tpl" id="group`$p_feature.feature_id`" text=$p_feature.description act="edit" href=$href_edit no_icon_link=true}</li>
                                                    {/if}
                                                    <li>{btn type="text" text=__("delete") href=$href_delete class="cm-confirm cm-ajax cm-ajax-force cm-ajax-full-render cm-delete-row" data=["data-ca-target-id" => "pagination_contents"] method="POST"}</li>
                                                {else}
                                                    <li>{include file="common/popupbox.tpl" id="group`$p_feature.feature_id`" text=$p_feature.description act="edit" link_text=__("view") href=$href_edit no_icon_link=true}</li>
                                                {/if}
                                            {/capture}
                                            {dropdown content=$smarty.capture.tools_list}
                                        </div>
                                    </td>
                                    <td width="10%" class="right nowrap" data-th="{__("status")}">
                                        {include file="common/select_popup.tpl" popup_additional_class="dropleft" id=$p_feature.feature_id status=$p_feature.status hidden=true object_id_name="feature_id" table="product_features" update_controller="product_features"}
                                    </td>
                                </tr>
                            {/foreach}
                            </tbody>
                        </table>
                    </div>
                {else}
                    <p class="no-items">{__("no_data")}</p>
                {/if}
            <!--update_features_list--></div>
        {/capture}

        {include file="common/context_menu_wrapper.tpl"
            form="manage_product_features_form"
            context_menu_class="bulk-edit--product-features"
            object="product_features"
            items=$smarty.capture.product_features_table
            has_permission=$has_permission
        }
    </form>

    {include file="common/pagination.tpl"}
    {capture name="adv_buttons"}
        {capture name="add_new_picker_2"}
            {include file="views/product_features/update.tpl" feature=[] in_popup=true return_url=$config.current_url}
        {/capture}
        {include file="common/popupbox.tpl" id="add_new_feature" text=__("new_feature") title=__("new_feature") content=$smarty.capture.add_new_picker_2 act="general" icon="icon-plus"}
    {/capture}

    {capture name="sidebar"}
        {include file="common/saved_search.tpl" dispatch="product_features.manage" view_type="product_features"}
        {include file="views/product_features/components/product_features_search_form.tpl" dispatch="product_features.manage"}
    {/capture}
    
    {capture name="buttons"}
        {capture name="tools_list"}
            {if $features}
                {hook name="product_features:list_extra_links"}
                {/hook}
            {/if}
        {/capture}
        {dropdown content=$smarty.capture.tools_list class="mobile-hide"}
    {/capture}

{/capture}
{include file="common/mainbox.tpl" title=__("features") content=$smarty.capture.mainbox select_languages=true buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons sidebar=$smarty.capture.sidebar}
